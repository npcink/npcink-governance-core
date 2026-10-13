<?php
/** App key and rate cases test module. @package NpcinkGovernanceCore */

$wpdb = npcink_governance_core_fail_closed_reset_db();
$wpdb->fail_insert_tables[] = $app_table;
$apps   = new \Npcink\GovernanceCore\Security\App_Key_Repository();
$result = $apps->create( array( 'app_label' => 'Adapter Client' ) );
npcink_governance_core_fail_closed_assert( is_wp_error( $result ), 'App-key insert failure returns WP_Error.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_app_insert_failed' === $result->get_error_code(), 'App-key insert failure uses stable error code.' );
npcink_governance_core_fail_closed_assert( 0 === count( $wpdb->rows( $app_table ) ), 'App-key insert failure stores no app row.' );

$app_scope_isolation_matrix = array(
	// Static contract marker: App-key scope isolation matrix.
	'approve-only cannot preflight' => array(
		'scopes'        => array( 'proposals:approve' ),
		'method'        => 'can_commit_preflight',
		'required'      => 'commit:preflight',
		'route_family'  => 'commit_preflight',
	),
	'approve-only cannot record execution' => array(
		'scopes'        => array( 'proposals:approve' ),
		'method'        => 'can_record_execution',
		'required'      => 'commit:record_execution',
		'route_family'  => 'commit_record_execution',
	),
	'preflight-only cannot approve' => array(
		'scopes'        => array( 'commit:preflight' ),
		'method'        => 'can_approve_proposals',
		'required'      => 'proposals:approve',
		'route_family'  => 'proposals_approve',
	),
	'preflight-only cannot record execution' => array(
		'scopes'        => array( 'commit:preflight' ),
		'method'        => 'can_record_execution',
		'required'      => 'commit:record_execution',
		'route_family'  => 'commit_record_execution',
	),
	'record-only cannot approve' => array(
		'scopes'        => array( 'commit:record_execution' ),
		'method'        => 'can_approve_proposals',
		'required'      => 'proposals:approve',
		'route_family'  => 'proposals_approve',
	),
	'record-only cannot preflight' => array(
		'scopes'        => array( 'commit:record_execution' ),
		'method'        => 'can_commit_preflight',
		'required'      => 'commit:preflight',
		'route_family'  => 'commit_preflight',
	),
);

foreach ( $app_scope_isolation_matrix as $scope_label => $case ) {
	$wpdb = npcink_governance_core_fail_closed_reset_db();
	global $npcink_governance_core_fail_closed_caps;
	$npcink_governance_core_fail_closed_caps['manage_options'] = false;
	$apps         = new \Npcink\GovernanceCore\Security\App_Key_Repository();
	$audit        = new \Npcink\GovernanceCore\Audit\Audit_Log_Repository();
	$rate_limiter = new \Npcink\GovernanceCore\Security\App_Rate_Limiter();
	$auth         = new \Npcink\GovernanceCore\Security\App_Authenticator( $apps, $rate_limiter, $audit );
	$app          = $apps->create(
		array(
			'app_label'           => 'Scope isolation ' . $scope_label,
			'caller_type'         => 'trusted_adapter',
			'scopes'              => (array) $case['scopes'],
			'rate_limit'          => 50,
			'rate_window_seconds' => 3600,
		)
	);
	npcink_governance_core_fail_closed_assert( ! is_wp_error( $app ), 'App-key scope isolation ' . $scope_label . ' fixture is created.' );
	$request = new WP_REST_Request(
		array(),
		array(
			'authorization' => 'Bearer ' . (string) ( $app['token'] ?? '' ),
		)
	);
	$method  = (string) $case['method'];
	$denied  = $auth->{$method}( $request );
	npcink_governance_core_fail_closed_assert( is_wp_error( $denied ), 'App-key scope isolation ' . $scope_label . ' fails closed.' );
	npcink_governance_core_fail_closed_assert( 'npcink_governance_core_app_scope_forbidden' === $denied->get_error_code(), 'App-key scope isolation ' . $scope_label . ' uses stable forbidden code.' );
	npcink_governance_core_fail_closed_assert( 403 === npcink_governance_core_fail_closed_error_http_status( $denied ), 'App-key scope isolation ' . $scope_label . ' maps to HTTP 403.' );
	$scope_denials = array_values(
		array_filter(
			$wpdb->rows( $audit_table ),
			static function ( array $row ) use ( $case ): bool {
				$metadata = json_decode( (string) ( $row['metadata_json'] ?? '{}' ), true );
				$auth     = is_array( $metadata['auth'] ?? null ) ? $metadata['auth'] : array();
				return 'app.scope_denied' === (string) ( $row['event_name'] ?? '' )
					&& (string) $case['required'] === (string) ( $metadata['required_scope'] ?? '' )
					&& (string) $case['route_family'] === (string) ( $metadata['route_family'] ?? '' )
					&& 'denied' === (string) ( $auth['scope_decision'] ?? '' );
			}
		)
	);
	npcink_governance_core_fail_closed_assert( 1 === count( $scope_denials ), 'App-key scope isolation ' . $scope_label . ' writes denied audit metadata.' );
}

