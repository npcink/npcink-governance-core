<?php
/** Plan contracts test module. @package NpcinkGovernanceCore */

$plan_proposal_service    = npcink_governance_core_read( $root . '/includes/Governance/Plan_Proposal_Service.php' );
$plan_contract_validator  = npcink_governance_core_read( $root . '/includes/Governance/Plan_Contract_Validator.php' );
$plan_contract_components = $plan_proposal_service . "\n" . $plan_contract_validator;
npcink_governance_core_assert( false !== strpos( $plan_contract_validator, 'final class Plan_Contract_Validator' ), 'Plan contract validator class exists.' );
npcink_governance_core_assert( false !== strpos( $plan_proposal_service, '$this->validator->supports( $plan_ability_id )' ), 'Plan proposal service delegates allowlist checks to the validator.' );
npcink_governance_core_assert( false !== strpos( $plan_proposal_service, '$this->validator->validate( $plan_ability_id, $plan )' ), 'Plan proposal service delegates common and ability-specific validation to the validator.' );
npcink_governance_core_assert( false === strpos( $plan_proposal_service, 'private $allowed_plan_abilities' ), 'Plan proposal service no longer owns the plan ability allowlist.' );
foreach (
	array(
		'validate_plan_contract',
		'validate_article_write_plan_contract',
		'validate_article_batch_write_plan_contract',
		'validate_article_media_batch_write_plan_contract',
		'validate_image_candidate_adoption_plan_contract',
		'validate_image_candidate_adoption_action',
		'validate_article_audio_adoption_plan_contract',
		'validate_site_knowledge_review_plan_contract',
		'validate_nightly_inspection_review_plan_contract',
		'validate_content_metadata_apply_plan_contract',
		'validate_media_alt_apply_plan_contract',
		'validate_media_optimization_plan_contract',
		'validate_media_adoption_enhancement_plan_contract',
		'validate_media_adoption_enhancement_action',
		'validate_media_rename_plan_contract',
		'validate_article_optimization_apply_plan_contract',
		'validate_article_block_plan_contract',
		'validate_pattern_page_plan_contract',
		'validate_block_theme_site_plan_contract',
		'validate_block_theme_site_blocks',
		'validate_block_theme_site_layout_contract',
		'validate_draft_blocks_batch_actions',
		'validate_article_artifacts',
		'validate_article_draft_action',
		'validate_article_media_batch_action',
		'validate_flagged_media_removal_plan_contract',
		'validate_flagged_media_reference_patch_action',
	) as $validator_method
) {
	npcink_governance_core_assert( false !== strpos( $plan_contract_validator, 'function ' . $validator_method . '(' ), 'Plan contract validator owns method: ' . $validator_method );
	npcink_governance_core_assert( false === strpos( $plan_proposal_service, 'function ' . $validator_method . '(' ), 'Plan proposal service no longer owns validator method: ' . $validator_method );
}
foreach (
	array(
		'inspect_block_theme_site_blocks',
		'block_theme_site_allowed_blocks',
		'block_theme_site_allowed_template_slugs',
		'block_theme_site_block_contains_forbidden_html',
		'block_class_names',
		'sanitize_block_class_name',
		'is_exact_output_reference',
		'is_valid_absolute_url',
		'article_workflow_artifact_keys',
		'nightly_inspection_core_intake_package',
		'nightly_inspection_selected_review_items',
	) as $validator_helper
) {
	npcink_governance_core_assert( false !== strpos( $plan_contract_validator, 'function ' . $validator_helper . '(' ), 'Plan contract validator owns helper: ' . $validator_helper );
	npcink_governance_core_assert( false === strpos( $plan_proposal_service, 'function ' . $validator_helper . '(' ), 'Plan proposal service no longer defines validator helper: ' . $validator_helper );
}
foreach (
	array(
		'Plan_Proposal_Service',
		'npcink-abilities-toolkit/build-content-inventory-fix-plan',
		'npcink-abilities-toolkit/build-nonproduction-content-cleanup-plan',
		'npcink-abilities-toolkit/build-media-inventory-fix-plan',
		'npcink-abilities-toolkit/build-media-reference-repair-plan',
		'npcink-abilities-toolkit/build-media-settings-reference-repair-plan',
		'npcink-abilities-toolkit/build-media-optimization-plan',
		'npcink-abilities-toolkit/build-media-adoption-enhancement-plan',
		'npcink-abilities-toolkit/build-media-rename-plan',
		'npcink-abilities-toolkit/build-article-optimization-apply-plan',
		'npcink-abilities-toolkit/build-article-block-plan',
		'npcink-abilities-toolkit/build-pattern-page-plan',
		'npcink-abilities-toolkit/build-block-theme-site-plan',
		'npcink-toolbox/build-article-write-plan',
		'npcink-toolbox/build-article-batch-write-plan',
		'npcink-toolbox/build-article-media-batch-write-plan',
		'npcink-abilities-toolkit/build-image-candidate-adoption-plan',
		'npcink-abilities-toolkit/build-article-audio-adoption-plan',
		'npcink-toolbox/build-site-knowledge-review-plan',
		'npcink-toolbox/build-nightly-inspection-review-plan',
		'npcink-abilities-toolkit/build-content-metadata-apply-plan',
		'npcink-abilities-toolkit/build-media-alt-apply-plan',
		'npcink-abilities-toolkit/build-flagged-media-removal-plan',
		'proposal.plan_ingested',
		'npcink-abilities-toolkit/delete-media-permanently',
		'npcink-abilities-toolkit/delete-attachment',
		'destructive_media_delete_not_explicitly_included',
		'validate_flagged_media_removal_plan_contract',
		'validate_flagged_media_reference_patch_action',
		'flagged_media_removal_preview',
		'flagged_media_removal_proposal_summary',
		'flagged_media_removal_audit_description',
		'is_flagged_media_removal_proposal',
		'function flagged_media_removal_context(',
		'function flagged_media_removal_branch(',
		'is_valid_deletion_policy_branch',
		'FLAGGED_MEDIA_REMOVAL_PLAN_ABILITY',
		'governed_with_backup',
		'illegal_content_no_backup',
		'no_backup_attestation',
		"'backup_evidence'",
		'restore_ability_contract',
		'flagged_media_removal_plan',
		'validate_article_write_plan_contract',
		'validate_article_batch_write_plan_contract',
		'validate_article_block_plan_contract',
		'validate_article_media_batch_write_plan_contract',
		'validate_image_candidate_adoption_plan_contract',
		'validate_site_knowledge_review_plan_contract',
		'validate_nightly_inspection_review_plan_contract',
		'validate_content_metadata_apply_plan_contract',
		'validate_media_alt_apply_plan_contract',
		'validate_media_optimization_plan_contract',
		'validate_media_adoption_enhancement_plan_contract',
		'validate_media_rename_plan_contract',
		'validate_article_optimization_apply_plan_contract',
		'validate_pattern_page_plan_contract',
		'validate_block_theme_site_plan_contract',
		'article_workflow_preview',
		'article_batch_workflow_preview',
		'article_media_batch_workflow_preview',
		'media_optimization_preview',
		'media_optimization_proposal_summary',
		'media_adoption_enhancement_preview',
		'media_rename_preview',
		'article_optimization_preview',
		'pattern_page_preview',
		'block_theme_site_preview',
		'site_knowledge_review_preview',
		'nightly_inspection_review_preview',
		'nightly_inspection_core_intake_package',
		'nightly_inspection_selected_review_items',
		'content_metadata_apply_preview',
		'article_workflow_artifact_keys',
		'article_write_plan',
		'article_batch_write_plan',
		'article_media_batch_write_plan',
		'article_optimization_apply_plan',
		'article_block_plan',
		'block_theme_site_plan',
		'image_candidate_adoption_plan',
		'site_knowledge_review_plan',
		'nightly_site_inspection_review_plan',
		'nightly_site_inspection_core_review_plan.v1',
		'content_metadata_apply_plan',
		'media_alt_apply_plan.v1',
		'expected_current_alt',
		'operator_visual_review_confirmed',
		'image_candidate.v1',
		'npcink_governance_core_site_knowledge_ready_rejected',
		'npcink_governance_core_site_knowledge_evidence_missing',
		'npcink_governance_core_nightly_inspection_ready_rejected',
		'npcink_governance_core_nightly_inspection_evidence_missing',
		'npcink_governance_core_nightly_inspection_review_item_missing',
		'draft_selected_review_item_before_commit_preflight',
		'selected_review_item_ids',
		'selected_review_items',
		'cloud_core_intake_package',
		'npcink_governance_core_content_metadata_create_missing_rejected',
		'npcink_governance_core_content_metadata_authorization_rejected',
		'npcink_governance_core_content_metadata_update_field_rejected',
		'npcink_governance_core_content_metadata_term_field_rejected',
		'npcink_governance_core_content_metadata_duplicate_action_rejected',
		'media_optimization_plan',
		'media_adoption_enhancement_plan',
		'media_rename_plan',
		'pattern_page_plan',
		'article_goal_brief',
		'research_evidence_pack',
		'article_outline',
		'article_draft_candidate',
		'discoverability_pack',
		'article_risk_report',
		'ARTICLE_BATCH_MAX_ACTIONS',
		'ARTICLE_MEDIA_BATCH_MAX_ARTICLES',
		'ARTICLE_MEDIA_BATCH_MAX_ACTIONS',
		'PLAN_MAX_WRITE_ACTIONS',
		'PLAN_MAX_PAYLOAD_BYTES',
		'MEDIA_OPTIMIZATION_MAX_ACTIONS',
		'BLOCK_THEME_SITE_MAX_ACTIONS',
		'npcink_governance_core_plan_too_many_actions',
		'npcink_governance_core_plan_payload_too_large',
		'npcink_governance_core_article_batch_mode_required',
		'npcink_governance_core_article_media_batch_mode_required',
		'npcink_governance_core_article_media_batch_candidate_missing',
		'npcink_governance_core_image_candidate_contract_missing',
		'npcink_governance_core_image_candidate_source_type_invalid',
		'npcink_governance_core_media_optimization_batch_required',
		'npcink_governance_core_media_optimization_actions_rejected',
		'npcink_governance_core_media_optimization_attachment_mismatch',
		'npcink_governance_core_flagged_media_removal_plan_invalid',
		'npcink_governance_core_flagged_media_removal_batch_required',
		'npcink_governance_core_flagged_media_removal_context_missing',
		'npcink_governance_core_flagged_media_removal_branch_invalid',
		'npcink_governance_core_flagged_media_backup_evidence_missing',
		'npcink_governance_core_flagged_media_attestation_missing',
		'npcink_governance_core_flagged_media_backup_evidence_conflict',
		'npcink_governance_core_flagged_media_attestation_conflict',
		'npcink_governance_core_flagged_media_delete_branch_mismatch',
		'npcink_governance_core_flagged_media_delete_attachment_mismatch',
		'npcink_governance_core_flagged_media_delete_idempotency_mismatch',
		'npcink_governance_core_flagged_media_target_rejected',
		'npcink_governance_core_flagged_media_delete_action_missing',
		'npcink_governance_core_flagged_media_patch_invalid',
		'npcink_governance_core_flagged_media_reference_actions_rejected',
		'FLAGGED_MEDIA_MAX_REFERENCE_ACTIONS',
		'npcink_governance_core_media_adoption_enhancement_actions_missing',
		'npcink_governance_core_media_adoption_enhancement_patch_invalid',
		'npcink_governance_core_media_rename_target_file_missing',
		'npcink_governance_core_media_rename_attachment_mismatch',
		'npcink_governance_core_block_theme_site_actions_rejected',
		'npcink_governance_core_block_theme_site_layout_profile_rejected',
		'npcink_governance_core_block_theme_site_layout_contract_rejected',
		'npcink_governance_core_block_theme_site_block_rejected',
		'npcink_governance_core_block_theme_site_block_tree_rejected',
		'npcink_governance_core_block_theme_site_roundtrip_required',
		'npcink_governance_core_block_theme_site_template_rejected',
		'customize_template_layout',
		'template_layout_contract',
		'block_theme_profile_compiler@0.3',
		'block_theme_safe_core_blocks@0.2',
		'accepted_profile_versions',
		'page_standard@0.2',
		'homepage_landing@0.3',
		'replace_template_layout_with_preserved_template_parts',
		'article_standard',
		'homepage_landing',
		'core/categories',
		'core/post-terms',
		'core/post-navigation-link',
		'core/comments',
		'BLOCK_THEME_SITE_MAX_BLOCKS',
		'BLOCK_THEME_SITE_MAX_BLOCK_DEPTH',
		'block_theme_site_allowed_blocks',
		'block_theme_site_allowed_template_slugs',
		'npcink_governance_core_pattern_page_class_rejected',
		'npcink_governance_core_article_plan_',
		'npcink_governance_core_article_batch_',
		'npcink_governance_core_article_media_batch_',
		'publish_rejected',
		'blocked_claims',
		'risk_blocked',
		'target_rejected',
		'npcink-abilities-toolkit/create-draft',
		'npcink-abilities-toolkit/upload-media-from-url',
		'npcink-abilities-toolkit/optimize-media-asset',
		'npcink-abilities-toolkit/set-post-featured-image',
		'proposal_ready',
		'needs_input',
		'preflight_blockers',
		'batch_review_summary',
		'core-batch-review-summary-v1',
		'operator_next_action',
		'resolve_blocked_items_before_commit_preflight',
		'needs_input_resolution_owner',
		'toolbox_morning_brief_operator',
		'resubmission_required',
		'core_amendment_supported',
		'final_execution_owner',
		'plan_requires_batch_proposal',
		'plan_to_proposal_batch',
		'batch_approval',
		'proposal_mode',
		'depends_on',
		'skipped_destructive_candidates',
		'manual_review',
		'commit_execution',
		'dry_run',
		'Repair inline references',
		'Preserve the original file as a local backup for rollback',
	) as $required
) {
	npcink_governance_core_assert( false !== strpos( $plan_contract_components, $required ), 'Plan-to-proposal components contain required text: ' . $required );
}

