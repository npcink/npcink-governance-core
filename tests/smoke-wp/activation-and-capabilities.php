<?php
/** Activation and capabilities test module. @package NpcinkGovernanceCore */

require_once ABSPATH . 'wp-admin/includes/plugin.php';

$core_plugins = array(
	'npcink-governance-core/npcink-governance-core.php',
);
$core_plugin  = '';
foreach ( $core_plugins as $candidate_plugin ) {
	if ( is_plugin_active( $candidate_plugin ) ) {
		$core_plugin = $candidate_plugin;
		break;
	}
}
if ( '' === $core_plugin ) {
	foreach ( $core_plugins as $candidate_plugin ) {
		if ( file_exists( WP_PLUGIN_DIR . '/' . $candidate_plugin ) ) {
			$core_plugin = $candidate_plugin;
			break;
		}
	}
}
$abilities_plugins = array(
	'npcink-abilities-toolkit/npcink-abilities-toolkit.php',
);
$abilities_plugin  = '';
foreach ( $abilities_plugins as $candidate_plugin ) {
	if ( is_plugin_active( $candidate_plugin ) ) {
		$abilities_plugin = $candidate_plugin;
		break;
	}
}
if ( '' === $abilities_plugin ) {
	foreach ( $abilities_plugins as $candidate_plugin ) {
		if ( file_exists( WP_PLUGIN_DIR . '/' . $candidate_plugin ) ) {
			$abilities_plugin = $candidate_plugin;
			break;
		}
	}
}

npcink_governance_core_smoke_assert( '' !== $abilities_plugin, 'npcink-abilities-toolkit plugin file found' );
if ( ! is_plugin_active( $abilities_plugin ) ) {
	$result = activate_plugin( $abilities_plugin );
	npcink_governance_core_smoke_assert( ! is_wp_error( $result ), 'npcink-abilities-toolkit activated' );
}

npcink_governance_core_smoke_assert( '' !== $core_plugin, 'npcink-governance-core plugin file found' );
if ( is_plugin_active( $core_plugin ) ) {
	deactivate_plugins( $core_plugin, true );
}

$activated = activate_plugin( $core_plugin );
npcink_governance_core_smoke_assert( ! is_wp_error( $activated ) && is_plugin_active( $core_plugin ), 'npcink-governance-core activated' );
\Npcink\GovernanceCore\Plugin::instance()->register();

$npcink_governance_core_smoke_initial_policy_mode = get_option( \Npcink\GovernanceCore\Governance\Approval_Policy_Evaluator::OPTION_POLICY_MODE, \Npcink\GovernanceCore\Governance\Approval_Policy_Evaluator::MODE_MANUAL );
$npcink_governance_core_smoke_initial_history_retention = get_option( \Npcink\GovernanceCore\Governance\History_Cleanup_Service::OPTION_HISTORY_RETENTION_DAYS, \Npcink\GovernanceCore\Governance\History_Cleanup_Service::DEFAULT_HISTORY_RETENTION_DAYS );
update_option( \Npcink\GovernanceCore\Governance\Approval_Policy_Evaluator::OPTION_POLICY_MODE, \Npcink\GovernanceCore\Governance\Approval_Policy_Evaluator::MODE_MANUAL, false );

global $wpdb;

$proposal_table     = $wpdb->prefix . 'npcink_governance_core_proposals';
$read_request_table = $wpdb->prefix . 'npcink_governance_core_read_requests';
$audit_table        = $wpdb->prefix . 'npcink_governance_core_audit_log';
$app_table          = $wpdb->prefix . 'npcink_governance_core_app_keys';
$rate_table         = $wpdb->prefix . 'npcink_governance_core_app_rate_limits';

$proposal_exists     = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $proposal_table ) );
$read_request_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $read_request_table ) );
$audit_exists        = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $audit_table ) );
$app_exists          = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $app_table ) );
$rate_exists         = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $rate_table ) );

