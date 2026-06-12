<?php
/**
 * App rate limiter.
 *
 * @package NpcinkGovernanceCore
 */

namespace Npcink\GovernanceCore\Security;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fixed-window app rate limiter backed by the database.
 */
final class App_Rate_Limiter {
	/**
	 * Returns table name.
	 *
	 * @return string
	 */
	public function table_name(): string {
		global $wpdb;

		return $wpdb->prefix . 'npcink_governance_core_app_rate_limits';
	}

	/**
	 * Installs table.
	 *
	 * @return void
	 */
	public function install(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();
		$table           = $this->table_name();

		dbDelta(
			"CREATE TABLE {$table} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				app_id varchar(64) NOT NULL,
				key_id varchar(64) NOT NULL,
				route_family varchar(80) NOT NULL,
				window_start datetime NOT NULL,
				window_end datetime NOT NULL,
				request_count int unsigned DEFAULT 0 NOT NULL,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY app_route_window (app_id, route_family, window_start),
				KEY key_id (key_id),
				KEY window_end (window_end)
			) {$charset_collate};"
		);
	}

	/**
	 * Consumes one request from the app rate limit.
	 *
	 * @param array<string,mixed> $app App row.
	 * @param string              $route_family Route family.
	 * @return array<string,mixed>
	 */
	public function consume( array $app, string $route_family ): array {
		global $wpdb;

		$app_id         = sanitize_text_field( (string) ( $app['app_id'] ?? '' ) );
		$key_id         = sanitize_text_field( (string) ( $app['key_id'] ?? '' ) );
		$route_family   = sanitize_key( $route_family );
		$limit          = max( 1, (int) ( $app['rate_limit'] ?? App_Key_Repository::DEFAULT_RATE_LIMIT ) );
		$window_seconds = max( 60, (int) ( $app['rate_window_seconds'] ?? App_Key_Repository::DEFAULT_RATE_WINDOW ) );
		$now_ts         = time();
		$window_start_ts = $now_ts - ( $now_ts % $window_seconds );
		$window_end_ts  = $window_start_ts + $window_seconds;
		$window_start   = gmdate( 'Y-m-d H:i:s', $window_start_ts );
		$window_end     = gmdate( 'Y-m-d H:i:s', $window_end_ts );
		$now            = current_time( 'mysql', true );

		$updated = $this->increment_existing_window( $app_id, $route_family, $window_start, $limit, $now );
		if ( false === $updated ) {
			return $this->result_from_row( null, $limit, $window_end, false );
		}
		if ( $updated > 0 ) {
			return $this->result_from_row( $this->find_window( $app_id, $route_family, $window_start ), $limit, $window_end, true );
		}

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Core owns this custom governance table.
		$inserted = $wpdb->insert(
			$this->table_name(),
			array(
				'app_id'        => $app_id,
				'key_id'        => $key_id,
				'route_family'  => $route_family,
				'window_start'  => $window_start,
				'window_end'    => $window_end,
				'request_count' => 1,
				'created_at'    => $now,
				'updated_at'    => $now,
			),
			array( '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s' )
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( false !== $inserted ) {
			return $this->result_from_row( $this->find_window( $app_id, $route_family, $window_start ), $limit, $window_end, true );
		}

		$updated = $this->increment_existing_window( $app_id, $route_family, $window_start, $limit, $now );
		if ( false === $updated ) {
			return $this->result_from_row( null, $limit, $window_end, false );
		}

		return $this->result_from_row( $this->find_window( $app_id, $route_family, $window_start ), $limit, $window_end, $updated > 0 );
	}

	/**
	 * Atomically increments an existing fixed window only while under limit.
	 *
	 * @param string $app_id App id.
	 * @param string $route_family Route family.
	 * @param string $window_start Window start.
	 * @param int    $limit Rate limit.
	 * @param string $now Current UTC timestamp.
	 * @return int|false Updated row count, or false on DB error.
	 */
	private function increment_existing_window( string $app_id, string $route_family, string $window_start, int $limit, string $now ) {
		global $wpdb;

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Core owns this custom governance table and needs an atomic conditional increment.
		return $wpdb->query(
			$wpdb->prepare(
				'UPDATE %i SET request_count = request_count + 1, updated_at = %s WHERE app_id = %s AND route_family = %s AND window_start = %s AND request_count < %d',
				$this->table_name(),
				$now,
				$app_id,
				$route_family,
				$window_start,
				$limit
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	/**
	 * Finds a fixed-window counter row.
	 *
	 * @param string $app_id App id.
	 * @param string $route_family Route family.
	 * @param string $window_start Window start.
	 * @return array<string,mixed>|null
	 */
	private function find_window( string $app_id, string $route_family, string $window_start ): ?array {
		global $wpdb;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Core owns this custom governance table.
		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT id, request_count FROM %i WHERE app_id = %s AND route_family = %s AND window_start = %s LIMIT 1',
				$this->table_name(),
				$app_id,
				$route_family,
				$window_start
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		return is_array( $row ) ? $row : null;
	}

	/**
	 * Builds a rate-limit response from the persisted counter.
	 *
	 * @param array<string,mixed>|null $row Counter row.
	 * @param int                      $limit Rate limit.
	 * @param string                   $window_end Window end.
	 * @param bool                     $allowed Whether this request consumed a slot.
	 * @return array<string,mixed>
	 */
	private function result_from_row( ?array $row, int $limit, string $window_end, bool $allowed ): array {
		$count = is_array( $row ) ? (int) ( $row['request_count'] ?? 0 ) : 0;

		return array(
			'allowed'       => $allowed,
			'limit'         => $limit,
			'remaining'     => max( 0, $limit - $count ),
			'reset_at'      => $window_end,
			'request_count' => $count,
		);
	}
}
