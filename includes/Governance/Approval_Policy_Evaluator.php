<?php
/**
 * Approval policy evaluator.
 *
 * @package MagickAICore
 */

namespace MagickAI\Core\Governance;

use MagickAI\Core\Security\Request_Context;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Evaluates conservative approval policy decisions.
 */
final class Approval_Policy_Evaluator {
	const DECISION_MANUAL_REQUIRED = 'manual_required';
	const DECISION_AUTO_APPROVED   = 'auto_approved';
	const DECISION_BLOCKED         = 'blocked';

	const PROFILE_MANUAL        = 'manual';
	const PROFILE_GUARDED       = 'guarded';
	const PROFILE_TRUSTED_LOCAL = 'trusted_local';
	const PROFILE_BREAK_GLASS   = 'break_glass';

	const VERSION                    = 'core-approval-policy-v1';
	const OPTION_POLICY_MODE         = 'magick_ai_core_approval_policy_mode';
	const MODE_MANUAL                = 'manual';
	const MODE_DRY_RUN_GUARDED       = 'dry_run_guarded';
	const MODE_LOCAL_GUARDED         = 'local_guarded';
	const CLEANUP_BATCH_MAX_ACTIONS  = 10;
	const AUTO_APPROVAL_HOURLY_LIMIT = 20;
	const AUTO_APPROVAL_DAILY_LIMIT  = 100;

	/**
	 * Returns allowed policy mode option values.
	 *
	 * @return array<int,string>
	 */
	public static function allowed_policy_modes(): array {
		return array(
			self::MODE_MANUAL,
			self::MODE_DRY_RUN_GUARDED,
			self::MODE_LOCAL_GUARDED,
		);
	}

	/**
	 * Sanitizes a policy mode.
	 *
	 * @param string $mode Raw mode.
	 * @return string
	 */
	public static function sanitize_policy_mode( string $mode ): string {
		$mode = sanitize_key( $mode );
		return in_array( $mode, self::allowed_policy_modes(), true ) ? $mode : self::MODE_MANUAL;
	}

	/**
	 * Returns the current site policy mode.
	 *
	 * @return string
	 */
	public static function current_policy_mode(): string {
		$mode = function_exists( 'get_option' ) ? (string) get_option( self::OPTION_POLICY_MODE, self::MODE_MANUAL ) : self::MODE_MANUAL;
		return self::sanitize_policy_mode( $mode );
	}

	/**
	 * Evaluates one proposal payload.
	 *
	 * The first version is intentionally observation-only. It records a stable
	 * manual decision for every proposal without changing proposal status.
	 *
	 * @param array<string,mixed> $proposal Proposal-like payload.
	 * @return array<string,mixed>
	 */
	public function evaluate( array $proposal ): array {
		$ability_id = sanitize_text_field( (string) ( $proposal['ability_id'] ?? '' ) );
		$input      = is_array( $proposal['input'] ?? null ) ? $proposal['input'] : array();
		$preview    = is_array( $proposal['preview'] ?? null ) ? $proposal['preview'] : array();
		$caller     = is_array( $proposal['caller'] ?? null ) ? $proposal['caller'] : array();
		$source     = sanitize_key( (string) ( $caller['source'] ?? '' ) );
		$mode       = self::current_policy_mode();
		$cleanup    = $this->cleanup_batch_evaluation( $ability_id, $input, $preview, $caller );

		$reasons = array( 'default_manual_required' );
		$decision = self::DECISION_MANUAL_REQUIRED;
		$profile  = self::PROFILE_MANUAL;
		$quota    = array();

		if ( self::MODE_DRY_RUN_GUARDED === $mode || self::MODE_LOCAL_GUARDED === $mode ) {
			$profile = self::PROFILE_GUARDED;
			$reasons[] = 'mode_' . $mode;
			$reasons = array_merge( $reasons, (array) ( $cleanup['reasons'] ?? array() ) );

			if ( ! empty( $cleanup['allowed'] ) ) {
				$reasons[] = 'guarded_cleanup_candidate';
				if ( self::MODE_DRY_RUN_GUARDED === $mode ) {
					$reasons[] = 'auto_approval_dry_run_only';
				} elseif ( ! $this->caller_can_auto_approve() ) {
					$reasons[] = 'guarded_cleanup_rejected_missing_approval_scope';
				} else {
					$quota = $this->auto_approval_quota_metadata( $mode );
					if ( ! $this->auto_approval_quota_available( $quota ) ) {
						$reasons[] = 'guarded_cleanup_rejected_auto_approval_quota_exceeded';
					} else {
						$decision = self::DECISION_AUTO_APPROVED;
						$profile  = self::PROFILE_TRUSTED_LOCAL;
						$reasons[] = 'local_guarded_cleanup_auto_approved';
					}
				}
			}
		}

		if ( 'magick-ai/create-draft' === $ability_id ) {
			$reasons[] = 'create_draft_auto_approval_deferred';
		}

		if ( '' !== $source ) {
			$reasons[] = 'source_' . $source;
		}

		return array(
			'policy_decision' => $decision,
			'policy_profile'  => $profile,
			'policy_version'  => self::VERSION,
			'policy_mode'     => $mode,
			'policy_reasons'  => array_values( array_unique( array_map( 'sanitize_key', $reasons ) ) ),
			'auto_approval_quota' => $quota,
		);
	}

