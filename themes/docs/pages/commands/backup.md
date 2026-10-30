---
title: "kestrel backup"
description: "Take a snapshot of one or more sources."
menus:
  docs:
    weight: 10
---

# kestrel backup

Take a snapshot of one or more sources into a store.

```text
kestrel backup [SOURCE...] --to STORE [--tag TEXT] [--dry-run]
```

## Behaviour

Kestrel walks each source, hashes every file, and copies the files whose
hashes are not already in the store. It then writes a snapshot directory of
hard links named by the UTC time the run started.

A snapshot is written atomically: it appears under `snapshots/` only after
every file in it is in the store.

## Options

| Option | Meaning |
| --- | --- |
| `--to STORE` | Store path. Defaults to `[store].path` in `kestrel.toml`. |
| `--tag TEXT` | A label recorded in the snapshot's manifest, for example `pre-upgrade`. |
| `--dry-run` | Print what would be copied and exit without writing. |
| `--one-file-system` | Do not cross mount points inside a source. |

## Exit status

`0` when the snapshot was written. `2` when some files could not be read; the
snapshot is still written and the unreadable paths are listed. `1` for any
other error, with nothing written.

::: note
Interrupting a backup leaves an incomplete store directory under `tmp/`. The
next run removes it.
:::
