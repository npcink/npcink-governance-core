<?php
/**
 * Sensitive read request service.
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
 * Coordinates sensitive read request lifecycle and grants.
 */
final class Read_Request_Service {
	const POLICY_VERSION = 'core-read-authorization-v1';
	const DEFAULT_TTL_SECONDS = 3600;
	const MAX_TTL_SECONDS = 86400;
	const CORE_MAX_ROWS = 1000;
	const CORE_MAX_TAIL_LINES = 500;

	/**
	 * Read request repository.
	 *
	 * @var Read_Request_Repository
	 */
	private $requests;

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
	 * @param Read_Request_Repository $requests Request repository.
	 * @param Ability_Registry_Adapter $abilities Ability adapter.
	 * @param Audit_Log_Repository    $audit Audit repository.
	 */
	public function __construct( Read_Request_Repository $requests, Ability_Registry_Adapter $abilities, Audit_Log_Repository $audit ) {
		$this->requests  = $requests;
		$this->abilities = $abilities;
		$this->audit     = $audit;
	}

	/**
	 * Creates a sensitive read request.
	 *
	 * @param array<string,mixed> $payload Payload.
	 * @return array<string,mixed>|WP_Error
	 */
	public function create( array $payload ) {
		$ability_id = sanitize_text_field( (string) ( $payload['ability_id'] ?? '' ) );
		if ( '' === $ability_id || false === strpos( $ability_id, '/' ) ) {
			return new WP_Error(
				'npcink_governance_core_invalid_read_request_ability_id',
				__( 'A namespaced read ability_id is required.', 'npcink-governance-core' ),
				array( 'status' => 400 )
			);
		}

		$capability = $this->abilities->find( $ability_id );
		if ( null === $capability ) {
			return new WP_Error(
				'npcink_governance_core_read_ability_not_available',
				__( 'Sensitive read target ability is not available.', 'npcink-governance-core' ),
				array( 'status' => 404 )
			);
		}

		if ( ! $this->capability_requires_read_authorization( $capability ) ) {
			return new WP_Error(
				'npcink_governance_core_read_authorization_not_required',
				__( 'This ability does not require Core sensitive read authorization.', 'npcink-governance-core' ),
				array( 'status' => 409 )
			);
		}

		$input_hash = $this->input_hash_from_payload( $ability_id, $payload );
		if ( '' === $input_hash ) {
			return new WP_Error(
				'npcink_governance_core_read_request_input_hash_required',
				__( 'A read request must bind to an input or input_hash.', 'npcink-governance-core' ),
				array( 'status' => 400 )
			);
		}

		$data_classes = $this->sanitize_string_list( is_array( $payload['data_classes'] ?? null ) ? (array) $payload['data_classes'] : array() );
		if ( empty( $data_classes ) ) {
			return new WP_Error(
				'npcink_governance_core_read_request_data_classes_required',
				__( 'Sensitive read requests must declare at least one data class for review.', 'npcink-governance-core' ),
				array( 'status' => 400 )
			);
		}

		$purpose = sanitize_textarea_field( (string) ( $payload['purpose'] ?? '' ) );
		if ( '' === $purpose ) {
			return new WP_Error(
				'npcink_governance_core_read_request_purpose_required',
				__( 'Sensitive read requests must include a review purpose.', 'npcink-governance-core' ),
				array( 'status' => 400 )
			);
		}

		$redaction_level = $this->redaction_level( (string) ( $payload['redaction_level'] ?? '' ) );
		if ( is_wp_error( $redaction_level ) ) {
			return $redaction_level;
		}

		$caller = is_array( $payload['caller'] ?? null ) ? $payload['caller'] : array();
		if ( Request_Context::is_app() ) {
			$caller['auth'] = Request_Context::audit_metadata();
		}

		$bounds     = $this->bounded_read_limits( $capability, $this->read_bounds_payload( $payload ) );
		$expires_at = $this->bounded_expires_at( (string) ( $payload['expires_at'] ?? '' ) );
		$request    = $this->requests->create(
			array(
				'ability_id'               => $ability_id,
				'input_hash'               => $input_hash,
				'requested_input_summary'  => $payload['requested_input_summary'] ?? '',
				'sensitivity'              => $this->sensitivity_for_request( $capability, (string) ( $payload['sensitivity'] ?? '' ) ),
				'data_classes'             => $data_classes,
				'redaction_level'          => $redaction_level,
				'purpose'                  => $purpose,
				'caller'                   => $caller,
				'bounds'                   => $bounds,
				'correlation_id'           => $this->new_correlation_id(),
				'expires_at'               => $expires_at,
			)
		);

		if ( is_wp_error( $request ) ) {
			return $request;
		}

		$event_id = $this->audit->record(
			'read_request.created',
			$this->audit_metadata( $request, array( 'read_authorization_required' => true ) ),
			(string) $request['request_id']
		);

		if ( '' === $event_id ) {
			$this->requests->delete_by_request_id( (string) $request['request_id'] );
			return $this->audit_failed_error( 'npcink_governance_core_read_request_audit_failed' );
		}

		return $request;
	}

