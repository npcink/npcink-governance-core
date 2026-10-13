<?php
/** Proposal persistence cases test module. @package NpcinkGovernanceCore */

$wpdb = npcink_governance_core_fail_closed_reset_db();
$wpdb->fail_insert_tables[] = $proposal_table;
$repository = new \Npcink\GovernanceCore\Governance\Proposal_Repository();
$result     = $repository->create( npcink_governance_core_fail_closed_payload() );
npcink_governance_core_fail_closed_assert( is_wp_error( $result ), 'Proposal insert failure returns WP_Error.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_proposal_insert_failed' === $result->get_error_code(), 'Proposal insert failure uses stable error code.' );
npcink_governance_core_fail_closed_assert( 0 === count( $wpdb->rows( $proposal_table ) ), 'Proposal insert failure stores no proposal row.' );

$wpdb = npcink_governance_core_fail_closed_reset_db();
$wpdb->fail_insert_tables[] = $audit_table;
$stack  = npcink_governance_core_fail_closed_proposal_stack();
$result = $stack['service']->create( npcink_governance_core_fail_closed_payload() );
npcink_governance_core_fail_closed_assert( is_wp_error( $result ), 'Proposal creation audit failure returns WP_Error.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_proposal_audit_failed' === $result->get_error_code(), 'Proposal creation audit failure uses stable error code.' );
npcink_governance_core_fail_closed_assert( 0 === count( $wpdb->rows( $proposal_table ) ), 'Unaudited proposal creation is deleted.' );

$wpdb = npcink_governance_core_fail_closed_reset_db();
$stack    = npcink_governance_core_fail_closed_proposal_stack();
$proposal = $stack['service']->create( npcink_governance_core_fail_closed_payload() );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $proposal ) && 'manual_required' === (string) ( $proposal['policy_decision'] ?? '' ), 'Control proposal records the default policy decision.' );
npcink_governance_core_fail_closed_assert( 'manual' === (string) ( $proposal['policy_profile'] ?? '' ), 'Control proposal records the default policy profile.' );
npcink_governance_core_fail_closed_assert( 'core-approval-policy-v1' === (string) ( $proposal['policy_version'] ?? '' ), 'Control proposal records the policy version.' );
npcink_governance_core_fail_closed_assert( in_array( 'default_manual_required', (array) ( $proposal['policy_reasons'] ?? array() ), true ), 'Control proposal records manual policy reason.' );

$wpdb        = npcink_governance_core_fail_closed_reset_db();
$repository  = new \Npcink\GovernanceCore\Governance\Proposal_Repository();
$blocks_input = npcink_governance_core_fail_closed_update_post_blocks_input();
$proposal    = $repository->create(
	array(
		'ability_id' => 'npcink-abilities-toolkit/update-post-blocks',
		'title'      => 'Update Gutenberg blocks',
		'summary'    => 'Preserve Gutenberg block structure.',
		'input'      => $blocks_input,
		'preview'    => array(),
		'caller'     => array( 'source' => 'fault_injection' ),
	)
);
npcink_governance_core_fail_closed_assert( ! is_wp_error( $proposal ), 'update-post-blocks proposal is created.' );
$stored_block = $proposal['input']['blocks'][0] ?? array();
npcink_governance_core_fail_closed_assert( isset( $stored_block['blockName'] ) && ! isset( $stored_block['blockname'] ), 'update-post-blocks preserves blockName key case.' );
npcink_governance_core_fail_closed_assert( isset( $stored_block['innerBlocks'] ) && ! isset( $stored_block['innerblocks'] ), 'update-post-blocks preserves innerBlocks key case.' );
npcink_governance_core_fail_closed_assert( isset( $stored_block['innerHTML'] ) && ! isset( $stored_block['innerhtml'] ), 'update-post-blocks preserves innerHTML key case.' );
npcink_governance_core_fail_closed_assert( isset( $stored_block['innerContent'] ) && ! isset( $stored_block['innercontent'] ), 'update-post-blocks preserves innerContent key case.' );
npcink_governance_core_fail_closed_assert( '1120px' === (string) ( $stored_block['attrs']['layout']['contentSize'] ?? '' ), 'update-post-blocks preserves attrs contentSize key case.' );
npcink_governance_core_fail_closed_assert( '18px' === (string) ( $stored_block['attrs']['style']['typography']['fontSize'] ?? '' ), 'update-post-blocks preserves attrs fontSize key case.' );
npcink_governance_core_fail_closed_assert( '0' === (string) ( $stored_block['attrs']['style']['typography']['letterSpacing'] ?? '' ), 'update-post-blocks preserves attrs letterSpacing key case.' );
npcink_governance_core_fail_closed_assert( 'none' === (string) ( $stored_block['attrs']['style']['typography']['textTransform'] ?? '' ), 'update-post-blocks preserves attrs textTransform key case.' );
npcink_governance_core_fail_closed_assert( false !== strpos( (string) ( $stored_block['innerHTML'] ?? '' ), '<div' ), 'update-post-blocks preserves safe block innerHTML tags.' );
npcink_governance_core_fail_closed_assert( false === strpos( (string) ( $stored_block['innerHTML'] ?? '' ), '<script' ), 'update-post-blocks strips unsafe block innerHTML tags.' );
npcink_governance_core_fail_closed_assert( false !== strpos( (string) ( $stored_block['innerContent'][0] ?? '' ), '<div' ), 'update-post-blocks preserves safe block innerContent tags.' );
npcink_governance_core_fail_closed_assert( false === strpos( (string) ( $stored_block['innerContent'][0] ?? '' ), '<script' ), 'update-post-blocks strips unsafe block innerContent tags.' );

