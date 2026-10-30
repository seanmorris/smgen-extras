---
title: "Configuration"
description: "The configuration file, where it is read from, and what each key does."
menus:
  docs:
    weight: 20
---

# Configuration

Say where the file lives, in what order the locations are read, and what
wins when a flag and a file disagree.

## A complete example

```toml
[project]
name = "example"
output = "./build"

[[source]]
path = "./src"
exclude = ["**/node_modules", "*.tmp"]

[retention]
keep = 14
```

## Keys

| Key | Type | Default | Meaning |
| --- | --- | --- | --- |
| `project.name` | string | directory name | Shown in reports |
| `project.output` | path | `./build` | Where results are written |
| `source.path` | path | required | A directory to read |
| `source.exclude` | list | `[]` | Glob patterns to skip |
| `retention.keep` | integer | `14` | How many results to keep |

::: tip
Put the table of keys on this page and nowhere else, then link to it from
the command pages. One table is easier to keep true than five.
:::

## Flags

Every key can be set on the command line as `--key value`, with dots in the
key name: `--retention.keep 30`. Flags override the file.
