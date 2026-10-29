// @ts-check

import path from 'node:path';
import * as ts from 'typescript';

/** @import {Diagnostic, Documentation, DocumentationGraph, GraphMember, GraphParameter, GraphSymbol, JsDocConfig, SourceFile, TypeReference, ValidationLevel} from './types.js' */

const typeFormat = ts.TypeFormatFlags.NoTruncation
	| ts.TypeFormatFlags.UseAliasDefinedOutsideCurrentScope
	| ts.TypeFormatFlags.UseSingleQuotesForStringLiteralType;

/**
 * Build a normalized public API graph without importing or executing source modules.
 *
 * @param {JsDocConfig} config Normalized project configuration.
 * @param {SourceFile[]} files Discovered JavaScript sources.
 * @returns {Promise<DocumentationGraph>} Validated graph with documentation-policy diagnostics.
 */
export async function analyze(config, files)
{
	const options = {
		allowJs: true,
		checkJs: true,
		noEmit: true,
		target: ts.ScriptTarget.ES2022,
		module: ts.ModuleKind.NodeNext,
		moduleResolution: ts.ModuleResolutionKind.NodeNext,
		skipLibCheck: true,
		strict: false,
	};
	const program = ts.createProgram(files.map((file) => file.absolute), options);
	const checker = program.getTypeChecker();
	const byAbsolute = new Map(files.map((file) => [canonical(file.absolute), file]));
	/** @type {Diagnostic[]} */
	const diagnostics = [];
	for(const file of files)
	{
		const source = program.getSourceFile(file.absolute);
		if(source === undefined) throw new Error(`TypeScript did not load ${file.relative}.`);
		for(const diagnostic of program.getSyntacticDiagnostics(source)) diagnostics.push(typescriptDiagnostic(diagnostic, file));
	}
	if(diagnostics.some((diagnostic) => diagnostic.severity === 'error'))
	{
		return {schemaVersion: 1, package: config.package, diagnostics, symbols: []};
	}

	/** @type {GraphSymbol[]} */
	const symbols = [];
	for(const file of files)
	{
		const source = program.getSourceFile(file.absolute);
		if(source === undefined) continue;
		collectExports(source, file, checker, byAbsolute, config, symbols);
		collectTypeDefinitions(source, file, checker, config, symbols);
	}
	const deduplicated = deduplicateSymbols(symbols);
	deduplicated.sort(compareSymbols);
	applyDocumentationPolicy(deduplicated, diagnostics, config);
	diagnostics.sort(compareDiagnostics);
	return validateGraph({schemaVersion: 1, package: config.package, diagnostics, symbols: deduplicated});
}

/**
 * Validate the graph boundary used by renderers and tests.
 *
 * @param {unknown} value Candidate graph.
 * @returns {DocumentationGraph} Validated graph.
 */
export function validateGraph(value)
{
	if(!isRecord(value) || value.schemaVersion !== 1) throw new Error('Unsupported JSDoc graph schema.');
	if(!isRecord(value.package) || !Array.isArray(value.symbols) || !Array.isArray(value.diagnostics)) throw new Error('Invalid JSDoc graph shape.');
	const ids = new Set();
	for(const candidate of value.symbols)
	{
		if(!isRecord(candidate) || typeof candidate.id !== 'string' || typeof candidate.name !== 'string' || typeof candidate.module !== 'string')
		{
			throw new Error('Invalid JSDoc graph symbol.');
		}
		if(ids.has(candidate.id)) throw new Error(`Duplicate JSDoc symbol ID: ${candidate.id}`);
		ids.add(candidate.id);
	}
	return /** @type {DocumentationGraph} */ (value);
}

/**
 * Collect the exports visible from one ES or CommonJS module.
 *
 * @param {ts.SourceFile} source Parsed source file.
 * @param {SourceFile} file Source metadata.
 * @param {ts.TypeChecker} checker Program type checker.
 * @param {Map<string,SourceFile>} byAbsolute Configured files by canonical path.
 * @param {JsDocConfig} config Normalized configuration.
 * @param {GraphSymbol[]} output Symbol accumulator.
 * @returns {void}
 */
function collectExports(source, file, checker, byAbsolute, config, output)
{
	const moduleSymbol = checker.getSymbolAtLocation(source);
	if(moduleSymbol === undefined) return;
	for(const exported of checker.getExportsOfModule(moduleSymbol))
	{
		const target = exported.flags & ts.SymbolFlags.Alias ? checker.getAliasedSymbol(exported) : exported;
		const declaration = resolveExportDeclaration(target, checker);
		if(declaration === undefined) continue;
		const declaredFile = byAbsolute.get(canonical(declaration.getSourceFile().fileName));
		if(declaredFile === undefined) continue;
		const symbol = declarationSymbol(exported.getName(), declaration, file.module, checker, config);
		if(symbol !== null) output.push(symbol);
	}
}

