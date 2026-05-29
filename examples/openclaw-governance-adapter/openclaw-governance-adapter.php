#!/usr/bin/env php
<?php
/**
 * Minimal external governance adapter example for OpenClaw-like clients.
 *
 * This script calls Magick AI Core REST governance routes. It does not expose
 * MCP tools, execute WordPress abilities, approve proposals, or route natural
 * language tasks.
 *
 * @package MagickAICore
 */

if ( PHP_SAPI !== 'cli' ) {
	fwrite( STDERR, "This script must be run from the command line.\n" );
	exit( 1 );
}

/**
 * Prints usage.
 *
 * @return void
 */
function magick_ai_core_adapter_usage(): void {
	$usage = <<<'TEXT'
Usage:
  php openclaw-governance-adapter.php capabilities
  php openclaw-governance-adapter.php create-proposal --ability=magick-ai/create-draft --title="Title" [--summary="Summary"] [--input='{}'] [--preview='{}'] [--caller='{}']
  php openclaw-governance-adapter.php commit-preflight --proposal=<proposal_id>

Required environment:
  MAGICK_AI_CORE_BASE_URL
  MAGICK_AI_CORE_APP_TOKEN

Fallback PoC environment for manage_options user auth:
  MAGICK_AI_CORE_USER
  MAGICK_AI_CORE_APPLICATION_PASSWORD

Notes:
  - Use real ability ids, not planning labels such as content/draft-preview.
  - This adapter intentionally does not approve proposals.
  - JSON options may be inline JSON or @/path/to/file.json.

TEXT;

	fwrite( STDERR, $usage );
}

/**
 * Exits with an error.
 *
 * @param string $message Error message.
 * @param int    $code Exit code.
 * @return void
 */
function magick_ai_core_adapter_fail( string $message, int $code = 1 ): void {
	fwrite( STDERR, $message . "\n" );
	exit( $code );
}

/**
 * Parses CLI options.
 *
 * @param array<int,string> $argv Raw argv.
 * @return array{command:string,options:array<string,string>}
 */
function magick_ai_core_adapter_parse_args( array $argv ): array {
	$command = (string) ( $argv[1] ?? 'help' );
	$options = array();

	for ( $i = 2; $i < count( $argv ); $i++ ) {
		$arg = (string) $argv[ $i ];
		if ( 0 !== strpos( $arg, '--' ) ) {
			continue;
		}

		$arg = substr( $arg, 2 );
		if ( false !== strpos( $arg, '=' ) ) {
			list( $key, $value ) = explode( '=', $arg, 2 );
			$options[ $key ]    = $value;
			continue;
		}

		$key = $arg;
		if ( isset( $argv[ $i + 1 ] ) && 0 !== strpos( (string) $argv[ $i + 1 ], '--' ) ) {
			$options[ $key ] = (string) $argv[ $i + 1 ];
			$i++;
			continue;
		}

		$options[ $key ] = '1';
	}

	return array(
		'command' => $command,
		'options' => $options,
	);
}

/**
 * Reads required env.
 *
 * @param string $name Env name.
 * @return string
 */
function magick_ai_core_adapter_env( string $name ): string {
	$value = getenv( $name );
	if ( ! is_string( $value ) || '' === trim( $value ) ) {
		magick_ai_core_adapter_fail( 'Missing required environment variable: ' . $name, 2 );
	}

	return trim( $value );
}

/**
 * Decodes a JSON option.
 *
 * @param string|null         $value Raw option.
 * @param array<string,mixed> $fallback Fallback.
 * @return array<string,mixed>
 */
function magick_ai_core_adapter_json_option( ?string $value, array $fallback = array() ): array {
	if ( null === $value || '' === trim( $value ) ) {
		return $fallback;
	}

	if ( '@' === substr( $value, 0, 1 ) ) {
		$path     = substr( $value, 1 );
		$contents = is_readable( $path ) ? file_get_contents( $path ) : false;
		if ( ! is_string( $contents ) ) {
			magick_ai_core_adapter_fail( 'Unable to read JSON file: ' . $path, 2 );
		}
		$value = $contents;
	}

	$decoded = json_decode( $value, true );
	if ( ! is_array( $decoded ) ) {
		magick_ai_core_adapter_fail( 'Expected JSON object: ' . $value, 2 );
	}

	return $decoded;
}

/**
 * Calls Core REST.
 *
 * @param string              $method HTTP method.
 * @param string              $path REST path without wp-json prefix.
 * @param array<string,mixed> $body Request body.
 * @return array<string,mixed>
 */
