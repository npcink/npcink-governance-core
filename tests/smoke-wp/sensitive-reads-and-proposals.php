<?php
/** Sensitive reads and proposals test module. @package NpcinkGovernanceCore */


$sensitive_read_ability_id = '';
foreach ( array( 'npcink-abilities-toolkit/wp-ops-diagnostics-detail', 'npcink-abilities-toolkit/wp-diagnostics-summary' ) as $candidate_sensitive_read_ability_id ) {
	if ( isset( $items_by_id[ $candidate_sensitive_read_ability_id ] ) ) {
		$sensitive_read_ability_id = $candidate_sensitive_read_ability_id;
		break;
	}
}
npcink_governance_core_smoke_assert( '' !== $sensitive_read_ability_id, 'sensitive diagnostics read ability is discoverable' );
npcink_governance_core_smoke_assert_read_authorization_required( $items_by_id[ $sensitive_read_ability_id ], 'diagnostics sensitive read uses Core read authorization' );

$sensitive_read_input = array(
	'include_error_log'     => true,
	'tail_lines'            => 5,
	'max_plugins_per_group' => 5,
);
$read_request = npcink_governance_core_smoke_rest(
	'POST',
	'/npcink-governance-core/v1/read-requests',
	array(
		'ability_id'              => $sensitive_read_ability_id,
		'input'                   => $sensitive_read_input,
		'requested_input_summary' => 'Smoke bounded diagnostics read',
		'sensitivity'             => 'sensitive',
		'data_classes'            => array( 'diagnostics', 'logs' ),
		'redaction_level'         => 'strict',
		'purpose'                 => 'Core smoke read authorization; authorization header: SHOULD_NOT_LEAK',
		'caller'                  => array(
			'caller_type' => 'mcp_adapter',
			'token'       => 'SHOULD_NOT_LEAK',
		),
		'max_rows'                => 25,
		'tail_lines'              => 5,
		'allowed_fields'          => array( 'error_log', 'plugins', 'database' ),
		'denied_fields'           => array( 'authorization', 'cookie' ),
	)
);
$read_request_id = (string) ( $read_request['request_id'] ?? '' );
npcink_governance_core_smoke_assert( '' !== $read_request_id, 'sensitive read request is created' );
npcink_governance_core_smoke_assert( 'pending' === (string) ( $read_request['status'] ?? '' ), 'sensitive read request starts pending' );
npcink_governance_core_smoke_assert( 64 === strlen( (string) ( $read_request['input_hash'] ?? '' ) ), 'sensitive read request binds input hash' );
npcink_governance_core_smoke_assert( false === strpos( (string) wp_json_encode( $read_request ), 'SHOULD_NOT_LEAK' ), 'sensitive read request response redacts secret-like caller and purpose values' );

$read_request_detail = npcink_governance_core_smoke_rest( 'GET', '/npcink-governance-core/v1/read-requests/' . rawurlencode( $read_request_id ) );
npcink_governance_core_smoke_assert( is_array( $read_request_detail['audit_timeline'] ?? null ), 'sensitive read request detail returns audit timeline' );
npcink_governance_core_smoke_assert( count( (array) ( $read_request_detail['audit_timeline'] ?? array() ) ) >= 1, 'sensitive read request audit timeline records creation' );

$read_request_list = npcink_governance_core_smoke_rest( 'GET', '/npcink-governance-core/v1/read-requests', array( 'status' => 'pending', 'limit' => 10 ) );
$listed_read_request = false;
foreach ( (array) ( $read_request_list['items'] ?? array() ) as $item ) {
	if ( is_array( $item ) && $read_request_id === (string) ( $item['request_id'] ?? '' ) ) {
		$listed_read_request = true;
		break;
	}
}
npcink_governance_core_smoke_assert( $listed_read_request, 'sensitive read request list returns pending request' );

