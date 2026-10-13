<?php
/** Media plan cases test module. @package NpcinkGovernanceCore */

$plan_attachment_title = 'Core Plan Bridge Media Candidate ' . $npcink_governance_core_smoke_run_id;
$plan_attachment_id    = npcink_governance_core_smoke_create_media_attachment_fixture( $plan_attachment_title );
npcink_governance_core_smoke_assert( (int) $plan_attachment_id > 0, 'plan bridge media fixture attachment is created' );
delete_post_meta( (int) $plan_attachment_id, '_wp_attachment_image_alt' );

$media_alt_plan_input = array(
	'attachment_id'                     => (int) $plan_attachment_id,
	'alt'                               => 'Reviewed blue square image for the Core smoke test',
	'expected_current_alt'              => '',
	'operator_visual_review_confirmed' => true,
	'review_set_contract'               => 'media_alt_caption_review_set.v1',
	'source_item_id'                    => 'media-alt-caption:' . (int) $plan_attachment_id,
	'evidence_refs'                     => array( 'core-smoke-visual-review:' . $npcink_governance_core_smoke_run_id ),
);
$media_alt_plan = npcink_governance_core_smoke_run_plan_ability( 'npcink-abilities-toolkit/build-media-alt-apply-plan', $media_alt_plan_input );
$media_alt_plan_result = npcink_governance_core_smoke_create_proposals_from_plan( 'npcink-abilities-toolkit/build-media-alt-apply-plan', $media_alt_plan, $media_alt_plan_input );
npcink_governance_core_smoke_assert( 1 === (int) ( $media_alt_plan_result['proposal_count'] ?? 0 ), 'media ALT apply plan generates exactly one Core proposal' );
$media_alt_plan_proposal = is_array( $media_alt_plan_result['proposals'][0] ?? null ) ? $media_alt_plan_result['proposals'][0] : array();
npcink_governance_core_smoke_assert_plan_proposal_shape( $media_alt_plan_proposal, 'npcink-abilities-toolkit/update-media-details', true );
npcink_governance_core_smoke_assert( 'pending' === (string) ( $media_alt_plan_proposal['status'] ?? '' ), 'manual Core policy keeps the media ALT proposal pending' );
npcink_governance_core_smoke_assert( '' === (string) ( $media_alt_plan_proposal['input']['expected_current_alt'] ?? 'missing' ), 'media ALT proposal preserves the expected empty old value' );
npcink_governance_core_smoke_assert( true === ( $media_alt_plan_proposal['input']['operator_visual_review_confirmed'] ?? false ), 'media ALT proposal preserves visual review confirmation' );
npcink_governance_core_smoke_assert( 'media_alt_apply_plan_item' === (string) ( $media_alt_plan_proposal['preview']['media_alt_apply']['artifact_type'] ?? '' ), 'media ALT proposal preserves ALT-only review evidence' );
$media_alt_audit = npcink_governance_core_smoke_rest(
	'GET',
	'/npcink-governance-core/v1/audit',
	array( 'proposal_id' => (string) ( $media_alt_plan_proposal['proposal_id'] ?? '' ), 'limit' => 20 )
);
$media_alt_created_audit = array_values(
	array_filter(
		(array) ( $media_alt_audit['items'] ?? array() ),
		static function ( $item ): bool {
			return is_array( $item ) && 'proposal.created' === (string) ( $item['event_name'] ?? '' );
		}
	)
);
$media_alt_audit_evidence = is_array( $media_alt_created_audit[0]['metadata']['media_alt_evidence'] ?? null ) ? $media_alt_created_audit[0]['metadata']['media_alt_evidence'] : array();
npcink_governance_core_smoke_assert( '' === (string) ( $media_alt_audit_evidence['expected_current_alt'] ?? 'missing' ), 'media ALT proposal audit preserves the empty old value' );
npcink_governance_core_smoke_assert( 'Reviewed blue square image for the Core smoke test' === (string) ( $media_alt_audit_evidence['proposed_alt'] ?? '' ), 'media ALT proposal audit preserves the reviewed final value' );
npcink_governance_core_smoke_assert( '' !== (string) ( $media_alt_audit_evidence['idempotency_key'] ?? '' ), 'media ALT proposal audit preserves the idempotency key' );
$media_alt_preflight = npcink_governance_core_smoke_approve_and_preflight_plan_proposal( (string) ( $media_alt_plan_proposal['proposal_id'] ?? '' ) );
npcink_governance_core_smoke_assert( true === (bool) ( $media_alt_preflight['proposal_item_preflight']['media_alt_guard']['valid'] ?? false ), 'Core preflight revalidates the approved media ALT evidence contract' );
npcink_governance_core_smoke_assert( true === (bool) ( $media_alt_preflight['proposal_item_preflight']['media_alt_guard']['requires_live_value_check'] ?? false ), 'Core preflight requires Adapter and Toolkit to recheck the live ALT value' );
$media_alt_dry_run = npcink_governance_core_smoke_run_external_ability_dry_run( 'npcink-abilities-toolkit/update-media-details', (array) ( $media_alt_plan_proposal['input'] ?? array() ) );
npcink_governance_core_smoke_assert( true === (bool) ( $media_alt_dry_run['dry_run'] ?? false ), 'Toolkit executes the approved media ALT input as a dry run before commit' );
npcink_governance_core_smoke_assert( '' === (string) get_post_meta( (int) $plan_attachment_id, '_wp_attachment_image_alt', true ), 'media ALT plan, Core proposal, preflight, and Toolkit dry run leave attachment ALT unchanged' );

