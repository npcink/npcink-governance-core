<?php
/**
 * Plugin Name: Npcink Governance Core
 * Description: Npcink AI governance layer for WordPress operations.
 * Version: 0.1.0
 * Requires at least: 7.0
 * Requires PHP: 8.0
 * Author: Npcink
 * License: GPL-2.0-or-later
 * Text Domain: npcink-governance-core
 * Domain Path: /languages
 *
 * @package NpcinkGovernanceCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'NPCINK_GOVERNANCE_CORE_VERSION', '0.1.0' );
define( 'NPCINK_GOVERNANCE_CORE_FILE', __FILE__ );
define( 'NPCINK_GOVERNANCE_CORE_DIR', plugin_dir_path( __FILE__ ) );

require_once NPCINK_GOVERNANCE_CORE_DIR . 'includes/Autoloader.php';

\Npcink\GovernanceCore\Autoloader::register();

register_activation_hook( __FILE__, array( \Npcink\GovernanceCore\Plugin::class, 'activate' ) );

add_action(
	'plugins_loaded',
	static function (): void {
		\Npcink\GovernanceCore\Plugin::instance()->register();
	}
);
