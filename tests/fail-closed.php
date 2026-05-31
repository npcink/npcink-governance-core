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
		return true;
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

if ( ! function_exists( 'magick_ai_abilities_get_registered' ) ) {
	/**
	 * Ability provider fixture.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	function magick_ai_abilities_get_registered(): array {
		return array(
			'magick-ai/create-draft' => array(
				'ability_id'        => 'magick-ai/create-draft',
				'label'             => 'Create Draft',
				'risk_level'        => 'write',
				'requires_approval' => true,
				'input_schema'      => array( 'type' => 'object' ),
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
require_once dirname( __DIR__ ) . '/includes/Governance/Proposal_Repository.php';
require_once dirname( __DIR__ ) . '/includes/Governance/Proposal_Service.php';
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
	global $wpdb;

	$wpdb = new Magick_AI_Core_Fail_Closed_WPDB();
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
		new \MagickAI\Core\Audit\Audit_Log_Repository()
	);

	return array(
		'service'   => $service,
		'proposals' => $proposals,
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
