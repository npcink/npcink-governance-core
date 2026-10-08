<?php
/**
 * Commit preflight service.
 *
 * @package NpcinkGovernanceCore
 */

namespace Npcink\GovernanceCore\Governance;

use Npcink\GovernanceCore\Audit\Audit_Log_Repository;
use Npcink\GovernanceCore\Capabilities\Ability_Registry_Adapter;
use Npcink\GovernanceCore\Governance\Read_Request_Service;
use Npcink\GovernanceCore\Security\Request_Context;
use Npcink\GovernanceCore\Security\Sensitive_Data_Redactor;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Verifies approval-commit readiness without executing abilities.
 */
final class Commit_Preflight_Service {
	const PREFLIGHT_TTL_SECONDS = 300;

	/**
	 * Write ability to permitted post-execution verification read ability.
	 *
	 * Narrow by design: only the block readback pairing the Adapter execution
	 * contract defines. Generalization requires a new decision record.
	 *
	 * @var array<string,string>
	 */
	const VERIFICATION_READ_ABILITIES = array(
		'npcink-abilities-toolkit/update-post-blocks'          => 'npcink-abilities-toolkit/get-post-blocks',
		'npcink-abilities-toolkit/update-template-blocks'      => 'npcink-abilities-toolkit/get-template-blocks',
		'npcink-abilities-toolkit/upsert-template-blocks'      => 'npcink-abilities-toolkit/get-template-blocks',
		'npcink-abilities-toolkit/update-template-part-blocks' => 'npcink-abilities-toolkit/get-template-part-blocks',
	);

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
	 * Read request service, used only to mint execution verification reads.
	 *
	 * @var Read_Request_Service|null
	 */
	private $read_requests;

