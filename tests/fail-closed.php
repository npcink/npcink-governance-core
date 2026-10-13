<?php
/**
 * Fail-closed fault injection tests for governance persistence.
 *
 * @package NpcinkGovernanceCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}

if ( ! defined( 'ARRAY_A' ) ) {
	define( 'ARRAY_A', 'ARRAY_A' );
}

// Keep explicit order and the calling scope shared across these test modules.
require __DIR__ . '/fail-closed/wordpress-stubs.php';
require __DIR__ . '/fail-closed/ability-registry.php';
require __DIR__ . '/fail-closed/database-stub.php';
require __DIR__ . '/fail-closed/bootstrap.php';
require __DIR__ . '/fail-closed/service-fixtures.php';
require __DIR__ . '/fail-closed/media-and-article-plan-fixtures.php';
require __DIR__ . '/fail-closed/block-and-review-plan-fixtures.php';
require __DIR__ . '/fail-closed/metadata-and-consent-fixtures.php';
require __DIR__ . '/fail-closed/rest-redaction-cases.php';
require __DIR__ . '/fail-closed/plan-intake-cases.php';
require __DIR__ . '/fail-closed/ability-and-read-cases.php';
require __DIR__ . '/fail-closed/proposal-persistence-cases.php';
require __DIR__ . '/fail-closed/lifecycle-and-preflight-cases.php';
require __DIR__ . '/fail-closed/app-key-and-rate-cases.php';

npcink_governance_core_fail_closed_report();
