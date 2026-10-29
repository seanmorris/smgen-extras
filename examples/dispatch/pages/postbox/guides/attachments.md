---
title: Attachments
description: Attach files from disk or from memory and keep message size in check.
TOC: true
menus:
  reference:
    parent: postbox-guides
    weight: 30
---

# Attachments

An [`Attachment`](../api/classes/postbox/attachment.html) holds a filename,
raw contents, and a media type. It is read-only once created.

## From disk

`Attachment::fromFile()` reads the file and guesses its media type.

```php
use Postbox\Attachment;

$message->attach(Attachment::fromFile('/srv/proofs/broadsheet.pdf'));
```

Pass a second argument to present the file under a different name:

```php
Attachment::fromFile('/tmp/upload-8f3a.pdf', 'broadsheet.pdf');
```

## From memory

Build the attachment directly when the contents are already in a string, such
as a generated CSV.

```php
$csv = "slug,title\nfeed-you-can-inspect,A feed you can inspect\n";
$message->attach(new Attachment('posts.csv', $csv, 'text/csv'));
```

## Watching the size

`size()` returns the attachment length in bytes. Most servers refuse messages
over a few tens of megabytes, so check before sending:

```php
$total = array_sum(array_map(fn ($a) => $a->size(), $message->attachments()));
if ($total > 20 * 1024 * 1024) {
    throw new RuntimeException('Attachments exceed 20 MB.');
}
```
