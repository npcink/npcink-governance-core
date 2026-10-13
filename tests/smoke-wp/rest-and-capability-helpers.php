<?php
/** Rest and capability helpers test module. @package NpcinkGovernanceCore */

/**
 * Dispatches a REST request as admin.
 *
 * @param string              $method HTTP method.
 * @param string              $route REST route.
 * @param array<string,mixed> $params Request params.
 * @return array<string,mixed>
 */
function npcink_governance_core_smoke_rest( string $method, string $route, array $params = array() ): array {
	$result = npcink_governance_core_smoke_rest_result( $method, $route, $params );
	$data   = is_array( $result['data'] ) ? $result['data'] : array();
	$detail = '';
	if ( $result['status'] < 200 || $result['status'] >= 300 ) {
		$detail = sprintf(
			' (%s: %s)',
			(string) ( $data['code'] ?? 'unknown_error' ),
			(string) ( $data['message'] ?? 'No error message returned.' )
		);
	}

	npcink_governance_core_smoke_assert( $result['status'] >= 200 && $result['status'] < 300, $method . ' ' . $route . ' returned HTTP ' . $result['status'] . $detail );

	return $data;
}

/**
 * Dispatches a REST request as admin and returns status/data.
 *
 * @param string              $method HTTP method.
 * @param string              $route REST route.
 * @param array<string,mixed> $params Request params.
 * @return array{status:int,data:mixed}
 */
function npcink_governance_core_smoke_rest_result( string $method, string $route, array $params = array() ): array {
	wp_set_current_user( 1 );

	$request = new WP_REST_Request( $method, $route );
	foreach ( $params as $key => $value ) {
		$request->set_param( $key, $value );
	}

	$response = rest_do_request( $request );
	$status   = (int) $response->get_status();
	$data     = $response->get_data();
	npcink_governance_core_smoke_track_rest_fixture( $method, $route, $data );

	return array(
		'status' => $status,
		'data'   => $data,
	);
}

/**
 * Dispatches a REST request with app bearer token.
 *
 * @param string              $method HTTP method.
 * @param string              $route REST route.
 * @param string              $token App token.
 * @param array<string,mixed> $params Request params.
 * @return array<string,mixed>
 */
function npcink_governance_core_smoke_rest_as_app( string $method, string $route, string $token, array $params = array() ): array {
	$result = npcink_governance_core_smoke_rest_result_as_app( $method, $route, $token, $params );
	$data   = is_array( $result['data'] ) ? $result['data'] : array();
	$detail = '';
	if ( $result['status'] < 200 || $result['status'] >= 300 ) {
		$detail = sprintf(
			' (%s: %s)',
			(string) ( $data['code'] ?? 'unknown_error' ),
			(string) ( $data['message'] ?? 'No error message returned.' )
		);
	}

	npcink_governance_core_smoke_assert( $result['status'] >= 200 && $result['status'] < 300, $method . ' ' . $route . ' returned HTTP ' . $result['status'] . ' for app token' . $detail );

	return $data;
}

/**
 * Dispatches a REST request with app bearer token and returns status/data.
 *
 * @param string              $method HTTP method.
 * @param string              $route REST route.
 * @param string              $token App token.
 * @param array<string,mixed> $params Request params.
 * @return array{status:int,data:mixed}
 */
function npcink_governance_core_smoke_rest_result_as_app( string $method, string $route, string $token, array $params = array() ): array {
	wp_set_current_user( 0 );

	$request = new WP_REST_Request( $method, $route );
	$request->set_header( 'authorization', 'Bearer ' . $token );
	foreach ( $params as $key => $value ) {
		$request->set_param( $key, $value );
	}

	$response = rest_do_request( $request );
	$status   = (int) $response->get_status();
	$data     = $response->get_data();
	npcink_governance_core_smoke_track_rest_fixture( $method, $route, $data );

	return array(
		'status' => $status,
		'data'   => $data,
	);
}

/**
 * Locates the shared npcink-abilities-toolkit replay fixture.
 *
 * @return string
 */