	/**
	 * Consumes quota for an auto-approved proposal.
	 *
	 * @param array<string,mixed> $policy Policy decision.
	 * @return bool
	 */
	public function consume_auto_approval_quota( array $policy ): bool {
		if ( self::DECISION_AUTO_APPROVED !== (string) ( $policy['policy_decision'] ?? '' ) ) {
			return true;
		}

		$quota = is_array( $policy['auto_approval_quota'] ?? null ) ? $policy['auto_approval_quota'] : array();
		foreach ( array( 'hour', 'day' ) as $window ) {
			$key   = sanitize_key( (string) ( $quota[ $window . '_key' ] ?? '' ) );
			$limit = absint( $quota[ $window . '_limit' ] ?? 0 );
			$ttl   = absint( $quota[ $window . '_ttl' ] ?? 0 );
			if ( '' === $key || $limit <= 0 || $ttl <= 0 ) {
				return false;
			}
			$current = function_exists( 'get_transient' ) ? absint( get_transient( $key ) ) : 0;
			if ( $current >= $limit ) {
				return false;
			}
			if ( function_exists( 'set_transient' ) && false === set_transient( $key, $current + 1, $ttl ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Evaluates the narrow cleanup batch candidate.
	 *
	 * @param string              $ability_id Ability id.
	 * @param array<string,mixed> $input Input.
	 * @param array<string,mixed> $preview Preview.
	 * @param array<string,mixed> $caller Caller.
	 * @return array{allowed:bool,reasons:array<int,string>}
	 */
	private function cleanup_batch_evaluation( string $ability_id, array $input, array $preview, array $caller ): array {
		$reasons = array();
		if ( 'magick-ai/trash-post' !== $ability_id ) {
			return array( 'allowed' => false, 'reasons' => array() );
		}

		if ( 'plan_to_proposal_batch' !== (string) ( $caller['source'] ?? '' ) ) {
			return array( 'allowed' => false, 'reasons' => array( 'guarded_cleanup_rejected_source' ) );
		}

		if ( 'magick-ai/build-test-content-cleanup-plan' !== (string) ( $caller['plan_ability_id'] ?? '' ) ) {
			return array( 'allowed' => false, 'reasons' => array( 'guarded_cleanup_rejected_plan_ability' ) );
		}

		$source = is_array( $preview['source'] ?? null ) ? $preview['source'] : array();
		if ( 'plan_to_proposal_batch' !== (string) ( $source['type'] ?? '' ) ) {
			return array( 'allowed' => false, 'reasons' => array( 'guarded_cleanup_rejected_preview_source' ) );
		}

		$actions = is_array( $input['write_actions'] ?? null ) ? array_values( $input['write_actions'] ) : array();
		if ( empty( $actions ) ) {
			return array( 'allowed' => false, 'reasons' => array( 'guarded_cleanup_rejected_empty_batch' ) );
		}
		if ( count( $actions ) > self::CLEANUP_BATCH_MAX_ACTIONS ) {
			return array( 'allowed' => false, 'reasons' => array( 'guarded_cleanup_rejected_batch_size' ) );
		}

		$evidence = $this->test_content_post_evidence( $preview );
		foreach ( $actions as $action ) {
			if ( ! is_array( $action ) || 'magick-ai/trash-post' !== (string) ( $action['target_ability_id'] ?? '' ) ) {
				return array( 'allowed' => false, 'reasons' => array( 'guarded_cleanup_rejected_mixed_target' ) );
			}
			if ( true !== (bool) ( $action['requires_approval'] ?? false ) || true === (bool) ( $action['commit_execution'] ?? false ) ) {
				return array( 'allowed' => false, 'reasons' => array( 'guarded_cleanup_rejected_action_contract' ) );
			}
			$action_input = is_array( $action['input'] ?? null ) ? $action['input'] : array();
			if ( false === (bool) ( $action_input['dry_run'] ?? true ) || true === (bool) ( $action_input['commit'] ?? false ) ) {
				return array( 'allowed' => false, 'reasons' => array( 'guarded_cleanup_rejected_commit_input' ) );
			}
			$post_id = absint( $action_input['post_id'] ?? 0 );
			if ( $post_id <= 0 || empty( $evidence[ $post_id ] ) ) {
				return array( 'allowed' => false, 'reasons' => array( 'guarded_cleanup_rejected_missing_test_content_evidence' ) );
			}
		}

		$reasons[] = 'guarded_cleanup_test_content_evidence';

		return array( 'allowed' => true, 'reasons' => $reasons );
	}

	/**
	 * Returns trusted test post evidence keyed by post id.
	 *
	 * @param array<string,mixed> $preview Proposal preview.
	 * @return array<int,bool>
	 */
	private function test_content_post_evidence( array $preview ): array {
		$evidence = array();
		$plan_preview = is_array( $preview['plan_preview'] ?? null ) ? $preview['plan_preview'] : array();
		foreach ( (array) ( $plan_preview['posts'] ?? array() ) as $post ) {
			if ( ! is_array( $post ) ) {
				continue;
			}
			$post_id = absint( $post['post_id'] ?? 0 );
			if ( $post_id > 0 && '' !== sanitize_text_field( (string) ( $post['matched_pattern'] ?? '' ) ) ) {
				$evidence[ $post_id ] = true;
			}
		}

		foreach ( (array) ( $preview['actions'] ?? array() ) as $action_preview ) {
			if ( ! is_array( $action_preview ) ) {
				continue;
			}
			$nested = is_array( $action_preview['preview']['plan_preview_row'] ?? null ) ? $action_preview['preview']['plan_preview_row'] : array();
			$post_id = absint( $nested['post_id'] ?? 0 );
			if ( $post_id > 0 && '' !== sanitize_text_field( (string) ( $nested['matched_pattern'] ?? '' ) ) ) {
				$evidence[ $post_id ] = true;
			}
		}

		return $evidence;
	}

	/**
	 * Returns whether the current caller can use local auto approval.
	 *
	 * @return bool
	 */
	private function caller_can_auto_approve(): bool {
		if ( function_exists( 'current_user_can' ) && current_user_can( 'manage_options' ) ) {
			return true;
		}

		return Request_Context::has_scope( 'proposals:approve' );
	}

	/**
	 * Builds quota metadata.
	 *
	 * @param string $mode Policy mode.
	 * @return array<string,mixed>
	 */
	private function auto_approval_quota_metadata( string $mode ): array {
		$subject = 'user:' . ( function_exists( 'get_current_user_id' ) ? max( 0, get_current_user_id() ) : 0 );
		$auth    = Request_Context::audit_metadata();
		if ( ! empty( $auth['app_id'] ) ) {
			$subject = 'app:' . sanitize_key( (string) $auth['app_id'] );
		}

		$base = 'magick_ai_core_auto_approval_' . sanitize_key( $mode ) . '_' . sanitize_key( str_replace( ':', '_', $subject ) );

		return array(
			'subject'    => $subject,
			'hour_key'   => $base . '_hour_' . gmdate( 'YmdH' ),
			'hour_limit' => self::AUTO_APPROVAL_HOURLY_LIMIT,
			'hour_ttl'   => defined( 'HOUR_IN_SECONDS' ) ? HOUR_IN_SECONDS : 3600,
			'day_key'    => $base . '_day_' . gmdate( 'Ymd' ),
			'day_limit'  => self::AUTO_APPROVAL_DAILY_LIMIT,
			'day_ttl'    => defined( 'DAY_IN_SECONDS' ) ? DAY_IN_SECONDS : 86400,
		);
	}

	/**
	 * Returns whether auto-approval quota is available.
	 *
	 * @param array<string,mixed> $quota Quota metadata.
	 * @return bool
	 */
	private function auto_approval_quota_available( array $quota ): bool {
		foreach ( array( 'hour', 'day' ) as $window ) {
			$key   = sanitize_key( (string) ( $quota[ $window . '_key' ] ?? '' ) );
			$limit = absint( $quota[ $window . '_limit' ] ?? 0 );
			if ( '' === $key || $limit <= 0 ) {
				return false;
			}
			$current = function_exists( 'get_transient' ) ? absint( get_transient( $key ) ) : 0;
			if ( $current >= $limit ) {
				return false;
			}
		}

		return true;
	}
}
