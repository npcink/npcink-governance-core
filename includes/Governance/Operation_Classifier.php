<?php
/**
 * Operation classification policy.
 *
 * @package NpcinkGovernanceCore
 */

namespace Npcink\GovernanceCore\Governance;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classifies AI-assisted operation authorization paths.
 */
final class Operation_Classifier {
	const CLASSIFICATION_SUGGESTION_ONLY          = 'suggestion_only';
	const CLASSIFICATION_LOCAL_ADMIN_CONSENT      = 'local_admin_consent';
	const CLASSIFICATION_STRONG_LOCAL_CONFIRMATION = 'strong_local_confirmation';
	const CLASSIFICATION_CORE_PROPOSAL_REQUIRED   = 'core_proposal_required';

	const SOURCE_WP_ADMIN_UI     = 'wp_admin_ui';
	const SOURCE_EXTERNAL_ADAPTER = 'external_adapter';
	const SOURCE_SCHEDULED_TASK   = 'scheduled_task';
	const SOURCE_CLI              = 'cli';
	const SOURCE_CLOUD_CALLBACK   = 'cloud_callback';

	const ACTOR_PRESENT_CLICK = 'present_click';
	const ACTOR_BACKGROUND    = 'background';
	const ACTOR_DELEGATED     = 'delegated';

	const PREVIEW_EXACT_FINAL = 'exact_final';
	const PREVIEW_SUFFICIENT  = 'sufficient';
	const PREVIEW_PARTIAL     = 'partial';
	const PREVIEW_NONE        = 'none';

	const SCOPE_ONE_FIELD        = 'one_field';
	const SCOPE_ONE_OBJECT       = 'one_object';
	const SCOPE_MULTIPLE_OBJECTS = 'multiple_objects';
	const SCOPE_SITE_WIDE        = 'site_wide';
	const SCOPE_EXTERNAL_ACCOUNT = 'external_account';

	const REVERSIBILITY_EASY_UNDO      = 'easy_undo';
	const REVERSIBILITY_BACKUP_RESTORE = 'backup_restore';
	const REVERSIBILITY_HARD_RESTORE   = 'hard_restore';
	const REVERSIBILITY_IRREVERSIBLE   = 'irreversible';

	const KIND_SUGGEST                  = 'suggest';
	const KIND_CREATE_DRAFT             = 'create_draft';
	const KIND_UPDATE_METADATA          = 'update_metadata';
	const KIND_UPDATE_EXISTING_TERMS    = 'update_existing_terms';
	const KIND_SET_FEATURED_IMAGE       = 'set_featured_image';
	const KIND_PUBLISH                  = 'publish';
	const KIND_UNPUBLISH                = 'unpublish';
	const KIND_DELETE                   = 'delete';
	const KIND_REPLACE_FILE             = 'replace_file';
	const KIND_OVERWRITE_CONTENT        = 'overwrite_content';
	const KIND_SETTINGS_CHANGE          = 'settings_change';
	const KIND_PERMISSION_CHANGE        = 'permission_change';
	const KIND_EXTERNAL_ACCOUNT_CHANGE  = 'external_account_change';
	const KIND_BATCH_PLAN               = 'batch_plan';

