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

$magick_ai_core_smoke_run_id                 = wp_generate_uuid4();
$magick_ai_core_smoke_post_fixture_ids       = array();
$magick_ai_core_smoke_comment_fixture_ids    = array();
$magick_ai_core_smoke_attachment_fixture_ids = array();
$magick_ai_core_smoke_term_fixtures          = array();
$magick_ai_core_smoke_app_key_fixture_ids    = array();
$magick_ai_core_smoke_app_fixture_ids        = array();
$magick_ai_core_smoke_proposal_fixture_ids   = array();
$magick_ai_core_smoke_cleanup_completed      = false;
$magick_ai_core_smoke_initial_policy_mode    = get_option( \MagickAI\Core\Governance\Approval_Policy_Evaluator::OPTION_POLICY_MODE, \MagickAI\Core\Governance\Approval_Policy_Evaluator::MODE_MANUAL );

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
 * Registers a post fixture for cleanup even when the smoke test fails.
 *
 * @param int $post_id Post id.
 * @return void
 */
function magick_ai_core_smoke_register_post_fixture( int $post_id ): void {
	global $magick_ai_core_smoke_post_fixture_ids;

	if ( $post_id <= 0 ) {
		return;
	}

	$magick_ai_core_smoke_post_fixture_ids[ $post_id ] = true;
}

/**
 * Registers a comment fixture for cleanup even when the smoke test fails.
 *
 * @param int $comment_id Comment id.
 * @return void
 */
function magick_ai_core_smoke_register_comment_fixture( int $comment_id ): void {
	global $magick_ai_core_smoke_comment_fixture_ids;

	if ( $comment_id <= 0 ) {
		return;
	}

	$magick_ai_core_smoke_comment_fixture_ids[ $comment_id ] = true;
}

/**
 * Registers a media fixture for cleanup even when the smoke test fails.
 *
 * @param int $attachment_id Attachment post id.
 * @return void
 */
function magick_ai_core_smoke_register_attachment_fixture( int $attachment_id ): void {
	global $magick_ai_core_smoke_attachment_fixture_ids;

	if ( $attachment_id <= 0 ) {
		return;
	}

	$magick_ai_core_smoke_attachment_fixture_ids[ $attachment_id ] = true;
}

/**
 * Creates a real local media attachment fixture with an attached uploads file.
 *
 * @param string $title Attachment title.
 * @return int
 */
function magick_ai_core_smoke_create_media_attachment_fixture( string $title ): int {
	global $magick_ai_core_smoke_run_id;

	$uploads = wp_upload_dir();
	magick_ai_core_smoke_assert( empty( $uploads['error'] ), 'smoke media fixture uploads directory is available' );

	$directory = trailingslashit( (string) $uploads['path'] );
	if ( ! is_dir( $directory ) ) {
		wp_mkdir_p( $directory );
	}
	magick_ai_core_smoke_assert( is_dir( $directory ) && is_writable( $directory ), 'smoke media fixture uploads directory is writable' );

	$file_name = sanitize_file_name( 'core-smoke-media-' . $magick_ai_core_smoke_run_id . '.png' );
	$file_path = $directory . $file_name;
	$image_bytes = base64_decode( 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII=', true );
	magick_ai_core_smoke_assert( is_string( $image_bytes ) && '' !== $image_bytes, 'smoke media fixture image bytes decode' );
	magick_ai_core_smoke_assert( false !== file_put_contents( $file_path, $image_bytes ), 'smoke media fixture file is written' );

	$attachment_id = wp_insert_post(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'post_title'     => $title,
			'post_mime_type' => 'image/png',
			'post_excerpt'   => '',
			'post_content'   => '',
			'guid'           => trailingslashit( (string) $uploads['url'] ) . $file_name,
		),
		true
	);
	magick_ai_core_smoke_assert( ! is_wp_error( $attachment_id ) && (int) $attachment_id > 0, 'smoke media fixture attachment post is created' );
	update_attached_file( (int) $attachment_id, $file_path );
	update_post_meta(
		(int) $attachment_id,
		'_wp_attachment_metadata',
		array(
			'width'  => 1,
			'height' => 1,
			'file'   => ltrim( trailingslashit( (string) $uploads['subdir'] ) . $file_name, '/' ),
		)
	);
	magick_ai_core_smoke_register_attachment_fixture( (int) $attachment_id );

	return (int) $attachment_id;
}

/**
 * Registers a taxonomy term fixture for cleanup even when the smoke test fails.
 *
 * @param int    $term_id Term id.
 * @param string $taxonomy Taxonomy id.
 * @return void
 */
function magick_ai_core_smoke_register_term_fixture( int $term_id, string $taxonomy ): void {
	global $magick_ai_core_smoke_term_fixtures;

	if ( $term_id <= 0 || '' === $taxonomy ) {
		return;
	}

	$magick_ai_core_smoke_term_fixtures[ $taxonomy . ':' . $term_id ] = array(
		'term_id'  => $term_id,
		'taxonomy' => $taxonomy,
	);
}

/**
 * Registers an app key fixture for revocation and optional purge.
 *
 * @param string $key_id App key id.
 * @return void
 */
function magick_ai_core_smoke_register_app_key_fixture( string $key_id ): void {
	global $magick_ai_core_smoke_app_key_fixture_ids;

	$key_id = trim( $key_id );
	if ( '' === $key_id ) {
		return;
	}

	$magick_ai_core_smoke_app_key_fixture_ids[ $key_id ] = true;
}

/**
 * Registers an app fixture for optional purge.
 *
 * @param string $app_id App id.
 * @return void
 */
function magick_ai_core_smoke_register_app_fixture( string $app_id ): void {
	global $magick_ai_core_smoke_app_fixture_ids;

	$app_id = trim( $app_id );
	if ( '' === $app_id ) {
		return;
	}

	$magick_ai_core_smoke_app_fixture_ids[ $app_id ] = true;
}

/**
 * Registers a proposal fixture for optional purge.
 *
 * @param string $proposal_id Proposal id.
 * @return void
 */
function magick_ai_core_smoke_register_proposal_fixture( string $proposal_id ): void {
	global $magick_ai_core_smoke_proposal_fixture_ids;

	$proposal_id = trim( $proposal_id );
	if ( '' === $proposal_id ) {
		return;
	}

	$magick_ai_core_smoke_proposal_fixture_ids[ $proposal_id ] = true;
}

/**
 * Tracks app and proposal fixtures created by REST calls.
 *
 * @param string $method HTTP method.
 * @param string $route REST route.
 * @param mixed  $data Response data.
 * @return void
 */
function magick_ai_core_smoke_track_rest_fixture( string $method, string $route, $data ): void {
	if ( 'POST' !== strtoupper( $method ) || ! is_array( $data ) ) {
		return;
	}

	if ( '/magick-ai-core/v1/apps' === $route ) {
		magick_ai_core_smoke_register_app_fixture( (string) ( $data['app_id'] ?? '' ) );
		magick_ai_core_smoke_register_app_key_fixture( (string) ( $data['key_id'] ?? '' ) );
		return;
	}

	if ( '/magick-ai-core/v1/proposals' === $route ) {
		magick_ai_core_smoke_register_proposal_fixture( (string) ( $data['proposal_id'] ?? '' ) );
		return;
	}

	if ( '/magick-ai-core/v1/proposals/from-plan' === $route ) {
		foreach ( (array) ( $data['proposals'] ?? array() ) as $proposal ) {
			if ( is_array( $proposal ) ) {
				magick_ai_core_smoke_register_proposal_fixture( (string) ( $proposal['proposal_id'] ?? '' ) );
			}
		}
	}
}

/**
 * Whether smoke should purge tracked governance rows instead of only revoking keys.
 *
 * @return bool
 */
function magick_ai_core_smoke_should_purge_governance_records(): bool {
	$value = getenv( 'MAGICK_AI_CORE_SMOKE_PURGE' );
	if ( ! is_string( $value ) ) {
		return false;
	}

	return in_array( strtolower( trim( $value ) ), array( '1', 'true', 'yes' ), true );
}

/**
 * Deletes tracked governance rows when explicitly requested for local cleanup.
 *
 * @return void
 */
function magick_ai_core_smoke_purge_governance_records(): void {
	global $wpdb, $magick_ai_core_smoke_app_fixture_ids, $magick_ai_core_smoke_app_key_fixture_ids, $magick_ai_core_smoke_proposal_fixture_ids;

	if ( ! magick_ai_core_smoke_should_purge_governance_records() ) {
		return;
	}

	$audit_table     = $wpdb->prefix . 'magick_ai_core_audit_log';
	$app_table       = $wpdb->prefix . 'magick_ai_core_app_keys';
	$rate_table      = $wpdb->prefix . 'magick_ai_core_app_rate_limits';
	$proposal_table  = $wpdb->prefix . 'magick_ai_core_proposals';
	$proposal_ids    = array_keys( $magick_ai_core_smoke_proposal_fixture_ids );
	$app_ids         = array_keys( $magick_ai_core_smoke_app_fixture_ids );
	$key_ids         = array_keys( $magick_ai_core_smoke_app_key_fixture_ids );

	foreach ( $proposal_ids as $proposal_id ) {
		$wpdb->delete( $audit_table, array( 'proposal_id' => sanitize_text_field( $proposal_id ) ), array( '%s' ) );
		$wpdb->delete( $proposal_table, array( 'proposal_id' => sanitize_text_field( $proposal_id ) ), array( '%s' ) );
	}

	foreach ( $app_ids as $app_id ) {
		$wpdb->delete( $rate_table, array( 'app_id' => sanitize_text_field( $app_id ) ), array( '%s' ) );
		$wpdb->query(
			$wpdb->prepare(
				'DELETE FROM ' . $audit_table . ' WHERE metadata_json LIKE %s',
				'%' . $wpdb->esc_like( '"app_id":"' . $app_id . '"' ) . '%'
			)
		);
	}

	foreach ( $key_ids as $key_id ) {
		$wpdb->delete( $rate_table, array( 'key_id' => sanitize_text_field( $key_id ) ), array( '%s' ) );
		$wpdb->delete( $app_table, array( 'key_id' => sanitize_text_field( $key_id ) ), array( '%s' ) );
		$wpdb->query(
			$wpdb->prepare(
				'DELETE FROM ' . $audit_table . ' WHERE metadata_json LIKE %s',
				'%' . $wpdb->esc_like( '"key_id":"' . $key_id . '"' ) . '%'
			)
		);
	}
}

/**
 * Deletes registered smoke fixtures.
 *
 * @return void
 */
function magick_ai_core_smoke_cleanup_fixtures(): void {
	global $magick_ai_core_smoke_post_fixture_ids, $magick_ai_core_smoke_comment_fixture_ids, $magick_ai_core_smoke_attachment_fixture_ids, $magick_ai_core_smoke_term_fixtures, $magick_ai_core_smoke_app_key_fixture_ids, $magick_ai_core_smoke_cleanup_completed, $magick_ai_core_smoke_initial_policy_mode;

	if ( $magick_ai_core_smoke_cleanup_completed ) {
		return;
	}

	$magick_ai_core_smoke_cleanup_completed = true;
	update_option( \MagickAI\Core\Governance\Approval_Policy_Evaluator::OPTION_POLICY_MODE, \MagickAI\Core\Governance\Approval_Policy_Evaluator::sanitize_policy_mode( (string) $magick_ai_core_smoke_initial_policy_mode ), false );

	foreach ( array_keys( (array) $magick_ai_core_smoke_app_key_fixture_ids ) as $key_id ) {
		$app_keys = new \MagickAI\Core\Security\App_Key_Repository();
		$revoked  = $app_keys->revoke_by_key_id( (string) $key_id );
		$app      = $app_keys->find_by_key_id( (string) $key_id );
		if ( ! $revoked && is_array( $app ) && 'revoked' !== (string) ( $app['status'] ?? '' ) ) {
			fwrite( STDERR, '[warn] failed to revoke smoke app key fixture ' . (string) $key_id . "\n" );
		}
	}

	foreach ( array_keys( (array) $magick_ai_core_smoke_comment_fixture_ids ) as $comment_id ) {
		$comment_id = (int) $comment_id;
		if ( $comment_id <= 0 || ! get_comment( $comment_id ) ) {
			continue;
		}

		if ( false === wp_delete_comment( $comment_id, true ) ) {
			fwrite( STDERR, '[warn] failed to delete smoke comment fixture ' . $comment_id . "\n" );
		}
	}

	foreach ( array_keys( (array) $magick_ai_core_smoke_attachment_fixture_ids ) as $attachment_id ) {
		$attachment_id = (int) $attachment_id;
		if ( $attachment_id <= 0 || 'attachment' !== get_post_type( $attachment_id ) ) {
			continue;
		}

		$deleted = wp_delete_attachment( $attachment_id, true );
		if ( false === $deleted ) {
			fwrite( STDERR, '[warn] failed to delete smoke attachment fixture ' . $attachment_id . "\n" );
		}
	}

	foreach ( array_keys( (array) $magick_ai_core_smoke_post_fixture_ids ) as $post_id ) {
		$post_id = (int) $post_id;
		if ( $post_id <= 0 || false === get_post_type( $post_id ) ) {
			continue;
		}

		if ( false === wp_delete_post( $post_id, true ) ) {
			fwrite( STDERR, '[warn] failed to delete smoke post fixture ' . $post_id . "\n" );
		}
	}

	foreach ( (array) $magick_ai_core_smoke_term_fixtures as $term_fixture ) {
		$term_id  = (int) ( $term_fixture['term_id'] ?? 0 );
		$taxonomy = (string) ( $term_fixture['taxonomy'] ?? '' );
		if ( $term_id <= 0 || '' === $taxonomy || ! term_exists( $term_id, $taxonomy ) ) {
			continue;
		}

		$deleted = wp_delete_term( $term_id, $taxonomy );
		if ( is_wp_error( $deleted ) || false === $deleted ) {
			fwrite( STDERR, '[warn] failed to delete smoke term fixture ' . $taxonomy . ':' . $term_id . "\n" );
		}
	}

	magick_ai_core_smoke_purge_governance_records();
}

