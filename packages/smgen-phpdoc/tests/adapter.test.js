// @ts-check

import assert from 'node:assert/strict';
import path from 'node:path';
import test from 'node:test';
import {fileURLToPath} from 'node:url';
import {analyze, validateGraph} from '../src/adapter.js';
import {discoverSources, loadConfig} from '../src/config.js';
import {renderGraph} from '../src/render.js';

/** @import {DocumentationGraph, GraphMember, GraphSymbol} from '../src/types.js' */

const fixture = path.join(path.dirname(fileURLToPath(import.meta.url)), 'fixtures', 'project');

test('static adapter covers declarations, rich PHPDoc, and runtime-only diagnostics', async () => {
	const config = await loadConfig(fixture);
	const files = await discoverSources(config);
	const graph = await analyze(config, files);
	const names = graph.symbols.map((symbol) => symbol.name);
	assert.deepEqual(names, [
		'Acme\\Widget',
		'Acme\\WIDGET_RETRIES',
		'Acme\\WidgetStatus',
		'Acme\\runtime_build',
		'Acme\\widget',
		'Acme\\Contracts\\Renderable',
		'Acme\\Support\\ClockTrait',
	]);
	const widget = requiredSymbol(graph, 'Acme\\Widget');
	assert.equal(widget.docs.summary, 'Stores and renders one named widget.');
	assert.equal(widget.implements[0].references[0], 'Acme\\Contracts\\Renderable');
	assert.equal(/** @type {{types?:Array<{references?:string[]}>}} */ (widget.traits[0]).types?.[0]?.references?.[0], 'Acme\\Support\\ClockTrait');
	assert.ok(widget.members.some((member) => member.id === 'php:property:Acme\\Widget::$name' && member.flags.includes('promoted')));
	assert.ok(widget.members.some((member) => member.id === 'php:property:Acme\\Widget::$labels' && member.virtual));
	assert.ok(widget.members.some((member) => member.id === 'php:method:Acme\\Widget::make' && member.virtual));
	const render = requiredMember(widget, 'php:method:Acme\\Widget::render');
	assert.equal(/** @type {{description?:unknown}|null|undefined} */ (render.parameters[0].docs)?.description, 'Text placed before the name.');
	assert.equal(render.docs.returns?.type.display, 'non-empty-string');
	assert.ok(graph.diagnostics.some((diagnostic) => diagnostic.code === 'runtime.class-alias'));
	assert.ok(graph.diagnostics.some((diagnostic) => diagnostic.code === 'runtime.conditional-declaration'));
});

test('renderer creates linked SMGen pages without browser assets', async () => {
	const config = await loadConfig(fixture);
	const graph = await analyze(config, await discoverSources(config));
	const {pages} = await renderGraph(graph, config);
	const widget = pages.find((page) => page.path === 'classes/acme/widget.md');
	assert.ok(widget);
	assert.match(widget.content, /^---\ntitle: Acme\\Widget/m);
	assert.match(widget.content, /smgen-assistant: true/);
	assert.match(widget.content, /TechArticle/);
	assert.match(widget.content, /interfaces\/acme\/contracts\/renderable\.html/);
	assert.match(widget.content, /traits\/acme\/support\/clock-trait\.html/);
	assert.match(widget.content, /blob\/0123456789abcdef0123456789abcdef01234567\/src\/Widget\.php#L/);
	assert.match(widget.content, /\| `\$prefix` \|/);
	assert.doesNotMatch(widget.content, /<script|<link rel=["']stylesheet/i);
	const landing = pages.find((page) => page.path === 'index.md');
	assert.ok(landing);
	assert.match(landing.content, /namespaces\/acme\//);
});

test('graph and route collisions stop before files are written', async () => {
	assert.throws(() => validateGraph({schemaVersion: 2, package: {}, diagnostics: [], symbols: []}), /unsupported graph schema/);
	const config = await loadConfig(fixture);
	const graph = await analyze(config, await discoverSources(config));
	const colliding = {
		...config,
		routes: {overrides: {
			'php:class:Acme\\Widget': 'same/path',
			'php:enum:Acme\\WidgetStatus': 'SAME/PATH',
		}},
	};
	await assert.rejects(renderGraph(graph, colliding), /Route collision/);
});

/**
 * Return one named symbol or fail with a focused test message.
 *
 * @param {DocumentationGraph} graph Documentation graph under test.
 * @param {string} name Qualified symbol name.
 * @returns {GraphSymbol} Required symbol.
 */
function requiredSymbol(graph, name)
{
	const symbol = graph.symbols.find((candidate) => candidate.name === name);
	assert.ok(symbol, `Missing symbol ${name}`);
	return symbol;
}

/**
 * Return one stable member ID or fail with a focused test message.
 *
 * @param {GraphSymbol} symbol Owning symbol.
 * @param {string} id Stable member ID.
 * @returns {GraphMember} Required member.
 */
function requiredMember(symbol, id)
{
	const member = symbol.members.find((candidate) => candidate.id === id);
	assert.ok(member, `Missing member ${id}`);
	return member;
}
