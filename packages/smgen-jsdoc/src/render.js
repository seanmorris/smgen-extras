// @ts-check

import path from 'node:path';
import {pathToFileURL} from 'node:url';
import {stringify} from 'yaml';

/** @import {Documentation, DocumentationGraph, GraphMember, GraphParameter, GraphSymbol, JsDocConfig, RenderedPage, TypeReference} from './types.js' */

/**
 * Render a validated JavaScript API graph into deterministic SMGen pages.
 *
 * @param {DocumentationGraph} graph Validated documentation graph.
 * @param {JsDocConfig} config Normalized configuration.
 * @returns {Promise<{pages:RenderedPage[],routes:Map<string,string>}>} Rendered pages and symbol routes.
 */
export async function renderGraph(graph, config)
{
	const routes = assignRoutes(graph, config);
	const modules = groupedModules(graph.symbols);
	/** @type {RenderedPage[]} */
	const pages = [];
	pages.push(await finishPage(pageShell(
		'index.md', 'index', graph.package.title, graph.package.description, 'landing', null, null,
		renderLanding(graph, modules, routes, config),
	), graph, config));

	if(config.grouping === 'module')
	{
		for(const [module, symbols] of modules)
		{
			const route = moduleRoute(module);
			const summary = `Exports documented from the ${module} module.`;
			pages.push(await finishPage(pageShell(
				`${route}.md`, route, module, summary, 'module', null, module,
				renderModule(module, symbols, routes, config),
			), graph, config));
		}
	}

	for(const symbol of graph.symbols)
	{
		const route = requiredRoute(routes, symbol.id);
		pages.push(await finishPage(pageShell(
			`${route}.md`, route, symbol.name, summaryFor(symbol), 'symbol', symbol, symbol.module,
			renderSymbol(symbol, graph, routes, config),
		), graph, config));
	}
	pages.sort((left, right) => left.path.localeCompare(right.path));
	const paths = new Set();
	for(const page of pages)
	{
		const folded = page.path.toLocaleLowerCase('en-US');
		if(paths.has(folded)) throw new Error(`Generated page path collides by case: ${page.path}`);
		paths.add(folded);
	}
	return {pages, routes};
}

/**
 * Format graph diagnostics with optional source locations for terminal output.
 *
 * @param {DocumentationGraph} graph Validated graph.
 * @returns {string[]} Terminal-ready diagnostic lines.
 */
export function formatDiagnostics(graph)
{
	return graph.diagnostics.map((diagnostic) => {
		const location = diagnostic.file === undefined ? '' : `${diagnostic.file}${diagnostic.line === undefined ? '' : `:${diagnostic.line}`}: `;
		return `${location}${diagnostic.severity} ${diagnostic.code}: ${diagnostic.message}`;
	});
}

/**
 * Assign stable routes to public symbols and reject folded collisions.
 *
 * @param {DocumentationGraph} graph Validated graph.
 * @param {JsDocConfig} config Normalized configuration.
 * @returns {Map<string,string>} Stable ID to route mapping.
 */
function assignRoutes(graph, config)
{
	/** @type {Map<string,string>} */
	const routes = new Map();
	/** @type {Map<string,string>} */
	const folded = new Map();
	for(const symbol of graph.symbols)
	{
		const route = config.routes.overrides[symbol.id] ?? defaultSymbolRoute(symbol);
		const key = route.toLocaleLowerCase('en-US');
		if(folded.has(key)) throw new Error(`Route collision between ${folded.get(key)} and ${symbol.id}: ${route}`);
		folded.set(key, symbol.id);
		routes.set(symbol.id, route);
	}
	return routes;
}

/**
 * Derive a kind-qualified route from one JavaScript export.
 *
 * @param {GraphSymbol} symbol Public symbol.
 * @returns {string} Extensionless route.
 */
function defaultSymbolRoute(symbol)
{
	/** @type {Record<string,string>} */
	const groups = {class: 'classes', function: 'functions', constant: 'values', variable: 'values', typedef: 'types', callback: 'types'};
	return [groups[symbol.kind] ?? 'symbols', ...moduleSegments(symbol.module), slug(symbol.name)].join('/');
}

/**
 * Derive the nested index route for one source module.
 *
 * @param {string} module Source module name.
 * @returns {string} Extensionless module index route.
 */
function moduleRoute(module)
{
	return ['modules', ...moduleSegments(module), 'index'].join('/');
}

