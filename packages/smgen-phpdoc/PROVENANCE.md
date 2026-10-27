# Provenance

The first `smgen-phpdoc` release uses behavior studied in `php-wasm-engine` commit [`1b2f7a11acef7a49ad58fcaedee10a6216f6b889`](https://github.com/seanmorris/php-wasm-engine/tree/1b2f7a11acef7a49ad58fcaedee10a6216f6b889). Both projects use Apache-2.0.

The reference files are:

- [`site/tools/php-api.php`](https://github.com/seanmorris/php-wasm-engine/blob/1b2f7a11acef7a49ad58fcaedee10a6216f6b889/site/tools/php-api.php), which established the public symbol, signature, source span, enum, promoted property, and PHPDoc fields.
- [`site/tools/api-reference.mjs`](https://github.com/seanmorris/php-wasm-engine/blob/1b2f7a11acef7a49ad58fcaedee10a6216f6b889/site/tools/api-reference.mjs), which established linked types, stable anchors, source links, parameter tables, namespace indexes, and SMGen frontmatter.
- [`site/tools/prepare.mjs`](https://github.com/seanmorris/php-wasm-engine/blob/1b2f7a11acef7a49ad58fcaedee10a6216f6b889/site/tools/prepare.mjs), which established isolated generated-page staging.
- [`site/build.sh`](https://github.com/seanmorris/php-wasm-engine/blob/1b2f7a11acef7a49ad58fcaedee10a6216f6b889/site/build.sh) and [`site/.smgen.yaml`](https://github.com/seanmorris/php-wasm-engine/blob/1b2f7a11acef7a49ad58fcaedee10a6216f6b889/site/.smgen.yaml), which supplied build and navigation integration examples.

This package replaces the prototype's Reflection and bootstrap execution with static AST analysis. It removes Phonics manifests, namespaces, package lists, browser JavaScript parsing, drafting outlines, prose, and hand-maintained menu IDs. Routes and configuration are project-neutral. The managed output installer, versioned graph, PHPDoc parser, diagnostics, CLI, watch mode, and template interface are new implementation work.
