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
JSDOC_TARBALL="$(cd "${ROOT_DIR}/packages/smgen-jsdoc" && npm pack --silent --ignore-scripts --pack-destination "${TEMP_ROOT}" | tail -n 1)"
CATEGORIES_TARBALL="$(cd "${ROOT_DIR}/packages/smgen-categories" && npm pack --silent --ignore-scripts --pack-destination "${TEMP_ROOT}" | tail -n 1)"
TIMELINE_TARBALL="$(cd "${ROOT_DIR}/packages/smgen-timeline" && npm pack --silent --ignore-scripts --pack-destination "${TEMP_ROOT}" | tail -n 1)"
npm install --prefix "${SITE}" --ignore-scripts --no-package-lock --no-save \
	"${TEMP_ROOT}/${DATA_TARBALL}" \
	"${TEMP_ROOT}/${STORE_TARBALL}" \
	"${TEMP_ROOT}/${PHPDOC_TARBALL}" \
	"${TEMP_ROOT}/${JSDOC_TARBALL}" >/dev/null

"${SITE}/node_modules/.bin/smgen-data-pages" validate --root "${SITE}" >/dev/null
"${SITE}/node_modules/.bin/smgen-store" validate --root "${SITE}" pages >/dev/null
"${SITE}/node_modules/.bin/smgen-phpdoc" generate --root "${SITE}" --pages pages >/dev/null
"${SITE}/node_modules/.bin/smgen-phpdoc" check --root "${SITE}" --pages pages >/dev/null
"${SITE}/node_modules/.bin/smgen-phpdoc" clean --root "${SITE}" --pages pages >/dev/null
"${SITE}/node_modules/.bin/smgen-jsdoc" generate --root "${SITE}" --pages pages >/dev/null
"${SITE}/node_modules/.bin/smgen-jsdoc" check --root "${SITE}" --pages pages >/dev/null
"${SITE}/node_modules/.bin/smgen-jsdoc" clean --root "${SITE}" --pages pages >/dev/null

(
	cd "${SITE}"
	SMGEN_ENGINE=php SMG_SEARCH="${ROOT_DIR}/node_modules/.bin/smgen-search" "${SMGEN_BIN}" build >/dev/null
)

test -f "${SITE}/docs/products/smgen-mug.html"
test -f "${SITE}/docs/products/index.html"
test -f "${SITE}/docs/smgen-store/store.css"
test -f "${SITE}/docs/api/index.html"
test -f "${SITE}/docs/api/classes/store/product.html"
test -f "${SITE}/docs/js-api/functions/cart/cart-label.html"
test -f "${SITE}/docs/search.bin"
test -f "${SITE}/docs/llms.txt"
test -f "${SITE}/docs/sitemap.xml"
grep -Fq 'https://example.com/checkout/smgen-mug' "${SITE}/docs/products/smgen-mug.html"
grep -Fq 'https://schema.org' "${SITE}/docs/products/smgen-mug.html"
grep -Fq '/shop/smgen-store/store.css' "${SITE}/docs/products/smgen-mug.html"
grep -Fq 'Store\\Product' "${SITE}/docs/api/classes/store/product.html"
grep -Fq 'cartLabel' "${SITE}/docs/js-api/functions/cart/cart-label.html"
grep -Fq '"programmingLanguage":"JavaScript"' "${SITE}/docs/js-api/functions/cart/cart-label.html"
grep -Fq 'https://schema.org' "${SITE}/docs/api/classes/store/product.html"
grep -Fq '/shop/api/' "${SITE}/docs/llms.txt"
grep -Fq '/shop/api/classes/store/product.html' "${SITE}/docs/llms.txt"
grep -Fq '/shop/js-api/functions/cart/cart-label.html' "${SITE}/docs/llms.txt"
grep -Fq '/shop/api/classes/store/product.html' "${SITE}/docs/sitemap.xml"
grep -Fq '/shop/js-api/functions/cart/cart-label.html' "${SITE}/docs/sitemap.xml"
INDEX_FILE="${SITE}/docs/search.bin" "${ROOT_DIR}/node_modules/.bin/smgen-search" search 'catalog product' | grep -Fq 'api/classes/store/product'
if ! grep -Fq '/shop/smgen-phpdoc/syntax-highlight.js' "${SITE}/docs/api/classes/store/product.html"; then
	echo "PHP documentation did not load the syntax highlighter." >&2
	exit 1
