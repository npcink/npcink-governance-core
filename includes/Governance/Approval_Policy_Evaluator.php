<?php
/**
 * Approval policy evaluator.
 *
 * @package MagickAICore
 */

namespace MagickAI\Core\Governance;

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

	const VERSION = 'core-approval-policy-v1';

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

		$reasons = array( 'default_manual_required' );
		if ( $this->is_cleanup_batch_candidate( $ability_id, $input, $preview, $caller ) ) {
			$reasons[] = 'cleanup_batch_candidate_detected';
			$reasons[] = 'auto_approval_dry_run_only';
		}

		if ( 'magick-ai/create-draft' === $ability_id ) {
			$reasons[] = 'create_draft_auto_approval_deferred';
		}

		if ( '' !== $source ) {
			$reasons[] = 'source_' . $source;
		}

		return array(
			'policy_decision' => self::DECISION_MANUAL_REQUIRED,
			'policy_profile'  => self::PROFILE_MANUAL,
			'policy_version'  => self::VERSION,
			'policy_reasons'  => array_values( array_unique( array_map( 'sanitize_key', $reasons ) ) ),
		);
	}

	/**
	 * Returns whether a proposal looks like the future narrow cleanup candidate.
	 *
	 * @param string              $ability_id Ability id.
	 * @param array<string,mixed> $input Input.
	 * @param array<string,mixed> $preview Preview.
	 * @param array<string,mixed> $caller Caller.
	 * @return bool
	 */
	private function is_cleanup_batch_candidate( string $ability_id, array $input, array $preview, array $caller ): bool {
		if ( 'magick-ai/trash-post' !== $ability_id ) {
			return false;
		}

		if ( 'plan_to_proposal_batch' !== (string) ( $caller['source'] ?? '' ) ) {
			return false;
		}

		if ( 'magick-ai/build-test-content-cleanup-plan' !== (string) ( $caller['plan_ability_id'] ?? '' ) ) {
			return false;
		}

		$source = is_array( $preview['source'] ?? null ) ? $preview['source'] : array();
		if ( 'plan_to_proposal_batch' !== (string) ( $source['type'] ?? '' ) ) {
			return false;
		}

		$actions = is_array( $input['write_actions'] ?? null ) ? array_values( $input['write_actions'] ) : array();
		if ( empty( $actions ) || count( $actions ) > 10 ) {
			return false;
		}

		foreach ( $actions as $action ) {
			if ( ! is_array( $action ) || 'magick-ai/trash-post' !== (string) ( $action['target_ability_id'] ?? '' ) ) {
				return false;
			}
		}

		return true;
	}
}
