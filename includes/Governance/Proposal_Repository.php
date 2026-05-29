<?php
/**
 * Proposal repository.
 *
 * @package MagickAICore
 */

namespace MagickAI\Core\Governance;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Persists AI operation proposals.
 */
final class Proposal_Repository {
	/**
	 * Returns table name.
	 *
	 * @return string
	 */
	public function table_name(): string {
		global $wpdb;

		return $wpdb->prefix . 'magick_ai_core_proposals';
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
				proposal_id varchar(64) NOT NULL,
				ability_id varchar(190) NOT NULL,
				status varchar(40) NOT NULL,
				title text NULL,
				summary longtext NULL,
				input_json longtext NULL,
				preview_json longtext NULL,
				caller_json longtext NULL,
				created_by bigint(20) unsigned DEFAULT 0 NOT NULL,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY proposal_id (proposal_id),
				KEY ability_id (ability_id),
				KEY status (status),
				KEY created_at (created_at)
			) {$charset_collate};"
		);
	}

	/**
	 * Creates a proposal.
	 *
	 * @param array<string,mixed> $data Proposal data.
	 * @return array<string,mixed>
	 */
	public function create( array $data ): array {
		global $wpdb;

		$proposal_id = function_exists( 'wp_generate_uuid4' ) ? wp_generate_uuid4() : uniqid( 'proposal_', true );
		$now         = current_time( 'mysql', true );
		$record      = array(
			'proposal_id'  => $proposal_id,
			'ability_id'   => sanitize_text_field( (string) ( $data['ability_id'] ?? '' ) ),
			'status'       => 'pending',
			'title'        => sanitize_text_field( (string) ( $data['title'] ?? '' ) ),
			'summary'      => sanitize_textarea_field( (string) ( $data['summary'] ?? '' ) ),
			'input_json'   => wp_json_encode( $this->sanitize_payload( $data['input'] ?? array() ) ),
			'preview_json' => wp_json_encode( $this->sanitize_payload( $data['preview'] ?? array() ) ),
			'caller_json'  => wp_json_encode( $this->sanitize_payload( $data['caller'] ?? array() ) ),
			'created_by'   => get_current_user_id(),
			'created_at'   => $now,
			'updated_at'   => $now,
		);

		$wpdb->insert(
			$this->table_name(),
			$record,
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s' )
		);

		return $this->normalize_row( $record );
	}

	/**
	 * Lists recent proposals.
	 *
	 * @param int $limit Maximum rows.
	 * @param string $status Optional status filter.
	 * @return array<int,array<string,mixed>>
	 */
	public function list_recent( int $limit = 50, string $status = '' ): array {
		global $wpdb;

		$limit = max( 1, min( 200, $limit ) );
		$status = sanitize_key( $status );
		$sql    = 'SELECT proposal_id, ability_id, status, title, summary, input_json, preview_json, caller_json, created_by, created_at, updated_at FROM ' . $this->table_name();
		$args   = array();

		if ( '' !== $status ) {
			$sql   .= ' WHERE status = %s';
			$args[] = $status;
		}

		$sql   .= ' ORDER BY id DESC LIMIT %d';
		$args[] = $limit;

		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $args ), ARRAY_A );

		return array_map( array( $this, 'normalize_row' ), is_array( $rows ) ? $rows : array() );
	}

	/**
	 * Finds a proposal by id.
	 *
	 * @param string $proposal_id Proposal id.
	 * @return array<string,mixed>|null
	 */
	public function find( string $proposal_id ): ?array {
		global $wpdb;

		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT proposal_id, ability_id, status, title, summary, input_json, preview_json, caller_json, created_by, created_at, updated_at FROM ' . $this->table_name() . ' WHERE proposal_id = %s LIMIT 1',
				sanitize_text_field( $proposal_id )
			),
			ARRAY_A
		);

		return is_array( $row ) ? $this->normalize_row( $row ) : null;
	}

	/**
	 * Updates proposal status.
	 *
	 * @param string $proposal_id Proposal id.
	 * @param string $status New status.
	 * @return array<string,mixed>|null
	 */
	public function update_status( string $proposal_id, string $status ): ?array {
		global $wpdb;

		$proposal_id = sanitize_text_field( $proposal_id );
		$status      = sanitize_key( $status );
		$allowed     = array( 'pending', 'approved', 'rejected' );

		if ( ! in_array( $status, $allowed, true ) ) {
			return null;
		}

		$wpdb->update(
			$this->table_name(),
			array(
				'status'     => $status,
				'updated_at' => current_time( 'mysql', true ),
			),
			array( 'proposal_id' => $proposal_id ),
			array( '%s', '%s' ),
			array( '%s' )
		);

		return $this->find( $proposal_id );
	}

	/**
	 * Counts proposals.
	 *
	 * @return int
	 */
	public function count(): int {
		global $wpdb;

		return (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . $this->table_name() );
	}

	/**
	 * Normalizes row.
	 *
	 * @param array<string,mixed> $row DB row.
	 * @return array<string,mixed>
	 */
	private function normalize_row( array $row ): array {
		return array(
			'proposal_id' => sanitize_text_field( (string) ( $row['proposal_id'] ?? '' ) ),
			'ability_id'  => sanitize_text_field( (string) ( $row['ability_id'] ?? '' ) ),
			'status'      => sanitize_key( (string) ( $row['status'] ?? '' ) ),
			'title'       => sanitize_text_field( (string) ( $row['title'] ?? '' ) ),
			'summary'     => sanitize_textarea_field( (string) ( $row['summary'] ?? '' ) ),
			'input'       => $this->decode_json_field( $row['input_json'] ?? '' ),
			'preview'     => $this->decode_json_field( $row['preview_json'] ?? '' ),
			'caller'      => $this->decode_json_field( $row['caller_json'] ?? '' ),
			'created_by'  => (int) ( $row['created_by'] ?? 0 ),
			'created_at'  => sanitize_text_field( (string) ( $row['created_at'] ?? '' ) ),
			'updated_at'  => sanitize_text_field( (string) ( $row['updated_at'] ?? '' ) ),
		);
	}

	/**
	 * Decodes JSON payload field.
	 *
	 * @param mixed $json JSON string.
	 * @return mixed
	 */
	private function decode_json_field( $json ) {
		$decoded = json_decode( (string) $json, true );

		return null === $decoded ? array() : $decoded;
	}

	/**
	 * Sanitizes structured payload recursively.
	 *
	 * @param mixed $value Raw value.
	 * @return mixed
	 */
	private function sanitize_payload( $value ) {
		if ( is_array( $value ) ) {
			$clean = array();
			foreach ( $value as $key => $item ) {
				$clean[ sanitize_key( (string) $key ) ] = $this->sanitize_payload( $item );
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
