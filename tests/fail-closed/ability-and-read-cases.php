<?php
/** Ability and read cases test module. @package NpcinkGovernanceCore */

$wpdb = npcink_governance_core_fail_closed_reset_db();
global $npcink_governance_core_fail_closed_native_abilities;
$npcink_governance_core_fail_closed_native_abilities = array(
	'third-party/native-only' => new class() {
		public function get_name(): string {
			return 'third-party/native-only';
		}

		public function get_label(): string {
			return 'Native Only';
		}

		public function get_description(): string {
			return 'Registered only through WordPress Abilities API.';
		}

		public function get_input_schema(): array {
			return array( 'type' => 'object' );
		}

		public function get_output_schema(): array {
			return array( 'type' => 'object' );
		}

		public function get_meta(): array {
			return array(
				'annotations'  => array(
					'readonly'    => false,
					'destructive' => false,
				),
				'show_in_rest' => true,
				'npcink' => array(
					'risk_level'       => 'write',
					'requires_approval' => true,
					'required_scope'   => 'third-party.write',
				),
			);
		}
	},
	'third-party/annotation-destructive' => array(
		'ability_id' => 'third-party/annotation-destructive',
		'label'      => 'Annotation Destructive',
		'meta'       => array(
			'annotations'  => array(
				'readonly'    => false,
				'destructive' => true,
			),
			'show_in_rest' => true,
		),
	),
	'third-party/ambiguous-risk' => array(
		'ability_id' => 'third-party/ambiguous-risk',
		'label'      => 'Ambiguous Risk',
		'meta'       => array(
			'annotations'  => array(
				'readonly'    => null,
				'destructive' => null,
			),
			'show_in_rest' => true,
		),
	),
	'third-party/conflicting-risk' => array(
		'ability_id' => 'third-party/conflicting-risk',
		'label'      => 'Conflicting Risk',
		'risk_level' => 'read',
		'meta'       => array(
			'annotations'  => array(
				'readonly'    => false,
				'destructive' => true,
			),
			'show_in_rest' => true,
		),
	),
	'third-party/provider-risk-conflict' => array(
		'ability_id' => 'third-party/provider-risk-conflict',
		'label'      => 'Provider Risk Conflict',
		'risk_level' => 'read',
		'meta'       => array(
			'annotations'  => array( 'readonly' => null, 'destructive' => null ),
			'show_in_rest' => true,
			'npcink'       => array( 'risk_level' => 'destructive' ),
		),
	),
	'npcink-abilities-toolkit/update-post' => array(
		'ability_id'        => 'npcink-abilities-toolkit/update-post',
		'label'             => 'Cross Source Risk Conflict',
		'risk_level'        => 'read',
		'requires_approval' => false,
		'sensitivity'       => 'public',
		'meta'              => array(
			'annotations'  => array( 'readonly' => true, 'destructive' => false ),
			'show_in_rest' => true,
		),
	),
	'npcink-abilities-toolkit/patch-post-content' => array(
		'ability_id'        => 'npcink-abilities-toolkit/patch-post-content',
		'label'             => 'Cross Source Annotation Conflict',
		'risk_level'        => 'write',
		'requires_approval' => true,
		'meta'              => array(
			'annotations'  => array( 'readonly' => true, 'destructive' => false ),
			'show_in_rest' => true,
		),
	),
	'third-party/invalid-risk-alias' => array(
		'ability_id' => 'third-party/invalid-risk-alias',
		'label'      => 'Invalid Risk Alias',
		'risk_level' => 'high',
		'meta'       => array(
			'annotations'  => array( 'readonly' => null, 'destructive' => null ),
			'show_in_rest' => true,
		),
	),
	'third-party/rest-hidden' => array(
		'ability_id' => 'third-party/rest-hidden',
		'label'      => 'REST Hidden',
		'risk_level' => 'read',
		'meta'       => array(
			'annotations'  => array(
				'readonly'    => true,
				'destructive' => false,
			),
			'show_in_rest' => false,
		),
	),
	'third-party/rest-undeclared' => array(
		'ability_id' => 'third-party/rest-undeclared',
		'label'      => 'REST Undeclared',
		'risk_level' => 'read',
		'meta'       => array(
			'annotations' => array( 'readonly' => true, 'destructive' => false ),
		),
	),
	'third-party/rest-top-level-only' => array(
		'ability_id'   => 'third-party/rest-top-level-only',
		'label'        => 'REST Top Level Only',
		'risk_level'   => 'read',
		'show_in_rest' => true,
		'meta'         => array(
			'annotations' => array( 'readonly' => true, 'destructive' => false ),
		),
	),
	'third-party/rest-npcink-only' => array(
		'ability_id' => 'third-party/rest-npcink-only',
		'label'      => 'REST Npcink Only',
		'risk_level' => 'read',
		'meta'       => array(
			'annotations' => array( 'readonly' => true, 'destructive' => false ),
			'npcink'      => array( 'show_in_rest' => true ),
		),
	),
	'third-party/rest-string-true' => array(
		'ability_id' => 'third-party/rest-string-true',
		'label'      => 'REST String True',
		'risk_level' => 'read',
		'meta'       => array(
			'annotations'  => array( 'readonly' => true, 'destructive' => false ),
			'show_in_rest' => '1',
		),
	),
	'third-party/undeclared-sensitivity' => array(
		'ability_id' => 'third-party/undeclared-sensitivity',
		'label'      => 'Undeclared Sensitivity',
		'risk_level' => 'read',
		'meta'       => array(
			'annotations'  => array(
				'readonly'    => true,
				'destructive' => false,
			),
			'show_in_rest' => true,
		),
	),
	'npcink-toolbox/build-article-write-plan' => array(
		'ability_id' => 'npcink-toolbox/build-article-write-plan',
		'label'      => 'REST Hidden Article Plan',
		'meta'       => array(
			'annotations'  => array(
				'readonly'    => true,
				'destructive' => false,
			),
			'show_in_rest' => false,
		),
	),
	'npcink-abilities-toolkit/create-draft' => array(
		'ability_id'        => 'npcink-abilities-toolkit/create-draft',
		'label'             => 'Native Aggregate Create Draft',
		'risk_level'        => 'write',
		'requires_approval' => true,
	),
);