$batch_proposal = $repository->create(
	array(
		'ability_id' => 'plan_to_proposal_batch',
		'title'      => 'Batch update Gutenberg blocks',
		'summary'    => 'Preserve nested Gutenberg block structure.',
		'input'      => array(
			'write_actions' => array(
				array(
					'action_id'         => 'update_blocks',
					'target_ability_id' => 'npcink-abilities-toolkit/update-post-blocks',
					'input'             => $blocks_input,
				),
			),
			'dry_run'       => true,
			'commit'        => false,
		),
		'preview'    => array(),
		'caller'     => array( 'source' => 'fault_injection' ),
	)
);
npcink_governance_core_fail_closed_assert( ! is_wp_error( $batch_proposal ), 'batch update-post-blocks proposal is created.' );
$batch_block = $batch_proposal['input']['write_actions'][0]['input']['blocks'][0] ?? array();
npcink_governance_core_fail_closed_assert( isset( $batch_block['blockName'] ) && ! isset( $batch_block['blockname'] ), 'batch update-post-blocks preserves blockName key case.' );
npcink_governance_core_fail_closed_assert( '1120px' === (string) ( $batch_block['attrs']['layout']['contentSize'] ?? '' ), 'batch update-post-blocks preserves nested attrs key case.' );

$wpdb = npcink_governance_core_fail_closed_reset_db();
$wpdb->fail_insert_event_names[] = 'proposal.policy_evaluated';
$stack  = npcink_governance_core_fail_closed_proposal_stack();
$result = $stack['service']->create( npcink_governance_core_fail_closed_payload() );
npcink_governance_core_fail_closed_assert( is_wp_error( $result ), 'Policy decision audit failure returns WP_Error.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_policy_decision_audit_failed' === $result->get_error_code(), 'Policy decision audit failure uses stable error code.' );
npcink_governance_core_fail_closed_assert( 0 === count( $wpdb->rows( $proposal_table ) ), 'Unaudited policy decision deletes the proposal row.' );
npcink_governance_core_fail_closed_assert( \Npcink\GovernanceCore\Governance\Approval_Policy_Evaluator::MODE_MANUAL === \Npcink\GovernanceCore\Governance\Approval_Policy_Evaluator::sanitize_policy_mode( 'dry_run_guarded' ), 'Removed dry-run guarded mode falls back to manual.' );
npcink_governance_core_fail_closed_assert( \Npcink\GovernanceCore\Governance\Approval_Policy_Evaluator::MODE_MANUAL === \Npcink\GovernanceCore\Governance\Approval_Policy_Evaluator::sanitize_policy_mode( 'local_guarded' ), 'Removed local guarded mode falls back to manual.' );

$wpdb = npcink_governance_core_fail_closed_reset_db();
update_option( \Npcink\GovernanceCore\Governance\Approval_Policy_Evaluator::OPTION_POLICY_MODE, \Npcink\GovernanceCore\Governance\Approval_Policy_Evaluator::MODE_SMART_GUARDED, false );
\Npcink\GovernanceCore\Security\Request_Context::set_app(
	array(
		'app_id'       => 'app_auto',
		'key_id'       => 'key_auto',
		'caller_type'  => 'trusted_adapter',
		'scope'        => 'proposals:create',
		'scopes'       => array( 'proposals:create', 'proposals:approve' ),
		'route_family' => 'proposals_create',
	)
);
$stack    = npcink_governance_core_fail_closed_proposal_stack();
$proposal = $stack['service']->create( npcink_governance_core_fail_closed_cleanup_batch_payload() );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $proposal ), 'Smart guarded cleanup proposal is created.' );
npcink_governance_core_fail_closed_assert( 'approved' === (string) ( $proposal['status'] ?? '' ), 'Smart guarded cleanup proposal is auto-approved.' );
npcink_governance_core_fail_closed_assert( 'auto_approved' === (string) ( $proposal['policy_decision'] ?? '' ), 'Smart guarded cleanup records auto-approved decision.' );
npcink_governance_core_fail_closed_assert( 'trusted_local' === (string) ( $proposal['policy_profile'] ?? '' ), 'Smart guarded cleanup records trusted_local profile.' );
npcink_governance_core_fail_closed_assert( in_array( 'smart_guarded_cleanup_auto_approved', (array) ( $proposal['policy_reasons'] ?? array() ), true ), 'Smart guarded cleanup records stable auto approval reason.' );
$auto_approval_events = array_filter(
	$wpdb->rows( $audit_table ),
	static function ( array $row ): bool {
		return 'proposal.auto_approved' === (string) ( $row['event_name'] ?? '' );
	}
);
npcink_governance_core_fail_closed_assert( 1 === count( $auto_approval_events ), 'Smart guarded cleanup writes proposal.auto_approved audit.' );

