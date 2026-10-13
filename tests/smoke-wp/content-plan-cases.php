<?php
/** Content plan cases test module. @package NpcinkGovernanceCore */

$plan_content_title   = 'Core Plan Bridge Content Candidate ' . $npcink_governance_core_smoke_run_id;
$plan_content_post_id = wp_insert_post(
	array(
		'post_title'   => $plan_content_title,
		'post_content' => 'Core plan bridge content candidate with enough words for deterministic SEO and excerpt planning smoke coverage.',
		'post_excerpt' => '',
		'post_status'  => 'draft',
		'post_type'    => 'post',
	),
	true
);
npcink_governance_core_smoke_assert( ! is_wp_error( $plan_content_post_id ) && (int) $plan_content_post_id > 0, 'plan bridge content fixture post is created' );
npcink_governance_core_smoke_register_post_fixture( (int) $plan_content_post_id );

$content_plan_input = array(
	'post_ids'    => array( (int) $plan_content_post_id ),
	'issue_types' => array( 'seo_title', 'seo_description' ),
	'max_actions' => 5,
);
$content_plan       = npcink_governance_core_smoke_run_plan_ability( 'npcink-abilities-toolkit/build-content-inventory-fix-plan', $content_plan_input );
$content_plan_result = npcink_governance_core_smoke_create_proposals_from_plan( 'npcink-abilities-toolkit/build-content-inventory-fix-plan', $content_plan, $content_plan_input );
npcink_governance_core_smoke_assert( (int) ( $content_plan_result['proposal_count'] ?? 0 ) >= 1, 'content fix plan generates Core proposals' );
$content_plan_proposal = is_array( $content_plan_result['proposals'][0] ?? null ) ? $content_plan_result['proposals'][0] : array();
npcink_governance_core_smoke_assert_plan_proposal_shape( $content_plan_proposal, 'npcink-abilities-toolkit/set-post-seo-meta', true );
npcink_governance_core_smoke_assert( isset( $content_plan_proposal['preview']['before'] ), 'content fix plan proposal preview includes before' );
npcink_governance_core_smoke_assert( isset( $content_plan_proposal['preview']['after_suggestion'] ), 'content fix plan proposal preview includes after_suggestion' );
npcink_governance_core_smoke_approve_and_preflight_plan_proposal( (string) ( $content_plan_proposal['proposal_id'] ?? '' ) );

