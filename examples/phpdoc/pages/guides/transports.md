---
title: Transports
description: Deliver over SMTP, read the delivery result, and handle failure.
TOC: true
menus:
  reference:
    parent: guides
    weight: 40
---

# Transports

A transport implements the
[`Transport`](../api/interfaces/postbox/transport/transport.html) interface:
one `send()` method and a `describe()` method for logs.

## SMTP

[`SmtpTransport`](../api/classes/postbox/transport/smtp-transport.html) is the
bundled implementation. It connects with STARTTLS on port 587 by default.

```php
use Postbox\Transport\SmtpTransport;

$transport = (new SmtpTransport('mail.example.test', 587, 'studio', $password))
    ->withTimeout(5);
```

The password parameter is marked `#[\SensitiveParameter]`, so it is redacted
from stack traces.

## Reading the result

`send()` returns a
[`DeliveryResult`](../api/classes/postbox/transport/delivery-result.html)
with a message identifier and the accepted state of every recipient.

```php
$result = $transport->send($message);

$result->messageId;    // "<3f9c…@mail.example.test>"
$result->isComplete(); // true when nobody was rejected
$result->rejected();   // list of refused addresses
```

## Handling failure

Two different things can go wrong, and they are reported differently.

- The server could not be reached or refused the session. `send()` throws a
  [`DeliveryException`](../api/classes/postbox/exception/delivery-exception.html)
  and nothing was delivered.
- The server accepted the session but refused one or more recipients. `send()`
  returns normally and the refused addresses appear in `rejected()`.

## Writing your own

Implement `Transport` to deliver through an HTTP API, a queue, or a test
double. Keep `describe()` free of credentials, because it is meant for logs.

```php
final class ArrayTransport implements Transport
{
    public array $sent = [];

    public function send(Message $message): DeliveryResult
    {
        $message->assertSendable();
        $this->sent[] = $message;
        $result = new DeliveryResult('<test@local>');
        foreach ($message->recipients() as $recipient) {
            $result->record($recipient, true);
        }
        return $result;
    }

    public function describe(): string
    {
        return 'array://';
    }
}
```
