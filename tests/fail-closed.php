<?php
/**
 * Fail-closed fault injection tests for governance persistence.
 *
 * @package NpcinkGovernanceCore
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
		 * Returns all parameters.
		 *
		 * @return array<string,mixed>
		 */
		public function get_params(): array {
			return $this->params;
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
		global $npcink_governance_core_fail_closed_caps;

		$npcink_governance_core_fail_closed_caps = is_array( $npcink_governance_core_fail_closed_caps ?? null ) ? $npcink_governance_core_fail_closed_caps : array();
		$capability = sanitize_key( $capability );

		return array_key_exists( $capability, $npcink_governance_core_fail_closed_caps ) ? (bool) $npcink_governance_core_fail_closed_caps[ $capability ] : true;
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

if ( ! function_exists( 'do_action' ) ) {
	/**
	 * Action dispatcher stub.
	 *
	 * @param string $hook Hook name.
	 * @param mixed  ...$args Action arguments.
	 * @return void
	 */
	function do_action( string $hook, ...$args ): void {
		global $npcink_governance_core_fail_closed_actions;

		$npcink_governance_core_fail_closed_actions = is_array( $npcink_governance_core_fail_closed_actions ?? null ) ? $npcink_governance_core_fail_closed_actions : array();
		$npcink_governance_core_fail_closed_actions[] = array(
			'hook' => $hook,
			'args' => $args,
		);
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
		global $npcink_governance_core_fail_closed_options;

		$npcink_governance_core_fail_closed_options = is_array( $npcink_governance_core_fail_closed_options ?? null ) ? $npcink_governance_core_fail_closed_options : array();

		return $npcink_governance_core_fail_closed_options[ $name ] ?? $default;
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
		global $npcink_governance_core_fail_closed_options;

		$npcink_governance_core_fail_closed_options[ $name ] = $value;

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
		global $npcink_governance_core_fail_closed_transients;

		$npcink_governance_core_fail_closed_transients = is_array( $npcink_governance_core_fail_closed_transients ?? null ) ? $npcink_governance_core_fail_closed_transients : array();

		return $npcink_governance_core_fail_closed_transients[ $key ] ?? false;
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
		global $npcink_governance_core_fail_closed_transients;

		$npcink_governance_core_fail_closed_transients[ $key ] = $value;

		return true;
	}
}

if ( ! function_exists( 'npcink_abilities_toolkit_get_registered' ) ) {
	/**
	 * Ability provider fixture.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	function npcink_abilities_toolkit_get_registered(): array {
		global $npcink_governance_core_fail_closed_abilities;

		if ( is_array( $npcink_governance_core_fail_closed_abilities ?? null ) ) {
			return $npcink_governance_core_fail_closed_abilities;
		}

		return array(
			'npcink-abilities-toolkit/create-draft' => array(
				'ability_id'        => 'npcink-abilities-toolkit/create-draft',
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
			'npcink-abilities-toolkit/update-post' => array(
				'ability_id'        => 'npcink-abilities-toolkit/update-post',
				'label'             => 'Update Post',
				'risk_level'        => 'write',
				'requires_approval' => true,
				'capability'        => 'edit_posts',
				'required_scopes'   => array( 'post.write' ),
				'input_schema'      => array( 'type' => 'object', 'properties' => array( 'post_id' => array( 'type' => 'integer' ), 'dry_run' => array( 'type' => 'boolean' ), 'commit' => array( 'type' => 'boolean' ), 'idempotency_key' => array( 'type' => 'string' ) ) ),
				'output_schema'     => array( 'type' => 'object' ),
			),
			'npcink-abilities-toolkit/set-post-terms' => array(
				'ability_id'        => 'npcink-abilities-toolkit/set-post-terms',
				'label'             => 'Set Post Terms',
				'risk_level'        => 'write',
				'requires_approval' => true,
				'capability'        => 'edit_posts',
				'required_scopes'   => array( 'taxonomy.manage' ),
				'input_schema'      => array( 'type' => 'object', 'properties' => array( 'post_id' => array( 'type' => 'integer' ), 'taxonomy' => array( 'type' => 'string' ), 'term_ids' => array( 'type' => 'array' ), 'dry_run' => array( 'type' => 'boolean' ), 'commit' => array( 'type' => 'boolean' ), 'idempotency_key' => array( 'type' => 'string' ) ) ),
				'output_schema'     => array( 'type' => 'object' ),
			),
			'npcink-abilities-toolkit/approve-comment' => array(
				'ability_id'        => 'npcink-abilities-toolkit/approve-comment',
				'label'             => 'Approve Comment',
				'risk_level'        => 'write',
				'requires_approval' => true,
				'capability'        => 'moderate_comments',
				'required_scopes'   => array( 'comments.manage' ),
				'input_schema'      => array( 'type' => 'object', 'properties' => array( 'comment_id' => array( 'type' => 'integer' ), 'dry_run' => array( 'type' => 'boolean' ), 'commit' => array( 'type' => 'boolean' ), 'idempotency_key' => array( 'type' => 'string' ) ) ),
				'output_schema'     => array( 'type' => 'object' ),
			),
			'npcink-abilities-toolkit/trash-post' => array(
				'ability_id'        => 'npcink-abilities-toolkit/trash-post',
				'label'             => 'Trash Post',
				'risk_level'        => 'destructive',
				'requires_approval' => true,
				'capability'        => 'delete_posts',
				'required_scopes'   => array( 'post.delete' ),
				'input_schema'      => array( 'type' => 'object', 'properties' => array( 'post_id' => array( 'type' => 'integer' ), 'dry_run' => array( 'type' => 'boolean' ), 'commit' => array( 'type' => 'boolean' ), 'idempotency_key' => array( 'type' => 'string' ) ) ),
				'output_schema'     => array( 'type' => 'object' ),
			),
			'npcink-abilities-toolkit/update-media-details' => array(
				'ability_id'        => 'npcink-abilities-toolkit/update-media-details',
				'label'             => 'Update Media Details',
				'risk_level'        => 'write',
				'requires_approval' => true,
				'capability'        => 'upload_files',
				'required_scopes'   => array( 'media.write' ),
				'input_schema'      => array( 'type' => 'object', 'properties' => array( 'attachment_id' => array( 'type' => 'integer' ), 'dry_run' => array( 'type' => 'boolean' ), 'commit' => array( 'type' => 'boolean' ), 'idempotency_key' => array( 'type' => 'string' ) ) ),
				'output_schema'     => array( 'type' => 'object' ),
			),
			'npcink-abilities-toolkit/upload-media-from-url' => array(
				'ability_id'        => 'npcink-abilities-toolkit/upload-media-from-url',
				'label'             => 'Upload Media From URL',
				'risk_level'        => 'write',
				'requires_approval' => true,
				'capability'        => 'upload_files',
				'required_scopes'   => array( 'media.write' ),
				'input_schema'      => array( 'type' => 'object', 'properties' => array( 'url' => array( 'type' => 'string' ), 'attach_to_post_id' => array( 'type' => 'integer' ), 'dry_run' => array( 'type' => 'boolean' ), 'commit' => array( 'type' => 'boolean' ), 'idempotency_key' => array( 'type' => 'string' ) ) ),
				'output_schema'     => array( 'type' => 'object' ),
			),
			'npcink-abilities-toolkit/set-post-featured-image' => array(
				'ability_id'        => 'npcink-abilities-toolkit/set-post-featured-image',
				'label'             => 'Set Post Featured Image',
				'risk_level'        => 'write',
				'requires_approval' => true,
				'capability'        => 'edit_posts',
				'required_scopes'   => array( 'media.write' ),
				'input_schema'      => array( 'type' => 'object', 'properties' => array( 'post_id' => array( 'type' => 'integer' ), 'attachment_id' => array( 'type' => 'integer' ), 'dry_run' => array( 'type' => 'boolean' ), 'commit' => array( 'type' => 'boolean' ), 'idempotency_key' => array( 'type' => 'string' ) ) ),
				'output_schema'     => array( 'type' => 'object' ),
			),
			'npcink-abilities-toolkit/adopt-cloud-media-derivative' => array(
				'ability_id'        => 'npcink-abilities-toolkit/adopt-cloud-media-derivative',
				'label'             => 'Adopt Cloud Media Derivative',
				'risk_level'        => 'write',
				'requires_approval' => true,
				'capability'        => 'upload_files',
				'required_scopes'   => array( 'media.write' ),
				'input_schema'      => array( 'type' => 'object', 'properties' => array( 'attachment_id' => array( 'type' => 'integer' ), 'dry_run' => array( 'type' => 'boolean' ), 'commit' => array( 'type' => 'boolean' ), 'idempotency_key' => array( 'type' => 'string' ) ) ),
				'output_schema'     => array( 'type' => 'object' ),
			),
			'npcink-abilities-toolkit/rename-media-file' => array(
				'ability_id'        => 'npcink-abilities-toolkit/rename-media-file',
				'label'             => 'Rename Media File',
				'risk_level'        => 'write',
				'requires_approval' => true,
				'capability'        => 'upload_files',
				'required_scopes'   => array( 'media.write' ),
				'input_schema'      => array( 'type' => 'object', 'properties' => array( 'attachment_id' => array( 'type' => 'integer' ), 'target_file_name' => array( 'type' => 'string' ), 'dry_run' => array( 'type' => 'boolean' ), 'commit' => array( 'type' => 'boolean' ), 'idempotency_key' => array( 'type' => 'string' ) ) ),
				'output_schema'     => array( 'type' => 'object' ),
			),
			'npcink-toolbox/build-article-write-plan' => array(
				'ability_id'        => 'npcink-toolbox/build-article-write-plan',
				'label'             => 'Build Article Write Plan',
				'risk_level'        => 'read',
				'requires_approval' => false,
				'capability'        => 'manage_options',
				'required_scopes'   => array( 'cap.toolbox.workflow_suggest' ),
				'input_schema'      => array( 'type' => 'object' ),
				'output_schema'     => array( 'type' => 'object' ),
			),
			'npcink-toolbox/build-article-batch-write-plan' => array(
				'ability_id'        => 'npcink-toolbox/build-article-batch-write-plan',
				'label'             => 'Build Article Batch Write Plan',
				'risk_level'        => 'read',
				'requires_approval' => false,
				'capability'        => 'manage_options',
				'required_scopes'   => array( 'cap.toolbox.workflow_suggest' ),
				'input_schema'      => array( 'type' => 'object' ),
				'output_schema'     => array( 'type' => 'object' ),
			),
			'npcink-toolbox/build-article-media-batch-write-plan' => array(
				'ability_id'        => 'npcink-toolbox/build-article-media-batch-write-plan',
				'label'             => 'Build Article Media Batch Write Plan',
				'risk_level'        => 'read',
				'requires_approval' => false,
				'capability'        => 'manage_options',
				'required_scopes'   => array( 'cap.toolbox.workflow_suggest' ),
				'input_schema'      => array( 'type' => 'object' ),
				'output_schema'     => array( 'type' => 'object' ),
			),
			'npcink-toolbox/build-image-candidate-adoption-plan' => array(
				'ability_id'        => 'npcink-toolbox/build-image-candidate-adoption-plan',
				'label'             => 'Build Image Candidate Adoption Plan',
				'risk_level'        => 'read',
				'requires_approval' => false,
				'capability'        => 'manage_options',
				'required_scopes'   => array( 'cap.toolbox.workflow_suggest' ),
				'input_schema'      => array( 'type' => 'object' ),
				'output_schema'     => array( 'type' => 'object' ),
			),
			'npcink-toolbox/build-site-knowledge-review-plan' => array(
				'ability_id'        => 'npcink-toolbox/build-site-knowledge-review-plan',
				'label'             => 'Build Site Knowledge Review Plan',
				'risk_level'        => 'read',
				'requires_approval' => false,
				'capability'        => 'manage_options',
				'required_scopes'   => array( 'cap.toolbox.workflow_suggest' ),
				'input_schema'      => array( 'type' => 'object' ),
				'output_schema'     => array( 'type' => 'object' ),
			),
			'npcink-abilities-toolkit/build-media-optimization-plan' => array(
				'ability_id'        => 'npcink-abilities-toolkit/build-media-optimization-plan',
				'label'             => 'Build Media Optimization Plan',
				'risk_level'        => 'read',
				'requires_approval' => false,
				'capability'        => 'upload_files',
				'required_scopes'   => array( 'media.read' ),
				'input_schema'      => array( 'type' => 'object' ),
				'output_schema'     => array( 'type' => 'object' ),
			),
			'npcink-abilities-toolkit/build-media-rename-plan' => array(
				'ability_id'        => 'npcink-abilities-toolkit/build-media-rename-plan',
				'label'             => 'Build Media Rename Plan',
				'risk_level'        => 'read',
				'requires_approval' => false,
				'capability'        => 'upload_files',
				'required_scopes'   => array( 'media.read' ),
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
		if ( false !== strpos( $table, 'npcink_governance_core_audit_log' ) && in_array( (string) ( $record['event_name'] ?? '' ), $this->fail_insert_event_names, true ) ) {
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

		$kept_args = array();
		$index     = 0;
		$query     = preg_replace_callback(
			'/%[isdFf]/',
			static function ( array $matches ) use ( $args, &$kept_args, &$index ): string {
				$placeholder = (string) $matches[0];
				$value       = $args[ $index ] ?? null;
				++$index;

				if ( '%i' === $placeholder ) {
					return (string) $value;
				}

				$kept_args[] = $value;
				return $placeholder;
			},
			$query
		);

		return array(
			'query' => is_string( $query ) ? $query : '',
			'args'  => $kept_args,
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

		if ( false !== strpos( $sql, 'npcink_governance_core_proposals' ) && false !== strpos( $sql, 'proposal_id = %s' ) ) {
			return $this->first_matching( $this->prefix . 'npcink_governance_core_proposals', array( 'proposal_id' => (string) ( $args[0] ?? '' ) ) );
		}

		if ( false !== strpos( $sql, 'npcink_governance_core_app_keys' ) && false !== strpos( $sql, 'key_id = %s' ) ) {
			return $this->first_matching( $this->prefix . 'npcink_governance_core_app_keys', array( 'key_id' => (string) ( $args[0] ?? '' ) ) );
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

		if ( false !== strpos( $sql, 'npcink_governance_core_audit_log' ) ) {
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
		$rows      = $this->tables[ $this->prefix . 'npcink_governance_core_audit_log' ] ?? array();
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
function npcink_governance_core_fail_closed_assert( bool $condition, string $message ): void {
	if ( ! $condition ) {
		fwrite( STDERR, '[fail] ' . $message . "\n" );
		exit( 1 );
	}
}

require_once dirname( __DIR__ ) . '/includes/Security/Request_Context.php';
require_once dirname( __DIR__ ) . '/includes/Observability.php';
require_once dirname( __DIR__ ) . '/includes/Audit/Audit_Log_Repository.php';
require_once dirname( __DIR__ ) . '/includes/Capabilities/Ability_Registry_Adapter.php';
require_once dirname( __DIR__ ) . '/includes/Governance/Approval_Policy_Evaluator.php';
require_once dirname( __DIR__ ) . '/includes/Governance/Proposal_Repository.php';
require_once dirname( __DIR__ ) . '/includes/Governance/Proposal_Service.php';
require_once dirname( __DIR__ ) . '/includes/Governance/Plan_Proposal_Service.php';
require_once dirname( __DIR__ ) . '/includes/Governance/Commit_Preflight_Service.php';
require_once dirname( __DIR__ ) . '/includes/Security/App_Key_Repository.php';
require_once dirname( __DIR__ ) . '/includes/Security/App_Rate_Limiter.php';
require_once dirname( __DIR__ ) . '/includes/Security/App_Authenticator.php';
require_once dirname( __DIR__ ) . '/includes/Rest/Apps_Controller.php';
require_once dirname( __DIR__ ) . '/includes/Rest/Proposals_Controller.php';

/**
 * Resets global storage.
 *
 * @return Magick_AI_Core_Fail_Closed_WPDB
 */
function npcink_governance_core_fail_closed_reset_db(): Magick_AI_Core_Fail_Closed_WPDB {
	global $wpdb, $npcink_governance_core_fail_closed_options, $npcink_governance_core_fail_closed_transients, $npcink_governance_core_fail_closed_caps, $npcink_governance_core_fail_closed_abilities, $npcink_governance_core_fail_closed_actions;

	$wpdb = new Magick_AI_Core_Fail_Closed_WPDB();
	$npcink_governance_core_fail_closed_options = array();
	$npcink_governance_core_fail_closed_transients = array();
	$npcink_governance_core_fail_closed_caps = array();
	$npcink_governance_core_fail_closed_abilities = null;
	$npcink_governance_core_fail_closed_actions = array();
	\Npcink\GovernanceCore\Security\Request_Context::clear();

	return $wpdb;
}

/**
 * Returns a proposal service and repository.
 *
 * @return array{service:\Npcink\GovernanceCore\Governance\Proposal_Service,proposals:\Npcink\GovernanceCore\Governance\Proposal_Repository}
 */
function npcink_governance_core_fail_closed_proposal_stack(): array {
	$proposals = new \Npcink\GovernanceCore\Governance\Proposal_Repository();
	$service   = new \Npcink\GovernanceCore\Governance\Proposal_Service(
		$proposals,
		new \Npcink\GovernanceCore\Capabilities\Ability_Registry_Adapter(),
		new \Npcink\GovernanceCore\Audit\Audit_Log_Repository(),
		new \Npcink\GovernanceCore\Governance\Approval_Policy_Evaluator()
	);

	return array(
		'service'   => $service,
		'proposals' => $proposals,
	);
}

/**
 * Returns a proposal/preflight service stack.
 *
 * @return array{service:\Npcink\GovernanceCore\Governance\Proposal_Service,preflight:\Npcink\GovernanceCore\Governance\Commit_Preflight_Service,proposals:\Npcink\GovernanceCore\Governance\Proposal_Repository,audit:\Npcink\GovernanceCore\Audit\Audit_Log_Repository}
 */
function npcink_governance_core_fail_closed_governance_stack(): array {
	$proposals = new \Npcink\GovernanceCore\Governance\Proposal_Repository();
	$abilities = new \Npcink\GovernanceCore\Capabilities\Ability_Registry_Adapter();
	$audit     = new \Npcink\GovernanceCore\Audit\Audit_Log_Repository();
	$service   = new \Npcink\GovernanceCore\Governance\Proposal_Service(
		$proposals,
		$abilities,
		$audit,
		new \Npcink\GovernanceCore\Governance\Approval_Policy_Evaluator()
	);
	$preflight = new \Npcink\GovernanceCore\Governance\Commit_Preflight_Service( $proposals, $abilities, $audit );

	return array(
		'service'   => $service,
		'preflight' => $preflight,
		'proposals' => $proposals,
		'audit'     => $audit,
	);
}

/**
 * Returns a proposal REST controller stack.
 *
 * @return array{controller:\Npcink\GovernanceCore\Rest\Proposals_Controller,service:\Npcink\GovernanceCore\Governance\Proposal_Service,preflight:\Npcink\GovernanceCore\Governance\Commit_Preflight_Service,proposals:\Npcink\GovernanceCore\Governance\Proposal_Repository,audit:\Npcink\GovernanceCore\Audit\Audit_Log_Repository}
 */
function npcink_governance_core_fail_closed_proposals_controller_stack(): array {
	$proposals = new \Npcink\GovernanceCore\Governance\Proposal_Repository();
	$abilities = new \Npcink\GovernanceCore\Capabilities\Ability_Registry_Adapter();
	$audit     = new \Npcink\GovernanceCore\Audit\Audit_Log_Repository();
	$service   = new \Npcink\GovernanceCore\Governance\Proposal_Service(
		$proposals,
		$abilities,
		$audit,
		new \Npcink\GovernanceCore\Governance\Approval_Policy_Evaluator()
	);
	$preflight = new \Npcink\GovernanceCore\Governance\Commit_Preflight_Service( $proposals, $abilities, $audit );
	$plan      = new \Npcink\GovernanceCore\Governance\Plan_Proposal_Service( $abilities, $service, $audit );
	$auth      = new \Npcink\GovernanceCore\Security\App_Authenticator(
		new \Npcink\GovernanceCore\Security\App_Key_Repository(),
		new \Npcink\GovernanceCore\Security\App_Rate_Limiter(),
		$audit
	);

	return array(
		'controller' => new \Npcink\GovernanceCore\Rest\Proposals_Controller( $service, $proposals, $preflight, $plan, $auth ),
		'service'    => $service,
		'preflight'  => $preflight,
		'proposals'  => $proposals,
		'audit'      => $audit,
	);
}

/**
 * Resets captured observability action events.
 *
 * @return void
 */
function npcink_governance_core_fail_closed_reset_observability_events(): void {
	global $npcink_governance_core_fail_closed_actions;

	$npcink_governance_core_fail_closed_actions = array();
}

/**
 * Returns captured observability payloads.
 *
 * @param string $event_kind Optional event kind filter.
 * @return array<int,array<string,mixed>>
 */
function npcink_governance_core_fail_closed_observability_events( string $event_kind = '' ): array {
	global $npcink_governance_core_fail_closed_actions;

	$actions = is_array( $npcink_governance_core_fail_closed_actions ?? null ) ? $npcink_governance_core_fail_closed_actions : array();
	$events  = array();

	foreach ( $actions as $action ) {
		if ( 'npcink_governance_core_observability_event' !== (string) ( $action['hook'] ?? '' ) ) {
			continue;
		}

		$args    = is_array( $action['args'] ?? null ) ? $action['args'] : array();
		$payload = is_array( $args[0] ?? null ) ? $args[0] : array();
		if ( '' !== $event_kind && $event_kind !== (string) ( $payload['event_kind'] ?? '' ) ) {
			continue;
		}

		$events[] = $payload;
	}

	return $events;
}

/**
 * Asserts that an observability event contains metadata only.
 *
 * @param array<string,mixed> $event Event payload.
 * @param string              $message Assertion message prefix.
 * @return void
 */
function npcink_governance_core_fail_closed_assert_observability_metadata_only( array $event, string $message ): void {
	foreach ( array( 'input', 'preview', 'caller', 'proposal', 'policy', 'note', 'payload', 'payload_json', 'raw' ) as $forbidden_key ) {
		npcink_governance_core_fail_closed_assert( ! array_key_exists( $forbidden_key, $event ), $message . ' omits forbidden key ' . $forbidden_key . '.' );
	}

	foreach ( $event as $key => $value ) {
		npcink_governance_core_fail_closed_assert( ! is_array( $value ), $message . ' keeps field ' . $key . ' bounded to a scalar.' );
	}

	$event_id = (string) ( $event['event_id'] ?? '' );
	npcink_governance_core_fail_closed_assert( '' !== $event_id, $message . ' includes a stable event id.' );
	npcink_governance_core_fail_closed_assert( 1 === preg_match( '/^[a-z0-9_]+$/', $event_id ), $message . ' event id is bounded to safe characters.' );

	$json = wp_json_encode( $event );
	$json = is_string( $json ) ? $json : '';
	foreach ( array( 'RAW_GENERATED_CONTENT_SENTINEL', 'APPROVAL_NOTE_SENTINEL', 'REJECTION_NOTE_SENTINEL', 'CALLER_SECRET_SENTINEL', 'POLICY_PAYLOAD_SENTINEL' ) as $sentinel ) {
		npcink_governance_core_fail_closed_assert( false === strpos( $json, $sentinel ), $message . ' does not expose ' . $sentinel . '.' );
	}
}

/**
 * Returns a plan-to-proposal service stack.
 *
 * @return array{service:\Npcink\GovernanceCore\Governance\Plan_Proposal_Service,proposals:\Npcink\GovernanceCore\Governance\Proposal_Repository}
 */
function npcink_governance_core_fail_closed_plan_stack(): array {
	$proposals = new \Npcink\GovernanceCore\Governance\Proposal_Repository();
	$abilities = new \Npcink\GovernanceCore\Capabilities\Ability_Registry_Adapter();
	$audit     = new \Npcink\GovernanceCore\Audit\Audit_Log_Repository();
	$proposal_service = new \Npcink\GovernanceCore\Governance\Proposal_Service(
		$proposals,
		$abilities,
		$audit,
		new \Npcink\GovernanceCore\Governance\Approval_Policy_Evaluator()
	);

	return array(
		'service'   => new \Npcink\GovernanceCore\Governance\Plan_Proposal_Service( $abilities, $proposal_service, $audit ),
		'proposals' => $proposals,
	);
}

/**
 * Creates a valid proposal payload.
 *
 * @return array<string,mixed>
 */
function npcink_governance_core_fail_closed_payload(): array {
	return array(
		'ability_id' => 'npcink-abilities-toolkit/create-draft',
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
function npcink_governance_core_fail_closed_governance_payload( string $ability_id ): array {
	$inputs = array(
		'npcink-abilities-toolkit/create-draft'     => array( 'title' => 'Governed draft', 'dry_run' => true, 'commit' => false, 'idempotency_key' => 'draft-1' ),
		'npcink-abilities-toolkit/update-post'      => array( 'post_id' => 101, 'title' => 'Updated title', 'dry_run' => true, 'commit' => false, 'idempotency_key' => 'update-1' ),
		'npcink-abilities-toolkit/set-post-terms'   => array( 'post_id' => 101, 'taxonomy' => 'post_tag', 'term_ids' => array( 7 ), 'dry_run' => true, 'commit' => false, 'idempotency_key' => 'terms-1' ),
		'npcink-abilities-toolkit/approve-comment'  => array( 'comment_id' => 55, 'dry_run' => true, 'commit' => false, 'idempotency_key' => 'comment-1' ),
		'npcink-abilities-toolkit/trash-post'       => array( 'post_id' => 101, 'dry_run' => true, 'commit' => false, 'idempotency_key' => 'trash-1' ),
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
function npcink_governance_core_fail_closed_audit_rows( string $proposal_id, string $event_name ): array {
	global $wpdb;

	return array_values(
		array_filter(
			$wpdb->rows( 'wp_npcink_governance_core_audit_log' ),
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
function npcink_governance_core_fail_closed_cleanup_batch_payload(): array {
	return array(
		'ability_id' => 'npcink-abilities-toolkit/trash-post',
		'title'      => 'Cleanup batch',
		'summary'    => 'Trash trusted test content.',
		'input'      => array(
			'write_actions' => array(
				array(
					'action_id'         => 'trash_test_post_101',
					'target_ability_id' => 'npcink-abilities-toolkit/trash-post',
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
				'plan_ability_id' => 'npcink-abilities-toolkit/build-nonproduction-content-cleanup-plan',
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
			'plan_ability_id' => 'npcink-abilities-toolkit/build-nonproduction-content-cleanup-plan',
			'batch_id'        => 'fault_injection_cleanup',
		),
	);
}

/**
 * Creates a representative Toolbox article write plan.
 *
 * @return array<string,mixed>
 */
function npcink_governance_core_fail_closed_article_write_plan(): array {
	return array(
		'artifact_type'          => 'article_write_plan',
		'version'                => 1,
		'batch_id'               => 'article_write_fault_injection',
		'requires_approval'      => true,
		'dry_run'                => true,
		'commit_execution'       => false,
		'proposal_mode'          => 'single',
		'article_goal_brief'     => array(
			'topic' => 'Governed writing workflow',
		),
		'research_evidence_pack' => array(
			'sources' => array(
				array(
					'title' => 'Internal planning note',
					'url'   => 'https://example.test/article-contract',
				),
			),
		),
		'article_outline'        => array(
			'sections' => array(
				array( 'heading' => 'Overview' ),
			),
		),
		'article_draft_candidate' => array(
			'content_markdown'  => 'Draft body.',
			'used_sources'      => array( 'https://example.test/article-contract' ),
			'unverified_claims' => array(),
			'needs_human_input' => array(),
		),
		'discoverability_pack'   => array(
			'seo_title'       => 'Governed writing workflow',
			'seo_description' => 'Draft description.',
		),
		'article_risk_report'    => array(
			'risk_level'         => 'low',
			'blocked_claims'     => array(),
			'needs_review'       => array(),
			'ready_for_proposal' => true,
		),
		'write_actions'          => array(
			array(
				'action_id'         => 'create_article_draft',
				'target_ability_id' => 'npcink-abilities-toolkit/create-draft',
				'input'             => array(
					'title'   => 'Governed writing workflow',
					'content' => 'Draft body.',
					'status'  => 'draft',
					'dry_run' => true,
					'commit'  => false,
				),
				'risk'              => 'medium',
				'requires_approval' => true,
				'commit_execution'  => false,
				'proposal_ready'    => true,
			),
		),
	);
}

/**
 * Creates a representative Toolbox article batch write plan.
 *
 * @return array<string,mixed>
 */
function npcink_governance_core_fail_closed_article_batch_write_plan(): array {
	$single_plan = npcink_governance_core_fail_closed_article_write_plan();
	$articles    = array();
	$actions     = array();

	for ( $index = 1; $index <= 3; $index++ ) {
		$article = $single_plan;
		unset( $article['artifact_type'], $article['version'], $article['batch_id'], $article['requires_approval'], $article['dry_run'], $article['commit_execution'], $article['proposal_mode'], $article['write_actions'] );
		$article['article_goal_brief']['topic'] = 'Governed writing workflow ' . $index;
		$article['article_draft_candidate']['content_markdown'] = 'Draft body ' . $index . '.';
		$articles[] = $article;

		$action = $single_plan['write_actions'][0];
		$action['action_id'] = 'create_article_draft_' . $index;
		$action['input']['title'] = 'Governed writing workflow ' . $index;
		$action['input']['content'] = 'Draft body ' . $index . '.';
		$action['input']['idempotency_key'] = 'article-batch-' . $index;
		$actions[] = $action;
	}

	return array(
		'artifact_type'     => 'article_batch_write_plan',
		'version'           => 1,
		'batch_id'          => 'article_batch_write_fault_injection',
		'requires_approval' => true,
		'dry_run'           => true,
		'commit_execution'  => false,
		'proposal_mode'     => 'batch',
		'batch_approval'    => true,
		'articles'          => $articles,
		'write_actions'     => $actions,
		'risk'              => array(
			'level'  => 'medium',
			'reason' => 'Three draft-only article writes.',
		),
	);
}

/**
 * Creates a representative Toolbox article media batch write plan.
 *
 * @return array<string,mixed>
 */
function npcink_governance_core_fail_closed_article_media_batch_write_plan(): array {
	$single_plan = npcink_governance_core_fail_closed_article_write_plan();
	$article     = $single_plan;
	unset( $article['artifact_type'], $article['version'], $article['batch_id'], $article['requires_approval'], $article['dry_run'], $article['commit_execution'], $article['proposal_mode'], $article['write_actions'] );
	$article['featured_image_candidate'] = array(
		'provider'          => 'unsplash',
		'regular_url'       => 'https://images.example.test/photo.jpg',
		'source_url'        => 'https://unsplash.com/photos/example',
		'download_location' => 'https://api.unsplash.com/photos/example/download',
		'photographer'      => 'Example Photographer',
		'attribution'       => 'Photo by Example Photographer on Unsplash.',
	);

	return array(
		'artifact_type'     => 'article_media_batch_write_plan',
		'version'           => 1,
		'batch_id'          => 'article_media_batch_write_fault_injection',
		'requires_approval' => true,
		'dry_run'           => true,
		'commit_execution'  => false,
		'proposal_mode'     => 'batch',
		'batch_approval'    => true,
		'articles'          => array( $article ),
		'media_workflow'    => array(
			array(
				'article_index'      => 0,
				'candidate_provider' => 'unsplash',
				'source_url'         => 'https://unsplash.com/photos/example',
				'download_location'  => 'https://api.unsplash.com/photos/example/download',
				'attribution'        => 'Photo by Example Photographer on Unsplash.',
			),
		),
		'write_actions'     => array(
			array(
				'action_id'         => 'create_article_draft_1',
				'target_ability_id' => 'npcink-abilities-toolkit/create-draft',
				'input'             => array(
					'title'   => 'Governed writing workflow',
					'content' => 'Draft body.',
					'status'  => 'draft',
					'dry_run' => true,
					'commit'  => false,
				),
				'requires_approval' => true,
				'commit_execution'  => false,
				'proposal_ready'    => true,
			),
			array(
				'action_id'         => 'upload_featured_image_1',
				'target_ability_id' => 'npcink-abilities-toolkit/upload-media-from-url',
				'depends_on'        => array( 'create_article_draft_1' ),
				'input'             => array(
					'url'               => 'https://images.example.test/photo.jpg',
					'alt'               => 'Example image alt text.',
					'source_type'       => 'stock',
					'source_page_url'   => 'https://unsplash.com/photos/example',
					'photographer_name' => 'Example Photographer',
					'attribution_text'  => 'Photo by Example Photographer on Unsplash.',
					'attach_to_post_id' => '$outputs.create_article_draft_1.post_id',
					'dry_run'           => true,
					'commit'            => false,
				),
				'requires_approval' => true,
				'commit_execution'  => false,
				'proposal_ready'    => true,
			),
			array(
				'action_id'         => 'update_featured_image_details_1',
				'target_ability_id' => 'npcink-abilities-toolkit/update-media-details',
				'depends_on'        => array( 'upload_featured_image_1' ),
				'input'             => array(
					'attachment_id'     => '$outputs.upload_featured_image_1.attachment_id',
					'alt'               => 'Example image alt text.',
					'source_type'       => 'stock',
					'source_page_url'   => 'https://unsplash.com/photos/example',
					'photographer_name' => 'Example Photographer',
					'attribution_text'  => 'Photo by Example Photographer on Unsplash.',
					'dry_run'           => true,
					'commit'            => false,
				),
				'requires_approval' => true,
				'commit_execution'  => false,
				'proposal_ready'    => true,
			),
			array(
				'action_id'         => 'set_featured_image_1',
				'target_ability_id' => 'npcink-abilities-toolkit/set-post-featured-image',
				'depends_on'        => array( 'create_article_draft_1', 'upload_featured_image_1' ),
				'input'             => array(
					'post_id'       => '$outputs.create_article_draft_1.post_id',
					'attachment_id' => '$outputs.upload_featured_image_1.attachment_id',
					'dry_run'       => true,
					'commit'        => false,
				),
				'requires_approval' => true,
				'commit_execution'  => false,
				'proposal_ready'    => true,
			),
		),
		'risk'              => array(
			'level'  => 'medium',
			'reason' => 'Draft creation with reviewed media upload and featured-image assignment.',
		),
	);
}

/**
 * Creates a representative media optimization plan.
 *
 * @return array<string,mixed>
 */
function npcink_governance_core_fail_closed_media_optimization_plan(): array {
	return array(
		'artifact_type'       => 'media_optimization_plan',
		'version'             => 1,
		'batch_id'            => 'media_optimization_fault_injection',
		'attachment_id'       => 1493,
		'optimization_goal'   => 'image_seo_and_webp',
		'requires_approval'   => true,
		'dry_run'             => true,
		'commit_execution'    => false,
		'proposal_mode'       => 'batch',
		'batch_approval'      => true,
		'metadata_preview'    => array(
			'before' => array( 'alt' => '' ),
			'after'  => array( 'alt' => 'AI generated product image' ),
		),
		'derivative_preview'  => array(
			'before' => array( 'mime_type' => 'image/png', 'size_bytes' => 900000 ),
			'after'  => array( 'mime_type' => 'image/webp', 'size_bytes' => 210000 ),
		),
		'write_actions'       => array(
			array(
				'action_id'         => 'update_media_details',
				'target_ability_id' => 'npcink-abilities-toolkit/update-media-details',
				'input'             => array(
					'attachment_id'    => 1493,
					'title'            => 'Optimized AI image',
					'alt'              => 'AI generated product image',
					'caption'          => 'AI generated product image.',
					'description'      => 'Optimized image metadata.',
					'source_type'      => 'ai_generated',
					'dry_run'          => true,
					'commit'           => false,
					'idempotency_key'  => 'media-optimize-metadata-1493',
				),
				'risk'              => 'medium',
				'requires_approval' => true,
				'commit_execution'  => false,
				'proposal_ready'    => true,
			),
			array(
				'action_id'         => 'adopt_webp_derivative',
				'target_ability_id' => 'npcink-abilities-toolkit/adopt-cloud-media-derivative',
				'input'             => array(
					'attachment_id'                  => 1493,
					'derivative_artifact'            => 'cloud://artifact/webp-1493',
					'expected_current_mime_type'     => 'image/png',
					'expected_derivative_mime_type'  => 'image/webp',
					'dry_run'                        => true,
					'commit'                         => false,
					'idempotency_key'                => 'media-optimize-derivative-1493',
				),
				'risk'              => 'medium',
				'requires_approval' => true,
				'commit_execution'  => false,
				'proposal_ready'    => true,
			),
		),
		'risk'                => array(
			'level'  => 'medium',
			'reason' => 'One attachment metadata update and derivative adoption.',
		),
	);
}

/**
 * Creates a representative media rename plan.
 *
 * @return array<string,mixed>
 */
function npcink_governance_core_fail_closed_media_rename_plan(): array {
	return array(
		'artifact_type'       => 'media_rename_plan',
		'version'             => 1,
		'batch_id'            => 'media_rename_fault_injection',
		'attachment_id'       => 1493,
		'requires_approval'   => true,
		'dry_run'             => true,
		'commit_execution'    => false,
		'proposal_mode'       => 'single',
		'batch_approval'      => false,
		'action_count'        => 1,
		'preview'             => array(
			'before' => array(
				'relative_file' => '2026/05/old-name.webp',
				'file_basename' => 'old-name.webp',
				'mime_type'     => 'image/webp',
				'content_hashes' => array(
					'md5'    => '1d7ea1565313df58fa0769e93e5310df',
					'sha256' => str_repeat( 'a', 64 ),
				),
			),
			'after_suggestion' => array(
				'relative_file' => '2026/05/1d7ea1565313df58fa0769e93e5310df.webp',
				'file_basename' => '1d7ea1565313df58fa0769e93e5310df.webp',
				'mime_type'     => 'image/webp',
			),
		),
		'write_actions'       => array(
			array(
				'action_id'         => 'rename_media_file_1493',
				'target_ability_id' => 'npcink-abilities-toolkit/rename-media-file',
				'input'             => array(
					'attachment_id'                  => 1493,
					'target_file_name'               => '1d7ea1565313df58fa0769e93e5310df.webp',
					'expected_current_relative_file' => '2026/05/old-name.webp',
					'expected_current_mime_type'     => 'image/webp',
					'expected_current_md5'           => '1d7ea1565313df58fa0769e93e5310df',
					'dry_run'                        => true,
					'commit'                         => false,
					'idempotency_key'                => 'media-rename-1493',
				),
				'risk'              => 'medium',
				'requires_approval' => true,
				'commit_execution'  => false,
				'proposal_ready'    => true,
			),
		),
		'risk'                => array(
			'level'  => 'medium',
			'reason' => 'One attachment main file rename changes its public URL.',
		),
	);
}

/**
 * Creates a representative image candidate adoption plan.
 *
 * @return array<string,mixed>
 */
function npcink_governance_core_fail_closed_image_candidate_adoption_plan(): array {
	return array(
		'artifact_type'              => 'image_candidate_adoption_plan',
		'version'                    => 1,
		'candidate_contract_version' => 'image_candidate.v1',
		'batch_id'                   => 'image_candidate_adoption_fault_injection',
		'requires_approval'          => true,
		'dry_run'                    => true,
		'commit_execution'           => false,
		'proposal_mode'              => 'batch',
		'batch_approval'             => true,
		'selected_image_candidate'   => array(
			'contract_version' => 'image_candidate.v1',
			'source_type'      => 'stock',
			'provider'         => 'unsplash',
			'provider_origin'  => 'toolbox',
			'download_url'     => 'https://images.example.test/photo.jpg',
			'thumbnail_url'    => 'https://images.example.test/photo-thumb.jpg',
			'source_url'       => 'https://unsplash.com/photos/example',
			'attribution'      => 'Photo by Example on Unsplash.',
		),
		'write_actions'              => array(
			array(
				'action_id'         => 'upload_image_candidate',
				'target_ability_id' => 'npcink-abilities-toolkit/upload-media-from-url',
				'input'             => array(
					'url'               => 'https://images.example.test/photo.jpg',
					'title'             => 'Reviewed image candidate',
					'alt'               => 'Reviewed image candidate',
					'caption'           => 'Photo by Example on Unsplash.',
					'description'       => 'Reviewed stock image.',
					'source_type'       => 'stock',
					'source_page_url'   => 'https://unsplash.com/photos/example',
					'photographer_name' => 'Example',
					'attribution_text'  => 'Photo by Example on Unsplash.',
					'attach_to_post_id' => 1493,
					'dry_run'           => true,
					'commit'            => false,
					'idempotency_key'   => 'image-candidate-upload-1493',
				),
				'risk'              => 'medium',
				'requires_approval' => true,
				'commit_execution'  => false,
				'proposal_ready'    => true,
			),
			array(
				'action_id'         => 'update_image_candidate_details',
				'target_ability_id' => 'npcink-abilities-toolkit/update-media-details',
				'depends_on'        => array( 'upload_image_candidate' ),
				'input'             => array(
					'attachment_id'     => '$outputs.upload_image_candidate.attachment_id',
					'title'             => 'Reviewed image candidate',
					'alt'               => 'Reviewed image candidate',
					'caption'           => 'Photo by Example on Unsplash.',
					'description'       => 'Reviewed stock image.',
					'source_type'       => 'stock',
					'source_page_url'   => 'https://unsplash.com/photos/example',
					'photographer_name' => 'Example',
					'attribution_text'  => 'Photo by Example on Unsplash.',
					'dry_run'           => true,
					'commit'            => false,
					'idempotency_key'   => 'image-candidate-details-1493',
				),
				'risk'              => 'medium',
				'requires_approval' => true,
				'commit_execution'  => false,
				'proposal_ready'    => true,
			),
			array(
				'action_id'         => 'set_image_candidate_featured_image',
				'target_ability_id' => 'npcink-abilities-toolkit/set-post-featured-image',
				'depends_on'        => array( 'upload_image_candidate' ),
				'input'             => array(
					'post_id'       => 1493,
					'attachment_id' => '$outputs.upload_image_candidate.attachment_id',
					'dry_run'       => true,
					'commit'        => false,
					'idempotency_key' => 'image-candidate-featured-1493',
				),
				'risk'              => 'medium',
				'requires_approval' => true,
				'commit_execution'  => false,
				'proposal_ready'    => true,
			),
		),
	);
}

/**
 * Creates a representative Site Knowledge review plan.
 *
 * @return array<string,mixed>
 */
function npcink_governance_core_fail_closed_site_knowledge_review_plan(): array {
	return array(
		'artifact_type'          => 'site_knowledge_review_plan',
		'version'                => 1,
		'batch_id'               => 'site_knowledge_review_fault_injection',
		'requires_approval'      => true,
		'dry_run'                => true,
		'commit_execution'       => false,
		'proposal_mode'          => 'single',
		'write_posture'          => 'core_proposal_handoff',
		'direct_wordpress_write' => false,
		'agent_id'               => 'site_knowledge_suggestion_agent',
		'agent_version'          => 'site_knowledge_agent.v1',
		'workflow'               => 'site_knowledge',
		'intent'                 => 'content_gap',
		'cloud_output'           => 'proposal_candidate',
		'local_next_action'      => 'operator_review',
		'evidence_gate_status'   => 'passed',
		'evidence_refs'          => array(
			array(
				'title'          => 'Existing site article',
				'url'            => 'https://example.test/existing-article',
				'post_id'        => 1493,
				'source_type'    => 'post',
				'suggested_use'  => 'supporting_evidence',
			),
		),
		'blocked_outputs'        => array( 'direct_wordpress_write' ),
		'preview'                => array(
			array(
				'action_id'      => 'review_site_knowledge_gap',
				'proposal_ready' => false,
			),
		),
		'write_actions'          => array(
			array(
				'action_id'         => 'review_site_knowledge_gap',
				'target_ability_id' => 'npcink-abilities-toolkit/create-draft',
				'input'             => array(
					'title'           => '',
					'content'         => '',
					'status'          => 'draft',
					'dry_run'         => true,
					'commit'          => false,
					'idempotency_key' => 'site-knowledge-review-fixture',
				),
				'risk'              => 'medium',
				'requires_approval' => true,
				'commit_execution'  => false,
				'proposal_ready'    => false,
				'requires_input'    => array( 'title', 'content' ),
			),
		),
	);
}

$proposal_table = 'wp_npcink_governance_core_proposals';
$audit_table    = 'wp_npcink_governance_core_audit_log';
$app_table      = 'wp_npcink_governance_core_app_keys';

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_proposals_controller_stack();
$controller = $stack['controller'];
$create_payload = npcink_governance_core_fail_closed_governance_payload( 'npcink-abilities-toolkit/create-draft' );
$create_payload['input']['content'] = 'RAW_GENERATED_CONTENT_SENTINEL';
$create_payload['preview']['policy_payload'] = 'POLICY_PAYLOAD_SENTINEL';
$create_payload['caller']['secret_hint'] = 'CALLER_SECRET_SENTINEL';
npcink_governance_core_fail_closed_reset_observability_events();
$create_response = $controller->create_proposal( new WP_REST_Request( $create_payload ) );
npcink_governance_core_fail_closed_assert( $create_response instanceof WP_REST_Response && 201 === $create_response->get_status(), 'Proposal REST create succeeds for observability smoke.' );
$created_proposal = $create_response->get_data();
$proposal_id      = (string) ( is_array( $created_proposal ) ? ( $created_proposal['proposal_id'] ?? '' ) : '' );
$create_events    = npcink_governance_core_fail_closed_observability_events( 'core.proposal.create' );
npcink_governance_core_fail_closed_assert( 1 === count( $create_events ), 'Proposal create emits one observability event.' );
npcink_governance_core_fail_closed_assert( 'ok' === (string) ( $create_events[0]['status'] ?? '' ), 'Proposal create emits ok status.' );
npcink_governance_core_fail_closed_assert( $proposal_id === (string) ( $create_events[0]['proposal_id'] ?? '' ), 'Proposal create event includes proposal id.' );
npcink_governance_core_fail_closed_assert( 'npcink-abilities-toolkit/create-draft' === (string) ( $create_events[0]['ability_id'] ?? '' ), 'Proposal create event includes ability id.' );
npcink_governance_core_fail_closed_assert_observability_metadata_only( $create_events[0], 'Proposal create observability event' );

npcink_governance_core_fail_closed_reset_observability_events();
$approve_response = $controller->approve_proposal(
	new WP_REST_Request(
		array(
			'proposal_id' => $proposal_id,
			'note'        => 'APPROVAL_NOTE_SENTINEL',
		)
	)
);
npcink_governance_core_fail_closed_assert( $approve_response instanceof WP_REST_Response && 200 === $approve_response->get_status(), 'Proposal REST approve succeeds for observability smoke.' );
$approve_events = npcink_governance_core_fail_closed_observability_events( 'core.proposal.approve' );
npcink_governance_core_fail_closed_assert( 1 === count( $approve_events ), 'Proposal approve emits one observability event.' );
npcink_governance_core_fail_closed_assert( 'ok' === (string) ( $approve_events[0]['status'] ?? '' ), 'Proposal approve emits ok status.' );
npcink_governance_core_fail_closed_assert( $proposal_id === (string) ( $approve_events[0]['proposal_id'] ?? '' ), 'Proposal approve event includes proposal id.' );
npcink_governance_core_fail_closed_assert_observability_metadata_only( $approve_events[0], 'Proposal approve observability event' );

npcink_governance_core_fail_closed_reset_observability_events();
$preflight_response = $controller->commit_preflight( new WP_REST_Request( array( 'proposal_id' => $proposal_id ) ) );
npcink_governance_core_fail_closed_assert( $preflight_response instanceof WP_REST_Response && 200 === $preflight_response->get_status(), 'Proposal REST preflight succeeds for observability smoke.' );
$preflight_data   = $preflight_response->get_data();
$preflight_events = npcink_governance_core_fail_closed_observability_events( 'core.commit.preflight' );
npcink_governance_core_fail_closed_assert( 1 === count( $preflight_events ), 'Successful preflight emits one observability event.' );
npcink_governance_core_fail_closed_assert( 'ok' === (string) ( $preflight_events[0]['status'] ?? '' ), 'Successful preflight emits ok status.' );
npcink_governance_core_fail_closed_assert( '' === (string) ( $preflight_events[0]['error_code'] ?? '' ), 'Successful preflight emits empty error code.' );
npcink_governance_core_fail_closed_assert( (string) ( is_array( $preflight_data ) ? ( $preflight_data['correlation_id'] ?? '' ) : '' ) === (string) ( $preflight_events[0]['correlation_id'] ?? '' ), 'Successful preflight event includes correlation id.' );
npcink_governance_core_fail_closed_assert_observability_metadata_only( $preflight_events[0], 'Successful preflight observability event' );

$reject_payload = npcink_governance_core_fail_closed_governance_payload( 'npcink-abilities-toolkit/set-post-terms' );
$reject_response = $controller->create_proposal( new WP_REST_Request( $reject_payload ) );
npcink_governance_core_fail_closed_assert( $reject_response instanceof WP_REST_Response && 201 === $reject_response->get_status(), 'Second proposal REST create succeeds for reject observability smoke.' );
$reject_proposal = $reject_response->get_data();
$reject_id       = (string) ( is_array( $reject_proposal ) ? ( $reject_proposal['proposal_id'] ?? '' ) : '' );
npcink_governance_core_fail_closed_reset_observability_events();
$reject_result = $controller->reject_proposal(
	new WP_REST_Request(
		array(
			'proposal_id' => $reject_id,
			'note'        => 'REJECTION_NOTE_SENTINEL',
		)
	)
);
npcink_governance_core_fail_closed_assert( $reject_result instanceof WP_REST_Response && 200 === $reject_result->get_status(), 'Proposal REST reject succeeds for observability smoke.' );
$reject_events = npcink_governance_core_fail_closed_observability_events( 'core.proposal.reject' );
npcink_governance_core_fail_closed_assert( 1 === count( $reject_events ), 'Proposal reject emits one observability event.' );
npcink_governance_core_fail_closed_assert( 'ok' === (string) ( $reject_events[0]['status'] ?? '' ), 'Proposal reject emits ok status.' );
npcink_governance_core_fail_closed_assert( $reject_id === (string) ( $reject_events[0]['proposal_id'] ?? '' ), 'Proposal reject event includes proposal id.' );
npcink_governance_core_fail_closed_assert_observability_metadata_only( $reject_events[0], 'Proposal reject observability event' );

$blocked_payload = npcink_governance_core_fail_closed_governance_payload( 'npcink-abilities-toolkit/trash-post' );
$blocked_payload['preview']['proposal_ready'] = false;
$blocked_payload['preview']['preflight_blockers'] = array(
	array(
		'code'   => 'destructive_review_missing',
		'reason' => 'Destructive review evidence is required.',
	),
);
$blocked_create = $controller->create_proposal( new WP_REST_Request( $blocked_payload ) );
npcink_governance_core_fail_closed_assert( $blocked_create instanceof WP_REST_Response && 201 === $blocked_create->get_status(), 'Blocked proposal REST create succeeds for observability smoke.' );
$blocked_proposal = $blocked_create->get_data();
$blocked_id       = (string) ( is_array( $blocked_proposal ) ? ( $blocked_proposal['proposal_id'] ?? '' ) : '' );
$blocked_approve  = $controller->approve_proposal( new WP_REST_Request( array( 'proposal_id' => $blocked_id ) ) );
npcink_governance_core_fail_closed_assert( $blocked_approve instanceof WP_REST_Response && 200 === $blocked_approve->get_status(), 'Blocked proposal REST approve succeeds before preflight block.' );
npcink_governance_core_fail_closed_reset_observability_events();
$blocked_preflight = $controller->commit_preflight( new WP_REST_Request( array( 'proposal_id' => $blocked_id ) ) );
npcink_governance_core_fail_closed_assert( is_wp_error( $blocked_preflight ), 'Blocked proposal REST preflight returns WP_Error.' );
$blocked_events = npcink_governance_core_fail_closed_observability_events( 'core.commit.preflight' );
npcink_governance_core_fail_closed_assert( 1 === count( $blocked_events ), 'Blocked preflight emits one observability event.' );
npcink_governance_core_fail_closed_assert( 'warning' === (string) ( $blocked_events[0]['status'] ?? '' ), 'Blocked preflight emits warning status.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_proposal_items_blocked' === (string) ( $blocked_events[0]['error_code'] ?? '' ), 'Blocked preflight event includes stable error code.' );
npcink_governance_core_fail_closed_assert_observability_metadata_only( $blocked_events[0], 'Blocked preflight observability event' );

$plan_error_request = new WP_REST_Request(
	array(
		'plan_ability_id' => 'npcink-abilities-toolkit/not-real-plan',
		'plan'            => array( 'success' => false ),
		'plan_input'      => array(),
		'caller'          => array( 'secret_hint' => 'CALLER_SECRET_SENTINEL' ),
	)
);
npcink_governance_core_fail_closed_reset_observability_events();
$plan_error = $controller->create_proposals_from_plan( $plan_error_request );
npcink_governance_core_fail_closed_assert( is_wp_error( $plan_error ), 'Invalid plan intake returns WP_Error for observability smoke.' );
$plan_events = npcink_governance_core_fail_closed_observability_events( 'core.proposal.plan_ingest' );
npcink_governance_core_fail_closed_assert( 1 === count( $plan_events ), 'Plan intake failure emits one observability event.' );
npcink_governance_core_fail_closed_assert( 'error' === (string) ( $plan_events[0]['status'] ?? '' ), 'Plan intake failure emits error status.' );
npcink_governance_core_fail_closed_assert( '' !== (string) ( $plan_events[0]['error_code'] ?? '' ), 'Plan intake failure emits stable error code.' );
npcink_governance_core_fail_closed_assert_observability_metadata_only( $plan_events[0], 'Plan intake observability event' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$article_plan = npcink_governance_core_fail_closed_article_write_plan();
$article_result = $stack['service']->create_from_plan( 'npcink-toolbox/build-article-write-plan', $article_plan, array(), array( 'source' => 'toolbox_article_workflow' ) );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $article_result ), 'Valid Toolbox article write plan creates a Core proposal.' );
npcink_governance_core_fail_closed_assert( 1 === (int) ( $article_result['proposal_count'] ?? 0 ), 'Valid Toolbox article write plan creates exactly one proposal.' );
npcink_governance_core_fail_closed_assert( 'npcink-abilities-toolkit/create-draft' === (string) ( $article_result['proposals'][0]['ability_id'] ?? '' ), 'Valid Toolbox article write plan targets create-draft.' );
npcink_governance_core_fail_closed_assert( is_array( $article_result['proposals'][0]['preview']['article_workflow'] ?? null ), 'Valid Toolbox article write plan preserves article workflow preview.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$publish_plan = npcink_governance_core_fail_closed_article_write_plan();
$publish_plan['write_actions'][0]['input']['status'] = 'publish';
$publish_result = $stack['service']->create_from_plan( 'npcink-toolbox/build-article-write-plan', $publish_plan );
npcink_governance_core_fail_closed_assert( is_wp_error( $publish_result ), 'Article write plan requesting publish is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_article_plan_publish_rejected' === $publish_result->get_error_code(), 'Article publish rejection uses stable error code.' );
npcink_governance_core_fail_closed_assert( 0 === count( $wpdb->rows( $proposal_table ) ), 'Rejected publish article plan stores no proposal row.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$blocked_claims_plan = npcink_governance_core_fail_closed_article_write_plan();
$blocked_claims_plan['article_risk_report']['blocked_claims'] = array( 'Unverified ranking guarantee.' );
$blocked_claims_result = $stack['service']->create_from_plan( 'npcink-toolbox/build-article-write-plan', $blocked_claims_plan );
npcink_governance_core_fail_closed_assert( is_wp_error( $blocked_claims_result ), 'Article write plan with blocked claims is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_article_plan_blocked_claims' === $blocked_claims_result->get_error_code(), 'Article blocked-claims rejection uses stable error code.' );
npcink_governance_core_fail_closed_assert( 0 === count( $wpdb->rows( $proposal_table ) ), 'Rejected blocked-claims article plan stores no proposal row.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$high_risk_plan = npcink_governance_core_fail_closed_article_write_plan();
$high_risk_plan['article_risk_report']['risk_level'] = 'high';
$high_risk_result = $stack['service']->create_from_plan( 'npcink-toolbox/build-article-write-plan', $high_risk_plan );
npcink_governance_core_fail_closed_assert( is_wp_error( $high_risk_result ), 'High-risk article write plan is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_article_plan_risk_blocked' === $high_risk_result->get_error_code(), 'Article high-risk rejection uses stable error code.' );
npcink_governance_core_fail_closed_assert( 0 === count( $wpdb->rows( $proposal_table ) ), 'Rejected high-risk article plan stores no proposal row.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$article_batch_plan = npcink_governance_core_fail_closed_article_batch_write_plan();
$article_batch_result = $stack['service']->create_from_plan( 'npcink-toolbox/build-article-batch-write-plan', $article_batch_plan, array(), array( 'source' => 'toolbox_article_batch_workflow' ) );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $article_batch_result ), 'Valid Toolbox article batch write plan creates a Core proposal.' );
npcink_governance_core_fail_closed_assert( 1 === (int) ( $article_batch_result['proposal_count'] ?? 0 ), 'Valid Toolbox article batch write plan creates one batch proposal.' );
$article_batch_proposal = is_array( $article_batch_result['proposals'][0] ?? null ) ? $article_batch_result['proposals'][0] : array();
npcink_governance_core_fail_closed_assert( 'plan_to_proposal_batch' === (string) ( $article_batch_proposal['preview']['source']['type'] ?? '' ), 'Article batch write plan stores batch proposal source.' );
npcink_governance_core_fail_closed_assert( 3 === count( (array) ( $article_batch_proposal['input']['write_actions'] ?? array() ) ), 'Article batch proposal stores all draft write actions.' );
npcink_governance_core_fail_closed_assert( is_array( $article_batch_proposal['preview']['article_batch_workflow'] ?? null ), 'Article batch proposal preserves batch workflow preview.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$article_batch_without_flag = npcink_governance_core_fail_closed_article_batch_write_plan();
$article_batch_without_flag['batch_approval'] = false;
$article_batch_without_flag['proposal_mode'] = 'single';
$article_batch_without_flag_result = $stack['service']->create_from_plan( 'npcink-toolbox/build-article-batch-write-plan', $article_batch_without_flag );
npcink_governance_core_fail_closed_assert( is_wp_error( $article_batch_without_flag_result ), 'Article batch write plan without explicit batch mode is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_article_batch_mode_required' === $article_batch_without_flag_result->get_error_code(), 'Article batch mode rejection uses stable error code.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$article_batch_publish_plan = npcink_governance_core_fail_closed_article_batch_write_plan();
$article_batch_publish_plan['write_actions'][1]['input']['status'] = 'publish';
$article_batch_publish_result = $stack['service']->create_from_plan( 'npcink-toolbox/build-article-batch-write-plan', $article_batch_publish_plan );
npcink_governance_core_fail_closed_assert( is_wp_error( $article_batch_publish_result ), 'Article batch write plan requesting publish is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_article_batch_publish_rejected' === $article_batch_publish_result->get_error_code(), 'Article batch publish rejection uses stable error code.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$article_media_batch_plan = npcink_governance_core_fail_closed_article_media_batch_write_plan();
$article_media_batch_result = $stack['service']->create_from_plan( 'npcink-toolbox/build-article-media-batch-write-plan', $article_media_batch_plan, array(), array( 'source' => 'toolbox_article_media_batch_workflow' ) );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $article_media_batch_result ), 'Valid Toolbox article media batch write plan creates a Core proposal.' );
npcink_governance_core_fail_closed_assert( 1 === (int) ( $article_media_batch_result['proposal_count'] ?? 0 ), 'Valid Toolbox article media batch write plan creates one batch proposal.' );
$article_media_batch_proposal = is_array( $article_media_batch_result['proposals'][0] ?? null ) ? $article_media_batch_result['proposals'][0] : array();
npcink_governance_core_fail_closed_assert( 'plan_to_proposal_batch' === (string) ( $article_media_batch_proposal['preview']['source']['type'] ?? '' ), 'Article media batch write plan stores batch proposal source.' );
npcink_governance_core_fail_closed_assert( 4 === count( (array) ( $article_media_batch_proposal['input']['write_actions'] ?? array() ) ), 'Article media batch proposal stores draft and media write actions.' );
npcink_governance_core_fail_closed_assert( is_array( $article_media_batch_proposal['preview']['article_media_batch_workflow'] ?? null ), 'Article media batch proposal preserves media workflow preview.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$article_media_missing_candidate = npcink_governance_core_fail_closed_article_media_batch_write_plan();
unset( $article_media_missing_candidate['articles'][0]['featured_image_candidate'] );
$article_media_missing_candidate_result = $stack['service']->create_from_plan( 'npcink-toolbox/build-article-media-batch-write-plan', $article_media_missing_candidate );
npcink_governance_core_fail_closed_assert( is_wp_error( $article_media_missing_candidate_result ), 'Article media batch write plan without image candidate evidence is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_article_media_batch_candidate_missing' === $article_media_missing_candidate_result->get_error_code(), 'Article media candidate rejection uses stable error code.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$article_media_missing_featured = npcink_governance_core_fail_closed_article_media_batch_write_plan();
array_pop( $article_media_missing_featured['write_actions'] );
$article_media_missing_featured_result = $stack['service']->create_from_plan( 'npcink-toolbox/build-article-media-batch-write-plan', $article_media_missing_featured );
npcink_governance_core_fail_closed_assert( is_wp_error( $article_media_missing_featured_result ), 'Article media batch write plan without featured-image action is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_article_media_batch_actions_missing' === $article_media_missing_featured_result->get_error_code(), 'Article media missing featured action rejection uses stable error code.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$media_optimization_plan = npcink_governance_core_fail_closed_media_optimization_plan();
$media_optimization_result = $stack['service']->create_from_plan( 'npcink-abilities-toolkit/build-media-optimization-plan', $media_optimization_plan, array(), array( 'source' => 'toolbox_media_optimization' ) );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $media_optimization_result ), 'Valid media optimization plan creates a Core proposal.' );
npcink_governance_core_fail_closed_assert( 1 === (int) ( $media_optimization_result['proposal_count'] ?? 0 ), 'Valid media optimization plan creates one batch proposal.' );
$media_optimization_proposal = is_array( $media_optimization_result['proposals'][0] ?? null ) ? $media_optimization_result['proposals'][0] : array();
npcink_governance_core_fail_closed_assert( 'plan_to_proposal_batch' === (string) ( $media_optimization_proposal['preview']['source']['type'] ?? '' ), 'Media optimization plan stores batch proposal source.' );
npcink_governance_core_fail_closed_assert( 2 === count( (array) ( $media_optimization_proposal['input']['write_actions'] ?? array() ) ), 'Media optimization proposal stores metadata and derivative actions.' );
npcink_governance_core_fail_closed_assert( is_array( $media_optimization_proposal['preview']['media_optimization'] ?? null ), 'Media optimization proposal preserves optimization preview.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$media_optimization_missing_derivative = npcink_governance_core_fail_closed_media_optimization_plan();
array_pop( $media_optimization_missing_derivative['write_actions'] );
$media_optimization_missing_result = $stack['service']->create_from_plan( 'npcink-abilities-toolkit/build-media-optimization-plan', $media_optimization_missing_derivative );
npcink_governance_core_fail_closed_assert( is_wp_error( $media_optimization_missing_result ), 'Media optimization plan without derivative adoption is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_media_optimization_actions_missing' === $media_optimization_missing_result->get_error_code(), 'Media optimization missing action rejection uses stable error code.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$media_optimization_mismatch = npcink_governance_core_fail_closed_media_optimization_plan();
$media_optimization_mismatch['write_actions'][1]['input']['attachment_id'] = 1494;
$media_optimization_mismatch_result = $stack['service']->create_from_plan( 'npcink-abilities-toolkit/build-media-optimization-plan', $media_optimization_mismatch );
npcink_governance_core_fail_closed_assert( is_wp_error( $media_optimization_mismatch_result ), 'Media optimization plan spanning multiple attachments is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_media_optimization_attachment_mismatch' === $media_optimization_mismatch_result->get_error_code(), 'Media optimization attachment mismatch uses stable error code.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$media_optimization_split_repair = npcink_governance_core_fail_closed_media_optimization_plan();
$media_optimization_split_repair['write_actions'][] = array(
	'action_id'         => 'repair_inline_media_reference',
	'target_ability_id' => 'npcink-abilities-toolkit/patch-post-content',
	'input'             => array(
		'post_id'          => 8842,
		'operations'       => array(
			array(
				'op'      => 'replace',
				'find'    => 'https://example.test/wp-content/uploads/2026/06/workflow-diagram-image-300x162.jpg',
				'replace' => 'https://example.test/wp-content/uploads/2026/06/customer-approved-diagram.webp',
				'limit'   => 1,
			),
		),
		'dry_run'          => true,
		'commit'           => false,
		'idempotency_key'  => 'media-optimize-reference-repair-1493',
	),
	'risk'              => 'medium',
	'requires_approval' => true,
	'commit_execution'  => false,
	'proposal_ready'    => true,
);
$media_optimization_split_repair_result = $stack['service']->create_from_plan( 'npcink-abilities-toolkit/build-media-optimization-plan', $media_optimization_split_repair );
npcink_governance_core_fail_closed_assert( is_wp_error( $media_optimization_split_repair_result ), 'Media optimization plan with separate post-content repair action is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_media_optimization_reference_repair_split' === $media_optimization_split_repair_result->get_error_code(), 'Media optimization split repair rejection uses stable error code.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$media_rename_plan = npcink_governance_core_fail_closed_media_rename_plan();
$media_rename_result = $stack['service']->create_from_plan( 'npcink-abilities-toolkit/build-media-rename-plan', $media_rename_plan, array(), array( 'source' => 'abilities_media_rename' ) );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $media_rename_result ), 'Valid media rename plan creates a Core proposal.' );
npcink_governance_core_fail_closed_assert( 1 === (int) ( $media_rename_result['proposal_count'] ?? 0 ), 'Valid media rename plan creates one proposal.' );
$media_rename_proposal = is_array( $media_rename_result['proposals'][0] ?? null ) ? $media_rename_result['proposals'][0] : array();
npcink_governance_core_fail_closed_assert( 'npcink-abilities-toolkit/rename-media-file' === (string) ( $media_rename_proposal['ability_id'] ?? '' ), 'Media rename plan creates a rename-media-file proposal.' );
npcink_governance_core_fail_closed_assert( is_array( $media_rename_proposal['preview']['media_rename'] ?? null ), 'Media rename proposal preserves rename preview.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$media_rename_missing_target = npcink_governance_core_fail_closed_media_rename_plan();
unset( $media_rename_missing_target['write_actions'][0]['input']['target_file_name'] );
$media_rename_missing_target_result = $stack['service']->create_from_plan( 'npcink-abilities-toolkit/build-media-rename-plan', $media_rename_missing_target );
npcink_governance_core_fail_closed_assert( is_wp_error( $media_rename_missing_target_result ), 'Media rename plan without target file name is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_media_rename_target_file_missing' === $media_rename_missing_target_result->get_error_code(), 'Media rename missing target file rejection uses stable error code.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$media_rename_mismatch = npcink_governance_core_fail_closed_media_rename_plan();
$media_rename_mismatch['write_actions'][0]['input']['attachment_id'] = 1494;
$media_rename_mismatch_result = $stack['service']->create_from_plan( 'npcink-abilities-toolkit/build-media-rename-plan', $media_rename_mismatch );
npcink_governance_core_fail_closed_assert( is_wp_error( $media_rename_mismatch_result ), 'Media rename plan spanning multiple attachments is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_media_rename_attachment_mismatch' === $media_rename_mismatch_result->get_error_code(), 'Media rename attachment mismatch uses stable error code.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$image_candidate_plan = npcink_governance_core_fail_closed_image_candidate_adoption_plan();
$image_candidate_result = $stack['service']->create_from_plan( 'npcink-toolbox/build-image-candidate-adoption-plan', $image_candidate_plan, array(), array( 'source' => 'toolbox_image_candidate_adoption' ) );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $image_candidate_result ), 'Valid image candidate adoption plan creates a Core proposal.' );
npcink_governance_core_fail_closed_assert( 1 === (int) ( $image_candidate_result['proposal_count'] ?? 0 ), 'Valid image candidate adoption plan creates one batch proposal.' );
$image_candidate_proposal = is_array( $image_candidate_result['proposals'][0] ?? null ) ? $image_candidate_result['proposals'][0] : array();
npcink_governance_core_fail_closed_assert( 'plan_to_proposal_batch' === (string) ( $image_candidate_proposal['preview']['source']['type'] ?? '' ), 'Image candidate adoption plan stores batch proposal source.' );
npcink_governance_core_fail_closed_assert( 3 === count( (array) ( $image_candidate_proposal['input']['write_actions'] ?? array() ) ), 'Image candidate adoption proposal stores media import, metadata, and featured-image actions.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$image_candidate_missing_contract = npcink_governance_core_fail_closed_image_candidate_adoption_plan();
unset( $image_candidate_missing_contract['candidate_contract_version'] );
unset( $image_candidate_missing_contract['selected_image_candidate']['contract_version'] );
$image_candidate_missing_contract_result = $stack['service']->create_from_plan( 'npcink-toolbox/build-image-candidate-adoption-plan', $image_candidate_missing_contract );
npcink_governance_core_fail_closed_assert( is_wp_error( $image_candidate_missing_contract_result ), 'Image candidate adoption plan without candidate contract is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_image_candidate_contract_missing' === $image_candidate_missing_contract_result->get_error_code(), 'Image candidate missing contract rejection uses stable error code.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$image_candidate_bad_source = npcink_governance_core_fail_closed_image_candidate_adoption_plan();
$image_candidate_bad_source['write_actions'][0]['input']['source_type'] = 'unsupported';
$image_candidate_bad_source_result = $stack['service']->create_from_plan( 'npcink-toolbox/build-image-candidate-adoption-plan', $image_candidate_bad_source );
npcink_governance_core_fail_closed_assert( is_wp_error( $image_candidate_bad_source_result ), 'Image candidate adoption plan with invalid source_type is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_image_candidate_source_type_invalid' === $image_candidate_bad_source_result->get_error_code(), 'Image candidate invalid source type rejection uses stable error code.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$site_knowledge_plan = npcink_governance_core_fail_closed_site_knowledge_review_plan();
$site_knowledge_result = $stack['service']->create_from_plan( 'npcink-toolbox/build-site-knowledge-review-plan', $site_knowledge_plan, array(), array( 'source' => 'toolbox_site_knowledge_review' ) );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $site_knowledge_result ), 'Valid Site Knowledge review plan creates a Core proposal.' );
npcink_governance_core_fail_closed_assert( 1 === (int) ( $site_knowledge_result['proposal_count'] ?? 0 ), 'Valid Site Knowledge review plan creates one blocked proposal.' );
npcink_governance_core_fail_closed_assert( 0 === (int) ( $site_knowledge_result['proposal_ready_count'] ?? 0 ), 'Site Knowledge review proposal is not proposal-ready before human draft input.' );
$site_knowledge_proposal = is_array( $site_knowledge_result['proposals'][0] ?? null ) ? $site_knowledge_result['proposals'][0] : array();
npcink_governance_core_fail_closed_assert( 'npcink-abilities-toolkit/create-draft' === (string) ( $site_knowledge_proposal['ability_id'] ?? '' ), 'Site Knowledge review plan targets the governed create-draft ability.' );
npcink_governance_core_fail_closed_assert( false === (bool) ( $site_knowledge_proposal['preview']['proposal_ready'] ?? true ), 'Site Knowledge review proposal stores proposal_ready=false.' );
npcink_governance_core_fail_closed_assert( in_array( 'title', (array) ( $site_knowledge_proposal['preview']['needs_input'] ?? array() ), true ), 'Site Knowledge review proposal requires title input.' );
npcink_governance_core_fail_closed_assert( isset( $site_knowledge_proposal['preview']['site_knowledge_review'] ), 'Site Knowledge review preview is preserved in the proposal.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$site_knowledge_ready = npcink_governance_core_fail_closed_site_knowledge_review_plan();
$site_knowledge_ready['write_actions'][0]['proposal_ready'] = true;
$site_knowledge_ready_result = $stack['service']->create_from_plan( 'npcink-toolbox/build-site-knowledge-review-plan', $site_knowledge_ready );
npcink_governance_core_fail_closed_assert( is_wp_error( $site_knowledge_ready_result ), 'Site Knowledge review plan marked ready is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_site_knowledge_ready_rejected' === $site_knowledge_ready_result->get_error_code(), 'Site Knowledge ready rejection uses stable error code.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$site_knowledge_missing_evidence = npcink_governance_core_fail_closed_site_knowledge_review_plan();
$site_knowledge_missing_evidence['evidence_refs'] = array();
$site_knowledge_missing_evidence_result = $stack['service']->create_from_plan( 'npcink-toolbox/build-site-knowledge-review-plan', $site_knowledge_missing_evidence );
npcink_governance_core_fail_closed_assert( is_wp_error( $site_knowledge_missing_evidence_result ), 'Site Knowledge review plan without evidence is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_site_knowledge_evidence_missing' === $site_knowledge_missing_evidence_result->get_error_code(), 'Site Knowledge missing evidence rejection uses stable error code.' );

$wpdb = npcink_governance_core_fail_closed_reset_db();
$wpdb->fail_insert_tables[] = $proposal_table;
$repository = new \Npcink\GovernanceCore\Governance\Proposal_Repository();
$result     = $repository->create( npcink_governance_core_fail_closed_payload() );
npcink_governance_core_fail_closed_assert( is_wp_error( $result ), 'Proposal insert failure returns WP_Error.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_proposal_insert_failed' === $result->get_error_code(), 'Proposal insert failure uses stable error code.' );
npcink_governance_core_fail_closed_assert( 0 === count( $wpdb->rows( $proposal_table ) ), 'Proposal insert failure stores no proposal row.' );

$wpdb = npcink_governance_core_fail_closed_reset_db();
$wpdb->fail_insert_tables[] = $audit_table;
$stack  = npcink_governance_core_fail_closed_proposal_stack();
$result = $stack['service']->create( npcink_governance_core_fail_closed_payload() );
npcink_governance_core_fail_closed_assert( is_wp_error( $result ), 'Proposal creation audit failure returns WP_Error.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_proposal_audit_failed' === $result->get_error_code(), 'Proposal creation audit failure uses stable error code.' );
npcink_governance_core_fail_closed_assert( 0 === count( $wpdb->rows( $proposal_table ) ), 'Unaudited proposal creation is deleted.' );

$wpdb = npcink_governance_core_fail_closed_reset_db();
$stack    = npcink_governance_core_fail_closed_proposal_stack();
$proposal = $stack['service']->create( npcink_governance_core_fail_closed_payload() );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $proposal ) && 'manual_required' === (string) ( $proposal['policy_decision'] ?? '' ), 'Control proposal records the default policy decision.' );
npcink_governance_core_fail_closed_assert( 'manual' === (string) ( $proposal['policy_profile'] ?? '' ), 'Control proposal records the default policy profile.' );
npcink_governance_core_fail_closed_assert( 'core-approval-policy-v1' === (string) ( $proposal['policy_version'] ?? '' ), 'Control proposal records the policy version.' );
npcink_governance_core_fail_closed_assert( in_array( 'default_manual_required', (array) ( $proposal['policy_reasons'] ?? array() ), true ), 'Control proposal records manual policy reason.' );

$wpdb = npcink_governance_core_fail_closed_reset_db();
$wpdb->fail_insert_event_names[] = 'proposal.policy_evaluated';
$stack  = npcink_governance_core_fail_closed_proposal_stack();
$result = $stack['service']->create( npcink_governance_core_fail_closed_payload() );
npcink_governance_core_fail_closed_assert( is_wp_error( $result ), 'Policy decision audit failure returns WP_Error.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_policy_decision_audit_failed' === $result->get_error_code(), 'Policy decision audit failure uses stable error code.' );
npcink_governance_core_fail_closed_assert( 0 === count( $wpdb->rows( $proposal_table ) ), 'Unaudited policy decision deletes the proposal row.' );

$wpdb = npcink_governance_core_fail_closed_reset_db();
update_option( \Npcink\GovernanceCore\Governance\Approval_Policy_Evaluator::OPTION_POLICY_MODE, \Npcink\GovernanceCore\Governance\Approval_Policy_Evaluator::MODE_LOCAL_GUARDED, false );
\Npcink\GovernanceCore\Security\Request_Context::set_app(
	array(
		'app_id'       => 'app_auto',
		'key_id'       => 'key_auto',
		'caller_type'  => 'trusted_adapter',
		'scope'        => 'proposals:create',
		'scopes'       => array( 'proposals:create', 'proposals:approve' ),
		'route_family' => 'proposals_create',
	)
);
$stack    = npcink_governance_core_fail_closed_proposal_stack();
$proposal = $stack['service']->create( npcink_governance_core_fail_closed_cleanup_batch_payload() );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $proposal ), 'Local guarded cleanup proposal is created.' );
npcink_governance_core_fail_closed_assert( 'approved' === (string) ( $proposal['status'] ?? '' ), 'Local guarded cleanup proposal is auto-approved.' );
npcink_governance_core_fail_closed_assert( 'auto_approved' === (string) ( $proposal['policy_decision'] ?? '' ), 'Local guarded cleanup records auto-approved decision.' );
npcink_governance_core_fail_closed_assert( 'trusted_local' === (string) ( $proposal['policy_profile'] ?? '' ), 'Local guarded cleanup records trusted_local profile.' );
$auto_approval_events = array_filter(
	$wpdb->rows( $audit_table ),
	static function ( array $row ): bool {
		return 'proposal.auto_approved' === (string) ( $row['event_name'] ?? '' );
	}
);
npcink_governance_core_fail_closed_assert( 1 === count( $auto_approval_events ), 'Local guarded cleanup writes proposal.auto_approved audit.' );

$wpdb = npcink_governance_core_fail_closed_reset_db();
update_option( \Npcink\GovernanceCore\Governance\Approval_Policy_Evaluator::OPTION_POLICY_MODE, \Npcink\GovernanceCore\Governance\Approval_Policy_Evaluator::MODE_LOCAL_GUARDED, false );
\Npcink\GovernanceCore\Security\Request_Context::set_app(
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
$stack  = npcink_governance_core_fail_closed_proposal_stack();
$result = $stack['service']->create( npcink_governance_core_fail_closed_cleanup_batch_payload() );
npcink_governance_core_fail_closed_assert( is_wp_error( $result ), 'Auto approval audit failure returns WP_Error.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_auto_approval_audit_failed' === $result->get_error_code(), 'Auto approval audit failure uses stable error code.' );
$proposal_rows = $wpdb->rows( $proposal_table );
npcink_governance_core_fail_closed_assert( 1 === count( $proposal_rows ), 'Auto approval audit failure keeps the audited proposal row.' );
npcink_governance_core_fail_closed_assert( 'pending' === (string) $proposal_rows[0]['status'], 'Auto approval audit failure leaves proposal pending, not approved.' );

$wpdb = npcink_governance_core_fail_closed_reset_db();
$stack    = npcink_governance_core_fail_closed_proposal_stack();
$proposal = $stack['service']->create( npcink_governance_core_fail_closed_payload() );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $proposal ), 'Control proposal is created before decision failure injection.' );
$wpdb->fail_insert_tables[] = $audit_table;
$result = $stack['service']->approve( (string) $proposal['proposal_id'], array( 'reason' => 'approve' ) );
npcink_governance_core_fail_closed_assert( is_wp_error( $result ), 'Approve audit failure returns WP_Error.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_proposal_decision_audit_failed' === $result->get_error_code(), 'Approve audit failure uses stable error code.' );
$rolled_back = $stack['proposals']->find( (string) $proposal['proposal_id'] );
npcink_governance_core_fail_closed_assert( is_array( $rolled_back ) && 'pending' === $rolled_back['status'], 'Approve audit failure rolls status back to pending.' );

$wpdb = npcink_governance_core_fail_closed_reset_db();
$stack    = npcink_governance_core_fail_closed_proposal_stack();
$proposal = $stack['service']->create( npcink_governance_core_fail_closed_payload() );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $proposal ), 'Control proposal is created before reject failure injection.' );
$wpdb->fail_insert_tables[] = $audit_table;
$result = $stack['service']->reject( (string) $proposal['proposal_id'], array( 'reason' => 'reject' ) );
npcink_governance_core_fail_closed_assert( is_wp_error( $result ), 'Reject audit failure returns WP_Error.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_proposal_decision_audit_failed' === $result->get_error_code(), 'Reject audit failure uses stable error code.' );
$rolled_back = $stack['proposals']->find( (string) $proposal['proposal_id'] );
npcink_governance_core_fail_closed_assert( is_array( $rolled_back ) && 'pending' === $rolled_back['status'], 'Reject audit failure rolls status back to pending.' );

$representative_ability_ids = array(
	'npcink-abilities-toolkit/create-draft',
	'npcink-abilities-toolkit/update-post',
	'npcink-abilities-toolkit/set-post-terms',
	'npcink-abilities-toolkit/approve-comment',
	'npcink-abilities-toolkit/trash-post',
);

foreach ( $representative_ability_ids as $ability_id ) {
	$wpdb     = npcink_governance_core_fail_closed_reset_db();
	$stack    = npcink_governance_core_fail_closed_governance_stack();
	$proposal = $stack['service']->create( npcink_governance_core_fail_closed_governance_payload( $ability_id ) );
	npcink_governance_core_fail_closed_assert( ! is_wp_error( $proposal ), $ability_id . ' governance proposal is created.' );

	$pending_preflight = $stack['preflight']->preflight( (string) $proposal['proposal_id'] );
	npcink_governance_core_fail_closed_assert( is_wp_error( $pending_preflight ), $ability_id . ' pending proposal fails commit preflight.' );
	npcink_governance_core_fail_closed_assert( 'npcink_governance_core_proposal_not_approved' === $pending_preflight->get_error_code(), $ability_id . ' pending preflight uses stable error code.' );
	npcink_governance_core_fail_closed_assert( 1 === count( npcink_governance_core_fail_closed_audit_rows( (string) $proposal['proposal_id'], 'commit.preflight_failed' ) ), $ability_id . ' pending preflight failure is audited.' );

	$approved = $stack['service']->approve( (string) $proposal['proposal_id'], array( 'reason' => 'negative_smoke_approval' ) );
	npcink_governance_core_fail_closed_assert( ! is_wp_error( $approved ) && 'approved' === (string) ( $approved['status'] ?? '' ), $ability_id . ' proposal can be approved.' );

	$preflight = $stack['preflight']->preflight( (string) $proposal['proposal_id'] );
	npcink_governance_core_fail_closed_assert( ! is_wp_error( $preflight ), $ability_id . ' approved proposal passes commit preflight.' );
	npcink_governance_core_fail_closed_assert( false === (bool) ( $preflight['commit_execution'] ?? true ), $ability_id . ' preflight does not execute commits.' );
	npcink_governance_core_fail_closed_assert( true === (bool) ( $preflight['idempotency_required'] ?? false ), $ability_id . ' preflight requires idempotency.' );
	npcink_governance_core_fail_closed_assert( true === (bool) ( $preflight['contract_preflight']['contract_matches'] ?? false ), $ability_id . ' preflight confirms ability contract match.' );
	npcink_governance_core_fail_closed_assert( true === (bool) ( $preflight['permission_preflight']['allowed'] ?? false ), $ability_id . ' preflight confirms permission.' );
	npcink_governance_core_fail_closed_assert( 1 === count( npcink_governance_core_fail_closed_audit_rows( (string) $proposal['proposal_id'], 'proposal.created' ) ), $ability_id . ' proposal creation is audited.' );
	npcink_governance_core_fail_closed_assert( 1 === count( npcink_governance_core_fail_closed_audit_rows( (string) $proposal['proposal_id'], 'proposal.policy_evaluated' ) ), $ability_id . ' policy evaluation is audited.' );
	npcink_governance_core_fail_closed_assert( 1 === count( npcink_governance_core_fail_closed_audit_rows( (string) $proposal['proposal_id'], 'proposal.approved' ) ), $ability_id . ' approval is audited.' );
	npcink_governance_core_fail_closed_assert( 1 === count( npcink_governance_core_fail_closed_audit_rows( (string) $proposal['proposal_id'], 'commit.preflighted' ) ), $ability_id . ' successful preflight is audited.' );

	$replay = $stack['preflight']->preflight( (string) $proposal['proposal_id'] );
	npcink_governance_core_fail_closed_assert( is_wp_error( $replay ), $ability_id . ' duplicate commit preflight is rejected.' );
	npcink_governance_core_fail_closed_assert( 'npcink_governance_core_commit_preflight_already_issued' === $replay->get_error_code(), $ability_id . ' duplicate preflight uses stable error code.' );
}

$wpdb     = npcink_governance_core_fail_closed_reset_db();
$stack    = npcink_governance_core_fail_closed_governance_stack();
$proposal = $stack['service']->create( npcink_governance_core_fail_closed_governance_payload( 'npcink-abilities-toolkit/update-post' ) );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $proposal ), 'Contract drift proposal is created.' );
$approved = $stack['service']->approve( (string) $proposal['proposal_id'], array( 'reason' => 'contract_drift' ) );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $approved ), 'Contract drift proposal is approved.' );
$npcink_governance_core_fail_closed_abilities = npcink_abilities_toolkit_get_registered();
$npcink_governance_core_fail_closed_abilities['npcink-abilities-toolkit/update-post']['input_schema']['properties']['unexpected_new_required_control'] = array( 'type' => 'string' );
$drift = $stack['preflight']->preflight( (string) $proposal['proposal_id'] );
npcink_governance_core_fail_closed_assert( is_wp_error( $drift ), 'Changed ability contract fails commit preflight.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_ability_contract_changed' === $drift->get_error_code(), 'Changed ability contract uses stable error code.' );
npcink_governance_core_fail_closed_assert( 1 === count( npcink_governance_core_fail_closed_audit_rows( (string) $proposal['proposal_id'], 'commit.preflight_failed' ) ), 'Contract drift preflight failure is audited.' );

$wpdb     = npcink_governance_core_fail_closed_reset_db();
$stack    = npcink_governance_core_fail_closed_governance_stack();
$proposal = $stack['service']->create( npcink_governance_core_fail_closed_governance_payload( 'npcink-abilities-toolkit/set-post-terms' ) );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $proposal ), 'Permission downgrade proposal is created.' );
$approved = $stack['service']->approve( (string) $proposal['proposal_id'], array( 'reason' => 'permission_downgrade' ) );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $approved ), 'Permission downgrade proposal is approved.' );
$npcink_governance_core_fail_closed_caps['edit_posts'] = false;
$permission_denied = $stack['preflight']->preflight( (string) $proposal['proposal_id'] );
npcink_governance_core_fail_closed_assert( is_wp_error( $permission_denied ), 'Missing WordPress capability fails commit preflight.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_ability_permission_denied' === $permission_denied->get_error_code(), 'Missing WordPress capability uses stable error code.' );
npcink_governance_core_fail_closed_assert( 1 === count( npcink_governance_core_fail_closed_audit_rows( (string) $proposal['proposal_id'], 'commit.preflight_failed' ) ), 'Permission preflight failure is audited.' );

$wpdb     = npcink_governance_core_fail_closed_reset_db();
$stack    = npcink_governance_core_fail_closed_governance_stack();
$proposal = $stack['service']->create( npcink_governance_core_fail_closed_governance_payload( 'npcink-abilities-toolkit/approve-comment' ) );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $proposal ), 'Unavailable ability proposal is created.' );
$approved = $stack['service']->approve( (string) $proposal['proposal_id'], array( 'reason' => 'ability_unavailable' ) );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $approved ), 'Unavailable ability proposal is approved.' );
$npcink_governance_core_fail_closed_abilities = array();
$unavailable = $stack['preflight']->preflight( (string) $proposal['proposal_id'] );
npcink_governance_core_fail_closed_assert( is_wp_error( $unavailable ), 'Unavailable ability fails commit preflight.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_ability_unavailable' === $unavailable->get_error_code(), 'Unavailable ability uses stable error code.' );
npcink_governance_core_fail_closed_assert( 1 === count( npcink_governance_core_fail_closed_audit_rows( (string) $proposal['proposal_id'], 'commit.preflight_failed' ) ), 'Unavailable ability preflight failure is audited.' );

$wpdb     = npcink_governance_core_fail_closed_reset_db();
$stack    = npcink_governance_core_fail_closed_governance_stack();
$proposal = $stack['service']->create(
	array_merge(
		npcink_governance_core_fail_closed_governance_payload( 'npcink-abilities-toolkit/trash-post' ),
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
npcink_governance_core_fail_closed_assert( ! is_wp_error( $proposal ), 'Blocked destructive proposal is created.' );
$approved = $stack['service']->approve( (string) $proposal['proposal_id'], array( 'reason' => 'blocked_preview' ) );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $approved ), 'Blocked destructive proposal is approved.' );
$blocked = $stack['preflight']->preflight( (string) $proposal['proposal_id'] );
npcink_governance_core_fail_closed_assert( is_wp_error( $blocked ), 'Blocked item fails commit preflight.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_proposal_items_blocked' === $blocked->get_error_code(), 'Blocked item uses stable error code.' );
npcink_governance_core_fail_closed_assert( 1 === count( npcink_governance_core_fail_closed_audit_rows( (string) $proposal['proposal_id'], 'commit.preflight_failed' ) ), 'Blocked item preflight failure is audited.' );

$wpdb = npcink_governance_core_fail_closed_reset_db();
$wpdb->fail_insert_tables[] = $app_table;
$apps   = new \Npcink\GovernanceCore\Security\App_Key_Repository();
$result = $apps->create( array( 'app_label' => 'Adapter Client' ) );
npcink_governance_core_fail_closed_assert( is_wp_error( $result ), 'App-key insert failure returns WP_Error.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_app_insert_failed' === $result->get_error_code(), 'App-key insert failure uses stable error code.' );
npcink_governance_core_fail_closed_assert( 0 === count( $wpdb->rows( $app_table ) ), 'App-key insert failure stores no app row.' );

$wpdb = npcink_governance_core_fail_closed_reset_db();
$wpdb->fail_insert_tables[] = $audit_table;
$apps        = new \Npcink\GovernanceCore\Security\App_Key_Repository();
$audit       = new \Npcink\GovernanceCore\Audit\Audit_Log_Repository();
$rate_limiter = new \Npcink\GovernanceCore\Security\App_Rate_Limiter();
$auth        = new \Npcink\GovernanceCore\Security\App_Authenticator( $apps, $rate_limiter, $audit );
$controller  = new \Npcink\GovernanceCore\Rest\Apps_Controller( $apps, $audit, $auth );
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
npcink_governance_core_fail_closed_assert( is_wp_error( $result ), 'App creation audit failure returns WP_Error.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_app_audit_failed' === $result->get_error_code(), 'App creation audit failure uses stable error code.' );
$app_rows = $wpdb->rows( $app_table );
npcink_governance_core_fail_closed_assert( 1 === count( $app_rows ), 'App row is retained for revocation evidence after audit failure.' );
npcink_governance_core_fail_closed_assert( 'revoked' === (string) $app_rows[0]['status'], 'App creation audit failure revokes the new key.' );
npcink_governance_core_fail_closed_assert( ! $result instanceof WP_REST_Response, 'App creation audit failure does not return the one-time token response.' );

echo "Fail-closed fault injection: ok\n";
