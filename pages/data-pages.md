---
title: Data Pages
description: Validate CSV or TSV data and turn each row into an SMGen page.
weight: 2
guide:
  package: smgen-data-pages
  example: examples/data-pages/
llms:
  section: plugins
  description: Configure smgen-data-pages, templates, validation, and generated outputs.
menus:
  documentation:
    title: Data Pages
    weight: 20
jsonld:
  "@context": https://schema.org
  "@type": TechArticle
  headline: Data Pages
  description: Validate CSV or TSV data and turn each row into an SMGen page.
  url: https://seanmorris.github.io/smgen-plugins/data-pages.html
  isPartOf:
    "@id": https://seanmorris.github.io/smgen-plugins/#website
---

# Data Pages

`smgen-data-pages` turns rows from a CSV or TSV file into Markdown before SMGen
scans the site. PHP's CSV parser handles quoted delimiters and multiline cells.

<p class="example-link"><a href="examples/data-pages/" target="_blank" rel="noopener noreferrer">Open the trail guide example</a>, built from two spreadsheets, or <a href="examples/store/" target="_blank" rel="noopener noreferrer">open the store example</a>, which pairs this plugin with <code>smgen-store</code>.</p>

## Install and Activate

```bash
npm install --save-dev smgen-data-pages
```

```yaml
PLUGINS:
  - use: smgen-data-pages
    options:
      config: .smgen-data-pages.yaml
    watch:
      - .smgen-data-pages.yaml
      - data/products.csv
      - templates/product.md.php
```

Restart `smgen watch` after adding a new watched file or directory.

## Define a Dataset

Create `.smgen-data-pages.yaml`:

```yaml
version: 1
datasets:
  products:
    source: data/products.csv
    template: templates/product.md.php
    output: products/{slug}.md
    key: sku
    columns:
      sku: SKU
      slug: Slug
      title: Name
      description: Description
      price: Price
      currency: Currency
      checkout_url: Checkout URL
      published: Published
    required: [sku, slug, title, price, currency, checkout_url]
    where:
      published: "yes"
```

Column mappings keep template names stable when spreadsheet headers use spaces
or title case. The parser preserves identifiers, prices, and leading zeros as
strings.

## Render a Row

The PHP template receives `$row`, `$sourceRow`, `$dataset`, `$rowNumber`, and
`$context`:

```php
<?php

$metadata = [
	'title' => $row['title'],
	'sku' => $row['sku'],
];

echo $context->frontmatter($metadata);
?>
# <?=$context->escapeHtml($row['title'])?>

<?=$row['description']?>
```

Templates are trusted PHP. Review the template and its data source before a
build. Markdown or raw HTML printed from a cell remains available to Pandoc.

## Validate Locally

```bash
npx smgen-data-pages validate
```

The command parses every row, applies mappings and filters, renders templates
in memory, and checks output paths. It does not write generated pages.

The validator rejects invalid UTF-8, duplicate headers, wrong row widths,
missing required values, duplicate keys, unsafe paths, symlink escapes, and
template errors. Diagnostics include the dataset and physical row when the
source provides them.

The [package reference on GitHub](https://github.com/seanmorris/smgen-plugins/tree/main/packages/smgen-data-pages)
covers collection pages, TSV delimiters, template helpers, and standalone
generation.