npcink_governance_core_smoke_assert( $proposal_table === $proposal_exists, 'proposal table exists' );
npcink_governance_core_smoke_assert( $read_request_table === $read_request_exists, 'sensitive read request table exists' );
npcink_governance_core_smoke_assert( $audit_table === $audit_exists, 'audit table exists' );
npcink_governance_core_smoke_assert( $app_table === $app_exists, 'app key table exists' );
npcink_governance_core_smoke_assert( $rate_table === $rate_exists, 'app rate limit table exists' );
npcink_governance_core_smoke_assert( false !== wp_next_scheduled( \Npcink\GovernanceCore\Plugin::HISTORY_CLEANUP_HOOK ), 'history cleanup cron event is scheduled' );

$history_cleanup_proposals = new \Npcink\GovernanceCore\Governance\Proposal_Repository();
$history_cleanup_apps      = new \Npcink\GovernanceCore\Security\App_Key_Repository();
$history_cleanup_audit     = new \Npcink\GovernanceCore\Audit\Audit_Log_Repository();
$history_cleanup_service   = new \Npcink\GovernanceCore\Governance\History_Cleanup_Service( $history_cleanup_proposals, $history_cleanup_apps, $history_cleanup_audit );
$history_cleanup_cutoff_fixture_time = gmdate( 'Y-m-d H:i:s', time() - ( 91 * DAY_IN_SECONDS ) );
update_option( \Npcink\GovernanceCore\Governance\History_Cleanup_Service::OPTION_HISTORY_RETENTION_DAYS, 90, false );

$history_cleanup_proposal = $history_cleanup_proposals->create(
	array(
		'ability_id' => 'npcink-abilities-toolkit/create-draft',
		'title'      => 'Historical cleanup smoke proposal ' . $npcink_governance_core_smoke_run_id,
		'summary'    => 'Created to verify bounded historical cleanup.',
		'input'      => array( 'title' => 'Cleanup smoke' ),
		'preview'    => array( 'smoke' => true ),
		'caller'     => array( 'source' => 'smoke' ),
	)
);
npcink_governance_core_smoke_assert( is_array( $history_cleanup_proposal ), 'history cleanup smoke proposal fixture is created' );
$history_cleanup_proposal_id = (string) ( $history_cleanup_proposal['proposal_id'] ?? '' );
npcink_governance_core_smoke_register_proposal_fixture( $history_cleanup_proposal_id );
$wpdb->update(
	$proposal_table,
	array(
		'status'     => \Npcink\GovernanceCore\Governance\Proposal_Repository::STATUS_EXPIRED,
		'created_at' => $history_cleanup_cutoff_fixture_time,
		'updated_at' => $history_cleanup_cutoff_fixture_time,
	),
	array( 'proposal_id' => $history_cleanup_proposal_id ),
	array( '%s', '%s', '%s' ),
	array( '%s' )
);

$history_cleanup_app = $history_cleanup_apps->create(
	array(
		'app_label'           => 'History cleanup revoked smoke ' . $npcink_governance_core_smoke_run_id,
		'caller_type'         => 'mcp_adapter',
		'scopes'              => array( 'capabilities:read' ),
		'rate_limit'          => 20,
		'rate_window_seconds' => 3600,
	)
);
npcink_governance_core_smoke_assert( is_array( $history_cleanup_app ), 'history cleanup revoked token fixture is created' );
$history_cleanup_app_id = (string) ( $history_cleanup_app['app_id'] ?? '' );
$history_cleanup_key_id = (string) ( $history_cleanup_app['key_id'] ?? '' );
npcink_governance_core_smoke_register_app_fixture( $history_cleanup_app_id );
npcink_governance_core_smoke_register_app_key_fixture( $history_cleanup_key_id );
npcink_governance_core_smoke_assert( $history_cleanup_apps->revoke_by_key_id( $history_cleanup_key_id ), 'history cleanup token fixture is revoked' );
$wpdb->update(
	$app_table,
	array(
		'revoked_at' => $history_cleanup_cutoff_fixture_time,
		'updated_at' => $history_cleanup_cutoff_fixture_time,
	),
	array( 'key_id' => $history_cleanup_key_id ),
	array( '%s', '%s' ),
	array( '%s' )
);

