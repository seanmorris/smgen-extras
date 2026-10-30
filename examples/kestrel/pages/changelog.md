---
title: "Changelog"
description: "What changed in each Kestrel release."
menus:
  docs:
    weight: 60
---

# Changelog

## v2.4.0

- `backup --one-file-system` stops at mount points.
- Snapshot names are now UTC; existing names are read either way.
- `prune` reports reclaimed space.

## v2.3.1

- Fixed a crash when a source path contained a trailing slash on Windows.

## v2.3.0

- Tags: `backup --tag` labels a snapshot and `prune` always keeps tagged snapshots.
- `restore --into` preserves file modes and times.

## v2.2.0

- Retention windows in `kestrel.toml`.
- `KESTREL_LOG=debug` prints every file decision.

## v2.1.0

- Excludes use gitignore syntax.
- The install script verifies checksums.
