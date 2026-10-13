<?php
/** Service fixtures test module. @package NpcinkGovernanceCore */

/**
 * Resets global storage.
 *
 * @return Npcink_Governance_Core_Fail_Closed_WPDB
 */
function npcink_governance_core_fail_closed_reset_db(): Npcink_Governance_Core_Fail_Closed_WPDB {
	global $wpdb, $npcink_governance_core_fail_closed_options, $npcink_governance_core_fail_closed_transients, $npcink_governance_core_fail_closed_caps, $npcink_governance_core_fail_closed_abilities, $npcink_governance_core_fail_closed_native_abilities, $npcink_governance_core_fail_closed_actions;

	$wpdb = new Npcink_Governance_Core_Fail_Closed_WPDB();
	$npcink_governance_core_fail_closed_options = array();
	$npcink_governance_core_fail_closed_transients = array();
	$npcink_governance_core_fail_closed_caps = array();
	$npcink_governance_core_fail_closed_abilities = null;
	$npcink_governance_core_fail_closed_native_abilities = null;
	$npcink_governance_core_fail_closed_actions = array();
	\Npcink\GovernanceCore\Security\Request_Context::clear();

	return $wpdb;
}

/**
 * Returns a proposal service and repository.
 *
 * @return array{service:\Npcink\GovernanceCore\Governance\Proposal_Service,proposals:\Npcink\GovernanceCore\Governance\Proposal_Repository}
 */
function npcink_governance_core_fail_closed_proposal_stack(): array {
	$proposals = new \Npcink\GovernanceCore\Governance\Proposal_Repository();
	$service   = new \Npcink\GovernanceCore\Governance\Proposal_Service(
		$proposals,
		new \Npcink\GovernanceCore\Capabilities\Ability_Registry_Adapter(),
		new \Npcink\GovernanceCore\Audit\Audit_Log_Repository(),
		new \Npcink\GovernanceCore\Governance\Approval_Policy_Evaluator()
	);

	return array(
		'service'   => $service,
		'proposals' => $proposals,
	);
}

/**
 * Returns a proposal/preflight service stack.
 *
 * @return array{service:\Npcink\GovernanceCore\Governance\Proposal_Service,preflight:\Npcink\GovernanceCore\Governance\Commit_Preflight_Service,proposals:\Npcink\GovernanceCore\Governance\Proposal_Repository,audit:\Npcink\GovernanceCore\Audit\Audit_Log_Repository}
 */
function npcink_governance_core_fail_closed_governance_stack(): array {
	$proposals = new \Npcink\GovernanceCore\Governance\Proposal_Repository();
	$abilities = new \Npcink\GovernanceCore\Capabilities\Ability_Registry_Adapter();
	$audit     = new \Npcink\GovernanceCore\Audit\Audit_Log_Repository();
	$service   = new \Npcink\GovernanceCore\Governance\Proposal_Service(
		$proposals,
		$abilities,
		$audit,
		new \Npcink\GovernanceCore\Governance\Approval_Policy_Evaluator()
	);
	$preflight = new \Npcink\GovernanceCore\Governance\Commit_Preflight_Service( $proposals, $abilities, $audit );

	return array(
		'service'   => $service,
		'preflight' => $preflight,
		'proposals' => $proposals,
		'audit'     => $audit,
	);
}

/**
 * Returns a sensitive read request service stack.
 *
 * @return array{service:\Npcink\GovernanceCore\Governance\Read_Request_Service,requests:\Npcink\GovernanceCore\Governance\Read_Request_Repository,audit:\Npcink\GovernanceCore\Audit\Audit_Log_Repository}
 */
function npcink_governance_core_fail_closed_read_request_stack(): array {
	$requests = new \Npcink\GovernanceCore\Governance\Read_Request_Repository();
	$abilities = new \Npcink\GovernanceCore\Capabilities\Ability_Registry_Adapter();
	$audit     = new \Npcink\GovernanceCore\Audit\Audit_Log_Repository();
	$service   = new \Npcink\GovernanceCore\Governance\Read_Request_Service( $requests, $abilities, $audit );

	return array(
		'service'  => $service,
		'requests' => $requests,
		'audit'    => $audit,
	);
}

