<?php
/** Rest redaction cases test module. @package NpcinkGovernanceCore */

$proposal_table = 'wp_npcink_governance_core_proposals';
$read_request_table = 'wp_npcink_governance_core_read_requests';
$audit_table    = 'wp_npcink_governance_core_audit_log';
$app_table      = 'wp_npcink_governance_core_app_keys';

$wpdb  = npcink_governance_core_fail_closed_reset_db();
$stack = npcink_governance_core_fail_closed_proposals_controller_stack();
$controller = $stack['controller'];
$create_payload = npcink_governance_core_fail_closed_governance_payload( 'npcink-abilities-toolkit/create-draft' );
$create_payload['input']['content'] = 'RAW_GENERATED_CONTENT_SENTINEL';
$create_payload['input']['authorization_header'] = 'Bearer INPUT_AUTHORIZATION_SECRET_SENTINEL';
$create_payload['input']['nested_provider_context'] = array(
	'provider_api_key' => 'INPUT_PROVIDER_KEY_SECRET_SENTINEL',
);
$create_payload['preview']['policy_payload'] = 'POLICY_PAYLOAD_SENTINEL';
$create_payload['preview']['headers'] = array(
	'cookie' => 'wordpress_logged_in=PREVIEW_COOKIE_SECRET_SENTINEL',
);
$create_payload['preview']['provider'] = array(
	'client_secret' => 'PREVIEW_CLIENT_SECRET_SENTINEL',
);
$create_payload['caller']['secret_hint'] = 'CALLER_SECRET_SENTINEL';
$create_payload['caller']['app_token'] = 'npcink_governance_core.key.CALLER_APP_TOKEN_SECRET_SENTINEL';
$create_payload['caller']['private_key'] = 'CALLER_PRIVATE_KEY_SECRET_SENTINEL';
$create_payload['caller']['note'] = 'token: CALLER_INLINE_TOKEN_SECRET_SENTINEL';
$redaction_persistence_sentinels = array(
	// Static contract marker: Redaction persistence matrix.
	'INPUT_AUTHORIZATION_SECRET_SENTINEL',
	'INPUT_PROVIDER_KEY_SECRET_SENTINEL',
	'PREVIEW_COOKIE_SECRET_SENTINEL',
	'PREVIEW_CLIENT_SECRET_SENTINEL',
	'CALLER_APP_TOKEN_SECRET_SENTINEL',
	'CALLER_PRIVATE_KEY_SECRET_SENTINEL',
	'CALLER_INLINE_TOKEN_SECRET_SENTINEL',
);
npcink_governance_core_fail_closed_reset_observability_events();
$create_response = $controller->create_proposal( new WP_REST_Request( $create_payload ) );
npcink_governance_core_fail_closed_assert( $create_response instanceof WP_REST_Response && 201 === $create_response->get_status(), 'Proposal REST create succeeds for observability smoke.' );
$created_proposal = $create_response->get_data();
$proposal_id      = (string) ( is_array( $created_proposal ) ? ( $created_proposal['proposal_id'] ?? '' ) : '' );
npcink_governance_core_fail_closed_assert( 'core_proposal_required' === (string) ( $created_proposal['preview']['operation_classification']['classification'] ?? '' ), 'Direct proposal creation persists Core proposal classification evidence.' );
npcink_governance_core_fail_closed_assert( 'operation-classification-v1' === (string) ( $created_proposal['preview']['operation_classification']['decision_envelope']['decision_version'] ?? '' ), 'Direct proposal classification evidence preserves the policy version.' );
npcink_governance_core_fail_closed_assert( 'core_proposal' === (string) ( $created_proposal['preview']['operation_classification']['intake_path'] ?? '' ), 'Direct proposal classification evidence records the Core proposal intake path.' );
npcink_governance_core_fail_closed_assert( false === strpos( (string) wp_json_encode( $created_proposal ), 'CALLER_SECRET_SENTINEL' ), 'Proposal response redacts secret-shaped caller metadata.' );
$created_proposal_json = (string) wp_json_encode( $created_proposal );
$proposal_rows_json    = (string) wp_json_encode( $wpdb->rows( $proposal_table ) );
foreach ( $redaction_persistence_sentinels as $sentinel ) {
	npcink_governance_core_fail_closed_assert( false === strpos( $created_proposal_json, $sentinel ), 'Redaction persistence response removes ' . $sentinel . '.' );
	npcink_governance_core_fail_closed_assert( false === strpos( $proposal_rows_json, $sentinel ), 'Redaction persistence proposal row removes ' . $sentinel . '.' );
}
npcink_governance_core_fail_closed_assert( false !== strpos( $proposal_rows_json, '[redacted]' ), 'Redaction persistence proposal row stores redaction markers.' );
$create_events    = npcink_governance_core_fail_closed_observability_events( 'core.proposal.create' );
npcink_governance_core_fail_closed_assert( 1 === count( $create_events ), 'Proposal create emits one observability event.' );
npcink_governance_core_fail_closed_assert( 'ok' === (string) ( $create_events[0]['status'] ?? '' ), 'Proposal create emits ok status.' );
npcink_governance_core_fail_closed_assert( $proposal_id === (string) ( $create_events[0]['proposal_id'] ?? '' ), 'Proposal create event includes proposal id.' );
npcink_governance_core_fail_closed_assert( 'npcink-abilities-toolkit/create-draft' === (string) ( $create_events[0]['ability_id'] ?? '' ), 'Proposal create event includes ability id.' );
npcink_governance_core_fail_closed_assert_observability_metadata_only( $create_events[0], 'Proposal create observability event' );
npcink_governance_core_fail_closed_assert( false === strpos( (string) wp_json_encode( $wpdb->rows( $audit_table ) ), 'CALLER_SECRET_SENTINEL' ), 'Proposal audit rows redact secret-shaped caller metadata.' );

