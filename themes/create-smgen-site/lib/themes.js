// Discover the installed theme packages through this package's dependencies.

import {createRequire} from 'node:module';
import {readFileSync} from 'node:fs';
import {dirname, join} from 'node:path';
import {fileURLToPath} from 'node:url';

const require = createRequire(import.meta.url);
const packageRoot = dirname(dirname(fileURLToPath(import.meta.url)));

/**
 * List the themes this scaffolder can copy, in dependency order.
 *
 * @returns {Array<{name: string, package: string, dir: string, manifest: Record<string, any>}>} Installed themes.
 */
export function discoverThemes()
{
	const own = JSON.parse(readFileSync(join(packageRoot, 'package.json'), 'utf8'));
	const names = Object.keys(own.dependencies ?? {}).filter((name) => name.startsWith('smgen-theme-'));
	return names.map((name) => {
		const dir = dirname(require.resolve(name + '/package.json'));
		const manifest = JSON.parse(readFileSync(join(dir, 'theme.json'), 'utf8'));
		if(typeof manifest.name !== 'string' || manifest.name === '')
		{
			throw new Error('Theme package ' + name + ' has no name in theme.json.');
		}
		return {name: manifest.name, package: name, dir, manifest};
	});
}

/**
 * Format the theme list for --list and for the interactive prompt.
 *
 * @param {Array<{name: string, manifest: Record<string, any>}>} themes Installed themes.
 * @returns {string} One line per theme.
 */
export function formatThemeList(themes)
{
	const width = Math.max(...themes.map((theme) => theme.name.length), 4);
	return themes.map((theme) => theme.name.padEnd(width + 2) + String(theme.manifest.description ?? '')).join('\n') + '\n';
}
