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
use MagickAI\Core\Governance\Commit_Preflight_Service;
use MagickAI\Core\Governance\Proposal_Repository;
use MagickAI\Core\Governance\Proposal_Service;
use MagickAI\Core\Rest\Audit_Controller;
use MagickAI\Core\Rest\Capabilities_Controller;
use MagickAI\Core\Rest\Proposals_Controller;

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
	 * Commit preflight service.
	 *
	 * @var Commit_Preflight_Service|null
	 */
	private $commit_preflight_service = null;

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
	}

	/**
	 * Registers plugin hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );

		if ( is_admin() ) {
			( new Admin_Page( $this->ability_adapter(), $this->proposal_repository(), $this->audit_repository(), $this->proposal_service() ) )->register();
		}
	}

	/**
	 * Registers REST routes.
	 *
	 * @return void
	 */
	public function register_rest_routes(): void {
		( new Capabilities_Controller( $this->ability_adapter(), $this->audit_repository() ) )->register_routes();
		( new Proposals_Controller( $this->proposal_service(), $this->proposal_repository(), $this->commit_preflight_service() ) )->register_routes();
		( new Audit_Controller( $this->audit_repository() ) )->register_routes();
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
			$this->proposal_service = new Proposal_Service( $this->proposal_repository(), $this->audit_repository() );
		}

		return $this->proposal_service;
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
}