$wpdb = npcink_governance_core_fail_closed_reset_db();
$wpdb->fail_insert_tables[] = $audit_table;
$apps        = new \Npcink\GovernanceCore\Security\App_Key_Repository();
$audit       = new \Npcink\GovernanceCore\Audit\Audit_Log_Repository();
$rate_limiter = new \Npcink\GovernanceCore\Security\App_Rate_Limiter();
$auth        = new \Npcink\GovernanceCore\Security\App_Authenticator( $apps, $rate_limiter, $audit );
$controller  = new \Npcink\GovernanceCore\Rest\Apps_Controller( $apps, $audit, $auth );
$result      = $controller->create_app(
	new WP_REST_Request(
		array(
			'app_label'           => 'Adapter Client',
			'caller_type'         => 'product_adapter',
			'scopes'              => array( 'capabilities:read', 'proposals:create' ),
			'rate_limit'          => 60,
			'rate_window_seconds' => 3600,
		)
	)
);
npcink_governance_core_fail_closed_assert( is_wp_error( $result ), 'App creation audit failure returns WP_Error.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_app_audit_failed' === $result->get_error_code(), 'App creation audit failure uses stable error code.' );
$app_rows = $wpdb->rows( $app_table );
npcink_governance_core_fail_closed_assert( 1 === count( $app_rows ), 'App row is retained for revocation evidence after audit failure.' );
npcink_governance_core_fail_closed_assert( 'revoked' === (string) $app_rows[0]['status'], 'App creation audit failure revokes the new key.' );
npcink_governance_core_fail_closed_assert( ! $result instanceof WP_REST_Response, 'App creation audit failure does not return the one-time token response.' );

$wpdb = npcink_governance_core_fail_closed_reset_db();
$apps        = new \Npcink\GovernanceCore\Security\App_Key_Repository();
$audit       = new \Npcink\GovernanceCore\Audit\Audit_Log_Repository();
$rate_limiter = new \Npcink\GovernanceCore\Security\App_Rate_Limiter();
$auth        = new \Npcink\GovernanceCore\Security\App_Authenticator( $apps, $rate_limiter, $audit );
$controller  = new \Npcink\GovernanceCore\Rest\Apps_Controller( $apps, $audit, $auth );
$old_app     = $apps->create(
	array(
		'app_label'           => 'Rotating Client',
		'caller_type'         => 'product_adapter',
		'scopes'              => array( 'capabilities:read', 'proposals:create' ),
		'rate_limit'          => 60,
		'rate_window_seconds' => 3600,
	)
);
npcink_governance_core_fail_closed_assert( ! is_wp_error( $old_app ), 'App rotation fixture is created.' );
$rotated = $controller->rotate_app( new WP_REST_Request( array( 'key_id' => (string) ( $old_app['key_id'] ?? '' ) ) ) );
npcink_governance_core_fail_closed_assert( $rotated instanceof WP_REST_Response, 'App rotation returns a one-time replacement token response.' );
$rotated_data = $rotated->get_data();
npcink_governance_core_fail_closed_assert( true === (bool) ( $rotated_data['old_key_revoked'] ?? false ), 'App rotation marks the old key revoked.' );
npcink_governance_core_fail_closed_assert( 'revoked' === (string) ( $apps->find_by_key_id( (string) ( $old_app['key_id'] ?? '' ) )['status'] ?? '' ), 'App rotation revokes the old key row.' );
npcink_governance_core_fail_closed_assert( 'active' === (string) ( $apps->find_by_key_id( (string) ( $rotated_data['key_id'] ?? '' ) )['status'] ?? '' ), 'App rotation leaves the replacement key active.' );
npcink_governance_core_fail_closed_assert( 1 === count( npcink_governance_core_fail_closed_audit_rows( '', 'app.rotated' ) ), 'App rotation is audited.' );
npcink_governance_core_fail_closed_assert( 1 === count( npcink_governance_core_fail_closed_audit_rows( '', 'app.revoked' ) ), 'App rotation old-key revocation is audited.' );