function magick_ai_core_adapter_request( string $method, string $path, array $body = array() ): array {
	if ( ! function_exists( 'curl_init' ) ) {
		magick_ai_core_adapter_fail( 'PHP cURL extension is required for this example adapter.', 2 );
	}

	$base_url = rtrim( magick_ai_core_adapter_env( 'MAGICK_AI_CORE_BASE_URL' ), '/' );
	$app_token = getenv( 'MAGICK_AI_CORE_APP_TOKEN' );
	$timeout  = getenv( 'MAGICK_AI_CORE_TIMEOUT' );
	$timeout  = is_string( $timeout ) && '' !== trim( $timeout ) ? max( 1, (int) $timeout ) : 30;

	$url = $base_url . '/wp-json/magick-ai-core/v1/' . ltrim( $path, '/' );

	$headers = array(
		'Accept: application/json',
	);

	if ( is_string( $app_token ) && '' !== trim( $app_token ) ) {
		$headers[] = 'Authorization: Bearer ' . trim( $app_token );
	} else {
		$user     = magick_ai_core_adapter_env( 'MAGICK_AI_CORE_USER' );
		$password = magick_ai_core_adapter_env( 'MAGICK_AI_CORE_APPLICATION_PASSWORD' );
		$headers[] = 'Authorization: Basic ' . base64_encode( $user . ':' . $password );
	}

	$curl = curl_init( $url );
	if ( false === $curl ) {
		magick_ai_core_adapter_fail( 'Unable to initialize HTTP client.', 2 );
	}

	curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
	curl_setopt( $curl, CURLOPT_CUSTOMREQUEST, strtoupper( $method ) );
	curl_setopt( $curl, CURLOPT_HTTPHEADER, $headers );
	curl_setopt( $curl, CURLOPT_TIMEOUT, $timeout );

	if ( ! empty( $body ) ) {
		$encoded = json_encode( $body );
		if ( ! is_string( $encoded ) ) {
			magick_ai_core_adapter_fail( 'Unable to encode request JSON.', 2 );
		}

		$headers[] = 'Content-Type: application/json';
		curl_setopt( $curl, CURLOPT_HTTPHEADER, $headers );
		curl_setopt( $curl, CURLOPT_POSTFIELDS, $encoded );
	}

	$response = curl_exec( $curl );
	$error    = curl_error( $curl );
	$status   = (int) curl_getinfo( $curl, CURLINFO_RESPONSE_CODE );
	curl_close( $curl );

	if ( false === $response ) {
		magick_ai_core_adapter_fail( 'HTTP request failed: ' . $error, 1 );
	}

	$decoded = json_decode( (string) $response, true );
	if ( ! is_array( $decoded ) ) {
		magick_ai_core_adapter_fail( 'Core returned non-JSON response with HTTP ' . $status . '.', 1 );
	}

	if ( $status < 200 || $status >= 300 ) {
		$output = json_encode(
			array(
				'status' => $status,
				'error'  => $decoded,
			),
			JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
		);
		fwrite( STDERR, ( is_string( $output ) ? $output : 'Request failed.' ) . "\n" );
		exit( 1 );
	}

	return $decoded;
}

/**
 * Prints JSON.
 *
 * @param array<string,mixed> $data Data.
 * @return void
 */
function magick_ai_core_adapter_print_json( array $data ): void {
	$output = json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
	if ( ! is_string( $output ) ) {
		magick_ai_core_adapter_fail( 'Unable to encode output JSON.', 1 );
	}

	echo $output . "\n";
}

$parsed  = magick_ai_core_adapter_parse_args( $argv );
$command = $parsed['command'];
$options = $parsed['options'];

if ( in_array( $command, array( 'help', '--help', '-h' ), true ) ) {
	magick_ai_core_adapter_usage();
	exit( 0 );
}

if ( 'capabilities' === $command ) {
	magick_ai_core_adapter_print_json( magick_ai_core_adapter_request( 'GET', 'capabilities' ) );
	exit( 0 );
}

if ( 'create-proposal' === $command ) {
	$ability_id = trim( (string) ( $options['ability'] ?? $options['ability_id'] ?? '' ) );
	if ( '' === $ability_id ) {
		magick_ai_core_adapter_fail( 'create-proposal requires --ability=<real ability_id>.', 2 );
	}

	$caller = magick_ai_core_adapter_json_option( $options['caller'] ?? null );
	$caller = array_merge(
		array(
			'source'       => 'openclaw-governance-adapter-example',
			'adapter_kind' => 'external_governance_client',
		),
		$caller
	);

	$payload = array(
		'ability_id' => $ability_id,
		'title'      => (string) ( $options['title'] ?? 'Agent proposal' ),
		'summary'    => (string) ( $options['summary'] ?? 'Created by external governance adapter.' ),
		'input'      => magick_ai_core_adapter_json_option( $options['input'] ?? null ),
		'preview'    => magick_ai_core_adapter_json_option( $options['preview'] ?? null ),
		'caller'     => $caller,
	);

	magick_ai_core_adapter_print_json( magick_ai_core_adapter_request( 'POST', 'proposals', $payload ) );
	exit( 0 );
}

if ( 'commit-preflight' === $command ) {
	$proposal_id = trim( (string) ( $options['proposal'] ?? $options['proposal_id'] ?? '' ) );
	if ( '' === $proposal_id ) {
		magick_ai_core_adapter_fail( 'commit-preflight requires --proposal=<proposal_id>.', 2 );
	}

	magick_ai_core_adapter_print_json( magick_ai_core_adapter_request( 'POST', 'proposals/' . rawurlencode( $proposal_id ) . '/commit-preflight' ) );
	exit( 0 );
}

magick_ai_core_adapter_usage();
magick_ai_core_adapter_fail( 'Unknown command: ' . $command, 2 );
