# smgen-theme-docs

A documentation theme for [SMGen](https://seanmorris.github.io/smgen/): a
contents tree on the left, the article in the middle, an outline on the
right, search in the header, callouts, a version badge and edit links. No
plugins.

```bash
npm create smgen-site@latest my-manual -- --theme docs
```

## What you get

- `pages/`: Markdown pages. The contents tree is SMGen's tree menu over this
  directory, so a new page or directory appears on its own. A directory's
  `.fm.yaml` names its group.
- Callouts: write `::: note`, `::: tip` or `::: warning` fenced divs.
- Search: set `SEARCH_ENABLED: auto` (the default here) and install
  `smgen-search` on the build machine; the search box appears when the index
  is built and stays hidden otherwise.
- `DOCS_VERSION` and `DOCS_REPOSITORY` in `.smgen.yaml` feed the masthead
  badge, the Source link and the per-page Edit link.
- `templates/page.php`, `static/docs.css`, `static/theme.js`: the template,
  the stylesheet, and the light and dark switch.

Part of [smgen-extras](https://seanmorris.github.io/smgen-extras/themes.html#docs).
