<?php
/** Lifecycle and preflight cases test module. @package NpcinkGovernanceCore */

$proposal_status_matrix_statuses = array(
	\Npcink\GovernanceCore\Governance\Proposal_Repository::STATUS_PENDING,
	\Npcink\GovernanceCore\Governance\Proposal_Repository::STATUS_APPROVED,
	\Npcink\GovernanceCore\Governance\Proposal_Repository::STATUS_REJECTED,
	\Npcink\GovernanceCore\Governance\Proposal_Repository::STATUS_EXPIRED,
	\Npcink\GovernanceCore\Governance\Proposal_Repository::STATUS_ARCHIVED,
	\Npcink\GovernanceCore\Governance\Proposal_Repository::STATUS_EXECUTED,
	\Npcink\GovernanceCore\Governance\Proposal_Repository::STATUS_EXECUTION_FAILED,
);
$proposal_status_matrix_operations = array(
	'approve'                          => array(
		'allowed_from' => array( \Npcink\GovernanceCore\Governance\Proposal_Repository::STATUS_PENDING ),
		'target'       => \Npcink\GovernanceCore\Governance\Proposal_Repository::STATUS_APPROVED,
	),
	'reject'                           => array(
		'allowed_from' => array( \Npcink\GovernanceCore\Governance\Proposal_Repository::STATUS_PENDING ),
		'target'       => \Npcink\GovernanceCore\Governance\Proposal_Repository::STATUS_REJECTED,
	),
	'archive'                          => array(
		'allowed_from' => array( \Npcink\GovernanceCore\Governance\Proposal_Repository::STATUS_EXPIRED ),
		'target'       => \Npcink\GovernanceCore\Governance\Proposal_Repository::STATUS_ARCHIVED,
	),
	'reopen'                           => array(
		'allowed_from' => array(
			\Npcink\GovernanceCore\Governance\Proposal_Repository::STATUS_EXPIRED,
			\Npcink\GovernanceCore\Governance\Proposal_Repository::STATUS_ARCHIVED,
		),
		'target'       => \Npcink\GovernanceCore\Governance\Proposal_Repository::STATUS_PENDING,
	),
	'record_execution_without_handoff' => array(
		'allowed_from' => array(),
		'target'       => '',
	),
);

foreach ( $proposal_status_matrix_statuses as $matrix_status ) {
	foreach ( $proposal_status_matrix_operations as $matrix_operation => $matrix_rule ) {
		$wpdb       = npcink_governance_core_fail_closed_reset_db();
		$fixture    = npcink_governance_core_fail_closed_proposal_in_status( $matrix_status );
		$proposal_id = (string) ( $fixture['proposal']['proposal_id'] ?? '' );
		$result      = npcink_governance_core_fail_closed_run_status_matrix_operation( $fixture, $matrix_operation );
		$allowed     = in_array( $matrix_status, (array) $matrix_rule['allowed_from'], true );
		$current     = $fixture['stack']['proposals']->find( $proposal_id );
		$message     = 'Status matrix ' . $matrix_operation . ' from ' . $matrix_status;

		if ( $allowed ) {
			npcink_governance_core_fail_closed_assert( ! is_wp_error( $result ), $message . ' succeeds.' );
			npcink_governance_core_fail_closed_assert( is_array( $current ) && (string) $matrix_rule['target'] === (string) ( $current['status'] ?? '' ), $message . ' reaches expected status.' );
			continue;
		}

		npcink_governance_core_fail_closed_assert( is_wp_error( $result ), $message . ' fails closed.' );
		npcink_governance_core_fail_closed_assert( is_array( $current ) && $matrix_status === (string) ( $current['status'] ?? '' ), $message . ' keeps original status.' );
	}
}

$rest_status_matrix_operations = array(
	'approve'                          => array(
		'allowed_from' => array( \Npcink\GovernanceCore\Governance\Proposal_Repository::STATUS_PENDING ),
		'target'       => \Npcink\GovernanceCore\Governance\Proposal_Repository::STATUS_APPROVED,
	),
	'reject'                           => array(
		'allowed_from' => array( \Npcink\GovernanceCore\Governance\Proposal_Repository::STATUS_PENDING ),
		'target'       => \Npcink\GovernanceCore\Governance\Proposal_Repository::STATUS_REJECTED,
	),
	'commit_preflight'                 => array(
		'allowed_from' => array( \Npcink\GovernanceCore\Governance\Proposal_Repository::STATUS_APPROVED ),
		'target'       => \Npcink\GovernanceCore\Governance\Proposal_Repository::STATUS_APPROVED,
	),
	'record_execution_without_handoff' => array(
		'allowed_from' => array(),
		'target'       => '',
	),
	'record_execution_with_handoff'    => array(
		'allowed_from' => array( \Npcink\GovernanceCore\Governance\Proposal_Repository::STATUS_APPROVED ),
		'target'       => \Npcink\GovernanceCore\Governance\Proposal_Repository::STATUS_EXECUTED,
	),
);

