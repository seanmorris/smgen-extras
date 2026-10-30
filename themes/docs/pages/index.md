---
title: "Getting started"
description: "What the first page of a manual should do, with the sections it usually needs."
menus:
  docs:
    weight: 0
---

# Getting started

The first page answers three questions in order: what the product is, how to
get it, and what to do in the first five minutes. Keep it short enough to
read in one sitting and link out for everything else.

::: note
This is the starter manual for the SMGen docs theme. Replace the pages under
`pages/` with your own; the contents column, the section numbers and these
margin notes come from the theme.
:::

## Install

Give the one command that works for most people, then link to the
[Install](install.html) page for packages, checksums and building from
source.

```bash
curl -fsSL https://example.com/install.sh | sh
yourtool --version
```

## First run

Show the shortest sequence that produces a real result. Say what each line
does in the sentence before it, not in a comment inside it.

```bash
yourtool init ~/project
yourtool run
```

::: tip
A reader who copies these two lines should see something happen. If the
first run needs configuration, put the smallest working file in
[Configuration](configuration.html) and link to it here.
:::

## Where to go next

- [Configuration](configuration.html) for the file and the flags.
- [Reference](reference/) for every command and setting.
- [Guides](guides/) for longer tasks, one page each.
- [FAQ](faq.html) for the questions people ask after reading this page.
