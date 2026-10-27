#!/usr/bin/env bash

set -Eeuo pipefail

ROOT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)"

php "${ROOT_DIR}/scripts/check-php-docblocks.php" "${ROOT_DIR}/packages"
npx eslint "${ROOT_DIR}/packages/smgen-store/static/store.js"

while IFS= read -r -d '' file; do
	php -l "${file}" >/dev/null
done < <(find "${ROOT_DIR}/packages" "${ROOT_DIR}/scripts" -type f -name '*.php' -print0)

echo "Lint passed."
