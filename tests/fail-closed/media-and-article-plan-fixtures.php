<?php
/** Media and article plan fixtures test module. @package NpcinkGovernanceCore */

/**
 * Creates a trusted cleanup batch payload.
 *
 * @return array<string,mixed>
 */
function npcink_governance_core_fail_closed_cleanup_batch_payload(): array {
	return array(
		'ability_id' => 'npcink-abilities-toolkit/trash-post',
		'title'      => 'Cleanup batch',
		'summary'    => 'Trash trusted test content.',
		'input'      => array(
			'write_actions' => array(
				array(
					'action_id'         => 'trash_test_post_101',
					'target_ability_id' => 'npcink-abilities-toolkit/trash-post',
					'input'             => array(
						'post_id' => 101,
						'dry_run' => true,
						'commit'  => false,
					),
					'requires_approval' => true,
					'commit_execution'  => false,
				),
			),
			'dry_run'       => true,
			'commit'        => false,
		),
		'preview'    => array(
			'source'       => array(
				'type'            => 'plan_to_proposal_batch',
				'plan_ability_id' => 'npcink-abilities-toolkit/build-nonproduction-content-cleanup-plan',
				'batch_approval'  => true,
			),
			'action_count' => 1,
			'plan_preview' => array(
				'posts' => array(
					array(
						'post_id'         => 101,
						'title'           => 'Core Plan Bridge Test Cleanup Candidate',
						'matched_pattern' => 'Core Plan Bridge Test Cleanup Candidate',
					),
				),
			),
		),
		'caller'     => array(
			'source'          => 'plan_to_proposal_batch',
			'plan_ability_id' => 'npcink-abilities-toolkit/build-nonproduction-content-cleanup-plan',
			'batch_id'        => 'fault_injection_cleanup',
		),
	);
}

/**
 * Creates a representative Toolbox article write plan.
 *
 * @return array<string,mixed>
 */
function npcink_governance_core_fail_closed_article_write_plan(): array {
	return array(
		'artifact_type'          => 'article_write_plan',
		'version'                => 1,
		'batch_id'               => 'article_write_fault_injection',
		'requires_approval'      => true,
		'dry_run'                => true,
		'commit_execution'       => false,
		'proposal_mode'          => 'single',
		'article_goal_brief'     => array(
			'topic' => 'Governed writing workflow',
		),
		'research_evidence_pack' => array(
			'sources' => array(
				array(
					'title' => 'Internal planning note',
					'url'   => 'https://example.test/article-contract',
				),
			),
		),
		'article_outline'        => array(
			'sections' => array(
				array( 'heading' => 'Overview' ),
			),
		),
		'article_draft_candidate' => array(
			'content_markdown'  => 'Draft body.',
			'used_sources'      => array( 'https://example.test/article-contract' ),
			'unverified_claims' => array(),
			'needs_human_input' => array(),
		),
		'discoverability_pack'   => array(
			'seo_title'       => 'Governed writing workflow',
			'seo_description' => 'Draft description.',
		),
		'article_risk_report'    => array(
			'risk_level'         => 'low',
			'blocked_claims'     => array(),
			'needs_review'       => array(),
			'ready_for_proposal' => true,
		),
		'write_actions'          => array(
			array(
				'action_id'         => 'create_article_draft',
				'target_ability_id' => 'npcink-abilities-toolkit/create-draft',
				'input'             => array(
					'title'   => 'Governed writing workflow',
					'content' => 'Draft body.',
					'status'  => 'draft',
					'dry_run' => true,
					'commit'  => false,
				),
				'risk'              => 'medium',
				'requires_approval' => true,
				'commit_execution'  => false,
				'proposal_ready'    => true,
			),
		),
	);
}

/**
 * Creates a representative Toolbox article batch write plan.
 *
 * @return array<string,mixed>
 */
function npcink_governance_core_fail_closed_article_batch_write_plan(): array {
	$single_plan = npcink_governance_core_fail_closed_article_write_plan();
	$articles    = array();
	$actions     = array();

	for ( $index = 1; $index <= 3; $index++ ) {
		$article = $single_plan;
		unset( $article['artifact_type'], $article['version'], $article['batch_id'], $article['requires_approval'], $article['dry_run'], $article['commit_execution'], $article['proposal_mode'], $article['write_actions'] );
		$article['article_goal_brief']['topic'] = 'Governed writing workflow ' . $index;
		$article['article_draft_candidate']['content_markdown'] = 'Draft body ' . $index . '.';
		$articles[] = $article;

		$action = $single_plan['write_actions'][0];
		$action['action_id'] = 'create_article_draft_' . $index;
		$action['input']['title'] = 'Governed writing workflow ' . $index;
		$action['input']['content'] = 'Draft body ' . $index . '.';
		$action['input']['idempotency_key'] = 'article-batch-' . $index;
		$actions[] = $action;
	}

	return array(
		'artifact_type'     => 'article_batch_write_plan',
		'version'           => 1,
		'batch_id'          => 'article_batch_write_fault_injection',
		'requires_approval' => true,
		'dry_run'           => true,
		'commit_execution'  => false,
		'proposal_mode'     => 'batch',
		'batch_approval'    => true,
		'articles'          => $articles,
		'write_actions'     => $actions,
		'risk'              => array(
			'level'  => 'medium',
			'reason' => 'Three draft-only article writes.',
		),
	);
}

