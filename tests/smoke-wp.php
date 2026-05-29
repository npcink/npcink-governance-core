<?php
/**
 * Real WordPress smoke test for Magick AI Core.
 *
 * Run through WP-CLI after the plugin is symlinked into the LocalWP site.
 *
 * @package MagickAICore
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "WordPress is not loaded.\n" );
	exit( 1 );
}

/**
 * Smoke assertion helper.
 *
 * @param bool   $condition Condition.
 * @param string $message Failure message.
 * @return void
 */
function magick_ai_core_smoke_assert( bool $condition, string $message ): void {
	if ( ! $condition ) {
		fwrite( STDERR, '[fail] ' . $message . "\n" );
		exit( 1 );
	}

	echo '[ok] ' . $message . "\n";
}

/**
 * Dispatches a REST request as admin.
 *
 * @param string              $method HTTP method.
 * @param string              $route REST route.
 * @param array<string,mixed> $params Request params.
 * @return array<string,mixed>
 */
function magick_ai_core_smoke_rest( string $method, string $route, array $params = array() ): array {
	$result = magick_ai_core_smoke_rest_result( $method, $route, $params );

	magick_ai_core_smoke_assert( $result['status'] >= 200 && $result['status'] < 300, $method . ' ' . $route . ' returned HTTP ' . $result['status'] );

	return is_array( $result['data'] ) ? $result['data'] : array();
}

/**
 * Dispatches a REST request as admin and returns status/data.
 *
 * @param string              $method HTTP method.
 * @param string              $route REST route.
 * @param array<string,mixed> $params Request params.
 * @return array{status:int,data:mixed}
 */
function magick_ai_core_smoke_rest_result( string $method, string $route, array $params = array() ): array {
	wp_set_current_user( 1 );

	$request = new WP_REST_Request( $method, $route );
	foreach ( $params as $key => $value ) {
		$request->set_param( $key, $value );
	}

	$response = rest_do_request( $request );
	$status   = (int) $response->get_status();
	$data     = $response->get_data();

	return array(
		'status' => $status,
		'data'   => $data,
	);
}

/**
 * Locates the shared magick-ai-abilities replay fixture.
 *
 * @return string
 */
function magick_ai_core_smoke_replay_fixture_path(): string {
	$env_path = getenv( 'MAGICK_AI_ABILITIES_PATH' );
	$roots    = array();

	if ( is_string( $env_path ) && '' !== trim( $env_path ) ) {
		$roots[] = rtrim( $env_path, '/' );
	}

	$roots[] = dirname( __DIR__, 2 ) . '/magick-ai-abilities';
	$roots[] = '/Users/muze/gitee/magick-ai-abilities';

	foreach ( $roots as $root ) {
		$fixture = $root . '/tests/fixtures/agent-workflow-replay.json';
		if ( is_readable( $fixture ) ) {
			return $fixture;
		}
	}

	return '';
}

/**
 * Returns shared workflow replay cases from the installed provider or fixture.
 *
 * @return array<string,array<string,mixed>>
 */
function magick_ai_core_smoke_workflow_cases(): array {
	if ( function_exists( 'magick_ai_abilities_get_workflow_definitions' ) ) {
		$manifest = magick_ai_abilities_get_workflow_definitions();
		if ( is_array( $manifest ) && is_array( $manifest['cases'] ?? null ) ) {
			return $manifest['cases'];
		}
	}

	$replay_fixture_path = magick_ai_core_smoke_replay_fixture_path();
	magick_ai_core_smoke_assert( '' !== $replay_fixture_path, 'shared magick-ai-abilities replay fixture is available' );
	$replay_fixture = json_decode( (string) file_get_contents( $replay_fixture_path ), true );
	magick_ai_core_smoke_assert( is_array( $replay_fixture ) && is_array( $replay_fixture['cases'] ?? null ), 'shared replay fixture decodes with cases' );

	return $replay_fixture['cases'];
}

require_once ABSPATH . 'wp-admin/includes/plugin.php';

$core_plugin      = 'magick-ai-core/magick-ai-core.php';
$abilities_plugin = 'magick-ai-abilities/magick-ai-abilities.php';

if ( ! is_plugin_active( $abilities_plugin ) ) {
	$result = activate_plugin( $abilities_plugin );
	magick_ai_core_smoke_assert( ! is_wp_error( $result ), 'magick-ai-abilities activated' );
}

if ( is_plugin_active( $core_plugin ) ) {
	deactivate_plugins( $core_plugin, true );
}

$activated = activate_plugin( $core_plugin );
magick_ai_core_smoke_assert( ! is_wp_error( $activated ) && is_plugin_active( $core_plugin ), 'magick-ai-core activated' );

global $wpdb;

