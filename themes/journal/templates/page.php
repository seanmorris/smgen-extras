<?php

/**
 * Renders Commonplace, a notebook of dated entries.
 *
 * Entries are Markdown files under pages/posts/. The front page and the
 * topic pages are laid out here from each entry's frontmatter, grouped by
 * month, so adding an entry is adding a file. Topics are the files under
 * pages/topics/. SMGen core writes the RSS feed from RSS_FEEDS.
 */

$layout = $frontmatter['layout'] ?? 'page';
$source = preg_replace('#^.*?/pages/#', '', (string) $pageFile) ?? '';

/** Read every entry's frontmatter from the pages directory, newest first. */
function journalPosts(): array
{
	static $posts = null;
	if($posts !== null) return $posts;
	$pagesDir = realpath(getConf('PAGES_DIR') ?: 'pages');
	$posts = [];
	foreach(glob($pagesDir . '/posts/*.md') ?: [] as $file)
	{
		$metadata = parsePageFrontmatter($file);
		if(!isset($metadata['date'])) continue;
		$posts[] = [
			'slug' => basename($file, '.md'),
			'title' => (string) ($metadata['title'] ?? basename($file, '.md')),
			'description' => (string) ($metadata['description'] ?? ''),
			'date' => (string) $metadata['date'],
			'topic' => (string) ($metadata['topic'] ?? ''),
			'minutes' => journalReadingMinutes($file),
		];
	}
	usort($posts, static fn(array $a, array $b): int => strcmp($b['date'], $a['date']));
	return $posts;
}

/** Read the topic pages, keyed by topic name, in weight then title order. */
function journalTopics(): array
{
	static $topics = null;
	if($topics !== null) return $topics;
	$pagesDir = realpath(getConf('PAGES_DIR') ?: 'pages');
	$topics = [];
	foreach(glob($pagesDir . '/topics/*.md') ?: [] as $file)
	{
		$metadata = parsePageFrontmatter($file);
		$name = (string) ($metadata['topic'] ?? $metadata['title'] ?? basename($file, '.md'));
		$topics[$name] = [
			'slug' => basename($file, '.md'),
			'title' => (string) ($metadata['title'] ?? $name),
			'description' => (string) ($metadata['description'] ?? ''),
			'weight' => (int) ($metadata['weight'] ?? 50),
		];
	}
	uasort($topics, static fn(array $a, array $b): int => [$a['weight'], $a['title']] <=> [$b['weight'], $b['title']]);
	return $topics;
}

/** Estimate reading time in whole minutes from a Markdown file's body. */
function journalReadingMinutes(string $file): int
{
	$text = (string) file_get_contents($file);
	$body = preg_replace('/\A---\R.*?\R---\R/s', '', $text, 1) ?? $text;
	return max(1, (int) ceil(str_word_count(strip_tags($body)) / 210));
}

/** Format an ISO date for reading. */
function journalDate(string $iso, string $format = 'j F Y'): string
{
	return (new DateTimeImmutable($iso))->format($format);
}

/** Group entries into [year-month => entries] keeping newest first. */
function journalByMonth(array $posts): array
{
	$groups = [];
	foreach($posts as $post)
	{
		$groups[substr($post['date'], 0, 7)][] = $post;
	}
	return $groups;
}

/** Render a month of entries as a ledger with the day in the margin. */
function journalMonth(string $month, array $posts, array $topics, bool $showTopic = true): string
{
	$html = '<section class="month" aria-labelledby="m-' . escapeHtml($month) . '">';
	$html .= '<h2 class="month-head" id="m-' . escapeHtml($month) . '">' . escapeHtml(journalDate($month . '-01', 'F Y')) . '</h2><ol class="entries">';
	foreach($posts as $post)
	{
		$topic = $topics[$post['topic']] ?? null;
		$html .= '<li class="entry"><time class="entry-day" datetime="' . escapeHtml($post['date']) . '">' . escapeHtml(journalDate($post['date'], 'D j')) . '</time>';
		$html .= '<div class="entry-body"><h3 class="entry-title"><a href="' . escapeHtml(resolveAssetUrl('/posts/' . $post['slug'] . '.html')) . '">' . escapeHtml($post['title']) . '</a></h3>';
		$html .= '<p class="entry-summary">' . escapeHtml($post['description']) . '</p>';
		$html .= '<p class="entry-meta">';
		if($showTopic && $topic !== null)
		{
			$html .= '<a href="' . escapeHtml(resolveAssetUrl('/topics/' . $topic['slug'] . '.html')) . '">' . escapeHtml($post['topic']) . '</a> · ';
		}
		$html .= escapeHtml((string) $post['minutes']) . ' min</p></div></li>';
	}
	return $html . '</ol></section>';
}

