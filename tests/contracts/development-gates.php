<?php
/** Development gates test module. @package NpcinkGovernanceCore */

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
		'token rotation and expiry automation are deferred',
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
foreach ( array( 'test:contracts', 'test:fail-closed', 'tests/fail-closed.php', 'test:release-package', 'tests/release-package-reproducibility.sh', 'smoke:wp-m4', 'scripts/run-m4-wordpress-smoke.sh', 'test:wordpress-smoke-evidence', 'check:wordpress-smoke-evidence', 'release:verify:m4', 'analyse:phpstan', 'vendor/bin/phpstan analyse', 'phpstan.neon.dist', 'acceptance:cross-repo-release', 'scripts/cross-repo-release-acceptance.sh', 'rc:version-matrix', 'scripts/check-release-candidate-version-matrix.sh' ) as $required ) {
	npcink_governance_core_assert( false !== strpos( $composer_json, $required ), 'Composer scripts include required test command: ' . $required );
}
$composer_data = json_decode( $composer_json, true );
npcink_governance_core_assert( is_array( $composer_data ), 'Composer JSON parses for script contract checks.' );
$composer_scripts = is_array( $composer_data['scripts'] ?? null ) ? $composer_data['scripts'] : array();
npcink_governance_core_assert( 'bash scripts/plugin-check-release.sh' === (string) ( $composer_scripts['plugin-check:release'] ?? '' ), 'Release Plugin Check uses the strict result parser.' );
$plugin_check_script = npcink_governance_core_read( $root . '/scripts/plugin-check-release.sh' );
npcink_governance_core_assert( false !== strpos( $plugin_check_script, '--format=strict-json' ), 'Release Plugin Check requests strict JSON.' );
npcink_governance_core_assert( false !== strpos( $plugin_check_script, 'scripts,stubs' ), 'Release Plugin Check excludes analysis-only stubs.' );
npcink_governance_core_assert( false !== strpos( $plugin_check_script, 'ERROR' ) && false !== strpos( $plugin_check_script, 'WARNING' ), 'Release Plugin Check blocks errors and warnings.' );
npcink_governance_core_assert( false !== strpos( $plugin_check_script, 'No errors found.' ), 'Release Plugin Check accepts the clean success response.' );
$prepare_release_script = npcink_governance_core_read( $root . '/scripts/prepare-release.sh' );
npcink_governance_core_assert( false !== strpos( $prepare_release_script, 'unzip -q "$ZIP_FILE"' ), 'Release preparation inspects the built ZIP.' );
npcink_governance_core_assert( false !== strpos( $prepare_release_script, 'PLUGIN_ROOT="$package_check_root/$PLUGIN_SLUG" composer plugin-check:release' ), 'Release preparation checks the packaged plugin root.' );
$package_builder = npcink_governance_core_read( $root . '/scripts/build-release-package.sh' );
foreach ( array( 'git ls-files -z', 'tar --null -T - -cf -', 'SOURCE_SNAPSHOT', 'export TZ=UTC', 'touch -t 200001010000', 'LC_ALL=C sort', 'zip -Xq' ) as $required ) {
	npcink_governance_core_assert( false !== strpos( $package_builder, $required ), 'Release package builder contains reproducibility control: ' . $required );
}
$package_reproducibility_test = npcink_governance_core_read( $root . '/tests/release-package-reproducibility.sh' );
foreach ( array( 'first_sha256', 'second_sha256', 'unzip -tq', 'forbidden in tests docs scripts stubs vendor .git .github', '.npcink-release-untracked-fixture' ) as $required ) {
	npcink_governance_core_assert( false !== strpos( $package_reproducibility_test, $required ), 'Release package regression contains required check: ' . $required );
}
$proposal_service_source = npcink_governance_core_read( $root . '/includes/Governance/Proposal_Service.php' );
$preflight_service_source = npcink_governance_core_read( $root . '/includes/Governance/Commit_Preflight_Service.php' );
$smoke_source = npcink_governance_core_test_sources( $root, 'smoke-wp' );
$authenticator_source = npcink_governance_core_read( $root . '/includes/Security/App_Authenticator.php' );
$plugin_source = npcink_governance_core_read( $root . '/includes/Plugin.php' );
$uninstall_source = npcink_governance_core_read( $root . '/uninstall.php' );
$plan_boundary_adr = npcink_governance_core_read( $root . '/docs/decisions/ADR-009-freeze-domain-plan-contracts.md' );
npcink_governance_core_assert( false !== strpos( $proposal_service_source, 'batch_action_guardrails' ), 'Batch proposals persist per-action governance guardrails.' );
npcink_governance_core_assert( false !== strpos( $preflight_service_source, 'batch_ability_contract_preflight' ), 'Commit preflight revalidates batch action contracts.' );
npcink_governance_core_assert( false !== strpos( $smoke_source, 'array_keys( (array) $npcink_governance_core_smoke_proposal_fixture_ids )' ), 'Smoke shutdown cleanup tolerates early failure before fixture globals are populated.' );
npcink_governance_core_assert( false !== strpos( $smoke_source, 'history cleanup removes the fixture or fills the bounded batch' ), 'History cleanup smoke respects bounded deletion when the site has an older backlog.' );
npcink_governance_core_assert( false !== strpos( $smoke_source, 'No error message returned.' ), 'WordPress smoke reports REST error details for failed requests.' );
npcink_governance_core_assert( false !== strpos( $smoke_source, "'cta_url'   => 'https://example.test/get-started/'" ), 'Homepage layout smoke supplies deterministic CTA evidence instead of depending on site content.' );
npcink_governance_core_assert( false !== strpos( $authenticator_source, 'hash_hmac' ) && false !== strpos( $authenticator_source, 'wp_salt' ), 'Request IP metadata uses a site-keyed HMAC.' );
npcink_governance_core_assert( false !== strpos( $plugin_source, 'network_wide' ) && false !== strpos( $plugin_source, 'wp_initialize_site' ), 'Plugin lifecycle provisions multisite tables.' );
npcink_governance_core_assert( false !== strpos( $plugin_source, 'OPTION_SCHEMA_VERSION' ) && false !== strpos( $plugin_source, 'maybe_upgrade_schema' ), 'Plugin lifecycle persists and checks schema version.' );
npcink_governance_core_assert( false !== strpos( $uninstall_source, 'WP_UNINSTALL_PLUGIN' ) && false !== strpos( $uninstall_source, 'Intentionally no-op' ), 'Uninstall policy explicitly preserves governance records.' );
npcink_governance_core_assert( false !== strpos( $plan_boundary_adr, 'will not add new domain-specific plan ids' ) && false !== strpos( $plan_boundary_adr, 'versioned, provider-neutral governance envelope' ), 'Core plan contract boundary is frozen for the 0.2.x line.' );
npcink_governance_core_assert( isset( $composer_scripts['eval:project:review'] ), 'Composer scripts include optional project eval-lab review command.' );
npcink_governance_core_assert( false !== strpos( (string) $composer_scripts['eval:project:review'], 'task=project_boundary_review_triad' ), 'Project eval-lab review command targets the triad task.' );
npcink_governance_core_assert( false !== strpos( (string) $composer_scripts['eval:project:review'], '"project=$PWD"' ), 'Project eval-lab review command quotes the project path.' );
npcink_governance_core_assert( false !== strpos( (string) $composer_scripts['eval:project:review'], 'project_label=npcink-governance-core' ), 'Project eval-lab review command passes a redacted project label.' );
npcink_governance_core_assert( false !== strpos( (string) $composer_scripts['eval:project:review'], 'contract=project_boundary_review_triad.v1' ), 'Project eval-lab review command pins the output contract.' );
npcink_governance_core_assert( false !== strpos( (string) $composer_scripts['eval:project:review'], 'mode=working_diff' ), 'Project eval-lab review command pins the default working diff mode.' );
$default_gate_scripts = array( 'test', 'test:all', 'release:verify', 'package:release', 'prepare:release', 'plugin-check:release', 'check:wporg', 'smoke:wp', 'test:contracts', 'test:fail-closed', 'acceptance:cross-repo-release' );
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

