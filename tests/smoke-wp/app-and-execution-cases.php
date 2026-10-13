<?php
/** App and execution cases test module. @package NpcinkGovernanceCore */

$app_proposal_payload = array(
	'ability_id' => 'npcink-abilities-toolkit/create-draft',
	'title'      => 'App authenticated proposal ' . $npcink_governance_core_smoke_run_id,
	'summary'    => 'Created by app key smoke test.',
	'input'      => array(
		'title'   => 'App Auth Draft ' . $npcink_governance_core_smoke_run_id,
		'content' => '<p>App auth content.</p>',
		'dry_run' => true,
	),
	'preview'    => array(
		'dry_run' => true,
	),
	'caller'     => array(
		'source' => 'app-auth-smoke:' . $npcink_governance_core_smoke_run_id,
	),
);
$app_created = npcink_governance_core_smoke_rest_as_app(
	'POST',
	'/npcink-governance-core/v1/proposals',
	$app_token,
	$app_proposal_payload
);
$app_proposal_id = (string) ( $app_created['proposal_id'] ?? '' );
npcink_governance_core_smoke_assert( '' !== $app_proposal_id, 'app-authenticated proposal is created' );
npcink_governance_core_smoke_assert( $app_id === (string) ( $app_created['caller']['auth']['app_id'] ?? '' ), 'app-authenticated proposal stores app attribution' );
npcink_governance_core_smoke_assert( 'proposals:create' === (string) ( $app_created['caller']['auth']['scope'] ?? '' ), 'app-authenticated proposal stores scope attribution' );
npcink_governance_core_smoke_assert( 'allowed' === (string) ( $app_created['caller']['auth']['scope_decision'] ?? '' ), 'app-authenticated proposal stores allowed scope decision' );
$app_duplicate = npcink_governance_core_smoke_rest_result_as_app( 'POST', '/npcink-governance-core/v1/proposals', $app_token, $app_proposal_payload );
npcink_governance_core_smoke_assert( 200 === (int) $app_duplicate['status'], 'duplicate app proposal returns existing pending proposal with HTTP 200' );
$app_duplicate_data = is_array( $app_duplicate['data'] ?? null ) ? $app_duplicate['data'] : array();
npcink_governance_core_smoke_assert( $app_proposal_id === (string) ( $app_duplicate_data['proposal_id'] ?? '' ), 'duplicate app proposal reuses the existing pending proposal id' );
npcink_governance_core_smoke_assert( true === (bool) ( $app_duplicate_data['deduplicated'] ?? false ), 'duplicate app proposal response is marked deduplicated' );

$app_approve = npcink_governance_core_smoke_rest_result_as_app( 'POST', '/npcink-governance-core/v1/proposals/' . rawurlencode( $app_proposal_id ) . '/approve', $app_token );
npcink_governance_core_smoke_assert( 403 === (int) $app_approve['status'], 'app-authenticated approval is denied without approval scope' );

npcink_governance_core_smoke_rest(
	'POST',
	'/npcink-governance-core/v1/proposals/' . rawurlencode( $app_proposal_id ) . '/approve',
	array(
		'note' => 'Admin approval for app-authenticated proposal.',
	)
);

$app_preflight = npcink_governance_core_smoke_rest_as_app( 'POST', '/npcink-governance-core/v1/proposals/' . rawurlencode( $app_proposal_id ) . '/commit-preflight', $app_token );
npcink_governance_core_smoke_assert( false === (bool) ( $app_preflight['commit_execution'] ?? true ), 'app-authenticated commit preflight does not execute ability' );
npcink_governance_core_smoke_assert( true === (bool) ( $app_preflight['approval_context']['approval_commit_authorized'] ?? false ), 'app-authenticated commit preflight returns approval context' );
npcink_governance_core_smoke_assert( '' !== (string) ( $app_preflight['correlation_id'] ?? '' ), 'app-authenticated commit preflight returns correlation id' );
npcink_governance_core_smoke_assert( '' !== (string) ( $app_preflight['approval_context']['approved_input_hash'] ?? '' ), 'app-authenticated commit preflight returns approved input hash' );
npcink_governance_core_smoke_assert( 'core-preflight-v1' === (string) ( $app_preflight['approval_context']['policy_version'] ?? '' ), 'app-authenticated commit preflight returns policy version' );
npcink_governance_core_smoke_assert( 'adapter_after_core_preflight' === (string) ( $app_preflight['execution_handoff']['executor'] ?? '' ), 'app-authenticated commit preflight returns adapter execution handoff' );
npcink_governance_core_smoke_assert( false === (bool) ( $app_preflight['execution_handoff']['commit_execution'] ?? true ), 'app-authenticated execution handoff keeps Core commit execution disabled' );

