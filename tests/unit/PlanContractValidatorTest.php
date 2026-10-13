<?php
/**
 * Behavioral tests for the plan contract validator.
 *
 * @package NpcinkGovernanceCore
 */

namespace NpcinkGovernanceCore\Tests\Unit;

use Npcink\GovernanceCore\Governance\Plan_Contract_Validator;
use PHPUnit\Framework\TestCase;

/**
 * Behavioral tests for Plan_Contract_Validator.
 */
final class PlanContractValidatorTest extends TestCase {
	/** @var Plan_Contract_Validator */
	private $validator;

	/**
	 * Creates the validator.
	 */
	protected function setUp(): void {
		$this->validator = new Plan_Contract_Validator();
	}

	/**
	 * Returns a plan that passes the common safety contract.
	 *
	 * @return array<string,mixed>
	 */
	private function safe_plan(): array {
		return array(
			'requires_approval' => true,
			'commit_execution'  => false,
			'dry_run'           => true,
			'write_actions'     => array(),
		);
	}

	public function testSupportsMatchesTheAllowlist(): void {
		$this->assertTrue( $this->validator->supports( 'npcink-abilities-toolkit/build-media-optimization-plan' ) );
		$this->assertTrue( $this->validator->supports( 'npcink-toolbox/build-article-batch-write-plan' ) );
		$this->assertFalse( $this->validator->supports( 'npcink-toolbox/build-unlisted-plan' ) );
	}

	public function testBatchCapsStayBounded(): void {
		$this->assertSame( 5, $this->validator->article_batch_max_actions() );
		$this->assertSame( 5, $this->validator->article_media_batch_max_articles() );
		$this->assertSame( 25, $this->validator->article_media_batch_max_actions() );
	}

	public function testCommonContractRejectionTable(): void {
		$cases = array(
			'npcink_governance_core_plan_requires_approval_missing' => array( 'requires_approval' => false ),
			'npcink_governance_core_plan_commit_execution_rejected' => array( 'commit_execution' => true ),
			'npcink_governance_core_plan_dry_run_required'          => array( 'dry_run' => false ),
			'npcink_governance_core_plan_write_actions_missing'     => array( 'write_actions' => 'not-an-array' ),
		);

		foreach ( $cases as $expected_code => $override ) {
			$plan   = array_merge( $this->safe_plan(), $override );
			$result = $this->validator->validate( 'npcink-abilities-toolkit/build-media-optimization-plan', $plan );
			$this->assertInstanceOf( \WP_Error::class, $result, "expected WP_Error for {$expected_code}" );
			$this->assertSame( $expected_code, $result->get_error_code() );
		}
	}

