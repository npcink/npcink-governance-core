<?php
/** Stage and consumers test module. @package NpcinkGovernanceCore */

$next_stage_plan = npcink_governance_core_read( $root . '/docs/next-stage-plan.md' );
npcink_governance_core_assert( false !== strpos( $next_stage_plan, 'Agent/MCP Governance Entry' ), 'Next stage plan includes Agent/MCP governance entry phase.' );
npcink_governance_core_assert( false !== strpos( $next_stage_plan, 'minimal implementation active' ), 'Next stage plan marks app auth as implemented minimally.' );
npcink_governance_core_assert( false !== strpos( $next_stage_plan, 'consumer readiness complete' ), 'Next stage plan marks consumer readiness complete.' );
npcink_governance_core_assert( false !== strpos( $next_stage_plan, 'Core 0.4 Consumer Readiness' ), 'Next stage plan links Core 0.4 consumer readiness.' );
npcink_governance_core_assert( false !== strpos( $next_stage_plan, 'Core Governance Operability' ), 'Next stage plan links Core Governance Operability.' );
npcink_governance_core_assert( false !== strpos( $next_stage_plan, 'Final Commit Execution Boundary' ), 'Next stage plan includes final commit execution boundary phase.' );
npcink_governance_core_assert( false !== strpos( $next_stage_plan, 'ADR-003' ), 'Next stage plan links the current-stage final execution ADR.' );
npcink_governance_core_assert( false === strpos( $next_stage_plan, 'revocation UI, and expiry automation' ), 'Next stage plan no longer lists implemented revocation UI as missing.' );
npcink_governance_core_assert( false !== strpos( $next_stage_plan, 'OpenClaw Adapter / Agent Gateway Planning' ), 'Next stage plan keeps OpenClaw adapter planning outside Core.' );
npcink_governance_core_assert( false !== strpos( $next_stage_plan, 'OpenClaw Execution Guidance' ), 'Next stage plan links OpenClaw execution guidance.' );
npcink_governance_core_assert( false !== strpos( $next_stage_plan, 'productized acceptance in Npcink AI Client Adapter' ), 'Next stage plan points productized OpenClaw acceptance to Adapter.' );
npcink_governance_core_assert( false !== strpos( $next_stage_plan, 'Adapter Handoff And Approval Policy Acceptance' ), 'Next stage plan links Adapter handoff and policy acceptance.' );
npcink_governance_core_assert( false !== strpos( $next_stage_plan, 'continue approve-and-execute implementation in Npcink AI Client Adapter' ), 'Next stage plan moves approve-and-execute implementation to Adapter.' );
npcink_governance_core_assert( false !== strpos( $next_stage_plan, 'Manual local acceptance' ), 'Next stage plan records manual approval policy acceptance.' );
npcink_governance_core_assert( false !== strpos( $next_stage_plan, '/Users/muze/gitee/npcink-ai-client-adapter/docs/openclaw-consumer-acceptance.md' ), 'Next stage plan links Adapter acceptance checklist.' );
npcink_governance_core_assert( false !== strpos( $next_stage_plan, 'AI Provider Log Correlation Acceptance' ), 'Next stage plan includes AI provider log correlation acceptance.' );
npcink_governance_core_assert( false !== strpos( $next_stage_plan, 'real AI provider request log correlation is implemented and tested in' ), 'Next stage plan keeps provider log correlation implementation in Adapter.' );
npcink_governance_core_assert( false !== strpos( $next_stage_plan, 'Create Draft Governance Scenario' ), 'Next stage plan links create-draft scenario.' );
npcink_governance_core_assert( false !== strpos( $next_stage_plan, 'Set Post SEO Meta Governance Scenario' ), 'Next stage plan links set-post-seo-meta scenario.' );
npcink_governance_core_assert( false !== strpos( $next_stage_plan, 'Approve Comment Governance Scenario' ), 'Next stage plan links approve-comment scenario.' );
npcink_governance_core_assert( false !== strpos( $next_stage_plan, 'Taxonomy Terms Preview Governance Scenario' ), 'Next stage plan links taxonomy terms preview scenario.' );
npcink_governance_core_assert( false !== strpos( $next_stage_plan, 'AI Write Classification Matrix' ), 'Next stage plan names the AI Write Classification Matrix as the next-stage target.' );
npcink_governance_core_assert( false !== strpos( $next_stage_plan, 'first-party metadata generation feature' ), 'Next stage plan rejects first-party metadata generation as the current target.' );
npcink_governance_core_assert( false !== strpos( $next_stage_plan, 'proposal hop' ) && false !== strpos( $next_stage_plan, 'author workflow' ), 'Next stage plan keeps native editor AI-plugin author review outside Core proposal review.' );
npcink_governance_core_assert( false !== strpos( $next_stage_plan, 'Content Metadata Delta Operator Trial' ) && false !== strpos( $next_stage_plan, 'historical protocol' ), 'Next stage plan marks the Content Metadata Delta operator trial as historical.' );
npcink_governance_core_assert( false !== strpos( $next_stage_plan, 'npcink-eval-lab' ) && false !== strpos( $next_stage_plan, 'development-only' ), 'Next stage plan keeps eval-lab optional and development-only.' );

