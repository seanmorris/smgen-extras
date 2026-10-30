<?php

/**
 * Renders the smgen-extras hub: one header navigation, a home layout, and guide pages with an outline.
 */

$layout = $frontmatter['layout'] ?? 'guide';
$currentSource = basename((string) $pageFile);
$navigation = [
	['source' => 'index.md', 'path' => '/', 'label' => 'Overview'],
	['source' => 'themes.md', 'path' => '/themes.html', 'label' => 'Themes'],
	['source' => 'phpdoc.md', 'path' => '/phpdoc.html', 'label' => 'PHPDoc'],
	['source' => 'jsdoc.md', 'path' => '/jsdoc.html', 'label' => 'JSDoc'],
	['source' => 'data-pages.md', 'path' => '/data-pages.html', 'label' => 'Data Pages'],
	['source' => 'categories.md', 'path' => '/categories.html', 'label' => 'Categories'],
	['source' => 'timeline.md', 'path' => '/timeline.html', 'label' => 'Timeline'],
	['source' => 'store.md', 'path' => '/store.html', 'label' => 'Store'],
	['source' => 'development.md', 'path' => '/development.html', 'label' => 'Development'],
];
$guide = $frontmatter['guide'] ?? null;

/**
 * Read every theme manifest in the workspace, sorted by name.
 *
 * @return array<int, array<string, mixed>>
 */
function hubThemes(): array
{
	$pagesDir = realpath(getConf('PAGES_DIR') ?: 'pages');
	$root = $pagesDir === false ? getcwd() : dirname($pagesDir);
	$themes = [];
	foreach(glob($root . '/themes/*/theme.json') ?: [] as $manifest)
	{
		$theme = json_decode((string) file_get_contents($manifest), true);
		if(is_array($theme) && isset($theme['name']))
		{
			$themes[] = $theme;
		}
	}
	usort($themes, static fn(array $a, array $b): int => strcmp((string) $a['name'], (string) $b['name']));
	return $themes;
}