/**
 * Follow CommonJS object shorthand and ordinary symbol declarations to code.
 *
 * @param {ts.Symbol} symbol Compiler export symbol.
 * @param {ts.TypeChecker} checker Program type checker.
 * @returns {ts.Declaration|undefined} Declaration that carries code and JSDoc.
 */
function resolveExportDeclaration(symbol, checker)
{
	const declaration = symbol.valueDeclaration ?? symbol.declarations?.[0];
	if(declaration === undefined) return undefined;
	if(ts.isShorthandPropertyAssignment(declaration))
	{
		const value = checker.getShorthandAssignmentValueSymbol(declaration);
		return value?.valueDeclaration ?? value?.declarations?.[0] ?? declaration;
	}
	if(ts.isPropertyAssignment(declaration) && ts.isIdentifier(declaration.initializer))
	{
		const value = checker.getSymbolAtLocation(declaration.initializer);
		return value?.valueDeclaration ?? value?.declarations?.[0] ?? declaration;
	}
	return declaration;
}

/**
 * Convert one exported compiler declaration into a documentation symbol.
 *
 * @param {string} exportName Public export name.
 * @param {ts.Declaration} declaration Compiler declaration behind the export.
 * @param {string} module Declaring public module.
 * @param {ts.TypeChecker} checker Program type checker.
 * @param {JsDocConfig} config Normalized configuration.
 * @returns {GraphSymbol|null} Normalized symbol or null for unsupported syntax.
 */
function declarationSymbol(exportName, declaration, module, checker, config)
{
	if(ts.isFunctionDeclaration(declaration) || ts.isFunctionExpression(declaration) || ts.isArrowFunction(declaration))
	{
		return callableSymbol('function', exportName, declaration, module, checker, config.root);
	}
	if(ts.isClassDeclaration(declaration) || ts.isClassExpression(declaration))
	{
		const docs = documentationFor(declaration, checker);
		const members = classMembers(declaration, exportName, module, checker, config);
		const flags = declarationFlags(declaration);
		const suffix = heritageText(declaration, checker);
		return {
			id: symbolId('class', module, exportName),
			kind: 'class',
			name: exportName,
			module,
			signature: `${flags.includes('abstract') ? 'abstract ' : ''}class ${exportName}${suffix}`,
			flags,
			type: null,
			returnType: null,
			parameters: [],
			members,
			docs,
			source: sourceSpan(declaration, config.root),
		};
	}
	if(ts.isVariableDeclaration(declaration))
	{
		const host = declaration.parent.parent;
		if(declaration.initializer !== undefined && (ts.isArrowFunction(declaration.initializer) || ts.isFunctionExpression(declaration.initializer)))
		{
			const callable = callableSymbol('function', exportName, declaration.initializer, module, checker, config.root);
			callable.docs = documentationFor(host, checker);
			callable.source = sourceSpan(declaration, config.root);
			return callable;
		}
		const docs = documentationFor(host, checker);
		const type = typeReference(checker.typeToString(checker.getTypeAtLocation(declaration), declaration, typeFormat));
		const constant = ts.isVariableDeclarationList(declaration.parent) && (declaration.parent.flags & ts.NodeFlags.Const) !== 0;
		const flags = constant ? ['const'] : [];
		return {
			id: symbolId(constant ? 'constant' : 'variable', module, exportName),
			kind: constant ? 'constant' : 'variable',
			name: exportName,
			module,
			signature: `${constant ? 'const' : 'let'} ${exportName}: ${type.display}`,
			flags,
			type,
			returnType: null,
			parameters: [],
			members: [],
			docs,
			source: sourceSpan(declaration, config.root),
		};
	}
	if(ts.isPropertyAccessExpression(declaration) && ts.isBinaryExpression(declaration.parent) && declaration.parent.left === declaration)
	{
		const assignment = declaration.parent;
		const host = ts.isExpressionStatement(assignment.parent) ? assignment.parent : assignment;
		if(ts.isArrowFunction(assignment.right) || ts.isFunctionExpression(assignment.right))
		{
			const callable = callableSymbol('function', exportName, assignment.right, module, checker, config.root);
			callable.docs = documentationFor(host, checker);
			callable.source = sourceSpan(host, config.root);
			return callable;
		}
		const docs = documentationFor(host, checker);
		const type = typeReference(checker.typeToString(checker.getTypeAtLocation(assignment.right), assignment.right, typeFormat));
		return {
			id: symbolId('variable', module, exportName),
			kind: 'variable',
			name: exportName,
			module,
			signature: `let ${exportName}: ${type.display}`,
			flags: ['commonjs'],
			type,
			returnType: null,
			parameters: [],
			members: [],
			docs,
			source: sourceSpan(host, config.root),
		};
	}
	return null;
}

/**
 * Normalize an exported function or callback-shaped declaration.
 *
 * @param {'function'|'callback'} kind Public callable kind.
 * @param {string} name Public name.
 * @param {ts.SignatureDeclaration} declaration Callable declaration.
 * @param {string} module Declaring module.
 * @param {ts.TypeChecker} checker Program type checker.
 * @param {string} root Canonical project root.
 * @returns {GraphSymbol} Normalized callable symbol.
 */
