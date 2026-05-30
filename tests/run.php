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

$readme = magick_ai_core_read( $root . '/README.md' );
foreach (
	array(
		'WordPress AI operation governance layer',
		'It does not generate content',
		'GET /wp-json/magick-ai-core/v1/capabilities',
		'POST /wp-json/magick-ai-core/v1/apps',
		'POST /wp-json/magick-ai-core/v1/proposals',
		'GET /wp-json/magick-ai-core/v1/proposals/{proposal_id}',
		'POST /wp-json/magick-ai-core/v1/proposals/{proposal_id}/approve',
		'POST /wp-json/magick-ai-core/v1/proposals/{proposal_id}/commit-preflight',
	) as $required
) {
	magick_ai_core_assert( false !== strpos( $readme, $required ), 'README contains required phrase: ' . $required );
}

$positioning = magick_ai_core_read( $root . '/docs/product-positioning.md' );
magick_ai_core_assert( false !== strpos( $positioning, 'Magick AI Core governs AI-assisted WordPress operations.' ), 'Positioning keeps one-sentence product truth.' );
magick_ai_core_assert( false !== strpos( $positioning, '`magick-ai-abilities`' ), 'Positioning names magick-ai-abilities as ability owner.' );
magick_ai_core_assert( false !== strpos( $positioning, '`magick-ai-content-assistant`' ), 'Positioning names Content Assistant as product UX owner.' );

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
		'GET /proposals/{proposal_id}',
		'POST /proposals/{proposal_id}/approve',
		'POST /proposals/{proposal_id}/reject',
		'POST /proposals/{proposal_id}/commit-preflight',
		'GET /audit',
		'POST /apps',
		'Authorization: Bearer mai_core.<key_id>.<secret>',
		'magick_ai_core_app_scope_forbidden',
		'magick_ai_core_app_rate_limited',
		'magick_ai_core_invalid_ability_id',
		'magick_ai_core_ability_not_available',
		'magick_ai_core_legacy_confirmation_rejected',
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
		'magick-ai/approve-comment',
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
	) as $required
) {
	magick_ai_core_assert( false !== strpos( $app_auth_scope, $required ), 'App auth scope policy contains required text: ' . $required );
}

$next_stage_plan = magick_ai_core_read( $root . '/docs/next-stage-plan.md' );
magick_ai_core_assert( false !== strpos( $next_stage_plan, 'Agent/MCP Governance Entry' ), 'Next stage plan includes Agent/MCP governance entry phase.' );
magick_ai_core_assert( false !== strpos( $next_stage_plan, 'minimal implementation active' ), 'Next stage plan marks app auth as implemented minimally.' );
magick_ai_core_assert( false !== strpos( $next_stage_plan, 'create-draft governance scenario active' ), 'Next stage plan marks create-draft governance scenario status.' );
magick_ai_core_assert( false !== strpos( $next_stage_plan, 'Create Draft Governance Scenario' ), 'Next stage plan links create-draft scenario.' );

$readme = magick_ai_core_read( $root . '/README.md' );
magick_ai_core_assert( false !== strpos( $readme, 'Agent MCP Entry Contract' ), 'README links Agent MCP Entry Contract.' );
magick_ai_core_assert( false !== strpos( $readme, 'App Auth Scope Policy' ), 'README links App Auth Scope Policy.' );
magick_ai_core_assert( false !== strpos( $readme, 'OpenClaw governance adapter example' ), 'README links OpenClaw governance adapter example.' );
magick_ai_core_assert( false !== strpos( $readme, 'Create Draft Governance Scenario' ), 'README links Create Draft Governance Scenario.' );

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
		'create-draft-proposal',
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

