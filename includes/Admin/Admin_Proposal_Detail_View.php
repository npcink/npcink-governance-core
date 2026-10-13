<?php
/**
 * Existing Core admin view cluster.
 *
 * @package NpcinkGovernanceCore
 */

namespace Npcink\GovernanceCore\Admin;

use Npcink\GovernanceCore\Governance\Operation_Classifier;
use Npcink\GovernanceCore\Governance\Proposal_Repository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Private presentation methods composed by Admin_Page. */
trait Admin_Proposal_Detail_View {
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
				'proposal_id'         => $proposal_id,
				'limit'               => 50,
				'order'               => 'desc',
				'exclude_event_names' => $this->low_value_audit_events(),
			)
		);
		$timeline_total = $this->audit->count_filtered(
			array(
				'proposal_id'         => $proposal_id,
				'exclude_event_names' => $this->low_value_audit_events(),
			)
		);
		?>
		<p><a href="<?php echo esc_url( $this->queue_url( max( 1, $this->page_from_request( 'review_page' ) ) ) ); ?>">&larr; <?php echo esc_html__( 'Back to review queue', 'npcink-governance-core' ); ?></a></p>
		<h2><?php echo esc_html__( 'Proposal Detail', 'npcink-governance-core' ); ?></h2>
		<p class="npcink-governance-core-subtle npcink-governance-core-copy-width"><?php echo esc_html__( 'Review the governance record, action plan, and audit evidence for this proposal.', 'npcink-governance-core' ); ?></p>
		<?php $this->render_proposal_summary_panel( $proposal ); ?>
		<?php $this->render_proposal_detail_tabs( $proposal, $active_tab ); ?>
		<div class="npcink-governance-core-tab-panel npcink-governance-core-max-wide">
			<?php $this->render_proposal_detail_tab_panel( $active_tab, $proposal, $capability, $timeline, $timeline_total ); ?>
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
	 * @param int                            $timeline_total Total audit events for the proposal.
	 * @return void
	 */
	private function render_proposal_detail_tab_panel( string $active_tab, array $proposal, ?array $capability, array $timeline, int $timeline_total = 0 ): void {
		if ( 'actions' === $active_tab ) {
			$this->render_proposal_actions_tab( $proposal );
			return;
		}

		if ( 'evidence' === $active_tab ) {
			$this->render_audit_timeline( $proposal, $timeline, false, $timeline_total );
			return;
		}

		if ( 'technical' === $active_tab ) {
			$this->render_proposal_implementation_posture( $capability );
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
			$this->render_single_action_input_summary( $proposal );
			return;
		}

		$this->render_proposal_batch_actions( $proposal );
		$this->render_proposed_change_details( $proposal );
	}

	/**
	 * Renders a human-readable summary of a single-action proposal's
	 * submitted input when no structured action plan or preview detail was
	 * recorded, so the reviewer can see what the ability would receive
	 * without opening the raw payload JSON.
	 *
	 * @param array<string,mixed> $proposal Proposal.
	 * @return void
	 */
	private function render_single_action_input_summary( array $proposal ): void {
		$input = is_array( $proposal['input'] ?? null ) ? $proposal['input'] : array();
		if ( empty( $input ) ) {
			?>
			<p class="npcink-governance-core-subtle"><?php echo esc_html__( 'No structured action plan or proposed change detail was recorded for this proposal.', 'npcink-governance-core' ); ?></p>
			<?php
			return;
		}
		?>
		<details class="npcink-governance-core-disclosure npcink-governance-core-max-wide npcink-governance-core-disclosure-top" open>
			<summary>
				<strong><?php echo esc_html__( 'Submitted action input', 'npcink-governance-core' ); ?></strong>
				<span class="npcink-governance-core-muted"><?php echo esc_html__( 'The input this proposal would pass to the target ability. Review it before approving.', 'npcink-governance-core' ); ?></span>
			</summary>
			<table class="widefat striped npcink-governance-core-table-spaced">
				<tbody>
					<?php foreach ( $input as $key => $value ) : ?>
						<tr>
							<th scope="row"><code><?php echo esc_html( (string) $key ); ?></code></th>
							<td><?php $this->render_review_input_value( $value ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</details>
		<?php
	}

	/**
	 * Renders one submitted-input value with a bounded block for large
	 * payloads so long article content cannot flood the review table.
	 *
	 * @param mixed $value Input value.
	 * @return void
	 */
	private function render_review_input_value( $value ): void {
		if ( is_array( $value ) || ( is_string( $value ) && strlen( $value ) > 4000 ) ) {
			echo '<pre style="max-height: 220px; overflow: auto; margin: 0;">';
			echo esc_html( is_array( $value ) ? (string) wp_json_encode( $value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) : $value );
			echo '</pre>';
			return;
		}

		$this->render_review_inline_value( $value );
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
	 * Renders the implementation-posture table on the technical tab.
	 *
	 * The posture rows are raw provider contract identifiers, so they support
	 * debugging rather than the approve/reject decision and belong next to the
	 * technical identifiers instead of the overview.
	 *
	 * @param array<string,mixed>|null $capability Capability row.
	 * @return void
	 */
	private function render_proposal_implementation_posture( ?array $capability ): void {
		$group = $this->implementation_posture_review_group( $capability );
		if ( empty( $group ) ) {
			return;
		}
		?>
		<details class="npcink-governance-core-disclosure npcink-governance-core-max-wide npcink-governance-core-disclosure-top">
			<summary>
				<strong><?php echo esc_html__( 'Provider posture', 'npcink-governance-core' ); ?></strong>
				<span class="npcink-governance-core-muted"><?php echo esc_html__( 'Raw provider posture identifiers for troubleshooting.', 'npcink-governance-core' ); ?></span>
			</summary>
			<?php $this->render_proposal_detail_groups( array( $group ) ); ?>
		</details>
		<?php
	}

	/**
	 * Returns implementation posture rows for the proposal review surface.
	 *
	 * @param array<string,mixed>|null $capability Capability row.
	 * @return array{label:string,rows:array<int,array{label:string,value:mixed,code?:bool}>}|array{}
	 */
	private function implementation_posture_review_group( ?array $capability ): array {
		$posture = is_array( $capability['implementation_posture'] ?? null ) ? $capability['implementation_posture'] : array();
		if ( empty( $posture ) ) {
			return array();
		}

		return array(
			'label' => __( 'Implementation posture', 'npcink-governance-core' ),
			'rows'  => array(
				array(
					'label' => __( 'Schema', 'npcink-governance-core' ),
					'value' => (string) ( $posture['schema_version'] ?? '' ),
					'code'  => true,
				),
				array(
					'label' => __( 'Write posture', 'npcink-governance-core' ),
					'value' => (string) ( $posture['write_posture'] ?? '' ),
					'code'  => true,
				),
				array(
					'label' => __( 'Commit authority', 'npcink-governance-core' ),
					'value' => (string) ( $posture['commit_authority'] ?? '' ),
					'code'  => true,
				),
				array(
					'label' => __( 'Execution surface', 'npcink-governance-core' ),
					'value' => (string) ( $posture['execution_surface'] ?? '' ),
					'code'  => true,
				),
				array(
					'label' => __( 'Final authorization owner', 'npcink-governance-core' ),
					'value' => (string) ( $posture['final_authorization_owner'] ?? '' ),
					'code'  => true,
				),
				array(
					'label' => __( 'Approval truth owner', 'npcink-governance-core' ),
					'value' => (string) ( $posture['approval_truth_owner'] ?? '' ),
					'code'  => true,
				),
				array(
					'label' => __( 'Audit truth owner', 'npcink-governance-core' ),
					'value' => (string) ( $posture['audit_truth_owner'] ?? '' ),
					'code'  => true,
				),
				array(
					'label' => __( 'Dry-run default', 'npcink-governance-core' ),
					'value' => $this->posture_bool_label( $posture, 'dry_run_default' ),
				),
				array(
					'label' => __( 'Commit default', 'npcink-governance-core' ),
					'value' => $this->posture_bool_label( $posture, 'commit_default' ),
				),
				array(
					'label' => __( 'Required host evidence', 'npcink-governance-core' ),
					'value' => implode( ', ', array_map( 'strval', (array) ( $posture['required_host_evidence'] ?? array() ) ) ),
				),
				array(
					'label' => __( 'Verification contract', 'npcink-governance-core' ),
					'value' => implode( ', ', array_map( 'strval', (array) ( $posture['verification_contract'] ?? array() ) ) ),
				),
				array(
					'label' => __( 'Reference patterns', 'npcink-governance-core' ),
					'value' => implode( ', ', array_map( 'strval', (array) ( $posture['reference_patterns'] ?? array() ) ) ),
				),
				array(
					'label' => __( 'Boundary flags', 'npcink-governance-core' ),
					'value' => $this->implementation_posture_boundary_label( $posture ),
				),
			),
		);
	}

	/**
	 * Returns a compact yes/no label for optional posture booleans.
	 *
	 * @param array<string,mixed> $posture Posture metadata.
	 * @param string              $field Boolean field.
	 * @return string
	 */
	private function posture_bool_label( array $posture, string $field ): string {
		if ( ! array_key_exists( $field, $posture ) ) {
			return '';
		}

		return ! empty( $posture[ $field ] ) ? __( 'yes', 'npcink-governance-core' ) : __( 'no', 'npcink-governance-core' );
	}

	/**
	 * Returns posture boundary status without making Core the provider owner.
	 *
	 * @param array<string,mixed> $posture Posture metadata.
	 * @return string
	 */
	private function implementation_posture_boundary_label( array $posture ): string {
		$enabled = array();
		foreach (
			array(
				'workflow_' . 'runtime' => __( 'workflow runtime', 'npcink-governance-core' ),
				'queue_or_scheduler'    => __( 'queue or scheduler', 'npcink-governance-core' ),
				'model_' . 'routing'    => __( 'model routing', 'npcink-governance-core' ),
				'provider_' . 'credentials' => __( 'provider credentials', 'npcink-governance-core' ),
				'approval_storage'      => __( 'approval storage', 'npcink-governance-core' ),
				'audit_storage'         => __( 'audit storage', 'npcink-governance-core' ),
			) as $field => $label
		) {
			if ( ! empty( $posture[ $field ] ) ) {
				$enabled[] = $label;
			}
		}

		if ( empty( $enabled ) ) {
			return __( 'No runtime, queue, model routing, credential, approval-store, or audit-store ownership declared.', 'npcink-governance-core' );
		}

		return sprintf(
			/* translators: %s: comma-separated provider-declared ownership flags. */
			__( 'Provider declares Core-forbidden ownership: %s', 'npcink-governance-core' ),
			implode( ', ', $enabled )
		);
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
						'value' => $this->caller_type_label( (string) ( $auth['caller_type'] ?? $caller['caller_type'] ?? '' ) ),
						'code'  => true,
					),
					array(
						'label' => __( 'App ID', 'npcink-governance-core' ),
						'value' => (string) ( $auth['app_id'] ?? $caller['app_id'] ?? '' ),
						'code'  => true,
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
			<input type="hidden" name="review_page" value="<?php echo esc_attr( (string) max( 1, $this->page_from_request( 'review_page' ) ) ); ?>" />
			<?php foreach ( $this->review_filter_query_args() as $filter_key => $filter_value ) : ?>
				<input type="hidden" name="<?php echo esc_attr( $filter_key ); ?>" value="<?php echo esc_attr( $filter_value ); ?>" />
			<?php endforeach; ?>
			<?php wp_nonce_field( 'npcink_governance_core_decide_proposal_' . $proposal_id ); ?>
			<label for="npcink-governance-core-note-<?php echo esc_attr( $proposal_id ); ?>"><?php echo esc_html__( 'Decision note (optional)', 'npcink-governance-core' ); ?></label>
			<textarea id="npcink-governance-core-note-<?php echo esc_attr( $proposal_id ); ?>" name="note" rows="2" class="large-text"></textarea>
			<label class="npcink-governance-core-open-next">
				<input type="checkbox" name="open_next" value="1" checked />
				<?php echo esc_html__( 'After this decision, open the next pending proposal', 'npcink-governance-core' ); ?>
			</label>
			<button type="submit" class="button button-primary" name="action" value="npcink_governance_core_approve_proposal">
				<?php echo esc_html__( 'Approve', 'npcink-governance-core' ); ?>
			</button>
			<details class="npcink-governance-core-reject-disclosure">
				<summary class="button"><?php echo esc_html__( 'Reject', 'npcink-governance-core' ); ?></summary>
				<div class="npcink-governance-core-reject-panel">
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
						'value' => $this->policy_decision_label( (string) ( $proposal['policy_decision'] ?? '' ) ),
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
						'value' => $this->review_basis_label( 'request_source', (string) $posture['request_source'] ),
						'code'  => true,
					),
					array(
						'label' => __( 'Actor presence', 'npcink-governance-core' ),
						'value' => $this->review_basis_label( 'actor_presence', (string) $posture['actor_presence'] ),
						'code'  => true,
					),
					array(
						'label' => __( 'Preview completeness', 'npcink-governance-core' ),
						'value' => $this->review_basis_label( 'preview_completeness', (string) $posture['preview_completeness'] ),
						'code'  => true,
					),
					array(
						'label' => __( 'Scope', 'npcink-governance-core' ),
						'value' => $this->review_basis_label( 'scope', (string) $posture['scope'] ),
						'code'  => true,
					),
					array(
						'label' => __( 'Operation kind', 'npcink-governance-core' ),
						'value' => $this->review_basis_label( 'operation_kind', (string) $posture['operation_kind'] ),
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

}