register_shutdown_function( 'magick_ai_core_smoke_cleanup_fixtures' );
update_option( \MagickAI\Core\Governance\Approval_Policy_Evaluator::OPTION_POLICY_MODE, \MagickAI\Core\Governance\Approval_Policy_Evaluator::MODE_MANUAL, false );

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
	magick_ai_core_smoke_track_rest_fixture( $method, $route, $data );

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
	magick_ai_core_smoke_track_rest_fixture( $method, $route, $data );

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
 * Verifies the preferred create-draft proposal target schema is discoverable.
 *
 * @param array<string,mixed> $items_by_id Capability rows keyed by ability id.
 * @return void
 */
function magick_ai_core_smoke_assert_create_draft_contract( array $items_by_id ): void {
	$ability_id = 'magick-ai/create-draft';
	magick_ai_core_smoke_assert( isset( $items_by_id[ $ability_id ] ), 'create-draft ability is discoverable for the primary governance scenario' );

	$ability      = $items_by_id[ $ability_id ];
	$input_schema = is_array( $ability['input_schema'] ?? null ) ? $ability['input_schema'] : array();
	$required     = (array) ( $input_schema['required'] ?? array() );
	$properties   = is_array( $input_schema['properties'] ?? null ) ? $input_schema['properties'] : array();

	magick_ai_core_smoke_assert( 'write' === (string) ( $ability['risk_level'] ?? '' ), 'create-draft is discovered as a write-risk ability' );
	magick_ai_core_smoke_assert( true === (bool) ( $ability['requires_approval'] ?? false ), 'create-draft is discovered as requiring approval' );
	magick_ai_core_smoke_assert_capability_guidance( $ability, 'proposal_required', 'adapter_after_core_preflight', 'create-draft uses proposal-required execution guidance' );
	magick_ai_core_smoke_assert( in_array( 'title', $required, true ), 'create-draft input schema requires title' );

	foreach ( array( 'dry_run', 'commit', 'idempotency_key' ) as $control ) {
		magick_ai_core_smoke_assert( array_key_exists( $control, $properties ), 'create-draft input schema exposes governance control ' . $control );
	}
}

/**
 * Verifies the preferred SEO metadata proposal target schema is discoverable.
 *
 * @param array<string,mixed> $items_by_id Capability rows keyed by ability id.
 * @return void
 */
function magick_ai_core_smoke_assert_seo_meta_contract( array $items_by_id ): void {
	$ability_id = 'magick-ai/set-post-seo-meta';
	magick_ai_core_smoke_assert( isset( $items_by_id[ $ability_id ] ), 'set-post-seo-meta ability is discoverable for the second governance scenario' );

	$ability      = $items_by_id[ $ability_id ];
	$input_schema = is_array( $ability['input_schema'] ?? null ) ? $ability['input_schema'] : array();
	$required     = (array) ( $input_schema['required'] ?? array() );
	$properties   = is_array( $input_schema['properties'] ?? null ) ? $input_schema['properties'] : array();

	magick_ai_core_smoke_assert( 'write' === (string) ( $ability['risk_level'] ?? '' ), 'set-post-seo-meta is discovered as a write-risk ability' );
	magick_ai_core_smoke_assert( true === (bool) ( $ability['requires_approval'] ?? false ), 'set-post-seo-meta is discovered as requiring approval' );
	magick_ai_core_smoke_assert_capability_guidance( $ability, 'proposal_required', 'adapter_after_core_preflight', 'set-post-seo-meta uses proposal-required execution guidance' );
	magick_ai_core_smoke_assert( in_array( 'post_id', $required, true ), 'set-post-seo-meta input schema requires post_id' );

	foreach ( array( 'seo_title', 'seo_description', 'dry_run', 'commit', 'idempotency_key' ) as $control ) {
		magick_ai_core_smoke_assert( array_key_exists( $control, $properties ), 'set-post-seo-meta input schema exposes field/control ' . $control );
	}
}

/**
 * Verifies the preferred comment approval proposal target schema is discoverable.
 *
 * @param array<string,mixed> $items_by_id Capability rows keyed by ability id.
 * @return void
 */
function magick_ai_core_smoke_assert_comment_approval_contract( array $items_by_id ): void {
	$ability_id = 'magick-ai/approve-comment';
	magick_ai_core_smoke_assert( isset( $items_by_id[ $ability_id ] ), 'approve-comment ability is discoverable for the third governance scenario' );

	$ability      = $items_by_id[ $ability_id ];
	$input_schema = is_array( $ability['input_schema'] ?? null ) ? $ability['input_schema'] : array();
	$required     = (array) ( $input_schema['required'] ?? array() );
	$properties   = is_array( $input_schema['properties'] ?? null ) ? $input_schema['properties'] : array();

	magick_ai_core_smoke_assert( 'write' === (string) ( $ability['risk_level'] ?? '' ), 'approve-comment is discovered as a write-risk ability' );
	magick_ai_core_smoke_assert( true === (bool) ( $ability['requires_approval'] ?? false ), 'approve-comment is discovered as requiring approval' );
	magick_ai_core_smoke_assert_capability_guidance( $ability, 'proposal_required', 'adapter_after_core_preflight', 'approve-comment uses proposal-required execution guidance' );
	magick_ai_core_smoke_assert( in_array( 'comment_id', $required, true ), 'approve-comment input schema requires comment_id' );

	foreach ( array( 'dry_run', 'commit', 'idempotency_key' ) as $control ) {
		magick_ai_core_smoke_assert( array_key_exists( $control, $properties ), 'approve-comment input schema exposes governance control ' . $control );
	}
}

/**
 * Verifies the taxonomy terms preview-to-proposal abilities are discoverable.
 *
 * @param array<string,mixed> $items_by_id Capability rows keyed by ability id.
 * @return void
 */
function magick_ai_core_smoke_assert_taxonomy_terms_contract( array $items_by_id ): void {
	$preview_id = 'magick-ai/propose-post-taxonomy-terms';
	$target_id  = 'magick-ai/set-post-terms';
	magick_ai_core_smoke_assert( isset( $items_by_id[ $preview_id ] ), 'propose-post-taxonomy-terms ability is discoverable for taxonomy preview consumption' );
	magick_ai_core_smoke_assert( isset( $items_by_id[ $target_id ] ), 'set-post-terms ability is discoverable for taxonomy proposal governance' );

	$preview            = $items_by_id[ $preview_id ];
	$preview_schema     = is_array( $preview['input_schema'] ?? null ) ? $preview['input_schema'] : array();
	$preview_required   = (array) ( $preview_schema['required'] ?? array() );
	$preview_properties = is_array( $preview_schema['properties'] ?? null ) ? $preview_schema['properties'] : array();

	magick_ai_core_smoke_assert( 'read' === (string) ( $preview['risk_level'] ?? '' ), 'propose-post-taxonomy-terms is discovered as read risk' );
	magick_ai_core_smoke_assert( false === (bool) ( $preview['requires_approval'] ?? true ), 'propose-post-taxonomy-terms does not require approval before read execution' );
	magick_ai_core_smoke_assert_capability_guidance( $preview, 'direct_read', 'wp_abilities_rest', 'propose-post-taxonomy-terms uses direct read guidance' );
	magick_ai_core_smoke_assert( in_array( 'post_id', $preview_required, true ), 'propose-post-taxonomy-terms input schema requires post_id' );
	foreach ( array( 'taxonomy', 'mode', 'candidate_term_ids', 'candidate_terms' ) as $field ) {
		magick_ai_core_smoke_assert( array_key_exists( $field, $preview_properties ), 'propose-post-taxonomy-terms input schema exposes field ' . $field );
	}

	$target            = $items_by_id[ $target_id ];
	$target_schema     = is_array( $target['input_schema'] ?? null ) ? $target['input_schema'] : array();
	$target_required   = (array) ( $target_schema['required'] ?? array() );
	$target_properties = is_array( $target_schema['properties'] ?? null ) ? $target_schema['properties'] : array();

	magick_ai_core_smoke_assert( 'write' === (string) ( $target['risk_level'] ?? '' ), 'set-post-terms is discovered as a write-risk ability' );
	magick_ai_core_smoke_assert( true === (bool) ( $target['requires_approval'] ?? false ), 'set-post-terms is discovered as requiring approval' );
	magick_ai_core_smoke_assert_capability_guidance( $target, 'proposal_required', 'adapter_after_core_preflight', 'set-post-terms uses proposal-required execution guidance' );
	magick_ai_core_smoke_assert( in_array( 'post_id', $target_required, true ), 'set-post-terms input schema requires post_id' );
	foreach ( array( 'taxonomy', 'mode', 'term_ids', 'terms', 'create_missing', 'dry_run', 'commit', 'idempotency_key' ) as $control ) {
		magick_ai_core_smoke_assert( array_key_exists( $control, $target_properties ), 'set-post-terms input schema exposes field/control ' . $control );
	}
}

/**
 * Verifies planning abilities used by plan-to-proposal intake are discoverable.
 *
 * @param array<string,mixed> $items_by_id Capability rows keyed by ability id.
 * @return void
 */
function magick_ai_core_smoke_assert_plan_bridge_contract( array $items_by_id ): void {
	foreach (
		array(
			'magick-ai/build-content-inventory-fix-plan',
			'magick-ai/build-test-content-cleanup-plan',
			'magick-ai/build-media-inventory-fix-plan',
		) as $ability_id
	) {
		magick_ai_core_smoke_assert( isset( $items_by_id[ $ability_id ] ), $ability_id . ' is discoverable for plan-to-proposal intake' );
		magick_ai_core_smoke_assert( 'read' === (string) ( $items_by_id[ $ability_id ]['risk_level'] ?? '' ), $ability_id . ' is a read-risk planning ability' );
		magick_ai_core_smoke_assert( false === (bool) ( $items_by_id[ $ability_id ]['requires_approval'] ?? true ), $ability_id . ' does not require approval before read execution' );
		magick_ai_core_smoke_assert_capability_guidance( $items_by_id[ $ability_id ], 'direct_read', 'wp_abilities_rest', $ability_id . ' uses direct read guidance' );
		magick_ai_core_smoke_assert_read_policy( $items_by_id[ $ability_id ], 'direct_read_internal', 'internal', false, $ability_id . ' uses internal read policy' );
	}
}

/**
 * Verifies adapter execution guidance on a capability row.
 *
 * @param array<string,mixed> $ability Capability row.
 * @param string              $governance_mode Expected governance mode.
 * @param string              $execution_surface Expected execution surface.
 * @param string              $message Assertion message.
 * @return void
 */
function magick_ai_core_smoke_assert_capability_guidance( array $ability, string $governance_mode, string $execution_surface, string $message ): void {
	magick_ai_core_smoke_assert( $governance_mode === (string) ( $ability['governance_mode'] ?? '' ), $message . ' governance_mode' );
	magick_ai_core_smoke_assert( $execution_surface === (string) ( $ability['execution_surface'] ?? '' ), $message . ' execution_surface' );
	magick_ai_core_smoke_assert( false === (bool) ( $ability['core_proxy_execute'] ?? true ), $message . ' keeps Core proxy execution disabled' );
	magick_ai_core_smoke_assert( false === (bool) ( $ability['commit_execution'] ?? true ), $message . ' keeps Core commit execution disabled' );
}

/**
 * Verifies read governance metadata on a capability row.
 *
 * @param array<string,mixed> $ability Capability row.
 * @param string              $read_policy Expected read policy.
 * @param string              $sensitivity Expected sensitivity.
 * @param bool                $redaction_required Expected redaction requirement.
 * @param string              $message Assertion message.
 * @return void
 */
function magick_ai_core_smoke_assert_read_policy( array $ability, string $read_policy, string $sensitivity, bool $redaction_required, string $message ): void {
	magick_ai_core_smoke_assert( $read_policy === (string) ( $ability['read_policy'] ?? '' ), $message . ' read_policy' );
	magick_ai_core_smoke_assert( $sensitivity === (string) ( $ability['sensitivity'] ?? '' ), $message . ' sensitivity' );
	magick_ai_core_smoke_assert( array_key_exists( 'redaction_required', $ability ) && $redaction_required === (bool) $ability['redaction_required'], $message . ' redaction_required' );
	magick_ai_core_smoke_assert( 'adapter_read_envelope' === (string) ( $ability['read_audit_mode'] ?? '' ), $message . ' read_audit_mode' );
}

