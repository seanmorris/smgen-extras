---
title: "Configuration"
description: "The kestrel.toml file: sources, stores, excludes and retention."
menus:
  docs:
    weight: 20
---

# Configuration

Kestrel reads `kestrel.toml` from the current directory, then from
`~/.config/kestrel/`. Command-line flags override the file.

## A complete example

```toml
[store]
path = "/mnt/backups/laptop"

[[source]]
path = "~/Documents"
exclude = ["**/node_modules", "**/.cache", "*.tmp"]

[[source]]
path = "~/Photos"

[retention]
daily = 14
weekly = 8
monthly = 24
```

## Sources

Each `[[source]]` is a directory to back up. Paths expand `~`. Excludes are
gitignore-style globs matched against paths relative to the source.

::: tip
Keep excludes close to the source they apply to. A global exclude that
quietly skips `*.db` will one day skip the one database you needed.
:::

## Retention

Retention keeps the newest snapshot in each bucket and prunes the rest when
you run [`kestrel prune`](commands/prune.html). Nothing is deleted
automatically.

| Key | Meaning | Default |
| --- | --- | --- |
| `daily` | Snapshots to keep, one per day | `7` |
| `weekly` | One per week, after the daily window | `4` |
| `monthly` | One per month, after the weekly window | `12` |

## Environment

`KESTREL_STORE` sets the store path when no file or flag does.
`KESTREL_LOG=debug` prints every file decision.
