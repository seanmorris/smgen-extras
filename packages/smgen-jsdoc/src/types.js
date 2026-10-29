// @ts-check

/** @typedef {'off'|'warn'|'error'} ValidationLevel */

/**
 * @typedef {object} SourceConfig
 * @property {string} path Project-relative source root.
 * @property {string[]} include Included source-root-relative globs.
 * @property {string[]} exclude Excluded source-root-relative globs.
 */

/**
 * @typedef {object} JsDocConfig
 * @property {1} version Configuration schema version.
 * @property {string} root Absolute project root.
 * @property {string} configPath Absolute configuration path.
 * @property {{title:string,description:string,version:string}} package Package metadata shown in generated pages.
 * @property {SourceConfig[]} sources Static-analysis source roots.
 * @property {string} output Pages-root-relative managed output path.
 * @property {Array<'public'|'protected'>} visibility Included class-member visibility.
 * @property {'module'|'flat'} grouping Index grouping mode.
 * @property {{overrides:Record<string,string>}} routes Stable-ID route overrides.
 * @property {{name:string|null,parent:string|null,weight:number,symbols:boolean}} menu Menu frontmatter settings.
 * @property {false|{section:string,description:string}} llms llms.txt page selection.
 * @property {boolean} assistant Whether generated pages select smgen-assistant.
 * @property {boolean} jsonld Whether generated pages carry TechArticle JSON-LD.
 * @property {{repository:string,ref:string,prefix:string,template:string}} source Source-link settings.
 * @property {{summaries:ValidationLevel,parameters:ValidationLevel,returns:ValidationLevel,brokenSee:ValidationLevel}} validation Documentation policy.
 * @property {'markdown'|'plain'} prose Documentation prose handling policy.
 * @property {string} cache Project-relative cache directory.
 * @property {{landing:string,module:string,symbol:string}} templates Optional trusted renderer modules.
 */

/**
 * @typedef {object} SourceFile
 * @property {string} absolute Absolute source path.
 * @property {string} relative Project-relative POSIX path.
 * @property {string} module Stable source-root-relative module name.
 */

/**
 * @typedef {object} Diagnostic
 * @property {'info'|'warning'|'error'} severity Diagnostic severity.
 * @property {string} code Stable diagnostic code.
 * @property {string} message Human-readable diagnostic.
 * @property {string} [file] Project-relative source path.
 * @property {number} [line] One-based source line.
 * @property {string} [symbolId] Related stable symbol ID.
 */

/**
 * @typedef {object} TypeReference
 * @property {string} display Source-facing type spelling.
 * @property {string[]} references Named type references found in the spelling.
 */

/**
 * @typedef {object} Documentation
 * @property {string} summary First documentation paragraph.
 * @property {string} description Remaining documentation prose.
 * @property {Record<string,{type:TypeReference,description:string}>} params Parameter documentation keyed by name.
 * @property {{type:TypeReference,description:string}|null} returns Return documentation.
 * @property {Array<{type:TypeReference,description:string}>} throws Thrown type documentation.
 * @property {string} deprecated Deprecation explanation.
 * @property {string[]} see Related symbol or URL values.
 * @property {string[]} examples Documented examples.
 * @property {Array<{name:string,value:string}>} tags Other normalized JSDoc tags.
 */

/**
 * @typedef {object} GraphParameter
 * @property {string} name Parameter name.
 * @property {TypeReference} type Effective parameter type.
 * @property {boolean} optional Whether callers may omit the parameter.
 * @property {boolean} rest Whether the parameter collects remaining arguments.
 * @property {string} defaultValue Source default expression, when present.
 * @property {{type:TypeReference,description:string}|null} docs JSDoc parameter entry.
 */

/**
 * @typedef {object} GraphMember
 * @property {string} id Stable member ID.
 * @property {'constructor'|'method'|'property'|'getter'|'setter'} kind Member kind.
 * @property {string} name Member name.
 * @property {string} anchor Stable page anchor.
 * @property {string} signature JavaScript-facing declaration text.
 * @property {string[]} flags Declaration flags.
 * @property {TypeReference|null} type Property type.
 * @property {TypeReference|null} returnType Callable return type.
 * @property {GraphParameter[]} parameters Callable parameters.
 * @property {Documentation} docs Parsed JSDoc.
 * @property {{file:string,startLine:number,endLine:number}} source Source span.
 */

/**
 * @typedef {object} GraphSymbol
 * @property {string} id Stable symbol ID.
 * @property {'class'|'function'|'constant'|'variable'|'typedef'|'callback'} kind Symbol kind.
 * @property {string} name Public export or type name.
 * @property {string} module Declaring module name.
 * @property {string} signature JavaScript-facing declaration text.
 * @property {string[]} flags Declaration flags.
 * @property {TypeReference|null} type Value or typedef type.
 * @property {TypeReference|null} returnType Callable return type.
 * @property {GraphParameter[]} parameters Callable parameters.
 * @property {GraphMember[]} members Visible class members.
 * @property {Documentation} docs Parsed JSDoc.
 * @property {{file:string,startLine:number,endLine:number}} source Source span.
 */

/**
 * @typedef {object} DocumentationGraph
 * @property {1} schemaVersion Documentation graph schema version.
 * @property {{title:string,description:string,version:string}} package Package metadata.
 * @property {Diagnostic[]} diagnostics Static-analysis and documentation diagnostics.
 * @property {GraphSymbol[]} symbols Deterministically sorted public declarations.
 */

/**
 * @typedef {object} RenderedPage
 * @property {string} path Output-root-relative Markdown path.
 * @property {string} route Output-root-relative public route without a leading slash.
 * @property {string} title Page title.
 * @property {string} summary Page summary.
 * @property {'landing'|'module'|'symbol'} kind Page role.
 * @property {GraphSymbol|null} symbol Related symbol for symbol pages.
 * @property {string|null} module Module the page documents or belongs to.
 * @property {string} content Complete Markdown source.
 */

export {};
