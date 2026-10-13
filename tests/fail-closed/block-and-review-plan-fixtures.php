<?php
/** Block and review plan fixtures test module. @package NpcinkGovernanceCore */

/**
 * Creates a representative Gutenberg pattern page plan.
 *
 * @return array<string,mixed>
 */
function npcink_governance_core_fail_closed_pattern_page_plan(): array {
	return array(
		'artifact_type'            => 'pattern_page_plan',
		'version'                  => 1,
		'batch_id'                 => 'pattern_page_fault_injection',
		'pattern_id'               => 'openai-style-landing',
		'style_preset'             => 'minimal-dark-light',
		'requires_approval'        => true,
		'dry_run'                  => true,
		'commit_execution'         => false,
		'direct_wordpress_write'   => false,
		'proposal_mode'            => 'batch',
		'summary'                  => array(
			'block_count'  => 3,
			'action_count' => 2,
		),
		'allowed_classes'          => array(
			'npcink-ai-page',
			'npcink-ai-hero',
			'npcink-ai-title',
			'npcink-ai-lede',
		),
		'write_actions'            => array(
			array(
				'action_id'         => 'create-pattern-page',
				'target_ability_id' => 'npcink-abilities-toolkit/create-draft',
				'input'             => array(
					'post_type'      => 'page',
					'status'         => 'draft',
					'title'          => 'WordPress AI',
					'content_format' => 'html',
					'content'        => '',
					'dry_run'        => true,
					'commit'         => false,
					'idempotency_key' => 'pattern-page-create',
				),
				'risk'              => 'medium',
				'requires_approval' => true,
				'commit_execution'  => false,
				'proposal_ready'    => true,
			),
			array(
				'action_id'         => 'update-pattern-page-blocks',
				'target_ability_id' => 'npcink-abilities-toolkit/update-post-blocks',
				'depends_on'        => array( 'create-pattern-page' ),
				'input'             => array(
					'post_id'            => '$outputs.create-pattern-page.post_id',
					'mode'               => 'replace',
					'validate_roundtrip' => true,
					'blocks'             => array(
						array(
							'blockName'    => 'core/group',
							'attrs'        => array( 'className' => 'npcink-ai-page npcink-ai-hero' ),
							'innerBlocks'  => array(
								array(
									'blockName'    => 'core/heading',
									'attrs'        => array( 'className' => 'npcink-ai-title' ),
									'innerBlocks'  => array(),
									'innerHTML'    => '<h2 class="wp-block-heading npcink-ai-title">WordPress AI</h2>',
									'innerContent' => array( '<h2 class="wp-block-heading npcink-ai-title">WordPress AI</h2>' ),
								),
								array(
									'blockName'    => 'core/paragraph',
									'attrs'        => array( 'className' => 'npcink-ai-lede' ),
									'innerBlocks'  => array(),
									'innerHTML'    => '<p class="npcink-ai-lede">Governed AI workflow for WordPress.</p>',
									'innerContent' => array( '<p class="npcink-ai-lede">Governed AI workflow for WordPress.</p>' ),
								),
							),
							'innerHTML'    => '<div class="wp-block-group npcink-ai-page npcink-ai-hero"></div>',
							'innerContent' => array( null, null ),
						),
					),
					'dry_run'            => true,
					'commit'             => false,
					'idempotency_key'    => 'pattern-page-blocks',
				),
				'risk'              => 'medium',
				'requires_approval' => true,
				'commit_execution'  => false,
				'proposal_ready'    => true,
			),
		),
		'risk'                     => array(
			'level'  => 'medium',
			'reason' => 'Creates a draft page and replaces it with allowlisted Gutenberg pattern blocks.',
		),
	);
}

/**
 * Creates a representative block theme site plan.
 *
 * @return array<string,mixed>
 */
