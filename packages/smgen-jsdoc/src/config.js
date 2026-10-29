// @ts-check

import fs from 'node:fs/promises';
import path from 'node:path';
import {LineCounter, parseDocument} from 'yaml';

/** @import {JsDocConfig, SourceConfig, SourceFile, ValidationLevel} from './types.js' */

const topFields = new Set([
	'version', 'package', 'sources', 'output', 'visibility', 'grouping',
	'routes', 'menu', 'llms', 'assistant', 'jsonld', 'source', 'validation',
	'prose', 'cache', 'templates',
]);

/**
 * Load, validate, and normalize one project configuration file.
 *
 * @param {string} projectRoot Project directory containing the configuration.
 * @param {string} [configName] Project-relative configuration filename.
 * @returns {Promise<JsDocConfig>} Fully normalized version 1 configuration.
 */
export async function loadConfig(projectRoot, configName = '.smgen-jsdoc.yaml')
{
	const root = await fs.realpath(path.resolve(projectRoot));
	const configPath = await resolveInput(root, configName, 'Configuration file');
	const source = await fs.readFile(configPath, 'utf8');
	const lines = new LineCounter();
	const document = parseDocument(source, {lineCounter: lines, prettyErrors: true, uniqueKeys: true});
	if(document.errors.length > 0)
	{
		throw new Error(`Invalid YAML in ${configPath}: ${document.errors[0].message}`);
	}
	const raw = document.toJS();
	const value = mapping(raw, `configuration ${configPath}`);
	rejectUnknown(value, topFields, `configuration ${configPath}`);
	if(value.version !== 1)
	{
		throw new Error(`Configuration ${configPath} requires version: 1.`);
	}

	const packageValue = mapping(value.package, 'package');
	rejectUnknown(packageValue, new Set(['title', 'description', 'version']), 'package');
	const title = requiredString(packageValue.title, 'package.title');
	const description = optionalString(packageValue.description, `${title} programming reference.`);
	const packageVersion = optionalString(packageValue.version, '');
	const sources = normalizeSources(value.sources);
	const output = safeRelativePath(optionalString(value.output, 'api'), 'output');
	const cache = safeRelativePath(optionalString(value.cache, '.smgen-jsdoc-cache'), 'cache');
	const visibility = stringList(value.visibility ?? ['public'], 'visibility');
	if(visibility.length === 0 || visibility.some((item) => !['public', 'protected'].includes(item)))
	{
		throw new Error('visibility must contain public and optionally protected.');
	}

	const routesValue = mapping(value.routes ?? {}, 'routes');
	rejectUnknown(routesValue, new Set(['overrides']), 'routes');
	const overridesValue = mapping(routesValue.overrides ?? {}, 'routes.overrides');
	/** @type {Record<string,string>} */
	const overrides = {};
	for(const [id, route] of Object.entries(overridesValue))
	{
		overrides[id] = safeRoute(requiredString(route, `route override ${id}`), `route override ${id}`);
	}

	const menuValue = mapping(value.menu ?? {}, 'menu');
	rejectUnknown(menuValue, new Set(['name', 'parent', 'weight', 'symbols']), 'menu');
	const menuName = nullableString(menuValue.name, 'menu.name');
	const menuParent = nullableString(menuValue.parent, 'menu.parent');
	const menuWeight = numberValue(menuValue.weight ?? 0, 'menu.weight');
	const llms = normalizeLlms(value.llms ?? false);
	const sourceValue = mapping(value.source ?? {}, 'source');
	rejectUnknown(sourceValue, new Set(['repository', 'ref', 'prefix', 'template']), 'source');
	const validationValue = mapping(value.validation ?? {}, 'validation');
	rejectUnknown(validationValue, new Set(['summaries', 'parameters', 'returns', 'brokenSee']), 'validation');
	const templatesValue = mapping(value.templates ?? {}, 'templates');
	rejectUnknown(templatesValue, new Set(['landing', 'module', 'symbol']), 'templates');

	/** @type {JsDocConfig} */
	const config = {
		version: 1,
		root,
		configPath,
		package: {title, description, version: packageVersion},
		sources,
		output,
		visibility: /** @type {Array<'public'|'protected'>} */ (visibility),
		grouping: /** @type {'module'|'flat'} */ (enumValue(value.grouping ?? 'module', ['module', 'flat'], 'grouping')),
		routes: {overrides},
		menu: {
			name: menuName,
			parent: menuParent,
			weight: menuWeight,
			symbols: booleanValue(menuValue.symbols ?? false, 'menu.symbols'),
		},
		llms,
		assistant: booleanValue(value.assistant ?? false, 'assistant'),
		jsonld: booleanValue(value.jsonld ?? false, 'jsonld'),
		source: {
			repository: optionalString(sourceValue.repository, ''),
			ref: optionalString(sourceValue.ref, 'HEAD'),
			prefix: optionalString(sourceValue.prefix, ''),
			template: optionalString(sourceValue.template, ''),
		},
		validation: {
			summaries: validationLevel(validationValue.summaries ?? 'warn', 'validation.summaries'),
			parameters: validationLevel(validationValue.parameters ?? 'warn', 'validation.parameters'),
			returns: validationLevel(validationValue.returns ?? 'warn', 'validation.returns'),
			brokenSee: validationLevel(validationValue.brokenSee ?? 'warn', 'validation.brokenSee'),
		},
		prose: /** @type {'markdown'|'plain'} */ (enumValue(value.prose ?? 'markdown', ['markdown', 'plain'], 'prose')),
		cache,
		templates: {
			landing: optionalString(templatesValue.landing, ''),
			module: optionalString(templatesValue.module, ''),
			symbol: optionalString(templatesValue.symbol, ''),
		},
	};

	for(const template of Object.values(config.templates))
	{
		if(template !== '') await resolveInput(root, template, 'Renderer module');
	}
	return config;
}

