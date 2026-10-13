<?php
/** Lifecycle and audit cases test module. @package NpcinkGovernanceCore */

$listed = npcink_governance_core_smoke_rest( 'GET', '/npcink-governance-core/v1/proposals', array( 'limit' => 10 ) );
npcink_governance_core_smoke_assert( count( (array) ( $listed['items'] ?? array() ) ) > 0, 'proposal list endpoint returns proposals' );
$listed_first = is_array( $listed['items'][0] ?? null ) ? $listed['items'][0] : array();
npcink_governance_core_smoke_assert( false === array_key_exists( 'input', $listed_first ) && false === array_key_exists( 'preview', $listed_first ) && false === array_key_exists( 'caller', $listed_first ), 'proposal list endpoint omits large payload fields by default' );
$listed_with_payload = npcink_governance_core_smoke_rest( 'GET', '/npcink-governance-core/v1/proposals', array( 'limit' => 1, 'include_payload' => true ) );
$listed_payload_first = is_array( $listed_with_payload['items'][0] ?? null ) ? $listed_with_payload['items'][0] : array();
npcink_governance_core_smoke_assert( true === array_key_exists( 'input', $listed_payload_first ) && true === (bool) ( $listed_with_payload['meta']['payload_included'] ?? false ), 'proposal list endpoint can explicitly include payload fields' );

$stale = npcink_governance_core_smoke_rest(
	'POST',
	'/npcink-governance-core/v1/proposals',
	array(
		'ability_id' => 'npcink-abilities-toolkit/create-draft',
		'title'      => 'Smoke stale proposal ' . $npcink_governance_core_smoke_run_id,
		'summary'    => 'Created to verify automatic expiration.',
	)
);
$stale_id = (string) ( $stale['proposal_id'] ?? '' );
npcink_governance_core_smoke_assert( '' !== $stale_id && 'pending' === (string) ( $stale['status'] ?? '' ), 'stale smoke proposal starts pending' );

$proposal_repository = \Npcink\GovernanceCore\Plugin::instance()->proposal_repository();
$proposal_service    = \Npcink\GovernanceCore\Plugin::instance()->proposal_service();
$stale_time          = gmdate( 'Y-m-d H:i:s', time() - \Npcink\GovernanceCore\Governance\Proposal_Service::PENDING_TTL_SECONDS - HOUR_IN_SECONDS );
global $wpdb;
$wpdb->update(
	$proposal_repository->table_name(),
	array(
		'created_at' => $stale_time,
		'updated_at' => $stale_time,
	),
	array( 'proposal_id' => $stale_id ),
	array( '%s', '%s' ),
	array( '%s' )
);

$expired_detail = npcink_governance_core_smoke_rest( 'GET', '/npcink-governance-core/v1/proposals/' . rawurlencode( $stale_id ) );
npcink_governance_core_smoke_assert( 'expired' === (string) ( $expired_detail['status'] ?? '' ), 'stale pending proposal expires before detail response' );

$archived = $proposal_service->archive( $stale_id, array( 'source' => 'smoke' ) );
npcink_governance_core_smoke_assert( is_array( $archived ) && 'archived' === (string) ( $archived['status'] ?? '' ), 'expired proposal can be archived' );

$reopened = $proposal_service->reopen( $stale_id, array( 'source' => 'smoke' ) );
npcink_governance_core_smoke_assert( is_array( $reopened ) && 'pending' === (string) ( $reopened['status'] ?? '' ), 'archived proposal can be reopened for review' );

$missing_detail = npcink_governance_core_smoke_rest_result( 'GET', '/npcink-governance-core/v1/proposals/missing-smoke-proposal' );
npcink_governance_core_smoke_assert( 404 === (int) $missing_detail['status'], 'proposal detail endpoint returns 404 for missing proposal' );

$legacy_preflight = npcink_governance_core_smoke_rest_result(
	'POST',
	'/npcink-governance-core/v1/proposals/' . rawurlencode( $proposal_id ) . '/commit-preflight',
	array(
		'confirm_token' => 'legacy',
	)
);
npcink_governance_core_smoke_assert( 400 === (int) $legacy_preflight['status'], 'commit preflight rejects legacy confirmation parameters' );

$second = npcink_governance_core_smoke_rest(
	'POST',
	'/npcink-governance-core/v1/proposals',
	array(
		'ability_id' => 'npcink-abilities-toolkit/create-draft',
		'title'      => 'Smoke rejection proposal ' . $npcink_governance_core_smoke_run_id,
		'summary'    => 'Created by real WordPress smoke test.',
	)
);