/**
 * Creates a local pending comment for comment moderation smoke coverage.
 *
 * @return array{comment_id:int,post_id:int,current_status:string}
 */
function magick_ai_core_smoke_create_pending_comment(): array {
	global $magick_ai_core_smoke_run_id;

	$post_id = wp_insert_post(
		array(
			'post_title'   => 'Core Governance Comment Smoke ' . $magick_ai_core_smoke_run_id,
			'post_content' => 'Comment moderation smoke parent post.',
			'post_status'  => 'publish',
			'post_type'    => 'post',
		),
		true
	);
	magick_ai_core_smoke_assert( ! is_wp_error( $post_id ) && (int) $post_id > 0, 'comment smoke parent post is created' );
	magick_ai_core_smoke_register_post_fixture( (int) $post_id );

	$comment_id = wp_insert_comment(
		array(
			'comment_post_ID'      => (int) $post_id,
			'comment_author'       => 'Core Smoke Reviewer',
			'comment_author_email' => 'core-smoke@example.test',
			'comment_content'      => 'Pending comment for Core governance smoke ' . $magick_ai_core_smoke_run_id . '.',
			'comment_approved'     => '0',
		)
	);
	magick_ai_core_smoke_assert( (int) $comment_id > 0, 'pending comment is created for comment approval governance' );
	magick_ai_core_smoke_register_comment_fixture( (int) $comment_id );

	$comment = get_comment( (int) $comment_id );
	magick_ai_core_smoke_assert( $comment instanceof WP_Comment, 'pending comment can be loaded for preview' );

	return array(
		'comment_id'     => (int) $comment_id,
		'post_id'        => (int) $post_id,
		'current_status' => (string) $comment->comment_approved,
	);
}

/**
 * Returns an existing or newly created term id.
 *
 * @param string $name Term name.
 * @param string $taxonomy Taxonomy id.
 * @return int
 */
function magick_ai_core_smoke_term_id( string $name, string $taxonomy ): int {
	$existing = term_exists( $name, $taxonomy );
	if ( is_array( $existing ) ) {
		$term_id = (int) ( $existing['term_id'] ?? 0 );
		magick_ai_core_smoke_register_term_fixture( $term_id, $taxonomy );
		return $term_id;
	}
	if ( is_int( $existing ) ) {
		magick_ai_core_smoke_register_term_fixture( $existing, $taxonomy );
		return $existing;
	}
	if ( is_string( $existing ) && is_numeric( $existing ) ) {
		$term_id = (int) $existing;
		magick_ai_core_smoke_register_term_fixture( $term_id, $taxonomy );
		return $term_id;
	}

	$created = wp_insert_term( $name, $taxonomy );
	if ( is_wp_error( $created ) ) {
		return 0;
	}

	$term_id = (int) ( is_array( $created ) ? ( $created['term_id'] ?? 0 ) : 0 );
	magick_ai_core_smoke_register_term_fixture( $term_id, $taxonomy );

	return $term_id;
}

/**
 * Creates a local post and existing terms for taxonomy governance smoke coverage.
 *
 * @return array{post_id:int,taxonomy:string,current_term_id:int,candidate_term_id:int,current_terms:array<int,int>}
 */
function magick_ai_core_smoke_create_taxonomy_terms_fixture(): array {
	global $magick_ai_core_smoke_run_id;

	$taxonomy = 'post_tag';
	$post_id  = wp_insert_post(
		array(
			'post_title'   => 'Taxonomy terms smoke parent post ' . $magick_ai_core_smoke_run_id,
			'post_content' => 'Taxonomy terms governance smoke content.',
			'post_status'  => 'draft',
			'post_type'    => 'post',
		),
		true
	);
	magick_ai_core_smoke_assert( ! is_wp_error( $post_id ) && (int) $post_id > 0, 'taxonomy smoke parent post is created' );
	magick_ai_core_smoke_register_post_fixture( (int) $post_id );

	$current_term_id   = magick_ai_core_smoke_term_id( 'Core Smoke Current Topic ' . $magick_ai_core_smoke_run_id, $taxonomy );
	$candidate_term_id = magick_ai_core_smoke_term_id( 'Core Smoke Candidate Topic ' . $magick_ai_core_smoke_run_id, $taxonomy );
	magick_ai_core_smoke_assert( $current_term_id > 0 && $candidate_term_id > 0, 'taxonomy smoke existing terms are available' );

	$set_terms = wp_set_post_terms( (int) $post_id, array( $current_term_id ), $taxonomy, false );
	magick_ai_core_smoke_assert( ! is_wp_error( $set_terms ), 'taxonomy smoke current term is assigned before Core governance' );

	return array(
		'post_id'           => (int) $post_id,
		'taxonomy'          => $taxonomy,
		'current_term_id'   => $current_term_id,
		'candidate_term_id' => $candidate_term_id,
		'current_terms'     => array( $current_term_id ),
	);
}

/**
 * Returns sorted post term ids.
 *
 * @param int    $post_id Post id.
 * @param string $taxonomy Taxonomy id.
 * @return array<int,int>
 */
function magick_ai_core_smoke_post_term_ids( int $post_id, string $taxonomy ): array {
	$ids = wp_get_post_terms( $post_id, $taxonomy, array( 'fields' => 'ids' ) );
	if ( is_wp_error( $ids ) ) {
		return array();
	}
	$ids = array_values( array_map( 'intval', (array) $ids ) );
	sort( $ids );
	return $ids;
}

/**
 * Runs the taxonomy proposal helper through WordPress Abilities API.
 *
 * @param array<string,mixed> $fixture Taxonomy smoke fixture.
 * @return array<string,mixed>
 */
function magick_ai_core_smoke_run_taxonomy_terms_preview( array $fixture ): array {
	wp_set_current_user( 1 );

	$request = new WP_REST_Request( 'GET', '/wp-abilities/v1/abilities/magick-ai/propose-post-taxonomy-terms/run' );
	$request->set_query_params(
		array(
			'input' => array(
				'post_id'            => (int) $fixture['post_id'],
				'taxonomy'           => (string) $fixture['taxonomy'],
				'mode'               => 'append',
				'candidate_term_ids' => array( (int) $fixture['candidate_term_id'] ),
				'candidate_terms'    => array( 'Unmatched Core Smoke Topic' ),
			),
		)
	);

	$response = rest_do_request( $request );
	magick_ai_core_smoke_assert( 200 === (int) $response->get_status(), 'taxonomy terms preview helper runs through WordPress Abilities API' );
	$data = $response->get_data();
	magick_ai_core_smoke_assert( is_array( $data ) && true === (bool) ( $data['success'] ?? false ), 'taxonomy terms preview helper returns a success envelope' );
	magick_ai_core_smoke_assert( 'magick-ai/set-post-terms' === (string) ( $data['data']['proposal']['target_ability_id'] ?? '' ), 'taxonomy terms preview helper targets set-post-terms' );
	magick_ai_core_smoke_assert( false === ( $data['data']['proposal']['commit_execution'] ?? null ), 'taxonomy terms preview helper does not execute commits' );

	return $data;
}

/**
 * Runs a read-only planning ability through WordPress Abilities API.
 *
 * @param string              $ability_id Planning ability id.
 * @param array<string,mixed> $input Ability input.
 * @return array<string,mixed>
 */
function magick_ai_core_smoke_run_plan_ability( string $ability_id, array $input ): array {
	wp_set_current_user( 1 );

	$request = new WP_REST_Request( 'GET', '/wp-abilities/v1/abilities/' . $ability_id . '/run' );
	$request->set_query_params(
		array(
			'input' => $input,
		)
	);

	$response = rest_do_request( $request );
	magick_ai_core_smoke_assert( 200 === (int) $response->get_status(), $ability_id . ' planning ability run returns 200' );
	$data = $response->get_data();
	magick_ai_core_smoke_assert( is_array( $data ) && true === (bool) ( $data['success'] ?? false ), $ability_id . ' returns a success envelope' );
	magick_ai_core_smoke_assert( true === (bool) ( $data['data']['requires_approval'] ?? false ), $ability_id . ' plan requires approval' );
	magick_ai_core_smoke_assert( false === (bool) ( $data['data']['commit_execution'] ?? true ), $ability_id . ' plan does not execute commits' );
	magick_ai_core_smoke_assert( true === (bool) ( $data['data']['dry_run'] ?? false ), $ability_id . ' plan remains dry-run only' );

	return $data;
}

/**
 * Creates Core proposals from a planning ability output.
 *
 * @param string              $ability_id Planning ability id.
 * @param array<string,mixed> $plan Planning ability response envelope.
 * @param array<string,mixed> $plan_input Input used to build the plan.
 * @return array<string,mixed>
 */
function magick_ai_core_smoke_create_proposals_from_plan( string $ability_id, array $plan, array $plan_input ): array {
	global $magick_ai_core_smoke_run_id;

	return magick_ai_core_smoke_rest(
		'POST',
		'/magick-ai-core/v1/proposals/from-plan',
		array(
			'plan_ability_id' => $ability_id,
			'plan'            => $plan,
			'plan_input'      => $plan_input,
			'caller'          => array(
				'source' => 'tests/smoke-wp.php:' . $magick_ai_core_smoke_run_id,
			),
		)
	);
}

/**
 * Creates Core proposals from a planning ability output as an app client.
 *
 * @param string              $ability_id Planning ability id.
 * @param array<string,mixed> $plan Planning ability response envelope.
 * @param array<string,mixed> $plan_input Input used to build the plan.
 * @param string              $token App token.
 * @return array<string,mixed>
 */
function magick_ai_core_smoke_create_proposals_from_plan_as_app( string $ability_id, array $plan, array $plan_input, string $token ): array {
	global $magick_ai_core_smoke_run_id;

	return magick_ai_core_smoke_rest_as_app(
		'POST',
		'/magick-ai-core/v1/proposals/from-plan',
		$token,
		array(
			'plan_ability_id' => $ability_id,
			'plan'            => $plan,
			'plan_input'      => $plan_input,
			'caller'          => array(
				'source' => 'tests/smoke-wp.php-app:' . $magick_ai_core_smoke_run_id,
			),
		)
	);
}

/**
 * Verifies a proposal produced by the plan-to-proposal bridge.
 *
 * @param array<string,mixed> $proposal Proposal row.
 * @param string              $target_ability_id Expected target ability id.
 * @param bool                $proposal_ready Expected proposal readiness.
 * @return void
 */
function magick_ai_core_smoke_assert_plan_proposal_shape( array $proposal, string $target_ability_id, bool $proposal_ready ): void {
	$preview = is_array( $proposal['preview'] ?? null ) ? $proposal['preview'] : array();
	$input   = is_array( $proposal['input'] ?? null ) ? $proposal['input'] : array();

	magick_ai_core_smoke_assert( 'pending' === (string) ( $proposal['status'] ?? '' ), $target_ability_id . ' plan proposal starts pending approval' );
	magick_ai_core_smoke_assert( $target_ability_id === (string) ( $proposal['ability_id'] ?? '' ), $target_ability_id . ' plan proposal stores target ability' );
	magick_ai_core_smoke_assert( $target_ability_id === (string) ( $preview['target_ability_id'] ?? '' ), $target_ability_id . ' preview stores target ability' );
	magick_ai_core_smoke_assert( true === (bool) ( $input['dry_run'] ?? false ), $target_ability_id . ' proposal input keeps dry_run=true' );
	magick_ai_core_smoke_assert( false === (bool) ( $input['commit'] ?? true ), $target_ability_id . ' proposal input keeps commit=false' );
	magick_ai_core_smoke_assert( true === (bool) ( $preview['requires_approval'] ?? false ), $target_ability_id . ' preview records approval requirement' );
	magick_ai_core_smoke_assert( false === (bool) ( $preview['commit_execution'] ?? true ), $target_ability_id . ' preview keeps commit_execution=false' );
	magick_ai_core_smoke_assert( $proposal_ready === (bool) ( $preview['proposal_ready'] ?? false ), $target_ability_id . ' preview records proposal readiness' );
	magick_ai_core_smoke_assert( is_array( $preview['risk'] ?? null ) && '' !== (string) ( $preview['risk']['level'] ?? '' ), $target_ability_id . ' preview records risk' );
	magick_ai_core_smoke_assert( array_key_exists( 'required_scopes', $preview ), $target_ability_id . ' preview records required scopes' );
}

/**
 * Approves and preflights a plan-generated proposal.
 *
 * @param string $proposal_id Proposal id.
 * @return array<string,mixed>
 */