function npcink_governance_core_smoke_replay_fixture_path(): string {
	$env_path = getenv( 'NPCINK_ABILITIES_TOOLKIT_PATH' );
	$roots    = array();

	if ( is_string( $env_path ) && '' !== trim( $env_path ) ) {
		$roots[] = rtrim( $env_path, '/' );
	}

	$roots[] = dirname( __DIR__, 3 ) . '/npcink-abilities-toolkit';
	$roots[] = '/Users/muze/gitee/npcink-abilities-toolkit';

	foreach ( $roots as $root ) {
		$fixture = $root . '/tests/fixtures/agent-workflow-replay.json';
		if ( is_readable( $fixture ) ) {
			return $fixture;
		}
	}

	return '';
}

/**
 * Returns shared workflow replay cases from the installed provider or fixture.
 *
 * @return array<string,array<string,mixed>>
 */
function npcink_governance_core_smoke_workflow_cases(): array {
	if ( function_exists( 'npcink_abilities_toolkit_get_workflow_definitions' ) ) {
		$manifest = npcink_abilities_toolkit_get_workflow_definitions();
		if ( is_array( $manifest ) && is_array( $manifest['cases'] ?? null ) ) {
			return $manifest['cases'];
		}
	}

	$replay_fixture_path = npcink_governance_core_smoke_replay_fixture_path();
	npcink_governance_core_smoke_assert( '' !== $replay_fixture_path, 'shared npcink-abilities-toolkit replay fixture is available' );
	$replay_fixture = json_decode( (string) file_get_contents( $replay_fixture_path ), true );
	npcink_governance_core_smoke_assert( is_array( $replay_fixture ) && is_array( $replay_fixture['cases'] ?? null ), 'shared replay fixture decodes with cases' );

	return $replay_fixture['cases'];
}

/**
 * Verifies the preferred create-draft proposal target schema is discoverable.
 *
 * @param array<string,mixed> $items_by_id Capability rows keyed by ability id.
 * @return void
 */
function npcink_governance_core_smoke_assert_create_draft_contract( array $items_by_id ): void {
	$ability_id = 'npcink-abilities-toolkit/create-draft';
	npcink_governance_core_smoke_assert( isset( $items_by_id[ $ability_id ] ), 'create-draft ability is discoverable for the primary governance scenario' );

	$ability      = $items_by_id[ $ability_id ];
	$input_schema = is_array( $ability['input_schema'] ?? null ) ? $ability['input_schema'] : array();
	$required     = (array) ( $input_schema['required'] ?? array() );
	$properties   = is_array( $input_schema['properties'] ?? null ) ? $input_schema['properties'] : array();

	npcink_governance_core_smoke_assert( 'write' === (string) ( $ability['risk_level'] ?? '' ), 'create-draft is discovered as a write-risk ability' );
	npcink_governance_core_smoke_assert( true === (bool) ( $ability['requires_approval'] ?? false ), 'create-draft is discovered as requiring approval' );
	npcink_governance_core_smoke_assert_capability_guidance( $ability, 'proposal_required', 'adapter_after_core_preflight', 'create-draft uses proposal-required execution guidance' );
	npcink_governance_core_smoke_assert( in_array( 'title', $required, true ), 'create-draft input schema requires title' );

	foreach ( array( 'dry_run', 'commit', 'idempotency_key' ) as $control ) {
		npcink_governance_core_smoke_assert( array_key_exists( $control, $properties ), 'create-draft input schema exposes governance control ' . $control );
	}
}

/**
 * Verifies the preferred SEO metadata proposal target schema is discoverable.
 *
 * @param array<string,mixed> $items_by_id Capability rows keyed by ability id.
 * @return void
 */
