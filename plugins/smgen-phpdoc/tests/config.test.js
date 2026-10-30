// @ts-check

import assert from 'node:assert/strict';
import fs from 'node:fs/promises';
import os from 'node:os';
import path from 'node:path';
import test from 'node:test';
import {analyze} from '../src/adapter.js';
import {discoverSources, loadConfig} from '../src/config.js';

test('configuration rejects unknown fields and linked source roots', async () => {
	await withTemporaryProject(async (project) => {
		await fs.mkdir(path.join(project, 'src'));
		await fs.writeFile(path.join(project, 'src', 'Example.php'), '<?php\n/** Example type. */\nclass Example {}\n');
		await fs.writeFile(path.join(project, '.smgen-phpdoc.yaml'), 'version: 1\npackage: {title: Example}\nunknown: true\n');
		await assert.rejects(loadConfig(project), /Unknown configuration/);

		await fs.writeFile(path.join(project, '.smgen-phpdoc.yaml'), 'version: 1\npackage: {title: Example}\nsources: [{path: linked}]\n');
		await fs.symlink(path.join(project, 'src'), path.join(project, 'linked'));
		const config = await loadConfig(project);
		await assert.rejects(discoverSources(config), /symbolic link/);
	});
});

test('source discovery accepts case-insensitive PHP extensions end to end', async () => {
	await withTemporaryProject(async (project) => {
		await fs.mkdir(path.join(project, 'src'));
		await fs.writeFile(path.join(project, 'src', 'Example.PHP'), '<?php\n/** Example type. */\nfinal class Example {}\n');
		await fs.writeFile(path.join(project, '.smgen-phpdoc.yaml'), 'version: 1\npackage: {title: Example}\n');
		const config = await loadConfig(project);
		const files = await discoverSources(config);
		assert.equal(files[0]?.relative, 'src/Example.PHP');
		const graph = await analyze(config, files);
		assert.equal(graph.symbols[0]?.name, 'Example');
	});
});

test('body headings default on and can be delegated to the site template', async () => {
	await withTemporaryProject(async (project) => {
		await fs.mkdir(path.join(project, 'src'));
		await fs.writeFile(path.join(project, 'src', 'Example.php'), '<?php\n/** Example type. */\nclass Example {}\n');
		const configPath = path.join(project, '.smgen-phpdoc.yaml');
		await fs.writeFile(configPath, 'version: 1\npackage: {title: Example}\n');
		assert.equal((await loadConfig(project)).bodyHeading, true);

		await fs.writeFile(configPath, 'version: 1\npackage: {title: Example}\nbodyHeading: false\n');
		assert.equal((await loadConfig(project)).bodyHeading, false);

		await fs.writeFile(configPath, 'version: 1\npackage: {title: Example}\nbodyHeading: title\n');
		await assert.rejects(loadConfig(project), /bodyHeading must be true or false/);
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
	const project = await fs.mkdtemp(path.join(os.tmpdir(), 'smgen-phpdoc-config-'));
	try
	{
		await callback(project);
	}
	finally
	{
		await fs.rm(project, {recursive: true, force: true});
	}
}