$smoke_wp = npcink_governance_core_test_sources( $root, 'smoke-wp' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'include_unattached_nonproduction_media' ), 'Smoke test media delete fixture opts into abilities-side test media delete policy.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'npcink_governance_core_smoke_register_post_fixture' ), 'Smoke test registers post fixtures for cleanup.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'npcink_governance_core_smoke_register_comment_fixture' ), 'Smoke test registers comment fixtures for cleanup.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'npcink_governance_core_smoke_register_attachment_fixture' ), 'Smoke test registers media attachment fixtures for cleanup.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'npcink_governance_core_smoke_register_term_fixture' ), 'Smoke test registers taxonomy term fixtures for cleanup.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'npcink_governance_core_smoke_register_app_key_fixture' ), 'Smoke test registers app key fixtures for revocation.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'app-authenticated token cannot list admin-only app keys' ), 'Smoke test proves app tokens cannot list admin-only app keys.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'app-authenticated token cannot rotate admin-only app keys' ), 'Smoke test proves app tokens cannot rotate admin-only app keys.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'register_shutdown_function' ), 'Smoke test runs fixture cleanup on shutdown.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'wp_delete_post' ), 'Smoke test permanently deletes post fixtures.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'wp_delete_comment' ), 'Smoke test permanently deletes comment fixtures.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'wp_delete_attachment' ), 'Smoke test permanently deletes media attachment fixtures.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'wp_delete_term' ), 'Smoke test deletes taxonomy term fixtures.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'revoke_by_key_id' ), 'Smoke test revokes app key fixtures.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'history cleanup cron event is scheduled' ), 'WordPress smoke validates history cleanup scheduling.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'history cleanup deletes old historical proposals' ), 'WordPress smoke validates historical proposal cleanup.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'history cleanup deletes old revoked access tokens' ), 'WordPress smoke validates revoked token cleanup.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'history cleanup writes completion audit evidence' ), 'WordPress smoke validates cleanup audit evidence.' );
npcink_governance_core_assert( false !== strpos( $smoke_wp, 'NPCINK_GOVERNANCE_CORE_SMOKE_PURGE' ), 'Smoke test keeps governance row purge opt-in.' );
npcink_governance_core_assert( false !== strpos( $testing_strategy, 'Proposal and audit rows remain persistent by default' ), 'Testing strategy keeps governance rows persistent by default.' );
npcink_governance_core_assert( false !== strpos( $development_workflow, 'NPCINK_GOVERNANCE_CORE_SMOKE_PURGE=1' ), 'Development workflow documents optional smoke purge.' );

