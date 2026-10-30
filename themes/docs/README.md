# smgen-theme-docs

A reference manual built with [SMGen](https://seanmorris.github.io/smgen/).
Serif text with numbered sections, notes and warnings in the margin beside
the paragraph they belong to, code with a rule and a language label, the
contents and the page's outline together on the left, search in the
masthead. No plugins.

```bash
npm create smgen-site@latest my-manual -- --theme docs
```

The sample pages are a neutral starter that describes the shape of a manual.
For a finished one built on this template, see the Kestrel example in
[smgen-extras](https://seanmorris.github.io/smgen-extras/examples/kestrel/)
and its source under `examples/kestrel`.

## What you get

- `pages/`: Getting started, Install, Configuration, a Reference group, a
  Guides group, FAQ, Changelog and About. Directories carry a `.fm.yaml`
  with their title and order in the contents.
- `templates/page.php`, `static/docs.css`, `static/theme.js`.
- `static/fonts/`: Source Serif 4 and IBM Plex Mono (OFL), preloaded.

## Writing

- Callouts are fenced divs: `::: note`, `::: tip`, `::: warning`. On a wide
  screen they sit in the margin beside the paragraph that follows them; on a
  narrow one they stay in the flow.
- Sections are numbered from the headings: `##` gets a section number, `###`
  a subsection number, and the outline under the contents matches.
- Fenced code with a language (`bash`, `toml`, `yaml`, `json`, `ini`,
  `php`, `javascript`, `python`, `text`) gets a label in its corner.

## Configuration

| Key | Purpose |
| --- | --- |
| `TAGLINE` | The edition shown after the product name, for example "Reference manual" |
| `DOCS_VERSION` | Appended to the edition, for example `v2.4.0` |
| `DOCS_REPOSITORY` | A URL to the pages' source; an "Edit this page" link appears when set |
| `SEARCH_ENABLED: auto` | Search when `smgen-search` is installed on the build machine |

Part of [smgen-extras](https://seanmorris.github.io/smgen-extras/themes.html#theme-docs).
