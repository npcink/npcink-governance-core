#!/usr/bin/env bash
set -euo pipefail

CORE_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DEFAULT_REPO_PARENT="$(dirname "$CORE_ROOT")"

ADAPTER_ROOT="${NPCINK_AI_CLIENT_ADAPTER_ROOT:-$DEFAULT_REPO_PARENT/npcink-ai-client-adapter}"
TOOLKIT_ROOT="${NPCINK_ABILITIES_TOOLKIT_ROOT:-$DEFAULT_REPO_PARENT/npcink-abilities-toolkit}"

EXPECTED_CORE_VERSION="${EXPECTED_CORE_VERSION:-0.1.0}"
EXPECTED_ADAPTER_VERSION="${EXPECTED_ADAPTER_VERSION:-0.3.2}"
EXPECTED_TOOLKIT_VERSION="${EXPECTED_TOOLKIT_VERSION:-0.5.2}"

ALLOW_DIRTY=0
REQUIRE_TAG_READY=0

usage() {
	cat <<USAGE
Usage: scripts/check-release-candidate-version-matrix.sh [--allow-dirty] [--require-tag-ready]

Audits the current Core + Adapter + Toolkit release-candidate version matrix.
It verifies plugin header versions, version constants, readme Stable tags, and
reports whether the conventional release tag for each repository can point at
the current HEAD without retagging history.

Environment overrides:
  NPCINK_AI_CLIENT_ADAPTER_ROOT    Path to npcink-ai-client-adapter.
  NPCINK_ABILITIES_TOOLKIT_ROOT    Path to npcink-abilities-toolkit.
  EXPECTED_CORE_VERSION           Default: 0.1.0.
  EXPECTED_ADAPTER_VERSION        Default: 0.3.2.
  EXPECTED_TOOLKIT_VERSION        Default: 0.5.2.

Options:
  --allow-dirty        Allow dirty worktrees for local diagnostics.
  --require-tag-ready  Fail when a conventional release tag already points at
                       a different commit.
  -h, --help           Show this help.
USAGE
}

while [[ $# -gt 0 ]]; do
	case "$1" in
		--allow-dirty)
			ALLOW_DIRTY=1
			shift
			;;
		--require-tag-ready)
			REQUIRE_TAG_READY=1
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

require_repo() {
	local label="$1"
	local root="$2"
	if [[ ! -d "$root/.git" ]]; then
		echo "$label repository is missing: $root" >&2
		exit 2
	fi
}

read_header_version() {
	local plugin_file="$1"
	sed -nE 's/^[[:space:]]+\*[[:space:]]Version:[[:space:]]*([^[:space:]]+).*/\1/p' "$plugin_file" | head -n 1
}

read_constant_version() {
	local plugin_file="$1"
	local constant_name="$2"
	sed -nE "s/^define\\( '$constant_name', '([^']+)' \\);/\\1/p" "$plugin_file" | head -n 1
}

read_stable_tag() {
	local readme_file="$1"
	sed -nE 's/^Stable tag:[[:space:]]*([^[:space:]]+).*/\1/p' "$readme_file" | head -n 1
}

check_clean() {
	local label="$1"
	local root="$2"
	local status
	status="$(git -C "$root" status --short)"
	if [[ "$ALLOW_DIRTY" != "1" && -n "$status" ]]; then
		echo "$label working tree is not clean." >&2
		echo "$status" >&2
		exit 1
	fi
}

tag_status() {
	local root="$1"
	local tag="$2"
	local head
	local tag_commit

	head="$(git -C "$root" rev-parse HEAD)"
	if ! tag_commit="$(git -C "$root" rev-parse --verify "$tag" 2>/dev/null)"; then
		echo "available"
		return
	fi

	if [[ "$tag_commit" == "$head" ]]; then
		echo "points_at_head"
		return
	fi

	echo "exists_at_other_commit:$tag_commit"
}

audit_repo() {
	local label="$1"
	local root="$2"
	local plugin_file="$3"
	local constant_name="$4"
	local expected_version="$5"
	local conventional_tag="$6"

	local header_version
	local constant_version
	local stable_tag
	local head
	local status

	header_version="$(read_header_version "$root/$plugin_file")"
	constant_version="$(read_constant_version "$root/$plugin_file" "$constant_name")"
	stable_tag="$(read_stable_tag "$root/readme.txt")"
	head="$(git -C "$root" rev-parse HEAD)"
	status="$(tag_status "$root" "$conventional_tag")"

	if [[ "$header_version" != "$expected_version" || "$constant_version" != "$expected_version" || "$stable_tag" != "$expected_version" ]]; then
		echo "$label version mismatch:" >&2
		echo "  expected: $expected_version" >&2
		echo "  header:   $header_version" >&2
		echo "  constant: $constant_version" >&2
		echo "  stable:   $stable_tag" >&2
		exit 1
	fi

	printf '%-8s version=%-6s head=%s tag=%s tag_status=%s\n' "$label" "$expected_version" "$head" "$conventional_tag" "$status"

	if [[ "$REQUIRE_TAG_READY" == "1" && "$status" == exists_at_other_commit:* ]]; then
		echo "$label tag $conventional_tag already exists at another commit; bump the plugin version or use an explicit RC snapshot tag." >&2
		exit 1
	fi
}

require_repo "Core" "$CORE_ROOT"
require_repo "Adapter" "$ADAPTER_ROOT"
require_repo "Toolkit" "$TOOLKIT_ROOT"

check_clean "Core" "$CORE_ROOT"
check_clean "Adapter" "$ADAPTER_ROOT"
check_clean "Toolkit" "$TOOLKIT_ROOT"

echo "Release candidate version matrix"
audit_repo "Core" "$CORE_ROOT" "npcink-governance-core.php" "NPCINK_GOVERNANCE_CORE_VERSION" "$EXPECTED_CORE_VERSION" "v$EXPECTED_CORE_VERSION"
audit_repo "Adapter" "$ADAPTER_ROOT" "npcink-ai-client-adapter.php" "NPCINK_OPENCLAW_ADAPTER_VERSION" "$EXPECTED_ADAPTER_VERSION" "v$EXPECTED_ADAPTER_VERSION"
audit_repo "Toolkit" "$TOOLKIT_ROOT" "npcink-abilities-toolkit.php" "NPCINK_ABILITIES_TOOLKIT_VERSION" "$EXPECTED_TOOLKIT_VERSION" "$EXPECTED_TOOLKIT_VERSION"

cat <<'NOTE'

Tag preparation rule:
- If tag_status=available, the conventional release tag can be created after
  the full release gate passes.
- If tag_status=points_at_head, the conventional release tag already matches
  the current candidate.
- If tag_status=exists_at_other_commit, do not retag history. Either bump that
  plugin version or create a clearly named RC snapshot tag outside the
  conventional release-tag namespace.
NOTE

echo "Release candidate version matrix: ok"
