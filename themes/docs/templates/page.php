<?php

/**
 * Renders Kestrel's manual: a masthead with search and a version badge, the
 * contents tree from SMGen's menu, the article with breadcrumbs, and the
 * outline from the Pandoc table of contents. Callouts come from fenced divs
 * (::: note, ::: tip, ::: warning) in the Markdown.
 */

$leftBarShow = $frontmatter['leftBarShow'] ?? true;
$source = preg_replace('#^.*?/pages/#', '', (string) $pageFile) ?? '';
$version = (string) (getConf('DOCS_VERSION') ?: '');
$repository = (string) (getConf('DOCS_REPOSITORY') ?: '');
$searchOn = (string) getConf('SEARCH_ENABLED') === '1';
$editUrl = $repository !== '' ? rtrim($repository, '/') . '/pages/' . $source : '';

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
				<img src="<?=escapeHtml(resolveAssetUrl('/mark.svg'))?>" width="28" height="28" alt="">
				<span><?=escapeHtml(getConf('PRODUCT_NAME'))?></span>
				<?php if($version !== ''): ?><small class="version"><?=escapeHtml($version)?></small><?php endif; ?>
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
					<svg class="theme-icon theme-icon--moon" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path d="M20.5 14.2A8.5 8.5 0 0 1 9.8 3.5a8.5 8.5 0 1 0 10.7 10.7z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
					<svg class="theme-icon theme-icon--sun" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M12 2.5v2.5M12 19v2.5M2.5 12H5M19 12h2.5M5.3 5.3l1.8 1.8M16.9 16.9l1.8 1.8M5.3 18.7l1.8-1.8M16.9 7.1l1.8-1.8" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
				</button>
			</nav>
		</div>
	</header>

	<div class="frame<?=$leftBarShow ? '' : ' frame--full'?>">
		<?php if($leftBarShow): ?>
		<aside class="contents">
			<nav aria-label="Contents"><?php renderMenuRegion('sidebar'); ?></nav>
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
			$if(toc)$
			<nav class="outline" aria-label="On this page">
				<p class="outline-label">On this page</p>
				${toc}
			</nav>
			$endif$
		</main>
	</div>

	<footer class="colophon">
		<p><?=escapeHtml(getConf('PRODUCT_NAME'))?> documentation<?php if($version !== ''): ?>, <?=escapeHtml($version)?><?php endif; ?>. Built with <a href="https://seanmorris.github.io/smgen/">SMGen</a> and the <a href="https://seanmorris.github.io/smgen-extras/themes.html#docs">docs theme</a>.</p>
	</footer>
</body>
</html>
