---
title: Category Pages
description: Generate one page for every value of a frontmatter field, including dates grouped by day, month, or year.
weight: 3
guide:
  package: smgen-categories
  example: examples/blog/
llms:
  section: plugins
  description: Configure smgen-categories to roll up pages by topic, author, product line, or date.
menus:
  documentation:
    title: Category Pages
    weight: 25
jsonld:
  "@context": https://schema.org
  "@type": TechArticle
  headline: Category Pages
  description: Generate one page for every value of a frontmatter field, including dates grouped by day, month, or year.
  url: https://seanmorris.github.io/smgen-plugins/categories.html
  isPartOf:
    "@id": https://seanmorris.github.io/smgen-plugins/#website
---

# Category Pages

`smgen-categories` reads every Markdown page under `PAGES_DIR`, groups them by a
frontmatter field, and writes one rollup page per group before SMGen scans the
site. Pages that earlier plugins generated in the same build count as members,
so a CSV-driven catalog from `smgen-data-pages` can have category pages too.

<p class="example-link"><a href="examples/blog/topics/walks.html" target="_blank" rel="noopener noreferrer">Open a generated topic page</a> <a href="examples/store/categories/notebooks.html" target="_blank" rel="noopener noreferrer">Open a store category</a></p>

## Install and Activate

```bash
npm install --save-dev smgen-categories
```

```yaml
PLUGINS:
  - smgen-data-pages
  - use: smgen-categories
    options:
      config: .smgen-categories.yaml
```

List it after any plugin whose generated pages should become members.

## Define a Category

```yaml
version: 1
categories:
  topic:
    field: topic
    pages: ['posts/**/*.md']
    output: topics/{slug}.md
    template: templates/topic.md.php
    sort: date-desc
  month:
    field: date
    group: month
    output: archive/{value}/index.md
    minimum: 2
```

- `field` names the frontmatter key. A list value files the page under each entry.
- `group` is `value` by default. `day`, `month`, and `year` parse the field as a date.
- `output` takes `{slug}` or `{value}`. A rollup may be a flat page or its directory's `index.md`; neither is required.
- `where` and `unless` compare frontmatter values exactly, and `minimum` drops small groups.
- `template` is a PHP file inside the site. Omit it for the built-in list.

## Write a Template

The template receives `$category`, `$members`, and `$context`, and calls
`$context->frontmatter()` like a data-page template. Link to members through
`$context->url($member['path'])`, which adds the site's `BASE_URL`, so the
links survive publishing below a path prefix such as GitHub Pages. The plugin adds a `title`
and a `category` mapping with the group's name, value, label, count, and member
paths, so other plugins and page templates can find the rollup.

```php
<?php
echo $context->frontmatter(['title' => $category['label'], 'layout' => 'topic']);
?>

<?php foreach($members as $member): ?>
- [<?=$context->escapeHtml($member['title'])?>](<?=$context->escapeHtml($context->url($member['path']))?>)
<?php endforeach; ?>
```

The blog example generates its topic pages this way and the store generates a
page per product category from the product pages' frontmatter.

## Product Categories From Generated Pages

The store example shows the second kind of category: one built from pages
another plugin generated in the same build. `smgen-data-pages` turns each CSV
row into a product page whose frontmatter carries a `product` mapping with the
category name, price, availability, and a ready-made tile. `smgen-categories`
runs next in `PLUGINS` and sees those pages like any other.

```yaml
PLUGINS:
  - use: smgen-data-pages
    options:
      config: .smgen-data-pages.yaml
  - use: smgen-categories
    options:
      config: .smgen-categories.yaml
  - smgen-store
```

The category reads a nested field with a dotted path and limits itself to the
product pages, leaving the catalog index out:

```yaml
version: 1
categories:
  category:
    field: product.category
    pages: ['products/**/*.md']
    exclude: ['products/index.md']
    sort: title
    output: categories/{slug}.md
    template: templates/category.md.php
```

The template never touches the CSV. Each member's `metadata` is the product
page's frontmatter, so the card reads the tile, price, and availability from
there and links to the member's public path:

```php
<?php foreach($members as $member): ?>
<?php $product = $member['metadata']['product'] ?? []; ?>
<li class="card"><a href="<?=$context->escapeHtml($context->url($member['path']))?>">
  <?=$product['cardTile'] ?? ''?>
  <span class="card-name"><?=$context->escapeHtml($member['title'])?></span>
  <span class="card-price"><?=$context->escapeHtml((string) ($product['price'] ?? ''))?></span>
</a></li>
<?php endforeach; ?>
```

The result is one page per product line at `/categories/notebooks.html` and so
on. The shop's navigation and catalog chips link to those pages, the product
page's breadcrumb leads back to its category, and adding a row with a new
category value to the spreadsheet creates the page on the next build. The
`smgen-store` validation still runs on every product page because it comes
last and only selects pages marked `storePage: true`.

## Validate Locally

```bash
npx smgen-categories validate --root . --pages pages
```

The command renders every rollup in memory and names the page and category
behind any bad date, unsafe value, collision, or template error.
