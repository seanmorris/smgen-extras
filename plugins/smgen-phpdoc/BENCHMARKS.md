# Build Cost

The release candidate was tested against `php-wasm-engine` commit
`1b2f7a11acef7a49ad58fcaedee10a6216f6b889`. The configured `src` tree contains
140 PHP files and 140 public symbols. PHPDoc produced 152 Markdown pages, or
about 1.1 MiB including its ownership metadata.

The measurements below came from one x86-64 container with Node.js 22.23.2 and
PHP 8.2.33. They are a scale check, not a cross-machine benchmark.

| Run | Analysis and rendering | Command wall time | Filesystem result |
| --- | ---: | ---: | --- |
| First generation | 1,138.0 ms | 1,323 ms | Installed 152 pages |
| Unchanged generation | 1,094.6 ms | 1,243 ms | No page or mtime changes |

The unchanged run deliberately repeats static analysis so it can detect source
or policy changes without trusting stale parser output. Its savings come from
skipping the generated-tree replacement and the SMGen rebuild that a changed
tree would trigger.

The same run reported 386 documentation warnings and completed successfully.
Warnings were left enabled to exercise policy diagnostics across a real code
base; projects may promote individual rules to errors in CI.