/**
 * Discover configured JavaScript files in stable path order without following links.
 *
 * @param {JsDocConfig} config Normalized project configuration.
 * @returns {Promise<SourceFile[]>} Stable, deduplicated JavaScript source list.
 */
export async function discoverSources(config)
{
	/** @type {Map<string,SourceFile>} */
	const files = new Map();
	for(const source of config.sources)
	{
		const sourceRoot = await resolveDirectory(config.root, source.path, `Source root ${source.path}`);
		await walkSource(config.root, sourceRoot, sourceRoot, source, files);
	}
	const result = [...files.values()].sort((left, right) => left.relative.localeCompare(right.relative));
	if(result.length === 0)
	{
		throw new Error(`No JavaScript files matched ${config.configPath}.`);
	}
	return result;
}

/**
 * Walk one source root recursively while refusing every symbolic link.
 *
 * @param {string} projectRoot Canonical project root.
 * @param {string} sourceRoot Canonical configured source root.
 * @param {string} directory Directory currently being scanned.
 * @param {SourceConfig} source Glob settings for this root.
 * @param {Map<string,SourceFile>} files Accumulated project-relative files.
 * @returns {Promise<void>} Completion after all descendants have been scanned.
 */
async function walkSource(projectRoot, sourceRoot, directory, source, files)
{
	const entries = await fs.readdir(directory, {withFileTypes: true});
	entries.sort((left, right) => left.name.localeCompare(right.name));
	for(const entry of entries)
	{
		const absolute = path.join(directory, entry.name);
		if(entry.isSymbolicLink()) continue;
		const relativeSource = slash(path.relative(sourceRoot, absolute));
		if(entry.isDirectory())
		{
			if(matchesAny(relativeSource + '/', source.exclude, true)) continue;
			await walkSource(projectRoot, sourceRoot, absolute, source, files);
			continue;
		}
		if(!entry.isFile() || !/\.(?:c|m)?js$/i.test(relativeSource)) continue;
		const included = matchesAny(relativeSource, source.include, false);
		const excluded = matchesAny(relativeSource, source.exclude, false);
		if(!included || excluded) continue;
		const relative = slash(path.relative(projectRoot, absolute));
		const module = moduleName(relativeSource);
		const prior = [...files.values()].find((file) => file.module.toLocaleLowerCase('en-US') === module.toLocaleLowerCase('en-US'));
		if(prior !== undefined) throw new Error(`Module name collision between ${prior.relative} and ${relative}: ${module}`);
		files.set(relative, {absolute, relative, module});
	}
}

