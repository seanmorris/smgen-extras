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
PHPDOC_TARBALL="$(cd "${ROOT_DIR}/packages/smgen-phpdoc" && npm pack --silent --ignore-scripts --pack-destination "${TEMP_ROOT}" | tail -n 1)"
npm install --prefix "${SITE}" --ignore-scripts --no-package-lock --no-save \
	"${TEMP_ROOT}/${DATA_TARBALL}" \
	"${TEMP_ROOT}/${STORE_TARBALL}" \
	"${TEMP_ROOT}/${PHPDOC_TARBALL}" >/dev/null

"${SITE}/node_modules/.bin/smgen-data-pages" validate --root "${SITE}" >/dev/null
"${SITE}/node_modules/.bin/smgen-store" validate --root "${SITE}" pages >/dev/null
"${SITE}/node_modules/.bin/smgen-phpdoc" generate --root "${SITE}" --pages pages >/dev/null
"${SITE}/node_modules/.bin/smgen-phpdoc" check --root "${SITE}" --pages pages >/dev/null
"${SITE}/node_modules/.bin/smgen-phpdoc" clean --root "${SITE}" --pages pages >/dev/null

(
	cd "${SITE}"
	SMGEN_ENGINE=php SMG_SEARCH="${ROOT_DIR}/node_modules/.bin/smgen-search" "${SMGEN_BIN}" build >/dev/null
)

test -f "${SITE}/docs/products/smgen-mug.html"
test -f "${SITE}/docs/products/index.html"
test -f "${SITE}/docs/smgen-store/store.css"
test -f "${SITE}/docs/api/index.html"
test -f "${SITE}/docs/api/classes/store/product.html"
test -f "${SITE}/docs/search.bin"
test -f "${SITE}/docs/llms.txt"
test -f "${SITE}/docs/sitemap.xml"
grep -Fq 'https://example.com/checkout/smgen-mug' "${SITE}/docs/products/smgen-mug.html"
grep -Fq 'https://schema.org' "${SITE}/docs/products/smgen-mug.html"
grep -Fq '/shop/smgen-store/store.css' "${SITE}/docs/products/smgen-mug.html"
grep -Fq 'Store\\Product' "${SITE}/docs/api/classes/store/product.html"
grep -Fq 'https://schema.org' "${SITE}/docs/api/classes/store/product.html"
grep -Fq '/shop/api/' "${SITE}/docs/llms.txt"
grep -Fq '/shop/api/classes/store/product.html' "${SITE}/docs/llms.txt"
grep -Fq '/shop/api/classes/store/product.html' "${SITE}/docs/sitemap.xml"
INDEX_FILE="${SITE}/docs/search.bin" "${ROOT_DIR}/node_modules/.bin/smgen-search" search 'catalog product' | grep -Fq 'api/classes/store/product'
if grep -Eq 'smgen-phpdoc[^<]*(\.css|\.js)' "${SITE}/docs/api/classes/store/product.html"; then
	echo "PHP documentation unexpectedly loaded a browser asset." >&2
	exit 1
fi
if grep -Eq 'js\.stripe\.com|paypal\.com/sdk|paypalobjects\.com' "${SITE}/docs/products/smgen-mug.html"; then
	echo "Link-only product unexpectedly loaded provider JavaScript." >&2
	exit 1
fi
test ! -e "${SITE}/pages/.smgen-generated"

echo "SMGen plugin integration passed."