foreach ( $proposal_status_matrix_statuses as $matrix_status ) {
	foreach ( $rest_status_matrix_operations as $matrix_operation => $matrix_rule ) {
		$wpdb       = npcink_governance_core_fail_closed_reset_db();
		$fixture    = npcink_governance_core_fail_closed_rest_proposal_in_status( $matrix_status );
		$proposal_id = (string) ( $fixture['proposal']['proposal_id'] ?? '' );
		$result      = npcink_governance_core_fail_closed_run_rest_status_matrix_operation( $fixture, $matrix_operation );
		$allowed     = in_array( $matrix_status, (array) $matrix_rule['allowed_from'], true );
		$current     = $fixture['stack']['proposals']->find( $proposal_id );
		$message     = 'REST status matrix ' . $matrix_operation . ' from ' . $matrix_status;

		if ( $allowed ) {
			npcink_governance_core_fail_closed_assert( $result instanceof WP_REST_Response && 200 === $result->get_status(), $message . ' returns HTTP 200 response.' );
			npcink_governance_core_fail_closed_assert( is_array( $current ) && (string) $matrix_rule['target'] === (string) ( $current['status'] ?? '' ), $message . ' reaches expected status.' );
			continue;
		}

		npcink_governance_core_fail_closed_assert( is_wp_error( $result ), $message . ' returns WP_Error.' );
		npcink_governance_core_fail_closed_assert( npcink_governance_core_fail_closed_rest_status_matrix_error_code( $matrix_status, $matrix_operation ) === $result->get_error_code(), $message . ' returns stable error code.' );
		npcink_governance_core_fail_closed_assert( 409 === npcink_governance_core_fail_closed_error_http_status( $result ), $message . ' maps to HTTP 409.' );
		npcink_governance_core_fail_closed_assert( is_array( $current ) && $matrix_status === (string) ( $current['status'] ?? '' ), $message . ' keeps original status.' );
	}
}

$representative_ability_ids = array(
	'npcink-abilities-toolkit/create-draft',
	'npcink-abilities-toolkit/update-post',
	'npcink-abilities-toolkit/set-post-terms',
	'npcink-abilities-toolkit/approve-comment',
	'npcink-abilities-toolkit/trash-post',
);

