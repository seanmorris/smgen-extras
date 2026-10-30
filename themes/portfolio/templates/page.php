<?php

/**
 * Renders Halden Bindery: a statement and a grid of work on the front page,
 * project pages with a facts strip, and plain pages for about and contact.
 * Projects are the files under pages/work/; each cover is drawn here from
 * the project's cloth and paper colours unless it names an image.
 */

$layout = $frontmatter['layout'] ?? 'page';
$source = preg_replace('#^.*?/pages/#', '', (string) $pageFile) ?? '';

/** Read every project's frontmatter, newest year first, then by weight. */
function studioProjects(): array
{
	static $projects = null;
	if($projects !== null) return $projects;
	$pagesDir = realpath(getConf('PAGES_DIR') ?: 'pages');
	$projects = [];
	foreach(glob($pagesDir . '/work/*.md') ?: [] as $file)
	{
		if(basename($file) === 'index.md') continue;
		$metadata = parsePageFrontmatter($file);
		$projects[] = [
			'slug' => basename($file, '.md'),
			'title' => (string) ($metadata['title'] ?? basename($file, '.md')),
			'kind' => (string) ($metadata['kind'] ?? ''),
			'year' => (string) ($metadata['year'] ?? ''),
			'weight' => (int) ($metadata['weight'] ?? 50),
			'description' => (string) ($metadata['description'] ?? ''),
			'cover' => is_array($metadata['cover'] ?? null) ? $metadata['cover'] : [],
			'image' => (string) ($metadata['image'] ?? ''),
		];
	}
	usort($projects, static fn(array $a, array $b): int => [$b['year'], $a['weight'], $a['title']] <=> [$a['year'], $b['weight'], $b['title']]);
	return $projects;
}

/** Keep a colour value to something CSS will accept inside an inline style. */
function studioColour(mixed $value, string $fallback): string
{
	$value = trim((string) $value);
	return preg_match('/^#[0-9a-fA-F]{3,8}$|^[a-zA-Z]+$|^(rgb|hsl|oklch|lab)a?\([^;{}<>"]+\)$/', $value) ? $value : $fallback;
}

/** Draw a project's cover: a photograph if it names one, otherwise cloth and paper. */
function studioCover(array $project, string $alt): string
{
	if($project['image'] !== '')
	{
		return '<img class="cover cover--image" src="' . escapeHtml(resolveAssetUrl($project['image'])) . '" alt="' . escapeHtml($alt) . '" loading="lazy">';
	}
	$cloth = studioColour($project['cover']['cloth'] ?? '', '#6b2d2d');
	$paper = studioColour($project['cover']['paper'] ?? '', '#efe6d6');
	$label = (string) ($project['cover']['label'] ?? $project['title']);
	return '<span class="cover cover--drawn" role="img" aria-label="' . escapeHtml($alt) . '" style="--cloth: ' . $cloth . '; --paper: ' . $paper . '">'
		. '<span class="cover-spine"></span><span class="cover-board"><span class="cover-label">' . escapeHtml($label) . '</span></span></span>';
}

/** Escape text for the page body and double dollars so Pandoc leaves them alone. */
function tpl(string $value): string
{
	return str_replace('$', '$$', escapeHtml($value));
}

$projects = studioProjects();
$project = null;
if($layout === 'project')
{
	$slug = basename($source, '.md');
	foreach($projects as $candidate) if($candidate['slug'] === $slug) $project = $candidate;
}
$facts = is_array($frontmatter['facts'] ?? null) ? $frontmatter['facts'] : [];
$navigation = [['href' => '/work/', 'label' => 'Work', 'match' => 'work/'], ['href' => '/about.html', 'label' => 'About', 'match' => 'about.md'], ['href' => '/contact.html', 'label' => 'Contact', 'match' => 'contact.md']];

?><!DOCTYPE html>
<html lang="en" class="<?=escapeHtml(getConf('DEFAULT_THEME') ?: 'theme-portfolio')?>">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>$if(pagetitle)$${pagetitle}$else$${title}$endif$$if(title-prefix)$ · ${title-prefix}$endif$</title>
$if(description-meta)$
	<meta name="description" content="$description-meta$">
