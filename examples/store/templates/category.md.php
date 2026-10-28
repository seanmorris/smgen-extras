<?php

/**
 * Renders one shop category from the generated product pages' frontmatter.
 */

require_once __DIR__ . '/shop-lib.php';

$categories = shopCategories();
$blurb = $categories[$category['value']]['blurb'] ?? '';

echo $context->frontmatter([
	'title' => $category['label'],
	'description' => $blurb !== '' ? $blurb : $category['count'] . ' products in ' . $category['label'] . '.',
	'layout' => 'category',
	'TOC' => false,
]);
?>

<ul class="cards">
<?php foreach($members as $member): ?>
<?php $product = $member['metadata']['product'] ?? []; ?>
<li class="card"><a href="<?=$context->escapeHtml($context->url($member['path']))?>"><?=$product['cardTile'] ?? ''?><span class="card-body"><span class="card-name"><?=$context->escapeHtml($member['title'])?></span><span class="card-tagline"><?=$context->escapeHtml((string) ($product['tagline'] ?? ''))?></span><span class="card-price"><?=$context->escapeHtml((string) ($product['price'] ?? ''))?><?php if(($product['availabilityTone'] ?? 'ok') !== 'ok'): ?><span class="badge badge--<?=$context->escapeHtml((string) $product['availabilityTone'])?>"><?=$context->escapeHtml((string) ($product['availability'] ?? ''))?></span><?php endif; ?></span></span></a></li>
<?php endforeach; ?>
</ul>

<p class="category-foot"><a href="../products/index.html">See every product</a></p>
