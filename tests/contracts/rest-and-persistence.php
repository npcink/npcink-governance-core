<?php
/** Rest and persistence test module. @package NpcinkGovernanceCore */

$capabilities_controller = npcink_governance_core_read( $root . '/includes/Rest/Capabilities_Controller.php' );
npcink_governance_core_assert( false !== strpos( $capabilities_controller, "'/capabilities'" ), 'Capabilities REST route is registered.' );
npcink_governance_core_assert( false !== strpos( $capabilities_controller, 'capabilities.listed' ), 'Capabilities route records audit event.' );

$contract_controller = npcink_governance_core_read( $root . '/includes/Rest/Contract_Controller.php' );
$proposals_controller = npcink_governance_core_read( $root . '/includes/Rest/Proposals_Controller.php' );
npcink_governance_core_assert( false !== strpos( $contract_controller, "'/contract'" ), 'Runtime contract REST route is registered.' );
npcink_governance_core_assert( false !== strpos( $contract_controller, 'can_read_contract' ), 'Runtime contract REST route accepts administrators or the contract:read app scope.' );

$audit_controller = npcink_governance_core_read( $root . '/includes/Rest/Audit_Controller.php' );
npcink_governance_core_assert( false !== strpos( $audit_controller, "'offset'" ) && false !== strpos( $audit_controller, "'search'" ), 'Audit REST route forwards offset and documented search pagination.' );
npcink_governance_core_assert( false !== strpos( $audit_controller, 'X-WP-Total' ), 'Audit REST list returns total counts.' );

$read_requests_controller = npcink_governance_core_read( $root . '/includes/Rest/Read_Requests_Controller.php' );
npcink_governance_core_assert( false !== strpos( $read_requests_controller, "'offset'" ), 'Read-requests REST route supports offset pagination.' );

npcink_governance_core_assert( false !== strpos( $proposals_controller, "array_merge( array( '' ), \$this->repository->allowed_statuses() )" ) && false !== strpos( $proposals_controller, "'enum'" ), 'Proposal list status filter reuses the canonical status allowlist so typos return 400 instead of an empty list.' );
npcink_governance_core_assert( false !== strpos( $read_requests_controller, "array_merge( array( '' ), \$this->repository->allowed_statuses() )" ) && false !== strpos( $read_requests_controller, "'enum'" ), 'Read-request list status filter reuses the canonical status allowlist.' );
npcink_governance_core_assert( false !== strpos( $read_request_service_source, "'request_status'" ), 'Read-request already-decided 409 echoes the stored request status.' );
npcink_governance_core_assert( false !== strpos( $read_requests_controller, "read_authorization_context']['expires_at'] = Rest_Format::iso8601" ), 'Read-preflight nested grant expiry is normalized to ISO8601.' );
npcink_governance_core_assert( false !== strpos( $proposal_service_source, 'earliest_pending_expires_at' ), 'Pending-quota 429 reports when the oldest pending proposal frees a slot.' );

npcink_governance_core_assert( false !== strpos( $proposals_controller, '0 === $created_count ) ? 200 : 201' ), 'From-plan avoids a false 201 Created when no proposal was created; blocked intake stays a 200 body contract.' );

$commit_preflight_service = npcink_governance_core_read( $root . '/includes/Governance/Commit_Preflight_Service.php' );
npcink_governance_core_assert( false !== strpos( $commit_preflight_service, "prior_handoff['correlation_id']" ), 'Repeated commit preflight echoes the original correlation id for lost-response recovery.' );

$app_authenticator = npcink_governance_core_read( $root . '/includes/Security/App_Authenticator.php' );
npcink_governance_core_assert( false !== strpos( $app_authenticator, 'npcink_governance_core_app_auth_expired' ), 'Expired app keys return a distinct rotate-key error code.' );
npcink_governance_core_assert( false !== strpos( $app_authenticator, 'Retry-After' ) && false !== strpos( $app_authenticator, 'retry_after_seconds' ), 'Rate-limited responses carry Retry-After guidance.' );
npcink_governance_core_assert( false !== strpos( $app_authenticator, 'X-RateLimit-Limit' ) && false !== strpos( $app_authenticator, 'X-RateLimit-Remaining' ) && false !== strpos( $app_authenticator, 'X-RateLimit-Reset' ), 'App-authenticated responses expose rate-limit quota headers.' );
npcink_governance_core_assert( false !== strpos( $app_authenticator, 'array( 400, 404 )' ) && false !== strpos( $app_authenticator, '->refund(' ), 'Client-validation failures (400/404) refund their consumed rate slot.' );
npcink_governance_core_assert( false !== strpos( $app_authenticator, 'rest_post_dispatch' ), 'Quota headers and refunds run after dispatch so the reported remaining value is accurate.' );
npcink_governance_core_assert( false !== strpos( $app_key_repository, "'contract:read'" ), 'App scope allowlist includes the contract:read compatibility scope.' );