	/**
	 * Approves a read request.
	 *
	 * @param string              $request_id Request id.
	 * @param array<string,mixed> $metadata Approval metadata.
	 * @return array<string,mixed>|WP_Error
	 */
	public function approve( string $request_id, array $metadata = array() ) {
		$request = $this->transitionable_request( $request_id );
		if ( is_wp_error( $request ) ) {
			return $request;
		}

		$capability = $this->abilities->find( (string) $request['ability_id'] );
		if ( null === $capability || ! $this->capability_requires_read_authorization( $capability ) ) {
			return new WP_Error(
				'npcink_governance_core_read_ability_not_available',
				__( 'Sensitive read target ability is not available.', 'npcink-governance-core' ),
				array( 'status' => 404 )
			);
		}

		$redaction_level = $this->redaction_level( (string) ( $metadata['redaction_level'] ?? $request['redaction_level'] ?? '' ) );
		if ( is_wp_error( $redaction_level ) ) {
			return $redaction_level;
		}

		$bounds     = $this->bounded_read_limits(
			$capability,
			$this->read_bounds_payload(
				$metadata,
				is_array( $request['bounds'] ?? null ) ? (array) $request['bounds'] : array()
			)
		);
		$expires_at = $this->bounded_expires_at( (string) ( $metadata['expires_at'] ?? $request['expires_at'] ?? '' ) );
		$updated    = $this->requests->update_approval_fields(
			(string) $request['request_id'],
			array(
				'bounds'          => $bounds,
				'redaction_level' => $redaction_level,
				'expires_at'      => $expires_at,
			)
		);
		if ( null === $updated ) {
			return $this->transition_failed_error();
		}

		$approved = $this->requests->update_status_when( (string) $request['request_id'], Read_Request_Repository::STATUS_PENDING, Read_Request_Repository::STATUS_APPROVED );
		if ( null === $approved ) {
			return $this->transition_failed_error();
		}

		$event_id = $this->audit->record(
			'read_request.approved',
			$this->audit_metadata( $approved, array( 'note' => (string) ( $metadata['note'] ?? '' ) ) ),
			(string) $approved['request_id']
		);

		if ( '' === $event_id ) {
			$this->requests->update_status_when( (string) $request['request_id'], Read_Request_Repository::STATUS_APPROVED, Read_Request_Repository::STATUS_PENDING );
			return $this->audit_failed_error( 'npcink_governance_core_read_request_decision_audit_failed' );
		}

		return $approved;
	}

	/**
	 * Rejects a read request.
	 *
	 * @param string              $request_id Request id.
	 * @param array<string,mixed> $metadata Rejection metadata.
	 * @return array<string,mixed>|WP_Error
	 */
	public function reject( string $request_id, array $metadata = array() ) {
		$request = $this->transitionable_request( $request_id );
		if ( is_wp_error( $request ) ) {
			return $request;
		}

		$rejected = $this->requests->update_status_when( (string) $request['request_id'], Read_Request_Repository::STATUS_PENDING, Read_Request_Repository::STATUS_REJECTED );
		if ( null === $rejected ) {
			return $this->transition_failed_error();
		}

		$event_id = $this->audit->record(
			'read_request.rejected',
			$this->audit_metadata( $rejected, array( 'note' => (string) ( $metadata['note'] ?? '' ) ) ),
			(string) $rejected['request_id']
		);

		if ( '' === $event_id ) {
			$this->requests->update_status_when( (string) $request['request_id'], Read_Request_Repository::STATUS_REJECTED, Read_Request_Repository::STATUS_PENDING );
			return $this->audit_failed_error( 'npcink_governance_core_read_request_decision_audit_failed' );
		}

		return $rejected;
	}