function callableSymbol(kind, name, declaration, module, checker, root)
{
	const docs = documentationFor(declaration, checker);
	const parameters = callableParameters(declaration, docs, checker);
	const signature = checker.getSignatureFromDeclaration(declaration);
	const returnType = signature === undefined ? typeReference('unknown') : typeReference(checker.typeToString(checker.getReturnTypeOfSignature(signature), declaration, typeFormat));
	const flags = declarationFlags(declaration);
	const prefix = flags.includes('async') ? 'async ' : '';
	return {
		id: symbolId(kind, module, name),
		kind,
		name,
		module,
		signature: `${prefix}${kind === 'function' ? 'function ' : ''}${name}(${parameterSignatures(parameters)}): ${returnType.display}`,
		flags,
		type: null,
		returnType,
		parameters,
		members: [],
		docs,
		source: sourceSpan(declaration, root),
	};
}

/**
 * Collect named `@typedef` and `@callback` declarations from one source file.
 *
 * @param {ts.SourceFile} source Parsed source file.
 * @param {SourceFile} file Source metadata.
 * @param {ts.TypeChecker} checker Program type checker.
 * @param {JsDocConfig} config Normalized configuration.
 * @param {GraphSymbol[]} output Symbol accumulator.
 * @returns {void}
 */
function collectTypeDefinitions(source, file, checker, config, output)
{
	const seen = new Set();
	/**
	 * Visit syntax nodes to find attached standalone JSDoc type declarations.
	 *
	 * @param {ts.Node} node Current syntax node.
	 * @returns {void}
	 */
	function visit(node)
	{
		const topLevel = node === source || node.parent === source;
		for(const block of topLevel ? jsdocBlocks(node) : [])
		{
			for(const tag of block.tags ?? [])
			{
				if(seen.has(tag.pos) || (!ts.isJSDocTypedefTag(tag) && !ts.isJSDocCallbackTag(tag))) continue;
				seen.add(tag.pos);
				const name = jsdocTypeName(tag);
				if(name === '' || hasPrivateTag(block)) continue;
				const kind = ts.isJSDocCallbackTag(tag) ? 'callback' : 'typedef';
				const docs = documentationFromBlock(block, checker);
				const typeText = jsdocTypeText(tag);
				const type = typeReference(typeText || (kind === 'callback' ? 'Function' : 'unknown'));
				const parameters = kind === 'callback' ? documentedParameters(docs) : [];
				const returnType = kind === 'callback' ? (docs.returns?.type ?? typeReference('unknown')) : null;
				const members = ts.isJSDocTypedefTag(tag) ? typedefMembers(tag, file.module, name, config.root) : [];
				output.push({
					id: symbolId(kind, file.module, name),
					kind,
					name,
					module: file.module,
					signature: kind === 'callback'
						? `function ${name}(${parameterSignatures(parameters)}): ${returnType?.display ?? 'unknown'}`
						: `type ${name} = ${type.display}`,
					flags: [],
					type: kind === 'typedef' ? type : null,
					returnType,
					parameters,
					members,
					docs,
					source: sourceSpan(tag, config.root),
				});
			}
		}
		ts.forEachChild(node, visit);
	}
	visit(source);
}

/**
 * Convert `@property` tags inside an object typedef into documented members.
 *
 * @param {ts.JSDocTypedefTag} tag Typedef tag.
 * @param {string} module Declaring module.
 * @param {string} typeName Public typedef name.
 * @param {string} root Canonical project root.
 * @returns {GraphMember[]} Object-shape properties in source order.
 */
function typedefMembers(tag, module, typeName, root)
{
	if(tag.typeExpression === undefined || !ts.isJSDocTypeLiteral(tag.typeExpression)) return [];
	return (tag.typeExpression.jsDocPropertyTags ?? []).map((property) => {
		const name = property.name.getText();
		const type = property.typeExpression === undefined ? typeReference('unknown') : typeReference(property.typeExpression.type.getText());
		const [summary, description] = splitProse(commentText(property.comment));
		const docs = emptyDocumentation();
		docs.summary = summary;
		docs.description = description;
		return {
			id: `${symbolId('typedef', module, typeName)}.${name}`,
			kind: /** @type {'property'} */ ('property'),
			name,
			anchor: memberAnchor('property', name),
			signature: `${name}${property.isBracketed ? '?' : ''}: ${type.display}`,
			flags: property.isBracketed ? ['optional'] : [],
			type,
			returnType: null,
			parameters: [],
			docs,
			source: sourceSpan(property, root),
		};
	});
}

/**
 * Render the name carried by a JSDoc typedef or callback tag.
 *
 * @param {ts.JSDocTypedefTag|ts.JSDocCallbackTag} tag Type declaration tag.
 * @returns {string} Declared type name.
 */
