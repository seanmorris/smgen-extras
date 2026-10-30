// @ts-check

import crypto from 'node:crypto';
import fs from 'node:fs/promises';
import path from 'node:path';
import {fileURLToPath} from 'node:url';
import {buildDocumentation} from './build.js';
import {discoverSources, loadConfig} from './config.js';
import {checkManaged, cleanManaged, installManaged} from './output.js';
import {formatDiagnostics} from './render.js';

/** @import {DocumentationGraph, JsDocConfig, SourceFile} from './types.js' */

const packageRoot = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');

/**
 * Parse and execute the public command-line interface.
 *
 * @param {string[]} argumentsList Command arguments after the executable name.
 * @returns {Promise<number>} Process exit status.
 */
export async function runCommand(argumentsList)
{
	const argumentsCopy = [...argumentsList];
	const command = argumentsCopy.shift() ?? 'help';
	if(['help', '--help', '-h'].includes(command))
	{
		process.stdout.write(usage());
		return 0;
	}
	if(['version', '--version', '-V'].includes(command))
	{
		const packageJson = JSON.parse(await fs.readFile(path.join(packageRoot, 'package.json'), 'utf8'));
		process.stdout.write(`smgen-jsdoc ${String(packageJson.version)}\n`);
		return 0;
	}
	if(!['generate', 'check', 'watch', 'clean'].includes(command)) throw new Error(`Unknown command: ${command}`);
	const options = parseOptions(argumentsCopy);
	const root = path.resolve(options.root ?? process.cwd());
	const config = await loadConfig(root, options.config ?? '.smgen-jsdoc.yaml');
	const pages = path.resolve(root, options.pages ?? 'pages');

	if(command === 'clean')
	{
		const cleaned = await cleanManaged(pages, config);
		process.stdout.write(cleaned ? `Removed ${path.join(pages, config.output)}.\n` : 'No managed JavaScript documentation was present.\n');
		return 0;
	}
	if(command === 'watch')
	{
		await watchProject(config, pages);
		return 0;
	}

	const result = await buildDocumentation(config);
	printDiagnostics(result.graph);
	if(result.graph.diagnostics.some((diagnostic) => diagnostic.severity === 'error'))
	{
		process.stderr.write('Documentation policy rejected the generated graph.\n');
		return 1;
	}
	if(command === 'check')
	{
		const check = await checkManaged(pages, config, result.pages);
		if(!check.current)
		{
			process.stderr.write(check.reason + '\n');
			return 1;
		}
		process.stdout.write(`Checked ${result.graph.symbols.length} symbols and ${result.pages.length} current page(s).\n`);
		return 0;
	}
	const installed = await installManaged(pages, config, result.pages);
	process.stdout.write(`${installed.changed ? 'Generated' : 'Unchanged'} ${result.pages.length} page(s) from ${result.files.length} JavaScript file(s) in ${result.timings.totalMs.toFixed(1)} ms.\n`);
	return 0;
}

/**
 * Parse value-bearing CLI options and reject ambiguous positional input.
 *
 * @param {string[]} argumentsList Remaining option tokens.
 * @returns {{root?:string,config?:string,pages?:string}} Parsed options.
 */
function parseOptions(argumentsList)
{
	/** @type {{root?:string,config?:string,pages?:string}} */
	const options = {};
	/** @type {Map<string,'root'|'config'|'pages'>} */
	const names = new Map([['--root', 'root'], ['--config', 'config'], ['--pages', 'pages']]);
	while(argumentsList.length > 0)
	{
		const option = argumentsList.shift();
		const key = names.get(option ?? '');
		if(key === undefined) throw new Error(`Unknown option: ${option ?? ''}`);
		const value = argumentsList.shift();
		if(value === undefined || value === '') throw new Error(`${option} requires a value.`);
		options[key] = value;
	}
	return options;
}

/**
 * Regenerate after stable source changes until the process receives a signal.
 *
 * @param {JsDocConfig} config Normalized project configuration.
 * @param {string} pages Pages directory supplied by the author.
 * @returns {Promise<void>} Completion after process termination.
 */
async function watchProject(config, pages)
{
	let previous = '';
	let active = false;
	let pending = true;
	let stopped = false;
	/** Mark the polling loop for clean termination after a process signal. */
	const stop = () => { stopped = true; };
	process.once('SIGINT', stop);
	process.once('SIGTERM', stop);
	process.stdout.write('Watching JavaScript sources for documentation changes.\n');
	while(!stopped)
	{
		const files = await discoverSources(config);
		const current = await sourceSnapshot(config, files);
		if(current !== previous)
		{
			previous = current;
			pending = true;
		}
		if(pending && !active)
		{
			active = true;
			pending = false;
			try
			{
				const result = await buildDocumentation(config);
				printDiagnostics(result.graph);
				if(result.graph.diagnostics.some((diagnostic) => diagnostic.severity === 'error'))
				{
					process.stderr.write('Documentation policy rejected this change; the prior pages remain installed.\n');
				}
				else
				{
					const installed = await installManaged(pages, config, result.pages);
					process.stdout.write(`${installed.changed ? 'Updated' : 'Checked'} ${result.pages.length} page(s).\n`);
				}
			}
			catch(error)
			{
				const message = error instanceof Error ? error.message : String(error);
				process.stderr.write(`smgen-jsdoc: ${message}\n`);
			}
			finally
			{
				active = false;
			}
		}
		await delay(300);
	}
}

/**
 * Hash file identity, size, and modification data for low-cost watch polling.
 *
 * @param {JsDocConfig} config Normalized project configuration.
 * @param {SourceFile[]} files Discovered source files.
 * @returns {Promise<string>} Snapshot digest.
 */
async function sourceSnapshot(config, files)
{
	const hash = crypto.createHash('sha256');
	const configStat = await fs.stat(config.configPath);
	hash.update(`${config.configPath}\0${configStat.size}\0${configStat.mtimeMs}\n`);
	for(const file of files)
	{
		const stat = await fs.stat(file.absolute);
		hash.update(`${file.relative}\0${stat.size}\0${stat.mtimeMs}\n`);
	}
	return hash.digest('hex');
}

/**
 * Print every static-analysis and documentation-policy diagnostic.
 *
 * @param {DocumentationGraph} graph Validated documentation graph.
 * @returns {void}
 */
function printDiagnostics(graph)
{
	const lines = formatDiagnostics(graph);
	for(let index = 0; index < lines.length; index++)
	{
		(graph.diagnostics[index]?.severity === 'error' ? process.stderr : process.stdout).write(lines[index] + '\n');
	}
}

/**
 * Wait for one polling interval without blocking signal delivery.
 *
 * @param {number} milliseconds Delay length.
 * @returns {Promise<void>} Completion after the timer fires.
 */
function delay(milliseconds)
{
	return new Promise((resolve) => setTimeout(resolve, milliseconds));
}

/**
 * Return concise help for local authoring and CI commands.
 *
 * @returns {string} Terminal help text.
 */
function usage()
{
	return `Usage:
  smgen-jsdoc generate [--root DIR] [--config FILE] [--pages DIR]
  smgen-jsdoc check [--root DIR] [--config FILE] [--pages DIR]
  smgen-jsdoc watch [--root DIR] [--config FILE] [--pages DIR]
  smgen-jsdoc clean [--root DIR] [--config FILE] [--pages DIR]
  smgen-jsdoc --version

generate statically analyzes JavaScript and atomically refreshes the owned pages subtree.
check validates source and reports generated-page drift without changing files.
watch polls configured JavaScript roots and regenerates after settled changes.
clean removes only output carrying the smgen-jsdoc ownership marker.
`;
}
