<?php
/** Database stub test module. @package NpcinkGovernanceCore */

/**
 * In-memory WPDB stub with injectable table failures.
 */
final class Npcink_Governance_Core_Fail_Closed_WPDB {
	/**
	 * Table prefix.
	 *
	 * @var string
	 */
	public $prefix = 'wp_';

	/**
	 * Stored rows by table.
	 *
	 * @var array<string,array<int,array<string,mixed>>>
	 */
	public $tables = array();

	/**
	 * Tables whose insert should fail.
	 *
	 * @var array<int,string>
	 */
	public $fail_insert_tables = array();

	/**
	 * Audit event names whose insert should fail.
	 *
	 * @var array<int,string>
	 */
	public $fail_insert_event_names = array();

	/**
	 * Tables whose update should fail.
	 *
	 * @var array<int,string>
	 */
	public $fail_update_tables = array();

	/**
	 * Last insert id.
	 *
	 * @var int
	 */
	public $insert_id = 0;

	/**
	 * Auto-increment counters.
	 *
	 * @var array<string,int>
	 */
	private $auto_increment = array();

	/**
	 * Inserts a row.
	 *
	 * @param string              $table Table.
	 * @param array<string,mixed> $record Record.
	 * @return int|false
	 */
	public function insert( string $table, array $record, array $format = array() ) {
		if ( in_array( $table, $this->fail_insert_tables, true ) ) {
			return false;
		}
		if ( false !== strpos( $table, 'npcink_governance_core_audit_log' ) && in_array( (string) ( $record['event_name'] ?? '' ), $this->fail_insert_event_names, true ) ) {
			return false;
		}
		if ( false !== strpos( $table, 'npcink_governance_core_audit_log' ) ) {
			foreach ( $this->tables[ $table ] ?? array() as $row ) {
				if ( (string) ( $row['event_id'] ?? '' ) === (string) ( $record['event_id'] ?? '' ) ) {
					return false;
				}
			}
		}

		$this->auto_increment[ $table ] = ( $this->auto_increment[ $table ] ?? 0 ) + 1;
		$this->insert_id               = $this->auto_increment[ $table ];
		$record                        = array( 'id' => $this->insert_id ) + $record;
		$this->tables[ $table ][]      = $record;

		return 1;
	}

	/**
	 * Deletes matching rows.
	 *
	 * @param string              $table Table.
	 * @param array<string,mixed> $where Where.
	 * @return int|false
	 */
	public function delete( string $table, array $where, array $where_format = array() ) {
		if ( empty( $this->tables[ $table ] ) ) {
			return 0;
		}

		$deleted = 0;
		foreach ( $this->tables[ $table ] as $index => $row ) {
			if ( $this->row_matches( $row, $where ) ) {
				unset( $this->tables[ $table ][ $index ] );
				++$deleted;
			}
		}

		$this->tables[ $table ] = array_values( $this->tables[ $table ] );

		return $deleted;
	}

	/**
	 * Updates matching rows.
	 *
	 * @param string              $table Table.
	 * @param array<string,mixed> $data Data.
	 * @param array<string,mixed> $where Where.
	 * @return int|false
	 */
	public function update( string $table, array $data, array $where, array $format = array(), array $where_format = array() ) {
		if ( in_array( $table, $this->fail_update_tables, true ) ) {
			return false;
		}

		if ( empty( $this->tables[ $table ] ) ) {
			return 0;
		}

		$updated = 0;
		foreach ( $this->tables[ $table ] as $index => $row ) {
			if ( ! $this->row_matches( $row, $where ) ) {
				continue;
			}

			$this->tables[ $table ][ $index ] = array_merge( $row, $data );
			++$updated;
		}

		return $updated;
	}

	/**
	 * Captures prepared SQL and arguments.
	 *
	 * @param string $query Query.
	 * @param mixed  ...$args Arguments.
	 * @return array{query:string,args:array<int,mixed>}
	 */
	public function prepare( string $query, ...$args ): array {
		if ( 1 === count( $args ) && is_array( $args[0] ) ) {
			$args = $args[0];
		}

		$kept_args = array();
		$index     = 0;
		$query     = preg_replace_callback(
			'/%[isdFf]/',
			static function ( array $matches ) use ( $args, &$kept_args, &$index ): string {
				$placeholder = (string) $matches[0];
				$value       = $args[ $index ] ?? null;
				++$index;

				if ( '%i' === $placeholder ) {
					return (string) $value;
				}

				$kept_args[] = $value;
				return $placeholder;
			},
			$query
		);

		return array(
			'query' => is_string( $query ) ? $query : '',
			'args'  => $kept_args,
		);
	}

