---
title: "About"
description: "Who Halden Bindery is, and what this site demonstrates."
---

# About

Halden Bindery is a fictional studio. The bindery, its town and its clients
were invented to demonstrate the SMGen **portfolio** theme, one of the base
themes in the [smgen-extras](https://seanmorris.github.io/smgen-extras/themes.html#portfolio)
collection.

## How the site works

Each project is one Markdown file under `pages/work/`. The front page and the
work page read every project's frontmatter at build time and draw the grid;
the covers are drawn from two colours named in that frontmatter, so a
project can be published before it has been photographed. Set `image:` on a
project to use a photograph instead.

Start a portfolio of your own with:

```bash
npm create smgen-site@latest my-studio -- --theme portfolio
```
