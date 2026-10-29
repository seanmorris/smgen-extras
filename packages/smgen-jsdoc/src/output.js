// @ts-check

import crypto from 'node:crypto';
import fs from 'node:fs/promises';
import path from 'node:path';
import {parse, stringify} from 'yaml';

/** @import {JsDocConfig, RenderedPage} from './types.js' */

const owner = 'smgen-jsdoc';

/**
 * Write one build's generated pages into SMGen's private plugin directory.
 *
 * @param {string} baseDirectory Plugin-specific generated-page directory.
 * @param {JsDocConfig} config Normalized project configuration.
 * @param {RenderedPage[]} pages Fully rendered Markdown pages.
 * @returns {Promise<number>} Number of generated pages.
 */
export async function writeEphemeral(baseDirectory, config, pages)
{
	const base = await existingDirectory(baseDirectory, 'SMGen generated-page directory');
	const target = path.join(base, ...config.output.split('/'));
	inside(base, target, 'Generated output');
	await rejectLinkedSegments(base, target, 'Generated output');
	if(await exists(target))
	{
		const entries = await fs.readdir(target);
		if(entries.length > 0) throw new Error(`Generated output is not empty: ${target}`);
	}
	await fs.mkdir(target, {recursive: true, mode: 0o755});
	await writeTree(target, config, pages);
	return pages.length;
}

/**
 * Atomically replace one owned pages subtree while preserving no-op mtimes.
 *
 * @param {string} pagesDirectory Author pages directory.
 * @param {JsDocConfig} config Normalized project configuration.
 * @param {RenderedPage[]} pages Fully rendered Markdown pages.
 * @returns {Promise<{changed:boolean,count:number,target:string}>} Installation result.
 */
export async function installManaged(pagesDirectory, config, pages)
{
	const pagesRoot = await existingDirectory(pagesDirectory, 'Pages directory');
	inside(config.root, pagesRoot, 'Pages directory');
	validateCacheLocation(config, pagesRoot);
	const target = path.join(pagesRoot, ...config.output.split('/'));
	inside(pagesRoot, target, 'Managed output');
	await rejectLinkedSegments(pagesRoot, target, 'Managed output');
	if(await exists(target)) await requireOwnership(target);

	const expected = expectedContents(config, pages);
	if(await treeMatches(target, expected)) return {changed: false, count: pages.length, target};

	const token = crypto.randomBytes(8).toString('hex');
	const stage = path.join(config.root, `.smgen-jsdoc-stage-${token}`);
	const backup = path.join(config.root, `.smgen-jsdoc-backup-${token}`);
	await fs.mkdir(stage, {recursive: false, mode: 0o700});
	try
	{
		await writeTree(stage, config, pages);
		if(!(await treeMatches(stage, expected))) throw new Error('Staged JavaScript documentation failed its content verification.');
		await fs.mkdir(path.dirname(target), {recursive: true, mode: 0o755});
		let movedOld = false;
		let installedNew = false;
		try
		{
			if(await exists(target))
			{
				await fs.rename(target, backup);
				movedOld = true;
			}
			await fs.rename(stage, target);
			installedNew = true;
			await writeCacheManifest(config, target, expected);
			if(movedOld) await fs.rm(backup, {recursive: true, force: true});
		}
		catch(error)
		{
			if(installedNew && await exists(target)) await fs.rm(target, {recursive: true, force: true});
			if(movedOld && await exists(backup)) await fs.rename(backup, target);
			throw error;
		}
		return {changed: true, count: pages.length, target};
	}
	finally
	{
		if(await exists(stage)) await fs.rm(stage, {recursive: true, force: true});
		if(await exists(backup)) await fs.rm(backup, {recursive: true, force: true});
	}
}

/**
 * Report whether installed managed pages exactly match freshly rendered output.
 *
 * @param {string} pagesDirectory Author pages directory.
 * @param {JsDocConfig} config Normalized project configuration.
 * @param {RenderedPage[]} pages Fully rendered Markdown pages.
 * @returns {Promise<{current:boolean,reason:string,target:string}>} Drift result.
 */
