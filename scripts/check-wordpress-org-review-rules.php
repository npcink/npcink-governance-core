<?php
/**
 * Guards against WordPress.org review patterns that should block release.
 *
 * @package NpcinkGovernanceCore
 */

$root     = dirname( __DIR__ );
$failures = array();

function npcink_governance_core_wporg_fail( $message ) {
	global $failures;
	$failures[] = (string) $message;
}

function npcink_governance_core_wporg_read( $path ) {
	if ( ! is_readable( $path ) ) {
		npcink_governance_core_wporg_fail( 'Missing readable file: ' . $path );
		return '';
	}

	$contents = file_get_contents( $path );
	return is_string( $contents ) ? $contents : '';
}

function npcink_governance_core_wporg_php_files() {
	global $root;

	$files = glob( $root . '/*.php' );
	$files = is_array( $files ) ? $files : array();

	foreach ( array( 'includes' ) as $directory ) {
		$path = $root . '/' . $directory;
		if ( ! is_dir( $path ) ) {
			continue;
		}

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator(
				$path,
				FilesystemIterator::SKIP_DOTS
			)
		);
		foreach ( $iterator as $file ) {
			if ( 'php' === strtolower( $file->getExtension() ) ) {
				$files[] = $file->getPathname();
			}
		}
	}

	sort( $files );
	return $files;
}

$rules = array(
	'Do not build paths into wp-admin/includes except the dbDelta upgrade.php activation helper.' => '/wp-admin\/includes\/(?!upgrade\.php)/',
	'Do not ship inline admin styles through wp_add_inline_style(); use assets/*.css.' => '/wp_add_inline_style\s*\(/',
	'Do not ship inline admin scripts through wp_add_inline_script(); use assets/*.js or data attributes.' => '/wp_add_inline_script\s*\(/',
	'Do not output raw script tags from PHP admin views; enqueue scripts.' => '/<\s*\/?\s*script\b/i',
	'Do not output raw style tags from PHP admin views; enqueue styles.' => '/<\s*\/?\s*style\b/i',
	'Do not read $_GET directly in plugin views; route reads through nonce-verified helpers.' => '/\$_GET\s*\[/',
	'Do not ship legacy Magick AI token or id prefixes in Core release PHP.' => '/\bmai_core\b|uniqid\s*\(\s*[\'"]mai(?:_|_corr_)/',
	'Do not pass raw SELECT strings directly into $wpdb->get_var(); prepare the query.' => '/->get_var\s*\(\s*[\'"]\s*SELECT\b/i',
	'Do not assemble SQL WHERE clauses inline with implode(); use fixed whitelisted clauses and prepare().' => '/WHERE\s+[\'"]\s*\.\s*implode\s*\(/i',
);

foreach ( npcink_governance_core_wporg_php_files() as $file ) {
	$relative = str_replace( $root . '/', '', $file );
	$contents = npcink_governance_core_wporg_read( $file );

	foreach ( $rules as $message => $pattern ) {
		if ( preg_match( $pattern, $contents ) ) {
			npcink_governance_core_wporg_fail( $relative . ': ' . $message );
		}
	}

	if ( false !== strpos( $contents, 'register_rest_route(' ) ) {
		$route_count      = substr_count( $contents, 'register_rest_route(' );
		$permission_count = substr_count( $contents, "'permission_callback'" ) + substr_count( $contents, '"permission_callback"' );
		if ( $permission_count < $route_count ) {
			npcink_governance_core_wporg_fail( $relative . ': Every register_rest_route() call must declare permission_callback.' );
		}
	}

	if ( false !== strpos( $contents, 'set_transient(' ) && false === strpos( $contents, 'AUTO_APPROVAL_TRANSIENT_PREFIX' ) ) {
		npcink_governance_core_wporg_fail( $relative . ': Dynamic transient keys must have an auditable npcink_governance_core prefix guard.' );
	}
}

if ( ! empty( $failures ) ) {
	foreach ( $failures as $failure ) {
		fwrite( STDERR, '[fail] ' . $failure . PHP_EOL );
	}
	exit( 1 );
}

echo "WordPress.org review guard: ok\n";
