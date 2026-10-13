<?php
/** Security and decisions test module. @package NpcinkGovernanceCore */

$app_key_repository = npcink_governance_core_read( $root . '/includes/Security/App_Key_Repository.php' );
foreach (
	array(
		'npcink_governance_core_app_keys',
		'secret_hash',
		'revoke_by_key_id',
		'revoked',
		'password_hash',
		'password_verify',
		"WHERE status = %s",
		'OFFSET %d',
		'latest_last_used_at',
		'expires_at',
		'expires_soon',
		'rotation_recommended',
		'last_used_ip_hash',
		'revoked_at',
		'revoked_reason',
		'hash_algorithm_version',
		'token_prefix',
		'is_expired',
		'sanitize_future_datetime',
		'expires_soon',
		'capabilities:read',
		'proposals:create',
		'commit:preflight',
		'commit:record_execution',
		'read_requests:create',
		'read_requests:read',
		'read_requests:preflight',
		'TOKEN_PREFIX',
		'npcink_governance_core',
		'npcink_governance_core_app_insert_failed',
		'npcink_governance_core_app_secret_hash_failed',
		'npcink_governance_core_app_scopes_empty',
	) as $required
) {
	npcink_governance_core_assert( false !== strpos( $app_key_repository, $required ), 'App key repository contains required text: ' . $required );
}
npcink_governance_core_assert( false === strpos( $app_key_repository, "'secret' => " . '$secret' ), 'App key repository does not persist raw app secret in DB record.' );
npcink_governance_core_assert( false === strpos( $app_key_repository, 'mai_core.' ), 'App key repository does not generate legacy Magick AI token prefixes.' );
npcink_governance_core_assert( false === strpos( $app_key_repository, 'empty( $clean ) ? $this->default_scopes()' ), 'App key repository does not turn explicitly empty scopes into default scopes.' );
npcink_governance_core_assert( 1 !== preg_match( '/SELECT app_id, app_label, key_id, ' . 'secret_hash, status.*ORDER BY id DESC/s', $app_key_repository ), 'App key list queries do not select secret_hash.' );

$app_rate_limiter = npcink_governance_core_read( $root . '/includes/Security/App_Rate_Limiter.php' );
npcink_governance_core_assert( false !== strpos( $app_rate_limiter, 'npcink_governance_core_app_rate_limits' ), 'App rate limiter stores fixed-window counters.' );
npcink_governance_core_assert( false !== strpos( $app_rate_limiter, 'app_route_window' ), 'App rate limiter has unique app route window key.' );
npcink_governance_core_assert( false !== strpos( $app_rate_limiter, 'ON DUPLICATE KEY UPDATE' ), 'App rate limiter uses an atomic upsert.' );
npcink_governance_core_assert( false !== strpos( $app_rate_limiter, 'request_count + 1' ), 'App rate limiter increments counters in SQL.' );
npcink_governance_core_assert( false !== strpos( $app_rate_limiter, 'request_count < %d' ), 'App rate limiter only increments while under limit.' );

$sensitive_data_redactor = npcink_governance_core_read( $root . '/includes/Security/Sensitive_Data_Redactor.php' );
foreach (
	array(
		'Sensitive_Data_Redactor',
		'sanitize_payload',
		'is_secret_key',
		'redact_secret_string',
		'Bearer [redacted]',
		'api_key',
		'credentials',
		'application_password',
	) as $required
) {
	npcink_governance_core_assert( false !== strpos( $sensitive_data_redactor, $required ), 'Sensitive data redactor contains required text: ' . $required );
}

$app_authenticator = npcink_governance_core_read( $root . '/includes/Security/App_Authenticator.php' );
foreach (
	array(
		'npcink_governance_core_app_auth_missing',
		'npcink_governance_core_app_scope_forbidden',
		'npcink_governance_core_app_rate_limited',
		'can_create_proposals',
		'can_commit_preflight',
		'can_record_execution',
		'commit:record_execution',
		'commit_record_execution',
		'can_create_read_requests',
		'can_preflight_read_requests',
		'read_requests_approve',
		'app.scope_denied',
		'app.rate_limited',
		"x-npcink-governance-core-app-token",
		"mark_scope_decision( 'denied' )",
		"mark_scope_decision( 'rate_limited' )",
		"mark_scope_decision( 'expired' )",
		'request_ip_hash',
	) as $required
) {
	npcink_governance_core_assert( false !== strpos( $app_authenticator, $required ), 'App authenticator contains required text: ' . $required );
}