/**
 * Normalize source-root definitions and their default JavaScript globs.
 *
 * @param {unknown} value Raw `sources` configuration.
 * @returns {SourceConfig[]} Validated source definitions.
 */
function normalizeSources(value)
{
	if(value === undefined)
	{
		return [{path: 'src', include: defaultIncludes(), exclude: defaultExcludes()}];
	}
	if(!Array.isArray(value) || value.length === 0)
	{
		throw new Error('sources must be a nonempty list.');
	}
	return value.map((item, index) => {
		if(typeof item === 'string')
		{
			return {path: safeRelativePath(item, `sources[${index}]`), include: defaultIncludes(), exclude: defaultExcludes()};
		}
		const source = mapping(item, `sources[${index}]`);
		rejectUnknown(source, new Set(['path', 'include', 'exclude']), `sources[${index}]`);
		return {
			path: safeRelativePath(requiredString(source.path, `sources[${index}].path`), `sources[${index}].path`),
			include: stringList(source.include ?? defaultIncludes(), `sources[${index}].include`),
			exclude: [...defaultExcludes(), ...stringList(source.exclude ?? [], `sources[${index}].exclude`)],
		};
	});
}

/**
 * Return the JavaScript module patterns used when a source omits `include`.
 *
 * @returns {string[]} Default source-root-relative globs.
 */
function defaultIncludes()
{
	return ['**/*.js', '**/*.mjs', '**/*.cjs'];
}

/**
 * Return default exclusions relative to each configured source root.
 *
 * @returns {string[]} Default source-root-relative globs.
 */
function defaultExcludes()
{
	return ['node_modules/**', 'vendor/**', 'test/**', 'tests/**', 'fixture/**', 'fixtures/**', 'cache/**', '.cache/**', 'dist/**', 'build/**'];
}

/**
 * Convert one source-root-relative file into a stable public module name.
 *
 * @param {string} relativeSource POSIX path below its configured source root.
 * @returns {string} Extensionless module path.
 */
function moduleName(relativeSource)
{
	const withoutExtension = relativeSource.replace(/\.(?:c|m)?js$/i, '');
	const withoutIndex = withoutExtension.replace(/(?:^|\/)index$/i, '');
	return withoutIndex.replace(/^\/|\/$/g, '') || 'index';
}

/**
 * Normalize the optional llms.txt selection mapping.
 *
 * @param {unknown} value Raw `llms` setting.
 * @returns {false|{section:string,description:string}} Page selection metadata.
 */
function normalizeLlms(value)
{
	if(value === false || value === null) return false;
	if(typeof value === 'string') return {section: requiredString(value, 'llms'), description: ''};
	const llms = mapping(value, 'llms');
	rejectUnknown(llms, new Set(['section', 'description']), 'llms');
	return {
		section: requiredString(llms.section, 'llms.section'),
		description: optionalString(llms.description, ''),
	};
}

/**
 * Resolve a readable project file and reject paths or links outside the root.
 *
 * @param {string} root Canonical project root.
 * @param {unknown} configured Configured project-relative path.
 * @param {string} label Diagnostic label.
 * @returns {Promise<string>} Canonical file path.
 */
async function resolveInput(root, configured, label)
{
	const candidate = path.resolve(root, requiredString(configured, label));
	inside(root, candidate, label);
	await rejectLinkedSegments(root, candidate, label);
	const resolved = await fs.realpath(candidate).catch(() => '');
	if(resolved === '' || !(await fs.stat(resolved)).isFile())
	{
		throw new Error(`${label} does not exist: ${configured}`);
	}
	inside(root, resolved, label);
	return resolved;
}

/**
 * Resolve a project directory and reject linked or escaping roots.
 *
 * @param {string} root Canonical project root.
 * @param {string} configured Configured project-relative directory.
 * @param {string} label Diagnostic label.
 * @returns {Promise<string>} Canonical directory path.
 */
