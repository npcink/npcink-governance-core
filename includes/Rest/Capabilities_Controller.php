<?php
/**
 * Capabilities REST controller.
 *
 * @package MagickAICore
 */

namespace MagickAI\Core\Rest;

use MagickAI\Core\Audit\Audit_Log_Repository;
use MagickAI\Core\Capabilities\Ability_Registry_Adapter;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Exposes read-only capability intake.
 */
final class Capabilities_Controller {
	const NAMESPACE = 'magick-ai-core/v1';

	/**
	 * Ability adapter.
	 *
	 * @var Ability_Registry_Adapter
	 */
	private $abilities;

	/**
	 * Audit repository.
	 *
	 * @var Audit_Log_Repository
	 */
	private $audit;

	/**
	 * Constructor.
	 *
	 * @param Ability_Registry_Adapter $abilities Ability adapter.
	 * @param Audit_Log_Repository     $audit Audit repository.
	 */
	public function __construct( Ability_Registry_Adapter $abilities, Audit_Log_Repository $audit ) {
		$this->abilities = $abilities;
		$this->audit     = $audit;
	}

	/**
	 * Registers routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/capabilities',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'list_capabilities' ),
					'permission_callback' => array( Rest_Permissions::class, 'can_manage' ),
				),
			)
		);
	}

	/**
	 * Lists capabilities.
	 *
	 * @return WP_REST_Response
	 */
	public function list_capabilities(): WP_REST_Response {
		$result = $this->abilities->list_capabilities();

		$this->audit->record(
			'capabilities.listed',
			array(
				'source' => $result['source'],
				'count'  => $result['count'],
			)
		);

		return new WP_REST_Response( $result, 200 );
	}
}

