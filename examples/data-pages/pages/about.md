---
title: About this guide
description: How two spreadsheets become a trail guide with twenty-eight pages and a nested menu.
layout: page
TOC: false
---

# About this guide

The Greywater Range does not exist. Its trails, campgrounds, and place names
were invented to show what `smgen-data-pages` can do with two ordinary
spreadsheets.

## Two files, twenty-eight pages

`data/trails.csv` has one row per trail with its region, distance, elevation
gain, route type, difficulty, dog policy, season, highlights, and a few
paragraphs of description. `data/campgrounds.csv` has one row per campground
with its facilities and a list of the trails that start from camp.

At build time the plugin:

1. Validates both files. A missing column, a duplicate slug, or an output path
   outside the project stops the build before anything is written.
2. Renders one page per row through a PHP template. The trail template turns
   a row into a stats panel, a difficulty meter, an elevation sketch, and prose.
3. Renders the front page, the trail index, and the campground index from the
   same rows, grouped by region.

Every generated page joins the nested menu on the left through its frontmatter.
Trails sort under their region by distance; campgrounds sort by elevation.

## Change a row, rebuild

Edit a distance in the spreadsheet and the trail page, its card on the index,
and the region totals on the front page all change together. There is no
database and nothing to keep in sync by hand.

[Back to the guide](index.html) · [See all trails](trails/index.html)
