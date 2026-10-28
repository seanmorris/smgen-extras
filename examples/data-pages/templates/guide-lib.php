<?php

/**
 * Shared helpers for the Greywater Range row and collection templates.
 */

/** Describe the four regions in display order. */
function guideRegions(): array
{
	return [
		'North Fork' => ['slug' => 'north-fork', 'blurb' => 'Creek valleys and old-growth cedar below the fire lookout.'],
		'Saddle Ridge' => ['slug' => 'saddle-ridge', 'blurb' => 'The high country: the summit, the larch basin, and the cirque.'],
		'Lower Basin' => ['slug' => 'lower-basin', 'blurb' => 'Reservoirs, sandstone bluffs, and the old railway to the sea.'],
		'Coastal Bluffs' => ['slug' => 'coastal-bluffs', 'blurb' => 'Cliff paths, pocket beaches, and the estuary marsh.'],
	];
}

/** Describe a difficulty code with a label, a rank, and a short meaning. */
function guideDifficulty(string $code): array
{
	return [
		'easy' => ['label' => 'Easy', 'rank' => 1, 'meaning' => 'Gentle grades, clear path, suitable for most visitors.'],
		'moderate' => ['label' => 'Moderate', 'rank' => 2, 'meaning' => 'Sustained climbing or distance. Bring water and a map.'],
		'hard' => ['label' => 'Hard', 'rank' => 3, 'meaning' => 'Long, steep, or exposed. Experience and an early start required.'],
	][$code] ?? ['label' => ucfirst($code), 'rank' => 2, 'meaning' => ''];
}

/** Describe a route type code. */
function guideRouteType(string $code): string
{
	return ['loop' => 'Loop', 'out-and-back' => 'Out and back', 'point-to-point' => 'Point to point'][$code] ?? ucfirst($code);
}

/** Describe the dog policy code. */
function guideDogs(string $code): string
{
	return ['yes' => 'Dogs welcome', 'leash' => 'Dogs on leash', 'no' => 'No dogs'][$code] ?? ucfirst($code);
}

/** Format hours as a readable duration. */
function guideDuration(string $hours): string
{
	$value = (float) $hours;
	if($value < 1)
	{
		return round($value * 60) . ' min';
	}
	$whole = floor($value);
	$minutes = round(($value - $whole) * 60);
	return $minutes > 0 ? $whole . ' h ' . $minutes . ' min' : $whole . ' h';
}

/** Turn a slug back into a readable title for cross-dataset links. */
function guideTitleFromSlug(string $slug): string
{
	$words = explode('-', $slug);
	$small = ['to', 'and', 'of', 'the'];
	foreach($words as $index => $word)
	{
		$words[$index] = ($index > 0 && in_array($word, $small, true)) ? $word : ucfirst($word);
	}
	return implode(' ', $words);
}

/** Draw a small, deterministic elevation profile shaped by route type and gain. */
function guideProfile(array $row, object $context): string
{
	$gain = max(0, (int) $row['gain_m']);
	$height = 8 + min(32, (int) round($gain / 32));
	$seed = crc32($row['slug']);
	$wobble = static fn(int $index): float => (($seed >> ($index * 3)) % 7) - 3;
	$points = [];
	$steps = 12;
	for($i = 0; $i <= $steps; $i++)
	{
		$x = $i / $steps;
		$shape = match($row['route_type'])
		{
			'loop' => sin($x * M_PI) * 0.75 + sin($x * M_PI * 2) * 0.25,
			'point-to-point' => $x < 0.7 ? $x / 0.7 : 1 - (($x - 0.7) / 0.3) * 0.35,
			default => sin($x * M_PI),
		};
		$y = 44 - max(0, $shape) * $height + ($i > 0 && $i < $steps ? $wobble($i) * ($height / 40) : 0);
		$points[] = round($x * 120, 1) . ',' . round(min(44, $y), 1);
	}
	$line = implode(' ', $points);
	return '<svg class="profile" viewBox="0 0 120 48" preserveAspectRatio="none" aria-hidden="true"><path d="M0,48 L' . $context->escapeHtml($line) . ' L120,48 Z" class="profile-fill"/><polyline points="' . $context->escapeHtml($line) . '" class="profile-line"/></svg>';
}

/** Render one trail card for the home page and the trails index. */
function guideTrailCard(array $row, object $context, string $prefix): string
{
	$difficulty = guideDifficulty($row['difficulty']);
	return '<li class="trail-card"><a href="' . $prefix . $context->escapeHtml($row['slug']) . '.html">'
		. guideProfile($row, $context)
		. '<span class="trail-card-head"><span class="trail-card-region">' . $context->escapeHtml($row['region']) . '</span><span class="pill pill--' . $context->escapeHtml($row['difficulty']) . '">' . $context->escapeHtml($difficulty['label']) . '</span></span>'
		. '<strong class="trail-card-name">' . $context->escapeHtml($row['name']) . '</strong>'
		. '<span class="trail-card-summary">' . $context->escapeHtml($row['summary']) . '</span>'
		. '<span class="stats"><span><b>' . $context->escapeHtml($row['distance_km']) . '</b> km</span><span><b>' . $context->escapeHtml($row['gain_m']) . '</b> m gain</span><span><b>' . $context->escapeHtml(guideDuration($row['hours'])) . '</b></span></span>'
		. '</a></li>';
}

/** Render one campground card. */
function guideCampgroundCard(array $row, object $context, string $prefix): string
{
	$fee = (float) $row['fee_usd'] === 0.0 ? 'Free' : '$' . $row['fee_usd'] . ' a night';
	return '<li class="camp-card"><a href="' . $prefix . $context->escapeHtml($row['slug']) . '.html">'
		. '<span class="trail-card-head"><span class="trail-card-region">' . $context->escapeHtml($row['region']) . '</span><span class="pill pill--neutral">' . $context->escapeHtml($row['reservations'] === 'reservable' ? 'Reservable' : 'First come') . '</span></span>'
		. '<strong class="trail-card-name">' . $context->escapeHtml($row['name']) . '</strong>'
		. '<span class="trail-card-summary">' . $context->escapeHtml($row['summary']) . '</span>'
		. '<span class="stats"><span><b>' . $context->escapeHtml($row['sites']) . '</b> sites</span><span><b>' . $context->escapeHtml($row['elevation_m']) . '</b> m</span><span><b>' . $context->escapeHtml($fee) . '</b></span></span>'
		. '</a></li>';
}