	/**
	 * Returns bounded read authorization context.
	 *
	 * @param string              $request_id Request id.
	 * @param array<string,mixed> $payload Payload.
	 * @return array<string,mixed>|WP_Error
	 */
	public function preflight( string $request_id, array $payload = array() ) {
		$request_id = sanitize_text_field( $request_id );
		$request    = $this->requests->find( $request_id );
		if ( null === $request ) {
			return $this->not_found_error();
		}

		if ( Read_Request_Repository::STATUS_APPROVED !== (string) ( $request['status'] ?? '' ) ) {
			return $this->preflight_error(
				'npcink_governance_core_read_request_not_approved',
				__( 'Only approved sensitive read requests can grant read authorization.', 'npcink-governance-core' ),
				409,
				$request,
				array( 'status' => (string) ( $request['status'] ?? '' ) )
			);
		}

		if ( $this->is_expired( $request ) ) {
			$expired = $this->requests->update_status_when( $request_id, Read_Request_Repository::STATUS_APPROVED, Read_Request_Repository::STATUS_EXPIRED );
			$this->audit->record(
				'read_request.expired',
				$this->audit_metadata( is_array( $expired ) ? $expired : $request, array( 'expiration_reason' => 'grant_attempt_after_expiry' ) ),
				$request_id
			);
			return $this->preflight_error(
				'npcink_governance_core_read_request_expired',
				__( 'Sensitive read request grant has expired.', 'npcink-governance-core' ),
				409,
				is_array( $expired ) ? $expired : $request
			);
		}

		$ability_id = sanitize_text_field( (string) ( $payload['ability_id'] ?? $request['ability_id'] ?? '' ) );
		if ( $ability_id !== (string) ( $request['ability_id'] ?? '' ) ) {
			return $this->preflight_error(
				'npcink_governance_core_read_request_ability_mismatch',
				__( 'Sensitive read grant does not match the approved ability.', 'npcink-governance-core' ),
				409,
				$request,
				array( 'requested_ability_id' => $ability_id )
			);
		}

		if ( $this->requires_raw_input_preflight( $request ) && ! $this->preflight_payload_includes_input( $payload ) ) {
			return $this->preflight_error(
				'npcink_governance_core_read_request_input_required_for_sensitive_preflight',
				__( 'Sensitive read preflight must include the raw input so Core can recompute the approved input hash.', 'npcink-governance-core' ),
				400,
				$request,
				array( 'approved_input_hash' => (string) ( $request['input_hash'] ?? '' ) )
			);
		}

		$input_hash = $this->input_hash_from_payload( $ability_id, $payload );
		if ( '' === $input_hash || $input_hash !== (string) ( $request['input_hash'] ?? '' ) ) {
			return $this->preflight_error(
				'npcink_governance_core_read_request_input_mismatch',
				__( 'Sensitive read grant does not match the approved input hash.', 'npcink-governance-core' ),
				409,
				$request,
				array( 'approved_input_hash' => (string) ( $request['input_hash'] ?? '' ) )
			);
		}

		$capability = $this->abilities->find( $ability_id );
		if ( null === $capability || ! $this->capability_requires_read_authorization( $capability ) ) {
			return $this->preflight_error(
				'npcink_governance_core_read_ability_not_available',
				__( 'Sensitive read target ability is not available.', 'npcink-governance-core' ),
				409,
				$request
			);
		}

		$bounds = $this->bounded_read_limits( $capability, is_array( $request['bounds'] ?? null ) ? (array) $request['bounds'] : array() );
		$grant_request = $request;
		if ( ! empty( $bounds['one_time'] ) ) {
			$consumed = $this->requests->consume_approved_once( (string) $request['request_id'] );
			if ( null === $consumed ) {
				return $this->preflight_error(
					'npcink_governance_core_read_request_consume_failed',
					__( 'One-time sensitive read request could not be consumed before grant.', 'npcink-governance-core' ),
					409,
					$request
				);
			}

			$consumed_event_id = $this->audit->record(
				'read_request.consumed',
				$this->audit_metadata( $consumed, array( 'correlation_id' => (string) $request['correlation_id'] ) ),
				(string) $request['request_id']
			);
			if ( '' === $consumed_event_id ) {
				return $this->audit_failed_error( 'npcink_governance_core_read_request_consume_audit_failed' );
			}

			$grant_request = $consumed;
		}

		// Emits signed_client_fingerprint and client_key_fingerprint when a trusted Adapter forwarded one.
		$client_binding = Request_Context::signed_client_context();
		$context = array(
			'request_id'               => (string) $request['request_id'],
			'ability_id'               => $ability_id,
			'approved_input_hash'      => (string) $request['input_hash'],
			'correlation_id'           => (string) $request['correlation_id'],
			'policy_version'           => self::POLICY_VERSION,
			'sensitivity'              => (string) $request['sensitivity'],
			'data_classes'             => (array) ( $request['data_classes'] ?? array() ),
			'redaction_level'          => (string) $request['redaction_level'],
			'expires_at'               => (string) $request['expires_at'],
			'bounds'                   => $bounds,
			'read_authorization_granted' => true,
			'core_authorization_truth' => 'npcink_governance_core',
			'commit_execution'         => false,
			'write_execution'          => false,
		) + $client_binding + $this->site_binding_context();

		$event_id = $this->audit->record(
			'read_request.preflighted',
			$this->audit_metadata(
				$grant_request,
				array(
					'correlation_id'             => (string) $request['correlation_id'],
					'approved_input_hash'        => (string) $request['input_hash'],
					'read_authorization_granted' => true,
					'commit_execution'           => false,
					'write_execution'            => false,
				) + $client_binding
			),
			(string) $request['request_id']
		);

		if ( '' === $event_id ) {
			return $this->audit_failed_error( 'npcink_governance_core_read_preflight_audit_failed' );
		}

		$response = array(
			'request'                    => $grant_request,
			'read_authorization_context' => $context,
			'correlation_id'             => (string) $request['correlation_id'],
			'commit_execution'           => false,
			'write_execution'            => false,
		);

		return $response;
	}