/**
 * Convert a module name into portable route segments.
 *
 * @param {string} module Source module name.
 * @returns {string[]} Nonempty lower-kebab segments.
 */
function moduleSegments(module)
{
	return module.split('/').map(slug).filter(Boolean);
}

/**
 * Group public symbols by module in stable display order.
 *
 * @param {GraphSymbol[]} symbols Sorted graph symbols.
 * @returns {Map<string,GraphSymbol[]>} Module groups.
 */
function groupedModules(symbols)
{
	/** @type {Map<string,GraphSymbol[]>} */
	const groups = new Map();
	for(const symbol of symbols)
	{
		const group = groups.get(symbol.module) ?? [];
		group.push(symbol);
		groups.set(symbol.module, group);
	}
	return new Map([...groups.entries()].sort(([left], [right]) => left.localeCompare(right)));
}

/**
 * Render the package landing page and its module and export entry points.
 *
 * @param {DocumentationGraph} graph Complete graph.
 * @param {Map<string,GraphSymbol[]>} modules Module groups.
 * @param {Map<string,string>} routes Symbol routes.
 * @param {JsDocConfig} config Normalized configuration.
 * @returns {string} Markdown body.
 */
function renderLanding(graph, modules, routes, config)
{
	let body = config.bodyHeading ? `# ${escapeHeading(graph.package.title)}\n\n` : '';
	body += `${prose(graph.package.description, config)}\n`;
	if(graph.package.version !== '') body += `\nVersion ${code(graph.package.version)}.\n`;
	if(config.grouping === 'module')
	{
		body += '\n## Modules\n\n';
		for(const [module, symbols] of modules)
		{
			body += `- [${code(module)}](${relativeLink('index', moduleRoute(module))}) — ${symbols.length} export${symbols.length === 1 ? '' : 's'}\n`;
		}
	}
	body += '\n## Exports\n\n';
	for(const symbol of graph.symbols)
	{
		body += `- [${code(symbol.name)}](${relativeLink('index', requiredRoute(routes, symbol.id))}) from ${code(symbol.module)} — ${inline(summaryFor(symbol))}\n`;
	}
	return body;
}

/**
 * Render one module index grouped by public symbol kind.
 *
 * @param {string} module Module name.
 * @param {GraphSymbol[]} symbols Public symbols in the module.
 * @param {Map<string,string>} routes Symbol routes.
 * @param {JsDocConfig} config Normalized configuration.
 * @returns {string} Markdown body.
 */
function renderModule(module, symbols, routes, config)
{
	const current = moduleRoute(module);
	let body = config.bodyHeading ? `# ${escapeHeading(module)}\n\n` : '';
	body += `[API reference](${relativeLink(current, 'index')})\n\nExports documented from ${code(module)}.\n`;
	for(const kind of ['class', 'function', 'constant', 'variable', 'typedef', 'callback'])
	{
		const matching = symbols.filter((symbol) => symbol.kind === kind);
		if(matching.length === 0) continue;
		body += `\n## ${pluralHeading(kind)}\n\n`;
		for(const symbol of matching)
		{
			body += `- [${code(symbol.name)}](${relativeLink(current, requiredRoute(routes, symbol.id))}) — ${inline(summaryFor(symbol))}\n`;
		}
	}
	return body;
}

/**
 * Render one export page with declaration, callable details, and class members.
 *
 * @param {GraphSymbol} symbol Public symbol.
 * @param {DocumentationGraph} graph Complete graph.
 * @param {Map<string,string>} routes Symbol routes.
 * @param {JsDocConfig} config Normalized configuration.
 * @returns {string} Markdown body.
 */
