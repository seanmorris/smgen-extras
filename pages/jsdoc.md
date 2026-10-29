---
title: JavaScript API References
description: Generate linked SMGen API pages from JavaScript and JSDoc without running the source.
weight: 2
guide:
  package: smgen-jsdoc
  example: examples/dispatch/signalbox/
llms:
  section: plugins
  description: Configure smgen-jsdoc, its generated pages, documentation policy, and CLI.
menus:
  documentation:
    title: JavaScript API References
    weight: 15
jsonld:
  "@context": https://schema.org
  "@type": TechArticle
  headline: JavaScript API References
  description: Generate linked SMGen API pages from JavaScript and JSDoc without running the source.
  url: https://seanmorris.github.io/smgen-plugins/jsdoc.html
  isPartOf:
    "@id": https://seanmorris.github.io/smgen-plugins/#website
---

# JavaScript API References

`smgen-jsdoc` creates a TypeScript program over JavaScript source, reads its
exports and JSDoc, and turns the result into ordinary SMGen Markdown pages. It
does not import the modules or run their top-level statements.

<p class="example-link"><a href="examples/dispatch/signalbox/" target="_blank" rel="noopener noreferrer">Open the JSDoc example in the Dispatch site</a></p>

## Install and Activate

```bash
npm install --save-dev smgen smgen-jsdoc
```

```yaml
PLUGINS:
  - use: smgen-jsdoc
    options:
      config: .smgen-jsdoc.yaml
    watch:
      - src
      - .smgen-jsdoc.yaml
```

Create `.smgen-jsdoc.yaml` in the site root:

```yaml
version: 1
package:
  title: Project JavaScript API
  description: Public modules and exports in Project.
  version: 2.4.0
sources:
  - path: src
    include: ['**/*.js', '**/*.mjs']
    exclude: ['internal/**']
output: api
visibility: [public]
grouping: module
```

The default source patterns also include `.cjs`. Dependencies, tests, fixtures,
caches, and build output are excluded.

Run the normal build:

```bash
npx smgen build
```

The prepare hook writes into SMGen's private generated-page directory. The
plugin contributes no JavaScript or CSS to the published site.

## What It Documents

Version 1 reads exported functions, arrow functions, classes, constants, and
variables, plus named `@typedef` and `@callback` declarations. Class pages list
constructors, methods, accessors, fields, inferred types, defaults, examples,
and source locations.

Explicit JSDoc types take precedence. The TypeScript compiler fills in types
that JavaScript can express through inference. Known project types link to
their generated pages; external types remain readable code text.

`visibility: [public, protected]` includes members marked `@protected`.
Language-private fields and members marked `@private` or `@ignore` remain out
of the graph.

## Join the Rest of the Site

Generated pages carry normal SMGen frontmatter:

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

The landing page joins the selected menu. Set `symbols: true` to add export
pages. Module and export pages carry breadcrumbs. Search, the sitemap,
`llms.txt`, and the assistant corpus see the same generated Markdown.

## Choose the Page Heading Owner

Generated Markdown begins with its page title as an H1. SMGen's stock template
expects that heading in the body. If the site template renders the frontmatter
`title` as its own H1, turn off the generated copy:

```yaml
bodyHeading: false
```

The frontmatter title and the remaining section headings stay in place.

## Choose a Documentation Policy

Each rule accepts `off`, `warn`, or `error`:

```yaml
validation:
  summaries: error
  parameters: warn
  returns: warn
  brokenSee: warn
```

An error leaves the prior generated pages and published site in place.
Warnings name the export and source location.

## Link Back to Source

Repository settings add line links without reading Git history:

```yaml
source:
  repository: https://github.com/example/project
  ref: 0123456789abcdef0123456789abcdef01234567
  prefix: packages/library
```

Use an immutable commit when published documentation must keep pointing to the
same source.

## Run the CLI Directly

Repositories can keep generated Markdown in their pages tree:

```bash
npx smgen-jsdoc generate
npx smgen-jsdoc check
npx smgen-jsdoc watch
npx smgen-jsdoc clean
```

`generate` replaces only its owned output and preserves no-op modification
times. `check` reports drift without writing. `clean` requires the plugin's
ownership marker before it removes anything.

The [package reference on GitHub](https://github.com/seanmorris/smgen-plugins/tree/main/packages/smgen-jsdoc)
lists supported JSDoc forms, renderer overrides, and output safeguards.