function magick_ai_core_smoke_approve_and_preflight_plan_proposal( string $proposal_id ): array {
	$approved = magick_ai_core_smoke_rest(
		'POST',
		'/magick-ai-core/v1/proposals/' . rawurlencode( $proposal_id ) . '/approve',
		array(
			'note' => 'Plan bridge smoke approval.',
		)
	);
	magick_ai_core_smoke_assert( 'approved' === (string) ( $approved['status'] ?? '' ), 'plan-generated proposal is approved through Core REST' );

	$preflight = magick_ai_core_smoke_rest( 'POST', '/magick-ai-core/v1/proposals/' . rawurlencode( $proposal_id ) . '/commit-preflight' );
	magick_ai_core_smoke_assert( false === (bool) ( $preflight['commit_execution'] ?? true ), 'plan-generated proposal preflight keeps Core execution disabled' );
	magick_ai_core_smoke_assert( true === (bool) ( $preflight['proposal_item_preflight']['executable'] ?? false ), 'plan-generated proposal preflight marks ready item executable' );
	magick_ai_core_smoke_assert( '' !== (string) ( $preflight['correlation_id'] ?? '' ), 'plan-generated proposal preflight returns correlation id' );

	return $preflight;
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
	global $magick_ai_core_smoke_run_id;

	magick_ai_core_smoke_assert( isset( $items_by_id[ $ability_id ] ), $ability_id . ' is discoverable for proposal governance' );
	magick_ai_core_smoke_assert( 'read' !== (string) ( $items_by_id[ $ability_id ]['risk_level'] ?? 'read' ), $ability_id . ' is write-like for proposal governance' );
	magick_ai_core_smoke_assert( true === (bool) ( $items_by_id[ $ability_id ]['requires_approval'] ?? false ), $ability_id . ' requires approval for proposal governance' );

	$created = magick_ai_core_smoke_rest(
		'POST',
		'/magick-ai-core/v1/proposals',
		array(
			'ability_id' => $ability_id,
			'title'      => $title . ' ' . $magick_ai_core_smoke_run_id,
			'summary'    => 'Created by real WordPress smoke test.',
			'input'      => $input,
			'preview'    => $preview,
			'caller'     => array(
				'source' => 'tests/smoke-wp.php:' . $magick_ai_core_smoke_run_id,
			),
		)
	);

	$proposal_id = (string) ( $created['proposal_id'] ?? '' );
	magick_ai_core_smoke_assert( '' !== $proposal_id && 'pending' === (string) ( $created['status'] ?? '' ), $ability_id . ' proposal created in pending status' );
	magick_ai_core_smoke_assert( $ability_id === (string) ( $created['ability_id'] ?? '' ), $ability_id . ' proposal stores the real ability id' );

	$detail = magick_ai_core_smoke_rest( 'GET', '/magick-ai-core/v1/proposals/' . rawurlencode( $proposal_id ) );
	magick_ai_core_smoke_assert( $proposal_id === (string) ( $detail['proposal_id'] ?? '' ), $ability_id . ' proposal detail endpoint returns created proposal' );
	$detail_timeline = (array) ( $detail['audit_timeline'] ?? array() );
	magick_ai_core_smoke_assert( count( $detail_timeline ) >= 1, $ability_id . ' proposal detail includes audit timeline' );

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
	magick_ai_core_smoke_assert( true === (bool) ( $preflight['proposal']['input']['dry_run'] ?? false ), $ability_id . ' preflight returns the dry-run proposal input without committing' );
	magick_ai_core_smoke_assert( false === (bool) ( $preflight['proposal']['input']['commit'] ?? false ), $ability_id . ' preflight does not turn proposal input into a commit request' );
	magick_ai_core_smoke_assert( $ability_id === (string) ( $preflight['capability']['ability_id'] ?? '' ), $ability_id . ' preflight capability is rediscovered from ability intake' );
	$correlation_id = (string) ( $preflight['correlation_id'] ?? '' );
	magick_ai_core_smoke_assert( '' !== $correlation_id, $ability_id . ' commit preflight returns correlation id' );
	magick_ai_core_smoke_assert( $correlation_id === (string) ( $preflight['approval_context']['correlation_id'] ?? '' ), $ability_id . ' approval context includes matching correlation id' );
	$approved_input_hash = (string) ( $preflight['approval_context']['approved_input_hash'] ?? '' );
	magick_ai_core_smoke_assert( '' !== $approved_input_hash, $ability_id . ' approval context includes approved input hash' );
	magick_ai_core_smoke_assert( $approved_input_hash === hash( 'sha256', (string) wp_json_encode( $preflight['proposal']['input'] ?? array() ) ), $ability_id . ' approved input hash matches proposal input' );
	magick_ai_core_smoke_assert( '' !== (string) ( $preflight['approval_context']['approved_preview_hash'] ?? '' ), $ability_id . ' approval context includes approved preview hash' );
	magick_ai_core_smoke_assert( 'core-preflight-v1' === (string) ( $preflight['approval_context']['policy_version'] ?? '' ), $ability_id . ' approval context includes policy version' );
	magick_ai_core_smoke_assert( $approved_input_hash === (string) ( $preflight['execution_handoff']['approved_input_hash'] ?? '' ), $ability_id . ' execution handoff carries approved input hash' );
	$GLOBALS['magick_ai_core_smoke_preflight_correlations'][ $proposal_id ] = $correlation_id;

	return $proposal_id;
}

require_once ABSPATH . 'wp-admin/includes/plugin.php';