$discovered_capabilities = ( new \Npcink\GovernanceCore\Capabilities\Ability_Registry_Adapter() )->list_capabilities();
$discovered_items = array();
foreach ( (array) ( $discovered_capabilities['items'] ?? array() ) as $item ) {
	if ( is_array( $item ) ) {
		$discovered_items[ (string) ( $item['ability_id'] ?? '' ) ] = $item;
	}
}
npcink_governance_core_fail_closed_assert( 'wordpress_abilities_api' === (string) ( $discovered_capabilities['source'] ?? '' ), 'WordPress Abilities API is the canonical aggregate discovery source.' );
npcink_governance_core_fail_closed_assert( isset( $discovered_items['third-party/native-only'] ), 'Ability intake discovers an ability registered only through WordPress Abilities API.' );
npcink_governance_core_fail_closed_assert( 'Native Only' === (string) ( $discovered_items['third-party/native-only']['label'] ?? '' ), 'Ability intake normalizes public WP_Ability getters.' );
npcink_governance_core_fail_closed_assert( 'write' === (string) ( $discovered_items['third-party/native-only']['risk_level'] ?? '' ), 'Ability intake normalizes nested provider risk metadata from WP_Ability.' );
npcink_governance_core_fail_closed_assert( true === (bool) ( $discovered_items['third-party/native-only']['requires_approval'] ?? false ), 'Ability intake normalizes nested provider approval metadata from WP_Ability.' );
npcink_governance_core_fail_closed_assert( 'wordpress_abilities_api' === (string) ( $discovered_items['third-party/native-only']['source'] ?? '' ), 'Native-only ability retains its discovery source.' );
npcink_governance_core_fail_closed_assert( isset( $discovered_items['npcink-abilities-toolkit/read-error-log'] ), 'Toolkit compatibility discovery fills abilities missing from the native aggregate.' );
npcink_governance_core_fail_closed_assert( 'Native Aggregate Create Draft' === (string) ( $discovered_items['npcink-abilities-toolkit/create-draft']['label'] ?? '' ), 'Native aggregate definition wins when both public sources contain the same ability id.' );
npcink_governance_core_fail_closed_assert( in_array( 'post.write', (array) ( $discovered_items['npcink-abilities-toolkit/create-draft']['required_scopes'] ?? array() ), true ), 'Toolkit compatibility definition fills governance metadata absent from the native object.' );
npcink_governance_core_fail_closed_assert( (int) ( $discovered_capabilities['count'] ?? 0 ) === (int) ( $discovered_capabilities['ready_count'] ?? 0 ) + (int) ( $discovered_capabilities['blocked_count'] ?? 0 ), 'Ability intake ready and blocked counts cover every discovered row.' );
npcink_governance_core_fail_closed_assert( 'ready' === (string) ( $discovered_items['third-party/annotation-destructive']['intake_status'] ?? '' ), 'WordPress annotations can provide complete intake risk evidence.' );
npcink_governance_core_fail_closed_assert( 'destructive' === (string) ( $discovered_items['third-party/annotation-destructive']['risk_level'] ?? '' ), 'Destructive WordPress annotation maps to destructive Core risk.' );
npcink_governance_core_fail_closed_assert( true === (bool) ( $discovered_items['third-party/annotation-destructive']['requires_approval'] ?? false ), 'Destructive annotation forces proposal approval.' );
npcink_governance_core_fail_closed_assert( 'blocked' === (string) ( $discovered_items['third-party/ambiguous-risk']['intake_status'] ?? '' ), 'Missing risk evidence is blocked instead of defaulting to read.' );
npcink_governance_core_fail_closed_assert( in_array( 'risk_undeclared', (array) ( $discovered_items['third-party/ambiguous-risk']['intake_reasons'] ?? array() ), true ), 'Missing risk uses a stable intake reason.' );
npcink_governance_core_fail_closed_assert( 'none' === (string) ( $discovered_items['third-party/ambiguous-risk']['execution_surface'] ?? '' ), 'Blocked ability has no execution surface.' );
npcink_governance_core_fail_closed_assert( in_array( 'risk_annotations_conflict', (array) ( $discovered_items['third-party/conflicting-risk']['intake_reasons'] ?? array() ), true ), 'Conflicting provider risk and WordPress annotations fail closed.' );
npcink_governance_core_fail_closed_assert( 'destructive' === (string) ( $discovered_items['third-party/provider-risk-conflict']['risk_level'] ?? '' ), 'Conflicting provider risk sources retain the strongest observed risk for diagnostics.' );
npcink_governance_core_fail_closed_assert( in_array( 'risk_sources_conflict', (array) ( $discovered_items['third-party/provider-risk-conflict']['intake_reasons'] ?? array() ), true ), 'Conflicting provider risk sources fail closed instead of using the first declaration.' );
npcink_governance_core_fail_closed_assert( 'blocked' === (string) ( $discovered_items['npcink-abilities-toolkit/update-post']['intake_status'] ?? '' ), 'Duplicate ability ids retain cross-discovery-source risk evidence before merge.' );
npcink_governance_core_fail_closed_assert( in_array( 'risk_sources_conflict', (array) ( $discovered_items['npcink-abilities-toolkit/update-post']['intake_reasons'] ?? array() ), true ), 'Cross-discovery-source risk conflict fails closed.' );
npcink_governance_core_fail_closed_assert( 'blocked' === (string) ( $discovered_items['npcink-abilities-toolkit/patch-post-content']['intake_status'] ?? '' ), 'Duplicate ability ids retain cross-discovery-source annotation evidence before merge.' );
npcink_governance_core_fail_closed_assert( in_array( 'annotations_conflict', (array) ( $discovered_items['npcink-abilities-toolkit/patch-post-content']['intake_reasons'] ?? array() ), true ), 'Cross-discovery-source annotation conflict fails closed.' );
npcink_governance_core_fail_closed_assert( 'blocked' === (string) ( $discovered_items['third-party/invalid-risk-alias']['intake_status'] ?? '' ), 'Risk aliases outside the exact closed set fail closed.' );
npcink_governance_core_fail_closed_assert( in_array( 'risk_invalid', (array) ( $discovered_items['third-party/invalid-risk-alias']['intake_reasons'] ?? array() ), true ), 'Invalid risk aliases expose a stable intake reason.' );
npcink_governance_core_fail_closed_assert( in_array( 'rest_exposure_disabled', (array) ( $discovered_items['third-party/rest-hidden']['intake_reasons'] ?? array() ), true ), 'Explicitly REST-hidden ability fails closed.' );
npcink_governance_core_fail_closed_assert( in_array( 'rest_exposure_undeclared', (array) ( $discovered_items['third-party/rest-undeclared']['intake_reasons'] ?? array() ), true ), 'Missing REST exposure declaration fails closed.' );
npcink_governance_core_fail_closed_assert( in_array( 'rest_exposure_undeclared', (array) ( $discovered_items['third-party/rest-top-level-only']['intake_reasons'] ?? array() ), true ), 'Top-level REST mirror cannot replace canonical meta.show_in_rest.' );
npcink_governance_core_fail_closed_assert( in_array( 'rest_exposure_undeclared', (array) ( $discovered_items['third-party/rest-npcink-only']['intake_reasons'] ?? array() ), true ), 'Npcink REST mirror cannot replace canonical meta.show_in_rest.' );
npcink_governance_core_fail_closed_assert( in_array( 'rest_exposure_invalid', (array) ( $discovered_items['third-party/rest-string-true']['intake_reasons'] ?? array() ), true ), 'Canonical REST exposure requires a literal boolean true.' );
npcink_governance_core_fail_closed_assert( 'sensitive' === (string) ( $discovered_items['third-party/undeclared-sensitivity']['sensitivity'] ?? '' ), 'Undeclared read sensitivity defaults to sensitive.' );
npcink_governance_core_fail_closed_assert( true === (bool) ( $discovered_items['third-party/undeclared-sensitivity']['redaction_required'] ?? false ), 'Undeclared sensitive read requires redaction.' );
npcink_governance_core_fail_closed_assert( true === (bool) ( $discovered_items['third-party/undeclared-sensitivity']['read_authorization_required'] ?? false ), 'Undeclared read sensitivity requires Core read authorization.' );

