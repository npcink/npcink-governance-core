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
		'Current Stage Governance Reliability',
			'Approval Policy Evaluator Standard',
			'Ability Recipe Orchestration Contract',
			'Article Writing Workflow Contract',
			'Cloud Bulk Article Run Contract',
			'workflow/task queues, batch execution consoles',
			'Review Queue, pending proposal queue',
			'Those terms do not permit workflow/task queue ownership',
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
		'Requires at least: 7.0',
		'Tested up to: 7.0',
		'Requires PHP: 8.0',
		'License: GPLv2 or later',
		'== Description ==',
	) as $required
) {
	magick_ai_core_assert( false !== strpos( $wp_readme, $required ), 'WordPress readme contains required phrase: ' . $required );
}

$platform_baseline = magick_ai_core_read( $root . '/docs/platform-baseline.md' );
foreach ( array( 'WordPress minimum: `7.0`', 'PHP minimum: `8.0`', '`magick-ai-cloud-addon`' ) as $required ) {
	magick_ai_core_assert( false !== strpos( $platform_baseline, $required ), 'Platform baseline documents required phrase: ' . $required );
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
foreach ( array( '`Magick AI`', '`Core`', '`Adapter`', '`Abilities`', '`Cloud Addon`' ) as $required ) {
	magick_ai_core_assert( false !== strpos( $admin_menu_standard, $required ), 'Admin menu standard documents required entry: ' . $required );
}

$admin_page = magick_ai_core_read( $root . '/includes/Admin/Admin_Page.php' );
foreach ( array( 'PARENT_MENU_SLUG', 'add_menu_page', 'add_submenu_page', 'Core', 'admin.php' ) as $required ) {
	magick_ai_core_assert( false !== strpos( $admin_page, $required ), 'Admin page implements shared menu contract: ' . $required );
}
magick_ai_core_assert( false !== strpos( $admin_page, "__( 'Core', 'magick-ai-core' ),\n\t\t\tself::MENU_CAPABILITY" ), 'Admin submenu title is Core.' );
magick_ai_core_assert( false !== strpos( $admin_page, "'magick-ai-cloud-addon'" ), 'Admin overview links to the canonical Cloud Addon slug.' );
magick_ai_core_assert( false !== strpos( $admin_page, "__( 'Cloud Addon', 'magick-ai-core' )" ), 'Admin overview labels the Cloud Addon surface.' );

$media_settings = magick_ai_core_read( $root . '/includes/Media/Media_Derivative_Settings.php' );
foreach (
	array(
		'Media_Derivative_Settings',
		'magick_ai_core_media_derivative_settings',
		"'target_format'           => 'webp'",
		"'max_width'               => 1600",
		"'quality'                 => 82",
		"'watermark_enabled'       => false",
		"'watermark_attachment_id' => 0",
		"'use_cloud_when_available' => true",
		'preferred_format',
		'target_max_width',
		'watermark',
		'policy_owner',
		'final_write_owner',
	) as $required
) {
	magick_ai_core_assert( false !== strpos( $media_settings, $required ), 'Media derivative settings implement local policy contract: ' . $required );
}
foreach ( array( 'update_attached_file', 'wp_update_attachment_metadata', 'wp_update_post', 'update_post_meta' ) as $forbidden ) {
	magick_ai_core_assert( false === strpos( $media_settings, $forbidden ), 'Media derivative settings do not perform WordPress media writes: ' . $forbidden );
}
magick_ai_core_assert( false !== strpos( $main_plugin, 'magick_ai_core_get_media_derivative_settings' ), 'Core exposes a media derivative settings helper for local surfaces.' );
magick_ai_core_assert( false !== strpos( $main_plugin, 'magick_ai_core_build_media_derivative_ability_input' ), 'Core exposes a media derivative ability-input helper for one-run handoffs.' );
magick_ai_core_assert( false !== strpos( $admin_page, "'media-policy'" ) && false !== strpos( $admin_page, 'render_media_policy_settings' ), 'Admin page exposes a lightweight local Media Policy tab.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'Core stores the local site policy for optimized media derivatives' ), 'Media Policy tab describes Core as local policy owner.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'Cloud remains an optional runtime' ), 'Media Policy tab keeps Cloud as optional runtime detail.' );
foreach (
	array(
		$root . '/README.md',
		$root . '/readme.txt',
		$root . '/docs/admin-menu-standard.md',
		$root . '/docs/app-auth-scope-policy.md',
		$root . '/docs/core-governance-operability.md',
		$root . '/docs/next-stage-plan.md',
		$root . '/docs/security-model.md',
		$root . '/includes/Admin/Admin_Page.php',
	) as $project_file
) {
	$project_text = magick_ai_core_read( $project_file );
	magick_ai_core_assert( false === strpos( $project_text, 'Magick AI -> Governance' ), 'Project docs no longer use old admin path in ' . $project_file );
	magick_ai_core_assert( false === strpos( $project_text, 'Magick AI > Governance' ), 'Project docs no longer use old admin path in ' . $project_file );
}

$governance = magick_ai_core_read( $root . '/docs/governance-contract.md' );
magick_ai_core_assert( false !== strpos( $governance, 'proposal.created' ), 'Governance contract records proposal.created event.' );
magick_ai_core_assert( false !== strpos( $governance, 'proposal.policy_evaluated' ), 'Governance contract records proposal.policy_evaluated event.' );
magick_ai_core_assert( false !== strpos( $governance, 'proposal.auto_approved' ), 'Governance contract records proposal.auto_approved event.' );
magick_ai_core_assert( false !== strpos( $governance, 'proposal.approved' ), 'Governance contract records proposal.approved event.' );
magick_ai_core_assert( false !== strpos( $governance, 'proposal.rejected' ), 'Governance contract records proposal.rejected event.' );
magick_ai_core_assert( false !== strpos( $governance, 'proposal.expired' ), 'Governance contract records proposal.expired event.' );
magick_ai_core_assert( false !== strpos( $governance, 'proposal.archived' ), 'Governance contract records proposal.archived event.' );
magick_ai_core_assert( false !== strpos( $governance, 'proposal.reopened' ), 'Governance contract records proposal.reopened event.' );
magick_ai_core_assert( false !== strpos( $governance, 'proposal.viewed' ), 'Governance contract records proposal.viewed event.' );
magick_ai_core_assert( false !== strpos( $governance, 'proposal.listed' ), 'Governance contract records proposal.listed event.' );
magick_ai_core_assert( false !== strpos( $governance, 'commit.preflighted' ), 'Governance contract records commit.preflighted event.' );
magick_ai_core_assert( false !== strpos( $governance, 'currently discoverable ability id' ), 'Governance contract requires real discoverable proposal ability ids.' );
magick_ai_core_assert( false !== strpos( $governance, 'must not reintroduce' ), 'Governance contract rejects legacy confirmation parameters.' );