$app_rate_limiter = npcink_governance_core_read( $root . '/includes/Security/App_Rate_Limiter.php' );
npcink_governance_core_assert( false !== strpos( $app_rate_limiter, 'request_count > 0' ) && false !== strpos( $app_rate_limiter, 'GREATEST(0, request_count - 1)' ), 'Rate refunds decrement atomically and are floored at zero.' );

$rest_format = npcink_governance_core_read( $root . '/includes/Rest/Rest_Format.php' );
npcink_governance_core_assert( false !== strpos( $rest_format, "gmdate( 'c'" ), 'REST timestamps are normalized to ISO8601 with a timezone designator.' );
npcink_governance_core_assert( false !== strpos( $contract_controller, 'npcink_governance_core_contract.v1' ), 'Runtime contract exposes the Core contract schema.' );
npcink_governance_core_assert( false !== strpos( $contract_controller, "'core_proxy_execute'      => false" ), 'Runtime contract keeps Core proxy execution disabled.' );
npcink_governance_core_assert( false !== strpos( $contract_controller, "'commit_execution'        => false" ), 'Runtime contract keeps commit execution disabled.' );
npcink_governance_core_assert( false !== strpos( $contract_controller, "'provider_secret_storage' => false" ), 'Runtime contract keeps provider secret storage outside Core.' );
npcink_governance_core_assert( false !== strpos( $contract_controller, "'operation_classification'" ), 'Runtime contract exposes operation classification discovery metadata.' );
npcink_governance_core_assert( false !== strpos( $contract_controller, 'Operation_Classifier::allowed_classifications()' ), 'Runtime contract reuses the shared operation classifier values.' );
npcink_governance_core_assert( false !== strpos( $contract_controller, 'CLASSIFICATION_CORE_PROPOSAL_REQUIRED' ) && false !== strpos( $contract_controller, "'proposal_intake_path'               => 'core_proposal'" ), 'Runtime contract declares Core proposal intake classification and path.' );
npcink_governance_core_assert( false !== strpos( $contract_controller, "'generic_editor_author_review_proposal_required' => false" ), 'Runtime contract keeps visible editor author review outside Core proposal intake.' );
npcink_governance_core_assert( false !== strpos( $contract_controller, "'implementation_posture'" ), 'Runtime contract exposes implementation posture discovery metadata.' );
npcink_governance_core_assert( false !== strpos( $contract_controller, "'commit_preflight_contract_validation' => true" ), 'Runtime contract declares implementation posture preflight validation.' );
npcink_governance_core_assert( false !== strpos( $contract_controller, "'core_records_truth'                  => false" ), 'Runtime contract keeps implementation posture truth outside Core.' );
npcink_governance_core_assert( false !== strpos( $contract_controller, "'forbidden_core_ownership_flags'" ), 'Runtime contract names forbidden Core ownership flags for provider posture.' );
npcink_governance_core_assert( false !== strpos( $contract_controller, 'minimum_adapter_contract_version' ), 'Runtime contract exposes Adapter compatibility floor.' );
npcink_governance_core_assert( false !== strpos( $contract_controller, 'context_bindings' ), 'Runtime contract exposes context binding metadata.' );
npcink_governance_core_assert( false !== strpos( $contract_controller, "'fields'       => array( 'site_url', 'home_url', 'blog_id' )" ), 'Runtime contract declares site binding fields.' );
npcink_governance_core_assert( false !== strpos( $contract_controller, 'supported_when_forwarded_by_trusted_adapter' ), 'Runtime contract declares supported Adapter-forwarded client fingerprint binding.' );
npcink_governance_core_assert( false !== strpos( $contract_controller, "'field'      => 'signed_client_fingerprint'" ), 'Runtime contract declares the signed client fingerprint field.' );
npcink_governance_core_assert( false !== strpos( $contract_controller, 'forbidden_payloads' ), 'Runtime contract declares forbidden payload families.' );
npcink_governance_core_assert( false !== strpos( $contract_controller, 'adapter_or_host_after_core_preflight' ), 'Runtime contract leaves final writes with the adapter or host after preflight.' );

