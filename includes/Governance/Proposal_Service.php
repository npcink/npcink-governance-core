<?php
/**
 * Proposal service.
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
 * Coordinates proposal records and audit events.
 */
final class Proposal_Service {
	const PENDING_TTL_SECONDS = 86400;
	const PENDING_QUOTA_PER_APP = 20;
	const PENDING_QUOTA_PER_USER = 1000;

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
	 * Approval policy evaluator.
	 *
	 * @var Approval_Policy_Evaluator
	 */
	private $policy_evaluator;

	/**
	 * Whether stale pending expiry has run before create in this request.
	 *
	 * @var bool
	 */
	private $stale_expired_for_create = false;

	/**
	 * Constructor.
	 *
	 * @param Proposal_Repository      $proposals Proposal repository.
	 * @param Ability_Registry_Adapter $abilities Ability adapter.
	 * @param Audit_Log_Repository     $audit Audit repository.
	 * @param Approval_Policy_Evaluator $policy_evaluator Policy evaluator.
	 */
	public function __construct(
		Proposal_Repository $proposals,
		Ability_Registry_Adapter $abilities,
		Audit_Log_Repository $audit,
		Approval_Policy_Evaluator $policy_evaluator
	) {
		$this->proposals        = $proposals;
		$this->abilities        = $abilities;
		$this->audit            = $audit;
		$this->policy_evaluator = $policy_evaluator;
	}

	/**
	 * Creates a proposal.
	 *
	 * @param array<string,mixed> $payload Payload.
	 * @return array<string,mixed>|WP_Error
	 */
	public function create( array $payload ) {
		$ability_id = sanitize_text_field( (string) ( $payload['ability_id'] ?? '' ) );
		if ( '' === $ability_id || false === strpos( $ability_id, '/' ) ) {
			return new WP_Error(
				'magick_ai_core_invalid_ability_id',
				__( 'A namespaced ability_id is required.', 'magick-ai-core' ),
				array( 'status' => 400 )
			);
		}

		$capability = $this->abilities->find( $ability_id );
		if ( null === $capability ) {
			return new WP_Error(
				'magick_ai_core_ability_not_available',
				__( 'Proposal target ability is not available.', 'magick-ai-core' ),
				array( 'status' => 404 )
			);
		}

		$input  = is_array( $payload['input'] ?? null ) ? $payload['input'] : array();
		$preview = is_array( $payload['preview'] ?? null ) ? $payload['preview'] : array();
		$caller = is_array( $payload['caller'] ?? null ) ? $payload['caller'] : array();
		if ( Request_Context::is_app() ) {
			$caller['auth'] = Request_Context::audit_metadata();
		}

		$guardrail = $this->proposal_create_guardrail( $ability_id, $input, $capability );
		$caller['core_guardrails'] = $guardrail;
		$policy = $this->policy_evaluator->evaluate(
			array(
				'ability_id' => $ability_id,
				'input'      => $input,
				'preview'    => $preview,
				'caller'     => $caller,
			)
		);
		$caller['core_policy'] = $policy;
		$this->expire_stale_pending_before_create();

		$pending = $this->proposals->list_pending_for_guardrail( (string) $guardrail['pending_quota_key'], '', max( 500, (int) $guardrail['pending_quota_limit'] ) );
		$duplicate = $this->find_duplicate_pending_proposal( $pending, $ability_id, (string) $guardrail['input_hash'], (string) $guardrail['pending_quota_key'] );
		if ( null !== $duplicate ) {
			$duplicate['deduplicated'] = true;
			$duplicate['dedupe']       = array(
				'reason'      => 'pending_equivalent_exists',
				'ability_id'  => $ability_id,
				'input_hash'  => (string) $guardrail['input_hash'],
			);
			$this->audit->record(
				'proposal.deduplicated',
				array(
					'ability_id' => $ability_id,
					'status'     => $duplicate['status'],
				),
				(string) $duplicate['proposal_id']
			);
			return $duplicate;
		}

		$pending_count = $this->count_pending_for_quota( $pending, (string) $guardrail['pending_quota_key'] );
		if ( $pending_count >= (int) $guardrail['pending_quota_limit'] ) {
			$this->audit->record(
				'proposal.quota_blocked',
				array(
					'ability_id'     => $ability_id,
					'pending_count'  => $pending_count,
					'quota_limit'    => (int) $guardrail['pending_quota_limit'],
					'quota_subject'  => (string) $guardrail['pending_quota_subject'],
				)
			);

			return new WP_Error(
				'magick_ai_core_pending_proposal_quota_exceeded',
				__( 'Too many pending proposals exist for this caller.', 'magick-ai-core' ),
				array(
					'status'        => 429,
					'pending_count' => $pending_count,
					'quota_limit'   => (int) $guardrail['pending_quota_limit'],
					'quota_subject' => (string) $guardrail['pending_quota_subject'],
				)
			);
		}

		$proposal = $this->proposals->create(
			array(
				'ability_id' => $ability_id,
				'title'      => $payload['title'] ?? '',
				'summary'    => $payload['summary'] ?? '',
				'input'      => $input,
				'preview'    => $preview,
				'caller'     => $caller,
			)
		);

		if ( is_wp_error( $proposal ) ) {
			return $proposal;
		}

		$event_id = $this->audit->record(
			'proposal.created',
			$this->policy_audit_metadata( $proposal, $policy, false ),
			(string) $proposal['proposal_id']
		);

		if ( '' === $event_id ) {
			$this->proposals->delete_by_proposal_id( (string) $proposal['proposal_id'] );
			return $this->audit_failed_error( 'magick_ai_core_proposal_audit_failed' );
		}

		$policy_event_id = $this->audit->record(
			'proposal.policy_evaluated',
			$this->policy_audit_metadata( $proposal, $policy, false ),
			(string) $proposal['proposal_id']
		);

		if ( '' === $policy_event_id ) {
			$this->proposals->delete_by_proposal_id( (string) $proposal['proposal_id'] );
			return $this->audit_failed_error( 'magick_ai_core_policy_decision_audit_failed' );
		}

		$auto_approved = $this->maybe_auto_approve_created_proposal( $proposal, $policy );
		if ( is_wp_error( $auto_approved ) ) {
			return $auto_approved;
		}
		if ( is_array( $auto_approved ) ) {
			$proposal = $auto_approved;
		}

		return $proposal;
	}