$approval_policy_standard = magick_ai_core_read( $root . '/docs/approval-policy-evaluator-standard.md' );
foreach (
	array(
		'Status: active planning standard',
		'magick_ai_core_approval_policy_mode',
		'dry_run_guarded',
		'local_guarded',
		'manual_required',
		'auto_approved',
		'blocked',
		'guarded',
		'trusted_local',
		'break_glass',
		'caller.core_policy',
		'proposal.policy_evaluated',
		'proposal.auto_approved',
		'build-test-content-cleanup-plan',
		'plan_to_proposal_batch',
		'magick-ai/trash-post',
		'trusted test-content',
		'include_unattached_test_media',
		'magick-ai/delete-media-permanently',
		'magick-ai/set-post-terms',
		'hourly and daily auto-approval quotas',
		'Do not widen real auto approval beyond cleanup until all of these remain true',
		'Adapter still executes only after approved status and successful preflight',
		'does not add a rules DSL',
		'composer smoke:wp',
	) as $required
) {
	magick_ai_core_assert( false !== strpos( $approval_policy_standard, $required ), 'Approval policy standard contains required text: ' . $required );
}

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
		'magick_ai_core_proposal_insert_failed',
		'magick_ai_core_proposal_audit_failed',
		'magick_ai_core_policy_decision_audit_failed',
		'magick_ai_core_proposal_decision_audit_failed',
		'magick_ai_core_legacy_confirmation_rejected',
		'magick_ai_core_proposal_expired',
		'expired',
		'archived',
		'audit_timeline',
		'correlation_id',
		'policy_decision',
		'policy_profile',
		'policy_reasons',
		'approved_input_hash',
		'approved_preview_hash',
		'policy_version',
		'$outputs.<prior_action_id>.<field>',
		'ordered batch proposal',
		'scope_decision',
		'app_id',
		'key_id',
		'caller_type',
		'event_name',
		'core.commit.preflight',
		'status=warning',
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
		'local_guarded',
		'trusted test cleanup trash-post batches',
		'proposal.policy_evaluated',
		'proposal is not left approved',
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
		'Governance Audit',
		'Core App Keys',
		'AI Request Logs remain owned by the',
		'`proposal_id` or `correlation_id`',
		'correlation_id',
		'commit_execution=false',
		'core_proxy_execute=false',
		'Do not add these as part of Core governance operability',
		'final commit execution',
		'magick_ai_observability_event',
		'not an audit replacement',
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
magick_ai_core_assert( false !== strpos( $next_stage_plan, 'Final Commit Execution Boundary' ), 'Next stage plan includes final commit execution boundary phase.' );
magick_ai_core_assert( false !== strpos( $next_stage_plan, 'ADR-003' ), 'Next stage plan links the current-stage final execution ADR.' );
magick_ai_core_assert( false === strpos( $next_stage_plan, 'revocation UI, and expiry automation' ), 'Next stage plan no longer lists implemented revocation UI as missing.' );
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
magick_ai_core_assert( false !== strpos( $readme, 'ADR-003: Keep Final Execution Outside Core For The Current Stage' ), 'README links ADR-003.' );
magick_ai_core_assert( false !== strpos( $readme, 'Productized OpenClaw acceptance should be run from Magick AI Adapter' ), 'README points OpenClaw productized acceptance to Adapter.' );
magick_ai_core_assert( false !== strpos( $readme, 'Create Draft Governance Scenario' ), 'README links Create Draft Governance Scenario.' );
magick_ai_core_assert( false !== strpos( $readme, 'Set Post SEO Meta Governance Scenario' ), 'README links Set Post SEO Meta Governance Scenario.' );
magick_ai_core_assert( false !== strpos( $readme, 'Approve Comment Governance Scenario' ), 'README links Approve Comment Governance Scenario.' );
magick_ai_core_assert( false !== strpos( $readme, 'Taxonomy Terms Preview Governance Scenario' ), 'README links Taxonomy Terms Preview Governance Scenario.' );
magick_ai_core_assert( false !== strpos( $readme, 'Article writing is now treated as local Ability recipe orchestration' ), 'README documents local Ability recipe orchestration boundary.' );
magick_ai_core_assert( false !== strpos( $readme, 'Cloud must not generate article drafts' ), 'README prohibits Cloud writing generation.' );

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
		'OFFSET %d',
		'latest_last_used_at',
		'capabilities:read',
		'proposals:create',
		'commit:preflight',
		'mai_core.',
		'magick_ai_core_app_insert_failed',
		'magick_ai_core_app_secret_hash_failed',
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
magick_ai_core_assert( false !== strpos( $apps_controller, 'magick_ai_core_app_audit_failed' ), 'Apps REST route fails app creation when audit cannot be written.' );

