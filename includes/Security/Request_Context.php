<?php
/**
 * Request-scoped auth context.
 *
 * @package MagickAICore
 */

namespace MagickAI\Core\Security;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stores the current REST authorization context for audit attribution.
 */
final class Request_Context {
	/**
	 * App context.
	 *
	 * @var array<string,mixed>
	 */
	private static $app = array();

	/**
	 * Clears current context.
	 *
	 * @return void
	 */
	public static function clear(): void {
		self::$app = array();
	}

	/**
	 * Sets app context.
	 *
	 * @param array<string,mixed> $context Context.
	 * @return void
	 */
	public static function set_app( array $context ): void {
		$scopes = array_values( array_unique( array_map( 'sanitize_text_field', (array) ( $context['scopes'] ?? array() ) ) ) );
		self::$app = array(
			'auth_type'      => 'app_key',
			'app_id'         => sanitize_text_field( (string) ( $context['app_id'] ?? '' ) ),
			'key_id'         => sanitize_text_field( (string) ( $context['key_id'] ?? '' ) ),
			'caller_type'    => sanitize_key( (string) ( $context['caller_type'] ?? 'external_app' ) ),
			'scope'          => sanitize_text_field( (string) ( $context['scope'] ?? '' ) ),
			'scopes'         => $scopes,
			'scope_decision' => sanitize_key( (string) ( $context['scope_decision'] ?? 'allowed' ) ),
			'route_family'   => sanitize_key( (string) ( $context['route_family'] ?? '' ) ),
		);
	}

	/**
	 * Updates the current scope decision.
	 *
	 * @param string $decision Decision label.
	 * @return void
	 */
	public static function mark_scope_decision( string $decision ): void {
		if ( ! self::is_app() ) {
			return;
		}

		self::$app['scope_decision'] = sanitize_key( $decision );
	}

	/**
	 * Returns whether current request is app-authenticated.
	 *
	 * @return bool
	 */
	public static function is_app(): bool {
		return '' !== (string) ( self::$app['app_id'] ?? '' );
	}

	/**
	 * Returns whether the current request carries a specific app scope.
	 *
	 * @param string $scope Scope.
	 * @return bool
	 */
	public static function has_scope( string $scope ): bool {
		if ( ! self::is_app() ) {
			return false;
		}

		if ( $scope === (string) ( self::$app['scope'] ?? '' ) ) {
			return true;
		}

		return in_array( $scope, (array) ( self::$app['scopes'] ?? array() ), true );
	}

	/**
	 * Returns audit metadata.
	 *
	 * @return array<string,mixed>
	 */
	public static function audit_metadata(): array {
		if ( ! self::is_app() ) {
			return array();
		}

		return self::$app;
	}
}
