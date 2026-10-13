<?php
/**
 * Minimal admin page.
 *
 * @package NpcinkGovernanceCore
 */

namespace Npcink\GovernanceCore\Admin;

use Npcink\GovernanceCore\Audit\Audit_Log_Repository;
use Npcink\GovernanceCore\Capabilities\Ability_Registry_Adapter;
use Npcink\GovernanceCore\Governance\Approval_Policy_Evaluator;
use Npcink\GovernanceCore\Governance\History_Cleanup_Service;
use Npcink\GovernanceCore\Governance\Proposal_Repository;
use Npcink\GovernanceCore\Governance\Proposal_Service;
use Npcink\GovernanceCore\Governance\Read_Request_Repository;
use Npcink\GovernanceCore\Security\App_Key_Repository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders a compact governance overview.
 */
final class Admin_Page {
	use Admin_Review_View;
	use Admin_Settings_And_Archive_View;
	use Admin_Proposal_Detail_View;
	use Admin_Audit_View;

	const PARENT_MENU_SLUG  = 'npcink-ai';
	const MENU_SLUG         = 'npcink-governance-core';
	const MENU_CAPABILITY   = 'manage_options';
	const REVIEW_PAGE_SIZE  = 10;
	const ARCHIVE_PAGE_SIZE = 10;
	const AUDIT_PAGE_SIZE   = 25;
	const APP_KEY_PAGE_SIZE = 10;
	const DATETIME_DISPLAY_FORMAT = 'Y-m-d H:i:s';

	/**
	 * Ability adapter.
	 *
	 * @var Ability_Registry_Adapter
	 */
	private $abilities;

	/**
	 * Proposal repository.
	 *
	 * @var Proposal_Repository
	 */
	private $proposals;

	/**
	 * Audit repository.
	 *
	 * @var Audit_Log_Repository
	 */
	private $audit;

	/**
	 * Proposal service.
	 *
	 * @var Proposal_Service
	 */
	private $service;

	/**
	 * App key repository.
	 *
	 * @var App_Key_Repository
	 */
	private $apps;

	/**
	 * History cleanup service.
	 *
	 * @var History_Cleanup_Service
	 */
	private $history_cleanup;

	/**
	 * Read request repository.
	 *
	 * @var Read_Request_Repository
	 */
	private $read_requests;

	/**
	 * Per-request cache of registry ability labels, or null before the first
	 * lookup.
	 *
	 * @var array<string,string>|null
	 */
	private $registry_label_cache;

	/**
	 * Constructor.
	 *
	 * @param Ability_Registry_Adapter $abilities Ability adapter.
	 * @param Proposal_Repository      $proposals Proposal repository.
	 * @param Audit_Log_Repository     $audit Audit repository.
	 * @param Proposal_Service         $service Proposal service.
	 * @param App_Key_Repository       $apps App key repository.
	 * @param History_Cleanup_Service  $history_cleanup History cleanup service.
	 * @param Read_Request_Repository  $read_requests Read request repository.
	 */
	public function __construct( Ability_Registry_Adapter $abilities, Proposal_Repository $proposals, Audit_Log_Repository $audit, Proposal_Service $service, App_Key_Repository $apps, History_Cleanup_Service $history_cleanup, Read_Request_Repository $read_requests ) {
		$this->abilities       = $abilities;
		$this->proposals       = $proposals;
		$this->audit           = $audit;
		$this->service         = $service;
		$this->apps            = $apps;
		$this->history_cleanup = $history_cleanup;
		$this->read_requests   = $read_requests;
	}

	/**
	 * Registers admin menu.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'add_menu' ), 10 );
		add_action( 'admin_post_npcink_governance_core_create_app_key', array( $this, 'handle_create_app_key' ) );
		add_action( 'admin_post_npcink_governance_core_revoke_app_key', array( $this, 'handle_revoke_app_key' ) );
		add_action( 'admin_post_npcink_governance_core_approve_proposal', array( $this, 'handle_approve' ) );
		add_action( 'admin_post_npcink_governance_core_reject_proposal', array( $this, 'handle_reject' ) );
		add_action( 'admin_post_npcink_governance_core_bulk_reject_proposals', array( $this, 'handle_bulk_reject' ) );
		add_action( 'admin_post_npcink_governance_core_archive_proposal', array( $this, 'handle_archive' ) );
		add_action( 'admin_post_npcink_governance_core_reopen_proposal', array( $this, 'handle_reopen' ) );
		add_action( 'admin_post_npcink_governance_core_update_approval_policy', array( $this, 'handle_update_approval_policy' ) );
		add_action( 'admin_post_npcink_governance_core_run_history_cleanup', array( $this, 'handle_run_history_cleanup' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Adds menu page.
	 *
	 * @return void
	 */
	public function add_menu(): void {
		$badge = $this->pending_badge_html();
		if ( $this->has_parent_menu() ) {
			add_submenu_page(
				self::PARENT_MENU_SLUG,
				__( 'Npcink Governance Core', 'npcink-governance-core' ),
				__( 'Core', 'npcink-governance-core' ) . $badge,
				self::MENU_CAPABILITY,
				self::MENU_SLUG,
				array( $this, 'render' ),
				10
			);
			return;
		}

		add_management_page(
			__( 'Npcink Governance Core', 'npcink-governance-core' ),
			__( 'Npcink Governance Core', 'npcink-governance-core' ) . $badge,
			self::MENU_CAPABILITY,
			self::MENU_SLUG,
			array( $this, 'render' )
		);
	}

	/**
	 * Returns the pending-count menu badge markup, or an empty string.
	 *
	 * The count is read-only and TTL-bounded so it matches what the review
	 * queue shows after stale-pending expiry, without writing audit events on
	 * unrelated admin page loads.
	 *
	 * @return string
	 */
	private function pending_badge_html(): string {
		if ( ! current_user_can( self::MENU_CAPABILITY ) ) {
			return '';
		}

		$count = $this->proposals->count_pending_within_ttl( Proposal_Service::PENDING_TTL_SECONDS );
		if ( $count < 1 ) {
			return '';
		}

		return ' <span class="awaiting-mod count-' . (int) $count . '"><span class="pending-count">' . esc_html( number_format_i18n( $count ) ) . '</span></span>';
	}

	/**
	 * Enqueues Core admin assets on Core-owned pages.
	 *
	 * @param string $hook_suffix Current admin hook suffix.
	 * @return void
	 */
	public function enqueue_assets( string $hook_suffix ): void {
		if ( false === strpos( $hook_suffix, self::PARENT_MENU_SLUG ) && false === strpos( $hook_suffix, self::MENU_SLUG ) ) {
			return;
		}

		wp_enqueue_style(
			'npcink-governance-core-admin',
			plugins_url( 'assets/admin.css', NPCINK_GOVERNANCE_CORE_FILE ),
			array(),
			NPCINK_GOVERNANCE_CORE_VERSION
		);

		wp_enqueue_script(
			'npcink-governance-core-admin',
			plugins_url( 'assets/admin.js', NPCINK_GOVERNANCE_CORE_FILE ),
			array(),
			NPCINK_GOVERNANCE_CORE_VERSION,
			true
		);
	}

	/**
	 * Returns whether another Npcink plugin already created the parent menu.
	 *
	 * @return bool
	 */
	private function has_parent_menu(): bool {
		global $menu;

		foreach ( (array) $menu as $item ) {
			if ( isset( $item[2] ) && self::PARENT_MENU_SLUG === $item[2] ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Renders admin page.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'npcink-governance-core' ) );
		}

		$this->service->expire_stale_pending();

