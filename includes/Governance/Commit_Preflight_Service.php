<?php
/**
 * Commit preflight service.
 *
 * @package MagickAICore
 */

namespace MagickAI\Core\Governance;

use MagickAI\Core\Audit\Audit_Log_Repository;
use MagickAI\Core\Capabilities\Ability_Registry_Adapter;
use MagickAI\Core\Security\Request_Context;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Verifies approval-commit readiness without executing abilities.
 */
final class Commit_Preflight_Service {
	/**
	 * Proposal repository.
	 *
	 * @var Proposal_Repository
	 */
	private $proposals;

	/**
	 * Ability adapter.
	 *
	 * @var Ability_Registry_Adapter
	 */
	private $abilities;

	/**
	 * Audit repository.
	 *
	 * @var Audit_Log_Repository
	 */
	private $audit;

	/**
	 * Constructor.
	 *
	 * @param Proposal_Repository      $proposals Proposal repository.
	 * @param Ability_Registry_Adapter $abilities Ability adapter.
	 * @param Audit_Log_Repository     $audit Audit repository.
	 */
	public function __construct( Proposal_Repository $proposals, Ability_Registry_Adapter $abilities, Audit_Log_Repository $audit ) {
		$this->proposals = $proposals;
		$this->abilities = $abilities;
		$this->audit     = $audit;
	}

	/**
	 * Runs commit preflight checks.
	 *
	 * @param string              $proposal_id Proposal id.
	 * @param array<string,mixed> $request_params Request parameters.
	 * @return array<string,mixed>|WP_Error
	 */
	public function preflight( string $proposal_id, array $request_params = array() ) {
		if ( array_key_exists( 'confirm_token', $request_params ) || array_key_exists( 'write_confirmed', $request_params ) ) {
			return new WP_Error(
				'magick_ai_core_legacy_confirmation_rejected',
				__( 'Legacy confirmation parameters are not accepted by Magick AI Core.', 'magick-ai-core' ),
				array( 'status' => 400 )
			);
		}

		if ( ! current_user_can( 'manage_options' ) && ! Request_Context::has_scope( 'commit:preflight' ) ) {
			return new WP_Error(
				'magick_ai_core_preflight_forbidden',
				__( 'You do not have permission to preflight this proposal.', 'magick-ai-core' ),
				array( 'status' => 403 )
			);
		}

		$proposal_id = sanitize_text_field( $proposal_id );
		$proposal    = $this->proposals->find( $proposal_id );

		if ( null === $proposal ) {
			return new WP_Error(
				'magick_ai_core_proposal_not_found',
				__( 'Proposal was not found.', 'magick-ai-core' ),
				array( 'status' => 404 )
			);
		}

		if ( 'approved' !== (string) ( $proposal['status'] ?? '' ) ) {
			return new WP_Error(
				'magick_ai_core_proposal_not_approved',
				__( 'Only approved proposals can pass commit preflight.', 'magick-ai-core' ),
				array( 'status' => 409 )
			);
		}

		$capability = $this->abilities->find( (string) ( $proposal['ability_id'] ?? '' ) );
		if ( null === $capability ) {
			return new WP_Error(
				'magick_ai_core_ability_unavailable',
				__( 'The proposal target ability is no longer available.', 'magick-ai-core' ),
				array( 'status' => 409 )
			);
		}

		$item_preflight = $this->proposal_item_preflight( $proposal );
		if ( false === (bool) ( $item_preflight['executable'] ?? false ) ) {
			return new WP_Error(
				'magick_ai_core_proposal_items_blocked',
				__( 'Proposal contains blocked items or missing required input.', 'magick-ai-core' ),
				array(
					'status'                  => 409,
					'proposal'                => $proposal,
					'proposal_item_preflight' => $item_preflight,
					'commit_execution'        => false,
				)
			);
		}

		$correlation_id = $this->new_correlation_id();
		$approval_context = array(
			'approval_commit_authorized' => true,
			'confirmation_state'        => 'approved_commit',
			'proposal_id'               => $proposal_id,
			'correlation_id'            => $correlation_id,
		);

		$event_id = $this->audit->record(
			'commit.preflighted',
			array(
				'ability_id'            => (string) $proposal['ability_id'],
				'status'                => (string) $proposal['status'],
				'commit_execution'      => false,
				'idempotency_required' => true,
				'correlation_id'        => $correlation_id,
			),
			$proposal_id
		);

		if ( '' === $event_id ) {
			return new WP_Error(
				'magick_ai_core_preflight_audit_failed',
				__( 'Commit preflight could not be audited.', 'magick-ai-core' ),
				array( 'status' => 500 )
			);
		}

		return array(
			'proposal'             => $proposal,
			'capability'           => $capability,
			'proposal_item_preflight' => $item_preflight,
			'approval_context'     => $approval_context,
			'correlation_id'       => $correlation_id,
			'commit_execution'     => false,
			'idempotency_required' => true,
		);
	}

	/**
	 * Returns proposal item readiness for commit preflight.
	 *
	 * @param array<string,mixed> $proposal Proposal row.
	 * @return array<string,mixed>
	 */
	private function proposal_item_preflight( array $proposal ): array {
		$preview         = is_array( $proposal['preview'] ?? null ) ? $proposal['preview'] : array();
		$needs_input     = array_values( array_map( 'sanitize_key', (array) ( $preview['needs_input'] ?? array() ) ) );
		$blocking_items  = is_array( $preview['preflight_blockers'] ?? null ) ? array_values( $preview['preflight_blockers'] ) : array();
		$proposal_ready  = array_key_exists( 'proposal_ready', $preview ) ? (bool) $preview['proposal_ready'] : true;

		if ( ! $proposal_ready && empty( $blocking_items ) ) {
			$blocking_items[] = array(
				'code'   => 'proposal_not_ready',
				'reason' => 'Proposal preview marks this item as not ready for commit preflight.',
			);
		}

		return array(
			'executable'     => $proposal_ready && empty( $needs_input ) && empty( $blocking_items ),
			'proposal_ready' => $proposal_ready,
			'needs_input'    => $needs_input,
			'blocked_items'  => $blocking_items,
			'warnings'       => is_array( $preview['warnings'] ?? null ) ? $preview['warnings'] : array(),
			'commit_execution' => false,
		);
	}

	/**
	 * Returns a new preflight correlation id.
	 *
	 * @return string
	 */
	private function new_correlation_id(): string {
		return function_exists( 'wp_generate_uuid4' ) ? wp_generate_uuid4() : uniqid( 'mai_corr_', true );
	}
}
