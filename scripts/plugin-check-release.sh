#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
WP_PATH="${WP_PATH:-/Users/muze/Local Sites/magick-ai/app/public}"
PLUGIN_ROOT="${PLUGIN_ROOT:-$ROOT_DIR}"
TARGET="${WP_PLUGIN_CHECK_TARGET:-npcink-governance-core/npcink-governance-core.php}"
PLUGIN_LINK="$WP_PATH/wp-content/plugins/npcink-governance-core"

if [[ ! -d "$PLUGIN_ROOT" ]]; then
	echo "Plugin root does not exist: $PLUGIN_ROOT" >&2
	exit 2
fi
if [[ ! -e "$PLUGIN_LINK" ]]; then
	ln -s "$PLUGIN_ROOT" "$PLUGIN_LINK"
fi
if [[ ! -e "$PLUGIN_LINK" ]]; then
	echo "Could not mount plugin at $PLUGIN_LINK" >&2
	exit 2
fi

output="$(bash "$ROOT_DIR/scripts/wp-cli-local.sh" plugin check "$TARGET" \
	--format=strict-json \
	--exclude-directories=tests,.git,.github,.sisyphus,.workbuddy,vendor,node_modules,build,dist,docs,examples,sj,scripts,stubs \
	--exclude-files=.DS_Store,.gitignore,.distignore,.phpunit.result.cache,AGENTS.md,README.md,composer.json,composer.lock,phpcs.xml,phpcs.xml.dist,phpstan.neon,phpstan.neon.dist,phpunit.xml,phpunit.xml.dist)"
printf '%s\n' "$output"
if [[ "$output" == "Success: Checks complete. No errors found." ]]; then
	exit 0
fi
printf '%s' "$output" | php -r '
$findings = json_decode(stream_get_contents(STDIN), true);
if (!is_array($findings)) { fwrite(STDERR, "Plugin Check returned invalid strict JSON.\n"); exit(1); }
$blocking = array_filter($findings, static function ($finding) {
    return is_array($finding) && in_array(strtoupper((string) ($finding["type"] ?? "")), ["ERROR", "WARNING"], true);
});
if ($blocking) { fwrite(STDERR, sprintf("Plugin Check blocked release with %d finding(s).\n", count($blocking))); exit(1); }
'
