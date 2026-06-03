<?php
/**
 * Plugin bootstrap.
 *
 * @package MagickAICore
 */

namespace MagickAI\Core;

use MagickAI\Core\Admin\Admin_Page;
use MagickAI\Core\Audit\Audit_Log_Repository;
use MagickAI\Core\Capabilities\Ability_Registry_Adapter;
use MagickAI\Core\Governance\Approval_Policy_Evaluator;
use MagickAI\Core\Governance\Commit_Preflight_Service;
use MagickAI\Core\Governance\Plan_Proposal_Service;
use MagickAI\Core\Governance\Proposal_Repository;
use MagickAI\Core\Governance\Proposal_Service;
use MagickAI\Core\Media\Media_Derivative_Settings;
use MagickAI\Core\Rest\Apps_Controller;
use MagickAI\Core\Rest\Audit_Controller;
use MagickAI\Core\Rest\Capabilities_Controller;
use MagickAI\Core\Rest\Proposals_Controller;
use MagickAI\Core\Security\App_Authenticator;
use MagickAI\Core\Security\App_Key_Repository;
use MagickAI\Core\Security\App_Rate_Limiter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin container.
 */
final class Plugin {
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
	 * Media derivative settings.
	 *
	 * @var Media_Derivative_Settings|null
	 */
	private $media_derivative_settings = null;

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
		self::instance()->audit_repository()->install();
		self::instance()->app_key_repository()->install();
		self::instance()->app_rate_limiter()->install();
	}

	/**
	 * Registers plugin hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_init', array( $this->media_derivative_settings(), 'register' ) );
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );

		if ( is_admin() ) {
			( new Admin_Page( $this->ability_adapter(), $this->proposal_repository(), $this->audit_repository(), $this->proposal_service(), $this->app_key_repository(), $this->media_derivative_settings() ) )->register();
		}
	}

	/**
	 * Registers REST routes.
	 *
	 * @return void
	 */
	public function register_rest_routes(): void {
		( new Capabilities_Controller( $this->ability_adapter(), $this->audit_repository(), $this->app_authenticator() ) )->register_routes();
		( new Proposals_Controller(
			$this->proposal_service(),
			$this->proposal_repository(),
			$this->commit_preflight_service(),
			$this->plan_proposal_service(),
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
	 * Returns media derivative settings.
	 *
	 * @return Media_Derivative_Settings
	 */
	public function media_derivative_settings(): Media_Derivative_Settings {
		if ( null === $this->media_derivative_settings ) {
			$this->media_derivative_settings = new Media_Derivative_Settings();
		}

		return $this->media_derivative_settings;
	}
}
