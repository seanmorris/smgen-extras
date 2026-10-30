#!/usr/bin/env bash

set -Eeuo pipefail

ROOT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)"

php "${ROOT_DIR}/plugins/smgen-data-pages/tests/run.php"
php "${ROOT_DIR}/plugins/smgen-store/tests/run.php"
php "${ROOT_DIR}/plugins/smgen-categories/tests/run.php"
php "${ROOT_DIR}/plugins/smgen-timeline/tests/run.php"
node --test "${ROOT_DIR}"/plugins/smgen-phpdoc/tests/*.test.js
node --test "${ROOT_DIR}"/plugins/smgen-jsdoc/tests/*.test.js
node --test "${ROOT_DIR}"/themes/create-smgen-site/tests/*.test.js
bash "${ROOT_DIR}/tests/integration.sh"
bash "${ROOT_DIR}/tests/themes.sh"
