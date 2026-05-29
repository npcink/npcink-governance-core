<?php
/**
 * Audit REST controller.
 *
 * @package MagickAICore
 */

namespace MagickAI\Core\Rest;

use MagickAI\Core\Audit\Audit_Log_Repository;
use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Exposes audit events.
 */
final class Audit_Controller {
	const NAMESPACE = 'magick-ai-core/v1';

	/**
	 * Audit repository.
	 *
	 * @var Audit_Log_Repository
	 */
	private $audit;

	/**
	 * Constructor.
	 *
	 * @param Audit_Log_Repository $audit Audit repository.
	 */
	public function __construct( Audit_Log_Repository $audit ) {
		$this->audit = $audit;
	}

	/**
	 * Registers routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/audit',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'list_events' ),
					'permission_callback' => array( Rest_Permissions::class, 'can_manage' ),
					'args'                => array(
						'limit' => array(
							'type'              => 'integer',
							'default'           => 50,
							'sanitize_callback' => 'absint',
						),
					),
				),
			)
		);
	}

	/**
	 * Lists audit events.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function list_events( WP_REST_Request $request ): WP_REST_Response {
		$items = $this->audit->list_recent( (int) $request->get_param( 'limit' ) );

		$this->audit->record(
			'audit.listed',
			array(
				'count' => count( $items ),
			)
		);

		return new WP_REST_Response(
			array(
				'items' => $items,
			),
			200
		);
	}
}