$redaction_audit = new \Npcink\GovernanceCore\Audit\Audit_Log_Repository();
$redaction_audit->record(
	'security.redaction_smoke',
	array(
		'authorization' => 'Bearer AUDIT_BEARER_SECRET_SENTINEL',
		'nested'        => array(
			'api_key'              => 'AUDIT_API_KEY_SECRET_SENTINEL',
			'provider_credentials' => 'AUDIT_PROVIDER_CREDENTIALS_SECRET_SENTINEL',
			'application_password' => 'AUDIT_APPLICATION_PASSWORD_SECRET_SENTINEL',
		),
		'note'          => 'cookie: AUDIT_COOKIE_SECRET_SENTINEL token: AUDIT_INLINE_TOKEN_SECRET_SENTINEL',
	)
);
$redaction_audit_rows = wp_json_encode( $wpdb->rows( $audit_table ) );
npcink_governance_core_fail_closed_assert( false === strpos( (string) $redaction_audit_rows, 'AUDIT_BEARER_SECRET_SENTINEL' ), 'Audit metadata redacts authorization values.' );
npcink_governance_core_fail_closed_assert( false === strpos( (string) $redaction_audit_rows, 'AUDIT_API_KEY_SECRET_SENTINEL' ), 'Audit metadata redacts secret-shaped nested keys.' );
npcink_governance_core_fail_closed_assert( false === strpos( (string) $redaction_audit_rows, 'AUDIT_COOKIE_SECRET_SENTINEL' ), 'Audit metadata redacts cookie-shaped strings.' );
foreach ( array( 'AUDIT_PROVIDER_CREDENTIALS_SECRET_SENTINEL', 'AUDIT_APPLICATION_PASSWORD_SECRET_SENTINEL', 'AUDIT_INLINE_TOKEN_SECRET_SENTINEL' ) as $sentinel ) {
	npcink_governance_core_fail_closed_assert( false === strpos( (string) $redaction_audit_rows, $sentinel ), 'Audit metadata redaction removes ' . $sentinel . '.' );
}
npcink_governance_core_fail_closed_assert( false !== strpos( (string) $redaction_audit_rows, '[redacted]' ), 'Audit metadata stores redaction markers.' );

