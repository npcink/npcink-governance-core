<?php
/** Bootstrap test module. @package NpcinkGovernanceCore */

$root = dirname( __DIR__, 2 );

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', $root . '/tests/wp-stub/' );
}

/**
 * Collected static contract failures.
 *
 * @var array<int,string>
 */
$GLOBALS['npcink_governance_core_contract_failures'] = array();

/**
 * Prints every collected contract failure and exits non-zero, or confirms ok.
 *
 * @return void
 */
function npcink_governance_core_report_contract_failures(): void {
	$failures = $GLOBALS['npcink_governance_core_contract_failures'];
	if ( 0 === count( $failures ) ) {
		echo "Static contracts: ok\n";
		return;
	}
	foreach ( array_slice( $failures, 0, 50 ) as $failure ) {
		fwrite( STDERR, '[fail] ' . $failure . "\n" );
	}
	$unshown = count( $failures ) - 50;
	if ( $unshown > 0 ) {
		fwrite( STDERR, "... and {$unshown} more failure(s) not shown\n" );
	}
	fwrite( STDERR, 'Static contracts: ' . count( $failures ) . " failure(s)\n" );
	exit( 1 );
}

/**
 * Assertion helper that collects every failure so one run reports the full
 * failure surface instead of stopping at the first miss.
 *
 * @param bool   $condition Condition.
 * @param string $message Failure message.
 * @return void
 */
function npcink_governance_core_assert( bool $condition, string $message ): void {
	if ( ! $condition ) {
		$GLOBALS['npcink_governance_core_contract_failures'][] = $message;
	}
}

/**
 * Reads a file.
 *
 * @param string $path Path.
 * @return string
 */
function npcink_governance_core_read( string $path ): string {
	$contents = is_readable( $path ) ? file_get_contents( $path ) : false;
	return is_string( $contents ) ? $contents : '';
}

/** Reads the admin facade and its private view traits for portable contracts. */
function npcink_governance_core_admin_sources( string $root ): string {
	$source = npcink_governance_core_read( $root . '/includes/Admin/Admin_Page.php' );
	foreach ( glob( $root . '/includes/Admin/*_View.php' ) as $path ) {
		$source .= "\n" . npcink_governance_core_read( $path );
	}
	return $source;
}

/**
 * Returns project text files used for drift checks.
 *
 * @param string $root Root.
 * @return array<int,string>
 */
function npcink_governance_core_project_files( string $root ): array {
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

/**
 * Locates the sibling npcink-abilities-toolkit replay fixture.
 *
 * @param string $root Current project root.
 * @return string
 */
function npcink_governance_core_shared_replay_fixture_path( string $root ): string {
	$env_path = getenv( 'NPCINK_ABILITIES_TOOLKIT_PATH' );
	$roots    = array();

	if ( is_string( $env_path ) && '' !== trim( $env_path ) ) {
		$roots[] = rtrim( $env_path, '/' );
	}

	$roots[] = dirname( $root ) . '/npcink-abilities-toolkit';
	$roots[] = '/Users/muze/gitee/npcink-abilities-toolkit';

	foreach ( $roots as $toolkit_root ) {
		$fixture = $toolkit_root . '/tests/fixtures/agent-workflow-replay.json';
		if ( is_readable( $fixture ) ) {
			return $fixture;
		}
	}

	return '';
}

/**
 * Finds a forbidden key in a nested array.
 *
 * @param mixed         $value Value to inspect.
 * @param array<int,string> $forbidden_keys Forbidden key names.
 * @param string        $path Current path.
 * @return string
 */
function npcink_governance_core_find_forbidden_key( $value, array $forbidden_keys, string $path = '$' ): string {
	if ( ! is_array( $value ) ) {
		return '';
	}

	foreach ( $value as $key => $child ) {
		if ( is_string( $key ) && in_array( $key, $forbidden_keys, true ) ) {
			return $path . '.' . $key;
		}

		$child_path = is_string( $key ) ? $path . '.' . $key : $path . '[]';
		$found      = npcink_governance_core_find_forbidden_key( $child, $forbidden_keys, $child_path );
		if ( '' !== $found ) {
			return $found;
		}
	}

	return '';
}


/** Reads the entrypoint and its modules for source-based coverage contracts. */
function npcink_governance_core_test_sources( string $root, string $runner ): string {
	$source = npcink_governance_core_read( $root . '/tests/' . $runner . '.php' );
	foreach ( glob( $root . '/tests/' . $runner . '/*.php' ) ?: array() as $module ) {
		$source .= "\n" . npcink_governance_core_read( $module );
	}
	return $source;
}
