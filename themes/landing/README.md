# smgen-theme-landing

A one-page product site built with [SMGen](https://seanmorris.github.io/smgen/).
The hero, a device screen drawn from data, a band of figures, feature rows
each paired with a readout, a plan comparison table, questions and a closing
call to action are all YAML in the front page's frontmatter, so the page is
edited as data, not HTML. Pricing, changelog and privacy pages ride along.
No plugins.

```bash
npm create smgen-site@latest my-product -- --theme landing
```

The sample content is a neutral starter that explains itself. For a finished
site built on this template, see the Tidewatch example in
[smgen-extras](https://seanmorris.github.io/smgen-extras/examples/tidewatch/)
and its source under `examples/tidewatch`.

## What you get

- `pages/index.md`: the front page; its frontmatter holds every section.
- `pages/pricing.md`, `pages/changelog.md`, `pages/privacy.md`: prose pages.
- `templates/page.php`, `static/landing.css`, `static/theme.js`.
- `static/fonts/`: Barlow and Barlow Condensed (OFL), preloaded.

## The front page's frontmatter

| Key | What it draws |
| --- | --- |
| `hero` | `kicker`, `title`, `lede`, `primary` and `secondary` buttons (`label`, `href`), `note`, and either `screen` or `image` |
| `hero.screen` | A phone screen: `title`, `subtitle`, `value`, `label`, `status`, `trend` (`up`, `down`, `flat`), `series` (numbers for the sparkline), `marks` (`at` 0 to 1, `label`), `rows` (`label`, `value`), `alt` |
| `stats` | A dark band of figures: `value`, `label` |
| `features` | Rows of `title`, `body` and an optional `readout` |
| `features[].readout` | `kind: value` (`value`, `label`, `note`), `kind: list` (`items` of `label`, `value`), `kind: text` (`lines`), `kind: dial` (`value`, `unit`, `label`, `trend`) |
| `plans` | Columns of the comparison table: `name`, `price`, `period`, `summary`, `href`, `action`, `featured` |
| `planRows` | Rows of the table: `label`, `values` (one per plan; `true`, `false` or text) |
| `faq` | `q`, `a`, rendered as native `details` |
| `closing` | `title`, `lede`, `primary`, `note`; falls back to the hero |
| `nav`, `footerLinks` | `label`, `href`; the nav defaults to the page's own sections |

The Markdown body, if any, appears as a short closing section below the
call to action. Prose pages read `pages/index.md` for the nav and the button.

Part of [smgen-extras](https://seanmorris.github.io/smgen-extras/themes.html#theme-landing).
