<?php
/**
 * Commit preflight service.
 *
 * @package MagickAICore
 */

namespace MagickAI\Core\Governance;

use MagickAI\Core\Audit\Audit_Log_Repository;
use MagickAI\Core\Capabilities\Ability_Registry_Adapter;
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

		if ( ! current_user_can( 'manage_options' ) ) {
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

		$approval_context = array(
			'approval_commit_authorized' => true,
			'confirmation_state'        => 'approved_commit',
			'proposal_id'               => $proposal_id,
		);

		$event_id = $this->audit->record(
			'commit.preflighted',
			array(
				'ability_id'            => (string) $proposal['ability_id'],
				'status'                => (string) $proposal['status'],
				'commit_execution'      => false,
				'idempotency_required' => true,
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
			'approval_context'     => $approval_context,
			'commit_execution'     => false,
			'idempotency_required' => true,
		);
	}
}
