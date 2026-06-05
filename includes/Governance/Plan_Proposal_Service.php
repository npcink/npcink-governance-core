<?php
/**
 * Plan-to-proposal governance bridge.
 *
 * @package MagickAICore
 */

namespace MagickAI\Core\Governance;

use MagickAI\Core\Audit\Audit_Log_Repository;
use MagickAI\Core\Capabilities\Ability_Registry_Adapter;
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
		'magick-ai/build-content-inventory-fix-plan'                  => true,
		'magick-ai/build-test-content-cleanup-plan'                   => true,
		'magick-ai/build-media-inventory-fix-plan'                    => true,
		'magick-ai/build-media-reference-repair-plan'                 => true,
		'magick-ai/build-media-settings-reference-repair-plan'        => true,
		'magick-ai/build-media-optimization-plan'                     => true,
		'magick-ai/build-media-rename-plan'                           => true,
		'magick-ai-toolbox/build-article-write-plan'                  => true,
		'magick-ai-toolbox/build-article-batch-write-plan'            => true,
		'magick-ai-toolbox/build-article-media-batch-write-plan'      => true,
		'magick-ai-toolbox/build-image-candidate-adoption-plan'       => true,
	);

	private const ARTICLE_BATCH_MAX_ACTIONS = 5;
	private const ARTICLE_MEDIA_BATCH_MAX_ARTICLES = 5;
	private const ARTICLE_MEDIA_BATCH_MAX_ACTIONS = 25;

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
				'magick_ai_core_plan_ability_not_allowed',
				__( 'This planning ability is not accepted by the Core plan-to-proposal bridge.', 'magick-ai-core' ),
				array( 'status' => 400 )
			);
		}

		$plan_capability = $this->abilities->find( $plan_ability_id );
		if ( null === $plan_capability ) {
			return new WP_Error(
				'magick_ai_core_plan_ability_unavailable',
				__( 'The planning ability is not currently discoverable.', 'magick-ai-core' ),
				array( 'status' => 404 )
			);
		}

		if ( 'direct_read' !== (string) ( $plan_capability['governance_mode'] ?? '' ) || 'wp_abilities_rest' !== (string) ( $plan_capability['execution_surface'] ?? '' ) ) {
			return new WP_Error(
				'magick_ai_core_plan_ability_not_read_only',
				__( 'Plan-to-proposal intake accepts only direct-read planning abilities.', 'magick-ai-core' ),
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

		if ( 'magick-ai-toolbox/build-article-write-plan' === $plan_ability_id ) {
			$article_contract_error = $this->validate_article_write_plan_contract( $plan );
			if ( is_wp_error( $article_contract_error ) ) {
				return $article_contract_error;
			}
		}

		if ( 'magick-ai-toolbox/build-article-batch-write-plan' === $plan_ability_id ) {
			$article_batch_contract_error = $this->validate_article_batch_write_plan_contract( $plan );
			if ( is_wp_error( $article_batch_contract_error ) ) {
				return $article_batch_contract_error;
			}
		}

		if ( 'magick-ai-toolbox/build-article-media-batch-write-plan' === $plan_ability_id ) {
			$article_media_batch_contract_error = $this->validate_article_media_batch_write_plan_contract( $plan );
			if ( is_wp_error( $article_media_batch_contract_error ) ) {
				return $article_media_batch_contract_error;
			}
		}

		if ( 'magick-ai-toolbox/build-image-candidate-adoption-plan' === $plan_ability_id ) {
			$image_candidate_contract_error = $this->validate_image_candidate_adoption_plan_contract( $plan );
			if ( is_wp_error( $image_candidate_contract_error ) ) {
				return $image_candidate_contract_error;
			}
		}

		if ( 'magick-ai/build-media-optimization-plan' === $plan_ability_id ) {
			$media_optimization_contract_error = $this->validate_media_optimization_plan_contract( $plan );
			if ( is_wp_error( $media_optimization_contract_error ) ) {
				return $media_optimization_contract_error;
			}
		}

		if ( 'magick-ai/build-media-rename-plan' === $plan_ability_id ) {
			$media_rename_contract_error = $this->validate_media_rename_plan_contract( $plan );
			if ( is_wp_error( $media_rename_contract_error ) ) {
				return $media_rename_contract_error;
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
				'magick_ai_core_plan_unsuccessful',
				__( 'Only successful plan outputs can be converted into proposals.', 'magick-ai-core' ),
				array( 'status' => 422 )
			);
		}

		$data = is_array( $payload['data'] ?? null ) ? $payload['data'] : $payload;
		if ( ! is_array( $data ) ) {
			return new WP_Error(
				'magick_ai_core_plan_invalid',
				__( 'Plan payload must be an object.', 'magick-ai-core' ),
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
				'magick_ai_core_plan_requires_approval_missing',
				__( 'Plan output must require approval.', 'magick-ai-core' ),
				array( 'status' => 422 )
			);
		}

		if ( false !== (bool) ( $plan['commit_execution'] ?? true ) ) {
			return new WP_Error(
				'magick_ai_core_plan_commit_execution_rejected',
				__( 'Plan output must not execute commits.', 'magick-ai-core' ),
				array( 'status' => 422 )
			);
		}

		if ( true !== (bool) ( $plan['dry_run'] ?? false ) ) {
			return new WP_Error(
				'magick_ai_core_plan_dry_run_required',
				__( 'Plan output must be dry-run only.', 'magick-ai-core' ),
				array( 'status' => 422 )
			);
		}

		if ( ! is_array( $plan['write_actions'] ?? null ) ) {
			return new WP_Error(
				'magick_ai_core_plan_write_actions_missing',
				__( 'Plan output must include write_actions.', 'magick-ai-core' ),
				array( 'status' => 422 )
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
				'magick_ai_core_article_batch_plan_invalid',
				__( 'Article batch write plans must declare artifact_type=article_batch_write_plan.', 'magick-ai-core' ),
				array( 'status' => 422 )
			);
		}

		if ( true !== (bool) ( $plan['batch_approval'] ?? false ) || 'batch' !== sanitize_key( (string) ( $plan['proposal_mode'] ?? '' ) ) ) {
			return new WP_Error(
				'magick_ai_core_article_batch_mode_required',
				__( 'Article batch write plans must explicitly request batch proposal approval.', 'magick-ai-core' ),
				array( 'status' => 422 )
			);
		}

		$write_actions = is_array( $plan['write_actions'] ?? null ) ? array_values( $plan['write_actions'] ) : array();
		if ( count( $write_actions ) < 2 || count( $write_actions ) > self::ARTICLE_BATCH_MAX_ACTIONS ) {
			return new WP_Error(
				'magick_ai_core_article_batch_size_rejected',
				__( 'Article batch write plans must contain a bounded group of draft actions.', 'magick-ai-core' ),
				array(
					'status'      => 422,
					'max_actions' => self::ARTICLE_BATCH_MAX_ACTIONS,
				)
			);
		}

		$articles = is_array( $plan['articles'] ?? null ) ? array_values( $plan['articles'] ) : array();
		if ( count( $articles ) !== count( $write_actions ) ) {
			return new WP_Error(
				'magick_ai_core_article_batch_artifacts_missing',
				__( 'Article batch write plans must include one reviewed article artifact set per draft action.', 'magick-ai-core' ),
				array( 'status' => 422 )
			);
		}

		foreach ( $articles as $article_index => $article ) {
			if ( ! is_array( $article ) ) {
				return new WP_Error(
					'magick_ai_core_article_batch_artifacts_missing',
					__( 'Article batch entries must be objects.', 'magick-ai-core' ),
					array(
						'status'        => 422,
						'article_index' => $article_index,
					)
				);
			}
			$artifact_error = $this->validate_article_artifacts( $article, 'magick_ai_core_article_batch_' );
			if ( is_wp_error( $artifact_error ) ) {
				return $artifact_error;
			}
		}

		foreach ( $write_actions as $action_index => $action ) {
			if ( ! is_array( $action ) ) {
				return new WP_Error(
					'magick_ai_core_article_batch_action_invalid',
					__( 'Article batch write actions must be objects.', 'magick-ai-core' ),
					array(
						'status'       => 422,
						'action_index' => $action_index,
					)
				);
			}
			$draft_error = $this->validate_article_draft_action( $action, 'magick_ai_core_article_batch_' );
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
				'magick_ai_core_article_media_batch_plan_invalid',
				__( 'Article media batch write plans must declare artifact_type=article_media_batch_write_plan.', 'magick-ai-core' ),
				array( 'status' => 422 )
			);
		}

		if ( true !== (bool) ( $plan['batch_approval'] ?? false ) || 'batch' !== sanitize_key( (string) ( $plan['proposal_mode'] ?? '' ) ) ) {
			return new WP_Error(
				'magick_ai_core_article_media_batch_mode_required',
				__( 'Article media batch write plans must explicitly request batch proposal approval.', 'magick-ai-core' ),
				array( 'status' => 422 )
			);
		}

		$articles = is_array( $plan['articles'] ?? null ) ? array_values( $plan['articles'] ) : array();
		if ( count( $articles ) < 1 || count( $articles ) > self::ARTICLE_MEDIA_BATCH_MAX_ARTICLES ) {
			return new WP_Error(
				'magick_ai_core_article_media_batch_size_rejected',
				__( 'Article media batch write plans must include 1 to 5 reviewed articles.', 'magick-ai-core' ),
				array(
					'status'       => 422,
					'max_articles' => self::ARTICLE_MEDIA_BATCH_MAX_ARTICLES,
				)
			);
		}

		foreach ( $articles as $article_index => $article ) {
			if ( ! is_array( $article ) ) {
				return new WP_Error(
					'magick_ai_core_article_media_batch_artifacts_missing',
					__( 'Article media batch entries must be objects.', 'magick-ai-core' ),
					array(
						'status'        => 422,
						'article_index' => $article_index,
					)
				);
			}
			$artifact_error = $this->validate_article_artifacts( $article, 'magick_ai_core_article_media_batch_' );
			if ( is_wp_error( $artifact_error ) ) {
				return $artifact_error;
			}
			if ( ! is_array( $article['featured_image_candidate'] ?? null ) ) {
				return new WP_Error(
					'magick_ai_core_article_media_batch_candidate_missing',
					__( 'Article media batch entries must preserve the selected image-source candidate.', 'magick-ai-core' ),
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
				'magick_ai_core_article_media_batch_actions_rejected',
				__( 'Article media batch write plans must contain a bounded group of draft, media upload, and featured-image actions.', 'magick-ai-core' ),
				array(
					'status'      => 422,
					'max_actions' => self::ARTICLE_MEDIA_BATCH_MAX_ACTIONS,
				)
			);
		}

		$target_counts = array(
			'magick-ai/create-draft'             => 0,
			'magick-ai/upload-media-from-url'    => 0,
			'magick-ai/set-post-featured-image'  => 0,
		);
		$allowed_targets = array(
			'magick-ai/create-draft'             => true,
			'magick-ai/upload-media-from-url'    => true,
			'magick-ai/update-media-details'     => true,
			'magick-ai/set-post-featured-image'  => true,
			'magick-ai/patch-post-content'       => true,
		);

		foreach ( $write_actions as $action_index => $action ) {
			if ( ! is_array( $action ) ) {
				return new WP_Error(
					'magick_ai_core_article_media_batch_action_invalid',
					__( 'Article media batch write actions must be objects.', 'magick-ai-core' ),
					array(
						'status'       => 422,
						'action_index' => $action_index,
					)
				);
			}

			$target_ability_id = sanitize_text_field( (string) ( $action['target_ability_id'] ?? '' ) );
			if ( ! isset( $allowed_targets[ $target_ability_id ] ) ) {
				return new WP_Error(
					'magick_ai_core_article_media_batch_target_rejected',
					__( 'Article media batch plans may target only draft and allowlisted media actions.', 'magick-ai-core' ),
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
					'magick_ai_core_article_media_batch_actions_missing',
					__( 'Article media batch plans must include create, upload, and featured-image actions for every article.', 'magick-ai-core' ),
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
				'magick_ai_core_image_candidate_adoption_plan_invalid',
				__( 'Image candidate adoption plans must declare artifact_type=image_candidate_adoption_plan.', 'magick-ai-core' ),
				array( 'status' => 422 )
			);
		}

		$candidate = is_array( $plan['selected_image_candidate'] ?? null ) ? $plan['selected_image_candidate'] : array();
		if ( empty( $candidate ) || 'image_candidate.v1' !== (string) ( $candidate['contract_version'] ?? $plan['candidate_contract_version'] ?? '' ) ) {
			return new WP_Error(
				'magick_ai_core_image_candidate_contract_missing',
				__( 'Image candidate adoption plans must preserve a selected image_candidate.v1 candidate.', 'magick-ai-core' ),
				array( 'status' => 422 )
			);
		}

		$write_actions = is_array( $plan['write_actions'] ?? null ) ? array_values( $plan['write_actions'] ) : array();
		if ( count( $write_actions ) < 2 || count( $write_actions ) > 3 ) {
			return new WP_Error(
				'magick_ai_core_image_candidate_actions_rejected',
				__( 'Image candidate adoption plans must contain upload, metadata, and optional featured-image actions only.', 'magick-ai-core' ),
				array( 'status' => 422 )
			);
		}

		$target_counts = array(
			'magick-ai/upload-media-from-url'   => 0,
			'magick-ai/update-media-details'    => 0,
			'magick-ai/set-post-featured-image' => 0,
		);

		foreach ( $write_actions as $action_index => $action ) {
			if ( ! is_array( $action ) ) {
				return new WP_Error(
					'magick_ai_core_image_candidate_action_invalid',
					__( 'Image candidate adoption actions must be objects.', 'magick-ai-core' ),
					array(
						'status'       => 422,
						'action_index' => $action_index,
					)
				);
			}
			$target_ability_id = sanitize_text_field( (string) ( $action['target_ability_id'] ?? '' ) );
			if ( ! isset( $target_counts[ $target_ability_id ] ) ) {
				return new WP_Error(
					'magick_ai_core_image_candidate_target_rejected',
					__( 'Image candidate adoption plans may target only media import, metadata, and featured-image abilities.', 'magick-ai-core' ),
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

		if ( 1 !== $target_counts['magick-ai/upload-media-from-url'] || 1 !== $target_counts['magick-ai/update-media-details'] || $target_counts['magick-ai/set-post-featured-image'] > 1 ) {
			return new WP_Error(
				'magick_ai_core_image_candidate_actions_missing',
				__( 'Image candidate adoption plans must include exactly one media upload and one metadata action, plus at most one featured-image action.', 'magick-ai-core' ),
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
				'magick_ai_core_image_candidate_commit_rejected',
				__( 'Image candidate adoption action input must remain dry-run and must not request commit.', 'magick-ai-core' ),
				array(
					'status'       => 422,
					'action_index' => $action_index,
				)
			);
		}

		$target_ability_id = sanitize_text_field( (string) ( $action['target_ability_id'] ?? '' ) );
		if ( 'magick-ai/upload-media-from-url' === $target_ability_id ) {
			if ( ! $this->is_valid_absolute_url( $input['url'] ?? null ) ) {
				return new WP_Error(
					'magick_ai_core_image_candidate_url_missing',
					__( 'Image candidate upload actions must include a reviewed candidate URL.', 'magick-ai-core' ),
					array(
						'status'       => 422,
						'action_index' => $action_index,
					)
				);
			}
			$source_type = sanitize_key( (string) ( $input['source_type'] ?? '' ) );
			if ( '' !== $source_type && ! in_array( $source_type, array( 'owned', 'ai_generated', 'stock', 'external', 'test' ), true ) ) {
				return new WP_Error(
					'magick_ai_core_image_candidate_source_type_invalid',
					__( 'Image candidate source_type must be owned, ai_generated, stock, external, or test.', 'magick-ai-core' ),
					array(
						'status'       => 422,
						'action_index' => $action_index,
					)
				);
			}
			return true;
		}

		if ( 'magick-ai/update-media-details' === $target_ability_id ) {
			if ( ! $this->is_exact_output_reference( $input['attachment_id'] ?? null ) && absint( $input['attachment_id'] ?? 0 ) <= 0 ) {
				return new WP_Error(
					'magick_ai_core_image_candidate_attachment_missing',
					__( 'Image candidate metadata actions must include attachment_id or an approved output reference.', 'magick-ai-core' ),
					array(
						'status'       => 422,
						'action_index' => $action_index,
					)
				);
			}
			return true;
		}

		if ( 'magick-ai/set-post-featured-image' === $target_ability_id ) {
			if ( ! $this->is_exact_output_reference( $input['attachment_id'] ?? null ) && absint( $input['attachment_id'] ?? 0 ) <= 0 ) {
				return new WP_Error(
					'magick_ai_core_image_candidate_featured_attachment_missing',
					__( 'Image candidate featured-image actions must include attachment_id or an approved output reference.', 'magick-ai-core' ),
					array(
						'status'       => 422,
						'action_index' => $action_index,
					)
				);
			}
			if ( absint( $input['post_id'] ?? 0 ) <= 0 && ! $this->is_exact_output_reference( $input['post_id'] ?? null ) ) {
				return new WP_Error(
					'magick_ai_core_image_candidate_featured_post_missing',
					__( 'Image candidate featured-image actions must include post_id or an approved output reference.', 'magick-ai-core' ),
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
				'magick_ai_core_media_optimization_plan_invalid',
				__( 'Media optimization plans must declare artifact_type=media_optimization_plan.', 'magick-ai-core' ),
				array( 'status' => 422 )
			);
		}

		if ( true !== (bool) ( $plan['batch_approval'] ?? false ) || 'batch' !== sanitize_key( (string) ( $plan['proposal_mode'] ?? '' ) ) ) {
			return new WP_Error(
				'magick_ai_core_media_optimization_batch_required',
				__( 'Media optimization plans must explicitly request one batch proposal approval.', 'magick-ai-core' ),
				array( 'status' => 422 )
			);
		}

		$write_actions = is_array( $plan['write_actions'] ?? null ) ? array_values( $plan['write_actions'] ) : array();
		if ( count( $write_actions ) < 2 ) {
			return new WP_Error(
				'magick_ai_core_media_optimization_actions_missing',
				__( 'Media optimization plans must include metadata and derivative adoption actions.', 'magick-ai-core' ),
				array( 'status' => 422 )
			);
		}

		$targets = array();
		foreach ( $write_actions as $action ) {
			if ( is_array( $action ) ) {
				$targets[] = sanitize_text_field( (string) ( $action['target_ability_id'] ?? '' ) );
			}
		}

		if ( ! in_array( 'magick-ai/update-media-details', $targets, true ) ) {
			return new WP_Error(
				'magick_ai_core_media_optimization_metadata_missing',
				__( 'Media optimization plans must include magick-ai/update-media-details.', 'magick-ai-core' ),
				array( 'status' => 422 )
			);
		}

		$derivative_targets = array(
			'magick-ai/adopt-cloud-media-derivative',
			'magick-ai/replace-media-file',
		);
		$separate_reference_repair_targets = array(
			'magick-ai/patch-post-content',
			'magick-ai/update-post',
			'magick-ai/update-post-blocks',
		);
		if ( empty( array_intersect( $targets, $derivative_targets ) ) ) {
			return new WP_Error(
				'magick_ai_core_media_optimization_derivative_missing',
				__( 'Media optimization plans must include a governed derivative adoption action.', 'magick-ai-core' ),
				array( 'status' => 422 )
			);
		}
		if ( ! empty( array_intersect( $targets, $separate_reference_repair_targets ) ) ) {
			return new WP_Error(
				'magick_ai_core_media_optimization_reference_repair_split',
				__( 'Media optimization plans must not split post-content media reference repair into a separate write action.', 'magick-ai-core' ),
				array( 'status' => 422 )
			);
		}

		$attachment_ids = array();
		foreach ( $write_actions as $action ) {
			$input = is_array( $action['input'] ?? null ) ? $action['input'] : array();
			$id    = absint( $input['attachment_id'] ?? 0 );
			if ( $id > 0 ) {
				$attachment_ids[] = $id;
			}
		}
		$attachment_ids = array_values( array_unique( $attachment_ids ) );
		if ( 1 !== count( $attachment_ids ) ) {
			return new WP_Error(
				'magick_ai_core_media_optimization_attachment_mismatch',
				__( 'Media optimization plans must target exactly one attachment across all write actions.', 'magick-ai-core' ),
				array( 'status' => 422 )
			);
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
				'magick_ai_core_media_rename_plan_invalid',
				__( 'Media rename plans must declare artifact_type=media_rename_plan.', 'magick-ai-core' ),
				array( 'status' => 422 )
			);
		}

		$write_actions = is_array( $plan['write_actions'] ?? null ) ? array_values( $plan['write_actions'] ) : array();
		if ( 1 !== count( $write_actions ) || ! is_array( $write_actions[0] ?? null ) ) {
			return new WP_Error(
				'magick_ai_core_media_rename_actions_missing',
				__( 'Media rename plans must include exactly one rename-media-file action.', 'magick-ai-core' ),
				array( 'status' => 422 )
			);
		}

		$action = $write_actions[0];
		if ( 'magick-ai/rename-media-file' !== sanitize_text_field( (string) ( $action['target_ability_id'] ?? '' ) ) ) {
			return new WP_Error(
				'magick_ai_core_media_rename_target_rejected',
				__( 'Media rename plans may target only magick-ai/rename-media-file.', 'magick-ai-core' ),
				array( 'status' => 422 )
			);
		}

		$input         = is_array( $action['input'] ?? null ) ? $action['input'] : array();
		$attachment_id = absint( $plan['attachment_id'] ?? ( $input['attachment_id'] ?? 0 ) );
		if ( $attachment_id <= 0 || absint( $input['attachment_id'] ?? 0 ) !== $attachment_id ) {
			return new WP_Error(
				'magick_ai_core_media_rename_attachment_mismatch',
				__( 'Media rename plans must target exactly one attachment.', 'magick-ai-core' ),
				array( 'status' => 422 )
			);
		}

		if ( '' === trim( sanitize_text_field( (string) ( $input['target_file_name'] ?? '' ) ) ) ) {
			return new WP_Error(
				'magick_ai_core_media_rename_target_file_missing',
				__( 'Media rename plans must include a reviewed target_file_name.', 'magick-ai-core' ),
				array( 'status' => 422 )
			);
		}

		if ( true === (bool) ( $input['commit'] ?? false ) || false === (bool) ( $input['dry_run'] ?? true ) ) {
			return new WP_Error(
				'magick_ai_core_media_rename_commit_rejected',
				__( 'Media rename action input must remain dry-run and must not request commit.', 'magick-ai-core' ),
				array( 'status' => 422 )
			);
		}

		return true;
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
				'magick_ai_core_article_plan_invalid',
				__( 'Article write plans must declare artifact_type=article_write_plan.', 'magick-ai-core' ),
				array( 'status' => 422 )
			);
		}

		if ( absint( $plan['version'] ?? 0 ) < 1 ) {
			return new WP_Error(
				'magick_ai_core_article_plan_invalid',
				__( 'Article write plans must declare version 1 or newer.', 'magick-ai-core' ),
				array( 'status' => 422 )
			);
		}

		$artifact_error = $this->validate_article_artifacts( $plan, 'magick_ai_core_article_plan_' );
		if ( is_wp_error( $artifact_error ) ) {
			return $artifact_error;
		}

		$write_actions = is_array( $plan['write_actions'] ?? null ) ? array_values( $plan['write_actions'] ) : array();
		if ( 1 !== count( $write_actions ) || ! is_array( $write_actions[0] ?? null ) ) {
			return new WP_Error(
				'magick_ai_core_article_plan_action_count_rejected',
				__( 'P0 article write plans must contain exactly one create-draft action.', 'magick-ai-core' ),
				array( 'status' => 422 )
			);
		}

		$action = $write_actions[0];
		$draft_error = $this->validate_article_draft_action( $action, 'magick_ai_core_article_plan_' );
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
					__( 'Article write plans must include every required workflow artifact.', 'magick-ai-core' ),
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
				__( 'Article write plans must pass risk review before proposal intake.', 'magick-ai-core' ),
				array( 'status' => 422 )
			);
		}

		if ( 'high' === sanitize_key( (string) ( $risk_report['risk_level'] ?? '' ) ) ) {
			return new WP_Error(
				$error_prefix . 'risk_blocked',
				__( 'High-risk article plans must be revised before draft proposal intake.', 'magick-ai-core' ),
				array( 'status' => 422 )
			);
		}

		if ( ! empty( $risk_report['blocked_claims'] ?? array() ) ) {
			return new WP_Error(
				$error_prefix . 'blocked_claims',
				__( 'Article write plans with blocked claims cannot create proposals.', 'magick-ai-core' ),
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
		if ( 'magick-ai/create-draft' !== sanitize_text_field( (string) ( $action['target_ability_id'] ?? '' ) ) ) {
			return new WP_Error(
				$error_prefix . 'target_rejected',
				__( 'P0 article write plans may target only magick-ai/create-draft.', 'magick-ai-core' ),
				array( 'status' => 422 )
			);
		}

		$input  = is_array( $action['input'] ?? null ) ? $action['input'] : array();
		$status = sanitize_key( (string) ( $input['status'] ?? ( $input['post_status'] ?? 'draft' ) ) );
		if ( '' !== $status && 'draft' !== $status ) {
			return new WP_Error(
				$error_prefix . 'publish_rejected',
				__( 'Article write plans may create drafts only.', 'magick-ai-core' ),
				array( 'status' => 422 )
			);
		}

		if ( true === (bool) ( $input['commit'] ?? false ) || false === (bool) ( $input['dry_run'] ?? true ) ) {
			return new WP_Error(
				$error_prefix . 'commit_rejected',
				__( 'Article write plan input must remain dry-run and must not request commit.', 'magick-ai-core' ),
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
		if ( 'magick-ai/create-draft' === $target_ability_id ) {
			return $this->validate_article_draft_action( $action, 'magick_ai_core_article_media_batch_' );
		}

		$input = is_array( $action['input'] ?? null ) ? $action['input'] : array();
		if ( true === (bool) ( $input['commit'] ?? false ) || false === (bool) ( $input['dry_run'] ?? true ) ) {
			return new WP_Error(
				'magick_ai_core_article_media_batch_commit_rejected',
				__( 'Article media batch action input must remain dry-run and must not request commit.', 'magick-ai-core' ),
				array(
					'status'       => 422,
					'action_index' => $action_index,
				)
			);
		}

		if ( 'magick-ai/upload-media-from-url' === $target_ability_id ) {
			if ( ! $this->is_valid_absolute_url( $input['url'] ?? null ) ) {
				return new WP_Error(
					'magick_ai_core_article_media_batch_media_url_missing',
					__( 'Article media upload actions must include a reviewed media URL.', 'magick-ai-core' ),
					array(
						'status'       => 422,
						'action_index' => $action_index,
					)
				);
			}
			return true;
		}

		if ( 'magick-ai/update-media-details' === $target_ability_id ) {
			if ( ! $this->is_exact_output_reference( $input['attachment_id'] ?? null ) && absint( $input['attachment_id'] ?? 0 ) <= 0 ) {
				return new WP_Error(
					'magick_ai_core_article_media_batch_attachment_missing',
					__( 'Article media metadata actions must include attachment_id or an approved output reference.', 'magick-ai-core' ),
					array(
						'status'       => 422,
						'action_index' => $action_index,
					)
				);
			}
			return true;
		}

		if ( 'magick-ai/set-post-featured-image' === $target_ability_id ) {
			if ( ! $this->is_exact_output_reference( $input['post_id'] ?? null ) && absint( $input['post_id'] ?? 0 ) <= 0 ) {
				return new WP_Error(
					'magick_ai_core_article_media_batch_post_missing',
					__( 'Article featured-image actions must include post_id or an approved output reference.', 'magick-ai-core' ),
					array(
						'status'       => 422,
						'action_index' => $action_index,
					)
				);
			}
			if ( ! $this->is_exact_output_reference( $input['attachment_id'] ?? null ) && absint( $input['attachment_id'] ?? 0 ) <= 0 && ! $this->is_valid_absolute_url( $input['media_url'] ?? null ) ) {
				return new WP_Error(
					'magick_ai_core_article_media_batch_featured_image_missing',
					__( 'Article featured-image actions must include attachment_id, media_url, or an approved output reference.', 'magick-ai-core' ),
					array(
						'status'       => 422,
						'action_index' => $action_index,
					)
				);
			}
			return true;
		}

		if ( 'magick-ai/patch-post-content' === $target_ability_id ) {
			if ( ! $this->is_exact_output_reference( $input['post_id'] ?? null ) && absint( $input['post_id'] ?? 0 ) <= 0 ) {
				return new WP_Error(
					'magick_ai_core_article_media_batch_post_missing',
					__( 'Article inline-image patch actions must include post_id or an approved output reference.', 'magick-ai-core' ),
					array(
						'status'       => 422,
						'action_index' => $action_index,
					)
				);
			}
			if ( ! is_array( $input['operations'] ?? null ) ) {
				return new WP_Error(
					'magick_ai_core_article_media_batch_patch_missing',
					__( 'Article inline-image patch actions must include operations.', 'magick-ai-core' ),
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

		$scheme = strtolower( (string) parse_url( $url, PHP_URL_SCHEME ) );
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

		if ( 'magick-ai/delete-media-permanently' === $target_ability_id && ! $this->include_delete_candidates( $plan, $plan_input ) ) {
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

		if ( 'magick-ai-toolbox/build-article-write-plan' === $plan_ability_id ) {
			$preview['article_workflow'] = $this->article_workflow_preview( $plan );
		}
		if ( 'magick-ai-toolbox/build-article-batch-write-plan' === $plan_ability_id ) {
			$preview['article_batch_workflow'] = $this->article_batch_workflow_preview( $plan );
		}
		if ( 'magick-ai-toolbox/build-article-media-batch-write-plan' === $plan_ability_id ) {
			$preview['article_media_batch_workflow'] = $this->article_media_batch_workflow_preview( $plan );
		}
		if ( 'magick-ai/build-media-optimization-plan' === $plan_ability_id ) {
			$preview['media_optimization'] = $this->media_optimization_preview( $plan );
		}
		if ( 'magick-ai/build-media-rename-plan' === $plan_ability_id ) {
			$preview['media_rename'] = $this->media_rename_preview( $plan );
		}

		$title = sprintf(
			/* translators: 1: target ability id, 2: action id. */
			__( 'Plan proposal: %1$s (%2$s)', 'magick-ai-core' ),
			$target_ability_id,
			$action_id
		);

		return array(
			'ability_id' => $target_ability_id,
			'title'      => $title,
			'summary'    => sprintf(
				/* translators: 1: plan ability id, 2: batch id. */
				__( 'Created from %1$s batch %2$s. Final execution remains outside Core.', 'magick-ai-core' ),
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

		if ( 'magick-ai-toolbox/build-article-batch-write-plan' === $plan_ability_id ) {
			$preview['article_batch_workflow'] = $this->article_batch_workflow_preview( $plan );
		}
		if ( 'magick-ai-toolbox/build-article-media-batch-write-plan' === $plan_ability_id ) {
			$preview['article_media_batch_workflow'] = $this->article_media_batch_workflow_preview( $plan );
		}
		if ( 'magick-ai/build-media-optimization-plan' === $plan_ability_id ) {
			$preview['media_optimization'] = $this->media_optimization_preview( $plan );
		}
		if ( 'magick-ai/build-media-rename-plan' === $plan_ability_id ) {
			$preview['media_rename'] = $this->media_rename_preview( $plan );
		}

		return array(
			'ability_id' => sanitize_text_field( (string) ( $first['ability_id'] ?? ( $first_preview['target_ability_id'] ?? '' ) ) ),
			'title'      => sprintf(
				/* translators: 1: plan ability id, 2: batch id. */
				__( 'Plan batch proposal: %1$s (%2$s)', 'magick-ai-core' ),
				$plan_ability_id,
				$batch_id
			),
			'summary'    => sprintf(
				/* translators: 1: plan ability id, 2: action count. */
				__( 'Created from %1$s as an ordered batch with %2$d actions. Final execution remains outside Core.', 'magick-ai-core' ),
				$plan_ability_id,
				count( $batch_actions )
			),
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
	 * Returns an error for a blocked action.
	 *
	 * @param string              $code Code.
	 * @param string              $reason Reason.
	 * @param array<string,mixed> $extra Extra data.
	 * @return WP_Error
	 */
	private function blocked_error( string $code, string $reason, array $extra = array() ): WP_Error {
		return new WP_Error(
			'magick_ai_core_plan_action_blocked',
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
