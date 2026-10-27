#!/usr/bin/env bash

set -Eeuo pipefail

ROOT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)"
SMGEN_COMMAND="${SMGEN_BIN:-}"

if [[ -z "${SMGEN_COMMAND}" && -x "${ROOT_DIR}/node_modules/.bin/smgen" ]]; then
	SMGEN_COMMAND="${ROOT_DIR}/node_modules/.bin/smgen"
elif [[ -z "${SMGEN_COMMAND}" && -x /app/smgen.sh ]]; then
	SMGEN_COMMAND=/app/smgen.sh
elif [[ -z "${SMGEN_COMMAND}" ]] && command -v smgen >/dev/null 2>&1; then
	SMGEN_COMMAND="$(command -v smgen)"
fi

if [[ -z "${SMGEN_COMMAND}" || ! -x "${SMGEN_COMMAND}" ]]; then
	echo "Set SMGEN_BIN to an executable SMGen checkout." >&2
	exit 1
fi

cd "${ROOT_DIR}"
COMMAND="${1:-build}"

if [[ "${COMMAND}" == build || "${COMMAND}" == watch ]]; then
	EXAMPLE_OUTPUT="${ROOT_DIR}/static/examples"
	if [[ -L "${EXAMPLE_OUTPUT}" ]]; then
		echo "Refusing to replace symbolic-link example output: ${EXAMPLE_OUTPUT}" >&2
		exit 1
	fi
	rm -rf -- "${EXAMPLE_OUTPUT}"
	SITE_BASE_URL="${BASE_URL:-http://localhost:8200}"
	for example in phpdoc data-pages store; do
		(
			cd "${ROOT_DIR}/examples/${example}"
			BASE_URL="${SITE_BASE_URL%/}/examples/${example}" "${SMGEN_COMMAND}" build
		)
	done
fi

exec "${SMGEN_COMMAND}" "${COMMAND}"