$readme = npcink_governance_core_read( $root . '/README.md' );
npcink_governance_core_assert( false !== strpos( $readme, 'Agent MCP Entry Contract' ), 'README links Agent MCP Entry Contract.' );
npcink_governance_core_assert( false !== strpos( $readme, 'App Auth Scope Policy' ), 'README links App Auth Scope Policy.' );
npcink_governance_core_assert( false !== strpos( $readme, 'OpenClaw governance adapter example' ), 'README links OpenClaw governance adapter example.' );
npcink_governance_core_assert( false !== strpos( $readme, 'Core 0.4 Consumer Readiness' ), 'README links Core 0.4 Consumer Readiness.' );
npcink_governance_core_assert( false !== strpos( $readme, 'Core Governance Operability' ), 'README links Core Governance Operability.' );
npcink_governance_core_assert( false !== strpos( $readme, 'AI Provider Log Correlation' ), 'README links AI Provider Log Correlation.' );
npcink_governance_core_assert( false !== strpos( $readme, 'Content Metadata Delta Operator Trial - Superseded Historical Protocol' ), 'README links the superseded Content Metadata Delta operator trial as historical.' );
npcink_governance_core_assert( false !== strpos( $readme, 'OpenClaw Execution Guidance' ), 'README links OpenClaw Execution Guidance.' );
npcink_governance_core_assert( false !== strpos( $readme, 'Adapter Handoff And Approval Policy Acceptance' ), 'README links Adapter handoff and approval policy acceptance.' );
npcink_governance_core_assert( false !== strpos( $readme, 'ADR-003: Keep Final Execution Outside Core For The Current Stage' ), 'README links ADR-003.' );
npcink_governance_core_assert( false !== strpos( $readme, 'Resume After A Break' ), 'README links the resume-after-break guide.' );
npcink_governance_core_assert( false !== strpos( $readme, 'Real Usage Validation' ), 'README links the real-usage validation program.' );
$real_usage_validation = npcink_governance_core_read( $root . '/docs/real-usage-validation.md' );
npcink_governance_core_assert( false !== strpos( $real_usage_validation, 'Weekly Ledger' ) && false !== strpos( $real_usage_validation, 'Recorded-Evidence Exit Criteria' ) && false !== strpos( $real_usage_validation, 'Release Gating' ), 'Real-usage validation keeps its ledger, exit-criteria, and release-gating sections.' );
npcink_governance_core_assert( false !== strpos( $real_usage_validation, 'Low usage is itself the primary product signal' ), 'Real-usage validation records low-usage weeks with a why-line.' );
$approval_policy_standard = npcink_governance_core_read( $root . '/docs/approval-policy-evaluator-standard.md' );
npcink_governance_core_assert( false !== strpos( $approval_policy_standard, 'Observation Window: Widening Standard' ) && false !== strpos( $approval_policy_standard, 'four consecutive ledger weeks of real usage' ), 'smart_guarded widening is gated on the validation ledger observation window.' );
npcink_governance_core_assert( false !== strpos( $approval_policy_standard, 'reverts that site' ) && false !== strpos( $approval_policy_standard, 'policy mode to `manual` the same day' ), 'A recorded misapproval reverts the site to manual the same day.' );
$resume_after_break = npcink_governance_core_read( $root . '/docs/resume-after-break.md' );
npcink_governance_core_assert( false !== strpos( $resume_after_break, 'Shortest Path' ) && false !== strpos( $resume_after_break, 'Gate Cheat Sheet' ) && false !== strpos( $resume_after_break, 'Known Pitfalls' ), 'Resume-after-break guide keeps its shortest path, gate, and pitfalls sections.' );
npcink_governance_core_assert( false !== strpos( $resume_after_break, 'session-breadcrumb' ) && false !== strpos( $resume_after_break, 'docs/next-stage-plan.md' ), 'Resume-after-break guide points at the rolling breadcrumb and the next-stage plan instead of duplicating state.' );
npcink_governance_core_assert( false !== strpos( $readme, 'Productized OpenClaw acceptance should be run from Npcink AI Client Adapter' ), 'README points OpenClaw productized acceptance to Adapter.' );
npcink_governance_core_assert( false !== strpos( $readme, 'Create Draft Governance Scenario' ), 'README links Create Draft Governance Scenario.' );
npcink_governance_core_assert( false !== strpos( $readme, 'Set Post SEO Meta Governance Scenario' ), 'README links Set Post SEO Meta Governance Scenario.' );
npcink_governance_core_assert( false !== strpos( $readme, 'Approve Comment Governance Scenario' ), 'README links Approve Comment Governance Scenario.' );
npcink_governance_core_assert( false !== strpos( $readme, 'Taxonomy Terms Preview Governance Scenario' ), 'README links Taxonomy Terms Preview Governance Scenario.' );
npcink_governance_core_assert( false !== strpos( $readme, 'Cross-Repo Release Acceptance' ), 'README links Cross-Repo Release Acceptance.' );
npcink_governance_core_assert( false !== strpos( $readme, 'Release Candidate Version Matrix' ), 'README links Release Candidate Version Matrix.' );
npcink_governance_core_assert( false !== strpos( $readme, 'AI Write Classification Regression Evidence' ), 'README links AI write classification regression evidence template.' );
npcink_governance_core_assert( false !== strpos( $readme, 'AI Write Classification Stage Closeout - 2026-07-07' ), 'README links AI write classification stage closeout.' );
npcink_governance_core_assert( false !== strpos( $readme, 'signed Adapter request through Core' ) && false !== strpos( $readme, 'Adapter status/readback without moving execution into Core' ), 'README summarizes the cross-repo release chain.' );
npcink_governance_core_assert( false !== strpos( $readme, 'Article writing is an external product/ability concern' ), 'README keeps article product ownership outside Core.' );
npcink_governance_core_assert( false !== strpos( $readme, 'Cloud must not provide Core with' ), 'README prohibits Cloud writing generation for Core intake.' );

