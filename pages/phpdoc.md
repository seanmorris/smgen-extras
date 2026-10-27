---
title: PHP API References
description: Generate linked SMGen API pages through static PHP analysis.
weight: 1
llms:
  section: plugins
  description: Configure smgen-phpdoc, its generated pages, validation policy, and CLI.
menus:
  documentation:
    title: PHP API References
    weight: 10
jsonld:
  "@context": https://schema.org
  "@type": TechArticle
  headline: PHP API References
  description: Generate linked SMGen API pages through static PHP analysis.
  url: https://seanmorris.github.io/smgen-plugins/phpdoc.html
  isPartOf:
    "@id": https://seanmorris.github.io/smgen-plugins/#website
---

# PHP API References

`smgen-phpdoc` parses PHP source without loading the project's autoloader,
including a source file, or running bootstrap code. It turns the resulting
symbol graph into ordinary SMGen Markdown pages.

<p class="example-link"><a href="examples/phpdoc/" target="_blank" rel="noopener noreferrer">Open the PHPDoc example site</a></p>

## Install and Activate

The npm package contains pinned copies of its PHP parsers. Site authors need
Node.js 20 or newer and PHP 8.2 or newer. They do not need Composer.

```bash
npm install --save-dev smgen smgen-phpdoc
```

```yaml
PLUGINS:
  - use: smgen-phpdoc
    options:
      config: .smgen-phpdoc.yaml
    watch:
      - src
      - .smgen-phpdoc.yaml
```

Create `.smgen-phpdoc.yaml` in the site root:

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

Run the normal build:

```bash
npx smgen build
```

The prepare hook writes into SMGen's private generated-page directory. SMGen
removes that temporary source tree after it publishes the HTML.

## Join the Rest of the Site

Generated pages carry normal SMGen frontmatter. Select the navigation,
`llms.txt`, assistant, and JSON-LD features in the PHPDoc configuration:

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

The landing page joins the selected menu. Set `symbols: true` to add each
symbol page as well. Search indexing, the sitemap, `llms.txt`, and the
assistant corpus use the same generated Markdown.

## Choose a Documentation Policy

Each validation rule accepts `off`, `warn`, or `error`:

```yaml
validation:
  summaries: error
  parameters: warn
  returns: warn
  unresolvedTypes: off
  brokenSee: warn
```

An error leaves the prior generated pages and published site in place.
Warnings name the affected declaration and source location.

## Link Back to Source

Repository settings add stable line links without reading Git history:

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
npx smgen-phpdoc generate
npx smgen-phpdoc check
npx smgen-phpdoc watch
npx smgen-phpdoc clean
```

`generate` replaces only the configured output directory and leaves no-op file
modification times intact. `check` reports drift without writing. `clean`
requires the plugin's ownership marker before it removes anything.

The [package reference on GitHub](https://github.com/seanmorris/smgen-plugins/tree/main/packages/smgen-phpdoc)
lists supported declarations, PHPDoc forms, renderer overrides, and the static
analysis limits.