$article_optimization_title = 'Core Article Optimization Candidate ' . $npcink_governance_core_smoke_run_id;
$article_optimization_post_id = wp_insert_post(
	array(
		'post_title'   => $article_optimization_title,
		'post_content' => 'This draft article has enough context for a governed excerpt optimization smoke test.',
		'post_excerpt' => 'Original smoke excerpt.',
		'post_status'  => 'draft',
		'post_type'    => 'post',
	),
	true
);
npcink_governance_core_smoke_assert( ! is_wp_error( $article_optimization_post_id ) && (int) $article_optimization_post_id > 0, 'article optimization fixture post is created' );
npcink_governance_core_smoke_register_post_fixture( (int) $article_optimization_post_id );
$article_optimization_excerpt = 'Reviewed smoke excerpt for Core proposal intake.';
$article_optimization_plan_input = array(
	'post'              => array(
		'post_id' => (int) $article_optimization_post_id,
		'title'   => $article_optimization_title,
		'status'  => 'draft',
		'excerpt' => 'Original smoke excerpt.',
	),
	'report'            => array(
		'summary' => array(
			'status'                => 'needs_attention',
			'high_priority_count'   => 1,
			'total_recommendations' => 2,
		),
		'geo'     => array(
			'summary' => array(
				'faq_candidate_count' => 1,
			),
		),
	),
	'optimization_plan' => array(
		'excerpt_mode' => 'apply',
		'seo_mode'     => 'suggest',
	),
	'generated_excerpt' => array(
		'proposal_text' => $article_optimization_excerpt,
	),
);
$article_optimization_plan = npcink_governance_core_smoke_run_plan_ability( 'npcink-abilities-toolkit/build-article-optimization-apply-plan', $article_optimization_plan_input );
$article_optimization_result = npcink_governance_core_smoke_create_proposals_from_plan( 'npcink-abilities-toolkit/build-article-optimization-apply-plan', $article_optimization_plan, $article_optimization_plan_input );
npcink_governance_core_smoke_assert( 1 === (int) ( $article_optimization_result['proposal_count'] ?? 0 ), 'article optimization apply plan generates one Core proposal' );
$article_optimization_proposal = is_array( $article_optimization_result['proposals'][0] ?? null ) ? $article_optimization_result['proposals'][0] : array();
npcink_governance_core_smoke_assert_plan_proposal_shape( $article_optimization_proposal, 'npcink-abilities-toolkit/update-post', true );
npcink_governance_core_smoke_assert( $article_optimization_excerpt === (string) ( $article_optimization_proposal['input']['excerpt'] ?? '' ), 'article optimization proposal preserves reviewed excerpt' );
npcink_governance_core_smoke_assert( is_array( $article_optimization_proposal['preview']['article_optimization'] ?? null ), 'article optimization proposal preserves optimization preview context' );
npcink_governance_core_smoke_assert( 'npcink-abilities-toolkit/recipes/article-optimization' === (string) ( $article_optimization_proposal['preview']['article_optimization']['source_recipe_ref'] ?? '' ), 'article optimization preview preserves recipe ref' );
npcink_governance_core_smoke_assert( in_array( 'update_excerpt', (array) ( $article_optimization_proposal['preview']['article_optimization']['safe_apply_supported'] ?? array() ), true ), 'article optimization preview records safe excerpt apply support' );
npcink_governance_core_smoke_assert( false === (bool) ( $article_optimization_proposal['preview']['article_optimization']['direct_wordpress_write'] ?? true ), 'article optimization preview keeps direct WordPress writes disabled' );
npcink_governance_core_smoke_assert( 'Original smoke excerpt.' === (string) get_post_field( 'post_excerpt', (int) $article_optimization_post_id ), 'article optimization from-plan intake does not mutate the post excerpt' );
npcink_governance_core_smoke_approve_and_preflight_plan_proposal( (string) ( $article_optimization_proposal['proposal_id'] ?? '' ) );
npcink_governance_core_smoke_assert( 'Original smoke excerpt.' === (string) get_post_field( 'post_excerpt', (int) $article_optimization_post_id ), 'article optimization preflight does not mutate the post excerpt' );

