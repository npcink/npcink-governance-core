<?php
/** Project and documentation test module. @package NpcinkGovernanceCore */

$main_plugin = npcink_governance_core_read( $root . '/npcink-governance-core.php' );
npcink_governance_core_assert( false !== strpos( $main_plugin, 'Plugin Name: Npcink Governance Core' ), 'Main plugin file declares plugin header.' );
npcink_governance_core_assert( false !== strpos( $main_plugin, 'Description: Npcink AI governance layer for WordPress operations.' ), 'Main plugin file declares the public positioning.' );
npcink_governance_core_assert( false !== strpos( $main_plugin, 'Text Domain: npcink-governance-core' ), 'Main plugin file keeps the canonical text domain.' );
npcink_governance_core_assert( false !== strpos( $main_plugin, 'Domain Path: /languages' ), 'Main plugin file declares the bundled languages path.' );
npcink_governance_core_assert( false === strpos( $main_plugin, 'load_plugin_textdomain' ), 'Main plugin file lets WordPress.org load translations automatically.' );
$plugin_class = npcink_governance_core_read( $root . '/includes/Plugin.php' );
npcink_governance_core_assert( false !== strpos( $plugin_class, 'load_plugin_textdomain' ), 'Plugin class loads bundled translations so locally distributed zips get zh_CN strings before wp.org language packs exist.' );
npcink_governance_core_assert( false !== strpos( $main_plugin, 'register_activation_hook' ), 'Main plugin file registers activation hook.' );
npcink_governance_core_assert( false !== strpos( $main_plugin, 'plugins_loaded' ), 'Main plugin file boots after plugins_loaded.' );
npcink_governance_core_assert( false === strpos( $main_plugin, 'example.com' ), 'Main plugin header does not use placeholder Plugin URI.' );

$translation_glossary = npcink_governance_core_read( $root . '/docs/translation-glossary-zh.md' );
foreach ( array( 'Governance', 'Proposal', 'Commit preflight', 'Ability', 'Audit', 'App key' ) as $required ) {
	npcink_governance_core_assert( false !== strpos( $translation_glossary, $required ), 'Chinese translation glossary contains required term: ' . $required );
}
npcink_governance_core_assert( false !== strpos( $translation_glossary, 'Workflow Toolbox menu | 流程工具箱' ), 'Chinese translation glossary contains the Workflow Toolbox menu label.' );

$translation_pot = npcink_governance_core_read( $root . '/languages/npcink-governance-core.pot' );
$translation_po  = npcink_governance_core_read( $root . '/languages/npcink-governance-core-zh_CN.po' );
npcink_governance_core_assert( '' !== $translation_pot, 'Bundled POT template exists.' );
npcink_governance_core_assert( '' !== $translation_po, 'Bundled zh_CN PO file exists.' );
npcink_governance_core_assert( is_readable( $root . '/languages/npcink-governance-core-zh_CN.mo' ), 'Bundled zh_CN MO file exists.' );
preg_match( '/^\s*\*\s*Version:\s*(\S+)\s*$/m', $main_plugin, $translation_version_match );
npcink_governance_core_assert( ! empty( $translation_version_match[1] ), 'Main plugin header exposes a version for translation catalogs.' );
$translation_version = trim( $translation_version_match[1] ?? '' );
npcink_governance_core_assert( '' !== $translation_version && false !== strpos( $translation_pot, '"Project-Id-Version: Npcink Governance Core ' . $translation_version . '\n"' ), 'Bundled POT Project-Id-Version matches the plugin header version.' );
npcink_governance_core_assert( '' !== $translation_version && false !== strpos( $translation_po, '"Project-Id-Version: npcink-governance-core ' . $translation_version . '\n"' ), 'Bundled zh_CN PO Project-Id-Version matches the plugin header version.' );
npcink_governance_core_assert( false !== strpos( $translation_po, '"Language: zh_CN\\n"' ), 'Bundled zh_CN PO declares zh_CN language.' );
npcink_governance_core_assert( false !== strpos( $translation_po, 'msgid "Review Queue"' ) && false !== strpos( $translation_po, 'msgstr "审核队列"' ), 'Bundled zh_CN PO translates Review Queue.' );
npcink_governance_core_assert( false !== strpos( $translation_po, 'msgid "Workflow Toolbox"' ) && false !== strpos( $translation_po, 'msgstr "流程工具箱"' ), 'Bundled zh_CN PO translates the Workflow Toolbox menu label.' );
npcink_governance_core_assert( false !== strpos( $translation_po, '"connection pointers, and ability packages."' ) && false !== strpos( $translation_po, 'Npcink 治理、连接、云端连接指引和能力包的本地 WordPress 入口。' ), 'Bundled zh_CN PO translates the shared Npcink AI overview description.' );
$translation_po_unwrapped = str_replace( "\"\n\"", '', $translation_po );
npcink_governance_core_assert( false !== strpos( $translation_po_unwrapped, 'msgid "Commit preflight has already issued an execution handoff for this approved proposal."' ), 'Bundled zh_CN PO keeps commit preflight source strings.' );

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
		'External Owner Boundary Notes',
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
		'npcink-local-automation-runtime',
		'modules/local-automation-runtime/',
		'Jobs, leases, retry workers, scheduler state',
		'AI Development Workstream Summary',
		'Operation Classification Contract',
		'Cross-Repo Release Acceptance',
		'Core Contract Reuse Readiness - 2026-07-08',
		'Release Candidate Version Matrix',
		'Reference Plugin Action Plan',
		'Reference Plugin Evaluation Development Summary - 2026-07-08',
		'Core Docs Authority Inventory - 2026-07-08',
		'Cross-project platform',
		'npcink-workflow-toolbox/docs/platform/README.md',
		'Governance truth:',
		'Platform coordination pointers and historical planning context',
		'Core governance truth',
		'local admin consent with audit',
	) as $required
) {
	npcink_governance_core_assert( false !== strpos( $readme, $required ), 'README contains required phrase: ' . $required );
}

