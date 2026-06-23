#!/usr/bin/env sh
set -eu

LAB_PATH="${NPCINK_EVAL_LAB_PATH:-}"
if [ -z "$LAB_PATH" ]; then
	LAB_PATH="$(dirname "$PWD")/npcink-eval-lab"
fi

if [ ! -d "$LAB_PATH" ]; then
	echo "Npcink eval-lab not found: $LAB_PATH" >&2
	echo "Set NPCINK_EVAL_LAB_PATH to the local npcink-eval-lab checkout." >&2
	exit 1
fi

if [ ! -f "$LAB_PATH/composer.json" ]; then
	echo "Npcink eval-lab composer.json not found in: $LAB_PATH" >&2
	exit 1
fi

cd "$LAB_PATH"
composer eval:task -- "$@"