$media_plan_input = array(
	'attachment_ids'  => array( (int) $plan_attachment_id ),
	'issue_types'     => array( 'missing_alt', 'missing_caption', 'missing_description', 'possibly_unattached' ),
	'article_title'   => $plan_content_title,
	'article_excerpt' => 'Smoke media metadata context.',
	'focus_keyword'   => 'plan bridge',
	'max_actions'     => 5,
);
$media_plan       = npcink_governance_core_smoke_run_plan_ability( 'npcink-abilities-toolkit/build-media-inventory-fix-plan', $media_plan_input );
$media_plan_result = npcink_governance_core_smoke_create_proposals_from_plan( 'npcink-abilities-toolkit/build-media-inventory-fix-plan', $media_plan, $media_plan_input );
npcink_governance_core_smoke_assert( (int) ( $media_plan_result['proposal_count'] ?? 0 ) >= 1, 'media inventory fix plan generates Core proposals' );
$media_targets = array_map(
	static function ( $proposal ) {
		return is_array( $proposal ) ? (string) ( $proposal['ability_id'] ?? '' ) : '';
	},
	(array) ( $media_plan_result['proposals'] ?? array() )
);
npcink_governance_core_smoke_assert( ! in_array( 'npcink-abilities-toolkit/delete-media-permanently', $media_targets, true ), 'media delete candidates do not enter executable proposals by default' );
$media_plan_proposal = is_array( $media_plan_result['proposals'][0] ?? null ) ? $media_plan_result['proposals'][0] : array();
npcink_governance_core_smoke_assert_plan_proposal_shape( $media_plan_proposal, 'npcink-abilities-toolkit/update-media-details', true );
npcink_governance_core_smoke_assert( (int) ( $media_plan_proposal['preview']['warnings']['skipped_destructive_candidate_count'] ?? 0 ) >= 1, 'media plan proposal preserves skipped destructive candidates' );
npcink_governance_core_smoke_approve_and_preflight_plan_proposal( (string) ( $media_plan_proposal['proposal_id'] ?? '' ) );

$media_rename_plan_input = array(
	'attachment_id'     => (int) $plan_attachment_id,
	'target_file_name'  => 'core-smoke-media-rename-reviewed',
);
$media_rename_plan = npcink_governance_core_smoke_run_plan_ability( 'npcink-abilities-toolkit/build-media-rename-plan', $media_rename_plan_input );
$media_rename_plan_result = npcink_governance_core_smoke_create_proposals_from_plan( 'npcink-abilities-toolkit/build-media-rename-plan', $media_rename_plan, $media_rename_plan_input );
npcink_governance_core_smoke_assert( 1 === (int) ( $media_rename_plan_result['proposal_count'] ?? 0 ), 'media rename plan generates one Core proposal' );
$media_rename_proposal = is_array( $media_rename_plan_result['proposals'][0] ?? null ) ? $media_rename_plan_result['proposals'][0] : array();
npcink_governance_core_smoke_assert_plan_proposal_shape( $media_rename_proposal, 'npcink-abilities-toolkit/rename-media-file', true );
npcink_governance_core_smoke_assert( 'core-smoke-media-rename-reviewed.png' === (string) ( $media_rename_proposal['input']['target_file_name'] ?? '' ), 'media rename proposal preserves reviewed target filename' );
npcink_governance_core_smoke_assert( is_array( $media_rename_proposal['preview']['media_rename'] ?? null ), 'media rename proposal preserves rename preview context' );
npcink_governance_core_smoke_approve_and_preflight_plan_proposal( (string) ( $media_rename_proposal['proposal_id'] ?? '' ) );

