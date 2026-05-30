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
 * Creates a local pending comment for comment moderation smoke coverage.
 *
 * @return array{comment_id:int,post_id:int,current_status:string}
 */
function magick_ai_core_smoke_create_pending_comment(): array {
	$post_id = wp_insert_post(
		array(
			'post_title'   => 'Core Governance Comment Smoke',
			'post_content' => 'Comment moderation smoke parent post.',
			'post_status'  => 'publish',
			'post_type'    => 'post',
		),
		true
	);
	magick_ai_core_smoke_assert( ! is_wp_error( $post_id ) && (int) $post_id > 0, 'comment smoke parent post is created' );

	$comment_id = wp_insert_comment(
		array(
			'comment_post_ID'      => (int) $post_id,
			'comment_author'       => 'Core Smoke Reviewer',
			'comment_author_email' => 'core-smoke@example.test',
			'comment_content'      => 'Pending comment for Core governance smoke.',
			'comment_approved'     => '0',
		)
	);
	magick_ai_core_smoke_assert( (int) $comment_id > 0, 'pending comment is created for comment approval governance' );

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
		return (int) ( $existing['term_id'] ?? 0 );
	}
	if ( is_int( $existing ) ) {
		return $existing;
	}
	if ( is_string( $existing ) && is_numeric( $existing ) ) {
		return (int) $existing;
	}

	$created = wp_insert_term( $name, $taxonomy );
	if ( is_wp_error( $created ) ) {
		return 0;
	}

	return (int) ( is_array( $created ) ? ( $created['term_id'] ?? 0 ) : 0 );
}

/**
 * Creates a local post and existing terms for taxonomy governance smoke coverage.
 *
 * @return array{post_id:int,taxonomy:string,current_term_id:int,candidate_term_id:int,current_terms:array<int,int>}
 */
function magick_ai_core_smoke_create_taxonomy_terms_fixture(): array {
	$taxonomy = 'post_tag';
	$post_id  = wp_insert_post(
		array(
			'post_title'   => 'Taxonomy terms smoke parent post',
			'post_content' => 'Taxonomy terms governance smoke content.',
			'post_status'  => 'draft',
			'post_type'    => 'post',
		),
		true
	);
	magick_ai_core_smoke_assert( ! is_wp_error( $post_id ) && (int) $post_id > 0, 'taxonomy smoke parent post is created' );

	$current_term_id   = magick_ai_core_smoke_term_id( 'Core Smoke Current Topic', $taxonomy );
	$candidate_term_id = magick_ai_core_smoke_term_id( 'Core Smoke Candidate Topic', $taxonomy );
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
	$GLOBALS['magick_ai_core_smoke_preflight_correlations'][ $proposal_id ] = $correlation_id;

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

$items_by_id                     = array();
$all_have_governance_mode        = true;
$all_have_execution_surface      = true;
$all_disable_core_proxy_execute  = true;
$all_disable_core_commit_execute = true;
foreach ( $items as $item ) {
	if ( is_array( $item ) && '' !== (string) ( $item['ability_id'] ?? '' ) ) {
		$all_have_governance_mode        = $all_have_governance_mode && array_key_exists( 'governance_mode', $item );
		$all_have_execution_surface      = $all_have_execution_surface && array_key_exists( 'execution_surface', $item );
		$all_disable_core_proxy_execute  = $all_disable_core_proxy_execute && false === (bool) ( $item['core_proxy_execute'] ?? true );
		$all_disable_core_commit_execute = $all_disable_core_commit_execute && false === (bool) ( $item['commit_execution'] ?? true );
		$items_by_id[ (string) $item['ability_id'] ] = $item;
	}
}
magick_ai_core_smoke_assert( $all_have_governance_mode, 'all capability rows expose governance mode guidance' );
magick_ai_core_smoke_assert( $all_have_execution_surface, 'all capability rows expose execution surface guidance' );
magick_ai_core_smoke_assert( $all_disable_core_proxy_execute, 'all capability rows keep Core proxy execution disabled' );
magick_ai_core_smoke_assert( $all_disable_core_commit_execute, 'all capability rows keep Core commit execution disabled' );

magick_ai_core_smoke_assert( isset( $items_by_id['magick-ai/site-info'] ), 'site-info read ability is discoverable for direct read guidance' );
magick_ai_core_smoke_assert_capability_guidance( $items_by_id['magick-ai/site-info'], 'direct_read', 'wp_abilities_rest', 'site-info uses direct read execution guidance' );

magick_ai_core_smoke_assert_create_draft_contract( $items_by_id );
magick_ai_core_smoke_assert_seo_meta_contract( $items_by_id );
magick_ai_core_smoke_assert_comment_approval_contract( $items_by_id );
magick_ai_core_smoke_assert_taxonomy_terms_contract( $items_by_id );

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
		'seo_title'       => 'Smoke SEO Title',
		'seo_description' => 'Smoke SEO description.',
		'dry_run'         => true,
		'commit'          => false,
	),
	array(
		'field_patch'      => array(
			'seo_title'       => 'Smoke SEO Title',
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
magick_ai_core_smoke_assert( 'allowed' === (string) ( $app_created['caller']['auth']['scope_decision'] ?? '' ), 'app-authenticated proposal stores allowed scope decision' );

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

echo "magick-ai-core WordPress smoke: ok\n";
