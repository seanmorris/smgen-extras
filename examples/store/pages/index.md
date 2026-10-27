---
title: Store Example
description: A CSV product catalog with validated hosted checkout links.
TOC: false
---

# A storefront from CSV

The [product catalog](products/index.html) starts with 24 rows from Datablist's
Products sample. The data-page plugin filters six unavailable products and
renders the other 18 with one PHP template.

Each published row supplies its product details, JSON-LD, price, and checkout
provider. The example uses placeholder hosted links, so it loads no
payment-provider JavaScript.

The build fails before publication if a product has an invalid price, an
unsupported checkout mode, an unsafe URL, or a credential-shaped value.

The source rows come from Datablist's
<a href="https://www.datablist.com/learn/csv/download-sample-csv-files" target="_blank" rel="noopener noreferrer">synthetic sample CSV files</a>.

<a href="https://seanmorris.github.io/smgen-plugins/store.html" target="_blank" rel="noopener noreferrer">Read the smgen-store guide</a>
or <a href="https://github.com/seanmorris/smgen-plugins/tree/main/examples/store" target="_blank" rel="noopener noreferrer">inspect this example's source</a>.
