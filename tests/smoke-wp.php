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
 * Dispatches a REST request with app bearer token.
 *
 * @param string              $method HTTP method.
 * @param string              $route REST route.
 * @param string              $token App token.
 * @param array<string,mixed> $params Request params.
 * @return array<string,mixed>
 */
function magick_ai_core_smoke_rest_as_app( string $method, string $route, string $token, array $params = array() ): array {
	$result = magick_ai_core_smoke_rest_result_as_app( $method, $route, $token, $params );

	magick_ai_core_smoke_assert( $result['status'] >= 200 && $result['status'] < 300, $method . ' ' . $route . ' returned HTTP ' . $result['status'] . ' for app token' );

	return is_array( $result['data'] ) ? $result['data'] : array();
}

/**
 * Dispatches a REST request with app bearer token and returns status/data.
 *
 * @param string              $method HTTP method.
 * @param string              $route REST route.
 * @param string              $token App token.
 * @param array<string,mixed> $params Request params.
 * @return array{status:int,data:mixed}
 */
function magick_ai_core_smoke_rest_result_as_app( string $method, string $route, string $token, array $params = array() ): array {
	wp_set_current_user( 0 );

	$request = new WP_REST_Request( $method, $route );
	$request->set_header( 'authorization', 'Bearer ' . $token );
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

/**
 * Runs the proposal, approval, preflight, and audit smoke loop for one write-like ability.
 *
 * @param string              $ability_id Ability id.
 * @param array<string,mixed> $items_by_id Capability rows keyed by ability id.
 * @param string              $title Proposal title.
 * @param array<string,mixed> $input Proposal input.
 * @param array<string,mixed> $preview Proposal preview.
 * @return string
 */
function magick_ai_core_smoke_run_governance_proposal( string $ability_id, array $items_by_id, string $title, array $input, array $preview ): string {
	magick_ai_core_smoke_assert( isset( $items_by_id[ $ability_id ] ), $ability_id . ' is discoverable for proposal governance' );
	magick_ai_core_smoke_assert( 'read' !== (string) ( $items_by_id[ $ability_id ]['risk_level'] ?? 'read' ), $ability_id . ' is write-like for proposal governance' );
	magick_ai_core_smoke_assert( true === (bool) ( $items_by_id[ $ability_id ]['requires_approval'] ?? false ), $ability_id . ' requires approval for proposal governance' );

	$created = magick_ai_core_smoke_rest(
		'POST',
		'/magick-ai-core/v1/proposals',
		array(
			'ability_id' => $ability_id,
			'title'      => $title,
			'summary'    => 'Created by real WordPress smoke test.',
			'input'      => $input,
			'preview'    => $preview,
			'caller'     => array(
				'source' => 'tests/smoke-wp.php',
			),
		)
	);

	$proposal_id = (string) ( $created['proposal_id'] ?? '' );
	magick_ai_core_smoke_assert( '' !== $proposal_id && 'pending' === (string) ( $created['status'] ?? '' ), $ability_id . ' proposal created in pending status' );
	magick_ai_core_smoke_assert( $ability_id === (string) ( $created['ability_id'] ?? '' ), $ability_id . ' proposal stores the real ability id' );

	$detail = magick_ai_core_smoke_rest( 'GET', '/magick-ai-core/v1/proposals/' . rawurlencode( $proposal_id ) );
	magick_ai_core_smoke_assert( $proposal_id === (string) ( $detail['proposal_id'] ?? '' ), $ability_id . ' proposal detail endpoint returns created proposal' );

	$pending_preflight = magick_ai_core_smoke_rest_result( 'POST', '/magick-ai-core/v1/proposals/' . rawurlencode( $proposal_id ) . '/commit-preflight' );
	magick_ai_core_smoke_assert( 409 === (int) $pending_preflight['status'], $ability_id . ' commit preflight fails for pending proposal' );

	$approved = magick_ai_core_smoke_rest(
		'POST',
		'/magick-ai-core/v1/proposals/' . rawurlencode( $proposal_id ) . '/approve',
		array(
			'note' => 'Smoke approval.',
		)
	);

	magick_ai_core_smoke_assert( 'approved' === (string) ( $approved['status'] ?? '' ), $ability_id . ' proposal approved through REST' );

	$preflight = magick_ai_core_smoke_rest( 'POST', '/magick-ai-core/v1/proposals/' . rawurlencode( $proposal_id ) . '/commit-preflight' );
	magick_ai_core_smoke_assert( false === (bool) ( $preflight['commit_execution'] ?? true ), $ability_id . ' commit preflight does not execute ability' );
	magick_ai_core_smoke_assert( true === (bool) ( $preflight['approval_context']['approval_commit_authorized'] ?? false ), $ability_id . ' commit preflight returns approval authorization context' );
	magick_ai_core_smoke_assert( 'approved_commit' === (string) ( $preflight['approval_context']['confirmation_state'] ?? '' ), $ability_id . ' commit preflight returns approved_commit state' );
	magick_ai_core_smoke_assert( $ability_id === (string) ( $preflight['proposal']['ability_id'] ?? '' ), $ability_id . ' preflight proposal keeps the real ability id' );
	magick_ai_core_smoke_assert( $ability_id === (string) ( $preflight['capability']['ability_id'] ?? '' ), $ability_id . ' preflight capability is rediscovered from ability intake' );

	return $proposal_id;
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
$app_table      = $wpdb->prefix . 'magick_ai_core_app_keys';
$rate_table     = $wpdb->prefix . 'magick_ai_core_app_rate_limits';

$proposal_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $proposal_table ) );
$audit_exists    = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $audit_table ) );
$app_exists      = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $app_table ) );
$rate_exists     = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $rate_table ) );