foreach ( $representative_ability_ids as $ability_id ) {
	$wpdb     = npcink_governance_core_fail_closed_reset_db();
	$stack    = npcink_governance_core_fail_closed_governance_stack();
	$proposal = $stack['service']->create( npcink_governance_core_fail_closed_governance_payload( $ability_id ) );
	npcink_governance_core_fail_closed_assert( ! is_wp_error( $proposal ), $ability_id . ' governance proposal is created.' );

	$pending_preflight = $stack['preflight']->preflight( (string) $proposal['proposal_id'] );
	npcink_governance_core_fail_closed_assert( is_wp_error( $pending_preflight ), $ability_id . ' pending proposal fails commit preflight.' );
	npcink_governance_core_fail_closed_assert( 'npcink_governance_core_proposal_not_approved' === $pending_preflight->get_error_code(), $ability_id . ' pending preflight uses stable error code.' );
	npcink_governance_core_fail_closed_assert( 1 === count( npcink_governance_core_fail_closed_audit_rows( (string) $proposal['proposal_id'], 'commit.preflight_failed' ) ), $ability_id . ' pending preflight failure is audited.' );

	$approved = $stack['service']->approve( (string) $proposal['proposal_id'], array( 'reason' => 'negative_smoke_approval' ) );
	npcink_governance_core_fail_closed_assert( ! is_wp_error( $approved ) && 'approved' === (string) ( $approved['status'] ?? '' ), $ability_id . ' proposal can be approved.' );

	$signed_client_fingerprint = 'sha256:' . str_repeat( 'a', 64 );
	\Npcink\GovernanceCore\Security\Request_Context::set_app(
		array(
			'app_id'                    => 'adapter_app_preflight',
			'key_id'                    => 'adapter_key_preflight',
			'caller_type'               => 'openclaw_adapter',
			'scope'                     => 'commit:preflight',
			'scopes'                    => array( 'commit:preflight' ),
			'route_family'              => 'commit_preflight',
			'signed_client_fingerprint' => $signed_client_fingerprint,
		)
	);
	$preflight = $stack['preflight']->preflight( (string) $proposal['proposal_id'] );
	npcink_governance_core_fail_closed_assert( ! is_wp_error( $preflight ), $ability_id . ' approved proposal passes commit preflight.' );
	npcink_governance_core_fail_closed_assert( false === (bool) ( $preflight['commit_execution'] ?? true ), $ability_id . ' preflight does not execute commits.' );
	npcink_governance_core_fail_closed_assert( $signed_client_fingerprint === (string) ( $preflight['approval_context']['signed_client_fingerprint'] ?? '' ), $ability_id . ' approval context binds signed client fingerprint.' );
	npcink_governance_core_fail_closed_assert( $signed_client_fingerprint === (string) ( $preflight['execution_handoff']['client_key_fingerprint'] ?? '' ), $ability_id . ' execution handoff binds client key fingerprint alias.' );
	npcink_governance_core_fail_closed_assert( '' !== (string) ( $preflight['approval_context']['expires_at'] ?? '' ), $ability_id . ' approval context carries handoff expiry.' );
	npcink_governance_core_fail_closed_assert( true === (bool) ( $preflight['idempotency_required'] ?? false ), $ability_id . ' preflight requires idempotency.' );
	npcink_governance_core_fail_closed_assert( true === (bool) ( $preflight['contract_preflight']['contract_matches'] ?? false ), $ability_id . ' preflight confirms ability contract match.' );
	npcink_governance_core_fail_closed_assert( true === (bool) ( $preflight['permission_preflight']['allowed'] ?? false ), $ability_id . ' preflight confirms permission.' );
	npcink_governance_core_fail_closed_assert( 1 === count( npcink_governance_core_fail_closed_audit_rows( (string) $proposal['proposal_id'], 'proposal.created' ) ), $ability_id . ' proposal creation is audited.' );
	npcink_governance_core_fail_closed_assert( 1 === count( npcink_governance_core_fail_closed_audit_rows( (string) $proposal['proposal_id'], 'proposal.policy_evaluated' ) ), $ability_id . ' policy evaluation is audited.' );
	npcink_governance_core_fail_closed_assert( 1 === count( npcink_governance_core_fail_closed_audit_rows( (string) $proposal['proposal_id'], 'proposal.approved' ) ), $ability_id . ' approval is audited.' );
	npcink_governance_core_fail_closed_assert( 1 === count( npcink_governance_core_fail_closed_audit_rows( (string) $proposal['proposal_id'], 'commit.preflighted' ) ), $ability_id . ' successful preflight is audited.' );
	$preflight_events = npcink_governance_core_fail_closed_audit_rows( (string) $proposal['proposal_id'], 'commit.preflighted' );
	npcink_governance_core_fail_closed_assert( 0 === strpos( (string) ( $preflight_events[0]['event_id'] ?? '' ), 'preflight_' ), $ability_id . ' successful preflight uses deterministic audit event id prefix.' );

	$replay = $stack['preflight']->preflight( (string) $proposal['proposal_id'] );
	npcink_governance_core_fail_closed_assert( is_wp_error( $replay ), $ability_id . ' duplicate commit preflight is rejected.' );
	npcink_governance_core_fail_closed_assert( 'npcink_governance_core_commit_preflight_already_issued' === $replay->get_error_code(), $ability_id . ' duplicate preflight uses stable error code.' );
}

$wpdb     = npcink_governance_core_fail_closed_reset_db();
$stack    = npcink_governance_core_fail_closed_governance_stack();
$proposal = $stack['service']->create( npcink_governance_core_fail_closed_governance_payload( 'npcink-abilities-toolkit/update-post' ) );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $proposal ), 'Preflight failure audit fixture proposal is created.' );
$wpdb->fail_insert_event_names[] = 'commit.preflight_failed';
$failed_preflight_audit = $stack['preflight']->preflight( (string) $proposal['proposal_id'] );
npcink_governance_core_fail_closed_assert( is_wp_error( $failed_preflight_audit ), 'Preflight denial fails closed when failure audit cannot be stored.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_preflight_failure_audit_failed' === $failed_preflight_audit->get_error_code(), 'Preflight failure audit insert failure uses stable error code.' );
npcink_governance_core_fail_closed_assert( 500 === npcink_governance_core_fail_closed_error_http_status( $failed_preflight_audit ), 'Preflight failure audit insert failure maps to HTTP 500.' );

