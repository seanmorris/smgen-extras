<?php

$public = [];
if($row['provider'] === 'stripe' && $row['presentation'] === 'embed')
{
	$public = [
		'buyButtonId' => $row['stripe_button_id'],
		'publishableKey' => $row['stripe_publishable_key'],
	];
}
elseif($row['provider'] === 'paypal' && $row['presentation'] === 'embed')
{
	$public = [
		'hostedButtonId' => $row['paypal_button_id'],
		'clientId' => $row['paypal_client_id'],
	];
}
elseif($row['provider'] === 'paypal' && $row['presentation'] === 'cart')
{
	$public = [
		'merchantId' => $row['paypal_merchant_id'],
		'cartItemId' => $row['paypal_cart_item_id'],
	];
}

$checkout = [
	'provider' => $row['provider'],
	'presentation' => $row['presentation'],
	'url' => $row['checkout_url'],
];
if($public !== [])
{
	$checkout['public'] = $public;
}

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
		'price' => [
			'amount' => $row['price'],
			'currency' => $row['currency'],
		],
		'checkout' => $checkout,
	],
	'jsonld' => [
		'@context' => 'https://schema.org',
		'@type' => 'Product',
		'sku' => $row['sku'],
		'name' => $row['title'],
		'description' => $row['description'],
		'offers' => [
			'@type' => 'Offer',
			'price' => $row['price'],
			'priceCurrency' => $row['currency'],
			'url' => $row['checkout_url'],
			'availability' => 'https://schema.org/InStock',
		],
	],
]);
?>
# <?=$context->escapeHtml($row['title'])?>

<?=$row['description']?>

**<?=$context->escapeHtml($row['currency'])?> <?=$context->escapeHtml($row['price'])?>**

::: {.smgen-store-checkout}
:::
