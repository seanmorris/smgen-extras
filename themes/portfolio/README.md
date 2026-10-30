# smgen-theme-portfolio

A personal site for someone who makes things, built with
[SMGen](https://seanmorris.github.io/smgen/). A statement on the front page,
a grid of work read from the project pages, project pages with a facts
strip, about and contact. Covers are drawn from two colours, so nothing has
to be photographed before the site goes up. No plugins.

```bash
npm create smgen-site@latest my-studio -- --theme portfolio
```

## What you get

- `pages/work/*.md`: one file per project. The front page shows the newest
  six (`show:` in `pages/index.md`) and `pages/work/index.md` shows them all,
  sorted by `year` then `weight`.
- `pages/index.md`, `pages/about.md`, `pages/contact.md`.
- `templates/page.php`, `static/portfolio.css`, `static/theme.js`.

## A project

```yaml
---
title: "Harbour logbook"
description: "One line for the grid and the project page."
layout: project
kind: "Commission"
year: 2026
cover:
  cloth: "#1f3a4d"
  paper: "#e9e2d2"
  label: "LOG"
facts:
  Format: "Quarter leather over cloth"
  Pages: "320"
---
```

Add `image: /images/logbook.jpg` to show a photograph instead of the drawn
cover; the file goes in `static/images/`.

Part of [smgen-extras](https://seanmorris.github.io/smgen-extras/themes.html#portfolio).
