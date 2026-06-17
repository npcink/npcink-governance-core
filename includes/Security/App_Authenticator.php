<?php
/**
 * App-key REST authenticator.
 *
 * @package NpcinkGovernanceCore
 */

namespace Npcink\GovernanceCore\Security;

use Npcink\GovernanceCore\Audit\Audit_Log_Repository;
use WP_Error;
use WP_REST_Request;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Authorizes REST requests through WordPress user auth or scoped app keys.
 */
final class App_Authenticator {
	/**
	 * App key repository.
	 *
	 * @var App_Key_Repository
	 */
	private $apps;

	/**
	 * Rate limiter.
	 *
	 * @var App_Rate_Limiter
	 */
	private $rate_limiter;

	/**
	 * Audit repository.
	 *
	 * @var Audit_Log_Repository
	 */
	private $audit;

	/**
	 * Constructor.
	 *
	 * @param App_Key_Repository $apps App key repository.
	 * @param App_Rate_Limiter   $rate_limiter Rate limiter.
	 * @param Audit_Log_Repository $audit Audit repository.
	 */
	public function __construct( App_Key_Repository $apps, App_Rate_Limiter $rate_limiter, Audit_Log_Repository $audit ) {
		$this->apps         = $apps;
		$this->rate_limiter = $rate_limiter;
		$this->audit        = $audit;
	}

	/**
	 * Checks admin-only access.
	 *
	 * @param WP_REST_Request|null $request Request.
	 * @return bool
	 */
	public function can_manage( ?WP_REST_Request $request = null ): bool {
		Request_Context::clear();
		return current_user_can( 'manage_options' );
	}

	/**
	 * Authorizes a capabilities read.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public function can_read_capabilities( WP_REST_Request $request ) {
		return $this->authorize( $request, 'capabilities:read', 'capabilities' );
	}

	/**
	 * Authorizes proposal creation.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public function can_create_proposals( WP_REST_Request $request ) {
		return $this->authorize( $request, 'proposals:create', 'proposals_create' );
	}

	/**
	 * Authorizes proposal reads.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public function can_read_proposals( WP_REST_Request $request ) {
		return $this->authorize( $request, 'proposals:read', 'proposals_read' );
	}

	/**
	 * Authorizes proposal approval.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public function can_approve_proposals( WP_REST_Request $request ) {
		return $this->authorize( $request, 'proposals:approve', 'proposals_approve' );
	}

	/**
	 * Authorizes proposal rejection.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public function can_reject_proposals( WP_REST_Request $request ) {
		return $this->authorize( $request, 'proposals:reject', 'proposals_reject' );
	}

	/**
	 * Authorizes commit preflight.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public function can_commit_preflight( WP_REST_Request $request ) {
		return $this->authorize( $request, 'commit:preflight', 'commit_preflight' );
	}

	/**
	 * Authorizes recording post-preflight execution results.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public function can_record_execution( WP_REST_Request $request ) {
		return $this->authorize( $request, 'commit:record_execution', 'commit_record_execution' );
	}

	/**
	 * Authorizes sensitive read request creation.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public function can_create_read_requests( WP_REST_Request $request ) {
		return $this->authorize( $request, 'read_requests:create', 'read_requests_create' );
	}

	/**
	 * Authorizes sensitive read request reads.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public function can_read_read_requests( WP_REST_Request $request ) {
		return $this->authorize( $request, 'read_requests:read', 'read_requests_read' );
	}

	/**
	 * Authorizes sensitive read request approval.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public function can_approve_read_requests( WP_REST_Request $request ) {
		return $this->authorize( $request, 'read_requests:approve', 'read_requests_approve' );
	}

	/**
	 * Authorizes sensitive read request rejection.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public function can_reject_read_requests( WP_REST_Request $request ) {
		return $this->authorize( $request, 'read_requests:reject', 'read_requests_reject' );
	}

	/**
	 * Authorizes sensitive read preflight.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public function can_preflight_read_requests( WP_REST_Request $request ) {
		return $this->authorize( $request, 'read_requests:preflight', 'read_requests_preflight' );
	}

	/**
	 * Authorizes audit reads.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public function can_read_audit( WP_REST_Request $request ) {
		return $this->authorize( $request, 'audit:read', 'audit' );
	}

	/**
	 * Authorizes one scoped request.
	 *
	 * @param WP_REST_Request $request Request.
	 * @param string          $scope Required scope.
	 * @param string          $route_family Route family for rate limiting.
	 * @return bool|WP_Error
	 */
	private function authorize( WP_REST_Request $request, string $scope, string $route_family ) {
		Request_Context::clear();

		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		$token = $this->token_from_request( $request );
		if ( '' === $token ) {
			return $this->error( 'npcink_governance_core_app_auth_missing', __( 'App authentication is required.', 'npcink-governance-core' ), 401 );
		}

		$parts = explode( '.', $token, 3 );
		if ( 3 !== count( $parts ) || App_Key_Repository::TOKEN_PREFIX !== $parts[0] ) {
			return $this->error( 'npcink_governance_core_app_auth_malformed', __( 'App authentication token is malformed.', 'npcink-governance-core' ), 400 );
		}

		$key_id = sanitize_text_field( $parts[1] );
		$secret = (string) $parts[2];
		$app    = $this->apps->find_by_key_id( $key_id );

		if ( null === $app || 'active' !== (string) ( $app['status'] ?? '' ) || ! $this->apps->verify_secret( $app, $secret ) ) {
			return $this->error( 'npcink_governance_core_app_auth_invalid', __( 'App authentication token is invalid.', 'npcink-governance-core' ), 401 );
		}
		if ( $this->apps->is_expired( $app ) ) {
			$this->set_context( $app, $scope, $route_family );
			Request_Context::mark_scope_decision( 'expired' );
			$this->audit->record(
				'app.scope_denied',
				array(
					'required_scope' => $scope,
					'route_family'   => sanitize_key( $route_family ),
					'denial_reason'  => 'app_key_expired',
				)
			);
			return $this->error( 'npcink_governance_core_app_auth_invalid', __( 'App authentication token is invalid.', 'npcink-governance-core' ), 401 );
		}

		if ( ! in_array( $scope, (array) ( $app['scopes'] ?? array() ), true ) ) {
			$this->set_context( $app, $scope, $route_family );
			Request_Context::mark_scope_decision( 'denied' );
			$this->audit->record(
				'app.scope_denied',
				array(
					'required_scope' => $scope,
					'route_family'   => sanitize_key( $route_family ),
				)
			);
			return $this->error( 'npcink_governance_core_app_scope_forbidden', __( 'App key does not have the required scope.', 'npcink-governance-core' ), 403 );
		}

		$this->set_context( $app, $scope, $route_family, $request );
		$rate = $this->rate_limiter->consume( $app, $route_family );
		if ( empty( $rate['allowed'] ) ) {
			Request_Context::mark_scope_decision( 'rate_limited' );
			$this->audit->record(
				'app.rate_limited',
				array(
					'route_family' => sanitize_key( $route_family ),
					'limit'        => (int) ( $rate['limit'] ?? 0 ),
					'reset_at'     => (string) ( $rate['reset_at'] ?? '' ),
				)
			);

			return new WP_Error(
				'npcink_governance_core_app_rate_limited',
				__( 'App key rate limit exceeded.', 'npcink-governance-core' ),
				array(
					'status'     => 429,
					'limit'      => (int) ( $rate['limit'] ?? 0 ),
					'reset_at'   => (string) ( $rate['reset_at'] ?? '' ),
					'route_family' => sanitize_key( $route_family ),
				)
			);
		}

		$this->apps->touch_last_used( $key_id, $this->request_ip_hash() );

		return true;
	}