	/**
	 * Returns one row for the limited queries used by the tests.
	 *
	 * @param mixed $query Query.
	 * @return array<string,mixed>|null
	 */
	public function get_row( $query, string $output = ARRAY_A ) {
		$sql  = is_array( $query ) ? (string) ( $query['query'] ?? '' ) : (string) $query;
		$args = is_array( $query ) ? (array) ( $query['args'] ?? array() ) : array();

		if ( false !== strpos( $sql, 'npcink_governance_core_proposals' ) && false !== strpos( $sql, 'proposal_id = %s' ) ) {
			return $this->first_matching( $this->prefix . 'npcink_governance_core_proposals', array( 'proposal_id' => (string) ( $args[0] ?? '' ) ) );
		}

		if ( false !== strpos( $sql, 'npcink_governance_core_read_requests' ) && false !== strpos( $sql, 'request_id = %s' ) ) {
			return $this->first_matching( $this->prefix . 'npcink_governance_core_read_requests', array( 'request_id' => (string) ( $args[0] ?? '' ) ) );
		}

		if ( false !== strpos( $sql, 'npcink_governance_core_app_keys' ) && false !== strpos( $sql, 'key_id = %s' ) ) {
			return $this->first_matching( $this->prefix . 'npcink_governance_core_app_keys', array( 'key_id' => (string) ( $args[0] ?? '' ) ) );
		}

		if ( false !== strpos( $sql, 'npcink_governance_core_app_rate_limits' ) && false !== strpos( $sql, 'window_start = %s' ) ) {
			return $this->first_matching(
				$this->prefix . 'npcink_governance_core_app_rate_limits',
				array(
					'app_id'       => (string) ( $args[0] ?? '' ),
					'route_family' => (string) ( $args[1] ?? '' ),
					'window_start' => (string) ( $args[2] ?? '' ),
				)
			);
		}

		return null;
	}

	/**
	 * Returns result rows.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function get_results( $query = null, string $output = ARRAY_A ): array {
		$sql  = is_array( $query ) ? (string) ( $query['query'] ?? '' ) : (string) $query;
		$args = is_array( $query ) ? (array) ( $query['args'] ?? array() ) : array();

		if ( false !== strpos( $sql, 'npcink_governance_core_audit_log' ) ) {
			return $this->filter_audit_rows( $sql, $args );
		}

		if ( false !== strpos( $sql, 'npcink_governance_core_read_requests' ) ) {
			return $this->filter_read_request_rows( $sql, $args );
		}

		return array();
	}

	/**
	 * Runs a limited direct query used by rate-limit conditional increments.
	 *
	 * @param mixed $query Query.
	 * @return int|false
	 */
	public function query( $query ) {
		$sql  = is_array( $query ) ? (string) ( $query['query'] ?? '' ) : (string) $query;
		$args = is_array( $query ) ? (array) ( $query['args'] ?? array() ) : array();

		if ( false !== strpos( $sql, 'npcink_governance_core_app_rate_limits' ) && false !== strpos( $sql, 'ON DUPLICATE KEY UPDATE' ) ) {
			$table        = $this->prefix . 'npcink_governance_core_app_rate_limits';
			$app_id       = (string) ( $args[0] ?? '' );
			$key_id       = (string) ( $args[1] ?? '' );
			$route_family = (string) ( $args[2] ?? '' );
			$window_start = (string) ( $args[3] ?? '' );
			$window_end   = (string) ( $args[4] ?? '' );
			$now          = (string) ( $args[5] ?? '' );
			$limit        = (int) ( $args[7] ?? 0 );

			foreach ( $this->tables[ $table ] ?? array() as $index => $row ) {
				if ( $app_id !== (string) ( $row['app_id'] ?? '' ) || $route_family !== (string) ( $row['route_family'] ?? '' ) || $window_start !== (string) ( $row['window_start'] ?? '' ) ) {
					continue;
				}
				if ( (int) ( $row['request_count'] ?? 0 ) >= $limit ) {
					return 0;
				}
				$this->tables[ $table ][ $index ]['request_count'] = (int) $row['request_count'] + 1;
				$this->tables[ $table ][ $index ]['updated_at']    = $now;
				return 2;
			}

			$this->auto_increment[ $table ] = ( $this->auto_increment[ $table ] ?? 0 ) + 1;
			$this->insert_id               = $this->auto_increment[ $table ];
			$this->tables[ $table ][]      = array(
				'id'            => $this->insert_id,
				'app_id'        => $app_id,
				'key_id'        => $key_id,
				'route_family'  => $route_family,
				'window_start'  => $window_start,
				'window_end'    => $window_end,
				'request_count' => 1,
				'created_at'    => $now,
				'updated_at'    => $now,
			);
			return 1;
		}

		if ( false !== strpos( $sql, 'npcink_governance_core_app_rate_limits' ) && false !== strpos( $sql, 'request_count = request_count + 1' ) ) {
			$table        = $this->prefix . 'npcink_governance_core_app_rate_limits';
			$now          = (string) ( $args[0] ?? '' );
			$app_id       = (string) ( $args[1] ?? '' );
			$route_family = (string) ( $args[2] ?? '' );
			$window_start = (string) ( $args[3] ?? '' );
			$limit        = (int) ( $args[4] ?? 0 );

			foreach ( $this->tables[ $table ] ?? array() as $index => $row ) {
				if (
					$app_id === (string) ( $row['app_id'] ?? '' )
					&& $route_family === (string) ( $row['route_family'] ?? '' )
					&& $window_start === (string) ( $row['window_start'] ?? '' )
					&& (int) ( $row['request_count'] ?? 0 ) < $limit
				) {
					$this->tables[ $table ][ $index ]['request_count'] = (int) ( $row['request_count'] ?? 0 ) + 1;
					$this->tables[ $table ][ $index ]['updated_at']    = $now;
					return 1;
				}
			}

			return 0;
		}

		return 0;
	}

