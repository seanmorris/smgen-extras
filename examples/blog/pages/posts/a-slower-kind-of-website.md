---
title: A slower kind of website
description: Why this journal is a folder of plain files, and the small build that turns them into pages.
date: '2026-06-23T10:20:00Z'
author: Mara Bell
topic: Tools
layout: post
leftBarShow: false
TOC: false
feeds:
- notes
rss:
  author: mara@fieldnotes.test (Mara Bell)
  category: Tools
jsonld:
  '@context': https://schema.org
  '@type': BlogPosting
  headline: A slower kind of website
  description: Why this journal is a folder of plain files, and the small build that turns them into pages.
  datePublished: '2026-06-23T10:20:00Z'
  articleSection: Tools
  author:
    '@type': Person
    name: Mara Bell
---

This journal is a spreadsheet. Each post is one row, with a title, a date, a category, and the words. A short script turns the rows into pages, and the pages into a site, and the site is a folder of HTML that could be served by anything.

We did it this way for the same reason we print on a hundred-year-old press: the simple tool is the one that is still working in ten years.

## What the build does

- Reads the spreadsheet and refuses to continue if a row is missing something it needs.
- Writes one page for every published row, and one index for all of them.
- Builds the archive calendar and the feed from the same dates the pages use.

Nothing happens in the browser. There is no script on this page unless you count the one that drew the little feed icon, and that is CSS.

## What it costs

About a minute to add a post, most of which is finding the right word for the description. The rest is a command.
