<?php
/**
 * Local observability event bridge.
 *
 * @package MagickAICore
 */

namespace MagickAI\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Emits metadata-only events for optional Cloud Addon collection.
 */
final class Observability {
	/**
	 * Emits a local-only observability event.
	 *
	 * @param string              $event_kind Event kind.
	 * @param array<string,mixed> $payload Event details.
	 * @return void
	 */
	public static function emit( string $event_kind, array $payload = array() ): void {
		if ( ! function_exists( 'do_action' ) ) {
			return;
		}

		$base = array(
			'schema_version' => '2026-06-01',
			'plugin_slug'    => 'npcink-governance-core',
			'plugin_version' => defined( 'MAGICK_AI_CORE_VERSION' ) ? MAGICK_AI_CORE_VERSION : '',
			'source'         => 'local',
			'event_kind'     => $event_kind,
			'emitted_at'     => gmdate( 'c' ),
		);

		do_action(
			'magick_ai_observability_event',
			array_merge(
				self::sanitize_payload( $payload ),
				$base
			)
		);
	}

	/**
	 * Keeps observability metadata bounded and safe for optional collection.
	 *
	 * @param array<string,mixed> $payload Event details.
	 * @return array<string,mixed>
	 */
	private static function sanitize_payload( array $payload ): array {
		$allowed = array(
				'status'         => 'status',
				'event_id'       => 'key',
				'error_code'     => 'key',
			'status_detail'  => 'key',
			'latency_ms'     => 'int',
			'ability_id'     => 'text',
			'proposal_id'    => 'text',
			'correlation_id' => 'text',
			'proposal_count' => 'int',
			'blocked_count'  => 'int',
			'deduplicated'   => 'bool',
		);
		$clean = array();

		foreach ( $allowed as $key => $type ) {
			if ( ! array_key_exists( $key, $payload ) ) {
				continue;
			}

			$value = $payload[ $key ];
			if ( 'int' === $type ) {
				$clean[ $key ] = max( 0, (int) $value );
				continue;
			}

			if ( 'bool' === $type ) {
				$clean[ $key ] = (bool) $value;
				continue;
			}

			$value = substr( sanitize_text_field( (string) $value ), 0, 160 );
			if ( 'status' === $type && ! in_array( $value, array( 'ok', 'warning', 'error' ), true ) ) {
				$value = 'error';
			}
			if ( 'key' === $type ) {
				$value = sanitize_key( $value );
			}
			$clean[ $key ] = $value;
		}

		return $clean;
	}
}
