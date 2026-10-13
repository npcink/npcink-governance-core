<?php
/** Fixture cleanup test module. @package NpcinkGovernanceCore */

/**
 * Smoke assertion helper.
 *
 * @param bool   $condition Condition.
 * @param string $message Failure message.
 * @return void
 */
function npcink_governance_core_smoke_assert( bool $condition, string $message ): void {
	if ( ! $condition ) {
		fwrite( STDERR, '[fail] ' . $message . "\n" );
		exit( 1 );
	}

	echo '[ok] ' . $message . "\n";
}

/**
 * Finds a post by exact title without using deprecated WordPress helpers.
 *
 * @param string $title Post title.
 * @param string $post_type Post type.
 * @return int
 */
function npcink_governance_core_smoke_find_post_id_by_title( string $title, string $post_type ): int {
	$posts = get_posts(
		array(
			'title'                  => $title,
			'post_type'              => $post_type,
			'post_status'            => 'any',
			'posts_per_page'         => 1,
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);

	return (int) ( $posts[0] ?? 0 );
}

/**
 * Registers a post fixture for cleanup even when the smoke test fails.
 *
 * @param int $post_id Post id.
 * @return void
 */
function npcink_governance_core_smoke_register_post_fixture( int $post_id ): void {
	global $npcink_governance_core_smoke_post_fixture_ids;

	if ( $post_id <= 0 ) {
		return;
	}

	$npcink_governance_core_smoke_post_fixture_ids[ $post_id ] = true;
}

/**
 * Registers a comment fixture for cleanup even when the smoke test fails.
 *
 * @param int $comment_id Comment id.
 * @return void
 */
function npcink_governance_core_smoke_register_comment_fixture( int $comment_id ): void {
	global $npcink_governance_core_smoke_comment_fixture_ids;

	if ( $comment_id <= 0 ) {
		return;
	}

	$npcink_governance_core_smoke_comment_fixture_ids[ $comment_id ] = true;
}

/**
 * Registers a media fixture for cleanup even when the smoke test fails.
 *
 * @param int $attachment_id Attachment post id.
 * @return void
 */
function npcink_governance_core_smoke_register_attachment_fixture( int $attachment_id ): void {
	global $npcink_governance_core_smoke_attachment_fixture_ids;

	if ( $attachment_id <= 0 ) {
		return;
	}

	$npcink_governance_core_smoke_attachment_fixture_ids[ $attachment_id ] = true;
}

/**
 * Creates a real local media attachment fixture with an attached uploads file.
 *
 * @param string $title Attachment title.
 * @return int
 */
function npcink_governance_core_smoke_create_media_attachment_fixture( string $title ): int {
	global $npcink_governance_core_smoke_run_id;

	$uploads = wp_upload_dir();
	npcink_governance_core_smoke_assert( empty( $uploads['error'] ), 'smoke media fixture uploads directory is available' );

	$directory = trailingslashit( (string) $uploads['path'] );
	if ( ! is_dir( $directory ) ) {
		wp_mkdir_p( $directory );
	}
	npcink_governance_core_smoke_assert( is_dir( $directory ) && is_writable( $directory ), 'smoke media fixture uploads directory is writable' );

	$file_name = sanitize_file_name( 'core-smoke-media-' . $npcink_governance_core_smoke_run_id . '.png' );
	$file_path = $directory . $file_name;
	$image_bytes = base64_decode( 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII=', true );
	npcink_governance_core_smoke_assert( is_string( $image_bytes ) && '' !== $image_bytes, 'smoke media fixture image bytes decode' );
	npcink_governance_core_smoke_assert( false !== file_put_contents( $file_path, $image_bytes ), 'smoke media fixture file is written' );

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
	npcink_governance_core_smoke_assert( ! is_wp_error( $attachment_id ) && (int) $attachment_id > 0, 'smoke media fixture attachment post is created' );
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
	npcink_governance_core_smoke_register_attachment_fixture( (int) $attachment_id );

	return (int) $attachment_id;
}

/**
 * Registers a taxonomy term fixture for cleanup even when the smoke test fails.
 *
 * @param int    $term_id Term id.
 * @param string $taxonomy Taxonomy id.
 * @return void
 */
function npcink_governance_core_smoke_register_term_fixture( int $term_id, string $taxonomy ): void {
	global $npcink_governance_core_smoke_term_fixtures;

	if ( $term_id <= 0 || '' === $taxonomy ) {
		return;
	}

	$npcink_governance_core_smoke_term_fixtures[ $taxonomy . ':' . $term_id ] = array(
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
function npcink_governance_core_smoke_register_app_key_fixture( string $key_id ): void {
	global $npcink_governance_core_smoke_app_key_fixture_ids;

	$key_id = trim( $key_id );
	if ( '' === $key_id ) {
		return;
	}

	$npcink_governance_core_smoke_app_key_fixture_ids[ $key_id ] = true;
}

/**
 * Registers an app fixture for optional purge.
 *
 * @param string $app_id App id.
 * @return void
 */
function npcink_governance_core_smoke_register_app_fixture( string $app_id ): void {
	global $npcink_governance_core_smoke_app_fixture_ids;

	$app_id = trim( $app_id );
	if ( '' === $app_id ) {
		return;
	}

	$npcink_governance_core_smoke_app_fixture_ids[ $app_id ] = true;
}

/**
 * Registers a proposal fixture for optional purge.
 *
 * @param string $proposal_id Proposal id.
 * @return void
 */
function npcink_governance_core_smoke_register_proposal_fixture( string $proposal_id ): void {
	global $npcink_governance_core_smoke_proposal_fixture_ids;

	$proposal_id = trim( $proposal_id );
	if ( '' === $proposal_id ) {
		return;
	}

	$npcink_governance_core_smoke_proposal_fixture_ids[ $proposal_id ] = true;
}

/**
 * Registers a sensitive read request fixture for optional purge.
 *
 * @param string $request_id Request id.
 * @return void
 */
function npcink_governance_core_smoke_register_read_request_fixture( string $request_id ): void {
	global $npcink_governance_core_smoke_read_request_fixture_ids;

	$request_id = trim( $request_id );
	if ( '' === $request_id ) {
		return;
	}

	$npcink_governance_core_smoke_read_request_fixture_ids[ $request_id ] = true;
}

/**
 * Tracks governance fixtures created by REST calls.
 *
 * @param string $method HTTP method.
 * @param string $route REST route.
 * @param mixed  $data Response data.
 * @return void
 */
function npcink_governance_core_smoke_track_rest_fixture( string $method, string $route, $data ): void {
	if ( 'POST' !== strtoupper( $method ) || ! is_array( $data ) ) {
		return;
	}

	if ( '/npcink-governance-core/v1/apps' === $route ) {
		npcink_governance_core_smoke_register_app_fixture( (string) ( $data['app_id'] ?? '' ) );
		npcink_governance_core_smoke_register_app_key_fixture( (string) ( $data['key_id'] ?? '' ) );
		return;
	}

	if ( '/npcink-governance-core/v1/proposals' === $route ) {
		npcink_governance_core_smoke_register_proposal_fixture( (string) ( $data['proposal_id'] ?? '' ) );
		return;
	}

	if ( '/npcink-governance-core/v1/proposals/from-plan' === $route ) {
		foreach ( (array) ( $data['proposals'] ?? array() ) as $proposal ) {
			if ( is_array( $proposal ) ) {
				npcink_governance_core_smoke_register_proposal_fixture( (string) ( $proposal['proposal_id'] ?? '' ) );
			}
		}
		return;
	}

	if ( '/npcink-governance-core/v1/read-requests' === $route ) {
		npcink_governance_core_smoke_register_read_request_fixture( (string) ( $data['request_id'] ?? '' ) );
	}
}

/**
 * Whether smoke should purge tracked governance rows instead of only revoking keys.
 *
 * @return bool
 */
function npcink_governance_core_smoke_should_purge_governance_records(): bool {
	$value = getenv( 'NPCINK_GOVERNANCE_CORE_SMOKE_PURGE' );
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
function npcink_governance_core_smoke_purge_governance_records(): void {
	global $wpdb, $npcink_governance_core_smoke_app_fixture_ids, $npcink_governance_core_smoke_app_key_fixture_ids, $npcink_governance_core_smoke_proposal_fixture_ids, $npcink_governance_core_smoke_read_request_fixture_ids;

	if ( ! npcink_governance_core_smoke_should_purge_governance_records() ) {
		return;
	}

	$audit_table     = $wpdb->prefix . 'npcink_governance_core_audit_log';
	$app_table       = $wpdb->prefix . 'npcink_governance_core_app_keys';
	$rate_table      = $wpdb->prefix . 'npcink_governance_core_app_rate_limits';
	$app_ids         = array_keys( (array) $npcink_governance_core_smoke_app_fixture_ids );
	$key_ids         = array_keys( (array) $npcink_governance_core_smoke_app_key_fixture_ids );

	npcink_governance_core_smoke_delete_governance_rows(
		array_keys( (array) $npcink_governance_core_smoke_proposal_fixture_ids ),
		array_keys( (array) $npcink_governance_core_smoke_read_request_fixture_ids )
	);

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
 * Deletes governance rows for the given proposal and read request ids.
 *
 * Shared by the per-run cleanup and the explicit purge path so audit and
 * table conventions stay in one place.
 *
 * @param array<int,string> $proposal_ids Proposal ids.
 * @param array<int,string> $request_ids Sensitive read request ids.
 * @return void
 */
function npcink_governance_core_smoke_delete_governance_rows( array $proposal_ids, array $request_ids ): void {
	global $wpdb;

	$audit_table        = $wpdb->prefix . 'npcink_governance_core_audit_log';
	$proposal_table     = $wpdb->prefix . 'npcink_governance_core_proposals';
	$read_request_table = $wpdb->prefix . 'npcink_governance_core_read_requests';

	foreach ( $proposal_ids as $proposal_id ) {
		$wpdb->delete( $audit_table, array( 'proposal_id' => sanitize_text_field( (string) $proposal_id ) ), array( '%s' ) );
		$wpdb->delete( $proposal_table, array( 'proposal_id' => sanitize_text_field( (string) $proposal_id ) ), array( '%s' ) );
	}

	foreach ( $request_ids as $request_id ) {
		$wpdb->delete( $audit_table, array( 'proposal_id' => sanitize_text_field( (string) $request_id ) ), array( '%s' ) );
		$wpdb->delete( $read_request_table, array( 'request_id' => sanitize_text_field( (string) $request_id ) ), array( '%s' ) );
	}
}

/**
 * Deletes tracked governance rows created by this smoke run.
 *
 * Every run removes the proposals and sensitive read requests it created,
 * together with their audit rows, so repeated local smoke runs do not
 * accumulate pending fixtures on the site. App keys are revoked but kept;
 * the broader app/rate/audit wipe stays behind the explicit purge env var.
 *
 * @return void
 */
function npcink_governance_core_smoke_delete_tracked_governance_rows(): void {
	global $npcink_governance_core_smoke_proposal_fixture_ids, $npcink_governance_core_smoke_read_request_fixture_ids;

	npcink_governance_core_smoke_delete_governance_rows(
		array_keys( (array) $npcink_governance_core_smoke_proposal_fixture_ids ),
		array_keys( (array) $npcink_governance_core_smoke_read_request_fixture_ids )
	);
}

/**
 * Deletes registered smoke fixtures.
 *
 * @return void
 */
function npcink_governance_core_smoke_cleanup_fixtures(): void {
	global $npcink_governance_core_smoke_post_fixture_ids, $npcink_governance_core_smoke_comment_fixture_ids, $npcink_governance_core_smoke_attachment_fixture_ids, $npcink_governance_core_smoke_term_fixtures, $npcink_governance_core_smoke_app_key_fixture_ids, $npcink_governance_core_smoke_cleanup_completed, $npcink_governance_core_smoke_initial_policy_mode, $npcink_governance_core_smoke_initial_history_retention;

	if ( $npcink_governance_core_smoke_cleanup_completed ) {
		return;
	}

	$npcink_governance_core_smoke_cleanup_completed = true;
	if ( class_exists( \Npcink\GovernanceCore\Governance\Approval_Policy_Evaluator::class ) ) {
		update_option( \Npcink\GovernanceCore\Governance\Approval_Policy_Evaluator::OPTION_POLICY_MODE, \Npcink\GovernanceCore\Governance\Approval_Policy_Evaluator::sanitize_policy_mode( (string) $npcink_governance_core_smoke_initial_policy_mode ), false );
	}
	if ( class_exists( \Npcink\GovernanceCore\Governance\History_Cleanup_Service::class ) ) {
		update_option( \Npcink\GovernanceCore\Governance\History_Cleanup_Service::OPTION_HISTORY_RETENTION_DAYS, \Npcink\GovernanceCore\Governance\History_Cleanup_Service::sanitize_retention_days( $npcink_governance_core_smoke_initial_history_retention ), false );
	}

	npcink_governance_core_smoke_delete_tracked_governance_rows();

	foreach ( array_keys( (array) $npcink_governance_core_smoke_app_key_fixture_ids ) as $key_id ) {
		$app_keys = new \Npcink\GovernanceCore\Security\App_Key_Repository();
		$revoked  = $app_keys->revoke_by_key_id( (string) $key_id );
		$app      = $app_keys->find_by_key_id( (string) $key_id );
		if ( ! $revoked && is_array( $app ) && 'revoked' !== (string) ( $app['status'] ?? '' ) ) {
			fwrite( STDERR, '[warn] failed to revoke smoke app key fixture ' . (string) $key_id . "\n" );
		}
	}

	foreach ( array_keys( (array) $npcink_governance_core_smoke_comment_fixture_ids ) as $comment_id ) {
		$comment_id = (int) $comment_id;
		if ( $comment_id <= 0 || ! get_comment( $comment_id ) ) {
			continue;
		}

		if ( false === wp_delete_comment( $comment_id, true ) ) {
			fwrite( STDERR, '[warn] failed to delete smoke comment fixture ' . $comment_id . "\n" );
		}
	}

	foreach ( array_keys( (array) $npcink_governance_core_smoke_attachment_fixture_ids ) as $attachment_id ) {
		$attachment_id = (int) $attachment_id;
		if ( $attachment_id <= 0 || 'attachment' !== get_post_type( $attachment_id ) ) {
			continue;
		}

		$deleted = wp_delete_attachment( $attachment_id, true );
		if ( false === $deleted ) {
			fwrite( STDERR, '[warn] failed to delete smoke attachment fixture ' . $attachment_id . "\n" );
		}
	}

	foreach ( array_keys( (array) $npcink_governance_core_smoke_post_fixture_ids ) as $post_id ) {
		$post_id = (int) $post_id;
		if ( $post_id <= 0 || false === get_post_type( $post_id ) ) {
			continue;
		}

		if ( false === wp_delete_post( $post_id, true ) ) {
			fwrite( STDERR, '[warn] failed to delete smoke post fixture ' . $post_id . "\n" );
		}
	}

	foreach ( (array) $npcink_governance_core_smoke_term_fixtures as $term_fixture ) {
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

	npcink_governance_core_smoke_purge_governance_records();
}

register_shutdown_function( 'npcink_governance_core_smoke_cleanup_fixtures' );