/**
 * Creates a representative Toolbox article media batch write plan.
 *
 * @return array<string,mixed>
 */
function npcink_governance_core_fail_closed_article_media_batch_write_plan(): array {
	$single_plan = npcink_governance_core_fail_closed_article_write_plan();
	$article     = $single_plan;
	unset( $article['artifact_type'], $article['version'], $article['batch_id'], $article['requires_approval'], $article['dry_run'], $article['commit_execution'], $article['proposal_mode'], $article['write_actions'] );
	$article['featured_image_candidate'] = array(
		'provider'          => 'unsplash',
		'regular_url'       => 'https://images.example.test/photo.jpg',
		'source_url'        => 'https://unsplash.com/photos/example',
		'download_location' => 'https://api.unsplash.com/photos/example/download',
		'photographer'      => 'Example Photographer',
		'attribution'       => 'Photo by Example Photographer on Unsplash.',
	);

	return array(
		'artifact_type'     => 'article_media_batch_write_plan',
		'version'           => 1,
		'batch_id'          => 'article_media_batch_write_fault_injection',
		'requires_approval' => true,
		'dry_run'           => true,
		'commit_execution'  => false,
		'proposal_mode'     => 'batch',
		'batch_approval'    => true,
		'articles'          => array( $article ),
		'media_workflow'    => array(
			array(
				'article_index'      => 0,
				'candidate_provider' => 'unsplash',
				'source_url'         => 'https://unsplash.com/photos/example',
				'download_location'  => 'https://api.unsplash.com/photos/example/download',
				'attribution'        => 'Photo by Example Photographer on Unsplash.',
			),
		),
		'write_actions'     => array(
			array(
				'action_id'         => 'create_article_draft_1',
				'target_ability_id' => 'npcink-abilities-toolkit/create-draft',
				'input'             => array(
					'title'   => 'Governed writing workflow',
					'content' => 'Draft body.',
					'status'  => 'draft',
					'dry_run' => true,
					'commit'  => false,
				),
				'requires_approval' => true,
				'commit_execution'  => false,
				'proposal_ready'    => true,
			),
			array(
				'action_id'         => 'upload_featured_image_1',
				'target_ability_id' => 'npcink-abilities-toolkit/upload-media-from-url',
				'depends_on'        => array( 'create_article_draft_1' ),
				'input'             => array(
					'url'               => 'https://images.example.test/photo.jpg',
					'alt'               => 'Example image alt text.',
					'source_type'       => 'stock',
					'source_page_url'   => 'https://unsplash.com/photos/example',
					'photographer_name' => 'Example Photographer',
					'attribution_text'  => 'Photo by Example Photographer on Unsplash.',
					'attach_to_post_id' => '$outputs.create_article_draft_1.post_id',
					'dry_run'           => true,
					'commit'            => false,
				),
				'requires_approval' => true,
				'commit_execution'  => false,
				'proposal_ready'    => true,
			),
			array(
				'action_id'         => 'update_featured_image_details_1',
				'target_ability_id' => 'npcink-abilities-toolkit/update-media-details',
				'depends_on'        => array( 'upload_featured_image_1' ),
				'input'             => array(
					'attachment_id'     => '$outputs.upload_featured_image_1.attachment_id',
					'alt'               => 'Example image alt text.',
					'source_type'       => 'stock',
					'source_page_url'   => 'https://unsplash.com/photos/example',
					'photographer_name' => 'Example Photographer',
					'attribution_text'  => 'Photo by Example Photographer on Unsplash.',
					'dry_run'           => true,
					'commit'            => false,
				),
				'requires_approval' => true,
				'commit_execution'  => false,
				'proposal_ready'    => true,
			),
			array(
				'action_id'         => 'set_featured_image_1',
				'target_ability_id' => 'npcink-abilities-toolkit/set-post-featured-image',
				'depends_on'        => array( 'create_article_draft_1', 'upload_featured_image_1' ),
				'input'             => array(
					'post_id'       => '$outputs.create_article_draft_1.post_id',
					'attachment_id' => '$outputs.upload_featured_image_1.attachment_id',
					'dry_run'       => true,
					'commit'        => false,
				),
				'requires_approval' => true,
				'commit_execution'  => false,
				'proposal_ready'    => true,
			),
		),
		'risk'              => array(
			'level'  => 'medium',
			'reason' => 'Draft creation with reviewed media upload and featured-image assignment.',
		),
	);
}