$read_proposal = npcink_governance_core_fail_closed_proposal_stack()['service']->create(
	array(
		'ability_id' => 'third-party/undeclared-sensitivity',
		'input'      => array(),
	)
);
npcink_governance_core_fail_closed_assert( is_wp_error( $read_proposal ), 'A ready read ability cannot enter proposal intake.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_ability_not_proposal_eligible' === $read_proposal->get_error_code(), 'Ready read proposal rejection uses stable error code.' );

$wpdb = npcink_governance_core_fail_closed_reset_db();
$npcink_governance_core_fail_closed_native_abilities = array(
	'third-party/ambiguous-risk' => array(
		'ability_id' => 'third-party/ambiguous-risk',
		'label'      => 'Ambiguous Risk',
		'meta'       => array(
			'annotations'  => array( 'readonly' => null, 'destructive' => null ),
			'show_in_rest' => true,
		),
	),
	'npcink-toolbox/build-article-write-plan' => array(
		'ability_id' => 'npcink-toolbox/build-article-write-plan',
		'label'      => 'REST Hidden Article Plan',
		'meta'       => array(
			'annotations'  => array( 'readonly' => true, 'destructive' => false ),
			'show_in_rest' => false,
		),
	),
);
$blocked_proposal = npcink_governance_core_fail_closed_proposal_stack()['service']->create(
	array(
		'ability_id' => 'third-party/ambiguous-risk',
		'input'      => array(),
	)
);
npcink_governance_core_fail_closed_assert( is_wp_error( $blocked_proposal ), 'Proposal creation rejects a discovered but blocked ability.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_ability_intake_blocked' === $blocked_proposal->get_error_code(), 'Blocked proposal intake uses stable error code.' );