$reject_id = (string) ( $second['proposal_id'] ?? '' );
npcink_governance_core_smoke_assert( '' !== $reject_id, 'second proposal created for rejection' );

$rejected = npcink_governance_core_smoke_rest(
	'POST',
	'/npcink-governance-core/v1/proposals/' . rawurlencode( $reject_id ) . '/reject',
	array(
		'note' => 'Smoke rejection.',
	)
);

npcink_governance_core_smoke_assert( 'rejected' === (string) ( $rejected['status'] ?? '' ), 'proposal rejected through REST' );

$audit = npcink_governance_core_smoke_rest( 'GET', '/npcink-governance-core/v1/audit', array( 'limit' => 20 ) );
npcink_governance_core_smoke_assert( count( (array) ( $audit['items'] ?? array() ) ) >= 3, 'audit endpoint returns governance events' );

$proposal_audit = npcink_governance_core_smoke_rest(
	'GET',
	'/npcink-governance-core/v1/audit',
	array(
		'proposal_id' => $proposal_id,
		'limit'       => 20,
	)
);
$proposal_audit_items = (array) ( $proposal_audit['items'] ?? array() );
npcink_governance_core_smoke_assert( count( $proposal_audit_items ) >= 3, 'audit endpoint filters by proposal id' );
foreach ( $proposal_audit_items as $item ) {
	npcink_governance_core_smoke_assert( $proposal_id === (string) ( is_array( $item ) ? ( $item['proposal_id'] ?? '' ) : '' ), 'proposal audit filter returns only matching proposal events' );
}

$taxonomy_proposal_audit = npcink_governance_core_smoke_rest(
	'GET',
	'/npcink-governance-core/v1/audit',
	array(
		'proposal_id' => $taxonomy_proposal_id,
		'limit'       => 20,
	)
);
$taxonomy_audit_items = (array) ( $taxonomy_proposal_audit['items'] ?? array() );
npcink_governance_core_smoke_assert( count( $taxonomy_audit_items ) >= 3, 'taxonomy terms audit filter returns the proposal lifecycle events' );
$found_taxonomy_preflight = false;
foreach ( $taxonomy_audit_items as $item ) {
	npcink_governance_core_smoke_assert( $taxonomy_proposal_id === (string) ( is_array( $item ) ? ( $item['proposal_id'] ?? '' ) : '' ), 'taxonomy audit filter returns only matching proposal events' );
	if ( is_array( $item ) && 'commit.preflighted' === (string) ( $item['event_name'] ?? '' ) ) {
		$found_taxonomy_preflight = 'npcink-abilities-toolkit/set-post-terms' === (string) ( $item['metadata']['ability_id'] ?? '' );
	}
}
npcink_governance_core_smoke_assert( $found_taxonomy_preflight, 'taxonomy terms audit correlates commit preflight with set-post-terms' );

$taxonomy_timeline_detail = npcink_governance_core_smoke_rest( 'GET', '/npcink-governance-core/v1/proposals/' . rawurlencode( $taxonomy_proposal_id ) );
$taxonomy_timeline_items  = (array) ( $taxonomy_timeline_detail['audit_timeline'] ?? array() );
npcink_governance_core_smoke_assert( count( $taxonomy_timeline_items ) >= 4, 'taxonomy proposal detail includes audit timeline through preflight' );

$taxonomy_correlation_id = (string) ( $GLOBALS['npcink_governance_core_smoke_preflight_correlations'][ $taxonomy_proposal_id ] ?? '' );
npcink_governance_core_smoke_assert( '' !== $taxonomy_correlation_id, 'taxonomy terms preflight correlation id is available for audit filtering' );

$ability_audit = npcink_governance_core_smoke_rest(
	'GET',
	'/npcink-governance-core/v1/audit',
	array(
		'ability_id' => 'npcink-abilities-toolkit/set-post-terms',
		'limit'      => 20,
	)
);
$ability_audit_items = (array) ( $ability_audit['items'] ?? array() );
npcink_governance_core_smoke_assert( count( $ability_audit_items ) >= 1, 'audit endpoint filters by ability id' );
foreach ( $ability_audit_items as $item ) {
	npcink_governance_core_smoke_assert( 'npcink-abilities-toolkit/set-post-terms' === (string) ( is_array( $item ) ? ( $item['metadata']['ability_id'] ?? '' ) : '' ), 'ability audit filter returns only matching ability metadata' );
}