$core_docs_authority_inventory = npcink_governance_core_read( $root . '/docs/core-docs-authority-inventory-2026-07-08.md' );
foreach (
	array(
		'Core Docs Authority Inventory',
		'Cross-project platform coordination',
		'npcink-workflow-toolbox/docs/platform/README.md',
		'core_truth',
		'core_consumer_note',
		'platform_coordination_pointer',
		'historical',
		'Keep As Core Truth',
		'Keep As Core Consumer Notes',
		'Downgrade To Platform Coordination Pointers',
		'Do not return to one flat "read everything" list',
		'platform process, product UX, reference learning, release orchestration, or',
		'cross-repo coordination',
	) as $required
) {
	npcink_governance_core_assert( false !== strpos( $core_docs_authority_inventory, $required ), 'Core docs authority inventory contains required phrase: ' . $required );
}

$docs_history_moved = array(
	'content-metadata-delta-operator-trial.md',
	'ai-write-classification-regression-evidence.md',
	'ai-write-classification-stage-closeout-2026-07-07.md',
	'eval-lab-governance-hardening-closeout-2026-07-01.md',
	'wordpress-org-zh-cn-translation-status-2026-07-03.md',
	'release-closeout-2026-06-21.md',
	'admin-menu-localization-closeout-2026-06-21.md',
	'admin-identity-tab-visual-closeout-2026-07-02.md',
	'reference-plugin-evaluation-development-summary-2026-07-08.md',
	'article-content-production-discussion-summary-2026-09-05.md',
	'current-stage-closeout-and-handoff.md',
	'approval-policy-stage-closeout.md',
	'project-history-summary.md',
);
foreach ( $docs_history_moved as $docs_history_doc ) {
	npcink_governance_core_assert( is_readable( $root . '/docs/history/' . $docs_history_doc ), 'Historical doc lives under docs/history/: ' . $docs_history_doc );
	npcink_governance_core_assert( ! is_readable( $root . '/docs/' . $docs_history_doc ), 'Historical doc no longer sits in the docs root: ' . $docs_history_doc );
	npcink_governance_core_assert( false !== strpos( $readme, '](docs/history/' . $docs_history_doc . ')' ), 'README links the historical doc at its docs/history path: ' . $docs_history_doc );
	npcink_governance_core_assert( false === strpos( $readme, '](docs/' . $docs_history_doc . ')' ), 'README no longer links the historical doc from the docs root: ' . $docs_history_doc );
}

