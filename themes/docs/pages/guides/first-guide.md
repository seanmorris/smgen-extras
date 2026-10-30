---
title: "Your first guide"
description: "The shape of a guide: the goal, what you need, the steps, and how to check it worked."
menus:
  docs:
    weight: 10
---

# Your first guide

State the goal in one sentence. Then what the reader needs before starting.
Then the steps, numbered, each ending in something they can see.

## Before you start

- A working install; see [Install](../install.html).
- A directory with something in it.

## Steps

1. Initialise the project:

   ```bash
   yourtool init ~/project
   ```

2. Add a source to the configuration file and run once with `--dry-run`.

3. Run for real and read the output.

## Check it worked

Say exactly what the reader should see, so they know whether to continue or
to go to the [FAQ](../faq.html).

::: tip
End every guide with the next guide, or with the reference page for the
command they just used.
:::
