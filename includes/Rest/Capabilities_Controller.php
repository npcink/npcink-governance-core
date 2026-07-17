<?php
/**
 * Capabilities REST controller.
 *
 * @package NpcinkGovernanceCore
 */

namespace Npcink\GovernanceCore\Rest;

use Npcink\GovernanceCore\Audit\Audit_Log_Repository;
use Npcink\GovernanceCore\Capabilities\Ability_Registry_Adapter;
use Npcink\GovernanceCore\Security\App_Authenticator;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Exposes read-only capability intake.
 */
final class Capabilities_Controller {
	const NAMESPACE = 'npcink-governance-core/v1';

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
	 * Authenticator.
	 *
	 * @var App_Authenticator
	 */
	private $auth;

	/**
	 * Constructor.
	 *
	 * @param Ability_Registry_Adapter $abilities Ability adapter.
	 * @param Audit_Log_Repository     $audit Audit repository.
	 * @param App_Authenticator        $auth Authenticator.
	 */
	public function __construct( Ability_Registry_Adapter $abilities, Audit_Log_Repository $audit, App_Authenticator $auth ) {
		$this->abilities = $abilities;
		$this->audit     = $audit;
		$this->auth      = $auth;
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
					'permission_callback' => array( $this->auth, 'can_read_capabilities' ),
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
				'source'        => $result['source'],
				'count'         => $result['count'],
				'ready_count'   => $result['ready_count'] ?? 0,
				'blocked_count' => $result['blocked_count'] ?? 0,
			)
		);

		return new WP_REST_Response( $result, 200 );
	}
}
