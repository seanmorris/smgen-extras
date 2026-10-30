---
title: "kestrel restore"
description: "Copy files out of a snapshot."
menus:
  docs:
    weight: 20
---

# kestrel restore

Copy files from a snapshot back into place, or somewhere else.

```text
kestrel restore STORE [PATH...] [--at SNAPSHOT] [--into DIR] [--overwrite]
```

## Choosing a snapshot

`--at` takes a snapshot name as shown by `kestrel list`, or a prefix of one.
Without it, Kestrel uses the newest snapshot that contains every requested
path.

## Where files go

By default files return to their original absolute paths. `--into DIR` puts
them under `DIR` instead, preserving the relative structure, which is the safe
way to inspect a restore before trusting it.

::: warning
`--overwrite` replaces existing files without asking. Without it, Kestrel
stops at the first file that already exists and lists the conflicts.
:::

## Examples

```bash
kestrel restore ~/backups/laptop Documents/notes --into /tmp/check
kestrel restore ~/backups/laptop --at 2026-03-01 --into /mnt/rebuild
```
