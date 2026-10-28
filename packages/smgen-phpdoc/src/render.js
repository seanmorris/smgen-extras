// @ts-check

import path from 'node:path';
import {pathToFileURL} from 'node:url';
import {stringify} from 'yaml';

/** @import {Documentation, DocumentationGraph, GraphMember, GraphSymbol, PhpDocConfig, RenderedPage, TypeReference} from './types.js' */

/**
 * Render a validated graph into deterministic SMGen Markdown pages.
 *
 * @param {DocumentationGraph} graph Validated documentation graph.
 * @param {PhpDocConfig} config Normalized project configuration.
 * @returns {Promise<{pages:RenderedPage[],routes:Map<string,string>}>} Rendered pages and symbol routes.
 */
export async function renderGraph(graph, config)
{
	const routes = assignRoutes(graph, config);
	/** @type {RenderedPage[]} */
	const pages = [];
	const namespaces = groupedNamespaces(graph.symbols);
	const landing = pageShell('index.md', 'index', graph.package.title, graph.package.description, 'landing', null, null,
		renderLanding(graph, namespaces, routes, config));
	pages.push(await finishPage(landing, graph, config));

	if(config.grouping === 'namespace')
	{
		for(const [namespace, symbols] of namespaces)
		{
			const route = namespaceRoute(namespace);
			const title = namespace === '' ? 'Global namespace' : namespace;
			const summary = namespace === '' ? 'Global PHP declarations.' : `Declarations in the ${namespace} namespace.`;
			const page = pageShell(`${route}.md`, route, title, summary, 'namespace', null, namespace,
				renderNamespace(namespace, symbols, routes));
			pages.push(await finishPage(page, graph, config));
		}
	}

	for(const symbol of graph.symbols)
	{
		const route = routes.get(symbol.id);
		if(route === undefined) throw new Error(`Missing route for ${symbol.id}.`);
		const page = pageShell(`${route}.md`, route, symbol.name, summaryFor(symbol), 'symbol', symbol, symbol.namespace,
			renderSymbol(symbol, graph, routes, config));
		pages.push(await finishPage(page, graph, config));
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
 * Format graph diagnostics for terminal output with source locations.
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
 * Assign stable routes to every symbol and reject exact or folded collisions.
 *
 * @param {DocumentationGraph} graph Validated graph.
 * @param {PhpDocConfig} config Normalized configuration.
 * @returns {Map<string,string>} Stable symbol ID to route mapping.
 */
function assignRoutes(graph, config)
{
	/** @type {Map<string,string>} */
	const routes = new Map();
	/** @type {Map<string,string>} */
	const folded = new Map();
	for(const symbol of graph.symbols)
	{
		const configured = config.routes.overrides[symbol.id];
		const route = configured ?? defaultSymbolRoute(symbol);
		const key = route.toLocaleLowerCase('en-US');
		if(folded.has(key))
		{
			throw new Error(`Route collision between ${folded.get(key)} and ${symbol.id}: ${route}`);
		}
		folded.set(key, symbol.id);
		routes.set(symbol.id, route);
	}
	return routes;
}

/**
 * Derive a stable kind-qualified route from one PHP symbol.
 *
 * @param {GraphSymbol} symbol PHP declaration.
 * @returns {string} Extensionless relative route.
 */
function defaultSymbolRoute(symbol)
{
	/** @type {Record<string,string>} */
	const plurals = {class: 'classes', interface: 'interfaces', trait: 'traits', enum: 'enums', function: 'functions', constant: 'constants'};
	const plural = plurals[symbol.kind] ?? 'symbols';
	const parts = symbol.name.split('\\').map(slug).filter(Boolean);
	return [plural, ...parts].join('/');
}

/**
 * Derive a nested namespace index route.
 *
 * @param {string} namespace Qualified namespace or an empty global namespace.
 * @returns {string} Namespace index route.
 */
function namespaceRoute(namespace)
{
	const parts = namespace === '' ? ['global'] : namespace.split('\\').map(slug).filter(Boolean);
	return ['namespaces', ...parts, 'index'].join('/');
}

/**
 * Group symbols by namespace in deterministic display order.
 *
 * @param {GraphSymbol[]} symbols Sorted graph symbols.
 * @returns {Map<string,GraphSymbol[]>} Namespace groups.
 */
function groupedNamespaces(symbols)
{
	/** @type {Map<string,GraphSymbol[]>} */
	const groups = new Map();
	for(const symbol of symbols)
	{
		const group = groups.get(symbol.namespace) ?? [];
		group.push(symbol);
		groups.set(symbol.namespace, group);
	}
	return new Map([...groups.entries()].sort(([left], [right]) => left.localeCompare(right)));
}

/**
 * Render the package landing page with namespace and symbol entry points.
 *
 * @param {DocumentationGraph} graph Validated graph.
 * @param {Map<string,GraphSymbol[]>} namespaces Namespace groups.
 * @param {Map<string,string>} routes Symbol routes.
 * @param {PhpDocConfig} config Normalized configuration.
 * @returns {string} Markdown body.
 */
function renderLanding(graph, namespaces, routes, config)
{
	let body = `# ${escapeHeading(graph.package.title)}\n\n${prose(graph.package.description, config)}\n`;
	if(graph.package.version !== '') body += `\nVersion ${code(graph.package.version)}.\n`;
	if(config.grouping === 'namespace')
	{
		body += '\n## Namespaces\n\n';
		for(const [namespace, symbols] of namespaces)
		{
			const title = namespace === '' ? 'Global namespace' : namespace;
			body += `- [${code(title)}](${relativeLink('index', namespaceRoute(namespace))}) — ${symbols.length} declaration${symbols.length === 1 ? '' : 's'}\n`;
		}
	}
	body += '\n## Declarations\n\n';
	for(const symbol of graph.symbols)
	{
		const route = routes.get(symbol.id);
		if(route === undefined) continue;
		body += `- [${code(symbol.name)}](${relativeLink('index', route)}) — ${inline(summaryFor(symbol))}\n`;
	}
	return body;
}

/**
 * Render one namespace index and its declaration list.
 *
 * @param {string} namespace Qualified namespace.
 * @param {GraphSymbol[]} symbols Namespace declarations.
 * @param {Map<string,string>} routes Symbol routes.
 * @returns {string} Markdown body.
 */
function renderNamespace(namespace, symbols, routes)
{
	const title = namespace === '' ? 'Global namespace' : namespace;
	const current = namespaceRoute(namespace);
	let body = `# ${escapeHeading(title)}\n\n[API reference](${relativeLink(current, 'index')})\n\n`;
	body += namespace === '' ? 'These declarations are in the global namespace.\n' : `These declarations are in ${code(namespace)}.\n`;
	for(const kind of ['class', 'interface', 'trait', 'enum', 'function', 'constant'])
	{
		const matching = symbols.filter((symbol) => symbol.kind === kind);
		if(matching.length === 0) continue;
		body += `\n## ${headingPlural(kind)}\n\n`;
		for(const symbol of matching)
		{
			const route = routes.get(symbol.id);
			if(route === undefined) continue;
			body += `- [${code(symbol.shortName)}](${relativeLink(current, route)}) — ${inline(summaryFor(symbol))}\n`;
		}
	}
	return body;
}

/**
 * Render one type, function, or constant page from normalized graph data.
 *
 * @param {GraphSymbol} symbol Declaration being rendered.
 * @param {DocumentationGraph} graph Complete graph.
 * @param {Map<string,string>} routes Symbol routes.
 * @param {PhpDocConfig} config Normalized configuration.
 * @returns {string} Markdown body.
 */
function renderSymbol(symbol, graph, routes, config)
{
	const current = routes.get(symbol.id);
	if(current === undefined) throw new Error(`Missing route for ${symbol.id}.`);
	let body = `# ${escapeHeading(symbol.name)}\n\n`;
	if(symbol.docs.deprecated !== '') body += `> **Deprecated:** ${prose(symbol.docs.deprecated, config)}\n\n`;
	if(symbol.conditional) body += '> This declaration is conditional. Static analysis cannot prove that it exists in every runtime.\n\n';
	body += `${prose(summaryFor(symbol), config)}\n`;
	if(symbol.docs.description !== '') body += `\n${prose(symbol.docs.description, config)}\n`;
	const source = sourceUrl(symbol.source, config);
	if(source !== '') body += `\n[View source](${source})\n`;
	body += `\n## Declaration\n\n${fence(symbol.signature, 'php')}\n`;
	if(symbol.attributes.length > 0)
	{
		body += '\n**Attributes:** ' + symbol.attributes.map((attribute) => code(String(attribute.name ?? 'attribute'))).join(', ') + '\n';
	}
	if(symbol.extends.length > 0) body += `\n**Extends:** ${symbol.extends.map((type) => typeMarkup(type, current, graph, routes)).join(', ')}\n`;
	if(symbol.implements.length > 0) body += `\n**Implements:** ${symbol.implements.map((type) => typeMarkup(type, current, graph, routes)).join(', ')}\n`;
	if(symbol.traits.length > 0)
	{
		const names = symbol.traits.flatMap((relationship) => Array.isArray(relationship.types) ? relationship.types : []);
		body += `\n**Uses:** ${names.map((type) => isType(type) ? typeMarkup(type, current, graph, routes) : code(String(type))).join(', ')}\n`;
	}
	if(symbol.kind === 'function')
	{
		body += callableDetails(/** @type {Array<Record<string,unknown>>} */ (symbol.parameters ?? []), /** @type {TypeReference|null} */ (symbol.returnType ?? null), symbol.docs, current, graph, routes, config);
	}
	/** @type {Array<[string,GraphMember[]]>} */
	const groups = [
		['Cases', symbol.members.filter((member) => member.kind === 'case')],
		['Constants', symbol.members.filter((member) => member.kind === 'constant')],
		['Properties', symbol.members.filter((member) => member.kind === 'property')],
		['Methods', symbol.members.filter((member) => member.kind === 'method')],
	];
	for(const [heading, members] of groups)
	{
		if(members.length === 0) continue;
		body += `\n## ${heading}\n`;
		for(const member of members)
		{
			body += renderMember(/** @type {GraphMember} */ (member), symbol, current, graph, routes, config);
		}
	}
	if(symbol.docs.see.length > 0)
	{
		body += '\n## See also\n\n';
		for(const related of symbol.docs.see) body += `- ${seeMarkup(related, symbol, current, graph, routes)}\n`;
	}
	return body;
}

/**
 * Render one member declaration, prose, and callable detail tables.
 *
 * @param {GraphMember} member Member being rendered.
 * @param {GraphSymbol} symbol Owning declaration.
 * @param {string} current Current route.
 * @param {DocumentationGraph} graph Complete graph.
 * @param {Map<string,string>} routes Symbol routes.
 * @param {PhpDocConfig} config Normalized configuration.
 * @returns {string} Markdown member section.
 */
function renderMember(member, symbol, current, graph, routes, config)
{
	const inherited = effectiveDocumentation(member);
	let body = `\n### ${member.kind === 'property' ? code('$' + member.name) : code(member.name + (member.kind === 'method' ? '()' : ''))} {#${member.anchor}}\n\n`;
	if(member.virtual) body += '*Documented virtual member.*\n\n';
	if(member.docs.deprecated !== '') body += `> **Deprecated:** ${prose(member.docs.deprecated, config)}\n\n`;
	if(inherited.docs.summary !== '') body += `${prose(inherited.docs.summary, config)}\n`;
	if(inherited.docs.description !== '') body += `\n${prose(inherited.docs.description, config)}\n`;
	if(inherited.from !== null)
	{
		const inheritedTarget = inherited.from.split('::', 1)[0].replace(/^php:[^:]+:/, '');
		const inheritedSymbol = graph.symbols.find((candidate) => candidate.name === inheritedTarget);
		const inheritedRoute = inheritedSymbol === undefined ? undefined : routes.get(inheritedSymbol.id);
		body += inheritedRoute === undefined
			? `\nDocumentation inherited from ${code(inherited.from)}.\n`
			: `\nDocumentation inherited from [${code(inheritedTarget)}](${relativeLink(current, inheritedRoute)}).\n`;
	}
	body += `\n${fence(member.signature, 'php')}\n`;
	body += callableDetails(member.parameters, member.returnType, inherited.docs, current, graph, routes, config);
	if(member.type !== null) body += `\n**Type:** ${typeMarkup(member.docs.var?.type ?? member.type, current, graph, routes)}\n`;
	if(member.docs.see.length > 0)
	{
		body += `\n**See:** ${member.docs.see.map((related) => seeMarkup(related, symbol, current, graph, routes)).join('; ')}\n`;
	}
	const source = sourceUrl(member.source, config);
	if(source !== '') body += `\n[Source](${source})\n`;
	return body;
}

/**
 * Render parameter, return, and thrown-type details for one callable.
 *
 * @param {Array<Record<string,unknown>>} parameters Normalized parameters.
 * @param {TypeReference|null} returnType Native return type.
 * @param {Documentation} docs Effective documentation.
 * @param {string} current Current route.
 * @param {DocumentationGraph} graph Complete graph.
 * @param {Map<string,string>} routes Symbol routes.
 * @param {PhpDocConfig} config Normalized configuration.
 * @returns {string} Markdown callable details.
 */
function callableDetails(parameters, returnType, docs, current, graph, routes, config)
{
	let body = '';
	if(parameters.length > 0)
	{
		body += '\n| Parameter | Type | Description |\n|---|---|---|\n';
		for(const parameter of parameters)
		{
			const name = typeof parameter.name === 'string' ? parameter.name : 'value';
			const documented = isRecord(parameter.docs) ? parameter.docs : null;
			const type = documented !== null && isType(documented.type) ? documented.type : (isType(parameter.type) ? parameter.type : null);
			const description = documented !== null && typeof documented.description === 'string' ? documented.description : '';
			body += `| ${code('$' + name)} | ${tableCell(type === null ? code('mixed') : typeMarkup(type, current, graph, routes))} | ${tableCell(prose(description, config))} |\n`;
		}
	}
	const effectiveReturn = docs.returns?.type ?? returnType;
	if(effectiveReturn !== null)
	{
		body += `\n**Returns:** ${typeMarkup(effectiveReturn, current, graph, routes)}`;
		if(docs.returns?.description) body += ` — ${prose(docs.returns.description, config)}`;
		body += '\n';
	}
	if(docs.throws.length > 0)
	{
		body += `\n**Throws:** ${docs.throws.map((item) => `${typeMarkup(item.type, current, graph, routes)}${item.description === '' ? '' : ` — ${prose(item.description, config)}`}`).join('; ')}\n`;
	}
	return body;
}

/**
 * Return declared documentation or a separately attached inherited copy.
 *
 * @param {GraphMember} member Declared member.
 * @returns {{docs:Documentation,from:string|null}} Effective docs and origin.
 */
function effectiveDocumentation(member)
{
	if(member.docs.summary !== '' || !isRecord(member.inheritedDocs)) return {docs: member.docs, from: null};
	const inherited = member.inheritedDocs;
	if(typeof inherited.from !== 'string' || !isRecord(inherited.docs)) return {docs: member.docs, from: null};
	return {docs: /** @type {Documentation} */ (inherited.docs), from: inherited.from};
}

/**
 * Render one type with links to every known internal declaration.
 *
 * @param {TypeReference} type Type to render.
 * @param {string} current Current route.
 * @param {DocumentationGraph} graph Complete graph.
 * @param {Map<string,string>} routes Symbol routes.
 * @returns {string} Markdown type markup.
 */
function typeMarkup(type, current, graph, routes)
{
	const base = code(type.display || 'mixed');
	const links = [];
	for(const reference of type.references)
	{
		const symbol = graph.symbols.find((candidate) => candidate.name === reference);
		const route = symbol === undefined ? undefined : routes.get(symbol.id);
		if(symbol !== undefined && route !== undefined)
		{
			links.push(`[${code(symbol.shortName)}](${relativeLink(current, route)})`);
		}
	}
	return links.length === 0 ? base : `${base} (${[...new Set(links)].join(', ')})`;
}

/**
 * Render a PHPDoc @see value as an internal symbol link when possible.
 *
 * @param {string} value Raw related reference.
 * @param {GraphSymbol} owner Owning declaration.
 * @param {string} current Current route.
 * @param {DocumentationGraph} graph Complete graph.
 * @param {Map<string,string>} routes Symbol routes.
 * @returns {string} Linked or code-formatted reference.
 */
function seeMarkup(value, owner, current, graph, routes)
{
	const token = value.trim().split(/\s+/, 1)[0].replace(/\(\)$/, '');
	if(/^(?:https?:|#)/i.test(token)) return `[${inline(value)}](${token})`;
	const [typeName, memberName] = token.split('::', 2);
	const qualified = typeName.includes('\\') || owner.namespace === '' ? typeName.replace(/^\\/, '') : `${owner.namespace}\\${typeName}`;
	const symbol = graph.symbols.find((candidate) => candidate.name === typeName.replace(/^\\/, '') || candidate.name === qualified);
	const route = symbol === undefined ? undefined : routes.get(symbol.id);
	if(symbol === undefined || route === undefined) return code(value);
	const member = memberName === undefined ? undefined : symbol.members.find((candidate) => candidate.name === memberName);
	return `[${code(value)}](${relativeLink(current, route)}${member === undefined ? '' : `#${member.anchor}`})`;
}

/**
 * Assemble frontmatter and apply an optional trusted renderer module.
 *
 * @param {RenderedPage} page Unfinished rendered page.
 * @param {DocumentationGraph} graph Complete graph.
 * @param {PhpDocConfig} config Normalized configuration.
 * @returns {Promise<RenderedPage>} Page with YAML frontmatter.
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
	const metadata = frontmatter(page, graph, config);
	return {...page, content: `---\n${stringify(metadata, {lineWidth: 0})}---\n\n${body.trim()}\n`};
}

/**
 * Build SMGen metadata for generated discovery, navigation, and structured data.
 *
 * @param {RenderedPage} page Rendered page.
 * @param {DocumentationGraph} graph Validated graph.
 * @param {PhpDocConfig} config Normalized configuration.
 * @returns {Record<string,unknown>} SMGen frontmatter mapping.
 */
function frontmatter(page, graph, config)
{
	/** @type {Record<string,unknown>} */
	const metadata = {
		title: page.title,
		'description-meta': page.summary,
		'TOC': true,
	};
	if(page.kind !== 'landing') metadata.breadcrumbs = breadcrumbs(page, graph, config);
	if(config.menu.name !== null && (page.kind === 'landing' || config.menu.symbols))
	{
		metadata.menus = {
			[config.menu.name]: {
				...(config.menu.parent === null ? {} : {parent: config.menu.parent}),
				weight: config.menu.weight + (page.kind === 'landing' ? 0 : 10),
			},
		};
	}
	if(config.llms !== false)
	{
		metadata.llms = {
			section: config.llms.section,
			description: config.llms.description || page.summary,
		};
	}
	if(config.assistant) metadata.plugins = {'smgen-assistant': true};
	if(config.jsonld)
	{
		metadata.jsonld = {
			'@context': 'https://schema.org',
			'@type': 'TechArticle',
			headline: page.title,
			description: page.summary,
			...(page.symbol === null ? {} : {about: { '@type': 'SoftwareSourceCode', name: page.symbol.name, programmingLanguage: 'PHP' }}),
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
 * Build one mutable rendered-page record before frontmatter is attached.
 *
 * @param {string} pagePath Relative Markdown path.
 * @param {string} route Extensionless public route.
 * @param {string} title Page title.
 * @param {string} summary Page summary.
 * @param {'landing'|'namespace'|'symbol'} kind Page role.
 * @param {GraphSymbol|null} symbol Related symbol.
 * @param {string|null} namespace Namespace the page documents or belongs to; null for the landing page.
 * @param {string} content Markdown body.
 * @returns {RenderedPage} Unfinished page record.
 */
function pageShell(pagePath, route, title, summary, kind, symbol, namespace, content)
{
	return /** @type {RenderedPage} */ ({path: pagePath, route, title, summary, kind, symbol, namespace, content});
}

/**
 * Convert one extensionless route into its root-relative public path.
 *
 * @param {string} route Extensionless route.
 * @param {PhpDocConfig} config Normalized configuration.
 * @returns {string} Root-relative public path; index routes end with a slash.
 */
function publicPath(route, config)
{
	const prefix = '/' + config.output.replace(/^\/+|\/+$/g, '');
	if(route === 'index') return `${prefix}/`;
	if(route.endsWith('/index')) return `${prefix}/${route.slice(0, -'index'.length)}`;
	return `${prefix}/${routeFile(route)}`;
}

/**
 * Build the ancestor trail of one page: the landing page, then each enclosing namespace.
 *
 * Namespace entries link to their index pages under namespace grouping. The page
 * itself is not part of the trail.
 *
 * @param {RenderedPage} page Rendered page.
 * @param {DocumentationGraph} graph Validated graph.
 * @param {PhpDocConfig} config Normalized configuration.
 * @returns {Array<{title:string,path?:string}>} Ordered ancestors.
 */
function breadcrumbs(page, graph, config)
{
	/** @type {Array<{title:string,path?:string}>} */
	const trail = [{title: graph.package.title, path: publicPath('index', config)}];
	if(page.namespace === null) return trail;
	const segments = page.namespace === '' ? [] : page.namespace.split('\\').filter(Boolean);
	const ancestors = page.kind === 'namespace' ? segments.slice(0, -1) : segments;
	if(page.kind === 'symbol' && page.namespace === '')
	{
		trail.push(config.grouping === 'namespace'
			? {title: 'Global namespace', path: publicPath(namespaceRoute(''), config)}
			: {title: 'Global namespace'});
		return trail;
	}
	for(let depth = 1; depth <= ancestors.length; depth++)
	{
		const namespace = ancestors.slice(0, depth).join('\\');
		trail.push(config.grouping === 'namespace'
			? {title: ancestors[depth - 1], path: publicPath(namespaceRoute(namespace), config)}
			: {title: ancestors[depth - 1]});
	}
	return trail;
}

/**
 * Build an immutable source URL from template or repository settings.
 *
 * @param {{file:string,startLine:number,endLine:number}} source Declaration source span.
 * @param {PhpDocConfig} config Normalized configuration.
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
 * Convert a rendered route into a relative canonical HTML link.
 *
 * @param {string} from Current extensionless route.
 * @param {string} to Target extensionless route.
 * @returns {string} Relative public link.
 */
function relativeLink(from, to)
{
	const fromFile = routeFile(from);
	const toFile = routeFile(to);
	let relative = path.posix.relative(path.posix.dirname(fromFile), toFile);
	if(to.endsWith('/index') || to === 'index')
	{
		relative = relative.replace(/index\.html$/, '');
		if(relative === '') relative = './';
	}
	return relative;
}

/**
 * Convert one extensionless route into its physical HTML output path.
 *
 * @param {string} route Extensionless route.
 * @returns {string} Physical HTML path.
 */
function routeFile(route)
{
	return `${route}.html`;
}

/**
 * Return a useful fallback when a declaration lacks a PHPDoc summary.
 *
 * @param {GraphSymbol} symbol PHP declaration.
 * @returns {string} Document summary.
 */
function summaryFor(symbol)
{
	return symbol.docs.summary || `${headingSingular(symbol.kind)} ${symbol.name}.`;
}

/**
 * Convert a declaration kind into a singular heading word.
 *
 * @param {string} kind Graph symbol kind.
 * @returns {string} Singular display word.
 */
function headingSingular(kind)
{
	/** @type {Record<string,string>} */
	const headings = {class: 'Class', interface: 'Interface', trait: 'Trait', enum: 'Enum', function: 'Function', constant: 'Constant'};
	return headings[kind] ?? 'Declaration';
}

/**
 * Convert a declaration kind into a plural heading word.
 *
 * @param {string} kind Graph symbol kind.
 * @returns {string} Plural display word.
 */
function headingPlural(kind)
{
	/** @type {Record<string,string>} */
	const headings = {class: 'Classes', interface: 'Interfaces', trait: 'Traits', enum: 'Enums', function: 'Functions', constant: 'Constants'};
	return headings[kind] ?? 'Declarations';
}

/**
 * Convert camel-case PHP name segments into portable route segments.
 *
 * @param {string} value PHP name segment.
 * @returns {string} Portable lower-kebab route segment.
 */
function slug(value)
{
	const separated = value.replace(/([a-z0-9])([A-Z])/g, '$1-$2');
	const result = separated.replace(/[^A-Za-z0-9]+/g, '-').replace(/^-|-$/g, '').toLowerCase();
	return result || 'symbol';
}

/**
 * Wrap code with a fence longer than any run found in its contents.
 *
 * @param {string} value Code block contents.
 * @param {string} language Markdown language hint.
 * @returns {string} Safe fenced code block.
 */
function fence(value, language)
{
	const runs = String(value).match(/`+/g) ?? [];
	const length = Math.max(3, ...runs.map((run) => run.length + 1));
	const marker = '`'.repeat(length);
	return `${marker}${language}\n${value}\n${marker}`;
}

/**
 * Wrap inline code with a delimiter not present in its contents.
 *
 * @param {unknown} value Inline code value.
 * @returns {string} Safe inline code markup.
 */
function code(value)
{
	const text = String(value);
	const runs = text.match(/`+/g) ?? [];
	const marker = '`'.repeat(Math.max(1, ...runs.map((run) => run.length + 1)));
	return `${marker}${text}${marker}`;
}

/**
 * Normalize prose according to configured Markdown or plain-text policy.
 *
 * @param {unknown} value Documentation prose.
 * @param {PhpDocConfig} config Normalized configuration.
 * @returns {string} Renderable prose.
 */
function prose(value, config)
{
	const text = String(value).trim();
	if(config.prose === 'markdown') return text;
	return text.replace(/[\\`*_{}[\]()<>#+.!|>-]/g, '\\$&');
}

/**
 * Collapse prose to one safe list or table line.
 *
 * @param {unknown} value Prose value.
 * @returns {string} Single-line prose.
 */
function inline(value)
{
	return String(value).replace(/\s+/g, ' ').trim();
}

/**
 * Escape Markdown table separators and line breaks.
 *
 * @param {unknown} value Table cell value.
 * @returns {string} Safe table cell.
 */
function tableCell(value)
{
	return inline(value).replaceAll('|', '\\|');
}

/**
 * Escape heading control characters while retaining readable PHP names.
 *
 * @param {unknown} value Heading text.
 * @returns {string} Safe Markdown heading text.
 */
function escapeHeading(value)
{
	return String(value).replace(/([\\`*_{}[\]<>#])/g, '\\$1');
}

/**
 * Report whether an unknown value is an ordinary object.
 *
 * @param {unknown} value Unknown value.
 * @returns {value is Record<string,unknown>} Whether the value is a mapping.
 */
function isRecord(value)
{
	return value !== null && typeof value === 'object' && !Array.isArray(value);
}

/**
 * Report whether an unknown object is a normalized type reference.
 *
 * @param {unknown} value Unknown value.
 * @returns {value is TypeReference} Whether the value is a type reference.
 */
function isType(value)
{
	return isRecord(value) && typeof value.display === 'string' && Array.isArray(value.references);
}