function npcink_governance_core_fail_closed_block_theme_site_plan(): array {
	return array(
		'artifact_type'          => 'block_theme_site_plan',
		'version'                => 1,
		'batch_id'               => 'block_theme_site_fault_injection',
		'intent'                 => 'add_breadcrumbs',
		'active_theme'           => array(
			'name'       => 'Twenty Twenty-Five',
			'stylesheet' => 'twentytwentyfive',
		),
		'affected_templates'     => array( 'single' ),
		'requires_approval'      => true,
		'dry_run'                => true,
		'commit_execution'       => false,
		'direct_wordpress_write' => false,
		'proposal_mode'          => 'batch',
		'summary'                => array(
			'block_count'  => 2,
			'action_count' => 1,
		),
		'preview'                => array(
			array(
				'target_type'               => 'wp_template',
				'post_id'                   => 0,
				'slug'                      => 'single',
				'theme'                     => 'twentytwentyfive',
				'source'                    => 'theme',
				'creates_template_override' => true,
				'target_ability_id'         => 'npcink-abilities-toolkit/upsert-template-blocks',
			),
		),
		'write_actions'          => array(
			array(
				'action_id'         => 'upsert-template-single-breadcrumbs',
				'target_ability_id' => 'npcink-abilities-toolkit/upsert-template-blocks',
				'input'             => array(
					'mode'               => 'replace',
					'validate_roundtrip' => true,
					'slug'               => 'single',
					'theme'              => 'twentytwentyfive',
					'title'              => 'Single',
					'source_template_id' => 'twentytwentyfive//single',
					'blocks'             => array(
						array(
							'blockName'    => 'core/group',
							'attrs'        => array( 'className' => 'openclaw-breadcrumbs' ),
							'innerBlocks'  => array(
								array(
									'blockName'    => 'core/paragraph',
									'attrs'        => array( 'className' => 'openclaw-breadcrumbs__trail' ),
									'innerBlocks'  => array(),
									'innerHTML'    => '<p class="openclaw-breadcrumbs__trail">Home / Current item</p>',
									'innerContent' => array( '<p class="openclaw-breadcrumbs__trail">Home / Current item</p>' ),
								),
							),
							'innerHTML'    => '<div class="wp-block-group openclaw-breadcrumbs"></div>',
							'innerContent' => array( null ),
						),
					),
					'dry_run'            => true,
					'commit'             => false,
					'idempotency_key'    => 'block-theme-site-upsert',
				),
				'risk'              => 'high',
				'requires_approval' => true,
				'commit_execution'  => false,
				'proposal_ready'    => true,
			),
		),
		'risk'                   => array(
			'level'  => 'high',
			'reason' => 'Creates a reviewed Site Editor template override from an active theme file template.',
		),
	);
}

/**
 * Creates a representative block theme template layout plan.
 *
 * @return array<string,mixed>
 */
