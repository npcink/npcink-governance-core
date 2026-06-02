<?php
/**
 * Proposal repository.
 *
 * @package MagickAICore
 */

namespace MagickAI\Core\Governance;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Persists AI operation proposals.
 */
final class Proposal_Repository {
	const STATUS_PENDING  = 'pending';
	const STATUS_APPROVED = 'approved';
	const STATUS_REJECTED = 'rejected';
	const STATUS_EXPIRED  = 'expired';
	const STATUS_ARCHIVED = 'archived';

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
	 * @return array<string,mixed>|WP_Error
	 */
	public function create( array $data ) {
		global $wpdb;

		$proposal_id = function_exists( 'wp_generate_uuid4' ) ? wp_generate_uuid4() : uniqid( 'proposal_', true );
		$now         = current_time( 'mysql', true );
		$record      = array(
			'proposal_id'  => $proposal_id,
			'ability_id'   => sanitize_text_field( (string) ( $data['ability_id'] ?? '' ) ),
			'status'       => self::STATUS_PENDING,
			'title'        => sanitize_text_field( (string) ( $data['title'] ?? '' ) ),
			'summary'      => sanitize_textarea_field( (string) ( $data['summary'] ?? '' ) ),
			'input_json'   => wp_json_encode( $this->sanitize_payload( $data['input'] ?? array() ) ),
			'preview_json' => wp_json_encode( $this->sanitize_payload( $data['preview'] ?? array() ) ),
			'caller_json'  => wp_json_encode( $this->sanitize_payload( $data['caller'] ?? array() ) ),
			'created_by'   => get_current_user_id(),
			'created_at'   => $now,
			'updated_at'   => $now,
		);

		$inserted = $wpdb->insert(
			$this->table_name(),
			$record,
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s' )
		);

		if ( false === $inserted ) {
			return new WP_Error(
				'magick_ai_core_proposal_insert_failed',
				__( 'Proposal could not be stored.', 'magick-ai-core' ),
				array( 'status' => 500 )
			);
		}

		return $this->normalize_row( $record );
	}

	/**
	 * Deletes a proposal by public id.
	 *
	 * @param string $proposal_id Proposal id.
	 * @return bool Whether a row was deleted.
	 */
	public function delete_by_proposal_id( string $proposal_id ): bool {
		global $wpdb;

		$deleted = $wpdb->delete(
			$this->table_name(),
			array( 'proposal_id' => sanitize_text_field( $proposal_id ) ),
			array( '%s' )
		);

		return false !== $deleted && $deleted > 0;
	}

