// @ts-check

import {spawn} from 'node:child_process';
import fs from 'node:fs/promises';
import os from 'node:os';
import path from 'node:path';
import {fileURLToPath} from 'node:url';

/** @import {Diagnostic, DocumentationGraph, GraphMember, GraphSymbol, PhpDocConfig, SourceFile, TypeReference, ValidationLevel} from './types.js' */

const packageRoot = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const adapterPath = path.join(packageRoot, 'adapter', 'analyze.php');
const outputLimit = 67_108_864;
const timeoutMilliseconds = 60_000;

/**
 * Run the isolated PHP adapter, validate its graph, and apply documentation policy.
 *
 * @param {PhpDocConfig} config Normalized project configuration.
 * @param {SourceFile[]} files Discovered PHP sources.
 * @returns {Promise<DocumentationGraph>} Validated documentation graph.
 */
export async function analyze(config, files)
{
	const directory = await fs.mkdtemp(path.join(os.tmpdir(), 'smgen-phpdoc-adapter-'));
	const inputPath = path.join(directory, 'input.json');
	try
	{
		await fs.writeFile(inputPath, JSON.stringify({
			root: config.root,
			php: config.php,
			visibility: config.visibility,
			package: config.package,
			files,
		}), {mode: 0o600});
		const output = await executeAdapter(inputPath, config.root);
		const parsed = /** @type {unknown} */ (JSON.parse(output));
		const graph = validateGraph(parsed);
		applyDocumentationPolicy(graph, config);
		graph.diagnostics.sort(compareDiagnostics);
		return graph;
	}
	finally
	{
		await fs.rm(directory, {recursive: true, force: true});
	}
}

/**
 * Spawn the bundled adapter with strict time and output limits.
 *
 * @param {string} inputPath Private JSON input file.
 * @param {string} cwd Project working directory.
 * @returns {Promise<string>} Adapter JSON output.
 */
function executeAdapter(inputPath, cwd)
{
	return new Promise((resolve, reject) => {
		const child = spawn('php', [adapterPath, inputPath], {cwd, stdio: ['ignore', 'pipe', 'pipe']});
		/** @type {Buffer[]} */
		const stdout = [];
		/** @type {Buffer[]} */
		const stderr = [];
		let bytes = 0;
		let settled = false;
		const timer = setTimeout(() => {
			settled = true;
			child.kill('SIGKILL');
			reject(new Error(`Static PHP adapter exceeded ${timeoutMilliseconds / 1000} seconds.`));
		}, timeoutMilliseconds);

		/**
		 * Capture bounded child-process output or terminate a runaway adapter.
		 *
		 * @param {Buffer[]} chunks Destination chunk list.
		 * @param {Buffer} chunk New child-process bytes.
		 * @returns {void}
		 */
		const capture = (chunks, chunk) => {
			bytes += chunk.length;
			if(bytes > outputLimit)
			{
				settled = true;
				clearTimeout(timer);
				child.kill('SIGKILL');
				reject(new Error('Static PHP adapter output exceeded 64 MiB.'));
				return;
			}
			chunks.push(chunk);
		};
		child.stdout.on('data', (/** @type {Buffer} */ chunk) => capture(stdout, chunk));
		child.stderr.on('data', (/** @type {Buffer} */ chunk) => capture(stderr, chunk));
		child.once('error', (error) => {
			if(settled) return;
			settled = true;
			clearTimeout(timer);
			reject(error);
		});
		child.once('close', (code, signal) => {
			if(settled) return;
			settled = true;
			clearTimeout(timer);
			const errors = Buffer.concat(stderr).toString('utf8').trim();
			if(code !== 0)
			{
				reject(new Error(errors || `Static PHP adapter exited ${code ?? signal ?? 'without a status'}.`));
				return;
			}
			resolve(Buffer.concat(stdout).toString('utf8'));
		});
	});
}

/**
 * Validate process-boundary graph structure before any path or Markdown work.
 *
 * @param {unknown} value Parsed adapter JSON.
 * @returns {DocumentationGraph} Validated graph.
 */