$adr_001 = magick_ai_core_read( $root . '/docs/decisions/ADR-001-rebuild-core-as-governance-layer.md' );
$adr_002 = magick_ai_core_read( $root . '/docs/decisions/ADR-002-no-workflow-runtime-in-core.md' );
$adr_003 = magick_ai_core_read( $root . '/docs/decisions/ADR-003-keep-final-execution-outside-core.md' );
magick_ai_core_assert( false !== strpos( $adr_001, 'Create a new standalone `magick-ai-core` plugin' ), 'ADR-001 records rebuild decision.' );
magick_ai_core_assert( false !== strpos( $adr_002, '`magick-ai-core` must not implement a workflow runtime' ), 'ADR-002 bans workflow runtime ownership.' );
magick_ai_core_assert( false !== strpos( $adr_003, 'Core remains governance-only' ), 'ADR-003 keeps Core governance-only for the current stage.' );
magick_ai_core_assert( false !== strpos( $adr_003, 'adapter_after_core_preflight' ), 'ADR-003 keeps final execution in Adapter/product plugins.' );
magick_ai_core_assert( false !== strpos( $adr_003, 'no Core `/execute`, `/proxy-execute`' ) && false !== strpos( $adr_003, 'commit route' ), 'ADR-003 blocks accidental Core execution routes.' );

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
magick_ai_core_assert( false !== strpos( $ability_adapter, "'read_policy'" ), 'Ability intake exposes read policy.' );
magick_ai_core_assert( false !== strpos( $ability_adapter, "'sensitivity'" ), 'Ability intake exposes read sensitivity.' );
magick_ai_core_assert( false !== strpos( $ability_adapter, "'redaction_required'" ), 'Ability intake exposes read redaction requirement.' );
magick_ai_core_assert( false !== strpos( $ability_adapter, 'infer_read_sensitivity' ), 'Ability intake infers read sensitivity when providers omit it.' );

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
magick_ai_core_assert( false !== strpos( $testing_strategy, 'composer test:fail-closed' ), 'Testing strategy documents fail-closed fault injection command.' );
magick_ai_core_assert( false !== strpos( $testing_strategy, 'tests/fail-closed.php' ), 'Testing strategy points to the fail-closed fault injection test.' );
magick_ai_core_assert( false !== strpos( $testing_strategy, 'primary `magick-ai/create-draft` governance scenario' ), 'Testing strategy records primary create-draft scenario coverage.' );
magick_ai_core_assert( false !== strpos( $testing_strategy, 'second `magick-ai/set-post-seo-meta` governance scenario' ), 'Testing strategy records second set-post-seo-meta scenario coverage.' );
magick_ai_core_assert( false !== strpos( $testing_strategy, 'third `magick-ai/approve-comment` governance scenario' ), 'Testing strategy records third approve-comment scenario coverage.' );
magick_ai_core_assert( false !== strpos( $testing_strategy, 'taxonomy terms preview governance scenario' ), 'Testing strategy records taxonomy terms preview scenario coverage.' );
magick_ai_core_assert( false !== strpos( $testing_strategy, 'proposal `audit_timeline`' ), 'Testing strategy records governance operability coverage.' );
magick_ai_core_assert( false !== strpos( $testing_strategy, 'commit-preflight `correlation_id`' ), 'Testing strategy records preflight correlation smoke coverage.' );
magick_ai_core_assert( false !== strpos( $testing_strategy, 'trusted Adapter approval coverage' ), 'Testing strategy records trusted Adapter approval smoke coverage.' );
magick_ai_core_assert( false !== strpos( $testing_strategy, 'Fail-closed governance paths' ), 'Testing strategy records fail-closed governance path coverage.' );
magick_ai_core_assert( false !== strpos( $testing_strategy, 'proposal.policy_evaluated' ), 'Testing strategy records policy decision audit failure coverage.' );
magick_ai_core_assert( false !== strpos( $testing_strategy, 'proposal.auto_approved' ), 'Testing strategy records auto approval audit failure coverage.' );
magick_ai_core_assert( false !== strpos( $testing_strategy, 'workflow/task queue, batch execution, or operator runtime console logic' ), 'Testing strategy bans runtime queue and batch execution ownership precisely.' );
magick_ai_core_assert( false !== strpos( $testing_strategy, 'Allowed governance terms such as Review Queue' ), 'Testing strategy distinguishes governance review terms from runtime queues.' );

$reliability_standard = magick_ai_core_read( $root . '/docs/current-stage-governance-reliability.md' );
foreach (
	array(
		'Core is the WordPress AI operation governance layer',
		'Core does not own final execution',
		'app-key rotation and expiry automation are deferred',
		'Adapter or another real external client',
		'Governance must fail closed',
		'magick_ai_core_proposal_audit_failed',
		'magick_ai_core_proposal_decision_audit_failed',
		'magick_ai_core_app_audit_failed',
		'do not return the one-time token',
		'The fault-injection gate must cover both the returned error code and the local',
	) as $required
) {
	magick_ai_core_assert( false !== strpos( $reliability_standard, $required ), 'Reliability standard contains required text: ' . $required );
}

$composer_json = magick_ai_core_read( $root . '/composer.json' );
foreach ( array( 'test:contracts', 'test:fail-closed', 'tests/fail-closed.php' ) as $required ) {
	magick_ai_core_assert( false !== strpos( $composer_json, $required ), 'Composer scripts include required test command: ' . $required );
}

$fail_closed_test = magick_ai_core_read( $root . '/tests/fail-closed.php' );
foreach (
	array(
		'Magick_AI_Core_Fail_Closed_WPDB',
		'fail_insert_tables',
		'magick_ai_core_proposal_insert_failed',
		'magick_ai_core_proposal_audit_failed',
		'magick_ai_core_proposal_decision_audit_failed',
		'magick_ai_core_app_insert_failed',
		'magick_ai_core_app_audit_failed',
		'Unaudited proposal creation is deleted.',
		'rolls status back to pending',
		'App creation audit failure revokes the new key.',
	) as $required
) {
	magick_ai_core_assert( false !== strpos( $fail_closed_test, $required ), 'Fail-closed fault test contains required behavior: ' . $required );
}

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
magick_ai_core_assert( false !== strpos( $smoke_wp, 'output-reference plan creates one batch proposal' ), 'WordPress smoke validates output-reference plan batch proposal creation.' );
magick_ai_core_assert( false !== strpos( $smoke_wp, 'output-reference batch proposal preserves depends_on on write action' ), 'WordPress smoke validates batch proposal dependency preservation.' );
magick_ai_core_assert( false !== strpos( $smoke_wp, 'stale pending proposal expires before detail response' ), 'WordPress smoke validates stale proposal expiration.' );
magick_ai_core_assert( false !== strpos( $smoke_wp, 'expired proposal can be archived' ), 'WordPress smoke validates proposal archiving.' );
magick_ai_core_assert( false !== strpos( $smoke_wp, 'archived proposal can be reopened for review' ), 'WordPress smoke validates proposal reopening.' );