/**
 * Returns a proposal REST controller stack.
 *
 * @return array{controller:\Npcink\GovernanceCore\Rest\Proposals_Controller,service:\Npcink\GovernanceCore\Governance\Proposal_Service,preflight:\Npcink\GovernanceCore\Governance\Commit_Preflight_Service,proposals:\Npcink\GovernanceCore\Governance\Proposal_Repository,audit:\Npcink\GovernanceCore\Audit\Audit_Log_Repository}
 */
function npcink_governance_core_fail_closed_proposals_controller_stack(): array {
	$proposals = new \Npcink\GovernanceCore\Governance\Proposal_Repository();
	$abilities = new \Npcink\GovernanceCore\Capabilities\Ability_Registry_Adapter();
	$audit     = new \Npcink\GovernanceCore\Audit\Audit_Log_Repository();
	$service   = new \Npcink\GovernanceCore\Governance\Proposal_Service(
		$proposals,
		$abilities,
		$audit,
		new \Npcink\GovernanceCore\Governance\Approval_Policy_Evaluator()
	);
	$preflight = new \Npcink\GovernanceCore\Governance\Commit_Preflight_Service( $proposals, $abilities, $audit );
	$plan      = new \Npcink\GovernanceCore\Governance\Plan_Proposal_Service( $abilities, $service, $audit, new \Npcink\GovernanceCore\Governance\Plan_Contract_Validator() );
	$auth      = new \Npcink\GovernanceCore\Security\App_Authenticator(
		new \Npcink\GovernanceCore\Security\App_Key_Repository(),
		new \Npcink\GovernanceCore\Security\App_Rate_Limiter(),
		$audit
	);

	return array(
		'controller' => new \Npcink\GovernanceCore\Rest\Proposals_Controller( $service, $proposals, $preflight, $plan, $auth ),
		'service'    => $service,
		'preflight'  => $preflight,
		'proposals'  => $proposals,
		'audit'      => $audit,
	);
}

/**
 * Resets captured observability action events.
 *
 * @return void
 */
function npcink_governance_core_fail_closed_reset_observability_events(): void {
	global $npcink_governance_core_fail_closed_actions;

	$npcink_governance_core_fail_closed_actions = array();
}

/**
 * Returns captured observability payloads.
 *
 * @param string $event_kind Optional event kind filter.
 * @return array<int,array<string,mixed>>
 */
function npcink_governance_core_fail_closed_observability_events( string $event_kind = '' ): array {
	global $npcink_governance_core_fail_closed_actions;

	$actions = is_array( $npcink_governance_core_fail_closed_actions ?? null ) ? $npcink_governance_core_fail_closed_actions : array();
	$events  = array();

	foreach ( $actions as $action ) {
		if ( 'npcink_governance_core_observability_event' !== (string) ( $action['hook'] ?? '' ) ) {
			continue;
		}

		$args    = is_array( $action['args'] ?? null ) ? $action['args'] : array();
		$payload = is_array( $args[0] ?? null ) ? $args[0] : array();
		if ( '' !== $event_kind && $event_kind !== (string) ( $payload['event_kind'] ?? '' ) ) {
			continue;
		}

		$events[] = $payload;
	}

	return $events;
}

/**
 * Asserts that an observability event contains metadata only.
 *
 * @param array<string,mixed> $event Event payload.
 * @param string              $message Assertion message prefix.
 * @return void
 */
function npcink_governance_core_fail_closed_assert_observability_metadata_only( array $event, string $message ): void {
	foreach ( array( 'input', 'preview', 'caller', 'proposal', 'policy', 'note', 'payload', 'payload_json', 'raw' ) as $forbidden_key ) {
		npcink_governance_core_fail_closed_assert( ! array_key_exists( $forbidden_key, $event ), $message . ' omits forbidden key ' . $forbidden_key . '.' );
	}

	foreach ( $event as $key => $value ) {
		npcink_governance_core_fail_closed_assert( ! is_array( $value ), $message . ' keeps field ' . $key . ' bounded to a scalar.' );
	}

	$event_id = (string) ( $event['event_id'] ?? '' );
	npcink_governance_core_fail_closed_assert( '' !== $event_id, $message . ' includes a stable event id.' );
	npcink_governance_core_fail_closed_assert( 1 === preg_match( '/^[a-z0-9_]+$/', $event_id ), $message . ' event id is bounded to safe characters.' );

	$json = wp_json_encode( $event );
	$json = is_string( $json ) ? $json : '';
	foreach ( array( 'RAW_GENERATED_CONTENT_SENTINEL', 'APPROVAL_NOTE_SENTINEL', 'REJECTION_NOTE_SENTINEL', 'CALLER_SECRET_SENTINEL', 'POLICY_PAYLOAD_SENTINEL' ) as $sentinel ) {
		npcink_governance_core_fail_closed_assert( false === strpos( $json, $sentinel ), $message . ' does not expose ' . $sentinel . '.' );
	}
}