$endif$
	<link rel="icon" type="image/svg+xml" href="<?=escapeHtml(resolveAssetUrl('/mark.svg'))?>">
	<link rel="sitemap" href="<?=escapeHtml(resolveAssetUrl('/sitemap.xml'))?>">
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
		<nav class="nav" aria-label="Site">
			<?php foreach($navigation as $link): ?>
			<a href="<?=escapeHtml(resolveAssetUrl($link['href']))?>"<?=str_starts_with($source, $link['match']) ? ' aria-current="page"' : ''?>><?=escapeHtml($link['label'])?></a>
			<?php endforeach; ?>
			<button type="button" class="theme-toggle" data-theme-toggle aria-label="Switch to dark theme" aria-pressed="false">
				<svg class="theme-icon theme-icon--moon" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path d="M20.5 14.2A8.5 8.5 0 0 1 9.8 3.5a8.5 8.5 0 1 0 10.7 10.7z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
				<svg class="theme-icon theme-icon--sun" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M12 2.5v2.5M12 19v2.5M2.5 12H5M19 12h2.5M5.3 5.3l1.8 1.8M16.9 16.9l1.8 1.8M5.3 18.7l1.8-1.8M16.9 7.1l1.8-1.8" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
			</button>
		</nav>
	</header>

	<?php if($layout === 'home' || $layout === 'work'): ?>
	<main id="content" class="page">
		<section class="statement prose">
			$body$
		</section>
		<section class="work" aria-labelledby="work-title">
			<h2 id="work-title" class="section-title"><?=$layout === 'home' ? 'Selected work' : 'All work'?></h2>
			<ul class="work-grid">
			<?php foreach($layout === 'home' ? array_slice($projects, 0, (int) ($frontmatter['show'] ?? 6)) : $projects as $item): ?>
				<li class="work-item">
					<a href="<?=escapeHtml(resolveAssetUrl('/work/' . $item['slug'] . '.html'))?>">
						<?=studioCover($item, $item['title'] . ($item['kind'] !== '' ? ', ' . $item['kind'] : ''))?>
						<span class="work-meta"><strong><?=escapeHtml($item['title'])?></strong><span><?=escapeHtml(trim($item['kind'] . ($item['year'] !== '' ? ' · ' . $item['year'] : ''), ' ·'))?></span></span>
					</a>
				</li>
			<?php endforeach; ?>
			</ul>
			<?php if($layout === 'home' && count($projects) > (int) ($frontmatter['show'] ?? 6)): ?><p class="work-more"><a href="<?=escapeHtml(resolveAssetUrl('/work/'))?>">All work</a></p><?php endif; ?>
		</section>
	</main>
	<?php elseif($layout === 'project'): ?>
	<main id="content" class="page page--project">
		<article class="project">
			<header class="project-head">
				<p class="kicker"><a href="<?=escapeHtml(resolveAssetUrl('/work/'))?>">Work</a><?php if($project !== null && $project['kind'] !== ''): ?> · <?=escapeHtml($project['kind'])?><?php endif; ?><?php if($project !== null && $project['year'] !== ''): ?> · <?=escapeHtml($project['year'])?><?php endif; ?></p>
				<h1>$title$</h1>
				<?php if(!empty($frontmatter['description'])): ?><p class="standfirst"><?=tpl((string) $frontmatter['description'])?></p><?php endif; ?>
			</header>
			<?php if($project !== null): ?>
			<figure class="project-cover"><?=studioCover($project, (string) ($frontmatter['coverAlt'] ?? $project['title']))?></figure>
			<?php endif; ?>
			<?php if($facts !== []): ?>
			<dl class="facts">
				<?php foreach($facts as $label => $value): ?>
				<div><dt><?=tpl((string) $label)?></dt><dd><?=tpl((string) $value)?></dd></div>
				<?php endforeach; ?>
			</dl>
			<?php endif; ?>
			<div class="prose">
				$body$
			</div>
		</article>
	</main>
	<?php else: ?>
	<main id="content" class="page page--prose">
		<article class="prose">
			$body$
		</article>
	</main>
	<?php endif; ?>

	<footer class="colophon">
		<p><strong><?=escapeHtml(getConf('PRODUCT_NAME'))?></strong><?php if(getConf('TAGLINE')): ?> · <?=escapeHtml(getConf('TAGLINE'))?><?php endif; ?></p>
		<p class="colophon-links"><a href="<?=escapeHtml(resolveAssetUrl('/contact.html'))?>">Contact</a><a href="https://seanmorris.github.io/smgen-extras/themes.html#portfolio">Portfolio theme</a></p>
	</footer>
</body>
</html>