$quota_app = npcink_governance_core_smoke_rest(
	'POST',
	'/npcink-governance-core/v1/apps',
	array(
		'app_label'           => 'OpenClaw pending quota smoke ' . $npcink_governance_core_smoke_run_id,
		'scopes'              => array( 'proposals:create' ),
		'rate_limit'          => 100,
		'rate_window_seconds' => 3600,
	)
);
$quota_token = (string) ( $quota_app['token'] ?? '' );
for ( $quota_index = 1; $quota_index <= 20; ++$quota_index ) {
	npcink_governance_core_smoke_rest_as_app(
		'POST',
		'/npcink-governance-core/v1/proposals',
		$quota_token,
		array(
			'ability_id' => 'npcink-abilities-toolkit/create-draft',
			'title'      => 'Pending quota proposal ' . $npcink_governance_core_smoke_run_id . ' ' . $quota_index,
			'summary'    => 'Created to verify pending proposal quota.',
			'input'      => array(
				'title'   => 'Pending quota draft ' . $npcink_governance_core_smoke_run_id . ' ' . $quota_index,
				'content' => '<p>Pending quota smoke.</p>',
				'dry_run' => true,
			),
			'preview'    => array(
				'dry_run' => true,
			),
			'caller'     => array(
				'source' => 'pending-quota-smoke:' . $npcink_governance_core_smoke_run_id,
			),
		)
	);
}
$quota_blocked = npcink_governance_core_smoke_rest_result_as_app(
	'POST',
	'/npcink-governance-core/v1/proposals',
	$quota_token,
	array(
		'ability_id' => 'npcink-abilities-toolkit/create-draft',
		'title'      => 'Pending quota proposal blocked ' . $npcink_governance_core_smoke_run_id,
		'summary'    => 'This should be blocked by pending proposal quota.',
		'input'      => array(
			'title'   => 'Pending quota blocked draft ' . $npcink_governance_core_smoke_run_id,
			'content' => '<p>Pending quota smoke.</p>',
			'dry_run' => true,
		),
		'preview'    => array(
			'dry_run' => true,
		),
		'caller'     => array(
			'source' => 'pending-quota-smoke:' . $npcink_governance_core_smoke_run_id,
		),
	)
);
npcink_governance_core_smoke_assert( 429 === (int) $quota_blocked['status'], 'app pending proposal quota blocks the twenty-first pending proposal' );
$quota_blocked_data = is_array( $quota_blocked['data'] ?? null ) ? $quota_blocked['data'] : array();
npcink_governance_core_smoke_assert( 'npcink_governance_core_pending_proposal_quota_exceeded' === (string) ( $quota_blocked_data['code'] ?? '' ), 'pending proposal quota returns stable error code' );

$app_audit_denied = npcink_governance_core_smoke_rest_result_as_app( 'GET', '/npcink-governance-core/v1/audit', $app_token, array( 'limit' => 5 ) );
npcink_governance_core_smoke_assert( 403 === (int) $app_audit_denied['status'], 'app-authenticated audit read is denied without audit scope' );

$trusted_adapter_app = npcink_governance_core_smoke_rest(
	'POST',
	'/npcink-governance-core/v1/apps',
	array(
		'app_label'           => 'Trusted Adapter approve smoke ' . $npcink_governance_core_smoke_run_id,
		'caller_type'         => 'trusted_adapter',
		'scopes'              => array( 'capabilities:read', 'proposals:create', 'proposals:read', 'proposals:approve', 'commit:preflight' ),
		'rate_limit'          => 20,
		'rate_window_seconds' => 3600,
	)
);
$trusted_adapter_token = (string) ( $trusted_adapter_app['token'] ?? '' );
$trusted_adapter_app_id = (string) ( $trusted_adapter_app['app_id'] ?? '' );
npcink_governance_core_smoke_assert( '' !== $trusted_adapter_token && '' !== $trusted_adapter_app_id, 'trusted Adapter app key is created for approve-and-execute smoke' );