$cross_repo_release_acceptance = npcink_governance_core_read( $root . '/docs/cross-repo-release-acceptance.md' );
foreach (
	array(
		'Cross-Repo Release Acceptance',
		'composer acceptance:cross-repo-release',
		'npcink-governance-core',
		'npcink-ai-client-adapter',
		'npcink-abilities-toolkit',
		'Core proposal',
		'Core commit preflight',
		'WordPress Abilities API',
		'Core record-execution',
		'Adapter status/readback',
		'AI write classification regression',
		'AI Write Classification Regression Evidence',
		'Generic AI plugin output accepted through the WordPress editor',
		'External,',
		'automated, delegated, high-risk',
		'incomplete-preview, or batch writes',
		'Core must not add final execution routes',
		'Adapter must not define reusable abilities',
		'Toolkit must not store approval records',
	) as $required
) {
	npcink_governance_core_assert( false !== strpos( $cross_repo_release_acceptance, $required ), 'Cross-repo release acceptance doc contains required text: ' . $required );
}

$release_candidate_version_matrix = npcink_governance_core_read( $root . '/docs/release-candidate-version-matrix.md' );
foreach (
	array(
		'Release Candidate Version Matrix',
		'composer rc:version-matrix',
		'npcink-governance-core',
		'npcink-ai-client-adapter',
		'npcink-abilities-toolkit',
		'0.3.0',
		'0.4.1',
		'0.5.10',
		'must not be moved',
		'--require-tag-ready',
		'stack-rc-2026-09-05-core-0.2.0-adapter-0.3.3-toolkit-0.5.5',
		'The matrix must not become a dependency resolver',
	) as $required
) {
	npcink_governance_core_assert( false !== strpos( $release_candidate_version_matrix, $required ), 'Release candidate version matrix doc contains required text: ' . $required );
}

