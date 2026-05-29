<?php
/**
 * Static contract tests for the MVP plugin.
 *
 * @package MagickAICore
 */

$root = dirname( __DIR__ );

/**
 * Assertion helper.
 *
 * @param bool   $condition Condition.
 * @param string $message Failure message.
 * @return void
 */
function magick_ai_core_assert( bool $condition, string $message ): void {
	if ( ! $condition ) {
		fwrite( STDERR, '[fail] ' . $message . "\n" );
		exit( 1 );
	}
}

/**
 * Reads a file.
 *
 * @param string $path Path.
 * @return string
 */
function magick_ai_core_read( string $path ): string {
	$contents = is_readable( $path ) ? file_get_contents( $path ) : false;
	return is_string( $contents ) ? $contents : '';
}

/**
 * Returns project text files used for drift checks.
 *
 * @param string $root Root.
 * @return array<int,string>
 */
function magick_ai_core_project_files( string $root ): array {
	$files = array();
	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator(
			$root,
			FilesystemIterator::SKIP_DOTS
		)
	);

	foreach ( $iterator as $file ) {
		$path = $file->getPathname();
		if ( false !== strpos( $path, '/.git/' ) ) {
			continue;
		}
		if ( preg_match( '/\.(php|md|json)$/', $path ) ) {
			$files[] = $path;
		}
	}

	sort( $files );

	return $files;
}

$main_plugin = magick_ai_core_read( $root . '/magick-ai-core.php' );
magick_ai_core_assert( false !== strpos( $main_plugin, 'Plugin Name: Magick AI Core' ), 'Main plugin file declares plugin header.' );
magick_ai_core_assert( false !== strpos( $main_plugin, 'register_activation_hook' ), 'Main plugin file registers activation hook.' );
magick_ai_core_assert( false !== strpos( $main_plugin, 'plugins_loaded' ), 'Main plugin file boots after plugins_loaded.' );

$readme = magick_ai_core_read( $root . '/README.md' );
foreach (
	array(
		'WordPress AI operation governance layer',
		'It does not generate content',
		'GET /wp-json/magick-ai-core/v1/capabilities',
		'POST /wp-json/magick-ai-core/v1/proposals',
		'POST /wp-json/magick-ai-core/v1/proposals/{proposal_id}/approve',
	) as $required
) {
	magick_ai_core_assert( false !== strpos( $readme, $required ), 'README contains required phrase: ' . $required );
}

$positioning = magick_ai_core_read( $root . '/docs/product-positioning.md' );
magick_ai_core_assert( false !== strpos( $positioning, 'Magick AI Core governs AI-assisted WordPress operations.' ), 'Positioning keeps one-sentence product truth.' );
magick_ai_core_assert( false !== strpos( $positioning, '`magick-ai-abilities`' ), 'Positioning names magick-ai-abilities as ability owner.' );
magick_ai_core_assert( false !== strpos( $positioning, '`magick-ai-content-assistant`' ), 'Positioning names Content Assistant as product UX owner.' );

$governance = magick_ai_core_read( $root . '/docs/governance-contract.md' );
magick_ai_core_assert( false !== strpos( $governance, 'proposal.created' ), 'Governance contract records proposal.created event.' );
magick_ai_core_assert( false !== strpos( $governance, 'proposal.approved' ), 'Governance contract records proposal.approved event.' );
magick_ai_core_assert( false !== strpos( $governance, 'proposal.rejected' ), 'Governance contract records proposal.rejected event.' );
magick_ai_core_assert( false !== strpos( $governance, 'must not reintroduce' ), 'Governance contract rejects legacy confirmation parameters.' );

$ability_adapter = magick_ai_core_read( $root . '/includes/Capabilities/Ability_Registry_Adapter.php' );
magick_ai_core_assert( false !== strpos( $ability_adapter, 'magick_ai_abilities_get_registered' ), 'Ability intake prefers magick-ai-abilities public API.' );
magick_ai_core_assert( false !== strpos( $ability_adapter, 'wp_get_abilities' ), 'Ability intake falls back to WordPress Abilities API.' );
magick_ai_core_assert( false !== strpos( $ability_adapter, "'none'" ), 'Ability intake has missing-provider diagnostic state.' );

$capabilities_controller = magick_ai_core_read( $root . '/includes/Rest/Capabilities_Controller.php' );
magick_ai_core_assert( false !== strpos( $capabilities_controller, "'/capabilities'" ), 'Capabilities REST route is registered.' );
magick_ai_core_assert( false !== strpos( $capabilities_controller, 'capabilities.listed' ), 'Capabilities route records audit event.' );

$proposals_controller = magick_ai_core_read( $root . '/includes/Rest/Proposals_Controller.php' );
magick_ai_core_assert( false !== strpos( $proposals_controller, "'/proposals'" ), 'Proposals REST route is registered.' );
magick_ai_core_assert( false !== strpos( $proposals_controller, "/approve'" ), 'Proposal approve REST route is registered.' );
magick_ai_core_assert( false !== strpos( $proposals_controller, "/reject'" ), 'Proposal reject REST route is registered.' );
magick_ai_core_assert( false !== strpos( $proposals_controller, "'ability_id'" ), 'Proposals route requires ability_id.' );

$proposal_service = magick_ai_core_read( $root . '/includes/Governance/Proposal_Service.php' );
magick_ai_core_assert( false !== strpos( $proposal_service, 'proposal.created' ), 'Proposal service records proposal.created audit event.' );
magick_ai_core_assert( false !== strpos( $proposal_service, 'proposal.approved' ), 'Proposal service records proposal.approved audit event.' );
magick_ai_core_assert( false !== strpos( $proposal_service, 'proposal.rejected' ), 'Proposal service records proposal.rejected audit event.' );
magick_ai_core_assert( false !== strpos( $proposal_service, "'pending'" ), 'Proposal service only transitions pending proposals.' );
magick_ai_core_assert( false === strpos( $proposal_service, 'confirm_token' ), 'Proposal service does not use confirm_token.' );
magick_ai_core_assert( false === strpos( $proposal_service, 'write_confirmed' ), 'Proposal service does not use write_confirmed.' );

$audit_repository = magick_ai_core_read( $root . '/includes/Audit/Audit_Log_Repository.php' );
magick_ai_core_assert( false !== strpos( $audit_repository, 'sanitize_text_field( $event_name )' ), 'Audit repository preserves dotted event names.' );

$forbidden_runtime_terms = array(
	'Agent Gateway',
	'MCP runtime',
	'Prompt Center',
	'batch_article_governance',
	'batch_media_optimize',
	'workflow/content_tag_completion',
	'workflow/comment-moderation',
);

foreach ( magick_ai_core_project_files( $root ) as $file ) {
	$relative = str_replace( $root . '/', '', $file );
	$contents = magick_ai_core_read( $file );

	foreach ( $forbidden_runtime_terms as $term ) {
		if ( ! preg_match( '/^(includes|magick-ai-core\.php)/', $relative ) ) {
			continue;
		}

		magick_ai_core_assert( false === strpos( $contents, $term ), $relative . ' must not reintroduce old Core runtime term: ' . $term );
	}
}

echo "Static contracts: ok\n";
