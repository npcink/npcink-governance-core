#!/usr/bin/env bash
set -euo pipefail

CORE_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DEFAULT_REPO_PARENT="$(dirname "$CORE_ROOT")"

ADAPTER_ROOT="${NPCINK_AI_CLIENT_ADAPTER_ROOT:-$DEFAULT_REPO_PARENT/npcink-ai-client-adapter}"
TOOLKIT_ROOT="${NPCINK_ABILITIES_TOOLKIT_ROOT:-$DEFAULT_REPO_PARENT/npcink-abilities-toolkit}"
WP_PATH="${WP_PATH:-/Users/muze/Local Sites/magick-ai/app/public}"
WP_CLI="${WP_CLI:-}"
WP_CLI_PHP="${WP_CLI_PHP:-}"
WP_CLI_ERROR_REPORTING="${WP_CLI_ERROR_REPORTING:-8191}"
WP_CLI_MYSQL_SOCKET="${WP_CLI_MYSQL_SOCKET:-$HOME/Library/Application Support/Local/run/NPb24Zg9g/mysql/mysqld.sock}"
WP_DB_SOCKET="${WP_DB_SOCKET:-$WP_CLI_MYSQL_SOCKET}"
ALLOW_DIRTY=0
SKIP_PACKAGING=0
SKIP_ADAPTER_FIXTURE=0

usage() {
	cat <<USAGE
Usage: scripts/cross-repo-release-acceptance.sh [--allow-dirty] [--skip-packaging] [--skip-adapter-fixture]

Runs the current Npcink AI cross-repository release acceptance gate:
  - Core governance source, WordPress smoke, Plugin Check, and package gate.
  - Adapter thin-channel source, WordPress smoke, release package, package install,
    and signed local AI client fixture.
  - Toolkit ability-layer source, PHPStan, release verification, and WordPress smoke.

Environment overrides:
  NPCINK_AI_CLIENT_ADAPTER_ROOT    Path to npcink-ai-client-adapter.
  NPCINK_ABILITIES_TOOLKIT_ROOT    Path to npcink-abilities-toolkit.
  WP_PATH, WP_CLI, WP_CLI_PHP, WP_CLI_MYSQL_SOCKET, WP_DB_SOCKET.

Options:
  --allow-dirty          Run even when one of the three worktrees has local changes.
  --skip-packaging       Skip package/release package install checks.
  --skip-adapter-fixture Skip the final signed Adapter fixture.
  -h, --help             Show this help.
USAGE
}

while [[ $# -gt 0 ]]; do
	case "$1" in
		--allow-dirty)
			ALLOW_DIRTY=1
			shift
			;;
		--skip-packaging)
			SKIP_PACKAGING=1
			shift
			;;
		--skip-adapter-fixture)
			SKIP_ADAPTER_FIXTURE=1
			shift
			;;
		-h|--help)
			usage
			exit 0
			;;
		*)
			echo "Unknown argument: $1" >&2
			usage >&2
			exit 2
			;;
	esac
done

log() {
	printf '\n[cross-repo-release] %s\n' "$*"
}

require_command() {
	if ! command -v "$1" >/dev/null 2>&1; then
		echo "Missing required command: $1" >&2
		exit 2
	fi
}

require_repo() {
	local label="$1"
	local root="$2"
	if { [[ ! -d "$root/.git" ]] && [[ ! -f "$root/.git" ]]; } || [[ ! -f "$root/composer.json" ]]; then
		echo "$label repository was not found or is not a Composer project: $root" >&2
		exit 2
	fi
}

check_clean() {
	local label="$1"
	local root="$2"
	local status
	status="$(git -C "$root" status --short)"
	if [[ "$ALLOW_DIRTY" != "1" && -n "$status" ]]; then
		echo "$label working tree is not clean. Commit or stash changes first." >&2
		echo "$status" >&2
		echo "Use --allow-dirty only for local script diagnostics." >&2
		exit 1
	fi
}

run_in_repo() {
	local label="$1"
	local root="$2"
	shift 2
	log "$label: $*"
	(
		cd "$root"
		"$@"
	)
}

