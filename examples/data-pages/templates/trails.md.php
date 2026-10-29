<?php

/**
 * Renders the full trail index grouped by region.
 */

require_once __DIR__ . '/guide-lib.php';

$regions = guideRegions();
$rows = array_map(static fn(array $record): array => $record['row'], $records);

echo $context->frontmatter([
	'title' => 'All trails',
	'description' => 'Every trail in the Greywater Range guide, grouped by region.',
	'layout' => 'index',
	'TOC' => false,
	'menus' => ['guide' => ['parent' => 'trails', 'title' => 'All trails', 'weight' => -1]],
	'index' => [
		'kicker' => 'Trails',
		'lede' => count($rows) . ' trails across four regions, from a half-hour boardwalk to a full-day traverse.',
		'photo' => 'railway-grade',
		'photoAlt' => 'The old logging railway grade running straight through second-growth forest in morning light',
	],
]);
?>
<nav class="anchor-nav" aria-label="Regions">
<?php foreach($regions as $name => $region): ?>
<a href="#<?=$context->escapeHtml($region['slug'])?>"><?=$context->escapeHtml($name)?></a>
<?php endforeach; ?>
</nav>

<?php foreach($regions as $name => $region): ?>
<?php $matching = array_values(array_filter($rows, static fn(array $row): bool => $row['region'] === $name)); ?>
<?php usort($matching, static fn(array $a, array $b): int => (float) $a['distance_km'] <=> (float) $b['distance_km']); ?>
<section class="band" id="<?=$context->escapeHtml($region['slug'])?>" aria-labelledby="<?=$context->escapeHtml($region['slug'])?>-title">
<figure class="region-banner"><?=guidePhoto($region['slug'], $region['alt'], $context, '../', '(max-width: 44rem) 100vw, 70vw')?></figure>
<div class="band-head"><h2 id="<?=$context->escapeHtml($region['slug'])?>-title"><?=$context->escapeHtml($name)?></h2><p><?=$context->escapeHtml($region['blurb'])?></p></div>
<ul class="trail-grid">
<?php foreach($matching as $row): ?>
<?=guideTrailCard($row, $context, '')?>
<?php endforeach; ?>
</ul>
</section>

<?php endforeach; ?>
