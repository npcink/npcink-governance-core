#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
TOOLKIT_ROOT="${NPCINK_CORE_M4_TOOLKIT_ROOT:-}"
CORE_ZIP="${NPCINK_CORE_M4_PACKAGE:-}"
PROJECT_NAME="${NPCINK_CORE_M4_PROJECT_NAME:-npcink_core_wordpress_smoke}"
HTTP_PORT="${NPCINK_CORE_M4_HTTP_PORT:-8931}"
WORDPRESS_VERSION="${NPCINK_CORE_M4_WORDPRESS_VERSION:-7.0}"
PHP_VERSION="${NPCINK_CORE_M4_PHP_VERSION:-8.0}"
SMOKE_LABEL="${NPCINK_CORE_M4_SMOKE_LABEL:-Minimum}"

if [[ -z "$TOOLKIT_ROOT" || ! -f "$TOOLKIT_ROOT/scripts/official-stack-e2e.sh" ]]; then
	echo "NPCINK_CORE_M4_TOOLKIT_ROOT must point to a complete Toolkit checkout." >&2
	exit 2
fi

if [[ -z "$CORE_ZIP" || ! -f "$CORE_ZIP" ]]; then
	echo "NPCINK_CORE_M4_PACKAGE must point to the Core release ZIP." >&2
	exit 2
fi

for command_name in curl docker unzip; do
	if ! command -v "$command_name" >/dev/null 2>&1; then
		echo "Missing required command: $command_name" >&2
		exit 127
	fi
done

COMPOSE_FILE="$TOOLKIT_ROOT/docker-compose.official-stack.yml"
CACHE_DIR="$TOOLKIT_ROOT/build/official-stack-cache"
WORDPRESS_CACHE="$CACHE_DIR/wordpress-$WORDPRESS_VERSION"
WORDPRESS_ZIP="$WORDPRESS_CACHE/wordpress-$WORDPRESS_VERSION.zip"
WORDPRESS_SOURCE="$WORDPRESS_CACHE/wordpress"
CORE_CACHE_ZIP="$CACHE_DIR/npcink-governance-core.zip"
CORE_SMOKE_FILE="$CACHE_DIR/npcink-governance-core-smoke-wp.php"
SMOKE_LOG="$(mktemp)"

compose() {
	COMPOSE_PROJECT_NAME="$PROJECT_NAME" \
	OFFICIAL_STACK_HTTP_PORT="$HTTP_PORT" \
	OFFICIAL_STACK_WORDPRESS_IMAGE="wordpress:php$PHP_VERSION-apache" \
	OFFICIAL_STACK_WPCLI_IMAGE="wordpress:cli-php$PHP_VERSION" \
		docker compose -f "$COMPOSE_FILE" "$@"
}

cleanup() {
	compose down -v --remove-orphans >/dev/null 2>&1 || true
	rm -f "$SMOKE_LOG"
}
trap cleanup EXIT

if [[ ! -f "$WORDPRESS_SOURCE/wp-settings.php" ]]; then
	mkdir -p "$WORDPRESS_CACHE"
	curl --fail --location --retry 3 --connect-timeout 15 --max-time 180 \
		"https://wordpress.org/wordpress-$WORDPRESS_VERSION.zip" \
		--output "$WORDPRESS_ZIP"
	unzip -tq "$WORDPRESS_ZIP" >/dev/null
	rm -rf "$WORDPRESS_SOURCE"
	unzip -q "$WORDPRESS_ZIP" -d "$WORDPRESS_CACHE"
fi

mkdir -p "$CACHE_DIR"
cp "$CORE_ZIP" "$CORE_CACHE_ZIP"
cp "$ROOT_DIR/tests/smoke-wp.php" "$CORE_SMOKE_FILE"

OFFICIAL_STACK_PROJECT_NAME="$PROJECT_NAME" \
OFFICIAL_STACK_HTTP_PORT="$HTTP_PORT" \
OFFICIAL_STACK_WORDPRESS_IMAGE="wordpress:php$PHP_VERSION-apache" \
OFFICIAL_STACK_WPCLI_IMAGE="wordpress:cli-php$PHP_VERSION" \
OFFICIAL_STACK_WP_URL="http://localhost:$HTTP_PORT" \
OFFICIAL_STACK_WORDPRESS_VERSION="$WORDPRESS_VERSION" \
OFFICIAL_STACK_WORDPRESS_SOURCE_DIR="/official-stack-cache/wordpress-$WORDPRESS_VERSION/wordpress" \
OFFICIAL_STACK_INSTALL_AI=0 \
OFFICIAL_STACK_INSTALL_MCP=0 \
OFFICIAL_STACK_RUN_MCP_HTTP_PROBE=0 \
	bash "$TOOLKIT_ROOT/scripts/official-stack-e2e.sh" --fresh --setup-only

compose run --rm cli --allow-root plugin install \
	/official-stack-cache/npcink-governance-core.zip --force --activate >/dev/null

runtime_wordpress="$(compose run --rm cli --allow-root core version | tr -d '\r')"
runtime_php="$(compose run --rm --entrypoint php cli -r 'echo PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION;' | tr -d '\r')"
if [[ "$runtime_wordpress" != "$WORDPRESS_VERSION" || "$runtime_php" != "$PHP_VERSION" ]]; then
	echo "Expected WordPress $WORDPRESS_VERSION/PHP $PHP_VERSION, found WordPress ${runtime_wordpress:-unknown}/PHP ${runtime_php:-unknown}." >&2
	exit 1
fi

installed_version="$(compose run --rm cli --allow-root plugin get npcink-governance-core --field=version | tr -d '\r')"
if [[ "$installed_version" != "0.2.0" ]]; then
	echo "Expected packaged Core 0.2.0, found ${installed_version:-unknown}." >&2
	exit 1
fi

if ! compose run --rm \
		-e NPCINK_ABILITIES_TOOLKIT_PATH=/var/www/html/wp-content/plugins/npcink-abilities-toolkit \
		-e NPCINK_GOVERNANCE_CORE_SMOKE_PURGE=1 \
		cli --allow-root eval-file /official-stack-cache/npcink-governance-core-smoke-wp.php > "$SMOKE_LOG" 2>&1; then
	sed -n '1,8000p' "$SMOKE_LOG" >&2
	exit 1
fi
smoke_output="$(<"$SMOKE_LOG")"
printf '%s\n' "$smoke_output"

if ! printf '%s\n' "$smoke_output" | grep -Fx 'npcink-governance-core WordPress smoke: ok' >/dev/null; then
	echo "Core WordPress smoke did not report completion." >&2
	exit 1
fi

assertions="$(printf '%s\n' "$smoke_output" | grep -c '^\[ok\] ')"
if [[ ! "$assertions" =~ ^[1-9][0-9]*$ ]]; then
	echo "Core WordPress smoke did not report assertions." >&2
	exit 1
fi

echo "$SMOKE_LABEL packaged Core smoke passed on WordPress $WORDPRESS_VERSION with PHP $PHP_VERSION."
echo "Smoke OK: $assertions assertions"