function jsdocTypeName(tag)
{
	const named = tag.fullName ?? tag.name;
	return named?.getText().trim() ?? '';
}

/**
 * Extract the declared type expression from a JSDoc type tag.
 *
 * @param {ts.JSDocTypedefTag|ts.JSDocCallbackTag} tag Type declaration tag.
 * @returns {string} Source-facing type text.
 */
function jsdocTypeText(tag)
{
	if(tag.typeExpression === undefined) return '';
	if(ts.isJSDocTypeLiteral(tag.typeExpression))
	{
		const properties = tag.typeExpression.jsDocPropertyTags ?? [];
		if(properties.length === 0) return 'object';
		return `{ ${properties.map((property) => {
			const name = property.name.getText();
			const optional = property.isBracketed ? '?' : '';
			const type = property.typeExpression?.type.getText() ?? 'unknown';
			return `${name}${optional}: ${type}`;
		}).join('; ')} }`;
	}
	if(ts.isJSDocSignature(tag.typeExpression)) return 'Function';
	return tag.typeExpression.type.getText().trim();
}

/**
 * Read every raw JSDoc block attached to a node, including stacked typedefs.
 *
 * TypeScript's public convenience helper returns only the effective block for
 * declarations with several adjacent comments. Type declarations often use
 * one standalone block each, so the analyzer retains the complete raw list.
 *
 * @param {ts.Node} node Compiler node.
 * @returns {readonly ts.JSDoc[]} Attached JSDoc blocks.
 */
function jsdocBlocks(node)
{
	const documented = /** @type {ts.Node & {jsDoc?:ts.NodeArray<ts.JSDoc>}} */ (node);
	return documented.jsDoc ?? [];
}

/**
 * Report whether one JSDoc block explicitly hides its declaration.
 *
 * @param {ts.Node} block JSDoc block.
 * @returns {boolean} Whether `@private` or `@ignore` is present.
 */
function hasPrivateTag(block)
{
	return (/** @type {ts.JSDoc} */ (block).tags ?? []).some((tag) => ['private', 'ignore'].includes(tag.tagName.text));
}

/**
 * Normalize visible members from one exported class.
 *
 * @param {ts.ClassDeclaration|ts.ClassExpression} declaration Class declaration.
 * @param {string} className Public class name.
 * @param {string} module Declaring module.
 * @param {ts.TypeChecker} checker Program type checker.
 * @param {JsDocConfig} config Normalized configuration.
 * @returns {GraphMember[]} Stable member list.
 */
function classMembers(declaration, className, module, checker, config)
{
	/** @type {GraphMember[]} */
	const members = [];
	for(const member of declaration.members)
	{
		if(!isDocumentableMember(member) || !memberIsVisible(member, config)) continue;
		const name = ts.isConstructorDeclaration(member) ? 'constructor' : memberName(member.name);
		if(name === '') continue;
		const docs = documentationFor(member, checker);
		const flags = declarationFlags(member);
		const span = sourceSpan(member, config.root);
		if(ts.isMethodDeclaration(member) || ts.isConstructorDeclaration(member) || ts.isGetAccessorDeclaration(member) || ts.isSetAccessorDeclaration(member))
		{
			const kind = ts.isConstructorDeclaration(member) ? 'constructor'
				: ts.isGetAccessorDeclaration(member) ? 'getter'
					: ts.isSetAccessorDeclaration(member) ? 'setter' : 'method';
			const parameters = callableParameters(member, docs, checker);
			const signature = checker.getSignatureFromDeclaration(member);
			const returnType = kind === 'constructor' ? null : signature === undefined
				? typeReference('unknown')
				: typeReference(checker.typeToString(checker.getReturnTypeOfSignature(signature), member, typeFormat));
			const displayName = kind === 'constructor' ? 'constructor' : name;
			members.push({
				id: `${symbolId('class', module, className)}.${name}`,
				kind,
				name,
				anchor: memberAnchor(kind, name),
				signature: `${flags.includes('static') ? 'static ' : ''}${flags.includes('async') ? 'async ' : ''}${displayName}(${parameterSignatures(parameters)})${returnType === null ? '' : `: ${returnType.display}`}`,
				flags,
				type: null,
				returnType,
				parameters,
				docs,
				source: span,
			});
			continue;
		}
		const type = typeReference(checker.typeToString(checker.getTypeAtLocation(member), member, typeFormat));
		members.push({
			id: `${symbolId('class', module, className)}.${name}`,
			kind: 'property',
			name,
			anchor: memberAnchor('property', name),
			signature: `${flags.includes('static') ? 'static ' : ''}${name}: ${type.display}`,
			flags,
			type,
			returnType: null,
			parameters: [],
			docs,
			source: span,
		});
	}
	return members.sort((left, right) => left.source.startLine - right.source.startLine || left.name.localeCompare(right.name));
}

/**
 * Report whether a class element has a stable public documentation shape.
 *
 * @param {ts.ClassElement} member Class element.
 * @returns {member is ts.MethodDeclaration|ts.ConstructorDeclaration|ts.GetAccessorDeclaration|ts.SetAccessorDeclaration|ts.PropertyDeclaration} Whether the member is supported.
 */
