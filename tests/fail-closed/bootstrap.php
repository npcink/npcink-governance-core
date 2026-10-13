<?php
/** Bootstrap test module. @package NpcinkGovernanceCore */

/**
 * Collected fail-closed failures.
 *
 * @var array<int,string>
 */
$GLOBALS['npcink_governance_core_fail_closed_failures'] = array();

/**
 * Prints every collected failure and exits non-zero, or confirms ok.
 *
 * @return void
 */
function npcink_governance_core_fail_closed_report(): void {
	$failures = $GLOBALS['npcink_governance_core_fail_closed_failures'];
	if ( 0 === count( $failures ) ) {
		echo "Fail-closed fault injection: ok\n";
		return;
	}
	foreach ( array_slice( $failures, 0, 50 ) as $failure ) {
		fwrite( STDERR, '[fail] ' . $failure . "\n" );
	}
	$unshown = count( $failures ) - 50;
	if ( $unshown > 0 ) {
		fwrite( STDERR, "... and {$unshown} more failure(s) not shown\n" );
	}
	fwrite( STDERR, 'Fail-closed fault injection: ' . count( $failures ) . " failure(s)\n" );
	exit( 1 );
}

/**
 * Assertion helper that collects every failure so one run reports the full
 * failure surface instead of stopping at the first miss.
 *
 * @param bool   $condition Condition.
 * @param string $message Message.
 * @return void
 */
function npcink_governance_core_fail_closed_assert( bool $condition, string $message ): void {
	if ( ! $condition ) {
		$GLOBALS['npcink_governance_core_fail_closed_failures'][] = $message;
	}
}

require_once dirname( __DIR__, 2 ) . '/includes/Security/Request_Context.php';
require_once dirname( __DIR__, 2 ) . '/includes/Security/Sensitive_Data_Redactor.php';
require_once dirname( __DIR__, 2 ) . '/includes/Observability.php';
require_once dirname( __DIR__, 2 ) . '/includes/Audit/Audit_Log_Repository.php';
require_once dirname( __DIR__, 2 ) . '/includes/Capabilities/Ability_Registry_Adapter.php';
require_once dirname( __DIR__, 2 ) . '/includes/Governance/Approval_Policy_Evaluator.php';
require_once dirname( __DIR__, 2 ) . '/includes/Governance/Operation_Classifier.php';
require_once dirname( __DIR__, 2 ) . '/includes/Governance/Proposal_Repository.php';
require_once dirname( __DIR__, 2 ) . '/includes/Governance/Proposal_Service.php';
require_once dirname( __DIR__, 2 ) . '/includes/Governance/Read_Request_Repository.php';
require_once dirname( __DIR__, 2 ) . '/includes/Governance/Read_Request_Service.php';
require_once dirname( __DIR__, 2 ) . '/includes/Governance/Plan_Contract_Validator.php';
require_once dirname( __DIR__, 2 ) . '/includes/Governance/Plan_Proposal_Service.php';
require_once dirname( __DIR__, 2 ) . '/includes/Governance/Commit_Preflight_Service.php';
require_once dirname( __DIR__, 2 ) . '/includes/Security/App_Key_Repository.php';
require_once dirname( __DIR__, 2 ) . '/includes/Security/App_Rate_Limiter.php';
require_once dirname( __DIR__, 2 ) . '/includes/Security/App_Authenticator.php';
require_once dirname( __DIR__, 2 ) . '/includes/Rest/Apps_Controller.php';
require_once dirname( __DIR__, 2 ) . '/includes/Rest/Rest_Format.php';
require_once dirname( __DIR__, 2 ) . '/includes/Rest/Proposals_Controller.php';
require_once dirname( __DIR__, 2 ) . '/includes/Rest/Read_Requests_Controller.php';
require_once dirname( __DIR__, 2 ) . '/includes/Plugin.php';

