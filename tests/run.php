<?php
/**
 * Static contract tests for the MVP plugin.
 *
 * @package NpcinkGovernanceCore
 */

$root = dirname( __DIR__ );

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', $root . '/tests/wp-stub/' );
}

/**
 * Assertion helper.
 *
 * @param bool   $condition Condition.
 * @param string $message Failure message.
 * @return void
 */
function npcink_governance_core_assert( bool $condition, string $message ): void {
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
function npcink_governance_core_read( string $path ): string {
	$contents = is_readable( $path ) ? file_get_contents( $path ) : false;
	return is_string( $contents ) ? $contents : '';
}

/**
 * Returns project text files used for drift checks.
 *
 * @param string $root Root.
 * @return array<int,string>
 */
function npcink_governance_core_project_files( string $root ): array {
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

/**
 * Locates the sibling npcink-abilities-toolkit replay fixture.
 *
 * @param string $root Current project root.
 * @return string
 */
function npcink_governance_core_shared_replay_fixture_path( string $root ): string {
	$env_path = getenv( 'NPCINK_ABILITIES_TOOLKIT_PATH' );
	$roots    = array();

	if ( is_string( $env_path ) && '' !== trim( $env_path ) ) {
		$roots[] = rtrim( $env_path, '/' );
	}

	$roots[] = dirname( $root ) . '/npcink-abilities-toolkit';
	$roots[] = '/Users/muze/gitee/npcink-abilities-toolkit';

	foreach ( $roots as $toolkit_root ) {
		$fixture = $toolkit_root . '/tests/fixtures/agent-workflow-replay.json';
		if ( is_readable( $fixture ) ) {
			return $fixture;
		}
	}

	return '';
}

/**
 * Finds a forbidden key in a nested array.
 *
 * @param mixed         $value Value to inspect.
 * @param array<int,string> $forbidden_keys Forbidden key names.
 * @param string        $path Current path.
 * @return string
 */
function npcink_governance_core_find_forbidden_key( $value, array $forbidden_keys, string $path = '$' ): string {
	if ( ! is_array( $value ) ) {
		return '';
	}

	foreach ( $value as $key => $child ) {
		if ( is_string( $key ) && in_array( $key, $forbidden_keys, true ) ) {
			return $path . '.' . $key;
		}

		$child_path = is_string( $key ) ? $path . '.' . $key : $path . '[]';
		$found      = npcink_governance_core_find_forbidden_key( $child, $forbidden_keys, $child_path );
		if ( '' !== $found ) {
			return $found;
		}
	}

	return '';
}

$main_plugin = npcink_governance_core_read( $root . '/npcink-governance-core.php' );
npcink_governance_core_assert( false !== strpos( $main_plugin, 'Plugin Name: npcink-governance-core' ), 'Main plugin file declares plugin header.' );
npcink_governance_core_assert( false !== strpos( $main_plugin, 'Description: Npcink AI governance layer for WordPress operations.' ), 'Main plugin file declares the public positioning.' );
npcink_governance_core_assert( false !== strpos( $main_plugin, 'Text Domain: npcink-governance-core' ), 'Main plugin file keeps the canonical text domain.' );
npcink_governance_core_assert( false !== strpos( $main_plugin, 'Domain Path: /languages' ), 'Main plugin file declares the bundled languages path.' );
npcink_governance_core_assert( false === strpos( $main_plugin, 'load_plugin_textdomain' ), 'Main plugin file lets WordPress.org load translations automatically.' );
npcink_governance_core_assert( false !== strpos( $main_plugin, 'register_activation_hook' ), 'Main plugin file registers activation hook.' );
npcink_governance_core_assert( false !== strpos( $main_plugin, 'plugins_loaded' ), 'Main plugin file boots after plugins_loaded.' );
npcink_governance_core_assert( false === strpos( $main_plugin, 'example.com' ), 'Main plugin header does not use placeholder Plugin URI.' );

$translation_glossary = npcink_governance_core_read( $root . '/docs/translation-glossary-zh.md' );
foreach ( array( 'Governance', 'Proposal', 'Commit preflight', 'Ability', 'Audit', 'App key' ) as $required ) {
	npcink_governance_core_assert( false !== strpos( $translation_glossary, $required ), 'Chinese translation glossary contains required term: ' . $required );
}

$translation_pot = npcink_governance_core_read( $root . '/languages/npcink-governance-core.pot' );
$translation_po  = npcink_governance_core_read( $root . '/languages/npcink-governance-core-zh_CN.po' );
npcink_governance_core_assert( '' !== $translation_pot, 'Bundled POT template exists.' );
npcink_governance_core_assert( '' !== $translation_po, 'Bundled zh_CN PO file exists.' );
npcink_governance_core_assert( is_readable( $root . '/languages/npcink-governance-core-zh_CN.mo' ), 'Bundled zh_CN MO file exists.' );
npcink_governance_core_assert( false !== strpos( $translation_po, '"Language: zh_CN\\n"' ), 'Bundled zh_CN PO declares zh_CN language.' );
npcink_governance_core_assert( false !== strpos( $translation_po, 'msgid "Review Queue"' ) && false !== strpos( $translation_po, 'msgstr "审核队列"' ), 'Bundled zh_CN PO translates Review Queue.' );
npcink_governance_core_assert( false !== strpos( $translation_po, 'msgid "Commit preflight has already issued an execution handoff for this approved proposal."' ), 'Bundled zh_CN PO keeps commit preflight source strings.' );

$readme = npcink_governance_core_read( $root . '/README.md' );
foreach (
	array(
		'Npcink AI governance layer for WordPress operations',
		'local WordPress AI operation governance control',
		'plane: it classifies, records, approves, preflights, and audits AI-initiated',
		'It does not plan, execute, route models, run workflows, or own',
		'It does not generate content',
		'Current Stage Governance Reliability',
		'Approval Policy Evaluator Standard',
		'Third-Party Ability Provider Guide',
		'Third-party ability providers can integrate without adopting',
		'allowlisted read-only planning',
		'Ability Recipe Orchestration Contract',
		'Article Writing Workflow Contract',
		'Cloud Bulk Article Run Contract',
		'workflow/task queues, batch execution consoles',
		'Review Queue, pending proposal queue',
		'Those terms do not permit workflow/task queue ownership',
		'GET /wp-json/npcink-governance-core/v1/capabilities',
		'GET /wp-json/npcink-governance-core/v1/contract',
		'POST /wp-json/npcink-governance-core/v1/apps',
		'POST /wp-json/npcink-governance-core/v1/proposals',
		'POST /wp-json/npcink-governance-core/v1/proposals/from-plan',
		'GET /wp-json/npcink-governance-core/v1/proposals/{proposal_id}',
		'POST /wp-json/npcink-governance-core/v1/proposals/{proposal_id}/approve',
		'POST /wp-json/npcink-governance-core/v1/proposals/{proposal_id}/commit-preflight',
		'POST /wp-json/npcink-governance-core/v1/read-requests',
		'GET /wp-json/npcink-governance-core/v1/read-requests/{request_id}',
		'POST /wp-json/npcink-governance-core/v1/read-requests/{request_id}/read-preflight',
		'Sensitive Read Authorization',
		'ADR-004: Suite Consolidation And Local Admin Consent',
		'ADR-005: Keep Core Independent And Standardize Channel Adapters',
		'ADR-006: Unattended Batch Automation Runtime Boundary',
		'ADR-007: Dedicated Local Automation Runtime Owner',
		'Local Automation Runtime Contract',
		'Local Automation Runtime Phase 1 Schema',
		'npcink-local-automation-runtime',
		'modules/local-automation-runtime/',
		'local-automation-runtime-dry-run-replay.json',
		'Jobs, leases, retry workers, scheduler state',
		'AI Development Workstream Summary',
		'Operation Classification Contract',
		'local admin consent with audit',
	) as $required
) {
	npcink_governance_core_assert( false !== strpos( $readme, $required ), 'README contains required phrase: ' . $required );
}

$wp_readme = npcink_governance_core_read( $root . '/readme.txt' );
foreach (
	array(
		'=== npcink-governance-core ===',
		'Stable tag: 0.1.0',
		'Npcink AI governance layer for WordPress operations.',
		'Open Npcink AI > Core',
		'Requires at least: 7.0',
		'Tested up to: 7.0',
		'Requires PHP: 8.0',
		'License: GPLv2 or later',
		'== Description ==',
	) as $required
) {
	npcink_governance_core_assert( false !== strpos( $wp_readme, $required ), 'WordPress readme contains required phrase: ' . $required );
}

$platform_baseline = npcink_governance_core_read( $root . '/docs/platform-baseline.md' );
foreach ( array( 'WordPress minimum: `7.0`', 'PHP minimum: `8.0`', '`npcink-cloud-addon`' ) as $required ) {
	npcink_governance_core_assert( false !== strpos( $platform_baseline, $required ), 'Platform baseline documents required phrase: ' . $required );
}

$distignore = npcink_governance_core_read( $root . '/.distignore' );
foreach ( array( 'tests', 'examples', 'docs', 'scripts', 'composer.json', 'AGENTS.md', '.sisyphus', '.workbuddy' ) as $required ) {
	npcink_governance_core_assert( false !== strpos( $distignore, $required ), 'Release distignore excludes development path: ' . $required );
}

$pull_request_template = npcink_governance_core_read( $root . '/.github/pull_request_template.md' );
foreach (
	array(
		'Core remains the governance layer for ability intake, proposals, approval/preflight, and audit.',
		'This does not add final WordPress write execution to Core.',
		'provider credential storage, model routing, workflow runtime, MCP runtime, Agent Gateway catalogs, task queues, batch execution consoles, or product workflow UX',
		'Reusable WordPress ability definitions remain outside Core',
	) as $required
) {
	npcink_governance_core_assert( false !== strpos( $pull_request_template, $required ), 'Pull request template contains Core boundary checkpoint: ' . $required );
}

$boundary_review_template = npcink_governance_core_read( $root . '/.github/ISSUE_TEMPLATE/boundary_review.yml' );
foreach (
	array(
		'Core owns proposal, approval, preflight, audit, or app-key governance for this change.',
		'Final WordPress write execution stays outside Core.',
		'provider credentials, model routing, workflow runtime, MCP runtime, Agent Gateway catalogs, task queues, batch execution consoles, operator runtime console, or product workflow UX',
		'Reusable WordPress ability definitions remain outside Core',
	) as $required
) {
	npcink_governance_core_assert( false !== strpos( $boundary_review_template, $required ), 'Boundary review template contains Core boundary checkpoint: ' . $required );
}

$positioning = npcink_governance_core_read( $root . '/docs/product-positioning.md' );
npcink_governance_core_assert( false !== strpos( $positioning, 'Npcink Governance Core governs AI-assisted WordPress operations.' ), 'Positioning keeps one-sentence product truth.' );
npcink_governance_core_assert( false !== strpos( $positioning, 'External explanation: Npcink AI governance layer for WordPress operations.' ), 'Positioning keeps public explanation.' );
npcink_governance_core_assert( false !== strpos( $positioning, '`npcink-abilities-toolkit`' ), 'Positioning names npcink-abilities-toolkit as ability owner.' );
npcink_governance_core_assert( false !== strpos( $positioning, 'Third-party ability providers' ), 'Positioning names third-party ability providers.' );
npcink_governance_core_assert( false !== strpos( $positioning, 'provider-neutral at the base proposal layer' ), 'Positioning keeps base proposals provider-neutral.' );
npcink_governance_core_assert( false !== strpos( $positioning, '`npcink-content-assistant`' ), 'Positioning names Content Assistant as product UX owner.' );
npcink_governance_core_assert( false !== strpos( $positioning, 'local admin consent with audit' ), 'Positioning documents local admin consent with audit.' );

$admin_menu_standard = npcink_governance_core_read( $root . '/docs/admin-menu-standard.md' );
foreach ( array( '`Npcink AI`', '`npcink-ai`', '`Core`', '`Adapter`', '`Abilities`', '`Cloud Addon`', '`Npcink AI -> Core`' ) as $required ) {
	npcink_governance_core_assert( false !== strpos( $admin_menu_standard, $required ), 'Admin menu standard documents required entry: ' . $required );
}

$admin_page = npcink_governance_core_read( $root . '/includes/Admin/Admin_Page.php' );
foreach ( array( 'PARENT_MENU_SLUG', 'add_menu_page', 'add_submenu_page', 'Core', 'admin.php' ) as $required ) {
	npcink_governance_core_assert( false !== strpos( $admin_page, $required ), 'Admin page implements shared menu contract: ' . $required );
}
$plugin_container = npcink_governance_core_read( $root . '/includes/Plugin.php' );
npcink_governance_core_assert( false !== strpos( $plugin_container, 'plugin_action_links_' ) && false !== strpos( $plugin_container, 'filter_plugin_action_links' ) && false !== strpos( $plugin_container, 'admin.php?page=npcink-governance-core' ), 'Plugin screen exposes a Settings shortcut to Core admin.' );
npcink_governance_core_assert( false !== strpos( $admin_page, "const PARENT_MENU_SLUG  = 'npcink-ai';" ), 'Admin page targets the shared Npcink AI parent menu slug.' );
npcink_governance_core_assert( false !== strpos( $admin_page, "__( 'Npcink AI', 'npcink-governance-core' )" ), 'Admin parent menu title is Npcink AI.' );
npcink_governance_core_assert( false !== strpos( $admin_page, "__( 'Npcink AI Overview', 'npcink-governance-core' )" ), 'Admin parent overview title is Npcink AI Overview.' );
npcink_governance_core_assert( false !== strpos( $admin_page, "__( 'Core', 'npcink-governance-core' ),\n\t\t\tself::MENU_CAPABILITY" ), 'Admin submenu title is Core.' );
npcink_governance_core_assert( false !== strpos( $admin_page, "'npcink-cloud-addon'" ), 'Admin overview links to the canonical Cloud Addon slug.' );
npcink_governance_core_assert( false !== strpos( $admin_page, "__( 'Cloud Addon', 'npcink-governance-core' )" ), 'Admin overview labels the Cloud Addon surface.' );

npcink_governance_core_assert( ! file_exists( $root . '/includes/Media/Media_Derivative_Settings.php' ), 'Core no longer owns media derivative product defaults.' );
npcink_governance_core_assert( false === strpos( $main_plugin, 'npcink_governance_core_get_media_derivative_settings' ), 'Core no longer exposes a media derivative settings helper for local product surfaces.' );
npcink_governance_core_assert( false === strpos( $main_plugin, 'npcink_governance_core_build_media_derivative_ability_input' ), 'Core no longer builds media derivative ability input for Toolbox handoffs.' );
npcink_governance_core_assert( false === strpos( $admin_page, "'media-policy'" ) && false === strpos( $admin_page, 'render_media_policy_settings' ), 'Core admin no longer exposes a Media Policy tab.' );
npcink_governance_core_assert( false !== strpos( $readme, 'Toolbox stores local media derivative defaults' ), 'README documents Toolbox as the media defaults owner.' );
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
	$project_text = npcink_governance_core_read( $project_file );
	npcink_governance_core_assert( false === strpos( $project_text, 'Magick AI -> Governance' ), 'Project docs no longer use old admin path in ' . $project_file );
	npcink_governance_core_assert( false === strpos( $project_text, 'Magick AI > Governance' ), 'Project docs no longer use old admin path in ' . $project_file );
}

$governance = npcink_governance_core_read( $root . '/docs/governance-contract.md' );
npcink_governance_core_assert( false !== strpos( $governance, 'proposal.created' ), 'Governance contract records proposal.created event.' );
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
		'smart_guarded_create_draft_auto_approved',
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
		'admin-only runtime discovery surface',
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
		'single draft-only create-draft proposals',
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
		'Trusted Magick AI Adapter approve-and-execute path',
		'scope_decision',
		'correlation_id',
	) as $required
) {
	npcink_governance_core_assert( false !== strpos( $app_auth_scope, $required ), 'App auth scope policy contains required text: ' . $required );
}

