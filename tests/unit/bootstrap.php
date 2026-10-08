<?php
/**
 * Bootstrap for Core behavioral unit tests.
 *
 * Loads Composer autoloading on top of minimal WordPress function and
 * WP_Error stubs so pure governance logic can be exercised without a
 * WordPress runtime. Behavioral tests live here; boundary and drift checks
 * stay in tests/run.php.
 *
 * @package NpcinkGovernanceCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__, 2 ) . '/tests/wp-stub/' );
}

if ( ! class_exists( 'WP_Error' ) ) {
	/**
	 * Minimal WP_Error stub for unit tests.
	 */
	class WP_Error {
		/** @var string */
		private $code;
		/** @var string */
		private $message;
		/** @var mixed */
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
		 * Returns the error message.
		 *
		 * @return string
		 */
		public function get_error_message(): string {
			return $this->message;
		}

		/**
		 * Returns the error data.
		 *
		 * @return mixed
		 */
		public function get_error_data() {
			return $this->data;
		}
	}
}

if ( ! function_exists( 'is_wp_error' ) ) {
	/**
	 * Reports whether a value is a WP_Error.
	 *
	 * @param mixed $thing Value.
	 * @return bool
	 */
	function is_wp_error( $thing ): bool {
		return $thing instanceof WP_Error;
	}
}

if ( ! function_exists( '__' ) ) {
	/**
	 * No-op translation stub.
	 *
	 * @param string $text Text.
	 * @param string $domain Domain.
	 * @return string
	 */
	function __( string $text, string $domain = 'default' ): string {
		return $text;
	}
}

if ( ! function_exists( 'sanitize_key' ) ) {
	/**
	 * Minimal sanitize_key stub.
	 *
	 * @param string $key Raw key.
	 * @return string
	 */
	function sanitize_key( string $key ): string {
		$key = strtolower( $key );
		return preg_replace( '/[^a-z0-9_\-]/', '', $key ) ?? '';
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	/**
	 * Minimal sanitize_text_field stub.
	 *
	 * @param string $text Raw text.
	 * @return string
	 */
	function sanitize_text_field( string $text ): string {
		return trim( strip_tags( $text ) );
	}
}

if ( ! function_exists( 'absint' ) ) {
	/**
	 * Minimal absint stub.
	 *
	 * @param mixed $value Value.
	 * @return int
	 */
	function absint( $value ): int {
		return abs( (int) $value );
	}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	/**
	 * Minimal wp_json_encode stub.
	 *
	 * @param mixed $value Value.
	 * @return string|false
	 */
	function wp_json_encode( $value ) {
		$encoded = json_encode( $value );
		return false === $encoded ? false : $encoded;
	}
}

if ( ! function_exists( 'sanitize_file_name' ) ) {
	/**
	 * Minimal sanitize_file_name stub.
	 *
	 * @param string $name Raw filename.
	 * @return string
	 */
	function sanitize_file_name( string $name ): string {
		$name = preg_replace( '/[^A-Za-z0-9._\-]/', '-', $name ) ?? '';
		return trim( $name, '.-' );
	}
}

if ( ! function_exists( 'current_time' ) ) {
	/**
	 * current_time stub always returning UTC; unit tests never depend on a
	 * site timezone offset.
	 *
	 * @param string $type Type.
	 * @param int    $gmt GMT flag.
	 * @return string|int
	 */
	function current_time( string $type = 'mysql', int $gmt = 0 ) {
		if ( 'timestamp' === $type ) {
			return time();
		}
		return gmdate( 'Y-m-d H:i:s' );
	}
}

if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
	define( 'HOUR_IN_SECONDS', 3600 );
}

if ( ! defined( 'OBJECT' ) ) {
	define( 'OBJECT', 'OBJECT' );
}
if ( ! defined( 'ARRAY_A' ) ) {
	define( 'ARRAY_A', 'ARRAY_A' );
}
if ( ! defined( 'ARRAY_N' ) ) {
	define( 'ARRAY_N', 'ARRAY_N' );
}

/**
 * Mutable option store shared by the get_option/update_option stubs.
 *
 * @var array<string,mixed>
 */
$GLOBALS['npcink_unit_options'] = array();

if ( ! function_exists( 'get_option' ) ) {
	/**
	 * get_option stub backed by the mutable option store.
	 *
	 * @param string $name Option name.
	 * @param mixed  $default Default.
	 * @return mixed
	 */
	function get_option( string $name, $default = false ) {
		return array_key_exists( $name, $GLOBALS['npcink_unit_options'] ) ? $GLOBALS['npcink_unit_options'][ $name ] : $default;
	}
}

if ( ! function_exists( 'update_option' ) ) {
	/**
	 * update_option stub backed by the mutable option store.
	 *
	 * @param string $name Option name.
	 * @param mixed  $value Value.
	 * @return bool
	 */
	function update_option( string $name, $value ): bool {
		$GLOBALS['npcink_unit_options'][ $name ] = $value;
		return true;
	}
}

/**
 * Mutable transient store shared by the get_transient/set_transient stubs.
 *
 * @var array<string,mixed>
 */
$GLOBALS['npcink_unit_transients'] = array();

if ( ! function_exists( 'get_transient' ) ) {
	/**
	 * get_transient stub backed by the mutable transient store.
	 *
	 * @param string $name Transient name.
	 * @return mixed
	 */
	function get_transient( string $name ) {
		return array_key_exists( $name, $GLOBALS['npcink_unit_transients'] ) ? $GLOBALS['npcink_unit_transients'][ $name ] : false;
	}
}

if ( ! function_exists( 'set_transient' ) ) {
	/**
	 * set_transient stub backed by the mutable transient store.
	 *
	 * @param string $name Transient name.
	 * @param mixed  $value Value.
	 * @param int    $expiration Expiration.
	 * @return bool
	 */
	function set_transient( string $name, $value, int $expiration = 0 ): bool {
		$GLOBALS['npcink_unit_transients'][ $name ] = $value;
		return true;
	}
}

$composer_autoload = dirname( __DIR__, 2 ) . '/vendor/autoload.php';
if ( ! file_exists( $composer_autoload ) ) {
	fwrite( STDERR, "vendor/autoload.php is missing; run `composer install` before the unit suite.\n" );
	exit( 1 );
}
require_once $composer_autoload;
