<?php

/**
 * Renders one field-note row as an SMGen Markdown page.
 */

echo $context->frontmatter([
	'title' => $row['title'],
	'description' => $row['summary'],
	'jsonld' => [
		'@context' => 'https://schema.org',
		'@type' => 'Article',
		'headline' => $row['title'],
		'description' => $row['summary'],
	],
]);
?>
# <?=$context->escapeHtml($row['title'])?>

**Habitat:** <?=$context->escapeHtml($row['habitat'])?>

<?=$row['summary']?>

[Back to all field notes](index.html)