$wpdb     = npcink_governance_core_fail_closed_reset_db();
$stack    = npcink_governance_core_fail_closed_governance_stack();
$proposal = $stack['service']->create( npcink_governance_core_fail_closed_governance_payload( 'npcink-abilities-toolkit/update-post' ) );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $proposal ), 'Expired handoff proposal is created.' );
$approved = $stack['service']->approve( (string) $proposal['proposal_id'], array( 'reason' => 'expired_handoff' ) );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $approved ), 'Expired handoff proposal is approved.' );
$preflight = $stack['preflight']->preflight( (string) $proposal['proposal_id'] );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $preflight ), 'Expired handoff proposal gets one preflight handoff.' );
npcink_governance_core_fail_closed_expire_preflight_handoff( (string) $proposal['proposal_id'] );
$expired_handoff_record = $stack['service']->record_execution_result(
	(string) $proposal['proposal_id'],
	array(
		'execution_status'    => 'succeeded',
		'correlation_id'      => (string) ( $preflight['correlation_id'] ?? '' ),
		'approved_input_hash' => (string) ( $preflight['approval_context']['approved_input_hash'] ?? '' ),
		'adapter_request_id'  => 'adapter-expired-handoff',
		'execution_mode'      => 'single_post',
		'executed_count'      => 1,
	)
);
npcink_governance_core_fail_closed_assert( is_wp_error( $expired_handoff_record ), 'Expired preflight handoff cannot record execution.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_execution_record_preflight_expired' === $expired_handoff_record->get_error_code(), 'Expired preflight handoff uses stable error code.' );
npcink_governance_core_fail_closed_assert( 409 === npcink_governance_core_fail_closed_error_http_status( $expired_handoff_record ), 'Expired preflight handoff maps to HTTP 409.' );
$after_expired_handoff = $stack['proposals']->find( (string) $proposal['proposal_id'] );
npcink_governance_core_fail_closed_assert( is_array( $after_expired_handoff ) && 'approved' === (string) ( $after_expired_handoff['status'] ?? '' ), 'Expired preflight handoff leaves proposal approved.' );
npcink_governance_core_fail_closed_assert( 0 === count( npcink_governance_core_fail_closed_audit_rows( (string) $proposal['proposal_id'], 'proposal.executed' ) ), 'Expired preflight handoff does not write executed audit.' );
npcink_governance_core_fail_closed_assert( 0 === count( npcink_governance_core_fail_closed_audit_rows( (string) $proposal['proposal_id'], 'proposal.execution_failed' ) ), 'Expired preflight handoff does not write execution-failed audit.' );
$expired_handoff_replay = $stack['preflight']->preflight( (string) $proposal['proposal_id'] );
npcink_governance_core_fail_closed_assert( is_wp_error( $expired_handoff_replay ), 'Expired preflight handoff cannot be replaced by duplicate preflight.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_commit_preflight_already_issued' === $expired_handoff_replay->get_error_code(), 'Expired preflight duplicate uses already-issued error code.' );
npcink_governance_core_fail_closed_assert( 409 === npcink_governance_core_fail_closed_error_http_status( $expired_handoff_replay ), 'Expired preflight duplicate maps to HTTP 409.' );
npcink_governance_core_fail_closed_assert( 1 === count( npcink_governance_core_fail_closed_audit_rows( (string) $proposal['proposal_id'], 'commit.preflighted' ) ), 'Expired preflight duplicate does not issue a second handoff.' );
npcink_governance_core_fail_closed_assert( 1 === count( npcink_governance_core_fail_closed_audit_rows( (string) $proposal['proposal_id'], 'commit.preflight_failed' ) ), 'Expired preflight duplicate is audited as a failed preflight.' );

$wpdb     = npcink_governance_core_fail_closed_reset_db();
$stack    = npcink_governance_core_fail_closed_governance_stack();
$proposal = $stack['service']->create( npcink_governance_core_fail_closed_governance_payload( 'npcink-abilities-toolkit/update-post' ) );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $proposal ), 'Execution record proposal is created.' );
$approved = $stack['service']->approve( (string) $proposal['proposal_id'], array( 'reason' => 'execution_record' ) );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $approved ), 'Execution record proposal is approved.' );
$preflight = $stack['preflight']->preflight( (string) $proposal['proposal_id'] );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $preflight ), 'Execution record proposal passes preflight.' );

