<?php

/**
 * Renders the Fernway Goods storefront shell with home, catalog, product, and plain page layouts.
 */

$layout = $frontmatter['layout'] ?? 'page';
$product = $frontmatter['product'] ?? null;
$activeCategory = $product['categorySlug'] ?? (is_array($frontmatter['category'] ?? null) ? ($frontmatter['category']['slug'] ?? '') : '');
$source = preg_replace('#^.*?/pages/(?:\.smgen-generated/[^/]+/)?#', '', (string) $pageFile) ?? '';
/** Escape text for the page body and double dollars so Pandoc treats them literally. */
function tpl(string $value): string
{
	return str_replace('$', '$$', escapeHtml($value));
}

$navigation = [
	['href' => '/products/index.html', 'label' => 'Shop all', 'active' => $layout === 'catalog'],
	['href' => '/categories/notebooks.html', 'label' => 'Notebooks', 'active' => $activeCategory === 'notebooks'],
	['href' => '/categories/writing.html', 'label' => 'Writing', 'active' => $activeCategory === 'writing'],
	['href' => '/categories/desk.html', 'label' => 'Desk', 'active' => $activeCategory === 'desk'],
	['href' => '/categories/bags.html', 'label' => 'Bags', 'active' => $activeCategory === 'bags'],
	['href' => '/about.html', 'label' => 'Our story', 'active' => $source === 'about.md'],
];

?><!DOCTYPE html>
<html lang="en" class="<?=escapeHtml(getConf('DEFAULT_THEME') ?: 'theme-store')?>">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>$if(pagetitle)$${pagetitle}$else$${title}$endif$$if(title-prefix)$ – ${title-prefix}$endif$</title>
$if(description-meta)$
	<meta name="description" content="$description-meta$">
$endif$
	<meta name="theme-color" content="#2f5d3a">
	<link rel="icon" type="image/svg+xml" href="<?=escapeHtml(resolveAssetUrl('/mark.svg'))?>">
<?php renderJsonLd($frontmatter, $pageFile); ?>
	<style>$styles.html()$</style>
$for(css)$
	<link rel="stylesheet" href="${css}">
$endfor$
<?php foreach(getConf('SCRIPTS') ?: [] as $javascript): ?>
	<script src="<?=escapeHtml(resolveAssetUrl($javascript))?>"></script>
<?php endforeach; ?>
<?php foreach(getConf('DEFER_SCRIPTS') ?: [] as $javascript): ?>
	<script defer src="<?=escapeHtml(resolveAssetUrl($javascript))?>"></script>
