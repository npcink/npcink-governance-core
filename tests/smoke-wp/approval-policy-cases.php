<?php
/** Approval policy cases test module. @package NpcinkGovernanceCore */

$cleanup_pattern = 'Core Plan Bridge Test Cleanup Candidate ' . $npcink_governance_core_smoke_run_id;
$cleanup_post_id = wp_insert_post(
	array(
		'post_title'   => $cleanup_pattern . ' A',
		'post_content' => 'This ' . $cleanup_pattern . ' A should be detected as test content for cleanup planning.',
		'post_status'  => 'draft',
		'post_type'    => 'post',
	),
	true
);
npcink_governance_core_smoke_assert( ! is_wp_error( $cleanup_post_id ) && (int) $cleanup_post_id > 0, 'plan bridge cleanup fixture post is created' );
npcink_governance_core_smoke_register_post_fixture( (int) $cleanup_post_id );
$cleanup_second_post_id = wp_insert_post(
	array(
		'post_title'   => $cleanup_pattern . ' B',
		'post_content' => 'This ' . $cleanup_pattern . ' B should be detected as test content for cleanup planning.',
		'post_status'  => 'draft',
		'post_type'    => 'post',
	),
	true
);
npcink_governance_core_smoke_assert( ! is_wp_error( $cleanup_second_post_id ) && (int) $cleanup_second_post_id > 0, 'second plan bridge cleanup fixture post is created' );
npcink_governance_core_smoke_register_post_fixture( (int) $cleanup_second_post_id );

$cleanup_plan_input = array(
	'patterns'    => array( $cleanup_pattern ),
	'max_actions' => 5,
);
$cleanup_plan       = npcink_governance_core_smoke_run_plan_ability( 'npcink-abilities-toolkit/build-nonproduction-content-cleanup-plan', $cleanup_plan_input );
$cleanup_plan_result = npcink_governance_core_smoke_create_proposals_from_plan( 'npcink-abilities-toolkit/build-nonproduction-content-cleanup-plan', $cleanup_plan, $cleanup_plan_input );
npcink_governance_core_smoke_assert( 1 === (int) ( $cleanup_plan_result['proposal_count'] ?? 0 ), 'test content cleanup plan generates one batch Core proposal' );
$cleanup_plan_proposal = is_array( $cleanup_plan_result['proposals'][0] ?? null ) ? $cleanup_plan_result['proposals'][0] : array();
$cleanup_plan_input_payload = is_array( $cleanup_plan_proposal['input'] ?? null ) ? $cleanup_plan_proposal['input'] : array();
$cleanup_plan_actions       = is_array( $cleanup_plan_input_payload['write_actions'] ?? null ) ? array_values( $cleanup_plan_input_payload['write_actions'] ) : array();
npcink_governance_core_smoke_assert( 'plan_to_proposal_batch' === (string) ( $cleanup_plan_proposal['preview']['source']['type'] ?? '' ), 'test content cleanup plan records batch proposal source type' );
npcink_governance_core_smoke_assert( 'batch' === (string) ( $cleanup_plan_proposal['preview']['source']['proposal_mode'] ?? '' ), 'test content cleanup batch preserves proposal_mode' );
npcink_governance_core_smoke_assert( true === (bool) ( $cleanup_plan_proposal['preview']['source']['batch_approval'] ?? false ), 'test content cleanup batch preserves batch_approval' );
npcink_governance_core_smoke_assert( 2 === count( $cleanup_plan_actions ), 'test content cleanup batch stores both trash-post actions' );
npcink_governance_core_smoke_assert( 'npcink-abilities-toolkit/trash-post' === (string) ( $cleanup_plan_actions[0]['target_ability_id'] ?? '' ), 'test content cleanup batch stores trash-post action targets' );
npcink_governance_core_smoke_assert( 'manual_required' === (string) ( $cleanup_plan_proposal['policy_decision'] ?? '' ), 'manual policy keeps cleanup batch manual by default' );
npcink_governance_core_smoke_approve_and_preflight_plan_proposal( (string) ( $cleanup_plan_proposal['proposal_id'] ?? '' ) );

