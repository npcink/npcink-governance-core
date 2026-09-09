<?php
/** Regression checks for revision-bound Core M4 Docker evidence. */

declare(strict_types=1);

$root         = dirname( __DIR__ );
$toolkit_root = (string) ( getenv( 'NPCINK_ABILITIES_TOOLKIT_PATH' ) ?: dirname( $root ) . '/npcink-abilities-toolkit' );
$command      = static function ( string $repository, string $arguments ): string {
	return trim( (string) shell_exec( 'git -C ' . escapeshellarg( $repository ) . ' ' . $arguments ) );
};
$archive_hash = static function ( string $repository ) use ( $command ): string {
	$output = $command( $repository, 'archive --format=tar HEAD | shasum -a 256' );
	return 1 === preg_match( '/^([0-9a-f]{64})\b/', $output, $matches ) ? $matches[1] : '';
};

$head                   = $command( $root, 'rev-parse HEAD' );
$source_archive_sha256  = $archive_hash( $root );
$toolkit_head           = $command( $toolkit_root, 'rev-parse HEAD' );
$toolkit_archive_sha256 = $archive_hash( $toolkit_root );
$package_path           = $root . '/build/npcink-governance-core.zip';
$package_sha256         = is_readable( $package_path ) ? hash_file( 'sha256', $package_path ) : false;
$path                   = tempnam( sys_get_temp_dir(), 'npcink-core-evidence-' );

if ( false === $path || ! is_string( $package_sha256 ) || '' === $head || '' === $source_archive_sha256 || '' === $toolkit_head || '' === $toolkit_archive_sha256 ) {
	fwrite( STDERR, "FAIL: Core M4 evidence fixture setup failed.\n" );
	exit( 1 );
}

$base = array(
	'schema_version'        => 'npcink_core_wordpress_smoke_evidence.v1',
	'runner'                => 'm4-docker',
	'source_revision'       => $head,
	'source_archive_sha256' => $source_archive_sha256,
	'package_sha256'        => $package_sha256,
	'toolkit_revision'      => $toolkit_head,
	'toolkit_archive_sha256' => $toolkit_archive_sha256,
	'docker_server_version' => '29.7.2',
	'generated_at'          => gmdate( 'Y-m-d\TH:i:s\Z' ),
	'profiles'              => array(
		'wordpress-7.0-php-8.0' => array( 'wordpress' => '7.0', 'php' => '8.0', 'assertions' => 1, 'installed_from_zip' => true, 'status' => 'passed' ),
		'wordpress-7.0-php-8.5'  => array( 'wordpress' => '7.0', 'php' => '8.5', 'assertions' => 1, 'installed_from_zip' => true, 'status' => 'passed' ),
	),
);

$run = static function ( array $evidence ) use ( $root, $toolkit_root, $path ): int {
	file_put_contents( $path, json_encode( $evidence, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) );
	$status = 0;
	passthru(
		'NPCINK_ABILITIES_TOOLKIT_PATH=' . escapeshellarg( $toolkit_root ) . ' '
		. escapeshellarg( PHP_BINARY ) . ' '
		. escapeshellarg( $root . '/scripts/check-wordpress-smoke-evidence.php' ) . ' '
		. escapeshellarg( $path ) . ' >/dev/null 2>&1',
		$status
	);
	return $status;
};

if ( 0 !== $run( $base ) ) {
	fwrite( STDERR, "FAIL: Exact Core M4 evidence was rejected.\n" );
	exit( 1 );
}

$invalid_cases = array();
$invalid_cases['stale Core revision']                    = array_replace( $base, array( 'source_revision' => str_repeat( '0', 40 ) ) );
$invalid_cases['wrong Core archive']                     = array_replace( $base, array( 'source_archive_sha256' => str_repeat( '0', 64 ) ) );
$invalid_cases['wrong package']                          = array_replace( $base, array( 'package_sha256' => str_repeat( '0', 64 ) ) );
$invalid_cases['stale Toolkit revision']                 = array_replace( $base, array( 'toolkit_revision' => str_repeat( '0', 40 ) ) );
$invalid_cases['wrong Toolkit archive']                  = array_replace( $base, array( 'toolkit_archive_sha256' => str_repeat( '0', 64 ) ) );
$invalid_cases['missing Docker identity']                = array_replace( $base, array( 'docker_server_version' => '' ) );
$invalid_cases['expired evidence']                       = array_replace( $base, array( 'generated_at' => '2000-01-01T00:00:00Z' ) );
$missing_profile                                         = $base;
unset( $missing_profile['profiles']['wordpress-7.0-php-8.5'] );
$invalid_cases['missing profile']                        = $missing_profile;
$not_packaged                                            = $base;
$not_packaged['profiles']['wordpress-7.0-php-8.0']['installed_from_zip'] = false;
$invalid_cases['source-mounted profile']                 = $not_packaged;

foreach ( $invalid_cases as $label => $evidence ) {
	if ( 0 === $run( $evidence ) ) {
		fwrite( STDERR, "FAIL: Core M4 evidence accepted $label.\n" );
	exit( 1 );
	}
}

$syntax_status = 0;
passthru(
	'bash -n ' . escapeshellarg( $root . '/scripts/run-m4-wordpress-smoke.sh' ) . ' '
	. escapeshellarg( $root . '/scripts/m4-wordpress-package-profile.sh' ),
	$syntax_status
);
unlink( $path );
if ( 0 !== $syntax_status ) {
	fwrite( STDERR, "FAIL: Core M4 smoke scripts have invalid shell syntax.\n" );
	exit( 1 );
}

$runner = file_get_contents( $root . '/scripts/run-m4-wordpress-smoke.sh' );
if ( false === $runner || 2 !== substr_count( $runner, "sed -nE 's/^Smoke OK: ([0-9]+) assertions$/\\1/p'" ) ) {
	fwrite( STDERR, "FAIL: Core M4 runner does not parse both assertion summaries.\n" );
	exit( 1 );
}
if ( 2 !== substr_count( $runner, "sed -n '1,4000p'" ) ) {
	fwrite( STDERR, "FAIL: Core M4 runner does not preserve both remote failure logs.\n" );
	exit( 1 );
}
if ( false === strpos( $runner, 'git ls-files -z' ) || false === strpos( $runner, 'test-workspace.tar' ) ) {
	fwrite( STDERR, "FAIL: Core M4 runner does not separate tracked test workspaces from distribution archives.\n" );
	exit( 1 );
}

$profile_runner = file_get_contents( $root . '/scripts/m4-wordpress-package-profile.sh' );
if ( false === $profile_runner || false === strpos( $profile_runner, 'plugin install' ) || false === strpos( $profile_runner, 'npcink-governance-core.zip --force --activate' ) ) {
	fwrite( STDERR, "FAIL: Core M4 profile does not install the release ZIP.\n" );
	exit( 1 );
}
if ( false === strpos( $profile_runner, 'runtime_wordpress' ) || false === strpos( $profile_runner, 'runtime_php' ) ) {
	fwrite( STDERR, "FAIL: Core M4 profile does not verify actual WordPress and PHP versions.\n" );
	exit( 1 );
}
if ( false === strpos( $profile_runner, "sed -n '1,8000p'" ) ) {
	fwrite( STDERR, "FAIL: Core M4 profile does not preserve the WordPress smoke failure log.\n" );
	exit( 1 );
}

echo "Revision-bound Core M4 WordPress smoke evidence behavior: ok\n";