$apps_controller = npcink_governance_core_read( $root . '/includes/Rest/Apps_Controller.php' );
npcink_governance_core_assert( false !== strpos( $apps_controller, "'/apps'" ), 'Apps REST route is registered.' );
npcink_governance_core_assert( false !== strpos( $apps_controller, "/rotate'" ), 'Apps REST route exposes app-key rotation.' );
npcink_governance_core_assert( false !== strpos( $apps_controller, 'rotate_app' ), 'Apps REST controller registers rotation callback.' );
npcink_governance_core_assert( false !== strpos( $apps_controller, 'app.created' ), 'Apps REST route audits app creation.' );
npcink_governance_core_assert( false !== strpos( $apps_controller, 'app.rotated' ), 'Apps REST route audits app-key rotation.' );
npcink_governance_core_assert( false !== strpos( $apps_controller, 'can_manage' ), 'Apps REST route remains admin-only.' );
npcink_governance_core_assert( false !== strpos( $apps_controller, 'npcink_governance_core_app_audit_failed' ), 'Apps REST route fails app creation when audit cannot be written.' );
npcink_governance_core_assert( false !== strpos( $apps_controller, 'npcink_governance_core_app_rotation_audit_failed' ), 'Apps REST route fails app rotation when audit cannot be written.' );
npcink_governance_core_assert( false !== strpos( $apps_controller, 'npcink_governance_core_app_rotation_revoke_failed' ), 'Apps REST route fails app rotation when old-key revocation fails.' );
npcink_governance_core_assert( false !== strpos( $apps_controller, 'npcink_governance_core_app_rotation_revoke_audit_failed' ), 'Apps REST route fails app rotation when old-key revocation cannot be audited.' );

