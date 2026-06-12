<?php
/**
 * Sensitive read request repository.
 *
 * @package NpcinkGovernanceCore
 */

namespace Npcink\GovernanceCore\Governance;

use Npcink\GovernanceCore\Security\Sensitive_Data_Redactor;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Persists Core-owned sensitive read authorization requests.
 */
final class Read_Request_Repository {
	const STATUS_PENDING  = 'pending';
	const STATUS_APPROVED = 'approved';
	const STATUS_REJECTED = 'rejected';
	const STATUS_EXPIRED  = 'expired';
	const STATUS_CONSUMED = 'consumed';

	/**
	 * Returns table name.
	 *
	 * @return string
	 */
	public function table_name(): string {
		global $wpdb;

		return $wpdb->prefix . 'npcink_governance_core_read_requests';
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
				request_id varchar(64) NOT NULL,
				ability_id varchar(190) NOT NULL,
				input_hash varchar(64) NOT NULL,
				status varchar(40) NOT NULL,
				requested_input_summary longtext NULL,
				sensitivity varchar(40) NOT NULL,
				data_classes_json longtext NULL,
				redaction_level varchar(80) NOT NULL,
				purpose longtext NULL,
				caller_json longtext NULL,
				bounds_json longtext NULL,
				correlation_id varchar(64) NOT NULL,
				expires_at datetime NOT NULL,
				consumed_at datetime NULL,
				created_by bigint(20) unsigned DEFAULT 0 NOT NULL,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY request_id (request_id),
				KEY ability_id (ability_id),
				KEY input_hash (input_hash),
				KEY status (status),
				KEY expires_at (expires_at),
				KEY created_at (created_at)
			) {$charset_collate};"
		);
	}

	/**
	 * Creates a read request.
	 *
	 * @param array<string,mixed> $data Request data.
	 * @return array<string,mixed>|WP_Error
	 */
	public function create( array $data ) {
		global $wpdb;

		$request_id = function_exists( 'wp_generate_uuid4' ) ? wp_generate_uuid4() : uniqid( 'read_request_', true );
		$now        = current_time( 'mysql', true );
		$record     = array(
			'request_id'              => $request_id,
			'ability_id'              => sanitize_text_field( (string) ( $data['ability_id'] ?? '' ) ),
			'input_hash'              => sanitize_text_field( (string) ( $data['input_hash'] ?? '' ) ),
			'status'                  => self::STATUS_PENDING,
			'requested_input_summary' => $this->redact_secret_string( sanitize_textarea_field( (string) ( $data['requested_input_summary'] ?? '' ) ) ),
			'sensitivity'             => sanitize_key( (string) ( $data['sensitivity'] ?? 'sensitive' ) ),
			'data_classes_json'       => wp_json_encode( $this->sanitize_string_list( is_array( $data['data_classes'] ?? null ) ? (array) $data['data_classes'] : array() ) ),
			'redaction_level'         => sanitize_key( (string) ( $data['redaction_level'] ?? 'standard' ) ),
			'purpose'                 => $this->redact_secret_string( sanitize_textarea_field( (string) ( $data['purpose'] ?? '' ) ) ),
			'caller_json'             => wp_json_encode( $this->sanitize_payload( $data['caller'] ?? array() ) ),
			'bounds_json'             => wp_json_encode( $this->sanitize_bounds( is_array( $data['bounds'] ?? null ) ? (array) $data['bounds'] : array() ) ),
			'correlation_id'          => sanitize_text_field( (string) ( $data['correlation_id'] ?? '' ) ),
			'expires_at'              => sanitize_text_field( (string) ( $data['expires_at'] ?? '' ) ),
			'consumed_at'             => null,
			'created_by'              => get_current_user_id(),
			'created_at'              => $now,
			'updated_at'              => $now,
		);

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Core owns this custom governance table.
		$inserted = $wpdb->insert(
			$this->table_name(),
			$record,
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s' )
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( false === $inserted ) {
			return new WP_Error(
				'npcink_governance_core_read_request_insert_failed',
				__( 'Sensitive read request could not be stored.', 'npcink-governance-core' ),
				array( 'status' => 500 )
			);
		}

		return $this->normalize_row( $record );
	}

	/**
	 * Deletes a read request.
	 *
	 * @param string $request_id Request id.
	 * @return bool
	 */
	public function delete_by_request_id( string $request_id ): bool {
		global $wpdb;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Core owns this custom governance table.
		$deleted = $wpdb->delete(
			$this->table_name(),
			array( 'request_id' => sanitize_text_field( $request_id ) ),
			array( '%s' )
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		return false !== $deleted && $deleted > 0;
	}

	/**
	 * Finds one request.
	 *
	 * @param string $request_id Request id.
	 * @return array<string,mixed>|null
	 */
	public function find( string $request_id ): ?array {
		global $wpdb;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Core owns this custom governance table.
		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT request_id, ability_id, input_hash, status, requested_input_summary, sensitivity, data_classes_json, redaction_level, purpose, caller_json, bounds_json, correlation_id, expires_at, consumed_at, created_by, created_at, updated_at FROM %i WHERE request_id = %s LIMIT 1',
				$this->table_name(),
				sanitize_text_field( $request_id )
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		return is_array( $row ) ? $this->normalize_row( $row ) : null;
	}

	/**
	 * Lists recent requests.
	 *
	 * @param int    $limit Limit.
	 * @param string $status Optional status.
	 * @return array<int,array<string,mixed>>
	 */
	public function list_recent( int $limit = 50, string $status = '' ): array {
		global $wpdb;

		$limit  = max( 1, min( 200, $limit ) );
		$status = sanitize_key( $status );

		if ( '' !== $status && in_array( $status, $this->allowed_statuses(), true ) ) {
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Core owns this custom governance table.
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT request_id, ability_id, input_hash, status, requested_input_summary, sensitivity, data_classes_json, redaction_level, purpose, caller_json, bounds_json, correlation_id, expires_at, consumed_at, created_by, created_at, updated_at FROM %i WHERE status = %s ORDER BY id DESC LIMIT %d',
					$this->table_name(),
					$status,
					$limit
				),
				ARRAY_A
			);
			// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			return array_map( array( $this, 'normalize_row' ), is_array( $rows ) ? $rows : array() );
		}

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Core owns this custom governance table.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT request_id, ability_id, input_hash, status, requested_input_summary, sensitivity, data_classes_json, redaction_level, purpose, caller_json, bounds_json, correlation_id, expires_at, consumed_at, created_by, created_at, updated_at FROM %i ORDER BY id DESC LIMIT %d',
				$this->table_name(),
				$limit
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		return array_map( array( $this, 'normalize_row' ), is_array( $rows ) ? $rows : array() );
	}

	/**
	 * Updates status.
	 *
	 * @param string $request_id Request id.
	 * @param string $status New status.
	 * @return array<string,mixed>|null
	 */
	private function update_status( string $request_id, string $status ): ?array {
		global $wpdb;

		$request_id = sanitize_text_field( $request_id );
		$status     = sanitize_key( $status );
		if ( ! in_array( $status, $this->allowed_statuses(), true ) ) {
			return null;
		}

		$data = array(
			'status'     => $status,
			'updated_at' => current_time( 'mysql', true ),
		);
		$formats = array( '%s', '%s' );

		if ( self::STATUS_CONSUMED === $status ) {
			$data['consumed_at'] = current_time( 'mysql', true );
			$formats[]          = '%s';
		}

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Core owns this custom governance table.
		$updated = $wpdb->update(
			$this->table_name(),
			$data,
			array( 'request_id' => $request_id ),
			$formats,
			array( '%s' )
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( false === $updated ) {
			return null;
		}

		return $this->find( $request_id );
	}

	/**
	 * Updates request status only when the current status still matches.
	 *
	 * @param string $request_id Request id.
	 * @param string $expected_status Expected current status.
	 * @param string $status New status.
	 * @return array<string,mixed>|null
	 */
	public function update_status_when( string $request_id, string $expected_status, string $status ): ?array {
		global $wpdb;

		$request_id      = sanitize_text_field( $request_id );
		$expected_status = sanitize_key( $expected_status );
		$status          = sanitize_key( $status );
		if ( ! in_array( $expected_status, $this->allowed_statuses(), true ) || ! in_array( $status, $this->allowed_statuses(), true ) ) {
			return null;
		}

		$data = array(
			'status'     => $status,
			'updated_at' => current_time( 'mysql', true ),
		);
		$formats = array( '%s', '%s' );

		if ( self::STATUS_CONSUMED === $status ) {
			$data['consumed_at'] = current_time( 'mysql', true );
			$formats[]          = '%s';
		}

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Core owns this custom governance table.
		$updated = $wpdb->update(
			$this->table_name(),
			$data,
			array(
				'request_id' => $request_id,
				'status'     => $expected_status,
			),
			$formats,
			array( '%s', '%s' )
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		return 1 === $updated ? $this->find( $request_id ) : null;
	}

	/**
	 * Atomically consumes an approved one-time read request.
	 *
	 * @param string $request_id Request id.
	 * @return array<string,mixed>|null
	 */
	public function consume_approved_once( string $request_id ): ?array {
		global $wpdb;

		$request_id = sanitize_text_field( $request_id );
		$now        = current_time( 'mysql', true );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Core owns this custom governance table.
		$updated = $wpdb->update(
			$this->table_name(),
			array(
				'status'      => self::STATUS_CONSUMED,
				'consumed_at' => $now,
				'updated_at'  => $now,
			),
			array(
				'request_id' => $request_id,
				'status'     => self::STATUS_APPROVED,
			),
			array( '%s', '%s', '%s' ),
			array( '%s', '%s' )
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( 1 !== $updated ) {
			return null;
		}

		return $this->find( $request_id );
	}

	/**
	 * Updates approved bounds and expiry.
	 *
	 * @param string              $request_id Request id.
	 * @param array<string,mixed> $data Approval data.
	 * @return array<string,mixed>|null
	 */
	public function update_approval_fields( string $request_id, array $data ): ?array {
		global $wpdb;

		$record = array(
			'bounds_json'     => wp_json_encode( $this->sanitize_bounds( is_array( $data['bounds'] ?? null ) ? (array) $data['bounds'] : array() ) ),
			'redaction_level' => sanitize_key( (string) ( $data['redaction_level'] ?? 'standard' ) ),
			'expires_at'      => sanitize_text_field( (string) ( $data['expires_at'] ?? '' ) ),
			'updated_at'      => current_time( 'mysql', true ),
		);

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Core owns this custom governance table.
		$wpdb->update(
			$this->table_name(),
			$record,
			array( 'request_id' => sanitize_text_field( $request_id ) ),
			array( '%s', '%s', '%s', '%s' ),
			array( '%s' )
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		return $this->find( $request_id );
	}

	/**
	 * Returns allowed statuses.
	 *
	 * @return array<int,string>
	 */
	public function allowed_statuses(): array {
		return array(
			self::STATUS_PENDING,
			self::STATUS_APPROVED,
			self::STATUS_REJECTED,
			self::STATUS_EXPIRED,
			self::STATUS_CONSUMED,
		);
	}

	/**
	 * Sanitizes bounds.
	 *
	 * @param array<string,mixed> $bounds Raw bounds.
	 * @return array<string,mixed>
	 */
	public function sanitize_bounds( array $bounds ): array {
		return array(
			'max_rows'       => max( 0, absint( $bounds['max_rows'] ?? 0 ) ),
			'tail_lines'     => max( 0, absint( $bounds['tail_lines'] ?? 0 ) ),
			'allowed_fields' => $this->sanitize_string_list( is_array( $bounds['allowed_fields'] ?? null ) ? (array) $bounds['allowed_fields'] : array() ),
			'denied_fields'  => $this->sanitize_string_list( is_array( $bounds['denied_fields'] ?? null ) ? (array) $bounds['denied_fields'] : array() ),
			'one_time'       => ! empty( $bounds['one_time'] ),
		);
	}

	/**
	 * Normalizes DB row.
	 *
	 * @param array<string,mixed> $row DB row.
	 * @return array<string,mixed>
	 */
	private function normalize_row( array $row ): array {
		$data_classes = json_decode( (string) ( $row['data_classes_json'] ?? '[]' ), true );
		$caller       = json_decode( (string) ( $row['caller_json'] ?? '{}' ), true );
		$bounds       = json_decode( (string) ( $row['bounds_json'] ?? '{}' ), true );

		return array(
			'request_id'              => sanitize_text_field( (string) ( $row['request_id'] ?? '' ) ),
			'ability_id'              => sanitize_text_field( (string) ( $row['ability_id'] ?? '' ) ),
			'input_hash'              => sanitize_text_field( (string) ( $row['input_hash'] ?? '' ) ),
			'status'                  => sanitize_key( (string) ( $row['status'] ?? '' ) ),
			'requested_input_summary' => $this->redact_secret_string( sanitize_textarea_field( (string) ( $row['requested_input_summary'] ?? '' ) ) ),
			'sensitivity'             => sanitize_key( (string) ( $row['sensitivity'] ?? '' ) ),
			'data_classes'            => is_array( $data_classes ) ? $this->sanitize_string_list( $data_classes ) : array(),
			'redaction_level'         => sanitize_key( (string) ( $row['redaction_level'] ?? '' ) ),
			'purpose'                 => $this->redact_secret_string( sanitize_textarea_field( (string) ( $row['purpose'] ?? '' ) ) ),
			'caller'                  => is_array( $caller ) ? $this->sanitize_payload( $caller ) : array(),
			'bounds'                  => is_array( $bounds ) ? $this->sanitize_bounds( $bounds ) : $this->sanitize_bounds( array() ),
			'correlation_id'          => sanitize_text_field( (string) ( $row['correlation_id'] ?? '' ) ),
			'expires_at'              => sanitize_text_field( (string) ( $row['expires_at'] ?? '' ) ),
			'consumed_at'             => sanitize_text_field( (string) ( $row['consumed_at'] ?? '' ) ),
			'created_by'              => (int) ( $row['created_by'] ?? 0 ),
			'created_at'              => sanitize_text_field( (string) ( $row['created_at'] ?? '' ) ),
			'updated_at'              => sanitize_text_field( (string) ( $row['updated_at'] ?? '' ) ),
		);
	}

	/**
	 * Sanitizes string list.
	 *
	 * @param array<mixed> $values Values.
	 * @return array<int,string>
	 */
	private function sanitize_string_list( array $values ): array {
		$clean = array();
		foreach ( $values as $value ) {
			$value = sanitize_text_field( (string) $value );
			if ( '' !== $value ) {
				$clean[] = $value;
			}
		}

		return array_values( array_unique( $clean ) );
	}

	/**
	 * Sanitizes payload and redacts secret-shaped keys.
	 *
	 * @param mixed $value Value.
	 * @return mixed
	 */
	private function sanitize_payload( $value ) {
		return Sensitive_Data_Redactor::sanitize_payload( $value );
	}

	/**
	 * Returns whether a key is secret-shaped.
	 *
	 * @param string $key Key.
	 * @return bool
	 */
	private function is_secret_key( string $key ): bool {
		return Sensitive_Data_Redactor::is_secret_key( $key );
	}

	/**
	 * Redacts obvious secret strings.
	 *
	 * @param string $value Value.
	 * @return string
	 */
	private function redact_secret_string( string $value ): string {
		return Sensitive_Data_Redactor::redact_secret_string( $value );
	}
}
