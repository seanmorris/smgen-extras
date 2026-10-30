---
title: "FAQ"
description: "The questions people ask after reading Getting started."
menus:
  docs:
    weight: 50
---

# FAQ

Keep each answer to a paragraph and link to the page that goes deeper.

## Can I run it on a schedule?

Yes. Any scheduler that can run a command will do; the command prints
nothing on success unless `--verbose` is set, so logs stay quiet.

## Where does the output go?

To `project.output` in the configuration file, `./build` by default. See
[Configuration](configuration.html).

## Something failed. Where do I look?

The exit code says which stage failed; see
[Commands](reference/commands.html). The message names the file.