$smart_guarded_app = npcink_governance_core_smoke_rest(
	'POST',
	'/npcink-governance-core/v1/apps',
	array(
		'app_label'           => 'Smart Guarded Approval Smoke ' . $npcink_governance_core_smoke_run_id,
		'caller_type'         => 'trusted_adapter',
		'scopes'              => array( 'capabilities:read', 'proposals:create', 'proposals:read', 'proposals:approve', 'commit:preflight', 'audit:read' ),
		'rate_limit'          => 20,
		'rate_window_seconds' => 3600,
	)
);
$smart_guarded_token = (string) ( $smart_guarded_app['token'] ?? '' );
npcink_governance_core_smoke_assert( '' !== $smart_guarded_token, 'smart guarded app key is created for auto approval smoke' );

update_option( \Npcink\GovernanceCore\Governance\Approval_Policy_Evaluator::OPTION_POLICY_MODE, \Npcink\GovernanceCore\Governance\Approval_Policy_Evaluator::MODE_SMART_GUARDED, false );
$smart_guarded_pattern = 'Core Smart Guarded Test Cleanup Candidate ' . $npcink_governance_core_smoke_run_id;
$smart_guarded_post_id = wp_insert_post(
	array(
		'post_title'   => $smart_guarded_pattern . ' A',
		'post_content' => 'This ' . $smart_guarded_pattern . ' A should be detected as test content for smart guarded cleanup.',
		'post_status'  => 'draft',
		'post_type'    => 'post',
	),
	true
);
npcink_governance_core_smoke_assert( ! is_wp_error( $smart_guarded_post_id ) && (int) $smart_guarded_post_id > 0, 'smart guarded cleanup fixture post is created' );
npcink_governance_core_smoke_register_post_fixture( (int) $smart_guarded_post_id );
$smart_guarded_plan_input = array(
	'patterns'    => array( $smart_guarded_pattern ),
	'max_actions' => 5,
);
$smart_guarded_plan = npcink_governance_core_smoke_run_plan_ability( 'npcink-abilities-toolkit/build-nonproduction-content-cleanup-plan', $smart_guarded_plan_input );
$smart_guarded_result = npcink_governance_core_smoke_create_proposals_from_plan_as_app( 'npcink-abilities-toolkit/build-nonproduction-content-cleanup-plan', $smart_guarded_plan, $smart_guarded_plan_input, $smart_guarded_token );
npcink_governance_core_smoke_assert( 1 === (int) ( $smart_guarded_result['proposal_count'] ?? 0 ), 'smart guarded cleanup plan generates one batch Core proposal' );
$smart_guarded_proposal = is_array( $smart_guarded_result['proposals'][0] ?? null ) ? $smart_guarded_result['proposals'][0] : array();
$smart_guarded_proposal_id = (string) ( $smart_guarded_proposal['proposal_id'] ?? '' );
npcink_governance_core_smoke_assert( 'approved' === (string) ( $smart_guarded_proposal['status'] ?? '' ), 'smart guarded cleanup is auto-approved' );
npcink_governance_core_smoke_assert( 'auto_approved' === (string) ( $smart_guarded_proposal['policy_decision'] ?? '' ), 'smart guarded cleanup records auto-approved decision' );
npcink_governance_core_smoke_assert( 'trusted_local' === (string) ( $smart_guarded_proposal['policy_profile'] ?? '' ), 'smart guarded cleanup records trusted_local profile' );
npcink_governance_core_smoke_assert( in_array( 'smart_guarded_cleanup_auto_approved', (array) ( $smart_guarded_proposal['policy_reasons'] ?? array() ), true ), 'smart guarded cleanup records auto-approved reason' );
$smart_guarded_preflight = npcink_governance_core_smoke_rest_as_app( 'POST', '/npcink-governance-core/v1/proposals/' . rawurlencode( $smart_guarded_proposal_id ) . '/commit-preflight', $smart_guarded_token );
npcink_governance_core_smoke_assert( true === (bool) ( $smart_guarded_preflight['approval_context']['approval_commit_authorized'] ?? false ), 'smart guarded cleanup preflight passes without manual approval' );
$smart_guarded_audit = npcink_governance_core_smoke_rest_as_app(
	'GET',
	'/npcink-governance-core/v1/audit',
	$smart_guarded_token,
	array(
		'proposal_id' => $smart_guarded_proposal_id,
		'limit'       => 20,
	)
);
$smart_guarded_events = array_values( array_map(
	static function ( $item ): string {
		return is_array( $item ) ? (string) ( $item['event_name'] ?? '' ) : '';
	},
	(array) ( $smart_guarded_audit['items'] ?? array() )
) );
npcink_governance_core_smoke_assert( in_array( 'proposal.auto_approved', $smart_guarded_events, true ), 'smart guarded cleanup writes auto approval audit event' );