	/**
	 * Lists recent proposals.
	 *
	 * @param int    $limit Maximum rows.
	 * @param string $status Optional status filter.
	 * @param int    $offset Rows to skip.
	 * @return array<int,array<string,mixed>>
	 */
	public function list_recent( int $limit = 50, string $status = '', int $offset = 0 ): array {
		global $wpdb;

		$limit  = max( 1, min( 200, $limit ) );
		$offset = max( 0, $offset );
		$status = sanitize_key( $status );
		$sql    = 'SELECT proposal_id, ability_id, status, title, summary, input_json, preview_json, caller_json, created_by, created_at, updated_at FROM ' . $this->table_name();
		$args   = array();

		if ( '' !== $status ) {
			$sql   .= ' WHERE status = %s';
			$args[] = $status;
		}

		$sql   .= ' ORDER BY id DESC LIMIT %d OFFSET %d';
		$args[] = $limit;
		$args[] = $offset;

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- SQL is assembled from fixed clauses and placeholder values; table name is generated from the WordPress table prefix.
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $args ), ARRAY_A );
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter

		return array_map( array( $this, 'normalize_row' ), is_array( $rows ) ? $rows : array() );
	}

	/**
	 * Lists recent proposals by status set.
	 *
	 * @param array<int,string> $statuses Status filters.
	 * @param int               $limit Maximum rows.
	 * @param int               $offset Rows to skip.
	 * @return array<int,array<string,mixed>>
	 */
	public function list_by_statuses( array $statuses, int $limit = 50, int $offset = 0 ): array {
		global $wpdb;

		$statuses = $this->sanitize_statuses( $statuses );
		if ( empty( $statuses ) ) {
			return array();
		}

		$limit        = max( 1, min( 200, $limit ) );
		$offset       = max( 0, $offset );
		$placeholders = implode( ', ', array_fill( 0, count( $statuses ), '%s' ) );
		$args         = $statuses;
		$args[]       = $limit;
		$args[]       = $offset;

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- SQL uses fixed clauses, generated placeholders, and a table name from the WordPress prefix.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT proposal_id, ability_id, status, title, summary, input_json, preview_json, caller_json, created_by, created_at, updated_at FROM ' . $this->table_name() . ' WHERE status IN (' . $placeholders . ') ORDER BY id DESC LIMIT %d OFFSET %d',
				$args
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter

		return array_map( array( $this, 'normalize_row' ), is_array( $rows ) ? $rows : array() );
	}

	/**
	 * Lists stale pending proposals older than a TTL.
	 *
	 * @param int $ttl_seconds Pending TTL in seconds.
	 * @param int $limit Maximum rows.
	 * @return array<int,array<string,mixed>>
	 */
	public function list_stale_pending( int $ttl_seconds, int $limit = 100 ): array {
		global $wpdb;

		$ttl_seconds = max( 60, $ttl_seconds );
		$limit       = max( 1, min( 200, $limit ) );
		$cutoff      = gmdate( 'Y-m-d H:i:s', time() - $ttl_seconds );

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table name is generated from the WordPress table prefix; query values use placeholders.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT proposal_id, ability_id, status, title, summary, input_json, preview_json, caller_json, created_by, created_at, updated_at FROM ' . $this->table_name() . ' WHERE status = %s AND created_at < %s ORDER BY id ASC LIMIT %d',
				self::STATUS_PENDING,
				$cutoff,
				$limit
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter

		return array_map( array( $this, 'normalize_row' ), is_array( $rows ) ? $rows : array() );
	}

	/**
	 * Lists pending proposals that may belong to one create guardrail bucket.
	 *
	 * @param string $quota_key Guardrail quota key.
	 * @param string $ability_id Optional ability filter.
	 * @param int    $limit Maximum rows.
	 * @return array<int,array<string,mixed>>
	 */
	public function list_pending_for_guardrail( string $quota_key, string $ability_id = '', int $limit = 500 ): array {
		global $wpdb;

		$quota_key = sanitize_text_field( $quota_key );
		if ( '' === $quota_key ) {
			return array();
		}

		$ability_id = sanitize_text_field( $ability_id );
		$limit      = max( 1, min( 1000, $limit ) );
		$where      = array( 'status = %s' );
		$args       = array( self::STATUS_PENDING );

		if ( '' !== $ability_id ) {
			$where[] = 'ability_id = %s';
			$args[]  = $ability_id;
		}

		$identity_where = array();
		if ( 0 === strpos( $quota_key, 'user:' ) ) {
			$user_id = absint( substr( $quota_key, 5 ) );
			$identity_where[] = 'created_by = %d';
			$args[]           = $user_id;
		}

		foreach ( $this->guardrail_like_terms( $quota_key ) as $term ) {
			$identity_where[] = 'caller_json LIKE %s';
			$args[]           = '%' . $wpdb->esc_like( $term ) . '%';
		}

		if ( empty( $identity_where ) ) {
			return array();
		}

		$where[] = '(' . implode( ' OR ', $identity_where ) . ')';
		$args[]  = $limit;

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- SQL uses fixed clauses, generated placeholders, and a table name from the WordPress prefix.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT proposal_id, ability_id, status, title, summary, input_json, preview_json, caller_json, created_by, created_at, updated_at FROM ' . $this->table_name() . ' WHERE ' . implode( ' AND ', $where ) . ' ORDER BY id DESC LIMIT %d',
				$args
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter

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

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Custom table name is generated from the WordPress table prefix; query values use placeholders.
		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT proposal_id, ability_id, status, title, summary, input_json, preview_json, caller_json, created_by, created_at, updated_at FROM ' . $this->table_name() . ' WHERE proposal_id = %s LIMIT 1',
				sanitize_text_field( $proposal_id )
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter

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
		$allowed     = $this->allowed_statuses();

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
	 * Reopens a proposal and resets the review clock.
	 *
	 * @param string $proposal_id Proposal id.
	 * @return array<string,mixed>|null
	 */
	public function reopen( string $proposal_id ): ?array {
		global $wpdb;

		$proposal_id = sanitize_text_field( $proposal_id );
		$now         = current_time( 'mysql', true );

		$wpdb->update(
			$this->table_name(),
			array(
				'status'     => self::STATUS_PENDING,
				'created_at' => $now,
				'updated_at' => $now,
			),
			array( 'proposal_id' => $proposal_id ),
			array( '%s', '%s', '%s' ),
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

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Custom table name is generated from the WordPress table prefix and no user values are interpolated.
		$count = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . $this->table_name() );
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter

		return $count;
	}

	/**
	 * Counts proposals by status.
	 *
	 * @param string $status Status filter.
	 * @return int
	 */
	public function count_by_status( string $status ): int {
		return $this->count_by_statuses( array( $status ) );
	}

	/**
	 * Counts proposals by status set.
	 *
	 * @param array<int,string> $statuses Status filters.
	 * @return int
	 */
	public function count_by_statuses( array $statuses ): int {
		global $wpdb;

		$statuses = $this->sanitize_statuses( $statuses );
		if ( empty( $statuses ) ) {
			return 0;
		}

		$placeholders = implode( ', ', array_fill( 0, count( $statuses ), '%s' ) );

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- SQL uses fixed clauses, generated placeholders, and a table name from the WordPress prefix.
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM ' . $this->table_name() . ' WHERE status IN (' . $placeholders . ')',
				$statuses
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter
	}

	/**
	 * Returns allowed proposal statuses.
	 *
	 * @return array<int,string>
	 */
	public function allowed_statuses(): array {
		return array(
			self::STATUS_PENDING,
			self::STATUS_APPROVED,
			self::STATUS_REJECTED,
			self::STATUS_EXPIRED,
			self::STATUS_ARCHIVED,
		);
	}

	/**
	 * Normalizes row.
	 *
	 * @param array<string,mixed> $row DB row.
	 * @return array<string,mixed>
	 */
	private function normalize_row( array $row ): array {
		$caller = $this->decode_json_field( $row['caller_json'] ?? '' );
		$policy = $this->policy_fields_from_caller( is_array( $caller ) ? $caller : array() );

		return array(
			'proposal_id'     => sanitize_text_field( (string) ( $row['proposal_id'] ?? '' ) ),
			'ability_id'      => sanitize_text_field( (string) ( $row['ability_id'] ?? '' ) ),
			'status'          => sanitize_key( (string) ( $row['status'] ?? '' ) ),
			'title'           => sanitize_text_field( (string) ( $row['title'] ?? '' ) ),
			'summary'         => sanitize_textarea_field( (string) ( $row['summary'] ?? '' ) ),
			'input'           => $this->decode_json_field( $row['input_json'] ?? '' ),
			'preview'         => $this->decode_json_field( $row['preview_json'] ?? '' ),
			'caller'          => is_array( $caller ) ? $caller : array(),
			'policy_decision' => $policy['policy_decision'],
			'policy_profile'  => $policy['policy_profile'],
			'policy_version'  => $policy['policy_version'],
			'policy_reasons'  => $policy['policy_reasons'],
			'created_by'      => (int) ( $row['created_by'] ?? 0 ),
			'created_at'      => sanitize_text_field( (string) ( $row['created_at'] ?? '' ) ),
			'updated_at'      => sanitize_text_field( (string) ( $row['updated_at'] ?? '' ) ),
		);
	}

	/**
	 * Returns normalized policy fields from caller metadata.
	 *
	 * @param array<string,mixed> $caller Caller metadata.
	 * @return array{policy_decision:string,policy_profile:string,policy_version:string,policy_reasons:array<int,string>}
	 */
	private function policy_fields_from_caller( array $caller ): array {
		$policy = is_array( $caller['core_policy'] ?? null ) ? $caller['core_policy'] : array();

		return array(
			'policy_decision' => sanitize_key( (string) ( $policy['policy_decision'] ?? Approval_Policy_Evaluator::DECISION_MANUAL_REQUIRED ) ),
			'policy_profile'  => sanitize_key( (string) ( $policy['policy_profile'] ?? Approval_Policy_Evaluator::PROFILE_MANUAL ) ),
			'policy_version'  => sanitize_key( (string) ( $policy['policy_version'] ?? Approval_Policy_Evaluator::VERSION ) ),
			'policy_reasons'  => array_values( array_map( 'sanitize_key', (array) ( $policy['policy_reasons'] ?? array( 'default_manual_required' ) ) ) ),
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
	 * Sanitizes and validates proposal statuses.
	 *
	 * @param array<int,string> $statuses Raw statuses.
	 * @return array<int,string>
	 */
	private function sanitize_statuses( array $statuses ): array {
		$allowed = $this->allowed_statuses();
		$clean   = array();

		foreach ( $statuses as $status ) {
			$status = sanitize_key( (string) $status );
			if ( in_array( $status, $allowed, true ) ) {
				$clean[] = $status;
			}
		}

		return array_values( array_unique( $clean ) );
	}

	/**
	 * Returns safe LIKE terms for a guardrail quota key.
	 *
	 * @param string $quota_key Guardrail quota key.
	 * @return array<int,string>
	 */
	private function guardrail_like_terms( string $quota_key ): array {
		$terms = array( $quota_key );

		if ( 0 === strpos( $quota_key, 'app:' ) ) {
			$app_id = substr( $quota_key, 4 );
			if ( '' !== $app_id ) {
				$terms[] = $app_id;
			}
		}

		return array_values( array_unique( array_filter( array_map( 'sanitize_text_field', $terms ) ) ) );
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