magick_ai_core_smoke_assert( $proposal_table === $proposal_exists, 'proposal table exists' );
magick_ai_core_smoke_assert( $audit_table === $audit_exists, 'audit table exists' );
magick_ai_core_smoke_assert( $app_table === $app_exists, 'app key table exists' );
magick_ai_core_smoke_assert( $rate_table === $rate_exists, 'app rate limit table exists' );

$capabilities = magick_ai_core_smoke_rest( 'GET', '/magick-ai-core/v1/capabilities' );
$items        = is_array( $capabilities['items'] ?? null ) ? $capabilities['items'] : array();

magick_ai_core_smoke_assert( true === (bool) ( $capabilities['available'] ?? false ), 'capability source is available' );
magick_ai_core_smoke_assert( 'magick_ai_abilities' === (string) ( $capabilities['source'] ?? '' ), 'capabilities are discovered from magick-ai-abilities' );
magick_ai_core_smoke_assert( count( $items ) > 0, 'capabilities endpoint returns abilities' );

$app = magick_ai_core_smoke_rest(
	'POST',
	'/magick-ai-core/v1/apps',
	array(
		'app_label'           => 'OpenClaw smoke adapter',
		'caller_type'         => 'mcp_adapter',
		'rate_limit'          => 20,
		'rate_window_seconds' => 3600,
	)
);
$app_token = (string) ( $app['token'] ?? '' );
$app_id    = (string) ( $app['app_id'] ?? '' );
$key_id    = (string) ( $app['key_id'] ?? '' );
magick_ai_core_smoke_assert( '' !== $app_token && false === strpos( $app_token, ' ' ), 'app key creation returns one-time bearer token' );
magick_ai_core_smoke_assert( '' !== $app_id && '' !== $key_id, 'app key creation returns app and key ids' );
magick_ai_core_smoke_assert( in_array( 'proposals:create', (array) ( $app['scopes'] ?? array() ), true ), 'app key defaults include proposal creation scope' );
magick_ai_core_smoke_assert( ! array_key_exists( 'secret_hash', $app ), 'app key creation response does not expose secret hash' );