$read_request_approved = npcink_governance_core_smoke_rest(
	'POST',
	'/npcink-governance-core/v1/read-requests/' . rawurlencode( $read_request_id ) . '/approve',
	array(
		'note'            => 'Smoke approval',
		'redaction_level' => 'strict',
		'max_rows'        => 10,
		'tail_lines'      => 5,
		'allowed_fields'  => array( 'error_log', 'plugins' ),
		'denied_fields'   => array( 'authorization', 'cookie', 'application_password' ),
	)
);
npcink_governance_core_smoke_assert( 'approved' === (string) ( $read_request_approved['status'] ?? '' ), 'sensitive read request is approved' );

$read_hash_only = npcink_governance_core_smoke_rest_result(
	'POST',
	'/npcink-governance-core/v1/read-requests/' . rawurlencode( $read_request_id ) . '/read-preflight',
	array(
		'ability_id'  => $sensitive_read_ability_id,
		'input_hash'  => (string) ( $read_request['input_hash'] ?? '' ),
	)
);
npcink_governance_core_smoke_assert( 400 === (int) $read_hash_only['status'], 'sensitive read preflight rejects hash-only grant requests' );

$read_wrong_input = npcink_governance_core_smoke_rest_result(
	'POST',
	'/npcink-governance-core/v1/read-requests/' . rawurlencode( $read_request_id ) . '/read-preflight',
	array(
		'ability_id' => $sensitive_read_ability_id,
		'input'      => array_merge( $sensitive_read_input, array( 'tail_lines' => 6 ) ),
	)
);
npcink_governance_core_smoke_assert( 409 === (int) $read_wrong_input['status'], 'sensitive read preflight rejects changed input hash' );

$read_grant = npcink_governance_core_smoke_rest(
	'POST',
	'/npcink-governance-core/v1/read-requests/' . rawurlencode( $read_request_id ) . '/read-preflight',
	array(
		'ability_id' => $sensitive_read_ability_id,
		'input'      => $sensitive_read_input,
	)
);
$read_context = is_array( $read_grant['read_authorization_context'] ?? null ) ? $read_grant['read_authorization_context'] : array();
npcink_governance_core_smoke_assert( true === (bool) ( $read_context['read_authorization_granted'] ?? false ), 'sensitive read preflight returns grant flag' );
npcink_governance_core_smoke_assert( 'npcink_governance_core' === (string) ( $read_context['core_authorization_truth'] ?? '' ), 'sensitive read grant names Core as authorization truth' );
npcink_governance_core_smoke_assert( false === (bool) ( $read_context['commit_execution'] ?? true ), 'sensitive read grant disables commit execution' );
npcink_governance_core_smoke_assert( false === (bool) ( $read_context['write_execution'] ?? true ), 'sensitive read grant disables write execution' );
npcink_governance_core_smoke_assert( (string) ( $read_request['input_hash'] ?? '' ) === (string) ( $read_context['approved_input_hash'] ?? '' ), 'sensitive read grant binds approved input hash' );
npcink_governance_core_smoke_assert( $sensitive_read_ability_id === (string) ( $read_context['ability_id'] ?? '' ), 'sensitive read grant binds ability id' );
npcink_governance_core_smoke_assert( untrailingslashit( site_url() ) === (string) ( $read_context['site_url'] ?? '' ), 'sensitive read grant binds current site_url' );
npcink_governance_core_smoke_assert( untrailingslashit( home_url() ) === (string) ( $read_context['home_url'] ?? '' ), 'sensitive read grant binds current home_url' );
npcink_governance_core_smoke_assert( get_current_blog_id() === (int) ( $read_context['blog_id'] ?? 0 ), 'sensitive read grant binds current blog id' );
$granted_max_rows   = (int) ( $read_context['bounds']['max_rows'] ?? 0 );
$granted_tail_lines = (int) ( $read_context['bounds']['tail_lines'] ?? 0 );
npcink_governance_core_smoke_assert( $granted_max_rows >= 0 && $granted_max_rows <= 10, 'sensitive read grant does not exceed approved max_rows bound when present' );
npcink_governance_core_smoke_assert( $granted_tail_lines > 0 && $granted_tail_lines <= 5, 'sensitive read grant does not exceed approved tail_lines bound' );
npcink_governance_core_smoke_assert( false === strpos( (string) wp_json_encode( $read_grant ), 'SHOULD_NOT_LEAK' ), 'sensitive read grant response does not emit secret sentinel' );

