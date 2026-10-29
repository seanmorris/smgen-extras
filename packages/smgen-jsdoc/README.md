# smgen-jsdoc

`smgen-jsdoc` reads JavaScript and JSDoc without importing the source modules,
then generates linked Markdown API pages for SMGen. SMGen renders those pages
with the site's templates, menus, search index, sitemap, `llms.txt`, JSON-LD,
and assistant corpus.

The published site receives ordinary HTML and no browser assets from this
plugin.

## Install

Install the package beside SMGen:

```sh
npm install --save-dev smgen smgen-jsdoc
```

Add the plugin to `.smgen.yaml`:

```yaml
PLUGINS:
  - smgen-jsdoc
```

Create `.smgen-jsdoc.yaml` in the project root:

```yaml
version: 1
package:
  title: Project JavaScript API
  description: Public modules and exports in Project.
  version: 2.4.0
sources:
  - path: src
output: api
```

A normal SMGen build runs the analyzer before page discovery:

```sh
npx smgen build
```

The prepare hook writes into SMGen's private generated-page directory. It does
not edit `pages/`.

## Generate Pages Outside a Build

Use the CLI when a repository wants generated Markdown in its pages tree:

```sh
npx smgen-jsdoc generate
npx smgen-jsdoc check
```

`generate` owns only the configured `output` directory. It stages and verifies
the complete tree before replacing that directory. A no-op build preserves file
modification times.

`check` analyzes and renders in memory, then fails when the installed pages
differ. `clean` removes output only when its `.fm.yaml` contains this plugin's
ownership marker.

```sh
npx smgen-jsdoc watch
npx smgen-jsdoc clean
```

`watch` polls configured source roots. Add source paths to the plugin
activation when `smgen watch` should rebuild the site too:

```yaml
PLUGINS:
  - use: smgen-jsdoc
    options:
      config: .smgen-jsdoc.yaml
    watch:
      - src
      - .smgen-jsdoc.yaml
```

## Configure Sources and Output

Configuration starts with `version: 1`. Unknown fields and wrong value types
stop the build.

```yaml
version: 1
package:
  title: Project JavaScript API
  description: Public modules and exports in Project.
sources:
  - path: src
    include: ['**/*.js', '**/*.mjs']
    exclude: ['internal/**']
output: api
visibility: [public]
grouping: module
```

Each source accepts `path`, `include`, and `exclude`. Patterns support `*`,
`**`, and `?`. Defaults include `.js`, `.mjs`, and `.cjs` modules and exclude
dependencies, tests, fixtures, caches, and build output. Discovery skips
symbolic links and rejects a source root outside the project.

`output` is relative to the SMGen pages root. It cannot be the pages root, an
absolute path, or a path containing `..`. Module grouping adds one index per
source module. Flat grouping keeps the package index and export pages.

JavaScript has no `protected` keyword. `visibility: [public, protected]`
includes class members carrying `@protected`; language-private fields and
members carrying `@private` or `@ignore` remain hidden.

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

The landing page joins `menu.name`. Set `symbols: true` to add export pages to
the same menu. Module and export pages carry breadcrumbs that begin at the API
landing page.

`llms` selects generated pages for one configured `LLMS` section.
`assistant: true` selects them for `smgen-assistant`. `jsonld: true` adds
`TechArticle` data with `JavaScript` as the programming language. The matching
SMGen feature or plugin still controls each output.

## Link to Source

Repository settings build line links without reading Git history:

```yaml
source:
  repository: https://github.com/example/project
  ref: 0123456789abcdef0123456789abcdef01234567
  prefix: packages/library
```

Use an immutable commit for stable published links. A custom template can
replace the repository form:

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
  brokenSee: warn
```

Warnings appear during the build. Errors leave the prior generated tree and
site in place. `parameters` checks for a description on every callable
parameter. `returns` checks non-void callables. `brokenSee` checks URLs, local
members, `Module#export` references, and unambiguous export names.

Set `prose: markdown` to retain Markdown in JSDoc. Set `prose: plain` to escape
Markdown punctuation before rendering.

## Supported JavaScript and JSDoc

Version 1 documents exports from `.js`, `.mjs`, and `.cjs` files. It recognizes
functions, arrow functions, classes, constants, variables, `@typedef`, and
`@callback`. Class pages include constructors, methods, getters, setters,
fields, inferred types, default values, and source spans.

The JSDoc reader uses summaries, longer descriptions, parameters, returns,
throws, examples, deprecations, and related symbols. Types come from explicit
JSDoc when present and from the TypeScript compiler's JavaScript inference
otherwise. Known project types become links; external types remain readable
code text.

The analyzer creates a TypeScript program over the configured files. It never
imports a project module, calls its exports, or runs its top-level statements.
Syntax errors stop generation before output changes.

## Choose the Page Heading Owner

Generated Markdown starts with a level-one heading by default. SMGen's stock
template renders the Markdown body without adding another page heading.

A site template that renders the frontmatter `title` as its own H1 should turn
off the body heading:

```yaml
bodyHeading: false
```

The plugin keeps `title` in frontmatter and preserves every section below the
page heading. Trusted renderer modules receive the same heading choice in
`page.content`.

## Override Rendering

Trusted ESM modules can replace the body for a page role:

```yaml
templates:
  landing: tools/jsdoc-landing.mjs
  module: tools/jsdoc-module.mjs
  symbol: tools/jsdoc-symbol.mjs
```

The module exports a default function or `render` function. It receives
`{page, graph, config}` and returns Markdown. `page.content` contains the
default body. The plugin adds validated frontmatter after the function returns.

Renderer modules execute with the build user's permissions. Review them like
other build hooks.

## Generated Content Ownership

The CLI places an ownership record in `<output>/.fm.yaml` and a hash manifest
under `cache`, which defaults to `.smgen-jsdoc-cache`. It rejects unowned
output, path traversal, and symbolic links. Failed analysis, validation,
rendering, or installation leaves the prior pages intact.

Commit generated pages only when the project needs them without the plugin.
Otherwise ignore the configured output and cache and let the prepare hook
create pages for each build.

[`schemas/config.schema.json`](schemas/config.schema.json) describes the
version 1 configuration contract. [`schemas/graph.schema.json`](schemas/graph.schema.json)
describes the graph passed to trusted renderer modules.
