# create-smgen-site

Scaffold an [SMGen](https://seanmorris.github.io/smgen/) site from one of the
official base themes in [smgen-extras](https://seanmorris.github.io/smgen-extras/themes.html).

```bash
npm create smgen-site@latest my-site -- --theme journal --name "Harbour Notes"
cd my-site
smgen build
```

`npx create-smgen-site@latest my-site --theme journal` does the same on older
npm versions that do not forward arguments after `--`.

## Options

| Option | Meaning |
| --- | --- |
| `<directory>` | Where to write the site. Must not exist, or must be empty. |
| `--theme <name>` | Which theme to copy. Required outside a terminal; otherwise you are asked. |
| `--name <text>` | Product name written to `.smgen.yaml` (`PRODUCT_NAME` and `TITLE_PREFIX`). Defaults to the directory name in title case. |
| `--tagline <text>` | Tagline written to `.smgen.yaml`. Defaults to the theme's sample tagline. |
| `--list` | Print the available themes. |

## What it writes

The theme's `.smgen.yaml`, `pages/`, `templates/`, `static/` and, where a
theme has one, `helpers/`, copied as they are, plus a README with the next
steps. `BASE_URL` is set to empty and `OUTPUT_DIR` to `./docs`. Package
bookkeeping (`package.json`, `theme.json`, the theme's own README and
LICENSE) stays behind.

Only the top-level `.smgen.yaml` keys are rewritten. Nested values such as an
RSS feed title keep the sample text until you edit them.

## Exit codes

0 success · 1 usage · 2 the directory exists and is not empty · 3 a file
could not be written · 4 the theme's `.smgen.yaml` did not follow the key
convention (a theme bug, please report it).
