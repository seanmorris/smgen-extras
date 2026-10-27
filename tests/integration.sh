#!/usr/bin/env bash

set -Eeuo pipefail

ROOT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)"
SMGEN_BIN="${SMGEN_BIN:-/app/smgen.sh}"

if [[ ! -x "${SMGEN_BIN}" ]]; then
	echo "Set SMGEN_BIN to an executable SMGen checkout." >&2
	exit 1
fi

SMGEN_ROOT="$(cd -- "$(dirname -- "${SMGEN_BIN}")" && pwd)"
TEMP_ROOT="$(mktemp -d)"
SITE="${TEMP_ROOT}/site"

trap 'rm -rf -- "${TEMP_ROOT}"' EXIT

mkdir -p "${SITE}/static"
cp -Rp "${ROOT_DIR}/tests/fixtures/store-site/." "${SITE}/"
cp -Rp "${SMGEN_ROOT}/templates/." "${SITE}/templates/"
cp -Rp "${SMGEN_ROOT}/static/." "${SITE}/static/"
printf '%s\n' '{"private":true}' > "${SITE}/package.json"
DATA_TARBALL="$(cd "${ROOT_DIR}/packages/smgen-data-pages" && npm pack --silent --ignore-scripts --pack-destination "${TEMP_ROOT}" | tail -n 1)"
STORE_TARBALL="$(cd "${ROOT_DIR}/packages/smgen-store" && npm pack --silent --ignore-scripts --pack-destination "${TEMP_ROOT}" | tail -n 1)"
npm install --prefix "${SITE}" --ignore-scripts --no-package-lock --no-save \
	"${TEMP_ROOT}/${DATA_TARBALL}" \
	"${TEMP_ROOT}/${STORE_TARBALL}" >/dev/null

"${SITE}/node_modules/.bin/smgen-data-pages" validate --root "${SITE}" >/dev/null
"${SITE}/node_modules/.bin/smgen-store" validate --root "${SITE}" pages >/dev/null

(
	cd "${SITE}"
	SMGEN_ENGINE=php "${SMGEN_BIN}" build >/dev/null
)

test -f "${SITE}/docs/products/smgen-mug.html"
test -f "${SITE}/docs/products/index.html"
test -f "${SITE}/docs/smgen-store/store.css"
grep -Fq 'https://example.com/checkout/smgen-mug' "${SITE}/docs/products/smgen-mug.html"
grep -Fq 'https://schema.org' "${SITE}/docs/products/smgen-mug.html"
grep -Fq '/shop/smgen-store/store.css' "${SITE}/docs/products/smgen-mug.html"
if grep -Eq 'js\.stripe\.com|paypal\.com/sdk|paypalobjects\.com' "${SITE}/docs/products/smgen-mug.html"; then
	echo "Link-only product unexpectedly loaded provider JavaScript." >&2
	exit 1
fi
test ! -e "${SITE}/pages/.smgen-generated"

echo "SMGen plugin integration passed."
