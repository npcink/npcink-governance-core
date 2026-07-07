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
use Npcink\GovernanceCore\Governance\Operation_Classifier;
use Npcink\GovernanceCore\Governance\Proposal_Repository;
use Npcink\GovernanceCore\Governance\Proposal_Service;
use Npcink\GovernanceCore\Security\App_Key_Repository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders a compact governance overview.
 */
final class Admin_Page {
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
	 * Constructor.
	 *
	 * @param Ability_Registry_Adapter $abilities Ability adapter.
	 * @param Proposal_Repository      $proposals Proposal repository.
	 * @param Audit_Log_Repository     $audit Audit repository.
	 * @param Proposal_Service         $service Proposal service.
	 * @param App_Key_Repository       $apps App key repository.
	 * @param History_Cleanup_Service  $history_cleanup History cleanup service.
	 */
	public function __construct( Ability_Registry_Adapter $abilities, Proposal_Repository $proposals, Audit_Log_Repository $audit, Proposal_Service $service, App_Key_Repository $apps, History_Cleanup_Service $history_cleanup ) {
		$this->abilities       = $abilities;
		$this->proposals       = $proposals;
		$this->audit           = $audit;
		$this->service         = $service;
		$this->apps            = $apps;
		$this->history_cleanup = $history_cleanup;
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
		$this->ensure_parent_menu();

		add_submenu_page(
			self::PARENT_MENU_SLUG,
			__( 'Npcink Governance Core', 'npcink-governance-core' ),
			__( 'Core', 'npcink-governance-core' ),
			self::MENU_CAPABILITY,
			self::MENU_SLUG,
			array( $this, 'render' ),
			10
		);
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
	 * Ensures the shared Npcink parent menu exists.
	 *
	 * @return void
	 */
	private function ensure_parent_menu(): void {
		if ( $this->has_parent_menu() ) {
			return;
		}

		add_menu_page(
			__( 'Npcink AI', 'npcink-governance-core' ),
			__( 'Npcink AI', 'npcink-governance-core' ),
			self::MENU_CAPABILITY,
			self::PARENT_MENU_SLUG,
			array( $this, 'render_overview' ),
			'dashicons-superhero',
			58
		);

		add_submenu_page(
			self::PARENT_MENU_SLUG,
			__( 'Npcink AI Overview', 'npcink-governance-core' ),
			__( 'Overview', 'npcink-governance-core' ),
			self::MENU_CAPABILITY,
			self::PARENT_MENU_SLUG,
			array( $this, 'render_overview' ),
			0
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
	 * Renders the shared Npcink overview page.
	 *
	 * @return void
	 */
	public function render_overview(): void {
		if ( ! current_user_can( self::MENU_CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'npcink-governance-core' ) );
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Npcink AI', 'npcink-governance-core' ); ?></h1>
			<p><?php echo esc_html__( 'Local WordPress entry points for Npcink governance, connections, cloud connection pointers, and ability packages.', 'npcink-governance-core' ); ?></p>
			<h2><?php echo esc_html__( 'Installed Surfaces', 'npcink-governance-core' ); ?></h2>
			<table class="widefat striped npcink-governance-core-table-narrow npcink-governance-core-overview-table">
				<tbody>
					<?php
					$this->render_overview_row( __( 'Core', 'npcink-governance-core' ), __( 'Review proposals, approval decisions, commit preflight, audit, and client access tokens.', 'npcink-governance-core' ), self::MENU_SLUG );
					$this->render_overview_row( __( 'Adapter', 'npcink-governance-core' ), __( 'Connect OpenClaw through the Adapter surface.', 'npcink-governance-core' ), 'npcink-ai-client-adapter' );
					$this->render_overview_row( __( 'Abilities', 'npcink-governance-core' ), __( 'Verify WordPress Abilities API packages and demo ability controls.', 'npcink-governance-core' ), 'npcink-abilities-toolkit' );
					$this->render_overview_row( __( 'Workflow Toolbox', 'npcink-governance-core' ), __( 'Open fixed review-only workflow buttons for site checks, image handling, and governed handoffs.', 'npcink-governance-core' ), 'npcink-toolbox' );
					$this->render_overview_row( __( 'Cloud Addon', 'npcink-governance-core' ), __( 'Connect this site to cloud services without moving local control-plane truth.', 'npcink-governance-core' ), 'npcink-cloud-addon' );
					?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Renders one overview row.
	 *
	 * @param string $label       Row label.
	 * @param string $description Row description.
	 * @param string $slug        Menu page slug.
	 * @return void
	 */
	private function render_overview_row( string $label, string $description, string $slug ): void {
		?>
		<tr>
			<th scope="row"><?php echo esc_html( $label ); ?></th>
			<td><?php echo esc_html( $description ); ?></td>
			<td>
				<?php if ( $this->is_submenu_registered( $slug ) ) : ?>
					<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=' . $slug ) ); ?>"><?php echo esc_html__( 'Open', 'npcink-governance-core' ); ?></a>
				<?php else : ?>
					<span class="npcink-governance-core-muted"><?php echo esc_html__( 'Not installed', 'npcink-governance-core' ); ?></span>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Returns whether a Npcink submenu has been registered.
	 *
	 * @param string $slug Menu page slug.
	 * @return bool
	 */
	private function is_submenu_registered( string $slug ): bool {
		global $submenu;

		foreach ( (array) ( $submenu[ self::PARENT_MENU_SLUG ] ?? array() ) as $item ) {
			if ( isset( $item[2] ) && $slug === $item[2] ) {
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

		$review_page    = $this->page_from_request( 'review_page' );
		$pending_count  = $this->proposals->count_by_status( Proposal_Repository::STATUS_PENDING );
		$review_page    = $this->bounded_page( $pending_count, $review_page, self::REVIEW_PAGE_SIZE );
		$pending        = $this->proposals->list_recent( self::REVIEW_PAGE_SIZE, Proposal_Repository::STATUS_PENDING, $this->offset_for_page( $review_page, self::REVIEW_PAGE_SIZE ) );
		$selected_id    = $this->admin_query_text( 'proposal_id' );
		$selected       = '' !== $selected_id ? $this->find_proposal_for_lookup( $selected_id ) : null;
		$view           = $this->admin_query_key( 'view' );
		$message        = $this->admin_query_key( 'npcink_governance_core_message' );
		$error          = $this->admin_query_key( 'npcink_governance_core_error' );
		?>
		<div class="wrap">
			<h1><?php echo esc_html( 'Npcink Governance Core' ); ?></h1>
			<p><?php echo esc_html__( 'Review, approve, and audit AI-initiated WordPress operations.', 'npcink-governance-core' ); ?></p>

			<?php if ( '' !== $message ) : ?>
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
				<?php $this->render_review_workbench( $pending, $pending_count, $review_page, $selected_id ); ?>
			<?php elseif ( 'audit' === $view ) : ?>
				<?php $audit_filters = $this->audit_filters_from_request(); ?>
				<?php $audit_total = $this->audit->count_filtered( $audit_filters ); ?>
				<?php $audit_filters = $this->bounded_audit_filters( $audit_filters, $audit_total ); ?>
				<?php $this->render_admin_tabs( 'audit' ); ?>
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
				<?php $this->render_review_workbench( $pending, $pending_count, $review_page ); ?>
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
	 * Renders the default queue-first workbench.
	 *
	 * @param array<int,array<string,mixed>> $pending Pending proposals.
	 * @param int                            $pending_count Pending proposal count.
	 * @param int                            $page Current review page.
	 * @return void
	 */
	private function render_review_workbench( array $pending, int $pending_count, int $page, string $lookup_id = '' ): void {
		?>
		<?php $this->render_queue_summary( $pending_count ); ?>
		<?php $this->render_workbench_toolbar( $lookup_id ); ?>
		<?php $this->render_pending_proposals( $pending, $pending_count, $page ); ?>
		<?php
	}

	/**
	 * Renders compact queue state.
	 *
	 * @param int $pending_count Pending proposal count.
	 * @return void
	 */
	private function render_queue_summary( int $pending_count ): void {
		$approved_count         = $this->proposals->count_by_status( Proposal_Repository::STATUS_APPROVED );
		$execution_failed_count = $this->proposals->count_by_status( Proposal_Repository::STATUS_EXECUTION_FAILED );
		$activity_count         = $this->audit->count();
		?>
		<div class="npcink-governance-core-summary-strip npcink-governance-core-max-wide">
			<?php $this->render_summary_item( __( 'Needs review', 'npcink-governance-core' ), (string) $pending_count, __( 'Pending proposals waiting for an administrator decision.', 'npcink-governance-core' ), 'warning' ); ?>
			<?php $this->render_summary_item( __( 'Approved', 'npcink-governance-core' ), (string) $approved_count, __( 'Approved proposals waiting for Adapter preflight or execution record.', 'npcink-governance-core' ), 'ok' ); ?>
			<?php $this->render_summary_item( __( 'Execution failed', 'npcink-governance-core' ), (string) $execution_failed_count, __( 'Adapter-reported failures that need operator follow-up.', 'npcink-governance-core' ), $execution_failed_count > 0 ? 'error' : 'inactive' ); ?>
			<?php $this->render_summary_item( __( 'Audit events', 'npcink-governance-core' ), (string) $activity_count, __( 'Recorded Core governance events.', 'npcink-governance-core' ), 'neutral' ); ?>
		</div>
		<?php
	}

	/**
	 * Renders one queue summary item.
	 *
	 * @param string $label Item label.
	 * @param string $value Item value.
	 * @param string $detail Item detail.
	 * @param string $tone Visual tone.
	 * @return void
	 */
	private function render_summary_item( string $label, string $value, string $detail, string $tone ): void {
		?>
		<div class="npcink-governance-core-summary-item npcink-governance-core-summary-<?php echo esc_attr( sanitize_html_class( $tone ) ); ?>">
			<div class="npcink-governance-core-summary-label"><?php echo esc_html( $label ); ?></div>
			<div class="npcink-governance-core-summary-value"><?php echo esc_html( $value ); ?></div>
			<div class="npcink-governance-core-summary-detail"><?php echo esc_html( $detail ); ?></div>
		</div>
		<?php
	}

	/**
	 * Renders the top workbench utility row.
	 *
	 * @param string $lookup_id Current lookup id.
	 * @return void
	 */
	private function render_workbench_toolbar( string $lookup_id = '' ): void {
		?>
		<div class="npcink-governance-core-workbench-toolbar npcink-governance-core-max-wide">
			<?php $this->render_proposal_lookup( $lookup_id ); ?>
			<?php $this->render_recent_activity(); ?>
		</div>
		<?php
	}

	/**
	 * Renders read-only proposal id lookup.
	 *
	 * @param string $lookup_id Current lookup id.
	 * @return void
	 */
	private function render_proposal_lookup( string $lookup_id = '' ): void {
		?>
		<div class="npcink-governance-core-utility-panel">
			<div>
				<strong><?php echo esc_html__( 'Proposal lookup', 'npcink-governance-core' ); ?></strong>
				<span class="npcink-governance-core-muted"><?php echo esc_html__( 'Open a Core proposal by display ID or full proposal ID.', 'npcink-governance-core' ); ?></span>
			</div>
			<form class="npcink-governance-core-inline-actions" method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
				<input type="hidden" name="page" value="<?php echo esc_attr( self::MENU_SLUG ); ?>" />
				<label for="npcink-governance-core-proposal-lookup" class="screen-reader-text"><?php echo esc_html__( 'Proposal ID', 'npcink-governance-core' ); ?></label>
				<input id="npcink-governance-core-proposal-lookup" class="regular-text" type="text" name="proposal_id" value="<?php echo esc_attr( $lookup_id ); ?>" placeholder="P-1234ABCD-EF90" />
				<button type="submit" class="button"><?php echo esc_html__( 'Find proposal', 'npcink-governance-core' ); ?></button>
			</form>
		</div>
		<?php
	}

	/**
	 * Renders the primary governance settings form.
	 *
	 * @return void
	 */
	private function render_approval_policy_entry(): void {
		$stored_mode       = Approval_Policy_Evaluator::stored_policy_mode();
		$current           = Approval_Policy_Evaluator::sanitize_policy_mode( $stored_mode );
		$invalid_stored    = ! Approval_Policy_Evaluator::is_allowed_policy_mode( $stored_mode );
		$retention_days    = History_Cleanup_Service::stored_retention_days();
		$labels            = array(
			Approval_Policy_Evaluator::MODE_MANUAL          => __( 'Require approval for all', 'npcink-governance-core' ),
			Approval_Policy_Evaluator::MODE_SMART_GUARDED   => __( 'Smart approval', 'npcink-governance-core' ),
			Approval_Policy_Evaluator::MODE_DEV_ALLOW_ALL   => __( 'Allow all (development only)', 'npcink-governance-core' ),
		);
		?>
		<section class="npcink-governance-core-settings-section npcink-governance-core-max-wide" aria-labelledby="npcink-governance-core-approval-policy-heading">
			<h3 id="npcink-governance-core-approval-policy-heading"><?php echo esc_html__( 'Development Approval Policy', 'npcink-governance-core' ); ?></h3>
			<p class="npcink-governance-core-muted">
				<?php
				printf(
					/* translators: %s: current policy mode. */
					esc_html__( 'Current mode: %s', 'npcink-governance-core' ),
					esc_html( (string) ( $labels[ $current ] ?? $current ) )
				);
				?>
			</p>
			<?php if ( $invalid_stored ) : ?>
				<div class="notice notice-warning inline npcink-governance-core-policy-warning">
					<p>
						<?php
						printf(
							/* translators: 1: stored policy mode, 2: effective manual policy label. */
							esc_html__( 'Stored approval policy mode "%1$s" is no longer supported. Core is treating it as "%2$s" until you save one of the supported modes.', 'npcink-governance-core' ),
							esc_html( '' !== $stored_mode ? $stored_mode : __( 'empty', 'npcink-governance-core' ) ),
							esc_html( (string) ( $labels[ Approval_Policy_Evaluator::MODE_MANUAL ] ?? Approval_Policy_Evaluator::MODE_MANUAL ) )
						);
						?>
					</p>
				</div>
			<?php endif; ?>
			<form class="npcink-governance-core-form-spaced" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="npcink_governance_core_update_approval_policy" />
				<?php wp_nonce_field( 'npcink_governance_core_update_approval_policy' ); ?>
				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row"><label for="npcink-governance-core-approval-policy-mode"><?php echo esc_html__( 'Policy mode', 'npcink-governance-core' ); ?></label></th>
							<td>
								<select id="npcink-governance-core-approval-policy-mode" name="policy_mode">
									<?php foreach ( Approval_Policy_Evaluator::allowed_policy_modes() as $mode ) : ?>
										<option value="<?php echo esc_attr( $mode ); ?>" <?php selected( $current, $mode ); ?>>
											<?php echo esc_html( (string) ( $labels[ $mode ] ?? $mode ) ); ?>
										</option>
									<?php endforeach; ?>
								</select>
								<p class="description"><?php echo esc_html__( 'Smart approval only auto-approves trusted cleanup, draft creation, article audio, reviewed single-image replacement, and reviewed ALT-only media detail proposals. Allow all is local-development only and requires NPCINK_GOVERNANCE_CORE_ENABLE_DEV_ALLOW_ALL; commit preflight is still required and Core still does not execute writes.', 'npcink-governance-core' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="npcink-governance-core-history-retention-days"><?php echo esc_html__( 'History retention', 'npcink-governance-core' ); ?></label></th>
							<td>
								<select id="npcink-governance-core-history-retention-days" name="history_retention_days">
									<?php foreach ( History_Cleanup_Service::retention_day_options() as $days => $label ) : ?>
										<option value="<?php echo esc_attr( (string) $days ); ?>" <?php selected( $retention_days, (int) $days ); ?>>
											<?php echo esc_html( $label ); ?>
										</option>
									<?php endforeach; ?>
								</select>
								<p class="description"><?php echo esc_html__( 'Deletes expired or archived proposal history and revoked client access tokens older than the selected retention window. Each cleanup pass is bounded and audited.', 'npcink-governance-core' ); ?></p>
							</td>
						</tr>
					</tbody>
				</table>
				<p><button type="submit" class="button button-secondary"><?php echo esc_html__( 'Save settings', 'npcink-governance-core' ); ?></button></p>
			</form>
			<form class="npcink-governance-core-inline-actions" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="npcink_governance_core_run_history_cleanup" />
				<?php wp_nonce_field( 'npcink_governance_core_run_history_cleanup' ); ?>
				<button type="submit" class="button"><?php echo esc_html__( 'Run cleanup now', 'npcink-governance-core' ); ?></button>
				<span class="npcink-governance-core-muted"><?php echo esc_html__( 'Runs one bounded cleanup pass using the saved retention policy.', 'npcink-governance-core' ); ?></span>
			</form>
		</section>
		<?php
	}

	/**
	 * Renders low-frequency system settings as their own tab.
	 *
	 * @return void
	 */
	private function render_system_settings_page(): void {
		?>
		<h2><?php echo esc_html__( 'Settings', 'npcink-governance-core' ); ?></h2>
		<p class="npcink-governance-core-subtle"><?php echo esc_html__( 'Development approval policy, history retention, and trusted governance client access.', 'npcink-governance-core' ); ?></p>
		<div class="npcink-governance-core-system-settings">
			<?php $this->render_approval_policy_entry(); ?>
			<?php $this->render_client_access_token_entry(); ?>
		</div>
		<?php
	}

	/**
	 * Renders the default review queue.
	 *
	 * @param array<int,array<string,mixed>> $pending Pending proposals.
	 * @param int                            $total Total matching proposals.
	 * @param int                            $page Current review page.
	 * @return void
	 */
	private function render_pending_proposals( array $pending, int $total, int $page ): void {
		?>
		<h2><?php echo esc_html__( 'Pending requests', 'npcink-governance-core' ); ?></h2>
		<form class="npcink-governance-core-max-wide" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="npcink_governance_core_bulk_reject_proposals" />
			<?php wp_nonce_field( 'npcink_governance_core_bulk_reject_proposals' ); ?>
			<?php $this->render_review_queue_nav( $total, $page, ! empty( $pending ) ); ?>
			<table class="widefat striped npcink-governance-core-review-table">
				<thead>
					<tr>
						<td class="check-column">
							<input type="checkbox" data-npcink-bulk-toggle-all aria-label="<?php echo esc_attr__( 'Select all proposals on this page', 'npcink-governance-core' ); ?>" />
						</td>
						<th scope="col"><?php echo esc_html__( 'Request', 'npcink-governance-core' ); ?></th>
						<th scope="col"><?php echo esc_html__( 'Source', 'npcink-governance-core' ); ?></th>
						<th scope="col"><?php echo esc_html__( 'Status', 'npcink-governance-core' ); ?></th>
						<th scope="col"><?php echo esc_html__( 'Created', 'npcink-governance-core' ); ?></th>
						<th scope="col"><?php echo esc_html__( 'Details', 'npcink-governance-core' ); ?></th>
						<th scope="col"><?php echo esc_html__( 'Action', 'npcink-governance-core' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $pending ) ) : ?>
						<tr>
							<td colspan="7">
								<div class="npcink-governance-core-empty-state">
									<strong><?php echo esc_html__( 'No requests need review.', 'npcink-governance-core' ); ?></strong>
									<span><?php echo esc_html__( 'Find a proposal by ID, inspect recent activity, or open historical records when you need audit context.', 'npcink-governance-core' ); ?></span>
									<span class="npcink-governance-core-empty-actions">
										<a href="#npcink-governance-core-proposal-lookup"><?php echo esc_html__( 'Find proposal', 'npcink-governance-core' ); ?></a>
										<a href="<?php echo esc_url( $this->view_url( 'audit' ) ); ?>"><?php echo esc_html__( 'Open activity log', 'npcink-governance-core' ); ?></a>
										<a href="<?php echo esc_url( $this->view_url( 'archive' ) ); ?>"><?php echo esc_html__( 'Open history', 'npcink-governance-core' ); ?></a>
									</span>
								</div>
							</td>
						</tr>
					<?php endif; ?>
					<?php foreach ( $pending as $proposal ) : ?>
						<?php $proposal_id = (string) $proposal['proposal_id']; ?>
						<?php $display_id = $this->proposal_display_id( $proposal ); ?>
						<?php $details_id = 'npcink-governance-core-row-details-' . substr( md5( $proposal_id ), 0, 12 ); ?>
						<?php $source_summary = $this->proposal_source_summary_parts( $proposal ); ?>
						<?php $source_trace = implode( ' · ', $this->pending_proposal_trace_parts( $proposal ) ); ?>
						<?php
						$display_title = sprintf(
							/* translators: %s: full proposal id. */
							__( 'Full proposal ID: %s', 'npcink-governance-core' ),
							$proposal_id
						);
						?>
						<tr>
							<th scope="row" class="check-column">
								<input type="checkbox" name="proposal_ids[]" value="<?php echo esc_attr( $proposal_id ); ?>" aria-label="<?php echo esc_attr__( 'Select proposal', 'npcink-governance-core' ); ?>" />
							</th>
							<td class="npcink-governance-core-request-cell">
								<div class="npcink-governance-core-request-title"><?php echo esc_html( $this->proposal_request_label( $proposal ) ); ?></div>
								<div class="npcink-governance-core-request-meta">
									<a href="<?php echo esc_url( $this->detail_url( $proposal_id ) ); ?>" title="<?php echo esc_attr( $display_title ); ?>"><code class="npcink-governance-core-display-id"><?php echo esc_html( $display_id ); ?></code></a>
								</div>
							</td>
							<td class="npcink-governance-core-source-cell">
								<span class="npcink-governance-core-source-summary" title="<?php echo esc_attr( '' !== $source_trace ? $source_trace : $this->proposal_source_summary( $proposal ) ); ?>">
									<span class="npcink-governance-core-source-actor"><?php echo esc_html( $source_summary['actor'] ); ?></span>
								</span>
							</td>
							<td class="npcink-governance-core-status-cell">
								<?php $this->render_status_badge( (string) $proposal['status'] ); ?>
								<?php if ( $this->proposal_has_declared_risk( $proposal ) ) : ?>
									<?php $this->render_risk_badge( $this->proposal_risk_label( $proposal ) ); ?>
								<?php endif; ?>
							</td>
							<td class="npcink-governance-core-time-cell">
								<span><?php echo esc_html( $this->display_datetime( (string) $proposal['created_at'] ) ); ?></span><br />
								<span class="<?php echo esc_attr( $this->proposal_due_class( $proposal ) ); ?>"><?php echo esc_html( $this->proposal_due_label( $proposal ) ); ?></span>
							</td>
							<td class="npcink-governance-core-detail-cell">
								<button
									type="button"
									class="button button-small npcink-governance-core-row-details-toggle"
									aria-expanded="false"
									aria-controls="<?php echo esc_attr( $details_id ); ?>"
									data-npcink-details-target="<?php echo esc_attr( $details_id ); ?>"
									data-show-label="<?php echo esc_attr__( 'Details', 'npcink-governance-core' ); ?>"
									data-hide-label="<?php echo esc_attr__( 'Hide details', 'npcink-governance-core' ); ?>"
								>
									<?php echo esc_html__( 'Details', 'npcink-governance-core' ); ?>
								</button>
							</td>
							<td class="npcink-governance-core-action-cell">
								<a class="button button-secondary" href="<?php echo esc_url( $this->detail_url( $proposal_id ) ); ?>">
									<?php echo esc_html__( 'Review', 'npcink-governance-core' ); ?>
								</a>
							</td>
						</tr>
						<tr id="<?php echo esc_attr( $details_id ); ?>" class="npcink-governance-core-row-details-row" hidden>
							<td colspan="7">
								<?php $this->render_pending_proposal_technical_details( $proposal ); ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php if ( ! empty( $pending ) ) : ?>
				<details class="npcink-governance-core-disclosure npcink-governance-core-disclosure-top npcink-governance-core-bulk-disclosure" data-npcink-bulk-disclosure>
					<summary>
						<strong
							data-npcink-bulk-count-label
							data-default-label="<?php echo esc_attr__( 'Bulk actions', 'npcink-governance-core' ); ?>"
							<?php /* translators: %d: number of selected proposal requests. */ ?>
							data-selected-label="<?php echo esc_attr__( 'Selected requests: %d', 'npcink-governance-core' ); ?>"
						><?php echo esc_html__( 'Bulk actions', 'npcink-governance-core' ); ?></strong>
						<span
							class="npcink-governance-core-muted"
							data-npcink-bulk-help
							data-default-help="<?php echo esc_attr__( 'Reject selected requests when they are no longer needed.', 'npcink-governance-core' ); ?>"
							data-selected-help="<?php echo esc_attr__( 'Bulk rejection closes selected Core proposals without executing writes.', 'npcink-governance-core' ); ?>"
						><?php echo esc_html__( 'Reject selected requests when they are no longer needed.', 'npcink-governance-core' ); ?></span>
					</summary>
					<div class="npcink-governance-core-bulk-action-bar" data-npcink-bulk-action-bar>
						<div class="npcink-governance-core-bulk-action-main">
							<strong><?php echo esc_html__( 'Bulk rejection ready.', 'npcink-governance-core' ); ?></strong>
							<span class="npcink-governance-core-muted"><?php echo esc_html__( 'Review the selection before rejecting. Core records the rejection and does not execute writes.', 'npcink-governance-core' ); ?></span>
						</div>
						<details class="npcink-governance-core-bulk-note">
							<summary><?php echo esc_html__( 'Add rejection note', 'npcink-governance-core' ); ?></summary>
							<label class="npcink-governance-core-block-label">
								<?php echo esc_html__( 'Rejection note', 'npcink-governance-core' ); ?><br />
								<input type="text" class="large-text" name="note" value="" placeholder="<?php echo esc_attr__( 'Describe why these requests should be rejected.', 'npcink-governance-core' ); ?>" />
							</label>
						</details>
						<button type="button" class="button" data-npcink-bulk-clear>
							<?php echo esc_html__( 'Clear selection', 'npcink-governance-core' ); ?>
						</button>
						<button type="submit" class="button">
							<?php echo esc_html__( 'Reject selected', 'npcink-governance-core' ); ?>
						</button>
					</div>
				</details>
			<?php endif; ?>
			<?php $this->render_review_queue_nav( $total, $page, false ); ?>
		</form>
		<?php
	}

	/**
	 * Renders the WordPress-style review queue navigation row.
	 *
	 * @param int  $total Total matching proposals.
	 * @param int  $page Current page.
	 * @param bool $show_bulk Whether to show bulk action controls.
	 * @return void
	 */
	private function render_review_queue_nav( int $total, int $page, bool $show_bulk ): void {
		if ( $total <= 0 ) {
			return;
		}

		$this->render_table_nav(
			$total,
			$page,
			self::REVIEW_PAGE_SIZE,
			'review_page',
			array(),
			array(
				'show_bulk'  => $show_bulk,
				'show_range' => false,
			)
		);
	}

	/**
	 * Renders the WordPress-style activity log navigation row.
	 *
	 * @param int                  $total Total matching events.
	 * @param int                  $page Current page.
	 * @param int                  $per_page Rows per page.
	 * @param array<string,string> $args Preserved query args.
	 * @return void
	 */
	private function render_audit_table_nav( int $total, int $page, int $per_page, array $args ): void {
		$this->render_table_nav(
			$total,
			$page,
			$per_page,
			'audit_page',
			$args,
			array(
				'classes'    => 'npcink-governance-core-audit-list-nav npcink-governance-core-max-wide',
				'show_range' => true,
			)
		);
	}

	/**
	 * Renders a WordPress-style navigation row for Core admin tables.
	 *
	 * @param int                  $total Total matching rows.
	 * @param int                  $page Current page.
	 * @param int                  $per_page Rows per page.
	 * @param string               $page_arg Query arg storing page number.
	 * @param array<string,string> $args Preserved query args.
	 * @param array<string,mixed>  $options Navigation options.
	 * @return void
	 */
	private function render_table_nav( int $total, int $page, int $per_page, string $page_arg, array $args, array $options = array() ): void {
		$total_pages = max( 1, (int) ceil( $total / max( 1, $per_page ) ) );
		$page        = min( max( 1, $page ), $total_pages );
		$base_url    = remove_query_arg( $page_arg, $this->admin_url( $args ) );
		$classes      = trim( 'tablenav npcink-governance-core-list-nav ' . (string) ( $options['classes'] ?? '' ) );
		$show_bulk    = ! empty( $options['show_bulk'] );
		$show_range   = ! empty( $options['show_range'] );
		$left_classes = trim( 'alignleft actions ' . ( $show_bulk ? 'bulkactions ' : '' ) . 'npcink-governance-core-list-nav-bulk' );
		?>
		<div class="<?php echo esc_attr( $classes ); ?>">
			<div class="<?php echo esc_attr( $left_classes ); ?>">
				<?php if ( $show_bulk ) : ?>
					<label class="screen-reader-text" for="npcink-governance-core-bulk-action"><?php echo esc_html__( 'Bulk action', 'npcink-governance-core' ); ?></label>
					<select id="npcink-governance-core-bulk-action" data-npcink-bulk-select>
						<option value=""><?php echo esc_html__( 'Bulk actions', 'npcink-governance-core' ); ?></option>
						<option value="reject"><?php echo esc_html__( 'Reject selected', 'npcink-governance-core' ); ?></option>
					</select>
					<button type="button" class="button" data-npcink-bulk-apply><?php echo esc_html__( 'Apply', 'npcink-governance-core' ); ?></button>
				<?php elseif ( $show_range ) : ?>
					<span class="displaying-num"><?php echo esc_html( $this->pagination_summary( $total, $page, $per_page ) ); ?></span>
				<?php endif; ?>
			</div>
			<div class="tablenav-pages npcink-governance-core-list-nav-pages">
				<span class="displaying-num">
					<?php
					echo esc_html(
						sprintf(
							/* translators: %s: total item count. */
							__( '%s items', 'npcink-governance-core' ),
							number_format_i18n( $total )
						)
					);
					?>
				</span>
				<span class="pagination-links">
					<?php $this->render_table_page_button( 1, $page > 1, $base_url, $page_arg, __( 'First page', 'npcink-governance-core' ), '&laquo;' ); ?>
					<?php $this->render_table_page_button( max( 1, $page - 1 ), $page > 1, $base_url, $page_arg, __( 'Previous page', 'npcink-governance-core' ), '&lsaquo;' ); ?>
					<span class="paging-input">
						<?php
						echo esc_html(
							sprintf(
								/* translators: 1: current page, 2: total pages. */
								__( 'Page %1$d of %2$d', 'npcink-governance-core' ),
								$page,
								$total_pages
							)
						);
						?>
					</span>
					<?php $this->render_table_page_button( min( $total_pages, $page + 1 ), $page < $total_pages, $base_url, $page_arg, __( 'Next page', 'npcink-governance-core' ), '&rsaquo;' ); ?>
					<?php $this->render_table_page_button( $total_pages, $page < $total_pages, $base_url, $page_arg, __( 'Last page', 'npcink-governance-core' ), '&raquo;' ); ?>
				</span>
			</div>
		</div>
		<?php
	}

	/**
	 * Renders one table pagination button.
	 *
	 * @param int    $target_page Target page.
	 * @param bool   $enabled Whether the button should be clickable.
	 * @param string $base_url Base URL without the current page argument.
	 * @param string $page_arg Query arg storing page number.
	 * @param string $label Accessible label.
	 * @param string $symbol Visible symbol entity.
	 * @return void
	 */
	private function render_table_page_button( int $target_page, bool $enabled, string $base_url, string $page_arg, string $label, string $symbol ): void {
		if ( ! $enabled ) {
			?>
			<span class="tablenav-pages-navspan button disabled npcink-governance-core-page-button" aria-hidden="true"><?php echo wp_kses_post( $symbol ); ?></span>
			<?php
			return;
		}

		$url = add_query_arg( $page_arg, (string) $target_page, $base_url );
		?>
		<a class="button npcink-governance-core-page-button" href="<?php echo esc_url( $url ); ?>" aria-label="<?php echo esc_attr( $label ); ?>"><?php echo wp_kses_post( $symbol ); ?></a>
		<?php
	}

	/**
	 * Renders technical proposal fields behind a row disclosure.
	 *
	 * @param array<string,mixed> $proposal Proposal.
	 * @return void
	 */
	private function render_pending_proposal_technical_details( array $proposal ): void {
		$groups = $this->pending_proposal_technical_detail_rows( $proposal );
		?>
		<div class="npcink-governance-core-row-details-panel">
			<?php foreach ( $groups as $group ) : ?>
				<div class="npcink-governance-core-row-details-group">
					<div class="npcink-governance-core-row-details-heading"><?php echo esc_html( $group['label'] ); ?></div>
					<?php $this->render_pending_proposal_technical_detail_table( $group['rows'] ); ?>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Renders one row details group table.
	 *
	 * @param array<int,array{label:string,value:string,code:bool}> $rows Rows.
	 * @return void
	 */
	private function render_pending_proposal_technical_detail_table( array $rows ): void {
		?>
		<table class="widefat npcink-governance-core-row-details-table">
			<tbody>
				<?php foreach ( $rows as $row ) : ?>
					<tr>
						<th scope="row"><?php echo esc_html( $row['label'] ); ?></th>
						<td>
							<?php if ( ! empty( $row['code'] ) ) : ?>
								<code><?php echo esc_html( $row['value'] ); ?></code>
							<?php else : ?>
								<?php echo esc_html( $row['value'] ); ?>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Returns row-level technical details for the inline details inspector.
	 *
	 * @param array<string,mixed> $proposal Proposal.
	 * @return array<int,array{label:string,rows:array<int,array{label:string,value:string,code:bool}>}>
	 */
	private function pending_proposal_technical_detail_rows( array $proposal ): array {
		$caller = is_array( $proposal['caller'] ?? null ) ? $proposal['caller'] : array();
		$auth   = is_array( $caller['auth'] ?? null ) ? $caller['auth'] : array();
		$identity_rows = array(
			array(
				'label' => __( 'Display ID', 'npcink-governance-core' ),
				'value' => $this->proposal_display_id( $proposal ),
				'code'  => true,
			),
			array(
				'label' => __( 'Proposal ID', 'npcink-governance-core' ),
				'value' => (string) ( $proposal['proposal_id'] ?? '' ),
				'code'  => true,
			),
			array(
				'label' => __( 'Target ability', 'npcink-governance-core' ),
				'value' => (string) ( $proposal['ability_id'] ?? '' ),
				'code'  => true,
			),
		);

		$identity_optional_rows = array(
			array(
				'label' => __( 'Source', 'npcink-governance-core' ),
				'value' => $this->pending_proposal_source_value( $proposal ),
				'code'  => false,
			),
			array(
				'label' => __( 'Caller type', 'npcink-governance-core' ),
				'value' => (string) ( $auth['caller_type'] ?? $caller['caller_type'] ?? '' ),
				'code'  => true,
			),
			array(
				'label' => __( 'App ID', 'npcink-governance-core' ),
				'value' => (string) ( $auth['app_id'] ?? $caller['app_id'] ?? '' ),
				'code'  => true,
			),
		);

		$policy_rows = array(
			array(
				'label' => __( 'Created', 'npcink-governance-core' ),
				'value' => $this->display_datetime( (string) ( $proposal['created_at'] ?? '' ) ),
				'code'  => false,
			),
			array(
				'label' => __( 'Updated', 'npcink-governance-core' ),
				'value' => $this->display_datetime( (string) ( $proposal['updated_at'] ?? '' ) ),
				'code'  => false,
			),
			array(
				'label' => __( 'Policy decision', 'npcink-governance-core' ),
				'value' => (string) ( $proposal['policy_decision'] ?? '' ),
				'code'  => true,
			),
			array(
				'label' => __( 'Policy profile', 'npcink-governance-core' ),
				'value' => (string) ( $proposal['policy_profile'] ?? '' ),
				'code'  => true,
			),
			array(
				'label' => __( 'Policy reasons', 'npcink-governance-core' ),
				'value' => implode( ', ', array_map( 'strval', (array) ( $proposal['policy_reasons'] ?? array() ) ) ),
				'code'  => false,
			),
		);

		foreach ( $identity_optional_rows as $row ) {
			if ( '' !== trim( $row['value'] ) ) {
				$identity_rows[] = $row;
			}
		}

		$policy_rows = array_values(
			array_filter(
				$policy_rows,
				static function ( array $row ): bool {
					return '' !== trim( $row['value'] );
				}
			)
		);

		return array(
			array(
				'label' => __( 'Identity and source', 'npcink-governance-core' ),
				'rows'  => $identity_rows,
			),
			array(
				'label' => __( 'Time and policy', 'npcink-governance-core' ),
				'rows'  => $policy_rows,
			),
		);
	}

	/**
	 * Returns the raw source value for the row details inspector.
	 *
	 * @param array<string,mixed> $proposal Proposal.
	 * @return string
	 */
	private function pending_proposal_source_value( array $proposal ): string {
		$caller = is_array( $proposal['caller'] ?? null ) ? $proposal['caller'] : array();
		$source = trim( (string) ( $caller['source'] ?? '' ) );

		if ( '' !== $source ) {
			return $source;
		}

		$preview        = is_array( $proposal['preview'] ?? null ) ? $proposal['preview'] : array();
		$preview_source = is_array( $preview['source'] ?? null ) ? $preview['source'] : array();

		return trim( (string) ( $preview_source['type'] ?? '' ) );
	}

	/**
	 * Returns the user-facing request label for a proposal row.
	 *
	 * @param array<string,mixed> $proposal Proposal.
	 * @return string
	 */
	private function proposal_request_label( array $proposal ): string {
		$ability_id = (string) ( $proposal['ability_id'] ?? '' );
		$labels     = array(
			'npcink-abilities-toolkit/create-draft'                 => __( 'Create draft', 'npcink-governance-core' ),
			'npcink-abilities-toolkit/set-post-seo-meta'            => __( 'Update SEO fields', 'npcink-governance-core' ),
			'npcink-abilities-toolkit/approve-comment'              => __( 'Approve comment', 'npcink-governance-core' ),
			'npcink-abilities-toolkit/set-post-terms'               => __( 'Update taxonomy terms', 'npcink-governance-core' ),
			'npcink-abilities-toolkit/update-post'                  => __( 'Update post', 'npcink-governance-core' ),
			'npcink-abilities-toolkit/update-media-details'         => __( 'Update media details', 'npcink-governance-core' ),
			'npcink-abilities-toolkit/rename-media-file'            => __( 'Rename media file', 'npcink-governance-core' ),
			'npcink-abilities-toolkit/delete-media-permanently'     => __( 'Delete media permanently', 'npcink-governance-core' ),
			'npcink-abilities-toolkit/import-media-from-url'        => __( 'Import media', 'npcink-governance-core' ),
			'npcink-abilities-toolkit/set-featured-image'           => __( 'Set featured image', 'npcink-governance-core' ),
			'npcink-abilities-toolkit/patch-post-content'           => __( 'Update post content', 'npcink-governance-core' ),
		);

		if ( isset( $labels[ $ability_id ] ) ) {
			return (string) $labels[ $ability_id ];
		}

		$title = trim( (string) ( $proposal['title'] ?? '' ) );
		if ( '' !== $title && false === strpos( strtolower( $title ), 'pending quota proposal' ) ) {
			return $title;
		}

		return __( 'Review WordPress change', 'npcink-governance-core' );
	}

	/**
	 * Returns a short request label for the proposal detail summary.
	 *
	 * @param array<string,mixed> $proposal Proposal.
	 * @return string
	 */
	private function proposal_summary_request_label( array $proposal ): string {
		$action_count = $this->proposal_action_count( $proposal );
		if ( $action_count > 1 ) {
			return __( 'Batch proposal', 'npcink-governance-core' );
		}

		return $this->proposal_request_label( $proposal );
	}

	/**
	 * Returns compact request metadata for the proposal detail summary.
	 *
	 * @param array<string,mixed> $proposal Proposal.
	 * @return string
	 */
	private function proposal_summary_request_meta( array $proposal ): string {
		$action_count = $this->proposal_action_count( $proposal );
		if ( $action_count > 1 ) {
			return sprintf(
				/* translators: %d: number of proposal actions. */
				_n( '%d governed action', '%d governed actions', $action_count, 'npcink-governance-core' ),
				$action_count
			);
		}

		return __( 'Single governed action', 'npcink-governance-core' );
	}

	/**
	 * Builds compact source trace parts for a pending proposal.
	 *
	 * @param array<string,mixed> $proposal Proposal.
	 * @return array<int,string>
	 */
	private function pending_proposal_trace_parts( array $proposal ): array {
		$caller = is_array( $proposal['caller'] ?? null ) ? $proposal['caller'] : array();
		$auth   = is_array( $caller['auth'] ?? null ) ? $caller['auth'] : array();
		$parts  = array();

		$source = (string) ( $caller['source'] ?? '' );
		if ( '' !== $source ) {
			$parts[] = $source;
		}

		$plan_ability_id = (string) ( $caller['plan_ability_id'] ?? '' );
		if ( '' !== $plan_ability_id ) {
			$parts[] = sprintf(
				/* translators: %s: plan ability id. */
				__( 'plan %s', 'npcink-governance-core' ),
				$plan_ability_id
			);
		}

		$batch_id = (string) ( $caller['batch_id'] ?? '' );
		if ( '' !== $batch_id ) {
			$parts[] = sprintf(
				/* translators: %s: batch id. */
				__( 'batch %s', 'npcink-governance-core' ),
				$batch_id
			);
		}

		$action_id = (string) ( $caller['action_id'] ?? '' );
		if ( '' !== $action_id ) {
			$parts[] = sprintf(
				/* translators: %s: action id. */
				__( 'action %s', 'npcink-governance-core' ),
				$action_id
			);
		}

		$caller_type = (string) ( $auth['caller_type'] ?? $caller['caller_type'] ?? '' );
		if ( '' !== $caller_type ) {
			$parts[] = sprintf(
				/* translators: %s: caller type. */
				__( 'caller %s', 'npcink-governance-core' ),
				$caller_type
			);
		}

		$app_id = (string) ( $auth['app_id'] ?? $caller['app_id'] ?? '' );
		if ( '' !== $app_id ) {
			$parts[] = sprintf(
				/* translators: %s: app id. */
				__( 'app %s', 'npcink-governance-core' ),
				$app_id
			);
		}

		return array_slice( array_values( array_unique( $parts ) ), 0, 5 );
	}

	/**
	 * Returns a short source summary for the default review row.
	 *
	 * @param array<string,mixed> $proposal Proposal.
	 * @return string
	 */
	private function proposal_source_summary( array $proposal ): string {
		$summary = $this->proposal_source_summary_parts( $proposal );
		$parts   = array( $summary['actor'] );

		if ( '' !== $summary['context'] ) {
			$parts[] = $summary['context'];
		}

		return implode( ' · ', $parts );
	}

	/**
	 * Returns compact source summary parts for the default review row.
	 *
	 * @param array<string,mixed> $proposal Proposal.
	 * @return array{actor:string,context:string}
	 */
	private function proposal_source_summary_parts( array $proposal ): array {
		$caller = is_array( $proposal['caller'] ?? null ) ? $proposal['caller'] : array();
		$auth   = is_array( $caller['auth'] ?? null ) ? $caller['auth'] : array();
		$actor  = '';
		$parts  = array();

		$caller_type = (string) ( $auth['caller_type'] ?? $caller['caller_type'] ?? '' );
		if ( '' !== $caller_type ) {
			$actor = $this->source_actor_label( $caller_type );
		}

		$app_id = (string) ( $auth['app_id'] ?? $caller['app_id'] ?? '' );
		if ( '' !== $app_id ) {
			$parts[] = $this->compact_identifier( $app_id );
		}

		$source = (string) ( $caller['source'] ?? '' );
		if ( '' !== $source ) {
			$parts[] = $this->source_short_label( $source );
		}

		$plan_ability_id = (string) ( $caller['plan_ability_id'] ?? '' );
		if ( 'npcink-toolbox/build-nightly-inspection-review-plan' === $plan_ability_id ) {
			$actor   = __( 'Nightly Inspection', 'npcink-governance-core' );
			$parts[] = __( 'Morning Brief', 'npcink-governance-core' );
		}

		$preview        = is_array( $proposal['preview'] ?? null ) ? $proposal['preview'] : array();
		$preview_source = is_array( $preview['source'] ?? null ) ? $preview['source'] : array();
		$source_type    = (string) ( $preview_source['type'] ?? '' );
		if ( empty( $parts ) && '' !== $source_type ) {
			$parts[] = $this->source_short_label( $source_type );
		}

		if ( '' === $actor ) {
			$actor = __( 'Direct request', 'npcink-governance-core' );
		}

		return array(
			'actor'   => $actor,
			'context' => implode( ' · ', array_slice( array_values( array_unique( $parts ) ), 0, 2 ) ),
		);
	}

	/**
	 * Returns a stable human-facing proposal display id.
	 *
	 * @param array<string,mixed> $proposal Proposal.
	 * @return string
	 */
	private function proposal_display_id( array $proposal ): string {
		$display_id = sanitize_text_field( (string) ( $proposal['display_id'] ?? '' ) );
		if ( '' !== $display_id ) {
			return $display_id;
		}

		return Proposal_Repository::display_id_for_proposal_id( (string) ( $proposal['proposal_id'] ?? '' ) );
	}

	/**
	 * Returns a compact actor label.
	 *
	 * @param string $caller_type Caller type.
	 * @return string
	 */
	private function source_actor_label( string $caller_type ): string {
		$labels = array(
			'external_app'    => __( 'External app', 'npcink-governance-core' ),
			'product_adapter' => __( 'Product adapter', 'npcink-governance-core' ),
			'wp_admin'        => __( 'WordPress admin', 'npcink-governance-core' ),
			'admin'           => __( 'WordPress admin', 'npcink-governance-core' ),
		);

		return (string) ( $labels[ $caller_type ] ?? str_replace( '_', ' ', $caller_type ) );
	}

	/**
	 * Returns a compact source label.
	 *
	 * @param string $source Source value.
	 * @return string
	 */
	private function source_short_label( string $source ): string {
		if ( 0 === strpos( $source, 'pending-quota-smoke' ) ) {
			return __( 'Smoke quota', 'npcink-governance-core' );
		}

		if ( 'plan_to_proposal_batch' === $source ) {
			return __( 'Plan batch', 'npcink-governance-core' );
		}

		return $this->compact_identifier( $source );
	}

	/**
	 * Renders a short activity list for the default workbench.
	 *
	 * @return void
	 */
	private function render_recent_activity(): void {
		$events = $this->audit->list_recent( 1 );
		$event  = $events[0] ?? null;
		?>
		<div class="npcink-governance-core-utility-panel npcink-governance-core-utility-panel-stretch">
			<div>
				<strong><?php echo esc_html__( 'Recent Activity', 'npcink-governance-core' ); ?></strong>
				<span class="npcink-governance-core-muted"><?php echo esc_html__( 'Latest Core governance events. Full audit is in its own tab.', 'npcink-governance-core' ); ?></span>
			</div>
			<div class="npcink-governance-core-secondary-row-main">
				<?php if ( null === $event ) : ?>
					<?php echo esc_html__( 'No recent governance activity.', 'npcink-governance-core' ); ?>
				<?php else : ?>
					<?php $proposal_id = (string) ( $event['proposal_id'] ?? '' ); ?>
					<?php echo esc_html( $this->display_datetime( (string) $event['created_at'] ) ); ?>
					<code><?php echo esc_html( (string) $event['event_name'] ); ?></code>
					<?php if ( '' !== $proposal_id ) : ?>
						<a href="<?php echo esc_url( $this->detail_url( $proposal_id ) ); ?>"><code><?php echo esc_html( $proposal_id ); ?></code></a>
					<?php else : ?>
						<?php echo esc_html__( 'System', 'npcink-governance-core' ); ?>
					<?php endif; ?>
				<?php endif; ?>
			</div>
			<a href="<?php echo esc_url( $this->view_url( 'audit' ) ); ?>"><?php echo esc_html__( 'Open full audit', 'npcink-governance-core' ); ?></a>
		</div>
		<?php
	}

	/**
	 * Renders the low-frequency client access token entry.
	 *
	 * @return void
	 */
	private function render_client_access_token_entry(): void {
		$active_count = $this->apps->count( 'active' );
		$last_used    = $this->apps->latest_last_used_at();
		?>
		<section class="npcink-governance-core-settings-section npcink-governance-core-max-wide" aria-labelledby="npcink-governance-core-client-token-heading">
			<h3 id="npcink-governance-core-client-token-heading"><?php echo esc_html__( 'Client access tokens', 'npcink-governance-core' ); ?></h3>
			<p class="npcink-governance-core-muted"><?php echo esc_html__( 'Manage access tokens for trusted Adapter or internal governance clients.', 'npcink-governance-core' ); ?></p>
			<table class="widefat striped npcink-governance-core-table-spaced">
				<tbody>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Active tokens', 'npcink-governance-core' ); ?></th>
						<td><?php echo esc_html( (string) $active_count ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Last used', 'npcink-governance-core' ); ?></th>
						<td><?php echo esc_html( '' !== $last_used ? $this->display_datetime( $last_used ) : __( 'Never', 'npcink-governance-core' ) ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Action', 'npcink-governance-core' ); ?></th>
						<td><a class="button" href="<?php echo esc_url( $this->view_url( 'app-keys' ) ); ?>"><?php echo esc_html__( 'Manage access tokens', 'npcink-governance-core' ); ?></a></td>
					</tr>
				</tbody>
			</table>
		</section>
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

		$args = array(
			'npcink_governance_core_message' => 'bulk_rejected',
			'bulk_rejected'          => (string) $rejected,
		);
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

		$app        = $this->apps->create(
			array(
				'app_label'           => isset( $_POST['app_label'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['app_label'] ) ) : 'External app',
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

		status_header( 200 );
		nocache_headers();
		$this->render_created_app_key( $app );
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
	 * Renders client access token section backed by scoped app identity rows.
	 *
	 * @return void
	 */
	private function render_external_access(): void {
		$token_tab    = $this->admin_query_key( 'token_tab', 'tokens' );
		$token_tab    = in_array( $token_tab, array( 'tokens', 'revoked', 'issue' ), true ) ? $token_tab : 'tokens';
		$page         = $this->page_from_request( 'app_key_page' );
		$total        = $this->apps->count();
		$active_count = $this->apps->count( 'active' );
		$list_status  = 'revoked' === $token_tab ? 'revoked' : 'active';
		$list_count   = $this->apps->count( $list_status );
		$last_used    = $this->apps->latest_last_used_at();
		$page         = $this->bounded_page( $list_count, $page, self::APP_KEY_PAGE_SIZE );
		$apps         = $this->apps->list_recent( self::APP_KEY_PAGE_SIZE, $this->offset_for_page( $page, self::APP_KEY_PAGE_SIZE ), $list_status );
	?>
		<p><a href="<?php echo esc_url( $this->view_url( 'settings' ) ); ?>">&larr; <?php echo esc_html__( 'Back to settings', 'npcink-governance-core' ); ?></a></p>
		<h2><?php echo esc_html__( 'Client Access Tokens', 'npcink-governance-core' ); ?></h2>
		<p><?php echo esc_html__( 'Manage limited REST access tokens for trusted Adapter or internal governance clients. These are not model API keys or productized OpenClaw connection settings.', 'npcink-governance-core' ); ?></p>

		<div class="npcink-governance-core-summary-strip npcink-governance-core-token-summary">
			<div class="npcink-governance-core-summary-item npcink-governance-core-summary-ok">
				<span class="npcink-governance-core-summary-label"><?php echo esc_html__( 'Active tokens', 'npcink-governance-core' ); ?></span>
				<div class="npcink-governance-core-summary-value"><?php echo esc_html( (string) $active_count ); ?></div>
				<div class="npcink-governance-core-summary-detail"><?php echo esc_html__( 'Can authenticate scoped REST requests.', 'npcink-governance-core' ); ?></div>
			</div>
			<div class="npcink-governance-core-summary-item npcink-governance-core-summary-inactive">
				<span class="npcink-governance-core-summary-label"><?php echo esc_html__( 'Total tokens', 'npcink-governance-core' ); ?></span>
				<div class="npcink-governance-core-summary-value"><?php echo esc_html( (string) $total ); ?></div>
				<div class="npcink-governance-core-summary-detail"><?php echo esc_html__( 'Kept for audit attribution.', 'npcink-governance-core' ); ?></div>
			</div>
			<div class="npcink-governance-core-summary-item npcink-governance-core-summary-neutral">
				<span class="npcink-governance-core-summary-label"><?php echo esc_html__( 'Last used', 'npcink-governance-core' ); ?></span>
				<div class="npcink-governance-core-summary-value npcink-governance-core-summary-value-compact"><?php echo esc_html( '' !== $last_used ? $this->display_datetime( $last_used ) : __( 'Never', 'npcink-governance-core' ) ); ?></div>
				<div class="npcink-governance-core-summary-detail"><?php echo esc_html__( 'Latest successful token authentication.', 'npcink-governance-core' ); ?></div>
			</div>
		</div>

		<nav class="npcink-ai-tabs npcink-governance-core-subtabs" aria-label="<?php echo esc_attr__( 'Client access token sections', 'npcink-governance-core' ); ?>">
			<a class="npcink-ai-tab <?php echo 'tokens' === $token_tab ? 'npcink-ai-tab-active' : ''; ?>" href="<?php echo esc_url( $this->admin_url( array( 'view' => 'app-keys', 'token_tab' => 'tokens' ) ) ); ?>" <?php echo 'tokens' === $token_tab ? 'aria-current="page"' : ''; ?>>
				<?php echo esc_html__( 'Access tokens', 'npcink-governance-core' ); ?>
			</a>
			<a class="npcink-ai-tab <?php echo 'revoked' === $token_tab ? 'npcink-ai-tab-active' : ''; ?>" href="<?php echo esc_url( $this->admin_url( array( 'view' => 'app-keys', 'token_tab' => 'revoked' ) ) ); ?>" <?php echo 'revoked' === $token_tab ? 'aria-current="page"' : ''; ?>>
				<?php echo esc_html__( 'Revoked tokens', 'npcink-governance-core' ); ?>
			</a>
			<a class="npcink-ai-tab <?php echo 'issue' === $token_tab ? 'npcink-ai-tab-active' : ''; ?>" href="<?php echo esc_url( $this->admin_url( array( 'view' => 'app-keys', 'token_tab' => 'issue' ) ) ); ?>" <?php echo 'issue' === $token_tab ? 'aria-current="page"' : ''; ?>>
				<?php echo esc_html__( 'Issue token', 'npcink-governance-core' ); ?>
			</a>
		</nav>

		<?php if ( 'issue' === $token_tab ) : ?>
		<section class="npcink-governance-core-token-issue-panel npcink-governance-core-max-wide" aria-labelledby="npcink-governance-core-token-issue-heading">
			<div class="npcink-governance-core-token-issue-heading">
				<h3 id="npcink-governance-core-token-issue-heading"><?php echo esc_html__( 'Issue client access token', 'npcink-governance-core' ); ?></h3>
				<p class="npcink-governance-core-muted"><?php echo esc_html__( 'Issue a scoped token for a trusted governance client. Choose a purpose first; use advanced permissions only for custom clients.', 'npcink-governance-core' ); ?></p>
			</div>
			<form class="npcink-governance-core-token-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="npcink_governance_core_create_app_key" />
				<?php wp_nonce_field( 'npcink_governance_core_create_app_key' ); ?>
				<div class="npcink-governance-core-token-form-grid">
					<div class="npcink-governance-core-token-form-field">
						<label for="npcink-governance-core-app-label"><?php echo esc_html__( 'Client label', 'npcink-governance-core' ); ?></label>
						<input id="npcink-governance-core-app-label" class="regular-text" type="text" name="app_label" value="Adapter Client" />
					</div>
					<div class="npcink-governance-core-token-form-field">
						<label for="npcink-governance-core-caller-type"><?php echo esc_html__( 'Caller type', 'npcink-governance-core' ); ?></label>
						<select id="npcink-governance-core-caller-type" name="caller_type">
							<?php foreach ( $this->caller_type_options() as $value => $label ) : ?>
								<option value="<?php echo esc_attr( (string) $value ); ?>" <?php selected( 'product_adapter', (string) $value ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>
				<fieldset class="npcink-governance-core-token-purpose">
					<legend><?php echo esc_html__( 'Token purpose', 'npcink-governance-core' ); ?></legend>
					<p class="npcink-governance-core-muted"><?php echo esc_html__( 'Token purpose controls the default permission set. Custom clients can override permissions below.', 'npcink-governance-core' ); ?></p>
					<?php $this->render_token_scope_presets(); ?>
				</fieldset>
				<details class="npcink-governance-core-token-advanced">
					<summary><?php echo esc_html__( 'Advanced permissions and rate limit', 'npcink-governance-core' ); ?></summary>
					<div class="npcink-governance-core-token-advanced-grid">
						<div>
							<h4><?php echo esc_html__( 'Custom permissions', 'npcink-governance-core' ); ?></h4>
							<p class="npcink-governance-core-muted"><?php echo esc_html__( 'Choose Custom permissions above to use the checkboxes below.', 'npcink-governance-core' ); ?></p>
							<?php $this->render_scope_checkboxes(); ?>
						</div>
						<div>
							<h4><?php echo esc_html__( 'Rate limit', 'npcink-governance-core' ); ?></h4>
							<div class="npcink-governance-core-token-rate-fields">
								<label for="npcink-governance-core-rate-limit">
									<?php echo esc_html__( 'Requests', 'npcink-governance-core' ); ?>
									<input id="npcink-governance-core-rate-limit" type="number" min="1" max="10000" name="rate_limit" value="<?php echo esc_attr( (string) App_Key_Repository::DEFAULT_RATE_LIMIT ); ?>" />
								</label>
								<label for="npcink-governance-core-rate-window">
									<?php echo esc_html__( 'Window seconds', 'npcink-governance-core' ); ?>
									<input id="npcink-governance-core-rate-window" type="number" min="60" max="86400" name="rate_window_seconds" value="<?php echo esc_attr( (string) App_Key_Repository::DEFAULT_RATE_WINDOW ); ?>" />
								</label>
							</div>
						</div>
					</div>
				</details>
				<p class="submit"><button type="submit" class="button button-primary"><?php echo esc_html__( 'Issue client access token', 'npcink-governance-core' ); ?></button></p>
			</form>
		</section>
		<?php else : ?>

		<?php if ( 'revoked' === $token_tab ) : ?>
			<h3><?php echo esc_html__( 'Revoked tokens', 'npcink-governance-core' ); ?></h3>
			<p class="npcink-governance-core-muted"><?php echo esc_html__( 'Revoked tokens are shown for audit attribution only. They cannot authenticate scoped REST requests.', 'npcink-governance-core' ); ?></p>
		<?php else : ?>
			<h3><?php echo esc_html__( 'Access tokens', 'npcink-governance-core' ); ?></h3>
			<p class="npcink-governance-core-muted"><?php echo esc_html__( 'Only active tokens are shown here. Revoked tokens are available from the revoked-token subtab for audit attribution.', 'npcink-governance-core' ); ?></p>
		<?php endif; ?>
	<?php
	$this->render_table_nav(
		$list_count,
		$page,
		self::APP_KEY_PAGE_SIZE,
		'app_key_page',
		array( 'view' => 'app-keys', 'token_tab' => $token_tab ),
		array( 'show_range' => true )
	);
	?>
		<table class="widefat striped npcink-governance-core-token-table npcink-governance-core-max-wide">
			<thead>
				<tr>
					<th class="npcink-governance-core-token-client-cell" scope="col"><?php echo esc_html__( 'Client', 'npcink-governance-core' ); ?></th>
					<th class="npcink-governance-core-status-cell" scope="col"><?php echo esc_html__( 'Status', 'npcink-governance-core' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'Permissions', 'npcink-governance-core' ); ?></th>
					<th class="npcink-governance-core-time-cell" scope="col"><?php echo esc_html__( 'Last used', 'npcink-governance-core' ); ?></th>
					<th class="npcink-governance-core-action-cell" scope="col"><?php echo esc_html__( 'Action', 'npcink-governance-core' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $apps ) ) : ?>
					<tr><td colspan="5"><?php echo esc_html( 'revoked' === $token_tab ? __( 'No revoked access tokens.', 'npcink-governance-core' ) : __( 'No active access tokens.', 'npcink-governance-core' ) ); ?></td></tr>
				<?php endif; ?>
				<?php foreach ( $apps as $index => $app ) : ?>
					<?php $details_id = 'npcink-governance-core-token-details-' . (string) ( $index + 1 ); ?>
					<tr>
						<td>
							<div class="npcink-governance-core-request-title"><?php echo esc_html( (string) $app['app_label'] ); ?></div>
							<div class="npcink-governance-core-request-meta">
								<span><?php echo esc_html__( 'App', 'npcink-governance-core' ); ?> <code title="<?php echo esc_attr( (string) $app['app_id'] ); ?>"><?php echo esc_html( $this->compact_identifier( (string) $app['app_id'] ) ); ?></code></span>
							</div>
						</td>
						<td><?php $this->render_token_status_badge( $app ); ?></td>
						<td>
							<div><?php echo esc_html( $this->token_scope_summary( (array) $app['scopes'] ) ); ?></div>
							<details class="npcink-governance-core-audit-row-details" id="<?php echo esc_attr( $details_id ); ?>">
								<summary><?php echo esc_html__( 'Details', 'npcink-governance-core' ); ?></summary>
								<?php $this->render_token_detail_table( $app ); ?>
							</details>
						</td>
						<td>
							<?php if ( '' !== (string) $app['last_used_at'] ) : ?>
								<?php echo esc_html( $this->display_datetime( (string) $app['last_used_at'] ) ); ?>
							<?php else : ?>
								<span class="npcink-governance-core-muted"><?php echo esc_html__( 'Never', 'npcink-governance-core' ); ?></span>
							<?php endif; ?>
						</td>
						<td>
							<?php if ( 'active' === (string) $app['status'] ) : ?>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
									<input type="hidden" name="action" value="npcink_governance_core_revoke_app_key" />
									<input type="hidden" name="key_id" value="<?php echo esc_attr( (string) $app['key_id'] ); ?>" />
									<?php wp_nonce_field( 'npcink_governance_core_revoke_app_key_' . (string) $app['key_id'] ); ?>
									<button type="submit" class="button button-link-delete" onclick="return confirm('<?php echo esc_js( __( 'Revoke this access token immediately? Clients using this token will no longer be able to access Core.', 'npcink-governance-core' ) ); ?>');"><?php echo esc_html__( 'Revoke token', 'npcink-governance-core' ); ?></button>
								</form>
								<?php else : ?>
									<span class="npcink-governance-core-muted"><?php echo esc_html__( 'No action', 'npcink-governance-core' ); ?></span>
								<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
		<?php
		$this->render_table_nav(
			$list_count,
			$page,
			self::APP_KEY_PAGE_SIZE,
			'app_key_page',
			array( 'view' => 'app-keys', 'token_tab' => $token_tab ),
			array( 'show_range' => true )
		);
		?>
		<?php endif; ?>
		<?php
	}

	/**
	 * Renders historical proposals.
	 *
	 * @return void
	 */
	private function render_archive_view(): void {
		$page      = $this->page_from_request( 'archive_page' );
		$statuses  = array(
			Proposal_Repository::STATUS_EXPIRED,
			Proposal_Repository::STATUS_ARCHIVED,
		);
		$total     = $this->proposals->count_by_statuses( $statuses );
		$page      = $this->bounded_page( $total, $page, self::ARCHIVE_PAGE_SIZE );
		$proposals = $this->proposals->list_by_statuses(
			$statuses,
			self::ARCHIVE_PAGE_SIZE,
			$this->offset_for_page( $page, self::ARCHIVE_PAGE_SIZE )
		);
		?>
		<h2><?php echo esc_html__( 'History', 'npcink-governance-core' ); ?></h2>
		<p><?php echo esc_html__( 'Expired requests are kept as historical records after they leave the active review queue.', 'npcink-governance-core' ); ?></p>
		<?php
		$this->render_table_nav(
			$total,
			$page,
			self::ARCHIVE_PAGE_SIZE,
			'archive_page',
			array(
				'view' => 'archive',
			),
			array(
				'show_range' => true,
			)
		);
		?>
		<table class="widefat striped npcink-governance-core-archive-table" style="max-width: 1100px;">
			<thead>
				<tr>
					<th scope="col"><?php echo esc_html__( 'Proposal', 'npcink-governance-core' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'Status', 'npcink-governance-core' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'Updated', 'npcink-governance-core' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'Details', 'npcink-governance-core' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $proposals ) ) : ?>
					<tr>
						<td colspan="4"><?php echo esc_html__( 'No historical proposals.', 'npcink-governance-core' ); ?></td>
					</tr>
				<?php endif; ?>
				<?php foreach ( $proposals as $proposal ) : ?>
					<?php $proposal_id = (string) $proposal['proposal_id']; ?>
					<?php $details_id = 'npcink-governance-core-archive-details-' . substr( md5( $proposal_id ), 0, 12 ); ?>
					<?php $display_title = sprintf(
						/* translators: %s: full proposal id. */
						__( 'Full proposal ID: %s', 'npcink-governance-core' ),
						$proposal_id
					); ?>
					<tr>
						<td class="npcink-governance-core-request-cell">
							<div class="npcink-governance-core-request-title">
								<a href="<?php echo esc_url( $this->detail_url( $proposal_id ) ); ?>">
									<?php echo esc_html( $this->proposal_request_label( $proposal ) ); ?>
								</a>
							</div>
							<div class="npcink-governance-core-request-meta">
								<a href="<?php echo esc_url( $this->detail_url( $proposal_id ) ); ?>" title="<?php echo esc_attr( $display_title ); ?>"><code class="npcink-governance-core-display-id"><?php echo esc_html( $this->proposal_display_id( $proposal ) ); ?></code></a>
							</div>
						</td>
						<td><?php $this->render_status_badge( (string) $proposal['status'] ); ?></td>
						<td class="npcink-governance-core-time-cell">
							<span><?php echo esc_html( $this->display_datetime( (string) $proposal['updated_at'] ) ); ?></span><br />
							<span class="npcink-governance-core-muted"><?php echo esc_html( $this->proposal_age_label( $proposal ) ); ?></span>
						</td>
						<td class="npcink-governance-core-detail-cell">
							<button
								type="button"
								class="button button-small npcink-governance-core-row-details-toggle"
								aria-expanded="false"
								aria-controls="<?php echo esc_attr( $details_id ); ?>"
								data-npcink-details-target="<?php echo esc_attr( $details_id ); ?>"
								data-show-label="<?php echo esc_attr__( 'Details', 'npcink-governance-core' ); ?>"
								data-hide-label="<?php echo esc_attr__( 'Hide details', 'npcink-governance-core' ); ?>"
							>
								<?php echo esc_html__( 'Details', 'npcink-governance-core' ); ?>
							</button>
						</td>
					</tr>
					<tr id="<?php echo esc_attr( $details_id ); ?>" class="npcink-governance-core-row-details-row" hidden>
						<td colspan="4">
							<?php $this->render_pending_proposal_technical_details( $proposal ); ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
		$this->render_table_nav(
			$total,
			$page,
			self::ARCHIVE_PAGE_SIZE,
			'archive_page',
			array(
				'view' => 'archive',
			),
			array( 'show_range' => true )
		);
		?>
		<?php
	}

	/**
	 * Renders token status.
	 *
	 * @param array<string,mixed> $app App row.
	 * @return void
	 */
	private function render_token_status_badge( array $app ): void {
		$status = (string) ( $app['status'] ?? '' );
		if ( 'active' === $status && $this->apps->is_expired( $app ) ) {
			$status = 'expired';
		}

		$labels = array(
			'active'  => __( 'Active', 'npcink-governance-core' ),
			'expired' => __( 'Expired', 'npcink-governance-core' ),
			'revoked' => __( 'Revoked', 'npcink-governance-core' ),
		);
		$label  = (string) ( $labels[ $status ] ?? ucfirst( $status ) );
		$class  = 'npcink-governance-core-status-' . sanitize_html_class( $status );
		?>
		<span class="npcink-governance-core-status-badge <?php echo esc_attr( $class ); ?>"><?php echo esc_html( $label ); ?></span>
		<?php
	}

	/**
	 * Returns compact scope summary text.
	 *
	 * @param array<int|string,mixed> $scopes Scopes.
	 * @return string
	 */
	private function token_scope_summary( array $scopes ): string {
		$scopes = $this->apps->sanitize_scopes( $scopes );
		$count  = count( $scopes );
		$summary = sprintf(
			/* translators: %d: permission scope count. */
			_n( '%d permission', '%d permissions', $count, 'npcink-governance-core' ),
			$count
		);

		$high_signal_scopes = array_values(
			array_filter(
				array(
					in_array( 'proposals:approve', $scopes, true ) ? __( 'approval', 'npcink-governance-core' ) : '',
					in_array( 'commit:record_execution', $scopes, true ) ? __( 'execution record', 'npcink-governance-core' ) : '',
					in_array( 'audit:read', $scopes, true ) ? __( 'audit read', 'npcink-governance-core' ) : '',
				)
			)
		);

		if ( ! empty( $high_signal_scopes ) ) {
			$summary .= ' · ' . sprintf(
				/* translators: %s: comma-separated sensitive scope labels. */
				__( 'Includes %s', 'npcink-governance-core' ),
				implode( ', ', $high_signal_scopes )
			);
		}

		return $summary;
	}

	/**
	 * Renders token technical details.
	 *
	 * @param array<string,mixed> $app App row.
	 * @return void
	 */
	private function render_token_detail_table( array $app ): void {
		$scopes = array();
		foreach ( (array) ( $app['scopes'] ?? array() ) as $scope ) {
			$scope    = (string) $scope;
			$scopes[] = sprintf( '%1$s (%2$s)', $this->scope_label( $scope ), $scope );
		}
		?>
		<table class="widefat npcink-governance-core-row-details-table npcink-governance-core-token-detail-table">
			<tbody>
				<tr>
					<th scope="row"><?php echo esc_html__( 'App ID', 'npcink-governance-core' ); ?></th>
					<td><code><?php echo esc_html( (string) $app['app_id'] ); ?></code></td>
				</tr>
				<tr>
					<th scope="row"><?php echo esc_html__( 'Key ID', 'npcink-governance-core' ); ?></th>
					<td><code><?php echo esc_html( (string) $app['key_id'] ); ?></code></td>
				</tr>
				<tr>
					<th scope="row"><?php echo esc_html__( 'Caller type', 'npcink-governance-core' ); ?></th>
					<td><code><?php echo esc_html( (string) $app['caller_type'] ); ?></code></td>
				</tr>
				<tr>
					<th scope="row"><?php echo esc_html__( 'Permissions', 'npcink-governance-core' ); ?></th>
					<td><?php echo esc_html( implode( ', ', $scopes ) ); ?></td>
				</tr>
				<tr>
					<th scope="row"><?php echo esc_html__( 'Rate limit', 'npcink-governance-core' ); ?></th>
					<td>
						<?php
						echo esc_html(
							sprintf(
								/* translators: 1: request count, 2: window in seconds. */
								__( '%1$d requests per %2$d seconds', 'npcink-governance-core' ),
								(int) ( $app['rate_limit'] ?? App_Key_Repository::DEFAULT_RATE_LIMIT ),
								(int) ( $app['rate_window_seconds'] ?? App_Key_Repository::DEFAULT_RATE_WINDOW )
							)
						);
						?>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php echo esc_html__( 'Created', 'npcink-governance-core' ); ?></th>
					<td><?php echo esc_html( '' !== (string) $app['created_at'] ? $this->display_datetime( (string) $app['created_at'] ) : __( 'Unknown', 'npcink-governance-core' ) ); ?></td>
				</tr>
				<tr>
					<th scope="row"><?php echo esc_html__( 'Expires', 'npcink-governance-core' ); ?></th>
					<td><?php echo esc_html( '' !== (string) $app['expires_at'] ? $this->display_datetime( (string) $app['expires_at'] ) : __( 'No expiry', 'npcink-governance-core' ) ); ?></td>
				</tr>
				<tr>
					<th scope="row"><?php echo esc_html__( 'Revoked', 'npcink-governance-core' ); ?></th>
					<td><?php echo esc_html( '' !== (string) $app['revoked_at'] ? $this->display_datetime( (string) $app['revoked_at'] ) : __( 'No', 'npcink-governance-core' ) ); ?></td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Returns a user-facing scope label.
	 *
	 * @param string $scope Scope.
	 * @return string
	 */
	private function scope_label( string $scope ): string {
		$labels = array(
			'capabilities:read'        => __( 'Read capabilities', 'npcink-governance-core' ),
			'proposals:create'         => __( 'Create proposals', 'npcink-governance-core' ),
			'proposals:read'           => __( 'Read proposals', 'npcink-governance-core' ),
			'commit:preflight'         => __( 'Commit preflight', 'npcink-governance-core' ),
			'proposals:approve'        => __( 'Approve proposals', 'npcink-governance-core' ),
			'proposals:reject'         => __( 'Reject proposals', 'npcink-governance-core' ),
			'commit:record_execution' => __( 'Record execution results', 'npcink-governance-core' ),
			'read_requests:create'     => __( 'Create read requests', 'npcink-governance-core' ),
			'read_requests:read'       => __( 'Read read requests', 'npcink-governance-core' ),
			'read_requests:approve'    => __( 'Approve read requests', 'npcink-governance-core' ),
			'read_requests:reject'     => __( 'Reject read requests', 'npcink-governance-core' ),
			'read_requests:preflight'  => __( 'Read preflight', 'npcink-governance-core' ),
			'audit:read'               => __( 'Read audit log', 'npcink-governance-core' ),
		);

		return (string) ( $labels[ $scope ] ?? $scope );
	}

	/**
	 * Returns caller type choices for admin-issued tokens.
	 *
	 * @return array<string,string>
	 */
	private function caller_type_options(): array {
		return array(
			'product_adapter' => __( 'Product Adapter', 'npcink-governance-core' ),
			'mcp_adapter'     => __( 'MCP Adapter', 'npcink-governance-core' ),
			'agent_host'      => __( 'Agent Host', 'npcink-governance-core' ),
			'internal'        => __( 'Internal governance client', 'npcink-governance-core' ),
		);
	}

	/**
	 * Sanitizes caller type for admin-issued tokens.
	 *
	 * @param string $caller_type Caller type.
	 * @return string
	 */
	private function sanitize_caller_type( string $caller_type ): string {
		$options = $this->caller_type_options();
		return array_key_exists( $caller_type, $options ) ? $caller_type : 'product_adapter';
	}

	/**
	 * Returns scope presets for the token issuance form.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	private function token_scope_presets(): array {
		return array(
			'adapter_default'             => array(
				'label'       => __( 'Adapter default access', 'npcink-governance-core' ),
				'description' => __( 'Recommended for trusted Adapter clients that create proposals and request preflight.', 'npcink-governance-core' ),
				'scopes'      => $this->apps->default_scopes(),
			),
			'read_only_discovery'         => array(
				'label'       => __( 'Read-only discovery', 'npcink-governance-core' ),
				'description' => __( 'Only reads the capability contract. Use for diagnostics or setup checks.', 'npcink-governance-core' ),
				'scopes'      => array( 'capabilities:read' ),
			),
			'trusted_approval_execution' => array(
				'label'       => __( 'Trusted approval and execution record', 'npcink-governance-core' ),
				'description' => __( 'Use only when a trusted host policy presents approval context to an administrator.', 'npcink-governance-core' ),
				'scopes'      => array(
					'capabilities:read',
					'proposals:create',
					'proposals:read',
					'proposals:approve',
					'commit:preflight',
					'commit:record_execution',
					'read_requests:create',
					'read_requests:read',
					'read_requests:approve',
					'read_requests:reject',
					'read_requests:preflight',
				),
			),
			'custom'                     => array(
				'label'       => __( 'Custom permissions', 'npcink-governance-core' ),
				'description' => __( 'Use the advanced permission checkboxes for a narrowly scoped custom client.', 'npcink-governance-core' ),
				'scopes'      => array(),
			),
		);
	}

	/**
	 * Sanitizes a token scope preset.
	 *
	 * @param string $preset Preset key.
	 * @return string
	 */
	private function sanitize_token_scope_preset( string $preset ): string {
		$presets = $this->token_scope_presets();
		return array_key_exists( $preset, $presets ) ? $preset : 'adapter_default';
	}

	/**
	 * Returns scopes for a token scope preset.
	 *
	 * @param string $preset Preset key.
	 * @return array<int,string>
	 */
	private function scopes_for_token_preset( string $preset ): array {
		$preset  = $this->sanitize_token_scope_preset( $preset );
		$presets = $this->token_scope_presets();
		return (array) ( $presets[ $preset ]['scopes'] ?? $this->apps->default_scopes() );
	}

	/**
	 * Renders token scope preset choices.
	 *
	 * @return void
	 */
	private function render_token_scope_presets(): void {
		foreach ( $this->token_scope_presets() as $value => $preset ) {
			$scopes = $this->apps->sanitize_scopes( (array) ( $preset['scopes'] ?? array() ) );
			?>
			<label class="npcink-governance-core-token-preset">
				<input type="radio" name="scope_preset" value="<?php echo esc_attr( (string) $value ); ?>" <?php checked( 'adapter_default', (string) $value ); ?> />
				<span>
					<strong><?php echo esc_html( (string) $preset['label'] ); ?></strong>
					<span class="npcink-governance-core-token-preset-description"><?php echo esc_html( (string) $preset['description'] ); ?></span>
					<span class="npcink-governance-core-token-preset-scopes">
						<?php
						echo esc_html(
							empty( $scopes )
								? __( 'Uses advanced permission checkboxes.', 'npcink-governance-core' )
								: $this->token_scope_summary( $scopes )
						);
						?>
					</span>
				</span>
			</label>
			<?php
		}
	}

	/**
	 * Renders scope checkboxes.
	 *
	 * @return void
	 */
	private function render_scope_checkboxes(): void {
		$defaults = $this->apps->default_scopes();

		foreach ( $this->apps->allowed_scopes() as $scope ) {
			?>
			<label style="display: block; margin: 0 0 4px;">
				<input type="checkbox" name="scopes[]" value="<?php echo esc_attr( $scope ); ?>" <?php checked( in_array( $scope, $defaults, true ) ); ?> />
				<?php echo esc_html( $this->scope_label( $scope ) ); ?>
				<code><?php echo esc_html( $scope ); ?></code>
			</label>
			<?php
		}
	}

	/**
	 * Renders one-time client access token result.
	 *
	 * @param array<string,mixed> $app App row with one-time token.
	 * @return void
	 */
	private function render_created_app_key( array $app ): void {
		$token = (string) ( $app['token'] ?? '' );
		?>
		<!doctype html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta charset="<?php echo esc_attr( get_bloginfo( 'charset' ) ); ?>" />
			<meta name="viewport" content="width=device-width, initial-scale=1" />
				<title><?php echo esc_html__( 'Client access token created', 'npcink-governance-core' ); ?></title>
			<?php
			wp_enqueue_style( 'npcink-governance-core-admin', $this->admin_stylesheet_url(), array(), NPCINK_GOVERNANCE_CORE_VERSION );
			wp_print_styles( 'npcink-governance-core-admin' );
			?>
		</head>
		<body>
			<main>
					<h1><?php echo esc_html__( 'Client access token created', 'npcink-governance-core' ); ?></h1>
				<div class="notice">
					<p><?php echo esc_html__( 'Copy this token now. It is shown only once and is not stored in raw form.', 'npcink-governance-core' ); ?></p>
					<p><?php echo esc_html__( 'Use this token only in a trusted adapter or internal governance client secret store.', 'npcink-governance-core' ); ?></p>
				</div>
				<table>
					<tbody>
						<tr>
							<th scope="row"><?php echo esc_html__( 'App ID', 'npcink-governance-core' ); ?></th>
							<td><code><?php echo esc_html( (string) $app['app_id'] ); ?></code></td>
						</tr>
						<tr>
							<th scope="row"><?php echo esc_html__( 'Key ID', 'npcink-governance-core' ); ?></th>
							<td><code><?php echo esc_html( (string) $app['key_id'] ); ?></code></td>
						</tr>
						<tr>
								<th scope="row"><?php echo esc_html__( 'Access token', 'npcink-governance-core' ); ?></th>
							<td><textarea rows="3" readonly><?php echo esc_textarea( $token ); ?></textarea></td>
						</tr>
						<tr>
							<th scope="row"><?php echo esc_html__( 'Core env', 'npcink-governance-core' ); ?></th>
							<td><textarea rows="4" readonly><?php echo esc_textarea( $this->core_env_text( $token ) ); ?></textarea></td>
						</tr>
					</tbody>
				</table>
					<p class="actions"><a class="button" href="<?php echo esc_url( $this->view_url( 'app-keys' ) ); ?>"><?php echo esc_html__( 'Back to client access tokens', 'npcink-governance-core' ); ?></a></p>
			</main>
		</body>
		</html>
		<?php
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

		$note   = isset( $_POST['note'] ) ? sanitize_textarea_field( wp_unslash( (string) $_POST['note'] ) ) : '';
		$result = 'approve' === $decision ? $this->service->approve( $proposal_id, array( 'note' => $note ) ) : $this->service->reject( $proposal_id, array( 'note' => $note ) );

		if ( is_wp_error( $result ) ) {
			wp_safe_redirect( $this->admin_url( array( 'npcink_governance_core_error' => $result->get_error_code() ) ) );
			exit;
		}

		wp_safe_redirect( $this->admin_url( array( 'npcink_governance_core_message' => 'approve' === $decision ? 'approved' : 'rejected' ) ) );
		exit;
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
	 * Renders proposal detail.
	 *
	 * @param array<string,mixed> $proposal Proposal.
	 * @return void
	 */
	private function render_proposal_detail( array $proposal ): void {
		$proposal_id = (string) $proposal['proposal_id'];
		$capability  = $this->abilities->find( (string) $proposal['ability_id'] );
		$active_tab  = $this->proposal_detail_tab_from_request();
		$timeline    = $this->audit->list_filtered(
			array(
				'proposal_id' => $proposal_id,
				'limit'       => 50,
				'order'       => 'asc',
			)
		);
		?>
		<p><a href="<?php echo esc_url( $this->admin_url() ); ?>">&larr; <?php echo esc_html__( 'Back to review queue', 'npcink-governance-core' ); ?></a></p>
		<h2><?php echo esc_html__( 'Proposal Detail', 'npcink-governance-core' ); ?></h2>
		<p class="npcink-governance-core-subtle npcink-governance-core-copy-width"><?php echo esc_html__( 'Review the governance record, action plan, and audit evidence for this proposal.', 'npcink-governance-core' ); ?></p>
		<?php $this->render_proposal_summary_panel( $proposal ); ?>
		<?php $this->render_proposal_detail_tabs( $proposal, $active_tab ); ?>
		<div class="npcink-governance-core-tab-panel npcink-governance-core-max-wide">
			<?php $this->render_proposal_detail_tab_panel( $active_tab, $proposal, $capability, $timeline ); ?>
		</div>
		<?php
	}

	/**
	 * Returns the active proposal detail tab.
	 *
	 * @return string
	 */
	private function proposal_detail_tab_from_request(): string {
		$requested = $this->admin_query_key( 'proposal_tab', 'overview' );
		$tabs      = array_keys( $this->proposal_detail_tabs() );

		return in_array( $requested, $tabs, true ) ? $requested : 'overview';
	}

	/**
	 * Returns proposal detail tabs.
	 *
	 * @return array<string,string>
	 */
	private function proposal_detail_tabs(): array {
		return array(
			'overview'  => __( 'Overview', 'npcink-governance-core' ),
			'actions'   => __( 'Action plan', 'npcink-governance-core' ),
			'evidence'  => __( 'Audit evidence', 'npcink-governance-core' ),
			'technical' => __( 'Technical info', 'npcink-governance-core' ),
		);
	}

	/**
	 * Renders proposal detail tab navigation.
	 *
	 * @param array<string,mixed> $proposal Proposal.
	 * @param string              $active_tab Active tab.
	 * @return void
	 */
	private function render_proposal_detail_tabs( array $proposal, string $active_tab ): void {
		?>
		<nav class="npcink-ai-tabs npcink-governance-core-detail-tabs" aria-label="<?php echo esc_attr__( 'Proposal detail sections', 'npcink-governance-core' ); ?>">
			<?php foreach ( $this->proposal_detail_tabs() as $tab => $label ) : ?>
				<a
					class="npcink-ai-tab<?php echo $active_tab === $tab ? ' npcink-ai-tab-active' : ''; ?>"
					href="<?php echo esc_url( $this->proposal_detail_tab_url( $proposal, $tab ) ); ?>"
					<?php echo $active_tab === $tab ? 'aria-current="page"' : ''; ?>
				><?php echo esc_html( $label ); ?></a>
			<?php endforeach; ?>
		</nav>
		<?php
	}

	/**
	 * Renders the active proposal detail tab.
	 *
	 * @param string                         $active_tab Active tab.
	 * @param array<string,mixed>            $proposal Proposal.
	 * @param array<string,mixed>|null       $capability Capability row.
	 * @param array<int,array<string,mixed>> $timeline Proposal audit timeline.
	 * @return void
	 */
	private function render_proposal_detail_tab_panel( string $active_tab, array $proposal, ?array $capability, array $timeline ): void {
		if ( 'actions' === $active_tab ) {
			$this->render_proposal_actions_tab( $proposal );
			return;
		}

		if ( 'evidence' === $active_tab ) {
			$this->render_audit_timeline( $timeline );
			return;
		}

		if ( 'technical' === $active_tab ) {
			$this->render_proposal_identity_panel( $proposal );
			$this->render_raw_proposal_payload( $proposal );
			return;
		}

		$this->render_proposal_overview_tab( $proposal, $capability );
	}

	/**
	 * Renders the proposal detail overview tab.
	 *
	 * @param array<string,mixed>      $proposal Proposal.
	 * @param array<string,mixed>|null $capability Capability row.
	 * @return void
	 */
	private function render_proposal_overview_tab( array $proposal, ?array $capability ): void {
		$this->render_proposal_outcome_notice( $proposal );
		$this->render_review_context( $proposal, $capability );
	}

	/**
	 * Renders the proposal detail action-plan tab.
	 *
	 * @param array<string,mixed> $proposal Proposal.
	 * @return void
	 */
	private function render_proposal_actions_tab( array $proposal ): void {
		$has_batch_actions = ! empty( $this->proposal_batch_action_rows( $proposal ) );
		$has_details       = $this->proposal_has_proposed_change_details( $proposal );

		if ( ! $has_batch_actions && ! $has_details ) {
			?>
			<p class="npcink-governance-core-subtle"><?php echo esc_html__( 'No structured action plan or proposed change detail was recorded for this proposal.', 'npcink-governance-core' ); ?></p>
			<?php
			return;
		}

		$this->render_proposal_batch_actions( $proposal );
		$this->render_proposed_change_details( $proposal );
	}

	/**
	 * Builds a tab URL for proposal detail.
	 *
	 * @param array<string,mixed> $proposal Proposal.
	 * @param string              $tab Tab key.
	 * @return string
	 */
	private function proposal_detail_tab_url( array $proposal, string $tab ): string {
		return $this->admin_url(
			array(
				'proposal_id'  => (string) ( $proposal['proposal_id'] ?? '' ),
				'proposal_tab' => $tab,
			)
		);
	}

	/**
	 * Renders the proposal detail summary panel.
	 *
	 * @param array<string,mixed> $proposal Proposal.
	 * @return void
	 */
	private function render_proposal_summary_panel( array $proposal ): void {
		$warning_count = $this->proposal_warning_count( $proposal );
		$blocked_count = $this->proposal_blocked_count( $proposal );
		$is_pending    = Proposal_Repository::STATUS_PENDING === (string) ( $proposal['status'] ?? '' );
		$posture       = $this->proposal_governance_posture( $proposal );
		$has_signals   = $warning_count > 0
			|| $blocked_count > 0
			|| $this->proposal_needs_input_count( $proposal ) > 0
			|| $this->proposal_preflight_blocker_count( $proposal ) > 0;
		$classes       = 'npcink-governance-core-proposal-summary npcink-governance-core-max-wide';
		if ( $is_pending ) {
			$classes .= ' npcink-governance-core-proposal-summary-with-actions';
		}
		?>
		<div class="<?php echo esc_attr( $classes ); ?>">
			<div>
				<div class="npcink-governance-core-summary-label"><?php echo esc_html__( 'Review ID', 'npcink-governance-core' ); ?></div>
				<code class="npcink-governance-core-display-id npcink-governance-core-display-id-primary"><?php echo esc_html( $this->proposal_display_id( $proposal ) ); ?></code>
				<div class="npcink-governance-core-muted">
					<?php echo esc_html( $this->proposal_summary_request_label( $proposal ) ); ?>
					<span aria-hidden="true">&middot;</span>
					<?php echo esc_html( $this->proposal_summary_request_meta( $proposal ) ); ?>
				</div>
			</div>
			<div>
				<div class="npcink-governance-core-summary-label"><?php echo esc_html__( 'Status', 'npcink-governance-core' ); ?></div>
				<?php $this->render_status_badge( (string) ( $proposal['status'] ?? '' ) ); ?>
				<div class="npcink-governance-core-summary-detail"><?php echo esc_html( $this->proposal_status_guidance( $proposal ) ); ?></div>
				<div class="npcink-governance-core-summary-detail"><?php echo esc_html( $this->display_datetime( (string) ( $proposal['created_at'] ?? '' ) ) ); ?></div>
			</div>
			<div>
				<div class="npcink-governance-core-summary-label"><?php echo esc_html__( 'Governance path', 'npcink-governance-core' ); ?></div>
				<span class="npcink-governance-core-classification-badge npcink-governance-core-classification-<?php echo esc_attr( sanitize_html_class( $posture['classification'] ) ); ?>"><?php echo esc_html( $posture['classification_label'] ); ?></span>
				<div class="npcink-governance-core-summary-detail"><?php echo esc_html( $posture['write_posture'] ); ?></div>
			</div>
			<div>
				<div class="npcink-governance-core-summary-label"><?php echo esc_html__( 'Evidence', 'npcink-governance-core' ); ?></div>
				<?php if ( $has_signals ) : ?>
					<?php $this->render_risk_badge( $this->proposal_risk_label( $proposal ) ); ?>
					<div class="npcink-governance-core-summary-detail">
						<?php
						printf(
							/* translators: 1: warning count, 2: blocked item count. */
							esc_html__( '%1$d warnings / %2$d blocked', 'npcink-governance-core' ),
							absint( $warning_count ),
							absint( $blocked_count )
						);
						?>
					</div>
				<?php else : ?>
					<span class="npcink-governance-core-evidence-ok"><?php echo esc_html__( 'No risk signals', 'npcink-governance-core' ); ?></span>
				<?php endif; ?>
			</div>
			<?php if ( $is_pending ) : ?>
				<div class="npcink-governance-core-summary-actions">
					<div class="npcink-governance-core-summary-label"><?php echo esc_html__( 'Decision', 'npcink-governance-core' ); ?></div>
					<?php $this->render_decision_controls( $proposal ); ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Returns review-facing governance posture for a proposal.
	 *
	 * @param array<string,mixed>      $proposal Proposal.
	 * @param array<string,mixed>|null $capability Capability row.
	 * @return array<string,string>
	 */
	private function proposal_governance_posture( array $proposal, ?array $capability = null ): array {
		$classification = $this->proposal_operation_classification( $proposal );
		$envelope       = is_array( $classification['decision_envelope'] ?? null ) ? $classification['decision_envelope'] : array();
		$value          = sanitize_key( (string) ( $classification['classification'] ?? '' ) );
		if ( '' === $value ) {
			$value = Operation_Classifier::CLASSIFICATION_CORE_PROPOSAL_REQUIRED;
		}

		return array(
			'classification'       => $value,
			'classification_label' => $this->proposal_classification_label( $value ),
			'intake_path'          => sanitize_key( (string) ( $classification['intake_path'] ?? 'core_proposal' ) ),
			'request_source'       => sanitize_key( (string) ( $envelope['request_source'] ?? '' ) ),
			'actor_presence'       => sanitize_key( (string) ( $envelope['actor_presence'] ?? '' ) ),
			'preview_completeness' => sanitize_key( (string) ( $envelope['preview_completeness'] ?? '' ) ),
			'scope'                => sanitize_key( (string) ( $envelope['scope'] ?? '' ) ),
			'operation_kind'       => sanitize_key( (string) ( $envelope['operation_kind'] ?? '' ) ),
			'write_posture'        => $this->proposal_write_posture_label( $value, $envelope ),
			'execution_owner'      => $this->proposal_execution_owner_label( $value ),
			'blocked_guidance'     => $this->proposal_blocked_guidance( $proposal, $capability ),
		);
	}

	/**
	 * Returns the stored operation classification evidence.
	 *
	 * @param array<string,mixed> $proposal Proposal.
	 * @return array<string,mixed>
	 */
	private function proposal_operation_classification( array $proposal ): array {
		$preview        = is_array( $proposal['preview'] ?? null ) ? $proposal['preview'] : array();
		$classification = is_array( $preview['operation_classification'] ?? null ) ? $preview['operation_classification'] : array();

		return $classification;
	}

	/**
	 * Returns a human label for the operation classification.
	 *
	 * @param string $classification Classification value.
	 * @return string
	 */
	private function proposal_classification_label( string $classification ): string {
		$labels = array(
			Operation_Classifier::CLASSIFICATION_SUGGESTION_ONLY          => __( 'Suggestion only', 'npcink-governance-core' ),
			Operation_Classifier::CLASSIFICATION_LOCAL_ADMIN_CONSENT      => __( 'Local admin consent', 'npcink-governance-core' ),
			Operation_Classifier::CLASSIFICATION_STRONG_LOCAL_CONFIRMATION => __( 'Strong local confirmation', 'npcink-governance-core' ),
			Operation_Classifier::CLASSIFICATION_CORE_PROPOSAL_REQUIRED   => __( 'Core proposal required', 'npcink-governance-core' ),
		);

		return (string) ( $labels[ $classification ] ?? str_replace( '_', ' ', $classification ) );
	}

	/**
	 * Returns the write posture label for a proposal classification.
	 *
	 * @param string              $classification Classification value.
	 * @param array<string,mixed> $envelope Decision envelope.
	 * @return string
	 */
	private function proposal_write_posture_label( string $classification, array $envelope ): string {
		$writes_state = array_key_exists( 'writes_wordpress_state', $envelope ) ? (bool) $envelope['writes_wordpress_state'] : true;
		if ( ! $writes_state || Operation_Classifier::CLASSIFICATION_SUGGESTION_ONLY === $classification ) {
			return __( 'No WordPress state write is declared.', 'npcink-governance-core' );
		}

		if ( Operation_Classifier::CLASSIFICATION_CORE_PROPOSAL_REQUIRED === $classification ) {
			return __( 'Core records proposal, approval, preflight, and audit only; final WordPress writes stay outside Core.', 'npcink-governance-core' );
		}

		if ( Operation_Classifier::CLASSIFICATION_LOCAL_ADMIN_CONSENT === $classification ) {
			return __( 'A present administrator may apply one bounded local write only with audit evidence.', 'npcink-governance-core' );
		}

		return __( 'A present administrator needs stronger confirmation or Core proposal review before the write.', 'npcink-governance-core' );
	}

	/**
	 * Returns the execution owner label for the proposal review surface.
	 *
	 * @param string $classification Classification value.
	 * @return string
	 */
	private function proposal_execution_owner_label( string $classification ): string {
		if ( Operation_Classifier::CLASSIFICATION_CORE_PROPOSAL_REQUIRED === $classification ) {
			return __( 'Adapter or host after Core commit preflight', 'npcink-governance-core' );
		}

		if ( Operation_Classifier::CLASSIFICATION_SUGGESTION_ONLY === $classification ) {
			return __( 'No execution owner because no WordPress write is declared', 'npcink-governance-core' );
		}

		return __( 'Local product module with Core-owned audit evidence', 'npcink-governance-core' );
	}

	/**
	 * Returns a compact blocker and next-step guidance label.
	 *
	 * @param array<string,mixed>      $proposal Proposal.
	 * @param array<string,mixed>|null $capability Capability row.
	 * @return string
	 */
	private function proposal_blocked_guidance( array $proposal, ?array $capability = null ): string {
		if ( null === $capability ) {
			return __( 'Target ability is not discoverable; commit preflight will fail closed until the provider exposes it again.', 'npcink-governance-core' );
		}

		if ( $this->proposal_preflight_blocker_count( $proposal ) > 0 || $this->proposal_blocked_count( $proposal ) > 0 || $this->proposal_needs_input_count( $proposal ) > 0 ) {
			return __( 'Resolve blocked items, required input, or preflight blockers before approval can become executable.', 'npcink-governance-core' );
		}

		$status = (string) ( $proposal['status'] ?? '' );
		if ( Proposal_Repository::STATUS_PENDING === $status ) {
			return __( 'Approve or reject this proposal; approved items still require commit preflight before external execution.', 'npcink-governance-core' );
		}

		if ( Proposal_Repository::STATUS_APPROVED === $status ) {
			return __( 'Run commit preflight to issue a bounded handoff; Core still will not execute the write.', 'npcink-governance-core' );
		}

		if ( Proposal_Repository::STATUS_EXECUTION_FAILED === $status ) {
			return __( 'Review the Adapter execution result and submit a new proposal if another write attempt is needed.', 'npcink-governance-core' );
		}

		return __( 'No pending Core decision is available for this lifecycle state.', 'npcink-governance-core' );
	}

	/**
	 * Renders proposal identity and source as grouped detail tables.
	 *
	 * @param array<string,mixed> $proposal Proposal.
	 * @return void
	 */
	private function render_proposal_identity_panel( array $proposal ): void {
		?>
		<details class="npcink-governance-core-disclosure npcink-governance-core-max-wide npcink-governance-core-disclosure-top">
			<summary>
				<strong><?php echo esc_html__( 'Technical identity', 'npcink-governance-core' ); ?></strong>
				<span class="npcink-governance-core-muted"><?php echo esc_html__( 'Full proposal id, ability id, source trace, caller, app, and policy fields.', 'npcink-governance-core' ); ?></span>
			</summary>
			<?php $this->render_proposal_detail_groups( $this->proposal_identity_groups( $proposal ) ); ?>
		</details>
		<?php
	}

	/**
	 * Renders a non-pending outcome note instead of decision controls.
	 *
	 * @param array<string,mixed> $proposal Proposal.
	 * @return void
	 */
	private function render_proposal_outcome_notice( array $proposal ): void {
		$status = (string) ( $proposal['status'] ?? '' );
		if ( Proposal_Repository::STATUS_PENDING === $status ) {
			return;
		}
		?>
		<div class="notice notice-info inline npcink-governance-core-outcome-notice npcink-governance-core-max-wide">
			<p>
				<strong><?php echo esc_html__( 'No pending decision.', 'npcink-governance-core' ); ?></strong>
				<?php echo esc_html( $this->proposal_outcome_message( $proposal ) ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Renders grouped key-value detail tables.
	 *
	 * @param array<int,array{label:string,rows:array<int,array{label:string,value:mixed,code?:bool}>}> $groups Detail groups.
	 * @return void
	 */
	private function render_proposal_detail_groups( array $groups ): void {
		?>
		<div class="npcink-governance-core-detail-groups npcink-governance-core-max-wide">
			<?php foreach ( $groups as $group ) : ?>
				<div class="npcink-governance-core-detail-group">
					<div class="npcink-governance-core-row-details-heading"><?php echo esc_html( $group['label'] ); ?></div>
					<table class="widefat npcink-governance-core-row-details-table">
						<tbody>
							<?php foreach ( $group['rows'] as $row ) : ?>
								<?php
								$value = $row['value'];
								if ( is_array( $value ) || is_object( $value ) ) {
									$value = wp_json_encode( $value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
								}
								$value = trim( (string) $value );
								if ( '' === $value ) {
									continue;
								}
								?>
								<tr>
									<th scope="row"><?php echo esc_html( $row['label'] ); ?></th>
									<td>
										<?php if ( ! empty( $row['code'] ) ) : ?>
											<code><?php echo esc_html( $value ); ?></code>
										<?php else : ?>
											<?php echo esc_html( $value ); ?>
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Returns grouped proposal identity rows.
	 *
	 * @param array<string,mixed> $proposal Proposal.
	 * @return array<int,array{label:string,rows:array<int,array{label:string,value:mixed,code?:bool}>}>
	 */
	private function proposal_identity_groups( array $proposal ): array {
		$preview        = is_array( $proposal['preview'] ?? null ) ? $proposal['preview'] : array();
		$source         = is_array( $preview['source'] ?? null ) ? $preview['source'] : array();
		$caller         = is_array( $proposal['caller'] ?? null ) ? $proposal['caller'] : array();
		$auth           = is_array( $caller['auth'] ?? null ) ? $caller['auth'] : array();
		$policy_reasons = implode( ', ', array_map( 'strval', (array) ( $proposal['policy_reasons'] ?? array() ) ) );
		$posture        = $this->proposal_governance_posture( $proposal );

		return array(
			array(
				'label' => __( 'Governance identity', 'npcink-governance-core' ),
				'rows'  => array(
					array(
						'label' => __( 'Display ID', 'npcink-governance-core' ),
						'value' => $this->proposal_display_id( $proposal ),
						'code'  => true,
					),
					array(
						'label' => __( 'Proposal ID', 'npcink-governance-core' ),
						'value' => (string) ( $proposal['proposal_id'] ?? '' ),
						'code'  => true,
					),
					array(
						'label' => __( 'Target ability', 'npcink-governance-core' ),
						'value' => (string) ( $proposal['ability_id'] ?? '' ),
						'code'  => true,
					),
					array(
						'label' => __( 'Created', 'npcink-governance-core' ),
						'value' => $this->display_datetime( (string) ( $proposal['created_at'] ?? '' ) ),
					),
					array(
						'label' => __( 'Updated', 'npcink-governance-core' ),
						'value' => $this->display_datetime( (string) ( $proposal['updated_at'] ?? '' ) ),
					),
					array(
						'label' => __( 'Expiry', 'npcink-governance-core' ),
						'value' => $this->proposal_expiry_label( $proposal ),
					),
				),
			),
			array(
				'label' => __( 'Source and policy', 'npcink-governance-core' ),
				'rows'  => array(
					array(
						'label' => __( 'Source type', 'npcink-governance-core' ),
						'value' => (string) ( $source['type'] ?? $caller['source'] ?? '' ),
						'code'  => true,
					),
					array(
						'label' => __( 'Plan ability', 'npcink-governance-core' ),
						'value' => (string) ( $source['plan_ability_id'] ?? $caller['plan_ability_id'] ?? '' ),
						'code'  => true,
					),
					array(
						'label' => __( 'Batch ID', 'npcink-governance-core' ),
						'value' => (string) ( $source['batch_id'] ?? $caller['batch_id'] ?? '' ),
						'code'  => true,
					),
					array(
						'label' => __( 'Proposal mode', 'npcink-governance-core' ),
						'value' => (string) ( $source['proposal_mode'] ?? '' ),
						'code'  => true,
					),
					array(
						'label' => __( 'Caller type', 'npcink-governance-core' ),
						'value' => (string) ( $auth['caller_type'] ?? $caller['caller_type'] ?? '' ),
						'code'  => true,
					),
					array(
						'label' => __( 'App ID', 'npcink-governance-core' ),
						'value' => (string) ( $auth['app_id'] ?? $caller['app_id'] ?? '' ),
						'code'  => true,
					),
					array(
						'label' => __( 'Policy decision', 'npcink-governance-core' ),
						'value' => (string) ( $proposal['policy_decision'] ?? '' ),
						'code'  => true,
					),
					array(
						'label' => __( 'Policy profile', 'npcink-governance-core' ),
						'value' => (string) ( $proposal['policy_profile'] ?? '' ),
						'code'  => true,
					),
					array(
						'label' => __( 'Policy reasons', 'npcink-governance-core' ),
						'value' => $policy_reasons,
					),
					array(
						'label' => __( 'Classification', 'npcink-governance-core' ),
						'value' => $posture['classification'],
						'code'  => true,
					),
					array(
						'label' => __( 'Intake path', 'npcink-governance-core' ),
						'value' => $posture['intake_path'],
						'code'  => true,
					),
				),
			),
		);
	}

	/**
	 * Renders ordered batch actions when a proposal contains write actions.
	 *
	 * @param array<string,mixed> $proposal Proposal.
	 * @return void
	 */
	private function render_proposal_batch_actions( array $proposal ): void {
		$actions = $this->proposal_batch_action_rows( $proposal );
		if ( empty( $actions ) ) {
			return;
		}
		?>
		<h3><?php echo esc_html__( 'Batch actions', 'npcink-governance-core' ); ?></h3>
		<p class="npcink-governance-core-subtle npcink-governance-core-copy-width"><?php echo esc_html__( 'Ordered actions in this Core proposal. Final execution remains outside Core after approval and preflight.', 'npcink-governance-core' ); ?></p>
		<table class="widefat striped npcink-governance-core-detail-table npcink-governance-core-action-plan-table">
			<thead>
				<tr>
					<th scope="col"><?php echo esc_html__( 'Action', 'npcink-governance-core' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'Target ability', 'npcink-governance-core' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'Readiness', 'npcink-governance-core' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'Dependency', 'npcink-governance-core' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $actions as $action ) : ?>
					<tr>
						<td>
							<code><?php echo esc_html( $action['action_id'] ); ?></code>
							<?php if ( '' !== $action['reason'] ) : ?>
								<div class="npcink-governance-core-muted"><?php echo esc_html( $action['reason'] ); ?></div>
							<?php endif; ?>
						</td>
						<td><code title="<?php echo esc_attr( $action['target_ability_id'] ); ?>"><?php echo esc_html( $this->compact_identifier( $action['target_ability_id'] ) ); ?></code></td>
						<td>
							<?php echo esc_html( $action['readiness'] ); ?>
							<div class="npcink-governance-core-muted"><?php echo esc_html( $action['execution'] ); ?></div>
						</td>
						<td><?php echo esc_html( $action['dependency'] ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Renders approve/reject controls near the top of pending proposal detail.
	 *
	 * @param array<string,mixed> $proposal Proposal.
	 * @return void
	 */
	private function render_decision_controls( array $proposal ): void {
		$proposal_id = (string) $proposal['proposal_id'];
		if ( Proposal_Repository::STATUS_PENDING !== (string) $proposal['status'] ) {
			return;
		}
		?>
		<form class="npcink-governance-core-decision-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="proposal_id" value="<?php echo esc_attr( $proposal_id ); ?>" />
			<?php wp_nonce_field( 'npcink_governance_core_decide_proposal_' . $proposal_id ); ?>
			<button type="submit" class="button button-primary" name="action" value="npcink_governance_core_approve_proposal">
				<?php echo esc_html__( 'Approve', 'npcink-governance-core' ); ?>
			</button>
			<details class="npcink-governance-core-reject-disclosure">
				<summary class="button"><?php echo esc_html__( 'Reject', 'npcink-governance-core' ); ?></summary>
				<div class="npcink-governance-core-reject-panel">
					<label for="npcink-governance-core-note"><?php echo esc_html__( 'Rejection note', 'npcink-governance-core' ); ?></label>
					<textarea id="npcink-governance-core-note" name="note" rows="3" class="large-text"></textarea>
					<button type="submit" class="button" name="action" value="npcink_governance_core_reject_proposal">
						<?php echo esc_html__( 'Confirm rejection', 'npcink-governance-core' ); ?>
					</button>
				</div>
			</details>
		</form>
		<?php
	}

	/**
	 * Renders review context for the selected proposal.
	 *
	 * @param array<string,mixed>      $proposal Proposal.
	 * @param array<string,mixed>|null $capability Capability row.
	 * @return void
	 */
	private function render_review_context( array $proposal, ?array $capability ): void {
		$preview               = is_array( $proposal['preview'] ?? null ) ? $proposal['preview'] : array();
		$risk_label            = $this->proposal_risk_label( $proposal );
		$undeclared_risk_label = __( 'Not declared', 'npcink-governance-core' );
		$target_ability        = (string) ( $preview['target_ability_id'] ?? $proposal['ability_id'] );
		$posture               = $this->proposal_governance_posture( $proposal, $capability );
		$reason                = (string) ( $preview['reason'] ?? '' );
		$ready_label           = array_key_exists( 'proposal_ready', $preview )
			? ( (bool) $preview['proposal_ready'] ? __( 'yes', 'npcink-governance-core' ) : __( 'no', 'npcink-governance-core' ) )
			: __( 'not declared', 'npcink-governance-core' );
		$warning_count         = $this->proposal_warning_count( $proposal );
		$blocked_count         = $this->proposal_blocked_count( $proposal );
		$needs_input_count     = $this->proposal_needs_input_count( $proposal );
		$preflight_count       = $this->proposal_preflight_blocker_count( $proposal );
		$signal_rows           = array();

		if ( $undeclared_risk_label === $risk_label && null !== $capability ) {
			$risk_label = (string) $capability['risk_level'];
		}
		if ( '' !== $reason ) {
			$signal_rows[] = array(
				'label' => __( 'Reason', 'npcink-governance-core' ),
				'value' => $reason,
			);
		}
		foreach (
			array(
				array(
					'label' => __( 'Warnings', 'npcink-governance-core' ),
					'value' => $warning_count,
				),
				array(
					'label' => __( 'Blocked items', 'npcink-governance-core' ),
					'value' => $blocked_count,
				),
				array(
					'label' => __( 'Needs input', 'npcink-governance-core' ),
					'value' => $needs_input_count,
				),
				array(
					'label' => __( 'Preflight blockers', 'npcink-governance-core' ),
					'value' => $preflight_count,
				),
			) as $row
		) {
			if ( $row['value'] > 0 ) {
				$signal_rows[] = array(
					'label' => $row['label'],
					'value' => (string) $row['value'],
				);
			}
		}
		?>
		<h3><?php echo esc_html__( 'Review basis', 'npcink-governance-core' ); ?></h3>
		<?php if ( null === $capability ) : ?>
			<div class="notice notice-warning inline npcink-governance-core-max-wide">
				<p><?php echo esc_html__( 'The target ability is not currently discoverable. Commit preflight will fail closed until the provider exposes it again.', 'npcink-governance-core' ); ?></p>
			</div>
		<?php endif; ?>
		<?php
		$review_groups = array(
			array(
				'label' => __( 'Ability and policy', 'npcink-governance-core' ),
				'rows'  => array(
					array(
						'label' => __( 'Target ability', 'npcink-governance-core' ),
						'value' => $target_ability,
						'code'  => true,
					),
					array(
						'label' => __( 'Risk', 'npcink-governance-core' ),
						'value' => $risk_label,
						'code'  => true,
					),
					array(
						'label' => __( 'Requires approval', 'npcink-governance-core' ),
						'value' => null !== $capability && ! empty( $capability['requires_approval'] ) ? __( 'yes', 'npcink-governance-core' ) : __( 'no', 'npcink-governance-core' ),
					),
					array(
						'label' => __( 'Proposal ready', 'npcink-governance-core' ),
						'value' => $ready_label,
					),
					array(
						'label' => __( 'Policy decision', 'npcink-governance-core' ),
						'value' => (string) ( $proposal['policy_decision'] ?? '' ),
						'code'  => true,
					),
				),
			),
			array(
				'label' => __( 'Classification and handoff', 'npcink-governance-core' ),
				'rows'  => array(
					array(
						'label' => __( 'Classification', 'npcink-governance-core' ),
						'value' => $posture['classification_label'],
					),
					array(
						'label' => __( 'Intake path', 'npcink-governance-core' ),
						'value' => $posture['intake_path'],
						'code'  => true,
					),
					array(
						'label' => __( 'Request source', 'npcink-governance-core' ),
						'value' => $posture['request_source'],
						'code'  => true,
					),
					array(
						'label' => __( 'Actor presence', 'npcink-governance-core' ),
						'value' => $posture['actor_presence'],
						'code'  => true,
					),
					array(
						'label' => __( 'Preview completeness', 'npcink-governance-core' ),
						'value' => $posture['preview_completeness'],
						'code'  => true,
					),
					array(
						'label' => __( 'Scope', 'npcink-governance-core' ),
						'value' => $posture['scope'],
						'code'  => true,
					),
					array(
						'label' => __( 'Operation kind', 'npcink-governance-core' ),
						'value' => $posture['operation_kind'],
						'code'  => true,
					),
					array(
						'label' => __( 'Write posture', 'npcink-governance-core' ),
						'value' => $posture['write_posture'],
					),
					array(
						'label' => __( 'Execution owner', 'npcink-governance-core' ),
						'value' => $posture['execution_owner'],
					),
					array(
						'label' => __( 'Blocked guidance', 'npcink-governance-core' ),
						'value' => $posture['blocked_guidance'],
					),
				),
			),
		);
		if ( ! empty( $signal_rows ) ) {
			$review_groups[] = array(
				'label' => __( 'Preview signals', 'npcink-governance-core' ),
				'rows'  => $signal_rows,
			);
		}
		$this->render_proposal_detail_groups( $review_groups );
		if ( empty( $signal_rows ) ) :
			?>
			<p class="npcink-governance-core-signal-summary npcink-governance-core-max-wide"><?php echo esc_html__( 'Preview signals: no warnings, no blocked items, no required input, and no preflight blockers.', 'npcink-governance-core' ); ?></p>
			<?php
		endif;
		?>
		<?php
	}

	/**
	 * Returns whether structured proposed-change detail exists.
	 *
	 * @param array<string,mixed> $proposal Proposal.
	 * @return bool
	 */
	private function proposal_has_proposed_change_details( array $proposal ): bool {
		$preview          = is_array( $proposal['preview'] ?? null ) ? $proposal['preview'] : array();
		$article_workflow = is_array( $preview['article_workflow'] ?? null ) ? $preview['article_workflow'] : array();
		$nightly_review   = is_array( $preview['nightly_inspection_review'] ?? null ) ? $preview['nightly_inspection_review'] : array();

		return array_key_exists( 'before', $preview )
			|| array_key_exists( 'after_suggestion', $preview )
			|| ! empty( $preview['needs_input'] )
			|| ! empty( $preview['blocked_items'] )
			|| ! empty( $preview['field_patch'] )
			|| ! empty( $article_workflow )
			|| ! empty( $nightly_review );
	}

	/**
	 * Renders structured proposed-change detail.
	 *
	 * @param array<string,mixed> $proposal Proposal.
	 * @return void
	 */
	private function render_proposed_change_details( array $proposal ): void {
		if ( ! $this->proposal_has_proposed_change_details( $proposal ) ) {
			return;
		}

		$preview          = is_array( $proposal['preview'] ?? null ) ? $proposal['preview'] : array();
		$article_workflow = is_array( $preview['article_workflow'] ?? null ) ? $preview['article_workflow'] : array();
		$nightly_review   = is_array( $preview['nightly_inspection_review'] ?? null ) ? $preview['nightly_inspection_review'] : array();
		?>
			<details class="npcink-governance-core-disclosure npcink-governance-core-max-wide npcink-governance-core-disclosure-top">
				<summary>
					<strong><?php echo esc_html__( 'Proposed change details', 'npcink-governance-core' ); ?></strong>
					<span class="npcink-governance-core-muted"><?php echo esc_html__( 'Structured preview fields that need reviewer attention.', 'npcink-governance-core' ); ?></span>
				</summary>
				<table class="widefat striped npcink-governance-core-table-spaced">
					<tbody>
						<?php
						if ( array_key_exists( 'before', $preview ) ) {
							$this->render_review_value_row( __( 'Before', 'npcink-governance-core' ), $preview['before'] );
						}
						if ( array_key_exists( 'after_suggestion', $preview ) ) {
							$this->render_review_value_row( __( 'After suggestion', 'npcink-governance-core' ), $preview['after_suggestion'] );
						}
						if ( ! empty( $preview['needs_input'] ) ) {
							$this->render_review_value_row( __( 'Needs input', 'npcink-governance-core' ), $preview['needs_input'] );
						}
						if ( ! empty( $preview['blocked_items'] ) ) {
							$this->render_review_value_row( __( 'Blocked items', 'npcink-governance-core' ), $preview['blocked_items'] );
						}
						if ( ! empty( $preview['field_patch'] ) && is_array( $preview['field_patch'] ) ) {
							$this->render_field_patch_review_context( $preview['field_patch'] );
						}
						if ( ! empty( $article_workflow ) ) {
							$this->render_article_workflow_review_context( $article_workflow, (string) $proposal['ability_id'] );
						}
						if ( ! empty( $nightly_review ) ) {
							$this->render_nightly_inspection_review_context( $nightly_review );
						}
						?>
					</tbody>
				</table>
			</details>
		<?php
	}

	/**
	 * Renders field-level changes from proposal preview metadata.
	 *
	 * @param array<string,mixed> $field_patch Field patch preview.
	 * @return void
	 */
	private function render_field_patch_review_context( array $field_patch ): void {
		$rows = array();
		foreach ( $field_patch as $field => $value ) {
			if ( is_int( $field ) && is_array( $value ) ) {
				$field = (string) ( $value['field'] ?? $value['path'] ?? '' );
				$value = $value['proposed'] ?? $value['after'] ?? $value['value'] ?? '';
			}

			$field = sanitize_text_field( (string) $field );
			if ( '' === $field ) {
				continue;
			}

			$rows[ $field ] = $value;
		}

		if ( empty( $rows ) ) {
			return;
		}
		?>
		<tr>
			<th scope="row"><?php echo esc_html__( 'Field changes', 'npcink-governance-core' ); ?></th>
			<td>
				<table class="widefat striped" style="max-width: 900px;">
					<thead>
						<tr>
							<th scope="col"><?php echo esc_html__( 'Field', 'npcink-governance-core' ); ?></th>
							<th scope="col"><?php echo esc_html__( 'Proposed value', 'npcink-governance-core' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $rows as $field => $value ) : ?>
							<tr>
								<td><code><?php echo esc_html( $field ); ?></code></td>
								<td><?php $this->render_review_inline_value( $value ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</td>
		</tr>
		<?php
	}

	/**
	 * Renders a compact article workflow summary for proposal review.
	 *
	 * @param array<string,mixed> $article_workflow Article workflow preview.
	 * @param string              $proposal_ability_id Final write ability id.
	 * @return void
	 */
	private function render_article_workflow_review_context( array $article_workflow, string $proposal_ability_id ): void {
		$goal           = is_array( $article_workflow['article_goal_brief'] ?? null ) ? $article_workflow['article_goal_brief'] : array();
		$draft          = is_array( $article_workflow['article_draft_candidate'] ?? null ) ? $article_workflow['article_draft_candidate'] : array();
		$risk_report    = is_array( $article_workflow['article_risk_report'] ?? null ) ? $article_workflow['article_risk_report'] : array();
		$blocked_claims = is_array( $risk_report['blocked_claims'] ?? null ) ? $risk_report['blocked_claims'] : array();
		$needs_review   = is_array( $risk_report['needs_review'] ?? null ) ? $risk_report['needs_review'] : array();
		$title          = (string) ( $draft['title'] ?? $goal['title'] ?? $goal['topic'] ?? '' );

		$this->render_review_value_row(
			__( 'Article workflow', 'npcink-governance-core' ),
			array(
				'title'                  => '' !== $title ? $title : '-',
				'artifact_type'          => (string) ( $article_workflow['artifact_type'] ?? '' ),
				'version'                => absint( $article_workflow['version'] ?? 0 ),
				'risk_level'             => (string) ( $risk_report['risk_level'] ?? '-' ),
				'ready_for_proposal'     => ! empty( $risk_report['ready_for_proposal'] ) ? __( 'yes', 'npcink-governance-core' ) : __( 'no', 'npcink-governance-core' ),
				'blocked_claims'         => count( $blocked_claims ),
				'needs_review'           => count( $needs_review ),
				'final_write_ability'    => $proposal_ability_id,
				'final_write_path'       => (string) ( $article_workflow['final_write_path'] ?? '' ),
				'direct_wordpress_write' => ! empty( $article_workflow['direct_wordpress_write'] ) ? __( 'yes', 'npcink-governance-core' ) : __( 'no', 'npcink-governance-core' ),
			)
		);

		$artifact_availability = array();
		foreach (
			array(
				'article_goal_brief',
				'research_evidence_pack',
				'article_outline',
				'article_draft_candidate',
				'discoverability_pack',
				'article_risk_report',
			) as $artifact_key
		) {
			$artifact_availability[ $artifact_key ] = ! empty( $article_workflow[ $artifact_key ] ) ? __( 'included', 'npcink-governance-core' ) : __( 'missing', 'npcink-governance-core' );
		}

		$this->render_review_value_row( __( 'Article artifacts', 'npcink-governance-core' ), $artifact_availability );

		if ( ! empty( $blocked_claims ) ) {
			$this->render_review_value_row( __( 'Blocked claims', 'npcink-governance-core' ), $blocked_claims );
		}
	}

	/**
	 * Renders the selected Morning Brief review item preserved by Nightly Inspection handoff.
	 *
	 * @param array<string,mixed> $nightly_review Nightly inspection preview payload.
	 * @return void
	 */
	private function render_nightly_inspection_review_context( array $nightly_review ): void {
		$selected_items      = is_array( $nightly_review['selected_review_items'] ?? null ) ? array_values( $nightly_review['selected_review_items'] ) : array();
		$core_intake_package = is_array( $nightly_review['core_intake_package'] ?? null ) ? $nightly_review['core_intake_package'] : array();
		$evidence_refs       = is_array( $nightly_review['evidence_refs'] ?? null ) ? array_values( $nightly_review['evidence_refs'] ) : array();
		$completed_draft     = is_array( $nightly_review['completed_draft'] ?? null ) ? $nightly_review['completed_draft'] : array();
		$proposal_ready      = array_key_exists( 'proposal_ready', $nightly_review ) ? ! empty( $nightly_review['proposal_ready'] ) : null;
		$completed_submitted = ! empty( $nightly_review['completed_draft_submitted'] );
		$first_item          = is_array( $selected_items[0] ?? null ) ? $selected_items[0] : array();

		$this->render_review_value_row(
			__( 'Nightly review item', 'npcink-governance-core' ),
			array(
				'action_id'               => (string) ( $first_item['action_id'] ?? '' ),
				'title'                   => (string) ( $first_item['title'] ?? '' ),
				'object_type'             => (string) ( $first_item['object_type'] ?? '' ),
				'object_id'               => (string) ( $first_item['object_id'] ?? '' ),
				'score'                   => $first_item['score'] ?? null,
				'severity'                => (string) ( $first_item['severity'] ?? '' ),
				'reason_codes'            => is_array( $first_item['reason_codes'] ?? null ) ? array_values( $first_item['reason_codes'] ) : array(),
				'recommended_next_action' => (string) ( $first_item['recommended_next_action'] ?? '' ),
				'evidence_ref_count'      => absint( $nightly_review['evidence_ref_count'] ?? 0 ),
			)
		);

		$source_summary = array(
			'source_surface'           => (string) ( $nightly_review['source_surface'] ?? 'toolbox_morning_brief' ),
			'source_status'            => (string) ( $nightly_review['source_status'] ?? '' ),
			'cloud_run_id'             => (string) ( $nightly_review['cloud_run_id'] ?? '' ),
			'selected_review_item_ids' => is_array( $nightly_review['selected_review_item_ids'] ?? null ) ? array_values( $nightly_review['selected_review_item_ids'] ) : array(),
			'selected_item_count'      => count( $selected_items ),
			'evidence_ref_count'       => ! empty( $evidence_refs ) ? count( $evidence_refs ) : absint( $nightly_review['evidence_ref_count'] ?? 0 ),
			'completed_draft_submitted' => $completed_submitted ? __( 'yes', 'npcink-governance-core' ) : __( 'no', 'npcink-governance-core' ),
		);
		if ( null !== $proposal_ready ) {
			$source_summary['proposal_ready'] = $proposal_ready ? __( 'yes', 'npcink-governance-core' ) : __( 'no', 'npcink-governance-core' );
		}

		$this->render_review_value_row(
			__( 'Morning Brief source', 'npcink-governance-core' ),
			$source_summary
		);

		if ( ! empty( $selected_items ) ) {
			$this->render_review_value_row(
				__( 'Selected review items', 'npcink-governance-core' ),
				array_slice( $selected_items, 0, 5 )
			);
		}

		if ( ! empty( $evidence_refs ) ) {
			$this->render_review_value_row(
				__( 'Evidence refs', 'npcink-governance-core' ),
				array_slice( $evidence_refs, 0, 5 )
			);
		}

		if ( ! empty( $completed_draft ) ) {
			$this->render_review_value_row(
				__( 'Completed draft input', 'npcink-governance-core' ),
				$completed_draft
			);
		}

		$this->render_review_value_row(
			__( 'Morning Brief handoff', 'npcink-governance-core' ),
			array(
				'contract_version'             => (string) ( $nightly_review['contract_version'] ?? '' ),
				'cloud_run_id'                 => (string) ( $nightly_review['cloud_run_id'] ?? '' ),
				'handoff_surface'              => (string) ( $core_intake_package['handoff_surface'] ?? 'morning_brief_review_queue' ),
				'selected_item_count'          => count( $selected_items ),
				'operator_next_action'         => (string) ( $nightly_review['operator_next_action'] ?? '' ),
				'needs_input_resolution_owner' => (string) ( $nightly_review['needs_input_resolution_owner'] ?? '' ),
				'resubmission_required'        => ! empty( $nightly_review['resubmission_required'] ) ? __( 'yes', 'npcink-governance-core' ) : __( 'no', 'npcink-governance-core' ),
				'core_amendment_supported'     => ! empty( $nightly_review['core_amendment_supported'] ) ? __( 'yes', 'npcink-governance-core' ) : __( 'no', 'npcink-governance-core' ),
				'final_write_path'             => (string) ( $nightly_review['final_write_path'] ?? '' ),
				'direct_wordpress_write'       => ! empty( $nightly_review['direct_wordpress_write'] ) ? __( 'yes', 'npcink-governance-core' ) : __( 'no', 'npcink-governance-core' ),
				'cloud_scheduler_truth'        => ! empty( $nightly_review['cloud_scheduler_truth'] ) ? __( 'yes', 'npcink-governance-core' ) : __( 'no', 'npcink-governance-core' ),
			)
		);

		$this->render_review_value_row(
			__( 'Required next step', 'npcink-governance-core' ),
			$completed_submitted || true === $proposal_ready
				? __( 'Review the completed Morning Brief draft in Core, approve only if the selected evidence and draft are acceptable, then run commit preflight before Adapter execution. Core still does not generate or edit draft fields.', 'npcink-governance-core' )
				: __( 'Return to Toolbox Morning Brief, draft the title and content for the selected review item, then resubmit a complete Core proposal. Core does not generate or edit missing draft fields.', 'npcink-governance-core' )
		);
	}

	/**
	 * Renders one review-context value row.
	 *
	 * @param string $label Row label.
	 * @param mixed  $value Row value.
	 * @return void
	 */
	private function render_review_value_row( string $label, $value ): void {
		?>
		<tr>
			<th scope="row"><?php echo esc_html( $label ); ?></th>
			<td>
				<?php if ( is_array( $value ) || is_object( $value ) ) : ?>
					<pre style="max-height: 180px; overflow: auto; margin: 0;"><?php echo esc_html( (string) wp_json_encode( $value, JSON_PRETTY_PRINT ) ); ?></pre>
				<?php else : ?>
					<?php $this->render_review_inline_value( $value ); ?>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Renders a scalar review value inline.
	 *
	 * @param mixed $value Value.
	 * @return void
	 */
	private function render_review_inline_value( $value ): void {
		if ( is_bool( $value ) ) {
			echo esc_html( $value ? __( 'yes', 'npcink-governance-core' ) : __( 'no', 'npcink-governance-core' ) );
			return;
		}

		if ( null === $value || '' === $value ) {
			echo '&mdash;';
			return;
		}

		echo esc_html( is_scalar( $value ) ? (string) $value : (string) wp_json_encode( $value, JSON_PRETTY_PRINT ) );
	}

	/**
	 * Renders raw proposal payload behind an explicit disclosure.
	 *
	 * @param array<string,mixed> $proposal Proposal.
	 * @return void
	 */
	private function render_raw_proposal_payload( array $proposal ): void {
		?>
		<details class="npcink-governance-core-disclosure npcink-governance-core-max-wide npcink-governance-core-disclosure-top">
			<summary>
				<strong><?php echo esc_html__( 'Raw proposal payload', 'npcink-governance-core' ); ?></strong>
				<span class="npcink-governance-core-muted"><?php echo esc_html__( 'Caller, input, and preview JSON for troubleshooting.', 'npcink-governance-core' ); ?></span>
			</summary>
			<table class="widefat striped npcink-governance-core-table-spaced">
				<tbody>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Caller', 'npcink-governance-core' ); ?></th>
						<td><pre class="npcink-governance-core-code-block"><?php echo esc_html( (string) wp_json_encode( $proposal['caller'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) ); ?></pre></td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Input', 'npcink-governance-core' ); ?></th>
						<td><pre class="npcink-governance-core-code-block"><?php echo esc_html( (string) wp_json_encode( $proposal['input'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) ); ?></pre></td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Preview', 'npcink-governance-core' ); ?></th>
						<td><pre class="npcink-governance-core-code-block"><?php echo esc_html( (string) wp_json_encode( $proposal['preview'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) ); ?></pre></td>
					</tr>
				</tbody>
			</table>
		</details>
		<?php
	}

	/**
	 * Renders proposal audit timeline.
	 *
	 * @param array<int,array<string,mixed>> $events Audit events.
	 * @return void
	 */
	private function render_audit_timeline( array $events, bool $open = false ): void {
		unset( $open );
		$this->render_audit_lifecycle_summary( $events );
		?>
		<details class="npcink-governance-core-disclosure npcink-governance-core-max-wide npcink-governance-core-disclosure-top">
			<summary>
				<strong><?php echo esc_html__( 'Full audit timeline', 'npcink-governance-core' ); ?></strong>
				<span class="npcink-governance-core-muted"><?php echo esc_html__( 'Complete event table with actor and technical attribution.', 'npcink-governance-core' ); ?></span>
			</summary>
			<table class="widefat striped npcink-governance-core-table-spaced">
				<thead>
					<tr>
						<th scope="col"><?php echo esc_html__( 'Time', 'npcink-governance-core' ); ?></th>
						<th scope="col"><?php echo esc_html__( 'Event', 'npcink-governance-core' ); ?></th>
						<th scope="col"><?php echo esc_html__( 'Actor', 'npcink-governance-core' ); ?></th>
						<th scope="col"><?php echo esc_html__( 'Detail', 'npcink-governance-core' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $events ) ) : ?>
						<tr>
							<td colspan="4"><?php echo esc_html__( 'No audit events recorded for this proposal yet.', 'npcink-governance-core' ); ?></td>
						</tr>
					<?php endif; ?>
					<?php foreach ( $events as $event ) : ?>
						<tr>
							<td><?php echo esc_html( $this->display_datetime( (string) $event['created_at'] ) ); ?></td>
							<td><code><?php echo esc_html( (string) $event['event_name'] ); ?></code></td>
							<td><?php echo esc_html( (string) $event['actor_id'] ); ?></td>
							<td><?php $this->render_audit_detail( $event ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</details>
		<?php
	}

	/**
	 * Renders compact lifecycle evidence before the full audit table.
	 *
	 * @param array<int,array<string,mixed>> $events Audit events.
	 * @return void
	 */
	private function render_audit_lifecycle_summary( array $events ): void {
		$steps = $this->audit_lifecycle_steps( $events );
		?>
		<section class="npcink-governance-core-lifecycle-summary npcink-governance-core-max-wide" aria-label="<?php echo esc_attr__( 'Lifecycle summary', 'npcink-governance-core' ); ?>">
			<h3><?php echo esc_html__( 'Lifecycle summary', 'npcink-governance-core' ); ?></h3>
			<?php if ( empty( $steps ) ) : ?>
				<p class="npcink-governance-core-muted"><?php echo esc_html__( 'No audit events recorded for this proposal yet.', 'npcink-governance-core' ); ?></p>
			<?php else : ?>
				<ol class="npcink-governance-core-lifecycle-steps">
					<?php foreach ( $steps as $step ) : ?>
						<li>
							<strong><?php echo esc_html( $step['label'] ); ?></strong>
							<span><?php echo esc_html( $step['time'] ); ?></span>
						</li>
					<?php endforeach; ?>
				</ol>
			<?php endif; ?>
		</section>
		<?php
	}

	/**
	 * Returns compact lifecycle steps from audit events.
	 *
	 * @param array<int,array<string,mixed>> $events Audit events.
	 * @return array<int,array{label:string,time:string}>
	 */
	private function audit_lifecycle_steps( array $events ): array {
		$steps = array();

		foreach ( $events as $event ) {
			$event_name = (string) ( $event['event_name'] ?? '' );
			if ( '' === $event_name || in_array( $event_name, array( 'proposal.viewed', 'proposal.listed' ), true ) ) {
				continue;
			}

			$steps[] = array(
				'label' => $this->audit_event_label( $event_name ),
				'time'  => $this->display_datetime( (string) ( $event['created_at'] ?? '' ) ),
			);
		}

		return $steps;
	}

	/**
	 * Renders filtered governance audit events.
	 *
	 * @param array<int,array<string,mixed>> $events Audit events.
	 * @param array<string,mixed>            $filters Active filters.
	 * @param int                            $total Total matching events.
	 * @return void
	 */
	private function render_governance_audit( array $events, array $filters, int $total ): void {
		?>
		<h2><?php echo esc_html__( 'Activity Log', 'npcink-governance-core' ); ?></h2>
		<p><?php echo esc_html__( 'Recent approval and access activity. Use the search bar for common lookups; open advanced filters only when tracing a specific technical request.', 'npcink-governance-core' ); ?></p>
		<form class="npcink-governance-core-audit-filter-shell npcink-governance-core-max-wide" method="get">
			<input type="hidden" name="page" value="npcink-governance-core" />
			<input type="hidden" name="view" value="audit" />
			<div class="npcink-governance-core-audit-filter-toolbar">
				<label class="npcink-governance-core-audit-search-field" for="npcink-governance-core-audit-search">
					<span class="npcink-governance-core-block-label"><?php echo esc_html__( 'Search activity', 'npcink-governance-core' ); ?></span>
					<input id="npcink-governance-core-audit-search" class="regular-text" type="search" name="audit_search" value="<?php echo esc_attr( (string) $filters['search'] ); ?>" placeholder="<?php echo esc_attr__( 'Proposal, event, ability, client, or correlation ID', 'npcink-governance-core' ); ?>" />
				</label>
				<label for="npcink-governance-core-audit-event">
					<span class="npcink-governance-core-block-label"><?php echo esc_html__( 'Event type', 'npcink-governance-core' ); ?></span>
					<select id="npcink-governance-core-audit-event" name="audit_event_name">
						<?php foreach ( $this->audit_event_filter_options() as $value => $label ) : ?>
							<option value="<?php echo esc_attr( (string) $value ); ?>" <?php selected( (string) $filters['event_name'], (string) $value ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<label for="npcink-governance-core-audit-range">
					<span class="npcink-governance-core-block-label"><?php echo esc_html__( 'Time range', 'npcink-governance-core' ); ?></span>
					<select id="npcink-governance-core-audit-range" name="audit_time_range">
						<?php foreach ( $this->audit_time_range_options() as $value => $label ) : ?>
							<option value="<?php echo esc_attr( (string) $value ); ?>" <?php selected( (string) $filters['time_range'], (string) $value ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<label for="npcink-governance-core-audit-limit">
					<span class="npcink-governance-core-block-label"><?php echo esc_html__( 'Per page', 'npcink-governance-core' ); ?></span>
					<select id="npcink-governance-core-audit-limit" name="audit_limit">
						<?php foreach ( array( 25, 50, 100, 200 ) as $limit ) : ?>
							<option value="<?php echo esc_attr( (string) $limit ); ?>" <?php selected( (int) $filters['limit'], $limit ); ?>><?php echo esc_html( (string) $limit ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<label class="npcink-governance-core-audit-read-toggle">
					<input type="checkbox" name="audit_include_read_events" value="1" <?php checked( ! empty( $filters['include_read_events'] ) ); ?> />
					<span><?php echo esc_html__( 'Include list/view noise events', 'npcink-governance-core' ); ?></span>
				</label>
				<div class="npcink-governance-core-audit-filter-actions">
					<button type="submit" class="button button-primary"><?php echo esc_html__( 'Apply filters', 'npcink-governance-core' ); ?></button>
					<a class="button" href="<?php echo esc_url( $this->view_url( 'audit' ) ); ?>"><?php echo esc_html__( 'Reset', 'npcink-governance-core' ); ?></a>
				</div>
			</div>
			<details class="npcink-governance-core-disclosure npcink-governance-core-audit-advanced" <?php echo $this->has_active_audit_advanced_filters( $filters ) ? 'open' : ''; ?>>
				<summary>
					<strong><?php echo esc_html__( 'Advanced filters', 'npcink-governance-core' ); ?></strong>
					<span class="npcink-governance-core-muted"><?php echo esc_html__( 'Narrow by exact proposal, ability, client, caller, or correlation values.', 'npcink-governance-core' ); ?></span>
				</summary>
				<div class="npcink-governance-core-audit-advanced-grid">
					<label for="npcink-governance-core-audit-proposal">
						<span class="npcink-governance-core-block-label"><?php echo esc_html__( 'Proposal ID', 'npcink-governance-core' ); ?></span>
						<input id="npcink-governance-core-audit-proposal" class="regular-text" type="text" name="audit_proposal_id" value="<?php echo esc_attr( (string) $filters['proposal_id'] ); ?>" />
					</label>
					<label for="npcink-governance-core-audit-ability">
						<span class="npcink-governance-core-block-label"><?php echo esc_html__( 'Ability ID', 'npcink-governance-core' ); ?></span>
						<input id="npcink-governance-core-audit-ability" class="regular-text" type="text" name="audit_ability_id" value="<?php echo esc_attr( (string) $filters['ability_id'] ); ?>" placeholder="npcink-abilities-toolkit/create-draft" />
					</label>
					<label for="npcink-governance-core-audit-app">
						<span class="npcink-governance-core-block-label"><?php echo esc_html__( 'App ID', 'npcink-governance-core' ); ?></span>
						<input id="npcink-governance-core-audit-app" class="regular-text" type="text" name="audit_app_id" value="<?php echo esc_attr( (string) $filters['app_id'] ); ?>" />
					</label>
					<label for="npcink-governance-core-audit-caller">
						<span class="npcink-governance-core-block-label"><?php echo esc_html__( 'Caller type', 'npcink-governance-core' ); ?></span>
						<select id="npcink-governance-core-audit-caller" name="audit_caller_type">
							<?php foreach ( $this->audit_caller_type_filter_options() as $value => $label ) : ?>
								<option value="<?php echo esc_attr( (string) $value ); ?>" <?php selected( (string) $filters['caller_type'], (string) $value ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
					<label for="npcink-governance-core-audit-correlation">
						<span class="npcink-governance-core-block-label"><?php echo esc_html__( 'Correlation ID', 'npcink-governance-core' ); ?></span>
						<input id="npcink-governance-core-audit-correlation" class="regular-text" type="text" name="audit_correlation_id" value="<?php echo esc_attr( (string) $filters['correlation_id'] ); ?>" />
					</label>
				</div>
			</details>
		</form>
		<?php $this->render_audit_filter_chips( $filters ); ?>
		<?php $this->render_audit_table_nav( $total, (int) $filters['page'], (int) $filters['limit'], $this->audit_query_args( $filters ) ); ?>
		<table class="widefat striped npcink-governance-core-audit-table npcink-governance-core-max-wide">
			<thead>
				<tr>
					<th scope="col"><?php echo esc_html__( 'Time', 'npcink-governance-core' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'Activity', 'npcink-governance-core' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'Request', 'npcink-governance-core' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'Context', 'npcink-governance-core' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'Details', 'npcink-governance-core' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $events ) ) : ?>
					<tr>
						<td colspan="5"><?php echo esc_html__( 'No activity matches the current filters.', 'npcink-governance-core' ); ?></td>
					</tr>
				<?php endif; ?>
				<?php foreach ( $events as $event ) : ?>
					<?php
					$proposal_id = (string) ( $event['proposal_id'] ?? '' );
					$display_id  = '' !== $proposal_id ? Proposal_Repository::display_id_for_proposal_id( $proposal_id ) : '';
					?>
					<tr>
						<td class="npcink-governance-core-audit-time"><?php echo esc_html( $this->display_datetime( (string) $event['created_at'] ) ); ?></td>
						<td class="npcink-governance-core-audit-activity">
							<strong><?php echo esc_html( $this->audit_event_label( (string) $event['event_name'] ) ); ?></strong>
						</td>
						<td class="npcink-governance-core-audit-request">
							<?php if ( '' !== $proposal_id ) : ?>
								<a href="<?php echo esc_url( $this->detail_url( $proposal_id ) ); ?>"><code class="npcink-governance-core-display-id"><?php echo esc_html( $display_id ); ?></code></a>
							<?php else : ?>
								<?php echo esc_html__( 'System', 'npcink-governance-core' ); ?>
							<?php endif; ?>
						</td>
						<td class="npcink-governance-core-audit-context"><?php $this->render_audit_context_summary( $event ); ?></td>
						<td class="npcink-governance-core-audit-detail-cell">
							<details class="npcink-governance-core-audit-row-details">
								<summary class="button button-small"><?php echo esc_html__( 'Details', 'npcink-governance-core' ); ?></summary>
								<?php $this->render_audit_detail( $event ); ?>
							</details>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php $this->render_audit_table_nav( $total, (int) $filters['page'], (int) $filters['limit'], $this->audit_query_args( $filters ) ); ?>
		<?php
	}

	/**
	 * Renders compact audit metadata for the table context column.
	 *
	 * @param array<string,mixed> $event Audit event.
	 * @return void
	 */
	private function render_audit_context_summary( array $event ): void {
		$metadata       = is_array( $event['metadata'] ?? null ) ? $event['metadata'] : array();
		$auth           = is_array( $metadata['auth'] ?? null ) ? $metadata['auth'] : array();
		$proposal_id    = (string) ( $event['proposal_id'] ?? '' );
		$actor_id       = (string) ( $event['actor_id'] ?? '' );
		$ability_id     = (string) ( $metadata['ability_id'] ?? '' );
		$app_id         = (string) ( $auth['app_id'] ?? '' );
		$caller_type    = (string) ( $auth['caller_type'] ?? '' );
		$correlation_id = (string) ( $metadata['correlation_id'] ?? '' );
		$has_detail     = false;

		if ( '' === $proposal_id ) {
			$this->render_audit_badge( __( 'System event', 'npcink-governance-core' ) );
			$has_detail = true;
		}

		if ( '' !== $actor_id ) {
			$this->render_audit_badge(
				sprintf(
					/* translators: %s: actor id. */
					__( 'Actor: %s', 'npcink-governance-core' ),
					$actor_id
				)
			);
			$has_detail = true;
		}

		if ( '' !== $ability_id ) {
			$this->render_audit_badge(
				sprintf(
					/* translators: %s: ability id. */
					__( 'Ability: %s', 'npcink-governance-core' ),
					$this->audit_short_ability_label( $ability_id )
				)
			);
			$has_detail = true;
		}

		if ( '' !== $app_id || '' !== $caller_type ) {
			$this->render_audit_badge(
				sprintf(
					/* translators: 1: app id, 2: caller type. */
					__( 'App: %1$s / %2$s', 'npcink-governance-core' ),
					'' !== $app_id ? $this->compact_identifier( $app_id ) : __( 'unknown', 'npcink-governance-core' ),
					'' !== $caller_type ? $caller_type : __( 'unknown', 'npcink-governance-core' )
				)
			);
			$has_detail = true;
		}

		if ( '' !== $correlation_id ) {
			$this->render_audit_badge(
				sprintf(
					/* translators: %s: correlation id. */
					__( 'Correlation: %s', 'npcink-governance-core' ),
					$this->compact_identifier( $correlation_id )
				)
			);
			$has_detail = true;
		}

		if ( ! $has_detail ) {
			echo esc_html__( 'No extra context', 'npcink-governance-core' );
		}
	}

	/**
	 * Renders full audit metadata behind the row disclosure.
	 *
	 * @param array<string,mixed> $event Audit event.
	 * @return void
	 */
	private function render_audit_detail( array $event ): void {
		$rows = $this->audit_detail_rows( $event );
		?>
		<table class="widefat npcink-governance-core-audit-detail-table">
			<tbody>
				<?php foreach ( $rows as $row ) : ?>
					<tr>
						<th scope="row"><?php echo esc_html( $row['label'] ); ?></th>
						<td><code><?php echo esc_html( $row['value'] ); ?></code></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Returns full audit metadata rows for the row disclosure.
	 *
	 * @param array<string,mixed> $event Audit event.
	 * @return array<int,array{label:string,value:string}>
	 */
	private function audit_detail_rows( array $event ): array {
		$metadata       = is_array( $event['metadata'] ?? null ) ? $event['metadata'] : array();
		$auth           = is_array( $metadata['auth'] ?? null ) ? $metadata['auth'] : array();
		$rows           = array();
		$proposal_id    = (string) ( $event['proposal_id'] ?? '' );
		$actor_id       = (string) ( $event['actor_id'] ?? '' );
		$ability_id     = (string) ( $metadata['ability_id'] ?? '' );
		$app_id         = (string) ( $auth['app_id'] ?? '' );
		$caller_type    = (string) ( $auth['caller_type'] ?? '' );
		$scope          = (string) ( $auth['scope'] ?? '' );
		$scope_decision = (string) ( $auth['scope_decision'] ?? '' );
		$correlation_id = (string) ( $metadata['correlation_id'] ?? '' );

		$candidates = array(
			array( __( 'Event name', 'npcink-governance-core' ), (string) ( $event['event_name'] ?? '' ) ),
			array( __( 'Proposal ID', 'npcink-governance-core' ), $proposal_id ),
			array( __( 'Actor', 'npcink-governance-core' ), $actor_id ),
			array( __( 'Ability ID', 'npcink-governance-core' ), $ability_id ),
			array( __( 'App ID', 'npcink-governance-core' ), $app_id ),
			array( __( 'Caller type', 'npcink-governance-core' ), $caller_type ),
			array( __( 'Scope', 'npcink-governance-core' ), $scope ),
			array( __( 'Scope decision', 'npcink-governance-core' ), $scope_decision ),
			array( __( 'Correlation ID', 'npcink-governance-core' ), $correlation_id ),
		);

		foreach ( $candidates as $candidate ) {
			if ( '' === $candidate[1] ) {
				continue;
			}

			$rows[] = array(
				'label' => $candidate[0],
				'value' => $candidate[1],
			);
		}

		if ( empty( $rows ) ) {
			$rows[] = array(
				'label' => __( 'Context', 'npcink-governance-core' ),
				'value' => __( 'No extra context', 'npcink-governance-core' ),
			);
		}

		return $rows;
	}

	/**
	 * Returns a compact user-facing ability label.
	 *
	 * @param string $ability_id Ability identifier.
	 * @return string
	 */
	private function audit_short_ability_label( string $ability_id ): string {
		$ability_id = trim( $ability_id );
		if ( false !== strpos( $ability_id, '/' ) ) {
			$parts = explode( '/', $ability_id );
			$last  = (string) end( $parts );

			if ( '' !== $last ) {
				return $last;
			}
		}

		return $this->compact_identifier( $ability_id );
	}

	/**
	 * Renders one audit detail badge.
	 *
	 * @param string $label Badge label.
	 * @return void
	 */
	private function render_audit_badge( string $label ): void {
		?>
		<code class="npcink-governance-core-audit-badge"><?php echo esc_html( $label ); ?></code>
		<?php
	}

	/**
	 * Returns a user-facing audit event label.
	 *
	 * @param string $event_name Raw event name.
	 * @return string
	 */
	private function audit_event_label( string $event_name ): string {
		$labels = array(
			'proposal.created'        => __( 'Request created', 'npcink-governance-core' ),
			'proposal.policy_evaluated' => __( 'Policy checked', 'npcink-governance-core' ),
			'proposal.auto_approved'  => __( 'Request auto-approved', 'npcink-governance-core' ),
			'proposal.approved'       => __( 'Request approved', 'npcink-governance-core' ),
			'proposal.rejected'       => __( 'Request rejected', 'npcink-governance-core' ),
			'proposal.expired'        => __( 'Request expired', 'npcink-governance-core' ),
			'proposal.archived'       => __( 'Request archived', 'npcink-governance-core' ),
			'proposal.reopened'       => __( 'Request reopened', 'npcink-governance-core' ),
			'proposal.deduplicated'   => __( 'Duplicate request reused', 'npcink-governance-core' ),
			'proposal.quota_blocked'  => __( 'Request quota blocked', 'npcink-governance-core' ),
			'proposal.executed'       => __( 'Request executed', 'npcink-governance-core' ),
			'proposal.execution_failed' => __( 'Execution failed', 'npcink-governance-core' ),
			'commit.preflighted'      => __( 'Commit preflight checked', 'npcink-governance-core' ),
			'app.created'             => __( 'Client access created', 'npcink-governance-core' ),
			'app.revoked'             => __( 'Client access revoked', 'npcink-governance-core' ),
			'app.scope_denied'        => __( 'Client access denied', 'npcink-governance-core' ),
			'app.rate_limited'        => __( 'Client rate limited', 'npcink-governance-core' ),
		);

		return (string) ( $labels[ $event_name ] ?? $event_name );
	}

	/**
	 * Returns event filter options for the activity log toolbar.
	 *
	 * @return array<string,string>
	 */
	private function audit_event_filter_options(): array {
		return array(
			''                            => __( 'All events', 'npcink-governance-core' ),
			'proposal.created'            => __( 'Request created', 'npcink-governance-core' ),
			'proposal.approved'           => __( 'Request approved', 'npcink-governance-core' ),
			'proposal.rejected'           => __( 'Request rejected', 'npcink-governance-core' ),
			'commit.preflighted'          => __( 'Commit preflight checked', 'npcink-governance-core' ),
			'proposal.executed'           => __( 'Request executed', 'npcink-governance-core' ),
			'proposal.execution_failed'   => __( 'Execution failed', 'npcink-governance-core' ),
			'proposal.viewed'             => __( 'Proposal viewed', 'npcink-governance-core' ),
			'proposal.listed'             => __( 'Proposal listed', 'npcink-governance-core' ),
			'app.created'                 => __( 'Client access created', 'npcink-governance-core' ),
			'app.revoked'                 => __( 'Client access revoked', 'npcink-governance-core' ),
			'app.scope_denied'            => __( 'Client access denied', 'npcink-governance-core' ),
			'app.rate_limited'            => __( 'Client rate limited', 'npcink-governance-core' ),
		);
	}

	/**
	 * Returns caller type filter options for advanced activity filters.
	 *
	 * @return array<string,string>
	 */
	private function audit_caller_type_filter_options(): array {
		return array(
			''                 => __( 'All callers', 'npcink-governance-core' ),
			'external_app'     => __( 'External app', 'npcink-governance-core' ),
			'product_adapter'  => __( 'Product adapter', 'npcink-governance-core' ),
			'openclaw_adapter' => __( 'OpenClaw Adapter', 'npcink-governance-core' ),
			'mcp_adapter'      => __( 'MCP adapter', 'npcink-governance-core' ),
			'system'           => __( 'System', 'npcink-governance-core' ),
		);
	}

	/**
	 * Returns activity time range filter options.
	 *
	 * @return array<string,string>
	 */
	private function audit_time_range_options(): array {
		return array(
			'24h' => __( 'Last 24 hours', 'npcink-governance-core' ),
			'7d'  => __( 'Last 7 days', 'npcink-governance-core' ),
			'30d' => __( 'Last 30 days', 'npcink-governance-core' ),
			'all' => __( 'All time', 'npcink-governance-core' ),
		);
	}

	/**
	 * Returns a bounded activity time range key from the request.
	 *
	 * @return string
	 */
	private function audit_time_range_from_request(): string {
		$range = $this->admin_query_key( 'audit_time_range', '30d' );
		return array_key_exists( $range, $this->audit_time_range_options() ) ? $range : '30d';
	}

	/**
	 * Returns UTC created-after timestamp for an activity time range.
	 *
	 * @param string $range Time range key.
	 * @return string
	 */
	private function audit_created_after_for_range( string $range ): string {
		$seconds = array(
			'24h' => DAY_IN_SECONDS,
			'7d'  => 7 * DAY_IN_SECONDS,
			'30d' => 30 * DAY_IN_SECONDS,
		);

		if ( ! isset( $seconds[ $range ] ) ) {
			return '';
		}

		return gmdate( 'Y-m-d H:i:s', time() - $seconds[ $range ] );
	}

	/**
	 * Returns governance audit filters from query args.
	 *
	 * @return array<string,mixed>
	 */
	private function audit_filters_from_request(): array {
		$include_read_events = $this->admin_query_bool( 'audit_include_read_events' );
		$page                = $this->page_from_request( 'audit_page' );
		$limit               = max( 1, min( 200, $this->admin_query_absint( 'audit_limit', self::AUDIT_PAGE_SIZE ) ) );
		$time_range          = $this->audit_time_range_from_request();
		$filters             = array(
			'search'         => $this->admin_query_text( 'audit_search' ),
			'proposal_id'    => $this->admin_query_text( 'audit_proposal_id' ),
			'event_name'     => $this->admin_query_text( 'audit_event_name' ),
			'ability_id'     => $this->admin_query_text( 'audit_ability_id' ),
			'app_id'         => $this->admin_query_text( 'audit_app_id' ),
			'caller_type'    => $this->admin_query_key( 'audit_caller_type' ),
			'correlation_id' => $this->admin_query_text( 'audit_correlation_id' ),
			'time_range'     => $time_range,
			'created_after'  => $this->audit_created_after_for_range( $time_range ),
			'limit'          => $limit,
			'page'           => $page,
			'offset'         => $this->offset_for_page( $page, $limit ),
			'include_read_events' => $include_read_events,
		);

		if ( ! $include_read_events ) {
			$filters['exclude_event_names'] = $this->low_value_audit_events();
		}

		return $filters;
	}

	/**
	 * Returns whether the audit filter disclosure should open by default.
	 *
	 * @param array<string,mixed> $filters Audit filters.
	 * @return bool
	 */
	private function has_active_audit_filters( array $filters ): bool {
		foreach ( array( 'search', 'proposal_id', 'event_name', 'ability_id', 'app_id', 'caller_type', 'correlation_id' ) as $key ) {
			if ( '' !== (string) ( $filters[ $key ] ?? '' ) ) {
				return true;
			}
		}

		return '30d' !== (string) ( $filters['time_range'] ?? '30d' ) || self::AUDIT_PAGE_SIZE !== (int) ( $filters['limit'] ?? self::AUDIT_PAGE_SIZE ) || ! empty( $filters['include_read_events'] );
	}

	/**
	 * Returns whether the advanced activity filter disclosure should open.
	 *
	 * @param array<string,mixed> $filters Audit filters.
	 * @return bool
	 */
	private function has_active_audit_advanced_filters( array $filters ): bool {
		foreach ( array( 'proposal_id', 'ability_id', 'app_id', 'caller_type', 'correlation_id' ) as $key ) {
			if ( '' !== (string) ( $filters[ $key ] ?? '' ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Renders active activity filter chips.
	 *
	 * @param array<string,mixed> $filters Audit filters.
	 * @return void
	 */
	private function render_audit_filter_chips( array $filters ): void {
		$chips = $this->audit_filter_chips( $filters );
		if ( empty( $chips ) ) {
			return;
		}
		?>
		<ul class="npcink-governance-core-filter-list npcink-governance-core-audit-filter-chips npcink-governance-core-max-wide" aria-label="<?php echo esc_attr__( 'Active activity filters', 'npcink-governance-core' ); ?>">
			<?php foreach ( $chips as $chip ) : ?>
				<li>
					<span><?php echo esc_html( $chip['label'] ); ?>: <strong><?php echo esc_html( $chip['value'] ); ?></strong></span>
					<a href="<?php echo esc_url( $chip['url'] ); ?>" aria-label="<?php echo esc_attr( $chip['clear_label'] ); ?>">&times;</a>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php
	}

	/**
	 * Returns active activity filter chip data.
	 *
	 * @param array<string,mixed> $filters Audit filters.
	 * @return array<int,array{label:string,value:string,url:string,clear_label:string}>
	 */
	private function audit_filter_chips( array $filters ): array {
		$chips   = array();
		$options = array(
			'event_name'  => $this->audit_event_filter_options(),
			'caller_type' => $this->audit_caller_type_filter_options(),
			'time_range'  => $this->audit_time_range_options(),
		);

		foreach (
			array(
				'search'         => __( 'Search', 'npcink-governance-core' ),
				'event_name'     => __( 'Event type', 'npcink-governance-core' ),
				'time_range'     => __( 'Time range', 'npcink-governance-core' ),
				'proposal_id'    => __( 'Proposal ID', 'npcink-governance-core' ),
				'ability_id'     => __( 'Ability ID', 'npcink-governance-core' ),
				'app_id'         => __( 'App ID', 'npcink-governance-core' ),
				'caller_type'    => __( 'Caller type', 'npcink-governance-core' ),
				'correlation_id' => __( 'Correlation ID', 'npcink-governance-core' ),
			) as $key => $label
		) {
			$value = (string) ( $filters[ $key ] ?? '' );
			if ( '' === $value || ( 'time_range' === $key && '30d' === $value ) ) {
				continue;
			}

			$display_value = (string) ( $options[ $key ][ $value ] ?? $value );
			$chips[]       = array(
				'label'       => $label,
				'value'       => $display_value,
				'url'         => $this->audit_clear_filter_url( $filters, $key ),
				'clear_label' => sprintf(
					/* translators: %s: filter label. */
					__( 'Clear %s filter', 'npcink-governance-core' ),
					$label
				),
			);
		}

		if ( ! empty( $filters['include_read_events'] ) ) {
			$chips[] = array(
				'label'       => __( 'Read events', 'npcink-governance-core' ),
				'value'       => __( 'Included', 'npcink-governance-core' ),
				'url'         => $this->audit_clear_filter_url( $filters, 'include_read_events' ),
				'clear_label' => __( 'Clear read events filter', 'npcink-governance-core' ),
			);
		}

		return $chips;
	}

	/**
	 * Returns a URL that clears one audit filter.
	 *
	 * @param array<string,mixed> $filters Audit filters.
	 * @param string              $filter_key Filter key.
	 * @return string
	 */
	private function audit_clear_filter_url( array $filters, string $filter_key ): string {
		$args    = $this->audit_query_args( $filters );
		$mapping = array(
			'search'              => 'audit_search',
			'event_name'          => 'audit_event_name',
			'time_range'          => 'audit_time_range',
			'proposal_id'         => 'audit_proposal_id',
			'ability_id'          => 'audit_ability_id',
			'app_id'              => 'audit_app_id',
			'caller_type'         => 'audit_caller_type',
			'correlation_id'      => 'audit_correlation_id',
			'include_read_events' => 'audit_include_read_events',
		);

		unset( $args['audit_page'] );
		if ( isset( $mapping[ $filter_key ] ) ) {
			unset( $args[ $mapping[ $filter_key ] ] );
		}

		return $this->admin_url( $args );
	}

	/**
	 * Returns low-value read events hidden by default from the admin audit.
	 *
	 * @return array<int,string>
	 */
	private function low_value_audit_events(): array {
		return array(
			'proposal.viewed',
			'proposal.listed',
			'capabilities.listed',
			'audit.listed',
			'app.listed',
		);
	}

	/**
	 * Returns preserved audit query args for pagination links.
	 *
	 * @param array<string,mixed> $filters Active filters.
	 * @return array<string,string>
	 */
	private function audit_query_args( array $filters ): array {
		$args = array(
			'view'             => 'audit',
			'audit_limit'      => (string) (int) ( $filters['limit'] ?? self::AUDIT_PAGE_SIZE ),
			'audit_time_range' => (string) ( $filters['time_range'] ?? '30d' ),
		);

		$mapping = array(
			'search'         => 'audit_search',
			'proposal_id'    => 'audit_proposal_id',
			'event_name'     => 'audit_event_name',
			'ability_id'     => 'audit_ability_id',
			'app_id'         => 'audit_app_id',
			'caller_type'    => 'audit_caller_type',
			'correlation_id' => 'audit_correlation_id',
		);

		foreach ( $mapping as $filter_key => $query_key ) {
			$value = (string) ( $filters[ $filter_key ] ?? '' );
			if ( '' !== $value ) {
				$args[ $query_key ] = $value;
			}
		}

		if ( ! empty( $filters['include_read_events'] ) ) {
			$args['audit_include_read_events'] = '1';
		}

		return $args;
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
		return $this->admin_url( array( 'proposal_id' => $proposal_id ) );
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
			admin_url( 'admin.php' )
		);
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
		);

		return (string) ( $messages[ $code ] ?? __( 'Proposal action could not be completed.', 'npcink-governance-core' ) );
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