	/**
	 * Builds policy audit metadata.
	 *
	 * @param array<string,mixed> $proposal Proposal row.
	 * @param array<string,mixed> $policy Policy decision.
	 * @param bool                $auto_approval_applied Whether status changed automatically.
	 * @return array<string,mixed>
	 */
	private function policy_audit_metadata( array $proposal, array $policy, bool $auto_approval_applied ): array {
		return array(
			'ability_id'             => (string) ( $proposal['ability_id'] ?? '' ),
			'status'                 => (string) ( $proposal['status'] ?? '' ),
			'policy_decision'        => (string) ( $policy['policy_decision'] ?? Approval_Policy_Evaluator::DECISION_MANUAL_REQUIRED ),
			'policy_profile'         => (string) ( $policy['policy_profile'] ?? Approval_Policy_Evaluator::PROFILE_MANUAL ),
			'policy_version'         => (string) ( $policy['policy_version'] ?? Approval_Policy_Evaluator::VERSION ),
			'policy_mode'            => sanitize_key( (string) ( $policy['policy_mode'] ?? Approval_Policy_Evaluator::MODE_MANUAL ) ),
			'policy_reasons'         => array_values( array_map( 'sanitize_key', (array) ( $policy['policy_reasons'] ?? array() ) ) ),
			'auto_approval_quota'    => $this->auto_approval_quota_audit_metadata( is_array( $policy['auto_approval_quota'] ?? null ) ? $policy['auto_approval_quota'] : array() ),
			'auto_approval_applied'  => $auto_approval_applied,
			'commit_execution'       => false,
		);
	}

