---
title: "About this manual"
description: "What this starter is and how its pages become a manual of your own."
menus:
  docs:
    weight: 90
---

# About this manual

This is the starter content of the SMGen **docs** theme, one of the base
themes in the [smgen-extras](https://seanmorris.github.io/smgen-extras/themes.html#theme-docs)
collection. The product it describes does not exist; the pages show the
shape a manual usually takes so you can replace them one at a time.

## How the site works

Every Markdown file under `pages/` is a page. The contents column is SMGen's
tree menu over that directory, so a new file appears on its own; a
directory's `.fm.yaml` gives it a title and an order. Section numbers, the
outline under the contents, and the notes in the margin come from the
stylesheet. Callouts are fenced divs: `::: note`, `::: tip`, `::: warning`.

Set `DOCS_VERSION` and `DOCS_REPOSITORY` in `.smgen.yaml` for the edition in
the masthead and the edit link at the foot of each page. Search appears when
the `smgen-search` package is installed on the build machine.

For a finished manual built on this theme, see the Kestrel example in
[smgen-extras](https://seanmorris.github.io/smgen-extras/examples/kestrel/).
