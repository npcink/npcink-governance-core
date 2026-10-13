<?php
/**
 * Static contract tests for the MVP plugin.
 *
 * @package NpcinkGovernanceCore
 */

// Keep explicit order and the calling scope shared across these test modules.
require __DIR__ . '/contracts/bootstrap.php';
require __DIR__ . '/contracts/project-and-documentation.php';
require __DIR__ . '/contracts/governance-contracts.php';
require __DIR__ . '/contracts/stage-and-consumers.php';
require __DIR__ . '/contracts/security-and-decisions.php';
require __DIR__ . '/contracts/classification-and-intake.php';
require __DIR__ . '/contracts/development-gates.php';
require __DIR__ . '/contracts/rest-and-persistence.php';
require __DIR__ . '/contracts/plan-contracts.php';
require __DIR__ . '/contracts/audit-and-admin.php';
require __DIR__ . '/contracts/runtime-boundaries.php';

npcink_governance_core_report_contract_failures();
