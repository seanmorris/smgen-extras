---
title: Store Example
description: Product pages with validated hosted checkout links.
TOC: false
---

# A static storefront

These product pages contain validated public checkout metadata. The example
uses placeholder hosted links, so it loads no payment-provider JavaScript.

- [Field Notes, printed](products/field-notes.html), USD 18.00
- [Pocket loupe](products/pocket-loupe.html), USD 12.00

The build fails before publication if a product has an invalid price, an
unsupported checkout mode, an unsafe URL, or a credential-shaped value.

[Read the smgen-store guide](https://seanmorris.github.io/smgen-plugins/store.html)
or [inspect this example's source](https://github.com/seanmorris/smgen-plugins/tree/main/examples/store).