		$review_filters = $this->review_filters_from_request();
		$review_page    = $this->page_from_request( 'review_page' );
		$pending_count  = $this->proposals->count_pending_filtered( $review_filters );
		$review_page    = $this->bounded_page( $pending_count, $review_page, self::REVIEW_PAGE_SIZE );
		$pending        = $this->proposals->list_pending_filtered( self::REVIEW_PAGE_SIZE, $this->offset_for_page( $review_page, self::REVIEW_PAGE_SIZE ), $review_filters );
		$selected_id    = $this->admin_query_text( 'proposal_id' );
		$selected       = '' !== $selected_id ? $this->find_proposal_for_lookup( $selected_id ) : null;
		$view           = $this->admin_query_key( 'view' );
		$message        = $this->admin_query_key( 'npcink_governance_core_message' );
		$error          = $this->admin_query_key( 'npcink_governance_core_error' );
		?>
		<div class="wrap">
			<h1><?php echo esc_html( 'Npcink Governance Core' ); ?></h1>
			<p><?php echo esc_html__( 'Review, approve, and audit AI-initiated WordPress operations.', 'npcink-governance-core' ); ?></p>

			<?php $bulk_outcome = $this->bulk_reject_outcome(); ?>
			<?php if ( null !== $bulk_outcome ) : ?>
				<div class="notice <?php echo esc_attr( $bulk_outcome['classes'] ); ?> is-dismissible">
					<p><?php echo esc_html( $bulk_outcome['text'] ); ?></p>
				</div>
			<?php elseif ( '' !== $message ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php echo esc_html( $this->message_text( $message ) ); ?></p>
				</div>
			<?php endif; ?>

			<?php if ( '' !== $error ) : ?>
				<div class="notice notice-error is-dismissible">
					<p><?php echo esc_html( $this->message_text( $error ) ); ?></p>
				</div>
			<?php endif; ?>

			<?php if ( null !== $selected ) : ?>
				<?php $this->render_proposal_detail( $selected ); ?>
			<?php elseif ( '' !== $selected_id ) : ?>
				<div class="notice notice-warning">
					<p><?php echo esc_html__( 'Selected proposal was not found.', 'npcink-governance-core' ); ?></p>
				</div>
				<?php $this->render_admin_tabs( 'review' ); ?>
				<?php $this->render_review_workbench( $pending, $pending_count, $review_page, $review_filters, $selected_id ); ?>
			<?php elseif ( 'audit' === $view ) : ?>
				<?php $audit_filters = $this->audit_filters_from_request(); ?>
				<?php $audit_total = $this->audit->count_filtered( $audit_filters ); ?>
				<?php $audit_filters = $this->bounded_audit_filters( $audit_filters, $audit_total ); ?>
				<?php $this->render_admin_tabs( 'audit' ); ?>
				<?php $this->render_activity_overview( $audit_filters ); ?>
				<?php $this->render_governance_audit( $this->audit->list_filtered( $audit_filters ), $audit_filters, $audit_total ); ?>
			<?php elseif ( 'archive' === $view ) : ?>
				<?php $this->render_admin_tabs( 'archive' ); ?>
				<?php $this->render_archive_view(); ?>
			<?php elseif ( 'settings' === $view ) : ?>
				<?php $this->render_admin_tabs( 'settings' ); ?>
				<?php $this->render_system_settings_page(); ?>
			<?php elseif ( 'app-keys' === $view ) : ?>
				<?php $this->render_admin_tabs( 'settings' ); ?>
				<?php $this->render_external_access(); ?>
			<?php else : ?>
				<?php $this->render_admin_tabs( 'review' ); ?>
				<?php $this->render_review_workbench( $pending, $pending_count, $review_page, $review_filters ); ?>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Renders the Core admin section tabs.
	 *
	 * @param string $active Active tab key.
	 * @return void
	 */
	private function render_admin_tabs( string $active ): void {
		$tabs = array(
			'review'   => array(
				'label' => __( 'Review Queue', 'npcink-governance-core' ),
				'url'   => $this->admin_url(),
			),
			'audit'    => array(
				'label' => __( 'Activity Log', 'npcink-governance-core' ),
				'url'   => $this->view_url( 'audit' ),
			),
			'archive'  => array(
				'label' => __( 'History', 'npcink-governance-core' ),
				'url'   => $this->view_url( 'archive' ),
			),
			'settings' => array(
				'label' => __( 'Settings', 'npcink-governance-core' ),
				'url'   => $this->view_url( 'settings' ),
			),
		);
		?>
		<nav class="npcink-ai-tabs npcink-governance-core-tabs" aria-label="<?php echo esc_attr__( 'Core admin sections', 'npcink-governance-core' ); ?>">
			<?php foreach ( $tabs as $key => $tab ) : ?>
				<a class="npcink-ai-tab <?php echo $active === $key ? 'npcink-ai-tab-active' : ''; ?>" href="<?php echo esc_url( (string) $tab['url'] ); ?>" <?php echo $active === $key ? 'aria-current="page"' : ''; ?>>
					<?php echo esc_html( (string) $tab['label'] ); ?>
				</a>
			<?php endforeach; ?>
		</nav>
		<?php
	}

	/**
	 * Handles approval form submission.
	 *
	 * @return void
	 */
	public function handle_approve(): void {
		$this->handle_decision( 'approve' );
	}

	/**
	 * Handles rejection form submission.
	 *
	 * @return void
	 */
	public function handle_reject(): void {
		$this->handle_decision( 'reject' );
	}

	/**
	 * Handles bulk rejection from the review queue.
	 *
	 * @return void
	 */
	public function handle_bulk_reject(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to update proposals.', 'npcink-governance-core' ) );
		}

		check_admin_referer( 'npcink_governance_core_bulk_reject_proposals' );

		$raw_proposal_ids = filter_input( INPUT_POST, 'proposal_ids', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
		$proposal_ids     = is_array( $raw_proposal_ids ) ? array_map( 'wp_unslash', $raw_proposal_ids ) : array();
		$proposal_ids = array_values( array_unique( array_filter( array_map( 'sanitize_text_field', $proposal_ids ) ) ) );
		$proposal_ids = array_slice( $proposal_ids, 0, 50 );
		$note         = isset( $_POST['note'] ) ? sanitize_textarea_field( wp_unslash( (string) $_POST['note'] ) ) : '';
		if ( '' === $note ) {
			$note = __( 'Rejected from bulk review.', 'npcink-governance-core' );
		}

		if ( empty( $proposal_ids ) ) {
			wp_safe_redirect( $this->admin_url( array( 'npcink_governance_core_error' => 'npcink_governance_core_bulk_reject_empty' ) ) );
			exit;
		}

		$rejected = 0;
		$failed   = 0;
		foreach ( $proposal_ids as $proposal_id ) {
			$result = $this->service->reject(
				$proposal_id,
				array(
					'note'        => $note,
					'source'      => 'admin_bulk',
					'bulk_action' => 'reject_selected',
				)
			);
			if ( is_wp_error( $result ) ) {
				++$failed;
				continue;
			}
			++$rejected;
		}

		$review_page = isset( $_POST['review_page'] ) ? absint( wp_unslash( (string) $_POST['review_page'] ) ) : 1;
		$args        = array(
			'npcink_governance_core_message' => 'bulk_rejected',
			'bulk_rejected'          => (string) $rejected,
		);
		if ( $review_page > 1 ) {
			$args['review_page'] = (string) $review_page;
		}
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- the bulk-reject and decision forms carry a wp_nonce_field verified above.
		foreach ( array( 'review_ability', 'review_age' ) as $filter_key ) {
			if ( isset( $_POST[ $filter_key ] ) ) {
				$filter_value = sanitize_text_field( wp_unslash( (string) $_POST[ $filter_key ] ) );
				if ( '' !== $filter_value ) {
					$args[ $filter_key ] = $filter_value;
				}
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing
		if ( $failed > 0 ) {
			$args['bulk_failed'] = (string) $failed;
		}

		wp_safe_redirect( $this->admin_url( $args ) );
		exit;
	}

	/**
	 * Handles archive form submission.
	 *
	 * @return void
	 */
	public function handle_archive(): void {
		$this->handle_lifecycle_action( 'archive' );
	}

	/**
	 * Handles reopen form submission.
	 *
	 * @return void
	 */
	public function handle_reopen(): void {
		$this->handle_lifecycle_action( 'reopen' );
	}

	/**
	 * Handles the lightweight approval policy mode form.
	 *
	 * @return void
	 */
	public function handle_update_approval_policy(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to update approval policy.', 'npcink-governance-core' ) );
		}

		check_admin_referer( 'npcink_governance_core_update_approval_policy' );

		$raw_mode           = filter_input( INPUT_POST, 'policy_mode', FILTER_UNSAFE_RAW );
		$raw_retention_days = filter_input( INPUT_POST, 'history_retention_days', FILTER_UNSAFE_RAW );
		$mode               = is_string( $raw_mode ) ? Approval_Policy_Evaluator::sanitize_policy_mode( wp_unslash( $raw_mode ) ) : Approval_Policy_Evaluator::MODE_MANUAL;
		$history_retention  = is_string( $raw_retention_days ) ? History_Cleanup_Service::sanitize_retention_days( wp_unslash( $raw_retention_days ) ) : History_Cleanup_Service::DEFAULT_HISTORY_RETENTION_DAYS;
		update_option( Approval_Policy_Evaluator::OPTION_POLICY_MODE, $mode, false );
		update_option( History_Cleanup_Service::OPTION_HISTORY_RETENTION_DAYS, $history_retention, false );

		$this->audit->record(
			'core.approval_policy_updated',
			array(
				'policy_mode'            => $mode,
				'history_retention_days' => $history_retention,
				'cleanup_scheduled'      => true,
				'commit_execution'       => false,
			)
		);

		wp_safe_redirect( $this->admin_url( array( 'npcink_governance_core_message' => 'settings_updated' ) ) );
		exit;
	}

	/**
	 * Handles manual history cleanup.
	 *
	 * @return void
	 */
	public function handle_run_history_cleanup(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to run history cleanup.', 'npcink-governance-core' ) );
		}

		check_admin_referer( 'npcink_governance_core_run_history_cleanup' );

		$result = $this->history_cleanup->run( 'manual' );
		if ( is_wp_error( $result ) ) {
			wp_safe_redirect( $this->admin_url( array( 'view' => 'settings', 'npcink_governance_core_error' => $result->get_error_code() ) ) );
			exit;
		}

		wp_safe_redirect(
			$this->admin_url(
				array(
					'view'                          => 'settings',
					'npcink_governance_core_message' => ! empty( $result['skipped'] ) ? 'history_cleanup_skipped' : 'history_cleanup_completed',
					'deleted_proposals'             => (string) (int) ( $result['deleted_proposals'] ?? 0 ),
					'deleted_app_keys'              => (string) (int) ( $result['deleted_app_keys'] ?? 0 ),
				)
			)
		);
		exit;
	}

	/**
	 * Handles app key creation form submission.
	 *
	 * @return void
	 */
	public function handle_create_app_key(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to create app keys.', 'npcink-governance-core' ) );
		}

		check_admin_referer( 'npcink_governance_core_create_app_key' );

		$scope_preset = isset( $_POST['scope_preset'] ) ? sanitize_key( wp_unslash( (string) $_POST['scope_preset'] ) ) : 'adapter_default';
		$raw_scopes   = $this->scopes_for_token_preset( $scope_preset );
		if ( 'custom' === $this->sanitize_token_scope_preset( $scope_preset ) ) {
			$raw_scopes = array();
			if ( isset( $_POST['scopes'] ) && is_array( $_POST['scopes'] ) ) {
				$raw_scopes = array_map(
					static function ( $scope ): string {
						return sanitize_text_field( (string) $scope );
					},
					(array) wp_unslash( $_POST['scopes'] ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Values are sanitized item-by-item above.
				);
			}
		}

		$raw_label  = isset( $_POST['app_label'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['app_label'] ) ) : '';
		$app        = $this->apps->create(
			array(
				'app_label'           => '' !== $raw_label ? $raw_label : 'External app',
				'caller_type'         => $this->sanitize_caller_type( isset( $_POST['caller_type'] ) ? sanitize_key( wp_unslash( (string) $_POST['caller_type'] ) ) : 'product_adapter' ),
				'scopes'              => $raw_scopes,
				'rate_limit'          => isset( $_POST['rate_limit'] ) ? absint( wp_unslash( (string) $_POST['rate_limit'] ) ) : App_Key_Repository::DEFAULT_RATE_LIMIT,
				'rate_window_seconds' => isset( $_POST['rate_window_seconds'] ) ? absint( wp_unslash( (string) $_POST['rate_window_seconds'] ) ) : App_Key_Repository::DEFAULT_RATE_WINDOW,
			)
		);

		if ( is_wp_error( $app ) ) {
			wp_safe_redirect( $this->admin_url( array( 'view' => 'app-keys', 'npcink_governance_core_error' => $app->get_error_code() ) ) );
			exit;
		}

		$event_id = $this->audit->record(
			'app.created',
			array(
				'app_id'      => (string) $app['app_id'],
				'key_id'      => (string) $app['key_id'],
				'caller_type' => (string) $app['caller_type'],
				'scopes'      => (array) $app['scopes'],
			)
		);

		if ( '' === $event_id ) {
			$this->apps->revoke_by_key_id( (string) $app['key_id'] );
			wp_safe_redirect( $this->admin_url( array( 'view' => 'app-keys', 'npcink_governance_core_error' => 'npcink_governance_core_app_audit_failed' ) ) );
			exit;
		}

		// The raw token never enters a URL or referrer; it crosses the redirect
		// through a short-lived per-user transient and renders once.
		set_transient(
			'npcink_governance_core_new_app_key_' . get_current_user_id(),
			array(
				'app_id'    => (string) $app['app_id'],
				'key_id'    => (string) $app['key_id'],
				'app_label' => (string) ( $app['app_label'] ?? '' ),
				'token'     => (string) ( $app['token'] ?? '' ),
			),
			5 * MINUTE_IN_SECONDS
		);

		wp_safe_redirect( $this->admin_url( array( 'view' => 'app-keys', 'npcink_governance_core_message' => 'app_key_created' ) ) );
		exit;
	}

	/**
	 * Handles app key revocation form submission.
	 *
	 * @return void
	 */
	public function handle_revoke_app_key(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to revoke app keys.', 'npcink-governance-core' ) );
		}

		$key_id = isset( $_POST['key_id'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['key_id'] ) ) : '';
		check_admin_referer( 'npcink_governance_core_revoke_app_key_' . $key_id );

		$app = '' !== $key_id ? $this->apps->find_by_key_id( $key_id ) : null;
		if ( null === $app || 'active' !== (string) ( $app['status'] ?? '' ) ) {
			wp_safe_redirect( $this->admin_url( array( 'view' => 'app-keys', 'npcink_governance_core_error' => 'npcink_governance_core_app_key_not_active' ) ) );
			exit;
		}

		if ( ! $this->apps->revoke_by_key_id( $key_id ) ) {
			wp_safe_redirect( $this->admin_url( array( 'view' => 'app-keys', 'npcink_governance_core_error' => 'npcink_governance_core_app_key_revoke_failed' ) ) );
			exit;
		}

		$this->audit->record(
			'app.revoked',
			array(
				'app_id'      => (string) $app['app_id'],
				'key_id'      => (string) $app['key_id'],
				'caller_type' => (string) $app['caller_type'],
			)
		);

		wp_safe_redirect( $this->admin_url( array( 'view' => 'app-keys', 'npcink_governance_core_message' => 'app_key_revoked' ) ) );
		exit;
	}

