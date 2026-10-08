<?php
/**
 * Behavioral tests for the fixed-window app rate limiter.
 *
 * @package NpcinkGovernanceCore
 */

namespace NpcinkGovernanceCore\Tests\Unit;

use Npcink\GovernanceCore\Security\App_Rate_Limiter;
use PHPUnit\Framework\TestCase;

/**
 * Records every SQL statement and returns programmable results.
 */
final class Npcink_Unit_Wpdb_Recorder {
	/** @var string */
	public $prefix = 'wp_';

	/** @var array<int,string> */
	public $query_log = array();

	/** @var array<int,mixed> FIFO return values for query(). */
	public $query_results = array();

	/** @var array<int,mixed> FIFO return values for get_row(). */
	public $row_results = array();

	/** @var mixed Return value for get_var(). */
	public $var_result = '7';

	/**
	 * Prepares a SQL statement by sequentially substituting placeholders.
	 *
	 * @param string $query Query with %i/%s/%d placeholders.
	 * @param mixed  ...$args Arguments.
	 * @return string
	 */
	public function prepare( string $query, ...$args ): string {
		$parts = preg_split( '/(%i|%s|%d|%f)/', $query, -1, PREG_SPLIT_DELIM_CAPTURE );
		$out   = '';
		$index = 0;
		foreach ( $parts as $part ) {
			if ( $part === '%i' ) {
				$out .= '`' . str_replace( '`', '', (string) ( $args[ $index ] ?? '' ) ) . '`';
				$index++;
			} elseif ( $part === '%s' ) {
				$out .= "'" . str_replace( "'", "''", (string) ( $args[ $index ] ?? '' ) ) . "'";
				$index++;
			} elseif ( $part === '%d' ) {
				$out .= (string) (int) ( $args[ $index ] ?? 0 );
				$index++;
			} elseif ( $part === '%f' ) {
				$out .= (string) (float) ( $args[ $index ] ?? 0 );
				$index++;
			} else {
				$out .= $part;
			}
		}
		return $out;
	}

	/**
	 * Logs and answers a query.
	 *
	 * @param string $sql SQL.
	 * @return mixed
	 */
	public function query( string $sql ) {
		$this->query_log[] = $sql;
		if ( 0 === count( $this->query_results ) ) {
			return 1;
		}
		return array_shift( $this->query_results );
	}

	/**
	 * Logs and answers a row fetch.
	 *
	 * @param string $sql SQL.
	 * @param string $output Output mode.
	 * @return mixed
	 */
	public function get_row( string $sql, string $output = 'OBJECT' ) {
		$this->query_log[] = $sql;
		if ( 0 === count( $this->row_results ) ) {
			return null;
		}
		return array_shift( $this->row_results );
	}

	/**
	 * Logs and answers a scalar fetch.
	 *
	 * @param string $sql SQL.
	 * @return mixed
	 */
	public function get_var( string $sql ) {
		$this->query_log[] = $sql;
		return $this->var_result;
	}
}

/**
 * Behavioral tests for App_Rate_Limiter.
 */
final class AppRateLimiterTest extends TestCase {
	/** @var Npcink_Unit_Wpdb_Recorder */
	private $wpdb;

	/** @var mixed Previous global wpdb, restored in tearDown. */
	private $previous_wpdb;

	/** @var App_Rate_Limiter */
	private $limiter;

	/**
	 * Sets up the recorder-backed limiter.
	 */
	protected function setUp(): void {
		$this->previous_wpdb = $GLOBALS['wpdb'] ?? null;
		$this->wpdb          = new Npcink_Unit_Wpdb_Recorder();
		$GLOBALS['wpdb']     = $this->wpdb;
		$this->limiter       = new App_Rate_Limiter();
	}

	/**
	 * Restores the previous global wpdb so the recorder never leaks.
	 */
	protected function tearDown(): void {
		if ( null === $this->previous_wpdb ) {
			unset( $GLOBALS['wpdb'] );
		} else {
			$GLOBALS['wpdb'] = $this->previous_wpdb;
		}
	}

	/**
	 * Provides a minimal app row.
	 *
	 * @return array<string,mixed>
	 */
	private function app(): array {
		return array(
			'app_id'             => 'app-1',
			'key_id'             => 'key-1',
			'rate_limit'         => 5,
			'rate_window_seconds' => 60,
		);
	}