	/**
	 * Classifies an operation-like payload.
	 *
	 * @param array<string,mixed> $operation Operation context.
	 * @return array<string,mixed>
	 */
	public function classify( array $operation ): array {
		$source        = $this->sanitize_enum( (string) ( $operation['request_source'] ?? '' ), self::allowed_request_sources(), self::SOURCE_EXTERNAL_ADAPTER );
		$actor         = $this->sanitize_enum( (string) ( $operation['actor_presence'] ?? '' ), self::allowed_actor_presence(), self::ACTOR_BACKGROUND );
		$preview       = $this->sanitize_enum( (string) ( $operation['preview_completeness'] ?? '' ), self::allowed_preview_completeness(), self::PREVIEW_NONE );
		$scope         = $this->sanitize_enum( (string) ( $operation['scope'] ?? '' ), self::allowed_scopes(), self::SCOPE_MULTIPLE_OBJECTS );
		$reversibility = $this->sanitize_enum( (string) ( $operation['reversibility'] ?? '' ), self::allowed_reversibility(), self::REVERSIBILITY_HARD_RESTORE );
		$kind          = $this->sanitize_enum( (string) ( $operation['operation_kind'] ?? '' ), self::allowed_operation_kinds(), self::KIND_BATCH_PLAN );
		$writes_state  = array_key_exists( 'writes_wordpress_state', $operation ) ? (bool) $operation['writes_wordpress_state'] : self::KIND_SUGGEST !== $kind;

		if ( ! $writes_state || self::KIND_SUGGEST === $kind ) {
			return $this->result(
				self::CLASSIFICATION_SUGGESTION_ONLY,
				array( 'no_wordpress_write' ),
				array()
			);
		}

		$hard_core_reasons = $this->core_required_reasons( $source, $actor, $preview, $scope, $reversibility, $kind );
		if ( ! empty( $hard_core_reasons ) ) {
			return $this->result(
				self::CLASSIFICATION_CORE_PROPOSAL_REQUIRED,
				$hard_core_reasons,
				$this->core_proposal_required_evidence()
			);
		}

		if ( $this->is_high_impact_single_object_kind( $kind ) ) {
			return $this->result(
				self::CLASSIFICATION_STRONG_LOCAL_CONFIRMATION,
				array( 'single_object_high_impact_write', 'present_admin_preview_required' ),
				$this->strong_local_confirmation_evidence()
			);
		}

		if (
			self::SOURCE_WP_ADMIN_UI === $source
			&& self::ACTOR_PRESENT_CLICK === $actor
			&& $this->preview_is_sufficient( $preview )
			&& $this->scope_is_local( $scope )
			&& $this->reversibility_is_low_cost( $reversibility )
		) {
			return $this->result(
				self::CLASSIFICATION_LOCAL_ADMIN_CONSENT,
				array( 'present_admin_single_visible_low_risk_write' ),
				$this->local_admin_consent_evidence()
			);
		}

		return $this->result(
			self::CLASSIFICATION_CORE_PROPOSAL_REQUIRED,
			array( 'local_admin_consent_requirements_not_met' ),
			$this->core_proposal_required_evidence()
		);
	}

	/**
	 * Returns allowed classification values.
	 *
	 * @return array<int,string>
	 */
	public static function allowed_classifications(): array {
		return array(
			self::CLASSIFICATION_SUGGESTION_ONLY,
			self::CLASSIFICATION_LOCAL_ADMIN_CONSENT,
			self::CLASSIFICATION_STRONG_LOCAL_CONFIRMATION,
			self::CLASSIFICATION_CORE_PROPOSAL_REQUIRED,
		);
	}

	/**
	 * Returns allowed request source values.
	 *
	 * @return array<int,string>
	 */
	public static function allowed_request_sources(): array {
		return array(
			self::SOURCE_WP_ADMIN_UI,
			self::SOURCE_EXTERNAL_ADAPTER,
			self::SOURCE_SCHEDULED_TASK,
			self::SOURCE_CLI,
			self::SOURCE_CLOUD_CALLBACK,
		);
	}

	/**
	 * Returns allowed actor presence values.
	 *
	 * @return array<int,string>
	 */
	public static function allowed_actor_presence(): array {
		return array(
			self::ACTOR_PRESENT_CLICK,
			self::ACTOR_BACKGROUND,
			self::ACTOR_DELEGATED,
		);
	}

	/**
	 * Returns allowed preview completeness values.
	 *
	 * @return array<int,string>
	 */
	public static function allowed_preview_completeness(): array {
		return array(
			self::PREVIEW_EXACT_FINAL,
			self::PREVIEW_SUFFICIENT,
			self::PREVIEW_PARTIAL,
			self::PREVIEW_NONE,
		);
	}

