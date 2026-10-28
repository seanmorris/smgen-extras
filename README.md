# SMGen Plugins

This workspace contains plugins that extend SMGen builds without adding code to SMGen core:

[Read the plugin guides](https://seanmorris.github.io/smgen-plugins/) or open a package reference below.

- [`smgen-data-pages`](packages/smgen-data-pages) validates CSV or TSV data and renders Markdown pages from PHP templates.
- [`smgen-store`](packages/smgen-store) validates public checkout metadata and renders hosted payment links or provider buttons.
- [`smgen-phpdoc`](packages/smgen-phpdoc) statically analyzes PHP and renders linked API reference pages without loading project code.
- [`smgen-categories`](packages/smgen-categories) groups pages by a frontmatter field and renders one rollup page per value, including dates by day, month, or year.
- [`smgen-timeline`](packages/smgen-timeline) renders a dated archive menu with a calendar and day rollup pages built on `smgen-categories`.

The generated site stays static. Product edits can start in a spreadsheet, pass through CI, and publish as ordinary HTML. Payment providers host checkout and remain responsible for the amount charged, payment state, inventory, and fulfillment.

## Check the Workspace

PHP 8.2, the PHP YAML extension, Node.js 20, and Pandoc 2.17 or newer are required.

```sh
npm ci
npm run check
```

The site launcher uses a local package or a sibling `static-gen` or `smgen`
checkout before it checks the system command. Set `SMGEN_BIN` when the current
checkout lives somewhere else:

```sh
SMGEN_BIN=/path/to/smgen/smgen.sh npm test
```

## Release a Package

Each package has its own version and tag. A tag such as `smgen-data-pages-v0.1.0` publishes only that workspace; `smgen-store-v*`, `smgen-phpdoc-v*`, `smgen-categories-v*`, and `smgen-timeline-v*` publish the others. Publish `smgen-categories` before `smgen-timeline`, which depends on it.

The publish workflow expects npm trusted publishing or an `NPM_TOKEN` repository secret.