function npcink_governance_core_fail_closed_block_theme_layout_plan(): array {
	$plan = npcink_governance_core_fail_closed_block_theme_site_plan();
	$plan['batch_id']       = 'block_theme_layout_fault_injection';
	$plan['intent']         = 'customize_template_layout';
	$plan['layout_profile'] = 'article_standard';
	$plan['template_layout_contract'] = array(
		'catalog_id'                => 'gutenberg_native_v1',
		'catalog_version'           => '1.0',
		'compiler_version'          => 'block_theme_profile_compiler@0.3',
		'forbidden_policy_version'  => 'block_theme_safe_core_blocks@0.2',
		'surface'                   => 'template',
		'intent'                    => 'customize_template_layout',
		'placement_model'           => 'bounded_template_layout_profile',
		'accepted_profiles'         => array( 'article_standard', 'page_standard', 'homepage_landing' ),
		'accepted_profile_versions' => array( 'article_standard@0.4', 'page_standard@0.2', 'homepage_landing@0.3' ),
		'forbidden_outputs'         => array( 'raw_template_html', 'core/html', 'core/freeform', 'non_core_blocks', 'custom_css', 'theme_json', 'global_styles', 'navigation_write', 'template_part_write' ),
		'contract_status'           => 'pass',
		'violation_codes'           => array(),
		'profiles'                  => array(
			array(
				'slug'              => 'single',
				'layout_profile'    => 'article_standard',
				'profile_id'        => 'article_standard@0.4',
				'profile_version'   => 'article_standard@0.4',
				'compiler_version'  => 'block_theme_profile_compiler@0.3',
				'operation'         => 'replace_template_layout_with_preserved_template_parts',
				'profile_allowed'   => true,
				'modules'           => array( 'header', 'breadcrumbs', 'post_title', 'author_date', 'post_categories', 'featured_image', 'post_content', 'post_tags', 'post_navigation', 'comments', 'related_posts', 'footer' ),
				'sections'          => array( 'header', 'breadcrumbs', 'post_title', 'author_date', 'post_categories', 'featured_image', 'post_content', 'post_tags', 'post_navigation', 'comments', 'related_posts', 'footer' ),
				'allowed_blocks'    => array( 'core/template-part', 'core/group', 'core/heading', 'core/paragraph', 'core/post-title', 'core/post-author-name', 'core/post-date', 'core/post-featured-image', 'core/post-terms', 'core/post-navigation-link', 'core/comments', 'core/post-content', 'core/latest-posts' ),
				'forbidden_outputs' => array( 'raw_template_html', 'core/html', 'core/freeform', 'non_core_blocks', 'custom_css', 'theme_json', 'global_styles', 'navigation_write', 'template_part_write' ),
			),
		),
	);
	$plan['preview'][0]['layout_profile'] = 'article_standard';
	$plan['preview'][0]['layout_sections'] = array( 'header', 'breadcrumbs', 'post_title', 'author_date', 'featured_image', 'post_content', 'related_posts', 'footer' );
	$plan['write_actions'][0]['action_id'] = 'upsert-template-single-layout';
	$plan['write_actions'][0]['input']['blocks'] = array(
		array(
			'blockName'    => 'core/template-part',
			'attrs'        => array( 'slug' => 'header', 'theme' => 'twentytwentyfive' ),
			'innerBlocks'  => array(),
			'innerHTML'    => '',
			'innerContent' => array(),
		),
		array(
			'blockName'    => 'core/group',
			'attrs'        => array( 'tagName' => 'main', 'className' => 'openclaw-template-layout openclaw-template-layout-article_standard' ),
			'innerBlocks'  => array(
				array(
					'blockName'    => 'core/post-title',
					'attrs'        => array( 'level' => 1 ),
					'innerBlocks'  => array(),
					'innerHTML'    => '',
					'innerContent' => array(),
				),
				array(
					'blockName'    => 'core/post-content',
					'attrs'        => array(),
					'innerBlocks'  => array(),
					'innerHTML'    => '',
					'innerContent' => array(),
				),
			),
			'innerHTML'    => '<main class="wp-block-group openclaw-template-layout openclaw-template-layout-article_standard"></main>',
			'innerContent' => array( '<main class="wp-block-group openclaw-template-layout openclaw-template-layout-article_standard">', null, null, '</main>' ),
		),
		array(
			'blockName'    => 'core/template-part',
			'attrs'        => array( 'slug' => 'footer', 'theme' => 'twentytwentyfive' ),
			'innerBlocks'  => array(),
			'innerHTML'    => '',
			'innerContent' => array(),
		),
	);

	return $plan;
}

/**
 * Creates a representative homepage block theme template layout plan.
 *
 * @return array<string,mixed>
 */