$core_ci = npcink_governance_core_read( $root . '/.github/workflows/ci.yml' );
foreach ( array( 'composer install --no-interaction --no-progress --prefer-dist', 'composer test:all', 'composer analyse:phpstan', 'composer check:wporg' ) as $required ) {
	npcink_governance_core_assert( false !== strpos( $core_ci, $required ), 'Core CI includes required static gate: ' . $required );
}
$release_workflow = npcink_governance_core_read( $root . '/.github/workflows/release-package.yml' );
foreach ( array( 'composer install --no-interaction --no-progress --prefer-dist', 'composer analyse:phpstan', 'forbidden in tests docs scripts stubs vendor', 'sha256sum build/npcink-governance-core.zip' ) as $required ) {
	npcink_governance_core_assert( false !== strpos( $release_workflow, $required ), 'Release workflow includes final package gate: ' . $required );
}
$phpstan_config = npcink_governance_core_read( $root . '/phpstan.neon.dist' );
foreach ( array( 'vendor/szepeviktor/phpstan-wordpress/extension.neon', 'level: 0', 'includes/', 'stubs/npcink-abilities-toolkit.stub.php' ) as $required ) {
	npcink_governance_core_assert( false !== strpos( $phpstan_config, $required ), 'PHPStan config keeps required WordPress/Core analysis contract: ' . $required );
}
$toolkit_stub = npcink_governance_core_read( $root . '/stubs/npcink-abilities-toolkit.stub.php' );
npcink_governance_core_assert( false !== strpos( $toolkit_stub, 'function npcink_abilities_toolkit_get_registered(): array' ), 'PHPStan stub describes the public Toolkit discovery helper.' );
foreach ( $composer_scripts as $script_name => $script_value ) {
	if ( str_starts_with( (string) $script_name, 'eval:' ) ) {
		continue;
	}
	$script_value = $composer_scripts[ $script_name ] ?? '';
	$script_text  = is_array( $script_value ) ? implode( "\n", array_map( 'strval', $script_value ) ) : (string) $script_value;
	npcink_governance_core_assert( false === strpos( $script_text, 'project_boundary_review_triad' ), 'Optional project eval-lab review is not part of ' . $script_name . '.' );
}