$core_operability = npcink_governance_core_read( $root . '/docs/core-governance-operability.md' );
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
npcink_governance_core_assert( false !== strpos( $next_stage_plan, 'productized acceptance in Magick AI Adapter' ), 'Next stage plan points productized OpenClaw acceptance to Adapter.' );
npcink_governance_core_assert( false !== strpos( $next_stage_plan, 'Adapter Handoff And Approval Policy Acceptance' ), 'Next stage plan links Adapter handoff and policy acceptance.' );
npcink_governance_core_assert( false !== strpos( $next_stage_plan, 'continue approve-and-execute implementation in Magick AI Adapter' ), 'Next stage plan moves approve-and-execute implementation to Adapter.' );
npcink_governance_core_assert( false !== strpos( $next_stage_plan, 'Manual local acceptance' ), 'Next stage plan records manual approval policy acceptance.' );
npcink_governance_core_assert( false !== strpos( $next_stage_plan, '/Users/muze/gitee/npcink-openclaw-adapter/docs/openclaw-consumer-acceptance.md' ), 'Next stage plan links Adapter acceptance checklist.' );
npcink_governance_core_assert( false !== strpos( $next_stage_plan, 'AI Provider Log Correlation Acceptance' ), 'Next stage plan includes AI provider log correlation acceptance.' );
npcink_governance_core_assert( false !== strpos( $next_stage_plan, 'real AI provider request log correlation is implemented and tested in' ), 'Next stage plan keeps provider log correlation implementation in Adapter.' );
npcink_governance_core_assert( false !== strpos( $next_stage_plan, 'Create Draft Governance Scenario' ), 'Next stage plan links create-draft scenario.' );
npcink_governance_core_assert( false !== strpos( $next_stage_plan, 'Set Post SEO Meta Governance Scenario' ), 'Next stage plan links set-post-seo-meta scenario.' );
npcink_governance_core_assert( false !== strpos( $next_stage_plan, 'Approve Comment Governance Scenario' ), 'Next stage plan links approve-comment scenario.' );
npcink_governance_core_assert( false !== strpos( $next_stage_plan, 'Taxonomy Terms Preview Governance Scenario' ), 'Next stage plan links taxonomy terms preview scenario.' );

$readme = npcink_governance_core_read( $root . '/README.md' );
npcink_governance_core_assert( false !== strpos( $readme, 'Agent MCP Entry Contract' ), 'README links Agent MCP Entry Contract.' );
npcink_governance_core_assert( false !== strpos( $readme, 'App Auth Scope Policy' ), 'README links App Auth Scope Policy.' );
npcink_governance_core_assert( false !== strpos( $readme, 'OpenClaw governance adapter example' ), 'README links OpenClaw governance adapter example.' );
npcink_governance_core_assert( false !== strpos( $readme, 'Core 0.4 Consumer Readiness' ), 'README links Core 0.4 Consumer Readiness.' );
npcink_governance_core_assert( false !== strpos( $readme, 'Core Governance Operability' ), 'README links Core Governance Operability.' );
npcink_governance_core_assert( false !== strpos( $readme, 'AI Provider Log Correlation' ), 'README links AI Provider Log Correlation.' );
npcink_governance_core_assert( false !== strpos( $readme, 'OpenClaw Execution Guidance' ), 'README links OpenClaw Execution Guidance.' );
npcink_governance_core_assert( false !== strpos( $readme, 'Adapter Handoff And Approval Policy Acceptance' ), 'README links Adapter handoff and approval policy acceptance.' );
npcink_governance_core_assert( false !== strpos( $readme, 'ADR-003: Keep Final Execution Outside Core For The Current Stage' ), 'README links ADR-003.' );
npcink_governance_core_assert( false !== strpos( $readme, 'Productized OpenClaw acceptance should be run from Magick AI Adapter' ), 'README points OpenClaw productized acceptance to Adapter.' );
npcink_governance_core_assert( false !== strpos( $readme, 'Create Draft Governance Scenario' ), 'README links Create Draft Governance Scenario.' );
npcink_governance_core_assert( false !== strpos( $readme, 'Set Post SEO Meta Governance Scenario' ), 'README links Set Post SEO Meta Governance Scenario.' );
npcink_governance_core_assert( false !== strpos( $readme, 'Approve Comment Governance Scenario' ), 'README links Approve Comment Governance Scenario.' );
npcink_governance_core_assert( false !== strpos( $readme, 'Taxonomy Terms Preview Governance Scenario' ), 'README links Taxonomy Terms Preview Governance Scenario.' );
npcink_governance_core_assert( false !== strpos( $readme, 'Article writing is now treated as local Ability recipe orchestration' ), 'README documents local Ability recipe orchestration boundary.' );
npcink_governance_core_assert( false !== strpos( $readme, 'Cloud must not generate article drafts' ), 'README prohibits Cloud writing generation.' );

