<?php

/**
 * Renders a page as plain semantic HTML: header with the site menu, main with
 * the article and breadcrumbs, an aside for the outline, and a footer. The
 * template adds no class names of its own; the only classes on the page come
 * from SMGen's menu and breadcrumb helpers.
 */

$leftBarShow = $frontmatter['leftBarShow'] ?? true;

?><!DOCTYPE html>
<html lang="en" class="<?=escapeHtml(getConf('DEFAULT_THEME') ?: 'theme-semantic')?>">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="color-scheme" content="light dark">
	<link rel="icon" href="data:,">
	<title>$if(pagetitle)$${pagetitle}$else$${title}$endif$$if(title-prefix)$ · ${title-prefix}$endif$</title>
$if(description-meta)$
	<meta name="description" content="$description-meta$">
$endif$
$for(author)$
	<meta name="author" content="$author.name$">
$endfor$
	<link rel="sitemap" href="<?=escapeHtml(resolveAssetUrl('/sitemap.xml'))?>">
<?php foreach(rssFeedDefinitions() as $rssFeed): ?>
	<link rel="alternate" type="application/rss+xml" title="<?=escapeHtml($rssFeed['title'])?>" href="<?=escapeHtml(resolveAssetUrl($rssFeed['path']))?>">
<?php endforeach; ?>
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
<body>
	<header>
		<p><a href="<?=escapeHtml(resolveAssetUrl('/'))?>"><strong><?=escapeHtml(getConf('PRODUCT_NAME'))?></strong></a><?php if(getConf('TAGLINE')): ?> <small><?=escapeHtml(getConf('TAGLINE'))?></small><?php endif; ?></p>
		<?php if($leftBarShow && menuRegionExists('header')): ?>
		<nav aria-label="Site">
			<?php renderMenuRegion('header'); ?>
		</nav>
		<?php endif; ?>
	</header>
	<main id="content">
		<article>
			<?php renderBreadcrumbs(['menu' => 'site']); ?>
			$body$
		</article>
		$if(toc)$
		<aside aria-label="On this page">
			<h2>On this page</h2>
			${toc}
		</aside>
		$endif$
	</main>
	<footer>
		<p><small><?=escapeHtml(getConf('PRODUCT_NAME'))?> is built with <a href="https://seanmorris.github.io/smgen/">SMGen</a> and the <a href="https://seanmorris.github.io/smgen-extras/themes.html#semantic">semantic theme</a>.</small></p>
	</footer>
</body>
</html>