function npcink_governance_core_smoke_assert_seo_meta_contract( array $items_by_id ): void {
	$ability_id = 'npcink-abilities-toolkit/set-post-seo-meta';
	npcink_governance_core_smoke_assert( isset( $items_by_id[ $ability_id ] ), 'set-post-seo-meta ability is discoverable for the second governance scenario' );

	$ability      = $items_by_id[ $ability_id ];
	$input_schema = is_array( $ability['input_schema'] ?? null ) ? $ability['input_schema'] : array();
	$required     = (array) ( $input_schema['required'] ?? array() );
	$properties   = is_array( $input_schema['properties'] ?? null ) ? $input_schema['properties'] : array();

	npcink_governance_core_smoke_assert( 'write' === (string) ( $ability['risk_level'] ?? '' ), 'set-post-seo-meta is discovered as a write-risk ability' );
	npcink_governance_core_smoke_assert( true === (bool) ( $ability['requires_approval'] ?? false ), 'set-post-seo-meta is discovered as requiring approval' );
	npcink_governance_core_smoke_assert_capability_guidance( $ability, 'proposal_required', 'adapter_after_core_preflight', 'set-post-seo-meta uses proposal-required execution guidance' );
	npcink_governance_core_smoke_assert( in_array( 'post_id', $required, true ), 'set-post-seo-meta input schema requires post_id' );

	foreach ( array( 'seo_title', 'seo_description', 'dry_run', 'commit', 'idempotency_key' ) as $control ) {
		npcink_governance_core_smoke_assert( array_key_exists( $control, $properties ), 'set-post-seo-meta input schema exposes field/control ' . $control );
	}
}

/**
 * Verifies the preferred comment approval proposal target schema is discoverable.
 *
 * @param array<string,mixed> $items_by_id Capability rows keyed by ability id.
 * @return void
 */
function npcink_governance_core_smoke_assert_comment_approval_contract( array $items_by_id ): void {
	$ability_id = 'npcink-abilities-toolkit/approve-comment';
	npcink_governance_core_smoke_assert( isset( $items_by_id[ $ability_id ] ), 'approve-comment ability is discoverable for the third governance scenario' );

	$ability      = $items_by_id[ $ability_id ];
	$input_schema = is_array( $ability['input_schema'] ?? null ) ? $ability['input_schema'] : array();
	$required     = (array) ( $input_schema['required'] ?? array() );
	$properties   = is_array( $input_schema['properties'] ?? null ) ? $input_schema['properties'] : array();

	npcink_governance_core_smoke_assert( 'write' === (string) ( $ability['risk_level'] ?? '' ), 'approve-comment is discovered as a write-risk ability' );
	npcink_governance_core_smoke_assert( true === (bool) ( $ability['requires_approval'] ?? false ), 'approve-comment is discovered as requiring approval' );
	npcink_governance_core_smoke_assert_capability_guidance( $ability, 'proposal_required', 'adapter_after_core_preflight', 'approve-comment uses proposal-required execution guidance' );
	npcink_governance_core_smoke_assert( in_array( 'comment_id', $required, true ), 'approve-comment input schema requires comment_id' );

	foreach ( array( 'dry_run', 'commit', 'idempotency_key' ) as $control ) {
		npcink_governance_core_smoke_assert( array_key_exists( $control, $properties ), 'approve-comment input schema exposes governance control ' . $control );
	}
}

/**
 * Verifies the taxonomy terms preview-to-proposal abilities are discoverable.
 *
 * @param array<string,mixed> $items_by_id Capability rows keyed by ability id.
 * @return void
 */
