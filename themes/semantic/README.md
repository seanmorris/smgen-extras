# smgen-theme-semantic

An [SMGen](https://seanmorris.github.io/smgen/) theme with almost no CSS. The
template writes clean, classless HTML (`header`, `nav`, `main > article`,
`aside`, `footer`), the stylesheet is eight lines long, and the sample pages
show every Markdown construct so you can see the markup you will be styling.

```bash
npm create smgen-site@latest my-site -- --theme semantic
```

## What you get

- `templates/page.php`: one template for every page.
- `static/base.css`: colour scheme, a reading measure, and table borders.
  Delete it for pure browser defaults.
- `pages/`: a home page, an elements page, two short guides, and an about
  page. Replace them with your own.
- `.smgen.yaml`: the site menu is SMGen's tree renderer over `pages/`, so
  new pages and directories appear in the navigation on their own.

## Light and dark

`color-scheme: light dark` lets the browser pick its own palette for the
visitor's preference. There is no toggle and no script.

Part of [smgen-extras](https://seanmorris.github.io/smgen-extras/themes.html#semantic).
