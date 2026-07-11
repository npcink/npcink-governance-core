<?php
/**
 * Approval policy evaluator.
 *
 * @package NpcinkGovernanceCore
 */

namespace Npcink\GovernanceCore\Governance;

use Npcink\GovernanceCore\Security\Request_Context;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Strategy contract for one approval policy mode.
 */
interface Approval_Policy_Strategy {
	/**
	 * Evaluates one proposal payload.
	 *
	 * @param array<string,mixed>    $proposal Proposal-like payload.
	 * @param string                 $mode Current policy mode.
	 * @param Approval_Policy_Evaluator $evaluator Shared evaluator helpers.
	 * @return array<string,mixed>
	 */
	public function evaluate( array $proposal, string $mode, Approval_Policy_Evaluator $evaluator ): array;
}

/**
 * Requires explicit approval for every proposal.
 */
final class Manual_Approval_Policy_Strategy implements Approval_Policy_Strategy {
	/**
	 * Evaluates one proposal payload.
	 *
	 * @param array<string,mixed>    $proposal Proposal-like payload.
	 * @param string                 $mode Current policy mode.
	 * @param Approval_Policy_Evaluator $evaluator Shared evaluator helpers.
	 * @return array<string,mixed>
	 */
	public function evaluate( array $proposal, string $mode, Approval_Policy_Evaluator $evaluator ): array {
		$ability_id = sanitize_text_field( (string) ( $proposal['ability_id'] ?? '' ) );
		$reasons    = array( 'default_manual_required', 'mode_' . $mode );
		if ( 'npcink-abilities-toolkit/create-draft' === $ability_id ) {
			$reasons[] = 'create_draft_auto_approval_deferred';
		}

		return $evaluator->policy_result(
			$proposal,
			$mode,
			Approval_Policy_Evaluator::DECISION_MANUAL_REQUIRED,
			Approval_Policy_Evaluator::PROFILE_MANUAL,
			$reasons
		);
	}
}

/**
 * Auto-approves only narrow, evidenced, quota-bound candidates.
 */