npcink_governance_core_smoke_assert_create_draft_contract( $items_by_id );
npcink_governance_core_smoke_assert_seo_meta_contract( $items_by_id );
npcink_governance_core_smoke_assert_comment_approval_contract( $items_by_id );
npcink_governance_core_smoke_assert_taxonomy_terms_contract( $items_by_id );
npcink_governance_core_smoke_assert_plan_bridge_contract( $items_by_id );

$workflow_cases = npcink_governance_core_smoke_workflow_cases();
npcink_governance_core_smoke_assert( count( $workflow_cases ) > 0, 'shared workflow definitions are available to Core' );

foreach ( $workflow_cases as $case_id => $case ) {
	$preferred_id = (string) ( is_array( $case ) ? ( $case['preferred_ability_id'] ?? '' ) : '' );
	npcink_governance_core_smoke_assert( isset( $items_by_id[ $preferred_id ] ), 'replay case ' . $case_id . ' preferred bundle is discoverable by Core' );
	npcink_governance_core_smoke_assert( 'read' === (string) ( $items_by_id[ $preferred_id ]['risk_level'] ?? '' ), 'replay case ' . $case_id . ' preferred bundle remains read risk' );
	npcink_governance_core_smoke_assert( false === (bool) ( $items_by_id[ $preferred_id ]['requires_approval'] ?? true ), 'replay case ' . $case_id . ' preferred bundle does not require approval' );
		npcink_governance_core_smoke_assert_capability_guidance( $items_by_id[ $preferred_id ], 'core_read_authorization_required', 'wp_abilities_rest', 'replay case ' . $case_id . ' preferred bundle uses fail-closed read authorization guidance' );
		npcink_governance_core_smoke_assert_read_authorization_required( $items_by_id[ $preferred_id ], 'replay case ' . $case_id . ' undeclared sensitivity' );

	foreach ( (array) ( $case['disallowed_default_ability_ids'] ?? array() ) as $disallowed_id ) {
		$disallowed_id = (string) $disallowed_id;
		npcink_governance_core_smoke_assert( isset( $items_by_id[ $disallowed_id ] ), 'replay case ' . $case_id . ' disallowed default ability is discoverable for proposal handoff' );
		npcink_governance_core_smoke_assert( 'read' !== (string) ( $items_by_id[ $disallowed_id ]['risk_level'] ?? 'read' ), 'replay case ' . $case_id . ' disallowed default ability is write-like' );
		npcink_governance_core_smoke_assert( true === (bool) ( $items_by_id[ $disallowed_id ]['requires_approval'] ?? false ), 'replay case ' . $case_id . ' disallowed default ability requires approval in Core' );
		npcink_governance_core_smoke_assert_capability_guidance( $items_by_id[ $disallowed_id ], 'proposal_required', 'adapter_after_core_preflight', 'replay case ' . $case_id . ' disallowed default ability uses proposal guidance' );
	}
}

$planning_label = npcink_governance_core_smoke_rest_result(
	'POST',
	'/npcink-governance-core/v1/proposals',
	array(
		'ability_id' => 'content/draft-preview',
		'title'      => 'Planning label should not be accepted ' . $npcink_governance_core_smoke_run_id,
	)
);
npcink_governance_core_smoke_assert( 404 === (int) $planning_label['status'], 'proposal creation rejects planning labels that are not real ability ids' );

