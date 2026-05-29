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
	wp_set_current_user( 1 );

	$request = new WP_REST_Request( $method, $route );
	foreach ( $params as $key => $value ) {
		$request->set_param( $key, $value );
	}

	$response = rest_do_request( $request );
	$status   = (int) $response->get_status();
	$data     = $response->get_data();

	magick_ai_core_smoke_assert( $status >= 200 && $status < 300, $method . ' ' . $route . ' returned HTTP ' . $status );

	return is_array( $data ) ? $data : array();
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

$approved = magick_ai_core_smoke_rest(
	'POST',
	'/magick-ai-core/v1/proposals/' . rawurlencode( $proposal_id ) . '/approve',
	array(
		'note' => 'Smoke approval.',
	)
);

magick_ai_core_smoke_assert( 'approved' === (string) ( $approved['status'] ?? '' ), 'proposal approved through REST' );

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

echo "magick-ai-core WordPress smoke: ok\n";