export function validateGraph(value)
{
	const graph = record(value, 'documentation graph');
	if(graph.schemaVersion !== 1) throw new Error('Static PHP adapter returned an unsupported graph schema.');
	const packageValue = record(graph.package, 'graph package');
	const diagnostics = array(graph.diagnostics, 'graph diagnostics').map((item, index) => validateDiagnostic(item, index));
	const symbols = array(graph.symbols, 'graph symbols').map((item, index) => validateSymbol(item, index));
	const ids = new Set();
	for(const symbol of symbols)
	{
		for(const valueWithId of [symbol, ...symbol.members])
		{
			if(ids.has(valueWithId.id)) throw new Error(`Static PHP adapter returned duplicate ID ${valueWithId.id}.`);
			ids.add(valueWithId.id);
		}
	}
	return /** @type {DocumentationGraph} */ ({
		schemaVersion: 1,
		package: {
			title: text(packageValue.title, 'graph package title'),
			description: text(packageValue.description, 'graph package description'),
			version: text(packageValue.version, 'graph package version'),
		},
		diagnostics,
		symbols,
	});
}

/**
 * Validate one adapter diagnostic.
 *
 * @param {unknown} value Untrusted diagnostic value.
 * @param {number} index Diagnostic index.
 * @returns {Diagnostic} Validated diagnostic.
 */
function validateDiagnostic(value, index)
{
	const diagnostic = record(value, `diagnostic ${index}`);
	const severity = text(diagnostic.severity, `diagnostic ${index} severity`);
	if(!['info', 'warning', 'error'].includes(severity)) throw new Error(`Diagnostic ${index} has an invalid severity.`);
	return /** @type {Diagnostic} */ ({
		severity,
		code: text(diagnostic.code, `diagnostic ${index} code`),
		message: text(diagnostic.message, `diagnostic ${index} message`),
		...(typeof diagnostic.file === 'string' ? {file: diagnostic.file} : {}),
		...(Number.isInteger(diagnostic.line) ? {line: /** @type {number} */ (diagnostic.line)} : {}),
		...(typeof diagnostic.symbolId === 'string' ? {symbolId: diagnostic.symbolId} : {}),
	});
}

/**
 * Validate one top-level declaration and its member collection.
 *
 * @param {unknown} value Untrusted symbol value.
 * @param {number} index Symbol index.
 * @returns {GraphSymbol} Validated symbol.
 */
function validateSymbol(value, index)
{
	const symbol = record(value, `symbol ${index}`);
	const source = validateSource(symbol.source, `symbol ${index} source`);
	const members = array(symbol.members, `symbol ${index} members`).map((member, memberIndex) => validateMember(member, index, memberIndex));
	return /** @type {GraphSymbol} */ ({
		...symbol,
		id: text(symbol.id, `symbol ${index} ID`),
		kind: text(symbol.kind, `symbol ${index} kind`),
		name: text(symbol.name, `symbol ${index} name`),
		shortName: text(symbol.shortName, `symbol ${index} short name`),
		namespace: text(symbol.namespace, `symbol ${index} namespace`),
		signature: text(symbol.signature, `symbol ${index} signature`),
		flags: stringArray(symbol.flags, `symbol ${index} flags`),
		attributes: objectArray(symbol.attributes, `symbol ${index} attributes`),
		docs: validateDocumentation(symbol.docs, `symbol ${index} docs`),
		extends: typeArray(symbol.extends, `symbol ${index} extends`),
		implements: typeArray(symbol.implements, `symbol ${index} implements`),
		traits: objectArray(symbol.traits, `symbol ${index} traits`),
		members,
		source,
		conditional: Boolean(symbol.conditional),
	});
}

/**
 * Validate one declared or documented virtual member.
 *
 * @param {unknown} value Untrusted member value.
 * @param {number} symbolIndex Owning symbol index.
 * @param {number} memberIndex Member index.
 * @returns {GraphMember} Validated member.
 */
