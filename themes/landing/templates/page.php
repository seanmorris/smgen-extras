<?php

/**
 * Renders a product landing page from the front page's frontmatter: a hero
 * with a device screen drawn from data, a band of figures, feature rows each
 * paired with a readout, a plan comparison table, questions, and a closing
 * call to action. Every other page is prose. Nothing here names a product;
 * the sample data in pages/index.md is a starter, and the Tidewatch demo in
 * the smgen-extras repository is an example built on the same template.
 */

$layout = $frontmatter['layout'] ?? 'page';
$source = preg_replace('#^.*?/pages/#', '', (string) $pageFile) ?? '';
$pagesDir = realpath(getConf('PAGES_DIR') ?: 'pages') ?: 'pages';
$front = $layout === 'landing' ? $frontmatter : (is_file($pagesDir . '/index.md') ? parsePageFrontmatter($pagesDir . '/index.md') : []);

$hero = is_array($front['hero'] ?? null) ? $front['hero'] : [];
$screen = is_array($hero['screen'] ?? null) ? $hero['screen'] : [];
$stats = is_array($front['stats'] ?? null) ? $front['stats'] : [];
$features = is_array($front['features'] ?? null) ? $front['features'] : [];
$plans = is_array($front['plans'] ?? null) ? $front['plans'] : [];
$planRows = is_array($front['planRows'] ?? null) ? $front['planRows'] : [];
$faq = is_array($front['faq'] ?? null) ? $front['faq'] : [];
$closing = is_array($front['closing'] ?? null) ? $front['closing'] : [];
$primary = is_array($hero['primary'] ?? null) ? $hero['primary'] : [];
$navigation = is_array($front['nav'] ?? null) ? $front['nav'] : [
	['label' => 'Features', 'href' => '/#features'],
	['label' => 'Plans', 'href' => '/#plans'],
	['label' => 'Questions', 'href' => '/#faq'],
];

/** Escape text for the page body and double dollars so Pandoc leaves them alone. */
function tpl(string $value): string
{
	return str_replace('$', '$$', escapeHtml($value));
}

/** A trend arrow for up, down or flat. */
function landingTrend(string $trend): string
{
	$glyph = ['up' => '▲', 'down' => '▼', 'flat' => '▬'][$trend] ?? '';
	return $glyph === '' ? '' : '<span class="trend trend--' . escapeHtml($trend) . '" aria-hidden="true">' . $glyph . '</span>';
}

/**
 * Draw a sparkline from a list of numbers, with optional marks placed by
 * fraction along the series ({at: 0.0-1.0, label: "..."}).
 */
function landingSparkline(array $series, array $marks): string
{
	$values = array_values(array_filter(array_map(static fn($v) => is_numeric($v) ? (float) $v : null, $series), static fn($v) => $v !== null));
	if(count($values) < 2) return '';
	$width = 320; $height = 96; $top = 18; $bottom = 86;
	$min = min($values); $max = max($values); $span = $max - $min ?: 1.0;
	$points = [];
	foreach($values as $index => $value)
	{
		$x = $index / (count($values) - 1) * $width;
		$y = $bottom - ($value - $min) / $span * ($bottom - $top);
		$points[] = [round($x, 1), round($y, 1)];
	}
	$line = implode(' ', array_map(static fn(array $p) => $p[0] . ',' . $p[1], $points));
	$area = 'M0,' . $height . ' L' . $line . ' L' . $width . ',' . $height . ' Z';
	$svg = '<svg class="sparkline" viewBox="0 0 ' . $width . ' ' . $height . '" preserveAspectRatio="none" aria-hidden="true" focusable="false">'
		. '<path class="sparkline-area" d="' . $area . '"/>'
		. '<polyline class="sparkline-line" points="' . $line . '"/>';
	foreach($marks as $mark)
	{
		if(!is_array($mark) || !isset($mark['at'])) continue;
		$at = max(0.0, min(1.0, (float) $mark['at']));
		$position = $at * (count($values) - 1);
		$lower = (int) floor($position); $upper = min(count($values) - 1, $lower + 1);
		$y = $points[$lower][1] + ($points[$upper][1] - $points[$lower][1]) * ($position - $lower);
		$x = round($at * $width, 1);
		$anchor = $at < 0.15 ? 'start' : ($at > 0.85 ? 'end' : 'middle');
		$below = $y > ($top + $bottom) / 2;
		$svg .= '<circle class="sparkline-mark" cx="' . $x . '" cy="' . round($y, 1) . '" r="3.5"/>';
		if(!empty($mark['label'])) $svg .= '<text class="sparkline-text" x="' . $x . '" y="' . round($below ? $y + 17 : $y - 9, 1) . '" text-anchor="' . $anchor . '">' . escapeHtml((string) $mark['label']) . '</text>';
	}
	return $svg . '</svg>';
}

