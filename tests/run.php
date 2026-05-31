<?php
/**
 * Static contract tests for the MVP plugin.
 *
 * @package MagickAICore
 */

$root = dirname( __DIR__ );

/**
 * Assertion helper.
 *
 * @param bool   $condition Condition.
 * @param string $message Failure message.
 * @return void
 */
function magick_ai_core_assert( bool $condition, string $message ): void {
	if ( ! $condition ) {
		fwrite( STDERR, '[fail] ' . $message . "\n" );
		exit( 1 );
	}
}

/**
 * Reads a file.
 *
 * @param string $path Path.
 * @return string
 */
function magick_ai_core_read( string $path ): string {
	$contents = is_readable( $path ) ? file_get_contents( $path ) : false;
	return is_string( $contents ) ? $contents : '';
}

/**
 * Returns project text files used for drift checks.
 *
 * @param string $root Root.
 * @return array<int,string>
 */
function magick_ai_core_project_files( string $root ): array {
	$files = array();
	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator(
			$root,
			FilesystemIterator::SKIP_DOTS
		)
	);

	foreach ( $iterator as $file ) {
		$path = $file->getPathname();
		if ( false !== strpos( $path, '/.git/' ) ) {
			continue;
		}
		if ( preg_match( '/\.(php|md|json)$/', $path ) ) {
			$files[] = $path;
		}
	}

	sort( $files );

	return $files;
}

$main_plugin = magick_ai_core_read( $root . '/magick-ai-core.php' );
magick_ai_core_assert( false !== strpos( $main_plugin, 'Plugin Name: Magick AI Core' ), 'Main plugin file declares plugin header.' );
magick_ai_core_assert( false !== strpos( $main_plugin, 'register_activation_hook' ), 'Main plugin file registers activation hook.' );
magick_ai_core_assert( false !== strpos( $main_plugin, 'plugins_loaded' ), 'Main plugin file boots after plugins_loaded.' );
magick_ai_core_assert( false === strpos( $main_plugin, 'example.com' ), 'Main plugin header does not use placeholder Plugin URI.' );

$readme = magick_ai_core_read( $root . '/README.md' );
foreach (
	array(
		'WordPress AI operation governance layer',
		'It does not generate content',
		'GET /wp-json/magick-ai-core/v1/capabilities',
		'POST /wp-json/magick-ai-core/v1/apps',
		'POST /wp-json/magick-ai-core/v1/proposals',
		'POST /wp-json/magick-ai-core/v1/proposals/from-plan',
		'GET /wp-json/magick-ai-core/v1/proposals/{proposal_id}',
		'POST /wp-json/magick-ai-core/v1/proposals/{proposal_id}/approve',
		'POST /wp-json/magick-ai-core/v1/proposals/{proposal_id}/commit-preflight',
	) as $required
) {
	magick_ai_core_assert( false !== strpos( $readme, $required ), 'README contains required phrase: ' . $required );
}

$wp_readme = magick_ai_core_read( $root . '/readme.txt' );
foreach (
	array(
		'=== Magick AI Core ===',
		'Stable tag: 0.1.0',
		'Requires at least: 6.9',
		'Tested up to: 7.0',
		'Requires PHP: 7.4',
		'License: GPLv2 or later',
		'== Description ==',
	) as $required
) {
	magick_ai_core_assert( false !== strpos( $wp_readme, $required ), 'WordPress readme contains required phrase: ' . $required );
}

$distignore = magick_ai_core_read( $root . '/.distignore' );
foreach ( array( 'tests', 'examples', 'docs', 'AGENTS.md', '.sisyphus', '.workbuddy' ) as $required ) {
	magick_ai_core_assert( false !== strpos( $distignore, $required ), 'Release distignore excludes development path: ' . $required );
}

$positioning = magick_ai_core_read( $root . '/docs/product-positioning.md' );
magick_ai_core_assert( false !== strpos( $positioning, 'Magick AI Core governs AI-assisted WordPress operations.' ), 'Positioning keeps one-sentence product truth.' );
magick_ai_core_assert( false !== strpos( $positioning, '`magick-ai-abilities`' ), 'Positioning names magick-ai-abilities as ability owner.' );
magick_ai_core_assert( false !== strpos( $positioning, '`magick-ai-content-assistant`' ), 'Positioning names Content Assistant as product UX owner.' );

$admin_menu_standard = magick_ai_core_read( $root . '/docs/admin-menu-standard.md' );
foreach ( array( '`Magick AI`', '`Governance`', '`OpenClaw Connection`', '`Cloud Connection`', '`Ability Packages`' ) as $required ) {
	magick_ai_core_assert( false !== strpos( $admin_menu_standard, $required ), 'Admin menu standard documents required entry: ' . $required );
}

$admin_page = magick_ai_core_read( $root . '/includes/Admin/Admin_Page.php' );
foreach ( array( 'PARENT_MENU_SLUG', 'add_menu_page', 'add_submenu_page', 'Governance', 'admin.php' ) as $required ) {
	magick_ai_core_assert( false !== strpos( $admin_page, $required ), 'Admin page implements shared menu contract: ' . $required );
}

$governance = magick_ai_core_read( $root . '/docs/governance-contract.md' );
magick_ai_core_assert( false !== strpos( $governance, 'proposal.created' ), 'Governance contract records proposal.created event.' );
magick_ai_core_assert( false !== strpos( $governance, 'proposal.approved' ), 'Governance contract records proposal.approved event.' );
magick_ai_core_assert( false !== strpos( $governance, 'proposal.rejected' ), 'Governance contract records proposal.rejected event.' );
magick_ai_core_assert( false !== strpos( $governance, 'proposal.viewed' ), 'Governance contract records proposal.viewed event.' );
magick_ai_core_assert( false !== strpos( $governance, 'proposal.listed' ), 'Governance contract records proposal.listed event.' );
magick_ai_core_assert( false !== strpos( $governance, 'commit.preflighted' ), 'Governance contract records commit.preflighted event.' );
magick_ai_core_assert( false !== strpos( $governance, 'currently discoverable ability id' ), 'Governance contract requires real discoverable proposal ability ids.' );
magick_ai_core_assert( false !== strpos( $governance, 'must not reintroduce' ), 'Governance contract rejects legacy confirmation parameters.' );

