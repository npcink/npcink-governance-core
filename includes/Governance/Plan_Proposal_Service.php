<?php
/**
 * Plan-to-proposal governance bridge.
 *
 * @package NpcinkGovernanceCore
 */

namespace Npcink\GovernanceCore\Governance;

use Npcink\GovernanceCore\Audit\Audit_Log_Repository;
use Npcink\GovernanceCore\Capabilities\Ability_Registry_Adapter;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Converts read-only planning ability output into Core proposals.
 */
final class Plan_Proposal_Service {
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
		'npcink-toolbox/build-image-candidate-adoption-plan'                 => true,
		'npcink-toolbox/build-site-knowledge-review-plan'                    => true,
		'npcink-toolbox/build-content-metadata-apply-plan'                   => true,
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
	 * Ability adapter.
	 *
	 * @var Ability_Registry_Adapter
	 */
	private $abilities;

	/**
	 * Proposal service.
	 *
	 * @var Proposal_Service
	 */
	private $proposals;

	/**
	 * Audit repository.
	 *
	 * @var Audit_Log_Repository
	 */
	private $audit;

	/**
	 * Constructor.
	 *
	 * @param Ability_Registry_Adapter $abilities Ability adapter.
	 * @param Proposal_Service         $proposals Proposal service.
	 * @param Audit_Log_Repository     $audit Audit repository.
	 */
	public function __construct( Ability_Registry_Adapter $abilities, Proposal_Service $proposals, Audit_Log_Repository $audit ) {
		$this->abilities = $abilities;
		$this->proposals = $proposals;
		$this->audit     = $audit;
	}

	/**
	 * Creates proposal records from a read-only plan output.
	 *
	 * @param string              $plan_ability_id Plan ability id.
	 * @param array<string,mixed> $plan_payload Plan output or success envelope.
	 * @param array<string,mixed> $plan_input Input used to generate the plan.
	 * @param array<string,mixed> $caller Caller metadata.
	 * @return array<string,mixed>|WP_Error
	 */
	public function create_from_plan( string $plan_ability_id, array $plan_payload, array $plan_input = array(), array $caller = array() ) {
		$plan_ability_id = sanitize_text_field( $plan_ability_id );
		if ( ! isset( $this->allowed_plan_abilities[ $plan_ability_id ] ) ) {
			return new WP_Error(
				'npcink_governance_core_plan_ability_not_allowed',
				__( 'This planning ability is not accepted by the Core plan-to-proposal bridge.', 'npcink-governance-core' ),
				array( 'status' => 400 )
			);
		}

		$plan_capability = $this->abilities->find( $plan_ability_id );
		if ( null === $plan_capability ) {
			return new WP_Error(
				'npcink_governance_core_plan_ability_unavailable',
				__( 'The planning ability is not currently discoverable.', 'npcink-governance-core' ),
				array( 'status' => 404 )
			);
		}

		if ( 'direct_read' !== (string) ( $plan_capability['governance_mode'] ?? '' ) || 'wp_abilities_rest' !== (string) ( $plan_capability['execution_surface'] ?? '' ) ) {
			return new WP_Error(
				'npcink_governance_core_plan_ability_not_read_only',
				__( 'Plan-to-proposal intake accepts only direct-read planning abilities.', 'npcink-governance-core' ),
				array( 'status' => 409 )
			);
		}

		$plan = $this->unwrap_plan_payload( $plan_payload );
		if ( is_wp_error( $plan ) ) {
			return $plan;
		}

		$contract_error = $this->validate_plan_contract( $plan );
		if ( is_wp_error( $contract_error ) ) {
			return $contract_error;
		}

		if ( 'npcink-toolbox/build-article-write-plan' === $plan_ability_id ) {
			$article_contract_error = $this->validate_article_write_plan_contract( $plan );
			if ( is_wp_error( $article_contract_error ) ) {
				return $article_contract_error;
			}
		}

		if ( 'npcink-toolbox/build-article-batch-write-plan' === $plan_ability_id ) {
			$article_batch_contract_error = $this->validate_article_batch_write_plan_contract( $plan );
			if ( is_wp_error( $article_batch_contract_error ) ) {
				return $article_batch_contract_error;
			}
		}

		if ( 'npcink-toolbox/build-article-media-batch-write-plan' === $plan_ability_id ) {
			$article_media_batch_contract_error = $this->validate_article_media_batch_write_plan_contract( $plan );
			if ( is_wp_error( $article_media_batch_contract_error ) ) {
				return $article_media_batch_contract_error;
			}
		}

		if ( 'npcink-toolbox/build-image-candidate-adoption-plan' === $plan_ability_id ) {
			$image_candidate_contract_error = $this->validate_image_candidate_adoption_plan_contract( $plan );
			if ( is_wp_error( $image_candidate_contract_error ) ) {
				return $image_candidate_contract_error;
			}
		}

		if ( 'npcink-toolbox/build-site-knowledge-review-plan' === $plan_ability_id ) {
			$site_knowledge_contract_error = $this->validate_site_knowledge_review_plan_contract( $plan );
			if ( is_wp_error( $site_knowledge_contract_error ) ) {
				return $site_knowledge_contract_error;
			}
		}

		if ( 'npcink-toolbox/build-content-metadata-apply-plan' === $plan_ability_id ) {
			$content_metadata_contract_error = $this->validate_content_metadata_apply_plan_contract( $plan );
			if ( is_wp_error( $content_metadata_contract_error ) ) {
				return $content_metadata_contract_error;
			}
		}

		if ( 'npcink-abilities-toolkit/build-media-optimization-plan' === $plan_ability_id ) {
			$media_optimization_contract_error = $this->validate_media_optimization_plan_contract( $plan );
			if ( is_wp_error( $media_optimization_contract_error ) ) {
				return $media_optimization_contract_error;
			}
		}

		if ( 'npcink-abilities-toolkit/build-media-adoption-enhancement-plan' === $plan_ability_id ) {
			$media_adoption_enhancement_contract_error = $this->validate_media_adoption_enhancement_plan_contract( $plan );
			if ( is_wp_error( $media_adoption_enhancement_contract_error ) ) {
				return $media_adoption_enhancement_contract_error;
			}
		}

		if ( 'npcink-abilities-toolkit/build-media-rename-plan' === $plan_ability_id ) {
			$media_rename_contract_error = $this->validate_media_rename_plan_contract( $plan );
			if ( is_wp_error( $media_rename_contract_error ) ) {
				return $media_rename_contract_error;
			}
		}

		if ( 'npcink-abilities-toolkit/build-article-optimization-apply-plan' === $plan_ability_id ) {
			$article_optimization_contract_error = $this->validate_article_optimization_apply_plan_contract( $plan );
			if ( is_wp_error( $article_optimization_contract_error ) ) {
				return $article_optimization_contract_error;
			}
		}

		if ( 'npcink-abilities-toolkit/build-article-block-plan' === $plan_ability_id ) {
			$article_block_contract_error = $this->validate_article_block_plan_contract( $plan );
			if ( is_wp_error( $article_block_contract_error ) ) {
				return $article_block_contract_error;
			}
		}

		if ( 'npcink-abilities-toolkit/build-pattern-page-plan' === $plan_ability_id ) {
			$pattern_page_contract_error = $this->validate_pattern_page_plan_contract( $plan );
			if ( is_wp_error( $pattern_page_contract_error ) ) {
				return $pattern_page_contract_error;
			}
		}

		if ( 'npcink-abilities-toolkit/build-block-theme-site-plan' === $plan_ability_id ) {
			$block_theme_contract_error = $this->validate_block_theme_site_plan_contract( $plan );
			if ( is_wp_error( $block_theme_contract_error ) ) {
				return $block_theme_contract_error;
			}
		}

		$write_actions = is_array( $plan['write_actions'] ?? null ) ? array_values( $plan['write_actions'] ) : array();
		$preview_rows  = is_array( $plan['preview'] ?? null ) ? array_values( $plan['preview'] ) : array();
		$manual_review = is_array( $plan['manual_review'] ?? null ) ? array_values( $plan['manual_review'] ) : array();
		$skipped_destructive = is_array( $plan['skipped_destructive_candidates'] ?? null ) ? array_values( $plan['skipped_destructive_candidates'] ) : array();
		$batch_id      = sanitize_text_field( (string) ( $plan['batch_id'] ?? '' ) );
		$issue_types   = array_values( array_map( 'sanitize_key', (array) ( $plan['issue_types'] ?? array() ) ) );
		$plan_risk     = $this->risk_level( $plan['risk'] ?? array() );

		$created       = array();
		$blocked_items = array();
		$needs_input   = array();
		$warnings      = $this->plan_warnings( $manual_review, $skipped_destructive );
		$items         = array();

		foreach ( $write_actions as $index => $raw_action ) {
			if ( ! is_array( $raw_action ) ) {
				$blocked_items[] = array(
					'index'  => $index,
					'reason' => 'Write action is not an object.',
				);
				continue;
			}

			$item = $this->proposal_payload_for_action(
				$plan_ability_id,
				$plan,
				$plan_input,
				$caller,
				$raw_action,
				$index,
				$preview_rows,
				$manual_review,
				$skipped_destructive,
				$plan_risk
			);

			if ( is_wp_error( $item ) ) {
				$blocked_items[] = array_merge(
					array(
						'index'     => $index,
						'action_id' => sanitize_key( (string) ( $raw_action['action_id'] ?? '' ) ),
					),
					(array) $item->get_error_data()
				);
				continue;
			}

			$items[] = $item;
		}

		if ( $this->plan_requires_batch_proposal( $plan, $write_actions ) ) {
			if ( empty( $blocked_items ) && ! empty( $items ) ) {
				$batch_item = $this->batch_proposal_payload_for_actions( $plan_ability_id, $plan, $caller, $items, $warnings );
				$proposal   = $this->proposals->create( $batch_item );
				if ( is_wp_error( $proposal ) ) {
					$blocked_items[] = array(
						'index'  => 0,
						'code'   => $proposal->get_error_code(),
						'reason' => $proposal->get_error_message(),
					);
				} else {
					if ( ! empty( $proposal['preview']['needs_input'] ?? array() ) ) {
						$needs_input[] = array(
							'proposal_id' => (string) $proposal['proposal_id'],
							'action_id'   => 'batch',
							'fields'      => (array) ( $proposal['preview']['needs_input'] ?? array() ),
						);
					}
					$created[] = $proposal;
				}
			}
		} else {
			foreach ( $items as $item ) {
				$proposal = $this->proposals->create( $item );
				if ( is_wp_error( $proposal ) ) {
					$blocked_items[] = array(
						'index'     => absint( $item['preview']['action_index'] ?? 0 ),
						'action_id' => sanitize_key( (string) ( $item['preview']['action_id'] ?? '' ) ),
						'code'      => $proposal->get_error_code(),
						'reason'    => $proposal->get_error_message(),
					);
					continue;
				}

				if ( ! empty( $proposal['preview']['needs_input'] ?? array() ) ) {
					$needs_input[] = array(
						'proposal_id' => (string) $proposal['proposal_id'],
						'action_id'   => (string) ( $proposal['preview']['action_id'] ?? '' ),
						'fields'      => (array) ( $proposal['preview']['needs_input'] ?? array() ),
					);
				}

				$created[] = $proposal;
			}
		}

		$this->audit->record(
			'proposal.plan_ingested',
			array(
				'plan_ability_id' => $plan_ability_id,
				'batch_id'        => $batch_id,
				'action_count'    => count( $write_actions ),
				'proposal_count'  => count( $created ),
				'blocked_count'   => count( $blocked_items ),
				'needs_input_count' => count( $needs_input ),
				'commit_execution' => false,
			)
		);

		return array(
			'plan_ability_id'  => $plan_ability_id,
			'batch_id'         => $batch_id,
			'issue_types'      => $issue_types,
			'risk'             => $this->sanitize_payload( $plan['risk'] ?? array() ),
			'requires_approval' => true,
			'dry_run'          => true,
			'commit_execution' => false,
			'action_count'     => count( $write_actions ),
			'proposal_count'   => count( $created ),
			'proposal_ready_count' => count(
				array_filter(
					$created,
					static function ( array $proposal ): bool {
						return true === (bool) ( $proposal['preview']['proposal_ready'] ?? false );
					}
				)
			),
			'proposals'        => $created,
			'warnings'         => $warnings,
			'blocked_items'    => $blocked_items,
			'needs_input'      => $needs_input,
		);
	}

