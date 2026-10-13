<?php
/** Classification and intake test module. @package NpcinkGovernanceCore */

$testing_strategy = npcink_governance_core_read( $root . '/docs/testing-strategy.md' );
foreach (
	array(
		'AI write classification release regression',
		'editor save/publish of visible AI-plugin-style content',
		'composer smoke:local-featured-image',
		'composer smoke:article-media-batch-core',
		'emit Local Admin Consent audit events',
		'AI Write Classification Regression Evidence',
		'Do not commit local credentials',
	) as $required
) {
	npcink_governance_core_assert( false !== strpos( $testing_strategy, $required ), 'Testing strategy documents AI write classification release regression text: ' . $required );
}

$ai_write_regression_evidence = npcink_governance_core_read( $root . '/docs/history/ai-write-classification-regression-evidence.md' );
foreach (
	array(
		'AI Write Classification Regression Evidence',
		'/Users/muze/Local Sites/magick-ai/app/public',
		'https://magick-ai.local/',
		'Do not store local passwords',
		'Lane 1 - Native editor / generic AI plugin acceptance',
		'Core proposal count before',
		'Lane 2 - Toolbox Local Admin Consent featured image',
		'local_admin_consent.requested',
		'Lane 3 - High-risk article/media batch',
		'NPCINK_TOOLBOX_ARTICLE_MEDIA_BATCH_SMOKE_PURGE=1',
		'Release candidate accepted for AI write classification',
		'not become',
		'Core final execution',
	) as $required
) {
	npcink_governance_core_assert( false !== strpos( $ai_write_regression_evidence, $required ), 'AI write classification regression evidence template contains required text: ' . $required );
}

$ai_write_stage_closeout = npcink_governance_core_read( $root . '/docs/history/ai-write-classification-stage-closeout-2026-07-07.md' );
foreach (
	array(
		'AI Write Classification Stage Closeout - 2026-07-07',
		'The current stage target is the AI Write Classification Matrix',
		'first-party summary/category/tag generation.',
		'Generic AI plugin output accepted',
		'first-party summary/category/tag generation in Core',
		'treating normal WordPress editor publish/save as a Core approval workflow',
		'Persisted classification evidence at real Core write entrypoints',
		'Promoted classification to release and new-entrypoint admission',
		'Native editor or generic AI plugin acceptance keeps Core proposal and audit',
		'This stage is complete. Do not continue by adding Core features.',
		'Continue only when one of these triggers appears',
		'Only fix code when the',
		'classification or evidence shows a real gap',
	) as $required
) {
	npcink_governance_core_assert( false !== strpos( $ai_write_stage_closeout, $required ), 'AI write classification stage closeout contains required text: ' . $required );
}