	/**
	 * Applies a successful auto-approval policy decision after creation audit.
	 *
	 * @param array<string,mixed> $proposal Created proposal.
	 * @param array<string,mixed> $policy Policy decision.
	 * @return array<string,mixed>|WP_Error|null
	 */
	private function maybe_auto_approve_created_proposal( array $proposal, array $policy ) {
		if ( Approval_Policy_Evaluator::DECISION_AUTO_APPROVED !== (string) ( $policy['policy_decision'] ?? '' ) ) {
			return null;
		}

		$proposal_id = sanitize_text_field( (string) ( $proposal['proposal_id'] ?? '' ) );
		if ( '' === $proposal_id ) {
			return $this->transition_failed_error();
		}

		if ( ! $this->policy_evaluator->consume_auto_approval_quota( $policy ) ) {
			$this->proposals->delete_by_proposal_id( $proposal_id );
			return new WP_Error(
				'magick_ai_core_auto_approval_quota_failed',
				__( 'Auto approval quota could not be consumed.', 'magick-ai-core' ),
				array( 'status' => 500 )
			);
		}

		$approved = $this->proposals->update_status( $proposal_id, Proposal_Repository::STATUS_APPROVED );
		if ( null === $approved ) {
			$this->proposals->delete_by_proposal_id( $proposal_id );
			return $this->transition_failed_error();
		}

		$event_id = $this->audit->record(
			'proposal.auto_approved',
			$this->policy_audit_metadata( $approved, $policy, true ),
			$proposal_id
		);

		if ( '' === $event_id ) {
			$this->proposals->update_status( $proposal_id, Proposal_Repository::STATUS_PENDING );
			return $this->audit_failed_error( 'magick_ai_core_auto_approval_audit_failed' );
		}

		return $approved;
	}

	/**
	 * Builds safe auto-approval quota audit metadata.
	 *
	 * @param array<string,mixed> $quota Quota metadata.
	 * @return array<string,mixed>
	 */
	private function auto_approval_quota_audit_metadata( array $quota ): array {
		if ( empty( $quota ) ) {
			return array();
		}

		return array(
			'subject'    => sanitize_text_field( (string) ( $quota['subject'] ?? '' ) ),
			'hour_limit' => absint( $quota['hour_limit'] ?? 0 ),
			'day_limit'  => absint( $quota['day_limit'] ?? 0 ),
		);
	}

	/**
	 * Approves a proposal without executing commit.
	 *
	 * @param string              $proposal_id Proposal id.
	 * @param array<string,mixed> $metadata Decision metadata.
	 * @return array<string,mixed>|WP_Error
	 */
	public function approve( string $proposal_id, array $metadata = array() ) {
		return $this->transition( $proposal_id, 'approved', 'proposal.approved', $metadata );
	}

	/**
	 * Rejects a proposal.
	 *
	 * @param string              $proposal_id Proposal id.
	 * @param array<string,mixed> $metadata Decision metadata.
	 * @return array<string,mixed>|WP_Error
	 */
	public function reject( string $proposal_id, array $metadata = array() ) {
		return $this->transition( $proposal_id, 'rejected', 'proposal.rejected', $metadata );
	}

	/**
	 * Archives an expired proposal.
	 *
	 * @param string              $proposal_id Proposal id.
	 * @param array<string,mixed> $metadata Archive metadata.
	 * @return array<string,mixed>|WP_Error
	 */
	public function archive( string $proposal_id, array $metadata = array() ) {
		$proposal_id = sanitize_text_field( $proposal_id );
		$existing    = $this->proposals->find( $proposal_id );

		if ( null === $existing ) {
			return $this->not_found_error();
		}

		if ( Proposal_Repository::STATUS_EXPIRED !== (string) ( $existing['status'] ?? '' ) ) {
			return new WP_Error(
				'magick_ai_core_proposal_archive_not_allowed',
				__( 'Only expired proposals can be archived.', 'magick-ai-core' ),
				array( 'status' => 409 )
			);
		}

		$proposal = $this->proposals->update_status( $proposal_id, Proposal_Repository::STATUS_ARCHIVED );
		if ( null === $proposal ) {
			return $this->transition_failed_error();
		}

		$event_id = $this->audit->record(
			'proposal.archived',
			array_merge(
				array(
					'ability_id'       => $proposal['ability_id'],
					'status'           => $proposal['status'],
					'previous_status'  => Proposal_Repository::STATUS_EXPIRED,
					'archive_terminal' => true,
				),
				$metadata
			),
			$proposal_id
		);

		if ( '' === $event_id ) {
			$this->proposals->update_status( $proposal_id, Proposal_Repository::STATUS_EXPIRED );
			return $this->audit_failed_error( 'magick_ai_core_proposal_archive_audit_failed' );
		}

		return $proposal;
	}