$wpdb = npcink_governance_core_fail_closed_reset_db();
update_option( \Npcink\GovernanceCore\Governance\Approval_Policy_Evaluator::OPTION_POLICY_MODE, \Npcink\GovernanceCore\Governance\Approval_Policy_Evaluator::MODE_SMART_GUARDED, false );
\Npcink\GovernanceCore\Security\Request_Context::set_app(
	array(
		'app_id'       => 'app_auto',
		'key_id'       => 'key_auto',
		'caller_type'  => 'trusted_adapter',
		'scope'        => 'proposals:create',
		'scopes'       => array( 'proposals:create', 'proposals:approve' ),
		'route_family' => 'proposals_create',
	)
);
$stack    = npcink_governance_core_fail_closed_proposal_stack();
$proposal = $stack['service']->create(
	array(
		'ability_id' => 'npcink-abilities-toolkit/create-draft',
		'title'      => 'Smart guarded draft proposal',
		'summary'    => 'Create one draft only.',
		'input'      => array(
			'post_type'       => 'post',
			'status'          => 'draft',
			'title'           => 'Smart guarded draft',
			'content'         => '<p>Draft content.</p>',
			'dry_run'         => true,
			'commit'          => false,
			'idempotency_key' => 'local-guarded-draft',
		),
		'preview'    => array( 'after_suggestion' => 'Draft content' ),
		'caller'     => array( 'source' => 'fault_injection' ),
	)
);
npcink_governance_core_fail_closed_assert( ! is_wp_error( $proposal ), 'Smart guarded create-draft proposal is created.' );
npcink_governance_core_fail_closed_assert( 'approved' === (string) ( $proposal['status'] ?? '' ), 'Smart guarded create-draft proposal is auto-approved.' );
npcink_governance_core_fail_closed_assert( 'auto_approved' === (string) ( $proposal['policy_decision'] ?? '' ), 'Smart guarded create-draft records auto-approved decision.' );
npcink_governance_core_fail_closed_assert( 'trusted_local' === (string) ( $proposal['policy_profile'] ?? '' ), 'Smart guarded create-draft records trusted_local profile.' );
npcink_governance_core_fail_closed_assert( in_array( 'smart_guarded_create_draft_auto_approved', (array) ( $proposal['policy_reasons'] ?? array() ), true ), 'Smart guarded create-draft records stable auto approval reason.' );

$wpdb = npcink_governance_core_fail_closed_reset_db();
update_option( \Npcink\GovernanceCore\Governance\Approval_Policy_Evaluator::OPTION_POLICY_MODE, \Npcink\GovernanceCore\Governance\Approval_Policy_Evaluator::MODE_SMART_GUARDED, false );
\Npcink\GovernanceCore\Security\Request_Context::set_app(
	array(
		'app_id'       => 'app_auto',
		'key_id'       => 'key_auto',
		'caller_type'  => 'trusted_adapter',
		'scope'        => 'proposals:create',
		'scopes'       => array( 'proposals:create', 'proposals:approve' ),
		'route_family' => 'proposals_create',
	)
);
$stack    = npcink_governance_core_fail_closed_proposal_stack();
$proposal = $stack['service']->create(
	array(
		'ability_id' => 'npcink-abilities-toolkit/update-media-details',
		'title'      => 'Smart guarded media ALT proposal',
		'summary'    => 'Apply one reviewed ALT suggestion.',
		'input'      => array(
			'attachment_id'                     => 321,
			'alt'                               => 'Small orange cat sitting beside a window',
			'expected_current_alt'              => '',
			'operator_visual_review_confirmed' => true,
			'dry_run'                           => true,
			'commit'                            => false,
			'idempotency_key'                   => 'guarded-media-alt',
		),
		'preview'    => array(
			'source'          => array( 'type' => 'plan_to_proposal', 'plan_ability_id' => 'npcink-abilities-toolkit/build-media-alt-apply-plan' ),
			'media_alt_apply' => array(
				'artifact_type'                     => 'media_alt_apply_plan_item',
				'contract_version'                 => 'media_alt_apply_plan.v1',
				'review_set_contract'              => 'media_alt_caption_review_set.v1',
				'source_item_id'                   => 'media-alt-caption:321',
				'attachment_id'                    => 321,
				'current_alt_status'               => 'missing',
				'expected_current_alt'             => '',
				'proposed_alt'                     => 'Small orange cat sitting beside a window',
				'operator_reviewed'                => true,
				'operator_visual_review_confirmed' => true,
			),
		),
		'caller'     => array( 'source' => 'fault_injection' ),
	)
);
npcink_governance_core_fail_closed_assert( ! is_wp_error( $proposal ), 'Smart guarded media ALT proposal is created.' );
npcink_governance_core_fail_closed_assert( 'approved' === (string) ( $proposal['status'] ?? '' ), 'Smart guarded media ALT proposal is auto-approved.' );
npcink_governance_core_fail_closed_assert( 'auto_approved' === (string) ( $proposal['policy_decision'] ?? '' ), 'Smart guarded media ALT records auto-approved decision.' );
npcink_governance_core_fail_closed_assert( in_array( 'guarded_media_alt_candidate', (array) ( $proposal['policy_reasons'] ?? array() ), true ), 'Smart guarded media ALT records candidate reason.' );
npcink_governance_core_fail_closed_assert( in_array( 'smart_guarded_media_alt_auto_approved', (array) ( $proposal['policy_reasons'] ?? array() ), true ), 'Smart guarded media ALT records stable auto approval reason.' );
$media_alt_created_events = array_values(
	array_filter(
		$wpdb->rows( $audit_table ),
		static function ( array $row ) use ( $proposal ): bool {
			return 'proposal.created' === (string) ( $row['event_name'] ?? '' ) && (string) ( $proposal['proposal_id'] ?? '' ) === (string) ( $row['proposal_id'] ?? '' );
		}
	)
);
$media_alt_created_metadata = json_decode( (string) ( $media_alt_created_events[0]['metadata_json'] ?? '{}' ), true );
npcink_governance_core_fail_closed_assert( '' === (string) ( $media_alt_created_metadata['media_alt_evidence']['expected_current_alt'] ?? 'missing' ), 'Media ALT creation audit preserves the reviewed empty old value.' );
npcink_governance_core_fail_closed_assert( 'Small orange cat sitting beside a window' === (string) ( $media_alt_created_metadata['media_alt_evidence']['proposed_alt'] ?? '' ), 'Media ALT creation audit preserves the reviewed final value.' );
npcink_governance_core_fail_closed_assert( 'guarded-media-alt' === (string) ( $media_alt_created_metadata['media_alt_evidence']['idempotency_key'] ?? '' ), 'Media ALT creation audit preserves the idempotency key.' );