/**
 * Creates exact local proposal evidence for one Cloud media derivative.
 *
 * @param array<string,mixed> $overrides Field overrides.
 * @return array<string,mixed>
 */
function npcink_governance_core_fail_closed_media_derivative_artifact( array $overrides = array() ): array {
	return array_merge(
		array(
			'artifact_id'        => 'art_00000000000000000000000000001493',
			'expires_at'         => gmdate( 'Y-m-d\TH:i:s\Z', time() + 600 ),
			'mime_type'          => 'image/webp',
			'format'             => 'webp',
			'width'              => 1200,
			'height'             => 800,
			'filesize_bytes'     => 210000,
			'sha256'             => hash( 'sha256', 'core-media-derivative-fixture' ),
			'suggested_filename' => 'optimized-1493.webp',
			'filename_basis'     => array(
				'owner'                          => 'wordpress_write_ability_final',
				'strategy'                       => 'format_checksum',
				'final_sanitize_unique_required' => true,
			),
			'processing_warnings' => array(),
		),
		$overrides
	);
}

/**
 * Creates a representative media optimization plan.
 *
 * @return array<string,mixed>
 */
function npcink_governance_core_fail_closed_media_optimization_plan(): array {
	return array(
		'artifact_type'       => 'media_optimization_plan',
		'version'             => 1,
		'batch_id'            => 'media_optimization_fault_injection',
		'attachment_id'       => 1493,
		'optimization_goal'   => 'image_seo_and_webp',
		'requires_approval'   => true,
		'dry_run'             => true,
		'commit_execution'    => false,
		'proposal_mode'       => 'batch',
		'batch_approval'      => true,
		'metadata_preview'    => array(
			'before' => array( 'alt' => '' ),
			'after'  => array( 'alt' => 'AI generated product image' ),
		),
		'derivative_preview'  => array(
			'before' => array( 'mime_type' => 'image/png', 'size_bytes' => 900000 ),
			'after'  => array( 'mime_type' => 'image/webp', 'size_bytes' => 210000 ),
		),
		'write_actions'       => array(
			array(
				'action_id'         => 'update_media_details',
				'target_ability_id' => 'npcink-abilities-toolkit/update-media-details',
				'input'             => array(
					'attachment_id'    => 1493,
					'title'            => 'Optimized AI image',
					'alt'              => 'AI generated product image',
					'caption'          => 'AI generated product image.',
					'description'      => 'Optimized image metadata.',
					'source_type'      => 'ai_generated',
					'dry_run'          => true,
					'commit'           => false,
					'idempotency_key'  => 'media-optimize-metadata-1493',
				),
				'risk'              => 'medium',
				'requires_approval' => true,
				'commit_execution'  => false,
				'proposal_ready'    => true,
			),
			array(
				'action_id'         => 'adopt_webp_derivative',
				'target_ability_id' => 'npcink-abilities-toolkit/adopt-cloud-media-derivative',
				'input'             => array(
					'attachment_id'                  => 1493,
					'derivative_artifact'            => npcink_governance_core_fail_closed_media_derivative_artifact(),
					'expected_current_mime_type'     => 'image/png',
					'expected_derivative_mime_type'  => 'image/webp',
					'dry_run'                        => true,
					'commit'                         => false,
					'idempotency_key'                => 'media-optimize-derivative-1493',
				),
				'risk'              => 'medium',
				'requires_approval' => true,
				'commit_execution'  => false,
				'proposal_ready'    => true,
			),
		),
		'risk'                => array(
			'level'  => 'medium',
			'reason' => 'One attachment metadata update and derivative adoption.',
		),
	);
}

/**
 * Creates a representative media adoption enhancement plan.
 *
 * @return array<string,mixed>
 */