$proposals_controller = npcink_governance_core_read( $root . '/includes/Rest/Proposals_Controller.php' );
npcink_governance_core_assert( false !== strpos( $proposals_controller, "'/proposals'" ), 'Proposals REST route is registered.' );
npcink_governance_core_assert( false !== strpos( $proposals_controller, 'Observability::emit' ), 'Proposal REST operations emit local observability events.' );
npcink_governance_core_assert( false !== strpos( $proposals_controller, 'core.proposal.create' ), 'Proposal create emits operation observability.' );
npcink_governance_core_assert( false !== strpos( $proposals_controller, 'core.proposal.plan_ingest' ), 'Plan intake emits operation observability.' );
npcink_governance_core_assert( false !== strpos( $proposals_controller, 'core.proposal.approve' ), 'Proposal approve emits operation observability.' );
npcink_governance_core_assert( false !== strpos( $proposals_controller, 'core.proposal.reject' ), 'Proposal reject emits operation observability.' );
npcink_governance_core_assert( false !== strpos( $proposals_controller, 'core.commit.preflight' ), 'Commit preflight emits operation observability.' );
npcink_governance_core_assert( false !== strpos( $proposals_controller, 'core.proposal.record_execution' ), 'Proposal execution record emits operation observability.' );
npcink_governance_core_assert( false !== strpos( $proposals_controller, 'npcink_governance_core_proposal_items_blocked' ), 'Commit preflight classifies blocked proposal observability.' );
npcink_governance_core_assert( false !== strpos( $proposals_controller, "'/proposals/from-plan'" ), 'Plan-to-proposal REST route is registered.' );
npcink_governance_core_assert( false !== strpos( $proposals_controller, 'create_proposals_from_plan' ), 'Plan-to-proposal REST callback is registered.' );
npcink_governance_core_assert( false !== strpos( $proposals_controller, 'get_proposal' ), 'Proposal detail REST callback is registered.' );
npcink_governance_core_assert( false !== strpos( $proposals_controller, "/approve'" ), 'Proposal approve REST route is registered.' );
npcink_governance_core_assert( false !== strpos( $proposals_controller, "/reject'" ), 'Proposal reject REST route is registered.' );
npcink_governance_core_assert( false !== strpos( $proposals_controller, "/commit-preflight'" ), 'Proposal commit preflight REST route is registered.' );
npcink_governance_core_assert( false !== strpos( $proposals_controller, "/record-execution'" ), 'Proposal execution record REST route is registered.' );
npcink_governance_core_assert( false !== strpos( $proposals_controller, "'ability_id'" ), 'Proposals route requires ability_id.' );
npcink_governance_core_assert( false !== strpos( $proposals_controller, 'audit_timeline' ), 'Proposal detail REST route returns audit timeline.' );
npcink_governance_core_assert( false !== strpos( $proposals_controller, 'expire_stale_pending' ), 'Proposal REST routes expire stale pending proposals before reads.' );

$read_requests_controller = npcink_governance_core_read( $root . '/includes/Rest/Read_Requests_Controller.php' );
npcink_governance_core_assert( false !== strpos( $read_requests_controller, "'/read-requests'" ), 'Read requests REST collection route is registered.' );
npcink_governance_core_assert( false !== strpos( $read_requests_controller, "/approve'" ), 'Read request approve REST route is registered.' );
npcink_governance_core_assert( false !== strpos( $read_requests_controller, "/reject'" ), 'Read request reject REST route is registered.' );
npcink_governance_core_assert( false !== strpos( $read_requests_controller, "/read-preflight'" ), 'Read request preflight REST route is registered.' );
npcink_governance_core_assert( false !== strpos( $read_requests_controller, 'read_preflight' ), 'Read request REST controller exposes read preflight callback.' );
npcink_governance_core_assert( false !== strpos( $read_requests_controller, 'request_contains_param' ) && false !== strpos( $read_requests_controller, "'_input_provided'" ), 'Read request REST controller distinguishes supplied input from route defaults.' );

$read_request_service = npcink_governance_core_read( $root . '/includes/Governance/Read_Request_Service.php' );
$read_request_repository = npcink_governance_core_read( $root . '/includes/Governance/Read_Request_Repository.php' );
foreach (
	array(
		'read_request.created',
		'read_request.approved',
		'read_request.rejected',
		'read_request.expired',
		'read_request.preflighted',
		'read_request.preflight_failed',
		'read_request.consumed',
		'read_authorization_context',
		'read_authorization_granted',
		'core_authorization_truth',
		'commit_execution',
		'write_execution',
		'approved_input_hash',
		'signed_client_context',
		'signed_client_fingerprint',
		'site_binding_context',
		"function_exists( 'site_url' ) ? site_url() : ''",
		"function_exists( 'home_url' ) ? home_url() : ''",
		"function_exists( 'get_current_blog_id' ) ? get_current_blog_id() : 0",
		'ability_mismatch',
		'input_mismatch',
		'requires_raw_input_preflight',
		'preflight_payload_includes_input',
		'npcink_governance_core_read_request_input_required_for_sensitive_preflight',
		'CORE_MAX_ROWS',
		'CORE_MAX_TAIL_LINES',
	) as $required
) {
	npcink_governance_core_assert( false !== strpos( $read_request_service, $required ), 'Read request service contains required text: ' . $required );
}
npcink_governance_core_assert( false !== strpos( $read_request_repository, 'npcink_governance_core_read_requests' ), 'Read request repository stores Core read request table.' );
npcink_governance_core_assert( false !== strpos( $read_request_repository, 'consumed_at' ), 'Read request repository stores one-time consumption timestamp.' );
npcink_governance_core_assert( false !== strpos( $read_request_repository, 'update_status_when' ), 'Read request repository supports conditional status transitions.' );
npcink_governance_core_assert( false === strpos( $read_request_repository, 'public function update_status(' ), 'Read request repository does not expose unconditional status transitions as public API.' );
npcink_governance_core_assert( false !== strpos( $read_request_repository, '\'status\'     => $expected_status' ), 'Read request repository binds current status in conditional updates.' );
$read_request_approval_fields_offset = strpos( $read_request_repository, 'public function update_approval_fields' );
$read_request_approval_fields_source = false === $read_request_approval_fields_offset ? '' : substr( $read_request_repository, $read_request_approval_fields_offset, 1200 );
npcink_governance_core_assert( false !== strpos( $read_request_approval_fields_source, 'if ( false === $updated )' ) && false !== strpos( $read_request_approval_fields_source, 'return $this->find( $request_id );' ), 'Read request approval field updates treat unchanged rows as successful persistence.' );
npcink_governance_core_assert( false !== strpos( $read_request_repository, 'Sensitive_Data_Redactor::sanitize_payload' ), 'Read request repository uses shared sensitive data redaction.' );
npcink_governance_core_assert( false !== strpos( $read_request_repository, 'redact_secret_string' ), 'Read request repository redacts secret-shaped strings.' );