$missing_binding_record = $stack['service']->record_execution_result(
	(string) $proposal['proposal_id'],
	array(
		'execution_status' => 'succeeded',
	)
);
npcink_governance_core_fail_closed_assert( is_wp_error( $missing_binding_record ), 'Execution record without preflight binding fails.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_execution_record_binding_required' === $missing_binding_record->get_error_code(), 'Execution record missing binding uses stable error code.' );
npcink_governance_core_fail_closed_assert( 400 === npcink_governance_core_fail_closed_error_http_status( $missing_binding_record ), 'Execution record missing binding maps to HTTP 400.' );
$after_missing_binding = $stack['proposals']->find( (string) $proposal['proposal_id'] );
npcink_governance_core_fail_closed_assert( is_array( $after_missing_binding ) && 'approved' === (string) ( $after_missing_binding['status'] ?? '' ), 'Execution record missing binding leaves proposal approved.' );

$wrong_hash_record = $stack['service']->record_execution_result(
	(string) $proposal['proposal_id'],
	array(
		'execution_status'    => 'succeeded',
		'correlation_id'      => (string) ( $preflight['correlation_id'] ?? '' ),
		'approved_input_hash' => str_repeat( '0', 64 ),
	)
);
npcink_governance_core_fail_closed_assert( is_wp_error( $wrong_hash_record ), 'Execution record with mismatched approved input hash fails.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_execution_record_preflight_missing' === $wrong_hash_record->get_error_code(), 'Execution record wrong hash uses stable preflight binding error.' );
npcink_governance_core_fail_closed_assert( 409 === npcink_governance_core_fail_closed_error_http_status( $wrong_hash_record ), 'Execution record wrong hash maps to HTTP 409.' );
$after_wrong_hash = $stack['proposals']->find( (string) $proposal['proposal_id'] );
npcink_governance_core_fail_closed_assert( is_array( $after_wrong_hash ) && 'approved' === (string) ( $after_wrong_hash['status'] ?? '' ), 'Execution record wrong hash leaves proposal approved.' );

$wrong_correlation_record = $stack['service']->record_execution_result(
	(string) $proposal['proposal_id'],
	array(
		'execution_status'    => 'succeeded',
		'correlation_id'      => 'wrong-correlation-id',
		'approved_input_hash' => (string) ( $preflight['approval_context']['approved_input_hash'] ?? '' ),
	)
);
npcink_governance_core_fail_closed_assert( is_wp_error( $wrong_correlation_record ), 'Execution record with mismatched correlation id fails.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_execution_record_preflight_missing' === $wrong_correlation_record->get_error_code(), 'Execution record wrong correlation uses stable preflight binding error.' );
npcink_governance_core_fail_closed_assert( 409 === npcink_governance_core_fail_closed_error_http_status( $wrong_correlation_record ), 'Execution record wrong correlation maps to HTTP 409.' );
$after_wrong_correlation = $stack['proposals']->find( (string) $proposal['proposal_id'] );
npcink_governance_core_fail_closed_assert( is_array( $after_wrong_correlation ) && 'approved' === (string) ( $after_wrong_correlation['status'] ?? '' ), 'Execution record wrong correlation leaves proposal approved.' );
npcink_governance_core_fail_closed_assert( 1 === count( npcink_governance_core_fail_closed_audit_rows( (string) $proposal['proposal_id'], 'commit.preflighted' ) ), 'Mismatched execution records do not add another preflight handoff.' );

$recorded = $stack['service']->record_execution_result(
	(string) $proposal['proposal_id'],
	array(
		'execution_status'    => 'succeeded',
		'correlation_id'      => (string) ( $preflight['correlation_id'] ?? '' ),
		'approved_input_hash' => (string) ( $preflight['approval_context']['approved_input_hash'] ?? '' ),
		'adapter_request_id'  => 'adapter-execution-record-smoke',
		'execution_mode'      => 'single_post',
		'executed_count'      => 1,
		'failed_count'        => 0,
	)
);
npcink_governance_core_fail_closed_assert( ! is_wp_error( $recorded ) && 'executed' === (string) ( $recorded['status'] ?? '' ), 'Execution record moves approved proposal to executed.' );
npcink_governance_core_fail_closed_assert( 1 === count( npcink_governance_core_fail_closed_audit_rows( (string) $proposal['proposal_id'], 'proposal.executed' ) ), 'Execution record success is audited.' );

