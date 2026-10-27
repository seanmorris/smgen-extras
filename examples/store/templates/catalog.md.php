<?php

/**
 * Renders the storefront catalog from every published product row.
 */

echo $context->frontmatter([
	'title' => 'Products',
	'description' => 'A static catalog generated from selected product records.',
	'TOC' => true,
	'menus' => [
		'catalog' => [
			'parent' => 'product-records',
			'title' => 'All products',
			'weight' => 0,
		],
	],
]);
?>
# Products

These 18 product pages come from rows whose `Published` value is `yes`.

## Product catalog

<ul class="catalog-list">
<?php foreach($records as $record): ?>
<?php $product = $record['row']; ?>
<li><a href="<?=$context->escapeHtml($product['ean'])?>.html"><span><strong><?=$context->escapeHtml($product['title'])?></strong><small><?=$context->escapeHtml($product['brand'] . ' · ' . $product['category'])?></small></span><small><?=$context->escapeHtml($product['currency'] . ' ' . number_format((float) $product['price'], 2, '.', ','))?></small></a></li>
<?php endforeach; ?>
</ul>

[Back to the example](../index.html)