$proposal_table = $wpdb->prefix . 'magick_ai_core_proposals';
$audit_table    = $wpdb->prefix . 'magick_ai_core_audit_log';

$proposal_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $proposal_table ) );
$audit_exists    = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $audit_table ) );

magick_ai_core_smoke_assert( $proposal_table === $proposal_exists, 'proposal table exists' );
magick_ai_core_smoke_assert( $audit_table === $audit_exists, 'audit table exists' );

$capabilities = magick_ai_core_smoke_rest( 'GET', '/magick-ai-core/v1/capabilities' );
$items        = is_array( $capabilities['items'] ?? null ) ? $capabilities['items'] : array();

magick_ai_core_smoke_assert( true === (bool) ( $capabilities['available'] ?? false ), 'capability source is available' );
magick_ai_core_smoke_assert( 'magick_ai_abilities' === (string) ( $capabilities['source'] ?? '' ), 'capabilities are discovered from magick-ai-abilities' );
magick_ai_core_smoke_assert( count( $items ) > 0, 'capabilities endpoint returns abilities' );

$items_by_id = array();
foreach ( $items as $item ) {
	if ( is_array( $item ) && '' !== (string) ( $item['ability_id'] ?? '' ) ) {
		$items_by_id[ (string) $item['ability_id'] ] = $item;
	}
}

$workflow_cases = magick_ai_core_smoke_workflow_cases();
magick_ai_core_smoke_assert( count( $workflow_cases ) > 0, 'shared workflow definitions are available to Core' );

foreach ( $workflow_cases as $case_id => $case ) {
	$preferred_id = (string) ( is_array( $case ) ? ( $case['preferred_ability_id'] ?? '' ) : '' );
	magick_ai_core_smoke_assert( isset( $items_by_id[ $preferred_id ] ), 'replay case ' . $case_id . ' preferred bundle is discoverable by Core' );
	magick_ai_core_smoke_assert( 'read' === (string) ( $items_by_id[ $preferred_id ]['risk_level'] ?? '' ), 'replay case ' . $case_id . ' preferred bundle remains read risk' );
	magick_ai_core_smoke_assert( false === (bool) ( $items_by_id[ $preferred_id ]['requires_approval'] ?? true ), 'replay case ' . $case_id . ' preferred bundle does not require approval' );

	foreach ( (array) ( $case['disallowed_default_ability_ids'] ?? array() ) as $disallowed_id ) {
		$disallowed_id = (string) $disallowed_id;
		magick_ai_core_smoke_assert( isset( $items_by_id[ $disallowed_id ] ), 'replay case ' . $case_id . ' disallowed default ability is discoverable for proposal handoff' );
		magick_ai_core_smoke_assert( 'read' !== (string) ( $items_by_id[ $disallowed_id ]['risk_level'] ?? 'read' ), 'replay case ' . $case_id . ' disallowed default ability is write-like' );
		magick_ai_core_smoke_assert( true === (bool) ( $items_by_id[ $disallowed_id ]['requires_approval'] ?? false ), 'replay case ' . $case_id . ' disallowed default ability requires approval in Core' );
	}
}

$ability_id = '';
foreach ( $items as $item ) {
	if ( is_array( $item ) && 'magick-ai/site-info' === (string) ( $item['ability_id'] ?? '' ) ) {
		$ability_id = 'magick-ai/site-info';
		break;
	}
}

if ( '' === $ability_id && is_array( $items[0] ?? null ) ) {
	$ability_id = (string) ( $items[0]['ability_id'] ?? '' );
}

magick_ai_core_smoke_assert( '' !== $ability_id, 'selected ability for proposal smoke' );

$created = magick_ai_core_smoke_rest(
	'POST',
	'/magick-ai-core/v1/proposals',
	array(
		'ability_id' => $ability_id,
		'title'      => 'Smoke proposal',
		'summary'    => 'Created by real WordPress smoke test.',
		'input'      => array(
			'smoke' => true,
		),
		'preview'    => array(
			'dry_run' => true,
		),
		'caller'     => array(
			'source' => 'tests/smoke-wp.php',
		),
	)
);

$proposal_id = (string) ( $created['proposal_id'] ?? '' );
magick_ai_core_smoke_assert( '' !== $proposal_id && 'pending' === (string) ( $created['status'] ?? '' ), 'proposal created in pending status' );

$listed = magick_ai_core_smoke_rest( 'GET', '/magick-ai-core/v1/proposals', array( 'limit' => 10 ) );
magick_ai_core_smoke_assert( count( (array) ( $listed['items'] ?? array() ) ) > 0, 'proposal list endpoint returns proposals' );

$detail = magick_ai_core_smoke_rest( 'GET', '/magick-ai-core/v1/proposals/' . rawurlencode( $proposal_id ) );
magick_ai_core_smoke_assert( $proposal_id === (string) ( $detail['proposal_id'] ?? '' ), 'proposal detail endpoint returns created proposal' );

