// @ts-check

import assert from 'node:assert/strict';
import path from 'node:path';
import test from 'node:test';
import {fileURLToPath} from 'node:url';
import {analyze, validateGraph} from '../src/analyzer.js';
import {discoverSources, loadConfig} from '../src/config.js';
import {renderGraph} from '../src/render.js';

/** @import {DocumentationGraph, GraphSymbol} from '../src/types.js' */

const fixture = path.join(path.dirname(fileURLToPath(import.meta.url)), 'fixtures', 'project');

test('static analysis covers exports, classes, JSDoc types, and callbacks without executing source', async () => {
	const config = await loadConfig(fixture);
	const graph = await analyze(config, await discoverSources(config));
	assert.equal(graph.diagnostics.filter((diagnostic) => diagnostic.severity === 'error').length, 0);
	assert.deepEqual(graph.symbols.map((symbol) => `${symbol.module}#${symbol.name}`), [
		'compat#compatibilityLabel',
		'courier#Courier',
		'courier#createCourier',
		'courier#DELIVERY_ATTEMPTS',
		'courier#ProgressHandler',
		'courier#TransportOptions',
		'format#formatAddress',
		'legacy#LEGACY_LABEL',
		'legacy#legacyId',
	]);
	const courier = requiredSymbol(graph, 'courier', 'Courier');
	assert.equal(courier.docs.summary, 'Compose and deliver messages through one transport.');
	assert.ok(courier.members.some((member) => member.name === 'deliver' && member.returnType?.display === 'Promise<boolean>'));
	assert.ok(courier.members.some((member) => member.name === 'normalize'));
	assert.ok(!courier.members.some((member) => member.name === '#secret'));
	const create = requiredSymbol(graph, 'courier', 'createCourier');
	assert.equal(create.parameters[0]?.docs?.description, 'Sender address.');
	assert.equal(create.returnType?.display, 'Courier');
	const options = requiredSymbol(graph, 'courier', 'TransportOptions');
	assert.ok(options.members.some((member) => member.name === 'timeout' && member.flags.includes('optional')));
});

test('renderer creates linked SMGen pages, source URLs, and JavaScript structured data', async () => {
	const config = await loadConfig(fixture);
	const graph = await analyze(config, await discoverSources(config));
	const {pages} = await renderGraph(graph, config);
	const courier = pages.find((page) => page.path === 'classes/courier/courier.md');
	assert.ok(courier);
	assert.match(courier.content, /^---\ntitle: Courier/m);
	assert.match(courier.content, /programmingLanguage: JavaScript/);
	assert.match(courier.content, /types\/courier\/transport-options\.html/);
	assert.match(courier.content, /blob\/0123456789abcdef0123456789abcdef01234567\/src\/courier\.js#L/);
	assert.match(courier.content, /const courier = new Courier/);
	assert.doesNotMatch(courier.content, /#secret/);
	const modulePage = pages.find((page) => page.path === 'modules/courier/index.md');
	assert.ok(modulePage);
	assert.match(modulePage.content, /classes\/courier\/courier\.html/);
});

test('graph validation and folded route collisions fail before output', async () => {
	assert.throws(() => validateGraph({schemaVersion: 2, package: {}, diagnostics: [], symbols: []}), /Unsupported JSDoc graph schema/);
	const config = await loadConfig(fixture);
	const graph = await analyze(config, await discoverSources(config));
	const colliding = {...config, routes: {overrides: {
		'js:class:courier#Courier': 'same/path',
		'js:function:courier#createCourier': 'SAME/PATH',
	}}};
	await assert.rejects(renderGraph(graph, colliding), /Route collision/);
});

/**
 * Return one public symbol or fail with a focused test message.
 *
 * @param {DocumentationGraph} graph Documentation graph.
 * @param {string} module Module name.
 * @param {string} name Public symbol name.
 * @returns {GraphSymbol} Required symbol.
 */
function requiredSymbol(graph, module, name)
{
	const symbol = graph.symbols.find((candidate) => candidate.module === module && candidate.name === name);
	assert.ok(symbol, `Missing ${module}#${name}`);
	return symbol;
}