$correlation_audit = npcink_governance_core_smoke_rest(
	'GET',
	'/npcink-governance-core/v1/audit',
	array(
		'correlation_id' => $taxonomy_correlation_id,
		'limit'          => 20,
	)
);
$correlation_audit_items = (array) ( $correlation_audit['items'] ?? array() );
npcink_governance_core_smoke_assert( count( $correlation_audit_items ) >= 1, 'audit endpoint filters by correlation id' );
foreach ( $correlation_audit_items as $item ) {
	npcink_governance_core_smoke_assert( $taxonomy_correlation_id === (string) ( is_array( $item ) ? ( $item['metadata']['correlation_id'] ?? '' ) : '' ), 'correlation audit filter returns only matching correlation metadata' );
}

$app_proposal_audit = npcink_governance_core_smoke_rest(
	'GET',
	'/npcink-governance-core/v1/audit',
	array(
		'proposal_id' => $app_proposal_id,
		'limit'       => 20,
	)
);
$app_proposal_audit_items = (array) ( $app_proposal_audit['items'] ?? array() );
$found_app_attribution    = false;
foreach ( $app_proposal_audit_items as $item ) {
	if ( is_array( $item ) && 'proposal.created' === (string) ( $item['event_name'] ?? '' ) ) {
		$found_app_attribution = $app_id === (string) ( $item['metadata']['auth']['app_id'] ?? '' );
	}
}
npcink_governance_core_smoke_assert( $found_app_attribution, 'app-authenticated audit event stores app attribution' );

$trusted_proposal_audit = npcink_governance_core_smoke_rest(
	'GET',
	'/npcink-governance-core/v1/audit',
	array(
		'proposal_id' => $trusted_proposal_id,
		'limit'       => 20,
	)
);
$trusted_proposal_audit_items = (array) ( $trusted_proposal_audit['items'] ?? array() );
$found_trusted_approval       = false;
$found_trusted_preflight      = false;
$found_trusted_record         = false;
foreach ( $trusted_proposal_audit_items as $item ) {
	if ( ! is_array( $item ) ) {
		continue;
	}
	if ( 'proposal.approved' === (string) ( $item['event_name'] ?? '' ) ) {
		$found_trusted_approval = $trusted_adapter_app_id === (string) ( $item['metadata']['auth']['app_id'] ?? '' )
			&& 'proposals:approve' === (string) ( $item['metadata']['auth']['scope'] ?? '' )
			&& 'allowed' === (string) ( $item['metadata']['auth']['scope_decision'] ?? '' );
	}
	if ( 'commit.preflighted' === (string) ( $item['event_name'] ?? '' ) ) {
		$found_trusted_preflight = $trusted_adapter_app_id === (string) ( $item['metadata']['auth']['app_id'] ?? '' )
			&& 'commit:preflight' === (string) ( $item['metadata']['auth']['scope'] ?? '' );
	}
	if ( 'proposal.executed' === (string) ( $item['event_name'] ?? '' ) ) {
		$found_trusted_record = $execution_recorder_app_id === (string) ( $item['metadata']['auth']['app_id'] ?? '' )
			&& 'commit:record_execution' === (string) ( $item['metadata']['auth']['scope'] ?? '' );
	}
}
npcink_governance_core_smoke_assert( $found_trusted_approval, 'trusted Adapter approval audit stores app attribution and approve scope' );
npcink_governance_core_smoke_assert( $found_trusted_preflight, 'trusted Adapter preflight audit stores app attribution and preflight scope' );
npcink_governance_core_smoke_assert( $found_trusted_record, 'execution recorder audit stores app attribution and execution-recording scope' );

$app_id_audit = npcink_governance_core_smoke_rest(
	'GET',
	'/npcink-governance-core/v1/audit',
	array(
		'app_id' => $app_id,
		'limit'  => 20,
	)
);
$app_id_audit_items = (array) ( $app_id_audit['items'] ?? array() );
npcink_governance_core_smoke_assert( count( $app_id_audit_items ) >= 1, 'audit endpoint filters by app id' );
foreach ( $app_id_audit_items as $item ) {
	$metadata = is_array( $item ) && is_array( $item['metadata'] ?? null ) ? $item['metadata'] : array();
	$auth     = is_array( $metadata['auth'] ?? null ) ? $metadata['auth'] : $metadata;
	npcink_governance_core_smoke_assert( $app_id === (string) ( $auth['app_id'] ?? '' ), 'app audit filter returns only matching app metadata' );
}