function validateMember(value, symbolIndex, memberIndex)
{
	const label = `symbol ${symbolIndex} member ${memberIndex}`;
	const member = record(value, label);
	return /** @type {GraphMember} */ ({
		...member,
		id: text(member.id, `${label} ID`),
		kind: text(member.kind, `${label} kind`),
		name: text(member.name, `${label} name`),
		anchor: text(member.anchor, `${label} anchor`),
		signature: text(member.signature, `${label} signature`),
		flags: stringArray(member.flags, `${label} flags`),
		type: nullableType(member.type, `${label} type`),
		returnType: nullableType(member.returnType, `${label} return type`),
		parameters: objectArray(member.parameters, `${label} parameters`),
		docs: validateDocumentation(member.docs, `${label} docs`),
		source: validateSource(member.source, `${label} source`),
		virtual: Boolean(member.virtual),
	});
}

/**
 * Validate the stable fields consumed from normalized documentation.
 *
 * @param {unknown} value Untrusted documentation value.
 * @param {string} label Diagnostic label.
 * @returns {import('./types.js').Documentation} Validated documentation.
 */
function validateDocumentation(value, label)
{
	const docs = record(value, label);
	const paramsValue = record(docs.params, `${label} parameters`);
	/** @type {Record<string,{type:TypeReference,description:string}>} */
	const params = {};
	for(const [name, parameter] of Object.entries(paramsValue))
	{
		const item = record(parameter, `${label} parameter ${name}`);
		params[name] = {type: validateType(item.type, `${label} parameter ${name} type`), description: text(item.description, `${label} parameter ${name} description`)};
	}
	return /** @type {import('./types.js').Documentation} */ ({
		...docs,
		summary: text(docs.summary, `${label} summary`),
		description: text(docs.description, `${label} description`),
		params,
		returns: nullableDocumentedType(docs.returns, `${label} return`),
		throws: array(docs.throws, `${label} throws`).map((item, index) => documentedType(item, `${label} throw ${index}`)),
		var: nullableDocumentedType(docs.var, `${label} var`),
		deprecated: text(docs.deprecated, `${label} deprecation`),
		see: stringArray(docs.see, `${label} see`),
		tags: objectArray(docs.tags, `${label} tags`).map((item) => ({name: text(item.name, `${label} tag name`), value: text(item.value, `${label} tag value`)})),
		magicMethods: objectArray(docs.magicMethods, `${label} magic methods`),
		magicProperties: objectArray(docs.magicProperties, `${label} magic properties`),
	});
}

/**
 * Validate one documented type and its prose.
 *
 * @param {unknown} value Untrusted documented type.
 * @param {string} label Diagnostic label.
 * @returns {{type:TypeReference,description:string}} Validated documented type.
 */
function documentedType(value, label)
{
	const item = record(value, label);
	return {type: validateType(item.type, `${label} type`), description: text(item.description, `${label} description`)};
}

/**
 * Validate an optional documented type.
 *
 * @param {unknown} value Untrusted optional documented type.
 * @param {string} label Diagnostic label.
 * @returns {{type:TypeReference,description:string}|null} Validated optional documented type.
 */
function nullableDocumentedType(value, label)
{
	return value === null ? null : documentedType(value, label);
}

/**
 * Validate a type object emitted by the adapter.
 *
 * @param {unknown} value Untrusted type value.
 * @param {string} label Diagnostic label.
 * @returns {TypeReference} Validated type reference.
 */
function validateType(value, label)
{
	const type = record(value, label);
	return {display: text(type.display, `${label} display`), references: stringArray(type.references, `${label} references`)};
}

/**
 * Validate an optional type object.
 *
 * @param {unknown} value Untrusted optional type.
 * @param {string} label Diagnostic label.
 * @returns {TypeReference|null} Validated optional type.
 */
function nullableType(value, label)
{
	return value === null ? null : validateType(value, label);
}

/**
 * Validate a list containing only normalized types.
 *
 * @param {unknown} value Untrusted type list.
 * @param {string} label Diagnostic label.
 * @returns {TypeReference[]} Validated type list.
 */
