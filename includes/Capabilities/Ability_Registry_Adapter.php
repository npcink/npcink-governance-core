<?php
/**
 * Ability intake adapter.
 *
 * @package MagickAICore
 */

namespace MagickAI\Core\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Normalizes read-only ability discovery from public provider APIs.
 */
final class Ability_Registry_Adapter {
	/**
	 * Returns normalized capability rows.
	 *
	 * @return array<string,mixed>
	 */
	public function list_capabilities(): array {
		$source  = $this->detect_source();
		$raw_map = $this->raw_abilities_for_source( $source );
		$items   = array();

		foreach ( $raw_map as $ability_id => $definition ) {
			$normalized_id = $this->normalize_ability_id( $ability_id, $definition );
			if ( '' === $normalized_id ) {
				continue;
			}

			$items[] = $this->normalize_row( $normalized_id, is_array( $definition ) ? $definition : array(), $source );
		}

		usort(
			$items,
			static function ( array $a, array $b ): int {
				return strcmp( (string) $a['ability_id'], (string) $b['ability_id'] );
			}
		);

		return array(
			'available' => 'none' !== $source,
			'source'    => $source,
			'count'     => count( $items ),
			'message'   => $this->message_for_source( $source ),
			'items'     => $items,
		);
	}

	/**
	 * Returns a compact discovery summary.
	 *
	 * @return array<string,mixed>
	 */
	public function summary(): array {
		$capabilities = $this->list_capabilities();

		return array(
			'available' => (bool) $capabilities['available'],
			'source'    => (string) $capabilities['source'],
			'count'     => (int) $capabilities['count'],
			'message'   => (string) $capabilities['message'],
		);
	}

	/**
	 * Finds one normalized capability row.
	 *
	 * @param string $ability_id Ability id.
	 * @return array<string,mixed>|null
	 */
	public function find( string $ability_id ): ?array {
		$ability_id   = sanitize_text_field( $ability_id );
		$capabilities = $this->list_capabilities();

		foreach ( (array) $capabilities['items'] as $item ) {
			if ( is_array( $item ) && $ability_id === (string) ( $item['ability_id'] ?? '' ) ) {
				return $item;
			}
		}

		return null;
	}

	/**
	 * Detects the best available discovery source.
	 *
	 * @return string
	 */
	private function detect_source(): string {
		if ( function_exists( 'magick_ai_abilities_get_registered' ) ) {
			return 'magick_ai_abilities';
		}

		if ( function_exists( 'wp_get_abilities' ) ) {
			return 'wordpress_abilities_api';
		}

		return 'none';
	}

	/**
	 * Returns raw ability definitions.
	 *
	 * @param string $source Source name.
	 * @return array<mixed>
	 */
	private function raw_abilities_for_source( string $source ): array {
		if ( 'magick_ai_abilities' === $source ) {
			$registered = magick_ai_abilities_get_registered();
			return is_array( $registered ) ? $registered : array();
		}

		if ( 'wordpress_abilities_api' === $source ) {
			$registered = wp_get_abilities();
			return is_array( $registered ) ? $registered : array();
		}

		return array();
	}

	/**
	 * Returns source message.
	 *
	 * @param string $source Source name.
	 * @return string
	 */
	private function message_for_source( string $source ): string {
		if ( 'magick_ai_abilities' === $source ) {
			return 'Capabilities discovered through magick-ai-abilities public API.';
		}

		if ( 'wordpress_abilities_api' === $source ) {
			return 'Capabilities discovered through WordPress Abilities API.';
		}

		return 'No ability provider is available. Install WordPress Abilities API and a provider plugin.';
	}

	/**
	 * Normalizes one ability id.
	 *
	 * @param mixed $key Raw map key.
	 * @param mixed $definition Raw definition.
	 * @return string
	 */
	private function normalize_ability_id( $key, $definition ): string {
		if ( is_array( $definition ) ) {
			foreach ( array( 'ability_id', 'name', 'id' ) as $field ) {
				if ( ! empty( $definition[ $field ] ) && is_string( $definition[ $field ] ) ) {
					return sanitize_text_field( $definition[ $field ] );
				}
			}
		}

		return is_string( $key ) ? sanitize_text_field( $key ) : '';
	}

