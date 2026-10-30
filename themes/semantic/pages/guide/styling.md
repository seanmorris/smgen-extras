---
title: Adding a stylesheet
description: How to grow the eight-line stylesheet into a design of your own.
menus:
  site:
    weight: 20
---

# Adding a stylesheet

`static/base.css` is the whole stylesheet:

```css
:root { color-scheme: light dark; }
body { max-width: 72ch; margin: 0 auto; padding: 1rem; line-height: 1.5; font-family: system-ui, sans-serif; }
img, svg, video { max-width: 100%; height: auto; }
pre { overflow: auto; }
td, th { padding: 0.25em 0.6em; border: 1px solid; }
table { border-collapse: collapse; }
```

The first line is the one that matters. `color-scheme: light dark` tells the
browser to render its default colours in whichever scheme the visitor
prefers, so the site is already light and dark without a single colour
declared.

## Start from the landmarks

The template emits these elements and nothing else of its own:

| Element | Purpose |
| --- | --- |
| `header` | Site name, tagline, and `nav[aria-label="Site"]` |
| `main > article` | Breadcrumbs, then the page body |
| `main > aside[aria-label="On this page"]` | The outline, when the page has one |
| `footer` | The colophon |

Style those by element. Class names appear only inside the menu
(`.menu`, `.menu--tree`, `details[data-id-path]`, `a[aria-current="page"]`)
and the breadcrumbs (`nav.breadcrumbs`), both rendered by SMGen core.

## Add a second file

Keep `base.css` and add your own file after it in `.smgen.yaml`:

```yaml
STYLES: [/base.css, /site.css]
```

SMGen copies everything in `static/` to the output, so the new file is served
beside the first.

## Or delete it

Remove the file and the `STYLES` entry and the site falls back to pure browser
defaults. The pages stay readable; they just run edge to edge.