$core_plugin      = 'magick-ai-core/magick-ai-core.php';
$abilities_plugins = array(
	'magick-ai-abilities/magick-ai-abilities.php',
	'magick-ai-abilities/npcink-abilities-toolkit.php',
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

magick_ai_core_smoke_assert( '' !== $abilities_plugin, 'magick-ai-abilities plugin file found' );
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
		'app_label'           => 'OpenClaw smoke adapter ' . $magick_ai_core_smoke_run_id,
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
		'app_label'           => 'OpenClaw revoked smoke ' . $magick_ai_core_smoke_run_id,
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

$items_by_id                     = array();
$all_have_governance_mode        = true;
$all_have_execution_surface      = true;
$all_have_read_policy            = true;
$all_have_sensitivity            = true;
$all_have_redaction_requirement  = true;
$all_disable_core_proxy_execute  = true;
$all_disable_core_commit_execute = true;
foreach ( $items as $item ) {
	if ( is_array( $item ) && '' !== (string) ( $item['ability_id'] ?? '' ) ) {
		$all_have_governance_mode        = $all_have_governance_mode && array_key_exists( 'governance_mode', $item );
		$all_have_execution_surface      = $all_have_execution_surface && array_key_exists( 'execution_surface', $item );
		$all_have_read_policy            = $all_have_read_policy && array_key_exists( 'read_policy', $item );
		$all_have_sensitivity            = $all_have_sensitivity && array_key_exists( 'sensitivity', $item );
		$all_have_redaction_requirement  = $all_have_redaction_requirement && array_key_exists( 'redaction_required', $item );
		$all_disable_core_proxy_execute  = $all_disable_core_proxy_execute && false === (bool) ( $item['core_proxy_execute'] ?? true );
		$all_disable_core_commit_execute = $all_disable_core_commit_execute && false === (bool) ( $item['commit_execution'] ?? true );
		$items_by_id[ (string) $item['ability_id'] ] = $item;
	}
}
magick_ai_core_smoke_assert( $all_have_governance_mode, 'all capability rows expose governance mode guidance' );
magick_ai_core_smoke_assert( $all_have_execution_surface, 'all capability rows expose execution surface guidance' );
magick_ai_core_smoke_assert( $all_have_read_policy, 'all capability rows expose read policy guidance' );
magick_ai_core_smoke_assert( $all_have_sensitivity, 'all capability rows expose read sensitivity guidance' );
magick_ai_core_smoke_assert( $all_have_redaction_requirement, 'all capability rows expose read redaction requirement' );
magick_ai_core_smoke_assert( $all_disable_core_proxy_execute, 'all capability rows keep Core proxy execution disabled' );
magick_ai_core_smoke_assert( $all_disable_core_commit_execute, 'all capability rows keep Core commit execution disabled' );

magick_ai_core_smoke_assert( isset( $items_by_id['magick-ai/site-info'] ), 'site-info read ability is discoverable for direct read guidance' );
magick_ai_core_smoke_assert_capability_guidance( $items_by_id['magick-ai/site-info'], 'direct_read', 'wp_abilities_rest', 'site-info uses direct read execution guidance' );
magick_ai_core_smoke_assert_read_policy( $items_by_id['magick-ai/site-info'], 'direct_read_public', 'public', false, 'site-info uses public read policy' );
if ( isset( $items_by_id['magick-ai-abilities/wp-diagnostics-summary'] ) ) {
	magick_ai_core_smoke_assert_read_policy( $items_by_id['magick-ai-abilities/wp-diagnostics-summary'], 'direct_read_sensitive', 'sensitive', true, 'diagnostics uses sensitive read policy' );
}

magick_ai_core_smoke_assert_create_draft_contract( $items_by_id );
magick_ai_core_smoke_assert_seo_meta_contract( $items_by_id );
magick_ai_core_smoke_assert_comment_approval_contract( $items_by_id );
magick_ai_core_smoke_assert_taxonomy_terms_contract( $items_by_id );
magick_ai_core_smoke_assert_plan_bridge_contract( $items_by_id );

$workflow_cases = magick_ai_core_smoke_workflow_cases();
magick_ai_core_smoke_assert( count( $workflow_cases ) > 0, 'shared workflow definitions are available to Core' );

foreach ( $workflow_cases as $case_id => $case ) {
	$preferred_id = (string) ( is_array( $case ) ? ( $case['preferred_ability_id'] ?? '' ) : '' );
	magick_ai_core_smoke_assert( isset( $items_by_id[ $preferred_id ] ), 'replay case ' . $case_id . ' preferred bundle is discoverable by Core' );
	magick_ai_core_smoke_assert( 'read' === (string) ( $items_by_id[ $preferred_id ]['risk_level'] ?? '' ), 'replay case ' . $case_id . ' preferred bundle remains read risk' );
	magick_ai_core_smoke_assert( false === (bool) ( $items_by_id[ $preferred_id ]['requires_approval'] ?? true ), 'replay case ' . $case_id . ' preferred bundle does not require approval' );
	magick_ai_core_smoke_assert_capability_guidance( $items_by_id[ $preferred_id ], 'direct_read', 'wp_abilities_rest', 'replay case ' . $case_id . ' preferred bundle uses direct read guidance' );

	foreach ( (array) ( $case['disallowed_default_ability_ids'] ?? array() ) as $disallowed_id ) {
		$disallowed_id = (string) $disallowed_id;
		magick_ai_core_smoke_assert( isset( $items_by_id[ $disallowed_id ] ), 'replay case ' . $case_id . ' disallowed default ability is discoverable for proposal handoff' );
		magick_ai_core_smoke_assert( 'read' !== (string) ( $items_by_id[ $disallowed_id ]['risk_level'] ?? 'read' ), 'replay case ' . $case_id . ' disallowed default ability is write-like' );
		magick_ai_core_smoke_assert( true === (bool) ( $items_by_id[ $disallowed_id ]['requires_approval'] ?? false ), 'replay case ' . $case_id . ' disallowed default ability requires approval in Core' );
		magick_ai_core_smoke_assert_capability_guidance( $items_by_id[ $disallowed_id ], 'proposal_required', 'adapter_after_core_preflight', 'replay case ' . $case_id . ' disallowed default ability uses proposal guidance' );
	}
}

$planning_label = magick_ai_core_smoke_rest_result(
	'POST',
	'/magick-ai-core/v1/proposals',
	array(
		'ability_id' => 'content/draft-preview',
		'title'      => 'Planning label should not be accepted ' . $magick_ai_core_smoke_run_id,
	)
);
magick_ai_core_smoke_assert( 404 === (int) $planning_label['status'], 'proposal creation rejects planning labels that are not real ability ids' );

$proposal_id = magick_ai_core_smoke_run_governance_proposal(
	'magick-ai/create-draft',
	$items_by_id,
	'Smoke draft proposal',
	array(
		'title'   => 'Core Governance Smoke Draft ' . $magick_ai_core_smoke_run_id,
		'content' => '<p>Smoke draft content.</p>',
		'status'  => 'draft',
		'dry_run' => true,
		'commit'  => false,
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
		'post_id'         => 1,
		'seo_title'       => 'Smoke SEO Title ' . $magick_ai_core_smoke_run_id,
		'seo_description' => 'Smoke SEO description.',
		'dry_run'         => true,
		'commit'          => false,
	),
	array(
		'field_patch'      => array(
			'seo_title'       => 'Smoke SEO Title ' . $magick_ai_core_smoke_run_id,
			'seo_description' => 'Smoke SEO description.',
		),
		'dry_run'          => true,
		'host_governed'    => true,
		'commit_execution' => false,
	)
);

magick_ai_core_smoke_assert( '' !== $seo_proposal_id, 'SEO metadata proposal completed governance loop' );

$pending_comment = magick_ai_core_smoke_create_pending_comment();

$comment_proposal_id = magick_ai_core_smoke_run_governance_proposal(
	'magick-ai/approve-comment',
	$items_by_id,
	'Smoke comment moderation proposal',
	array(
		'comment_id' => $pending_comment['comment_id'],
		'dry_run'    => true,
		'commit'     => false,
	),
	array(
		'comment_id'       => $pending_comment['comment_id'],
		'post_id'          => $pending_comment['post_id'],
		'current_status'   => $pending_comment['current_status'],
		'target_action'    => 'approve',
		'dry_run'          => true,
		'host_governed'    => true,
		'commit_execution' => false,
	)
);

magick_ai_core_smoke_assert( '' !== $comment_proposal_id, 'comment moderation proposal completed governance loop' );
$comment_after_preflight = get_comment( $pending_comment['comment_id'] );
magick_ai_core_smoke_assert( $comment_after_preflight instanceof WP_Comment && $pending_comment['current_status'] === (string) $comment_after_preflight->comment_approved, 'comment approval governance loop does not mutate comment status' );

$taxonomy_fixture = magick_ai_core_smoke_create_taxonomy_terms_fixture();
$taxonomy_before  = magick_ai_core_smoke_post_term_ids( $taxonomy_fixture['post_id'], $taxonomy_fixture['taxonomy'] );
$taxonomy_preview = magick_ai_core_smoke_run_taxonomy_terms_preview( $taxonomy_fixture );
$taxonomy_data    = is_array( $taxonomy_preview['data'] ?? null ) ? $taxonomy_preview['data'] : array();
$taxonomy_input   = is_array( $taxonomy_data['proposal']['input'] ?? null ) ? $taxonomy_data['proposal']['input'] : array();
magick_ai_core_smoke_assert( true === (bool) ( $taxonomy_input['dry_run'] ?? false ), 'taxonomy terms preview produces dry-run set-post-terms input' );
magick_ai_core_smoke_assert( false === (bool) ( $taxonomy_input['commit'] ?? false ), 'taxonomy terms preview keeps set-post-terms commit disabled' );
magick_ai_core_smoke_assert( false === (bool) ( $taxonomy_input['create_missing'] ?? true ), 'taxonomy terms preview does not request term creation' );

$taxonomy_proposal_id = magick_ai_core_smoke_run_governance_proposal(
	'magick-ai/set-post-terms',
	$items_by_id,
	'Smoke taxonomy terms proposal',
	$taxonomy_input,
	array(
		'proposal_helper_ability_id' => 'magick-ai/propose-post-taxonomy-terms',
		'target_ability_id'          => 'magick-ai/set-post-terms',
		'taxonomy'                   => (string) ( $taxonomy_data['taxonomy'] ?? 'post_tag' ),
		'mode'                       => (string) ( $taxonomy_data['mode'] ?? 'append' ),
		'current_terms'              => (array) ( $taxonomy_data['current_terms'] ?? array() ),
		'matched_terms'              => (array) ( $taxonomy_data['matched_terms'] ?? array() ),
		'unmatched_terms'            => (array) ( $taxonomy_data['unmatched_terms'] ?? array() ),
		'proposed_term_ids'          => (array) ( $taxonomy_data['proposed_term_ids'] ?? array() ),
		'added_term_ids'             => (array) ( $taxonomy_data['added_term_ids'] ?? array() ),
		'removed_term_ids'           => (array) ( $taxonomy_data['removed_term_ids'] ?? array() ),
		'dry_run'                    => true,
		'host_governed'              => true,
		'commit_execution'           => false,
	)
);

magick_ai_core_smoke_assert( '' !== $taxonomy_proposal_id, 'taxonomy terms proposal completed governance loop' );
$taxonomy_after = magick_ai_core_smoke_post_term_ids( $taxonomy_fixture['post_id'], $taxonomy_fixture['taxonomy'] );
magick_ai_core_smoke_assert( $taxonomy_before === $taxonomy_after, 'taxonomy terms governance loop does not mutate post terms' );

$plan_content_title   = 'Core Plan Bridge Content Candidate ' . $magick_ai_core_smoke_run_id;
$plan_content_post_id = wp_insert_post(
	array(
		'post_title'   => $plan_content_title,
		'post_content' => 'Core plan bridge content candidate with enough words for deterministic SEO and excerpt planning smoke coverage.',
		'post_excerpt' => '',
		'post_status'  => 'draft',
		'post_type'    => 'post',
	),
	true
);
magick_ai_core_smoke_assert( ! is_wp_error( $plan_content_post_id ) && (int) $plan_content_post_id > 0, 'plan bridge content fixture post is created' );
magick_ai_core_smoke_register_post_fixture( (int) $plan_content_post_id );

$content_plan_input = array(
	'post_ids'    => array( (int) $plan_content_post_id ),
	'issue_types' => array( 'seo_title', 'seo_description' ),
	'max_actions' => 5,
);
$content_plan       = magick_ai_core_smoke_run_plan_ability( 'magick-ai/build-content-inventory-fix-plan', $content_plan_input );
$content_plan_result = magick_ai_core_smoke_create_proposals_from_plan( 'magick-ai/build-content-inventory-fix-plan', $content_plan, $content_plan_input );
magick_ai_core_smoke_assert( (int) ( $content_plan_result['proposal_count'] ?? 0 ) >= 1, 'content fix plan generates Core proposals' );
$content_plan_proposal = is_array( $content_plan_result['proposals'][0] ?? null ) ? $content_plan_result['proposals'][0] : array();
magick_ai_core_smoke_assert_plan_proposal_shape( $content_plan_proposal, 'magick-ai/set-post-seo-meta', true );
magick_ai_core_smoke_assert( isset( $content_plan_proposal['preview']['before'] ), 'content fix plan proposal preview includes before' );
magick_ai_core_smoke_assert( isset( $content_plan_proposal['preview']['after_suggestion'] ), 'content fix plan proposal preview includes after_suggestion' );
magick_ai_core_smoke_approve_and_preflight_plan_proposal( (string) ( $content_plan_proposal['proposal_id'] ?? '' ) );

$cleanup_pattern = 'Core Plan Bridge Test Cleanup Candidate ' . $magick_ai_core_smoke_run_id;
$cleanup_post_id = wp_insert_post(
	array(
		'post_title'   => $cleanup_pattern . ' A',
		'post_content' => 'This ' . $cleanup_pattern . ' A should be detected as test content for cleanup planning.',
		'post_status'  => 'draft',
		'post_type'    => 'post',
	),
	true
);
magick_ai_core_smoke_assert( ! is_wp_error( $cleanup_post_id ) && (int) $cleanup_post_id > 0, 'plan bridge cleanup fixture post is created' );
magick_ai_core_smoke_register_post_fixture( (int) $cleanup_post_id );
$cleanup_second_post_id = wp_insert_post(
	array(
		'post_title'   => $cleanup_pattern . ' B',
		'post_content' => 'This ' . $cleanup_pattern . ' B should be detected as test content for cleanup planning.',
		'post_status'  => 'draft',
		'post_type'    => 'post',
	),
	true
);
magick_ai_core_smoke_assert( ! is_wp_error( $cleanup_second_post_id ) && (int) $cleanup_second_post_id > 0, 'second plan bridge cleanup fixture post is created' );
magick_ai_core_smoke_register_post_fixture( (int) $cleanup_second_post_id );

$cleanup_plan_input = array(
	'patterns'    => array( $cleanup_pattern ),
	'max_actions' => 5,
);
$cleanup_plan       = magick_ai_core_smoke_run_plan_ability( 'magick-ai/build-test-content-cleanup-plan', $cleanup_plan_input );
$cleanup_plan_result = magick_ai_core_smoke_create_proposals_from_plan( 'magick-ai/build-test-content-cleanup-plan', $cleanup_plan, $cleanup_plan_input );
magick_ai_core_smoke_assert( 1 === (int) ( $cleanup_plan_result['proposal_count'] ?? 0 ), 'test content cleanup plan generates one batch Core proposal' );
$cleanup_plan_proposal = is_array( $cleanup_plan_result['proposals'][0] ?? null ) ? $cleanup_plan_result['proposals'][0] : array();
$cleanup_plan_input_payload = is_array( $cleanup_plan_proposal['input'] ?? null ) ? $cleanup_plan_proposal['input'] : array();
$cleanup_plan_actions       = is_array( $cleanup_plan_input_payload['write_actions'] ?? null ) ? array_values( $cleanup_plan_input_payload['write_actions'] ) : array();
magick_ai_core_smoke_assert( 'plan_to_proposal_batch' === (string) ( $cleanup_plan_proposal['preview']['source']['type'] ?? '' ), 'test content cleanup plan records batch proposal source type' );
magick_ai_core_smoke_assert( 'batch' === (string) ( $cleanup_plan_proposal['preview']['source']['proposal_mode'] ?? '' ), 'test content cleanup batch preserves proposal_mode' );
magick_ai_core_smoke_assert( true === (bool) ( $cleanup_plan_proposal['preview']['source']['batch_approval'] ?? false ), 'test content cleanup batch preserves batch_approval' );
magick_ai_core_smoke_assert( 2 === count( $cleanup_plan_actions ), 'test content cleanup batch stores both trash-post actions' );
magick_ai_core_smoke_assert( 'magick-ai/trash-post' === (string) ( $cleanup_plan_actions[0]['target_ability_id'] ?? '' ), 'test content cleanup batch stores trash-post action targets' );
magick_ai_core_smoke_assert( 'manual_required' === (string) ( $cleanup_plan_proposal['policy_decision'] ?? '' ), 'manual policy keeps cleanup batch manual by default' );
magick_ai_core_smoke_approve_and_preflight_plan_proposal( (string) ( $cleanup_plan_proposal['proposal_id'] ?? '' ) );

update_option( \MagickAI\Core\Governance\Approval_Policy_Evaluator::OPTION_POLICY_MODE, \MagickAI\Core\Governance\Approval_Policy_Evaluator::MODE_DRY_RUN_GUARDED, false );
$dry_guarded_pattern = 'Core Dry Guarded Test Cleanup Candidate ' . $magick_ai_core_smoke_run_id;
$dry_guarded_post_id = wp_insert_post(
	array(
		'post_title'   => $dry_guarded_pattern . ' A',
		'post_content' => 'This ' . $dry_guarded_pattern . ' A should be detected as test content for dry-run guarded cleanup.',
		'post_status'  => 'draft',
		'post_type'    => 'post',
	),
	true
);
magick_ai_core_smoke_assert( ! is_wp_error( $dry_guarded_post_id ) && (int) $dry_guarded_post_id > 0, 'dry-run guarded cleanup fixture post is created' );
magick_ai_core_smoke_register_post_fixture( (int) $dry_guarded_post_id );
$dry_guarded_plan_input = array(
	'patterns'    => array( $dry_guarded_pattern ),
	'max_actions' => 5,
);
$dry_guarded_plan = magick_ai_core_smoke_run_plan_ability( 'magick-ai/build-test-content-cleanup-plan', $dry_guarded_plan_input );
$dry_guarded_result = magick_ai_core_smoke_create_proposals_from_plan( 'magick-ai/build-test-content-cleanup-plan', $dry_guarded_plan, $dry_guarded_plan_input );
magick_ai_core_smoke_assert( 1 === (int) ( $dry_guarded_result['proposal_count'] ?? 0 ), 'dry-run guarded cleanup plan generates one batch Core proposal' );
$dry_guarded_proposal = is_array( $dry_guarded_result['proposals'][0] ?? null ) ? $dry_guarded_result['proposals'][0] : array();
magick_ai_core_smoke_assert( 'pending' === (string) ( $dry_guarded_proposal['status'] ?? '' ), 'dry-run guarded cleanup remains pending' );
magick_ai_core_smoke_assert( 'manual_required' === (string) ( $dry_guarded_proposal['policy_decision'] ?? '' ), 'dry-run guarded cleanup remains manual_required' );
magick_ai_core_smoke_assert( 'guarded' === (string) ( $dry_guarded_proposal['policy_profile'] ?? '' ), 'dry-run guarded cleanup records guarded profile' );
magick_ai_core_smoke_assert( in_array( 'guarded_cleanup_candidate', (array) ( $dry_guarded_proposal['policy_reasons'] ?? array() ), true ), 'dry-run guarded cleanup records candidate reason' );
magick_ai_core_smoke_assert( in_array( 'auto_approval_dry_run_only', (array) ( $dry_guarded_proposal['policy_reasons'] ?? array() ), true ), 'dry-run guarded cleanup records dry-run-only reason' );
magick_ai_core_smoke_approve_and_preflight_plan_proposal( (string) ( $dry_guarded_proposal['proposal_id'] ?? '' ) );

$local_guarded_app = magick_ai_core_smoke_rest(
	'POST',
	'/magick-ai-core/v1/apps',
	array(
		'app_label'           => 'Local Guarded Approval Smoke ' . $magick_ai_core_smoke_run_id,
		'caller_type'         => 'trusted_adapter',
		'scopes'              => array( 'capabilities:read', 'proposals:create', 'proposals:read', 'proposals:approve', 'commit:preflight', 'audit:read' ),
		'rate_limit'          => 20,
		'rate_window_seconds' => 3600,
	)
);
$local_guarded_token = (string) ( $local_guarded_app['token'] ?? '' );
magick_ai_core_smoke_assert( '' !== $local_guarded_token, 'local guarded app key is created for auto approval smoke' );

update_option( \MagickAI\Core\Governance\Approval_Policy_Evaluator::OPTION_POLICY_MODE, \MagickAI\Core\Governance\Approval_Policy_Evaluator::MODE_LOCAL_GUARDED, false );
$local_guarded_pattern = 'Core Local Guarded Test Cleanup Candidate ' . $magick_ai_core_smoke_run_id;
$local_guarded_post_id = wp_insert_post(
	array(
		'post_title'   => $local_guarded_pattern . ' A',
		'post_content' => 'This ' . $local_guarded_pattern . ' A should be detected as test content for local guarded cleanup.',
		'post_status'  => 'draft',
		'post_type'    => 'post',
	),
	true
);
magick_ai_core_smoke_assert( ! is_wp_error( $local_guarded_post_id ) && (int) $local_guarded_post_id > 0, 'local guarded cleanup fixture post is created' );
magick_ai_core_smoke_register_post_fixture( (int) $local_guarded_post_id );
$local_guarded_plan_input = array(
	'patterns'    => array( $local_guarded_pattern ),
	'max_actions' => 5,
);
$local_guarded_plan = magick_ai_core_smoke_run_plan_ability( 'magick-ai/build-test-content-cleanup-plan', $local_guarded_plan_input );
$local_guarded_result = magick_ai_core_smoke_create_proposals_from_plan_as_app( 'magick-ai/build-test-content-cleanup-plan', $local_guarded_plan, $local_guarded_plan_input, $local_guarded_token );
magick_ai_core_smoke_assert( 1 === (int) ( $local_guarded_result['proposal_count'] ?? 0 ), 'local guarded cleanup plan generates one batch Core proposal' );
$local_guarded_proposal = is_array( $local_guarded_result['proposals'][0] ?? null ) ? $local_guarded_result['proposals'][0] : array();
$local_guarded_proposal_id = (string) ( $local_guarded_proposal['proposal_id'] ?? '' );
magick_ai_core_smoke_assert( 'approved' === (string) ( $local_guarded_proposal['status'] ?? '' ), 'local guarded cleanup is auto-approved' );
magick_ai_core_smoke_assert( 'auto_approved' === (string) ( $local_guarded_proposal['policy_decision'] ?? '' ), 'local guarded cleanup records auto-approved decision' );
magick_ai_core_smoke_assert( 'trusted_local' === (string) ( $local_guarded_proposal['policy_profile'] ?? '' ), 'local guarded cleanup records trusted_local profile' );
magick_ai_core_smoke_assert( in_array( 'local_guarded_cleanup_auto_approved', (array) ( $local_guarded_proposal['policy_reasons'] ?? array() ), true ), 'local guarded cleanup records auto-approved reason' );
$local_guarded_preflight = magick_ai_core_smoke_rest_as_app( 'POST', '/magick-ai-core/v1/proposals/' . rawurlencode( $local_guarded_proposal_id ) . '/commit-preflight', $local_guarded_token );
magick_ai_core_smoke_assert( true === (bool) ( $local_guarded_preflight['approval_context']['approval_commit_authorized'] ?? false ), 'local guarded cleanup preflight passes without manual approval' );
$local_guarded_audit = magick_ai_core_smoke_rest_as_app(
	'GET',
	'/magick-ai-core/v1/audit',
	$local_guarded_token,
	array(
		'proposal_id' => $local_guarded_proposal_id,
		'limit'       => 20,
	)
);
$local_guarded_events = array_values( array_map(
	static function ( $item ): string {
		return is_array( $item ) ? (string) ( $item['event_name'] ?? '' ) : '';
	},
	(array) ( $local_guarded_audit['items'] ?? array() )
) );
magick_ai_core_smoke_assert( in_array( 'proposal.auto_approved', $local_guarded_events, true ), 'local guarded cleanup writes auto approval audit event' );
update_option( \MagickAI\Core\Governance\Approval_Policy_Evaluator::OPTION_POLICY_MODE, \MagickAI\Core\Governance\Approval_Policy_Evaluator::MODE_MANUAL, false );

$plan_attachment_title = 'Core Plan Bridge Media Candidate ' . $magick_ai_core_smoke_run_id;
$plan_attachment_id    = magick_ai_core_smoke_create_media_attachment_fixture( $plan_attachment_title );
magick_ai_core_smoke_assert( (int) $plan_attachment_id > 0, 'plan bridge media fixture attachment is created' );

$media_plan_input = array(
	'attachment_ids'  => array( (int) $plan_attachment_id ),
	'issue_types'     => array( 'missing_alt', 'missing_caption', 'missing_description', 'possibly_unattached' ),
	'article_title'   => $plan_content_title,
	'article_excerpt' => 'Smoke media metadata context.',
	'focus_keyword'   => 'plan bridge',
	'max_actions'     => 5,
);
$media_plan       = magick_ai_core_smoke_run_plan_ability( 'magick-ai/build-media-inventory-fix-plan', $media_plan_input );
$media_plan_result = magick_ai_core_smoke_create_proposals_from_plan( 'magick-ai/build-media-inventory-fix-plan', $media_plan, $media_plan_input );
magick_ai_core_smoke_assert( (int) ( $media_plan_result['proposal_count'] ?? 0 ) >= 1, 'media inventory fix plan generates Core proposals' );
$media_targets = array_map(
	static function ( $proposal ) {
		return is_array( $proposal ) ? (string) ( $proposal['ability_id'] ?? '' ) : '';
	},
	(array) ( $media_plan_result['proposals'] ?? array() )
);
magick_ai_core_smoke_assert( ! in_array( 'magick-ai/delete-media-permanently', $media_targets, true ), 'media delete candidates do not enter executable proposals by default' );
$media_plan_proposal = is_array( $media_plan_result['proposals'][0] ?? null ) ? $media_plan_result['proposals'][0] : array();
magick_ai_core_smoke_assert_plan_proposal_shape( $media_plan_proposal, 'magick-ai/update-media-details', true );
magick_ai_core_smoke_assert( (int) ( $media_plan_proposal['preview']['warnings']['skipped_destructive_candidate_count'] ?? 0 ) >= 1, 'media plan proposal preserves skipped destructive candidates' );
magick_ai_core_smoke_approve_and_preflight_plan_proposal( (string) ( $media_plan_proposal['proposal_id'] ?? '' ) );

$media_rename_plan_input = array(
	'attachment_id'     => (int) $plan_attachment_id,
	'target_file_name'  => 'core-smoke-media-rename-reviewed',
);
$media_rename_plan = magick_ai_core_smoke_run_plan_ability( 'magick-ai/build-media-rename-plan', $media_rename_plan_input );
$media_rename_plan_result = magick_ai_core_smoke_create_proposals_from_plan( 'magick-ai/build-media-rename-plan', $media_rename_plan, $media_rename_plan_input );
magick_ai_core_smoke_assert( 1 === (int) ( $media_rename_plan_result['proposal_count'] ?? 0 ), 'media rename plan generates one Core proposal' );
$media_rename_proposal = is_array( $media_rename_plan_result['proposals'][0] ?? null ) ? $media_rename_plan_result['proposals'][0] : array();
magick_ai_core_smoke_assert_plan_proposal_shape( $media_rename_proposal, 'magick-ai/rename-media-file', true );
magick_ai_core_smoke_assert( 'core-smoke-media-rename-reviewed.png' === (string) ( $media_rename_proposal['input']['target_file_name'] ?? '' ), 'media rename proposal preserves reviewed target filename' );
magick_ai_core_smoke_assert( is_array( $media_rename_proposal['preview']['media_rename'] ?? null ), 'media rename proposal preserves rename preview context' );
magick_ai_core_smoke_approve_and_preflight_plan_proposal( (string) ( $media_rename_proposal['proposal_id'] ?? '' ) );

$media_delete_plan_input = array(
	'attachment_ids'                  => array( (int) $plan_attachment_id ),
	'issue_types'                     => array( 'possibly_unattached' ),
	'include_delete_candidates'       => true,
	'include_unattached_test_media'   => true,
	'max_actions'                     => 5,
);
$media_delete_plan       = magick_ai_core_smoke_run_plan_ability( 'magick-ai/build-media-inventory-fix-plan', $media_delete_plan_input );
$media_delete_blocked    = magick_ai_core_smoke_create_proposals_from_plan( 'magick-ai/build-media-inventory-fix-plan', $media_delete_plan, array( 'include_delete_candidates' => false ) );
magick_ai_core_smoke_assert( 0 === (int) ( $media_delete_blocked['proposal_count'] ?? -1 ), 'explicit media delete plan is blocked without matching include_delete_candidates input' );
magick_ai_core_smoke_assert( 'destructive_media_delete_not_explicitly_included' === (string) ( $media_delete_blocked['blocked_items'][0]['block_code'] ?? '' ), 'blocked media delete records destructive guard reason' );
$media_delete_plan_tampered = $media_delete_plan;
if ( is_array( $media_delete_plan_tampered['data'] ?? null ) ) {
	$media_delete_plan_tampered['data']['include_delete_candidates'] = true;
}
$media_delete_tampered = magick_ai_core_smoke_create_proposals_from_plan( 'magick-ai/build-media-inventory-fix-plan', $media_delete_plan_tampered, array() );
magick_ai_core_smoke_assert( 0 === (int) ( $media_delete_tampered['proposal_count'] ?? -1 ), 'tampered media delete plan flag does not bypass plan_input destructive guard' );
magick_ai_core_smoke_assert( 'destructive_media_delete_not_explicitly_included' === (string) ( $media_delete_tampered['blocked_items'][0]['block_code'] ?? '' ), 'tampered media delete plan is blocked by destructive guard' );
$media_delete_allowed = magick_ai_core_smoke_create_proposals_from_plan( 'magick-ai/build-media-inventory-fix-plan', $media_delete_plan, $media_delete_plan_input );
magick_ai_core_smoke_assert( (int) ( $media_delete_allowed['proposal_count'] ?? 0 ) >= 1, 'explicit media delete plan can generate a high-risk Core proposal' );
$media_delete_proposal = is_array( $media_delete_allowed['proposals'][0] ?? null ) ? $media_delete_allowed['proposals'][0] : array();
magick_ai_core_smoke_assert_plan_proposal_shape( $media_delete_proposal, 'magick-ai/delete-media-permanently', true );
magick_ai_core_smoke_assert( 'high' === (string) ( $media_delete_proposal['preview']['risk']['level'] ?? '' ), 'explicit media delete proposal is marked high risk' );

$requires_input_plan = array(
	'success' => true,
	'data'    => array(
			'batch_id'         => 'core_plan_bridge_requires_input_smoke_' . $magick_ai_core_smoke_run_id,
		'issue_types'      => array( 'title' ),
		'write_actions'    => array(
			array(
				'action_id'          => 'set_title_requires_input_smoke',
				'target_ability_id'  => 'magick-ai/update-post',
				'input'              => array(
					'post_id' => (int) $plan_content_post_id,
					'dry_run' => true,
					'commit'  => false,
				),
				'requires_approval'  => true,
				'commit_execution'   => false,
				'required_scopes'    => array( 'post.write' ),
				'risk'               => 'medium',
				'reason'             => 'Title requires human input before execution.',
				'requires_input'     => array( 'title' ),
				'proposal_ready'     => false,
			),
		),
		'preview'          => array(
			array(
				'post_id'          => (int) $plan_content_post_id,
				'before'           => array( 'title' => '' ),
				'after_suggestion' => array(),
			),
		),
		'risk'             => array(
			'level'  => 'medium',
			'reason' => 'Requires human input.',
		),
		'requires_approval' => true,
		'commit_execution' => false,
		'dry_run'          => true,
	),
);
$requires_input_result = magick_ai_core_smoke_create_proposals_from_plan( 'magick-ai/build-content-inventory-fix-plan', $requires_input_plan, array() );
magick_ai_core_smoke_assert( 1 === (int) ( $requires_input_result['proposal_count'] ?? 0 ), 'requires-input plan action still creates a reviewable proposal' );
$requires_input_proposal = is_array( $requires_input_result['proposals'][0] ?? null ) ? $requires_input_result['proposals'][0] : array();
magick_ai_core_smoke_assert_plan_proposal_shape( $requires_input_proposal, 'magick-ai/update-post', false );
magick_ai_core_smoke_assert( array( 'title' ) === (array) ( $requires_input_proposal['preview']['needs_input'] ?? array() ), 'requires-input proposal preserves missing field list' );
magick_ai_core_smoke_rest(
	'POST',
	'/magick-ai-core/v1/proposals/' . rawurlencode( (string) ( $requires_input_proposal['proposal_id'] ?? '' ) ) . '/approve',
	array(
		'note' => 'Approve blocked proposal for preflight smoke.',
	)
);
$requires_input_preflight = magick_ai_core_smoke_rest_result( 'POST', '/magick-ai-core/v1/proposals/' . rawurlencode( (string) ( $requires_input_proposal['proposal_id'] ?? '' ) ) . '/commit-preflight' );
magick_ai_core_smoke_assert( 409 === (int) $requires_input_preflight['status'], 'requires-input proposal cannot enter committable state' );
magick_ai_core_smoke_assert( false === (bool) ( $requires_input_preflight['data']['data']['proposal_item_preflight']['executable'] ?? true ), 'requires-input preflight marks proposal item blocked' );
magick_ai_core_smoke_assert( array( 'title' ) === (array) ( $requires_input_preflight['data']['data']['proposal_item_preflight']['needs_input'] ?? array() ), 'requires-input preflight reports fields needing human input' );

$output_reference_plan = array(
	'success' => true,
	'data'    => array(
			'batch_id'         => 'core_plan_bridge_output_reference_smoke_' . $magick_ai_core_smoke_run_id,
		'issue_types'      => array( 'acceptance' ),
		'write_actions'    => array(
			array(
				'action_id'          => 'create-draft-fixture',
				'target_ability_id'  => 'magick-ai/create-draft',
				'input'              => array(
					'post_type' => 'post',
					'status'    => 'draft',
						'title'     => 'Core plan bridge output reference draft ' . $magick_ai_core_smoke_run_id,
					'content'   => '<p>Created for Core plan bridge output reference smoke.</p>',
					'dry_run'   => true,
					'commit'    => false,
				),
				'requires_approval'  => true,
				'commit_execution'   => false,
				'required_scopes'    => array( 'post.write' ),
				'risk'               => 'medium',
				'reason'             => 'Create a draft fixture for a dependent update action.',
				'proposal_ready'     => true,
			),
			array(
				'action_id'          => 'update-created-draft',
				'target_ability_id'  => 'magick-ai/update-post',
				'depends_on'         => array( 'create-draft-fixture' ),
				'input'              => array(
					'post_id' => '$outputs.create-draft-fixture.post_id',
						'title'   => 'Core plan bridge output reference updated draft ' . $magick_ai_core_smoke_run_id,
					'dry_run' => true,
					'commit'  => false,
				),
				'requires_approval'  => true,
				'commit_execution'   => false,
				'required_scopes'    => array( 'post.write' ),
				'risk'               => 'medium',
				'reason'             => 'Update the draft created by the prior action.',
				'proposal_ready'     => true,
			),
		),
		'preview'          => array(),
		'risk'             => array(
			'level'  => 'medium',
			'reason' => 'Ordered write action smoke.',
		),
		'requires_approval' => true,
		'commit_execution' => false,
		'dry_run'          => true,
	),
);
$output_reference_result = magick_ai_core_smoke_create_proposals_from_plan( 'magick-ai/build-content-inventory-fix-plan', $output_reference_plan, array() );
magick_ai_core_smoke_assert( 1 === (int) ( $output_reference_result['proposal_count'] ?? 0 ), 'output-reference plan creates one batch proposal' );
$output_reference_proposal = is_array( $output_reference_result['proposals'][0] ?? null ) ? $output_reference_result['proposals'][0] : array();
$output_reference_input    = is_array( $output_reference_proposal['input'] ?? null ) ? $output_reference_proposal['input'] : array();
$output_reference_actions  = is_array( $output_reference_input['write_actions'] ?? null ) ? array_values( $output_reference_input['write_actions'] ) : array();
magick_ai_core_smoke_assert( 'plan_to_proposal_batch' === (string) ( $output_reference_proposal['preview']['source']['type'] ?? '' ), 'output-reference plan proposal records batch source type' );
magick_ai_core_smoke_assert( 'magick-ai/create-draft' === (string) ( $output_reference_proposal['ability_id'] ?? '' ), 'output-reference batch proposal stores first target ability for Core availability checks' );
magick_ai_core_smoke_assert( 2 === count( $output_reference_actions ), 'output-reference batch proposal stores ordered write_actions' );
magick_ai_core_smoke_assert( '$outputs.create-draft-fixture.post_id' === (string) ( $output_reference_actions[1]['input']['post_id'] ?? '' ), 'output-reference batch proposal preserves post_id output reference' );
magick_ai_core_smoke_assert( array( 'create-draft-fixture' ) === (array) ( $output_reference_actions[1]['depends_on'] ?? array() ), 'output-reference batch proposal preserves depends_on on write action' );
magick_ai_core_smoke_assert( array( 'create-draft-fixture' ) === (array) ( $output_reference_proposal['preview']['actions'][1]['depends_on'] ?? array() ), 'output-reference batch proposal preserves depends_on in preview action' );
magick_ai_core_smoke_approve_and_preflight_plan_proposal( (string) ( $output_reference_proposal['proposal_id'] ?? '' ) );

$unsafe_action_plan = $requires_input_plan;
$unsafe_action_plan['data']['write_actions'][0]['requires_approval'] = false;
$unsafe_action_plan['data']['write_actions'][0]['commit_execution'] = true;
$unsafe_action_result = magick_ai_core_smoke_create_proposals_from_plan( 'magick-ai/build-content-inventory-fix-plan', $unsafe_action_plan, array() );
magick_ai_core_smoke_assert( 0 === (int) ( $unsafe_action_result['proposal_count'] ?? -1 ), 'unsafe write action is blocked before proposal creation' );
magick_ai_core_smoke_assert( 'action_requires_approval_missing' === (string) ( $unsafe_action_result['blocked_items'][0]['block_code'] ?? '' ), 'unsafe write action records missing action approval guard' );
$executed_action_plan = $requires_input_plan;
$executed_action_plan['data']['write_actions'][0]['requires_approval'] = true;
$executed_action_plan['data']['write_actions'][0]['commit_execution'] = true;
$executed_action_result = magick_ai_core_smoke_create_proposals_from_plan( 'magick-ai/build-content-inventory-fix-plan', $executed_action_plan, array() );
magick_ai_core_smoke_assert( 0 === (int) ( $executed_action_result['proposal_count'] ?? -1 ), 'already-executed write action is blocked before proposal creation' );
magick_ai_core_smoke_assert( 'action_commit_execution_rejected' === (string) ( $executed_action_result['blocked_items'][0]['block_code'] ?? '' ), 'already-executed write action records commit execution guard' );

$app_proposal_payload = array(
	'ability_id' => 'magick-ai/create-draft',
	'title'      => 'App authenticated proposal ' . $magick_ai_core_smoke_run_id,
	'summary'    => 'Created by app key smoke test.',
	'input'      => array(
		'title'   => 'App Auth Draft ' . $magick_ai_core_smoke_run_id,
		'content' => '<p>App auth content.</p>',
		'dry_run' => true,
	),
	'preview'    => array(
		'dry_run' => true,
	),
	'caller'     => array(
		'source' => 'app-auth-smoke:' . $magick_ai_core_smoke_run_id,
	),
);
$app_created = magick_ai_core_smoke_rest_as_app(
	'POST',
	'/magick-ai-core/v1/proposals',
	$app_token,
	$app_proposal_payload
);
$app_proposal_id = (string) ( $app_created['proposal_id'] ?? '' );
magick_ai_core_smoke_assert( '' !== $app_proposal_id, 'app-authenticated proposal is created' );
magick_ai_core_smoke_assert( $app_id === (string) ( $app_created['caller']['auth']['app_id'] ?? '' ), 'app-authenticated proposal stores app attribution' );
magick_ai_core_smoke_assert( 'proposals:create' === (string) ( $app_created['caller']['auth']['scope'] ?? '' ), 'app-authenticated proposal stores scope attribution' );
magick_ai_core_smoke_assert( 'allowed' === (string) ( $app_created['caller']['auth']['scope_decision'] ?? '' ), 'app-authenticated proposal stores allowed scope decision' );
$app_duplicate = magick_ai_core_smoke_rest_result_as_app( 'POST', '/magick-ai-core/v1/proposals', $app_token, $app_proposal_payload );
magick_ai_core_smoke_assert( 200 === (int) $app_duplicate['status'], 'duplicate app proposal returns existing pending proposal with HTTP 200' );
$app_duplicate_data = is_array( $app_duplicate['data'] ?? null ) ? $app_duplicate['data'] : array();
magick_ai_core_smoke_assert( $app_proposal_id === (string) ( $app_duplicate_data['proposal_id'] ?? '' ), 'duplicate app proposal reuses the existing pending proposal id' );
magick_ai_core_smoke_assert( true === (bool) ( $app_duplicate_data['deduplicated'] ?? false ), 'duplicate app proposal response is marked deduplicated' );

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
magick_ai_core_smoke_assert( '' !== (string) ( $app_preflight['correlation_id'] ?? '' ), 'app-authenticated commit preflight returns correlation id' );
magick_ai_core_smoke_assert( '' !== (string) ( $app_preflight['approval_context']['approved_input_hash'] ?? '' ), 'app-authenticated commit preflight returns approved input hash' );
magick_ai_core_smoke_assert( 'core-preflight-v1' === (string) ( $app_preflight['approval_context']['policy_version'] ?? '' ), 'app-authenticated commit preflight returns policy version' );
magick_ai_core_smoke_assert( 'adapter_after_core_preflight' === (string) ( $app_preflight['execution_handoff']['executor'] ?? '' ), 'app-authenticated commit preflight returns adapter execution handoff' );
magick_ai_core_smoke_assert( false === (bool) ( $app_preflight['execution_handoff']['commit_execution'] ?? true ), 'app-authenticated execution handoff keeps Core commit execution disabled' );

$quota_app = magick_ai_core_smoke_rest(
	'POST',
	'/magick-ai-core/v1/apps',
	array(
		'app_label'           => 'OpenClaw pending quota smoke ' . $magick_ai_core_smoke_run_id,
		'scopes'              => array( 'proposals:create' ),
		'rate_limit'          => 100,
		'rate_window_seconds' => 3600,
	)
);
$quota_token = (string) ( $quota_app['token'] ?? '' );
for ( $quota_index = 1; $quota_index <= 20; ++$quota_index ) {
	magick_ai_core_smoke_rest_as_app(
		'POST',
		'/magick-ai-core/v1/proposals',
		$quota_token,
		array(
			'ability_id' => 'magick-ai/create-draft',
			'title'      => 'Pending quota proposal ' . $magick_ai_core_smoke_run_id . ' ' . $quota_index,
			'summary'    => 'Created to verify pending proposal quota.',
			'input'      => array(
				'title'   => 'Pending quota draft ' . $magick_ai_core_smoke_run_id . ' ' . $quota_index,
				'content' => '<p>Pending quota smoke.</p>',
				'dry_run' => true,
			),
			'preview'    => array(
				'dry_run' => true,
			),
			'caller'     => array(
				'source' => 'pending-quota-smoke:' . $magick_ai_core_smoke_run_id,
			),
		)
	);
}
$quota_blocked = magick_ai_core_smoke_rest_result_as_app(
	'POST',
	'/magick-ai-core/v1/proposals',
	$quota_token,
	array(
		'ability_id' => 'magick-ai/create-draft',
		'title'      => 'Pending quota proposal blocked ' . $magick_ai_core_smoke_run_id,
		'summary'    => 'This should be blocked by pending proposal quota.',
		'input'      => array(
			'title'   => 'Pending quota blocked draft ' . $magick_ai_core_smoke_run_id,
			'content' => '<p>Pending quota smoke.</p>',
			'dry_run' => true,
		),
		'preview'    => array(
			'dry_run' => true,
		),
		'caller'     => array(
			'source' => 'pending-quota-smoke:' . $magick_ai_core_smoke_run_id,
		),
	)
);
magick_ai_core_smoke_assert( 429 === (int) $quota_blocked['status'], 'app pending proposal quota blocks the twenty-first pending proposal' );
$quota_blocked_data = is_array( $quota_blocked['data'] ?? null ) ? $quota_blocked['data'] : array();
magick_ai_core_smoke_assert( 'magick_ai_core_pending_proposal_quota_exceeded' === (string) ( $quota_blocked_data['code'] ?? '' ), 'pending proposal quota returns stable error code' );

$app_audit_denied = magick_ai_core_smoke_rest_result_as_app( 'GET', '/magick-ai-core/v1/audit', $app_token, array( 'limit' => 5 ) );
magick_ai_core_smoke_assert( 403 === (int) $app_audit_denied['status'], 'app-authenticated audit read is denied without audit scope' );

$trusted_adapter_app = magick_ai_core_smoke_rest(
	'POST',
	'/magick-ai-core/v1/apps',
	array(
		'app_label'           => 'Trusted Adapter approve smoke ' . $magick_ai_core_smoke_run_id,
		'caller_type'         => 'trusted_adapter',
		'scopes'              => array( 'capabilities:read', 'proposals:create', 'proposals:read', 'proposals:approve', 'commit:preflight' ),
		'rate_limit'          => 20,
		'rate_window_seconds' => 3600,
	)
);
$trusted_adapter_token = (string) ( $trusted_adapter_app['token'] ?? '' );
$trusted_adapter_app_id = (string) ( $trusted_adapter_app['app_id'] ?? '' );
magick_ai_core_smoke_assert( '' !== $trusted_adapter_token && '' !== $trusted_adapter_app_id, 'trusted Adapter app key is created for approve-and-execute smoke' );

$trusted_created = magick_ai_core_smoke_rest_as_app(
	'POST',
	'/magick-ai-core/v1/proposals',
	$trusted_adapter_token,
	array(
		'ability_id' => 'magick-ai/create-draft',
		'title'      => 'Trusted Adapter approval proposal ' . $magick_ai_core_smoke_run_id,
		'summary'    => 'Created by trusted Adapter approval smoke test.',
		'input'      => array(
			'title'   => 'Trusted Adapter Draft ' . $magick_ai_core_smoke_run_id,
			'content' => '<p>Trusted Adapter content.</p>',
			'dry_run' => true,
			'commit'  => false,
		),
		'preview'    => array(
			'dry_run'          => true,
			'commit_execution' => false,
		),
		'caller'     => array(
			'source' => 'trusted-adapter-smoke:' . $magick_ai_core_smoke_run_id,
		),
	)
);
$trusted_proposal_id = (string) ( $trusted_created['proposal_id'] ?? '' );
magick_ai_core_smoke_assert( '' !== $trusted_proposal_id, 'trusted Adapter app creates proposal' );

$trusted_approved = magick_ai_core_smoke_rest_as_app(
	'POST',
	'/magick-ai-core/v1/proposals/' . rawurlencode( $trusted_proposal_id ) . '/approve',
	$trusted_adapter_token,
	array(
		'note' => 'Trusted Adapter approve-and-execute smoke approval.',
	)
);
magick_ai_core_smoke_assert( 'approved' === (string) ( $trusted_approved['status'] ?? '' ), 'trusted Adapter app approves proposal with approval scope' );

$trusted_preflight = magick_ai_core_smoke_rest_as_app( 'POST', '/magick-ai-core/v1/proposals/' . rawurlencode( $trusted_proposal_id ) . '/commit-preflight', $trusted_adapter_token );
magick_ai_core_smoke_assert( true === (bool) ( $trusted_preflight['approval_context']['approval_commit_authorized'] ?? false ), 'trusted Adapter app receives approval context after approval' );
magick_ai_core_smoke_assert( 'adapter_after_core_preflight' === (string) ( $trusted_preflight['execution_handoff']['executor'] ?? '' ), 'trusted Adapter preflight returns execution handoff' );
magick_ai_core_smoke_assert( 'magick-ai/create-draft' === (string) ( $trusted_preflight['execution_handoff']['ability_id'] ?? '' ), 'trusted Adapter execution handoff includes target ability id' );
magick_ai_core_smoke_assert( (string) ( $trusted_preflight['approval_context']['approved_input_hash'] ?? '' ) === (string) ( $trusted_preflight['execution_handoff']['approved_input_hash'] ?? '' ), 'trusted Adapter execution handoff carries approved input hash' );
magick_ai_core_smoke_assert( 'core-preflight-v1' === (string) ( $trusted_preflight['execution_handoff']['policy_version'] ?? '' ), 'trusted Adapter execution handoff carries policy version' );
magick_ai_core_smoke_assert( false === (bool) ( $trusted_preflight['execution_handoff']['core_proxy_execute'] ?? true ), 'trusted Adapter execution handoff keeps Core proxy execution disabled' );
magick_ai_core_smoke_assert( false === (bool) ( $trusted_preflight['execution_handoff']['commit_execution'] ?? true ), 'trusted Adapter execution handoff keeps Core final execution disabled' );

$rate_app = magick_ai_core_smoke_rest(
	'POST',
	'/magick-ai-core/v1/apps',
	array(
		'app_label'           => 'OpenClaw rate smoke ' . $magick_ai_core_smoke_run_id,
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

$stale = magick_ai_core_smoke_rest(
	'POST',
	'/magick-ai-core/v1/proposals',
	array(
		'ability_id' => 'magick-ai/create-draft',
		'title'      => 'Smoke stale proposal ' . $magick_ai_core_smoke_run_id,
		'summary'    => 'Created to verify automatic expiration.',
	)
);
$stale_id = (string) ( $stale['proposal_id'] ?? '' );
magick_ai_core_smoke_assert( '' !== $stale_id && 'pending' === (string) ( $stale['status'] ?? '' ), 'stale smoke proposal starts pending' );

$proposal_repository = \MagickAI\Core\Plugin::instance()->proposal_repository();
$proposal_service    = \MagickAI\Core\Plugin::instance()->proposal_service();
$stale_time          = gmdate( 'Y-m-d H:i:s', time() - \MagickAI\Core\Governance\Proposal_Service::PENDING_TTL_SECONDS - HOUR_IN_SECONDS );
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

$expired_detail = magick_ai_core_smoke_rest( 'GET', '/magick-ai-core/v1/proposals/' . rawurlencode( $stale_id ) );
magick_ai_core_smoke_assert( 'expired' === (string) ( $expired_detail['status'] ?? '' ), 'stale pending proposal expires before detail response' );

$archived = $proposal_service->archive( $stale_id, array( 'source' => 'smoke' ) );
magick_ai_core_smoke_assert( is_array( $archived ) && 'archived' === (string) ( $archived['status'] ?? '' ), 'expired proposal can be archived' );

$reopened = $proposal_service->reopen( $stale_id, array( 'source' => 'smoke' ) );
magick_ai_core_smoke_assert( is_array( $reopened ) && 'pending' === (string) ( $reopened['status'] ?? '' ), 'archived proposal can be reopened for review' );

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
		'title'      => 'Smoke rejection proposal ' . $magick_ai_core_smoke_run_id,
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

$taxonomy_proposal_audit = magick_ai_core_smoke_rest(
	'GET',
	'/magick-ai-core/v1/audit',
	array(
		'proposal_id' => $taxonomy_proposal_id,
		'limit'       => 20,
	)
);
$taxonomy_audit_items = (array) ( $taxonomy_proposal_audit['items'] ?? array() );
magick_ai_core_smoke_assert( count( $taxonomy_audit_items ) >= 3, 'taxonomy terms audit filter returns the proposal lifecycle events' );
$found_taxonomy_preflight = false;
foreach ( $taxonomy_audit_items as $item ) {
	magick_ai_core_smoke_assert( $taxonomy_proposal_id === (string) ( is_array( $item ) ? ( $item['proposal_id'] ?? '' ) : '' ), 'taxonomy audit filter returns only matching proposal events' );
	if ( is_array( $item ) && 'commit.preflighted' === (string) ( $item['event_name'] ?? '' ) ) {
		$found_taxonomy_preflight = 'magick-ai/set-post-terms' === (string) ( $item['metadata']['ability_id'] ?? '' );
	}
}
magick_ai_core_smoke_assert( $found_taxonomy_preflight, 'taxonomy terms audit correlates commit preflight with set-post-terms' );

$taxonomy_timeline_detail = magick_ai_core_smoke_rest( 'GET', '/magick-ai-core/v1/proposals/' . rawurlencode( $taxonomy_proposal_id ) );
$taxonomy_timeline_items  = (array) ( $taxonomy_timeline_detail['audit_timeline'] ?? array() );
magick_ai_core_smoke_assert( count( $taxonomy_timeline_items ) >= 4, 'taxonomy proposal detail includes audit timeline through preflight' );

$taxonomy_correlation_id = (string) ( $GLOBALS['magick_ai_core_smoke_preflight_correlations'][ $taxonomy_proposal_id ] ?? '' );
magick_ai_core_smoke_assert( '' !== $taxonomy_correlation_id, 'taxonomy terms preflight correlation id is available for audit filtering' );

$ability_audit = magick_ai_core_smoke_rest(
	'GET',
	'/magick-ai-core/v1/audit',
	array(
		'ability_id' => 'magick-ai/set-post-terms',
		'limit'      => 20,
	)
);
$ability_audit_items = (array) ( $ability_audit['items'] ?? array() );
magick_ai_core_smoke_assert( count( $ability_audit_items ) >= 1, 'audit endpoint filters by ability id' );
foreach ( $ability_audit_items as $item ) {
	magick_ai_core_smoke_assert( 'magick-ai/set-post-terms' === (string) ( is_array( $item ) ? ( $item['metadata']['ability_id'] ?? '' ) : '' ), 'ability audit filter returns only matching ability metadata' );
}

$correlation_audit = magick_ai_core_smoke_rest(
	'GET',
	'/magick-ai-core/v1/audit',
	array(
		'correlation_id' => $taxonomy_correlation_id,
		'limit'          => 20,
	)
);
$correlation_audit_items = (array) ( $correlation_audit['items'] ?? array() );
magick_ai_core_smoke_assert( count( $correlation_audit_items ) >= 1, 'audit endpoint filters by correlation id' );
foreach ( $correlation_audit_items as $item ) {
	magick_ai_core_smoke_assert( $taxonomy_correlation_id === (string) ( is_array( $item ) ? ( $item['metadata']['correlation_id'] ?? '' ) : '' ), 'correlation audit filter returns only matching correlation metadata' );
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

$trusted_proposal_audit = magick_ai_core_smoke_rest(
	'GET',
	'/magick-ai-core/v1/audit',
	array(
		'proposal_id' => $trusted_proposal_id,
		'limit'       => 20,
	)
);
$trusted_proposal_audit_items = (array) ( $trusted_proposal_audit['items'] ?? array() );
$found_trusted_approval       = false;
$found_trusted_preflight      = false;
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
}
magick_ai_core_smoke_assert( $found_trusted_approval, 'trusted Adapter approval audit stores app attribution and approve scope' );
magick_ai_core_smoke_assert( $found_trusted_preflight, 'trusted Adapter preflight audit stores app attribution and preflight scope' );

$app_id_audit = magick_ai_core_smoke_rest(
	'GET',
	'/magick-ai-core/v1/audit',
	array(
		'app_id' => $app_id,
		'limit'  => 20,
	)
);
$app_id_audit_items = (array) ( $app_id_audit['items'] ?? array() );
magick_ai_core_smoke_assert( count( $app_id_audit_items ) >= 1, 'audit endpoint filters by app id' );
foreach ( $app_id_audit_items as $item ) {
	$metadata = is_array( $item ) && is_array( $item['metadata'] ?? null ) ? $item['metadata'] : array();
	$auth     = is_array( $metadata['auth'] ?? null ) ? $metadata['auth'] : $metadata;
	magick_ai_core_smoke_assert( $app_id === (string) ( $auth['app_id'] ?? '' ), 'app audit filter returns only matching app metadata' );
}

$key_id_audit = magick_ai_core_smoke_rest(
	'GET',
	'/magick-ai-core/v1/audit',
	array(
		'key_id' => $key_id,
		'limit'  => 20,
	)
);
$key_id_audit_items = (array) ( $key_id_audit['items'] ?? array() );
magick_ai_core_smoke_assert( count( $key_id_audit_items ) >= 1, 'audit endpoint filters by key id' );
foreach ( $key_id_audit_items as $item ) {
	$metadata = is_array( $item ) && is_array( $item['metadata'] ?? null ) ? $item['metadata'] : array();
	$auth     = is_array( $metadata['auth'] ?? null ) ? $metadata['auth'] : $metadata;
	magick_ai_core_smoke_assert( $key_id === (string) ( $auth['key_id'] ?? '' ), 'key audit filter returns only matching key metadata' );
}

$caller_type_audit = magick_ai_core_smoke_rest(
	'GET',
	'/magick-ai-core/v1/audit',
	array(
		'caller_type' => 'mcp_adapter',
		'limit'       => 20,
	)
);
$caller_type_audit_items = (array) ( $caller_type_audit['items'] ?? array() );
magick_ai_core_smoke_assert( count( $caller_type_audit_items ) >= 1, 'audit endpoint filters by caller type' );
foreach ( $caller_type_audit_items as $item ) {
	$metadata = is_array( $item ) && is_array( $item['metadata'] ?? null ) ? $item['metadata'] : array();
	$auth     = is_array( $metadata['auth'] ?? null ) ? $metadata['auth'] : $metadata;
	magick_ai_core_smoke_assert( 'mcp_adapter' === (string) ( $auth['caller_type'] ?? '' ), 'caller type audit filter returns only matching caller metadata' );
}

$scope_denied_audit = magick_ai_core_smoke_rest(
	'GET',
	'/magick-ai-core/v1/audit',
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
magick_ai_core_smoke_assert( $found_denied_app_decision, 'app scope denial audit stores scope decision denied' );

$rate_limited_audit = magick_ai_core_smoke_rest(
	'GET',
	'/magick-ai-core/v1/audit',
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
magick_ai_core_smoke_assert( $found_rate_limited_decision, 'app rate-limit audit stores scope decision rate_limited' );

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

magick_ai_core_smoke_cleanup_fixtures();
magick_ai_core_smoke_assert( null === get_comment( (int) $pending_comment['comment_id'] ), 'pending comment fixture is deleted after smoke' );
magick_ai_core_smoke_assert( false === get_post_type( (int) $pending_comment['post_id'] ), 'comment parent post fixture is deleted after smoke' );
magick_ai_core_smoke_assert( false === get_post_type( (int) $taxonomy_fixture['post_id'] ), 'taxonomy post fixture is deleted after smoke' );
magick_ai_core_smoke_assert( 0 === (int) term_exists( (int) $taxonomy_fixture['current_term_id'], (string) $taxonomy_fixture['taxonomy'] ), 'taxonomy current term fixture is deleted after smoke' );
magick_ai_core_smoke_assert( 0 === (int) term_exists( (int) $taxonomy_fixture['candidate_term_id'], (string) $taxonomy_fixture['taxonomy'] ), 'taxonomy candidate term fixture is deleted after smoke' );
magick_ai_core_smoke_assert( false === get_post_type( (int) $plan_content_post_id ), 'plan bridge content post fixture is deleted after smoke' );
magick_ai_core_smoke_assert( false === get_post_type( (int) $cleanup_post_id ), 'plan bridge cleanup post fixture is deleted after smoke' );
magick_ai_core_smoke_assert( false === get_post_type( (int) $cleanup_second_post_id ), 'second plan bridge cleanup post fixture is deleted after smoke' );
magick_ai_core_smoke_assert( false === get_post_type( (int) $plan_attachment_id ), 'plan bridge media fixture attachment is deleted after smoke' );
if ( ! magick_ai_core_smoke_should_purge_governance_records() ) {
	$main_app_key_after = ( new \MagickAI\Core\Security\App_Key_Repository() )->find_by_key_id( $key_id );
	magick_ai_core_smoke_assert( is_array( $main_app_key_after ) && 'revoked' === (string) ( $main_app_key_after['status'] ?? '' ), 'smoke app key fixture is revoked after smoke' );
}

echo "magick-ai-core WordPress smoke: ok\n";