$pattern_page_title = 'Core Pattern Page Candidate ' . $npcink_governance_core_smoke_run_id;
$pattern_page_plan_input = array(
	'title'        => $pattern_page_title,
	'pattern_id'   => 'openai-style-landing',
	'style_preset' => 'minimal-dark-light',
	'variables'    => array(
		'eyebrow'          => 'WordPress AI Plugin',
		'hero_title'       => '把 AI 工作流带进 WordPress 内容现场',
		'hero_description' => '让内容生产、SEO 优化、媒体处理与发布协作在同一个可审计流程中完成。',
		'primary_cta'      => '查看工作流',
		'secondary_cta'    => '了解能力',
		'features'         => array(
			array(
				'title'       => 'AI 内容草稿',
				'description' => '从主题、上下文和站点知识出发，生成结构化草稿。',
			),
		),
	),
);
$pattern_page_plan = npcink_governance_core_smoke_run_plan_ability( 'npcink-abilities-toolkit/build-pattern-page-plan', $pattern_page_plan_input );
$pattern_page_result = npcink_governance_core_smoke_create_proposals_from_plan( 'npcink-abilities-toolkit/build-pattern-page-plan', $pattern_page_plan, $pattern_page_plan_input );
npcink_governance_core_smoke_assert( 1 === (int) ( $pattern_page_result['proposal_count'] ?? 0 ), 'pattern page plan generates one Core batch proposal' );
$pattern_page_proposal = is_array( $pattern_page_result['proposals'][0] ?? null ) ? $pattern_page_result['proposals'][0] : array();
npcink_governance_core_smoke_assert( 'plan_to_proposal_batch' === (string) ( $pattern_page_proposal['preview']['source']['type'] ?? '' ), 'pattern page plan records batch proposal source type' );
npcink_governance_core_smoke_assert( 'batch' === (string) ( $pattern_page_proposal['preview']['source']['proposal_mode'] ?? '' ), 'pattern page batch preserves proposal_mode' );
npcink_governance_core_smoke_assert( is_array( $pattern_page_proposal['preview']['pattern_page'] ?? null ), 'pattern page proposal preserves pattern preview context' );
npcink_governance_core_smoke_assert( 'openai-style-landing' === (string) ( $pattern_page_proposal['preview']['pattern_page']['pattern_id'] ?? '' ), 'pattern page preview preserves pattern id' );
npcink_governance_core_smoke_assert( is_array( $pattern_page_proposal['preview']['block_editor_quality_gate'] ?? null ), 'pattern page proposal preserves block editor quality gate' );
npcink_governance_core_smoke_assert( true === (bool) ( $pattern_page_proposal['preview']['block_editor_quality_gate']['ready_for_proposal'] ?? false ), 'pattern page block editor gate remains ready for proposal' );
npcink_governance_core_smoke_assert( 'landing_design' === (string) ( $pattern_page_proposal['preview']['block_editor_quality_gate']['profile'] ?? '' ), 'pattern page block editor gate preserves review profile' );
npcink_governance_core_smoke_assert( 'npcink-abilities-toolkit/review-block-editor-surface' === (string) ( $pattern_page_proposal['preview']['block_editor_quality_gate']['review_ability_id'] ?? '' ), 'pattern page block editor gate preserves review ability id' );
npcink_governance_core_smoke_assert( false === (bool) ( $pattern_page_proposal['preview']['block_editor_quality_gate']['commit_execution'] ?? true ), 'pattern page block editor gate keeps commit execution disabled' );
$pattern_page_actions = is_array( $pattern_page_proposal['input']['write_actions'] ?? null ) ? array_values( $pattern_page_proposal['input']['write_actions'] ) : array();
npcink_governance_core_smoke_assert( 2 === count( $pattern_page_actions ), 'pattern page batch stores create and block update actions' );
npcink_governance_core_smoke_assert( 'npcink-abilities-toolkit/create-draft' === (string) ( $pattern_page_actions[0]['target_ability_id'] ?? '' ), 'pattern page first action creates a draft page' );
npcink_governance_core_smoke_assert( 'npcink-abilities-toolkit/update-post-blocks' === (string) ( $pattern_page_actions[1]['target_ability_id'] ?? '' ), 'pattern page second action updates Gutenberg blocks' );
npcink_governance_core_smoke_assert( '$outputs.create-pattern-page.post_id' === (string) ( $pattern_page_actions[1]['input']['post_id'] ?? '' ), 'pattern page update action preserves output reference' );
$pattern_page_guardrails = (array) ( $pattern_page_proposal['caller']['core_guardrails']['batch_action_guardrails'] ?? array() );
npcink_governance_core_smoke_assert( 2 === count( $pattern_page_guardrails ), 'pattern page batch persists one contract guardrail per action' );
npcink_governance_core_smoke_assert( 'npcink-abilities-toolkit/update-post-blocks' === (string) ( $pattern_page_guardrails[1]['target_ability_id'] ?? '' ), 'pattern page batch persists the second action ability contract' );
npcink_governance_core_smoke_assert( 0 === npcink_governance_core_smoke_find_post_id_by_title( $pattern_page_title, 'page' ), 'pattern page from-plan intake does not create the page draft' );
npcink_governance_core_smoke_approve_and_preflight_plan_proposal( (string) ( $pattern_page_proposal['proposal_id'] ?? '' ) );
npcink_governance_core_smoke_assert( 0 === npcink_governance_core_smoke_find_post_id_by_title( $pattern_page_title, 'page' ), 'pattern page preflight does not create the page draft' );