function isDocumentableMember(member)
{
	return ts.isMethodDeclaration(member) || ts.isConstructorDeclaration(member)
		|| ts.isGetAccessorDeclaration(member) || ts.isSetAccessorDeclaration(member)
		|| ts.isPropertyDeclaration(member);
}

/**
 * Apply public/protected configuration and always hide language-private fields.
 *
 * @param {ts.ClassElement} member Class element.
 * @param {JsDocConfig} config Normalized configuration.
 * @returns {boolean} Whether the member belongs in the public graph.
 */
function memberIsVisible(member, config)
{
	if(member.name !== undefined && ts.isPrivateIdentifier(member.name)) return false;
	const tags = ts.getJSDocTags(member).map((tag) => tag.tagName.text);
	if(tags.includes('private') || tags.includes('ignore')) return false;
	if(tags.includes('protected')) return config.visibility.includes('protected');
	const modifiers = ts.canHaveModifiers(member) ? ts.getModifiers(member) ?? [] : [];
	if(modifiers.some((modifier) => modifier.kind === ts.SyntaxKind.PrivateKeyword)) return false;
	if(modifiers.some((modifier) => modifier.kind === ts.SyntaxKind.ProtectedKeyword)) return config.visibility.includes('protected');
	return config.visibility.includes('public');
}

/**
 * Convert a property-name syntax node into its stable text form.
 *
 * @param {ts.PropertyName|undefined} name Compiler property name.
 * @returns {string} Member name or an empty value for computed names.
 */
function memberName(name)
{
	if(name === undefined || ts.isComputedPropertyName(name)) return '';
	if(ts.isStringLiteral(name) || ts.isNumericLiteral(name)) return name.text;
	return name.getText();
}

/**
 * Parse callable parameters with inferred types and attached JSDoc entries.
 *
 * @param {ts.SignatureDeclaration} declaration Callable declaration.
 * @param {Documentation} docs Parsed declaration documentation.
 * @param {ts.TypeChecker} checker Program type checker.
 * @returns {GraphParameter[]} Normalized parameters in call order.
 */
function callableParameters(declaration, docs, checker)
{
	return declaration.parameters.map((parameter) => {
		const rawName = parameter.name.getText();
		const name = ts.isIdentifier(parameter.name) ? parameter.name.text : rawName;
		const documented = docs.params[name] ?? docs.params[rawName] ?? null;
		const inferred = typeReference(checker.typeToString(checker.getTypeAtLocation(parameter), parameter, typeFormat));
		return {
			name,
			type: documented?.type ?? inferred,
			optional: parameter.questionToken !== undefined || parameter.initializer !== undefined,
			rest: parameter.dotDotDotToken !== undefined,
			defaultValue: parameter.initializer?.getText() ?? '',
			docs: documented,
		};
	});
}

/**
 * Reconstruct callback parameters from its JSDoc entries when no syntax exists.
 *
 * @param {Documentation} docs Callback documentation.
 * @returns {GraphParameter[]} Parameters in documented order.
 */
function documentedParameters(docs)
{
	return Object.entries(docs.params).map(([name, documented]) => ({
		name,
		type: documented.type,
		optional: false,
		rest: false,
		defaultValue: '',
		docs: documented,
	}));
}

/**
 * Parse the closest JSDoc block associated with a declaration.
 *
 * @param {ts.Node} node Documented syntax node.
 * @param {ts.TypeChecker} checker Program type checker.
 * @returns {Documentation} Normalized documentation.
 */
function documentationFor(node, checker)
{
	const host = ts.isVariableDeclaration(node) ? node.parent.parent : node;
	const blocks = ts.getJSDocCommentsAndTags(host).filter(ts.isJSDoc);
	const block = blocks.at(-1);
	if(block !== undefined) return documentationFromBlock(block, checker);
	return emptyDocumentation();
}

/**
 * Parse prose and supported tags from one compiler JSDoc block.
 *
 * @param {ts.JSDoc} block JSDoc block.
 * @param {ts.TypeChecker} checker Program type checker.
 * @returns {Documentation} Normalized documentation.
 */