$history_cleanup_access_event_id = $history_cleanup_audit->record(
	'audit.listed',
	array(
		'source' => 'smoke_old_access_event',
		'count'  => 1,
	)
);
npcink_governance_core_smoke_assert( '' !== $history_cleanup_access_event_id, 'history cleanup old access audit fixture is created' );
$wpdb->update(
	$audit_table,
	array( 'created_at' => $history_cleanup_cutoff_fixture_time ),
	array( 'event_id' => $history_cleanup_access_event_id ),
	array( '%s' ),
	array( '%s' )
);

$history_cleanup_planned_audit_events = $history_cleanup_audit->count_access_events_before( gmdate( 'Y-m-d H:i:s', time() - ( 90 * DAY_IN_SECONDS ) ) );
$history_cleanup_result = $history_cleanup_service->run( 'smoke' );
npcink_governance_core_smoke_assert( ! is_wp_error( $history_cleanup_result ), 'history cleanup service runs without error' );
npcink_governance_core_smoke_assert( (int) ( $history_cleanup_result['deleted_proposals'] ?? 0 ) >= 1, 'history cleanup deletes old historical proposals' );
npcink_governance_core_smoke_assert( (int) ( $history_cleanup_result['deleted_app_keys'] ?? 0 ) >= 1, 'history cleanup deletes old revoked access tokens' );
npcink_governance_core_smoke_assert( (int) ( $history_cleanup_result['deleted_audit_events'] ?? 0 ) >= 1, 'history cleanup deletes old access audit events' );
npcink_governance_core_smoke_assert( null === $history_cleanup_proposals->find( $history_cleanup_proposal_id ), 'history cleanup removes the expired proposal fixture' );
npcink_governance_core_smoke_assert( null === $history_cleanup_apps->find_by_key_id( $history_cleanup_key_id ), 'history cleanup removes the revoked token fixture' );
$history_cleanup_access_event_exists = (int) $wpdb->get_var(
	$wpdb->prepare(
		'SELECT COUNT(*) FROM %i WHERE event_id = %s',
		$audit_table,
		$history_cleanup_access_event_id
	)
);
npcink_governance_core_smoke_assert(
	0 === $history_cleanup_access_event_exists
		|| (
			$history_cleanup_planned_audit_events > \Npcink\GovernanceCore\Governance\History_Cleanup_Service::CLEANUP_BATCH_LIMIT
			&& \Npcink\GovernanceCore\Governance\History_Cleanup_Service::CLEANUP_BATCH_LIMIT === (int) ( $history_cleanup_result['deleted_audit_events'] ?? 0 )
		),
	'history cleanup removes the fixture or fills the bounded batch when older eligible events remain'
);
$history_cleanup_completed_count = (int) $wpdb->get_var(
	$wpdb->prepare(
		'SELECT COUNT(*) FROM %i WHERE event_name = %s AND metadata_json LIKE %s',
		$audit_table,
		'core.history_cleanup_completed',
		'%' . $wpdb->esc_like( '"source":"smoke"' ) . '%'
	)
);
npcink_governance_core_smoke_assert( $history_cleanup_completed_count > 0, 'history cleanup writes completion audit evidence' );
update_option( \Npcink\GovernanceCore\Governance\History_Cleanup_Service::OPTION_HISTORY_RETENTION_DAYS, \Npcink\GovernanceCore\Governance\History_Cleanup_Service::sanitize_retention_days( $npcink_governance_core_smoke_initial_history_retention ), false );

$capabilities = npcink_governance_core_smoke_rest( 'GET', '/npcink-governance-core/v1/capabilities' );
$items        = is_array( $capabilities['items'] ?? null ) ? $capabilities['items'] : array();

npcink_governance_core_smoke_assert( true === (bool) ( $capabilities['available'] ?? false ), 'capability source is available' );
npcink_governance_core_smoke_assert( 'wordpress_abilities_api' === (string) ( $capabilities['source'] ?? '' ), 'capabilities use the WordPress Abilities API aggregate source' );
npcink_governance_core_smoke_assert( count( $items ) > 0, 'capabilities endpoint returns abilities' );
npcink_governance_core_smoke_assert( count( $items ) === (int) ( $capabilities['ready_count'] ?? -1 ) + (int) ( $capabilities['blocked_count'] ?? -1 ), 'capability ready and blocked counts cover every discovered row' );

