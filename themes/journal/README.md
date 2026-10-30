# smgen-theme-journal

A notebook theme for [SMGen](https://seanmorris.github.io/smgen/). Dated
entries listed newest first, topic pages, months and years as running heads
in the margin, an RSS feed, and a light and dark toggle. No plugins.

```bash
npm create smgen-site@latest my-notebook -- --theme journal
```

## What you get

- `pages/posts/*.md`: one file per entry. The front page reads each file's
  frontmatter at build time, so adding an entry is adding a file.
- `pages/topics/*.md`: one file per topic with `layout: topic` and a
  `topic:` name. Entries name their topic with the same key; the navigation
  lists whatever topic files exist.
- `pages/index.md` and `pages/about.md`: the front page note and an about
  page.
- `templates/page.php`, `static/journal.css`, `static/theme.js`: the
  template, the stylesheet, and the theme switch.

## Writing an entry

```yaml
---
title: A hinge that does not squeak
description: One line shown in the list and the feed.
description-meta: The same line again, which SMGen uses for the feed item.
date: 2025-11-12
topic: Making
feeds: [posts]
---
```

`date` orders the list and places the entry under its month. `feeds: [posts]`
puts it in the RSS feed that SMGen core writes from `RSS_FEEDS` in
`.smgen.yaml`; update that feed's `title` and `description` when you rename
the site.

Part of [smgen-extras](https://seanmorris.github.io/smgen-extras/themes.html#journal).
