<?php

/**
 * Renders one published product row with store metadata, JSON-LD, and a buy box.
 */

require_once __DIR__ . '/shop-lib.php';

$categories = shopCategories();
$category = $categories[$row['category']] ?? ['slug' => $context->pathSegment($row['category']), 'blurb' => ''];
$availability = shopAvailability($row['availability']);
$highlights = array_values(array_filter(array_map('trim', explode('|', $row['highlights']))));
$price = shopPrice($row);

echo $context->frontmatter([
	'title' => $row['title'],
	'description' => $row['tagline'] . ' ' . $row['description'],
	'layout' => 'product',
	'TOC' => false,
	'storePage' => true,
	'plugins' => ['smgen-store' => true],
	'product' => [
		'category' => $row['category'],
		'categorySlug' => $category['slug'],
		'tagline' => $row['tagline'],
		'price' => $price,
		'availability' => $availability['label'],
		'availabilityTone' => $availability['tone'],
		'availabilityNote' => $availability['note'],
		'provider' => shopProviderName($row['provider']),
		'colorHex' => $row['color_hex'],
		'colorName' => $row['color_name'],
		'sku' => $row['sku'],
		'image' => '/' . ltrim($row['image'], '/'),
		'tile' => shopTile($row, $context, 'hero', '../images/products/'),
		'cardTile' => shopTile($row, $context, 'card', '../images/products/'),
	],
	'store' => [
		'schema' => 1,
		'type' => 'product',
		'id' => $row['sku'],
		'name' => $row['title'],
		'description' => $row['tagline'],
		'price' => [
			'amount' => $row['price'],
			'currency' => $row['currency'],
		],
		'checkout' => [
			'provider' => $row['provider'],
			'presentation' => $row['presentation'],
			'url' => $row['checkout_url'],
			'label' => 'Buy now · ' . $price,
		],
	],
	'jsonld' => [
		'@context' => 'https://schema.org',
		'@type' => 'Product',
		'name' => $row['title'],
		'description' => $row['description'],
		'sku' => $row['sku'],
		'brand' => ['@type' => 'Brand', 'name' => 'Fernway Goods'],
		'category' => $row['category'],
		'color' => $row['color_name'],
		'material' => $row['material'],
		'offers' => [
			'@type' => 'Offer',
			'price' => $row['price'],
			'priceCurrency' => $row['currency'],
			'availability' => $availability['schema'],
			'url' => $row['checkout_url'],
		],
	],
]);
?>
::: {.smgen-store-checkout}
:::

<p class="checkout-note">Secure checkout with <?=$context->escapeHtml(shopProviderName($row['provider']))?>. <?=$context->escapeHtml($availability['note'])?></p>

<?=$context->escapeHtml($row['description'])?>

<h2 class="spec-title">Details</h2>

<dl class="spec">
<div><dt>Material</dt><dd><?=$context->escapeHtml($row['material'])?></dd></div>
<div><dt>Size</dt><dd><?=$context->escapeHtml($row['dimensions'])?></dd></div>
<div><dt>Colour</dt><dd><span class="swatch" style="--swatch: <?=$context->escapeHtml($row['color_hex'])?>"></span><?=$context->escapeHtml($row['color_name'])?></dd></div>
<div><dt>SKU</dt><dd><?=$context->escapeHtml($row['sku'])?></dd></div>
</dl>

<ul class="highlights">
<?php foreach($highlights as $highlight): ?>
<li><?=$context->escapeHtml($highlight)?></li>
<?php endforeach; ?>
</ul>