$adapter_policy_acceptance = npcink_governance_core_read( $root . '/docs/adapter-handoff-and-approval-policy-acceptance.md' );
foreach (
	array(
		'Adapter Handoff And Approval Policy Acceptance',
		'The next execution work belongs in Magick AI Adapter',
		'/Users/muze/gitee/npcink-openclaw-adapter',
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

$app_key_repository = npcink_governance_core_read( $root . '/includes/Security/App_Key_Repository.php' );
foreach (
	array(
		'npcink_governance_core_app_keys',
		'secret_hash',
		'revoke_by_key_id',
		'revoked',
		'password_hash',
		'password_verify',
		'OFFSET %d',
		'latest_last_used_at',
		'expires_at',
		'last_used_ip_hash',
		'revoked_at',
		'revoked_reason',
		'hash_algorithm_version',
		'token_prefix',
		'is_expired',
		'sanitize_future_datetime',
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

$app_rate_limiter = npcink_governance_core_read( $root . '/includes/Security/App_Rate_Limiter.php' );
npcink_governance_core_assert( false !== strpos( $app_rate_limiter, 'npcink_governance_core_app_rate_limits' ), 'App rate limiter stores fixed-window counters.' );
npcink_governance_core_assert( false !== strpos( $app_rate_limiter, 'app_route_window' ), 'App rate limiter has unique app route window key.' );
npcink_governance_core_assert( false !== strpos( $app_rate_limiter, 'increment_existing_window' ), 'App rate limiter uses a conditional increment helper.' );
npcink_governance_core_assert( false !== strpos( $app_rate_limiter, 'request_count = request_count + 1' ), 'App rate limiter increments counters in SQL.' );
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
npcink_governance_core_assert( false !== strpos( $apps_controller, 'app.created' ), 'Apps REST route audits app creation.' );
npcink_governance_core_assert( false !== strpos( $apps_controller, 'can_manage' ), 'Apps REST route remains admin-only.' );
npcink_governance_core_assert( false !== strpos( $apps_controller, 'npcink_governance_core_app_audit_failed' ), 'Apps REST route fails app creation when audit cannot be written.' );

$adr_001 = npcink_governance_core_read( $root . '/docs/decisions/ADR-001-rebuild-core-as-governance-layer.md' );
$adr_002 = npcink_governance_core_read( $root . '/docs/decisions/ADR-002-no-workflow-runtime-in-core.md' );
$adr_003 = npcink_governance_core_read( $root . '/docs/decisions/ADR-003-keep-final-execution-outside-core.md' );
$adr_004 = npcink_governance_core_read( $root . '/docs/decisions/ADR-004-suite-consolidation-and-local-admin-consent.md' );
$adr_005 = npcink_governance_core_read( $root . '/docs/decisions/ADR-005-keep-core-independent-and-standardize-channel-adapters.md' );
$adr_006 = npcink_governance_core_read( $root . '/docs/decisions/ADR-006-unattended-batch-automation-runtime-boundary.md' );
$adr_007 = npcink_governance_core_read( $root . '/docs/decisions/ADR-007-dedicated-local-automation-runtime-owner.md' );
npcink_governance_core_assert( false !== strpos( $adr_001, 'Create a new standalone `npcink-governance-core` plugin' ), 'ADR-001 records rebuild decision.' );
npcink_governance_core_assert( false !== strpos( $adr_002, '`npcink-governance-core` must not implement a workflow runtime' ), 'ADR-002 bans workflow runtime ownership.' );
npcink_governance_core_assert( false !== strpos( $adr_003, 'Core remains governance-only' ), 'ADR-003 keeps Core governance-only for the current stage.' );
npcink_governance_core_assert( false !== strpos( $adr_003, 'adapter_after_core_preflight' ), 'ADR-003 keeps final execution in Adapter/product plugins.' );
npcink_governance_core_assert( false !== strpos( $adr_003, 'no Core `/execute`, `/proxy-execute`' ) && false !== strpos( $adr_003, 'commit route' ), 'ADR-003 blocks accidental Core execution routes.' );
npcink_governance_core_assert( false !== strpos( $adr_004, 'Local Admin Consent Model' ), 'ADR-004 documents local admin consent.' );
npcink_governance_core_assert( false !== strpos( $adr_004, 'The same plugin package may contain those modules' ), 'ADR-004 permits package consolidation without authority collapse.' );
npcink_governance_core_assert( false !== strpos( $adr_004, 'External, automated, batch, destructive, high-impact' ) && false !== strpos( $adr_004, 'previewed AI writes must not use local admin consent' ), 'ADR-004 keeps risky writes behind Core review.' );
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
npcink_governance_core_assert( false !== strpos( $adr_007, 'release packaging may bundle it inside' ) && false !== strpos( $adr_007, '`magick-ai-toolbox`' ), 'ADR-007 allows Toolbox release bundling.' );
npcink_governance_core_assert( false !== strpos( $adr_007, 'module path: `modules/local-automation-runtime/`' ), 'ADR-007 defines the Toolbox bundled module path.' );
npcink_governance_core_assert( false !== strpos( $adr_007, 'Toolbox fixed-flow' ) && false !== strpos( $adr_007, 'runtime state machine' ), 'ADR-007 keeps Toolbox fixed buttons out of runtime ownership.' );
npcink_governance_core_assert( false !== strpos( $adr_007, 'Phase 1 is contract and replay only' ), 'ADR-007 keeps Phase 1 contract and replay only.' );
npcink_governance_core_assert( false !== strpos( $adr_007, 'This Core pass does not create the development repository' ), 'ADR-007 keeps this Core pass implementation-free.' );

$local_automation_runtime_contract = npcink_governance_core_read( $root . '/docs/local-automation-runtime-contract.md' );
foreach (
	array(
		'Status: planning contract',
		'ADR-007: Dedicated Local Automation Runtime Owner',
		'npcink-local-automation-runtime',
		'magick-ai-toolbox',
		'modules/local-automation-runtime/',
		'Toolbox fixed',
		'does not add Core REST',
		'Core final write execution',
		'contract_version',
		'npcink_local_automation_runtime.v1',
		'job_id',
		'idempotency_key',
		'core_handoff',
		'Allowed job statuses',
		'awaiting_core_approval',
		'awaiting_core_preflight',
		'dead_lettered',
		'compare-and-set style',
		'eligibility_summary',
		'blocked_items',
		'Core proposal creation fails',
		'approved_input_hash',
		'lease_token',
		'lease_expires_at',
		'no infinite retries',
		'next_retry_at',
		'Action execution must carry an action-level idempotency key',
		'$outputs.prior_action.field',
		'approval scope',
		'kill switch for all scheduled unattended runs',
		'runtime.job.dead_lettered',
		'runtime.action.lease_acquired',
		'Before a supervised worker ships',
		'Before scheduled unattended jobs ship',
		'no unattended runtime, no scheduler, no worker, no runtime job table',
	) as $required
) {
	npcink_governance_core_assert( false !== strpos( $local_automation_runtime_contract, $required ), 'Local automation runtime contract contains required text: ' . $required );
}

$local_automation_phase_1_schema = npcink_governance_core_read( $root . '/docs/local-automation-runtime-phase-1-schema.md' );
foreach (
	array(
		'Status: planning schema',
		'npcink-local-automation-runtime',
		'npcink_local_automation_runtime.v1',
		'dry_run_replay',
		'background_execution',
		'Allowed Phase 1 `status` values',
		'Phase 1 fixtures must not use `running`',
		'core_execution` and `commit_execution` must both be `false`',
		'Action execution events are intentionally excluded from Phase 1 fixtures',
		'"phase": "phase_1_contract_only"',
		'"worker_created": false',
		'"scheduler_created": false',
		'"dead_letter_processor_created": false',
	) as $required
) {
	npcink_governance_core_assert( false !== strpos( $local_automation_phase_1_schema, $required ), 'Local automation runtime Phase 1 schema contains required text: ' . $required );
}

$local_automation_replay_text = npcink_governance_core_read( $root . '/tests/fixtures/local-automation-runtime-dry-run-replay.json' );
$local_automation_replay      = json_decode( $local_automation_replay_text, true );
npcink_governance_core_assert( is_array( $local_automation_replay ), 'Local automation runtime dry-run replay fixture is valid JSON.' );
npcink_governance_core_assert( 'npcink_local_automation_runtime.v1' === ( $local_automation_replay['contract_version'] ?? '' ), 'Local automation runtime replay fixture declares contract version.' );
npcink_governance_core_assert( 'dry_run_replay' === ( $local_automation_replay['mode'] ?? '' ), 'Local automation runtime replay fixture is dry-run replay only.' );
npcink_governance_core_assert( 'npcink-local-automation-runtime' === ( $local_automation_replay['runtime_owner'] ?? '' ), 'Local automation runtime replay fixture names the future owner.' );
npcink_governance_core_assert( false === ( $local_automation_replay['core_runtime_execution'] ?? true ), 'Local automation runtime replay fixture keeps Core runtime execution false.' );
npcink_governance_core_assert( false === ( $local_automation_replay['background_execution'] ?? true ), 'Local automation runtime replay fixture keeps background execution false.' );
npcink_governance_core_assert( isset( $local_automation_replay['job']['eligibility_summary'] ) && is_array( $local_automation_replay['job']['eligibility_summary'] ), 'Local automation runtime replay fixture includes eligibility summary.' );
npcink_governance_core_assert( isset( $local_automation_replay['job']['blocked_items'] ) && is_array( $local_automation_replay['job']['blocked_items'] ), 'Local automation runtime replay fixture includes blocked items.' );
npcink_governance_core_assert( isset( $local_automation_replay['job']['actions'] ) && 2 === count( $local_automation_replay['job']['actions'] ), 'Local automation runtime replay fixture includes two dry-run actions.' );
npcink_governance_core_assert( false === ( $local_automation_replay['core_handoff']['core_execution'] ?? true ), 'Local automation runtime replay fixture keeps handoff core_execution false.' );
npcink_governance_core_assert( false === ( $local_automation_replay['core_handoff']['commit_execution'] ?? true ), 'Local automation runtime replay fixture keeps handoff commit_execution false.' );
npcink_governance_core_assert( true === ( $local_automation_replay['operator_controls']['kill_switch'] ?? false ), 'Local automation runtime replay fixture includes kill switch control.' );
npcink_governance_core_assert( true === ( $local_automation_replay['acceptance']['schema_only'] ?? false ), 'Local automation runtime replay fixture is schema-only.' );
npcink_governance_core_assert( true === ( $local_automation_replay['acceptance']['dry_run_replay_only'] ?? false ), 'Local automation runtime replay fixture is replay-only.' );
npcink_governance_core_assert( false === ( $local_automation_replay['acceptance']['worker_created'] ?? true ), 'Local automation runtime replay fixture does not create a worker.' );
npcink_governance_core_assert( false === ( $local_automation_replay['acceptance']['scheduler_created'] ?? true ), 'Local automation runtime replay fixture does not create a scheduler.' );
npcink_governance_core_assert( false === ( $local_automation_replay['acceptance']['dead_letter_processor_created'] ?? true ), 'Local automation runtime replay fixture does not create a dead-letter processor.' );

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
	) as $required
) {
	npcink_governance_core_assert( false !== strpos( $operation_classification, $required ), 'Operation classification contract contains required text: ' . $required );
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
npcink_governance_core_assert( false !== strpos( $ability_adapter, 'npcink_abilities_toolkit_get_registered' ), 'Ability intake prefers npcink-abilities-toolkit public API.' );
npcink_governance_core_assert( false !== strpos( $ability_adapter, 'wp_get_abilities' ), 'Ability intake falls back to WordPress Abilities API.' );
npcink_governance_core_assert( false !== strpos( $ability_adapter, "'none'" ), 'Ability intake has missing-provider diagnostic state.' );
npcink_governance_core_assert( false !== strpos( $ability_adapter, 'execution_guidance' ), 'Ability intake adds capability execution guidance.' );
npcink_governance_core_assert( false !== strpos( $ability_adapter, "'governance_mode'" ), 'Ability intake exposes governance mode.' );
npcink_governance_core_assert( false !== strpos( $ability_adapter, "'execution_surface'" ), 'Ability intake exposes execution surface.' );
npcink_governance_core_assert( false !== strpos( $ability_adapter, "'core_proxy_execute'" ), 'Ability intake reports no Core proxy execution.' );
npcink_governance_core_assert( false !== strpos( $ability_adapter, "'direct_read'" ), 'Ability intake guides direct read abilities.' );
npcink_governance_core_assert( false !== strpos( $ability_adapter, "'proposal_required'" ), 'Ability intake guides proposal-required abilities.' );
npcink_governance_core_assert( false !== strpos( $ability_adapter, "'read_policy'" ), 'Ability intake exposes read policy.' );
npcink_governance_core_assert( false !== strpos( $ability_adapter, "'read_authorization_required'" ), 'Ability intake exposes read authorization required flag.' );
npcink_governance_core_assert( false !== strpos( $ability_adapter, "'requires_read_authorization'" ), 'Ability intake exposes Adapter-compatible read authorization flag.' );
npcink_governance_core_assert( false !== strpos( $ability_adapter, "'authorization_mode'" ), 'Ability intake exposes read authorization mode.' );
npcink_governance_core_assert( false !== strpos( $ability_adapter, "'read_authorization'" ), 'Ability intake exposes nested read authorization metadata.' );
npcink_governance_core_assert( false !== strpos( $ability_adapter, 'read_authorization_preflight_route' ), 'Ability intake exposes read authorization route guidance.' );
npcink_governance_core_assert( false !== strpos( $ability_adapter, 'core_read_authorization_required' ), 'Ability intake marks sensitive reads as Core authorization required.' );
npcink_governance_core_assert( false !== strpos( $ability_adapter, "'sensitivity'" ), 'Ability intake exposes read sensitivity.' );
npcink_governance_core_assert( false !== strpos( $ability_adapter, "'redaction_required'" ), 'Ability intake exposes read redaction requirement.' );
npcink_governance_core_assert( false !== strpos( $ability_adapter, 'infer_read_sensitivity' ), 'Ability intake infers read sensitivity when providers omit it.' );

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

$testing_strategy = npcink_governance_core_read( $root . '/docs/testing-strategy.md' );
npcink_governance_core_assert( false !== strpos( $testing_strategy, 'agent-workflow-replay.json' ), 'Testing strategy records shared replay fixture smoke coverage.' );
npcink_governance_core_assert( false !== strpos( $testing_strategy, 'composer test:fail-closed' ), 'Testing strategy documents fail-closed fault injection command.' );
npcink_governance_core_assert( false !== strpos( $testing_strategy, 'tests/fail-closed.php' ), 'Testing strategy points to the fail-closed fault injection test.' );
npcink_governance_core_assert( false !== strpos( $testing_strategy, 'primary `npcink-abilities-toolkit/create-draft` governance scenario' ), 'Testing strategy records primary create-draft scenario coverage.' );
npcink_governance_core_assert( false !== strpos( $testing_strategy, 'second `npcink-abilities-toolkit/set-post-seo-meta` governance scenario' ), 'Testing strategy records second set-post-seo-meta scenario coverage.' );
npcink_governance_core_assert( false !== strpos( $testing_strategy, 'third `npcink-abilities-toolkit/approve-comment` governance scenario' ), 'Testing strategy records third approve-comment scenario coverage.' );
npcink_governance_core_assert( false !== strpos( $testing_strategy, 'taxonomy terms preview governance scenario' ), 'Testing strategy records taxonomy terms preview scenario coverage.' );
npcink_governance_core_assert( false !== strpos( $testing_strategy, 'proposal `audit_timeline`' ), 'Testing strategy records governance operability coverage.' );
npcink_governance_core_assert( false !== strpos( $testing_strategy, 'commit-preflight `correlation_id`' ), 'Testing strategy records preflight correlation smoke coverage.' );
npcink_governance_core_assert( false !== strpos( $testing_strategy, 'trusted Adapter approval coverage' ), 'Testing strategy records trusted Adapter approval smoke coverage.' );
npcink_governance_core_assert( false !== strpos( $testing_strategy, 'Fail-closed governance paths' ), 'Testing strategy records fail-closed governance path coverage.' );
npcink_governance_core_assert( false !== strpos( $testing_strategy, 'proposal.policy_evaluated' ), 'Testing strategy records policy decision audit failure coverage.' );
npcink_governance_core_assert( false !== strpos( $testing_strategy, 'proposal.auto_approved' ), 'Testing strategy records auto approval audit failure coverage.' );
npcink_governance_core_assert( false !== strpos( $testing_strategy, 'workflow/task queue, batch execution, or operator runtime console logic' ), 'Testing strategy bans runtime queue and batch execution ownership precisely.' );
npcink_governance_core_assert( false !== strpos( $testing_strategy, 'Allowed governance terms such as Review Queue' ), 'Testing strategy distinguishes governance review terms from runtime queues.' );
npcink_governance_core_assert( false !== strpos( $testing_strategy, 'Governance Hardening Matrix' ), 'Testing strategy records the governance hardening matrix.' );
npcink_governance_core_assert( false !== strpos( $testing_strategy, 'Proposal state transition matrix' ), 'Testing strategy prioritizes lifecycle state transition matrix coverage.' );
npcink_governance_core_assert( false !== strpos( $testing_strategy, 'Commit preflight race and duplicate handoff' ), 'Testing strategy prioritizes commit preflight race and duplicate handoff coverage.' );
npcink_governance_core_assert( false !== strpos( $testing_strategy, 'Ability drift' ), 'Testing strategy prioritizes ability drift coverage.' );
npcink_governance_core_assert( false !== strpos( $testing_strategy, 'Audit completeness' ), 'Testing strategy prioritizes durable audit completeness coverage.' );
npcink_governance_core_assert( false !== strpos( $testing_strategy, 'Do not satisfy this matrix by adding Core execution' ), 'Testing strategy keeps hardening matrix inside Core non-execution boundary.' );

$reliability_standard = npcink_governance_core_read( $root . '/docs/current-stage-governance-reliability.md' );
foreach (
	array(
		'Core is the Npcink AI governance layer for WordPress operations',
		'Core does not own final execution',
		'app-key rotation and expiry automation are deferred',
		'Adapter or another real external client',
		'Governance must fail closed',
		'npcink_governance_core_proposal_audit_failed',
		'npcink_governance_core_proposal_decision_audit_failed',
		'npcink_governance_core_app_audit_failed',
		'do not return the one-time token',
		'The fault-injection gate must cover both the returned error code and the local',
	) as $required
) {
	npcink_governance_core_assert( false !== strpos( $reliability_standard, $required ), 'Reliability standard contains required text: ' . $required );
}

$composer_json = npcink_governance_core_read( $root . '/composer.json' );
foreach ( array( 'test:contracts', 'test:fail-closed', 'tests/fail-closed.php' ) as $required ) {
	npcink_governance_core_assert( false !== strpos( $composer_json, $required ), 'Composer scripts include required test command: ' . $required );
}
$composer_data = json_decode( $composer_json, true );
npcink_governance_core_assert( is_array( $composer_data ), 'Composer JSON parses for script contract checks.' );
$composer_scripts = is_array( $composer_data['scripts'] ?? null ) ? $composer_data['scripts'] : array();
npcink_governance_core_assert( isset( $composer_scripts['eval:project:review'] ), 'Composer scripts include optional project eval-lab review command.' );
npcink_governance_core_assert( false !== strpos( (string) $composer_scripts['eval:project:review'], 'task=project_boundary_review_triad' ), 'Project eval-lab review command targets the triad task.' );
npcink_governance_core_assert( false !== strpos( (string) $composer_scripts['eval:project:review'], '"project=$PWD"' ), 'Project eval-lab review command quotes the project path.' );
npcink_governance_core_assert( false !== strpos( (string) $composer_scripts['eval:project:review'], 'project_label=npcink-governance-core' ), 'Project eval-lab review command passes a redacted project label.' );
npcink_governance_core_assert( false !== strpos( (string) $composer_scripts['eval:project:review'], 'contract=project_boundary_review_triad.v1' ), 'Project eval-lab review command pins the output contract.' );
npcink_governance_core_assert( false !== strpos( (string) $composer_scripts['eval:project:review'], 'mode=working_diff' ), 'Project eval-lab review command pins the default working diff mode.' );
$default_gate_scripts = array( 'test', 'test:all', 'release:verify', 'package:release', 'prepare:release', 'plugin-check:release', 'check:wporg', 'smoke:wp', 'test:contracts', 'test:fail-closed' );
foreach ( $default_gate_scripts as $script_name ) {
	if ( ! isset( $composer_scripts[ $script_name ] ) ) {
		continue;
	}
	$script_value = $composer_scripts[ $script_name ];
	$script_text  = is_array( $script_value ) ? implode( "\n", array_map( 'strval', $script_value ) ) : (string) $script_value;
	foreach ( array( '@eval:', 'scripts/eval-lab.sh', 'eval-lab.sh', 'project_boundary_review_triad', 'gutenberg_judge_cross' ) as $forbidden ) {
		npcink_governance_core_assert( false === strpos( $script_text, $forbidden ), 'Default Composer gate ' . $script_name . ' does not invoke eval-lab via ' . $forbidden . '.' );
	}
}
foreach ( $composer_scripts as $script_name => $script_value ) {
	if ( str_starts_with( (string) $script_name, 'eval:' ) ) {
		continue;
	}
	$script_value = $composer_scripts[ $script_name ] ?? '';
	$script_text  = is_array( $script_value ) ? implode( "\n", array_map( 'strval', $script_value ) ) : (string) $script_value;
	npcink_governance_core_assert( false === strpos( $script_text, 'project_boundary_review_triad' ), 'Optional project eval-lab review is not part of ' . $script_name . '.' );
}

$fail_closed_test = npcink_governance_core_read( $root . '/tests/fail-closed.php' );
foreach (
	array(
		'Npcink_Governance_Core_Fail_Closed_WPDB',
		'fail_insert_tables',
		'npcink_governance_core_proposal_insert_failed',
		'npcink_governance_core_proposal_audit_failed',
		'npcink_governance_core_proposal_decision_audit_failed',
		'npcink_governance_core_app_insert_failed',
		'npcink_governance_core_app_audit_failed',
		'Unaudited proposal creation is deleted.',
		'rolls status back to pending',
		'App creation audit failure revokes the new key.',
	) as $required
) {
	npcink_governance_core_assert( false !== strpos( $fail_closed_test, $required ), 'Fail-closed fault test contains required behavior: ' . $required );
}

$development_workflow = npcink_governance_core_read( $root . '/docs/development-workflow.md' );
npcink_governance_core_assert( false !== strpos( $development_workflow, 'does not depend on the abandoned legacy Magick AI' ), 'Development workflow rejects the abandoned legacy Magick AI dependency.' );
npcink_governance_core_assert( false !== strpos( $development_workflow, 'Smoke Failure Classification' ), 'Development workflow documents smoke failure classification.' );
npcink_governance_core_assert( false !== strpos( $development_workflow, '[smoke:preflight:fail] environment:' ), 'Development workflow classifies smoke environment failures.' );
npcink_governance_core_assert( false !== strpos( $development_workflow, '[smoke:preflight:fail] toolkit:' ), 'Development workflow classifies smoke Toolkit setup failures.' );
npcink_governance_core_assert( false !== strpos( $development_workflow, 'do not copy Toolkit ability definitions into Core' ), 'Development workflow preserves Toolkit ability ownership during smoke fixes.' );
npcink_governance_core_assert( false !== strpos( $development_workflow, 'Core or Toolkit contract regression' ), 'Development workflow classifies post-preflight smoke assertion failures.' );
npcink_governance_core_assert( false === strpos( $development_workflow, 'magick-ai-root' ), 'Development workflow must not depend on magick-ai-root.' );

$ai_workstream_summary = npcink_governance_core_read( $root . '/docs/ai-development-workstream-summary.md' );
foreach (
	array(
		'Core boundary drift',
		'Weak local smoke evidence',
		'Core Boundary Regression Checks',
		'LocalWP Smoke Reliability',
		'Current Stop Point',
		'Do not keep adding guards merely because more strings or checks are possible.',
		'real review finding, smoke failure',
		'release-preparation issue, or maintainer priority change',
	) as $required
) {
	npcink_governance_core_assert( false !== strpos( $ai_workstream_summary, $required ), 'AI development workstream summary contains required text: ' . $required );
}

$smoke_wp_sh = npcink_governance_core_read( $root . '/tests/smoke-wp.sh' );
npcink_governance_core_assert( false !== strpos( $smoke_wp_sh, 'WP_CLI' ), 'WordPress smoke shell uses WP-CLI directly.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp_sh, 'smoke_preflight_note' ), 'WordPress smoke shell reports preflight diagnostics.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp_sh, 'smoke_preflight_fail' ), 'WordPress smoke shell fails environment setup before runtime assertions.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp_sh, 'wp-config.php' ), 'WordPress smoke shell verifies WP_PATH before invoking WP-CLI.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp_sh, 'npcink-abilities-toolkit/npcink-abilities-toolkit.php' ), 'WordPress smoke shell verifies the Toolkit plugin file before runtime assertions.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp_sh, 'NPCINK_ABILITIES_TOOLKIT_PATH' ), 'WordPress smoke shell reports the Toolkit source path assumption.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp_sh, 'readlink' ), 'WordPress smoke shell diagnoses Core plugin symlink state.' );
npcink_governance_core_assert( false === strpos( $smoke_wp_sh, 'magick-ai-root' ), 'WordPress smoke shell must not depend on magick-ai-root.' );

