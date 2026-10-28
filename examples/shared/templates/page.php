<?php

/**
 * Renders the lightweight shell shared by the published plugin examples.
 */

$menuRegions = getConf('MENU_REGIONS') ?: [];
$leftBarShow = is_array($menuRegions)
	&& array_key_exists('sidebar', $menuRegions)
	&& ($frontmatter['leftBarShow'] ?? true);
$exampleThemes = [
	'theme-phpdoc' => [
		'color' => '#007a73',
		'mark' => '/example-mark-phpdoc.svg',
		'guide' => 'https://seanmorris.github.io/smgen-plugins/phpdoc.html',
	],
	'theme-data-pages' => [
		'color' => '#6545d3',
		'mark' => '/example-mark-data-pages.svg',
		'guide' => 'https://seanmorris.github.io/smgen-plugins/data-pages.html',
	],
	'theme-store' => [
		'color' => '#a94f08',
		'mark' => '/example-mark-store.svg',
		'guide' => 'https://seanmorris.github.io/smgen-plugins/store.html',
	],
	'theme-blog' => [
		'color' => '#2f6e5e',
		'mark' => '/example-mark-blog.svg',
		'guide' => 'https://seanmorris.github.io/smgen-plugins/data-pages.html',
	],
];
$exampleTheme = getConf('DEFAULT_THEME') ?: 'theme-data-pages';
$theme = $exampleThemes[$exampleTheme] ?? $exampleThemes['theme-data-pages'];

?><!DOCTYPE html>
<html lang="en" class="<?=escapeHtml($exampleTheme)?>">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>$if(title-prefix)$${title-prefix} | $endif$$if(pagetitle)$${pagetitle}$else$${title}$endif$</title>
$if(description-meta)$
	<meta name="description" content="$description-meta$">
$endif$
	<meta name="theme-color" content="<?=escapeHtml($theme['color'])?>">
	<link rel="icon" type="image/svg+xml" href="<?=escapeHtml(resolveAssetUrl($theme['mark']))?>">
<?php renderJsonLd($frontmatter, $pageFile); ?>
<?php foreach(rssFeedDefinitions() as $rssFeed): ?>
	<link rel="alternate" type="application/rss+xml" title="<?=escapeHtml($rssFeed['title'])?>" href="<?=escapeHtml(resolveAssetUrl($rssFeed['path']))?>">
<?php endforeach; ?>
	<style>$styles.html()$</style>
$for(css)$
	<link rel="stylesheet" href="${css}">
$endfor$
</head>
<body<?=$leftBarShow ? ' class="example-with-sidebar"' : ''?>>
	<a class="skip-link" href="#content">Skip to content</a>
	<header class="example-header">
		<div class="example-shell example-header-inner">
			<a class="example-brand" href="<?=escapeHtml(resolveAssetUrl('/'))?>">
				<img src="<?=escapeHtml(resolveAssetUrl($theme['mark']))?>" width="44" height="44" alt="">
				<span>
					<strong><?=escapeHtml(getConf('PRODUCT_NAME'))?></strong>
					<small><?=escapeHtml(getConf('TAGLINE'))?></small>
				</span>
			</a>
			<nav class="example-header-links" aria-label="Related links">
				<a href="<?=escapeHtml($theme['guide'])?>" target="_blank" rel="noopener noreferrer">Plugin guide</a>
				<a href="https://github.com/seanmorris/smgen-plugins" target="_blank" rel="noopener noreferrer">GitHub</a>
			</nav>
		</div>
	</header>
	<div class="example-shell example-layout<?=$leftBarShow ? ' example-layout--sidebar' : ''?>">
		<?php if($leftBarShow): ?>
		<aside class="example-sidebar">
			<nav aria-label="Site navigation"><?php renderMenuRegion('sidebar'); ?></nav>
		</aside>
		<?php endif; ?>
		<main id="content"$if(toc)$ class="example-content--with-toc"$endif$>
			<article>
				$body$
			</article>
			$if(toc)$
			<nav class="example-toc" aria-label="On this page">
				<strong>On this page</strong>
				${toc}
			</nav>
			$endif$
		</main>
	</div>
	<footer class="example-footer">
		<div class="example-shell example-footer-inner">
			<span>Example site</span>
			<a href="https://github.com/seanmorris/smgen-plugins" target="_blank" rel="noopener noreferrer">View source</a>
		</div>
	</footer>
</body>
</html>