	/**
	 * Records list access.
	 *
	 * @param int $count Count.
	 * @return void
	 */
	public function record_listed( int $count ): void {
		$this->audit->record(
			'read_request.listed',
			array(
				'count' => max( 0, $count ),
			)
		);
	}

	/**
	 * Records detail access.
	 *
	 * @param array<string,mixed> $request Request row.
	 * @return void
	 */
	public function record_viewed( array $request ): void {
		$this->audit->record(
			'read_request.viewed',
			$this->audit_metadata( $request ),
			(string) ( $request['request_id'] ?? '' )
		);
	}

	/**
	 * Returns audit timeline.
	 *
	 * @param string $request_id Request id.
	 * @return array<int,array<string,mixed>>
	 */
	public function audit_timeline( string $request_id ): array {
		return $this->audit->list_filtered(
			array(
				'proposal_id' => sanitize_text_field( $request_id ),
				'limit'       => 50,
				'order'       => 'asc',
			)
		);
	}

	/**
	 * Returns not-found error.
	 *
	 * @return WP_Error
	 */
	public function not_found_error(): WP_Error {
		return new WP_Error(
			'npcink_governance_core_read_request_not_found',
			__( 'Sensitive read request was not found.', 'npcink-governance-core' ),
			array( 'status' => 404 )
		);
	}

	/**
	 * Returns request eligible for decision.
	 *
	 * @param string $request_id Request id.
	 * @return array<string,mixed>|WP_Error
	 */
	private function transitionable_request( string $request_id ) {
		$request = $this->requests->find( sanitize_text_field( $request_id ) );
		if ( null === $request ) {
			return $this->not_found_error();
		}

		if ( $this->is_expired( $request ) ) {
			$expired = $this->requests->update_status_when( (string) $request['request_id'], Read_Request_Repository::STATUS_PENDING, Read_Request_Repository::STATUS_EXPIRED );
			$this->audit->record(
				'read_request.expired',
				$this->audit_metadata( is_array( $expired ) ? $expired : $request, array( 'expiration_reason' => 'decision_attempt_after_expiry' ) ),
				(string) $request['request_id']
			);
			return new WP_Error(
				'npcink_governance_core_read_request_expired',
				__( 'Sensitive read request expired before a decision was made.', 'npcink-governance-core' ),
				array( 'status' => 409 )
			);
		}

		if ( Read_Request_Repository::STATUS_PENDING !== (string) ( $request['status'] ?? '' ) ) {
			return new WP_Error(
				'npcink_governance_core_read_request_already_decided',
				__( 'Only pending sensitive read requests can be approved or rejected.', 'npcink-governance-core' ),
				array( 'status' => 409 )
			);
		}

		return $request;
	}

