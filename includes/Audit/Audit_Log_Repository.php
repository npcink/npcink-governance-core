<?php
/**
 * Audit log repository.
 *
 * @package NpcinkGovernanceCore
 */

namespace Npcink\GovernanceCore\Audit;

use Npcink\GovernanceCore\Security\Request_Context;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stores append-only governance events.
 */
final class Audit_Log_Repository {
	/**
	 * Returns table name.
	 *
	 * @return string
	 */
	public function table_name(): string {
		global $wpdb;

		return $wpdb->prefix . 'npcink_governance_core_audit_log';
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
				event_id varchar(64) NOT NULL,
				event_name varchar(120) NOT NULL,
				proposal_id varchar(64) DEFAULT '' NOT NULL,
				actor_id bigint(20) unsigned DEFAULT 0 NOT NULL,
				metadata_json longtext NULL,
				created_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY event_id (event_id),
				KEY event_name (event_name),
				KEY proposal_id (proposal_id),
				KEY created_at (created_at)
			) {$charset_collate};"
		);
	}

	/**
	 * Records one event.
	 *
	 * @param string              $event_name Event name.
	 * @param array<string,mixed> $metadata Event metadata.
	 * @param string              $proposal_id Optional proposal id.
	 * @return string
	 */
	public function record( string $event_name, array $metadata = array(), string $proposal_id = '' ): string {
		global $wpdb;

		$event_id = function_exists( 'wp_generate_uuid4' ) ? wp_generate_uuid4() : uniqid( 'mai_', true );
		$now      = current_time( 'mysql', true );
		$auth     = Request_Context::audit_metadata();
		if ( ! empty( $auth ) ) {
			$metadata['auth'] = $auth;
		}

		$inserted = $wpdb->insert(
			$this->table_name(),
			array(
				'event_id'      => $event_id,
				'event_name'    => sanitize_text_field( $event_name ),
				'proposal_id'   => sanitize_text_field( $proposal_id ),
				'actor_id'      => get_current_user_id(),
				'metadata_json' => wp_json_encode( $this->sanitize_metadata( $metadata ) ),
				'created_at'    => $now,
			),
			array( '%s', '%s', '%s', '%d', '%s', '%s' )
		);

		return false === $inserted ? '' : $event_id;
	}

	/**
	 * Lists recent events.
	 *
	 * @param int $limit Maximum rows.
	 * @return array<int,array<string,mixed>>
	 */
	public function list_recent( int $limit = 50 ): array {
		return $this->list_filtered( array( 'limit' => $limit ) );
	}

	/**
	 * Lists filtered events.
	 *
	 * @param array<string,mixed> $filters Filters.
	 * @return array<int,array<string,mixed>>
	 */
	public function list_filtered( array $filters = array() ): array {
		global $wpdb;

		$limit          = max( 1, min( 200, absint( $filters['limit'] ?? 50 ) ) );
		$offset         = max( 0, absint( $filters['offset'] ?? 0 ) );
		$order          = 'asc' === sanitize_key( (string) ( $filters['order'] ?? 'desc' ) ) ? 'ASC' : 'DESC';
		$parts          = $this->filtered_query_parts( $filters );
		$where          = $parts['where'];
		$args           = $parts['args'];

		$sql = 'SELECT event_id, event_name, proposal_id, actor_id, metadata_json, created_at FROM ' . $this->table_name();

		if ( ! empty( $where ) ) {
			$sql .= ' WHERE ' . implode( ' AND ', $where );
		}

		$sql   .= ' ORDER BY id ' . $order . ' LIMIT %d OFFSET %d';
		$args[] = $limit;
		$args[] = $offset;

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- SQL is assembled from fixed clauses, whitelisted sort order, and placeholder values.
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $args ), ARRAY_A );
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter

		return array_map( array( $this, 'normalize_row' ), is_array( $rows ) ? $rows : array() );
	}

	/**
	 * Counts filtered events.
	 *
	 * @param array<string,mixed> $filters Filters.
	 * @return int
	 */
	public function count_filtered( array $filters = array() ): int {
		global $wpdb;

		$parts = $this->filtered_query_parts( $filters );
		$where = $parts['where'];
		$args  = $parts['args'];
		$sql   = 'SELECT COUNT(*) FROM ' . $this->table_name();

		if ( ! empty( $where ) ) {
			$sql .= ' WHERE ' . implode( ' AND ', $where );
		}

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- SQL is assembled from fixed clauses and placeholder values.
		$count = empty( $args ) ? (int) $wpdb->get_var( $sql ) : (int) $wpdb->get_var( $wpdb->prepare( $sql, $args ) );
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter

		return $count;
	}

	/**
	 * Counts audit records.
	 *
	 * @return int
	 */
	public function count(): int {
		global $wpdb;

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Custom table name is generated from the WordPress table prefix and no user values are interpolated.
		$count = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . $this->table_name() );
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter

		return $count;
	}

	/**
	 * Normalizes DB row.
	 *
	 * @param array<string,mixed> $row DB row.
	 * @return array<string,mixed>
	 */
	private function normalize_row( array $row ): array {
		$metadata = json_decode( (string) ( $row['metadata_json'] ?? '{}' ), true );

		return array(
			'event_id'    => sanitize_text_field( (string) ( $row['event_id'] ?? '' ) ),
			'event_name'  => sanitize_text_field( (string) ( $row['event_name'] ?? '' ) ),
			'proposal_id' => sanitize_text_field( (string) ( $row['proposal_id'] ?? '' ) ),
			'actor_id'    => (int) ( $row['actor_id'] ?? 0 ),
			'metadata'    => is_array( $metadata ) ? $metadata : array(),
			'created_at'  => sanitize_text_field( (string) ( $row['created_at'] ?? '' ) ),
		);
	}

	/**
	 * Returns a JSON-safe metadata key/value search needle.
	 *
	 * @param string $key Metadata key.
	 * @param string $value Metadata value.
	 * @return string
	 */
	private function metadata_filter_needle( string $key, string $value ): string {
		$encoded = wp_json_encode( $value );
		return '"' . sanitize_key( $key ) . '":' . ( is_string( $encoded ) ? $encoded : '""' );
	}

	/**
	 * Builds filtered audit query parts.
	 *
	 * @param array<string,mixed> $filters Filters.
	 * @return array{where:array<int,string>,args:array<int,mixed>}
	 */
	private function filtered_query_parts( array $filters ): array {
		global $wpdb;

		$proposal_id    = sanitize_text_field( (string) ( $filters['proposal_id'] ?? '' ) );
		$event_name     = sanitize_text_field( (string) ( $filters['event_name'] ?? '' ) );
		$ability_id     = sanitize_text_field( (string) ( $filters['ability_id'] ?? '' ) );
		$app_id         = sanitize_text_field( (string) ( $filters['app_id'] ?? '' ) );
		$key_id         = sanitize_text_field( (string) ( $filters['key_id'] ?? '' ) );
		$caller_type    = sanitize_key( (string) ( $filters['caller_type'] ?? '' ) );
		$correlation_id = sanitize_text_field( (string) ( $filters['correlation_id'] ?? '' ) );
		$exclude_events = is_array( $filters['exclude_event_names'] ?? null ) ? $this->sanitize_event_names( (array) $filters['exclude_event_names'] ) : array();
		$where          = array();
		$args           = array();

		if ( '' !== $proposal_id ) {
			$where[] = 'proposal_id = %s';
			$args[]  = $proposal_id;
		}

		if ( '' !== $event_name ) {
			$where[] = 'event_name = %s';
			$args[]  = $event_name;
		} elseif ( ! empty( $exclude_events ) ) {
			$where[] = 'event_name NOT IN (' . implode( ', ', array_fill( 0, count( $exclude_events ), '%s' ) ) . ')';
			foreach ( $exclude_events as $excluded ) {
				$args[] = $excluded;
			}
		}

		$metadata_filters = array(
			'ability_id'     => $ability_id,
			'app_id'         => $app_id,
			'key_id'         => $key_id,
			'caller_type'    => $caller_type,
			'correlation_id' => $correlation_id,
		);

		foreach ( $metadata_filters as $key => $value ) {
			if ( '' === $value ) {
				continue;
			}

			$where[] = 'metadata_json LIKE %s';
			$args[]  = '%' . $wpdb->esc_like( $this->metadata_filter_needle( (string) $key, (string) $value ) ) . '%';
		}

		return array(
			'where' => $where,
			'args'  => $args,
		);
	}

	/**
	 * Sanitizes audit event names.
	 *
	 * @param array<int,string> $event_names Event names.
	 * @return array<int,string>
	 */
	private function sanitize_event_names( array $event_names ): array {
		$clean = array();

		foreach ( $event_names as $event_name ) {
			$event_name = sanitize_text_field( (string) $event_name );
			if ( '' !== $event_name ) {
				$clean[] = $event_name;
			}
		}

		return array_values( array_unique( $clean ) );
	}

	/**
	 * Sanitizes metadata recursively.
	 *
	 * @param mixed $value Raw value.
	 * @return mixed
	 */
	private function sanitize_metadata( $value ) {
		if ( is_array( $value ) ) {
			$clean = array();
			foreach ( $value as $key => $item ) {
				$clean[ sanitize_key( (string) $key ) ] = $this->sanitize_metadata( $item );
			}
			return $clean;
		}

		if ( is_string( $value ) ) {
			return sanitize_textarea_field( $value );
		}

		if ( is_bool( $value ) || is_int( $value ) || is_float( $value ) || null === $value ) {
			return $value;
		}

		return sanitize_text_field( (string) $value );
	}
}
