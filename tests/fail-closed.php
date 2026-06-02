<?php
/**
 * Fail-closed fault injection tests for governance persistence.
 *
 * @package MagickAICore
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}

if ( ! defined( 'ARRAY_A' ) ) {
	define( 'ARRAY_A', 'ARRAY_A' );
}

if ( ! class_exists( 'WP_Error' ) ) {
	/**
	 * Minimal WP_Error stub for standalone fault tests.
	 */
	class WP_Error {
		/**
		 * Error code.
		 *
		 * @var string
		 */
		private $code;

		/**
		 * Error message.
		 *
		 * @var string
		 */
		private $message;

		/**
		 * Error data.
		 *
		 * @var mixed
		 */
		private $data;

		/**
		 * Constructor.
		 *
		 * @param string $code Error code.
		 * @param string $message Error message.
		 * @param mixed  $data Error data.
		 */
		public function __construct( string $code = '', string $message = '', $data = null ) {
			$this->code    = $code;
			$this->message = $message;
			$this->data    = $data;
		}

		/**
		 * Returns the error code.
		 *
		 * @return string
		 */
		public function get_error_code(): string {
			return $this->code;
		}

		/**
		 * Returns the error data.
		 *
		 * @return mixed
		 */
		public function get_error_data() {
			return $this->data;
		}

		/**
		 * Returns the error message.
		 *
		 * @return string
		 */
		public function get_error_message(): string {
			return $this->message;
		}
	}
}

if ( ! class_exists( 'WP_REST_Request' ) ) {
	/**
	 * Minimal REST request stub.
	 */
	class WP_REST_Request {
		/**
		 * Parameters.
		 *
		 * @var array<string,mixed>
		 */
		private $params;

		/**
		 * Constructor.
		 *
		 * @param array<string,mixed> $params Parameters.
		 */
		public function __construct( array $params = array() ) {
			$this->params = $params;
		}

		/**
		 * Returns one parameter.
		 *
		 * @param string $key Key.
		 * @return mixed
		 */
		public function get_param( string $key ) {
			return $this->params[ $key ] ?? null;
		}

		/**
		 * Returns one header.
		 *
		 * @param string $key Header key.
		 * @return string
		 */
		public function get_header( string $key ): string {
			return '';
		}
	}
}

if ( ! class_exists( 'WP_REST_Response' ) ) {
	/**
	 * Minimal REST response stub.
	 */
	class WP_REST_Response {
		/**
		 * Response data.
		 *
		 * @var mixed
		 */
		private $data;

		/**
		 * Status code.
		 *
		 * @var int
		 */
		private $status;

		/**
		 * Constructor.
		 *
		 * @param mixed $data Data.
		 * @param int   $status Status.
		 */
		public function __construct( $data = null, int $status = 200 ) {
			$this->data   = $data;
			$this->status = $status;
		}

		/**
		 * Returns response data.
		 *
		 * @return mixed
		 */
		public function get_data() {
			return $this->data;
		}

		/**
		 * Returns response status.
		 *
		 * @return int
		 */
		public function get_status(): int {
			return $this->status;
		}
	}
}

if ( ! function_exists( '__' ) ) {
	/**
	 * Translation stub.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	function __( string $text, string $domain = '' ): string {
		return $text;
	}
}

if ( ! function_exists( 'is_wp_error' ) ) {
	/**
	 * WP_Error check.
	 *
	 * @param mixed $value Value.
	 * @return bool
	 */
	function is_wp_error( $value ): bool {
		return $value instanceof WP_Error;
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	/**
	 * Text sanitizer stub.
	 *
	 * @param mixed $value Value.
	 * @return string
	 */
	function sanitize_text_field( $value ): string {
		return trim( strip_tags( (string) $value ) );
	}
}

if ( ! function_exists( 'sanitize_textarea_field' ) ) {
	/**
	 * Textarea sanitizer stub.
	 *
	 * @param mixed $value Value.
	 * @return string
	 */
	function sanitize_textarea_field( $value ): string {
		return trim( strip_tags( (string) $value ) );
	}
}

if ( ! function_exists( 'sanitize_key' ) ) {
	/**
	 * Key sanitizer stub.
	 *
	 * @param mixed $value Value.
	 * @return string
	 */
	function sanitize_key( $value ): string {
		return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) ) ?? '';
	}
}

if ( ! function_exists( 'absint' ) ) {
	/**
	 * Absolute integer stub.
	 *
	 * @param mixed $value Value.
	 * @return int
	 */
	function absint( $value ): int {
		return abs( (int) $value );
	}
}

if ( ! function_exists( 'current_time' ) ) {
	/**
	 * Current time stub.
	 *
	 * @return string
	 */
	function current_time( string $type = '', bool $gmt = false ): string {
		return gmdate( 'Y-m-d H:i:s' );
	}
}

if ( ! function_exists( 'get_current_user_id' ) ) {
	/**
	 * Current user stub.
	 *
	 * @return int
	 */
	function get_current_user_id(): int {
		return 1;
	}
}

