<?php
/**
 * Audit log repository.
 *
 * @package MagickAICore
 */

namespace MagickAI\Core\Audit;

use MagickAI\Core\Security\Request_Context;

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

		return $wpdb->prefix . 'magick_ai_core_audit_log';
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

		$limit       = max( 1, min( 200, absint( $filters['limit'] ?? 50 ) ) );
		$proposal_id = sanitize_text_field( (string) ( $filters['proposal_id'] ?? '' ) );
		$event_name  = sanitize_text_field( (string) ( $filters['event_name'] ?? '' ) );
		$where       = array();
		$args        = array();

		if ( '' !== $proposal_id ) {
			$where[] = 'proposal_id = %s';
			$args[]  = $proposal_id;
		}

		if ( '' !== $event_name ) {
			$where[] = 'event_name = %s';
			$args[]  = $event_name;
		}

		$sql = 'SELECT event_id, event_name, proposal_id, actor_id, metadata_json, created_at FROM ' . $this->table_name();

		if ( ! empty( $where ) ) {
			$sql .= ' WHERE ' . implode( ' AND ', $where );
		}

		$sql   .= ' ORDER BY id DESC LIMIT %d';
		$args[] = $limit;

		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $args ), ARRAY_A );

		return array_map( array( $this, 'normalize_row' ), is_array( $rows ) ? $rows : array() );
	}

	/**
	 * Counts audit records.
	 *
	 * @return int
	 */
	public function count(): int {
		global $wpdb;

		return (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . $this->table_name() );
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