$adr_001 = npcink_governance_core_read( $root . '/docs/decisions/ADR-001-rebuild-core-as-governance-layer.md' );
$adr_002 = npcink_governance_core_read( $root . '/docs/decisions/ADR-002-no-workflow-runtime-in-core.md' );
$adr_003 = npcink_governance_core_read( $root . '/docs/decisions/ADR-003-keep-final-execution-outside-core.md' );
$adr_004 = npcink_governance_core_read( $root . '/docs/decisions/ADR-004-suite-consolidation-and-local-admin-consent.md' );
$adr_005 = npcink_governance_core_read( $root . '/docs/decisions/ADR-005-keep-core-independent-and-standardize-channel-adapters.md' );
$adr_006 = npcink_governance_core_read( $root . '/docs/decisions/ADR-006-unattended-batch-automation-runtime-boundary.md' );
$adr_007 = npcink_governance_core_read( $root . '/docs/decisions/ADR-007-dedicated-local-automation-runtime-owner.md' );
$adr_010 = npcink_governance_core_read( $root . '/docs/decisions/ADR-010-defer-final-commit-ownership-until-trigger.md' );
$adr_011 = npcink_governance_core_read( $root . '/docs/decisions/ADR-011-verification-read-request-kind-for-post-execution-readback.md' );
$adr_012 = npcink_governance_core_read( $root . '/docs/decisions/ADR-012-execution-attached-verification-reads.md' );
npcink_governance_core_assert( false !== strpos( $adr_001, 'Create a new standalone `npcink-governance-core` plugin' ), 'ADR-001 records rebuild decision.' );
npcink_governance_core_assert( false !== strpos( $adr_002, '`npcink-governance-core` must not implement a workflow runtime' ), 'ADR-002 bans workflow runtime ownership.' );
npcink_governance_core_assert( false !== strpos( $adr_003, 'Core remains governance-only' ), 'ADR-003 keeps Core governance-only for the current stage.' );
npcink_governance_core_assert( false !== strpos( $adr_003, 'adapter_after_core_preflight' ), 'ADR-003 keeps final execution in Adapter/product plugins.' );
npcink_governance_core_assert( false !== strpos( $adr_003, 'no Core `/execute`, `/proxy-execute`' ) && false !== strpos( $adr_003, 'commit route' ), 'ADR-003 blocks accidental Core execution routes.' );
npcink_governance_core_assert( false !== strpos( $adr_004, 'Local Admin Consent Model' ), 'ADR-004 documents local admin consent.' );
npcink_governance_core_assert( false !== strpos( $adr_004, 'The same plugin package may contain those modules' ), 'ADR-004 permits package consolidation without authority collapse.' );
npcink_governance_core_assert( false !== strpos( $adr_004, 'External, automated, batch, destructive, high-impact' ) && false !== strpos( $adr_004, 'previewed AI writes must not use local admin consent' ), 'ADR-004 keeps risky writes behind Core review.' );
npcink_governance_core_assert( false !== strpos( $adr_004, 'Generic AI plugin output inside the WordPress editor' ) && false !== strpos( $adr_004, 'visible editor action is the human' ) && false !== strpos( $adr_004, 'review step' ) && false !== strpos( $adr_004, 'should not add a Core proposal hop' ), 'ADR-004 keeps visible generic AI plugin editor acceptance out of Core proposal review.' );
npcink_governance_core_assert( false !== strpos( $adr_004, 'ADR-001, ADR-002, and ADR-003 remain active' ), 'ADR-004 preserves earlier governance ADRs.' );
npcink_governance_core_assert( false !== strpos( $adr_005, 'Do not merge Core and Adapter as the next implementation step' ), 'ADR-005 defers Core and Adapter merge.' );
npcink_governance_core_assert( false !== strpos( $adr_005, 'OpenClaw Adapter as the first channel adapter' ), 'ADR-005 treats OpenClaw Adapter as one channel adapter.' );
npcink_governance_core_assert( false !== strpos( $adr_005, 'shared operation classification contract' ), 'ADR-005 requires shared operation classification.' );
npcink_governance_core_assert( false !== strpos( $adr_005, 'Future MCP, browser' ) && false !== strpos( $adr_005, 'cloud, or local automation adapters' ), 'ADR-005 preserves future adapter optionality.' );
npcink_governance_core_assert( false !== strpos( $adr_006, 'Do not implement unattended batch automation inside Core or the OpenClaw' ), 'ADR-006 keeps unattended batch automation outside Core and Adapter.' );
npcink_governance_core_assert( false !== strpos( $adr_006, 'dedicated local automation runtime' ), 'ADR-006 requires a dedicated local automation runtime contract.' );
npcink_governance_core_assert( false !== strpos( $adr_006, 'Core must not own jobs, leases, retries, workers' ), 'ADR-006 blocks Core runtime ownership.' );
npcink_governance_core_assert( false !== strpos( $adr_006, 'Adapter must not own scheduler state' ), 'ADR-006 blocks Adapter scheduler ownership.' );
npcink_governance_core_assert( false !== strpos( $adr_006, 'batch_review_summary' ) && false !== strpos( $adr_006, 'batch_review_feedback' ), 'ADR-006 ties current batch work to review feedback, not runtime.' );
npcink_governance_core_assert( false !== strpos( $adr_006, 'Lease, lock, timeout, retry backoff, and dead-letter semantics' ), 'ADR-006 requires lease, timeout, retry, and dead-letter semantics before runtime.' );
npcink_governance_core_assert( false !== strpos( $adr_006, 'Kill switch, pause, resume, and cancel behavior' ), 'ADR-006 requires operator stop controls before runtime.' );
npcink_governance_core_assert( false !== strpos( $adr_006, 'No unattended runtime exists in this phase' ), 'ADR-006 keeps Phase 0 reviewed governance only.' );
npcink_governance_core_assert( false !== strpos( $adr_007, 'Use `npcink-local-automation-runtime` as the dedicated owner' ), 'ADR-007 selects a dedicated future runtime owner.' );
npcink_governance_core_assert( false !== strpos( $adr_007, '`npcink-local-automation-runtime`' ), 'ADR-007 names npcink-local-automation-runtime.' );
npcink_governance_core_assert( false !== strpos( $adr_007, 'repo: `/Users/muze/gitee/npcink-local-automation-runtime`' ), 'ADR-007 records the future runtime repo path.' );
npcink_governance_core_assert( false !== strpos( $adr_007, 'release packaging may bundle it inside' ) && false !== strpos( $adr_007, '`npcink-toolbox`' ), 'ADR-007 allows Toolbox release bundling.' );
npcink_governance_core_assert( false !== strpos( $adr_007, 'module path: `modules/local-automation-runtime/`' ), 'ADR-007 defines the Toolbox bundled module path.' );
npcink_governance_core_assert( false !== strpos( $adr_007, 'Toolbox fixed-flow' ) && false !== strpos( $adr_007, 'runtime state machine' ), 'ADR-007 keeps Toolbox fixed buttons out of runtime ownership.' );
npcink_governance_core_assert( false !== strpos( $adr_007, 'Phase 1 is contract and replay only' ), 'ADR-007 keeps Phase 1 contract and replay only.' );
npcink_governance_core_assert( false !== strpos( $adr_007, 'This Core pass does not create the development repository' ), 'ADR-007 keeps this Core pass implementation-free.' );
npcink_governance_core_assert( false !== strpos( $adr_007, 'Start Trigger And Parked Status' ) && false !== strpos( $adr_007, 'formally parked until a named trigger fires' ), 'ADR-007 runtime development is parked behind a named trigger.' );
npcink_governance_core_assert( false !== strpos( $adr_007, 'records a concrete unattended-automation requirement' ), 'ADR-007 start trigger is a recorded ledger requirement, not opinion.' );
npcink_governance_core_assert( false !== strpos( $adr_007, 'they check the ledger' ), 'ADR-007 parked state is checked through the validation ledger.' );
npcink_governance_core_assert( false !== strpos( $adr_010, 'Trigger Review Checkpoint' ) && false !== strpos( $adr_010, 'the current count of production execution consumers' ), 'ADR-010 defines an observable trigger review checkpoint.' );
npcink_governance_core_assert( false !== strpos( $adr_010, 'successor ADR is opened before release work continues' ), 'ADR-010 stops the release closeout when a trigger fires.' );
npcink_governance_core_assert( false !== strpos( $adr_010, 'not a runtime feature' ), 'ADR-010 keeps the checkpoint review-only.' );
npcink_governance_core_assert( false !== strpos( $adr_011, 'an explicit verification read-request kind' ), 'ADR-011 selects the explicit verification read-request kind.' );
npcink_governance_core_assert( false !== strpos( $adr_011, 'option 2 of issue #125' ) && false !== strpos( $adr_011, 'not an execution-attached read credential' ), 'ADR-011 records the option choice from issue #125.' );
npcink_governance_core_assert( false !== strpos( $adr_011, 'no new trusted-adapter privilege is added' ), 'ADR-011 adds no new adapter privilege.' );
npcink_governance_core_assert( false !== strpos( $adr_011, 'The existing single read-authorization surface stays the only truth' ), 'ADR-011 keeps one read-authorization truth.' );
npcink_governance_core_assert( false !== strpos( $adr_011, 'Implementation is deferred and fires only on recorded evidence' ), 'ADR-011 implementation is evidence-gated.' );
npcink_governance_core_assert( false !== strpos( $adr_011, 'grant-mode deployments keep the known degradation' ), 'ADR-011 classifies pre-trigger degradation as known.' );
npcink_governance_core_assert( false !== strpos( $adr_011, 'npcink-ai-client-adapter issue #93' ), 'ADR-011 hands adapter implementation back to adapter issue #93.' );
npcink_governance_core_assert( false !== strpos( $adr_011, 'Superseded by ADR-012 (2026-10-08)' ), 'ADR-011 is marked superseded by ADR-012.' );
npcink_governance_core_assert( false !== strpos( $adr_012, 'Accepted — supersedes ADR-011' ), 'ADR-012 stands as the accepted decision superseding ADR-011.' );
npcink_governance_core_assert( false !== strpos( $adr_012, 'it mints authorization, never runs reads' ), 'ADR-012 keeps Core authorization-only, never running reads.' );
npcink_governance_core_assert( false !== strpos( $adr_012, 'ahead of any recorded ledger entry' ) && false !== strpos( $adr_012, 'a waiver, not a precedent' ), 'ADR-012 records the ledger-trigger waiver explicitly and non-precedentially.' );
$adr_013 = npcink_governance_core_read( $root . '/docs/decisions/ADR-013-post-execution-verification-read-minting.md' );
npcink_governance_core_assert( false !== strpos( $adr_013, '# ADR-013: Post-Execution Verification Read Minting' ) && false !== strpos( $adr_013, 'Proposed' ), 'ADR-013 is the proposed post-execution minting record, uniquely numbered.' );
npcink_governance_core_assert( false !== strpos( $adr_013, 'renumbered to ADR-013 to' ), 'ADR-013 records its renumbering from the duplicate ADR-012 filing.' );
npcink_governance_core_assert( false !== strpos( $adr_013, 'ADR-012 mints execution-attached verification reads' ) && false !== strpos( $adr_013, 'Limitations section of ADR-012' ), 'ADR-013 references the canonical ADR numbering.' );
$release_closeout_standard = npcink_governance_core_read( $root . '/docs/release-closeout-standard.md' );
npcink_governance_core_assert( false !== strpos( $release_closeout_standard, 'ADR-010 trigger review checkpoint' ), 'Release closeout standard runs the ADR-010 checkpoint before candidate freeze.' );

