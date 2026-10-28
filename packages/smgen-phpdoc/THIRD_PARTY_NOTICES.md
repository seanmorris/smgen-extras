# Third-Party Notices

The npm archive bundles two Composer production dependencies so a consuming site does not need Composer, and one browser library for syntax highlighting.

## nikic/php-parser

- Version: 5.9.0
- Source: <https://github.com/nikic/PHP-Parser/tree/v5.9.0>
- License: BSD-3-Clause
- License file: `vendor/nikic/php-parser/LICENSE`

## phpstan/phpdoc-parser

- Version: 2.3.8
- Source: <https://github.com/phpstan/phpdoc-parser/tree/2.3.8>
- License: MIT
- License file: `vendor/phpstan/phpdoc-parser/LICENSE`

Composer records the exact distribution references and content hashes in `composer.lock`.

## highlight.js

- Version: 11.12.0
- Source: <https://github.com/highlightjs/highlight.js/tree/11.12.0>
- License: BSD-3-Clause
- License file: `static/vendor/highlight.js/LICENSE`

`static/vendor/highlight.js/highlight.min.js` is the common-languages browser build from the `@highlightjs/cdn-assets` npm package. Run `npm run vendor:highlight` after updating the pinned version.