$wordpress_org_release_gate = npcink_governance_core_read( $root . '/docs/wordpress-org-release-gate.md' );
foreach (
	array(
		'AI Write Classification',
		'release regression from [Testing Strategy]',
		'AI Write Classification Regression Evidence',
		'visible generic AI plugin or native editor acceptance',
		'narrow Toolbox existing-attachment featured-image proof',
		'`core_proposal_required`, with Core proposal evidence',
		'not a feature request',
		'record the AI Write',
		'Classification release regression evidence',
		'without storing local credentials',
	) as $required
) {
	npcink_governance_core_assert( false !== strpos( $wordpress_org_release_gate, $required ), 'WordPress.org release gate documents AI write classification release evidence: ' . $required );
}
foreach (
	array(
		'suggestion_only',
		'local_admin_consent',
		'strong_local_confirmation',
		'core_proposal_required',
		'set_featured_image',
		'batch_plan',
		'external_adapter',
		'present_click',
	) as $required
) {
	npcink_governance_core_assert( false !== strpos( $operation_classifier, $required ), 'Operation classifier contains required text: ' . $required );
}
npcink_governance_core_assert( false !== strpos( $operation_classifier, 'operation-classification-v1' ), 'Operation classifier returns a stable policy version.' );
npcink_governance_core_assert( false !== strpos( $operation_classifier, 'decision_envelope' ), 'Operation classifier returns a stable decision envelope.' );
npcink_governance_core_assert( false !== strpos( $operation_classifier, 'risk_factors' ), 'Operation classifier exposes auditable decision risk factors.' );
npcink_governance_core_assert( false !== strpos( $operation_classifier, 'target_ability_id' ), 'Operation classifier requires Core proposal evidence for high-risk writes.' );
npcink_governance_core_assert( false !== strpos( $main_plugin, 'includes/Autoloader.php' ), 'Main plugin uses the class autoloader for operation classifier loading.' );
$plugin_container = npcink_governance_core_read( $root . '/includes/Plugin.php' );
npcink_governance_core_assert( false !== strpos( $plugin_container, 'operation_classifier' ), 'Plugin container exposes the operation classifier.' );
npcink_governance_core_assert( false !== strpos( $plugin_container, 'read_request_repository()->install' ), 'Plugin activation installs sensitive read request table.' );
npcink_governance_core_assert( false !== strpos( $plugin_container, 'Read_Requests_Controller' ), 'Plugin container registers sensitive read request REST controller.' );
npcink_governance_core_assert( false !== strpos( $plugin_container, 'read_request_service' ), 'Plugin container exposes sensitive read request service.' );
npcink_governance_core_assert( false !== strpos( $plugin_container, 'npcink_governance_core_record_local_admin_consent' ) && false !== strpos( $plugin_container, 'record_local_admin_consent_audit' ), 'Plugin container exposes a Core-owned local admin consent audit filter.' );
npcink_governance_core_assert( false !== strpos( $plugin_container, 'local_admin_consent.requested' ) && false !== strpos( $plugin_container, 'local_admin_consent.completed' ) && false !== strpos( $plugin_container, 'local_admin_consent.failed' ), 'Local admin consent audit accepts only bounded lifecycle events.' );
npcink_governance_core_assert( false !== strpos( $plugin_container, "proposal_created']" ) && false !== strpos( $plugin_container, "core_execution']" ), 'Local admin consent audit does not create proposals or execute Core writes.' );
npcink_governance_core_assert( false !== strpos( $plugin_container, 'local_admin_consent_classification_evidence' ), 'Local admin consent audit validates classification evidence before recording.' );
npcink_governance_core_assert( false !== strpos( $plugin_container, 'npcink_governance_core_local_consent_classification_missing' ) && false !== strpos( $plugin_container, 'npcink_governance_core_local_consent_classification_rejected' ), 'Local admin consent audit fails closed for missing or wrong classification evidence.' );

require_once $root . '/includes/Governance/Operation_Classifier.php';
$classifier = new \Npcink\GovernanceCore\Governance\Operation_Classifier();

$suggestion_classification = $classifier->classify(
	array(
		'operation_kind'          => 'suggest',
		'writes_wordpress_state' => false,
	)
);
npcink_governance_core_assert( 'suggestion_only' === (string) ( $suggestion_classification['classification'] ?? '' ), 'Operation classifier returns suggestion_only for no-write suggestions.' );
npcink_governance_core_assert( 'operation-classification-v1' === (string) ( $suggestion_classification['decision_envelope']['decision_version'] ?? '' ), 'Operation classifier decision envelope includes the stable decision version.' );

$editor_ai_plugin_suggestion = $classifier->classify(
	array(
		'request_source'       => 'wp_admin_ui',
		'actor_presence'      => 'present_click',
		'preview_completeness' => 'exact_final',
		'scope'                => 'one_field',
		'reversibility'        => 'easy_undo',
		'operation_kind'       => 'suggest',
		'writes_wordpress_state' => false,
	)
);
npcink_governance_core_assert( 'suggestion_only' === (string) ( $editor_ai_plugin_suggestion['classification'] ?? '' ), 'Operation classifier treats visible editor AI-plugin suggestions as no-write until the author/editor applies them.' );

$editor_ai_plugin_field_apply = $classifier->classify(
	array(
		'request_source'       => 'wp_admin_ui',
		'actor_presence'      => 'present_click',
		'preview_completeness' => 'exact_final',
		'scope'                => 'one_field',
		'reversibility'        => 'easy_undo',
		'operation_kind'       => 'update_metadata',
	)
);
npcink_governance_core_assert( 'local_admin_consent' === (string) ( $editor_ai_plugin_field_apply['classification'] ?? '' ), 'Operation classifier allows a present author to apply one visible low-risk AI-plugin editor field without Core proposal review.' );