$external_owner_boundary = npcink_governance_core_read( $root . '/docs/external-owner-boundary-notes.md' );
foreach (
	array(
		'Status: active boundary note',
		'not an implementation contract',
		'Article recipe orchestration',
		'Cloud or hosted article writing',
		'Local automation runtime',
		'Content Metadata Delta product workbench',
		'npcink-local-automation-runtime',
		'npcink-toolbox',
		'Do not add these files or equivalents back to Core',
		'docs/local-automation-runtime-contract.md',
		'tests/fixtures/local-automation-runtime-dry-run-replay.json',
	) as $required
) {
	npcink_governance_core_assert( false !== strpos( $external_owner_boundary, $required ), 'External owner boundary note contains required text: ' . $required );
}
foreach (
	array(
		'docs/local-automation-runtime-contract.md',
		'docs/local-automation-runtime-phase-1-schema.md',
		'tests/fixtures/local-automation-runtime-dry-run-replay.json',
	) as $removed_path
) {
	npcink_governance_core_assert( ! file_exists( $root . '/' . $removed_path ), 'Core must not keep local automation runtime artifact: ' . $removed_path );
}

$content_metadata_operator_trial = npcink_governance_core_read( $root . '/docs/history/content-metadata-delta-operator-trial.md' );
foreach (
	array(
		'Status: superseded as the current next-stage target',
		'not the current next-stage target',
		'must not be used to start a first-party',
		'summary/category/tag recommendation',
		'product inside Core',
		'3 to',
		'real posts',
		'Process one post',
		'Issue Record',
		'Outcome Contract',
		'Learning Entry',
		'reviewed excerpt',
		'existing category assignment',
		'existing tag assignment',
		'Workflow Toolbox or another product plugin',
		'Governance Core',
		'npcink-eval-lab',
		'development-only assistant',
		'composer eval:lab -- --list domain=recommendation provider=offline',
		'composer eval:project:review -- dry_run=true',
		'It must not become',
		'Core proposal source of truth',
		'CI-required gate',
		'WordPress write executor',
		'Stop Rules',
		'automatic taxonomy term creation',
		'Core final execution',
	) as $required
) {
	npcink_governance_core_assert( false !== strpos( $content_metadata_operator_trial, $required ), 'Content Metadata Delta operator trial doc contains required text: ' . $required );
}