$block_theme_site_plan_input = array(
	'intent'                 => 'customize_template_layout',
	'target_templates'       => array( 'single' ),
	'layout_profile'         => 'article_standard',
	'include_breadcrumbs'    => true,
	'show_author_date'       => true,
	'show_featured_image'    => true,
	'include_related_posts'  => true,
	'separator'              => '/',
	'show_current_item'      => true,
	'show_home_item'         => true,
	'show_on_home_page'      => false,
);
$block_theme_site_template_before = get_page_by_path( 'twentytwentyfive//single', OBJECT, 'wp_template' );
$block_theme_site_plan = npcink_governance_core_smoke_run_plan_ability( 'npcink-abilities-toolkit/build-block-theme-site-plan', $block_theme_site_plan_input );
$block_theme_site_result = npcink_governance_core_smoke_create_proposals_from_plan( 'npcink-abilities-toolkit/build-block-theme-site-plan', $block_theme_site_plan, $block_theme_site_plan_input );
npcink_governance_core_smoke_assert( 1 === (int) ( $block_theme_site_result['proposal_count'] ?? 0 ), 'block theme site plan generates one Core batch proposal' );
$block_theme_site_proposal = is_array( $block_theme_site_result['proposals'][0] ?? null ) ? $block_theme_site_result['proposals'][0] : array();
npcink_governance_core_smoke_assert( 'plan_to_proposal_batch' === (string) ( $block_theme_site_proposal['preview']['source']['type'] ?? '' ), 'block theme site plan records batch proposal source type' );
npcink_governance_core_smoke_assert( is_array( $block_theme_site_proposal['preview']['block_theme_site'] ?? null ), 'block theme site proposal preserves block theme preview context' );
npcink_governance_core_smoke_assert( 'customize_template_layout' === (string) ( $block_theme_site_proposal['preview']['block_theme_site']['intent'] ?? '' ), 'block theme site proposal preserves template layout intent' );
npcink_governance_core_smoke_assert( 'article_standard' === (string) ( $block_theme_site_proposal['preview']['block_theme_site']['layout_profile'] ?? '' ), 'block theme site proposal preserves bounded layout profile' );
npcink_governance_core_smoke_assert( 'pass' === (string) ( $block_theme_site_proposal['preview']['block_theme_site']['template_layout_contract']['contract_status'] ?? '' ), 'block theme site proposal preserves passing template layout contract' );
npcink_governance_core_smoke_assert( 'create_wp_template_override' === (string) ( $block_theme_site_proposal['preview']['block_theme_site']['file_template_write_mode'] ?? '' ), 'block theme site preview preserves file-backed template override mode' );
if ( is_array( $block_theme_site_plan['block_editor_reviews'] ?? null ) ) {
	npcink_governance_core_smoke_assert( is_array( $block_theme_site_proposal['preview']['block_editor_reviews'] ?? null ), 'block theme site proposal preserves block editor reviews' );
}
$block_theme_site_actions = is_array( $block_theme_site_proposal['input']['write_actions'] ?? null ) ? array_values( $block_theme_site_proposal['input']['write_actions'] ) : array();
npcink_governance_core_smoke_assert( 1 === count( $block_theme_site_actions ), 'block theme site batch stores one template write action' );
$block_theme_site_target_ability_id = (string) ( $block_theme_site_actions[0]['target_ability_id'] ?? '' );
npcink_governance_core_smoke_assert(
	in_array(
		$block_theme_site_target_ability_id,
		array(
			'npcink-abilities-toolkit/update-template-blocks',
			'npcink-abilities-toolkit/upsert-template-blocks',
		),
		true
	),
	'block theme site action uses a reviewed template write ability'
);
if ( 'npcink-abilities-toolkit/upsert-template-blocks' === $block_theme_site_target_ability_id ) {
	npcink_governance_core_smoke_assert( 'single' === (string) ( $block_theme_site_actions[0]['input']['slug'] ?? '' ), 'block theme site upsert action preserves template slug' );
} else {
	$block_theme_site_post_id = (int) ( $block_theme_site_actions[0]['input']['post_id'] ?? 0 );
	npcink_governance_core_smoke_assert( $block_theme_site_post_id > 0, 'block theme site update action preserves template post id' );
	if ( $block_theme_site_template_before instanceof WP_Post ) {
		npcink_governance_core_smoke_assert( $block_theme_site_post_id === (int) $block_theme_site_template_before->ID, 'block theme site update action targets the existing template override' );
	}
}
npcink_governance_core_smoke_assert( isset( $block_theme_site_actions[0]['input']['blocks'][0]['blockName'] ), 'block theme site action preserves Gutenberg block tree' );
$block_theme_site_template_after = get_page_by_path( 'twentytwentyfive//single', OBJECT, 'wp_template' );
if ( $block_theme_site_template_before instanceof WP_Post ) {
	npcink_governance_core_smoke_assert( $block_theme_site_template_after instanceof WP_Post && (int) $block_theme_site_template_after->ID === (int) $block_theme_site_template_before->ID, 'block theme site from-plan intake does not replace the existing template override' );
} else {
	npcink_governance_core_smoke_assert( null === $block_theme_site_template_after, 'block theme site from-plan intake does not create a template override' );
}

