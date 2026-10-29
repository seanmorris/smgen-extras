// @ts-check

import {analyze} from './analyzer.js';
import {discoverSources} from './config.js';
import {renderGraph} from './render.js';

/** @import {JsDocConfig} from './types.js' */

/**
 * Analyze configured JavaScript and render every documentation page in memory.
 *
 * @param {JsDocConfig} config Normalized project configuration.
 * @returns {Promise<{files:import('./types.js').SourceFile[],graph:import('./types.js').DocumentationGraph,pages:import('./types.js').RenderedPage[],timings:{discoveryMs:number,analysisMs:number,renderMs:number,totalMs:number}}>} Complete read-only build.
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
 * Convert two monotonic timestamps into fractional milliseconds.
 *
 * @param {bigint} start Starting timestamp.
 * @param {bigint} end Ending timestamp.
 * @returns {number} Elapsed milliseconds.
 */
function milliseconds(start, end)
{
	return Number(end - start) / 1_000_000;
}