$wpdb = npcink_governance_core_fail_closed_reset_db();
update_option( \Npcink\GovernanceCore\Governance\Approval_Policy_Evaluator::OPTION_POLICY_MODE, \Npcink\GovernanceCore\Governance\Approval_Policy_Evaluator::MODE_SMART_GUARDED, false );
\Npcink\GovernanceCore\Security\Request_Context::set_app(
	array(
		'app_id'       => 'app_auto',
		'key_id'       => 'key_auto',
		'caller_type'  => 'trusted_adapter',
		'scope'        => 'proposals:create',
		'scopes'       => array( 'proposals:create', 'proposals:approve' ),
		'route_family' => 'proposals_create',
	)
);
$stack    = npcink_governance_core_fail_closed_proposal_stack();
$proposal = $stack['service']->create(
	array(
		'ability_id' => 'npcink-abilities-toolkit/update-media-details',
		'title'      => 'Smart guarded media ALT plus caption proposal',
		'summary'    => 'Caption edits must stay in manual review.',
		'input'      => array(
			'attachment_id'                     => 321,
			'alt'                               => 'Small orange cat sitting beside a window',
			'caption'                           => 'Cat by the window',
			'expected_current_alt'              => '',
			'operator_visual_review_confirmed' => true,
			'dry_run'                           => true,
			'commit'                            => false,
			'idempotency_key'                   => 'guarded-media-alt-caption',
		),
		'preview'    => array(
			'source'          => array( 'type' => 'plan_to_proposal', 'plan_ability_id' => 'npcink-abilities-toolkit/build-media-alt-apply-plan' ),
			'media_alt_apply' => array(
				'artifact_type'                     => 'media_alt_apply_plan_item',
				'contract_version'                 => 'media_alt_apply_plan.v1',
				'review_set_contract'              => 'media_alt_caption_review_set.v1',
				'source_item_id'                   => 'media-alt-caption:321',
				'attachment_id'                    => 321,
				'current_alt_status'               => 'missing',
				'expected_current_alt'             => '',
				'proposed_alt'                     => 'Small orange cat sitting beside a window',
				'operator_reviewed'                => true,
				'operator_visual_review_confirmed' => true,
			),
		),
		'caller'     => array( 'source' => 'fault_injection' ),
	)
);
npcink_governance_core_fail_closed_assert( ! is_wp_error( $proposal ), 'Smart guarded media ALT plus caption proposal is created.' );
npcink_governance_core_fail_closed_assert( 'pending' === (string) ( $proposal['status'] ?? '' ), 'Smart guarded media ALT plus caption proposal stays pending.' );
npcink_governance_core_fail_closed_assert( in_array( 'guarded_media_alt_rejected_non_alt_field', (array) ( $proposal['policy_reasons'] ?? array() ), true ), 'Smart guarded media ALT rejects non-ALT media metadata fields.' );

