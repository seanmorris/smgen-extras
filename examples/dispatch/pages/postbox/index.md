---
title: Postbox
description: A small PHP library for composing and delivering email, documented from its source.
layout: library
guides:
  - href: guides/getting-started.html
    title: Getting started
    summary: Install the package, build a message, and send it in a dozen lines.
  - href: guides/messages.html
    title: Messages
    summary: Recipients, subjects, text and HTML bodies, headers, and priority.
  - href: guides/attachments.html
    title: Attachments
    summary: Attach files from disk or memory and keep an eye on size.
  - href: guides/transports.html
    title: Transports
    summary: Deliver over SMTP, read the result, and handle failure.
TOC: false
menus:
  reference:
    parent: postbox
    title: Overview
    weight: 0
jsonld:
  "@context": https://schema.org
  "@type": SoftwareSourceCode
  name: Postbox
  description: A small PHP library for composing and delivering email.
  programmingLanguage: PHP
---

## A message in twelve lines

```php
use Postbox\Message;
use Postbox\Priority;
use Postbox\Transport\SmtpTransport;
use function Postbox\address;

$message = Message::from(address('Studio <studio@example.test>'))
    ->to(address('Mara Bell <mara@example.test>'))
    ->subject('Proofs are ready')
    ->text('The autumn broadsheet proofs are on the sill.')
    ->priority(Priority::High);

$result = (new SmtpTransport('mail.example.test'))->send($message);
```

## Why Postbox

- **Immutable where it matters.** Addresses and attachments are read-only value objects. Messages are fluent and mutable until they are sent.
- **Honest failures.** A missing sender or recipient throws before the socket opens. A refused recipient is recorded per address in the delivery result.
- **No surprises in headers.** Line breaks are stripped from subjects and header values, and names are quoted correctly.