function npcink_governance_core_fail_closed_block_theme_homepage_layout_plan(): array {
	$plan = npcink_governance_core_fail_closed_block_theme_layout_plan();
	$plan['batch_id']       = 'block_theme_homepage_layout_fault_injection';
	$plan['layout_profile'] = 'homepage_landing';
	$plan['affected_templates'] = array( 'front-page' );
	$plan['template_layout_contract']['profiles'][0] = array(
		'slug'              => 'front-page',
		'layout_profile'    => 'homepage_landing',
		'profile_id'        => 'homepage_landing@0.3',
		'profile_version'   => 'homepage_landing@0.3',
		'compiler_version'  => 'block_theme_profile_compiler@0.3',
		'operation'         => 'replace_template_layout_with_preserved_template_parts',
		'profile_allowed'   => true,
		'modules'           => array( 'header', 'hero', 'entry_columns', 'primary_cta', 'latest_posts', 'category_links', 'final_cta', 'footer' ),
		'sections'          => array( 'header', 'hero', 'entry_columns', 'primary_cta', 'latest_posts', 'category_links', 'final_cta', 'footer' ),
		'allowed_blocks'    => array( 'core/template-part', 'core/group', 'core/heading', 'core/paragraph', 'core/buttons', 'core/button', 'core/columns', 'core/column', 'core/latest-posts', 'core/categories', 'core/separator', 'core/spacer' ),
		'forbidden_outputs' => array( 'raw_template_html', 'core/html', 'core/freeform', 'non_core_blocks', 'custom_css', 'theme_json', 'global_styles', 'navigation_write', 'template_part_write' ),
	);
	$plan['preview'][0]['slug'] = 'front-page';
	$plan['preview'][0]['layout_profile'] = 'homepage_landing';
	$plan['preview'][0]['layout_sections'] = array( 'header', 'hero', 'latest_posts', 'category_links', 'footer' );
	$plan['write_actions'][0]['action_id'] = 'upsert-template-front-page-layout';
	$plan['write_actions'][0]['input']['slug'] = 'front-page';
	$plan['write_actions'][0]['input']['title'] = 'Front Page';
	$plan['write_actions'][0]['input']['source_template_id'] = 'twentytwentyfive//front-page';
	$plan['write_actions'][0]['input']['blocks'] = array(
		array(
			'blockName'    => 'core/template-part',
			'attrs'        => array( 'slug' => 'header', 'theme' => 'twentytwentyfive' ),
			'innerBlocks'  => array(),
			'innerHTML'    => '',
			'innerContent' => array(),
		),
		array(
			'blockName'    => 'core/group',
			'attrs'        => array( 'tagName' => 'main', 'className' => 'openclaw-template-layout openclaw-template-layout-homepage_landing' ),
			'innerBlocks'  => array(
				array(
					'blockName'    => 'core/group',
					'attrs'        => array( 'className' => 'openclaw-homepage-hero' ),
					'innerBlocks'  => array(
						array(
							'blockName'    => 'core/heading',
							'attrs'        => array( 'level' => 1 ),
							'innerBlocks'  => array(),
							'innerHTML'    => '<h1>Welcome</h1>',
							'innerContent' => array( '<h1>Welcome</h1>' ),
						),
						array(
							'blockName'    => 'core/paragraph',
							'attrs'        => array(),
							'innerBlocks'  => array(),
							'innerHTML'    => '<p>Homepage introduction.</p>',
							'innerContent' => array( '<p>Homepage introduction.</p>' ),
						),
						array(
							'blockName'    => 'core/buttons',
							'attrs'        => array(),
							'innerBlocks'  => array(
								array(
									'blockName'    => 'core/button',
									'attrs'        => array( 'url' => 'https://example.test/blog' ),
									'innerBlocks'  => array(),
									'innerHTML'    => '<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="https://example.test/blog">Read Blog</a></div>',
									'innerContent' => array( '<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="https://example.test/blog">Read Blog</a></div>' ),
								),
							),
							'innerHTML'    => '<div class="wp-block-buttons"></div>',
							'innerContent' => array( '<div class="wp-block-buttons">', null, '</div>' ),
						),
					),
					'innerHTML'    => '<div class="wp-block-group openclaw-homepage-hero"></div>',
					'innerContent' => array( '<div class="wp-block-group openclaw-homepage-hero">', null, null, null, '</div>' ),
				),
				array(
					'blockName'    => 'core/latest-posts',
					'attrs'        => array( 'postsToShow' => 6 ),
					'innerBlocks'  => array(),
					'innerHTML'    => '',
					'innerContent' => array(),
				),
				array(
					'blockName'    => 'core/categories',
					'attrs'        => array( 'showPostCounts' => true ),
					'innerBlocks'  => array(),
					'innerHTML'    => '',
					'innerContent' => array(),
				),
			),
			'innerHTML'    => '<main class="wp-block-group openclaw-template-layout openclaw-template-layout-homepage_landing"></main>',
			'innerContent' => array( '<main class="wp-block-group openclaw-template-layout openclaw-template-layout-homepage_landing">', null, null, null, '</main>' ),
		),
		array(
			'blockName'    => 'core/template-part',
			'attrs'        => array( 'slug' => 'footer', 'theme' => 'twentytwentyfive' ),
			'innerBlocks'  => array(),
			'innerHTML'    => '',
			'innerContent' => array(),
		),
	);

	return $plan;
}

/**
 * Creates a representative Gutenberg article block plan.
 *
 * @return array<string,mixed>
 */
