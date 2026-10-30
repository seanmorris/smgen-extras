# smgen-theme-portfolio

A maker's catalogue set like a fine-press book, built with
[SMGen](https://seanmorris.github.io/smgen/). Running heads in small
capitals, numbered entries with the photograph mounted as a plate, project
pages with a title page, a specification block, a drop cap and further
plates, then about and contact. Bone paper, black ink, one rubric red, and
whatever serif the reader's machine has. No plugins.

```bash
npm create smgen-site@latest my-studio -- --theme portfolio
```

## What you get

- `pages/work/*.md`: one file per project. The front page lists the newest
  four (`show:` in `pages/index.md`) and `pages/work/index.md` lists them
  all, numbered in order of `year`, newest first, then `weight`. The three
  starter entries explain the frontmatter and ship without photographs.
- `pages/index.md`, `pages/about.md`, `pages/contact.md`.
- `static/images/`: put photographs here at 2400, 1200 and 800 pixels wide.
  The template offers the smaller renditions through `srcset` when files
  named `<name>-1200.webp` and `<name>-800.webp` exist.
- `templates/page.php`, `static/portfolio.css`, `static/theme.js`.

The sample content is a neutral starter. For a finished catalogue with
photographs built on this template, see the Halden Bindery example in
[smgen-extras](https://seanmorris.github.io/smgen-extras/examples/halden-bindery/)
and its source under `examples/halden-bindery`.

## A project

```yaml
---
title: "Harbour logbook"
description: "One line for the grid and the project page."
layout: project
kind: "Commission"
year: 2026
image: /images/harbour-logbook.webp
imageAlt: "What the photograph shows"
facts:
  Format: "Quarter leather over cloth"
  Pages: "320"
gallery:
  - image: /images/sewing.webp
    alt: "Hands sewing a text block"
    caption: "Sewn on tapes."
---
```

Photographs are landscape, 3:2; the catalogue crops them to 4:3 for the
small plate. A project with no `image:` yet is listed without a plate, so it
can be published before it is photographed.

Part of [smgen-extras](https://seanmorris.github.io/smgen-extras/themes.html#theme-portfolio).
