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
  php openclaw-governance-adapter.php create-draft-proposal --title="Title" [--content="<p>Body</p>"] [--input='{}'] [--preview='{}'] [--caller='{}']
  php openclaw-governance-adapter.php create-seo-meta-proposal --post-id=123 [--seo-title="SEO title"] [--seo-description="SEO description"] [--input='{}'] [--preview='{}'] [--caller='{}']
  php openclaw-governance-adapter.php create-proposal --ability=magick-ai/create-draft --title="Title" [--summary="Summary"] [--input='{}'] [--preview='{}'] [--caller='{}']
  php openclaw-governance-adapter.php commit-preflight --proposal=<proposal_id>

Required environment:
  MAGICK_AI_CORE_BASE_URL
  MAGICK_AI_CORE_APP_TOKEN

Optional local TLS environment:
  MAGICK_AI_CORE_CA_BUNDLE=/path/to/local-ca.pem
  MAGICK_AI_CORE_INSECURE_SSL=true

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
 * Returns whether the URL host is local-only.
 *
 * @param string $url Base URL.
 * @return bool
 */
function magick_ai_core_adapter_is_local_url( string $url ): bool {
	$host = parse_url( $url, PHP_URL_HOST );
	if ( ! is_string( $host ) ) {
		return false;
	}

	$host = strtolower( trim( $host, '[]' ) );
	if ( in_array( $host, array( 'localhost', '127.0.0.1', '::1' ), true ) ) {
		return true;
	}

	return strlen( $host ) >= 6 && '.local' === substr( $host, -6 );
}

/**
 * Configures TLS behavior for local development.
 *
 * @param mixed  $curl cURL handle.
 * @param string $base_url Base URL.
 * @return void
 */
function magick_ai_core_adapter_configure_tls( $curl, string $base_url ): void {
	$ca_bundle = getenv( 'MAGICK_AI_CORE_CA_BUNDLE' );
	if ( is_string( $ca_bundle ) && '' !== trim( $ca_bundle ) ) {
		$ca_bundle = trim( $ca_bundle );
		if ( ! is_readable( $ca_bundle ) ) {
			magick_ai_core_adapter_fail( 'MAGICK_AI_CORE_CA_BUNDLE is not readable: ' . $ca_bundle, 2 );
		}

		curl_setopt( $curl, CURLOPT_CAINFO, $ca_bundle );
		return;
	}

	$insecure = getenv( 'MAGICK_AI_CORE_INSECURE_SSL' );
	if ( 'true' !== strtolower( trim( is_string( $insecure ) ? $insecure : '' ) ) ) {
		return;
	}

	if ( ! magick_ai_core_adapter_is_local_url( $base_url ) ) {
		magick_ai_core_adapter_fail( 'MAGICK_AI_CORE_INSECURE_SSL=true is only allowed for localhost, 127.0.0.1, ::1, or .local hosts.', 2 );
	}

	curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, false );
	curl_setopt( $curl, CURLOPT_SSL_VERIFYHOST, 0 );
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
	magick_ai_core_adapter_configure_tls( $curl, $base_url );

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

/**
 * Finds a capability row by ability id.
 *
 * @param array<string,mixed> $capabilities Capabilities response.
 * @param string              $ability_id Ability id.
 * @return array<string,mixed>
 */
function magick_ai_core_adapter_find_capability( array $capabilities, string $ability_id ): array {
	foreach ( (array) ( $capabilities['items'] ?? array() ) as $item ) {
		if ( is_array( $item ) && $ability_id === (string) ( $item['ability_id'] ?? '' ) ) {
			return $item;
		}
	}

	magick_ai_core_adapter_fail( 'Required ability is not discoverable through Core: ' . $ability_id, 1 );
}

/**
 * Verifies the discovered create-draft ability is still host-governed.
 *
 * @param array<string,mixed> $ability Ability row.
 * @return void
 */
