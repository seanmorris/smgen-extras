<?php

/**
 * Renders the Greywater Range guide as a trailhead map board: an aerial hero,
 * routed wood sign boards, and trail-blaze grading, with a legend menu.
 */

$layout = $frontmatter['layout'] ?? 'page';
$trail = $frontmatter['trail'] ?? null;
$camp = $frontmatter['camp'] ?? null;
$home = $frontmatter['home'] ?? null;
$index = $frontmatter['index'] ?? null;
$source = preg_replace('#^.*?/pages/(?:\.smgen-generated/[^/]+/)?#', '', (string) $pageFile) ?? '';
$section = explode('/', $source)[0];

/** Escape text for the page body and double dollars so Pandoc treats them literally. */
function tpl(string $value): string
{
	return str_replace('$', '$$', escapeHtml($value));
}

?><!DOCTYPE html>
<html lang="en" class="<?=escapeHtml(getConf('DEFAULT_THEME') ?: 'theme-guide')?>">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>$if(pagetitle)$${pagetitle}$else$${title}$endif$$if(title-prefix)$ · ${title-prefix}$endif$</title>
$if(description-meta)$
	<meta name="description" content="$description-meta$">
$endif$
	<meta name="theme-color" content="#1d3b2f">
	<link rel="icon" type="image/svg+xml" href="<?=escapeHtml(resolveAssetUrl('/mark.svg'))?>">
