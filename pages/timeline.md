---
title: Timeline
description: Add a dated archive with a calendar, year and month groups, and a page for every day with several posts.
weight: 3
guide:
  package: smgen-timeline
  example: examples/blog/
llms:
  section: plugins
  description: Activate smgen-timeline and customise its day rollup pages.
menus:
  documentation:
    title: Timeline
    weight: 26
jsonld:
  "@context": https://schema.org
  "@type": TechArticle
  headline: Timeline
  description: Add a dated archive with a calendar, year and month groups, and a page for every day with several posts.
  url: https://seanmorris.github.io/smgen-plugins/timeline.html
  isPartOf:
    "@id": https://seanmorris.github.io/smgen-plugins/#website
---

# Timeline

`smgen-timeline` provides the `timeline` menu renderer: a calendar whose arrows
and year chips step through every month with posts without leaving the page,
expandable year and month groups, and one line per post. Every page with a `date` joins it, and nothing else is required of the
author. The plugin depends on `smgen-categories` to build a page for each day
that has more than one post.

<p class="example-link"><a href="examples/blog/" target="_blank" rel="noopener noreferrer">Open the blog example</a></p>

## Install and Activate

```bash
npm install --save-dev smgen-timeline
```

```yaml
PLUGINS:
  - smgen-timeline
MENU_REGIONS:
  sidebar: [archive]
```

The plugin defines the `archive` menu. Render the region in the page template
with `renderMenuRegion('sidebar')`. A page opts out with `archive: false`, and a
page with its own `menus` field keeps that explicit membership.

## Day Rollups

A calendar day with one post links straight to it. A day with several posts
shows a count and links to a generated page listing them. Rollups come from
`smgen-categories` with a `day` grouping and a minimum of two members:

```yaml
PLUGINS:
  - use: smgen-timeline
    options:
      output: journal/{value}/index.md
      template: templates/day.md.php
```

`output` must contain `{value}`, the day as `2026-10-24`, and may publish a
directory index. `template` is a project-relative PHP file that receives the
same `$category`, `$members`, and `$context` as any category template. The
built-in template sets `archive: false` so a rollup never joins the timeline.

## Dates and Styles

A page's day comes from `date`, then `created`. A page with neither gets its
file birth time, first Git addition, or modification time, written back as
`created` so later builds agree. The widget links
`/smgen-timeline/timeline.css` only where it renders with content, and keeps
the `archive-*` class names from the built-in version, so existing CSS applies.
