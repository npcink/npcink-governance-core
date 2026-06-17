<?php
/**
 * Runtime contract REST controller.
 *
 * @package NpcinkGovernanceCore
 */

namespace Npcink\GovernanceCore\Rest;

use Npcink\GovernanceCore\Security\App_Authenticator;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Exposes stable Core governance contract metadata.
 */
final class Contract_Controller {
	const NAMESPACE                            = 'npcink-governance-core/v1';
	const CORE_CONTRACT_VERSION                = '1';
	const GOVERNANCE_CONTRACT_VERSION          = '1';
	const REST_API_CONTRACT_VERSION            = '1';
	const PROPOSAL_LIFECYCLE_VERSION           = '1';
	const APPROVAL_COMMIT_CONTRACT_VERSION     = '1';
	const SENSITIVE_READ_AUTHORIZATION_VERSION = '1';
	const APP_AUTH_CONTRACT_VERSION            = '1';
	const RUNTIME_CONTRACT_ENDPOINT_VERSION    = '1';

	/**
	 * Authenticator.
	 *
	 * @var App_Authenticator
	 */
	private $auth;

	/**
	 * Constructor.
	 *
	 * @param App_Authenticator $auth Authenticator.
	 */
	public function __construct( App_Authenticator $auth ) {
		$this->auth = $auth;
	}

	/**
	 * Registers routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/contract',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'contract' ),
					'permission_callback' => array( $this->auth, 'can_manage' ),
				),
			)
		);
	}

	/**
	 * Returns the Core runtime contract.
	 *
	 * @return WP_REST_Response
	 */
	public function contract(): WP_REST_Response {
		return new WP_REST_Response(
			array(
				'schema_version'                         => 'npcink_governance_core_contract.v1',
				'core_contract_version'                  => self::CORE_CONTRACT_VERSION,
				'plugin_version'                         => defined( 'NPCINK_GOVERNANCE_CORE_VERSION' ) ? (string) NPCINK_GOVERNANCE_CORE_VERSION : '',
				'rest_namespace'                         => self::NAMESPACE,
				'governance_contract_version'            => self::GOVERNANCE_CONTRACT_VERSION,
				'rest_api_contract_version'              => self::REST_API_CONTRACT_VERSION,
				'proposal_lifecycle_version'             => self::PROPOSAL_LIFECYCLE_VERSION,
				'approval_commit_contract_version'       => self::APPROVAL_COMMIT_CONTRACT_VERSION,
				'sensitive_read_authorization_version'   => self::SENSITIVE_READ_AUTHORIZATION_VERSION,
				'app_auth_contract_version'              => self::APP_AUTH_CONTRACT_VERSION,
				'runtime_contract_endpoint_version'      => self::RUNTIME_CONTRACT_ENDPOINT_VERSION,
				'compatibility'                          => array(
					'contract_family'                    => 'npcink_governance_core',
					'minimum_adapter_contract_version'   => '1',
					'metadata_only'                      => true,
					'admin_authenticated'                => true,
					'proposal_truth_available'           => true,
					'approval_truth_available'           => true,
					'commit_preflight_available'         => true,
					'execution_result_record_available'  => true,
					'sensitive_read_preflight_available' => true,
				),
				'runtime_controls'                       => array(
					'core_proxy_execute'      => false,
					'commit_execution'        => false,
					'read_proxy_execute'      => false,
					'workflow_orchestration'  => false,
					'background_jobs'         => false,
					'batch_execution'         => false,
					'mcp_transport'           => false,
					'agent_catalog'           => false,
					'provider_secret_storage' => false,
				),
				'context_bindings'                       => array(
					'site_binding'           => array(
						'fields'       => array( 'site_url', 'home_url', 'blog_id' ),
						'emitted_in'   => array( 'approval_context', 'execution_handoff', 'read_authorization_context' ),
						'site_url'     => $this->normalize_url( function_exists( 'site_url' ) ? site_url() : '' ),
						'home_url'     => $this->normalize_url( function_exists( 'home_url' ) ? home_url() : '' ),
						'blog_id'      => function_exists( 'get_current_blog_id' ) ? get_current_blog_id() : 0,
						'fail_closed'  => true,
					),
					'client_key_fingerprint' => array(
						'field'      => 'client_key_fingerprint',
						'emitted'    => false,
						'status'     => 'pending_signed_client_identity_contract',
						'owner'      => 'npcink-governance-core',
					),
				),
				'handoff_routes'                         => array(
					'capabilities'                       => '/wp-json/npcink-governance-core/v1/capabilities',
					'proposal_create'                    => '/wp-json/npcink-governance-core/v1/proposals',
					'plan_to_proposal'                   => '/wp-json/npcink-governance-core/v1/proposals/from-plan',
					'commit_preflight_template'          => '/wp-json/npcink-governance-core/v1/proposals/{proposal_id}/commit-preflight',
					'record_execution_template'          => '/wp-json/npcink-governance-core/v1/proposals/{proposal_id}/record-execution',
					'read_request_create'                => '/wp-json/npcink-governance-core/v1/read-requests',
					'read_preflight_template'            => '/wp-json/npcink-governance-core/v1/read-requests/{request_id}/read-preflight',
				),
				'boundary'                               => array(
					'proposal_truth_owner'       => 'npcink-governance-core',
					'approval_truth_owner'       => 'npcink-governance-core',
					'audit_truth_owner'          => 'npcink-governance-core',
					'ability_definitions_owner'  => 'wordpress_abilities_provider',
					'final_write_authority'      => 'adapter_or_host_after_core_preflight',
					'workflow_execution_owner'   => 'external_dedicated_runtime',
					'cloud_control_plane_owner'  => 'not_npcink-governance-core',
				),
				'forbidden_payloads'                     => array(
					'proposal_rows'          => false,
					'audit_rows'             => false,
					'app_secret_material'    => false,
					'provider_secret_material' => false,
					'ability_definitions'    => false,
					'runtime_state'          => false,
					'final_execution_results' => false,
				),
			),
			200
		);
	}

	/**
	 * Normalizes a URL for context binding comparisons.
	 *
	 * @param string $url URL.
	 * @return string
	 */
	private function normalize_url( string $url ): string {
		return rtrim( $url, '/' );
	}
}