$rest_contract = magick_ai_core_read( $root . '/docs/rest-api-contract.md' );
foreach (
	array(
		'GET /capabilities',
		'POST /proposals',
		'POST /proposals/from-plan',
		'GET /proposals/{proposal_id}',
		'POST /proposals/{proposal_id}/approve',
		'POST /proposals/{proposal_id}/reject',
		'POST /proposals/{proposal_id}/commit-preflight',
		'GET /audit',
		'POST /apps',
		'Authorization: Bearer mai_core.<key_id>.<secret>',
		'governance_mode',
		'execution_surface',
		'core_proxy_execute=false',
		'core_proxy_execute',
		'commit_execution=false',
		'magick_ai_core_app_scope_forbidden',
		'magick_ai_core_app_rate_limited',
		'magick_ai_core_invalid_ability_id',
		'magick_ai_core_ability_not_available',
		'magick_ai_core_legacy_confirmation_rejected',
		'audit_timeline',
		'correlation_id',
		'scope_decision',
		'app_id',
		'key_id',
		'caller_type',
		'event_name',
	) as $required
) {
	magick_ai_core_assert( false !== strpos( $rest_contract, $required ), 'REST API contract contains required text: ' . $required );
}

$handoff_validation = magick_ai_core_read( $root . '/docs/core-governance-handoff-validation.md' );
foreach (
	array(
		'Core proposal records must store real ability ids',
		'content/draft-preview',
		'magick-ai/create-draft',
		'Create Draft Governance Scenario',
		'create-draft-proposal',
		'magick-ai/set-post-seo-meta',
		'Set Post SEO Meta Governance Scenario',
		'create-seo-meta-proposal',
		'magick-ai/approve-comment',
		'Approve Comment Governance Scenario',
		'create-comment-approval-proposal',
		'No proposal is required for read-only intake.',
		'does not add workflow runtime ownership',
	) as $required
) {
	magick_ai_core_assert( false !== strpos( $handoff_validation, $required ), 'Handoff validation doc contains required text: ' . $required );
}

$database_schema = magick_ai_core_read( $root . '/docs/database-schema.md' );
foreach (
	array(
		'{prefix}magick_ai_core_proposals',
		'{prefix}magick_ai_core_audit_log',
		'{prefix}magick_ai_core_app_keys',
		'{prefix}magick_ai_core_app_rate_limits',
		'pending',
		'approved',
		'rejected',
		'app.created',
		'app.revoked',
		'app.rate_limited',
		'proposal.created',
		'proposal.plan_ingested',
		'proposal.listed',
		'proposal.viewed',
		'commit.preflighted',
	) as $required
) {
	magick_ai_core_assert( false !== strpos( $database_schema, $required ), 'Database schema contains required text: ' . $required );
}

$security_model = magick_ai_core_read( $root . '/docs/security-model.md' );
foreach (
	array(
		"current_user_can( 'manage_options' )",
		'Final write or destructive execution',
		'confirm_token',
		'write_confirmed',
		'magick_ai_abilities_get_registered()',
		'App Auth Scope Policy',
		'Authorization: Bearer mai_core.<key_id>.<secret>',
		'raw app secrets',
	) as $required
) {
	magick_ai_core_assert( false !== strpos( $security_model, $required ), 'Security model contains required text: ' . $required );
}

$agent_mcp_entry = magick_ai_core_read( $root . '/docs/agent-mcp-entry-contract.md' );
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
	magick_ai_core_assert( false !== strpos( $agent_mcp_entry, $required ), 'Agent MCP entry contract contains required text: ' . $required );
}

$app_auth_scope = magick_ai_core_read( $root . '/docs/app-auth-scope-policy.md' );
foreach (
	array(
		'current_user_can( \'manage_options\' )',
		'minimal implementation active',
		'app_id',
		'secret_hash',
		'Authorization: Bearer mai_core.<key_id>.<secret>',
		'app.rate_limited',
		'capabilities:read',
		'proposals:create',
		'commit:preflight',
		'Do not grant `proposals:approve` or `audit:read` by default to generic MCP',
		'Trusted Magick AI Adapter approve-and-execute path',
		'scope_decision',
		'correlation_id',
	) as $required
) {
	magick_ai_core_assert( false !== strpos( $app_auth_scope, $required ), 'App auth scope policy contains required text: ' . $required );
}

$core_operability = magick_ai_core_read( $root . '/docs/core-governance-operability.md' );
foreach (
	array(
		'minimal implementation active',
		'Core remains the WordPress AI operation governance layer',
		'proposal audit timelines',
		'audit filters',
		'scope_decision',
		'Recent Governance Audit',
		'Advanced Core App Keys',
		'AI Request Logs remain owned by the',
		'`proposal_id` or `correlation_id`',
		'correlation_id',
		'commit_execution=false',
		'core_proxy_execute=false',
		'Do not add these as part of Core governance operability',
		'final commit execution',
		'AI Provider Log Correlation',
	) as $required
) {
	magick_ai_core_assert( false !== strpos( $core_operability, $required ), 'Core governance operability doc contains required text: ' . $required );
}

$ai_provider_log_correlation = magick_ai_core_read( $root . '/docs/ai-provider-log-correlation.md' );
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
		'governance_source=magick-ai-core',
		'commit_execution=false',
		'core_proxy_execute=false',
		'Core should not add a provider request endpoint',
		'Ollama',
		'qwen3.5:0.8b',
	) as $required
) {
	magick_ai_core_assert( false !== strpos( $ai_provider_log_correlation, $required ), 'AI provider log correlation doc contains required text: ' . $required );
}

$next_stage_plan = magick_ai_core_read( $root . '/docs/next-stage-plan.md' );
magick_ai_core_assert( false !== strpos( $next_stage_plan, 'Agent/MCP Governance Entry' ), 'Next stage plan includes Agent/MCP governance entry phase.' );
magick_ai_core_assert( false !== strpos( $next_stage_plan, 'minimal implementation active' ), 'Next stage plan marks app auth as implemented minimally.' );
magick_ai_core_assert( false !== strpos( $next_stage_plan, 'consumer readiness complete' ), 'Next stage plan marks consumer readiness complete.' );
magick_ai_core_assert( false !== strpos( $next_stage_plan, 'Core 0.4 Consumer Readiness' ), 'Next stage plan links Core 0.4 consumer readiness.' );
magick_ai_core_assert( false !== strpos( $next_stage_plan, 'Core Governance Operability' ), 'Next stage plan links Core Governance Operability.' );
magick_ai_core_assert( false !== strpos( $next_stage_plan, 'Final Commit Execution ADR Decision' ), 'Next stage plan includes final commit execution ADR decision phase.' );
magick_ai_core_assert( false !== strpos( $next_stage_plan, 'OpenClaw Adapter / Agent Gateway Planning' ), 'Next stage plan keeps OpenClaw adapter planning outside Core.' );
magick_ai_core_assert( false !== strpos( $next_stage_plan, 'OpenClaw Execution Guidance' ), 'Next stage plan links OpenClaw execution guidance.' );
magick_ai_core_assert( false !== strpos( $next_stage_plan, 'productized acceptance in Magick AI Adapter' ), 'Next stage plan points productized OpenClaw acceptance to Adapter.' );
magick_ai_core_assert( false !== strpos( $next_stage_plan, '/Users/muze/gitee/magick-ai-adapter/docs/openclaw-consumer-acceptance.md' ), 'Next stage plan links Adapter acceptance checklist.' );
magick_ai_core_assert( false !== strpos( $next_stage_plan, 'AI Provider Log Correlation Acceptance' ), 'Next stage plan includes AI provider log correlation acceptance.' );
magick_ai_core_assert( false !== strpos( $next_stage_plan, 'real AI provider request log correlation is implemented and tested in' ), 'Next stage plan keeps provider log correlation implementation in Adapter.' );
magick_ai_core_assert( false !== strpos( $next_stage_plan, 'Create Draft Governance Scenario' ), 'Next stage plan links create-draft scenario.' );
magick_ai_core_assert( false !== strpos( $next_stage_plan, 'Set Post SEO Meta Governance Scenario' ), 'Next stage plan links set-post-seo-meta scenario.' );
magick_ai_core_assert( false !== strpos( $next_stage_plan, 'Approve Comment Governance Scenario' ), 'Next stage plan links approve-comment scenario.' );
magick_ai_core_assert( false !== strpos( $next_stage_plan, 'Taxonomy Terms Preview Governance Scenario' ), 'Next stage plan links taxonomy terms preview scenario.' );

