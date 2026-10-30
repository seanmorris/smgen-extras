---
title: "About this manual"
description: "This manual is a demonstration of the SMGen docs theme."
menus:
  docs:
    weight: 90
---

# About this manual

Kestrel does not exist. Its commands, options and releases were invented to
demonstrate the SMGen **docs** theme, one of the base themes in the
[smgen-extras](https://seanmorris.github.io/smgen-extras/themes.html#docs)
collection.

The theme gives a manual three columns: a contents tree built by SMGen core
from the pages directory, the article, and an outline from the page's own
headings. Callouts are fenced divs (`::: note`, `::: tip`, `::: warning`),
search comes from `smgen-search` when it is installed, and the version badge
and edit links come from two keys in `.smgen.yaml`.

Start a manual of your own with:

```bash
npm create smgen-site@latest my-manual -- --theme docs
```