	/**
	 * Returns whether capability requires Core read authorization.
	 *
	 * @param array<string,mixed> $capability Capability row.
	 * @return bool
	 */
	private function capability_requires_read_authorization( array $capability ): bool {
		$read_authorization = is_array( $capability['read_authorization'] ?? null ) ? $capability['read_authorization'] : array();
		return ! empty( $capability['read_authorization_required'] )
			|| ! empty( $capability['requires_read_authorization'] )
			|| ! empty( $read_authorization['required'] )
			|| 'core_read_authorization_required' === (string) ( $capability['read_policy'] ?? '' )
			|| 'core_read_request' === (string) ( $capability['authorization_mode'] ?? '' );
	}

	/**
	 * Returns whether read-preflight must recompute the hash from raw input.
	 *
	 * @param array<string,mixed> $request Request row.
	 * @return bool
	 */
	private function requires_raw_input_preflight( array $request ): bool {
		return 'sensitive' === sanitize_key( (string) ( $request['sensitivity'] ?? '' ) );
	}

	/**
	 * Returns whether preflight payload included a raw input object.
	 *
	 * @param array<string,mixed> $payload Preflight payload.
	 * @return bool
	 */
	private function preflight_payload_includes_input( array $payload ): bool {
		if ( array_key_exists( '_input_provided', $payload ) ) {
			return ! empty( $payload['_input_provided'] ) && is_array( $payload['input'] ?? null );
		}

		return array_key_exists( 'input', $payload ) && is_array( $payload['input'] );
	}

	/**
	 * Returns request input hash from input or explicit hash.
	 *
	 * @param string              $ability_id Ability id.
	 * @param array<string,mixed> $payload Payload.
	 * @return string
	 */
	private function input_hash_from_payload( string $ability_id, array $payload ): string {
		$explicit_hash = sanitize_text_field( (string) ( $payload['input_hash'] ?? '' ) );
		if ( is_array( $payload['input'] ?? null ) && ( ! empty( $payload['input'] ) || '' === $explicit_hash ) ) {
			return $this->stable_input_hash( $ability_id, (array) $payload['input'] );
		}

		$input_hash = $explicit_hash;
		return 1 === preg_match( '/^[a-f0-9]{64}$/', $input_hash ) ? $input_hash : '';
	}

	/**
	 * Returns stable hash for read ability input.
	 *
	 * @param string              $ability_id Ability id.
	 * @param array<string,mixed> $input Input.
	 * @return string
	 */
	private function stable_input_hash( string $ability_id, array $input ): string {
		$json = wp_json_encode(
			$this->normalize_payload_for_hash(
				array(
					'ability_id' => sanitize_text_field( $ability_id ),
					'input'      => $this->sanitize_payload_for_hash( $input ),
				)
			)
		);

		return hash( 'sha256', is_string( $json ) ? $json : '' );
	}

	/**
	 * Returns bounded limits that cannot exceed provider or Core caps.
	 *
	 * @param array<string,mixed> $capability Capability row.
	 * @param array<string,mixed> $requested Requested bounds.
	 * @return array<string,mixed>
	 */
	private function bounded_read_limits( array $capability, array $requested ): array {
		$declared = is_array( $capability['read_authorization'] ?? null ) ? (array) $capability['read_authorization'] : array();
		$max_rows = $this->bounded_integer(
			absint( $requested['max_rows'] ?? 0 ),
			absint( $declared['max_rows'] ?? 0 ),
			self::CORE_MAX_ROWS
		);
		$tail_lines = $this->bounded_integer(
			absint( $requested['tail_lines'] ?? 0 ),
			absint( $declared['tail_lines'] ?? 0 ),
			self::CORE_MAX_TAIL_LINES
		);

		$declared_allowed = $this->sanitize_string_list( is_array( $declared['allowed_fields'] ?? null ) ? (array) $declared['allowed_fields'] : array() );
		$requested_allowed = $this->sanitize_string_list( is_array( $requested['allowed_fields'] ?? null ) ? (array) $requested['allowed_fields'] : array() );
		if ( ! empty( $declared_allowed ) ) {
			$requested_allowed = empty( $requested_allowed ) ? $declared_allowed : array_values( array_intersect( $requested_allowed, $declared_allowed ) );
		}

		$denied = array_values(
			array_unique(
				array_merge(
					$this->sanitize_string_list( is_array( $declared['denied_fields'] ?? null ) ? (array) $declared['denied_fields'] : array() ),
					$this->sanitize_string_list( is_array( $requested['denied_fields'] ?? null ) ? (array) $requested['denied_fields'] : array() )
				)
			)
		);

		return array(
			'max_rows'       => $max_rows,
			'tail_lines'     => $tail_lines,
			'allowed_fields' => $requested_allowed,
			'denied_fields'  => $denied,
			'one_time'       => ! empty( $requested['one_time'] ),
		);
	}