/**
 * Returns a plan-to-proposal service stack.
 *
 * @return array{service:\Npcink\GovernanceCore\Governance\Plan_Proposal_Service,proposal_service:\Npcink\GovernanceCore\Governance\Proposal_Service,preflight:\Npcink\GovernanceCore\Governance\Commit_Preflight_Service,proposals:\Npcink\GovernanceCore\Governance\Proposal_Repository,audit:\Npcink\GovernanceCore\Audit\Audit_Log_Repository}
 */
function npcink_governance_core_fail_closed_plan_stack(): array {
	$proposals = new \Npcink\GovernanceCore\Governance\Proposal_Repository();
	$abilities = new \Npcink\GovernanceCore\Capabilities\Ability_Registry_Adapter();
	$audit     = new \Npcink\GovernanceCore\Audit\Audit_Log_Repository();
	$proposal_service = new \Npcink\GovernanceCore\Governance\Proposal_Service(
		$proposals,
		$abilities,
		$audit,
		new \Npcink\GovernanceCore\Governance\Approval_Policy_Evaluator()
	);
	$preflight = new \Npcink\GovernanceCore\Governance\Commit_Preflight_Service( $proposals, $abilities, $audit );

	return array(
		'service'          => new \Npcink\GovernanceCore\Governance\Plan_Proposal_Service( $abilities, $proposal_service, $audit, new \Npcink\GovernanceCore\Governance\Plan_Contract_Validator() ),
		'proposal_service' => $proposal_service,
		'preflight'        => $preflight,
		'proposals'        => $proposals,
		'audit'            => $audit,
	);
}

/**
 * Creates a valid proposal payload.
 *
 * @return array<string,mixed>
 */
function npcink_governance_core_fail_closed_payload(): array {
	return array(
		'ability_id' => 'npcink-abilities-toolkit/create-draft',
		'title'      => 'Draft proposal',
		'summary'    => 'Create a draft.',
		'input'      => array( 'dry_run' => true ),
		'preview'    => array( 'after_suggestion' => 'Draft content' ),
		'caller'     => array( 'source' => 'fault_injection' ),
	);
}

/**
 * Creates a representative governance payload.
 *
 * @param string $ability_id Ability id.
 * @return array<string,mixed>
 */
function npcink_governance_core_fail_closed_governance_payload( string $ability_id ): array {
	$inputs = array(
		'npcink-abilities-toolkit/create-draft'     => array( 'title' => 'Governed draft', 'dry_run' => true, 'commit' => false, 'idempotency_key' => 'draft-1' ),
		'npcink-abilities-toolkit/update-post'      => array( 'post_id' => 101, 'title' => 'Updated title', 'dry_run' => true, 'commit' => false, 'idempotency_key' => 'update-1' ),
		'npcink-abilities-toolkit/set-post-terms'   => array( 'post_id' => 101, 'taxonomy' => 'post_tag', 'term_ids' => array( 7 ), 'dry_run' => true, 'commit' => false, 'idempotency_key' => 'terms-1' ),
		'npcink-abilities-toolkit/approve-comment'  => array( 'comment_id' => 55, 'dry_run' => true, 'commit' => false, 'idempotency_key' => 'comment-1' ),
		'npcink-abilities-toolkit/trash-post'       => array( 'post_id' => 101, 'dry_run' => true, 'commit' => false, 'idempotency_key' => 'trash-1' ),
	);

	return array(
		'ability_id' => $ability_id,
		'title'      => 'Governance negative smoke: ' . $ability_id,
		'summary'    => 'Representative proposal for negative governance smoke.',
		'input'      => $inputs[ $ability_id ] ?? array( 'dry_run' => true, 'commit' => false, 'idempotency_key' => 'generic-1' ),
		'preview'    => array(
			'proposal_ready'   => true,
			'after_suggestion' => 'Preview for ' . $ability_id,
		),
		'caller'     => array( 'source' => 'governance_negative_smoke' ),
	);
}