$posts = journalPosts();
$topics = journalTopics();
$topicName = (string) ($frontmatter['topic'] ?? '');
$activeTopic = $topics[$topicName]['slug'] ?? null;
$currentPost = $previousPost = $nextPost = null;
if($layout === 'post')
{
	$slug = basename($source, '.md');
	foreach($posts as $index => $post)
	{
		if($post['slug'] !== $slug) continue;
		$currentPost = $post;
		$nextPost = $posts[$index - 1] ?? null;
		$previousPost = $posts[$index + 1] ?? null;
	}
}
$listPosts = $layout === 'topic' ? array_values(array_filter($posts, static fn(array $post): bool => $post['topic'] === $topicName)) : $posts;
$years = [];
foreach($posts as $post) $years[substr($post['date'], 0, 4)] = ($years[substr($post['date'], 0, 4)] ?? 0) + 1;

?><!DOCTYPE html>
<html lang="en" class="<?=escapeHtml(getConf('DEFAULT_THEME') ?: 'theme-journal')?>">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>$if(pagetitle)$${pagetitle}$else$${title}$endif$$if(title-prefix)$ · ${title-prefix}$endif$</title>
$if(description-meta)$
	<meta name="description" content="$description-meta$">
$endif$
$for(author)$
	<meta name="author" content="$author.name$">
$endfor$
	<link rel="icon" type="image/svg+xml" href="<?=escapeHtml(resolveAssetUrl('/mark.svg'))?>">
	<link rel="sitemap" href="<?=escapeHtml(resolveAssetUrl('/sitemap.xml'))?>">
