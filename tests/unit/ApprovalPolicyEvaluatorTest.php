<?php
/**
 * Behavioral tests for the approval policy evaluator.
 *
 * @package NpcinkGovernanceCore
 */

namespace NpcinkGovernanceCore\Tests\Unit;

use Npcink\GovernanceCore\Governance\Approval_Policy_Evaluator;
use PHPUnit\Framework\TestCase;

/**
 * Behavioral tests for Approval_Policy_Evaluator.
 */
final class ApprovalPolicyEvaluatorTest extends TestCase {
	/** @var Approval_Policy_Evaluator */
	private $evaluator;

	/**
	 * Resets option and transient stores between tests.
	 */
	protected function setUp(): void {
		$this->evaluator              = new Approval_Policy_Evaluator();
		$GLOBALS['npcink_unit_options']    = array();
		$GLOBALS['npcink_unit_transients'] = array();
	}

	public function testAllowedPolicyModesIsAClosedSet(): void {
		$this->assertSame(
			array( 'manual', 'smart_guarded', 'dev_allow_all' ),
			Approval_Policy_Evaluator::allowed_policy_modes()
		);
	}

	public function testUnknownPolicyModeFallsBackToManual(): void {
		$this->assertFalse( Approval_Policy_Evaluator::is_allowed_policy_mode( 'auto_approve_everything' ) );
		$this->assertSame( 'manual', Approval_Policy_Evaluator::sanitize_policy_mode( 'Auto Approve Everything' ) );
		$this->assertSame( 'manual', Approval_Policy_Evaluator::sanitize_policy_mode( '' ) );
		$this->assertSame( 'smart_guarded', Approval_Policy_Evaluator::sanitize_policy_mode( 'Smart_Guarded' ) );
	}

	public function testCurrentPolicyModeReadsStoredOptionWithFallback(): void {
		$this->assertSame( 'manual', Approval_Policy_Evaluator::current_policy_mode(), 'missing option defaults to manual' );

		$GLOBALS['npcink_unit_options']['npcink_governance_core_approval_policy_mode'] = 'smart_guarded';
		$this->assertSame( 'smart_guarded', Approval_Policy_Evaluator::current_policy_mode() );

		$GLOBALS['npcink_unit_options']['npcink_governance_core_approval_policy_mode'] = 'not-a-mode';
		$this->assertSame( 'manual', Approval_Policy_Evaluator::current_policy_mode(), 'unknown stored values fall back to manual' );
	}

	public function testPolicyResultNormalizesReasonsAndAttributesSource(): void {
		$result = $this->evaluator->policy_result(
			array( 'caller' => array( 'source' => 'openclaw_adapter' ) ),
			'manual',
			'manual_review',
			'default',
			array( 'quota_check', 'Quota Check', 'quota_check' )
		);

		$this->assertSame( 'manual_review', $result['policy_decision'] );
		$this->assertSame( 'manual', $result['policy_mode'] );
		$this->assertSame( Approval_Policy_Evaluator::VERSION, $result['policy_version'] );
		$this->assertSame(
			array( 'quota_check', 'quotacheck', 'source_openclaw_adapter' ),
			$result['policy_reasons'],
			'reasons are sanitized key-likes, deduplicated after sanitizing, and gain the caller source prefix'
		);
		$this->assertSame( array(), $result['auto_approval_quota'] );
	}

	public function testConsumeQuotaIgnoresNonAutoApprovedDecisions(): void {
		$this->assertTrue(
			$this->evaluator->consume_auto_approval_quota( array( 'policy_decision' => 'manual_review' ) ),
			'non-auto-approved decisions never touch quota transients'
		);
		$this->assertSame( array(), $GLOBALS['npcink_unit_transients'] );
	}

	public function testConsumeQuotaRejectsAutoApprovalWithoutQuotaContract(): void {
		$this->assertFalse(
			$this->evaluator->consume_auto_approval_quota( array( 'policy_decision' => 'auto_approved' ) ),
			'an auto-approved decision without hour/day quota metadata must not consume anything'
		);
	}

