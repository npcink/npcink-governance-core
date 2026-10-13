<?php
/** Audit and admin test module. @package NpcinkGovernanceCore */

$audit_repository = npcink_governance_core_read( $root . '/includes/Audit/Audit_Log_Repository.php' );
npcink_governance_core_assert( false !== strpos( $audit_repository, 'sanitize_text_field( $event_name )' ), 'Audit repository preserves dotted event names.' );
npcink_governance_core_assert( false !== strpos( $audit_repository, 'count_grouped_by_event_name' ) && false !== strpos( $audit_repository, 'GROUP BY event_name' ), 'Audit repository can group event counts for the activity overview.' );
npcink_governance_core_assert( false !== strpos( $audit_repository, 'list_filtered' ), 'Audit repository supports filtered event lists.' );
npcink_governance_core_assert( false !== strpos( $audit_repository, 'proposal_id = %s' ), 'Audit repository filters by proposal id safely.' );
npcink_governance_core_assert( false !== strpos( $audit_repository, 'event_name = %s' ), 'Audit repository filters by event name safely.' );
npcink_governance_core_assert( false !== strpos( $audit_repository, 'proposal_id LIKE %s OR event_name LIKE %s OR ability_id LIKE %s' ), 'Audit repository supports broad search across indexed audit columns.' );
npcink_governance_core_assert( false !== strpos( $audit_repository, 'created_at >= %s' ), 'Audit repository supports bounded activity time ranges.' );
npcink_governance_core_assert( false !== strpos( $audit_repository, 'ability_id' ), 'Audit repository filters by ability id metadata.' );
npcink_governance_core_assert( false !== strpos( $audit_repository, 'app_id' ), 'Audit repository filters by app id metadata.' );
npcink_governance_core_assert( false !== strpos( $audit_repository, 'key_id' ), 'Audit repository filters by key id metadata.' );
npcink_governance_core_assert( false !== strpos( $audit_repository, 'caller_type' ), 'Audit repository filters by caller type metadata.' );
npcink_governance_core_assert( false !== strpos( $audit_repository, 'correlation_id' ), 'Audit repository filters by correlation id metadata.' );
npcink_governance_core_assert( false !== strpos( $audit_repository, 'ability_id varchar(190)' ), 'Audit repository stores indexed ability id column.' );
npcink_governance_core_assert( false !== strpos( $audit_repository, 'KEY correlation_id' ), 'Audit repository stores indexed correlation id column.' );
npcink_governance_core_assert( false !== strpos( $audit_repository, 'indexed_metadata' ), 'Audit repository extracts indexed metadata fields.' );
npcink_governance_core_assert( false !== strpos( $audit_repository, 'Sensitive_Data_Redactor::sanitize_payload' ), 'Audit repository uses shared sensitive data redaction.' );
npcink_governance_core_assert( false === strpos( $audit_repository, 'metadata_json LIKE' ), 'Audit repository does not scan metadata_json for common filters.' );
npcink_governance_core_assert( false !== strpos( $audit_repository, 'exclude_event_names' ), 'Audit repository can exclude noisy read events.' );
npcink_governance_core_assert( false !== strpos( $audit_repository, 'count_filtered' ), 'Audit repository can count filtered rows for pagination.' );
npcink_governance_core_assert( false !== strpos( $audit_repository, 'offset' ), 'Audit repository supports paginated admin lists.' );
npcink_governance_core_assert( false !== strpos( $audit_repository, 'delete_access_events_before' ), 'Audit repository can clean old low-value access events.' );
npcink_governance_core_assert( false !== strpos( $audit_repository, 'count_access_events_before' ), 'Audit repository can count old low-value access events.' );
npcink_governance_core_assert( false !== strpos( $audit_repository, 'retention_cleanup_event_names' ), 'Audit repository scopes audit retention cleanup to explicit event names.' );
npcink_governance_core_assert( false !== strpos( $audit_repository, '$wpdb->esc_like( $search ) . \'%\'' ), 'Audit repository uses prefix search for indexed activity search.' );

$audit_controller = npcink_governance_core_read( $root . '/includes/Rest/Audit_Controller.php' );
npcink_governance_core_assert( false !== strpos( $audit_controller, "'/audit'" ), 'Audit REST route is registered.' );
npcink_governance_core_assert( false !== strpos( $audit_controller, 'ability_id' ), 'Audit REST route accepts ability id filter.' );
npcink_governance_core_assert( false !== strpos( $audit_controller, 'app_id' ), 'Audit REST route accepts app id filter.' );
npcink_governance_core_assert( false !== strpos( $audit_controller, 'key_id' ), 'Audit REST route accepts key id filter.' );
npcink_governance_core_assert( false !== strpos( $audit_controller, 'caller_type' ), 'Audit REST route accepts caller type filter.' );
npcink_governance_core_assert( false !== strpos( $audit_controller, 'correlation_id' ), 'Audit REST route accepts correlation id filter.' );

$request_context = npcink_governance_core_read( $root . '/includes/Security/Request_Context.php' );
npcink_governance_core_assert( false !== strpos( $request_context, 'scope_decision' ), 'Request context stores scope decision.' );
npcink_governance_core_assert( false !== strpos( $request_context, 'mark_scope_decision' ), 'Request context can update scope decision for denials.' );
npcink_governance_core_assert( false !== strpos( $request_context, "'scopes'" ), 'Request context stores app scopes for smart guarded auto approval.' );
npcink_governance_core_assert( false !== strpos( $request_context, 'in_array( $scope' ), 'Request context can check any app scope, not only the current route scope.' );
npcink_governance_core_assert( false !== strpos( $request_context, 'signed_client_context' ), 'Request context can expose signed Adapter client bindings.' );
npcink_governance_core_assert( false !== strpos( $request_context, 'signed_client_fingerprint' ), 'Request context stores the signed Adapter client fingerprint.' );
npcink_governance_core_assert( false !== strpos( $request_context, 'client_key_fingerprint' ), 'Request context stores the compatible client key fingerprint alias.' );

$observability = npcink_governance_core_read( $root . '/includes/Observability.php' );
foreach ( array( 'Observability', 'npcink_governance_core_observability_event', 'schema_version', 'plugin_slug', 'source', 'local', 'event_kind', 'event_id', 'sanitize_payload', 'proposal_count', 'blocked_count' ) as $required ) {
	npcink_governance_core_assert( false !== strpos( $observability, $required ), 'Observability bridge contains required text: ' . $required );
}

$core_operability = npcink_governance_core_read( $root . '/docs/core-governance-operability.md' );
foreach ( array( 'core.proposal.create', 'core.proposal.plan_ingest', 'core.proposal.approve', 'core.proposal.reject', 'core.commit.preflight', 'approval notes', 'policy payloads' ) as $required ) {
	npcink_governance_core_assert( false !== strpos( $core_operability, $required ), 'Core operability documents observability contract: ' . $required );
}

