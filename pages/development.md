---
title: Development
description: Test, package, and release the SMGen plugin workspace.
weight: 4
layout: guide
llms:
  section: development
  description: Work on the plugin workspace and publish one package by tag.
menus:
  documentation:
    title: Development
    weight: 40
jsonld:
  "@context": https://schema.org
  "@type": TechArticle
  headline: SMGen Plugins Development
  description: Test, package, and release the SMGen plugin workspace.
  url: https://seanmorris.github.io/smgen-plugins/development.html
  isPartOf:
    "@id": https://seanmorris.github.io/smgen-plugins/#website
---

# Development

The repository is an npm workspace. Each plugin has its own package, version,
manifest, tests, and release tag.

## Check the Workspace

Install Node.js 20 or newer, PHP 8.2 with the YAML extension, Composer, and
Pandoc. Then run:

```bash
npm ci
npm run check
```

The check installs the pinned PHPDoc parser dependencies, enforces PHP and
JavaScript documentation, runs strict checked JSDoc, exercises each package,
builds a combined fixture site, and builds this documentation site.

The site launcher uses a local package or a sibling `static-gen` or `smgen`
checkout before it checks the system command. Set `SMGEN_BIN` when the current
checkout lives somewhere else:

```bash
SMGEN_BIN=/path/to/smgen/smgen.sh npm run check
```

## Preview This Site

```bash
npm run dev
```

SMGen serves the generated site at <http://localhost:8200> and rebuilds it when
the pages, templates, or static assets change.

## Release One Package

Each tag selects one workspace:

```text
smgen-data-pages-v0.1.0
smgen-store-v0.1.0
smgen-phpdoc-v0.1.0
smgen-jsdoc-v0.1.0
smgen-categories-v0.1.0
smgen-timeline-v0.1.0
```

The publish workflow compares the tag with that package's declared version,
runs the complete checks, inspects the npm archive, and publishes only the
selected workspace. `smgen-timeline` depends on `smgen-categories`, so publish
the categories package first whenever both change.

The [source repository](https://github.com/seanmorris/smgen-plugins) contains
the package contracts, fixtures, and release workflow.
