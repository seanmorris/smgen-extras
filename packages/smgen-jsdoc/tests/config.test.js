// @ts-check

import assert from 'node:assert/strict';
import fs from 'node:fs/promises';
import os from 'node:os';
import path from 'node:path';
import test from 'node:test';
import {discoverSources, loadConfig} from '../src/config.js';

test('configuration rejects unknown fields and linked source roots', async () => {
	await withTemporaryProject(async (project) => {
		await fs.mkdir(path.join(project, 'src'));
		await fs.writeFile(path.join(project, 'src', 'example.js'), '/** Example. */\nexport const example = true;\n');
		await fs.writeFile(path.join(project, '.smgen-jsdoc.yaml'), 'version: 1\npackage: {title: Example}\nunknown: true\n');
		await assert.rejects(loadConfig(project), /Unknown configuration/);

		await fs.writeFile(path.join(project, '.smgen-jsdoc.yaml'), 'version: 1\npackage: {title: Example}\nsources: [{path: linked}]\n');
		await fs.symlink(path.join(project, 'src'), path.join(project, 'linked'));
		const config = await loadConfig(project);
		await assert.rejects(discoverSources(config), /symbolic link/);
	});
});

test('source discovery accepts JavaScript module extensions and excludes tests', async () => {
	await withTemporaryProject(async (project) => {
		await fs.mkdir(path.join(project, 'src'));
		await fs.mkdir(path.join(project, 'src', 'tests'));
		await fs.writeFile(path.join(project, 'src', 'index.mjs'), '/** Example. */\nexport const example = true;\n');
		await fs.writeFile(path.join(project, 'src', 'worker.cjs'), '/** Worker. */\nexports.worker = true;\n');
		await fs.writeFile(path.join(project, 'src', 'tests', 'ignored.js'), 'export const ignored = true;\n');
		await fs.writeFile(path.join(project, '.smgen-jsdoc.yaml'), 'version: 1\npackage: {title: Example}\n');
		const files = await discoverSources(await loadConfig(project));
		assert.deepEqual(files.map((file) => [file.relative, file.module]), [
			['src/index.mjs', 'index'],
			['src/worker.cjs', 'worker'],
		]);
	});
});

/**
 * Create and clean one isolated configuration project.
 *
 * @param {(project:string)=>Promise<void>} callback Regression body.
 * @returns {Promise<void>} Completion after cleanup.
 */
async function withTemporaryProject(callback)
{
	const project = await fs.mkdtemp(path.join(os.tmpdir(), 'smgen-jsdoc-config-'));
	try
	{
		await callback(project);
	}
	finally
	{
		await fs.rm(project, {recursive: true, force: true});
	}
}