function typeArray(value, label)
{
	return array(value, label).map((item, index) => validateType(item, `${label} ${index}`));
}

/**
 * Validate a project-relative source span.
 *
 * @param {unknown} value Untrusted source span.
 * @param {string} label Diagnostic label.
 * @returns {{file:string,startLine:number,endLine:number}} Validated source span.
 */
function validateSource(value, label)
{
	const source = record(value, label);
	const startLine = integer(source.startLine, `${label} start line`);
	const endLine = integer(source.endLine, `${label} end line`);
	if(startLine < 1 || endLine < startLine) throw new Error(`${label} has an invalid line range.`);
	return {file: text(source.file, `${label} file`), startLine, endLine};
}

/**
 * Apply configured completeness, reference, and type-resolution diagnostics.
 *
 * @param {DocumentationGraph} graph Mutable validated graph.
 * @param {PhpDocConfig} config Documentation policy.
 * @returns {void}
 */
function applyDocumentationPolicy(graph, config)
{
	const known = new Set(graph.symbols.map((symbol) => symbol.name));
	for(const symbol of graph.symbols)
	{
		policyDiagnostic(graph, config.validation.summaries, symbol.docs.summary === '', 'docs.summary', `${symbol.name} is missing a summary.`, symbol);
		checkReferences(graph, config.validation.unresolvedTypes, known, symbol, symbol);
		checkSee(graph, config.validation.brokenSee, known, symbol.docs.see, symbol);
		if(symbol.kind === 'function')
		{
			checkParameters(graph, config.validation.parameters, /** @type {Array<Record<string,unknown>>} */ (symbol.parameters ?? []), symbol);
			checkReturn(graph, config.validation.returns, /** @type {TypeReference|null} */ (symbol.returnType ?? null), symbol.docs, symbol, false);
		}
		for(const member of symbol.members)
		{
			const effectiveDocs = member.docs.summary === '' && isRecord(member.inheritedDocs)
				? validateDocumentation(record(member.inheritedDocs, 'inherited docs').docs, 'inherited docs')
				: member.docs;
			policyDiagnostic(graph, config.validation.summaries, effectiveDocs.summary === '', 'docs.summary', `${symbol.name}::${member.name} is missing a summary.`, symbol, member);
			checkParameters(graph, config.validation.parameters, member.parameters, symbol, member);
			checkReturn(graph, config.validation.returns, member.returnType, effectiveDocs, symbol, member.name === '__construct', member);
			checkReferences(graph, config.validation.unresolvedTypes, known, symbol, member);
			checkSee(graph, config.validation.brokenSee, known, effectiveDocs.see, symbol, member);
		}
	}
}

/**
 * Check parameter descriptions on one function or method.
 *
 * @param {DocumentationGraph} graph Mutable graph.
 * @param {ValidationLevel} level Configured rule severity.
 * @param {Array<Record<string,unknown>>} parameters Normalized parameters.
 * @param {GraphSymbol} symbol Owning symbol.
 * @param {GraphMember} [member] Optional owning method.
 * @returns {void}
 */
function checkParameters(graph, level, parameters, symbol, member)
{
	for(const parameter of parameters)
	{
		const name = typeof parameter.name === 'string' ? parameter.name : 'value';
		const docs = isRecord(parameter.docs) ? parameter.docs : null;
		policyDiagnostic(graph, level, docs === null || typeof docs.description !== 'string' || docs.description.trim() === '', 'docs.parameter', `${symbol.name}${member ? `::${member.name}` : ''}() is missing a description for $${name}.`, symbol, member);
	}
}

/**
 * Check return prose when a callable has a meaningful return type.
 *
 * @param {DocumentationGraph} graph Mutable graph.
 * @param {ValidationLevel} level Configured rule severity.
 * @param {TypeReference|null} returnType Native return type.
 * @param {import('./types.js').Documentation} docs Effective documentation.
 * @param {GraphSymbol} symbol Owning symbol.
 * @param {boolean} constructor Whether this callable is a constructor.
 * @param {GraphMember} [member] Optional owning method.
 * @returns {void}
 */