/** The device screen in the hero, drawn entirely from data. */
function landingScreen(array $screen): string
{
	$label = (string) ($screen['alt'] ?? trim(($screen['title'] ?? '') . ' ' . ($screen['value'] ?? '') . ' ' . ($screen['label'] ?? '')));
	$html = '<div class="device" role="img" aria-label="' . escapeHtml($label) . '"><div class="screen">';
	if(!empty($screen['title']) || !empty($screen['subtitle']))
	{
		$html .= '<div class="screen-head"><span>' . tpl((string) ($screen['title'] ?? '')) . '</span><span>' . tpl((string) ($screen['subtitle'] ?? '')) . '</span></div>';
	}
	if(isset($screen['value'])) $html .= '<p class="screen-value">' . tpl((string) $screen['value']) . '</p>';
	if(!empty($screen['label'])) $html .= '<p class="screen-label">' . tpl((string) $screen['label']) . '</p>';
	if(!empty($screen['status'])) $html .= '<p class="screen-status">' . landingTrend((string) ($screen['trend'] ?? '')) . tpl((string) $screen['status']) . '</p>';
	$html .= landingSparkline((array) ($screen['series'] ?? []), (array) ($screen['marks'] ?? []));
	if(!empty($screen['rows']) && is_array($screen['rows']))
	{
		$html .= '<dl class="screen-rows">';
		foreach($screen['rows'] as $row)
		{
			if(!is_array($row)) continue;
			$html .= '<div><dt>' . tpl((string) ($row['label'] ?? '')) . '</dt><dd>' . tpl((string) ($row['value'] ?? '')) . '</dd></div>';
		}
		$html .= '</dl>';
	}
	return $html . '</div></div>';
}

/** A readout beside a feature: a value, a list, a dial or a few lines. */
function landingReadout(array $readout): string
{
	$kind = (string) ($readout['kind'] ?? 'value');
	switch($kind)
	{
		case 'list':
			$html = '<dl class="readout readout--list">';
			foreach((array) ($readout['items'] ?? []) as $item)
			{
				if(!is_array($item)) continue;
				$html .= '<div><dt>' . tpl((string) ($item['label'] ?? '')) . '</dt><dd>' . tpl((string) ($item['value'] ?? '')) . '</dd></div>';
			}
			return $html . '</dl>';
		case 'dial':
			return '<div class="readout readout--dial"><div class="dial">'
				. '<span class="dial-value">' . tpl((string) ($readout['value'] ?? '')) . '<small>' . tpl((string) ($readout['unit'] ?? '')) . '</small></span>'
				. '<span class="dial-label">' . landingTrend((string) ($readout['trend'] ?? '')) . tpl((string) ($readout['label'] ?? '')) . '</span>'
				. '</div></div>';
		case 'text':
			$html = '<ul class="readout readout--text">';
			foreach((array) ($readout['lines'] ?? []) as $line) $html .= '<li>' . tpl((string) $line) . '</li>';
			return $html . '</ul>';
		default:
			return '<div class="readout readout--value">'
				. '<p class="readout-value">' . tpl((string) ($readout['value'] ?? '')) . '</p>'
				. (!empty($readout['label']) ? '<p class="readout-label">' . tpl((string) $readout['label']) . '</p>' : '')
				. (!empty($readout['note']) ? '<p class="readout-note">' . tpl((string) $readout['note']) . '</p>' : '')
				. '</div>';
	}
}

/** One cell of the plan table: a tick, a dash, or text. */
function landingCell(mixed $value): string
{
	if($value === true) return '<span class="yes" aria-label="Included">✓</span>';
	if($value === false || $value === null || $value === '') return '<span class="no" aria-label="Not included">–</span>';
	return tpl((string) $value);
}