export async function checkManaged(pagesDirectory, config, pages)
{
	const pagesRoot = await existingDirectory(pagesDirectory, 'Pages directory');
	inside(config.root, pagesRoot, 'Pages directory');
	const target = path.join(pagesRoot, ...config.output.split('/'));
	inside(pagesRoot, target, 'Managed output');
	await rejectLinkedSegments(pagesRoot, target, 'Managed output');
	if(!(await exists(target))) return {current: false, reason: `Managed output is missing: ${target}`, target};
	await requireOwnership(target);
	const current = await treeMatches(target, expectedContents(config, pages));
	return {current, reason: current ? '' : `Managed output differs from generated pages: ${target}`, target};
}

/**
 * Remove only a subtree carrying this plugin's ownership marker.
 *
 * @param {string} pagesDirectory Author pages directory.
 * @param {JsDocConfig} config Normalized project configuration.
 * @returns {Promise<boolean>} Whether an owned tree was removed.
 */
export async function cleanManaged(pagesDirectory, config)
{
	const pagesRoot = await existingDirectory(pagesDirectory, 'Pages directory');
	inside(config.root, pagesRoot, 'Pages directory');
	const target = path.join(pagesRoot, ...config.output.split('/'));
	inside(pagesRoot, target, 'Managed output');
	await rejectLinkedSegments(pagesRoot, target, 'Managed output');
	if(!(await exists(target))) return false;
	await requireOwnership(target);
	await fs.rm(target, {recursive: true, force: false});
	return true;
}

/**
 * Return expected files and hashes, including the ownership frontmatter.
 *
 * @param {JsDocConfig} config Normalized project configuration.
 * @param {RenderedPage[]} pages Fully rendered Markdown pages.
 * @returns {Map<string,{content:string,hash:string}>} Expected relative files.
 */
function expectedContents(config, pages)
{
	/** @type {Map<string,{content:string,hash:string}>} */
	const expected = new Map();
	const marker = ownershipFrontmatter(config);
	expected.set('.fm.yaml', {content: marker, hash: hash(marker)});
	for(const page of pages)
	{
		const relative = safeOutputPath(page.path);
		if(expected.has(relative)) throw new Error(`Generated output collision: ${relative}`);
		expected.set(relative, {content: page.content, hash: hash(page.content)});
	}
	return expected;
}

/**
 * Write all expected files beneath one empty staging directory.
 *
 * @param {string} root Empty staging root.
 * @param {JsDocConfig} config Normalized project configuration.
 * @param {RenderedPage[]} pages Fully rendered Markdown pages.
 * @returns {Promise<void>} Completion after every file is written.
 */
async function writeTree(root, config, pages)
{
	const expected = expectedContents(config, pages);
	for(const [relative, value] of expected)
	{
		const target = path.join(root, ...relative.split('/'));
		inside(root, target, 'Rendered page');
		await fs.mkdir(path.dirname(target), {recursive: true, mode: 0o755});
		await fs.writeFile(target, value.content, {encoding: 'utf8', mode: 0o644, flag: 'wx'});
	}
}

/**
 * Compare a real directory with expected files without following links.
 *
 * @param {string} root Candidate installed tree.
 * @param {Map<string,{content:string,hash:string}>} expected Expected relative files.
 * @returns {Promise<boolean>} Whether paths and hashes match exactly.
 */