	/**
	 * Extracts read bounds from top-level or nested REST payload fields.
	 *
	 * @param array<string,mixed> $payload Payload.
	 * @param array<string,mixed> $base Existing bounds.
	 * @return array<string,mixed>
	 */
	private function read_bounds_payload( array $payload, array $base = array() ): array {
		$bounds = $base;
		foreach ( array( $payload, is_array( $payload['bounds'] ?? null ) ? (array) $payload['bounds'] : array() ) as $candidate ) {
			if ( array_key_exists( 'max_rows', $candidate ) && absint( $candidate['max_rows'] ) > 0 ) {
				$bounds['max_rows'] = absint( $candidate['max_rows'] );
			}
			if ( array_key_exists( 'tail_lines', $candidate ) && absint( $candidate['tail_lines'] ) > 0 ) {
				$bounds['tail_lines'] = absint( $candidate['tail_lines'] );
			}
			if ( ! empty( $candidate['allowed_fields'] ) && is_array( $candidate['allowed_fields'] ) ) {
				$bounds['allowed_fields'] = (array) $candidate['allowed_fields'];
			}
			if ( ! empty( $candidate['denied_fields'] ) && is_array( $candidate['denied_fields'] ) ) {
				$bounds['denied_fields'] = (array) $candidate['denied_fields'];
			}
			if ( ! empty( $candidate['one_time'] ) ) {
				$bounds['one_time'] = true;
			}
		}

		return $bounds;
	}

	/**
	 * Bounds a numeric value.
	 *
	 * @param int $requested Requested.
	 * @param int $declared Provider declared max.
	 * @param int $core_max Core max.
	 * @return int
	 */
	private function bounded_integer( int $requested, int $declared, int $core_max ): int {
		$limit = $declared > 0 ? min( $declared, $core_max ) : $core_max;
		if ( $requested <= 0 ) {
			return $limit;
		}

		return min( $requested, $limit );
	}

	/**
	 * Returns bounded expiry timestamp.
	 *
	 * @param string $expires_at Requested expiry.
	 * @return string
	 */
	private function bounded_expires_at( string $expires_at ): string {
		$now       = time();
		$requested = '' !== trim( $expires_at ) ? strtotime( $expires_at ) : false;
		$default   = $now + self::DEFAULT_TTL_SECONDS;
		$max       = $now + self::MAX_TTL_SECONDS;
		$timestamp = false === $requested ? $default : (int) $requested;
		if ( $timestamp <= $now ) {
			$timestamp = $default;
		}

		return gmdate( 'Y-m-d H:i:s', min( $timestamp, $max ) );
	}

	/**
	 * Returns request sensitivity.
	 *
	 * @param array<string,mixed> $capability Capability row.
	 * @param string              $requested Requested sensitivity.
	 * @return string
	 */
	private function sensitivity_for_request( array $capability, string $requested ): string {
		$sensitivity = sanitize_key( '' !== $requested ? $requested : (string) ( $capability['sensitivity'] ?? 'sensitive' ) );
		return in_array( $sensitivity, array( 'internal', 'sensitive' ), true ) ? $sensitivity : 'sensitive';
	}

	/**
	 * Returns redaction level.
	 *
	 * @param string $value Raw value.
	 * @return string|WP_Error
	 */
	private function redaction_level( string $value ) {
		$value = sanitize_key( '' !== $value ? $value : 'strict' );
		if ( 'none' === $value ) {
			return new WP_Error(
				'npcink_governance_core_read_request_redaction_required',
				__( 'Sensitive read authorization cannot disable redaction.', 'npcink-governance-core' ),
				array( 'status' => 400 )
			);
		}

		return in_array( $value, array( 'standard', 'strict' ), true ) ? $value : 'strict';
	}

