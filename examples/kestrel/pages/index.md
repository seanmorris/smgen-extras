---
title: "Getting started"
description: "Install Kestrel, take a first backup, and restore a file, in about five minutes."
menus:
  docs:
    weight: 0
---

# Getting started

Kestrel is a command-line backup tool for people who want to read their
backups. It stores snapshots as plain directories of content-addressed files,
so a snapshot can be browsed with `ls`, checked with `sha256sum`, and restored
with nothing but `cp` if Kestrel itself is gone.

::: note
Kestrel is a fictional tool. This manual exists to demonstrate the SMGen
docs theme; the commands work on the page and nowhere else.
:::

## Install

```bash
curl -fsSL https://kestrel.example/install.sh | sh
kestrel --version
```

See [Install](install.html) for packages, checksums and building from source.

## Take a first backup

Point Kestrel at a directory to protect and a place to keep snapshots:

```bash
kestrel init ~/backups/laptop
kestrel backup ~/Documents --to ~/backups/laptop
```

The first run copies everything. Later runs copy only what changed, because
every file is stored once under the hash of its contents.

## Look inside

```bash
kestrel list --to ~/backups/laptop
ls ~/backups/laptop/snapshots/2026-03-28T07-10-11/Documents
```

Each snapshot is a directory of hard links into the store. There is no
database to corrupt and no format to decode.

## Restore one file

```bash
kestrel restore ~/backups/laptop Documents/notes/march.md --at 2026-03-28T07-10-11
```

::: tip
Leave off `--at` to restore from the newest snapshot that has the file.
:::

## Next

- [Configuration](configuration.html) covers the `kestrel.toml` file, excludes and retention.
- The [commands](commands/) section documents every subcommand.
- [Recipes](recipes.html) has worked examples for laptops, servers and shared drives.
