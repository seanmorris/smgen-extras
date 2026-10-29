---
title: Signalbox
description: A small JavaScript library for signals, subscriptions, and deliberately timed work, documented from its JSDoc.
layout: library
guides:
  - href: guides/getting-started.html
    title: Getting started
    summary: Create a signal, subscribe, emit a value, and clean up.
  - href: guides/listeners.html
    title: Listeners
    summary: One-shot listeners, abort signals, and explicit subscriptions.
  - href: guides/scheduling.html
    title: Scheduling
    summary: Queue, cancel, and debounce work without hiding the timer.
TOC: false
menus:
  reference:
    parent: signalbox
    title: Overview
    weight: 0
jsonld:
  "@context": https://schema.org
  "@type": SoftwareSourceCode
  name: Signalbox
  description: A small JavaScript library for signals and scheduled work.
  programmingLanguage: JavaScript
---

## A signal in six lines

```js
import {createSignal} from '@signalbox/core';

const selected = createSignal('selected');
const subscription = selected.subscribe((id) => console.log(id));
selected.emit('row-7');
subscription.unsubscribe();
```

## Why Signalbox

- **Cleanup is a value.** Every listener returns a `Subscription` with one idempotent `unsubscribe()` method.
- **Cancellation composes.** Pass an `AbortSignal` when another operation already owns the lifetime.
- **Timing stays visible.** Scheduling helpers return cancellation functions and accept their delays directly.
