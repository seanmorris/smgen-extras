---
title: About the studio
description: Three people, one press, and a journal kept as a folder of plain files.
layout: page
leftBarShow: false
TOC: false
jsonld:
  "@context": https://schema.org
  "@type": AboutPage
  name: About Field Notes
  description: Three people, one press, and a journal kept as a folder of plain files.
---

# About the studio

Field Notes is the journal of a small print and design studio on the harbour.
There are three of us, one Vandercook proof press from 1962, one very good
kettle, and a window that faces the slipway.

We print cards, broadsheets, and the occasional book in small runs. We build
websites out of plain files for people who want them to last. The two kinds of
work have the same reason behind them: the simple tool is the one that is still
working in ten years.

## The people

- **Mara Bell** runs the press and mixes the ink. She writes most of the
  workshop notes and all of the reading lists.
- **Theo Okafor** sets type, rebuilds cabinets, and walks the sea wall in any
  weather.
- **June Castellanos** draws, binds, and keeps the flask filled for the early
  mornings at the ferry slip.

## Colophon

This journal is a folder of Markdown files. Every post is one file in
`pages/posts/` with a short block of frontmatter: a title, a date, an author,
and a topic. The words follow. At build time:

1. SMGen reads every post, builds the archive calendar and the RSS feed from
   the publication dates, and turns the Markdown into HTML.
2. The page template reads the same frontmatter to lay out the front page and
   the topic pages. There is no database and no generated index to keep in
   sync.
3. The result is copied to a folder of plain files along with one stylesheet.

The published site is HTML, CSS, and XML. There is no JavaScript on any page.
The type is whatever serif your device already has, which is faster than
anything we could send you.

[Back to the journal](index.html) · [Subscribe to the feed](feed.xml)
