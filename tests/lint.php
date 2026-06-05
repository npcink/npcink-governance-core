<?php
/**
 * PHP syntax lint runner.
 *
 * @package NpcinkGovernanceCore
 */

$root = dirname( __DIR__ );

/**
 * Returns PHP files to lint.
 *
 * @param string $root Project root.
 * @return array<int,string>
 */
function npcink_governance_core_lint_files( string $root ): array {
	$files = array( $root . '/npcink-governance-core.php' );

	foreach ( array( 'includes', 'tests', 'examples' ) as $directory ) {
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

	return array_values( array_unique( $files ) );
}

$failures = array();
foreach ( npcink_governance_core_lint_files( $root ) as $file ) {
	$command = escapeshellarg( PHP_BINARY ) . ' -l ' . escapeshellarg( $file ) . ' 2>&1';
	$output  = array();
	$status  = 0;
	exec( $command, $output, $status );

	if ( 0 !== $status ) {
		$failures[] = $file . "\n" . implode( "\n", $output );
	}
}

if ( ! empty( $failures ) ) {
	fwrite( STDERR, implode( "\n\n", $failures ) . "\n" );
	exit( 1 );
}

echo "PHP lint: ok\n";
