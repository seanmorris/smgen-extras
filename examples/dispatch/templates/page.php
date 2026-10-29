<?php

/**
 * Renders Dispatch, the shared documentation desk for Postbox (PHP) and
 * Signalbox (JavaScript): a slate masthead, a ledger-paper sheet, a reference
 * index on the left, and an outline on the right. Each library carries its own
 * accent colour through the index, routes, and reference stamps.
 */

$layout = $frontmatter['layout'] ?? 'doc';
$isApi = !empty($frontmatter['api-page']);
$source = preg_replace('#^.*?/pages/(?:\.smgen-generated/[^/]+/)?#', '', (string) $pageFile) ?? '';
$libraries = [
	'postbox' => ['name' => 'Postbox', 'language' => 'PHP 8.2', 'version' => '1.2.0', 'plugin' => 'smgen-phpdoc', 'sep' => '\\'],
	'signalbox' => ['name' => 'Signalbox', 'language' => 'ES2022', 'version' => '0.8.0', 'plugin' => 'smgen-jsdoc', 'sep' => '/'],
];
$libraryKey = explode('/', $source)[0];
$library = $libraries[$libraryKey] ?? null;
$inApi = $library !== null && ($isApi || str_starts_with($source, $libraryKey . '/api/'));
$inGuides = $library !== null && !$inApi && $layout !== 'library';
$leftBarShow = $frontmatter['leftBarShow'] ?? true;
$pageTitle = (string) ($frontmatter['title'] ?? '');
// Generated API pages carry their ancestor trail: the landing page, then each namespace or module.
$hasTrail = is_array($frontmatter['breadcrumbs'] ?? null);
$trail = $hasTrail ? array_slice($frontmatter['breadcrumbs'], 1) : [];
$leaf = $library !== null && str_contains($pageTitle, $library['sep']) ? substr($pageTitle, strrpos($pageTitle, $library['sep']) + 1) : $pageTitle;
$libraryBase = $library !== null ? '/' . $libraryKey : '';

?><!DOCTYPE html>
<html lang="en" class="<?=escapeHtml(getConf('DEFAULT_THEME') ?: 'theme-dispatch')?>">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>$if(pagetitle)$${pagetitle}$else$${title}$endif$$if(title-prefix)$ · ${title-prefix}$endif$</title>
$if(description-meta)$
	<meta name="description" content="$description-meta$">
