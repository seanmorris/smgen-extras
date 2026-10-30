---
title: Writing pages
description: Frontmatter, menus and the outline in the semantic theme.
menus:
  site:
    weight: 10
---

# Writing pages

Every page is a Markdown file under `pages/` with a YAML block at the top.
SMGen turns `pages/guide/writing.md` into `guide/writing.html`.

## Frontmatter

```yaml
---
title: Writing pages
description: One sentence for search engines and link previews.
menus:
  site:
    weight: 10
---
```

`title` becomes the page heading in the browser tab and the menu. `description`
is used for the `meta` description. Set `TOC: false` to drop the outline
aside on short pages.

## The menu

The site menu is SMGen's core tree renderer over the pages directory. A page
joins it by naming the menu under `menus:`; `weight` orders siblings. A
directory becomes a group, titled by the `.fm.yaml` file inside it, and
`expanded: true` under the same `menus:` entry opens the group by default.

## The outline

Headings from `##` down become the "On this page" list in an `aside`. Keep
one `#` heading per page and start sections at `##` so the outline stays flat
enough to read.

## Breadcrumbs

Pages inside a directory get a breadcrumb trail above the heading, rendered
by SMGen core from the menu. Nothing in this theme needs to know about it.
