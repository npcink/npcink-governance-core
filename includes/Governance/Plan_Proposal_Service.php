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
		'magick-ai/build-content-inventory-fix-plan' => true,
		'magick-ai/build-test-content-cleanup-plan'  => true,
		'magick-ai/build-media-inventory-fix-plan'   => true,
	);

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

		if ( $this->plan_requires_batch_proposal( $write_actions ) ) {
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
	 * @param array<int,mixed> $write_actions Write actions.
	 * @return bool
	 */
	private function plan_requires_batch_proposal( array $write_actions ): bool {
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
			),
			'action_count'       => count( $batch_actions ),
			'action_ids'         => $action_ids,
			'target_ability_ids' => $target_ids,
			'actions'            => $action_previews,
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