$plan_to_proposal_docs = npcink_governance_core_read( $root . '/docs/plan-to-proposal-governance.md' );
$media_alt_governance = npcink_governance_core_read( $root . '/docs/media-alt-governance-scenario.md' );
$canonical_plan_ability_ids = array(
	'npcink-abilities-toolkit/build-content-inventory-fix-plan',
	'npcink-abilities-toolkit/build-nonproduction-content-cleanup-plan',
	'npcink-abilities-toolkit/build-media-inventory-fix-plan',
	'npcink-abilities-toolkit/build-media-reference-repair-plan',
	'npcink-abilities-toolkit/build-media-settings-reference-repair-plan',
	'npcink-abilities-toolkit/build-media-optimization-plan',
	'npcink-abilities-toolkit/build-media-adoption-enhancement-plan',
	'npcink-abilities-toolkit/build-media-rename-plan',
	'npcink-abilities-toolkit/build-article-optimization-apply-plan',
	'npcink-abilities-toolkit/build-article-block-plan',
	'npcink-abilities-toolkit/build-pattern-page-plan',
	'npcink-abilities-toolkit/build-block-theme-site-plan',
	'npcink-toolbox/build-article-write-plan',
	'npcink-toolbox/build-article-batch-write-plan',
	'npcink-toolbox/build-article-media-batch-write-plan',
	'npcink-abilities-toolkit/build-image-candidate-adoption-plan',
	'npcink-abilities-toolkit/build-article-audio-adoption-plan',
	'npcink-toolbox/build-site-knowledge-review-plan',
	'npcink-toolbox/build-nightly-inspection-review-plan',
	'npcink-abilities-toolkit/build-content-metadata-apply-plan',
	'npcink-abilities-toolkit/build-flagged-media-removal-plan',
);
foreach ( $canonical_plan_ability_ids as $ability_id ) {
	npcink_governance_core_assert( false !== strpos( $plan_contract_validator, $ability_id ), 'Plan validator allowlist contains canonical plan ability: ' . $ability_id );
	npcink_governance_core_assert( false !== strpos( $governance, $ability_id ), 'Governance contract contains canonical plan ability: ' . $ability_id );
	npcink_governance_core_assert( false !== strpos( $rest_contract, $ability_id ), 'REST contract contains canonical plan ability: ' . $ability_id );
	npcink_governance_core_assert( false !== strpos( $ability_intake, $ability_id ), 'Ability intake contract contains canonical plan ability: ' . $ability_id );
	npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, $ability_id ), 'Plan-to-proposal docs contain canonical plan ability: ' . $ability_id );
}
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'include_unattached_nonproduction_media' ), 'Plan-to-proposal docs mention abilities-side unattached test media delete gate.' );
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'include_trash_parent_media' ), 'Plan-to-proposal docs mention abilities-side trash-parent media delete gate.' );
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'npcink-toolbox/build-article-write-plan' ), 'Plan-to-proposal docs include the Toolbox article writing handoff.' );
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'npcink-toolbox/build-article-batch-write-plan' ), 'Plan-to-proposal docs include the Toolbox article batch writing handoff.' );
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'npcink-toolbox/build-article-media-batch-write-plan' ), 'Plan-to-proposal docs include the Toolbox article media batch handoff.' );
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'npcink-toolbox/build-site-knowledge-review-plan' ), 'Plan-to-proposal docs include the Toolbox Site Knowledge review handoff.' );
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'npcink-toolbox/build-nightly-inspection-review-plan' ), 'Plan-to-proposal docs include the Toolbox Nightly Inspection review handoff.' );
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'npcink-abilities-toolkit/build-content-metadata-apply-plan' ), 'Plan-to-proposal docs include the Toolkit content metadata apply handoff.' );
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'npcink-abilities-toolkit/build-media-alt-apply-plan' ), 'Plan-to-proposal docs include the Toolkit missing-ALT apply handoff.' );
npcink_governance_core_assert( false !== strpos( $media_alt_governance, 'media_alt_apply_plan.v1' ) && false !== strpos( $media_alt_governance, 'expected_current_alt' ) && false !== strpos( $media_alt_governance, 'requires_live_value_check=true' ), 'Missing media ALT scenario documents the shared contract, old-value guard, and live Adapter check.' );
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'blocked draft-review proposal' ), 'Plan-to-proposal docs keep Site Knowledge review non-executable before human input.' );
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'review this Morning Brief item in Core' ) && false !== strpos( $plan_to_proposal_docs, 'preview.nightly_inspection_review' ) && false !== strpos( $plan_to_proposal_docs, 'selected Morning Brief review item ids/items' ), 'Plan-to-proposal docs preserve Nightly Morning Brief selected-item review context.' );
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'needs_input_resolution_owner=toolbox_morning_brief_operator' ) && false !== strpos( $plan_to_proposal_docs, 'Return to Toolbox Morning Brief' ) && false !== strpos( $plan_to_proposal_docs, 'Core does not generate or edit missing draft fields' ), 'Plan-to-proposal docs route Nightly missing-input resolution back to Toolbox Morning Brief.' );
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'npcink-abilities-toolkit/build-media-optimization-plan' ), 'Plan-to-proposal docs include the media optimization handoff.' );
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'npcink-abilities-toolkit/build-media-adoption-enhancement-plan' ), 'Plan-to-proposal docs include the media adoption enhancement handoff.' );
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'npcink-abilities-toolkit/build-media-rename-plan' ), 'Plan-to-proposal docs include the media rename handoff.' );
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'npcink-abilities-toolkit/build-article-optimization-apply-plan' ), 'Plan-to-proposal docs include the article optimization apply handoff.' );
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'npcink-abilities-toolkit/build-article-block-plan' ), 'Plan-to-proposal docs include the article block handoff.' );
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'npcink-abilities-toolkit/build-pattern-page-plan' ), 'Plan-to-proposal docs include the pattern page handoff.' );
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'npcink-abilities-toolkit/build-block-theme-site-plan' ), 'Plan-to-proposal docs include the block theme site handoff.' );
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'core/categories' ), 'Plan-to-proposal docs document safe categories blocks for homepage layouts.' );
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'preview.article_workflow' ), 'Plan-to-proposal docs require article workflow preview evidence.' );
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'preview.article_optimization' ), 'Plan-to-proposal docs require article optimization preview evidence.' );
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'preview.article_block' ), 'Plan-to-proposal docs require article block preview evidence.' );
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'preview.pattern_page' ), 'Plan-to-proposal docs require pattern page preview evidence.' );
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'preview.block_theme_site' ), 'Plan-to-proposal docs require block theme site preview evidence.' );
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'preview.content_metadata_apply' ), 'Plan-to-proposal docs require content metadata apply preview evidence.' );
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'proposal_mode=batch' ), 'Plan-to-proposal docs require explicit batch proposal mode where needed.' );
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'optimize these reviewed media items' ), 'Plan-to-proposal docs define media optimization as a reviewed media intent.' );
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'include paired metadata and derivative actions for' ), 'Plan-to-proposal docs define media optimization attachment pairing.' );
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'each attachment in the plan' ), 'Plan-to-proposal docs define media optimization per-attachment pairing.' );
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'adopt this reviewed remote image into a page' ), 'Plan-to-proposal docs define media adoption enhancement intent.' );
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'preview.media_adoption_enhancement' ), 'Plan-to-proposal docs require media adoption enhancement preview evidence.' );
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'optimize this existing article' ), 'Plan-to-proposal docs define article optimization as an existing-content intent.' );
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'target_file_name' ), 'Plan-to-proposal docs require reviewed media rename target filename.' );
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'Article writing is a local Ability recipe' ), 'Plan-to-proposal docs treat article writing as local Ability recipe.' );
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'must not produce article drafts' ), 'Plan-to-proposal docs prohibit Cloud draft generation.' );
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'Plan Handoff Admission Checklist' ), 'Plan-to-proposal docs define a plan handoff admission checklist.' );
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'fixed external owner' ), 'Plan-to-proposal docs require fixed external plan owners.' );
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'explicit and allowlisted' ), 'Plan-to-proposal docs keep plan ability ids explicit and allowlisted.' );
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'maximum action count' ), 'Plan-to-proposal docs require bounded action counts for new handoffs.' );
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'not a generic workflow runtime' ), 'Plan-to-proposal docs reject generic workflow runtime ownership.' );
npcink_governance_core_assert( false !== strpos( $operation_classification, 'Media Optimization Regression Rule' ), 'Operation classification docs lock media optimization batch plans to Core proposal review.' );
npcink_governance_core_assert( false !== strpos( $operation_classification, 'Adapter owns derived readiness and execution state' ), 'Operation classification docs keep Adapter ownership for media execution state.' );
npcink_governance_core_assert( false !== strpos( $next_stage_plan, 'regression-owned cross-repo path' ), 'Next stage plan treats media optimization as a cross-repo regression path.' );
npcink_governance_core_assert( false !== strpos( $next_stage_plan, 'replacement rule counts versus actual replacements' ), 'Next stage plan keeps media replacement count verification in Abilities.' );
npcink_governance_core_assert( false !== strpos( $next_stage_plan, 'stop expanding the media optimization implementation in Core' ), 'Next stage plan records the media optimization stop decision.' );
npcink_governance_core_assert( false !== strpos( $next_stage_plan, 'classifier-driven authorization paths' ), 'Next stage plan redirects new product energy to classifier-driven authorization paths.' );
npcink_governance_core_assert( false !== strpos( $next_stage_plan, 'Core no longer keeps the' ) && false !== strpos( $next_stage_plan, 'article recipe or Cloud bulk-writing planning contracts locally' ), 'Next stage plan moves article recipe contracts outside Core.' );

