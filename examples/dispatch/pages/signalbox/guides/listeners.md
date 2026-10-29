---
title: Listeners
description: Remove Signalbox listeners explicitly, after one call, or with an AbortSignal.
TOC: true
menus:
  reference:
    parent: signalbox-guides
    weight: 20
---

# Listeners

Subscriptions make listener lifetime explicit. Keep the returned object near
the code that owns the listener and close it when that owner goes away.

## Listen once

Set `once` when only the next value matters:

```js
const ready = createSignal('ready');

ready.subscribe(initializeEditor, {once: true});
ready.emit(config);
```

Signalbox removes the wrapper before it invokes the listener, so an emission
inside the callback cannot call it twice.

## Follow an existing lifetime

Pass an `AbortSignal` when a request, component, or other operation already
controls cleanup:

```js
const controller = new AbortController();

selected.subscribe(updateInspector, {signal: controller.signal});
controller.abort();
```

The returned [`Subscription`](../api/classes/signal/subscription.html) still
works. Aborting and then unsubscribing is harmless.