$local_classified_proposal_payload = npcink_governance_core_fail_closed_governance_payload( 'npcink-abilities-toolkit/update-post' );
$local_classified_proposal_payload['preview']['operation_classification'] = npcink_governance_core_fail_closed_local_consent_classification();
$local_classified_proposal = $controller->create_proposal( new WP_REST_Request( $local_classified_proposal_payload ) );
npcink_governance_core_fail_closed_assert( is_wp_error( $local_classified_proposal ), 'Direct Core proposal rejects local-admin-consent classification evidence.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_proposal_classification_rejected' === $local_classified_proposal->get_error_code(), 'Direct Core proposal classification rejection uses a stable error code.' );

$plugin = new \Npcink\GovernanceCore\Plugin();
$missing_local_consent = $plugin->record_local_admin_consent_audit(
	null,
	'local_admin_consent.requested',
	array(
		'actor_user_id'      => 1,
		'source_module'      => 'toolbox',
		'target_object_id'   => 1493,
		'target_object_type' => 'post',
	)
);
npcink_governance_core_fail_closed_assert( is_wp_error( $missing_local_consent ), 'Local consent audit rejects missing classification evidence.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_local_consent_classification_missing' === $missing_local_consent->get_error_code(), 'Local consent missing classification rejection uses a stable error code.' );

$core_classified_local_consent = $plugin->record_local_admin_consent_audit(
	null,
	'local_admin_consent.requested',
	array(
		'actor_user_id'            => 1,
		'source_module'            => 'toolbox',
		'target_object_id'         => 1493,
		'target_object_type'       => 'post',
		'operation_classification' => npcink_governance_core_fail_closed_local_consent_classification( 'core_proposal_required' ),
	)
);
npcink_governance_core_fail_closed_assert( is_wp_error( $core_classified_local_consent ), 'Local consent audit rejects Core-proposal-required classification evidence.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_local_consent_classification_rejected' === $core_classified_local_consent->get_error_code(), 'Local consent wrong-classification rejection uses a stable error code.' );

$valid_local_consent = $plugin->record_local_admin_consent_audit(
	null,
	'local_admin_consent.completed',
	array(
		'actor_user_id'            => 1,
		'source_module'            => 'toolbox',
		'target_object_id'         => 1493,
		'target_object_type'       => 'post',
		'ai_suggestion_summary'    => 'Reviewed visible field value.',
		'operation_classification' => npcink_governance_core_fail_closed_local_consent_classification(),
	)
);
npcink_governance_core_fail_closed_assert( is_array( $valid_local_consent ) && '' !== (string) ( $valid_local_consent['event_id'] ?? '' ), 'Valid local consent classification evidence is audited.' );
$local_consent_rows = npcink_governance_core_fail_closed_audit_rows( '', 'local_admin_consent.completed' );
npcink_governance_core_fail_closed_assert( 1 === count( $local_consent_rows ), 'Valid local consent audit writes one audit row.' );
$local_consent_metadata = json_decode( (string) ( $local_consent_rows[0]['metadata_json'] ?? '' ), true );
npcink_governance_core_fail_closed_assert( is_array( $local_consent_metadata ) && false === (bool) ( $local_consent_metadata['proposal_created'] ?? true ), 'Valid local consent audit does not create a proposal.' );
npcink_governance_core_fail_closed_assert( 'local_admin_consent' === (string) ( $local_consent_metadata['operation_classification']['classification'] ?? '' ), 'Valid local consent audit persists classification evidence.' );
npcink_governance_core_fail_closed_assert( 'local_admin_consent_audit' === (string) ( $local_consent_metadata['operation_classification']['intake_path'] ?? '' ), 'Valid local consent audit records the local consent intake path.' );

