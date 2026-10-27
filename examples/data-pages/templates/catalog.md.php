<?php

/**
 * Renders the field-note collection from all selected CSV records.
 */

echo $context->frontmatter([
	'title' => 'Field Notes',
	'description' => 'Published notes selected from the example CSV file.',
	'TOC' => false,
]);
?>
# Field notes

Only rows with `Published` set to `yes` appear here.

<ul class="catalog-list">
<?php foreach($records as $record): ?>
<li><a href="<?=$context->escapeHtml($record['row']['slug'])?>.html"><span><?=$context->escapeHtml($record['row']['title'])?></span><small><?=$context->escapeHtml($record['row']['habitat'])?></small></a></li>
<?php endforeach; ?>
</ul>
