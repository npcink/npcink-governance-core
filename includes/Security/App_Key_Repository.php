<?php
/**
 * App key repository.
 *
 * @package NpcinkGovernanceCore
 */

namespace Npcink\GovernanceCore\Security;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stores app identities and hashed app secrets.
 */
final class App_Key_Repository {
	const DEFAULT_RATE_LIMIT = 60;
	const DEFAULT_RATE_WINDOW = 3600;
	const TOKEN_PREFIX = 'npcink_governance_core';

	/**
	 * Returns table name.
	 *
	 * @return string
	 */
	public function table_name(): string {
		global $wpdb;

		return $wpdb->prefix . 'npcink_governance_core_app_keys';
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
				app_label varchar(190) NOT NULL,
				key_id varchar(64) NOT NULL,
				secret_hash varchar(255) NOT NULL,
				status varchar(40) DEFAULT 'active' NOT NULL,
				scopes_json longtext NULL,
				rate_limit int unsigned DEFAULT 60 NOT NULL,
				rate_window_seconds int unsigned DEFAULT 3600 NOT NULL,
				caller_type varchar(80) DEFAULT 'external_app' NOT NULL,
				expires_at datetime NULL,
				last_used_ip_hash varchar(64) DEFAULT '' NOT NULL,
				revoked_at datetime NULL,
				revoked_reason text NULL,
				hash_algorithm_version varchar(80) DEFAULT 'password_hash_default' NOT NULL,
				created_by bigint(20) unsigned DEFAULT 0 NOT NULL,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				last_used_at datetime NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY app_id (app_id),
				UNIQUE KEY key_id (key_id),
				KEY status (status),
				KEY created_at (created_at)
			) {$charset_collate};"
		);
	}

	/**
	 * Returns allowed scopes.
	 *
	 * @return array<int,string>
	 */
	public function allowed_scopes(): array {
		return array(
			'capabilities:read',
			'proposals:create',
			'proposals:read',
			'proposals:approve',
			'proposals:reject',
			'commit:preflight',
			'commit:record_execution',
			'read_requests:create',
			'read_requests:read',
			'read_requests:approve',
			'read_requests:reject',
			'read_requests:preflight',
			'audit:read',
		);
	}

	/**
	 * Returns default adapter scopes.
	 *
	 * @return array<int,string>
	 */
	public function default_scopes(): array {
		return array(
			'capabilities:read',
			'proposals:create',
			'proposals:read',
			'commit:preflight',
			'read_requests:create',
			'read_requests:read',
			'read_requests:preflight',
		);
	}

	/**
	 * Creates one app key and returns the raw secret once.
	 *
	 * @param array<string,mixed> $data Data.
	 * @return array<string,mixed>|WP_Error
	 */
	public function create( array $data ) {
		global $wpdb;

		$app_id              = $this->generate_public_id( 'app' );
		$key_id              = $this->generate_public_id( 'key' );
		$secret              = $this->generate_secret();
		$scopes              = $this->default_scopes();
		if ( array_key_exists( 'scopes', $data ) ) {
			$scopes = $this->sanitize_scopes( is_array( $data['scopes'] ) ? $data['scopes'] : array() );
		}
		$rate_limit          = max( 1, min( 10000, absint( $data['rate_limit'] ?? self::DEFAULT_RATE_LIMIT ) ) );
		$rate_window_seconds = max( 60, min( 86400, absint( $data['rate_window_seconds'] ?? self::DEFAULT_RATE_WINDOW ) ) );
		$expires_at          = $this->sanitize_future_datetime( (string) ( $data['expires_at'] ?? '' ) );
		$now                 = current_time( 'mysql', true );

		if ( empty( $scopes ) ) {
			return new WP_Error(
				'npcink_governance_core_app_scopes_empty',
				__( 'App keys must include at least one valid scope.', 'npcink-governance-core' ),
				array( 'status' => 400 )
			);
		}

		$secret_hash = password_hash( $secret, PASSWORD_DEFAULT );
		if ( ! is_string( $secret_hash ) ) {
			return new WP_Error(
				'npcink_governance_core_app_secret_hash_failed',
				__( 'App key secret could not be protected.', 'npcink-governance-core' ),
				array( 'status' => 500 )
			);
		}

		$record = array(
			'app_id'              => $app_id,
			'app_label'           => sanitize_text_field( (string) ( $data['app_label'] ?? 'External app' ) ),
			'key_id'              => $key_id,
			'secret_hash'         => $secret_hash,
			'status'              => 'active',
			'scopes_json'         => wp_json_encode( $scopes ),
			'rate_limit'          => $rate_limit,
			'rate_window_seconds' => $rate_window_seconds,
			'caller_type'         => sanitize_key( (string) ( $data['caller_type'] ?? 'external_app' ) ),
			'expires_at'          => $expires_at,
			'last_used_ip_hash'   => '',
			'revoked_at'          => null,
			'revoked_reason'      => null,
			'hash_algorithm_version' => 'password_hash_default',
			'created_by'          => get_current_user_id(),
			'created_at'          => $now,
			'updated_at'          => $now,
			'last_used_at'        => null,
		);

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Core owns this custom governance table.
		$inserted = $wpdb->insert(
			$this->table_name(),
			$record,
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s' )
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( false === $inserted ) {
			return new WP_Error(
				'npcink_governance_core_app_insert_failed',
				__( 'App key could not be stored.', 'npcink-governance-core' ),
				array( 'status' => 500 )
			);
		}

		$row             = $this->normalize_row( $record );
		$row['secret']   = $secret;
			$row['token']    = self::TOKEN_PREFIX . '.' . $key_id . '.' . $secret;
		$row['shown_once'] = true;

		return $row;
	}

	/**
	 * Lists app keys without secrets.
	 *
	 * @param int    $limit Max rows.
	 * @param int    $offset Rows to skip.
	 * @param string $status Optional status filter.
	 * @return array<int,array<string,mixed>>
	 */
	public function list_recent( int $limit = 50, int $offset = 0, string $status = '' ): array {
		global $wpdb;

		$limit  = max( 1, min( 200, $limit ) );
		$offset = max( 0, $offset );
		$status = sanitize_key( $status );
		if ( '' !== $status ) {
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Core owns this custom governance table.
			$rows  = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT app_id, app_label, key_id, status, scopes_json, rate_limit, rate_window_seconds, caller_type, expires_at, last_used_ip_hash, revoked_at, revoked_reason, hash_algorithm_version, created_by, created_at, updated_at, last_used_at FROM %i WHERE status = %s ORDER BY id DESC LIMIT %d OFFSET %d',
					$this->table_name(),
					$status,
					$limit,
					$offset
				),
				ARRAY_A
			);
			// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			return array_map( array( $this, 'normalize_row' ), is_array( $rows ) ? $rows : array() );
		}

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Core owns this custom governance table.
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT app_id, app_label, key_id, status, scopes_json, rate_limit, rate_window_seconds, caller_type, expires_at, last_used_ip_hash, revoked_at, revoked_reason, hash_algorithm_version, created_by, created_at, updated_at, last_used_at FROM %i ORDER BY id DESC LIMIT %d OFFSET %d',
				$this->table_name(),
				$limit,
				$offset
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		return array_map( array( $this, 'normalize_row' ), is_array( $rows ) ? $rows : array() );
	}

	/**
	 * Counts app keys.
	 *
	 * @param string $status Optional status filter.
	 * @return int
	 */
	public function count( string $status = '' ): int {
		global $wpdb;

		$status = sanitize_key( $status );
			if ( '' !== $status ) {
				// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Core owns this custom governance table.
				$count = (int) $wpdb->get_var(
					$wpdb->prepare(
						'SELECT COUNT(*) FROM %i WHERE status = %s',
						$this->table_name(),
						$status
					)
				);
				// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				return $count;
			}

			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Core owns this custom governance table.
			$count = (int) $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COUNT(*) FROM %i',
					$this->table_name()
				)
			);
			// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		return $count;
	}

	/**
	 * Counts revoked app keys older than a cutoff.
	 *
	 * @param string $cutoff_utc UTC cutoff datetime.
	 * @return int
	 */
	public function count_revoked_before( string $cutoff_utc ): int {
		global $wpdb;

		$cutoff_utc = sanitize_text_field( $cutoff_utc );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Core owns this custom governance table.
		$count = (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM %i WHERE status = %s AND revoked_at IS NOT NULL AND revoked_at < %s',
				$this->table_name(),
				'revoked',
				$cutoff_utc
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		return $count;
	}

	/**
	 * Deletes revoked app keys older than a cutoff.
	 *
	 * @param string $cutoff_utc UTC cutoff datetime.
	 * @param int    $limit Maximum records to delete in one pass.
	 * @return int|null Deleted row count, or null on database failure.
	 */
	public function delete_revoked_before( string $cutoff_utc, int $limit = 200 ): ?int {
		global $wpdb;

		$cutoff_utc = sanitize_text_field( $cutoff_utc );
		$limit      = max( 1, min( 1000, $limit ) );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Core owns this custom governance table and deletes only bounded revoked token rows.
		$deleted = $wpdb->query(
			$wpdb->prepare(
				'DELETE FROM %i WHERE status = %s AND revoked_at IS NOT NULL AND revoked_at < %s ORDER BY id ASC LIMIT %d',
				$this->table_name(),
				'revoked',
				$cutoff_utc,
				$limit
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		return false === $deleted ? null : (int) $deleted;
	}

	/**
	 * Returns the latest app-key use timestamp.
	 *
	 * @return string
	 */
	public function latest_last_used_at(): string {
		global $wpdb;

			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Core owns this custom governance table.
			$value = $wpdb->get_var(
				$wpdb->prepare(
					'SELECT MAX(last_used_at) FROM %i WHERE last_used_at IS NOT NULL',
					$this->table_name()
				)
			);
			// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		return sanitize_text_field( (string) $value );
	}

	/**
	 * Finds one app key by key id.
	 *
	 * @param string $key_id Key id.
	 * @return array<string,mixed>|null
	 */
	public function find_by_key_id( string $key_id ): ?array {
		global $wpdb;

			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Core owns this custom governance table.
			$row = $wpdb->get_row(
				$wpdb->prepare(
					'SELECT app_id, app_label, key_id, secret_hash, status, scopes_json, rate_limit, rate_window_seconds, caller_type, expires_at, last_used_ip_hash, revoked_at, revoked_reason, hash_algorithm_version, created_by, created_at, updated_at, last_used_at FROM %i WHERE key_id = %s LIMIT 1',
					$this->table_name(),
					sanitize_text_field( $key_id )
				),
				ARRAY_A
			);
			// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		return is_array( $row ) ? $this->normalize_row( $row, true ) : null;
	}

	/**
	 * Verifies a raw secret.
	 *
	 * @param array<string,mixed> $app App row.
	 * @param string              $secret Raw secret.
	 * @return bool
	 */
	public function verify_secret( array $app, string $secret ): bool {
		$hash = (string) ( $app['secret_hash'] ?? '' );
		return '' !== $hash && password_verify( $secret, $hash );
	}

	/**
	 * Updates last-used timestamp.
	 *
	 * @param string $key_id Key id.
	 * @return void
	 */
	public function touch_last_used( string $key_id, string $ip_hash = '' ): void {
		global $wpdb;

		$updates = array(
			'last_used_at' => current_time( 'mysql', true ),
			'updated_at'   => current_time( 'mysql', true ),
		);
		$formats = array( '%s', '%s' );
		$ip_hash = sanitize_text_field( $ip_hash );
		if ( '' !== $ip_hash ) {
			$updates['last_used_ip_hash'] = $ip_hash;
			$formats[] = '%s';
		}

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Core owns this custom governance table.
		$wpdb->update(
			$this->table_name(),
			$updates,
			array( 'key_id' => sanitize_text_field( $key_id ) ),
			$formats,
			array( '%s' )
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	/**
	 * Revokes one app key.
	 *
	 * @param string $key_id Key id.
	 * @return bool Whether a row was updated.
	 */
	public function revoke_by_key_id( string $key_id, string $reason = '' ): bool {
		global $wpdb;
		$now = current_time( 'mysql', true );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Core owns this custom governance table.
		$updated = $wpdb->update(
			$this->table_name(),
			array(
				'status'         => 'revoked',
				'revoked_at'     => $now,
				'revoked_reason' => sanitize_textarea_field( $reason ),
				'updated_at'     => $now,
			),
			array(
				'key_id' => sanitize_text_field( $key_id ),
				'status' => 'active',
			),
			array( '%s', '%s', '%s', '%s' ),
			array( '%s', '%s' )
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		return false !== $updated && $updated > 0;
	}

	/**
	 * Checks whether an app key row is expired.
	 *
	 * @param array<string,mixed> $app App row.
	 * @return bool
	 */
	public function is_expired( array $app ): bool {
		$expires_at = strtotime( (string) ( $app['expires_at'] ?? '' ) );
		return false !== $expires_at && $expires_at <= time();
	}

	/**
	 * Sanitizes scopes.
	 *
	 * @param array<mixed> $scopes Scopes.
	 * @return array<int,string>
	 */
	public function sanitize_scopes( array $scopes ): array {
		$allowed = $this->allowed_scopes();
		$clean   = array();

		foreach ( $scopes as $scope ) {
			$scope = sanitize_text_field( (string) $scope );
			if ( in_array( $scope, $allowed, true ) ) {
				$clean[] = $scope;
			}
		}

		$clean = array_values( array_unique( $clean ) );
		return $clean;
	}

	/**
	 * Generates public id.
	 *
	 * @param string $prefix Prefix.
	 * @return string
	 */
	private function generate_public_id( string $prefix ): string {
		$uuid = function_exists( 'wp_generate_uuid4' ) ? wp_generate_uuid4() : uniqid( '', true );
		return sanitize_key( $prefix ) . '_' . str_replace( '.', '_', sanitize_text_field( $uuid ) );
	}

	/**
	 * Generates app secret.
	 *
	 * @return string
	 */
	private function generate_secret(): string {
		if ( function_exists( 'wp_generate_password' ) ) {
			return wp_generate_password( 40, false, false );
		}

		return bin2hex( random_bytes( 20 ) );
	}

	/**
	 * Normalizes row.
	 *
	 * @param array<string,mixed> $row DB row.
	 * @param bool                $include_hash Whether to include secret hash.
	 * @return array<string,mixed>
	 */
	private function normalize_row( array $row, bool $include_hash = false ): array {
		$scopes = json_decode( (string) ( $row['scopes_json'] ?? '[]' ), true );
		$data   = array(
			'app_id'              => sanitize_text_field( (string) ( $row['app_id'] ?? '' ) ),
			'app_label'           => sanitize_text_field( (string) ( $row['app_label'] ?? '' ) ),
			'key_id'              => sanitize_text_field( (string) ( $row['key_id'] ?? '' ) ),
			'status'              => sanitize_key( (string) ( $row['status'] ?? '' ) ),
			'scopes'              => is_array( $scopes ) ? $this->sanitize_scopes( $scopes ) : array(),
			'rate_limit'          => (int) ( $row['rate_limit'] ?? self::DEFAULT_RATE_LIMIT ),
			'rate_window_seconds' => (int) ( $row['rate_window_seconds'] ?? self::DEFAULT_RATE_WINDOW ),
			'caller_type'         => sanitize_key( (string) ( $row['caller_type'] ?? 'external_app' ) ),
			'token_prefix'        => self::TOKEN_PREFIX,
			'expires_at'          => sanitize_text_field( (string) ( $row['expires_at'] ?? '' ) ),
			'expires_soon'        => $this->expires_soon( (string) ( $row['expires_at'] ?? '' ) ),
			'rotation_recommended' => $this->is_expired( $row ) || $this->expires_soon( (string) ( $row['expires_at'] ?? '' ) ),
			'last_used_ip_hash'   => sanitize_text_field( (string) ( $row['last_used_ip_hash'] ?? '' ) ),
			'revoked_at'          => sanitize_text_field( (string) ( $row['revoked_at'] ?? '' ) ),
			'revoked_reason'      => sanitize_textarea_field( (string) ( $row['revoked_reason'] ?? '' ) ),
			'hash_algorithm_version' => sanitize_key( (string) ( $row['hash_algorithm_version'] ?? 'password_hash_default' ) ),
			'created_by'          => (int) ( $row['created_by'] ?? 0 ),
			'created_at'          => sanitize_text_field( (string) ( $row['created_at'] ?? '' ) ),
			'updated_at'          => sanitize_text_field( (string) ( $row['updated_at'] ?? '' ) ),
			'last_used_at'        => sanitize_text_field( (string) ( $row['last_used_at'] ?? '' ) ),
		);

		if ( $include_hash ) {
			$data['secret_hash'] = (string) ( $row['secret_hash'] ?? '' );
		}

		return $data;
	}

	/**
	 * Sanitizes an optional future UTC datetime.
	 *
	 * @param string $value Raw value.
	 * @return string|null
	 */
	private function sanitize_future_datetime( string $value ) {
		$value = trim( $value );
		if ( '' === $value ) {
			return null;
		}

		$timestamp = strtotime( $value );
		if ( false === $timestamp || $timestamp <= time() ) {
			return null;
		}

		return gmdate( 'Y-m-d H:i:s', $timestamp );
	}

	/**
	 * Returns whether a key expires within the rotation warning window.
	 *
	 * @param string $expires_at Expiry timestamp.
	 * @return bool
	 */
	private function expires_soon( string $expires_at ): bool {
		$timestamp = strtotime( $expires_at );
		if ( false === $timestamp ) {
			return false;
		}

		return $timestamp > time() && $timestamp <= time() + ( 14 * 86400 );
	}
}