$media_delete_plan_input = array(
	'attachment_ids'                  => array( (int) $plan_attachment_id ),
	'issue_types'                     => array( 'possibly_unattached' ),
	'include_delete_candidates'       => true,
	'include_unattached_nonproduction_media'   => true,
	'max_actions'                     => 5,
);
$media_delete_plan       = npcink_governance_core_smoke_run_plan_ability( 'npcink-abilities-toolkit/build-media-inventory-fix-plan', $media_delete_plan_input );
$media_delete_blocked    = npcink_governance_core_smoke_create_proposals_from_plan( 'npcink-abilities-toolkit/build-media-inventory-fix-plan', $media_delete_plan, array( 'include_delete_candidates' => false ) );
npcink_governance_core_smoke_assert( 0 === (int) ( $media_delete_blocked['proposal_count'] ?? -1 ), 'explicit media delete plan is blocked without matching include_delete_candidates input' );
npcink_governance_core_smoke_assert( 'destructive_media_delete_not_explicitly_included' === (string) ( $media_delete_blocked['blocked_items'][0]['block_code'] ?? '' ), 'blocked media delete records destructive guard reason' );
$media_delete_plan_tampered = $media_delete_plan;
if ( is_array( $media_delete_plan_tampered['data'] ?? null ) ) {
	$media_delete_plan_tampered['data']['include_delete_candidates'] = true;
}
$media_delete_tampered = npcink_governance_core_smoke_create_proposals_from_plan( 'npcink-abilities-toolkit/build-media-inventory-fix-plan', $media_delete_plan_tampered, array() );
npcink_governance_core_smoke_assert( 0 === (int) ( $media_delete_tampered['proposal_count'] ?? -1 ), 'tampered media delete plan flag does not bypass plan_input destructive guard' );
npcink_governance_core_smoke_assert( 'destructive_media_delete_not_explicitly_included' === (string) ( $media_delete_tampered['blocked_items'][0]['block_code'] ?? '' ), 'tampered media delete plan is blocked by destructive guard' );
$media_delete_allowed = npcink_governance_core_smoke_create_proposals_from_plan( 'npcink-abilities-toolkit/build-media-inventory-fix-plan', $media_delete_plan, $media_delete_plan_input );
npcink_governance_core_smoke_assert( (int) ( $media_delete_allowed['proposal_count'] ?? 0 ) >= 1, 'explicit media delete plan can generate a high-risk Core proposal' );
$media_delete_proposal = is_array( $media_delete_allowed['proposals'][0] ?? null ) ? $media_delete_allowed['proposals'][0] : array();
npcink_governance_core_smoke_assert_plan_proposal_shape( $media_delete_proposal, 'npcink-abilities-toolkit/delete-media-permanently', true );
npcink_governance_core_smoke_assert( 'high' === (string) ( $media_delete_proposal['preview']['risk']['level'] ?? '' ), 'explicit media delete proposal is marked high risk' );