$adapter_policy_acceptance = npcink_governance_core_read( $root . '/docs/adapter-handoff-and-approval-policy-acceptance.md' );
foreach (
	array(
		'Adapter Handoff And Approval Policy Acceptance',
		'The next execution work belongs in Npcink AI Client Adapter',
		'/Users/muze/gitee/npcink-ai-client-adapter',
		'Adapter should implement or verify this approve-and-execute sequence',
		'Discover Core capability guidance',
		'create a Core proposal with',
		'Poll or display Core proposal status',
		'Call Core commit preflight',
		'Execute the target WordPress ability through WordPress Abilities API',
		'Record the result back to Core with',
		'Adapter must fail closed',
		'Adapter must not',
		'add Core `/execute`, `/proxy-execute`, scheduler, queue, workflow runtime',
		'Manual Approval Policy Acceptance',
		'Require Approval For All',
		'Smart Approval',
		'Allow All Development Mode',
		'Stale Stored Policy Value',
		'commit_execution=false',
		'no Core code path executes a target ability',
	) as $required
) {
	npcink_governance_core_assert( false !== strpos( $adapter_policy_acceptance, $required ), 'Adapter handoff acceptance doc contains required text: ' . $required );
}

$third_party_provider_guide = npcink_governance_core_read( $root . '/docs/third-party-ability-provider-guide.md' );
foreach (
	array(
		'Third-Party Ability Provider Guide',
		'currently discoverable WordPress ability id',
		'permission callbacks',
		'dry-run previews',
		'POST /wp-json/npcink-governance-core/v1/proposals',
		'not a generic workflow runtime',
		'capabilities:read',
		'proposals:create',
		'commit:preflight',
		'provider or adapter, not through',
	) as $required
) {
	npcink_governance_core_assert( false !== strpos( $third_party_provider_guide, $required ), 'Third-party provider guide contains required text: ' . $required );
}

$openclaw_execution_guidance = npcink_governance_core_read( $root . '/docs/openclaw-execution-guidance.md' );
foreach (
	array(
		'Core is the OpenClaw governance bridge, not the OpenClaw execution gateway.',
		'Agent_Gateway_Openclaw_开发建议.md',
		'governance_mode',
		'execution_surface',
		'core_proxy_execute',
		'commit_execution',
		'direct_read',
		'proposal_required',
		'wp_abilities_rest',
		'adapter_after_core_preflight',
		'WordPress Abilities API',
		'Why Governance And Execution Stay Separate',
		'not a permanent ban on future',
		'Combining both roles in Core now would make Core both the governance authority',
		'duplicate WordPress Abilities API as an',
		'When To Reconsider Core Execution',
		'separate ADR',
		'core_proxy_execute=false',
		'Do not add these to Core',
		'/proxy-execute',
		'OpenClaw Adapter, MCP Adapter, or Agent Gateway plugin',
		'Proposal Status Bridge',
		'proxy Core proposal list/detail reads',
		'not expose `POST /proposals/{proposal_id}/approve`',
		'commit:record_execution',
	) as $required
) {
	npcink_governance_core_assert( false !== strpos( $openclaw_execution_guidance, $required ), 'OpenClaw execution guidance doc contains required text: ' . $required );
}