function checkReturn(graph, level, returnType, docs, symbol, constructor, member)
{
	if(constructor || returnType === null || ['void', 'never'].includes(returnType.display.toLowerCase())) return;
	policyDiagnostic(graph, level, docs.returns === null || docs.returns.description.trim() === '', 'docs.return', `${symbol.name}${member ? `::${member.name}` : ''}() is missing a return description.`, symbol, member);
}

/**
 * Check every named type target against declarations in the analyzed roots.
 *
 * @param {DocumentationGraph} graph Mutable graph.
 * @param {ValidationLevel} level Configured rule severity.
 * @param {Set<string>} known Declared qualified names.
 * @param {GraphSymbol} symbol Owning symbol.
 * @param {GraphSymbol|GraphMember} member Item whose types are checked.
 * @returns {void}
 */
function checkReferences(graph, level, known, symbol, member)
{
	if(level === 'off') return;
	const references = new Set();
	for(const type of allTypes(member))
	{
		for(const reference of type.references) references.add(reference);
	}
	for(const reference of references)
	{
		if(!known.has(reference)) policyDiagnostic(graph, level, true, 'types.unresolved', `${member.id} references external or unresolved type ${reference}.`, symbol, 'id' in member && member.id !== symbol.id ? /** @type {GraphMember} */ (member) : undefined);
	}
}

/**
 * Check symbol-like @see targets while allowing URLs and prose references.
 *
 * @param {DocumentationGraph} graph Mutable graph.
 * @param {ValidationLevel} level Configured rule severity.
 * @param {Set<string>} known Declared qualified names.
 * @param {string[]} values Related references.
 * @param {GraphSymbol} symbol Owning symbol.
 * @param {GraphMember} [member] Optional owning member.
 * @returns {void}
 */
