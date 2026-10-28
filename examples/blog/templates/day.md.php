<?php

/**
 * Renders one day's notes: every post published on the same calendar day.
 */

$topics = ['Workshop' => 'workshop', 'Walks' => 'walks', 'Reading' => 'reading', 'Tools' => 'tools', 'Studio' => 'studio'];

echo $context->frontmatter([
	'title' => $category['label'],
	'description' => $category['count'] . ' notes from ' . $category['label'] . '.',
	'layout' => 'day',
	'day' => $category['value'],
	'TOC' => false,
	'archive' => false,
]);
?>

<ol class="entries">
<?php foreach($members as $member): ?>
<?php $topic = (string) ($member['metadata']['topic'] ?? ''); $published = new DateTimeImmutable((string) $member['date']); ?>
<li class="entry">
<div class="entry-meta"><?php if(isset($topics[$topic])): ?><a class="entry-topic" href="../topics/<?=$context->escapeHtml($topics[$topic])?>.html"><?=$context->escapeHtml($topic)?></a><?php endif; ?><time datetime="<?=$context->escapeHtml((string) $member['date'])?>"><?=$context->escapeHtml($published->format('H:i'))?></time></div>
<h3 class="entry-title"><a href="<?=$context->escapeHtml($member['path'])?>"><?=$context->escapeHtml($member['title'])?></a></h3>
<p class="entry-description"><?=$context->escapeHtml($member['description'])?></p>
<p class="entry-byline"><?=$context->escapeHtml((string) ($member['metadata']['author'] ?? ''))?></p>
</li>
<?php endforeach; ?>
</ol>