/**
 * Creates one proposal fixture in a requested lifecycle status.
 *
 * @param string $status Proposal status.
 * @return array{stack:array<string,mixed>,proposal:array<string,mixed>,preflight?:array<string,mixed>}
 */
function npcink_governance_core_fail_closed_proposal_in_status( string $status ): array {
	$stack    = npcink_governance_core_fail_closed_governance_stack();
	$proposal = $stack['service']->create( npcink_governance_core_fail_closed_governance_payload( 'npcink-abilities-toolkit/update-post' ) );
	npcink_governance_core_fail_closed_assert( ! is_wp_error( $proposal ), 'Status matrix fixture proposal is created.' );

	$proposal_id = (string) $proposal['proposal_id'];
	if ( \Npcink\GovernanceCore\Governance\Proposal_Repository::STATUS_PENDING === $status ) {
		return array(
			'stack'    => $stack,
			'proposal' => $proposal,
		);
	}

	if ( \Npcink\GovernanceCore\Governance\Proposal_Repository::STATUS_APPROVED === $status ) {
		$approved = $stack['service']->approve( $proposal_id, array( 'reason' => 'status_matrix_setup' ) );
		npcink_governance_core_fail_closed_assert( ! is_wp_error( $approved ), 'Status matrix fixture proposal is approved.' );
		return array(
			'stack'    => $stack,
			'proposal' => $approved,
		);
	}

	if ( \Npcink\GovernanceCore\Governance\Proposal_Repository::STATUS_REJECTED === $status ) {
		$rejected = $stack['service']->reject( $proposal_id, array( 'reason' => 'status_matrix_setup' ) );
		npcink_governance_core_fail_closed_assert( ! is_wp_error( $rejected ), 'Status matrix fixture proposal is rejected.' );
		return array(
			'stack'    => $stack,
			'proposal' => $rejected,
		);
	}

	if ( \Npcink\GovernanceCore\Governance\Proposal_Repository::STATUS_EXPIRED === $status || \Npcink\GovernanceCore\Governance\Proposal_Repository::STATUS_ARCHIVED === $status ) {
		$expired = $stack['proposals']->update_status_when(
			$proposal_id,
			\Npcink\GovernanceCore\Governance\Proposal_Repository::STATUS_PENDING,
			\Npcink\GovernanceCore\Governance\Proposal_Repository::STATUS_EXPIRED
		);
		npcink_governance_core_fail_closed_assert( is_array( $expired ), 'Status matrix fixture proposal is expired.' );
		if ( \Npcink\GovernanceCore\Governance\Proposal_Repository::STATUS_EXPIRED === $status ) {
			return array(
				'stack'    => $stack,
				'proposal' => $expired,
			);
		}

		$archived = $stack['service']->archive( $proposal_id, array( 'reason' => 'status_matrix_setup' ) );
		npcink_governance_core_fail_closed_assert( ! is_wp_error( $archived ), 'Status matrix fixture proposal is archived.' );
		return array(
			'stack'    => $stack,
			'proposal' => $archived,
		);
	}

	if ( \Npcink\GovernanceCore\Governance\Proposal_Repository::STATUS_EXECUTED === $status || \Npcink\GovernanceCore\Governance\Proposal_Repository::STATUS_EXECUTION_FAILED === $status ) {
		$approved = $stack['service']->approve( $proposal_id, array( 'reason' => 'status_matrix_terminal_setup' ) );
		npcink_governance_core_fail_closed_assert( ! is_wp_error( $approved ), 'Status matrix terminal fixture proposal is approved.' );
		$preflight = $stack['preflight']->preflight( $proposal_id );
		npcink_governance_core_fail_closed_assert( ! is_wp_error( $preflight ), 'Status matrix terminal fixture proposal passes preflight.' );
		$recorded = $stack['service']->record_execution_result(
			$proposal_id,
			array(
				'execution_status'    => \Npcink\GovernanceCore\Governance\Proposal_Repository::STATUS_EXECUTED === $status ? 'succeeded' : 'failed',
				'correlation_id'      => (string) ( $preflight['correlation_id'] ?? '' ),
				'approved_input_hash' => (string) ( $preflight['approval_context']['approved_input_hash'] ?? '' ),
				'adapter_request_id'  => 'status-matrix-terminal',
				'execution_mode'      => 'single_post',
				'executed_count'      => \Npcink\GovernanceCore\Governance\Proposal_Repository::STATUS_EXECUTED === $status ? 1 : 0,
				'failed_count'        => \Npcink\GovernanceCore\Governance\Proposal_Repository::STATUS_EXECUTION_FAILED === $status ? 1 : 0,
			)
		);
		npcink_governance_core_fail_closed_assert( ! is_wp_error( $recorded ), 'Status matrix terminal fixture records execution result.' );
		return array(
			'stack'     => $stack,
			'proposal'  => $recorded,
			'preflight' => $preflight,
		);
	}

	npcink_governance_core_fail_closed_assert( false, 'Unsupported status matrix fixture status: ' . $status );
	return array(
		'stack'    => $stack,
		'proposal' => $proposal,
	);
}