function magick_ai_core_adapter_assert_create_draft_contract( array $ability ): void {
	if ( 'write' !== (string) ( $ability['risk_level'] ?? '' ) || true !== (bool) ( $ability['requires_approval'] ?? false ) ) {
		magick_ai_core_adapter_fail( 'magick-ai/create-draft is not exposed as a host-governed write ability.', 1 );
	}

	$input_schema = is_array( $ability['input_schema'] ?? null ) ? $ability['input_schema'] : array();
	$required     = (array) ( $input_schema['required'] ?? array() );
	$properties   = is_array( $input_schema['properties'] ?? null ) ? $input_schema['properties'] : array();

	if ( ! in_array( 'title', $required, true ) ) {
		magick_ai_core_adapter_fail( 'magick-ai/create-draft input schema does not require title.', 1 );
	}

	foreach ( array( 'dry_run', 'commit', 'idempotency_key' ) as $control ) {
		if ( ! array_key_exists( $control, $properties ) ) {
			magick_ai_core_adapter_fail( 'magick-ai/create-draft input schema is missing governance control: ' . $control, 1 );
		}
	}
}

/**
 * Verifies the discovered SEO metadata ability is still host-governed.
 *
 * @param array<string,mixed> $ability Ability row.
 * @return void
 */
function magick_ai_core_adapter_assert_seo_meta_contract( array $ability ): void {
	if ( 'write' !== (string) ( $ability['risk_level'] ?? '' ) || true !== (bool) ( $ability['requires_approval'] ?? false ) ) {
		magick_ai_core_adapter_fail( 'magick-ai/set-post-seo-meta is not exposed as a host-governed write ability.', 1 );
	}

	$input_schema = is_array( $ability['input_schema'] ?? null ) ? $ability['input_schema'] : array();
	$required     = (array) ( $input_schema['required'] ?? array() );
	$properties   = is_array( $input_schema['properties'] ?? null ) ? $input_schema['properties'] : array();

	if ( ! in_array( 'post_id', $required, true ) ) {
		magick_ai_core_adapter_fail( 'magick-ai/set-post-seo-meta input schema does not require post_id.', 1 );
	}

	foreach ( array( 'seo_title', 'seo_description', 'dry_run', 'commit', 'idempotency_key' ) as $control ) {
		if ( ! array_key_exists( $control, $properties ) ) {
			magick_ai_core_adapter_fail( 'magick-ai/set-post-seo-meta input schema is missing field/control: ' . $control, 1 );
		}
	}
}

/**
 * Returns non-empty SEO metadata fields from input.
 *
 * @param array<string,mixed> $input Proposal input.
 * @return array<string,mixed>
 */
function magick_ai_core_adapter_seo_field_patch( array $input ): array {
	$patch = array();
	foreach ( array( 'seo_title', 'seo_description' ) as $field ) {
		if ( array_key_exists( $field, $input ) && '' !== trim( (string) $input[ $field ] ) ) {
			$patch[ $field ] = $input[ $field ];
		}
	}

	return $patch;
}

/**
 * Builds standard caller metadata.
 *
 * @param array<string,mixed> $caller Caller overrides.
 * @return array<string,mixed>
 */
