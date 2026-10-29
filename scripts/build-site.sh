#!/usr/bin/env bash

set -Eeuo pipefail

ROOT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)"
SMGEN_COMMAND="${SMGEN_BIN:-}"

if [[ -z "${SMGEN_COMMAND}" && -x "${ROOT_DIR}/node_modules/.bin/smgen" ]]; then
	SMGEN_COMMAND="${ROOT_DIR}/node_modules/.bin/smgen"
elif [[ -z "${SMGEN_COMMAND}" && -x "${ROOT_DIR}/../static-gen/smgen.sh" ]]; then
	SMGEN_COMMAND="${ROOT_DIR}/../static-gen/smgen.sh"
elif [[ -z "${SMGEN_COMMAND}" && -x "${ROOT_DIR}/../smgen/smgen.sh" ]]; then
	SMGEN_COMMAND="${ROOT_DIR}/../smgen/smgen.sh"
elif [[ -z "${SMGEN_COMMAND}" && -x /app/smgen.sh ]]; then
	SMGEN_COMMAND=/app/smgen.sh
elif [[ -z "${SMGEN_COMMAND}" ]] && command -v smgen >/dev/null 2>&1; then
	SMGEN_COMMAND="$(command -v smgen)"
fi

if [[ -z "${SMGEN_COMMAND}" || ! -x "${SMGEN_COMMAND}" ]]; then
	echo "Set SMGEN_BIN to an executable SMGen checkout." >&2
	exit 1
fi

SMGEN_REAL_PATH="$(php -r '$path = realpath($argv[1]); echo $path === false ? $argv[1] : $path;' "${SMGEN_COMMAND}")"
SMGEN_PLUGIN_REGISTRY="$(dirname -- "${SMGEN_REAL_PATH}")/src/Smgen/PluginRegistry.php"
if [[ ! -f "${SMGEN_PLUGIN_REGISTRY}" ]] || ! grep -q "smgen/v1" "${SMGEN_PLUGIN_REGISTRY}"; then
	echo "The selected SMGen command does not support versioned plugins: ${SMGEN_COMMAND}" >&2
	echo "Update SMGen or set SMGEN_BIN to a current smgen.sh checkout." >&2
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
	for example in dispatch data-pages store blog; do
		(
			cd "${ROOT_DIR}/examples/${example}"
			BASE_URL="${SITE_BASE_URL%/}/examples/${example}" "${SMGEN_COMMAND}" build
		)
	done
fi

exec "${SMGEN_COMMAND}" "${COMMAND}"
