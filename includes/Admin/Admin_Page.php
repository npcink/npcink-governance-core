<?php
/**
 * Minimal admin page.
 *
 * @package MagickAICore
 */

namespace MagickAI\Core\Admin;

use MagickAI\Core\Audit\Audit_Log_Repository;
use MagickAI\Core\Capabilities\Ability_Registry_Adapter;
use MagickAI\Core\Governance\Approval_Policy_Evaluator;
use MagickAI\Core\Governance\Proposal_Repository;
use MagickAI\Core\Governance\Proposal_Service;
use MagickAI\Core\Media\Media_Derivative_Settings;
use MagickAI\Core\Security\App_Key_Repository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders a compact governance overview.
 */
final class Admin_Page {
	const PARENT_MENU_SLUG  = 'magick-ai';
	const MENU_SLUG         = 'magick-ai-core';
	const MENU_CAPABILITY   = 'manage_options';
	const REVIEW_PAGE_SIZE  = 20;
	const ARCHIVE_PAGE_SIZE = 20;
	const AUDIT_PAGE_SIZE   = 25;
	const APP_KEY_PAGE_SIZE = 10;
	const DATETIME_DISPLAY_FORMAT = 'Y-m-d H:i:s';
	const ADMIN_REQUEST_ACTION = 'magick_ai_core_admin_request';
	const ADMIN_REQUEST_NONCE  = 'magick_ai_core_nonce';

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
	 * Media derivative settings.
	 *
	 * @var Media_Derivative_Settings
	 */
	private $media_settings;

	/**
	 * Constructor.
	 *
	 * @param Ability_Registry_Adapter $abilities Ability adapter.
	 * @param Proposal_Repository      $proposals Proposal repository.
	 * @param Audit_Log_Repository     $audit Audit repository.
	 * @param Proposal_Service         $service Proposal service.
	 * @param App_Key_Repository       $apps App key repository.
	 * @param Media_Derivative_Settings $media_settings Media settings.
	 */
	public function __construct( Ability_Registry_Adapter $abilities, Proposal_Repository $proposals, Audit_Log_Repository $audit, Proposal_Service $service, App_Key_Repository $apps, Media_Derivative_Settings $media_settings ) {
		$this->abilities      = $abilities;
		$this->proposals      = $proposals;
		$this->audit          = $audit;
		$this->service        = $service;
		$this->apps           = $apps;
		$this->media_settings = $media_settings;
	}

	/**
	 * Registers admin menu.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'add_menu' ), 10 );
		add_action( 'admin_post_magick_ai_core_create_app_key', array( $this, 'handle_create_app_key' ) );
		add_action( 'admin_post_magick_ai_core_revoke_app_key', array( $this, 'handle_revoke_app_key' ) );
		add_action( 'admin_post_magick_ai_core_approve_proposal', array( $this, 'handle_approve' ) );
		add_action( 'admin_post_magick_ai_core_reject_proposal', array( $this, 'handle_reject' ) );
		add_action( 'admin_post_magick_ai_core_bulk_reject_proposals', array( $this, 'handle_bulk_reject' ) );
		add_action( 'admin_post_magick_ai_core_archive_proposal', array( $this, 'handle_archive' ) );
		add_action( 'admin_post_magick_ai_core_reopen_proposal', array( $this, 'handle_reopen' ) );
		add_action( 'admin_post_magick_ai_core_update_approval_policy', array( $this, 'handle_update_approval_policy' ) );
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
			__( 'Magick AI Core', 'magick-ai-core' ),
			__( 'Core', 'magick-ai-core' ),
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
			'magick-ai-core-admin',
			plugins_url( 'assets/admin.css', MAGICK_AI_CORE_FILE ),
			array(),
			MAGICK_AI_CORE_VERSION
		);
	}

	/**
	 * Ensures the shared Magick AI parent menu exists.
	 *
	 * @return void
	 */
	private function ensure_parent_menu(): void {
		if ( $this->has_parent_menu() ) {
			return;
		}

		add_menu_page(
			__( 'Magick AI', 'magick-ai-core' ),
			__( 'Magick AI', 'magick-ai-core' ),
			self::MENU_CAPABILITY,
			self::PARENT_MENU_SLUG,
			array( $this, 'render_overview' ),
			'dashicons-superhero',
			58
		);

		add_submenu_page(
			self::PARENT_MENU_SLUG,
			__( 'Magick AI Overview', 'magick-ai-core' ),
			__( 'Overview', 'magick-ai-core' ),
			self::MENU_CAPABILITY,
			self::PARENT_MENU_SLUG,
			array( $this, 'render_overview' ),
			0
		);
	}

	/**
	 * Returns whether another Magick AI plugin already created the parent menu.
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
	 * Renders the shared Magick AI overview page.
	 *
	 * @return void
	 */
	public function render_overview(): void {
		if ( ! current_user_can( self::MENU_CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'magick-ai-core' ) );
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Magick AI', 'magick-ai-core' ); ?></h1>
			<p><?php echo esc_html__( 'Local WordPress entry points for Magick AI governance, connections, cloud access, and ability packages.', 'magick-ai-core' ); ?></p>
			<h2><?php echo esc_html__( 'Installed Surfaces', 'magick-ai-core' ); ?></h2>
			<table class="widefat striped magick-ai-core-table-narrow">
				<tbody>
					<?php
					$this->render_overview_row( __( 'Core', 'magick-ai-core' ), __( 'Review proposals, approval decisions, commit preflight, audit, and Core app keys.', 'magick-ai-core' ), self::MENU_SLUG );
					$this->render_overview_row( __( 'Adapter', 'magick-ai-core' ), __( 'Connect OpenClaw through the Adapter surface.', 'magick-ai-core' ), 'magick-ai-adapter' );
					$this->render_overview_row( __( 'Abilities', 'magick-ai-core' ), __( 'Verify WordPress Abilities API packages and demo ability controls.', 'magick-ai-core' ), 'magick-ai-abilities' );
					$this->render_overview_row( __( 'Cloud Addon', 'magick-ai-core' ), __( 'Connect this site to Magick AI Cloud without moving local control-plane truth.', 'magick-ai-core' ), 'magick-ai-cloud-addon' );
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
					<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=' . $slug ) ); ?>"><?php echo esc_html__( 'Open', 'magick-ai-core' ); ?></a>
				<?php else : ?>
					<span class="magick-ai-core-muted"><?php echo esc_html__( 'Not installed', 'magick-ai-core' ); ?></span>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Returns whether a Magick AI submenu has been registered.
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
			wp_die( esc_html__( 'You do not have permission to access this page.', 'magick-ai-core' ) );
		}

		$this->service->expire_stale_pending();