	public function testConsumeReturnsAlignedWindowBounds(): void {
		$this->wpdb->row_results = array( array( 'id' => 1, 'request_count' => 3 ) );

		$result = $this->limiter->consume( $this->app(), 'capabilities' );

		$this->assertSame( 0, (int) ( strtotime( $result['window_start'] ) % 60 ), 'window_start is aligned to the 60s window' );
		$this->assertSame(
			60,
			(int) ( strtotime( $result['reset_at'] ) - strtotime( $result['window_start'] ) ),
			'window length equals rate_window_seconds'
		);
		$this->assertSame( 3, $result['request_count'] );
		$this->assertSame( 2, $result['remaining'] );
		$this->assertSame( 5, $result['limit'] );
		$this->assertTrue( $result['allowed'] );
	}

	public function testConsumeReportsWindowStartForExactWindowRefunds(): void {
		$this->wpdb->row_results = array( array( 'id' => 1, 'request_count' => 1 ) );

		$result = $this->limiter->consume( $this->app(), 'proposals' );

		$this->assertArrayHasKey( 'window_start', $result, 'consume returns window_start so refunds bind the exact window' );
		$upsert = $this->wpdb->query_log[0];
		$this->assertStringContainsString( 'ON DUPLICATE KEY UPDATE', $upsert, 'consume uses one atomic upsert' );
		$this->assertStringContainsString( '`wp_npcink_governance_core_app_rate_limits`', $upsert );
	}

	public function testConsumeOnDatabaseErrorFailsClosed(): void {
		$this->wpdb->query_results = array( false );

		$result = $this->limiter->consume( $this->app(), 'capabilities' );

		$this->assertFalse( $result['allowed'], 'a failed upsert consumes no slot' );
		$this->assertSame( 0, $result['request_count'] );
		$this->assertSame( 5, $result['remaining'] );
	}

	public function testConsumeClampsLimitAndWindowToRepositoryDefaultsFloor(): void {
		$app = array(
			'app_id'             => 'app-2',
			'key_id'             => 'key-2',
			'rate_limit'         => 0,
			'rate_window_seconds' => 1,
		);

		$result = $this->limiter->consume( $app, 'audit' );

		$this->assertSame( 1, $result['limit'], 'rate_limit floor is 1' );
		$this->assertSame(
			60,
			(int) ( strtotime( $result['reset_at'] ) - strtotime( $result['window_start'] ) ),
			'window seconds floor is 60'
		);
	}

	public function testRefundRequiresAppRouteAndWindow(): void {
		$this->assertFalse( $this->limiter->refund( array(), 'capabilities', '2026-10-08 00:00:00' ) );
		$this->assertFalse( $this->limiter->refund( $this->app(), '', '2026-10-08 00:00:00' ) );
		$this->assertFalse( $this->limiter->refund( $this->app(), 'capabilities', '' ) );
		$this->assertCount( 0, $this->wpdb->query_log, 'no refund SQL runs for incomplete input' );
	}

	public function testRefundDecrementsOnlyTheExactWindow(): void {
		$this->wpdb->query_results = array( 1 );

		$refunded = $this->limiter->refund( $this->app(), 'proposals', '2026-10-08 00:01:00' );

		$this->assertTrue( $refunded );
		$sql = $this->wpdb->query_log[0];
		$this->assertStringContainsString( 'GREATEST(0, request_count - 1)', $sql, 'refund is floored at zero' );
		$this->assertStringContainsString( "'2026-10-08 00:01:00'", $sql, 'refund binds the exact consumed window' );
		$this->assertStringContainsString( 'request_count > 0', $sql, 'refund never goes negative' );
	}

	public function testRefundReportsNoRowTouched(): void {
		$this->wpdb->query_results = array( 0 );

		$this->assertFalse( $this->limiter->refund( $this->app(), 'proposals', '2026-10-08 00:01:00' ) );
	}

	public function testDeleteExpiredBeforeClampsLimitToBounds(): void {
		$this->limiter->delete_expired_before( '2026-10-08 00:00:00', 5000 );
		$this->assertStringContainsString( 'LIMIT 1000', $this->wpdb->query_log[0], 'limit clamps to 1000' );

		$this->limiter->delete_expired_before( '2026-10-08 00:00:00', 0 );
		$this->assertStringContainsString( 'LIMIT 1', $this->wpdb->query_log[1], 'limit clamps to 1' );
	}

	public function testDeleteExpiredBeforeReturnsNullOnDatabaseError(): void {
		$this->wpdb->query_results = array( false );

		$this->assertNull( $this->limiter->delete_expired_before( '2026-10-08 00:00:00' ) );
	}

	public function testCountExpiredBeforeCastsToInteger(): void {
		$this->wpdb->var_result = '42';

		$this->assertSame( 42, $this->limiter->count_expired_before( '2026-10-08 00:00:00' ) );
		$this->assertStringContainsString( 'SELECT COUNT(*)', $this->wpdb->query_log[0] );
	}
}