$smoke_wp = npcink_governance_core_read( $root . '/tests/smoke-wp.php' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'NPCINK_ABILITIES_TOOLKIT_PATH' ), 'WordPress smoke can locate the shared npcink-abilities-toolkit repository explicitly.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'agent-workflow-replay.json' ), 'WordPress smoke consumes the shared replay fixture.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'preferred bundle is discoverable by Core' ), 'WordPress smoke validates preferred bundle discovery.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'disallowed default ability requires approval in Core' ), 'WordPress smoke validates write-like defaults stay approval-gated.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'content/draft-preview' ), 'WordPress smoke rejects planning labels as proposal targets.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'npcink-abilities-toolkit/create-draft' ), 'WordPress smoke validates draft proposal governance.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'npcink_governance_core_smoke_assert_create_draft_contract' ), 'WordPress smoke has a dedicated create-draft contract check.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'create-draft input schema exposes governance control' ), 'WordPress smoke validates create-draft schema controls.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'preflight returns the dry-run proposal input without committing' ), 'WordPress smoke validates preflight keeps dry-run input without commit execution.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'npcink-abilities-toolkit/set-post-seo-meta' ), 'WordPress smoke validates SEO proposal governance.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'npcink_governance_core_smoke_assert_seo_meta_contract' ), 'WordPress smoke has a dedicated set-post-seo-meta contract check.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'set-post-seo-meta input schema exposes field/control' ), 'WordPress smoke validates set-post-seo-meta field controls.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'field_patch' ), 'WordPress smoke validates set-post-seo-meta field patch preview.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'npcink-abilities-toolkit/approve-comment' ), 'WordPress smoke validates comment moderation proposal governance.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'npcink_governance_core_smoke_assert_comment_approval_contract' ), 'WordPress smoke has a dedicated approve-comment contract check.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'npcink_governance_core_smoke_create_pending_comment' ), 'WordPress smoke creates a pending comment for approve-comment governance.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'approve-comment input schema exposes governance control' ), 'WordPress smoke validates approve-comment governance controls.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'target_action' ), 'WordPress smoke validates approve-comment target action preview.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'does not mutate comment status' ), 'WordPress smoke validates approve-comment preflight does not mutate comments.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'npcink-abilities-toolkit/propose-post-taxonomy-terms' ), 'WordPress smoke validates taxonomy proposal helper discovery.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'npcink-abilities-toolkit/set-post-terms' ), 'WordPress smoke validates taxonomy set-post-terms governance.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'npcink_governance_core_smoke_assert_taxonomy_terms_contract' ), 'WordPress smoke has a dedicated taxonomy terms contract check.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'npcink_governance_core_smoke_run_taxonomy_terms_preview' ), 'WordPress smoke runs taxonomy terms preview helper through WordPress Abilities API.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'taxonomy terms governance loop does not mutate post terms' ), 'WordPress smoke validates taxonomy terms preflight does not mutate post terms.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'taxonomy terms audit correlates commit preflight with set-post-terms' ), 'WordPress smoke validates taxonomy terms audit correlation.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'proposal detail includes audit timeline' ), 'WordPress smoke validates proposal audit timeline.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'commit preflight returns correlation id' ), 'WordPress smoke validates preflight correlation id.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'trusted Adapter app approves proposal with approval scope' ), 'WordPress smoke validates trusted Adapter approval scope.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'trusted Adapter execution handoff keeps Core final execution disabled' ), 'WordPress smoke validates trusted Adapter execution handoff.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'trusted Adapter approval audit stores app attribution and approve scope' ), 'WordPress smoke validates trusted Adapter approval audit attribution.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'audit endpoint filters by ability id' ), 'WordPress smoke validates ability audit filtering.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'audit endpoint filters by app id' ), 'WordPress smoke validates app audit filtering.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'scope decision denied' ), 'WordPress smoke validates denied scope decision attribution.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'scope decision rate_limited' ), 'WordPress smoke validates rate-limit scope decision attribution.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'app-authenticated proposal stores app attribution' ), 'WordPress smoke validates app proposal attribution.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'app-authenticated audit read is denied without audit scope' ), 'WordPress smoke validates denied app audit scope.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'app rate limit returns 429 after fixed window is exhausted' ), 'WordPress smoke validates app rate limiting.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'revoked app key returns 401' ), 'WordPress smoke validates revoked app key denial.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'npcink-abilities-toolkit/build-content-inventory-fix-plan' ), 'WordPress smoke validates content plan-to-proposal intake.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'npcink-abilities-toolkit/build-nonproduction-content-cleanup-plan' ), 'WordPress smoke validates cleanup plan-to-proposal intake.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'npcink-abilities-toolkit/build-media-inventory-fix-plan' ), 'WordPress smoke validates media plan-to-proposal intake.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'media delete candidates do not enter executable proposals by default' ), 'WordPress smoke validates default destructive media delete guard.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'requires-input proposal cannot enter committable state' ), 'WordPress smoke validates requires_input preflight blocking.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'output-reference plan creates one batch proposal' ), 'WordPress smoke validates output-reference plan batch proposal creation.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'output-reference batch proposal preserves depends_on on write action' ), 'WordPress smoke validates batch proposal dependency preservation.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'stale pending proposal expires before detail response' ), 'WordPress smoke validates stale proposal expiration.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'expired proposal can be archived' ), 'WordPress smoke validates proposal archiving.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'archived proposal can be reopened for review' ), 'WordPress smoke validates proposal reopening.' );

$capabilities_controller = npcink_governance_core_read( $root . '/includes/Rest/Capabilities_Controller.php' );
npcink_governance_core_assert( false !== strpos( $capabilities_controller, "'/capabilities'" ), 'Capabilities REST route is registered.' );
npcink_governance_core_assert( false !== strpos( $capabilities_controller, 'capabilities.listed' ), 'Capabilities route records audit event.' );

$contract_controller = npcink_governance_core_read( $root . '/includes/Rest/Contract_Controller.php' );
npcink_governance_core_assert( false !== strpos( $contract_controller, "'/contract'" ), 'Runtime contract REST route is registered.' );
npcink_governance_core_assert( false !== strpos( $contract_controller, 'can_manage' ), 'Runtime contract REST route is admin-only.' );
npcink_governance_core_assert( false !== strpos( $contract_controller, 'npcink_governance_core_contract.v1' ), 'Runtime contract exposes the Core contract schema.' );
npcink_governance_core_assert( false !== strpos( $contract_controller, "'core_proxy_execute'      => false" ), 'Runtime contract keeps Core proxy execution disabled.' );
npcink_governance_core_assert( false !== strpos( $contract_controller, "'commit_execution'        => false" ), 'Runtime contract keeps commit execution disabled.' );
npcink_governance_core_assert( false !== strpos( $contract_controller, "'provider_secret_storage' => false" ), 'Runtime contract keeps provider secret storage outside Core.' );
npcink_governance_core_assert( false !== strpos( $contract_controller, 'minimum_adapter_contract_version' ), 'Runtime contract exposes Adapter compatibility floor.' );
npcink_governance_core_assert( false !== strpos( $contract_controller, 'context_bindings' ), 'Runtime contract exposes context binding metadata.' );
npcink_governance_core_assert( false !== strpos( $contract_controller, "'fields'       => array( 'site_url', 'home_url', 'blog_id' )" ), 'Runtime contract declares site binding fields.' );
npcink_governance_core_assert( false !== strpos( $contract_controller, 'supported_when_forwarded_by_trusted_adapter' ), 'Runtime contract declares supported Adapter-forwarded client fingerprint binding.' );
npcink_governance_core_assert( false !== strpos( $contract_controller, "'field'      => 'signed_client_fingerprint'" ), 'Runtime contract declares the signed client fingerprint field.' );
npcink_governance_core_assert( false !== strpos( $contract_controller, 'forbidden_payloads' ), 'Runtime contract declares forbidden payload families.' );
npcink_governance_core_assert( false !== strpos( $contract_controller, 'adapter_or_host_after_core_preflight' ), 'Runtime contract leaves final writes with the adapter or host after preflight.' );

$proposals_controller = npcink_governance_core_read( $root . '/includes/Rest/Proposals_Controller.php' );
npcink_governance_core_assert( false !== strpos( $proposals_controller, "'/proposals'" ), 'Proposals REST route is registered.' );
npcink_governance_core_assert( false !== strpos( $proposals_controller, 'Observability::emit' ), 'Proposal REST operations emit local observability events.' );
npcink_governance_core_assert( false !== strpos( $proposals_controller, 'core.proposal.create' ), 'Proposal create emits operation observability.' );
npcink_governance_core_assert( false !== strpos( $proposals_controller, 'core.proposal.plan_ingest' ), 'Plan intake emits operation observability.' );
npcink_governance_core_assert( false !== strpos( $proposals_controller, 'core.proposal.approve' ), 'Proposal approve emits operation observability.' );
npcink_governance_core_assert( false !== strpos( $proposals_controller, 'core.proposal.reject' ), 'Proposal reject emits operation observability.' );
npcink_governance_core_assert( false !== strpos( $proposals_controller, 'core.commit.preflight' ), 'Commit preflight emits operation observability.' );
npcink_governance_core_assert( false !== strpos( $proposals_controller, 'core.proposal.record_execution' ), 'Proposal execution record emits operation observability.' );
npcink_governance_core_assert( false !== strpos( $proposals_controller, 'npcink_governance_core_proposal_items_blocked' ), 'Commit preflight classifies blocked proposal observability.' );
npcink_governance_core_assert( false !== strpos( $proposals_controller, "'/proposals/from-plan'" ), 'Plan-to-proposal REST route is registered.' );
npcink_governance_core_assert( false !== strpos( $proposals_controller, 'create_proposals_from_plan' ), 'Plan-to-proposal REST callback is registered.' );
npcink_governance_core_assert( false !== strpos( $proposals_controller, 'get_proposal' ), 'Proposal detail REST callback is registered.' );
npcink_governance_core_assert( false !== strpos( $proposals_controller, "/approve'" ), 'Proposal approve REST route is registered.' );
npcink_governance_core_assert( false !== strpos( $proposals_controller, "/reject'" ), 'Proposal reject REST route is registered.' );
npcink_governance_core_assert( false !== strpos( $proposals_controller, "/commit-preflight'" ), 'Proposal commit preflight REST route is registered.' );
npcink_governance_core_assert( false !== strpos( $proposals_controller, "/record-execution'" ), 'Proposal execution record REST route is registered.' );
npcink_governance_core_assert( false !== strpos( $proposals_controller, "'ability_id'" ), 'Proposals route requires ability_id.' );
npcink_governance_core_assert( false !== strpos( $proposals_controller, 'audit_timeline' ), 'Proposal detail REST route returns audit timeline.' );
npcink_governance_core_assert( false !== strpos( $proposals_controller, 'expire_stale_pending' ), 'Proposal REST routes expire stale pending proposals before reads.' );