$missing_detail = magick_ai_core_smoke_rest_result( 'GET', '/magick-ai-core/v1/proposals/missing-smoke-proposal' );
magick_ai_core_smoke_assert( 404 === (int) $missing_detail['status'], 'proposal detail endpoint returns 404 for missing proposal' );

$pending_preflight = magick_ai_core_smoke_rest_result( 'POST', '/magick-ai-core/v1/proposals/' . rawurlencode( $proposal_id ) . '/commit-preflight' );
magick_ai_core_smoke_assert( 409 === (int) $pending_preflight['status'], 'commit preflight fails for pending proposal' );

$approved = magick_ai_core_smoke_rest(
	'POST',
	'/magick-ai-core/v1/proposals/' . rawurlencode( $proposal_id ) . '/approve',
	array(
		'note' => 'Smoke approval.',
	)
);

magick_ai_core_smoke_assert( 'approved' === (string) ( $approved['status'] ?? '' ), 'proposal approved through REST' );

$preflight = magick_ai_core_smoke_rest( 'POST', '/magick-ai-core/v1/proposals/' . rawurlencode( $proposal_id ) . '/commit-preflight' );
magick_ai_core_smoke_assert( false === (bool) ( $preflight['commit_execution'] ?? true ), 'commit preflight does not execute ability' );
magick_ai_core_smoke_assert( true === (bool) ( $preflight['approval_context']['approval_commit_authorized'] ?? false ), 'commit preflight returns approval authorization context' );
magick_ai_core_smoke_assert( 'approved_commit' === (string) ( $preflight['approval_context']['confirmation_state'] ?? '' ), 'commit preflight returns approved_commit state' );

$legacy_preflight = magick_ai_core_smoke_rest_result(
	'POST',
	'/magick-ai-core/v1/proposals/' . rawurlencode( $proposal_id ) . '/commit-preflight',
	array(
		'confirm_token' => 'legacy',
	)
);
magick_ai_core_smoke_assert( 400 === (int) $legacy_preflight['status'], 'commit preflight rejects legacy confirmation parameters' );

$second = magick_ai_core_smoke_rest(
	'POST',
	'/magick-ai-core/v1/proposals',
	array(
		'ability_id' => $ability_id,
		'title'      => 'Smoke rejection proposal',
		'summary'    => 'Created by real WordPress smoke test.',
	)
);

$reject_id = (string) ( $second['proposal_id'] ?? '' );
magick_ai_core_smoke_assert( '' !== $reject_id, 'second proposal created for rejection' );

$rejected = magick_ai_core_smoke_rest(
	'POST',
	'/magick-ai-core/v1/proposals/' . rawurlencode( $reject_id ) . '/reject',
	array(
		'note' => 'Smoke rejection.',
	)
);

magick_ai_core_smoke_assert( 'rejected' === (string) ( $rejected['status'] ?? '' ), 'proposal rejected through REST' );

$audit = magick_ai_core_smoke_rest( 'GET', '/magick-ai-core/v1/audit', array( 'limit' => 20 ) );
magick_ai_core_smoke_assert( count( (array) ( $audit['items'] ?? array() ) ) >= 3, 'audit endpoint returns governance events' );

$proposal_audit = magick_ai_core_smoke_rest(
	'GET',
	'/magick-ai-core/v1/audit',
	array(
		'proposal_id' => $proposal_id,
		'limit'       => 20,
	)
);
$proposal_audit_items = (array) ( $proposal_audit['items'] ?? array() );
magick_ai_core_smoke_assert( count( $proposal_audit_items ) >= 3, 'audit endpoint filters by proposal id' );
foreach ( $proposal_audit_items as $item ) {
	magick_ai_core_smoke_assert( $proposal_id === (string) ( is_array( $item ) ? ( $item['proposal_id'] ?? '' ) : '' ), 'proposal audit filter returns only matching proposal events' );
}

$preflight_audit = magick_ai_core_smoke_rest(
	'GET',
	'/magick-ai-core/v1/audit',
	array(
		'event_name' => 'commit.preflighted',
		'limit'      => 20,
	)
);
$preflight_audit_items = (array) ( $preflight_audit['items'] ?? array() );
magick_ai_core_smoke_assert( count( $preflight_audit_items ) >= 1, 'audit endpoint filters by event name' );
foreach ( $preflight_audit_items as $item ) {
	magick_ai_core_smoke_assert( 'commit.preflighted' === (string) ( is_array( $item ) ? ( $item['event_name'] ?? '' ) : '' ), 'event audit filter returns only matching event names' );
}

echo "magick-ai-core WordPress smoke: ok\n";