$local_consent_classification = $classifier->classify(
	array(
		'request_source'       => 'wp_admin_ui',
		'actor_presence'      => 'present_click',
		'preview_completeness' => 'exact_final',
		'scope'                => 'one_object',
		'reversibility'        => 'easy_undo',
		'operation_kind'       => 'set_featured_image',
	)
);
npcink_governance_core_assert( 'local_admin_consent' === (string) ( $local_consent_classification['classification'] ?? '' ), 'Operation classifier allows single visible low-risk admin writes.' );
npcink_governance_core_assert( in_array( 'actor_user_id', (array) ( $local_consent_classification['required_evidence'] ?? array() ), true ), 'Local admin consent requires actor evidence.' );
npcink_governance_core_assert( 'wp_admin_ui' === (string) ( $local_consent_classification['decision_envelope']['request_source'] ?? '' ), 'Local admin consent envelope preserves normalized request source.' );

$batch_featured_images_classification = $classifier->classify(
	array(
		'request_source'       => 'wp_admin_ui',
		'actor_presence'      => 'present_click',
		'preview_completeness' => 'partial',
		'scope'                => 'multiple_objects',
		'reversibility'        => 'backup_restore',
		'operation_kind'       => 'batch_plan',
	)
);
npcink_governance_core_assert( 'core_proposal_required' === (string) ( $batch_featured_images_classification['classification'] ?? '' ), 'Operation classifier sends batch image selection to Core proposal review even from wp-admin.' );
npcink_governance_core_assert( in_array( 'scope_not_single_object', (array) ( $batch_featured_images_classification['reasons'] ?? array() ), true ), 'Batch image selection is rejected from local consent because it touches multiple objects.' );
npcink_governance_core_assert( in_array( 'broad_or_batch_scope', (array) ( $batch_featured_images_classification['decision_envelope']['risk_factors'] ?? array() ), true ), 'Batch classification envelope records broad or batch scope risk.' );

$strong_confirmation_classification = $classifier->classify(
	array(
		'request_source'       => 'wp_admin_ui',
		'actor_presence'      => 'present_click',
		'preview_completeness' => 'sufficient',
		'scope'                => 'one_object',
		'reversibility'        => 'backup_restore',
		'operation_kind'       => 'replace_file',
	)
);
npcink_governance_core_assert( 'strong_local_confirmation' === (string) ( $strong_confirmation_classification['classification'] ?? '' ), 'Operation classifier escalates high-impact single-object writes.' );

$editor_publish_classification = $classifier->classify(
	array(
		'request_source'       => 'wp_admin_ui',
		'actor_presence'      => 'present_click',
		'preview_completeness' => 'exact_final',
		'scope'                => 'one_object',
		'reversibility'        => 'backup_restore',
		'operation_kind'       => 'publish',
	)
);
npcink_governance_core_assert( 'strong_local_confirmation' === (string) ( $editor_publish_classification['classification'] ?? '' ), 'Operation classifier treats a visible editor publish action as high-impact local confirmation rather than a default Core proposal hop.' );

$media_optimization_batch_classification = $classifier->classify(
	array(
		'request_source'       => 'wp_admin_ui',
		'actor_presence'      => 'present_click',
		'preview_completeness' => 'sufficient',
		'scope'                => 'one_object',
		'reversibility'        => 'backup_restore',
		'operation_kind'       => 'batch_plan',
	)
);
npcink_governance_core_assert( 'core_proposal_required' === (string) ( $media_optimization_batch_classification['classification'] ?? '' ), 'Operation classifier keeps one-attachment media optimization batch plans in Core proposal review.' );
npcink_governance_core_assert( in_array( 'operation_kind_requires_core_proposal', (array) ( $media_optimization_batch_classification['reasons'] ?? array() ), true ), 'Media optimization batch plan is rejected from local confirmation because batch plans require Core proposals.' );

