---
title: "Settings"
description: "Environment variables and the order in which settings are resolved."
menus:
  docs:
    weight: 20
---

# Settings

Settings are resolved in this order, later entries winning: built-in
defaults, the configuration file, environment variables, flags.

## Environment variables

| Variable | Overrides |
| --- | --- |
| `YOURTOOL_CONFIG` | The path of the configuration file |
| `YOURTOOL_OUTPUT` | `project.output` |
| `NO_COLOR` | Disables colour in output when set to anything |

::: warning
Never read secrets from flags; they end up in shell history. Read them from
the environment or a file with restricted permissions, and say so here.
:::