$read_requests_controller = npcink_governance_core_read( $root . '/includes/Rest/Read_Requests_Controller.php' );
npcink_governance_core_assert( false !== strpos( $read_requests_controller, "'/read-requests'" ), 'Read requests REST collection route is registered.' );
npcink_governance_core_assert( false !== strpos( $read_requests_controller, "/approve'" ), 'Read request approve REST route is registered.' );
npcink_governance_core_assert( false !== strpos( $read_requests_controller, "/reject'" ), 'Read request reject REST route is registered.' );
npcink_governance_core_assert( false !== strpos( $read_requests_controller, "/read-preflight'" ), 'Read request preflight REST route is registered.' );
npcink_governance_core_assert( false !== strpos( $read_requests_controller, 'read_preflight' ), 'Read request REST controller exposes read preflight callback.' );

$read_request_service = npcink_governance_core_read( $root . '/includes/Governance/Read_Request_Service.php' );
$read_request_repository = npcink_governance_core_read( $root . '/includes/Governance/Read_Request_Repository.php' );
foreach (
	array(
		'read_request.created',
		'read_request.approved',
		'read_request.rejected',
		'read_request.expired',
		'read_request.preflighted',
		'read_request.preflight_failed',
		'read_request.consumed',
		'read_authorization_context',
		'read_authorization_granted',
		'core_authorization_truth',
		'commit_execution',
		'write_execution',
		'approved_input_hash',
		'signed_client_context',
		'signed_client_fingerprint',
		'site_binding_context',
		"function_exists( 'site_url' ) ? site_url() : ''",
		"function_exists( 'home_url' ) ? home_url() : ''",
		"function_exists( 'get_current_blog_id' ) ? get_current_blog_id() : 0",
		'ability_mismatch',
		'input_mismatch',
		'CORE_MAX_ROWS',
		'CORE_MAX_TAIL_LINES',
	) as $required
) {
	npcink_governance_core_assert( false !== strpos( $read_request_service, $required ), 'Read request service contains required text: ' . $required );
}
npcink_governance_core_assert( false !== strpos( $read_request_repository, 'npcink_governance_core_read_requests' ), 'Read request repository stores Core read request table.' );
npcink_governance_core_assert( false !== strpos( $read_request_repository, 'consumed_at' ), 'Read request repository stores one-time consumption timestamp.' );
npcink_governance_core_assert( false !== strpos( $read_request_repository, 'update_status_when' ), 'Read request repository supports conditional status transitions.' );
npcink_governance_core_assert( false === strpos( $read_request_repository, 'public function update_status(' ), 'Read request repository does not expose unconditional status transitions as public API.' );
npcink_governance_core_assert( false !== strpos( $read_request_repository, '\'status\'     => $expected_status' ), 'Read request repository binds current status in conditional updates.' );
npcink_governance_core_assert( false !== strpos( $read_request_repository, 'Sensitive_Data_Redactor::sanitize_payload' ), 'Read request repository uses shared sensitive data redaction.' );
npcink_governance_core_assert( false !== strpos( $read_request_repository, 'redact_secret_string' ), 'Read request repository redacts secret-shaped strings.' );

