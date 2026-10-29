# smgen-phpdoc

`smgen-phpdoc` reads PHP source without running it and generates linked Markdown API pages for SMGen. SMGen then renders the pages with the site's templates, menus, search index, sitemap, `llms.txt`, JSON-LD, and assistant corpus.

The published site receives HTML from SMGen. On generated API pages the plugin adds one deferred script pair that re-highlights PHP code blocks in the browser; it adds no CSS.

## Install

Install the package beside SMGen:

```sh
npm install --save-dev smgen smgen-phpdoc
```

The package contains its pinned PHP parsers. A site needs Node.js 20 or newer and PHP 8.2 or newer. Site authors do not need Composer.

Add the plugin to `.smgen.yaml`:

```yaml
PLUGINS:
  - smgen-phpdoc
```

Copy [`examples/.smgen-phpdoc.yaml`](examples/.smgen-phpdoc.yaml) to the project root and change the package, source, navigation, and source-link values. A normal SMGen build runs the parser before page discovery:

```sh
npx smgen build
```

The prepare hook writes into SMGen's private generated-page directory. It does not edit `pages/`.

## Generate Pages Outside a Build

Use the CLI when a repository wants generated Markdown in its pages tree:

```sh
npx smgen-phpdoc generate
npx smgen-phpdoc check
```

`generate` owns only the configured `output` directory. It stages every page, validates the complete tree, and replaces that directory after the build succeeds. A second run compares content hashes and leaves file modification times unchanged.

`check` parses and renders in memory, then fails when the installed pages differ. `clean` removes the output only when its `.fm.yaml` contains the matching ownership marker.

```sh
npx smgen-phpdoc watch
npx smgen-phpdoc clean
```

`watch` polls configured source roots and regenerates after a change. Add project source paths to the plugin activation's `watch` list when `smgen watch` should also rebuild the site:

```yaml
PLUGINS:
  - use: smgen-phpdoc
    options:
      config: .smgen-phpdoc.yaml
    watch:
      - src
      - .smgen-phpdoc.yaml
```

## Configure Sources and Output

Configuration starts with `version: 1`. Unknown fields and wrong value types stop the build.

```yaml
version: 1
package:
  title: Project API
  description: Public classes and functions in Project.
  version: 2.4.0
sources:
  - path: src
    include: ['**/*.php']
    exclude: ['Internal/**']
php: '8.2'
output: api
visibility: [public]
grouping: namespace
```

Each source entry accepts `path`, `include`, and `exclude`. Patterns support `*`, `**`, and `?`. The default exclusions cover `vendor`, tests, fixtures, and caches. Discovery skips symbolic links and rejects a configured source root that crosses the project boundary.

`output` is relative to the SMGen pages root. It cannot be the pages root, an absolute path, or a path containing `..`. `visibility` accepts `public` and `protected`. Namespace grouping adds one index per namespace. Flat grouping keeps the package index and symbol pages.

## Add Navigation and Discovery Data

Generated pages use ordinary SMGen frontmatter:

```yaml
menu:
  name: documentation
  parent: reference
  weight: 60
  symbols: false
llms:
  section: reference
assistant: true
jsonld: true
```

The landing page joins `menu.name`. Set `symbols: true` to add symbol pages to the same menu. The named menu and parent must already exist in the site configuration.

Every namespace and symbol page also carries a `breadcrumbs` list: its ancestors in order, starting with the landing page and then each enclosing namespace. Each entry has a `title` and, under namespace grouping, the root-relative `path` of that namespace's index page. Templates can render the trail as links without knowing how the plugin names its routes:

```yaml
breadcrumbs:
  - title: Postbox API
    path: /api/
  - title: Postbox
    path: /api/namespaces/postbox/
  - title: Transport
    path: /api/namespaces/postbox/transport/
```

`llms` selects every generated page for one configured `LLMS` section. `assistant: true` selects the pages for `smgen-assistant`. `jsonld: true` adds `TechArticle` data to each page. These settings add metadata only. The corresponding SMGen feature or plugin still controls its output.

## Link to Source

Repository settings build line links without reading Git history:

```yaml
source:
  repository: https://github.com/example/project
  ref: 0123456789abcdef0123456789abcdef01234567
  prefix: packages/library
```

Use an immutable commit for stable published links. A custom template can replace the repository form:

```yaml
source:
  template: https://code.example/{ref}/{path}#L{line}
  ref: release-2.4.0
```