	/**
	 * Returns scalar value.
	 *
	 * @return int
	 */
	public function get_var( $query = null ): int {
		return 0;
	}

	/**
	 * Escapes LIKE fragments.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	public function esc_like( string $text ): string {
		return addcslashes( $text, '_%' );
	}

	/**
	 * Returns charset collate.
	 *
	 * @return string
	 */
	public function get_charset_collate(): string {
		return '';
	}

	/**
	 * Returns table rows.
	 *
	 * @param string $table Table.
	 * @return array<int,array<string,mixed>>
	 */
	public function rows( string $table ): array {
		return $this->tables[ $table ] ?? array();
	}

	/**
	 * Returns the first matching row.
	 *
	 * @param string              $table Table.
	 * @param array<string,mixed> $where Where.
	 * @return array<string,mixed>|null
	 */
	private function first_matching( string $table, array $where ): ?array {
		foreach ( $this->tables[ $table ] ?? array() as $row ) {
			if ( $this->row_matches( $row, $where ) ) {
				return $row;
			}
		}

		return null;
	}

	/**
	 * Returns filtered audit rows for repository queries used by tests.
	 *
	 * @param string           $sql SQL fragment.
	 * @param array<int,mixed> $args Prepared args.
	 * @return array<int,array<string,mixed>>
	 */
	private function filter_audit_rows( string $sql, array $args ): array {
		$rows      = $this->tables[ $this->prefix . 'npcink_governance_core_audit_log' ] ?? array();
		$arg_index = 0;

		if ( false !== strpos( $sql, 'proposal_id = %s' ) ) {
			$proposal_id = (string) ( $args[ $arg_index ] ?? '' );
			++$arg_index;
			$rows = array_values(
				array_filter(
					$rows,
					static function ( array $row ) use ( $proposal_id ): bool {
						return $proposal_id === (string) ( $row['proposal_id'] ?? '' );
					}
				)
			);
		}

		if ( false !== strpos( $sql, 'event_name = %s' ) ) {
			$event_name = (string) ( $args[ $arg_index ] ?? '' );
			$rows = array_values(
				array_filter(
					$rows,
					static function ( array $row ) use ( $event_name ): bool {
						return $event_name === (string) ( $row['event_name'] ?? '' );
					}
				)
			);
		}

		return $rows;
	}

	/**
	 * Returns filtered read request rows.
	 *
	 * @param string           $sql SQL fragment.
	 * @param array<int,mixed> $args Prepared args.
	 * @return array<int,array<string,mixed>>
	 */
	private function filter_read_request_rows( string $sql, array $args ): array {
		$rows = $this->tables[ $this->prefix . 'npcink_governance_core_read_requests' ] ?? array();

		if ( false !== strpos( $sql, 'status = %s' ) ) {
			$status = (string) ( $args[0] ?? '' );
			$rows   = array_values(
				array_filter(
					$rows,
					static function ( array $row ) use ( $status ): bool {
						return $status === (string) ( $row['status'] ?? '' );
					}
				)
			);
		}

		return $rows;
	}

	/**
	 * Checks row matches.
	 *
	 * @param array<string,mixed> $row Row.
	 * @param array<string,mixed> $where Where.
	 * @return bool
	 */
	private function row_matches( array $row, array $where ): bool {
		foreach ( $where as $key => $value ) {
			if ( (string) ( $row[ $key ] ?? '' ) !== (string) $value ) {
				return false;
			}
		}

		return true;
	}
}

