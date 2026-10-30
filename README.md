# SMGen Extras

Themes to start an [SMGen](https://seanmorris.github.io/smgen/) site from, and plugins that extend SMGen builds without adding code to SMGen core. The [documentation site](https://seanmorris.github.io/smgen-extras/) has the guides and a live demo of every theme and example.

## Themes

A theme is a complete, plugin-free site with real sample pages. Copy one with:

```sh
npm create smgen-site@latest my-site -- --theme journal
```

- [`smgen-theme-docs`](themes/docs): a reference manual in two inks, numbered sections and margin notes; shown finished as [Kestrel](examples/kestrel).
- [`smgen-theme-journal`](themes/journal): dated entries listed from frontmatter, topic pages and an RSS feed.
- [`smgen-theme-portfolio`](themes/portfolio): a maker's catalogue set like a fine-press book, with numbered entries, photographs as plates and a specification block per project.
- [`smgen-theme-landing`](themes/landing): a one-page product site edited in YAML, with a screen drawn from data; shown finished as [Tidewatch](examples/tidewatch).
- [`smgen-theme-semantic`](themes/semantic): classless HTML in three columns and a stylesheet under twenty lines, for building a design from nothing.
- [`create-smgen-site`](themes/create-smgen-site): the scaffolder behind the command above.

## Plugins

- [`smgen-data-pages`](plugins/smgen-data-pages) validates CSV or TSV data and renders Markdown pages from PHP templates.
- [`smgen-store`](plugins/smgen-store) validates public checkout metadata and renders hosted payment links or provider buttons.
- [`smgen-phpdoc`](plugins/smgen-phpdoc) statically analyzes PHP and renders linked API reference pages without loading project code.
- [`smgen-jsdoc`](plugins/smgen-jsdoc) statically analyzes JavaScript and JSDoc and renders linked API reference pages without importing project modules.
- [`smgen-categories`](plugins/smgen-categories) groups pages by a frontmatter field and renders one rollup page per value, including dates by day, month, or year.
- [`smgen-timeline`](plugins/smgen-timeline) renders a dated archive menu with a calendar and day rollup pages built on `smgen-categories`.

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

## Layout

```text
plugins/<name>/            smgen-<name>
themes/<name>/             smgen-theme-<name>, plus theme.json
themes/create-smgen-site/  the scaffolder
examples/<name>/           plugin demos and theme demos, built into the site under examples/
pages/, templates/, static/   the documentation site
```

## Release a Package

Each package has its own version and tag, `<package>-v<version>`, such as `smgen-data-pages-v0.1.0` or `smgen-theme-journal-v0.1.0`. The workflow finds the workspace by package name and publishes only that one. Publish `smgen-categories` before `smgen-timeline`, which depends on it, and publish changed themes before `create-smgen-site`, which pins their exact versions.

The publish workflow expects npm trusted publishing or an `NPM_TOKEN` repository secret.