if ( ! function_exists( 'current_user_can' ) ) {
	/**
	 * Capability stub.
	 *
	 * @return bool
	 */
	function current_user_can( string $capability = '' ): bool {
		global $magick_ai_core_fail_closed_caps;

		$magick_ai_core_fail_closed_caps = is_array( $magick_ai_core_fail_closed_caps ?? null ) ? $magick_ai_core_fail_closed_caps : array();
		$capability = sanitize_key( $capability );

		return array_key_exists( $capability, $magick_ai_core_fail_closed_caps ) ? (bool) $magick_ai_core_fail_closed_caps[ $capability ] : true;
	}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	/**
	 * JSON encode stub.
	 *
	 * @param mixed $value Value.
	 * @return string|false
	 */
	function wp_json_encode( $value, int $flags = 0, int $depth = 512 ) {
		return json_encode( $value, $flags, $depth );
	}
}

if ( ! function_exists( 'wp_generate_uuid4' ) ) {
	/**
	 * UUID stub.
	 *
	 * @return string
	 */
	function wp_generate_uuid4(): string {
		static $counter = 0;
		++$counter;
		return sprintf( '00000000-0000-4000-8000-%012d', $counter );
	}
}

if ( ! function_exists( 'wp_generate_password' ) ) {
	/**
	 * Password stub.
	 *
	 * @return string
	 */
	function wp_generate_password( int $length = 12, bool $special_chars = true, bool $extra_special_chars = false ): string {
		return 'fault-injection-secret';
	}
}

if ( ! function_exists( 'get_option' ) ) {
	/**
	 * Option read stub.
	 *
	 * @param string $name Option name.
	 * @param mixed  $default Default.
	 * @return mixed
	 */
	function get_option( string $name, $default = false ) {
		global $magick_ai_core_fail_closed_options;

		$magick_ai_core_fail_closed_options = is_array( $magick_ai_core_fail_closed_options ?? null ) ? $magick_ai_core_fail_closed_options : array();

		return $magick_ai_core_fail_closed_options[ $name ] ?? $default;
	}
}

if ( ! function_exists( 'update_option' ) ) {
	/**
	 * Option write stub.
	 *
	 * @param string $name Option name.
	 * @param mixed  $value Value.
	 * @return bool
	 */
	function update_option( string $name, $value, $autoload = null ): bool {
		global $magick_ai_core_fail_closed_options;

		$magick_ai_core_fail_closed_options[ $name ] = $value;

		return true;
	}
}

if ( ! function_exists( 'get_transient' ) ) {
	/**
	 * Transient read stub.
	 *
	 * @param string $key Key.
	 * @return mixed
	 */
	function get_transient( string $key ) {
		global $magick_ai_core_fail_closed_transients;

		$magick_ai_core_fail_closed_transients = is_array( $magick_ai_core_fail_closed_transients ?? null ) ? $magick_ai_core_fail_closed_transients : array();

		return $magick_ai_core_fail_closed_transients[ $key ] ?? false;
	}
}

if ( ! function_exists( 'set_transient' ) ) {
	/**
	 * Transient write stub.
	 *
	 * @param string $key Key.
	 * @param mixed  $value Value.
	 * @return bool
	 */
	function set_transient( string $key, $value, int $expiration = 0 ): bool {
		global $magick_ai_core_fail_closed_transients;

		$magick_ai_core_fail_closed_transients[ $key ] = $value;

		return true;
	}
}