$themes = $layout === 'themes' ? hubThemes() : [];

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
			<a class="nav-external" href="https://github.com/seanmorris/smgen-extras" target="_blank" rel="noopener noreferrer">GitHub</a>
			<button type="button" class="theme-toggle" data-theme-toggle aria-label="Switch to light theme" aria-pressed="false">
				<svg class="theme-icon theme-icon--moon" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path d="M20.5 14.2A8.5 8.5 0 0 1 9.8 3.5a8.5 8.5 0 1 0 10.7 10.7z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
				<svg class="theme-icon theme-icon--sun" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M12 2.5v2.5M12 19v2.5M2.5 12H5M19 12h2.5M5.3 5.3l1.8 1.8M16.9 16.9l1.8 1.8M5.3 18.7l1.8-1.8M16.9 7.1l1.8-1.8" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
			</button>
		</div>
	</header>

	<?php if($layout === 'home'): ?>
	<section class="hero">
		<div class="shell hero-inner">
			<p class="eyebrow"><span>6 plugins</span><span>5 themes</span><span>PHP 8.2</span><span>Node 20</span></p>
			<h1>Build steps, not applications.</h1>
			<p class="hero-lede">Six plugins that add PHP and JavaScript API references, pages from spreadsheets, category rollups, a dated archive, and a static storefront to an <a href="https://seanmorris.github.io/smgen/">SMGen</a> site, and five complete starter themes to build a new site from. Everything runs at build time and publishes ordinary files. Nothing on the published site needs a server.</p>
			<p class="hero-actions"><a class="button" href="<?=escapeHtml(resolveAssetUrl('/themes.html'))?>">Start from a theme</a><a class="button button--ghost" href="<?=escapeHtml(resolveAssetUrl('/phpdoc.html'))?>">Read the plugin guides</a></p>
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
			<?php if($themes): ?>
			<h2 id="the-themes">The themes</h2>
			<?php foreach($themes as $theme): $name = (string) $theme['name']; $demoUrl = resolveAssetUrl('/themes/' . $name . '/'); ?>
			<section class="theme" id="theme-<?=escapeHtml($name)?>" aria-labelledby="theme-<?=escapeHtml($name)?>-title">
				<div class="theme-head"><h3 id="theme-<?=escapeHtml($name)?>-title"><?=escapeHtml((string) $theme['title'])?></h3><span class="package"><?=escapeHtml((string) $theme['package'])?></span></div>
				<p class="theme-description"><?=escapeHtml((string) $theme['description'])?></p>
				<a class="theme-preview" href="<?=escapeHtml($demoUrl)?>" target="_blank" rel="noopener noreferrer"><img class="preview preview--dark" src="<?=escapeHtml(resolveAssetUrl('/previews/theme-' . $name . '-dark.png'))?>" width="900" height="585" alt="<?=escapeHtml((string) ($theme['demo']['productName'] ?? $theme['title']))?> home page, dark" loading="lazy"><img class="preview preview--light" src="<?=escapeHtml(resolveAssetUrl('/previews/theme-' . $name . '-light.png'))?>" width="900" height="585" alt="<?=escapeHtml((string) ($theme['demo']['productName'] ?? $theme['title']))?> home page, light" loading="lazy"></a>
				<div class="theme-columns">
					<div><h4>What it does</h4><ul><?php foreach($theme['features'] ?? [] as $feature): ?><li><?=escapeHtml((string) $feature)?></li><?php endforeach; ?></ul></div>
					<div><h4>Sample content</h4><dl class="theme-content"><?php foreach($theme['sampleContent'] ?? [] as $entry): ?><dt><code><?=escapeHtml((string) ($entry['path'] ?? ''))?></code></dt><dd><?=escapeHtml((string) ($entry['purpose'] ?? ''))?></dd><?php endforeach; ?></dl></div>
				</div>
				<?php if(!empty($theme['notes'])): ?>
				<ul class="theme-notes"><?php foreach($theme['notes'] as $note): ?><li><?=escapeHtml((string) $note)?></li><?php endforeach; ?></ul>
				<?php endif; ?>
				<pre class="theme-command"><code>npm create smgen-site@latest my-site -- --theme <?=escapeHtml($name)?></code></pre>
				<p class="theme-links"><a href="<?=escapeHtml($demoUrl)?>" target="_blank" rel="noopener noreferrer">Open the demo: <?=escapeHtml((string) ($theme['demo']['productName'] ?? $theme['title']))?></a><a href="https://github.com/seanmorris/smgen-extras/tree/main/themes/<?=escapeHtml($name)?>" target="_blank" rel="noopener noreferrer">Theme source</a><a href="https://www.npmjs.com/package/<?=escapeHtml((string) $theme['package'])?>" target="_blank" rel="noopener noreferrer">npm</a></p>
			</section>
			<?php endforeach; ?>
			<?php endif; ?>
		</article>
		$if(toc)$
		<aside class="outline" aria-label="On this page">
			<p class="outline-title">On this page</p>
			${toc}
			<?php if($themes): ?>
			<ul><li><a href="#the-themes">The themes</a><ul><?php foreach($themes as $theme): ?><li><a href="#theme-<?=escapeHtml((string) $theme['name'])?>"><?=escapeHtml((string) $theme['title'])?></a></li><?php endforeach; ?></ul></li></ul>
			<?php endif; ?>
			<?php if(is_array($guide)): ?>
			<p class="outline-links"><a href="<?=escapeHtml($guide['example'])?>" target="_blank" rel="noopener noreferrer">Open the example</a><a href="https://github.com/seanmorris/smgen-extras/tree/main/plugins/<?=escapeHtml($guide['package'])?>" target="_blank" rel="noopener noreferrer">Package source</a></p>
			<?php endif; ?>
		</aside>
		$endif$
	</main>
	<?php endif; ?>

	<footer class="footer">
		<div class="shell footer-inner">
			<p>&copy; <?=date('Y')?> <?=escapeHtml(getConf('ORGANIZATION'))?>. Apache-2.0.</p>
			<p class="footer-links"><a href="https://seanmorris.github.io/smgen/">SMGen</a><a href="https://github.com/seanmorris/smgen-extras">Source</a><a href="https://github.com/seanmorris/smgen-extras/issues">Issues</a><a href="<?=escapeHtml(resolveAssetUrl('/llms.txt'))?>">llms.txt</a></p>
		</div>
	</footer>
</body>
</html>