$blocked_plan = npcink_governance_core_fail_closed_plan_stack()['service']->create_from_plan( 'npcink-toolbox/build-article-write-plan', array() );
npcink_governance_core_fail_closed_assert( is_wp_error( $blocked_plan ), 'Plan intake rejects a discovered but blocked planning ability before payload validation.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_plan_ability_intake_blocked' === $blocked_plan->get_error_code(), 'Blocked planning ability uses stable error code.' );

$blocked_read = npcink_governance_core_fail_closed_read_request_stack()['service']->create(
	array(
		'ability_id' => 'third-party/ambiguous-risk',
		'input'      => array( 'probe' => true ),
	)
);
npcink_governance_core_fail_closed_assert( is_wp_error( $blocked_read ), 'Sensitive read request rejects a discovered but blocked ability.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_ability_intake_blocked' === $blocked_read->get_error_code(), 'Blocked sensitive read intake uses stable error code.' );

$wpdb = npcink_governance_core_fail_closed_reset_db();
$capabilities = ( new \Npcink\GovernanceCore\Capabilities\Ability_Registry_Adapter() )->list_capabilities();
$sensitive_capability = array();
foreach ( (array) ( $capabilities['items'] ?? array() ) as $item ) {
	if ( is_array( $item ) && 'npcink-abilities-toolkit/read-error-log' === (string) ( $item['ability_id'] ?? '' ) ) {
		$sensitive_capability = $item;
		break;
	}
}
npcink_governance_core_fail_closed_assert( true === (bool) ( $sensitive_capability['read_authorization_required'] ?? false ), 'Capability can mark sensitive read authorization required.' );
npcink_governance_core_fail_closed_assert( true === (bool) ( $sensitive_capability['requires_read_authorization'] ?? false ), 'Capability exposes Adapter-compatible requires_read_authorization.' );
npcink_governance_core_fail_closed_assert( 'core_read_authorization_required' === (string) ( $sensitive_capability['read_policy'] ?? '' ), 'Capability exposes Core read authorization read_policy.' );
npcink_governance_core_fail_closed_assert( 'core_read_request' === (string) ( $sensitive_capability['authorization_mode'] ?? '' ), 'Capability exposes Core read request authorization_mode.' );
npcink_governance_core_fail_closed_assert( true === (bool) ( $sensitive_capability['read_authorization']['required'] ?? false ), 'Capability exposes nested read_authorization.required.' );
npcink_governance_core_fail_closed_assert( false !== strpos( (string) ( $sensitive_capability['read_authorization_preflight_route'] ?? '' ), '/read-requests/{request_id}/read-preflight' ), 'Capability exposes read authorization preflight route guidance.' );