$trusted_draft_title = 'Trusted Adapter Draft ' . $npcink_governance_core_smoke_run_id;
$trusted_created = npcink_governance_core_smoke_rest_as_app(
	'POST',
	'/npcink-governance-core/v1/proposals',
	$trusted_adapter_token,
	array(
		'ability_id' => 'npcink-abilities-toolkit/create-draft',
		'title'      => 'Trusted Adapter approval proposal ' . $npcink_governance_core_smoke_run_id,
		'summary'    => 'Created by trusted Adapter approval smoke test.',
		'input'      => array(
			'title'   => $trusted_draft_title,
			'content' => '<p>Trusted Adapter content.</p>',
			'dry_run' => true,
			'commit'  => false,
		),
		'preview'    => array(
			'dry_run'          => true,
			'commit_execution' => false,
		),
		'caller'     => array(
			'source' => 'trusted-adapter-smoke:' . $npcink_governance_core_smoke_run_id,
		),
	)
);
$trusted_proposal_id = (string) ( $trusted_created['proposal_id'] ?? '' );
npcink_governance_core_smoke_assert( '' !== $trusted_proposal_id, 'trusted Adapter app creates proposal' );

$trusted_approved = npcink_governance_core_smoke_rest_as_app(
	'POST',
	'/npcink-governance-core/v1/proposals/' . rawurlencode( $trusted_proposal_id ) . '/approve',
	$trusted_adapter_token,
	array(
		'note' => 'Trusted Adapter approve-and-execute smoke approval.',
	)
);
npcink_governance_core_smoke_assert( 'approved' === (string) ( $trusted_approved['status'] ?? '' ), 'trusted Adapter app approves proposal with approval scope' );

$trusted_preflight = npcink_governance_core_smoke_rest_as_app( 'POST', '/npcink-governance-core/v1/proposals/' . rawurlencode( $trusted_proposal_id ) . '/commit-preflight', $trusted_adapter_token );
npcink_governance_core_smoke_assert( true === (bool) ( $trusted_preflight['approval_context']['approval_commit_authorized'] ?? false ), 'trusted Adapter app receives approval context after approval' );
npcink_governance_core_smoke_assert( 'adapter_after_core_preflight' === (string) ( $trusted_preflight['execution_handoff']['executor'] ?? '' ), 'trusted Adapter preflight returns execution handoff' );
npcink_governance_core_smoke_assert( 'npcink-abilities-toolkit/create-draft' === (string) ( $trusted_preflight['execution_handoff']['ability_id'] ?? '' ), 'trusted Adapter execution handoff includes target ability id' );
npcink_governance_core_smoke_assert( (string) ( $trusted_preflight['approval_context']['approved_input_hash'] ?? '' ) === (string) ( $trusted_preflight['execution_handoff']['approved_input_hash'] ?? '' ), 'trusted Adapter execution handoff carries approved input hash' );
npcink_governance_core_smoke_assert( 'core-preflight-v1' === (string) ( $trusted_preflight['execution_handoff']['policy_version'] ?? '' ), 'trusted Adapter execution handoff carries policy version' );
npcink_governance_core_smoke_assert( false === (bool) ( $trusted_preflight['execution_handoff']['core_proxy_execute'] ?? true ), 'trusted Adapter execution handoff keeps Core proxy execution disabled' );
npcink_governance_core_smoke_assert( false === (bool) ( $trusted_preflight['execution_handoff']['commit_execution'] ?? true ), 'trusted Adapter execution handoff keeps Core final execution disabled' );

$trusted_external_input = is_array( $trusted_preflight['proposal']['input'] ?? null ) ? $trusted_preflight['proposal']['input'] : array();
npcink_governance_core_smoke_assert( (string) ( $trusted_preflight['execution_handoff']['approved_input_hash'] ?? '' ) === hash( 'sha256', (string) wp_json_encode( $trusted_external_input ) ), 'trusted Adapter external execution input matches Core approved input hash' );
$trusted_external_result = npcink_governance_core_smoke_run_external_ability_dry_run( 'npcink-abilities-toolkit/create-draft', $trusted_external_input );
npcink_governance_core_smoke_assert( true === (bool) ( $trusted_external_result['dry_run'] ?? false ), 'trusted Adapter external WordPress Abilities API execution stays dry-run' );
npcink_governance_core_smoke_assert( true === (bool) ( $trusted_external_result['host_governed'] ?? false ), 'trusted Adapter external WordPress Abilities API execution is host-governed' );
npcink_governance_core_smoke_assert( true === (bool) ( $trusted_external_result['commit_required'] ?? false ), 'trusted Adapter external WordPress Abilities API execution still requires commit approval for mutation' );
npcink_governance_core_smoke_assert( 0 === (int) ( $trusted_external_result['post_id'] ?? -1 ), 'trusted Adapter external dry-run does not create a post id' );
npcink_governance_core_smoke_assert( 'dry_run' === (string) ( $trusted_external_result['status'] ?? '' ), 'trusted Adapter external execution returns dry-run status' );
npcink_governance_core_smoke_assert( 'create_draft' === (string) ( $trusted_external_result['preview']['action'] ?? '' ), 'trusted Adapter external execution runs the target create-draft ability' );
npcink_governance_core_smoke_assert( 0 === npcink_governance_core_smoke_find_post_id_by_title( $trusted_draft_title, 'post' ), 'trusted Adapter external dry-run does not create the post draft' );

