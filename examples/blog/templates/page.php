<?php

/**
 * Renders the Field Notes journal shell.
 *
 * Posts are plain Markdown files under pages/posts/. The front page and topic
 * pages are laid out here from each post's frontmatter, so there is nothing to
 * regenerate when a post is added.
 */

$layout = $frontmatter['layout'] ?? 'page';
$showArchive = in_array($layout, ['journal', 'topic', 'day'], true);
$source = preg_replace('#^.*?/pages/#', '', (string) $pageFile) ?? '';
$topics = [
	'Workshop' => ['slug' => 'workshop', 'blurb' => 'Press days, ink recipes, and the slow care of old machines.'],
	'Walks' => ['slug' => 'walks', 'blurb' => 'Loops along the sea wall, the headland, and the ferry slip.'],
	'Reading' => ['slug' => 'reading', 'blurb' => 'Books that earned a place on the studio shelf.'],
	'Tools' => ['slug' => 'tools', 'blurb' => 'Pencils, presses, and plain files that keep working.'],
	'Studio' => ['slug' => 'studio', 'blurb' => 'Who we are, how we work, and what we charge for.'],
];

/** Read every post's frontmatter from the pages directory, newest first. */
function journalPosts(): array
{
	static $posts = null;
	if($posts !== null)
	{
		return $posts;
	}
	$pagesDir = realpath(getConf('PAGES_DIR') ?: 'pages');
	$posts = [];
	foreach(glob($pagesDir . '/posts/*.md') ?: [] as $file)
	{
		$metadata = parsePageFrontmatter($file);
		if(!isset($metadata['date']))
		{
			continue;
		}
		$posts[] = [
			'slug' => basename($file, '.md'),
			'title' => (string) ($metadata['title'] ?? basename($file, '.md')),
			'description' => (string) ($metadata['description'] ?? ''),
			'date' => (string) $metadata['date'],
			'modified' => (string) ($metadata['modified'] ?? ''),
			'author' => (string) ($metadata['author'] ?? ''),
			'topic' => (string) ($metadata['topic'] ?? ''),
			'minutes' => journalReadingMinutes($file),
		];
	}
	usort($posts, static fn(array $a, array $b): int => strcmp($b['date'], $a['date']));
	return $posts;
}

/** Estimate reading time in whole minutes from a Markdown file's body. */
function journalReadingMinutes(string $file): int
{
	$text = (string) file_get_contents($file);
	$body = preg_replace('/\A---\R.*?\R---\R/s', '', $text, 1) ?? $text;
	return max(1, (int) ceil(str_word_count(strip_tags($body)) / 210));
}

/** Build the two-letter monogram used for author avatars. */
function journalInitials(string $name): string
{
	$initials = '';
	foreach(array_slice(preg_split('/\s+/', trim($name)) ?: [], 0, 2) as $part)
	{
		$initials .= mb_strtoupper(mb_substr($part, 0, 1));
	}
	return $initials;
}

/** Format an ISO timestamp as a long or short reading date. */
function journalDate(string $iso, string $format = 'j F Y'): string
{
	return (new DateTimeImmutable($iso))->format($format);
}

/** Render one post entry for the front page or a topic list. */
function journalEntry(array $post, array $topics, bool $showTopic = true): string
{
	$topic = $topics[$post['topic']] ?? null;
	$html = '<li class="entry"><div class="entry-meta">';
	if($showTopic && $topic !== null)
	{
		$html .= '<a class="entry-topic" href="' . escapeHtml(resolveAssetUrl('/topics/' . $topic['slug'] . '.html')) . '">' . escapeHtml($post['topic']) . '</a>';
	}
	$html .= '<time datetime="' . escapeHtml($post['date']) . '">' . escapeHtml(journalDate($post['date'], 'j M Y')) . '</time></div>';
	$html .= '<h3 class="entry-title"><a href="' . escapeHtml(resolveAssetUrl('/posts/' . $post['slug'] . '.html')) . '">' . escapeHtml($post['title']) . '</a></h3>';
	$html .= '<p class="entry-description">' . escapeHtml($post['description']) . '</p>';
	$html .= '<p class="entry-byline">' . escapeHtml($post['author']) . '</p></li>';
	return $html;
}

$posts = journalPosts();
$activeTopic = null;
$currentPost = null;
$previousPost = null;
$nextPost = null;
if($layout === 'post')
{
	$slug = basename($source, '.md');
	foreach($posts as $index => $post)
	{
		if($post['slug'] === $slug)
		{
			$currentPost = $post;
			$nextPost = $posts[$index - 1] ?? null;
			$previousPost = $posts[$index + 1] ?? null;
		}
	}
	$activeTopic = $topics[$frontmatter['topic'] ?? '']['slug'] ?? null;
}
if($layout === 'topic')
{
	$activeTopic = $topics[$frontmatter['topic'] ?? '']['slug'] ?? null;
}
$topicName = (string) ($frontmatter['topic'] ?? '');
$topicPosts = array_values(array_filter($posts, static fn(array $post): bool => $post['topic'] === $topicName));