$batch_classification = $classifier->classify(
	array(
		'request_source'       => 'external_adapter',
		'actor_presence'      => 'delegated',
		'preview_completeness' => 'partial',
		'scope'                => 'multiple_objects',
		'reversibility'        => 'hard_restore',
		'operation_kind'       => 'batch_plan',
	)
);
npcink_governance_core_assert( 'core_proposal_required' === (string) ( $batch_classification['classification'] ?? '' ), 'Operation classifier requires Core proposal for external batch writes.' );
npcink_governance_core_assert( in_array( 'target_ability_id', (array) ( $batch_classification['required_evidence'] ?? array() ), true ), 'Core proposal classification requires target ability evidence.' );

$ability_adapter = npcink_governance_core_read( $root . '/includes/Capabilities/Ability_Registry_Adapter.php' );
npcink_governance_core_assert( false !== strpos( $ability_adapter, 'npcink_abilities_toolkit_get_registered' ), 'Ability intake keeps npcink-abilities-toolkit as a compatibility discovery source.' );
npcink_governance_core_assert( false !== strpos( $ability_adapter, 'wp_get_abilities' ), 'Ability intake uses WordPress Abilities API as the aggregate discovery source.' );
npcink_governance_core_assert( false !== strpos( $ability_adapter, 'definition_to_array' ), 'Ability intake normalizes public WP_Ability getter output.' );
npcink_governance_core_assert( false !== strpos( $ability_adapter, "'none'" ), 'Ability intake has missing-provider diagnostic state.' );
npcink_governance_core_assert( false !== strpos( $ability_adapter, 'execution_guidance' ), 'Ability intake adds capability execution guidance.' );
npcink_governance_core_assert( false !== strpos( $ability_adapter, "'governance_mode'" ), 'Ability intake exposes governance mode.' );
npcink_governance_core_assert( false !== strpos( $ability_adapter, "'execution_surface'" ), 'Ability intake exposes execution surface.' );
npcink_governance_core_assert( false !== strpos( $ability_adapter, "'core_proxy_execute'" ), 'Ability intake reports no Core proxy execution.' );
npcink_governance_core_assert( false !== strpos( $ability_adapter, "'direct_read'" ), 'Ability intake guides direct read abilities.' );
npcink_governance_core_assert( false !== strpos( $ability_adapter, "'proposal_required'" ), 'Ability intake guides proposal-required abilities.' );
npcink_governance_core_assert( false !== strpos( $ability_adapter, "'intake_contract_version'" ), 'Ability intake exposes a versioned readiness contract.' );
npcink_governance_core_assert( false !== strpos( $ability_adapter, "'intake_status'" ), 'Ability intake exposes ready or blocked status.' );
npcink_governance_core_assert( false !== strpos( $ability_adapter, "'intake_reasons'" ), 'Ability intake exposes stable fail-closed reason keys.' );
npcink_governance_core_assert( false !== strpos( $ability_adapter, "'ready_count'" ), 'Ability intake reports ready capability count.' );
npcink_governance_core_assert( false !== strpos( $ability_adapter, "'blocked_count'" ), 'Ability intake reports blocked capability count.' );
npcink_governance_core_assert( false !== strpos( $ability_adapter, "'risk_undeclared'" ), 'Ability intake blocks undeclared risk instead of defaulting to read.' );
npcink_governance_core_assert( false !== strpos( $ability_adapter, "'risk_annotations_conflict'" ), 'Ability intake blocks contradictory provider risk and WordPress annotations.' );
npcink_governance_core_assert( false !== strpos( $ability_adapter, "'rest_exposure_disabled'" ), 'Ability intake blocks explicitly REST-hidden abilities.' );
npcink_governance_core_assert( false !== strpos( $ability_adapter, "'write_approval_conflict'" ), 'Ability intake blocks write abilities that explicitly disable approval.' );
npcink_governance_core_assert( false !== strpos( $ability_adapter, "'read_policy'" ), 'Ability intake exposes read policy.' );
npcink_governance_core_assert( false !== strpos( $ability_adapter, "'read_authorization_required'" ), 'Ability intake exposes read authorization required flag.' );
npcink_governance_core_assert( false !== strpos( $ability_adapter, "'requires_read_authorization'" ), 'Ability intake exposes Adapter-compatible read authorization flag.' );
npcink_governance_core_assert( false !== strpos( $ability_adapter, "'authorization_mode'" ), 'Ability intake exposes read authorization mode.' );
npcink_governance_core_assert( false !== strpos( $ability_adapter, "'read_authorization'" ), 'Ability intake exposes nested read authorization metadata.' );
npcink_governance_core_assert( false !== strpos( $ability_adapter, 'read_authorization_preflight_route' ), 'Ability intake exposes read authorization route guidance.' );
npcink_governance_core_assert( false !== strpos( $ability_adapter, 'core_read_authorization_required' ), 'Ability intake marks sensitive reads as Core authorization required.' );
npcink_governance_core_assert( false !== strpos( $ability_adapter, "'sensitivity'" ), 'Ability intake exposes read sensitivity.' );
npcink_governance_core_assert( false !== strpos( $ability_adapter, "'redaction_required'" ), 'Ability intake exposes read redaction requirement.' );
npcink_governance_core_assert( false !== strpos( $ability_adapter, 'sensitivity_contract' ), 'Ability intake resolves undeclared and conflicting read sensitivity conservatively.' );
npcink_governance_core_assert( false !== strpos( $ability_adapter, "'risk_sources_conflict'" ), 'Ability intake compares risk declarations across provider sources.' );
npcink_governance_core_assert( false !== strpos( $ability_adapter, "'rest_exposure_undeclared'" ), 'Ability intake requires explicit REST exposure.' );
npcink_governance_core_assert( false !== strpos( $ability_adapter, 'implementation_posture_metadata' ), 'Ability intake reads provider implementation posture metadata.' );
npcink_governance_core_assert( false !== strpos( $ability_adapter, "'implementation_posture_available'" ), 'Ability intake exposes implementation posture availability.' );
npcink_governance_core_assert( false !== strpos( $ability_adapter, "'implementation_posture'" ), 'Ability intake exposes implementation posture metadata.' );
npcink_governance_core_assert( false !== strpos( $ability_adapter, "'meta', 'npcink', 'implementation_posture'" ) || false !== strpos( $ability_adapter, "\$npcink_meta['implementation_posture']" ), 'Ability intake accepts Toolkit mirrored Npcink implementation posture metadata.' );

