<?php
/**
 * App key repository.
 *
 * @package MagickAICore
 */

namespace MagickAI\Core\Security;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stores app identities and hashed app secrets.
 */
final class App_Key_Repository {
	const DEFAULT_RATE_LIMIT = 60;
	const DEFAULT_RATE_WINDOW = 3600;

	/**
	 * Returns table name.
	 *
	 * @return string
	 */
	public function table_name(): string {
		global $wpdb;

		return $wpdb->prefix . 'magick_ai_core_app_keys';
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
		);
	}

	/**
	 * Creates one app key and returns the raw secret once.
	 *
	 * @param array<string,mixed> $data Data.
	 * @return array<string,mixed>
	 */
	public function create( array $data ): array {
		global $wpdb;

		$app_id              = $this->generate_public_id( 'app' );
		$key_id              = $this->generate_public_id( 'key' );
		$secret              = $this->generate_secret();
		$scopes              = $this->sanitize_scopes( is_array( $data['scopes'] ?? null ) ? $data['scopes'] : $this->default_scopes() );
		$rate_limit          = max( 1, min( 10000, absint( $data['rate_limit'] ?? self::DEFAULT_RATE_LIMIT ) ) );
		$rate_window_seconds = max( 60, min( 86400, absint( $data['rate_window_seconds'] ?? self::DEFAULT_RATE_WINDOW ) ) );
		$now                 = current_time( 'mysql', true );

		$secret_hash = password_hash( $secret, PASSWORD_DEFAULT );
		if ( ! is_string( $secret_hash ) ) {
			$secret_hash = '';
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
			'created_by'          => get_current_user_id(),
			'created_at'          => $now,
			'updated_at'          => $now,
			'last_used_at'        => null,
		);

		$wpdb->insert(
			$this->table_name(),
			$record,
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%d', '%s', '%s', '%s' )
		);

		$row             = $this->normalize_row( $record );
		$row['secret']   = $secret;
		$row['token']    = 'mai_core.' . $key_id . '.' . $secret;
		$row['shown_once'] = true;

		return $row;
	}

	/**
	 * Lists app keys without secrets.
	 *
	 * @param int $limit Max rows.
	 * @param int $offset Rows to skip.
	 * @return array<int,array<string,mixed>>
	 */
	public function list_recent( int $limit = 50, int $offset = 0 ): array {
		global $wpdb;

		$limit  = max( 1, min( 200, $limit ) );
		$offset = max( 0, $offset );
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Custom table name is generated from the WordPress table prefix; query values use placeholders.
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT app_id, app_label, key_id, secret_hash, status, scopes_json, rate_limit, rate_window_seconds, caller_type, created_by, created_at, updated_at, last_used_at FROM ' . $this->table_name() . ' ORDER BY id DESC LIMIT %d OFFSET %d',
				$limit,
				$offset
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter

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
		$sql    = 'SELECT COUNT(*) FROM ' . $this->table_name();
		$args   = array();

		if ( '' !== $status ) {
			$sql   .= ' WHERE status = %s';
			$args[] = $status;
		}

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Custom table name is generated from the WordPress table prefix; query values use placeholders.
		$count = empty( $args ) ? (int) $wpdb->get_var( $sql ) : (int) $wpdb->get_var( $wpdb->prepare( $sql, $args ) );
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter

		return $count;
	}

	/**
	 * Returns the latest app-key use timestamp.
	 *
	 * @return string
	 */
	public function latest_last_used_at(): string {
		global $wpdb;

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Custom table name is generated from the WordPress table prefix and no user values are interpolated.
		$value = $wpdb->get_var( 'SELECT MAX(last_used_at) FROM ' . $this->table_name() . ' WHERE last_used_at IS NOT NULL' );
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter

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

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Custom table name is generated from the WordPress table prefix; query values use placeholders.
		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT app_id, app_label, key_id, secret_hash, status, scopes_json, rate_limit, rate_window_seconds, caller_type, created_by, created_at, updated_at, last_used_at FROM ' . $this->table_name() . ' WHERE key_id = %s LIMIT 1',
				sanitize_text_field( $key_id )
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter

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
	public function touch_last_used( string $key_id ): void {
		global $wpdb;

		$wpdb->update(
			$this->table_name(),
			array(
				'last_used_at' => current_time( 'mysql', true ),
				'updated_at'   => current_time( 'mysql', true ),
			),
			array( 'key_id' => sanitize_text_field( $key_id ) ),
			array( '%s', '%s' ),
			array( '%s' )
		);
	}

	/**
	 * Revokes one app key.
	 *
	 * @param string $key_id Key id.
	 * @return bool Whether a row was updated.
	 */
	public function revoke_by_key_id( string $key_id ): bool {
		global $wpdb;

		$updated = $wpdb->update(
			$this->table_name(),
			array(
				'status'     => 'revoked',
				'updated_at' => current_time( 'mysql', true ),
			),
			array(
				'key_id' => sanitize_text_field( $key_id ),
				'status' => 'active',
			),
			array( '%s', '%s' ),
			array( '%s', '%s' )
		);

		return false !== $updated && $updated > 0;
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
		return empty( $clean ) ? $this->default_scopes() : $clean;
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
}
