---
title: About this notebook
description: What Commonplace is, who keeps it, and how it is built.
TOC: false
---

# About this notebook

Commonplace is a fictional notebook. Its keeper, its reading and its walks
are invented to demonstrate the SMGen **journal** theme, one of the base
themes in the [smgen-extras](https://seanmorris.github.io/smgen-extras/themes.html#journal)
collection. Nothing here is advice and nobody here is real.

## How it works

Every entry is one Markdown file in `pages/posts/` with a date, a topic and a
one-line summary in its frontmatter. The front page reads those files at build
time and lists them by month; the topic pages filter the same list. There is
no plugin, no database and nothing to regenerate by hand. SMGen core writes
the RSS feed from the same frontmatter.

Start a notebook of your own with:

```bash
npm create smgen-site@latest my-notebook -- --theme journal
```