async function treeMatches(root, expected)
{
	if(!(await exists(root))) return false;
	const stat = await fs.lstat(root);
	if(!stat.isDirectory() || stat.isSymbolicLink()) return false;
	/** @type {Map<string,string>} */
	const actual = new Map();
	/**
	 * Hash one candidate directory without crossing symbolic links.
	 *
	 * @param {string} directory Directory currently being compared.
	 * @returns {Promise<boolean>} Whether every descendant is a regular file or directory.
	 */
	const walk = async (directory) => {
		const entries = await fs.readdir(directory, {withFileTypes: true});
		entries.sort((left, right) => left.name.localeCompare(right.name));
		for(const entry of entries)
		{
			const absolute = path.join(directory, entry.name);
			if(entry.isSymbolicLink()) return false;
			if(entry.isDirectory())
			{
				if(!await walk(absolute)) return false;
				continue;
			}
			if(!entry.isFile()) return false;
			const relative = slash(path.relative(root, absolute));
			actual.set(relative, hash(await fs.readFile(absolute)));
		}
		return true;
	};
	if(!await walk(root) || actual.size !== expected.size) return false;
	for(const [relative, value] of expected)
	{
		if(actual.get(relative) !== value.hash) return false;
	}
	return true;
}

/**
 * Require a matching ownership mapping before replacement or deletion.
 *
 * @param {string} target Existing managed output candidate.
 * @returns {Promise<void>} Completion after ownership is verified.
 */
async function requireOwnership(target)
{
	const markerPath = path.join(target, '.fm.yaml');
	if(!(await exists(markerPath)) || (await fs.lstat(markerPath)).isSymbolicLink())
	{
		throw new Error(`Refusing to replace unowned output directory: ${target}`);
	}
	let marker;
	try
	{
		marker = /** @type {unknown} */ (parse(await fs.readFile(markerPath, 'utf8')));
	}
	catch(error)
	{
		throw new Error(`Refusing to replace output with an invalid ownership marker: ${target}`, {cause: error});
	}
	if(!isRecord(marker)) throw new Error(`Refusing to replace unowned output directory: ${target}`);
	const ownership = marker['smgen-jsdoc'];
	if(!isRecord(ownership) || ownership.owner !== owner || ownership.schemaVersion !== 1)
	{
		throw new Error(`Refusing to replace unowned output directory: ${target}`);
	}
}

/**
 * Build directory frontmatter that identifies ownership and supplies a title.
 *
 * @param {JsDocConfig} config Normalized project configuration.
 * @returns {string} YAML directory metadata.
 */
function ownershipFrontmatter(config)
{
	return stringify({
		title: config.package.title,
		'smgen-jsdoc': {
			owner,
			schemaVersion: 1,
			config: slash(path.relative(config.root, config.configPath)),
		},
	}, {lineWidth: 0});
}

/**
 * Persist a content manifest outside PAGES_DIR after a successful swap.
 *
 * @param {JsDocConfig} config Normalized project configuration.
 * @param {string} target Installed managed tree.
 * @param {Map<string,{content:string,hash:string}>} expected Installed files and hashes.
 * @returns {Promise<void>} Completion after atomic manifest replacement.
 */
async function writeCacheManifest(config, target, expected)
{
	const cacheRoot = path.resolve(config.root, ...config.cache.split('/'));
	inside(config.root, cacheRoot, 'Cache directory');
	await rejectLinkedSegments(config.root, cacheRoot, 'Cache directory');
	await fs.mkdir(cacheRoot, {recursive: true, mode: 0o700});
	const manifest = {
		schemaVersion: 1,
		owner,
		target: slash(path.relative(config.root, target)),
		files: Object.fromEntries([...expected.entries()].map(([relative, value]) => [relative, value.hash])),
	};
	const temporary = path.join(cacheRoot, `manifest-${process.pid}-${crypto.randomBytes(4).toString('hex')}.json`);
	try
	{
		await fs.writeFile(temporary, JSON.stringify(manifest, null, 2) + '\n', {mode: 0o600, flag: 'wx'});
		await fs.rename(temporary, path.join(cacheRoot, 'manifest.json'));
	}
	finally
	{
		if(await exists(temporary)) await fs.rm(temporary, {force: true});
	}
}