$runtime_contract = npcink_governance_core_smoke_rest( 'GET', '/npcink-governance-core/v1/contract' );
npcink_governance_core_smoke_assert( 'npcink_governance_core_contract.v1' === (string) ( $runtime_contract['schema_version'] ?? '' ), 'runtime contract exposes the expected schema version' );
npcink_governance_core_smoke_assert( defined( 'NPCINK_GOVERNANCE_CORE_VERSION' ) && NPCINK_GOVERNANCE_CORE_VERSION === (string) ( $runtime_contract['plugin_version'] ?? '' ), 'runtime contract exposes the active plugin version' );
npcink_governance_core_smoke_assert( false === (bool) ( $runtime_contract['runtime_controls']['core_proxy_execute'] ?? true ), 'runtime contract keeps Core proxy execution disabled' );
npcink_governance_core_smoke_assert( false === (bool) ( $runtime_contract['runtime_controls']['commit_execution'] ?? true ), 'runtime contract keeps commit execution disabled' );
npcink_governance_core_smoke_assert( false === (bool) ( $runtime_contract['runtime_controls']['workflow_orchestration'] ?? true ), 'runtime contract keeps workflow orchestration outside Core' );
npcink_governance_core_smoke_assert( false === (bool) ( $runtime_contract['runtime_controls']['background_jobs'] ?? true ), 'runtime contract keeps background jobs outside Core' );
npcink_governance_core_smoke_assert( false === (bool) ( $runtime_contract['runtime_controls']['provider_secret_storage'] ?? true ), 'runtime contract keeps provider secret storage outside Core' );
npcink_governance_core_smoke_assert( 'operation-classification-v1' === (string) ( $runtime_contract['operation_classification']['policy_version'] ?? '' ), 'runtime contract exposes operation classification policy version' );
npcink_governance_core_smoke_assert( in_array( 'core_proposal_required', (array) ( $runtime_contract['operation_classification']['classification_values'] ?? array() ), true ), 'runtime contract exposes Core proposal required classification value' );
npcink_governance_core_smoke_assert( 'core_proposal_required' === (string) ( $runtime_contract['operation_classification']['proposal_intake_classification'] ?? '' ), 'runtime contract declares Core proposal intake classification' );
npcink_governance_core_smoke_assert( 'core_proposal' === (string) ( $runtime_contract['operation_classification']['proposal_intake_path'] ?? '' ), 'runtime contract declares Core proposal intake path' );
npcink_governance_core_smoke_assert( false === (bool) ( $runtime_contract['operation_classification']['generic_editor_author_review_proposal_required'] ?? true ), 'runtime contract keeps generic editor author review outside Core proposal intake' );
npcink_governance_core_smoke_assert( array( 'native_editor_commit' ) === (array) ( $runtime_contract['operation_classification']['pre_classification_exclusions'] ?? array() ), 'runtime contract exposes native editor commit only as a pre-classification exclusion' );
npcink_governance_core_smoke_assert( false === (bool) ( $runtime_contract['operation_classification']['native_editor_commit_is_core_classification'] ?? true ), 'runtime contract does not add native editor commit as a fifth Core classification' );
npcink_governance_core_smoke_assert( false === (bool) ( $runtime_contract['operation_classification']['native_editor_commit_core_record_required'] ?? true ), 'runtime contract requires no Core record for native editor commit' );
npcink_governance_core_smoke_assert( 'adapter_or_host_after_core_preflight' === (string) ( $runtime_contract['operation_classification']['execution_owner_for_core_proposals'] ?? '' ), 'runtime contract names Adapter or host execution owner for Core proposals' );
npcink_governance_core_smoke_assert( 'implementation_posture' === (string) ( $runtime_contract['implementation_posture']['provider_metadata_field'] ?? '' ), 'runtime contract names implementation posture provider metadata field' );
npcink_governance_core_smoke_assert( '/wp-json/npcink-governance-core/v1/capabilities' === (string) ( $runtime_contract['implementation_posture']['capabilities_surface'] ?? '' ), 'runtime contract points implementation posture consumers to capabilities' );
npcink_governance_core_smoke_assert( true === (bool) ( $runtime_contract['implementation_posture']['proposal_review_visibility'] ?? false ), 'runtime contract marks implementation posture visible in proposal review' );
npcink_governance_core_smoke_assert( true === (bool) ( $runtime_contract['implementation_posture']['commit_preflight_contract_validation'] ?? false ), 'runtime contract marks implementation posture validated by commit preflight' );
npcink_governance_core_smoke_assert( true === (bool) ( $runtime_contract['implementation_posture']['metadata_only'] ?? false ), 'runtime contract keeps implementation posture metadata-only' );
npcink_governance_core_smoke_assert( false === (bool) ( $runtime_contract['implementation_posture']['core_records_truth'] ?? true ), 'runtime contract does not make Core the implementation posture truth owner' );
npcink_governance_core_smoke_assert( in_array( 'workflow_runtime', (array) ( $runtime_contract['implementation_posture']['forbidden_core_ownership_flags'] ?? array() ), true ), 'runtime contract names forbidden workflow runtime ownership flag' );
npcink_governance_core_smoke_assert( '1' === (string) ( $runtime_contract['runtime_contract_endpoint_version'] ?? '' ), 'runtime contract exposes endpoint version' );
npcink_governance_core_smoke_assert( true === (bool) ( $runtime_contract['compatibility']['metadata_only'] ?? false ), 'runtime contract is metadata-only' );
npcink_governance_core_smoke_assert( true === (bool) ( $runtime_contract['compatibility']['commit_preflight_available'] ?? false ), 'runtime contract exposes commit preflight availability' );
npcink_governance_core_smoke_assert( true === (bool) ( $runtime_contract['compatibility']['sensitive_read_preflight_available'] ?? false ), 'runtime contract exposes sensitive read preflight availability' );
npcink_governance_core_smoke_assert( array( 'site_url', 'home_url', 'blog_id' ) === array_values( (array) ( $runtime_contract['context_bindings']['site_binding']['fields'] ?? array() ) ), 'runtime contract declares site binding fields' );
npcink_governance_core_smoke_assert( untrailingslashit( site_url() ) === (string) ( $runtime_contract['context_bindings']['site_binding']['site_url'] ?? '' ), 'runtime contract binds current site_url' );
npcink_governance_core_smoke_assert( untrailingslashit( home_url() ) === (string) ( $runtime_contract['context_bindings']['site_binding']['home_url'] ?? '' ), 'runtime contract binds current home_url' );
npcink_governance_core_smoke_assert( get_current_blog_id() === (int) ( $runtime_contract['context_bindings']['site_binding']['blog_id'] ?? 0 ), 'runtime contract binds current blog id' );
npcink_governance_core_smoke_assert( true === (bool) ( $runtime_contract['context_bindings']['client_key_fingerprint']['emitted'] ?? false ), 'runtime contract marks Adapter-forwarded client fingerprint binding supported' );
npcink_governance_core_smoke_assert( 'signed_client_fingerprint' === (string) ( $runtime_contract['context_bindings']['client_key_fingerprint']['field'] ?? '' ), 'runtime contract names signed client fingerprint field' );
npcink_governance_core_smoke_assert( 'supported_when_forwarded_by_trusted_adapter' === (string) ( $runtime_contract['context_bindings']['client_key_fingerprint']['status'] ?? '' ), 'runtime contract names supported client identity contract' );
npcink_governance_core_smoke_assert( 'adapter_or_host_after_core_preflight' === (string) ( $runtime_contract['boundary']['final_write_authority'] ?? '' ), 'runtime contract leaves final writes with adapter or host after preflight' );

