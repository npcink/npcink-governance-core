<?php
/** Governance contracts test module. @package NpcinkGovernanceCore */

$governance = npcink_governance_core_read( $root . '/docs/governance-contract.md' );
npcink_governance_core_assert( false !== strpos( $governance, 'proposal.created' ), 'Governance contract records proposal.created event.' );
npcink_governance_core_assert( false !== strpos( $governance, 'preview.operation_classification' ) && false !== strpos( $governance, 'intake_path=core_proposal' ), 'Governance contract requires proposal intake classification evidence.' );
npcink_governance_core_assert( false !== strpos( $governance, 'operation-classification-v1' ) && false !== strpos( $governance, 'core_proposal_required` classification must use proposal intake' ), 'Governance contract requires local consent classification evidence.' );
npcink_governance_core_assert( false !== strpos( $governance, 'proposal.policy_evaluated' ), 'Governance contract records proposal.policy_evaluated event.' );
npcink_governance_core_assert( false !== strpos( $governance, 'proposal.auto_approved' ), 'Governance contract records proposal.auto_approved event.' );
npcink_governance_core_assert( false !== strpos( $governance, 'proposal.approved' ), 'Governance contract records proposal.approved event.' );
npcink_governance_core_assert( false !== strpos( $governance, 'proposal.rejected' ), 'Governance contract records proposal.rejected event.' );
npcink_governance_core_assert( false !== strpos( $governance, 'proposal.expired' ), 'Governance contract records proposal.expired event.' );
npcink_governance_core_assert( false !== strpos( $governance, 'proposal.archived' ), 'Governance contract records proposal.archived event.' );
npcink_governance_core_assert( false !== strpos( $governance, 'proposal.reopened' ), 'Governance contract records proposal.reopened event.' );
npcink_governance_core_assert( false !== strpos( $governance, 'proposal.executed' ), 'Governance contract records proposal.executed event.' );
npcink_governance_core_assert( false !== strpos( $governance, 'proposal.execution_failed' ), 'Governance contract records proposal.execution_failed event.' );
npcink_governance_core_assert( false !== strpos( $governance, 'proposal.viewed' ), 'Governance contract records proposal.viewed event.' );
npcink_governance_core_assert( false !== strpos( $governance, 'proposal.listed' ), 'Governance contract records proposal.listed event.' );
npcink_governance_core_assert( false !== strpos( $governance, 'commit.preflighted' ), 'Governance contract records commit.preflighted event.' );
npcink_governance_core_assert( false !== strpos( $governance, 'read_request.created' ), 'Governance contract records read_request.created event.' );
npcink_governance_core_assert( false !== strpos( $governance, 'read_request.approved' ), 'Governance contract records read_request.approved event.' );
npcink_governance_core_assert( false !== strpos( $governance, 'read_request.rejected' ), 'Governance contract records read_request.rejected event.' );
npcink_governance_core_assert( false !== strpos( $governance, 'read_request.preflighted' ), 'Governance contract records read_request.preflighted event.' );
npcink_governance_core_assert( false !== strpos( $governance, 'core_authorization_truth=npcink_governance_core' ), 'Governance contract keeps Core as sensitive read authorization truth.' );
npcink_governance_core_assert( false !== strpos( $governance, 'currently discoverable ability id' ), 'Governance contract requires real discoverable proposal ability ids.' );
npcink_governance_core_assert( false !== strpos( $governance, 'must not reintroduce' ), 'Governance contract rejects legacy confirmation parameters.' );
npcink_governance_core_assert( false !== strpos( $governance, 'supported approval policy mode set is closed to `manual`' ) && false !== strpos( $governance, '`smart_guarded`, and `dev_allow_all`' ), 'Governance contract freezes the supported approval policy mode set.' );
npcink_governance_core_assert( false !== strpos( $governance, 'removed legacy mode names, fall back to `manual` and must not act as aliases' ), 'Governance contract rejects legacy approval policy aliases.' );
npcink_governance_core_assert( false !== strpos( $governance, 'core/categories' ), 'Governance contract documents safe categories block for homepage layouts.' );