$capabilities_controller = magick_ai_core_read( $root . '/includes/Rest/Capabilities_Controller.php' );
magick_ai_core_assert( false !== strpos( $capabilities_controller, "'/capabilities'" ), 'Capabilities REST route is registered.' );
magick_ai_core_assert( false !== strpos( $capabilities_controller, 'capabilities.listed' ), 'Capabilities route records audit event.' );

$proposals_controller = magick_ai_core_read( $root . '/includes/Rest/Proposals_Controller.php' );
magick_ai_core_assert( false !== strpos( $proposals_controller, "'/proposals'" ), 'Proposals REST route is registered.' );
magick_ai_core_assert( false !== strpos( $proposals_controller, 'Observability::emit' ), 'Proposal REST operations emit local observability events.' );
magick_ai_core_assert( false !== strpos( $proposals_controller, 'core.proposal.create' ), 'Proposal create emits operation observability.' );
magick_ai_core_assert( false !== strpos( $proposals_controller, 'core.proposal.plan_ingest' ), 'Plan intake emits operation observability.' );
magick_ai_core_assert( false !== strpos( $proposals_controller, 'core.proposal.approve' ), 'Proposal approve emits operation observability.' );
magick_ai_core_assert( false !== strpos( $proposals_controller, 'core.proposal.reject' ), 'Proposal reject emits operation observability.' );
magick_ai_core_assert( false !== strpos( $proposals_controller, 'core.commit.preflight' ), 'Commit preflight emits operation observability.' );
magick_ai_core_assert( false !== strpos( $proposals_controller, 'magick_ai_core_proposal_items_blocked' ), 'Commit preflight classifies blocked proposal observability.' );
magick_ai_core_assert( false !== strpos( $proposals_controller, "'/proposals/from-plan'" ), 'Plan-to-proposal REST route is registered.' );
magick_ai_core_assert( false !== strpos( $proposals_controller, 'create_proposals_from_plan' ), 'Plan-to-proposal REST callback is registered.' );
magick_ai_core_assert( false !== strpos( $proposals_controller, 'get_proposal' ), 'Proposal detail REST callback is registered.' );
magick_ai_core_assert( false !== strpos( $proposals_controller, "/approve'" ), 'Proposal approve REST route is registered.' );
magick_ai_core_assert( false !== strpos( $proposals_controller, "/reject'" ), 'Proposal reject REST route is registered.' );
magick_ai_core_assert( false !== strpos( $proposals_controller, "/commit-preflight'" ), 'Proposal commit preflight REST route is registered.' );
magick_ai_core_assert( false !== strpos( $proposals_controller, "'ability_id'" ), 'Proposals route requires ability_id.' );
magick_ai_core_assert( false !== strpos( $proposals_controller, 'audit_timeline' ), 'Proposal detail REST route returns audit timeline.' );
magick_ai_core_assert( false !== strpos( $proposals_controller, 'expire_stale_pending' ), 'Proposal REST routes expire stale pending proposals before reads.' );