function renderSymbol(symbol, graph, routes, config)
{
	const current = requiredRoute(routes, symbol.id);
	let body = config.bodyHeading ? `# ${escapeHeading(symbol.name)}\n\n` : '';
	if(symbol.docs.deprecated !== '') body += `> **Deprecated:** ${prose(symbol.docs.deprecated, config)}\n\n`;
	body += `${prose(summaryFor(symbol), config)}\n`;
	if(symbol.docs.description !== '') body += `\n${prose(symbol.docs.description, config)}\n`;
	body += `\nExported from ${code(symbol.module)}.\n`;
	const source = sourceUrl(symbol.source, config);
	if(source !== '') body += `\n[View source](${source})\n`;
	body += `\n## Declaration\n\n${fence(symbol.signature, 'js')}\n`;
	if(symbol.type !== null) body += `\n**Type:** ${typeMarkup(symbol.type, symbol, current, graph, routes)}\n`;
	body += callableDetails(symbol.parameters, symbol.returnType, symbol.docs, symbol, current, graph, routes, config);
	if(symbol.docs.examples.length > 0)
	{
		body += '\n## Examples\n';
		for(const example of symbol.docs.examples) body += `\n${renderExample(example)}\n`;
	}
	if(symbol.members.length > 0)
	{
		const properties = symbol.members.filter((member) => ['property', 'getter', 'setter'].includes(member.kind));
		const methods = symbol.members.filter((member) => ['constructor', 'method'].includes(member.kind));
		if(properties.length > 0)
		{
			body += '\n## Properties\n';
			for(const member of properties) body += renderMember(member, symbol, current, graph, routes, config);
		}
		if(methods.length > 0)
		{
			body += '\n## Methods\n';
			for(const member of methods) body += renderMember(member, symbol, current, graph, routes, config);
		}
	}
	if(symbol.docs.see.length > 0)
	{
		body += '\n## See also\n\n';
		for(const value of symbol.docs.see) body += `- ${seeMarkup(value, symbol, current, graph, routes)}\n`;
	}
	return body;
}

/**
 * Render one class member and its own source link.
 *
 * @param {GraphMember} member Class member.
 * @param {GraphSymbol} owner Owning class.
 * @param {string} current Current route.
 * @param {DocumentationGraph} graph Complete graph.
 * @param {Map<string,string>} routes Symbol routes.
 * @param {JsDocConfig} config Normalized configuration.
 * @returns {string} Markdown member section.
 */
function renderMember(member, owner, current, graph, routes, config)
{
	const label = member.kind === 'constructor' ? 'constructor()' : member.kind === 'method' ? `${member.name}()` : member.name;
	let body = `\n### ${code(label)} {#${member.anchor}}\n\n`;
	if(member.docs.deprecated !== '') body += `> **Deprecated:** ${prose(member.docs.deprecated, config)}\n\n`;
	if(member.docs.summary !== '') body += `${prose(member.docs.summary, config)}\n`;
	if(member.docs.description !== '') body += `\n${prose(member.docs.description, config)}\n`;
	body += `\n${fence(member.signature, 'js')}\n`;
	if(member.type !== null) body += `\n**Type:** ${typeMarkup(member.type, owner, current, graph, routes)}\n`;
	body += callableDetails(member.parameters, member.returnType, member.docs, owner, current, graph, routes, config);
	if(member.docs.examples.length > 0)
	{
		for(const example of member.docs.examples) body += `\n${renderExample(example)}\n`;
	}
	const source = sourceUrl(member.source, config);
	if(source !== '') body += `\n[Source](${source})\n`;
	return body;
}

/**
 * Render parameter, return, and thrown-value documentation for one callable.
 *
 * @param {GraphParameter[]} parameters Callable parameters.
 * @param {TypeReference|null} returnType Effective return type.
 * @param {Documentation} docs Parsed JSDoc.
 * @param {GraphSymbol} owner Owning public symbol.
 * @param {string} current Current route.
 * @param {DocumentationGraph} graph Complete graph.
 * @param {Map<string,string>} routes Symbol routes.
 * @param {JsDocConfig} config Normalized configuration.
 * @returns {string} Markdown details.
 */
function callableDetails(parameters, returnType, docs, owner, current, graph, routes, config)
{
	let body = '';
	if(parameters.length > 0)
	{
		body += '\n| Parameter | Type | Description |\n|---|---|---|\n';
		for(const parameter of parameters)
		{
			const badges = [parameter.optional ? 'optional' : '', parameter.rest ? 'rest' : ''].filter(Boolean);
			const name = badges.length === 0 ? code(parameter.name) : `${code(parameter.name)} _${badges.join(', ')}_`;
			body += `| ${name} | ${tableCell(typeMarkup(parameter.type, owner, current, graph, routes))} | ${tableCell(prose(parameter.docs?.description ?? '', config))} |\n`;
		}
	}
	const effectiveReturn = docs.returns?.type ?? returnType;
	if(effectiveReturn !== null)
	{
		body += `\n**Returns:** ${typeMarkup(effectiveReturn, owner, current, graph, routes)}`;
		if(docs.returns?.description) body += ` — ${prose(docs.returns.description, config)}`;
		body += '\n';
	}
	if(docs.throws.length > 0)
	{
		body += `\n**Throws:** ${docs.throws.map((item) => `${typeMarkup(item.type, owner, current, graph, routes)}${item.description === '' ? '' : ` — ${prose(item.description, config)}`}`).join('; ')}\n`;
	}
	return body;
}

