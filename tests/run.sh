#!/usr/bin/env bash

set -Eeuo pipefail

ROOT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)"

php "${ROOT_DIR}/packages/smgen-data-pages/tests/run.php"
php "${ROOT_DIR}/packages/smgen-store/tests/run.php"
bash "${ROOT_DIR}/tests/integration.sh"
