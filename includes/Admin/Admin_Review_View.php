<?php
/**
 * Existing Core admin view cluster.
 *
 * @package NpcinkGovernanceCore
 */

namespace Npcink\GovernanceCore\Admin;

use Npcink\GovernanceCore\Governance\Approval_Policy_Evaluator;
use Npcink\GovernanceCore\Governance\History_Cleanup_Service;
use Npcink\GovernanceCore\Governance\Proposal_Repository;
use Npcink\GovernanceCore\Governance\Proposal_Service;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Private presentation methods composed by Admin_Page. */
trait Admin_Review_View {
	/**
	 * Renders the default queue-first workbench.
	 *
	 * @param array<int,array<string,mixed>> $pending Pending proposals.
	 * @param int                            $pending_count Pending proposal count.
	 * @param int                            $page Current review page.
	 * @return void
	 */
	private function render_review_workbench( array $pending, int $pending_count, int $page, array $filters = array(), string $lookup_id = '' ): void {
		?>
		<?php $this->render_queue_summary( $this->proposals->count_by_status( Proposal_Repository::STATUS_PENDING ) ); ?>
		<?php $this->render_workbench_toolbar( $lookup_id ); ?>
		<?php $this->render_review_filters( $filters, $this->proposals->distinct_pending_ability_ids() ); ?>
		<?php $this->render_pending_proposals( $pending, $pending_count, $page, $filters ); ?>
		<?php
	}

	/**
	 * Returns active review-queue filters from the request.
	 *
	 * @return array<string,mixed>
	 */
	private function review_filters_from_request(): array {
		$filters = array();
		$ability = $this->admin_query_text( 'review_ability' );
		if ( '' !== $ability ) {
			$filters['ability_id'] = $ability;
		}

		$age  = $this->admin_query_key( 'review_age', '' );
		$ages = array(
			'24h' => DAY_IN_SECONDS,
			'7d'  => 7 * DAY_IN_SECONDS,
			'30d' => 30 * DAY_IN_SECONDS,
		);
		if ( isset( $ages[ $age ] ) ) {
			$filters['age_key']        = $age;
			$filters['created_before'] = gmdate( 'Y-m-d H:i:s', time() - $ages[ $age ] );
		}

		return $filters;
	}

	/**
	 * Returns URL query args that preserve the current review filters.
	 *
	 * @return array<string,string>
	 */
	private function review_filter_query_args(): array {
		return $this->review_filter_query_args_for( $this->review_filters_from_request() );
	}

	/**
	 * Returns URL query args for the given review filters.
	 *
	 * @param array<string,mixed> $filters Filters.
	 * @return array<string,string>
	 */
	private function review_filter_query_args_for( array $filters ): array {
		$args = array();
		if ( ! empty( $filters['ability_id'] ) ) {
			$args['review_ability'] = (string) $filters['ability_id'];
		}
		if ( ! empty( $filters['age_key'] ) ) {
			$args['review_age'] = (string) $filters['age_key'];
		}

		return $args;
	}