/** The plan head: name, price, summary and button, shared by the table and the stack. */
function landingPlanHead(array $plan): string
{
	$html = '<p class="plan-name">' . tpl((string) ($plan['name'] ?? '')) . '</p>'
		. '<p class="plan-price"><strong>' . tpl((string) ($plan['price'] ?? '')) . '</strong>' . (!empty($plan['period']) ? '<small>' . tpl((string) $plan['period']) . '</small>' : '') . '</p>'
		. (!empty($plan['summary']) ? '<p class="plan-summary">' . tpl((string) $plan['summary']) . '</p>' : '');
	if(!empty($plan['href']))
	{
		$html .= '<a class="button' . (empty($plan['featured']) ? ' button--ghost' : '') . '" href="' . escapeHtml(resolveAssetUrl((string) $plan['href'])) . '">' . tpl((string) ($plan['action'] ?? 'Choose')) . '</a>';
	}
	return $html;
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
<?php foreach(['barlow-condensed-700', 'barlow-400', 'barlow-700'] as $font): ?>
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
<body class="layout-<?=escapeHtml($layout)?>">
	<a class="skip-link" href="#content">Skip to content</a>
	<header class="topbar">
		<div class="shell topbar-inner">
			<a class="brand" href="<?=escapeHtml(resolveAssetUrl('/'))?>"><img src="<?=escapeHtml(resolveAssetUrl('/mark.svg'))?>" width="26" height="26" alt=""><span><?=escapeHtml(getConf('PRODUCT_NAME'))?></span></a>
			<nav class="nav" aria-label="Site">
				<?php foreach($navigation as $link): if(!is_array($link) || empty($link['href'])) continue; ?>
				<a href="<?=escapeHtml(resolveAssetUrl((string) $link['href']))?>"><?=tpl((string) ($link['label'] ?? ''))?></a>
				<?php endforeach; ?>
			</nav>
			<?php if(!empty($primary['href'])): ?><a class="button button--small" href="<?=escapeHtml(resolveAssetUrl((string) $primary['href']))?>"><?=tpl((string) ($primary['label'] ?? 'Get started'))?></a><?php endif; ?>
			<button type="button" class="theme-toggle" data-theme-toggle aria-label="Switch to dark theme" aria-pressed="false">
				<svg class="theme-icon theme-icon--moon" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path d="M20.5 14.2A8.5 8.5 0 0 1 9.8 3.5a8.5 8.5 0 1 0 10.7 10.7z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
				<svg class="theme-icon theme-icon--sun" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M12 2.5v2.5M12 19v2.5M2.5 12H5M19 12h2.5M5.3 5.3l1.8 1.8M16.9 16.9l1.8 1.8M5.3 18.7l1.8-1.8M16.9 7.1l1.8-1.8" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
			</button>
		</div>
	</header>

	<?php if($layout === 'landing'): ?>
	<main id="content">
		<section class="hero<?=$screen !== [] || !empty($hero['image']) ? ' hero--with-device' : ''?>">
			<div class="shell hero-inner">
				<div class="hero-copy">
					<?php if(!empty($hero['kicker'])): ?><p class="kicker"><?=tpl((string) $hero['kicker'])?></p><?php endif; ?>
					<h1><?=tpl((string) ($hero['title'] ?? getConf('PRODUCT_NAME')))?></h1>
					<?php if(!empty($hero['lede'])): ?><p class="lede"><?=tpl((string) $hero['lede'])?></p><?php endif; ?>
					<p class="actions">
						<?php if(!empty($primary['href'])): ?><a class="button button--large" href="<?=escapeHtml(resolveAssetUrl((string) $primary['href']))?>"><?=tpl((string) ($primary['label'] ?? 'Get started'))?></a><?php endif; ?>
						<?php if(!empty($hero['secondary']['href'])): ?><a class="button button--large button--ghost" href="<?=escapeHtml(resolveAssetUrl((string) $hero['secondary']['href']))?>"><?=tpl((string) ($hero['secondary']['label'] ?? 'Learn more'))?></a><?php endif; ?>
					</p>
					<?php if(!empty($hero['note'])): ?><p class="hero-note"><?=tpl((string) $hero['note'])?></p><?php endif; ?>
				</div>
				<?php if($screen !== []): ?>
				<div class="hero-device"><?=landingScreen($screen)?></div>
				<?php elseif(!empty($hero['image'])): ?>
				<figure class="hero-figure"><img src="<?=escapeHtml(resolveAssetUrl((string) $hero['image']))?>" alt="<?=tpl((string) ($hero['imageAlt'] ?? ''))?>" fetchpriority="high"></figure>
				<?php endif; ?>
			</div>
		</section>

		<?php if($stats !== []): ?>
		<section class="stats" aria-label="In numbers">
			<div class="shell">
				<dl class="stat-list">
					<?php foreach($stats as $stat): if(!is_array($stat)) continue; ?>
					<div><dd><?=tpl((string) ($stat['value'] ?? ''))?></dd><dt><?=tpl((string) ($stat['label'] ?? ''))?></dt></div>
					<?php endforeach; ?>
				</dl>
			</div>
		</section>
		<?php endif; ?>

		<?php if($features !== []): ?>
		<section class="features" id="features" aria-labelledby="features-title">
			<div class="shell">
				<h2 id="features-title" class="section-title"><?=tpl((string) ($front['featuresTitle'] ?? 'What it does'))?></h2>
				<ol class="feature-list">
					<?php foreach($features as $feature): if(!is_array($feature)) continue; ?>
					<li class="feature">
						<div class="feature-copy">
							<h3><?=tpl((string) ($feature['title'] ?? ''))?></h3>
							<p><?=tpl((string) ($feature['body'] ?? ''))?></p>
						</div>
						<?php if(is_array($feature['readout'] ?? null)): ?><div class="feature-readout"><?=landingReadout($feature['readout'])?></div><?php endif; ?>
					</li>
					<?php endforeach; ?>
				</ol>
			</div>
		</section>
		<?php endif; ?>

		<?php if($plans !== []): ?>
		<section class="plans" id="plans" aria-labelledby="plans-title">
			<div class="shell">
				<h2 id="plans-title" class="section-title"><?=tpl((string) ($front['plansTitle'] ?? 'Plans'))?></h2>
				<table class="plan-table">
					<thead>
						<tr>
							<td></td>
							<?php foreach($plans as $plan): if(!is_array($plan)) continue; ?>
							<th scope="col"<?=!empty($plan['featured']) ? ' class="featured"' : ''?>><?=landingPlanHead($plan)?></th>
							<?php endforeach; ?>
						</tr>
					</thead>
					<tbody>
						<?php foreach($planRows as $row): if(!is_array($row)) continue; $values = (array) ($row['values'] ?? []); ?>
						<tr>
							<th scope="row"><?=tpl((string) ($row['label'] ?? ''))?></th>
							<?php foreach(array_values($plans) as $index => $plan): if(!is_array($plan)) continue; ?>
							<td<?=!empty($plan['featured']) ? ' class="featured"' : ''?>><?=landingCell($values[$index] ?? null)?></td>
							<?php endforeach; ?>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<ul class="plan-stack">
					<?php foreach(array_values($plans) as $index => $plan): if(!is_array($plan)) continue; ?>
					<li class="plan<?=!empty($plan['featured']) ? ' plan--featured' : ''?>">
						<?=landingPlanHead($plan)?>
						<dl>
							<?php foreach($planRows as $row): if(!is_array($row)) continue; $values = (array) ($row['values'] ?? []); ?>
							<div><dt><?=tpl((string) ($row['label'] ?? ''))?></dt><dd><?=landingCell($values[$index] ?? null)?></dd></div>
							<?php endforeach; ?>
						</dl>
					</li>
					<?php endforeach; ?>
				</ul>
				<?php if(!empty($front['plansNote'])): ?><p class="plans-note"><?=tpl((string) $front['plansNote'])?></p><?php endif; ?>
			</div>
		</section>
		<?php endif; ?>

		<?php if($faq !== []): ?>
		<section class="faq" id="faq" aria-labelledby="faq-title">
			<div class="shell">
				<h2 id="faq-title" class="section-title"><?=tpl((string) ($front['faqTitle'] ?? 'Questions'))?></h2>
				<div class="faq-grid">
					<?php foreach($faq as $item): if(!is_array($item)) continue; ?>
					<details class="faq-item">
						<summary><?=tpl((string) ($item['q'] ?? ''))?></summary>
						<p><?=tpl((string) ($item['a'] ?? ''))?></p>
					</details>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
		<?php endif; ?>

		<section class="closing">
			<div class="shell closing-inner">
				<h2><?=tpl((string) ($closing['title'] ?? $hero['title'] ?? getConf('PRODUCT_NAME')))?></h2>
				<?php if(!empty($closing['lede'])): ?><p class="lede"><?=tpl((string) $closing['lede'])?></p><?php endif; ?>
				<?php $closingPrimary = is_array($closing['primary'] ?? null) ? $closing['primary'] : $primary; if(!empty($closingPrimary['href'])): ?>
				<p class="actions"><a class="button button--large" href="<?=escapeHtml(resolveAssetUrl((string) $closingPrimary['href']))?>"><?=tpl((string) ($closingPrimary['label'] ?? 'Get started'))?></a></p>
				<?php endif; ?>
				<?php if(!empty($closing['note'] ?? $hero['note'] ?? '')): ?><p class="hero-note"><?=tpl((string) ($closing['note'] ?? $hero['note']))?></p><?php endif; ?>
			</div>
		</section>

		<section class="aside-note">
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
				<?php foreach(is_array($front['footerLinks'] ?? null) ? $front['footerLinks'] : [] as $link): if(!is_array($link) || empty($link['href'])) continue; ?>
				<a href="<?=escapeHtml(resolveAssetUrl((string) $link['href']))?>"><?=tpl((string) ($link['label'] ?? ''))?></a>
				<?php endforeach; ?>
				<a href="https://seanmorris.github.io/smgen-extras/themes.html#theme-landing">Landing theme</a>
			</nav>
		</div>
	</footer>
</body>
</html>
