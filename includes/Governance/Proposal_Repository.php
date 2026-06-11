<?php
/**
 * Proposal repository.
 *
 * @package NpcinkGovernanceCore
 */

namespace Npcink\GovernanceCore\Governance;

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
	const STATUS_EXECUTED = 'executed';
	const STATUS_EXECUTION_FAILED = 'execution_failed';

	/**
	 * Returns table name.
	 *
	 * @return string
	 */
	public function table_name(): string {
		global $wpdb;

		return $wpdb->prefix . 'npcink_governance_core_proposals';
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
		$ability_id  = sanitize_text_field( (string) ( $data['ability_id'] ?? '' ) );
		$input       = is_array( $data['input'] ?? null ) ? $data['input'] : array();
		$record      = array(
			'proposal_id'  => $proposal_id,
			'ability_id'   => $ability_id,
			'status'       => self::STATUS_PENDING,
			'title'        => sanitize_text_field( (string) ( $data['title'] ?? '' ) ),
			'summary'      => sanitize_textarea_field( (string) ( $data['summary'] ?? '' ) ),
			'input_json'   => wp_json_encode( $this->sanitize_input_for_ability( $ability_id, $input ) ),
			'preview_json' => wp_json_encode( $this->sanitize_payload( $data['preview'] ?? array() ) ),
			'caller_json'  => wp_json_encode( $this->sanitize_payload( $data['caller'] ?? array() ) ),
			'created_by'   => get_current_user_id(),
			'created_at'   => $now,
			'updated_at'   => $now,
		);

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Core owns this custom governance table.
		$inserted = $wpdb->insert(
			$this->table_name(),
			$record,
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s' )
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( false === $inserted ) {
			return new WP_Error(
				'npcink_governance_core_proposal_insert_failed',
				__( 'Proposal could not be stored.', 'npcink-governance-core' ),
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

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Core owns this custom governance table.
		$deleted = $wpdb->delete(
			$this->table_name(),
			array( 'proposal_id' => sanitize_text_field( $proposal_id ) ),
			array( '%s' )
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

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
		if ( '' !== $status ) {
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Core owns this custom governance table.
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT proposal_id, ability_id, status, title, summary, input_json, preview_json, caller_json, created_by, created_at, updated_at FROM %i WHERE status = %s ORDER BY id DESC LIMIT %d OFFSET %d',
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
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT proposal_id, ability_id, status, title, summary, input_json, preview_json, caller_json, created_by, created_at, updated_at FROM %i ORDER BY id DESC LIMIT %d OFFSET %d',
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

		array_unshift( $args, $this->table_name() );

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- SQL uses fixed clauses, generated placeholders, and Core's custom governance table.
		$rows = $wpdb->get_results(
					$wpdb->prepare(
						'SELECT proposal_id, ability_id, status, title, summary, input_json, preview_json, caller_json, created_by, created_at, updated_at FROM %i WHERE status IN (' . $placeholders . ') ORDER BY id DESC LIMIT %d OFFSET %d',
						...$args
					),
				ARRAY_A
			);
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

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

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Core owns this custom governance table.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT proposal_id, ability_id, status, title, summary, input_json, preview_json, caller_json, created_by, created_at, updated_at FROM %i WHERE status = %s AND created_at < %s ORDER BY id ASC LIMIT %d',
				$this->table_name(),
				self::STATUS_PENDING,
				$cutoff,
				$limit
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

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
		$args       = array( $this->table_name(), self::STATUS_PENDING );

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

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber,PluginCheck.Security.DirectDB.UnescapedDBParameter,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- SQL uses fixed clauses, generated placeholders, and Core's custom governance table.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT proposal_id, ability_id, status, title, summary, input_json, preview_json, caller_json, created_by, created_at, updated_at FROM %i WHERE ' . $this->join_where_clauses( $where ) . ' ORDER BY id DESC LIMIT %d',
				...$args
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber,PluginCheck.Security.DirectDB.UnescapedDBParameter,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

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

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Core owns this custom governance table.
		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT proposal_id, ability_id, status, title, summary, input_json, preview_json, caller_json, created_by, created_at, updated_at FROM %i WHERE proposal_id = %s LIMIT 1',
				$this->table_name(),
				sanitize_text_field( $proposal_id )
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

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

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Core owns this custom governance table.
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
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

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

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Core owns this custom governance table.
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
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		return $this->find( $proposal_id );
	}


	/**
	 * Counts proposals.
	 *
	 * @return int
	 */
	public function count(): int {
		global $wpdb;

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

		array_unshift( $statuses, $this->table_name() );

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- SQL uses fixed clauses, generated placeholders, and Core's custom governance table.
		return (int) $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COUNT(*) FROM %i WHERE status IN (' . $placeholders . ')',
					...$statuses
				)
			);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
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
			self::STATUS_EXECUTED,
			self::STATUS_EXECUTION_FAILED,
		);
	}

	/**
	 * Sanitizes proposal input for a target ability before persistence or hashing.
	 *
	 * Most structured proposal input remains plain-text sanitized. The create-draft
	 * content field is a reviewed WordPress post-content field, so explicit
	 * content_format=html uses WordPress safe post HTML filtering instead.
	 * Gutenberg block object keys are also case-sensitive, so block write abilities
	 * preserve reviewed block-tree keys while still sanitizing values.
	 *
	 * @param string              $ability_id Target ability id.
	 * @param array<string,mixed> $input Raw proposal input.
	 * @return array<string,mixed>
	 */
	public function sanitize_input_for_ability( string $ability_id, array $input ): array {
		$ability_id = sanitize_text_field( $ability_id );
		$clean      = $this->sanitize_payload( $input );
		$clean      = is_array( $clean ) ? $clean : array();

		if ( $this->is_create_draft_html_input( $ability_id, $input ) ) {
			$clean['content'] = $this->sanitize_post_content_html( (string) ( $input['content'] ?? '' ) );
		}

		if ( $this->is_update_post_blocks_input( $ability_id, $input ) ) {
			$clean = $this->sanitize_update_post_blocks_input( $input, $clean );
		}

		if ( is_array( $input['write_actions'] ?? null ) && is_array( $clean['write_actions'] ?? null ) ) {
			foreach ( array_values( $input['write_actions'] ) as $index => $action ) {
				if ( ! is_array( $action ) || ! is_array( $action['input'] ?? null ) ) {
					continue;
				}
				if ( ! isset( $clean['write_actions'][ $index ] ) || ! is_array( $clean['write_actions'][ $index ] ) ) {
					continue;
				}
				if ( ! isset( $clean['write_actions'][ $index ]['input'] ) || ! is_array( $clean['write_actions'][ $index ]['input'] ) ) {
					continue;
				}
				$target_ability_id = sanitize_text_field( (string) ( $action['target_ability_id'] ?? '' ) );
				if ( ! $this->is_create_draft_html_input( $target_ability_id, $action['input'] ) ) {
					if ( $this->is_update_post_blocks_input( $target_ability_id, $action['input'] ) ) {
						$clean['write_actions'][ $index ]['input'] = $this->sanitize_update_post_blocks_input( $action['input'], $clean['write_actions'][ $index ]['input'] );
					}
					continue;
				}
				$clean['write_actions'][ $index ]['input']['content'] = $this->sanitize_post_content_html( (string) ( $action['input']['content'] ?? '' ) );
			}
		}

		return $clean;
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
	 * Returns whether a create-draft input explicitly requests HTML post content.
	 *
	 * @param string              $ability_id Target ability id.
	 * @param array<string,mixed> $input Raw input.
	 * @return bool
	 */
	private function is_create_draft_html_input( string $ability_id, array $input ): bool {
		return 'npcink-abilities-toolkit/create-draft' === sanitize_text_field( $ability_id )
			&& array_key_exists( 'content', $input )
			&& 'html' === sanitize_key( (string) ( $input['content_format'] ?? '' ) );
	}

	/**
	 * Returns whether an input contains Gutenberg block objects.
	 *
	 * @param string              $ability_id Target ability id.
	 * @param array<string,mixed> $input Raw input.
	 * @return bool
	 */
	private function is_update_post_blocks_input( string $ability_id, array $input ): bool {
		return in_array(
			sanitize_text_field( $ability_id ),
			array(
				'npcink-abilities-toolkit/update-post-blocks',
				'npcink-abilities-toolkit/update-template-blocks',
				'npcink-abilities-toolkit/upsert-template-blocks',
				'npcink-abilities-toolkit/update-template-part-blocks',
			),
			true
		)
			&& is_array( $input['blocks'] ?? null );
	}

	/**
	 * Preserves block tree keys while sanitizing block values.
	 *
	 * @param array<string,mixed> $input Raw update-post-blocks input.
	 * @param array<string,mixed> $clean Plain sanitized input.
	 * @return array<string,mixed>
	 */
	private function sanitize_update_post_blocks_input( array $input, array $clean ): array {
		$clean['blocks'] = $this->sanitize_block_payload( $input['blocks'] ?? array(), 'blocks' );

		return $clean;
	}

	/**
	 * Sanitizes reviewed draft HTML through WordPress post-content KSES rules.
	 *
	 * @param string $content Raw HTML content.
	 * @return string
	 */
	private function sanitize_post_content_html( string $content ): string {
		if ( function_exists( 'wp_kses_post' ) ) {
			return wp_kses_post( $content );
		}

		return sanitize_textarea_field( $content );
	}

	/**
	 * Sanitizes a Gutenberg block payload without lowercasing object keys.
	 *
	 * @param mixed  $value Raw block payload value.
	 * @param string $parent_key Parent object key.
	 * @return mixed
	 */
	private function sanitize_block_payload( $value, string $parent_key = '' ) {
		if ( is_array( $value ) ) {
			$clean = array();
			foreach ( $value as $key => $item ) {
				$clean_key = is_int( $key ) ? $key : $this->sanitize_block_payload_key( (string) $key );
				if ( '' === $clean_key ) {
					continue;
				}
				$child_parent_key    = is_int( $key ) && 'innerContent' === $parent_key ? $parent_key : (string) $clean_key;
				$clean[ $clean_key ] = $this->sanitize_block_payload( $item, $child_parent_key );
			}
			return $clean;
		}

		if ( is_string( $value ) ) {
			if ( 'innerHTML' === $parent_key || 'innerContent' === $parent_key ) {
				return $this->sanitize_post_content_html( $value );
			}
			return sanitize_textarea_field( $value );
		}

		if ( is_bool( $value ) || is_int( $value ) || is_float( $value ) || null === $value ) {
			return $value;
		}

		return sanitize_text_field( (string) $value );
	}

	/**
	 * Sanitizes a Gutenberg block object key while preserving camelCase.
	 *
	 * @param string $key Raw key.
	 * @return string
	 */
	private function sanitize_block_payload_key( string $key ): string {
		$key = preg_replace( '/[^A-Za-z0-9_-]/', '', $key );

		return is_string( $key ) ? $key : '';
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
	 * Joins query clauses that were built from fixed repository templates.
	 *
	 * @param array<int,string> $clauses WHERE clauses.
	 * @return string
	 */
	private function join_where_clauses( array $clauses ): string {
		return implode( ' AND ', array_filter( array_map( 'trim', $clauses ) ) );
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
