<?php
/**
 * Plan-to-proposal inbound contract validator.
 *
 * @package NpcinkGovernanceCore
 */

namespace Npcink\GovernanceCore\Governance;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Validates allowlisted planning artifacts before Core creates proposals.
 *
 * This class owns inbound governance contracts only. It is not a workflow
 * definition registry and does not execute, schedule, or persist plans.
 */
final class Plan_Contract_Validator {
	/**
	 * Supported read-only planning abilities.
	 *
	 * @var array<string,bool>
	 */
	private $allowed_plan_abilities = array(
		'npcink-abilities-toolkit/build-content-inventory-fix-plan'           => true,
		'npcink-abilities-toolkit/build-nonproduction-content-cleanup-plan'  => true,
		'npcink-abilities-toolkit/build-media-inventory-fix-plan'             => true,
		'npcink-abilities-toolkit/build-media-reference-repair-plan'          => true,
		'npcink-abilities-toolkit/build-media-settings-reference-repair-plan' => true,
		'npcink-abilities-toolkit/build-media-optimization-plan'              => true,
		'npcink-abilities-toolkit/build-media-adoption-enhancement-plan'      => true,
		'npcink-abilities-toolkit/build-media-rename-plan'                    => true,
		'npcink-abilities-toolkit/build-article-optimization-apply-plan'      => true,
		'npcink-abilities-toolkit/build-article-block-plan'                   => true,
		'npcink-abilities-toolkit/build-pattern-page-plan'                    => true,
		'npcink-abilities-toolkit/build-block-theme-site-plan'                => true,
		'npcink-toolbox/build-article-write-plan'                            => true,
		'npcink-toolbox/build-article-batch-write-plan'                      => true,
		'npcink-toolbox/build-article-media-batch-write-plan'                => true,
		'npcink-abilities-toolkit/build-image-candidate-adoption-plan'        => true,
		'npcink-abilities-toolkit/build-article-audio-adoption-plan'         => true,
		'npcink-toolbox/build-site-knowledge-review-plan'                    => true,
		'npcink-toolbox/build-nightly-inspection-review-plan'                => true,
		'npcink-abilities-toolkit/build-content-metadata-apply-plan'         => true,
		'npcink-abilities-toolkit/build-media-alt-apply-plan'               => true,
	);

	private const ARTICLE_BATCH_MAX_ACTIONS = 5;
	private const ARTICLE_MEDIA_BATCH_MAX_ARTICLES = 5;
	private const ARTICLE_MEDIA_BATCH_MAX_ACTIONS = 25;
	private const PLAN_MAX_WRITE_ACTIONS = 25;
	private const PLAN_MAX_PAYLOAD_BYTES = 262144;
	private const MEDIA_OPTIMIZATION_MAX_ACTIONS = 10;
	private const BLOCK_THEME_SITE_MAX_ACTIONS = 10;
	private const BLOCK_THEME_SITE_MAX_BLOCKS = 80;
	private const BLOCK_THEME_SITE_MAX_BLOCK_DEPTH = 8;
	private const BLOCK_THEME_SITE_MAX_ATTR_BYTES = 16384;

	/**
	 * Reports whether Core accepts a planning ability for proposal intake.
	 *
	 * @param string $plan_ability_id Planning ability id.
	 * @return bool
	 */
	public function supports( string $plan_ability_id ): bool {
		return isset( $this->allowed_plan_abilities[ $plan_ability_id ] );
	}

	/**
	 * Returns the article batch action cap used by proposal preview assembly.
	 *
	 * @return int
	 */
	public function article_batch_max_actions(): int {
		return self::ARTICLE_BATCH_MAX_ACTIONS;
	}

	/**
	 * Returns the article media batch article cap used by preview assembly.
	 *
	 * @return int
	 */
	public function article_media_batch_max_articles(): int {
		return self::ARTICLE_MEDIA_BATCH_MAX_ARTICLES;
	}

	/**
	 * Returns the article media batch action cap used by preview assembly.
	 *
	 * @return int
	 */
	public function article_media_batch_max_actions(): int {
		return self::ARTICLE_MEDIA_BATCH_MAX_ACTIONS;
	}

	/**
	 * Validates the common safety contract and the ability-specific plan contract.
	 *
	 * @param string              $plan_ability_id Planning ability id.
	 * @param array<string,mixed> $plan Plan data.
	 * @return true|WP_Error
	 */
	public function validate( string $plan_ability_id, array $plan ) {
		$contract_error = $this->validate_plan_contract( $plan );
		if ( is_wp_error( $contract_error ) ) {
			return $contract_error;
		}

		switch ( $plan_ability_id ) {
			case 'npcink-toolbox/build-article-write-plan':
				return $this->validate_article_write_plan_contract( $plan );
			case 'npcink-toolbox/build-article-batch-write-plan':
				return $this->validate_article_batch_write_plan_contract( $plan );
			case 'npcink-toolbox/build-article-media-batch-write-plan':
				return $this->validate_article_media_batch_write_plan_contract( $plan );
			case 'npcink-abilities-toolkit/build-image-candidate-adoption-plan':
				return $this->validate_image_candidate_adoption_plan_contract( $plan );
			case 'npcink-abilities-toolkit/build-article-audio-adoption-plan':
				return $this->validate_article_audio_adoption_plan_contract( $plan );
			case 'npcink-toolbox/build-site-knowledge-review-plan':
				return $this->validate_site_knowledge_review_plan_contract( $plan );
			case 'npcink-toolbox/build-nightly-inspection-review-plan':
				return $this->validate_nightly_inspection_review_plan_contract( $plan );
			case 'npcink-abilities-toolkit/build-content-metadata-apply-plan':
				return $this->validate_content_metadata_apply_plan_contract( $plan );
			case 'npcink-abilities-toolkit/build-media-alt-apply-plan':
				return $this->validate_media_alt_apply_plan_contract( $plan );
			case 'npcink-abilities-toolkit/build-media-optimization-plan':
				return $this->validate_media_optimization_plan_contract( $plan );
			case 'npcink-abilities-toolkit/build-media-adoption-enhancement-plan':
				return $this->validate_media_adoption_enhancement_plan_contract( $plan );
			case 'npcink-abilities-toolkit/build-media-rename-plan':
				return $this->validate_media_rename_plan_contract( $plan );
			case 'npcink-abilities-toolkit/build-article-optimization-apply-plan':
				return $this->validate_article_optimization_apply_plan_contract( $plan );
			case 'npcink-abilities-toolkit/build-article-block-plan':
				return $this->validate_article_block_plan_contract( $plan );
			case 'npcink-abilities-toolkit/build-pattern-page-plan':
				return $this->validate_pattern_page_plan_contract( $plan );
			case 'npcink-abilities-toolkit/build-block-theme-site-plan':
				return $this->validate_block_theme_site_plan_contract( $plan );
			default:
				return true;
		}
	}

