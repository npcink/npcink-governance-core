<?php
/**
 * Apps REST controller.
 *
 * @package NpcinkGovernanceCore
 */

namespace Npcink\GovernanceCore\Rest;

use Npcink\GovernanceCore\Audit\Audit_Log_Repository;
use Npcink\GovernanceCore\Security\App_Key_Repository;
use Npcink\GovernanceCore\Security\App_Authenticator;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Exposes app key management for administrators.
 */
final class Apps_Controller {
	const NAMESPACE = 'npcink-governance-core/v1';

	/**
	 * App key repository.
	 *
	 * @var App_Key_Repository
	 */
	private $apps;

	/**
	 * Audit repository.
	 *
	 * @var Audit_Log_Repository
	 */
	private $audit;

	/**
	 * Authenticator.
	 *
	 * @var App_Authenticator
	 */
	private $auth;

	/**
	 * Constructor.
	 *
	 * @param App_Key_Repository $apps App key repository.
	 * @param Audit_Log_Repository $audit Audit repository.
	 * @param App_Authenticator $auth Authenticator.
	 */
	public function __construct( App_Key_Repository $apps, Audit_Log_Repository $audit, App_Authenticator $auth ) {
		$this->apps  = $apps;
		$this->audit = $audit;
		$this->auth  = $auth;
	}