$apps_list = magick_ai_core_smoke_rest( 'GET', '/magick-ai-core/v1/apps', array( 'limit' => 5 ) );
$apps_json = wp_json_encode( $apps_list );
magick_ai_core_smoke_assert( is_string( $apps_json ) && false === strpos( $apps_json, (string) ( $app['secret'] ?? 'unreachable-secret' ) ), 'app list does not expose raw app secret' );
magick_ai_core_smoke_assert( is_string( $apps_json ) && false === strpos( $apps_json, 'secret_hash' ), 'app list does not expose secret hash' );

$app_capabilities = magick_ai_core_smoke_rest_as_app( 'GET', '/magick-ai-core/v1/capabilities', $app_token );
magick_ai_core_smoke_assert( count( (array) ( $app_capabilities['items'] ?? array() ) ) > 0, 'app-authenticated capabilities read succeeds' );

$revoked_app = magick_ai_core_smoke_rest(
	'POST',
	'/magick-ai-core/v1/apps',
	array(
		'app_label'           => 'OpenClaw revoked smoke',
		'caller_type'         => 'mcp_adapter',
		'scopes'              => array( 'capabilities:read' ),
		'rate_limit'          => 20,
		'rate_window_seconds' => 3600,
	)
);
$revoked_token = (string) ( $revoked_app['token'] ?? '' );
$revoked_key   = (string) ( $revoked_app['key_id'] ?? '' );
$app_keys      = new \MagickAI\Core\Security\App_Key_Repository();
magick_ai_core_smoke_assert( $app_keys->revoke_by_key_id( $revoked_key ), 'app key repository revokes active key' );
$revoked_result = magick_ai_core_smoke_rest_result_as_app( 'GET', '/magick-ai-core/v1/capabilities', $revoked_token );
magick_ai_core_smoke_assert( 401 === (int) $revoked_result['status'], 'revoked app key returns 401' );

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

$planning_label = magick_ai_core_smoke_rest_result(
	'POST',
	'/magick-ai-core/v1/proposals',
	array(
		'ability_id' => 'content/draft-preview',
		'title'      => 'Planning label should not be accepted',
	)
);
magick_ai_core_smoke_assert( 404 === (int) $planning_label['status'], 'proposal creation rejects planning labels that are not real ability ids' );

$proposal_id = magick_ai_core_smoke_run_governance_proposal(
	'magick-ai/create-draft',
	$items_by_id,
	'Smoke draft proposal',
	array(
		'title'   => 'Core Governance Smoke Draft',
		'content' => '<p>Smoke draft content.</p>',
		'status'  => 'draft',
		'dry_run' => true,
	),
	array(
		'dry_run'       => true,
		'host_governed' => true,
	)
);

$seo_proposal_id = magick_ai_core_smoke_run_governance_proposal(
	'magick-ai/set-post-seo-meta',
	$items_by_id,
	'Smoke SEO metadata proposal',
	array(
		'post_id'     => 1,
		'title'       => 'Smoke SEO Title',
		'description' => 'Smoke SEO description.',
		'dry_run'     => true,
	),
	array(
		'dry_run'       => true,
		'host_governed' => true,
	)
);

magick_ai_core_smoke_assert( '' !== $seo_proposal_id, 'SEO metadata proposal completed governance loop' );

$comment_proposal_id = magick_ai_core_smoke_run_governance_proposal(
	'magick-ai/approve-comment',
	$items_by_id,
	'Smoke comment moderation proposal',
	array(
		'comment_id' => 1,
		'dry_run'    => true,
	),
	array(
		'dry_run'       => true,
		'host_governed' => true,
	)
);

magick_ai_core_smoke_assert( '' !== $comment_proposal_id, 'comment moderation proposal completed governance loop' );

