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
		$sources     = $this->discovery_sources();
		$source      = ! empty( $sources ) ? (string) $sources[0]['source'] : 'none';
		$definitions = array();

		foreach ( $sources as $discovery_source ) {
			$row_source = (string) $discovery_source['source'];
			foreach ( (array) $discovery_source['abilities'] as $ability_id => $definition ) {
				$definition   = $this->definition_to_array( $definition );
				$normalized_id = $this->normalize_ability_id( $ability_id, $definition );
				if ( '' === $normalized_id ) {
					continue;
				}

				if ( isset( $definitions[ $normalized_id ] ) ) {
					$definitions[ $normalized_id ]['source_definitions'][] = $definition;
					$definitions[ $normalized_id ]['definition'] = array_replace_recursive(
						$definition,
						$definitions[ $normalized_id ]['definition']
					);
					continue;
				}

				$definitions[ $normalized_id ] = array(
					'definition'         => $definition,
					'source_definitions' => array( $definition ),
					'source'             => $row_source,
				);
			}
		}

		$items = array();
		foreach ( $definitions as $ability_id => $discovery_definition ) {
			$items[] = $this->normalize_row(
				(string) $ability_id,
				(array) $discovery_definition['definition'],
				(string) $discovery_definition['source'],
				(array) ( $discovery_definition['source_definitions'] ?? array() )
			);
		}

		usort(
			$items,
			static function ( array $a, array $b ): int {
				return strcmp( (string) $a['ability_id'], (string) $b['ability_id'] );
			}
		);

		$ready_count   = 0;
		$blocked_count = 0;
		foreach ( $items as $item ) {
			if ( 'ready' === (string) ( $item['intake_status'] ?? '' ) ) {
				++$ready_count;
			} else {
				++$blocked_count;
			}
		}

		return array(
			'available'     => 'none' !== $source,
			'source'        => $source,
			'count'         => count( $items ),
			'ready_count'   => $ready_count,
			'blocked_count' => $blocked_count,
			'message'       => $this->message_for_source( $source ),
			'items'         => $items,
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
			'available'     => (bool) $capabilities['available'],
			'source'        => (string) $capabilities['source'],
			'count'         => (int) $capabilities['count'],
			'ready_count'   => (int) ( $capabilities['ready_count'] ?? 0 ),
			'blocked_count' => (int) ( $capabilities['blocked_count'] ?? 0 ),
			'message'       => (string) $capabilities['message'],
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
	 * Returns public discovery sources in precedence order.
	 *
	 * The WordPress Abilities API is the aggregate registry. The Toolkit helper
	 * remains a compatibility source for missing definitions and fields.
	 *
	 * @return array<int,array{source:string,abilities:array<mixed>}>
	 */
	private function discovery_sources(): array {
		$sources = array();

		if ( function_exists( 'wp_get_abilities' ) ) {
			$registered = wp_get_abilities();
			$sources[]  = array(
				'source'    => 'wordpress_abilities_api',
				'abilities' => is_array( $registered ) ? $registered : array(),
			);
		}

		if ( function_exists( 'npcink_abilities_toolkit_get_registered' ) ) {
			$registered = npcink_abilities_toolkit_get_registered();
			$sources[]  = array(
				'source'    => 'npcink_abilities_toolkit',
				'abilities' => is_array( $registered ) ? $registered : array(),
			);
		}

		return $sources;
	}

	/**
	 * Converts one public WordPress ability object to the existing array shape.
	 *
	 * @param mixed $definition Raw definition.
	 * @return array<string,mixed>
	 */
	private function definition_to_array( $definition ): array {
		if ( is_array( $definition ) ) {
			return $definition;
		}

		if ( ! is_object( $definition ) ) {
			return array();
		}

		$method_fields = array(
			'get_name'          => 'ability_id',
			'get_label'         => 'label',
			'get_description'   => 'description',
			'get_input_schema'  => 'input_schema',
			'get_output_schema' => 'output_schema',
			'get_meta'          => 'meta',
		);
		$normalized    = array();

		foreach ( $method_fields as $method => $field ) {
			if ( is_callable( array( $definition, $method ) ) ) {
				$normalized[ $field ] = $definition->{$method}();
			}
		}

		return $normalized;
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
	 * @param array<int,array<string,mixed>> $source_definitions Pre-merge source definitions.
	 * @return array<string,mixed>
	 */
	private function normalize_row( string $ability_id, array $definition, string $source, array $source_definitions ): array {
		$meta        = is_array( $definition['meta'] ?? null ) ? $definition['meta'] : array();
		$npcink_meta = is_array( $meta['npcink'] ?? null ) ? $meta['npcink'] : array();
		$source_definitions = empty( $source_definitions ) ? array( $definition ) : $source_definitions;
		$annotation_sources = $this->ability_annotation_sources( $source_definitions );
		$annotations        = $this->ability_annotations( $annotation_sources );
		$risk_contract      = $this->risk_contract( $source_definitions, $annotation_sources );
		$risk_level         = (string) $risk_contract['risk_level'];
		$approval_contract  = $this->approval_contract( $source_definitions, $annotation_sources, $risk_level );
		$sensitivity_contract = $this->sensitivity_contract( $source_definitions, $annotation_sources, $risk_level );
		$requires_approval  = (bool) $approval_contract['requires_approval'];
		$intake_reasons     = array_merge(
			(array) $risk_contract['reasons'],
			(array) $approval_contract['reasons'],
			(array) $sensitivity_contract['reasons'],
			$this->rest_exposure_reasons( $source_definitions )
		);

		$intake_reasons = array_values( array_unique( $intake_reasons ) );
		$intake_status  = empty( $intake_reasons ) ? 'ready' : 'blocked';
		$guidance          = $this->execution_guidance( sanitize_key( $risk_level ), $requires_approval );
		if ( 'blocked' === $intake_status ) {
			$guidance = array(
				'governance_mode'   => 'blocked',
				'execution_surface' => 'none',
			);
		}
		$required_scope    = $this->first_string(
			array(
				$definition['required_scope'] ?? null,
				$meta['required_scope'] ?? null,
				$npcink_meta['required_scope'] ?? null,
			),
			''
		);
		$required_scopes   = $this->sanitize_scope_list(
			is_array( $definition['required_scopes'] ?? null )
				? (array) $definition['required_scopes']
				: ( '' !== $required_scope ? array( $required_scope ) : array() )
		);
		$read_policy       = $this->read_governance_policy( sanitize_key( $risk_level ), $guidance, $definition, $meta, $annotations, (string) $sensitivity_contract['sensitivity'] );
		if ( ! empty( $read_policy['read_authorization_required'] ) ) {
			$guidance['governance_mode'] = 'core_read_authorization_required';
		}
		$implementation_posture = $this->implementation_posture_metadata( $definition, $meta, $annotations );

		return array(
			'ability_id'        => $ability_id,
			'label'             => $this->first_string( array( $definition['label'] ?? null, $definition['title'] ?? null ), $ability_id ),
			'description'       => $this->first_string( array( $definition['description'] ?? null ), '' ),
			'risk_level'        => sanitize_key( $risk_level ),
			'requires_approval' => $requires_approval,
			'intake_contract_version' => 'core-ability-intake-v1',
			'intake_status'     => $intake_status,
			'intake_reasons'    => $intake_reasons,
			'capability'        => $this->first_string( array( $definition['capability'] ?? null, $meta['capability'] ?? null, $npcink_meta['capability'] ?? null ), '' ),
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
			'implementation_posture_available' => ! empty( $implementation_posture ),
			'implementation_posture' => $implementation_posture,
			'input_schema'      => is_array( $definition['input_schema'] ?? null ) ? $definition['input_schema'] : array( 'type' => 'object' ),
			'output_schema'     => is_array( $definition['output_schema'] ?? null ) ? $definition['output_schema'] : array( 'type' => 'object' ),
			'source'            => $this->first_string( array( $definition['source'] ?? null ), $source ),
			'raw'               => $this->redact_raw_definition( $definition ),
		);
	}

	/**
	 * Returns every annotation payload that can enter through supported provider shapes.
	 *
	 * @param array<int,array<string,mixed>> $source_definitions Pre-merge source definitions.
	 * @return array<int,array<string,mixed>>
	 */
	private function ability_annotation_sources( array $source_definitions ): array {
		$sources = array();
		foreach ( $source_definitions as $definition ) {
			$meta        = is_array( $definition['meta'] ?? null ) ? $definition['meta'] : array();
			$npcink_meta = is_array( $meta['npcink'] ?? null ) ? $meta['npcink'] : array();
			foreach ( array( $meta['annotations'] ?? null, $definition['annotations'] ?? null, $npcink_meta['annotations'] ?? null ) as $candidate ) {
				if ( is_array( $candidate ) ) {
					$sources[] = $candidate;
				}
			}
		}

		return $sources;
	}

	/**
	 * Returns the canonical annotation payload for non-risk metadata.
	 *
	 * Risk evidence is evaluated across every source before this helper is used.
	 *
	 * @param array<int,array<string,mixed>> $sources Annotation sources.
	 * @return array<string,mixed>
	 */
	private function ability_annotations( array $sources ): array {
		return is_array( $sources[0] ?? null ) ? $sources[0] : array();
	}

	/**
	 * Resolves all risk declarations without allowing one source to hide another.
	 *
	 * @param array<int,array<string,mixed>> $source_definitions Pre-merge source definitions.
	 * @param array<int,array<string,mixed>> $annotation_sources Annotation sources.
	 * @return array{risk_level:string,reasons:array<int,string>}
	 */
	private function risk_contract( array $source_definitions, array $annotation_sources ): array {
		$reasons            = array();
		$provider_risks     = array();
		$annotation_risks   = array();
		$risk_declarations  = array();
		foreach ( $source_definitions as $definition ) {
			$meta        = is_array( $definition['meta'] ?? null ) ? $definition['meta'] : array();
			$npcink_meta = is_array( $meta['npcink'] ?? null ) ? $meta['npcink'] : array();
			$risk_declarations[] = $definition['risk_level'] ?? null;
			$risk_declarations[] = $meta['risk_level'] ?? null;
			$risk_declarations[] = $npcink_meta['risk_level'] ?? null;
		}

		foreach ( $risk_declarations as $candidate ) {
			if ( null === $candidate ) {
				continue;
			}
			if ( ! is_string( $candidate ) || ! in_array( $candidate, array( 'read', 'write', 'destructive' ), true ) ) {
				$reasons[] = 'risk_invalid';
				continue;
			}
			$provider_risks[] = $candidate;
		}

		foreach ( $source_definitions as $definition ) {
			$meta        = is_array( $definition['meta'] ?? null ) ? $definition['meta'] : array();
			$npcink_meta = is_array( $meta['npcink'] ?? null ) ? $meta['npcink'] : array();
			foreach ( array( $meta['annotations'] ?? null, $definition['annotations'] ?? null, $npcink_meta['annotations'] ?? null ) as $candidate ) {
				if ( null !== $candidate && ! is_array( $candidate ) ) {
					$reasons[] = 'annotations_invalid';
				}
			}
		}

		foreach ( $annotation_sources as $annotations ) {
			$annotation_risk = $this->risk_from_annotations( $annotations, $reasons );
			if ( '' !== $annotation_risk ) {
				$annotation_risks[] = $annotation_risk;
			}
		}

		$provider_risks   = array_values( array_unique( $provider_risks ) );
		$annotation_risks = array_values( array_unique( $annotation_risks ) );
		$all_risks        = array_values( array_unique( array_merge( $provider_risks, $annotation_risks ) ) );
		if ( count( $provider_risks ) > 1 ) {
			$reasons[] = 'risk_sources_conflict';
		}
		if ( count( $annotation_risks ) > 1 ) {
			$reasons[] = 'annotations_conflict';
		}
		if ( ! empty( $provider_risks ) && ! empty( $annotation_risks ) && count( $all_risks ) > 1 ) {
			$reasons[] = 'risk_annotations_conflict';
		}
		if ( empty( $all_risks ) ) {
			$reasons[] = empty( array_filter( $risk_declarations, static function ( $value ): bool { return null !== $value; } ) ) ? 'risk_undeclared' : 'risk_invalid';
		}

		return array(
			'risk_level' => $this->most_conservative_risk( $all_risks ),
			'reasons'    => array_values( array_unique( $reasons ) ),
		);
	}

	/**
	 * Derives one risk value from standard annotations and records invalid evidence.
	 *
	 * @param array<string,mixed> $annotations Annotation fields.
	 * @param array<int,string>   $reasons Intake reasons passed by reference.
	 * @return string
	 */
	private function risk_from_annotations( array $annotations, array &$reasons ): string {
		foreach ( array( 'readonly', 'destructive' ) as $field ) {
			if ( array_key_exists( $field, $annotations ) && null !== $annotations[ $field ] && ! $this->is_boolean_like( $annotations[ $field ] ) ) {
				$reasons[] = 'annotations_invalid';
				return '';
			}
		}

		$readonly    = $this->annotation_bool( $annotations, 'readonly' );
		$destructive = $this->annotation_bool( $annotations, 'destructive' );
		if ( true === $readonly && true === $destructive ) {
			$reasons[] = 'annotations_conflict';
			return 'destructive';
		}
		if ( true === $destructive ) {
			return 'destructive';
		}
		if ( true === $readonly ) {
			return 'read';
		}
		if ( false === $readonly ) {
			return 'write';
		}

		return '';
	}

	/**
	 * Returns the strongest observed risk for diagnostics when intake is blocked.
	 *
	 * @param array<int,string> $risks Normalized risks.
	 * @return string
	 */
	private function most_conservative_risk( array $risks ): string {
		foreach ( array( 'destructive', 'write', 'read' ) as $risk ) {
			if ( in_array( $risk, $risks, true ) ) {
				return $risk;
			}
		}

		return 'unknown';
	}

	/**
	 * Resolves all approval declarations without first-value-wins behavior.
	 *
	 * @param array<int,array<string,mixed>> $source_definitions Pre-merge source definitions.
	 * @param array<int,array<string,mixed>> $annotation_sources Annotation sources.
	 * @param string                         $risk_level Normalized risk.
	 * @return array{requires_approval:bool,reasons:array<int,string>}
	 */
	private function approval_contract( array $source_definitions, array $annotation_sources, string $risk_level ): array {
		$reasons = array();
		$values  = array();
		foreach ( $source_definitions as $definition ) {
			$meta        = is_array( $definition['meta'] ?? null ) ? $definition['meta'] : array();
			$npcink_meta = is_array( $meta['npcink'] ?? null ) ? $meta['npcink'] : array();
			$values[] = $definition['requires_confirm'] ?? null;
			$values[] = $definition['requires_approval'] ?? null;
			$values[] = $meta['requires_confirm'] ?? null;
			$values[] = $meta['requires_approval'] ?? null;
			$values[] = $npcink_meta['requires_confirm'] ?? null;
			$values[] = $npcink_meta['requires_approval'] ?? null;
		}
		foreach ( $annotation_sources as $annotations ) {
			$values[] = $annotations['requires_confirm'] ?? null;
		}

		$declared = array();
		foreach ( $values as $value ) {
			if ( null === $value ) {
				continue;
			}
			if ( ! $this->is_boolean_like( $value ) ) {
				$reasons[] = 'approval_invalid';
				continue;
			}
			$declared[] = (bool) (int) $value;
		}
		$declared = array_values( array_unique( $declared, SORT_REGULAR ) );
		if ( count( $declared ) > 1 ) {
			$reasons[] = 'approval_sources_conflict';
		}

		$write_like        = in_array( $risk_level, array( 'write', 'destructive' ), true );
		$requires_approval = 1 === count( $declared ) ? (bool) $declared[0] : $write_like;
		if ( $write_like && in_array( false, $declared, true ) ) {
			$reasons[]         = 'write_approval_conflict';
			$requires_approval = true;
		}
		if ( 'read' === $risk_level && in_array( true, $declared, true ) ) {
			$reasons[] = 'read_approval_conflict';
		}

		return array(
			'requires_approval' => $requires_approval,
			'reasons'           => array_values( array_unique( $reasons ) ),
		);
	}

	/**
	 * Resolves read sensitivity conservatively across every declared source.
	 *
	 * Missing sensitivity becomes sensitive and requires Core read authorization.
	 *
	 * @param array<int,array<string,mixed>> $source_definitions Pre-merge source definitions.
	 * @param array<int,array<string,mixed>> $annotation_sources Annotation sources.
	 * @param string                         $risk_level Normalized risk.
	 * @return array{sensitivity:string,reasons:array<int,string>}
	 */
	private function sensitivity_contract( array $source_definitions, array $annotation_sources, string $risk_level ): array {
		if ( 'read' !== $risk_level ) {
			return array( 'sensitivity' => 'internal', 'reasons' => array() );
		}

		$values = array();
		foreach ( $source_definitions as $definition ) {
			$meta        = is_array( $definition['meta'] ?? null ) ? $definition['meta'] : array();
			$npcink_meta = is_array( $meta['npcink'] ?? null ) ? $meta['npcink'] : array();
			$values[] = $definition['sensitivity'] ?? null;
			$values[] = $meta['sensitivity'] ?? null;
			$values[] = $npcink_meta['sensitivity'] ?? null;
		}
		foreach ( $annotation_sources as $annotations ) {
			$values[] = $annotations['sensitivity'] ?? null;
		}

		$declared = array();
		$reasons  = array();
		foreach ( $values as $value ) {
			if ( null === $value ) {
				continue;
			}
			if ( ! is_string( $value ) || ! in_array( $value, array( 'public', 'internal', 'sensitive' ), true ) ) {
				$reasons[] = 'sensitivity_invalid';
				continue;
			}
			$declared[] = $value;
		}
		$declared = array_values( array_unique( $declared ) );
		if ( count( $declared ) > 1 ) {
			$reasons[] = 'sensitivity_sources_conflict';
		}

		foreach ( array( 'sensitive', 'internal', 'public' ) as $sensitivity ) {
			if ( in_array( $sensitivity, $declared, true ) ) {
				return array( 'sensitivity' => $sensitivity, 'reasons' => array_values( array_unique( $reasons ) ) );
			}
		}

		return array( 'sensitivity' => 'sensitive', 'reasons' => array_values( array_unique( $reasons ) ) );
	}

	/**
	 * Requires an unambiguous, explicit REST exposure declaration.
	 *
	 * @param array<int,array<string,mixed>> $source_definitions Pre-merge source definitions.
	 * @return array<int,string>
	 */
	private function rest_exposure_reasons( array $source_definitions ): array {
		$canonical = array();
		$mirrors   = array();
		$reasons   = array();
		foreach ( $source_definitions as $definition ) {
			$meta        = is_array( $definition['meta'] ?? null ) ? $definition['meta'] : array();
			$npcink_meta = is_array( $meta['npcink'] ?? null ) ? $meta['npcink'] : array();
			if ( array_key_exists( 'show_in_rest', $meta ) ) {
				if ( ! is_bool( $meta['show_in_rest'] ) ) {
					$reasons[] = 'rest_exposure_invalid';
				} else {
					$canonical[] = $meta['show_in_rest'];
				}
			}
			foreach ( array( $definition, $npcink_meta ) as $mirror_source ) {
				if ( ! array_key_exists( 'show_in_rest', $mirror_source ) ) {
					continue;
				}
				if ( ! is_bool( $mirror_source['show_in_rest'] ) ) {
					$reasons[] = 'rest_exposure_mirror_invalid';
				} else {
					$mirrors[] = $mirror_source['show_in_rest'];
				}
			}
		}

		$canonical = array_values( array_unique( $canonical, SORT_REGULAR ) );
		$mirrors   = array_values( array_unique( $mirrors, SORT_REGULAR ) );
		if ( count( $canonical ) > 1 || ( ! empty( $canonical ) && ! empty( $mirrors ) && count( array_unique( array_merge( $canonical, $mirrors ), SORT_REGULAR ) ) > 1 ) ) {
			$reasons[] = 'rest_exposure_conflict';
		}
		if ( empty( $canonical ) ) {
			$reasons[] = 'rest_exposure_undeclared';
		} elseif ( ! in_array( true, $canonical, true ) ) {
			$reasons[] = 'rest_exposure_disabled';
		}

		return array_values( array_unique( $reasons ) );
	}

	/**
	 * Returns a nullable standard annotation boolean.
	 *
	 * @param array<string,mixed> $annotations Annotation fields.
	 * @param string              $field Field name.
	 * @return bool|null
	 */
	private function annotation_bool( array $annotations, string $field ): ?bool {
		if ( ! array_key_exists( $field, $annotations ) || null === $annotations[ $field ] || ! $this->is_boolean_like( $annotations[ $field ] ) ) {
			return null;
		}

		return (bool) (int) $annotations[ $field ];
	}

	/**
	 * Returns provider-declared implementation posture metadata.
	 *
	 * @param array<string,mixed> $definition Raw definition.
	 * @param array<string,mixed> $meta Meta fields.
	 * @param array<string,mixed> $annotations Annotation fields.
	 * @return array<string,mixed>
	 */
	private function implementation_posture_metadata( array $definition, array $meta, array $annotations ): array {
		$npcink_meta = is_array( $meta['npcink'] ?? null ) ? $meta['npcink'] : array();
		foreach ( array( $definition['implementation_posture'] ?? null, $meta['implementation_posture'] ?? null, $npcink_meta['implementation_posture'] ?? null, $annotations['implementation_posture'] ?? null ) as $candidate ) {
			if ( is_array( $candidate ) && ! empty( $candidate ) ) {
				return $this->sanitize_implementation_posture( $candidate );
			}
		}

		return array();
	}

	/**
	 * Sanitizes implementation posture metadata without making Core its owner.
	 *
	 * @param array<string,mixed> $posture Raw posture metadata.
	 * @return array<string,mixed>
	 */
	private function sanitize_implementation_posture( array $posture ): array {
		$clean       = array();
		$text_fields = array(
			'schema_version',
		);
		$key_fields  = array(
			'implementation_owner',
			'execution_surface',
			'write_posture',
			'commit_authority',
			'final_authorization_owner',
			'approval_truth_owner',
			'audit_truth_owner',
		);
		$bool_fields = array(
			'direct_wordpress_write_default',
			'dry_run_default',
			'commit_default',
			'workflow_' . 'runtime',
			'queue_or_scheduler',
			'model_' . 'routing',
			'provider_' . 'credentials',
			'approval_storage',
			'audit_storage',
		);
		$list_fields = array(
			'reference_patterns',
			'verification_contract',
			'required_host_evidence',
			'non_goals',
		);

		foreach ( $text_fields as $field ) {
			if ( isset( $posture[ $field ] ) && is_scalar( $posture[ $field ] ) ) {
				$value = sanitize_text_field( (string) $posture[ $field ] );
				if ( '' !== $value ) {
					$clean[ $field ] = $value;
				}
			}
		}

		foreach ( $key_fields as $field ) {
			if ( isset( $posture[ $field ] ) && is_scalar( $posture[ $field ] ) ) {
				$value = sanitize_key( (string) $posture[ $field ] );
				if ( '' !== $value ) {
					$clean[ $field ] = $value;
				}
			}
		}

		foreach ( $bool_fields as $field ) {
			if ( array_key_exists( $field, $posture ) ) {
				$clean[ $field ] = $this->first_bool( array( $posture[ $field ] ), false );
			}
		}

		foreach ( $list_fields as $field ) {
			if ( isset( $posture[ $field ] ) && is_array( $posture[ $field ] ) ) {
				$values = $this->sanitize_scope_list( (array) $posture[ $field ] );
				if ( ! empty( $values ) ) {
					$clean[ $field ] = $values;
				}
			}
		}

		return $clean;
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
	 * @param string              $risk_level Risk level.
	 * @param array<string,mixed> $guidance Execution guidance.
	 * @param array<string,mixed> $definition Raw definition.
	 * @param array<string,mixed> $meta Meta fields.
	 * @param array<string,mixed> $annotations Annotation fields.
	 * @param string              $resolved_sensitivity Fail-closed resolved sensitivity.
	 * @return array{read_policy:string,sensitivity:string,redaction_required:bool,read_audit_mode:string,read_authorization_required:bool,read_authorization:array<string,mixed>}
	 */
	private function read_governance_policy( string $risk_level, array $guidance, array $definition, array $meta, array $annotations, string $resolved_sensitivity ): array {
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

		$sensitivity = in_array( $resolved_sensitivity, array( 'public', 'internal', 'sensitive' ), true ) ? $resolved_sensitivity : 'sensitive';

		$redaction_required = $this->first_bool(
			array(
				$definition['redaction_required'] ?? null,
				$meta['redaction_required'] ?? null,
				$annotations['redaction_required'] ?? null,
			),
			'public' !== $sensitivity
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
	 * Returns whether one value is an unambiguous boolean representation.
	 *
	 * @param mixed $value Value.
	 * @return bool
	 */
	private function is_boolean_like( $value ): bool {
		return is_bool( $value )
			|| 0 === $value
			|| 1 === $value
			|| 0.0 === $value
			|| 1.0 === $value
			|| '0' === $value
			|| '1' === $value;
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