function npcink_governance_core_fail_closed_media_adoption_enhancement_plan(): array {
	return array(
		'artifact_type'            => 'media_adoption_enhancement_plan',
		'version'                  => 1,
		'batch_id'                 => 'media_adoption_enhancement_fault_injection',
		'post_id'                  => 8842,
		'attach_to_post_id'        => 8842,
		'requires_approval'        => true,
		'dry_run'                  => true,
		'commit_execution'         => false,
		'direct_wordpress_write'   => false,
		'proposal_mode'            => 'batch',
		'batch_approval'           => true,
		'action_count'             => 3,
		'media'                    => array(
			'url'               => 'https://images.example.test/generated-dashboard.png',
			'attach_to_post_id' => 8842,
		),
		'reference_repair'         => array(
			'post_id' => 8842,
			'old_url' => 'https://example.test/wp-content/uploads/2026/06/old-dashboard.png',
		),
		'write_actions'            => array(
			array(
				'action_id'         => 'upload-media-asset',
				'target_ability_id' => 'npcink-abilities-toolkit/upload-media-from-url',
				'input'             => array(
					'url'               => 'https://images.example.test/generated-dashboard.png',
					'file_name'         => 'generated-dashboard.png',
					'title'             => 'Generated dashboard',
					'alt'               => 'Generated dashboard preview for WordPress AI',
					'source_type'       => 'ai_generated',
					'attach_to_post_id' => 8842,
					'dry_run'           => true,
					'commit'            => false,
					'idempotency_key'   => 'media-adoption-upload-8842',
				),
				'risk'              => 'medium',
				'requires_approval' => true,
				'commit_execution'  => false,
				'proposal_ready'    => true,
			),
			array(
				'action_id'         => 'optimize-media-asset',
				'target_ability_id' => 'npcink-abilities-toolkit/optimize-media-asset',
				'depends_on'        => array( 'upload-media-asset' ),
				'input'             => array(
					'attachment_id'     => '$outputs.upload-media-asset.attachment_id',
					'target_max_width'  => 1600,
					'preferred_format'  => 'webp',
					'quality'           => 82,
					'derivative_suffix' => '-optimized',
					'dry_run'           => true,
					'commit'            => false,
					'idempotency_key'   => 'media-adoption-optimize-8842',
				),
				'risk'              => 'medium',
				'requires_approval' => true,
				'commit_execution'  => false,
				'proposal_ready'    => true,
			),
			array(
				'action_id'         => 'repair-post-media-reference',
				'target_ability_id' => 'npcink-abilities-toolkit/patch-post-content',
				'depends_on'        => array( 'optimize-media-asset' ),
				'input'             => array(
					'post_id'         => 8842,
					'operations'      => array(
						array(
							'op'      => 'replace',
							'find'    => 'https://example.test/wp-content/uploads/2026/06/old-dashboard.png',
							'replace' => '$outputs.optimize-media-asset.derivative_url',
							'limit'   => 1,
						),
					),
					'dry_run'         => true,
					'commit'          => false,
					'idempotency_key' => 'media-adoption-repair-8842',
				),
				'risk'              => 'medium',
				'requires_approval' => true,
				'commit_execution'  => false,
				'proposal_ready'    => true,
			),
		),
		'risk'                     => array(
			'level'  => 'medium',
			'reason' => 'Imports a reviewed image URL, optimizes it locally, and repairs a reviewed page reference.',
		),
	);
}

/**
 * Creates a representative media rename plan.
 *
 * @return array<string,mixed>
 */
function npcink_governance_core_fail_closed_media_rename_plan(): array {
	return array(
		'artifact_type'       => 'media_rename_plan',
		'version'             => 1,
		'batch_id'            => 'media_rename_fault_injection',
		'attachment_id'       => 1493,
		'requires_approval'   => true,
		'dry_run'             => true,
		'commit_execution'    => false,
		'proposal_mode'       => 'single',
		'batch_approval'      => false,
		'action_count'        => 1,
		'preview'             => array(
			'before' => array(
				'relative_file' => '2026/05/old-name.webp',
				'file_basename' => 'old-name.webp',
				'mime_type'     => 'image/webp',
				'content_hashes' => array(
					'md5'    => '1d7ea1565313df58fa0769e93e5310df',
					'sha256' => str_repeat( 'a', 64 ),
				),
			),
			'after_suggestion' => array(
				'relative_file' => '2026/05/1d7ea1565313df58fa0769e93e5310df.webp',
				'file_basename' => '1d7ea1565313df58fa0769e93e5310df.webp',
				'mime_type'     => 'image/webp',
			),
		),
		'write_actions'       => array(
			array(
				'action_id'         => 'rename_media_file_1493',
				'target_ability_id' => 'npcink-abilities-toolkit/rename-media-file',
				'input'             => array(
					'attachment_id'                  => 1493,
					'target_file_name'               => '1d7ea1565313df58fa0769e93e5310df.webp',
					'expected_current_relative_file' => '2026/05/old-name.webp',
					'expected_current_mime_type'     => 'image/webp',
					'expected_current_md5'           => '1d7ea1565313df58fa0769e93e5310df',
					'dry_run'                        => true,
					'commit'                         => false,
					'idempotency_key'                => 'media-rename-1493',
				),
				'risk'              => 'medium',
				'requires_approval' => true,
				'commit_execution'  => false,
				'proposal_ready'    => true,
			),
		),
		'risk'                => array(
			'level'  => 'medium',
			'reason' => 'One attachment main file rename changes its public URL.',
		),
	);
}