$app = npcink_governance_core_smoke_rest(
	'POST',
	'/npcink-governance-core/v1/apps',
	array(
		'app_label'           => 'OpenClaw smoke adapter ' . $npcink_governance_core_smoke_run_id,
		'caller_type'         => 'mcp_adapter',
		'rate_limit'          => 20,
		'rate_window_seconds' => 3600,
	)
);
$app_token = (string) ( $app['token'] ?? '' );
$app_id    = (string) ( $app['app_id'] ?? '' );
$key_id    = (string) ( $app['key_id'] ?? '' );
npcink_governance_core_smoke_assert( '' !== $app_token && false === strpos( $app_token, ' ' ), 'app key creation returns one-time bearer token' );
npcink_governance_core_smoke_assert( '' !== $app_id && '' !== $key_id, 'app key creation returns app and key ids' );
npcink_governance_core_smoke_assert( in_array( 'proposals:create', (array) ( $app['scopes'] ?? array() ), true ), 'app key defaults include proposal creation scope' );
npcink_governance_core_smoke_assert( in_array( 'read_requests:create', (array) ( $app['scopes'] ?? array() ), true ), 'app key defaults include sensitive read request creation scope' );
npcink_governance_core_smoke_assert( in_array( 'read_requests:preflight', (array) ( $app['scopes'] ?? array() ), true ), 'app key defaults include sensitive read preflight scope' );
npcink_governance_core_smoke_assert( ! in_array( 'commit:record_execution', (array) ( $app['scopes'] ?? array() ), true ), 'app key defaults do not include execution-result recording scope' );
npcink_governance_core_smoke_assert( ! array_key_exists( 'secret_hash', $app ), 'app key creation response does not expose secret hash' );