$wpdb = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_read_request_stack();
$read_payload = array(
	'ability_id'              => 'npcink-abilities-toolkit/read-error-log',
	'input'                   => array(
		'tail'   => true,
		'filter' => 'recent-errors',
	),
	'requested_input_summary' => 'Tail recent PHP errors. Authorization: Bearer SHOULD_NOT_LEAK',
	'sensitivity'             => 'sensitive',
	'data_classes'            => array( 'logs', 'diagnostics' ),
	'redaction_level'         => 'strict',
	'purpose'                 => 'Debug a failed smoke run.',
	'caller'                  => array(
		'source'        => 'fault_injection',
		'authorization' => 'Bearer SHOULD_NOT_LEAK',
		'cookie'        => 'wordpress_logged_in=SHOULD_NOT_LEAK',
	),
	'max_rows'                => 500,
	'tail_lines'              => 500,
	'allowed_fields'          => array( 'timestamp', 'message', 'cookie' ),
	'denied_fields'           => array( 'stack_trace' ),
);

$missing_data_classes_payload = $read_payload;
$missing_data_classes_payload['input'] = array( 'tail' => true, 'filter' => 'missing-data-classes' );
unset( $missing_data_classes_payload['data_classes'] );
$missing_data_classes = $stack['service']->create( $missing_data_classes_payload );
npcink_governance_core_fail_closed_assert( is_wp_error( $missing_data_classes ), 'Sensitive read request fails without data classes.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_read_request_data_classes_required' === $missing_data_classes->get_error_code(), 'Missing data classes uses stable error code.' );

$missing_purpose_payload = $read_payload;
$missing_purpose_payload['input'] = array( 'tail' => true, 'filter' => 'missing-purpose' );
unset( $missing_purpose_payload['purpose'] );
$missing_purpose = $stack['service']->create( $missing_purpose_payload );
npcink_governance_core_fail_closed_assert( is_wp_error( $missing_purpose ), 'Sensitive read request fails without purpose.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_read_request_purpose_required' === $missing_purpose->get_error_code(), 'Missing purpose uses stable error code.' );

$unredacted_payload = $read_payload;
$unredacted_payload['input'] = array( 'tail' => true, 'filter' => 'no-redaction' );
$unredacted_payload['redaction_level'] = 'none';
$unredacted_request = $stack['service']->create( $unredacted_payload );
npcink_governance_core_fail_closed_assert( is_wp_error( $unredacted_request ), 'Sensitive read request fails when redaction is disabled.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_read_request_redaction_required' === $unredacted_request->get_error_code(), 'Disabled redaction uses stable error code.' );

$unredacted_approval_request = $stack['service']->create(
	array_merge(
		$read_payload,
		array(
			'input' => array( 'tail' => true, 'filter' => 'approval-no-redaction' ),
		)
	)
);
npcink_governance_core_fail_closed_assert( ! is_wp_error( $unredacted_approval_request ), 'Sensitive read request is created for approval redaction guard.' );
$unredacted_approval = $stack['service']->approve( (string) $unredacted_approval_request['request_id'], array( 'redaction_level' => 'none' ) );
npcink_governance_core_fail_closed_assert( is_wp_error( $unredacted_approval ), 'Sensitive read approval fails when redaction is disabled.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_read_request_redaction_required' === $unredacted_approval->get_error_code(), 'Approval disabled redaction uses stable error code.' );

$read_request = $stack['service']->create( $read_payload );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $read_request ), 'Sensitive read request is created.' );
npcink_governance_core_fail_closed_assert( 'pending' === (string) ( $read_request['status'] ?? '' ), 'Sensitive read request starts pending.' );
npcink_governance_core_fail_closed_assert( '' !== (string) ( $read_request['request_id'] ?? '' ), 'Sensitive read request returns request_id.' );
npcink_governance_core_fail_closed_assert( '' !== (string) ( $read_request['input_hash'] ?? '' ), 'Sensitive read request binds input_hash.' );
npcink_governance_core_fail_closed_assert( false === strpos( wp_json_encode( $read_request ), 'SHOULD_NOT_LEAK' ), 'Sensitive read request response does not emit secrets.' );
npcink_governance_core_fail_closed_assert( 1 === count( npcink_governance_core_fail_closed_audit_rows( (string) $read_request['request_id'], 'read_request.created' ) ), 'Sensitive read request creation is audited.' );

