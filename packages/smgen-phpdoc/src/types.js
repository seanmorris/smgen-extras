// @ts-check

/** @typedef {'off'|'warn'|'error'} ValidationLevel */

/**
 * @typedef {object} SourceConfig
 * @property {string} path Project-relative source root.
 * @property {string[]} include Included source-root-relative globs.
 * @property {string[]} exclude Excluded source-root-relative globs.
 */

/**
 * @typedef {object} PhpDocConfig
 * @property {1} version Configuration schema version.
 * @property {string} root Absolute project root.
 * @property {string} configPath Absolute configuration path.
 * @property {{title:string, description:string, version:string}} package Package metadata shown in generated pages.
 * @property {SourceConfig[]} sources Static-analysis source roots.
 * @property {string} php Target PHP syntax version.
 * @property {string} output Pages-root-relative managed output path.
 * @property {Array<'public'|'protected'>} visibility Included declaration visibility.
 * @property {'namespace'|'flat'} grouping Index grouping mode.
 * @property {{overrides:Record<string,string>}} routes Stable-ID route overrides.
 * @property {{name:string|null, parent:string|null, weight:number, symbols:boolean}} menu Menu frontmatter settings.
 * @property {false|{section:string, description:string}} llms llms.txt page selection.
 * @property {boolean} assistant Whether generated pages select smgen-assistant.
 * @property {boolean} jsonld Whether generated pages carry TechArticle JSON-LD.
 * @property {{repository:string, ref:string, prefix:string, template:string}} source Source-link settings.
 * @property {{summaries:ValidationLevel, parameters:ValidationLevel, returns:ValidationLevel, unresolvedTypes:ValidationLevel, brokenSee:ValidationLevel}} validation Documentation policy.
 * @property {'markdown'|'plain'} prose Documentation prose handling policy.
 * @property {string} cache Project-relative cache directory.
 * @property {{landing:string, namespace:string, symbol:string}} templates Optional trusted renderer modules.
 */

/**
 * @typedef {object} SourceFile
 * @property {string} absolute Absolute source path.
 * @property {string} relative Project-relative POSIX path.
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
 * @property {string[]} references Canonical named type references.
 */

/**
 * @typedef {object} Documentation
 * @property {string} summary First documentation paragraph.
 * @property {string} description Remaining documentation prose.
 * @property {Record<string,{type:TypeReference, description:string}>} params Parameter documentation keyed without `$`.
 * @property {{type:TypeReference, description:string}|null} returns Return documentation.
 * @property {Array<{type:TypeReference, description:string}>} throws Thrown type documentation.
 * @property {{type:TypeReference, description:string}|null} var Property or constant type documentation.
 * @property {string} deprecated Deprecation explanation.
 * @property {string[]} see Related symbol or URL values.
 * @property {Array<{name:string, value:string}>} tags Normalized PHPDoc tags.
 * @property {Array<Record<string, unknown>>} magicMethods Documented virtual methods.
 * @property {Array<Record<string, unknown>>} magicProperties Documented virtual properties.
 */

/**
 * @typedef {object} GraphMember
 * @property {string} id Stable member ID.
 * @property {string} kind Member kind.
 * @property {string} name Member name.
 * @property {string} anchor Stable page anchor.
 * @property {string} signature PHP declaration text.
 * @property {string[]} flags Declaration flags.
 * @property {TypeReference|null} type Declared value type.
 * @property {TypeReference|null} returnType Declared return type.
 * @property {Array<Record<string, unknown>>} parameters Function parameters.
 * @property {Documentation} docs Parsed PHPDoc.
 * @property {Array<Record<string, unknown>>} attributes Attributes attached to the member.
 * @property {{file:string,startLine:number,endLine:number}} source Source span.
 * @property {boolean} virtual Whether PHPDoc rather than PHP syntax declared the member.
 * @property {{from:string,docs:Documentation}} [inheritedDocs] Separately retained ancestor documentation.
 */

/**
 * @typedef {object} GraphSymbol
 * @property {string} id Stable symbol ID.
 * @property {string} kind Symbol kind.
 * @property {string} name Qualified symbol name.
 * @property {string} shortName Unqualified symbol name.
 * @property {string} namespace Declaring namespace.
 * @property {string} signature PHP declaration text.
 * @property {string[]} flags Declaration flags.
 * @property {Array<Record<string, unknown>>} attributes Attributes attached to the declaration.
 * @property {Documentation} docs Parsed PHPDoc.
 * @property {TypeReference[]} extends Extended types.
 * @property {TypeReference[]} implements Implemented interfaces.
 * @property {Array<Record<string, unknown>>} traits Trait relationships and adaptations.
 * @property {GraphMember[]} members Declared and documented virtual members.
 * @property {Array<Record<string, unknown>>} [parameters] Function parameters.
 * @property {TypeReference|null} [returnType] Function return type.
 * @property {{file:string,startLine:number,endLine:number}} source Source span.
 * @property {boolean} conditional Whether control flow contains the declaration.
 */

/**
 * @typedef {object} DocumentationGraph
 * @property {1} schemaVersion Documentation graph schema version.
 * @property {{title:string,description:string,version:string}} package Package metadata.
 * @property {Diagnostic[]} diagnostics Static-analysis diagnostics.
 * @property {GraphSymbol[]} symbols Deterministically sorted declarations.
 */

/**
 * @typedef {object} RenderedPage
 * @property {string} path Output-root-relative Markdown path.
 * @property {string} route Output-root-relative public route without a leading slash.
 * @property {string} title Page title.
 * @property {string} summary Page summary.
 * @property {'landing'|'namespace'|'symbol'} kind Page role.
 * @property {GraphSymbol|null} symbol Related symbol for symbol pages.
 * @property {string} content Complete Markdown source.
 */

export {};
