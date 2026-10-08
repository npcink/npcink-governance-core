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
}
