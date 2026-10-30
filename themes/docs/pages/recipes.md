---
title: "Recipes"
description: "Worked setups for a laptop, a small server and a shared drive."
menus:
  docs:
    weight: 40
---

# Recipes

Three setups that cover most machines. Each one is a `kestrel.toml` and a
schedule.

## A laptop to an external disk

```toml
[store]
path = "/Volumes/Backup/laptop"

[[source]]
path = "~"
exclude = ["Library", ".cache", "**/node_modules", "Downloads"]
```

Run `kestrel backup` whenever the disk is plugged in. Kestrel notices when the
store is missing and exits quietly, so a login hook is safe.

## A server to another server

Keep the store on a second machine mounted over SSHFS or NFS, and run from
cron:

```text
15 3 * * *  kestrel backup --to /mnt/offsite/web01
30 3 * * 0  kestrel prune /mnt/offsite/web01
```

::: note
Back up databases from a dump, not from their live files. Add the dump
directory as a source and exclude the data directory.
:::

## A shared drive, several machines

Give each machine its own store directory under a shared root. Stores do not
share files between them, which costs disk and buys independence: one
machine's mistake cannot prune another's history.

## Checking a backup

Once a month, restore something small into `/tmp` and open it. A backup you
have never restored is a hope, not a backup.