if ( ! function_exists( 'magick_ai_abilities_get_registered' ) ) {
	/**
	 * Ability provider fixture.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	function magick_ai_abilities_get_registered(): array {
		global $magick_ai_core_fail_closed_abilities;

		if ( is_array( $magick_ai_core_fail_closed_abilities ?? null ) ) {
			return $magick_ai_core_fail_closed_abilities;
		}

		return array(
			'magick-ai/create-draft' => array(
				'ability_id'        => 'magick-ai/create-draft',
				'label'             => 'Create Draft',
				'risk_level'        => 'write',
				'requires_approval' => true,
				'capability'        => 'edit_posts',
				'required_scopes'   => array( 'post.write' ),
				'input_schema'      => array(
					'type'       => 'object',
					'properties' => array(
						'dry_run'         => array( 'type' => 'boolean', 'default' => true ),
						'commit'          => array( 'type' => 'boolean', 'default' => false ),
						'idempotency_key' => array( 'type' => 'string' ),
					),
				),
				'output_schema'     => array( 'type' => 'object' ),
			),
			'magick-ai/update-post' => array(
				'ability_id'        => 'magick-ai/update-post',
				'label'             => 'Update Post',
				'risk_level'        => 'write',
				'requires_approval' => true,
				'capability'        => 'edit_posts',
				'required_scopes'   => array( 'post.write' ),
				'input_schema'      => array( 'type' => 'object', 'properties' => array( 'post_id' => array( 'type' => 'integer' ), 'dry_run' => array( 'type' => 'boolean' ), 'commit' => array( 'type' => 'boolean' ), 'idempotency_key' => array( 'type' => 'string' ) ) ),
				'output_schema'     => array( 'type' => 'object' ),
			),
			'magick-ai/set-post-terms' => array(
				'ability_id'        => 'magick-ai/set-post-terms',
				'label'             => 'Set Post Terms',
				'risk_level'        => 'write',
				'requires_approval' => true,
				'capability'        => 'edit_posts',
				'required_scopes'   => array( 'taxonomy.manage' ),
				'input_schema'      => array( 'type' => 'object', 'properties' => array( 'post_id' => array( 'type' => 'integer' ), 'taxonomy' => array( 'type' => 'string' ), 'term_ids' => array( 'type' => 'array' ), 'dry_run' => array( 'type' => 'boolean' ), 'commit' => array( 'type' => 'boolean' ), 'idempotency_key' => array( 'type' => 'string' ) ) ),
				'output_schema'     => array( 'type' => 'object' ),
			),
			'magick-ai/approve-comment' => array(
				'ability_id'        => 'magick-ai/approve-comment',
				'label'             => 'Approve Comment',
				'risk_level'        => 'write',
				'requires_approval' => true,
				'capability'        => 'moderate_comments',
				'required_scopes'   => array( 'comments.manage' ),
				'input_schema'      => array( 'type' => 'object', 'properties' => array( 'comment_id' => array( 'type' => 'integer' ), 'dry_run' => array( 'type' => 'boolean' ), 'commit' => array( 'type' => 'boolean' ), 'idempotency_key' => array( 'type' => 'string' ) ) ),
				'output_schema'     => array( 'type' => 'object' ),
			),
			'magick-ai/trash-post' => array(
				'ability_id'        => 'magick-ai/trash-post',
				'label'             => 'Trash Post',
				'risk_level'        => 'destructive',
				'requires_approval' => true,
				'capability'        => 'delete_posts',
				'required_scopes'   => array( 'post.delete' ),
				'input_schema'      => array( 'type' => 'object', 'properties' => array( 'post_id' => array( 'type' => 'integer' ), 'dry_run' => array( 'type' => 'boolean' ), 'commit' => array( 'type' => 'boolean' ), 'idempotency_key' => array( 'type' => 'string' ) ) ),
				'output_schema'     => array( 'type' => 'object' ),
			),
		);
	}
}

/**
 * In-memory WPDB stub with injectable table failures.
 */
final class Magick_AI_Core_Fail_Closed_WPDB {
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
		if ( false !== strpos( $table, 'magick_ai_core_audit_log' ) && in_array( (string) ( $record['event_name'] ?? '' ), $this->fail_insert_event_names, true ) ) {
			return false;
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

		return array(
			'query' => $query,
			'args'  => $args,
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

		if ( false !== strpos( $sql, 'magick_ai_core_proposals' ) && false !== strpos( $sql, 'proposal_id = %s' ) ) {
			return $this->first_matching( $this->prefix . 'magick_ai_core_proposals', array( 'proposal_id' => (string) ( $args[0] ?? '' ) ) );
		}

		if ( false !== strpos( $sql, 'magick_ai_core_app_keys' ) && false !== strpos( $sql, 'key_id = %s' ) ) {
			return $this->first_matching( $this->prefix . 'magick_ai_core_app_keys', array( 'key_id' => (string) ( $args[0] ?? '' ) ) );
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

		if ( false !== strpos( $sql, 'magick_ai_core_audit_log' ) ) {
			return $this->filter_audit_rows( $sql, $args );
		}

		return array();
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
		$rows      = $this->tables[ $this->prefix . 'magick_ai_core_audit_log' ] ?? array();
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

/**
 * Assertion helper.
 *
 * @param bool   $condition Condition.
 * @param string $message Message.
 * @return void
 */
function magick_ai_core_fail_closed_assert( bool $condition, string $message ): void {
	if ( ! $condition ) {
		fwrite( STDERR, '[fail] ' . $message . "\n" );
		exit( 1 );
	}
}

require_once dirname( __DIR__ ) . '/includes/Security/Request_Context.php';
require_once dirname( __DIR__ ) . '/includes/Audit/Audit_Log_Repository.php';
require_once dirname( __DIR__ ) . '/includes/Capabilities/Ability_Registry_Adapter.php';
require_once dirname( __DIR__ ) . '/includes/Governance/Approval_Policy_Evaluator.php';
require_once dirname( __DIR__ ) . '/includes/Governance/Proposal_Repository.php';
require_once dirname( __DIR__ ) . '/includes/Governance/Proposal_Service.php';
require_once dirname( __DIR__ ) . '/includes/Governance/Commit_Preflight_Service.php';
require_once dirname( __DIR__ ) . '/includes/Security/App_Key_Repository.php';
require_once dirname( __DIR__ ) . '/includes/Security/App_Rate_Limiter.php';
require_once dirname( __DIR__ ) . '/includes/Security/App_Authenticator.php';
require_once dirname( __DIR__ ) . '/includes/Rest/Apps_Controller.php';

/**
 * Resets global storage.
 *
 * @return Magick_AI_Core_Fail_Closed_WPDB
 */
function magick_ai_core_fail_closed_reset_db(): Magick_AI_Core_Fail_Closed_WPDB {
	global $wpdb, $magick_ai_core_fail_closed_options, $magick_ai_core_fail_closed_transients, $magick_ai_core_fail_closed_caps, $magick_ai_core_fail_closed_abilities;

	$wpdb = new Magick_AI_Core_Fail_Closed_WPDB();
	$magick_ai_core_fail_closed_options = array();
	$magick_ai_core_fail_closed_transients = array();
	$magick_ai_core_fail_closed_caps = array();
	$magick_ai_core_fail_closed_abilities = null;
	\MagickAI\Core\Security\Request_Context::clear();

	return $wpdb;
}

/**
 * Returns a proposal service and repository.
 *
 * @return array{service:\MagickAI\Core\Governance\Proposal_Service,proposals:\MagickAI\Core\Governance\Proposal_Repository}
 */
function magick_ai_core_fail_closed_proposal_stack(): array {
	$proposals = new \MagickAI\Core\Governance\Proposal_Repository();
	$service   = new \MagickAI\Core\Governance\Proposal_Service(
		$proposals,
		new \MagickAI\Core\Capabilities\Ability_Registry_Adapter(),
		new \MagickAI\Core\Audit\Audit_Log_Repository(),
		new \MagickAI\Core\Governance\Approval_Policy_Evaluator()
	);

	return array(
		'service'   => $service,
		'proposals' => $proposals,
	);
}

/**
 * Returns a proposal/preflight service stack.
 *
 * @return array{service:\MagickAI\Core\Governance\Proposal_Service,preflight:\MagickAI\Core\Governance\Commit_Preflight_Service,proposals:\MagickAI\Core\Governance\Proposal_Repository,audit:\MagickAI\Core\Audit\Audit_Log_Repository}
 */
function magick_ai_core_fail_closed_governance_stack(): array {
	$proposals = new \MagickAI\Core\Governance\Proposal_Repository();
	$abilities = new \MagickAI\Core\Capabilities\Ability_Registry_Adapter();
	$audit     = new \MagickAI\Core\Audit\Audit_Log_Repository();
	$service   = new \MagickAI\Core\Governance\Proposal_Service(
		$proposals,
		$abilities,
		$audit,
		new \MagickAI\Core\Governance\Approval_Policy_Evaluator()
	);
	$preflight = new \MagickAI\Core\Governance\Commit_Preflight_Service( $proposals, $abilities, $audit );

	return array(
		'service'   => $service,
		'preflight' => $preflight,
		'proposals' => $proposals,
		'audit'     => $audit,
	);
}

/**
 * Creates a valid proposal payload.
 *
 * @return array<string,mixed>
 */
function magick_ai_core_fail_closed_payload(): array {
	return array(
		'ability_id' => 'magick-ai/create-draft',
		'title'      => 'Draft proposal',
		'summary'    => 'Create a draft.',
		'input'      => array( 'dry_run' => true ),
		'preview'    => array( 'after_suggestion' => 'Draft content' ),
		'caller'     => array( 'source' => 'fault_injection' ),
	);
}

/**
 * Creates a representative governance payload.
 *
 * @param string $ability_id Ability id.
 * @return array<string,mixed>
 */
function magick_ai_core_fail_closed_governance_payload( string $ability_id ): array {
	$inputs = array(
		'magick-ai/create-draft'     => array( 'title' => 'Governed draft', 'dry_run' => true, 'commit' => false, 'idempotency_key' => 'draft-1' ),
		'magick-ai/update-post'      => array( 'post_id' => 101, 'title' => 'Updated title', 'dry_run' => true, 'commit' => false, 'idempotency_key' => 'update-1' ),
		'magick-ai/set-post-terms'   => array( 'post_id' => 101, 'taxonomy' => 'post_tag', 'term_ids' => array( 7 ), 'dry_run' => true, 'commit' => false, 'idempotency_key' => 'terms-1' ),
		'magick-ai/approve-comment'  => array( 'comment_id' => 55, 'dry_run' => true, 'commit' => false, 'idempotency_key' => 'comment-1' ),
		'magick-ai/trash-post'       => array( 'post_id' => 101, 'dry_run' => true, 'commit' => false, 'idempotency_key' => 'trash-1' ),
	);

	return array(
		'ability_id' => $ability_id,
		'title'      => 'Governance negative smoke: ' . $ability_id,
		'summary'    => 'Representative proposal for negative governance smoke.',
		'input'      => $inputs[ $ability_id ] ?? array( 'dry_run' => true, 'commit' => false, 'idempotency_key' => 'generic-1' ),
		'preview'    => array(
			'proposal_ready'   => true,
			'after_suggestion' => 'Preview for ' . $ability_id,
		),
		'caller'     => array( 'source' => 'governance_negative_smoke' ),
	);
}

/**
 * Returns audit rows for a proposal id and event name.
 *
 * @param string $proposal_id Proposal id.
 * @param string $event_name Event name.
 * @return array<int,array<string,mixed>>
 */
function magick_ai_core_fail_closed_audit_rows( string $proposal_id, string $event_name ): array {
	global $wpdb;

	return array_values(
		array_filter(
			$wpdb->rows( 'wp_magick_ai_core_audit_log' ),
			static function ( array $row ) use ( $proposal_id, $event_name ): bool {
				return $proposal_id === (string) ( $row['proposal_id'] ?? '' ) && $event_name === (string) ( $row['event_name'] ?? '' );
			}
		)
	);
}

/**
 * Creates a trusted cleanup batch payload.
 *
 * @return array<string,mixed>
 */
function magick_ai_core_fail_closed_cleanup_batch_payload(): array {
	return array(
		'ability_id' => 'magick-ai/trash-post',
		'title'      => 'Cleanup batch',
		'summary'    => 'Trash trusted test content.',
		'input'      => array(
			'write_actions' => array(
				array(
					'action_id'         => 'trash_test_post_101',
					'target_ability_id' => 'magick-ai/trash-post',
					'input'             => array(
						'post_id' => 101,
						'dry_run' => true,
						'commit'  => false,
					),
					'requires_approval' => true,
					'commit_execution'  => false,
				),
			),
			'dry_run'       => true,
			'commit'        => false,
		),
		'preview'    => array(
			'source'       => array(
				'type'            => 'plan_to_proposal_batch',
				'plan_ability_id' => 'magick-ai/build-test-content-cleanup-plan',
				'batch_approval'  => true,
			),
			'action_count' => 1,
			'plan_preview' => array(
				'posts' => array(
					array(
						'post_id'         => 101,
						'title'           => 'Core Plan Bridge Test Cleanup Candidate',
						'matched_pattern' => 'Core Plan Bridge Test Cleanup Candidate',
					),
				),
			),
		),
		'caller'     => array(
			'source'          => 'plan_to_proposal_batch',
			'plan_ability_id' => 'magick-ai/build-test-content-cleanup-plan',
			'batch_id'        => 'fault_injection_cleanup',
		),
	);
}

$proposal_table = 'wp_magick_ai_core_proposals';
$audit_table    = 'wp_magick_ai_core_audit_log';
$app_table      = 'wp_magick_ai_core_app_keys';

$wpdb = magick_ai_core_fail_closed_reset_db();
$wpdb->fail_insert_tables[] = $proposal_table;
$repository = new \MagickAI\Core\Governance\Proposal_Repository();
$result     = $repository->create( magick_ai_core_fail_closed_payload() );
magick_ai_core_fail_closed_assert( is_wp_error( $result ), 'Proposal insert failure returns WP_Error.' );
magick_ai_core_fail_closed_assert( 'magick_ai_core_proposal_insert_failed' === $result->get_error_code(), 'Proposal insert failure uses stable error code.' );
magick_ai_core_fail_closed_assert( 0 === count( $wpdb->rows( $proposal_table ) ), 'Proposal insert failure stores no proposal row.' );

$wpdb = magick_ai_core_fail_closed_reset_db();
$wpdb->fail_insert_tables[] = $audit_table;
$stack  = magick_ai_core_fail_closed_proposal_stack();
$result = $stack['service']->create( magick_ai_core_fail_closed_payload() );
magick_ai_core_fail_closed_assert( is_wp_error( $result ), 'Proposal creation audit failure returns WP_Error.' );
magick_ai_core_fail_closed_assert( 'magick_ai_core_proposal_audit_failed' === $result->get_error_code(), 'Proposal creation audit failure uses stable error code.' );
magick_ai_core_fail_closed_assert( 0 === count( $wpdb->rows( $proposal_table ) ), 'Unaudited proposal creation is deleted.' );

$wpdb = magick_ai_core_fail_closed_reset_db();
$stack    = magick_ai_core_fail_closed_proposal_stack();
$proposal = $stack['service']->create( magick_ai_core_fail_closed_payload() );
magick_ai_core_fail_closed_assert( ! is_wp_error( $proposal ) && 'manual_required' === (string) ( $proposal['policy_decision'] ?? '' ), 'Control proposal records the default policy decision.' );
magick_ai_core_fail_closed_assert( 'manual' === (string) ( $proposal['policy_profile'] ?? '' ), 'Control proposal records the default policy profile.' );
magick_ai_core_fail_closed_assert( 'core-approval-policy-v1' === (string) ( $proposal['policy_version'] ?? '' ), 'Control proposal records the policy version.' );
magick_ai_core_fail_closed_assert( in_array( 'default_manual_required', (array) ( $proposal['policy_reasons'] ?? array() ), true ), 'Control proposal records manual policy reason.' );

$wpdb = magick_ai_core_fail_closed_reset_db();
$wpdb->fail_insert_event_names[] = 'proposal.policy_evaluated';
$stack  = magick_ai_core_fail_closed_proposal_stack();
$result = $stack['service']->create( magick_ai_core_fail_closed_payload() );
magick_ai_core_fail_closed_assert( is_wp_error( $result ), 'Policy decision audit failure returns WP_Error.' );
magick_ai_core_fail_closed_assert( 'magick_ai_core_policy_decision_audit_failed' === $result->get_error_code(), 'Policy decision audit failure uses stable error code.' );
magick_ai_core_fail_closed_assert( 0 === count( $wpdb->rows( $proposal_table ) ), 'Unaudited policy decision deletes the proposal row.' );

$wpdb = magick_ai_core_fail_closed_reset_db();
update_option( \MagickAI\Core\Governance\Approval_Policy_Evaluator::OPTION_POLICY_MODE, \MagickAI\Core\Governance\Approval_Policy_Evaluator::MODE_LOCAL_GUARDED, false );
\MagickAI\Core\Security\Request_Context::set_app(
	array(
		'app_id'       => 'app_auto',
		'key_id'       => 'key_auto',
		'caller_type'  => 'trusted_adapter',
		'scope'        => 'proposals:create',
		'scopes'       => array( 'proposals:create', 'proposals:approve' ),
		'route_family' => 'proposals_create',
	)
);
$stack    = magick_ai_core_fail_closed_proposal_stack();
$proposal = $stack['service']->create( magick_ai_core_fail_closed_cleanup_batch_payload() );
magick_ai_core_fail_closed_assert( ! is_wp_error( $proposal ), 'Local guarded cleanup proposal is created.' );
magick_ai_core_fail_closed_assert( 'approved' === (string) ( $proposal['status'] ?? '' ), 'Local guarded cleanup proposal is auto-approved.' );
magick_ai_core_fail_closed_assert( 'auto_approved' === (string) ( $proposal['policy_decision'] ?? '' ), 'Local guarded cleanup records auto-approved decision.' );
magick_ai_core_fail_closed_assert( 'trusted_local' === (string) ( $proposal['policy_profile'] ?? '' ), 'Local guarded cleanup records trusted_local profile.' );
$auto_approval_events = array_filter(
	$wpdb->rows( $audit_table ),
	static function ( array $row ): bool {
		return 'proposal.auto_approved' === (string) ( $row['event_name'] ?? '' );
	}
);
magick_ai_core_fail_closed_assert( 1 === count( $auto_approval_events ), 'Local guarded cleanup writes proposal.auto_approved audit.' );

$wpdb = magick_ai_core_fail_closed_reset_db();
update_option( \MagickAI\Core\Governance\Approval_Policy_Evaluator::OPTION_POLICY_MODE, \MagickAI\Core\Governance\Approval_Policy_Evaluator::MODE_LOCAL_GUARDED, false );
\MagickAI\Core\Security\Request_Context::set_app(
	array(
		'app_id'       => 'app_auto',
		'key_id'       => 'key_auto',
		'caller_type'  => 'trusted_adapter',
		'scope'        => 'proposals:create',
		'scopes'       => array( 'proposals:create', 'proposals:approve' ),
		'route_family' => 'proposals_create',
	)
);
$wpdb->fail_insert_event_names[] = 'proposal.auto_approved';
$stack  = magick_ai_core_fail_closed_proposal_stack();
$result = $stack['service']->create( magick_ai_core_fail_closed_cleanup_batch_payload() );
magick_ai_core_fail_closed_assert( is_wp_error( $result ), 'Auto approval audit failure returns WP_Error.' );
magick_ai_core_fail_closed_assert( 'magick_ai_core_auto_approval_audit_failed' === $result->get_error_code(), 'Auto approval audit failure uses stable error code.' );
$proposal_rows = $wpdb->rows( $proposal_table );
magick_ai_core_fail_closed_assert( 1 === count( $proposal_rows ), 'Auto approval audit failure keeps the audited proposal row.' );
magick_ai_core_fail_closed_assert( 'pending' === (string) $proposal_rows[0]['status'], 'Auto approval audit failure leaves proposal pending, not approved.' );

$wpdb = magick_ai_core_fail_closed_reset_db();
$stack    = magick_ai_core_fail_closed_proposal_stack();
$proposal = $stack['service']->create( magick_ai_core_fail_closed_payload() );
magick_ai_core_fail_closed_assert( ! is_wp_error( $proposal ), 'Control proposal is created before decision failure injection.' );
$wpdb->fail_insert_tables[] = $audit_table;
$result = $stack['service']->approve( (string) $proposal['proposal_id'], array( 'reason' => 'approve' ) );
magick_ai_core_fail_closed_assert( is_wp_error( $result ), 'Approve audit failure returns WP_Error.' );
magick_ai_core_fail_closed_assert( 'magick_ai_core_proposal_decision_audit_failed' === $result->get_error_code(), 'Approve audit failure uses stable error code.' );
$rolled_back = $stack['proposals']->find( (string) $proposal['proposal_id'] );
magick_ai_core_fail_closed_assert( is_array( $rolled_back ) && 'pending' === $rolled_back['status'], 'Approve audit failure rolls status back to pending.' );

$wpdb = magick_ai_core_fail_closed_reset_db();
$stack    = magick_ai_core_fail_closed_proposal_stack();
$proposal = $stack['service']->create( magick_ai_core_fail_closed_payload() );
magick_ai_core_fail_closed_assert( ! is_wp_error( $proposal ), 'Control proposal is created before reject failure injection.' );
$wpdb->fail_insert_tables[] = $audit_table;
$result = $stack['service']->reject( (string) $proposal['proposal_id'], array( 'reason' => 'reject' ) );
magick_ai_core_fail_closed_assert( is_wp_error( $result ), 'Reject audit failure returns WP_Error.' );
magick_ai_core_fail_closed_assert( 'magick_ai_core_proposal_decision_audit_failed' === $result->get_error_code(), 'Reject audit failure uses stable error code.' );
$rolled_back = $stack['proposals']->find( (string) $proposal['proposal_id'] );
magick_ai_core_fail_closed_assert( is_array( $rolled_back ) && 'pending' === $rolled_back['status'], 'Reject audit failure rolls status back to pending.' );

$representative_ability_ids = array(
	'magick-ai/create-draft',
	'magick-ai/update-post',
	'magick-ai/set-post-terms',
	'magick-ai/approve-comment',
	'magick-ai/trash-post',
);

foreach ( $representative_ability_ids as $ability_id ) {
	$wpdb     = magick_ai_core_fail_closed_reset_db();
	$stack    = magick_ai_core_fail_closed_governance_stack();
	$proposal = $stack['service']->create( magick_ai_core_fail_closed_governance_payload( $ability_id ) );
	magick_ai_core_fail_closed_assert( ! is_wp_error( $proposal ), $ability_id . ' governance proposal is created.' );

	$pending_preflight = $stack['preflight']->preflight( (string) $proposal['proposal_id'] );
	magick_ai_core_fail_closed_assert( is_wp_error( $pending_preflight ), $ability_id . ' pending proposal fails commit preflight.' );
	magick_ai_core_fail_closed_assert( 'magick_ai_core_proposal_not_approved' === $pending_preflight->get_error_code(), $ability_id . ' pending preflight uses stable error code.' );
	magick_ai_core_fail_closed_assert( 1 === count( magick_ai_core_fail_closed_audit_rows( (string) $proposal['proposal_id'], 'commit.preflight_failed' ) ), $ability_id . ' pending preflight failure is audited.' );

	$approved = $stack['service']->approve( (string) $proposal['proposal_id'], array( 'reason' => 'negative_smoke_approval' ) );
	magick_ai_core_fail_closed_assert( ! is_wp_error( $approved ) && 'approved' === (string) ( $approved['status'] ?? '' ), $ability_id . ' proposal can be approved.' );

	$preflight = $stack['preflight']->preflight( (string) $proposal['proposal_id'] );
	magick_ai_core_fail_closed_assert( ! is_wp_error( $preflight ), $ability_id . ' approved proposal passes commit preflight.' );
	magick_ai_core_fail_closed_assert( false === (bool) ( $preflight['commit_execution'] ?? true ), $ability_id . ' preflight does not execute commits.' );
	magick_ai_core_fail_closed_assert( true === (bool) ( $preflight['idempotency_required'] ?? false ), $ability_id . ' preflight requires idempotency.' );
	magick_ai_core_fail_closed_assert( true === (bool) ( $preflight['contract_preflight']['contract_matches'] ?? false ), $ability_id . ' preflight confirms ability contract match.' );
	magick_ai_core_fail_closed_assert( true === (bool) ( $preflight['permission_preflight']['allowed'] ?? false ), $ability_id . ' preflight confirms permission.' );
	magick_ai_core_fail_closed_assert( 1 === count( magick_ai_core_fail_closed_audit_rows( (string) $proposal['proposal_id'], 'proposal.created' ) ), $ability_id . ' proposal creation is audited.' );
	magick_ai_core_fail_closed_assert( 1 === count( magick_ai_core_fail_closed_audit_rows( (string) $proposal['proposal_id'], 'proposal.policy_evaluated' ) ), $ability_id . ' policy evaluation is audited.' );
	magick_ai_core_fail_closed_assert( 1 === count( magick_ai_core_fail_closed_audit_rows( (string) $proposal['proposal_id'], 'proposal.approved' ) ), $ability_id . ' approval is audited.' );
	magick_ai_core_fail_closed_assert( 1 === count( magick_ai_core_fail_closed_audit_rows( (string) $proposal['proposal_id'], 'commit.preflighted' ) ), $ability_id . ' successful preflight is audited.' );

	$replay = $stack['preflight']->preflight( (string) $proposal['proposal_id'] );
	magick_ai_core_fail_closed_assert( is_wp_error( $replay ), $ability_id . ' duplicate commit preflight is rejected.' );
	magick_ai_core_fail_closed_assert( 'magick_ai_core_commit_preflight_already_issued' === $replay->get_error_code(), $ability_id . ' duplicate preflight uses stable error code.' );
}

$wpdb     = magick_ai_core_fail_closed_reset_db();
$stack    = magick_ai_core_fail_closed_governance_stack();
$proposal = $stack['service']->create( magick_ai_core_fail_closed_governance_payload( 'magick-ai/update-post' ) );
magick_ai_core_fail_closed_assert( ! is_wp_error( $proposal ), 'Contract drift proposal is created.' );
$approved = $stack['service']->approve( (string) $proposal['proposal_id'], array( 'reason' => 'contract_drift' ) );
magick_ai_core_fail_closed_assert( ! is_wp_error( $approved ), 'Contract drift proposal is approved.' );
$magick_ai_core_fail_closed_abilities = magick_ai_abilities_get_registered();
$magick_ai_core_fail_closed_abilities['magick-ai/update-post']['input_schema']['properties']['unexpected_new_required_control'] = array( 'type' => 'string' );
$drift = $stack['preflight']->preflight( (string) $proposal['proposal_id'] );
magick_ai_core_fail_closed_assert( is_wp_error( $drift ), 'Changed ability contract fails commit preflight.' );
magick_ai_core_fail_closed_assert( 'magick_ai_core_ability_contract_changed' === $drift->get_error_code(), 'Changed ability contract uses stable error code.' );
magick_ai_core_fail_closed_assert( 1 === count( magick_ai_core_fail_closed_audit_rows( (string) $proposal['proposal_id'], 'commit.preflight_failed' ) ), 'Contract drift preflight failure is audited.' );

$wpdb     = magick_ai_core_fail_closed_reset_db();
$stack    = magick_ai_core_fail_closed_governance_stack();
$proposal = $stack['service']->create( magick_ai_core_fail_closed_governance_payload( 'magick-ai/set-post-terms' ) );
magick_ai_core_fail_closed_assert( ! is_wp_error( $proposal ), 'Permission downgrade proposal is created.' );
$approved = $stack['service']->approve( (string) $proposal['proposal_id'], array( 'reason' => 'permission_downgrade' ) );
magick_ai_core_fail_closed_assert( ! is_wp_error( $approved ), 'Permission downgrade proposal is approved.' );
$magick_ai_core_fail_closed_caps['edit_posts'] = false;
$permission_denied = $stack['preflight']->preflight( (string) $proposal['proposal_id'] );
magick_ai_core_fail_closed_assert( is_wp_error( $permission_denied ), 'Missing WordPress capability fails commit preflight.' );
magick_ai_core_fail_closed_assert( 'magick_ai_core_ability_permission_denied' === $permission_denied->get_error_code(), 'Missing WordPress capability uses stable error code.' );
magick_ai_core_fail_closed_assert( 1 === count( magick_ai_core_fail_closed_audit_rows( (string) $proposal['proposal_id'], 'commit.preflight_failed' ) ), 'Permission preflight failure is audited.' );

$wpdb     = magick_ai_core_fail_closed_reset_db();
$stack    = magick_ai_core_fail_closed_governance_stack();
$proposal = $stack['service']->create( magick_ai_core_fail_closed_governance_payload( 'magick-ai/approve-comment' ) );
magick_ai_core_fail_closed_assert( ! is_wp_error( $proposal ), 'Unavailable ability proposal is created.' );
$approved = $stack['service']->approve( (string) $proposal['proposal_id'], array( 'reason' => 'ability_unavailable' ) );
magick_ai_core_fail_closed_assert( ! is_wp_error( $approved ), 'Unavailable ability proposal is approved.' );
$magick_ai_core_fail_closed_abilities = array();
$unavailable = $stack['preflight']->preflight( (string) $proposal['proposal_id'] );
magick_ai_core_fail_closed_assert( is_wp_error( $unavailable ), 'Unavailable ability fails commit preflight.' );
magick_ai_core_fail_closed_assert( 'magick_ai_core_ability_unavailable' === $unavailable->get_error_code(), 'Unavailable ability uses stable error code.' );
magick_ai_core_fail_closed_assert( 1 === count( magick_ai_core_fail_closed_audit_rows( (string) $proposal['proposal_id'], 'commit.preflight_failed' ) ), 'Unavailable ability preflight failure is audited.' );

$wpdb     = magick_ai_core_fail_closed_reset_db();
$stack    = magick_ai_core_fail_closed_governance_stack();
$proposal = $stack['service']->create(
	array_merge(
		magick_ai_core_fail_closed_governance_payload( 'magick-ai/trash-post' ),
		array(
			'preview' => array(
				'proposal_ready'     => false,
				'preflight_blockers' => array(
					array(
						'code'   => 'destructive_review_missing',
						'reason' => 'Destructive review evidence is required.',
					),
				),
			),
		)
	)
);
magick_ai_core_fail_closed_assert( ! is_wp_error( $proposal ), 'Blocked destructive proposal is created.' );
$approved = $stack['service']->approve( (string) $proposal['proposal_id'], array( 'reason' => 'blocked_preview' ) );
magick_ai_core_fail_closed_assert( ! is_wp_error( $approved ), 'Blocked destructive proposal is approved.' );
$blocked = $stack['preflight']->preflight( (string) $proposal['proposal_id'] );
magick_ai_core_fail_closed_assert( is_wp_error( $blocked ), 'Blocked item fails commit preflight.' );
magick_ai_core_fail_closed_assert( 'magick_ai_core_proposal_items_blocked' === $blocked->get_error_code(), 'Blocked item uses stable error code.' );
magick_ai_core_fail_closed_assert( 1 === count( magick_ai_core_fail_closed_audit_rows( (string) $proposal['proposal_id'], 'commit.preflight_failed' ) ), 'Blocked item preflight failure is audited.' );

$wpdb = magick_ai_core_fail_closed_reset_db();
$wpdb->fail_insert_tables[] = $app_table;
$apps   = new \MagickAI\Core\Security\App_Key_Repository();
$result = $apps->create( array( 'app_label' => 'Adapter Client' ) );
magick_ai_core_fail_closed_assert( is_wp_error( $result ), 'App-key insert failure returns WP_Error.' );
magick_ai_core_fail_closed_assert( 'magick_ai_core_app_insert_failed' === $result->get_error_code(), 'App-key insert failure uses stable error code.' );
magick_ai_core_fail_closed_assert( 0 === count( $wpdb->rows( $app_table ) ), 'App-key insert failure stores no app row.' );

$wpdb = magick_ai_core_fail_closed_reset_db();
$wpdb->fail_insert_tables[] = $audit_table;
$apps        = new \MagickAI\Core\Security\App_Key_Repository();
$audit       = new \MagickAI\Core\Audit\Audit_Log_Repository();
$rate_limiter = new \MagickAI\Core\Security\App_Rate_Limiter();
$auth        = new \MagickAI\Core\Security\App_Authenticator( $apps, $rate_limiter, $audit );
$controller  = new \MagickAI\Core\Rest\Apps_Controller( $apps, $audit, $auth );
$result      = $controller->create_app(
	new WP_REST_Request(
		array(
			'app_label'           => 'Adapter Client',
			'caller_type'         => 'product_adapter',
			'scopes'              => array( 'capabilities:read', 'proposals:create' ),
			'rate_limit'          => 60,
			'rate_window_seconds' => 3600,
		)
	)
);
magick_ai_core_fail_closed_assert( is_wp_error( $result ), 'App creation audit failure returns WP_Error.' );
magick_ai_core_fail_closed_assert( 'magick_ai_core_app_audit_failed' === $result->get_error_code(), 'App creation audit failure uses stable error code.' );
$app_rows = $wpdb->rows( $app_table );
magick_ai_core_fail_closed_assert( 1 === count( $app_rows ), 'App row is retained for revocation evidence after audit failure.' );
magick_ai_core_fail_closed_assert( 'revoked' === (string) $app_rows[0]['status'], 'App creation audit failure revokes the new key.' );
magick_ai_core_fail_closed_assert( ! $result instanceof WP_REST_Response, 'App creation audit failure does not return the one-time token response.' );

echo "Fail-closed fault injection: ok\n";