	/**
	 * Returns plan payload data from an ability success envelope.
	 *
	 * @param array<string,mixed> $payload Raw payload.
	 * @return array<string,mixed>|WP_Error
	 */
	private function unwrap_plan_payload( array $payload ) {
		if ( array_key_exists( 'success', $payload ) && true !== (bool) $payload['success'] ) {
			return new WP_Error(
				'npcink_governance_core_plan_unsuccessful',
				__( 'Only successful plan outputs can be converted into proposals.', 'npcink-governance-core' ),
				array( 'status' => 422 )
			);
		}

		$data = is_array( $payload['data'] ?? null ) ? $payload['data'] : $payload;
		if ( ! is_array( $data ) ) {
			return new WP_Error(
				'npcink_governance_core_plan_invalid',
				__( 'Plan payload must be an object.', 'npcink-governance-core' ),
				array( 'status' => 400 )
			);
		}

		return $data;
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
	 * Validates a Toolbox content metadata apply plan from reviewed editor choices.
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
		$layout_profile   = sanitize_key( (string) ( $plan['layout_profile'] ?? '' ) );
		$layout_contract  = is_array( $plan['template_layout_contract'] ?? null ) ? $plan['template_layout_contract'] : array();
		$contract_status  = sanitize_key( (string) ( $layout_contract['contract_status'] ?? '' ) );
		$contract_model   = sanitize_key( (string) ( $layout_contract['placement_model'] ?? '' ) );
		$profile_rows     = is_array( $layout_contract['profiles'] ?? null ) ? array_values( $layout_contract['profiles'] ) : array();

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
		if ( 'pass' !== $contract_status || 'bounded_template_layout_profile' !== $contract_model ) {
			return new WP_Error(
				'npcink_governance_core_block_theme_site_layout_contract_rejected',
				__( 'Block theme layout plans must include a passing bounded template layout contract.', 'npcink-governance-core' ),
				array(
					'status'          => 422,
					'contract_status' => $contract_status,
					'contract_model'  => $contract_model,
				)
			);
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
	private function sanitize_block_class_name( $class_name ): string {
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
	private function article_workflow_artifact_keys(): array {
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
	 * Builds preview context for article write plans.
	 *
	 * @param array<string,mixed> $plan Plan data.
	 * @return array<string,mixed>
	 */
	private function article_workflow_preview( array $plan ): array {
		$preview = array(
			'artifact_type'          => sanitize_key( (string) ( $plan['artifact_type'] ?? ( $plan['plan_type'] ?? '' ) ) ),
			'version'                => absint( $plan['version'] ?? 0 ),
			'final_write_path'       => 'core_proposal_required',
			'direct_wordpress_write' => false,
		);

		foreach ( $this->article_workflow_artifact_keys() as $artifact_key ) {
			$preview[ $artifact_key ] = $this->sanitize_payload( $plan[ $artifact_key ] ?? array() );
		}

		return $preview;
	}

	/**
	 * Builds preview context for bounded article batch plans.
	 *
	 * @param array<string,mixed> $plan Plan data.
	 * @return array<string,mixed>
	 */
	private function article_batch_workflow_preview( array $plan ): array {
		$articles = is_array( $plan['articles'] ?? null ) ? array_values( $plan['articles'] ) : array();

		return array(
			'artifact_type'          => sanitize_key( (string) ( $plan['artifact_type'] ?? ( $plan['plan_type'] ?? '' ) ) ),
			'version'                => absint( $plan['version'] ?? 0 ),
			'max_actions'            => self::ARTICLE_BATCH_MAX_ACTIONS,
			'article_count'          => count( $articles ),
			'final_write_path'       => 'core_batch_proposal_required',
			'direct_wordpress_write' => false,
			'articles'               => $this->sanitize_payload( $articles ),
		);
	}

	/**
	 * Builds preview context for article media batch plans.
	 *
	 * @param array<string,mixed> $plan Plan data.
	 * @return array<string,mixed>
	 */
	private function article_media_batch_workflow_preview( array $plan ): array {
		$articles       = is_array( $plan['articles'] ?? null ) ? array_values( $plan['articles'] ) : array();
		$media_workflow = is_array( $plan['media_workflow'] ?? null ) ? array_values( $plan['media_workflow'] ) : array();

		return array(
			'artifact_type'          => sanitize_key( (string) ( $plan['artifact_type'] ?? ( $plan['plan_type'] ?? '' ) ) ),
			'version'                => absint( $plan['version'] ?? 0 ),
			'max_articles'           => self::ARTICLE_MEDIA_BATCH_MAX_ARTICLES,
			'max_actions'            => self::ARTICLE_MEDIA_BATCH_MAX_ACTIONS,
			'article_count'          => count( $articles ),
			'media_workflow'         => $this->sanitize_payload( $media_workflow ),
			'final_write_path'       => 'core_batch_proposal_required',
			'direct_wordpress_write' => false,
			'articles'               => $this->sanitize_payload( $articles ),
		);
	}

	/**
	 * Builds preview context for media optimization plans.
	 *
	 * @param array<string,mixed> $plan Plan data.
	 * @return array<string,mixed>
	 */
	private function media_optimization_preview( array $plan ): array {
		return array(
			'artifact_type'          => sanitize_key( (string) ( $plan['artifact_type'] ?? ( $plan['plan_type'] ?? '' ) ) ),
			'version'                => absint( $plan['version'] ?? 0 ),
			'optimization_goal'      => sanitize_key( (string) ( $plan['optimization_goal'] ?? 'media_optimization' ) ),
			'attachment_id'          => absint( $plan['attachment_id'] ?? 0 ),
			'derivative_preview'     => $this->sanitize_payload( $plan['derivative_preview'] ?? array() ),
			'metadata_preview'       => $this->sanitize_payload( $plan['metadata_preview'] ?? array() ),
			'final_write_path'       => 'core_batch_proposal_required',
			'direct_wordpress_write' => false,
		);
	}

	/**
	 * Builds preview context for media adoption enhancement plans.
	 *
	 * @param array<string,mixed> $plan Plan data.
	 * @return array<string,mixed>
	 */
	private function media_adoption_enhancement_preview( array $plan ): array {
		$summary = is_array( $plan['summary'] ?? null ) ? $plan['summary'] : array();
		$media   = is_array( $plan['media'] ?? null ) ? $plan['media'] : array();
		$reference_repair = is_array( $plan['reference_repair'] ?? null ) ? $plan['reference_repair'] : array();

		return array(
			'artifact_type'          => sanitize_key( (string) ( $plan['artifact_type'] ?? ( $plan['plan_type'] ?? '' ) ) ),
			'version'                => absint( $plan['version'] ?? 0 ),
			'post_id'                => absint( $plan['post_id'] ?? ( $reference_repair['post_id'] ?? 0 ) ),
			'attach_to_post_id'      => absint( $plan['attach_to_post_id'] ?? ( $media['attach_to_post_id'] ?? 0 ) ),
			'source_url'             => esc_url_raw( (string) ( $plan['source_url'] ?? ( $media['url'] ?? '' ) ) ),
			'old_url'                => esc_url_raw( (string) ( $plan['old_url'] ?? ( $reference_repair['old_url'] ?? '' ) ) ),
			'action_count'           => absint( $summary['action_count'] ?? ( $plan['action_count'] ?? 0 ) ),
			'reference_repair'       => $this->sanitize_payload( $reference_repair ),
			'final_write_path'       => 'core_batch_proposal_required',
			'direct_wordpress_write' => false,
		);
	}

	/**
	 * Builds preview context for media rename plans.
	 *
	 * @param array<string,mixed> $plan Plan data.
	 * @return array<string,mixed>
	 */
	private function media_rename_preview( array $plan ): array {
		return array(
			'artifact_type'          => sanitize_key( (string) ( $plan['artifact_type'] ?? ( $plan['plan_type'] ?? '' ) ) ),
			'version'                => absint( $plan['version'] ?? 0 ),
			'attachment_id'          => absint( $plan['attachment_id'] ?? 0 ),
			'preview'                => $this->sanitize_payload( $plan['preview'] ?? array() ),
			'final_write_path'       => 'core_proposal_required',
			'direct_wordpress_write' => false,
		);
	}

	/**
	 * Builds preview context for existing article optimization apply plans.
	 *
	 * @param array<string,mixed> $plan Plan data.
	 * @return array<string,mixed>
	 */
	private function article_optimization_preview( array $plan ): array {
		$post    = is_array( $plan['post'] ?? null ) ? $plan['post'] : array();
		$summary = is_array( $plan['summary'] ?? null ) ? $plan['summary'] : array();

		return array(
			'artifact_type'          => sanitize_key( (string) ( $plan['artifact_type'] ?? ( $plan['plan_type'] ?? '' ) ) ),
			'version'                => absint( $plan['version'] ?? 0 ),
			'post_id'                => absint( $post['post_id'] ?? 0 ),
			'source_recipe_ref'      => sanitize_text_field( (string) ( $plan['source_recipe_ref'] ?? '' ) ),
			'safe_apply_supported'   => array_values( array_map( 'sanitize_key', (array) ( $summary['safe_apply_supported'] ?? array() ) ) ),
			'advisory_sections'      => array_values( array_map( 'sanitize_key', (array) ( $summary['advisory_sections'] ?? array() ) ) ),
			'final_write_path'       => 'core_proposal_required',
			'direct_wordpress_write' => false,
		);
	}

	/**
	 * Builds preview context for Gutenberg article block plans.
	 *
	 * @param array<string,mixed> $plan Plan data.
	 * @return array<string,mixed>
	 */
	private function article_block_preview( array $plan ): array {
		$summary = is_array( $plan['summary'] ?? null ) ? $plan['summary'] : array();

		return array(
			'artifact_type'          => sanitize_key( (string) ( $plan['artifact_type'] ?? ( $plan['plan_type'] ?? '' ) ) ),
			'version'                => absint( $plan['version'] ?? 0 ),
			'article_template'       => sanitize_key( (string) ( $plan['article_template'] ?? '' ) ),
			'responsive_profile'     => sanitize_key( (string) ( $plan['responsive_profile'] ?? '' ) ),
			'media_strategy'         => sanitize_key( (string) ( $plan['media_strategy'] ?? '' ) ),
			'block_count'            => absint( $summary['block_count'] ?? 0 ),
			'action_count'           => absint( $summary['action_count'] ?? 0 ),
			'editorial_quality'      => $this->sanitize_payload( $plan['editorial_quality'] ?? array() ),
			'responsive_quality'     => $this->sanitize_payload( $plan['responsive_quality'] ?? array() ),
			'final_write_path'       => 'core_batch_proposal_required',
			'direct_wordpress_write' => false,
		);
	}

	/**
	 * Builds preview context for Gutenberg pattern page plans.
	 *
	 * @param array<string,mixed> $plan Plan data.
	 * @return array<string,mixed>
	 */
	private function pattern_page_preview( array $plan ): array {
		$summary = is_array( $plan['summary'] ?? null ) ? $plan['summary'] : array();

		return array(
			'artifact_type'          => sanitize_key( (string) ( $plan['artifact_type'] ?? ( $plan['plan_type'] ?? '' ) ) ),
			'version'                => absint( $plan['version'] ?? 0 ),
			'pattern_id'             => sanitize_key( (string) ( $plan['pattern_id'] ?? '' ) ),
			'style_preset'           => sanitize_key( (string) ( $plan['style_preset'] ?? '' ) ),
			'block_count'            => absint( $summary['block_count'] ?? 0 ),
			'action_count'           => absint( $summary['action_count'] ?? 0 ),
			'allowed_classes'        => array_values(
				array_filter(
					array_map( array( $this, 'sanitize_block_class_name' ), (array) ( $plan['allowed_classes'] ?? array() ) )
				)
			),
			'final_write_path'       => 'core_batch_proposal_required',
			'direct_wordpress_write' => false,
		);
	}

	/**
	 * Builds preview context for block theme site plans.
	 *
	 * @param array<string,mixed> $plan Plan data.
	 * @return array<string,mixed>
	 */
	private function block_theme_site_preview( array $plan ): array {
		$summary      = is_array( $plan['summary'] ?? null ) ? $plan['summary'] : array();
		$active_theme = is_array( $plan['active_theme'] ?? null ) ? $plan['active_theme'] : array();

		return array(
			'artifact_type'            => sanitize_key( (string) ( $plan['artifact_type'] ?? ( $plan['plan_type'] ?? '' ) ) ),
			'version'                  => absint( $plan['version'] ?? 0 ),
			'intent'                   => sanitize_key( (string) ( $plan['intent'] ?? '' ) ),
			'layout_profile'           => sanitize_key( (string) ( $plan['layout_profile'] ?? '' ) ),
			'active_theme'             => $this->sanitize_payload( $active_theme ),
			'affected_templates'       => array_values( array_map( 'sanitize_key', (array) ( $plan['affected_templates'] ?? array() ) ) ),
			'template_layout_contract' => is_array( $plan['template_layout_contract'] ?? null ) ? $this->sanitize_payload( $plan['template_layout_contract'] ) : array(),
			'block_count'              => absint( $summary['block_count'] ?? 0 ),
			'action_count'             => absint( $summary['action_count'] ?? 0 ),
			'file_template_write_mode' => 'create_wp_template_override',
			'final_write_path'         => 'core_batch_proposal_required',
			'direct_wordpress_write'   => false,
		);
	}

	/**
	 * Builds preview context for Site Knowledge review plans.
	 *
	 * @param array<string,mixed> $plan Plan data.
	 * @return array<string,mixed>
	 */
	private function site_knowledge_review_preview( array $plan ): array {
		return array(
			'artifact_type'          => sanitize_key( (string) ( $plan['artifact_type'] ?? ( $plan['plan_type'] ?? '' ) ) ),
			'version'                => absint( $plan['version'] ?? 0 ),
			'agent_id'               => sanitize_key( (string) ( $plan['agent_id'] ?? '' ) ),
			'agent_version'          => sanitize_text_field( (string) ( $plan['agent_version'] ?? '' ) ),
			'workflow'               => sanitize_key( (string) ( $plan['workflow'] ?? '' ) ),
			'intent'                 => sanitize_key( (string) ( $plan['intent'] ?? '' ) ),
			'local_next_action'      => sanitize_key( (string) ( $plan['local_next_action'] ?? '' ) ),
			'evidence_gate_status'   => sanitize_key( (string) ( $plan['evidence_gate_status'] ?? '' ) ),
			'evidence_refs'          => $this->sanitize_payload( $plan['evidence_refs'] ?? array() ),
			'blocked_outputs'        => $this->sanitize_payload( $plan['blocked_outputs'] ?? array() ),
			'final_write_path'       => 'core_proposal_required',
			'direct_wordpress_write' => false,
		);
	}

	/**
	 * Builds preview context for content metadata apply plans.
	 *
	 * @param array<string,mixed> $plan Plan data.
	 * @return array<string,mixed>
	 */
	private function content_metadata_apply_preview( array $plan ): array {
		$post = is_array( $plan['post'] ?? null ) ? $plan['post'] : array();

		return array(
			'artifact_type'          => sanitize_key( (string) ( $plan['artifact_type'] ?? ( $plan['plan_type'] ?? '' ) ) ),
			'version'                => absint( $plan['version'] ?? 0 ),
			'post_id'                => absint( $post['post_id'] ?? ( $plan['target_post_id'] ?? 0 ) ),
			'accepted_choices'       => $this->sanitize_payload( $plan['accepted_choices'] ?? array() ),
			'evidence_refs'          => $this->sanitize_payload( $plan['evidence_refs'] ?? array() ),
			'classification_evidence' => $this->sanitize_payload( $plan['authorization'] ?? array() ),
			'new_term_candidate_count' => count( is_array( $plan['new_term_candidates'] ?? null ) ? $plan['new_term_candidates'] : array() ),
			'final_write_path'       => 'core_batch_proposal_required',
			'direct_wordpress_write' => false,
		);
	}

	/**
	 * Returns plan-level block editor review metadata for proposal previews.
	 *
	 * These fields are advisory review evidence only. They do not grant write
	 * authority or change Core approval/preflight behavior.
	 *
	 * @param array<string,mixed> $plan Plan payload.
	 * @return array<string,mixed>
	 */
	private function block_editor_quality_preview( array $plan ): array {
		$preview = array();

		foreach ( array( 'block_editor_quality_gate', 'block_editor_review', 'block_editor_reviews' ) as $key ) {
			if ( is_array( $plan[ $key ] ?? null ) ) {
				$preview[ $key ] = $this->sanitize_payload( $plan[ $key ] );
			}
		}

		return $preview;
	}

	/**
	 * Builds one Proposal_Service payload for a write action.
	 *
	 * @param string              $plan_ability_id Plan ability id.
	 * @param array<string,mixed> $plan Plan data.
	 * @param array<string,mixed> $plan_input Plan input.
	 * @param array<string,mixed> $caller Caller metadata.
	 * @param array<string,mixed> $action Write action.
	 * @param int                 $index Action index.
	 * @param array<int,mixed>    $preview_rows Plan preview rows.
	 * @param array<int,mixed>    $manual_review Manual review rows.
	 * @param array<int,mixed>    $skipped_destructive Skipped destructive candidates.
	 * @param string              $plan_risk Plan risk level.
	 * @return array<string,mixed>|WP_Error
	 */
	private function proposal_payload_for_action( string $plan_ability_id, array $plan, array $plan_input, array $caller, array $action, int $index, array $preview_rows, array $manual_review, array $skipped_destructive, string $plan_risk ) {
		$target_ability_id = sanitize_text_field( (string) ( $action['target_ability_id'] ?? '' ) );
		$action_id         = sanitize_key( (string) ( $action['action_id'] ?? ( 'plan_action_' . ( $index + 1 ) ) ) );
		if ( '' === $target_ability_id || false === strpos( $target_ability_id, '/' ) ) {
			return $this->blocked_error( 'missing_target_ability', 'Write action target_ability_id is missing or invalid.' );
		}

		if ( true !== (bool) ( $action['requires_approval'] ?? false ) ) {
			return $this->blocked_error( 'action_requires_approval_missing', 'Write action must require approval before proposal intake.', array( 'action_id' => $action_id ) );
		}

		if ( false !== (bool) ( $action['commit_execution'] ?? true ) ) {
			return $this->blocked_error( 'action_commit_execution_rejected', 'Write action must not claim commit execution already happened.', array( 'action_id' => $action_id ) );
		}

		$target = $this->abilities->find( $target_ability_id );
		if ( null === $target ) {
			return $this->blocked_error( 'target_ability_unavailable', 'Write action target ability is not currently discoverable.', array( 'target_ability_id' => $target_ability_id ) );
		}

		if ( 'proposal_required' !== (string) ( $target['governance_mode'] ?? '' ) || true !== (bool) ( $target['requires_approval'] ?? false ) ) {
			return $this->blocked_error( 'target_ability_not_governed', 'Write action target ability is not governed by Core proposals.', array( 'target_ability_id' => $target_ability_id ) );
		}

		if ( true === (bool) ( $target['core_proxy_execute'] ?? false ) || true === (bool) ( $target['commit_execution'] ?? false ) ) {
			return $this->blocked_error( 'target_ability_execution_not_allowed', 'Target ability guidance unexpectedly allows Core execution.', array( 'target_ability_id' => $target_ability_id ) );
		}

		$input = is_array( $action['input'] ?? null ) ? $action['input'] : array();
		if ( false === (bool) ( $input['dry_run'] ?? true ) ) {
			return $this->blocked_error( 'action_not_dry_run', 'Write action input must stay in dry-run mode.', array( 'action_id' => $action_id ) );
		}
		if ( true === (bool) ( $input['commit'] ?? false ) ) {
			return $this->blocked_error( 'action_commit_rejected', 'Write action input must not request commit execution.', array( 'action_id' => $action_id ) );
		}
		$input['dry_run'] = true;
		$input['commit']  = false;

		if ( 'npcink-abilities-toolkit/delete-media-permanently' === $target_ability_id && ! $this->include_delete_candidates( $plan, $plan_input ) ) {
			return $this->blocked_error( 'destructive_media_delete_not_explicitly_included', 'Permanent media deletion is excluded unless include_delete_candidates=true is present in the plan input.', array( 'target_ability_id' => $target_ability_id ) );
		}

		$requires_input = array_values( array_map( 'sanitize_key', (array) ( $action['requires_input'] ?? array() ) ) );
		$risk           = $this->action_risk( $action, $target, $plan_risk );
		$matched_preview = $this->matching_preview_row( $preview_rows, $input );
		$proposal_ready = array_key_exists( 'proposal_ready', $action ) ? (bool) $action['proposal_ready'] : empty( $requires_input );
		$preflight_blockers = array();
		if ( ! empty( $requires_input ) ) {
			$preflight_blockers[] = array(
				'code'          => 'requires_input',
				'fields'        => $requires_input,
				'reason'        => 'The plan action requires human input before commit preflight can pass.',
				'proposal_ready' => false,
			);
		}
		if ( ! $proposal_ready && empty( $preflight_blockers ) ) {
			$preflight_blockers[] = array(
				'code'           => 'proposal_not_ready',
				'reason'         => 'The plan action marks this item as not ready for commit preflight.',
				'proposal_ready' => false,
			);
		}

		$preview = array(
			'source' => array(
				'type'            => 'plan_to_proposal',
				'plan_ability_id' => $plan_ability_id,
				'batch_id'        => sanitize_text_field( (string) ( $plan['batch_id'] ?? '' ) ),
				'issue_types'     => array_values( array_map( 'sanitize_key', (array) ( $plan['issue_types'] ?? array() ) ) ),
			),
			'action_id'          => $action_id,
			'action_index'       => $index,
			'target_ability_id'  => $target_ability_id,
			'before'             => is_array( $matched_preview['before'] ?? null ) ? $matched_preview['before'] : array(),
			'after_suggestion'   => is_array( $matched_preview['after_suggestion'] ?? null ) ? $matched_preview['after_suggestion'] : array(),
			'reason'             => sanitize_textarea_field( (string) ( $action['reason'] ?? '' ) ),
			'depends_on'         => array_values( array_map( 'sanitize_key', (array) ( $action['depends_on'] ?? array() ) ) ),
			'risk'               => array(
				'level'            => $risk,
				'plan_level'       => $plan_risk,
				'target_risk_level' => sanitize_key( (string) ( $target['risk_level'] ?? '' ) ),
			),
			'required_scopes'     => array_values( array_map( 'sanitize_key', (array) ( $action['required_scopes'] ?? array() ) ) ),
			'requires_approval'   => true,
			'dry_run'             => true,
			'commit'              => false,
			'commit_execution'    => false,
			'proposal_ready'      => $proposal_ready,
			'needs_input'         => $requires_input,
			'warnings'            => $this->plan_warnings( $manual_review, $skipped_destructive ),
			'blocked_items'       => array(
				'manual_review'                  => $this->sanitize_payload( $manual_review ),
				'skipped_destructive_candidates' => $this->sanitize_payload( $skipped_destructive ),
			),
			'preflight_blockers'  => $preflight_blockers,
			'plan_preview_row'    => $matched_preview,
		);

		if ( 'npcink-toolbox/build-article-write-plan' === $plan_ability_id ) {
			$preview['article_workflow'] = $this->article_workflow_preview( $plan );
		}
		if ( 'npcink-toolbox/build-article-batch-write-plan' === $plan_ability_id ) {
			$preview['article_batch_workflow'] = $this->article_batch_workflow_preview( $plan );
		}
		if ( 'npcink-toolbox/build-article-media-batch-write-plan' === $plan_ability_id ) {
			$preview['article_media_batch_workflow'] = $this->article_media_batch_workflow_preview( $plan );
		}
		if ( 'npcink-abilities-toolkit/build-media-optimization-plan' === $plan_ability_id ) {
			$preview['media_optimization'] = $this->media_optimization_preview( $plan );
		}
		if ( 'npcink-abilities-toolkit/build-media-adoption-enhancement-plan' === $plan_ability_id ) {
			$preview['media_adoption_enhancement'] = $this->media_adoption_enhancement_preview( $plan );
		}
		if ( 'npcink-abilities-toolkit/build-media-rename-plan' === $plan_ability_id ) {
			$preview['media_rename'] = $this->media_rename_preview( $plan );
		}
		if ( 'npcink-abilities-toolkit/build-article-optimization-apply-plan' === $plan_ability_id ) {
			$preview['article_optimization'] = $this->article_optimization_preview( $plan );
		}
		if ( 'npcink-abilities-toolkit/build-article-block-plan' === $plan_ability_id ) {
			$preview['article_block'] = $this->article_block_preview( $plan );
		}
		if ( 'npcink-abilities-toolkit/build-pattern-page-plan' === $plan_ability_id ) {
			$preview['pattern_page'] = $this->pattern_page_preview( $plan );
		}
		if ( 'npcink-abilities-toolkit/build-block-theme-site-plan' === $plan_ability_id ) {
			$preview['block_theme_site'] = $this->block_theme_site_preview( $plan );
		}
		if ( 'npcink-toolbox/build-site-knowledge-review-plan' === $plan_ability_id ) {
			$preview['site_knowledge_review'] = $this->site_knowledge_review_preview( $plan );
		}
		if ( 'npcink-toolbox/build-content-metadata-apply-plan' === $plan_ability_id ) {
			$preview['content_metadata_apply'] = $this->content_metadata_apply_preview( $plan );
		}
		$preview = array_merge( $preview, $this->block_editor_quality_preview( $plan ) );

		$title = sprintf(
			/* translators: 1: target ability id, 2: action id. */
			__( 'Plan proposal: %1$s (%2$s)', 'npcink-governance-core' ),
			$target_ability_id,
			$action_id
		);

		return array(
			'ability_id' => $target_ability_id,
			'title'      => $title,
			'summary'    => sprintf(
				/* translators: 1: plan ability id, 2: batch id. */
				__( 'Created from %1$s batch %2$s. Final execution remains outside Core.', 'npcink-governance-core' ),
				$plan_ability_id,
				(string) ( $plan['batch_id'] ?? '' )
			),
			'input'      => $input,
			'preview'    => $preview,
			'caller'     => array_merge(
				$caller,
				array(
					'source'          => 'plan_to_proposal',
					'plan_ability_id' => $plan_ability_id,
					'batch_id'        => sanitize_text_field( (string) ( $plan['batch_id'] ?? '' ) ),
					'action_id'       => $action_id,
					'action_index'    => $index,
				)
			),
		);
	}

	/**
	 * Returns whether a plan must stay in one proposal for ordered execution.
	 *
	 * @param array<string,mixed> $plan Plan data.
	 * @param array<int,mixed>    $write_actions Write actions.
	 * @return bool
	 */
	private function plan_requires_batch_proposal( array $plan, array $write_actions ): bool {
		if ( true === (bool) ( $plan['batch_approval'] ?? false ) ) {
			return true;
		}

		if ( 'batch' === (string) ( $plan['proposal_mode'] ?? '' ) ) {
			return true;
		}

		foreach ( $write_actions as $action ) {
			if ( ! is_array( $action ) ) {
				continue;
			}
			if ( ! empty( $action['depends_on'] ?? array() ) ) {
				return true;
			}
			if ( $this->value_contains_output_reference( $action['input'] ?? array() ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Returns whether a value tree contains an output reference token.
	 *
	 * @param mixed $value Value.
	 * @return bool
	 */
	private function value_contains_output_reference( $value ): bool {
		if ( is_string( $value ) ) {
			return false !== strpos( $value, '$outputs.' );
		}
		if ( ! is_array( $value ) ) {
			return false;
		}
		foreach ( $value as $child ) {
			if ( $this->value_contains_output_reference( $child ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Builds one batch proposal payload from dependent plan actions.
	 *
	 * @param string              $plan_ability_id Plan ability id.
	 * @param array<string,mixed> $plan Plan data.
	 * @param array<string,mixed> $caller Caller metadata.
	 * @param array<int,array<string,mixed>> $items Per-action proposal payloads.
	 * @param array<string,mixed> $warnings Plan warnings.
	 * @return array<string,mixed>
	 */
	private function batch_proposal_payload_for_actions( string $plan_ability_id, array $plan, array $caller, array $items, array $warnings ): array {
		$first        = is_array( $items[0] ?? null ) ? $items[0] : array();
		$first_preview = is_array( $first['preview'] ?? null ) ? $first['preview'] : array();
		$batch_id     = sanitize_text_field( (string) ( $plan['batch_id'] ?? '' ) );
		$batch_actions = array();
		$action_previews = array();
		$action_ids   = array();
		$target_ids   = array();
		$needs_input  = array();
		$preflight_blockers = array();
		$proposal_ready = true;

		foreach ( $items as $item ) {
			$preview           = is_array( $item['preview'] ?? null ) ? $item['preview'] : array();
			$action_id         = sanitize_key( (string) ( $preview['action_id'] ?? '' ) );
			$target_ability_id = sanitize_text_field( (string) ( $preview['target_ability_id'] ?? ( $item['ability_id'] ?? '' ) ) );
			$action_ready      = array_key_exists( 'proposal_ready', $preview ) ? (bool) $preview['proposal_ready'] : true;
			$action_needs_input = array_values( array_map( 'sanitize_key', (array) ( $preview['needs_input'] ?? array() ) ) );
			$action_blockers   = is_array( $preview['preflight_blockers'] ?? null ) ? array_values( $preview['preflight_blockers'] ) : array();
			$depends_on        = array_values( array_map( 'sanitize_key', (array) ( $preview['depends_on'] ?? array() ) ) );

			$batch_actions[] = array(
				'action_id'         => $action_id,
				'action_index'      => absint( $preview['action_index'] ?? count( $batch_actions ) ),
				'target_ability_id' => $target_ability_id,
				'depends_on'        => $depends_on,
				'input'             => is_array( $item['input'] ?? null ) ? $item['input'] : array(),
				'requires_approval' => true,
				'commit_execution'  => false,
				'proposal_ready'    => $action_ready,
				'requires_input'    => $action_needs_input,
				'preflight_blockers' => $action_blockers,
				'required_scopes'   => array_values( array_map( 'sanitize_key', (array) ( $preview['required_scopes'] ?? array() ) ) ),
				'reason'            => sanitize_textarea_field( (string) ( $preview['reason'] ?? '' ) ),
			);
			$action_previews[] = array(
				'action_id'         => $action_id,
				'action_index'      => absint( $preview['action_index'] ?? 0 ),
				'target_ability_id' => $target_ability_id,
				'depends_on'        => $depends_on,
				'preview'           => $this->sanitize_payload( $preview ),
			);

			$action_ids[] = $action_id;
			$target_ids[] = $target_ability_id;
			$needs_input  = array_merge( $needs_input, $action_needs_input );
			if ( ! $action_ready ) {
				$proposal_ready = false;
			}
			foreach ( $action_blockers as $blocker ) {
				$preflight_blockers[] = array(
					'action_id' => $action_id,
					'blocker'   => $this->sanitize_payload( $blocker ),
				);
			}
		}

		$target_ids  = array_values( array_unique( array_filter( $target_ids ) ) );
		$action_ids  = array_values( array_filter( $action_ids ) );
		$needs_input = array_values( array_unique( array_filter( $needs_input ) ) );
		$preview     = array(
			'source' => array(
				'type'            => 'plan_to_proposal_batch',
				'plan_ability_id' => $plan_ability_id,
				'batch_id'        => $batch_id,
				'issue_types'     => array_values( array_map( 'sanitize_key', (array) ( $plan['issue_types'] ?? array() ) ) ),
				'proposal_mode'   => sanitize_key( (string) ( $plan['proposal_mode'] ?? '' ) ),
				'batch_approval'  => true === (bool) ( $plan['batch_approval'] ?? false ),
			),
			'action_count'       => count( $batch_actions ),
			'action_ids'         => $action_ids,
			'target_ability_ids' => $target_ids,
			'actions'            => $action_previews,
			'plan_preview'       => $this->sanitize_payload( $plan['preview'] ?? array() ),
			'risk'               => is_array( $plan['risk'] ?? null ) ? $this->sanitize_payload( $plan['risk'] ) : array(),
			'warnings'           => $warnings,
			'blocked_items'      => array(
				'manual_review'                  => $this->sanitize_payload( $plan['manual_review'] ?? array() ),
				'skipped_destructive_candidates' => $this->sanitize_payload( $plan['skipped_destructive_candidates'] ?? array() ),
			),
			'requires_approval'  => true,
			'dry_run'            => true,
			'commit'             => false,
			'commit_execution'   => false,
			'proposal_ready'     => $proposal_ready && empty( $needs_input ) && empty( $preflight_blockers ),
			'needs_input'        => $needs_input,
			'preflight_blockers' => $preflight_blockers,
		);

		if ( 'npcink-toolbox/build-article-batch-write-plan' === $plan_ability_id ) {
			$preview['article_batch_workflow'] = $this->article_batch_workflow_preview( $plan );
		}
		if ( 'npcink-toolbox/build-article-media-batch-write-plan' === $plan_ability_id ) {
			$preview['article_media_batch_workflow'] = $this->article_media_batch_workflow_preview( $plan );
		}
		if ( 'npcink-abilities-toolkit/build-media-optimization-plan' === $plan_ability_id ) {
			$preview['media_optimization'] = $this->media_optimization_preview( $plan );
		}
		if ( 'npcink-abilities-toolkit/build-media-adoption-enhancement-plan' === $plan_ability_id ) {
			$preview['media_adoption_enhancement'] = $this->media_adoption_enhancement_preview( $plan );
		}
		if ( 'npcink-abilities-toolkit/build-media-rename-plan' === $plan_ability_id ) {
			$preview['media_rename'] = $this->media_rename_preview( $plan );
		}
		if ( 'npcink-abilities-toolkit/build-article-block-plan' === $plan_ability_id ) {
			$preview['article_block'] = $this->article_block_preview( $plan );
		}
		if ( 'npcink-abilities-toolkit/build-pattern-page-plan' === $plan_ability_id ) {
			$preview['pattern_page'] = $this->pattern_page_preview( $plan );
		}
		if ( 'npcink-abilities-toolkit/build-block-theme-site-plan' === $plan_ability_id ) {
			$preview['block_theme_site'] = $this->block_theme_site_preview( $plan );
		}
		if ( 'npcink-toolbox/build-content-metadata-apply-plan' === $plan_ability_id ) {
			$preview['content_metadata_apply'] = $this->content_metadata_apply_preview( $plan );
		}
		$preview = array_merge( $preview, $this->block_editor_quality_preview( $plan ) );
		$summary = sprintf(
			/* translators: 1: plan ability id, 2: action count. */
			__( 'Created from %1$s as an ordered batch with %2$d actions. Final execution remains outside Core.', 'npcink-governance-core' ),
			$plan_ability_id,
			count( $batch_actions )
		);
		if ( 'npcink-abilities-toolkit/build-media-optimization-plan' === $plan_ability_id ) {
			$summary = $this->media_optimization_proposal_summary( $plan, count( $batch_actions ), $summary );
		}

		return array(
			'ability_id' => sanitize_text_field( (string) ( $first['ability_id'] ?? ( $first_preview['target_ability_id'] ?? '' ) ) ),
			'title'      => sprintf(
				/* translators: 1: plan ability id, 2: batch id. */
				__( 'Plan batch proposal: %1$s (%2$s)', 'npcink-governance-core' ),
				$plan_ability_id,
				$batch_id
			),
			'summary'    => $summary,
			'input'      => array(
				'write_actions' => $batch_actions,
				'dry_run'       => true,
				'commit'        => false,
			),
			'preview'    => $preview,
			'caller'     => array_merge(
				$caller,
				array(
					'source'          => 'plan_to_proposal_batch',
					'plan_ability_id' => $plan_ability_id,
					'batch_id'        => $batch_id,
					'action_count'    => count( $batch_actions ),
				)
			),
		);
	}

	/**
	 * Builds a human-readable approval summary for media optimization batches.
	 *
	 * @param array<string,mixed> $plan Plan payload.
	 * @param int                 $action_count Batch action count.
	 * @param string              $fallback Fallback summary.
	 * @return string
	 */
	private function media_optimization_proposal_summary( array $plan, int $action_count, string $fallback ): string {
		$attachment_id = absint( $plan['attachment_id'] ?? 0 );
		$derivative_preview = is_array( $plan['derivative_preview'] ?? null ) ? $plan['derivative_preview'] : array();
		$before = is_array( $derivative_preview['before'] ?? null ) ? $derivative_preview['before'] : array();
		$after  = is_array( $derivative_preview['after'] ?? null ) ? $derivative_preview['after'] : array();
		$metadata_preview = is_array( $plan['metadata_preview'] ?? null ) ? $plan['metadata_preview'] : array();
		$metadata_after   = is_array( $metadata_preview['after'] ?? null ) ? $metadata_preview['after'] : array();
		$repairs = is_array( $derivative_preview['content_reference_repairs'] ?? null ) ? $derivative_preview['content_reference_repairs'] : array();

		if ( $attachment_id <= 0 || empty( $derivative_preview ) ) {
			return $fallback;
		}

		$reviewed_file = '';
		foreach ( (array) ( $plan['write_actions'] ?? array() ) as $action ) {
			if ( ! is_array( $action ) || 'npcink-abilities-toolkit/adopt-cloud-media-derivative' !== (string) ( $action['target_ability_id'] ?? '' ) ) {
				continue;
			}
			$input = is_array( $action['input'] ?? null ) ? $action['input'] : array();
			$reviewed_file = sanitize_text_field( basename( str_replace( '\\', '/', (string) ( $input['file_name'] ?? '' ) ) ) );
			break;
		}

		$lines = array();
		$from_mime = sanitize_text_field( (string) ( $before['mime_type'] ?? '' ) );
		$to_mime   = sanitize_text_field( (string) ( $after['mime_type'] ?? '' ) );
		if ( '' !== $from_mime || '' !== $to_mime ) {
			$lines[] = sprintf(
				/* translators: 1: attachment id, 2: source MIME type, 3: target MIME type. */
				__( 'Optimize attachment %1$d: replace %2$s with %3$s.', 'npcink-governance-core' ),
				$attachment_id,
				'' !== $from_mime ? $from_mime : __( 'the current file', 'npcink-governance-core' ),
				'' !== $to_mime ? $to_mime : __( 'the reviewed derivative', 'npcink-governance-core' )
			);
		} else {
			$lines[] = sprintf(
				/* translators: %d: attachment id. */
				__( 'Optimize attachment %d with the reviewed media derivative.', 'npcink-governance-core' ),
				$attachment_id
			);
		}

		$width  = absint( $after['width'] ?? 0 );
		$height = absint( $after['height'] ?? 0 );
		if ( $width > 0 && $height > 0 ) {
			$lines[] = sprintf(
				/* translators: 1: width, 2: height. */
				__( 'Reviewed derivative dimensions: %1$d x %2$d.', 'npcink-governance-core' ),
				$width,
				$height
			);
		}
		if ( '' !== $reviewed_file ) {
			$lines[] = sprintf(
				/* translators: %s: reviewed file basename. */
				__( 'Reviewed derivative filename: %s.', 'npcink-governance-core' ),
				$reviewed_file
			);
		}
		if ( ! empty( $metadata_after ) ) {
			$lines[] = __( 'Update reviewed media title, alt text, caption, description, or source metadata.', 'npcink-governance-core' );
		}

		$post_count = absint( $repairs['post_count'] ?? 0 );
		$actual_replacement_count = absint( $repairs['actual_replacement_count'] ?? ( $repairs['replacement_count'] ?? 0 ) );
		if ( $post_count > 0 || $actual_replacement_count > 0 ) {
			$lines[] = sprintf(
				/* translators: 1: post count, 2: replacement count. */
				__( 'Repair inline references in %1$d post(s), with %2$d actual replacement(s) expected.', 'npcink-governance-core' ),
				$post_count,
				$actual_replacement_count
			);
		}

		$lines[] = sprintf(
			/* translators: %d: action count. */
			__( 'Create one Core approval for %d ordered action(s); final execution remains outside Core.', 'npcink-governance-core' ),
			$action_count
		);
		$lines[] = __( 'Preserve the original file as a local backup for rollback.', 'npcink-governance-core' );

		return implode( "\n", array_values( array_filter( $lines ) ) );
	}

	/**
	 * Returns an error for a blocked action.
	 *
	 * @param string              $code Code.
	 * @param string              $reason Reason.
	 * @param array<string,mixed> $extra Extra data.
	 * @return WP_Error
	 */
	private function blocked_error( string $code, string $reason, array $extra = array() ): WP_Error {
		return new WP_Error(
			'npcink_governance_core_plan_action_blocked',
			$reason,
			array_merge(
				array(
					'block_code' => $code,
					'reason'     => $reason,
				),
				$extra
			)
		);
	}

	/**
	 * Returns whether destructive media delete candidates were explicit.
	 *
	 * @param array<string,mixed> $plan Plan data.
	 * @param array<string,mixed> $plan_input Plan input.
	 * @return bool
	 */
	private function include_delete_candidates( array $plan, array $plan_input ): bool {
		return true === (bool) ( $plan_input['include_delete_candidates'] ?? false );
	}

	/**
	 * Returns a normalized action risk.
	 *
	 * @param array<string,mixed> $action Action.
	 * @param array<string,mixed> $target Target capability.
	 * @param string              $plan_risk Plan risk level.
	 * @return string
	 */
	private function action_risk( array $action, array $target, string $plan_risk ): string {
		$risk        = sanitize_key( (string) ( $action['risk'] ?? $plan_risk ) );
		$target_risk = sanitize_key( (string) ( $target['risk_level'] ?? '' ) );

		if ( in_array( $target_risk, array( 'destructive', 'delete' ), true ) || in_array( $risk, array( 'destructive', 'delete' ), true ) ) {
			return 'high';
		}

		if ( ! in_array( $risk, array( 'low', 'medium', 'high' ), true ) ) {
			return 'medium';
		}

		return $risk;
	}

	/**
	 * Returns plan risk level.
	 *
	 * @param mixed $risk Risk data.
	 * @return string
	 */
	private function risk_level( $risk ): string {
		if ( is_array( $risk ) ) {
			$risk = $risk['level'] ?? 'medium';
		}

		$risk = sanitize_key( (string) $risk );
		return in_array( $risk, array( 'low', 'medium', 'high' ), true ) ? $risk : 'medium';
	}

	/**
	 * Returns plan-level warnings.
	 *
	 * @param array<int,mixed> $manual_review Manual review rows.
	 * @param array<int,mixed> $skipped_destructive Skipped destructive candidates.
	 * @return array<string,mixed>
	 */
	private function plan_warnings( array $manual_review, array $skipped_destructive ): array {
		return array(
			'manual_review_count'                  => count( $manual_review ),
			'skipped_destructive_candidate_count' => count( $skipped_destructive ),
			'manual_review'                       => $this->sanitize_payload( $manual_review ),
			'skipped_destructive_candidates'      => $this->sanitize_payload( $skipped_destructive ),
		);
	}

	/**
	 * Finds the preview row associated with an action input.
	 *
	 * @param array<int,mixed>    $preview_rows Preview rows.
	 * @param array<string,mixed> $input Action input.
	 * @return array<string,mixed>
	 */
	private function matching_preview_row( array $preview_rows, array $input ): array {
		$keys = array( 'post_id', 'attachment_id', 'comment_id', 'term_id' );

		foreach ( $preview_rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			foreach ( $keys as $key ) {
				if ( isset( $input[ $key ], $row[ $key ] ) && (string) $input[ $key ] === (string) $row[ $key ] ) {
					return $this->sanitize_payload( $row );
				}
			}
		}

		return array();
	}

	/**
	 * Sanitizes structured payload recursively.
	 *
	 * @param mixed $value Value.
	 * @return mixed
	 */
	private function sanitize_payload( $value ) {
		if ( is_array( $value ) ) {
			$clean = array();
			foreach ( $value as $key => $item ) {
				$clean[ sanitize_key( (string) $key ) ] = $this->sanitize_payload( $item );
			}
			return $clean;
		}

		if ( is_string( $value ) ) {
			return sanitize_textarea_field( $value );
		}

		if ( is_bool( $value ) || is_int( $value ) || is_float( $value ) || null === $value ) {
			return $value;
		}

		return sanitize_text_field( (string) $value );
	}
}