$proposal_service = magick_ai_core_read( $root . '/includes/Governance/Proposal_Service.php' );
$proposal_repository = magick_ai_core_read( $root . '/includes/Governance/Proposal_Repository.php' );
$approval_policy_evaluator = magick_ai_core_read( $root . '/includes/Governance/Approval_Policy_Evaluator.php' );
foreach (
	array(
		'Approval_Policy_Evaluator',
		'manual_required',
		'auto_approved',
		'blocked',
		'manual',
		'guarded',
		'trusted_local',
		'break_glass',
		'core-approval-policy-v1',
		'OPTION_POLICY_MODE',
		'MODE_DRY_RUN_GUARDED',
		'MODE_LOCAL_GUARDED',
		'CLEANUP_BATCH_MAX_ACTIONS',
		'AUTO_APPROVAL_HOURLY_LIMIT',
		'AUTO_APPROVAL_DAILY_LIMIT',
		'auto_approval_dry_run_only',
		'local_guarded_cleanup_auto_approved',
		'guarded_cleanup_rejected_missing_test_content_evidence',
		'build-test-content-cleanup-plan',
		'magick-ai/trash-post',
		'consume_auto_approval_quota',
	) as $required
) {
	magick_ai_core_assert( false !== strpos( $approval_policy_evaluator, $required ), 'Approval policy evaluator contains required text: ' . $required );
}
magick_ai_core_assert( false !== strpos( $proposal_repository, 'STATUS_EXPIRED' ), 'Proposal repository defines expired status.' );
magick_ai_core_assert( false !== strpos( $proposal_repository, 'STATUS_ARCHIVED' ), 'Proposal repository defines archived status.' );
magick_ai_core_assert( false !== strpos( $proposal_repository, 'list_stale_pending' ), 'Proposal repository can list stale pending proposals.' );
magick_ai_core_assert( false !== strpos( $proposal_repository, 'list_pending_for_guardrail' ), 'Proposal repository can list pending proposals for create guardrails.' );
magick_ai_core_assert( false !== strpos( $proposal_repository, 'count_by_status' ), 'Proposal repository can count status queues.' );
magick_ai_core_assert( false !== strpos( $proposal_repository, 'OFFSET %d' ), 'Proposal repository supports paginated admin lists.' );
magick_ai_core_assert( false !== strpos( $proposal_repository, 'magick_ai_core_proposal_insert_failed' ), 'Proposal repository returns a stable insert failure error.' );
magick_ai_core_assert( false !== strpos( $proposal_repository, 'delete_by_proposal_id' ), 'Proposal repository can remove unaudited created proposals.' );
magick_ai_core_assert( false !== strpos( $proposal_repository, 'policy_fields_from_caller' ), 'Proposal repository promotes stored policy fields into responses.' );
magick_ai_core_assert( false !== strpos( $proposal_repository, 'policy_decision' ), 'Proposal repository returns policy_decision.' );
magick_ai_core_assert( false !== strpos( $proposal_repository, 'policy_reasons' ), 'Proposal repository returns policy_reasons.' );
magick_ai_core_assert( false !== strpos( $proposal_service, 'proposal.created' ), 'Proposal service records proposal.created audit event.' );
magick_ai_core_assert( false !== strpos( $proposal_service, 'proposal.policy_evaluated' ), 'Proposal service records policy evaluation audit event.' );
magick_ai_core_assert( false !== strpos( $proposal_service, 'proposal.auto_approved' ), 'Proposal service records auto approval audit event.' );
magick_ai_core_assert( false !== strpos( $proposal_service, 'magick_ai_core_auto_approval_audit_failed' ), 'Proposal service fails closed when auto approval audit fails.' );
magick_ai_core_assert( false !== strpos( $proposal_service, 'magick_ai_core_auto_approval_quota_failed' ), 'Proposal service fails closed when auto approval quota cannot be consumed.' );
magick_ai_core_assert( false !== strpos( $proposal_service, 'proposal.deduplicated' ), 'Proposal service records proposal.deduplicated audit event.' );
magick_ai_core_assert( false !== strpos( $proposal_service, 'proposal.quota_blocked' ), 'Proposal service records proposal.quota_blocked audit event.' );
magick_ai_core_assert( false !== strpos( $proposal_service, 'Ability_Registry_Adapter' ), 'Proposal service validates target abilities against ability intake.' );
magick_ai_core_assert( false !== strpos( $proposal_service, 'magick_ai_core_ability_not_available' ), 'Proposal service rejects unavailable target abilities.' );
magick_ai_core_assert( false !== strpos( $proposal_service, 'proposal.approved' ), 'Proposal service records proposal.approved audit event.' );
magick_ai_core_assert( false !== strpos( $proposal_service, 'proposal.rejected' ), 'Proposal service records proposal.rejected audit event.' );
magick_ai_core_assert( false !== strpos( $proposal_service, 'proposal.expired' ), 'Proposal service records proposal.expired audit event.' );
magick_ai_core_assert( false !== strpos( $proposal_service, 'proposal.archived' ), 'Proposal service records proposal.archived audit event.' );
magick_ai_core_assert( false !== strpos( $proposal_service, 'proposal.reopened' ), 'Proposal service records proposal.reopened audit event.' );
magick_ai_core_assert( false !== strpos( $proposal_service, 'PENDING_TTL_SECONDS' ), 'Proposal service defines a pending review TTL.' );
magick_ai_core_assert( false !== strpos( $proposal_service, 'PENDING_QUOTA_PER_APP' ), 'Proposal service defines an app pending proposal quota.' );
magick_ai_core_assert( false !== strpos( $proposal_service, 'PENDING_QUOTA_PER_USER' ), 'Proposal service defines a user pending proposal quota.' );
magick_ai_core_assert( false !== strpos( $proposal_service, 'magick_ai_core_pending_proposal_quota_exceeded' ), 'Proposal service blocks callers with too many pending proposals.' );
magick_ai_core_assert( false !== strpos( $proposal_service, 'core_guardrails' ), 'Proposal service stores non-secret proposal creation guardrail metadata.' );
magick_ai_core_assert( false !== strpos( $proposal_service, 'core_policy' ), 'Proposal service stores non-secret policy decision metadata.' );
magick_ai_core_assert( false !== strpos( $proposal_service, 'magick_ai_core_policy_decision_audit_failed' ), 'Proposal service fails closed when policy decision audit fails.' );
magick_ai_core_assert( false !== strpos( $proposal_service, 'deduplicated' ), 'Proposal service returns existing pending duplicates.' );
magick_ai_core_assert( false !== strpos( $proposal_service, 'proposal.listed' ), 'Proposal service records proposal.listed audit event.' );
magick_ai_core_assert( false !== strpos( $proposal_service, 'proposal.viewed' ), 'Proposal service records proposal.viewed audit event.' );
magick_ai_core_assert( false !== strpos( $proposal_service, 'audit_timeline' ), 'Proposal service exposes proposal audit timeline.' );
magick_ai_core_assert( false !== strpos( $proposal_service, "'pending'" ), 'Proposal service only transitions pending proposals.' );
magick_ai_core_assert( false !== strpos( $proposal_service, 'magick_ai_core_ability_not_available' ), 'Proposal service rejects unavailable target abilities.' );
magick_ai_core_assert( false !== strpos( $proposal_service, '$this->abilities->find' ), 'Proposal service validates against ability intake.' );
magick_ai_core_assert( false !== strpos( $proposal_service, 'magick_ai_core_proposal_audit_failed' ), 'Proposal service fails closed when creation audit fails.' );
magick_ai_core_assert( false !== strpos( $proposal_service, 'magick_ai_core_proposal_decision_audit_failed' ), 'Proposal service fails closed when decision audit fails.' );
magick_ai_core_assert( false !== strpos( $proposal_service, 'audit_failed_error' ), 'Proposal service uses stable audit failure errors.' );
magick_ai_core_assert( false !== strpos( $proposal_service, 'update_status( $proposal_id, (string) $existing' ), 'Proposal service rolls back decision status when audit fails.' );
magick_ai_core_assert( false === strpos( $proposal_service, 'confirm_token' ), 'Proposal service does not use confirm_token.' );
magick_ai_core_assert( false === strpos( $proposal_service, 'write_confirmed' ), 'Proposal service does not use write_confirmed.' );

