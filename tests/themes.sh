#!/usr/bin/env bash
# Scaffold every theme with create-smgen-site, build each with SMGen core, and
# check the result. Also keeps the scaffolder's theme pins in step with the
# theme package versions so the workspace symlinks, not the registry, are used.

set -Eeuo pipefail

ROOT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)"
SMGEN_BIN="${SMGEN_BIN:-/app/smgen.sh}"
SCAFFOLD="${ROOT_DIR}/themes/create-smgen-site/bin/create-smgen-site.js"
TEMP_ROOT="$(mktemp -d)"
trap 'rm -rf -- "${TEMP_ROOT}"' EXIT

fail()
{
	echo "FAIL: $*" >&2
	exit 1
}

node - "${ROOT_DIR}" <<'NODE'
const fs = require('node:fs');
const path = require('node:path');
const root = process.argv[2];
const scaffolder = JSON.parse(fs.readFileSync(path.join(root, 'themes/create-smgen-site/package.json'), 'utf8'));
let bad = 0;
for (const [name, pin] of Object.entries(scaffolder.dependencies || {})) {
	if (!name.startsWith('smgen-theme-')) continue;
	const dir = path.join(root, 'themes', name.replace(/^smgen-theme-/, ''));
	const version = JSON.parse(fs.readFileSync(path.join(dir, 'package.json'), 'utf8')).version;
	if (pin !== version) { console.error(`create-smgen-site pins ${name}@${pin} but the workspace has ${version}`); bad++; }
}
for (const entry of fs.readdirSync(path.join(root, 'themes'))) {
	if (entry === 'create-smgen-site') continue;
	if (!fs.existsSync(path.join(root, 'themes', entry, 'theme.json'))) continue;
	if (!scaffolder.dependencies || !scaffolder.dependencies[`smgen-theme-${entry}`]) { console.error(`create-smgen-site does not depend on smgen-theme-${entry}`); bad++; }
}
process.exit(bad === 0 ? 0 : 1);
NODE

listing="$(node "${SCAFFOLD}" --list)"
for manifest in "${ROOT_DIR}"/themes/*/theme.json; do
	theme="$(basename "$(dirname "${manifest}")")"
	grep -Eq "^${theme}[[:space:]]" <<< "${listing}" || fail "--list does not name ${theme}"
	[ "$(grep -Ec "^${theme}[[:space:]]" <<< "${listing}")" -eq 1 ] || fail "--list names ${theme} more than once"

	site="${TEMP_ROOT}/${theme}"
	node "${SCAFFOLD}" "${site}" --theme "${theme}" --name "Probe ${theme} Site" > "${TEMP_ROOT}/${theme}.log" 2>&1 || { cat "${TEMP_ROOT}/${theme}.log" >&2; fail "scaffolding ${theme}"; }
	[ ! -e "${site}/package.json" ] || fail "${theme}: package.json was copied"
	[ ! -e "${site}/theme.json" ] || fail "${theme}: theme.json was copied"
	[ ! -d "${site}/node_modules" ] || fail "${theme}: node_modules was copied"
	grep -Fxq "PRODUCT_NAME: \"Probe ${theme} Site\"" "${site}/.smgen.yaml" || fail "${theme}: PRODUCT_NAME not rewritten"
	grep -Fxq "BASE_URL: ''" "${site}/.smgen.yaml" || fail "${theme}: BASE_URL not rewritten"
	grep -Fxq "OUTPUT_DIR: \"./docs\"" "${site}/.smgen.yaml" || fail "${theme}: OUTPUT_DIR not rewritten"
	[ -f "${site}/README.md" ] || fail "${theme}: README missing"

	( cd "${site}" && "${SMGEN_BIN}" build > "${TEMP_ROOT}/${theme}.build.log" 2>&1 ) || { cat "${TEMP_ROOT}/${theme}.build.log" >&2; fail "building ${theme}"; }
	[ -f "${site}/docs/index.html" ] || fail "${theme}: no index.html"
	grep -Fq "Probe ${theme} Site" "${site}/docs/index.html" || fail "${theme}: product name missing from index.html"
	[ -f "${site}/docs/sitemap.xml" ] || fail "${theme}: no sitemap"
	case "${theme}" in
		journal)
			[ -f "${site}/docs/feed.xml" ] || fail "journal: no feed.xml"
			grep -Fq '<item>' "${site}/docs/feed.xml" || fail "journal: feed has no items"
			;;
		semantic)
			grep -Fq '<main id="content">' "${site}/docs/elements.html" || fail "semantic: main landmark missing"
			;;
	esac
	echo "ok ${theme}"
done

busy="${TEMP_ROOT}/busy"; mkdir -p "${busy}"; : > "${busy}/keep"
node "${SCAFFOLD}" "${busy}" --theme semantic > /dev/null 2>&1 && fail "occupied directory accepted"
[ "$(node "${SCAFFOLD}" "${busy}" --theme semantic > /dev/null 2>&1; echo $?)" -eq 2 ] || fail "occupied directory exit code"
[ "$(node "${SCAFFOLD}" "${TEMP_ROOT}/unknown" --theme nope > /dev/null 2>&1; echo $?)" -eq 1 ] || fail "unknown theme exit code"
[ "$(node "${SCAFFOLD}" "${TEMP_ROOT}/notty" > /dev/null 2>&1 < /dev/null; echo $?)" -eq 1 ] || fail "missing --theme exit code"

echo "PASS: themes"