wp_set_current_user( 1 );
$empty_scope_request = new WP_REST_Request( 'POST', '/npcink-governance-core/v1/apps' );
$empty_scope_request->set_body_params(
	array(
		'app_label'           => 'Empty scopes smoke ' . $npcink_governance_core_smoke_run_id,
		'caller_type'         => 'mcp_adapter',
		'scopes'              => array(),
		'rate_limit'          => 20,
		'rate_window_seconds' => 3600,
	)
);
$empty_scope_response = rest_do_request( $empty_scope_request );
$empty_scope_data     = $empty_scope_response->get_data();
npcink_governance_core_smoke_assert( 400 === (int) $empty_scope_response->get_status(), 'app key creation rejects explicitly empty scopes' );
npcink_governance_core_smoke_assert( 'npcink_governance_core_app_scopes_empty' === (string) ( is_array( $empty_scope_data ) ? ( $empty_scope_data['code'] ?? '' ) : '' ), 'empty scopes return stable app scope error code' );

$invalid_scope_app = npcink_governance_core_smoke_rest_result(
	'POST',
	'/npcink-governance-core/v1/apps',
	array(
		'app_label'           => 'Invalid scopes smoke ' . $npcink_governance_core_smoke_run_id,
		'caller_type'         => 'mcp_adapter',
		'scopes'              => array( 'not:a-real-scope' ),
		'rate_limit'          => 20,
		'rate_window_seconds' => 3600,
	)
);
npcink_governance_core_smoke_assert( 400 === (int) $invalid_scope_app['status'], 'app key creation rejects explicitly invalid scopes' );
npcink_governance_core_smoke_assert( 'npcink_governance_core_app_scopes_empty' === (string) ( $invalid_scope_app['data']['code'] ?? '' ), 'invalid scopes return stable app scope error code' );

$apps_list = npcink_governance_core_smoke_rest( 'GET', '/npcink-governance-core/v1/apps', array( 'limit' => 5 ) );
$apps_json = wp_json_encode( $apps_list );
npcink_governance_core_smoke_assert( is_string( $apps_json ) && false === strpos( $apps_json, (string) ( $app['secret'] ?? 'unreachable-secret' ) ), 'app list does not expose raw app secret' );
npcink_governance_core_smoke_assert( is_string( $apps_json ) && false === strpos( $apps_json, 'secret_hash' ), 'app list does not expose secret hash' );

$app_capabilities = npcink_governance_core_smoke_rest_as_app( 'GET', '/npcink-governance-core/v1/capabilities', $app_token );
npcink_governance_core_smoke_assert( count( (array) ( $app_capabilities['items'] ?? array() ) ) > 0, 'app-authenticated capabilities read succeeds' );
$app_manage_denied = npcink_governance_core_smoke_rest_result_as_app( 'GET', '/npcink-governance-core/v1/apps', $app_token, array( 'limit' => 1 ) );
npcink_governance_core_smoke_assert( in_array( (int) $app_manage_denied['status'], array( 401, 403 ), true ), 'app-authenticated token cannot list admin-only app keys' );
$app_rotate_denied = npcink_governance_core_smoke_rest_result_as_app( 'POST', '/npcink-governance-core/v1/apps/' . rawurlencode( $key_id ) . '/rotate', $app_token );
npcink_governance_core_smoke_assert( in_array( (int) $app_rotate_denied['status'], array( 401, 403 ), true ), 'app-authenticated token cannot rotate admin-only app keys' );

