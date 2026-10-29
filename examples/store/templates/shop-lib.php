<?php

/**
 * Shared helpers for the Fernway Goods row and collection templates.
 */

/** Describe the shop categories in display order. */
function shopCategories(): array
{
	return [
		'Notebooks' => ['slug' => 'notebooks', 'blurb' => 'Sewn, stitched, and bound to lie flat.'],
		'Writing' => ['slug' => 'writing', 'blurb' => 'Pens, pencils, and the house ink.'],
		'Desk' => ['slug' => 'desk', 'blurb' => 'Trays, pads, rulers, and a good lamp.'],
		'Bags' => ['slug' => 'bags', 'blurb' => 'Waxed canvas and leather for carrying it all.'],
	];
}

/** Map availability codes to labels, schema.org URLs, and a visual tone. */
function shopAvailability(string $code): array
{
	$table = [
		'in_stock' => ['label' => 'In stock', 'schema' => 'https://schema.org/InStock', 'tone' => 'ok', 'note' => 'Ships in two working days.'],
		'limited_stock' => ['label' => 'Low stock', 'schema' => 'https://schema.org/LimitedAvailability', 'tone' => 'warn', 'note' => 'Only a few left in this batch.'],
		'pre_order' => ['label' => 'Pre-order', 'schema' => 'https://schema.org/PreOrder', 'tone' => 'info', 'note' => 'Ships when the next batch is finished.'],
		'backorder' => ['label' => 'Backorder', 'schema' => 'https://schema.org/BackOrder', 'tone' => 'info', 'note' => 'Made to order. Allow three weeks.'],
	];
	return $table[$code] ?? ['label' => ucfirst(str_replace('_', ' ', $code)), 'schema' => 'https://schema.org/OnlineOnly', 'tone' => 'info', 'note' => ''];
}

/** Map checkout providers to the name shown beside the checkout button. */
function shopProviderName(string $provider): string
{
	return ['hosted-link' => 'our hosted checkout', 'stripe' => 'Stripe', 'paypal' => 'PayPal', 'square' => 'Square'][$provider] ?? $provider;
}

/** Format a decimal price for display. */
function shopPrice(array $row): string
{
	$amount = (float) $row['price'];
	$symbol = $row['currency'] === 'USD' ? '$' : $row['currency'] . ' ';
	return $symbol . number_format($amount, $amount === floor($amount) ? 0 : 2, '.', ',');
}

/** Choose a dark or light foreground for a hex background. */
function shopInk(string $hex): string
{
	$hex = ltrim($hex, '#');
	if(strlen($hex) !== 6)
	{
		return '#1c1a17';
	}
	[$r, $g, $b] = [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
	$luminance = (0.2126 * $r + 0.7152 * $g + 0.0722 * $b) / 255;
	return $luminance > 0.6 ? '#1c1a17' : '#fbf8f2';
}

/**
 * Render a product photograph with the original illustrated tile as a fallback.
 *
 * @param array<string, string> $row Normalized product data.
 * @param object $context Template context that escapes HTML.
 * @param string $size Tile size modifier.
 * @param string $imageBase Relative directory containing product images.
 */
function shopTile(array $row, object $context, string $size = 'card', string $imageBase = '../images/products/'): string
{
	$categories = shopCategories();
	$icon = $categories[$row['category']]['slug'] ?? 'desk';
	$hex = preg_match('/^#[0-9a-fA-F]{6}$/', $row['color_hex']) ? $row['color_hex'] : '#8a8a8a';
	$image = trim((string) ($row['image'] ?? ''));
	if($image !== '')
	{
		$source = $imageBase . basename($image);
		$loading = $size === 'card' ? ' loading="lazy"' : '';
		$priority = $size === 'hero' ? ' fetchpriority="high"' : '';
		return '<div class="tile tile--photo tile--' . $context->escapeHtml($size) . '">'
			. '<img src="' . $context->escapeHtml($source) . '" width="960" height="960" alt="' . $context->escapeHtml($row['title'] . ', ' . $row['color_name']) . '"' . $loading . $priority . '>'
			. '<span class="tile-sku" aria-hidden="true">' . $context->escapeHtml($row['sku']) . '</span>'
			. '</div>';
	}
	return '<div class="tile tile--' . $context->escapeHtml($size) . '" style="--tile: ' . $context->escapeHtml($hex) . '; --tile-ink: ' . $context->escapeHtml(shopInk($hex)) . '" role="img" aria-label="' . $context->escapeHtml($row['title'] . ' in ' . $row['color_name']) . '">'
		. '<svg class="tile-icon" aria-hidden="true"><use href="#icon-' . $context->escapeHtml($icon) . '"/></svg>'
		. '<span class="tile-sku" aria-hidden="true">' . $context->escapeHtml($row['sku']) . '</span>'
		. '</div>';
}

/**
 * Render one product card for a grid.
 *
 * @param array<string, string> $row Normalized product data.
 * @param object $context Template context that escapes HTML.
 * @param string $prefix Relative product-page prefix.
 * @param string $imageBase Relative directory containing product images.
 */
function shopCard(array $row, object $context, string $prefix, string $imageBase): string
{
	$availability = shopAvailability($row['availability']);
	$badge = $row['availability'] === 'in_stock' ? '' : '<span class="badge badge--' . $context->escapeHtml($availability['tone']) . '">' . $context->escapeHtml($availability['label']) . '</span>';
	return '<li class="card"><a href="' . $prefix . $context->escapeHtml($row['slug']) . '.html">'
		. shopTile($row, $context, 'card', $imageBase)
		. '<span class="card-body"><span class="card-name">' . $context->escapeHtml($row['title']) . '</span>'
		. '<span class="card-tagline">' . $context->escapeHtml($row['tagline']) . '</span>'
		. '<span class="card-price">' . $context->escapeHtml(shopPrice($row)) . $badge . '</span></span>'
		. '</a></li>';
}