foreach (
	array(
		'docs/article-writing-workflow-contract.md',
		'docs/ability-recipe-orchestration-contract.md',
		'docs/cloud-bulk-article-run-contract.md',
		'docs/content-metadata-delta-implementation-prompt.md',
	) as $removed_path
) {
	npcink_governance_core_assert( ! file_exists( $root . '/' . $removed_path ), 'Core must not keep external product contract artifact: ' . $removed_path );
}
npcink_governance_core_assert( false !== strpos( $external_owner_boundary, 'Article recipe orchestration and Article Assistant UX' ), 'External owner boundary note moves article recipe ownership out of Core.' );
npcink_governance_core_assert( false !== strpos( $external_owner_boundary, 'Cloud must remain runtime/detail only' ), 'External owner boundary note keeps Cloud writing out of Core.' );
npcink_governance_core_assert( false !== strpos( $external_owner_boundary, 'Cloud Addon connection and optional telemetry collection' ), 'External owner boundary note keeps Cloud Addon connection ownership outside Core.' );
npcink_governance_core_assert( false !== strpos( $external_owner_boundary, 'optional local metadata pointers only' ), 'External owner boundary note limits Core Cloud Addon role to metadata pointers.' );
npcink_governance_core_assert( false !== strpos( $external_owner_boundary, 'Adapter channel execution and status readback' ), 'External owner boundary note keeps Adapter channel execution outside Core.' );
npcink_governance_core_assert( false !== strpos( $external_owner_boundary, 'record external execution outcomes only' ), 'External owner boundary note limits Core to external execution outcome records.' );
npcink_governance_core_assert( false !== strpos( $external_owner_boundary, 'Govern reviewed apply plans only' ), 'External owner boundary note moves Content Metadata Delta product workbench out of Core.' );
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'standard article artifacts documented by the owning product or ability' ), 'Plan-to-proposal docs defer article workflow contract to external owner.' );
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'Core no longer keeps the article workflow contract locally' ), 'Plan-to-proposal docs do not keep article workflow contract in Core.' );
npcink_governance_core_assert( false !== strpos( $plan_to_proposal_docs, 'Recipe orchestration and Cloud-writing' ), 'Plan-to-proposal docs keep recipe and Cloud-writing details outside Core.' );