$ability_intake = npcink_governance_core_read( $root . '/docs/ability-intake-contract.md' );
npcink_governance_core_assert( false !== strpos( $ability_intake, 'reference provider and smoke-test' ), 'Ability intake contract treats npcink-abilities-toolkit as the reference provider.' );
npcink_governance_core_assert( false !== strpos( $ability_intake, 'not a generic third-party workflow runtime' ), 'Ability intake contract keeps third-party plan fan-out allowlisted.' );
npcink_governance_core_assert( false !== strpos( $ability_intake, 'Third-Party Ability Provider Guide' ), 'Ability intake contract links third-party provider guidance.' );
npcink_governance_core_assert( false !== strpos( $ability_intake, 'npcink_abilities_toolkit_get_workflow_definitions()' ), 'Ability intake contract prefers runtime workflow definition discovery.' );
npcink_governance_core_assert( false !== strpos( $ability_intake, 'agent-workflow-replay.json' ), 'Ability intake contract points to the shared replay fixture.' );
npcink_governance_core_assert( false !== strpos( $ability_intake, 'does not copy the fixture into a workflow runtime' ), 'Ability intake contract keeps replay consumption out of runtime ownership.' );
npcink_governance_core_assert( false !== strpos( $ability_intake, 'currently discoverable' ), 'Ability intake contract rejects unavailable proposal ability ids.' );
npcink_governance_core_assert( false !== strpos( $ability_intake, 'Create Draft Governance Scenario' ), 'Ability intake contract points to the create-draft scenario.' );
npcink_governance_core_assert( false !== strpos( $ability_intake, 'Set Post SEO Meta Governance Scenario' ), 'Ability intake contract points to the set-post-seo-meta scenario.' );
npcink_governance_core_assert( false !== strpos( $ability_intake, 'Approve Comment Governance Scenario' ), 'Ability intake contract points to the approve-comment scenario.' );
npcink_governance_core_assert( false !== strpos( $ability_intake, 'Taxonomy Terms Preview Governance Scenario' ), 'Ability intake contract points to the taxonomy terms preview scenario.' );
npcink_governance_core_assert( false !== strpos( $ability_intake, 'core/categories' ), 'Ability intake contract documents safe categories block for homepage layouts.' );
npcink_governance_core_assert( false !== strpos( $ability_intake, 'Fail-Closed Intake Readiness' ), 'Ability intake contract documents discovery versus admission.' );
npcink_governance_core_assert( false !== strpos( $ability_intake, 'intake_status=ready' ), 'Ability intake contract requires ready status at governance entrypoints.' );

