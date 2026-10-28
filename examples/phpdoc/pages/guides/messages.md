---
title: Messages
description: Recipients, bodies, custom headers, and priority on a Postbox message.
TOC: true
menus:
  reference:
    parent: guides
    weight: 20
---

# Messages

A [`Message`](../api/classes/postbox/message.html) is built up with chained
calls and checked once, when it is sent.

## Recipients

`to()` and `cc()` each take one
[`Address`](../api/classes/postbox/address.html) and may be called as often as
needed. `recipients()` returns every address once, in the order they were
added, with duplicates removed.

```php
$message
    ->to(address('Mara Bell <mara@example.test>'))
    ->to(address('theo@example.test'))
    ->cc(address('june@example.test'));
```

## Bodies

A message always has a plain-text body. An HTML body is optional and is sent
as an alternative, so clients that cannot render HTML still get the text.

```php
$message
    ->text('Proofs are on the sill.')
    ->html('<p>Proofs are on the <strong>sill</strong>.</p>');
```

The `textFromHtml()` function makes a reasonable text body from HTML when you
only have the latter.

## Custom headers

Headers come from the
[`HasHeaders`](../api/traits/postbox/has-headers.html) trait. Names are
normalised to `Title-Case` and line breaks are stripped from values, so a
header can never smuggle in a second header.

```php
$message->withHeader('x-campaign', 'autumn-broadsheet');
$message->header('X-Campaign'); // "autumn-broadsheet"
```

## Priority

[`Priority`](../api/enums/postbox/priority.html) is an enum with `High`,
`Normal`, and `Low` cases. It becomes the `X-Priority` header.

```php
$message->priority(Priority::High);
```

## Checking before sending

`assertSendable()` throws a
[`MessageException`](../api/classes/postbox/exception/message-exception.html)
when the sender, a recipient, or the subject is missing. Transports call it for
you, but it is useful in validation code.