$proposal_id = npcink_governance_core_smoke_run_governance_proposal(
	'npcink-abilities-toolkit/create-draft',
	$items_by_id,
	'Smoke draft proposal',
	array(
		'title'          => 'Core Governance Smoke Draft ' . $npcink_governance_core_smoke_run_id,
		'content'        => '<p>Smoke draft content.</p><script>alert("unsafe")</script>',
		'content_format' => 'html',
		'status'         => 'draft',
		'dry_run'        => true,
		'commit'         => false,
	),
	array(
		'dry_run'       => true,
		'host_governed' => true,
	)
);

$seo_proposal_id = npcink_governance_core_smoke_run_governance_proposal(
	'npcink-abilities-toolkit/set-post-seo-meta',
	$items_by_id,
	'Smoke SEO metadata proposal',
	array(
		'post_id'         => 1,
		'seo_title'       => 'Smoke SEO Title ' . $npcink_governance_core_smoke_run_id,
		'seo_description' => 'Smoke SEO description.',
		'dry_run'         => true,
		'commit'          => false,
	),
	array(
		'field_patch'      => array(
			'seo_title'       => 'Smoke SEO Title ' . $npcink_governance_core_smoke_run_id,
			'seo_description' => 'Smoke SEO description.',
		),
		'dry_run'          => true,
		'host_governed'    => true,
		'commit_execution' => false,
	)
);

npcink_governance_core_smoke_assert( '' !== $seo_proposal_id, 'SEO metadata proposal completed governance loop' );

$pending_comment = npcink_governance_core_smoke_create_pending_comment();

$comment_proposal_id = npcink_governance_core_smoke_run_governance_proposal(
	'npcink-abilities-toolkit/approve-comment',
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

npcink_governance_core_smoke_assert( '' !== $comment_proposal_id, 'comment moderation proposal completed governance loop' );
$comment_after_preflight = get_comment( $pending_comment['comment_id'] );
npcink_governance_core_smoke_assert( $comment_after_preflight instanceof WP_Comment && $pending_comment['current_status'] === (string) $comment_after_preflight->comment_approved, 'comment approval governance loop does not mutate comment status' );

$taxonomy_fixture = npcink_governance_core_smoke_create_taxonomy_terms_fixture();
$taxonomy_before  = npcink_governance_core_smoke_post_term_ids( $taxonomy_fixture['post_id'], $taxonomy_fixture['taxonomy'] );
$taxonomy_preview = npcink_governance_core_smoke_run_taxonomy_terms_preview( $taxonomy_fixture );
$taxonomy_data    = is_array( $taxonomy_preview['data'] ?? null ) ? $taxonomy_preview['data'] : array();
$taxonomy_input   = is_array( $taxonomy_data['proposal']['input'] ?? null ) ? $taxonomy_data['proposal']['input'] : array();
npcink_governance_core_smoke_assert( true === (bool) ( $taxonomy_input['dry_run'] ?? false ), 'taxonomy terms preview produces dry-run set-post-terms input' );
npcink_governance_core_smoke_assert( false === (bool) ( $taxonomy_input['commit'] ?? false ), 'taxonomy terms preview keeps set-post-terms commit disabled' );
npcink_governance_core_smoke_assert( false === (bool) ( $taxonomy_input['create_missing'] ?? true ), 'taxonomy terms preview does not request term creation' );

$taxonomy_proposal_id = npcink_governance_core_smoke_run_governance_proposal(
	'npcink-abilities-toolkit/set-post-terms',
	$items_by_id,
	'Smoke taxonomy terms proposal',
	$taxonomy_input,
	array(
		'proposal_helper_ability_id' => 'npcink-abilities-toolkit/propose-post-taxonomy-terms',
		'target_ability_id'          => 'npcink-abilities-toolkit/set-post-terms',
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

npcink_governance_core_smoke_assert( '' !== $taxonomy_proposal_id, 'taxonomy terms proposal completed governance loop' );
$taxonomy_after = npcink_governance_core_smoke_post_term_ids( $taxonomy_fixture['post_id'], $taxonomy_fixture['taxonomy'] );
npcink_governance_core_smoke_assert( $taxonomy_before === $taxonomy_after, 'taxonomy terms governance loop does not mutate post terms' );