activate_plugin_if_possible() {
	local slug="$1"
	if [[ ! -f "$WP_CLI" || ! -x "$WP_CLI_PHP" || ! -d "$WP_PATH" ]]; then
		return
	fi

	if [[ "$WP_CLI" == *.phar ]]; then
		"$WP_CLI_PHP" \
			-d display_errors=0 \
			-d "error_reporting=$WP_CLI_ERROR_REPORTING" \
			-d "mysqli.default_socket=$WP_CLI_MYSQL_SOCKET" \
			-d "pdo_mysql.default_socket=$WP_CLI_MYSQL_SOCKET" \
			"$WP_CLI" --path="$WP_PATH" plugin activate "$slug" >/dev/null
		return
	fi

	"$WP_CLI" --path="$WP_PATH" plugin activate "$slug" >/dev/null
}

require_command composer
require_command git
require_repo "Core" "$CORE_ROOT"
require_repo "Adapter" "$ADAPTER_ROOT"
require_repo "Toolkit" "$TOOLKIT_ROOT"

if [[ -z "$WP_CLI" ]]; then
	if command -v wp >/dev/null 2>&1; then
		WP_CLI="$(command -v wp)"
	elif [[ -f /tmp/wp-cli.phar ]]; then
		WP_CLI="/tmp/wp-cli.phar"
	else
		echo "Missing WP-CLI. Set WP_CLI=/path/to/wp or install wp on PATH." >&2
		exit 2
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
	exit 2
fi

export WP_PATH
export WP_CLI
export WP_CLI_PHP
export WP_CLI_ERROR_REPORTING
export WP_CLI_MYSQL_SOCKET
export WP_DB_SOCKET

log "Core root: $CORE_ROOT"
log "Adapter root: $ADAPTER_ROOT"
log "Toolkit root: $TOOLKIT_ROOT"
log "WordPress root: $WP_PATH"

check_clean "Core" "$CORE_ROOT"
check_clean "Adapter" "$ADAPTER_ROOT"
check_clean "Toolkit" "$TOOLKIT_ROOT"

run_in_repo "Core" "$CORE_ROOT" composer validate --no-check-publish
run_in_repo "Core" "$CORE_ROOT" composer test:all
run_in_repo "Core" "$CORE_ROOT" composer smoke:wp
run_in_repo "Core" "$CORE_ROOT" composer release:verify
if [[ "$SKIP_PACKAGING" != "1" ]]; then
	run_in_repo "Core" "$CORE_ROOT" composer package:release
fi

run_in_repo "Adapter" "$ADAPTER_ROOT" composer test:all
run_in_repo "Adapter" "$ADAPTER_ROOT" composer smoke:wp
run_in_repo "Adapter" "$ADAPTER_ROOT" composer release:verify
if [[ "$SKIP_PACKAGING" != "1" ]]; then
	run_in_repo "Adapter" "$ADAPTER_ROOT" composer package:release
	run_in_repo "Adapter" "$ADAPTER_ROOT" composer smoke:package-install
fi

if [[ "$SKIP_ADAPTER_FIXTURE" != "1" ]]; then
	activate_plugin_if_possible npcink-ai-client-adapter
	run_in_repo "Adapter" "$ADAPTER_ROOT" env \
		MAA_ADAPTER_ACCEPTANCE_PROFILE=local \
		MAA_ADAPTER_ACCEPTANCE_INSECURE_LOCAL_TLS=1 \
		MAA_ADAPTER_FIXTURE_ALLOW_COMMIT=1 \
		composer accept:local-ai-client-fixture
fi

run_in_repo "Toolkit" "$TOOLKIT_ROOT" composer test:all
run_in_repo "Toolkit" "$TOOLKIT_ROOT" composer analyse:phpstan
run_in_repo "Toolkit" "$TOOLKIT_ROOT" composer release:verify
run_in_repo "Toolkit" "$TOOLKIT_ROOT" composer smoke:wp

log "Cross-repository release acceptance: ok"
