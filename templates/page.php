<?php

/**
 * Renders the documentation shell for the SMGen plugin collection.
 */

$leftBarShow = $frontmatter['leftBarShow'] ?? true;
$currentSource = basename($pageFile);
$sectionLinks = [
	['source' => 'index.md', 'path' => '/', 'label' => 'Overview'],
	['source' => 'phpdoc.md', 'path' => '/phpdoc.html', 'label' => 'PHPDoc'],
	['source' => 'data-pages.md', 'path' => '/data-pages.html', 'label' => 'Data Pages'],
	['source' => 'store.md', 'path' => '/store.html', 'label' => 'Store'],
];

?><!DOCTYPE html>
<html lang="en" class="theme-plugins">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="smgen-base-url" content="<?=escapeHtml(getConf('BASE_URL'))?>">
	<title>$if(title-prefix)$${title-prefix} | $endif$$if(pagetitle)$${pagetitle}$else$${title}$endif$</title>
$if(description-meta)$
	<meta name="description" content="$description-meta$">
$endif$
	<meta name="theme-color" content="#7657e8">
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
</head>
<body>
	<a class="skip-link" href="#content">Skip to content</a>
	<header class="site-header">
		<div class="shell header-inner">
			<a class="brand" href="<?=escapeHtml(resolveAssetUrl('/'))?>">
				<img src="<?=escapeHtml(resolveAssetUrl('/logo.svg'))?>" width="64" height="64" alt="">
				<span>
					<strong><?=escapeHtml(getConf('PRODUCT_NAME'))?></strong>
					<small><?=escapeHtml(getConf('TAGLINE'))?></small>
				</span>
			</a>
			<div class="header-main">
				<nav class="section-links" aria-label="Plugin guides">
					<?php foreach($sectionLinks as $section): ?>
					<a href="<?=escapeHtml(resolveAssetUrl($section['path']))?>"<?=$currentSource === $section['source'] ? ' class="active-link" aria-current="page"' : ''?>><?=escapeHtml($section['label'])?></a>
					<?php endforeach; ?>
				</nav>
				<nav class="header-links" aria-label="Related sites">
					<a href="https://seanmorris.github.io/smgen/" target="_blank" rel="noopener noreferrer">SMGen</a>
					<a href="https://github.com/seanmorris/smgen-plugins" target="_blank" rel="noopener noreferrer">GitHub</a>
				</nav>
			</div>
		</div>
	</header>
	<div class="shell site-layout<?=!$leftBarShow ? ' no-sidebar' : ''?>">
		<?php if($leftBarShow): ?>
		<aside class="sidebar">
			<nav class="main" aria-label="Plugin documentation"><?php renderNavBar(); ?></nav>
		</aside>
		<?php endif; ?>
		<main id="content" class="page-content">
			<article>
				$body$
			</article>
			$if(toc)$
			<nav class="table-of-contents" aria-label="On this page">
				<strong>On this page</strong>
				${toc}
			</nav>
			$endif$
		</main>
	</div>
	<footer class="site-footer">
		<div class="shell footer-inner">
			<span>&copy; <?=date('Y')?> <?=escapeHtml(getConf('ORGANIZATION'))?></span>
			<span>Built with <a href="https://seanmorris.github.io/smgen/" target="_blank" rel="noopener noreferrer">SMGen</a></span>
		</div>
	</footer>
</body>
</html>
