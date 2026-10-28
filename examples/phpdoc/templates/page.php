<?php

/**
 * Renders the Postbox documentation: a navy index column, a routing bar, the
 * page content, and an "On this page" outline in a right-hand rail.
 */

$layout = $frontmatter['layout'] ?? 'doc';
$isApi = !empty($frontmatter['api-page']);
$source = preg_replace('#^.*?/pages/(?:\.smgen-generated/[^/]+/)?#', '', (string) $pageFile) ?? '';
$inApi = $isApi || str_starts_with($source, 'api/');
$inGuides = !$inApi && $layout !== 'landing';
$leftBarShow = $frontmatter['leftBarShow'] ?? true;
$sourceUrl = $frontmatter['source-url'] ?? null;
$version = '1.2.0';
$pageTitle = (string) ($frontmatter['title'] ?? '');
// Generated API pages carry their ancestor trail: the landing page, then each namespace.
$hasTrail = is_array($frontmatter['breadcrumbs'] ?? null);
$trail = $hasTrail ? array_slice($frontmatter['breadcrumbs'], 1) : [];
$leaf = str_contains($pageTitle, '\\') ? substr($pageTitle, strrpos($pageTitle, '\\') + 1) : $pageTitle;

?><!DOCTYPE html>
<html lang="en" class="<?=escapeHtml(getConf('DEFAULT_THEME') ?: 'theme-docs')?>">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>$if(pagetitle)$${pagetitle}$else$${title}$endif$$if(title-prefix)$ · ${title-prefix}$endif$</title>
$if(description-meta)$
	<meta name="description" content="$description-meta$">
$endif$
	<meta name="theme-color" content="#1b2a4a">
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
<body class="layout-<?=escapeHtml($layout)?><?=$isApi ? ' api-page' : ''?>">
	<a class="skip-link" href="#content">Skip to content</a>
	<header class="masthead">
		<div class="masthead-inner">
			<a class="brand" href="<?=escapeHtml(resolveAssetUrl('/'))?>">
				<img src="<?=escapeHtml(resolveAssetUrl('/mark.svg'))?>" width="34" height="34" alt="">
				<span><?=escapeHtml(getConf('PRODUCT_NAME'))?></span>
			</a>
			<nav class="tabs" aria-label="Sections">
				<a href="<?=escapeHtml(resolveAssetUrl('/guides/getting-started.html'))?>"<?=$inGuides ? ' aria-current="page"' : ''?>>Guides</a>
				<a href="<?=escapeHtml(resolveAssetUrl('/api/index.html'))?>"<?=$inApi ? ' aria-current="page"' : ''?>>API reference</a>
				<a href="https://github.com/seanmorris/smgen-plugins/tree/main/examples/phpdoc">Source</a>
			</nav>
			<span class="version">v<?=escapeHtml($version)?> · PHP 8.2</span>
			<button type="button" class="theme-toggle" data-theme-toggle aria-label="Switch to light theme" aria-pressed="false">
				<svg class="theme-icon theme-icon--moon" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path d="M20.5 14.2A8.5 8.5 0 0 1 9.8 3.5a8.5 8.5 0 1 0 10.7 10.7z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
				<svg class="theme-icon theme-icon--sun" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M12 2.5v2.5M12 19v2.5M2.5 12H5M19 12h2.5M5.3 5.3l1.8 1.8M16.9 16.9l1.8 1.8M5.3 18.7l1.8-1.8M16.9 7.1l1.8-1.8" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
			</button>
		</div>
	</header>

	<?php if($layout === 'landing'): ?>
	<section class="cover">
		<div class="cover-inner">
			<p class="postmark">Postbox · composed and delivered · since 2024</p>
			<h1>A mail library you can read in an afternoon.</h1>
			<p class="cover-lede"><?=escapeHtml($frontmatter['description'] ?? '')?> Every signature on these pages comes from the source on each build, not from a hand-kept copy.</p>
			<p class="cover-actions"><a class="button" href="<?=escapeHtml(resolveAssetUrl('/guides/getting-started.html'))?>">Start with the guide</a><a class="button button--outline" href="<?=escapeHtml(resolveAssetUrl('/api/index.html'))?>">Open the reference</a></p>
		</div>
	</section>
	<main id="content" class="landing prose">
		$body$
	</main>
	<?php else: ?>
	<div class="frame<?=$leftBarShow ? '' : ' frame--full'?>">
		<?php if($leftBarShow): ?>
		<aside class="index">
			<nav aria-label="Documentation index"><?php renderMenuRegion('sidebar'); ?></nav>
		</aside>
		<?php endif; ?>
		<main id="content" class="sheet">
			<p class="route" aria-label="Breadcrumb">
				<?php if($inApi && $hasTrail): ?>
				<a href="<?=escapeHtml(resolveAssetUrl('/api/index.html'))?>">api</a>
				<?php foreach($trail as $crumb): ?>
				<span class="route-sep">\</span><?php if(!empty($crumb['path'])): ?><a href="<?=escapeHtml(resolveAssetUrl((string) $crumb['path']))?>"><?=escapeHtml((string) ($crumb['title'] ?? ''))?></a><?php else: ?><span><?=escapeHtml((string) ($crumb['title'] ?? ''))?></span><?php endif; ?>
				<?php endforeach; ?>
				<span class="route-sep">\</span><strong><?=escapeHtml($leaf)?></strong>
				<?php elseif($inApi): ?>
				<a href="<?=escapeHtml(resolveAssetUrl('/api/index.html'))?>">api</a><span class="route-sep">/</span><strong><?=escapeHtml($pageTitle)?></strong>
				<?php else: ?>
				<a href="<?=escapeHtml(resolveAssetUrl('/guides/getting-started.html'))?>">guides</a><span class="route-sep">/</span><strong><?=escapeHtml($pageTitle)?></strong>
				<?php endif; ?>
			</p>
			<article class="prose<?=$isApi ? ' prose--api' : ''?>">
				<?php if($isApi): ?>
				<header class="api-head">
					<h1 class="api-title"><?=escapeHtml($pageTitle)?></h1>
					<?php if($sourceUrl): ?>
					<a class="source-link" href="<?=escapeHtml($sourceUrl)?>">View source<span aria-hidden="true"> ↗</span></a>
					<?php endif; ?>
				</header>
				<?php endif; ?>
				$body$
			</article>
			$if(toc)$
			<nav class="outline" aria-label="On this page">
				<p class="outline-label">On this page</p>
				${toc}
			</nav>
			$endif$
			<?php if($isApi): ?>
			<footer class="sheet-foot">
				<a href="<?=escapeHtml(resolveAssetUrl('/api/index.html'))?>">All declarations</a>
				<a href="https://github.com/seanmorris/smgen-plugins/tree/main/examples/phpdoc">Edit this page</a>
			</footer>
			<?php endif; ?>
		</main>
	</div>
	<?php endif; ?>

	<footer class="colophon">
		<div class="colophon-inner">
			<p><strong><?=escapeHtml(getConf('PRODUCT_NAME'))?></strong> is a fictional library written to show what <a href="https://seanmorris.github.io/smgen-plugins/phpdoc.html">smgen-phpdoc</a> generates from real PHP source.</p>
			<p class="colophon-links"><a href="<?=escapeHtml(resolveAssetUrl('/api/index.html'))?>">API reference</a><a href="https://github.com/seanmorris/smgen-plugins/tree/main/examples/phpdoc">View the source</a></p>
		</div>
	</footer>
</body>
</html>