$duplicate_record = $stack['service']->record_execution_result(
	(string) $proposal['proposal_id'],
	array(
		'execution_status'    => 'succeeded',
		'correlation_id'      => (string) ( $preflight['correlation_id'] ?? '' ),
		'approved_input_hash' => (string) ( $preflight['approval_context']['approved_input_hash'] ?? '' ),
		'adapter_request_id'  => 'adapter-execution-record-duplicate',
		'execution_mode'      => 'single_post',
		'executed_count'      => 1,
		'failed_count'        => 0,
	)
);
npcink_governance_core_fail_closed_assert( is_wp_error( $duplicate_record ), 'Duplicate execution record fails after terminal status.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_execution_record_already_recorded' === $duplicate_record->get_error_code(), 'Duplicate execution record uses stable error code.' );
npcink_governance_core_fail_closed_assert( 1 === count( npcink_governance_core_fail_closed_audit_rows( (string) $proposal['proposal_id'], 'proposal.executed' ) ), 'Duplicate execution record does not add another executed audit event.' );

$wpdb     = npcink_governance_core_fail_closed_reset_db();
$stack    = npcink_governance_core_fail_closed_governance_stack();
$proposal = $stack['service']->create( npcink_governance_core_fail_closed_governance_payload( 'npcink-abilities-toolkit/update-post' ) );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $proposal ), 'Execution record rollback proposal is created.' );
$approved = $stack['service']->approve( (string) $proposal['proposal_id'], array( 'reason' => 'execution_record_rollback' ) );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $approved ), 'Execution record rollback proposal is approved.' );
$preflight = $stack['preflight']->preflight( (string) $proposal['proposal_id'] );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $preflight ), 'Execution record rollback proposal passes preflight.' );
$wpdb->fail_insert_event_names[] = 'proposal.executed';
$recorded = $stack['service']->record_execution_result(
	(string) $proposal['proposal_id'],
	array(
		'execution_status'    => 'succeeded',
		'correlation_id'      => (string) ( $preflight['correlation_id'] ?? '' ),
		'approved_input_hash' => (string) ( $preflight['approval_context']['approved_input_hash'] ?? '' ),
		'adapter_request_id'  => 'adapter-execution-record-rollback',
		'execution_mode'      => 'single_post',
		'executed_count'      => 1,
		'failed_count'        => 0,
	)
);
npcink_governance_core_fail_closed_assert( is_wp_error( $recorded ), 'Execution record audit failure returns WP_Error.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_execution_record_audit_failed' === $recorded->get_error_code(), 'Execution record audit failure uses stable error code.' );
$rolled_back = $stack['proposals']->find( (string) $proposal['proposal_id'] );
npcink_governance_core_fail_closed_assert( is_array( $rolled_back ) && 'approved' === $rolled_back['status'], 'Execution record audit failure rolls status back to approved.' );

$wpdb     = npcink_governance_core_fail_closed_reset_db();
$stack    = npcink_governance_core_fail_closed_governance_stack();
$proposal = $stack['service']->create( npcink_governance_core_fail_closed_governance_payload( 'npcink-abilities-toolkit/update-post' ) );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $proposal ), 'REST exposure drift proposal is created while ability intake is ready.' );
$approved = $stack['service']->approve( (string) $proposal['proposal_id'], array( 'reason' => 'rest_exposure_drift' ) );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $approved ), 'REST exposure drift proposal is approved.' );
npcink_governance_core_fail_closed_mutate_ability(
	'npcink-abilities-toolkit/update-post',
	static function ( array $ability ): array {
		$ability['meta'] = is_array( $ability['meta'] ?? null ) ? $ability['meta'] : array();
		$ability['meta']['show_in_rest'] = false;
		return $ability;
	}
);
$blocked_intake_preflight = $stack['preflight']->preflight( (string) $proposal['proposal_id'] );
npcink_governance_core_fail_closed_assert( is_wp_error( $blocked_intake_preflight ), 'Commit preflight rejects an ability that became blocked after approval.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_ability_intake_blocked' === $blocked_intake_preflight->get_error_code(), 'Blocked commit preflight uses stable intake error code.' );
npcink_governance_core_fail_closed_assert( 1 === count( npcink_governance_core_fail_closed_audit_rows( (string) $proposal['proposal_id'], 'commit.preflight_failed' ) ), 'Blocked commit preflight is audited.' );