<?php foreach(rssFeedDefinitions() as $rssFeed): ?>
	<link rel="alternate" type="application/rss+xml" title="<?=escapeHtml($rssFeed['title'])?>" href="<?=escapeHtml(resolveAssetUrl($rssFeed['path']))?>">
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
	<header class="masthead">
		<a class="wordmark" href="<?=escapeHtml(resolveAssetUrl('/'))?>"><?=escapeHtml(getConf('PRODUCT_NAME'))?></a>
		<?php if(getConf('TAGLINE')): ?><p class="tagline"><?=escapeHtml(getConf('TAGLINE'))?></p><?php endif; ?>
		<nav class="topnav" aria-label="Site">
			<a href="<?=escapeHtml(resolveAssetUrl('/'))?>"<?=$layout === 'journal' ? ' aria-current="page"' : ''?>>Entries</a>
			<?php foreach($topics as $name => $topic): ?>
			<a href="<?=escapeHtml(resolveAssetUrl('/topics/' . $topic['slug'] . '.html'))?>"<?=$activeTopic === $topic['slug'] && $layout === 'topic' ? ' aria-current="page"' : ''?>><?=escapeHtml($name)?></a>
			<?php endforeach; ?>
			<a href="<?=escapeHtml(resolveAssetUrl('/about.html'))?>"<?=$source === 'about.md' ? ' aria-current="page"' : ''?>>About</a>
			<a class="feed-link" href="<?=escapeHtml(resolveAssetUrl('/feed.xml'))?>" target="_blank" rel="noopener noreferrer">RSS</a>
			<button type="button" class="theme-toggle" data-theme-toggle aria-label="Switch to dark theme" aria-pressed="false">
				<svg class="theme-icon theme-icon--moon" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path d="M20.5 14.2A8.5 8.5 0 0 1 9.8 3.5a8.5 8.5 0 1 0 10.7 10.7z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
				<svg class="theme-icon theme-icon--sun" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M12 2.5v2.5M12 19v2.5M2.5 12H5M19 12h2.5M5.3 5.3l1.8 1.8M16.9 16.9l1.8 1.8M5.3 18.7l1.8-1.8M16.9 7.1l1.8-1.8" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
			</button>
		</nav>
	</header>

	<main id="content" class="page">
		<?php if($layout === 'journal' || $layout === 'topic'): ?>
		<header class="page-head">
			<?php if($layout === 'topic'): ?><p class="kicker">Topic</p><?php endif; ?>
			<h1><?=$layout === 'topic' ? escapeHtml((string) ($frontmatter['title'] ?? $topicName)) : escapeHtml(getConf('PRODUCT_NAME'))?></h1>
			<div class="page-intro">$body$</div>
			<?php if($layout === 'journal' && count($years) > 1): ?>
			<p class="years" aria-label="Years"><?php foreach($years as $year => $count): ?><a href="#y-<?=escapeHtml((string) $year)?>"><?=escapeHtml((string) $year)?> <small>(<?=$count?>)</small></a><?php endforeach; ?></p>
			<?php endif; ?>
		</header>
		<?php $lastYear = null; foreach(journalByMonth($listPosts) as $month => $monthPosts): ?>
		<?php $year = substr($month, 0, 4); if($layout === 'journal' && $year !== $lastYear): $lastYear = $year; ?>
		<h2 class="year-head" id="y-<?=escapeHtml($year)?>"><?=escapeHtml($year)?></h2>
		<?php endif; ?>
		<?=journalMonth($month, $monthPosts, $topics, $layout !== 'topic')?>
		<?php endforeach; ?>
		<?php if($listPosts === []): ?><p class="empty">No entries yet.</p><?php endif; ?>
		<?php elseif($layout === 'post'): ?>
		<article class="post">
			<header class="post-head">
				<p class="kicker"><?php if($activeTopic !== null): ?><a href="<?=escapeHtml(resolveAssetUrl('/topics/' . $activeTopic . '.html'))?>"><?=escapeHtml($topicName)?></a> · <?php endif; ?><time datetime="<?=escapeHtml((string) ($frontmatter['date'] ?? ''))?>"><?=escapeHtml(isset($frontmatter['date']) ? journalDate((string) $frontmatter['date']) : '')?></time><?php if($currentPost !== null): ?> · <?=escapeHtml((string) $currentPost['minutes'])?> min<?php endif; ?></p>
				<h1>$title$</h1>
				<?php if(!empty($frontmatter['description'])): ?><p class="standfirst"><?=escapeHtml((string) $frontmatter['description'])?></p><?php endif; ?>
			</header>
			<div class="prose">
				$body$
			</div>
			<footer class="post-foot">
				<nav class="siblings" aria-label="Nearby entries">
					<?php if($previousPost !== null): ?><a class="sibling sibling--earlier" href="<?=escapeHtml(resolveAssetUrl('/posts/' . $previousPost['slug'] . '.html'))?>"><small>Earlier</small><span><?=escapeHtml($previousPost['title'])?></span></a><?php endif; ?>
					<?php if($nextPost !== null): ?><a class="sibling sibling--later" href="<?=escapeHtml(resolveAssetUrl('/posts/' . $nextPost['slug'] . '.html'))?>"><small>Later</small><span><?=escapeHtml($nextPost['title'])?></span></a><?php endif; ?>
				</nav>
			</footer>
		</article>
		<?php else: ?>
		<article class="prose prose--page">
			$body$
		</article>
		<?php endif; ?>
	</main>

	<footer class="colophon">
		<p><strong><?=escapeHtml(getConf('PRODUCT_NAME'))?></strong> is kept in plain files and published with <a href="https://seanmorris.github.io/smgen/">SMGen</a>.</p>
		<p class="colophon-links"><a href="<?=escapeHtml(resolveAssetUrl('/feed.xml'))?>" target="_blank" rel="noopener noreferrer">RSS</a><a href="<?=escapeHtml(resolveAssetUrl('/about.html'))?>">About</a><a href="https://seanmorris.github.io/smgen-extras/themes.html#journal">Journal theme</a></p>
	</footer>
</body>
</html>