$block_theme_homepage_plan_input = array(
	'intent'                 => 'customize_template_layout',
	'target_templates'       => array( 'front-page' ),
	'layout_profile'         => 'homepage_landing',
	'include_latest_posts'   => true,
	'include_category_links' => true,
	'include_cta'            => true,
	'variables'              => array(
		'cta_url'   => 'https://example.test/get-started/',
		'cta_label' => 'Get started',
	),
);
$block_theme_homepage_plan = npcink_governance_core_smoke_run_plan_ability( 'npcink-abilities-toolkit/build-block-theme-site-plan', $block_theme_homepage_plan_input );
$block_theme_homepage_result = npcink_governance_core_smoke_create_proposals_from_plan( 'npcink-abilities-toolkit/build-block-theme-site-plan', $block_theme_homepage_plan, $block_theme_homepage_plan_input );
npcink_governance_core_smoke_assert( 1 === (int) ( $block_theme_homepage_result['proposal_count'] ?? 0 ), 'block theme homepage layout plan generates one Core batch proposal' );
$block_theme_homepage_proposal = is_array( $block_theme_homepage_result['proposals'][0] ?? null ) ? $block_theme_homepage_result['proposals'][0] : array();
npcink_governance_core_smoke_assert( 'homepage_landing' === (string) ( $block_theme_homepage_proposal['preview']['block_theme_site']['layout_profile'] ?? '' ), 'block theme homepage proposal preserves homepage landing profile' );
$block_theme_homepage_actions = is_array( $block_theme_homepage_proposal['input']['write_actions'] ?? null ) ? array_values( $block_theme_homepage_proposal['input']['write_actions'] ) : array();
npcink_governance_core_smoke_assert( 1 === count( $block_theme_homepage_actions ), 'block theme homepage batch stores one template write action' );
$block_theme_homepage_blocks_json = wp_json_encode( $block_theme_homepage_actions[0]['input']['blocks'] ?? array() );
$block_theme_homepage_blocks_json = is_string( $block_theme_homepage_blocks_json ) ? $block_theme_homepage_blocks_json : '';
npcink_governance_core_smoke_assert( false !== strpos( $block_theme_homepage_blocks_json, 'core\/button' ), 'block theme homepage proposal stores a CTA button block' );
npcink_governance_core_smoke_assert( false !== strpos( $block_theme_homepage_blocks_json, 'core\/latest-posts' ), 'block theme homepage proposal stores latest posts block' );
npcink_governance_core_smoke_assert( false !== strpos( $block_theme_homepage_blocks_json, 'core\/categories' ), 'block theme homepage proposal stores categories block' );

