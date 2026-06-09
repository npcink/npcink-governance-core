<?php
/**
 * Sensitive read request REST controller.
 *
 * @package NpcinkGovernanceCore
 */

namespace Npcink\GovernanceCore\Rest;

use Npcink\GovernanceCore\Governance\Read_Request_Repository;
use Npcink\GovernanceCore\Governance\Read_Request_Service;
use Npcink\GovernanceCore\Security\App_Authenticator;
use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Exposes Core-owned sensitive read authorization records.
 */
final class Read_Requests_Controller {
	const NAMESPACE = 'npcink-governance-core/v1';

	/**
	 * Service.
	 *
	 * @var Read_Request_Service
	 */
	private $service;

	/**
	 * Repository.
	 *
	 * @var Read_Request_Repository
	 */
	private $repository;

	/**
	 * Authenticator.
	 *
	 * @var App_Authenticator
	 */
	private $auth;

	/**
	 * Constructor.
	 *
	 * @param Read_Request_Service    $service Service.
	 * @param Read_Request_Repository $repository Repository.
	 * @param App_Authenticator       $auth Authenticator.
	 */
	public function __construct( Read_Request_Service $service, Read_Request_Repository $repository, App_Authenticator $auth ) {
		$this->service    = $service;
		$this->repository = $repository;
		$this->auth       = $auth;
	}

	/**
	 * Registers routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/read-requests',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'list_requests' ),
					'permission_callback' => array( $this->auth, 'can_read_read_requests' ),
					'args'                => array(
						'limit'  => array(
							'type'              => 'integer',
							'default'           => 50,
							'sanitize_callback' => 'absint',
						),
						'status' => array(
							'type'              => 'string',
							'default'           => '',
							'sanitize_callback' => 'sanitize_key',
						),
					),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'create_request' ),
					'permission_callback' => array( $this->auth, 'can_create_read_requests' ),
					'args'                => $this->request_args(),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/read-requests/(?P<request_id>[A-Za-z0-9_-]+)',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_request' ),
					'permission_callback' => array( $this->auth, 'can_read_read_requests' ),
					'args'                => array(
						'request_id' => array(
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/read-requests/(?P<request_id>[A-Za-z0-9_-]+)/approve',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'approve_request' ),
					'permission_callback' => array( $this->auth, 'can_approve_read_requests' ),
					'args'                => array_merge( $this->approval_args(), $this->request_id_arg() ),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/read-requests/(?P<request_id>[A-Za-z0-9_-]+)/reject',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'reject_request' ),
					'permission_callback' => array( $this->auth, 'can_reject_read_requests' ),
					'args'                => array_merge(
						array(
							'note' => array(
								'type'              => 'string',
								'default'           => '',
								'sanitize_callback' => 'sanitize_textarea_field',
							),
						),
						$this->request_id_arg()
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/read-requests/(?P<request_id>[A-Za-z0-9_-]+)/read-preflight',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'read_preflight' ),
					'permission_callback' => array( $this->auth, 'can_preflight_read_requests' ),
					'args'                => array_merge( $this->preflight_args(), $this->request_id_arg() ),
				),
			)
		);
	}

	/**
	 * Lists requests.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function list_requests( WP_REST_Request $request ): WP_REST_Response {
		$items = $this->repository->list_recent( (int) $request->get_param( 'limit' ), (string) $request->get_param( 'status' ) );
		$this->service->record_listed( count( $items ) );

		return new WP_REST_Response( array( 'items' => $items ), 200 );
	}

	/**
	 * Gets one request.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|\WP_Error
	 */
	public function get_request( WP_REST_Request $request ) {
		$request_id = (string) $request->get_param( 'request_id' );
		$row        = $this->repository->find( $request_id );
		if ( null === $row ) {
			return $this->service->not_found_error();
		}

		$this->service->record_viewed( $row );
		$row['audit_timeline'] = $this->service->audit_timeline( $request_id );

		return new WP_REST_Response( $row, 200 );
	}

	/**
	 * Creates a request.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|\WP_Error
	 */
	public function create_request( WP_REST_Request $request ) {
		$result = $this->service->create( $request->get_params() );

		return is_wp_error( $result ) ? $result : new WP_REST_Response( $result, 201 );
	}

