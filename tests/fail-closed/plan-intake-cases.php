<?php
/** Plan intake cases test module. @package NpcinkGovernanceCore */

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$article_plan = npcink_governance_core_fail_closed_article_write_plan();
$article_result = $stack['service']->create_from_plan( 'npcink-toolbox/build-article-write-plan', $article_plan, array(), array( 'source' => 'toolbox_article_workflow' ) );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $article_result ), 'Valid Toolbox article write plan creates a Core proposal.' );
npcink_governance_core_fail_closed_assert( 1 === (int) ( $article_result['proposal_count'] ?? 0 ), 'Valid Toolbox article write plan creates exactly one proposal.' );
npcink_governance_core_fail_closed_assert( 'npcink-abilities-toolkit/create-draft' === (string) ( $article_result['proposals'][0]['ability_id'] ?? '' ), 'Valid Toolbox article write plan targets create-draft.' );
npcink_governance_core_fail_closed_assert( is_array( $article_result['proposals'][0]['preview']['article_workflow'] ?? null ), 'Valid Toolbox article write plan preserves article workflow preview.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$wpdb->fail_insert_event_names[] = 'proposal.plan_ingested';
$plan_ingest_audit_failure = $stack['service']->create_from_plan( 'npcink-toolbox/build-article-write-plan', npcink_governance_core_fail_closed_article_write_plan(), array(), array( 'source' => 'toolbox_article_workflow' ) );
npcink_governance_core_fail_closed_assert( is_wp_error( $plan_ingest_audit_failure ), 'Plan intake fails closed when aggregate plan_ingested audit cannot be stored.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_plan_ingest_audit_failed' === $plan_ingest_audit_failure->get_error_code(), 'Plan ingest audit failure uses stable error code.' );
npcink_governance_core_fail_closed_assert( 0 === count( $wpdb->rows( $proposal_table ) ), 'Plan ingest audit failure deletes created proposal rows.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$oversized_payload_plan = npcink_governance_core_fail_closed_article_write_plan();
$oversized_payload_plan['intake_padding'] = str_repeat( 'x', 263000 );
$oversized_payload_result = $stack['service']->create_from_plan( 'npcink-toolbox/build-article-write-plan', $oversized_payload_plan );
npcink_governance_core_fail_closed_assert( is_wp_error( $oversized_payload_result ), 'Oversized plan payload is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_plan_payload_too_large' === $oversized_payload_result->get_error_code(), 'Oversized plan payload rejection uses stable error code.' );
npcink_governance_core_fail_closed_assert( 0 === count( $wpdb->rows( $proposal_table ) ), 'Oversized plan payload stores no proposal row.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$oversized_proposal_payload = npcink_governance_core_fail_closed_payload();
$oversized_proposal_payload['preview']['padding'] = str_repeat( 'x', 263000 );
$oversized_proposal_result = $stack['proposal_service']->create( $oversized_proposal_payload );
npcink_governance_core_fail_closed_assert( is_wp_error( $oversized_proposal_result ), 'Oversized direct proposal payload is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_proposal_payload_too_large' === $oversized_proposal_result->get_error_code(), 'Oversized direct proposal payload uses stable error code.' );
npcink_governance_core_fail_closed_assert( 0 === count( $wpdb->rows( $proposal_table ) ), 'Oversized direct proposal stores no proposal row.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$publish_plan = npcink_governance_core_fail_closed_article_write_plan();
$publish_plan['write_actions'][0]['input']['status'] = 'publish';
$publish_result = $stack['service']->create_from_plan( 'npcink-toolbox/build-article-write-plan', $publish_plan );
npcink_governance_core_fail_closed_assert( is_wp_error( $publish_result ), 'Article write plan requesting publish is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_article_plan_publish_rejected' === $publish_result->get_error_code(), 'Article publish rejection uses stable error code.' );
npcink_governance_core_fail_closed_assert( 0 === count( $wpdb->rows( $proposal_table ) ), 'Rejected publish article plan stores no proposal row.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$blocked_claims_plan = npcink_governance_core_fail_closed_article_write_plan();
$blocked_claims_plan['article_risk_report']['blocked_claims'] = array( 'Unverified ranking guarantee.' );
$blocked_claims_result = $stack['service']->create_from_plan( 'npcink-toolbox/build-article-write-plan', $blocked_claims_plan );
npcink_governance_core_fail_closed_assert( is_wp_error( $blocked_claims_result ), 'Article write plan with blocked claims is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_article_plan_blocked_claims' === $blocked_claims_result->get_error_code(), 'Article blocked-claims rejection uses stable error code.' );
npcink_governance_core_fail_closed_assert( 0 === count( $wpdb->rows( $proposal_table ) ), 'Rejected blocked-claims article plan stores no proposal row.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$high_risk_plan = npcink_governance_core_fail_closed_article_write_plan();
$high_risk_plan['article_risk_report']['risk_level'] = 'high';
$high_risk_result = $stack['service']->create_from_plan( 'npcink-toolbox/build-article-write-plan', $high_risk_plan );
npcink_governance_core_fail_closed_assert( is_wp_error( $high_risk_result ), 'High-risk article write plan is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_article_plan_risk_blocked' === $high_risk_result->get_error_code(), 'Article high-risk rejection uses stable error code.' );
npcink_governance_core_fail_closed_assert( 0 === count( $wpdb->rows( $proposal_table ) ), 'Rejected high-risk article plan stores no proposal row.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$article_batch_plan = npcink_governance_core_fail_closed_article_batch_write_plan();
$article_batch_result = $stack['service']->create_from_plan( 'npcink-toolbox/build-article-batch-write-plan', $article_batch_plan, array(), array( 'source' => 'toolbox_article_batch_workflow' ) );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $article_batch_result ), 'Valid Toolbox article batch write plan creates a Core proposal.' );
npcink_governance_core_fail_closed_assert( 1 === (int) ( $article_batch_result['proposal_count'] ?? 0 ), 'Valid Toolbox article batch write plan creates one batch proposal.' );
$article_batch_proposal = is_array( $article_batch_result['proposals'][0] ?? null ) ? $article_batch_result['proposals'][0] : array();
npcink_governance_core_fail_closed_assert( 'plan_to_proposal_batch' === (string) ( $article_batch_proposal['preview']['source']['type'] ?? '' ), 'Article batch write plan stores batch proposal source.' );
npcink_governance_core_fail_closed_assert( 3 === count( (array) ( $article_batch_proposal['input']['write_actions'] ?? array() ) ), 'Article batch proposal stores all draft write actions.' );
npcink_governance_core_fail_closed_assert( is_array( $article_batch_proposal['preview']['article_batch_workflow'] ?? null ), 'Article batch proposal preserves batch workflow preview.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$article_batch_without_flag = npcink_governance_core_fail_closed_article_batch_write_plan();
$article_batch_without_flag['batch_approval'] = false;
$article_batch_without_flag['proposal_mode'] = 'single';
$article_batch_without_flag_result = $stack['service']->create_from_plan( 'npcink-toolbox/build-article-batch-write-plan', $article_batch_without_flag );
npcink_governance_core_fail_closed_assert( is_wp_error( $article_batch_without_flag_result ), 'Article batch write plan without explicit batch mode is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_article_batch_mode_required' === $article_batch_without_flag_result->get_error_code(), 'Article batch mode rejection uses stable error code.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$article_batch_publish_plan = npcink_governance_core_fail_closed_article_batch_write_plan();
$article_batch_publish_plan['write_actions'][1]['input']['status'] = 'publish';
$article_batch_publish_result = $stack['service']->create_from_plan( 'npcink-toolbox/build-article-batch-write-plan', $article_batch_publish_plan );
npcink_governance_core_fail_closed_assert( is_wp_error( $article_batch_publish_result ), 'Article batch write plan requesting publish is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_article_batch_publish_rejected' === $article_batch_publish_result->get_error_code(), 'Article batch publish rejection uses stable error code.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$article_media_batch_plan = npcink_governance_core_fail_closed_article_media_batch_write_plan();
$article_media_batch_result = $stack['service']->create_from_plan( 'npcink-toolbox/build-article-media-batch-write-plan', $article_media_batch_plan, array(), array( 'source' => 'toolbox_article_media_batch_workflow' ) );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $article_media_batch_result ), 'Valid Toolbox article media batch write plan creates a Core proposal.' );
npcink_governance_core_fail_closed_assert( 1 === (int) ( $article_media_batch_result['proposal_count'] ?? 0 ), 'Valid Toolbox article media batch write plan creates one batch proposal.' );
$article_media_batch_proposal = is_array( $article_media_batch_result['proposals'][0] ?? null ) ? $article_media_batch_result['proposals'][0] : array();
npcink_governance_core_fail_closed_assert( 'plan_to_proposal_batch' === (string) ( $article_media_batch_proposal['preview']['source']['type'] ?? '' ), 'Article media batch write plan stores batch proposal source.' );
npcink_governance_core_fail_closed_assert( 4 === count( (array) ( $article_media_batch_proposal['input']['write_actions'] ?? array() ) ), 'Article media batch proposal stores draft and media write actions.' );
npcink_governance_core_fail_closed_assert( is_array( $article_media_batch_proposal['preview']['article_media_batch_workflow'] ?? null ), 'Article media batch proposal preserves media workflow preview.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$article_media_missing_candidate = npcink_governance_core_fail_closed_article_media_batch_write_plan();
unset( $article_media_missing_candidate['articles'][0]['featured_image_candidate'] );
$article_media_missing_candidate_result = $stack['service']->create_from_plan( 'npcink-toolbox/build-article-media-batch-write-plan', $article_media_missing_candidate );
npcink_governance_core_fail_closed_assert( is_wp_error( $article_media_missing_candidate_result ), 'Article media batch write plan without image candidate evidence is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_article_media_batch_candidate_missing' === $article_media_missing_candidate_result->get_error_code(), 'Article media candidate rejection uses stable error code.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$article_media_missing_featured = npcink_governance_core_fail_closed_article_media_batch_write_plan();
array_pop( $article_media_missing_featured['write_actions'] );
$article_media_missing_featured_result = $stack['service']->create_from_plan( 'npcink-toolbox/build-article-media-batch-write-plan', $article_media_missing_featured );
npcink_governance_core_fail_closed_assert( is_wp_error( $article_media_missing_featured_result ), 'Article media batch write plan without featured-image action is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_article_media_batch_actions_missing' === $article_media_missing_featured_result->get_error_code(), 'Article media missing featured action rejection uses stable error code.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$media_alt_plan = array(
	'artifact_type'          => 'media_alt_apply_plan',
	'contract_version'       => 'media_alt_apply_plan.v1',
	'proposal_mode'          => 'single',
	'requires_approval'      => true,
	'dry_run'                => true,
	'commit_execution'       => false,
	'direct_wordpress_write' => false,
	'authorization'          => array( 'classification' => 'core_proposal_required', 'authority' => 'npcink-governance-core' ),
	'write_actions'          => array(
		array(
			'action_id'         => 'update_media_alt_321',
			'target_ability_id' => 'npcink-abilities-toolkit/update-media-details',
			'input'             => array(
				'attachment_id'                     => 321,
				'alt'                               => 'Small orange cat sitting beside a window',
				'expected_current_alt'              => '',
				'operator_visual_review_confirmed' => true,
				'dry_run'                           => true,
				'commit'                            => false,
				'idempotency_key'                   => 'media-alt-missing-321-abcdef',
			),
			'requires_approval' => true,
			'commit_execution'  => false,
			'proposal_ready'    => true,
			'required_scopes'   => array( 'media.write' ),
			'risk'              => 'medium',
			'preview'           => array(
				'artifact_type'                     => 'media_alt_apply_plan_item',
				'contract_version'                 => 'media_alt_apply_plan.v1',
				'review_set_contract'              => 'media_alt_caption_review_set.v1',
				'source_item_id'                   => 'media-alt-caption:321',
				'attachment_id'                    => 321,
				'mime_type'                        => 'image/jpeg',
				'current_alt_status'               => 'missing',
				'expected_current_alt'             => '',
				'proposed_alt'                     => 'Small orange cat sitting beside a window',
				'operator_reviewed'                => true,
				'operator_visual_review_confirmed' => true,
				'evidence_refs'                    => array( 'image-context:321' ),
			),
		),
	),
);
$media_alt_result = $stack['service']->create_from_plan( 'npcink-abilities-toolkit/build-media-alt-apply-plan', $media_alt_plan, array(), array( 'source' => 'toolbox_media_alt_review' ) );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $media_alt_result ), 'Valid media ALT apply plan creates one Core proposal.' );
npcink_governance_core_fail_closed_assert( 1 === (int) ( $media_alt_result['proposal_count'] ?? 0 ), 'Media ALT apply plan creates exactly one proposal.' );
$media_alt_proposal = is_array( $media_alt_result['proposals'][0] ?? null ) ? $media_alt_result['proposals'][0] : array();
npcink_governance_core_fail_closed_assert( '' === (string) ( $media_alt_proposal['input']['expected_current_alt'] ?? 'missing' ), 'Media ALT proposal preserves the expected empty old value.' );
npcink_governance_core_fail_closed_assert( true === ( $media_alt_proposal['input']['operator_visual_review_confirmed'] ?? false ), 'Media ALT proposal preserves visual confirmation.' );
npcink_governance_core_fail_closed_assert( 'media_alt_apply_plan_item' === (string) ( $media_alt_proposal['preview']['media_alt_apply']['artifact_type'] ?? '' ), 'Media ALT proposal preserves reviewed plan evidence.' );
npcink_governance_core_fail_closed_assert( 'core_proposal_required' === (string) ( $media_alt_proposal['preview']['operation_classification']['classification'] ?? '' ), 'Media ALT proposal stores Core proposal classification evidence.' );

$media_alt_unconfirmed = $media_alt_plan;
$media_alt_unconfirmed['write_actions'][0]['input']['operator_visual_review_confirmed'] = false;
$media_alt_unconfirmed_result = $stack['service']->create_from_plan( 'npcink-abilities-toolkit/build-media-alt-apply-plan', $media_alt_unconfirmed );
npcink_governance_core_fail_closed_assert( is_wp_error( $media_alt_unconfirmed_result ), 'Media ALT apply plan rejects missing visual confirmation.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_media_alt_visual_confirmation_required' === $media_alt_unconfirmed_result->get_error_code(), 'Media ALT visual confirmation rejection uses a stable code.' );

$media_alt_overwrite = $media_alt_plan;
$media_alt_overwrite['write_actions'][0]['input']['expected_current_alt'] = 'Existing ALT';
$media_alt_overwrite_result = $stack['service']->create_from_plan( 'npcink-abilities-toolkit/build-media-alt-apply-plan', $media_alt_overwrite );
npcink_governance_core_fail_closed_assert( is_wp_error( $media_alt_overwrite_result ), 'Media ALT apply plan rejects overwriting a non-empty ALT.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_media_alt_missing_only' === $media_alt_overwrite_result->get_error_code(), 'Media ALT overwrite rejection uses a stable code.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$media_optimization_plan = npcink_governance_core_fail_closed_media_optimization_plan();
$media_optimization_result = $stack['service']->create_from_plan( 'npcink-abilities-toolkit/build-media-optimization-plan', $media_optimization_plan, array(), array( 'source' => 'toolbox_media_optimization' ) );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $media_optimization_result ), 'Valid media optimization plan creates a Core proposal.' );
npcink_governance_core_fail_closed_assert( 1 === (int) ( $media_optimization_result['proposal_count'] ?? 0 ), 'Valid media optimization plan creates one batch proposal.' );
$media_optimization_proposal = is_array( $media_optimization_result['proposals'][0] ?? null ) ? $media_optimization_result['proposals'][0] : array();
npcink_governance_core_fail_closed_assert( 'plan_to_proposal_batch' === (string) ( $media_optimization_proposal['preview']['source']['type'] ?? '' ), 'Media optimization plan stores batch proposal source.' );
npcink_governance_core_fail_closed_assert( 2 === count( (array) ( $media_optimization_proposal['input']['write_actions'] ?? array() ) ), 'Media optimization proposal stores metadata and derivative actions.' );
npcink_governance_core_fail_closed_assert( is_array( $media_optimization_proposal['preview']['media_optimization'] ?? null ), 'Media optimization proposal preserves optimization preview.' );
npcink_governance_core_fail_closed_assert( 'core-batch-review-summary-v1' === (string) ( $media_optimization_proposal['preview']['batch_review_summary']['summary_version'] ?? '' ), 'Media optimization proposal stores a stable batch review summary.' );
npcink_governance_core_fail_closed_assert( 2 === (int) ( $media_optimization_proposal['preview']['batch_review_summary']['action_count'] ?? 0 ), 'Media optimization batch review summary counts actions.' );
npcink_governance_core_fail_closed_assert( 'review_and_approve_or_reject' === (string) ( $media_optimization_proposal['preview']['batch_review_summary']['operator_next_action'] ?? '' ), 'Ready media optimization batch tells the operator to review and decide.' );
npcink_governance_core_fail_closed_assert( false === (bool) ( $media_optimization_proposal['preview']['batch_review_summary']['core_execution'] ?? true ), 'Media optimization batch review summary keeps Core execution disabled.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$multi_media_optimization_plan = npcink_governance_core_fail_closed_media_optimization_plan();
$multi_media_optimization_plan['attachment_ids'] = array( 1493, 1494 );
$multi_media_optimization_plan['write_actions'][] = array(
	'action_id'         => 'update_media_details_1494',
	'target_ability_id' => 'npcink-abilities-toolkit/update-media-details',
	'input'             => array(
		'attachment_id'   => 1494,
		'title'           => 'Second optimized AI image',
		'alt'             => 'Second AI generated product image',
		'source_type'     => 'ai_generated',
		'dry_run'         => true,
		'commit'          => false,
		'idempotency_key' => 'media-optimize-metadata-1494',
	),
	'risk'              => 'medium',
	'requires_approval' => true,
	'commit_execution'  => false,
	'proposal_ready'    => true,
);
$multi_media_optimization_plan['write_actions'][] = array(
	'action_id'         => 'adopt_webp_derivative_1494',
	'target_ability_id' => 'npcink-abilities-toolkit/adopt-cloud-media-derivative',
	'input'             => array(
		'attachment_id'                  => 1494,
		'derivative_artifact'            => npcink_governance_core_fail_closed_media_derivative_artifact(
			array(
				'artifact_id'        => 'art_00000000000000000000000000001494',
				'suggested_filename' => 'optimized-1494.webp',
			)
		),
		'expected_current_mime_type'     => 'image/png',
		'expected_derivative_mime_type'  => 'image/webp',
		'dry_run'                        => true,
		'commit'                         => false,
		'idempotency_key'                => 'media-optimize-derivative-1494',
	),
	'risk'              => 'medium',
	'requires_approval' => true,
	'commit_execution'  => false,
	'proposal_ready'    => true,
);
$multi_media_optimization_result = $stack['service']->create_from_plan( 'npcink-abilities-toolkit/build-media-optimization-plan', $multi_media_optimization_plan, array(), array( 'source' => 'toolbox_media_optimization' ) );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $multi_media_optimization_result ), 'Valid multi-attachment media optimization plan creates a Core proposal.' );
npcink_governance_core_fail_closed_assert( 1 === (int) ( $multi_media_optimization_result['proposal_count'] ?? 0 ), 'Valid multi-attachment media optimization plan creates one batch proposal.' );
$multi_media_optimization_proposal = is_array( $multi_media_optimization_result['proposals'][0] ?? null ) ? $multi_media_optimization_result['proposals'][0] : array();
npcink_governance_core_fail_closed_assert( 4 === count( (array) ( $multi_media_optimization_proposal['input']['write_actions'] ?? array() ) ), 'Multi-attachment media optimization proposal stores all metadata and derivative actions.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$too_many_media_actions = npcink_governance_core_fail_closed_media_optimization_plan();
$base_media_actions = $too_many_media_actions['write_actions'];
$too_many_media_actions['attachment_ids'] = array();
$too_many_media_actions['write_actions'] = array();
for ( $i = 0; $i < 6; $i++ ) {
	$attachment_id = 2000 + $i;
	$metadata_action = $base_media_actions[0];
	$derivative_action = $base_media_actions[1];
	$metadata_action['action_id'] = 'update_media_details_' . $attachment_id;
	$metadata_action['input']['attachment_id'] = $attachment_id;
	$metadata_action['input']['idempotency_key'] = 'media-optimize-metadata-' . $attachment_id;
	$derivative_action['action_id'] = 'adopt_webp_derivative_' . $attachment_id;
	$derivative_action['input']['attachment_id'] = $attachment_id;
	$derivative_action['input']['derivative_artifact'] = npcink_governance_core_fail_closed_media_derivative_artifact(
		array(
			'artifact_id'        => 'art_' . str_pad( dechex( $attachment_id ), 32, '0', STR_PAD_LEFT ),
			'suggested_filename' => 'optimized-' . $attachment_id . '.webp',
		)
	);
	$derivative_action['input']['idempotency_key'] = 'media-optimize-derivative-' . $attachment_id;
	$too_many_media_actions['attachment_ids'][] = $attachment_id;
	$too_many_media_actions['write_actions'][] = $metadata_action;
	$too_many_media_actions['write_actions'][] = $derivative_action;
}
$too_many_media_actions_result = $stack['service']->create_from_plan( 'npcink-abilities-toolkit/build-media-optimization-plan', $too_many_media_actions );
npcink_governance_core_fail_closed_assert( is_wp_error( $too_many_media_actions_result ), 'Media optimization plan with too many actions is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_media_optimization_actions_rejected' === $too_many_media_actions_result->get_error_code(), 'Media optimization action limit rejection uses stable error code.' );
npcink_governance_core_fail_closed_assert( 0 === count( $wpdb->rows( $proposal_table ) ), 'Oversized media optimization plan stores no proposal row.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$media_optimization_missing_derivative = npcink_governance_core_fail_closed_media_optimization_plan();
array_pop( $media_optimization_missing_derivative['write_actions'] );
$media_optimization_missing_result = $stack['service']->create_from_plan( 'npcink-abilities-toolkit/build-media-optimization-plan', $media_optimization_missing_derivative );
npcink_governance_core_fail_closed_assert( is_wp_error( $media_optimization_missing_result ), 'Media optimization plan without derivative adoption is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_media_optimization_actions_missing' === $media_optimization_missing_result->get_error_code(), 'Media optimization missing action rejection uses stable error code.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$media_optimization_mismatch = npcink_governance_core_fail_closed_media_optimization_plan();
$media_optimization_mismatch['write_actions'][1]['input']['attachment_id'] = 1494;
$media_optimization_mismatch_result = $stack['service']->create_from_plan( 'npcink-abilities-toolkit/build-media-optimization-plan', $media_optimization_mismatch );
npcink_governance_core_fail_closed_assert( is_wp_error( $media_optimization_mismatch_result ), 'Media optimization plan with unpaired attachment actions is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_media_optimization_attachment_mismatch' === $media_optimization_mismatch_result->get_error_code(), 'Media optimization attachment mismatch uses stable error code.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$media_optimization_split_repair = npcink_governance_core_fail_closed_media_optimization_plan();
$media_optimization_split_repair['write_actions'][] = array(
	'action_id'         => 'repair_inline_media_reference',
	'target_ability_id' => 'npcink-abilities-toolkit/patch-post-content',
	'input'             => array(
		'post_id'          => 8842,
		'operations'       => array(
			array(
				'op'      => 'replace',
				'find'    => 'https://example.test/wp-content/uploads/2026/06/workflow-diagram-image-300x162.jpg',
				'replace' => 'https://example.test/wp-content/uploads/2026/06/customer-approved-diagram.webp',
				'limit'   => 1,
			),
		),
		'dry_run'          => true,
		'commit'           => false,
		'idempotency_key'  => 'media-optimize-reference-repair-1493',
	),
	'risk'              => 'medium',
	'requires_approval' => true,
	'commit_execution'  => false,
	'proposal_ready'    => true,
);
$media_optimization_split_repair_result = $stack['service']->create_from_plan( 'npcink-abilities-toolkit/build-media-optimization-plan', $media_optimization_split_repair );
npcink_governance_core_fail_closed_assert( is_wp_error( $media_optimization_split_repair_result ), 'Media optimization plan with separate post-content repair action is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_media_optimization_reference_repair_split' === $media_optimization_split_repair_result->get_error_code(), 'Media optimization split repair rejection uses stable error code.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$media_adoption_enhancement_plan = npcink_governance_core_fail_closed_media_adoption_enhancement_plan();
$media_adoption_enhancement_result = $stack['service']->create_from_plan( 'npcink-abilities-toolkit/build-media-adoption-enhancement-plan', $media_adoption_enhancement_plan, array(), array( 'source' => 'abilities_media_adoption_enhancement' ) );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $media_adoption_enhancement_result ), 'Valid media adoption enhancement plan creates a Core proposal.' );
npcink_governance_core_fail_closed_assert( 1 === (int) ( $media_adoption_enhancement_result['proposal_count'] ?? 0 ), 'Valid media adoption enhancement plan creates one batch proposal.' );
$media_adoption_enhancement_proposal = is_array( $media_adoption_enhancement_result['proposals'][0] ?? null ) ? $media_adoption_enhancement_result['proposals'][0] : array();
npcink_governance_core_fail_closed_assert( 'plan_to_proposal_batch' === (string) ( $media_adoption_enhancement_proposal['preview']['source']['type'] ?? '' ), 'Media adoption enhancement plan stores batch proposal source.' );
npcink_governance_core_fail_closed_assert( 3 === count( (array) ( $media_adoption_enhancement_proposal['input']['write_actions'] ?? array() ) ), 'Media adoption enhancement proposal stores upload, optimize, and repair actions.' );
npcink_governance_core_fail_closed_assert( is_array( $media_adoption_enhancement_proposal['preview']['media_adoption_enhancement'] ?? null ), 'Media adoption enhancement proposal preserves enhancement preview.' );
npcink_governance_core_fail_closed_assert( 'https://images.example.test/generated-dashboard.png' === (string) ( $media_adoption_enhancement_proposal['preview']['media_adoption_enhancement']['source_url'] ?? '' ), 'Media adoption enhancement preview preserves nested media source URL.' );
npcink_governance_core_fail_closed_assert( 'https://example.test/wp-content/uploads/2026/06/old-dashboard.png' === (string) ( $media_adoption_enhancement_proposal['preview']['media_adoption_enhancement']['old_url'] ?? '' ), 'Media adoption enhancement preview preserves nested old URL.' );
npcink_governance_core_fail_closed_assert( 3 === (int) ( $media_adoption_enhancement_proposal['preview']['media_adoption_enhancement']['action_count'] ?? 0 ), 'Media adoption enhancement preview preserves top-level action count.' );
npcink_governance_core_fail_closed_assert( '$outputs.upload-media-asset.attachment_id' === (string) ( $media_adoption_enhancement_proposal['input']['write_actions'][1]['input']['attachment_id'] ?? '' ), 'Media adoption enhancement optimize action preserves upload output reference.' );
npcink_governance_core_fail_closed_assert( '$outputs.optimize-media-asset.derivative_url' === (string) ( $media_adoption_enhancement_proposal['input']['write_actions'][2]['input']['operations'][0]['replace'] ?? '' ), 'Media adoption enhancement repair action preserves derivative URL output reference.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$media_adoption_missing_optimize = npcink_governance_core_fail_closed_media_adoption_enhancement_plan();
array_splice( $media_adoption_missing_optimize['write_actions'], 1, 1 );
$media_adoption_missing_optimize_result = $stack['service']->create_from_plan( 'npcink-abilities-toolkit/build-media-adoption-enhancement-plan', $media_adoption_missing_optimize );
npcink_governance_core_fail_closed_assert( is_wp_error( $media_adoption_missing_optimize_result ), 'Media adoption enhancement plan without optimize action is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_media_adoption_enhancement_actions_missing' === $media_adoption_missing_optimize_result->get_error_code(), 'Media adoption enhancement missing optimize rejection uses stable error code.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$media_adoption_bad_repair = npcink_governance_core_fail_closed_media_adoption_enhancement_plan();
$media_adoption_bad_repair['write_actions'][2]['input']['operations'][0]['replace'] = 'https://example.test/wp-content/uploads/2026/06/unreviewed.webp';
$media_adoption_bad_repair_result = $stack['service']->create_from_plan( 'npcink-abilities-toolkit/build-media-adoption-enhancement-plan', $media_adoption_bad_repair );
npcink_governance_core_fail_closed_assert( is_wp_error( $media_adoption_bad_repair_result ), 'Media adoption enhancement plan with literal repair replacement URL is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_media_adoption_enhancement_patch_invalid' === $media_adoption_bad_repair_result->get_error_code(), 'Media adoption enhancement repair validation uses stable error code.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$media_rename_plan = npcink_governance_core_fail_closed_media_rename_plan();
$media_rename_result = $stack['service']->create_from_plan( 'npcink-abilities-toolkit/build-media-rename-plan', $media_rename_plan, array(), array( 'source' => 'abilities_media_rename' ) );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $media_rename_result ), 'Valid media rename plan creates a Core proposal.' );
npcink_governance_core_fail_closed_assert( 1 === (int) ( $media_rename_result['proposal_count'] ?? 0 ), 'Valid media rename plan creates one proposal.' );
$media_rename_proposal = is_array( $media_rename_result['proposals'][0] ?? null ) ? $media_rename_result['proposals'][0] : array();
npcink_governance_core_fail_closed_assert( 'npcink-abilities-toolkit/rename-media-file' === (string) ( $media_rename_proposal['ability_id'] ?? '' ), 'Media rename plan creates a rename-media-file proposal.' );
npcink_governance_core_fail_closed_assert( is_array( $media_rename_proposal['preview']['media_rename'] ?? null ), 'Media rename proposal preserves rename preview.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$media_rename_missing_target = npcink_governance_core_fail_closed_media_rename_plan();
unset( $media_rename_missing_target['write_actions'][0]['input']['target_file_name'] );
$media_rename_missing_target_result = $stack['service']->create_from_plan( 'npcink-abilities-toolkit/build-media-rename-plan', $media_rename_missing_target );
npcink_governance_core_fail_closed_assert( is_wp_error( $media_rename_missing_target_result ), 'Media rename plan without target file name is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_media_rename_target_file_missing' === $media_rename_missing_target_result->get_error_code(), 'Media rename missing target file rejection uses stable error code.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$media_rename_mismatch = npcink_governance_core_fail_closed_media_rename_plan();
$media_rename_mismatch['write_actions'][0]['input']['attachment_id'] = 1494;
$media_rename_mismatch_result = $stack['service']->create_from_plan( 'npcink-abilities-toolkit/build-media-rename-plan', $media_rename_mismatch );
npcink_governance_core_fail_closed_assert( is_wp_error( $media_rename_mismatch_result ), 'Media rename plan spanning multiple attachments is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_media_rename_attachment_mismatch' === $media_rename_mismatch_result->get_error_code(), 'Media rename attachment mismatch uses stable error code.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$pattern_page_plan = npcink_governance_core_fail_closed_pattern_page_plan();
$pattern_page_result = $stack['service']->create_from_plan( 'npcink-abilities-toolkit/build-pattern-page-plan', $pattern_page_plan, array(), array( 'source' => 'abilities_pattern_page' ) );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $pattern_page_result ), 'Valid pattern page plan creates a Core proposal.' );
npcink_governance_core_fail_closed_assert( 1 === (int) ( $pattern_page_result['proposal_count'] ?? 0 ), 'Valid pattern page plan creates one batch proposal.' );
$pattern_page_proposal = is_array( $pattern_page_result['proposals'][0] ?? null ) ? $pattern_page_result['proposals'][0] : array();
npcink_governance_core_fail_closed_assert( 'plan_to_proposal_batch' === (string) ( $pattern_page_proposal['preview']['source']['type'] ?? '' ), 'Pattern page plan stores batch proposal source.' );
npcink_governance_core_fail_closed_assert( is_array( $pattern_page_proposal['preview']['pattern_page'] ?? null ), 'Pattern page proposal preserves pattern page preview.' );
npcink_governance_core_fail_closed_assert( 'openai-style-landing' === (string) ( $pattern_page_proposal['preview']['pattern_page']['pattern_id'] ?? '' ), 'Pattern page preview preserves pattern id.' );
npcink_governance_core_fail_closed_assert( 2 === count( (array) ( $pattern_page_proposal['input']['write_actions'] ?? array() ) ), 'Pattern page proposal stores create and block update actions.' );
npcink_governance_core_fail_closed_assert( '$outputs.create-pattern-page.post_id' === (string) ( $pattern_page_proposal['input']['write_actions'][1]['input']['post_id'] ?? '' ), 'Pattern page update action preserves output reference.' );
$pattern_page_block = $pattern_page_proposal['input']['write_actions'][1]['input']['blocks'][0] ?? array();
npcink_governance_core_fail_closed_assert( is_array( $pattern_page_block ) && isset( $pattern_page_block['blockName'] ) && ! isset( $pattern_page_block['blockname'] ), 'Pattern page proposal preserves Gutenberg blockName key case.' );
npcink_governance_core_fail_closed_assert( isset( $pattern_page_block['innerBlocks'] ) && ! isset( $pattern_page_block['innerblocks'] ), 'Pattern page proposal preserves Gutenberg innerBlocks key case.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$pattern_page_bad_class = npcink_governance_core_fail_closed_pattern_page_plan();
$pattern_page_bad_class['write_actions'][1]['input']['blocks'][0]['attrs']['className'] = 'npcink-ai-page rogue-class';
$pattern_page_bad_class_result = $stack['service']->create_from_plan( 'npcink-abilities-toolkit/build-pattern-page-plan', $pattern_page_bad_class );
npcink_governance_core_fail_closed_assert( is_wp_error( $pattern_page_bad_class_result ), 'Pattern page plan with non-allowlisted block class is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_pattern_page_class_rejected' === $pattern_page_bad_class_result->get_error_code(), 'Pattern page class rejection uses stable error code.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$block_theme_site_plan = npcink_governance_core_fail_closed_block_theme_site_plan();
$block_theme_site_result = $stack['service']->create_from_plan( 'npcink-abilities-toolkit/build-block-theme-site-plan', $block_theme_site_plan, array(), array( 'source' => 'abilities_block_theme_site' ) );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $block_theme_site_result ), 'Valid block theme site plan creates a Core proposal.' );
npcink_governance_core_fail_closed_assert( 1 === (int) ( $block_theme_site_result['proposal_count'] ?? 0 ), 'Valid block theme site plan creates one batch proposal.' );
$block_theme_site_proposal = is_array( $block_theme_site_result['proposals'][0] ?? null ) ? $block_theme_site_result['proposals'][0] : array();
npcink_governance_core_fail_closed_assert( 'plan_to_proposal_batch' === (string) ( $block_theme_site_proposal['preview']['source']['type'] ?? '' ), 'Block theme site plan stores batch proposal source.' );
npcink_governance_core_fail_closed_assert( is_array( $block_theme_site_proposal['preview']['block_theme_site'] ?? null ), 'Block theme site proposal preserves block theme preview.' );
npcink_governance_core_fail_closed_assert( 'create_wp_template_override' === (string) ( $block_theme_site_proposal['preview']['block_theme_site']['file_template_write_mode'] ?? '' ), 'Block theme site preview records file-template override mode.' );
npcink_governance_core_fail_closed_assert( 1 === count( (array) ( $block_theme_site_proposal['input']['write_actions'] ?? array() ) ), 'Block theme site proposal stores the template write action.' );
npcink_governance_core_fail_closed_assert( 'npcink-abilities-toolkit/upsert-template-blocks' === (string) ( $block_theme_site_proposal['input']['write_actions'][0]['target_ability_id'] ?? '' ), 'Block theme site proposal stores template override upsert action.' );
$block_theme_site_block = $block_theme_site_proposal['input']['write_actions'][0]['input']['blocks'][0] ?? array();
npcink_governance_core_fail_closed_assert( is_array( $block_theme_site_block ) && isset( $block_theme_site_block['blockName'] ) && ! isset( $block_theme_site_block['blockname'] ), 'Block theme site proposal preserves Gutenberg blockName key case.' );
npcink_governance_core_fail_closed_assert( isset( $block_theme_site_block['innerBlocks'] ) && ! isset( $block_theme_site_block['innerblocks'] ), 'Block theme site proposal preserves Gutenberg innerBlocks key case.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$block_theme_layout_plan = npcink_governance_core_fail_closed_block_theme_layout_plan();
$block_theme_layout_result = $stack['service']->create_from_plan( 'npcink-abilities-toolkit/build-block-theme-site-plan', $block_theme_layout_plan, array(), array( 'source' => 'abilities_block_theme_layout' ) );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $block_theme_layout_result ), 'Valid block theme template layout plan creates a Core proposal.' );
npcink_governance_core_fail_closed_assert( 1 === (int) ( $block_theme_layout_result['proposal_count'] ?? 0 ), 'Valid block theme template layout plan creates one batch proposal.' );
$block_theme_layout_proposal = is_array( $block_theme_layout_result['proposals'][0] ?? null ) ? $block_theme_layout_result['proposals'][0] : array();
npcink_governance_core_fail_closed_assert( 'customize_template_layout' === (string) ( $block_theme_layout_proposal['preview']['block_theme_site']['intent'] ?? '' ), 'Block theme layout proposal preserves layout intent.' );
npcink_governance_core_fail_closed_assert( 'article_standard' === (string) ( $block_theme_layout_proposal['preview']['block_theme_site']['layout_profile'] ?? '' ), 'Block theme layout proposal preserves layout profile.' );
npcink_governance_core_fail_closed_assert( 'pass' === (string) ( $block_theme_layout_proposal['preview']['block_theme_site']['template_layout_contract']['contract_status'] ?? '' ), 'Block theme layout proposal preserves passing layout contract.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$block_theme_homepage_layout_plan = npcink_governance_core_fail_closed_block_theme_homepage_layout_plan();
$block_theme_homepage_layout_result = $stack['service']->create_from_plan( 'npcink-abilities-toolkit/build-block-theme-site-plan', $block_theme_homepage_layout_plan, array(), array( 'source' => 'abilities_block_theme_homepage_layout' ) );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $block_theme_homepage_layout_result ), 'Valid block theme homepage layout plan with categories creates a Core proposal.' );
npcink_governance_core_fail_closed_assert( 1 === (int) ( $block_theme_homepage_layout_result['proposal_count'] ?? 0 ), 'Valid block theme homepage layout plan creates one batch proposal.' );
$block_theme_homepage_layout_proposal = is_array( $block_theme_homepage_layout_result['proposals'][0] ?? null ) ? $block_theme_homepage_layout_result['proposals'][0] : array();
npcink_governance_core_fail_closed_assert( 'homepage_landing' === (string) ( $block_theme_homepage_layout_proposal['preview']['block_theme_site']['layout_profile'] ?? '' ), 'Block theme homepage layout proposal preserves homepage layout profile.' );
$block_theme_homepage_blocks_json = wp_json_encode( $block_theme_homepage_layout_proposal['input']['write_actions'][0]['input']['blocks'] ?? array() );
npcink_governance_core_fail_closed_assert( is_string( $block_theme_homepage_blocks_json ) && false !== strpos( $block_theme_homepage_blocks_json, 'core\/categories' ), 'Block theme homepage layout proposal accepts core categories blocks.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$block_theme_layout_bad_profile = npcink_governance_core_fail_closed_block_theme_layout_plan();
$block_theme_layout_bad_profile['template_layout_contract']['profiles'][0]['layout_profile'] = 'arbitrary_layout';
$block_theme_layout_bad_profile_result = $stack['service']->create_from_plan( 'npcink-abilities-toolkit/build-block-theme-site-plan', $block_theme_layout_bad_profile );
npcink_governance_core_fail_closed_assert( is_wp_error( $block_theme_layout_bad_profile_result ), 'Block theme layout plan with unaccepted profile is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_block_theme_site_layout_profile_rejected' === $block_theme_layout_bad_profile_result->get_error_code(), 'Block theme layout profile rejection uses stable error code.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$block_theme_layout_missing_contract = npcink_governance_core_fail_closed_block_theme_layout_plan();
unset( $block_theme_layout_missing_contract['template_layout_contract'] );
$block_theme_layout_missing_contract_result = $stack['service']->create_from_plan( 'npcink-abilities-toolkit/build-block-theme-site-plan', $block_theme_layout_missing_contract );
npcink_governance_core_fail_closed_assert( is_wp_error( $block_theme_layout_missing_contract_result ), 'Block theme layout plan without a passing layout contract is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_block_theme_site_layout_contract_rejected' === $block_theme_layout_missing_contract_result->get_error_code(), 'Block theme layout contract rejection uses stable error code.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$block_theme_missing_roundtrip = npcink_governance_core_fail_closed_block_theme_layout_plan();
unset( $block_theme_missing_roundtrip['write_actions'][0]['input']['validate_roundtrip'] );
$block_theme_missing_roundtrip_result = $stack['service']->create_from_plan( 'npcink-abilities-toolkit/build-block-theme-site-plan', $block_theme_missing_roundtrip );
npcink_governance_core_fail_closed_assert( is_wp_error( $block_theme_missing_roundtrip_result ), 'Block theme layout plan without roundtrip validation evidence is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_block_theme_site_roundtrip_required' === $block_theme_missing_roundtrip_result->get_error_code(), 'Block theme layout roundtrip rejection uses stable error code.' );
npcink_governance_core_fail_closed_assert( 0 === count( $wpdb->rows( $proposal_table ) ), 'Block theme layout missing roundtrip stores no proposal row.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$block_theme_bad_block = npcink_governance_core_fail_closed_block_theme_layout_plan();
$block_theme_bad_block['write_actions'][0]['input']['blocks'][1]['innerBlocks'][] = array(
	'blockName'    => 'core/html',
	'attrs'        => array(),
	'innerBlocks'  => array(),
	'innerHTML'    => '<script>alert("blocked")</script>',
	'innerContent' => array( '<script>alert("blocked")</script>' ),
);
$block_theme_bad_block_result = $stack['service']->create_from_plan( 'npcink-abilities-toolkit/build-block-theme-site-plan', $block_theme_bad_block );
npcink_governance_core_fail_closed_assert( is_wp_error( $block_theme_bad_block_result ), 'Block theme layout plan with raw HTML block is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_block_theme_site_block_rejected' === $block_theme_bad_block_result->get_error_code(), 'Block theme raw HTML block rejection uses stable error code.' );
npcink_governance_core_fail_closed_assert( 0 === count( $wpdb->rows( $proposal_table ) ), 'Block theme raw HTML block stores no proposal row.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$block_theme_navigation_block = npcink_governance_core_fail_closed_block_theme_layout_plan();
$block_theme_navigation_block['write_actions'][0]['input']['blocks'][1]['innerBlocks'][] = array(
	'blockName'    => 'core/navigation',
	'attrs'        => array( 'ref' => 1 ),
	'innerBlocks'  => array(),
	'innerHTML'    => '',
	'innerContent' => array(),
);
$block_theme_navigation_block_result = $stack['service']->create_from_plan( 'npcink-abilities-toolkit/build-block-theme-site-plan', $block_theme_navigation_block );
npcink_governance_core_fail_closed_assert( is_wp_error( $block_theme_navigation_block_result ), 'Block theme layout plan with navigation block is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_block_theme_site_block_rejected' === $block_theme_navigation_block_result->get_error_code(), 'Block theme navigation block rejection uses stable error code.' );
npcink_governance_core_fail_closed_assert( 0 === count( $wpdb->rows( $proposal_table ) ), 'Block theme navigation block stores no proposal row.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$block_theme_script_html = npcink_governance_core_fail_closed_block_theme_layout_plan();
$block_theme_script_html['write_actions'][0]['input']['blocks'][1]['innerHTML'] = '<main><iframe src="https://example.com"></iframe></main>';
$block_theme_script_html['write_actions'][0]['input']['blocks'][1]['innerContent'] = array( '<main><iframe src="https://example.com"></iframe></main>' );
$block_theme_script_html_result = $stack['service']->create_from_plan( 'npcink-abilities-toolkit/build-block-theme-site-plan', $block_theme_script_html );
npcink_governance_core_fail_closed_assert( is_wp_error( $block_theme_script_html_result ), 'Block theme layout plan with embedded HTML is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_block_theme_site_block_rejected' === $block_theme_script_html_result->get_error_code(), 'Block theme embedded HTML rejection uses stable error code.' );
npcink_governance_core_fail_closed_assert( 0 === count( $wpdb->rows( $proposal_table ) ), 'Block theme embedded HTML stores no proposal row.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$block_theme_bad_slug = npcink_governance_core_fail_closed_block_theme_layout_plan();
$block_theme_bad_slug['write_actions'][0]['input']['slug'] = 'archive-product';
$block_theme_bad_slug_result = $stack['service']->create_from_plan( 'npcink-abilities-toolkit/build-block-theme-site-plan', $block_theme_bad_slug );
npcink_governance_core_fail_closed_assert( is_wp_error( $block_theme_bad_slug_result ), 'Block theme layout plan for a non-allowlisted template slug is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_block_theme_site_template_rejected' === $block_theme_bad_slug_result->get_error_code(), 'Block theme template slug rejection uses stable error code.' );
npcink_governance_core_fail_closed_assert( 0 === count( $wpdb->rows( $proposal_table ) ), 'Block theme bad slug stores no proposal row.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$too_many_global_actions = npcink_governance_core_fail_closed_block_theme_site_plan();
$base_block_theme_action = $too_many_global_actions['write_actions'][0];
$too_many_global_actions['write_actions'] = array();
for ( $i = 0; $i < 26; $i++ ) {
	$action = $base_block_theme_action;
	$action['action_id'] = 'upsert-template-global-limit-' . $i;
	$action['input']['slug'] = 'single-global-limit-' . $i;
	$action['input']['source_template_id'] = 'twentytwentyfive//single-global-limit-' . $i;
	$action['input']['idempotency_key'] = 'block-theme-site-global-limit-' . $i;
	$too_many_global_actions['write_actions'][] = $action;
}
$too_many_global_actions_result = $stack['service']->create_from_plan( 'npcink-abilities-toolkit/build-block-theme-site-plan', $too_many_global_actions );
npcink_governance_core_fail_closed_assert( is_wp_error( $too_many_global_actions_result ), 'Plan with too many write actions is rejected before proposal creation.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_plan_too_many_actions' === $too_many_global_actions_result->get_error_code(), 'Global plan action limit rejection uses stable error code.' );
npcink_governance_core_fail_closed_assert( 0 === count( $wpdb->rows( $proposal_table ) ), 'Plan over global action limit stores no proposal row.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$too_many_block_theme_actions = npcink_governance_core_fail_closed_block_theme_site_plan();
$base_block_theme_action = $too_many_block_theme_actions['write_actions'][0];
$too_many_block_theme_actions['write_actions'] = array();
for ( $i = 0; $i < 11; $i++ ) {
	$action = $base_block_theme_action;
	$action['action_id'] = 'upsert-template-block-limit-' . $i;
	$action['input']['slug'] = 'single-block-limit-' . $i;
	$action['input']['source_template_id'] = 'twentytwentyfive//single-block-limit-' . $i;
	$action['input']['idempotency_key'] = 'block-theme-site-block-limit-' . $i;
	$too_many_block_theme_actions['write_actions'][] = $action;
}
$too_many_block_theme_actions_result = $stack['service']->create_from_plan( 'npcink-abilities-toolkit/build-block-theme-site-plan', $too_many_block_theme_actions );
npcink_governance_core_fail_closed_assert( is_wp_error( $too_many_block_theme_actions_result ), 'Block theme site plan with too many template actions is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_block_theme_site_actions_rejected' === $too_many_block_theme_actions_result->get_error_code(), 'Block theme site action limit rejection uses stable error code.' );
npcink_governance_core_fail_closed_assert( 0 === count( $wpdb->rows( $proposal_table ) ), 'Oversized block theme site plan stores no proposal row.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$block_theme_site_bad_target = npcink_governance_core_fail_closed_block_theme_site_plan();
$block_theme_site_bad_target['write_actions'][0]['target_ability_id'] = 'npcink-abilities-toolkit/update-post-blocks';
$block_theme_site_bad_target_result = $stack['service']->create_from_plan( 'npcink-abilities-toolkit/build-block-theme-site-plan', $block_theme_site_bad_target );
npcink_governance_core_fail_closed_assert( is_wp_error( $block_theme_site_bad_target_result ), 'Block theme site plan with non-template target is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_block_theme_site_target_rejected' === $block_theme_site_bad_target_result->get_error_code(), 'Block theme site target rejection uses stable error code.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$block_theme_site_missing_slug = npcink_governance_core_fail_closed_block_theme_site_plan();
unset( $block_theme_site_missing_slug['write_actions'][0]['input']['slug'] );
$block_theme_site_missing_slug_result = $stack['service']->create_from_plan( 'npcink-abilities-toolkit/build-block-theme-site-plan', $block_theme_site_missing_slug );
npcink_governance_core_fail_closed_assert( is_wp_error( $block_theme_site_missing_slug_result ), 'Block theme site template override without slug is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_block_theme_site_upsert_target_missing' === $block_theme_site_missing_slug_result->get_error_code(), 'Block theme site missing slug rejection uses stable error code.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$article_block_plan = npcink_governance_core_fail_closed_article_block_plan();
$article_block_result = $stack['service']->create_from_plan( 'npcink-abilities-toolkit/build-article-block-plan', $article_block_plan, array(), array( 'source' => 'abilities_article_block' ) );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $article_block_result ), 'Valid article block plan creates a Core proposal.' );
npcink_governance_core_fail_closed_assert( 1 === (int) ( $article_block_result['proposal_count'] ?? 0 ), 'Valid article block plan creates one batch proposal.' );
$article_block_proposal = is_array( $article_block_result['proposals'][0] ?? null ) ? $article_block_result['proposals'][0] : array();
npcink_governance_core_fail_closed_assert( 'plan_to_proposal_batch' === (string) ( $article_block_proposal['preview']['source']['type'] ?? '' ), 'Article block plan stores batch proposal source.' );
npcink_governance_core_fail_closed_assert( is_array( $article_block_proposal['preview']['article_block'] ?? null ), 'Article block proposal preserves article block preview.' );
npcink_governance_core_fail_closed_assert( 'comparison-review' === (string) ( $article_block_proposal['preview']['article_block']['article_template'] ?? '' ), 'Article block preview preserves article template.' );
npcink_governance_core_fail_closed_assert( 2 === count( (array) ( $article_block_proposal['input']['write_actions'] ?? array() ) ), 'Article block proposal stores create and block update actions.' );
npcink_governance_core_fail_closed_assert( '$outputs.create-article-draft.post_id' === (string) ( $article_block_proposal['input']['write_actions'][1]['input']['post_id'] ?? '' ), 'Article block update action preserves output reference.' );
$article_block = $article_block_proposal['input']['write_actions'][1]['input']['blocks'][0] ?? array();
npcink_governance_core_fail_closed_assert( is_array( $article_block ) && isset( $article_block['blockName'] ) && ! isset( $article_block['blockname'] ), 'Article block proposal preserves Gutenberg blockName key case.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$article_block_bad_class = npcink_governance_core_fail_closed_article_block_plan();
$article_block_bad_class['write_actions'][1]['input']['blocks'][0]['attrs']['className'] = 'rogue-class';
$article_block_bad_class_result = $stack['service']->create_from_plan( 'npcink-abilities-toolkit/build-article-block-plan', $article_block_bad_class );
npcink_governance_core_fail_closed_assert( is_wp_error( $article_block_bad_class_result ), 'Article block plan with custom block class is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_article_block_class_rejected' === $article_block_bad_class_result->get_error_code(), 'Article block class rejection uses stable error code.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$image_candidate_plan = npcink_governance_core_fail_closed_image_candidate_adoption_plan();
$image_candidate_result = $stack['service']->create_from_plan( 'npcink-abilities-toolkit/build-image-candidate-adoption-plan', $image_candidate_plan, array(), array( 'source' => 'toolbox_image_candidate_adoption' ) );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $image_candidate_result ), 'Valid image candidate adoption plan creates a Core proposal.' );
npcink_governance_core_fail_closed_assert( 1 === (int) ( $image_candidate_result['proposal_count'] ?? 0 ), 'Valid image candidate adoption plan creates one batch proposal.' );
$image_candidate_proposal = is_array( $image_candidate_result['proposals'][0] ?? null ) ? $image_candidate_result['proposals'][0] : array();
npcink_governance_core_fail_closed_assert( 'plan_to_proposal_batch' === (string) ( $image_candidate_proposal['preview']['source']['type'] ?? '' ), 'Image candidate adoption plan stores batch proposal source.' );
npcink_governance_core_fail_closed_assert( 3 === count( (array) ( $image_candidate_proposal['input']['write_actions'] ?? array() ) ), 'Image candidate adoption proposal stores media import, metadata, and featured-image actions.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$image_candidate_missing_contract = npcink_governance_core_fail_closed_image_candidate_adoption_plan();
unset( $image_candidate_missing_contract['candidate_contract_version'] );
unset( $image_candidate_missing_contract['selected_image_candidate']['contract_version'] );
$image_candidate_missing_contract_result = $stack['service']->create_from_plan( 'npcink-abilities-toolkit/build-image-candidate-adoption-plan', $image_candidate_missing_contract );
npcink_governance_core_fail_closed_assert( is_wp_error( $image_candidate_missing_contract_result ), 'Image candidate adoption plan without candidate contract is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_image_candidate_contract_missing' === $image_candidate_missing_contract_result->get_error_code(), 'Image candidate missing contract rejection uses stable error code.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$image_candidate_bad_source = npcink_governance_core_fail_closed_image_candidate_adoption_plan();
$image_candidate_bad_source['write_actions'][0]['input']['source_type'] = 'unsupported';
$image_candidate_bad_source_result = $stack['service']->create_from_plan( 'npcink-abilities-toolkit/build-image-candidate-adoption-plan', $image_candidate_bad_source );
npcink_governance_core_fail_closed_assert( is_wp_error( $image_candidate_bad_source_result ), 'Image candidate adoption plan with invalid source_type is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_image_candidate_source_type_invalid' === $image_candidate_bad_source_result->get_error_code(), 'Image candidate invalid source type rejection uses stable error code.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$site_knowledge_plan = npcink_governance_core_fail_closed_site_knowledge_review_plan();
$site_knowledge_result = $stack['service']->create_from_plan( 'npcink-toolbox/build-site-knowledge-review-plan', $site_knowledge_plan, array(), array( 'source' => 'toolbox_site_knowledge_review' ) );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $site_knowledge_result ), 'Valid Site Knowledge review plan creates a Core proposal.' );
npcink_governance_core_fail_closed_assert( 1 === (int) ( $site_knowledge_result['proposal_count'] ?? 0 ), 'Valid Site Knowledge review plan creates one blocked proposal.' );
npcink_governance_core_fail_closed_assert( 0 === (int) ( $site_knowledge_result['proposal_ready_count'] ?? 0 ), 'Site Knowledge review proposal is not proposal-ready before human draft input.' );
$site_knowledge_proposal = is_array( $site_knowledge_result['proposals'][0] ?? null ) ? $site_knowledge_result['proposals'][0] : array();
npcink_governance_core_fail_closed_assert( 'npcink-abilities-toolkit/create-draft' === (string) ( $site_knowledge_proposal['ability_id'] ?? '' ), 'Site Knowledge review plan targets the governed create-draft ability.' );
npcink_governance_core_fail_closed_assert( false === (bool) ( $site_knowledge_proposal['preview']['proposal_ready'] ?? true ), 'Site Knowledge review proposal stores proposal_ready=false.' );
npcink_governance_core_fail_closed_assert( in_array( 'title', (array) ( $site_knowledge_proposal['preview']['needs_input'] ?? array() ), true ), 'Site Knowledge review proposal requires title input.' );
npcink_governance_core_fail_closed_assert( isset( $site_knowledge_proposal['preview']['site_knowledge_review'] ), 'Site Knowledge review preview is preserved in the proposal.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$site_knowledge_ready = npcink_governance_core_fail_closed_site_knowledge_review_plan();
$site_knowledge_ready['write_actions'][0]['proposal_ready'] = true;
$site_knowledge_ready_result = $stack['service']->create_from_plan( 'npcink-toolbox/build-site-knowledge-review-plan', $site_knowledge_ready );
npcink_governance_core_fail_closed_assert( is_wp_error( $site_knowledge_ready_result ), 'Site Knowledge review plan marked ready is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_site_knowledge_ready_rejected' === $site_knowledge_ready_result->get_error_code(), 'Site Knowledge ready rejection uses stable error code.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$site_knowledge_missing_evidence = npcink_governance_core_fail_closed_site_knowledge_review_plan();
$site_knowledge_missing_evidence['evidence_refs'] = array();
$site_knowledge_missing_evidence_result = $stack['service']->create_from_plan( 'npcink-toolbox/build-site-knowledge-review-plan', $site_knowledge_missing_evidence );
npcink_governance_core_fail_closed_assert( is_wp_error( $site_knowledge_missing_evidence_result ), 'Site Knowledge review plan without evidence is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_site_knowledge_evidence_missing' === $site_knowledge_missing_evidence_result->get_error_code(), 'Site Knowledge missing evidence rejection uses stable error code.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$nightly_inspection_plan = npcink_governance_core_fail_closed_nightly_inspection_review_plan();
$nightly_inspection_result = $stack['service']->create_from_plan( 'npcink-toolbox/build-nightly-inspection-review-plan', $nightly_inspection_plan, array(), array( 'source' => 'cloud_nightly_inspection' ) );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $nightly_inspection_result ), 'Valid Nightly Inspection review plan creates a Core proposal.' );
npcink_governance_core_fail_closed_assert( 1 === (int) ( $nightly_inspection_result['proposal_count'] ?? 0 ), 'Valid Nightly Inspection review plan creates one blocked proposal.' );
npcink_governance_core_fail_closed_assert( 0 === (int) ( $nightly_inspection_result['proposal_ready_count'] ?? 0 ), 'Nightly Inspection review proposal is not proposal-ready before human draft input.' );
$nightly_inspection_proposal = is_array( $nightly_inspection_result['proposals'][0] ?? null ) ? $nightly_inspection_result['proposals'][0] : array();
npcink_governance_core_fail_closed_assert( 'npcink-abilities-toolkit/create-draft' === (string) ( $nightly_inspection_proposal['ability_id'] ?? '' ), 'Nightly Inspection review plan targets the governed create-draft ability.' );
npcink_governance_core_fail_closed_assert( false === (bool) ( $nightly_inspection_proposal['preview']['proposal_ready'] ?? true ), 'Nightly Inspection review proposal stores proposal_ready=false.' );
npcink_governance_core_fail_closed_assert( in_array( 'title', (array) ( $nightly_inspection_proposal['preview']['needs_input'] ?? array() ), true ), 'Nightly Inspection review proposal requires title input.' );
npcink_governance_core_fail_closed_assert( isset( $nightly_inspection_proposal['preview']['nightly_inspection_review'] ), 'Nightly Inspection review preview is preserved in the proposal.' );
npcink_governance_core_fail_closed_assert( 'run_nightly_inspection_fault_injection' === (string) ( $nightly_inspection_proposal['preview']['nightly_inspection_review']['cloud_run_id'] ?? '' ), 'Nightly Inspection proposal preserves Cloud run id.' );
npcink_governance_core_fail_closed_assert( 'action_001' === (string) ( $nightly_inspection_proposal['preview']['nightly_inspection_review']['selected_review_item_ids'][0] ?? '' ), 'Nightly Inspection proposal preserves selected Morning Brief review item id.' );
npcink_governance_core_fail_closed_assert( 1 === (int) ( $nightly_inspection_proposal['preview']['nightly_inspection_review']['evidence_ref_count'] ?? 0 ), 'Nightly Inspection proposal preserves evidence reference count.' );
npcink_governance_core_fail_closed_assert( 'toolbox_morning_brief_operator' === (string) ( $nightly_inspection_proposal['preview']['nightly_inspection_review']['needs_input_resolution_owner'] ?? '' ), 'Nightly Inspection proposal routes missing input back to the Toolbox Morning Brief operator.' );
npcink_governance_core_fail_closed_assert( true === (bool) ( $nightly_inspection_proposal['preview']['nightly_inspection_review']['resubmission_required'] ?? false ), 'Nightly Inspection proposal requires complete resubmission after missing input is resolved.' );
npcink_governance_core_fail_closed_assert( false === (bool) ( $nightly_inspection_proposal['preview']['nightly_inspection_review']['core_amendment_supported'] ?? true ), 'Nightly Inspection proposal does not allow Core-side amendment of missing draft fields.' );
$nightly_approved = $stack['proposal_service']->approve( (string) ( $nightly_inspection_proposal['proposal_id'] ?? '' ), array( 'reason' => 'nightly_missing_input_preflight' ) );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $nightly_approved ), 'Blocked Nightly Inspection proposal can be approved for review without becoming executable.' );
$nightly_blocked_preflight = $stack['preflight']->preflight( (string) ( $nightly_inspection_proposal['proposal_id'] ?? '' ) );
npcink_governance_core_fail_closed_assert( is_wp_error( $nightly_blocked_preflight ), 'Blocked Nightly Inspection proposal fails commit preflight after approval.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_proposal_items_blocked' === $nightly_blocked_preflight->get_error_code(), 'Blocked Nightly Inspection proposal uses stable preflight blocked error code.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$nightly_inspection_ready = npcink_governance_core_fail_closed_nightly_inspection_review_plan();
$nightly_inspection_ready['write_actions'][0]['proposal_ready'] = true;
$nightly_inspection_ready_result = $stack['service']->create_from_plan( 'npcink-toolbox/build-nightly-inspection-review-plan', $nightly_inspection_ready );
npcink_governance_core_fail_closed_assert( is_wp_error( $nightly_inspection_ready_result ), 'Nightly Inspection review plan marked ready is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_nightly_inspection_ready_rejected' === $nightly_inspection_ready_result->get_error_code(), 'Nightly Inspection ready rejection uses stable error code.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$nightly_inspection_missing_evidence = npcink_governance_core_fail_closed_nightly_inspection_review_plan();
$nightly_inspection_missing_evidence['evidence_refs'] = array();
$nightly_inspection_missing_evidence_result = $stack['service']->create_from_plan( 'npcink-toolbox/build-nightly-inspection-review-plan', $nightly_inspection_missing_evidence );
npcink_governance_core_fail_closed_assert( is_wp_error( $nightly_inspection_missing_evidence_result ), 'Nightly Inspection review plan without evidence is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_nightly_inspection_evidence_missing' === $nightly_inspection_missing_evidence_result->get_error_code(), 'Nightly Inspection missing evidence rejection uses stable error code.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$content_metadata_plan = npcink_governance_core_fail_closed_content_metadata_apply_plan();
$content_metadata_result = $stack['service']->create_from_plan( 'npcink-abilities-toolkit/build-content-metadata-apply-plan', $content_metadata_plan, array(), array( 'source' => 'toolbox_content_metadata_apply' ) );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $content_metadata_result ), 'Valid content metadata apply plan creates a Core proposal.' );
npcink_governance_core_fail_closed_assert( 1 === (int) ( $content_metadata_result['proposal_count'] ?? 0 ), 'Valid content metadata apply plan creates one batch proposal.' );
$content_metadata_proposal = is_array( $content_metadata_result['proposals'][0] ?? null ) ? $content_metadata_result['proposals'][0] : array();
npcink_governance_core_fail_closed_assert( 'plan_to_proposal_batch' === (string) ( $content_metadata_proposal['preview']['source']['type'] ?? '' ), 'Content metadata apply plan stores batch proposal source.' );
npcink_governance_core_fail_closed_assert( 3 === count( (array) ( $content_metadata_proposal['input']['write_actions'] ?? array() ) ), 'Content metadata apply proposal stores excerpt, category, and tag actions.' );
npcink_governance_core_fail_closed_assert( isset( $content_metadata_proposal['preview']['content_metadata_apply'] ), 'Content metadata apply preview is preserved in the proposal.' );
npcink_governance_core_fail_closed_assert( 'core_proposal_required' === (string) ( $content_metadata_proposal['preview']['content_metadata_apply']['classification_evidence']['decision_envelope']['classification'] ?? '' ), 'Content metadata apply proposal preserves classifier decision evidence.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$content_metadata_local_consent = npcink_governance_core_fail_closed_content_metadata_apply_plan();
$content_metadata_local_consent['authorization']['classification'] = 'local_admin_consent';
$content_metadata_local_consent['authorization']['decision_envelope']['classification'] = 'local_admin_consent';
$content_metadata_local_consent_result = $stack['service']->create_from_plan( 'npcink-abilities-toolkit/build-content-metadata-apply-plan', $content_metadata_local_consent );
npcink_governance_core_fail_closed_assert( is_wp_error( $content_metadata_local_consent_result ), 'Content metadata apply plan with local-admin-consent classification is rejected from Core intake.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_content_metadata_authorization_rejected' === $content_metadata_local_consent_result->get_error_code(), 'Content metadata authorization mismatch rejection uses stable error code.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$content_metadata_title_update = npcink_governance_core_fail_closed_content_metadata_apply_plan();
$content_metadata_title_update['write_actions'][0]['input']['title'] = 'Unexpected title write';
$content_metadata_title_result = $stack['service']->create_from_plan( 'npcink-abilities-toolkit/build-content-metadata-apply-plan', $content_metadata_title_update );
npcink_governance_core_fail_closed_assert( is_wp_error( $content_metadata_title_result ), 'Content metadata apply plan with title update is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_content_metadata_update_field_rejected' === $content_metadata_title_result->get_error_code(), 'Content metadata title update rejection uses stable error code.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$content_metadata_create_missing = npcink_governance_core_fail_closed_content_metadata_apply_plan();
$content_metadata_create_missing['write_actions'][2]['input']['create_missing'] = true;
$content_metadata_create_missing['write_actions'][2]['input']['terms'] = array( 'New Review Term' );
$content_metadata_create_missing_result = $stack['service']->create_from_plan( 'npcink-abilities-toolkit/build-content-metadata-apply-plan', $content_metadata_create_missing );
npcink_governance_core_fail_closed_assert( is_wp_error( $content_metadata_create_missing_result ), 'Content metadata apply plan with create_missing terms is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_content_metadata_create_missing_rejected' === $content_metadata_create_missing_result->get_error_code(), 'Content metadata create_missing rejection uses stable error code.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$content_metadata_taxonomy = npcink_governance_core_fail_closed_content_metadata_apply_plan();
$content_metadata_taxonomy['write_actions'][1]['input']['taxonomy'] = 'product_cat';
$content_metadata_taxonomy_result = $stack['service']->create_from_plan( 'npcink-abilities-toolkit/build-content-metadata-apply-plan', $content_metadata_taxonomy );
npcink_governance_core_fail_closed_assert( is_wp_error( $content_metadata_taxonomy_result ), 'Content metadata apply plan with unsupported taxonomy is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_content_metadata_taxonomy_rejected' === $content_metadata_taxonomy_result->get_error_code(), 'Content metadata taxonomy rejection uses stable error code.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$content_metadata_missing_controls = npcink_governance_core_fail_closed_content_metadata_apply_plan();
unset( $content_metadata_missing_controls['write_actions'][1]['input']['dry_run'], $content_metadata_missing_controls['write_actions'][1]['input']['commit'] );
$content_metadata_missing_controls_result = $stack['service']->create_from_plan( 'npcink-abilities-toolkit/build-content-metadata-apply-plan', $content_metadata_missing_controls );
npcink_governance_core_fail_closed_assert( is_wp_error( $content_metadata_missing_controls_result ), 'Content metadata apply plan without explicit dry_run/commit controls is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_content_metadata_commit_rejected' === $content_metadata_missing_controls_result->get_error_code(), 'Content metadata missing control rejection uses stable error code.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$content_metadata_term_extra = npcink_governance_core_fail_closed_content_metadata_apply_plan();
$content_metadata_term_extra['write_actions'][1]['input']['status'] = 'publish';
$content_metadata_term_extra_result = $stack['service']->create_from_plan( 'npcink-abilities-toolkit/build-content-metadata-apply-plan', $content_metadata_term_extra );
npcink_governance_core_fail_closed_assert( is_wp_error( $content_metadata_term_extra_result ), 'Content metadata apply plan with extra term action fields is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_content_metadata_term_field_rejected' === $content_metadata_term_extra_result->get_error_code(), 'Content metadata term field rejection uses stable error code.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$content_metadata_term_ids_string = npcink_governance_core_fail_closed_content_metadata_apply_plan();
$content_metadata_term_ids_string['write_actions'][2]['input']['term_ids'] = '41,42';
$content_metadata_term_ids_string_result = $stack['service']->create_from_plan( 'npcink-abilities-toolkit/build-content-metadata-apply-plan', $content_metadata_term_ids_string );
npcink_governance_core_fail_closed_assert( is_wp_error( $content_metadata_term_ids_string_result ), 'Content metadata apply plan with non-array term_ids is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_content_metadata_term_ids_missing' === $content_metadata_term_ids_string_result->get_error_code(), 'Content metadata term_ids type rejection uses stable error code.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$content_metadata_duplicate_excerpt = npcink_governance_core_fail_closed_content_metadata_apply_plan();
$content_metadata_duplicate_excerpt['write_actions'][1] = $content_metadata_duplicate_excerpt['write_actions'][0];
$content_metadata_duplicate_excerpt['write_actions'][1]['action_id'] = 'apply_second_selected_excerpt';
$content_metadata_duplicate_excerpt_result = $stack['service']->create_from_plan( 'npcink-abilities-toolkit/build-content-metadata-apply-plan', $content_metadata_duplicate_excerpt );
npcink_governance_core_fail_closed_assert( is_wp_error( $content_metadata_duplicate_excerpt_result ), 'Content metadata apply plan with duplicate excerpt actions is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_content_metadata_duplicate_action_rejected' === $content_metadata_duplicate_excerpt_result->get_error_code(), 'Content metadata duplicate excerpt rejection uses stable error code.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$content_metadata_duplicate_taxonomy = npcink_governance_core_fail_closed_content_metadata_apply_plan();
$content_metadata_duplicate_taxonomy['write_actions'][2] = $content_metadata_duplicate_taxonomy['write_actions'][1];
$content_metadata_duplicate_taxonomy['write_actions'][2]['action_id'] = 'assign_existing_categories_again';
$content_metadata_duplicate_taxonomy_result = $stack['service']->create_from_plan( 'npcink-abilities-toolkit/build-content-metadata-apply-plan', $content_metadata_duplicate_taxonomy );
npcink_governance_core_fail_closed_assert( is_wp_error( $content_metadata_duplicate_taxonomy_result ), 'Content metadata apply plan with duplicate taxonomy actions is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_content_metadata_duplicate_action_rejected' === $content_metadata_duplicate_taxonomy_result->get_error_code(), 'Content metadata duplicate taxonomy rejection uses stable error code.' );