function documentationFromBlock(block, checker)
{
	const [summary, description] = splitProse(commentText(block.comment));
	const docs = emptyDocumentation();
	docs.summary = summary;
	docs.description = description;
	for(const tag of block.tags ?? [])
	{
		const name = tag.tagName.text;
		const comment = commentText(tag.comment);
		if(ts.isJSDocCallbackTag(tag) && tag.typeExpression !== undefined)
		{
			for(const parameter of tag.typeExpression.parameters)
			{
				const parameterName = parameter.name.getText().replace(/^\[|\]$/g, '').split('=', 1)[0];
				docs.params[parameterName] = {type: jsdocTagType(parameter, checker), description: commentText(parameter.comment)};
			}
			if(tag.typeExpression.type !== undefined)
			{
				docs.returns = {type: jsdocTagType(tag.typeExpression.type, checker), description: commentText(tag.typeExpression.type.comment)};
			}
		}
		else if(ts.isJSDocParameterTag(tag))
		{
			const parameterName = tag.name.getText().replace(/^\[|\]$/g, '').split('=', 1)[0];
			docs.params[parameterName] = {type: jsdocTagType(tag, checker), description: comment};
		}
		else if(ts.isJSDocReturnTag(tag)) docs.returns = {type: jsdocTagType(tag, checker), description: comment};
		else if(name === 'throws' || name === 'exception') docs.throws.push({type: jsdocTagType(tag, checker), description: comment});
		else if(ts.isJSDocDeprecatedTag(tag)) docs.deprecated = comment || 'This API is deprecated.';
		else if(name === 'see') docs.see.push([tagNameText(tag), comment].filter(Boolean).join(' ').trim());
		else if(name === 'example') docs.examples.push(comment);
		else if(!['typedef', 'callback', 'type', 'private', 'public', 'protected', 'ignore'].includes(name)) docs.tags.push({name, value: comment});
	}
	return docs;
}

/**
 * Return the name-like payload used by tags such as `@see`.
 *
 * @param {ts.JSDocTag} tag JSDoc tag.
 * @returns {string} Tag name payload when present.
 */
function tagNameText(tag)
{
	if('name' in tag && tag.name !== undefined && tag.name !== null && typeof tag.name === 'object' && 'getText' in tag.name)
	{
		return /** @type {{getText:()=>string}} */ (tag.name).getText();
	}
	return '';
}

/**
 * Convert a JSDoc tag's explicit type into a normalized reference.
 *
 * @param {ts.JSDocTag} tag JSDoc tag with an optional type expression.
 * @param {ts.TypeChecker} checker Program type checker.
 * @returns {TypeReference} Explicit type or `unknown`.
 */
function jsdocTagType(tag, checker)
{
	if('typeExpression' in tag && tag.typeExpression !== undefined)
	{
		const expression = /** @type {ts.JSDocTypeExpression} */ (tag.typeExpression);
		return typeReference(expression.type.getText());
	}
	void checker;
	return typeReference('unknown');
}

/**
 * Convert TypeScript's structured JSDoc comments into plain text.
 *
 * @param {string|ts.NodeArray<ts.JSDocComment>|undefined} comment Compiler comment representation.
 * @returns {string} Trimmed prose with preserved line breaks.
 */
function commentText(comment)
{
	if(typeof comment === 'string') return comment.trim();
	if(comment === undefined) return '';
	return comment.map((part) => part.text).join('').trim();
}

/**
 * Split documentation into a lead paragraph and the remaining prose.
 *
 * @param {string} value Complete JSDoc prose.
 * @returns {[string,string]} Summary and description.
 */
function splitProse(value)
{
	const paragraphs = value.split(/\n\s*\n/).map((part) => part.trim()).filter(Boolean);
	return [paragraphs.shift() ?? '', paragraphs.join('\n\n')];
}

/**
 * Create a fresh empty documentation value.
 *
 * @returns {Documentation} Empty normalized documentation.
 */
function emptyDocumentation()
{
	return {summary: '', description: '', params: {}, returns: null, throws: [], deprecated: '', see: [], examples: [], tags: []};
}

/**
 * Convert one compiler type spelling into display text and link candidates.
 *
 * @param {string} display Compiler or JSDoc type spelling.
 * @returns {TypeReference} Normalized type reference.
 */
function typeReference(display)
{
	const normalized = display.trim() || 'unknown';
	const ignored = new Set(['any', 'unknown', 'never', 'void', 'undefined', 'null', 'boolean', 'number', 'bigint', 'string', 'symbol', 'object', 'function', 'true', 'false', 'this', 'readonly', 'keyof', 'typeof', 'new']);
	const references = [...normalized.matchAll(/[A-Za-z_$][\w$]*/g)]
		.map((match) => match[0])
		.filter((name) => !ignored.has(name.toLowerCase()));
	return {display: normalized, references: [...new Set(references)]};
}

/**
 * Build a concise heritage clause for a class signature.
 *
 * @param {ts.ClassDeclaration|ts.ClassExpression} declaration Class declaration.
 * @param {ts.TypeChecker} checker Program type checker.
 * @returns {string} Extends and implements suffix.
 */
function heritageText(declaration, checker)
{
	const clauses = [];
	for(const clause of declaration.heritageClauses ?? [])
	{
		const keyword = clause.token === ts.SyntaxKind.ExtendsKeyword ? 'extends' : 'implements';
		const types = clause.types.map((type) => checker.typeToString(checker.getTypeAtLocation(type), type, typeFormat));
		clauses.push(`${keyword} ${types.join(', ')}`);
	}
	return clauses.length === 0 ? '' : ' ' + clauses.join(' ');
}