	/**
	 * Approves a request.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|\WP_Error
	 */
	public function approve_request( WP_REST_Request $request ) {
		$result = $this->service->approve( (string) $request->get_param( 'request_id' ), $request->get_params() );

		return is_wp_error( $result ) ? $result : new WP_REST_Response( $result, 200 );
	}

	/**
	 * Rejects a request.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|\WP_Error
	 */
	public function reject_request( WP_REST_Request $request ) {
		$result = $this->service->reject( (string) $request->get_param( 'request_id' ), $request->get_params() );

		return is_wp_error( $result ) ? $result : new WP_REST_Response( $result, 200 );
	}

	/**
	 * Runs read preflight.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|\WP_Error
	 */
	public function read_preflight( WP_REST_Request $request ) {
		$result = $this->service->preflight( (string) $request->get_param( 'request_id' ), $request->get_params() );

		return is_wp_error( $result ) ? $result : new WP_REST_Response( $result, 200 );
	}

	/**
	 * Returns request arg definitions.
	 *
	 * @return array<string,mixed>
	 */
	private function request_args(): array {
		return array_merge(
			array(
				'ability_id' => array(
					'type'              => 'string',
					'required'          => true,
					'sanitize_callback' => 'sanitize_text_field',
				),
				'input_hash' => array(
					'type'              => 'string',
					'default'           => '',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'input'      => array(
					'type'    => 'object',
					'default' => array(),
				),
				'requested_input_summary' => array(
					'type'              => 'string',
					'default'           => '',
					'sanitize_callback' => 'sanitize_textarea_field',
				),
				'sensitivity' => array(
					'type'              => 'string',
					'default'           => 'sensitive',
					'sanitize_callback' => 'sanitize_key',
				),
				'data_classes' => array(
					'type'    => 'array',
					'default' => array(),
				),
					'redaction_level' => array(
						'type'              => 'string',
						'default'           => 'strict',
						'sanitize_callback' => 'sanitize_key',
					),
				'purpose'    => array(
					'type'              => 'string',
					'default'           => '',
					'sanitize_callback' => 'sanitize_textarea_field',
				),
				'caller'     => array(
					'type'    => 'object',
					'default' => array(),
				),
				'expires_at' => array(
					'type'              => 'string',
					'default'           => '',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'bounds'     => array(
					'type'    => 'object',
					'default' => array(),
				),
			),
			$this->bounds_args()
		);
	}

	/**
	 * Returns approval arg definitions.
	 *
	 * @return array<string,mixed>
	 */
	private function approval_args(): array {
		return array_merge(
			array(
				'note' => array(
					'type'              => 'string',
					'default'           => '',
					'sanitize_callback' => 'sanitize_textarea_field',
				),
				'expires_at' => array(
					'type'              => 'string',
					'default'           => '',
					'sanitize_callback' => 'sanitize_text_field',
				),
					'redaction_level' => array(
						'type'              => 'string',
						'default'           => 'strict',
						'sanitize_callback' => 'sanitize_key',
					),
				'bounds' => array(
					'type'    => 'object',
					'default' => array(),
				),
			),
			$this->bounds_args()
		);
	}

	/**
	 * Returns preflight arg definitions.
	 *
	 * @return array<string,mixed>
	 */
	private function preflight_args(): array {
		return array(
			'ability_id' => array(
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
			),
			'input_hash' => array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'input' => array(
				'type'    => 'object',
				'default' => array(),
			),
		);
	}

	/**
	 * Returns bounds arg definitions.
	 *
	 * @return array<string,mixed>
	 */
	private function bounds_args(): array {
		return array(
			'max_rows' => array(
				'type'              => 'integer',
				'default'           => 0,
				'sanitize_callback' => 'absint',
			),
			'tail_lines' => array(
				'type'              => 'integer',
				'default'           => 0,
				'sanitize_callback' => 'absint',
			),
			'allowed_fields' => array(
				'type'    => 'array',
				'default' => array(),
			),
			'denied_fields' => array(
				'type'    => 'array',
				'default' => array(),
			),
			'one_time' => array(
				'type'    => 'boolean',
				'default' => false,
			),
		);
	}

	/**
	 * Returns request id arg.
	 *
	 * @return array<string,mixed>
	 */
	private function request_id_arg(): array {
		return array(
			'request_id' => array(
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
			),
		);
	}
}