function npcink_governance_core_smoke_assert_taxonomy_terms_contract( array $items_by_id ): void {
	$preview_id = 'npcink-abilities-toolkit/propose-post-taxonomy-terms';
	$target_id  = 'npcink-abilities-toolkit/set-post-terms';
	npcink_governance_core_smoke_assert( isset( $items_by_id[ $preview_id ] ), 'propose-post-taxonomy-terms ability is discoverable for taxonomy preview consumption' );
	npcink_governance_core_smoke_assert( isset( $items_by_id[ $target_id ] ), 'set-post-terms ability is discoverable for taxonomy proposal governance' );

	$preview            = $items_by_id[ $preview_id ];
	$preview_schema     = is_array( $preview['input_schema'] ?? null ) ? $preview['input_schema'] : array();
	$preview_required   = (array) ( $preview_schema['required'] ?? array() );
	$preview_properties = is_array( $preview_schema['properties'] ?? null ) ? $preview_schema['properties'] : array();

	npcink_governance_core_smoke_assert( 'read' === (string) ( $preview['risk_level'] ?? '' ), 'propose-post-taxonomy-terms is discovered as read risk' );
	npcink_governance_core_smoke_assert( false === (bool) ( $preview['requires_approval'] ?? true ), 'propose-post-taxonomy-terms does not require approval before read execution' );
	npcink_governance_core_smoke_assert_capability_guidance( $preview, 'core_read_authorization_required', 'wp_abilities_rest', 'propose-post-taxonomy-terms uses fail-closed read authorization guidance' );
	npcink_governance_core_smoke_assert_read_authorization_required( $preview, 'propose-post-taxonomy-terms undeclared sensitivity' );
	npcink_governance_core_smoke_assert( in_array( 'post_id', $preview_required, true ), 'propose-post-taxonomy-terms input schema requires post_id' );
	foreach ( array( 'taxonomy', 'mode', 'candidate_term_ids', 'candidate_terms' ) as $field ) {
		npcink_governance_core_smoke_assert( array_key_exists( $field, $preview_properties ), 'propose-post-taxonomy-terms input schema exposes field ' . $field );
	}

	$target            = $items_by_id[ $target_id ];
	$target_schema     = is_array( $target['input_schema'] ?? null ) ? $target['input_schema'] : array();
	$target_required   = (array) ( $target_schema['required'] ?? array() );
	$target_properties = is_array( $target_schema['properties'] ?? null ) ? $target_schema['properties'] : array();

	npcink_governance_core_smoke_assert( 'write' === (string) ( $target['risk_level'] ?? '' ), 'set-post-terms is discovered as a write-risk ability' );
	npcink_governance_core_smoke_assert( true === (bool) ( $target['requires_approval'] ?? false ), 'set-post-terms is discovered as requiring approval' );
	npcink_governance_core_smoke_assert_capability_guidance( $target, 'proposal_required', 'adapter_after_core_preflight', 'set-post-terms uses proposal-required execution guidance' );
	npcink_governance_core_smoke_assert( in_array( 'post_id', $target_required, true ), 'set-post-terms input schema requires post_id' );
	foreach ( array( 'taxonomy', 'mode', 'term_ids', 'terms', 'create_missing', 'dry_run', 'commit', 'idempotency_key' ) as $control ) {
		npcink_governance_core_smoke_assert( array_key_exists( $control, $target_properties ), 'set-post-terms input schema exposes field/control ' . $control );
	}
}

/**
 * Verifies planning abilities used by plan-to-proposal intake are discoverable.
 *
 * @param array<string,mixed> $items_by_id Capability rows keyed by ability id.
 * @return void
 */
function npcink_governance_core_smoke_assert_plan_bridge_contract( array $items_by_id ): void {
	foreach (
		array(
			'npcink-abilities-toolkit/build-content-inventory-fix-plan',
			'npcink-abilities-toolkit/build-nonproduction-content-cleanup-plan',
			'npcink-abilities-toolkit/build-media-inventory-fix-plan',
			'npcink-abilities-toolkit/build-article-optimization-apply-plan',
			'npcink-abilities-toolkit/build-media-adoption-enhancement-plan',
			'npcink-abilities-toolkit/build-pattern-page-plan',
		) as $ability_id
	) {
		npcink_governance_core_smoke_assert( isset( $items_by_id[ $ability_id ] ), $ability_id . ' is discoverable for plan-to-proposal intake' );
		npcink_governance_core_smoke_assert( 'read' === (string) ( $items_by_id[ $ability_id ]['risk_level'] ?? '' ), $ability_id . ' is a read-risk planning ability' );
		npcink_governance_core_smoke_assert( false === (bool) ( $items_by_id[ $ability_id ]['requires_approval'] ?? true ), $ability_id . ' does not require approval before read execution' );
		npcink_governance_core_smoke_assert_capability_guidance( $items_by_id[ $ability_id ], 'core_read_authorization_required', 'wp_abilities_rest', $ability_id . ' uses fail-closed read authorization guidance' );
		npcink_governance_core_smoke_assert( 'sensitive' === (string) ( $items_by_id[ $ability_id ]['sensitivity'] ?? '' ), $ability_id . ' defaults undeclared sensitivity to sensitive' );
		npcink_governance_core_smoke_assert( true === (bool) ( $items_by_id[ $ability_id ]['redaction_required'] ?? false ), $ability_id . ' requires redaction' );
		npcink_governance_core_smoke_assert_read_authorization_required( $items_by_id[ $ability_id ], $ability_id . ' requires Core read authorization' );
	}
}

