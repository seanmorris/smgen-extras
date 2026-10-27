<?php

/**
 * Renders the organization directory from every selected CSV record.
 */

echo $context->frontmatter([
	'title' => 'Organizations',
	'description' => 'A directory generated from 24 synthetic organization records.',
	'TOC' => false,
]);
?>
# Organizations

These 24 profiles come from a second CSV file and its own row template.

<ul class="catalog-list">
<?php foreach($records as $record): ?>
<?php $organization = $record['row']; ?>
<li><a href="<?=$context->escapeHtml($organization['organization_id'])?>.html"><span><strong><?=$context->escapeHtml($organization['name'])?></strong><small><?=$context->escapeHtml($organization['industry'])?></small></span><small><?=$context->escapeHtml($organization['country'])?></small></a></li>
<?php endforeach; ?>
</ul>

[Back to the example](../index.html)
