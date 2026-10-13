<?php
/**
 * Real WordPress smoke test for Npcink Governance Core.
 *
 * Run through WP-CLI after the plugin is symlinked into the LocalWP site.
 *
 * @package NpcinkGovernanceCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "WordPress is not loaded.\n" );
	exit( 1 );
}

$npcink_governance_core_smoke_run_id                 = wp_generate_uuid4();
$npcink_governance_core_smoke_post_fixture_ids       = array();
$npcink_governance_core_smoke_comment_fixture_ids    = array();
$npcink_governance_core_smoke_attachment_fixture_ids = array();
$npcink_governance_core_smoke_term_fixtures          = array();
$npcink_governance_core_smoke_app_key_fixture_ids    = array();
$npcink_governance_core_smoke_app_fixture_ids        = array();
$npcink_governance_core_smoke_proposal_fixture_ids   = array();
$npcink_governance_core_smoke_read_request_fixture_ids = array();
$npcink_governance_core_smoke_cleanup_completed      = false;
$npcink_governance_core_smoke_initial_policy_mode    = 'manual';
$npcink_governance_core_smoke_initial_history_retention = 90;

// Keep explicit order and the calling scope shared across these test modules.
require __DIR__ . '/smoke-wp/fixture-cleanup.php';
require __DIR__ . '/smoke-wp/rest-and-capability-helpers.php';
require __DIR__ . '/smoke-wp/governance-helpers.php';
require __DIR__ . '/smoke-wp/activation-and-capabilities.php';
require __DIR__ . '/smoke-wp/sensitive-reads-and-proposals.php';
require __DIR__ . '/smoke-wp/content-plan-cases.php';
require __DIR__ . '/smoke-wp/approval-policy-cases.php';
require __DIR__ . '/smoke-wp/media-plan-cases.php';
require __DIR__ . '/smoke-wp/app-and-execution-cases.php';
require __DIR__ . '/smoke-wp/lifecycle-and-audit-cases.php';
require __DIR__ . '/smoke-wp/cleanup-assertions.php';
