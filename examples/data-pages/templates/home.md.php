<?php

/**
 * Renders the guide front page from every trail row.
 */

require_once __DIR__ . '/guide-lib.php';

$regions = guideRegions();
$rows = array_map(static fn(array $record): array => $record['row'], $records);
$totalKm = array_sum(array_map(static fn(array $row): float => (float) $row['distance_km'], $rows));
$maxGain = max(array_map(static fn(array $row): int => (int) $row['gain_m'], $rows));
$easy = array_values(array_filter($rows, static fn(array $row): bool => $row['difficulty'] === 'easy' && (float) $row['distance_km'] >= 3));
usort($easy, static fn(array $a, array $b): int => (float) $b['distance_km'] <=> (float) $a['distance_km']);
$big = array_values(array_filter($rows, static fn(array $row): bool => $row['difficulty'] === 'hard'));
usort($big, static fn(array $a, array $b): int => (int) $b['gain_m'] <=> (int) $a['gain_m']);

echo $context->frontmatter([
	'title' => 'Greywater Range trail guide',
	'description' => 'Twenty trails and eight campgrounds across four regions of a fictional mountain range, generated from two spreadsheets.',
	'layout' => 'home',
	'TOC' => false,
	'menus' => ['guide' => ['title' => 'Overview', 'weight' => -100]],
	'home' => [
		'trails' => count($rows),
		'km' => number_format($totalKm, 0),
		'gain' => number_format($maxGain),
		'regions' => count($regions),
	],
	'jsonld' => [
		'@context' => 'https://schema.org',
		'@type' => 'WebSite',
		'name' => 'Greywater Range trail guide',
		'description' => 'Twenty trails and eight campgrounds across four regions of a fictional mountain range.',
	],
]);
?>
<section class="band" aria-labelledby="regions-title">
<div class="band-head"><h2 id="regions-title">Four regions</h2><a href="trails/index.html">All trails</a></div>
<ul class="region-grid">
<?php foreach($regions as $name => $region): ?>
<?php $matching = array_filter($rows, static fn(array $row): bool => $row['region'] === $name); ?>
<?php $km = array_sum(array_map(static fn(array $row): float => (float) $row['distance_km'], $matching)); ?>
<li><a href="trails/index.html#<?=$context->escapeHtml($region['slug'])?>"><span class="region-photo"><?=guidePhoto($region['slug'], $region['alt'], $context, '', '(max-width: 44rem) 100vw, 25vw')?></span><span class="region-body"><strong><?=$context->escapeHtml($name)?></strong><span><?=$context->escapeHtml($region['blurb'])?></span><small><?=count($matching)?> trails · <?=number_format($km, 0)?> km</small></span></a></li>
<?php endforeach; ?>
</ul>
</section>

<section class="band" aria-labelledby="easy-title">
<div class="band-head"><h2 id="easy-title">Easy days out</h2><p>Gentle grades, clear paths, and somewhere to sit.</p></div>
<ul class="trail-grid">
<?php foreach(array_slice($easy, 0, 3) as $row): ?>
<?=guideTrailCard($row, $context, 'trails/')?>
<?php endforeach; ?>
</ul>
</section>

<section class="band" aria-labelledby="big-title">
<div class="band-head"><h2 id="big-title">Big days</h2><p>Start early, carry a map, and tell someone where you are going.</p></div>
<ul class="trail-grid">
<?php foreach(array_slice($big, 0, 3) as $row): ?>
<?=guideTrailCard($row, $context, 'trails/')?>
<?php endforeach; ?>
</ul>
</section>

<section class="band band--camp" aria-labelledby="camp-title">
<div class="band-head"><h2 id="camp-title">Stay the night</h2><a href="campgrounds/index.html">All campgrounds</a></div>
<div class="camp-feature">
<figure class="camp-photo"><?=guidePhoto('camp-alpine-pass', 'Three tents pitched among cairns on an alpine pass at dawn, with cloud filling the valleys below', $context, '', '(max-width: 44rem) 100vw, 40vw')?></figure>
<p class="band-copy">Eight campgrounds from a sandy reservoir beach to a windy alpine pass. Each page lists the trails that start from camp, so you can plan a weekend without a car.</p>
</div>
</section>
