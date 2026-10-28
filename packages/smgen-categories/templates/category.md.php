<?php

/**
 * Default rollup page: a heading, a count, and a list of member pages.
 *
 * Variables: $category (name, field, group, value, label, slug, count),
 * $members (source, path, title, description, date, metadata), $context.
 */

echo $context->frontmatter([
	'title' => $category['label'],
	'description' => $category['count'] . ' ' . ($category['count'] === 1 ? 'page' : 'pages') . ' in ' . $category['label'] . '.',
	'TOC' => false,
]);
?>

# <?=$context->escapeHtml($category['label'])?>

<ul class="category-members">
<?php foreach($members as $member): ?>
<li><a href="<?=$context->escapeHtml($context->url($member['path']))?>"><?=$context->escapeHtml($member['title'])?></a><?php if($member['description'] !== ''): ?> — <?=$context->escapeHtml($member['description'])?><?php endif; ?></li>
<?php endforeach; ?>
</ul>
