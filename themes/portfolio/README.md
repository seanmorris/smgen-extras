# smgen-theme-portfolio

A photo-first personal site for someone who makes things, built with
[SMGen](https://seanmorris.github.io/smgen/). A statement and one large
photograph on the front page, a grid of work led by each project's picture,
project pages with a full-width image, a facts strip, prose and a small
gallery, then about and contact. No plugins.

```bash
npm create smgen-site@latest my-studio -- --theme portfolio
```

## What you get

- `pages/work/*.md`: one file per project. The front page shows the newest
  five (`show:` in `pages/index.md`), the first of them large, and
  `pages/work/index.md` shows them all, sorted by `year` then `weight`.
- `pages/index.md`, `pages/about.md`, `pages/contact.md`.
- `static/images/`: the sample photographs, each at 2400, 1200 and 800
  pixels wide. The template offers the smaller renditions through `srcset`
  when files named `<name>-1200.webp` and `<name>-800.webp` exist.
- `templates/page.php`, `static/portfolio.css`, `static/theme.js`.

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

Photographs are landscape, 3:2. A project with no `image:` yet shows a plain
tile in its `cloth:` colour (or the theme accent) so it can be published
before it is photographed.

Part of [smgen-extras](https://seanmorris.github.io/smgen-extras/themes.html#theme-portfolio).
