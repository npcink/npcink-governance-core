<?php
/**
 * Proposals REST controller.
 *
 * @package MagickAICore
 */

namespace MagickAI\Core\Rest;

use MagickAI\Core\Governance\Proposal_Repository;
use MagickAI\Core\Governance\Proposal_Service;
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
	 * Constructor.
	 *
	 * @param Proposal_Service    $service Proposal service.
	 * @param Proposal_Repository $repository Proposal repository.
	 */
	public function __construct( Proposal_Service $service, Proposal_Repository $repository ) {
		$this->service    = $service;
		$this->repository = $repository;
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
					'permission_callback' => array( Rest_Permissions::class, 'can_manage' ),
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
					'permission_callback' => array( Rest_Permissions::class, 'can_manage' ),
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
	}

	/**
	 * Lists proposals.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function list_proposals( WP_REST_Request $request ): WP_REST_Response {
		return new WP_REST_Response(
			array(
				'items' => $this->repository->list_recent( (int) $request->get_param( 'limit' ) ),
			),
			200
		);
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
}

