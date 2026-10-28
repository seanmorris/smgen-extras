---
title: SMGen Plugins
description: Add spreadsheet-driven pages, static checkout controls, and PHP API references to an SMGen site.
TOC: false
llms:
  section: start
  description: Choose and install the official SMGen plugins.
menus:
  documentation:
    title: Overview
    weight: 0
jsonld:
  "@context": https://schema.org
  "@graph":
    - "@type": WebSite
      "@id": https://seanmorris.github.io/smgen-plugins/#website
      url: https://seanmorris.github.io/smgen-plugins/
      name: smgen-plugins
      description: Focused extensions for static SMGen sites.
      inLanguage: en
    - "@type": CollectionPage
      "@id": https://seanmorris.github.io/smgen-plugins/#page
      url: https://seanmorris.github.io/smgen-plugins/
      name: SMGen Plugins
      isPartOf:
        "@id": https://seanmorris.github.io/smgen-plugins/#website
      about:
        "@type": SoftwareApplication
        name: SMGen
        url: https://seanmorris.github.io/smgen/
---

# Build more with SMGen

SMGen plugins add focused build steps without turning the published site into
an application. They produce Markdown, HTML, CSS, and compact data files that
can live on GitHub Pages or any other static host.

::: {.plugin-grid}
::: {.plugin-card}
## PHP API references

`smgen-phpdoc` reads PHP source without running it. It creates linked API pages
that join the site's navigation, search, sitemap, `llms.txt`, and assistant
corpus.

[Generate PHP documentation](phpdoc.html)

<a href="examples/phpdoc/" target="_blank" rel="noopener noreferrer">Open the example</a>
:::

::: {.plugin-card}
## Pages from a spreadsheet

`smgen-data-pages` validates CSV or TSV rows and renders one Markdown page per
row with a PHP template. A local command catches malformed data before a build.

[Build data-driven pages](data-pages.html)

<a href="examples/data-pages/" target="_blank" rel="noopener noreferrer">Open the example</a>
:::

::: {.plugin-card}
## Static storefronts

`smgen-store` validates product metadata and inserts hosted checkout controls.
Pricing, payment state, and fulfillment remain with the payment provider.

[Add a storefront](store.html)

<a href="examples/store/" target="_blank" rel="noopener noreferrer">Open the example</a>
:::
:::

## A Blog from CSV

The blog example turns dated CSV rows into posts with `smgen-data-pages`.
SMGen uses the generated dates and flags to build its calendar archive and RSS
feed.

<a href="examples/blog/" target="_blank" rel="noopener noreferrer">Open the blog example</a>

## Install a Plugin

Install the package beside SMGen, then activate it in `.smgen.yaml`:

```bash
npm install --save-dev smgen-phpdoc
```

```yaml
PLUGINS:
  - smgen-phpdoc
```

Run the normal site build. SMGen loads the package manifest, runs its declared
hook, and keeps output replacement atomic.

```bash
npx smgen build
```

The packages use the versioned `smgen/v1` plugin API. They do not add browser
assets unless the published feature needs them.

## Combine the Packages

Data pages and the store plugin form a small static commerce pipeline. A CSV
row supplies product data, a PHP template turns the row into Markdown and
frontmatter, and `smgen-store` validates the checkout metadata before SMGen
publishes the page.

PHPDoc is independent. It can document the PHP used by a site, a library, or
another plugin in the same build.
