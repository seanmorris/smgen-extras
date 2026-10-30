<?php

/**
 * Renders a reference manual: a masthead with the product, edition and
 * search; a contents column holding SMGen's tree menu and the page's
 * outline; the article with breadcrumbs and numbered sections; and a margin
 * on the right where notes, tips and warnings (fenced divs in the Markdown)
 * sit beside the paragraph they belong to. Nothing here names a product.
 */

$leftBarShow = $frontmatter['leftBarShow'] ?? true;
$source = preg_replace('#^.*?/pages/#', '', (string) $pageFile) ?? '';
$version = (string) (getConf('DOCS_VERSION') ?: '');
$repository = (string) (getConf('DOCS_REPOSITORY') ?: '');
$searchOn = (string) getConf('SEARCH_ENABLED') === '1';
$editUrl = $repository !== '' ? rtrim($repository, '/') . '/pages/' . $source : '';
$edition = (string) (getConf('TAGLINE') ?: 'Reference manual');

?><!DOCTYPE html>
<html lang="en" class="<?=escapeHtml(getConf('DEFAULT_THEME') ?: 'theme-docs')?>">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="smgen-base-url" content="<?=escapeHtml(getConf('BASE_URL'))?>">
	<title>$if(pagetitle)$${pagetitle}$else$${title}$endif$$if(title-prefix)$ · ${title-prefix}$endif$</title>
$if(description-meta)$
	<meta name="description" content="$description-meta$">
$endif$
	<link rel="icon" type="image/svg+xml" href="<?=escapeHtml(resolveAssetUrl('/mark.svg'))?>">
	<link rel="sitemap" href="<?=escapeHtml(resolveAssetUrl('/sitemap.xml'))?>">
<?php if(llmsEnabled()): ?>
	<link rel="describedby" type="text/markdown" href="<?=escapeHtml(resolveAssetUrl('/llms.txt'))?>">
<?php endif; ?>
<?php foreach(['ibm-plex-sans-variable', 'ibm-plex-mono-400'] as $font): ?>
	<link rel="preload" href="<?=escapeHtml(resolveAssetUrl('/fonts/' . $font . '.woff2'))?>" as="font" type="font/woff2" crossorigin>
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
	<a class="skip-link" href="#content">Skip to content</a>
	<header class="masthead">
		<div class="masthead-inner">
			<a class="brand" href="<?=escapeHtml(resolveAssetUrl('/'))?>">
				<img src="<?=escapeHtml(resolveAssetUrl('/mark.svg'))?>" width="26" height="26" alt="">
				<span class="brand-name"><?=escapeHtml(getConf('PRODUCT_NAME'))?></span>
				<span class="brand-edition"><?=escapeHtml($edition)?><?php if($version !== ''): ?> · <?=escapeHtml($version)?><?php endif; ?></span>
			</a>
			<?php if($searchOn): ?>
			<div class="search">
				<input id="search-query" type="search" placeholder="Search the manual" aria-label="Search" aria-controls="search-results" autocomplete="off" data-search-index="<?=escapeHtml(resolveAssetUrl('/search.bin'))?>" data-search-reader="<?=escapeHtml(resolveAssetUrl(getConf('SEARCH_READER')))?>" data-search-results="#search-results">
				<ul class="search-menu" id="search-results" aria-live="polite"></ul>
			</div>
			<?php endif; ?>
			<nav class="masthead-links" aria-label="Site">
				<a href="<?=escapeHtml(resolveAssetUrl('/changelog.html'))?>"<?=$source === 'changelog.md' ? ' aria-current="page"' : ''?>>Changelog</a>
				<?php if($repository !== ''): ?><a href="<?=escapeHtml($repository)?>" target="_blank" rel="noopener noreferrer">Source</a><?php endif; ?>
				<button type="button" class="theme-toggle" data-theme-toggle aria-label="Switch to dark theme" aria-pressed="false">
					<svg class="theme-icon theme-icon--moon" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" focusable="false"><path d="M20.5 14.2A8.5 8.5 0 0 1 9.8 3.5a8.5 8.5 0 1 0 10.7 10.7z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
					<svg class="theme-icon theme-icon--sun" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M12 2.5v2.5M12 19v2.5M2.5 12H5M19 12h2.5M5.3 5.3l1.8 1.8M16.9 16.9l1.8 1.8M5.3 18.7l1.8-1.8M16.9 7.1l1.8-1.8" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
				</button>
			</nav>
		</div>
	</header>

	<div class="frame<?=$leftBarShow ? '' : ' frame--full'?>">
		<?php if($leftBarShow): ?>
		<aside class="contents">
			<details class="contents-panel" open>
				<summary>Contents</summary>
				<nav aria-label="Contents"><?php renderMenuRegion('sidebar'); ?></nav>
				$if(toc)$
				<nav class="outline" aria-label="On this page">
					<p class="outline-label">On this page</p>
					${toc}
				</nav>
				$endif$
			</details>
		</aside>
		<?php endif; ?>
		<main id="content" class="sheet">
			<article class="prose">
				<?php renderBreadcrumbs(); ?>
				$body$
				<?php if($editUrl !== ''): ?>
				<footer class="article-foot">
					<a href="<?=escapeHtml($editUrl)?>" target="_blank" rel="noopener noreferrer">Edit this page</a>
				</footer>
				<?php endif; ?>
			</article>
		</main>
	</div>

	<footer class="colophon">
		<p><?=escapeHtml(getConf('PRODUCT_NAME'))?> <?=escapeHtml(strtolower($edition))?><?php if($version !== ''): ?>, <?=escapeHtml($version)?><?php endif; ?>. Set in IBM Plex Sans and Mono; built with <a href="https://seanmorris.github.io/smgen/">SMGen</a> and the <a href="https://seanmorris.github.io/smgen-extras/themes.html#theme-docs">docs theme</a>.</p>
	</footer>
</body>
</html>
