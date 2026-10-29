<?php

/**
 * Renders the full catalog grouped by category with anchor navigation.
 */

require_once __DIR__ . '/shop-lib.php';

$categories = shopCategories();
$rows = array_map(static fn(array $record): array => $record['row'], $records);

echo $context->frontmatter([
	'title' => 'All products',
	'description' => 'The complete Fernway Goods catalog: notebooks, writing tools, desk goods, and bags.',
	'layout' => 'catalog',
	'TOC' => false,
	'catalog' => [
		'count' => count($rows),
	],
]);
?>
<nav class="catalog-nav" aria-label="Categories">
<?php foreach($categories as $name => $category): ?>
<a href="../categories/<?=$context->escapeHtml($category['slug'])?>.html"><?=$context->escapeHtml($name)?></a>
<?php endforeach; ?>
</nav>

<?php foreach($categories as $name => $category): ?>
<?php $matching = array_values(array_filter($rows, static fn(array $row): bool => $row['category'] === $name)); ?>
<?php if($matching === []) continue; ?>
<section class="band" id="<?=$context->escapeHtml($category['slug'])?>" aria-labelledby="<?=$context->escapeHtml($category['slug'])?>-title">
<div class="band-head">
<h2 id="<?=$context->escapeHtml($category['slug'])?>-title"><?=$context->escapeHtml($name)?></h2>
<p><?=$context->escapeHtml($category['blurb'])?></p>
</div>
<ul class="cards">
<?php foreach($matching as $row): ?>
<?=shopCard($row, $context, '', '../images/products/')?>
<?php endforeach; ?>
</ul>
</section>

<?php endforeach; ?>