<?php endforeach; ?>
</head>
<body class="layout-<?=escapeHtml($layout)?>">
	<svg class="sprite" aria-hidden="true" focusable="false">
		<symbol id="icon-notebooks" viewBox="0 0 48 48"><rect x="11" y="6" width="28" height="36" rx="3"/><path d="M11 14h-3M11 22h-3M11 30h-3M11 38h-3M18 16h14M18 23h14M18 30h8"/></symbol>
		<symbol id="icon-writing" viewBox="0 0 48 48"><path d="M30 8l10 10-20 20H10V28z"/><path d="M26 12l10 10M10 38l4-4"/></symbol>
		<symbol id="icon-desk" viewBox="0 0 48 48"><path d="M10 40h28M24 40V30"/><path d="M14 30h20l-5-10h-10z"/><path d="M19 20c0-6-3-10-8-10"/><circle cx="11" cy="10" r="3"/></symbol>
		<symbol id="icon-bags" viewBox="0 0 48 48"><path d="M8 18h32l-3 22H11z"/><path d="M17 18v-4a7 7 0 0 1 14 0v4"/><path d="M8 26h32"/></symbol>
	</svg>
	<a class="skip-link" href="#content">Skip to content</a>
	<p class="announce"><span>Free shipping on orders over $$60</span><span>Printed and packed in the studio</span><span>Pre-orders ship when the batch is done</span></p>
	<header class="header">
		<div class="header-inner">
			<a class="brand" href="<?=escapeHtml(resolveAssetUrl('/'))?>">
				<img src="<?=escapeHtml(resolveAssetUrl('/mark.svg'))?>" width="32" height="32" alt="">
				<span><?=escapeHtml(getConf('PRODUCT_NAME'))?></span>
			</a>
			<nav class="nav" aria-label="Shop">
				<?php foreach($navigation as $link): ?>
				<a href="<?=escapeHtml(resolveAssetUrl($link['href']))?>"<?=$link['active'] ? ' aria-current="page"' : ''?>><?=escapeHtml($link['label'])?></a>
				<?php endforeach; ?>
			</nav>
			<button type="button" class="theme-toggle" data-theme-toggle aria-label="Switch to light theme" aria-pressed="false">
				<svg class="theme-icon theme-icon--moon" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path d="M20.5 14.2A8.5 8.5 0 0 1 9.8 3.5a8.5 8.5 0 1 0 10.7 10.7z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
				<svg class="theme-icon theme-icon--sun" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M12 2.5v2.5M12 19v2.5M2.5 12H5M19 12h2.5M5.3 5.3l1.8 1.8M16.9 16.9l1.8 1.8M5.3 18.7l1.8-1.8M16.9 7.1l1.8-1.8" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
			</button>
		</div>
	</header>

	<main id="content" class="main">
		<?php if($layout === 'product' && is_array($product)): ?>
		<nav class="crumbs" aria-label="Breadcrumb">
			<a href="<?=escapeHtml(resolveAssetUrl('/'))?>">Shop</a>
			<a href="<?=escapeHtml(resolveAssetUrl('/categories/' . $product['categorySlug'] . '.html'))?>"><?=escapeHtml($product['category'])?></a>
			<span aria-current="page">$title$</span>
		</nav>
		<article class="product">
			<div class="product-media">
				<?=$product['tile']?>
				<p class="product-colour"><span class="swatch" style="--swatch: <?=escapeHtml($product['colorHex'])?>"></span><?=escapeHtml($product['colorName'])?></p>
			</div>
			<div class="product-buy">
				<p class="eyebrow"><a href="<?=escapeHtml(resolveAssetUrl('/categories/' . $product['categorySlug'] . '.html'))?>"><?=escapeHtml($product['category'])?></a></p>
				<h1 class="product-title">$title$</h1>
				<p class="product-tagline"><?=escapeHtml($product['tagline'])?></p>
				<p class="product-price"><span><?=tpl($product['price'])?></span><span class="badge badge--<?=escapeHtml($product['availabilityTone'])?>"><?=escapeHtml($product['availability'])?></span></p>
				<div class="product-body">
					$body$
				</div>
			</div>
		</article>
		<?php elseif($layout === 'category' && is_array($frontmatter['category'] ?? null)): ?>
		<nav class="crumbs" aria-label="Breadcrumb">
			<a href="<?=escapeHtml(resolveAssetUrl('/'))?>">Shop</a>
			<a href="<?=escapeHtml(resolveAssetUrl('/products/index.html'))?>">All products</a>
			<span aria-current="page">$title$</span>
		</nav>
		<header class="catalog-head">
			<p class="eyebrow">Category</p>
			<h1>$title$</h1>
			<p class="lede"><?=escapeHtml($frontmatter['description'] ?? '')?></p>
		</header>
		$body$
		<?php elseif($layout === 'catalog'): ?>
		<header class="catalog-head">
			<p class="eyebrow">Catalog</p>
			<h1>All products</h1>
			<p class="lede"><?=escapeHtml($frontmatter['catalog']['count'] ?? '')?> things made in small runs. When a batch sells out, the page says so.</p>
		</header>
		$body$
		<?php elseif($layout === 'home'): ?>
		$body$
		<?php else: ?>
		<article class="prose">
			$body$
		</article>
		<?php endif; ?>
	</main>

	<footer class="footer">
		<div class="footer-inner">
			<div class="footer-brand">
				<strong><?=escapeHtml(getConf('PRODUCT_NAME'))?></strong>
				<p><?=escapeHtml(getConf('TAGLINE'))?>. Orders ship from the harbour studio within two working days.</p>
			</div>
			<ul class="footer-links" aria-label="Shop">
				<li><a href="<?=escapeHtml(resolveAssetUrl('/products/index.html'))?>">All products</a></li>
				<li><a href="<?=escapeHtml(resolveAssetUrl('/about.html'))?>">Our story</a></li>
				<li><a href="<?=escapeHtml(resolveAssetUrl('/about.html#shipping-and-returns'))?>">Shipping and returns</a></li>
			</ul>
			<ul class="footer-links" aria-label="About this example">
				<li><a href="https://seanmorris.github.io/smgen-plugins/store.html">Built with smgen-store</a></li>
				<li><a href="https://seanmorris.github.io/smgen-plugins/data-pages.html">smgen-data-pages</a></li>
				<li><a href="https://seanmorris.github.io/smgen-plugins/categories.html">smgen-categories</a></li>
				<li><a href="https://github.com/seanmorris/smgen-plugins/tree/main/examples/store">View the source</a></li>
			</ul>
		</div>
		<p class="footer-note">A fictional shop. Checkout links point at example.com and charge nothing.</p>
	</footer>
</body>
</html>
