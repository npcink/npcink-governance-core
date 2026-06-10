<?php
/**
 * Commit preflight service.
 *
 * @package NpcinkGovernanceCore
 */

namespace Npcink\GovernanceCore\Governance;

use Npcink\GovernanceCore\Audit\Audit_Log_Repository;
use Npcink\GovernanceCore\Capabilities\Ability_Registry_Adapter;
use Npcink\GovernanceCore\Security\Request_Context;
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
				'npcink_governance_core_legacy_confirmation_rejected',
				__( 'Legacy confirmation parameters are not accepted by npcink-governance-core.', 'npcink-governance-core' ),
				array( 'status' => 400 )
			);
		}

		if ( ! current_user_can( 'manage_options' ) && ! Request_Context::has_scope( 'commit:preflight' ) ) {
			return new WP_Error(
				'npcink_governance_core_preflight_forbidden',
				__( 'You do not have permission to preflight this proposal.', 'npcink-governance-core' ),
				array( 'status' => 403 )
			);
		}

		$proposal_id = sanitize_text_field( $proposal_id );
		$proposal    = $this->proposals->find( $proposal_id );

		if ( null === $proposal ) {
			return new WP_Error(
				'npcink_governance_core_proposal_not_found',
				__( 'Proposal was not found.', 'npcink-governance-core' ),
				array( 'status' => 404 )
			);
		}

		if ( 'approved' !== (string) ( $proposal['status'] ?? '' ) ) {
			return $this->preflight_error(
				'npcink_governance_core_proposal_not_approved',
				__( 'Only approved proposals can pass commit preflight.', 'npcink-governance-core' ),
				409,
				$proposal_id,
				array(
					'ability_id' => (string) ( $proposal['ability_id'] ?? '' ),
					'status'     => (string) ( $proposal['status'] ?? '' ),
				)
			);
		}

		$capability = $this->abilities->find( (string) ( $proposal['ability_id'] ?? '' ) );
		if ( null === $capability ) {
			return $this->preflight_error(
				'npcink_governance_core_ability_unavailable',
				__( 'The proposal target ability is no longer available.', 'npcink-governance-core' ),
				409,
				$proposal_id,
				array(
					'ability_id' => (string) ( $proposal['ability_id'] ?? '' ),
					'status'     => (string) ( $proposal['status'] ?? '' ),
				)
			);
		}

		$contract_preflight = $this->ability_contract_preflight( $proposal, $capability );
		if ( false === (bool) ( $contract_preflight['contract_matches'] ?? false ) ) {
			return $this->preflight_error(
				'npcink_governance_core_ability_contract_changed',
				__( 'The proposal target ability contract has changed since approval.', 'npcink-governance-core' ),
				409,
				$proposal_id,
				array(
					'ability_id'           => (string) ( $proposal['ability_id'] ?? '' ),
					'status'               => (string) ( $proposal['status'] ?? '' ),
					'contract_preflight'   => $contract_preflight,
					'idempotency_required' => true,
				)
			);
		}

		$permission_preflight = $this->ability_permission_preflight( $capability );
		if ( false === (bool) ( $permission_preflight['allowed'] ?? false ) ) {
			return $this->preflight_error(
				'npcink_governance_core_ability_permission_denied',
				__( 'The current caller no longer has permission for this ability.', 'npcink-governance-core' ),
				403,
				$proposal_id,
				array(
					'ability_id'             => (string) ( $proposal['ability_id'] ?? '' ),
					'status'                 => (string) ( $proposal['status'] ?? '' ),
					'permission_preflight'   => $permission_preflight,
					'idempotency_required'   => true,
				)
			);
		}

		$item_preflight = $this->proposal_item_preflight( $proposal );
		if ( false === (bool) ( $item_preflight['executable'] ?? false ) ) {
			return $this->preflight_error(
				'npcink_governance_core_proposal_items_blocked',
				__( 'Proposal contains blocked items or missing required input.', 'npcink-governance-core' ),
				409,
				$proposal_id,
				array(
					'ability_id'              => (string) ( $proposal['ability_id'] ?? '' ),
					'status'                  => (string) ( $proposal['status'] ?? '' ),
					'proposal_item_preflight' => $item_preflight,
					'idempotency_required'    => true,
				),
				array(
					'proposal'                => $proposal,
					'proposal_item_preflight' => $item_preflight,
					'commit_execution'        => false,
				)
			);
		}

		$approved_input_hash = $this->payload_hash( $proposal['input'] ?? array() );
		if ( $this->has_prior_preflight( $proposal_id, $approved_input_hash ) ) {
			return $this->preflight_error(
				'npcink_governance_core_commit_preflight_already_issued',
				__( 'Commit preflight has already issued an execution handoff for this approved proposal.', 'npcink-governance-core' ),
				409,
				$proposal_id,
				array(
					'ability_id'             => (string) ( $proposal['ability_id'] ?? '' ),
					'status'                 => (string) ( $proposal['status'] ?? '' ),
					'approved_input_hash'    => $approved_input_hash,
					'idempotency_required'   => true,
				)
			);
		}

		$correlation_id = $this->new_correlation_id();
		$approved_preview_hash = $this->payload_hash( $proposal['preview'] ?? array() );
		$policy_version        = 'core-preflight-v1';
		$approval_context = array(
			'approval_commit_authorized' => true,
			'confirmation_state'        => 'approved_commit',
			'proposal_id'               => $proposal_id,
			'ability_id'                 => (string) $proposal['ability_id'],
			'correlation_id'            => $correlation_id,
			'approved_input_hash'        => $approved_input_hash,
			'approved_preview_hash'      => $approved_preview_hash,
			'approval_updated_at'        => (string) ( $proposal['updated_at'] ?? '' ),
			'policy_version'             => $policy_version,
		);
		$execution_handoff = array(
			'executor'           => 'adapter_after_core_preflight',
			'execution_surface' => 'wp_abilities_rest',
			'ability_id'        => (string) $proposal['ability_id'],
			'proposal_id'       => $proposal_id,
			'correlation_id'    => $correlation_id,
			'approved_input_hash' => $approved_input_hash,
			'policy_version'    => $policy_version,
			'core_proxy_execute' => false,
			'commit_execution'  => false,
		);

		$event_id = $this->audit->record(
			'commit.preflighted',
			array(
				'ability_id'            => (string) $proposal['ability_id'],
				'status'                => (string) $proposal['status'],
				'commit_execution'      => false,
				'idempotency_required' => true,
				'correlation_id'        => $correlation_id,
				'approved_input_hash'   => $approved_input_hash,
				'policy_version'        => $policy_version,
				'ability_contract_hash' => (string) ( $contract_preflight['current_contract_hash'] ?? '' ),
				'capability'            => (string) ( $permission_preflight['capability'] ?? '' ),
			),
			$proposal_id
		);

		if ( '' === $event_id ) {
			return new WP_Error(
				'npcink_governance_core_preflight_audit_failed',
				__( 'Commit preflight could not be audited.', 'npcink-governance-core' ),
				array( 'status' => 500 )
			);
		}

		return array(
			'proposal'             => $proposal,
			'capability'           => $capability,
			'contract_preflight'   => $contract_preflight,
			'permission_preflight' => $permission_preflight,
			'proposal_item_preflight' => $item_preflight,
			'approval_context'     => $approval_context,
			'execution_handoff'    => $execution_handoff,
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
		return function_exists( 'wp_generate_uuid4' ) ? wp_generate_uuid4() : uniqid( 'npcink_governance_core_corr_', true );
	}

	/**
	 * Returns a stable hash for an approved structured payload.
	 *
	 * @param mixed $payload Payload.
	 * @return string
	 */
	private function payload_hash( $payload ): string {
		$json = wp_json_encode( $payload );
		return hash( 'sha256', is_string( $json ) ? $json : '' );
	}

	/**
	 * Verifies the approved proposal still matches the live ability contract.
	 *
	 * @param array<string,mixed> $proposal Proposal row.
	 * @param array<string,mixed> $capability Live capability row.
	 * @return array<string,mixed>
	 */
	private function ability_contract_preflight( array $proposal, array $capability ): array {
		$caller       = is_array( $proposal['caller'] ?? null ) ? $proposal['caller'] : array();
		$guardrails   = is_array( $caller['core_guardrails'] ?? null ) ? $caller['core_guardrails'] : array();
		$approved     = sanitize_text_field( (string) ( $guardrails['ability_contract_hash'] ?? '' ) );
		$current      = $this->ability_contract_hash( $capability );

		return array(
			'contract_matches'       => '' !== $approved && $approved === $current,
			'approved_contract_hash' => $approved,
			'current_contract_hash'  => $current,
			'approved_contract'      => is_array( $guardrails['ability_contract'] ?? null ) ? $guardrails['ability_contract'] : array(),
			'current_contract'       => $this->ability_contract_fingerprint( $capability ),
		);
	}

	/**
	 * Verifies the current caller still satisfies the ability permission boundary.
	 *
	 * @param array<string,mixed> $capability Live capability row.
	 * @return array<string,mixed>
	 */
	private function ability_permission_preflight( array $capability ): array {
		$capability_name = sanitize_key( (string) ( $capability['capability'] ?? '' ) );
		if ( '' === $capability_name || Request_Context::is_app() ) {
			return array(
				'allowed'    => true,
				'capability' => $capability_name,
				'source'     => Request_Context::is_app() ? 'app_scope' : 'none_required',
			);
		}

		return array(
			'allowed'    => current_user_can( $capability_name ),
			'capability' => $capability_name,
			'source'     => 'current_user_can',
		);
	}

	/**
	 * Returns whether an execution handoff was already issued for this approved input.
	 *
	 * @param string $proposal_id Proposal id.
	 * @param string $approved_input_hash Approved input hash.
	 * @return bool
	 */
	private function has_prior_preflight( string $proposal_id, string $approved_input_hash ): bool {
		$events = $this->audit->list_filtered(
			array(
				'proposal_id' => $proposal_id,
				'event_name'  => 'commit.preflighted',
				'limit'       => 1,
			)
		);

		foreach ( $events as $event ) {
			$metadata = is_array( $event['metadata'] ?? null ) ? $event['metadata'] : array();
			if ( $approved_input_hash === (string) ( $metadata['approved_input_hash'] ?? '' ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Records and returns a preflight failure.
	 *
	 * @param string              $code Error code.
	 * @param string              $message Error message.
	 * @param int                 $status HTTP status.
	 * @param string              $proposal_id Proposal id.
	 * @param array<string,mixed> $metadata Audit metadata.
	 * @param array<string,mixed> $data Extra error data.
	 * @return WP_Error
	 */
	private function preflight_error( string $code, string $message, int $status, string $proposal_id = '', array $metadata = array(), array $data = array() ): WP_Error {
		if ( '' !== $proposal_id ) {
			$this->audit->record(
				'commit.preflight_failed',
				array_merge(
					array(
						'error_code'       => $code,
						'status'           => $status,
						'commit_execution' => false,
					),
					$metadata
				),
				$proposal_id
			);
		}

			return new WP_Error(
				$code,
				$message,
			array_merge(
				array(
					'status'           => $status,
					'commit_execution' => false,
				),
				$data
			)
		);
	}

	/**
	 * Returns the stable contract hash for a normalized ability row.
	 *
	 * @param array<string,mixed> $capability Normalized capability row.
	 * @return string
	 */
	private function ability_contract_hash( array $capability ): string {
		$json = wp_json_encode( $this->ability_contract_fingerprint( $capability ) );

		return hash( 'sha256', is_string( $json ) ? $json : '' );
	}

	/**
	 * Returns the governance-relevant part of a normalized ability row.
	 *
	 * @param array<string,mixed> $capability Normalized capability row.
	 * @return array<string,mixed>
	 */
	private function ability_contract_fingerprint( array $capability ): array {
		$required_scopes = array_values( array_map( 'sanitize_text_field', (array) ( $capability['required_scopes'] ?? array() ) ) );
		sort( $required_scopes );

		return array(
			'ability_id'        => sanitize_text_field( (string) ( $capability['ability_id'] ?? '' ) ),
			'risk_level'        => sanitize_key( (string) ( $capability['risk_level'] ?? '' ) ),
			'requires_approval' => (bool) ( $capability['requires_approval'] ?? false ),
			'governance_mode'   => sanitize_key( (string) ( $capability['governance_mode'] ?? '' ) ),
			'execution_surface' => sanitize_key( (string) ( $capability['execution_surface'] ?? '' ) ),
			'capability'        => sanitize_key( (string) ( $capability['capability'] ?? '' ) ),
			'required_scope'    => sanitize_text_field( (string) ( $capability['required_scope'] ?? '' ) ),
			'required_scopes'   => $required_scopes,
			'input_schema'      => $this->normalize_payload_for_hash( $this->sanitize_payload_for_hash( $capability['input_schema'] ?? array() ) ),
		);
	}

	/**
	 * Normalizes array key order before hashing.
	 *
	 * @param mixed $value Value.
	 * @return mixed
	 */
	private function normalize_payload_for_hash( $value ) {
		if ( ! is_array( $value ) ) {
			return $value;
		}

		$normalized = array();
		foreach ( $value as $key => $item ) {
			$normalized[ $key ] = $this->normalize_payload_for_hash( $item );
		}
		ksort( $normalized );

		return $normalized;
	}

	/**
	 * Sanitizes structured payloads before hashing.
	 *
	 * @param mixed $value Value.
	 * @return mixed
	 */
	private function sanitize_payload_for_hash( $value ) {
		if ( is_array( $value ) ) {
			$clean = array();
			foreach ( $value as $key => $item ) {
				$clean[ sanitize_key( (string) $key ) ] = $this->sanitize_payload_for_hash( $item );
			}
			return $clean;
		}

		if ( is_string( $value ) ) {
			return sanitize_textarea_field( $value );
		}

		if ( is_bool( $value ) || is_int( $value ) || is_float( $value ) || null === $value ) {
			return $value;
		}

		return sanitize_text_field( (string) $value );
	}
}