$consumer_readiness = npcink_governance_core_read( $root . '/docs/core-0.4-consumer-readiness.md' );
foreach (
	array(
		'`npcink-abilities-toolkit`: 0.4.0',
		'3d94af7',
		'2c28a27',
		'0f44ee0',
		'`npcink-abilities-toolkit/propose-post-taxonomy-terms` -> `npcink-abilities-toolkit/set-post-terms`',
		'capabilities discovery -> proposal -> approve/reject -> commit-preflight -> audit',
		'`commit_execution=false`',
		'Core does not execute final WordPress mutation',
		'composer test:all',
		'composer smoke:wp',
		'openclaw-governance-adapter.php --help',
		'No current finding requires `npcink-abilities-toolkit`',
		'separate ADR',
	) as $required
) {
	npcink_governance_core_assert( false !== strpos( $consumer_readiness, $required ), 'Core 0.4 consumer readiness doc contains required text: ' . $required );
}

$openclaw_adapter_readme = npcink_governance_core_read( $root . '/examples/openclaw-governance-adapter/README.md' );
foreach (
	array(
		'not an MCP server',
		'GET /wp-json/npcink-governance-core/v1/capabilities',
		'POST /wp-json/npcink-governance-core/v1/proposals',
		'NPCINK_GOVERNANCE_CORE_CA_BUNDLE',
		'NPCINK_GOVERNANCE_CORE_INSECURE_SSL',
		'Do not use `NPCINK_GOVERNANCE_CORE_INSECURE_SSL=true` for production',
		'NPCINK_GOVERNANCE_CORE_APPLICATION_PASSWORD',
		'Generic adapters should not approve proposals by default',
		'governance_mode=direct_read',
		'execution_surface=wp_abilities_rest',
		'governance_mode=proposal_required',
		'execution_surface=adapter_after_core_preflight',
		'core_proxy_execute=false',
		'create-draft-proposal',
		'create-seo-meta-proposal',
		'create-comment-approval-proposal',
		'create-taxonomy-terms-proposal',
		'This command discovers',
		'commit_execution=false',
	) as $required
) {
	npcink_governance_core_assert( false !== strpos( $openclaw_adapter_readme, $required ), 'OpenClaw adapter README contains required text: ' . $required );
}

$create_draft_scenario = npcink_governance_core_read( $root . '/docs/create-draft-governance-scenario.md' );
foreach (
	array(
		'`npcink-abilities-toolkit/create-draft`',
		'write-risk ability with `requires_approval=true`',
		'`dry_run`, `commit`, and `idempotency_key` are governance controls',
		'`commit_execution=false`',
		'approve the proposal or execute the write.',
		'do not patch Core with aliases or fallback definitions',
	) as $required
) {
	npcink_governance_core_assert( false !== strpos( $create_draft_scenario, $required ), 'Create draft scenario doc contains required text: ' . $required );
}

$seo_meta_scenario = npcink_governance_core_read( $root . '/docs/set-post-seo-meta-governance-scenario.md' );
foreach (
	array(
		'`npcink-abilities-toolkit/set-post-seo-meta`',
		'field-level updates to an existing',
		'`post_id` is required',
		'`seo_title` and `seo_description` are the reviewable field update inputs',
		'`dry_run`, `commit`, and `idempotency_key` are governance controls',
		'`preview.field_patch`',
		'`commit_execution=false`',
		'approve the proposal or execute the write',
		'do not patch Core with aliases or fallback definitions',
	) as $required
) {
	npcink_governance_core_assert( false !== strpos( $seo_meta_scenario, $required ), 'SEO metadata scenario doc contains required text: ' . $required );
}