<?php renderJsonLd($frontmatter, $pageFile); ?>
	<style>$styles.html()$</style>
	<link rel="preload" as="font" type="font/woff2" crossorigin href="<?=escapeHtml(resolveAssetUrl('/fonts/barlow-400.woff2'))?>">
	<link rel="preload" as="font" type="font/woff2" crossorigin href="<?=escapeHtml(resolveAssetUrl('/fonts/barlow-700.woff2'))?>">
	<link rel="preload" as="font" type="font/woff2" crossorigin href="<?=escapeHtml(resolveAssetUrl('/fonts/barlow-condensed-600.woff2'))?>">
	<link rel="preload" as="font" type="font/woff2" crossorigin href="<?=escapeHtml(resolveAssetUrl('/fonts/barlow-condensed-700.woff2'))?>">
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
		<div class="topbar-inner">
			<a class="brand" href="<?=escapeHtml(resolveAssetUrl('/'))?>">
				<img src="<?=escapeHtml(resolveAssetUrl('/mark.svg'))?>" width="34" height="34" alt="">
				<span><strong><?=escapeHtml(getConf('PRODUCT_NAME'))?></strong><small><?=escapeHtml(getConf('TAGLINE'))?></small></span>
			</a>
			<nav class="topnav" aria-label="Sections">
				<a href="<?=escapeHtml(resolveAssetUrl('/trails/index.html'))?>"<?=$section === 'trails' ? ' aria-current="page"' : ''?>>Trails</a>
				<a href="<?=escapeHtml(resolveAssetUrl('/campgrounds/index.html'))?>"<?=$section === 'campgrounds' ? ' aria-current="page"' : ''?>>Campgrounds</a>
				<a href="<?=escapeHtml(resolveAssetUrl('/about.html'))?>"<?=$source === 'about.md' ? ' aria-current="page"' : ''?>>About</a>
			</nav>
				<button type="button" class="theme-toggle" data-theme-toggle aria-label="Switch to light theme" aria-pressed="false">
				<svg class="theme-icon theme-icon--moon" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path d="M20.5 14.2A8.5 8.5 0 0 1 9.8 3.5a8.5 8.5 0 1 0 10.7 10.7z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
				<svg class="theme-icon theme-icon--sun" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M12 2.5v2.5M12 19v2.5M2.5 12H5M19 12h2.5M5.3 5.3l1.8 1.8M16.9 16.9l1.8 1.8M5.3 18.7l1.8-1.8M16.9 7.1l1.8-1.8" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
			</button>
		</div>
	</header>

	<?php if($layout === 'home' && is_array($home)): ?>
	<section class="hero">
		<img class="hero-photo" src="<?=escapeHtml(resolveAssetUrl('/images/hero-aerial.webp'))?>" srcset="<?=escapeHtml(resolveAssetUrl('/images/hero-aerial-1200.webp'))?> 1200w, <?=escapeHtml(resolveAssetUrl('/images/hero-aerial.webp'))?> 1942w" sizes="100vw" width="1942" height="809" alt="" fetchpriority="high" decoding="async">
		<div class="hero-inner">
			<div>
				<p class="eyebrow">Trailhead · a fictional range, a real build</p>
				<h1>Twenty trails. Four regions. One weekend is not enough.</h1>
				<p class="hero-lede">From a half-hour boardwalk across the estuary to a full-day traverse over the ridge, every trail in the Greywater Range is here with its distance, climb, and the honest version of how hard it is.</p>
				<p class="hero-actions"><a class="button" href="<?=escapeHtml(resolveAssetUrl('/trails/index.html'))?>">Browse the trails</a><a class="button button--ghost" href="<?=escapeHtml(resolveAssetUrl('/campgrounds/index.html'))?>">Find a campground</a></p>
				<p class="scale-bar" aria-hidden="true"><span>0</span><span>1</span><span>2</span><span>3</span><span>4 km</span></p>
			</div>
			<ul class="hero-stats" aria-label="Guide at a glance">
				<li><b><?=escapeHtml($home['trails'])?></b><span>trails</span></li>
				<li><b><?=escapeHtml($home['km'])?></b><span>kilometres</span></li>
				<li><b><?=escapeHtml($home['gain'])?> m</b><span>biggest climb</span></li>
				<li><b><?=escapeHtml($home['regions'])?></b><span>regions</span></li>
			</ul>
		</div>
	</section>
	<?php endif; ?>

	<div class="frame">
		<aside class="sidebar">
			<nav aria-label="Guide"><?php renderMenuRegion('sidebar'); ?></nav>
		</aside>
		<main id="content" class="content">
			<?php if($layout === 'trail' && is_array($trail)): ?>
			<article class="record">
				<header class="record-head">
					<p class="eyebrow"><a href="<?=escapeHtml(resolveAssetUrl('/trails/index.html#' . $trail['regionSlug']))?>"><?=escapeHtml($trail['region'])?></a><span class="pill pill--<?=escapeHtml($trail['difficulty'])?>"><?=escapeHtml($trail['difficultyLabel'])?></span></p>
					<h1>$title$</h1>
					<p class="lede"><?=escapeHtml($frontmatter['description'] ?? '')?></p>
				</header>
				<div class="glance">
					<?=$trail['profile']?>
					<ul class="tiles">
						<li><b><?=escapeHtml($trail['distance'])?><small>km</small></b><span>Distance</span></li>
						<li><b><?=escapeHtml($trail['gain'])?><small>m</small></b><span>Elevation gain</span></li>
						<li><b><?=escapeHtml($trail['duration'])?></b><span>Typical time</span></li>
						<li><b class="tiles-text"><?=escapeHtml($trail['routeType'])?></b><span>Route</span></li>
					</ul>
					<dl class="facts">
						<div><dt>Difficulty</dt><dd><span class="meter" aria-hidden="true"><i class="<?=$trail['difficultyRank'] >= 1 ? 'on' : ''?>"></i><i class="<?=$trail['difficultyRank'] >= 2 ? 'on' : ''?>"></i><i class="<?=$trail['difficultyRank'] >= 3 ? 'on' : ''?>"></i></span><?=escapeHtml($trail['difficultyMeaning'])?></dd></div>
						<div><dt>Dogs</dt><dd><?=escapeHtml($trail['dogs'])?></dd></div>
						<div><dt>Season</dt><dd><?=escapeHtml($trail['season'])?></dd></div>
						<div><dt>Trailhead</dt><dd><?=escapeHtml($trail['trailhead'])?></dd></div>
					</dl>
				</div>
				<div class="prose">
					$body$
				</div>
				<footer class="record-foot">
					<a href="<?=escapeHtml(resolveAssetUrl('/trails/index.html#' . $trail['regionSlug']))?>">More trails in <?=escapeHtml($trail['region'])?></a>
					<a href="<?=escapeHtml(resolveAssetUrl('/campgrounds/index.html#' . $trail['regionSlug']))?>">Campgrounds nearby</a>
				</footer>
			</article>
			<?php elseif($layout === 'campground' && is_array($camp)): ?>
			<article class="record">
				<header class="record-head">
					<p class="eyebrow"><a href="<?=escapeHtml(resolveAssetUrl('/campgrounds/index.html#' . $camp['regionSlug']))?>"><?=escapeHtml($camp['region'])?></a><span class="pill pill--neutral"><?=escapeHtml($camp['reservations'])?></span></p>
					<h1>$title$</h1>
					<p class="lede"><?=escapeHtml($frontmatter['description'] ?? '')?></p>
				</header>
				<div class="glance glance--camp">
					<ul class="tiles">
						<li><b><?=escapeHtml($camp['sites'])?></b><span>Sites</span></li>
						<li><b><?=escapeHtml($camp['elevation'])?><small>m</small></b><span>Elevation</span></li>
						<li><b><?=tpl($camp['fee'])?></b><span><?=$camp['fee'] === 'Free' ? 'No fee' : 'Per night'?></span></li>
						<li><b class="tiles-text"><?=escapeHtml($camp['season'])?></b><span>Open</span></li>
					</ul>
					<dl class="facts">
						<div><dt>Water</dt><dd><?=escapeHtml($camp['water'])?></dd></div>
						<div><dt>Toilets</dt><dd><?=escapeHtml($camp['toilets'])?></dd></div>
						<div><dt>Booking</dt><dd><?=escapeHtml($camp['reservations'])?></dd></div>
					</dl>
				</div>
				<div class="prose">
					$body$
				</div>
				<footer class="record-foot">
					<a href="<?=escapeHtml(resolveAssetUrl('/campgrounds/index.html'))?>">All campgrounds</a>
					<a href="<?=escapeHtml(resolveAssetUrl('/trails/index.html#' . $camp['regionSlug']))?>">Trails in <?=escapeHtml($camp['region'])?></a>
				</footer>
			</article>
			<?php elseif($layout === 'index' && is_array($index)): ?>
			<header class="index-head">
				<p class="eyebrow"><?=escapeHtml($index['kicker'])?></p>
				<h1>$title$</h1>
				<p class="lede"><?=escapeHtml($index['lede'])?></p>
			</header>
			<?php if(!empty($index['photo'])): ?>
			<figure class="index-photo"><img src="<?=escapeHtml(resolveAssetUrl('/images/' . $index['photo'] . '.webp'))?>" srcset="<?=escapeHtml(resolveAssetUrl('/images/' . $index['photo'] . '-800.webp'))?> 800w, <?=escapeHtml(resolveAssetUrl('/images/' . $index['photo'] . '.webp'))?> 1600w" sizes="(max-width: 44rem) 100vw, 70vw" width="1600" height="900" alt="<?=escapeHtml($index['photoAlt'] ?? '')?>" fetchpriority="high" decoding="async"></figure>
			<?php endif; ?>
			$body$
			<?php elseif($layout === 'home'): ?>
			$body$
			<?php else: ?>
			<article class="prose prose--page">
				$body$
			</article>
			<?php endif; ?>
		</main>
	</div>

	<footer class="footer">
		<p><strong><?=escapeHtml(getConf('PRODUCT_NAME'))?></strong> is a fictional place. Distances and conditions are invented. Real trails deserve a real guide.</p>
		<p class="footer-links"><a href="https://seanmorris.github.io/smgen-plugins/data-pages.html">Built with smgen-data-pages</a><a href="https://github.com/seanmorris/smgen-plugins/tree/main/examples/data-pages">View the source</a></p>
	</footer>
</body>
</html>
