<?php
/**
 * Ability intake adapter.
 *
 * @package NpcinkGovernanceCore
 */

namespace Npcink\GovernanceCore\Capabilities;

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
		if ( function_exists( 'npcink_abilities_toolkit_get_registered' ) ) {
			return 'npcink_abilities_toolkit';
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
		if ( 'npcink_abilities_toolkit' === $source ) {
			$registered = npcink_abilities_toolkit_get_registered();
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
		if ( 'npcink_abilities_toolkit' === $source ) {
			return 'Capabilities discovered through npcink-abilities-toolkit public API.';
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
		$required_scope    = $this->first_string(
			array(
				$definition['required_scope'] ?? null,
				$meta['required_scope'] ?? null,
			),
			''
		);
		$required_scopes   = $this->sanitize_scope_list(
			is_array( $definition['required_scopes'] ?? null )
				? (array) $definition['required_scopes']
				: ( '' !== $required_scope ? array( $required_scope ) : array() )
		);
		$read_policy       = $this->read_governance_policy( $ability_id, sanitize_key( $risk_level ), $guidance, $definition, $meta, $annotations );
		if ( ! empty( $read_policy['read_authorization_required'] ) ) {
			$guidance['governance_mode'] = 'core_read_authorization_required';
		}

		return array(
			'ability_id'        => $ability_id,
			'label'             => $this->first_string( array( $definition['label'] ?? null, $definition['title'] ?? null ), $ability_id ),
			'description'       => $this->first_string( array( $definition['description'] ?? null ), '' ),
			'risk_level'        => sanitize_key( $risk_level ),
			'requires_approval' => $requires_approval,
			'capability'        => $this->first_string( array( $definition['capability'] ?? null, $meta['capability'] ?? null ), '' ),
			'required_scope'    => $required_scope,
			'required_scopes'   => $required_scopes,
			'governance_mode'   => $guidance['governance_mode'],
			'execution_surface' => $guidance['execution_surface'],
			'core_proxy_execute' => false,
			'commit_execution'  => false,
			'read_policy'       => $read_policy['read_policy'],
			'read_authorization_required' => (bool) $read_policy['read_authorization_required'],
			'requires_read_authorization' => (bool) $read_policy['read_authorization_required'],
			'authorization_mode' => ! empty( $read_policy['read_authorization_required'] ) ? 'core_read_request' : 'none',
			'read_authorization' => $read_policy['read_authorization'],
			'read_authorization_request_route' => ! empty( $read_policy['read_authorization_required'] ) ? '/wp-json/npcink-governance-core/v1/read-requests' : '',
			'read_authorization_preflight_route' => ! empty( $read_policy['read_authorization_required'] ) ? '/wp-json/npcink-governance-core/v1/read-requests/{request_id}/read-preflight' : '',
			'read_authorization_status_route' => ! empty( $read_policy['read_authorization_required'] ) ? '/wp-json/npcink-governance-core/v1/read-requests/{request_id}' : '',
			'sensitivity'       => $read_policy['sensitivity'],
			'redaction_required' => $read_policy['redaction_required'],
			'read_audit_mode'   => $read_policy['read_audit_mode'],
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
	 * Returns read-side governance metadata for adapter routing.
	 *
	 * @param string              $ability_id Ability id.
	 * @param string              $risk_level Risk level.
	 * @param array<string,mixed> $guidance Execution guidance.
	 * @param array<string,mixed> $definition Raw definition.
	 * @param array<string,mixed> $meta Meta fields.
	 * @param array<string,mixed> $annotations Annotation fields.
	 * @return array{read_policy:string,sensitivity:string,redaction_required:bool,read_audit_mode:string,read_authorization_required:bool,read_authorization:array<string,mixed>}
	 */
	private function read_governance_policy( string $ability_id, string $risk_level, array $guidance, array $definition, array $meta, array $annotations ): array {
		if ( 'direct_read' !== (string) ( $guidance['governance_mode'] ?? '' ) || 'read' !== $risk_level ) {
			return array(
				'read_policy'        => 'not_direct_read',
				'sensitivity'        => 'internal',
				'redaction_required' => false,
				'read_audit_mode'    => 'none',
				'read_authorization_required' => false,
				'read_authorization' => array( 'required' => false ),
			);
		}

		$sensitivity = sanitize_key(
			$this->first_string(
				array(
					$definition['sensitivity'] ?? null,
					$meta['sensitivity'] ?? null,
					$annotations['sensitivity'] ?? null,
				),
				$this->infer_read_sensitivity( $ability_id )
			)
		);
		if ( ! in_array( $sensitivity, array( 'public', 'internal', 'sensitive' ), true ) ) {
			$sensitivity = 'internal';
		}

		$redaction_required = $this->first_bool(
			array(
				$definition['redaction_required'] ?? null,
				$meta['redaction_required'] ?? null,
				$annotations['redaction_required'] ?? null,
			),
			'sensitive' === $sensitivity
		);
		$read_authorization = $this->read_authorization_metadata( $definition, $meta, $annotations );
		$auth_required      = 'sensitive' === $sensitivity
			|| (bool) ( $read_authorization['required'] ?? false )
			|| $this->first_bool(
				array(
					$definition['read_authorization_required'] ?? null,
					$definition['requires_read_authorization'] ?? null,
					$meta['read_authorization_required'] ?? null,
					$annotations['read_authorization_required'] ?? null,
				),
				false
			)
			|| 'core_read_authorization_required' === sanitize_key( (string) ( $definition['read_policy'] ?? '' ) )
			|| 'core_read_request' === sanitize_key( (string) ( $definition['authorization_mode'] ?? '' ) );

		if ( $auth_required ) {
			$read_authorization['required'] = true;
		}

		return array(
			'read_policy'        => $auth_required ? 'core_read_authorization_required' : 'direct_read_' . $sensitivity,
			'sensitivity'        => $sensitivity,
			'redaction_required' => $redaction_required,
			'read_audit_mode'    => $auth_required ? 'core_read_request_audit' : 'adapter_read_envelope',
			'read_authorization_required' => $auth_required,
			'read_authorization' => $read_authorization,
		);
	}

	/**
	 * Returns provider-declared read authorization metadata.
	 *
	 * @param array<string,mixed> $definition Raw definition.
	 * @param array<string,mixed> $meta Meta fields.
	 * @param array<string,mixed> $annotations Annotation fields.
	 * @return array<string,mixed>
	 */
	private function read_authorization_metadata( array $definition, array $meta, array $annotations ): array {
		$source = array();
		foreach ( array( $definition['read_authorization'] ?? null, $meta['read_authorization'] ?? null, $annotations['read_authorization'] ?? null ) as $candidate ) {
			if ( is_array( $candidate ) ) {
				$source = $candidate;
				break;
			}
		}

		$bounds = is_array( $source['bounds'] ?? null ) ? (array) $source['bounds'] : $source;

		return array(
			'required'       => $this->first_bool( array( $source['required'] ?? null ), false ),
			'policy_version' => 'core-read-authorization-v1',
			'max_rows'       => isset( $bounds['max_rows'] ) ? absint( $bounds['max_rows'] ) : 0,
			'tail_lines'     => isset( $bounds['tail_lines'] ) ? absint( $bounds['tail_lines'] ) : 0,
			'allowed_fields' => $this->sanitize_scope_list( is_array( $bounds['allowed_fields'] ?? null ) ? (array) $bounds['allowed_fields'] : array() ),
			'denied_fields'  => $this->sanitize_scope_list( is_array( $bounds['denied_fields'] ?? null ) ? (array) $bounds['denied_fields'] : array() ),
		);
	}

	/**
	 * Infers read sensitivity when the provider has not declared one.
	 *
	 * @param string $ability_id Ability id.
	 * @return string
	 */
	private function infer_read_sensitivity( string $ability_id ): string {
		$ability_id = strtolower( $ability_id );

		foreach ( array( 'diagnostic', 'permissions', 'database', 'error-log', 'plugin-conflict', 'ops' ) as $needle ) {
			if ( false !== strpos( $ability_id, $needle ) ) {
				return 'sensitive';
			}
		}

		foreach ( array( 'inventory', 'plan', 'media', 'pages', 'posts', 'users', 'menu', 'term', 'workflow' ) as $needle ) {
			if ( false !== strpos( $ability_id, $needle ) ) {
				return 'internal';
			}
		}

		return 'public';
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
	 * Sanitizes scope metadata.
	 *
	 * @param array<mixed> $scopes Scope values.
	 * @return array<int,string>
	 */
	private function sanitize_scope_list( array $scopes ): array {
		$clean = array();
		foreach ( $scopes as $scope ) {
			if ( ! is_string( $scope ) || '' === trim( $scope ) ) {
				continue;
			}

			$clean[] = sanitize_text_field( $scope );
		}

		return array_values( array_unique( $clean ) );
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

		return $this->redact_secret_payload( $definition );
	}

	/**
	 * Removes secret-shaped values from raw provider metadata.
	 *
	 * @param mixed $value Raw value.
	 * @return mixed
	 */
	private function redact_secret_payload( $value ) {
		if ( is_array( $value ) ) {
			$clean = array();
			foreach ( $value as $key => $item ) {
				$key_string = (string) $key;
				if ( preg_match( '/(secret|token|private[_-]?key|authorization|cookie|application[_-]?password|password|api[_-]?key)/i', $key_string ) ) {
					$clean[ $key ] = '[redacted]';
					continue;
				}
				$clean[ $key ] = $this->redact_secret_payload( $item );
			}
			return $clean;
		}

		return $value;
	}
}
