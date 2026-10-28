<?php

/**
 * Renders one campground row with facilities and links to nearby trails.
 */

require_once __DIR__ . '/guide-lib.php';

$regions = guideRegions();
$region = $regions[$row['region']] ?? ['slug' => $context->pathSegment($row['region']), 'blurb' => ''];
$nearby = array_values(array_filter(array_map('trim', explode('|', $row['nearby']))));
$fee = (float) $row['fee_usd'] === 0.0 ? 'Free' : '$' . $row['fee_usd'];
$water = ['tap' => 'Tap water', 'stream' => 'Stream water, treat before drinking', 'none' => 'No water'][$row['water']] ?? ucfirst($row['water']);
$toilets = ['flush' => 'Flush toilets', 'pit' => 'Pit toilets', 'none' => 'No toilets'][$row['toilets']] ?? ucfirst($row['toilets']);

echo $context->frontmatter([
	'title' => $row['name'],
	'description' => $row['summary'],
	'layout' => 'campground',
	'TOC' => false,
	'menus' => [
		'guide' => [
			'parent' => 'campgrounds',
			'weight' => (int) $row['elevation_m'],
		],
	],
	'camp' => [
		'region' => $row['region'],
		'regionSlug' => $region['slug'],
		'sites' => $row['sites'],
		'elevation' => $row['elevation_m'],
		'fee' => $fee,
		'reservations' => $row['reservations'] === 'reservable' ? 'Reservable' : 'First come, first served',
		'season' => $row['season'],
		'water' => $water,
		'toilets' => $toilets,
	],
	'jsonld' => [
		'@context' => 'https://schema.org',
		'@type' => 'Campground',
		'name' => $row['name'],
		'description' => $row['summary'],
		'containedInPlace' => ['@type' => 'Place', 'name' => $row['region'] . ', Greywater Range'],
	],
]);
?>
<?=$row['description']?>

<h2 class="section-heading">Trails from camp</h2>

<ul class="nearby">
<?php foreach($nearby as $slug): ?>
<li><a href="../trails/<?=$context->escapeHtml($slug)?>.html"><?=$context->escapeHtml(guideTitleFromSlug($slug))?></a></li>
<?php endforeach; ?>
</ul>