		$summary        = $this->abilities->summary();
		$review_page    = $this->page_from_request( 'review_page' );
		$pending_count  = $this->proposals->count_by_status( Proposal_Repository::STATUS_PENDING );
		$review_page    = $this->bounded_page( $pending_count, $review_page, self::REVIEW_PAGE_SIZE );
		$pending        = $this->proposals->list_recent( self::REVIEW_PAGE_SIZE, Proposal_Repository::STATUS_PENDING, $this->offset_for_page( $review_page, self::REVIEW_PAGE_SIZE ) );
		$expired_count  = $this->proposals->count_by_status( Proposal_Repository::STATUS_EXPIRED );
		$archived_count = $this->proposals->count_by_status( Proposal_Repository::STATUS_ARCHIVED );
		$selected_id    = $this->admin_query_text( 'proposal_id' );
		$selected       = '' !== $selected_id ? $this->proposals->find( $selected_id ) : null;
		$view           = $this->admin_query_key( 'view' );
		$message        = $this->admin_query_key( 'magick_ai_core_message' );
		$error          = $this->admin_query_key( 'magick_ai_core_error' );
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Magick AI Core', 'magick-ai-core' ); ?></h1>
			<p><?php echo esc_html__( 'Governance review and audit for WordPress ability proposals.', 'magick-ai-core' ); ?></p>

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
					<p><?php echo esc_html__( 'Selected proposal was not found.', 'magick-ai-core' ); ?></p>
				</div>
				<?php $this->render_admin_tabs( 'review' ); ?>
				<?php $this->render_review_workbench( $summary, $pending_count, $expired_count, $archived_count, $pending, $review_page ); ?>
			<?php elseif ( 'audit' === $view ) : ?>
				<?php $audit_filters = $this->audit_filters_from_request(); ?>
				<?php $audit_total = $this->audit->count_filtered( $audit_filters ); ?>
				<?php $audit_filters = $this->bounded_audit_filters( $audit_filters, $audit_total ); ?>
				<?php $this->render_admin_tabs( 'audit' ); ?>
				<?php $this->render_governance_audit( $this->audit->list_filtered( $audit_filters ), $audit_filters, $audit_total ); ?>
			<?php elseif ( 'archive' === $view ) : ?>
				<?php $this->render_admin_tabs( 'archive' ); ?>
				<?php $this->render_archive_view(); ?>
			<?php elseif ( 'app-keys' === $view ) : ?>
				<?php $this->render_admin_tabs( '' ); ?>
				<?php $this->render_external_access(); ?>
			<?php elseif ( 'media-policy' === $view ) : ?>
				<?php $this->render_admin_tabs( 'media-policy' ); ?>
				<?php $this->render_media_policy_settings(); ?>
			<?php else : ?>
				<?php $this->render_admin_tabs( 'review' ); ?>
				<?php $this->render_review_workbench( $summary, $pending_count, $expired_count, $archived_count, $pending, $review_page ); ?>
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
				'label' => __( 'Review Queue', 'magick-ai-core' ),
				'url'   => $this->admin_url(),
			),
			'audit'    => array(
				'label' => __( 'Governance Audit', 'magick-ai-core' ),
				'url'   => $this->view_url( 'audit' ),
			),
			'archive'  => array(
				'label' => __( 'Expired / Archived', 'magick-ai-core' ),
				'url'   => $this->view_url( 'archive' ),
			),
			'media-policy' => array(
				'label' => __( 'Media Policy', 'magick-ai-core' ),
				'url'   => $this->view_url( 'media-policy' ),
			),
		);
		?>
		<nav class="nav-tab-wrapper magick-ai-core-tabs" aria-label="<?php echo esc_attr__( 'Core admin sections', 'magick-ai-core' ); ?>">
			<?php foreach ( $tabs as $key => $tab ) : ?>
				<a class="nav-tab <?php echo $active === $key ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( (string) $tab['url'] ); ?>" <?php echo $active === $key ? 'aria-current="page"' : ''; ?>>
					<?php echo esc_html( (string) $tab['label'] ); ?>
				</a>
			<?php endforeach; ?>
		</nav>
		<?php
	}

	/**
	 * Renders the default queue-first workbench.
	 *
	 * @param array<string,mixed>            $summary Ability summary.
	 * @param int                            $pending_count Pending proposal count.
	 * @param int                            $expired_count Expired proposal count.
	 * @param int                            $archived_count Archived proposal count.
	 * @param array<int,array<string,mixed>> $pending Pending proposals.
	 * @param int                            $page Current review page.
	 * @return void
	 */
	private function render_review_workbench( array $summary, int $pending_count, int $expired_count, int $archived_count, array $pending, int $page ): void {
		?>
		<?php $this->render_summary_strip( $summary, $pending_count, $expired_count, $archived_count ); ?>
		<?php $this->render_approval_policy_entry(); ?>
		<?php $this->render_pending_proposals( $pending, $pending_count, $page ); ?>
		<?php $this->render_recent_activity(); ?>
		<?php $this->render_advanced_access_entry(); ?>
		<?php
	}

	/**
	 * Renders the lightweight development approval policy setting.
	 *
	 * @return void
	 */
	private function render_approval_policy_entry(): void {
		$current = Approval_Policy_Evaluator::current_policy_mode();
		$labels  = array(
			Approval_Policy_Evaluator::MODE_MANUAL          => __( 'Manual', 'magick-ai-core' ),
			Approval_Policy_Evaluator::MODE_DRY_RUN_GUARDED => __( 'Dry-run guarded', 'magick-ai-core' ),
			Approval_Policy_Evaluator::MODE_LOCAL_GUARDED   => __( 'Local guarded', 'magick-ai-core' ),
		);
		?>
		<details class="magick-ai-core-disclosure magick-ai-core-max-wide">
			<summary>
				<strong><?php echo esc_html__( 'Development Approval Policy', 'magick-ai-core' ); ?></strong>
				<span class="magick-ai-core-muted">
					<?php
					printf(
						/* translators: %s: current policy mode. */
						esc_html__( 'Current mode: %s', 'magick-ai-core' ),
						esc_html( (string) ( $labels[ $current ] ?? $current ) )
					);
					?>
				</span>
			</summary>
			<form class="magick-ai-core-form-spaced" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="magick_ai_core_update_approval_policy" />
				<?php wp_nonce_field( 'magick_ai_core_update_approval_policy' ); ?>
				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row"><label for="magick-ai-core-approval-policy-mode"><?php echo esc_html__( 'Policy mode', 'magick-ai-core' ); ?></label></th>
							<td>
								<select id="magick-ai-core-approval-policy-mode" name="policy_mode">
									<?php foreach ( Approval_Policy_Evaluator::allowed_policy_modes() as $mode ) : ?>
										<option value="<?php echo esc_attr( $mode ); ?>" <?php selected( $current, $mode ); ?>>
											<?php echo esc_html( (string) ( $labels[ $mode ] ?? $mode ) ); ?>
										</option>
									<?php endforeach; ?>
								</select>
								<p class="description"><?php echo esc_html__( 'Local guarded only auto-approves trusted test-content cleanup trash batches. Destructive deletes, comments, terms, and published content updates remain manual.', 'magick-ai-core' ); ?></p>
							</td>
						</tr>
					</tbody>
				</table>
				<p><button type="submit" class="button button-secondary"><?php echo esc_html__( 'Save approval policy', 'magick-ai-core' ); ?></button></p>
			</form>
		</details>
		<?php
	}

	/**
	 * Renders local media derivative policy settings.
	 *
	 * @return void
	 */
	private function render_media_policy_settings(): void {
		$settings = $this->media_settings->get_all();
		?>
		<h2><?php echo esc_html__( 'Media Optimization Policy', 'magick-ai-core' ); ?></h2>
		<p class="magick-ai-core-copy-width"><?php echo esc_html__( 'Core stores the local site policy for optimized media derivatives. Toolbox may use these defaults for one-run handoffs, and Cloud Addon may execute them when available, but final WordPress writes still require local proposal governance.', 'magick-ai-core' ); ?></p>
		<form class="magick-ai-core-form-width" method="post" action="options.php">
			<?php settings_fields( 'magick_ai_core_media_derivative' ); ?>
			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Media optimization', 'magick-ai-core' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( Media_Derivative_Settings::OPTION_NAME ); ?>[enabled]" value="1" <?php checked( ! empty( $settings['enabled'] ) ); ?> />
								<?php echo esc_html__( 'Enable local media derivative policy', 'magick-ai-core' ); ?>
							</label>
							<p class="description"><?php echo esc_html__( 'This stores defaults only. It does not optimize files, approve proposals, or write attachment metadata.', 'magick-ai-core' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="magick-ai-core-media-format"><?php echo esc_html__( 'Output format', 'magick-ai-core' ); ?></label></th>
						<td>
							<select id="magick-ai-core-media-format" name="<?php echo esc_attr( Media_Derivative_Settings::OPTION_NAME ); ?>[target_format]">
								<?php foreach ( $this->media_settings->allowed_formats() as $format ) : ?>
									<option value="<?php echo esc_attr( $format ); ?>" <?php selected( (string) $settings['target_format'], $format ); ?>>
										<?php echo esc_html( strtoupper( $format ) ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="magick-ai-core-media-width"><?php echo esc_html__( 'Maximum width', 'magick-ai-core' ); ?></label></th>
						<td>
							<input id="magick-ai-core-media-width" type="number" min="320" max="7680" step="1" name="<?php echo esc_attr( Media_Derivative_Settings::OPTION_NAME ); ?>[max_width]" value="<?php echo esc_attr( (string) $settings['max_width'] ); ?>" />
							<span><?php echo esc_html__( 'px', 'magick-ai-core' ); ?></span>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="magick-ai-core-media-quality"><?php echo esc_html__( 'Quality', 'magick-ai-core' ); ?></label></th>
						<td>
							<input id="magick-ai-core-media-quality" type="number" min="1" max="100" step="1" name="<?php echo esc_attr( Media_Derivative_Settings::OPTION_NAME ); ?>[quality]" value="<?php echo esc_attr( (string) $settings['quality'] ); ?>" />
						</td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Watermark', 'magick-ai-core' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( Media_Derivative_Settings::OPTION_NAME ); ?>[watermark_enabled]" value="1" <?php checked( ! empty( $settings['watermark_enabled'] ) ); ?> />
								<?php echo esc_html__( 'Use image watermark when a logo attachment is configured', 'magick-ai-core' ); ?>
							</label>
							<p>
								<label for="magick-ai-core-watermark-attachment"><?php echo esc_html__( 'Logo attachment ID', 'magick-ai-core' ); ?></label><br />
								<input id="magick-ai-core-watermark-attachment" type="number" min="0" step="1" name="<?php echo esc_attr( Media_Derivative_Settings::OPTION_NAME ); ?>[watermark_attachment_id]" value="<?php echo esc_attr( (string) $settings['watermark_attachment_id'] ); ?>" />
							</p>
							<details>
								<summary><?php echo esc_html__( 'Watermark placement', 'magick-ai-core' ); ?></summary>
								<p>
									<label for="magick-ai-core-watermark-position"><?php echo esc_html__( 'Position', 'magick-ai-core' ); ?></label><br />
									<select id="magick-ai-core-watermark-position" name="<?php echo esc_attr( Media_Derivative_Settings::OPTION_NAME ); ?>[watermark_position]">
										<?php foreach ( $this->media_settings->allowed_watermark_positions() as $position ) : ?>
											<option value="<?php echo esc_attr( $position ); ?>" <?php selected( (string) $settings['watermark_position'], $position ); ?>>
												<?php echo esc_html( ucwords( str_replace( '_', ' ', $position ) ) ); ?>
											</option>
										<?php endforeach; ?>
									</select>
								</p>
								<p>
									<label for="magick-ai-core-watermark-opacity"><?php echo esc_html__( 'Opacity', 'magick-ai-core' ); ?></label><br />
									<input id="magick-ai-core-watermark-opacity" type="number" min="0" max="100" step="1" name="<?php echo esc_attr( Media_Derivative_Settings::OPTION_NAME ); ?>[watermark_opacity]" value="<?php echo esc_attr( (string) $settings['watermark_opacity'] ); ?>" />
									<span><?php echo esc_html__( '%', 'magick-ai-core' ); ?></span>
								</p>
								<p>
									<label for="magick-ai-core-watermark-scale"><?php echo esc_html__( 'Scale', 'magick-ai-core' ); ?></label><br />
									<input id="magick-ai-core-watermark-scale" type="number" min="1" max="100" step="1" name="<?php echo esc_attr( Media_Derivative_Settings::OPTION_NAME ); ?>[watermark_scale]" value="<?php echo esc_attr( (string) $settings['watermark_scale'] ); ?>" />
									<span><?php echo esc_html__( '%', 'magick-ai-core' ); ?></span>
								</p>
								<p>
									<label for="magick-ai-core-watermark-margin"><?php echo esc_html__( 'Margin', 'magick-ai-core' ); ?></label><br />
									<input id="magick-ai-core-watermark-margin" type="number" min="0" max="1000" step="1" name="<?php echo esc_attr( Media_Derivative_Settings::OPTION_NAME ); ?>[watermark_margin]" value="<?php echo esc_attr( (string) $settings['watermark_margin'] ); ?>" />
									<span><?php echo esc_html__( 'px', 'magick-ai-core' ); ?></span>
								</p>
							</details>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Execution preference', 'magick-ai-core' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( Media_Derivative_Settings::OPTION_NAME ); ?>[use_cloud_when_available]" value="1" <?php checked( ! empty( $settings['use_cloud_when_available'] ) ); ?> />
								<?php echo esc_html__( 'Use Cloud execution when Cloud Addon is installed and verified', 'magick-ai-core' ); ?>
							</label>
							<p class="description"><?php echo esc_html__( 'Cloud remains an optional runtime. Core keeps the policy and final local write decision.', 'magick-ai-core' ); ?></p>
						</td>
					</tr>
				</tbody>
			</table>
			<?php submit_button( __( 'Save media policy', 'magick-ai-core' ) ); ?>
		</form>
		<?php
	}

	/**
	 * Renders compact Core status.
	 *
	 * @param array<string,mixed> $summary Ability summary.
	 * @param int                 $pending_count Pending proposal count.
	 * @param int                 $expired_count Expired proposal count.
	 * @param int                 $archived_count Archived proposal count.
	 * @return void
	 */
	private function render_summary_strip( array $summary, int $pending_count, int $expired_count, int $archived_count ): void {
		?>
		<h2><?php echo esc_html__( 'Review Queue', 'magick-ai-core' ); ?></h2>
		<div class="magick-ai-core-status-strip">
			<?php $this->render_status_metric( __( 'Needs review', 'magick-ai-core' ), (string) $pending_count, true ); ?>
			<?php $this->render_status_metric( __( 'Expired', 'magick-ai-core' ), (string) $expired_count, false, false, $this->view_url( 'archive' ) ); ?>
			<?php $this->render_status_metric( __( 'Archived', 'magick-ai-core' ), (string) $archived_count, false, false, $this->view_url( 'archive' ) ); ?>
			<?php $this->render_status_metric( __( 'Available abilities', 'magick-ai-core' ), (string) $summary['count'] ); ?>
		</div>
		<?php
	}

	/**
	 * Renders one compact status metric.
	 *
	 * @param string $label Metric label.
	 * @param string $value Metric value.
	 * @param bool   $primary Whether this is the main metric.
	 * @param bool   $code Whether to render the value as code.
	 * @param string $url Optional metric link.
	 * @return void
	 */
	private function render_status_metric( string $label, string $value, bool $primary = false, bool $code = false, string $url = '' ): void {
		?>
		<div class="<?php echo esc_attr( $primary ? 'magick-ai-core-metric magick-ai-core-metric-primary' : 'magick-ai-core-metric' ); ?>">
			<div class="magick-ai-core-metric-label"><?php echo esc_html( $label ); ?></div>
			<div class="<?php echo esc_attr( $primary ? 'magick-ai-core-metric-value magick-ai-core-metric-value-primary' : 'magick-ai-core-metric-value' ); ?>">
				<?php if ( '' !== $url ) : ?>
					<a class="magick-ai-core-plain-link" href="<?php echo esc_url( $url ); ?>">
				<?php endif; ?>
				<?php if ( $code ) : ?>
					<code><?php echo esc_html( $value ); ?></code>
				<?php else : ?>
					<?php echo esc_html( $value ); ?>
				<?php endif; ?>
				<?php if ( '' !== $url ) : ?>
					</a>
				<?php endif; ?>
			</div>
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
		<h2><?php echo esc_html__( 'Needs Review', 'magick-ai-core' ); ?></h2>
		<p><?php echo esc_html( $this->pagination_summary( $total, $page, self::REVIEW_PAGE_SIZE ) ); ?></p>
		<form class="magick-ai-core-max-wide" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="magick_ai_core_bulk_reject_proposals" />
			<?php wp_nonce_field( 'magick_ai_core_bulk_reject_proposals' ); ?>
			<table class="widefat striped">
				<thead>
					<tr>
						<td class="check-column"><span class="screen-reader-text"><?php echo esc_html__( 'Select proposal', 'magick-ai-core' ); ?></span></td>
						<th scope="col"><?php echo esc_html__( 'Proposal', 'magick-ai-core' ); ?></th>
						<th scope="col"><?php echo esc_html__( 'Created', 'magick-ai-core' ); ?></th>
						<th scope="col"><?php echo esc_html__( 'Action', 'magick-ai-core' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $pending ) ) : ?>
						<tr>
							<td colspan="4"><?php echo esc_html__( 'No active proposals. Expired items are moved out of the review queue automatically.', 'magick-ai-core' ); ?></td>
						</tr>
					<?php endif; ?>
					<?php foreach ( $pending as $proposal ) : ?>
						<?php $proposal_id = (string) $proposal['proposal_id']; ?>
						<tr>
							<th scope="row" class="check-column">
								<input type="checkbox" name="proposal_ids[]" value="<?php echo esc_attr( $proposal_id ); ?>" aria-label="<?php echo esc_attr__( 'Select proposal', 'magick-ai-core' ); ?>" />
							</th>
							<td>
								<strong><?php echo esc_html( (string) ( $proposal['title'] ?: $proposal_id ) ); ?></strong><br />
								<span class="magick-ai-core-subtle">
									<?php echo esc_html__( 'Proposal ID:', 'magick-ai-core' ); ?>
									<a href="<?php echo esc_url( $this->detail_url( $proposal_id ) ); ?>"><code><?php echo esc_html( $proposal_id ); ?></code></a>
								</span><br />
								<span class="magick-ai-core-subtle">
									<?php echo esc_html__( 'Ability:', 'magick-ai-core' ); ?>
									<code><?php echo esc_html( (string) $proposal['ability_id'] ); ?></code>
								</span>
								<?php $this->render_pending_proposal_trace( $proposal ); ?>
							</td>
							<td><?php echo esc_html( $this->display_datetime( (string) $proposal['created_at'] ) ); ?></td>
							<td>
								<a class="button" href="<?php echo esc_url( $this->detail_url( $proposal_id ) ); ?>">
									<?php echo esc_html__( 'Review', 'magick-ai-core' ); ?>
								</a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php if ( ! empty( $pending ) ) : ?>
				<p class="magick-ai-core-inline-actions">
					<label class="magick-ai-core-flex-field">
						<?php echo esc_html__( 'Bulk rejection note', 'magick-ai-core' ); ?><br />
						<input type="text" class="large-text" name="note" value="<?php echo esc_attr__( 'Superseded by batch cleanup proposal.', 'magick-ai-core' ); ?>" />
					</label>
					<button type="submit" class="button">
						<?php echo esc_html__( 'Reject selected', 'magick-ai-core' ); ?>
					</button>
				</p>
			<?php endif; ?>
		</form>
		<?php $this->render_pagination( $total, $page, self::REVIEW_PAGE_SIZE, 'review_page', array() ); ?>
		<?php
	}

	/**
	 * Renders a compact source trace for one pending proposal row.
	 *
	 * @param array<string,mixed> $proposal Proposal.
	 * @return void
	 */
	private function render_pending_proposal_trace( array $proposal ): void {
		$trace = $this->pending_proposal_trace_parts( $proposal );

		if ( empty( $trace ) ) {
			return;
		}
		?>
		<br />
		<span class="magick-ai-core-muted">
			<?php echo esc_html__( 'Source:', 'magick-ai-core' ); ?>
			<?php echo esc_html( implode( ' · ', $trace ) ); ?>
		</span>
		<?php
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
				__( 'plan %s', 'magick-ai-core' ),
				$plan_ability_id
			);
		}

		$batch_id = (string) ( $caller['batch_id'] ?? '' );
		if ( '' !== $batch_id ) {
			$parts[] = sprintf(
				/* translators: %s: batch id. */
				__( 'batch %s', 'magick-ai-core' ),
				$batch_id
			);
		}

		$action_id = (string) ( $caller['action_id'] ?? '' );
		if ( '' !== $action_id ) {
			$parts[] = sprintf(
				/* translators: %s: action id. */
				__( 'action %s', 'magick-ai-core' ),
				$action_id
			);
		}

		$caller_type = (string) ( $auth['caller_type'] ?? $caller['caller_type'] ?? '' );
		if ( '' !== $caller_type ) {
			$parts[] = sprintf(
				/* translators: %s: caller type. */
				__( 'caller %s', 'magick-ai-core' ),
				$caller_type
			);
		}

		$app_id = (string) ( $auth['app_id'] ?? $caller['app_id'] ?? '' );
		if ( '' !== $app_id ) {
			$parts[] = sprintf(
				/* translators: %s: app id. */
				__( 'app %s', 'magick-ai-core' ),
				$app_id
			);
		}

		return array_slice( array_values( array_unique( $parts ) ), 0, 5 );
	}

	/**
	 * Renders a short activity list for the default workbench.
	 *
	 * @return void
	 */
	private function render_recent_activity(): void {
		$events = $this->audit->list_recent( 10 );
		?>
		<details class="magick-ai-core-disclosure magick-ai-core-max-wide magick-ai-core-disclosure-top">
			<summary>
				<strong><?php echo esc_html__( 'Recent Activity', 'magick-ai-core' ); ?></strong>
				<span class="magick-ai-core-muted"><?php echo esc_html__( 'Latest Core governance events. Full audit is in its own tab.', 'magick-ai-core' ); ?></span>
			</summary>
			<table class="widefat striped magick-ai-core-table-spaced">
				<thead>
					<tr>
						<th scope="col"><?php echo esc_html__( 'Time', 'magick-ai-core' ); ?></th>
						<th scope="col"><?php echo esc_html__( 'Event', 'magick-ai-core' ); ?></th>
						<th scope="col"><?php echo esc_html__( 'Proposal', 'magick-ai-core' ); ?></th>
						<th scope="col"><?php echo esc_html__( 'Actor', 'magick-ai-core' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $events ) ) : ?>
						<tr>
							<td colspan="4"><?php echo esc_html__( 'No recent governance activity.', 'magick-ai-core' ); ?></td>
						</tr>
					<?php endif; ?>
					<?php foreach ( $events as $event ) : ?>
						<?php $proposal_id = (string) ( $event['proposal_id'] ?? '' ); ?>
						<tr>
							<td><?php echo esc_html( $this->display_datetime( (string) $event['created_at'] ) ); ?></td>
							<td><code><?php echo esc_html( (string) $event['event_name'] ); ?></code></td>
							<td>
								<?php if ( '' !== $proposal_id ) : ?>
									<a href="<?php echo esc_url( $this->detail_url( $proposal_id ) ); ?>"><code><?php echo esc_html( $proposal_id ); ?></code></a>
								<?php else : ?>
									<?php echo esc_html__( 'System', 'magick-ai-core' ); ?>
								<?php endif; ?>
							</td>
							<td><?php echo esc_html( (string) $event['actor_id'] ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<p><a href="<?php echo esc_url( $this->view_url( 'audit' ) ); ?>"><?php echo esc_html__( 'Open full audit', 'magick-ai-core' ); ?></a></p>
		</details>
		<?php
	}

	/**
	 * Renders the low-frequency external access entry.
	 *
	 * @return void
	 */
	private function render_advanced_access_entry(): void {
		$active_count = $this->apps->count( 'active' );
		$last_used    = $this->apps->latest_last_used_at();
		?>
		<details class="magick-ai-core-disclosure magick-ai-core-max-wide magick-ai-core-disclosure-top">
			<summary>
				<strong><?php echo esc_html__( 'Advanced Access', 'magick-ai-core' ); ?></strong>
				<span class="magick-ai-core-muted"><?php echo esc_html__( 'Core app keys for trusted governance clients.', 'magick-ai-core' ); ?></span>
			</summary>
			<table class="widefat striped magick-ai-core-table-spaced">
				<tbody>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Active app keys', 'magick-ai-core' ); ?></th>
						<td><?php echo esc_html( (string) $active_count ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Last used', 'magick-ai-core' ); ?></th>
						<td><?php echo esc_html( '' !== $last_used ? $this->display_datetime( $last_used ) : __( 'Never', 'magick-ai-core' ) ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Action', 'magick-ai-core' ); ?></th>
						<td><a class="button" href="<?php echo esc_url( $this->view_url( 'app-keys' ) ); ?>"><?php echo esc_html__( 'Manage Core app keys', 'magick-ai-core' ); ?></a></td>
					</tr>
				</tbody>
			</table>
		</details>
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
			wp_die( esc_html__( 'You do not have permission to update proposals.', 'magick-ai-core' ) );
		}

		check_admin_referer( 'magick_ai_core_bulk_reject_proposals' );

		$raw_proposal_ids = filter_input( INPUT_POST, 'proposal_ids', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
		$proposal_ids     = is_array( $raw_proposal_ids ) ? array_map( 'wp_unslash', $raw_proposal_ids ) : array();
		$proposal_ids = array_values( array_unique( array_filter( array_map( 'sanitize_text_field', $proposal_ids ) ) ) );
		$proposal_ids = array_slice( $proposal_ids, 0, 50 );
		$note         = isset( $_POST['note'] ) ? sanitize_textarea_field( wp_unslash( (string) $_POST['note'] ) ) : '';
		if ( '' === $note ) {
			$note = __( 'Superseded by batch cleanup proposal.', 'magick-ai-core' );
		}

		if ( empty( $proposal_ids ) ) {
			wp_safe_redirect( $this->admin_url( array( 'magick_ai_core_error' => 'magick_ai_core_bulk_reject_empty' ) ) );
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
			'magick_ai_core_message' => 'bulk_rejected',
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
			wp_die( esc_html__( 'You do not have permission to update approval policy.', 'magick-ai-core' ) );
		}

		check_admin_referer( 'magick_ai_core_update_approval_policy' );

		$raw_mode = filter_input( INPUT_POST, 'policy_mode', FILTER_UNSAFE_RAW );
		$mode     = is_string( $raw_mode ) ? Approval_Policy_Evaluator::sanitize_policy_mode( wp_unslash( $raw_mode ) ) : Approval_Policy_Evaluator::MODE_MANUAL;
		update_option( Approval_Policy_Evaluator::OPTION_POLICY_MODE, $mode, false );

		$this->audit->record(
			'core.approval_policy_updated',
			array(
				'policy_mode'      => $mode,
				'commit_execution' => false,
			)
		);

		wp_safe_redirect( $this->admin_url( array( 'magick_ai_core_message' => 'approval_policy_updated' ) ) );
		exit;
	}

	/**
	 * Handles app key creation form submission.
	 *
	 * @return void
	 */
	public function handle_create_app_key(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to create app keys.', 'magick-ai-core' ) );
		}

		check_admin_referer( 'magick_ai_core_create_app_key' );

		$raw_scopes = array();
		if ( isset( $_POST['scopes'] ) && is_array( $_POST['scopes'] ) ) {
			$raw_scopes = array_map(
				static function ( $scope ): string {
					return sanitize_text_field( (string) $scope );
				},
				(array) wp_unslash( $_POST['scopes'] ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Values are sanitized item-by-item above.
			);
		}

		$app        = $this->apps->create(
			array(
				'app_label'           => isset( $_POST['app_label'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['app_label'] ) ) : 'External app',
				'caller_type'         => isset( $_POST['caller_type'] ) ? sanitize_key( wp_unslash( (string) $_POST['caller_type'] ) ) : 'mcp_adapter',
				'scopes'              => $raw_scopes,
				'rate_limit'          => isset( $_POST['rate_limit'] ) ? absint( wp_unslash( (string) $_POST['rate_limit'] ) ) : App_Key_Repository::DEFAULT_RATE_LIMIT,
				'rate_window_seconds' => isset( $_POST['rate_window_seconds'] ) ? absint( wp_unslash( (string) $_POST['rate_window_seconds'] ) ) : App_Key_Repository::DEFAULT_RATE_WINDOW,
			)
		);

		if ( is_wp_error( $app ) ) {
			wp_safe_redirect( $this->admin_url( array( 'view' => 'app-keys', 'magick_ai_core_error' => $app->get_error_code() ) ) );
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
			wp_safe_redirect( $this->admin_url( array( 'view' => 'app-keys', 'magick_ai_core_error' => 'magick_ai_core_app_audit_failed' ) ) );
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
			wp_die( esc_html__( 'You do not have permission to revoke app keys.', 'magick-ai-core' ) );
		}

		$key_id = isset( $_POST['key_id'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['key_id'] ) ) : '';
		check_admin_referer( 'magick_ai_core_revoke_app_key_' . $key_id );

		$app = '' !== $key_id ? $this->apps->find_by_key_id( $key_id ) : null;
		if ( null === $app || 'active' !== (string) ( $app['status'] ?? '' ) ) {
			wp_safe_redirect( $this->admin_url( array( 'view' => 'app-keys', 'magick_ai_core_error' => 'magick_ai_core_app_key_not_active' ) ) );
			exit;
		}

		if ( ! $this->apps->revoke_by_key_id( $key_id ) ) {
			wp_safe_redirect( $this->admin_url( array( 'view' => 'app-keys', 'magick_ai_core_error' => 'magick_ai_core_app_key_revoke_failed' ) ) );
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

		wp_safe_redirect( $this->admin_url( array( 'view' => 'app-keys', 'magick_ai_core_message' => 'app_key_revoked' ) ) );
		exit;
	}

	/**
	 * Renders Core app key access section.
	 *
	 * @return void
	 */
	private function render_external_access(): void {
		$page  = $this->page_from_request( 'app_key_page' );
		$total = $this->apps->count();
		$page  = $this->bounded_page( $total, $page, self::APP_KEY_PAGE_SIZE );
		$apps  = $this->apps->list_recent( self::APP_KEY_PAGE_SIZE, $this->offset_for_page( $page, self::APP_KEY_PAGE_SIZE ) );
		?>
		<p><a href="<?php echo esc_url( $this->admin_url() ); ?>">&larr; <?php echo esc_html__( 'Back to review queue', 'magick-ai-core' ); ?></a></p>
		<h2><?php echo esc_html__( 'Advanced Access', 'magick-ai-core' ); ?></h2>
		<p><?php echo esc_html__( 'Use this only for trusted Core governance clients. Productized OpenClaw setup belongs in Magick AI Adapter.', 'magick-ai-core' ); ?></p>

		<details style="max-width: 1100px; margin: 0 0 16px;">
			<summary style="cursor: pointer;">
				<strong><?php echo esc_html__( 'Create Core App Key', 'magick-ai-core' ); ?></strong>
				<span style="color: #646970;"><?php echo esc_html__( 'Issue a scoped token for a trusted governance client.', 'magick-ai-core' ); ?></span>
			</summary>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top: 8px;">
				<input type="hidden" name="action" value="magick_ai_core_create_app_key" />
				<?php wp_nonce_field( 'magick_ai_core_create_app_key' ); ?>
				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row"><label for="magick-ai-core-app-label"><?php echo esc_html__( 'App label', 'magick-ai-core' ); ?></label></th>
							<td><input id="magick-ai-core-app-label" class="regular-text" type="text" name="app_label" value="Adapter Client" /></td>
						</tr>
						<tr>
							<th scope="row"><label for="magick-ai-core-caller-type"><?php echo esc_html__( 'Caller type', 'magick-ai-core' ); ?></label></th>
							<td><input id="magick-ai-core-caller-type" class="regular-text" type="text" name="caller_type" value="product_adapter" /></td>
						</tr>
						<tr>
							<th scope="row"><?php echo esc_html__( 'Scopes', 'magick-ai-core' ); ?></th>
							<td><?php $this->render_scope_checkboxes(); ?></td>
						</tr>
						<tr>
							<th scope="row"><label for="magick-ai-core-rate-limit"><?php echo esc_html__( 'Rate limit', 'magick-ai-core' ); ?></label></th>
							<td>
								<label>
									<?php echo esc_html__( 'Requests', 'magick-ai-core' ); ?>
									<input id="magick-ai-core-rate-limit" type="number" min="1" max="10000" name="rate_limit" value="<?php echo esc_attr( (string) App_Key_Repository::DEFAULT_RATE_LIMIT ); ?>" />
								</label>
								<label style="margin-left: 12px;">
									<?php echo esc_html__( 'Window seconds', 'magick-ai-core' ); ?>
									<input type="number" min="60" max="86400" name="rate_window_seconds" value="<?php echo esc_attr( (string) App_Key_Repository::DEFAULT_RATE_WINDOW ); ?>" />
								</label>
							</td>
						</tr>
					</tbody>
				</table>
				<p><button type="submit" class="button button-secondary"><?php echo esc_html__( 'Create Core App Key', 'magick-ai-core' ); ?></button></p>
			</form>
		</details>

		<h3><?php echo esc_html__( 'Core App Keys', 'magick-ai-core' ); ?></h3>
		<p><?php echo esc_html( $this->pagination_summary( $total, $page, self::APP_KEY_PAGE_SIZE ) ); ?></p>
		<table class="widefat striped" style="max-width: 1100px;">
			<thead>
				<tr>
					<th scope="col"><?php echo esc_html__( 'Label', 'magick-ai-core' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'App ID', 'magick-ai-core' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'Key ID', 'magick-ai-core' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'Status', 'magick-ai-core' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'Scopes', 'magick-ai-core' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'Last used', 'magick-ai-core' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'Action', 'magick-ai-core' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $apps ) ) : ?>
					<tr><td colspan="7"><?php echo esc_html__( 'No app keys yet.', 'magick-ai-core' ); ?></td></tr>
				<?php endif; ?>
				<?php foreach ( $apps as $app ) : ?>
					<tr>
						<td><?php echo esc_html( (string) $app['app_label'] ); ?></td>
						<td><code><?php echo esc_html( (string) $app['app_id'] ); ?></code></td>
						<td><code><?php echo esc_html( (string) $app['key_id'] ); ?></code></td>
						<td><?php echo esc_html( (string) $app['status'] ); ?></td>
						<td><?php echo esc_html( implode( ', ', (array) $app['scopes'] ) ); ?></td>
						<td><?php echo esc_html( '' !== (string) $app['last_used_at'] ? $this->display_datetime( (string) $app['last_used_at'] ) : __( 'Never', 'magick-ai-core' ) ); ?></td>
						<td>
							<?php if ( 'active' === (string) $app['status'] ) : ?>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
									<input type="hidden" name="action" value="magick_ai_core_revoke_app_key" />
									<input type="hidden" name="key_id" value="<?php echo esc_attr( (string) $app['key_id'] ); ?>" />
									<?php wp_nonce_field( 'magick_ai_core_revoke_app_key_' . (string) $app['key_id'] ); ?>
									<button type="submit" class="button button-link-delete" onclick="return confirm('<?php echo esc_js( __( 'Disable this app key? Existing clients using this token will receive 401.', 'magick-ai-core' ) ); ?>');"><?php echo esc_html__( 'Disable', 'magick-ai-core' ); ?></button>
								</form>
							<?php else : ?>
								<?php echo esc_html__( 'Disabled', 'magick-ai-core' ); ?>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php $this->render_pagination( $total, $page, self::APP_KEY_PAGE_SIZE, 'app_key_page', array( 'view' => 'app-keys' ) ); ?>
		<?php
	}

	/**
	 * Renders expired and archived proposals.
	 *
	 * @return void
	 */
	private function render_archive_view(): void {
		$page           = $this->page_from_request( 'archive_page' );
		$status_filter = $this->admin_query_key( 'archive_status', 'all' );
		$status_filter = in_array( $status_filter, array( 'all', Proposal_Repository::STATUS_EXPIRED, Proposal_Repository::STATUS_ARCHIVED ), true ) ? $status_filter : 'all';
		$statuses      = 'all' === $status_filter
			? array( Proposal_Repository::STATUS_EXPIRED, Proposal_Repository::STATUS_ARCHIVED )
			: array( $status_filter );
		$total          = $this->proposals->count_by_statuses( $statuses );
		$page           = $this->bounded_page( $total, $page, self::ARCHIVE_PAGE_SIZE );
		$proposals      = $this->proposals->list_by_statuses(
			$statuses,
			self::ARCHIVE_PAGE_SIZE,
			$this->offset_for_page( $page, self::ARCHIVE_PAGE_SIZE )
		);
		?>
		<h2><?php echo esc_html__( 'Expired / Archived', 'magick-ai-core' ); ?></h2>
		<p><?php echo esc_html__( 'Stale requests are kept for audit but removed from the active review queue.', 'magick-ai-core' ); ?></p>
		<ul class="subsubsub" style="float: none; margin: 0 0 12px;">
			<?php foreach ( $this->archive_status_filters() as $key => $label ) : ?>
				<li>
					<a href="<?php echo esc_url( $this->admin_url( array( 'view' => 'archive', 'archive_status' => $key ) ) ); ?>" class="<?php echo $status_filter === $key ? 'current' : ''; ?>" <?php echo $status_filter === $key ? 'aria-current="page"' : ''; ?>>
						<?php echo esc_html( $label ); ?>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
		<p><?php echo esc_html( $this->pagination_summary( $total, $page, self::ARCHIVE_PAGE_SIZE ) ); ?></p>
		<table class="widefat striped" style="max-width: 1100px;">
			<thead>
				<tr>
					<th scope="col"><?php echo esc_html__( 'Proposal', 'magick-ai-core' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'Status', 'magick-ai-core' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'Age', 'magick-ai-core' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'Updated', 'magick-ai-core' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'Action', 'magick-ai-core' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $proposals ) ) : ?>
					<tr>
						<td colspan="5"><?php echo esc_html__( 'No expired or archived proposals.', 'magick-ai-core' ); ?></td>
					</tr>
				<?php endif; ?>
				<?php foreach ( $proposals as $proposal ) : ?>
					<tr>
						<td>
							<a href="<?php echo esc_url( $this->detail_url( (string) $proposal['proposal_id'] ) ); ?>">
								<strong><?php echo esc_html( (string) ( $proposal['title'] ?: $proposal['proposal_id'] ) ); ?></strong>
							</a><br />
							<code><?php echo esc_html( (string) $proposal['ability_id'] ); ?></code>
						</td>
						<td><?php echo esc_html( $this->status_label( (string) $proposal['status'] ) ); ?></td>
						<td><?php echo esc_html( $this->proposal_age_label( $proposal ) ); ?></td>
						<td><?php echo esc_html( $this->display_datetime( (string) $proposal['updated_at'] ) ); ?></td>
						<td><?php $this->render_lifecycle_actions( $proposal, true ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php $this->render_pagination( $total, $page, self::ARCHIVE_PAGE_SIZE, 'archive_page', array( 'view' => 'archive', 'archive_status' => $status_filter ) ); ?>
		<?php
	}

	/**
	 * Renders scope checkboxes.
	 *
	 * @return void
	 */
	private function render_scope_checkboxes(): void {
		$defaults = $this->apps->default_scopes();
		$labels   = array(
			'capabilities:read' => __( 'Read capabilities', 'magick-ai-core' ),
			'proposals:create'  => __( 'Create proposals', 'magick-ai-core' ),
			'proposals:read'    => __( 'Read proposals', 'magick-ai-core' ),
			'commit:preflight'  => __( 'Commit preflight', 'magick-ai-core' ),
			'proposals:approve' => __( 'Approve proposals', 'magick-ai-core' ),
			'proposals:reject'  => __( 'Reject proposals', 'magick-ai-core' ),
			'audit:read'        => __( 'Read audit log', 'magick-ai-core' ),
		);

		foreach ( $this->apps->allowed_scopes() as $scope ) {
			?>
			<label style="display: block; margin: 0 0 4px;">
				<input type="checkbox" name="scopes[]" value="<?php echo esc_attr( $scope ); ?>" <?php checked( in_array( $scope, $defaults, true ) ); ?> />
				<?php echo esc_html( (string) ( $labels[ $scope ] ?? $scope ) ); ?>
				<code><?php echo esc_html( $scope ); ?></code>
			</label>
			<?php
		}
	}

	/**
	 * Renders one-time app key result.
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
			<title><?php echo esc_html__( 'Core App Key Created', 'magick-ai-core' ); ?></title>
			<?php
			wp_enqueue_style( 'magick-ai-core-admin', $this->admin_stylesheet_url(), array(), MAGICK_AI_CORE_VERSION );
			wp_print_styles( 'magick-ai-core-admin' );
			?>
		</head>
		<body>
			<main>
				<h1><?php echo esc_html__( 'Core App Key Created', 'magick-ai-core' ); ?></h1>
				<div class="notice">
					<p><?php echo esc_html__( 'Copy this token now. It is shown only once and is not stored in raw form.', 'magick-ai-core' ); ?></p>
					<p><?php echo esc_html__( 'Use this token only in a trusted Adapter or internal governance client secret store. Configure productized OpenClaw setup in Magick AI Adapter.', 'magick-ai-core' ); ?></p>
				</div>
				<table>
					<tbody>
						<tr>
							<th scope="row"><?php echo esc_html__( 'App ID', 'magick-ai-core' ); ?></th>
							<td><code><?php echo esc_html( (string) $app['app_id'] ); ?></code></td>
						</tr>
						<tr>
							<th scope="row"><?php echo esc_html__( 'Key ID', 'magick-ai-core' ); ?></th>
							<td><code><?php echo esc_html( (string) $app['key_id'] ); ?></code></td>
						</tr>
						<tr>
							<th scope="row"><?php echo esc_html__( 'App token', 'magick-ai-core' ); ?></th>
							<td><textarea rows="3" readonly><?php echo esc_textarea( $token ); ?></textarea></td>
						</tr>
						<tr>
							<th scope="row"><?php echo esc_html__( 'Core env', 'magick-ai-core' ); ?></th>
							<td><textarea rows="4" readonly><?php echo esc_textarea( $this->core_env_text( $token ) ); ?></textarea></td>
						</tr>
					</tbody>
				</table>
				<p class="actions"><a class="button" href="<?php echo esc_url( $this->view_url( 'app-keys' ) ); ?>"><?php echo esc_html__( 'Back to Advanced Access', 'magick-ai-core' ); ?></a></p>
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
			wp_die( esc_html__( 'You do not have permission to update proposals.', 'magick-ai-core' ) );
		}

		$proposal_id = isset( $_POST['proposal_id'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['proposal_id'] ) ) : '';
		check_admin_referer( 'magick_ai_core_decide_proposal_' . $proposal_id );

		$note   = isset( $_POST['note'] ) ? sanitize_textarea_field( wp_unslash( (string) $_POST['note'] ) ) : '';
		$result = 'approve' === $decision ? $this->service->approve( $proposal_id, array( 'note' => $note ) ) : $this->service->reject( $proposal_id, array( 'note' => $note ) );

		if ( is_wp_error( $result ) ) {
			wp_safe_redirect( $this->admin_url( array( 'magick_ai_core_error' => $result->get_error_code() ) ) );
			exit;
		}

		wp_safe_redirect( $this->admin_url( array( 'magick_ai_core_message' => 'approve' === $decision ? 'approved' : 'rejected' ) ) );
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
			wp_die( esc_html__( 'You do not have permission to update proposals.', 'magick-ai-core' ) );
		}

		$proposal_id = isset( $_POST['proposal_id'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['proposal_id'] ) ) : '';
		check_admin_referer( 'magick_ai_core_' . $action . '_proposal_' . $proposal_id );

		$result = 'archive' === $action
			? $this->service->archive( $proposal_id, array( 'source' => 'admin' ) )
			: $this->service->reopen( $proposal_id, array( 'source' => 'admin' ) );

		if ( is_wp_error( $result ) ) {
			wp_safe_redirect( $this->admin_url( array( 'view' => 'archive', 'magick_ai_core_error' => $result->get_error_code() ) ) );
			exit;
		}

		wp_safe_redirect(
			$this->admin_url(
				array(
					'view'                   => 'archive',
					'magick_ai_core_message' => 'archive' === $action ? 'archived' : 'reopened',
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
		$timeline    = $this->audit->list_filtered(
			array(
				'proposal_id' => $proposal_id,
				'limit'       => 50,
				'order'       => 'asc',
			)
		);
		?>
		<p><a href="<?php echo esc_url( $this->admin_url() ); ?>">&larr; <?php echo esc_html__( 'Back to review queue', 'magick-ai-core' ); ?></a></p>
		<h2><?php echo esc_html__( 'Proposal Detail', 'magick-ai-core' ); ?></h2>
		<table class="widefat striped" style="max-width: 1100px;">
			<tbody>
				<tr>
					<th scope="row"><?php echo esc_html__( 'Proposal ID', 'magick-ai-core' ); ?></th>
					<td><code><?php echo esc_html( $proposal_id ); ?></code></td>
				</tr>
				<tr>
					<th scope="row"><?php echo esc_html__( 'Status', 'magick-ai-core' ); ?></th>
					<td><?php echo esc_html( $this->status_label( (string) $proposal['status'] ) ); ?></td>
				</tr>
				<tr>
					<th scope="row"><?php echo esc_html__( 'Age', 'magick-ai-core' ); ?></th>
					<td><?php echo esc_html( $this->proposal_age_label( $proposal ) ); ?></td>
				</tr>
				<tr>
					<th scope="row"><?php echo esc_html__( 'Expiry', 'magick-ai-core' ); ?></th>
					<td><?php echo esc_html( $this->proposal_expiry_label( $proposal ) ); ?></td>
				</tr>
				<tr>
					<th scope="row"><?php echo esc_html__( 'Ability', 'magick-ai-core' ); ?></th>
					<td><code><?php echo esc_html( (string) $proposal['ability_id'] ); ?></code></td>
				</tr>
				<tr>
					<th scope="row"><?php echo esc_html__( 'Summary', 'magick-ai-core' ); ?></th>
					<td><?php echo esc_html( (string) $proposal['summary'] ); ?></td>
				</tr>
			</tbody>
		</table>

		<?php $this->render_lifecycle_actions( $proposal ); ?>
		<?php $this->render_review_context( $proposal, $capability ); ?>

		<?php if ( Proposal_Repository::STATUS_PENDING === (string) $proposal['status'] ) : ?>
			<h3><?php echo esc_html__( 'Decision', 'magick-ai-core' ); ?></h3>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="max-width: 760px;">
				<input type="hidden" name="proposal_id" value="<?php echo esc_attr( $proposal_id ); ?>" />
				<?php wp_nonce_field( 'magick_ai_core_decide_proposal_' . $proposal_id ); ?>
				<p>
					<label for="magick-ai-core-note"><?php echo esc_html__( 'Decision note', 'magick-ai-core' ); ?></label><br />
					<textarea id="magick-ai-core-note" name="note" rows="3" class="large-text"></textarea>
				</p>
				<p>
					<button type="submit" class="button button-primary" name="action" value="magick_ai_core_approve_proposal">
						<?php echo esc_html__( 'Approve', 'magick-ai-core' ); ?>
					</button>
					<button type="submit" class="button" name="action" value="magick_ai_core_reject_proposal">
						<?php echo esc_html__( 'Reject', 'magick-ai-core' ); ?>
					</button>
				</p>
			</form>
		<?php endif; ?>

		<?php $this->render_raw_proposal_payload( $proposal ); ?>
		<?php $this->render_audit_timeline( $timeline ); ?>
		<?php
	}

	/**
	 * Renders proposal lifecycle actions.
	 *
	 * @param array<string,mixed> $proposal Proposal row.
	 * @param bool                $compact Whether to render compact actions.
	 * @return void
	 */
	private function render_lifecycle_actions( array $proposal, bool $compact = false ): void {
		$status      = (string) ( $proposal['status'] ?? '' );
		$proposal_id = (string) ( $proposal['proposal_id'] ?? '' );

		if ( '' === $proposal_id || ! in_array( $status, array( Proposal_Repository::STATUS_EXPIRED, Proposal_Repository::STATUS_ARCHIVED ), true ) ) {
			return;
		}

		if ( ! $compact ) {
			?>
			<h3><?php echo esc_html__( 'Lifecycle', 'magick-ai-core' ); ?></h3>
			<p><?php echo esc_html__( 'Expired and archived proposals are not eligible for approval until reopened.', 'magick-ai-core' ); ?></p>
			<?php
		}
		?>
		<div style="display: flex; flex-wrap: wrap; gap: 8px;">
			<?php if ( Proposal_Repository::STATUS_EXPIRED === $status ) : ?>
				<?php $this->render_lifecycle_form( $proposal_id, 'archive', __( 'Archive', 'magick-ai-core' ), 'button' ); ?>
			<?php endif; ?>
			<?php $this->render_lifecycle_form( $proposal_id, 'reopen', __( 'Reopen for review', 'magick-ai-core' ), 'button button-secondary' ); ?>
		</div>
		<?php
	}

	/**
	 * Renders one lifecycle action form.
	 *
	 * @param string $proposal_id Proposal id.
	 * @param string $action Action key.
	 * @param string $label Button label.
	 * @param string $class Button class.
	 * @return void
	 */
	private function render_lifecycle_form( string $proposal_id, string $action, string $label, string $class ): void {
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin: 0;">
			<input type="hidden" name="action" value="<?php echo esc_attr( 'magick_ai_core_' . $action . '_proposal' ); ?>" />
			<input type="hidden" name="proposal_id" value="<?php echo esc_attr( $proposal_id ); ?>" />
			<?php wp_nonce_field( 'magick_ai_core_' . $action . '_proposal_' . $proposal_id ); ?>
			<button type="submit" class="<?php echo esc_attr( $class ); ?>"><?php echo esc_html( $label ); ?></button>
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
		$preview          = is_array( $proposal['preview'] ?? null ) ? $proposal['preview'] : array();
		$article_workflow = is_array( $preview['article_workflow'] ?? null ) ? $preview['article_workflow'] : array();
		$risk             = $preview['risk'] ?? null;
		$risk_label       = is_array( $risk ) ? (string) ( $risk['level'] ?? $risk['target_risk_level'] ?? '' ) : (string) $risk;
		$target_ability   = (string) ( $preview['target_ability_id'] ?? $proposal['ability_id'] );
		$reason           = (string) ( $preview['reason'] ?? '' );
		$ready_label      = array_key_exists( 'proposal_ready', $preview )
			? ( (bool) $preview['proposal_ready'] ? __( 'yes', 'magick-ai-core' ) : __( 'no', 'magick-ai-core' ) )
			: __( 'not declared', 'magick-ai-core' );

		if ( '' === $risk_label && null !== $capability ) {
			$risk_label = (string) $capability['risk_level'];
		}
		?>
		<h3><?php echo esc_html__( 'Review Context', 'magick-ai-core' ); ?></h3>
		<table class="widefat striped" style="max-width: 1100px;">
			<tbody>
				<?php if ( null === $capability ) : ?>
					<tr>
						<td><?php echo esc_html__( 'The target ability is not currently discoverable. Commit preflight will fail closed until the provider exposes it again.', 'magick-ai-core' ); ?></td>
					</tr>
				<?php else : ?>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Target ability', 'magick-ai-core' ); ?></th>
						<td><code><?php echo esc_html( $target_ability ); ?></code></td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Risk', 'magick-ai-core' ); ?></th>
						<td><code><?php echo esc_html( '' !== $risk_label ? $risk_label : '-' ); ?></code></td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Requires approval', 'magick-ai-core' ); ?></th>
						<td><?php echo esc_html( ! empty( $capability['requires_approval'] ) ? __( 'yes', 'magick-ai-core' ) : __( 'no', 'magick-ai-core' ) ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Proposal ready', 'magick-ai-core' ); ?></th>
						<td><?php echo esc_html( $ready_label ); ?></td>
					</tr>
					<?php if ( '' !== $reason ) : ?>
						<tr>
							<th scope="row"><?php echo esc_html__( 'Reason', 'magick-ai-core' ); ?></th>
							<td><?php echo esc_html( $reason ); ?></td>
						</tr>
					<?php endif; ?>
					<?php
					if ( array_key_exists( 'before', $preview ) ) {
						$this->render_review_value_row( __( 'Before', 'magick-ai-core' ), $preview['before'] );
					}
					if ( array_key_exists( 'after_suggestion', $preview ) ) {
						$this->render_review_value_row( __( 'After suggestion', 'magick-ai-core' ), $preview['after_suggestion'] );
					}
					if ( ! empty( $preview['needs_input'] ) ) {
						$this->render_review_value_row( __( 'Needs input', 'magick-ai-core' ), $preview['needs_input'] );
					}
					if ( ! empty( $preview['blocked_items'] ) ) {
						$this->render_review_value_row( __( 'Blocked items', 'magick-ai-core' ), $preview['blocked_items'] );
					}
					?>
				<?php endif; ?>
				<?php
				if ( ! empty( $article_workflow ) ) {
					$this->render_article_workflow_review_context( $article_workflow, (string) $proposal['ability_id'] );
				}
				?>
			</tbody>
		</table>
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
			__( 'Article workflow', 'magick-ai-core' ),
			array(
				'title'                  => '' !== $title ? $title : '-',
				'artifact_type'          => (string) ( $article_workflow['artifact_type'] ?? '' ),
				'version'                => absint( $article_workflow['version'] ?? 0 ),
				'risk_level'             => (string) ( $risk_report['risk_level'] ?? '-' ),
				'ready_for_proposal'     => ! empty( $risk_report['ready_for_proposal'] ) ? __( 'yes', 'magick-ai-core' ) : __( 'no', 'magick-ai-core' ),
				'blocked_claims'         => count( $blocked_claims ),
				'needs_review'           => count( $needs_review ),
				'final_write_ability'    => $proposal_ability_id,
				'final_write_path'       => (string) ( $article_workflow['final_write_path'] ?? '' ),
				'direct_wordpress_write' => ! empty( $article_workflow['direct_wordpress_write'] ) ? __( 'yes', 'magick-ai-core' ) : __( 'no', 'magick-ai-core' ),
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
			$artifact_availability[ $artifact_key ] = ! empty( $article_workflow[ $artifact_key ] ) ? __( 'included', 'magick-ai-core' ) : __( 'missing', 'magick-ai-core' );
		}

		$this->render_review_value_row( __( 'Article artifacts', 'magick-ai-core' ), $artifact_availability );

		if ( ! empty( $blocked_claims ) ) {
			$this->render_review_value_row( __( 'Blocked claims', 'magick-ai-core' ), $blocked_claims );
		}
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
					<?php echo esc_html( (string) $value ); ?>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Renders raw proposal payload behind an explicit disclosure.
	 *
	 * @param array<string,mixed> $proposal Proposal.
	 * @return void
	 */
	private function render_raw_proposal_payload( array $proposal ): void {
		?>
		<details style="max-width: 1100px; margin-top: 12px;">
			<summary style="cursor: pointer;">
				<strong><?php echo esc_html__( 'Raw proposal payload', 'magick-ai-core' ); ?></strong>
				<span style="color: #646970;"><?php echo esc_html__( 'Caller, input, and preview JSON.', 'magick-ai-core' ); ?></span>
			</summary>
			<table class="widefat striped" style="margin-top: 8px;">
				<tbody>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Caller', 'magick-ai-core' ); ?></th>
						<td><pre><?php echo esc_html( (string) wp_json_encode( $proposal['caller'], JSON_PRETTY_PRINT ) ); ?></pre></td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Input', 'magick-ai-core' ); ?></th>
						<td><pre><?php echo esc_html( (string) wp_json_encode( $proposal['input'], JSON_PRETTY_PRINT ) ); ?></pre></td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Preview', 'magick-ai-core' ); ?></th>
						<td><pre><?php echo esc_html( (string) wp_json_encode( $proposal['preview'], JSON_PRETTY_PRINT ) ); ?></pre></td>
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
	private function render_audit_timeline( array $events ): void {
		?>
		<h3><?php echo esc_html__( 'Audit Timeline', 'magick-ai-core' ); ?></h3>
		<table class="widefat striped" style="max-width: 1100px;">
			<thead>
				<tr>
					<th scope="col"><?php echo esc_html__( 'Time', 'magick-ai-core' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'Event', 'magick-ai-core' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'Actor', 'magick-ai-core' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'Detail', 'magick-ai-core' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $events ) ) : ?>
					<tr>
						<td colspan="4"><?php echo esc_html__( 'No audit events recorded for this proposal yet.', 'magick-ai-core' ); ?></td>
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
		<?php
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
		<h2><?php echo esc_html__( 'Governance Audit', 'magick-ai-core' ); ?></h2>
		<p><?php echo esc_html__( 'Recent Core governance events. AI Request Logs remain separate; correlate them with proposal_id or correlation_id.', 'magick-ai-core' ); ?></p>
		<p><?php echo esc_html( $this->pagination_summary( $total, (int) $filters['page'], (int) $filters['limit'] ) ); ?></p>
		<details style="max-width: 1100px; margin: 0 0 12px;" <?php echo $this->has_active_audit_filters( $filters ) ? 'open' : ''; ?>>
			<summary style="cursor: pointer;">
				<strong><?php echo esc_html__( 'Advanced audit filters', 'magick-ai-core' ); ?></strong>
				<span style="color: #646970;"><?php echo esc_html__( 'Narrow by proposal, event, ability, app, caller, or correlation.', 'magick-ai-core' ); ?></span>
			</summary>
			<form method="get" style="margin-top: 8px;">
				<input type="hidden" name="page" value="magick-ai-core" />
				<input type="hidden" name="view" value="audit" />
				<input type="hidden" name="<?php echo esc_attr( self::ADMIN_REQUEST_NONCE ); ?>" value="<?php echo esc_attr( wp_create_nonce( self::ADMIN_REQUEST_ACTION ) ); ?>" />
				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row"><label for="magick-ai-core-audit-proposal"><?php echo esc_html__( 'Proposal ID', 'magick-ai-core' ); ?></label></th>
							<td><input id="magick-ai-core-audit-proposal" class="regular-text" type="text" name="audit_proposal_id" value="<?php echo esc_attr( (string) $filters['proposal_id'] ); ?>" /></td>
						</tr>
						<tr>
							<th scope="row"><label for="magick-ai-core-audit-event"><?php echo esc_html__( 'Event', 'magick-ai-core' ); ?></label></th>
							<td><input id="magick-ai-core-audit-event" class="regular-text" type="text" name="audit_event_name" value="<?php echo esc_attr( (string) $filters['event_name'] ); ?>" placeholder="proposal.created" /></td>
						</tr>
						<tr>
							<th scope="row"><label for="magick-ai-core-audit-ability"><?php echo esc_html__( 'Ability ID', 'magick-ai-core' ); ?></label></th>
							<td><input id="magick-ai-core-audit-ability" class="regular-text" type="text" name="audit_ability_id" value="<?php echo esc_attr( (string) $filters['ability_id'] ); ?>" placeholder="magick-ai/create-draft" /></td>
						</tr>
						<tr>
							<th scope="row"><label for="magick-ai-core-audit-app"><?php echo esc_html__( 'App ID', 'magick-ai-core' ); ?></label></th>
							<td><input id="magick-ai-core-audit-app" class="regular-text" type="text" name="audit_app_id" value="<?php echo esc_attr( (string) $filters['app_id'] ); ?>" /></td>
						</tr>
						<tr>
							<th scope="row"><label for="magick-ai-core-audit-caller"><?php echo esc_html__( 'Caller type', 'magick-ai-core' ); ?></label></th>
							<td><input id="magick-ai-core-audit-caller" class="regular-text" type="text" name="audit_caller_type" value="<?php echo esc_attr( (string) $filters['caller_type'] ); ?>" placeholder="product_adapter" /></td>
						</tr>
						<tr>
							<th scope="row"><label for="magick-ai-core-audit-correlation"><?php echo esc_html__( 'Correlation ID', 'magick-ai-core' ); ?></label></th>
							<td><input id="magick-ai-core-audit-correlation" class="regular-text" type="text" name="audit_correlation_id" value="<?php echo esc_attr( (string) $filters['correlation_id'] ); ?>" /></td>
						</tr>
						<tr>
							<th scope="row"><label for="magick-ai-core-audit-limit"><?php echo esc_html__( 'Per page', 'magick-ai-core' ); ?></label></th>
							<td><input id="magick-ai-core-audit-limit" type="number" min="1" max="200" name="audit_limit" value="<?php echo esc_attr( (string) $filters['limit'] ); ?>" /></td>
						</tr>
						<tr>
							<th scope="row"><?php echo esc_html__( 'Read events', 'magick-ai-core' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="audit_include_read_events" value="1" <?php checked( ! empty( $filters['include_read_events'] ) ); ?> />
									<?php echo esc_html__( 'Include list/view noise events', 'magick-ai-core' ); ?>
								</label>
							</td>
						</tr>
					</tbody>
				</table>
				<p>
					<button type="submit" class="button"><?php echo esc_html__( 'Filter Audit', 'magick-ai-core' ); ?></button>
					<a class="button button-link" href="<?php echo esc_url( $this->view_url( 'audit' ) ); ?>"><?php echo esc_html__( 'Clear', 'magick-ai-core' ); ?></a>
				</p>
			</form>
		</details>
		<table class="widefat striped" style="max-width: 1100px;">
			<thead>
				<tr>
					<th scope="col"><?php echo esc_html__( 'Time', 'magick-ai-core' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'Event', 'magick-ai-core' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'Proposal', 'magick-ai-core' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'Actor', 'magick-ai-core' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'Ability', 'magick-ai-core' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'Detail', 'magick-ai-core' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $events ) ) : ?>
					<tr>
						<td colspan="6"><?php echo esc_html__( 'No governance audit events match the current filters.', 'magick-ai-core' ); ?></td>
					</tr>
				<?php endif; ?>
				<?php foreach ( $events as $event ) : ?>
					<?php
					$metadata       = is_array( $event['metadata'] ?? null ) ? $event['metadata'] : array();
					$proposal_id    = (string) ( $event['proposal_id'] ?? '' );
					$ability_id     = (string) ( $metadata['ability_id'] ?? '' );
					?>
					<tr>
						<td><?php echo esc_html( $this->display_datetime( (string) $event['created_at'] ) ); ?></td>
						<td><code><?php echo esc_html( (string) $event['event_name'] ); ?></code></td>
						<td>
							<?php if ( '' !== $proposal_id ) : ?>
								<a href="<?php echo esc_url( $this->detail_url( $proposal_id ) ); ?>"><code><?php echo esc_html( $proposal_id ); ?></code></a>
							<?php else : ?>
								<?php echo esc_html__( 'System', 'magick-ai-core' ); ?>
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( (string) $event['actor_id'] ); ?></td>
						<td><?php echo '' !== $ability_id ? '<code>' . esc_html( $ability_id ) . '</code>' : esc_html__( 'Core event', 'magick-ai-core' ); ?></td>
						<td><?php $this->render_audit_detail( $event ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php $this->render_pagination( $total, (int) $filters['page'], (int) $filters['limit'], 'audit_page', $this->audit_query_args( $filters ) ); ?>
		<?php
	}

	/**
	 * Renders compact optional audit metadata.
	 *
	 * @param array<string,mixed> $event Audit event.
	 * @return void
	 */
	private function render_audit_detail( array $event ): void {
		$metadata       = is_array( $event['metadata'] ?? null ) ? $event['metadata'] : array();
		$auth           = is_array( $metadata['auth'] ?? null ) ? $metadata['auth'] : array();
		$proposal_id    = (string) ( $event['proposal_id'] ?? '' );
		$app_id         = (string) ( $auth['app_id'] ?? '' );
		$caller_type    = (string) ( $auth['caller_type'] ?? '' );
		$scope          = (string) ( $auth['scope'] ?? '' );
		$scope_decision = (string) ( $auth['scope_decision'] ?? '' );
		$correlation_id = (string) ( $metadata['correlation_id'] ?? '' );
		$has_detail     = false;

		if ( '' === $proposal_id ) {
			$this->render_audit_badge( __( 'System event', 'magick-ai-core' ) );
			$has_detail = true;
		}

		if ( '' !== $app_id || '' !== $caller_type ) {
			$this->render_audit_badge(
				sprintf(
					/* translators: 1: app id, 2: caller type. */
					__( 'App: %1$s / %2$s', 'magick-ai-core' ),
					'' !== $app_id ? $app_id : __( 'unknown', 'magick-ai-core' ),
					'' !== $caller_type ? $caller_type : __( 'unknown', 'magick-ai-core' )
				)
			);
			$has_detail = true;
		}

		if ( '' !== $scope || '' !== $scope_decision ) {
			$this->render_audit_badge(
				sprintf(
					/* translators: 1: scope, 2: scope decision. */
					__( 'Scope: %1$s / %2$s', 'magick-ai-core' ),
					'' !== $scope ? $scope : __( 'unknown', 'magick-ai-core' ),
					'' !== $scope_decision ? $scope_decision : __( 'unknown', 'magick-ai-core' )
				)
			);
			$has_detail = true;
		}

		if ( '' !== $correlation_id ) {
			$this->render_audit_badge(
				sprintf(
					/* translators: %s: correlation id. */
					__( 'Correlation: %s', 'magick-ai-core' ),
					$correlation_id
				)
			);
			$has_detail = true;
		}

		if ( ! $has_detail ) {
			echo esc_html__( 'No extra context', 'magick-ai-core' );
		}
	}

	/**
	 * Renders one audit detail badge.
	 *
	 * @param string $label Badge label.
	 * @return void
	 */
	private function render_audit_badge( string $label ): void {
		?>
		<code style="display: inline-block; margin: 0 4px 4px 0;"><?php echo esc_html( $label ); ?></code>
		<?php
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
		$filters             = array(
			'proposal_id'    => $this->admin_query_text( 'audit_proposal_id' ),
			'event_name'     => $this->admin_query_text( 'audit_event_name' ),
			'ability_id'     => $this->admin_query_text( 'audit_ability_id' ),
			'app_id'         => $this->admin_query_text( 'audit_app_id' ),
			'caller_type'    => $this->admin_query_key( 'audit_caller_type' ),
			'correlation_id' => $this->admin_query_text( 'audit_correlation_id' ),
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
		foreach ( array( 'proposal_id', 'event_name', 'ability_id', 'app_id', 'caller_type', 'correlation_id' ) as $key ) {
			if ( '' !== (string) ( $filters[ $key ] ?? '' ) ) {
				return true;
			}
		}

		return self::AUDIT_PAGE_SIZE !== (int) ( $filters['limit'] ?? self::AUDIT_PAGE_SIZE ) || ! empty( $filters['include_read_events'] );
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
	 * Returns archive status filter labels.
	 *
	 * @return array<string,string>
	 */
	private function archive_status_filters(): array {
		return array(
			'all'                                  => __( 'All', 'magick-ai-core' ),
			Proposal_Repository::STATUS_EXPIRED  => __( 'Expired', 'magick-ai-core' ),
			Proposal_Repository::STATUS_ARCHIVED => __( 'Archived', 'magick-ai-core' ),
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
			'view'        => 'audit',
			'audit_limit' => (string) (int) ( $filters['limit'] ?? self::AUDIT_PAGE_SIZE ),
		);

		$mapping = array(
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
			return __( 'No matching records.', 'magick-ai-core' );
		}

		$start = $this->offset_for_page( $page, $per_page ) + 1;
		$end   = min( $total, $start + $per_page - 1 );

		return sprintf(
			/* translators: 1: first row number, 2: last row number, 3: total row count. */
			__( 'Showing %1$d-%2$d of %3$d.', 'magick-ai-core' ),
			$start,
			$end,
			$total
		);
	}

	/**
	 * Renders pagination links for admin lists.
	 *
	 * @param int                  $total Total rows.
	 * @param int                  $page Current page.
	 * @param int                  $per_page Rows per page.
	 * @param string               $page_arg Query arg storing page number.
	 * @param array<string,string> $args Preserved query args.
	 * @return void
	 */
	private function render_pagination( int $total, int $page, int $per_page, string $page_arg, array $args ): void {
		$total_pages = (int) ceil( $total / max( 1, $per_page ) );
		if ( $total_pages <= 1 ) {
			return;
		}

		$base_url = remove_query_arg( $page_arg, $this->admin_url( $args ) );
		$links    = paginate_links(
			array(
				'base'      => add_query_arg( $page_arg, '%#%', $base_url ),
				'format'    => '',
				'current'   => max( 1, $page ),
				'total'     => $total_pages,
				'prev_text' => __( 'Previous', 'magick-ai-core' ),
				'next_text' => __( 'Next', 'magick-ai-core' ),
			)
		);

		if ( is_string( $links ) && '' !== $links ) {
			?>
			<div class="tablenav" style="max-width: 1100px;">
				<div class="tablenav-pages"><?php echo wp_kses_post( $links ); ?></div>
			</div>
			<?php
		}
	}

	/**
	 * Returns a display label for proposal status.
	 *
	 * @param string $status Raw status.
	 * @return string
	 */
	private function status_label( string $status ): string {
		$labels = array(
			Proposal_Repository::STATUS_PENDING  => __( 'Needs review', 'magick-ai-core' ),
			Proposal_Repository::STATUS_APPROVED => __( 'Approved', 'magick-ai-core' ),
			Proposal_Repository::STATUS_REJECTED => __( 'Rejected', 'magick-ai-core' ),
			Proposal_Repository::STATUS_EXPIRED  => __( 'Expired', 'magick-ai-core' ),
			Proposal_Repository::STATUS_ARCHIVED => __( 'Archived', 'magick-ai-core' ),
		);

		return (string) ( $labels[ $status ] ?? $status );
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
			return __( 'Unknown', 'magick-ai-core' );
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
			return __( 'Unknown', 'magick-ai-core' );
		}

		if ( Proposal_Repository::STATUS_PENDING !== $status ) {
			if ( Proposal_Repository::STATUS_EXPIRED === $status || Proposal_Repository::STATUS_ARCHIVED === $status ) {
				return __( 'Expired', 'magick-ai-core' );
			}

			return __( 'Not applicable', 'magick-ai-core' );
		}

		$expires_at = $created + $this->service->pending_ttl_seconds();
		$remaining  = $expires_at - time();

		if ( $remaining <= 0 ) {
			return __( 'Expired', 'magick-ai-core' );
		}

		return sprintf(
			/* translators: %s: remaining duration. */
			__( 'Expires in %s', 'magick-ai-core' ),
			$this->duration_label( $remaining )
		);
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
				__( '%d min', 'magick-ai-core' ),
				max( 1, (int) ceil( $seconds / MINUTE_IN_SECONDS ) )
			);
		}

		if ( $seconds < DAY_IN_SECONDS ) {
			return sprintf(
				/* translators: %d: hours. */
				__( '%d hr', 'magick-ai-core' ),
				max( 1, (int) ceil( $seconds / HOUR_IN_SECONDS ) )
			);
		}

		return sprintf(
			/* translators: %d: days. */
			__( '%d days', 'magick-ai-core' ),
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
					'page'                    => self::MENU_SLUG,
					self::ADMIN_REQUEST_NONCE => wp_create_nonce( self::ADMIN_REQUEST_ACTION ),
				),
				$args
			),
			admin_url( 'admin.php' )
		);
	}

	/**
	 * Returns whether the current admin query nonce is valid.
	 *
	 * @return bool
	 */
	private function has_valid_admin_request_nonce(): bool {
		$nonce = filter_input( INPUT_GET, self::ADMIN_REQUEST_NONCE, FILTER_UNSAFE_RAW );
		if ( ! is_string( $nonce ) || '' === $nonce ) {
			return false;
		}

		return (bool) wp_verify_nonce( sanitize_text_field( $nonce ), self::ADMIN_REQUEST_ACTION );
	}

	/**
	 * Returns sanitized text from a nonce-verified admin query arg.
	 *
	 * @param string $key Query arg key.
	 * @param string $default Default value.
	 * @return string
	 */
	private function admin_query_text( string $key, string $default = '' ): string {
		if ( ! $this->has_valid_admin_request_nonce() ) {
			return $default;
		}

		$value = filter_input( INPUT_GET, $key, FILTER_UNSAFE_RAW );
		return is_string( $value ) ? sanitize_text_field( $value ) : $default;
	}

	/**
	 * Returns sanitized key text from a nonce-verified admin query arg.
	 *
	 * @param string $key Query arg key.
	 * @param string $default Default value.
	 * @return string
	 */
	private function admin_query_key( string $key, string $default = '' ): string {
		if ( ! $this->has_valid_admin_request_nonce() ) {
			return $default;
		}

		$value = filter_input( INPUT_GET, $key, FILTER_UNSAFE_RAW );
		return is_string( $value ) ? sanitize_key( $value ) : $default;
	}

	/**
	 * Returns an absolute integer from a nonce-verified admin query arg.
	 *
	 * @param string $key Query arg key.
	 * @param int    $default Default value.
	 * @return int
	 */
	private function admin_query_absint( string $key, int $default = 0 ): int {
		if ( ! $this->has_valid_admin_request_nonce() ) {
			return $default;
		}

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
		return $this->has_valid_admin_request_nonce() && '' !== $this->admin_query_text( $key );
	}

	/**
	 * Returns the Core admin stylesheet URL.
	 *
	 * @return string
	 */
	private function admin_stylesheet_url(): string {
		return plugins_url( 'assets/admin.css', MAGICK_AI_CORE_FILE );
	}

	/**
	 * Returns user-facing message text.
	 *
	 * @param string $code Message code.
	 * @return string
	 */
	private function message_text( string $code ): string {
		$messages = array(
			'approved'                                      => __( 'Proposal approved.', 'magick-ai-core' ),
			'rejected'                                      => __( 'Proposal rejected.', 'magick-ai-core' ),
			'bulk_rejected'                                 => __( 'Selected proposals rejected.', 'magick-ai-core' ),
			'archived'                                      => __( 'Proposal archived.', 'magick-ai-core' ),
			'reopened'                                      => __( 'Proposal reopened for review.', 'magick-ai-core' ),
			'app_key_revoked'                               => __( 'App key disabled.', 'magick-ai-core' ),
			'approval_policy_updated'                       => __( 'Approval policy mode updated.', 'magick-ai-core' ),
			'magick_ai_core_app_key_not_active'             => __( 'App key is missing or already disabled.', 'magick-ai-core' ),
			'magick_ai_core_app_key_revoke_failed'          => __( 'App key could not be disabled.', 'magick-ai-core' ),
			'magick_ai_core_proposal_not_found'             => __( 'Proposal was not found.', 'magick-ai-core' ),
			'magick_ai_core_proposal_expired'               => __( 'Proposal expired before a decision was made.', 'magick-ai-core' ),
			'magick_ai_core_proposal_archive_not_allowed'   => __( 'Only expired proposals can be archived.', 'magick-ai-core' ),
			'magick_ai_core_proposal_reopen_not_allowed'    => __( 'Only expired or archived proposals can be reopened.', 'magick-ai-core' ),
			'magick_ai_core_proposal_already_decided'       => __( 'Only pending proposals can be approved or rejected.', 'magick-ai-core' ),
			'magick_ai_core_proposal_transition_failed'     => __( 'Proposal status could not be updated.', 'magick-ai-core' ),
			'magick_ai_core_bulk_reject_empty'              => __( 'Select at least one pending proposal to reject.', 'magick-ai-core' ),
		);

		return (string) ( $messages[ $code ] ?? __( 'Proposal action could not be completed.', 'magick-ai-core' ) );
	}

	/**
	 * Returns minimal Core environment text.
	 *
	 * @param string $token App token or placeholder.
	 * @return string
	 */
	private function core_env_text( string $token ): string {
		$lines = array(
			'MAGICK_AI_CORE_BASE_URL=' . home_url(),
			'MAGICK_AI_CORE_APP_TOKEN=' . $token,
		);

		return implode( "\n", $lines );
	}
}