$wpdb = npcink_governance_core_fail_closed_reset_db();
update_option( \Npcink\GovernanceCore\Governance\Approval_Policy_Evaluator::OPTION_POLICY_MODE, \Npcink\GovernanceCore\Governance\Approval_Policy_Evaluator::MODE_SMART_GUARDED, false );
\Npcink\GovernanceCore\Security\Request_Context::set_app(
	array(
		'app_id'       => 'app_auto',
		'key_id'       => 'key_auto',
		'caller_type'  => 'trusted_adapter',
		'scope'        => 'proposals:create',
		'scopes'       => array( 'proposals:create', 'proposals:approve' ),
		'route_family' => 'proposals_create',
	)
);
$stack = npcink_governance_core_fail_closed_proposal_stack();
$media_derivative_payload = array(
	'ability_id' => 'npcink-abilities-toolkit/adopt-cloud-media-derivative',
	'title'      => 'Smart guarded media derivative proposal',
	'summary'    => 'Adopt one reviewed derivative artifact.',
	'input'      => array(
		'attachment_id'                  => 123,
		'derivative_artifact'            => npcink_governance_core_fail_closed_media_derivative_artifact(
			array(
				'artifact_id'        => 'art_0000000000000000000000000000007b',
				'filesize_bytes'     => 12345,
				'sha256'             => hash( 'sha256', 'core-smart-media-derivative' ),
				'suggested_filename' => 'optimized-123.webp',
			)
		),
		'expected_derivative_mime_type'  => 'image/webp',
		'backup_suffix'                  => 'npcink-cloud-backup',
		'dry_run'                        => true,
		'commit'                         => false,
		'idempotency_key'                => 'guarded-media-derivative',
	),
	'preview'    => array(
		'artifact_type'     => 'media_optimization_plan',
		'attachment_id'     => 123,
		'requires_approval' => true,
		'dry_run'           => true,
		'commit_execution'  => false,
	),
	'caller'     => array( 'source' => 'fault_injection' ),
);
$proposal = $stack['service']->create( $media_derivative_payload );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $proposal ), 'Smart guarded media derivative proposal is created.' );
npcink_governance_core_fail_closed_assert( 'approved' === (string) ( $proposal['status'] ?? '' ), 'Smart guarded media derivative proposal is auto-approved.' );
npcink_governance_core_fail_closed_assert( 'auto_approved' === (string) ( $proposal['policy_decision'] ?? '' ), 'Smart guarded media derivative records auto-approved decision.' );
npcink_governance_core_fail_closed_assert( 'trusted_local' === (string) ( $proposal['policy_profile'] ?? '' ), 'Smart guarded media derivative records trusted_local profile.' );
npcink_governance_core_fail_closed_assert( in_array( 'guarded_media_derivative_candidate', (array) ( $proposal['policy_reasons'] ?? array() ), true ), 'Smart guarded media derivative records candidate reason.' );
npcink_governance_core_fail_closed_assert( in_array( 'guarded_media_derivative_exact_local_artifact_evidence', (array) ( $proposal['policy_reasons'] ?? array() ), true ), 'Smart guarded media derivative records exact local artifact evidence.' );
npcink_governance_core_fail_closed_assert( in_array( 'smart_guarded_media_derivative_auto_approved', (array) ( $proposal['policy_reasons'] ?? array() ), true ), 'Smart guarded media derivative records stable auto approval reason.' );

$decode_boundary_payload = $media_derivative_payload;
$decode_boundary_payload['input']['derivative_artifact'] = npcink_governance_core_fail_closed_media_derivative_artifact(
	array(
		'artifact_id'        => 'art_00000000000000000000000000000081',
		'width'              => 8192,
		'height'             => 2048,
		'expires_at'         => gmdate( 'c', time() + 600 ),
		'suggested_filename' => 'decode-boundary.webp',
	)
);
$decode_boundary_payload['input']['idempotency_key'] = 'guarded-media-derivative-decode-boundary';
$decode_boundary_proposal = $stack['service']->create( $decode_boundary_payload );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $decode_boundary_proposal ) && 'approved' === (string) ( $decode_boundary_proposal['status'] ?? '' ), 'Cloud decode boundary 8192 x 2048 remains eligible for smart guarded approval.' );

$invalid_media_derivative_artifacts = array(
	'noncanonical id' => npcink_governance_core_fail_closed_media_derivative_artifact(
		array(
			'artifact_id'        => 'cloud-derivative-123',
			'suggested_filename' => 'invalid-id.webp',
		)
	),
	'expired descriptor' => npcink_governance_core_fail_closed_media_derivative_artifact(
		array(
			'artifact_id'        => 'art_0000000000000000000000000000007c',
			'expires_at'         => gmdate( 'c', time() - 60 ),
			'suggested_filename' => 'expired.webp',
		)
	),
	'impossible calendar date' => npcink_governance_core_fail_closed_media_derivative_artifact(
		array(
			'artifact_id'        => 'art_00000000000000000000000000000082',
			'expires_at'         => '2027-02-31T12:00:00Z',
			'suggested_filename' => 'impossible-date.webp',
		)
	),
	'non UTC expiry' => npcink_governance_core_fail_closed_media_derivative_artifact(
		array(
			'artifact_id'        => 'art_00000000000000000000000000000083',
			'expires_at'         => '2099-01-01T12:00:00+08:00',
			'suggested_filename' => 'non-utc-expiry.webp',
		)
	),
	'axis above Cloud decode limit' => npcink_governance_core_fail_closed_media_derivative_artifact(
		array(
			'artifact_id'        => 'art_0000000000000000000000000000007f',
			'width'              => 8193,
			'height'             => 1,
			'suggested_filename' => 'axis-too-large.webp',
		)
	),
	'pixel area above Cloud decode limit' => npcink_governance_core_fail_closed_media_derivative_artifact(
		array(
			'artifact_id'        => 'art_00000000000000000000000000000080',
			'width'              => 8192,
			'height'             => 2049,
			'suggested_filename' => 'area-too-large.webp',
		)
	),
);
$incomplete_media_derivative_artifact = npcink_governance_core_fail_closed_media_derivative_artifact(
	array(
		'artifact_id'        => 'art_0000000000000000000000000000007d',
		'suggested_filename' => 'incomplete.webp',
	)
);
unset( $incomplete_media_derivative_artifact['filename_basis'] );
$invalid_media_derivative_artifacts['incomplete descriptor'] = $incomplete_media_derivative_artifact;
$aliased_media_derivative_artifact = npcink_governance_core_fail_closed_media_derivative_artifact(
	array( 'suggested_filename' => 'aliased-id.webp' )
);
$aliased_media_derivative_artifact['id'] = $aliased_media_derivative_artifact['artifact_id'];
unset( $aliased_media_derivative_artifact['artifact_id'] );
$invalid_media_derivative_artifacts['legacy id alias'] = $aliased_media_derivative_artifact;
$legacy_media_derivative_artifact = npcink_governance_core_fail_closed_media_derivative_artifact(
	array(
		'artifact_id'        => 'art_0000000000000000000000000000007e',
		'suggested_filename' => 'legacy-url.webp',
	)
);
$legacy_media_derivative_artifact['download_url'] = 'https://cloud.example.test/legacy-download';
$invalid_media_derivative_artifacts['legacy URL field'] = $legacy_media_derivative_artifact;