	/**
	 * Handles approval or rejection.
	 *
	 * @param string $decision Decision.
	 * @return void
	 */
	private function handle_decision( string $decision ): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to update proposals.', 'npcink-governance-core' ) );
		}

		$proposal_id = isset( $_POST['proposal_id'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['proposal_id'] ) ) : '';
		check_admin_referer( 'npcink_governance_core_decide_proposal_' . $proposal_id );

		$review_page = isset( $_POST['review_page'] ) ? absint( wp_unslash( (string) $_POST['review_page'] ) ) : 1;
		$review_page = max( 1, $review_page );
		$open_next   = ! isset( $_POST['open_next'] ) || '1' === sanitize_key( (string) wp_unslash( (string) $_POST['open_next'] ) );

		$note   = isset( $_POST['note'] ) ? sanitize_textarea_field( wp_unslash( (string) $_POST['note'] ) ) : '';
		$result = 'approve' === $decision ? $this->service->approve( $proposal_id, array( 'note' => $note ) ) : $this->service->reject( $proposal_id, array( 'note' => $note ) );

		$message = 'approve' === $decision ? 'approved' : 'rejected';

		if ( is_wp_error( $result ) ) {
			wp_safe_redirect(
				$this->admin_url(
					array(
						'proposal_id'                   => $proposal_id,
						'review_page'                   => (string) $review_page,
						'npcink_governance_core_error'  => $result->get_error_code(),
					)
				)
			);
			exit;
		}

		$next_pending_id = $open_next ? $this->next_pending_proposal_id() : '';
		if ( '' !== $next_pending_id ) {
			wp_safe_redirect(
				$this->admin_url(
					array(
						'proposal_id'                    => $next_pending_id,
						'review_page'                    => (string) $review_page,
						'npcink_governance_core_message' => $message,
					)
				)
			);
			exit;
		}

		wp_safe_redirect( $this->admin_url( $this->queue_redirect_args( $review_page, $message ) ) );
		exit;
	}

	/**
	 * Returns queue redirect args that preserve the current review page.
	 *
	 * @param int    $review_page Review queue page.
	 * @param string $message Message code.
	 * @return array<string,string>
	 */
	private function queue_redirect_args( int $review_page, string $message ): array {
		$args = array( 'npcink_governance_core_message' => $message );
		if ( $review_page > 1 ) {
			$args['review_page'] = (string) $review_page;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- the bulk-reject and decision forms carry a wp_nonce_field verified above.
		foreach ( array( 'review_ability', 'review_age' ) as $filter_key ) {
			if ( isset( $_POST[ $filter_key ] ) ) {
				$filter_value = sanitize_text_field( wp_unslash( (string) $_POST[ $filter_key ] ) );
				if ( '' !== $filter_value ) {
					$args[ $filter_key ] = $filter_value;
				}
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		return $args;
	}

	/**
	 * Returns the id of the next pending proposal in queue order, or an empty
	 * string when the pending queue is empty.
	 *
	 * @return string
	 */
	private function next_pending_proposal_id(): string {
		$rows = $this->proposals->list_recent_summaries( 1, Proposal_Repository::STATUS_PENDING );
		$row  = $rows[0] ?? array();

		return isset( $row['proposal_id'] ) ? (string) $row['proposal_id'] : '';
	}

	/**
	 * Handles proposal archive or reopen actions.
	 *
	 * @param string $action Lifecycle action.
	 * @return void
	 */
	private function handle_lifecycle_action( string $action ): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to update proposals.', 'npcink-governance-core' ) );
		}

		$proposal_id = isset( $_POST['proposal_id'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['proposal_id'] ) ) : '';
		check_admin_referer( 'npcink_governance_core_' . $action . '_proposal_' . $proposal_id );

		$result = 'archive' === $action
			? $this->service->archive( $proposal_id, array( 'source' => 'admin' ) )
			: $this->service->reopen( $proposal_id, array( 'source' => 'admin' ) );

		if ( is_wp_error( $result ) ) {
			wp_safe_redirect( $this->admin_url( array( 'view' => 'archive', 'npcink_governance_core_error' => $result->get_error_code() ) ) );
			exit;
		}

		wp_safe_redirect(
			$this->admin_url(
				array(
					'view'                   => 'archive',
					'npcink_governance_core_message' => 'archive' === $action ? 'archived' : 'reopened',
				)
			)
		);
		exit;
	}

	/**
	 * Returns current admin page number from query args.
	 *
	 * @param string $query_key Query arg key.
	 * @return int
	 */
	private function page_from_request( string $query_key ): int {
		return max( 1, $this->admin_query_absint( $query_key, 1 ) );
	}

	/**
	 * Returns offset for one-indexed page.
	 *
	 * @param int $page Current page.
	 * @param int $per_page Rows per page.
	 * @return int
	 */
	private function offset_for_page( int $page, int $per_page ): int {
		return max( 0, ( max( 1, $page ) - 1 ) * max( 1, $per_page ) );
	}

	/**
	 * Bounds a requested page to the available result set.
	 *
	 * @param int $total Total rows.
	 * @param int $page Requested page.
	 * @param int $per_page Rows per page.
	 * @return int
	 */
	private function bounded_page( int $total, int $page, int $per_page ): int {
		if ( $total <= 0 ) {
			return 1;
		}

		$total_pages = (int) ceil( $total / max( 1, $per_page ) );
		return max( 1, min( $page, $total_pages ) );
	}

	/**
	 * Returns bounded history retention choices.
	 *
	 * @return array<int,string>
	 */
	private function history_retention_day_options(): array {
		return History_Cleanup_Service::retention_day_options();
	}

	/**
	 * Sanitizes history retention days.
	 *
	 * @param string|int $days Raw retention days.
	 * @return int
	 */
	private function sanitize_history_retention_days( $days ): int {
		return History_Cleanup_Service::sanitize_retention_days( $days );
	}

	/**
	 * Returns stored history retention days.
	 *
	 * @return int
	 */
	private function stored_history_retention_days(): int {
		return History_Cleanup_Service::stored_retention_days();
	}

	/**
	 * Bounds audit filters to a valid page and offset.
	 *
	 * @param array<string,mixed> $filters Active filters.
	 * @param int                 $total Total matching rows.
	 * @return array<string,mixed>
	 */
	private function bounded_audit_filters( array $filters, int $total ): array {
		$limit             = (int) ( $filters['limit'] ?? self::AUDIT_PAGE_SIZE );
		$page              = $this->bounded_page( $total, (int) ( $filters['page'] ?? 1 ), $limit );
		$filters['page']   = $page;
		$filters['offset'] = $this->offset_for_page( $page, $limit );

		return $filters;
	}

	/**
	 * Returns a compact pagination summary.
	 *
	 * @param int $total Total rows.
	 * @param int $page Current page.
	 * @param int $per_page Rows per page.
	 * @return string
	 */
	private function pagination_summary( int $total, int $page, int $per_page ): string {
		if ( $total <= 0 ) {
			return __( 'No matching records.', 'npcink-governance-core' );
		}

		$start = $this->offset_for_page( $page, $per_page ) + 1;
		$end   = min( $total, $start + $per_page - 1 );

		return sprintf(
			/* translators: 1: first row number, 2: last row number, 3: total row count. */
			__( 'Showing %1$d-%2$d of %3$d.', 'npcink-governance-core' ),
			$start,
			$end,
			$total
		);
	}

	/**
	 * Returns a display label for proposal status.
	 *
	 * @param string $status Raw status.
	 * @return string
	 */
	private function status_label( string $status ): string {
		$labels = array(
			Proposal_Repository::STATUS_PENDING          => __( 'Needs review', 'npcink-governance-core' ),
			Proposal_Repository::STATUS_APPROVED         => __( 'Approved', 'npcink-governance-core' ),
			Proposal_Repository::STATUS_REJECTED         => __( 'Rejected', 'npcink-governance-core' ),
			Proposal_Repository::STATUS_EXPIRED          => __( 'Expired', 'npcink-governance-core' ),
			Proposal_Repository::STATUS_ARCHIVED         => __( 'Archived', 'npcink-governance-core' ),
			Proposal_Repository::STATUS_EXECUTED         => __( 'Executed', 'npcink-governance-core' ),
			Proposal_Repository::STATUS_EXECUTION_FAILED => __( 'Execution failed', 'npcink-governance-core' ),
		);

		return (string) ( $labels[ $status ] ?? $status );
	}

	/**
	 * Renders a proposal status badge.
	 *
	 * @param string $status Raw proposal status.
	 * @return void
	 */
	private function render_status_badge( string $status ): void {
		$class = 'npcink-governance-core-status-' . sanitize_html_class( str_replace( '_', '-', $status ) );
		?>
		<span class="npcink-governance-core-status-badge <?php echo esc_attr( $class ); ?>"><?php echo esc_html( $this->status_label( $status ) ); ?></span>
		<?php
	}

	/**
	 * Renders a proposal risk badge.
	 *
	 * @param string $risk_label Risk label.
	 * @return void
	 */
	private function render_risk_badge( string $risk_label ): void {
		$risk_label = '' !== trim( $risk_label ) ? trim( $risk_label ) : __( 'Not declared', 'npcink-governance-core' );
		$class      = 'npcink-governance-core-risk-' . sanitize_html_class( strtolower( str_replace( array( '_', ' ' ), '-', $risk_label ) ) );
		?>
		<span class="npcink-governance-core-risk-badge <?php echo esc_attr( $class ); ?>"><?php echo esc_html( $risk_label ); ?></span>
		<?php
	}

	/**
	 * Returns whether a proposal has risk metadata worth showing in the list.
	 *
	 * @param array<string,mixed> $proposal Proposal row.
	 * @return bool
	 */
	private function proposal_has_declared_risk( array $proposal ): bool {
		return __( 'Not declared', 'npcink-governance-core' ) !== $this->proposal_risk_label( $proposal );
	}

	/**
	 * Finds a proposal for admin lookup input.
	 *
	 * @param string $lookup_id Display id or full proposal id.
	 * @return array<string,mixed>|null
	 */
	private function find_proposal_for_lookup( string $lookup_id ): ?array {
		$lookup_id = sanitize_text_field( $lookup_id );
		if ( '' === $lookup_id ) {
			return null;
		}

		$proposal = $this->proposals->find( $lookup_id );
		if ( null !== $proposal ) {
			return $proposal;
		}

		return $this->proposals->find_by_display_id( $lookup_id );
	}

	/**
	 * Returns a compact risk label for a proposal.
	 *
	 * @param array<string,mixed> $proposal Proposal row.
	 * @return string
	 */
	private function proposal_risk_label( array $proposal ): string {
		$preview = is_array( $proposal['preview'] ?? null ) ? $proposal['preview'] : array();
		$risk    = $preview['risk'] ?? null;

		if ( is_array( $risk ) ) {
			$risk_label = (string) ( $risk['level'] ?? $risk['target_risk_level'] ?? '' );
			if ( '' !== $risk_label ) {
				return $risk_label;
			}
		} elseif ( is_scalar( $risk ) && '' !== (string) $risk ) {
			return (string) $risk;
		}

		if ( '' !== (string) ( $preview['risk_level'] ?? '' ) ) {
			return (string) $preview['risk_level'];
		}

		$article_workflow = is_array( $preview['article_workflow'] ?? null ) ? $preview['article_workflow'] : array();
		$risk_report      = is_array( $article_workflow['article_risk_report'] ?? null ) ? $article_workflow['article_risk_report'] : array();
		if ( '' !== (string) ( $risk_report['risk_level'] ?? '' ) ) {
			return (string) $risk_report['risk_level'];
		}

		return __( 'Not declared', 'npcink-governance-core' );
	}

	/**
	 * Returns a compact source label for a proposal.
	 *
	 * @param array<string,mixed> $proposal Proposal row.
	 * @return string
	 */
	private function proposal_source_label( array $proposal ): string {
		$trace = $this->pending_proposal_trace_parts( $proposal );
		if ( ! empty( $trace ) ) {
			return implode( ' · ', $trace );
		}

		$preview = is_array( $proposal['preview'] ?? null ) ? $proposal['preview'] : array();
		$source  = is_array( $preview['source'] ?? null ) ? $preview['source'] : array();
		$type    = (string) ( $source['type'] ?? '' );
		if ( '' !== $type ) {
			return $type;
		}

		return __( 'Direct request', 'npcink-governance-core' );
	}

	/**
	 * Returns a detail-page guidance sentence for the proposal status.
	 *
	 * @param array<string,mixed> $proposal Proposal row.
	 * @return string
	 */
	private function proposal_status_guidance( array $proposal ): string {
		switch ( (string) ( $proposal['status'] ?? '' ) ) {
			case Proposal_Repository::STATUS_PENDING:
				return __( 'Review context is ready before approval or rejection.', 'npcink-governance-core' );
			case Proposal_Repository::STATUS_APPROVED:
				return __( 'Approved; Adapter can continue after commit preflight.', 'npcink-governance-core' );
			case Proposal_Repository::STATUS_EXECUTED:
				return __( 'Executed outside Core after approval and preflight.', 'npcink-governance-core' );
			case Proposal_Repository::STATUS_EXECUTION_FAILED:
				return __( 'Execution failed after handoff; inspect audit evidence.', 'npcink-governance-core' );
			case Proposal_Repository::STATUS_REJECTED:
				return __( 'Rejected; no execution handoff should continue.', 'npcink-governance-core' );
			case Proposal_Repository::STATUS_EXPIRED:
			case Proposal_Repository::STATUS_ARCHIVED:
				return __( 'Historical record; no approval action is available.', 'npcink-governance-core' );
		}

		return __( 'Review the audit timeline for current state.', 'npcink-governance-core' );
	}

	/**
	 * Returns the non-pending outcome message.
	 *
	 * @param array<string,mixed> $proposal Proposal row.
	 * @return string
	 */
	private function proposal_outcome_message( array $proposal ): string {
		switch ( (string) ( $proposal['status'] ?? '' ) ) {
			case Proposal_Repository::STATUS_EXECUTED:
				return __( 'This proposal has already been executed outside Core. Use the audit timeline to verify approval, preflight, and execution evidence.', 'npcink-governance-core' );
			case Proposal_Repository::STATUS_APPROVED:
				return __( 'This proposal is approved. Use the audit timeline to verify the approval and preflight handoff.', 'npcink-governance-core' );
			case Proposal_Repository::STATUS_EXECUTION_FAILED:
				return __( 'This proposal reported an execution failure after handoff. Use the audit timeline and Adapter logs for follow-up.', 'npcink-governance-core' );
			case Proposal_Repository::STATUS_REJECTED:
				return __( 'This proposal was rejected and should not continue to execution.', 'npcink-governance-core' );
			case Proposal_Repository::STATUS_EXPIRED:
			case Proposal_Repository::STATUS_ARCHIVED:
				return __( 'This proposal is kept as a historical record and is not available for approval.', 'npcink-governance-core' );
		}

		return __( 'This proposal is no longer pending. Review the audit timeline for lifecycle evidence.', 'npcink-governance-core' );
	}

	/**
	 * Returns the proposal action count for detail summaries.
	 *
	 * @param array<string,mixed> $proposal Proposal row.
	 * @return int
	 */
	private function proposal_action_count( array $proposal ): int {
		$preview = is_array( $proposal['preview'] ?? null ) ? $proposal['preview'] : array();
		$input   = is_array( $proposal['input'] ?? null ) ? $proposal['input'] : array();
		$summary = is_array( $preview['batch_review_summary'] ?? null ) ? $preview['batch_review_summary'] : array();

		foreach ( array( $summary['action_count'] ?? null, $preview['action_count'] ?? null ) as $count ) {
			if ( is_numeric( $count ) && (int) $count > 0 ) {
				return (int) $count;
			}
		}

		if ( ! empty( $preview['actions'] ) && is_array( $preview['actions'] ) ) {
			return count( $preview['actions'] );
		}

		if ( ! empty( $input['write_actions'] ) && is_array( $input['write_actions'] ) ) {
			return count( $input['write_actions'] );
		}

		return 1;
	}

	/**
	 * Returns the warning count from preview metadata.
	 *
	 * @param array<string,mixed> $proposal Proposal row.
	 * @return int
	 */
	private function proposal_warning_count( array $proposal ): int {
		$preview = is_array( $proposal['preview'] ?? null ) ? $proposal['preview'] : array();
		$summary = is_array( $preview['batch_review_summary'] ?? null ) ? $preview['batch_review_summary'] : array();
		if ( is_numeric( $summary['warning_count'] ?? null ) ) {
			return (int) $summary['warning_count'];
		}

		$warnings = $preview['warnings'] ?? array();
		if ( is_array( $warnings ) ) {
			$count = 0;
			foreach ( $warnings as $key => $value ) {
				if ( is_string( $key ) && str_ends_with( $key, '_count' ) && is_numeric( $value ) ) {
					$count += (int) $value;
				}
			}
			if ( $count > 0 ) {
				return $count;
			}
		}

		return $this->count_nested_review_items( $warnings );
	}

	/**
	 * Returns the blocked item count from preview metadata.
	 *
	 * @param array<string,mixed> $proposal Proposal row.
	 * @return int
	 */
	private function proposal_blocked_count( array $proposal ): int {
		$preview = is_array( $proposal['preview'] ?? null ) ? $proposal['preview'] : array();
		$summary = is_array( $preview['batch_review_summary'] ?? null ) ? $preview['batch_review_summary'] : array();
		if ( is_numeric( $summary['blocked_count'] ?? null ) ) {
			return (int) $summary['blocked_count'];
		}

		return $this->count_nested_review_items( $preview['blocked_items'] ?? array() );
	}

	/**
	 * Returns the needs-input count from preview metadata.
	 *
	 * @param array<string,mixed> $proposal Proposal row.
	 * @return int
	 */
	private function proposal_needs_input_count( array $proposal ): int {
		$preview = is_array( $proposal['preview'] ?? null ) ? $proposal['preview'] : array();
		$summary = is_array( $preview['batch_review_summary'] ?? null ) ? $preview['batch_review_summary'] : array();
		if ( is_numeric( $summary['needs_input_count'] ?? null ) ) {
			return (int) $summary['needs_input_count'];
		}

		return $this->count_nested_review_items( $preview['needs_input'] ?? array() );
	}

	/**
	 * Returns the preflight blocker count from preview metadata.
	 *
	 * @param array<string,mixed> $proposal Proposal row.
	 * @return int
	 */
	private function proposal_preflight_blocker_count( array $proposal ): int {
		$preview = is_array( $proposal['preview'] ?? null ) ? $proposal['preview'] : array();
		return $this->count_nested_review_items( $preview['preflight_blockers'] ?? array() );
	}

	/**
	 * Counts meaningful nested review items.
	 *
	 * @param mixed $value Value.
	 * @return int
	 */
	private function count_nested_review_items( $value ): int {
		if ( empty( $value ) ) {
			return 0;
		}

		if ( is_scalar( $value ) ) {
			return '' !== trim( (string) $value ) ? 1 : 0;
		}

		if ( ! is_array( $value ) ) {
			return 0;
		}

		$count = 0;
		foreach ( $value as $key => $item ) {
			if ( is_string( $key ) && str_ends_with( $key, '_count' ) ) {
				continue;
			}
			$count += $this->count_nested_review_items( $item );
		}

		return $count;
	}

	/**
	 * Returns ordered batch action rows for a proposal.
	 *
	 * @param array<string,mixed> $proposal Proposal row.
	 * @return array<int,array{action_id:string,target_ability_id:string,reason:string,readiness:string,execution:string,dependency:string}>
	 */
	private function proposal_batch_action_rows( array $proposal ): array {
		$input           = is_array( $proposal['input'] ?? null ) ? $proposal['input'] : array();
		$preview         = is_array( $proposal['preview'] ?? null ) ? $proposal['preview'] : array();
		$write_actions   = is_array( $input['write_actions'] ?? null ) ? $input['write_actions'] : array();
		$preview_actions = is_array( $preview['actions'] ?? null ) ? $preview['actions'] : array();

		if ( empty( $write_actions ) && empty( $preview_actions ) ) {
			return array();
		}

		$preview_by_id = array();
		foreach ( $preview_actions as $preview_action ) {
			if ( ! is_array( $preview_action ) ) {
				continue;
			}
			$preview_action_id = (string) ( $preview_action['action_id'] ?? '' );
			if ( '' === $preview_action_id && is_array( $preview_action['preview'] ?? null ) ) {
				$preview_action_id = (string) ( $preview_action['preview']['action_id'] ?? '' );
			}
			if ( '' !== $preview_action_id ) {
				$preview_by_id[ $preview_action_id ] = $preview_action;
			}
		}

		$source_actions = ! empty( $write_actions ) ? $write_actions : $preview_actions;
		$rows           = array();
		foreach ( $source_actions as $index => $action ) {
			if ( ! is_array( $action ) ) {
				continue;
			}
			$action_preview = array();
			$action_id      = (string) ( $action['action_id'] ?? '' );
			if ( '' === $action_id && is_array( $action['preview'] ?? null ) ) {
				$action_id = (string) ( $action['preview']['action_id'] ?? '' );
			}
			if ( '' === $action_id ) {
				$action_id = 'action_' . (string) ( (int) $index + 1 );
			}

			if ( is_array( $preview_by_id[ $action_id ] ?? null ) ) {
				$action_preview = is_array( $preview_by_id[ $action_id ]['preview'] ?? null ) ? $preview_by_id[ $action_id ]['preview'] : $preview_by_id[ $action_id ];
			} elseif ( is_array( $action['preview'] ?? null ) ) {
				$action_preview = $action['preview'];
			}

			$depends_on = array_map( 'strval', (array) ( $action['depends_on'] ?? $action_preview['depends_on'] ?? array() ) );
			$rows[] = array(
				'action_id'         => $action_id,
				'target_ability_id' => (string) ( $action['target_ability_id'] ?? $action_preview['target_ability_id'] ?? '' ),
				'reason'            => (string) ( $action['reason'] ?? $action_preview['reason'] ?? '' ),
				'readiness'         => $this->proposal_action_readiness_label( $action, $action_preview ),
				'execution'         => $this->proposal_action_execution_label( $action, $action_preview ),
				'dependency'        => ! empty( $depends_on )
					? sprintf(
						/* translators: %s: dependency action ids. */
						__( 'Depends on: %s', 'npcink-governance-core' ),
						implode( ', ', $depends_on )
					)
					: __( 'No dependency', 'npcink-governance-core' ),
			);
		}

		return $rows;
	}

	/**
	 * Returns a compact action readiness label.
	 *
	 * @param array<string,mixed> $action Action input row.
	 * @param array<string,mixed> $preview Action preview row.
	 * @return string
	 */
	private function proposal_action_readiness_label( array $action, array $preview ): string {
		$needs_input        = $this->count_nested_review_items( $action['requires_input'] ?? $preview['needs_input'] ?? array() );
		$preflight_blockers = $this->count_nested_review_items( $action['preflight_blockers'] ?? $preview['preflight_blockers'] ?? array() );
		if ( $preflight_blockers > 0 ) {
			return __( 'Blocked', 'npcink-governance-core' );
		}
		if ( $needs_input > 0 ) {
			return __( 'Needs input', 'npcink-governance-core' );
		}
		if ( array_key_exists( 'proposal_ready', $action ) || array_key_exists( 'proposal_ready', $preview ) ) {
			return ! empty( $action['proposal_ready'] ?? $preview['proposal_ready'] ) ? __( 'Ready', 'npcink-governance-core' ) : __( 'Blocked', 'npcink-governance-core' );
		}

		return __( 'Ready', 'npcink-governance-core' );
	}

	/**
	 * Returns a compact action execution label.
	 *
	 * @param array<string,mixed> $action Action input row.
	 * @param array<string,mixed> $preview Action preview row.
	 * @return string
	 */
	private function proposal_action_execution_label( array $action, array $preview ): string {
		if ( ! empty( $action['commit_execution'] ?? $preview['commit_execution'] ) ) {
			return __( 'Commit requested', 'npcink-governance-core' );
		}

		if ( ! empty( $action['dry_run'] ?? $preview['dry_run'] ) ) {
			return __( 'Dry run; Core execution disabled', 'npcink-governance-core' );
		}

		return __( 'Core execution disabled', 'npcink-governance-core' );
	}

	/**
	 * Returns a shortened identifier.
	 *
	 * @param string $value Identifier.
	 * @return string
	 */
	private function compact_identifier( string $value ): string {
		$value = trim( $value );
		if ( strlen( $value ) <= 18 ) {
			return $value;
		}

		return substr( $value, 0, 8 ) . '...' . substr( $value, -4 );
	}

	/**
	 * Formats a stored UTC datetime for the site's WordPress timezone.
	 *
	 * @param string $datetime UTC datetime string.
	 * @return string
	 */
	private function display_datetime( string $datetime ): string {
		$datetime = trim( $datetime );
		if ( '' === $datetime ) {
			return '';
		}

		$has_timezone = (bool) preg_match( '/(?:Z|UTC|[+-]\d{2}:?\d{2})$/i', $datetime );
		$timestamp    = strtotime( $has_timezone ? $datetime : $datetime . ' UTC' );
		if ( false === $timestamp ) {
			return $datetime;
		}

		if ( function_exists( 'wp_date' ) ) {
			return wp_date( self::DATETIME_DISPLAY_FORMAT, $timestamp );
		}

		if ( function_exists( 'date_i18n' ) ) {
			return date_i18n( self::DATETIME_DISPLAY_FORMAT, $timestamp, true );
		}

		return gmdate( self::DATETIME_DISPLAY_FORMAT, $timestamp );
	}

	/**
	 * Returns a proposal age label.
	 *
	 * @param array<string,mixed> $proposal Proposal row.
	 * @return string
	 */
	private function proposal_age_label( array $proposal ): string {
		$created = strtotime( (string) ( $proposal['created_at'] ?? '' ) );
		if ( false === $created ) {
			return __( 'Unknown', 'npcink-governance-core' );
		}

		$seconds = max( 0, time() - $created );
		return $this->duration_label( $seconds );
	}

	/**
	 * Returns a proposal expiry label.
	 *
	 * @param array<string,mixed> $proposal Proposal row.
	 * @return string
	 */
	private function proposal_expiry_label( array $proposal ): string {
		$status  = (string) ( $proposal['status'] ?? '' );
		$created = strtotime( (string) ( $proposal['created_at'] ?? '' ) );
		if ( false === $created ) {
			return __( 'Unknown', 'npcink-governance-core' );
		}

		if ( Proposal_Repository::STATUS_PENDING !== $status ) {
			if ( Proposal_Repository::STATUS_EXPIRED === $status || Proposal_Repository::STATUS_ARCHIVED === $status ) {
				return __( 'Expired', 'npcink-governance-core' );
			}

			return __( 'Not applicable', 'npcink-governance-core' );
		}

		$expires_at = $created + $this->service->pending_ttl_seconds();
		$remaining  = $expires_at - time();

		if ( $remaining <= 0 ) {
			return __( 'Expired', 'npcink-governance-core' );
		}

		return sprintf(
			/* translators: %s: remaining duration. */
			__( 'Expires in %s', 'npcink-governance-core' ),
			$this->duration_label( $remaining )
		);
	}

	/**
	 * Returns a compact due label for the review row.
	 *
	 * @param array<string,mixed> $proposal Proposal row.
	 * @return string
	 */
	private function proposal_due_label( array $proposal ): string {
		$status  = (string) ( $proposal['status'] ?? '' );
		$created = strtotime( (string) ( $proposal['created_at'] ?? '' ) );
		if ( false === $created ) {
			return __( 'Unknown', 'npcink-governance-core' );
		}

		if ( Proposal_Repository::STATUS_PENDING !== $status ) {
			return $this->status_label( $status );
		}

		$remaining = ( $created + $this->service->pending_ttl_seconds() ) - time();
		if ( $remaining <= 0 ) {
			return __( 'Expired', 'npcink-governance-core' );
		}

		return sprintf(
			/* translators: %s: remaining duration. */
			__( '%s left', 'npcink-governance-core' ),
			$this->duration_label( $remaining )
		);
	}

	/**
	 * Returns the due-label CSS class for a proposal.
	 *
	 * @param array<string,mixed> $proposal Proposal row.
	 * @return string
	 */
	private function proposal_due_class( array $proposal ): string {
		$status  = (string) ( $proposal['status'] ?? '' );
		$created = strtotime( (string) ( $proposal['created_at'] ?? '' ) );
		if ( Proposal_Repository::STATUS_PENDING !== $status || false === $created ) {
			return 'npcink-governance-core-due-label';
		}

		$remaining = ( $created + $this->service->pending_ttl_seconds() ) - time();
		if ( $remaining <= 0 ) {
			return 'npcink-governance-core-due-label npcink-governance-core-due-expired';
		}

		if ( $remaining <= 6 * HOUR_IN_SECONDS ) {
			return 'npcink-governance-core-due-label npcink-governance-core-due-soon';
		}

		return 'npcink-governance-core-due-label';
	}

	/**
	 * Returns a compact duration label.
	 *
	 * @param int $seconds Duration in seconds.
	 * @return string
	 */
	private function duration_label( int $seconds ): string {
		if ( $seconds < HOUR_IN_SECONDS ) {
			return sprintf(
				/* translators: %d: minutes. */
				__( '%d min', 'npcink-governance-core' ),
				max( 1, (int) ceil( $seconds / MINUTE_IN_SECONDS ) )
			);
		}

		if ( $seconds < DAY_IN_SECONDS ) {
			return sprintf(
				/* translators: %d: hours. */
				__( '%d hr', 'npcink-governance-core' ),
				max( 1, (int) ceil( $seconds / HOUR_IN_SECONDS ) )
			);
		}

		return sprintf(
			/* translators: %d: days. */
			__( '%d days', 'npcink-governance-core' ),
			max( 1, (int) ceil( $seconds / DAY_IN_SECONDS ) )
		);
	}

	/**
	 * Returns detail URL.
	 *
	 * @param string $proposal_id Proposal id.
	 * @return string
	 */
	private function detail_url( string $proposal_id ): string {
		$args        = array( 'proposal_id' => $proposal_id );
		$review_page = $this->page_from_request( 'review_page' );
		if ( $review_page > 1 ) {
			$args['review_page'] = (string) $review_page;
		}

		return $this->admin_url( array_merge( $args, $this->review_filter_query_args() ) );
	}

	/**
	 * Returns the review queue URL, preserving the page number and filters.
	 *
	 * @param int $page Review queue page.
	 * @return string
	 */
	private function queue_url( int $page = 1 ): string {
		$args = array();
		if ( $page > 1 ) {
			$args['review_page'] = (string) $page;
		}

		return $this->admin_url( array_merge( $args, $this->review_filter_query_args() ) );
	}

	/**
	 * Returns view URL.
	 *
	 * @param string $view View name.
	 * @return string
	 */
	private function view_url( string $view ): string {
		return $this->admin_url( array( 'view' => $view ) );
	}

	/**
	 * Returns admin page URL.
	 *
	 * @param array<string,string> $args Query args.
	 * @return string
	 */
	private function admin_url( array $args = array() ): string {
		return add_query_arg(
			array_merge(
				array(
					'page' => self::MENU_SLUG,
				),
				$args
			),
			$this->admin_base_url()
		);
	}

	/**
	 * Returns the active parent URL for the Core page.
	 *
	 * @return string
	 */
	private function admin_base_url(): string {
		return admin_url( defined( 'NPCINK_TOOLBOX_VERSION' ) ? 'admin.php' : 'tools.php' );
	}

	/**
	 * Returns sanitized text from an admin query arg.
	 *
	 * @param string $key Query arg key.
	 * @param string $default Default value.
	 * @return string
	 */
	private function admin_query_text( string $key, string $default = '' ): string {
		$value = filter_input( INPUT_GET, $key, FILTER_UNSAFE_RAW );
		return is_string( $value ) ? sanitize_text_field( $value ) : $default;
	}

	/**
	 * Returns sanitized key text from an admin query arg.
	 *
	 * @param string $key Query arg key.
	 * @param string $default Default value.
	 * @return string
	 */
	private function admin_query_key( string $key, string $default = '' ): string {
		$value = filter_input( INPUT_GET, $key, FILTER_UNSAFE_RAW );
		return is_string( $value ) ? sanitize_key( $value ) : $default;
	}

	/**
	 * Returns an absolute integer from an admin query arg.
	 *
	 * @param string $key Query arg key.
	 * @param int    $default Default value.
	 * @return int
	 */
	private function admin_query_absint( string $key, int $default = 0 ): int {
		$value = filter_input( INPUT_GET, $key, FILTER_UNSAFE_RAW );
		return is_string( $value ) ? absint( $value ) : $default;
	}

	/**
	 * Returns a boolean from a nonce-verified admin query arg.
	 *
	 * @param string $key Query arg key.
	 * @return bool
	 */
	private function admin_query_bool( string $key ): bool {
		return '' !== $this->admin_query_text( $key );
	}

	/**
	 * Returns the Core admin stylesheet URL.
	 *
	 * @return string
	 */
	private function admin_stylesheet_url(): string {
		return plugins_url( 'assets/admin.css', NPCINK_GOVERNANCE_CORE_FILE );
	}

	/**
	 * Returns the bulk-rejection outcome notice, or null when the current
	 * request is not a bulk-rejection result.
	 *
	 * @return array{classes:string,text:string}|null
	 */
	private function bulk_reject_outcome(): ?array {
		$message = $this->admin_query_key( 'npcink_governance_core_message' );
		if ( 'bulk_rejected' !== $message ) {
			return null;
		}

		$rejected = $this->admin_query_absint( 'bulk_rejected', 0 );
		$failed   = $this->admin_query_absint( 'bulk_failed', 0 );

		if ( $failed > 0 ) {
			return array(
				'classes' => 'notice-error',
				'text'    => sprintf(
					/* translators: 1: rejected proposal count, 2: failed proposal count. */
					__( 'Rejected %1$d proposals, but %2$d could not be rejected; they may have expired or already been decided. Review the queue and retry the remaining items.', 'npcink-governance-core' ),
					$rejected,
					$failed
				),
			);
		}

		return array(
			'classes' => 'notice-success',
			'text'    => sprintf(
				/* translators: %d: rejected proposal count. */
				__( 'Rejected %d proposals.', 'npcink-governance-core' ),
				$rejected
			),
		);
	}

	/**
	 * Returns user-facing message text.
	 *
	 * @param string $code Message code.
	 * @return string
	 */
	private function message_text( string $code ): string {
		$messages = array(
			'approved'                                      => __( 'Proposal approved.', 'npcink-governance-core' ),
			'rejected'                                      => __( 'Proposal rejected.', 'npcink-governance-core' ),
			'bulk_rejected'                                 => __( 'Selected proposals rejected.', 'npcink-governance-core' ),
			'archived'                                      => __( 'Proposal archived.', 'npcink-governance-core' ),
			'reopened'                                      => __( 'Proposal reopened for review.', 'npcink-governance-core' ),
			'app_key_revoked'                               => __( 'App key disabled.', 'npcink-governance-core' ),
			'app_key_created'                               => __( 'Client access token created. Copy it below now; it is shown only once.', 'npcink-governance-core' ),
			'approval_policy_updated'                       => __( 'Approval policy mode updated.', 'npcink-governance-core' ),
			'settings_updated'                              => __( 'Settings updated.', 'npcink-governance-core' ),
			'history_cleanup_completed'                     => __( 'History cleanup completed.', 'npcink-governance-core' ),
			'history_cleanup_skipped'                       => __( 'History cleanup is disabled by the current retention policy.', 'npcink-governance-core' ),
			'npcink_governance_core_history_cleanup_audit_failed' => __( 'History cleanup could not be audited.', 'npcink-governance-core' ),
			'npcink_governance_core_history_cleanup_failed'  => __( 'History cleanup could not delete all selected records.', 'npcink-governance-core' ),
			'npcink_governance_core_history_cleanup_completion_audit_failed' => __( 'History cleanup completed, but the completion audit could not be stored.', 'npcink-governance-core' ),
			'npcink_governance_core_app_key_not_active'             => __( 'App key is missing or already disabled.', 'npcink-governance-core' ),
			'npcink_governance_core_app_key_revoke_failed'          => __( 'App key could not be disabled.', 'npcink-governance-core' ),
			'npcink_governance_core_proposal_not_found'             => __( 'Proposal was not found.', 'npcink-governance-core' ),
			'npcink_governance_core_proposal_expired'               => __( 'Proposal expired before a decision was made.', 'npcink-governance-core' ),
			'npcink_governance_core_proposal_archive_not_allowed'   => __( 'Only expired proposals can be archived.', 'npcink-governance-core' ),
			'npcink_governance_core_proposal_reopen_not_allowed'    => __( 'Only expired or archived proposals can be reopened.', 'npcink-governance-core' ),
			'npcink_governance_core_proposal_already_decided'       => __( 'Only pending proposals can be approved or rejected.', 'npcink-governance-core' ),
			'npcink_governance_core_proposal_transition_failed'     => __( 'Proposal status could not be updated.', 'npcink-governance-core' ),
			'npcink_governance_core_bulk_reject_empty'              => __( 'Select at least one pending proposal to reject.', 'npcink-governance-core' ),
			'npcink_governance_core_app_audit_failed'               => __( 'The client access token could not be created because its audit record could not be stored. No token was issued; please retry.', 'npcink-governance-core' ),
			'npcink_governance_core_app_scopes_empty'               => __( 'Client access tokens must include at least one valid scope.', 'npcink-governance-core' ),
			'npcink_governance_core_app_secret_hash_failed'         => __( 'The client access token secret could not be protected. Please retry.', 'npcink-governance-core' ),
			'npcink_governance_core_app_insert_failed'              => __( 'The client access token could not be stored. Please retry.', 'npcink-governance-core' ),
		);

		$fallback = __( 'Proposal action could not be completed.', 'npcink-governance-core' );
		if ( ! isset( $messages[ $code ] ) && '' !== $code ) {
			return sprintf(
				/* translators: 1: failure message, 2: machine-readable error code. */
				__( '%1$s Error code: %2$s', 'npcink-governance-core' ),
				$fallback,
				$code
			);
		}

		return (string) ( $messages[ $code ] ?? $fallback );
	}

	/**
	 * Returns minimal Core environment text.
	 *
	 * @param string $token App token or placeholder.
	 * @return string
	 */
	private function core_env_text( string $token ): string {
		$lines = array(
			'NPCINK_GOVERNANCE_CORE_BASE_URL=' . home_url(),
			'NPCINK_GOVERNANCE_CORE_APP_TOKEN=' . $token,
		);

		return implode( "\n", $lines );
	}
}
