<?php
/**
 * Existing Core admin view cluster.
 *
 * @package NpcinkGovernanceCore
 */

namespace Npcink\GovernanceCore\Admin;

use Npcink\GovernanceCore\Governance\Proposal_Repository;
use Npcink\GovernanceCore\Security\App_Key_Repository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Private presentation methods composed by Admin_Page. */
trait Admin_Settings_And_Archive_View {
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
		<?php $this->render_created_app_key_panel(); ?>

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
						<input id="npcink-governance-core-app-label" class="regular-text" type="text" name="app_label" placeholder="<?php echo esc_attr__( 'For example: OpenClaw adapter on titan', 'npcink-governance-core' ); ?>" />
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
			<p class="npcink-governance-core-muted">
				<?php
				printf(
					/* translators: %s: URL of the settings view. */
					esc_html__( 'Revoked tokens are removed automatically once they pass the history retention window configured in %s.', 'npcink-governance-core' ),
					'<a href="' . esc_url( $this->view_url( 'settings' ) ) . '">' . esc_html__( 'Settings', 'npcink-governance-core' ) . '</a>'
				);
				?>
			</p>
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
					<?php if ( ! empty( $scopes ) ) : ?>
						<span class="npcink-governance-core-token-preset-scopes"><?php echo esc_html( $this->token_scope_summary( $scopes ) ); ?></span>
					<?php endif; ?>
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
	 * Renders the one-time client access token result inside the normal admin
	 * page, or nothing when no token payload is pending.
	 *
	 * @return void
	 */
	private function render_created_app_key_panel(): void {
		$payload = get_transient( 'npcink_governance_core_new_app_key_' . get_current_user_id() );
		if ( ! is_array( $payload ) || '' === (string) ( $payload['token'] ?? '' ) ) {
			return;
		}
		delete_transient( 'npcink_governance_core_new_app_key_' . get_current_user_id() );

		$token = (string) $payload['token'];
		?>
		<section class="npcink-governance-core-token-panel npcink-governance-core-max-wide" aria-labelledby="npcink-governance-core-token-panel-heading">
			<h3 id="npcink-governance-core-token-panel-heading"><?php echo esc_html__( 'Client access token created', 'npcink-governance-core' ); ?></h3>
			<div class="notice notice-warning inline">
				<p><?php echo esc_html__( 'Copy this token now. It is shown only once and is not stored in raw form.', 'npcink-governance-core' ); ?></p>
				<p><?php echo esc_html__( 'Use this token only in a trusted adapter or internal governance client secret store.', 'npcink-governance-core' ); ?></p>
			</div>
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row"><?php echo esc_html__( 'App ID', 'npcink-governance-core' ); ?></th>
						<td><code><?php echo esc_html( (string) $payload['app_id'] ); ?></code></td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Key ID', 'npcink-governance-core' ); ?></th>
						<td><code><?php echo esc_html( (string) $payload['key_id'] ); ?></code></td>
					</tr>
					<tr>
						<th scope="row">
							<label for="npcink-governance-core-new-token"><?php echo esc_html__( 'Access token', 'npcink-governance-core' ); ?></label>
						</th>
						<td>
							<textarea id="npcink-governance-core-new-token" rows="3" readonly class="large-text"><?php echo esc_textarea( $token ); ?></textarea>
							<button type="button" class="button" data-npcink-copy-target="npcink-governance-core-new-token" data-copied-label="<?php echo esc_attr__( 'Copied', 'npcink-governance-core' ); ?>">
								<?php echo esc_html__( 'Copy token', 'npcink-governance-core' ); ?>
							</button>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="npcink-governance-core-new-token-env"><?php echo esc_html__( 'Core env', 'npcink-governance-core' ); ?></label>
						</th>
						<td>
							<textarea id="npcink-governance-core-new-token-env" rows="4" readonly class="large-text"><?php echo esc_textarea( $this->core_env_text( $token ) ); ?></textarea>
							<button type="button" class="button" data-npcink-copy-target="npcink-governance-core-new-token-env" data-copied-label="<?php echo esc_attr__( 'Copied', 'npcink-governance-core' ); ?>">
								<?php echo esc_html__( 'Copy env snippet', 'npcink-governance-core' ); ?>
							</button>
						</td>
					</tr>
				</tbody>
			</table>
		</section>
		<?php
	}

}
