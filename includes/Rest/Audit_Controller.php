<?php
/**
 * Audit REST controller.
 *
 * @package NpcinkGovernanceCore
 */

namespace Npcink\GovernanceCore\Rest;

use Npcink\GovernanceCore\Audit\Audit_Log_Repository;
use Npcink\GovernanceCore\Security\App_Authenticator;
use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Exposes audit events.
 */
final class Audit_Controller {
	const NAMESPACE = 'npcink-governance-core/v1';

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
	 * @param Audit_Log_Repository $audit Audit repository.
	 * @param App_Authenticator    $auth Authenticator.
	 */
	public function __construct( Audit_Log_Repository $audit, App_Authenticator $auth ) {
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
			'/audit',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'list_events' ),
					'permission_callback' => array( $this->auth, 'can_read_audit' ),
					'args'                => array(
						'limit' => array(
							'type'              => 'integer',
							'default'           => 50,
							'sanitize_callback' => 'absint',
						),
						'offset' => array(
							'type'              => 'integer',
							'default'           => 0,
							'sanitize_callback' => 'absint',
						),
						'order' => array(
							'type'              => 'string',
							'default'           => 'desc',
							'enum'              => array( 'asc', 'desc' ),
							'sanitize_callback' => 'sanitize_key',
						),
						'search' => array(
							'type'              => 'string',
							'default'           => '',
							'sanitize_callback' => 'sanitize_text_field',
						),
						'created_after' => array(
							'type'              => 'string',
							'default'           => '',
							'sanitize_callback' => 'sanitize_text_field',
						),
						'proposal_id' => array(
							'type'              => 'string',
							'default'           => '',
							'sanitize_callback' => 'sanitize_text_field',
						),
						'event_name'  => array(
							'type'              => 'string',
							'default'           => '',
							'sanitize_callback' => 'sanitize_text_field',
						),
						'ability_id'  => array(
							'type'              => 'string',
							'default'           => '',
							'sanitize_callback' => 'sanitize_text_field',
						),
						'app_id'      => array(
							'type'              => 'string',
							'default'           => '',
							'sanitize_callback' => 'sanitize_text_field',
						),
						'key_id'      => array(
							'type'              => 'string',
							'default'           => '',
							'sanitize_callback' => 'sanitize_text_field',
						),
						'caller_type' => array(
							'type'              => 'string',
							'default'           => '',
							'sanitize_callback' => 'sanitize_key',
						),
						'correlation_id' => array(
							'type'              => 'string',
							'default'           => '',
							'sanitize_callback' => 'sanitize_text_field',
						),
						'include_read_events' => array(
							'type'              => 'boolean',
							'default'           => false,
							'sanitize_callback' => 'rest_sanitize_boolean',
						),
					),
				)
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
		$filters = array(
			'limit'          => max( 1, min( 200, absint( $request->get_param( 'limit' ) ) ) ),
			'offset'         => max( 0, absint( $request->get_param( 'offset' ) ) ),
			'order'          => (string) $request->get_param( 'order' ),
			'search'         => (string) $request->get_param( 'search' ),
			'created_after'  => (string) $request->get_param( 'created_after' ),
			'proposal_id'    => (string) $request->get_param( 'proposal_id' ),
			'event_name'     => (string) $request->get_param( 'event_name' ),
			'ability_id'     => (string) $request->get_param( 'ability_id' ),
			'app_id'         => (string) $request->get_param( 'app_id' ),
			'key_id'         => (string) $request->get_param( 'key_id' ),
			'caller_type'    => (string) $request->get_param( 'caller_type' ),
			'correlation_id' => (string) $request->get_param( 'correlation_id' ),
		);

		// Read-noise events stay excluded by default so offset paging is
		// stable: this call itself records an `audit.listed` row that must
		// not shift the next page. An explicit event_name filter keeps
		// precedence over the exclusion inside the repository.
		if ( ! (bool) $request->get_param( 'include_read_events' ) ) {
			$filters['exclude_event_names'] = $this->audit->read_noise_event_names();
		}

		$items = $this->audit->list_filtered( $filters );
		$items = Rest_Format::rows( $items, array( 'created_at' ) );

		// Counted before the audit.listed event below is written so meta.total
		// always describes the same snapshot the items were listed from.
		$total = $this->audit->count_filtered( $filters );

		$this->audit->record(
			'audit.listed',
			array(
				'count'       => count( $items ),
				'offset'      => $filters['offset'],
				'search'      => $filters['search'],
				'proposal_id' => $filters['proposal_id'],
				'event_name'  => $filters['event_name'],
				'ability_id'  => $filters['ability_id'],
				'app_id'      => $filters['app_id'],
				'key_id'      => $filters['key_id'],
				'caller_type' => $filters['caller_type'],
				'correlation_id' => $filters['correlation_id'],
			)
		);

		$response = new WP_REST_Response(
			array(
				'items' => $items,
				'meta'  => array(
					'limit'  => $filters['limit'],
					'offset' => $filters['offset'],
					'total'  => $total,
				),
			),
			200
		);
		$response->header( 'X-WP-Total', (string) $total );

		return $response;
	}
}
