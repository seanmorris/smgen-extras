---
title: "Install"
description: "Packages, checksums and building from source."
menus:
  docs:
    weight: 10
---

# Install

List every supported way to install, most common first. Each gets a heading,
a command and one sentence on when to prefer it.

## Packages

```bash
brew install yourtool        # macOS
apt install yourtool         # Debian and Ubuntu
winget install yourtool      # Windows
```

## Verify a download

::: warning
Tell people how to check what they downloaded before they run it, and keep
the checksum file next to every release.
:::

```bash
sha256sum --check yourtool-1.0.0.sha256
```

## Build from source

```bash
git clone https://example.com/yourtool.git
cd yourtool
make install
```

Say which toolchain versions are required and where the build puts the
binary.