fi
if grep -Eq 'smgen-phpdoc[^<]*\.css' "${SITE}/docs/api/classes/store/product.html"; then
	echo "PHP documentation unexpectedly loaded a stylesheet." >&2
	exit 1
fi
if grep -Fq 'smgen-phpdoc/' "${SITE}/docs/products/smgen-mug.html"; then
	echo "A product page unexpectedly loaded PHP documentation assets." >&2
	exit 1
fi
test -f "${SITE}/docs/smgen-phpdoc/syntax-highlight.js"
test -f "${SITE}/docs/smgen-phpdoc/vendor/highlight.js/highlight.min.js"
if grep -Eq 'js\.stripe\.com|paypal\.com/sdk|paypalobjects\.com' "${SITE}/docs/products/smgen-mug.html"; then
	echo "Link-only product unexpectedly loaded provider JavaScript." >&2
	exit 1
fi
test ! -e "${SITE}/pages/.smgen-generated"

JOURNAL="${TEMP_ROOT}/journal"
mkdir -p "${JOURNAL}/static"
cp -Rp "${ROOT_DIR}/tests/fixtures/blog-site/." "${JOURNAL}/"
cp -Rp "${SMGEN_ROOT}/templates/." "${JOURNAL}/templates/"
cp -Rp "${SMGEN_ROOT}/static/." "${JOURNAL}/static/"
printf '%s\n' '{"private":true}' > "${JOURNAL}/package.json"
npm install --prefix "${JOURNAL}" --ignore-scripts --no-package-lock --no-save \
	"${TEMP_ROOT}/${CATEGORIES_TARBALL}" \
	"${TEMP_ROOT}/${TIMELINE_TARBALL}" >/dev/null

"${JOURNAL}/node_modules/.bin/smgen-categories" validate --root "${JOURNAL}" --pages "${JOURNAL}/pages" >/dev/null

(
	cd "${JOURNAL}"
	SMGEN_ENGINE=php "${SMGEN_BIN}" build >/dev/null
)

test -f "${JOURNAL}/docs/topics/walks.html"
test -f "${JOURNAL}/docs/topics/tools.html"
test -f "${JOURNAL}/docs/days/2026-10-24/index.html"
test ! -e "${JOURNAL}/docs/days/2026-10-11"
test -f "${JOURNAL}/docs/smgen-timeline/timeline.css"
grep -Fq 'href="https://example.test/journal/smgen-timeline/timeline.css"' "${JOURNAL}/docs/index.html"
grep -Fq 'aria-label="October 24, 2026: 2 posts"' "${JOURNAL}/docs/index.html"
grep -Fq '<td class="has-posts has-rollup"><a href="https://example.test/journal/days/2026-10-24/"' "${JOURNAL}/docs/index.html"
grep -Fq '<td class="has-posts"><a href="https://example.test/journal/posts/solo.html"' "${JOURNAL}/docs/index.html"
grep -Fq 'href="https://example.test/journal/posts/late.html"' "${JOURNAL}/docs/days/2026-10-24/index.html"
grep -Fq 'Early post' "${JOURNAL}/docs/days/2026-10-24/index.html"
if grep -Fq 'Private post' "${JOURNAL}/docs/days/2026-10-24/index.html" || grep -Fq 'Private post' "${JOURNAL}/docs/index.html"; then
	echo "A page with archive: false joined the timeline." >&2
	exit 1
fi
grep -Fq 'Late post' "${JOURNAL}/docs/topics/walks.html"
test ! -e "${JOURNAL}/pages/.smgen-generated"

echo "SMGen plugin integration passed."