/**
 * Collect stable declaration modifiers relevant to readers.
 *
 * @param {ts.Node} node Declaration node.
 * @returns {string[]} Ordered modifier names.
 */
function declarationFlags(node)
{
	if(!ts.canHaveModifiers(node)) return [];
	const allowed = new Set(['export', 'default', 'abstract', 'static', 'async', 'readonly', 'protected', 'public']);
	return (ts.getModifiers(node) ?? []).map((modifier) => modifier.getText()).filter((value) => allowed.has(value));
}

/**
 * Format a parameter list for generated declaration snippets.
 *
 * @param {GraphParameter[]} parameters Normalized parameters.
 * @returns {string} Comma-separated JavaScript parameter text.
 */
function parameterSignatures(parameters)
{
	return parameters.map((parameter) => {
		const prefix = parameter.rest ? '...' : '';
		const optional = parameter.optional && parameter.defaultValue === '' ? '?' : '';
		const defaultValue = parameter.defaultValue === '' ? '' : ` = ${parameter.defaultValue}`;
		return `${prefix}${parameter.name}${optional}: ${parameter.type.display}${defaultValue}`;
	}).join(', ');
}

/**
 * Return one stable language-qualified symbol identifier.
 *
 * @param {string} kind Symbol kind.
 * @param {string} module Module name.
 * @param {string} name Public symbol name.
 * @returns {string} Stable symbol ID.
 */
function symbolId(kind, module, name)
{
	return `js:${kind}:${module}#${name}`;
}

/**
 * Derive one portable class-member anchor.
 *
 * @param {string} kind Member kind.
 * @param {string} name Member name.
 * @returns {string} Lowercase anchor.
 */
function memberAnchor(kind, name)
{
	return `${kind}-${name}`.replace(/([a-z0-9])([A-Z])/g, '$1-$2').replace(/[^A-Za-z0-9_-]+/g, '-').replace(/^-|-$/g, '').toLowerCase();
}

/**
 * Resolve a compiler node into a project-relative source span.
 *
 * @param {ts.Node} node Declaration node.
 * @param {string} root Canonical project root.
 * @returns {{file:string,startLine:number,endLine:number}} Source span.
 */
function sourceSpan(node, root)
{
	const source = node.getSourceFile();
	const start = source.getLineAndCharacterOfPosition(node.getStart(source, false));
	const end = source.getLineAndCharacterOfPosition(node.getEnd());
	return {file: slash(path.relative(root, source.fileName)), startLine: start.line + 1, endLine: end.line + 1};
}

/**
 * Convert one compiler syntax diagnostic into the public graph form.
 *
 * @param {ts.Diagnostic} diagnostic Compiler diagnostic.
 * @param {SourceFile} file Source metadata.
 * @returns {Diagnostic} Graph diagnostic.
 */
function typescriptDiagnostic(diagnostic, file)
{
	const line = diagnostic.file !== undefined && diagnostic.start !== undefined
		? diagnostic.file.getLineAndCharacterOfPosition(diagnostic.start).line + 1
		: undefined;
	return {
		severity: 'error',
		code: `typescript.${diagnostic.code}`,
		message: ts.flattenDiagnosticMessageText(diagnostic.messageText, '\n'),
		file: file.relative,
		...(line === undefined ? {} : {line}),
	};
}

/**
 * Enforce author-selected documentation completeness against the final graph.
 *
 * @param {GraphSymbol[]} symbols Public symbols.
 * @param {Diagnostic[]} diagnostics Diagnostic accumulator.
 * @param {JsDocConfig} config Normalized configuration.
 * @returns {void}
 */
function applyDocumentationPolicy(symbols, diagnostics, config)
{
	for(const symbol of symbols)
	{
		policyDiagnostic(config.validation.summaries, symbol.docs.summary === '', 'docs.summary', `Missing summary for ${symbol.module}#${symbol.name}.`, symbol, diagnostics);
		validateCallable(symbol, symbol.parameters, symbol.returnType, symbol.docs, config, diagnostics);
		for(const member of symbol.members)
		{
			policyDiagnostic(config.validation.summaries, member.docs.summary === '', 'docs.summary', `Missing summary for ${symbol.name}.${member.name}.`, symbol, diagnostics, member.source.startLine);
			validateCallable(symbol, member.parameters, member.returnType, member.docs, config, diagnostics, member.source.startLine);
		}
		for(const reference of symbol.docs.see)
		{
			if(!relatedExists(reference, symbol, symbols))
			{
				policyDiagnostic(config.validation.brokenSee, true, 'docs.see', `Unresolved @see reference ${reference} on ${symbol.module}#${symbol.name}.`, symbol, diagnostics);
			}
		}
	}
}

/**
 * Enforce parameter and return documentation on one callable declaration.
 *
 * @param {GraphSymbol} owner Owning public symbol.
 * @param {GraphParameter[]} parameters Callable parameters.
 * @param {TypeReference|null} returnType Callable return type.
 * @param {Documentation} docs Callable documentation.
 * @param {JsDocConfig} config Normalized configuration.
 * @param {Diagnostic[]} diagnostics Diagnostic accumulator.
 * @param {number} [line] Member source line override.
 * @returns {void}
 */