$admin_page = npcink_governance_core_admin_sources( $root );
$history_cleanup_service = npcink_governance_core_read( $root . '/includes/Governance/History_Cleanup_Service.php' );
$plugin_bootstrap = npcink_governance_core_read( $root . '/includes/Plugin.php' );
$admin_css = npcink_governance_core_read( $root . '/assets/admin.css' );
$admin_js = npcink_governance_core_read( $root . '/assets/admin.js' );
$admin_surface_standard = npcink_governance_core_read( $root . '/docs/admin-surface-standard.md' );
$admin_identity_tab_visual_closeout = npcink_governance_core_read( $root . '/docs/history/admin-identity-tab-visual-closeout-2026-07-02.md' );
foreach (
	array(
		'local governance workbench',
		'fixed product name `Npcink Governance Core`',
		'Review Queue',
		'compact status summary',
		'pending request list',
		'10 proposals per page',
		'WordPress-style table navigation row',
		'first/previous/next/last page buttons',
		'compact source summary',
		'source attribution should default to the actor only',
		'stable display id',
		'dedicated `Details` column',
		'inline full-width key-value details table',
		'two-column grouped inspector',
		'Source should show the raw source value only',
		'undeclared-risk badge',
		'read-only lookup',
		'technical details',
		'Activity Log',
		'AI activity overview',
		'Adapter bridge state and provider logs stay out of the overview',
		'History',
		'read-only',
		'row selection, bulk actions, archive actions, or reopen actions',
		'Settings',
		'Approval Policy',
		'Do not wrap this primary setting in a disclosure',
		'History retention',
		'90 days, 180 days',
		'one bounded daily cleanup pass',
		'manual cleanup action',
		'cleanup is audited',
		'Client access tokens',
		'Manage access tokens',
		'paginated',
		'useful empty state',
		'proposal summary panel',
		'action count',
		'warning/blocker counts',
		'collapsed technical identity inspector',
		'non-pending outcome notice',
		'batch action table',
		'Ability and policy',
		'Preview signals',
		'audit evidence before raw payload',
		'bounded code blocks',
		'visual status badges',
		'reviewer sees the basis',
		'Shared Tab Visual Standard',
		'npcink-ai-tabs',
		'npcink-ai-tab',
		'npcink-ai-tab-active',
		'aria-current="page"',
		'3px to 4px `#3858e9` bottom border',
		'Do not globally restyle WordPress core `.nav-tab`',
		'no gray rectangular inactive tabs',
		'Migration order should be Core, Abilities Toolkit, Cloud Addon, Workflow',
		'Toolbox, then Adapter when Adapter adds or exposes product tabs',
		'Shared Switch Control Standard',
		'npcink-ai-switch',
		'npcink-ai-switch__input',
		'npcink-ai-switch__track',
		'npcink-ai-switch__thumb',
		'visual contract, not a runtime',
		'must enqueue its',
		'own admin stylesheet',
		'without Core active',
		'Use switches only for independent enable/disable settings',
		'Prefer normal checkboxes for bulk selection',
		'Auto-save should be reserved for',
		'single setting switches',
		'Do not globally restyle WordPress core checkbox selectors',
		'low-frequency fallback action',
		'directly visible Settings section',
		'operator-facing token',
		'Access tokens',
		'Revoked tokens',
		'Issue token',
		'active tokens only',
		'read-only audit lookup',
		'purpose',
		'raw scope checkboxes',
		'rate-limit fields',
		'default token table',
		'Settings section',
		'keeping these controls out of the default review queue',
		'directly visible',
		'compact contextual action bar',
		'collapsed disclosure fallback for no-JavaScript',
		'general policy rules UI',
		'OpenClaw onboarding',
		'ability definitions',
		'cloud connection settings',
		'Time Display',
		'WordPress site timezone',
		'Y-m-d H:i:s',
		'Do not print raw UTC strings',
		'must not append a nonce to the URL',
	) as $required
) {
	npcink_governance_core_assert( false !== strpos( $admin_surface_standard, $required ), 'Admin surface standard documents Core page boundary: ' . $required );
}
npcink_governance_core_assert(
	false !== strpos( $readme, 'Admin Identity And Tab Visual Closeout - 2026-07-02' )
	&& false !== strpos( $admin_identity_tab_visual_closeout, 'Npcink Governance Core' )
	&& false !== strpos( $admin_identity_tab_visual_closeout, 'Npcink Abilities Toolkit' )
	&& false !== strpos( $admin_identity_tab_visual_closeout, 'Npcink AI Client Adapter' )
	&& false !== strpos( $admin_identity_tab_visual_closeout, 'Npcink Cloud Addon' )
	&& false !== strpos( $admin_identity_tab_visual_closeout, 'AI 能力集' )
	&& false !== strpos( $admin_identity_tab_visual_closeout, '#3858e9' )
	&& false !== strpos( $admin_identity_tab_visual_closeout, 'margin: 16px 0' )
	&& false !== strpos( $admin_identity_tab_visual_closeout, 'does not' ),
	'Admin identity and tab visual closeout records the cross-repository naming, tab color, spacing, and boundary decisions.'
);
npcink_governance_core_assert( false !== strpos( $admin_page, 'admin_post_npcink_governance_core_approve_proposal' ), 'Admin page registers approve handler.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'admin_post_npcink_governance_core_reject_proposal' ), 'Admin page registers reject handler.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'admin_post_npcink_governance_core_bulk_reject_proposals' ), 'Admin page registers bulk reject handler.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'admin_post_npcink_governance_core_archive_proposal' ), 'Admin page registers archive handler.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'admin_post_npcink_governance_core_reopen_proposal' ), 'Admin page registers reopen handler.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'admin_post_npcink_governance_core_update_approval_policy' ), 'Admin page registers approval policy mode handler.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'admin_post_npcink_governance_core_create_app_key' ), 'Admin page registers app-key creation handler.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'admin_post_npcink_governance_core_revoke_app_key' ), 'Admin page registers app-key revocation handler.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'check_admin_referer' ), 'Admin proposal actions enforce nonce.' );
npcink_governance_core_assert( false === strpos( $admin_page, 'ADMIN_REQUEST_NONCE' ), 'Admin read-only GET navigation does not add nonce parameters to release URLs.' );
npcink_governance_core_assert( false === strpos( $admin_page, 'has_valid_admin_request_nonce' ), 'Admin GET filters rely on capability checks and sanitization instead of URL nonce gating.' );
npcink_governance_core_assert( false !== strpos( $admin_page, "current_user_can( 'manage_options' )" ), 'Admin proposal actions enforce capability.' );
npcink_governance_core_assert( false !== strpos( $admin_page, "DATETIME_DISPLAY_FORMAT = 'Y-m-d H:i:s'" ), 'Admin page standardizes visible datetime format.' );
npcink_governance_core_assert( false !== strpos( $admin_page, "esc_html( 'Npcink Governance Core' )" ), 'Admin page title uses the fixed product name.' );
npcink_governance_core_assert( false === strpos( $admin_page, "<h1><?php echo esc_html__( 'npcink-governance-core'" ), 'Admin page title does not expose the plugin slug as the primary heading.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Review, approve, and audit AI-initiated WordPress operations.' ), 'Admin page intro describes the governance job.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'display_datetime' ), 'Admin page centralizes visible datetime formatting.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'wp_date( self::DATETIME_DISPLAY_FORMAT, $timestamp )' ), 'Admin page formats stored UTC timestamps with the WordPress timezone.' );
npcink_governance_core_assert( false !== strpos( $admin_page, "\$this->display_datetime( (string) \$proposal['created_at'] )" ), 'Admin review queue formats proposal creation time through WordPress time.' );
npcink_governance_core_assert( false !== strpos( $admin_page, "\$this->display_datetime( (string) \$event['created_at'] )" ), 'Admin audit views format event time through WordPress time.' );
npcink_governance_core_assert( false === strpos( $admin_page, "echo esc_html( (string) \$event['created_at']" ), 'Admin page does not output raw UTC audit timestamps.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Reject selected' ), 'Admin review queue exposes bulk rejection.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Bulk actions' ), 'Admin review queue folds bulk rejection behind a low-frequency disclosure.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'data-npcink-bulk-disclosure' ), 'Admin review queue keeps bulk rejection as a no-JavaScript disclosure fallback.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'data-npcink-bulk-action-bar' ), 'Admin review queue renders a contextual bulk action bar.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Selected requests: %d' ), 'Admin review queue shows selected proposal counts in bulk actions.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Clear selection' ), 'Admin review queue lets operators clear bulk selection.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Add rejection note' ), 'Admin review queue keeps bulk rejection notes optional until needed.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'array_slice( $proposal_ids, 0, 50 )' ), 'Admin bulk rejection is bounded.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'render_proposal_lookup' ), 'Admin review queue exposes proposal lookup.' );
npcink_governance_core_assert( false !== strpos( $admin_page, "plugins_url( 'assets/admin.js'" ), 'Admin page enqueues the Core admin interaction script.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'npcink-governance-core-proposal-lookup' ), 'Admin proposal lookup uses a stable field id.' );
npcink_governance_core_assert( false !== strpos( $admin_page, "method=\"get\" action=\"<?php echo esc_url( \$this->admin_base_url() ); ?>\"" ), 'Admin proposal lookup is read-only GET navigation through the active Toolbox or standalone parent.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Find proposal' ), 'Admin proposal lookup opens an existing proposal detail by id.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'render_queue_summary' ), 'Admin default page renders a compact queue status summary.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'npcink-governance-core-summary-strip' ), 'Admin default page uses a compact status summary strip.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'render_workbench_toolbar' ), 'Admin default page groups lookup and recent activity as utilities.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'npcink-governance-core-workbench-toolbar' ), 'Admin default page separates utility lookup from the main queue.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Pending requests' ), 'Admin review queue uses user-facing pending request copy.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'npcink-governance-core-review-table' ), 'Admin review queue uses the compact review table.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Source' ) && false !== strpos( $admin_page, 'Status' ) && false !== strpos( $admin_page, 'Created' ) && false !== strpos( $admin_page, 'Details' ) && false !== strpos( $admin_page, 'Action' ), 'Admin review queue uses compact source, status, created, details, and action columns.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'proposal_risk_label' ), 'Admin review queue summarizes proposal risk in the default row.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'proposal_has_declared_risk' ), 'Admin review queue hides undeclared-risk noise from default rows.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'proposal_source_summary' ), 'Admin review queue summarizes proposal source in the default row.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'proposal_source_summary_parts' ), 'Admin review queue separates source actor and context for scanability.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'npcink-governance-core-source-cell' ), 'Admin review queue moves source attribution into a dedicated column.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'npcink-governance-core-source-summary' ), 'Admin review queue renders compact source attribution separately from request identity.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'npcink-governance-core-source-actor' ), 'Admin review queue shows the source actor before source context.' );
npcink_governance_core_assert( false === strpos( $admin_page, 'Display ID:' ), 'Admin review queue does not repeat the display id label in every default row.' );
npcink_governance_core_assert( false === strpos( $admin_page, 'npcink-governance-core-source-context' ), 'Admin review queue keeps app/source context out of default source rows.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'proposal_display_id( $proposal )' ), 'Admin review queue shows stable display ids in the default row.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'npcink-governance-core-detail-cell' ), 'Admin review queue moves technical detail access into a dedicated column.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'npcink-governance-core-row-details-toggle' ), 'Admin review queue toggles row details explicitly.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'npcink-governance-core-row-details-row' ), 'Admin review queue renders inline full-width detail rows.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'npcink-governance-core-row-details-table' ), 'Admin review queue renders technical details as a key-value table.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'npcink-governance-core-row-details-group' ), 'Admin review queue groups row technical details.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Identity and source' ) && false !== strpos( $admin_page, 'Time and policy' ), 'Admin review queue labels row technical detail groups.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'pending_proposal_source_value' ), 'Admin review queue shows raw source separately from caller and app fields.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'pending_proposal_technical_detail_rows' ), 'Admin review queue centralizes row technical detail fields.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'find_proposal_for_lookup' ), 'Admin proposal lookup accepts display ids as well as full proposal ids.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'find_by_display_id( $lookup_id )' ), 'Admin proposal lookup resolves display ids through the repository.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'P-1234ABCD-EF90' ), 'Admin proposal lookup teaches the display id format.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'proposal_due_label( $proposal )' ), 'Admin review queue shows compact remaining time in the default row.' );
npcink_governance_core_assert( false !== strpos( $admin_page, "__( '%s left', 'npcink-governance-core' )" ), 'Admin review queue avoids spelling the fixed TTL as repeated dominant text.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Review' ), 'Admin review queue uses a compact review row action.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Details' ), 'Admin review queue folds machine identifiers behind an explicit details entry.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Proposal ID' ), 'Admin technical details keep proposal ids visible.' );
npcink_governance_core_assert( false !== strpos( $admin_page, "(string) \$proposal['proposal_id']" ), 'Admin technical details keep the full proposal id available.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Describe why these requests should be rejected.' ), 'Admin bulk rejection asks for a user-entered reason without prefilled technical copy.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Rejected from bulk review.' ), 'Admin bulk rejection fallback note uses neutral governance copy.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Target ability' ), 'Admin review queue keeps ability ids available in technical details.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'proposal_request_label' ), 'Admin review queue maps ability ids to user-facing request labels.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'pending_proposal_trace_parts' ), 'Admin review queue summarizes source trace metadata.' );
npcink_governance_core_assert( false === strpos( $admin_page, 'Review this WordPress change before it can run.' ), 'Admin review queue avoids repeating generic instructions on every row.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'No requests need review.' ), 'Admin review queue empty state gives a clear no-work status.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Open activity log' ), 'Admin review queue empty state links to audit context.' );
npcink_governance_core_assert( false === strpos( $admin_page, "name=\"note\" value=\"<?php echo esc_attr__( 'Superseded by batch cleanup proposal.'" ), 'Admin review queue does not prefill bulk rejection with technical cleanup copy.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'plan_ability_id' ), 'Admin review queue can show plan-to-proposal source metadata.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'render_system_settings_page' ), 'Admin page renders low-frequency settings in a dedicated settings tab.' );
npcink_governance_core_assert( false !== strpos( $admin_page, "'settings' => array" ), 'Admin page exposes Settings as a first-level Core tab.' );
npcink_governance_core_assert( false !== strpos( $admin_page, "'settings' === \$view" ), 'Admin page routes the Settings tab independently from the review queue.' );
npcink_governance_core_assert( false !== strpos( $admin_page, "\$this->render_admin_tabs( 'settings' );" ), 'Admin page highlights Settings for settings and advanced access views.' );
npcink_governance_core_assert( false === strpos( $admin_page, '$this->render_system_settings_entry();' ), 'Admin review queue no longer renders system settings at the bottom.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Approval policy, history retention, and trusted governance client access.' ), 'Admin Settings tab explains its narrow configuration scope.' );
npcink_governance_core_assert( false !== strpos( $admin_page, '<section class="npcink-governance-core-settings-section npcink-governance-core-max-wide"' ), 'Admin Settings tab renders primary settings directly instead of behind a disclosure.' );
npcink_governance_core_assert( false === strpos( $admin_page, 'render_approval_policy_entry( true )' ), 'Admin Settings tab does not use a disclosure-open flag for the primary approval policy setting.' );
npcink_governance_core_assert( false !== strpos( $history_cleanup_service, 'OPTION_HISTORY_RETENTION_DAYS' ), 'History cleanup service defines a bounded history retention option.' );
npcink_governance_core_assert( false !== strpos( $history_cleanup_service, 'DEFAULT_HISTORY_RETENTION_DAYS = 90' ), 'History cleanup service defaults history retention to ninety days.' );
npcink_governance_core_assert( false !== strpos( $history_cleanup_service, 'HISTORY_RETENTION_DISABLED_DAYS = 0' ), 'History cleanup service supports disabling automatic deletion.' );
npcink_governance_core_assert( false !== strpos( $history_cleanup_service, 'CLEANUP_BATCH_LIMIT = 200' ), 'History cleanup service bounds each cleanup pass.' );
npcink_governance_core_assert( false !== strpos( $history_cleanup_service, 'core.history_cleanup_requested' ), 'History cleanup service audits requested cleanup before deletion.' );
npcink_governance_core_assert( false !== strpos( $history_cleanup_service, 'core.history_cleanup_completed' ), 'History cleanup service audits completed cleanup counts.' );
npcink_governance_core_assert( false !== strpos( $history_cleanup_service, 'delete_historical_before' ), 'History cleanup service deletes old historical proposal rows.' );
npcink_governance_core_assert( false !== strpos( $history_cleanup_service, 'delete_revoked_before' ), 'History cleanup service deletes old revoked token rows.' );
npcink_governance_core_assert( false !== strpos( $history_cleanup_service, 'delete_access_events_before' ), 'History cleanup service deletes old low-value audit access events.' );
npcink_governance_core_assert( false !== strpos( $history_cleanup_service, 'delete_historical_before' ), 'History cleanup service deletes terminal sensitive read requests.' );
npcink_governance_core_assert( false !== strpos( $history_cleanup_service, 'delete_expired_before' ), 'History cleanup service deletes expired rate-limit windows.' );
npcink_governance_core_assert( false !== strpos( $history_cleanup_service, 'deleted_audit_events' ), 'History cleanup service reports deleted audit event counts.' );
npcink_governance_core_assert( false !== strpos( $history_cleanup_service, 'commit_execution' ) && false !== strpos( $history_cleanup_service, 'core_execution' ), 'History cleanup audit explicitly stays outside final execution.' );
npcink_governance_core_assert( false !== strpos( $plugin_bootstrap, 'HISTORY_CLEANUP_HOOK' ), 'Plugin registers a named bounded history cleanup hook.' );
npcink_governance_core_assert( false !== strpos( $plugin_bootstrap, 'wp_schedule_event' ), 'Plugin schedules the daily bounded history cleanup pass.' );
npcink_governance_core_assert( false !== strpos( $plugin_bootstrap, 'wp_clear_scheduled_hook' ), 'Plugin clears the history cleanup hook on deactivation.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'admin_post_npcink_governance_core_run_history_cleanup' ), 'Admin Settings tab exposes a manual history cleanup action.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Run cleanup now' ), 'Admin Settings tab labels the manual history cleanup action.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'history_retention_day_options' ), 'Admin Settings tab renders bounded history retention choices.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'sanitize_history_retention_days' ), 'Admin Settings tab sanitizes history retention choices.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'history_retention_days' ), 'Admin Settings tab persists the selected history retention choice.' );
npcink_governance_core_assert( false !== strpos( $admin_page, "'cleanup_scheduled'      => true" ), 'Admin Settings tab records that scheduled cleanup is active.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'render_client_access_token_entry' ), 'Admin Settings tab renders client access token management directly.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Manage access tokens for trusted Adapter or internal governance clients.' ), 'Admin Settings tab explains client access token scope directly.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Manage access tokens' ), 'Admin Settings tab labels token management as an action.' );
npcink_governance_core_assert( false === strpos( $admin_page, 'Advanced Access' ), 'Admin Settings tab no longer hides token management behind Advanced Access copy.' );
npcink_governance_core_assert( false !== strpos( $admin_page, "__( 'Approval Policy', 'npcink-governance-core' )" ), 'Admin page exposes the approval policy section without the misleading development-only label.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'MODE_SMART_GUARDED' ), 'Admin page exposes smart approval mode.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'MODE_DEV_ALLOW_ALL' ), 'Admin page exposes development allow-all approval mode.' );
npcink_governance_core_assert( 1 === preg_match_all( '/\$this->implementation_posture_review_group\( \$capability \);/', $admin_page ) && false !== strpos( $admin_page, 'render_proposal_implementation_posture( $capability );' ) && false !== strpos( $admin_page, 'Provider posture' ) && false !== strpos( $admin_page, 'Raw provider posture identifiers for troubleshooting.' ), 'Admin implementation posture renders once, on the technical tab, behind a distinct disclosure summary.' );
npcink_governance_core_assert( false === strpos( $admin_page, 'Uses advanced permission checkboxes.' ) && false !== strpos( $admin_page, 'if ( ! empty( $scopes ) ) :' ), 'Admin custom token preset renders one description sentence, not a duplicated fallback line.' );
npcink_governance_core_assert( false !== strpos( $app_authenticator, 'Administrator authentication or a scoped app key is required.' ), 'REST 401 wording names both accepted authentication paths.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'stored_policy_mode' ), 'Admin page checks the stored approval policy mode before fallback.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'is_allowed_policy_mode' ), 'Admin page detects stale or invalid stored approval policy modes.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Stored approval policy mode "%1$s" is no longer supported.' ), 'Admin page warns when stored approval policy mode falls back to manual.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'update_option( Approval_Policy_Evaluator::OPTION_POLICY_MODE' ), 'Admin page persists approval policy mode through a bounded option.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'update_option( History_Cleanup_Service::OPTION_HISTORY_RETENTION_DAYS' ), 'Admin page persists history retention through the cleanup service option.' );
npcink_governance_core_assert( false !== strpos( $admin_page, "'app-keys'" ), 'Admin page keeps token management available behind an advanced view.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'render_admin_tabs' ), 'Admin page exposes tabbed Core sections.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'npcink-ai-tabs npcink-governance-core-tabs' ) && false !== strpos( $admin_page, 'npcink-ai-tab-active' ) && false !== strpos( $admin_page, 'aria-current="page"' ), 'Admin page uses the shared Npcink AI tab visual standard for Core sections.' );
npcink_governance_core_assert( false === strpos( $admin_page, 'nav-tab-wrapper' ) && false === strpos( $admin_page, 'nav-tab-active' ), 'Admin page no longer uses boxed WordPress nav tabs.' );
npcink_governance_core_assert( false === strpos( $admin_page, "'app-keys' => array" ), 'Admin page does not expose client access tokens as a first-level tab.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Review Queue' ), 'Admin page defaults to the review queue tab.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'History' ), 'Admin page exposes historical proposal records tab.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Open history' ), 'Admin empty state points to historical proposal records.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Settings' ), 'Admin page exposes the Settings tab.' );
npcink_governance_core_assert( false === strpos( $admin_page, 'render_summary_strip' ), 'Admin default page does not use the removed legacy summary strip helper.' );
npcink_governance_core_assert( false === strpos( $admin_page, 'render_status_metric' ), 'Admin default page removes metric cards from the review surface.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'render_table_nav' ), 'Admin page uses a shared WordPress-style table navigation helper for long governance lists.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'render_table_page_button' ), 'Admin page renders compact square pagination buttons through the shared helper.' );
npcink_governance_core_assert( false === strpos( $admin_page, 'paginate_links(' ), 'Admin page does not use the older text-link pagination style for Core admin lists.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'review_page' ), 'Admin page paginates review queue.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'REVIEW_PAGE_SIZE  = 10' ), 'Admin review queue shows ten pending proposals per page.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'render_review_queue_nav' ), 'Admin review queue renders a WordPress-style table navigation row.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'data-npcink-bulk-toggle-all' ), 'Admin review queue supports selecting all visible pending proposals.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'data-npcink-bulk-select' ) && false !== strpos( $admin_page, 'data-npcink-bulk-apply' ), 'Admin review queue exposes compact bulk action controls.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Page %1$d of %2$d' ), 'Admin review queue renders compact page position text.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'archive_page' ), 'Admin page paginates historical proposals.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'ARCHIVE_PAGE_SIZE = 10' ), 'Admin history shows ten historical proposals per page.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'npcink-governance-core-archive-table' ), 'Admin history renders a compact fixed-layout table.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'npcink-governance-core-archive-details-' ), 'Admin history moves technical proposal fields behind row details.' );
npcink_governance_core_assert( false !== strpos( $admin_page, '$this->proposal_display_id( $proposal )' ), 'Admin history shows display ids instead of raw full proposal ids in the default row.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'No historical proposals.' ), 'Admin history has a clear empty state.' );
npcink_governance_core_assert( false === strpos( $admin_page, 'render_archive_primary_action' ), 'Admin history does not expose row-level reopen actions.' );
npcink_governance_core_assert( false === strpos( $admin_page, 'render_archive_row_actions' ), 'Admin history does not expose row-level archive actions.' );
npcink_governance_core_assert( false === strpos( $admin_page, 'archive_status_filter_links' ), 'Admin history does not split expired and archived records into confusing filters.' );
npcink_governance_core_assert( false === strpos( $admin_page, 'render_table_filter_links' ), 'Admin history does not render status filter links.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'audit_page' ), 'Admin page paginates governance audit.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'app_key_page' ), 'Admin page paginates token management.' );
npcink_governance_core_assert( false === strpos( $admin_page, 'archive_status' ), 'Admin history does not expose an expired versus archived status filter.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'No requests need review.' ), 'Admin page provides a clear active queue empty state.' );
npcink_governance_core_assert( false === strpos( $admin_page, 'render_advanced_entries' ), 'Admin default page no longer renders low-frequency administration links inline.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Recent Activity' ), 'Admin default page exposes a compact recent activity section.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'npcink-governance-core-utility-panel-stretch' ), 'Admin default page renders recent activity as a secondary utility panel.' );
npcink_governance_core_assert( false !== strpos( $admin_page, "'limit'                => 5," ) && false !== strpos( $admin_page, 'npcink-governance-core-recent-activity-list' ), 'Admin default page shows the latest five non-noise governance events.' );
npcink_governance_core_assert( false !== strpos( $admin_page, "'exclude_event_names'  => \$this->low_value_audit_events()," ), 'Admin recent activity panel excludes read-noise events.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Latest Core governance events. Full audit is in its own tab.' ), 'Admin default page points detailed activity to the audit tab.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Activity Log' ), 'Admin page exposes a release-facing activity log view.' );
npcink_governance_core_assert( false === strpos( $admin_page, 'Advanced: Core App Keys' ), 'Admin default page no longer folds app-key management inline.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Client access tokens' ), 'Admin Settings page exposes token management as a visible low-frequency section.' );
npcink_governance_core_assert( false === strpos( $admin_page, 'Manage Core app keys' ), 'Admin UI no longer labels the action as Core app-key management.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Search activity' ) && false !== strpos( $admin_page, 'audit_search' ), 'Admin audit exposes a broad default activity search field.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'audit_event_filter_options' ) && false !== strpos( $admin_page, 'audit_caller_type_filter_options' ), 'Admin audit uses dropdowns for event and caller filters.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'audit_time_range_options' ) && false !== strpos( $admin_page, 'audit_created_after_for_range' ), 'Admin audit exposes bounded time range filtering.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Advanced filters' ), 'Admin page folds detailed audit filters into an advanced disclosure.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'has_active_audit_advanced_filters' ), 'Admin audit opens the advanced filter disclosure only for technical filters.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'render_audit_filter_chips' ), 'Admin audit renders active filters as clearable chips.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'audit_include_read_events' ), 'Admin audit hides read noise by default with an opt-in filter.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'render_audit_table_nav' ) && false !== strpos( $admin_page, "'audit_page'" ), 'Admin audit renders WordPress-style top and bottom table navigation.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'npcink-governance-core-audit-table' ), 'Admin audit renders a compact fixed-layout activity table.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Proposal_Repository::display_id_for_proposal_id( $proposal_id )' ), 'Admin audit shows stable display ids instead of full proposal ids in the default row.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'render_audit_context_summary' ), 'Admin audit keeps the default context column concise.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'npcink-governance-core-audit-row-details' ) && false !== strpos( $admin_page, 'audit_detail_rows' ), 'Admin audit moves full technical metadata behind a row details disclosure.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'audit_event_label' ), 'Admin audit renders user-facing activity labels instead of leading with raw event names.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'audit_filters_from_request' ), 'Admin page reads governance audit filters.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'audit_proposal_id' ), 'Admin page exposes proposal audit filter.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'audit_correlation_id' ), 'Admin page exposes correlation audit filter.' );
npcink_governance_core_assert( false === strpos( $admin_page, 'AI Request Logs remain separate' ), 'Admin activity log does not lead with developer log-correlation copy.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'productized OpenClaw connection settings' ), 'Admin page avoids presenting Core as the OpenClaw product entry point.' );
npcink_governance_core_assert( false === strpos( $admin_page, 'Environment template' ), 'Admin default page no longer exposes an env template.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Client Access Tokens' ), 'Admin token page uses client access token heading.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'token_tab' ), 'Admin token page uses subtabs for token list and issuance.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Client access token sections' ), 'Admin token page labels its token subtabs.' );
npcink_governance_core_assert( false !== strpos( $admin_page, "'token_tab' => 'tokens'" ), 'Admin token page defaults navigation to access tokens.' );
npcink_governance_core_assert( false !== strpos( $admin_page, "'token_tab' => 'revoked'" ), 'Admin token page exposes revoked tokens as an audit subtab.' );
npcink_governance_core_assert( false !== strpos( $admin_page, "'token_tab' => 'issue'" ), 'Admin token page exposes issuance as a secondary subtab.' );
npcink_governance_core_assert( false !== strpos( $admin_page, "\$list_status  = 'revoked' === \$token_tab ? 'revoked' : 'active';" ), 'Admin token page maps list subtabs to bounded token statuses.' );
npcink_governance_core_assert( false !== strpos( $admin_page, "\$this->apps->list_recent( self::APP_KEY_PAGE_SIZE, \$this->offset_for_page( \$page, self::APP_KEY_PAGE_SIZE ), \$list_status )" ), 'Admin token list queries by the selected token status.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Only active tokens are shown here.' ), 'Admin token page explains active tokens remain the default list.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Revoked tokens are shown for audit attribution only.' ), 'Admin revoked token page explains that revoked tokens are read-only audit records.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Issue client access token' ), 'Admin page labels token creation with operator-facing token copy.' );
npcink_governance_core_assert( false !== strpos( $admin_page, "'issue' === \$token_tab" ), 'Admin token page shows the issuance panel only on the Issue token subtab.' );
npcink_governance_core_assert( false !== strpos( $admin_page, "'tokens' === \$token_tab" ), 'Admin token page opens on the access token list subtab.' );
npcink_governance_core_assert( false !== strpos( $admin_page, "'revoked' === \$token_tab" ), 'Admin token page renders a separate revoked-token list subtab.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Issue a scoped token for a trusted governance client. Choose a purpose first; use advanced permissions only for custom clients.' ), 'Admin token creation explains purpose-first issuance.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'caller_type_options' ), 'Admin token creation uses bounded caller type choices.' );
npcink_governance_core_assert( false !== strpos( $admin_page, '<select id="npcink-governance-core-caller-type" name="caller_type">' ), 'Admin token creation renders caller type as a dropdown.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'token_scope_presets' ), 'Admin token creation centralizes purpose presets.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'scope_preset' ), 'Admin token creation posts a scope preset before raw custom scopes.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'adapter_default' ), 'Admin token creation includes an adapter default preset.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'read_only_discovery' ), 'Admin token creation includes a read-only discovery preset.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'trusted_approval_execution' ), 'Admin token creation includes a trusted approval/execution preset.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Advanced permissions and rate limit' ), 'Admin token creation keeps raw scopes and rate limit behind advanced controls.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'npcink-governance-core-token-table' ), 'Admin token page uses a compact token table.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'token_scope_summary' ), 'Admin token page summarizes permission scopes in the default row.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'render_token_detail_table' ), 'Admin token page moves App ID, Key ID, caller type, and full scope strings behind details.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'render_token_status_badge' ), 'Admin token page renders localized token status badges.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Revoke token' ), 'Admin active token rows expose an explicit revoke action.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Revoke this access token immediately?' ), 'Admin token revocation confirmation explains immediate access impact.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'No active access tokens.' ), 'Admin token list empty state refers to active tokens only.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'No revoked access tokens.' ), 'Admin revoked token list has a specific empty state.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'No action' ), 'Admin revoked token rows remain read-only.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Back to settings' ), 'Admin token page returns operators to Settings.' );
npcink_governance_core_assert( false === strpos( $admin_page, 'Create Core App Key' ), 'Admin UI no longer exposes Core App Key creation copy.' );
npcink_governance_core_assert( false === strpos( $admin_page, 'Reopen for review' ), 'Admin page does not expose reopen actions for historical proposals.' );
npcink_governance_core_assert( false === strpos( $admin_page, "__( 'Archive', 'npcink-governance-core' )" ), 'Admin page does not expose archive actions for historical proposals.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'name="app_label" placeholder=' ) && false !== strpos( $admin_page, 'For example: OpenClaw adapter on titan' ), 'Admin token form prompts a specific client label instead of defaulting every token to a generic name.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'product_adapter' ), 'Admin page defaults caller type to product_adapter.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'core_env_text' ), 'Admin page centralizes minimal Core env generation.' );
npcink_governance_core_assert( false === strpos( $admin_page, 'Direct Core Handoff' ), 'Admin page no longer exposes Core handoff guidance.' );
npcink_governance_core_assert( false === strpos( $admin_page, 'Agent rules' ), 'Admin page does not host external agent rules.' );
npcink_governance_core_assert( false === strpos( $admin_page, 'NPCINK_OPENCLAW_ADAPTER_BASE_URL' ), 'Admin page does not export Adapter base URL guidance.' );
npcink_governance_core_assert( false === strpos( $admin_page, 'NPCINK_GOVERNANCE_CORE_INSECURE_SSL=true' ), 'Admin page does not export local TLS test settings.' );
npcink_governance_core_assert( false === strpos( $admin_page, 'NPCINK_GOVERNANCE_CORE_CA_BUNDLE' ), 'Admin page does not export local CA bundle settings.' );
npcink_governance_core_assert( false === strpos( $admin_page, 'include_local_tls' ), 'Admin page does not expose a local TLS export checkbox.' );
npcink_governance_core_assert( false === strpos( $admin_page, 'is_local_base_url' ), 'Admin page no longer computes local TLS defaults.' );
npcink_governance_core_assert( false === strpos( $admin_page, 'openclaw_handoff_text' ), 'Admin page does not generate OpenClaw handoff text.' );
npcink_governance_core_assert( false === strpos( $admin_page, 'openclaw_env_text' ), 'Admin page does not use OpenClaw-named env helpers.' );
npcink_governance_core_assert( false === strpos( $admin_page, 'create-draft-proposal' ), 'Admin page no longer embeds adapter scenario commands.' );
npcink_governance_core_assert( false === strpos( $admin_page, 'create-taxonomy-terms-proposal' ), 'Admin page no longer embeds taxonomy adapter commands.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'NPCINK_GOVERNANCE_CORE_BASE_URL' ), 'Admin page shows base URL env value.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'NPCINK_GOVERNANCE_CORE_APP_TOKEN' ), 'Admin page shows app token env value.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'render_created_app_key_panel' ), 'Admin page renders one-time app key result inside the token screen.' );
npcink_governance_core_assert( false === strpos( $admin_page, '<!doctype html>' ) && false === strpos( $admin_page, 'language_attributes' ), 'Admin one-time token result no longer renders a standalone unstyled document.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'npcink_governance_core_new_app_key_' ) && false !== strpos( $admin_page, 'delete_transient' ), 'Admin one-time token crosses the redirect through a prefixed per-user transient and renders once.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'data-npcink-copy-target' ), 'Admin one-time token result exposes a copy-to-clipboard control.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'shown only once and is not stored in raw form' ), 'Admin page warns that app token is one-time only.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'npcink_governance_core_app_audit_failed' ), 'Admin page does not show one-time app token when creation audit fails.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'default_scopes' ), 'Admin page defaults to scoped external adapter access.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'App_Key_Repository::DEFAULT_RATE_LIMIT' ), 'Admin page exposes bounded rate policy inputs.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'array_map' ) && false !== strpos( $admin_page, 'sanitize_text_field' ), 'Admin app-key scopes are sanitized before repository validation.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'app.revoked' ), 'Admin page audits app-key revocation.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'button-link-delete' ), 'Admin page exposes a key disable action.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Review basis' ), 'Admin proposal detail renders summary-first review basis.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'render_proposal_summary_panel' ), 'Admin proposal detail opens with a compact summary panel.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'npcink-governance-core-proposal-summary' ), 'Admin proposal detail displays review id, status, and evidence summary.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Review ID' ), 'Admin proposal detail leads the summary with the operator-facing review id.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Governance path' ) && false !== strpos( $admin_page, 'proposal_governance_posture' ), 'Admin proposal detail summarizes the operation classification path before raw payload.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Classification and handoff' ), 'Admin proposal overview exposes classification and handoff posture.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Write posture' ) && false !== strpos( $admin_page, 'Execution owner' ) && false !== strpos( $admin_page, 'Blocked guidance' ), 'Admin proposal overview explains write posture, execution owner, and blocked guidance.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Core records proposal, approval, preflight, and audit only; final WordPress writes stay outside Core.' ), 'Admin proposal detail states Core does not execute final WordPress writes.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Adapter or host after Core commit preflight' ), 'Admin proposal detail names Adapter or host as execution owner after Core preflight.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'proposal_summary_request_meta' ), 'Admin proposal detail keeps the top request summary compact.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'npcink-governance-core-summary-actions' ), 'Admin proposal detail integrates pending approve/reject controls into the summary panel.' );
npcink_governance_core_assert( false === strpos( $admin_page, 'npcink-governance-core-decision-bar' ), 'Admin proposal detail does not render a separate empty decision bar.' );
npcink_governance_core_assert( false === strpos( $admin_page, 'npcink-governance-core-decision-summary' ), 'Admin proposal detail decision bar does not repeat review id or status.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'npcink-governance-core-reject-disclosure' ) && false !== strpos( $admin_page, 'Confirm rejection' ), 'Admin proposal detail keeps rejection notes behind a secondary disclosure.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'No risk signals' ), 'Admin proposal detail summarizes zero evidence signals without showing undeclared risk.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'render_proposal_detail_tabs' ), 'Admin proposal detail splits dense content into tabs.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'proposal_tab' ), 'Admin proposal detail preserves the active tab in the URL.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Action plan' ) && false !== strpos( $admin_page, 'Audit evidence' ) && false !== strpos( $admin_page, 'Technical info' ), 'Admin proposal detail exposes action, evidence, and technical tabs.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'render_proposal_identity_panel' ), 'Admin proposal detail groups proposal identity and source metadata.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'render_proposal_detail_groups' ), 'Admin proposal detail uses grouped inspectors instead of a repeated linear identity table.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Technical identity' ), 'Admin proposal detail folds full ids and source-policy metadata behind technical identity.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Governance identity' ) && false !== strpos( $admin_page, 'Source and policy' ), 'Admin proposal detail labels identity and source-policy inspector groups.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'render_proposal_outcome_notice' ), 'Admin proposal detail explains why non-pending proposals no longer show approve/reject controls.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'No pending decision.' ), 'Admin proposal detail renders non-pending outcome copy.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'render_proposal_batch_actions' ), 'Admin proposal detail renders ordered batch actions when write_actions are present.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'proposal_batch_action_rows' ), 'Admin proposal detail derives batch action rows from proposal input and preview metadata.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Batch actions' ) && false !== strpos( $admin_page, 'Target ability' ) && false !== strpos( $admin_page, 'Readiness' ) && false !== strpos( $admin_page, 'Dependency' ), 'Admin proposal detail exposes batch action columns.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'proposal_warning_count' ) && false !== strpos( $admin_page, 'proposal_blocked_count' ), 'Admin proposal detail surfaces warning and blocker counts.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'proposal_action_count' ), 'Admin proposal detail summarizes batch versus single proposal action count.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Ability and policy' ) && false !== strpos( $admin_page, 'Preview signals' ), 'Admin proposal detail groups review basis into ability-policy and non-empty preview signal inspectors.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Preview signals: no warnings, no blocked items, no required input, and no preflight blockers.' ), 'Admin proposal detail compresses zero-value preview signals into one line.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'render_proposed_change_details' ) && false !== strpos( $admin_page, 'proposal_has_proposed_change_details' ), 'Admin proposal detail moves structured preview details into the action-plan tab.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'render_audit_lifecycle_summary' ), 'Admin proposal detail shows compact lifecycle evidence before the full audit table.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'render_audit_outcome_summary' ) && false !== strpos( $admin_page, 'audit_outcome_summary' ), 'Admin proposal audit evidence opens with a current governance outcome summary.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Current governance outcome' ) && false !== strpos( $admin_page, 'Next step' ) && false !== strpos( $admin_page, 'Evidence trail' ), 'Admin proposal audit evidence summarizes outcome, next step, and evidence trail.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Awaiting Core review.' ) && false !== strpos( $admin_page, 'Approved; preflight handoff not yet issued.' ), 'Admin proposal audit outcome explains pending and approved states.' );
npcink_governance_core_assert( false !== strpos( $admin_page, '$this->render_audit_timeline( $proposal, $timeline, false, $timeline_total );' ) && false !== strpos( $admin_page, '$this->render_raw_proposal_payload( $proposal );' ), 'Admin proposal detail separates audit evidence from raw payload.' );
npcink_governance_core_assert( false !== strpos( $admin_page, "'order'               => 'desc'," ), 'Admin proposal detail timeline lists the newest audit events first.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Showing the latest %1$d of %2$d recorded events.' ), 'Admin proposal detail timeline discloses truncation.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'render_status_badge' ), 'Admin proposal status uses visual badges.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'render_risk_badge' ), 'Admin proposal risk uses visual badges.' );
$decision_call_position        = strpos( $admin_page, '$this->render_decision_controls( $proposal );' );
$summary_function_position     = strpos( $admin_page, 'private function render_proposal_summary_panel' );
$identity_function_position    = strpos( $admin_page, 'private function render_proposal_identity_panel' );
$summary_action_label_position = strpos( $admin_page, "esc_html__( 'Decision', 'npcink-governance-core' )" );
$summary_call_position         = strpos( $admin_page, '$this->render_proposal_summary_panel( $proposal );' );
$tabs_call_position            = strpos( $admin_page, '$this->render_proposal_detail_tabs( $proposal, $active_tab );' );
npcink_governance_core_assert( false !== $summary_call_position && false !== $tabs_call_position && $summary_call_position < $tabs_call_position, 'Admin proposal detail places the summary with approve/reject controls before detail tabs.' );
npcink_governance_core_assert( false !== $summary_function_position && false !== $identity_function_position && false !== $decision_call_position && $summary_function_position < $decision_call_position && $decision_call_position < $identity_function_position, 'Admin proposal detail renders decision controls inside the summary panel.' );
npcink_governance_core_assert( false !== $summary_function_position && false !== $summary_action_label_position && $summary_function_position < $summary_action_label_position, 'Admin proposal detail labels the inline summary decision slot.' );
npcink_governance_core_assert( false !== strpos( $admin_css, '.npcink-governance-core-summary-strip' ), 'Admin CSS styles the compact status summary.' );
npcink_governance_core_assert( false !== strpos( $admin_css, '.npcink-governance-core-audit-filter-toolbar' ), 'Admin CSS styles activity filters as a compact toolbar.' );
npcink_governance_core_assert( false !== strpos( $admin_css, '.npcink-governance-core-audit-advanced-grid' ), 'Admin CSS styles advanced activity filters as a compact grid.' );
npcink_governance_core_assert( false !== strpos( $admin_css, '.npcink-governance-core-audit-filter-chips' ), 'Admin CSS styles activity filter chips.' );
npcink_governance_core_assert( false !== strpos( $admin_css, '.npcink-governance-core-audit-table' ) && false !== strpos( $admin_css, 'table-layout: fixed' ), 'Admin CSS keeps the activity table compact and stable.' );
npcink_governance_core_assert( false !== strpos( $admin_css, '.npcink-governance-core-audit-row-details' ) && false !== strpos( $admin_css, '.npcink-governance-core-audit-detail-table' ), 'Admin CSS styles activity row disclosures and detail tables.' );
npcink_governance_core_assert( false !== strpos( $admin_css, '.npcink-governance-core-workbench-toolbar' ), 'Admin CSS styles the utility toolbar.' );
npcink_governance_core_assert( false !== strpos( $admin_css, '.npcink-governance-core-review-table' ), 'Admin CSS styles the compact review list.' );
npcink_governance_core_assert( false !== strpos( $admin_css, '.npcink-governance-core-review-table th' ) && false !== strpos( $admin_css, 'text-align: left' ), 'Admin CSS left-aligns review table headers and cells.' );
npcink_governance_core_assert( false !== strpos( $admin_css, '.npcink-governance-core-request-meta' ), 'Admin CSS styles compact request metadata.' );
npcink_governance_core_assert( false !== strpos( $admin_css, 'white-space: nowrap' ), 'Admin CSS keeps display ids from wrapping in default rows.' );
npcink_governance_core_assert( false !== strpos( $admin_css, '.npcink-governance-core-source-cell' ), 'Admin CSS sizes the dedicated source column.' );
npcink_governance_core_assert( false !== strpos( $admin_css, '.npcink-governance-core-source-summary' ), 'Admin CSS styles compact source attribution.' );
npcink_governance_core_assert( false !== strpos( $admin_css, '.npcink-governance-core-source-actor' ), 'Admin CSS styles the source actor line.' );
npcink_governance_core_assert( false === strpos( $admin_css, '.npcink-governance-core-source-context' ), 'Admin CSS does not reserve a default row line for source context.' );
npcink_governance_core_assert( false !== strpos( $admin_css, '.npcink-governance-core-display-id' ), 'Admin CSS styles proposal display ids.' );
npcink_governance_core_assert( false !== strpos( $admin_css, '.npcink-governance-core-policy-warning' ), 'Admin CSS spaces stale approval policy warnings.' );
npcink_governance_core_assert( false !== strpos( $admin_css, '.npcink-governance-core-settings-section' ), 'Admin CSS spaces directly visible Settings sections.' );
npcink_governance_core_assert( false !== strpos( $admin_css, '.npcink-governance-core-detail-cell' ), 'Admin CSS styles the dedicated details column.' );
npcink_governance_core_assert( false !== strpos( $admin_css, '.npcink-governance-core-row-details-row' ), 'Admin CSS styles inline details rows.' );
npcink_governance_core_assert( false !== strpos( $admin_css, '.npcink-governance-core-row-details-panel' ) && false !== strpos( $admin_css, 'grid-template-columns: minmax(0, 1fr) minmax(0, 1fr)' ), 'Admin CSS renders row details as a two-column inspector.' );
npcink_governance_core_assert( false !== strpos( $admin_css, '.npcink-governance-core-row-details-heading' ), 'Admin CSS styles row detail group headings.' );
npcink_governance_core_assert( false !== strpos( $admin_css, '.npcink-governance-core-row-details-table' ), 'Admin CSS styles row technical details tables.' );
npcink_governance_core_assert( false !== strpos( $admin_css, '.npcink-governance-core-due-label' ), 'Admin CSS styles compact due labels.' );
npcink_governance_core_assert( false !== strpos( $admin_css, '.npcink-governance-core-status-badge' ), 'Admin CSS styles proposal status badges.' );
npcink_governance_core_assert( false !== strpos( $admin_css, '.npcink-governance-core-risk-badge' ), 'Admin CSS styles proposal risk badges.' );
npcink_governance_core_assert( false !== strpos( $admin_css, '.npcink-governance-core-detail-groups' ), 'Admin CSS renders proposal detail inspectors as grouped columns.' );
npcink_governance_core_assert( false !== strpos( $admin_css, '.npcink-governance-core-summary-actions' ) && false !== strpos( $admin_css, '.npcink-governance-core-reject-panel' ), 'Admin CSS styles the proposal detail inline summary actions.' );
npcink_governance_core_assert( false === strpos( $admin_css, '.npcink-governance-core-decision-bar' ), 'Admin CSS does not keep obsolete separate decision bar styling.' );
npcink_governance_core_assert( false !== strpos( $admin_css, '.npcink-governance-core-evidence-ok' ), 'Admin CSS styles the zero-risk evidence summary.' );
npcink_governance_core_assert( false !== strpos( $admin_css, '.npcink-governance-core-classification-badge' ), 'Admin CSS styles classification badges in proposal detail.' );
npcink_governance_core_assert( false !== strpos( $admin_css, '.npcink-governance-core-classification-core-proposal-required' ), 'Admin CSS distinguishes Core proposal required classification.' );
npcink_governance_core_assert( false !== strpos( $admin_css, '.npcink-ai-tabs' ) && false !== strpos( $admin_css, '.npcink-ai-tab-active' ) && false !== strpos( $admin_css, '.npcink-governance-core-detail-tabs' ) && false !== strpos( $admin_css, '.npcink-governance-core-tab-panel' ), 'Admin CSS styles the proposal detail tab shell with the shared Npcink AI tab visual standard.' );
npcink_governance_core_assert( false !== strpos( $admin_css, '.npcink-governance-core-action-plan-table' ), 'Admin CSS keeps batch action rows vertically aligned.' );
npcink_governance_core_assert( false !== strpos( $admin_css, '.npcink-governance-core-display-id-primary' ), 'Admin CSS emphasizes the proposal detail review id.' );
npcink_governance_core_assert( false !== strpos( $admin_css, '.npcink-governance-core-signal-summary' ), 'Admin CSS styles compressed zero-value review signals.' );
npcink_governance_core_assert( false !== strpos( $admin_css, '.npcink-governance-core-audit-outcome' ), 'Admin CSS styles the current governance outcome summary.' );
npcink_governance_core_assert( false !== strpos( $admin_css, '.npcink-governance-core-audit-outcome-grid' ), 'Admin CSS lays out the audit outcome summary.' );
npcink_governance_core_assert( false !== strpos( $admin_css, '.npcink-governance-core-lifecycle-summary' ), 'Admin CSS styles compact proposal lifecycle evidence.' );
npcink_governance_core_assert( false !== strpos( $admin_css, '.npcink-governance-core-empty-state' ), 'Admin CSS styles the active queue empty state.' );
npcink_governance_core_assert( false !== strpos( $admin_css, '.npcink-governance-core-bulk-action-bar' ), 'Admin CSS styles the contextual bulk action bar.' );
npcink_governance_core_assert( false !== strpos( $admin_css, '.npcink-governance-core-bulk-disclosure[hidden]' ), 'Admin CSS hides bulk actions until JavaScript reveals selection context.' );
npcink_governance_core_assert( false !== strpos( $admin_css, '.npcink-governance-core-list-nav' ), 'Admin CSS styles the review queue table navigation row.' );
npcink_governance_core_assert( false === strpos( $admin_css, '.npcink-governance-core-table-filter-links' ), 'Admin CSS no longer carries unused history status filter styles.' );
npcink_governance_core_assert( false !== strpos( $admin_css, '.npcink-governance-core-page-button' ), 'Admin CSS styles compact square pagination buttons.' );
npcink_governance_core_assert( false !== strpos( $admin_css, '.npcink-governance-core-overview-table th' ) && false !== strpos( $admin_css, 'vertical-align: middle' ), 'Admin CSS emphasizes overview labels and vertically centers overview row content.' );
npcink_governance_core_assert( false !== strpos( $admin_css, '.npcink-governance-core-overview-table td:last-child .button' ) && false !== strpos( $admin_css, 'justify-content: center' ), 'Admin CSS centers overview action button labels.' );
npcink_governance_core_assert( false !== strpos( $admin_js, 'data-npcink-details-target' ), 'Admin JS toggles row details by explicit target id.' );
npcink_governance_core_assert( false !== strpos( $admin_js, 'aria-expanded' ), 'Admin JS maintains details toggle accessibility state.' );
npcink_governance_core_assert( false !== strpos( $admin_js, 'target.hidden = expanded' ), 'Admin JS shows and hides inline technical detail rows.' );
npcink_governance_core_assert( false !== strpos( $admin_js, 'updateBulkActions' ), 'Admin JS updates bulk action visibility from row selection.' );
npcink_governance_core_assert( false !== strpos( $admin_js, 'disclosure.hidden = count === 0' ), 'Admin JS hides bulk actions until at least one proposal is selected.' );
npcink_governance_core_assert( false !== strpos( $admin_js, 'data-npcink-bulk-clear' ), 'Admin JS clears selected bulk proposal checkboxes.' );
npcink_governance_core_assert( false !== strpos( $admin_js, 'data-npcink-bulk-toggle-all' ), 'Admin JS keeps the select-all checkbox in sync.' );
npcink_governance_core_assert( false !== strpos( $admin_js, 'data-npcink-bulk-apply' ), 'Admin JS opens the contextual bulk rejection confirmation from the compact toolbar.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'render_article_workflow_review_context' ), 'Admin proposal detail renders article workflow review context.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'render_field_patch_review_context' ), 'Admin proposal detail renders field-level proposal patches.' );
npcink_governance_core_assert( false !== strpos( $admin_page, "'field_patch'" ), 'Admin proposal detail reads preview.field_patch before raw payload.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Field changes' ), 'Admin proposal detail labels field-level changes.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Proposed value' ), 'Admin proposal detail shows proposed field values.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Article workflow' ), 'Admin proposal detail labels article workflow summary.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'final_write_ability' ), 'Admin proposal detail shows article final write ability.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'direct_wordpress_write' ), 'Admin proposal detail shows article direct-write state.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'article_goal_brief' ), 'Admin proposal detail shows article goal artifact availability.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'article_risk_report' ), 'Admin proposal detail shows article risk artifact availability.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Raw proposal payload' ), 'Admin proposal detail folds raw JSON payload behind a disclosure.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'npcink-governance-core-code-block' ), 'Admin proposal detail renders raw JSON in bounded code blocks.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Lifecycle summary' ), 'Admin proposal detail renders lifecycle evidence before the full audit table.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Audit timeline' ), 'Admin proposal detail folds audit timeline behind a secondary disclosure.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'scope_decision' ), 'Admin proposal detail shows scope decision attribution.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'correlation_id' ), 'Admin proposal detail shows correlation id attribution.' );