	/**
	 * Returns allowed scope values.
	 *
	 * @return array<int,string>
	 */
	public static function allowed_scopes(): array {
		return array(
			self::SCOPE_ONE_FIELD,
			self::SCOPE_ONE_OBJECT,
			self::SCOPE_MULTIPLE_OBJECTS,
			self::SCOPE_SITE_WIDE,
			self::SCOPE_EXTERNAL_ACCOUNT,
		);
	}

	/**
	 * Returns allowed reversibility values.
	 *
	 * @return array<int,string>
	 */
	public static function allowed_reversibility(): array {
		return array(
			self::REVERSIBILITY_EASY_UNDO,
			self::REVERSIBILITY_BACKUP_RESTORE,
			self::REVERSIBILITY_HARD_RESTORE,
			self::REVERSIBILITY_IRREVERSIBLE,
		);
	}

	/**
	 * Returns allowed operation kind values.
	 *
	 * @return array<int,string>
	 */
	public static function allowed_operation_kinds(): array {
		return array(
			self::KIND_SUGGEST,
			self::KIND_CREATE_DRAFT,
			self::KIND_UPDATE_METADATA,
			self::KIND_UPDATE_EXISTING_TERMS,
			self::KIND_SET_FEATURED_IMAGE,
			self::KIND_PUBLISH,
			self::KIND_UNPUBLISH,
			self::KIND_DELETE,
			self::KIND_REPLACE_FILE,
			self::KIND_OVERWRITE_CONTENT,
			self::KIND_SETTINGS_CHANGE,
			self::KIND_PERMISSION_CHANGE,
			self::KIND_EXTERNAL_ACCOUNT_CHANGE,
			self::KIND_BATCH_PLAN,
		);
	}

	/**
	 * Returns proposal-required reasons that cannot be downgraded locally.
	 *
	 * @param string $source Request source.
	 * @param string $actor Actor presence.
	 * @param string $preview Preview completeness.
	 * @param string $scope Scope.
	 * @param string $reversibility Reversibility.
	 * @param string $kind Operation kind.
	 * @return array<int,string>
	 */
	private function core_required_reasons( string $source, string $actor, string $preview, string $scope, string $reversibility, string $kind ): array {
		$reasons = array();

		if ( self::SOURCE_WP_ADMIN_UI !== $source ) {
			$reasons[] = 'non_admin_ui_source';
		}

		if ( self::ACTOR_PRESENT_CLICK !== $actor ) {
			$reasons[] = 'actor_not_present_click';
		}

		if ( ! $this->preview_is_sufficient( $preview ) ) {
			$reasons[] = 'preview_not_sufficient';
		}

		if ( ! $this->scope_is_local( $scope ) ) {
			$reasons[] = 'scope_not_single_object';
		}

		if ( self::REVERSIBILITY_IRREVERSIBLE === $reversibility ) {
			$reasons[] = 'irreversible_operation';
		}

		if ( $this->is_always_core_kind( $kind ) ) {
			$reasons[] = 'operation_kind_requires_core_proposal';
		}

		return array_values( array_unique( $reasons ) );
	}

	/**
	 * Checks whether preview is enough for local authorization.
	 *
	 * @param string $preview Preview completeness.
	 * @return bool
	 */
	private function preview_is_sufficient( string $preview ): bool {
		return in_array( $preview, array( self::PREVIEW_EXACT_FINAL, self::PREVIEW_SUFFICIENT ), true );
	}

	/**
	 * Checks whether scope is bounded to one field or object.
	 *
	 * @param string $scope Scope.
	 * @return bool
	 */
	private function scope_is_local( string $scope ): bool {
		return in_array( $scope, array( self::SCOPE_ONE_FIELD, self::SCOPE_ONE_OBJECT ), true );
	}