function npcink_governance_core_fail_closed_article_block_plan(): array {
	return array(
		'artifact_type'          => 'article_block_plan',
		'version'                => 1,
		'batch_id'               => 'article_block_fault_injection',
		'article_template'       => 'comparison-review',
		'responsive_profile'     => 'article_standard',
		'media_strategy'         => 'existing_media_url',
		'requires_approval'      => true,
		'dry_run'                => true,
		'commit_execution'       => false,
		'direct_wordpress_write' => false,
		'proposal_mode'          => 'batch',
		'summary'                => array(
			'block_count'  => 5,
			'action_count' => 2,
		),
		'editorial_quality'      => array(
			'pattern_version'         => '1.0',
			'uses_native_blocks'      => true,
			'has_takeaways'           => true,
			'has_faq'                 => true,
			'has_comparison_columns'  => true,
			'custom_css_required'     => false,
		),
		'responsive_quality'     => array(
			'responsive_profile'          => 'article_standard',
			'uses_core_responsive_blocks' => true,
			'uses_mobile_stack'           => true,
			'has_responsive_media'        => true,
			'max_columns_per_row'         => 2,
			'custom_css_required'         => false,
		),
		'write_actions'          => array(
			array(
				'action_id'         => 'create-article-draft',
				'target_ability_id' => 'npcink-abilities-toolkit/create-draft',
				'input'             => array(
					'post_type'      => 'post',
					'status'         => 'draft',
					'title'          => 'Gutenberg Article Draft',
					'content_format' => 'html',
					'content'        => '',
					'dry_run'        => true,
					'commit'         => false,
					'idempotency_key' => 'article-block-create',
				),
				'risk'              => 'medium',
				'requires_approval' => true,
				'commit_execution'  => false,
				'proposal_ready'    => true,
			),
			array(
				'action_id'         => 'update-article-blocks',
				'target_ability_id' => 'npcink-abilities-toolkit/update-post-blocks',
				'depends_on'        => array( 'create-article-draft' ),
				'input'             => array(
					'post_id'            => '$outputs.create-article-draft.post_id',
					'mode'               => 'replace',
					'validate_roundtrip' => true,
					'blocks'             => array(
						array(
							'blockName'    => 'core/paragraph',
							'attrs'        => array(),
							'innerBlocks'  => array(),
							'innerHTML'    => '<p>Article intro.</p>',
							'innerContent' => array( '<p>Article intro.</p>' ),
						),
						array(
							'blockName'    => 'core/image',
							'attrs'        => array(
								'url'             => 'https://example.test/wp-content/uploads/article.jpg',
								'alt'             => 'Article image',
								'sizeSlug'        => 'large',
								'linkDestination' => 'none',
							),
							'innerBlocks'  => array(),
							'innerHTML'    => '<figure class="wp-block-image size-large"><img src="https://example.test/wp-content/uploads/article.jpg" alt="Article image"/></figure>',
							'innerContent' => array( '<figure class="wp-block-image size-large"><img src="https://example.test/wp-content/uploads/article.jpg" alt="Article image"/></figure>' ),
						),
						array(
							'blockName'    => 'core/columns',
							'attrs'        => array( 'isStackedOnMobile' => true ),
							'innerBlocks'  => array(),
							'innerHTML'    => '<div class="wp-block-columns"></div>',
							'innerContent' => array( '<div class="wp-block-columns"></div>' ),
						),
						array(
							'blockName'    => 'core/details',
							'attrs'        => array( 'summary' => 'Can editors continue editing?' ),
							'innerBlocks'  => array(
								array(
									'blockName'    => 'core/paragraph',
									'attrs'        => array(),
									'innerBlocks'  => array(),
									'innerHTML'    => '<p>Yes.</p>',
									'innerContent' => array( '<p>Yes.</p>' ),
								),
							),
							'innerHTML'    => '<details class="wp-block-details"><summary>Can editors continue editing?</summary></details>',
							'innerContent' => array( '<details class="wp-block-details"><summary>Can editors continue editing?</summary>', null, '</details>' ),
						),
					),
					'dry_run'            => true,
					'commit'             => false,
					'idempotency_key'    => 'article-blocks',
				),
				'risk'              => 'medium',
				'requires_approval' => true,
				'commit_execution'  => false,
				'proposal_ready'    => true,
			),
		),
		'risk'                   => array(
			'level'  => 'medium',
			'reason' => 'Creates a draft post and replaces it with Gutenberg-native editorial blocks.',
		),
	);
}

