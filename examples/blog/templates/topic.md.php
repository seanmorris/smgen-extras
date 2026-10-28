<?php

/**
 * Renders one topic page. The page template lists the posts; this file supplies the frontmatter.
 */

$blurbs = [
	'Workshop' => 'Press days, ink recipes, and the slow care of old machines.',
	'Walks' => 'Loops along the sea wall, the headland, and the ferry slip.',
	'Reading' => 'Books that earned a place on the studio shelf.',
	'Tools' => 'Pencils, presses, and plain files that keep working.',
	'Studio' => 'Who we are, how we work, and what we charge for.',
];

echo $context->frontmatter([
	'title' => $category['label'],
	'description' => $blurbs[$category['value']] ?? ($category['count'] . ' notes filed under ' . $category['label'] . '.'),
	'layout' => 'topic',
	'topic' => $category['value'],
	'TOC' => false,
]);
