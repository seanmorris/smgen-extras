import {test} from 'node:test';
import assert from 'node:assert/strict';
import {spawnSync} from 'node:child_process';
import {mkdtempSync, mkdirSync, writeFileSync, existsSync, readFileSync, rmSync} from 'node:fs';
import {tmpdir} from 'node:os';
import {join, dirname} from 'node:path';
import {fileURLToPath} from 'node:url';

const bin = join(dirname(fileURLToPath(import.meta.url)), '..', 'bin', 'create-smgen-site.js');

/**
 * Run the scaffolder with stdin closed, as a script would.
 *
 * @param {string[]} args Command-line arguments.
 * @param {string} cwd Working directory.
 * @returns {{status: number|null, stdout: string, stderr: string}} Process result.
 */
function runCli(args, cwd)
{
	const result = spawnSync(process.execPath, [bin, ...args], {cwd, encoding: 'utf8', stdio: ['ignore', 'pipe', 'pipe']});
	return {status: result.status, stdout: result.stdout, stderr: result.stderr};
}

test('--list names every installed theme once', () => {
	const result = runCli(['--list'], tmpdir());
	assert.equal(result.status, 0);
	const lines = result.stdout.trim().split('\n');
	assert.ok(lines.length >= 1);
	const names = lines.map((line) => line.split(/\s+/)[0]);
	assert.equal(new Set(names).size, names.length);
	assert.ok(names.includes('semantic'));
});

test('scaffolds a site and rewrites the config', () => {
	const root = mkdtempSync(join(tmpdir(), 'create-smgen-site-'));
	try
	{
		const result = runCli(['my-site', '--theme', 'semantic', '--name', 'Probe "Site"'], root);
		assert.equal(result.status, 0, result.stderr);
		const site = join(root, 'my-site');
		assert.ok(existsSync(join(site, '.smgen.yaml')));
		assert.ok(existsSync(join(site, 'templates', 'page.php')));
		assert.ok(existsSync(join(site, 'README.md')));
		assert.ok(!existsSync(join(site, 'package.json')));
		assert.ok(!existsSync(join(site, 'theme.json')));
		assert.ok(!existsSync(join(site, 'LICENSE')));
		const config = readFileSync(join(site, '.smgen.yaml'), 'utf8');
		assert.match(config, /^PRODUCT_NAME: "Probe \\"Site\\""$/m);
		assert.match(config, /^BASE_URL: ''$/m);
		assert.match(config, /^OUTPUT_DIR: "\.\/docs"$/m);
		assert.match(readFileSync(join(site, 'README.md'), 'utf8'), /^# Probe "Site"/);
		assert.match(result.stdout, /Next steps/);
	}
	finally
	{
		rmSync(root, {recursive: true, force: true});
	}
});

test('refuses an occupied directory and an unknown theme', () => {
	const root = mkdtempSync(join(tmpdir(), 'create-smgen-site-'));
	try
	{
		mkdirSync(join(root, 'busy'));
		writeFileSync(join(root, 'busy', 'keep.txt'), 'x');
		assert.equal(runCli(['busy', '--theme', 'semantic'], root).status, 2);
		assert.equal(runCli(['fresh', '--theme', 'nope'], root).status, 1);
		assert.ok(!existsSync(join(root, 'fresh')));
		assert.equal(runCli(['fresh'], root).status, 1);
	}
	finally
	{
		rmSync(root, {recursive: true, force: true});
	}
});

test('defaults the name from the directory', () => {
	const root = mkdtempSync(join(tmpdir(), 'create-smgen-site-'));
	try
	{
		assert.equal(runCli(['harbour-notes', '--theme', 'semantic'], root).status, 0);
		assert.match(readFileSync(join(root, 'harbour-notes', '.smgen.yaml'), 'utf8'), /^PRODUCT_NAME: "Harbour Notes"$/m);
	}
	finally
	{
		rmSync(root, {recursive: true, force: true});
	}
});