	public function testConsumeQuotaIncrementsEachWindowWithinLimit(): void {
		$policy = array(
			'policy_decision'     => 'auto_approved',
			'auto_approval_quota' => array(
				'hour_limit'   => 3,
				'hour_ttl'     => 3600,
				'day_limit'    => 10,
				'day_ttl'      => 86400,
				'hour_suffix'  => 'h1',
				'day_suffix'   => 'd1',
			),
		);

		$this->assertTrue( $this->evaluator->consume_auto_approval_quota( $policy ) );
		$this->assertSame( 1, $GLOBALS['npcink_unit_transients']['npcink_governance_core_auto_approval_h1'] );
		$this->assertSame( 1, $GLOBALS['npcink_unit_transients']['npcink_governance_core_auto_approval_d1'] );
	}

	public function testConsumeQuotaRefusesWhenAnyWindowIsExhausted(): void {
		$GLOBALS['npcink_unit_transients']['npcink_governance_core_auto_approval_h1'] = 3;

		$policy = array(
			'policy_decision'     => 'auto_approved',
			'auto_approval_quota' => array(
				'hour_limit'   => 3,
				'hour_ttl'     => 3600,
				'day_limit'    => 10,
				'day_ttl'      => 86400,
				'hour_suffix'  => 'h1',
				'day_suffix'   => 'd1',
			),
		);

		$this->assertFalse( $this->evaluator->consume_auto_approval_quota( $policy ) );
	}

	public function testConsumeQuotaDayExhaustedDocumentsPartialHourConsumption(): void {
		$GLOBALS['npcink_unit_transients']['npcink_governance_core_auto_approval_d1'] = 10;

		$policy = array(
			'policy_decision'     => 'auto_approved',
			'auto_approval_quota' => array(
				'hour_limit'   => 3,
				'hour_ttl'     => 3600,
				'day_limit'    => 10,
				'day_ttl'      => 86400,
				'hour_suffix'  => 'h1',
				'day_suffix'   => 'd1',
			),
		);

		$this->assertFalse( $this->evaluator->consume_auto_approval_quota( $policy ) );
		$this->assertSame(
			1,
			$GLOBALS['npcink_unit_transients']['npcink_governance_core_auto_approval_h1'],
			'documented current quirk: the hour window is consumed before the day check, so a day-exhausted policy still increments the hour counter without rollback'
		);
	}

	public function testCreateDraftEvaluationRejectionTable(): void {
		$evaluator = $this->evaluator;

		$not_target = $evaluator->create_draft_evaluation( 'npcink-abilities-toolkit/set-post-seo-meta', array() );
		$this->assertFalse( $not_target['allowed'] );
		$this->assertSame( array(), $not_target['reasons'], 'other abilities are out of scope with no reasons' );

		$existing = $evaluator->create_draft_evaluation( 'npcink-abilities-toolkit/create-draft', array( 'post_id' => 12 ) );
		$this->assertSame( array( 'guarded_create_draft_rejected_existing_target' ), $existing['reasons'] );

		$type = $evaluator->create_draft_evaluation( 'npcink-abilities-toolkit/create-draft', array( 'post_type' => 'page' ) );
		$this->assertSame( array( 'guarded_create_draft_rejected_post_type' ), $type['reasons'] );

		$status = $evaluator->create_draft_evaluation( 'npcink-abilities-toolkit/create-draft', array( 'status' => 'publish' ) );
		$this->assertSame( array( 'guarded_create_draft_rejected_status' ), $status['reasons'] );

		$untitled = $evaluator->create_draft_evaluation( 'npcink-abilities-toolkit/create-draft', array() );
		$this->assertSame( array( 'guarded_create_draft_rejected_title_missing' ), $untitled['reasons'] );

		$commit = $evaluator->create_draft_evaluation(
			'npcink-abilities-toolkit/create-draft',
			array( 'title' => 'T', 'dry_run' => false )
		);
		$this->assertSame( array( 'guarded_create_draft_rejected_commit_input' ), $commit['reasons'] );

		$scheduled = $evaluator->create_draft_evaluation(
			'npcink-abilities-toolkit/create-draft',
			array( 'title' => 'T', 'post_date' => '2027-01-01' )
		);
		$this->assertSame( array( 'guarded_create_draft_rejected_schedule' ), $scheduled['reasons'] );

		$oversize = $evaluator->create_draft_evaluation(
			'npcink-abilities-toolkit/create-draft',
			array( 'title' => 'T', 'content' => str_repeat( 'a', 20001 ) )
		);
		$this->assertSame( array( 'guarded_create_draft_rejected_content_size' ), $oversize['reasons'] );
	}

