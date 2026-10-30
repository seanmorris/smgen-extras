---
title: "kestrel prune"
description: "Remove snapshots outside the retention windows."
menus:
  docs:
    weight: 30
---

# kestrel prune

Remove snapshots that fall outside the retention windows, then drop store
files no snapshot references.

```text
kestrel prune STORE [--keep-daily N] [--keep-weekly N] [--keep-monthly N] [--dry-run]
```

## What is kept

For each window Kestrel keeps the newest snapshot per day, week or month,
counting back from now. A snapshot kept by any window is kept. Tagged
snapshots are always kept.

## Two passes

1. Snapshot directories outside every window are removed.
2. Store files with no remaining hard link are removed.

Both passes print what they remove. Run with `--dry-run` first on a store you
care about.

::: tip
Prune on a schedule, not after a scare. A weekly cron entry keeps the store
small enough that you will actually look at it.
:::
