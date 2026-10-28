---
title: How This Blog Is Built
description: Follow one CSV row through the data-page, timeline, RSS, and JSON-LD build steps.
TOC: true
jsonld:
  "@context": https://schema.org
  "@type": TechArticle
  headline: How This Blog Is Built
  description: Follow one CSV row through the data-page, timeline, RSS, and JSON-LD build steps.
---

# How this blog is built

The example combines one plugin with SMGen's normal page metadata. It publishes
ordinary HTML and XML.

## Read the spreadsheet

`smgen-data-pages` reads `data/posts.csv`. Its configuration selects published
rows, checks required columns, and reserves one output path per slug.

## Render the posts

`templates/post.md.php` turns each row into Markdown. The generated frontmatter
includes the publication date plus membership in the `archive` menu and
`notes` feed.

## Build the archive and feed

SMGen groups the selected pages by year, month, and day for the timeline. It
sorts the same pages by publication time for `feed.xml`. The page template adds
the feed's discovery link to the document head.

## Publish static files

The output contains HTML, CSS, XML, and images. The timeline uses native
`details` elements and loads no JavaScript.

[Back to the blog](index.html)
