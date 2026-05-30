<?php
/**
 * Proposals REST controller.
 *
 * @package MagickAICore
 */

namespace MagickAI\Core\Rest;

use MagickAI\Core\Governance\Commit_Preflight_Service;
use MagickAI\Core\Governance\Proposal_Repository;
use MagickAI\Core\Governance\Proposal_Service;
use MagickAI\Core\Security\App_Authenticator;
use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Exposes proposal records.
 */
final class Proposals_Controller {
	const NAMESPACE = 'magick-ai-core/v1';

	/**
	 * Proposal service.
	 *
	 * @var Proposal_Service
	 */
	private $service;

	/**
	 * Proposal repository.
	 *
	 * @var Proposal_Repository
	 */
	private $repository;

	/**
	 * Commit preflight service.
	 *
	 * @var Commit_Preflight_Service
	 */
	private $preflight;

	/**
	 * Authenticator.
	 *
	 * @var App_Authenticator
	 */
	private $auth;

	/**
	 * Constructor.
	 *
	 * @param Proposal_Service          $service Proposal service.
	 * @param Proposal_Repository       $repository Proposal repository.
	 * @param Commit_Preflight_Service $preflight Commit preflight service.
	 * @param App_Authenticator        $auth Authenticator.
	 */
	public function __construct(
		Proposal_Service $service,
		Proposal_Repository $repository,
		Commit_Preflight_Service $preflight,
		App_Authenticator $auth
	) {
		$this->service    = $service;
		$this->repository = $repository;
		$this->preflight  = $preflight;
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
			'/proposals',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'list_proposals' ),
					'permission_callback' => array( $this->auth, 'can_read_proposals' ),
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
					'callback'            => array( $this, 'create_proposal' ),
					'permission_callback' => array( $this->auth, 'can_create_proposals' ),
					'args'                => array(
						'ability_id' => array(
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						),
						'title'      => array(
							'type'              => 'string',
							'default'           => '',
							'sanitize_callback' => 'sanitize_text_field',
						),
						'summary'    => array(
							'type'              => 'string',
							'default'           => '',
							'sanitize_callback' => 'sanitize_textarea_field',
						),
						'input'      => array(
							'type'    => 'object',
							'default' => array(),
						),
						'preview'    => array(
							'type'    => 'object',
							'default' => array(),
						),
						'caller'     => array(
							'type'    => 'object',
							'default' => array(),
						),
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/proposals/(?P<proposal_id>[A-Za-z0-9_-]+)/approve',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'approve_proposal' ),
					'permission_callback' => array( $this->auth, 'can_approve_proposals' ),
					'args'                => array(
						'proposal_id' => array(
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						),
						'note'        => array(
							'type'              => 'string',
							'default'           => '',
							'sanitize_callback' => 'sanitize_textarea_field',
						),
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/proposals/(?P<proposal_id>[A-Za-z0-9_-]+)/reject',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'reject_proposal' ),
					'permission_callback' => array( $this->auth, 'can_reject_proposals' ),
					'args'                => array(
						'proposal_id' => array(
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						),
						'note'        => array(
							'type'              => 'string',
							'default'           => '',
							'sanitize_callback' => 'sanitize_textarea_field',
						),
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/proposals/(?P<proposal_id>[A-Za-z0-9_-]+)',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_proposal' ),
					'permission_callback' => array( $this->auth, 'can_read_proposals' ),
					'args'                => array(
						'proposal_id' => array(
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
			'/proposals/(?P<proposal_id>[A-Za-z0-9_-]+)/commit-preflight',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'commit_preflight' ),
					'permission_callback' => array( $this->auth, 'can_commit_preflight' ),
					'args'                => array(
						'proposal_id' => array(
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			)
		);
	}

	/**
	 * Lists proposals.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function list_proposals( WP_REST_Request $request ): WP_REST_Response {
		$items = $this->repository->list_recent( (int) $request->get_param( 'limit' ) );
		$this->service->record_listed( count( $items ) );

		return new WP_REST_Response(
			array(
				'items' => $items,
			),
			200
		);
	}

	/**
	 * Gets one proposal.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|\WP_Error
	 */
	public function get_proposal( WP_REST_Request $request ) {
		$proposal_id = (string) $request->get_param( 'proposal_id' );
		$proposal    = $this->repository->find( $proposal_id );

		if ( null === $proposal ) {
			return $this->service->not_found_error();
		}

		$this->service->record_viewed( $proposal );
		$proposal['audit_timeline'] = $this->service->audit_timeline( $proposal_id );

		return new WP_REST_Response( $proposal, 200 );
	}

	/**
	 * Creates proposal.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|\WP_Error
	 */
	public function create_proposal( WP_REST_Request $request ) {
		$result = $this->service->create(
			array(
				'ability_id' => $request->get_param( 'ability_id' ),
				'title'      => $request->get_param( 'title' ),
				'summary'    => $request->get_param( 'summary' ),
				'input'      => $request->get_param( 'input' ),
				'preview'    => $request->get_param( 'preview' ),
				'caller'     => $request->get_param( 'caller' ),
			)
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response( $result, 201 );
	}

	/**
	 * Approves proposal.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|\WP_Error
	 */
	public function approve_proposal( WP_REST_Request $request ) {
		$result = $this->service->approve(
			(string) $request->get_param( 'proposal_id' ),
			array(
				'note' => (string) $request->get_param( 'note' ),
			)
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response( $result, 200 );
	}

	/**
	 * Rejects proposal.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|\WP_Error
	 */
	public function reject_proposal( WP_REST_Request $request ) {
		$result = $this->service->reject(
			(string) $request->get_param( 'proposal_id' ),
			array(
				'note' => (string) $request->get_param( 'note' ),
			)
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response( $result, 200 );
	}

	/**
	 * Runs commit preflight.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|\WP_Error
	 */
	public function commit_preflight( WP_REST_Request $request ) {
		$result = $this->preflight->preflight(
			(string) $request->get_param( 'proposal_id' ),
			$request->get_params()
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response( $result, 200 );
	}
}
