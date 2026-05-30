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
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
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
		add_management_page(
			__( 'Magick AI Core', 'magick-ai-core' ),
			__( 'Magick AI Core', 'magick-ai-core' ),
			'manage_options',
			'magick-ai-core',
			array( $this, 'render' )
		);
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

			<?php $this->render_external_access(); ?>

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

		$raw_scopes        = isset( $_POST['scopes'] ) && is_array( $_POST['scopes'] ) ? wp_unslash( $_POST['scopes'] ) : array();
		$include_local_tls = ! empty( $_POST['include_local_tls'] );
		$app               = $this->apps->create(
			array(
				'app_label'           => isset( $_POST['app_label'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['app_label'] ) ) : 'External app',
				'caller_type'         => isset( $_POST['caller_type'] ) ? sanitize_key( wp_unslash( (string) $_POST['caller_type'] ) ) : 'mcp_adapter',
				'scopes'              => is_array( $raw_scopes ) ? $raw_scopes : array(),
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
		$this->render_created_app_key( $app, $include_local_tls );
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
	 * Renders external app access section.
	 *
	 * @return void
	 */
	private function render_external_access(): void {
		$base_url          = home_url();
		$rest_url          = rest_url( 'magick-ai-core/v1' );
		$apps              = $this->apps->list_recent( 10 );
		$default_local_tls = $this->is_local_base_url( $base_url );
		?>
		<h2><?php echo esc_html__( 'External App Access', 'magick-ai-core' ); ?></h2>
		<p><?php echo esc_html__( 'Issue scoped app keys for external governance clients. Human approval remains in Core.', 'magick-ai-core' ); ?></p>
		<table class="widefat striped" style="max-width: 1100px;">
			<tbody>
				<tr>
					<th scope="row"><?php echo esc_html__( 'Base URL', 'magick-ai-core' ); ?></th>
					<td><code><?php echo esc_html( $base_url ); ?></code></td>
				</tr>
				<tr>
					<th scope="row"><?php echo esc_html__( 'Core REST URL', 'magick-ai-core' ); ?></th>
					<td><code><?php echo esc_html( $rest_url ); ?></code></td>
				</tr>
				<tr>
					<th scope="row"><?php echo esc_html__( 'OpenClaw env', 'magick-ai-core' ); ?></th>
					<td>
						<pre style="margin: 0;">MAGICK_AI_CORE_BASE_URL=<?php echo esc_html( $base_url ); ?>
MAGICK_AI_CORE_APP_TOKEN=mai_core.key_xxx.secret_xxx</pre>
					</td>
				</tr>
			</tbody>
		</table>

		<h3><?php echo esc_html__( 'OpenClaw Handoff', 'magick-ai-core' ); ?></h3>
		<p><?php echo esc_html__( 'Copy this guide with the environment values when configuring an external agent client.', 'magick-ai-core' ); ?></p>
		<textarea class="large-text code" rows="18" readonly><?php echo esc_textarea( $this->openclaw_handoff_text( 'mai_core.key_xxx.secret_xxx', false ) ); ?></textarea>

		<h3><?php echo esc_html__( 'Create App Key', 'magick-ai-core' ); ?></h3>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="max-width: 1100px;">
			<input type="hidden" name="action" value="magick_ai_core_create_app_key" />
			<?php wp_nonce_field( 'magick_ai_core_create_app_key' ); ?>
			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row"><label for="magick-ai-core-app-label"><?php echo esc_html__( 'App label', 'magick-ai-core' ); ?></label></th>
						<td><input id="magick-ai-core-app-label" class="regular-text" type="text" name="app_label" value="OpenClaw Adapter" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="magick-ai-core-caller-type"><?php echo esc_html__( 'Caller type', 'magick-ai-core' ); ?></label></th>
						<td><input id="magick-ai-core-caller-type" class="regular-text" type="text" name="caller_type" value="mcp_adapter" /></td>
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
					<tr>
						<th scope="row"><?php echo esc_html__( 'Local testing', 'magick-ai-core' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="include_local_tls" value="1" <?php checked( $default_local_tls ); ?> />
								<?php echo esc_html__( 'Include LocalWP TLS test setting in OpenClaw env and handoff.', 'magick-ai-core' ); ?>
							</label>
							<p class="description"><?php echo esc_html__( 'Use only for localhost or .local testing. This only changes copied client configuration; it does not change Core server security.', 'magick-ai-core' ); ?></p>
						</td>
					</tr>
				</tbody>
			</table>
			<p><button type="submit" class="button button-primary"><?php echo esc_html__( 'Create App Key', 'magick-ai-core' ); ?></button></p>
		</form>

		<h3><?php echo esc_html__( 'Recent App Keys', 'magick-ai-core' ); ?></h3>
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
	private function render_created_app_key( array $app, bool $include_local_tls ): void {
		$base_url = home_url();
		$token    = (string) ( $app['token'] ?? '' );
		?>
		<!doctype html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta charset="<?php echo esc_attr( get_bloginfo( 'charset' ) ); ?>" />
			<meta name="viewport" content="width=device-width, initial-scale=1" />
			<title><?php echo esc_html__( 'App Key Created', 'magick-ai-core' ); ?></title>
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
				<h1><?php echo esc_html__( 'App Key Created', 'magick-ai-core' ); ?></h1>
				<div class="notice">
					<p><?php echo esc_html__( 'Copy this token now. It is shown only once and is not stored in raw form.', 'magick-ai-core' ); ?></p>
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
							<th scope="row"><?php echo esc_html__( 'OpenClaw env', 'magick-ai-core' ); ?></th>
							<td><textarea rows="5" readonly><?php echo esc_textarea( $this->openclaw_env_text( $token, $include_local_tls ) ); ?></textarea></td>
						</tr>
						<tr>
							<th scope="row"><?php echo esc_html__( 'OpenClaw handoff', 'magick-ai-core' ); ?></th>
							<td><textarea rows="18" readonly><?php echo esc_textarea( $this->openclaw_handoff_text( $token, $include_local_tls ) ); ?></textarea></td>
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
		return add_query_arg( array_merge( array( 'page' => 'magick-ai-core' ), $args ), admin_url( 'tools.php' ) );
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
	 * Returns copyable OpenClaw setup guidance.
	 *
	 * @param string $token App token or placeholder.
	 * @param bool   $include_local_tls Whether to include local TLS env.
	 * @return string
	 */
	private function openclaw_handoff_text( string $token, bool $include_local_tls ): string {
		return "Magick AI Core connection\n"
			. $this->openclaw_env_text( $token, $include_local_tls ) . "\n\n"
			. "# LocalWP only: set MAGICK_AI_CORE_INSECURE_SSL=true when a .local/localhost self-signed certificate blocks local testing.\n"
			. "# Prefer MAGICK_AI_CORE_CA_BUNDLE=/path/to/local-ca.pem when a local CA bundle is available.\n\n"
			. "Agent rules\n"
			. "1. Treat Magick AI Core as the WordPress governance layer, not as a protocol runtime or content generator.\n"
			. "2. Call capabilities first and use only real ability_id values returned by Core.\n"
			. "3. Create proposals for risky WordPress operations; do not approve proposals by default.\n"
			. "4. Human approval remains in WordPress unless a trusted host policy is separately contracted.\n"
			. "5. After approval, call commit-preflight to get approval context; Core still returns commit_execution=false.\n"
			. "6. Do not store or print MAGICK_AI_CORE_APP_TOKEN in logs, proposal payloads, prompts, or files.\n"
			. "7. Stop and report the reason on 401, 403, or 429 responses.\n\n"
			. "Example commands\n"
			. "php examples/openclaw-governance-adapter/openclaw-governance-adapter.php capabilities\n\n"
			. "php examples/openclaw-governance-adapter/openclaw-governance-adapter.php create-proposal \\\n"
			. "  --ability=magick-ai/create-draft \\\n"
			. "  --title=\"OpenClaw draft proposal\" \\\n"
			. "  --summary=\"Review before creating a draft.\" \\\n"
			. "  --input='{\"title\":\"Draft title\",\"content\":\"<p>Draft body.</p>\",\"dry_run\":true}' \\\n"
			. "  --preview='{\"dry_run\":true,\"source\":\"openclaw\"}'\n\n"
			. "php examples/openclaw-governance-adapter/openclaw-governance-adapter.php commit-preflight \\\n"
			. "  --proposal=<proposal_id>";
	}

	/**
	 * Returns OpenClaw environment text.
	 *
	 * @param string $token App token or placeholder.
	 * @param bool   $include_local_tls Whether to include local TLS env.
	 * @return string
	 */
	private function openclaw_env_text( string $token, bool $include_local_tls ): string {
		$lines = array(
			'MAGICK_AI_CORE_BASE_URL=' . home_url(),
			'MAGICK_AI_CORE_APP_TOKEN=' . $token,
		);

		if ( $include_local_tls ) {
			$lines[] = 'MAGICK_AI_CORE_INSECURE_SSL=true';
		}

		return implode( "\n", $lines );
	}

	/**
	 * Returns whether a base URL is a local testing host.
	 *
	 * @param string $url URL.
	 * @return bool
	 */
	private function is_local_base_url( string $url ): bool {
		$host = wp_parse_url( $url, PHP_URL_HOST );
		if ( ! is_string( $host ) ) {
			return false;
		}

		$host = strtolower( trim( $host, '[]' ) );
		return in_array( $host, array( 'localhost', '127.0.0.1', '::1' ), true ) || ( strlen( $host ) >= 6 && '.local' === substr( $host, -6 ) );
	}
}
