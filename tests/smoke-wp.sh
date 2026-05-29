#!/usr/bin/env bash
set -euo pipefail

WP_ROOT="${WP_ROOT:-/Users/muze/Local Sites/magick-ai/app/public}"
WRAPPER="${MAGICK_AI_ROOT_WRAPPER:-/Users/muze/gitee/magick-ai-root/scripts/local-wp.sh}"

if [[ ! -x "${WRAPPER}" && ! -f "${WRAPPER}" ]]; then
	echo "Missing LocalWP wrapper: ${WRAPPER}" >&2
	exit 2
fi

bash "${WRAPPER}" --wp-root "${WP_ROOT}" wp eval-file "$(cd "$(dirname "$0")" && pwd)/smoke-wp.php"