$commit_preflight_service = magick_ai_core_read( $root . '/includes/Governance/Commit_Preflight_Service.php' );
magick_ai_core_assert( false !== strpos( $commit_preflight_service, 'commit.preflighted' ), 'Commit preflight records commit.preflighted audit event.' );
magick_ai_core_assert( false !== strpos( $commit_preflight_service, 'approval_commit_authorized' ), 'Commit preflight returns approval context.' );
magick_ai_core_assert( false !== strpos( $commit_preflight_service, 'commit_execution' ), 'Commit preflight explicitly reports no commit execution.' );
magick_ai_core_assert( false !== strpos( $commit_preflight_service, 'correlation_id' ), 'Commit preflight returns and audits correlation id.' );
magick_ai_core_assert( false !== strpos( $commit_preflight_service, 'approved_input_hash' ), 'Commit preflight binds approval context to approved input hash.' );
magick_ai_core_assert( false !== strpos( $commit_preflight_service, 'approved_preview_hash' ), 'Commit preflight binds approval context to approved preview hash.' );
magick_ai_core_assert( false !== strpos( $commit_preflight_service, 'policy_version' ), 'Commit preflight returns a policy version for Adapter binding.' );
magick_ai_core_assert( false !== strpos( $commit_preflight_service, 'payload_hash' ), 'Commit preflight has stable payload hash generation.' );
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
		'magick-ai/build-media-reference-repair-plan',
		'magick-ai/build-media-settings-reference-repair-plan',
		'magick-ai-toolbox/build-article-write-plan',
		'proposal.plan_ingested',
		'magick-ai/delete-media-permanently',
		'destructive_media_delete_not_explicitly_included',
		'validate_article_write_plan_contract',
		'article_workflow_preview',
		'article_workflow_artifact_keys',
		'article_write_plan',
		'article_goal_brief',
		'research_evidence_pack',
		'article_outline',
		'article_draft_candidate',
		'discoverability_pack',
		'article_risk_report',
		'magick_ai_core_article_plan_publish_rejected',
		'magick_ai_core_article_plan_blocked_claims',
		'magick_ai_core_article_plan_risk_blocked',
		'magick_ai_core_article_plan_target_rejected',
		'magick-ai/create-draft',
		'proposal_ready',
		'needs_input',
		'preflight_blockers',
		'plan_requires_batch_proposal',
		'plan_to_proposal_batch',
		'batch_approval',
		'proposal_mode',
		'depends_on',
		'skipped_destructive_candidates',
		'manual_review',
		'commit_execution',
		'dry_run',
	) as $required
) {
	magick_ai_core_assert( false !== strpos( $plan_proposal_service, $required ), 'Plan-to-proposal service contains required text: ' . $required );
}

$smoke_wp = magick_ai_core_read( $root . '/tests/smoke-wp.php' );
magick_ai_core_assert( false !== strpos( $smoke_wp, 'include_unattached_test_media' ), 'Smoke test media delete fixture opts into abilities-side test media delete policy.' );
magick_ai_core_assert( false !== strpos( $smoke_wp, 'magick_ai_core_smoke_register_post_fixture' ), 'Smoke test registers post fixtures for cleanup.' );
magick_ai_core_assert( false !== strpos( $smoke_wp, 'magick_ai_core_smoke_register_comment_fixture' ), 'Smoke test registers comment fixtures for cleanup.' );
magick_ai_core_assert( false !== strpos( $smoke_wp, 'magick_ai_core_smoke_register_attachment_fixture' ), 'Smoke test registers media attachment fixtures for cleanup.' );
magick_ai_core_assert( false !== strpos( $smoke_wp, 'magick_ai_core_smoke_register_term_fixture' ), 'Smoke test registers taxonomy term fixtures for cleanup.' );
magick_ai_core_assert( false !== strpos( $smoke_wp, 'magick_ai_core_smoke_register_app_key_fixture' ), 'Smoke test registers app key fixtures for revocation.' );
magick_ai_core_assert( false !== strpos( $smoke_wp, 'register_shutdown_function' ), 'Smoke test runs fixture cleanup on shutdown.' );
magick_ai_core_assert( false !== strpos( $smoke_wp, 'wp_delete_post' ), 'Smoke test permanently deletes post fixtures.' );
magick_ai_core_assert( false !== strpos( $smoke_wp, 'wp_delete_comment' ), 'Smoke test permanently deletes comment fixtures.' );
magick_ai_core_assert( false !== strpos( $smoke_wp, 'wp_delete_attachment' ), 'Smoke test permanently deletes media attachment fixtures.' );
magick_ai_core_assert( false !== strpos( $smoke_wp, 'wp_delete_term' ), 'Smoke test deletes taxonomy term fixtures.' );
magick_ai_core_assert( false !== strpos( $smoke_wp, 'revoke_by_key_id' ), 'Smoke test revokes app key fixtures.' );
magick_ai_core_assert( false !== strpos( $smoke_wp, 'MAGICK_AI_CORE_SMOKE_PURGE' ), 'Smoke test keeps governance row purge opt-in.' );
magick_ai_core_assert( false !== strpos( $testing_strategy, 'Proposal and audit rows remain persistent by default' ), 'Testing strategy keeps governance rows persistent by default.' );
magick_ai_core_assert( false !== strpos( $development_workflow, 'MAGICK_AI_CORE_SMOKE_PURGE=1' ), 'Development workflow documents optional smoke purge.' );

$plan_to_proposal_docs = magick_ai_core_read( $root . '/docs/plan-to-proposal-governance.md' );
magick_ai_core_assert( false !== strpos( $plan_to_proposal_docs, 'include_unattached_test_media' ), 'Plan-to-proposal docs mention abilities-side unattached test media delete gate.' );
magick_ai_core_assert( false !== strpos( $plan_to_proposal_docs, 'include_trash_parent_media' ), 'Plan-to-proposal docs mention abilities-side trash-parent media delete gate.' );
magick_ai_core_assert( false !== strpos( $plan_to_proposal_docs, 'magick-ai-toolbox/build-article-write-plan' ), 'Plan-to-proposal docs include the Toolbox article writing handoff.' );
magick_ai_core_assert( false !== strpos( $plan_to_proposal_docs, 'preview.article_workflow' ), 'Plan-to-proposal docs require article workflow preview evidence.' );
magick_ai_core_assert( false !== strpos( $plan_to_proposal_docs, 'Article writing is a local Ability recipe' ), 'Plan-to-proposal docs treat article writing as local Ability recipe.' );
magick_ai_core_assert( false !== strpos( $plan_to_proposal_docs, 'must not produce article drafts' ), 'Plan-to-proposal docs prohibit Cloud draft generation.' );