	/**
	 * Renders the review queue filter bar.
	 *
	 * @param array<string,mixed> $filters Active filters.
	 * @param array<int,string>   $ability_options Distinct pending ability ids.
	 * @return void
	 */
	private function render_review_filters( array $filters, array $ability_options ): void {
		$ability = (string) ( $filters['ability_id'] ?? '' );
		$age     = (string) ( $filters['age_key'] ?? '' );
		$ages    = array(
			'24h' => __( 'Older than 24 hours', 'npcink-governance-core' ),
			'7d'  => __( 'Older than 7 days', 'npcink-governance-core' ),
			'30d' => __( 'Older than 30 days', 'npcink-governance-core' ),
		);
		?>
		<form class="npcink-governance-core-review-filters npcink-governance-core-max-wide" method="get" action="<?php echo esc_url( $this->admin_base_url() ); ?>">
			<input type="hidden" name="page" value="<?php echo esc_attr( self::MENU_SLUG ); ?>" />
			<label for="npcink-governance-core-filter-ability"><?php echo esc_html__( 'Ability', 'npcink-governance-core' ); ?></label>
			<select id="npcink-governance-core-filter-ability" name="review_ability">
				<option value=""><?php echo esc_html__( 'All abilities', 'npcink-governance-core' ); ?></option>
				<?php foreach ( $ability_options as $option ) : ?>
					<option value="<?php echo esc_attr( $option ); ?>" <?php selected( $option, $ability ); ?>><?php echo esc_html( $option ); ?></option>
				<?php endforeach; ?>
			</select>
			<label for="npcink-governance-core-filter-age"><?php echo esc_html__( 'Waiting', 'npcink-governance-core' ); ?></label>
			<select id="npcink-governance-core-filter-age" name="review_age">
				<option value=""><?php echo esc_html__( 'Any age', 'npcink-governance-core' ); ?></option>
				<?php foreach ( $ages as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key, $age ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
			<button type="submit" class="button"><?php echo esc_html__( 'Filter', 'npcink-governance-core' ); ?></button>
			<?php if ( '' !== $ability || '' !== $age ) : ?>
				<a class="button-link" href="<?php echo esc_url( $this->admin_url() ); ?>"><?php echo esc_html__( 'Clear filters', 'npcink-governance-core' ); ?></a>
			<?php endif; ?>
		</form>
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
		$expired_count          = $this->proposals->count_by_status( Proposal_Repository::STATUS_EXPIRED );
		$pending_reads_count    = $this->read_requests->count_pending_unexpired();
		$activity_filters       = array(
			'exclude_event_names' => $this->low_value_audit_events(),
			'created_after'       => $this->audit_created_after_for_range( '30d' ),
		);
		$activity_count         = $this->audit->count_filtered( $activity_filters );
		$audit_overview_url     = $this->view_url( 'audit' );
		?>
		<div class="npcink-governance-core-summary-strip npcink-governance-core-max-wide">
			<?php $this->render_summary_item( __( 'Needs review', 'npcink-governance-core' ), (string) $pending_count, __( 'Pending proposals waiting for an administrator decision.', 'npcink-governance-core' ), 'warning', $this->admin_url() ); ?>
			<?php if ( $expired_count > 0 ) : ?>
				<?php $this->render_summary_item( __( 'Expired unreviewed', 'npcink-governance-core' ), (string) $expired_count, __( 'Proposals that expired while waiting for a decision. The filtered activity view lists each expiry.', 'npcink-governance-core' ), 'neutral', $this->admin_url( array( 'view' => 'audit', 'audit_time_range' => 'all', 'audit_event_name' => 'proposal.expired' ) ) ); ?>
			<?php endif; ?>
			<?php $this->render_summary_item( __( 'Approved', 'npcink-governance-core' ), (string) $approved_count, __( 'Approved proposals waiting for Adapter preflight or execution record. The activity log shows recent decision events.', 'npcink-governance-core' ), 'ok', $audit_overview_url ); ?>
			<?php $this->render_summary_item( __( 'Execution failed', 'npcink-governance-core' ), (string) $execution_failed_count, __( 'Adapter-reported failures that need operator follow-up. The activity log shows recent failure events.', 'npcink-governance-core' ), $execution_failed_count > 0 ? 'error' : 'inactive', $audit_overview_url ); ?>
			<?php if ( $pending_reads_count > 0 ) : ?>
				<?php $this->render_summary_item( __( 'Sensitive reads', 'npcink-governance-core' ), (string) $pending_reads_count, __( 'Pending sensitive read requests waiting for approval. The linked activity view lists every created request.', 'npcink-governance-core' ), 'warning', $this->admin_url( array( 'view' => 'audit', 'audit_time_range' => 'all', 'audit_event_name' => 'read_request.created' ) ) ); ?>
			<?php endif; ?>
			<?php $this->render_summary_item( __( 'Audit events', 'npcink-governance-core' ), (string) $activity_count, __( 'Governance events in the last 30 days, excluding list and view noise.', 'npcink-governance-core' ), 'neutral', $audit_overview_url ); ?>
		</div>
		<?php
	}

	/**
	 * Renders one queue summary item.
	 *
	 * @param string      $label Item label.
	 * @param string      $value Item value.
	 * @param string      $detail Item detail.
	 * @param string      $tone Visual tone.
	 * @param string|null $url Optional deep-link target.
	 * @return void
	 */
	private function render_summary_item( string $label, string $value, string $detail, string $tone, ?string $url = null ): void {
		$classes = 'npcink-governance-core-summary-item npcink-governance-core-summary-' . sanitize_html_class( $tone );
		if ( null !== $url ) {
			$classes .= ' npcink-governance-core-summary-link';
		}
		$inner = '<div class="npcink-governance-core-summary-label">' . esc_html( $label ) . '</div>'
			. '<div class="npcink-governance-core-summary-value">' . esc_html( $value ) . '</div>'
			. '<div class="npcink-governance-core-summary-detail">' . esc_html( $detail ) . '</div>';
		?>
		<?php if ( null !== $url ) : ?>
			<a class="<?php echo esc_attr( $classes ); ?>" href="<?php echo esc_url( $url ); ?>"><?php echo wp_kses_post( $inner ); ?></a>
		<?php else : ?>
			<div class="<?php echo esc_attr( $classes ); ?>"><?php echo wp_kses_post( $inner ); ?></div>
		<?php endif; ?>
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
			<form class="npcink-governance-core-inline-actions" method="get" action="<?php echo esc_url( $this->admin_base_url() ); ?>">
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
			Approval_Policy_Evaluator::MODE_SMART_GUARDED   => __( 'Smart guarded approval', 'npcink-governance-core' ),
			Approval_Policy_Evaluator::MODE_DEV_ALLOW_ALL   => __( 'Allow all (development only)', 'npcink-governance-core' ),
		);
		?>
		<section class="npcink-governance-core-settings-section npcink-governance-core-max-wide" aria-labelledby="npcink-governance-core-approval-policy-heading">
			<h3 id="npcink-governance-core-approval-policy-heading"><?php echo esc_html__( 'Approval Policy', 'npcink-governance-core' ); ?></h3>
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
		<p class="npcink-governance-core-subtle"><?php echo esc_html__( 'Approval policy, history retention, and trusted governance client access.', 'npcink-governance-core' ); ?></p>
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
	private function render_pending_proposals( array $pending, int $total, int $page, array $filters = array() ): void {
		?>
		<h2><?php echo esc_html__( 'Pending requests', 'npcink-governance-core' ); ?></h2>
		<form class="npcink-governance-core-max-wide" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="npcink_governance_core_bulk_reject_proposals" />
			<input type="hidden" name="review_page" value="<?php echo esc_attr( (string) max( 1, $page ) ); ?>" />
			<?php foreach ( $this->review_filter_query_args_for( $filters ) as $filter_key => $filter_value ) : ?>
				<input type="hidden" name="<?php echo esc_attr( $filter_key ); ?>" value="<?php echo esc_attr( $filter_value ); ?>" />
			<?php endforeach; ?>
			<?php wp_nonce_field( 'npcink_governance_core_bulk_reject_proposals' ); ?>
			<?php $this->render_review_queue_nav( $total, $page, ! empty( $pending ), $this->review_filter_query_args_for( $filters ) ); ?>
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
							<?php if ( ! empty( $filters ) ) : ?>
								<tr>
									<td colspan="7">
										<div class="npcink-governance-core-empty-state">
											<strong><?php echo esc_html__( 'No pending requests match the current filters.', 'npcink-governance-core' ); ?></strong>
											<span class="npcink-governance-core-empty-actions">
												<a href="<?php echo esc_url( $this->admin_url() ); ?>"><?php echo esc_html__( 'Clear filters', 'npcink-governance-core' ); ?></a>
											</span>
										</div>
									</td>
								</tr>
							<?php else : ?>
							<tr>
								<td colspan="7">
									<div class="npcink-governance-core-empty-state">
										<strong><?php echo esc_html__( 'No requests need review.', 'npcink-governance-core' ); ?></strong>
										<span><?php echo esc_html__( 'Find a proposal by ID, inspect recent activity, or open historical records when you need audit context.', 'npcink-governance-core' ); ?></span>
										<?php $expired_count = (int) $this->proposals->count_by_status( Proposal_Repository::STATUS_EXPIRED ); ?>
										<?php if ( $expired_count > 0 ) : ?>
											<span>
												<?php
												echo esc_html( sprintf(
													/* translators: 1: expired proposal count, 2: pending TTL hours. */
													_n( '%1$d proposal expired without a review decision. Pending proposals expire after %2$d hours of waiting.', '%1$d proposals expired without a review decision. Pending proposals expire after %2$d hours of waiting.', $expired_count, 'npcink-governance-core' ),
													$expired_count,
													(int) ( Proposal_Service::PENDING_TTL_SECONDS / HOUR_IN_SECONDS )
												) );
												?>
												<a href="<?php echo esc_url( $this->admin_url( array( 'view' => 'audit', 'audit_time_range' => 'all', 'audit_event_name' => 'proposal.expired' ) ) ); ?>"><?php echo esc_html__( 'Review expired requests', 'npcink-governance-core' ); ?></a>
											</span>
										<?php endif; ?>
										<span class="npcink-governance-core-empty-actions">
											<a href="#npcink-governance-core-proposal-lookup"><?php echo esc_html__( 'Find proposal', 'npcink-governance-core' ); ?></a>
											<a href="<?php echo esc_url( $this->view_url( 'audit' ) ); ?>"><?php echo esc_html__( 'Open activity log', 'npcink-governance-core' ); ?></a>
											<a href="<?php echo esc_url( $this->view_url( 'archive' ) ); ?>"><?php echo esc_html__( 'Open history', 'npcink-governance-core' ); ?></a>
										</span>
									</div>
								</td>
							</tr>
							<?php endif; ?>
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
						<button
							type="submit"
							class="button"
							<?php /* translators: %d: number of selected proposals. */ ?>
							data-npcink-bulk-confirm="<?php echo esc_attr__( 'Reject the %d selected proposal(s)? Rejections cannot be undone.', 'npcink-governance-core' ); ?>"
						>
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
	private function render_review_queue_nav( int $total, int $page, bool $show_bulk, array $args = array() ): void {
		if ( $total <= 0 ) {
			return;
		}

		$this->render_table_nav(
			$total,
			$page,
			self::REVIEW_PAGE_SIZE,
			'review_page',
			$args,
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
				'value' => $this->caller_type_label( (string) ( $auth['caller_type'] ?? $caller['caller_type'] ?? '' ) ),
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
				'value' => $this->policy_decision_label( (string) ( $proposal['policy_decision'] ?? '' ) ),
				'code'  => true,
			),
			array(
				'label' => __( 'Policy profile', 'npcink-governance-core' ),
				'value' => $this->policy_profile_label( (string) ( $proposal['policy_profile'] ?? '' ) ),
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

		$registry_label = $this->registry_ability_label( $ability_id );
		if ( '' !== $registry_label ) {
			return $registry_label;
		}

		$title = trim( (string) ( $proposal['title'] ?? '' ) );
		if ( '' !== $title && false === strpos( strtolower( $title ), 'pending quota proposal' ) ) {
			return $title;
		}

		return __( 'Review WordPress change', 'npcink-governance-core' );
	}

	/**
	 * Returns the intake registry label for an ability id, or an empty string.
	 *
	 * The registry is consulted once per request for any ability id outside
	 * the translated first-party label map, so third-party provider labels
	 * surface without Core hardcoding provider-specific knowledge. Registry
	 * rows whose label equals the raw ability id add no operator context and
	 * are skipped.
	 *
	 * @param string $ability_id Ability id.
	 * @return string
	 */
	private function registry_ability_label( string $ability_id ): string {
		if ( '' === $ability_id ) {
			return '';
		}

		if ( null === $this->registry_label_cache ) {
			$this->registry_label_cache = array();
			$capabilities               = $this->abilities->list_capabilities();
			foreach ( (array) ( $capabilities['items'] ?? array() ) as $item ) {
				if ( ! is_array( $item ) ) {
					continue;
				}
				$id    = (string) ( $item['ability_id'] ?? '' );
				$label = trim( (string) ( $item['label'] ?? '' ) );
				if ( '' !== $id && '' !== $label && $label !== $id ) {
					$this->registry_label_cache[ $id ] = $label;
				}
			}
		}

		return (string) ( $this->registry_label_cache[ $ability_id ] ?? '' );
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
	 * Returns the operator-facing label for a caller type value.
	 *
	 * @param string $value Raw caller type.
	 * @return string
	 */
	private function caller_type_label( string $value ): string {
		$labels = array(
			'external_app'    => __( 'External app', 'npcink-governance-core' ),
			'product_adapter' => __( 'Product adapter', 'npcink-governance-core' ),
			'mcp_adapter'     => __( 'MCP adapter', 'npcink-governance-core' ),
			'agent_host'      => __( 'Agent host', 'npcink-governance-core' ),
			'internal'        => __( 'Internal governance client', 'npcink-governance-core' ),
			'wp_admin'        => __( 'WordPress admin', 'npcink-governance-core' ),
			'admin'           => __( 'WordPress admin', 'npcink-governance-core' ),
		);

		return (string) ( $labels[ $value ] ?? $value );
	}

	/**
	 * Returns the operator-facing label for an approval policy decision.
	 *
	 * @param string $value Raw policy decision.
	 * @return string
	 */
	private function policy_decision_label( string $value ): string {
		$labels = array(
			'manual_required' => __( 'Manual approval required', 'npcink-governance-core' ),
			'auto_approved'   => __( 'Auto-approved by policy', 'npcink-governance-core' ),
			'blocked'         => __( 'Blocked by policy', 'npcink-governance-core' ),
		);

		return (string) ( $labels[ $value ] ?? $value );
	}

	/**
	 * Returns the operator-facing label for an approval policy profile.
	 *
	 * @param string $value Raw policy profile.
	 * @return string
	 */
	private function policy_profile_label( string $value ): string {
		$labels = array(
			'manual'        => __( 'Manual review', 'npcink-governance-core' ),
			'guarded'       => __( 'Guarded automation', 'npcink-governance-core' ),
			'trusted_local' => __( 'Trusted local consent', 'npcink-governance-core' ),
			'break_glass'   => __( 'Break-glass', 'npcink-governance-core' ),
		);

		return (string) ( $labels[ $value ] ?? $value );
	}

	/**
	 * Returns the operator-facing label for one governance-envelope value.
	 *
	 * @param string $field Envelope field key.
	 * @param string $value Raw enum value.
	 * @return string
	 */
	private function review_basis_label( string $field, string $value ): string {
		$maps = array(
			'request_source'       => array(
				'wp_admin_ui'      => __( 'WordPress admin UI', 'npcink-governance-core' ),
				'external_adapter' => __( 'External adapter', 'npcink-governance-core' ),
				'scheduled_task'   => __( 'Scheduled task', 'npcink-governance-core' ),
				'cli'              => __( 'CLI', 'npcink-governance-core' ),
				'cloud_callback'   => __( 'Cloud callback', 'npcink-governance-core' ),
			),
			'actor_presence'       => array(
				'present_click' => __( 'Operator present at click', 'npcink-governance-core' ),
				'background'    => __( 'Background automation', 'npcink-governance-core' ),
				'delegated'     => __( 'Delegated agent', 'npcink-governance-core' ),
			),
			'preview_completeness' => array(
				'exact_final' => __( 'Exact final preview', 'npcink-governance-core' ),
				'sufficient'  => __( 'Sufficient preview', 'npcink-governance-core' ),
				'partial'     => __( 'Partial preview', 'npcink-governance-core' ),
				'none'        => __( 'No preview', 'npcink-governance-core' ),
			),
			'scope'                => array(
				'one_field'        => __( 'One field', 'npcink-governance-core' ),
				'one_object'       => __( 'One object', 'npcink-governance-core' ),
				'multiple_objects' => __( 'Multiple objects', 'npcink-governance-core' ),
				'site_wide'        => __( 'Site-wide', 'npcink-governance-core' ),
				'external_account' => __( 'External account', 'npcink-governance-core' ),
			),
			'operation_kind'       => array(
				'suggest'                 => __( 'Suggestion only', 'npcink-governance-core' ),
				'create_draft'            => __( 'Create draft', 'npcink-governance-core' ),
				'update_metadata'         => __( 'Update metadata', 'npcink-governance-core' ),
				'update_existing_terms'   => __( 'Update existing terms', 'npcink-governance-core' ),
				'set_featured_image'      => __( 'Set featured image', 'npcink-governance-core' ),
				'publish'                 => __( 'Publish', 'npcink-governance-core' ),
				'unpublish'               => __( 'Unpublish', 'npcink-governance-core' ),
				'delete'                  => __( 'Delete', 'npcink-governance-core' ),
				'replace_file'            => __( 'Replace file', 'npcink-governance-core' ),
				'overwrite_content'       => __( 'Overwrite content', 'npcink-governance-core' ),
				'settings_change'         => __( 'Settings change', 'npcink-governance-core' ),
				'permission_change'       => __( 'Permission change', 'npcink-governance-core' ),
				'external_account_change' => __( 'External account change', 'npcink-governance-core' ),
				'batch_plan'              => __( 'Batch plan', 'npcink-governance-core' ),
			),
		);

		$field_map = $maps[ $field ] ?? array();

		return (string) ( $field_map[ $value ] ?? $value );
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
	 * Read-noise events are excluded so the panel surfaces decisions and
	 * lifecycle evidence rather than the access log produced by polling.
	 *
	 * @return void
	 */
	private function render_recent_activity(): void {
		$events = $this->audit->list_filtered(
			array(
				'limit'                => 5,
				'order'                => 'desc',
				'exclude_event_names'  => $this->low_value_audit_events(),
			)
		);
		?>
		<div class="npcink-governance-core-utility-panel npcink-governance-core-utility-panel-stretch">
			<div>
				<strong><?php echo esc_html__( 'Recent Activity', 'npcink-governance-core' ); ?></strong>
				<span class="npcink-governance-core-muted"><?php echo esc_html__( 'Latest Core governance events. Full audit is in its own tab.', 'npcink-governance-core' ); ?></span>
			</div>
			<div class="npcink-governance-core-secondary-row-main">
				<?php if ( empty( $events ) ) : ?>
					<?php echo esc_html__( 'No recent governance activity.', 'npcink-governance-core' ); ?>
				<?php else : ?>
					<ul class="npcink-governance-core-recent-activity-list">
						<?php foreach ( $events as $event ) : ?>
							<?php $proposal_id = (string) ( $event['proposal_id'] ?? '' ); ?>
							<li>
								<span class="npcink-governance-core-muted"><?php echo esc_html( $this->display_datetime( (string) $event['created_at'] ) ); ?></span>
								<span><?php echo esc_html( $this->audit_event_label( (string) $event['event_name'] ) ); ?></span>
								<?php if ( '' !== $proposal_id ) : ?>
									<a href="<?php echo esc_url( $this->detail_url( $proposal_id ) ); ?>"><code><?php echo esc_html( $proposal_id ); ?></code></a>
								<?php else : ?>
									<span class="npcink-governance-core-muted"><?php echo esc_html__( 'System', 'npcink-governance-core' ); ?></span>
								<?php endif; ?>
							</li>
						<?php endforeach; ?>
					</ul>
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

}