$approval_policy_standard = npcink_governance_core_read( $root . '/docs/approval-policy-evaluator-standard.md' );
foreach (
	array(
		'Status: active planning standard',
		'npcink_governance_core_approval_policy_mode',
		'smart_guarded',
		'dev_allow_all',
		'Approval_Policy_Strategy',
		'NPCINK_GOVERNANCE_CORE_ENABLE_DEV_ALLOW_ALL',
		'manual_required',
		'auto_approved',
		'blocked',
		'guarded',
		'trusted_local',
		'break_glass',
		'caller.core_policy',
		'proposal.policy_evaluated',
		'proposal.auto_approved',
		'build-nonproduction-content-cleanup-plan',
		'plan_to_proposal_batch',
		'npcink-abilities-toolkit/trash-post',
		'npcink-abilities-toolkit/create-draft',
		'npcink-abilities-toolkit/adopt-cloud-media-derivative',
		'npcink-abilities-toolkit/update-media-details',
		'smart_guarded_create_draft_auto_approved',
		'smart_guarded_media_derivative_auto_approved',
		'guarded_media_derivative_candidate',
		'guarded_media_derivative_exact_local_artifact_evidence',
		'exact local 11-field proposal',
		'no legacy id, URL, run, checksum-alias, size-alias, receive, transfer, or',
		'smart_guarded_media_alt_auto_approved',
		'guarded_media_alt_candidate',
		'media_alt_caption_review_set.v1',
		'media_optimization_plan',
		'dev_allow_all_auto_approved',
		'dev_allow_all_rejected_disabled',
		'supported policy mode enum is closed',
		'Admin settings must make that fallback visible',
		'trusted test-content',
		'include_unattached_nonproduction_media',
		'npcink-abilities-toolkit/delete-media-permanently',
		'npcink-abilities-toolkit/set-post-terms',
		'hourly and daily auto-approval quotas',
		'Do not widen real auto approval beyond implemented narrow candidates until all of these remain true',
		'Adapter still executes only after approved status and successful preflight',
		'does not add a rules DSL',
		'composer smoke:wp',
	) as $required
) {
	npcink_governance_core_assert( false !== strpos( $approval_policy_standard, $required ), 'Approval policy standard contains required text: ' . $required );
}

$rest_contract = npcink_governance_core_read( $root . '/docs/rest-api-contract.md' );
foreach (
	array(
		'GET /contract',
		'GET /capabilities',
		'POST /proposals',
		'POST /proposals/from-plan',
		'GET /proposals/{proposal_id}',
		'POST /proposals/{proposal_id}/approve',
		'POST /proposals/{proposal_id}/reject',
		'POST /proposals/{proposal_id}/commit-preflight',
		'POST /read-requests',
		'GET /read-requests/{request_id}',
		'POST /read-requests/{request_id}/approve',
		'POST /read-requests/{request_id}/reject',
		'POST /read-requests/{request_id}/read-preflight',
		'GET /audit',
		'POST /apps',
		'Authorization: Bearer npcink_governance_core.<key_id>.<secret>',
		'X-Npcink-Governance-Core-App-Token',
		'governance_mode',
		'execution_surface',
		'core_proxy_execute=false',
		'core_proxy_execute',
		'commit_execution=false',
		'npcink_governance_core_contract.v1',
		'runtime discovery surface',
		'or app scope `contract:read`',
		'runtime_contract_endpoint_version',
		'minimum_adapter_contract_version',
		'metadata_only',
		'admin_authenticated',
		'context_bindings',
		'site_binding',
		'site_url',
		'home_url',
		'blog_id',
		'supported_when_forwarded_by_trusted_adapter',
		'signed_client_fingerprint',
		'client_key_fingerprint',
		'write_execution=false',
		'read_authorization_required',
		'requires_read_authorization',
		'authorization_mode',
		'core_read_authorization_required',
		'core_read_request',
		'read_authorization_context',
		'read_authorization_granted',
		'core_authorization_truth',
		'read_requests:create',
		'read_requests:approve',
		'read_requests:preflight',
		'npcink_governance_core_app_scope_forbidden',
		'npcink_governance_core_app_rate_limited',
		'npcink_governance_core_invalid_ability_id',
		'npcink_governance_core_ability_not_available',
		'preview.operation_classification',
		'npcink_governance_core_proposal_classification_rejected',
		'npcink_governance_core_proposal_insert_failed',
		'npcink_governance_core_proposal_audit_failed',
		'npcink_governance_core_policy_decision_audit_failed',
		'npcink_governance_core_proposal_decision_audit_failed',
		'npcink_governance_core_legacy_confirmation_rejected',
		'npcink_governance_core_proposal_expired',
		'expired',
		'archived',
		'audit_timeline',
		'correlation_id',
		'display_id',
		'deterministic human-facing alias',
		'policy_decision',
		'policy_profile',
		'policy_reasons',
		'approved_input_hash',
		'approved_preview_hash',
		'mode set is closed to `manual`, `smart_guarded`, and `dev_allow_all`',
		'removed legacy mode names, fall back to',
		'policy_version',
		'$outputs.<prior_action_id>.<field>',
		'ordered batch proposal',
		'npcink-toolbox/build-article-batch-write-plan',
		'npcink-abilities-toolkit/build-media-optimization-plan',
		'npcink-abilities-toolkit/build-media-alt-apply-plan',
		'media_alt_apply_plan.v1',
		'operator_visual_review_confirmed',
		'expected_current_alt',
		'npcink-abilities-toolkit/build-media-adoption-enhancement-plan',
		'npcink-abilities-toolkit/build-media-rename-plan',
		'article_batch_write_plan',
		'media_optimization_plan',
		'media_adoption_enhancement_plan',
		'media_rename_plan',
		'scope_decision',
		'app_id',
		'key_id',
		'caller_type',
		'commit:record_execution',
		'event_name',
		'core.commit.preflight',
		'status=warning',
	) as $required
) {
	npcink_governance_core_assert( false !== strpos( $rest_contract, $required ), 'REST API contract contains required text: ' . $required );
}