$app_created = magick_ai_core_smoke_rest_as_app(
	'POST',
	'/magick-ai-core/v1/proposals',
	$app_token,
	array(
		'ability_id' => 'magick-ai/create-draft',
		'title'      => 'App authenticated proposal',
		'summary'    => 'Created by app key smoke test.',
		'input'      => array(
			'title'   => 'App Auth Draft',
			'content' => '<p>App auth content.</p>',
			'dry_run' => true,
		),
		'preview'    => array(
			'dry_run' => true,
		),
		'caller'     => array(
			'source' => 'app-auth-smoke',
		),
	)
);
$app_proposal_id = (string) ( $app_created['proposal_id'] ?? '' );
magick_ai_core_smoke_assert( '' !== $app_proposal_id, 'app-authenticated proposal is created' );
magick_ai_core_smoke_assert( $app_id === (string) ( $app_created['caller']['auth']['app_id'] ?? '' ), 'app-authenticated proposal stores app attribution' );
magick_ai_core_smoke_assert( 'proposals:create' === (string) ( $app_created['caller']['auth']['scope'] ?? '' ), 'app-authenticated proposal stores scope attribution' );

$app_approve = magick_ai_core_smoke_rest_result_as_app( 'POST', '/magick-ai-core/v1/proposals/' . rawurlencode( $app_proposal_id ) . '/approve', $app_token );
magick_ai_core_smoke_assert( 403 === (int) $app_approve['status'], 'app-authenticated approval is denied without approval scope' );

magick_ai_core_smoke_rest(
	'POST',
	'/magick-ai-core/v1/proposals/' . rawurlencode( $app_proposal_id ) . '/approve',
	array(
		'note' => 'Admin approval for app-authenticated proposal.',
	)
);

$app_preflight = magick_ai_core_smoke_rest_as_app( 'POST', '/magick-ai-core/v1/proposals/' . rawurlencode( $app_proposal_id ) . '/commit-preflight', $app_token );
magick_ai_core_smoke_assert( false === (bool) ( $app_preflight['commit_execution'] ?? true ), 'app-authenticated commit preflight does not execute ability' );
magick_ai_core_smoke_assert( true === (bool) ( $app_preflight['approval_context']['approval_commit_authorized'] ?? false ), 'app-authenticated commit preflight returns approval context' );

$app_audit_denied = magick_ai_core_smoke_rest_result_as_app( 'GET', '/magick-ai-core/v1/audit', $app_token, array( 'limit' => 5 ) );
magick_ai_core_smoke_assert( 403 === (int) $app_audit_denied['status'], 'app-authenticated audit read is denied without audit scope' );

$rate_app = magick_ai_core_smoke_rest(
	'POST',
	'/magick-ai-core/v1/apps',
	array(
		'app_label'           => 'OpenClaw rate smoke',
		'caller_type'         => 'mcp_adapter',
		'scopes'              => array( 'capabilities:read' ),
		'rate_limit'          => 1,
		'rate_window_seconds' => 3600,
	)
);
$rate_token = (string) ( $rate_app['token'] ?? '' );
magick_ai_core_smoke_rest_as_app( 'GET', '/magick-ai-core/v1/capabilities', $rate_token );
$rate_limited = magick_ai_core_smoke_rest_result_as_app( 'GET', '/magick-ai-core/v1/capabilities', $rate_token );
magick_ai_core_smoke_assert( 429 === (int) $rate_limited['status'], 'app rate limit returns 429 after fixed window is exhausted' );

$listed = magick_ai_core_smoke_rest( 'GET', '/magick-ai-core/v1/proposals', array( 'limit' => 10 ) );
magick_ai_core_smoke_assert( count( (array) ( $listed['items'] ?? array() ) ) > 0, 'proposal list endpoint returns proposals' );

$missing_detail = magick_ai_core_smoke_rest_result( 'GET', '/magick-ai-core/v1/proposals/missing-smoke-proposal' );
magick_ai_core_smoke_assert( 404 === (int) $missing_detail['status'], 'proposal detail endpoint returns 404 for missing proposal' );

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
		'ability_id' => 'magick-ai/create-draft',
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

$app_proposal_audit = magick_ai_core_smoke_rest(
	'GET',
	'/magick-ai-core/v1/audit',
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
magick_ai_core_smoke_assert( $found_app_attribution, 'app-authenticated audit event stores app attribution' );

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
