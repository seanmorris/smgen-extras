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

/** Sample the deterministic elevation shape of a trail as fractions of its height. */
function guideProfileShape(array $row, int $steps = 24): array
{
	$seed = crc32($row['slug']);
	$wobble = static fn(int $index): float => (($seed >> (($index % 10) * 3)) % 7) - 3;
	$shape = [];
	for($i = 0; $i <= $steps; $i++)
	{
		$x = $i / $steps;
		$value = match($row['route_type'])
		{
			'loop' => sin($x * M_PI) * 0.75 + sin($x * M_PI * 2) * 0.25,
			'point-to-point' => $x < 0.7 ? $x / 0.7 : 1 - (($x - 0.7) / 0.3) * 0.35,
			default => sin($x * M_PI),
		};
		$value = max(0, $value) + ($i > 0 && $i < $steps ? $wobble($i) / 40 : 0);
		$shape[] = max(0, min(1, $value));
	}
	return $shape;
}

/**
 * Draw an elevation profile. Cards get a small sparkline; a detailed chart adds
 * gridlines, kilometre ticks, and the climb so the figure reads as a chart.
 */
function guideProfile(array $row, object $context, bool $detailed = false): string
{
	$gain = max(0, (int) $row['gain_m']);
	$distance = max(0.1, (float) $row['distance_km']);
	$shape = guideProfileShape($row);
	$steps = count($shape) - 1;
	if(!$detailed)
	{
		$height = 8 + min(32, (int) round($gain / 32));
		$points = [];
		foreach($shape as $i => $value)
		{
			$points[] = round($i / $steps * 120, 1) . ',' . round(44 - $value * $height, 1);
		}
		$line = implode(' ', $points);
		return '<svg class="profile" viewBox="0 0 120 48" preserveAspectRatio="none" aria-hidden="true">'
			. '<path d="M0,48 L' . $context->escapeHtml($line) . ' L120,48 Z" class="profile-fill"/>'
			. '<polyline points="' . $context->escapeHtml($line) . '" class="profile-line"/>'
			. '<line x1="0" y1="47.5" x2="120" y2="47.5" class="profile-base"/></svg>';
	}
	$width = 480;
	$left = 10;
	$right = 470;
	$base = 128;
	$top = 30;
	$points = [];
	foreach($shape as $i => $value)
	{
		$points[] = round($left + $i / $steps * ($right - $left), 1) . ',' . round($base - $value * ($base - $top), 1);
	}
	$line = implode(' ', $points);
	$svg = '<svg class="profile profile--detailed" viewBox="0 0 ' . $width . ' 170" preserveAspectRatio="xMidYMid meet" role="img" aria-label="Elevation profile: ' . $context->escapeHtml((string) $gain) . ' metres of climb over ' . $context->escapeHtml((string) $distance) . ' kilometres">';
	foreach([0.25, 0.5, 0.75, 1] as $fraction)
	{
		$y = round($base - $fraction * ($base - $top), 1);
		$svg .= '<line x1="' . $left . '" y1="' . $y . '" x2="' . $right . '" y2="' . $y . '" class="profile-grid" stroke-dasharray="3 5"/>';
	}
	$svg .= '<path d="M' . $left . ',' . $base . ' L' . $context->escapeHtml($line) . ' L' . $right . ',' . $base . ' Z" class="profile-fill"/>';
	$svg .= '<polyline points="' . $context->escapeHtml($line) . '" class="profile-line"/>';
	$svg .= '<line x1="' . $left . '" y1="' . $base . '" x2="' . $right . '" y2="' . $base . '" class="profile-base"/>';
	$kilometres = (int) floor($distance);
	$every = $kilometres > 12 ? 4 : ($kilometres > 6 ? 2 : 1);
	for($km = 0; $km <= $kilometres; $km += $every)
	{
		$x = round($left + $km / $distance * ($right - $left), 1);
		$svg .= '<line x1="' . $x . '" y1="' . $base . '" x2="' . $x . '" y2="' . ($base + 6) . '" class="profile-base"/>';
		if($km === 0 || $distance - $km >= $every * 0.6)
		{
			$svg .= '<text x="' . $x . '" y="' . ($base + 22) . '" text-anchor="' . ($km === 0 ? 'start' : 'middle') . '">' . $km . '</text>';
		}
	}
	$svg .= '<text x="' . $right . '" y="' . ($base + 22) . '" text-anchor="end">' . $context->escapeHtml((string) $distance) . ' km</text>';
	$svg .= '<text x="' . $left . '" y="' . ($top - 10) . '" class="profile-gain">+' . number_format($gain) . ' m</text>';
	$svg .= '<text x="' . $right . '" y="' . ($top - 10) . '" text-anchor="end">' . $context->escapeHtml(guideRouteType($row['route_type'])) . '</text>';
	return $svg . '</svg>';
}

/** Render one trail card for the home page and the trails index. */
function guideTrailCard(array $row, object $context, string $prefix): string
{
	$difficulty = guideDifficulty($row['difficulty']);
	return '<li class="trail-card region-' . $context->pathSegment($row['region']) . '"><a href="' . $prefix . $context->escapeHtml($row['slug']) . '.html">'
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
	return '<li class="camp-card region-' . $context->pathSegment($row['region']) . '"><a href="' . $prefix . $context->escapeHtml($row['slug']) . '.html">'
		. '<span class="trail-card-head"><span class="trail-card-region">' . $context->escapeHtml($row['region']) . '</span><span class="pill pill--neutral">' . $context->escapeHtml($row['reservations'] === 'reservable' ? 'Reservable' : 'First come') . '</span></span>'
		. '<strong class="trail-card-name">' . $context->escapeHtml($row['name']) . '</strong>'
		. '<span class="trail-card-summary">' . $context->escapeHtml($row['summary']) . '</span>'
		. '<span class="stats"><span><b>' . $context->escapeHtml($row['sites']) . '</b> sites</span><span><b>' . $context->escapeHtml($row['elevation_m']) . '</b> m</span><span><b>' . $context->escapeHtml($fee) . '</b></span></span>'
		. '</a></li>';
}