$openclaw_adapter = magick_ai_core_read( $root . '/examples/openclaw-governance-adapter/openclaw-governance-adapter.php' );
foreach (
	array(
		'capabilities',
		'create-draft-proposal',
		'create-proposal',
		'commit-preflight',
		'magick_ai_core_adapter_assert_create_draft_contract',
		'Required ability is not discoverable through Core',
		'input schema is missing governance control',
		'$input[\'commit\']  = false',
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

$ability_intake = magick_ai_core_read( $root . '/docs/ability-intake-contract.md' );
magick_ai_core_assert( false !== strpos( $ability_intake, 'magick_ai_abilities_get_workflow_definitions()' ), 'Ability intake contract prefers runtime workflow definition discovery.' );
magick_ai_core_assert( false !== strpos( $ability_intake, 'agent-workflow-replay.json' ), 'Ability intake contract points to the shared replay fixture.' );
magick_ai_core_assert( false !== strpos( $ability_intake, 'does not copy the fixture into a workflow runtime' ), 'Ability intake contract keeps replay consumption out of runtime ownership.' );
magick_ai_core_assert( false !== strpos( $ability_intake, 'currently discoverable' ), 'Ability intake contract rejects unavailable proposal ability ids.' );
magick_ai_core_assert( false !== strpos( $ability_intake, 'Create Draft Governance Scenario' ), 'Ability intake contract points to the create-draft scenario.' );

$testing_strategy = magick_ai_core_read( $root . '/docs/testing-strategy.md' );
magick_ai_core_assert( false !== strpos( $testing_strategy, 'agent-workflow-replay.json' ), 'Testing strategy records shared replay fixture smoke coverage.' );
magick_ai_core_assert( false !== strpos( $testing_strategy, 'primary `magick-ai/create-draft` governance scenario' ), 'Testing strategy records primary create-draft scenario coverage.' );

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
magick_ai_core_assert( false !== strpos( $smoke_wp, 'magick-ai/approve-comment' ), 'WordPress smoke validates comment moderation proposal governance.' );
magick_ai_core_assert( false !== strpos( $smoke_wp, 'app-authenticated proposal stores app attribution' ), 'WordPress smoke validates app proposal attribution.' );
magick_ai_core_assert( false !== strpos( $smoke_wp, 'app-authenticated audit read is denied without audit scope' ), 'WordPress smoke validates denied app audit scope.' );
magick_ai_core_assert( false !== strpos( $smoke_wp, 'app rate limit returns 429 after fixed window is exhausted' ), 'WordPress smoke validates app rate limiting.' );
magick_ai_core_assert( false !== strpos( $smoke_wp, 'revoked app key returns 401' ), 'WordPress smoke validates revoked app key denial.' );

$capabilities_controller = magick_ai_core_read( $root . '/includes/Rest/Capabilities_Controller.php' );
magick_ai_core_assert( false !== strpos( $capabilities_controller, "'/capabilities'" ), 'Capabilities REST route is registered.' );
magick_ai_core_assert( false !== strpos( $capabilities_controller, 'capabilities.listed' ), 'Capabilities route records audit event.' );

$proposals_controller = magick_ai_core_read( $root . '/includes/Rest/Proposals_Controller.php' );
magick_ai_core_assert( false !== strpos( $proposals_controller, "'/proposals'" ), 'Proposals REST route is registered.' );
magick_ai_core_assert( false !== strpos( $proposals_controller, 'get_proposal' ), 'Proposal detail REST callback is registered.' );
magick_ai_core_assert( false !== strpos( $proposals_controller, "/approve'" ), 'Proposal approve REST route is registered.' );
magick_ai_core_assert( false !== strpos( $proposals_controller, "/reject'" ), 'Proposal reject REST route is registered.' );
magick_ai_core_assert( false !== strpos( $proposals_controller, "/commit-preflight'" ), 'Proposal commit preflight REST route is registered.' );
magick_ai_core_assert( false !== strpos( $proposals_controller, "'ability_id'" ), 'Proposals route requires ability_id.' );

$proposal_service = magick_ai_core_read( $root . '/includes/Governance/Proposal_Service.php' );
magick_ai_core_assert( false !== strpos( $proposal_service, 'proposal.created' ), 'Proposal service records proposal.created audit event.' );
magick_ai_core_assert( false !== strpos( $proposal_service, 'Ability_Registry_Adapter' ), 'Proposal service validates target abilities against ability intake.' );
magick_ai_core_assert( false !== strpos( $proposal_service, 'magick_ai_core_ability_not_available' ), 'Proposal service rejects unavailable target abilities.' );
magick_ai_core_assert( false !== strpos( $proposal_service, 'proposal.approved' ), 'Proposal service records proposal.approved audit event.' );
magick_ai_core_assert( false !== strpos( $proposal_service, 'proposal.rejected' ), 'Proposal service records proposal.rejected audit event.' );
magick_ai_core_assert( false !== strpos( $proposal_service, 'proposal.listed' ), 'Proposal service records proposal.listed audit event.' );
magick_ai_core_assert( false !== strpos( $proposal_service, 'proposal.viewed' ), 'Proposal service records proposal.viewed audit event.' );
magick_ai_core_assert( false !== strpos( $proposal_service, "'pending'" ), 'Proposal service only transitions pending proposals.' );
magick_ai_core_assert( false !== strpos( $proposal_service, 'magick_ai_core_ability_not_available' ), 'Proposal service rejects unavailable target abilities.' );
magick_ai_core_assert( false !== strpos( $proposal_service, '$this->abilities->find' ), 'Proposal service validates against ability intake.' );
magick_ai_core_assert( false === strpos( $proposal_service, 'confirm_token' ), 'Proposal service does not use confirm_token.' );
magick_ai_core_assert( false === strpos( $proposal_service, 'write_confirmed' ), 'Proposal service does not use write_confirmed.' );

$commit_preflight_service = magick_ai_core_read( $root . '/includes/Governance/Commit_Preflight_Service.php' );
magick_ai_core_assert( false !== strpos( $commit_preflight_service, 'commit.preflighted' ), 'Commit preflight records commit.preflighted audit event.' );
magick_ai_core_assert( false !== strpos( $commit_preflight_service, 'approval_commit_authorized' ), 'Commit preflight returns approval context.' );
magick_ai_core_assert( false !== strpos( $commit_preflight_service, 'commit_execution' ), 'Commit preflight explicitly reports no commit execution.' );
magick_ai_core_assert( false !== strpos( $commit_preflight_service, 'confirm_token' ), 'Commit preflight rejects confirm_token input.' );
magick_ai_core_assert( false !== strpos( $commit_preflight_service, 'write_confirmed' ), 'Commit preflight rejects write_confirmed input.' );

$audit_repository = magick_ai_core_read( $root . '/includes/Audit/Audit_Log_Repository.php' );
magick_ai_core_assert( false !== strpos( $audit_repository, 'sanitize_text_field( $event_name )' ), 'Audit repository preserves dotted event names.' );
magick_ai_core_assert( false !== strpos( $audit_repository, 'list_filtered' ), 'Audit repository supports filtered event lists.' );
magick_ai_core_assert( false !== strpos( $audit_repository, 'proposal_id = %s' ), 'Audit repository filters by proposal id safely.' );
magick_ai_core_assert( false !== strpos( $audit_repository, 'event_name = %s' ), 'Audit repository filters by event name safely.' );

$admin_page = magick_ai_core_read( $root . '/includes/Admin/Admin_Page.php' );
magick_ai_core_assert( false !== strpos( $admin_page, 'admin_post_magick_ai_core_approve_proposal' ), 'Admin page registers approve handler.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'admin_post_magick_ai_core_reject_proposal' ), 'Admin page registers reject handler.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'admin_post_magick_ai_core_create_app_key' ), 'Admin page registers app-key creation handler.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'admin_post_magick_ai_core_revoke_app_key' ), 'Admin page registers app-key revocation handler.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'check_admin_referer' ), 'Admin proposal actions enforce nonce.' );
magick_ai_core_assert( false !== strpos( $admin_page, "current_user_can( 'manage_options' )" ), 'Admin proposal actions enforce capability.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'External App Access' ), 'Admin page exposes external app access section.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'OpenClaw Handoff' ), 'Admin page exposes OpenClaw handoff guidance.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'Agent rules' ), 'Admin page includes external agent rules.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'Do not store or print MAGICK_AI_CORE_APP_TOKEN' ), 'Admin page warns external agents not to leak app tokens.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'create-draft-proposal' ), 'Admin page handoff points to the primary create-draft adapter path.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'MAGICK_AI_CORE_INSECURE_SSL=true' ), 'Admin page includes local TLS handoff setting.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'MAGICK_AI_CORE_CA_BUNDLE' ), 'Admin page prefers local CA bundle when available.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'include_local_tls' ), 'Admin page exposes local TLS export checkbox.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'is_local_base_url' ), 'Admin page defaults local TLS export only for local hosts.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'openclaw_env_text' ), 'Admin page centralizes OpenClaw env generation.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'does not change Core server security' ), 'Admin page clarifies local TLS is client-side only.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'MAGICK_AI_CORE_BASE_URL' ), 'Admin page shows base URL env value.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'MAGICK_AI_CORE_APP_TOKEN' ), 'Admin page shows app token env value.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'render_created_app_key' ), 'Admin page renders one-time app key result.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'nocache_headers' ), 'Admin app-key result prevents caching the one-time token.' );
magick_ai_core_assert( false === strpos( $admin_page, 'wp-admin/admin-header.php' ), 'Admin app-key result avoids admin header inside admin-post context.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'shown only once and is not stored in raw form' ), 'Admin page warns that app token is one-time only.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'default_scopes' ), 'Admin page defaults to scoped external adapter access.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'App_Key_Repository::DEFAULT_RATE_LIMIT' ), 'Admin page exposes bounded rate policy inputs.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'app.revoked' ), 'Admin page audits app-key revocation.' );
magick_ai_core_assert( false !== strpos( $admin_page, 'button-link-delete' ), 'Admin page exposes a key disable action.' );

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
