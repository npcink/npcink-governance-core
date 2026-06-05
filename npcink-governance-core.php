<?php
/**
 * Plugin Name: Npcink Governance Core
 * Description: WordPress AI operation governance layer for ability intake, proposals, approval boundaries, and audit logs.
 * Version: 0.1.0
 * Requires at least: 7.0
 * Requires PHP: 8.0
 * Author: Npcink
 * License: GPL-2.0-or-later
 * Text Domain: npcink-governance-core
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

if ( ! function_exists( 'npcink_governance_core_get_media_derivative_settings' ) ) {
	/**
	 * Returns the local Core media derivative policy summary.
	 *
	 * @return array<string,mixed>
	 */
	function npcink_governance_core_get_media_derivative_settings(): array {
		return \Npcink\GovernanceCore\Plugin::instance()->media_derivative_settings()->summary();
	}
}

if ( ! function_exists( 'npcink_governance_core_build_media_derivative_ability_input' ) ) {
	/**
	 * Builds one-run input for npcink-abilities-toolkit/build-media-derivative-cloud-request.
	 *
	 * @param array<string,mixed> $overrides One-run overrides.
	 * @return array<string,mixed>
	 */
	function npcink_governance_core_build_media_derivative_ability_input( array $overrides = array() ): array {
		return \Npcink\GovernanceCore\Plugin::instance()->media_derivative_settings()->ability_input( $overrides );
	}
}

register_activation_hook( __FILE__, array( \Npcink\GovernanceCore\Plugin::class, 'activate' ) );

add_action(
	'plugins_loaded',
	static function (): void {
		\Npcink\GovernanceCore\Plugin::instance()->register();
	}
);