	public function testMissingCommitExecutionFlagIsRejected(): void {
		$plan = $this->safe_plan();
		unset( $plan['commit_execution'] );

		$result = $this->validator->validate( 'npcink-abilities-toolkit/build-media-optimization-plan', $plan );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'npcink_governance_core_plan_commit_execution_rejected', $result->get_error_code(), 'commit_execution must be explicitly false, not merely absent' );
	}

	public function testPayloadSizeCeilingIsEnforced(): void {
		$plan = $this->safe_plan();
		$plan['payload_filler'] = str_repeat( 'x', 262200 );

		$result = $this->validator->validate( 'npcink-abilities-toolkit/build-media-optimization-plan', $plan );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'npcink_governance_core_plan_payload_too_large', $result->get_error_code() );
		$this->assertSame( 413, $result->get_error_data()['status'] );
	}

	public function testWriteActionCeilingIsEnforced(): void {
		$plan = $this->safe_plan();
		$plan['write_actions'] = array_fill( 0, 26, array( 'target_ability_id' => 'x/y' ) );

		$result = $this->validator->validate( 'npcink-abilities-toolkit/build-media-optimization-plan', $plan );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'npcink_governance_core_plan_too_many_actions', $result->get_error_code() );
	}

	public function testUnknownPlanAbilityPassesCommonContractOnly(): void {
		$result = $this->validator->validate( 'npcink-toolbox/build-unlisted-plan', $this->safe_plan() );

		$this->assertTrue( $result, 'the common safety contract is the gate for plans without a specific contract' );
	}

	public function testArticleBatchContractRejectionTable(): void {
		$ability = 'npcink-toolbox/build-article-batch-write-plan';

		$wrong_type = $this->validator->validate( $ability, array_merge( $this->safe_plan(), array( 'artifact_type' => 'something_else' ) ) );
		$this->assertSame( 'npcink_governance_core_article_batch_plan_invalid', $wrong_type->get_error_code() );

		$mode_missing = $this->validator->validate(
			$ability,
			array_merge( $this->safe_plan(), array( 'artifact_type' => 'article_batch_write_plan' ) )
		);
		$this->assertSame( 'npcink_governance_core_article_batch_mode_required', $mode_missing->get_error_code(), 'batch approval must be explicitly requested' );

		$too_small = $this->validator->validate(
			$ability,
			array_merge(
				$this->safe_plan(),
				array(
					'artifact_type'   => 'article_batch_write_plan',
					'batch_approval'  => true,
					'proposal_mode'   => 'batch',
					'write_actions'   => array( array( 'target_ability_id' => 'a/b' ) ),
					'articles'        => array( array( 'title' => 'one' ) ),
				)
			)
		);
		$this->assertSame( 'npcink_governance_core_article_batch_size_rejected', $too_small->get_error_code(), 'a batch needs at least two draft actions' );

		$count_mismatch = $this->validator->validate(
			$ability,
			array_merge(
				$this->safe_plan(),
				array(
					'artifact_type'   => 'article_batch_write_plan',
					'batch_approval'  => true,
					'proposal_mode'   => 'batch',
					'write_actions'   => array(
						array( 'target_ability_id' => 'a/b' ),
						array( 'target_ability_id' => 'a/b' ),
					),
					'articles'        => array( array( 'title' => 'one' ) ),
				)
			)
		);
		$this->assertSame( 'npcink_governance_core_article_batch_artifacts_missing', $count_mismatch->get_error_code(), 'one reviewed artifact set per action' );
	}

	public function testSupportsFlaggedMediaRemovalPlan(): void {
		$this->assertTrue( $this->validator->supports( Plan_Contract_Validator::FLAGGED_MEDIA_REMOVAL_PLAN_ABILITY ) );
	}

	/**
	 * Returns a flagged media removal plan that satisfies the contract.
	 *
	 * @param string $branch Deletion policy branch.
	 * @return array<string,mixed>
	 */
	private function flagged_media_plan( string $branch = Plan_Contract_Validator::DELETION_POLICY_BRANCH_GOVERNED_WITH_BACKUP ): array {
		$reason          = 'Flagged for illegal content by operator review.';
		$idempotency_key = 'flagged-delete-42-20261011';

		$deletion = array(
			'attachment_id'          => 42,
			'deletion_policy_branch' => $branch,
			'reason'                 => $reason,
			'idempotency_key'        => $idempotency_key,
		);
		if ( Plan_Contract_Validator::DELETION_POLICY_BRANCH_GOVERNED_WITH_BACKUP === $branch ) {
			$deletion['backup_evidence'] = array(
				'location'                => 'toolkit-backups/2026/10/attachment-42',
				'retention_window'        => '30 days',
				'restore_ability_contract'  => 'npcink-abilities-toolkit/restore-media-backup',
			);
		} else {
			$deletion['no_backup_attestation'] = array(
				'confirmed' => true,
				'statement' => 'No backup, local copy, or Cloud artifact will be created for this deletion.',
			);
		}

		return array(
			'requires_approval'     => true,
			'commit_execution'      => false,
			'dry_run'               => true,
			'artifact_type'         => 'flagged_media_removal_plan',
			'proposal_mode'         => 'batch',
			'batch_approval'        => true,
			'batch_id'              => 'flagged-batch-1',
			'flagged_media_removal' => $deletion,
			'write_actions'         => array(
				array(
					'action_id'         => 'delete_attachment_42',
					'target_ability_id' => 'npcink-abilities-toolkit/delete-attachment',
					'requires_approval' => true,
					'commit_execution'  => false,
					'input'             => array(
						'attachment_id'          => 42,
						'deletion_policy_branch' => $branch,
						'reason'                 => $reason,
						'idempotency_key'        => $idempotency_key,
						'dry_run'                => true,
						'commit'                 => false,
					),
				),
				array(
					'action_id'         => 'patch_post_7',
					'target_ability_id' => 'npcink-abilities-toolkit/patch-post-content',
					'requires_approval' => true,
					'commit_execution'  => false,
					'input'             => array(
						'post_id'    => 7,
						'operations' => array(
							array(
								'op'      => 'replace',
								'find'    => 'https://example.test/wp-content/uploads/2026/09/flagged.png',
								'replace' => '',
							),
						),
						'dry_run'    => true,
						'commit'     => false,
					),
				),
				array(
					'action_id'         => 'patch_option_1',
					'target_ability_id' => 'npcink-abilities-toolkit/patch-setting-value',
					'requires_approval' => true,
					'commit_execution'  => false,
					'input'             => array(
						'target_type' => 'option',
						'target_name' => 'theme_media_cache',
						'operations'  => array(
							array(
								'op'      => 'replace',
								'find'    => 'https://example.test/wp-content/uploads/2026/09/flagged.png',
								'replace' => '',
							),
						),
						'dry_run'    => true,
						'commit'     => false,
					),
				),
			),
		);
	}

	public function testFlaggedMediaRemovalPlanAcceptsBothBranches(): void {
		$this->assertTrue( $this->validator->validate( Plan_Contract_Validator::FLAGGED_MEDIA_REMOVAL_PLAN_ABILITY, $this->flagged_media_plan( Plan_Contract_Validator::DELETION_POLICY_BRANCH_GOVERNED_WITH_BACKUP ) ) );
		$this->assertTrue( $this->validator->validate( Plan_Contract_Validator::FLAGGED_MEDIA_REMOVAL_PLAN_ABILITY, $this->flagged_media_plan( Plan_Contract_Validator::DELETION_POLICY_BRANCH_ILLEGAL_NO_BACKUP ) ) );
	}

	public function testFlaggedMediaRemovalPlanWorksWithoutReferencePatches(): void {
		$plan = $this->flagged_media_plan();
		$plan['write_actions'] = array( $plan['write_actions'][0] );

		$this->assertTrue( $this->validator->validate( Plan_Contract_Validator::FLAGGED_MEDIA_REMOVAL_PLAN_ABILITY, $plan ), 'an unreferenced attachment has an empty bounded reference set' );
	}

	public function testFlaggedMediaRemovalContractRejectionTable(): void {
		$ability = Plan_Contract_Validator::FLAGGED_MEDIA_REMOVAL_PLAN_ABILITY;

		$rejections = array(
			'npcink_governance_core_flagged_media_removal_plan_invalid' => function ( array $plan ): array {
				$plan['artifact_type'] = 'something_else';
				return $plan;
			},
			'npcink_governance_core_flagged_media_removal_batch_required' => function ( array $plan ): array {
				unset( $plan['batch_approval'], $plan['proposal_mode'] );
				return $plan;
			},
			'npcink_governance_core_flagged_media_removal_context_missing' => function ( array $plan ): array {
				unset( $plan['flagged_media_removal'] );
				return $plan;
			},
			'npcink_governance_core_flagged_media_removal_attachment_missing' => function ( array $plan ): array {
				$plan['flagged_media_removal']['attachment_id'] = 0;
				return $plan;
			},
			'npcink_governance_core_flagged_media_removal_branch_invalid' => function ( array $plan ): array {
				$plan['flagged_media_removal']['deletion_policy_branch'] = 'just_delete';
				return $plan;
			},
			'npcink_governance_core_flagged_media_removal_reason_invalid' => function ( array $plan ): array {
				$plan['flagged_media_removal']['reason'] = '  ';
				return $plan;
			},
			'npcink_governance_core_flagged_media_removal_idempotency_key_missing' => function ( array $plan ): array {
				$plan['flagged_media_removal']['idempotency_key'] = '';
				return $plan;
			},
			'npcink_governance_core_flagged_media_backup_evidence_missing' => function ( array $plan ): array {
				$plan['flagged_media_removal']['backup_evidence']['location'] = '';
				return $plan;
			},
			'npcink_governance_core_flagged_media_attestation_conflict' => function ( array $plan ): array {
				$plan['flagged_media_removal']['no_backup_attestation'] = array( 'confirmed' => true, 'statement' => 'conflicting attestation' );
				return $plan;
			},
			'npcink_governance_core_flagged_media_delete_branch_mismatch' => function ( array $plan ): array {
				$plan['write_actions'][0]['input']['deletion_policy_branch'] = 'illegal_content_no_backup';
				return $plan;
			},
			'npcink_governance_core_flagged_media_delete_attachment_mismatch' => function ( array $plan ): array {
				$plan['write_actions'][0]['input']['attachment_id'] = 43;
				return $plan;
			},
			'npcink_governance_core_flagged_media_delete_reason_mismatch' => function ( array $plan ): array {
				$plan['write_actions'][0]['input']['reason'] = 'different reason';
				return $plan;
			},
			'npcink_governance_core_flagged_media_delete_idempotency_mismatch' => function ( array $plan ): array {
				$plan['write_actions'][0]['input']['idempotency_key'] = 'different-key';
				return $plan;
			},
			'npcink_governance_core_flagged_media_removal_action_invalid' => function ( array $plan ): array {
				$plan['write_actions'][0] = 'invalid-action';
				return $plan;
			},
			'npcink_governance_core_flagged_media_target_rejected' => function ( array $plan ): array {
				$plan['write_actions'][1]['target_ability_id'] = 'npcink-abilities-toolkit/update-post';
				return $plan;
			},
			'npcink_governance_core_flagged_media_delete_action_missing' => function ( array $plan ): array {
				$plan['write_actions'][] = $plan['write_actions'][0];
				return $plan;
			},
			'npcink_governance_core_flagged_media_patch_invalid' => function ( array $plan ): array {
				$plan['write_actions'][1]['input']['operations'][0]['find'] = 'not a url';
				return $plan;
			},
		);

		foreach ( $rejections as $expected_code => $mutate ) {
			$plan   = $mutate( $this->flagged_media_plan() );
			$result = $this->validator->validate( $ability, $plan );
			$this->assertInstanceOf( \WP_Error::class, $result, "expected WP_Error for {$expected_code}" );
			$this->assertSame( $expected_code, $result->get_error_code() );
		}
	}

	public function testFlaggedMediaRemovalIllegalBranchEvidenceRules(): void {
		$ability = Plan_Contract_Validator::FLAGGED_MEDIA_REMOVAL_PLAN_ABILITY;

		$missing_attestation = $this->flagged_media_plan( Plan_Contract_Validator::DELETION_POLICY_BRANCH_ILLEGAL_NO_BACKUP );
		unset( $missing_attestation['flagged_media_removal']['no_backup_attestation'] );
		$result = $this->validator->validate( $ability, $missing_attestation );
		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'npcink_governance_core_flagged_media_attestation_missing', $result->get_error_code() );

		$unconfirmed_attestation = $this->flagged_media_plan( Plan_Contract_Validator::DELETION_POLICY_BRANCH_ILLEGAL_NO_BACKUP );
		$unconfirmed_attestation['flagged_media_removal']['no_backup_attestation']['confirmed'] = false;
		$result = $this->validator->validate( $ability, $unconfirmed_attestation );
		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'npcink_governance_core_flagged_media_attestation_missing', $result->get_error_code() );

		$conflicting_backup = $this->flagged_media_plan( Plan_Contract_Validator::DELETION_POLICY_BRANCH_ILLEGAL_NO_BACKUP );
		$conflicting_backup['flagged_media_removal']['backup_evidence'] = array(
			'location'                => 'toolkit-backups/2026/10/attachment-42',
			'retention_window'        => '30 days',
			'restore_ability_contract'  => 'npcink-abilities-toolkit/restore-media-backup',
		);
		$result = $this->validator->validate( $ability, $conflicting_backup );
		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'npcink_governance_core_flagged_media_backup_evidence_conflict', $result->get_error_code() );
	}

	public function testFlaggedMediaRemovalPatchOperationsStayExactRemovals(): void {
		$ability = Plan_Contract_Validator::FLAGGED_MEDIA_REMOVAL_PLAN_ABILITY;

		$patch_with_replacement = $this->flagged_media_plan();
		$patch_with_replacement['write_actions'][1]['input']['operations'][0]['replace'] = 'https://example.test/replacement.png';
		$result = $this->validator->validate( $ability, $patch_with_replacement );
		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'npcink_governance_core_flagged_media_patch_invalid', $result->get_error_code(), 'reference patches remove the deleted URL, they do not substitute another one' );

		$empty_operations = $this->flagged_media_plan();
		$empty_operations['write_actions'][1]['input']['operations'] = array();
		$result = $this->validator->validate( $ability, $empty_operations );
		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'npcink_governance_core_flagged_media_patch_invalid', $result->get_error_code(), 'empty reference patches are rejected, not widened' );

		$missing_post_id = $this->flagged_media_plan();
		unset( $missing_post_id['write_actions'][1]['input']['post_id'] );
		$result = $this->validator->validate( $ability, $missing_post_id );
		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'npcink_governance_core_flagged_media_patch_invalid', $result->get_error_code() );

		$bad_setting_target = $this->flagged_media_plan();
		$bad_setting_target['write_actions'][2]['input']['target_type'] = 'transient';
		$result = $this->validator->validate( $ability, $bad_setting_target );
		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'npcink_governance_core_flagged_media_patch_invalid', $result->get_error_code() );
	}

	public function testFlaggedMediaReferenceActionCapIsEnforced(): void {
		$plan   = $this->flagged_media_plan();
		$patch  = $plan['write_actions'][1];
		$plan['write_actions'] = array_merge( array( $plan['write_actions'][0] ), array_fill( 0, 11, $patch ) );

		$result = $this->validator->validate( Plan_Contract_Validator::FLAGGED_MEDIA_REMOVAL_PLAN_ABILITY, $plan );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'npcink_governance_core_flagged_media_reference_actions_rejected', $result->get_error_code() );
	}

	public function testFlaggedMediaDetectionHelpers(): void {
		$attributed = array(
			'caller' => array(
				'source'          => 'plan_to_proposal_batch',
				'plan_ability_id' => Plan_Contract_Validator::FLAGGED_MEDIA_REMOVAL_PLAN_ABILITY,
			),
		);
		$this->assertTrue( Plan_Contract_Validator::is_flagged_media_removal_proposal( $attributed ) );
		$this->assertSame( '', Plan_Contract_Validator::flagged_media_removal_branch( $attributed ), 'no recorded branch yields an empty string' );

		$with_context = array(
			'preview' => array( 'flagged_media_removal' => array( 'deletion_policy_branch' => 'illegal_content_no_backup' ) ),
		);
		$this->assertTrue( Plan_Contract_Validator::is_flagged_media_removal_proposal( $with_context ) );
		$this->assertSame( 'illegal_content_no_backup', Plan_Contract_Validator::flagged_media_removal_branch( $with_context ) );

		$with_action_branch = array(
			'input' => array(
				'write_actions' => array(
					array(
						'target_ability_id' => 'npcink-abilities-toolkit/delete-attachment',
						'input'             => array( 'deletion_policy_branch' => 'governed_with_backup' ),
					),
				),
			),
		);
		$this->assertSame( 'governed_with_backup', Plan_Contract_Validator::flagged_media_removal_branch( $with_action_branch ) );

		$direct_delete = array(
			'ability_id' => 'npcink-abilities-toolkit/delete-attachment',
			'input'      => array(
				'attachment_id'          => 42,
				'deletion_policy_branch' => 'illegal_content_no_backup',
			),
		);
		$this->assertTrue( Plan_Contract_Validator::is_flagged_media_removal_proposal( $direct_delete ), 'direct proposals to the governed delete ability never escape the flagged media rules' );
		$this->assertSame( 'illegal_content_no_backup', Plan_Contract_Validator::flagged_media_removal_branch( $direct_delete ) );
		$direct_context = Plan_Contract_Validator::flagged_media_removal_context( $direct_delete );
		$this->assertSame( 42, $direct_context['attachment_id'], 'a direct proposal carries branch evidence as flat input' );

		$direct_without_evidence = array(
			'ability_id' => 'npcink-abilities-toolkit/delete-attachment',
			'input'      => array( 'attachment_id' => 42 ),
		);
		$this->assertSame( '', Plan_Contract_Validator::flagged_media_removal_branch( $direct_without_evidence ), 'a branch-less direct proposal fails closed at preflight' );
		$this->assertArrayNotHasKey( 'deletion_policy_branch', Plan_Contract_Validator::flagged_media_removal_context( $direct_without_evidence ) );

		$plain = array( 'ability_id' => 'npcink-abilities-toolkit/create-draft' );
		$this->assertFalse( Plan_Contract_Validator::is_flagged_media_removal_proposal( $plain ) );
	}
}
