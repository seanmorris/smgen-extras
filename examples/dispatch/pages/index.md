---
title: Dispatch
description: Developer documentation for Postbox, a PHP mail library, and Signalbox, a JavaScript signal library, both generated from their source on every build.
layout: landing
TOC: false
leftBarShow: false
jsonld:
  "@context": https://schema.org
  "@type": WebSite
  name: Dispatch
  description: Developer documentation for the Postbox and Signalbox libraries.
---

<ol class="board" aria-label="Libraries">
<li class="board-row board-row--postbox">
<span class="board-no">01</span>
<span class="board-name"><a href="postbox/">Postbox</a><small>Compose and deliver email</small></span>
<span class="board-lang">PHP 8.2</span>
<span class="board-ver">v1.2.0</span>
<span class="board-links"><a href="postbox/guides/getting-started.html">Guides</a><a href="postbox/api/index.html">API</a></span>
</li>
<li class="board-row board-row--signalbox">
<span class="board-no">02</span>
<span class="board-name"><a href="signalbox/">Signalbox</a><small>Signals, subscriptions, and timed work</small></span>
<span class="board-lang">ES2022</span>
<span class="board-ver">v0.8.0</span>
<span class="board-links"><a href="signalbox/guides/getting-started.html">Guides</a><a href="signalbox/api/index.html">API</a></span>
</li>
</ol>

<div class="samples">
<section class="sample sample--postbox" aria-labelledby="sample-postbox">
<h2 id="sample-postbox">A message in twelve lines</h2>

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

<p class="sample-link"><a href="postbox/guides/getting-started.html">Start with Postbox</a></p>
</section>
<section class="sample sample--signalbox" aria-labelledby="sample-signalbox">
<h2 id="sample-signalbox">A signal in six lines</h2>

```js
import {createSignal} from '@signalbox/core';

const selected = createSignal('selected');
const subscription = selected.subscribe((id) => console.log(id));
selected.emit('row-7');
subscription.unsubscribe();
```

<p class="sample-link"><a href="signalbox/guides/getting-started.html">Start with Signalbox</a></p>
</section>
</div>

<section class="ledger" aria-labelledby="ledger-title">
<h2 id="ledger-title">How these pages are made</h2>
<dl class="ledger-list">
<div><dt>Guides</dt><dd>Hand-written Markdown, one file per page, kept beside the source.</dd></div>
<div><dt>References</dt><dd>Generated from the PHP source and the JSDoc on each build by <a href="https://seanmorris.github.io/smgen-plugins/phpdoc.html">smgen-phpdoc</a> and <a href="https://seanmorris.github.io/smgen-plugins/jsdoc.html">smgen-jsdoc</a>. Nothing here is a hand-kept copy.</dd></div>
<div><dt>Policy</dt><dd>A public symbol without a summary, a parameter without a description, or a broken <code>@see</code> fails the build before anything is published.</dd></div>
</dl>
</section>