function validateCallable(owner, parameters, returnType, docs, config, diagnostics, line)
{
	for(const parameter of parameters)
	{
		policyDiagnostic(config.validation.parameters, parameter.docs === null || parameter.docs.description === '', 'docs.param', `Missing @param description for ${owner.name}(${parameter.name}).`, owner, diagnostics, line);
	}
	const needsReturn = returnType !== null && !['void', 'undefined', 'never'].includes(returnType.display);
	policyDiagnostic(config.validation.returns, needsReturn && (docs.returns === null || docs.returns.description === ''), 'docs.returns', `Missing @returns description for ${owner.module}#${owner.name}.`, owner, diagnostics, line);
}

/**
 * Add one policy diagnostic when its condition and configured level require it.
 *
 * @param {ValidationLevel} level Configured policy level.
 * @param {boolean} condition Whether the defect exists.
 * @param {string} code Stable diagnostic code.
 * @param {string} message Diagnostic prose.
 * @param {GraphSymbol} symbol Related public symbol.
 * @param {Diagnostic[]} diagnostics Diagnostic accumulator.
 * @param {number} [line] Source line override.
 * @returns {void}
 */
function policyDiagnostic(level, condition, code, message, symbol, diagnostics, line)
{
	if(!condition || level === 'off') return;
	diagnostics.push({
		severity: level === 'error' ? 'error' : 'warning',
		code,
		message,
		file: symbol.source.file,
		line: line ?? symbol.source.startLine,
		symbolId: symbol.id,
	});
}

/**
 * Resolve a `@see` token against URLs, local members, and public graph symbols.
 *
 * @param {string} value Raw related reference.
 * @param {GraphSymbol} owner Owning symbol.
 * @param {GraphSymbol[]} symbols Public symbols.
 * @returns {boolean} Whether the target is resolvable.
 */
function relatedExists(value, owner, symbols)
{
	const token = value.trim().split(/\s+/, 1)[0].replace(/\(\)$/, '');
	if(token === '' || /^(?:https?:|#)/i.test(token)) return true;
	if(token.startsWith('.')) return owner.members.some((member) => member.name === token.replace(/^\./, ''));
	const [moduleOrName, exported] = token.split('#', 2);
	if(exported !== undefined) return symbols.some((symbol) => symbol.module === moduleOrName && symbol.name === exported);
	return symbols.some((symbol) => symbol.name === moduleOrName || `${symbol.module}.${symbol.name}` === moduleOrName);
}

/**
 * Remove duplicate compiler views of the same public ID while rejecting conflicts.
 *
 * @param {GraphSymbol[]} symbols Candidate symbols.
 * @returns {GraphSymbol[]} Unique symbols.
 */
function deduplicateSymbols(symbols)
{
	/** @type {Map<string,GraphSymbol>} */
	const unique = new Map();
	for(const symbol of symbols)
	{
		const prior = unique.get(symbol.id);
		if(prior === undefined) unique.set(symbol.id, symbol);
		else if(prior.source.file !== symbol.source.file || prior.source.startLine !== symbol.source.startLine)
		{
			throw new Error(`Conflicting declarations for ${symbol.id}.`);
		}
	}
	return [...unique.values()];
}

/**
 * Sort public symbols by module, public name, and kind.
 *
 * @param {GraphSymbol} left Left symbol.
 * @param {GraphSymbol} right Right symbol.
 * @returns {number} Sort comparison.
 */
function compareSymbols(left, right)
{
	return left.module.localeCompare(right.module) || left.name.localeCompare(right.name) || left.kind.localeCompare(right.kind);
}

/**
 * Sort diagnostics by source, line, severity, and stable code.
 *
 * @param {Diagnostic} left Left diagnostic.
 * @param {Diagnostic} right Right diagnostic.
 * @returns {number} Sort comparison.
 */
function compareDiagnostics(left, right)
{
	return (left.file ?? '').localeCompare(right.file ?? '') || (left.line ?? 0) - (right.line ?? 0)
		|| left.severity.localeCompare(right.severity) || left.code.localeCompare(right.code);
}

/**
 * Normalize one filesystem path for compiler lookups.
 *
 * @param {string} value Filesystem path.
 * @returns {string} Absolute case-stable path.
 */
function canonical(value)
{
	return path.resolve(value);
}

/**
 * Normalize operating-system path separators for stored source paths.
 *
 * @param {string} value Native path.
 * @returns {string} POSIX path.
 */
function slash(value)
{
	return value.split(path.sep).join('/');
}

/**
 * Report whether an unknown value is an ordinary object.
 *
 * @param {unknown} value Candidate value.
 * @returns {value is Record<string,unknown>} Whether the value is a mapping.
 */
function isRecord(value)
{
	return value !== null && typeof value === 'object' && !Array.isArray(value);
}