$article_writing_contract = magick_ai_core_read( $root . '/docs/article-writing-workflow-contract.md' );
foreach (
	array(
		'Status: active planning contract',
		'article_draft_v1',
		'magick-ai-toolbox/build-article-write-plan',
		'magick-ai-toolbox/get-content-discoverability-context',
		'magick-ai/create-draft',
		'article_goal_brief',
		'research_evidence_pack',
		'article_outline',
		'article_draft_candidate',
		'discoverability_pack',
		'article_risk_report',
		'article_write_plan',
		'ready_for_proposal',
		'blocked_claims',
		'status',
		'draft',
		'commit_execution=false',
		'Ability Recipe Orchestration Contract',
		'Cloud Bulk Article Run Contract',
		'Cloud must not generate article drafts',
		'Final WordPress writes stay local and Abilities API based',
		'Cloud Addon must not import Cloud article artifacts',
		'not a second control',
		'Article Assistant Workbench',
		'not an article generation product',
		'one article and one draft proposal per run',
		'no batch writing',
	) as $required
) {
	magick_ai_core_assert( false !== strpos( $article_writing_contract, $required ), 'Article writing workflow contract contains required text: ' . $required );
}

$ability_recipe_contract = magick_ai_core_read( $root . '/docs/ability-recipe-orchestration-contract.md' );
foreach (
	array(
		'Status: active planning contract',
		'An ability recipe is a deterministic orchestration plan',
		'Article drafting is the first example recipe',
		'article_draft_v1',
		'magick-ai-toolbox/get-content-discoverability-context',
		'magick-ai-toolbox/build-article-write-plan',
		'magick-ai/create-draft',
		'Cloud must not provide article writing generation',
		'Cloud must not store article body generation jobs',
		'Core must not become article-aware beyond validating supported plan output',
		'Do not add Cloud article import flows',
		'local article assistant workbench',
		'one local article at a time',
		'hidden content-generation platform',
	) as $required
) {
	magick_ai_core_assert( false !== strpos( $ability_recipe_contract, $required ), 'Ability recipe orchestration contract contains required text: ' . $required );
}