final class Smart_Guarded_Approval_Policy_Strategy implements Approval_Policy_Strategy {
	/**
	 * Evaluates one proposal payload.
	 *
	 * @param array<string,mixed>    $proposal Proposal-like payload.
	 * @param string                 $mode Current policy mode.
	 * @param Approval_Policy_Evaluator $evaluator Shared evaluator helpers.
	 * @return array<string,mixed>
	 */
	public function evaluate( array $proposal, string $mode, Approval_Policy_Evaluator $evaluator ): array {
		$ability_id    = sanitize_text_field( (string) ( $proposal['ability_id'] ?? '' ) );
		$input         = is_array( $proposal['input'] ?? null ) ? $proposal['input'] : array();
		$preview       = is_array( $proposal['preview'] ?? null ) ? $proposal['preview'] : array();
		$caller        = is_array( $proposal['caller'] ?? null ) ? $proposal['caller'] : array();
		$cleanup          = $evaluator->cleanup_batch_evaluation( $ability_id, $input, $preview, $caller );
		$create_draft     = $evaluator->create_draft_evaluation( $ability_id, $input );
		$article_audio    = $evaluator->article_audio_adoption_evaluation( $ability_id, $input, $preview, $caller );
		$media_alt        = $evaluator->media_alt_text_evaluation( $ability_id, $input, $preview );
		$media_derivative = $evaluator->media_derivative_adoption_evaluation( $ability_id, $input, $preview );

		$reasons  = array( 'default_manual_required', 'mode_' . $mode );
		$decision = Approval_Policy_Evaluator::DECISION_MANUAL_REQUIRED;
		$profile  = Approval_Policy_Evaluator::PROFILE_GUARDED;
		$quota    = array();

		$reasons = array_merge( $reasons, (array) ( $cleanup['reasons'] ?? array() ) );
		$reasons = array_merge( $reasons, (array) ( $create_draft['reasons'] ?? array() ) );
		$reasons = array_merge( $reasons, (array) ( $article_audio['reasons'] ?? array() ) );
		$reasons = array_merge( $reasons, (array) ( $media_alt['reasons'] ?? array() ) );
		$reasons = array_merge( $reasons, (array) ( $media_derivative['reasons'] ?? array() ) );

		if ( ! empty( $cleanup['allowed'] ) ) {
			$reasons[] = 'guarded_cleanup_candidate';
			if ( ! $evaluator->caller_can_auto_approve() ) {
				$reasons[] = 'guarded_cleanup_rejected_missing_approval_scope';
			} else {
				$quota = $evaluator->auto_approval_quota_metadata( $mode );
				if ( ! $evaluator->auto_approval_quota_available( $quota ) ) {
					$reasons[] = 'guarded_cleanup_rejected_auto_approval_quota_exceeded';
				} else {
					$decision  = Approval_Policy_Evaluator::DECISION_AUTO_APPROVED;
					$profile   = Approval_Policy_Evaluator::PROFILE_TRUSTED_LOCAL;
					$reasons[] = 'smart_guarded_cleanup_auto_approved';
				}
			}
		} elseif ( ! empty( $create_draft['allowed'] ) ) {
			$reasons[] = 'guarded_create_draft_candidate';
			if ( ! $evaluator->caller_can_auto_approve() ) {
				$reasons[] = 'guarded_create_draft_rejected_missing_approval_scope';
			} else {
				$quota = $evaluator->auto_approval_quota_metadata( $mode );
				if ( ! $evaluator->auto_approval_quota_available( $quota ) ) {
					$reasons[] = 'guarded_create_draft_rejected_auto_approval_quota_exceeded';
				} else {
					$decision  = Approval_Policy_Evaluator::DECISION_AUTO_APPROVED;
					$profile   = Approval_Policy_Evaluator::PROFILE_TRUSTED_LOCAL;
					$reasons[] = 'smart_guarded_create_draft_auto_approved';
				}
			}
		} elseif ( ! empty( $article_audio['allowed'] ) ) {
			$reasons[] = 'guarded_article_audio_candidate';
			if ( ! $evaluator->caller_can_auto_approve() ) {
				$reasons[] = 'guarded_article_audio_rejected_missing_approval_scope';
			} else {
				$quota = $evaluator->auto_approval_quota_metadata( $mode );
				if ( ! $evaluator->auto_approval_quota_available( $quota ) ) {
					$reasons[] = 'guarded_article_audio_rejected_auto_approval_quota_exceeded';
				} else {
					$decision  = Approval_Policy_Evaluator::DECISION_AUTO_APPROVED;
					$profile   = Approval_Policy_Evaluator::PROFILE_TRUSTED_LOCAL;
					$reasons[] = 'smart_guarded_article_audio_auto_approved';
				}
			}
		} elseif ( ! empty( $media_alt['allowed'] ) ) {
			$reasons[] = 'guarded_media_alt_candidate';
			if ( ! $evaluator->caller_can_auto_approve() ) {
				$reasons[] = 'guarded_media_alt_rejected_missing_approval_scope';
			} else {
				$quota = $evaluator->auto_approval_quota_metadata( $mode );
				if ( ! $evaluator->auto_approval_quota_available( $quota ) ) {
					$reasons[] = 'guarded_media_alt_rejected_auto_approval_quota_exceeded';
				} else {
					$decision  = Approval_Policy_Evaluator::DECISION_AUTO_APPROVED;
					$profile   = Approval_Policy_Evaluator::PROFILE_TRUSTED_LOCAL;
					$reasons[] = 'smart_guarded_media_alt_auto_approved';
				}
			}
		} elseif ( ! empty( $media_derivative['allowed'] ) ) {
			$reasons[] = 'guarded_media_derivative_candidate';
			if ( ! $evaluator->caller_can_auto_approve() ) {
				$reasons[] = 'guarded_media_derivative_rejected_missing_approval_scope';
			} else {
				$quota = $evaluator->auto_approval_quota_metadata( $mode );
				if ( ! $evaluator->auto_approval_quota_available( $quota ) ) {
					$reasons[] = 'guarded_media_derivative_rejected_auto_approval_quota_exceeded';
				} else {
					$decision  = Approval_Policy_Evaluator::DECISION_AUTO_APPROVED;
					$profile   = Approval_Policy_Evaluator::PROFILE_TRUSTED_LOCAL;
					$reasons[] = 'smart_guarded_media_derivative_auto_approved';
				}
			}
		}

		return $evaluator->policy_result( $proposal, $mode, $decision, $profile, $reasons, $quota );
	}
}

