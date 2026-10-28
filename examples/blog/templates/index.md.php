<?php

/**
 * Renders the blog index from every published CSV record.
 */

echo $context->frontmatter([
	'title' => 'Field Notes',
	'description' => 'A small blog generated from CSV with a static timeline and RSS feed.',
	'TOC' => false,
	'jsonld' => [
		'@context' => 'https://schema.org',
		'@type' => 'Blog',
		'name' => 'Field Notes',
		'description' => 'Notes about static publishing, small builds, and durable files.',
	],
]);
?>
# Notes from a spreadsheet

Each post begins as one CSV row. The row template supplies the date, archive
membership, feed membership, and JSON-LD before SMGen renders the site.

<p class="blog-feed-link"><a href="feed.xml">Subscribe to the RSS feed</a></p>

<ul class="blog-list">
<?php foreach($records as $record): ?>
<?php
$post = $record['row'];
$published = new DateTimeImmutable($post['date']);
?>
<li>
<time datetime="<?=$context->escapeHtml($post['date'])?>"><?=$context->escapeHtml($published->format('M j, Y'))?></time>
<div>
<a href="posts/<?=$context->escapeHtml($post['slug'])?>.html"><strong><?=$context->escapeHtml($post['title'])?></strong></a>
<p><?=$context->escapeHtml($post['description'])?></p>
<small><?=$context->escapeHtml($post['category'])?></small>
</div>
</li>
<?php endforeach; ?>
</ul>

<p class="record-note">The archive uses native disclosure controls. The page loads no blog-specific JavaScript. <a href="about.html">See how the build works</a>.</p>

<a href="https://seanmorris.github.io/smgen-plugins/data-pages.html" target="_blank" rel="noopener noreferrer">Read the smgen-data-pages guide</a>
or <a href="https://github.com/seanmorris/smgen-plugins/tree/main/examples/blog" target="_blank" rel="noopener noreferrer">inspect this example's source</a>.
