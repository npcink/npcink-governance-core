<?php
/**
 * Plugin Name: Magick AI Core
 * Description: WordPress AI operation governance layer for ability intake, proposals, approval boundaries, and audit logs.
 * Version: 0.1.0
 * Requires at least: 6.9
 * Requires PHP: 7.4
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

register_activation_hook( __FILE__, array( \MagickAI\Core\Plugin::class, 'activate' ) );

add_action(
	'plugins_loaded',
	static function (): void {
		\MagickAI\Core\Plugin::instance()->register();
	}
);
