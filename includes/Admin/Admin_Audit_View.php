<?php
/**
 * Existing Core admin view cluster.
 *
 * @package NpcinkGovernanceCore
 */

namespace Npcink\GovernanceCore\Admin;

use Npcink\GovernanceCore\Governance\Proposal_Repository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Private presentation methods composed by Admin_Page. */
trait Admin_Audit_View {
	/**
	 * Renders proposal audit timeline.
	 *
	 * @param array<string,mixed>            $proposal Proposal row.
	 * @param array<int,array<string,mixed>> $events Audit events, newest first.
	 * @param bool                           $open Deprecated open flag.
	 * @param int                            $total Total audit events for the proposal.
	 * @return void
	 */
	private function render_audit_timeline( array $proposal, array $events, bool $open = false, int $total = 0 ): void {
		unset( $open );
		$this->render_audit_outcome_summary( $proposal, $events );
		$this->render_audit_lifecycle_summary( $events );
		$shown  = count( $events );
		$is_truncated = $total > $shown;
		?>
		<details class="npcink-governance-core-disclosure npcink-governance-core-max-wide npcink-governance-core-disclosure-top">
			<summary>
				<strong><?php echo esc_html__( 'Audit timeline', 'npcink-governance-core' ); ?></strong>
				<span class="npcink-governance-core-muted">
					<?php
					if ( $is_truncated ) {
						echo esc_html(
							sprintf(
								/* translators: 1: shown event count, 2: total event count. */
								__( 'Newest first. Showing the latest %1$d of %2$d recorded events.', 'npcink-governance-core' ),
								$shown,
								$total
							)
						);
					} else {
						echo esc_html__( 'Newest first. Complete event table with actor and technical attribution.', 'npcink-governance-core' );
					}
					?>
				</span>
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
	 * Renders the current proposal outcome before dense audit evidence.
	 *
	 * @param array<string,mixed>            $proposal Proposal row.
	 * @param array<int,array<string,mixed>> $events Audit events.
	 * @return void
	 */
	private function render_audit_outcome_summary( array $proposal, array $events ): void {
		$summary = $this->audit_outcome_summary( $proposal, $events );
		?>
		<section class="npcink-governance-core-audit-outcome npcink-governance-core-max-wide" aria-label="<?php echo esc_attr__( 'Current governance outcome', 'npcink-governance-core' ); ?>">
			<h3><?php echo esc_html__( 'Current governance outcome', 'npcink-governance-core' ); ?></h3>
			<div class="npcink-governance-core-audit-outcome-grid">
				<div>
					<div class="npcink-governance-core-summary-label"><?php echo esc_html__( 'Outcome', 'npcink-governance-core' ); ?></div>
					<strong><?php echo esc_html( $summary['outcome'] ); ?></strong>
				</div>
				<div>
					<div class="npcink-governance-core-summary-label"><?php echo esc_html__( 'Next step', 'npcink-governance-core' ); ?></div>
					<span><?php echo esc_html( $summary['next_step'] ); ?></span>
				</div>
				<div>
					<div class="npcink-governance-core-summary-label"><?php echo esc_html__( 'Evidence trail', 'npcink-governance-core' ); ?></div>
					<span><?php echo esc_html( $summary['evidence'] ); ?></span>
				</div>
			</div>
		</section>
		<?php
	}

	/**
	 * Returns a user-facing current audit outcome summary.
	 *
	 * @param array<string,mixed>            $proposal Proposal row.
	 * @param array<int,array<string,mixed>> $events Audit events.
	 * @return array{outcome:string,next_step:string,evidence:string}
	 */
	private function audit_outcome_summary( array $proposal, array $events ): array {
		$status          = (string) ( $proposal['status'] ?? '' );
		$has_approval    = $this->audit_events_include( $events, 'proposal.approved' );
		$has_rejection   = $this->audit_events_include( $events, 'proposal.rejected' );
		$has_preflight   = $this->audit_events_include( $events, 'commit.preflighted' );
		$has_executed    = $this->audit_events_include( $events, 'proposal.executed' );
		$has_failed      = $this->audit_events_include( $events, 'proposal.execution_failed' );
		$evidence_count  = count( $this->audit_lifecycle_steps( $events ) );
		$evidence_suffix = sprintf(
			/* translators: %d: audit lifecycle evidence count. */
			_n( '%d lifecycle event recorded.', '%d lifecycle events recorded.', $evidence_count, 'npcink-governance-core' ),
			absint( $evidence_count )
		);

		if ( Proposal_Repository::STATUS_PENDING === $status ) {
			return array(
				'outcome'   => __( 'Awaiting Core review.', 'npcink-governance-core' ),
				'next_step' => __( 'Review the basis, then approve or reject. Approval still requires commit preflight before Adapter or host execution.', 'npcink-governance-core' ),
				'evidence'  => $evidence_suffix,
			);
		}

		if ( Proposal_Repository::STATUS_APPROVED === $status ) {
			return array(
				'outcome'   => $has_preflight ? __( 'Approved and preflight handoff issued.', 'npcink-governance-core' ) : __( 'Approved; preflight handoff not yet issued.', 'npcink-governance-core' ),
				'next_step' => $has_preflight ? __( 'Adapter or host can use the bounded preflight context; Core still does not execute the write.', 'npcink-governance-core' ) : __( 'Run commit preflight before any external execution attempt.', 'npcink-governance-core' ),
				'evidence'  => $this->audit_outcome_evidence_label( $evidence_suffix, $has_approval, $has_preflight, $has_executed, $has_failed, $has_rejection ),
			);
		}

		if ( Proposal_Repository::STATUS_EXECUTED === $status ) {
			return array(
				'outcome'   => __( 'Execution result recorded outside Core.', 'npcink-governance-core' ),
				'next_step' => __( 'Verify the execution record and related Adapter evidence; no Core decision is pending.', 'npcink-governance-core' ),
				'evidence'  => $this->audit_outcome_evidence_label( $evidence_suffix, $has_approval, $has_preflight, $has_executed, $has_failed, $has_rejection ),
			);
		}

		if ( Proposal_Repository::STATUS_EXECUTION_FAILED === $status ) {
			return array(
				'outcome'   => __( 'External execution failed after handoff.', 'npcink-governance-core' ),
				'next_step' => __( 'Inspect Adapter execution evidence and submit a new proposal if another write attempt is needed.', 'npcink-governance-core' ),
				'evidence'  => $this->audit_outcome_evidence_label( $evidence_suffix, $has_approval, $has_preflight, $has_executed, $has_failed, $has_rejection ),
			);
		}

		if ( Proposal_Repository::STATUS_REJECTED === $status ) {
			return array(
				'outcome'   => __( 'Rejected by Core review.', 'npcink-governance-core' ),
				'next_step' => __( 'Do not continue to commit preflight or external execution for this proposal.', 'npcink-governance-core' ),
				'evidence'  => $this->audit_outcome_evidence_label( $evidence_suffix, $has_approval, $has_preflight, $has_executed, $has_failed, $has_rejection ),
			);
		}

		if ( Proposal_Repository::STATUS_EXPIRED === $status || Proposal_Repository::STATUS_ARCHIVED === $status ) {
			return array(
				'outcome'   => __( 'Historical governance record.', 'npcink-governance-core' ),
				'next_step' => __( 'Use this record for audit lookup only; no approval action is available here.', 'npcink-governance-core' ),
				'evidence'  => $evidence_suffix,
			);
		}

		return array(
			'outcome'   => __( 'Lifecycle state needs audit review.', 'npcink-governance-core' ),
			'next_step' => __( 'Read the lifecycle summary and full audit timeline before taking follow-up action.', 'npcink-governance-core' ),
			'evidence'  => $evidence_suffix,
		);
	}

	/**
	 * Returns whether audit events include a lifecycle event name.
	 *
	 * @param array<int,array<string,mixed>> $events Audit events.
	 * @param string                         $event_name Event name.
	 * @return bool
	 */
	private function audit_events_include( array $events, string $event_name ): bool {
		foreach ( $events as $event ) {
			if ( $event_name === (string) ( $event['event_name'] ?? '' ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Returns a compact evidence label from known lifecycle events.
	 *
	 * @param string $base Base evidence count label.
	 * @param bool   $has_approval Has approval evidence.
	 * @param bool   $has_preflight Has preflight evidence.
	 * @param bool   $has_executed Has execution evidence.
	 * @param bool   $has_failed Has execution failure evidence.
	 * @param bool   $has_rejection Has rejection evidence.
	 * @return string
	 */
	private function audit_outcome_evidence_label( string $base, bool $has_approval, bool $has_preflight, bool $has_executed, bool $has_failed, bool $has_rejection ): string {
		$parts = array();
		if ( $has_approval ) {
			$parts[] = __( 'approval', 'npcink-governance-core' );
		}
		if ( $has_preflight ) {
			$parts[] = __( 'preflight', 'npcink-governance-core' );
		}
		if ( $has_executed ) {
			$parts[] = __( 'execution result', 'npcink-governance-core' );
		}
		if ( $has_failed ) {
			$parts[] = __( 'execution failure', 'npcink-governance-core' );
		}
		if ( $has_rejection ) {
			$parts[] = __( 'rejection', 'npcink-governance-core' );
		}

		if ( empty( $parts ) ) {
			return $base;
		}

		return sprintf(
			/* translators: 1: base evidence count label, 2: comma-separated evidence labels. */
			__( '%1$s Includes %2$s evidence.', 'npcink-governance-core' ),
			$base,
			implode( ', ', $parts )
		);
	}

	/**
	 * Renders compact lifecycle evidence before the full audit table.
	 *
	 * @param array<int,array<string,mixed>> $events Audit events.
	 * @return void
	 */
	private function render_audit_lifecycle_summary( array $events ): void {
		// The evidence tab receives newest-first events; the ordered list is a
		// stage sequence, so it renders chronologically regardless.
		$steps = $this->audit_lifecycle_steps( array_reverse( $events ) );
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
	 * Renders the AI activity overview summary strip for the audit view.
	 *
	 * Counters group audit events by outcome within the current filter scope
	 * (excluding the event-type filter), so one screen answers what the AI
	 * requested, what was approved or rejected, and what executed or failed.
	 * Each counter links to that event's filtered activity list. Read-only;
	 * no approval or execution state is changed from this strip.
	 *
	 * @param array<string,mixed> $filters Audit filters.
	 * @return void
	 */
	private function render_activity_overview( array $filters ): void {
		$counts = $this->audit->count_grouped_by_event_name( $filters );

		$cards = array(
			array(
				'event'  => 'proposal.created',
				'label'  => __( 'Requests created', 'npcink-governance-core' ),
				'detail' => __( 'AI-initiated proposals submitted for governance.', 'npcink-governance-core' ),
				'tone'   => 'neutral',
			),
			array(
				'event'  => 'proposal.approved',
				'label'  => __( 'Approved', 'npcink-governance-core' ),
				'detail' => __( 'Administrator-approved proposals.', 'npcink-governance-core' ),
				'tone'   => 'ok',
			),
			array(
				'event'  => 'proposal.rejected',
				'label'  => __( 'Rejected', 'npcink-governance-core' ),
				'detail' => __( 'Proposals rejected before any write happened.', 'npcink-governance-core' ),
				'tone'   => 'warning',
			),
			array(
				'event'  => 'commit.preflighted',
				'label'  => __( 'Preflight checks', 'npcink-governance-core' ),
				'detail' => __( 'Commit preflights run before final execution.', 'npcink-governance-core' ),
				'tone'   => 'neutral',
			),
			array(
				'event'  => 'proposal.executed',
				'label'  => __( 'Executed', 'npcink-governance-core' ),
				'detail' => __( 'Approved writes completed through Adapter execution profiles.', 'npcink-governance-core' ),
				'tone'   => 'ok',
			),
			array(
				'event'  => 'proposal.execution_failed',
				'label'  => __( 'Execution failed', 'npcink-governance-core' ),
				'detail' => __( 'Failures that need operator follow-up.', 'npcink-governance-core' ),
				'tone'   => 'error',
			),
		);
		?>
		<div class="npcink-governance-core-summary-strip npcink-governance-core-max-wide npcink-governance-core-activity-overview">
			<?php foreach ( $cards as $card ) : ?>
				<?php
				$count = (int) ( $counts[ $card['event'] ] ?? 0 );
				$tone  = 'error' === $card['tone'] && 0 === $count ? 'inactive' : $card['tone'];
				$url   = $this->admin_url(
					array_merge(
						$this->audit_query_args( $filters ),
						array( 'audit_event_name' => $card['event'] )
					)
				);
				?>
				<a class="npcink-governance-core-summary-item npcink-governance-core-summary-<?php echo esc_attr( sanitize_html_class( $tone ) ); ?> npcink-governance-core-summary-link" href="<?php echo esc_url( $url ); ?>">
					<div class="npcink-governance-core-summary-label"><?php echo esc_html( $card['label'] ); ?></div>
					<div class="npcink-governance-core-summary-value"><?php echo esc_html( (string) $count ); ?></div>
					<div class="npcink-governance-core-summary-detail"><?php echo esc_html( $card['detail'] ); ?></div>
				</a>
			<?php endforeach; ?>
		</div>
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
			'proposal.plan_ingested'  => __( 'Plan ingested', 'npcink-governance-core' ),
			'proposal.executed'       => __( 'Request executed', 'npcink-governance-core' ),
			'proposal.execution_failed' => __( 'Execution failed', 'npcink-governance-core' ),
			'proposal.execution_provisional' => __( 'Provisional execution recorded', 'npcink-governance-core' ),
			'proposal.listed'         => __( 'Proposal listed', 'npcink-governance-core' ),
			'proposal.viewed'         => __( 'Proposal viewed', 'npcink-governance-core' ),
			'commit.preflighted'      => __( 'Commit preflight checked', 'npcink-governance-core' ),
			'commit.preflight_failed' => __( 'Commit preflight failed', 'npcink-governance-core' ),
			'read_request.created'    => __( 'Sensitive read requested', 'npcink-governance-core' ),
			'read_request.approved'   => __( 'Sensitive read approved', 'npcink-governance-core' ),
			'read_request.rejected'   => __( 'Sensitive read rejected', 'npcink-governance-core' ),
			'read_request.expired'    => __( 'Sensitive read expired', 'npcink-governance-core' ),
			'read_request.consumed'   => __( 'Sensitive read consumed', 'npcink-governance-core' ),
			'read_request.preflighted' => __( 'Read preflight checked', 'npcink-governance-core' ),
			'read_request.preflight_failed' => __( 'Read preflight failed', 'npcink-governance-core' ),
			'read_request.listed'     => __( 'Read request listed', 'npcink-governance-core' ),
			'read_request.viewed'     => __( 'Read request viewed', 'npcink-governance-core' ),
			'app.created'             => __( 'Client access created', 'npcink-governance-core' ),
			'app.revoked'             => __( 'Client access revoked', 'npcink-governance-core' ),
			'app.rotated'             => __( 'Client access rotated', 'npcink-governance-core' ),
			'app.listed'              => __( 'Client access listed', 'npcink-governance-core' ),
			'app.scope_denied'        => __( 'Client access denied', 'npcink-governance-core' ),
			'app.rate_limited'        => __( 'Client rate limited', 'npcink-governance-core' ),
			'local_admin_consent.requested'  => __( 'Local admin consent requested', 'npcink-governance-core' ),
			'local_admin_consent.completed' => __( 'Local admin consent completed', 'npcink-governance-core' ),
			'local_admin_consent.failed'     => __( 'Local admin consent failed', 'npcink-governance-core' ),
			'audit.listed'                    => __( 'Audit log listed', 'npcink-governance-core' ),
			'capabilities.listed'             => __( 'Capabilities listed', 'npcink-governance-core' ),
			'core.approval_policy_updated'    => __( 'Approval policy updated', 'npcink-governance-core' ),
			'core.history_cleanup_requested'  => __( 'History cleanup requested', 'npcink-governance-core' ),
			'core.history_cleanup_completed'  => __( 'History cleanup completed', 'npcink-governance-core' ),
			'core.history_cleanup_failed'     => __( 'History cleanup failed', 'npcink-governance-core' ),
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
			'proposal.policy_evaluated'   => __( 'Policy checked', 'npcink-governance-core' ),
			'proposal.auto_approved'      => __( 'Request auto-approved', 'npcink-governance-core' ),
			'proposal.approved'           => __( 'Request approved', 'npcink-governance-core' ),
			'proposal.rejected'           => __( 'Request rejected', 'npcink-governance-core' ),
			'proposal.expired'            => __( 'Request expired', 'npcink-governance-core' ),
			'proposal.archived'           => __( 'Request archived', 'npcink-governance-core' ),
			'proposal.reopened'           => __( 'Request reopened', 'npcink-governance-core' ),
			'proposal.deduplicated'       => __( 'Duplicate request reused', 'npcink-governance-core' ),
			'proposal.quota_blocked'      => __( 'Request quota blocked', 'npcink-governance-core' ),
			'proposal.plan_ingested'      => __( 'Plan ingested', 'npcink-governance-core' ),
			'commit.preflighted'          => __( 'Commit preflight checked', 'npcink-governance-core' ),
			'commit.preflight_failed'     => __( 'Commit preflight failed', 'npcink-governance-core' ),
			'proposal.executed'           => __( 'Request executed', 'npcink-governance-core' ),
			'proposal.execution_failed'   => __( 'Execution failed', 'npcink-governance-core' ),
			'proposal.execution_provisional' => __( 'Provisional execution recorded', 'npcink-governance-core' ),
			'proposal.viewed'             => __( 'Proposal viewed', 'npcink-governance-core' ),
			'proposal.listed'             => __( 'Proposal listed', 'npcink-governance-core' ),
			'read_request.created'        => __( 'Sensitive read requested', 'npcink-governance-core' ),
			'read_request.approved'       => __( 'Sensitive read approved', 'npcink-governance-core' ),
			'read_request.rejected'       => __( 'Sensitive read rejected', 'npcink-governance-core' ),
			'read_request.preflighted'    => __( 'Read preflight checked', 'npcink-governance-core' ),
			'read_request.preflight_failed' => __( 'Read preflight failed', 'npcink-governance-core' ),
			'read_request.consumed'       => __( 'Sensitive read consumed', 'npcink-governance-core' ),
			'read_request.expired'        => __( 'Sensitive read expired', 'npcink-governance-core' ),
			'read_request.listed'         => __( 'Read request listed', 'npcink-governance-core' ),
			'read_request.viewed'         => __( 'Read request viewed', 'npcink-governance-core' ),
			'app.created'                 => __( 'Client access created', 'npcink-governance-core' ),
			'app.revoked'                 => __( 'Client access revoked', 'npcink-governance-core' ),
			'app.rotated'                 => __( 'Client access rotated', 'npcink-governance-core' ),
			'app.listed'                  => __( 'Client access listed', 'npcink-governance-core' ),
			'app.scope_denied'            => __( 'Client access denied', 'npcink-governance-core' ),
			'app.rate_limited'            => __( 'Client rate limited', 'npcink-governance-core' ),
			'local_admin_consent.requested'  => __( 'Local admin consent requested', 'npcink-governance-core' ),
			'local_admin_consent.completed' => __( 'Local admin consent completed', 'npcink-governance-core' ),
			'local_admin_consent.failed'     => __( 'Local admin consent failed', 'npcink-governance-core' ),
			'audit.listed'                    => __( 'Audit log listed', 'npcink-governance-core' ),
			'capabilities.listed'             => __( 'Capabilities listed', 'npcink-governance-core' ),
			'core.approval_policy_updated'    => __( 'Approval policy updated', 'npcink-governance-core' ),
			'core.history_cleanup_requested'  => __( 'History cleanup requested', 'npcink-governance-core' ),
			'core.history_cleanup_completed'  => __( 'History cleanup completed', 'npcink-governance-core' ),
			'core.history_cleanup_failed'     => __( 'History cleanup failed', 'npcink-governance-core' ),
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
	 * Reuses the repository's canonical noise list so REST and admin defaults
	 * can never diverge.
	 *
	 * @return array<int,string>
	 */
	private function low_value_audit_events(): array {
		return $this->audit->read_noise_event_names();
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

}
