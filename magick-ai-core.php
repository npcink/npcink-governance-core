<?php
/**
 * Plugin Name: Magick AI Core
 * Description: WordPress AI operation governance layer for ability intake, proposals, approval boundaries, and audit logs.
 * Version: 0.1.0
 * Requires at least: 7.0
 * Requires PHP: 8.0
 * Author: Magick AI
 * License: GPL-2.0-or-later
 * Text Domain: magick-ai-core
 *
 * @package MagickAICore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MAGICK_AI_CORE_VERSION', '0.1.0' );
define( 'MAGICK_AI_CORE_FILE', __FILE__ );
define( 'MAGICK_AI_CORE_DIR', plugin_dir_path( __FILE__ ) );

require_once MAGICK_AI_CORE_DIR . 'includes/Autoloader.php';

\MagickAI\Core\Autoloader::register();

if ( ! function_exists( 'magick_ai_core_get_media_derivative_settings' ) ) {
	/**
	 * Returns the local Core media derivative policy summary.
	 *
	 * @return array<string,mixed>
	 */
	function magick_ai_core_get_media_derivative_settings(): array {
		return \MagickAI\Core\Plugin::instance()->media_derivative_settings()->summary();
	}
}

if ( ! function_exists( 'magick_ai_core_build_media_derivative_ability_input' ) ) {
	/**
	 * Builds one-run input for magick-ai/build-media-derivative-cloud-request.
	 *
	 * @param array<string,mixed> $overrides One-run overrides.
	 * @return array<string,mixed>
	 */
	function magick_ai_core_build_media_derivative_ability_input( array $overrides = array() ): array {
		return \MagickAI\Core\Plugin::instance()->media_derivative_settings()->ability_input( $overrides );
	}
}

register_activation_hook( __FILE__, array( \MagickAI\Core\Plugin::class, 'activate' ) );

add_action(
	'plugins_loaded',
	static function (): void {
		\MagickAI\Core\Plugin::instance()->register();
	}
);