$proposal_service = npcink_governance_core_read( $root . '/includes/Governance/Proposal_Service.php' );
$proposal_repository = npcink_governance_core_read( $root . '/includes/Governance/Proposal_Repository.php' );
$approval_policy_evaluator = npcink_governance_core_read( $root . '/includes/Governance/Approval_Policy_Evaluator.php' );
$wporg_guard = npcink_governance_core_read( $root . '/scripts/check-wordpress-org-review-rules.php' );
foreach (
	array(
		'Approval_Policy_Evaluator',
		'Approval_Policy_Strategy',
		'Manual_Approval_Policy_Strategy',
		'Smart_Guarded_Approval_Policy_Strategy',
		'Dev_Allow_All_Approval_Policy_Strategy',
		'manual_required',
		'auto_approved',
		'blocked',
		'manual',
		'guarded',
		'trusted_local',
		'break_glass',
		'core-approval-policy-v1',
		'OPTION_POLICY_MODE',
		'MODE_SMART_GUARDED',
		'MODE_DEV_ALLOW_ALL',
		'stored_policy_mode',
		'is_allowed_policy_mode',
		'CLEANUP_BATCH_MAX_ACTIONS',
		'CREATE_DRAFT_MAX_CONTENT_BYTES',
		'AUTO_APPROVAL_HOURLY_LIMIT',
		'AUTO_APPROVAL_DAILY_LIMIT',
		'smart_guarded_cleanup_auto_approved',
		'smart_guarded_create_draft_auto_approved',
		'dev_allow_all_auto_approved',
		'dev_allow_all_rejected_disabled',
		'commit_preflight_still_required',
		'NPCINK_GOVERNANCE_CORE_ENABLE_DEV_ALLOW_ALL',
		'guarded_create_draft_rejected_status',
		'guarded_cleanup_rejected_missing_test_content_evidence',
		'build-nonproduction-content-cleanup-plan',
		'npcink-abilities-toolkit/trash-post',
		'npcink-abilities-toolkit/create-draft',
		'consume_auto_approval_quota',
		'AUTO_APPROVAL_TRANSIENT_PREFIX . $key_suffix',
	) as $required
) {
	npcink_governance_core_assert( false !== strpos( $approval_policy_evaluator, $required ), 'Approval policy evaluator contains required text: ' . $required );
}
npcink_governance_core_assert( false === strpos( $approval_policy_evaluator, 'MODE_DRY_RUN_GUARDED' ), 'Approval policy evaluator removes dry-run guarded mode.' );
npcink_governance_core_assert( false === strpos( $approval_policy_evaluator, 'MODE_LOCAL_GUARDED' ), 'Approval policy evaluator removes local guarded mode.' );
npcink_governance_core_assert( false === strpos( $approval_policy_evaluator, 'auto_approval_dry_run_only' ), 'Approval policy evaluator removes dry-run-only approval reason.' );
npcink_governance_core_assert( false === strpos( $approval_policy_evaluator, 'local_guarded_cleanup_auto_approved' ), 'Approval policy evaluator removes local guarded cleanup reason.' );
npcink_governance_core_assert( false === strpos( $approval_policy_evaluator, 'local_guarded_create_draft_auto_approved' ), 'Approval policy evaluator removes local guarded create-draft reason.' );
npcink_governance_core_assert( false === strpos( $approval_policy_evaluator, 'set_transient( $prefixed_key' ), 'Approval policy evaluator does not pass variable-only transient keys.' );
npcink_governance_core_assert( false !== strpos( $wporg_guard, 'variable-only key' ), 'WordPress.org guard rejects variable-only transient keys.' );
npcink_governance_core_assert( false !== strpos( $wporg_guard, 'prefix visible at the call site' ), 'WordPress.org guard requires transient prefixes at the call site.' );
npcink_governance_core_assert( false !== strpos( $proposal_repository, 'STATUS_EXPIRED' ), 'Proposal repository defines expired status.' );
npcink_governance_core_assert( false !== strpos( $proposal_repository, 'STATUS_ARCHIVED' ), 'Proposal repository defines archived status.' );
npcink_governance_core_assert( false !== strpos( $proposal_repository, 'STATUS_EXECUTED' ), 'Proposal repository defines executed status.' );
npcink_governance_core_assert( false !== strpos( $proposal_repository, 'STATUS_EXECUTION_FAILED' ), 'Proposal repository defines execution failed status.' );
npcink_governance_core_assert( false !== strpos( $proposal_repository, 'list_stale_pending' ), 'Proposal repository can list stale pending proposals.' );
npcink_governance_core_assert( false !== strpos( $proposal_repository, 'list_pending_for_guardrail' ), 'Proposal repository can list pending proposals for create guardrails.' );
npcink_governance_core_assert( false !== strpos( $proposal_repository, 'pending_quota_key' ), 'Proposal repository stores indexed pending quota keys.' );
npcink_governance_core_assert( false === strpos( $proposal_repository, 'caller_json LIKE' ), 'Proposal repository does not scan caller JSON for guardrail quota lookup.' );
npcink_governance_core_assert( false !== strpos( $proposal_repository, 'count_by_status' ), 'Proposal repository can count status queues.' );
npcink_governance_core_assert( false !== strpos( $proposal_repository, 'OFFSET %d' ), 'Proposal repository supports paginated admin lists.' );
npcink_governance_core_assert( false !== strpos( $proposal_repository, 'npcink_governance_core_proposal_insert_failed' ), 'Proposal repository returns a stable insert failure error.' );
npcink_governance_core_assert( false !== strpos( $proposal_repository, 'delete_by_proposal_id' ), 'Proposal repository can remove unaudited created proposals.' );
npcink_governance_core_assert( false !== strpos( $proposal_repository, 'display_id_for_proposal_id' ), 'Proposal repository derives stable human-facing display ids.' );
npcink_governance_core_assert( false !== strpos( $proposal_repository, "'display_id'" ), 'Proposal rows expose a display id without replacing proposal_id.' );
npcink_governance_core_assert( false !== strpos( $proposal_repository, 'find_by_display_id' ), 'Proposal repository supports admin lookup by display id.' );
npcink_governance_core_assert( false !== strpos( $proposal_repository, 'ORDER BY id DESC LIMIT 2' ), 'Proposal display id lookup detects non-unique aliases instead of choosing an arbitrary row.' );
npcink_governance_core_assert( false !== strpos( $proposal_repository, 'policy_fields_from_caller' ), 'Proposal repository promotes stored policy fields into responses.' );
npcink_governance_core_assert( false !== strpos( $proposal_repository, 'policy_decision' ), 'Proposal repository returns policy_decision.' );
npcink_governance_core_assert( false !== strpos( $proposal_repository, 'policy_reasons' ), 'Proposal repository returns policy_reasons.' );
npcink_governance_core_assert( false !== strpos( $proposal_repository, 'sanitize_input_for_ability' ), 'Proposal repository sanitizes proposal input with ability-aware context.' );
npcink_governance_core_assert( false !== strpos( $proposal_repository, 'is_create_draft_html_input' ), 'Proposal repository detects create-draft HTML content input narrowly.' );
npcink_governance_core_assert( false !== strpos( $proposal_repository, 'wp_kses_post( $content )' ), 'Proposal repository preserves create-draft HTML only through WordPress safe post KSES.' );
npcink_governance_core_assert( false !== strpos( $proposal_repository, "\$input['content_format'] ?? ''" ), 'Proposal repository requires content_format before preserving create-draft HTML.' );
npcink_governance_core_assert( false !== strpos( $proposal_repository, "\$clean['write_actions'][ \$index ]['input']['content']" ), 'Proposal repository preserves safe create-draft HTML inside batch write actions.' );
npcink_governance_core_assert( false !== strpos( $proposal_repository, 'is_update_post_blocks_input' ), 'Proposal repository detects update-post-blocks input narrowly.' );
npcink_governance_core_assert( false !== strpos( $proposal_repository, 'sanitize_update_post_blocks_input' ), 'Proposal repository preserves update-post-blocks input through ability-aware sanitization.' );
npcink_governance_core_assert( false !== strpos( $proposal_repository, 'sanitize_block_payload_key' ), 'Proposal repository preserves Gutenberg block object key case safely.' );
npcink_governance_core_assert( false !== strpos( $proposal_repository, '/[^A-Za-z0-9_-]/' ), 'Proposal repository block key sanitizer does not lowercase camelCase Gutenberg keys.' );
npcink_governance_core_assert( false !== strpos( $proposal_repository, "\$clean['write_actions'][ \$index ]['input'] = \$this->sanitize_update_post_blocks_input" ), 'Proposal repository preserves update-post-blocks keys inside batch write actions.' );
npcink_governance_core_assert( false !== strpos( $fail_closed_test, 'update-post-blocks preserves blockName key case' ), 'Fail-closed tests assert update-post-blocks blockName is not lowercased.' );
npcink_governance_core_assert( false !== strpos( $fail_closed_test, 'update-post-blocks preserves attrs contentSize key case' ), 'Fail-closed tests assert update-post-blocks attrs camelCase is not lowercased.' );
npcink_governance_core_assert( false !== strpos( $fail_closed_test, 'batch update-post-blocks preserves blockName key case' ), 'Fail-closed tests assert batch update-post-blocks blockName is not lowercased.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'proposal.created' ), 'Proposal service records proposal.created audit event.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'proposal.policy_evaluated' ), 'Proposal service records policy evaluation audit event.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'proposal.auto_approved' ), 'Proposal service records auto approval audit event.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'npcink_governance_core_auto_approval_audit_failed' ), 'Proposal service fails closed when auto approval audit fails.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'npcink_governance_core_auto_approval_quota_failed' ), 'Proposal service fails closed when auto approval quota cannot be consumed.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'proposal.deduplicated' ), 'Proposal service records proposal.deduplicated audit event.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'proposal.quota_blocked' ), 'Proposal service records proposal.quota_blocked audit event.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'Ability_Registry_Adapter' ), 'Proposal service validates target abilities against ability intake.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'npcink_governance_core_ability_not_available' ), 'Proposal service rejects unavailable target abilities.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'proposal.approved' ), 'Proposal service records proposal.approved audit event.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'proposal.rejected' ), 'Proposal service records proposal.rejected audit event.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'proposal.expired' ), 'Proposal service records proposal.expired audit event.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'proposal.archived' ), 'Proposal service records proposal.archived audit event.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'proposal.reopened' ), 'Proposal service records proposal.reopened audit event.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'record_execution_result' ), 'Proposal service records post-preflight execution results.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'proposal.executed' ), 'Proposal service records proposal.executed audit event.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'proposal.execution_failed' ), 'Proposal service records proposal.execution_failed audit event.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'npcink_governance_core_execution_record_preflight_missing' ), 'Proposal service requires execution records to bind to preflight.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'npcink_governance_core_execution_record_audit_failed' ), 'Proposal service fails closed when execution record audit fails.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'PENDING_TTL_SECONDS' ), 'Proposal service defines a pending review TTL.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'PENDING_QUOTA_PER_APP' ), 'Proposal service defines an app pending proposal quota.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'PENDING_QUOTA_PER_USER' ), 'Proposal service defines a user pending proposal quota.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'npcink_governance_core_pending_proposal_quota_exceeded' ), 'Proposal service blocks callers with too many pending proposals.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'core_guardrails' ), 'Proposal service stores non-secret proposal creation guardrail metadata.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'core_policy' ), 'Proposal service stores non-secret policy decision metadata.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'npcink_governance_core_policy_decision_audit_failed' ), 'Proposal service fails closed when policy decision audit fails.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'deduplicated' ), 'Proposal service returns existing pending duplicates.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'stable_input_hash' ), 'Proposal service hashes proposal input after ability-aware persistence sanitization.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, '$this->proposals->sanitize_input_for_ability' ), 'Proposal service input hashes match repository persistence sanitization.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'proposal.listed' ), 'Proposal service records proposal.listed audit event.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'proposal.viewed' ), 'Proposal service records proposal.viewed audit event.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'audit_timeline' ), 'Proposal service exposes proposal audit timeline.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, "'pending'" ), 'Proposal service only transitions pending proposals.' );
npcink_governance_core_assert( false !== strpos( $proposal_repository, 'update_status_when' ), 'Proposal repository supports conditional status transitions.' );
npcink_governance_core_assert( false === strpos( $proposal_repository, 'public function update_status(' ), 'Proposal repository does not expose unconditional status transitions as public API.' );
npcink_governance_core_assert( false !== strpos( $proposal_repository, 'reopen_when' ), 'Proposal repository supports conditional reopen transitions.' );
npcink_governance_core_assert( false !== strpos( $proposal_repository, 'Sensitive_Data_Redactor::sanitize_payload' ), 'Proposal repository uses shared sensitive data redaction.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'npcink_governance_core_ability_not_available' ), 'Proposal service rejects unavailable target abilities.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, '$this->abilities->find' ), 'Proposal service validates against ability intake.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'npcink_governance_core_proposal_audit_failed' ), 'Proposal service fails closed when creation audit fails.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'npcink_governance_core_proposal_decision_audit_failed' ), 'Proposal service fails closed when decision audit fails.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'audit_failed_error' ), 'Proposal service uses stable audit failure errors.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'update_status_when( $proposal_id, $status, (string) $existing' ), 'Proposal service rolls back decision status when audit fails.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'update_status_when( $proposal_id, Proposal_Repository::STATUS_APPROVED, Proposal_Repository::STATUS_PENDING' ), 'Proposal service rolls back auto approval status when audit fails.' );
npcink_governance_core_assert( false === strpos( $proposal_service, 'confirm_token' ), 'Proposal service does not use confirm_token.' );
npcink_governance_core_assert( false === strpos( $proposal_service, 'write_confirmed' ), 'Proposal service does not use write_confirmed.' );

$commit_preflight_service = npcink_governance_core_read( $root . '/includes/Governance/Commit_Preflight_Service.php' );
npcink_governance_core_assert( false !== strpos( $commit_preflight_service, 'commit.preflighted' ), 'Commit preflight records commit.preflighted audit event.' );
npcink_governance_core_assert( false !== strpos( $commit_preflight_service, 'approval_commit_authorized' ), 'Commit preflight returns approval context.' );
npcink_governance_core_assert( false !== strpos( $commit_preflight_service, 'commit_execution' ), 'Commit preflight explicitly reports no commit execution.' );
npcink_governance_core_assert( false !== strpos( $commit_preflight_service, 'correlation_id' ), 'Commit preflight returns and audits correlation id.' );
npcink_governance_core_assert( false !== strpos( $commit_preflight_service, 'approved_input_hash' ), 'Commit preflight binds approval context to approved input hash.' );
npcink_governance_core_assert( false !== strpos( $commit_preflight_service, 'approved_preview_hash' ), 'Commit preflight binds approval context to approved preview hash.' );
npcink_governance_core_assert( false !== strpos( $commit_preflight_service, 'policy_version' ), 'Commit preflight returns a policy version for Adapter binding.' );
npcink_governance_core_assert( false !== strpos( $commit_preflight_service, 'PREFLIGHT_TTL_SECONDS' ), 'Commit preflight declares a bounded handoff TTL.' );
npcink_governance_core_assert( false !== strpos( $commit_preflight_service, 'signed_client_context' ), 'Commit preflight binds signed Adapter client fingerprint when present.' );
npcink_governance_core_assert( false !== strpos( $commit_preflight_service, 'site_binding_context' ), 'Commit preflight has a site binding helper.' );
npcink_governance_core_assert( false !== strpos( $commit_preflight_service, "function_exists( 'site_url' ) ? site_url() : ''" ), 'Commit preflight binds site_url.' );
npcink_governance_core_assert( false !== strpos( $commit_preflight_service, "function_exists( 'home_url' ) ? home_url() : ''" ), 'Commit preflight binds home_url.' );
npcink_governance_core_assert( false !== strpos( $commit_preflight_service, "function_exists( 'get_current_blog_id' ) ? get_current_blog_id() : 0" ), 'Commit preflight binds blog_id.' );
npcink_governance_core_assert( false !== strpos( $commit_preflight_service, 'payload_hash' ), 'Commit preflight has stable payload hash generation.' );
npcink_governance_core_assert( false !== strpos( $commit_preflight_service, 'new_correlation_id' ), 'Commit preflight generates a correlation id.' );
npcink_governance_core_assert( false !== strpos( $commit_preflight_service, 'proposal_item_preflight' ), 'Commit preflight evaluates proposal item readiness.' );
npcink_governance_core_assert( false !== strpos( $commit_preflight_service, 'batch_review_summary' ), 'Commit preflight returns batch review summary when a proposal has one.' );
npcink_governance_core_assert( false !== strpos( $commit_preflight_service, 'batch_review_summary_preflight' ), 'Commit preflight bounds batch review summary response shape.' );
npcink_governance_core_assert( false !== strpos( $commit_preflight_service, 'npcink_governance_core_proposal_items_blocked' ), 'Commit preflight blocks incomplete proposal items.' );
npcink_governance_core_assert( false !== strpos( $commit_preflight_service, 'execution_handoff' ), 'Commit preflight returns adapter execution handoff.' );
npcink_governance_core_assert( false !== strpos( $commit_preflight_service, 'adapter_after_core_preflight' ), 'Commit preflight handoff points execution to Adapter.' );
npcink_governance_core_assert( false !== strpos( $commit_preflight_service, 'confirm_token' ), 'Commit preflight rejects confirm_token input.' );
npcink_governance_core_assert( false !== strpos( $commit_preflight_service, 'write_confirmed' ), 'Commit preflight rejects write_confirmed input.' );

$plan_proposal_service = npcink_governance_core_read( $root . '/includes/Governance/Plan_Proposal_Service.php' );
foreach (
	array(
		'Plan_Proposal_Service',
		'npcink-abilities-toolkit/build-content-inventory-fix-plan',
		'npcink-abilities-toolkit/build-nonproduction-content-cleanup-plan',
		'npcink-abilities-toolkit/build-media-inventory-fix-plan',
		'npcink-abilities-toolkit/build-media-reference-repair-plan',
		'npcink-abilities-toolkit/build-media-settings-reference-repair-plan',
		'npcink-abilities-toolkit/build-media-optimization-plan',
		'npcink-abilities-toolkit/build-media-adoption-enhancement-plan',
		'npcink-abilities-toolkit/build-media-rename-plan',
		'npcink-abilities-toolkit/build-article-optimization-apply-plan',
		'npcink-abilities-toolkit/build-article-block-plan',
		'npcink-abilities-toolkit/build-pattern-page-plan',
		'npcink-abilities-toolkit/build-block-theme-site-plan',
		'npcink-toolbox/build-article-write-plan',
		'npcink-toolbox/build-article-batch-write-plan',
		'npcink-toolbox/build-article-media-batch-write-plan',
		'npcink-toolbox/build-image-candidate-adoption-plan',
		'npcink-toolbox/build-site-knowledge-review-plan',
		'npcink-toolbox/build-nightly-inspection-review-plan',
		'npcink-toolbox/build-content-metadata-apply-plan',
		'proposal.plan_ingested',
		'npcink-abilities-toolkit/delete-media-permanently',
		'destructive_media_delete_not_explicitly_included',
		'validate_article_write_plan_contract',
		'validate_article_batch_write_plan_contract',
		'validate_article_block_plan_contract',
		'validate_article_media_batch_write_plan_contract',
		'validate_image_candidate_adoption_plan_contract',
		'validate_site_knowledge_review_plan_contract',
		'validate_nightly_inspection_review_plan_contract',
		'validate_content_metadata_apply_plan_contract',
		'validate_media_optimization_plan_contract',
		'validate_media_adoption_enhancement_plan_contract',
		'validate_media_rename_plan_contract',
		'validate_article_optimization_apply_plan_contract',
		'validate_pattern_page_plan_contract',
		'validate_block_theme_site_plan_contract',
		'article_workflow_preview',
		'article_batch_workflow_preview',
		'article_media_batch_workflow_preview',
		'media_optimization_preview',
		'media_optimization_proposal_summary',
		'media_adoption_enhancement_preview',
		'media_rename_preview',
		'article_optimization_preview',
		'pattern_page_preview',
		'block_theme_site_preview',
		'site_knowledge_review_preview',
		'nightly_inspection_review_preview',
		'nightly_inspection_core_intake_package',
		'nightly_inspection_selected_review_items',
		'content_metadata_apply_preview',
		'article_workflow_artifact_keys',
		'article_write_plan',
		'article_batch_write_plan',
		'article_media_batch_write_plan',
		'article_optimization_apply_plan',
		'article_block_plan',
		'block_theme_site_plan',
		'image_candidate_adoption_plan',
		'site_knowledge_review_plan',
		'nightly_site_inspection_review_plan',
		'nightly_site_inspection_core_review_plan.v1',
		'content_metadata_apply_plan',
		'image_candidate.v1',
		'npcink_governance_core_site_knowledge_ready_rejected',
		'npcink_governance_core_site_knowledge_evidence_missing',
		'npcink_governance_core_nightly_inspection_ready_rejected',
		'npcink_governance_core_nightly_inspection_evidence_missing',
		'npcink_governance_core_nightly_inspection_review_item_missing',
		'draft_selected_review_item_before_commit_preflight',
		'selected_review_item_ids',
		'selected_review_items',
		'cloud_core_intake_package',
		'npcink_governance_core_content_metadata_create_missing_rejected',
		'npcink_governance_core_content_metadata_authorization_rejected',
		'npcink_governance_core_content_metadata_update_field_rejected',
		'npcink_governance_core_content_metadata_term_field_rejected',
		'npcink_governance_core_content_metadata_duplicate_action_rejected',
		'media_optimization_plan',
		'media_adoption_enhancement_plan',
		'media_rename_plan',
		'pattern_page_plan',
		'article_goal_brief',
		'research_evidence_pack',
		'article_outline',
		'article_draft_candidate',
		'discoverability_pack',
		'article_risk_report',
		'ARTICLE_BATCH_MAX_ACTIONS',
		'ARTICLE_MEDIA_BATCH_MAX_ARTICLES',
		'ARTICLE_MEDIA_BATCH_MAX_ACTIONS',
		'PLAN_MAX_WRITE_ACTIONS',
		'PLAN_MAX_PAYLOAD_BYTES',
		'MEDIA_OPTIMIZATION_MAX_ACTIONS',
		'BLOCK_THEME_SITE_MAX_ACTIONS',
		'npcink_governance_core_plan_too_many_actions',
		'npcink_governance_core_plan_payload_too_large',
		'npcink_governance_core_article_batch_mode_required',
		'npcink_governance_core_article_media_batch_mode_required',
		'npcink_governance_core_article_media_batch_candidate_missing',
		'npcink_governance_core_image_candidate_contract_missing',
		'npcink_governance_core_image_candidate_source_type_invalid',
		'npcink_governance_core_media_optimization_batch_required',
		'npcink_governance_core_media_optimization_actions_rejected',
		'npcink_governance_core_media_optimization_attachment_mismatch',
		'npcink_governance_core_media_adoption_enhancement_actions_missing',
		'npcink_governance_core_media_adoption_enhancement_patch_invalid',
		'npcink_governance_core_media_rename_target_file_missing',
		'npcink_governance_core_media_rename_attachment_mismatch',
		'npcink_governance_core_block_theme_site_actions_rejected',
		'npcink_governance_core_block_theme_site_layout_profile_rejected',
		'npcink_governance_core_block_theme_site_layout_contract_rejected',
		'npcink_governance_core_block_theme_site_block_rejected',
		'npcink_governance_core_block_theme_site_block_tree_rejected',
		'npcink_governance_core_block_theme_site_roundtrip_required',
		'npcink_governance_core_block_theme_site_template_rejected',
		'customize_template_layout',
		'template_layout_contract',
		'block_theme_profile_compiler@0.3',
		'block_theme_safe_core_blocks@0.2',
		'accepted_profile_versions',
		'page_standard@0.2',
		'homepage_landing@0.3',
		'replace_template_layout_with_preserved_template_parts',
		'article_standard',
		'homepage_landing',
		'core/categories',
		'core/post-terms',
		'core/post-navigation-link',
		'core/comments',
		'BLOCK_THEME_SITE_MAX_BLOCKS',
		'BLOCK_THEME_SITE_MAX_BLOCK_DEPTH',
		'block_theme_site_allowed_blocks',
		'block_theme_site_allowed_template_slugs',
		'npcink_governance_core_pattern_page_class_rejected',
		'npcink_governance_core_article_plan_',
		'npcink_governance_core_article_batch_',
		'npcink_governance_core_article_media_batch_',
		'publish_rejected',
		'blocked_claims',
		'risk_blocked',
		'target_rejected',
		'npcink-abilities-toolkit/create-draft',
		'npcink-abilities-toolkit/upload-media-from-url',
		'npcink-abilities-toolkit/optimize-media-asset',
		'npcink-abilities-toolkit/set-post-featured-image',
		'proposal_ready',
		'needs_input',
		'preflight_blockers',
		'batch_review_summary',
		'core-batch-review-summary-v1',
		'operator_next_action',
		'resolve_blocked_items_before_commit_preflight',
		'needs_input_resolution_owner',
		'toolbox_morning_brief_operator',
		'resubmission_required',
		'core_amendment_supported',
		'final_execution_owner',
		'plan_requires_batch_proposal',
		'plan_to_proposal_batch',
		'batch_approval',
		'proposal_mode',
		'depends_on',
		'skipped_destructive_candidates',
		'manual_review',
		'commit_execution',
		'dry_run',
		'Repair inline references',
		'Preserve the original file as a local backup for rollback',
	) as $required
) {
	npcink_governance_core_assert( false !== strpos( $plan_proposal_service, $required ), 'Plan-to-proposal service contains required text: ' . $required );
}

$smoke_wp = npcink_governance_core_read( $root . '/tests/smoke-wp.php' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'include_unattached_nonproduction_media' ), 'Smoke test media delete fixture opts into abilities-side test media delete policy.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'npcink_governance_core_smoke_register_post_fixture' ), 'Smoke test registers post fixtures for cleanup.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'npcink_governance_core_smoke_register_comment_fixture' ), 'Smoke test registers comment fixtures for cleanup.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'npcink_governance_core_smoke_register_attachment_fixture' ), 'Smoke test registers media attachment fixtures for cleanup.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'npcink_governance_core_smoke_register_term_fixture' ), 'Smoke test registers taxonomy term fixtures for cleanup.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'npcink_governance_core_smoke_register_app_key_fixture' ), 'Smoke test registers app key fixtures for revocation.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'register_shutdown_function' ), 'Smoke test runs fixture cleanup on shutdown.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'wp_delete_post' ), 'Smoke test permanently deletes post fixtures.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'wp_delete_comment' ), 'Smoke test permanently deletes comment fixtures.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'wp_delete_attachment' ), 'Smoke test permanently deletes media attachment fixtures.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'wp_delete_term' ), 'Smoke test deletes taxonomy term fixtures.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'revoke_by_key_id' ), 'Smoke test revokes app key fixtures.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'NPCINK_GOVERNANCE_CORE_SMOKE_PURGE' ), 'Smoke test keeps governance row purge opt-in.' );
npcink_governance_core_assert( false !== strpos( $testing_strategy, 'Proposal and audit rows remain persistent by default' ), 'Testing strategy keeps governance rows persistent by default.' );
npcink_governance_core_assert( false !== strpos( $development_workflow, 'NPCINK_GOVERNANCE_CORE_SMOKE_PURGE=1' ), 'Development workflow documents optional smoke purge.' );