	/**
	 * Checks whether correction cost is low enough for local consent.
	 *
	 * @param string $reversibility Reversibility.
	 * @return bool
	 */
	private function reversibility_is_low_cost( string $reversibility ): bool {
		return in_array( $reversibility, array( self::REVERSIBILITY_EASY_UNDO, self::REVERSIBILITY_BACKUP_RESTORE ), true );
	}

	/**
	 * Checks whether an operation must use Core review.
	 *
	 * @param string $kind Operation kind.
	 * @return bool
	 */
	private function is_always_core_kind( string $kind ): bool {
		return in_array(
			$kind,
			array(
				self::KIND_DELETE,
				self::KIND_SETTINGS_CHANGE,
				self::KIND_PERMISSION_CHANGE,
				self::KIND_EXTERNAL_ACCOUNT_CHANGE,
				self::KIND_BATCH_PLAN,
			),
			true
		);
	}

	/**
	 * Checks whether an operation is single-object but high impact.
	 *
	 * @param string $kind Operation kind.
	 * @return bool
	 */
	private function is_high_impact_single_object_kind( string $kind ): bool {
		return in_array(
			$kind,
			array(
				self::KIND_PUBLISH,
				self::KIND_UNPUBLISH,
				self::KIND_REPLACE_FILE,
				self::KIND_OVERWRITE_CONTENT,
			),
			true
		);
	}

	/**
	 * Formats a classification result.
	 *
	 * @param string            $classification Classification.
	 * @param array<int,string> $reasons Reasons.
	 * @param array<int,string> $required_evidence Required evidence keys.
	 * @return array<string,mixed>
	 */
	private function result( string $classification, array $reasons, array $required_evidence ): array {
		return array(
			'classification'    => $classification,
			'reasons'           => array_values( array_unique( array_map( array( $this, 'sanitize_token' ), $reasons ) ) ),
			'required_evidence' => array_values( array_unique( array_map( array( $this, 'sanitize_token' ), $required_evidence ) ) ),
			'policy_version'    => 'operation-classification-v1',
		);
	}

	/**
	 * Returns local admin consent evidence requirements.
	 *
	 * @return array<int,string>
	 */
	private function local_admin_consent_evidence(): array {
		return array(
			'actor_user_id',
			'source_module',
			'target_object_id',
			'target_object_type',
			'classification',
			'ai_suggestion_summary',
			'timestamp',
			'request_or_correlation_id',
		);
	}

	/**
	 * Returns strong local confirmation evidence requirements.
	 *
	 * @return array<int,string>
	 */
	private function strong_local_confirmation_evidence(): array {
		return array_merge(
			$this->local_admin_consent_evidence(),
			array(
				'strong_confirmation',
				'reversibility_evidence',
			)
		);
	}

	/**
	 * Returns Core proposal evidence requirements.
	 *
	 * @return array<int,string>
	 */
	private function core_proposal_required_evidence(): array {
		return array(
			'target_ability_id',
			'target_input_or_safe_summary',
			'before_after_or_dry_run_evidence',
			'reason_risk_required_scopes',
			'caller_source_metadata',
			'batch_item_details_when_applicable',
		);
	}

	/**
	 * Sanitizes an enum value.
	 *
	 * @param string            $value Raw value.
	 * @param array<int,string> $allowed Allowed values.
	 * @param string            $fallback Fallback.
	 * @return string
	 */
	private function sanitize_enum( string $value, array $allowed, string $fallback ): string {
		$value = $this->sanitize_token( $value );
		return in_array( $value, $allowed, true ) ? $value : $fallback;
	}

	/**
	 * Sanitizes one token without requiring WordPress to be loaded.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	private function sanitize_token( string $value ): string {
		if ( function_exists( 'sanitize_key' ) ) {
			return sanitize_key( $value );
		}

		$value = strtolower( $value );
		return preg_replace( '/[^a-z0-9_\-]/', '', $value ) ?: '';
	}
}
