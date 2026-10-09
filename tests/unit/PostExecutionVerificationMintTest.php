<?php
/**
 * Behavioral tests for result-bound verification read minting.
 *
 * @package NpcinkGovernanceCore
 */

namespace NpcinkGovernanceCore\Tests\Unit;

use Npcink\GovernanceCore\Audit\Audit_Log_Repository;
use Npcink\GovernanceCore\Capabilities\Ability_Registry_Adapter;
use Npcink\GovernanceCore\Governance\Commit_Preflight_Service;
use Npcink\GovernanceCore\Governance\Proposal_Repository;
use PHPUnit\Framework\TestCase;

/**
 * Covers the pure derivation rules of result-bound minting (ADR-012).
 */
final class PostExecutionVerificationMintTest extends TestCase {
	/**
	 * Returns the service without a read-request backend (minting stays inert).
	 *
	 * @return Commit_Preflight_Service
	 */
	private function service(): Commit_Preflight_Service {
		return new Commit_Preflight_Service( new Proposal_Repository(), new Ability_Registry_Adapter(), new Audit_Log_Repository(), null );
	}

	/**
	 * Invokes the result-bound mint directly (the method is public).
	 *
	 * @param array<int,array<string,mixed>> $actions Recorded actions.
	 * @return array<string,mixed>
	 */
	private function mint( array $actions ): array {
		return $this->service()->mint_result_bound_verification_reads( 'p1', 'corr-1', $actions );
	}

	/**
	 * Unpaired write abilities produce no reads and no denials.
	 */
	public function test_unpaired_write_is_skipped(): void {
		$result = $this->mint( array( array( 'ability_id' => 'npcink-abilities-toolkit/publish-post', 'result' => array( 'post_id' => 5 ) ) ) );

		$this->assertSame( array( 'granted' => array(), 'denied' => array() ), $result );
	}

	/**
	 * A chained update addressing a just-created post derives its read from the recorded result.
	 */
	public function test_result_bound_post_read_is_derived(): void {
		$granted = $this->mint( array( array( 'ability_id' => 'npcink-abilities-toolkit/update-post-blocks', 'result' => array( 'post_id' => 77 ) ) ) );

		// Minting is inert without the read-request backend; the denial list proves derivation ran.
		$this->assertNotEmpty( $granted['denied'] );
		$this->assertSame( 'npcink-abilities-toolkit/get-post-blocks', $granted['denied'][0]['ability_id'] );
		$this->assertSame( 'mint_unavailable', $granted['denied'][0]['reason'] );
	}

	/**
	 * A result with no object reference is denied explicitly.
	 */
	public function test_missing_result_object_is_denied(): void {
		$granted = $this->mint( array( array( 'ability_id' => 'npcink-abilities-toolkit/update-post-blocks', 'result' => array() ) ) );

		$this->assertSame( array(), $granted['granted'] );
		$this->assertSame( 'result_object_missing', $granted['denied'][0]['reason'] );
	}

	/**
	 * Statically addressed writes are denied: they have a provable preflight minting path.
	 */
	public function test_static_object_is_denied_and_directed_to_preflight_mint(): void {
		$granted = $this->mint( array( array( 'ability_id' => 'npcink-abilities-toolkit/update-post-blocks', 'input' => array( 'post_id' => 9 ), 'result' => array( 'post_id' => 9 ) ) ) );

		$this->assertSame( array(), $granted['granted'] );
		$this->assertSame( 'static_object_use_preflight_mint', $granted['denied'][0]['reason'] );
	}

	/**
	 * Abilities the proposal does not carry are denied membership.
	 */
	public function test_ability_outside_proposal_is_denied(): void {
		$service = new Commit_Preflight_Service( new Proposal_Repository(), new Ability_Registry_Adapter(), new Audit_Log_Repository(), null );
		$result  = $service->mint_result_bound_verification_reads( 'p1', 'corr-1', array( array( 'ability_id' => 'npcink-abilities-toolkit/update-post-blocks', 'result' => array( 'post_id' => 5 ) ) ), array( 'ability_id' => 'npcink-abilities-toolkit/publish-post', 'input' => array( 'write_actions' => array( array( 'target_ability_id' => 'npcink-abilities-toolkit/publish-post' ) ) ) ) );

		$this->assertSame( 'ability_not_in_proposal', $result['denied'][0]['reason'] );
	}

	/**
	 * Template results may address by slug when no post id exists.
	 */
	public function test_slug_addressed_template_result_is_derived(): void {
		$granted = $this->mint( array( array( 'ability_id' => 'npcink-abilities-toolkit/upsert-template-blocks', 'result' => array( 'slug' => 'single' ) ) ) );

		$this->assertSame( 'npcink-abilities-toolkit/get-template-blocks', $granted['denied'][0]['ability_id'] );
		$this->assertSame( 'mint_unavailable', $granted['denied'][0]['reason'] );
	}
}