$case_index = 0;
foreach ( $invalid_media_derivative_artifacts as $case => $invalid_artifact ) {
	$case_payload = $media_derivative_payload;
	$case_payload['input']['derivative_artifact'] = $invalid_artifact;
	$case_payload['input']['idempotency_key'] = 'guarded-media-derivative-invalid-' . $case_index;
	$invalid_proposal = $stack['service']->create( $case_payload );
	npcink_governance_core_fail_closed_assert( ! is_wp_error( $invalid_proposal ), 'Malformed smart guarded media derivative proposal remains reviewable: ' . $case . '.' );
	npcink_governance_core_fail_closed_assert( 'pending' === (string) ( $invalid_proposal['status'] ?? '' ), 'Malformed smart guarded media derivative proposal is not auto-approved: ' . $case . '.' );
	npcink_governance_core_fail_closed_assert( in_array( 'guarded_media_derivative_rejected_artifact_contract', (array) ( $invalid_proposal['policy_reasons'] ?? array() ), true ), 'Malformed smart guarded media derivative proposal records contract rejection: ' . $case . '.' );
	++$case_index;
}

$mime_mismatch_payload = $media_derivative_payload;
$mime_mismatch_payload['input']['expected_derivative_mime_type'] = 'image/avif';
$mime_mismatch_payload['input']['idempotency_key'] = 'guarded-media-derivative-mime-mismatch';
$mime_mismatch_proposal = $stack['service']->create( $mime_mismatch_payload );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $mime_mismatch_proposal ), 'MIME-mismatched smart guarded media derivative proposal remains reviewable.' );
npcink_governance_core_fail_closed_assert( 'pending' === (string) ( $mime_mismatch_proposal['status'] ?? '' ), 'MIME-mismatched smart guarded media derivative proposal is not auto-approved.' );
npcink_governance_core_fail_closed_assert( in_array( 'guarded_media_derivative_rejected_artifact_mime_mismatch', (array) ( $mime_mismatch_proposal['policy_reasons'] ?? array() ), true ), 'MIME-mismatched smart guarded media derivative proposal records mismatch rejection.' );

$wpdb = npcink_governance_core_fail_closed_reset_db();
update_option( \Npcink\GovernanceCore\Governance\Approval_Policy_Evaluator::OPTION_POLICY_MODE, \Npcink\GovernanceCore\Governance\Approval_Policy_Evaluator::MODE_SMART_GUARDED, false );
\Npcink\GovernanceCore\Security\Request_Context::set_app(
	array(
		'app_id'       => 'app_auto',
		'key_id'       => 'key_auto',
		'caller_type'  => 'trusted_adapter',
		'scope'        => 'proposals:create',
		'scopes'       => array( 'proposals:create', 'proposals:approve' ),
		'route_family' => 'proposals_create',
	)
);
$stack    = npcink_governance_core_fail_closed_proposal_stack();
$proposal = $stack['service']->create(
	array(
		'ability_id' => 'npcink-abilities-toolkit/adopt-cloud-media-derivative',
		'title'      => 'Smart guarded media derivative batch-shaped proposal',
		'summary'    => 'Nested write actions must not auto approve.',
		'input'      => array(
			'attachment_id'       => 123,
			'derivative_artifact' => npcink_governance_core_fail_closed_media_derivative_artifact(
				array(
					'artifact_id'        => 'art_0000000000000000000000000000007b',
					'suggested_filename' => 'optimized-123.webp',
				)
			),
			'write_actions'       => array(
				array(
					'target_ability_id' => 'npcink-abilities-toolkit/adopt-cloud-media-derivative',
					'input'             => array( 'attachment_id' => 123 ),
				),
			),
			'dry_run'             => true,
			'commit'              => false,
			'idempotency_key'     => 'guarded-media-derivative-batch-shaped',
		),
		'preview'    => array( 'artifact_type' => 'media_optimization_plan' ),
		'caller'     => array( 'source' => 'fault_injection' ),
	)
);
npcink_governance_core_fail_closed_assert( ! is_wp_error( $proposal ), 'Smart guarded media derivative batch-shaped proposal is still created for review.' );
npcink_governance_core_fail_closed_assert( 'pending' === (string) ( $proposal['status'] ?? '' ), 'Smart guarded media derivative batch-shaped proposal remains pending.' );
npcink_governance_core_fail_closed_assert( 'manual_required' === (string) ( $proposal['policy_decision'] ?? '' ), 'Smart guarded media derivative batch-shaped proposal remains manual.' );
npcink_governance_core_fail_closed_assert( in_array( 'guarded_media_derivative_rejected_multi_action', (array) ( $proposal['policy_reasons'] ?? array() ), true ), 'Smart guarded media derivative batch-shaped proposal records multi-action rejection reason.' );

