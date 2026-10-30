---
title: About the studio
description: A fictional studio built to demonstrate SMGen, and the plain files behind its journal.
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

**Field Notes is fiction.** The studio, its people, and every post on this
site were invented to demonstrate the [SMGen](https://seanmorris.github.io/smgen/)
static site generator and its plugins. Nothing here describes a real business,
a real place, or a real person, and any resemblance to one is accidental.

In the story, Field Notes is the journal of a small print and design studio on
the harbour: three people, one Vandercook proof press from 1962, one very good
kettle, and a window that faces the slipway.

We print cards, broadsheets, and the occasional book in small runs. We build
websites out of plain files for people who want them to last. The two kinds of
work have the same reason behind them: the simple tool is the one that is still
working in ten years.

## The people

The three authors are characters, not contributors. Their names appear on
posts to show how SMGen handles per-post authorship and feeds.

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

The published site is HTML, CSS, and XML, plus a few small scripts that
remember your open archive groups, fetch pages early, and switch calendar
months in place. Every link works without them. The type is whatever serif
your device already has, which is faster than anything we could send you.

The example lives in the [smgen-extras repository](https://github.com/seanmorris/smgen-extras/tree/main/examples/blog).

[Back to the journal](index.html) · [Subscribe to the feed](feed.xml)
