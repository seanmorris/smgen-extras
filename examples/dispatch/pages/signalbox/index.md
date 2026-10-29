---
title: Signalbox
description: A small JavaScript library for signals, subscriptions, and deliberately timed work, documented from its JSDoc.
layout: library
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

<div class="landing-grid">
<a class="landing-card" href="guides/getting-started.html"><strong>Getting started</strong><span>Create a signal, subscribe, emit a value, and clean up.</span></a>
<a class="landing-card" href="guides/listeners.html"><strong>Listeners</strong><span>One-shot listeners, abort signals, and explicit subscriptions.</span></a>
<a class="landing-card" href="guides/scheduling.html"><strong>Scheduling</strong><span>Queue, cancel, and debounce work without hiding the timer.</span></a>
<a class="landing-card landing-card--api" href="api/index.html"><strong>API reference</strong><span>Every public export and member, regenerated from JSDoc on each build.</span></a>
</div>

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