	public function testCreateDraftEvaluationAllowsBoundedDryRunDraft(): void {
		$result = $this->evaluator->create_draft_evaluation(
			'npcink-abilities-toolkit/create-draft',
			array(
				'title'   => 'Draft title',
				'dry_run' => true,
			)
		);

		$this->assertTrue( $result['allowed'] );
		$this->assertSame( array( 'guarded_create_draft_draft_only' ), $result['reasons'], 'an allowed create-draft carries the draft-only marker reason' );
	}

	public function testCleanupBatchEvaluationAcceptsGuardedTrashBatchWithEvidence(): void {
		$result = $this->evaluator->cleanup_batch_evaluation(
			'npcink-abilities-toolkit/trash-post',
			array(
				'write_actions' => array(
					array(
						'target_ability_id' => 'npcink-abilities-toolkit/trash-post',
						'requires_approval' => true,
						'commit_execution'  => false,
						'input'             => array( 'post_id' => 7, 'dry_run' => true, 'commit' => false ),
					),
				),
			),
			array(
				'source'       => array( 'type' => 'plan_to_proposal_batch' ),
				'plan_preview' => array(
					'posts' => array(
						array( 'post_id' => 7, 'matched_pattern' => 'npcink-test-content' ),
					),
				),
			),
			array(
				'source'          => 'plan_to_proposal_batch',
				'plan_ability_id' => 'npcink-abilities-toolkit/build-nonproduction-content-cleanup-plan',
			)
		);

		$this->assertTrue( $result['allowed'], 'guarded trash batch with per-post test-content evidence passes' );
	}

	public function testCleanupBatchEvaluationRejectionTable(): void {
		$evaluator = $this->evaluator;
		$preview   = array( 'source' => array( 'type' => 'plan_to_proposal_batch' ) );
		$caller    = array(
			'source'          => 'plan_to_proposal_batch',
			'plan_ability_id' => 'npcink-abilities-toolkit/build-nonproduction-content-cleanup-plan',
		);

		$wrong_ability = $evaluator->cleanup_batch_evaluation( 'other/ability', array(), array(), array() );
		$this->assertFalse( $wrong_ability['allowed'] );
		$this->assertSame( array(), $wrong_ability['reasons'] );

		$wrong_source = $evaluator->cleanup_batch_evaluation( 'npcink-abilities-toolkit/trash-post', array(), $preview, array( 'source' => 'direct_api' ) );
		$this->assertSame( array( 'guarded_cleanup_rejected_source' ), $wrong_source['reasons'] );

		$wrong_plan = $evaluator->cleanup_batch_evaluation( 'npcink-abilities-toolkit/trash-post', array(), $preview, array( 'source' => 'plan_to_proposal_batch', 'plan_ability_id' => 'other/plan' ) );
		$this->assertSame( array( 'guarded_cleanup_rejected_plan_ability' ), $wrong_plan['reasons'] );

		$empty_batch = $evaluator->cleanup_batch_evaluation( 'npcink-abilities-toolkit/trash-post', array(), $preview, $caller );
		$this->assertSame( array( 'guarded_cleanup_rejected_empty_batch' ), $empty_batch['reasons'] );

		$mixed = $evaluator->cleanup_batch_evaluation(
			'npcink-abilities-toolkit/trash-post',
			array( 'write_actions' => array( array( 'target_ability_id' => 'other/ability' ) ) ),
			$preview,
			$caller
		);
		$this->assertSame( array( 'guarded_cleanup_rejected_mixed_target' ), $mixed['reasons'] );

		$no_evidence = $evaluator->cleanup_batch_evaluation(
			'npcink-abilities-toolkit/trash-post',
			array(
				'write_actions' => array(
					array(
						'target_ability_id' => 'npcink-abilities-toolkit/trash-post',
						'requires_approval' => true,
						'input'             => array( 'post_id' => 99, 'dry_run' => true ),
					),
				),
			),
			array( 'source' => array( 'type' => 'plan_to_proposal_batch' ), 'plan_preview' => array( 'posts' => array() ) ),
			$caller
		);
		$this->assertSame( array( 'guarded_cleanup_rejected_missing_test_content_evidence' ), $no_evidence['reasons'] );
	}
}
