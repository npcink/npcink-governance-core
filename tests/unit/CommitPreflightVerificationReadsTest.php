<?php
/**
 * Behavioral tests for execution verification read minting at commit preflight.
 *
 * @package NpcinkGovernanceCore
 */

namespace NpcinkGovernanceCore\Tests\Unit;

use Npcink\GovernanceCore\Audit\Audit_Log_Repository;
use Npcink\GovernanceCore\Capabilities\Ability_Registry_Adapter;
use Npcink\GovernanceCore\Governance\Commit_Preflight_Service;
use Npcink\GovernanceCore\Governance\Proposal_Repository;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Covers the pure pairing and object-scope rules of verification read minting.
 */
final class CommitPreflightVerificationReadsTest extends TestCase {
	/**
	 * Returns the service with nullable read-request dependencies.
	 *
	 * @return Commit_Preflight_Service
	 */
	private function service(): Commit_Preflight_Service {
		return new Commit_Preflight_Service( new Proposal_Repository(), new Ability_Registry_Adapter(), new Audit_Log_Repository(), null );
	}

	/**
	 * Invokes one private method.
	 *
	 * @param string              $name Method name.
	 * @param array<int,mixed>    $args Arguments.
	 * @return mixed
	 */
	private function invoke( string $name, array $args ) {
		$method = new ReflectionMethod( Commit_Preflight_Service::class, $name );
		$method->setAccessible( true );

		return $method->invokeArgs( $this->service(), $args );
	}

	/**
	 * The pairing map covers exactly the Adapter block readback contract.
	 */
	public function test_pairing_map_matches_adapter_block_readback_contract(): void {
		$expected = array(
			'npcink-abilities-toolkit/update-post-blocks'          => 'npcink-abilities-toolkit/get-post-blocks',
			'npcink-abilities-toolkit/update-template-blocks'      => 'npcink-abilities-toolkit/get-template-blocks',
			'npcink-abilities-toolkit/upsert-template-blocks'      => 'npcink-abilities-toolkit/get-template-blocks',
			'npcink-abilities-toolkit/update-template-part-blocks' => 'npcink-abilities-toolkit/get-template-part-blocks',
		);

		$this->assertSame( $expected, Commit_Preflight_Service::VERIFICATION_READ_ABILITIES );
	}

	/**
	 * Post block verification reads must target the written post id.
	 */
	public function test_post_readback_requires_same_post_id(): void {
		$checker = fn( array $write_input, array $read_input ): bool => $this->invoke(
			'verification_read_targets_same_object',
			array( 'npcink-abilities-toolkit/update-post-blocks', $write_input, $read_input )
		);

		$this->assertTrue( $checker( array( 'post_id' => 42 ), array( 'post_id' => 42, 'include_inner_blocks' => true ) ) );
		$this->assertFalse( $checker( array( 'post_id' => 42 ), array( 'post_id' => 43 ) ) );
		$this->assertFalse( $checker( array( 'post_id' => 0 ), array( 'post_id' => 0 ) ) );
		$this->assertFalse( $checker( array( 'post_id' => 42 ), array( 'slug' => 'single' ) ) );
	}

	/**
	 * Template verification reads may address the object by id or slug.
	 */
	public function test_template_readback_accepts_id_or_slug_match(): void {
		$checker = fn( array $write_input, array $read_input ): bool => $this->invoke(
			'verification_read_targets_same_object',
			array( 'npcink-abilities-toolkit/update-template-blocks', $write_input, $read_input )
		);

		$this->assertTrue( $checker( array( 'post_id' => 7 ), array( 'post_id' => 7 ) ) );
		$this->assertTrue( $checker( array( 'slug' => 'single' ), array( 'slug' => 'single' ) ) );
		$this->assertFalse( $checker( array( 'slug' => 'single' ), array( 'slug' => 'page' ) ) );
		$this->assertFalse( $checker( array( 'post_id' => 7 ), array( 'slug' => 'single' ) ) );
		$this->assertFalse( $checker( array(), array() ) );
	}

	/**
	 * Minting without the read-request service stays inert and fail-open.
	 */
	public function test_mint_without_read_request_service_is_inert(): void {
		$result = $this->invoke(
			'mint_execution_verification_reads',
			array( array( 'proposal_id' => 'p1', 'ability_id' => 'npcink-abilities-toolkit/update-post-blocks', 'input' => array( 'post_id' => 42 ) ), array( 'verification_reads' => array( array( 'ability_id' => 'npcink-abilities-toolkit/get-post-blocks', 'input' => array( 'post_id' => 42 ) ) ) ), 'corr-1' )
		);

		$this->assertSame( array( 'granted' => array(), 'denied' => array() ), $result );
	}
}