$readme = magick_ai_core_read( $root . '/README.md' );
magick_ai_core_assert( false !== strpos( $readme, 'Agent MCP Entry Contract' ), 'README links Agent MCP Entry Contract.' );
magick_ai_core_assert( false !== strpos( $readme, 'App Auth Scope Policy' ), 'README links App Auth Scope Policy.' );
magick_ai_core_assert( false !== strpos( $readme, 'OpenClaw governance adapter example' ), 'README links OpenClaw governance adapter example.' );
magick_ai_core_assert( false !== strpos( $readme, 'Core 0.4 Consumer Readiness' ), 'README links Core 0.4 Consumer Readiness.' );
magick_ai_core_assert( false !== strpos( $readme, 'Core Governance Operability' ), 'README links Core Governance Operability.' );
magick_ai_core_assert( false !== strpos( $readme, 'AI Provider Log Correlation' ), 'README links AI Provider Log Correlation.' );
magick_ai_core_assert( false !== strpos( $readme, 'OpenClaw Execution Guidance' ), 'README links OpenClaw Execution Guidance.' );
magick_ai_core_assert( false !== strpos( $readme, 'Productized OpenClaw acceptance should be run from Magick AI Adapter' ), 'README points OpenClaw productized acceptance to Adapter.' );
magick_ai_core_assert( false !== strpos( $readme, 'Create Draft Governance Scenario' ), 'README links Create Draft Governance Scenario.' );
magick_ai_core_assert( false !== strpos( $readme, 'Set Post SEO Meta Governance Scenario' ), 'README links Set Post SEO Meta Governance Scenario.' );
magick_ai_core_assert( false !== strpos( $readme, 'Approve Comment Governance Scenario' ), 'README links Approve Comment Governance Scenario.' );
magick_ai_core_assert( false !== strpos( $readme, 'Taxonomy Terms Preview Governance Scenario' ), 'README links Taxonomy Terms Preview Governance Scenario.' );

$openclaw_execution_guidance = magick_ai_core_read( $root . '/docs/openclaw-execution-guidance.md' );
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
	) as $required
) {
	magick_ai_core_assert( false !== strpos( $openclaw_execution_guidance, $required ), 'OpenClaw execution guidance doc contains required text: ' . $required );
}

$consumer_readiness = magick_ai_core_read( $root . '/docs/core-0.4-consumer-readiness.md' );
foreach (
	array(
		'`magick-ai-abilities`: 0.4.0',
		'3d94af7',
		'2c28a27',
		'0f44ee0',
		'`magick-ai/propose-post-taxonomy-terms` -> `magick-ai/set-post-terms`',
		'capabilities discovery -> proposal -> approve/reject -> commit-preflight -> audit',
		'`commit_execution=false`',
		'Core does not execute final WordPress mutation',
		'composer test:all',
		'composer smoke:wp',
		'openclaw-governance-adapter.php --help',
		'No current finding requires `magick-ai-abilities`',
		'separate ADR',
	) as $required
) {
	magick_ai_core_assert( false !== strpos( $consumer_readiness, $required ), 'Core 0.4 consumer readiness doc contains required text: ' . $required );
}