	/**
	 * Normalizes one ability row.
	 *
	 * @param string               $ability_id Ability id.
	 * @param array<string,mixed>  $definition Raw definition.
	 * @param string               $source Source name.
	 * @return array<string,mixed>
	 */
	private function normalize_row( string $ability_id, array $definition, string $source ): array {
		$meta        = is_array( $definition['meta'] ?? null ) ? $definition['meta'] : array();
		$annotations = is_array( $definition['annotations'] ?? null ) ? $definition['annotations'] : array();
		$risk_level  = $this->first_string(
			array(
				$definition['risk_level'] ?? null,
				$meta['risk_level'] ?? null,
				$annotations['risk_level'] ?? null,
				$definition['mode'] ?? null,
			),
			'read'
		);

		$requires_approval = $this->first_bool(
			array(
				$definition['requires_confirm'] ?? null,
				$definition['requires_approval'] ?? null,
				$meta['requires_confirm'] ?? null,
				$annotations['requires_confirm'] ?? null,
			),
			in_array( $risk_level, array( 'write', 'destructive' ), true )
		);
		$guidance          = $this->execution_guidance( sanitize_key( $risk_level ), $requires_approval );

		return array(
			'ability_id'        => $ability_id,
			'label'             => $this->first_string( array( $definition['label'] ?? null, $definition['title'] ?? null ), $ability_id ),
			'description'       => $this->first_string( array( $definition['description'] ?? null ), '' ),
			'risk_level'        => sanitize_key( $risk_level ),
			'requires_approval' => $requires_approval,
			'governance_mode'   => $guidance['governance_mode'],
			'execution_surface' => $guidance['execution_surface'],
			'core_proxy_execute' => false,
			'commit_execution'  => false,
			'input_schema'      => is_array( $definition['input_schema'] ?? null ) ? $definition['input_schema'] : array( 'type' => 'object' ),
			'output_schema'     => is_array( $definition['output_schema'] ?? null ) ? $definition['output_schema'] : array( 'type' => 'object' ),
			'source'            => $this->first_string( array( $definition['source'] ?? null ), $source ),
			'raw'               => $this->redact_raw_definition( $definition ),
		);
	}

	/**
	 * Returns machine-readable adapter guidance without executing abilities.
	 *
	 * @param string $risk_level Risk level.
	 * @param bool   $requires_approval Whether Core approval is required.
	 * @return array{governance_mode:string,execution_surface:string}
	 */
	private function execution_guidance( string $risk_level, bool $requires_approval ): array {
		if ( ! $requires_approval && 'read' === $risk_level ) {
			return array(
				'governance_mode'   => 'direct_read',
				'execution_surface' => 'wp_abilities_rest',
			);
		}

		return array(
			'governance_mode'   => 'proposal_required',
			'execution_surface' => 'adapter_after_core_preflight',
		);
	}

	/**
	 * Returns first non-empty string.
	 *
	 * @param array<mixed> $values Values.
	 * @param string       $fallback Fallback.
	 * @return string
	 */
	private function first_string( array $values, string $fallback ): string {
		foreach ( $values as $value ) {
			if ( is_string( $value ) && '' !== trim( $value ) ) {
				return sanitize_text_field( $value );
			}
		}

		return $fallback;
	}

	/**
	 * Returns first boolean-like value.
	 *
	 * @param array<mixed> $values Values.
	 * @param bool         $fallback Fallback.
	 * @return bool
	 */
	private function first_bool( array $values, bool $fallback ): bool {
		foreach ( $values as $value ) {
			if ( is_bool( $value ) ) {
				return $value;
			}

			if ( is_numeric( $value ) ) {
				return 1 === (int) $value;
			}
		}

		return $fallback;
	}

	/**
	 * Removes callbacks and other non-serializable fields from raw data.
	 *
	 * @param array<string,mixed> $definition Raw definition.
	 * @return array<string,mixed>
	 */
	private function redact_raw_definition( array $definition ): array {
		foreach ( array( 'execute_callback', 'permission_callback', 'callback' ) as $field ) {
			if ( array_key_exists( $field, $definition ) ) {
				$definition[ $field ] = '[callable]';
			}
		}

		return $definition;
	}
}