async function resolveDirectory(root, configured, label)
{
	const candidate = path.resolve(root, configured);
	inside(root, candidate, label);
	await rejectLinkedSegments(root, candidate, label);
	const resolved = await fs.realpath(candidate).catch(() => '');
	if(resolved === '' || !(await fs.stat(resolved)).isDirectory())
	{
		throw new Error(`${label} does not exist: ${configured}`);
	}
	inside(root, resolved, label);
	return resolved;
}

/**
 * Reject a candidate outside its configured root.
 *
 * @param {string} root Canonical root.
 * @param {string} candidate Candidate path.
 * @param {string} label Diagnostic label.
 * @returns {void}
 */
function inside(root, candidate, label)
{
	const relative = path.relative(root, candidate);
	if(relative.startsWith('..' + path.sep) || relative === '..' || path.isAbsolute(relative))
	{
		throw new Error(`${label} escapes the project root: ${candidate}`);
	}
}

/**
 * Reject any symbolic link between the root and an input candidate.
 *
 * @param {string} root Canonical root.
 * @param {string} candidate Candidate path.
 * @param {string} label Diagnostic label.
 * @returns {Promise<void>} Completion after every existing segment is checked.
 */
async function rejectLinkedSegments(root, candidate, label)
{
	const relative = path.relative(root, candidate);
	let cursor = root;
	for(const segment of relative.split(path.sep).filter(Boolean))
	{
		cursor = path.join(cursor, segment);
		const stat = await fs.lstat(cursor).catch(() => null);
		if(stat?.isSymbolicLink()) throw new Error(`${label} contains a symbolic link: ${cursor}`);
		if(stat === null) break;
	}
}

/**
 * Report whether a path matches one or more supported glob expressions.
 *
 * @param {string} value Source-root-relative POSIX path.
 * @param {string[]} patterns Configured glob expressions.
 * @param {boolean} directory Whether the candidate is a directory prefix.
 * @returns {boolean} Whether any pattern matches.
 */
function matchesAny(value, patterns, directory)
{
	return patterns.some((pattern) => {
		const normalized = directory ? pattern.replace(/\*\*$/, '') : pattern;
		return globRegex(normalized).test(value);
	});
}

/**
 * Compile `*`, `**`, and `?` path globs into anchored regular expressions.
 *
 * @param {string} pattern Portable glob expression.
 * @returns {RegExp} Anchored regular expression.
 */
function globRegex(pattern)
{
	let output = '^';
	for(let index = 0; index < pattern.length; index++)
	{
		const character = pattern[index];
		if(character === '*' && pattern[index + 1] === '*')
		{
			index++;
			if(pattern[index + 1] === '/')
			{
				index++;
				output += '(?:.*/)?';
			}
			else output += '.*';
		}
		else if(character === '*') output += '[^/]*';
		else if(character === '?') output += '[^/]';
		else output += character.replace(/[|\\{}()[\]^$+?.]/g, '\\$&');
	}
	return new RegExp(output + '$');
}

/**
 * Require a mapping rather than a scalar or sequence.
 *
 * @param {unknown} value Parsed YAML value.
 * @param {string} label Diagnostic label.
 * @returns {Record<string,unknown>} Validated mapping.
 */
function mapping(value, label)
{
	if(value === null || typeof value !== 'object' || Array.isArray(value))
	{
		throw new Error(`${label} must be a mapping.`);
	}
	return /** @type {Record<string,unknown>} */ (value);
}

/**
 * Reject unrecognized mapping keys so misspelled settings cannot be ignored.
 *
 * @param {Record<string,unknown>} value Validated mapping.
 * @param {Set<string>} allowed Allowed field names.
 * @param {string} label Diagnostic label.
 * @returns {void}
 */
function rejectUnknown(value, allowed, label)
{
	for(const key of Object.keys(value))
	{
		if(!allowed.has(key)) throw new Error(`Unknown ${label} field: ${key}`);
	}
}

/**
 * Read a required nonempty string.
 *
 * @param {unknown} value Raw field value.
 * @param {string} label Diagnostic label.
 * @returns {string} Trimmed nonempty text.
 */