$revoked_app = npcink_governance_core_smoke_rest(
	'POST',
	'/npcink-governance-core/v1/apps',
	array(
		'app_label'           => 'OpenClaw revoked smoke ' . $npcink_governance_core_smoke_run_id,
		'caller_type'         => 'mcp_adapter',
		'scopes'              => array( 'capabilities:read' ),
		'rate_limit'          => 20,
		'rate_window_seconds' => 3600,
	)
);
$revoked_token = (string) ( $revoked_app['token'] ?? '' );
$revoked_key   = (string) ( $revoked_app['key_id'] ?? '' );
$app_keys      = new \Npcink\GovernanceCore\Security\App_Key_Repository();
npcink_governance_core_smoke_assert( $app_keys->revoke_by_key_id( $revoked_key ), 'app key repository revokes active key' );
$revoked_result = npcink_governance_core_smoke_rest_result_as_app( 'GET', '/npcink-governance-core/v1/capabilities', $revoked_token );
npcink_governance_core_smoke_assert( 401 === (int) $revoked_result['status'], 'revoked app key returns 401' );

$expired_app = npcink_governance_core_smoke_rest(
	'POST',
	'/npcink-governance-core/v1/apps',
	array(
		'app_label'           => 'Expired smoke ' . $npcink_governance_core_smoke_run_id,
		'caller_type'         => 'mcp_adapter',
		'scopes'              => array( 'capabilities:read' ),
		'rate_limit'          => 20,
		'rate_window_seconds' => 3600,
		'expires_at'          => gmdate( 'Y-m-d H:i:s', time() + 3600 ),
	)
);
$expired_token = (string) ( $expired_app['token'] ?? '' );
$expired_key   = (string) ( $expired_app['key_id'] ?? '' );
npcink_governance_core_smoke_register_app_key_fixture( $expired_key );
global $wpdb;
$wpdb->update(
	$app_keys->table_name(),
	array(
		'expires_at' => gmdate( 'Y-m-d H:i:s', time() - 60 ),
	),
	array( 'key_id' => $expired_key ),
	array( '%s' ),
	array( '%s' )
);
$expired_result = npcink_governance_core_smoke_rest_result_as_app( 'GET', '/npcink-governance-core/v1/capabilities', $expired_token );
npcink_governance_core_smoke_assert( 401 === (int) $expired_result['status'], 'expired app key returns 401' );