	/**
	 * Returns a non-reversible hash of the request IP when available.
	 *
	 * @return string
	 */
	private function request_ip_hash(): string {
		$remote_addr = sanitize_text_field( (string) ( $_SERVER['REMOTE_ADDR'] ?? '' ) );
		if ( '' === $remote_addr ) {
			return '';
		}

		return hash( 'sha256', $remote_addr );
	}

	/**
	 * Sets request context from app row.
	 *
	 * @param array<string,mixed> $app App row.
	 * @param string              $scope Scope.
	 * @param string              $route_family Route family.
	 * @return void
	 */
	private function set_context( array $app, string $scope, string $route_family, ?WP_REST_Request $request = null ): void {
		Request_Context::set_app(
			array(
				'app_id'                    => (string) $app['app_id'],
				'key_id'                    => (string) $app['key_id'],
				'caller_type'               => (string) $app['caller_type'],
				'scope'                     => $scope,
				'scopes'                    => (array) ( $app['scopes'] ?? array() ),
				'route_family'              => $route_family,
				'signed_client_fingerprint' => $request instanceof WP_REST_Request ? $this->signed_client_fingerprint_from_request( $request ) : '',
			)
		);
	}

	/**
	 * Returns the Adapter-authenticated client fingerprint forwarded by a trusted app key.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return string
	 */
	private function signed_client_fingerprint_from_request( WP_REST_Request $request ): string {
		$fingerprint = sanitize_text_field( (string) $request->get_header( 'x_npcink_adapter_signed_client_fingerprint' ) );
		if ( '' === $fingerprint ) {
			$fingerprint = sanitize_text_field( (string) $request->get_header( 'x_npcink_adapter_client_key_fingerprint' ) );
		}

		return 1 === preg_match( '/^sha256:[a-f0-9]{64}$/', $fingerprint ) ? $fingerprint : '';
	}

	/**
	 * Extracts bearer token.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return string
	 */
	private function token_from_request( WP_REST_Request $request ): string {
		$authorization = (string) $request->get_header( 'authorization' );
		if ( preg_match( '/^Bearer\s+(.+)$/i', $authorization, $matches ) ) {
			return trim( (string) $matches[1] );
		}

		return trim( (string) $request->get_header( 'x-npcink-governance-core-app-token' ) );
	}

	/**
	 * Returns permission error.
	 *
	 * @param string $code Error code.
	 * @param string $message Message.
	 * @param int    $status HTTP status.
	 * @return WP_Error
	 */
	private function error( string $code, string $message, int $status ): WP_Error {
		return new WP_Error( $code, $message, array( 'status' => $status ) );
	}
}