$trusted_record_denied = npcink_governance_core_smoke_rest_result_as_app(
	'POST',
	'/npcink-governance-core/v1/proposals/' . rawurlencode( $trusted_proposal_id ) . '/record-execution',
	$trusted_adapter_token,
	array(
		'execution_status'    => 'succeeded',
		'correlation_id'      => (string) ( $trusted_preflight['execution_handoff']['correlation_id'] ?? '' ),
		'approved_input_hash' => (string) ( $trusted_preflight['execution_handoff']['approved_input_hash'] ?? '' ),
		'adapter_request_id'  => 'trusted-record-denied-' . $npcink_governance_core_smoke_run_id,
	)
);
npcink_governance_core_smoke_assert( 403 === (int) $trusted_record_denied['status'], 'preflight-only trusted Adapter key cannot record execution result' );

$execution_recorder_app = npcink_governance_core_smoke_rest(
	'POST',
	'/npcink-governance-core/v1/apps',
	array(
		'app_label'           => 'Execution Recorder Smoke ' . $npcink_governance_core_smoke_run_id,
		'caller_type'         => 'trusted_adapter',
		'scopes'              => array( 'commit:record_execution' ),
		'rate_limit'          => 20,
		'rate_window_seconds' => 3600,
	)
);
$execution_recorder_token  = (string) ( $execution_recorder_app['token'] ?? '' );
$execution_recorder_app_id = (string) ( $execution_recorder_app['app_id'] ?? '' );
npcink_governance_core_smoke_assert( '' !== $execution_recorder_token && '' !== $execution_recorder_app_id, 'execution recorder app key is created with recording scope' );

$trusted_recorded = npcink_governance_core_smoke_rest_as_app(
	'POST',
	'/npcink-governance-core/v1/proposals/' . rawurlencode( $trusted_proposal_id ) . '/record-execution',
	$execution_recorder_token,
	array(
		'execution_status'    => 'succeeded',
		'correlation_id'      => (string) ( $trusted_preflight['execution_handoff']['correlation_id'] ?? '' ),
		'approved_input_hash' => (string) ( $trusted_preflight['execution_handoff']['approved_input_hash'] ?? '' ),
		'adapter_request_id'  => 'trusted-record-success-' . $npcink_governance_core_smoke_run_id,
		'execution_mode'      => 'wp_abilities_rest_dry_run',
		'executed_count'      => 1,
		'failed_count'        => 0,
	)
);
npcink_governance_core_smoke_assert( 'executed' === (string) ( $trusted_recorded['status'] ?? '' ), 'execution recorder app records successful external ability result' );

$rate_app = npcink_governance_core_smoke_rest(
	'POST',
	'/npcink-governance-core/v1/apps',
	array(
		'app_label'           => 'OpenClaw rate smoke ' . $npcink_governance_core_smoke_run_id,
		'caller_type'         => 'mcp_adapter',
		'scopes'              => array( 'capabilities:read' ),
		'rate_limit'          => 1,
		'rate_window_seconds' => 3600,
	)
);
$rate_token = (string) ( $rate_app['token'] ?? '' );
npcink_governance_core_smoke_rest_as_app( 'GET', '/npcink-governance-core/v1/capabilities', $rate_token );
$rate_limited = npcink_governance_core_smoke_rest_result_as_app( 'GET', '/npcink-governance-core/v1/capabilities', $rate_token );
npcink_governance_core_smoke_assert( 429 === (int) $rate_limited['status'], 'app rate limit returns 429 after fixed window is exhausted' );

