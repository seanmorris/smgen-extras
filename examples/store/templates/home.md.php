<?php

/**
 * Renders the shop front page from the published product rows.
 */

require_once __DIR__ . '/shop-lib.php';

$categories = shopCategories();
$rows = array_map(static fn(array $record): array => $record['row'], $records);
$featured = array_values(array_filter($rows, static fn(array $row): bool => $row['featured'] === 'yes'));
$featured = array_slice($featured, 0, 4);

echo $context->frontmatter([
	'title' => 'Fernway Goods',
	'description' => 'Notebooks, pens, ink, and desk goods made in small runs by a harbour print studio.',
	'layout' => 'home',
	'TOC' => false,
	'jsonld' => [
		'@context' => 'https://schema.org',
		'@type' => 'Store',
		'name' => 'Fernway Goods',
		'description' => 'Notebooks, pens, ink, and desk goods made in small runs by a harbour print studio.',
	],
]);
?>
<section class="hero">
<div class="hero-copy">
<p class="eyebrow">Small runs, printed and packed in the studio</p>
<h1>Things for people who still write things down.</h1>
<p class="hero-lede">Notebooks that lie flat, pens with some weight to them, and the house ink in a bottle. Everything here is made in batches of a few dozen and sold until it is gone.</p>
<p class="hero-actions"><a class="button" href="products/index.html">Shop the catalog</a><a class="button button--ghost" href="about.html">Our story</a></p>
</div>
<ul class="hero-tiles" aria-label="Featured products">
<?php foreach(array_slice($featured, 0, 3) as $row): ?>
<li><a href="products/<?=$context->escapeHtml($row['slug'])?>.html" aria-label="<?=$context->escapeHtml($row['title'])?>"><?=shopTile($row, $context, 'hero-grid', 'images/products/')?></a></li>
<?php endforeach; ?>
</ul>
</section>

<section class="band" aria-labelledby="featured-title">
<div class="band-head">
<h2 id="featured-title">This season</h2>
<a href="products/index.html">All <?=count($rows)?> products</a>
</div>
<ul class="cards">
<?php foreach($featured as $row): ?>
<?=shopCard($row, $context, 'products/', 'images/products/')?>
<?php endforeach; ?>
</ul>
</section>

<section class="band" aria-labelledby="categories-title">
<div class="band-head">
<h2 id="categories-title">Shop by category</h2>
</div>
<ul class="category-grid">
<?php foreach($categories as $name => $category): ?>
<?php $count = count(array_filter($rows, static fn(array $row): bool => $row['category'] === $name)); ?>
<li><a href="categories/<?=$context->escapeHtml($category['slug'])?>.html"><svg class="category-icon" aria-hidden="true"><use href="#icon-<?=$context->escapeHtml($category['slug'])?>"/></svg><strong><?=$context->escapeHtml($name)?></strong><span><?=$context->escapeHtml($category['blurb'])?></span><small><?=$count?> <?=$count === 1 ? 'item' : 'items'?></small></a></li>
<?php endforeach; ?>
</ul>
</section>

<section class="story">
<div>
<p class="eyebrow">Why small runs</p>
<h2>A run of fifty takes an afternoon. A run of five thousand takes the joy out of it.</h2>
<p>Every notebook is sewn on the bench by the window. Every pen is checked before it goes in a box. When a batch sells out, the page says so, and the next batch goes on the list. <a href="about.html">Read how the shop works</a>.</p>
</div>
</section>