$approve_comment_scenario = npcink_governance_core_read( $root . '/docs/approve-comment-governance-scenario.md' );
foreach (
	array(
		'`npcink-abilities-toolkit/approve-comment`',
		'comment moderation writes for a non-post',
		'`comment_id` is required',
		'current status',
		'`dry_run`, `commit`, and `idempotency_key` are governance controls',
		'`target_action=approve`',
		'`commit_execution=false`',
		'comment remains pending after the Core governance loop',
		'do not patch Core with aliases or fallback definitions',
	) as $required
) {
	npcink_governance_core_assert( false !== strpos( $approve_comment_scenario, $required ), 'Approve comment scenario doc contains required text: ' . $required );
}

$taxonomy_terms_scenario = npcink_governance_core_read( $root . '/docs/taxonomy-terms-preview-governance-scenario.md' );
foreach (
	array(
		'`npcink-abilities-toolkit/propose-post-taxonomy-terms`',
		'`npcink-abilities-toolkit/set-post-terms`',
		'read-risk helper',
		'`governance_mode=direct_read`',
		'WordPress Abilities API',
		'`dry_run=true`, `commit=false`, `create_missing=false`',
		'`commit_execution=false`',
		'post terms remain unchanged after the Core governance loop',
		'audit filters correlate the taxonomy proposal lifecycle',
		'do not patch Core with aliases or fallback definitions',
	) as $required
) {
	npcink_governance_core_assert( false !== strpos( $taxonomy_terms_scenario, $required ), 'Taxonomy terms scenario doc contains required text: ' . $required );
}

$openclaw_adapter = npcink_governance_core_read( $root . '/examples/openclaw-governance-adapter/openclaw-governance-adapter.php' );
foreach (
	array(
		'capabilities',
		'create-draft-proposal',
		'create-seo-meta-proposal',
		'create-comment-approval-proposal',
		'create-taxonomy-terms-proposal',
		'create-proposal',
		'commit-preflight',
		'npcink_governance_core_adapter_assert_create_draft_contract',
		'npcink_governance_core_adapter_assert_seo_meta_contract',
		'npcink_governance_core_adapter_assert_comment_approval_contract',
		'npcink_governance_core_adapter_assert_taxonomy_terms_contract',
		'npcink_governance_core_adapter_taxonomy_terms_payload',
		'npcink_governance_core_adapter_seo_field_patch',
		'Required ability is not discoverable through Core',
		'input schema is missing governance control',
		'input schema is missing field/control',
		'$input[\'commit\']  = false',
		'field_patch',
		'target_action',
		'proposal_helper_ability_id',
		'npcink-abilities-toolkit/propose-post-taxonomy-terms',
		'npcink-abilities-toolkit/set-post-terms',
		'commit_execution',
		'NPCINK_GOVERNANCE_CORE_BASE_URL',
		'NPCINK_GOVERNANCE_CORE_APP_TOKEN',
		'NPCINK_GOVERNANCE_CORE_CA_BUNDLE',
		'NPCINK_GOVERNANCE_CORE_INSECURE_SSL',
		'CURLOPT_CAINFO',
		'CURLOPT_SSL_VERIFYPEER',
		'CURLOPT_SSL_VERIFYHOST',
		'npcink_governance_core_adapter_is_local_url',
		'NPCINK_GOVERNANCE_CORE_APPLICATION_PASSWORD',
		'wp-json/npcink-governance-core/v1',
		'openclaw-governance-adapter-example',
		'This adapter intentionally does not approve proposals.',
	) as $required
) {
	npcink_governance_core_assert( false !== strpos( $openclaw_adapter, $required ), 'OpenClaw adapter script contains required text: ' . $required );
}
npcink_governance_core_assert( false === strpos( $openclaw_adapter, 'proposals/{proposal_id}/approve' ), 'OpenClaw adapter script does not implement approval.' );