$ability_drift_matrix = array(
	// Static contract markers: Ability drift execution guidance; Ability drift required scopes.
	'input schema'       => static function ( array $ability ): array {
		$ability['input_schema']['properties']['unexpected_new_required_control'] = array( 'type' => 'string' );
		return $ability;
	},
	'risk metadata'      => static function ( array $ability ): array {
		$ability['risk_level'] = 'destructive';
		$ability['meta'] = is_array( $ability['meta'] ?? null ) ? $ability['meta'] : array();
		$ability['meta']['annotations'] = array( 'readonly' => false, 'destructive' => true );
		return $ability;
	},
	'permission metadata' => static function ( array $ability ): array {
		$ability['capability'] = 'delete_posts';
		return $ability;
	},
	'required scope'     => static function ( array $ability ): array {
		$ability['required_scope'] = 'post.admin';
		return $ability;
	},
	'required scopes'    => static function ( array $ability ): array {
		$ability['required_scopes'] = array( 'post.write', 'post.admin' );
		return $ability;
	},
	'approval flag'      => static function ( array $ability ): array {
		$ability['requires_approval'] = false;
		return $ability;
	},
	'execution guidance' => static function ( array $ability ): array {
		$ability['risk_level']        = 'read';
		$ability['requires_approval'] = false;
		$ability['sensitivity']       = 'public';
		$ability['meta'] = is_array( $ability['meta'] ?? null ) ? $ability['meta'] : array();
		$ability['meta']['annotations'] = array( 'readonly' => true, 'destructive' => false );
		return $ability;
	},
);

foreach ( $ability_drift_matrix as $drift_label => $drift_mutator ) {
	$wpdb     = npcink_governance_core_fail_closed_reset_db();
	$stack    = npcink_governance_core_fail_closed_governance_stack();
	$proposal = $stack['service']->create( npcink_governance_core_fail_closed_governance_payload( 'npcink-abilities-toolkit/update-post' ) );
	npcink_governance_core_fail_closed_assert( ! is_wp_error( $proposal ), 'Ability drift ' . $drift_label . ' proposal is created.' );
	$approved = $stack['service']->approve( (string) $proposal['proposal_id'], array( 'reason' => 'ability_drift_' . sanitize_key( $drift_label ) ) );
	npcink_governance_core_fail_closed_assert( ! is_wp_error( $approved ), 'Ability drift ' . $drift_label . ' proposal is approved.' );
	npcink_governance_core_fail_closed_mutate_ability( 'npcink-abilities-toolkit/update-post', $drift_mutator );

	$drift = $stack['preflight']->preflight( (string) $proposal['proposal_id'] );
	npcink_governance_core_fail_closed_assert( is_wp_error( $drift ), 'Ability drift ' . $drift_label . ' fails commit preflight.' );
	$expected_drift_code = 'approval flag' === $drift_label
		? 'npcink_governance_core_ability_intake_blocked'
		: ( 'execution guidance' === $drift_label
			? 'npcink_governance_core_ability_not_proposal_eligible'
			: 'npcink_governance_core_ability_contract_changed' );
	npcink_governance_core_fail_closed_assert( $expected_drift_code === $drift->get_error_code(), 'Ability drift ' . $drift_label . ' uses stable error code.' );
	npcink_governance_core_fail_closed_assert( 409 === npcink_governance_core_fail_closed_error_http_status( $drift ), 'Ability drift ' . $drift_label . ' maps to HTTP 409.' );
	$after_drift = $stack['proposals']->find( (string) $proposal['proposal_id'] );
	npcink_governance_core_fail_closed_assert( is_array( $after_drift ) && 'approved' === (string) ( $after_drift['status'] ?? '' ), 'Ability drift ' . $drift_label . ' leaves proposal approved.' );
	npcink_governance_core_fail_closed_assert( 1 === count( npcink_governance_core_fail_closed_audit_rows( (string) $proposal['proposal_id'], 'commit.preflight_failed' ) ), 'Ability drift ' . $drift_label . ' preflight failure is audited.' );
	npcink_governance_core_fail_closed_assert( 0 === count( npcink_governance_core_fail_closed_audit_rows( (string) $proposal['proposal_id'], 'commit.preflighted' ) ), 'Ability drift ' . $drift_label . ' does not issue a handoff.' );
}

$wpdb     = npcink_governance_core_fail_closed_reset_db();
$stack    = npcink_governance_core_fail_closed_governance_stack();
$proposal = $stack['service']->create( npcink_governance_core_fail_closed_governance_payload( 'npcink-abilities-toolkit/set-post-terms' ) );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $proposal ), 'Permission downgrade proposal is created.' );
$approved = $stack['service']->approve( (string) $proposal['proposal_id'], array( 'reason' => 'permission_downgrade' ) );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $approved ), 'Permission downgrade proposal is approved.' );
$npcink_governance_core_fail_closed_caps['edit_posts'] = false;
$permission_denied = $stack['preflight']->preflight( (string) $proposal['proposal_id'] );
npcink_governance_core_fail_closed_assert( is_wp_error( $permission_denied ), 'Missing WordPress capability fails commit preflight.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_ability_permission_denied' === $permission_denied->get_error_code(), 'Missing WordPress capability uses stable error code.' );
npcink_governance_core_fail_closed_assert( 1 === count( npcink_governance_core_fail_closed_audit_rows( (string) $proposal['proposal_id'], 'commit.preflight_failed' ) ), 'Permission preflight failure is audited.' );