/**
 * Verifies adapter execution guidance on a capability row.
 *
 * @param array<string,mixed> $ability Capability row.
 * @param string              $governance_mode Expected governance mode.
 * @param string              $execution_surface Expected execution surface.
 * @param string              $message Assertion message.
 * @return void
 */
function npcink_governance_core_smoke_assert_capability_guidance( array $ability, string $governance_mode, string $execution_surface, string $message ): void {
	npcink_governance_core_smoke_assert( $governance_mode === (string) ( $ability['governance_mode'] ?? '' ), $message . ' governance_mode' );
	npcink_governance_core_smoke_assert( $execution_surface === (string) ( $ability['execution_surface'] ?? '' ), $message . ' execution_surface' );
	npcink_governance_core_smoke_assert( false === (bool) ( $ability['core_proxy_execute'] ?? true ), $message . ' keeps Core proxy execution disabled' );
	npcink_governance_core_smoke_assert( false === (bool) ( $ability['commit_execution'] ?? true ), $message . ' keeps Core commit execution disabled' );
}

/**
 * Verifies read governance metadata on a capability row.
 *
 * @param array<string,mixed> $ability Capability row.
 * @param string              $read_policy Expected read policy.
 * @param string              $sensitivity Expected sensitivity.
 * @param bool                $redaction_required Expected redaction requirement.
 * @param string              $message Assertion message.
 * @return void
 */
function npcink_governance_core_smoke_assert_read_policy( array $ability, string $read_policy, string $sensitivity, bool $redaction_required, string $message ): void {
	npcink_governance_core_smoke_assert( $read_policy === (string) ( $ability['read_policy'] ?? '' ), $message . ' read_policy' );
	npcink_governance_core_smoke_assert( $sensitivity === (string) ( $ability['sensitivity'] ?? '' ), $message . ' sensitivity' );
	npcink_governance_core_smoke_assert( array_key_exists( 'redaction_required', $ability ) && $redaction_required === (bool) $ability['redaction_required'], $message . ' redaction_required' );
	npcink_governance_core_smoke_assert( 'adapter_read_envelope' === (string) ( $ability['read_audit_mode'] ?? '' ), $message . ' read_audit_mode' );
}

/**
 * Verifies provider-declared implementation posture on host-governed write abilities.
 *
 * @param array<string,mixed> $ability Capability row.
 * @param string              $ability_id Ability id.
 * @return void
 */
