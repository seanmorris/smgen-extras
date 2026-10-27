// @ts-check

import {analyze} from './adapter.js';
import {discoverSources} from './config.js';
import {renderGraph} from './render.js';

/** @import {PhpDocConfig} from './types.js' */

/**
 * Analyze configured source and render all pages without changing the project.
 *
 * @param {PhpDocConfig} config Normalized project configuration.
 * @returns {Promise<{files:import('./types.js').SourceFile[],graph:import('./types.js').DocumentationGraph,pages:import('./types.js').RenderedPage[],timings:{discoveryMs:number,analysisMs:number,renderMs:number,totalMs:number}}>} Complete in-memory build.
 */
export async function buildDocumentation(config)
{
	const started = process.hrtime.bigint();
	const files = await discoverSources(config);
	const discovered = process.hrtime.bigint();
	const graph = await analyze(config, files);
	const analyzed = process.hrtime.bigint();
	const rendered = await renderGraph(graph, config);
	const finished = process.hrtime.bigint();
	return {
		files,
		graph,
		pages: rendered.pages,
		timings: {
			discoveryMs: milliseconds(started, discovered),
			analysisMs: milliseconds(discovered, analyzed),
			renderMs: milliseconds(analyzed, finished),
			totalMs: milliseconds(started, finished),
		},
	};
}

/**
 * Convert two high-resolution timestamps into fractional milliseconds.
 *
 * @param {bigint} start Starting monotonic timestamp.
 * @param {bigint} end Ending monotonic timestamp.
 * @returns {number} Elapsed milliseconds.
 */
function milliseconds(start, end)
{
	return Number(end - start) / 1_000_000;
}
