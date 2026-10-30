# smgen-data-pages

`smgen-data-pages` turns rows from a CSV or TSV file into Markdown source pages before SMGen scans the site. PHP's CSV parser handles quoted delimiters and multiline cells. A local command checks the same inputs and templates that the build hook uses.

## Install the Plugin

```sh
npm install --save-dev smgen-data-pages
```

Activate it before plugins that consume generated frontmatter:

```yaml
PLUGINS:
  - use: smgen-data-pages
    options:
      config: .smgen-data-pages.yaml
    watch:
      - .smgen-data-pages.yaml
      - data/products.csv
      - templates/product.md.php
      - templates/catalog.md.php
```

Activation watch paths are project-relative. Restart `smgen watch` after creating a watched file or directory.

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
    collections:
      - template: templates/catalog.md.php
        output: products/index.md
```

`columns` maps names used by templates to exact spreadsheet headers. Quote values under `where`; matching uses the original cell strings. The parser also keeps prices, identifiers, and leading zeros as strings.

The output pattern can interpolate canonical columns. Each interpolated value must already be a portable path segment made from letters, numbers, periods, underscores, or hyphens. Every output must end in `.md` and stay below SMGen's generated-page directory.

Use `delimiter: "\t"` for TSV when the filename does not end in `.tsv`.

## Write a Row Template

The template receives `$row`, `$sourceRow`, `$dataset`, `$rowNumber`, and `$context`. Canonical row values are also available as local variables when their names do not collide with the documented scope.

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

`$context->frontmatter()` encodes a metadata mapping and adds delimiters. `$context->yaml()` encodes a YAML fragment, `$context->escapeHtml()` escapes raw HTML text, and `$context->pathSegment()` converts human text to a lowercase ASCII slug.

Markdown and raw HTML from the spreadsheet remain unchanged when a template prints them directly. Templates execute as trusted PHP with the build user's permissions. Review both the template and its data source.

Collection templates receive `$records`, `$dataset`, and `$context`. Each record contains `row`, `sourceRow`, and `rowNumber`.

## Validate Before Building

```sh
npx smgen-data-pages validate
```

The command parses every row, applies mappings and filters, renders each template in memory, and checks every output path. It does not write generated pages.

Add the command before the normal build in CI:

```json
{
  "scripts": {
    "validate:data": "smgen-data-pages validate",
    "check": "npm run validate:data && smgen build"
  }
}
```

The validator rejects invalid UTF-8, empty or duplicate headers, wrong row widths, missing required values, duplicate keys, duplicate outputs, unsafe paths, symlink escapes, and template errors. Diagnostics name the dataset and physical row where possible.

`smgen build` runs the validator again through the prepare hook. SMGen removes the managed Markdown sources after the atomic build, while their rendered HTML remains in the published output.

## Generate Outside SMGen

The package also exposes its writer for inspection and tooling:

```sh
npx smgen-data-pages generate --output pages/generated-preview
```

The output directory must stay inside the project and must be absent or empty. The command renders all files first, writes a temporary sibling directory, and then installs it.