/**
 * Render one type and link unambiguous project-local references.
 *
 * @param {TypeReference} type Type reference.
 * @param {GraphSymbol} owner Current public symbol.
 * @param {string} current Current route.
 * @param {DocumentationGraph} graph Complete graph.
 * @param {Map<string,string>} routes Symbol routes.
 * @returns {string} Code type plus related links.
 */
function typeMarkup(type, owner, current, graph, routes)
{
	const links = [];
	for(const reference of type.references)
	{
		const local = graph.symbols.filter((symbol) => symbol.name === reference && symbol.module === owner.module);
		const candidates = local.length > 0 ? local : graph.symbols.filter((symbol) => symbol.name === reference);
		if(candidates.length !== 1) continue;
		const target = candidates[0];
		links.push(`[${code(target.name)}](${relativeLink(current, requiredRoute(routes, target.id))})`);
	}
	return links.length === 0 ? code(type.display) : `${code(type.display)} (${[...new Set(links)].join(', ')})`;
}

/**
 * Link a `@see` value to a URL, local member, or unambiguous public symbol.
 *
 * @param {string} value Raw related reference.
 * @param {GraphSymbol} owner Owning public symbol.
 * @param {string} current Current route.
 * @param {DocumentationGraph} graph Complete graph.
 * @param {Map<string,string>} routes Symbol routes.
 * @returns {string} Linked or code-formatted value.
 */