$wpdb = npcink_governance_core_fail_closed_reset_db();
$wpdb->fail_insert_tables[] = $audit_table;
$apps        = new \Npcink\GovernanceCore\Security\App_Key_Repository();
$audit       = new \Npcink\GovernanceCore\Audit\Audit_Log_Repository();
$rate_limiter = new \Npcink\GovernanceCore\Security\App_Rate_Limiter();
$auth        = new \Npcink\GovernanceCore\Security\App_Authenticator( $apps, $rate_limiter, $audit );
$controller  = new \Npcink\GovernanceCore\Rest\Apps_Controller( $apps, $audit, $auth );
$old_app     = $apps->create(
	array(
		'app_label'           => 'Rotation Audit Failure',
		'caller_type'         => 'product_adapter',
		'scopes'              => array( 'capabilities:read' ),
		'rate_limit'          => 60,
		'rate_window_seconds' => 3600,
	)
);
npcink_governance_core_fail_closed_assert( ! is_wp_error( $old_app ), 'App rotation audit failure fixture is created.' );
$rotation_failure = $controller->rotate_app( new WP_REST_Request( array( 'key_id' => (string) ( $old_app['key_id'] ?? '' ) ) ) );
npcink_governance_core_fail_closed_assert( is_wp_error( $rotation_failure ), 'App rotation audit failure returns WP_Error.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_app_rotation_audit_failed' === $rotation_failure->get_error_code(), 'App rotation audit failure uses stable error code.' );
$rotation_rows = $wpdb->rows( $app_table );
npcink_governance_core_fail_closed_assert( 2 === count( $rotation_rows ), 'App rotation audit failure preserves old and replacement evidence rows.' );
npcink_governance_core_fail_closed_assert( 'active' === (string) $rotation_rows[0]['status'] && 'revoked' === (string) $rotation_rows[1]['status'], 'App rotation audit failure leaves old key active and revokes replacement.' );

$wpdb = npcink_governance_core_fail_closed_reset_db();
$wpdb->fail_insert_event_names[] = 'app.revoked';
$apps        = new \Npcink\GovernanceCore\Security\App_Key_Repository();
$audit       = new \Npcink\GovernanceCore\Audit\Audit_Log_Repository();
$rate_limiter = new \Npcink\GovernanceCore\Security\App_Rate_Limiter();
$auth        = new \Npcink\GovernanceCore\Security\App_Authenticator( $apps, $rate_limiter, $audit );
$controller  = new \Npcink\GovernanceCore\Rest\Apps_Controller( $apps, $audit, $auth );
$old_app     = $apps->create(
	array(
		'app_label'           => 'Rotation Revoke Audit Failure',
		'caller_type'         => 'product_adapter',
		'scopes'              => array( 'capabilities:read' ),
		'rate_limit'          => 60,
		'rate_window_seconds' => 3600,
	)
);
npcink_governance_core_fail_closed_assert( ! is_wp_error( $old_app ), 'App rotation revoke audit failure fixture is created.' );
$revoke_audit_failure = $controller->rotate_app( new WP_REST_Request( array( 'key_id' => (string) ( $old_app['key_id'] ?? '' ) ) ) );
npcink_governance_core_fail_closed_assert( is_wp_error( $revoke_audit_failure ), 'App rotation revoke audit failure returns WP_Error.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_app_rotation_revoke_audit_failed' === $revoke_audit_failure->get_error_code(), 'App rotation revoke audit failure uses stable error code.' );
$revoke_audit_rows = $wpdb->rows( $app_table );
npcink_governance_core_fail_closed_assert( 2 === count( $revoke_audit_rows ), 'App rotation revoke audit failure preserves old and replacement evidence rows.' );
npcink_governance_core_fail_closed_assert( 'revoked' === (string) $revoke_audit_rows[0]['status'] && 'revoked' === (string) $revoke_audit_rows[1]['status'], 'App rotation revoke audit failure revokes both old and replacement keys.' );
npcink_governance_core_fail_closed_assert( 1 === count( npcink_governance_core_fail_closed_audit_rows( '', 'app.rotated' ) ), 'App rotation revoke audit failure preserves rotation audit evidence.' );
npcink_governance_core_fail_closed_assert( 0 === count( npcink_governance_core_fail_closed_audit_rows( '', 'app.revoked' ) ), 'App rotation revoke audit failure does not pretend old-key revocation was audited.' );

$wpdb        = npcink_governance_core_fail_closed_reset_db();
$rate_limiter = new \Npcink\GovernanceCore\Security\App_Rate_Limiter();
$rate_fixture = array(
	'app_id'             => 'app-rate-race',
	'key_id'             => 'key-rate-race',
	'rate_limit'         => 1,
	'rate_window_seconds' => 3600,
);
$first_rate  = $rate_limiter->consume( $rate_fixture, 'capabilities' );
$second_rate = $rate_limiter->consume( $rate_fixture, 'capabilities' );
npcink_governance_core_fail_closed_assert( true === $first_rate['allowed'] && 1 === (int) $first_rate['request_count'], 'Rate limiter atomically inserts the first fixed-window request.' );
npcink_governance_core_fail_closed_assert( false === $second_rate['allowed'] && 1 === (int) $second_rate['request_count'], 'Rate limiter atomically rejects an exhausted window without duplicate-key failure.' );
npcink_governance_core_fail_closed_assert( 1 === count( $wpdb->rows( $wpdb->prefix . 'npcink_governance_core_app_rate_limits' ) ), 'Rate limiter keeps one row for concurrent first-use semantics.' );