	/**
	 * Returns whether request expired.
	 *
	 * @param array<string,mixed> $request Request row.
	 * @return bool
	 */
	private function is_expired( array $request ): bool {
		$expires_at = strtotime( (string) ( $request['expires_at'] ?? '' ) );
		return false !== $expires_at && $expires_at <= time();
	}

	/**
	 * Builds audit metadata.
	 *
	 * @param array<string,mixed> $request Request row.
	 * @param array<string,mixed> $extra Extra metadata.
	 * @return array<string,mixed>
	 */
	private function audit_metadata( array $request, array $extra = array() ): array {
		return array_merge(
			array(
				'request_id'       => (string) ( $request['request_id'] ?? '' ),
				'ability_id'       => (string) ( $request['ability_id'] ?? '' ),
				'status'           => (string) ( $request['status'] ?? '' ),
				'input_hash'       => (string) ( $request['input_hash'] ?? '' ),
				'sensitivity'      => (string) ( $request['sensitivity'] ?? '' ),
				'data_classes'     => (array) ( $request['data_classes'] ?? array() ),
				'redaction_level'  => (string) ( $request['redaction_level'] ?? '' ),
				'expires_at'       => (string) ( $request['expires_at'] ?? '' ),
				'correlation_id'   => (string) ( $request['correlation_id'] ?? '' ),
				'policy_version'   => self::POLICY_VERSION,
				'commit_execution' => false,
				'write_execution'  => false,
			),
			$this->sanitize_audit_extra( $extra )
		);
	}

	/**
	 * Records a preflight failure and returns an error.
	 *
	 * @param string              $code Code.
	 * @param string              $message Message.
	 * @param int                 $status HTTP status.
	 * @param array<string,mixed> $request Request row.
	 * @param array<string,mixed> $metadata Metadata.
	 * @return WP_Error
	 */
	private function preflight_error( string $code, string $message, int $status, array $request, array $metadata = array() ): WP_Error {
		$this->audit->record(
			'read_request.preflight_failed',
			$this->audit_metadata(
				$request,
				array_merge(
					array(
						'error_code' => $code,
						'status_code' => $status,
					),
					$metadata
				)
			),
			(string) ( $request['request_id'] ?? '' )
		);

		return new WP_Error(
			$code,
			$message,
			array(
				'status'           => $status,
				'commit_execution' => false,
				'write_execution'  => false,
			)
		);
	}

	/**
	 * Returns a new correlation id.
	 *
	 * @return string
	 */
	private function new_correlation_id(): string {
		return function_exists( 'wp_generate_uuid4' ) ? wp_generate_uuid4() : uniqid( 'npcink_governance_core_read_corr_', true );
	}

	/**
	 * Returns the current WordPress site binding for Core-issued read grants.
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
	 * Sanitizes payload for hashing.
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
	 * Sanitizes string list.
	 *
	 * @param array<mixed> $values Values.
	 * @return array<int,string>
	 */
	private function sanitize_string_list( array $values ): array {
		$clean = array();
		foreach ( $values as $value ) {
			$value = sanitize_text_field( (string) $value );
			if ( '' !== $value ) {
				$clean[] = $value;
			}
		}

		return array_values( array_unique( $clean ) );
	}

	/**
	 * Sanitizes extra audit fields.
	 *
	 * @param array<string,mixed> $extra Extra metadata.
	 * @return array<string,mixed>
	 */
	private function sanitize_audit_extra( array $extra ): array {
		$clean = array();
		foreach ( $extra as $key => $value ) {
			$clean[ sanitize_key( (string) $key ) ] = is_array( $value ) ? array() : sanitize_textarea_field( (string) $value );
		}

		return $clean;
	}

	/**
	 * Returns transition failure error.
	 *
	 * @return WP_Error
	 */
	private function transition_failed_error(): WP_Error {
		return new WP_Error(
			'npcink_governance_core_read_request_transition_failed',
			__( 'Sensitive read request status could not be updated.', 'npcink-governance-core' ),
			array( 'status' => 500 )
		);
	}

	/**
	 * Returns audit failure error.
	 *
	 * @param string $code Code.
	 * @return WP_Error
	 */
	private function audit_failed_error( string $code ): WP_Error {
		return new WP_Error(
			$code,
			__( 'Sensitive read request lifecycle could not be audited.', 'npcink-governance-core' ),
			array( 'status' => 500 )
		);
	}
}
