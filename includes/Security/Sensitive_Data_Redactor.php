<?php
/**
 * Shared sensitive data redaction.
 *
 * @package NpcinkGovernanceCore
 */

namespace Npcink\GovernanceCore\Security;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sanitizes structured metadata while redacting secret-shaped values.
 */
final class Sensitive_Data_Redactor {
	/**
	 * Sanitizes a structured payload recursively.
	 *
	 * @param mixed $value Raw value.
	 * @return mixed
	 */
	public static function sanitize_payload( $value ) {
		if ( is_array( $value ) ) {
			$clean = array();
			foreach ( $value as $key => $item ) {
				$key_string = (string) $key;
				$clean_key  = sanitize_key( $key_string );
				if ( '' === $clean_key ) {
					continue;
				}
				if ( self::is_secret_key( $key_string ) ) {
					$clean[ $clean_key ] = '[redacted]';
					continue;
				}
				$clean[ $clean_key ] = self::sanitize_payload( $item );
			}

			return $clean;
		}

		if ( is_string( $value ) ) {
			return self::redact_secret_string( sanitize_textarea_field( $value ) );
		}

		if ( is_bool( $value ) || is_int( $value ) || is_float( $value ) || null === $value ) {
			return $value;
		}

		return sanitize_text_field( (string) $value );
	}

	/**
	 * Returns whether a metadata key usually carries secret material.
	 *
	 * @param string $key Raw key.
	 * @return bool
	 */
	public static function is_secret_key( string $key ): bool {
		$key = strtolower( str_replace( '-', '_', $key ) );
		if ( 0 === strpos( $key, 'read_authorization' ) || 'authorization_mode' === $key ) {
			return false;
		}
		if ( in_array( $key, array( 'authorization', 'authorization_header', 'cookie', 'cookies', 'credential', 'credentials', 'password', 'secret', 'token' ), true ) ) {
			return true;
		}

		return 1 === preg_match( '/(^|_)(api_key|access_token|refresh_token|bearer_token|auth_token|secret|secret_key|client_secret|private_key|application_password|credential|credentials|password|token|cookie)($|_)/', $key );
	}

	/**
	 * Redacts obvious inline secret values from a scalar string.
	 *
	 * @param string $value Sanitized string.
	 * @return string
	 */
	public static function redact_secret_string( string $value ): string {
		$value = preg_replace( '/Bearer\s+[A-Za-z0-9._~+\/=-]+/i', 'Bearer [redacted]', $value );
		$value = preg_replace( '/(application password|private key|authorization header|authorization|cookie|api key|token|secret)\s*[:=]\s*\S+/i', '$1=[redacted]', is_string( $value ) ? $value : '' );

		return is_string( $value ) ? $value : '';
	}
}
