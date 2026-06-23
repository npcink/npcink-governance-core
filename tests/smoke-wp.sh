#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
WP_PATH="${WP_PATH:-/Users/muze/Local Sites/magick-ai/app/public}"
WP_CLI_BIN="${WP_CLI:-}"
WP_CLI_PHP="${WP_CLI_PHP:-}"
WP_CLI_PHP_ARGS="${WP_CLI_PHP_ARGS:-}"
WP_CLI_ERROR_REPORTING="${WP_CLI_ERROR_REPORTING:-8191}"
WP_CLI_MYSQL_SOCKET="${WP_CLI_MYSQL_SOCKET:-}"

smoke_preflight_note() {
	echo "[smoke:preflight] $*"
}

smoke_preflight_fail() {
	echo "[smoke:preflight:fail] $*" >&2
	exit 2
}

smoke_path_state() {
	local path="$1"

	if [[ -L "$path" ]]; then
		local target
		target="$(readlink "$path" || true)"
		if [[ -e "$path" ]]; then
			echo "symlink -> $target"
		else
			echo "broken symlink -> $target"
		fi
		return
	fi

	if [[ -d "$path" ]]; then
		echo "directory"
		return
	fi

	if [[ -f "$path" ]]; then
		echo "file"
		return
	fi

	if [[ -S "$path" ]]; then
		echo "socket"
		return
	fi

	echo "missing"
}

if [[ -z "$WP_CLI_BIN" ]]; then
	if [[ -f /tmp/wp-cli.phar ]]; then
		WP_CLI_BIN="/tmp/wp-cli.phar"
	elif command -v wp >/dev/null 2>&1; then
		WP_CLI_BIN="$(command -v wp)"
	else
		smoke_preflight_fail "environment: missing WP-CLI. Set WP_CLI=/path/to/wp-cli.phar or install wp on PATH."
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
	smoke_preflight_fail "environment: missing PHP for WP-CLI. Set WP_CLI_PHP=/path/to/php."
fi

if [[ -z "$WP_CLI_MYSQL_SOCKET" ]]; then
	default_socket="$HOME/Library/Application Support/Local/run/NPb24Zg9g/mysql/mysqld.sock"
	if [[ -S "$default_socket" ]]; then
		WP_CLI_MYSQL_SOCKET="$default_socket"
	fi
fi

if [[ -z "$WP_PATH" ]]; then
	smoke_preflight_fail "environment: WP_PATH is empty."
fi

if [[ ! -d "$WP_PATH" ]]; then
	smoke_preflight_fail "environment: WP_PATH does not exist or is not a directory: $WP_PATH"
fi

if [[ ! -f "$WP_PATH/wp-config.php" ]]; then
	smoke_preflight_fail "environment: WP_PATH does not contain wp-config.php: $WP_PATH"
fi

if [[ ! -d "$WP_PATH/wp-content/plugins" ]]; then
	smoke_preflight_fail "environment: WP_PATH does not contain wp-content/plugins: $WP_PATH"
fi

TOOLKIT_SOURCE_PATH="${NPCINK_ABILITIES_TOOLKIT_PATH:-}"
if [[ -z "$TOOLKIT_SOURCE_PATH" ]]; then
	if [[ -d "$(dirname "$ROOT_DIR")/npcink-abilities-toolkit" ]]; then
		TOOLKIT_SOURCE_PATH="$(dirname "$ROOT_DIR")/npcink-abilities-toolkit"
	else
		TOOLKIT_SOURCE_PATH="/Users/muze/gitee/npcink-abilities-toolkit"
	fi
fi

plugin_link="$WP_PATH/wp-content/plugins/npcink-governance-core"
toolkit_plugin="$WP_PATH/wp-content/plugins/npcink-abilities-toolkit/npcink-abilities-toolkit.php"
toolkit_fixture="$TOOLKIT_SOURCE_PATH/tests/fixtures/agent-workflow-replay.json"