$smart_guarded_draft_title = 'Core Smart Guarded Draft ' . $npcink_governance_core_smoke_run_id;
$smart_guarded_draft = npcink_governance_core_smoke_rest_as_app(
	'POST',
	'/npcink-governance-core/v1/proposals',
	$smart_guarded_token,
	array(
		'ability_id' => 'npcink-abilities-toolkit/create-draft',
		'title'      => 'Smart guarded draft proposal ' . $npcink_governance_core_smoke_run_id,
		'summary'    => 'Created by smart guarded draft smoke test.',
		'input'      => array(
			'post_type'       => 'post',
			'status'          => 'draft',
			'title'           => $smart_guarded_draft_title,
			'content'         => '<p>Smart guarded draft content.</p>',
			'dry_run'         => true,
			'commit'          => false,
			'idempotency_key' => 'smart-guarded-draft-' . $npcink_governance_core_smoke_run_id,
		),
		'preview'    => array(
			'dry_run'          => true,
			'after_suggestion' => 'Smart guarded draft content.',
		),
		'caller'     => array(
			'source' => 'smart-guarded-draft-smoke:' . $npcink_governance_core_smoke_run_id,
		),
	)
);
$smart_guarded_draft_id = (string) ( $smart_guarded_draft['proposal_id'] ?? '' );
npcink_governance_core_smoke_assert( '' !== $smart_guarded_draft_id, 'smart guarded create-draft proposal is created' );
npcink_governance_core_smoke_assert( 'approved' === (string) ( $smart_guarded_draft['status'] ?? '' ), 'smart guarded create-draft is auto-approved' );
npcink_governance_core_smoke_assert( 'auto_approved' === (string) ( $smart_guarded_draft['policy_decision'] ?? '' ), 'smart guarded create-draft records auto-approved decision' );
npcink_governance_core_smoke_assert( 'trusted_local' === (string) ( $smart_guarded_draft['policy_profile'] ?? '' ), 'smart guarded create-draft records trusted_local profile' );
npcink_governance_core_smoke_assert( in_array( 'smart_guarded_create_draft_auto_approved', (array) ( $smart_guarded_draft['policy_reasons'] ?? array() ), true ), 'smart guarded create-draft records auto-approved reason' );
$smart_guarded_draft_preflight = npcink_governance_core_smoke_rest_as_app( 'POST', '/npcink-governance-core/v1/proposals/' . rawurlencode( $smart_guarded_draft_id ) . '/commit-preflight', $smart_guarded_token );
npcink_governance_core_smoke_assert( true === (bool) ( $smart_guarded_draft_preflight['approval_context']['approval_commit_authorized'] ?? false ), 'smart guarded create-draft preflight passes without manual approval' );
npcink_governance_core_smoke_assert( 0 === npcink_governance_core_smoke_find_post_id_by_title( $smart_guarded_draft_title, 'post' ), 'smart guarded create-draft preflight does not create the post draft' );

