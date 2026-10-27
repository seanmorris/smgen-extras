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
jsonld:
  "@context": https://schema.org
  "@type": Product
  sku: MUG-001
  name: SMGen Mug
  description: A sturdy mug for long builds.
  offers:
    "@type": Offer
    price: "24.00"
    priceCurrency: USD
    url: https://example.com/checkout/smgen-mug
    availability: https://schema.org/InStock
---

# SMGen Mug

A sturdy mug for long builds.

**USD 24.00**

::: {.smgen-store-checkout}
:::