$rest_api_contract = npcink_governance_core_read( $root . '/docs/rest-api-contract.md' );
npcink_governance_core_assert( false !== strpos( $rest_api_contract, 'npcink-toolbox/build-nightly-inspection-review-plan' ) && false !== strpos( $rest_api_contract, 'source_context.cloud_core_intake_package.selected_review_items' ) && false !== strpos( $rest_api_contract, 'preview.nightly_inspection_review' ), 'REST API contract documents Nightly Morning Brief Core intake shape.' );
npcink_governance_core_assert( false !== strpos( $rest_api_contract, 'needs_input_resolution_owner' ) && false !== strpos( $rest_api_contract, 'toolbox_morning_brief_operator' ) && false !== strpos( $rest_api_contract, 'Core does not generate or edit missing draft fields' ), 'REST API contract documents Nightly Morning Brief needs-input resubmission path.' );
npcink_governance_core_assert( false !== strpos( $rest_api_contract, 'recording external execution results' ), 'REST API contract defines record-execution as external execution result recording.' );
npcink_governance_core_assert( false !== strpos( $rest_api_contract, 'not as Core execution' ), 'REST API contract keeps record-execution wording out of Core execution ownership.' );
$approval_commit_contract = npcink_governance_core_read( $root . '/docs/approval-commit-contract.md' );
npcink_governance_core_assert( false !== strpos( $approval_commit_contract, 'records an external execution result only' ), 'Approval commit contract limits record-execution to external execution result recording.' );
npcink_governance_core_assert( false !== strpos( $approval_commit_contract, 'not a Core execute' ), 'Approval commit contract rejects Core execute semantics for record-execution.' );
$ability_intake_contract = npcink_governance_core_read( $root . '/docs/ability-intake-contract.md' );
npcink_governance_core_assert( false !== strpos( $ability_intake_contract, 'npcink-toolbox/build-nightly-inspection-review-plan' ) && false !== strpos( $ability_intake_contract, 'selected Morning Brief review item' ) && false !== strpos( $ability_intake_contract, 'Cloud scheduling, retry state' ), 'Ability intake contract documents Nightly Inspection as selected-item review handoff only.' );
npcink_governance_core_assert( false !== strpos( $ability_intake_contract, 'Toolbox Morning Brief operator' ) && false !== strpos( $ability_intake_contract, 'complete proposal resubmission' ) && false !== strpos( $ability_intake_contract, 'Core does not generate or edit missing draft fields' ), 'Ability intake contract documents Nightly missing-input owner and Core non-amendment boundary.' );