?><!DOCTYPE html>
<html lang="en" class="<?=escapeHtml(getConf('DEFAULT_THEME') ?: 'theme-journal')?>">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>$if(title-prefix)$${title-prefix} · $endif$$if(pagetitle)$${pagetitle}$else$${title}$endif$</title>
$if(description-meta)$
	<meta name="description" content="$description-meta$">
$endif$
	<meta name="theme-color" content="#1f3a4d">
	<link rel="icon" type="image/svg+xml" href="<?=escapeHtml(resolveAssetUrl('/mark.svg'))?>">
<?php renderJsonLd($frontmatter, $pageFile); ?>
<?php foreach(rssFeedDefinitions() as $rssFeed): ?>
	<link rel="alternate" type="application/rss+xml" title="<?=escapeHtml($rssFeed['title'])?>" href="<?=escapeHtml(resolveAssetUrl($rssFeed['path']))?>">
<?php endforeach; ?>
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
		<p class="masthead-line"><span>No. <?=count($posts)?></span><span>Harbour studio</span><span>Est. 2019</span></p>
		<a class="wordmark" href="<?=escapeHtml(resolveAssetUrl('/'))?>"><?=escapeHtml(getConf('PRODUCT_NAME'))?></a>
		<p class="masthead-tagline"><?=escapeHtml(getConf('TAGLINE'))?></p>
		<nav class="topics-nav" aria-label="Topics">
			<a href="<?=escapeHtml(resolveAssetUrl('/'))?>"<?=$layout === 'journal' ? ' aria-current="page"' : ''?>>Journal</a>
			<?php foreach($topics as $label => $topic): ?>
			<a href="<?=escapeHtml(resolveAssetUrl('/topics/' . $topic['slug'] . '.html'))?>"<?=$activeTopic === $topic['slug'] ? ' aria-current="page"' : ''?>><?=escapeHtml($label)?></a>
			<?php endforeach; ?>
			<a href="<?=escapeHtml(resolveAssetUrl('/about.html'))?>"<?=$source === 'about.md' ? ' aria-current="page"' : ''?>>About</a>
			<a class="feed-link" href="<?=escapeHtml(resolveAssetUrl('/feed.xml'))?>" target="_blank" rel="noopener noreferrer">RSS</a>
			<button type="button" class="theme-toggle" data-theme-toggle aria-label="Switch to light theme" aria-pressed="false">
				<svg class="theme-icon theme-icon--moon" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path d="M20.5 14.2A8.5 8.5 0 0 1 9.8 3.5a8.5 8.5 0 1 0 10.7 10.7z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
				<svg class="theme-icon theme-icon--sun" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M12 2.5v2.5M12 19v2.5M2.5 12H5M19 12h2.5M5.3 5.3l1.8 1.8M16.9 16.9l1.8 1.8M5.3 18.7l1.8-1.8M16.9 7.1l1.8-1.8" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
			</button>
		</nav>
	</header>

	<main id="content" class="page<?=$showArchive ? ' page--with-rail' : ''?>">
		<?php if($layout === 'post'): ?>
		<?php $topic = $topics[$topicName] ?? ['slug' => 'studio']; ?>
		<article class="post">
			<header class="post-header">
				<p class="post-kicker"><a href="<?=escapeHtml(resolveAssetUrl('/topics/' . $topic['slug'] . '.html'))?>"><?=escapeHtml($topicName)?></a><span>·</span><time datetime="<?=escapeHtml($frontmatter['date'] ?? '')?>"><?=escapeHtml(journalDate((string) ($frontmatter['date'] ?? 'now')))?></time></p>
				<h1 class="post-title">$title$</h1>
				<p class="post-deck"><?=escapeHtml($frontmatter['description'] ?? '')?></p>
				<p class="post-byline"><span class="avatar" aria-hidden="true"><?=escapeHtml(journalInitials((string) ($frontmatter['author'] ?? '')))?></span><span><strong><?=escapeHtml($frontmatter['author'] ?? '')?></strong><br><?=$currentPost['minutes'] ?? 1?> minute read<?php if(!empty($frontmatter['modified'])): ?> · updated <?=escapeHtml(journalDate((string) $frontmatter['modified']))?><?php endif; ?></span></p>
			</header>
			<div class="post-body prose">
				$body$
			</div>
			<footer class="post-footer">
				<p>Filed under <a href="<?=escapeHtml(resolveAssetUrl('/topics/' . $topic['slug'] . '.html'))?>"><?=escapeHtml($topicName)?></a>. <a href="<?=escapeHtml(resolveAssetUrl('/feed.xml'))?>" target="_blank" rel="noopener noreferrer">Subscribe to the feed</a> to hear about the next one.</p>
				<nav class="post-siblings" aria-label="Nearby notes">
					<?php if($previousPost !== null): ?>
					<a class="post-sibling post-sibling--previous" href="<?=escapeHtml(resolveAssetUrl('/posts/' . $previousPost['slug'] . '.html'))?>"><small>Earlier</small><span><?=escapeHtml($previousPost['title'])?></span></a>
					<?php endif; ?>
					<?php if($nextPost !== null): ?>
					<a class="post-sibling post-sibling--next" href="<?=escapeHtml(resolveAssetUrl('/posts/' . $nextPost['slug'] . '.html'))?>"><small>Later</small><span><?=escapeHtml($nextPost['title'])?></span></a>
					<?php endif; ?>
				</nav>
			</footer>
		</article>
		<?php elseif($layout === 'topic'): ?>
		<div class="page-main">
			<header class="topic-header">
				<p class="topic-kicker">Topic</p>
				<h1 class="topic-title"><?=escapeHtml($topicName)?></h1>
				<p class="topic-blurb"><?=escapeHtml($frontmatter['description'] ?? '')?></p>
				<p class="topic-count"><?=count($topicPosts)?> <?=count($topicPosts) === 1 ? 'note' : 'notes'?></p>
			</header>
			$body$
			<ol class="entries">
				<?php foreach($topicPosts as $post): ?>
				<?=journalEntry($post, $topics, false)?>
				<?php endforeach; ?>
			</ol>
		</div>
		<?php elseif($layout === 'day'): ?>
		<div class="page-main">
			<header class="topic-header">
				<p class="topic-kicker">Notes from one day</p>
				<h1 class="topic-title"><?=escapeHtml($frontmatter['title'] ?? '')?></h1>
				<p class="topic-blurb"><?=escapeHtml($frontmatter['description'] ?? '')?></p>
			</header>
			$body$
		</div>
		<?php elseif($layout === 'journal'): ?>
		<div class="page-main">
			$body$
			<?php if($posts !== []): ?>
			<?php $featured = $posts[0]; $featuredTopic = $topics[$featured['topic']] ?? ['slug' => 'studio']; ?>
			<section class="featured" aria-labelledby="featured-title">
				<p class="featured-kicker"><span>Latest</span> <a href="<?=escapeHtml(resolveAssetUrl('/topics/' . $featuredTopic['slug'] . '.html'))?>"><?=escapeHtml($featured['topic'])?></a></p>
				<h1 id="featured-title" class="featured-title"><a href="<?=escapeHtml(resolveAssetUrl('/posts/' . $featured['slug'] . '.html'))?>"><?=escapeHtml($featured['title'])?></a></h1>
				<p class="featured-description"><?=escapeHtml($featured['description'])?></p>
				<p class="featured-byline"><span class="avatar" aria-hidden="true"><?=escapeHtml(journalInitials($featured['author']))?></span><span><?=escapeHtml($featured['author'])?></span><span class="byline-dot">·</span><time datetime="<?=escapeHtml($featured['date'])?>"><?=escapeHtml(journalDate($featured['date']))?></time><span class="byline-dot">·</span><span><?=$featured['minutes']?> minute read</span></p>
			</section>
			<section class="recent" aria-labelledby="recent-title">
				<h2 id="recent-title" class="section-title">Recent notes</h2>
				<ol class="entries">
					<?php foreach(array_slice($posts, 1) as $post): ?>
					<?=journalEntry($post, $topics)?>
					<?php endforeach; ?>
				</ol>
			</section>
			<section class="topics" aria-labelledby="topics-title">
				<h2 id="topics-title" class="section-title">Topics</h2>
				<ul class="topic-grid">
					<?php foreach($topics as $label => $topic): ?>
					<?php $count = count(array_filter($posts, static fn(array $post): bool => $post['topic'] === $label)); ?>
					<li><a href="<?=escapeHtml(resolveAssetUrl('/topics/' . $topic['slug'] . '.html'))?>"><strong><?=escapeHtml($label)?></strong><span><?=escapeHtml($topic['blurb'])?></span><small><?=$count?> <?=$count === 1 ? 'note' : 'notes'?></small></a></li>
					<?php endforeach; ?>
				</ul>
			</section>
			<?php endif; ?>
		</div>
		<?php else: ?>
		<article class="post post--plain">
			<div class="prose">
				$body$
			</div>
		</article>
		<?php endif; ?>
		<?php if($showArchive): ?>
		<aside class="rail" aria-label="Archive">
			<?php renderMenuRegion('sidebar'); ?>
			<section class="rail-note">
				<h2 class="navigation-widget-title">About this journal</h2>
				<p>Every note is one Markdown file with a few lines of frontmatter. <a href="<?=escapeHtml(resolveAssetUrl('/about.html'))?>">Read the colophon</a>.</p>
			</section>
		</aside>
		<?php endif; ?>
	</main>

	<footer class="colophon">
		<p><strong><?=escapeHtml(getConf('PRODUCT_NAME'))?></strong> is printed by hand and published from plain files.</p>
		<p class="colophon-links"><a href="<?=escapeHtml(resolveAssetUrl('/feed.xml'))?>" target="_blank" rel="noopener noreferrer">RSS feed</a><a href="<?=escapeHtml(resolveAssetUrl('/about.html'))?>">Colophon</a><a href="https://seanmorris.github.io/smgen/">Built with SMGen</a><a href="https://github.com/seanmorris/smgen-plugins/tree/main/examples/blog">Source</a></p>
	</footer>
</body>
</html>
