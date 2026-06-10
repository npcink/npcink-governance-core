#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PLUGIN_SLUG="npcink-governance-core"
PACKAGE_DIR="$ROOT_DIR/build/$PLUGIN_SLUG"
ASSETS_DIR="$ROOT_DIR/sj/exports/wordpress-org"

VERSION=""
SVN_DIR="${WPORG_SVN_DIR:-}"
APPLY=0
COMMIT=0
SYNC_ASSETS=0

usage() {
	cat <<USAGE
Usage: scripts/sync-wporg-svn.sh --version <version> --svn-dir <path> [--apply] [--commit] [--assets]

Prepares a WordPress.org SVN working copy from build/$PLUGIN_SLUG.
Default mode is dry-run and does not modify the SVN checkout.

Options:
  --version <version>  Release tag to create under tags/<version>.
  --svn-dir <path>    Existing checkout of https://plugins.svn.wordpress.org/$PLUGIN_SLUG.
  --apply             Modify the SVN working copy.
  --commit            Commit the staged SVN release after applying it.
  --assets            Also sync sj/exports/wordpress-org into SVN assets/.
  -h, --help          Show this help.
USAGE
}

while [[ $# -gt 0 ]]; do
	case "$1" in
		--version)
			VERSION="${2:-}"
			if [[ -z "$VERSION" ]]; then
				echo "Missing value for --version." >&2
				exit 2
			fi
			shift 2
			;;
		--svn-dir)
			SVN_DIR="${2:-}"
			if [[ -z "$SVN_DIR" ]]; then
				echo "Missing value for --svn-dir." >&2
				exit 2
			fi
			shift 2
			;;
		--apply)
			APPLY=1
			shift
			;;
		--commit)
			APPLY=1
			COMMIT=1
			shift
			;;
		--assets)
			SYNC_ASSETS=1
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

require_command() {
	if ! command -v "$1" >/dev/null 2>&1; then
		echo "Missing required command: $1" >&2
		exit 2
	fi
}

require_command rsync
require_command svn

if [[ -z "$VERSION" || -z "$SVN_DIR" ]]; then
	usage >&2
	exit 2
fi

SVN_DIR="$(cd "$SVN_DIR" && pwd)"
TRUNK_DIR="$SVN_DIR/trunk"
TAGS_DIR="$SVN_DIR/tags"
TAG_DIR="$TAGS_DIR/$VERSION"
SVN_ASSETS_DIR="$SVN_DIR/assets"

if [[ ! -d "$PACKAGE_DIR" ]]; then
	echo "Missing package directory: $PACKAGE_DIR" >&2
	echo "Run scripts/prepare-release.sh first." >&2
	exit 1
fi

if [[ ! -d "$TRUNK_DIR" || ! -d "$TAGS_DIR" ]]; then
	echo "SVN checkout must contain trunk/ and tags/: $SVN_DIR" >&2
	exit 1
fi

svn info "$SVN_DIR" >/dev/null

if [[ -e "$TAG_DIR" ]]; then
	echo "Tag already exists in SVN working copy: $TAG_DIR" >&2
	exit 1
fi

echo "SVN checkout: $SVN_DIR"
echo "Release tag: $VERSION"

if [[ "$APPLY" != "1" ]]; then
	echo "Dry-run: package files that would sync to trunk/"
	rsync -a --delete --dry-run "$PACKAGE_DIR/" "$TRUNK_DIR/"
	if [[ "$SYNC_ASSETS" == "1" ]]; then
		echo "Dry-run: WordPress.org assets that would sync to assets/"
		rsync -a --delete --dry-run --exclude='.gitkeep' "$ASSETS_DIR/" "$SVN_ASSETS_DIR/"
	fi
	echo "Dry-run only. Re-run with --apply to modify the SVN working copy."
	exit 0
fi

rsync -a --delete "$PACKAGE_DIR/" "$TRUNK_DIR/"

if [[ "$SYNC_ASSETS" == "1" ]]; then
	mkdir -p "$SVN_ASSETS_DIR"
	rsync -a --delete --exclude='.gitkeep' "$ASSETS_DIR/" "$SVN_ASSETS_DIR/"
fi

svn add --force "$TRUNK_DIR" >/dev/null
if [[ "$SYNC_ASSETS" == "1" ]]; then
	svn add --force "$SVN_ASSETS_DIR" >/dev/null
	find "$SVN_ASSETS_DIR" -type f -name '*.png' -exec svn propset svn:mime-type image/png {} \; >/dev/null
fi

while IFS= read -r missing_path; do
	[[ -z "$missing_path" ]] && continue
	svn rm "$missing_path" >/dev/null
done < <(svn status "$TRUNK_DIR" "$SVN_ASSETS_DIR" 2>/dev/null | awk '/^!/ { print substr($0, 9) }')

svn copy "$TRUNK_DIR" "$TAG_DIR" >/dev/null
svn add --force "$TAGS_DIR" >/dev/null

echo "SVN status after sync:"
svn status "$SVN_DIR"

if [[ "$COMMIT" != "1" ]]; then
	echo "SVN working copy prepared but not committed."
	echo "Review with: svn status \"$SVN_DIR\""
	echo "Commit with: svn commit \"$SVN_DIR\" -m \"Release $PLUGIN_SLUG $VERSION\""
	exit 0
fi

svn commit "$SVN_DIR" -m "Release $PLUGIN_SLUG $VERSION"
