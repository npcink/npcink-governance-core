<?php
/** Cleanup assertions test module. @package NpcinkGovernanceCore */

npcink_governance_core_smoke_cleanup_fixtures();
npcink_governance_core_smoke_assert( null === get_comment( (int) $pending_comment['comment_id'] ), 'pending comment fixture is deleted after smoke' );
npcink_governance_core_smoke_assert( false === get_post_type( (int) $pending_comment['post_id'] ), 'comment parent post fixture is deleted after smoke' );
npcink_governance_core_smoke_assert( false === get_post_type( (int) $taxonomy_fixture['post_id'] ), 'taxonomy post fixture is deleted after smoke' );
npcink_governance_core_smoke_assert( 0 === (int) term_exists( (int) $taxonomy_fixture['current_term_id'], (string) $taxonomy_fixture['taxonomy'] ), 'taxonomy current term fixture is deleted after smoke' );
npcink_governance_core_smoke_assert( 0 === (int) term_exists( (int) $taxonomy_fixture['candidate_term_id'], (string) $taxonomy_fixture['taxonomy'] ), 'taxonomy candidate term fixture is deleted after smoke' );
npcink_governance_core_smoke_assert( false === get_post_type( (int) $plan_content_post_id ), 'plan bridge content post fixture is deleted after smoke' );
npcink_governance_core_smoke_assert( false === get_post_type( (int) $cleanup_post_id ), 'plan bridge cleanup post fixture is deleted after smoke' );
npcink_governance_core_smoke_assert( false === get_post_type( (int) $cleanup_second_post_id ), 'second plan bridge cleanup post fixture is deleted after smoke' );
npcink_governance_core_smoke_assert( false === get_post_type( (int) $plan_attachment_id ), 'plan bridge media fixture attachment is deleted after smoke' );
if ( ! npcink_governance_core_smoke_should_purge_governance_records() ) {
	$main_app_key_after = ( new \Npcink\GovernanceCore\Security\App_Key_Repository() )->find_by_key_id( $key_id );
	npcink_governance_core_smoke_assert( is_array( $main_app_key_after ) && 'revoked' === (string) ( $main_app_key_after['status'] ?? '' ), 'smoke app key fixture is revoked after smoke' );
}

// wp eval-file includes this file inside a method scope, so the fixture
// tracker (written through `global` in the register helpers) must be pulled
// in explicitly before the final assertions.
global $wpdb, $npcink_governance_core_smoke_proposal_fixture_ids, $npcink_governance_core_smoke_read_request_fixture_ids;
foreach ( array_keys( (array) $npcink_governance_core_smoke_proposal_fixture_ids ) as $smoke_proposal_fixture_id ) {
	$smoke_proposal_raw  = $wpdb->get_var(
		$wpdb->prepare( 'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'npcink_governance_core_proposals WHERE proposal_id = %s', (string) $smoke_proposal_fixture_id )
	);
	$smoke_proposal_left = (int) $smoke_proposal_raw;
	npcink_governance_core_smoke_assert( null !== $smoke_proposal_raw && 0 === $smoke_proposal_left, 'smoke proposal fixture is deleted after smoke: ' . (string) $smoke_proposal_fixture_id );
}

foreach ( array_keys( (array) $npcink_governance_core_smoke_read_request_fixture_ids ) as $smoke_read_request_fixture_id ) {
	$smoke_read_request_raw  = $wpdb->get_var(
		$wpdb->prepare( 'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'npcink_governance_core_read_requests WHERE request_id = %s', (string) $smoke_read_request_fixture_id )
	);
	$smoke_read_request_left = (int) $smoke_read_request_raw;
	npcink_governance_core_smoke_assert( null !== $smoke_read_request_raw && 0 === $smoke_read_request_left, 'smoke read request fixture is deleted after smoke: ' . (string) $smoke_read_request_fixture_id );
}

echo "npcink-governance-core WordPress smoke: ok\n";
