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
	 * Inbound plan contract validator.
	 *
	 * @var Plan_Contract_Validator
	 */
	private $validator;

	/**
	 * Constructor.
	 *
	 * @param Ability_Registry_Adapter $abilities Ability adapter.
	 * @param Proposal_Service         $proposals Proposal service.
	 * @param Audit_Log_Repository     $audit Audit repository.
	 * @param Plan_Contract_Validator  $validator Inbound plan validator.
	 */
	public function __construct( Ability_Registry_Adapter $abilities, Proposal_Service $proposals, Audit_Log_Repository $audit, Plan_Contract_Validator $validator ) {
		$this->abilities = $abilities;
		$this->proposals = $proposals;
		$this->audit     = $audit;
		$this->validator = $validator;
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
		if ( ! $this->validator->supports( $plan_ability_id ) ) {
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

		$contract_error = $this->validator->validate( $plan_ability_id, $plan );
		if ( is_wp_error( $contract_error ) ) {
			return $contract_error;
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

		$plan_ingested_event_id = $this->audit->record(
			'proposal.plan_ingested',
			array(
				'plan_ability_id'  => $plan_ability_id,
				'batch_id'         => $batch_id,
				'action_count'     => count( $write_actions ),
				'proposal_count'   => count( $created ),
				'blocked_count'    => count( $blocked_items ),
				'needs_input_count' => count( $needs_input ),
				'commit_execution' => false,
			)
		);
		if ( '' === $plan_ingested_event_id ) {
			$this->delete_created_proposals( $created );
			return new WP_Error(
				'npcink_governance_core_plan_ingest_audit_failed',
				__( 'Plan intake could not be audited.', 'npcink-governance-core' ),
				array( 'status' => 500 )
			);
		}

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
	 * Deletes proposal rows created by a failed aggregate plan ingest.
	 *
	 * @param array<int,array<string,mixed>> $created Created proposal rows.
	 * @return void
	 */
	private function delete_created_proposals( array $created ): void {
		foreach ( $created as $proposal ) {
			if ( ! is_array( $proposal ) ) {
				continue;
			}

			$proposal_id = sanitize_text_field( (string) ( $proposal['proposal_id'] ?? '' ) );
			if ( '' !== $proposal_id ) {
				$this->proposals->delete_created_proposal( $proposal_id );
			}
		}
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

		foreach ( $this->validator->article_workflow_artifact_keys() as $artifact_key ) {
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
			'max_actions'            => $this->validator->article_batch_max_actions(),
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
			'max_articles'           => $this->validator->article_media_batch_max_articles(),
			'max_actions'            => $this->validator->article_media_batch_max_actions(),
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
					array_map( array( $this->validator, 'sanitize_block_class_name' ), (array) ( $plan['allowed_classes'] ?? array() ) )
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
	 * Builds preview context for Nightly Site Inspection review plans.
	 *
	 * @param array<string,mixed> $plan Plan data.
	 * @return array<string,mixed>
	 */
	private function nightly_inspection_review_preview( array $plan ): array {
		$core_intake_package = $this->validator->nightly_inspection_core_intake_package( $plan );
		$selected_items      = $this->validator->nightly_inspection_selected_review_items( $plan );

		return array(
			'artifact_type'                 => sanitize_key( (string) ( $plan['artifact_type'] ?? ( $plan['plan_type'] ?? '' ) ) ),
			'contract_version'              => sanitize_text_field( (string) ( $plan['contract_version'] ?? '' ) ),
			'version'                       => absint( $plan['version'] ?? 0 ),
			'cloud_run_id'                  => sanitize_text_field( (string) ( $plan['cloud_run_id'] ?? ( $plan['batch_id'] ?? '' ) ) ),
			'runtime_owner'                 => sanitize_key( (string) ( $plan['runtime_owner'] ?? '' ) ),
			'agent_id'                      => sanitize_key( (string) ( $plan['agent_id'] ?? '' ) ),
			'agent_version'                 => sanitize_text_field( (string) ( $plan['agent_version'] ?? '' ) ),
			'workflow'                      => sanitize_key( (string) ( $plan['workflow'] ?? '' ) ),
			'intent'                        => sanitize_key( (string) ( $plan['intent'] ?? '' ) ),
			'local_next_action'             => sanitize_key( (string) ( $plan['local_next_action'] ?? '' ) ),
			'evidence_gate_status'          => sanitize_key( (string) ( $plan['evidence_gate_status'] ?? '' ) ),
			'evidence_refs'                 => $this->sanitize_payload( $plan['evidence_refs'] ?? array() ),
			'evidence_ref_count'            => count( is_array( $plan['evidence_refs'] ?? null ) ? $plan['evidence_refs'] : array() ),
			'core_intake_package'           => $this->sanitize_payload( $core_intake_package ),
			'selected_review_item_ids' => array_values(
				array_filter(
					array_map(
						static function ( array $item ): string {
							return sanitize_text_field( (string) ( $item['action_id'] ?? ( $item['item_id'] ?? '' ) ) );
						},
						$selected_items
					)
				)
			),
			'selected_review_items'         => $this->sanitize_payload( $selected_items ),
			'blocked_outputs'               => $this->sanitize_payload( $plan['blocked_outputs'] ?? array() ),
			'operator_next_action'          => 'draft_selected_review_item_before_commit_preflight',
			'needs_input_resolution_owner'  => 'toolbox_morning_brief_operator',
			'resubmission_required'         => true,
			'core_amendment_supported'      => false,
			'final_write_path'              => 'core_proposal_required',
			'direct_wordpress_write'        => false,
			'cloud_scheduler_truth'         => false,
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
	 * Builds preview context for article audio adoption plans.
	 *
	 * @param array<string,mixed> $plan Plan data.
	 * @return array<string,mixed>
	 */
	private function article_audio_adoption_preview( array $plan ): array {
		$action = is_array( $plan['write_actions'][0] ?? null ) ? $plan['write_actions'][0] : array();
		$input  = is_array( $action['input'] ?? null ) ? $action['input'] : array();

		return array(
			'artifact_type'          => sanitize_text_field( (string) ( $plan['artifact_type'] ?? '' ) ),
			'post_id'                => absint( $input['post_id'] ?? ( $plan['target_post_id'] ?? 0 ) ),
			'audio_kind'             => sanitize_key( (string) ( $input['audio_kind'] ?? '' ) ),
			'audio_url'              => esc_url_raw( (string) ( $input['audio_url'] ?? '' ) ),
			'audio_title'            => sanitize_text_field( (string) ( $input['audio_title'] ?? '' ) ),
			'duration_seconds'       => is_numeric( $input['duration_seconds'] ?? null ) ? (float) $input['duration_seconds'] : 0.0,
			'provider'               => sanitize_key( (string) ( $input['provider'] ?? '' ) ),
			'model'                  => sanitize_text_field( (string) ( $input['model'] ?? '' ) ),
			'trace_id'               => sanitize_text_field( (string) ( $input['trace_id'] ?? '' ) ),
			'import_media'           => ! empty( $input['import_media'] ),
			'media_file_name'        => sanitize_text_field( (string) ( $input['media_file_name'] ?? '' ) ),
			'storage_mode'           => ! empty( $input['import_media'] ) ? 'wordpress_media_library' : 'remote_url',
			'final_write_path'       => 'core_proposal_required',
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
		$action_preview  = is_array( $action['preview'] ?? null ) ? $this->sanitize_payload( $action['preview'] ) : array();
		if ( ! empty( $action_preview ) ) {
			$matched_preview = array_merge( $matched_preview, $action_preview );
		}
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
		if ( 'npcink-toolbox/build-nightly-inspection-review-plan' === $plan_ability_id ) {
			$preview['nightly_inspection_review'] = $this->nightly_inspection_review_preview( $plan );
		}
		if ( 'npcink-abilities-toolkit/build-content-metadata-apply-plan' === $plan_ability_id ) {
			$preview['content_metadata_apply'] = $this->content_metadata_apply_preview( $plan );
		}
		if ( 'npcink-abilities-toolkit/build-media-alt-apply-plan' === $plan_ability_id ) {
			$preview['media_alt_apply'] = $matched_preview;
		}
		if ( 'npcink-abilities-toolkit/build-article-audio-adoption-plan' === $plan_ability_id ) {
			$preview['article_audio_adoption'] = $this->article_audio_adoption_preview( $plan );
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
		$proposal_ready = $proposal_ready && empty( $needs_input ) && empty( $preflight_blockers );
		$batch_review_summary = $this->batch_review_summary(
			$plan_ability_id,
			$batch_id,
			$batch_actions,
			$target_ids,
			$needs_input,
			$preflight_blockers,
			$warnings,
			$proposal_ready
		);
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
			'batch_review_summary' => $batch_review_summary,
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
			'proposal_ready'     => $proposal_ready,
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
		if ( 'npcink-abilities-toolkit/build-content-metadata-apply-plan' === $plan_ability_id ) {
			$preview['content_metadata_apply'] = $this->content_metadata_apply_preview( $plan );
		}
		if ( 'npcink-abilities-toolkit/build-article-audio-adoption-plan' === $plan_ability_id ) {
			$preview['article_audio_adoption'] = $this->article_audio_adoption_preview( $plan );
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
	 * Builds the operator-facing review summary for a grouped proposal.
	 *
	 * @param string                   $plan_ability_id Plan ability id.
	 * @param string                   $batch_id Batch id.
	 * @param array<int,array<string,mixed>> $batch_actions Batch actions.
	 * @param array<int,string>        $target_ids Target ability ids.
	 * @param array<int,string>        $needs_input Required input fields.
	 * @param array<int,mixed>         $preflight_blockers Preflight blockers.
	 * @param array<string,mixed>      $warnings Plan warnings.
	 * @param bool                     $proposal_ready Whether the grouped proposal can pass item preflight.
	 * @return array<string,mixed>
	 */
	private function batch_review_summary( string $plan_ability_id, string $batch_id, array $batch_actions, array $target_ids, array $needs_input, array $preflight_blockers, array $warnings, bool $proposal_ready ): array {
		$action_count = count( $batch_actions );
		$blocked_count = count( $preflight_blockers );
		$needs_input_count = count( $needs_input );
		$warning_count = 0;
		foreach ( $warnings as $warning_items ) {
			if ( is_array( $warning_items ) ) {
				$warning_count += count( $warning_items );
			}
		}

		$operator_next_action = 'review_and_approve_or_reject';
		if ( ! $proposal_ready || $blocked_count > 0 || $needs_input_count > 0 ) {
			$operator_next_action = 'resolve_blocked_items_before_commit_preflight';
		} elseif ( $warning_count > 0 ) {
			$operator_next_action = 'review_warnings_before_approval';
		}

		return array(
			'summary_version'     => 'core-batch-review-summary-v1',
			'plan_ability_id'     => $plan_ability_id,
			'batch_id'            => $batch_id,
			'action_count'        => $action_count,
			'executable_count'    => $proposal_ready ? $action_count : 0,
			'blocked_count'       => $blocked_count,
			'needs_input_count'   => $needs_input_count,
			'warning_count'       => $warning_count,
			'target_ability_ids'  => $target_ids,
			'proposal_ready'      => $proposal_ready,
			'retryable'           => ! $proposal_ready,
			'operator_next_action' => $operator_next_action,
			'final_execution_owner' => 'adapter_after_core_preflight',
			'core_execution'      => false,
			'commit_execution'    => false,
			'blocked_items'       => $this->sanitize_payload( $preflight_blockers ),
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