	/**
	 * Reopens an expired or archived proposal into pending review.
	 *
	 * @param string              $proposal_id Proposal id.
	 * @param array<string,mixed> $metadata Reopen metadata.
	 * @return array<string,mixed>|WP_Error
	 */
	public function reopen( string $proposal_id, array $metadata = array() ) {
		$proposal_id = sanitize_text_field( $proposal_id );
		$existing    = $this->proposals->find( $proposal_id );

		if ( null === $existing ) {
			return $this->not_found_error();
		}

		$previous_status = (string) ( $existing['status'] ?? '' );
		if ( ! in_array( $previous_status, array( Proposal_Repository::STATUS_EXPIRED, Proposal_Repository::STATUS_ARCHIVED ), true ) ) {
			return new WP_Error(
				'magick_ai_core_proposal_reopen_not_allowed',
				__( 'Only expired or archived proposals can be reopened.', 'magick-ai-core' ),
				array( 'status' => 409 )
			);
		}

		$proposal = $this->proposals->reopen( $proposal_id );
		if ( null === $proposal ) {
			return $this->transition_failed_error();
		}

		$event_id = $this->audit->record(
			'proposal.reopened',
			array_merge(
				array(
					'ability_id'      => $proposal['ability_id'],
					'status'          => $proposal['status'],
					'previous_status' => $previous_status,
				),
				$metadata
			),
			$proposal_id
		);

		if ( '' === $event_id ) {
			$this->proposals->update_status( $proposal_id, $previous_status );
			return $this->audit_failed_error( 'magick_ai_core_proposal_reopen_audit_failed' );
		}

		return $proposal;
	}

	/**
	 * Expires stale pending proposals.
	 *
	 * @param int $limit Maximum proposals to expire.
	 * @return int Expired proposal count.
	 */
	public function expire_stale_pending( int $limit = 100 ): int {
		$stale = $this->proposals->list_stale_pending( self::PENDING_TTL_SECONDS, $limit );
		$count = 0;

		foreach ( $stale as $proposal ) {
			if ( $this->expire_one( $proposal, 'ttl_elapsed' ) ) {
				++$count;
			}
		}

		return $count;
	}

	/**
	 * Expires stale pending proposals once before create guardrail checks.
	 *
	 * @return void
	 */
	private function expire_stale_pending_before_create(): void {
		if ( $this->stale_expired_for_create ) {
			return;
		}

		$this->stale_expired_for_create = true;
		$this->expire_stale_pending( 25 );
	}