$ability_intake_adr = npcink_governance_core_read( $root . '/docs/decisions/ADR-008-fail-closed-ability-intake.md' );
npcink_governance_core_assert( false !== strpos( $ability_intake_adr, 'Fail Closed At Ability Intake' ), 'ADR-008 records the fail-closed intake decision.' );
npcink_governance_core_assert( false !== strpos( $ability_intake_adr, 'execution_surface=none' ), 'ADR-008 keeps blocked abilities non-executable.' );

$proposal_service_source = npcink_governance_core_read( $root . '/includes/Governance/Proposal_Service.php' );
$plan_service_source = npcink_governance_core_read( $root . '/includes/Governance/Plan_Proposal_Service.php' );
$read_request_service_source = npcink_governance_core_read( $root . '/includes/Governance/Read_Request_Service.php' );
$commit_preflight_source = npcink_governance_core_read( $root . '/includes/Governance/Commit_Preflight_Service.php' );
npcink_governance_core_assert( false !== strpos( $proposal_service_source, 'npcink_governance_core_ability_intake_blocked' ), 'Proposal creation rejects blocked ability intake.' );
npcink_governance_core_assert( false !== strpos( $proposal_service_source, 'npcink_governance_core_ability_not_proposal_eligible' ), 'Proposal creation admits only governed write or destructive abilities.' );
npcink_governance_core_assert( false !== strpos( $plan_service_source, 'npcink_governance_core_plan_ability_intake_blocked' ), 'Plan intake rejects a blocked planning ability.' );
npcink_governance_core_assert( false !== strpos( $plan_service_source, 'target_ability_intake_blocked' ), 'Plan intake rejects blocked target abilities.' );
npcink_governance_core_assert( false !== strpos( $read_request_service_source, 'intake_blocked_error' ), 'Sensitive read lifecycle rejects blocked ability intake.' );
npcink_governance_core_assert( false !== strpos( $commit_preflight_source, 'npcink_governance_core_ability_intake_blocked' ), 'Commit preflight rejects blocked ability intake.' );
npcink_governance_core_assert( false !== strpos( $commit_preflight_source, 'npcink_governance_core_ability_not_proposal_eligible' ), 'Commit preflight rechecks governed write eligibility.' );
npcink_governance_core_assert( false !== strpos( $proposal_service_source, 'flagged_media_attestation_gate' ), 'Proposal approval gates illegal_content_no_backup flagged media removal on the recorded attestation.' );
npcink_governance_core_assert( false !== strpos( $proposal_service_source, '$this->flagged_media_attestation_gate( $existing )' ), 'Proposal approval invokes the flagged media attestation gate before approving.' );
npcink_governance_core_assert( false !== strpos( $proposal_service_source, 'npcink_governance_core_flagged_media_attestation_required' ), 'Proposal approval returns the stable flagged media attestation error.' );
npcink_governance_core_assert( false !== strpos( $commit_preflight_source, 'flagged_media_branch_evidence_preflight' ), 'Commit preflight revalidates flagged media branch evidence before handoff.' );
npcink_governance_core_assert( false !== strpos( $commit_preflight_source, '$this->flagged_media_branch_evidence_preflight( $proposal )' ), 'Commit preflight invokes the flagged media branch evidence revalidation before handoff.' );
npcink_governance_core_assert( false !== strpos( $commit_preflight_source, 'npcink_governance_core_flagged_media_branch_evidence_missing' ), 'Commit preflight fails closed when flagged media branch evidence is inconsistent.' );
npcink_governance_core_assert( false !== strpos( $plan_service_source, 'flagged_media_removal_audit_description' ), 'Flagged media plan intake audits a deletion description without content retention.' );

