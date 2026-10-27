<?php

echo $context->frontmatter([
	'title' => $row['title'],
	'storePage' => true,
	'plugins' => ['smgen-store' => true],
	'store' => [
		'schema' => 1,
		'type' => 'product',
		'id' => $row['sku'],
		'name' => $row['title'],
		'description' => $row['description'],
		'price' => ['amount' => $row['price'], 'currency' => $row['currency']],
		'checkout' => [
			'provider' => $row['provider'],
			'presentation' => $row['presentation'],
			'url' => $row['checkout_url'],
		],
	],
	'jsonld' => [
		'@context' => 'https://schema.org',
		'@type' => 'Product',
		'sku' => $row['sku'],
		'name' => $row['title'],
		'offers' => [
			'@type' => 'Offer',
			'price' => $row['price'],
			'priceCurrency' => $row['currency'],
			'url' => $row['checkout_url'],
		],
	],
]);
?>
# <?=$context->escapeHtml($row['title'])?>

<?=$row['description']?>

::: {.smgen-store-checkout}
:::
