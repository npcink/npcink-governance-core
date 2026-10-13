<?php
/** Governance helpers test module. @package NpcinkGovernanceCore */

/**
 * Creates a local pending comment for comment moderation smoke coverage.
 *
 * @return array{comment_id:int,post_id:int,current_status:string}
 */
function npcink_governance_core_smoke_create_pending_comment(): array {
	global $npcink_governance_core_smoke_run_id;

	$post_id = wp_insert_post(
		array(
			'post_title'   => 'Core Governance Comment Smoke ' . $npcink_governance_core_smoke_run_id,
			'post_content' => 'Comment moderation smoke parent post.',
			'post_status'  => 'publish',
			'post_type'    => 'post',
		),
		true
	);
	npcink_governance_core_smoke_assert( ! is_wp_error( $post_id ) && (int) $post_id > 0, 'comment smoke parent post is created' );
	npcink_governance_core_smoke_register_post_fixture( (int) $post_id );

	$comment_id = wp_insert_comment(
		array(
			'comment_post_ID'      => (int) $post_id,
			'comment_author'       => 'Core Smoke Reviewer',
			'comment_author_email' => 'core-smoke@example.test',
			'comment_content'      => 'Pending comment for Core governance smoke ' . $npcink_governance_core_smoke_run_id . '.',
			'comment_approved'     => '0',
		)
	);
	npcink_governance_core_smoke_assert( (int) $comment_id > 0, 'pending comment is created for comment approval governance' );
	npcink_governance_core_smoke_register_comment_fixture( (int) $comment_id );

	$comment = get_comment( (int) $comment_id );
	npcink_governance_core_smoke_assert( $comment instanceof WP_Comment, 'pending comment can be loaded for preview' );

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
function npcink_governance_core_smoke_term_id( string $name, string $taxonomy ): int {
	$existing = term_exists( $name, $taxonomy );
	if ( is_array( $existing ) ) {
		$term_id = (int) ( $existing['term_id'] ?? 0 );
		npcink_governance_core_smoke_register_term_fixture( $term_id, $taxonomy );
		return $term_id;
	}
	if ( is_int( $existing ) ) {
		npcink_governance_core_smoke_register_term_fixture( $existing, $taxonomy );
		return $existing;
	}
	if ( is_string( $existing ) && is_numeric( $existing ) ) {
		$term_id = (int) $existing;
		npcink_governance_core_smoke_register_term_fixture( $term_id, $taxonomy );
		return $term_id;
	}

	$created = wp_insert_term( $name, $taxonomy );
	if ( is_wp_error( $created ) ) {
		return 0;
	}

	$term_id = (int) ( is_array( $created ) ? ( $created['term_id'] ?? 0 ) : 0 );
	npcink_governance_core_smoke_register_term_fixture( $term_id, $taxonomy );

	return $term_id;
}

/**
 * Creates a local post and existing terms for taxonomy governance smoke coverage.
 *
 * @return array{post_id:int,taxonomy:string,current_term_id:int,candidate_term_id:int,current_terms:array<int,int>}
 */
function npcink_governance_core_smoke_create_taxonomy_terms_fixture(): array {
	global $npcink_governance_core_smoke_run_id;

	$taxonomy = 'post_tag';
	$post_id  = wp_insert_post(
		array(
			'post_title'   => 'Taxonomy terms smoke parent post ' . $npcink_governance_core_smoke_run_id,
			'post_content' => 'Taxonomy terms governance smoke content.',
			'post_status'  => 'draft',
			'post_type'    => 'post',
		),
		true
	);
	npcink_governance_core_smoke_assert( ! is_wp_error( $post_id ) && (int) $post_id > 0, 'taxonomy smoke parent post is created' );
	npcink_governance_core_smoke_register_post_fixture( (int) $post_id );

	$current_term_id   = npcink_governance_core_smoke_term_id( 'Core Smoke Current Topic ' . $npcink_governance_core_smoke_run_id, $taxonomy );
	$candidate_term_id = npcink_governance_core_smoke_term_id( 'Core Smoke Candidate Topic ' . $npcink_governance_core_smoke_run_id, $taxonomy );
	npcink_governance_core_smoke_assert( $current_term_id > 0 && $candidate_term_id > 0, 'taxonomy smoke existing terms are available' );

	$set_terms = wp_set_post_terms( (int) $post_id, array( $current_term_id ), $taxonomy, false );
	npcink_governance_core_smoke_assert( ! is_wp_error( $set_terms ), 'taxonomy smoke current term is assigned before Core governance' );

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
function npcink_governance_core_smoke_post_term_ids( int $post_id, string $taxonomy ): array {
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
function npcink_governance_core_smoke_run_taxonomy_terms_preview( array $fixture ): array {
	wp_set_current_user( 1 );

	$request = new WP_REST_Request( 'GET', '/wp-abilities/v1/abilities/npcink-abilities-toolkit/propose-post-taxonomy-terms/run' );
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
	npcink_governance_core_smoke_assert( 200 === (int) $response->get_status(), 'taxonomy terms preview helper runs through WordPress Abilities API' );
	$data = $response->get_data();
	npcink_governance_core_smoke_assert( is_array( $data ) && true === (bool) ( $data['success'] ?? false ), 'taxonomy terms preview helper returns a success envelope' );
	npcink_governance_core_smoke_assert( 'npcink-abilities-toolkit/set-post-terms' === (string) ( $data['data']['proposal']['target_ability_id'] ?? '' ), 'taxonomy terms preview helper targets set-post-terms' );
	npcink_governance_core_smoke_assert( false === ( $data['data']['proposal']['commit_execution'] ?? null ), 'taxonomy terms preview helper does not execute commits' );

	return $data;
}

/**
 * Runs a read-only planning ability through WordPress Abilities API.
 *
 * @param string              $ability_id Planning ability id.
 * @param array<string,mixed> $input Ability input.
 * @return array<string,mixed>
 */
function npcink_governance_core_smoke_run_plan_ability( string $ability_id, array $input ): array {
	wp_set_current_user( 1 );

	$request = new WP_REST_Request( 'GET', '/wp-abilities/v1/abilities/' . $ability_id . '/run' );
	$request->set_query_params(
		array(
			'input' => $input,
		)
	);

	$response = rest_do_request( $request );
	npcink_governance_core_smoke_assert( 200 === (int) $response->get_status(), $ability_id . ' planning ability run returns 200' );
	$data = $response->get_data();
	npcink_governance_core_smoke_assert( is_array( $data ) && true === (bool) ( $data['success'] ?? false ), $ability_id . ' returns a success envelope' );
	npcink_governance_core_smoke_assert( true === (bool) ( $data['data']['requires_approval'] ?? false ), $ability_id . ' plan requires approval' );
	npcink_governance_core_smoke_assert( false === (bool) ( $data['data']['commit_execution'] ?? true ), $ability_id . ' plan does not execute commits' );
	npcink_governance_core_smoke_assert( true === (bool) ( $data['data']['dry_run'] ?? false ), $ability_id . ' plan remains dry-run only' );

	return $data;
}

/**
 * Runs a target WordPress ability through the external ability runtime.
 *
 * This simulates the Adapter/host execution step after Core commit preflight.
 * Core is not the executor; the request goes to the WordPress Abilities API.
 *
 * @param string              $ability_id Ability id.
 * @param array<string,mixed> $input Ability input.
 * @return array<string,mixed>
 */
function npcink_governance_core_smoke_run_external_ability_dry_run( string $ability_id, array $input ): array {
	wp_set_current_user( 1 );

	$request = new WP_REST_Request( 'POST', '/wp-abilities/v1/abilities/' . $ability_id . '/run' );
	$request->set_header( 'Content-Type', 'application/json' );
	$request->set_body(
		wp_json_encode(
			array(
				'input' => $input,
			)
		)
	);

	$response = rest_do_request( $request );
	$status   = (int) $response->get_status();
	$data     = $response->get_data();
	npcink_governance_core_smoke_assert( $status >= 200 && $status < 300, $ability_id . ' external WordPress Abilities API dry-run returns HTTP ' . $status );
	npcink_governance_core_smoke_assert( is_array( $data ), $ability_id . ' external WordPress Abilities API dry-run returns structured data' );

	if ( true === (bool) ( $data['success'] ?? false ) && is_array( $data['data'] ?? null ) ) {
		return $data['data'];
	}

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
function npcink_governance_core_smoke_create_proposals_from_plan( string $ability_id, array $plan, array $plan_input ): array {
	global $npcink_governance_core_smoke_run_id;

	return npcink_governance_core_smoke_rest(
		'POST',
		'/npcink-governance-core/v1/proposals/from-plan',
		array(
			'plan_ability_id' => $ability_id,
			'plan'            => $plan,
			'plan_input'      => $plan_input,
			'caller'          => array(
				'source' => 'tests/smoke-wp.php:' . $npcink_governance_core_smoke_run_id,
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
function npcink_governance_core_smoke_create_proposals_from_plan_as_app( string $ability_id, array $plan, array $plan_input, string $token ): array {
	global $npcink_governance_core_smoke_run_id;

	return npcink_governance_core_smoke_rest_as_app(
		'POST',
		'/npcink-governance-core/v1/proposals/from-plan',
		$token,
		array(
			'plan_ability_id' => $ability_id,
			'plan'            => $plan,
			'plan_input'      => $plan_input,
			'caller'          => array(
				'source' => 'tests/smoke-wp.php-app:' . $npcink_governance_core_smoke_run_id,
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
function npcink_governance_core_smoke_assert_plan_proposal_shape( array $proposal, string $target_ability_id, bool $proposal_ready ): void {
	$preview = is_array( $proposal['preview'] ?? null ) ? $proposal['preview'] : array();
	$input   = is_array( $proposal['input'] ?? null ) ? $proposal['input'] : array();

	npcink_governance_core_smoke_assert( 'pending' === (string) ( $proposal['status'] ?? '' ), $target_ability_id . ' plan proposal starts pending approval' );
	npcink_governance_core_smoke_assert( $target_ability_id === (string) ( $proposal['ability_id'] ?? '' ), $target_ability_id . ' plan proposal stores target ability' );
	npcink_governance_core_smoke_assert( $target_ability_id === (string) ( $preview['target_ability_id'] ?? '' ), $target_ability_id . ' preview stores target ability' );
	npcink_governance_core_smoke_assert( true === (bool) ( $input['dry_run'] ?? false ), $target_ability_id . ' proposal input keeps dry_run=true' );
	npcink_governance_core_smoke_assert( false === (bool) ( $input['commit'] ?? true ), $target_ability_id . ' proposal input keeps commit=false' );
	npcink_governance_core_smoke_assert( true === (bool) ( $preview['requires_approval'] ?? false ), $target_ability_id . ' preview records approval requirement' );
	npcink_governance_core_smoke_assert( false === (bool) ( $preview['commit_execution'] ?? true ), $target_ability_id . ' preview keeps commit_execution=false' );
	npcink_governance_core_smoke_assert( $proposal_ready === (bool) ( $preview['proposal_ready'] ?? false ), $target_ability_id . ' preview records proposal readiness' );
	npcink_governance_core_smoke_assert( is_array( $preview['risk'] ?? null ) && '' !== (string) ( $preview['risk']['level'] ?? '' ), $target_ability_id . ' preview records risk' );
	npcink_governance_core_smoke_assert( array_key_exists( 'required_scopes', $preview ), $target_ability_id . ' preview records required scopes' );
	npcink_governance_core_smoke_assert( 'core_proposal_required' === (string) ( $preview['operation_classification']['classification'] ?? '' ), $target_ability_id . ' plan proposal stores Core proposal classification evidence' );
	npcink_governance_core_smoke_assert( 'core_proposal' === (string) ( $preview['operation_classification']['intake_path'] ?? '' ), $target_ability_id . ' plan proposal records Core proposal intake path' );
}

/**
 * Approves and preflights a plan-generated proposal.
 *
 * @param string $proposal_id Proposal id.
 * @return array<string,mixed>
 */
function npcink_governance_core_smoke_approve_and_preflight_plan_proposal( string $proposal_id ): array {
	$approved = npcink_governance_core_smoke_rest(
		'POST',
		'/npcink-governance-core/v1/proposals/' . rawurlencode( $proposal_id ) . '/approve',
		array(
			'note' => 'Plan bridge smoke approval.',
		)
	);
	npcink_governance_core_smoke_assert( 'approved' === (string) ( $approved['status'] ?? '' ), 'plan-generated proposal is approved through Core REST' );

	$preflight = npcink_governance_core_smoke_rest( 'POST', '/npcink-governance-core/v1/proposals/' . rawurlencode( $proposal_id ) . '/commit-preflight' );
	npcink_governance_core_smoke_assert( false === (bool) ( $preflight['commit_execution'] ?? true ), 'plan-generated proposal preflight keeps Core execution disabled' );
	npcink_governance_core_smoke_assert( true === (bool) ( $preflight['proposal_item_preflight']['executable'] ?? false ), 'plan-generated proposal preflight marks ready item executable' );
	npcink_governance_core_smoke_assert( '' !== (string) ( $preflight['correlation_id'] ?? '' ), 'plan-generated proposal preflight returns correlation id' );

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
function npcink_governance_core_smoke_run_governance_proposal( string $ability_id, array $items_by_id, string $title, array $input, array $preview ): string {
	global $npcink_governance_core_smoke_run_id;

	npcink_governance_core_smoke_assert( isset( $items_by_id[ $ability_id ] ), $ability_id . ' is discoverable for proposal governance' );
	npcink_governance_core_smoke_assert( 'read' !== (string) ( $items_by_id[ $ability_id ]['risk_level'] ?? 'read' ), $ability_id . ' is write-like for proposal governance' );
	npcink_governance_core_smoke_assert( true === (bool) ( $items_by_id[ $ability_id ]['requires_approval'] ?? false ), $ability_id . ' requires approval for proposal governance' );

	$created = npcink_governance_core_smoke_rest(
		'POST',
		'/npcink-governance-core/v1/proposals',
		array(
			'ability_id' => $ability_id,
			'title'      => $title . ' ' . $npcink_governance_core_smoke_run_id,
			'summary'    => 'Created by real WordPress smoke test.',
			'input'      => $input,
			'preview'    => $preview,
			'caller'     => array(
				'source' => 'tests/smoke-wp.php:' . $npcink_governance_core_smoke_run_id,
			),
		)
	);

	$proposal_id = (string) ( $created['proposal_id'] ?? '' );
	npcink_governance_core_smoke_assert( '' !== $proposal_id && 'pending' === (string) ( $created['status'] ?? '' ), $ability_id . ' proposal created in pending status' );
	npcink_governance_core_smoke_assert( $ability_id === (string) ( $created['ability_id'] ?? '' ), $ability_id . ' proposal stores the real ability id' );
	npcink_governance_core_smoke_assert( 'core_proposal_required' === (string) ( $created['preview']['operation_classification']['classification'] ?? '' ), $ability_id . ' proposal stores Core proposal classification evidence' );
	npcink_governance_core_smoke_assert( 'core_proposal' === (string) ( $created['preview']['operation_classification']['intake_path'] ?? '' ), $ability_id . ' proposal records Core proposal intake path' );

	$detail = npcink_governance_core_smoke_rest( 'GET', '/npcink-governance-core/v1/proposals/' . rawurlencode( $proposal_id ) );
	npcink_governance_core_smoke_assert( $proposal_id === (string) ( $detail['proposal_id'] ?? '' ), $ability_id . ' proposal detail endpoint returns created proposal' );
	$detail_timeline = (array) ( $detail['audit_timeline'] ?? array() );
	npcink_governance_core_smoke_assert( count( $detail_timeline ) >= 1, $ability_id . ' proposal detail includes audit timeline' );
	if ( 'npcink-abilities-toolkit/create-draft' === $ability_id && 'html' === (string) ( $input['content_format'] ?? '' ) ) {
		npcink_governance_core_smoke_assert( false !== strpos( (string) ( $detail['input']['content'] ?? '' ), '<p>' ), 'create-draft proposal preserves safe HTML content for review and execution handoff' );
		npcink_governance_core_smoke_assert( false === strpos( (string) ( $detail['input']['content'] ?? '' ), '<script' ), 'create-draft proposal strips unsafe HTML content before persistence' );
	}

	$pending_preflight = npcink_governance_core_smoke_rest_result( 'POST', '/npcink-governance-core/v1/proposals/' . rawurlencode( $proposal_id ) . '/commit-preflight' );
	npcink_governance_core_smoke_assert( 409 === (int) $pending_preflight['status'], $ability_id . ' commit preflight fails for pending proposal' );

	$approved = npcink_governance_core_smoke_rest(
		'POST',
		'/npcink-governance-core/v1/proposals/' . rawurlencode( $proposal_id ) . '/approve',
		array(
			'note' => 'Smoke approval.',
		)
	);

	npcink_governance_core_smoke_assert( 'approved' === (string) ( $approved['status'] ?? '' ), $ability_id . ' proposal approved through REST' );

	$preflight = npcink_governance_core_smoke_rest( 'POST', '/npcink-governance-core/v1/proposals/' . rawurlencode( $proposal_id ) . '/commit-preflight' );
	npcink_governance_core_smoke_assert( false === (bool) ( $preflight['commit_execution'] ?? true ), $ability_id . ' commit preflight does not execute ability' );
	npcink_governance_core_smoke_assert( true === (bool) ( $preflight['approval_context']['approval_commit_authorized'] ?? false ), $ability_id . ' commit preflight returns approval authorization context' );
	npcink_governance_core_smoke_assert( 'approved_commit' === (string) ( $preflight['approval_context']['confirmation_state'] ?? '' ), $ability_id . ' commit preflight returns approved_commit state' );
	npcink_governance_core_smoke_assert( $ability_id === (string) ( $preflight['proposal']['ability_id'] ?? '' ), $ability_id . ' preflight proposal keeps the real ability id' );
	npcink_governance_core_smoke_assert( true === (bool) ( $preflight['proposal']['input']['dry_run'] ?? false ), $ability_id . ' preflight returns the dry-run proposal input without committing' );
	npcink_governance_core_smoke_assert( false === (bool) ( $preflight['proposal']['input']['commit'] ?? false ), $ability_id . ' preflight does not turn proposal input into a commit request' );
	if ( 'npcink-abilities-toolkit/create-draft' === $ability_id && 'html' === (string) ( $input['content_format'] ?? '' ) ) {
		npcink_governance_core_smoke_assert( false !== strpos( (string) ( $preflight['proposal']['input']['content'] ?? '' ), '<p>' ), 'create-draft preflight preserves safe HTML content in approved input' );
	}
	npcink_governance_core_smoke_assert( $ability_id === (string) ( $preflight['capability']['ability_id'] ?? '' ), $ability_id . ' preflight capability is rediscovered from ability intake' );
	$preflight_posture = is_array( $preflight['capability']['implementation_posture'] ?? null ) ? $preflight['capability']['implementation_posture'] : array();
	if ( ! empty( $preflight_posture ) ) {
		npcink_governance_core_smoke_assert( 'npcink_abilities_toolkit_implementation_posture.v1' === (string) ( $preflight_posture['schema_version'] ?? '' ), $ability_id . ' preflight capability exposes implementation posture' );
		npcink_governance_core_smoke_assert( $preflight_posture == (array) ( $preflight['contract_preflight']['current_contract']['implementation_posture'] ?? array() ), $ability_id . ' preflight contract fingerprint carries implementation posture' );
	}
	$correlation_id = (string) ( $preflight['correlation_id'] ?? '' );
	npcink_governance_core_smoke_assert( '' !== $correlation_id, $ability_id . ' commit preflight returns correlation id' );
	npcink_governance_core_smoke_assert( $correlation_id === (string) ( $preflight['approval_context']['correlation_id'] ?? '' ), $ability_id . ' approval context includes matching correlation id' );
	$approved_input_hash = (string) ( $preflight['approval_context']['approved_input_hash'] ?? '' );
	npcink_governance_core_smoke_assert( '' !== $approved_input_hash, $ability_id . ' approval context includes approved input hash' );
	npcink_governance_core_smoke_assert( $approved_input_hash === hash( 'sha256', (string) wp_json_encode( $preflight['proposal']['input'] ?? array() ) ), $ability_id . ' approved input hash matches proposal input' );
	npcink_governance_core_smoke_assert( '' !== (string) ( $preflight['approval_context']['approved_preview_hash'] ?? '' ), $ability_id . ' approval context includes approved preview hash' );
	npcink_governance_core_smoke_assert( 'core-preflight-v1' === (string) ( $preflight['approval_context']['policy_version'] ?? '' ), $ability_id . ' approval context includes policy version' );
	npcink_governance_core_smoke_assert( $approved_input_hash === (string) ( $preflight['execution_handoff']['approved_input_hash'] ?? '' ), $ability_id . ' execution handoff carries approved input hash' );
	npcink_governance_core_smoke_assert( untrailingslashit( site_url() ) === (string) ( $preflight['approval_context']['site_url'] ?? '' ), $ability_id . ' approval context binds current site_url' );
	npcink_governance_core_smoke_assert( untrailingslashit( home_url() ) === (string) ( $preflight['approval_context']['home_url'] ?? '' ), $ability_id . ' approval context binds current home_url' );
	npcink_governance_core_smoke_assert( get_current_blog_id() === (int) ( $preflight['approval_context']['blog_id'] ?? 0 ), $ability_id . ' approval context binds current blog id' );
	npcink_governance_core_smoke_assert( untrailingslashit( site_url() ) === (string) ( $preflight['execution_handoff']['site_url'] ?? '' ), $ability_id . ' execution handoff binds current site_url' );
	npcink_governance_core_smoke_assert( untrailingslashit( home_url() ) === (string) ( $preflight['execution_handoff']['home_url'] ?? '' ), $ability_id . ' execution handoff binds current home_url' );
	npcink_governance_core_smoke_assert( get_current_blog_id() === (int) ( $preflight['execution_handoff']['blog_id'] ?? 0 ), $ability_id . ' execution handoff binds current blog id' );
	$GLOBALS['npcink_governance_core_smoke_preflight_correlations'][ $proposal_id ] = $correlation_id;

	return $proposal_id;
}

