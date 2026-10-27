# SMGen Plugins

This workspace contains two plugins for stores built from static files:

- [`smgen-data-pages`](packages/smgen-data-pages) validates CSV or TSV data and renders Markdown pages from PHP templates.
- [`smgen-store`](packages/smgen-store) validates public checkout metadata and renders hosted payment links or provider buttons.

The generated site stays static. Product edits can start in a spreadsheet, pass through CI, and publish as ordinary HTML. Payment providers host checkout and remain responsible for the amount charged, payment state, inventory, and fulfillment.

## Check the Workspace

PHP 8.2, the PHP YAML extension, Node.js 20, and Pandoc 2.17 or newer are required.

```sh
npm ci
npm run check
```

Set `SMGEN_BIN` to an SMGen checkout when it is not available at `/app/smgen.sh`:

```sh
SMGEN_BIN=/path/to/smgen/smgen.sh npm test
```

## Release a Package

Each package has its own version and tag. A tag such as `smgen-data-pages-v0.1.0` publishes only that workspace. `smgen-store-v0.1.0` publishes the store package.

The publish workflow expects npm trusted publishing or an `NPM_TOKEN` repository secret.
