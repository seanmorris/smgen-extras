// Copy a theme directory into a new site, leaving package bookkeeping behind.

import {cpSync, mkdirSync, readdirSync, statSync, existsSync} from 'node:fs';
import {basename, join} from 'node:path';

const SKIPPED = new Set(['package.json', 'node_modules', 'theme.json', 'README.md', 'LICENSE', 'docs', '.smgen-generated', '.DS_Store']);

/**
 * Decide whether a top-level theme entry belongs in a user's site.
 *
 * @param {string} name Entry name relative to the theme root.
 * @returns {boolean} True when the entry should be copied.
 */
export function isSiteEntry(name)
{
	return !SKIPPED.has(name);
}

/**
 * Report whether a target can receive a site: missing, or an empty directory.
 *
 * @param {string} target Absolute path.
 * @returns {'missing'|'empty'|'occupied'|'file'} The target's state.
 */
export function targetState(target)
{
	if(!existsSync(target)) return 'missing';
	const stats = statSync(target);
	if(!stats.isDirectory()) return 'file';
	return readdirSync(target).length === 0 ? 'empty' : 'occupied';
}

/**
 * Copy the theme's site files into the target, preserving file modes.
 *
 * @param {string} themeDir Theme package directory.
 * @param {string} target Absolute destination directory.
 * @returns {string[]} Top-level entries that were copied.
 */
export function copyTheme(themeDir, target)
{
	mkdirSync(target, {recursive: true, mode: 0o755});
	const copied = [];
	for(const entry of readdirSync(themeDir))
	{
		if(!isSiteEntry(entry)) continue;
		cpSync(join(themeDir, entry), join(target, entry), {
			recursive: true,
			preserveTimestamps: false,
			filter: (source) => basename(source) !== '.DS_Store' && basename(source) !== '.smgen-generated',
		});
		copied.push(entry);
	}
	return copied;
}
