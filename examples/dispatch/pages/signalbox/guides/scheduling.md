---
title: Scheduling
description: Schedule, cancel, and debounce Signalbox work.
TOC: true
menus:
  reference:
    parent: signalbox-guides
    weight: 30
---

# Scheduling

The scheduling helpers are thin wrappers around timers. They return cleanup
instead of storing a hidden global queue.

## Schedule work

[`schedule()`](../api/functions/scheduler/schedule.html) returns a function that
cancels the pending task:

```js
const cancel = schedule(refreshPreview, 120);

if (editor.closed) {
    cancel();
}
```

## Keep the latest call

[`debounce()`](../api/functions/scheduler/debounce.html) resets its timer on each
call and forwards the latest arguments:

```js
const updateQuery = debounce((query) => {
    results.emit(search(query));
}, 180);

input.addEventListener('input', () => updateQuery(input.value));
```

Use the browser's own timing primitives when you need leading-edge execution,
maximum waits, or animation-frame scheduling.
