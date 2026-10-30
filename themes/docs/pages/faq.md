---
title: "FAQ"
description: "Short answers to the questions that come up most."
menus:
  docs:
    weight: 50
---

# FAQ

## Is the store encrypted?

No. Kestrel stores plain files so they stay readable without Kestrel. Put the
store on an encrypted volume if the disk might leave your control.

## How much space does a snapshot take?

Only the changed files. Unchanged files are hard links to the store, so a
daily snapshot of a mostly static home directory costs kilobytes.

## Can I back up to cloud storage?

Yes, to anything that presents as a filesystem with hard links: a mounted
block volume, NFS, or SSHFS. Object stores do not support hard links and are
not supported.

## What happens if a file changes during a backup?

Kestrel hashes the file as it reads it and stores what it read. The snapshot
records the hash it stored, so a later check will tell you the file was
captured mid-write.

## Why TOML?

Because the file is read by people far more often than it is written by
programs, and TOML stays readable with comments and no indentation rules.
