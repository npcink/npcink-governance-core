<?php
/**
 * PHP syntax lint runner.
 *
 * @package MagickAICore
 */

$root = dirname( __DIR__ );

/**
 * Returns PHP files to lint.
 *
 * @param string $root Project root.
 * @return array<int,string>
 */
function magick_ai_core_lint_files( string $root ): array {
	$files = array( $root . '/magick-ai-core.php' );

	foreach ( array( 'includes', 'tests' ) as $directory ) {
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
foreach ( magick_ai_core_lint_files( $root ) as $file ) {
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