	/**
	 * Validates the plan-level safety contract.
	 *
	 * @param array<string,mixed> $plan Plan data.
	 * @return true|WP_Error
	 */
	private function validate_plan_contract( array $plan ) {
		if ( true !== (bool) ( $plan['requires_approval'] ?? false ) ) {
			return new WP_Error(
				'npcink_governance_core_plan_requires_approval_missing',
				__( 'Plan output must require approval.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		if ( false !== (bool) ( $plan['commit_execution'] ?? true ) ) {
			return new WP_Error(
				'npcink_governance_core_plan_commit_execution_rejected',
				__( 'Plan output must not execute commits.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		if ( true !== (bool) ( $plan['dry_run'] ?? false ) ) {
			return new WP_Error(
				'npcink_governance_core_plan_dry_run_required',
				__( 'Plan output must be dry-run only.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		if ( ! is_array( $plan['write_actions'] ?? null ) ) {
			return new WP_Error(
				'npcink_governance_core_plan_write_actions_missing',
				__( 'Plan output must include write_actions.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		$encoded = wp_json_encode( $plan );
		if ( is_string( $encoded ) && strlen( $encoded ) > self::PLAN_MAX_PAYLOAD_BYTES ) {
			return new WP_Error(
				'npcink_governance_core_plan_payload_too_large',
				__( 'Plan output is too large for Core proposal intake.', 'npcink-governance-core' ),
				array(
					'status'    => 413,
					'max_bytes' => self::PLAN_MAX_PAYLOAD_BYTES,
				)
			);
		}

		$write_actions = array_values( (array) $plan['write_actions'] );
		if ( count( $write_actions ) > self::PLAN_MAX_WRITE_ACTIONS ) {
			return new WP_Error(
				'npcink_governance_core_plan_too_many_actions',
				__( 'Plan output includes too many write actions for one Core intake request.', 'npcink-governance-core' ),
				array(
					'status'      => 422,
					'max_actions' => self::PLAN_MAX_WRITE_ACTIONS,
				)
			);
		}

		return true;
	}

	/**
	 * Validates a bounded batch article draft plan.
	 *
	 * @param array<string,mixed> $plan Plan data.
	 * @return true|WP_Error
	 */
	private function validate_article_batch_write_plan_contract( array $plan ) {
		$artifact_type = sanitize_key( (string) ( $plan['artifact_type'] ?? ( $plan['plan_type'] ?? '' ) ) );
		if ( 'article_batch_write_plan' !== $artifact_type ) {
			return new WP_Error(
				'npcink_governance_core_article_batch_plan_invalid',
				__( 'Article batch write plans must declare artifact_type=article_batch_write_plan.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		if ( true !== (bool) ( $plan['batch_approval'] ?? false ) || 'batch' !== sanitize_key( (string) ( $plan['proposal_mode'] ?? '' ) ) ) {
			return new WP_Error(
				'npcink_governance_core_article_batch_mode_required',
				__( 'Article batch write plans must explicitly request batch proposal approval.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		$write_actions = is_array( $plan['write_actions'] ?? null ) ? array_values( $plan['write_actions'] ) : array();
		if ( count( $write_actions ) < 2 || count( $write_actions ) > self::ARTICLE_BATCH_MAX_ACTIONS ) {
			return new WP_Error(
				'npcink_governance_core_article_batch_size_rejected',
				__( 'Article batch write plans must contain a bounded group of draft actions.', 'npcink-governance-core' ),
				array(
					'status'      => 422,
					'max_actions' => self::ARTICLE_BATCH_MAX_ACTIONS,
				)
			);
		}

		$articles = is_array( $plan['articles'] ?? null ) ? array_values( $plan['articles'] ) : array();
		if ( count( $articles ) !== count( $write_actions ) ) {
			return new WP_Error(
				'npcink_governance_core_article_batch_artifacts_missing',
				__( 'Article batch write plans must include one reviewed article artifact set per draft action.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		foreach ( $articles as $article_index => $article ) {
			if ( ! is_array( $article ) ) {
				return new WP_Error(
					'npcink_governance_core_article_batch_artifacts_missing',
					__( 'Article batch entries must be objects.', 'npcink-governance-core' ),
					array(
						'status'        => 422,
						'article_index' => $article_index,
					)
				);
			}
			$artifact_error = $this->validate_article_artifacts( $article, 'npcink_governance_core_article_batch_' );
			if ( is_wp_error( $artifact_error ) ) {
				return $artifact_error;
			}
		}

		foreach ( $write_actions as $action_index => $action ) {
			if ( ! is_array( $action ) ) {
				return new WP_Error(
					'npcink_governance_core_article_batch_action_invalid',
					__( 'Article batch write actions must be objects.', 'npcink-governance-core' ),
					array(
						'status'       => 422,
						'action_index' => $action_index,
					)
				);
			}
			$draft_error = $this->validate_article_draft_action( $action, 'npcink_governance_core_article_batch_' );
			if ( is_wp_error( $draft_error ) ) {
				return $draft_error;
			}
		}

		return true;
	}

	/**
	 * Validates a bounded article media batch plan.
	 *
	 * @param array<string,mixed> $plan Plan data.
	 * @return true|WP_Error
	 */
	private function validate_article_media_batch_write_plan_contract( array $plan ) {
		$artifact_type = sanitize_key( (string) ( $plan['artifact_type'] ?? ( $plan['plan_type'] ?? '' ) ) );
		if ( 'article_media_batch_write_plan' !== $artifact_type ) {
			return new WP_Error(
				'npcink_governance_core_article_media_batch_plan_invalid',
				__( 'Article media batch write plans must declare artifact_type=article_media_batch_write_plan.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		if ( true !== (bool) ( $plan['batch_approval'] ?? false ) || 'batch' !== sanitize_key( (string) ( $plan['proposal_mode'] ?? '' ) ) ) {
			return new WP_Error(
				'npcink_governance_core_article_media_batch_mode_required',
				__( 'Article media batch write plans must explicitly request batch proposal approval.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		$articles = is_array( $plan['articles'] ?? null ) ? array_values( $plan['articles'] ) : array();
		if ( count( $articles ) < 1 || count( $articles ) > self::ARTICLE_MEDIA_BATCH_MAX_ARTICLES ) {
			return new WP_Error(
				'npcink_governance_core_article_media_batch_size_rejected',
				__( 'Article media batch write plans must include 1 to 5 reviewed articles.', 'npcink-governance-core' ),
				array(
					'status'       => 422,
					'max_articles' => self::ARTICLE_MEDIA_BATCH_MAX_ARTICLES,
				)
			);
		}

		foreach ( $articles as $article_index => $article ) {
			if ( ! is_array( $article ) ) {
				return new WP_Error(
					'npcink_governance_core_article_media_batch_artifacts_missing',
					__( 'Article media batch entries must be objects.', 'npcink-governance-core' ),
					array(
						'status'        => 422,
						'article_index' => $article_index,
					)
				);
			}
			$artifact_error = $this->validate_article_artifacts( $article, 'npcink_governance_core_article_media_batch_' );
			if ( is_wp_error( $artifact_error ) ) {
				return $artifact_error;
			}
			if ( ! is_array( $article['featured_image_candidate'] ?? null ) ) {
				return new WP_Error(
					'npcink_governance_core_article_media_batch_candidate_missing',
					__( 'Article media batch entries must preserve the selected image-source candidate.', 'npcink-governance-core' ),
					array(
						'status'        => 422,
						'article_index' => $article_index,
					)
				);
			}
		}

		$write_actions = is_array( $plan['write_actions'] ?? null ) ? array_values( $plan['write_actions'] ) : array();
		if ( count( $write_actions ) < count( $articles ) * 3 || count( $write_actions ) > self::ARTICLE_MEDIA_BATCH_MAX_ACTIONS ) {
			return new WP_Error(
				'npcink_governance_core_article_media_batch_actions_rejected',
				__( 'Article media batch write plans must contain a bounded group of draft, media upload, and featured-image actions.', 'npcink-governance-core' ),
				array(
					'status'      => 422,
					'max_actions' => self::ARTICLE_MEDIA_BATCH_MAX_ACTIONS,
				)
			);
		}

		$target_counts = array(
			'npcink-abilities-toolkit/create-draft'             => 0,
			'npcink-abilities-toolkit/upload-media-from-url'    => 0,
			'npcink-abilities-toolkit/set-post-featured-image'  => 0,
		);
		$allowed_targets = array(
			'npcink-abilities-toolkit/create-draft'             => true,
			'npcink-abilities-toolkit/upload-media-from-url'    => true,
			'npcink-abilities-toolkit/update-media-details'     => true,
			'npcink-abilities-toolkit/set-post-featured-image'  => true,
			'npcink-abilities-toolkit/patch-post-content'       => true,
		);

		foreach ( $write_actions as $action_index => $action ) {
			if ( ! is_array( $action ) ) {
				return new WP_Error(
					'npcink_governance_core_article_media_batch_action_invalid',
					__( 'Article media batch write actions must be objects.', 'npcink-governance-core' ),
					array(
						'status'       => 422,
						'action_index' => $action_index,
					)
				);
			}

			$target_ability_id = sanitize_text_field( (string) ( $action['target_ability_id'] ?? '' ) );
			if ( ! isset( $allowed_targets[ $target_ability_id ] ) ) {
				return new WP_Error(
					'npcink_governance_core_article_media_batch_target_rejected',
					__( 'Article media batch plans may target only draft and allowlisted media actions.', 'npcink-governance-core' ),
					array(
						'status'            => 422,
						'action_index'      => $action_index,
						'target_ability_id' => $target_ability_id,
					)
				);
			}
			if ( isset( $target_counts[ $target_ability_id ] ) ) {
				++$target_counts[ $target_ability_id ];
			}

			$action_error = $this->validate_article_media_batch_action( $action, $action_index );
			if ( is_wp_error( $action_error ) ) {
				return $action_error;
			}
		}

		foreach ( $target_counts as $target => $count ) {
			if ( $count < count( $articles ) ) {
				return new WP_Error(
					'npcink_governance_core_article_media_batch_actions_missing',
					__( 'Article media batch plans must include create, upload, and featured-image actions for every article.', 'npcink-governance-core' ),
					array(
						'status'            => 422,
						'target_ability_id' => $target,
					)
				);
			}
		}

		return true;
	}

	/**
	 * Validates a bounded image candidate adoption plan.
	 *
	 * @param array<string,mixed> $plan Plan data.
	 * @return true|WP_Error
	 */
	private function validate_image_candidate_adoption_plan_contract( array $plan ) {
		$artifact_type = sanitize_key( (string) ( $plan['artifact_type'] ?? ( $plan['plan_type'] ?? '' ) ) );
		if ( 'image_candidate_adoption_plan' !== $artifact_type ) {
			return new WP_Error(
				'npcink_governance_core_image_candidate_adoption_plan_invalid',
				__( 'Image candidate adoption plans must declare artifact_type=image_candidate_adoption_plan.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		$candidate = is_array( $plan['selected_image_candidate'] ?? null ) ? $plan['selected_image_candidate'] : array();
		if ( empty( $candidate ) || 'image_candidate.v1' !== (string) ( $candidate['contract_version'] ?? $plan['candidate_contract_version'] ?? '' ) ) {
			return new WP_Error(
				'npcink_governance_core_image_candidate_contract_missing',
				__( 'Image candidate adoption plans must preserve a selected image_candidate.v1 candidate.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		$write_actions = is_array( $plan['write_actions'] ?? null ) ? array_values( $plan['write_actions'] ) : array();
		if ( count( $write_actions ) < 2 || count( $write_actions ) > 3 ) {
			return new WP_Error(
				'npcink_governance_core_image_candidate_actions_rejected',
				__( 'Image candidate adoption plans must contain upload, metadata, and optional featured-image actions only.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		$target_counts = array(
			'npcink-abilities-toolkit/upload-media-from-url'   => 0,
			'npcink-abilities-toolkit/update-media-details'    => 0,
			'npcink-abilities-toolkit/set-post-featured-image' => 0,
		);

		foreach ( $write_actions as $action_index => $action ) {
			if ( ! is_array( $action ) ) {
				return new WP_Error(
					'npcink_governance_core_image_candidate_action_invalid',
					__( 'Image candidate adoption actions must be objects.', 'npcink-governance-core' ),
					array(
						'status'       => 422,
						'action_index' => $action_index,
					)
				);
			}
			$target_ability_id = sanitize_text_field( (string) ( $action['target_ability_id'] ?? '' ) );
			if ( ! isset( $target_counts[ $target_ability_id ] ) ) {
				return new WP_Error(
					'npcink_governance_core_image_candidate_target_rejected',
					__( 'Image candidate adoption plans may target only media import, metadata, and featured-image abilities.', 'npcink-governance-core' ),
					array(
						'status'            => 422,
						'action_index'      => $action_index,
						'target_ability_id' => $target_ability_id,
					)
				);
			}
			++$target_counts[ $target_ability_id ];

			$action_error = $this->validate_image_candidate_adoption_action( $action, $action_index );
			if ( is_wp_error( $action_error ) ) {
				return $action_error;
			}
		}

		if ( 1 !== $target_counts['npcink-abilities-toolkit/upload-media-from-url'] || 1 !== $target_counts['npcink-abilities-toolkit/update-media-details'] || $target_counts['npcink-abilities-toolkit/set-post-featured-image'] > 1 ) {
			return new WP_Error(
				'npcink_governance_core_image_candidate_actions_missing',
				__( 'Image candidate adoption plans must include exactly one media upload and one metadata action, plus at most one featured-image action.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		return true;
	}

	/**
	 * Validates one image candidate adoption write action.
	 *
	 * @param array<string,mixed> $action Write action.
	 * @param int                 $action_index Action index.
	 * @return true|WP_Error
	 */
	private function validate_image_candidate_adoption_action( array $action, int $action_index ) {
		$input = is_array( $action['input'] ?? null ) ? $action['input'] : array();
		if ( true === (bool) ( $input['commit'] ?? false ) || false === (bool) ( $input['dry_run'] ?? true ) ) {
			return new WP_Error(
				'npcink_governance_core_image_candidate_commit_rejected',
				__( 'Image candidate adoption action input must remain dry-run and must not request commit.', 'npcink-governance-core' ),
				array(
					'status'       => 422,
					'action_index' => $action_index,
				)
			);
		}

		$target_ability_id = sanitize_text_field( (string) ( $action['target_ability_id'] ?? '' ) );
		if ( 'npcink-abilities-toolkit/upload-media-from-url' === $target_ability_id ) {
			if ( ! $this->is_valid_absolute_url( $input['url'] ?? null ) ) {
				return new WP_Error(
					'npcink_governance_core_image_candidate_url_missing',
					__( 'Image candidate upload actions must include a reviewed candidate URL.', 'npcink-governance-core' ),
					array(
						'status'       => 422,
						'action_index' => $action_index,
					)
				);
			}
			$source_type = sanitize_key( (string) ( $input['source_type'] ?? '' ) );
			if ( '' !== $source_type && ! in_array( $source_type, array( 'owned', 'ai_generated', 'stock', 'external', 'test' ), true ) ) {
				return new WP_Error(
					'npcink_governance_core_image_candidate_source_type_invalid',
					__( 'Image candidate source_type must be owned, ai_generated, stock, external, or test.', 'npcink-governance-core' ),
					array(
						'status'       => 422,
						'action_index' => $action_index,
					)
				);
			}
			return true;
		}

		if ( 'npcink-abilities-toolkit/update-media-details' === $target_ability_id ) {
			if ( ! $this->is_exact_output_reference( $input['attachment_id'] ?? null ) && absint( $input['attachment_id'] ?? 0 ) <= 0 ) {
				return new WP_Error(
					'npcink_governance_core_image_candidate_attachment_missing',
					__( 'Image candidate metadata actions must include attachment_id or an approved output reference.', 'npcink-governance-core' ),
					array(
						'status'       => 422,
						'action_index' => $action_index,
					)
				);
			}
			return true;
		}

		if ( 'npcink-abilities-toolkit/set-post-featured-image' === $target_ability_id ) {
			if ( ! $this->is_exact_output_reference( $input['attachment_id'] ?? null ) && absint( $input['attachment_id'] ?? 0 ) <= 0 ) {
				return new WP_Error(
					'npcink_governance_core_image_candidate_featured_attachment_missing',
					__( 'Image candidate featured-image actions must include attachment_id or an approved output reference.', 'npcink-governance-core' ),
					array(
						'status'       => 422,
						'action_index' => $action_index,
					)
				);
			}
			if ( absint( $input['post_id'] ?? 0 ) <= 0 && ! $this->is_exact_output_reference( $input['post_id'] ?? null ) ) {
				return new WP_Error(
					'npcink_governance_core_image_candidate_featured_post_missing',
					__( 'Image candidate featured-image actions must include post_id or an approved output reference.', 'npcink-governance-core' ),
					array(
						'status'       => 422,
						'action_index' => $action_index,
					)
				);
			}
		}

		return true;
	}

	/**
	 * Validates a Site Knowledge review plan from the Toolbox agent handoff.
	 *
	 * @param array<string,mixed> $plan Plan data.
	 * @return true|WP_Error
	 */
	private function validate_site_knowledge_review_plan_contract( array $plan ) {
		$artifact_type = sanitize_key( (string) ( $plan['artifact_type'] ?? ( $plan['plan_type'] ?? '' ) ) );
		if ( 'site_knowledge_review_plan' !== $artifact_type ) {
			return new WP_Error(
				'npcink_governance_core_site_knowledge_review_plan_invalid',
				__( 'Site Knowledge review plans must declare artifact_type=site_knowledge_review_plan.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		if ( true === (bool) ( $plan['direct_wordpress_write'] ?? false ) ) {
			return new WP_Error(
				'npcink_governance_core_site_knowledge_direct_write_rejected',
				__( 'Site Knowledge review plans must not claim direct WordPress write authority.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		$evidence_refs = is_array( $plan['evidence_refs'] ?? null ) ? array_values( $plan['evidence_refs'] ) : array();
		if ( empty( $evidence_refs ) ) {
			return new WP_Error(
				'npcink_governance_core_site_knowledge_evidence_missing',
				__( 'Site Knowledge review plans must preserve evidence_refs from the Cloud handoff.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		$write_actions = is_array( $plan['write_actions'] ?? null ) ? array_values( $plan['write_actions'] ) : array();
		if ( 1 !== count( $write_actions ) || ! is_array( $write_actions[0] ?? null ) ) {
			return new WP_Error(
				'npcink_governance_core_site_knowledge_action_count_rejected',
				__( 'Site Knowledge review plans must contain exactly one blocked create-draft review action.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		$action = $write_actions[0];
		if ( 'npcink-abilities-toolkit/create-draft' !== sanitize_text_field( (string) ( $action['target_ability_id'] ?? '' ) ) ) {
			return new WP_Error(
				'npcink_governance_core_site_knowledge_target_rejected',
				__( 'Site Knowledge review plans may target only create-draft as a non-ready review proposal.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		if ( true === (bool) ( $action['proposal_ready'] ?? true ) ) {
			return new WP_Error(
				'npcink_governance_core_site_knowledge_ready_rejected',
				__( 'Site Knowledge review plans must remain not ready until a human supplies the draft fields.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		$requires_input = array_values( array_map( 'sanitize_key', (array) ( $action['requires_input'] ?? array() ) ) );
		foreach ( array( 'title', 'content' ) as $field ) {
			if ( ! in_array( $field, $requires_input, true ) ) {
				return new WP_Error(
					'npcink_governance_core_site_knowledge_required_input_missing',
					__( 'Site Knowledge review plans must require human title and content input.', 'npcink-governance-core' ),
					array(
						'status' => 422,
						'field'  => $field,
					)
				);
			}
		}

		$input  = is_array( $action['input'] ?? null ) ? $action['input'] : array();
		$status = sanitize_key( (string) ( $input['status'] ?? ( $input['post_status'] ?? 'draft' ) ) );
		if ( '' !== $status && 'draft' !== $status ) {
			return new WP_Error(
				'npcink_governance_core_site_knowledge_publish_rejected',
				__( 'Site Knowledge review plans may prepare draft proposals only.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		if ( true === (bool) ( $input['commit'] ?? false ) || false === (bool) ( $input['dry_run'] ?? true ) ) {
			return new WP_Error(
				'npcink_governance_core_site_knowledge_commit_rejected',
				__( 'Site Knowledge review action input must remain dry-run and must not request commit.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		return true;
	}

	/**
	 * Validates a Nightly Site Inspection review plan from Cloud runtime handoff.
	 *
	 * @param array<string,mixed> $plan Plan data.
	 * @return true|WP_Error
	 */
	private function validate_nightly_inspection_review_plan_contract( array $plan ) {
		$artifact_type = sanitize_key( (string) ( $plan['artifact_type'] ?? ( $plan['plan_type'] ?? '' ) ) );
		if ( 'nightly_site_inspection_review_plan' !== $artifact_type ) {
			return new WP_Error(
				'npcink_governance_core_nightly_inspection_plan_invalid',
				__( 'Nightly Inspection review plans must declare artifact_type=nightly_site_inspection_review_plan.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		$contract_version = sanitize_text_field( (string) ( $plan['contract_version'] ?? '' ) );
		if ( '' !== $contract_version && 'nightly_site_inspection_core_review_plan.v1' !== $contract_version ) {
			return new WP_Error(
				'npcink_governance_core_nightly_inspection_contract_rejected',
				__( 'Nightly Inspection review plans must use the v1 Core review plan contract.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		if ( true === (bool) ( $plan['direct_wordpress_write'] ?? false ) ) {
			return new WP_Error(
				'npcink_governance_core_nightly_inspection_direct_write_rejected',
				__( 'Nightly Inspection review plans must not claim direct WordPress write authority.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		$evidence_refs = is_array( $plan['evidence_refs'] ?? null ) ? array_values( $plan['evidence_refs'] ) : array();
		if ( empty( $evidence_refs ) ) {
			return new WP_Error(
				'npcink_governance_core_nightly_inspection_evidence_missing',
				__( 'Nightly Inspection review plans must preserve Cloud evidence_refs.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		$selected_items = $this->nightly_inspection_selected_review_items( $plan );
		if ( empty( $selected_items ) ) {
			return new WP_Error(
				'npcink_governance_core_nightly_inspection_review_item_missing',
				__( 'Nightly Inspection review plans must preserve the selected Morning Brief review item.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		$write_actions = is_array( $plan['write_actions'] ?? null ) ? array_values( $plan['write_actions'] ) : array();
		if ( 1 !== count( $write_actions ) || ! is_array( $write_actions[0] ?? null ) ) {
			return new WP_Error(
				'npcink_governance_core_nightly_inspection_action_count_rejected',
				__( 'Nightly Inspection review plans must contain exactly one blocked create-draft review action.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		$action = $write_actions[0];
		if ( 'npcink-abilities-toolkit/create-draft' !== sanitize_text_field( (string) ( $action['target_ability_id'] ?? '' ) ) ) {
			return new WP_Error(
				'npcink_governance_core_nightly_inspection_target_rejected',
				__( 'Nightly Inspection review plans may target only create-draft as a non-ready review proposal.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		if ( true === (bool) ( $action['proposal_ready'] ?? true ) ) {
			return new WP_Error(
				'npcink_governance_core_nightly_inspection_ready_rejected',
				__( 'Nightly Inspection review plans must remain not ready until a human supplies draft fields.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		$requires_input = array_values( array_map( 'sanitize_key', (array) ( $action['requires_input'] ?? array() ) ) );
		foreach ( array( 'title', 'content' ) as $field ) {
			if ( ! in_array( $field, $requires_input, true ) ) {
				return new WP_Error(
					'npcink_governance_core_nightly_inspection_required_input_missing',
					__( 'Nightly Inspection review plans must require human title and content input.', 'npcink-governance-core' ),
					array(
						'status' => 422,
						'field'  => $field,
					)
				);
			}
		}

		$input  = is_array( $action['input'] ?? null ) ? $action['input'] : array();
		$status = sanitize_key( (string) ( $input['status'] ?? ( $input['post_status'] ?? 'draft' ) ) );
		if ( '' !== $status && 'draft' !== $status ) {
			return new WP_Error(
				'npcink_governance_core_nightly_inspection_publish_rejected',
				__( 'Nightly Inspection review plans may prepare draft proposals only.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		if ( true === (bool) ( $input['commit'] ?? false ) || false === (bool) ( $input['dry_run'] ?? true ) ) {
			return new WP_Error(
				'npcink_governance_core_nightly_inspection_commit_rejected',
				__( 'Nightly Inspection review action input must remain dry-run and must not request commit.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		return true;
	}

	/**
	 * Validates one Toolkit missing-ALT apply plan.
	 *
	 * @param array<string,mixed> $plan Plan data.
	 * @return true|WP_Error
	 */
	private function validate_media_alt_apply_plan_contract( array $plan ) {
		if ( 'media_alt_apply_plan' !== sanitize_key( (string) ( $plan['artifact_type'] ?? '' ) ) || 'media_alt_apply_plan.v1' !== (string) ( $plan['contract_version'] ?? '' ) ) {
			return new WP_Error( 'npcink_governance_core_media_alt_plan_invalid', __( 'Media ALT apply plans must declare media_alt_apply_plan.v1.', 'npcink-governance-core' ), array( 'status' => 422 ) );
		}
		if ( true === (bool) ( $plan['direct_wordpress_write'] ?? false ) || true !== (bool) ( $plan['dry_run'] ?? false ) || false !== (bool) ( $plan['commit_execution'] ?? true ) ) {
			return new WP_Error( 'npcink_governance_core_media_alt_plan_write_posture_rejected', __( 'Media ALT apply plans must remain dry-run and must not claim execution or direct write authority.', 'npcink-governance-core' ), array( 'status' => 422 ) );
		}
		if ( 'single' !== sanitize_key( (string) ( $plan['proposal_mode'] ?? '' ) ) ) {
			return new WP_Error( 'npcink_governance_core_media_alt_plan_single_required', __( 'Media ALT apply plans must contain one independently reviewed attachment.', 'npcink-governance-core' ), array( 'status' => 422 ) );
		}
		$authorization = is_array( $plan['authorization'] ?? null ) ? $plan['authorization'] : array();
		if ( 'core_proposal_required' !== sanitize_key( (string) ( $authorization['classification'] ?? '' ) ) ) {
			return new WP_Error( 'npcink_governance_core_media_alt_authorization_rejected', __( 'Media ALT apply plans must classify the write as Core proposal required.', 'npcink-governance-core' ), array( 'status' => 422 ) );
		}

		$actions = is_array( $plan['write_actions'] ?? null ) ? array_values( $plan['write_actions'] ) : array();
		if ( 1 !== count( $actions ) || ! is_array( $actions[0] ?? null ) ) {
			return new WP_Error( 'npcink_governance_core_media_alt_action_count_rejected', __( 'Media ALT apply plans must contain exactly one write action.', 'npcink-governance-core' ), array( 'status' => 422 ) );
		}
		$action = $actions[0];
		if ( 'npcink-abilities-toolkit/update-media-details' !== sanitize_text_field( (string) ( $action['target_ability_id'] ?? '' ) ) ) {
			return new WP_Error( 'npcink_governance_core_media_alt_target_rejected', __( 'Media ALT apply plans may target only update-media-details.', 'npcink-governance-core' ), array( 'status' => 422 ) );
		}
		if ( true !== ( $action['requires_approval'] ?? false ) || false !== ( $action['commit_execution'] ?? true ) || false === (bool) ( $action['proposal_ready'] ?? false ) ) {
			return new WP_Error( 'npcink_governance_core_media_alt_action_posture_rejected', __( 'Media ALT apply actions must be proposal-ready, require approval, and remain non-executing.', 'npcink-governance-core' ), array( 'status' => 422 ) );
		}

		$input = is_array( $action['input'] ?? null ) ? $action['input'] : array();
		$allowed_input_keys = array_fill_keys( array( 'attachment_id', 'alt', 'expected_current_alt', 'operator_visual_review_confirmed', 'dry_run', 'commit', 'idempotency_key' ), true );
		foreach ( array_keys( $input ) as $key ) {
			if ( ! isset( $allowed_input_keys[ (string) $key ] ) ) {
				return new WP_Error( 'npcink_governance_core_media_alt_input_key_rejected', __( 'Media ALT apply input contains a field outside the ALT-only contract.', 'npcink-governance-core' ), array( 'status' => 422, 'field' => (string) $key ) );
			}
		}
		$attachment_id = absint( $input['attachment_id'] ?? 0 );
		$alt           = trim( sanitize_text_field( (string) ( $input['alt'] ?? '' ) ) );
		if ( $attachment_id <= 0 || strlen( $alt ) < 3 || strlen( $alt ) > 160 || preg_match( '/https?:\/\/|generated\s+by|prompt\s*:|model\s*:|provider\s*:|profile\s*:/i', $alt ) ) {
			return new WP_Error( 'npcink_governance_core_media_alt_input_rejected', __( 'Media ALT apply input requires one attachment and one concise reviewed ALT.', 'npcink-governance-core' ), array( 'status' => 422 ) );
		}
		if ( ! array_key_exists( 'expected_current_alt', $input ) || '' !== (string) $input['expected_current_alt'] ) {
			return new WP_Error( 'npcink_governance_core_media_alt_missing_only', __( 'The first media ALT apply contract accepts only an explicitly empty expected current ALT.', 'npcink-governance-core' ), array( 'status' => 422 ) );
		}
		if ( true !== ( $input['operator_visual_review_confirmed'] ?? false ) ) {
			return new WP_Error( 'npcink_governance_core_media_alt_visual_confirmation_required', __( 'Media ALT apply input requires explicit operator visual confirmation.', 'npcink-governance-core' ), array( 'status' => 422 ) );
		}
		if ( true !== ( $input['dry_run'] ?? false ) || false !== ( $input['commit'] ?? true ) || '' === trim( sanitize_text_field( (string) ( $input['idempotency_key'] ?? '' ) ) ) ) {
			return new WP_Error( 'npcink_governance_core_media_alt_controls_rejected', __( 'Media ALT apply input must stay dry-run, non-commit, and include an idempotency key.', 'npcink-governance-core' ), array( 'status' => 422 ) );
		}

		$evidence = is_array( $action['preview'] ?? null ) ? $action['preview'] : array();
		if ( 'media_alt_apply_plan_item' !== sanitize_key( (string) ( $evidence['artifact_type'] ?? '' ) ) || 'media_alt_apply_plan.v1' !== (string) ( $evidence['contract_version'] ?? '' ) || 'media_alt_caption_review_set.v1' !== (string) ( $evidence['review_set_contract'] ?? '' ) ) {
			return new WP_Error( 'npcink_governance_core_media_alt_evidence_contract_rejected', __( 'Media ALT apply actions must preserve the reviewed ALT plan and review-set contracts.', 'npcink-governance-core' ), array( 'status' => 422 ) );
		}
		if ( absint( $evidence['attachment_id'] ?? 0 ) !== $attachment_id || '' !== (string) ( $evidence['expected_current_alt'] ?? '' ) || 'missing' !== sanitize_key( (string) ( $evidence['current_alt_status'] ?? '' ) ) || true !== ( $evidence['operator_reviewed'] ?? false ) || true !== ( $evidence['operator_visual_review_confirmed'] ?? false ) || sanitize_text_field( (string) ( $evidence['proposed_alt'] ?? '' ) ) !== $alt ) {
			return new WP_Error( 'npcink_governance_core_media_alt_evidence_mismatch', __( 'Media ALT review evidence does not match the guarded write input.', 'npcink-governance-core' ), array( 'status' => 422 ) );
		}
		if ( 0 !== strpos( sanitize_text_field( (string) ( $evidence['mime_type'] ?? '' ) ), 'image/' ) ) {
			return new WP_Error( 'npcink_governance_core_media_alt_image_required', __( 'Media ALT apply evidence must identify an image attachment.', 'npcink-governance-core' ), array( 'status' => 422 ) );
		}

		return true;
	}

	/**
	 * Validates a Toolkit content metadata apply plan from reviewed editor choices.
	 *
	 * @param array<string,mixed> $plan Plan data.
	 * @return true|WP_Error
	 */
	private function validate_content_metadata_apply_plan_contract( array $plan ) {
		$artifact_type = sanitize_key( (string) ( $plan['artifact_type'] ?? ( $plan['plan_type'] ?? '' ) ) );
		if ( 'content_metadata_apply_plan' !== $artifact_type ) {
			return new WP_Error(
				'npcink_governance_core_content_metadata_plan_invalid',
				__( 'Content metadata apply plans must declare artifact_type=content_metadata_apply_plan.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		if ( true === (bool) ( $plan['direct_wordpress_write'] ?? false ) ) {
			return new WP_Error(
				'npcink_governance_core_content_metadata_direct_write_rejected',
				__( 'Content metadata apply plans must not claim direct WordPress write authority.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		if ( true !== (bool) ( $plan['batch_approval'] ?? false ) || 'batch' !== sanitize_key( (string) ( $plan['proposal_mode'] ?? '' ) ) ) {
			return new WP_Error(
				'npcink_governance_core_content_metadata_batch_required',
				__( 'Content metadata apply plans must explicitly request one batch proposal approval.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		$authorization = is_array( $plan['authorization'] ?? null ) ? $plan['authorization'] : array();
		$decision_envelope = is_array( $authorization['decision_envelope'] ?? null ) ? $authorization['decision_envelope'] : array();
		$classification = sanitize_key( (string) ( $authorization['classification'] ?? ( $decision_envelope['classification'] ?? '' ) ) );
		if ( '' !== $classification && 'core_proposal_required' !== $classification ) {
			return new WP_Error(
				'npcink_governance_core_content_metadata_authorization_rejected',
				__( 'Content metadata apply plans submitted to Core must classify as Core proposal required.', 'npcink-governance-core' ),
				array(
					'status'         => 422,
					'classification' => $classification,
				)
			);
		}

		$post = is_array( $plan['post'] ?? null ) ? $plan['post'] : array();
		$post_id = absint( $post['post_id'] ?? ( $plan['target_post_id'] ?? 0 ) );
		if ( $post_id <= 0 ) {
			return new WP_Error(
				'npcink_governance_core_content_metadata_post_missing',
				__( 'Content metadata apply plans must target one post.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		$write_actions = is_array( $plan['write_actions'] ?? null ) ? array_values( $plan['write_actions'] ) : array();
		if ( count( $write_actions ) < 1 || count( $write_actions ) > 3 ) {
			return new WP_Error(
				'npcink_governance_core_content_metadata_actions_rejected',
				__( 'Content metadata apply plans must include one to three reviewed metadata actions.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		$allowed_targets = array(
			'npcink-abilities-toolkit/update-post'      => true,
			'npcink-abilities-toolkit/set-post-terms'  => true,
		);
		$seen_metadata_actions = array();

		foreach ( $write_actions as $action_index => $action ) {
			if ( ! is_array( $action ) ) {
				return new WP_Error(
					'npcink_governance_core_content_metadata_action_invalid',
					__( 'Content metadata apply actions must be objects.', 'npcink-governance-core' ),
					array(
						'status'       => 422,
						'action_index' => $action_index,
					)
				);
			}

			$target_ability_id = sanitize_text_field( (string) ( $action['target_ability_id'] ?? '' ) );
			if ( ! isset( $allowed_targets[ $target_ability_id ] ) ) {
				return new WP_Error(
					'npcink_governance_core_content_metadata_target_rejected',
					__( 'Content metadata apply plans may target only excerpt updates and existing taxonomy term assignment.', 'npcink-governance-core' ),
					array(
						'status'            => 422,
						'action_index'      => $action_index,
						'target_ability_id' => $target_ability_id,
					)
				);
			}

			$input = is_array( $action['input'] ?? null ) ? $action['input'] : array();
			if ( absint( $input['post_id'] ?? 0 ) !== $post_id ) {
				return new WP_Error(
					'npcink_governance_core_content_metadata_post_mismatch',
					__( 'Content metadata apply actions must target the plan post.', 'npcink-governance-core' ),
					array(
						'status'       => 422,
						'action_index' => $action_index,
					)
				);
			}

			if ( ! array_key_exists( 'dry_run', $input ) || true !== $input['dry_run'] || ! array_key_exists( 'commit', $input ) || false !== $input['commit'] ) {
				return new WP_Error(
					'npcink_governance_core_content_metadata_commit_rejected',
					__( 'Content metadata apply actions must remain dry-run and must not request commit.', 'npcink-governance-core' ),
					array(
						'status'       => 422,
						'action_index' => $action_index,
					)
				);
			}

			if ( 'npcink-abilities-toolkit/update-post' === $target_ability_id ) {
				if ( isset( $seen_metadata_actions['excerpt'] ) ) {
					return new WP_Error(
						'npcink_governance_core_content_metadata_duplicate_action_rejected',
						__( 'Content metadata apply plans may include at most one excerpt update action.', 'npcink-governance-core' ),
						array(
							'status'       => 422,
							'action_index' => $action_index,
							'action_type'  => 'excerpt',
						)
					);
				}
				$seen_metadata_actions['excerpt'] = true;

				$allowed_input_keys = array(
					'post_id'          => true,
					'excerpt'          => true,
					'dry_run'          => true,
					'commit'           => true,
					'idempotency_key'  => true,
				);
				foreach ( array_keys( $input ) as $input_key ) {
					if ( ! isset( $allowed_input_keys[ (string) $input_key ] ) ) {
						return new WP_Error(
							'npcink_governance_core_content_metadata_update_field_rejected',
							__( 'Content metadata update-post actions may update only the excerpt.', 'npcink-governance-core' ),
							array(
								'status'       => 422,
								'action_index' => $action_index,
								'field'        => sanitize_key( (string) $input_key ),
							)
						);
					}
				}
				if ( '' === trim( sanitize_textarea_field( (string) ( $input['excerpt'] ?? '' ) ) ) ) {
					return new WP_Error(
						'npcink_governance_core_content_metadata_excerpt_missing',
						__( 'Content metadata update-post actions must include a reviewed excerpt.', 'npcink-governance-core' ),
						array(
							'status'       => 422,
							'action_index' => $action_index,
						)
					);
				}
				continue;
			}

			$allowed_input_keys = array(
				'post_id'          => true,
				'taxonomy'         => true,
				'mode'             => true,
				'term_ids'         => true,
				'terms'            => true,
				'create_missing'   => true,
				'dry_run'          => true,
				'commit'           => true,
				'idempotency_key'  => true,
			);
			foreach ( array_keys( $input ) as $input_key ) {
				if ( ! isset( $allowed_input_keys[ (string) $input_key ] ) ) {
					return new WP_Error(
						'npcink_governance_core_content_metadata_term_field_rejected',
						__( 'Content metadata term actions may assign only reviewed existing term ids.', 'npcink-governance-core' ),
						array(
							'status'       => 422,
							'action_index' => $action_index,
							'field'        => sanitize_key( (string) $input_key ),
						)
					);
				}
			}

			$taxonomy = sanitize_key( (string) ( $input['taxonomy'] ?? '' ) );
			if ( ! in_array( $taxonomy, array( 'category', 'post_tag' ), true ) ) {
				return new WP_Error(
					'npcink_governance_core_content_metadata_taxonomy_rejected',
					__( 'Content metadata term actions may target only category or post_tag.', 'npcink-governance-core' ),
					array(
						'status'       => 422,
						'action_index' => $action_index,
						'taxonomy'     => $taxonomy,
					)
				);
			}
			if ( isset( $seen_metadata_actions[ $taxonomy ] ) ) {
				return new WP_Error(
					'npcink_governance_core_content_metadata_duplicate_action_rejected',
					__( 'Content metadata apply plans may include at most one action per taxonomy.', 'npcink-governance-core' ),
					array(
						'status'       => 422,
						'action_index' => $action_index,
						'action_type'  => $taxonomy,
					)
				);
			}
			$seen_metadata_actions[ $taxonomy ] = true;

			$mode = sanitize_key( (string) ( $input['mode'] ?? 'append' ) );
			if ( ! in_array( $mode, array( 'append', 'replace' ), true ) ) {
				return new WP_Error(
					'npcink_governance_core_content_metadata_mode_rejected',
					__( 'Content metadata term actions may append or replace existing terms only.', 'npcink-governance-core' ),
					array(
						'status'       => 422,
						'action_index' => $action_index,
						'mode'         => $mode,
					)
				);
			}

			$term_ids = is_array( $input['term_ids'] ?? null )
				? array_values( array_filter( array_map( 'absint', $input['term_ids'] ) ) )
				: array();
			if ( empty( $term_ids ) ) {
				return new WP_Error(
					'npcink_governance_core_content_metadata_term_ids_missing',
					__( 'Content metadata term actions must include reviewed existing term_ids.', 'npcink-governance-core' ),
					array(
						'status'       => 422,
						'action_index' => $action_index,
					)
				);
			}

			if ( ! array_key_exists( 'create_missing', $input ) || false !== $input['create_missing'] || ! empty( $input['terms'] ?? array() ) ) {
				return new WP_Error(
					'npcink_governance_core_content_metadata_create_missing_rejected',
					__( 'Content metadata term actions must not create or name missing terms.', 'npcink-governance-core' ),
					array(
						'status'       => 422,
						'action_index' => $action_index,
					)
				);
			}
		}

		return true;
	}

	/**
	 * Validates a media optimization plan for one user-intent approval.
	 *
	 * @param array<string,mixed> $plan Plan data.
	 * @return true|WP_Error
	 */
	private function validate_media_optimization_plan_contract( array $plan ) {
		$artifact_type = sanitize_key( (string) ( $plan['artifact_type'] ?? ( $plan['plan_type'] ?? '' ) ) );
		if ( 'media_optimization_plan' !== $artifact_type ) {
			return new WP_Error(
				'npcink_governance_core_media_optimization_plan_invalid',
				__( 'Media optimization plans must declare artifact_type=media_optimization_plan.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		if ( true !== (bool) ( $plan['batch_approval'] ?? false ) || 'batch' !== sanitize_key( (string) ( $plan['proposal_mode'] ?? '' ) ) ) {
			return new WP_Error(
				'npcink_governance_core_media_optimization_batch_required',
				__( 'Media optimization plans must explicitly request one batch proposal approval.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		$write_actions = is_array( $plan['write_actions'] ?? null ) ? array_values( $plan['write_actions'] ) : array();
		if ( count( $write_actions ) < 2 ) {
			return new WP_Error(
				'npcink_governance_core_media_optimization_actions_missing',
				__( 'Media optimization plans must include metadata and derivative adoption actions.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}
		if ( count( $write_actions ) > self::MEDIA_OPTIMIZATION_MAX_ACTIONS ) {
			return new WP_Error(
				'npcink_governance_core_media_optimization_actions_rejected',
				__( 'Media optimization plans must keep reviewed metadata and derivative actions bounded.', 'npcink-governance-core' ),
				array(
					'status'      => 422,
					'max_actions' => self::MEDIA_OPTIMIZATION_MAX_ACTIONS,
				)
			);
		}

		$targets = array();
		foreach ( $write_actions as $action ) {
			if ( is_array( $action ) ) {
				$targets[] = sanitize_text_field( (string) ( $action['target_ability_id'] ?? '' ) );
			}
		}

		if ( ! in_array( 'npcink-abilities-toolkit/update-media-details', $targets, true ) ) {
			return new WP_Error(
				'npcink_governance_core_media_optimization_metadata_missing',
				__( 'Media optimization plans must include npcink-abilities-toolkit/update-media-details.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		$derivative_targets = array(
			'npcink-abilities-toolkit/adopt-cloud-media-derivative',
			'npcink-abilities-toolkit/replace-media-file',
		);
		$separate_reference_repair_targets = array(
			'npcink-abilities-toolkit/patch-post-content',
			'npcink-abilities-toolkit/update-post',
			'npcink-abilities-toolkit/update-post-blocks',
		);
		if ( empty( array_intersect( $targets, $derivative_targets ) ) ) {
			return new WP_Error(
				'npcink_governance_core_media_optimization_derivative_missing',
				__( 'Media optimization plans must include a governed derivative adoption action.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}
		if ( ! empty( array_intersect( $targets, $separate_reference_repair_targets ) ) ) {
			return new WP_Error(
				'npcink_governance_core_media_optimization_reference_repair_split',
				__( 'Media optimization plans must not split post-content media reference repair into a separate write action.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		$attachment_requirements = array();
		foreach ( $write_actions as $action ) {
			if ( ! is_array( $action ) ) {
				continue;
			}
			$target = sanitize_text_field( (string) ( $action['target_ability_id'] ?? '' ) );
			$input = is_array( $action['input'] ?? null ) ? $action['input'] : array();
			$id    = absint( $input['attachment_id'] ?? 0 );
			if ( ! in_array( $target, array_merge( array( 'npcink-abilities-toolkit/update-media-details' ), $derivative_targets ), true ) ) {
				continue;
			}

			if ( $id <= 0 ) {
				return new WP_Error(
					'npcink_governance_core_media_optimization_attachment_mismatch',
					__( 'Media optimization plans must pair metadata and derivative actions for each attachment.', 'npcink-governance-core' ),
					array( 'status' => 422 )
				);
			}

			if ( ! isset( $attachment_requirements[ $id ] ) ) {
				$attachment_requirements[ $id ] = array(
					'metadata'   => false,
					'derivative' => false,
				);
			}
			if ( 'npcink-abilities-toolkit/update-media-details' === $target ) {
				$attachment_requirements[ $id ]['metadata'] = true;
			}
			if ( in_array( $target, $derivative_targets, true ) ) {
				$attachment_requirements[ $id ]['derivative'] = true;
			}
		}
		foreach ( $attachment_requirements as $attachment_requirement ) {
			if ( empty( $attachment_requirement['metadata'] ) || empty( $attachment_requirement['derivative'] ) ) {
				return new WP_Error(
					'npcink_governance_core_media_optimization_attachment_mismatch',
					__( 'Media optimization plans must pair metadata and derivative actions for each attachment.', 'npcink-governance-core' ),
					array( 'status' => 422 )
				);
			}
		}

		return true;
	}

	/**
	 * Validates a governed media adoption enhancement plan.
	 *
	 * @param array<string,mixed> $plan Plan data.
	 * @return true|WP_Error
	 */
	private function validate_media_adoption_enhancement_plan_contract( array $plan ) {
		$artifact_type = sanitize_key( (string) ( $plan['artifact_type'] ?? ( $plan['plan_type'] ?? '' ) ) );
		if ( 'media_adoption_enhancement_plan' !== $artifact_type ) {
			return new WP_Error(
				'npcink_governance_core_media_adoption_enhancement_plan_invalid',
				__( 'Media adoption enhancement plans must declare artifact_type=media_adoption_enhancement_plan.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		if ( true !== (bool) ( $plan['batch_approval'] ?? false ) || 'batch' !== sanitize_key( (string) ( $plan['proposal_mode'] ?? '' ) ) ) {
			return new WP_Error(
				'npcink_governance_core_media_adoption_enhancement_batch_required',
				__( 'Media adoption enhancement plans must explicitly request one batch proposal approval.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		if ( true === (bool) ( $plan['direct_wordpress_write'] ?? false ) ) {
			return new WP_Error(
				'npcink_governance_core_media_adoption_enhancement_direct_write_rejected',
				__( 'Media adoption enhancement plans must not claim direct WordPress write authority.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		$write_actions = is_array( $plan['write_actions'] ?? null ) ? array_values( $plan['write_actions'] ) : array();
		if ( count( $write_actions ) < 2 || count( $write_actions ) > 3 ) {
			return new WP_Error(
				'npcink_governance_core_media_adoption_enhancement_actions_rejected',
				__( 'Media adoption enhancement plans must contain upload, optimize, and optional reference repair actions only.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		$target_counts = array(
			'npcink-abilities-toolkit/upload-media-from-url' => 0,
			'npcink-abilities-toolkit/optimize-media-asset'  => 0,
			'npcink-abilities-toolkit/patch-post-content'    => 0,
		);

		foreach ( $write_actions as $action_index => $action ) {
			if ( ! is_array( $action ) ) {
				return new WP_Error(
					'npcink_governance_core_media_adoption_enhancement_action_invalid',
					__( 'Media adoption enhancement actions must be objects.', 'npcink-governance-core' ),
					array(
						'status'       => 422,
						'action_index' => $action_index,
					)
				);
			}

			$target_ability_id = sanitize_text_field( (string) ( $action['target_ability_id'] ?? '' ) );
			if ( ! isset( $target_counts[ $target_ability_id ] ) ) {
				return new WP_Error(
					'npcink_governance_core_media_adoption_enhancement_target_rejected',
					__( 'Media adoption enhancement plans may target only media upload, local optimization, and post-content reference repair abilities.', 'npcink-governance-core' ),
					array(
						'status'            => 422,
						'action_index'      => $action_index,
						'target_ability_id' => $target_ability_id,
					)
				);
			}
			++$target_counts[ $target_ability_id ];

			$action_error = $this->validate_media_adoption_enhancement_action( $action, $action_index );
			if ( is_wp_error( $action_error ) ) {
				return $action_error;
			}
		}

		if ( 1 !== $target_counts['npcink-abilities-toolkit/upload-media-from-url'] || 1 !== $target_counts['npcink-abilities-toolkit/optimize-media-asset'] || $target_counts['npcink-abilities-toolkit/patch-post-content'] > 1 ) {
			return new WP_Error(
				'npcink_governance_core_media_adoption_enhancement_actions_missing',
				__( 'Media adoption enhancement plans must include exactly one upload and one optimize action, plus at most one reference repair action.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		return true;
	}

	/**
	 * Validates one media adoption enhancement write action.
	 *
	 * @param array<string,mixed> $action Write action.
	 * @param int                 $action_index Action index.
	 * @return true|WP_Error
	 */
	private function validate_media_adoption_enhancement_action( array $action, int $action_index ) {
		$input = is_array( $action['input'] ?? null ) ? $action['input'] : array();
		if ( true === (bool) ( $input['commit'] ?? false ) || false === (bool) ( $input['dry_run'] ?? true ) ) {
			return new WP_Error(
				'npcink_governance_core_media_adoption_enhancement_commit_rejected',
				__( 'Media adoption enhancement action input must remain dry-run and must not request commit.', 'npcink-governance-core' ),
				array(
					'status'       => 422,
					'action_index' => $action_index,
				)
			);
		}

		$target_ability_id = sanitize_text_field( (string) ( $action['target_ability_id'] ?? '' ) );
		if ( 'npcink-abilities-toolkit/upload-media-from-url' === $target_ability_id ) {
			if ( ! $this->is_valid_absolute_url( $input['url'] ?? null ) ) {
				return new WP_Error(
					'npcink_governance_core_media_adoption_enhancement_url_missing',
					__( 'Media adoption enhancement upload actions must include a reviewed media URL.', 'npcink-governance-core' ),
					array(
						'status'       => 422,
						'action_index' => $action_index,
					)
				);
			}
			return true;
		}

		if ( 'npcink-abilities-toolkit/optimize-media-asset' === $target_ability_id ) {
			if ( ! $this->is_exact_output_reference( $input['attachment_id'] ?? null ) && absint( $input['attachment_id'] ?? 0 ) <= 0 ) {
				return new WP_Error(
					'npcink_governance_core_media_adoption_enhancement_attachment_missing',
					__( 'Media adoption enhancement optimize actions must include attachment_id or an approved output reference.', 'npcink-governance-core' ),
					array(
						'status'       => 422,
						'action_index' => $action_index,
					)
				);
			}
			return true;
		}

		if ( 'npcink-abilities-toolkit/patch-post-content' === $target_ability_id ) {
			if ( ! $this->is_exact_output_reference( $input['post_id'] ?? null ) && absint( $input['post_id'] ?? 0 ) <= 0 ) {
				return new WP_Error(
					'npcink_governance_core_media_adoption_enhancement_post_missing',
					__( 'Media adoption enhancement reference repair actions must include post_id or an approved output reference.', 'npcink-governance-core' ),
					array(
						'status'       => 422,
						'action_index' => $action_index,
					)
				);
			}
			$operations = is_array( $input['operations'] ?? null ) ? array_values( $input['operations'] ) : array();
			if ( empty( $operations ) ) {
				return new WP_Error(
					'npcink_governance_core_media_adoption_enhancement_patch_missing',
					__( 'Media adoption enhancement reference repair actions must include operations.', 'npcink-governance-core' ),
					array(
						'status'       => 422,
						'action_index' => $action_index,
					)
				);
			}
			foreach ( $operations as $operation ) {
				if ( ! is_array( $operation ) || 'replace' !== sanitize_key( (string) ( $operation['op'] ?? '' ) ) || ! $this->is_valid_absolute_url( $operation['find'] ?? null ) || ! $this->is_exact_output_reference( $operation['replace'] ?? null ) ) {
					return new WP_Error(
						'npcink_governance_core_media_adoption_enhancement_patch_invalid',
						__( 'Media adoption enhancement reference repair operations must replace one reviewed URL with an approved output reference.', 'npcink-governance-core' ),
						array(
							'status'       => 422,
							'action_index' => $action_index,
						)
					);
				}
			}
		}

		return true;
	}

	/**
	 * Validates a governed media rename plan for one attachment.
	 *
	 * @param array<string,mixed> $plan Plan data.
	 * @return true|WP_Error
	 */
	private function validate_media_rename_plan_contract( array $plan ) {
		$artifact_type = sanitize_key( (string) ( $plan['artifact_type'] ?? ( $plan['plan_type'] ?? '' ) ) );
		if ( 'media_rename_plan' !== $artifact_type ) {
			return new WP_Error(
				'npcink_governance_core_media_rename_plan_invalid',
				__( 'Media rename plans must declare artifact_type=media_rename_plan.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		$write_actions = is_array( $plan['write_actions'] ?? null ) ? array_values( $plan['write_actions'] ) : array();
		if ( 1 !== count( $write_actions ) || ! is_array( $write_actions[0] ?? null ) ) {
			return new WP_Error(
				'npcink_governance_core_media_rename_actions_missing',
				__( 'Media rename plans must include exactly one rename-media-file action.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		$action = $write_actions[0];
		if ( 'npcink-abilities-toolkit/rename-media-file' !== sanitize_text_field( (string) ( $action['target_ability_id'] ?? '' ) ) ) {
			return new WP_Error(
				'npcink_governance_core_media_rename_target_rejected',
				__( 'Media rename plans may target only npcink-abilities-toolkit/rename-media-file.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		$input         = is_array( $action['input'] ?? null ) ? $action['input'] : array();
		$attachment_id = absint( $plan['attachment_id'] ?? ( $input['attachment_id'] ?? 0 ) );
		if ( $attachment_id <= 0 || absint( $input['attachment_id'] ?? 0 ) !== $attachment_id ) {
			return new WP_Error(
				'npcink_governance_core_media_rename_attachment_mismatch',
				__( 'Media rename plans must target exactly one attachment.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		if ( '' === trim( sanitize_text_field( (string) ( $input['target_file_name'] ?? '' ) ) ) ) {
			return new WP_Error(
				'npcink_governance_core_media_rename_target_file_missing',
				__( 'Media rename plans must include a reviewed target_file_name.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		if ( true === (bool) ( $input['commit'] ?? false ) || false === (bool) ( $input['dry_run'] ?? true ) ) {
			return new WP_Error(
				'npcink_governance_core_media_rename_commit_rejected',
				__( 'Media rename action input must remain dry-run and must not request commit.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		return true;
	}

	/**
	 * Validates the governed existing-article optimization apply plan contract.
	 *
	 * @param array<string,mixed> $plan Plan data.
	 * @return true|WP_Error
	 */
	private function validate_article_optimization_apply_plan_contract( array $plan ) {
		$artifact_type = sanitize_key( (string) ( $plan['artifact_type'] ?? ( $plan['plan_type'] ?? '' ) ) );
		if ( 'article_optimization_apply_plan' !== $artifact_type ) {
			return new WP_Error(
				'npcink_governance_core_article_optimization_plan_invalid',
				__( 'Article optimization apply plans must declare artifact_type=article_optimization_apply_plan.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		$post_id = absint( $plan['post']['post_id'] ?? 0 );
		if ( $post_id <= 0 ) {
			return new WP_Error(
				'npcink_governance_core_article_optimization_post_missing',
				__( 'Article optimization apply plans must target one post.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		$write_actions = is_array( $plan['write_actions'] ?? null ) ? array_values( $plan['write_actions'] ) : array();
		if ( count( $write_actions ) < 1 || count( $write_actions ) > 4 ) {
			return new WP_Error(
				'npcink_governance_core_article_optimization_actions_rejected',
				__( 'Article optimization apply plans must include a bounded set of reviewed write actions.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		$allowed_targets = array(
			'npcink-abilities-toolkit/update-post'          => true,
			'npcink-abilities-toolkit/set-post-seo-meta'   => true,
			'npcink-abilities-toolkit/patch-post-content'  => true,
			'npcink-abilities-toolkit/update-post-blocks'  => true,
		);

		foreach ( $write_actions as $action_index => $action ) {
			if ( ! is_array( $action ) ) {
				return new WP_Error(
					'npcink_governance_core_article_optimization_action_invalid',
					__( 'Article optimization write actions must be objects.', 'npcink-governance-core' ),
					array(
						'status'       => 422,
						'action_index' => $action_index,
					)
				);
			}

			$target_ability_id = sanitize_text_field( (string) ( $action['target_ability_id'] ?? '' ) );
			if ( ! isset( $allowed_targets[ $target_ability_id ] ) ) {
				return new WP_Error(
					'npcink_governance_core_article_optimization_target_rejected',
					__( 'Article optimization apply plans may target only reviewed post update actions.', 'npcink-governance-core' ),
					array(
						'status'            => 422,
						'action_index'      => $action_index,
						'target_ability_id' => $target_ability_id,
					)
				);
			}

			$input = is_array( $action['input'] ?? null ) ? $action['input'] : array();
			if ( absint( $input['post_id'] ?? 0 ) !== $post_id ) {
				return new WP_Error(
					'npcink_governance_core_article_optimization_post_mismatch',
					__( 'Article optimization write actions must target the plan post.', 'npcink-governance-core' ),
					array(
						'status'       => 422,
						'action_index' => $action_index,
					)
				);
			}

			if ( true === (bool) ( $input['commit'] ?? false ) || false === (bool) ( $input['dry_run'] ?? true ) ) {
				return new WP_Error(
					'npcink_governance_core_article_optimization_commit_rejected',
					__( 'Article optimization write actions must remain dry-run and must not request commit.', 'npcink-governance-core' ),
					array(
						'status'       => 422,
						'action_index' => $action_index,
					)
				);
			}
		}

		return true;
	}

	/**
	 * Validates the governed Gutenberg article block plan contract.
	 *
	 * @param array<string,mixed> $plan Plan data.
	 * @return true|WP_Error
	 */
	private function validate_article_block_plan_contract( array $plan ) {
		$artifact_type = sanitize_key( (string) ( $plan['artifact_type'] ?? ( $plan['plan_type'] ?? '' ) ) );
		if ( 'article_block_plan' !== $artifact_type ) {
			return new WP_Error(
				'npcink_governance_core_article_block_plan_invalid',
				__( 'Article block plans must declare artifact_type=article_block_plan.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		if ( 'batch' !== sanitize_key( (string) ( $plan['proposal_mode'] ?? '' ) ) ) {
			return new WP_Error(
				'npcink_governance_core_article_block_mode_required',
				__( 'Article block plans must request batch proposal mode.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		if ( true === (bool) ( $plan['direct_wordpress_write'] ?? false ) ) {
			return new WP_Error(
				'npcink_governance_core_article_block_direct_write_rejected',
				__( 'Article block plans must not claim direct WordPress write authority.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		$allowed_templates = array(
			'editorial-longform' => true,
			'how-to-guide'       => true,
			'comparison-review'  => true,
		);
		$article_template = sanitize_key( (string) ( $plan['article_template'] ?? '' ) );
		if ( ! isset( $allowed_templates[ $article_template ] ) ) {
			return new WP_Error(
				'npcink_governance_core_article_block_template_rejected',
				__( 'Article block plans must use an allowlisted article template.', 'npcink-governance-core' ),
				array(
					'status'           => 422,
					'article_template' => $article_template,
				)
			);
		}

		if ( 'article_standard' !== sanitize_key( (string) ( $plan['responsive_profile'] ?? '' ) ) ) {
			return new WP_Error(
				'npcink_governance_core_article_block_responsive_profile_rejected',
				__( 'Article block plans must use the article_standard responsive profile.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		$editorial_quality = is_array( $plan['editorial_quality'] ?? null ) ? $plan['editorial_quality'] : array();
		$responsive_quality = is_array( $plan['responsive_quality'] ?? null ) ? $plan['responsive_quality'] : array();
		if ( true !== (bool) ( $editorial_quality['uses_native_blocks'] ?? false ) || true === (bool) ( $editorial_quality['custom_css_required'] ?? true ) ) {
			return new WP_Error(
				'npcink_governance_core_article_block_quality_rejected',
				__( 'Article block plans must use native Gutenberg blocks without custom CSS requirements.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}
		if ( true === (bool) ( $responsive_quality['custom_css_required'] ?? true ) ) {
			return new WP_Error(
				'npcink_governance_core_article_block_responsive_quality_rejected',
				__( 'Article block responsive quality must not require custom CSS.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		$validated_actions = $this->validate_draft_blocks_batch_actions(
			$plan,
			array(
				'actions_error_code'          => 'npcink_governance_core_article_block_actions_rejected',
				'actions_error_message'       => __( 'Article block plans must contain create-draft and update-post-blocks actions.', 'npcink-governance-core' ),
				'create_target_error_code'    => 'npcink_governance_core_article_block_create_action_invalid',
				'create_target_error_message' => __( 'Article block plans must first create a post draft.', 'npcink-governance-core' ),
				'update_target_error_code'    => 'npcink_governance_core_article_block_update_action_invalid',
				'update_target_error_message' => __( 'Article block plans must then update Gutenberg blocks.', 'npcink-governance-core' ),
				'post_type'                   => 'post',
				'post_type_error_code'        => 'npcink_governance_core_article_block_create_action_invalid',
				'post_type_error_message'     => __( 'Article block create action must create only a draft post.', 'npcink-governance-core' ),
				'title_error_code'            => 'npcink_governance_core_article_block_title_missing',
				'title_error_message'         => __( 'Article block create action must include a reviewed title.', 'npcink-governance-core' ),
				'commit_error_code'           => 'npcink_governance_core_article_block_commit_rejected',
				'create_commit_message'       => __( 'Article block create action must remain dry-run and must not request commit.', 'npcink-governance-core' ),
				'output_reference'            => '$outputs.create-article-draft.post_id',
				'output_error_code'           => 'npcink_governance_core_article_block_output_reference_required',
				'output_error_message'        => __( 'Article block update action must use the draft post output reference.', 'npcink-governance-core' ),
				'update_commit_message'       => __( 'Article block update action must remain dry-run and must not request commit.', 'npcink-governance-core' ),
				'blocks_error_code'           => 'npcink_governance_core_article_block_blocks_missing',
				'blocks_error_message'        => __( 'Article block update action must include Gutenberg blocks.', 'npcink-governance-core' ),
			)
		);
		if ( is_wp_error( $validated_actions ) ) {
			return $validated_actions;
		}

		$blocks = $validated_actions['blocks'];
		if ( ! empty( $this->block_class_names( $blocks ) ) ) {
			return new WP_Error(
				'npcink_governance_core_article_block_class_rejected',
				__( 'Article block plans must not rely on custom CSS classes.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		return true;
	}

	/**
	 * Validates the governed Gutenberg pattern page plan contract.
	 *
	 * @param array<string,mixed> $plan Plan data.
	 * @return true|WP_Error
	 */
	private function validate_pattern_page_plan_contract( array $plan ) {
		$artifact_type = sanitize_key( (string) ( $plan['artifact_type'] ?? ( $plan['plan_type'] ?? '' ) ) );
		if ( 'pattern_page_plan' !== $artifact_type ) {
			return new WP_Error(
				'npcink_governance_core_pattern_page_plan_invalid',
				__( 'Pattern page plans must declare artifact_type=pattern_page_plan.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		if ( 'batch' !== sanitize_key( (string) ( $plan['proposal_mode'] ?? '' ) ) ) {
			return new WP_Error(
				'npcink_governance_core_pattern_page_mode_required',
				__( 'Pattern page plans must request batch proposal mode.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		if ( true === (bool) ( $plan['direct_wordpress_write'] ?? false ) ) {
			return new WP_Error(
				'npcink_governance_core_pattern_page_direct_write_rejected',
				__( 'Pattern page plans must not claim direct WordPress write authority.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		$allowed_patterns = array(
			'openai-style-landing' => true,
		);
		$allowed_presets = array(
			'minimal-dark-light' => true,
		);
		$pattern_id      = sanitize_key( (string) ( $plan['pattern_id'] ?? '' ) );
		$style_preset    = sanitize_key( (string) ( $plan['style_preset'] ?? '' ) );
		if ( ! isset( $allowed_patterns[ $pattern_id ] ) || ! isset( $allowed_presets[ $style_preset ] ) ) {
			return new WP_Error(
				'npcink_governance_core_pattern_page_style_rejected',
				__( 'Pattern page plans must use an allowlisted pattern and style preset.', 'npcink-governance-core' ),
				array(
					'status'       => 422,
					'pattern_id'   => $pattern_id,
					'style_preset' => $style_preset,
				)
			);
		}

		$validated_actions = $this->validate_draft_blocks_batch_actions(
			$plan,
			array(
				'actions_error_code'          => 'npcink_governance_core_pattern_page_actions_rejected',
				'actions_error_message'       => __( 'Pattern page plans must contain create-draft and update-post-blocks actions.', 'npcink-governance-core' ),
				'create_target_error_code'    => 'npcink_governance_core_pattern_page_create_action_invalid',
				'create_target_error_message' => __( 'Pattern page plans must first create a page draft.', 'npcink-governance-core' ),
				'update_target_error_code'    => 'npcink_governance_core_pattern_page_update_action_invalid',
				'update_target_error_message' => __( 'Pattern page plans must then update Gutenberg blocks.', 'npcink-governance-core' ),
				'post_type'                   => 'page',
				'post_type_error_code'        => 'npcink_governance_core_pattern_page_create_action_invalid',
				'post_type_error_message'     => __( 'Pattern page create action must create only a draft page.', 'npcink-governance-core' ),
				'title_error_code'            => 'npcink_governance_core_pattern_page_title_missing',
				'title_error_message'         => __( 'Pattern page create action must include a reviewed title.', 'npcink-governance-core' ),
				'commit_error_code'           => 'npcink_governance_core_pattern_page_commit_rejected',
				'create_commit_message'       => __( 'Pattern page create action must remain dry-run and must not request commit.', 'npcink-governance-core' ),
				'output_reference'            => '$outputs.create-pattern-page.post_id',
				'output_error_code'           => 'npcink_governance_core_pattern_page_output_reference_required',
				'output_error_message'        => __( 'Pattern page update action must use the draft page output reference.', 'npcink-governance-core' ),
				'update_commit_message'       => __( 'Pattern page block update action must remain dry-run and must not request commit.', 'npcink-governance-core' ),
				'blocks_error_code'           => 'npcink_governance_core_pattern_page_blocks_missing',
				'blocks_error_message'        => __( 'Pattern page block update action must include Gutenberg blocks.', 'npcink-governance-core' ),
			)
		);
		if ( is_wp_error( $validated_actions ) ) {
			return $validated_actions;
		}

		$blocks = $validated_actions['blocks'];

		$allowed_classes = array_fill_keys(
			array_values(
				array_filter(
					array_map( array( $this, 'sanitize_block_class_name' ), (array) ( $plan['allowed_classes'] ?? array() ) )
				)
			),
			true
		);
		if ( empty( $allowed_classes ) ) {
			return new WP_Error(
				'npcink_governance_core_pattern_page_class_whitelist_missing',
				__( 'Pattern page plans must include a CSS class allowlist.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		foreach ( $this->block_class_names( $blocks ) as $class_name ) {
			if ( ! isset( $allowed_classes[ $class_name ] ) ) {
				return new WP_Error(
					'npcink_governance_core_pattern_page_class_rejected',
					__( 'Pattern page blocks may use only allowlisted CSS classes.', 'npcink-governance-core' ),
					array(
						'status'     => 422,
						'class_name' => $class_name,
					)
				);
			}
		}

		return true;
	}

	/**
	 * Validates the governed block theme site plan contract.
	 *
	 * @param array<string,mixed> $plan Plan data.
	 * @return true|WP_Error
	 */
	private function validate_block_theme_site_plan_contract( array $plan ) {
		$artifact_type = sanitize_key( (string) ( $plan['artifact_type'] ?? ( $plan['plan_type'] ?? '' ) ) );
		if ( 'block_theme_site_plan' !== $artifact_type ) {
			return new WP_Error(
				'npcink_governance_core_block_theme_site_plan_invalid',
				__( 'Block theme site plans must declare artifact_type=block_theme_site_plan.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		$intent          = sanitize_key( (string) ( $plan['intent'] ?? '' ) );
		$allowed_intents = array(
			'add_breadcrumbs'            => true,
			'customize_template_layout' => true,
		);
		if ( ! isset( $allowed_intents[ $intent ] ) ) {
			return new WP_Error(
				'npcink_governance_core_block_theme_site_intent_rejected',
				__( 'Block theme site plans currently must use intent=add_breadcrumbs or intent=customize_template_layout.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}
		if ( 'customize_template_layout' === $intent ) {
			$layout_contract = $this->validate_block_theme_site_layout_contract( $plan );
			if ( is_wp_error( $layout_contract ) ) {
				return $layout_contract;
			}
		}

		if ( 'batch' !== sanitize_key( (string) ( $plan['proposal_mode'] ?? '' ) ) ) {
			return new WP_Error(
				'npcink_governance_core_block_theme_site_mode_required',
				__( 'Block theme site plans must request batch proposal mode.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		if ( true === (bool) ( $plan['direct_wordpress_write'] ?? false ) ) {
			return new WP_Error(
				'npcink_governance_core_block_theme_site_direct_write_rejected',
				__( 'Block theme site plans must not claim direct WordPress write authority.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		$active_theme = is_array( $plan['active_theme'] ?? null ) ? $plan['active_theme'] : array();
		$theme        = sanitize_key( (string) ( $active_theme['stylesheet'] ?? '' ) );
		if ( '' === $theme ) {
			return new WP_Error(
				'npcink_governance_core_block_theme_site_theme_missing',
				__( 'Block theme site plans must identify the active theme stylesheet.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		$write_actions = is_array( $plan['write_actions'] ?? null ) ? array_values( $plan['write_actions'] ) : array();
		if ( empty( $write_actions ) ) {
			return new WP_Error(
				'npcink_governance_core_block_theme_site_actions_missing',
				__( 'Block theme site plans must contain at least one template write action.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}
		if ( count( $write_actions ) > self::BLOCK_THEME_SITE_MAX_ACTIONS ) {
			return new WP_Error(
				'npcink_governance_core_block_theme_site_actions_rejected',
				__( 'Block theme site plans must keep template write actions bounded.', 'npcink-governance-core' ),
				array(
					'status'      => 422,
					'max_actions' => self::BLOCK_THEME_SITE_MAX_ACTIONS,
				)
			);
		}

		$allowed_targets = array(
			'npcink-abilities-toolkit/update-template-blocks' => true,
			'npcink-abilities-toolkit/upsert-template-blocks' => true,
		);
		foreach ( $write_actions as $index => $action ) {
			if ( ! is_array( $action ) ) {
				return new WP_Error(
					'npcink_governance_core_block_theme_site_action_invalid',
					__( 'Block theme site write actions must be objects.', 'npcink-governance-core' ),
					array( 'status' => 422 )
				);
			}

			$target_ability_id = sanitize_text_field( (string) ( $action['target_ability_id'] ?? '' ) );
			if ( ! isset( $allowed_targets[ $target_ability_id ] ) ) {
				return new WP_Error(
					'npcink_governance_core_block_theme_site_target_rejected',
					__( 'Block theme site plans may only target governed template block write abilities.', 'npcink-governance-core' ),
					array(
						'status'            => 422,
						'action_index'      => $index,
						'target_ability_id' => $target_ability_id,
					)
				);
			}

			$input = is_array( $action['input'] ?? null ) ? $action['input'] : array();
			if ( 'replace' !== sanitize_key( (string) ( $input['mode'] ?? '' ) ) ) {
				return new WP_Error(
					'npcink_governance_core_block_theme_site_mode_rejected',
					__( 'Block theme site template actions must use mode=replace.', 'npcink-governance-core' ),
					array(
						'status'       => 422,
						'action_index' => $index,
					)
				);
			}
			if ( true === (bool) ( $input['commit'] ?? false ) || false === (bool) ( $input['dry_run'] ?? true ) ) {
				return new WP_Error(
					'npcink_governance_core_block_theme_site_commit_rejected',
					__( 'Block theme site template actions must remain dry-run and must not request commit.', 'npcink-governance-core' ),
					array(
						'status'       => 422,
						'action_index' => $index,
					)
				);
			}
			if ( empty( $input['blocks'] ?? array() ) || ! is_array( $input['blocks'] ?? null ) ) {
				return new WP_Error(
					'npcink_governance_core_block_theme_site_blocks_missing',
					__( 'Block theme site template actions must include reviewed Gutenberg blocks.', 'npcink-governance-core' ),
					array(
						'status'       => 422,
						'action_index' => $index,
					)
				);
			}
			$block_tree_error = $this->validate_block_theme_site_blocks( $input['blocks'], $input, $index );
			if ( is_wp_error( $block_tree_error ) ) {
				return $block_tree_error;
			}
			if ( 'npcink-abilities-toolkit/update-template-blocks' === $target_ability_id && absint( $input['post_id'] ?? 0 ) <= 0 ) {
				return new WP_Error(
					'npcink_governance_core_block_theme_site_post_id_missing',
					__( 'Existing template updates must include post_id.', 'npcink-governance-core' ),
					array(
						'status'       => 422,
						'action_index' => $index,
					)
				);
			}
			if ( 'npcink-abilities-toolkit/upsert-template-blocks' === $target_ability_id ) {
				$slug        = sanitize_key( (string) ( $input['slug'] ?? '' ) );
				$action_theme = sanitize_key( (string) ( $input['theme'] ?? '' ) );
				if ( '' === $slug || '' === $action_theme ) {
					return new WP_Error(
						'npcink_governance_core_block_theme_site_upsert_target_missing',
						__( 'Template override upserts must include slug and theme.', 'npcink-governance-core' ),
						array(
							'status'       => 422,
							'action_index' => $index,
						)
					);
				}
				if ( ! isset( $this->block_theme_site_allowed_template_slugs()[ $slug ] ) ) {
					return new WP_Error(
						'npcink_governance_core_block_theme_site_template_rejected',
						__( 'Block theme site template overrides may only target accepted template slugs.', 'npcink-governance-core' ),
						array(
							'status'       => 422,
							'action_index' => $index,
							'slug'         => $slug,
						)
					);
				}
				if ( $theme !== $action_theme ) {
					return new WP_Error(
						'npcink_governance_core_block_theme_site_theme_mismatch',
						__( 'Template override upserts must target the active theme.', 'npcink-governance-core' ),
						array(
							'status'       => 422,
							'action_index' => $index,
							'theme'        => $action_theme,
						)
					);
				}
			}
		}

		return true;
	}

	/**
	 * Validates reviewed block trees for governed block theme template plans.
	 *
	 * @param array<int,mixed>    $blocks Block tree.
	 * @param array<string,mixed> $input Action input.
	 * @param int                 $action_index Action index.
	 * @return true|WP_Error
	 */
	private function validate_block_theme_site_blocks( array $blocks, array $input, int $action_index ) {
		if ( true !== (bool) ( $input['validate_roundtrip'] ?? false ) ) {
			return new WP_Error(
				'npcink_governance_core_block_theme_site_roundtrip_required',
				__( 'Block theme site template actions must declare parser roundtrip validation.', 'npcink-governance-core' ),
				array(
					'status'       => 422,
					'action_index' => $action_index,
				)
			);
		}

		$stats = array(
			'count'     => 0,
			'max_depth' => 0,
		);
		$error = $this->inspect_block_theme_site_blocks( $blocks, 1, $stats );
		if ( is_wp_error( $error ) ) {
			$data                 = is_array( $error->get_error_data() ) ? $error->get_error_data() : array();
			$data['action_index'] = $action_index;
			return new WP_Error( $error->get_error_code(), $error->get_error_message(), $data );
		}
		if ( $stats['count'] > self::BLOCK_THEME_SITE_MAX_BLOCKS || $stats['max_depth'] > self::BLOCK_THEME_SITE_MAX_BLOCK_DEPTH ) {
			return new WP_Error(
				'npcink_governance_core_block_theme_site_block_tree_rejected',
				__( 'Block theme site template block trees must stay bounded.', 'npcink-governance-core' ),
				array(
					'status'       => 422,
					'action_index' => $action_index,
					'block_count'  => $stats['count'],
					'max_depth'    => $stats['max_depth'],
				)
			);
		}

		return true;
	}

	/**
	 * Recursively inspects one block theme site block tree.
	 *
	 * @param array<int,mixed>       $blocks Block list.
	 * @param int                    $depth Current depth.
	 * @param array<string,int>      $stats Mutable block stats.
	 * @return true|WP_Error
	 */
	private function inspect_block_theme_site_blocks( array $blocks, int $depth, array &$stats ) {
		$allowed_blocks = $this->block_theme_site_allowed_blocks();
		foreach ( $blocks as $block ) {
			if ( ! is_array( $block ) ) {
				return new WP_Error(
					'npcink_governance_core_block_theme_site_block_rejected',
					__( 'Block theme site plans may only contain reviewed block objects.', 'npcink-governance-core' ),
					array( 'status' => 422 )
				);
			}

			$stats['count']     = (int) $stats['count'] + 1;
			$stats['max_depth'] = max( (int) $stats['max_depth'], $depth );
			$block_name         = sanitize_text_field( (string) ( $block['blockName'] ?? '' ) );
			if ( ! isset( $allowed_blocks[ $block_name ] ) ) {
				return new WP_Error(
					'npcink_governance_core_block_theme_site_block_rejected',
					__( 'Block theme site plans may only use accepted safe core blocks.', 'npcink-governance-core' ),
					array(
						'status'     => 422,
						'block_name' => $block_name,
					)
				);
			}

			$attrs_json = wp_json_encode( is_array( $block['attrs'] ?? null ) ? $block['attrs'] : array() );
			if ( is_string( $attrs_json ) && strlen( $attrs_json ) > self::BLOCK_THEME_SITE_MAX_ATTR_BYTES ) {
				return new WP_Error(
					'npcink_governance_core_block_theme_site_block_rejected',
					__( 'Block theme site block attributes must stay bounded.', 'npcink-governance-core' ),
					array(
						'status'     => 422,
						'block_name' => $block_name,
					)
				);
			}

			if ( $this->block_theme_site_block_contains_forbidden_html( $block ) ) {
				return new WP_Error(
					'npcink_governance_core_block_theme_site_block_rejected',
					__( 'Block theme site plans must not contain scriptable or embedded raw HTML.', 'npcink-governance-core' ),
					array(
						'status'     => 422,
						'block_name' => $block_name,
					)
				);
			}

			$inner_blocks = is_array( $block['innerBlocks'] ?? null ) ? array_values( $block['innerBlocks'] ) : array();
			if ( ! empty( $inner_blocks ) ) {
				$error = $this->inspect_block_theme_site_blocks( $inner_blocks, $depth + 1, $stats );
				if ( is_wp_error( $error ) ) {
					return $error;
				}
			}
		}

		return true;
	}

	/**
	 * Returns allowed block names for governed block theme template proposals.
	 *
	 * @return array<string,bool>
	 */
	private function block_theme_site_allowed_blocks(): array {
		return array(
			'core/button'              => true,
			'core/buttons'             => true,
			'core/categories'          => true,
			'core/comments'            => true,
			'core/column'              => true,
			'core/columns'             => true,
			'core/group'               => true,
			'core/heading'             => true,
			'core/latest-posts'        => true,
			'core/list'                => true,
			'core/list-item'           => true,
			'core/paragraph'           => true,
			'core/post-author-name'    => true,
			'core/post-content'        => true,
			'core/post-date'           => true,
			'core/post-featured-image' => true,
			'core/post-navigation-link' => true,
			'core/post-terms'          => true,
			'core/post-template'       => true,
			'core/post-title'          => true,
			'core/query'               => true,
			'core/query-no-results'    => true,
			'core/query-pagination'    => true,
			'core/separator'           => true,
			'core/spacer'              => true,
			'core/template-part'       => true,
		);
	}

	/**
	 * Returns accepted template slugs for governed block theme template proposals.
	 *
	 * @return array<string,bool>
	 */
	private function block_theme_site_allowed_template_slugs(): array {
		return array(
			'front-page' => true,
			'home'       => true,
			'index'      => true,
			'page'       => true,
			'single'     => true,
		);
	}

	/**
	 * Checks block HTML fields for scriptable or embedded markup.
	 *
	 * @param array<string,mixed> $block Block.
	 * @return bool
	 */
	private function block_theme_site_block_contains_forbidden_html( array $block ): bool {
		$strings = array();
		if ( is_string( $block['innerHTML'] ?? null ) ) {
			$strings[] = (string) $block['innerHTML'];
		}
		if ( is_array( $block['innerContent'] ?? null ) ) {
			foreach ( $block['innerContent'] as $content ) {
				if ( is_string( $content ) ) {
					$strings[] = $content;
				}
			}
		}

		foreach ( $strings as $html ) {
			if ( preg_match( '/<\s*(script|iframe|object|embed|style|link|meta)\b/i', $html ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Validates bounded template layout metadata for block theme layout plans.
	 *
	 * @param array<string,mixed> $plan Plan data.
	 * @return true|WP_Error
	 */
	private function validate_block_theme_site_layout_contract( array $plan ) {
		$allowed_profiles = array(
			'article_standard' => true,
			'page_standard'    => true,
			'homepage_landing' => true,
		);
		$allowed_profile_versions = array(
			'article_standard' => 'article_standard@0.4',
			'page_standard'    => 'page_standard@0.2',
			'homepage_landing' => 'homepage_landing@0.3',
		);
		$layout_profile   = sanitize_key( (string) ( $plan['layout_profile'] ?? '' ) );
		$layout_contract  = is_array( $plan['template_layout_contract'] ?? null ) ? $plan['template_layout_contract'] : array();
		$contract_status  = sanitize_key( (string) ( $layout_contract['contract_status'] ?? '' ) );
		$contract_model   = sanitize_key( (string) ( $layout_contract['placement_model'] ?? '' ) );
		$compiler_version = sanitize_text_field( (string) ( $layout_contract['compiler_version'] ?? ( $plan['compiler_version'] ?? '' ) ) );
		$policy_version   = sanitize_text_field( (string) ( $layout_contract['forbidden_policy_version'] ?? '' ) );
		$profile_rows     = is_array( $layout_contract['profiles'] ?? null ) ? array_values( $layout_contract['profiles'] ) : array();
		$accepted_versions = is_array( $layout_contract['accepted_profile_versions'] ?? null ) ? array_values( $layout_contract['accepted_profile_versions'] ) : array();

		if ( '' !== $layout_profile && 'auto' !== $layout_profile && ! isset( $allowed_profiles[ $layout_profile ] ) ) {
			return new WP_Error(
				'npcink_governance_core_block_theme_site_layout_profile_rejected',
				__( 'Block theme layout plans may only use accepted bounded layout profiles.', 'npcink-governance-core' ),
				array(
					'status'         => 422,
					'layout_profile' => $layout_profile,
				)
			);
		}
		if (
			'pass' !== $contract_status
			|| 'bounded_template_layout_profile' !== $contract_model
			|| 'block_theme_profile_compiler@0.3' !== $compiler_version
			|| 'block_theme_safe_core_blocks@0.2' !== $policy_version
		) {
			return new WP_Error(
				'npcink_governance_core_block_theme_site_layout_contract_rejected',
				__( 'Block theme layout plans must include a passing bounded template layout contract with accepted compiler and policy versions.', 'npcink-governance-core' ),
				array(
					'status'          => 422,
					'contract_status' => $contract_status,
					'contract_model'  => $contract_model,
					'compiler_version' => $compiler_version,
					'forbidden_policy_version' => $policy_version,
				)
			);
		}
		foreach ( $allowed_profile_versions as $profile_version ) {
			if ( ! in_array( $profile_version, $accepted_versions, true ) ) {
				return new WP_Error(
					'npcink_governance_core_block_theme_site_layout_contract_rejected',
					__( 'Block theme layout plans must declare all accepted versioned profile contracts.', 'npcink-governance-core' ),
					array(
						'status'          => 422,
						'missing_profile_version' => $profile_version,
					)
				);
			}
		}
		if ( empty( $profile_rows ) ) {
			return new WP_Error(
				'npcink_governance_core_block_theme_site_layout_profiles_missing',
				__( 'Block theme layout plans must include reviewed layout profile rows.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}
		foreach ( $profile_rows as $index => $row ) {
			if ( ! is_array( $row ) ) {
				return new WP_Error(
					'npcink_governance_core_block_theme_site_layout_profile_invalid',
					__( 'Block theme layout profile rows must be objects.', 'npcink-governance-core' ),
					array(
						'status'        => 422,
						'profile_index' => $index,
					)
				);
			}
			$row_profile = sanitize_key( (string) ( $row['layout_profile'] ?? '' ) );
			$row_profile_version = sanitize_text_field( (string) ( $row['profile_version'] ?? ( $row['profile_id'] ?? '' ) ) );
			$row_operation       = sanitize_key( (string) ( $row['operation'] ?? '' ) );
			$row_modules         = is_array( $row['modules'] ?? null ) ? array_values( $row['modules'] ) : array();
			$row_forbidden       = is_array( $row['forbidden_outputs'] ?? null ) ? array_values( $row['forbidden_outputs'] ) : array();
			if ( ! isset( $allowed_profiles[ $row_profile ] ) || empty( $row['profile_allowed'] ) ) {
				return new WP_Error(
					'npcink_governance_core_block_theme_site_layout_profile_rejected',
					__( 'Block theme layout plans may only use accepted bounded layout profiles.', 'npcink-governance-core' ),
					array(
						'status'         => 422,
						'profile_index'  => $index,
						'layout_profile' => $row_profile,
					)
				);
			}
			if (
				( $allowed_profile_versions[ $row_profile ] ?? '' ) !== $row_profile_version
				|| 'replace_template_layout_with_preserved_template_parts' !== $row_operation
				|| empty( $row_modules )
			) {
				return new WP_Error(
					'npcink_governance_core_block_theme_site_layout_profile_rejected',
					__( 'Block theme layout profile rows must use accepted profile versions, operations, and modules.', 'npcink-governance-core' ),
					array(
						'status'          => 422,
						'profile_index'   => $index,
						'layout_profile'  => $row_profile,
						'profile_version' => $row_profile_version,
						'operation'       => $row_operation,
					)
				);
			}
			foreach ( array( 'core/html', 'non_core_blocks', 'custom_css', 'theme_json', 'global_styles', 'navigation_write', 'template_part_write' ) as $required_forbidden ) {
				if ( ! in_array( $required_forbidden, $row_forbidden, true ) ) {
					return new WP_Error(
						'npcink_governance_core_block_theme_site_layout_contract_rejected',
						__( 'Block theme layout profile rows must preserve the forbidden output policy.', 'npcink-governance-core' ),
						array(
							'status'             => 422,
							'profile_index'      => $index,
							'missing_forbidden'  => $required_forbidden,
						)
					);
				}
			}
		}

		return true;
	}

	/**
	 * Validates the shared draft-create plus block-update batch action shape.
	 *
	 * @param array<string,mixed> $plan Plan data.
	 * @param array<string,mixed> $config Error codes, messages, and expected action shape.
	 * @return array<string,mixed>|WP_Error
	 */
	private function validate_draft_blocks_batch_actions( array $plan, array $config ) {
		$write_actions = is_array( $plan['write_actions'] ?? null ) ? array_values( $plan['write_actions'] ) : array();
		if ( 2 !== count( $write_actions ) || ! is_array( $write_actions[0] ?? null ) || ! is_array( $write_actions[1] ?? null ) ) {
			return new WP_Error(
				(string) $config['actions_error_code'],
				(string) $config['actions_error_message'],
				array( 'status' => 422 )
			);
		}

		$create_action = $write_actions[0];
		$update_action = $write_actions[1];
		if ( 'npcink-abilities-toolkit/create-draft' !== sanitize_text_field( (string) ( $create_action['target_ability_id'] ?? '' ) ) ) {
			return new WP_Error(
				(string) $config['create_target_error_code'],
				(string) $config['create_target_error_message'],
				array( 'status' => 422 )
			);
		}
		if ( 'npcink-abilities-toolkit/update-post-blocks' !== sanitize_text_field( (string) ( $update_action['target_ability_id'] ?? '' ) ) ) {
			return new WP_Error(
				(string) $config['update_target_error_code'],
				(string) $config['update_target_error_message'],
				array( 'status' => 422 )
			);
		}

		$create_input = is_array( $create_action['input'] ?? null ) ? $create_action['input'] : array();
		if ( sanitize_key( (string) ( $config['post_type'] ?? '' ) ) !== sanitize_key( (string) ( $create_input['post_type'] ?? '' ) ) || 'draft' !== sanitize_key( (string) ( $create_input['status'] ?? 'draft' ) ) ) {
			return new WP_Error(
				(string) $config['post_type_error_code'],
				(string) $config['post_type_error_message'],
				array( 'status' => 422 )
			);
		}
		if ( '' === trim( sanitize_text_field( (string) ( $create_input['title'] ?? '' ) ) ) ) {
			return new WP_Error(
				(string) $config['title_error_code'],
				(string) $config['title_error_message'],
				array( 'status' => 422 )
			);
		}
		if ( true === (bool) ( $create_input['commit'] ?? false ) || false === (bool) ( $create_input['dry_run'] ?? true ) ) {
			return new WP_Error(
				(string) $config['commit_error_code'],
				(string) $config['create_commit_message'],
				array( 'status' => 422 )
			);
		}

		$update_input = is_array( $update_action['input'] ?? null ) ? $update_action['input'] : array();
		if ( (string) $config['output_reference'] !== (string) ( $update_input['post_id'] ?? '' ) ) {
			return new WP_Error(
				(string) $config['output_error_code'],
				(string) $config['output_error_message'],
				array( 'status' => 422 )
			);
		}
		if ( true === (bool) ( $update_input['commit'] ?? false ) || false === (bool) ( $update_input['dry_run'] ?? true ) ) {
			return new WP_Error(
				(string) $config['commit_error_code'],
				(string) $config['update_commit_message'],
				array( 'status' => 422 )
			);
		}

		$blocks = is_array( $update_input['blocks'] ?? null ) ? array_values( $update_input['blocks'] ) : array();
		if ( empty( $blocks ) ) {
			return new WP_Error(
				(string) $config['blocks_error_code'],
				(string) $config['blocks_error_message'],
				array( 'status' => 422 )
			);
		}

		return array(
			'write_actions' => $write_actions,
			'create_input'  => $create_input,
			'update_input'  => $update_input,
			'blocks'        => $blocks,
		);
	}

	/**
	 * Returns CSS class names used by Gutenberg block attrs.
	 *
	 * @param array<int,mixed> $blocks Block tree.
	 * @return array<int,string>
	 */
	private function block_class_names( array $blocks ): array {
		$class_names = array();
		foreach ( $blocks as $block ) {
			if ( ! is_array( $block ) ) {
				continue;
			}
			$attrs = is_array( $block['attrs'] ?? null ) ? $block['attrs'] : array();
			foreach ( preg_split( '/\s+/', (string) ( $attrs['className'] ?? '' ) ) ?: array() as $class_name ) {
				$class_name = $this->sanitize_block_class_name( $class_name );
				if ( '' !== $class_name ) {
					$class_names[] = $class_name;
				}
			}
			if ( is_array( $block['innerBlocks'] ?? null ) ) {
				$class_names = array_merge( $class_names, $this->block_class_names( array_values( $block['innerBlocks'] ) ) );
			}
		}

		return array_values( array_unique( $class_names ) );
	}

	/**
	 * Sanitizes a CSS class token without requiring all WordPress admin helpers in tests.
	 *
	 * @param mixed $class_name Raw class token.
	 * @return string
	 */
	public function sanitize_block_class_name( $class_name ): string {
		if ( function_exists( 'sanitize_html_class' ) ) {
			return sanitize_html_class( (string) $class_name );
		}

		return preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $class_name ) ?: '';
	}

	/**
	 * Validates the P0 article writing plan contract.
	 *
	 * @param array<string,mixed> $plan Plan data.
	 * @return true|WP_Error
	 */
	private function validate_article_write_plan_contract( array $plan ) {
		$artifact_type = sanitize_key( (string) ( $plan['artifact_type'] ?? ( $plan['plan_type'] ?? '' ) ) );
		if ( 'article_write_plan' !== $artifact_type ) {
			return new WP_Error(
				'npcink_governance_core_article_plan_invalid',
				__( 'Article write plans must declare artifact_type=article_write_plan.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		if ( absint( $plan['version'] ?? 0 ) < 1 ) {
			return new WP_Error(
				'npcink_governance_core_article_plan_invalid',
				__( 'Article write plans must declare version 1 or newer.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		$artifact_error = $this->validate_article_artifacts( $plan, 'npcink_governance_core_article_plan_' );
		if ( is_wp_error( $artifact_error ) ) {
			return $artifact_error;
		}

		$write_actions = is_array( $plan['write_actions'] ?? null ) ? array_values( $plan['write_actions'] ) : array();
		if ( 1 !== count( $write_actions ) || ! is_array( $write_actions[0] ?? null ) ) {
			return new WP_Error(
				'npcink_governance_core_article_plan_action_count_rejected',
				__( 'P0 article write plans must contain exactly one create-draft action.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		$action = $write_actions[0];
		$draft_error = $this->validate_article_draft_action( $action, 'npcink_governance_core_article_plan_' );
		if ( is_wp_error( $draft_error ) ) {
			return $draft_error;
		}

		return true;
	}

	/**
	 * Validates article review artifacts.
	 *
	 * @param array<string,mixed> $payload Artifact payload.
	 * @param string              $error_prefix Error code prefix.
	 * @return true|WP_Error
	 */
	private function validate_article_artifacts( array $payload, string $error_prefix ) {
		foreach ( $this->article_workflow_artifact_keys() as $artifact_key ) {
			if ( ! is_array( $payload[ $artifact_key ] ?? null ) ) {
				return new WP_Error(
					$error_prefix . 'artifact_missing',
					__( 'Article write plans must include every required workflow artifact.', 'npcink-governance-core' ),
					array(
						'status'   => 422,
						'artifact' => $artifact_key,
					)
				);
			}
		}

		$risk_report = is_array( $payload['article_risk_report'] ?? null ) ? $payload['article_risk_report'] : array();
		if ( true !== (bool) ( $risk_report['ready_for_proposal'] ?? false ) ) {
			return new WP_Error(
				$error_prefix . 'not_ready',
				__( 'Article write plans must pass risk review before proposal intake.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		if ( 'high' === sanitize_key( (string) ( $risk_report['risk_level'] ?? '' ) ) ) {
			return new WP_Error(
				$error_prefix . 'risk_blocked',
				__( 'High-risk article plans must be revised before draft proposal intake.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		if ( ! empty( $risk_report['blocked_claims'] ?? array() ) ) {
			return new WP_Error(
				$error_prefix . 'blocked_claims',
				__( 'Article write plans with blocked claims cannot create proposals.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		return true;
	}

	/**
	 * Validates a draft-only article write action.
	 *
	 * @param array<string,mixed> $action Write action.
	 * @param string              $error_prefix Error code prefix.
	 * @return true|WP_Error
	 */
	private function validate_article_draft_action( array $action, string $error_prefix ) {
		if ( 'npcink-abilities-toolkit/create-draft' !== sanitize_text_field( (string) ( $action['target_ability_id'] ?? '' ) ) ) {
			return new WP_Error(
				$error_prefix . 'target_rejected',
				__( 'P0 article write plans may target only npcink-abilities-toolkit/create-draft.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		$input  = is_array( $action['input'] ?? null ) ? $action['input'] : array();
		$status = sanitize_key( (string) ( $input['status'] ?? ( $input['post_status'] ?? 'draft' ) ) );
		if ( '' !== $status && 'draft' !== $status ) {
			return new WP_Error(
				$error_prefix . 'publish_rejected',
				__( 'Article write plans may create drafts only.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		if ( true === (bool) ( $input['commit'] ?? false ) || false === (bool) ( $input['dry_run'] ?? true ) ) {
			return new WP_Error(
				$error_prefix . 'commit_rejected',
				__( 'Article write plan input must remain dry-run and must not request commit.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		return true;
	}

	/**
	 * Validates one action from an article media batch plan.
	 *
	 * @param array<string,mixed> $action Write action.
	 * @param int                 $action_index Action index.
	 * @return true|WP_Error
	 */
	private function validate_article_media_batch_action( array $action, int $action_index ) {
		$target_ability_id = sanitize_text_field( (string) ( $action['target_ability_id'] ?? '' ) );
		if ( 'npcink-abilities-toolkit/create-draft' === $target_ability_id ) {
			return $this->validate_article_draft_action( $action, 'npcink_governance_core_article_media_batch_' );
		}

		$input = is_array( $action['input'] ?? null ) ? $action['input'] : array();
		if ( true === (bool) ( $input['commit'] ?? false ) || false === (bool) ( $input['dry_run'] ?? true ) ) {
			return new WP_Error(
				'npcink_governance_core_article_media_batch_commit_rejected',
				__( 'Article media batch action input must remain dry-run and must not request commit.', 'npcink-governance-core' ),
				array(
					'status'       => 422,
					'action_index' => $action_index,
				)
			);
		}

		if ( 'npcink-abilities-toolkit/upload-media-from-url' === $target_ability_id ) {
			if ( ! $this->is_valid_absolute_url( $input['url'] ?? null ) ) {
				return new WP_Error(
					'npcink_governance_core_article_media_batch_media_url_missing',
					__( 'Article media upload actions must include a reviewed media URL.', 'npcink-governance-core' ),
					array(
						'status'       => 422,
						'action_index' => $action_index,
					)
				);
			}
			return true;
		}

		if ( 'npcink-abilities-toolkit/update-media-details' === $target_ability_id ) {
			if ( ! $this->is_exact_output_reference( $input['attachment_id'] ?? null ) && absint( $input['attachment_id'] ?? 0 ) <= 0 ) {
				return new WP_Error(
					'npcink_governance_core_article_media_batch_attachment_missing',
					__( 'Article media metadata actions must include attachment_id or an approved output reference.', 'npcink-governance-core' ),
					array(
						'status'       => 422,
						'action_index' => $action_index,
					)
				);
			}
			return true;
		}

		if ( 'npcink-abilities-toolkit/set-post-featured-image' === $target_ability_id ) {
			if ( ! $this->is_exact_output_reference( $input['post_id'] ?? null ) && absint( $input['post_id'] ?? 0 ) <= 0 ) {
				return new WP_Error(
					'npcink_governance_core_article_media_batch_post_missing',
					__( 'Article featured-image actions must include post_id or an approved output reference.', 'npcink-governance-core' ),
					array(
						'status'       => 422,
						'action_index' => $action_index,
					)
				);
			}
			if ( ! $this->is_exact_output_reference( $input['attachment_id'] ?? null ) && absint( $input['attachment_id'] ?? 0 ) <= 0 && ! $this->is_valid_absolute_url( $input['media_url'] ?? null ) ) {
				return new WP_Error(
					'npcink_governance_core_article_media_batch_featured_image_missing',
					__( 'Article featured-image actions must include attachment_id, media_url, or an approved output reference.', 'npcink-governance-core' ),
					array(
						'status'       => 422,
						'action_index' => $action_index,
					)
				);
			}
			return true;
		}

		if ( 'npcink-abilities-toolkit/patch-post-content' === $target_ability_id ) {
			if ( ! $this->is_exact_output_reference( $input['post_id'] ?? null ) && absint( $input['post_id'] ?? 0 ) <= 0 ) {
				return new WP_Error(
					'npcink_governance_core_article_media_batch_post_missing',
					__( 'Article inline-image patch actions must include post_id or an approved output reference.', 'npcink-governance-core' ),
					array(
						'status'       => 422,
						'action_index' => $action_index,
					)
				);
			}
			if ( ! is_array( $input['operations'] ?? null ) ) {
				return new WP_Error(
					'npcink_governance_core_article_media_batch_patch_missing',
					__( 'Article inline-image patch actions must include operations.', 'npcink-governance-core' ),
					array(
						'status'       => 422,
						'action_index' => $action_index,
					)
				);
			}
		}

		return true;
	}

	/**
	 * Checks whether a value is an exact batch output reference.
	 *
	 * @param mixed $value Value.
	 * @return bool
	 */
	private function is_exact_output_reference( $value ): bool {
		return is_string( $value ) && 1 === preg_match( '/^\$outputs\.[A-Za-z0-9_-]+\.[A-Za-z0-9_]+$/', $value );
	}

	/**
	 * Checks for an absolute HTTP(S) URL without depending on WordPress URL helpers.
	 *
	 * @param mixed $value Candidate URL.
	 * @return bool
	 */
	private function is_valid_absolute_url( $value ): bool {
		if ( ! is_string( $value ) ) {
			return false;
		}

		$url = trim( $value );
		if ( '' === $url || false === filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return false;
		}

		$scheme = preg_match( '/^([a-z][a-z0-9+.-]*):/i', $url, $matches ) ? strtolower( (string) $matches[1] ) : '';
		return in_array( $scheme, array( 'http', 'https' ), true );
	}

	/**
	 * Returns article workflow artifact keys that must be reviewable.
	 *
	 * @return array<int,string>
	 */
	public function article_workflow_artifact_keys(): array {
		return array(
			'article_goal_brief',
			'research_evidence_pack',
			'article_outline',
			'article_draft_candidate',
			'discoverability_pack',
			'article_risk_report',
		);
	}

	/**
	 * Returns the Core intake package embedded by Cloud or Toolbox.
	 *
	 * @param array<string,mixed> $plan Plan data.
	 * @return array<string,mixed>
	 */
	public function nightly_inspection_core_intake_package( array $plan ): array {
		$source_context = is_array( $plan['source_context'] ?? null ) ? $plan['source_context'] : array();
		if ( is_array( $source_context['cloud_core_intake_package'] ?? null ) ) {
			return $source_context['cloud_core_intake_package'];
		}

		if ( is_array( $plan['core_intake_package'] ?? null ) ) {
			return $plan['core_intake_package'];
		}

		$handoff = is_array( $plan['handoff'] ?? null ) ? $plan['handoff'] : array();
		if ( is_array( $handoff['core_intake_package'] ?? null ) ) {
			return $handoff['core_intake_package'];
		}

		return array();
	}

	/**
	 * Returns selected Morning Brief review items from the v1 Core handoff shape.
	 *
	 * @param array<string,mixed> $plan Plan data.
	 * @return array<int,array<string,mixed>>
	 */
	public function nightly_inspection_selected_review_items( array $plan ): array {
		$core_intake_package = $this->nightly_inspection_core_intake_package( $plan );
		$selected_items      = is_array( $core_intake_package['selected_review_items'] ?? null ) ? array_values( $core_intake_package['selected_review_items'] ) : array();

		if ( empty( $selected_items ) ) {
			$selected_items = is_array( $plan['selected_review_items'] ?? null ) ? array_values( $plan['selected_review_items'] ) : array();
		}

		if ( empty( $selected_items ) ) {
			$selected_items = is_array( $plan['evidence_refs'] ?? null ) ? array_values( $plan['evidence_refs'] ) : array();
		}

		$items = array();
		foreach ( $selected_items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$action_id = sanitize_text_field( (string) ( $item['action_id'] ?? ( $item['item_id'] ?? ( $item['id'] ?? '' ) ) ) );
			if ( '' === $action_id ) {
				continue;
			}

			$items[] = array(
				'action_id'               => $action_id,
				'title'                   => sanitize_text_field( (string) ( $item['title'] ?? __( 'Morning Brief review item', 'npcink-governance-core' ) ) ),
				'object_type'             => sanitize_key( (string) ( $item['object_type'] ?? '' ) ),
				'object_id'               => sanitize_text_field( (string) ( $item['object_id'] ?? '' ) ),
				'post_id'                 => absint( $item['post_id'] ?? 0 ),
				'score'                   => is_numeric( $item['score'] ?? null ) ? (float) $item['score'] : null,
				'severity'                => sanitize_key( (string) ( $item['severity'] ?? '' ) ),
				'reason_codes'            => array_values( array_map( 'sanitize_key', (array) ( $item['reason_codes'] ?? array() ) ) ),
				'evidence_summary'        => sanitize_textarea_field( (string) ( $item['evidence_summary'] ?? '' ) ),
				'recommended_next_action' => sanitize_key( (string) ( $item['recommended_next_action'] ?? '' ) ),
				'direct_wordpress_write'  => false,
			);
		}

		return array_slice( $items, 0, 5 );
	}

	/**
	 * Validates a Toolkit article audio adoption plan.
	 *
	 * @param array<string,mixed> $plan Plan data.
	 * @return true|WP_Error
	 */
	private function validate_article_audio_adoption_plan_contract( array $plan ) {
		if ( 'article_audio_adoption_plan.v1' !== (string) ( $plan['artifact_type'] ?? '' ) ) {
			return new WP_Error(
				'npcink_governance_core_article_audio_plan_invalid',
				__( 'Article audio adoption plans must declare artifact_type=article_audio_adoption_plan.v1.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		if ( true === (bool) ( $plan['direct_wordpress_write'] ?? false ) ) {
			return new WP_Error(
				'npcink_governance_core_article_audio_direct_write_rejected',
				__( 'Article audio adoption plans must not claim direct WordPress write authority.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		$write_actions = is_array( $plan['write_actions'] ?? null ) ? array_values( $plan['write_actions'] ) : array();
		if ( 1 !== count( $write_actions ) || ! is_array( $write_actions[0] ?? null ) ) {
			return new WP_Error(
				'npcink_governance_core_article_audio_action_count_rejected',
				__( 'Article audio adoption plans must contain exactly one adoption action.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		$action = $write_actions[0];
		if ( 'npcink-abilities-toolkit/adopt-article-audio' !== sanitize_text_field( (string) ( $action['target_ability_id'] ?? '' ) ) ) {
			return new WP_Error(
				'npcink_governance_core_article_audio_target_rejected',
				__( 'Article audio adoption plans may target only adopt-article-audio.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		if ( true !== (bool) ( $action['requires_approval'] ?? false ) || false !== (bool) ( $action['commit_execution'] ?? true ) ) {
			return new WP_Error(
				'npcink_governance_core_article_audio_action_contract_rejected',
				__( 'Article audio adoption actions must require approval and must not claim commit execution.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		$input = is_array( $action['input'] ?? null ) ? $action['input'] : array();
		if ( absint( $input['post_id'] ?? 0 ) <= 0 || '' === esc_url_raw( (string) ( $input['audio_url'] ?? '' ) ) ) {
			return new WP_Error(
				'npcink_governance_core_article_audio_input_rejected',
				__( 'Article audio adoption input must include post_id and audio_url.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		if ( false === (bool) ( $input['dry_run'] ?? true ) || true === (bool) ( $input['commit'] ?? false ) ) {
			return new WP_Error(
				'npcink_governance_core_article_audio_commit_rejected',
				__( 'Article audio adoption action input must remain dry-run and must not request commit.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		$allowed_input_keys = array_fill_keys(
			array( 'post_id', 'audio_url', 'audio_title', 'audio_kind', 'duration_seconds', 'mime_type', 'source_content_hash', 'source_word_count', 'source_generated_at', 'provider', 'model', 'trace_id', 'import_media', 'media_file_name', 'dry_run', 'commit', 'idempotency_key' ),
			true
		);
		foreach ( array_keys( $input ) as $key ) {
			if ( ! isset( $allowed_input_keys[ (string) $key ] ) ) {
				return new WP_Error(
					'npcink_governance_core_article_audio_input_key_rejected',
					__( 'Article audio adoption input includes an unsupported field.', 'npcink-governance-core' ),
					array(
						'status' => 422,
						'field'  => (string) $key,
					)
				);
			}
		}

		return true;
	}

}