function npcink_governance_core_smoke_assert_implementation_posture( array $ability, string $ability_id ): void {
	$posture = is_array( $ability['implementation_posture'] ?? null ) ? $ability['implementation_posture'] : array();

	npcink_governance_core_smoke_assert( true === (bool) ( $ability['implementation_posture_available'] ?? false ), $ability_id . ' exposes implementation posture availability' );
	npcink_governance_core_smoke_assert( 'npcink_abilities_toolkit_implementation_posture.v1' === (string) ( $posture['schema_version'] ?? '' ), $ability_id . ' exposes implementation posture schema' );
	npcink_governance_core_smoke_assert( 'host_governed_dry_run_first' === (string) ( $posture['write_posture'] ?? '' ), $ability_id . ' declares host-governed dry-run-first write posture' );
	npcink_governance_core_smoke_assert( 'host_runtime_approval_context_required' === (string) ( $posture['commit_authority'] ?? '' ), $ability_id . ' declares host runtime approval context authority' );
	npcink_governance_core_smoke_assert( 'host_governance_layer' === (string) ( $posture['final_authorization_owner'] ?? '' ), $ability_id . ' leaves final authorization truth with host governance' );
	npcink_governance_core_smoke_assert( 'host_governance_layer' === (string) ( $posture['approval_truth_owner'] ?? '' ), $ability_id . ' leaves approval truth with host governance' );
	npcink_governance_core_smoke_assert( 'host_governance_layer' === (string) ( $posture['audit_truth_owner'] ?? '' ), $ability_id . ' leaves audit truth with host governance' );
	npcink_governance_core_smoke_assert( true === (bool) ( $posture['dry_run_default'] ?? false ), $ability_id . ' keeps dry-run default enabled' );
	npcink_governance_core_smoke_assert( false === (bool) ( $posture['commit_default'] ?? true ), $ability_id . ' keeps commit default disabled' );
	npcink_governance_core_smoke_assert( false === (bool) ( $posture['direct_wordpress_write_default'] ?? true ), $ability_id . ' keeps direct WordPress write default disabled' );
	foreach ( array( 'reference_patterns', 'verification_contract', 'required_host_evidence' ) as $list_field ) {
		npcink_governance_core_smoke_assert( count( (array) ( $posture[ $list_field ] ?? array() ) ) > 0, $ability_id . ' exposes implementation posture ' . $list_field );
	}
	foreach ( array( 'workflow_runtime', 'queue_or_scheduler', 'model_routing', 'provider_credentials', 'approval_storage', 'audit_storage' ) as $flag ) {
		npcink_governance_core_smoke_assert( false === (bool) ( $posture[ $flag ] ?? true ), $ability_id . ' does not declare Core-forbidden ownership flag ' . $flag );
	}
}

/**
 * Verifies Core-owned sensitive read authorization guidance.
 *
 * @param array<string,mixed> $ability Capability row.
 * @param string              $message Assertion message.
 * @return void
 */
function npcink_governance_core_smoke_assert_read_authorization_required( array $ability, string $message ): void {
	$read_authorization = is_array( $ability['read_authorization'] ?? null ) ? $ability['read_authorization'] : array();

	npcink_governance_core_smoke_assert( true === (bool) ( $ability['read_authorization_required'] ?? false ), $message . ' read_authorization_required' );
	npcink_governance_core_smoke_assert( true === (bool) ( $ability['requires_read_authorization'] ?? false ), $message . ' requires_read_authorization' );
	npcink_governance_core_smoke_assert( true === (bool) ( $read_authorization['required'] ?? false ), $message . ' read_authorization.required' );
	npcink_governance_core_smoke_assert( 'core_read_authorization_required' === (string) ( $ability['read_policy'] ?? '' ), $message . ' read_policy' );
	npcink_governance_core_smoke_assert( 'core_read_authorization_required' === (string) ( $ability['governance_mode'] ?? '' ), $message . ' governance_mode' );
	npcink_governance_core_smoke_assert( 'core_read_request' === (string) ( $ability['authorization_mode'] ?? '' ), $message . ' authorization_mode' );
	npcink_governance_core_smoke_assert( '/wp-json/npcink-governance-core/v1/read-requests' === (string) ( $ability['read_authorization_request_route'] ?? '' ), $message . ' request route guidance' );
	npcink_governance_core_smoke_assert( false !== strpos( (string) ( $ability['read_authorization_preflight_route'] ?? '' ), '/read-requests/{request_id}/read-preflight' ), $message . ' preflight route guidance' );
	npcink_governance_core_smoke_assert( false !== strpos( (string) ( $ability['read_authorization_status_route'] ?? '' ), '/read-requests/{request_id}' ), $message . ' status route guidance' );
}

