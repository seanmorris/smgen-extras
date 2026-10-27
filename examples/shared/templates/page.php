<?php

/**
 * Renders the lightweight shell shared by the published plugin examples.
 */

$menuRegions = getConf('MENU_REGIONS') ?: [];
$leftBarShow = is_array($menuRegions)
	&& array_key_exists('sidebar', $menuRegions)
	&& ($frontmatter['leftBarShow'] ?? true);

?><!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>$if(title-prefix)$${title-prefix} | $endif$$if(pagetitle)$${pagetitle}$else$${title}$endif$</title>
$if(description-meta)$
	<meta name="description" content="$description-meta$">
$endif$
	<link rel="icon" type="image/svg+xml" href="<?=escapeHtml(resolveAssetUrl('/example-mark.svg'))?>">
<?php renderJsonLd($frontmatter, $pageFile); ?>
	<style>$styles.html()$</style>
$for(css)$
	<link rel="stylesheet" href="${css}">
$endfor$
</head>
<body<?=$leftBarShow ? ' class="example-with-sidebar"' : ''?>>
	<header class="example-header">
		<a class="example-brand" href="<?=escapeHtml(resolveAssetUrl('/'))?>">
			<img src="<?=escapeHtml(resolveAssetUrl('/example-mark.svg'))?>" width="44" height="44" alt="">
			<span>
				<strong><?=escapeHtml(getConf('PRODUCT_NAME'))?></strong>
				<small><?=escapeHtml(getConf('TAGLINE'))?></small>
			</span>
		</a>
		<a href="https://seanmorris.github.io/smgen-plugins/" target="_blank" rel="noopener noreferrer">Plugin guides</a>
	</header>
	<div class="example-layout<?=$leftBarShow ? ' example-layout--sidebar' : ''?>">
		<?php if($leftBarShow): ?>
		<aside class="example-sidebar">
			<nav aria-label="Site navigation"><?php renderMenuRegion('sidebar'); ?></nav>
		</aside>
		<?php endif; ?>
		<main>
			<article>
				$body$
			</article>
			$if(toc)$
			<nav class="example-toc" aria-label="On this page">${toc}</nav>
			$endif$
		</main>
	</div>
	<footer>
		<span>Example site</span>
		<a href="https://github.com/seanmorris/smgen-plugins" target="_blank" rel="noopener noreferrer">View source</a>
	</footer>
</body>
</html>
