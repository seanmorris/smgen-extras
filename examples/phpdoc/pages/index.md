---
title: PHPDoc Example
description: Browse API pages generated from a small PHP source tree.
TOC: false
---

# Documentation from source

This example builds the [Greeting API](api/index.html) from the PHP files in
its `src/` directory. The source is parsed as text and never loaded into the
build process.

The generated reference includes namespaces, signatures, parameter and return
documentation, internal type links, and source locations.

```bash
npx smgen build
```

[Read the smgen-phpdoc guide](https://seanmorris.github.io/smgen-plugins/phpdoc.html)
or [inspect this example's source](https://github.com/seanmorris/smgen-plugins/tree/main/examples/phpdoc).