	/**
	 * Registers routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/apps',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'list_apps' ),
					'permission_callback' => array( $this->auth, 'can_manage' ),
					'args'                => array(
						'limit' => array(
							'type'              => 'integer',
							'default'           => 50,
							'sanitize_callback' => 'absint',
						),
					),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'create_app' ),
					'permission_callback' => array( $this->auth, 'can_manage' ),
					'args'                => array(
						'app_label' => array(
							'type'              => 'string',
							'default'           => 'External app',
							'sanitize_callback' => 'sanitize_text_field',
						),
						'caller_type' => array(
							'type'              => 'string',
							'default'           => 'external_app',
							'sanitize_callback' => 'sanitize_key',
						),
						'scopes' => array(
							'type'    => 'array',
							'default' => array(),
						),
						'rate_limit' => array(
							'type'              => 'integer',
							'default'           => App_Key_Repository::DEFAULT_RATE_LIMIT,
							'sanitize_callback' => 'absint',
						),
						'rate_window_seconds' => array(
							'type'              => 'integer',
							'default'           => App_Key_Repository::DEFAULT_RATE_WINDOW,
							'sanitize_callback' => 'absint',
						),
						'expires_at' => array(
							'type'              => 'string',
							'default'           => '',
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/apps/(?P<key_id>[A-Za-z0-9_-]+)/rotate',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'rotate_app' ),
					'permission_callback' => array( $this->auth, 'can_manage' ),
					'args'                => array(
						'key_id' => array(
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						),
						'expires_at' => array(
							'type'              => 'string',
							'default'           => '',
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			)
		);
	}

	/**
	 * Lists apps.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function list_apps( WP_REST_Request $request ): WP_REST_Response {
		$items = $this->apps->list_recent( (int) $request->get_param( 'limit' ) );
		$this->audit->record(
			'app.listed',
			array(
				'count' => count( $items ),
			)
		);

		return new WP_REST_Response( array( 'items' => $items ), 200 );
	}

	/**
	 * Creates app key.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_app( WP_REST_Request $request ) {
		$data = array(
			'app_label'           => $request->get_param( 'app_label' ),
			'caller_type'         => $request->get_param( 'caller_type' ),
			'rate_limit'          => $request->get_param( 'rate_limit' ),
			'rate_window_seconds' => $request->get_param( 'rate_window_seconds' ),
			'expires_at'          => $request->get_param( 'expires_at' ),
		);

		$body_params = method_exists( $request, 'get_body_params' ) && is_array( $request->get_body_params() ) ? $request->get_body_params() : array();
		$json_params = method_exists( $request, 'get_json_params' ) && is_array( $request->get_json_params() ) ? $request->get_json_params() : array();
		$body_params = array_merge( $body_params, $json_params );
		$scope_param = $request->get_param( 'scopes' );
		if ( array_key_exists( 'scopes', $body_params ) || ( is_array( $scope_param ) && ! empty( $scope_param ) ) ) {
			$data['scopes'] = is_array( $scope_param ) ? $scope_param : array();
		}

		$app = $this->apps->create( $data );

		if ( is_wp_error( $app ) ) {
			return $app;
		}

		$event_id = $this->audit->record(
			'app.created',
			array(
				'app_id'      => (string) $app['app_id'],
				'key_id'      => (string) $app['key_id'],
				'caller_type' => (string) $app['caller_type'],
				'scopes'      => (array) $app['scopes'],
			)
		);

		if ( '' === $event_id ) {
			$this->apps->revoke_by_key_id( (string) $app['key_id'] );
			return new WP_Error(
				'npcink_governance_core_app_audit_failed',
				__( 'App key creation could not be audited.', 'npcink-governance-core' ),
				array( 'status' => 500 )
			);
		}

		return new WP_REST_Response( $app, 201 );
	}

	/**
	 * Rotates an active app key by issuing a replacement token and revoking the old key.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function rotate_app( WP_REST_Request $request ) {
		$key_id = sanitize_text_field( (string) $request->get_param( 'key_id' ) );
		$old    = '' !== $key_id ? $this->apps->find_by_key_id( $key_id ) : null;
		if ( null === $old || 'active' !== (string) ( $old['status'] ?? '' ) ) {
			return new WP_Error(
				'npcink_governance_core_app_key_not_active',
				__( 'Only active app keys can be rotated.', 'npcink-governance-core' ),
				array( 'status' => 404 )
			);
		}

		$replacement = $this->apps->create(
			array(
				'app_label'           => (string) ( $old['app_label'] ?? 'External app' ),
				'caller_type'         => (string) ( $old['caller_type'] ?? 'external_app' ),
				'scopes'              => (array) ( $old['scopes'] ?? array() ),
				'rate_limit'          => (int) ( $old['rate_limit'] ?? App_Key_Repository::DEFAULT_RATE_LIMIT ),
				'rate_window_seconds' => (int) ( $old['rate_window_seconds'] ?? App_Key_Repository::DEFAULT_RATE_WINDOW ),
				'expires_at'          => (string) ( $request->get_param( 'expires_at' ) ?: ( $old['expires_at'] ?? '' ) ),
			)
		);
		if ( is_wp_error( $replacement ) ) {
			return $replacement;
		}

		$event_id = $this->audit->record(
			'app.rotated',
			array(
				'old_app_id'      => (string) ( $old['app_id'] ?? '' ),
				'old_key_id'      => (string) ( $old['key_id'] ?? '' ),
				'new_app_id'      => (string) ( $replacement['app_id'] ?? '' ),
				'new_key_id'      => (string) ( $replacement['key_id'] ?? '' ),
				'caller_type'     => (string) ( $replacement['caller_type'] ?? '' ),
				'scopes'          => (array) ( $replacement['scopes'] ?? array() ),
				'old_key_revoked' => false,
			)
		);
		if ( '' === $event_id ) {
			$this->apps->revoke_by_key_id( (string) $replacement['key_id'], 'rotation_audit_failed' );
			return new WP_Error(
				'npcink_governance_core_app_rotation_audit_failed',
				__( 'App key rotation could not be audited.', 'npcink-governance-core' ),
				array( 'status' => 500 )
			);
		}

		if ( ! $this->apps->revoke_by_key_id( $key_id, 'rotated_to:' . (string) $replacement['key_id'] ) ) {
			$this->apps->revoke_by_key_id( (string) $replacement['key_id'], 'rotation_revoke_failed' );
			return new WP_Error(
				'npcink_governance_core_app_rotation_revoke_failed',
				__( 'App key rotation could not revoke the old key.', 'npcink-governance-core' ),
				array( 'status' => 500 )
			);
		}

		$revoked_event_id = $this->audit->record(
			'app.revoked',
			array(
				'app_id'        => (string) ( $old['app_id'] ?? '' ),
				'key_id'        => (string) ( $old['key_id'] ?? '' ),
				'caller_type'   => (string) ( $old['caller_type'] ?? '' ),
				'revoke_reason' => 'rotated',
			)
		);
		if ( '' === $revoked_event_id ) {
			$this->apps->revoke_by_key_id( (string) $replacement['key_id'], 'rotation_revoke_audit_failed' );
			return new WP_Error(
				'npcink_governance_core_app_rotation_revoke_audit_failed',
				__( 'App key rotation could not audit old-key revocation.', 'npcink-governance-core' ),
				array( 'status' => 500 )
			);
		}

		$replacement['rotated_from_key_id'] = $key_id;
		$replacement['old_key_revoked']    = true;

		return new WP_REST_Response( $replacement, 201 );
	}
}
