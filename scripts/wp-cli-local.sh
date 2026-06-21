#!/usr/bin/env bash
set -euo pipefail

WP_PATH="${WP_PATH:-/Users/muze/Local Sites/magick-ai/app/public}"
WP_CLI_BIN="${WP_CLI:-${WP_CLI_BIN:-}}"
WP_CLI_PHP="${WP_CLI_PHP:-}"
WP_CLI_ERROR_REPORTING="${WP_CLI_ERROR_REPORTING:-8191}"
WP_CLI_MYSQL_SOCKET="${WP_CLI_MYSQL_SOCKET:-${WP_DB_SOCKET:-}}"

usage() {
	cat <<USAGE
Usage: scripts/wp-cli-local.sh <wp-cli-command> [args...]

Runs WP-CLI against the local LocalWP smoke site with stable PHP and database
socket defaults. Environment overrides:

  WP_PATH                 WordPress root. Default: /Users/muze/Local Sites/magick-ai/app/public
  WP_CLI or WP_CLI_BIN    WP-CLI phar/binary. Default: /tmp/wp-cli.phar, then PATH wp.
  WP_CLI_PHP             PHP binary for WP-CLI. Default prefers the LocalWP PHP
                         runtime used by the smoke site.
  WP_CLI_MYSQL_SOCKET    Local MySQL socket. Falls back to WP_DB_SOCKET.
  WP_CLI_ERROR_REPORTING PHP error_reporting value. Default: 8191.

Examples:
  scripts/wp-cli-local.sh core is-installed
  scripts/wp-cli-local.sh plugin list --skip-update-check
USAGE
}

if [[ "${1:-}" == "-h" || "${1:-}" == "--help" ]]; then
	usage
	exit 0
fi

if [[ $# -eq 0 ]]; then
	usage >&2
	exit 2
fi

if [[ -z "$WP_CLI_BIN" ]]; then
	if [[ -f /tmp/wp-cli.phar ]]; then
		WP_CLI_BIN="/tmp/wp-cli.phar"
	elif command -v wp >/dev/null 2>&1; then
		WP_CLI_BIN="$(command -v wp)"
	else
		echo "Missing WP-CLI. Set WP_CLI=/path/to/wp-cli.phar or install wp on PATH." >&2
		exit 127
	fi
fi

if [[ -z "$WP_CLI_PHP" ]]; then
	for candidate in \
		"$HOME/Library/Application Support/Local/lightning-services/php-8.5.3+1/bin/darwin-arm64/bin/php" \
		"$HOME/Library/Application Support/Local/lightning-services/php-8.2.29+0/bin/darwin-arm64/bin/php" \
		"$(command -v php 2>/dev/null || true)"
	do
		if [[ -n "$candidate" && -x "$candidate" ]]; then
			WP_CLI_PHP="$candidate"
			break
		fi
	done
fi

if [[ -z "$WP_CLI_PHP" ]]; then
	echo "Missing PHP for WP-CLI. Set WP_CLI_PHP=/path/to/php." >&2
	exit 127
fi

if [[ -z "$WP_CLI_MYSQL_SOCKET" ]]; then
	default_socket="$HOME/Library/Application Support/Local/run/NPb24Zg9g/mysql/mysqld.sock"
	if [[ -S "$default_socket" ]]; then
		WP_CLI_MYSQL_SOCKET="$default_socket"
	fi
fi

php_args=("-d" "display_errors=0")
if [[ -n "$WP_CLI_ERROR_REPORTING" ]]; then
	php_args+=("-d" "error_reporting=$WP_CLI_ERROR_REPORTING")
fi
if [[ -n "$WP_CLI_MYSQL_SOCKET" ]]; then
	php_args+=("-d" "mysqli.default_socket=$WP_CLI_MYSQL_SOCKET")
	php_args+=("-d" "pdo_mysql.default_socket=$WP_CLI_MYSQL_SOCKET")
fi

wp_args=()
if [[ -n "$WP_PATH" ]]; then
	wp_args+=(--path="$WP_PATH")
fi

"$WP_CLI_PHP" "${php_args[@]}" "$WP_CLI_BIN" "${wp_args[@]}" --no-color "$@"