$reference_plugin_action_plan = npcink_governance_core_read( $root . '/docs/reference-plugin-action-plan.md' );
foreach (
	array(
		'Core governance review',
		'Abilities Toolkit contracts',
		'Adapter channel feedback',
		'Workflow Toolbox product flow',
		'Cloud Addon and Cloud runtime evidence',
		'composer quality:matrix:run',
		'does not add a new dependency unless a separate ADR accepts it',
		'turn suggestions into silent WordPress writes',
	) as $required
) {
	npcink_governance_core_assert( false !== strpos( $reference_plugin_action_plan, $required ), 'Reference plugin action plan contains required phrase: ' . $required );
}

$reference_plugin_evaluation_summary = npcink_governance_core_read( $root . '/docs/history/reference-plugin-evaluation-development-summary-2026-07-08.md' );
foreach (
	array(
		'Reference Plugin Evaluation Development Summary',
		'pattern sources, not as ownership models',
		'proposal_handoff',
		'Core contract-reuse observation showing that no new Core runtime code was',
		'operation classification',
		'Does it need `suggestion_only`, `local_admin_consent`,',
		'second ability registry',
		'workflow runtime, jobs, tasks, runs, queues, schedulers, workers, or leases',
		'final WordPress write execution',
		'central matrix from `npcink-workflow-toolbox`',
	) as $required
) {
	npcink_governance_core_assert( false !== strpos( $reference_plugin_evaluation_summary, $required ), 'Reference plugin evaluation summary contains required phrase: ' . $required );
}

$article_content_production_discussion_summary = npcink_governance_core_read( $root . '/docs/history/article-content-production-discussion-summary-2026-09-05.md' );
foreach (
	array(
		'Article Content Production Discussion Summary',
		'reference plugins are pattern sources, not ownership models',
		'Separate technical, quality, and value gates',
		'One Product, Two Commit Paths',
		'`native_editor_commit`',
		'`core_proposal_required`',
		'Do not add MCP for this goal.',
		'ADR-009 now freezes new domain-specific plan ids',
		'Historical proposal; not accepted and now blocked by ADR-009',
		'Cloud does not generate drafts, SEO copy, article-plan candidates,',
		'workflow runtime, task queues, workers, leases, retries, or schedulers',
		'final WordPress write execution',
		'write a boundary note or a new ADR',
	) as $required
) {
	npcink_governance_core_assert( false !== strpos( $article_content_production_discussion_summary, $required ), 'Article content production discussion summary contains required phrase: ' . $required );
}

$core_contract_reuse_readiness = npcink_governance_core_read( $root . '/docs/core-contract-reuse-readiness-2026-07-08.md' );
foreach (
	array(
		'Core Contract Reuse Readiness',
		'proposal_handoff',
		'ability_contracts',
		'execution_profiles',
		'product_surface',
		'signed_transport',
		'runtime_detail',
		'No new Core runtime code is needed for this pass.',
		'preview.operation_classification',
		'classification=core_proposal_required',
		'intake_path=core_proposal',
		'commit_execution=false',
		'record-execution stores external execution outcomes',
		'Stop and write a boundary note or ADR',
		'final WordPress write execution',
		'npcink-abilities-toolkit',
		'composer test:all',
	) as $required
) {
	npcink_governance_core_assert( false !== strpos( $core_contract_reuse_readiness, $required ), 'Core contract reuse readiness contains required phrase: ' . $required );
}

$wp_readme = npcink_governance_core_read( $root . '/readme.txt' );
foreach (
	array(
		'=== Npcink Governance Core ===',
		'Stable tag: 0.3.0',
		'Npcink AI governance layer for WordPress operations.',
		'= What Core does =',
		'= Who should use this plugin =',
		'= Privacy and data =',
		'= Boundaries =',
		'Open Npcink AI > Core',
		'= Is this an AI content generator? =',
		'= What is commit preflight? =',
		'= Does this plugin send data to an external AI service? =',
		'= What data does Core store? =',
		'Core does not call external AI services and does not send site data to third parties.',
		'Third-party providers can expose WordPress Abilities API definitions',
		'Requires at least: 7.0',
		'Tested up to: 7.1',
		'Requires PHP: 8.0',
		'License: GPLv2 or later',
		'== Description ==',
	) as $required
) {
	npcink_governance_core_assert( false !== strpos( $wp_readme, $required ), 'WordPress readme contains required phrase: ' . $required );
}