/**
 * Runs one lifecycle operation for the proposal status matrix.
 *
 * @param array<string,mixed> $fixture Matrix fixture.
 * @param string              $operation Operation key.
 * @return mixed
 */
function npcink_governance_core_fail_closed_run_status_matrix_operation( array $fixture, string $operation ) {
	$stack       = $fixture['stack'];
	$proposal_id = (string) ( $fixture['proposal']['proposal_id'] ?? '' );

	if ( 'approve' === $operation ) {
		return $stack['service']->approve( $proposal_id, array( 'reason' => 'status_matrix' ) );
	}
	if ( 'reject' === $operation ) {
		return $stack['service']->reject( $proposal_id, array( 'reason' => 'status_matrix' ) );
	}
	if ( 'archive' === $operation ) {
		return $stack['service']->archive( $proposal_id, array( 'reason' => 'status_matrix' ) );
	}
	if ( 'reopen' === $operation ) {
		return $stack['service']->reopen( $proposal_id, array( 'reason' => 'status_matrix' ) );
	}
	if ( 'record_execution_without_handoff' === $operation ) {
		return $stack['service']->record_execution_result(
			$proposal_id,
			array(
				'execution_status'    => 'succeeded',
				'correlation_id'      => 'status-matrix-no-matching-handoff',
				'approved_input_hash' => str_repeat( 'f', 64 ),
				'adapter_request_id'  => 'status-matrix',
				'execution_mode'      => 'single_post',
				'executed_count'      => 1,
			)
		);
	}

	npcink_governance_core_fail_closed_assert( false, 'Unsupported status matrix operation: ' . $operation );
	return null;
}

/**
 * Creates one controller-backed proposal fixture in a requested lifecycle status.
 *
 * @param string $status Proposal status.
 * @return array{stack:array<string,mixed>,proposal:array<string,mixed>,preflight?:array<string,mixed>}
 */