$fail_closed_test = npcink_governance_core_test_sources( $root, 'fail-closed' );
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
		'Status matrix',
		'REST status matrix',
		'record_execution_with_handoff',
		'record_execution_without_handoff',
		'Expired preflight handoff cannot record execution.',
		'Ability drift execution guidance',
		'Ability drift required scopes',
		'App-key scope isolation matrix',
		'record-only cannot preflight',
		'Redaction persistence matrix',
		'AUDIT_PROVIDER_CREDENTIALS_SECRET_SENTINEL',
		'Oversized direct proposal payload is rejected.',
		'Hash-only sensitive read preflight uses stable error code.',
		'App creation audit failure revokes the new key.',
		'App rotation returns a one-time replacement token response.',
		'App rotation audit failure uses stable error code.',
		'App rotation revoke audit failure uses stable error code.',
	) as $required
) {
	npcink_governance_core_assert( false !== strpos( $fail_closed_test, $required ), 'Fail-closed fault test contains required behavior: ' . $required );
}

$development_workflow = npcink_governance_core_read( $root . '/docs/development-workflow.md' );
npcink_governance_core_assert( false !== strpos( $development_workflow, 'does not depend on the abandoned legacy Magick AI' ), 'Development workflow rejects the abandoned legacy Magick AI dependency.' );
npcink_governance_core_assert( false !== strpos( $development_workflow, '/Users/muze/gitee/npcink-workflow-toolbox/docs/platform/README.md' ), 'Development workflow points cross-project platform coordination to Toolbox platform index.' );
npcink_governance_core_assert( false !== strpos( $development_workflow, 'cd /Users/muze/gitee/npcink-workflow-toolbox' ), 'Development workflow runs the cross-repo quality matrix from npcink-workflow-toolbox.' );
npcink_governance_core_assert( false !== strpos( $development_workflow, 'Smoke Failure Classification' ), 'Development workflow documents smoke failure classification.' );
npcink_governance_core_assert( false !== strpos( $development_workflow, '[smoke:preflight:fail] environment:' ), 'Development workflow classifies smoke environment failures.' );
npcink_governance_core_assert( false !== strpos( $development_workflow, '[smoke:preflight:fail] toolkit:' ), 'Development workflow classifies smoke Toolkit setup failures.' );
npcink_governance_core_assert( false !== strpos( $development_workflow, 'do not copy Toolkit ability definitions into Core' ), 'Development workflow preserves Toolkit ability ownership during smoke fixes.' );
npcink_governance_core_assert( false !== strpos( $development_workflow, 'Core or Toolkit contract regression' ), 'Development workflow classifies post-preflight smoke assertion failures.' );
npcink_governance_core_assert( false !== strpos( $development_workflow, 'New AI Write Entrypoint Admission' ), 'Development workflow documents new AI write entrypoint admission.' );
npcink_governance_core_assert( false !== strpos( $development_workflow, 'classification answer must be recorded' ), 'Development workflow requires classification evidence before implementation.' );
npcink_governance_core_assert( false !== strpos( $development_workflow, 'Do not add a Core proposal hop' ), 'Development workflow keeps native editor AI plugin acceptance outside Core.' );
npcink_governance_core_assert( false !== strpos( $development_workflow, 'owns the final WordPress action' ), 'Development workflow frames AI write entrypoints by final WordPress action ownership.' );
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
$wp_cli_local_sh = npcink_governance_core_read( $root . '/scripts/wp-cli-local.sh' );
npcink_governance_core_assert( false !== strpos( $smoke_wp_sh, 'WP_CLI' ), 'WordPress smoke shell uses WP-CLI directly.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp_sh, 'smoke_preflight_note' ), 'WordPress smoke shell reports preflight diagnostics.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp_sh, 'smoke_preflight_fail' ), 'WordPress smoke shell fails environment setup before runtime assertions.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp_sh, 'wp-config.php' ), 'WordPress smoke shell verifies WP_PATH before invoking WP-CLI.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp_sh, 'npcink-abilities-toolkit/npcink-abilities-toolkit.php' ), 'WordPress smoke shell verifies the Toolkit plugin file before runtime assertions.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp_sh, 'NPCINK_ABILITIES_TOOLKIT_PATH' ), 'WordPress smoke shell reports the Toolkit source path assumption.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp_sh, 'readlink' ), 'WordPress smoke shell diagnoses Core plugin symlink state.' );
npcink_governance_core_assert( false === strpos( $smoke_wp_sh, 'magick-ai-root' ), 'WordPress smoke shell must not depend on magick-ai-root.' );
npcink_governance_core_assert( false !== strpos( $wp_cli_local_sh, 'php-8.5.3+1' ), 'Local WP-CLI wrapper prefers the current LocalWP smoke PHP runtime.' );
npcink_governance_core_assert( false !== strpos( $wp_cli_local_sh, 'display_errors=0' ), 'Local WP-CLI wrapper suppresses PHP display errors for release-smoke noise control.' );
npcink_governance_core_assert( false !== strpos( $wp_cli_local_sh, 'pdo_mysql.default_socket' ), 'Local WP-CLI wrapper sets both mysqli and PDO MySQL sockets.' );
npcink_governance_core_assert( false !== strpos( $wp_cli_local_sh, 'LOCALWP_RUN_ROOT' ), 'Local WP-CLI wrapper supports an overridable LocalWP run root.' );
npcink_governance_core_assert( false !== strpos( $wp_cli_local_sh, "-path '*/mysql/mysqld.sock'" ), 'Local WP-CLI wrapper discovers the active LocalWP MySQL socket when the legacy path is absent.' );

$smoke_wp = npcink_governance_core_test_sources( $root, 'smoke-wp' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'NPCINK_ABILITIES_TOOLKIT_PATH' ), 'WordPress smoke can locate the shared npcink-abilities-toolkit repository explicitly.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'npcink_governance_core_smoke_delete_tracked_governance_rows' ), 'WordPress smoke deletes its tracked governance rows every run.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'smoke proposal fixture is deleted after smoke' ), 'WordPress smoke asserts its proposal fixtures are gone after cleanup.' );
npcink_governance_core_assert( false === strpos( $smoke_wp, 'get_page_by_title' ), 'WordPress smoke does not use deprecated get_page_by_title.' );
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
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'external execution input matches Core approved input hash' ), 'WordPress smoke validates Adapter external execution input binding.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'external WordPress Abilities API execution stays dry-run' ), 'WordPress smoke validates Adapter external ability execution through WordPress Abilities API.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'records successful external ability result' ), 'WordPress smoke validates Adapter execution result recording after external ability run.' );
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

