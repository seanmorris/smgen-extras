---
title: Adding a stylesheet
description: How to grow the twenty-line stylesheet into a design of your own.
menus:
  site:
    weight: 20
---

# Adding a stylesheet

`static/base.css` is the whole stylesheet:

```css
:root { color-scheme: light dark; }
body { margin: 0 auto; max-width: 90rem; padding: 0 1rem; line-height: 1.5; font-family: system-ui, sans-serif; }
body > header { padding: 1rem 0; border-bottom: 1px solid; }
body > footer { padding: 1rem 0; border-top: 1px solid; }
main { max-width: 72ch; }
img, svg, video { max-width: 100%; height: auto; }
pre { overflow: auto; }
table { border-collapse: collapse; }
td, th { padding: 0.25em 0.6em; border: 1px solid; }
@media (min-width: 64rem) {
  body { display: grid; grid-template-columns: 14rem minmax(0, 1fr) 14rem; grid-template-areas: "header header header" "nav main aside" "footer footer footer"; column-gap: 2rem; }
  body > header { grid-area: header; }
  body > nav { grid-area: nav; }
  main { grid-area: main; justify-self: center; width: 100%; }
  body > aside { grid-area: aside; }
  body > footer { grid-area: footer; }
  body > nav, body > aside { position: sticky; top: 0; align-self: start; max-height: 100vh; overflow: auto; }
}
```

The first rule is the one that matters. `color-scheme: light dark` tells the
browser to render its default colours in whichever scheme the visitor
prefers, so the site is already light and dark without a single colour
declared. The media query at the end is the layout: above 64rem the body
becomes a grid with the navigation on the left, the article in the middle and
the outline on the right, and the two side columns stick while the article
scrolls. Below that width the rule does not apply and the landmarks stack in
source order, which is also what you get with no stylesheet at all.

## Start from the landmarks

The template emits these elements and nothing else of its own:

| Element | Purpose |
| --- | --- |
| `body > header` | Site name and tagline |
| `body > nav[aria-label="Site"]` | The site menu |
| `main > article` | Breadcrumbs, then the page body |
| `body > aside[aria-label="On this page"]` | The outline, when the page has one |
| `body > footer` | The colophon |

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
defaults. The pages stay readable in one column; they just run edge to edge.