function npcink_governance_core_fail_closed_rest_proposal_in_status( string $status ): array {
	$stack    = npcink_governance_core_fail_closed_proposals_controller_stack();
	$proposal = $stack['service']->create( npcink_governance_core_fail_closed_governance_payload( 'npcink-abilities-toolkit/update-post' ) );
	npcink_governance_core_fail_closed_assert( ! is_wp_error( $proposal ), 'REST status matrix fixture proposal is created.' );

	$proposal_id = (string) $proposal['proposal_id'];
	if ( \Npcink\GovernanceCore\Governance\Proposal_Repository::STATUS_PENDING === $status ) {
		return array(
			'stack'    => $stack,
			'proposal' => $proposal,
		);
	}

	if ( \Npcink\GovernanceCore\Governance\Proposal_Repository::STATUS_APPROVED === $status ) {
		$approved = $stack['service']->approve( $proposal_id, array( 'reason' => 'rest_status_matrix_setup' ) );
		npcink_governance_core_fail_closed_assert( ! is_wp_error( $approved ), 'REST status matrix fixture proposal is approved.' );
		return array(
			'stack'    => $stack,
			'proposal' => $approved,
		);
	}

	if ( \Npcink\GovernanceCore\Governance\Proposal_Repository::STATUS_REJECTED === $status ) {
		$rejected = $stack['service']->reject( $proposal_id, array( 'reason' => 'rest_status_matrix_setup' ) );
		npcink_governance_core_fail_closed_assert( ! is_wp_error( $rejected ), 'REST status matrix fixture proposal is rejected.' );
		return array(
			'stack'    => $stack,
			'proposal' => $rejected,
		);
	}

	if ( \Npcink\GovernanceCore\Governance\Proposal_Repository::STATUS_EXPIRED === $status || \Npcink\GovernanceCore\Governance\Proposal_Repository::STATUS_ARCHIVED === $status ) {
		$expired = $stack['proposals']->update_status_when(
			$proposal_id,
			\Npcink\GovernanceCore\Governance\Proposal_Repository::STATUS_PENDING,
			\Npcink\GovernanceCore\Governance\Proposal_Repository::STATUS_EXPIRED
		);
		npcink_governance_core_fail_closed_assert( is_array( $expired ), 'REST status matrix fixture proposal is expired.' );
		if ( \Npcink\GovernanceCore\Governance\Proposal_Repository::STATUS_EXPIRED === $status ) {
			return array(
				'stack'    => $stack,
				'proposal' => $expired,
			);
		}

		$archived = $stack['service']->archive( $proposal_id, array( 'reason' => 'rest_status_matrix_setup' ) );
		npcink_governance_core_fail_closed_assert( ! is_wp_error( $archived ), 'REST status matrix fixture proposal is archived.' );
		return array(
			'stack'    => $stack,
			'proposal' => $archived,
		);
	}

	if ( \Npcink\GovernanceCore\Governance\Proposal_Repository::STATUS_EXECUTED === $status || \Npcink\GovernanceCore\Governance\Proposal_Repository::STATUS_EXECUTION_FAILED === $status ) {
		$approved = $stack['service']->approve( $proposal_id, array( 'reason' => 'rest_status_matrix_terminal_setup' ) );
		npcink_governance_core_fail_closed_assert( ! is_wp_error( $approved ), 'REST status matrix terminal fixture proposal is approved.' );
		$preflight = $stack['preflight']->preflight( $proposal_id );
		npcink_governance_core_fail_closed_assert( ! is_wp_error( $preflight ), 'REST status matrix terminal fixture proposal passes preflight.' );
		$recorded = $stack['service']->record_execution_result(
			$proposal_id,
			array(
				'execution_status'    => \Npcink\GovernanceCore\Governance\Proposal_Repository::STATUS_EXECUTED === $status ? 'succeeded' : 'failed',
				'correlation_id'      => (string) ( $preflight['correlation_id'] ?? '' ),
				'approved_input_hash' => (string) ( $preflight['approval_context']['approved_input_hash'] ?? '' ),
				'adapter_request_id'  => 'rest-status-matrix-terminal',
				'execution_mode'      => 'single_post',
				'executed_count'      => \Npcink\GovernanceCore\Governance\Proposal_Repository::STATUS_EXECUTED === $status ? 1 : 0,
				'failed_count'        => \Npcink\GovernanceCore\Governance\Proposal_Repository::STATUS_EXECUTION_FAILED === $status ? 1 : 0,
			)
		);
		npcink_governance_core_fail_closed_assert( ! is_wp_error( $recorded ), 'REST status matrix terminal fixture records execution result.' );
		return array(
			'stack'     => $stack,
			'proposal'  => $recorded,
			'preflight' => $preflight,
		);
	}

	npcink_governance_core_fail_closed_assert( false, 'Unsupported REST status matrix fixture status: ' . $status );
	return array(
		'stack'    => $stack,
		'proposal' => $proposal,
	);
}

/**
 * Returns one WP_Error status code.
 *
 * @param WP_Error $error Error.
 * @return int
 */
function npcink_governance_core_fail_closed_error_http_status( WP_Error $error ): int {
	$data = $error->get_error_data();

	return is_array( $data ) ? (int) ( $data['status'] ?? 0 ) : 0;
}

/**
 * Runs one public REST lifecycle operation for the proposal status matrix.
 *
 * @param array<string,mixed> $fixture Matrix fixture.
 * @param string              $operation Operation key.
 * @return mixed
 */
