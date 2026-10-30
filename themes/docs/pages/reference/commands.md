---
title: "Commands"
description: "What each command does, its flags, and what it returns."
menus:
  docs:
    weight: 10
---

# Commands

Use the same headings for every command so a reader can skim: synopsis,
what it does, flags, exit codes.

## init

```bash
yourtool init <directory>
```

Creates the configuration file and the output directory. Safe to run twice;
it never overwrites a file that exists.

| Flag | Meaning |
| --- | --- |
| `--force` | Overwrite an existing configuration |
| `--quiet` | Print nothing unless something fails |

## run

```bash
yourtool run [--dry-run]
```

Reads every source, writes results to the output directory, and prints one
line per source.

::: note
`--dry-run` prints what would happen and writes nothing. Mention it on
every command that changes files.
:::

## Exit codes

| Code | Meaning |
| --- | --- |
| `0` | Success |
| `1` | Usage error; the message says which argument |
| `2` | A source could not be read |
| `3` | The output directory could not be written |
