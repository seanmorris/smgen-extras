<?php

/**
 * Renders one trail row as a guide page with stats, highlights, and prose.
 */

require_once __DIR__ . '/guide-lib.php';

$regions = guideRegions();
$region = $regions[$row['region']] ?? ['slug' => $context->pathSegment($row['region']), 'blurb' => ''];
$difficulty = guideDifficulty($row['difficulty']);
$highlights = array_values(array_filter(array_map('trim', explode('|', $row['highlights']))));

echo $context->frontmatter([
	'title' => $row['name'],
	'description' => $row['summary'],
	'layout' => 'trail',
	'TOC' => false,
	'menus' => [
		'guide' => [
			'parent' => 'region-' . $region['slug'],
			'weight' => (int) round((float) $row['distance_km'] * 10),
		],
	],
	'trail' => [
		'region' => $row['region'],
		'regionSlug' => $region['slug'],
		'difficulty' => $row['difficulty'],
		'difficultyLabel' => $difficulty['label'],
		'difficultyRank' => $difficulty['rank'],
		'difficultyMeaning' => $difficulty['meaning'],
		'distance' => $row['distance_km'],
		'gain' => $row['gain_m'],
		'duration' => guideDuration($row['hours']),
		'routeType' => guideRouteType($row['route_type']),
		'dogs' => guideDogs($row['dogs']),
		'season' => $row['season'],
		'trailhead' => $row['trailhead'],
		'profile' => guideProfile($row, $context),
	],
	'jsonld' => [
		'@context' => 'https://schema.org',
		'@type' => 'TouristAttraction',
		'name' => $row['name'],
		'description' => $row['summary'],
		'touristType' => 'Hikers',
		'isAccessibleForFree' => true,
		'containedInPlace' => ['@type' => 'Place', 'name' => $row['region'] . ', Greywater Range'],
	],
]);
?>
<ul class="highlights">
<?php foreach($highlights as $highlight): ?>
<li><?=$context->escapeHtml($highlight)?></li>
<?php endforeach; ?>
</ul>

<?=$row['description']?>