$openclaw_adapter_readme = magick_ai_core_read( $root . '/examples/openclaw-governance-adapter/README.md' );
foreach (
	array(
		'not an MCP server',
		'GET /wp-json/magick-ai-core/v1/capabilities',
		'POST /wp-json/magick-ai-core/v1/proposals',
		'MAGICK_AI_CORE_CA_BUNDLE',
		'MAGICK_AI_CORE_INSECURE_SSL',
		'Do not use `MAGICK_AI_CORE_INSECURE_SSL=true` for production',
		'MAGICK_AI_CORE_APPLICATION_PASSWORD',
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
	magick_ai_core_assert( false !== strpos( $openclaw_adapter_readme, $required ), 'OpenClaw adapter README contains required text: ' . $required );
}

$create_draft_scenario = magick_ai_core_read( $root . '/docs/create-draft-governance-scenario.md' );
foreach (
	array(
		'`magick-ai/create-draft`',
		'write-risk ability with `requires_approval=true`',
		'`dry_run`, `commit`, and `idempotency_key` are governance controls',
		'`commit_execution=false`',
		'approve the proposal or execute the write.',
		'do not patch Core with aliases or fallback definitions',
	) as $required
) {
	magick_ai_core_assert( false !== strpos( $create_draft_scenario, $required ), 'Create draft scenario doc contains required text: ' . $required );
}

$seo_meta_scenario = magick_ai_core_read( $root . '/docs/set-post-seo-meta-governance-scenario.md' );
foreach (
	array(
		'`magick-ai/set-post-seo-meta`',
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
	magick_ai_core_assert( false !== strpos( $seo_meta_scenario, $required ), 'SEO metadata scenario doc contains required text: ' . $required );
}

$approve_comment_scenario = magick_ai_core_read( $root . '/docs/approve-comment-governance-scenario.md' );
foreach (
	array(
		'`magick-ai/approve-comment`',
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
	magick_ai_core_assert( false !== strpos( $approve_comment_scenario, $required ), 'Approve comment scenario doc contains required text: ' . $required );
}

$taxonomy_terms_scenario = magick_ai_core_read( $root . '/docs/taxonomy-terms-preview-governance-scenario.md' );
foreach (
	array(
		'`magick-ai/propose-post-taxonomy-terms`',
		'`magick-ai/set-post-terms`',
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
	magick_ai_core_assert( false !== strpos( $taxonomy_terms_scenario, $required ), 'Taxonomy terms scenario doc contains required text: ' . $required );
}

$openclaw_adapter = magick_ai_core_read( $root . '/examples/openclaw-governance-adapter/openclaw-governance-adapter.php' );
foreach (
	array(
		'capabilities',
		'create-draft-proposal',
		'create-seo-meta-proposal',
		'create-comment-approval-proposal',
		'create-taxonomy-terms-proposal',
		'create-proposal',
		'commit-preflight',
		'magick_ai_core_adapter_assert_create_draft_contract',
		'magick_ai_core_adapter_assert_seo_meta_contract',
		'magick_ai_core_adapter_assert_comment_approval_contract',
		'magick_ai_core_adapter_assert_taxonomy_terms_contract',
		'magick_ai_core_adapter_taxonomy_terms_payload',
		'magick_ai_core_adapter_seo_field_patch',
		'Required ability is not discoverable through Core',
		'input schema is missing governance control',
		'input schema is missing field/control',
		'$input[\'commit\']  = false',
		'field_patch',
		'target_action',
		'proposal_helper_ability_id',
		'magick-ai/propose-post-taxonomy-terms',
		'magick-ai/set-post-terms',
		'commit_execution',
		'MAGICK_AI_CORE_BASE_URL',
		'MAGICK_AI_CORE_APP_TOKEN',
		'MAGICK_AI_CORE_CA_BUNDLE',
		'MAGICK_AI_CORE_INSECURE_SSL',
		'CURLOPT_CAINFO',
		'CURLOPT_SSL_VERIFYPEER',
		'CURLOPT_SSL_VERIFYHOST',
		'magick_ai_core_adapter_is_local_url',
		'MAGICK_AI_CORE_APPLICATION_PASSWORD',
		'wp-json/magick-ai-core/v1',
		'openclaw-governance-adapter-example',
		'This adapter intentionally does not approve proposals.',
	) as $required
) {
	magick_ai_core_assert( false !== strpos( $openclaw_adapter, $required ), 'OpenClaw adapter script contains required text: ' . $required );
}
magick_ai_core_assert( false === strpos( $openclaw_adapter, 'proposals/{proposal_id}/approve' ), 'OpenClaw adapter script does not implement approval.' );

$app_key_repository = magick_ai_core_read( $root . '/includes/Security/App_Key_Repository.php' );
foreach (
	array(
		'magick_ai_core_app_keys',
		'secret_hash',
		'revoke_by_key_id',
		'revoked',
		'password_hash',
		'password_verify',
		'capabilities:read',
		'proposals:create',
		'commit:preflight',
		'mai_core.',
	) as $required
) {
	magick_ai_core_assert( false !== strpos( $app_key_repository, $required ), 'App key repository contains required text: ' . $required );
}
magick_ai_core_assert( false === strpos( $app_key_repository, "'secret' => " . '$secret' ), 'App key repository does not persist raw app secret in DB record.' );

$app_rate_limiter = magick_ai_core_read( $root . '/includes/Security/App_Rate_Limiter.php' );
magick_ai_core_assert( false !== strpos( $app_rate_limiter, 'magick_ai_core_app_rate_limits' ), 'App rate limiter stores fixed-window counters.' );
magick_ai_core_assert( false !== strpos( $app_rate_limiter, 'app_route_window' ), 'App rate limiter has unique app route window key.' );

$app_authenticator = magick_ai_core_read( $root . '/includes/Security/App_Authenticator.php' );
foreach (
	array(
		'magick_ai_core_app_auth_missing',
		'magick_ai_core_app_scope_forbidden',
		'magick_ai_core_app_rate_limited',
		'can_create_proposals',
		'can_commit_preflight',
		'app.scope_denied',
		'app.rate_limited',
		"mark_scope_decision( 'denied' )",
		"mark_scope_decision( 'rate_limited' )",
	) as $required
) {
	magick_ai_core_assert( false !== strpos( $app_authenticator, $required ), 'App authenticator contains required text: ' . $required );
}

$apps_controller = magick_ai_core_read( $root . '/includes/Rest/Apps_Controller.php' );
magick_ai_core_assert( false !== strpos( $apps_controller, "'/apps'" ), 'Apps REST route is registered.' );
magick_ai_core_assert( false !== strpos( $apps_controller, 'app.created' ), 'Apps REST route audits app creation.' );
magick_ai_core_assert( false !== strpos( $apps_controller, 'can_manage' ), 'Apps REST route remains admin-only.' );

$adr_001 = magick_ai_core_read( $root . '/docs/decisions/ADR-001-rebuild-core-as-governance-layer.md' );
$adr_002 = magick_ai_core_read( $root . '/docs/decisions/ADR-002-no-workflow-runtime-in-core.md' );
magick_ai_core_assert( false !== strpos( $adr_001, 'Create a new standalone `magick-ai-core` plugin' ), 'ADR-001 records rebuild decision.' );
magick_ai_core_assert( false !== strpos( $adr_002, '`magick-ai-core` must not implement a workflow runtime' ), 'ADR-002 bans workflow runtime ownership.' );

$ability_adapter = magick_ai_core_read( $root . '/includes/Capabilities/Ability_Registry_Adapter.php' );
magick_ai_core_assert( false !== strpos( $ability_adapter, 'magick_ai_abilities_get_registered' ), 'Ability intake prefers magick-ai-abilities public API.' );
magick_ai_core_assert( false !== strpos( $ability_adapter, 'wp_get_abilities' ), 'Ability intake falls back to WordPress Abilities API.' );
magick_ai_core_assert( false !== strpos( $ability_adapter, "'none'" ), 'Ability intake has missing-provider diagnostic state.' );
magick_ai_core_assert( false !== strpos( $ability_adapter, 'execution_guidance' ), 'Ability intake adds capability execution guidance.' );
magick_ai_core_assert( false !== strpos( $ability_adapter, "'governance_mode'" ), 'Ability intake exposes governance mode.' );
magick_ai_core_assert( false !== strpos( $ability_adapter, "'execution_surface'" ), 'Ability intake exposes execution surface.' );
magick_ai_core_assert( false !== strpos( $ability_adapter, "'core_proxy_execute'" ), 'Ability intake reports no Core proxy execution.' );
magick_ai_core_assert( false !== strpos( $ability_adapter, "'direct_read'" ), 'Ability intake guides direct read abilities.' );
magick_ai_core_assert( false !== strpos( $ability_adapter, "'proposal_required'" ), 'Ability intake guides proposal-required abilities.' );

$ability_intake = magick_ai_core_read( $root . '/docs/ability-intake-contract.md' );
magick_ai_core_assert( false !== strpos( $ability_intake, 'magick_ai_abilities_get_workflow_definitions()' ), 'Ability intake contract prefers runtime workflow definition discovery.' );
magick_ai_core_assert( false !== strpos( $ability_intake, 'agent-workflow-replay.json' ), 'Ability intake contract points to the shared replay fixture.' );
magick_ai_core_assert( false !== strpos( $ability_intake, 'does not copy the fixture into a workflow runtime' ), 'Ability intake contract keeps replay consumption out of runtime ownership.' );
magick_ai_core_assert( false !== strpos( $ability_intake, 'currently discoverable' ), 'Ability intake contract rejects unavailable proposal ability ids.' );
magick_ai_core_assert( false !== strpos( $ability_intake, 'Create Draft Governance Scenario' ), 'Ability intake contract points to the create-draft scenario.' );
magick_ai_core_assert( false !== strpos( $ability_intake, 'Set Post SEO Meta Governance Scenario' ), 'Ability intake contract points to the set-post-seo-meta scenario.' );
magick_ai_core_assert( false !== strpos( $ability_intake, 'Approve Comment Governance Scenario' ), 'Ability intake contract points to the approve-comment scenario.' );
magick_ai_core_assert( false !== strpos( $ability_intake, 'Taxonomy Terms Preview Governance Scenario' ), 'Ability intake contract points to the taxonomy terms preview scenario.' );

$testing_strategy = magick_ai_core_read( $root . '/docs/testing-strategy.md' );
magick_ai_core_assert( false !== strpos( $testing_strategy, 'agent-workflow-replay.json' ), 'Testing strategy records shared replay fixture smoke coverage.' );
magick_ai_core_assert( false !== strpos( $testing_strategy, 'primary `magick-ai/create-draft` governance scenario' ), 'Testing strategy records primary create-draft scenario coverage.' );
magick_ai_core_assert( false !== strpos( $testing_strategy, 'second `magick-ai/set-post-seo-meta` governance scenario' ), 'Testing strategy records second set-post-seo-meta scenario coverage.' );
magick_ai_core_assert( false !== strpos( $testing_strategy, 'third `magick-ai/approve-comment` governance scenario' ), 'Testing strategy records third approve-comment scenario coverage.' );
magick_ai_core_assert( false !== strpos( $testing_strategy, 'taxonomy terms preview governance scenario' ), 'Testing strategy records taxonomy terms preview scenario coverage.' );
magick_ai_core_assert( false !== strpos( $testing_strategy, 'proposal `audit_timeline`' ), 'Testing strategy records governance operability coverage.' );
magick_ai_core_assert( false !== strpos( $testing_strategy, 'commit-preflight `correlation_id`' ), 'Testing strategy records preflight correlation smoke coverage.' );
magick_ai_core_assert( false !== strpos( $testing_strategy, 'trusted Adapter approval coverage' ), 'Testing strategy records trusted Adapter approval smoke coverage.' );

$development_workflow = magick_ai_core_read( $root . '/docs/development-workflow.md' );
magick_ai_core_assert( false !== strpos( $development_workflow, 'does not depend on the abandoned legacy Magick AI' ), 'Development workflow rejects the abandoned legacy Magick AI dependency.' );
magick_ai_core_assert( false === strpos( $development_workflow, 'magick-ai-root' ), 'Development workflow must not depend on magick-ai-root.' );

$smoke_wp_sh = magick_ai_core_read( $root . '/tests/smoke-wp.sh' );
magick_ai_core_assert( false !== strpos( $smoke_wp_sh, 'WP_CLI' ), 'WordPress smoke shell uses WP-CLI directly.' );
magick_ai_core_assert( false === strpos( $smoke_wp_sh, 'magick-ai-root' ), 'WordPress smoke shell must not depend on magick-ai-root.' );

$smoke_wp = magick_ai_core_read( $root . '/tests/smoke-wp.php' );
magick_ai_core_assert( false !== strpos( $smoke_wp, 'MAGICK_AI_ABILITIES_PATH' ), 'WordPress smoke can locate the shared magick-ai-abilities repository explicitly.' );
magick_ai_core_assert( false !== strpos( $smoke_wp, 'agent-workflow-replay.json' ), 'WordPress smoke consumes the shared replay fixture.' );
magick_ai_core_assert( false !== strpos( $smoke_wp, 'preferred bundle is discoverable by Core' ), 'WordPress smoke validates preferred bundle discovery.' );
magick_ai_core_assert( false !== strpos( $smoke_wp, 'disallowed default ability requires approval in Core' ), 'WordPress smoke validates write-like defaults stay approval-gated.' );
magick_ai_core_assert( false !== strpos( $smoke_wp, 'content/draft-preview' ), 'WordPress smoke rejects planning labels as proposal targets.' );
magick_ai_core_assert( false !== strpos( $smoke_wp, 'magick-ai/create-draft' ), 'WordPress smoke validates draft proposal governance.' );
magick_ai_core_assert( false !== strpos( $smoke_wp, 'magick_ai_core_smoke_assert_create_draft_contract' ), 'WordPress smoke has a dedicated create-draft contract check.' );
magick_ai_core_assert( false !== strpos( $smoke_wp, 'create-draft input schema exposes governance control' ), 'WordPress smoke validates create-draft schema controls.' );
magick_ai_core_assert( false !== strpos( $smoke_wp, 'preflight returns the dry-run proposal input without committing' ), 'WordPress smoke validates preflight keeps dry-run input without commit execution.' );
magick_ai_core_assert( false !== strpos( $smoke_wp, 'magick-ai/set-post-seo-meta' ), 'WordPress smoke validates SEO proposal governance.' );
magick_ai_core_assert( false !== strpos( $smoke_wp, 'magick_ai_core_smoke_assert_seo_meta_contract' ), 'WordPress smoke has a dedicated set-post-seo-meta contract check.' );
magick_ai_core_assert( false !== strpos( $smoke_wp, 'set-post-seo-meta input schema exposes field/control' ), 'WordPress smoke validates set-post-seo-meta field controls.' );
magick_ai_core_assert( false !== strpos( $smoke_wp, 'field_patch' ), 'WordPress smoke validates set-post-seo-meta field patch preview.' );
magick_ai_core_assert( false !== strpos( $smoke_wp, 'magick-ai/approve-comment' ), 'WordPress smoke validates comment moderation proposal governance.' );
magick_ai_core_assert( false !== strpos( $smoke_wp, 'magick_ai_core_smoke_assert_comment_approval_contract' ), 'WordPress smoke has a dedicated approve-comment contract check.' );
magick_ai_core_assert( false !== strpos( $smoke_wp, 'magick_ai_core_smoke_create_pending_comment' ), 'WordPress smoke creates a pending comment for approve-comment governance.' );
magick_ai_core_assert( false !== strpos( $smoke_wp, 'approve-comment input schema exposes governance control' ), 'WordPress smoke validates approve-comment governance controls.' );
magick_ai_core_assert( false !== strpos( $smoke_wp, 'target_action' ), 'WordPress smoke validates approve-comment target action preview.' );
magick_ai_core_assert( false !== strpos( $smoke_wp, 'does not mutate comment status' ), 'WordPress smoke validates approve-comment preflight does not mutate comments.' );
magick_ai_core_assert( false !== strpos( $smoke_wp, 'magick-ai/propose-post-taxonomy-terms' ), 'WordPress smoke validates taxonomy proposal helper discovery.' );
magick_ai_core_assert( false !== strpos( $smoke_wp, 'magick-ai/set-post-terms' ), 'WordPress smoke validates taxonomy set-post-terms governance.' );
magick_ai_core_assert( false !== strpos( $smoke_wp, 'magick_ai_core_smoke_assert_taxonomy_terms_contract' ), 'WordPress smoke has a dedicated taxonomy terms contract check.' );
magick_ai_core_assert( false !== strpos( $smoke_wp, 'magick_ai_core_smoke_run_taxonomy_terms_preview' ), 'WordPress smoke runs taxonomy terms preview helper through WordPress Abilities API.' );
magick_ai_core_assert( false !== strpos( $smoke_wp, 'taxonomy terms governance loop does not mutate post terms' ), 'WordPress smoke validates taxonomy terms preflight does not mutate post terms.' );
magick_ai_core_assert( false !== strpos( $smoke_wp, 'taxonomy terms audit correlates commit preflight with set-post-terms' ), 'WordPress smoke validates taxonomy terms audit correlation.' );
magick_ai_core_assert( false !== strpos( $smoke_wp, 'proposal detail includes audit timeline' ), 'WordPress smoke validates proposal audit timeline.' );
magick_ai_core_assert( false !== strpos( $smoke_wp, 'commit preflight returns correlation id' ), 'WordPress smoke validates preflight correlation id.' );
magick_ai_core_assert( false !== strpos( $smoke_wp, 'trusted Adapter app approves proposal with approval scope' ), 'WordPress smoke validates trusted Adapter approval scope.' );
magick_ai_core_assert( false !== strpos( $smoke_wp, 'trusted Adapter execution handoff keeps Core final execution disabled' ), 'WordPress smoke validates trusted Adapter execution handoff.' );
magick_ai_core_assert( false !== strpos( $smoke_wp, 'trusted Adapter approval audit stores app attribution and approve scope' ), 'WordPress smoke validates trusted Adapter approval audit attribution.' );
magick_ai_core_assert( false !== strpos( $smoke_wp, 'audit endpoint filters by ability id' ), 'WordPress smoke validates ability audit filtering.' );
magick_ai_core_assert( false !== strpos( $smoke_wp, 'audit endpoint filters by app id' ), 'WordPress smoke validates app audit filtering.' );
magick_ai_core_assert( false !== strpos( $smoke_wp, 'scope decision denied' ), 'WordPress smoke validates denied scope decision attribution.' );
magick_ai_core_assert( false !== strpos( $smoke_wp, 'scope decision rate_limited' ), 'WordPress smoke validates rate-limit scope decision attribution.' );
magick_ai_core_assert( false !== strpos( $smoke_wp, 'app-authenticated proposal stores app attribution' ), 'WordPress smoke validates app proposal attribution.' );
magick_ai_core_assert( false !== strpos( $smoke_wp, 'app-authenticated audit read is denied without audit scope' ), 'WordPress smoke validates denied app audit scope.' );
magick_ai_core_assert( false !== strpos( $smoke_wp, 'app rate limit returns 429 after fixed window is exhausted' ), 'WordPress smoke validates app rate limiting.' );
magick_ai_core_assert( false !== strpos( $smoke_wp, 'revoked app key returns 401' ), 'WordPress smoke validates revoked app key denial.' );
magick_ai_core_assert( false !== strpos( $smoke_wp, 'magick-ai/build-content-inventory-fix-plan' ), 'WordPress smoke validates content plan-to-proposal intake.' );
magick_ai_core_assert( false !== strpos( $smoke_wp, 'magick-ai/build-test-content-cleanup-plan' ), 'WordPress smoke validates cleanup plan-to-proposal intake.' );
magick_ai_core_assert( false !== strpos( $smoke_wp, 'magick-ai/build-media-inventory-fix-plan' ), 'WordPress smoke validates media plan-to-proposal intake.' );
magick_ai_core_assert( false !== strpos( $smoke_wp, 'media delete candidates do not enter executable proposals by default' ), 'WordPress smoke validates default destructive media delete guard.' );
magick_ai_core_assert( false !== strpos( $smoke_wp, 'requires-input proposal cannot enter committable state' ), 'WordPress smoke validates requires_input preflight blocking.' );

$capabilities_controller = magick_ai_core_read( $root . '/includes/Rest/Capabilities_Controller.php' );
magick_ai_core_assert( false !== strpos( $capabilities_controller, "'/capabilities'" ), 'Capabilities REST route is registered.' );
magick_ai_core_assert( false !== strpos( $capabilities_controller, 'capabilities.listed' ), 'Capabilities route records audit event.' );

$proposals_controller = magick_ai_core_read( $root . '/includes/Rest/Proposals_Controller.php' );
magick_ai_core_assert( false !== strpos( $proposals_controller, "'/proposals'" ), 'Proposals REST route is registered.' );
magick_ai_core_assert( false !== strpos( $proposals_controller, "'/proposals/from-plan'" ), 'Plan-to-proposal REST route is registered.' );
magick_ai_core_assert( false !== strpos( $proposals_controller, 'create_proposals_from_plan' ), 'Plan-to-proposal REST callback is registered.' );
magick_ai_core_assert( false !== strpos( $proposals_controller, 'get_proposal' ), 'Proposal detail REST callback is registered.' );
magick_ai_core_assert( false !== strpos( $proposals_controller, "/approve'" ), 'Proposal approve REST route is registered.' );
magick_ai_core_assert( false !== strpos( $proposals_controller, "/reject'" ), 'Proposal reject REST route is registered.' );
magick_ai_core_assert( false !== strpos( $proposals_controller, "/commit-preflight'" ), 'Proposal commit preflight REST route is registered.' );
magick_ai_core_assert( false !== strpos( $proposals_controller, "'ability_id'" ), 'Proposals route requires ability_id.' );
magick_ai_core_assert( false !== strpos( $proposals_controller, 'audit_timeline' ), 'Proposal detail REST route returns audit timeline.' );

$proposal_service = magick_ai_core_read( $root . '/includes/Governance/Proposal_Service.php' );
magick_ai_core_assert( false !== strpos( $proposal_service, 'proposal.created' ), 'Proposal service records proposal.created audit event.' );
magick_ai_core_assert( false !== strpos( $proposal_service, 'Ability_Registry_Adapter' ), 'Proposal service validates target abilities against ability intake.' );
magick_ai_core_assert( false !== strpos( $proposal_service, 'magick_ai_core_ability_not_available' ), 'Proposal service rejects unavailable target abilities.' );
magick_ai_core_assert( false !== strpos( $proposal_service, 'proposal.approved' ), 'Proposal service records proposal.approved audit event.' );
magick_ai_core_assert( false !== strpos( $proposal_service, 'proposal.rejected' ), 'Proposal service records proposal.rejected audit event.' );
magick_ai_core_assert( false !== strpos( $proposal_service, 'proposal.listed' ), 'Proposal service records proposal.listed audit event.' );
magick_ai_core_assert( false !== strpos( $proposal_service, 'proposal.viewed' ), 'Proposal service records proposal.viewed audit event.' );
magick_ai_core_assert( false !== strpos( $proposal_service, 'audit_timeline' ), 'Proposal service exposes proposal audit timeline.' );
magick_ai_core_assert( false !== strpos( $proposal_service, "'pending'" ), 'Proposal service only transitions pending proposals.' );
magick_ai_core_assert( false !== strpos( $proposal_service, 'magick_ai_core_ability_not_available' ), 'Proposal service rejects unavailable target abilities.' );
magick_ai_core_assert( false !== strpos( $proposal_service, '$this->abilities->find' ), 'Proposal service validates against ability intake.' );
magick_ai_core_assert( false === strpos( $proposal_service, 'confirm_token' ), 'Proposal service does not use confirm_token.' );
magick_ai_core_assert( false === strpos( $proposal_service, 'write_confirmed' ), 'Proposal service does not use write_confirmed.' );

$commit_preflight_service = magick_ai_core_read( $root . '/includes/Governance/Commit_Preflight_Service.php' );
magick_ai_core_assert( false !== strpos( $commit_preflight_service, 'commit.preflighted' ), 'Commit preflight records commit.preflighted audit event.' );
magick_ai_core_assert( false !== strpos( $commit_preflight_service, 'approval_commit_authorized' ), 'Commit preflight returns approval context.' );
magick_ai_core_assert( false !== strpos( $commit_preflight_service, 'commit_execution' ), 'Commit preflight explicitly reports no commit execution.' );
magick_ai_core_assert( false !== strpos( $commit_preflight_service, 'correlation_id' ), 'Commit preflight returns and audits correlation id.' );
magick_ai_core_assert( false !== strpos( $commit_preflight_service, 'new_correlation_id' ), 'Commit preflight generates a correlation id.' );
magick_ai_core_assert( false !== strpos( $commit_preflight_service, 'proposal_item_preflight' ), 'Commit preflight evaluates proposal item readiness.' );
magick_ai_core_assert( false !== strpos( $commit_preflight_service, 'magick_ai_core_proposal_items_blocked' ), 'Commit preflight blocks incomplete proposal items.' );
magick_ai_core_assert( false !== strpos( $commit_preflight_service, 'execution_handoff' ), 'Commit preflight returns adapter execution handoff.' );
magick_ai_core_assert( false !== strpos( $commit_preflight_service, 'adapter_after_core_preflight' ), 'Commit preflight handoff points execution to Adapter.' );
magick_ai_core_assert( false !== strpos( $commit_preflight_service, 'confirm_token' ), 'Commit preflight rejects confirm_token input.' );
magick_ai_core_assert( false !== strpos( $commit_preflight_service, 'write_confirmed' ), 'Commit preflight rejects write_confirmed input.' );

$plan_proposal_service = magick_ai_core_read( $root . '/includes/Governance/Plan_Proposal_Service.php' );
foreach (
	array(
		'Plan_Proposal_Service',
		'magick-ai/build-content-inventory-fix-plan',
		'magick-ai/build-test-content-cleanup-plan',
		'magick-ai/build-media-inventory-fix-plan',
		'proposal.plan_ingested',
		'magick-ai/delete-media-permanently',
		'destructive_media_delete_not_explicitly_included',
		'proposal_ready',
		'needs_input',
		'preflight_blockers',
		'skipped_destructive_candidates',
		'manual_review',
		'commit_execution',
		'dry_run',
	) as $required
) {
	magick_ai_core_assert( false !== strpos( $plan_proposal_service, $required ), 'Plan-to-proposal service contains required text: ' . $required );
}

$audit_repository = magick_ai_core_read( $root . '/includes/Audit/Audit_Log_Repository.php' );
magick_ai_core_assert( false !== strpos( $audit_repository, 'sanitize_text_field( $event_name )' ), 'Audit repository preserves dotted event names.' );
magick_ai_core_assert( false !== strpos( $audit_repository, 'list_filtered' ), 'Audit repository supports filtered event lists.' );
magick_ai_core_assert( false !== strpos( $audit_repository, 'proposal_id = %s' ), 'Audit repository filters by proposal id safely.' );
magick_ai_core_assert( false !== strpos( $audit_repository, 'event_name = %s' ), 'Audit repository filters by event name safely.' );
magick_ai_core_assert( false !== strpos( $audit_repository, 'ability_id' ), 'Audit repository filters by ability id metadata.' );
magick_ai_core_assert( false !== strpos( $audit_repository, 'app_id' ), 'Audit repository filters by app id metadata.' );
magick_ai_core_assert( false !== strpos( $audit_repository, 'key_id' ), 'Audit repository filters by key id metadata.' );
magick_ai_core_assert( false !== strpos( $audit_repository, 'caller_type' ), 'Audit repository filters by caller type metadata.' );
magick_ai_core_assert( false !== strpos( $audit_repository, 'correlation_id' ), 'Audit repository filters by correlation id metadata.' );
magick_ai_core_assert( false !== strpos( $audit_repository, 'metadata_filter_needle' ), 'Audit repository uses JSON-safe metadata filter needles.' );

$audit_controller = magick_ai_core_read( $root . '/includes/Rest/Audit_Controller.php' );
magick_ai_core_assert( false !== strpos( $audit_controller, "'/audit'" ), 'Audit REST route is registered.' );
magick_ai_core_assert( false !== strpos( $audit_controller, 'ability_id' ), 'Audit REST route accepts ability id filter.' );
magick_ai_core_assert( false !== strpos( $audit_controller, 'app_id' ), 'Audit REST route accepts app id filter.' );
magick_ai_core_assert( false !== strpos( $audit_controller, 'key_id' ), 'Audit REST route accepts key id filter.' );
magick_ai_core_assert( false !== strpos( $audit_controller, 'caller_type' ), 'Audit REST route accepts caller type filter.' );
magick_ai_core_assert( false !== strpos( $audit_controller, 'correlation_id' ), 'Audit REST route accepts correlation id filter.' );

$request_context = magick_ai_core_read( $root . '/includes/Security/Request_Context.php' );
magick_ai_core_assert( false !== strpos( $request_context, 'scope_decision' ), 'Request context stores scope decision.' );
magick_ai_core_assert( false !== strpos( $request_context, 'mark_scope_decision' ), 'Request context can update scope decision for denials.' );

$admin_page = magick_ai_core_read( $root . '/includes/Admin/Admin_Page.php' );
magick_ai_core_assert( false !== strpos( $admin_page, 'admin_post_magick_ai_core_approve_proposal' ), 'Admin page registers approve handler.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'admin_post_magick_ai_core_reject_proposal' ), 'Admin page registers reject handler.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'admin_post_magick_ai_core_create_app_key' ), 'Admin page registers app-key creation handler.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'admin_post_magick_ai_core_revoke_app_key' ), 'Admin page registers app-key revocation handler.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'check_admin_referer' ), 'Admin proposal actions enforce nonce.' );
magick_ai_core_assert( false !== strpos( $admin_page, "current_user_can( 'manage_options' )" ), 'Admin proposal actions enforce capability.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'Core App Keys' ), 'Admin page exposes Core app-key management section.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'Advanced: Core App Keys' ), 'Admin page folds app-key management into an advanced disclosure.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'Recent Governance Audit' ), 'Admin page exposes recent governance audit section.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'Advanced audit filters' ), 'Admin page folds detailed audit filters into an advanced disclosure.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'audit_filters_from_request' ), 'Admin page reads governance audit filters.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'audit_proposal_id' ), 'Admin page exposes proposal audit filter.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'audit_correlation_id' ), 'Admin page exposes correlation audit filter.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'AI Request Logs remain separate' ), 'Admin page separates Core audit from AI Request Logs.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'Productized OpenClaw setup belongs in Magick AI Adapter' ), 'Admin page avoids presenting Core as the OpenClaw product entry point.' );
magick_ai_core_assert( false === strpos( $admin_page, 'Environment template' ), 'Admin default page no longer exposes an env template.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'Create Core App Key' ), 'Admin page labels key creation as Core credential management.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'Adapter Client' ), 'Admin page defaults app label to a generic adapter client.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'product_adapter' ), 'Admin page defaults caller type to product_adapter.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'core_env_text' ), 'Admin page centralizes minimal Core env generation.' );
magick_ai_core_assert( false === strpos( $admin_page, 'Direct Core Handoff' ), 'Admin page no longer exposes Core handoff guidance.' );
magick_ai_core_assert( false === strpos( $admin_page, 'Agent rules' ), 'Admin page does not host external agent rules.' );
magick_ai_core_assert( false === strpos( $admin_page, 'MAGICK_AI_ADAPTER_BASE_URL' ), 'Admin page does not export Adapter base URL guidance.' );
magick_ai_core_assert( false === strpos( $admin_page, 'MAGICK_AI_CORE_INSECURE_SSL=true' ), 'Admin page does not export local TLS test settings.' );
magick_ai_core_assert( false === strpos( $admin_page, 'MAGICK_AI_CORE_CA_BUNDLE' ), 'Admin page does not export local CA bundle settings.' );
magick_ai_core_assert( false === strpos( $admin_page, 'include_local_tls' ), 'Admin page does not expose a local TLS export checkbox.' );
magick_ai_core_assert( false === strpos( $admin_page, 'is_local_base_url' ), 'Admin page no longer computes local TLS defaults.' );
magick_ai_core_assert( false === strpos( $admin_page, 'openclaw_handoff_text' ), 'Admin page does not generate OpenClaw handoff text.' );
magick_ai_core_assert( false === strpos( $admin_page, 'openclaw_env_text' ), 'Admin page does not use OpenClaw-named env helpers.' );
magick_ai_core_assert( false === strpos( $admin_page, 'create-draft-proposal' ), 'Admin page no longer embeds adapter scenario commands.' );
magick_ai_core_assert( false === strpos( $admin_page, 'create-taxonomy-terms-proposal' ), 'Admin page no longer embeds taxonomy adapter commands.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'MAGICK_AI_CORE_BASE_URL' ), 'Admin page shows base URL env value.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'MAGICK_AI_CORE_APP_TOKEN' ), 'Admin page shows app token env value.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'render_created_app_key' ), 'Admin page renders one-time app key result.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'nocache_headers' ), 'Admin app-key result prevents caching the one-time token.' );
magick_ai_core_assert( false === strpos( $admin_page, 'wp-admin/admin-header.php' ), 'Admin app-key result avoids admin header inside admin-post context.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'shown only once and is not stored in raw form' ), 'Admin page warns that app token is one-time only.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'default_scopes' ), 'Admin page defaults to scoped external adapter access.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'App_Key_Repository::DEFAULT_RATE_LIMIT' ), 'Admin page exposes bounded rate policy inputs.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'array_map' ) && false !== strpos( $admin_page, 'sanitize_text_field' ), 'Admin app-key scopes are sanitized before repository validation.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'app.revoked' ), 'Admin page audits app-key revocation.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'button-link-delete' ), 'Admin page exposes a key disable action.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'Review Context' ), 'Admin proposal detail renders summary-first review context.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'Raw proposal payload' ), 'Admin proposal detail folds raw JSON payload behind a disclosure.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'Audit Timeline' ), 'Admin proposal detail renders audit timeline.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'scope_decision' ), 'Admin proposal detail shows scope decision attribution.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'correlation_id' ), 'Admin proposal detail shows correlation id attribution.' );

$forbidden_runtime_terms = array(
	'Agent Gateway',
	'MCP runtime',
	'Prompt Center',
	'batch_article_governance',
	'batch_media_optimize',
	'workflow/content_tag_completion',
	'workflow/comment-moderation',
);

foreach ( magick_ai_core_project_files( $root ) as $file ) {
	$relative = str_replace( $root . '/', '', $file );
	$contents = magick_ai_core_read( $file );

	foreach ( $forbidden_runtime_terms as $term ) {
		if ( ! preg_match( '/^(includes|magick-ai-core\.php)/', $relative ) ) {
			continue;
		}

		magick_ai_core_assert( false === strpos( $contents, $term ), $relative . ' must not reintroduce old Core runtime term: ' . $term );
	}
}

echo "Static contracts: ok\n";
