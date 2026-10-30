---
title: Themes
description: Start a complete SMGen site from one of five base themes with a single command.
weight: 0.5
layout: themes
llms:
  section: themes
  description: Scaffold a site from a theme with create-smgen-site, and what each theme contains.
menus:
  documentation:
    title: Themes
    weight: 5
jsonld:
  "@context": https://schema.org
  "@type": TechArticle
  headline: SMGen Themes
  description: Start a complete SMGen site from one of five base themes with a single command.
  url: https://seanmorris.github.io/smgen-extras/themes.html
  isPartOf:
    "@id": https://seanmorris.github.io/smgen-extras/#website
---

# Themes

A theme is a complete, working SMGen site: configuration, one PHP template,
a stylesheet, and real sample pages. Nothing in it needs a plugin. You copy
it, rename it, and start replacing the sample content with your own. Each
theme is also built and published here, so you can read it before you pick
it.

## Start a site

```bash
npm create smgen-site@latest my-site -- --theme journal
```

The command copies the theme into `my-site/`, writes your site's name and
tagline into `.smgen.yaml`, and prints what to do next. Name and tagline come
from the directory name unless you pass them:

```bash
npm create smgen-site@latest my-site -- --theme docs --name "Kestrel" --tagline "Backups you can read"
npm create smgen-site@latest -- --list
```

Then install SMGen and build:

```bash
cd my-site
git clone https://github.com/seanmorris/smgen.git ../smgen
../smgen/smgen.sh build
```

The site lands in `docs/`. Set `BASE_URL` in `.smgen.yaml` before you publish
under a path such as a GitHub Pages project site. Where the sample content
lives, and what to edit first, is listed per theme below and in the README
the command writes into the new site.

Older npm clients can run the same package directly:

```bash
npx create-smgen-site@latest my-site --theme journal
```

## Light and dark

Every theme except Semantic ships both palettes and follows the visitor's
system preference until they press the toggle, which then remembers their
choice on that browser. Semantic has no script at all and lets the browser
apply its own colour scheme. The previews below switch with this site's
theme.

## Make your own

A theme is an npm package named `smgen-theme-<name>` whose files are the
site itself, plus a `theme.json` manifest for the scaffolder and this page.
The shape, the few rules the scaffolder relies on, and how to add one to this
repository are described under
[Development](development.html#make-a-theme).