$endif$
	<meta name="theme-color" content="#1f262e">
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
<body class="layout-<?=escapeHtml($layout)?><?=$isApi ? ' api-page' : ''?><?=$library !== null ? ' lib-' . escapeHtml($libraryKey) : ''?>">
	<a class="skip-link" href="#content">Skip to content</a>
	<header class="masthead">
		<div class="masthead-inner">
			<a class="brand" href="<?=escapeHtml(resolveAssetUrl('/'))?>">
				<img src="<?=escapeHtml(resolveAssetUrl('/mark.svg'))?>" width="30" height="30" alt="">
				<span><?=escapeHtml(getConf('PRODUCT_NAME'))?></span>
			</a>
			<nav class="tabs" aria-label="Libraries">
				<?php foreach($libraries as $key => $entry): ?>
				<a class="tab tab--<?=escapeHtml($key)?>" href="<?=escapeHtml(resolveAssetUrl('/' . $key . '/'))?>"<?=$libraryKey === $key ? ' aria-current="page"' : ''?>><?=escapeHtml($entry['name'])?><small><?=escapeHtml($entry['language'])?></small></a>
				<?php endforeach; ?>
			</nav>
			<a class="masthead-source" href="https://github.com/seanmorris/smgen-plugins/tree/main/examples/dispatch" target="_blank" rel="noopener noreferrer">Source</a>
			<button type="button" class="theme-toggle" data-theme-toggle aria-label="Switch to light theme" aria-pressed="false">
				<svg class="theme-icon theme-icon--moon" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path d="M20.5 14.2A8.5 8.5 0 0 1 9.8 3.5a8.5 8.5 0 1 0 10.7 10.7z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
				<svg class="theme-icon theme-icon--sun" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M12 2.5v2.5M12 19v2.5M2.5 12H5M19 12h2.5M5.3 5.3l1.8 1.8M16.9 16.9l1.8 1.8M5.3 18.7l1.8-1.8M16.9 7.1l1.8-1.8" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
			</button>
		</div>
	</header>

	<?php if($layout === 'landing'): ?>
	<section class="cover">
		<div class="cover-inner">
			<p class="cover-kicker">Dispatch · developer documentation · issued on every build</p>
			<h1>Two libraries, one desk.</h1>
			<p class="cover-lede"><?=escapeHtml($frontmatter['description'] ?? '')?></p>
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
				<?php if($library !== null): ?>
				<a class="route-lib" href="<?=escapeHtml(resolveAssetUrl($libraryBase . '/'))?>"><?=escapeHtml($library['name'])?></a>
				<?php endif; ?>
				<?php if($inApi && $hasTrail): ?>
				<span class="route-sep">/</span><a href="<?=escapeHtml(resolveAssetUrl($libraryBase . '/api/index.html'))?>">api</a>
				<?php foreach($trail as $crumb): ?>
				<span class="route-sep"><?=escapeHtml($library['sep'])?></span><?php if(!empty($crumb['path'])): ?><a href="<?=escapeHtml(resolveAssetUrl((string) $crumb['path']))?>"><?=escapeHtml((string) ($crumb['title'] ?? ''))?></a><?php else: ?><span><?=escapeHtml((string) ($crumb['title'] ?? ''))?></span><?php endif; ?>
				<?php endforeach; ?>
				<span class="route-sep"><?=escapeHtml($library['sep'])?></span><strong><?=escapeHtml($leaf)?></strong>
				<?php elseif($inApi): ?>
				<span class="route-sep">/</span><a href="<?=escapeHtml(resolveAssetUrl($libraryBase . '/api/index.html'))?>">api</a><span class="route-sep">/</span><strong><?=escapeHtml($pageTitle)?></strong>
				<?php elseif($inGuides): ?>
				<span class="route-sep">/</span><a href="<?=escapeHtml(resolveAssetUrl($libraryBase . '/guides/getting-started.html'))?>">guides</a><span class="route-sep">/</span><strong><?=escapeHtml($pageTitle)?></strong>
				<?php elseif($library !== null): ?>
				<span class="route-sep">/</span><strong>overview</strong>
				<?php else: ?>
				<strong><?=escapeHtml($pageTitle)?></strong>
				<?php endif; ?>
				<?php if($library !== null): ?>
				<span class="route-meta"><?=escapeHtml($library['language'])?> · v<?=escapeHtml($library['version'])?></span>
				<?php endif; ?>
			</p>
			<?php if($layout === 'library' && $library !== null): ?>
			<header class="ticket">
				<p class="ticket-kicker"><?=escapeHtml($library['name'])?> · v<?=escapeHtml($library['version'])?> · <?=escapeHtml($library['language'])?></p>
				<h1><?=escapeHtml($frontmatter['description'] ?? $pageTitle)?></h1>
				<p class="ticket-actions"><a class="button" href="<?=escapeHtml(resolveAssetUrl($libraryBase . '/guides/getting-started.html'))?>">Start with the guide</a><a class="button button--outline" href="<?=escapeHtml(resolveAssetUrl($libraryBase . '/api/index.html'))?>">Open the reference</a></p>
			</header>
			<?php endif; ?>
			<article class="prose<?=$isApi ? ' prose--api' : ''?><?=$layout === 'library' ? ' prose--library' : ''?>">
				<?php if($isApi): ?>
				<header class="api-head">
					<h1 class="api-title"><?=escapeHtml($pageTitle)?></h1>
					<?php if(!empty($frontmatter['source-url'])): ?>
					<a class="source-link" href="<?=escapeHtml((string) $frontmatter['source-url'])?>" target="_blank" rel="noopener noreferrer">View source<span aria-hidden="true"> ↗</span></a>
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
			<?php if($isApi && $library !== null): ?>
			<footer class="sheet-foot">
				<a href="<?=escapeHtml(resolveAssetUrl($libraryBase . '/api/index.html'))?>">All <?=escapeHtml($library['name'])?> declarations</a>
				<a href="https://github.com/seanmorris/smgen-plugins/tree/main/examples/dispatch" target="_blank" rel="noopener noreferrer">Edit this page</a>
			</footer>
			<?php endif; ?>
		</main>
	</div>
	<?php endif; ?>

	<footer class="colophon">
		<div class="colophon-inner">
			<p><strong>Postbox</strong> and <strong>Signalbox</strong> are fictional libraries written to show what <a href="https://seanmorris.github.io/smgen-plugins/phpdoc.html" target="_blank" rel="noopener noreferrer">smgen-phpdoc</a> and <a href="https://seanmorris.github.io/smgen-plugins/jsdoc.html" target="_blank" rel="noopener noreferrer">smgen-jsdoc</a> generate from real source.</p>
			<p class="colophon-links"><a href="<?=escapeHtml(resolveAssetUrl('/postbox/api/index.html'))?>">Postbox API</a><a href="<?=escapeHtml(resolveAssetUrl('/signalbox/api/index.html'))?>">Signalbox API</a><a href="https://github.com/seanmorris/smgen-plugins/tree/main/examples/dispatch" target="_blank" rel="noopener noreferrer">View the source</a></p>
		</div>
	</footer>
</body>
</html>