function npcink_governance_core_fail_closed_run_rest_status_matrix_operation( array $fixture, string $operation ) {
	$stack       = $fixture['stack'];
	$controller  = $stack['controller'];
	$proposal_id = (string) ( $fixture['proposal']['proposal_id'] ?? '' );

	if ( 'approve' === $operation ) {
		return $controller->approve_proposal( new WP_REST_Request( array( 'proposal_id' => $proposal_id ) ) );
	}
	if ( 'reject' === $operation ) {
		return $controller->reject_proposal( new WP_REST_Request( array( 'proposal_id' => $proposal_id ) ) );
	}
	if ( 'commit_preflight' === $operation ) {
		return $controller->commit_preflight( new WP_REST_Request( array( 'proposal_id' => $proposal_id ) ) );
	}
	if ( 'record_execution_without_handoff' === $operation ) {
		return $controller->record_execution(
			new WP_REST_Request(
				array(
					'proposal_id'          => $proposal_id,
					'execution_status'     => 'succeeded',
					'correlation_id'       => 'rest-status-matrix-no-matching-handoff',
					'approved_input_hash'  => str_repeat( 'e', 64 ),
					'adapter_request_id'   => 'rest-status-matrix',
					'execution_mode'       => 'single_post',
					'executed_count'       => 1,
				)
			)
		);
	}
	if ( 'record_execution_with_handoff' === $operation ) {
		if ( \Npcink\GovernanceCore\Governance\Proposal_Repository::STATUS_APPROVED !== (string) ( $fixture['proposal']['status'] ?? '' ) ) {
			return $controller->record_execution(
				new WP_REST_Request(
					array(
						'proposal_id'          => $proposal_id,
						'execution_status'     => 'succeeded',
						'correlation_id'       => 'rest-status-matrix-no-matching-handoff',
						'approved_input_hash'  => str_repeat( 'e', 64 ),
						'adapter_request_id'   => 'rest-status-matrix',
						'execution_mode'       => 'single_post',
						'executed_count'       => 1,
					)
				)
			);
		}

		$preflight = $controller->commit_preflight( new WP_REST_Request( array( 'proposal_id' => $proposal_id ) ) );
		if ( is_wp_error( $preflight ) ) {
			return $preflight;
		}
		npcink_governance_core_fail_closed_assert( $preflight instanceof WP_REST_Response, 'REST status matrix execution fixture preflight returns a response.' );
		$data             = $preflight->get_data();
		$approval_context = is_array( $data ) && is_array( $data['approval_context'] ?? null ) ? $data['approval_context'] : array();

		return $controller->record_execution(
			new WP_REST_Request(
				array(
					'proposal_id'          => $proposal_id,
					'execution_status'     => 'succeeded',
					'correlation_id'       => (string) ( is_array( $data ) ? ( $data['correlation_id'] ?? '' ) : '' ),
					'approved_input_hash'  => (string) ( $approval_context['approved_input_hash'] ?? '' ),
					'adapter_request_id'   => 'rest-status-matrix',
					'execution_mode'       => 'single_post',
					'executed_count'       => 1,
				)
			)
		);
	}

	npcink_governance_core_fail_closed_assert( false, 'Unsupported REST status matrix operation: ' . $operation );
	return null;
}

/**
 * Returns the expected REST status matrix error code for a blocked operation.
 *
 * @param string $status Proposal status.
 * @param string $operation Operation key.
 * @return string
 */
function npcink_governance_core_fail_closed_rest_status_matrix_error_code( string $status, string $operation ): string {
	if ( 'approve' === $operation || 'reject' === $operation ) {
		return 'npcink_governance_core_proposal_already_decided';
	}
	if ( 'commit_preflight' === $operation ) {
		return 'npcink_governance_core_proposal_not_approved';
	}
	if ( in_array( $operation, array( 'record_execution_without_handoff', 'record_execution_with_handoff' ), true ) ) {
		if ( in_array( $status, array( \Npcink\GovernanceCore\Governance\Proposal_Repository::STATUS_EXECUTED, \Npcink\GovernanceCore\Governance\Proposal_Repository::STATUS_EXECUTION_FAILED ), true ) ) {
			return 'npcink_governance_core_execution_record_already_recorded';
		}
		if ( \Npcink\GovernanceCore\Governance\Proposal_Repository::STATUS_APPROVED === $status ) {
			return 'npcink_governance_core_execution_record_preflight_missing';
		}

		return 'npcink_governance_core_execution_record_not_allowed';
	}

	return '';
}