$wpdb = npcink_governance_core_fail_closed_reset_db();
update_option( \Npcink\GovernanceCore\Governance\Approval_Policy_Evaluator::OPTION_POLICY_MODE, \Npcink\GovernanceCore\Governance\Approval_Policy_Evaluator::MODE_SMART_GUARDED, false );
\Npcink\GovernanceCore\Security\Request_Context::set_app(
	array(
		'app_id'       => 'app_auto',
		'key_id'       => 'key_auto',
		'caller_type'  => 'trusted_adapter',
		'scope'        => 'proposals:create',
		'scopes'       => array( 'proposals:create', 'proposals:approve' ),
		'route_family' => 'proposals_create',
	)
);
$stack    = npcink_governance_core_fail_closed_proposal_stack();
$proposal = $stack['service']->create(
	array(
		'ability_id' => 'npcink-abilities-toolkit/create-draft',
		'title'      => 'Smart guarded publish proposal',
		'summary'    => 'Publish must not auto approve.',
		'input'      => array(
			'post_type'       => 'post',
			'status'          => 'publish',
			'title'           => 'Blocked publish',
			'content'         => '<p>Published content.</p>',
			'dry_run'         => true,
			'commit'          => false,
			'idempotency_key' => 'local-guarded-publish',
		),
		'preview'    => array( 'after_suggestion' => 'Published content' ),
		'caller'     => array( 'source' => 'fault_injection' ),
	)
);
npcink_governance_core_fail_closed_assert( ! is_wp_error( $proposal ), 'Smart guarded publish create-draft proposal is still created for review.' );
npcink_governance_core_fail_closed_assert( 'pending' === (string) ( $proposal['status'] ?? '' ), 'Smart guarded publish create-draft remains pending.' );
npcink_governance_core_fail_closed_assert( 'manual_required' === (string) ( $proposal['policy_decision'] ?? '' ), 'Smart guarded publish create-draft remains manual.' );
npcink_governance_core_fail_closed_assert( in_array( 'guarded_create_draft_rejected_status', (array) ( $proposal['policy_reasons'] ?? array() ), true ), 'Smart guarded publish create-draft records status rejection reason.' );

$wpdb = npcink_governance_core_fail_closed_reset_db();
update_option( \Npcink\GovernanceCore\Governance\Approval_Policy_Evaluator::OPTION_POLICY_MODE, \Npcink\GovernanceCore\Governance\Approval_Policy_Evaluator::MODE_DEV_ALLOW_ALL, false );
\Npcink\GovernanceCore\Security\Request_Context::set_app(
	array(
		'app_id'       => 'app_auto',
		'key_id'       => 'key_auto',
		'caller_type'  => 'trusted_adapter',
		'scope'        => 'proposals:create',
		'scopes'       => array( 'proposals:create', 'proposals:approve' ),
		'route_family' => 'proposals_create',
	)
);
$stack    = npcink_governance_core_fail_closed_proposal_stack();
$proposal = $stack['service']->create( npcink_governance_core_fail_closed_governance_payload( 'npcink-abilities-toolkit/approve-comment' ) );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $proposal ), 'Development allow-all disabled proposal is still created for review.' );
npcink_governance_core_fail_closed_assert( 'pending' === (string) ( $proposal['status'] ?? '' ), 'Development allow-all without constant remains pending.' );
npcink_governance_core_fail_closed_assert( 'manual_required' === (string) ( $proposal['policy_decision'] ?? '' ), 'Development allow-all without constant remains manual.' );
npcink_governance_core_fail_closed_assert( in_array( 'dev_allow_all_rejected_disabled', (array) ( $proposal['policy_reasons'] ?? array() ), true ), 'Development allow-all without constant records disabled reason.' );

if ( ! defined( 'NPCINK_GOVERNANCE_CORE_ENABLE_DEV_ALLOW_ALL' ) ) {
	define( 'NPCINK_GOVERNANCE_CORE_ENABLE_DEV_ALLOW_ALL', true );
}

