---
title: Getting started
description: Install Postbox, compose a first message, and send it over SMTP.
TOC: true
menus:
  reference:
    parent: postbox-guides
    weight: 10
---

# Getting started

Postbox is a small library with three jobs: describe an email, deliver it, and
tell you what happened. This page gets you from an empty project to a sent
message.

## Install

```bash
composer require postbox/postbox
```

Postbox needs PHP 8.2 or newer and nothing else. The SMTP transport uses the
standard socket functions.

## Compose a message

Every message starts with a sender and gains recipients, a subject, and a body
through a fluent interface. The `address()` helper parses header-style strings
so you do not have to split names from mailboxes yourself.

```php
use Postbox\Message;
use function Postbox\address;

$message = Message::from(address('Studio <studio@example.test>'))
    ->to(address('mara@example.test'))
    ->subject('Proofs are ready')
    ->text('The autumn broadsheet proofs are on the sill.');
```

See [`Message`](../api/classes/postbox/message.html) for every builder method.

## Send it

A transport takes a message and returns a
[`DeliveryResult`](../api/classes/postbox/transport/delivery-result.html).

```php
use Postbox\Transport\SmtpTransport;

$transport = new SmtpTransport('mail.example.test', 587, 'studio', $password);
$result = $transport->send($message);

if (!$result->isComplete()) {
    foreach ($result->rejected() as $email) {
        error_log("Rejected: {$email}");
    }
}
```

## What can go wrong

| Problem | What Postbox does |
| --- | --- |
| Address does not parse | Throws `InvalidAddressException` when the `Address` is created |
| No sender, recipient, or subject | Throws `MessageException` from `assertSendable()` before connecting |
| Server unreachable | Throws `DeliveryException` with the transport description |
| One recipient refused | Records it in the result; the others are still delivered |

Next: [Messages](messages.html) covers headers, HTML bodies, and priority.
