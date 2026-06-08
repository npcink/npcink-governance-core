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

/**
 * Asserts option-table transient keys expose the plugin prefix at call sites.
 *
 * @param string $relative Relative file path.
 * @param string $contents File contents.
 * @return void
 */
function npcink_governance_core_wporg_assert_prefixed_transient_calls( $relative, $contents ) {
	if ( ! preg_match_all( '/\b(get|set)_transient\s*\(\s*([^,\)\r\n]+)/', $contents, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE ) ) {
		return;
	}

	foreach ( $matches as $match ) {
		$function = $match[1][0];
		$argument = trim( $match[2][0] );
		$line     = substr_count( substr( $contents, 0, $match[0][1] ), "\n" ) + 1;

		if ( preg_match( '/^\$[A-Za-z_][A-Za-z0-9_]*$/', $argument ) ) {
			npcink_governance_core_wporg_fail(
				$relative . ':' . $line . ': Do not pass a variable-only key to ' . $function . '(); make the npcink_governance_core prefix visible at the call site.'
			);
			continue;
		}

		if ( false === strpos( $argument, 'AUTO_APPROVAL_TRANSIENT_PREFIX' ) && ! preg_match( '/[\'"]npcink_governance_core/', $argument ) ) {
			npcink_governance_core_wporg_fail(
				$relative . ':' . $line . ': Transient keys must expose an auditable npcink_governance_core prefix at the call site.'
			);
		}
	}
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

	npcink_governance_core_wporg_assert_prefixed_transient_calls( $relative, $contents );
}

if ( ! empty( $failures ) ) {
	foreach ( $failures as $failure ) {
		fwrite( STDERR, '[fail] ' . $failure . PHP_EOL );
	}
	exit( 1 );
}

echo "WordPress.org review guard: ok\n";