$plan_to_proposal_docs = npcink_governance_core_read( $root . '/docs/plan-to-proposal-governance.md' );
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'include_unattached_nonproduction_media' ), 'Plan-to-proposal docs mention abilities-side unattached test media delete gate.' );
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'include_trash_parent_media' ), 'Plan-to-proposal docs mention abilities-side trash-parent media delete gate.' );
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'npcink-toolbox/build-article-write-plan' ), 'Plan-to-proposal docs include the Toolbox article writing handoff.' );
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'npcink-toolbox/build-article-batch-write-plan' ), 'Plan-to-proposal docs include the Toolbox article batch writing handoff.' );
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'npcink-toolbox/build-article-media-batch-write-plan' ), 'Plan-to-proposal docs include the Toolbox article media batch handoff.' );
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'npcink-toolbox/build-site-knowledge-review-plan' ), 'Plan-to-proposal docs include the Toolbox Site Knowledge review handoff.' );
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'npcink-toolbox/build-nightly-inspection-review-plan' ), 'Plan-to-proposal docs include the Toolbox Nightly Inspection review handoff.' );
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'npcink-toolbox/build-content-metadata-apply-plan' ), 'Plan-to-proposal docs include the Toolbox content metadata apply handoff.' );
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'blocked draft-review proposal' ), 'Plan-to-proposal docs keep Site Knowledge review non-executable before human input.' );
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'review this Morning Brief item in Core' ) && false !== strpos( $plan_to_proposal_docs, 'preview.nightly_inspection_review' ) && false !== strpos( $plan_to_proposal_docs, 'selected Morning Brief review item ids/items' ), 'Plan-to-proposal docs preserve Nightly Morning Brief selected-item review context.' );
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'needs_input_resolution_owner=toolbox_morning_brief_operator' ) && false !== strpos( $plan_to_proposal_docs, 'Return to Toolbox Morning Brief' ) && false !== strpos( $plan_to_proposal_docs, 'Core does not generate or edit missing draft fields' ), 'Plan-to-proposal docs route Nightly missing-input resolution back to Toolbox Morning Brief.' );
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'npcink-abilities-toolkit/build-media-optimization-plan' ), 'Plan-to-proposal docs include the media optimization handoff.' );
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'npcink-abilities-toolkit/build-media-adoption-enhancement-plan' ), 'Plan-to-proposal docs include the media adoption enhancement handoff.' );
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'npcink-abilities-toolkit/build-media-rename-plan' ), 'Plan-to-proposal docs include the media rename handoff.' );
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'npcink-abilities-toolkit/build-article-optimization-apply-plan' ), 'Plan-to-proposal docs include the article optimization apply handoff.' );
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'npcink-abilities-toolkit/build-article-block-plan' ), 'Plan-to-proposal docs include the article block handoff.' );
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'npcink-abilities-toolkit/build-pattern-page-plan' ), 'Plan-to-proposal docs include the pattern page handoff.' );
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'npcink-abilities-toolkit/build-block-theme-site-plan' ), 'Plan-to-proposal docs include the block theme site handoff.' );
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'core/categories' ), 'Plan-to-proposal docs document safe categories blocks for homepage layouts.' );
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'preview.article_workflow' ), 'Plan-to-proposal docs require article workflow preview evidence.' );
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'preview.article_optimization' ), 'Plan-to-proposal docs require article optimization preview evidence.' );
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'preview.article_block' ), 'Plan-to-proposal docs require article block preview evidence.' );
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'preview.pattern_page' ), 'Plan-to-proposal docs require pattern page preview evidence.' );
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'preview.block_theme_site' ), 'Plan-to-proposal docs require block theme site preview evidence.' );
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'preview.content_metadata_apply' ), 'Plan-to-proposal docs require content metadata apply preview evidence.' );
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'proposal_mode=batch' ), 'Plan-to-proposal docs require explicit batch proposal mode where needed.' );
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'optimize these reviewed media items' ), 'Plan-to-proposal docs define media optimization as a reviewed media intent.' );
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'include paired metadata and derivative actions for' ), 'Plan-to-proposal docs define media optimization attachment pairing.' );
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'each attachment in the plan' ), 'Plan-to-proposal docs define media optimization per-attachment pairing.' );
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'adopt this reviewed remote image into a page' ), 'Plan-to-proposal docs define media adoption enhancement intent.' );
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'preview.media_adoption_enhancement' ), 'Plan-to-proposal docs require media adoption enhancement preview evidence.' );
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'optimize this existing article' ), 'Plan-to-proposal docs define article optimization as an existing-content intent.' );
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'target_file_name' ), 'Plan-to-proposal docs require reviewed media rename target filename.' );
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'Article writing is a local Ability recipe' ), 'Plan-to-proposal docs treat article writing as local Ability recipe.' );
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'must not produce article drafts' ), 'Plan-to-proposal docs prohibit Cloud draft generation.' );
npcink_governance_core_assert( false !== strpos( $operation_classification, 'Media Optimization Regression Rule' ), 'Operation classification docs lock media optimization batch plans to Core proposal review.' );
npcink_governance_core_assert( false !== strpos( $operation_classification, 'Adapter owns derived readiness and execution state' ), 'Operation classification docs keep Adapter ownership for media execution state.' );
npcink_governance_core_assert( false !== strpos( $next_stage_plan, 'regression-owned cross-repo path' ), 'Next stage plan treats media optimization as a cross-repo regression path.' );
npcink_governance_core_assert( false !== strpos( $next_stage_plan, 'replacement rule counts versus actual replacements' ), 'Next stage plan keeps media replacement count verification in Abilities.' );
npcink_governance_core_assert( false !== strpos( $next_stage_plan, 'stop expanding the media optimization implementation in Core' ), 'Next stage plan records the media optimization stop decision.' );
npcink_governance_core_assert( false !== strpos( $next_stage_plan, 'classifier-driven authorization paths' ), 'Next stage plan redirects new product energy to classifier-driven authorization paths.' );

$article_writing_contract = npcink_governance_core_read( $root . '/docs/article-writing-workflow-contract.md' );
foreach (
	array(
		'Status: active planning contract',
		'article_draft_v1',
		'npcink-toolbox/build-article-write-plan',
		'npcink-toolbox/get-content-discoverability-context',
		'npcink-abilities-toolkit/create-draft',
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
		'npcink-toolbox/build-article-batch-write-plan',
		'npcink-toolbox/build-article-media-batch-write-plan',
		'article_batch_write_plan',
		'article_media_batch_write_plan',
		'2 to 5 actions',
		'featured_image_candidate',
		'npcink-abilities-toolkit/upload-media-from-url',
		'npcink-abilities-toolkit/set-post-featured-image',
		'plan_to_proposal_batch',
	) as $required
) {
	npcink_governance_core_assert( false !== strpos( $article_writing_contract, $required ), 'Article writing workflow contract contains required text: ' . $required );
}

$ability_recipe_contract = npcink_governance_core_read( $root . '/docs/ability-recipe-orchestration-contract.md' );
foreach (
	array(
		'Status: active planning contract',
		'An ability recipe is a deterministic orchestration plan',
		'Article drafting is the first example recipe',
		'article_draft_v1',
		'npcink-toolbox/get-content-discoverability-context',
		'npcink-toolbox/build-article-write-plan',
		'npcink-toolbox/build-article-batch-write-plan',
		'npcink-toolbox/build-article-media-batch-write-plan',
		'npcink-abilities-toolkit/create-draft',
		'Cloud must not provide article writing generation',
		'Cloud must not store article body generation jobs',
		'Cloud must also not generate `article_batch_write_plan` or',
		'`article_media_batch_write_plan` candidates',
		'Core must not become article-aware beyond validating supported plan output',
		'Do not add Cloud article import flows',
		'local article assistant workbench',
		'one local article at a time',
		'article_batch_draft_v1',
		'article_media_batch_draft_v1',
		'hidden content-generation platform',
	) as $required
) {
	npcink_governance_core_assert( false !== strpos( $ability_recipe_contract, $required ), 'Ability recipe orchestration contract contains required text: ' . $required );
}

$cloud_bulk_article_contract = npcink_governance_core_read( $root . '/docs/cloud-bulk-article-run-contract.md' );
foreach (
	array(
		'Status: prohibited and deprecated planning contract',
		'bulk_article_run_v1',
		'article writing generation',
		'Cloud-produced `article_write_plan` candidates',
		'Cloud-produced `article_batch_write_plan` candidates',
		'Cloud-produced `article_media_batch_write_plan` candidates',
		'Ability Recipe Orchestration Contract',
		'article_write_plan',
		'article_batch_write_plan',
		'article_media_batch_write_plan',
		'npcink-toolbox/build-article-write-plan',
		'Core POST /proposals/from-plan',
		'Cloud must not generate, store, or return article body content',
		'local Ability recipe orchestration',
		'Rejected Product Language',
		'Cloud article generator',
		'local Article Assistant Workbench',
	) as $required
) {
	npcink_governance_core_assert( false !== strpos( $cloud_bulk_article_contract, $required ), 'Cloud bulk article run contract contains required text: ' . $required );
}

$rest_api_contract = npcink_governance_core_read( $root . '/docs/rest-api-contract.md' );
npcink_governance_core_assert( false !== strpos( $rest_api_contract, 'npcink-toolbox/build-nightly-inspection-review-plan' ) && false !== strpos( $rest_api_contract, 'source_context.cloud_core_intake_package.selected_review_items' ) && false !== strpos( $rest_api_contract, 'preview.nightly_inspection_review' ), 'REST API contract documents Nightly Morning Brief Core intake shape.' );
npcink_governance_core_assert( false !== strpos( $rest_api_contract, 'needs_input_resolution_owner' ) && false !== strpos( $rest_api_contract, 'toolbox_morning_brief_operator' ) && false !== strpos( $rest_api_contract, 'Core does not generate or edit missing draft fields' ), 'REST API contract documents Nightly Morning Brief needs-input resubmission path.' );
$ability_intake_contract = npcink_governance_core_read( $root . '/docs/ability-intake-contract.md' );
npcink_governance_core_assert( false !== strpos( $ability_intake_contract, 'npcink-toolbox/build-nightly-inspection-review-plan' ) && false !== strpos( $ability_intake_contract, 'selected Morning Brief review item' ) && false !== strpos( $ability_intake_contract, 'Cloud scheduling, retry state' ), 'Ability intake contract documents Nightly Inspection as selected-item review handoff only.' );
npcink_governance_core_assert( false !== strpos( $ability_intake_contract, 'Toolbox Morning Brief operator' ) && false !== strpos( $ability_intake_contract, 'complete proposal resubmission' ) && false !== strpos( $ability_intake_contract, 'Core does not generate or edit missing draft fields' ), 'Ability intake contract documents Nightly missing-input owner and Core non-amendment boundary.' );

