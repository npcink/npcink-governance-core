<?php
/**
 * REST response formatting helpers.
 *
 * @package NpcinkGovernanceCore
 */

namespace Npcink\GovernanceCore\Rest;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Normalizes REST response values that consumers parse programmatically.
 */
final class Rest_Format {
	/**
	 * Converts a stored UTC datetime string to an ISO8601 timestamp with a
	 * timezone designator. Values that cannot be parsed are returned as-is.
	 *
	 * @param string $value Stored UTC datetime, or an already-ISO8601 value.
	 * @return string
	 */
	public static function iso8601( string $value ): string {
		$timestamp = strtotime( $value );

		return false === $timestamp ? $value : gmdate( 'c', $timestamp );
	}

	/**
	 * Formats known timestamp fields on one row.
	 *
	 * @param array<string,mixed> $row Row.
	 * @param array<int,string>   $fields Timestamp field names present on the row.
	 * @return array<string,mixed>
	 */
	public static function row( array $row, array $fields ): array {
		foreach ( $fields as $field ) {
			if ( isset( $row[ $field ] ) && is_string( $row[ $field ] ) && '' !== $row[ $field ] ) {
				$row[ $field ] = self::iso8601( (string) $row[ $field ] );
			}
		}

		return $row;
	}

	/**
	 * Formats known timestamp fields on a list of rows.
	 *
	 * @param array<int,array<string,mixed>> $rows Rows.
	 * @param array<int,string>              $fields Timestamp field names.
	 * @return array<int,array<string,mixed>>
	 */
	public static function rows( array $rows, array $fields ): array {
		return array_map(
			static function ( array $row ) use ( $fields ): array {
				return self::row( $row, $fields );
			},
			$rows
		);
	}
}