$wporg_translation_readme = npcink_governance_core_read( $root . '/sj/wporg-readme-translations/README.md' );
foreach (
	array(
		'Stable Readme',
		'translate.wordpress.org',
		'zh_CN',
		'zh_TW',
		'ja',
		'ko_KR',
		'es_ES',
		'fr_FR',
		'de_DE',
		'pt_BR',
		'Do not localize the slug or REST namespace.',
	) as $required
) {
	npcink_governance_core_assert( false !== strpos( $wporg_translation_readme, $required ), 'WordPress.org translation readme contains required phrase: ' . $required );
}

foreach (
	array(
		'zh_CN' => '面向 AI 辅助 WordPress 操作的治理',
		'zh_TW' => '為 AI 輔助的 WordPress 操作提供治理',
		'ja'    => 'AI 支援の WordPress 操作',
		'ko_KR' => 'AI 지원 WordPress 작업',
		'es_ES' => 'operaciones de WordPress asistidas por IA',
		'fr_FR' => 'opérations WordPress assistées par IA',
		'de_DE' => 'KI-gestützte WordPress-Operationen',
		'pt_BR' => 'operações WordPress assistidas por IA',
	) as $locale => $required
) {
	$draft = npcink_governance_core_read( $root . '/sj/wporg-readme-translations/stable-readme-' . $locale . '.md' );
	npcink_governance_core_assert( false !== strpos( $draft, 'Npcink Governance Core' ), 'WordPress.org readme translation draft keeps product name: ' . $locale );
	npcink_governance_core_assert( false !== strpos( $draft, '/wp-json/npcink-governance-core/v1/' ), 'WordPress.org readme translation draft keeps REST namespace: ' . $locale );
	npcink_governance_core_assert( false !== strpos( $draft, $required ), 'WordPress.org readme translation draft contains localized positioning: ' . $locale );
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
foreach ( array( '`Npcink AI`', '`npcink-ai`', '`Core`', '`Adapter`', '`Abilities`', '`Workflow Toolbox`', '`Cloud Addon`', '`Npcink AI -> Core`', '`Npcink AI -> Workflow Toolbox`', '`Npcink AI -> 流程工具箱`' ) as $required ) {
	npcink_governance_core_assert( false !== strpos( $admin_menu_standard, $required ), 'Admin menu standard documents required entry: ' . $required );
}

$admin_page = npcink_governance_core_admin_sources( $root );
foreach ( array( 'PARENT_MENU_SLUG', 'has_parent_menu', 'add_submenu_page', 'add_management_page', 'Core' ) as $required ) {
	npcink_governance_core_assert( false !== strpos( $admin_page, $required ), 'Admin page implements shared menu contract: ' . $required );
}
$plugin_container = npcink_governance_core_read( $root . '/includes/Plugin.php' );
npcink_governance_core_assert( false !== strpos( $plugin_container, 'plugin_action_links_' ) && false !== strpos( $plugin_container, 'filter_plugin_action_links' ) && false !== strpos( $plugin_container, 'menu_page_url' ) && false !== strpos( $plugin_container, 'tools.php?page=npcink-governance-core' ), 'Plugin screen exposes a Settings shortcut to the registered Core page or standalone Tools fallback.' );
npcink_governance_core_assert( false !== strpos( $admin_page, "const PARENT_MENU_SLUG  = 'npcink-ai';" ), 'Admin page targets the shared Npcink AI parent menu slug.' );
npcink_governance_core_assert( false === strpos( $admin_page, 'add_menu_page' ) && false === strpos( $admin_page, 'render_overview' ), 'Core does not create the suite navigation shell or overview.' );
npcink_governance_core_assert( false !== strpos( $admin_page, "__( 'Core', 'npcink-governance-core' )" ) && false !== strpos( $admin_page, 'add_submenu_page' ), 'Admin submenu title is Core.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'add_management_page' ), 'Core remains independently usable through a Tools fallback.' );

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

