<?php

/**
 * Renders the customer directory from every selected CSV record.
 */

echo $context->frontmatter([
	'title' => 'Customers',
	'description' => 'A directory generated from 24 synthetic customer records.',
	'TOC' => true,
	'menus' => [
		'directory' => [
			'parent' => 'customer-records',
			'title' => 'All customers',
			'weight' => 0,
		],
	],
]);
?>
# Customers

These 24 profiles come from one CSV file and one row template.

## Customer directory

<ul class="catalog-list">
<?php foreach($records as $record): ?>
<?php $customer = $record['row']; ?>
<li><a href="<?=$context->escapeHtml($customer['customer_id'])?>.html"><span><strong><?=$context->escapeHtml($customer['first_name'] . ' ' . $customer['last_name'])?></strong><small><?=$context->escapeHtml($customer['company'])?></small></span><small><?=$context->escapeHtml($customer['city'] . ', ' . $customer['country'])?></small></a></li>
<?php endforeach; ?>
</ul>

[Back to the example](../index.html)
