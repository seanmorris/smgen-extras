<?php

/**
 * Renders one dated CSV row as a blog post with archive and feed metadata.
 */

$published = new DateTimeImmutable($row['date']);
$metadata = [
	'title' => $row['title'],
	'description' => $row['description'],
	'date' => $row['date'],
	'feeds' => ['notes'],
	'menus' => ['archive' => true],
	'rss' => [
		'author' => $row['author_email'] . ' (' . $row['author_name'] . ')',
		'category' => $row['category'],
	],
	'jsonld' => [
		'@context' => 'https://schema.org',
		'@type' => 'BlogPosting',
		'headline' => $row['title'],
		'description' => $row['description'],
		'datePublished' => $row['date'],
		'articleSection' => $row['category'],
		'author' => [
			'@type' => 'Person',
			'name' => $row['author_name'],
		],
	],
];
if(trim($row['modified']) !== '')
{
	$metadata['modified'] = $row['modified'];
	$metadata['jsonld']['dateModified'] = $row['modified'];
}

echo $context->frontmatter($metadata);
?>
# <?=$context->escapeHtml($row['title'])?>

<p class="record-kicker"><time datetime="<?=$context->escapeHtml($row['date'])?>"><?=$context->escapeHtml($published->format('F j, Y'))?></time> · <?=$context->escapeHtml($row['category'])?></p>

<?=$row['body']?>

<p class="record-note"><a href="../">All posts</a> · <a href="../feed.xml">RSS feed</a></p>