function checkSee(graph, level, known, values, symbol, member)
{
	if(level === 'off') return;
	for(const value of values)
	{
		const target = value.trim().split(/\s+/, 1)[0].replace(/\(\)$/, '');
		if(target === '' || /^(?:https?:|#)/i.test(target)) continue;
		const owner = target.split('::', 1)[0].replace(/^\\/, '');
		const qualified = owner.includes('\\') || symbol.namespace === '' ? owner : `${symbol.namespace}\\${owner}`;
		policyDiagnostic(graph, level, !known.has(owner) && !known.has(qualified), 'docs.see', `${member?.id ?? symbol.id} has an unresolved @see target ${target}.`, symbol, member);
	}
}

/**
 * Gather native and documented types reachable from one graph item.
 *
 * @param {GraphSymbol|GraphMember} value Graph item.
 * @returns {TypeReference[]} Reachable native and documented types.
 */
function allTypes(value)
{
	/** @type {TypeReference[]} */
	const types = [];
	if('type' in value && isType(value.type)) types.push(value.type);
	if('returnType' in value && isType(value.returnType)) types.push(value.returnType);
	if('extends' in value && Array.isArray(value.extends)) for(const type of value.extends) if(isType(type)) types.push(type);
	if('implements' in value && Array.isArray(value.implements)) for(const type of value.implements) if(isType(type)) types.push(type);
	if(Array.isArray(value.parameters))
	{
		for(const parameter of value.parameters)
		{
			if(isRecord(parameter) && isType(parameter.type)) types.push(parameter.type);
			if(isRecord(parameter) && isRecord(parameter.docs) && isType(parameter.docs.type)) types.push(parameter.docs.type);
		}
	}
	if(isRecord(value.docs))
	{
		if(isRecord(value.docs.returns) && isType(value.docs.returns.type)) types.push(value.docs.returns.type);
		if(isRecord(value.docs.var) && isType(value.docs.var.type)) types.push(value.docs.var.type);
		if(Array.isArray(value.docs.throws)) for(const item of value.docs.throws) if(isRecord(item) && isType(item.type)) types.push(item.type);
	}
	return types;
}

/**
 * Add one configured policy diagnostic only when its condition applies.
 *
 * @param {DocumentationGraph} graph Mutable graph.
 * @param {ValidationLevel} level Configured rule severity.
 * @param {boolean} condition Whether the policy violation occurred.
 * @param {string} code Stable diagnostic code.
 * @param {string} message Human-readable diagnostic.
 * @param {GraphSymbol} symbol Owning symbol.
 * @param {GraphMember} [member] Optional owning member.
 * @returns {void}
 */
function policyDiagnostic(graph, level, condition, code, message, symbol, member)
{
	if(!condition || level === 'off') return;
	graph.diagnostics.push({
		severity: level === 'error' ? 'error' : 'warning',
		code,
		message,
		file: member?.source.file ?? symbol.source.file,
		line: member?.source.startLine ?? symbol.source.startLine,
		symbolId: member?.id ?? symbol.id,
	});
}

/**
 * Compare diagnostics by source, line, code, and message.
 *
 * @param {Diagnostic} left Left diagnostic.
 * @param {Diagnostic} right Right diagnostic.
 * @returns {number} Sort comparison result.
 */
function compareDiagnostics(left, right)
{
	return `${left.file ?? ''}\0${String(left.line ?? 0).padStart(10, '0')}\0${left.code}\0${left.message}`
		.localeCompare(`${right.file ?? ''}\0${String(right.line ?? 0).padStart(10, '0')}\0${right.code}\0${right.message}`);
}

/**
 * Require one ordinary object.
 *
 * @param {unknown} value Untrusted value.
 * @param {string} label Diagnostic label.
 * @returns {Record<string,unknown>} Validated object.
 */
function record(value, label)
{
	if(!isRecord(value)) throw new Error(`Static PHP adapter returned invalid ${label}.`);
	return value;
}

/**
 * Report whether an unknown value is an ordinary object.
 *
 * @param {unknown} value Unknown value.
 * @returns {value is Record<string,unknown>} Whether the value is an object.
 */
function isRecord(value)
{
	return value !== null && typeof value === 'object' && !Array.isArray(value);
}

/**
 * Report whether an unknown value has normalized type fields.
 *
 * @param {unknown} value Unknown value.
 * @returns {value is TypeReference} Whether the value is a type reference.
 */
function isType(value)
{
	return isRecord(value) && typeof value.display === 'string' && Array.isArray(value.references);
}

/**
 * Require one array.
 *
 * @param {unknown} value Untrusted value.
 * @param {string} label Diagnostic label.
 * @returns {unknown[]} Validated array.
 */
function array(value, label)
{
	if(!Array.isArray(value)) throw new Error(`Static PHP adapter returned invalid ${label}.`);
	return value;
}

/**
 * Require one array of plain objects.
 *
 * @param {unknown} value Untrusted value.
 * @param {string} label Diagnostic label.
 * @returns {Array<Record<string,unknown>>} Validated objects.
 */
function objectArray(value, label)
{
	return array(value, label).map((item, index) => record(item, `${label} ${index}`));
}

/**
 * Require one array of strings.
 *
 * @param {unknown} value Untrusted value.
 * @param {string} label Diagnostic label.
 * @returns {string[]} Validated strings.
 */
function stringArray(value, label)
{
	return array(value, label).map((item, index) => text(item, `${label} ${index}`));
}

/**
 * Require one string while permitting an intentionally empty value.
 *
 * @param {unknown} value Untrusted value.
 * @param {string} label Diagnostic label.
 * @returns {string} Validated string.
 */
function text(value, label)
{
	if(typeof value !== 'string') throw new Error(`Static PHP adapter returned invalid ${label}.`);
	return value;
}

/**
 * Require one integer.
 *
 * @param {unknown} value Untrusted value.
 * @param {string} label Diagnostic label.
 * @returns {number} Validated integer.
 */
function integer(value, label)
{
	if(!Number.isInteger(value)) throw new Error(`Static PHP adapter returned invalid ${label}.`);
	return /** @type {number} */ (value);
}