$key_id_audit = npcink_governance_core_smoke_rest(
	'GET',
	'/npcink-governance-core/v1/audit',
	array(
		'key_id' => $key_id,
		'limit'  => 20,
	)
);
$key_id_audit_items = (array) ( $key_id_audit['items'] ?? array() );
npcink_governance_core_smoke_assert( count( $key_id_audit_items ) >= 1, 'audit endpoint filters by key id' );
foreach ( $key_id_audit_items as $item ) {
	$metadata = is_array( $item ) && is_array( $item['metadata'] ?? null ) ? $item['metadata'] : array();
	$auth     = is_array( $metadata['auth'] ?? null ) ? $metadata['auth'] : $metadata;
	npcink_governance_core_smoke_assert( $key_id === (string) ( $auth['key_id'] ?? '' ), 'key audit filter returns only matching key metadata' );
}

$caller_type_audit = npcink_governance_core_smoke_rest(
	'GET',
	'/npcink-governance-core/v1/audit',
	array(
		'caller_type' => 'mcp_adapter',
		'limit'       => 20,
	)
);
$caller_type_audit_items = (array) ( $caller_type_audit['items'] ?? array() );
npcink_governance_core_smoke_assert( count( $caller_type_audit_items ) >= 1, 'audit endpoint filters by caller type' );
foreach ( $caller_type_audit_items as $item ) {
	$metadata = is_array( $item ) && is_array( $item['metadata'] ?? null ) ? $item['metadata'] : array();
	$auth     = is_array( $metadata['auth'] ?? null ) ? $metadata['auth'] : $metadata;
	npcink_governance_core_smoke_assert( 'mcp_adapter' === (string) ( $auth['caller_type'] ?? '' ), 'caller type audit filter returns only matching caller metadata' );
}

$scope_denied_audit = npcink_governance_core_smoke_rest(
	'GET',
	'/npcink-governance-core/v1/audit',
	array(
		'event_name' => 'app.scope_denied',
		'limit'      => 20,
	)
);
$scope_denied_items        = (array) ( $scope_denied_audit['items'] ?? array() );
$found_denied_app_decision = false;
foreach ( $scope_denied_items as $item ) {
	if ( is_array( $item ) && $app_id === (string) ( $item['metadata']['auth']['app_id'] ?? '' ) ) {
		$found_denied_app_decision = 'denied' === (string) ( $item['metadata']['auth']['scope_decision'] ?? '' );
	}
}
npcink_governance_core_smoke_assert( $found_denied_app_decision, 'app scope denial audit stores scope decision denied' );

$rate_limited_audit = npcink_governance_core_smoke_rest(
	'GET',
	'/npcink-governance-core/v1/audit',
	array(
		'event_name' => 'app.rate_limited',
		'limit'      => 20,
	)
);
$rate_limited_items         = (array) ( $rate_limited_audit['items'] ?? array() );
$found_rate_limited_decision = false;
foreach ( $rate_limited_items as $item ) {
	if ( is_array( $item ) && (string) ( $rate_app['app_id'] ?? '' ) === (string) ( $item['metadata']['auth']['app_id'] ?? '' ) ) {
		$found_rate_limited_decision = 'rate_limited' === (string) ( $item['metadata']['auth']['scope_decision'] ?? '' );
	}
}
npcink_governance_core_smoke_assert( $found_rate_limited_decision, 'app rate-limit audit stores scope decision rate_limited' );

$preflight_audit = npcink_governance_core_smoke_rest(
	'GET',
	'/npcink-governance-core/v1/audit',
	array(
		'event_name' => 'commit.preflighted',
		'limit'      => 20,
	)
);
$preflight_audit_items = (array) ( $preflight_audit['items'] ?? array() );
npcink_governance_core_smoke_assert( count( $preflight_audit_items ) >= 1, 'audit endpoint filters by event name' );
foreach ( $preflight_audit_items as $item ) {
	npcink_governance_core_smoke_assert( 'commit.preflighted' === (string) ( is_array( $item ) ? ( $item['event_name'] ?? '' ) : '' ), 'event audit filter returns only matching event names' );
}