function requiredString(value, label)
{
	if(typeof value !== 'string' || value.trim() === '') throw new Error(`${label} must be a nonempty string.`);
	return value.trim();
}

/**
 * Read an optional string and return its default when absent.
 *
 * @param {unknown} value Raw field value.
 * @param {string} fallback Default text.
 * @returns {string} Trimmed configured or default text.
 */
function optionalString(value, fallback)
{
	if(value === undefined || value === null) return fallback;
	if(typeof value !== 'string') throw new Error('Expected a string value.');
	return value.trim();
}

/**
 * Read a nullable nonempty string.
 *
 * @param {unknown} value Raw field value.
 * @param {string} label Diagnostic label.
 * @returns {string|null} Trimmed text or null.
 */
function nullableString(value, label)
{
	if(value === undefined || value === null || value === '') return null;
	return requiredString(value, label);
}

/**
 * Read a list containing only nonempty strings.
 *
 * @param {unknown} value Raw list value.
 * @param {string} label Diagnostic label.
 * @returns {string[]} Trimmed strings.
 */
function stringList(value, label)
{
	if(!Array.isArray(value) || value.some((item) => typeof item !== 'string' || item.trim() === ''))
	{
		throw new Error(`${label} must be a list of nonempty strings.`);
	}
	return value.map((item) => /** @type {string} */ (item).trim());
}

/**
 * Read a strict boolean value.
 *
 * @param {unknown} value Raw field value.
 * @param {string} label Diagnostic label.
 * @returns {boolean} Validated boolean.
 */
function booleanValue(value, label)
{
	if(typeof value !== 'boolean') throw new Error(`${label} must be true or false.`);
	return value;
}

/**
 * Read a finite numeric value.
 *
 * @param {unknown} value Raw field value.
 * @param {string} label Diagnostic label.
 * @returns {number} Finite number.
 */
function numberValue(value, label)
{
	if(typeof value !== 'number' || !Number.isFinite(value)) throw new Error(`${label} must be a number.`);
	return value;
}

/**
 * Read one value from a fixed string set.
 *
 * @param {unknown} value Raw field value.
 * @param {string[]} allowed Allowed strings.
 * @param {string} label Diagnostic label.
 * @returns {string} Validated string.
 */
function enumValue(value, allowed, label)
{
	if(typeof value !== 'string' || !allowed.includes(value))
	{
		throw new Error(`${label} must be one of: ${allowed.join(', ')}.`);
	}
	return value;
}

/**
 * Read one documentation-validation level.
 *
 * @param {unknown} value Raw field value.
 * @param {string} label Diagnostic label.
 * @returns {ValidationLevel} Validated policy level.
 */
function validationLevel(value, label)
{
	return /** @type {ValidationLevel} */ (enumValue(value, ['off', 'warn', 'error'], label));
}

/**
 * Reject absolute, empty, dot, and parent-relative configured paths.
 *
 * @param {string} value Configured path.
 * @param {string} label Diagnostic label.
 * @returns {string} Normalized project-relative path.
 */
function safeRelativePath(value, label)
{
	const normalized = slash(value).replace(/^\.\//, '').replace(/\/$/, '');
	if(normalized === '' || normalized === '.' || path.posix.isAbsolute(normalized)
		|| normalized.split('/').some((segment) => segment === '' || segment === '.' || segment === '..'))
	{
		throw new Error(`${label} must be a safe project-relative path.`);
	}
	return normalized;
}

/**
 * Normalize and validate one route override without a file extension.
 *
 * @param {string} value Configured route.
 * @param {string} label Diagnostic label.
 * @returns {string} Validated extensionless route.
 */
function safeRoute(value, label)
{
	const route = safeRelativePath(value, label).replace(/\.md$/i, '');
	if(route.endsWith('.html')) throw new Error(`${label} must omit the .html extension.`);
	return route;
}

/**
 * Normalize operating-system separators for stored project paths.
 *
 * @param {string} value Native path.
 * @returns {string} POSIX-style path.
 */
function slash(value)
{
	return value.split(path.sep).join('/');
}
