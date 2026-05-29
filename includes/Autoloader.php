<?php
/**
 * Minimal class autoloader.
 *
 * @package MagickAICore
 */

namespace MagickAI\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Loads plugin classes without requiring Composer at runtime.
 */
final class Autoloader {
	/**
	 * Registers the autoloader.
	 *
	 * @return void
	 */
	public static function register(): void {
		spl_autoload_register( array( __CLASS__, 'load' ) );
	}

	/**
	 * Loads a class when it belongs to this plugin namespace.
	 *
	 * @param string $class Class name.
	 * @return void
	 */
	public static function load( string $class ): void {
		$prefix = __NAMESPACE__ . '\\';
		if ( 0 !== strpos( $class, $prefix ) ) {
			return;
		}

		$relative = substr( $class, strlen( $prefix ) );
		$path     = MAGICK_AI_CORE_DIR . 'includes/' . str_replace( '\\', '/', $relative ) . '.php';

		if ( is_readable( $path ) ) {
			require_once $path;
		}
	}
}

