<?php
/**
 * Plugin bootstrap.
 *
 * @package NpcinkGovernanceCore
 */

namespace Npcink\GovernanceCore;

use Npcink\GovernanceCore\Admin\Admin_Page;
use Npcink\GovernanceCore\Audit\Audit_Log_Repository;
use Npcink\GovernanceCore\Capabilities\Ability_Registry_Adapter;
use Npcink\GovernanceCore\Governance\Approval_Policy_Evaluator;
use Npcink\GovernanceCore\Governance\Commit_Preflight_Service;
use Npcink\GovernanceCore\Governance\History_Cleanup_Service;
use Npcink\GovernanceCore\Governance\Operation_Classifier;
use Npcink\GovernanceCore\Governance\Plan_Proposal_Service;
use Npcink\GovernanceCore\Governance\Proposal_Repository;
use Npcink\GovernanceCore\Governance\Proposal_Service;
use Npcink\GovernanceCore\Governance\Read_Request_Repository;
use Npcink\GovernanceCore\Governance\Read_Request_Service;
use Npcink\GovernanceCore\Rest\Apps_Controller;
use Npcink\GovernanceCore\Rest\Audit_Controller;
use Npcink\GovernanceCore\Rest\Capabilities_Controller;
use Npcink\GovernanceCore\Rest\Contract_Controller;
use Npcink\GovernanceCore\Rest\Proposals_Controller;
use Npcink\GovernanceCore\Rest\Read_Requests_Controller;
use Npcink\GovernanceCore\Security\App_Authenticator;
use Npcink\GovernanceCore\Security\App_Key_Repository;
use Npcink\GovernanceCore\Security\App_Rate_Limiter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin container.
 */
final class Plugin {
	const HISTORY_CLEANUP_HOOK = 'npcink_governance_core_history_cleanup';

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Ability intake adapter.
	 *
	 * @var Ability_Registry_Adapter|null
	 */
	private $ability_adapter = null;

	/**
	 * Audit repository.
	 *
	 * @var Audit_Log_Repository|null
	 */
	private $audit_repository = null;

	/**
	 * Proposal repository.
	 *
	 * @var Proposal_Repository|null
	 */
	private $proposal_repository = null;

	/**
	 * Proposal service.
	 *
	 * @var Proposal_Service|null
	 */
	private $proposal_service = null;

	/**
	 * Approval policy evaluator.
	 *
	 * @var Approval_Policy_Evaluator|null
	 */
	private $approval_policy_evaluator = null;

	/**
	 * Operation classifier.
	 *
	 * @var Operation_Classifier|null
	 */
	private $operation_classifier = null;

	/**
	 * Commit preflight service.
	 *
	 * @var Commit_Preflight_Service|null
	 */
	private $commit_preflight_service = null;

	/**
	 * Plan-to-proposal service.
	 *
	 * @var Plan_Proposal_Service|null
	 */
	private $plan_proposal_service = null;

	/**
	 * Read request repository.
	 *
	 * @var Read_Request_Repository|null
	 */
	private $read_request_repository = null;

	/**
	 * Read request service.
	 *
	 * @var Read_Request_Service|null
	 */
	private $read_request_service = null;

	/**
	 * App key repository.
	 *
	 * @var App_Key_Repository|null
	 */
	private $app_key_repository = null;

	/**
	 * App rate limiter.
	 *
	 * @var App_Rate_Limiter|null
	 */
	private $app_rate_limiter = null;

	/**
	 * App authenticator.
	 *
	 * @var App_Authenticator|null
	 */
	private $app_authenticator = null;

	/**
	 * History cleanup service.
	 *
	 * @var History_Cleanup_Service|null
	 */
	private $history_cleanup_service = null;

	/**
	 * Returns the singleton.
	 *
	 * @return self
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Activation callback.
	 *
	 * @return void
	 */
	public static function activate(): void {
		self::instance()->proposal_repository()->install();
		self::instance()->read_request_repository()->install();
		self::instance()->audit_repository()->install();
		self::instance()->app_key_repository()->install();
		self::instance()->app_rate_limiter()->install();
		self::instance()->ensure_history_cleanup_event();
	}