/**
 * Development-only policy that approves every proposal after explicit opt-in.
 */
final class Dev_Allow_All_Approval_Policy_Strategy implements Approval_Policy_Strategy {
	/**
	 * Evaluates one proposal payload.
	 *
	 * @param array<string,mixed>    $proposal Proposal-like payload.
	 * @param string                 $mode Current policy mode.
	 * @param Approval_Policy_Evaluator $evaluator Shared evaluator helpers.
	 * @return array<string,mixed>
	 */
	public function evaluate( array $proposal, string $mode, Approval_Policy_Evaluator $evaluator ): array {
		$reasons  = array( 'default_manual_required', 'mode_' . $mode );
		$decision = Approval_Policy_Evaluator::DECISION_MANUAL_REQUIRED;
		$profile  = Approval_Policy_Evaluator::PROFILE_GUARDED;
		$quota    = array();

		if ( ! $evaluator->dev_allow_all_enabled() ) {
			$reasons[] = 'dev_allow_all_rejected_disabled';
		} elseif ( ! $evaluator->caller_can_auto_approve() ) {
			$reasons[] = 'dev_allow_all_rejected_missing_approval_scope';
		} else {
			$quota = $evaluator->auto_approval_quota_metadata( $mode );
			if ( ! $evaluator->auto_approval_quota_available( $quota ) ) {
				$reasons[] = 'dev_allow_all_rejected_auto_approval_quota_exceeded';
			} else {
				$decision  = Approval_Policy_Evaluator::DECISION_AUTO_APPROVED;
				$profile   = Approval_Policy_Evaluator::PROFILE_TRUSTED_LOCAL;
				$reasons[] = 'dev_allow_all_auto_approved';
				$reasons[] = 'commit_preflight_still_required';
			}
		}

		return $evaluator->policy_result( $proposal, $mode, $decision, $profile, $reasons, $quota );
	}
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
	const OPTION_POLICY_MODE         = 'npcink_governance_core_approval_policy_mode';
	const MODE_MANUAL                = 'manual';
	const MODE_SMART_GUARDED         = 'smart_guarded';
	const MODE_DEV_ALLOW_ALL         = 'dev_allow_all';
	const CLEANUP_BATCH_MAX_ACTIONS  = 10;
	const CREATE_DRAFT_MAX_CONTENT_BYTES = 20000;
	const AUTO_APPROVAL_HOURLY_LIMIT = 20;
	const AUTO_APPROVAL_DAILY_LIMIT  = 100;
	const AUTO_APPROVAL_TRANSIENT_PREFIX = 'npcink_governance_core_auto_approval_';

	/**
	 * Returns allowed policy mode option values.
	 *
	 * @return array<int,string>
	 */
	public static function allowed_policy_modes(): array {
		return array(
			self::MODE_MANUAL,
			self::MODE_SMART_GUARDED,
			self::MODE_DEV_ALLOW_ALL,
		);
	}

	/**
	 * Returns whether a policy mode is part of the closed supported set.
	 *
	 * @param string $mode Raw mode.
	 * @return bool
	 */
	public static function is_allowed_policy_mode( string $mode ): bool {
		return in_array( sanitize_key( $mode ), self::allowed_policy_modes(), true );
	}

	/**
	 * Sanitizes a policy mode.
	 *
	 * @param string $mode Raw mode.
	 * @return string
	 */
	public static function sanitize_policy_mode( string $mode ): string {
		$mode = sanitize_key( $mode );
		return self::is_allowed_policy_mode( $mode ) ? $mode : self::MODE_MANUAL;
	}

	/**
	 * Returns the stored site policy mode before fallback.
	 *
	 * @return string
	 */
	public static function stored_policy_mode(): string {
		$mode = function_exists( 'get_option' ) ? (string) get_option( self::OPTION_POLICY_MODE, self::MODE_MANUAL ) : self::MODE_MANUAL;
		return sanitize_key( $mode );
	}