$article_block_title = 'Core Article Block Plan Smoke ' . $npcink_governance_core_smoke_run_id;
$article_block_plan_input = array(
	'title'              => $article_block_title,
	'article_template'   => 'comparison-review',
	'responsive_profile' => 'article_standard',
	'media_strategy'     => 'none',
	'variables'          => array(
		'dek'         => 'Gutenberg article blocks can carry editorial structure through governance.',
		'intro'       => 'This smoke verifies that article block plans become Core proposals without creating drafts during intake.',
		'takeaways'   => array(
			'Core accepts the plan only as a proposal batch.',
			'Adapter remains the post-Core executor.',
			'WordPress content is not mutated during from-plan intake.',
		),
		'sections'    => array(
			array(
				'title'      => 'Why article blocks',
				'paragraphs' => array( 'Core should govern the reviewed block write without owning article generation.' ),
			),
			array(
				'title'      => 'How it stays bounded',
				'paragraphs' => array( 'The plan creates a draft post and then replaces blocks through approved execution only.' ),
			),
			array(
				'title'      => 'Responsive check',
				'paragraphs' => array( 'Comparison columns must stack on mobile.' ),
			),
		),
		'comparisons' => array(
			array(
				'title'       => 'HTML',
				'description' => 'Flexible but harder to keep editable.',
			),
			array(
				'title'       => 'Blocks',
				'description' => 'Structured and easier to review.',
			),
		),
	),
);
$article_block_plan = npcink_governance_core_smoke_run_plan_ability( 'npcink-abilities-toolkit/build-article-block-plan', $article_block_plan_input );
$article_block_result = npcink_governance_core_smoke_create_proposals_from_plan( 'npcink-abilities-toolkit/build-article-block-plan', $article_block_plan, $article_block_plan_input );
npcink_governance_core_smoke_assert( 1 === (int) ( $article_block_result['proposal_count'] ?? 0 ), 'article block plan generates one Core batch proposal' );
$article_block_proposal = is_array( $article_block_result['proposals'][0] ?? null ) ? $article_block_result['proposals'][0] : array();
npcink_governance_core_smoke_assert( 'plan_to_proposal_batch' === (string) ( $article_block_proposal['preview']['source']['type'] ?? '' ), 'article block plan records batch proposal source type' );
npcink_governance_core_smoke_assert( 'batch' === (string) ( $article_block_proposal['preview']['source']['proposal_mode'] ?? '' ), 'article block batch preserves proposal_mode' );
npcink_governance_core_smoke_assert( is_array( $article_block_proposal['preview']['article_block'] ?? null ), 'article block proposal preserves article preview context' );
npcink_governance_core_smoke_assert( 'comparison-review' === (string) ( $article_block_proposal['preview']['article_block']['article_template'] ?? '' ), 'article block preview preserves article template' );
if ( is_array( $article_block_plan['block_editor_review'] ?? null ) ) {
	npcink_governance_core_smoke_assert( is_array( $article_block_proposal['preview']['block_editor_review'] ?? null ), 'article block proposal preserves block editor review' );
}
$article_block_actions = is_array( $article_block_proposal['input']['write_actions'] ?? null ) ? array_values( $article_block_proposal['input']['write_actions'] ) : array();
npcink_governance_core_smoke_assert( 2 === count( $article_block_actions ), 'article block batch stores create and block update actions' );
npcink_governance_core_smoke_assert( 'npcink-abilities-toolkit/create-draft' === (string) ( $article_block_actions[0]['target_ability_id'] ?? '' ), 'article block first action creates a draft post' );
npcink_governance_core_smoke_assert( 'npcink-abilities-toolkit/update-post-blocks' === (string) ( $article_block_actions[1]['target_ability_id'] ?? '' ), 'article block second action updates Gutenberg blocks' );
npcink_governance_core_smoke_assert( '$outputs.create-article-draft.post_id' === (string) ( $article_block_actions[1]['input']['post_id'] ?? '' ), 'article block update action preserves output reference' );
npcink_governance_core_smoke_assert( 0 === npcink_governance_core_smoke_find_post_id_by_title( $article_block_title, 'post' ), 'article block from-plan intake does not create the post draft' );
npcink_governance_core_smoke_approve_and_preflight_plan_proposal( (string) ( $article_block_proposal['proposal_id'] ?? '' ) );
npcink_governance_core_smoke_assert( 0 === npcink_governance_core_smoke_find_post_id_by_title( $article_block_title, 'post' ), 'article block preflight does not create the post draft' );