$admin_page = npcink_governance_core_admin_sources( $root );
npcink_governance_core_assert( false !== strpos( $admin_page, 'render_nightly_inspection_review_context' ) && false !== strpos( $admin_page, 'Nightly review item' ) && false !== strpos( $admin_page, 'Morning Brief handoff' ), 'Admin proposal detail renders Nightly Morning Brief selected review item context.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Required next step' ) && false !== strpos( $admin_page, 'Return to Toolbox Morning Brief' ) && false !== strpos( $admin_page, 'resubmit a complete Core proposal' ) && false !== strpos( $admin_page, 'Core does not generate or edit missing draft fields' ), 'Admin proposal detail gives explicit Nightly needs-input recovery guidance.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Morning Brief source' ) && false !== strpos( $admin_page, 'Selected review items' ) && false !== strpos( $admin_page, 'Evidence refs' ) && false !== strpos( $admin_page, 'Completed draft input' ), 'Admin proposal detail exposes Nightly source, selected review items, evidence refs, and completed draft input.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Review the completed Morning Brief draft in Core' ) && false !== strpos( $admin_page, 'run commit preflight before Adapter execution' ) && false !== strpos( $admin_page, 'Core still does not generate or edit draft fields' ), 'Admin proposal detail gives explicit completed Nightly draft review guidance.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'npcink-toolbox/build-nightly-inspection-review-plan' ) && false !== strpos( $admin_page, 'Nightly Inspection' ) && false !== strpos( $admin_page, 'Morning Brief' ), 'Admin review queue summarizes Nightly Inspection proposal source as Morning Brief.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'render_activity_overview' ) && false !== strpos( $admin_page, 'count_grouped_by_event_name' ) && false !== strpos( $admin_page, 'npcink-governance-core-activity-overview' ), 'Admin audit view renders a linked AI activity overview strip.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'Requests created' ) && false !== strpos( $admin_page, 'Preflight checks' ) && false !== strpos( $admin_page, 'Approved writes completed through Adapter execution profiles' ), 'Admin activity overview summarizes creation, approval, preflight, and execution outcomes.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'audit_event_name' ) && false !== strpos( $admin_page, 'audit_time_range' ) && false !== strpos( $admin_page, 'npcink-governance-core-summary-link' ), 'Admin activity overview counters link into the filtered activity list.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'render_single_action_input_summary' ) && false !== strpos( $admin_page, 'Submitted action input' ) && false !== strpos( $admin_page, 'render_review_input_value' ), 'Admin proposal actions tab renders submitted single-action input instead of an empty plan.' );
npcink_governance_core_assert( false !== strpos( $admin_page, 'registry_ability_label' ) && false !== strpos( $admin_page, 'registry_label_cache' ), 'Admin request labels fall back to cached intake registry labels for third-party abilities.' );
$governance_event_sources = '';
$governance_event_paths   = array( $root . '/includes/*.php' );
foreach ( array( 'Governance', 'Security', 'Rest', 'Admin', 'Capabilities', 'Audit' ) as $governance_event_dir ) {
	$governance_event_paths[] = $root . '/includes/' . $governance_event_dir . '/*.php';
}
foreach ( $governance_event_paths as $governance_event_path ) {
	foreach ( (array) glob( $governance_event_path ) as $governance_event_file ) {
		$governance_event_sources .= npcink_governance_core_read( $governance_event_file );
	}
}
preg_match_all( "/'(?:proposal|commit|read_request|app|local_admin_consent|audit|capabilities|core)\.[a-z_]+'/", $governance_event_sources, $governance_event_literal_matches );
foreach ( array_unique( $governance_event_literal_matches[0] ) as $governance_event_literal ) {
	$governance_event_name = trim( $governance_event_literal, "'" );
	npcink_governance_core_assert( 2 <= preg_match_all( "/'" . preg_quote( $governance_event_name, '/' ) . "'\s*=> __\(/", $admin_page ), 'Admin audit surfaces label and filter every emitted governance event: ' . $governance_event_name );
}
npcink_governance_core_assert( false !== strpos( $admin_page, 'Expired unreviewed' ) && false !== strpos( $admin_page, "'audit_event_name' => 'proposal.expired'" ) && false !== strpos( $admin_page, 'Review expired requests' ) && false !== strpos( $admin_page, 'PENDING_TTL_SECONDS / HOUR_IN_SECONDS' ), 'Admin review queue surfaces expired-unreviewed proposals with a filtered activity link.' );