$shared_replay_path = npcink_governance_core_shared_replay_fixture_path( $root );
npcink_governance_core_assert( '' !== $shared_replay_path, 'Shared npcink-abilities-toolkit replay fixture is available for Core static proof.' );
$shared_replay_json = npcink_governance_core_read( $shared_replay_path );
$shared_replay      = json_decode( $shared_replay_json, true );
npcink_governance_core_assert( is_array( $shared_replay ), 'Shared replay fixture decodes as JSON.' );
npcink_governance_core_assert( 'v1' === (string) ( $shared_replay['schema_version'] ?? '' ), 'Shared replay fixture uses schema v1.' );
npcink_governance_core_assert( is_array( $shared_replay['cases'] ?? null ) && count( $shared_replay['cases'] ) >= 5, 'Shared replay fixture exposes stabilization recipe cases.' );
$shared_replay_forbidden_fields = array(
	'workflow_state',
	'execution_state',
	'schedule',
	'scheduler',
	'retry_policy',
	'queue',
	'lease',
	'model',
	'model_routing',
	'prompt',
	'prompt_registry',
	'approval_store',
	'approval_policy',
	'audit_log',
	'quota',
	'commit_policy',
	'final_write_authority',
);
npcink_governance_core_assert( '' === npcink_governance_core_find_forbidden_key( $shared_replay, $shared_replay_forbidden_fields ), 'Shared replay fixture does not contain host-runtime or governance ownership fields.' );
foreach ( (array) ( $shared_replay['cases'] ?? array() ) as $case_id => $case ) {
	$case = is_array( $case ) ? $case : array();
	npcink_governance_core_assert( 'workflow_recipe' === (string) ( $case['definition_kind'] ?? '' ), 'Shared replay case ' . $case_id . ' remains a declarative workflow recipe.' );
	npcink_governance_core_assert( '' !== (string) ( $case['recipe_id'] ?? '' ), 'Shared replay case ' . $case_id . ' exposes a recipe id.' );
	npcink_governance_core_assert( (string) ( $case['preferred_ability_id'] ?? '' ) === (string) ( $case['entrypoint_ability_id'] ?? '' ), 'Shared replay case ' . $case_id . ' selects its preferred bundle as entrypoint.' );
	npcink_governance_core_assert( 0 === strpos( (string) ( $case['entrypoint_ability_id'] ?? '' ), 'npcink-abilities-toolkit/' ), 'Shared replay case ' . $case_id . ' entrypoint is a real Toolkit ability id.' );
	npcink_governance_core_assert( ! empty( $case['natural_tasks'] ) && is_array( $case['natural_tasks'] ), 'Shared replay case ' . $case_id . ' provides host-side routing examples.' );
	npcink_governance_core_assert( is_array( $case['expanded_ability_ids'] ?? null ), 'Shared replay case ' . $case_id . ' exposes expanded read-chain ids.' );
	npcink_governance_core_assert( is_array( $case['disallowed_default_ability_ids'] ?? null ) && ! empty( $case['disallowed_default_ability_ids'] ), 'Shared replay case ' . $case_id . ' names write defaults Core must govern.' );
	npcink_governance_core_assert( 'host' === (string) ( $case['handoff']['owner'] ?? '' ), 'Shared replay case ' . $case_id . ' keeps handoff ownership in the host.' );
	npcink_governance_core_assert( false !== strpos( (string) ( $case['failure_policy'] ?? '' ), 'fail_closed' ), 'Shared replay case ' . $case_id . ' fails closed.' );
	foreach ( (array) ( $case['disallowed_default_ability_ids'] ?? array() ) as $disallowed_ability_id ) {
		$disallowed_ability_id = (string) $disallowed_ability_id;
		npcink_governance_core_assert( false !== strpos( $disallowed_ability_id, '/' ) && 0 !== strpos( $disallowed_ability_id, 'workflow/' ), 'Shared replay case ' . $case_id . ' disallowed default is a real ability id, not a workflow label.' );
		npcink_governance_core_assert( $disallowed_ability_id !== (string) ( $case['entrypoint_ability_id'] ?? '' ), 'Shared replay case ' . $case_id . ' does not select a write target as entrypoint.' );
		npcink_governance_core_assert( ! in_array( $disallowed_ability_id, (array) ( $case['expanded_ability_ids'] ?? array() ), true ), 'Shared replay case ' . $case_id . ' keeps write targets out of the expanded read chain.' );
	}
}