$proposal_service = npcink_governance_core_read( $root . '/includes/Governance/Proposal_Service.php' );
$proposal_repository = npcink_governance_core_read( $root . '/includes/Governance/Proposal_Repository.php' );
$approval_policy_evaluator = npcink_governance_core_read( $root . '/includes/Governance/Approval_Policy_Evaluator.php' );
$wporg_guard = npcink_governance_core_read( $root . '/scripts/check-wordpress-org-review-rules.php' );
foreach (
	array(
		'Approval_Policy_Evaluator',
		'Approval_Policy_Strategy',
		'Manual_Approval_Policy_Strategy',
		'Smart_Guarded_Approval_Policy_Strategy',
		'Dev_Allow_All_Approval_Policy_Strategy',
		'manual_required',
		'auto_approved',
		'blocked',
		'manual',
		'guarded',
		'trusted_local',
		'break_glass',
		'core-approval-policy-v1',
		'OPTION_POLICY_MODE',
		'MODE_SMART_GUARDED',
		'MODE_DEV_ALLOW_ALL',
		'stored_policy_mode',
		'is_allowed_policy_mode',
		'CLEANUP_BATCH_MAX_ACTIONS',
		'CREATE_DRAFT_MAX_CONTENT_BYTES',
		'AUTO_APPROVAL_HOURLY_LIMIT',
		'AUTO_APPROVAL_DAILY_LIMIT',
		'smart_guarded_cleanup_auto_approved',
		'smart_guarded_create_draft_auto_approved',
		'smart_guarded_article_audio_auto_approved',
		'smart_guarded_media_derivative_auto_approved',
		'dev_allow_all_auto_approved',
		'dev_allow_all_rejected_disabled',
		'commit_preflight_still_required',
		'NPCINK_GOVERNANCE_CORE_ENABLE_DEV_ALLOW_ALL',
		'guarded_create_draft_rejected_status',
		'guarded_cleanup_rejected_missing_test_content_evidence',
		'build-nonproduction-content-cleanup-plan',
		'npcink-abilities-toolkit/trash-post',
		'npcink-abilities-toolkit/create-draft',
		'npcink-abilities-toolkit/adopt-article-audio',
		'npcink-abilities-toolkit/adopt-cloud-media-derivative',
		'npcink-abilities-toolkit/update-media-details',
		'media_alt_text_evaluation',
		'smart_guarded_media_alt_auto_approved',
		'guarded_media_alt_rejected_non_alt_field',
		'media_derivative_adoption_evaluation',
		'media_derivative_artifact_evidence_is_valid',
		'guarded_media_derivative_exact_local_artifact_evidence',
		'guarded_media_derivative_rejected_artifact_contract',
		'guarded_media_derivative_rejected_artifact_mime_mismatch',
		'guarded_media_derivative_preview_plan',
		'consume_auto_approval_quota',
		'AUTO_APPROVAL_TRANSIENT_PREFIX . $key_suffix',
		'flagged_media_removal_manual_evaluation',
		'flagged_media_removal_manual_required',
		'flagged_media_removal_destructive_never_auto_approved',
		'dev_allow_all_flagged_media_removal_manual_required',
	) as $required
) {
	npcink_governance_core_assert( false !== strpos( $approval_policy_evaluator, $required ), 'Approval policy evaluator contains required text: ' . $required );
}
npcink_governance_core_assert( false === strpos( $approval_policy_evaluator, 'MODE_DRY_RUN_GUARDED' ), 'Approval policy evaluator removes dry-run guarded mode.' );
npcink_governance_core_assert( false === strpos( $approval_policy_evaluator, 'MODE_LOCAL_GUARDED' ), 'Approval policy evaluator removes local guarded mode.' );
npcink_governance_core_assert( false === strpos( $approval_policy_evaluator, 'auto_approval_dry_run_only' ), 'Approval policy evaluator removes dry-run-only approval reason.' );
npcink_governance_core_assert( false === strpos( $approval_policy_evaluator, 'local_guarded_cleanup_auto_approved' ), 'Approval policy evaluator removes local guarded cleanup reason.' );
npcink_governance_core_assert( false === strpos( $approval_policy_evaluator, 'local_guarded_create_draft_auto_approved' ), 'Approval policy evaluator removes local guarded create-draft reason.' );
npcink_governance_core_assert( false === strpos( $approval_policy_evaluator, "artifact['id']" ), 'Approval policy evaluator rejects legacy derivative artifact id aliases.' );
npcink_governance_core_assert( false === strpos( $approval_policy_evaluator, 'download_url' ) && false === strpos( $approval_policy_evaluator, 'preview_url' ), 'Approval policy evaluator does not consume media delivery URLs.' );
npcink_governance_core_assert( false === strpos( $approval_policy_evaluator, 'set_transient( $prefixed_key' ), 'Approval policy evaluator does not pass variable-only transient keys.' );
npcink_governance_core_assert( false !== strpos( $wporg_guard, 'variable-only key' ), 'WordPress.org guard rejects variable-only transient keys.' );
npcink_governance_core_assert( false !== strpos( $wporg_guard, 'prefix visible at the call site' ), 'WordPress.org guard requires transient prefixes at the call site.' );
npcink_governance_core_assert( false !== strpos( $proposal_repository, 'STATUS_EXPIRED' ), 'Proposal repository defines expired status.' );
npcink_governance_core_assert( false !== strpos( $proposal_repository, 'STATUS_ARCHIVED' ), 'Proposal repository defines archived status.' );
npcink_governance_core_assert( false !== strpos( $proposal_repository, 'STATUS_EXECUTED' ), 'Proposal repository defines executed status.' );
npcink_governance_core_assert( false !== strpos( $proposal_repository, 'STATUS_EXECUTION_FAILED' ), 'Proposal repository defines execution failed status.' );
npcink_governance_core_assert( false !== strpos( $proposal_repository, 'list_stale_pending' ), 'Proposal repository can list stale pending proposals.' );
npcink_governance_core_assert( false !== strpos( $proposal_repository, 'list_recent_summaries' ), 'Proposal repository supports payload-light proposal lists.' );
npcink_governance_core_assert( false !== strpos( $proposal_repository, 'payload_included' ), 'Proposal repository marks payload-light list rows.' );
npcink_governance_core_assert( false !== strpos( $proposal_repository, 'list_pending_for_guardrail' ), 'Proposal repository can list pending proposals for create guardrails.' );
npcink_governance_core_assert( false !== strpos( $proposal_repository, 'list_pending_guardrail_summaries' ), 'Proposal repository supports payload-light pending guardrail checks.' );
npcink_governance_core_assert( false !== strpos( $proposal_repository, 'SELECT proposal_id, ability_id, status, caller_json, created_by, created_at, updated_at' ), 'Pending guardrail summary query omits large input and preview payloads.' );
npcink_governance_core_assert( false !== strpos( $proposal_repository, 'pending_quota_key' ), 'Proposal repository stores indexed pending quota keys.' );
npcink_governance_core_assert( false === strpos( $proposal_repository, 'caller_json LIKE' ), 'Proposal repository does not scan caller JSON for guardrail quota lookup.' );
npcink_governance_core_assert( false !== strpos( $proposal_repository, 'count_by_status' ), 'Proposal repository can count status queues.' );
npcink_governance_core_assert( false !== strpos( $proposal_repository, 'OFFSET %d' ), 'Proposal repository supports paginated admin lists.' );
npcink_governance_core_assert( false !== strpos( $proposal_repository, 'npcink_governance_core_proposal_insert_failed' ), 'Proposal repository returns a stable insert failure error.' );
npcink_governance_core_assert( false !== strpos( $proposal_repository, 'delete_by_proposal_id' ), 'Proposal repository can remove unaudited created proposals.' );
npcink_governance_core_assert( false !== strpos( $proposal_repository, 'display_id_for_proposal_id' ), 'Proposal repository derives stable human-facing display ids.' );
npcink_governance_core_assert( false !== strpos( $proposal_repository, "'display_id'" ), 'Proposal rows expose a display id without replacing proposal_id.' );
npcink_governance_core_assert( false !== strpos( $proposal_repository, 'find_by_display_id' ), 'Proposal repository supports admin lookup by display id.' );
npcink_governance_core_assert( false !== strpos( $proposal_repository, 'ORDER BY id DESC LIMIT 2' ), 'Proposal display id lookup detects non-unique aliases instead of choosing an arbitrary row.' );
npcink_governance_core_assert( false !== strpos( $proposal_repository, 'policy_fields_from_caller' ), 'Proposal repository promotes stored policy fields into responses.' );
npcink_governance_core_assert( false !== strpos( $proposal_repository, 'policy_decision' ), 'Proposal repository returns policy_decision.' );
npcink_governance_core_assert( false !== strpos( $proposal_repository, 'policy_reasons' ), 'Proposal repository returns policy_reasons.' );
npcink_governance_core_assert( false !== strpos( $proposal_repository, 'sanitize_input_for_ability' ), 'Proposal repository sanitizes proposal input with ability-aware context.' );
npcink_governance_core_assert( false !== strpos( $proposal_repository, 'is_create_draft_html_input' ), 'Proposal repository detects create-draft HTML content input narrowly.' );
npcink_governance_core_assert( false !== strpos( $proposal_repository, 'wp_kses_post( $content )' ), 'Proposal repository preserves create-draft HTML only through WordPress safe post KSES.' );
npcink_governance_core_assert( false !== strpos( $proposal_repository, "\$input['content_format'] ?? ''" ), 'Proposal repository requires content_format before preserving create-draft HTML.' );
npcink_governance_core_assert( false !== strpos( $proposal_repository, "\$clean['write_actions'][ \$index ]['input']['content']" ), 'Proposal repository preserves safe create-draft HTML inside batch write actions.' );
npcink_governance_core_assert( false !== strpos( $proposal_repository, 'is_update_post_blocks_input' ), 'Proposal repository detects update-post-blocks input narrowly.' );
npcink_governance_core_assert( false !== strpos( $proposal_repository, 'sanitize_update_post_blocks_input' ), 'Proposal repository preserves update-post-blocks input through ability-aware sanitization.' );
npcink_governance_core_assert( false !== strpos( $proposal_repository, 'sanitize_block_payload_key' ), 'Proposal repository preserves Gutenberg block object key case safely.' );
npcink_governance_core_assert( false !== strpos( $proposal_repository, '/[^A-Za-z0-9_-]/' ), 'Proposal repository block key sanitizer does not lowercase camelCase Gutenberg keys.' );
npcink_governance_core_assert( false !== strpos( $proposal_repository, "\$clean['write_actions'][ \$index ]['input'] = \$this->sanitize_update_post_blocks_input" ), 'Proposal repository preserves update-post-blocks keys inside batch write actions.' );
npcink_governance_core_assert( false !== strpos( $fail_closed_test, 'update-post-blocks preserves blockName key case' ), 'Fail-closed tests assert update-post-blocks blockName is not lowercased.' );
npcink_governance_core_assert( false !== strpos( $fail_closed_test, 'update-post-blocks preserves attrs contentSize key case' ), 'Fail-closed tests assert update-post-blocks attrs camelCase is not lowercased.' );
npcink_governance_core_assert( false !== strpos( $fail_closed_test, 'batch update-post-blocks preserves blockName key case' ), 'Fail-closed tests assert batch update-post-blocks blockName is not lowercased.' );
npcink_governance_core_assert( false !== strpos( $fail_closed_test, 'Direct proposal creation persists Core proposal classification evidence' ), 'Fail-closed tests assert direct proposal classification evidence persistence.' );
npcink_governance_core_assert( false !== strpos( $fail_closed_test, 'Local consent audit rejects missing classification evidence' ), 'Fail-closed tests assert local consent audit classification fail-closed behavior.' );
$smoke_test = npcink_governance_core_test_sources( $root, 'smoke-wp' );
npcink_governance_core_assert( false !== strpos( $smoke_test, 'proposal stores Core proposal classification evidence' ), 'WordPress smoke asserts direct proposal classification evidence.' );
npcink_governance_core_assert( false !== strpos( $smoke_test, 'plan proposal stores Core proposal classification evidence' ), 'WordPress smoke asserts plan proposal classification evidence.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'proposal.created' ), 'Proposal service records proposal.created audit event.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'MAX_PROPOSAL_PAYLOAD_BYTES' ), 'Proposal service bounds direct proposal payload size.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'validate_proposal_payload_size' ), 'Proposal service validates direct proposal payload size before persistence.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'npcink_governance_core_proposal_payload_too_large' ), 'Proposal service uses a stable oversized direct proposal payload error.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'proposal_intake_classification' ) && false !== strpos( $proposal_service, "'operation_classification'" ), 'Proposal service persists operation classification evidence on proposal intake.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, "'implementation_posture'" ), 'Proposal service stores implementation posture in the proposal ability contract fingerprint.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'npcink_governance_core_proposal_classification_rejected' ), 'Proposal service rejects non-Core proposal classification evidence.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'proposal.policy_evaluated' ), 'Proposal service records policy evaluation audit event.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'proposal.auto_approved' ), 'Proposal service records auto approval audit event.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'npcink_governance_core_auto_approval_audit_failed' ), 'Proposal service fails closed when auto approval audit fails.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'npcink_governance_core_auto_approval_quota_failed' ), 'Proposal service fails closed when auto approval quota cannot be consumed.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'proposal.deduplicated' ), 'Proposal service records proposal.deduplicated audit event.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'proposal.quota_blocked' ), 'Proposal service records proposal.quota_blocked audit event.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'Ability_Registry_Adapter' ), 'Proposal service validates target abilities against ability intake.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'npcink_governance_core_ability_not_available' ), 'Proposal service rejects unavailable target abilities.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'proposal.approved' ), 'Proposal service records proposal.approved audit event.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'proposal.rejected' ), 'Proposal service records proposal.rejected audit event.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'proposal.expired' ), 'Proposal service records proposal.expired audit event.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'proposal.archived' ), 'Proposal service records proposal.archived audit event.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'proposal.reopened' ), 'Proposal service records proposal.reopened audit event.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'record_execution_result' ), 'Proposal service records post-preflight execution results.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'proposal.executed' ), 'Proposal service records proposal.executed audit event.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'proposal.execution_failed' ), 'Proposal service records proposal.execution_failed audit event.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'npcink_governance_core_execution_record_already_recorded' ), 'Proposal service rejects duplicate terminal execution records.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'npcink_governance_core_execution_record_preflight_missing' ), 'Proposal service requires execution records to bind to preflight.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'npcink_governance_core_execution_record_preflight_expired' ), 'Proposal service rejects expired preflight handoffs.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'is_preflight_handoff_expired' ), 'Proposal service checks preflight handoff expiry before recording execution.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'npcink_governance_core_execution_record_audit_failed' ), 'Proposal service fails closed when execution record audit fails.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'PENDING_TTL_SECONDS' ), 'Proposal service defines a pending review TTL.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'PENDING_QUOTA_PER_APP' ), 'Proposal service defines an app pending proposal quota.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'PENDING_QUOTA_PER_USER' ), 'Proposal service defines a user pending proposal quota.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'npcink_governance_core_pending_proposal_quota_exceeded' ), 'Proposal service blocks callers with too many pending proposals.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'core_guardrails' ), 'Proposal service stores non-secret proposal creation guardrail metadata.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'core_policy' ), 'Proposal service stores non-secret policy decision metadata.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'npcink_governance_core_policy_decision_audit_failed' ), 'Proposal service fails closed when policy decision audit fails.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'deduplicated' ), 'Proposal service returns existing pending duplicates.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, '$full_duplicate = $this->proposals->find' ), 'Proposal service reloads full proposal payloads before returning pending duplicates.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'stable_input_hash' ), 'Proposal service hashes proposal input after ability-aware persistence sanitization.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, '$this->proposals->sanitize_input_for_ability' ), 'Proposal service input hashes match repository persistence sanitization.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'proposal.listed' ), 'Proposal service records proposal.listed audit event.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'proposal.viewed' ), 'Proposal service records proposal.viewed audit event.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'audit_timeline' ), 'Proposal service exposes proposal audit timeline.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, "'pending'" ), 'Proposal service only transitions pending proposals.' );
npcink_governance_core_assert( false !== strpos( $proposal_repository, 'update_status_when' ), 'Proposal repository supports conditional status transitions.' );
npcink_governance_core_assert( false === strpos( $proposal_repository, 'public function update_status(' ), 'Proposal repository does not expose unconditional status transitions as public API.' );
npcink_governance_core_assert( false !== strpos( $proposal_repository, 'reopen_when' ), 'Proposal repository supports conditional reopen transitions.' );
npcink_governance_core_assert( false !== strpos( $proposal_repository, 'Sensitive_Data_Redactor::sanitize_payload' ), 'Proposal repository uses shared sensitive data redaction.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'npcink_governance_core_ability_not_available' ), 'Proposal service rejects unavailable target abilities.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, '$this->abilities->find' ), 'Proposal service validates against ability intake.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'npcink_governance_core_proposal_audit_failed' ), 'Proposal service fails closed when creation audit fails.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'npcink_governance_core_proposal_decision_audit_failed' ), 'Proposal service fails closed when decision audit fails.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'media_alt_audit_metadata' ) && false !== strpos( $proposal_service, "'media_alt_evidence'") && false !== strpos( $proposal_service, "'expected_current_alt'") && false !== strpos( $proposal_service, "'proposed_alt'") && false !== strpos( $proposal_service, "'idempotency_key'"), 'Proposal lifecycle audit preserves bounded missing-ALT old value, final value, and idempotency evidence.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'audit_failed_error' ), 'Proposal service uses stable audit failure errors.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'update_status_when( $proposal_id, $status, (string) $existing' ), 'Proposal service rolls back decision status when audit fails.' );
npcink_governance_core_assert( false !== strpos( $proposal_service, 'update_status_when( $proposal_id, Proposal_Repository::STATUS_APPROVED, Proposal_Repository::STATUS_PENDING' ), 'Proposal service rolls back auto approval status when audit fails.' );
npcink_governance_core_assert( false === strpos( $proposal_service, 'confirm_token' ), 'Proposal service does not use confirm_token.' );
npcink_governance_core_assert( false === strpos( $proposal_service, 'write_confirmed' ), 'Proposal service does not use write_confirmed.' );

$commit_preflight_service = npcink_governance_core_read( $root . '/includes/Governance/Commit_Preflight_Service.php' );
npcink_governance_core_assert( false !== strpos( $commit_preflight_service, 'commit.preflighted' ), 'Commit preflight records commit.preflighted audit event.' );
npcink_governance_core_assert( false !== strpos( $commit_preflight_service, 'approval_commit_authorized' ), 'Commit preflight returns approval context.' );
npcink_governance_core_assert( false !== strpos( $commit_preflight_service, 'commit_execution' ), 'Commit preflight explicitly reports no commit execution.' );
npcink_governance_core_assert( false !== strpos( $commit_preflight_service, 'correlation_id' ), 'Commit preflight returns and audits correlation id.' );
npcink_governance_core_assert( false !== strpos( $commit_preflight_service, 'approved_input_hash' ), 'Commit preflight binds approval context to approved input hash.' );
npcink_governance_core_assert( false !== strpos( $commit_preflight_service, 'approved_preview_hash' ), 'Commit preflight binds approval context to approved preview hash.' );
npcink_governance_core_assert( false !== strpos( $commit_preflight_service, 'policy_version' ), 'Commit preflight returns a policy version for Adapter binding.' );
npcink_governance_core_assert( false !== strpos( $commit_preflight_service, 'PREFLIGHT_TTL_SECONDS' ), 'Commit preflight declares a bounded handoff TTL.' );
npcink_governance_core_assert( false !== strpos( $commit_preflight_service, 'ability_contract_fingerprint' ), 'Commit preflight fingerprints live ability contracts.' );
npcink_governance_core_assert( false !== strpos( $commit_preflight_service, "'requires_approval'" ), 'Commit preflight contract includes approval requirement drift.' );
npcink_governance_core_assert( false !== strpos( $commit_preflight_service, "'governance_mode'" ), 'Commit preflight contract includes governance mode drift.' );
npcink_governance_core_assert( false !== strpos( $commit_preflight_service, "'execution_surface'" ), 'Commit preflight contract includes execution surface drift.' );
npcink_governance_core_assert( false !== strpos( $commit_preflight_service, "'required_scopes'" ), 'Commit preflight contract includes required scope drift.' );
npcink_governance_core_assert( false !== strpos( $commit_preflight_service, "'implementation_posture'" ), 'Commit preflight contract includes implementation posture drift.' );
npcink_governance_core_assert( false !== strpos( $commit_preflight_service, "'input_schema'" ), 'Commit preflight contract includes input schema drift.' );
npcink_governance_core_assert( false !== strpos( $commit_preflight_service, 'signed_client_context' ), 'Commit preflight binds signed Adapter client fingerprint when present.' );
npcink_governance_core_assert( false !== strpos( $commit_preflight_service, 'site_binding_context' ), 'Commit preflight has a site binding helper.' );
npcink_governance_core_assert( false !== strpos( $commit_preflight_service, "function_exists( 'site_url' ) ? site_url() : ''" ), 'Commit preflight binds site_url.' );
npcink_governance_core_assert( false !== strpos( $commit_preflight_service, "function_exists( 'home_url' ) ? home_url() : ''" ), 'Commit preflight binds home_url.' );
npcink_governance_core_assert( false !== strpos( $commit_preflight_service, "function_exists( 'get_current_blog_id' ) ? get_current_blog_id() : 0" ), 'Commit preflight binds blog_id.' );
npcink_governance_core_assert( false !== strpos( $commit_preflight_service, 'payload_hash' ), 'Commit preflight has stable payload hash generation.' );
npcink_governance_core_assert( false !== strpos( $commit_preflight_service, 'record_with_event_id' ), 'Commit preflight uses deterministic audit event id for one successful handoff.' );
npcink_governance_core_assert( false !== strpos( $commit_preflight_service, 'preflight_failure_audit_failed' ), 'Commit preflight fails closed when denial audit cannot be stored.' );
npcink_governance_core_assert( false !== strpos( $commit_preflight_service, 'new_correlation_id' ), 'Commit preflight generates a correlation id.' );
npcink_governance_core_assert( false !== strpos( $commit_preflight_service, 'proposal_item_preflight' ), 'Commit preflight evaluates proposal item readiness.' );
npcink_governance_core_assert( false !== strpos( $commit_preflight_service, 'batch_review_summary' ), 'Commit preflight returns batch review summary when a proposal has one.' );
npcink_governance_core_assert( false !== strpos( $commit_preflight_service, 'batch_review_summary_preflight' ), 'Commit preflight bounds batch review summary response shape.' );
npcink_governance_core_assert( false !== strpos( $commit_preflight_service, 'media_alt_guard_preflight' ) && false !== strpos( $commit_preflight_service, 'requires_live_value_check' ) && false !== strpos( $commit_preflight_service, 'adapter_toolkit_dry_run_before_commit' ), 'Commit preflight preserves the media ALT evidence guard and delegates live-value truth to Adapter and Toolkit.' );
npcink_governance_core_assert( false !== strpos( $commit_preflight_service, 'npcink_governance_core_proposal_items_blocked' ), 'Commit preflight blocks incomplete proposal items.' );
npcink_governance_core_assert( false !== strpos( $commit_preflight_service, 'execution_handoff' ), 'Commit preflight returns adapter execution handoff.' );
npcink_governance_core_assert( false !== strpos( $commit_preflight_service, 'adapter_after_core_preflight' ), 'Commit preflight handoff points execution to Adapter.' );
npcink_governance_core_assert( false !== strpos( $commit_preflight_service, 'confirm_token' ), 'Commit preflight rejects confirm_token input.' );
npcink_governance_core_assert( false !== strpos( $commit_preflight_service, 'write_confirmed' ), 'Commit preflight rejects write_confirmed input.' );

