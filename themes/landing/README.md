# smgen-theme-landing

A one-page product site for [SMGen](https://seanmorris.github.io/smgen/).
The hero, feature list, plan table and FAQ are YAML in the front page's
frontmatter, so the page is edited as data rather than HTML. Pricing,
changelog and privacy pages ride along as plain Markdown.

```bash
npm create smgen-site@latest my-product -- --theme landing
```

## What you get

- `pages/index.md`: `layout: landing`. Its frontmatter holds `hero`,
  `features`, `plans` and `faq`; the template renders each section that is
  present and skips the ones that are not. The Markdown body becomes a
  closing section.
- `pages/pricing.md`, `pages/changelog.md`, `pages/privacy.md`: prose pages
  with the same header and footer.
- `static/hero.svg`: the hero illustration. Replace it with a screenshot.
- `templates/page.php`, `static/landing.css`, `static/theme.js`: the
  template, the stylesheet, and the light and dark switch.

## Editing the front page

```yaml
hero:
  title: "Know the tide before you need it."
  lede: "One or two sentences."
  primary: {label: "Get Tidewatch", href: "/pricing.html"}
  secondary: {label: "See what it does", href: "/#features"}
  image: "/hero.svg"
features:
  - title: "The next window, not a graph"
    body: "A short paragraph."
plans:
  - name: "Plus"
    price: "£12"
    period: "a year"
    includes: ["Four thousand harbours", "Unlimited marks"]
    href: "/pricing.html"
    featured: true
faq:
  - q: "Does it work offline?"
    a: "Yes."
```

Part of [smgen-extras](https://seanmorris.github.io/smgen-extras/themes.html#landing).
