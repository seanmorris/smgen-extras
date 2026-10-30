<?php

/**
 * Renders Tidewatch's landing page from the front page's frontmatter (hero,
 * features, plans, faq), with the Markdown body as an optional closing
 * section, and plain prose pages for everything else.
 */

$layout = $frontmatter['layout'] ?? 'page';
$source = preg_replace('#^.*?/pages/#', '', (string) $pageFile) ?? '';
$hero = is_array($frontmatter['hero'] ?? null) ? $frontmatter['hero'] : [];
$features = is_array($frontmatter['features'] ?? null) ? $frontmatter['features'] : [];
$plans = is_array($frontmatter['plans'] ?? null) ? $frontmatter['plans'] : [];
$faq = is_array($frontmatter['faq'] ?? null) ? $frontmatter['faq'] : [];
$navigation = [
	['href' => '/#features', 'label' => 'Features', 'source' => null],
	['href' => '/pricing.html', 'label' => 'Pricing', 'source' => 'pricing.md'],
	['href' => '/changelog.html', 'label' => 'Changelog', 'source' => 'changelog.md'],
];

/** Escape text for the page body and double dollars so Pandoc leaves them alone. */
function tpl(string $value): string
{
	return str_replace('$', '$$', escapeHtml($value));
}

?><!DOCTYPE html>
<html lang="en" class="<?=escapeHtml(getConf('DEFAULT_THEME') ?: 'theme-landing')?>">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>$if(pagetitle)$${pagetitle}$else$${title}$endif$$if(title-prefix)$ · ${title-prefix}$endif$</title>
$if(description-meta)$
	<meta name="description" content="$description-meta$">