$approved_read = $stack['service']->approve(
	(string) $read_request['request_id'],
	array(
		'note'           => 'Approve bounded diagnostic read.',
		'max_rows'       => 80,
		'tail_lines'     => 120,
		'allowed_fields' => array( 'timestamp', 'message', 'severity', 'cookie' ),
		'denied_fields'  => array( 'authorization' ),
	)
);
npcink_governance_core_fail_closed_assert( ! is_wp_error( $approved_read ) && 'approved' === (string) ( $approved_read['status'] ?? '' ), 'Sensitive read request can be approved.' );
npcink_governance_core_fail_closed_assert( 50 === (int) ( $approved_read['bounds']['max_rows'] ?? 0 ), 'Sensitive read approval cannot expand provider max_rows.' );
npcink_governance_core_fail_closed_assert( 100 === (int) ( $approved_read['bounds']['tail_lines'] ?? 0 ), 'Sensitive read approval cannot expand provider tail_lines.' );
npcink_governance_core_fail_closed_assert( ! in_array( 'cookie', (array) ( $approved_read['bounds']['allowed_fields'] ?? array() ), true ), 'Sensitive read approval cannot allow fields outside provider scope.' );
npcink_governance_core_fail_closed_assert( in_array( 'authorization', (array) ( $approved_read['bounds']['denied_fields'] ?? array() ), true ), 'Sensitive read approval preserves denied fields.' );
npcink_governance_core_fail_closed_assert( 1 === count( npcink_governance_core_fail_closed_audit_rows( (string) $read_request['request_id'], 'read_request.approved' ) ), 'Sensitive read approval is audited.' );

$read_signed_client_fingerprint = 'sha256:' . str_repeat( 'b', 64 );
\Npcink\GovernanceCore\Security\Request_Context::set_app(
	array(
		'app_id'                    => 'adapter_app_read_preflight',
		'key_id'                    => 'adapter_key_read_preflight',
		'caller_type'               => 'openclaw_adapter',
		'scope'                     => 'read_requests:preflight',
		'scopes'                    => array( 'read_requests:preflight' ),
		'route_family'              => 'read_requests_preflight',
		'signed_client_fingerprint' => $read_signed_client_fingerprint,
	)
);
$grant = $stack['service']->preflight(
	(string) $read_request['request_id'],
	array(
		'ability_id' => 'npcink-abilities-toolkit/read-error-log',
		'input'      => $read_payload['input'],
	)
);
npcink_governance_core_fail_closed_assert( ! is_wp_error( $grant ), 'Approved sensitive read request returns grant context.' );
$grant_context = is_array( $grant['read_authorization_context'] ?? null ) ? $grant['read_authorization_context'] : array();
npcink_governance_core_fail_closed_assert( true === (bool) ( $grant_context['read_authorization_granted'] ?? false ), 'Grant context marks read_authorization_granted=true.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core' === (string) ( $grant_context['core_authorization_truth'] ?? '' ), 'Grant context records Core as authorization truth.' );
npcink_governance_core_fail_closed_assert( false === (bool) ( $grant_context['commit_execution'] ?? true ), 'Grant context has commit_execution=false.' );
npcink_governance_core_fail_closed_assert( false === (bool) ( $grant_context['write_execution'] ?? true ), 'Grant context has write_execution=false.' );
npcink_governance_core_fail_closed_assert( (string) ( $approved_read['input_hash'] ?? '' ) === (string) ( $grant_context['approved_input_hash'] ?? '' ), 'Grant context binds approved input hash.' );
npcink_governance_core_fail_closed_assert( $read_signed_client_fingerprint === (string) ( $grant_context['signed_client_fingerprint'] ?? '' ), 'Grant context binds signed client fingerprint.' );
npcink_governance_core_fail_closed_assert( $read_signed_client_fingerprint === (string) ( $grant_context['client_key_fingerprint'] ?? '' ), 'Grant context binds client key fingerprint alias.' );
npcink_governance_core_fail_closed_assert( 1 === count( npcink_governance_core_fail_closed_audit_rows( (string) $read_request['request_id'], 'read_request.preflighted' ) ), 'Sensitive read preflight/grant is audited.' );

$hash_only_grant = $stack['service']->preflight(
	(string) $read_request['request_id'],
	array(
		'ability_id'  => 'npcink-abilities-toolkit/read-error-log',
		'input_hash'  => (string) ( $read_request['input_hash'] ?? '' ),
	)
);
npcink_governance_core_fail_closed_assert( is_wp_error( $hash_only_grant ), 'Sensitive read preflight rejects hash-only grants.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_read_request_input_required_for_sensitive_preflight' === $hash_only_grant->get_error_code(), 'Hash-only sensitive read preflight uses stable error code.' );
npcink_governance_core_fail_closed_assert( 1 === count( npcink_governance_core_fail_closed_audit_rows( (string) $read_request['request_id'], 'read_request.preflight_failed' ) ), 'Hash-only sensitive read preflight failure is audited.' );

