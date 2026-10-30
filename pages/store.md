---
title: Static Store
description: Add validated hosted checkout controls to static SMGen pages.
weight: 3
guide:
  package: smgen-store
  example: examples/store/
llms:
  section: plugins
  description: Configure smgen-store product metadata, checkout providers, and JSON-LD.
menus:
  documentation:
    title: Static Store
    weight: 30
jsonld:
  "@context": https://schema.org
  "@type": TechArticle
  headline: Static Store
  description: Add validated hosted checkout controls to static SMGen pages.
  url: https://seanmorris.github.io/smgen-extras/store.html
  isPartOf:
    "@id": https://seanmorris.github.io/smgen-extras/#website
---

# Static Store

`smgen-store` adds hosted checkout controls to an SMGen page. Link-based modes
publish plain HTML. Stripe and PayPal embeds load provider code only on pages
that use those modes.

<p class="example-link"><a href="examples/store/" target="_blank" rel="noopener noreferrer">Open the store example site</a></p>

## Install and Activate

Place the store after any plugin that generates product pages:

```bash
npm install --save-dev smgen-data-pages smgen-store
```

```yaml
PLUGINS:
  - use: smgen-data-pages
    options:
      config: .smgen-data-pages.yaml
  - smgen-store
```

The store prepare hook scans authored and generated pages. Invalid checkout
metadata stops the build before SMGen replaces the published output.

## Describe a Product

Add a versioned store mapping to page frontmatter:

```yaml
---
title: SMGen Mug
storePage: true
plugins:
  smgen-store: true
store:
  schema: 1
  type: product
  id: MUG-001
  name: SMGen Mug
  description: A sturdy mug for long builds.
  price:
    amount: "24.00"
    currency: USD
  checkout:
    provider: hosted-link
    presentation: link
    url: https://example.com/checkout/smgen-mug
---
```

Keep `price.amount` quoted. The checkout provider remains responsible for the
amount charged, tax, shipping, inventory, payment state, refunds, and
fulfillment.

Place the control where it belongs in the page:

```markdown
::: {.smgen-store-checkout}
:::
```

## Choose a Checkout Mode

| Provider | Presentation | Browser code |
| --- | --- | --- |
| Hosted link | `link` | None |
| Stripe | `link` | None |
| Stripe | `embed` | Stripe buy button |
| PayPal | `link` | None |
| PayPal | `embed` | PayPal hosted button |
| PayPal | `cart` | PayPal cart |
| Square | `link` | None |

Every mode requires an HTTPS fallback URL. Embed modes accept only the public
identifiers copied from the provider's generated button code.

## Add Product JSON-LD

Store metadata and Schema.org data have different jobs. Add JSON-LD through
SMGen's normal `jsonld` frontmatter:

```yaml
jsonld:
  "@context": https://schema.org
  "@type": Product
  sku: MUG-001
  name: SMGen Mug
  offers:
    "@type": Offer
    price: "24.00"
    priceCurrency: USD
    url: https://example.com/checkout/smgen-mug
```

A data-page template can emit both mappings from the same CSV row.

## Validate Before Publishing

```bash
npx smgen-store validate pages
```

The validator rejects unknown fields, unsupported modes, malformed prices,
non-HTTPS links, missing public identifiers, and credential-shaped fields or
values. Never put provider secret keys or access tokens in Markdown, YAML, CSV,
or Git.

The [package reference on GitHub](https://github.com/seanmorris/smgen-extras/tree/main/plugins/smgen-store)
documents provider fields and content security policy requirements.
