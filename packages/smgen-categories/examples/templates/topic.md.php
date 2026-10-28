<?php

/**
 * Example topic rollup: a heading, a standfirst, and one entry per member page.
 */

echo $context->frontmatter([
	'title' => $category['label'],
	'description' => 'Every post filed under ' . $category['label'] . '.',
	'menus' => ['documentation' => ['parent' => 'topics', 'weight' => 10]],
]);
?>

# <?=$context->escapeHtml($category['label'])?>

<?=$category['count']?> <?=$category['count'] === 1 ? 'post' : 'posts'?>.

<?php foreach($members as $member): ?>
- [<?=$context->escapeHtml($member['title'])?>](<?=$context->escapeHtml($member['path'])?>)<?php if($member['description'] !== ''): ?> — <?=$context->escapeHtml($member['description'])?><?php endif; ?>

<?php endforeach; ?>