	/**
	 * Deactivation callback.
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		self::clear_history_cleanup_event();
	}

	/**
	 * Registers plugin hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
		add_action( self::HISTORY_CLEANUP_HOOK, array( $this, 'run_history_cleanup' ) );
		add_filter( 'npcink_governance_core_record_local_admin_consent', array( $this, 'record_local_admin_consent_audit' ), 10, 3 );
		add_filter( 'plugin_action_links_' . plugin_basename( NPCINK_GOVERNANCE_CORE_FILE ), array( $this, 'filter_plugin_action_links' ) );
		$this->ensure_history_cleanup_event();

		if ( is_admin() ) {
			( new Admin_Page( $this->ability_adapter(), $this->proposal_repository(), $this->audit_repository(), $this->proposal_service(), $this->app_key_repository(), $this->history_cleanup_service() ) )->register();
		}
	}

	/**
	 * Schedules the bounded history cleanup event when missing.
	 *
	 * @return void
	 */
	public function ensure_history_cleanup_event(): void {
		if ( ! wp_next_scheduled( self::HISTORY_CLEANUP_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::HISTORY_CLEANUP_HOOK );
		}
	}

	/**
	 * Clears the bounded history cleanup event.
	 *
	 * @return void
	 */
	public static function clear_history_cleanup_event(): void {
		wp_clear_scheduled_hook( self::HISTORY_CLEANUP_HOOK );
	}

	/**
	 * Runs the bounded history cleanup event.
	 *
	 * @return void
	 */
	public function run_history_cleanup(): void {
		$this->history_cleanup_service()->run( 'wp_cron' );
	}

	/**
	 * Adds a settings shortcut on the WordPress plugins screen.
	 *
	 * @param array<int|string,string> $links Existing plugin action links.
	 * @return array<int|string,string>
	 */
	public function filter_plugin_action_links( array $links ): array {
		$settings_url = menu_page_url( 'npcink-governance-core', false );
		if ( '' === $settings_url ) {
			$settings_url = admin_url( 'tools.php?page=npcink-governance-core' );
		}

		array_unshift(
			$links,
			sprintf(
				'<a href="%1$s">%2$s</a>',
				esc_url( $settings_url ),
				esc_html__( 'Settings', 'npcink-governance-core' )
			)
		);

		return $links;
	}

	/**
	 * Records a local-admin-consent audit event for a local product module.
	 *
	 * This is intentionally an audit-only integration point. It does not create
	 * proposals, approve proposals, preflight commits, or execute abilities.
	 *
	 * @param mixed               $result Existing filter result.
	 * @param string              $event_name Audit event name.
	 * @param array<string,mixed> $metadata Audit metadata.
	 * @return array<string,string>|\WP_Error
	 */
	public function record_local_admin_consent_audit( $result, string $event_name, array $metadata ) {
		if ( null !== $result ) {
			return $result;
		}

		$allowed_events = array(
			'local_admin_consent.requested' => true,
			'local_admin_consent.completed' => true,
			'local_admin_consent.failed'    => true,
		);
		if ( ! isset( $allowed_events[ $event_name ] ) ) {
			return new \WP_Error(
				'npcink_governance_core_local_consent_audit_event_rejected',
				__( 'Unsupported local admin consent audit event.', 'npcink-governance-core' ),
				array( 'status' => 400 )
			);
		}

		$classification = $this->local_admin_consent_classification_evidence( $metadata );
		if ( is_wp_error( $classification ) ) {
			return $classification;
		}

		$metadata['governance_record_type'] = 'local_admin_consent_audit';
		$metadata['proposal_created']       = false;
		$metadata['core_execution']         = false;
		$metadata['operation_classification'] = $classification;

		$event_id = $this->audit_repository()->record( $event_name, $metadata );
		if ( '' === $event_id ) {
			return new \WP_Error(
				'npcink_governance_core_local_consent_audit_failed',
				__( 'Local admin consent could not be audited.', 'npcink-governance-core' ),
				array( 'status' => 500 )
			);
		}

		return array(
			'event_id'   => $event_id,
			'event_name' => $event_name,
		);
	}

