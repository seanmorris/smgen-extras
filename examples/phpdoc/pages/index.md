---
title: Postbox
description: A small PHP library for composing and delivering email, documented from its source.
layout: landing
TOC: false
leftBarShow: false
menus:
  reference:
    parent: guides
    title: Overview
    weight: 0
jsonld:
  "@context": https://schema.org
  "@type": SoftwareSourceCode
  name: Postbox
  description: A small PHP library for composing and delivering email.
  programmingLanguage: PHP
---

<div class="landing-grid">
<a class="landing-card" href="guides/getting-started.html"><strong>Getting started</strong><span>Install the package, build a message, and send it in a dozen lines.</span></a>
<a class="landing-card" href="guides/messages.html"><strong>Messages</strong><span>Recipients, subjects, text and HTML bodies, headers, and priority.</span></a>
<a class="landing-card" href="guides/attachments.html"><strong>Attachments</strong><span>Attach files from disk or memory and keep an eye on size.</span></a>
<a class="landing-card" href="guides/transports.html"><strong>Transports</strong><span>Deliver over SMTP, read the result, and handle failure.</span></a>
<a class="landing-card landing-card--api" href="api/index.html"><strong>API reference</strong><span>Every public class, method, and function, generated from the source on each build.</span></a>
</div>

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