$endif$
	<link rel="icon" type="image/svg+xml" href="<?=escapeHtml(resolveAssetUrl('/mark.svg'))?>">
	<link rel="sitemap" href="<?=escapeHtml(resolveAssetUrl('/sitemap.xml'))?>">
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
			<a class="brand" href="<?=escapeHtml(resolveAssetUrl('/'))?>"><img src="<?=escapeHtml(resolveAssetUrl('/mark.svg'))?>" width="28" height="28" alt=""><span><?=escapeHtml(getConf('PRODUCT_NAME'))?></span></a>
			<nav class="nav" aria-label="Site">
				<?php foreach($navigation as $link): ?>
				<a href="<?=escapeHtml(resolveAssetUrl($link['href']))?>"<?=$link['source'] !== null && $link['source'] === $source ? ' aria-current="page"' : ''?>><?=escapeHtml($link['label'])?></a>
				<?php endforeach; ?>
			</nav>
			<?php if(!empty($hero['primary']['href'])): ?><a class="button button--small" href="<?=escapeHtml(resolveAssetUrl((string) $hero['primary']['href']))?>"><?=escapeHtml((string) ($hero['primary']['label'] ?? 'Get started'))?></a><?php endif; ?>
			<button type="button" class="theme-toggle" data-theme-toggle aria-label="Switch to dark theme" aria-pressed="false">
				<svg class="theme-icon theme-icon--moon" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path d="M20.5 14.2A8.5 8.5 0 0 1 9.8 3.5a8.5 8.5 0 1 0 10.7 10.7z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
				<svg class="theme-icon theme-icon--sun" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M12 2.5v2.5M12 19v2.5M2.5 12H5M19 12h2.5M5.3 5.3l1.8 1.8M16.9 16.9l1.8 1.8M5.3 18.7l1.8-1.8M16.9 7.1l1.8-1.8" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
			</button>
		</div>
	</header>

	<?php if($layout === 'landing'): ?>
	<main id="content">
		<section class="hero">
			<div class="shell hero-inner">
				<div class="hero-copy">
					<?php if(!empty($hero['kicker'])): ?><p class="kicker"><?=tpl((string) $hero['kicker'])?></p><?php endif; ?>
					<h1><?=tpl((string) ($hero['title'] ?? getConf('PRODUCT_NAME')))?></h1>
					<?php if(!empty($hero['lede'])): ?><p class="lede"><?=tpl((string) $hero['lede'])?></p><?php endif; ?>
					<p class="actions">
						<?php if(!empty($hero['primary']['href'])): ?><a class="button" href="<?=escapeHtml(resolveAssetUrl((string) $hero['primary']['href']))?>"><?=tpl((string) ($hero['primary']['label'] ?? 'Get started'))?></a><?php endif; ?>
						<?php if(!empty($hero['secondary']['href'])): ?><a class="button button--ghost" href="<?=escapeHtml(resolveAssetUrl((string) $hero['secondary']['href']))?>"><?=tpl((string) ($hero['secondary']['label'] ?? 'Learn more'))?></a><?php endif; ?>
					</p>
					<?php if(!empty($hero['note'])): ?><p class="hero-note"><?=tpl((string) $hero['note'])?></p><?php endif; ?>
				</div>
				<?php if(!empty($hero['image'])): ?>
				<figure class="hero-figure"><img src="<?=escapeHtml(resolveAssetUrl((string) $hero['image']))?>" width="520" height="360" alt="<?=tpl((string) ($hero['imageAlt'] ?? ''))?>" fetchpriority="high"></figure>
				<?php endif; ?>
			</div>
		</section>

		<?php if($features !== []): ?>
		<section class="features" id="features" aria-labelledby="features-title">
			<div class="shell">
				<h2 id="features-title" class="section-title"><?=tpl((string) ($frontmatter['featuresTitle'] ?? 'What it does'))?></h2>
				<ol class="feature-list">
					<?php foreach($features as $index => $feature): ?>
					<li class="feature">
						<span class="feature-number"><?=sprintf('%02d', $index + 1)?></span>
						<h3><?=tpl((string) ($feature['title'] ?? ''))?></h3>
						<p><?=tpl((string) ($feature['body'] ?? ''))?></p>
					</li>
					<?php endforeach; ?>
				</ol>
			</div>
		</section>
		<?php endif; ?>

		<?php if($plans !== []): ?>
		<section class="plans" id="plans" aria-labelledby="plans-title">
			<div class="shell">
				<h2 id="plans-title" class="section-title"><?=tpl((string) ($frontmatter['plansTitle'] ?? 'Plans'))?></h2>
				<ul class="plan-list">
					<?php foreach($plans as $plan): ?>
					<li class="plan<?=!empty($plan['featured']) ? ' plan--featured' : ''?>">
						<h3><?=tpl((string) ($plan['name'] ?? ''))?></h3>
						<p class="plan-price"><strong><?=tpl((string) ($plan['price'] ?? ''))?></strong><?php if(!empty($plan['period'])): ?> <small><?=tpl((string) $plan['period'])?></small><?php endif; ?></p>
						<?php if(!empty($plan['summary'])): ?><p class="plan-summary"><?=tpl((string) $plan['summary'])?></p><?php endif; ?>
						<ul class="plan-points">
							<?php foreach((array) ($plan['includes'] ?? []) as $point): ?><li><?=tpl((string) $point)?></li><?php endforeach; ?>
						</ul>
						<?php if(!empty($plan['href'])): ?><a class="button<?=empty($plan['featured']) ? ' button--ghost' : ''?>" href="<?=escapeHtml(resolveAssetUrl((string) $plan['href']))?>"><?=tpl((string) ($plan['action'] ?? 'Choose'))?></a><?php endif; ?>
					</li>
					<?php endforeach; ?>
				</ul>
				<?php if(!empty($frontmatter['plansNote'])): ?><p class="plans-note"><?=tpl((string) $frontmatter['plansNote'])?></p><?php endif; ?>
			</div>
		</section>
		<?php endif; ?>

		<?php if($faq !== []): ?>
		<section class="faq" id="faq" aria-labelledby="faq-title">
			<div class="shell shell--narrow">
				<h2 id="faq-title" class="section-title"><?=tpl((string) ($frontmatter['faqTitle'] ?? 'Questions'))?></h2>
				<?php foreach($faq as $item): ?>
				<details class="faq-item">
					<summary><?=tpl((string) ($item['q'] ?? ''))?></summary>
					<p><?=tpl((string) ($item['a'] ?? ''))?></p>
				</details>
				<?php endforeach; ?>
			</div>
		</section>
		<?php endif; ?>

		<section class="closing">
			<div class="shell shell--narrow prose">
				$body$
			</div>
		</section>
	</main>
	<?php else: ?>
	<main id="content" class="shell shell--narrow page">
		<article class="prose">
			$body$
		</article>
	</main>
	<?php endif; ?>

	<footer class="footer">
		<div class="shell footer-inner">
			<p><strong><?=escapeHtml(getConf('PRODUCT_NAME'))?></strong><?php if(getConf('TAGLINE')): ?> · <?=escapeHtml(getConf('TAGLINE'))?><?php endif; ?></p>
			<nav class="footer-links" aria-label="Footer">
				<a href="<?=escapeHtml(resolveAssetUrl('/pricing.html'))?>">Pricing</a>
				<a href="<?=escapeHtml(resolveAssetUrl('/changelog.html'))?>">Changelog</a>
				<a href="<?=escapeHtml(resolveAssetUrl('/privacy.html'))?>">Privacy</a>
				<a href="https://seanmorris.github.io/smgen-extras/themes.html#landing">Landing theme</a>
			</nav>
		</div>
	</footer>
</body>
</html>