$wpdb     = npcink_governance_core_fail_closed_reset_db();
$stack    = npcink_governance_core_fail_closed_governance_stack();
$proposal = $stack['service']->create( npcink_governance_core_fail_closed_governance_payload( 'npcink-abilities-toolkit/approve-comment' ) );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $proposal ), 'Unavailable ability proposal is created.' );
$approved = $stack['service']->approve( (string) $proposal['proposal_id'], array( 'reason' => 'ability_unavailable' ) );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $approved ), 'Unavailable ability proposal is approved.' );
$npcink_governance_core_fail_closed_abilities = array();
$unavailable = $stack['preflight']->preflight( (string) $proposal['proposal_id'] );
npcink_governance_core_fail_closed_assert( is_wp_error( $unavailable ), 'Unavailable ability fails commit preflight.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_ability_unavailable' === $unavailable->get_error_code(), 'Unavailable ability uses stable error code.' );
npcink_governance_core_fail_closed_assert( 1 === count( npcink_governance_core_fail_closed_audit_rows( (string) $proposal['proposal_id'], 'commit.preflight_failed' ) ), 'Unavailable ability preflight failure is audited.' );

$wpdb     = npcink_governance_core_fail_closed_reset_db();
$stack    = npcink_governance_core_fail_closed_governance_stack();
$proposal = $stack['service']->create(
	array_merge(
		npcink_governance_core_fail_closed_governance_payload( 'npcink-abilities-toolkit/trash-post' ),
		array(
			'preview' => array(
				'proposal_ready'     => false,
				'preflight_blockers' => array(
					array(
						'code'   => 'destructive_review_missing',
						'reason' => 'Destructive review evidence is required.',
					),
				),
				'batch_review_summary' => array(
					'summary_version'      => 'core-batch-review-summary-v1',
					'operator_next_action' => 'resolve_blocked_items_before_commit_preflight',
					'core_execution'       => false,
					'commit_execution'     => false,
					'api_key'              => 'BATCH_SUMMARY_SECRET_SENTINEL',
					'queue_lease'          => 'not-core-owned-runtime-state',
				),
			),
		)
	)
);
npcink_governance_core_fail_closed_assert( ! is_wp_error( $proposal ), 'Blocked destructive proposal is created.' );
$approved = $stack['service']->approve( (string) $proposal['proposal_id'], array( 'reason' => 'blocked_preview' ) );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $approved ), 'Blocked destructive proposal is approved.' );
$blocked = $stack['preflight']->preflight( (string) $proposal['proposal_id'] );
npcink_governance_core_fail_closed_assert( is_wp_error( $blocked ), 'Blocked item fails commit preflight.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_proposal_items_blocked' === $blocked->get_error_code(), 'Blocked item uses stable error code.' );
$blocked_data = is_array( $blocked->get_error_data() ) ? $blocked->get_error_data() : array();
npcink_governance_core_fail_closed_assert( 'core-batch-review-summary-v1' === (string) ( $blocked_data['proposal_item_preflight']['batch_review_summary']['summary_version'] ?? '' ), 'Blocked item preflight returns batch review summary for operator recovery.' );
npcink_governance_core_fail_closed_assert( 'resolve_blocked_items_before_commit_preflight' === (string) ( $blocked_data['proposal_item_preflight']['batch_review_summary']['operator_next_action'] ?? '' ), 'Blocked item preflight returns operator next action.' );
npcink_governance_core_fail_closed_assert( false === strpos( (string) wp_json_encode( $blocked_data['proposal_item_preflight']['batch_review_summary'] ?? array() ), 'BATCH_SUMMARY_SECRET_SENTINEL' ), 'Blocked item preflight does not leak secret-shaped batch summary fields.' );
npcink_governance_core_fail_closed_assert( false === array_key_exists( 'queue_lease', (array) ( $blocked_data['proposal_item_preflight']['batch_review_summary'] ?? array() ) ), 'Blocked item preflight does not expose queue-like batch summary fields.' );
npcink_governance_core_fail_closed_assert( 1 === count( npcink_governance_core_fail_closed_audit_rows( (string) $proposal['proposal_id'], 'commit.preflight_failed' ) ), 'Blocked item preflight failure is audited.' );

