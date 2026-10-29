<?php

/**
 * Renders the campground index grouped by region.
 */

require_once __DIR__ . '/guide-lib.php';

$regions = guideRegions();
$rows = array_map(static fn(array $record): array => $record['row'], $records);

echo $context->frontmatter([
	'title' => 'Campgrounds',
	'description' => 'Every campground in the Greywater Range guide, with facilities and the trails that start from camp.',
	'layout' => 'index',
	'TOC' => false,
	'menus' => ['guide' => ['parent' => 'campgrounds', 'title' => 'All campgrounds', 'weight' => -1]],
	'index' => [
		'kicker' => 'Campgrounds',
		'lede' => count($rows) . ' places to spend the night, from a reservoir beach to an alpine pass.',
		'photo' => 'camp-reservoir-beach',
		'photoAlt' => 'Two tents on a sandy reservoir beach at dawn with a canoe pulled up on the shore',
	],
]);
?>
<?php foreach($regions as $name => $region): ?>
<?php $matching = array_values(array_filter($rows, static fn(array $row): bool => $row['region'] === $name)); ?>
<?php if($matching === []) continue; ?>
<section class="band" id="<?=$context->escapeHtml($region['slug'])?>" aria-labelledby="camp-<?=$context->escapeHtml($region['slug'])?>-title">
<div class="band-head"><h2 id="camp-<?=$context->escapeHtml($region['slug'])?>-title"><?=$context->escapeHtml($name)?></h2></div>
<ul class="trail-grid">
<?php foreach($matching as $row): ?>
<?=guideCampgroundCard($row, $context, '')?>
<?php endforeach; ?>
</ul>
</section>

<?php endforeach; ?>
