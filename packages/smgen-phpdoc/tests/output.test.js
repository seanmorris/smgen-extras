// @ts-check

import assert from 'node:assert/strict';
import crypto from 'node:crypto';
import fs from 'node:fs/promises';
import os from 'node:os';
import path from 'node:path';
import test from 'node:test';
import {fileURLToPath} from 'node:url';
import {buildDocumentation} from '../src/build.js';
import {loadConfig} from '../src/config.js';
import {checkManaged, cleanManaged, installManaged} from '../src/output.js';

const fixture = path.join(path.dirname(fileURLToPath(import.meta.url)), 'fixtures', 'project');

test('managed output is atomic, ownership-scoped, and unchanged on a no-op', async () => {
	await withProject(async (project, pages) => {
		const config = await loadConfig(project);
		const build = await buildDocumentation(config);
		const first = await installManaged(pages, config, build.pages);
		assert.equal(first.changed, true);
		const landing = path.join(pages, 'api', 'index.md');
		const landingContent = await fs.readFile(landing, 'utf8');
		assert.match(landingContent, /^TOC: true$/m);
		assert.doesNotMatch(landingContent, /^toc: true$/m);
		assert.doesNotMatch(landingContent, /^breadcrumbs:/m);
		const contracts = await fs.readFile(path.join(pages, 'api', 'namespaces', 'acme', 'contracts', 'index.md'), 'utf8');
		assert.match(contracts, /^breadcrumbs:\n  - title: Acme PHP API\n    path: \/api\/\n  - title: Acme\n    path: \/api\/namespaces\/acme\/\nllms:/m);
		const renderable = await fs.readFile(path.join(pages, 'api', 'interfaces', 'acme', 'contracts', 'renderable.md'), 'utf8');
		assert.match(renderable, /^breadcrumbs:\n  - title: Acme PHP API\n    path: \/api\/\n  - title: Acme\n    path: \/api\/namespaces\/acme\/\n  - title: Contracts\n    path: \/api\/namespaces\/acme\/contracts\/\n/m);
		const firstStat = await fs.stat(landing);
		await delay(25);
		const second = await installManaged(pages, config, build.pages);
		assert.equal(second.changed, false);
		assert.equal((await fs.stat(landing)).mtimeMs, firstStat.mtimeMs);

		await fs.writeFile(path.join(pages, 'api', 'stale.md'), 'stale\n');
		const replaced = await installManaged(pages, config, build.pages);
		assert.equal(replaced.changed, true);
		await assert.rejects(fs.stat(path.join(pages, 'api', 'stale.md')), {code: 'ENOENT'});
		assert.equal((await checkManaged(pages, config, build.pages)).current, true);

		await fs.appendFile(landing, '\ndrift\n');
		assert.equal((await checkManaged(pages, config, build.pages)).current, false);
		await installManaged(pages, config, build.pages);
		const beforeFailure = await digest(landing);
		await fs.appendFile(path.join(project, 'src', 'functions.php'), '\nfunction broken( {\n');
		await assert.rejects(buildDocumentation(config), /Syntax error|unexpected/i);
		assert.equal(await digest(landing), beforeFailure);

		assert.equal(await cleanManaged(pages, config), true);
		assert.equal(await cleanManaged(pages, config), false);
	});
});

test('unowned directories and dangling output links are rejected', async () => {
	await withProject(async (project, pages) => {
		const baseConfig = await loadConfig(project);
		const build = await buildDocumentation(baseConfig);
		const unowned = {...baseConfig, output: 'manual-api'};
		await fs.mkdir(path.join(pages, 'manual-api'));
		await fs.writeFile(path.join(pages, 'manual-api', 'notes.md'), '# Authored\n');
		await assert.rejects(installManaged(pages, unowned, build.pages), /unowned output/);

		const outside = path.join(project, 'outside-api');
		const linked = {...baseConfig, output: 'linked-api'};
		await fs.symlink(outside, path.join(pages, 'linked-api'));
		await assert.rejects(installManaged(pages, linked, build.pages), /symbolic link/);
		await assert.rejects(fs.stat(outside), {code: 'ENOENT'});
	});
});

test('cache failures restore the prior generated tree', async () => {
	await withProject(async (project, pages) => {
		const config = await loadConfig(project);
		const build = await buildDocumentation(config);
		await installManaged(pages, config, build.pages);
		const landing = path.join(pages, 'api', 'index.md');
		const prior = await digest(landing);
		const blockedCache = path.join(project, 'blocked-cache');
		await fs.writeFile(blockedCache, 'not a directory\n');
		const changedPages = build.pages.map((page) => page.path === 'index.md'
			? {...page, content: page.content + '\nChanged.\n'}
			: page);
		await assert.rejects(
			installManaged(pages, {...config, cache: 'blocked-cache'}, changedPages),
			/EEXIST|directory/i,
		);
		assert.equal(await digest(landing), prior);
	});
});

test('CLI pages and cache directories cannot escape their ownership boundaries', async () => {
	await withProject(async (project, pages) => {
		const config = await loadConfig(project);
		const build = await buildDocumentation(config);
		const outside = path.join(path.dirname(project), 'outside-pages');
		await fs.mkdir(outside);
		await assert.rejects(installManaged(outside, config, build.pages), /Pages directory escapes/);
		await assert.rejects(installManaged(pages, {...config, cache: 'pages/cache'}, build.pages), /outside the pages directory/);
	});
});

/**
 * Create an isolated fixture project and remove it after one regression.
 *
 * @param {(project:string,pages:string)=>Promise<void>} callback Regression body.
 * @returns {Promise<void>} Completion after cleanup.
 */
async function withProject(callback)
{
	const root = await fs.mkdtemp(path.join(os.tmpdir(), 'smgen-phpdoc-test-'));
	const project = path.join(root, 'project');
	const pages = path.join(project, 'pages');
	try
	{
		await fs.cp(fixture, project, {recursive: true});
		await fs.mkdir(pages);
		await callback(project, pages);
	}
	finally
	{
		await fs.rm(root, {recursive: true, force: true});
	}
}

/**
 * Compute a file digest without depending on timestamp precision.
 *
 * @param {string} file File to hash.
 * @returns {Promise<string>} SHA-256 digest.
 */
async function digest(file)
{
	return crypto.createHash('sha256').update(await fs.readFile(file)).digest('hex');
}

/**
 * Wait long enough to expose accidental no-op timestamp writes.
 *
 * @param {number} milliseconds Delay length.
 * @returns {Promise<void>} Completion after the timer fires.
 */
function delay(milliseconds)
{
	return new Promise((resolve) => setTimeout(resolve, milliseconds));
}