$cloud_bulk_article_contract = magick_ai_core_read( $root . '/docs/cloud-bulk-article-run-contract.md' );
foreach (
	array(
		'Status: prohibited and deprecated planning contract',
		'bulk_article_run_v1',
		'article writing generation',
		'Cloud-produced `article_write_plan` candidates',
		'Ability Recipe Orchestration Contract',
		'article_write_plan',
		'magick-ai-toolbox/build-article-write-plan',
		'Core POST /proposals/from-plan',
		'Cloud must not generate, store, or return article body content',
		'local Ability recipe orchestration',
		'Rejected Product Language',
		'Cloud article generator',
		'local Article Assistant Workbench',
	) as $required
) {
	magick_ai_core_assert( false !== strpos( $cloud_bulk_article_contract, $required ), 'Cloud bulk article run contract contains required text: ' . $required );
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
magick_ai_core_assert( false !== strpos( $audit_repository, 'exclude_event_names' ), 'Audit repository can exclude noisy read events.' );
magick_ai_core_assert( false !== strpos( $audit_repository, 'count_filtered' ), 'Audit repository can count filtered rows for pagination.' );
magick_ai_core_assert( false !== strpos( $audit_repository, 'offset' ), 'Audit repository supports paginated admin lists.' );

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
magick_ai_core_assert( false !== strpos( $request_context, "'scopes'" ), 'Request context stores app scopes for local guarded auto approval.' );
magick_ai_core_assert( false !== strpos( $request_context, 'in_array( $scope' ), 'Request context can check any app scope, not only the current route scope.' );

$observability = magick_ai_core_read( $root . '/includes/Observability.php' );
foreach ( array( 'Observability', 'magick_ai_observability_event', 'schema_version', 'plugin_slug', 'source', 'local', 'event_kind', 'event_id', 'sanitize_payload', 'proposal_count', 'blocked_count' ) as $required ) {
	magick_ai_core_assert( false !== strpos( $observability, $required ), 'Observability bridge contains required text: ' . $required );
}

$core_operability = magick_ai_core_read( $root . '/docs/core-governance-operability.md' );
foreach ( array( 'core.proposal.create', 'core.proposal.plan_ingest', 'core.proposal.approve', 'core.proposal.reject', 'core.commit.preflight', 'approval notes', 'policy payloads' ) as $required ) {
	magick_ai_core_assert( false !== strpos( $core_operability, $required ), 'Core operability documents observability contract: ' . $required );
}

$admin_page = magick_ai_core_read( $root . '/includes/Admin/Admin_Page.php' );
$admin_surface_standard = magick_ai_core_read( $root . '/docs/admin-surface-standard.md' );
foreach (
	array(
		'local governance workbench',
		'Review Queue',
		'pending proposal review list',
		'Governance Audit',
		'Expired / Archived',
		'Development Approval Policy',
		'Advanced Access',
		'paginated',
		'low-frequency fallback action',
		'general policy rules UI',
		'OpenClaw onboarding',
		'ability definitions',
		'cloud connection settings',
	) as $required
) {
	magick_ai_core_assert( false !== strpos( $admin_surface_standard, $required ), 'Admin surface standard documents Core page boundary: ' . $required );
}
magick_ai_core_assert( false !== strpos( $admin_page, 'admin_post_magick_ai_core_approve_proposal' ), 'Admin page registers approve handler.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'admin_post_magick_ai_core_reject_proposal' ), 'Admin page registers reject handler.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'admin_post_magick_ai_core_bulk_reject_proposals' ), 'Admin page registers bulk reject handler.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'admin_post_magick_ai_core_archive_proposal' ), 'Admin page registers archive handler.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'admin_post_magick_ai_core_reopen_proposal' ), 'Admin page registers reopen handler.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'admin_post_magick_ai_core_update_approval_policy' ), 'Admin page registers approval policy mode handler.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'admin_post_magick_ai_core_create_app_key' ), 'Admin page registers app-key creation handler.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'admin_post_magick_ai_core_revoke_app_key' ), 'Admin page registers app-key revocation handler.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'check_admin_referer' ), 'Admin proposal actions enforce nonce.' );
magick_ai_core_assert( false !== strpos( $admin_page, "current_user_can( 'manage_options' )" ), 'Admin proposal actions enforce capability.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'Reject selected' ), 'Admin review queue exposes bulk rejection.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'array_slice( $proposal_ids, 0, 50 )' ), 'Admin bulk rejection is bounded.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'Proposal ID:' ), 'Admin review queue keeps proposal ids visible.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'pending_proposal_trace_parts' ), 'Admin review queue summarizes source trace metadata.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'plan_ability_id' ), 'Admin review queue can show plan-to-proposal source metadata.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'Advanced Access' ), 'Admin page folds Core app-key management behind advanced access.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'Development Approval Policy' ), 'Admin page exposes lightweight development approval policy mode.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'MODE_DRY_RUN_GUARDED' ), 'Admin page exposes dry-run guarded approval mode.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'MODE_LOCAL_GUARDED' ), 'Admin page exposes local guarded approval mode.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'update_option( Approval_Policy_Evaluator::OPTION_POLICY_MODE' ), 'Admin page persists approval policy mode through a bounded option.' );
magick_ai_core_assert( false !== strpos( $admin_page, "'app-keys'" ), 'Admin page keeps app-key management available behind an advanced view.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'render_admin_tabs' ), 'Admin page exposes tabbed Core sections.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'nav-tab-wrapper' ), 'Admin page uses WordPress admin tabs for Core sections.' );
magick_ai_core_assert( false === strpos( $admin_page, "'app-keys' => array" ), 'Admin page does not expose Core App Keys as a first-level tab.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'Review Queue' ), 'Admin page defaults to the review queue tab.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'Expired / Archived' ), 'Admin page exposes stale proposal archive tab.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'render_pagination' ), 'Admin page paginates long governance lists.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'review_page' ), 'Admin page paginates review queue.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'archive_page' ), 'Admin page paginates expired and archived proposals.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'audit_page' ), 'Admin page paginates governance audit.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'app_key_page' ), 'Admin page paginates advanced app-key management.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'archive_status' ), 'Admin page filters expired and archived proposal lists.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'No active proposals. Expired items are moved out of the review queue automatically.' ), 'Admin page provides a clear active queue empty state.' );
magick_ai_core_assert( false === strpos( $admin_page, 'render_advanced_entries' ), 'Admin default page no longer renders low-frequency administration links inline.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'Recent Activity' ), 'Admin default page exposes a compact recent activity section.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'Latest Core governance events. Full audit is in its own tab.' ), 'Admin default page folds recent activity into a disclosure.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'Governance Audit' ), 'Admin page exposes a full governance audit view.' );
magick_ai_core_assert( false === strpos( $admin_page, 'Advanced: Core App Keys' ), 'Admin default page no longer folds app-key management inline.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'Manage Core app keys' ), 'Admin default page exposes app-key management as a low-frequency action.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'Advanced audit filters' ), 'Admin page folds detailed audit filters into an advanced disclosure.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'audit_include_read_events' ), 'Admin audit hides read noise by default with an opt-in filter.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'render_audit_detail' ), 'Admin audit combines optional app/scope/correlation metadata into a detail cell.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'audit_filters_from_request' ), 'Admin page reads governance audit filters.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'audit_proposal_id' ), 'Admin page exposes proposal audit filter.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'audit_correlation_id' ), 'Admin page exposes correlation audit filter.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'AI Request Logs remain separate' ), 'Admin page separates Core audit from AI Request Logs.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'Productized OpenClaw setup belongs in Magick AI Adapter' ), 'Admin page avoids presenting Core as the OpenClaw product entry point.' );
magick_ai_core_assert( false === strpos( $admin_page, 'Environment template' ), 'Admin default page no longer exposes an env template.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'Create Core App Key' ), 'Admin page labels key creation as Core credential management.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'Issue a scoped token for a trusted governance client.' ), 'Admin page folds Core app-key creation behind an explicit disclosure.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'Reopen for review' ), 'Admin page exposes reopen action for expired or archived proposals.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'Archive' ), 'Admin page exposes archive action for expired proposals.' );
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
magick_ai_core_assert( false !== strpos( $admin_page, 'magick_ai_core_app_audit_failed' ), 'Admin page does not show one-time app token when creation audit fails.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'default_scopes' ), 'Admin page defaults to scoped external adapter access.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'App_Key_Repository::DEFAULT_RATE_LIMIT' ), 'Admin page exposes bounded rate policy inputs.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'array_map' ) && false !== strpos( $admin_page, 'sanitize_text_field' ), 'Admin app-key scopes are sanitized before repository validation.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'app.revoked' ), 'Admin page audits app-key revocation.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'button-link-delete' ), 'Admin page exposes a key disable action.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'Review Context' ), 'Admin proposal detail renders summary-first review context.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'render_article_workflow_review_context' ), 'Admin proposal detail renders article workflow review context.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'Article workflow' ), 'Admin proposal detail labels article workflow summary.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'final_write_ability' ), 'Admin proposal detail shows article final write ability.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'direct_wordpress_write' ), 'Admin proposal detail shows article direct-write state.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'article_goal_brief' ), 'Admin proposal detail shows article goal artifact availability.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'article_risk_report' ), 'Admin proposal detail shows article risk artifact availability.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'Raw proposal payload' ), 'Admin proposal detail folds raw JSON payload behind a disclosure.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'Audit Timeline' ), 'Admin proposal detail renders audit timeline.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'scope_decision' ), 'Admin proposal detail shows scope decision attribution.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'correlation_id' ), 'Admin proposal detail shows correlation id attribution.' );
magick_ai_core_assert( false !== strpos( $core_operability, 'article workflow summary' ), 'Core governance operability documents article workflow summary.' );
magick_ai_core_assert( false !== strpos( $core_operability, 'final write ability' ), 'Core governance operability documents article final write ability.' );

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
