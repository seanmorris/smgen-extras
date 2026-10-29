// @ts-check

import assert from 'node:assert/strict';
import fs from 'node:fs/promises';
import os from 'node:os';
import path from 'node:path';
import test from 'node:test';
import {fileURLToPath} from 'node:url';
import {buildDocumentation} from '../src/build.js';
import {loadConfig} from '../src/config.js';
import {checkManaged, cleanManaged, installManaged} from '../src/output.js';

const fixture = path.join(path.dirname(fileURLToPath(import.meta.url)), 'fixtures', 'project');

test('managed output is ownership-scoped, atomic, and unchanged on a no-op', async () => {
	await withProject(async (project, pages) => {
		const config = await loadConfig(project);
		const build = await buildDocumentation(config);
		const first = await installManaged(pages, config, build.pages);
		assert.equal(first.changed, true);
		const landing = path.join(pages, 'api', 'index.md');
		const firstStat = await fs.stat(landing);
		await delay(25);
		assert.equal((await installManaged(pages, config, build.pages)).changed, false);
		assert.equal((await fs.stat(landing)).mtimeMs, firstStat.mtimeMs);
		assert.equal((await checkManaged(pages, config, build.pages)).current, true);
		await fs.appendFile(landing, '\ndrift\n');
		assert.equal((await checkManaged(pages, config, build.pages)).current, false);
		await installManaged(pages, config, build.pages);
		assert.equal(await cleanManaged(pages, config), true);
		assert.equal(await cleanManaged(pages, config), false);
	});
});

test('unowned directories and dangling output links are rejected', async () => {
	await withProject(async (project, pages) => {
		const config = await loadConfig(project);
		const build = await buildDocumentation(config);
		const unowned = {...config, output: 'manual-api'};
		await fs.mkdir(path.join(pages, 'manual-api'));
		await fs.writeFile(path.join(pages, 'manual-api', 'notes.md'), '# Authored\n');
		await assert.rejects(installManaged(pages, unowned, build.pages), /unowned output/);

		const outside = path.join(project, 'outside-api');
		const linked = {...config, output: 'linked-api'};
		await fs.symlink(outside, path.join(pages, 'linked-api'));
		await assert.rejects(installManaged(pages, linked, build.pages), /symbolic link/);
		await assert.rejects(fs.stat(outside), {code: 'ENOENT'});
	});
});

/**
 * Copy the fixture into a temporary project with an author pages directory.
 *
 * @param {(project:string,pages:string)=>Promise<void>} callback Regression body.
 * @returns {Promise<void>} Completion after cleanup.
 */
async function withProject(callback)
{
	const root = await fs.mkdtemp(path.join(os.tmpdir(), 'smgen-jsdoc-output-'));
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
 * Wait long enough to expose accidental no-op timestamp writes.
 *
 * @param {number} milliseconds Delay length.
 * @returns {Promise<void>} Completion after the timer fires.
 */
function delay(milliseconds)
{
	return new Promise((resolve) => setTimeout(resolve, milliseconds));
}