$items_by_id                     = array();
$all_have_governance_mode        = true;
$all_have_execution_surface      = true;
$all_have_read_policy            = true;
$all_have_sensitivity            = true;
$all_have_redaction_requirement  = true;
$all_have_intake_contract        = true;
$all_blocked_disable_execution   = true;
$all_disable_core_proxy_execute  = true;
$all_disable_core_commit_execute = true;
foreach ( $items as $item ) {
	if ( is_array( $item ) && '' !== (string) ( $item['ability_id'] ?? '' ) ) {
		$all_have_governance_mode        = $all_have_governance_mode && array_key_exists( 'governance_mode', $item );
		$all_have_execution_surface      = $all_have_execution_surface && array_key_exists( 'execution_surface', $item );
		$all_have_read_policy            = $all_have_read_policy && array_key_exists( 'read_policy', $item );
		$all_have_sensitivity            = $all_have_sensitivity && array_key_exists( 'sensitivity', $item );
		$all_have_redaction_requirement  = $all_have_redaction_requirement && array_key_exists( 'redaction_required', $item );
		$all_have_intake_contract        = $all_have_intake_contract
			&& 'core-ability-intake-v1' === (string) ( $item['intake_contract_version'] ?? '' )
			&& in_array( (string) ( $item['intake_status'] ?? '' ), array( 'ready', 'blocked' ), true )
			&& is_array( $item['intake_reasons'] ?? null );
		if ( 'blocked' === (string) ( $item['intake_status'] ?? '' ) ) {
			$all_blocked_disable_execution = $all_blocked_disable_execution
				&& 'blocked' === (string) ( $item['governance_mode'] ?? '' )
				&& 'none' === (string) ( $item['execution_surface'] ?? '' );
		}
		$all_disable_core_proxy_execute  = $all_disable_core_proxy_execute && false === (bool) ( $item['core_proxy_execute'] ?? true );
		$all_disable_core_commit_execute = $all_disable_core_commit_execute && false === (bool) ( $item['commit_execution'] ?? true );
		$items_by_id[ (string) $item['ability_id'] ] = $item;
	}
}
npcink_governance_core_smoke_assert( $all_have_governance_mode, 'all capability rows expose governance mode guidance' );
npcink_governance_core_smoke_assert( $all_have_execution_surface, 'all capability rows expose execution surface guidance' );
npcink_governance_core_smoke_assert( $all_have_read_policy, 'all capability rows expose read policy guidance' );
npcink_governance_core_smoke_assert( $all_have_sensitivity, 'all capability rows expose read sensitivity guidance' );
npcink_governance_core_smoke_assert( $all_have_redaction_requirement, 'all capability rows expose read redaction requirement' );
npcink_governance_core_smoke_assert( $all_have_intake_contract, 'all capability rows expose versioned fail-closed intake readiness' );
npcink_governance_core_smoke_assert( $all_blocked_disable_execution, 'all blocked capability rows disable execution guidance' );
npcink_governance_core_smoke_assert( $all_disable_core_proxy_execute, 'all capability rows keep Core proxy execution disabled' );
npcink_governance_core_smoke_assert( $all_disable_core_commit_execute, 'all capability rows keep Core commit execution disabled' );

if ( isset( $items_by_id['mcp-adapter/execute-ability'] ) ) {
	$mcp_execute = $items_by_id['mcp-adapter/execute-ability'];
	npcink_governance_core_smoke_assert( 'destructive' === (string) ( $mcp_execute['risk_level'] ?? '' ), 'WordPress destructive annotation is not downgraded to read' );
	npcink_governance_core_smoke_assert( 'blocked' === (string) ( $mcp_execute['intake_status'] ?? '' ), 'REST-hidden MCP execute ability remains diagnosable but blocked' );
	npcink_governance_core_smoke_assert( in_array( 'rest_exposure_disabled', (array) ( $mcp_execute['intake_reasons'] ?? array() ), true ), 'REST-hidden MCP execute ability exposes stable blocked reason' );
}

foreach (
	array(
		'npcink-abilities-toolkit/create-draft',
		'npcink-abilities-toolkit/update-post',
		'npcink-abilities-toolkit/update-post-blocks',
		'npcink-abilities-toolkit/set-post-terms',
		'npcink-abilities-toolkit/update-media-details',
	) as $posture_ability_id
) {
	npcink_governance_core_smoke_assert( isset( $items_by_id[ $posture_ability_id ] ), $posture_ability_id . ' is discoverable for implementation posture validation' );
	npcink_governance_core_smoke_assert_implementation_posture( $items_by_id[ $posture_ability_id ], $posture_ability_id );
}

npcink_governance_core_smoke_assert( isset( $items_by_id['npcink-abilities-toolkit/site-info'] ), 'site-info read ability is discoverable for direct read guidance' );
npcink_governance_core_smoke_assert_capability_guidance( $items_by_id['npcink-abilities-toolkit/site-info'], 'core_read_authorization_required', 'wp_abilities_rest', 'site-info uses fail-closed read authorization guidance' );
npcink_governance_core_smoke_assert( 'sensitive' === (string) ( $items_by_id['npcink-abilities-toolkit/site-info']['sensitivity'] ?? '' ), 'site-info defaults undeclared sensitivity to sensitive' );
npcink_governance_core_smoke_assert( true === (bool) ( $items_by_id['npcink-abilities-toolkit/site-info']['redaction_required'] ?? false ), 'site-info requires redaction when sensitivity is undeclared' );
npcink_governance_core_smoke_assert_read_authorization_required( $items_by_id['npcink-abilities-toolkit/site-info'], 'site-info undeclared sensitivity' );