npcink_governance_core_fail_closed_reset_observability_events();
$approve_response = $controller->approve_proposal(
	new WP_REST_Request(
		array(
			'proposal_id' => $proposal_id,
			'note'        => 'APPROVAL_NOTE_SENTINEL',
		)
	)
);
npcink_governance_core_fail_closed_assert( $approve_response instanceof WP_REST_Response && 200 === $approve_response->get_status(), 'Proposal REST approve succeeds for observability smoke.' );
$approve_events = npcink_governance_core_fail_closed_observability_events( 'core.proposal.approve' );
npcink_governance_core_fail_closed_assert( 1 === count( $approve_events ), 'Proposal approve emits one observability event.' );
npcink_governance_core_fail_closed_assert( 'ok' === (string) ( $approve_events[0]['status'] ?? '' ), 'Proposal approve emits ok status.' );
npcink_governance_core_fail_closed_assert( $proposal_id === (string) ( $approve_events[0]['proposal_id'] ?? '' ), 'Proposal approve event includes proposal id.' );
npcink_governance_core_fail_closed_assert_observability_metadata_only( $approve_events[0], 'Proposal approve observability event' );

npcink_governance_core_fail_closed_reset_observability_events();
$preflight_response = $controller->commit_preflight( new WP_REST_Request( array( 'proposal_id' => $proposal_id ) ) );
npcink_governance_core_fail_closed_assert( $preflight_response instanceof WP_REST_Response && 200 === $preflight_response->get_status(), 'Proposal REST preflight succeeds for observability smoke.' );
$preflight_data   = $preflight_response->get_data();
$preflight_events = npcink_governance_core_fail_closed_observability_events( 'core.commit.preflight' );
npcink_governance_core_fail_closed_assert( 1 === count( $preflight_events ), 'Successful preflight emits one observability event.' );
npcink_governance_core_fail_closed_assert( 'ok' === (string) ( $preflight_events[0]['status'] ?? '' ), 'Successful preflight emits ok status.' );
npcink_governance_core_fail_closed_assert( '' === (string) ( $preflight_events[0]['error_code'] ?? '' ), 'Successful preflight emits empty error code.' );
npcink_governance_core_fail_closed_assert( (string) ( is_array( $preflight_data ) ? ( $preflight_data['correlation_id'] ?? '' ) : '' ) === (string) ( $preflight_events[0]['correlation_id'] ?? '' ), 'Successful preflight event includes correlation id.' );
npcink_governance_core_fail_closed_assert_observability_metadata_only( $preflight_events[0], 'Successful preflight observability event' );

$reject_payload = npcink_governance_core_fail_closed_governance_payload( 'npcink-abilities-toolkit/set-post-terms' );
$reject_response = $controller->create_proposal( new WP_REST_Request( $reject_payload ) );
npcink_governance_core_fail_closed_assert( $reject_response instanceof WP_REST_Response && 201 === $reject_response->get_status(), 'Second proposal REST create succeeds for reject observability smoke.' );
$reject_proposal = $reject_response->get_data();
$reject_id       = (string) ( is_array( $reject_proposal ) ? ( $reject_proposal['proposal_id'] ?? '' ) : '' );
npcink_governance_core_fail_closed_reset_observability_events();
$reject_result = $controller->reject_proposal(
	new WP_REST_Request(
		array(
			'proposal_id' => $reject_id,
			'note'        => 'REJECTION_NOTE_SENTINEL',
		)
	)
);
npcink_governance_core_fail_closed_assert( $reject_result instanceof WP_REST_Response && 200 === $reject_result->get_status(), 'Proposal REST reject succeeds for observability smoke.' );
$reject_events = npcink_governance_core_fail_closed_observability_events( 'core.proposal.reject' );
npcink_governance_core_fail_closed_assert( 1 === count( $reject_events ), 'Proposal reject emits one observability event.' );
npcink_governance_core_fail_closed_assert( 'ok' === (string) ( $reject_events[0]['status'] ?? '' ), 'Proposal reject emits ok status.' );
npcink_governance_core_fail_closed_assert( $reject_id === (string) ( $reject_events[0]['proposal_id'] ?? '' ), 'Proposal reject event includes proposal id.' );
npcink_governance_core_fail_closed_assert_observability_metadata_only( $reject_events[0], 'Proposal reject observability event' );

