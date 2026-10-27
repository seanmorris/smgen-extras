<?php

/**
 * Renders one selected product row with store metadata and JSON-LD.
 */

$availabilityLabels = [
	'in_stock' => 'In stock',
	'pre_order' => 'Pre-order',
	'limited_stock' => 'Limited stock',
	'backorder' => 'Backorder',
];
$schemaAvailability = [
	'in_stock' => 'https://schema.org/InStock',
	'pre_order' => 'https://schema.org/PreOrder',
	'limited_stock' => 'https://schema.org/LimitedAvailability',
	'backorder' => 'https://schema.org/BackOrder',
];
$providerLabels = [
	'hosted-link' => 'Hosted checkout link',
	'stripe' => 'Stripe Payment Link',
	'paypal' => 'PayPal payment link',
	'square' => 'Square payment link',
];
$availability = $availabilityLabels[$row['availability']] ?? $row['availability'];
$offerAvailability = $schemaAvailability[$row['availability']] ?? 'https://schema.org/OnlineOnly';
$provider = $providerLabels[$row['provider']] ?? $row['provider'];
$formattedPrice = number_format((float) $row['price'], 2, '.', ',');

echo $context->frontmatter([
	'title' => $row['title'],
	'description' => $row['description'],
	'TOC' => true,
	'menus' => [
		'catalog' => [
			'parent' => 'product-records',
			'weight' => (int) $row['index'],
		],
	],
	'storePage' => true,
	'plugins' => ['smgen-store' => true],
	'store' => [
		'schema' => 1,
		'type' => 'product',
		'id' => $row['ean'],
		'name' => $row['title'],
		'description' => $row['description'],
		'price' => [
			'amount' => $row['price'],
			'currency' => $row['currency'],
		],
		'checkout' => [
			'provider' => $row['provider'],
			'presentation' => $row['presentation'],
			'url' => $row['checkout_url'],
			'label' => 'Open example checkout',
		],
	],
	'jsonld' => [
		'@context' => 'https://schema.org',
		'@type' => 'Product',
		'name' => $row['title'],
		'description' => $row['description'],
		'brand' => [
			'@type' => 'Brand',
			'name' => $row['brand'],
		],
		'category' => $row['category'],
		'color' => $row['color'],
		'size' => $row['size'],
		'gtin13' => $row['ean'],
		'offers' => [
			'@type' => 'Offer',
			'price' => $row['price'],
			'priceCurrency' => $row['currency'],
			'availability' => $offerAvailability,
			'url' => $row['checkout_url'],
		],
	],
]);
?>
# <?=$context->escapeHtml($row['title'])?>

<p class="record-kicker"><?=$context->escapeHtml($row['brand'])?> · <?=$context->escapeHtml($row['category'])?></p>

<?=$context->escapeHtml($row['description'])?>

<p class="product-price"><?=$context->escapeHtml($row['currency'])?> <?=$context->escapeHtml($formattedPrice)?></p>

## Product details

<dl class="record-details">
<dt>Availability</dt><dd><?=$context->escapeHtml($availability)?></dd>
<dt>Stock</dt><dd><?=$context->escapeHtml($row['stock'])?></dd>
<dt>Color</dt><dd><?=$context->escapeHtml($row['color'])?></dd>
<dt>Size</dt><dd><?=$context->escapeHtml($row['size'])?></dd>
<dt>EAN</dt><dd><?=$context->escapeHtml($row['ean'])?></dd>
<dt>Checkout</dt><dd><?=$context->escapeHtml($provider)?></dd>
</dl>

## Checkout

::: {.smgen-store-checkout}
:::

[Back to all products](index.html)