$wrong_ability = $stack['service']->preflight(
	(string) $read_request['request_id'],
	array(
		'ability_id' => 'npcink-abilities-toolkit/build-article-block-plan',
		'input'      => $read_payload['input'],
	)
);
npcink_governance_core_fail_closed_assert( is_wp_error( $wrong_ability ), 'Sensitive read grant rejects wrong ability_id.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_read_request_ability_mismatch' === $wrong_ability->get_error_code(), 'Wrong ability grant uses stable error code.' );

$wrong_input = $stack['service']->preflight(
	(string) $read_request['request_id'],
	array(
		'ability_id' => 'npcink-abilities-toolkit/read-error-log',
		'input'      => array( 'tail' => true, 'filter' => 'all-errors' ),
	)
);
npcink_governance_core_fail_closed_assert( is_wp_error( $wrong_input ), 'Sensitive read grant rejects changed input hash.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_read_request_input_mismatch' === $wrong_input->get_error_code(), 'Changed input grant uses stable error code.' );

$rejected_request = $stack['service']->create(
	array_merge(
		$read_payload,
		array(
			'input' => array( 'tail' => true, 'filter' => 'rejected-errors' ),
		)
	)
);
npcink_governance_core_fail_closed_assert( ! is_wp_error( $rejected_request ), 'Second sensitive read request is created for rejection.' );
$rejected_read = $stack['service']->reject( (string) $rejected_request['request_id'], array( 'note' => 'Reject diagnostic read.' ) );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $rejected_read ) && 'rejected' === (string) ( $rejected_read['status'] ?? '' ), 'Sensitive read request can be rejected.' );
npcink_governance_core_fail_closed_assert( 1 === count( npcink_governance_core_fail_closed_audit_rows( (string) $rejected_request['request_id'], 'read_request.rejected' ) ), 'Sensitive read rejection is audited.' );
$rejected_grant = $stack['service']->preflight(
	(string) $rejected_request['request_id'],
	array(
		'ability_id' => 'npcink-abilities-toolkit/read-error-log',
		'input'      => array( 'tail' => true, 'filter' => 'rejected-errors' ),
	)
);
npcink_governance_core_fail_closed_assert( is_wp_error( $rejected_grant ), 'Rejected sensitive read request cannot grant.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_read_request_not_approved' === $rejected_grant->get_error_code(), 'Rejected read grant uses not-approved error code.' );

$expired_request = $stack['service']->create(
	array_merge(
		$read_payload,
		array(
			'input' => array( 'tail' => true, 'filter' => 'expired-errors' ),
		)
	)
);
npcink_governance_core_fail_closed_assert( ! is_wp_error( $expired_request ), 'Third sensitive read request is created for expiry.' );
$expired_approved = $stack['service']->approve( (string) $expired_request['request_id'] );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $expired_approved ), 'Expiring sensitive read request can be approved.' );
$wpdb->update(
	$read_request_table,
	array( 'expires_at' => gmdate( 'Y-m-d H:i:s', time() - 60 ) ),
	array( 'request_id' => (string) $expired_request['request_id'] )
);
$expired_grant = $stack['service']->preflight(
	(string) $expired_request['request_id'],
	array(
		'ability_id' => 'npcink-abilities-toolkit/read-error-log',
		'input'      => array( 'tail' => true, 'filter' => 'expired-errors' ),
	)
);
npcink_governance_core_fail_closed_assert( is_wp_error( $expired_grant ), 'Expired sensitive read grant is rejected.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_read_request_expired' === $expired_grant->get_error_code(), 'Expired sensitive read grant uses stable error code.' );

$one_time_request = $stack['service']->create(
	array_merge(
		$read_payload,
		array(
			'input'    => array( 'tail' => true, 'filter' => 'one-time-errors' ),
			'one_time' => true,
		)
	)
);
npcink_governance_core_fail_closed_assert( ! is_wp_error( $one_time_request ), 'One-time sensitive read request is created.' );
$one_time_approved = $stack['service']->approve( (string) $one_time_request['request_id'], array( 'one_time' => true ) );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $one_time_approved ), 'One-time sensitive read request is approved.' );
$one_time_grant = $stack['service']->preflight(
	(string) $one_time_request['request_id'],
	array(
		'ability_id' => 'npcink-abilities-toolkit/read-error-log',
		'input'      => array( 'tail' => true, 'filter' => 'one-time-errors' ),
	)
);
npcink_governance_core_fail_closed_assert( ! is_wp_error( $one_time_grant ), 'One-time sensitive read request grants once.' );
$consumed_request = $stack['requests']->find( (string) $one_time_request['request_id'] );
npcink_governance_core_fail_closed_assert( is_array( $consumed_request ) && 'consumed' === (string) ( $consumed_request['status'] ?? '' ), 'One-time sensitive read request is consumed after grant.' );
$one_time_replay = $stack['service']->preflight(
	(string) $one_time_request['request_id'],
	array(
		'ability_id' => 'npcink-abilities-toolkit/read-error-log',
		'input'      => array( 'tail' => true, 'filter' => 'one-time-errors' ),
	)
);
npcink_governance_core_fail_closed_assert( is_wp_error( $one_time_replay ), 'Consumed one-time sensitive read request cannot be reused.' );