smoke_preflight_note "ROOT_DIR=$ROOT_DIR"
smoke_preflight_note "WP_PATH=$WP_PATH ($(smoke_path_state "$WP_PATH"))"
smoke_preflight_note "WP_CLI=$WP_CLI_BIN ($(smoke_path_state "$WP_CLI_BIN"))"
smoke_preflight_note "WP_CLI_PHP=$WP_CLI_PHP ($(smoke_path_state "$WP_CLI_PHP"))"
smoke_preflight_note "WP_CLI_MYSQL_SOCKET=${WP_CLI_MYSQL_SOCKET:-<not set>} ($(smoke_path_state "${WP_CLI_MYSQL_SOCKET:-}"))"
smoke_preflight_note "NPCINK_ABILITIES_TOOLKIT_PATH=${NPCINK_ABILITIES_TOOLKIT_PATH:-<not set>}; diagnostic source=$TOOLKIT_SOURCE_PATH ($(smoke_path_state "$TOOLKIT_SOURCE_PATH"))"
smoke_preflight_note "Core plugin mount=$plugin_link ($(smoke_path_state "$plugin_link"))"
smoke_preflight_note "Toolkit plugin file=$toolkit_plugin ($(smoke_path_state "$toolkit_plugin"))"
smoke_preflight_note "Toolkit replay fixture=$toolkit_fixture ($(smoke_path_state "$toolkit_fixture"))"

if [[ ! -f "$toolkit_plugin" ]]; then
	smoke_preflight_fail "toolkit: npcink-abilities-toolkit plugin file is missing in the LocalWP plugins directory: $toolkit_plugin"
fi

wp_args=()
if [[ -n "$WP_PATH" ]]; then
	wp_args+=(--path="$WP_PATH")
fi

run_wp() {
	if [[ "$WP_CLI_BIN" == *.phar ]]; then
		php_args=()
		php_args+=("-d" "display_errors=0")
		if [[ -n "$WP_CLI_ERROR_REPORTING" ]]; then
			php_args+=("-d" "error_reporting=$WP_CLI_ERROR_REPORTING")
		fi
		if [[ -n "$WP_CLI_MYSQL_SOCKET" ]]; then
			php_args+=("-d" "mysqli.default_socket=$WP_CLI_MYSQL_SOCKET")
			php_args+=("-d" "pdo_mysql.default_socket=$WP_CLI_MYSQL_SOCKET")
		fi
		if [[ -n "$WP_CLI_PHP_ARGS" ]]; then
			extra_php_args=()
			read -r -a extra_php_args <<< "$WP_CLI_PHP_ARGS"
			php_args+=("${extra_php_args[@]}")
		fi
		"$WP_CLI_PHP" "${php_args[@]}" "$WP_CLI_BIN" "${wp_args[@]}" "$@"
		return
	fi

	"$WP_CLI_BIN" "${wp_args[@]}" "$@"
}

if ! run_wp core is-installed >/dev/null; then
	smoke_preflight_fail "environment: WP-CLI could not confirm a WordPress installation at WP_PATH=$WP_PATH"
fi

if [[ -L "$plugin_link" && ! -e "$plugin_link" ]]; then
	smoke_preflight_fail "environment: Core plugin symlink is broken: $plugin_link -> $(readlink "$plugin_link" || true)"
fi

if [[ ! -e "$plugin_link" ]]; then
	ln -s "$ROOT_DIR" "$plugin_link"
	smoke_preflight_note "Created Core plugin symlink: $plugin_link -> $ROOT_DIR"
fi

if [[ -e "$plugin_link" && ! -L "$plugin_link" ]]; then
	smoke_preflight_fail "environment: Core plugin path exists but is not the expected symlink: $plugin_link"
fi

root_resolved="$(cd "$ROOT_DIR" && pwd -P)"
plugin_resolved="$(cd "$plugin_link" && pwd -P)"
if [[ "$plugin_resolved" != "$root_resolved" ]]; then
	smoke_preflight_fail "environment: Core plugin symlink resolves to $plugin_resolved, expected $root_resolved"
fi

run_wp eval-file "$ROOT_DIR/tests/smoke-wp.php"