$operation_classification = npcink_governance_core_read( $root . '/docs/operation-classification-contract.md' );
$operation_classifier = npcink_governance_core_read( $root . '/includes/Governance/Operation_Classifier.php' );
foreach (
	array(
		'suggestion_only',
		'local_admin_consent',
		'strong_local_confirmation',
		'core_proposal_required',
		'set one displayed existing WordPress image attachment as',
		'reviewed article plus image batch handoff',
		'plan_to_proposal_batch',
		'Any classification that selects or rejects an',
		'evidence surfaces before execution or rejection is reported as final',
		'Operation_Classifier` must remain side-effect free',
		'caller owns persistence and must fail closed',
		'Core proposal intake now persists this path explicitly',
		'preview.operation_classification',
		'operation-classification-v1` `decision_envelope`',
		'AI Write Classification Matrix',
		'Generic AI plugin shows title, excerpt, summary, category, tag, ALT, meta description',
		'Do not add a Core proposal hop',
		'The author\'s visible editor action is the human review step',
		'External Agent, OpenClaw, MCP, Adapter, CLI, scheduled task, Cloud callback',
		'Release Regression Gate',
		'Native editor / generic AI plugin acceptance stays outside Core',
		'`native_editor_commit` is intentionally absent from this table',
		'not a fifth Core classification',
		'Media-library mutation, cross-object changes, global settings, external',
		'exactly one requested plus one',
		'completed local-consent audit pair',
		'High-risk, external, delegated, or batch writes stay in Core proposal',
		'review.** Article/media batch plans',
		'not a new product surface',
	) as $required
) {
	npcink_governance_core_assert( false !== strpos( $operation_classification, $required ), 'Operation classification contract contains required text: ' . $required );
}
npcink_governance_core_assert( false !== strpos( $operation_classifier, "PRE_CLASSIFICATION_NATIVE_EDITOR_COMMIT = 'native_editor_commit'" ), 'Operation classifier names native editor commit as a pre-classification exclusion.' );
npcink_governance_core_assert( false !== strpos( $operation_classifier, 'pre_classification_exclusions()' ), 'Operation classifier exposes pre-classification exclusions separately from allowed classifications.' );
npcink_governance_core_assert( false !== strpos( $rest_contract, '"pre_classification_exclusions": ["native_editor_commit"]' ), 'REST contract documents native editor commit as a pre-classification exclusion.' );