	/**
	 * Validates and normalizes classification evidence for local consent audit.
	 *
	 * @param array<string,mixed> $metadata Audit metadata.
	 * @return array<string,mixed>|\WP_Error
	 */
	private function local_admin_consent_classification_evidence( array $metadata ) {
		$evidence = is_array( $metadata['operation_classification'] ?? null ) ? $metadata['operation_classification'] : $metadata;
		$envelope = is_array( $evidence['decision_envelope'] ?? null ) ? $evidence['decision_envelope'] : array();
		if ( empty( $envelope ) ) {
			return new \WP_Error(
				'npcink_governance_core_local_consent_classification_missing',
				__( 'Local admin consent audit requires operation classification evidence.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		$classification          = sanitize_key( (string) ( $evidence['classification'] ?? ( $envelope['classification'] ?? '' ) ) );
		$envelope_classification = sanitize_key( (string) ( $envelope['classification'] ?? '' ) );
		if ( '' === $classification || $classification !== $envelope_classification ) {
			return new \WP_Error(
				'npcink_governance_core_local_consent_classification_mismatch',
				__( 'Local admin consent audit classification evidence is inconsistent.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		if ( ! in_array( $classification, array( Operation_Classifier::CLASSIFICATION_LOCAL_ADMIN_CONSENT, Operation_Classifier::CLASSIFICATION_STRONG_LOCAL_CONFIRMATION ), true ) ) {
			return new \WP_Error(
				'npcink_governance_core_local_consent_classification_rejected',
				__( 'Local admin consent audit accepts only local consent or strong local confirmation classifications.', 'npcink-governance-core' ),
				array(
					'status'         => 422,
					'classification' => $classification,
				)
			);
		}

		$decision_version = sanitize_key( (string) ( $evidence['decision_version'] ?? ( $envelope['decision_version'] ?? '' ) ) );
		if ( Operation_Classifier::POLICY_VERSION !== $decision_version ) {
			return new \WP_Error(
				'npcink_governance_core_local_consent_classification_version_rejected',
				__( 'Local admin consent audit requires the current operation classification policy version.', 'npcink-governance-core' ),
				array(
					'status'           => 422,
					'decision_version' => $decision_version,
				)
			);
		}

		return array(
			'classification'    => $classification,
			'policy_version'    => Operation_Classifier::POLICY_VERSION,
			'decision_version'  => Operation_Classifier::POLICY_VERSION,
			'decision_envelope' => $envelope,
			'intake_path'       => 'local_admin_consent_audit',
		);
	}

	/**
	 * Registers REST routes.
	 *
	 * @return void
	 */
	public function register_rest_routes(): void {
		( new Contract_Controller( $this->app_authenticator() ) )->register_routes();
		( new Capabilities_Controller( $this->ability_adapter(), $this->audit_repository(), $this->app_authenticator() ) )->register_routes();
		( new Proposals_Controller(
			$this->proposal_service(),
			$this->proposal_repository(),
			$this->commit_preflight_service(),
			$this->plan_proposal_service(),
			$this->app_authenticator()
		) )->register_routes();
		( new Read_Requests_Controller(
			$this->read_request_service(),
			$this->read_request_repository(),
			$this->app_authenticator()
		) )->register_routes();
		( new Audit_Controller( $this->audit_repository(), $this->app_authenticator() ) )->register_routes();
		( new Apps_Controller( $this->app_key_repository(), $this->audit_repository(), $this->app_authenticator() ) )->register_routes();
	}

	/**
	 * Returns ability adapter.
	 *
	 * @return Ability_Registry_Adapter
	 */
	public function ability_adapter(): Ability_Registry_Adapter {
		if ( null === $this->ability_adapter ) {
			$this->ability_adapter = new Ability_Registry_Adapter();
		}

		return $this->ability_adapter;
	}

	/**
	 * Returns audit repository.
	 *
	 * @return Audit_Log_Repository
	 */
	public function audit_repository(): Audit_Log_Repository {
		if ( null === $this->audit_repository ) {
			$this->audit_repository = new Audit_Log_Repository();
		}

		return $this->audit_repository;
	}

	/**
	 * Returns proposal repository.
	 *
	 * @return Proposal_Repository
	 */
	public function proposal_repository(): Proposal_Repository {
		if ( null === $this->proposal_repository ) {
			$this->proposal_repository = new Proposal_Repository();
		}

		return $this->proposal_repository;
	}

	/**
	 * Returns proposal service.
	 *
	 * @return Proposal_Service
	 */
	public function proposal_service(): Proposal_Service {
		if ( null === $this->proposal_service ) {
			$this->proposal_service = new Proposal_Service(
				$this->proposal_repository(),
				$this->ability_adapter(),
				$this->audit_repository(),
				$this->approval_policy_evaluator()
			);
		}

		return $this->proposal_service;
	}

	/**
	 * Returns approval policy evaluator.
	 *
	 * @return Approval_Policy_Evaluator
	 */
	public function approval_policy_evaluator(): Approval_Policy_Evaluator {
		if ( null === $this->approval_policy_evaluator ) {
			$this->approval_policy_evaluator = new Approval_Policy_Evaluator();
		}

		return $this->approval_policy_evaluator;
	}

	/**
	 * Returns operation classifier.
	 *
	 * @return Operation_Classifier
	 */
	public function operation_classifier(): Operation_Classifier {
		if ( null === $this->operation_classifier ) {
			$this->operation_classifier = new Operation_Classifier();
		}

		return $this->operation_classifier;
	}

	/**
	 * Returns commit preflight service.
	 *
	 * @return Commit_Preflight_Service
	 */
	public function commit_preflight_service(): Commit_Preflight_Service {
		if ( null === $this->commit_preflight_service ) {
			$this->commit_preflight_service = new Commit_Preflight_Service( $this->proposal_repository(), $this->ability_adapter(), $this->audit_repository() );
		}

		return $this->commit_preflight_service;
	}

	/**
	 * Returns plan-to-proposal service.
	 *
	 * @return Plan_Proposal_Service
	 */
	public function plan_proposal_service(): Plan_Proposal_Service {
		if ( null === $this->plan_proposal_service ) {
			$this->plan_proposal_service = new Plan_Proposal_Service( $this->ability_adapter(), $this->proposal_service(), $this->audit_repository() );
		}

		return $this->plan_proposal_service;
	}

	/**
	 * Returns read request repository.
	 *
	 * @return Read_Request_Repository
	 */
	public function read_request_repository(): Read_Request_Repository {
		if ( null === $this->read_request_repository ) {
			$this->read_request_repository = new Read_Request_Repository();
		}

		return $this->read_request_repository;
	}

	/**
	 * Returns read request service.
	 *
	 * @return Read_Request_Service
	 */
	public function read_request_service(): Read_Request_Service {
		if ( null === $this->read_request_service ) {
			$this->read_request_service = new Read_Request_Service( $this->read_request_repository(), $this->ability_adapter(), $this->audit_repository() );
		}

		return $this->read_request_service;
	}

	/**
	 * Returns app key repository.
	 *
	 * @return App_Key_Repository
	 */
	public function app_key_repository(): App_Key_Repository {
		if ( null === $this->app_key_repository ) {
			$this->app_key_repository = new App_Key_Repository();
		}

		return $this->app_key_repository;
	}

	/**
	 * Returns app rate limiter.
	 *
	 * @return App_Rate_Limiter
	 */
	public function app_rate_limiter(): App_Rate_Limiter {
		if ( null === $this->app_rate_limiter ) {
			$this->app_rate_limiter = new App_Rate_Limiter();
		}

		return $this->app_rate_limiter;
	}

	/**
	 * Returns app authenticator.
	 *
	 * @return App_Authenticator
	 */
	public function app_authenticator(): App_Authenticator {
		if ( null === $this->app_authenticator ) {
			$this->app_authenticator = new App_Authenticator( $this->app_key_repository(), $this->app_rate_limiter(), $this->audit_repository() );
		}

		return $this->app_authenticator;
	}

	/**
	 * Returns history cleanup service.
	 *
	 * @return History_Cleanup_Service
	 */
	public function history_cleanup_service(): History_Cleanup_Service {
		if ( null === $this->history_cleanup_service ) {
			$this->history_cleanup_service = new History_Cleanup_Service( $this->proposal_repository(), $this->app_key_repository(), $this->audit_repository() );
		}

		return $this->history_cleanup_service;
	}

}
