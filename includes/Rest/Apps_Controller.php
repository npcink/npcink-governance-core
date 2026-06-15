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
}
