<?php
/**
 * REST permission helpers.
 *
 * @package MagickAICore
 */

namespace MagickAI\Core\Rest;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shared REST permission checks.
 */
final class Rest_Permissions {
	/**
	 * Checks whether the current user can manage Core governance.
	 *
	 * @return bool
	 */
	public static function can_manage(): bool {
		return current_user_can( 'manage_options' );
	}
}

