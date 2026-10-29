---
title: Getting started
description: Install Signalbox, create a first signal, and dispose its listener.
TOC: true
menus:
  reference:
    parent: signalbox-guides
    weight: 10
---

# Getting started

Signalbox gives a value a small broadcast channel. A signal can have any number
of listeners; emitting walks the current listeners in registration order.

## Install

```bash
npm install @signalbox/core
```

Signalbox ships as JavaScript modules and has no runtime dependencies.

## Create a signal

Give a signal a name when the name will help in logs or development tools.

```js
import {createSignal} from '@signalbox/core';

const saved = createSignal('saved');
```

[`createSignal()`](../api/functions/signal/create-signal.html) returns the
`subscribe`, `emit`, and `size` operations together.

## Subscribe and emit

```js
const subscription = saved.subscribe((documentId) => {
    console.log(`Saved ${documentId}`);
});

saved.emit('draft-17');
subscription.unsubscribe();
```

Unsubscribing twice is safe. A listener removed during an emission does not run
on the next one.

Next: [Listeners](listeners.html) covers one-shot work and cancellation.