$requires_input_plan = array(
	'success' => true,
	'data'    => array(
			'batch_id'         => 'core_plan_bridge_requires_input_smoke_' . $npcink_governance_core_smoke_run_id,
		'issue_types'      => array( 'title' ),
		'write_actions'    => array(
			array(
				'action_id'          => 'set_title_requires_input_smoke',
				'target_ability_id'  => 'npcink-abilities-toolkit/update-post',
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
$requires_input_result = npcink_governance_core_smoke_create_proposals_from_plan( 'npcink-abilities-toolkit/build-content-inventory-fix-plan', $requires_input_plan, array() );
npcink_governance_core_smoke_assert( 1 === (int) ( $requires_input_result['proposal_count'] ?? 0 ), 'requires-input plan action still creates a reviewable proposal' );
$requires_input_proposal = is_array( $requires_input_result['proposals'][0] ?? null ) ? $requires_input_result['proposals'][0] : array();
npcink_governance_core_smoke_assert_plan_proposal_shape( $requires_input_proposal, 'npcink-abilities-toolkit/update-post', false );
npcink_governance_core_smoke_assert( array( 'title' ) === (array) ( $requires_input_proposal['preview']['needs_input'] ?? array() ), 'requires-input proposal preserves missing field list' );
npcink_governance_core_smoke_rest(
	'POST',
	'/npcink-governance-core/v1/proposals/' . rawurlencode( (string) ( $requires_input_proposal['proposal_id'] ?? '' ) ) . '/approve',
	array(
		'note' => 'Approve blocked proposal for preflight smoke.',
	)
);
$requires_input_preflight = npcink_governance_core_smoke_rest_result( 'POST', '/npcink-governance-core/v1/proposals/' . rawurlencode( (string) ( $requires_input_proposal['proposal_id'] ?? '' ) ) . '/commit-preflight' );
npcink_governance_core_smoke_assert( 409 === (int) $requires_input_preflight['status'], 'requires-input proposal cannot enter committable state' );
npcink_governance_core_smoke_assert( false === (bool) ( $requires_input_preflight['data']['data']['proposal_item_preflight']['executable'] ?? true ), 'requires-input preflight marks proposal item blocked' );
npcink_governance_core_smoke_assert( array( 'title' ) === (array) ( $requires_input_preflight['data']['data']['proposal_item_preflight']['needs_input'] ?? array() ), 'requires-input preflight reports fields needing human input' );

$output_reference_plan = array(
	'success' => true,
	'data'    => array(
			'batch_id'         => 'core_plan_bridge_output_reference_smoke_' . $npcink_governance_core_smoke_run_id,
		'issue_types'      => array( 'acceptance' ),
		'write_actions'    => array(
			array(
				'action_id'          => 'create-draft-fixture',
				'target_ability_id'  => 'npcink-abilities-toolkit/create-draft',
				'input'              => array(
					'post_type' => 'post',
					'status'    => 'draft',
						'title'     => 'Core plan bridge output reference draft ' . $npcink_governance_core_smoke_run_id,
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
				'target_ability_id'  => 'npcink-abilities-toolkit/update-post',
				'depends_on'         => array( 'create-draft-fixture' ),
				'input'              => array(
					'post_id' => '$outputs.create-draft-fixture.post_id',
						'title'   => 'Core plan bridge output reference updated draft ' . $npcink_governance_core_smoke_run_id,
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
$output_reference_result = npcink_governance_core_smoke_create_proposals_from_plan( 'npcink-abilities-toolkit/build-content-inventory-fix-plan', $output_reference_plan, array() );
npcink_governance_core_smoke_assert( 1 === (int) ( $output_reference_result['proposal_count'] ?? 0 ), 'output-reference plan creates one batch proposal' );
$output_reference_proposal = is_array( $output_reference_result['proposals'][0] ?? null ) ? $output_reference_result['proposals'][0] : array();
$output_reference_input    = is_array( $output_reference_proposal['input'] ?? null ) ? $output_reference_proposal['input'] : array();
$output_reference_actions  = is_array( $output_reference_input['write_actions'] ?? null ) ? array_values( $output_reference_input['write_actions'] ) : array();
npcink_governance_core_smoke_assert( 'plan_to_proposal_batch' === (string) ( $output_reference_proposal['preview']['source']['type'] ?? '' ), 'output-reference plan proposal records batch source type' );
npcink_governance_core_smoke_assert( 'npcink-abilities-toolkit/create-draft' === (string) ( $output_reference_proposal['ability_id'] ?? '' ), 'output-reference batch proposal stores first target ability for Core availability checks' );
npcink_governance_core_smoke_assert( 2 === count( $output_reference_actions ), 'output-reference batch proposal stores ordered write_actions' );
npcink_governance_core_smoke_assert( '$outputs.create-draft-fixture.post_id' === (string) ( $output_reference_actions[1]['input']['post_id'] ?? '' ), 'output-reference batch proposal preserves post_id output reference' );
npcink_governance_core_smoke_assert( array( 'create-draft-fixture' ) === (array) ( $output_reference_actions[1]['depends_on'] ?? array() ), 'output-reference batch proposal preserves depends_on on write action' );
npcink_governance_core_smoke_assert( array( 'create-draft-fixture' ) === (array) ( $output_reference_proposal['preview']['actions'][1]['depends_on'] ?? array() ), 'output-reference batch proposal preserves depends_on in preview action' );
npcink_governance_core_smoke_approve_and_preflight_plan_proposal( (string) ( $output_reference_proposal['proposal_id'] ?? '' ) );

$unsafe_action_plan = $requires_input_plan;
$unsafe_action_plan['data']['write_actions'][0]['requires_approval'] = false;
$unsafe_action_plan['data']['write_actions'][0]['commit_execution'] = true;
$unsafe_action_result = npcink_governance_core_smoke_create_proposals_from_plan( 'npcink-abilities-toolkit/build-content-inventory-fix-plan', $unsafe_action_plan, array() );
npcink_governance_core_smoke_assert( 0 === (int) ( $unsafe_action_result['proposal_count'] ?? -1 ), 'unsafe write action is blocked before proposal creation' );
npcink_governance_core_smoke_assert( 'action_requires_approval_missing' === (string) ( $unsafe_action_result['blocked_items'][0]['block_code'] ?? '' ), 'unsafe write action records missing action approval guard' );
$executed_action_plan = $requires_input_plan;
$executed_action_plan['data']['write_actions'][0]['requires_approval'] = true;
$executed_action_plan['data']['write_actions'][0]['commit_execution'] = true;
$executed_action_result = npcink_governance_core_smoke_create_proposals_from_plan( 'npcink-abilities-toolkit/build-content-inventory-fix-plan', $executed_action_plan, array() );
npcink_governance_core_smoke_assert( 0 === (int) ( $executed_action_result['proposal_count'] ?? -1 ), 'already-executed write action is blocked before proposal creation' );
npcink_governance_core_smoke_assert( 'action_commit_execution_rejected' === (string) ( $executed_action_result['blocked_items'][0]['block_code'] ?? '' ), 'already-executed write action records commit execution guard' );