$handoff_validation = npcink_governance_core_read( $root . '/docs/core-governance-handoff-validation.md' );
foreach (
	array(
		'Core proposal records must store real ability ids',
		'content/draft-preview',
		'npcink-abilities-toolkit/create-draft',
		'Create Draft Governance Scenario',
		'create-draft-proposal',
		'npcink-abilities-toolkit/set-post-seo-meta',
		'Set Post SEO Meta Governance Scenario',
		'create-seo-meta-proposal',
		'npcink-abilities-toolkit/approve-comment',
		'Approve Comment Governance Scenario',
		'create-comment-approval-proposal',
		'No proposal is required for read-only intake.',
		'does not add workflow runtime ownership',
	) as $required
) {
	npcink_governance_core_assert( false !== strpos( $handoff_validation, $required ), 'Handoff validation doc contains required text: ' . $required );
}

$database_schema = npcink_governance_core_read( $root . '/docs/database-schema.md' );
foreach (
	array(
		'{prefix}npcink_governance_core_proposals',
		'{prefix}npcink_governance_core_read_requests',
		'{prefix}npcink_governance_core_audit_log',
		'{prefix}npcink_governance_core_app_keys',
		'{prefix}npcink_governance_core_app_rate_limits',
		'pending',
		'approved',
		'rejected',
		'expired',
		'archived',
		'app.created',
		'app.revoked',
		'app.rate_limited',
		'proposal.created',
		'proposal.policy_evaluated',
		'proposal.auto_approved',
		'proposal.plan_ingested',
		'proposal.expired',
		'proposal.archived',
		'proposal.reopened',
		'proposal.listed',
		'proposal.viewed',
		'commit.preflighted',
		'read_request.created',
		'read_request.approved',
		'read_request.rejected',
		'read_request.expired',
		'read_request.consumed',
		'read_request.preflighted',
		'read_request.preflight_failed',
		'input_hash',
		'pending_quota_key',
		'status_quota',
		'`display_id` is not stored as a column',
		'consumed',
	) as $required
) {
	npcink_governance_core_assert( false !== strpos( $database_schema, $required ), 'Database schema contains required text: ' . $required );
}

$security_model = npcink_governance_core_read( $root . '/docs/security-model.md' );
foreach (
	array(
		"current_user_can( 'manage_options' )",
		'Final write or destructive execution',
		'confirm_token',
		'write_confirmed',
		'npcink_abilities_toolkit_get_registered()',
		'App Auth Scope Policy',
		'Authorization: Bearer npcink_governance_core.<key_id>.<secret>',
		'raw app secrets',
		'smart_guarded',
		'dev_allow_all',
		'NPCINK_GOVERNANCE_CORE_ENABLE_DEV_ALLOW_ALL',
		'trusted test cleanup trash-post batches',
		'draft-only create-draft proposals',
		'reviewed media derivative adoption proposals',
		'proposal.policy_evaluated',
		'proposal is not left approved',
		'sensitive read request metadata',
		'read_authorization_context',
		'core_authorization_truth=npcink_governance_core',
		'read_requests:create',
		'read_requests:preflight',
	) as $required
) {
	npcink_governance_core_assert( false !== strpos( $security_model, $required ), 'Security model contains required text: ' . $required );
}