/**
 * Creates a representative image candidate adoption plan.
 *
 * @return array<string,mixed>
 */
function npcink_governance_core_fail_closed_image_candidate_adoption_plan(): array {
	return array(
		'artifact_type'              => 'image_candidate_adoption_plan',
		'version'                    => 1,
		'candidate_contract_version' => 'image_candidate.v1',
		'batch_id'                   => 'image_candidate_adoption_fault_injection',
		'requires_approval'          => true,
		'dry_run'                    => true,
		'commit_execution'           => false,
		'proposal_mode'              => 'batch',
		'batch_approval'             => true,
		'selected_image_candidate'   => array(
			'contract_version' => 'image_candidate.v1',
			'source_type'      => 'stock',
			'provider'         => 'unsplash',
			'provider_origin'  => 'toolbox',
			'download_url'     => 'https://images.example.test/photo.jpg',
			'thumbnail_url'    => 'https://images.example.test/photo-thumb.jpg',
			'source_url'       => 'https://unsplash.com/photos/example',
			'attribution'      => 'Photo by Example on Unsplash.',
		),
		'write_actions'              => array(
			array(
				'action_id'         => 'upload_image_candidate',
				'target_ability_id' => 'npcink-abilities-toolkit/upload-media-from-url',
				'input'             => array(
					'url'               => 'https://images.example.test/photo.jpg',
					'title'             => 'Reviewed image candidate',
					'alt'               => 'Reviewed image candidate',
					'caption'           => 'Photo by Example on Unsplash.',
					'description'       => 'Reviewed stock image.',
					'source_type'       => 'stock',
					'source_page_url'   => 'https://unsplash.com/photos/example',
					'photographer_name' => 'Example',
					'attribution_text'  => 'Photo by Example on Unsplash.',
					'attach_to_post_id' => 1493,
					'dry_run'           => true,
					'commit'            => false,
					'idempotency_key'   => 'image-candidate-upload-1493',
				),
				'risk'              => 'medium',
				'requires_approval' => true,
				'commit_execution'  => false,
				'proposal_ready'    => true,
			),
			array(
				'action_id'         => 'update_image_candidate_details',
				'target_ability_id' => 'npcink-abilities-toolkit/update-media-details',
				'depends_on'        => array( 'upload_image_candidate' ),
				'input'             => array(
					'attachment_id'     => '$outputs.upload_image_candidate.attachment_id',
					'title'             => 'Reviewed image candidate',
					'alt'               => 'Reviewed image candidate',
					'caption'           => 'Photo by Example on Unsplash.',
					'description'       => 'Reviewed stock image.',
					'source_type'       => 'stock',
					'source_page_url'   => 'https://unsplash.com/photos/example',
					'photographer_name' => 'Example',
					'attribution_text'  => 'Photo by Example on Unsplash.',
					'dry_run'           => true,
					'commit'            => false,
					'idempotency_key'   => 'image-candidate-details-1493',
				),
				'risk'              => 'medium',
				'requires_approval' => true,
				'commit_execution'  => false,
				'proposal_ready'    => true,
			),
			array(
				'action_id'         => 'set_image_candidate_featured_image',
				'target_ability_id' => 'npcink-abilities-toolkit/set-post-featured-image',
				'depends_on'        => array( 'upload_image_candidate' ),
				'input'             => array(
					'post_id'       => 1493,
					'attachment_id' => '$outputs.upload_image_candidate.attachment_id',
					'dry_run'       => true,
					'commit'        => false,
					'idempotency_key' => 'image-candidate-featured-1493',
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
 * Creates a representative Site Knowledge review plan.
 *
 * @return array<string,mixed>
 */
function npcink_governance_core_fail_closed_site_knowledge_review_plan(): array {
	return array(
		'artifact_type'          => 'site_knowledge_review_plan',
		'version'                => 1,
		'batch_id'               => 'site_knowledge_review_fault_injection',
		'requires_approval'      => true,
		'dry_run'                => true,
		'commit_execution'       => false,
		'proposal_mode'          => 'single',
		'write_posture'          => 'core_proposal_handoff',
		'direct_wordpress_write' => false,
		'agent_id'               => 'site_knowledge_suggestion_agent',
		'agent_version'          => 'site_knowledge_agent.v1',
		'workflow'               => 'site_knowledge',
		'intent'                 => 'content_gap',
		'cloud_output'           => 'proposal_candidate',
		'local_next_action'      => 'operator_review',
		'evidence_gate_status'   => 'passed',
		'evidence_refs'          => array(
			array(
				'title'          => 'Existing site article',
				'url'            => 'https://example.test/existing-article',
				'post_id'        => 1493,
				'source_type'    => 'post',
				'suggested_use'  => 'supporting_evidence',
			),
		),
		'blocked_outputs'        => array( 'direct_wordpress_write' ),
		'preview'                => array(
			array(
				'action_id'      => 'review_site_knowledge_gap',
				'proposal_ready' => false,
			),
		),
		'write_actions'          => array(
			array(
				'action_id'         => 'review_site_knowledge_gap',
				'target_ability_id' => 'npcink-abilities-toolkit/create-draft',
				'input'             => array(
					'title'           => '',
					'content'         => '',
					'status'          => 'draft',
					'dry_run'         => true,
					'commit'          => false,
					'idempotency_key' => 'site-knowledge-review-fixture',
				),
				'risk'              => 'medium',
				'requires_approval' => true,
				'commit_execution'  => false,
				'proposal_ready'    => false,
				'requires_input'    => array( 'title', 'content' ),
			),
		),
	);
}

/**
 * Creates a representative Nightly Site Inspection Core review plan.
 *
 * @return array<string,mixed>
 */
function npcink_governance_core_fail_closed_nightly_inspection_review_plan(): array {
	return array(
		'artifact_type'          => 'nightly_site_inspection_review_plan',
		'contract_version'       => 'nightly_site_inspection_core_review_plan.v1',
		'version'                => 1,
		'batch_id'               => 'run_nightly_inspection_fault_injection',
		'cloud_run_id'           => 'run_nightly_inspection_fault_injection',
		'requires_approval'      => true,
		'dry_run'                => true,
		'commit_execution'       => false,
		'proposal_mode'          => 'single',
		'write_posture'          => 'core_proposal_handoff',
		'direct_wordpress_write' => false,
		'runtime_owner'          => 'npcink-local-automation-runtime',
		'agent_id'               => 'nightly_site_inspection_cloud_runtime',
		'agent_version'          => 'nightly_site_inspection_cloud_runtime.v1',
		'workflow'               => 'nightly_site_inspection',
		'intent'                 => 'morning_review_preparation',
		'cloud_output'           => 'proposal_candidate',
		'local_next_action'      => 'operator_review',
		'evidence_gate_status'   => 'passed',
		'evidence_refs'          => array(
			array(
				'action_id'     => 'action_001',
				'title'         => 'Short title',
				'post_id'       => 123,
				'source_type'   => 'post',
				'score'         => 55,
				'severity'      => 'critical',
				'reason_codes'  => array( 'missing_meta_description', 'missing_internal_links' ),
				'suggested_use' => 'morning_brief_review_evidence',
			),
		),
		'blocked_outputs'        => array(
			'direct_wordpress_write',
			'article_body',
			'article_write_plan',
			'final_seo_copy',
		),
		'issue_types'            => array( 'nightly_site_inspection' ),
		'risk'                   => array(
			'level'  => 'medium',
			'reason' => 'review_required',
		),
		'preview'                => array(
			array(
				'action_id'          => 'review_nightly_site_inspection',
				'proposal_ready'     => false,
				'evidence_ref_count' => 1,
			),
		),
		'write_actions'          => array(
			array(
				'action_id'         => 'review_nightly_site_inspection',
				'target_ability_id' => 'npcink-abilities-toolkit/create-draft',
				'input'             => array(
					'title'           => '',
					'content'         => '',
					'status'          => 'draft',
					'dry_run'         => true,
					'commit'          => false,
					'idempotency_key' => 'nightly-inspection-review-fixture',
				),
				'risk'              => 'medium',
				'requires_approval' => true,
				'commit_execution'  => false,
				'proposal_ready'    => false,
				'requires_input'    => array( 'title', 'content' ),
				'reason'            => 'Morning Brief found reviewable content quality signals.',
			),
		),
	);
}

