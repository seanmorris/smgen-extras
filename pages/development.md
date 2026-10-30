---
title: Development
description: Test, package, and release the plugins, themes, and scaffolder in the SMGen Extras workspace.
weight: 4
layout: guide
llms:
  section: development
  description: Work on the workspace, add a theme, and publish one package by tag.
menus:
  documentation:
    title: Development
    weight: 40
jsonld:
  "@context": https://schema.org
  "@type": TechArticle
  headline: SMGen Plugins Development
  description: Test, package, and release the plugins, themes, and scaffolder in the SMGen Extras workspace.
  url: https://seanmorris.github.io/smgen-extras/development.html
  isPartOf:
    "@id": https://seanmorris.github.io/smgen-extras/#website
---

# Development

The repository is an npm workspace with two kinds of package and one site
that documents both:

```text
plugins/<name>/            smgen-<name>: a build-time plugin with its manifest and tests
themes/<name>/             smgen-theme-<name>: a complete starter site plus theme.json
themes/create-smgen-site/  the scaffolder that copies a theme into a new directory
examples/<name>/           the plugin demos and the theme demos, built into the hub under examples/
pages/, templates/, static/   this site
```

Each package has its own version and release tag. Themes have no code to
test beyond a build, so the workspace check scaffolds every one of them and
builds the result.

## Check the Workspace

Install Node.js 20 or newer, PHP 8.2 with the YAML extension, Composer, and
Pandoc. Then run:

```bash
npm ci
npm run check
```

The check installs the pinned PHPDoc parser dependencies, enforces PHP and
JavaScript documentation, runs strict checked JSDoc, exercises each plugin,
builds a combined fixture site, runs the scaffolder's unit tests, scaffolds
and builds every theme, and builds this documentation site with the four
examples and five theme demos inside it.

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

## Make a Theme

A theme directory is the site itself, so start by copying the one closest to
what you want, say `themes/semantic`, to `themes/<name>` and renaming the
package to `smgen-theme-<name>`. The scaffolder and this site depend on a few
conventions:

- The first lines of `.smgen.yaml` are `PRODUCT_NAME`, `TITLE_PREFIX`,
  `TAGLINE`, `BASE_URL: ''` and `OUTPUT_DIR: ./docs`, each on one line at
  the top level, and no other top-level line uses those names. The scaffolder
  rewrites exactly those lines; everything else is copied as is.
- Templates read `getConf('PRODUCT_NAME')` and `getConf('TAGLINE')` instead
  of repeating the demo's name, so the rename reaches every page.
- `theme.json` carries `name`, `package`, `title`, `description`,
  `demo.productName`, `demo.tagline`, `features`, `sampleContent`,
  `layouts` and `notes`. The [Themes](themes.html) page and
  `create-smgen-site --list` are rendered from it.
- `package.json` lists `.smgen.yaml` in `files` (npm drops dotfiles
  otherwise), and the sample content says somewhere that it is a demo.
- Light and dark are both designed: tokens on `:root`, the dark values under
  `@media (prefers-color-scheme: dark)` guarded by
  `:root:not([data-theme="light"])`, and again under `:root[data-theme="dark"]`.
- The theme's own pages are a neutral starter, so nobody scaffolds someone
  else's product. A finished demo lives under `examples/<name>` with only
  `pages/` and a `.smgen.yaml` whose `TEMPLATE_DIR` and `STATIC_DIR` point at
  `../../themes/<theme>`, so the demo can never drift from the theme; add the
  example to the loop in `scripts/build-site.sh` and name it in `theme.json`
  as `demo.path`, with `demo.starterPath` for the bare starter.

Then add `"smgen-theme-<name>": "<version>"` to
`themes/create-smgen-site/package.json` (the pin must equal the theme's own
version; a test fails on drift), run `npm install`, and run the check. The
hub build picks up any `themes/*/theme.json` on its own and publishes the
demo under `themes/<name>/`; add preview screenshots as
`static/previews/theme-<name>-dark.png` and `-light.png` at 900 by 585.

## Release One Package

Each tag selects one workspace by package name:

```text
smgen-data-pages-v0.1.0
smgen-store-v0.1.0
smgen-phpdoc-v0.1.0
smgen-jsdoc-v0.1.0
smgen-categories-v0.1.0
smgen-timeline-v0.1.0
smgen-theme-docs-v0.1.0
smgen-theme-journal-v0.1.0
smgen-theme-landing-v0.1.0
smgen-theme-portfolio-v0.1.0
smgen-theme-semantic-v0.1.0
create-smgen-site-v0.1.0
```

The publish workflow finds the workspace whose `package.json` name matches
the tag, compares the tag with that package's declared version, runs the
complete checks, inspects the npm archive, and publishes only the selected
workspace. Two orderings matter: `smgen-timeline` depends on
`smgen-categories`, so publish the categories package first whenever both
change; and `create-smgen-site` pins exact theme versions, so publish every
changed theme, then bump the pins and the scaffolder's own version, then
publish the scaffolder.

The [source repository](https://github.com/seanmorris/smgen-extras) contains
the package contracts, fixtures, and release workflow.
