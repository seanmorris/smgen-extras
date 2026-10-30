#!/usr/bin/env bash

set -Eeuo pipefail

ROOT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)"

php "${ROOT_DIR}/scripts/check-php-docblocks.php" "${ROOT_DIR}/plugins"
npx eslint "${ROOT_DIR}/plugins" "${ROOT_DIR}/themes"

while IFS= read -r -d '' file; do
	php -l "${file}" >/dev/null
done < <(find "${ROOT_DIR}/plugins" "${ROOT_DIR}/themes" "${ROOT_DIR}/scripts" \( -path '*/vendor' -o -path '*/docs' \) -prune -o -type f -name '*.php' -print0)

echo "Lint passed."
