<?php
/** Metadata and consent fixtures test module. @package NpcinkGovernanceCore */

/**
 * Creates a representative content metadata apply plan.
 *
 * @return array<string,mixed>
 */
function npcink_governance_core_fail_closed_content_metadata_apply_plan(): array {
	return array(
		'artifact_type'          => 'content_metadata_apply_plan',
		'composition_role'       => 'core_content_metadata_apply_plan',
		'version'                => 1,
		'batch_id'               => 'content_metadata_apply_fault_injection',
		'requires_approval'      => true,
		'dry_run'                => true,
		'commit_execution'       => false,
		'proposal_mode'          => 'batch',
		'batch_approval'         => true,
		'write_posture'          => 'core_proposal_handoff',
		'direct_wordpress_write' => false,
		'post'                   => array(
			'post_id'     => 1493,
			'post_type'   => 'post',
			'post_status' => 'draft',
			'title'       => 'Metadata review target',
		),
		'accepted_choices'       => array(
			'excerpt_selected' => true,
			'category_ids'     => array( 31 ),
			'tag_ids'          => array( 41, 42 ),
			'new_term_policy'  => 'manual_review_only_no_create_term_action',
		),
		'authorization'          => array(
			'classification'    => 'core_proposal_required',
			'reason'            => 'content_metadata_apply_plan_uses_core_review',
			'decision_envelope' => array(
				'decision_version'  => 'operation-classification-v1',
				'classification'    => 'core_proposal_required',
				'reasons'           => array( 'reviewed_metadata_apply_plan' ),
				'risk_factors'      => array( 'batch_or_multi_action_metadata_apply' ),
				'required_evidence' => array( 'target_ability_id', 'before_after_or_dry_run_evidence' ),
				'request_source'    => 'external_adapter',
				'actor_presence'   => 'delegated',
				'preview_completeness' => 'sufficient',
				'scope'             => 'one_object',
				'reversibility'     => 'easy_undo',
				'operation_kind'    => 'batch_plan',
				'writes_wordpress_state' => true,
			),
		),
		'evidence_refs'          => array(
			array(
				'id'      => 'target-post',
				'type'    => 'target_post',
				'post_id' => 1493,
			),
		),
		'new_term_candidates'    => array(
			array(
				'name'   => 'Review Only Term',
				'status' => 'review_only_vocabulary_gap',
			),
		),
		'preview'                => array(
			array(
				'action_id' => 'content_metadata_apply',
				'post_id'   => 1493,
				'before'    => array(
					'excerpt'      => '',
					'category_ids' => array(),
					'tag_ids'      => array(),
				),
				'after_suggestion' => array(
					'excerpt'       => 'Reviewed metadata excerpt.',
					'category_ids'  => array( 31 ),
					'category_mode' => 'append',
					'tag_ids'       => array( 41, 42 ),
					'tag_mode'      => 'append',
				),
			),
		),
		'manual_review'          => array(
			array(
				'code'   => 'new_term_candidates_not_applied',
				'fields' => array( 'new_term_candidates' ),
			),
		),
		'write_actions'          => array(
			array(
				'action_id'         => 'apply_selected_excerpt',
				'target_ability_id' => 'npcink-abilities-toolkit/update-post',
				'input'             => array(
					'post_id'         => 1493,
					'excerpt'         => 'Reviewed metadata excerpt.',
					'dry_run'         => true,
					'commit'          => false,
					'idempotency_key' => 'metadata-excerpt-fixture',
				),
				'risk'              => 'low',
				'requires_approval' => true,
				'commit_execution'  => false,
				'proposal_ready'    => true,
			),
			array(
				'action_id'         => 'assign_existing_categories',
				'target_ability_id' => 'npcink-abilities-toolkit/set-post-terms',
				'input'             => array(
					'post_id'         => 1493,
					'taxonomy'        => 'category',
					'mode'            => 'append',
					'term_ids'        => array( 31 ),
					'create_missing'  => false,
					'dry_run'         => true,
					'commit'          => false,
					'idempotency_key' => 'metadata-categories-fixture',
				),
				'risk'              => 'medium',
				'requires_approval' => true,
				'commit_execution'  => false,
				'proposal_ready'    => true,
			),
			array(
				'action_id'         => 'assign_existing_tags',
				'target_ability_id' => 'npcink-abilities-toolkit/set-post-terms',
				'input'             => array(
					'post_id'         => 1493,
					'taxonomy'        => 'post_tag',
					'mode'            => 'append',
					'term_ids'        => array( 41, 42 ),
					'create_missing'  => false,
					'dry_run'         => true,
					'commit'          => false,
					'idempotency_key' => 'metadata-tags-fixture',
				),
				'risk'              => 'low',
				'requires_approval' => true,
				'commit_execution'  => false,
				'proposal_ready'    => true,
			),
		),
		'risk'                   => array(
			'level' => 'medium',
		),
	);
}

/**
 * Returns local consent classification evidence for audit filter tests.
 *
 * @param string $classification Classification value.
 * @return array<string,mixed>
 */
function npcink_governance_core_fail_closed_local_consent_classification( string $classification = 'local_admin_consent' ): array {
	return array(
		'classification'    => $classification,
		'policy_version'    => 'operation-classification-v1',
		'decision_version'  => 'operation-classification-v1',
		'decision_envelope' => array(
			'decision_version'  => 'operation-classification-v1',
			'classification'    => $classification,
			'reasons'           => array( 'present_admin_single_visible_low_risk_write' ),
			'risk_factors'      => array(),
			'required_evidence' => array( 'actor_user_id', 'target_object_id', 'classification' ),
			'request_source'    => 'wp_admin_ui',
			'actor_presence'   => 'present_click',
			'preview_completeness' => 'exact_final',
			'scope'             => 'one_object',
			'reversibility'     => 'easy_undo',
			'operation_kind'    => 'update_metadata',
			'writes_wordpress_state' => true,
		),
	);
}