function seeMarkup(value, owner, current, graph, routes)
{
	const token = value.trim().split(/\s+/, 1)[0].replace(/\(\)$/, '');
	if(/^https?:/i.test(token)) return `[${inline(value)}](${token})`;
	if(token.startsWith('#') || token.startsWith('.'))
	{
		const name = token.replace(/^[#.]/, '');
		const member = owner.members.find((candidate) => candidate.name === name);
		return member === undefined ? code(value) : `[${code(value)}](#${member.anchor})`;
	}
	const [moduleName, exportName] = token.split('#', 2);
	const candidates = exportName === undefined
		? graph.symbols.filter((symbol) => symbol.name === moduleName)
		: graph.symbols.filter((symbol) => symbol.module === moduleName && symbol.name === exportName);
	if(candidates.length !== 1) return code(value);
	return `[${code(value)}](${relativeLink(current, requiredRoute(routes, candidates[0].id))})`;
}

/**
 * Render an example while preserving an author-supplied fenced block.
 *
 * @param {string} example Example source.
 * @returns {string} Markdown example.
 */
function renderExample(example)
{
	const trimmed = example.trim();
	return /^(?:`{3,}|~{3,})/.test(trimmed) ? trimmed : fence(trimmed, 'js');
}

/**
 * Apply frontmatter and an optional trusted project renderer to one page.
 *
 * @param {RenderedPage} page Unfinished page.
 * @param {DocumentationGraph} graph Complete graph.
 * @param {JsDocConfig} config Normalized configuration.
 * @returns {Promise<RenderedPage>} Finished Markdown page.
 */
async function finishPage(page, graph, config)
{
	let body = page.content;
	const modulePath = config.templates[page.kind];
	if(modulePath !== '')
	{
		const absolute = path.resolve(config.root, modulePath);
		const loaded = /** @type {Record<string,unknown>} */ (await import(pathToFileURL(absolute).href));
		const renderer = loaded.default ?? loaded.render;
		if(typeof renderer !== 'function') throw new Error(`Renderer module ${modulePath} must export a function.`);
		const rendered = await renderer({page: {...page, content: body}, graph, config});
		if(typeof rendered !== 'string') throw new Error(`Renderer module ${modulePath} did not return Markdown text.`);
		body = rendered;
	}
	return {...page, content: `---\n${stringify(frontmatter(page, graph, config), {lineWidth: 0})}---\n\n${body.trim()}\n`};
}

/**
 * Build SMGen discovery, menu, assistant, and structured-data metadata.
 *
 * @param {RenderedPage} page Rendered page.
 * @param {DocumentationGraph} graph Complete graph.
 * @param {JsDocConfig} config Normalized configuration.
 * @returns {Record<string,unknown>} Frontmatter mapping.
 */
function frontmatter(page, graph, config)
{
	/** @type {Record<string,unknown>} */
	const metadata = {title: page.title, 'description-meta': page.summary, 'TOC': true};
	if(page.kind !== 'landing') metadata.breadcrumbs = breadcrumbs(page, graph, config);
	if(config.menu.name !== null && (page.kind === 'landing' || config.menu.symbols))
	{
		metadata.menus = {[config.menu.name]: {
			...(config.menu.parent === null ? {} : {parent: config.menu.parent}),
			weight: config.menu.weight + (page.kind === 'landing' ? 0 : 10),
		}};
	}
	if(config.llms !== false) metadata.llms = {section: config.llms.section, description: config.llms.description || page.summary};
	if(config.assistant) metadata.plugins = {'smgen-assistant': true};
	if(config.jsonld)
	{
		metadata.jsonld = {
			'@context': 'https://schema.org',
			'@type': 'TechArticle',
			headline: page.title,
			description: page.summary,
			...(page.symbol === null ? {} : {about: {'@type': 'SoftwareSourceCode', name: page.symbol.name, programmingLanguage: 'JavaScript'}}),
		};
	}
	if(page.symbol !== null)
	{
		const source = sourceUrl(page.symbol.source, config);
		if(source !== '') metadata['source-url'] = source;
		metadata['api-page'] = true;
	}
	return metadata;
}

/**
 * Build one rendered-page record before metadata is attached.
 *
 * @param {string} pagePath Relative Markdown path.
 * @param {string} route Extensionless route.
 * @param {string} title Page title.
 * @param {string} summary Page summary.
 * @param {'landing'|'module'|'symbol'} kind Page role.
 * @param {GraphSymbol|null} symbol Related symbol.
 * @param {string|null} module Related module.
 * @param {string} content Markdown body.
 * @returns {RenderedPage} Unfinished page record.
 */
function pageShell(pagePath, route, title, summary, kind, symbol, module, content)
{
	return {path: pagePath, route, title, summary, kind, symbol, module, content};
}

/**
 * Build the API landing and module breadcrumb trail for one page.
 *
 * @param {RenderedPage} page Rendered page.
 * @param {DocumentationGraph} graph Complete graph.
 * @param {JsDocConfig} config Normalized configuration.
 * @returns {Array<{title:string,path?:string}>} Ordered ancestors.
 */
function breadcrumbs(page, graph, config)
{
	/** @type {Array<{title:string,path?:string}>} */
	const trail = [{title: graph.package.title, path: publicPath('index', config)}];
	if(page.kind === 'symbol' && page.module !== null)
	{
		trail.push(config.grouping === 'module'
			? {title: page.module, path: publicPath(moduleRoute(page.module), config)}
			: {title: page.module});
	}
	return trail;
}

/**
 * Convert an API route into its root-relative public path.
 *
 * @param {string} route Extensionless route.
 * @param {JsDocConfig} config Normalized configuration.
 * @returns {string} Root-relative public path.
 */
function publicPath(route, config)
{
	const prefix = '/' + config.output.replace(/^\/+|\/+$/g, '');
	if(route === 'index') return `${prefix}/`;
	if(route.endsWith('/index')) return `${prefix}/${route.slice(0, -'index'.length)}`;
	return `${prefix}/${routeFile(route)}`;
}

/**
 * Build a source URL from a repository or custom template setting.
 *
 * @param {{file:string,startLine:number,endLine:number}} source Declaration source span.
 * @param {JsDocConfig} config Normalized configuration.
 * @returns {string} Source URL or an empty value.
 */
function sourceUrl(source, config)
{
	const encodedPath = source.file.split('/').map(encodeURIComponent).join('/');
	const prefix = config.source.prefix.replace(/^\/+|\/+$/g, '');
	const combined = prefix === '' ? encodedPath : `${prefix}/${encodedPath}`;
	if(config.source.template !== '')
	{
		return config.source.template
			.replaceAll('{ref}', encodeURIComponent(config.source.ref))
			.replaceAll('{path}', combined)
			.replaceAll('{line}', String(source.startLine))
			.replaceAll('{endLine}', String(source.endLine));
	}
	if(config.source.repository === '') return '';
	return `${config.source.repository.replace(/\/$/, '')}/blob/${encodeURIComponent(config.source.ref)}/${combined}#L${source.startLine}`;
}

/**
 * Convert one route into a relative canonical HTML link.
 *
 * @param {string} from Current extensionless route.
 * @param {string} to Target extensionless route.
 * @returns {string} Relative public link.
 */
function relativeLink(from, to)
{
	let relative = path.posix.relative(path.posix.dirname(routeFile(from)), routeFile(to));
	if(to === 'index' || to.endsWith('/index'))
	{
		relative = relative.replace(/index\.html$/, '');
		if(relative === '') relative = './';
	}
	return relative;
}

/**
 * Add the generated HTML extension to one route.
 *
 * @param {string} route Extensionless route.
 * @returns {string} HTML path.
 */
function routeFile(route)
{
	return `${route}.html`;
}

/**
 * Require a route that should have been assigned during rendering.
 *
 * @param {Map<string,string>} routes Route map.
 * @param {string} id Stable symbol ID.
 * @returns {string} Assigned route.
 */
function requiredRoute(routes, id)
{
	const route = routes.get(id);
	if(route === undefined) throw new Error(`Missing route for ${id}.`);
	return route;
}

/**
 * Return a useful fallback when an export lacks a JSDoc summary.
 *
 * @param {GraphSymbol} symbol Public symbol.
 * @returns {string} Page summary.
 */
function summaryFor(symbol)
{
	return symbol.docs.summary || `${singularHeading(symbol.kind)} ${symbol.name} from ${symbol.module}.`;
}

/**
 * Convert one symbol kind into a singular display word.
 *
 * @param {string} kind Symbol kind.
 * @returns {string} Singular heading.
 */
function singularHeading(kind)
{
	/** @type {Record<string,string>} */
	const headings = {class: 'Class', function: 'Function', constant: 'Constant', variable: 'Variable', typedef: 'Type', callback: 'Callback'};
	return headings[kind] ?? 'Export';
}

/**
 * Convert one symbol kind into a plural section heading.
 *
 * @param {string} kind Symbol kind.
 * @returns {string} Plural heading.
 */
function pluralHeading(kind)
{
	/** @type {Record<string,string>} */
	const headings = {class: 'Classes', function: 'Functions', constant: 'Constants', variable: 'Variables', typedef: 'Types', callback: 'Callbacks'};
	return headings[kind] ?? 'Exports';
}

/**
 * Convert a name segment into a portable lower-kebab route segment.
 *
 * @param {string} value Name segment.
 * @returns {string} Route segment.
 */
function slug(value)
{
	const separated = value.replace(/([a-z0-9])([A-Z])/g, '$1-$2');
	return separated.replace(/[^A-Za-z0-9]+/g, '-').replace(/^-|-$/g, '').toLowerCase() || 'symbol';
}

/**
 * Wrap source with a code fence longer than any run inside it.
 *
 * @param {string} value Code block contents.
 * @param {string} language Markdown language hint.
 * @returns {string} Safe fenced code block.
 */
function fence(value, language)
{
	const runs = String(value).match(/`+/g) ?? [];
	const marker = '`'.repeat(Math.max(3, ...runs.map((run) => run.length + 1)));
	return `${marker}${language}\n${value}\n${marker}`;
}

/**
 * Wrap inline code with a delimiter absent from its contents.
 *
 * @param {unknown} value Inline code value.
 * @returns {string} Safe inline code.
 */
function code(value)
{
	const text = String(value);
	const runs = text.match(/`+/g) ?? [];
	const marker = '`'.repeat(Math.max(1, ...runs.map((run) => run.length + 1)));
	return `${marker}${text}${marker}`;
}

/**
 * Apply the configured Markdown or plain-text prose policy.
 *
 * @param {unknown} value Documentation prose.
 * @param {JsDocConfig} config Normalized configuration.
 * @returns {string} Renderable prose.
 */
function prose(value, config)
{
	const text = String(value).trim();
	return config.prose === 'markdown' ? text : text.replace(/[\\`*_{}[\]()<>#+.!|>-]/g, '\\$&');
}

/**
 * Collapse prose onto one list or table line.
 *
 * @param {unknown} value Prose value.
 * @returns {string} Single-line prose.
 */
function inline(value)
{
	return String(value).replace(/\s+/g, ' ').trim();
}

/**
 * Escape Markdown table separators in one cell.
 *
 * @param {unknown} value Table cell value.
 * @returns {string} Safe table cell.
 */
function tableCell(value)
{
	return inline(value).replaceAll('|', '\\|');
}

/**
 * Escape Markdown control characters in a heading.
 *
 * @param {unknown} value Heading value.
 * @returns {string} Safe heading text.
 */
function escapeHeading(value)
{
	return String(value).replace(/([\\`*_{}[\]<>#])/g, '\\$1');
}
