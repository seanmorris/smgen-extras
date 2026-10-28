---
title: Our story
description: A paper goods shop run from a harbour print studio, built as plain files.
layout: page
TOC: false
jsonld:
  "@context": https://schema.org
  "@type": AboutPage
  name: About Fernway Goods
  description: A paper goods shop run from a harbour print studio, built as plain files.
---

# Our story

Fernway Goods started as the shelf by the door of a small print studio. People
kept asking to buy the notebooks we made for ourselves, so we made a few more,
and then a few more than that.

Everything in the shop is made in small batches, by hand where it matters and
by machine where it does not. When a batch is gone the page says so. There is
no warehouse and no algorithm; there is a bench, a kettle, and a list.

## Shipping and returns

Orders ship within two working days from the studio. Pre-orders and backorders
ship when the batch is finished, and the product page says how long that
usually takes. If something arrives damaged or simply is not what you hoped,
send it back within thirty days and we will refund it.

## How this shop is built

This storefront is a static site. It has no server, no database, and no
JavaScript on any product page.

1. The catalog lives in one spreadsheet, `data/products.csv`. Each row is a
   product with its price, stock state, and a hosted checkout link.
2. `smgen-data-pages` validates the rows and renders one page per published
   product, plus the front page and the catalog, from PHP templates.
3. `smgen-categories` reads the generated product pages and writes one page
   per product line, such as Notebooks or Desk, from a template that lays out
   the same cards as the catalog.
4. `smgen-store` checks every product's checkout metadata before the build
   finishes. A bad price, an unsupported checkout mode, an insecure URL, or a
   credential-shaped value stops the build.
5. SMGen publishes the result as ordinary HTML and CSS.

Payment itself happens on the provider's hosted page. The provider is
responsible for the amount charged, payment state, inventory, and fulfilment.
The static pages only link to it.

[Back to the shop](index.html) · [Browse the catalog](products/index.html)
