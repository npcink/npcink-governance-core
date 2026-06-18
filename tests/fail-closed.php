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

if ( ! function_exists( 'wp_kses_post' ) ) {
	/**
	 * Safe post HTML sanitizer stub.
	 *
	 * @param mixed $value Value.
	 * @return string
	 */
	function wp_kses_post( $value ): string {
		$value = preg_replace( '#<script\b[^>]*>.*?</script>#is', '', (string) $value );
		$value = preg_replace( '#<style\b[^>]*>.*?</style>#is', '', is_string( $value ) ? $value : '' );

		return trim( is_string( $value ) ? $value : '' );
	}
}

if ( ! function_exists( 'esc_url_raw' ) ) {
	/**
	 * URL sanitizer stub.
	 *
	 * @param mixed $value Value.
	 * @return string
	 */
	function esc_url_raw( $value ): string {
		return filter_var( (string) $value, FILTER_VALIDATE_URL ) ? (string) $value : '';
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
			'npcink-abilities-toolkit/patch-post-content' => array(
				'ability_id'        => 'npcink-abilities-toolkit/patch-post-content',
				'label'             => 'Patch Post Content',
				'risk_level'        => 'write',
				'requires_approval' => true,
				'capability'        => 'edit_posts',
				'required_scopes'   => array( 'post.write' ),
				'input_schema'      => array( 'type' => 'object', 'properties' => array( 'post_id' => array( 'type' => array( 'integer', 'string' ) ), 'operations' => array( 'type' => 'array' ), 'dry_run' => array( 'type' => 'boolean' ), 'commit' => array( 'type' => 'boolean' ), 'idempotency_key' => array( 'type' => 'string' ) ) ),
				'output_schema'     => array( 'type' => 'object' ),
			),
			'npcink-abilities-toolkit/update-post-blocks' => array(
				'ability_id'        => 'npcink-abilities-toolkit/update-post-blocks',
				'label'             => 'Update Post Blocks',
				'risk_level'        => 'write',
				'requires_approval' => true,
				'capability'        => 'edit_posts',
				'required_scopes'   => array( 'post.write' ),
				'input_schema'      => array( 'type' => 'object', 'properties' => array( 'post_id' => array( 'type' => array( 'integer', 'string' ) ), 'blocks' => array( 'type' => 'array' ), 'dry_run' => array( 'type' => 'boolean' ), 'commit' => array( 'type' => 'boolean' ), 'idempotency_key' => array( 'type' => 'string' ) ) ),
				'output_schema'     => array( 'type' => 'object' ),
			),
			'npcink-abilities-toolkit/update-template-blocks' => array(
				'ability_id'        => 'npcink-abilities-toolkit/update-template-blocks',
				'label'             => 'Update Template Blocks',
				'risk_level'        => 'high',
				'requires_approval' => true,
				'capability'        => 'edit_theme_options',
				'required_scopes'   => array( 'site.write' ),
				'input_schema'      => array( 'type' => 'object', 'properties' => array( 'post_id' => array( 'type' => 'integer' ), 'blocks' => array( 'type' => 'array' ), 'dry_run' => array( 'type' => 'boolean' ), 'commit' => array( 'type' => 'boolean' ), 'idempotency_key' => array( 'type' => 'string' ) ) ),
				'output_schema'     => array( 'type' => 'object' ),
			),
			'npcink-abilities-toolkit/upsert-template-blocks' => array(
				'ability_id'        => 'npcink-abilities-toolkit/upsert-template-blocks',
				'label'             => 'Upsert Template Blocks',
				'risk_level'        => 'high',
				'requires_approval' => true,
				'capability'        => 'edit_theme_options',
				'required_scopes'   => array( 'site.write' ),
				'input_schema'      => array( 'type' => 'object', 'properties' => array( 'slug' => array( 'type' => 'string' ), 'theme' => array( 'type' => 'string' ), 'blocks' => array( 'type' => 'array' ), 'dry_run' => array( 'type' => 'boolean' ), 'commit' => array( 'type' => 'boolean' ), 'idempotency_key' => array( 'type' => 'string' ) ) ),
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
			'npcink-abilities-toolkit/optimize-media-asset' => array(
				'ability_id'        => 'npcink-abilities-toolkit/optimize-media-asset',
				'label'             => 'Optimize Media Asset',
				'risk_level'        => 'write',
				'requires_approval' => true,
				'capability'        => 'upload_files',
				'required_scopes'   => array( 'media.write' ),
				'input_schema'      => array( 'type' => 'object', 'properties' => array( 'attachment_id' => array( 'type' => array( 'integer', 'string' ) ), 'target_max_width' => array( 'type' => 'integer' ), 'preferred_format' => array( 'type' => 'string' ), 'quality' => array( 'type' => 'integer' ), 'dry_run' => array( 'type' => 'boolean' ), 'commit' => array( 'type' => 'boolean' ), 'idempotency_key' => array( 'type' => 'string' ) ) ),
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
			'npcink-toolbox/build-nightly-inspection-review-plan' => array(
				'ability_id'        => 'npcink-toolbox/build-nightly-inspection-review-plan',
				'label'             => 'Build Nightly Inspection Review Plan',
				'risk_level'        => 'read',
				'requires_approval' => false,
				'capability'        => 'manage_options',
				'required_scopes'   => array( 'cap.toolbox.workflow_suggest' ),
				'input_schema'      => array( 'type' => 'object' ),
				'output_schema'     => array( 'type' => 'object' ),
			),
			'npcink-toolbox/build-content-metadata-apply-plan' => array(
				'ability_id'        => 'npcink-toolbox/build-content-metadata-apply-plan',
				'label'             => 'Build Content Metadata Apply Plan',
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
			'npcink-abilities-toolkit/build-media-adoption-enhancement-plan' => array(
				'ability_id'        => 'npcink-abilities-toolkit/build-media-adoption-enhancement-plan',
				'label'             => 'Build Media Adoption Enhancement Plan',
				'risk_level'        => 'read',
				'requires_approval' => false,
				'capability'        => 'upload_files',
				'required_scopes'   => array( 'media.read', 'post.read' ),
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
			'npcink-abilities-toolkit/build-pattern-page-plan' => array(
				'ability_id'        => 'npcink-abilities-toolkit/build-pattern-page-plan',
				'label'             => 'Build Pattern Page Plan',
				'risk_level'        => 'read',
				'requires_approval' => false,
				'capability'        => 'edit_pages',
				'required_scopes'   => array( 'post.read' ),
				'input_schema'      => array( 'type' => 'object' ),
				'output_schema'     => array( 'type' => 'object' ),
			),
			'npcink-abilities-toolkit/build-block-theme-site-plan' => array(
				'ability_id'        => 'npcink-abilities-toolkit/build-block-theme-site-plan',
				'label'             => 'Build Block Theme Site Plan',
				'risk_level'        => 'read',
				'requires_approval' => false,
				'capability'        => 'edit_theme_options',
				'required_scopes'   => array( 'site.read' ),
				'input_schema'      => array( 'type' => 'object' ),
				'output_schema'     => array( 'type' => 'object' ),
			),
			'npcink-abilities-toolkit/build-article-block-plan' => array(
				'ability_id'        => 'npcink-abilities-toolkit/build-article-block-plan',
				'label'             => 'Build Article Block Plan',
				'risk_level'        => 'read',
				'requires_approval' => false,
				'capability'        => 'edit_posts',
				'required_scopes'   => array( 'post.read' ),
				'input_schema'      => array( 'type' => 'object' ),
				'output_schema'     => array( 'type' => 'object' ),
			),
			'npcink-abilities-toolkit/read-error-log' => array(
				'ability_id'        => 'npcink-abilities-toolkit/read-error-log',
				'label'             => 'Read Error Log',
				'risk_level'        => 'read',
				'requires_approval' => false,
				'capability'        => 'manage_options',
				'required_scopes'   => array( 'logs.read' ),
				'sensitivity'       => 'sensitive',
				'data_classes'      => array( 'logs', 'diagnostics' ),
				'read_authorization' => array(
					'required'       => true,
					'max_rows'       => 50,
					'tail_lines'     => 100,
					'allowed_fields' => array( 'timestamp', 'message', 'severity' ),
					'denied_fields'  => array( 'cookie', 'authorization' ),
				),
				'input_schema'      => array( 'type' => 'object' ),
				'output_schema'     => array( 'type' => 'object' ),
			),
		);
	}
}

/**
 * In-memory WPDB stub with injectable table failures.
 */
final class Npcink_Governance_Core_Fail_Closed_WPDB {
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

		if ( false !== strpos( $sql, 'npcink_governance_core_read_requests' ) && false !== strpos( $sql, 'request_id = %s' ) ) {
			return $this->first_matching( $this->prefix . 'npcink_governance_core_read_requests', array( 'request_id' => (string) ( $args[0] ?? '' ) ) );
		}

		if ( false !== strpos( $sql, 'npcink_governance_core_app_keys' ) && false !== strpos( $sql, 'key_id = %s' ) ) {
			return $this->first_matching( $this->prefix . 'npcink_governance_core_app_keys', array( 'key_id' => (string) ( $args[0] ?? '' ) ) );
		}

		if ( false !== strpos( $sql, 'npcink_governance_core_app_rate_limits' ) && false !== strpos( $sql, 'window_start = %s' ) ) {
			return $this->first_matching(
				$this->prefix . 'npcink_governance_core_app_rate_limits',
				array(
					'app_id'       => (string) ( $args[0] ?? '' ),
					'route_family' => (string) ( $args[1] ?? '' ),
					'window_start' => (string) ( $args[2] ?? '' ),
				)
			);
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

		if ( false !== strpos( $sql, 'npcink_governance_core_read_requests' ) ) {
			return $this->filter_read_request_rows( $sql, $args );
		}

		return array();
	}

	/**
	 * Runs a limited direct query used by rate-limit conditional increments.
	 *
	 * @param mixed $query Query.
	 * @return int|false
	 */
	public function query( $query ) {
		$sql  = is_array( $query ) ? (string) ( $query['query'] ?? '' ) : (string) $query;
		$args = is_array( $query ) ? (array) ( $query['args'] ?? array() ) : array();

		if ( false !== strpos( $sql, 'npcink_governance_core_app_rate_limits' ) && false !== strpos( $sql, 'request_count = request_count + 1' ) ) {
			$table        = $this->prefix . 'npcink_governance_core_app_rate_limits';
			$now          = (string) ( $args[0] ?? '' );
			$app_id       = (string) ( $args[1] ?? '' );
			$route_family = (string) ( $args[2] ?? '' );
			$window_start = (string) ( $args[3] ?? '' );
			$limit        = (int) ( $args[4] ?? 0 );

			foreach ( $this->tables[ $table ] ?? array() as $index => $row ) {
				if (
					$app_id === (string) ( $row['app_id'] ?? '' )
					&& $route_family === (string) ( $row['route_family'] ?? '' )
					&& $window_start === (string) ( $row['window_start'] ?? '' )
					&& (int) ( $row['request_count'] ?? 0 ) < $limit
				) {
					$this->tables[ $table ][ $index ]['request_count'] = (int) ( $row['request_count'] ?? 0 ) + 1;
					$this->tables[ $table ][ $index ]['updated_at']    = $now;
					return 1;
				}
			}

			return 0;
		}

		return 0;
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
	 * Returns filtered read request rows.
	 *
	 * @param string           $sql SQL fragment.
	 * @param array<int,mixed> $args Prepared args.
	 * @return array<int,array<string,mixed>>
	 */
	private function filter_read_request_rows( string $sql, array $args ): array {
		$rows = $this->tables[ $this->prefix . 'npcink_governance_core_read_requests' ] ?? array();

		if ( false !== strpos( $sql, 'status = %s' ) ) {
			$status = (string) ( $args[0] ?? '' );
			$rows   = array_values(
				array_filter(
					$rows,
					static function ( array $row ) use ( $status ): bool {
						return $status === (string) ( $row['status'] ?? '' );
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
require_once dirname( __DIR__ ) . '/includes/Security/Sensitive_Data_Redactor.php';
require_once dirname( __DIR__ ) . '/includes/Observability.php';
require_once dirname( __DIR__ ) . '/includes/Audit/Audit_Log_Repository.php';
require_once dirname( __DIR__ ) . '/includes/Capabilities/Ability_Registry_Adapter.php';
require_once dirname( __DIR__ ) . '/includes/Governance/Approval_Policy_Evaluator.php';
require_once dirname( __DIR__ ) . '/includes/Governance/Proposal_Repository.php';
require_once dirname( __DIR__ ) . '/includes/Governance/Proposal_Service.php';
require_once dirname( __DIR__ ) . '/includes/Governance/Read_Request_Repository.php';
require_once dirname( __DIR__ ) . '/includes/Governance/Read_Request_Service.php';
require_once dirname( __DIR__ ) . '/includes/Governance/Plan_Proposal_Service.php';
require_once dirname( __DIR__ ) . '/includes/Governance/Commit_Preflight_Service.php';
require_once dirname( __DIR__ ) . '/includes/Security/App_Key_Repository.php';
require_once dirname( __DIR__ ) . '/includes/Security/App_Rate_Limiter.php';
require_once dirname( __DIR__ ) . '/includes/Security/App_Authenticator.php';
require_once dirname( __DIR__ ) . '/includes/Rest/Apps_Controller.php';
require_once dirname( __DIR__ ) . '/includes/Rest/Proposals_Controller.php';
require_once dirname( __DIR__ ) . '/includes/Rest/Read_Requests_Controller.php';

/**
 * Resets global storage.
 *
 * @return Npcink_Governance_Core_Fail_Closed_WPDB
 */
function npcink_governance_core_fail_closed_reset_db(): Npcink_Governance_Core_Fail_Closed_WPDB {
	global $wpdb, $npcink_governance_core_fail_closed_options, $npcink_governance_core_fail_closed_transients, $npcink_governance_core_fail_closed_caps, $npcink_governance_core_fail_closed_abilities, $npcink_governance_core_fail_closed_actions;

	$wpdb = new Npcink_Governance_Core_Fail_Closed_WPDB();
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
 * Returns a sensitive read request service stack.
 *
 * @return array{service:\Npcink\GovernanceCore\Governance\Read_Request_Service,requests:\Npcink\GovernanceCore\Governance\Read_Request_Repository,audit:\Npcink\GovernanceCore\Audit\Audit_Log_Repository}
 */
function npcink_governance_core_fail_closed_read_request_stack(): array {
	$requests = new \Npcink\GovernanceCore\Governance\Read_Request_Repository();
	$abilities = new \Npcink\GovernanceCore\Capabilities\Ability_Registry_Adapter();
	$audit     = new \Npcink\GovernanceCore\Audit\Audit_Log_Repository();
	$service   = new \Npcink\GovernanceCore\Governance\Read_Request_Service( $requests, $abilities, $audit );

	return array(
		'service'  => $service,
		'requests' => $requests,
		'audit'    => $audit,
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
 * @return array{service:\Npcink\GovernanceCore\Governance\Plan_Proposal_Service,proposal_service:\Npcink\GovernanceCore\Governance\Proposal_Service,preflight:\Npcink\GovernanceCore\Governance\Commit_Preflight_Service,proposals:\Npcink\GovernanceCore\Governance\Proposal_Repository,audit:\Npcink\GovernanceCore\Audit\Audit_Log_Repository}
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
	$preflight = new \Npcink\GovernanceCore\Governance\Commit_Preflight_Service( $proposals, $abilities, $audit );

	return array(
		'service'          => new \Npcink\GovernanceCore\Governance\Plan_Proposal_Service( $abilities, $proposal_service, $audit ),
		'proposal_service' => $proposal_service,
		'preflight'        => $preflight,
		'proposals'        => $proposals,
		'audit'            => $audit,
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
 * Creates a representative Gutenberg block input.
 *
 * @return array<string,mixed>
 */
function npcink_governance_core_fail_closed_update_post_blocks_input(): array {
	return array(
		'post_id'            => 5791,
		'mode'               => 'replace',
		'validate_roundtrip' => true,
		'dry_run'            => true,
		'commit'             => false,
		'blocks'             => array(
			array(
				'blockName'    => 'core/group',
				'attrs'        => array(
					'layout'     => array(
						'type'        => 'constrained',
						'contentSize' => '1120px',
					),
					'style'      => array(
						'typography' => array(
							'fontSize'      => '18px',
							'letterSpacing' => '0',
							'textTransform' => 'none',
						),
					),
					'className'  => 'wp-ai-section',
					'anchorName' => 'hero',
				),
				'innerBlocks'  => array(
					array(
						'blockName'    => 'core/paragraph',
						'attrs'        => array( 'fontSize' => 'large' ),
						'innerHTML'    => '<p>Reviewed block body<script>alert(1)</script></p>',
						'innerBlocks'  => array(),
						'innerContent' => array( '<p>Reviewed block body<script>alert(1)</script></p>' ),
					),
				),
				'innerHTML'    => '<div class="wp-block-group"><p>Reviewed group</p><script>alert(1)</script></div>',
				'innerContent' => array( '<div class="wp-block-group">', null, '</div>' ),
			),
		),
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
 * Creates a representative media adoption enhancement plan.
 *
 * @return array<string,mixed>
 */
function npcink_governance_core_fail_closed_media_adoption_enhancement_plan(): array {
	return array(
		'artifact_type'            => 'media_adoption_enhancement_plan',
		'version'                  => 1,
		'batch_id'                 => 'media_adoption_enhancement_fault_injection',
		'post_id'                  => 8842,
		'attach_to_post_id'        => 8842,
		'requires_approval'        => true,
		'dry_run'                  => true,
		'commit_execution'         => false,
		'direct_wordpress_write'   => false,
		'proposal_mode'            => 'batch',
		'batch_approval'           => true,
		'action_count'             => 3,
		'media'                    => array(
			'url'               => 'https://images.example.test/generated-dashboard.png',
			'attach_to_post_id' => 8842,
		),
		'reference_repair'         => array(
			'post_id' => 8842,
			'old_url' => 'https://example.test/wp-content/uploads/2026/06/old-dashboard.png',
		),
		'write_actions'            => array(
			array(
				'action_id'         => 'upload-media-asset',
				'target_ability_id' => 'npcink-abilities-toolkit/upload-media-from-url',
				'input'             => array(
					'url'               => 'https://images.example.test/generated-dashboard.png',
					'file_name'         => 'generated-dashboard.png',
					'title'             => 'Generated dashboard',
					'alt'               => 'Generated dashboard preview for WordPress AI',
					'source_type'       => 'ai_generated',
					'attach_to_post_id' => 8842,
					'dry_run'           => true,
					'commit'            => false,
					'idempotency_key'   => 'media-adoption-upload-8842',
				),
				'risk'              => 'medium',
				'requires_approval' => true,
				'commit_execution'  => false,
				'proposal_ready'    => true,
			),
			array(
				'action_id'         => 'optimize-media-asset',
				'target_ability_id' => 'npcink-abilities-toolkit/optimize-media-asset',
				'depends_on'        => array( 'upload-media-asset' ),
				'input'             => array(
					'attachment_id'     => '$outputs.upload-media-asset.attachment_id',
					'target_max_width'  => 1600,
					'preferred_format'  => 'webp',
					'quality'           => 82,
					'derivative_suffix' => '-optimized',
					'dry_run'           => true,
					'commit'            => false,
					'idempotency_key'   => 'media-adoption-optimize-8842',
				),
				'risk'              => 'medium',
				'requires_approval' => true,
				'commit_execution'  => false,
				'proposal_ready'    => true,
			),
			array(
				'action_id'         => 'repair-post-media-reference',
				'target_ability_id' => 'npcink-abilities-toolkit/patch-post-content',
				'depends_on'        => array( 'optimize-media-asset' ),
				'input'             => array(
					'post_id'         => 8842,
					'operations'      => array(
						array(
							'op'      => 'replace',
							'find'    => 'https://example.test/wp-content/uploads/2026/06/old-dashboard.png',
							'replace' => '$outputs.optimize-media-asset.derivative_url',
							'limit'   => 1,
						),
					),
					'dry_run'         => true,
					'commit'          => false,
					'idempotency_key' => 'media-adoption-repair-8842',
				),
				'risk'              => 'medium',
				'requires_approval' => true,
				'commit_execution'  => false,
				'proposal_ready'    => true,
			),
		),
		'risk'                     => array(
			'level'  => 'medium',
			'reason' => 'Imports a reviewed image URL, optimizes it locally, and repairs a reviewed page reference.',
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
 * Creates a representative Gutenberg pattern page plan.
 *
 * @return array<string,mixed>
 */
function npcink_governance_core_fail_closed_pattern_page_plan(): array {
	return array(
		'artifact_type'            => 'pattern_page_plan',
		'version'                  => 1,
		'batch_id'                 => 'pattern_page_fault_injection',
		'pattern_id'               => 'openai-style-landing',
		'style_preset'             => 'minimal-dark-light',
		'requires_approval'        => true,
		'dry_run'                  => true,
		'commit_execution'         => false,
		'direct_wordpress_write'   => false,
		'proposal_mode'            => 'batch',
		'summary'                  => array(
			'block_count'  => 3,
			'action_count' => 2,
		),
		'allowed_classes'          => array(
			'npcink-ai-page',
			'npcink-ai-hero',
			'npcink-ai-title',
			'npcink-ai-lede',
		),
		'write_actions'            => array(
			array(
				'action_id'         => 'create-pattern-page',
				'target_ability_id' => 'npcink-abilities-toolkit/create-draft',
				'input'             => array(
					'post_type'      => 'page',
					'status'         => 'draft',
					'title'          => 'WordPress AI',
					'content_format' => 'html',
					'content'        => '',
					'dry_run'        => true,
					'commit'         => false,
					'idempotency_key' => 'pattern-page-create',
				),
				'risk'              => 'medium',
				'requires_approval' => true,
				'commit_execution'  => false,
				'proposal_ready'    => true,
			),
			array(
				'action_id'         => 'update-pattern-page-blocks',
				'target_ability_id' => 'npcink-abilities-toolkit/update-post-blocks',
				'depends_on'        => array( 'create-pattern-page' ),
				'input'             => array(
					'post_id'            => '$outputs.create-pattern-page.post_id',
					'mode'               => 'replace',
					'validate_roundtrip' => true,
					'blocks'             => array(
						array(
							'blockName'    => 'core/group',
							'attrs'        => array( 'className' => 'npcink-ai-page npcink-ai-hero' ),
							'innerBlocks'  => array(
								array(
									'blockName'    => 'core/heading',
									'attrs'        => array( 'className' => 'npcink-ai-title' ),
									'innerBlocks'  => array(),
									'innerHTML'    => '<h2 class="wp-block-heading npcink-ai-title">WordPress AI</h2>',
									'innerContent' => array( '<h2 class="wp-block-heading npcink-ai-title">WordPress AI</h2>' ),
								),
								array(
									'blockName'    => 'core/paragraph',
									'attrs'        => array( 'className' => 'npcink-ai-lede' ),
									'innerBlocks'  => array(),
									'innerHTML'    => '<p class="npcink-ai-lede">Governed AI workflow for WordPress.</p>',
									'innerContent' => array( '<p class="npcink-ai-lede">Governed AI workflow for WordPress.</p>' ),
								),
							),
							'innerHTML'    => '<div class="wp-block-group npcink-ai-page npcink-ai-hero"></div>',
							'innerContent' => array( null, null ),
						),
					),
					'dry_run'            => true,
					'commit'             => false,
					'idempotency_key'    => 'pattern-page-blocks',
				),
				'risk'              => 'medium',
				'requires_approval' => true,
				'commit_execution'  => false,
				'proposal_ready'    => true,
			),
		),
		'risk'                     => array(
			'level'  => 'medium',
			'reason' => 'Creates a draft page and replaces it with allowlisted Gutenberg pattern blocks.',
		),
	);
}

/**
 * Creates a representative block theme site plan.
 *
 * @return array<string,mixed>
 */
function npcink_governance_core_fail_closed_block_theme_site_plan(): array {
	return array(
		'artifact_type'          => 'block_theme_site_plan',
		'version'                => 1,
		'batch_id'               => 'block_theme_site_fault_injection',
		'intent'                 => 'add_breadcrumbs',
		'active_theme'           => array(
			'name'       => 'Twenty Twenty-Five',
			'stylesheet' => 'twentytwentyfive',
		),
		'affected_templates'     => array( 'single' ),
		'requires_approval'      => true,
		'dry_run'                => true,
		'commit_execution'       => false,
		'direct_wordpress_write' => false,
		'proposal_mode'          => 'batch',
		'summary'                => array(
			'block_count'  => 2,
			'action_count' => 1,
		),
		'preview'                => array(
			array(
				'target_type'               => 'wp_template',
				'post_id'                   => 0,
				'slug'                      => 'single',
				'theme'                     => 'twentytwentyfive',
				'source'                    => 'theme',
				'creates_template_override' => true,
				'target_ability_id'         => 'npcink-abilities-toolkit/upsert-template-blocks',
			),
		),
		'write_actions'          => array(
			array(
				'action_id'         => 'upsert-template-single-breadcrumbs',
				'target_ability_id' => 'npcink-abilities-toolkit/upsert-template-blocks',
				'input'             => array(
					'mode'               => 'replace',
					'validate_roundtrip' => true,
					'slug'               => 'single',
					'theme'              => 'twentytwentyfive',
					'title'              => 'Single',
					'source_template_id' => 'twentytwentyfive//single',
					'blocks'             => array(
						array(
							'blockName'    => 'core/group',
							'attrs'        => array( 'className' => 'openclaw-breadcrumbs' ),
							'innerBlocks'  => array(
								array(
									'blockName'    => 'core/paragraph',
									'attrs'        => array( 'className' => 'openclaw-breadcrumbs__trail' ),
									'innerBlocks'  => array(),
									'innerHTML'    => '<p class="openclaw-breadcrumbs__trail">Home / Current item</p>',
									'innerContent' => array( '<p class="openclaw-breadcrumbs__trail">Home / Current item</p>' ),
								),
							),
							'innerHTML'    => '<div class="wp-block-group openclaw-breadcrumbs"></div>',
							'innerContent' => array( null ),
						),
					),
					'dry_run'            => true,
					'commit'             => false,
					'idempotency_key'    => 'block-theme-site-upsert',
				),
				'risk'              => 'high',
				'requires_approval' => true,
				'commit_execution'  => false,
				'proposal_ready'    => true,
			),
		),
		'risk'                   => array(
			'level'  => 'high',
			'reason' => 'Creates a reviewed Site Editor template override from an active theme file template.',
		),
	);
}

/**
 * Creates a representative block theme template layout plan.
 *
 * @return array<string,mixed>
 */
function npcink_governance_core_fail_closed_block_theme_layout_plan(): array {
	$plan = npcink_governance_core_fail_closed_block_theme_site_plan();
	$plan['batch_id']       = 'block_theme_layout_fault_injection';
	$plan['intent']         = 'customize_template_layout';
	$plan['layout_profile'] = 'article_standard';
	$plan['template_layout_contract'] = array(
		'catalog_id'                => 'gutenberg_native_v1',
		'catalog_version'           => '1.0',
		'compiler_version'          => 'block_theme_profile_compiler@0.3',
		'forbidden_policy_version'  => 'block_theme_safe_core_blocks@0.2',
		'surface'                   => 'template',
		'intent'                    => 'customize_template_layout',
		'placement_model'           => 'bounded_template_layout_profile',
		'accepted_profiles'         => array( 'article_standard', 'page_standard', 'homepage_landing' ),
		'accepted_profile_versions' => array( 'article_standard@0.4', 'page_standard@0.2', 'homepage_landing@0.3' ),
		'forbidden_outputs'         => array( 'raw_template_html', 'core/html', 'core/freeform', 'non_core_blocks', 'custom_css', 'theme_json', 'global_styles', 'navigation_write', 'template_part_write' ),
		'contract_status'           => 'pass',
		'violation_codes'           => array(),
		'profiles'                  => array(
			array(
				'slug'              => 'single',
				'layout_profile'    => 'article_standard',
				'profile_id'        => 'article_standard@0.4',
				'profile_version'   => 'article_standard@0.4',
				'compiler_version'  => 'block_theme_profile_compiler@0.3',
				'operation'         => 'replace_template_layout_with_preserved_template_parts',
				'profile_allowed'   => true,
				'modules'           => array( 'header', 'breadcrumbs', 'post_title', 'author_date', 'post_categories', 'featured_image', 'post_content', 'post_tags', 'post_navigation', 'comments', 'related_posts', 'footer' ),
				'sections'          => array( 'header', 'breadcrumbs', 'post_title', 'author_date', 'post_categories', 'featured_image', 'post_content', 'post_tags', 'post_navigation', 'comments', 'related_posts', 'footer' ),
				'allowed_blocks'    => array( 'core/template-part', 'core/group', 'core/heading', 'core/paragraph', 'core/post-title', 'core/post-author-name', 'core/post-date', 'core/post-featured-image', 'core/post-terms', 'core/post-navigation-link', 'core/comments', 'core/post-content', 'core/latest-posts' ),
				'forbidden_outputs' => array( 'raw_template_html', 'core/html', 'core/freeform', 'non_core_blocks', 'custom_css', 'theme_json', 'global_styles', 'navigation_write', 'template_part_write' ),
			),
		),
	);
	$plan['preview'][0]['layout_profile'] = 'article_standard';
	$plan['preview'][0]['layout_sections'] = array( 'header', 'breadcrumbs', 'post_title', 'author_date', 'featured_image', 'post_content', 'related_posts', 'footer' );
	$plan['write_actions'][0]['action_id'] = 'upsert-template-single-layout';
	$plan['write_actions'][0]['input']['blocks'] = array(
		array(
			'blockName'    => 'core/template-part',
			'attrs'        => array( 'slug' => 'header', 'theme' => 'twentytwentyfive' ),
			'innerBlocks'  => array(),
			'innerHTML'    => '',
			'innerContent' => array(),
		),
		array(
			'blockName'    => 'core/group',
			'attrs'        => array( 'tagName' => 'main', 'className' => 'openclaw-template-layout openclaw-template-layout-article_standard' ),
			'innerBlocks'  => array(
				array(
					'blockName'    => 'core/post-title',
					'attrs'        => array( 'level' => 1 ),
					'innerBlocks'  => array(),
					'innerHTML'    => '',
					'innerContent' => array(),
				),
				array(
					'blockName'    => 'core/post-content',
					'attrs'        => array(),
					'innerBlocks'  => array(),
					'innerHTML'    => '',
					'innerContent' => array(),
				),
			),
			'innerHTML'    => '<main class="wp-block-group openclaw-template-layout openclaw-template-layout-article_standard"></main>',
			'innerContent' => array( '<main class="wp-block-group openclaw-template-layout openclaw-template-layout-article_standard">', null, null, '</main>' ),
		),
		array(
			'blockName'    => 'core/template-part',
			'attrs'        => array( 'slug' => 'footer', 'theme' => 'twentytwentyfive' ),
			'innerBlocks'  => array(),
			'innerHTML'    => '',
			'innerContent' => array(),
		),
	);

	return $plan;
}

/**
 * Creates a representative homepage block theme template layout plan.
 *
 * @return array<string,mixed>
 */
function npcink_governance_core_fail_closed_block_theme_homepage_layout_plan(): array {
	$plan = npcink_governance_core_fail_closed_block_theme_layout_plan();
	$plan['batch_id']       = 'block_theme_homepage_layout_fault_injection';
	$plan['layout_profile'] = 'homepage_landing';
	$plan['affected_templates'] = array( 'front-page' );
	$plan['template_layout_contract']['profiles'][0] = array(
		'slug'              => 'front-page',
		'layout_profile'    => 'homepage_landing',
		'profile_id'        => 'homepage_landing@0.3',
		'profile_version'   => 'homepage_landing@0.3',
		'compiler_version'  => 'block_theme_profile_compiler@0.3',
		'operation'         => 'replace_template_layout_with_preserved_template_parts',
		'profile_allowed'   => true,
		'modules'           => array( 'header', 'hero', 'entry_columns', 'primary_cta', 'latest_posts', 'category_links', 'final_cta', 'footer' ),
		'sections'          => array( 'header', 'hero', 'entry_columns', 'primary_cta', 'latest_posts', 'category_links', 'final_cta', 'footer' ),
		'allowed_blocks'    => array( 'core/template-part', 'core/group', 'core/heading', 'core/paragraph', 'core/buttons', 'core/button', 'core/columns', 'core/column', 'core/latest-posts', 'core/categories', 'core/separator', 'core/spacer' ),
		'forbidden_outputs' => array( 'raw_template_html', 'core/html', 'core/freeform', 'non_core_blocks', 'custom_css', 'theme_json', 'global_styles', 'navigation_write', 'template_part_write' ),
	);
	$plan['preview'][0]['slug'] = 'front-page';
	$plan['preview'][0]['layout_profile'] = 'homepage_landing';
	$plan['preview'][0]['layout_sections'] = array( 'header', 'hero', 'latest_posts', 'category_links', 'footer' );
	$plan['write_actions'][0]['action_id'] = 'upsert-template-front-page-layout';
	$plan['write_actions'][0]['input']['slug'] = 'front-page';
	$plan['write_actions'][0]['input']['title'] = 'Front Page';
	$plan['write_actions'][0]['input']['source_template_id'] = 'twentytwentyfive//front-page';
	$plan['write_actions'][0]['input']['blocks'] = array(
		array(
			'blockName'    => 'core/template-part',
			'attrs'        => array( 'slug' => 'header', 'theme' => 'twentytwentyfive' ),
			'innerBlocks'  => array(),
			'innerHTML'    => '',
			'innerContent' => array(),
		),
		array(
			'blockName'    => 'core/group',
			'attrs'        => array( 'tagName' => 'main', 'className' => 'openclaw-template-layout openclaw-template-layout-homepage_landing' ),
			'innerBlocks'  => array(
				array(
					'blockName'    => 'core/group',
					'attrs'        => array( 'className' => 'openclaw-homepage-hero' ),
					'innerBlocks'  => array(
						array(
							'blockName'    => 'core/heading',
							'attrs'        => array( 'level' => 1 ),
							'innerBlocks'  => array(),
							'innerHTML'    => '<h1>Welcome</h1>',
							'innerContent' => array( '<h1>Welcome</h1>' ),
						),
						array(
							'blockName'    => 'core/paragraph',
							'attrs'        => array(),
							'innerBlocks'  => array(),
							'innerHTML'    => '<p>Homepage introduction.</p>',
							'innerContent' => array( '<p>Homepage introduction.</p>' ),
						),
						array(
							'blockName'    => 'core/buttons',
							'attrs'        => array(),
							'innerBlocks'  => array(
								array(
									'blockName'    => 'core/button',
									'attrs'        => array( 'url' => 'https://example.test/blog' ),
									'innerBlocks'  => array(),
									'innerHTML'    => '<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="https://example.test/blog">Read Blog</a></div>',
									'innerContent' => array( '<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="https://example.test/blog">Read Blog</a></div>' ),
								),
							),
							'innerHTML'    => '<div class="wp-block-buttons"></div>',
							'innerContent' => array( '<div class="wp-block-buttons">', null, '</div>' ),
						),
					),
					'innerHTML'    => '<div class="wp-block-group openclaw-homepage-hero"></div>',
					'innerContent' => array( '<div class="wp-block-group openclaw-homepage-hero">', null, null, null, '</div>' ),
				),
				array(
					'blockName'    => 'core/latest-posts',
					'attrs'        => array( 'postsToShow' => 6 ),
					'innerBlocks'  => array(),
					'innerHTML'    => '',
					'innerContent' => array(),
				),
				array(
					'blockName'    => 'core/categories',
					'attrs'        => array( 'showPostCounts' => true ),
					'innerBlocks'  => array(),
					'innerHTML'    => '',
					'innerContent' => array(),
				),
			),
			'innerHTML'    => '<main class="wp-block-group openclaw-template-layout openclaw-template-layout-homepage_landing"></main>',
			'innerContent' => array( '<main class="wp-block-group openclaw-template-layout openclaw-template-layout-homepage_landing">', null, null, null, '</main>' ),
		),
		array(
			'blockName'    => 'core/template-part',
			'attrs'        => array( 'slug' => 'footer', 'theme' => 'twentytwentyfive' ),
			'innerBlocks'  => array(),
			'innerHTML'    => '',
			'innerContent' => array(),
		),
	);

	return $plan;
}

/**
 * Creates a representative Gutenberg article block plan.
 *
 * @return array<string,mixed>
 */
function npcink_governance_core_fail_closed_article_block_plan(): array {
	return array(
		'artifact_type'          => 'article_block_plan',
		'version'                => 1,
		'batch_id'               => 'article_block_fault_injection',
		'article_template'       => 'comparison-review',
		'responsive_profile'     => 'article_standard',
		'media_strategy'         => 'existing_media_url',
		'requires_approval'      => true,
		'dry_run'                => true,
		'commit_execution'       => false,
		'direct_wordpress_write' => false,
		'proposal_mode'          => 'batch',
		'summary'                => array(
			'block_count'  => 5,
			'action_count' => 2,
		),
		'editorial_quality'      => array(
			'pattern_version'         => '1.0',
			'uses_native_blocks'      => true,
			'has_takeaways'           => true,
			'has_faq'                 => true,
			'has_comparison_columns'  => true,
			'custom_css_required'     => false,
		),
		'responsive_quality'     => array(
			'responsive_profile'          => 'article_standard',
			'uses_core_responsive_blocks' => true,
			'uses_mobile_stack'           => true,
			'has_responsive_media'        => true,
			'max_columns_per_row'         => 2,
			'custom_css_required'         => false,
		),
		'write_actions'          => array(
			array(
				'action_id'         => 'create-article-draft',
				'target_ability_id' => 'npcink-abilities-toolkit/create-draft',
				'input'             => array(
					'post_type'      => 'post',
					'status'         => 'draft',
					'title'          => 'Gutenberg Article Draft',
					'content_format' => 'html',
					'content'        => '',
					'dry_run'        => true,
					'commit'         => false,
					'idempotency_key' => 'article-block-create',
				),
				'risk'              => 'medium',
				'requires_approval' => true,
				'commit_execution'  => false,
				'proposal_ready'    => true,
			),
			array(
				'action_id'         => 'update-article-blocks',
				'target_ability_id' => 'npcink-abilities-toolkit/update-post-blocks',
				'depends_on'        => array( 'create-article-draft' ),
				'input'             => array(
					'post_id'            => '$outputs.create-article-draft.post_id',
					'mode'               => 'replace',
					'validate_roundtrip' => true,
					'blocks'             => array(
						array(
							'blockName'    => 'core/paragraph',
							'attrs'        => array(),
							'innerBlocks'  => array(),
							'innerHTML'    => '<p>Article intro.</p>',
							'innerContent' => array( '<p>Article intro.</p>' ),
						),
						array(
							'blockName'    => 'core/image',
							'attrs'        => array(
								'url'             => 'https://example.test/wp-content/uploads/article.jpg',
								'alt'             => 'Article image',
								'sizeSlug'        => 'large',
								'linkDestination' => 'none',
							),
							'innerBlocks'  => array(),
							'innerHTML'    => '<figure class="wp-block-image size-large"><img src="https://example.test/wp-content/uploads/article.jpg" alt="Article image"/></figure>',
							'innerContent' => array( '<figure class="wp-block-image size-large"><img src="https://example.test/wp-content/uploads/article.jpg" alt="Article image"/></figure>' ),
						),
						array(
							'blockName'    => 'core/columns',
							'attrs'        => array( 'isStackedOnMobile' => true ),
							'innerBlocks'  => array(),
							'innerHTML'    => '<div class="wp-block-columns"></div>',
							'innerContent' => array( '<div class="wp-block-columns"></div>' ),
						),
						array(
							'blockName'    => 'core/details',
							'attrs'        => array( 'summary' => 'Can editors continue editing?' ),
							'innerBlocks'  => array(
								array(
									'blockName'    => 'core/paragraph',
									'attrs'        => array(),
									'innerBlocks'  => array(),
									'innerHTML'    => '<p>Yes.</p>',
									'innerContent' => array( '<p>Yes.</p>' ),
								),
							),
							'innerHTML'    => '<details class="wp-block-details"><summary>Can editors continue editing?</summary></details>',
							'innerContent' => array( '<details class="wp-block-details"><summary>Can editors continue editing?</summary>', null, '</details>' ),
						),
					),
					'dry_run'            => true,
					'commit'             => false,
					'idempotency_key'    => 'article-blocks',
				),
				'risk'              => 'medium',
				'requires_approval' => true,
				'commit_execution'  => false,
				'proposal_ready'    => true,
			),
		),
		'risk'                   => array(
			'level'  => 'medium',
			'reason' => 'Creates a draft post and replaces it with Gutenberg-native editorial blocks.',
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

/**
 * Creates a representative Nightly Site Inspection Core review plan.
 *
 * @return array<string,mixed>
 */
function npcink_governance_core_fail_closed_nightly_inspection_review_plan(): array {
	return array(
		'artifact_type'          => 'nightly_site_inspection_review_plan',
		'contract_version'       => 'nightly_site_inspection_core_review_plan.v1',
		'version'                => 1,
		'batch_id'               => 'run_nightly_inspection_fault_injection',
		'cloud_run_id'           => 'run_nightly_inspection_fault_injection',
		'requires_approval'      => true,
		'dry_run'                => true,
		'commit_execution'       => false,
		'proposal_mode'          => 'single',
		'write_posture'          => 'core_proposal_handoff',
		'direct_wordpress_write' => false,
		'runtime_owner'          => 'npcink-local-automation-runtime',
		'agent_id'               => 'nightly_site_inspection_cloud_runtime',
		'agent_version'          => 'nightly_site_inspection_cloud_runtime.v1',
		'workflow'               => 'nightly_site_inspection',
		'intent'                 => 'morning_review_preparation',
		'cloud_output'           => 'proposal_candidate',
		'local_next_action'      => 'operator_review',
		'evidence_gate_status'   => 'passed',
		'evidence_refs'          => array(
			array(
				'action_id'     => 'action_001',
				'title'         => 'Short title',
				'post_id'       => 123,
				'source_type'   => 'post',
				'score'         => 55,
				'severity'      => 'critical',
				'reason_codes'  => array( 'missing_meta_description', 'missing_internal_links' ),
				'suggested_use' => 'morning_brief_review_evidence',
			),
		),
		'blocked_outputs'        => array(
			'direct_wordpress_write',
			'article_body',
			'article_write_plan',
			'final_seo_copy',
		),
		'issue_types'            => array( 'nightly_site_inspection' ),
		'risk'                   => array(
			'level'  => 'medium',
			'reason' => 'review_required',
		),
		'preview'                => array(
			array(
				'action_id'          => 'review_nightly_site_inspection',
				'proposal_ready'     => false,
				'evidence_ref_count' => 1,
			),
		),
		'write_actions'          => array(
			array(
				'action_id'         => 'review_nightly_site_inspection',
				'target_ability_id' => 'npcink-abilities-toolkit/create-draft',
				'input'             => array(
					'title'           => '',
					'content'         => '',
					'status'          => 'draft',
					'dry_run'         => true,
					'commit'          => false,
					'idempotency_key' => 'nightly-inspection-review-fixture',
				),
				'risk'              => 'medium',
				'requires_approval' => true,
				'commit_execution'  => false,
				'proposal_ready'    => false,
				'requires_input'    => array( 'title', 'content' ),
				'reason'            => 'Morning Brief found reviewable content quality signals.',
			),
		),
	);
}

/**
 * Creates a representative content metadata apply plan.
 *
 * @return array<string,mixed>
 */
function npcink_governance_core_fail_closed_content_metadata_apply_plan(): array {
	return array(
		'artifact_type'          => 'content_metadata_apply_plan',
		'composition_role'       => 'core_content_metadata_apply_plan',
		'version'                => 1,
		'batch_id'               => 'content_metadata_apply_fault_injection',
		'requires_approval'      => true,
		'dry_run'                => true,
		'commit_execution'       => false,
		'proposal_mode'          => 'batch',
		'batch_approval'         => true,
		'write_posture'          => 'core_proposal_handoff',
		'direct_wordpress_write' => false,
		'post'                   => array(
			'post_id'     => 1493,
			'post_type'   => 'post',
			'post_status' => 'draft',
			'title'       => 'Metadata review target',
		),
		'accepted_choices'       => array(
			'excerpt_selected' => true,
			'category_ids'     => array( 31 ),
			'tag_ids'          => array( 41, 42 ),
			'new_term_policy'  => 'manual_review_only_no_create_term_action',
		),
		'authorization'          => array(
			'classification'    => 'core_proposal_required',
			'reason'            => 'content_metadata_apply_plan_uses_core_review',
			'decision_envelope' => array(
				'decision_version'  => 'operation-classification-v1',
				'classification'    => 'core_proposal_required',
				'reasons'           => array( 'reviewed_metadata_apply_plan' ),
				'risk_factors'      => array( 'batch_or_multi_action_metadata_apply' ),
				'required_evidence' => array( 'target_ability_id', 'before_after_or_dry_run_evidence' ),
				'request_source'    => 'external_adapter',
				'actor_presence'   => 'delegated',
				'preview_completeness' => 'sufficient',
				'scope'             => 'one_object',
				'reversibility'     => 'easy_undo',
				'operation_kind'    => 'batch_plan',
				'writes_wordpress_state' => true,
			),
		),
		'evidence_refs'          => array(
			array(
				'id'      => 'target-post',
				'type'    => 'target_post',
				'post_id' => 1493,
			),
		),
		'new_term_candidates'    => array(
			array(
				'name'   => 'Review Only Term',
				'status' => 'review_only_vocabulary_gap',
			),
		),
		'preview'                => array(
			array(
				'action_id' => 'content_metadata_apply',
				'post_id'   => 1493,
				'before'    => array(
					'excerpt'      => '',
					'category_ids' => array(),
					'tag_ids'      => array(),
				),
				'after_suggestion' => array(
					'excerpt'       => 'Reviewed metadata excerpt.',
					'category_ids'  => array( 31 ),
					'category_mode' => 'append',
					'tag_ids'       => array( 41, 42 ),
					'tag_mode'      => 'append',
				),
			),
		),
		'manual_review'          => array(
			array(
				'code'   => 'new_term_candidates_not_applied',
				'fields' => array( 'new_term_candidates' ),
			),
		),
		'write_actions'          => array(
			array(
				'action_id'         => 'apply_selected_excerpt',
				'target_ability_id' => 'npcink-abilities-toolkit/update-post',
				'input'             => array(
					'post_id'         => 1493,
					'excerpt'         => 'Reviewed metadata excerpt.',
					'dry_run'         => true,
					'commit'          => false,
					'idempotency_key' => 'metadata-excerpt-fixture',
				),
				'risk'              => 'low',
				'requires_approval' => true,
				'commit_execution'  => false,
				'proposal_ready'    => true,
			),
			array(
				'action_id'         => 'assign_existing_categories',
				'target_ability_id' => 'npcink-abilities-toolkit/set-post-terms',
				'input'             => array(
					'post_id'         => 1493,
					'taxonomy'        => 'category',
					'mode'            => 'append',
					'term_ids'        => array( 31 ),
					'create_missing'  => false,
					'dry_run'         => true,
					'commit'          => false,
					'idempotency_key' => 'metadata-categories-fixture',
				),
				'risk'              => 'medium',
				'requires_approval' => true,
				'commit_execution'  => false,
				'proposal_ready'    => true,
			),
			array(
				'action_id'         => 'assign_existing_tags',
				'target_ability_id' => 'npcink-abilities-toolkit/set-post-terms',
				'input'             => array(
					'post_id'         => 1493,
					'taxonomy'        => 'post_tag',
					'mode'            => 'append',
					'term_ids'        => array( 41, 42 ),
					'create_missing'  => false,
					'dry_run'         => true,
					'commit'          => false,
					'idempotency_key' => 'metadata-tags-fixture',
				),
				'risk'              => 'low',
				'requires_approval' => true,
				'commit_execution'  => false,
				'proposal_ready'    => true,
			),
		),
		'risk'                   => array(
			'level' => 'medium',
		),
	);
}

$proposal_table = 'wp_npcink_governance_core_proposals';
$read_request_table = 'wp_npcink_governance_core_read_requests';
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
npcink_governance_core_fail_closed_assert( false === strpos( (string) wp_json_encode( $created_proposal ), 'CALLER_SECRET_SENTINEL' ), 'Proposal response redacts secret-shaped caller metadata.' );
$create_events    = npcink_governance_core_fail_closed_observability_events( 'core.proposal.create' );
npcink_governance_core_fail_closed_assert( 1 === count( $create_events ), 'Proposal create emits one observability event.' );
npcink_governance_core_fail_closed_assert( 'ok' === (string) ( $create_events[0]['status'] ?? '' ), 'Proposal create emits ok status.' );
npcink_governance_core_fail_closed_assert( $proposal_id === (string) ( $create_events[0]['proposal_id'] ?? '' ), 'Proposal create event includes proposal id.' );
npcink_governance_core_fail_closed_assert( 'npcink-abilities-toolkit/create-draft' === (string) ( $create_events[0]['ability_id'] ?? '' ), 'Proposal create event includes ability id.' );
npcink_governance_core_fail_closed_assert_observability_metadata_only( $create_events[0], 'Proposal create observability event' );
npcink_governance_core_fail_closed_assert( false === strpos( (string) wp_json_encode( $wpdb->rows( $audit_table ) ), 'CALLER_SECRET_SENTINEL' ), 'Proposal audit rows redact secret-shaped caller metadata.' );

$redaction_audit = new \Npcink\GovernanceCore\Audit\Audit_Log_Repository();
$redaction_audit->record(
	'security.redaction_smoke',
	array(
		'authorization' => 'Bearer AUDIT_BEARER_SECRET_SENTINEL',
		'nested'        => array(
			'api_key' => 'AUDIT_API_KEY_SECRET_SENTINEL',
		),
		'note'          => 'cookie: AUDIT_COOKIE_SECRET_SENTINEL',
	)
);
$redaction_audit_rows = wp_json_encode( $wpdb->rows( $audit_table ) );
npcink_governance_core_fail_closed_assert( false === strpos( (string) $redaction_audit_rows, 'AUDIT_BEARER_SECRET_SENTINEL' ), 'Audit metadata redacts authorization values.' );
npcink_governance_core_fail_closed_assert( false === strpos( (string) $redaction_audit_rows, 'AUDIT_API_KEY_SECRET_SENTINEL' ), 'Audit metadata redacts secret-shaped nested keys.' );
npcink_governance_core_fail_closed_assert( false === strpos( (string) $redaction_audit_rows, 'AUDIT_COOKIE_SECRET_SENTINEL' ), 'Audit metadata redacts cookie-shaped strings.' );

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
$oversized_payload_plan = npcink_governance_core_fail_closed_article_write_plan();
$oversized_payload_plan['intake_padding'] = str_repeat( 'x', 263000 );
$oversized_payload_result = $stack['service']->create_from_plan( 'npcink-toolbox/build-article-write-plan', $oversized_payload_plan );
npcink_governance_core_fail_closed_assert( is_wp_error( $oversized_payload_result ), 'Oversized plan payload is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_plan_payload_too_large' === $oversized_payload_result->get_error_code(), 'Oversized plan payload rejection uses stable error code.' );
npcink_governance_core_fail_closed_assert( 0 === count( $wpdb->rows( $proposal_table ) ), 'Oversized plan payload stores no proposal row.' );

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
npcink_governance_core_fail_closed_assert( 'core-batch-review-summary-v1' === (string) ( $media_optimization_proposal['preview']['batch_review_summary']['summary_version'] ?? '' ), 'Media optimization proposal stores a stable batch review summary.' );
npcink_governance_core_fail_closed_assert( 2 === (int) ( $media_optimization_proposal['preview']['batch_review_summary']['action_count'] ?? 0 ), 'Media optimization batch review summary counts actions.' );
npcink_governance_core_fail_closed_assert( 'review_and_approve_or_reject' === (string) ( $media_optimization_proposal['preview']['batch_review_summary']['operator_next_action'] ?? '' ), 'Ready media optimization batch tells the operator to review and decide.' );
npcink_governance_core_fail_closed_assert( false === (bool) ( $media_optimization_proposal['preview']['batch_review_summary']['core_execution'] ?? true ), 'Media optimization batch review summary keeps Core execution disabled.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$multi_media_optimization_plan = npcink_governance_core_fail_closed_media_optimization_plan();
$multi_media_optimization_plan['attachment_ids'] = array( 1493, 1494 );
$multi_media_optimization_plan['write_actions'][] = array(
	'action_id'         => 'update_media_details_1494',
	'target_ability_id' => 'npcink-abilities-toolkit/update-media-details',
	'input'             => array(
		'attachment_id'   => 1494,
		'title'           => 'Second optimized AI image',
		'alt'             => 'Second AI generated product image',
		'source_type'     => 'ai_generated',
		'dry_run'         => true,
		'commit'          => false,
		'idempotency_key' => 'media-optimize-metadata-1494',
	),
	'risk'              => 'medium',
	'requires_approval' => true,
	'commit_execution'  => false,
	'proposal_ready'    => true,
);
$multi_media_optimization_plan['write_actions'][] = array(
	'action_id'         => 'adopt_webp_derivative_1494',
	'target_ability_id' => 'npcink-abilities-toolkit/adopt-cloud-media-derivative',
	'input'             => array(
		'attachment_id'                  => 1494,
		'derivative_artifact'            => 'cloud://artifact/webp-1494',
		'expected_current_mime_type'     => 'image/png',
		'expected_derivative_mime_type'  => 'image/webp',
		'dry_run'                        => true,
		'commit'                         => false,
		'idempotency_key'                => 'media-optimize-derivative-1494',
	),
	'risk'              => 'medium',
	'requires_approval' => true,
	'commit_execution'  => false,
	'proposal_ready'    => true,
);
$multi_media_optimization_result = $stack['service']->create_from_plan( 'npcink-abilities-toolkit/build-media-optimization-plan', $multi_media_optimization_plan, array(), array( 'source' => 'toolbox_media_optimization' ) );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $multi_media_optimization_result ), 'Valid multi-attachment media optimization plan creates a Core proposal.' );
npcink_governance_core_fail_closed_assert( 1 === (int) ( $multi_media_optimization_result['proposal_count'] ?? 0 ), 'Valid multi-attachment media optimization plan creates one batch proposal.' );
$multi_media_optimization_proposal = is_array( $multi_media_optimization_result['proposals'][0] ?? null ) ? $multi_media_optimization_result['proposals'][0] : array();
npcink_governance_core_fail_closed_assert( 4 === count( (array) ( $multi_media_optimization_proposal['input']['write_actions'] ?? array() ) ), 'Multi-attachment media optimization proposal stores all metadata and derivative actions.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$too_many_media_actions = npcink_governance_core_fail_closed_media_optimization_plan();
$base_media_actions = $too_many_media_actions['write_actions'];
$too_many_media_actions['attachment_ids'] = array();
$too_many_media_actions['write_actions'] = array();
for ( $i = 0; $i < 6; $i++ ) {
	$attachment_id = 2000 + $i;
	$metadata_action = $base_media_actions[0];
	$derivative_action = $base_media_actions[1];
	$metadata_action['action_id'] = 'update_media_details_' . $attachment_id;
	$metadata_action['input']['attachment_id'] = $attachment_id;
	$metadata_action['input']['idempotency_key'] = 'media-optimize-metadata-' . $attachment_id;
	$derivative_action['action_id'] = 'adopt_webp_derivative_' . $attachment_id;
	$derivative_action['input']['attachment_id'] = $attachment_id;
	$derivative_action['input']['derivative_artifact'] = 'cloud://artifact/webp-' . $attachment_id;
	$derivative_action['input']['idempotency_key'] = 'media-optimize-derivative-' . $attachment_id;
	$too_many_media_actions['attachment_ids'][] = $attachment_id;
	$too_many_media_actions['write_actions'][] = $metadata_action;
	$too_many_media_actions['write_actions'][] = $derivative_action;
}
$too_many_media_actions_result = $stack['service']->create_from_plan( 'npcink-abilities-toolkit/build-media-optimization-plan', $too_many_media_actions );
npcink_governance_core_fail_closed_assert( is_wp_error( $too_many_media_actions_result ), 'Media optimization plan with too many actions is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_media_optimization_actions_rejected' === $too_many_media_actions_result->get_error_code(), 'Media optimization action limit rejection uses stable error code.' );
npcink_governance_core_fail_closed_assert( 0 === count( $wpdb->rows( $proposal_table ) ), 'Oversized media optimization plan stores no proposal row.' );

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
npcink_governance_core_fail_closed_assert( is_wp_error( $media_optimization_mismatch_result ), 'Media optimization plan with unpaired attachment actions is rejected.' );
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
$media_adoption_enhancement_plan = npcink_governance_core_fail_closed_media_adoption_enhancement_plan();
$media_adoption_enhancement_result = $stack['service']->create_from_plan( 'npcink-abilities-toolkit/build-media-adoption-enhancement-plan', $media_adoption_enhancement_plan, array(), array( 'source' => 'abilities_media_adoption_enhancement' ) );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $media_adoption_enhancement_result ), 'Valid media adoption enhancement plan creates a Core proposal.' );
npcink_governance_core_fail_closed_assert( 1 === (int) ( $media_adoption_enhancement_result['proposal_count'] ?? 0 ), 'Valid media adoption enhancement plan creates one batch proposal.' );
$media_adoption_enhancement_proposal = is_array( $media_adoption_enhancement_result['proposals'][0] ?? null ) ? $media_adoption_enhancement_result['proposals'][0] : array();
npcink_governance_core_fail_closed_assert( 'plan_to_proposal_batch' === (string) ( $media_adoption_enhancement_proposal['preview']['source']['type'] ?? '' ), 'Media adoption enhancement plan stores batch proposal source.' );
npcink_governance_core_fail_closed_assert( 3 === count( (array) ( $media_adoption_enhancement_proposal['input']['write_actions'] ?? array() ) ), 'Media adoption enhancement proposal stores upload, optimize, and repair actions.' );
npcink_governance_core_fail_closed_assert( is_array( $media_adoption_enhancement_proposal['preview']['media_adoption_enhancement'] ?? null ), 'Media adoption enhancement proposal preserves enhancement preview.' );
npcink_governance_core_fail_closed_assert( 'https://images.example.test/generated-dashboard.png' === (string) ( $media_adoption_enhancement_proposal['preview']['media_adoption_enhancement']['source_url'] ?? '' ), 'Media adoption enhancement preview preserves nested media source URL.' );
npcink_governance_core_fail_closed_assert( 'https://example.test/wp-content/uploads/2026/06/old-dashboard.png' === (string) ( $media_adoption_enhancement_proposal['preview']['media_adoption_enhancement']['old_url'] ?? '' ), 'Media adoption enhancement preview preserves nested old URL.' );
npcink_governance_core_fail_closed_assert( 3 === (int) ( $media_adoption_enhancement_proposal['preview']['media_adoption_enhancement']['action_count'] ?? 0 ), 'Media adoption enhancement preview preserves top-level action count.' );
npcink_governance_core_fail_closed_assert( '$outputs.upload-media-asset.attachment_id' === (string) ( $media_adoption_enhancement_proposal['input']['write_actions'][1]['input']['attachment_id'] ?? '' ), 'Media adoption enhancement optimize action preserves upload output reference.' );
npcink_governance_core_fail_closed_assert( '$outputs.optimize-media-asset.derivative_url' === (string) ( $media_adoption_enhancement_proposal['input']['write_actions'][2]['input']['operations'][0]['replace'] ?? '' ), 'Media adoption enhancement repair action preserves derivative URL output reference.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$media_adoption_missing_optimize = npcink_governance_core_fail_closed_media_adoption_enhancement_plan();
array_splice( $media_adoption_missing_optimize['write_actions'], 1, 1 );
$media_adoption_missing_optimize_result = $stack['service']->create_from_plan( 'npcink-abilities-toolkit/build-media-adoption-enhancement-plan', $media_adoption_missing_optimize );
npcink_governance_core_fail_closed_assert( is_wp_error( $media_adoption_missing_optimize_result ), 'Media adoption enhancement plan without optimize action is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_media_adoption_enhancement_actions_missing' === $media_adoption_missing_optimize_result->get_error_code(), 'Media adoption enhancement missing optimize rejection uses stable error code.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$media_adoption_bad_repair = npcink_governance_core_fail_closed_media_adoption_enhancement_plan();
$media_adoption_bad_repair['write_actions'][2]['input']['operations'][0]['replace'] = 'https://example.test/wp-content/uploads/2026/06/unreviewed.webp';
$media_adoption_bad_repair_result = $stack['service']->create_from_plan( 'npcink-abilities-toolkit/build-media-adoption-enhancement-plan', $media_adoption_bad_repair );
npcink_governance_core_fail_closed_assert( is_wp_error( $media_adoption_bad_repair_result ), 'Media adoption enhancement plan with literal repair replacement URL is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_media_adoption_enhancement_patch_invalid' === $media_adoption_bad_repair_result->get_error_code(), 'Media adoption enhancement repair validation uses stable error code.' );

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
$pattern_page_plan = npcink_governance_core_fail_closed_pattern_page_plan();
$pattern_page_result = $stack['service']->create_from_plan( 'npcink-abilities-toolkit/build-pattern-page-plan', $pattern_page_plan, array(), array( 'source' => 'abilities_pattern_page' ) );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $pattern_page_result ), 'Valid pattern page plan creates a Core proposal.' );
npcink_governance_core_fail_closed_assert( 1 === (int) ( $pattern_page_result['proposal_count'] ?? 0 ), 'Valid pattern page plan creates one batch proposal.' );
$pattern_page_proposal = is_array( $pattern_page_result['proposals'][0] ?? null ) ? $pattern_page_result['proposals'][0] : array();
npcink_governance_core_fail_closed_assert( 'plan_to_proposal_batch' === (string) ( $pattern_page_proposal['preview']['source']['type'] ?? '' ), 'Pattern page plan stores batch proposal source.' );
npcink_governance_core_fail_closed_assert( is_array( $pattern_page_proposal['preview']['pattern_page'] ?? null ), 'Pattern page proposal preserves pattern page preview.' );
npcink_governance_core_fail_closed_assert( 'openai-style-landing' === (string) ( $pattern_page_proposal['preview']['pattern_page']['pattern_id'] ?? '' ), 'Pattern page preview preserves pattern id.' );
npcink_governance_core_fail_closed_assert( 2 === count( (array) ( $pattern_page_proposal['input']['write_actions'] ?? array() ) ), 'Pattern page proposal stores create and block update actions.' );
npcink_governance_core_fail_closed_assert( '$outputs.create-pattern-page.post_id' === (string) ( $pattern_page_proposal['input']['write_actions'][1]['input']['post_id'] ?? '' ), 'Pattern page update action preserves output reference.' );
$pattern_page_block = $pattern_page_proposal['input']['write_actions'][1]['input']['blocks'][0] ?? array();
npcink_governance_core_fail_closed_assert( is_array( $pattern_page_block ) && isset( $pattern_page_block['blockName'] ) && ! isset( $pattern_page_block['blockname'] ), 'Pattern page proposal preserves Gutenberg blockName key case.' );
npcink_governance_core_fail_closed_assert( isset( $pattern_page_block['innerBlocks'] ) && ! isset( $pattern_page_block['innerblocks'] ), 'Pattern page proposal preserves Gutenberg innerBlocks key case.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$pattern_page_bad_class = npcink_governance_core_fail_closed_pattern_page_plan();
$pattern_page_bad_class['write_actions'][1]['input']['blocks'][0]['attrs']['className'] = 'npcink-ai-page rogue-class';
$pattern_page_bad_class_result = $stack['service']->create_from_plan( 'npcink-abilities-toolkit/build-pattern-page-plan', $pattern_page_bad_class );
npcink_governance_core_fail_closed_assert( is_wp_error( $pattern_page_bad_class_result ), 'Pattern page plan with non-allowlisted block class is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_pattern_page_class_rejected' === $pattern_page_bad_class_result->get_error_code(), 'Pattern page class rejection uses stable error code.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$block_theme_site_plan = npcink_governance_core_fail_closed_block_theme_site_plan();
$block_theme_site_result = $stack['service']->create_from_plan( 'npcink-abilities-toolkit/build-block-theme-site-plan', $block_theme_site_plan, array(), array( 'source' => 'abilities_block_theme_site' ) );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $block_theme_site_result ), 'Valid block theme site plan creates a Core proposal.' );
npcink_governance_core_fail_closed_assert( 1 === (int) ( $block_theme_site_result['proposal_count'] ?? 0 ), 'Valid block theme site plan creates one batch proposal.' );
$block_theme_site_proposal = is_array( $block_theme_site_result['proposals'][0] ?? null ) ? $block_theme_site_result['proposals'][0] : array();
npcink_governance_core_fail_closed_assert( 'plan_to_proposal_batch' === (string) ( $block_theme_site_proposal['preview']['source']['type'] ?? '' ), 'Block theme site plan stores batch proposal source.' );
npcink_governance_core_fail_closed_assert( is_array( $block_theme_site_proposal['preview']['block_theme_site'] ?? null ), 'Block theme site proposal preserves block theme preview.' );
npcink_governance_core_fail_closed_assert( 'create_wp_template_override' === (string) ( $block_theme_site_proposal['preview']['block_theme_site']['file_template_write_mode'] ?? '' ), 'Block theme site preview records file-template override mode.' );
npcink_governance_core_fail_closed_assert( 1 === count( (array) ( $block_theme_site_proposal['input']['write_actions'] ?? array() ) ), 'Block theme site proposal stores the template write action.' );
npcink_governance_core_fail_closed_assert( 'npcink-abilities-toolkit/upsert-template-blocks' === (string) ( $block_theme_site_proposal['input']['write_actions'][0]['target_ability_id'] ?? '' ), 'Block theme site proposal stores template override upsert action.' );
$block_theme_site_block = $block_theme_site_proposal['input']['write_actions'][0]['input']['blocks'][0] ?? array();
npcink_governance_core_fail_closed_assert( is_array( $block_theme_site_block ) && isset( $block_theme_site_block['blockName'] ) && ! isset( $block_theme_site_block['blockname'] ), 'Block theme site proposal preserves Gutenberg blockName key case.' );
npcink_governance_core_fail_closed_assert( isset( $block_theme_site_block['innerBlocks'] ) && ! isset( $block_theme_site_block['innerblocks'] ), 'Block theme site proposal preserves Gutenberg innerBlocks key case.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$block_theme_layout_plan = npcink_governance_core_fail_closed_block_theme_layout_plan();
$block_theme_layout_result = $stack['service']->create_from_plan( 'npcink-abilities-toolkit/build-block-theme-site-plan', $block_theme_layout_plan, array(), array( 'source' => 'abilities_block_theme_layout' ) );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $block_theme_layout_result ), 'Valid block theme template layout plan creates a Core proposal.' );
npcink_governance_core_fail_closed_assert( 1 === (int) ( $block_theme_layout_result['proposal_count'] ?? 0 ), 'Valid block theme template layout plan creates one batch proposal.' );
$block_theme_layout_proposal = is_array( $block_theme_layout_result['proposals'][0] ?? null ) ? $block_theme_layout_result['proposals'][0] : array();
npcink_governance_core_fail_closed_assert( 'customize_template_layout' === (string) ( $block_theme_layout_proposal['preview']['block_theme_site']['intent'] ?? '' ), 'Block theme layout proposal preserves layout intent.' );
npcink_governance_core_fail_closed_assert( 'article_standard' === (string) ( $block_theme_layout_proposal['preview']['block_theme_site']['layout_profile'] ?? '' ), 'Block theme layout proposal preserves layout profile.' );
npcink_governance_core_fail_closed_assert( 'pass' === (string) ( $block_theme_layout_proposal['preview']['block_theme_site']['template_layout_contract']['contract_status'] ?? '' ), 'Block theme layout proposal preserves passing layout contract.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$block_theme_homepage_layout_plan = npcink_governance_core_fail_closed_block_theme_homepage_layout_plan();
$block_theme_homepage_layout_result = $stack['service']->create_from_plan( 'npcink-abilities-toolkit/build-block-theme-site-plan', $block_theme_homepage_layout_plan, array(), array( 'source' => 'abilities_block_theme_homepage_layout' ) );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $block_theme_homepage_layout_result ), 'Valid block theme homepage layout plan with categories creates a Core proposal.' );
npcink_governance_core_fail_closed_assert( 1 === (int) ( $block_theme_homepage_layout_result['proposal_count'] ?? 0 ), 'Valid block theme homepage layout plan creates one batch proposal.' );
$block_theme_homepage_layout_proposal = is_array( $block_theme_homepage_layout_result['proposals'][0] ?? null ) ? $block_theme_homepage_layout_result['proposals'][0] : array();
npcink_governance_core_fail_closed_assert( 'homepage_landing' === (string) ( $block_theme_homepage_layout_proposal['preview']['block_theme_site']['layout_profile'] ?? '' ), 'Block theme homepage layout proposal preserves homepage layout profile.' );
$block_theme_homepage_blocks_json = wp_json_encode( $block_theme_homepage_layout_proposal['input']['write_actions'][0]['input']['blocks'] ?? array() );
npcink_governance_core_fail_closed_assert( is_string( $block_theme_homepage_blocks_json ) && false !== strpos( $block_theme_homepage_blocks_json, 'core\/categories' ), 'Block theme homepage layout proposal accepts core categories blocks.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$block_theme_layout_bad_profile = npcink_governance_core_fail_closed_block_theme_layout_plan();
$block_theme_layout_bad_profile['template_layout_contract']['profiles'][0]['layout_profile'] = 'arbitrary_layout';
$block_theme_layout_bad_profile_result = $stack['service']->create_from_plan( 'npcink-abilities-toolkit/build-block-theme-site-plan', $block_theme_layout_bad_profile );
npcink_governance_core_fail_closed_assert( is_wp_error( $block_theme_layout_bad_profile_result ), 'Block theme layout plan with unaccepted profile is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_block_theme_site_layout_profile_rejected' === $block_theme_layout_bad_profile_result->get_error_code(), 'Block theme layout profile rejection uses stable error code.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$block_theme_layout_missing_contract = npcink_governance_core_fail_closed_block_theme_layout_plan();
unset( $block_theme_layout_missing_contract['template_layout_contract'] );
$block_theme_layout_missing_contract_result = $stack['service']->create_from_plan( 'npcink-abilities-toolkit/build-block-theme-site-plan', $block_theme_layout_missing_contract );
npcink_governance_core_fail_closed_assert( is_wp_error( $block_theme_layout_missing_contract_result ), 'Block theme layout plan without a passing layout contract is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_block_theme_site_layout_contract_rejected' === $block_theme_layout_missing_contract_result->get_error_code(), 'Block theme layout contract rejection uses stable error code.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$block_theme_missing_roundtrip = npcink_governance_core_fail_closed_block_theme_layout_plan();
unset( $block_theme_missing_roundtrip['write_actions'][0]['input']['validate_roundtrip'] );
$block_theme_missing_roundtrip_result = $stack['service']->create_from_plan( 'npcink-abilities-toolkit/build-block-theme-site-plan', $block_theme_missing_roundtrip );
npcink_governance_core_fail_closed_assert( is_wp_error( $block_theme_missing_roundtrip_result ), 'Block theme layout plan without roundtrip validation evidence is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_block_theme_site_roundtrip_required' === $block_theme_missing_roundtrip_result->get_error_code(), 'Block theme layout roundtrip rejection uses stable error code.' );
npcink_governance_core_fail_closed_assert( 0 === count( $wpdb->rows( $proposal_table ) ), 'Block theme layout missing roundtrip stores no proposal row.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$block_theme_bad_block = npcink_governance_core_fail_closed_block_theme_layout_plan();
$block_theme_bad_block['write_actions'][0]['input']['blocks'][1]['innerBlocks'][] = array(
	'blockName'    => 'core/html',
	'attrs'        => array(),
	'innerBlocks'  => array(),
	'innerHTML'    => '<script>alert("blocked")</script>',
	'innerContent' => array( '<script>alert("blocked")</script>' ),
);
$block_theme_bad_block_result = $stack['service']->create_from_plan( 'npcink-abilities-toolkit/build-block-theme-site-plan', $block_theme_bad_block );
npcink_governance_core_fail_closed_assert( is_wp_error( $block_theme_bad_block_result ), 'Block theme layout plan with raw HTML block is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_block_theme_site_block_rejected' === $block_theme_bad_block_result->get_error_code(), 'Block theme raw HTML block rejection uses stable error code.' );
npcink_governance_core_fail_closed_assert( 0 === count( $wpdb->rows( $proposal_table ) ), 'Block theme raw HTML block stores no proposal row.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$block_theme_navigation_block = npcink_governance_core_fail_closed_block_theme_layout_plan();
$block_theme_navigation_block['write_actions'][0]['input']['blocks'][1]['innerBlocks'][] = array(
	'blockName'    => 'core/navigation',
	'attrs'        => array( 'ref' => 1 ),
	'innerBlocks'  => array(),
	'innerHTML'    => '',
	'innerContent' => array(),
);
$block_theme_navigation_block_result = $stack['service']->create_from_plan( 'npcink-abilities-toolkit/build-block-theme-site-plan', $block_theme_navigation_block );
npcink_governance_core_fail_closed_assert( is_wp_error( $block_theme_navigation_block_result ), 'Block theme layout plan with navigation block is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_block_theme_site_block_rejected' === $block_theme_navigation_block_result->get_error_code(), 'Block theme navigation block rejection uses stable error code.' );
npcink_governance_core_fail_closed_assert( 0 === count( $wpdb->rows( $proposal_table ) ), 'Block theme navigation block stores no proposal row.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$block_theme_script_html = npcink_governance_core_fail_closed_block_theme_layout_plan();
$block_theme_script_html['write_actions'][0]['input']['blocks'][1]['innerHTML'] = '<main><iframe src="https://example.com"></iframe></main>';
$block_theme_script_html['write_actions'][0]['input']['blocks'][1]['innerContent'] = array( '<main><iframe src="https://example.com"></iframe></main>' );
$block_theme_script_html_result = $stack['service']->create_from_plan( 'npcink-abilities-toolkit/build-block-theme-site-plan', $block_theme_script_html );
npcink_governance_core_fail_closed_assert( is_wp_error( $block_theme_script_html_result ), 'Block theme layout plan with embedded HTML is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_block_theme_site_block_rejected' === $block_theme_script_html_result->get_error_code(), 'Block theme embedded HTML rejection uses stable error code.' );
npcink_governance_core_fail_closed_assert( 0 === count( $wpdb->rows( $proposal_table ) ), 'Block theme embedded HTML stores no proposal row.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$block_theme_bad_slug = npcink_governance_core_fail_closed_block_theme_layout_plan();
$block_theme_bad_slug['write_actions'][0]['input']['slug'] = 'archive-product';
$block_theme_bad_slug_result = $stack['service']->create_from_plan( 'npcink-abilities-toolkit/build-block-theme-site-plan', $block_theme_bad_slug );
npcink_governance_core_fail_closed_assert( is_wp_error( $block_theme_bad_slug_result ), 'Block theme layout plan for a non-allowlisted template slug is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_block_theme_site_template_rejected' === $block_theme_bad_slug_result->get_error_code(), 'Block theme template slug rejection uses stable error code.' );
npcink_governance_core_fail_closed_assert( 0 === count( $wpdb->rows( $proposal_table ) ), 'Block theme bad slug stores no proposal row.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$too_many_global_actions = npcink_governance_core_fail_closed_block_theme_site_plan();
$base_block_theme_action = $too_many_global_actions['write_actions'][0];
$too_many_global_actions['write_actions'] = array();
for ( $i = 0; $i < 26; $i++ ) {
	$action = $base_block_theme_action;
	$action['action_id'] = 'upsert-template-global-limit-' . $i;
	$action['input']['slug'] = 'single-global-limit-' . $i;
	$action['input']['source_template_id'] = 'twentytwentyfive//single-global-limit-' . $i;
	$action['input']['idempotency_key'] = 'block-theme-site-global-limit-' . $i;
	$too_many_global_actions['write_actions'][] = $action;
}
$too_many_global_actions_result = $stack['service']->create_from_plan( 'npcink-abilities-toolkit/build-block-theme-site-plan', $too_many_global_actions );
npcink_governance_core_fail_closed_assert( is_wp_error( $too_many_global_actions_result ), 'Plan with too many write actions is rejected before proposal creation.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_plan_too_many_actions' === $too_many_global_actions_result->get_error_code(), 'Global plan action limit rejection uses stable error code.' );
npcink_governance_core_fail_closed_assert( 0 === count( $wpdb->rows( $proposal_table ) ), 'Plan over global action limit stores no proposal row.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$too_many_block_theme_actions = npcink_governance_core_fail_closed_block_theme_site_plan();
$base_block_theme_action = $too_many_block_theme_actions['write_actions'][0];
$too_many_block_theme_actions['write_actions'] = array();
for ( $i = 0; $i < 11; $i++ ) {
	$action = $base_block_theme_action;
	$action['action_id'] = 'upsert-template-block-limit-' . $i;
	$action['input']['slug'] = 'single-block-limit-' . $i;
	$action['input']['source_template_id'] = 'twentytwentyfive//single-block-limit-' . $i;
	$action['input']['idempotency_key'] = 'block-theme-site-block-limit-' . $i;
	$too_many_block_theme_actions['write_actions'][] = $action;
}
$too_many_block_theme_actions_result = $stack['service']->create_from_plan( 'npcink-abilities-toolkit/build-block-theme-site-plan', $too_many_block_theme_actions );
npcink_governance_core_fail_closed_assert( is_wp_error( $too_many_block_theme_actions_result ), 'Block theme site plan with too many template actions is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_block_theme_site_actions_rejected' === $too_many_block_theme_actions_result->get_error_code(), 'Block theme site action limit rejection uses stable error code.' );
npcink_governance_core_fail_closed_assert( 0 === count( $wpdb->rows( $proposal_table ) ), 'Oversized block theme site plan stores no proposal row.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$block_theme_site_bad_target = npcink_governance_core_fail_closed_block_theme_site_plan();
$block_theme_site_bad_target['write_actions'][0]['target_ability_id'] = 'npcink-abilities-toolkit/update-post-blocks';
$block_theme_site_bad_target_result = $stack['service']->create_from_plan( 'npcink-abilities-toolkit/build-block-theme-site-plan', $block_theme_site_bad_target );
npcink_governance_core_fail_closed_assert( is_wp_error( $block_theme_site_bad_target_result ), 'Block theme site plan with non-template target is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_block_theme_site_target_rejected' === $block_theme_site_bad_target_result->get_error_code(), 'Block theme site target rejection uses stable error code.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$block_theme_site_missing_slug = npcink_governance_core_fail_closed_block_theme_site_plan();
unset( $block_theme_site_missing_slug['write_actions'][0]['input']['slug'] );
$block_theme_site_missing_slug_result = $stack['service']->create_from_plan( 'npcink-abilities-toolkit/build-block-theme-site-plan', $block_theme_site_missing_slug );
npcink_governance_core_fail_closed_assert( is_wp_error( $block_theme_site_missing_slug_result ), 'Block theme site template override without slug is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_block_theme_site_upsert_target_missing' === $block_theme_site_missing_slug_result->get_error_code(), 'Block theme site missing slug rejection uses stable error code.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$article_block_plan = npcink_governance_core_fail_closed_article_block_plan();
$article_block_result = $stack['service']->create_from_plan( 'npcink-abilities-toolkit/build-article-block-plan', $article_block_plan, array(), array( 'source' => 'abilities_article_block' ) );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $article_block_result ), 'Valid article block plan creates a Core proposal.' );
npcink_governance_core_fail_closed_assert( 1 === (int) ( $article_block_result['proposal_count'] ?? 0 ), 'Valid article block plan creates one batch proposal.' );
$article_block_proposal = is_array( $article_block_result['proposals'][0] ?? null ) ? $article_block_result['proposals'][0] : array();
npcink_governance_core_fail_closed_assert( 'plan_to_proposal_batch' === (string) ( $article_block_proposal['preview']['source']['type'] ?? '' ), 'Article block plan stores batch proposal source.' );
npcink_governance_core_fail_closed_assert( is_array( $article_block_proposal['preview']['article_block'] ?? null ), 'Article block proposal preserves article block preview.' );
npcink_governance_core_fail_closed_assert( 'comparison-review' === (string) ( $article_block_proposal['preview']['article_block']['article_template'] ?? '' ), 'Article block preview preserves article template.' );
npcink_governance_core_fail_closed_assert( 2 === count( (array) ( $article_block_proposal['input']['write_actions'] ?? array() ) ), 'Article block proposal stores create and block update actions.' );
npcink_governance_core_fail_closed_assert( '$outputs.create-article-draft.post_id' === (string) ( $article_block_proposal['input']['write_actions'][1]['input']['post_id'] ?? '' ), 'Article block update action preserves output reference.' );
$article_block = $article_block_proposal['input']['write_actions'][1]['input']['blocks'][0] ?? array();
npcink_governance_core_fail_closed_assert( is_array( $article_block ) && isset( $article_block['blockName'] ) && ! isset( $article_block['blockname'] ), 'Article block proposal preserves Gutenberg blockName key case.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$article_block_bad_class = npcink_governance_core_fail_closed_article_block_plan();
$article_block_bad_class['write_actions'][1]['input']['blocks'][0]['attrs']['className'] = 'rogue-class';
$article_block_bad_class_result = $stack['service']->create_from_plan( 'npcink-abilities-toolkit/build-article-block-plan', $article_block_bad_class );
npcink_governance_core_fail_closed_assert( is_wp_error( $article_block_bad_class_result ), 'Article block plan with custom block class is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_article_block_class_rejected' === $article_block_bad_class_result->get_error_code(), 'Article block class rejection uses stable error code.' );

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

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$nightly_inspection_plan = npcink_governance_core_fail_closed_nightly_inspection_review_plan();
$nightly_inspection_result = $stack['service']->create_from_plan( 'npcink-toolbox/build-nightly-inspection-review-plan', $nightly_inspection_plan, array(), array( 'source' => 'cloud_nightly_inspection' ) );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $nightly_inspection_result ), 'Valid Nightly Inspection review plan creates a Core proposal.' );
npcink_governance_core_fail_closed_assert( 1 === (int) ( $nightly_inspection_result['proposal_count'] ?? 0 ), 'Valid Nightly Inspection review plan creates one blocked proposal.' );
npcink_governance_core_fail_closed_assert( 0 === (int) ( $nightly_inspection_result['proposal_ready_count'] ?? 0 ), 'Nightly Inspection review proposal is not proposal-ready before human draft input.' );
$nightly_inspection_proposal = is_array( $nightly_inspection_result['proposals'][0] ?? null ) ? $nightly_inspection_result['proposals'][0] : array();
npcink_governance_core_fail_closed_assert( 'npcink-abilities-toolkit/create-draft' === (string) ( $nightly_inspection_proposal['ability_id'] ?? '' ), 'Nightly Inspection review plan targets the governed create-draft ability.' );
npcink_governance_core_fail_closed_assert( false === (bool) ( $nightly_inspection_proposal['preview']['proposal_ready'] ?? true ), 'Nightly Inspection review proposal stores proposal_ready=false.' );
npcink_governance_core_fail_closed_assert( in_array( 'title', (array) ( $nightly_inspection_proposal['preview']['needs_input'] ?? array() ), true ), 'Nightly Inspection review proposal requires title input.' );
npcink_governance_core_fail_closed_assert( isset( $nightly_inspection_proposal['preview']['nightly_inspection_review'] ), 'Nightly Inspection review preview is preserved in the proposal.' );
npcink_governance_core_fail_closed_assert( 'run_nightly_inspection_fault_injection' === (string) ( $nightly_inspection_proposal['preview']['nightly_inspection_review']['cloud_run_id'] ?? '' ), 'Nightly Inspection proposal preserves Cloud run id.' );
npcink_governance_core_fail_closed_assert( 'action_001' === (string) ( $nightly_inspection_proposal['preview']['nightly_inspection_review']['selected_review_item_ids'][0] ?? '' ), 'Nightly Inspection proposal preserves selected Morning Brief review item id.' );
npcink_governance_core_fail_closed_assert( 1 === (int) ( $nightly_inspection_proposal['preview']['nightly_inspection_review']['evidence_ref_count'] ?? 0 ), 'Nightly Inspection proposal preserves evidence reference count.' );
npcink_governance_core_fail_closed_assert( 'toolbox_morning_brief_operator' === (string) ( $nightly_inspection_proposal['preview']['nightly_inspection_review']['needs_input_resolution_owner'] ?? '' ), 'Nightly Inspection proposal routes missing input back to the Toolbox Morning Brief operator.' );
npcink_governance_core_fail_closed_assert( true === (bool) ( $nightly_inspection_proposal['preview']['nightly_inspection_review']['resubmission_required'] ?? false ), 'Nightly Inspection proposal requires complete resubmission after missing input is resolved.' );
npcink_governance_core_fail_closed_assert( false === (bool) ( $nightly_inspection_proposal['preview']['nightly_inspection_review']['core_amendment_supported'] ?? true ), 'Nightly Inspection proposal does not allow Core-side amendment of missing draft fields.' );
$nightly_approved = $stack['proposal_service']->approve( (string) ( $nightly_inspection_proposal['proposal_id'] ?? '' ), array( 'reason' => 'nightly_missing_input_preflight' ) );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $nightly_approved ), 'Blocked Nightly Inspection proposal can be approved for review without becoming executable.' );
$nightly_blocked_preflight = $stack['preflight']->preflight( (string) ( $nightly_inspection_proposal['proposal_id'] ?? '' ) );
npcink_governance_core_fail_closed_assert( is_wp_error( $nightly_blocked_preflight ), 'Blocked Nightly Inspection proposal fails commit preflight after approval.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_proposal_items_blocked' === $nightly_blocked_preflight->get_error_code(), 'Blocked Nightly Inspection proposal uses stable preflight blocked error code.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$nightly_inspection_ready = npcink_governance_core_fail_closed_nightly_inspection_review_plan();
$nightly_inspection_ready['write_actions'][0]['proposal_ready'] = true;
$nightly_inspection_ready_result = $stack['service']->create_from_plan( 'npcink-toolbox/build-nightly-inspection-review-plan', $nightly_inspection_ready );
npcink_governance_core_fail_closed_assert( is_wp_error( $nightly_inspection_ready_result ), 'Nightly Inspection review plan marked ready is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_nightly_inspection_ready_rejected' === $nightly_inspection_ready_result->get_error_code(), 'Nightly Inspection ready rejection uses stable error code.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$nightly_inspection_missing_evidence = npcink_governance_core_fail_closed_nightly_inspection_review_plan();
$nightly_inspection_missing_evidence['evidence_refs'] = array();
$nightly_inspection_missing_evidence_result = $stack['service']->create_from_plan( 'npcink-toolbox/build-nightly-inspection-review-plan', $nightly_inspection_missing_evidence );
npcink_governance_core_fail_closed_assert( is_wp_error( $nightly_inspection_missing_evidence_result ), 'Nightly Inspection review plan without evidence is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_nightly_inspection_evidence_missing' === $nightly_inspection_missing_evidence_result->get_error_code(), 'Nightly Inspection missing evidence rejection uses stable error code.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$content_metadata_plan = npcink_governance_core_fail_closed_content_metadata_apply_plan();
$content_metadata_result = $stack['service']->create_from_plan( 'npcink-toolbox/build-content-metadata-apply-plan', $content_metadata_plan, array(), array( 'source' => 'toolbox_content_metadata_apply' ) );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $content_metadata_result ), 'Valid content metadata apply plan creates a Core proposal.' );
npcink_governance_core_fail_closed_assert( 1 === (int) ( $content_metadata_result['proposal_count'] ?? 0 ), 'Valid content metadata apply plan creates one batch proposal.' );
$content_metadata_proposal = is_array( $content_metadata_result['proposals'][0] ?? null ) ? $content_metadata_result['proposals'][0] : array();
npcink_governance_core_fail_closed_assert( 'plan_to_proposal_batch' === (string) ( $content_metadata_proposal['preview']['source']['type'] ?? '' ), 'Content metadata apply plan stores batch proposal source.' );
npcink_governance_core_fail_closed_assert( 3 === count( (array) ( $content_metadata_proposal['input']['write_actions'] ?? array() ) ), 'Content metadata apply proposal stores excerpt, category, and tag actions.' );
npcink_governance_core_fail_closed_assert( isset( $content_metadata_proposal['preview']['content_metadata_apply'] ), 'Content metadata apply preview is preserved in the proposal.' );
npcink_governance_core_fail_closed_assert( 'core_proposal_required' === (string) ( $content_metadata_proposal['preview']['content_metadata_apply']['classification_evidence']['decision_envelope']['classification'] ?? '' ), 'Content metadata apply proposal preserves classifier decision evidence.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$content_metadata_local_consent = npcink_governance_core_fail_closed_content_metadata_apply_plan();
$content_metadata_local_consent['authorization']['classification'] = 'local_admin_consent';
$content_metadata_local_consent['authorization']['decision_envelope']['classification'] = 'local_admin_consent';
$content_metadata_local_consent_result = $stack['service']->create_from_plan( 'npcink-toolbox/build-content-metadata-apply-plan', $content_metadata_local_consent );
npcink_governance_core_fail_closed_assert( is_wp_error( $content_metadata_local_consent_result ), 'Content metadata apply plan with local-admin-consent classification is rejected from Core intake.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_content_metadata_authorization_rejected' === $content_metadata_local_consent_result->get_error_code(), 'Content metadata authorization mismatch rejection uses stable error code.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$content_metadata_title_update = npcink_governance_core_fail_closed_content_metadata_apply_plan();
$content_metadata_title_update['write_actions'][0]['input']['title'] = 'Unexpected title write';
$content_metadata_title_result = $stack['service']->create_from_plan( 'npcink-toolbox/build-content-metadata-apply-plan', $content_metadata_title_update );
npcink_governance_core_fail_closed_assert( is_wp_error( $content_metadata_title_result ), 'Content metadata apply plan with title update is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_content_metadata_update_field_rejected' === $content_metadata_title_result->get_error_code(), 'Content metadata title update rejection uses stable error code.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$content_metadata_create_missing = npcink_governance_core_fail_closed_content_metadata_apply_plan();
$content_metadata_create_missing['write_actions'][2]['input']['create_missing'] = true;
$content_metadata_create_missing['write_actions'][2]['input']['terms'] = array( 'New Review Term' );
$content_metadata_create_missing_result = $stack['service']->create_from_plan( 'npcink-toolbox/build-content-metadata-apply-plan', $content_metadata_create_missing );
npcink_governance_core_fail_closed_assert( is_wp_error( $content_metadata_create_missing_result ), 'Content metadata apply plan with create_missing terms is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_content_metadata_create_missing_rejected' === $content_metadata_create_missing_result->get_error_code(), 'Content metadata create_missing rejection uses stable error code.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$content_metadata_taxonomy = npcink_governance_core_fail_closed_content_metadata_apply_plan();
$content_metadata_taxonomy['write_actions'][1]['input']['taxonomy'] = 'product_cat';
$content_metadata_taxonomy_result = $stack['service']->create_from_plan( 'npcink-toolbox/build-content-metadata-apply-plan', $content_metadata_taxonomy );
npcink_governance_core_fail_closed_assert( is_wp_error( $content_metadata_taxonomy_result ), 'Content metadata apply plan with unsupported taxonomy is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_content_metadata_taxonomy_rejected' === $content_metadata_taxonomy_result->get_error_code(), 'Content metadata taxonomy rejection uses stable error code.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$content_metadata_missing_controls = npcink_governance_core_fail_closed_content_metadata_apply_plan();
unset( $content_metadata_missing_controls['write_actions'][1]['input']['dry_run'], $content_metadata_missing_controls['write_actions'][1]['input']['commit'] );
$content_metadata_missing_controls_result = $stack['service']->create_from_plan( 'npcink-toolbox/build-content-metadata-apply-plan', $content_metadata_missing_controls );
npcink_governance_core_fail_closed_assert( is_wp_error( $content_metadata_missing_controls_result ), 'Content metadata apply plan without explicit dry_run/commit controls is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_content_metadata_commit_rejected' === $content_metadata_missing_controls_result->get_error_code(), 'Content metadata missing control rejection uses stable error code.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$content_metadata_term_extra = npcink_governance_core_fail_closed_content_metadata_apply_plan();
$content_metadata_term_extra['write_actions'][1]['input']['status'] = 'publish';
$content_metadata_term_extra_result = $stack['service']->create_from_plan( 'npcink-toolbox/build-content-metadata-apply-plan', $content_metadata_term_extra );
npcink_governance_core_fail_closed_assert( is_wp_error( $content_metadata_term_extra_result ), 'Content metadata apply plan with extra term action fields is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_content_metadata_term_field_rejected' === $content_metadata_term_extra_result->get_error_code(), 'Content metadata term field rejection uses stable error code.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$content_metadata_term_ids_string = npcink_governance_core_fail_closed_content_metadata_apply_plan();
$content_metadata_term_ids_string['write_actions'][2]['input']['term_ids'] = '41,42';
$content_metadata_term_ids_string_result = $stack['service']->create_from_plan( 'npcink-toolbox/build-content-metadata-apply-plan', $content_metadata_term_ids_string );
npcink_governance_core_fail_closed_assert( is_wp_error( $content_metadata_term_ids_string_result ), 'Content metadata apply plan with non-array term_ids is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_content_metadata_term_ids_missing' === $content_metadata_term_ids_string_result->get_error_code(), 'Content metadata term_ids type rejection uses stable error code.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$content_metadata_duplicate_excerpt = npcink_governance_core_fail_closed_content_metadata_apply_plan();
$content_metadata_duplicate_excerpt['write_actions'][1] = $content_metadata_duplicate_excerpt['write_actions'][0];
$content_metadata_duplicate_excerpt['write_actions'][1]['action_id'] = 'apply_second_selected_excerpt';
$content_metadata_duplicate_excerpt_result = $stack['service']->create_from_plan( 'npcink-toolbox/build-content-metadata-apply-plan', $content_metadata_duplicate_excerpt );
npcink_governance_core_fail_closed_assert( is_wp_error( $content_metadata_duplicate_excerpt_result ), 'Content metadata apply plan with duplicate excerpt actions is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_content_metadata_duplicate_action_rejected' === $content_metadata_duplicate_excerpt_result->get_error_code(), 'Content metadata duplicate excerpt rejection uses stable error code.' );

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_plan_stack();
$content_metadata_duplicate_taxonomy = npcink_governance_core_fail_closed_content_metadata_apply_plan();
$content_metadata_duplicate_taxonomy['write_actions'][2] = $content_metadata_duplicate_taxonomy['write_actions'][1];
$content_metadata_duplicate_taxonomy['write_actions'][2]['action_id'] = 'assign_existing_categories_again';
$content_metadata_duplicate_taxonomy_result = $stack['service']->create_from_plan( 'npcink-toolbox/build-content-metadata-apply-plan', $content_metadata_duplicate_taxonomy );
npcink_governance_core_fail_closed_assert( is_wp_error( $content_metadata_duplicate_taxonomy_result ), 'Content metadata apply plan with duplicate taxonomy actions is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_content_metadata_duplicate_action_rejected' === $content_metadata_duplicate_taxonomy_result->get_error_code(), 'Content metadata duplicate taxonomy rejection uses stable error code.' );

$capabilities = ( new \Npcink\GovernanceCore\Capabilities\Ability_Registry_Adapter() )->list_capabilities();
$sensitive_capability = array();
foreach ( (array) ( $capabilities['items'] ?? array() ) as $item ) {
	if ( is_array( $item ) && 'npcink-abilities-toolkit/read-error-log' === (string) ( $item['ability_id'] ?? '' ) ) {
		$sensitive_capability = $item;
		break;
	}
}
npcink_governance_core_fail_closed_assert( true === (bool) ( $sensitive_capability['read_authorization_required'] ?? false ), 'Capability can mark sensitive read authorization required.' );
npcink_governance_core_fail_closed_assert( true === (bool) ( $sensitive_capability['requires_read_authorization'] ?? false ), 'Capability exposes Adapter-compatible requires_read_authorization.' );
npcink_governance_core_fail_closed_assert( 'core_read_authorization_required' === (string) ( $sensitive_capability['read_policy'] ?? '' ), 'Capability exposes Core read authorization read_policy.' );
npcink_governance_core_fail_closed_assert( 'core_read_request' === (string) ( $sensitive_capability['authorization_mode'] ?? '' ), 'Capability exposes Core read request authorization_mode.' );
npcink_governance_core_fail_closed_assert( true === (bool) ( $sensitive_capability['read_authorization']['required'] ?? false ), 'Capability exposes nested read_authorization.required.' );
npcink_governance_core_fail_closed_assert( false !== strpos( (string) ( $sensitive_capability['read_authorization_preflight_route'] ?? '' ), '/read-requests/{request_id}/read-preflight' ), 'Capability exposes read authorization preflight route guidance.' );

$wpdb = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_read_request_stack();
$read_payload = array(
	'ability_id'              => 'npcink-abilities-toolkit/read-error-log',
	'input'                   => array(
		'tail'   => true,
		'filter' => 'recent-errors',
	),
	'requested_input_summary' => 'Tail recent PHP errors. Authorization: Bearer SHOULD_NOT_LEAK',
	'sensitivity'             => 'sensitive',
	'data_classes'            => array( 'logs', 'diagnostics' ),
	'redaction_level'         => 'strict',
	'purpose'                 => 'Debug a failed smoke run.',
	'caller'                  => array(
		'source'        => 'fault_injection',
		'authorization' => 'Bearer SHOULD_NOT_LEAK',
		'cookie'        => 'wordpress_logged_in=SHOULD_NOT_LEAK',
	),
	'max_rows'                => 500,
	'tail_lines'              => 500,
	'allowed_fields'          => array( 'timestamp', 'message', 'cookie' ),
	'denied_fields'           => array( 'stack_trace' ),
);

$missing_data_classes_payload = $read_payload;
$missing_data_classes_payload['input'] = array( 'tail' => true, 'filter' => 'missing-data-classes' );
unset( $missing_data_classes_payload['data_classes'] );
$missing_data_classes = $stack['service']->create( $missing_data_classes_payload );
npcink_governance_core_fail_closed_assert( is_wp_error( $missing_data_classes ), 'Sensitive read request fails without data classes.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_read_request_data_classes_required' === $missing_data_classes->get_error_code(), 'Missing data classes uses stable error code.' );

$missing_purpose_payload = $read_payload;
$missing_purpose_payload['input'] = array( 'tail' => true, 'filter' => 'missing-purpose' );
unset( $missing_purpose_payload['purpose'] );
$missing_purpose = $stack['service']->create( $missing_purpose_payload );
npcink_governance_core_fail_closed_assert( is_wp_error( $missing_purpose ), 'Sensitive read request fails without purpose.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_read_request_purpose_required' === $missing_purpose->get_error_code(), 'Missing purpose uses stable error code.' );

$unredacted_payload = $read_payload;
$unredacted_payload['input'] = array( 'tail' => true, 'filter' => 'no-redaction' );
$unredacted_payload['redaction_level'] = 'none';
$unredacted_request = $stack['service']->create( $unredacted_payload );
npcink_governance_core_fail_closed_assert( is_wp_error( $unredacted_request ), 'Sensitive read request fails when redaction is disabled.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_read_request_redaction_required' === $unredacted_request->get_error_code(), 'Disabled redaction uses stable error code.' );

$unredacted_approval_request = $stack['service']->create(
	array_merge(
		$read_payload,
		array(
			'input' => array( 'tail' => true, 'filter' => 'approval-no-redaction' ),
		)
	)
);
npcink_governance_core_fail_closed_assert( ! is_wp_error( $unredacted_approval_request ), 'Sensitive read request is created for approval redaction guard.' );
$unredacted_approval = $stack['service']->approve( (string) $unredacted_approval_request['request_id'], array( 'redaction_level' => 'none' ) );
npcink_governance_core_fail_closed_assert( is_wp_error( $unredacted_approval ), 'Sensitive read approval fails when redaction is disabled.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_read_request_redaction_required' === $unredacted_approval->get_error_code(), 'Approval disabled redaction uses stable error code.' );

$read_request = $stack['service']->create( $read_payload );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $read_request ), 'Sensitive read request is created.' );
npcink_governance_core_fail_closed_assert( 'pending' === (string) ( $read_request['status'] ?? '' ), 'Sensitive read request starts pending.' );
npcink_governance_core_fail_closed_assert( '' !== (string) ( $read_request['request_id'] ?? '' ), 'Sensitive read request returns request_id.' );
npcink_governance_core_fail_closed_assert( '' !== (string) ( $read_request['input_hash'] ?? '' ), 'Sensitive read request binds input_hash.' );
npcink_governance_core_fail_closed_assert( false === strpos( wp_json_encode( $read_request ), 'SHOULD_NOT_LEAK' ), 'Sensitive read request response does not emit secrets.' );
npcink_governance_core_fail_closed_assert( 1 === count( npcink_governance_core_fail_closed_audit_rows( (string) $read_request['request_id'], 'read_request.created' ) ), 'Sensitive read request creation is audited.' );

$approved_read = $stack['service']->approve(
	(string) $read_request['request_id'],
	array(
		'note'           => 'Approve bounded diagnostic read.',
		'max_rows'       => 80,
		'tail_lines'     => 120,
		'allowed_fields' => array( 'timestamp', 'message', 'severity', 'cookie' ),
		'denied_fields'  => array( 'authorization' ),
	)
);
npcink_governance_core_fail_closed_assert( ! is_wp_error( $approved_read ) && 'approved' === (string) ( $approved_read['status'] ?? '' ), 'Sensitive read request can be approved.' );
npcink_governance_core_fail_closed_assert( 50 === (int) ( $approved_read['bounds']['max_rows'] ?? 0 ), 'Sensitive read approval cannot expand provider max_rows.' );
npcink_governance_core_fail_closed_assert( 100 === (int) ( $approved_read['bounds']['tail_lines'] ?? 0 ), 'Sensitive read approval cannot expand provider tail_lines.' );
npcink_governance_core_fail_closed_assert( ! in_array( 'cookie', (array) ( $approved_read['bounds']['allowed_fields'] ?? array() ), true ), 'Sensitive read approval cannot allow fields outside provider scope.' );
npcink_governance_core_fail_closed_assert( in_array( 'authorization', (array) ( $approved_read['bounds']['denied_fields'] ?? array() ), true ), 'Sensitive read approval preserves denied fields.' );
npcink_governance_core_fail_closed_assert( 1 === count( npcink_governance_core_fail_closed_audit_rows( (string) $read_request['request_id'], 'read_request.approved' ) ), 'Sensitive read approval is audited.' );

$read_signed_client_fingerprint = 'sha256:' . str_repeat( 'b', 64 );
\Npcink\GovernanceCore\Security\Request_Context::set_app(
	array(
		'app_id'                    => 'adapter_app_read_preflight',
		'key_id'                    => 'adapter_key_read_preflight',
		'caller_type'               => 'openclaw_adapter',
		'scope'                     => 'read_requests:preflight',
		'scopes'                    => array( 'read_requests:preflight' ),
		'route_family'              => 'read_requests_preflight',
		'signed_client_fingerprint' => $read_signed_client_fingerprint,
	)
);
$grant = $stack['service']->preflight(
	(string) $read_request['request_id'],
	array(
		'ability_id' => 'npcink-abilities-toolkit/read-error-log',
		'input'      => $read_payload['input'],
	)
);
npcink_governance_core_fail_closed_assert( ! is_wp_error( $grant ), 'Approved sensitive read request returns grant context.' );
$grant_context = is_array( $grant['read_authorization_context'] ?? null ) ? $grant['read_authorization_context'] : array();
npcink_governance_core_fail_closed_assert( true === (bool) ( $grant_context['read_authorization_granted'] ?? false ), 'Grant context marks read_authorization_granted=true.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core' === (string) ( $grant_context['core_authorization_truth'] ?? '' ), 'Grant context records Core as authorization truth.' );
npcink_governance_core_fail_closed_assert( false === (bool) ( $grant_context['commit_execution'] ?? true ), 'Grant context has commit_execution=false.' );
npcink_governance_core_fail_closed_assert( false === (bool) ( $grant_context['write_execution'] ?? true ), 'Grant context has write_execution=false.' );
npcink_governance_core_fail_closed_assert( (string) ( $approved_read['input_hash'] ?? '' ) === (string) ( $grant_context['approved_input_hash'] ?? '' ), 'Grant context binds approved input hash.' );
npcink_governance_core_fail_closed_assert( $read_signed_client_fingerprint === (string) ( $grant_context['signed_client_fingerprint'] ?? '' ), 'Grant context binds signed client fingerprint.' );
npcink_governance_core_fail_closed_assert( $read_signed_client_fingerprint === (string) ( $grant_context['client_key_fingerprint'] ?? '' ), 'Grant context binds client key fingerprint alias.' );
npcink_governance_core_fail_closed_assert( 1 === count( npcink_governance_core_fail_closed_audit_rows( (string) $read_request['request_id'], 'read_request.preflighted' ) ), 'Sensitive read preflight/grant is audited.' );

$wrong_ability = $stack['service']->preflight(
	(string) $read_request['request_id'],
	array(
		'ability_id' => 'npcink-abilities-toolkit/build-article-block-plan',
		'input'      => $read_payload['input'],
	)
);
npcink_governance_core_fail_closed_assert( is_wp_error( $wrong_ability ), 'Sensitive read grant rejects wrong ability_id.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_read_request_ability_mismatch' === $wrong_ability->get_error_code(), 'Wrong ability grant uses stable error code.' );

$wrong_input = $stack['service']->preflight(
	(string) $read_request['request_id'],
	array(
		'ability_id' => 'npcink-abilities-toolkit/read-error-log',
		'input'      => array( 'tail' => true, 'filter' => 'all-errors' ),
	)
);
npcink_governance_core_fail_closed_assert( is_wp_error( $wrong_input ), 'Sensitive read grant rejects changed input hash.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_read_request_input_mismatch' === $wrong_input->get_error_code(), 'Changed input grant uses stable error code.' );

$rejected_request = $stack['service']->create(
	array_merge(
		$read_payload,
		array(
			'input' => array( 'tail' => true, 'filter' => 'rejected-errors' ),
		)
	)
);
npcink_governance_core_fail_closed_assert( ! is_wp_error( $rejected_request ), 'Second sensitive read request is created for rejection.' );
$rejected_read = $stack['service']->reject( (string) $rejected_request['request_id'], array( 'note' => 'Reject diagnostic read.' ) );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $rejected_read ) && 'rejected' === (string) ( $rejected_read['status'] ?? '' ), 'Sensitive read request can be rejected.' );
npcink_governance_core_fail_closed_assert( 1 === count( npcink_governance_core_fail_closed_audit_rows( (string) $rejected_request['request_id'], 'read_request.rejected' ) ), 'Sensitive read rejection is audited.' );
$rejected_grant = $stack['service']->preflight(
	(string) $rejected_request['request_id'],
	array(
		'ability_id' => 'npcink-abilities-toolkit/read-error-log',
		'input'      => array( 'tail' => true, 'filter' => 'rejected-errors' ),
	)
);
npcink_governance_core_fail_closed_assert( is_wp_error( $rejected_grant ), 'Rejected sensitive read request cannot grant.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_read_request_not_approved' === $rejected_grant->get_error_code(), 'Rejected read grant uses not-approved error code.' );

$expired_request = $stack['service']->create(
	array_merge(
		$read_payload,
		array(
			'input' => array( 'tail' => true, 'filter' => 'expired-errors' ),
		)
	)
);
npcink_governance_core_fail_closed_assert( ! is_wp_error( $expired_request ), 'Third sensitive read request is created for expiry.' );
$expired_approved = $stack['service']->approve( (string) $expired_request['request_id'] );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $expired_approved ), 'Expiring sensitive read request can be approved.' );
$wpdb->update(
	$read_request_table,
	array( 'expires_at' => gmdate( 'Y-m-d H:i:s', time() - 60 ) ),
	array( 'request_id' => (string) $expired_request['request_id'] )
);
$expired_grant = $stack['service']->preflight(
	(string) $expired_request['request_id'],
	array(
		'ability_id' => 'npcink-abilities-toolkit/read-error-log',
		'input'      => array( 'tail' => true, 'filter' => 'expired-errors' ),
	)
);
npcink_governance_core_fail_closed_assert( is_wp_error( $expired_grant ), 'Expired sensitive read grant is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_read_request_expired' === $expired_grant->get_error_code(), 'Expired sensitive read grant uses stable error code.' );

$one_time_request = $stack['service']->create(
	array_merge(
		$read_payload,
		array(
			'input'    => array( 'tail' => true, 'filter' => 'one-time-errors' ),
			'one_time' => true,
		)
	)
);
npcink_governance_core_fail_closed_assert( ! is_wp_error( $one_time_request ), 'One-time sensitive read request is created.' );
$one_time_approved = $stack['service']->approve( (string) $one_time_request['request_id'], array( 'one_time' => true ) );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $one_time_approved ), 'One-time sensitive read request is approved.' );
$one_time_grant = $stack['service']->preflight(
	(string) $one_time_request['request_id'],
	array(
		'ability_id' => 'npcink-abilities-toolkit/read-error-log',
		'input'      => array( 'tail' => true, 'filter' => 'one-time-errors' ),
	)
);
npcink_governance_core_fail_closed_assert( ! is_wp_error( $one_time_grant ), 'One-time sensitive read request grants once.' );
$consumed_request = $stack['requests']->find( (string) $one_time_request['request_id'] );
npcink_governance_core_fail_closed_assert( is_array( $consumed_request ) && 'consumed' === (string) ( $consumed_request['status'] ?? '' ), 'One-time sensitive read request is consumed after grant.' );
$one_time_replay = $stack['service']->preflight(
	(string) $one_time_request['request_id'],
	array(
		'ability_id' => 'npcink-abilities-toolkit/read-error-log',
		'input'      => array( 'tail' => true, 'filter' => 'one-time-errors' ),
	)
);
npcink_governance_core_fail_closed_assert( is_wp_error( $one_time_replay ), 'Consumed one-time sensitive read request cannot be reused.' );

$one_time_failed_consume_request = $stack['service']->create(
	array_merge(
		$read_payload,
		array(
			'input'    => array( 'tail' => true, 'filter' => 'one-time-consume-fails' ),
			'one_time' => true,
		)
	)
);
npcink_governance_core_fail_closed_assert( ! is_wp_error( $one_time_failed_consume_request ), 'One-time sensitive read request is created for consume failure.' );
$one_time_failed_consume_approved = $stack['service']->approve( (string) $one_time_failed_consume_request['request_id'], array( 'one_time' => true ) );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $one_time_failed_consume_approved ), 'One-time sensitive read request is approved for consume failure.' );
$wpdb->fail_update_tables[] = $read_request_table;
$one_time_failed_consume_grant = $stack['service']->preflight(
	(string) $one_time_failed_consume_request['request_id'],
	array(
		'ability_id' => 'npcink-abilities-toolkit/read-error-log',
		'input'      => array( 'tail' => true, 'filter' => 'one-time-consume-fails' ),
	)
);
npcink_governance_core_fail_closed_assert( is_wp_error( $one_time_failed_consume_grant ), 'One-time sensitive read request fails closed when consume update fails.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_read_request_consume_failed' === $one_time_failed_consume_grant->get_error_code(), 'One-time consume failure uses stable error code.' );

$read_timeline = $stack['service']->audit_timeline( (string) $read_request['request_id'] );
$read_timeline_events = array_map(
	static function ( array $event ): string {
		return (string) ( $event['event_name'] ?? '' );
	},
	$read_timeline
);
npcink_governance_core_fail_closed_assert( in_array( 'read_request.created', $read_timeline_events, true ), 'Read request audit timeline records create.' );
npcink_governance_core_fail_closed_assert( in_array( 'read_request.approved', $read_timeline_events, true ), 'Read request audit timeline records approve.' );
npcink_governance_core_fail_closed_assert( in_array( 'read_request.preflighted', $read_timeline_events, true ), 'Read request audit timeline records preflight/grant.' );

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

$wpdb        = npcink_governance_core_fail_closed_reset_db();
$repository  = new \Npcink\GovernanceCore\Governance\Proposal_Repository();
$blocks_input = npcink_governance_core_fail_closed_update_post_blocks_input();
$proposal    = $repository->create(
	array(
		'ability_id' => 'npcink-abilities-toolkit/update-post-blocks',
		'title'      => 'Update Gutenberg blocks',
		'summary'    => 'Preserve Gutenberg block structure.',
		'input'      => $blocks_input,
		'preview'    => array(),
		'caller'     => array( 'source' => 'fault_injection' ),
	)
);
npcink_governance_core_fail_closed_assert( ! is_wp_error( $proposal ), 'update-post-blocks proposal is created.' );
$stored_block = $proposal['input']['blocks'][0] ?? array();
npcink_governance_core_fail_closed_assert( isset( $stored_block['blockName'] ) && ! isset( $stored_block['blockname'] ), 'update-post-blocks preserves blockName key case.' );
npcink_governance_core_fail_closed_assert( isset( $stored_block['innerBlocks'] ) && ! isset( $stored_block['innerblocks'] ), 'update-post-blocks preserves innerBlocks key case.' );
npcink_governance_core_fail_closed_assert( isset( $stored_block['innerHTML'] ) && ! isset( $stored_block['innerhtml'] ), 'update-post-blocks preserves innerHTML key case.' );
npcink_governance_core_fail_closed_assert( isset( $stored_block['innerContent'] ) && ! isset( $stored_block['innercontent'] ), 'update-post-blocks preserves innerContent key case.' );
npcink_governance_core_fail_closed_assert( '1120px' === (string) ( $stored_block['attrs']['layout']['contentSize'] ?? '' ), 'update-post-blocks preserves attrs contentSize key case.' );
npcink_governance_core_fail_closed_assert( '18px' === (string) ( $stored_block['attrs']['style']['typography']['fontSize'] ?? '' ), 'update-post-blocks preserves attrs fontSize key case.' );
npcink_governance_core_fail_closed_assert( '0' === (string) ( $stored_block['attrs']['style']['typography']['letterSpacing'] ?? '' ), 'update-post-blocks preserves attrs letterSpacing key case.' );
npcink_governance_core_fail_closed_assert( 'none' === (string) ( $stored_block['attrs']['style']['typography']['textTransform'] ?? '' ), 'update-post-blocks preserves attrs textTransform key case.' );
npcink_governance_core_fail_closed_assert( false !== strpos( (string) ( $stored_block['innerHTML'] ?? '' ), '<div' ), 'update-post-blocks preserves safe block innerHTML tags.' );
npcink_governance_core_fail_closed_assert( false === strpos( (string) ( $stored_block['innerHTML'] ?? '' ), '<script' ), 'update-post-blocks strips unsafe block innerHTML tags.' );
npcink_governance_core_fail_closed_assert( false !== strpos( (string) ( $stored_block['innerContent'][0] ?? '' ), '<div' ), 'update-post-blocks preserves safe block innerContent tags.' );
npcink_governance_core_fail_closed_assert( false === strpos( (string) ( $stored_block['innerContent'][0] ?? '' ), '<script' ), 'update-post-blocks strips unsafe block innerContent tags.' );

$batch_proposal = $repository->create(
	array(
		'ability_id' => 'plan_to_proposal_batch',
		'title'      => 'Batch update Gutenberg blocks',
		'summary'    => 'Preserve nested Gutenberg block structure.',
		'input'      => array(
			'write_actions' => array(
				array(
					'action_id'         => 'update_blocks',
					'target_ability_id' => 'npcink-abilities-toolkit/update-post-blocks',
					'input'             => $blocks_input,
				),
			),
			'dry_run'       => true,
			'commit'        => false,
		),
		'preview'    => array(),
		'caller'     => array( 'source' => 'fault_injection' ),
	)
);
npcink_governance_core_fail_closed_assert( ! is_wp_error( $batch_proposal ), 'batch update-post-blocks proposal is created.' );
$batch_block = $batch_proposal['input']['write_actions'][0]['input']['blocks'][0] ?? array();
npcink_governance_core_fail_closed_assert( isset( $batch_block['blockName'] ) && ! isset( $batch_block['blockname'] ), 'batch update-post-blocks preserves blockName key case.' );
npcink_governance_core_fail_closed_assert( '1120px' === (string) ( $batch_block['attrs']['layout']['contentSize'] ?? '' ), 'batch update-post-blocks preserves nested attrs key case.' );

$wpdb = npcink_governance_core_fail_closed_reset_db();
$wpdb->fail_insert_event_names[] = 'proposal.policy_evaluated';
$stack  = npcink_governance_core_fail_closed_proposal_stack();
$result = $stack['service']->create( npcink_governance_core_fail_closed_payload() );
npcink_governance_core_fail_closed_assert( is_wp_error( $result ), 'Policy decision audit failure returns WP_Error.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_policy_decision_audit_failed' === $result->get_error_code(), 'Policy decision audit failure uses stable error code.' );
npcink_governance_core_fail_closed_assert( 0 === count( $wpdb->rows( $proposal_table ) ), 'Unaudited policy decision deletes the proposal row.' );
npcink_governance_core_fail_closed_assert( \Npcink\GovernanceCore\Governance\Approval_Policy_Evaluator::MODE_MANUAL === \Npcink\GovernanceCore\Governance\Approval_Policy_Evaluator::sanitize_policy_mode( 'dry_run_guarded' ), 'Removed dry-run guarded mode falls back to manual.' );
npcink_governance_core_fail_closed_assert( \Npcink\GovernanceCore\Governance\Approval_Policy_Evaluator::MODE_MANUAL === \Npcink\GovernanceCore\Governance\Approval_Policy_Evaluator::sanitize_policy_mode( 'local_guarded' ), 'Removed local guarded mode falls back to manual.' );

$wpdb = npcink_governance_core_fail_closed_reset_db();
update_option( \Npcink\GovernanceCore\Governance\Approval_Policy_Evaluator::OPTION_POLICY_MODE, \Npcink\GovernanceCore\Governance\Approval_Policy_Evaluator::MODE_SMART_GUARDED, false );
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
npcink_governance_core_fail_closed_assert( ! is_wp_error( $proposal ), 'Smart guarded cleanup proposal is created.' );
npcink_governance_core_fail_closed_assert( 'approved' === (string) ( $proposal['status'] ?? '' ), 'Smart guarded cleanup proposal is auto-approved.' );
npcink_governance_core_fail_closed_assert( 'auto_approved' === (string) ( $proposal['policy_decision'] ?? '' ), 'Smart guarded cleanup records auto-approved decision.' );
npcink_governance_core_fail_closed_assert( 'trusted_local' === (string) ( $proposal['policy_profile'] ?? '' ), 'Smart guarded cleanup records trusted_local profile.' );
npcink_governance_core_fail_closed_assert( in_array( 'smart_guarded_cleanup_auto_approved', (array) ( $proposal['policy_reasons'] ?? array() ), true ), 'Smart guarded cleanup records stable auto approval reason.' );
$auto_approval_events = array_filter(
	$wpdb->rows( $audit_table ),
	static function ( array $row ): bool {
		return 'proposal.auto_approved' === (string) ( $row['event_name'] ?? '' );
	}
);
npcink_governance_core_fail_closed_assert( 1 === count( $auto_approval_events ), 'Smart guarded cleanup writes proposal.auto_approved audit.' );

$wpdb = npcink_governance_core_fail_closed_reset_db();
update_option( \Npcink\GovernanceCore\Governance\Approval_Policy_Evaluator::OPTION_POLICY_MODE, \Npcink\GovernanceCore\Governance\Approval_Policy_Evaluator::MODE_SMART_GUARDED, false );
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
$proposal = $stack['service']->create(
	array(
		'ability_id' => 'npcink-abilities-toolkit/create-draft',
		'title'      => 'Smart guarded draft proposal',
		'summary'    => 'Create one draft only.',
		'input'      => array(
			'post_type'       => 'post',
			'status'          => 'draft',
			'title'           => 'Smart guarded draft',
			'content'         => '<p>Draft content.</p>',
			'dry_run'         => true,
			'commit'          => false,
			'idempotency_key' => 'local-guarded-draft',
		),
		'preview'    => array( 'after_suggestion' => 'Draft content' ),
		'caller'     => array( 'source' => 'fault_injection' ),
	)
);
npcink_governance_core_fail_closed_assert( ! is_wp_error( $proposal ), 'Smart guarded create-draft proposal is created.' );
npcink_governance_core_fail_closed_assert( 'approved' === (string) ( $proposal['status'] ?? '' ), 'Smart guarded create-draft proposal is auto-approved.' );
npcink_governance_core_fail_closed_assert( 'auto_approved' === (string) ( $proposal['policy_decision'] ?? '' ), 'Smart guarded create-draft records auto-approved decision.' );
npcink_governance_core_fail_closed_assert( 'trusted_local' === (string) ( $proposal['policy_profile'] ?? '' ), 'Smart guarded create-draft records trusted_local profile.' );
npcink_governance_core_fail_closed_assert( in_array( 'smart_guarded_create_draft_auto_approved', (array) ( $proposal['policy_reasons'] ?? array() ), true ), 'Smart guarded create-draft records stable auto approval reason.' );

$wpdb = npcink_governance_core_fail_closed_reset_db();
update_option( \Npcink\GovernanceCore\Governance\Approval_Policy_Evaluator::OPTION_POLICY_MODE, \Npcink\GovernanceCore\Governance\Approval_Policy_Evaluator::MODE_SMART_GUARDED, false );
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
$proposal = $stack['service']->create(
	array(
		'ability_id' => 'npcink-abilities-toolkit/create-draft',
		'title'      => 'Smart guarded publish proposal',
		'summary'    => 'Publish must not auto approve.',
		'input'      => array(
			'post_type'       => 'post',
			'status'          => 'publish',
			'title'           => 'Blocked publish',
			'content'         => '<p>Published content.</p>',
			'dry_run'         => true,
			'commit'          => false,
			'idempotency_key' => 'local-guarded-publish',
		),
		'preview'    => array( 'after_suggestion' => 'Published content' ),
		'caller'     => array( 'source' => 'fault_injection' ),
	)
);
npcink_governance_core_fail_closed_assert( ! is_wp_error( $proposal ), 'Smart guarded publish create-draft proposal is still created for review.' );
npcink_governance_core_fail_closed_assert( 'pending' === (string) ( $proposal['status'] ?? '' ), 'Smart guarded publish create-draft remains pending.' );
npcink_governance_core_fail_closed_assert( 'manual_required' === (string) ( $proposal['policy_decision'] ?? '' ), 'Smart guarded publish create-draft remains manual.' );
npcink_governance_core_fail_closed_assert( in_array( 'guarded_create_draft_rejected_status', (array) ( $proposal['policy_reasons'] ?? array() ), true ), 'Smart guarded publish create-draft records status rejection reason.' );

$wpdb = npcink_governance_core_fail_closed_reset_db();
update_option( \Npcink\GovernanceCore\Governance\Approval_Policy_Evaluator::OPTION_POLICY_MODE, \Npcink\GovernanceCore\Governance\Approval_Policy_Evaluator::MODE_DEV_ALLOW_ALL, false );
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
$proposal = $stack['service']->create( npcink_governance_core_fail_closed_governance_payload( 'npcink-abilities-toolkit/approve-comment' ) );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $proposal ), 'Development allow-all disabled proposal is still created for review.' );
npcink_governance_core_fail_closed_assert( 'pending' === (string) ( $proposal['status'] ?? '' ), 'Development allow-all without constant remains pending.' );
npcink_governance_core_fail_closed_assert( 'manual_required' === (string) ( $proposal['policy_decision'] ?? '' ), 'Development allow-all without constant remains manual.' );
npcink_governance_core_fail_closed_assert( in_array( 'dev_allow_all_rejected_disabled', (array) ( $proposal['policy_reasons'] ?? array() ), true ), 'Development allow-all without constant records disabled reason.' );

if ( ! defined( 'NPCINK_GOVERNANCE_CORE_ENABLE_DEV_ALLOW_ALL' ) ) {
	define( 'NPCINK_GOVERNANCE_CORE_ENABLE_DEV_ALLOW_ALL', true );
}

$wpdb = npcink_governance_core_fail_closed_reset_db();
update_option( \Npcink\GovernanceCore\Governance\Approval_Policy_Evaluator::OPTION_POLICY_MODE, \Npcink\GovernanceCore\Governance\Approval_Policy_Evaluator::MODE_DEV_ALLOW_ALL, false );
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
$proposal = $stack['service']->create( npcink_governance_core_fail_closed_governance_payload( 'npcink-abilities-toolkit/approve-comment' ) );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $proposal ), 'Development allow-all enabled proposal is created.' );
npcink_governance_core_fail_closed_assert( 'approved' === (string) ( $proposal['status'] ?? '' ), 'Development allow-all enabled proposal is auto-approved.' );
npcink_governance_core_fail_closed_assert( 'auto_approved' === (string) ( $proposal['policy_decision'] ?? '' ), 'Development allow-all records auto-approved decision.' );
npcink_governance_core_fail_closed_assert( in_array( 'dev_allow_all_auto_approved', (array) ( $proposal['policy_reasons'] ?? array() ), true ), 'Development allow-all records stable auto approval reason.' );
npcink_governance_core_fail_closed_assert( in_array( 'commit_preflight_still_required', (array) ( $proposal['policy_reasons'] ?? array() ), true ), 'Development allow-all records commit preflight requirement.' );
$auto_approval_events = array_filter(
	$wpdb->rows( $audit_table ),
	static function ( array $row ): bool {
		return 'proposal.auto_approved' === (string) ( $row['event_name'] ?? '' );
	}
);
npcink_governance_core_fail_closed_assert( 1 === count( $auto_approval_events ), 'Development allow-all writes proposal.auto_approved audit.' );

$wpdb = npcink_governance_core_fail_closed_reset_db();
update_option( \Npcink\GovernanceCore\Governance\Approval_Policy_Evaluator::OPTION_POLICY_MODE, \Npcink\GovernanceCore\Governance\Approval_Policy_Evaluator::MODE_SMART_GUARDED, false );
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

	$signed_client_fingerprint = 'sha256:' . str_repeat( 'a', 64 );
	\Npcink\GovernanceCore\Security\Request_Context::set_app(
		array(
			'app_id'                    => 'adapter_app_preflight',
			'key_id'                    => 'adapter_key_preflight',
			'caller_type'               => 'openclaw_adapter',
			'scope'                     => 'commit:preflight',
			'scopes'                    => array( 'commit:preflight' ),
			'route_family'              => 'commit_preflight',
			'signed_client_fingerprint' => $signed_client_fingerprint,
		)
	);
	$preflight = $stack['preflight']->preflight( (string) $proposal['proposal_id'] );
	npcink_governance_core_fail_closed_assert( ! is_wp_error( $preflight ), $ability_id . ' approved proposal passes commit preflight.' );
	npcink_governance_core_fail_closed_assert( false === (bool) ( $preflight['commit_execution'] ?? true ), $ability_id . ' preflight does not execute commits.' );
	npcink_governance_core_fail_closed_assert( $signed_client_fingerprint === (string) ( $preflight['approval_context']['signed_client_fingerprint'] ?? '' ), $ability_id . ' approval context binds signed client fingerprint.' );
	npcink_governance_core_fail_closed_assert( $signed_client_fingerprint === (string) ( $preflight['execution_handoff']['client_key_fingerprint'] ?? '' ), $ability_id . ' execution handoff binds client key fingerprint alias.' );
	npcink_governance_core_fail_closed_assert( '' !== (string) ( $preflight['approval_context']['expires_at'] ?? '' ), $ability_id . ' approval context carries handoff expiry.' );
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
npcink_governance_core_fail_closed_assert( ! is_wp_error( $proposal ), 'Execution record proposal is created.' );
$approved = $stack['service']->approve( (string) $proposal['proposal_id'], array( 'reason' => 'execution_record' ) );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $approved ), 'Execution record proposal is approved.' );
$preflight = $stack['preflight']->preflight( (string) $proposal['proposal_id'] );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $preflight ), 'Execution record proposal passes preflight.' );
$recorded = $stack['service']->record_execution_result(
	(string) $proposal['proposal_id'],
	array(
		'execution_status'    => 'succeeded',
		'correlation_id'      => (string) ( $preflight['correlation_id'] ?? '' ),
		'approved_input_hash' => (string) ( $preflight['approval_context']['approved_input_hash'] ?? '' ),
		'adapter_request_id'  => 'adapter-execution-record-smoke',
		'execution_mode'      => 'single_post',
		'executed_count'      => 1,
		'failed_count'        => 0,
	)
);
npcink_governance_core_fail_closed_assert( ! is_wp_error( $recorded ) && 'executed' === (string) ( $recorded['status'] ?? '' ), 'Execution record moves approved proposal to executed.' );
npcink_governance_core_fail_closed_assert( 1 === count( npcink_governance_core_fail_closed_audit_rows( (string) $proposal['proposal_id'], 'proposal.executed' ) ), 'Execution record success is audited.' );

$wpdb     = npcink_governance_core_fail_closed_reset_db();
$stack    = npcink_governance_core_fail_closed_governance_stack();
$proposal = $stack['service']->create( npcink_governance_core_fail_closed_governance_payload( 'npcink-abilities-toolkit/update-post' ) );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $proposal ), 'Execution record rollback proposal is created.' );
$approved = $stack['service']->approve( (string) $proposal['proposal_id'], array( 'reason' => 'execution_record_rollback' ) );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $approved ), 'Execution record rollback proposal is approved.' );
$preflight = $stack['preflight']->preflight( (string) $proposal['proposal_id'] );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $preflight ), 'Execution record rollback proposal passes preflight.' );
$wpdb->fail_insert_event_names[] = 'proposal.executed';
$recorded = $stack['service']->record_execution_result(
	(string) $proposal['proposal_id'],
	array(
		'execution_status'    => 'succeeded',
		'correlation_id'      => (string) ( $preflight['correlation_id'] ?? '' ),
		'approved_input_hash' => (string) ( $preflight['approval_context']['approved_input_hash'] ?? '' ),
		'adapter_request_id'  => 'adapter-execution-record-rollback',
		'execution_mode'      => 'single_post',
		'executed_count'      => 1,
		'failed_count'        => 0,
	)
);
npcink_governance_core_fail_closed_assert( is_wp_error( $recorded ), 'Execution record audit failure returns WP_Error.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_execution_record_audit_failed' === $recorded->get_error_code(), 'Execution record audit failure uses stable error code.' );
$rolled_back = $stack['proposals']->find( (string) $proposal['proposal_id'] );
npcink_governance_core_fail_closed_assert( is_array( $rolled_back ) && 'approved' === $rolled_back['status'], 'Execution record audit failure rolls status back to approved.' );

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
				'batch_review_summary' => array(
					'summary_version'      => 'core-batch-review-summary-v1',
					'operator_next_action' => 'resolve_blocked_items_before_commit_preflight',
					'core_execution'       => false,
					'commit_execution'     => false,
					'api_key'              => 'BATCH_SUMMARY_SECRET_SENTINEL',
					'queue_lease'          => 'not-core-owned-runtime-state',
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
$blocked_data = is_array( $blocked->get_error_data() ) ? $blocked->get_error_data() : array();
npcink_governance_core_fail_closed_assert( 'core-batch-review-summary-v1' === (string) ( $blocked_data['proposal_item_preflight']['batch_review_summary']['summary_version'] ?? '' ), 'Blocked item preflight returns batch review summary for operator recovery.' );
npcink_governance_core_fail_closed_assert( 'resolve_blocked_items_before_commit_preflight' === (string) ( $blocked_data['proposal_item_preflight']['batch_review_summary']['operator_next_action'] ?? '' ), 'Blocked item preflight returns operator next action.' );
npcink_governance_core_fail_closed_assert( false === strpos( (string) wp_json_encode( $blocked_data['proposal_item_preflight']['batch_review_summary'] ?? array() ), 'BATCH_SUMMARY_SECRET_SENTINEL' ), 'Blocked item preflight does not leak secret-shaped batch summary fields.' );
npcink_governance_core_fail_closed_assert( false === array_key_exists( 'queue_lease', (array) ( $blocked_data['proposal_item_preflight']['batch_review_summary'] ?? array() ) ), 'Blocked item preflight does not expose queue-like batch summary fields.' );
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