update_option( \Npcink\GovernanceCore\Governance\Approval_Policy_Evaluator::OPTION_POLICY_MODE, \Npcink\GovernanceCore\Governance\Approval_Policy_Evaluator::MODE_DEV_ALLOW_ALL, false );
$dev_allow_all_disabled = npcink_governance_core_smoke_rest_as_app(
	'POST',
	'/npcink-governance-core/v1/proposals',
	$smart_guarded_token,
	array(
		'ability_id' => 'npcink-abilities-toolkit/approve-comment',
		'title'      => 'Dev allow all disabled comment proposal ' . $npcink_governance_core_smoke_run_id,
		'summary'    => 'Created by disabled dev allow all smoke test.',
		'input'      => array(
			'comment_id'      => $pending_comment['comment_id'],
			'dry_run'         => true,
			'commit'          => false,
			'idempotency_key' => 'dev-allow-all-disabled-' . $npcink_governance_core_smoke_run_id,
		),
		'preview'    => array(
			'comment_id'       => $pending_comment['comment_id'],
			'target_action'    => 'approve',
			'dry_run'          => true,
			'commit_execution' => false,
		),
		'caller'     => array(
			'source' => 'dev-allow-all-disabled-smoke:' . $npcink_governance_core_smoke_run_id,
		),
	)
);
npcink_governance_core_smoke_assert( 'pending' === (string) ( $dev_allow_all_disabled['status'] ?? '' ), 'dev allow all remains pending when the constant is disabled' );
npcink_governance_core_smoke_assert( 'manual_required' === (string) ( $dev_allow_all_disabled['policy_decision'] ?? '' ), 'dev allow all disabled records manual decision' );
npcink_governance_core_smoke_assert( in_array( 'dev_allow_all_rejected_disabled', (array) ( $dev_allow_all_disabled['policy_reasons'] ?? array() ), true ), 'dev allow all disabled records disabled reason' );

if ( ! defined( 'NPCINK_GOVERNANCE_CORE_ENABLE_DEV_ALLOW_ALL' ) ) {
	define( 'NPCINK_GOVERNANCE_CORE_ENABLE_DEV_ALLOW_ALL', true );
}

$dev_allow_all_enabled = npcink_governance_core_smoke_rest_as_app(
	'POST',
	'/npcink-governance-core/v1/proposals',
	$smart_guarded_token,
	array(
		'ability_id' => 'npcink-abilities-toolkit/approve-comment',
		'title'      => 'Dev allow all enabled comment proposal ' . $npcink_governance_core_smoke_run_id,
		'summary'    => 'Created by enabled dev allow all smoke test.',
		'input'      => array(
			'comment_id'      => $pending_comment['comment_id'],
			'dry_run'         => true,
			'commit'          => false,
			'idempotency_key' => 'dev-allow-all-enabled-' . $npcink_governance_core_smoke_run_id,
		),
		'preview'    => array(
			'comment_id'       => $pending_comment['comment_id'],
			'target_action'    => 'approve',
			'dry_run'          => true,
			'commit_execution' => false,
		),
		'caller'     => array(
			'source' => 'dev-allow-all-enabled-smoke:' . $npcink_governance_core_smoke_run_id,
		),
	)
);
$dev_allow_all_enabled_id = (string) ( $dev_allow_all_enabled['proposal_id'] ?? '' );
npcink_governance_core_smoke_assert( 'approved' === (string) ( $dev_allow_all_enabled['status'] ?? '' ), 'dev allow all enabled auto-approves non-smart proposal' );
npcink_governance_core_smoke_assert( 'auto_approved' === (string) ( $dev_allow_all_enabled['policy_decision'] ?? '' ), 'dev allow all enabled records auto-approved decision' );
npcink_governance_core_smoke_assert( in_array( 'dev_allow_all_auto_approved', (array) ( $dev_allow_all_enabled['policy_reasons'] ?? array() ), true ), 'dev allow all enabled records auto approval reason' );
npcink_governance_core_smoke_assert( in_array( 'commit_preflight_still_required', (array) ( $dev_allow_all_enabled['policy_reasons'] ?? array() ), true ), 'dev allow all enabled records preflight requirement' );
$dev_allow_all_preflight = npcink_governance_core_smoke_rest_as_app( 'POST', '/npcink-governance-core/v1/proposals/' . rawurlencode( $dev_allow_all_enabled_id ) . '/commit-preflight', $smart_guarded_token );
npcink_governance_core_smoke_assert( true === (bool) ( $dev_allow_all_preflight['approval_context']['approval_commit_authorized'] ?? false ), 'dev allow all enabled still requires commit preflight' );
$comment_after_dev_allow_all = get_comment( $pending_comment['comment_id'] );
npcink_governance_core_smoke_assert( $comment_after_dev_allow_all instanceof WP_Comment && $pending_comment['current_status'] === (string) $comment_after_dev_allow_all->comment_approved, 'dev allow all preflight does not mutate comment status' );
update_option( \Npcink\GovernanceCore\Governance\Approval_Policy_Evaluator::OPTION_POLICY_MODE, \Npcink\GovernanceCore\Governance\Approval_Policy_Evaluator::MODE_MANUAL, false );

