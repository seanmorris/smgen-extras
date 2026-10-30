---
title: Adding a stylesheet
description: How to grow the short stylesheet into a design of your own.
menus:
  site:
    weight: 20
---

# Adding a stylesheet

`static/base.css` is the whole stylesheet of the theme's own:

```css
:root { color-scheme: light dark; --scrollbar-thumb: color-mix(in srgb, currentColor 25%, transparent); }
body { min-height: 100vh; margin: 0 auto; max-width: 90rem; padding: 0 1rem; line-height: 1.5; font-family: system-ui, sans-serif; }
body > header { padding: 1rem 0; border-bottom: 1px solid; }
body > footer { position: sticky; top: 100vh; padding: 1rem 0; border-top: 1px solid; }
nav[aria-label="Breadcrumb"] ol { display: flex; flex-wrap: wrap; gap: 0.4em; margin: 1rem 0 0; padding: 0; list-style: none; font-size: 0.9em; }
nav[aria-label="Breadcrumb"] li + li::before { content: "/"; margin-right: 0.4em; opacity: 0.5; }
img, svg, video { max-width: 100%; height: auto; }
pre { overflow: auto; }
table { border-collapse: collapse; }
code span.kw, code span.cf, code span.bu, code span.im, code span.dt, code span.ot, code span.pp, code span.ex, code span.re { color: light-dark(#00703c, #6fd39a); }
code span.st, code span.ch, code span.sc, code span.vs, code span.ss, code span.bn, code span.dv, code span.fl, code span.at { color: light-dark(#1a56a8, #8ec2ff); }
code span.co, code span.do, code span.an, code span.cv, code span.in, code span.wa, code span.op { color: light-dark(#5f6b74, #a3adb5); }
code span.va, code span.cn, code span.fu { color: light-dark(#7a2e9e, #d9a8ff); }
code span.al, code span.er { color: light-dark(#b3261e, #ff8a80); }
td, th { padding: 0.25em 0.6em; border: 1px solid; }
@media (min-width: 64rem) {
  body { display: flex; flex-wrap: wrap; align-content: flex-start; align-items: flex-start; column-gap: 2rem; }
  body > header, body > footer { flex: 0 0 100%; }
  body > nav { flex: 0 1 14rem; }
  main { flex: 1 1 0; min-width: 0; }
  body > aside { flex: 0 1 14rem; }
  body > nav, body > aside { position: sticky; top: 0; max-height: 100vh; overflow: auto; }
}
```

The first rule is the one that matters. `color-scheme: light dark` tells the
browser to render its default colours in whichever scheme the visitor
prefers, so the site is already light and dark without a single colour
declared. The five `code span` rules recolour Pandoc's syntax highlighting by role,
keywords, strings and values, comments, names, and errors, and `light-dark()` picks
the light or dark value to match the browser's scheme, so every token stays
readable on either background. The two breadcrumb rules turn SMGen's breadcrumb list into one line with a
slash between the steps, keyed on its `aria-label` rather than a class. The body is at least as tall as the window and the footer is
sticky at `top: 100vh`, which pins it to the bottom of a short page without
a wrapper element. The media query at the end is the layout: above 64rem
the body becomes a flex row that wraps, the header and footer take the full
width, the navigation and the outline take a column each, and the article
takes whatever is left, so there is no dead space between them. The two
side columns stick while the article scrolls. Below that width the rule
does not apply and the landmarks stack in source order, which is also what
you get with no stylesheet at all.

The only other stylesheet is SMGen core's `scrollbar.css`, listed under
`CORE_ASSETS` and `STYLES` in `.smgen.yaml`: a thin, quiet scrollbar whose
gutter is reserved so the page does not shift between pages that scroll
and pages that do not. `--scrollbar-thumb` on `:root` sets its colour.

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
