<?php
/** Wordpress stubs test module. @package NpcinkGovernanceCore */

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
		 * Headers.
		 *
		 * @var array<string,string>
		 */
		private $headers;

		/**
		 * Constructor.
		 *
		 * @param array<string,mixed>  $params Parameters.
		 * @param array<string,string> $headers Headers.
		 */
		public function __construct( array $params = array(), array $headers = array() ) {
			$this->params  = $params;
			$this->headers = array();
			foreach ( $headers as $key => $value ) {
				$this->headers[ strtolower( str_replace( '_', '-', (string) $key ) ) ] = (string) $value;
			}
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
			$key = strtolower( str_replace( '_', '-', $key ) );
			return (string) ( $this->headers[ $key ] ?? '' );
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

if ( ! function_exists( 'sanitize_file_name' ) ) {
	/**
	 * File name sanitizer stub.
	 *
	 * @param mixed $value Value.
	 * @return string
	 */
	function sanitize_file_name( $value ): string {
		return (string) preg_replace( '/[^A-Za-z0-9._-]/', '', basename( (string) $value ) );
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

