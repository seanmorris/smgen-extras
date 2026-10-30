---
title: "Install"
description: "Packages, checksums, and building Kestrel from source."
menus:
  docs:
    weight: 10
---

# Install

Kestrel is a single static binary. Pick the route that fits the machine.

## Script

```bash
curl -fsSL https://kestrel.example/install.sh | sh
```

The script downloads the binary for your platform, verifies its checksum
against the signed manifest, and places it in `~/.local/bin`.

## Packages

| Platform | Command |
| --- | --- |
| Debian, Ubuntu | `sudo apt install kestrel` |
| Fedora | `sudo dnf install kestrel` |
| macOS | `brew install kestrel` |
| Windows | `winget install Kestrel.Kestrel` |

## Checksums

Every release publishes `SHA256SUMS` and a detached signature. Verify before
you trust a download:

```bash
sha256sum --check SHA256SUMS --ignore-missing
```

::: warning
Do not skip verification on a machine that will hold other machines' backups.
A tampered binary there compromises everything it touches.
:::

## From source

Kestrel builds with a recent Go toolchain and no other dependencies:

```bash
git clone https://kestrel.example/kestrel.git
cd kestrel
go build ./cmd/kestrel
```

## Upgrading

Snapshots are forward compatible. A newer Kestrel reads any older store; an
older Kestrel refuses a store written by a newer one and says why.
