<?php

/**
 * Renders the smgen-plugins hub: one header navigation, a home layout, and guide pages with an outline.
 */

$layout = $frontmatter['layout'] ?? 'guide';
$currentSource = basename((string) $pageFile);
$navigation = [
	['source' => 'index.md', 'path' => '/', 'label' => 'Overview'],
	['source' => 'phpdoc.md', 'path' => '/phpdoc.html', 'label' => 'PHPDoc'],
	['source' => 'jsdoc.md', 'path' => '/jsdoc.html', 'label' => 'JSDoc'],
	['source' => 'data-pages.md', 'path' => '/data-pages.html', 'label' => 'Data Pages'],
	['source' => 'categories.md', 'path' => '/categories.html', 'label' => 'Categories'],
	['source' => 'timeline.md', 'path' => '/timeline.html', 'label' => 'Timeline'],
	['source' => 'store.md', 'path' => '/store.html', 'label' => 'Store'],
	['source' => 'development.md', 'path' => '/development.html', 'label' => 'Development'],
];
$guide = $frontmatter['guide'] ?? null;

?><!DOCTYPE html>
<html lang="en" class="theme-plugins">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="smgen-base-url" content="<?=escapeHtml(getConf('BASE_URL'))?>">
	<title>$if(pagetitle)$${pagetitle}$else$${title}$endif$$if(title-prefix)$ · ${title-prefix}$endif$</title>
$if(description-meta)$
	<meta name="description" content="$description-meta$">
$endif$
	<meta name="theme-color" content="#1f3fd6">
	<link rel="icon" type="image/svg+xml" href="<?=escapeHtml(resolveAssetUrl('/logo.svg'))?>">
	<link rel="sitemap" href="<?=escapeHtml(resolveAssetUrl('/sitemap.xml'))?>">
<?php if(llmsEnabled()): ?>
	<link rel="describedby" type="text/markdown" href="<?=escapeHtml(resolveAssetUrl('/llms.txt'))?>">
<?php endif; ?>
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
	<a class="skip-link" href="#content">Skip to content</a>
	<header class="topbar">
		<div class="shell topbar-inner">
			<a class="brand" href="<?=escapeHtml(resolveAssetUrl('/'))?>">
				<img src="<?=escapeHtml(resolveAssetUrl('/logo.svg'))?>" width="32" height="32" alt="">
				<span><?=escapeHtml(getConf('PRODUCT_NAME'))?></span>
			</a>
			<nav class="nav" aria-label="Site">
				<?php foreach($navigation as $link): ?>
				<a href="<?=escapeHtml(resolveAssetUrl($link['path']))?>"<?=$currentSource === $link['source'] ? ' aria-current="page"' : ''?>><?=escapeHtml($link['label'])?></a>
				<?php endforeach; ?>
			</nav>
			<a class="nav-external" href="https://github.com/seanmorris/smgen-plugins" target="_blank" rel="noopener noreferrer">GitHub</a>
			<button type="button" class="theme-toggle" data-theme-toggle aria-label="Switch to light theme" aria-pressed="false">
				<svg class="theme-icon theme-icon--moon" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path d="M20.5 14.2A8.5 8.5 0 0 1 9.8 3.5a8.5 8.5 0 1 0 10.7 10.7z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
				<svg class="theme-icon theme-icon--sun" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M12 2.5v2.5M12 19v2.5M2.5 12H5M19 12h2.5M5.3 5.3l1.8 1.8M16.9 16.9l1.8 1.8M5.3 18.7l1.8-1.8M16.9 7.1l1.8-1.8" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
			</button>
		</div>
	</header>

	<?php if($layout === 'home'): ?>
	<section class="hero">
		<div class="shell hero-inner">
			<p class="eyebrow"><span>smgen/v1 plugin API</span><span>PHP 8.2</span><span>Node 20</span></p>
			<h1>Build steps, not applications.</h1>
			<p class="hero-lede">Six plugins that add PHP and JavaScript API references, pages from spreadsheets, category rollups, a dated archive, and a static storefront to an <a href="https://seanmorris.github.io/smgen/">SMGen</a> site. Each one runs at build time and publishes ordinary files. Nothing on the published site needs a server.</p>
			<p class="hero-actions"><a class="button" href="<?=escapeHtml(resolveAssetUrl('/phpdoc.html'))?>">Read the guides</a><a class="button button--ghost" href="#examples">See the examples</a></p>
		</div>
	</section>
	<main id="content" class="shell home">
		$body$
	</main>
	<?php else: ?>
	<main id="content" class="shell guide<?=$layout === 'plain' ? ' guide--plain' : ''?>">
		<article class="prose">
			<?php if(is_array($guide)): ?>
			<p class="eyebrow"><span>Plugin guide</span><span class="package"><?=escapeHtml($guide['package'])?></span></p>
			<?php endif; ?>
			$body$
		</article>
		$if(toc)$
		<aside class="outline" aria-label="On this page">
			<p class="outline-title">On this page</p>
			${toc}
			<?php if(is_array($guide)): ?>
			<p class="outline-links"><a href="<?=escapeHtml($guide['example'])?>" target="_blank" rel="noopener noreferrer">Open the example</a><a href="https://github.com/seanmorris/smgen-plugins/tree/main/packages/<?=escapeHtml($guide['package'])?>" target="_blank" rel="noopener noreferrer">Package source</a></p>
			<?php endif; ?>
		</aside>
		$endif$
	</main>
	<?php endif; ?>

	<footer class="footer">
		<div class="shell footer-inner">
			<p>&copy; <?=date('Y')?> <?=escapeHtml(getConf('ORGANIZATION'))?>. Apache-2.0.</p>
			<p class="footer-links"><a href="https://seanmorris.github.io/smgen/">SMGen</a><a href="https://github.com/seanmorris/smgen-plugins">Source</a><a href="https://github.com/seanmorris/smgen-plugins/issues">Issues</a><a href="<?=escapeHtml(resolveAssetUrl('/llms.txt'))?>">llms.txt</a></p>
		</div>
	</footer>
</body>
</html>