Templates receive `{ref}`, `{path}`, `{line}`, and `{endLine}`.

## Set Documentation Policy

Each rule accepts `off`, `warn`, or `error`:

```yaml
validation:
  summaries: error
  parameters: warn
  returns: warn
  unresolvedTypes: off
  brokenSee: warn
```

Warnings appear in the build output. Errors leave the previous generated tree and rendered site in place. External types remain readable even when `unresolvedTypes` reports them.

Set `prose: markdown` to preserve Markdown from PHPDoc. Set `prose: plain` to escape Markdown punctuation before rendering.

## Supported PHP

Version 1 reads namespaces, classes, interfaces, traits, enums, functions, constants, attributes, promoted properties, trait adaptations, nullable types, unions, intersections, DNF types, and source expressions used as defaults. It records native source spelling and canonical names supplied by imports.

The PHPDoc parser understands parameters, returns, thrown types, variables, deprecations, related symbols, templates, generics, array shapes, callable types, `class-string`, magic methods, and magic properties. Generated pages link known internal declarations. Unknown external declarations remain code text.

The parser records conditional declarations, `class_alias`, anonymous classes, and `eval` as static-analysis limits. Runtime-generated classes and bootstrap-only aliases cannot be recovered without executing the project. The default adapter never loads the target autoloader, includes source files, or runs bootstrap code.

## Choose the Page Heading Owner

Generated Markdown starts with a level-one heading by default. SMGen's stock template renders the Markdown body without adding another page heading.

A site template that renders the frontmatter `title` as its own H1 should turn off the body heading:

```yaml
bodyHeading: false
```

The plugin keeps `title` in frontmatter and preserves every section below the page heading. Trusted renderer modules receive the same heading choice in `page.content`.

## Override Rendering

Trusted ESM modules can replace the body for a page role:

```yaml
templates:
  landing: tools/phpdoc-landing.mjs
  namespace: tools/phpdoc-namespace.mjs
  symbol: tools/phpdoc-symbol.mjs
```

The module exports a default function or `render` function. It receives `{page, graph, config}` and returns Markdown text. `page.content` contains the default body. The plugin supplies validated frontmatter after the function returns.

Renderer modules execute with the build user's permissions. Keep them in the project and review them like other build hooks.

## Syntax Highlighting

Pandoc's PHP grammar colours only the first letter of a class or namespace name. The plugin ships [highlight.js](https://highlightjs.org/) and a small `syntax-highlight.js`, and loads both as deferred scripts on pages whose frontmatter carries `api-page: true`. The script re-tokenises each `php` code block and maps highlight.js scopes onto Pandoc's class names (`kw`, `dt`, `fu`, `va`, `st`, and so on), so the site's `HIGHLIGHT_STYLE` and any stylesheet written for Pandoc output keep working. Line spans and anchors are preserved.

Other pages can opt in by listing the same assets in `DEFER_SCRIPTS`:

```yaml
DEFER_SCRIPTS:
  - /smgen-phpdoc/vendor/highlight.js/highlight.min.js
  - /smgen-phpdoc/syntax-highlight.js
```

Set `window.smgenPhpDocHighlight = {languages: ['php', 'js']}` before the scripts load to re-highlight more languages.

## Generated Content Ownership

The CLI places an ownership record in `<output>/.fm.yaml` and a hash manifest under the configured `cache` directory, which defaults to `.smgen-phpdoc-cache`. It rejects unowned output, path traversal, and symbolic links. A failed parse, validation, renderer, or install leaves the prior pages intact.

Commit generated pages only when the project needs them without running the plugin. Otherwise ignore the configured output and cache, then let the SMGen prepare hook create pages for each build.

## Inspect the Contracts

[`schemas/config.schema.json`](schemas/config.schema.json) describes configuration version 1. [`schemas/graph.schema.json`](schemas/graph.schema.json) describes the adapter-to-renderer graph. The graph is an internal versioned contract for this package. It is not a general adapter SDK in version 1.

[`PROVENANCE.md`](PROVENANCE.md) records the Phonics prototype used to establish page and signature behavior. [`THIRD_PARTY_NOTICES.md`](THIRD_PARTY_NOTICES.md) records bundled parser licenses.

[`BENCHMARKS.md`](BENCHMARKS.md) records a release-candidate run against the pinned `php-wasm-engine` source snapshot, including first-generation and unchanged-build timings.