	/**
	 * Builds proposal creation guardrail metadata.
	 *
	 * @param string              $ability_id Ability id.
	 * @param array<string,mixed> $input Proposal input.
	 * @return array<string,mixed>
	 */
	private function proposal_create_guardrail( string $ability_id, array $input, array $capability ): array {
		$subject = 'user';
		$limit   = self::PENDING_QUOTA_PER_USER;
		$key     = 'user:' . max( 0, get_current_user_id() );

		if ( Request_Context::is_app() ) {
			$auth   = Request_Context::audit_metadata();
			$app_id = sanitize_text_field( (string) ( $auth['app_id'] ?? '' ) );
			if ( '' !== $app_id ) {
				$subject = 'app';
				$limit   = self::PENDING_QUOTA_PER_APP;
				$key     = 'app:' . $app_id;
			}
		}

		$input_hash  = $this->stable_payload_hash( $input );
		$dedupe_hash = $this->stable_payload_hash(
			array(
				'ability_id' => $ability_id,
				'quota_key'  => $key,
				'input_hash' => $input_hash,
			)
		);

		return array(
			'pending_quota_key'     => $key,
			'pending_quota_subject' => $subject,
			'pending_quota_limit'   => $limit,
			'input_hash'            => $input_hash,
			'dedupe_hash'           => $dedupe_hash,
			'ability_contract_hash' => $this->ability_contract_hash( $capability ),
			'ability_contract'      => $this->ability_contract_fingerprint( $capability ),
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
	 * Finds an equivalent pending proposal for the same caller.
	 *
	 * @param array<int,array<string,mixed>> $pending Pending proposals.
	 * @param string                         $ability_id Ability id.
	 * @param string                         $input_hash Input hash.
	 * @param string                         $quota_key Quota key.
	 * @return array<string,mixed>|null
	 */
	private function find_duplicate_pending_proposal( array $pending, string $ability_id, string $input_hash, string $quota_key ): ?array {
		foreach ( $pending as $proposal ) {
			if ( ! $this->proposal_matches_quota_key( $proposal, $quota_key ) ) {
				continue;
			}
			if ( $ability_id !== (string) ( $proposal['ability_id'] ?? '' ) ) {
				continue;
			}
			if ( $input_hash !== $this->proposal_input_hash( $proposal ) ) {
				continue;
			}

			return $proposal;
		}

		return null;
	}

	/**
	 * Counts pending proposals for a caller quota bucket.
	 *
	 * @param array<int,array<string,mixed>> $pending Pending proposals.
	 * @param string                         $quota_key Quota key.
	 * @return int
	 */
	private function count_pending_for_quota( array $pending, string $quota_key ): int {
		$count = 0;
		foreach ( $pending as $proposal ) {
			if ( $this->proposal_matches_quota_key( $proposal, $quota_key ) ) {
				++$count;
			}
		}

		return $count;
	}

	/**
	 * Returns whether a proposal belongs to a quota key.
	 *
	 * @param array<string,mixed> $proposal Proposal row.
	 * @param string              $quota_key Quota key.
	 * @return bool
	 */
	private function proposal_matches_quota_key( array $proposal, string $quota_key ): bool {
		$caller     = is_array( $proposal['caller'] ?? null ) ? $proposal['caller'] : array();
		$guardrails = is_array( $caller['core_guardrails'] ?? null ) ? $caller['core_guardrails'] : array();
		if ( $quota_key === (string) ( $guardrails['pending_quota_key'] ?? '' ) ) {
			return true;
		}

		if ( 0 === strpos( $quota_key, 'app:' ) ) {
			$app_id = substr( $quota_key, 4 );
			$auth   = is_array( $caller['auth'] ?? null ) ? $caller['auth'] : array();
			return '' !== $app_id && $app_id === (string) ( $auth['app_id'] ?? '' );
		}

		if ( 0 === strpos( $quota_key, 'user:' ) ) {
			return absint( substr( $quota_key, 5 ) ) === (int) ( $proposal['created_by'] ?? 0 );
		}

		return false;
	}

	/**
	 * Returns the stored or computed input hash for a proposal.
	 *
	 * @param array<string,mixed> $proposal Proposal row.
	 * @return string
	 */
	private function proposal_input_hash( array $proposal ): string {
		$caller     = is_array( $proposal['caller'] ?? null ) ? $proposal['caller'] : array();
		$guardrails = is_array( $caller['core_guardrails'] ?? null ) ? $caller['core_guardrails'] : array();
		$stored     = sanitize_text_field( (string) ( $guardrails['input_hash'] ?? '' ) );

		return '' !== $stored ? $stored : $this->stable_payload_hash( $proposal['input'] ?? array() );
	}

	/**
	 * Returns a stable hash for a structured payload.
	 *
	 * @param mixed $payload Payload.
	 * @return string
	 */
	private function stable_payload_hash( $payload ): string {
		$json = wp_json_encode( $this->normalize_payload_for_hash( $this->sanitize_payload_for_hash( $payload ) ) );

		return hash( 'sha256', is_string( $json ) ? $json : '' );
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

	/**
	 * Returns whether a proposal is pending and past the TTL.
	 *
	 * @param array<string,mixed> $proposal Proposal row.
	 * @return bool
	 */
	public function is_stale_pending( array $proposal ): bool {
		if ( Proposal_Repository::STATUS_PENDING !== (string) ( $proposal['status'] ?? '' ) ) {
			return false;
		}

		$created_at = strtotime( (string) ( $proposal['created_at'] ?? '' ) );
		return false !== $created_at && $created_at < ( time() - self::PENDING_TTL_SECONDS );
	}

	/**
	 * Returns the pending proposal TTL in seconds.
	 *
	 * @return int
	 */
	public function pending_ttl_seconds(): int {
		return self::PENDING_TTL_SECONDS;
	}

	/**
	 * Records proposal list access.
	 *
	 * @param int $count Returned row count.
	 * @return void
	 */
	public function record_listed( int $count ): void {
		$this->audit->record(
			'proposal.listed',
			array(
				'count' => max( 0, $count ),
			)
		);
	}

	/**
	 * Records proposal detail access.
	 *
	 * @param array<string,mixed> $proposal Proposal row.
	 * @return void
	 */
	public function record_viewed( array $proposal ): void {
		$this->audit->record(
			'proposal.viewed',
			array(
				'ability_id' => (string) ( $proposal['ability_id'] ?? '' ),
				'status'     => (string) ( $proposal['status'] ?? '' ),
			),
			(string) ( $proposal['proposal_id'] ?? '' )
		);
	}

	/**
	 * Returns proposal audit timeline.
	 *
	 * @param string $proposal_id Proposal id.
	 * @return array<int,array<string,mixed>>
	 */
	public function audit_timeline( string $proposal_id ): array {
		return $this->audit->list_filtered(
			array(
				'proposal_id' => sanitize_text_field( $proposal_id ),
				'limit'       => 50,
				'order'       => 'asc',
			)
		);
	}

	/**
	 * Returns the shared not-found error.
	 *
	 * @return WP_Error
	 */
	public function not_found_error(): WP_Error {
		return new WP_Error(
			'magick_ai_core_proposal_not_found',
			__( 'Proposal was not found.', 'magick-ai-core' ),
			array( 'status' => 404 )
		);
	}

	/**
	 * Transitions a proposal status and records audit.
	 *
	 * @param string              $proposal_id Proposal id.
	 * @param string              $status New status.
	 * @param string              $event_name Audit event.
	 * @param array<string,mixed> $metadata Decision metadata.
	 * @return array<string,mixed>|WP_Error
	 */
	private function transition( string $proposal_id, string $status, string $event_name, array $metadata ) {
		$proposal_id = sanitize_text_field( $proposal_id );
		$existing    = $this->proposals->find( $proposal_id );

		if ( null === $existing ) {
			return $this->not_found_error();
		}

		if ( $this->is_stale_pending( $existing ) ) {
			$this->expire_one( $existing, 'decision_attempt_after_ttl' );
			return new WP_Error(
				'magick_ai_core_proposal_expired',
				__( 'Proposal expired before a decision was made.', 'magick-ai-core' ),
				array( 'status' => 409 )
			);
		}

		if ( 'pending' !== (string) ( $existing['status'] ?? '' ) ) {
			return new WP_Error(
				'magick_ai_core_proposal_already_decided',
				__( 'Only pending proposals can be approved or rejected.', 'magick-ai-core' ),
				array( 'status' => 409 )
			);
		}

		$proposal = $this->proposals->update_status( $proposal_id, $status );
		if ( null === $proposal ) {
			return $this->transition_failed_error();
		}

		$event_id = $this->audit->record(
			$event_name,
			array_merge(
				array(
					'ability_id' => $proposal['ability_id'],
					'status'     => $proposal['status'],
				),
				$metadata
			),
			$proposal_id
		);

		if ( '' === $event_id ) {
			$this->proposals->update_status( $proposal_id, (string) $existing['status'] );
			return $this->audit_failed_error( 'magick_ai_core_proposal_decision_audit_failed' );
		}

		return $proposal;
	}

	/**
	 * Expires one pending proposal and records audit.
	 *
	 * @param array<string,mixed> $proposal Existing proposal row.
	 * @param string              $reason Expiration reason.
	 * @return bool Whether expiration succeeded.
	 */
	private function expire_one( array $proposal, string $reason ): bool {
		$proposal_id = sanitize_text_field( (string) ( $proposal['proposal_id'] ?? '' ) );
		if ( '' === $proposal_id || Proposal_Repository::STATUS_PENDING !== (string) ( $proposal['status'] ?? '' ) ) {
			return false;
		}

		$expired = $this->proposals->update_status( $proposal_id, Proposal_Repository::STATUS_EXPIRED );
		if ( null === $expired ) {
			return false;
		}

		$event_id = $this->audit->record(
			'proposal.expired',
			array(
				'ability_id'             => (string) $expired['ability_id'],
				'status'                 => (string) $expired['status'],
				'previous_status'        => Proposal_Repository::STATUS_PENDING,
				'expired_after_seconds'  => self::PENDING_TTL_SECONDS,
				'expiration_reason'      => sanitize_key( $reason ),
			),
			$proposal_id
		);

		if ( '' === $event_id ) {
			$this->proposals->update_status( $proposal_id, Proposal_Repository::STATUS_PENDING );
			return false;
		}

		return true;
	}

	/**
	 * Returns transition failed error.
	 *
	 * @return WP_Error
	 */
	private function transition_failed_error(): WP_Error {
		return new WP_Error(
			'magick_ai_core_proposal_transition_failed',
			__( 'Proposal status could not be updated.', 'magick-ai-core' ),
			array( 'status' => 500 )
		);
	}

	/**
	 * Returns audit persistence error.
	 *
	 * @param string $code Stable error code.
	 * @return WP_Error
	 */
	private function audit_failed_error( string $code ): WP_Error {
		return new WP_Error(
			$code,
			__( 'Proposal lifecycle could not be audited.', 'magick-ai-core' ),
			array( 'status' => 500 )
		);
	}
}