	/**
	 * Returns the current site policy mode.
	 *
	 * @return string
	 */
	public static function current_policy_mode(): string {
		return self::sanitize_policy_mode( self::stored_policy_mode() );
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
		$mode = self::current_policy_mode();
		return $this->strategy_for_mode( $mode )->evaluate( $proposal, $mode, $this );
	}

	/**
	 * Builds a normalized policy result.
	 *
	 * @param array<string,mixed> $proposal Proposal-like payload.
	 * @param string              $mode Current policy mode.
	 * @param string              $decision Decision.
	 * @param string              $profile Profile.
	 * @param array<int,string>   $reasons Reason keys.
	 * @param array<string,mixed> $quota Quota metadata.
	 * @return array<string,mixed>
	 */
	public function policy_result( array $proposal, string $mode, string $decision, string $profile, array $reasons, array $quota = array() ): array {
		$caller = is_array( $proposal['caller'] ?? null ) ? $proposal['caller'] : array();
		$source = sanitize_key( (string) ( $caller['source'] ?? '' ) );
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
	 * Returns the strategy for a policy mode.
	 *
	 * @param string $mode Policy mode.
	 * @return Approval_Policy_Strategy
	 */
	private function strategy_for_mode( string $mode ): Approval_Policy_Strategy {
		if ( self::MODE_SMART_GUARDED === $mode ) {
			return new Smart_Guarded_Approval_Policy_Strategy();
		}

		if ( self::MODE_DEV_ALLOW_ALL === $mode ) {
			return new Dev_Allow_All_Approval_Policy_Strategy();
		}

		return new Manual_Approval_Policy_Strategy();
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
			$key_suffix = $this->auto_approval_transient_key_suffix( $quota, $window );
			$limit      = absint( $quota[ $window . '_limit' ] ?? 0 );
			$ttl        = absint( $quota[ $window . '_ttl' ] ?? 0 );
			if ( '' === $key_suffix || $limit <= 0 || $ttl <= 0 ) {
				return false;
			}
			$current = function_exists( 'get_transient' ) ? absint( get_transient( self::AUTO_APPROVAL_TRANSIENT_PREFIX . $key_suffix ) ) : 0;
			if ( $current >= $limit ) {
				return false;
			}
			if ( function_exists( 'set_transient' ) && false === set_transient( self::AUTO_APPROVAL_TRANSIENT_PREFIX . $key_suffix, $current + 1, $ttl ) ) {
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
	public function cleanup_batch_evaluation( string $ability_id, array $input, array $preview, array $caller ): array {
		$reasons = array();
		if ( 'npcink-abilities-toolkit/trash-post' !== $ability_id ) {
			return array( 'allowed' => false, 'reasons' => array() );
		}

		if ( 'plan_to_proposal_batch' !== (string) ( $caller['source'] ?? '' ) ) {
			return array( 'allowed' => false, 'reasons' => array( 'guarded_cleanup_rejected_source' ) );
		}

		if ( 'npcink-abilities-toolkit/build-nonproduction-content-cleanup-plan' !== (string) ( $caller['plan_ability_id'] ?? '' ) ) {
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
			if ( ! is_array( $action ) || 'npcink-abilities-toolkit/trash-post' !== (string) ( $action['target_ability_id'] ?? '' ) ) {
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
	 * Evaluates the narrow local create-draft candidate.
	 *
	 * @param string              $ability_id Ability id.
	 * @param array<string,mixed> $input Input.
	 * @return array{allowed:bool,reasons:array<int,string>}
	 */
	public function create_draft_evaluation( string $ability_id, array $input ): array {
		if ( 'npcink-abilities-toolkit/create-draft' !== $ability_id ) {
			return array( 'allowed' => false, 'reasons' => array() );
		}

		if ( absint( $input['post_id'] ?? ( $input['ID'] ?? ( $input['id'] ?? 0 ) ) ) > 0 ) {
			return array( 'allowed' => false, 'reasons' => array( 'guarded_create_draft_rejected_existing_target' ) );
		}

		$post_type = sanitize_key( (string) ( $input['post_type'] ?? 'post' ) );
		if ( 'post' !== $post_type ) {
			return array( 'allowed' => false, 'reasons' => array( 'guarded_create_draft_rejected_post_type' ) );
		}

		$status = sanitize_key( (string) ( $input['status'] ?? 'draft' ) );
		if ( 'draft' !== $status ) {
			return array( 'allowed' => false, 'reasons' => array( 'guarded_create_draft_rejected_status' ) );
		}

		if ( '' === trim( sanitize_text_field( (string) ( $input['title'] ?? '' ) ) ) ) {
			return array( 'allowed' => false, 'reasons' => array( 'guarded_create_draft_rejected_title_missing' ) );
		}

		if ( false === (bool) ( $input['dry_run'] ?? true ) || true === (bool) ( $input['commit'] ?? false ) ) {
			return array( 'allowed' => false, 'reasons' => array( 'guarded_create_draft_rejected_commit_input' ) );
		}

		foreach ( array( 'publish_at', 'schedule_at', 'scheduled_at', 'post_date', 'post_date_gmt' ) as $schedule_key ) {
			if ( '' !== trim( sanitize_text_field( (string) ( $input[ $schedule_key ] ?? '' ) ) ) ) {
				return array( 'allowed' => false, 'reasons' => array( 'guarded_create_draft_rejected_schedule' ) );
			}
		}

		if ( strlen( (string) ( $input['content'] ?? '' ) ) > self::CREATE_DRAFT_MAX_CONTENT_BYTES ) {
			return array( 'allowed' => false, 'reasons' => array( 'guarded_create_draft_rejected_content_size' ) );
		}

		return array(
			'allowed' => true,
			'reasons' => array( 'guarded_create_draft_draft_only' ),
		);
	}

	/**
	 * Evaluates the narrow article audio adoption candidate.
	 *
	 * @param string              $ability_id Ability id.
	 * @param array<string,mixed> $input Input.
	 * @param array<string,mixed> $preview Preview.
	 * @param array<string,mixed> $caller Caller.
	 * @return array{allowed:bool,reasons:array<int,string>}
	 */
	public function article_audio_adoption_evaluation( string $ability_id, array $input, array $preview, array $caller ): array {
		if ( 'npcink-abilities-toolkit/adopt-article-audio' !== $ability_id ) {
			return array( 'allowed' => false, 'reasons' => array() );
		}

		if ( 'plan_to_proposal' !== (string) ( $caller['source'] ?? '' ) ) {
			return array( 'allowed' => false, 'reasons' => array( 'guarded_article_audio_rejected_source' ) );
		}

		if ( 'npcink-abilities-toolkit/build-article-audio-adoption-plan' !== (string) ( $caller['plan_ability_id'] ?? '' ) ) {
			return array( 'allowed' => false, 'reasons' => array( 'guarded_article_audio_rejected_plan_ability' ) );
		}

		$source = is_array( $preview['source'] ?? null ) ? $preview['source'] : array();
		if ( 'plan_to_proposal' !== (string) ( $source['type'] ?? '' ) ) {
			return array( 'allowed' => false, 'reasons' => array( 'guarded_article_audio_rejected_preview_source' ) );
		}

		if ( absint( $input['post_id'] ?? 0 ) <= 0 || '' === trim( sanitize_text_field( (string) ( $input['audio_url'] ?? '' ) ) ) ) {
			return array( 'allowed' => false, 'reasons' => array( 'guarded_article_audio_rejected_missing_required_input' ) );
		}

		$kind = sanitize_key( (string) ( $input['audio_kind'] ?? '' ) );
		if ( ! in_array( $kind, array( 'article_narration', 'article_audio_summary' ), true ) ) {
			return array( 'allowed' => false, 'reasons' => array( 'guarded_article_audio_rejected_kind' ) );
		}

		if ( false === (bool) ( $input['dry_run'] ?? true ) || true === (bool) ( $input['commit'] ?? false ) ) {
			return array( 'allowed' => false, 'reasons' => array( 'guarded_article_audio_rejected_commit_input' ) );
		}

		$allowed_input_keys = array_fill_keys(
			array( 'post_id', 'audio_url', 'audio_title', 'audio_kind', 'duration_seconds', 'mime_type', 'source_content_hash', 'source_word_count', 'source_generated_at', 'provider', 'model', 'trace_id', 'import_media', 'media_file_name', 'dry_run', 'commit', 'idempotency_key' ),
			true
		);
		foreach ( array_keys( $input ) as $key ) {
			if ( ! isset( $allowed_input_keys[ (string) $key ] ) ) {
				return array( 'allowed' => false, 'reasons' => array( 'guarded_article_audio_rejected_input_key' ) );
			}
		}

		return array(
			'allowed' => true,
			'reasons' => array( ! empty( $input['import_media'] ) ? 'guarded_article_audio_single_post_media_import' : 'guarded_article_audio_single_post_meta_only' ),
		);
	}

	/**
	 * Evaluates a narrow media ALT-only update candidate.
	 *
	 * @param string              $ability_id Ability id.
	 * @param array<string,mixed> $input Input.
	 * @param array<string,mixed> $preview Preview.
	 * @return array{allowed:bool,reasons:array<int,string>}
	 */
	public function media_alt_text_evaluation( string $ability_id, array $input, array $preview ): array {
		if ( 'npcink-abilities-toolkit/update-media-details' !== $ability_id ) {
			return array( 'allowed' => false, 'reasons' => array() );
		}

		if ( absint( $input['attachment_id'] ?? 0 ) <= 0 ) {
			return array( 'allowed' => false, 'reasons' => array( 'guarded_media_alt_rejected_attachment' ) );
		}

		$alt = trim( sanitize_text_field( (string) ( $input['alt'] ?? '' ) ) );
		if ( '' === $alt || strlen( $alt ) < 3 || strlen( $alt ) > 160 ) {
			return array( 'allowed' => false, 'reasons' => array( 'guarded_media_alt_rejected_alt_quality' ) );
		}

		if ( preg_match( '/https?:\/\/|generated\s+by|prompt\s*:|model\s*:|provider\s*:|profile\s*:/i', $alt ) ) {
			return array( 'allowed' => false, 'reasons' => array( 'guarded_media_alt_rejected_runtime_or_source_text' ) );
		}

		if ( false === (bool) ( $input['dry_run'] ?? true ) || true === (bool) ( $input['commit'] ?? false ) ) {
			return array( 'allowed' => false, 'reasons' => array( 'guarded_media_alt_rejected_commit_input' ) );
		}
		if ( ! array_key_exists( 'expected_current_alt', $input ) || '' !== (string) $input['expected_current_alt'] ) {
			return array( 'allowed' => false, 'reasons' => array( 'guarded_media_alt_rejected_missing_only_guard' ) );
		}
		if ( true !== ( $input['operator_visual_review_confirmed'] ?? false ) ) {
			return array( 'allowed' => false, 'reasons' => array( 'guarded_media_alt_rejected_input_visual_confirmation' ) );
		}

		$allowed_input_keys = array_fill_keys(
			array( 'attachment_id', 'alt', 'expected_current_alt', 'operator_visual_review_confirmed', 'dry_run', 'commit', 'idempotency_key' ),
			true
		);
		foreach ( array_keys( $input ) as $key ) {
			if ( ! isset( $allowed_input_keys[ (string) $key ] ) ) {
				return array( 'allowed' => false, 'reasons' => array( 'guarded_media_alt_rejected_non_alt_field' ) );
			}
		}

		$source   = is_array( $preview['source'] ?? null ) ? $preview['source'] : array();
		$evidence = is_array( $preview['media_alt_apply'] ?? null ) ? $preview['media_alt_apply'] : array();
		if ( 'npcink-abilities-toolkit/build-media-alt-apply-plan' !== (string) ( $source['plan_ability_id'] ?? '' ) || 'media_alt_apply_plan_item' !== sanitize_key( (string) ( $evidence['artifact_type'] ?? '' ) ) || 'media_alt_apply_plan.v1' !== (string) ( $evidence['contract_version'] ?? '' ) ) {
			return array( 'allowed' => false, 'reasons' => array( 'guarded_media_alt_rejected_preview_source' ) );
		}

		if ( 'media_alt_caption_review_set.v1' !== (string) ( $evidence['review_set_contract'] ?? '' ) ) {
			return array( 'allowed' => false, 'reasons' => array( 'guarded_media_alt_rejected_review_contract' ) );
		}

		$current_status = sanitize_key( (string) ( $evidence['current_alt_status'] ?? '' ) );
		if ( 'missing' !== $current_status || '' !== (string) ( $evidence['expected_current_alt'] ?? '' ) ) {
			return array( 'allowed' => false, 'reasons' => array( 'guarded_media_alt_rejected_current_status' ) );
		}

		if ( true !== ( $evidence['operator_reviewed'] ?? false ) || true !== ( $evidence['operator_visual_review_confirmed'] ?? false ) || absint( $evidence['attachment_id'] ?? 0 ) !== absint( $input['attachment_id'] ?? 0 ) || '' === trim( sanitize_text_field( (string) ( $evidence['source_item_id'] ?? '' ) ) ) ) {
			return array( 'allowed' => false, 'reasons' => array( 'guarded_media_alt_rejected_operator_review' ) );
		}

		$proposed_alt = trim( sanitize_text_field( (string) ( $evidence['proposed_alt'] ?? '' ) ) );
		if ( '' !== $proposed_alt && $proposed_alt !== $alt ) {
			return array( 'allowed' => false, 'reasons' => array( 'guarded_media_alt_rejected_preview_mismatch' ) );
		}

		return array(
			'allowed' => true,
			'reasons' => array( 'guarded_media_alt_single_attachment', 'guarded_media_alt_missing_only', 'guarded_media_alt_reviewed_alt_only', 'guarded_media_alt_plan_contract_v1' ),
		);
	}

	/**
	 * Evaluates the narrow Cloud media derivative adoption candidate.
	 *
	 * @param string              $ability_id Ability id.
	 * @param array<string,mixed> $input Input.
	 * @param array<string,mixed> $preview Preview.
	 * @return array{allowed:bool,reasons:array<int,string>}
	 */
	public function media_derivative_adoption_evaluation( string $ability_id, array $input, array $preview ): array {
		if ( 'npcink-abilities-toolkit/adopt-cloud-media-derivative' !== $ability_id ) {
			return array( 'allowed' => false, 'reasons' => array() );
		}

		if ( absint( $input['attachment_id'] ?? 0 ) <= 0 ) {
			return array( 'allowed' => false, 'reasons' => array( 'guarded_media_derivative_rejected_attachment' ) );
		}

		if ( false === (bool) ( $input['dry_run'] ?? true ) || true === (bool) ( $input['commit'] ?? false ) ) {
			return array( 'allowed' => false, 'reasons' => array( 'guarded_media_derivative_rejected_commit_input' ) );
		}

		if ( ! empty( $input['write_actions'] ) || ! empty( $input['content_reference_repairs'] ) ) {
			return array( 'allowed' => false, 'reasons' => array( 'guarded_media_derivative_rejected_multi_action' ) );
		}

		$artifact = is_array( $input['derivative_artifact'] ?? null ) ? $input['derivative_artifact'] : array();
		if ( '' === trim( sanitize_text_field( (string) ( $artifact['artifact_id'] ?? ( $artifact['id'] ?? '' ) ) ) ) ) {
			return array( 'allowed' => false, 'reasons' => array( 'guarded_media_derivative_rejected_artifact' ) );
		}

		$preview_media = is_array( $preview['media_optimization'] ?? null ) ? $preview['media_optimization'] : array();
		$source        = is_array( $preview['source'] ?? null ) ? $preview['source'] : array();
		$artifact_type = sanitize_key( (string) ( $preview['artifact_type'] ?? ( $preview_media['artifact_type'] ?? ( $preview['type'] ?? '' ) ) ) );
		if ( 'media_optimization_plan' !== $artifact_type && 'npcink-abilities-toolkit/build-media-optimization-plan' !== (string) ( $source['plan_ability_id'] ?? '' ) ) {
			return array( 'allowed' => false, 'reasons' => array( 'guarded_media_derivative_rejected_preview_plan' ) );
		}

		$allowed_input_keys = array_fill_keys(
			array( 'attachment_id', 'derivative_artifact', 'expected_current_relative_file', 'expected_current_mime_type', 'expected_derivative_mime_type', 'expected_storage_provider', 'expected_storage_adapter', 'storage_preflight', 'file_name', 'expected_content_reference_post_ids', 'expected_content_reference_post_count', 'expected_content_reference_replacement_count', 'backup_suffix', 'dry_run', 'commit', 'idempotency_key' ),
			true
		);
		foreach ( array_keys( $input ) as $key ) {
			if ( ! isset( $allowed_input_keys[ (string) $key ] ) ) {
				return array( 'allowed' => false, 'reasons' => array( 'guarded_media_derivative_rejected_input_key' ) );
			}
		}

		return array(
			'allowed' => true,
			'reasons' => array( 'guarded_media_derivative_single_attachment', 'guarded_media_derivative_artifact_evidence', 'guarded_media_derivative_preview_plan' ),
		);
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
	public function caller_can_auto_approve(): bool {
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
	public function auto_approval_quota_metadata( string $mode ): array {
		$subject = 'user:' . ( function_exists( 'get_current_user_id' ) ? max( 0, get_current_user_id() ) : 0 );
		$auth    = Request_Context::audit_metadata();
		if ( ! empty( $auth['app_id'] ) ) {
			$subject = 'app:' . sanitize_key( (string) $auth['app_id'] );
		}

		$base_suffix = sanitize_key( $mode ) . '_' . sanitize_key( str_replace( ':', '_', $subject ) );

		return array(
			'subject'     => $subject,
			'hour_suffix' => $base_suffix . '_hour_' . gmdate( 'YmdH' ),
			'hour_limit'  => self::AUTO_APPROVAL_HOURLY_LIMIT,
			'hour_ttl'    => defined( 'HOUR_IN_SECONDS' ) ? HOUR_IN_SECONDS : 3600,
			'day_suffix'  => $base_suffix . '_day_' . gmdate( 'Ymd' ),
			'day_limit'   => self::AUTO_APPROVAL_DAILY_LIMIT,
			'day_ttl'     => defined( 'DAY_IN_SECONDS' ) ? DAY_IN_SECONDS : 86400,
		);
	}

	/**
	 * Returns whether auto-approval quota is available.
	 *
	 * @param array<string,mixed> $quota Quota metadata.
	 * @return bool
	 */
	public function auto_approval_quota_available( array $quota ): bool {
		foreach ( array( 'hour', 'day' ) as $window ) {
			$key_suffix = $this->auto_approval_transient_key_suffix( $quota, $window );
			$limit      = absint( $quota[ $window . '_limit' ] ?? 0 );
			if ( '' === $key_suffix || $limit <= 0 ) {
				return false;
			}
			$current = function_exists( 'get_transient' ) ? absint( get_transient( self::AUTO_APPROVAL_TRANSIENT_PREFIX . $key_suffix ) ) : 0;
			if ( $current >= $limit ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Returns a sanitized transient key suffix for one quota window.
	 *
	 * @param array<string,mixed> $quota Quota metadata.
	 * @param string              $window Quota window.
	 * @return string
	 */
	private function auto_approval_transient_key_suffix( array $quota, string $window ): string {
		$suffix = sanitize_key( (string) ( $quota[ $window . '_suffix' ] ?? '' ) );
		if ( '' !== $suffix ) {
			return $suffix;
		}

		$prefixed_key = sanitize_key( (string) ( $quota[ $window . '_key' ] ?? '' ) );
		if ( 0 === strpos( $prefixed_key, self::AUTO_APPROVAL_TRANSIENT_PREFIX ) ) {
			return substr( $prefixed_key, strlen( self::AUTO_APPROVAL_TRANSIENT_PREFIX ) );
		}

		return '';
	}

	/**
	 * Returns whether the unsafe local development allow-all strategy is enabled.
	 *
	 * @return bool
	 */
	public function dev_allow_all_enabled(): bool {
		return defined( 'NPCINK_GOVERNANCE_CORE_ENABLE_DEV_ALLOW_ALL' ) && true === NPCINK_GOVERNANCE_CORE_ENABLE_DEV_ALLOW_ALL;
	}
}