$admin_page = npcink_governance_core_read( $root . '/includes/Admin/Admin_Page.php' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'render_nightly_inspection_review_context' ) && false !== strpos( $admin_page, 'Nightly review item' ) && false !== strpos( $admin_page, 'Morning Brief handoff' ), 'Admin proposal detail renders Nightly Morning Brief selected review item context.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Required next step' ) && false !== strpos( $admin_page, 'Return to Toolbox Morning Brief' ) && false !== strpos( $admin_page, 'resubmit a complete Core proposal' ) && false !== strpos( $admin_page, 'Core does not generate or edit missing draft fields' ), 'Admin proposal detail gives explicit Nightly needs-input recovery guidance.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Morning Brief source' ) && false !== strpos( $admin_page, 'Selected review items' ) && false !== strpos( $admin_page, 'Evidence refs' ) && false !== strpos( $admin_page, 'Completed draft input' ), 'Admin proposal detail exposes Nightly source, selected review items, evidence refs, and completed draft input.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Review the completed Morning Brief draft in Core' ) && false !== strpos( $admin_page, 'run commit preflight before Adapter execution' ) && false !== strpos( $admin_page, 'Core still does not generate or edit draft fields' ), 'Admin proposal detail gives explicit completed Nightly draft review guidance.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'npcink-toolbox/build-nightly-inspection-review-plan' ) && false !== strpos( $admin_page, 'Nightly Inspection' ) && false !== strpos( $admin_page, 'Morning Brief' ), 'Admin review queue summarizes Nightly Inspection proposal source as Morning Brief.' );

$audit_repository = npcink_governance_core_read( $root . '/includes/Audit/Audit_Log_Repository.php' );
npcink_governance_core_assert( false !== strpos( $audit_repository, 'sanitize_text_field( $event_name )' ), 'Audit repository preserves dotted event names.' );
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

$admin_page = npcink_governance_core_read( $root . '/includes/Admin/Admin_Page.php' );
$admin_css = npcink_governance_core_read( $root . '/assets/admin.css' );
$admin_js = npcink_governance_core_read( $root . '/assets/admin.js' );
$admin_surface_standard = npcink_governance_core_read( $root . '/docs/admin-surface-standard.md' );
foreach (
	array(
		'local governance workbench',
		'operator-facing module name `Governance Core`',
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
		'History',
		'read-only',
		'row selection, bulk actions, archive actions, or reopen actions',
		'Settings',
		'Development Approval Policy',
		'Do not wrap this primary setting in a disclosure',
		'History retention',
		'90 days, 180 days',
		'This stores the retention policy only',
		'Advanced Access',
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
			'low-frequency fallback action',
			'Use operator-facing token',
			'default token table',
		'Settings tab',
		'keeping these controls out of the default review queue',
		'default collapsed',
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
npcink_governance_core_assert( false !== strpos( $admin_page, "__( 'Governance Core', 'npcink-governance-core' )" ), 'Admin page title uses the operator-facing module name.' );
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
npcink_governance_core_assert( false !== strpos( $admin_page, "method=\"get\" action=\"<?php echo esc_url( admin_url( 'admin.php' ) ); ?>\"" ), 'Admin proposal lookup is read-only GET navigation.' );
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
npcink_governance_core_assert( false !== strpos( $admin_page, 'Development approval policy, history retention, and trusted governance client access.' ), 'Admin Settings tab explains its narrow configuration scope.' );
npcink_governance_core_assert( false !== strpos( $admin_page, '<section class="npcink-governance-core-settings-section npcink-governance-core-max-wide"' ), 'Admin Settings tab renders primary settings directly instead of behind a disclosure.' );
npcink_governance_core_assert( false === strpos( $admin_page, 'render_approval_policy_entry( true )' ), 'Admin Settings tab does not use a disclosure-open flag for the primary approval policy setting.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'OPTION_HISTORY_RETENTION_DAYS' ), 'Admin Settings tab defines a bounded history retention option.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'DEFAULT_HISTORY_RETENTION_DAYS = 90' ), 'Admin Settings tab defaults history retention to ninety days.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'HISTORY_RETENTION_DISABLED_DAYS = 0' ), 'Admin Settings tab supports disabling automatic deletion.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'history_retention_day_options' ), 'Admin Settings tab renders bounded history retention choices.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'sanitize_history_retention_days' ), 'Admin Settings tab sanitizes history retention choices.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'history_retention_days' ), 'Admin Settings tab persists the selected history retention choice.' );
npcink_governance_core_assert( false !== strpos( $admin_page, "'cleanup_scheduled'      => false" ), 'Admin Settings tab does not claim scheduled cleanup is active.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Advanced Access' ), 'Admin page keeps token management behind advanced access.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Development Approval Policy' ), 'Admin page exposes lightweight development approval policy mode.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'MODE_SMART_GUARDED' ), 'Admin page exposes smart approval mode.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'MODE_DEV_ALLOW_ALL' ), 'Admin page exposes development allow-all approval mode.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'stored_policy_mode' ), 'Admin page checks the stored approval policy mode before fallback.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'is_allowed_policy_mode' ), 'Admin page detects stale or invalid stored approval policy modes.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Stored approval policy mode "%1$s" is no longer supported.' ), 'Admin page warns when stored approval policy mode falls back to manual.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'update_option( Approval_Policy_Evaluator::OPTION_POLICY_MODE' ), 'Admin page persists approval policy mode through a bounded option.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'update_option( self::OPTION_HISTORY_RETENTION_DAYS' ), 'Admin page persists history retention through a bounded option.' );
npcink_governance_core_assert( false !== strpos( $admin_page, "'app-keys'" ), 'Admin page keeps token management available behind an advanced view.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'render_admin_tabs' ), 'Admin page exposes tabbed Core sections.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'nav-tab-wrapper' ), 'Admin page uses WordPress admin tabs for Core sections.' );
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
npcink_governance_core_assert( false !== strpos( $admin_page, 'app_key_page' ), 'Admin page paginates advanced token management.' );
npcink_governance_core_assert( false === strpos( $admin_page, 'archive_status' ), 'Admin history does not expose an expired versus archived status filter.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'No requests need review.' ), 'Admin page provides a clear active queue empty state.' );
npcink_governance_core_assert( false === strpos( $admin_page, 'render_advanced_entries' ), 'Admin default page no longer renders low-frequency administration links inline.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Recent Activity' ), 'Admin default page exposes a compact recent activity section.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'npcink-governance-core-utility-panel-stretch' ), 'Admin default page renders recent activity as a secondary utility panel.' );
npcink_governance_core_assert( false !== strpos( $admin_page, '$events = $this->audit->list_recent( 1 );' ), 'Admin default page limits recent activity to the latest event.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Latest Core governance events. Full audit is in its own tab.' ), 'Admin default page points detailed activity to the audit tab.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Activity Log' ), 'Admin page exposes a release-facing activity log view.' );
npcink_governance_core_assert( false === strpos( $admin_page, 'Advanced: Core App Keys' ), 'Admin default page no longer folds app-key management inline.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Client access tokens' ), 'Admin default page exposes token management as a low-frequency action.' );
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
npcink_governance_core_assert( false !== strpos( $admin_page, 'Client Access Tokens' ), 'Admin advanced access page uses client access token heading.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Issue client access token' ), 'Admin page labels token creation with operator-facing token copy.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Issue a scoped token for a trusted governance client.' ), 'Admin page folds token creation behind an explicit disclosure.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'npcink-governance-core-token-table' ), 'Admin token page uses a compact token table.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'token_scope_summary' ), 'Admin token page summarizes permission scopes in the default row.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'render_token_detail_table' ), 'Admin token page moves App ID, Key ID, caller type, and full scope strings behind details.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'render_token_status_badge' ), 'Admin token page renders localized token status badges.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Back to settings' ), 'Admin token page returns operators to Settings.' );
npcink_governance_core_assert( false === strpos( $admin_page, 'Create Core App Key' ), 'Admin UI no longer exposes Core App Key creation copy.' );
npcink_governance_core_assert( false === strpos( $admin_page, 'Reopen for review' ), 'Admin page does not expose reopen actions for historical proposals.' );
npcink_governance_core_assert( false === strpos( $admin_page, "__( 'Archive', 'npcink-governance-core' )" ), 'Admin page does not expose archive actions for historical proposals.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Adapter Client' ), 'Admin page defaults app label to a generic adapter client.' );
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
npcink_governance_core_assert( false !== strpos( $admin_page, 'render_created_app_key' ), 'Admin page renders one-time app key result.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'nocache_headers' ), 'Admin app-key result prevents caching the one-time token.' );
npcink_governance_core_assert( false === strpos( $admin_page, 'wp-admin/admin-header.php' ), 'Admin app-key result avoids admin header inside admin-post context.' );
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
npcink_governance_core_assert( false !== strpos( $admin_page, '$this->render_audit_timeline( $timeline );' ) && false !== strpos( $admin_page, '$this->render_raw_proposal_payload( $proposal );' ), 'Admin proposal detail separates audit evidence from raw payload.' );
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
npcink_governance_core_assert( false !== strpos( $admin_css, '.npcink-governance-core-detail-tabs' ) && false !== strpos( $admin_css, '.npcink-governance-core-tab-panel' ), 'Admin CSS styles the proposal detail tab shell.' );
npcink_governance_core_assert( false !== strpos( $admin_css, '.npcink-governance-core-action-plan-table' ), 'Admin CSS keeps batch action rows vertically aligned.' );
npcink_governance_core_assert( false !== strpos( $admin_css, '.npcink-governance-core-display-id-primary' ), 'Admin CSS emphasizes the proposal detail review id.' );
npcink_governance_core_assert( false !== strpos( $admin_css, '.npcink-governance-core-signal-summary' ), 'Admin CSS styles compressed zero-value review signals.' );
npcink_governance_core_assert( false !== strpos( $admin_css, '.npcink-governance-core-lifecycle-summary' ), 'Admin CSS styles compact proposal lifecycle evidence.' );
npcink_governance_core_assert( false !== strpos( $admin_css, '.npcink-governance-core-empty-state' ), 'Admin CSS styles the active queue empty state.' );
npcink_governance_core_assert( false !== strpos( $admin_css, '.npcink-governance-core-bulk-action-bar' ), 'Admin CSS styles the contextual bulk action bar.' );
npcink_governance_core_assert( false !== strpos( $admin_css, '.npcink-governance-core-bulk-disclosure[hidden]' ), 'Admin CSS hides bulk actions until JavaScript reveals selection context.' );
npcink_governance_core_assert( false !== strpos( $admin_css, '.npcink-governance-core-list-nav' ), 'Admin CSS styles the review queue table navigation row.' );
npcink_governance_core_assert( false === strpos( $admin_css, '.npcink-governance-core-table-filter-links' ), 'Admin CSS no longer carries unused history status filter styles.' );
npcink_governance_core_assert( false !== strpos( $admin_css, '.npcink-governance-core-page-button' ), 'Admin CSS styles compact square pagination buttons.' );
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
npcink_governance_core_assert( false !== strpos( $admin_page, 'Full audit timeline' ), 'Admin proposal detail folds audit timeline behind a secondary disclosure.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'scope_decision' ), 'Admin proposal detail shows scope decision attribution.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'correlation_id' ), 'Admin proposal detail shows correlation id attribution.' );
npcink_governance_core_assert( false !== strpos( $core_operability, 'article workflow summary' ), 'Core governance operability documents article workflow summary.' );
npcink_governance_core_assert( false !== strpos( $core_operability, 'final write ability' ), 'Core governance operability documents article final write ability.' );
npcink_governance_core_assert( false !== strpos( $core_operability, 'batch action details' ), 'Core governance operability documents batch action detail review.' );
npcink_governance_core_assert( false !== strpos( $core_operability, 'audit lifecycle summary' ), 'Core governance operability documents visible lifecycle evidence.' );

$forbidden_runtime_terms = array(
	'Agent Gateway',
	'MCP runtime',
	'Prompt Center',
	'batch_article_governance',
	'batch_media_optimize',
	'workflow/content_tag_completion',
	'workflow/comment-moderation',
);

$forbidden_runtime_code_markers = array(
	"register_rest_route( 'npcink-governance-core/v1', '/execute",
	"register_rest_route( 'npcink-governance-core/v1', '/proxy-execute",
	"register_rest_route( 'npcink-governance-core/v1', '/jobs",
	"register_rest_route( 'npcink-governance-core/v1', '/tasks",
	"register_rest_route( 'npcink-governance-core/v1', '/runs",
	'provider_credentials',
	'provider_api_key',
	'model_router',
	'model_routing',
	'prompt_preset',
	'workflow_runtime',
	'task_queue',
	'workflow_queue',
	'batch_execution_console',
	'operator_runtime_console',
	'agent_gateway',
	'mcp_server',
);

$forbidden_runtime_name_pattern = '/(^|_)(executor|job_runner|queue|scheduler|worker|workflow_runtime|mcp_server|agent_gateway)($|_)/i';

foreach ( npcink_governance_core_project_files( $root ) as $file ) {
	$relative = str_replace( $root . '/', '', $file );
	$contents = npcink_governance_core_read( $file );

	if ( ! preg_match( '/^(includes|npcink-governance-core\.php)/', $relative ) ) {
		continue;
	}

	foreach ( $forbidden_runtime_terms as $term ) {
		npcink_governance_core_assert( false === strpos( $contents, $term ), $relative . ' must not reintroduce old Core runtime term: ' . $term );
	}

	foreach ( $forbidden_runtime_code_markers as $marker ) {
		npcink_governance_core_assert( false === strpos( $contents, $marker ), $relative . ' must not reintroduce Core runtime ownership marker: ' . $marker );
	}

	$basename = strtolower( pathinfo( $relative, PATHINFO_FILENAME ) );
	npcink_governance_core_assert( 1 !== preg_match( $forbidden_runtime_name_pattern, $basename ), $relative . ' must not introduce a runtime-shaped file name.' );

	if ( preg_match_all( '/\b(?:final\s+)?(?:class|interface|trait)\s+([A-Za-z_][A-Za-z0-9_]*)/', $contents, $matches ) ) {
		foreach ( $matches[1] as $symbol_name ) {
			npcink_governance_core_assert( 1 !== preg_match( $forbidden_runtime_name_pattern, $symbol_name ), $relative . ' must not introduce a runtime-shaped PHP symbol: ' . $symbol_name );
		}
	}
}

echo "Static contracts: ok\n";