function magick_ai_core_adapter_caller( array $caller = array() ): array {
	return array_merge(
		array(
			'source'       => 'openclaw-governance-adapter-example',
			'adapter_kind' => 'external_governance_client',
		),
		$caller
	);
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

if ( 'create-draft-proposal' === $command ) {
	$capabilities = magick_ai_core_adapter_request( 'GET', 'capabilities' );
	$ability      = magick_ai_core_adapter_find_capability( $capabilities, 'magick-ai/create-draft' );
	magick_ai_core_adapter_assert_create_draft_contract( $ability );

	$input = magick_ai_core_adapter_json_option( $options['input'] ?? null );
	if ( empty( $input['title'] ) ) {
		$input['title'] = (string) ( $options['title'] ?? '' );
	}
	if ( empty( $input['content'] ) && isset( $options['content'] ) ) {
		$input['content'] = (string) $options['content'];
	}
	$input['dry_run'] = true;
	$input['commit']  = false;

	if ( '' === trim( (string) ( $input['title'] ?? '' ) ) ) {
		magick_ai_core_adapter_fail( 'create-draft-proposal requires --title or input.title.', 2 );
	}

	$preview = array_merge(
		magick_ai_core_adapter_json_option( $options['preview'] ?? null ),
		array(
			'ability_risk_level'    => (string) ( $ability['risk_level'] ?? '' ),
			'requires_approval'     => (bool) ( $ability['requires_approval'] ?? false ),
			'input_required_fields' => (array) ( $ability['input_schema']['required'] ?? array() ),
			'dry_run'               => true,
			'host_governed'         => true,
			'commit_execution'      => false,
		)
	);

	$payload = array(
		'ability_id' => 'magick-ai/create-draft',
		'title'      => (string) ( $options['proposal-title'] ?? $options['title'] ?? 'OpenClaw draft proposal' ),
		'summary'    => (string) ( $options['summary'] ?? 'Review before creating a draft. Core will not execute the write.' ),
		'input'      => $input,
		'preview'    => $preview,
		'caller'     => magick_ai_core_adapter_caller( magick_ai_core_adapter_json_option( $options['caller'] ?? null ) ),
	);

	magick_ai_core_adapter_print_json( magick_ai_core_adapter_request( 'POST', 'proposals', $payload ) );
	exit( 0 );
}

if ( 'create-seo-meta-proposal' === $command ) {
	$capabilities = magick_ai_core_adapter_request( 'GET', 'capabilities' );
	$ability      = magick_ai_core_adapter_find_capability( $capabilities, 'magick-ai/set-post-seo-meta' );
	magick_ai_core_adapter_assert_seo_meta_contract( $ability );

	$input = magick_ai_core_adapter_json_option( $options['input'] ?? null );
	if ( empty( $input['post_id'] ) && isset( $options['post-id'] ) ) {
		$input['post_id'] = (int) $options['post-id'];
	}
	if ( empty( $input['seo_title'] ) && isset( $options['seo-title'] ) ) {
		$input['seo_title'] = (string) $options['seo-title'];
	}
	if ( empty( $input['seo_description'] ) && isset( $options['seo-description'] ) ) {
		$input['seo_description'] = (string) $options['seo-description'];
	}
	$input['dry_run'] = true;
	$input['commit']  = false;

	if ( empty( $input['post_id'] ) || (int) $input['post_id'] < 1 ) {
		magick_ai_core_adapter_fail( 'create-seo-meta-proposal requires --post-id or input.post_id.', 2 );
	}

	$field_patch = magick_ai_core_adapter_seo_field_patch( $input );
	if ( empty( $field_patch ) ) {
		magick_ai_core_adapter_fail( 'create-seo-meta-proposal requires seo_title or seo_description.', 2 );
	}

	$preview = array_merge(
		magick_ai_core_adapter_json_option( $options['preview'] ?? null ),
		array(
			'ability_risk_level'    => (string) ( $ability['risk_level'] ?? '' ),
			'requires_approval'     => (bool) ( $ability['requires_approval'] ?? false ),
			'input_required_fields' => (array) ( $ability['input_schema']['required'] ?? array() ),
			'field_patch'           => $field_patch,
			'dry_run'               => true,
			'host_governed'         => true,
			'commit_execution'      => false,
		)
	);

	$payload = array(
		'ability_id' => 'magick-ai/set-post-seo-meta',
		'title'      => (string) ( $options['proposal-title'] ?? 'OpenClaw SEO metadata proposal' ),
		'summary'    => (string) ( $options['summary'] ?? 'Review SEO metadata field updates before changing an existing post. Core will not execute the write.' ),
		'input'      => $input,
		'preview'    => $preview,
		'caller'     => magick_ai_core_adapter_caller( magick_ai_core_adapter_json_option( $options['caller'] ?? null ) ),
	);

	magick_ai_core_adapter_print_json( magick_ai_core_adapter_request( 'POST', 'proposals', $payload ) );
	exit( 0 );
}

if ( 'create-proposal' === $command ) {
	$ability_id = trim( (string) ( $options['ability'] ?? $options['ability_id'] ?? '' ) );
	if ( '' === $ability_id ) {
		magick_ai_core_adapter_fail( 'create-proposal requires --ability=<real ability_id>.', 2 );
	}

	$payload = array(
		'ability_id' => $ability_id,
		'title'      => (string) ( $options['title'] ?? 'Agent proposal' ),
		'summary'    => (string) ( $options['summary'] ?? 'Created by external governance adapter.' ),
		'input'      => magick_ai_core_adapter_json_option( $options['input'] ?? null ),
		'preview'    => magick_ai_core_adapter_json_option( $options['preview'] ?? null ),
		'caller'     => magick_ai_core_adapter_caller( magick_ai_core_adapter_json_option( $options['caller'] ?? null ) ),
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
