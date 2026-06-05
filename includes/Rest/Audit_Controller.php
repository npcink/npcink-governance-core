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
		$items = $this->audit->list_filtered(
			array(
				'limit'       => (int) $request->get_param( 'limit' ),
				'proposal_id' => (string) $request->get_param( 'proposal_id' ),
				'event_name'  => (string) $request->get_param( 'event_name' ),
				'ability_id'  => (string) $request->get_param( 'ability_id' ),
				'app_id'      => (string) $request->get_param( 'app_id' ),
				'key_id'      => (string) $request->get_param( 'key_id' ),
				'caller_type' => (string) $request->get_param( 'caller_type' ),
				'correlation_id' => (string) $request->get_param( 'correlation_id' ),
			)
		);

		$this->audit->record(
			'audit.listed',
			array(
				'count'       => count( $items ),
				'proposal_id' => (string) $request->get_param( 'proposal_id' ),
				'event_name'  => (string) $request->get_param( 'event_name' ),
				'ability_id'  => (string) $request->get_param( 'ability_id' ),
				'app_id'      => (string) $request->get_param( 'app_id' ),
				'key_id'      => (string) $request->get_param( 'key_id' ),
				'caller_type' => (string) $request->get_param( 'caller_type' ),
				'correlation_id' => (string) $request->get_param( 'correlation_id' ),
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