$sensitive_read_auth = npcink_governance_core_read( $root . '/docs/sensitive-read-authorization.md' );
foreach (
	array(
		'Core-managed sensitive read authorization',
		'read_authorization_required=true',
		'authorization_mode=core_read_request',
		'POST /read-requests',
		'POST /read-requests/{request_id}/read-preflight',
		'read_authorization_context',
		'approved_input_hash',
		'core_authorization_truth',
		'commit_execution',
		'write_execution',
		'Adapter must not store an Adapter-owned approval truth',
		'workflow runtime',
	) as $required
) {
	npcink_governance_core_assert( false !== strpos( $sensitive_read_auth, $required ), 'Sensitive read authorization doc contains required text: ' . $required );
}

$agent_mcp_entry = npcink_governance_core_read( $root . '/docs/agent-mcp-entry-contract.md' );
foreach (
	array(
		'Core is MCP-aware, but it is not an MCP runtime',
		'Agent and MCP adapters expose abilities. Core governs risky operations.',
		'WordPress Abilities API',
		'OpenClaw Execution Guidance',
		'core_proxy_execute=false',
		'commit_execution=false',
		'channel-private schema, scope, approval, workflow, or write truth',
		'MCP server',
	) as $required
) {
	npcink_governance_core_assert( false !== strpos( $agent_mcp_entry, $required ), 'Agent MCP entry contract contains required text: ' . $required );
}

$app_auth_scope = npcink_governance_core_read( $root . '/docs/app-auth-scope-policy.md' );
foreach (
	array(
		'current_user_can( \'manage_options\' )',
		'minimal implementation active',
		'app_id',
		'secret_hash',
		'Authorization: Bearer npcink_governance_core.<key_id>.<secret>',
		'X-Npcink-Governance-Core-App-Token',
		'app.rate_limited',
		'capabilities:read',
		'proposals:create',
		'commit:preflight',
		'commit:record_execution',
		'read_requests:create',
		'read_requests:read',
		'read_requests:approve',
		'read_requests:reject',
		'read_requests:preflight',
		'Do not grant `proposals:approve`, `commit:record_execution`, or `audit:read` by',
		'Trusted Npcink AI Client Adapter approve-and-execute path',
		'scope_decision',
		'correlation_id',
	) as $required
) {
	npcink_governance_core_assert( false !== strpos( $app_auth_scope, $required ), 'App auth scope policy contains required text: ' . $required );
}

$core_operability = npcink_governance_core_read( $root . '/docs/core-governance-operability.md' );
$decision_briefs = npcink_governance_core_read( $root . '/docs/next-decisions-briefs-2026-10-ux-round2.md' );
npcink_governance_core_assert( false !== strpos( $decision_briefs, 'Decision 1 verdict: accepted' ) && false !== strpos( $decision_briefs, 'option C, adapter-owned notification' ), 'Notification decision is closed as adapter-owned through the observability seam.' );
npcink_governance_core_assert( false !== strpos( $decision_briefs, 'Decision 2 verdict: open' ) && false !== strpos( $decision_briefs, 'implement B only on a recorded trigger' ), 'Polling-filter decision stays open with its recorded trigger condition.' );
npcink_governance_core_assert( false !== strpos( $core_operability, 'accepted Decision 1 outcome (option C)' ), 'Operability doc names the observability hook as the decided notification seam.' );
foreach (
	array(
		'minimal implementation active',
		'Core remains the Npcink AI governance layer for WordPress operations',
		'proposal audit timelines',
		'audit filters',
		'scope_decision',
		'Activity Log',
			'Client Access Tokens',
		'AI Request Logs remain owned by the',
		'`proposal_id` or `correlation_id`',
		'correlation_id',
		'commit_execution=false',
		'core_proxy_execute=false',
		'Do not add these as part of Core governance operability',
		'final commit execution',
		'npcink_governance_core_observability_event',
		'not an audit replacement',
		'AI Provider Log Correlation',
	) as $required
) {
	npcink_governance_core_assert( false !== strpos( $core_operability, $required ), 'Core governance operability doc contains required text: ' . $required );
}

$ai_provider_log_correlation = npcink_governance_core_read( $root . '/docs/ai-provider-log-correlation.md' );
foreach (
	array(
		'AI Provider Log Correlation',
		'adapter-owned productization contract',
		'Provider request logs remain owned by the WordPress `ai` plugin',
		'proposal_id',
		'correlation_id',
		'ability_id',
		'adapter_request_id',
		'adapter_route',
		'ai_provider',
		'ai_model',
		'governance_source=npcink-governance-core',
		'commit_execution=false',
		'core_proxy_execute=false',
		'Core should not add a provider request endpoint',
		'Ollama',
		'qwen3.5:0.8b',
	) as $required
) {
	npcink_governance_core_assert( false !== strpos( $ai_provider_log_correlation, $required ), 'AI provider log correlation doc contains required text: ' . $required );
}