/**
 * Creates a representative Gutenberg block input.
 *
 * @return array<string,mixed>
 */
function npcink_governance_core_fail_closed_update_post_blocks_input(): array {
	return array(
		'post_id'            => 5791,
		'mode'               => 'replace',
		'validate_roundtrip' => true,
		'dry_run'            => true,
		'commit'             => false,
		'blocks'             => array(
			array(
				'blockName'    => 'core/group',
				'attrs'        => array(
					'layout'     => array(
						'type'        => 'constrained',
						'contentSize' => '1120px',
					),
					'style'      => array(
						'typography' => array(
							'fontSize'      => '18px',
							'letterSpacing' => '0',
							'textTransform' => 'none',
						),
					),
					'className'  => 'wp-ai-section',
					'anchorName' => 'hero',
				),
				'innerBlocks'  => array(
					array(
						'blockName'    => 'core/paragraph',
						'attrs'        => array( 'fontSize' => 'large' ),
						'innerHTML'    => '<p>Reviewed block body<script>alert(1)</script></p>',
						'innerBlocks'  => array(),
						'innerContent' => array( '<p>Reviewed block body<script>alert(1)</script></p>' ),
					),
				),
				'innerHTML'    => '<div class="wp-block-group"><p>Reviewed group</p><script>alert(1)</script></div>',
				'innerContent' => array( '<div class="wp-block-group">', null, '</div>' ),
			),
		),
	);
}

/**
 * Returns audit rows for a proposal id and event name.
 *
 * @param string $proposal_id Proposal id.
 * @param string $event_name Event name.
 * @return array<int,array<string,mixed>>
 */
function npcink_governance_core_fail_closed_audit_rows( string $proposal_id, string $event_name ): array {
	global $wpdb;

	return array_values(
		array_filter(
			$wpdb->rows( 'wp_npcink_governance_core_audit_log' ),
			static function ( array $row ) use ( $proposal_id, $event_name ): bool {
				return $proposal_id === (string) ( $row['proposal_id'] ?? '' ) && $event_name === (string) ( $row['event_name'] ?? '' );
			}
		)
	);
}

/**
 * Marks the latest test preflight handoff for one proposal as expired.
 *
 * @param string $proposal_id Proposal id.
 * @return void
 */
function npcink_governance_core_fail_closed_expire_preflight_handoff( string $proposal_id ): void {
	global $wpdb;

	$rows = npcink_governance_core_fail_closed_audit_rows( $proposal_id, 'commit.preflighted' );
	npcink_governance_core_fail_closed_assert( 1 === count( $rows ), 'Expired preflight fixture has one handoff to expire.' );
	$metadata = json_decode( (string) ( $rows[0]['metadata_json'] ?? '{}' ), true );
	$metadata = is_array( $metadata ) ? $metadata : array();
	$metadata['expires_at'] = gmdate( 'c', time() - 60 );

	$updated = $wpdb->update(
		'wp_npcink_governance_core_audit_log',
		array( 'metadata_json' => wp_json_encode( $metadata ) ),
		array(
			'proposal_id' => $proposal_id,
			'event_name'  => 'commit.preflighted',
		)
	);
	npcink_governance_core_fail_closed_assert( false !== $updated && 1 <= (int) $updated, 'Expired preflight fixture updates handoff TTL.' );
}

/**
 * Applies one mutation to the fake ability registry.
 *
 * @param string   $ability_id Ability id.
 * @param callable $mutator Mutation callback.
 * @return void
 */
function npcink_governance_core_fail_closed_mutate_ability( string $ability_id, callable $mutator ): void {
	global $npcink_governance_core_fail_closed_abilities;

	$npcink_governance_core_fail_closed_abilities = npcink_abilities_toolkit_get_registered();
	$ability = is_array( $npcink_governance_core_fail_closed_abilities[ $ability_id ] ?? null ) ? $npcink_governance_core_fail_closed_abilities[ $ability_id ] : array();
	$ability = $mutator( $ability );
	npcink_governance_core_fail_closed_assert( is_array( $ability ), 'Ability drift mutator returns an ability definition.' );
	$npcink_governance_core_fail_closed_abilities[ $ability_id ] = $ability;
}