$blocked_payload = npcink_governance_core_fail_closed_governance_payload( 'npcink-abilities-toolkit/trash-post' );
$blocked_payload['preview']['proposal_ready'] = false;
$blocked_payload['preview']['preflight_blockers'] = array(
	array(
		'code'   => 'destructive_review_missing',
		'reason' => 'Destructive review evidence is required.',
	),
);
$blocked_create = $controller->create_proposal( new WP_REST_Request( $blocked_payload ) );
npcink_governance_core_fail_closed_assert( $blocked_create instanceof WP_REST_Response && 201 === $blocked_create->get_status(), 'Blocked proposal REST create succeeds for observability smoke.' );
$blocked_proposal = $blocked_create->get_data();
$blocked_id       = (string) ( is_array( $blocked_proposal ) ? ( $blocked_proposal['proposal_id'] ?? '' ) : '' );
$blocked_approve  = $controller->approve_proposal( new WP_REST_Request( array( 'proposal_id' => $blocked_id ) ) );
npcink_governance_core_fail_closed_assert( $blocked_approve instanceof WP_REST_Response && 200 === $blocked_approve->get_status(), 'Blocked proposal REST approve succeeds before preflight block.' );
npcink_governance_core_fail_closed_reset_observability_events();
$blocked_preflight = $controller->commit_preflight( new WP_REST_Request( array( 'proposal_id' => $blocked_id ) ) );
npcink_governance_core_fail_closed_assert( is_wp_error( $blocked_preflight ), 'Blocked proposal REST preflight returns WP_Error.' );
$blocked_events = npcink_governance_core_fail_closed_observability_events( 'core.commit.preflight' );
npcink_governance_core_fail_closed_assert( 1 === count( $blocked_events ), 'Blocked preflight emits one observability event.' );
npcink_governance_core_fail_closed_assert( 'warning' === (string) ( $blocked_events[0]['status'] ?? '' ), 'Blocked preflight emits warning status.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_proposal_items_blocked' === (string) ( $blocked_events[0]['error_code'] ?? '' ), 'Blocked preflight event includes stable error code.' );
npcink_governance_core_fail_closed_assert_observability_metadata_only( $blocked_events[0], 'Blocked preflight observability event' );

$plan_error_request = new WP_REST_Request(
	array(
		'plan_ability_id' => 'npcink-abilities-toolkit/not-real-plan',
		'plan'            => array( 'success' => false ),
		'plan_input'      => array(),
		'caller'          => array( 'secret_hint' => 'CALLER_SECRET_SENTINEL' ),
	)
);
npcink_governance_core_fail_closed_reset_observability_events();
$plan_error = $controller->create_proposals_from_plan( $plan_error_request );
npcink_governance_core_fail_closed_assert( is_wp_error( $plan_error ), 'Invalid plan intake returns WP_Error for observability smoke.' );
$plan_events = npcink_governance_core_fail_closed_observability_events( 'core.proposal.plan_ingest' );
npcink_governance_core_fail_closed_assert( 1 === count( $plan_events ), 'Plan intake failure emits one observability event.' );
npcink_governance_core_fail_closed_assert( 'error' === (string) ( $plan_events[0]['status'] ?? '' ), 'Plan intake failure emits error status.' );
npcink_governance_core_fail_closed_assert( '' !== (string) ( $plan_events[0]['error_code'] ?? '' ), 'Plan intake failure emits stable error code.' );
npcink_governance_core_fail_closed_assert_observability_metadata_only( $plan_events[0], 'Plan intake observability event' );