$one_time_failed_consume_audit_request = $stack['service']->create(
	array_merge(
		$read_payload,
		array(
			'input'    => array( 'tail' => true, 'filter' => 'one-time-consume-audit-fails' ),
			'one_time' => true,
		)
	)
);
npcink_governance_core_fail_closed_assert( ! is_wp_error( $one_time_failed_consume_audit_request ), 'One-time sensitive read request is created for consume audit failure.' );
$one_time_failed_consume_audit_approved = $stack['service']->approve( (string) $one_time_failed_consume_audit_request['request_id'], array( 'one_time' => true ) );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $one_time_failed_consume_audit_approved ), 'One-time sensitive read request is approved for consume audit failure.' );
$wpdb->fail_insert_event_names[] = 'read_request.consumed';
$one_time_failed_consume_audit_grant = $stack['service']->preflight(
	(string) $one_time_failed_consume_audit_request['request_id'],
	array(
		'ability_id' => 'npcink-abilities-toolkit/read-error-log',
		'input'      => array( 'tail' => true, 'filter' => 'one-time-consume-audit-fails' ),
	)
);
npcink_governance_core_fail_closed_assert( is_wp_error( $one_time_failed_consume_audit_grant ), 'One-time sensitive read request fails closed when consume audit fails.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_read_request_consume_audit_failed' === $one_time_failed_consume_audit_grant->get_error_code(), 'One-time consume audit failure uses stable error code.' );
$one_time_after_consume_audit_failure = $stack['requests']->find( (string) $one_time_failed_consume_audit_request['request_id'] );
npcink_governance_core_fail_closed_assert( is_array( $one_time_after_consume_audit_failure ) && 'approved' === (string) ( $one_time_after_consume_audit_failure['status'] ?? '' ), 'One-time consume audit failure rolls the request back to approved.' );
$wpdb->fail_insert_event_names = array_values( array_diff( $wpdb->fail_insert_event_names, array( 'read_request.consumed' ) ) );

$one_time_failed_consume_request = $stack['service']->create(
	array_merge(
		$read_payload,
		array(
			'input'    => array( 'tail' => true, 'filter' => 'one-time-consume-fails' ),
			'one_time' => true,
		)
	)
);
npcink_governance_core_fail_closed_assert( ! is_wp_error( $one_time_failed_consume_request ), 'One-time sensitive read request is created for consume failure.' );
$one_time_failed_consume_approved = $stack['service']->approve( (string) $one_time_failed_consume_request['request_id'], array( 'one_time' => true ) );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $one_time_failed_consume_approved ), 'One-time sensitive read request is approved for consume failure.' );
$wpdb->fail_update_tables[] = $read_request_table;
$one_time_failed_consume_grant = $stack['service']->preflight(
	(string) $one_time_failed_consume_request['request_id'],
	array(
		'ability_id' => 'npcink-abilities-toolkit/read-error-log',
		'input'      => array( 'tail' => true, 'filter' => 'one-time-consume-fails' ),
	)
);
npcink_governance_core_fail_closed_assert( is_wp_error( $one_time_failed_consume_grant ), 'One-time sensitive read request fails closed when consume update fails.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_read_request_consume_failed' === $one_time_failed_consume_grant->get_error_code(), 'One-time consume failure uses stable error code.' );

$read_timeline = $stack['service']->audit_timeline( (string) $read_request['request_id'] );
$read_timeline_events = array_map(
	static function ( array $event ): string {
		return (string) ( $event['event_name'] ?? '' );
	},
	$read_timeline
);
npcink_governance_core_fail_closed_assert( in_array( 'read_request.created', $read_timeline_events, true ), 'Read request audit timeline records create.' );
npcink_governance_core_fail_closed_assert( in_array( 'read_request.approved', $read_timeline_events, true ), 'Read request audit timeline records approve.' );
npcink_governance_core_fail_closed_assert( in_array( 'read_request.preflighted', $read_timeline_events, true ), 'Read request audit timeline records preflight/grant.' );

