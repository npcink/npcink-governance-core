<?php
/**
 * Minimal admin page.
 *
 * @package MagickAICore
 */

namespace MagickAI\Core\Admin;

use MagickAI\Core\Audit\Audit_Log_Repository;
use MagickAI\Core\Capabilities\Ability_Registry_Adapter;
use MagickAI\Core\Governance\Proposal_Repository;
use MagickAI\Core\Governance\Proposal_Service;
use MagickAI\Core\Security\App_Key_Repository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders a compact governance overview.
 */
final class Admin_Page {
	const PARENT_MENU_SLUG = 'magick-ai';
	const MENU_SLUG        = 'magick-ai-core';
	const MENU_CAPABILITY  = 'manage_options';

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
	 * Constructor.
	 *
	 * @param Ability_Registry_Adapter $abilities Ability adapter.
	 * @param Proposal_Repository      $proposals Proposal repository.
	 * @param Audit_Log_Repository     $audit Audit repository.
	 * @param Proposal_Service         $service Proposal service.
	 * @param App_Key_Repository       $apps App key repository.
	 */
	public function __construct( Ability_Registry_Adapter $abilities, Proposal_Repository $proposals, Audit_Log_Repository $audit, Proposal_Service $service, App_Key_Repository $apps ) {
		$this->abilities = $abilities;
		$this->proposals = $proposals;
		$this->audit     = $audit;
		$this->service   = $service;
		$this->apps      = $apps;
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
			<table class="widefat striped" style="max-width: 860px;">
				<tbody>
					<?php
					$this->render_overview_row( __( 'Core', 'magick-ai-core' ), __( 'Review proposals, approval decisions, commit preflight, audit, and Core app keys.', 'magick-ai-core' ), self::MENU_SLUG );
					$this->render_overview_row( __( 'Adapter', 'magick-ai-core' ), __( 'Connect OpenClaw through the Adapter surface.', 'magick-ai-core' ), 'magick-ai-adapter' );
					$this->render_overview_row( __( 'Cloud Connection', 'magick-ai-core' ), __( 'Connect this site to Magick AI Cloud without moving local control-plane truth.', 'magick-ai-core' ), 'magick-ai-cloud' );
					$this->render_overview_row( __( 'Ability Packages', 'magick-ai-core' ), __( 'Verify WordPress Abilities API packages and demo ability controls.', 'magick-ai-core' ), 'magick-ai-abilities-test' );
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
					<span style="color: #646970;"><?php echo esc_html__( 'Not installed', 'magick-ai-core' ); ?></span>
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

		$summary       = $this->abilities->summary();
		$pending       = $this->proposals->list_recent( 20, 'pending' );
		$selected_id   = isset( $_GET['proposal_id'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['proposal_id'] ) ) : '';
		$selected      = '' !== $selected_id ? $this->proposals->find( $selected_id ) : null;
		$audit_filters = $this->audit_filters_from_request();
		$audit_events  = $this->audit->list_filtered( $audit_filters );
		$message       = isset( $_GET['magick_ai_core_message'] ) ? sanitize_key( wp_unslash( (string) $_GET['magick_ai_core_message'] ) ) : '';
		$error         = isset( $_GET['magick_ai_core_error'] ) ? sanitize_key( wp_unslash( (string) $_GET['magick_ai_core_error'] ) ) : '';
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Magick AI Core', 'magick-ai-core' ); ?></h1>
			<p><?php echo esc_html__( 'AI operation governance for WordPress abilities.', 'magick-ai-core' ); ?></p>

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

			<h2><?php echo esc_html__( 'Governance Summary', 'magick-ai-core' ); ?></h2>
			<table class="widefat striped" style="max-width: 760px;">
				<tbody>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Ability source', 'magick-ai-core' ); ?></th>
						<td><?php echo esc_html( $summary['source'] ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Available abilities', 'magick-ai-core' ); ?></th>
						<td><?php echo esc_html( (string) $summary['count'] ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Proposals', 'magick-ai-core' ); ?></th>
						<td><?php echo esc_html( (string) $this->proposals->count() ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Audit events', 'magick-ai-core' ); ?></th>
						<td><?php echo esc_html( (string) $this->audit->count() ); ?></td>
					</tr>
				</tbody>
			</table>

			<h2><?php echo esc_html__( 'Pending Proposals', 'magick-ai-core' ); ?></h2>
			<table class="widefat striped" style="max-width: 1100px;">
				<thead>
					<tr>
						<th scope="col"><?php echo esc_html__( 'Title', 'magick-ai-core' ); ?></th>
						<th scope="col"><?php echo esc_html__( 'Ability', 'magick-ai-core' ); ?></th>
						<th scope="col"><?php echo esc_html__( 'Created', 'magick-ai-core' ); ?></th>
						<th scope="col"><?php echo esc_html__( 'Action', 'magick-ai-core' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $pending ) ) : ?>
						<tr>
							<td colspan="4"><?php echo esc_html__( 'No pending proposals.', 'magick-ai-core' ); ?></td>
						</tr>
					<?php endif; ?>
					<?php foreach ( $pending as $proposal ) : ?>
						<tr>
							<td><?php echo esc_html( (string) ( $proposal['title'] ?: $proposal['proposal_id'] ) ); ?></td>
							<td><code><?php echo esc_html( (string) $proposal['ability_id'] ); ?></code></td>
							<td><?php echo esc_html( (string) $proposal['created_at'] ); ?></td>
							<td>
								<a class="button" href="<?php echo esc_url( $this->detail_url( (string) $proposal['proposal_id'] ) ); ?>">
									<?php echo esc_html__( 'Review', 'magick-ai-core' ); ?>
								</a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<?php if ( null !== $selected ) : ?>
				<?php $this->render_proposal_detail( $selected ); ?>
			<?php elseif ( '' !== $selected_id ) : ?>
				<div class="notice notice-warning">
					<p><?php echo esc_html__( 'Selected proposal was not found.', 'magick-ai-core' ); ?></p>
				</div>
			<?php endif; ?>

			<?php $this->render_governance_audit( $audit_events, $audit_filters ); ?>
			<?php $this->render_external_access(); ?>
		</div>
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

		$this->audit->record(
			'app.created',
			array(
				'app_id'      => (string) $app['app_id'],
				'key_id'      => (string) $app['key_id'],
				'caller_type' => (string) $app['caller_type'],
				'scopes'      => (array) $app['scopes'],
			)
		);

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
			wp_safe_redirect( $this->admin_url( array( 'magick_ai_core_error' => 'magick_ai_core_app_key_not_active' ) ) );
			exit;
		}

		if ( ! $this->apps->revoke_by_key_id( $key_id ) ) {
			wp_safe_redirect( $this->admin_url( array( 'magick_ai_core_error' => 'magick_ai_core_app_key_revoke_failed' ) ) );
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

		wp_safe_redirect( $this->admin_url( array( 'magick_ai_core_message' => 'app_key_revoked' ) ) );
		exit;
	}

	/**
	 * Renders Core app key access section.
	 *
	 * @return void
	 */
	private function render_external_access(): void {
		$apps = $this->apps->list_recent( 10 );
		?>
		<details style="max-width: 1100px; margin-top: 24px;">
			<summary style="cursor: pointer;">
				<strong><?php echo esc_html__( 'Advanced: Core App Keys', 'magick-ai-core' ); ?></strong>
				<span style="color: #646970;"><?php echo esc_html__( 'Issue or disable governance credentials.', 'magick-ai-core' ); ?></span>
			</summary>
			<p><?php echo esc_html__( 'Use this only for trusted Core governance clients. Productized OpenClaw setup belongs in Magick AI Adapter.', 'magick-ai-core' ); ?></p>

			<h3><?php echo esc_html__( 'Create Core App Key', 'magick-ai-core' ); ?></h3>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
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

			<h3><?php echo esc_html__( 'Recent App Keys', 'magick-ai-core' ); ?></h3>
			<table class="widefat striped">
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
							<td><?php echo esc_html( (string) ( $app['last_used_at'] ?: '-' ) ); ?></td>
							<td>
								<?php if ( 'active' === (string) $app['status'] ) : ?>
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
										<input type="hidden" name="action" value="magick_ai_core_revoke_app_key" />
										<input type="hidden" name="key_id" value="<?php echo esc_attr( (string) $app['key_id'] ); ?>" />
										<?php wp_nonce_field( 'magick_ai_core_revoke_app_key_' . (string) $app['key_id'] ); ?>
										<button type="submit" class="button button-link-delete" onclick="return confirm('<?php echo esc_js( __( 'Disable this app key? Existing clients using this token will receive 401.', 'magick-ai-core' ) ); ?>');"><?php echo esc_html__( 'Disable', 'magick-ai-core' ); ?></button>
									</form>
								<?php else : ?>
									<span aria-hidden="true">-</span>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</details>
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
			<style>
				body { margin: 0; background: #f0f0f1; color: #1d2327; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
				main { max-width: 960px; margin: 32px auto; padding: 0 24px; }
				h1 { font-size: 24px; margin: 0 0 16px; }
				.notice { background: #fff8e5; border-left: 4px solid #dba617; margin: 0 0 20px; padding: 12px 16px; }
				table { width: 100%; border-collapse: collapse; background: #fff; border: 1px solid #c3c4c7; }
				th, td { border-bottom: 1px solid #dcdcde; padding: 12px; text-align: left; vertical-align: top; }
				th { width: 160px; font-weight: 600; }
				code, textarea { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; }
				textarea { box-sizing: border-box; width: 100%; min-height: 96px; padding: 10px; border: 1px solid #8c8f94; background: #fff; color: #1d2327; }
				.actions { margin-top: 20px; }
				.button { display: inline-block; background: #2271b1; border: 1px solid #2271b1; border-radius: 3px; color: #fff; padding: 8px 14px; text-decoration: none; }
			</style>
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
				<p class="actions"><a class="button" href="<?php echo esc_url( $this->admin_url() ); ?>"><?php echo esc_html__( 'Back to Magick AI Core', 'magick-ai-core' ); ?></a></p>
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
		<h2><?php echo esc_html__( 'Proposal Detail', 'magick-ai-core' ); ?></h2>
		<table class="widefat striped" style="max-width: 1100px;">
			<tbody>
				<tr>
					<th scope="row"><?php echo esc_html__( 'Proposal ID', 'magick-ai-core' ); ?></th>
					<td><code><?php echo esc_html( $proposal_id ); ?></code></td>
				</tr>
				<tr>
					<th scope="row"><?php echo esc_html__( 'Status', 'magick-ai-core' ); ?></th>
					<td><?php echo esc_html( (string) $proposal['status'] ); ?></td>
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

		<?php $this->render_review_context( $proposal, $capability ); ?>
		<?php $this->render_raw_proposal_payload( $proposal ); ?>
		<?php $this->render_audit_timeline( $timeline ); ?>

		<?php if ( 'pending' === (string) $proposal['status'] ) : ?>
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
		$preview        = is_array( $proposal['preview'] ?? null ) ? $proposal['preview'] : array();
		$risk           = $preview['risk'] ?? null;
		$risk_label     = is_array( $risk ) ? (string) ( $risk['level'] ?? $risk['target_risk_level'] ?? '' ) : (string) $risk;
		$target_ability = (string) ( $preview['target_ability_id'] ?? $proposal['ability_id'] );
		$reason         = (string) ( $preview['reason'] ?? '' );
		$ready_label    = array_key_exists( 'proposal_ready', $preview )
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
			</tbody>
		</table>
		<?php
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
					<th scope="col"><?php echo esc_html__( 'App', 'magick-ai-core' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'Scope decision', 'magick-ai-core' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'Correlation', 'magick-ai-core' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $events ) ) : ?>
					<tr>
						<td colspan="6"><?php echo esc_html__( 'No audit events recorded for this proposal yet.', 'magick-ai-core' ); ?></td>
					</tr>
				<?php endif; ?>
				<?php foreach ( $events as $event ) : ?>
					<?php
					$metadata       = is_array( $event['metadata'] ?? null ) ? $event['metadata'] : array();
					$auth           = is_array( $metadata['auth'] ?? null ) ? $metadata['auth'] : array();
					$app_label      = (string) ( $auth['app_id'] ?? '-' );
					$scope          = (string) ( $auth['scope'] ?? '-' );
					$scope_decision = (string) ( $auth['scope_decision'] ?? '-' );
					$correlation_id = (string) ( $metadata['correlation_id'] ?? '-' );
					?>
					<tr>
						<td><?php echo esc_html( (string) $event['created_at'] ); ?></td>
						<td><code><?php echo esc_html( (string) $event['event_name'] ); ?></code></td>
						<td><?php echo esc_html( (string) $event['actor_id'] ); ?></td>
						<td><code><?php echo esc_html( $app_label ); ?></code></td>
						<td><code><?php echo esc_html( $scope . ' / ' . $scope_decision ); ?></code></td>
						<td><code><?php echo esc_html( $correlation_id ); ?></code></td>
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
	 * @return void
	 */
	private function render_governance_audit( array $events, array $filters ): void {
		?>
		<h2><?php echo esc_html__( 'Recent Governance Audit', 'magick-ai-core' ); ?></h2>
		<p><?php echo esc_html__( 'Recent Core governance events. AI Request Logs remain separate; correlate them with proposal_id or correlation_id.', 'magick-ai-core' ); ?></p>
		<details style="max-width: 1100px; margin: 0 0 12px;" <?php echo $this->has_active_audit_filters( $filters ) ? 'open' : ''; ?>>
			<summary style="cursor: pointer;">
				<strong><?php echo esc_html__( 'Advanced audit filters', 'magick-ai-core' ); ?></strong>
				<span style="color: #646970;"><?php echo esc_html__( 'Narrow by proposal, event, ability, app, caller, or correlation.', 'magick-ai-core' ); ?></span>
			</summary>
			<form method="get" style="margin-top: 8px;">
				<input type="hidden" name="page" value="magick-ai-core" />
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
							<th scope="row"><label for="magick-ai-core-audit-limit"><?php echo esc_html__( 'Limit', 'magick-ai-core' ); ?></label></th>
							<td><input id="magick-ai-core-audit-limit" type="number" min="1" max="200" name="audit_limit" value="<?php echo esc_attr( (string) $filters['limit'] ); ?>" /></td>
						</tr>
					</tbody>
				</table>
				<p>
					<button type="submit" class="button"><?php echo esc_html__( 'Filter Audit', 'magick-ai-core' ); ?></button>
					<a class="button button-link" href="<?php echo esc_url( $this->admin_url() ); ?>"><?php echo esc_html__( 'Clear', 'magick-ai-core' ); ?></a>
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
					<th scope="col"><?php echo esc_html__( 'App / caller', 'magick-ai-core' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'Scope decision', 'magick-ai-core' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'Correlation', 'magick-ai-core' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $events ) ) : ?>
					<tr>
						<td colspan="8"><?php echo esc_html__( 'No governance audit events match the current filters.', 'magick-ai-core' ); ?></td>
					</tr>
				<?php endif; ?>
				<?php foreach ( $events as $event ) : ?>
					<?php
					$metadata       = is_array( $event['metadata'] ?? null ) ? $event['metadata'] : array();
					$auth           = is_array( $metadata['auth'] ?? null ) ? $metadata['auth'] : array();
					$proposal_id    = (string) ( $event['proposal_id'] ?? '' );
					$ability_id     = (string) ( $metadata['ability_id'] ?? '-' );
					$app_id         = (string) ( $auth['app_id'] ?? '-' );
					$caller_type    = (string) ( $auth['caller_type'] ?? '-' );
					$scope          = (string) ( $auth['scope'] ?? '-' );
					$scope_decision = (string) ( $auth['scope_decision'] ?? '-' );
					$correlation_id = (string) ( $metadata['correlation_id'] ?? '-' );
					?>
					<tr>
						<td><?php echo esc_html( (string) $event['created_at'] ); ?></td>
						<td><code><?php echo esc_html( (string) $event['event_name'] ); ?></code></td>
						<td>
							<?php if ( '' !== $proposal_id ) : ?>
								<a href="<?php echo esc_url( $this->detail_url( $proposal_id ) ); ?>"><code><?php echo esc_html( $proposal_id ); ?></code></a>
							<?php else : ?>
								<span aria-hidden="true">-</span>
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( (string) $event['actor_id'] ); ?></td>
						<td><code><?php echo esc_html( $ability_id ); ?></code></td>
						<td><code><?php echo esc_html( $app_id . ' / ' . $caller_type ); ?></code></td>
						<td><code><?php echo esc_html( $scope . ' / ' . $scope_decision ); ?></code></td>
						<td><code><?php echo esc_html( $correlation_id ); ?></code></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Returns governance audit filters from query args.
	 *
	 * @return array<string,mixed>
	 */
	private function audit_filters_from_request(): array {
		return array(
			'proposal_id'    => isset( $_GET['audit_proposal_id'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['audit_proposal_id'] ) ) : '',
			'event_name'     => isset( $_GET['audit_event_name'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['audit_event_name'] ) ) : '',
			'ability_id'     => isset( $_GET['audit_ability_id'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['audit_ability_id'] ) ) : '',
			'app_id'         => isset( $_GET['audit_app_id'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['audit_app_id'] ) ) : '',
			'caller_type'    => isset( $_GET['audit_caller_type'] ) ? sanitize_key( wp_unslash( (string) $_GET['audit_caller_type'] ) ) : '',
			'correlation_id' => isset( $_GET['audit_correlation_id'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['audit_correlation_id'] ) ) : '',
			'limit'          => isset( $_GET['audit_limit'] ) ? max( 1, min( 200, absint( wp_unslash( (string) $_GET['audit_limit'] ) ) ) ) : 50,
		);
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

		return 50 !== (int) ( $filters['limit'] ?? 50 );
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
	 * Returns admin page URL.
	 *
	 * @param array<string,string> $args Query args.
	 * @return string
	 */
	private function admin_url( array $args = array() ): string {
		return add_query_arg( array_merge( array( 'page' => self::MENU_SLUG ), $args ), admin_url( 'admin.php' ) );
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
			'app_key_revoked'                               => __( 'App key disabled.', 'magick-ai-core' ),
			'magick_ai_core_app_key_not_active'             => __( 'App key is missing or already disabled.', 'magick-ai-core' ),
			'magick_ai_core_app_key_revoke_failed'          => __( 'App key could not be disabled.', 'magick-ai-core' ),
			'magick_ai_core_proposal_not_found'             => __( 'Proposal was not found.', 'magick-ai-core' ),
			'magick_ai_core_proposal_already_decided'       => __( 'Only pending proposals can be approved or rejected.', 'magick-ai-core' ),
			'magick_ai_core_proposal_transition_failed'     => __( 'Proposal status could not be updated.', 'magick-ai-core' ),
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
