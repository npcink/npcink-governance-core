<?php
/**
 * Apps REST controller.
 *
 * @package MagickAICore
 */

namespace MagickAI\Core\Rest;

use MagickAI\Core\Audit\Audit_Log_Repository;
use MagickAI\Core\Security\App_Key_Repository;
use MagickAI\Core\Security\App_Authenticator;
use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Exposes app key management for administrators.
 */
final class Apps_Controller {
	const NAMESPACE = 'magick-ai-core/v1';

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
	 * @return WP_REST_Response
	 */
	public function create_app( WP_REST_Request $request ): WP_REST_Response {
		$app = $this->apps->create(
			array(
				'app_label'           => $request->get_param( 'app_label' ),
				'caller_type'         => $request->get_param( 'caller_type' ),
				'scopes'              => is_array( $request->get_param( 'scopes' ) ) ? $request->get_param( 'scopes' ) : array(),
				'rate_limit'          => $request->get_param( 'rate_limit' ),
				'rate_window_seconds' => $request->get_param( 'rate_window_seconds' ),
			)
		);

		$this->audit->record(
			'app.created',
			array(
				'app_id'      => (string) $app['app_id'],
				'key_id'      => (string) $app['key_id'],
				'caller_type' => (string) $app['caller_type'],
				'scopes'      => (array) $app['scopes'],
			)
		);

		return new WP_REST_Response( $app, 201 );
	}
}

