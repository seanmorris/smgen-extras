---
title: What an entry needs
layout: post
description: The six lines of frontmatter at the top of every entry, and what each one is for.
description-meta: The six lines of frontmatter at the top of every entry, and what each one is for.
date: 2026-01-12
topic: Notes
feeds: [posts]
author: Your Name
---

Every entry starts with a few lines between `---` marks:

- `title`, which is also the heading.
- `layout: post`, so the theme shows the date and the previous and next links.
- `description` and `description-meta`, one sentence for the list and the feed.
- `date`, which decides the order and the month in the margin.
- `topic`, matching a file under `pages/topics/`.
- `feeds: [posts]`, so the entry appears in the RSS feed.

Then the entry itself, in Markdown. Headings, lists, quotations and code all
work; keep them small.
