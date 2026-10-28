<?php

/**
 * Default day rollup: every page published on one calendar day, newest first.
 *
 * Variables: $category (value is the day as Y-m-d, label is the long date),
 * $members (title, path, description, date, metadata), $context.
 */

echo $context->frontmatter([
	'title' => $category['label'],
	'description' => $category['count'] . ' posts published on ' . $category['label'] . '.',
	'TOC' => false,
	'archive' => false,
]);
?>

# <?=$context->escapeHtml($category['label'])?>

<?=$category['count']?> posts were published on this day.

<ul class="timeline-day">
<?php foreach($members as $member): ?>
<li><a href="<?=$context->escapeHtml($member['path'])?>"><?=$context->escapeHtml($member['title'])?></a><?php if($member['description'] !== ''): ?> — <?=$context->escapeHtml($member['description'])?><?php endif; ?></li>
<?php endforeach; ?>
</ul>