$wpdb = npcink_governance_core_fail_closed_reset_db();
update_option( \Npcink\GovernanceCore\Governance\Approval_Policy_Evaluator::OPTION_POLICY_MODE, \Npcink\GovernanceCore\Governance\Approval_Policy_Evaluator::MODE_DEV_ALLOW_ALL, false );
\Npcink\GovernanceCore\Security\Request_Context::set_app(
	array(
		'app_id'       => 'app_auto',
		'key_id'       => 'key_auto',
		'caller_type'  => 'trusted_adapter',
		'scope'        => 'proposals:create',
		'scopes'       => array( 'proposals:create', 'proposals:approve' ),
		'route_family' => 'proposals_create',
	)
);
$stack    = npcink_governance_core_fail_closed_proposal_stack();
$proposal = $stack['service']->create( npcink_governance_core_fail_closed_governance_payload( 'npcink-abilities-toolkit/approve-comment' ) );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $proposal ), 'Development allow-all enabled proposal is created.' );
npcink_governance_core_fail_closed_assert( 'approved' === (string) ( $proposal['status'] ?? '' ), 'Development allow-all enabled proposal is auto-approved.' );
npcink_governance_core_fail_closed_assert( 'auto_approved' === (string) ( $proposal['policy_decision'] ?? '' ), 'Development allow-all records auto-approved decision.' );
npcink_governance_core_fail_closed_assert( in_array( 'dev_allow_all_auto_approved', (array) ( $proposal['policy_reasons'] ?? array() ), true ), 'Development allow-all records stable auto approval reason.' );
npcink_governance_core_fail_closed_assert( in_array( 'commit_preflight_still_required', (array) ( $proposal['policy_reasons'] ?? array() ), true ), 'Development allow-all records commit preflight requirement.' );
$auto_approval_events = array_filter(
	$wpdb->rows( $audit_table ),
	static function ( array $row ): bool {
		return 'proposal.auto_approved' === (string) ( $row['event_name'] ?? '' );
	}
);
npcink_governance_core_fail_closed_assert( 1 === count( $auto_approval_events ), 'Development allow-all writes proposal.auto_approved audit.' );

$wpdb = npcink_governance_core_fail_closed_reset_db();
update_option( \Npcink\GovernanceCore\Governance\Approval_Policy_Evaluator::OPTION_POLICY_MODE, \Npcink\GovernanceCore\Governance\Approval_Policy_Evaluator::MODE_SMART_GUARDED, false );
\Npcink\GovernanceCore\Security\Request_Context::set_app(
	array(
		'app_id'       => 'app_auto',
		'key_id'       => 'key_auto',
		'caller_type'  => 'trusted_adapter',
		'scope'        => 'proposals:create',
		'scopes'       => array( 'proposals:create', 'proposals:approve' ),
		'route_family' => 'proposals_create',
	)
);
$wpdb->fail_insert_event_names[] = 'proposal.auto_approved';
$stack  = npcink_governance_core_fail_closed_proposal_stack();
$result = $stack['service']->create( npcink_governance_core_fail_closed_cleanup_batch_payload() );
npcink_governance_core_fail_closed_assert( is_wp_error( $result ), 'Auto approval audit failure returns WP_Error.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_auto_approval_audit_failed' === $result->get_error_code(), 'Auto approval audit failure uses stable error code.' );
$proposal_rows = $wpdb->rows( $proposal_table );
npcink_governance_core_fail_closed_assert( 1 === count( $proposal_rows ), 'Auto approval audit failure keeps the audited proposal row.' );
npcink_governance_core_fail_closed_assert( 'pending' === (string) $proposal_rows[0]['status'], 'Auto approval audit failure leaves proposal pending, not approved.' );

$wpdb = npcink_governance_core_fail_closed_reset_db();
$stack    = npcink_governance_core_fail_closed_proposal_stack();
$proposal = $stack['service']->create( npcink_governance_core_fail_closed_payload() );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $proposal ), 'Control proposal is created before decision failure injection.' );
$wpdb->fail_insert_tables[] = $audit_table;
$result = $stack['service']->approve( (string) $proposal['proposal_id'], array( 'reason' => 'approve' ) );
npcink_governance_core_fail_closed_assert( is_wp_error( $result ), 'Approve audit failure returns WP_Error.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_proposal_decision_audit_failed' === $result->get_error_code(), 'Approve audit failure uses stable error code.' );
$rolled_back = $stack['proposals']->find( (string) $proposal['proposal_id'] );
npcink_governance_core_fail_closed_assert( is_array( $rolled_back ) && 'pending' === $rolled_back['status'], 'Approve audit failure rolls status back to pending.' );

$wpdb = npcink_governance_core_fail_closed_reset_db();
$stack    = npcink_governance_core_fail_closed_proposal_stack();
$proposal = $stack['service']->create( npcink_governance_core_fail_closed_payload() );
npcink_governance_core_fail_closed_assert( ! is_wp_error( $proposal ), 'Control proposal is created before reject failure injection.' );
$wpdb->fail_insert_tables[] = $audit_table;
$result = $stack['service']->reject( (string) $proposal['proposal_id'], array( 'reason' => 'reject' ) );
npcink_governance_core_fail_closed_assert( is_wp_error( $result ), 'Reject audit failure returns WP_Error.' );
npcink_governance_core_fail_closed_assert( 'npcink_governance_core_proposal_decision_audit_failed' === $result->get_error_code(), 'Reject audit failure uses stable error code.' );
$rolled_back = $stack['proposals']->find( (string) $proposal['proposal_id'] );
npcink_governance_core_fail_closed_assert( is_array( $rolled_back ) && 'pending' === $rolled_back['status'], 'Reject audit failure rolls status back to pending.' );