/**
 * Keep the cache outside the author pages tree so it cannot become published
 * content or invalidate the generated output it describes.
 *
 * @param {JsDocConfig} config Normalized project configuration.
 * @param {string} pagesRoot Canonical author pages directory.
 * @returns {void}
 */
function validateCacheLocation(config, pagesRoot)
{
	const cacheRoot = path.resolve(config.root, ...config.cache.split('/'));
	inside(config.root, cacheRoot, 'Cache directory');
	const relative = path.relative(pagesRoot, cacheRoot);
	if(relative === '' || relative !== '..' && !relative.startsWith('..' + path.sep) && !path.isAbsolute(relative))
	{
		throw new Error(`Cache directory must stay outside the pages directory: ${cacheRoot}`);
	}
}

/**
 * Resolve and require one existing non-link directory.
 *
 * @param {string} value Configured directory.
 * @param {string} label Diagnostic label.
 * @returns {Promise<string>} Canonical directory path.
 */
async function existingDirectory(value, label)
{
	const absolute = path.resolve(value);
	const stat = await fs.lstat(absolute).catch(() => null);
	if(stat === null || !stat.isDirectory() || stat.isSymbolicLink()) throw new Error(`${label} is not a safe directory: ${value}`);
	return await fs.realpath(absolute);
}

/**
 * Reject any symbolic link along an existing target path prefix.
 *
 * @param {string} root Canonical root.
 * @param {string} target Candidate descendant.
 * @param {string} label Diagnostic label.
 * @returns {Promise<void>} Completion after existing segments are checked.
 */
async function rejectLinkedSegments(root, target, label)
{
	let cursor = root;
	for(const segment of path.relative(root, target).split(path.sep).filter(Boolean))
	{
		cursor = path.join(cursor, segment);
		const stat = await fs.lstat(cursor).catch(() => null);
		if(stat?.isSymbolicLink()) throw new Error(`${label} contains a symbolic link: ${cursor}`);
		if(stat === null) break;
	}
}

/**
 * Reject paths that leave their intended root.
 *
 * @param {string} root Canonical root.
 * @param {string} target Candidate descendant.
 * @param {string} label Diagnostic label.
 * @returns {void}
 */
function inside(root, target, label)
{
	const relative = path.relative(root, target);
	if(relative === '..' || relative.startsWith('..' + path.sep) || path.isAbsolute(relative)) throw new Error(`${label} escapes its root: ${target}`);
}

/**
 * Validate one renderer-owned relative Markdown path.
 *
 * @param {string} value Renderer output path.
 * @returns {string} Validated POSIX relative path.
 */
function safeOutputPath(value)
{
	const normalized = slash(value);
	if(path.posix.isAbsolute(normalized) || !normalized.endsWith('.md') || normalized.split('/').some((segment) => segment === '' || segment === '.' || segment === '..'))
	{
		throw new Error(`Unsafe rendered output path: ${value}`);
	}
	return normalized;
}

/**
 * Compute one lowercase SHA-256 content digest.
 *
 * @param {string|Buffer} value Content bytes.
 * @returns {string} Hexadecimal SHA-256 digest.
 */
function hash(value)
{
	return crypto.createHash('sha256').update(value).digest('hex');
}

/**
 * Report whether a filesystem entry exists without following dangling links.
 *
 * @param {string} value Filesystem path.
 * @returns {Promise<boolean>} Whether lstat finds an entry.
 */
async function exists(value)
{
	return await fs.lstat(value).then(() => true, () => false);
}

/**
 * Normalize path separators for stored paths.
 *
 * @param {string} value Native path.
 * @returns {string} POSIX-style path.
 */
function slash(value)
{
	return value.split(path.sep).join('/');
}

/**
 * Report whether an unknown parsed YAML value is an ordinary object.
 *
 * @param {unknown} value Parsed YAML value.
 * @returns {value is Record<string,unknown>} Whether the value is a mapping.
 */
function isRecord(value)
{
	return value !== null && typeof value === 'object' && !Array.isArray(value);
}