	/**
	 * Constructor.
	 *
	 * @param Proposal_Repository      $proposals Proposal repository.
	 * @param Ability_Registry_Adapter $abilities Ability adapter.
	 * @param Audit_Log_Repository     $audit Audit repository.
	 * @param Read_Request_Service|null $read_requests Read request service, used only to mint execution verification reads.
	 */
	public function __construct( Proposal_Repository $proposals, Ability_Registry_Adapter $abilities, Audit_Log_Repository $audit, ?Read_Request_Service $read_requests = null ) {
		$this->proposals     = $proposals;
		$this->abilities     = $abilities;
		$this->audit         = $audit;
		$this->read_requests = $read_requests;
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

		if ( 'ready' !== (string) ( $capability['intake_status'] ?? '' ) ) {
			return $this->preflight_error(
				'npcink_governance_core_ability_intake_blocked',
				__( 'The proposal target ability is blocked by the Core ability intake contract.', 'npcink-governance-core' ),
				409,
				$proposal_id,
				array(
					'ability_id'     => (string) ( $proposal['ability_id'] ?? '' ),
					'status'         => (string) ( $proposal['status'] ?? '' ),
					'intake_reasons' => (array) ( $capability['intake_reasons'] ?? array() ),
				)
			);
		}
		if (
			! in_array( (string) ( $capability['risk_level'] ?? '' ), array( 'write', 'destructive' ), true )
			|| true !== (bool) ( $capability['requires_approval'] ?? false )
			|| 'proposal_required' !== (string) ( $capability['governance_mode'] ?? '' )
			|| 'adapter_after_core_preflight' !== (string) ( $capability['execution_surface'] ?? '' )
		) {
			return $this->preflight_error(
				'npcink_governance_core_ability_not_proposal_eligible',
				__( 'The proposal target ability is no longer eligible for governed write approval.', 'npcink-governance-core' ),
				409,
				$proposal_id,
				array(
					'ability_id'        => (string) ( $proposal['ability_id'] ?? '' ),
					'status'            => (string) ( $proposal['status'] ?? '' ),
					'risk_level'        => (string) ( $capability['risk_level'] ?? '' ),
					'requires_approval' => (bool) ( $capability['requires_approval'] ?? false ),
					'governance_mode'   => (string) ( $capability['governance_mode'] ?? '' ),
					'execution_surface' => (string) ( $capability['execution_surface'] ?? '' ),
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

		$batch_contract_preflight = $this->batch_ability_contract_preflight( $proposal );
		if ( false === (bool) ( $batch_contract_preflight['contract_matches'] ?? true ) ) {
			return $this->preflight_error(
				'npcink_governance_core_ability_contract_changed',
				__( 'A proposal batch action ability contract has changed since approval.', 'npcink-governance-core' ),
				409,
				$proposal_id,
				array(
					'ability_id'         => (string) ( $proposal['ability_id'] ?? '' ),
					'status'             => (string) ( $proposal['status'] ?? '' ),
					'batch_contract_preflight' => $batch_contract_preflight,
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
		$prior_handoff       = $this->prior_preflight_handoff( $proposal_id, $approved_input_hash );
		if ( null !== $prior_handoff ) {
			return $this->already_issued_error( $proposal_id, $approved_input_hash, $proposal, $prior_handoff );
		}

		$correlation_id = $this->new_correlation_id();
		$approved_preview_hash = $this->payload_hash( $proposal['preview'] ?? array() );
		$policy_version        = 'core-preflight-v1';
		$site_binding          = $this->site_binding_context();
		$client_binding        = Request_Context::signed_client_context();
		$expires_at            = gmdate( 'c', time() + self::PREFLIGHT_TTL_SECONDS );
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
			'expires_at'                  => $expires_at,
		) + $client_binding + $site_binding;
		$execution_handoff = array(
			'executor'           => 'adapter_after_core_preflight',
			'execution_surface' => 'wp_abilities_rest',
			'ability_id'        => (string) $proposal['ability_id'],
			'proposal_id'       => $proposal_id,
			'correlation_id'    => $correlation_id,
			'approved_input_hash' => $approved_input_hash,
			'policy_version'    => $policy_version,
			'expires_at'        => $expires_at,
			'core_proxy_execute' => false,
			'commit_execution'  => false,
		) + $client_binding + $site_binding;

		$event_id = $this->audit->record_with_event_id(
			$this->preflight_event_id( $proposal_id, $approved_input_hash ),
			'commit.preflighted',
			array(
				'ability_id'            => (string) $proposal['ability_id'],
				'status'                => (string) $proposal['status'],
				'commit_execution'      => false,
				'idempotency_required' => true,
				'correlation_id'        => $correlation_id,
				'approved_input_hash'   => $approved_input_hash,
				'policy_version'        => $policy_version,
				'expires_at'            => $expires_at,
				'ability_contract_hash' => (string) ( $contract_preflight['current_contract_hash'] ?? '' ),
				'capability'            => (string) ( $permission_preflight['capability'] ?? '' ),
			) + $client_binding,
			$proposal_id
		);

		if ( '' === $event_id ) {
			$prior_handoff = $this->prior_preflight_handoff( $proposal_id, $approved_input_hash );
			if ( null !== $prior_handoff ) {
				return $this->already_issued_error( $proposal_id, $approved_input_hash, $proposal, $prior_handoff );
			}

			return new WP_Error(
				'npcink_governance_core_preflight_audit_failed',
				__( 'Commit preflight could not be audited.', 'npcink-governance-core' ),
				array( 'status' => 500 )
			);
		}

		$verification_reads = $this->mint_execution_verification_reads( $proposal, $request_params, $correlation_id );
		if ( ! empty( $verification_reads['granted'] ) ) {
			$execution_handoff['execution_verification_reads'] = $verification_reads['granted'];
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
			'execution_verification_reads' => $verification_reads,
		);
	}

	/**
	 * Mints single-use approved read requests for post-execution verification.
	 *
	 * The Adapter names the verification reads it will need; Core stays the
	 * authorization owner: each request must match the write ability's
	 * contract-paired readback ability and target the same object as the
	 * approved write input. Mint failures never block the write - the
	 * readback degrades exactly as it does today - but every denial is
	 * recorded for audit.
	 *
	 * @param array<string,mixed> $proposal Approved proposal row.
	 * @param array<string,mixed> $request_params Preflight request parameters.
	 * @param string              $correlation_id Preflight correlation id.
	 * @return array<string,mixed> Granted and denied verification reads.
	 */
	private function mint_execution_verification_reads( array $proposal, array $request_params, string $correlation_id ): array {
		$requested = is_array( $request_params['verification_reads'] ?? null ) ? array_values( (array) $request_params['verification_reads'] ) : array();
		$granted   = array();
		$denied    = array();

		if ( empty( $requested ) || null === $this->read_requests ) {
			return array( 'granted' => $granted, 'denied' => $denied );
		}

		$write_ability_id = (string) ( $proposal['ability_id'] ?? '' );
		$write_input      = is_array( $proposal['input'] ?? null ) ? $proposal['input'] : array();
		$paired_read      = (string) ( self::VERIFICATION_READ_ABILITIES[ $write_ability_id ] ?? '' );

		foreach ( $requested as $read ) {
			$read          = is_array( $read ) ? $read : array();
			$ability_id    = sanitize_text_field( (string) ( $read['ability_id'] ?? '' ) );
			$read_input    = is_array( $read['input'] ?? null ) ? $read['input'] : array();
			$denial_reason = '';

			if ( '' === $paired_read || $ability_id !== $paired_read ) {
				$denial_reason = 'ability_not_paired_with_write';
			} elseif ( ! $this->verification_read_targets_same_object( $write_ability_id, $write_input, $read_input ) ) {
				$denial_reason = 'object_mismatch';
			}

			if ( '' !== $denial_reason ) {
				$denied[] = array(
					'ability_id' => $ability_id,
					'reason'     => $denial_reason,
				);
				continue;
			}

			$request = $this->read_requests->create(
				array(
					'ability_id'              => $ability_id,
					'input'                   => $read_input,
					'requested_input_summary' => 'Post-execution verification read for proposal ' . (string) ( $proposal['proposal_id'] ?? '' ),
					'data_classes'            => array( 'content_blocks' ),
					'purpose'                 => 'Post-execution verification read minted at commit preflight (proposal ' . (string) ( $proposal['proposal_id'] ?? '' ) . ', correlation ' . $correlation_id . ').',
					'caller'                  => array( 'correlation_id' => $correlation_id ),
					'expires_at'              => gmdate( 'Y-m-d H:i:s', time() + self::PREFLIGHT_TTL_SECONDS ),
				)
			);

			if ( is_wp_error( $request ) ) {
				$denied[] = array(
					'ability_id' => $ability_id,
					'reason'     => 'mint_rejected:' . sanitize_key( $request->get_error_code() ),
				);
				continue;
			}

			$approved = $this->read_requests->approve(
				(string) $request['request_id'],
				array(
					'note'        => 'Execution verification read attached to commit preflight.',
					'proposal_id' => (string) ( $proposal['proposal_id'] ?? '' ),
					'source'      => 'execution_verification',
				)
			);

			if ( is_wp_error( $approved ) ) {
				$denied[] = array(
					'ability_id' => $ability_id,
					'reason'     => 'approve_rejected:' . sanitize_key( $approved->get_error_code() ),
				);
				continue;
			}

			$granted[] = array(
				'request_id' => (string) $request['request_id'],
				'ability_id' => $ability_id,
			);
		}

		return array( 'granted' => $granted, 'denied' => $denied );
	}

	/**
	 * Returns whether one verification read targets the same object as the write.
	 *
	 * @param string              $write_ability_id Write ability id.
	 * @param array<string,mixed> $write_input Approved write input.
	 * @param array<string,mixed> $read_input Verification read input.
	 * @return bool
	 */
	private function verification_read_targets_same_object( string $write_ability_id, array $write_input, array $read_input ): bool {
		if ( 'npcink-abilities-toolkit/update-post-blocks' === $write_ability_id ) {
			return (int) ( $write_input['post_id'] ?? 0 ) > 0
				&& (int) ( $write_input['post_id'] ?? 0 ) === (int) ( $read_input['post_id'] ?? 0 );
		}

		$write_slug = sanitize_key( (string) ( $write_input['slug'] ?? '' ) );
		$write_post = (int) ( $write_input['post_id'] ?? 0 );
		$read_slug  = sanitize_key( (string) ( $read_input['slug'] ?? '' ) );
		$read_post  = (int) ( $read_input['post_id'] ?? 0 );

		if ( $write_post > 0 ) {
			return $write_post === $read_post && ( '' === $read_slug || $read_slug === $write_slug );
		}
		if ( '' !== $write_slug ) {
			return $read_slug === $write_slug;
		}

		return false;
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
		$batch_review_summary = $this->batch_review_summary_preflight( $preview );
		$media_alt_guard = $this->media_alt_guard_preflight( $proposal );
		if ( ! empty( $media_alt_guard['applies'] ) && empty( $media_alt_guard['valid'] ) ) {
			$blocking_items[] = array(
				'code'   => 'media_alt_guard_invalid',
				'reason' => 'The approved media ALT input no longer matches its missing-ALT review evidence.',
			);
		}

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
			'batch_review_summary' => $batch_review_summary,
			'media_alt_guard' => $media_alt_guard,
			'commit_execution' => false,
		);
	}

	/**
	 * Revalidates the persisted missing-ALT contract before Adapter handoff.
	 *
	 * Core validates approved evidence consistency only. Adapter must still run
	 * the Toolkit dry-run immediately before commit to compare live media truth.
	 *
	 * @param array<string,mixed> $proposal Proposal row.
	 * @return array<string,mixed>
	 */
	private function media_alt_guard_preflight( array $proposal ): array {
		if ( 'npcink-abilities-toolkit/update-media-details' !== (string) ( $proposal['ability_id'] ?? '' ) ) {
			return array();
		}
		$preview  = is_array( $proposal['preview'] ?? null ) ? $proposal['preview'] : array();
		$evidence = is_array( $preview['media_alt_apply'] ?? null ) ? $preview['media_alt_apply'] : array();
		if ( 'media_alt_apply_plan_item' !== sanitize_key( (string) ( $evidence['artifact_type'] ?? '' ) ) ) {
			return array();
		}
		$input = is_array( $proposal['input'] ?? null ) ? $proposal['input'] : array();
		$valid = absint( $input['attachment_id'] ?? 0 ) > 0
			&& absint( $input['attachment_id'] ?? 0 ) === absint( $evidence['attachment_id'] ?? 0 )
			&& array_key_exists( 'expected_current_alt', $input )
			&& '' === (string) $input['expected_current_alt']
			&& '' === (string) ( $evidence['expected_current_alt'] ?? '' )
			&& true === ( $input['operator_visual_review_confirmed'] ?? false )
			&& true === ( $evidence['operator_visual_review_confirmed'] ?? false )
			&& 'missing' === sanitize_key( (string) ( $evidence['current_alt_status'] ?? '' ) )
			&& sanitize_text_field( (string) ( $input['alt'] ?? '' ) ) === sanitize_text_field( (string) ( $evidence['proposed_alt'] ?? '' ) )
			&& '' !== trim( sanitize_text_field( (string) ( $input['idempotency_key'] ?? '' ) ) );

		return array(
			'applies'                  => true,
			'valid'                    => $valid,
			'contract_version'         => 'media_alt_apply_plan.v1',
			'attachment_id'            => absint( $input['attachment_id'] ?? 0 ),
			'expected_current_alt'     => '',
			'visual_review_confirmed'  => true === ( $input['operator_visual_review_confirmed'] ?? false ),
			'idempotency_key_present'  => '' !== trim( sanitize_text_field( (string) ( $input['idempotency_key'] ?? '' ) ) ),
			'live_value_check_owner'   => 'adapter_toolkit_dry_run_before_commit',
			'requires_live_value_check' => true,
		);
	}

	/**
	 * Returns a bounded operator-facing batch review summary for preflight responses.
	 *
	 * @param array<string,mixed> $preview Proposal preview.
	 * @return array<string,mixed>
	 */
	private function batch_review_summary_preflight( array $preview ): array {
		$summary = is_array( $preview['batch_review_summary'] ?? null ) ? $preview['batch_review_summary'] : array();
		$version = sanitize_key( (string) ( $summary['summary_version'] ?? '' ) );
		if ( 'core-batch-review-summary-v1' !== $version ) {
			return array();
		}

		$target_ability_ids = array();
		foreach ( (array) ( $summary['target_ability_ids'] ?? array() ) as $target_id ) {
			if ( ! is_scalar( $target_id ) ) {
				continue;
			}
			$target_id = sanitize_text_field( (string) $target_id );
			if ( '' !== $target_id ) {
				$target_ability_ids[] = $target_id;
			}
		}

		return array(
			'summary_version'       => $version,
			'plan_ability_id'       => sanitize_text_field( (string) ( $summary['plan_ability_id'] ?? '' ) ),
			'batch_id'              => sanitize_text_field( (string) ( $summary['batch_id'] ?? '' ) ),
			'action_count'          => absint( $summary['action_count'] ?? 0 ),
			'executable_count'      => absint( $summary['executable_count'] ?? 0 ),
			'blocked_count'         => absint( $summary['blocked_count'] ?? 0 ),
			'needs_input_count'     => absint( $summary['needs_input_count'] ?? 0 ),
			'warning_count'         => absint( $summary['warning_count'] ?? 0 ),
			'target_ability_ids'    => $target_ability_ids,
			'proposal_ready'        => (bool) ( $summary['proposal_ready'] ?? false ),
			'retryable'             => (bool) ( $summary['retryable'] ?? false ),
			'operator_next_action'  => sanitize_key( (string) ( $summary['operator_next_action'] ?? '' ) ),
			'final_execution_owner' => sanitize_key( (string) ( $summary['final_execution_owner'] ?? '' ) ),
			'core_execution'        => false,
			'commit_execution'      => false,
			'blocked_items'         => Sensitive_Data_Redactor::sanitize_payload( is_array( $summary['blocked_items'] ?? null ) ? $summary['blocked_items'] : array() ),
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
	 * Returns the current WordPress site binding for Core-issued handoff context.
	 *
	 * @return array<string,mixed>
	 */
	private function site_binding_context(): array {
		return array(
			'site_url' => $this->normalize_url( function_exists( 'site_url' ) ? site_url() : '' ),
			'home_url' => $this->normalize_url( function_exists( 'home_url' ) ? home_url() : '' ),
			'blog_id'  => function_exists( 'get_current_blog_id' ) ? get_current_blog_id() : 0,
		);
	}

	/**
	 * Normalizes a URL for context binding comparisons.
	 *
	 * @param string $url URL.
	 * @return string
	 */
	private function normalize_url( string $url ): string {
		return rtrim( $url, '/' );
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
	 * Revalidates every action contract captured in a batch proposal.
	 *
	 * @param array<string,mixed> $proposal Proposal row.
	 * @return array<string,mixed>
	 */
	private function batch_ability_contract_preflight( array $proposal ): array {
		$caller    = is_array( $proposal['caller'] ?? null ) ? $proposal['caller'] : array();
		$guardrail = is_array( $caller['core_guardrails'] ?? null ) ? $caller['core_guardrails'] : array();
		$approved  = is_array( $guardrail['batch_action_guardrails'] ?? null ) ? array_values( $guardrail['batch_action_guardrails'] ) : array();
		$actions   = is_array( $proposal['input']['write_actions'] ?? null ) ? array_values( $proposal['input']['write_actions'] ) : array();
		if ( empty( $approved ) || empty( $actions ) ) {
			return array( 'contract_matches' => true, 'actions' => array() );
		}

		$current = array();
		$matches = true;
		foreach ( $actions as $index => $action ) {
			if ( ! is_array( $action ) ) {
				$matches = false;
				continue;
			}
			$action_id = sanitize_key( (string) ( $action['action_id'] ?? '' ) );
			$action_index = absint( $action['action_index'] ?? $index );
			$approved_row = null;
			foreach ( $approved as $row ) {
				if ( is_array( $row ) && $action_index === absint( $row['action_index'] ?? -1 ) && $action_id === sanitize_key( (string) ( $row['action_id'] ?? '' ) ) ) {
					$approved_row = $row;
					break;
				}
			}
			$target_id = sanitize_text_field( (string) ( $action['target_ability_id'] ?? '' ) );
			$capability = $this->abilities->find( $target_id );
			$hash = null !== $capability ? $this->ability_contract_hash( $capability ) : '';
			$match = is_array( $approved_row )
				&& $target_id === (string) ( $approved_row['target_ability_id'] ?? '' )
				&& $hash === (string) ( $approved_row['ability_contract_hash'] ?? '' );
			$matches = $matches && $match;
			$current[] = array(
				'action_id' => $action_id,
				'action_index' => $action_index,
				'target_ability_id' => $target_id,
				'contract_hash' => $hash,
				'matches' => $match,
			);
		}

		return array( 'contract_matches' => $matches, 'actions' => $current );
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
	 * Returns the prior execution-handoff metadata for this approved input, or
	 * null when no commit preflight was issued yet.
	 *
	 * @param string $proposal_id Proposal id.
	 * @param string $approved_input_hash Approved input hash.
	 * @return array<string,mixed>|null
	 */
	private function prior_preflight_handoff( string $proposal_id, string $approved_input_hash ): ?array {
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
				return $metadata;
			}
		}

		return null;
	}

	/**
	 * Returns the 409 error for a repeated preflight, echoing the original
	 * handoff identifiers so a client that lost the first response can still
	 * record execution.
	 *
	 * @param string $proposal_id Proposal id.
	 * @param string $approved_input_hash Approved input hash.
	 * @param array<string,mixed> $prior_handoff Prior handoff metadata.
	 * @return WP_Error
	 */
	private function already_issued_error( string $proposal_id, string $approved_input_hash, array $proposal, array $prior_handoff ): WP_Error {
		return $this->preflight_error(
			'npcink_governance_core_commit_preflight_already_issued',
			__( 'Commit preflight has already issued an execution handoff for this approved proposal.', 'npcink-governance-core' ),
			409,
			$proposal_id,
			array(
				'ability_id'           => (string) ( $proposal['ability_id'] ?? '' ),
				'status'               => (string) ( $proposal['status'] ?? '' ),
				'approved_input_hash'  => $approved_input_hash,
				'idempotency_required' => true,
			),
			array(
				'approved_input_hash'  => $approved_input_hash,
				'correlation_id'       => (string) ( $prior_handoff['correlation_id'] ?? '' ),
				'expires_at'           => (string) ( $prior_handoff['expires_at'] ?? '' ),
				'idempotency_required' => true,
			)
		);
	}

	/**
	 * Returns a deterministic event id for one successful preflight handoff.
	 *
	 * @param string $proposal_id Proposal id.
	 * @param string $approved_input_hash Approved input hash.
	 * @return string
	 */
	private function preflight_event_id( string $proposal_id, string $approved_input_hash ): string {
		return 'preflight_' . substr( hash( 'sha256', $proposal_id . '|' . $approved_input_hash ), 0, 48 );
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
			$event_id = $this->audit->record(
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
			if ( '' === $event_id ) {
				return new WP_Error(
					'npcink_governance_core_preflight_failure_audit_failed',
					__( 'Commit preflight failure could not be audited.', 'npcink-governance-core' ),
					array(
						'status'        => 500,
						'original_code' => $code,
					)
				);
			}
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
			'intake_status'     => sanitize_key( (string) ( $capability['intake_status'] ?? '' ) ),
			'intake_reasons'    => array_values( array_map( 'sanitize_key', (array) ( $capability['intake_reasons'] ?? array() ) ) ),
			'governance_mode'   => sanitize_key( (string) ( $capability['governance_mode'] ?? '' ) ),
			'execution_surface' => sanitize_key( (string) ( $capability['execution_surface'] ?? '' ) ),
			'capability'        => sanitize_key( (string) ( $capability['capability'] ?? '' ) ),
			'required_scope'    => sanitize_text_field( (string) ( $capability['required_scope'] ?? '' ) ),
			'required_scopes'   => $required_scopes,
			'implementation_posture' => $this->normalize_payload_for_hash( is_array( $capability['implementation_posture'] ?? null ) ? $capability['implementation_posture'] : array() ),
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
