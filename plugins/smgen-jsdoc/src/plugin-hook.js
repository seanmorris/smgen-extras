// @ts-check

import fs from 'node:fs/promises';
import {buildDocumentation} from './build.js';
import {loadConfig} from './config.js';
import {writeEphemeral} from './output.js';
import {formatDiagnostics} from './render.js';

/**
 * Read SMGen's prepare context and write this build's generated pages.
 *
 * @returns {Promise<void>} Completion after generated pages are installed.
 */
export async function runPrepareHook()
{
	const contextPath = process.env.SMGEN_PLUGIN_CONTEXT;
	if(contextPath === undefined || contextPath === '') throw new Error('SMGEN_PLUGIN_CONTEXT must name a readable build context.');
	const context = /** @type {unknown} */ (JSON.parse(await fs.readFile(contextPath, 'utf8')));
	if(!isRecord(context) || context.apiVersion !== 'smgen/v1' || context.phase !== 'prepare') throw new Error('smgen-jsdoc requires an smgen/v1 prepare context.');
	const plugin = requiredRecord(context.plugin, 'plugin context');
	if(plugin.id !== 'smgen-jsdoc') throw new Error('Plugin context ID does not match smgen-jsdoc.');
	const project = requiredRecord(context.project, 'project context');
	const paths = requiredRecord(context.paths, 'path context');
	const options = isRecord(plugin.options) ? plugin.options : {};
	for(const key of Object.keys(options))
	{
		if(key !== 'config') throw new Error(`Unknown smgen-jsdoc option: ${key}`);
	}
	if(typeof project.root !== 'string' || typeof paths.generatedPages !== 'string') throw new Error('Plugin context is missing project or generated-page paths.');
	const configName = options.config === undefined ? '.smgen-jsdoc.yaml' : options.config;
	if(typeof configName !== 'string' || configName === '') throw new Error('smgen-jsdoc config option must be a nonempty path.');
	const config = await loadConfig(project.root, configName);
	const result = await buildDocumentation(config);
	for(const line of formatDiagnostics(result.graph)) process.stdout.write(line + '\n');
	if(result.graph.diagnostics.some((diagnostic) => diagnostic.severity === 'error')) throw new Error('Documentation policy rejected the generated graph.');
	await writeEphemeral(paths.generatedPages, config, result.pages);
	process.stdout.write(`smgen-jsdoc generated ${result.pages.length} page(s) from ${result.files.length} JavaScript file(s).\n`);
}

/**
 * Require an ordinary object from the untrusted plugin context.
 *
 * @param {unknown} value Untrusted context value.
 * @param {string} label Diagnostic label.
 * @returns {Record<string,unknown>} Validated context mapping.
 */
function requiredRecord(value, label)
{
	if(!isRecord(value)) throw new Error(`Invalid ${label}.`);
	return value;
}

/**
 * Report whether an unknown context value is an ordinary object.
 *
 * @param {unknown} value Untrusted context value.
 * @returns {value is Record<string,unknown>} Whether the value is a mapping.
 */
function isRecord(value)
{
	return value !== null && typeof value === 'object' && !Array.isArray(value);
}
