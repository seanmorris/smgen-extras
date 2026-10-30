<?php

/**
 * Renders Halden Bindery, a photo-first portfolio: a statement and a hero
 * photograph on the front page, a grid of work led by each project's
 * photograph, project pages with a full-width image, a facts strip, prose
 * and a gallery, and plain pages for about and contact. Projects are the
 * files under pages/work/.
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
			'image' => (string) ($metadata['image'] ?? ''),
			'imageAlt' => (string) ($metadata['imageAlt'] ?? ''),
			'cloth' => (string) ($metadata['cloth'] ?? ''),
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

/**
 * Render a responsive photograph. Smaller renditions named <name>-1200.webp
 * and <name>-800.webp beside the file are offered through srcset when they
 * exist; width and height come from the file so the layout does not jump.
 */
function studioImage(string $path, string $alt, string $sizes, bool $eager = false): string
{
	$staticDir = rtrim(getConf('STATIC_DIR') ?: './static', '/');
	$file = $staticDir . '/' . ltrim($path, '/');
	$size = is_file($file) ? @getimagesize($file) : false;
	$dimensions = $size ? ' width="' . (int) $size[0] . '" height="' . (int) $size[1] . '"' : '';
	$candidates = [];
	$extension = pathinfo($path, PATHINFO_EXTENSION);
	$stem = substr($path, 0, -strlen($extension) - 1);
	foreach([800, 1200] as $width)
	{
		$variant = $stem . '-' . $width . '.' . $extension;
		if(is_file($staticDir . '/' . ltrim($variant, '/')))
		{
			$candidates[] = escapeHtml(resolveAssetUrl($variant)) . ' ' . $width . 'w';
		}
	}
	if($candidates !== [] && $size)
	{
		$candidates[] = escapeHtml(resolveAssetUrl($path)) . ' ' . (int) $size[0] . 'w';
	}
	$srcset = $candidates !== [] ? ' srcset="' . implode(', ', $candidates) . '" sizes="' . escapeHtml($sizes) . '"' : '';
	$loading = $eager ? ' fetchpriority="high"' : ' loading="lazy"';
	return '<img src="' . escapeHtml(resolveAssetUrl($path)) . '"' . $srcset . $dimensions . ' alt="' . escapeHtml($alt) . '"' . $loading . ' decoding="async">';
}

/** A project's photograph, or a plain cloth-coloured tile when it has none yet. */
function studioCover(array $project, string $sizes, bool $eager = false): string
{
	if($project['image'] !== '')
	{
		return studioImage($project['image'], $project['imageAlt'] !== '' ? $project['imageAlt'] : $project['title'], $sizes, $eager);
	}
	$cloth = studioColour($project['cloth'], 'var(--accent)');
	return '<span class="cover-plain" role="img" aria-label="' . escapeHtml($project['title']) . '" style="--cloth: ' . $cloth . '"><span>' . escapeHtml($project['title']) . '</span></span>';
}

/** Escape text for the page body and double dollars so Pandoc leaves them alone. */
function tpl(string $value): string
{
	return str_replace('$', '$$', escapeHtml($value));
}

$projects = studioProjects();
$project = null;
$projectIndex = null;
if($layout === 'project')
{
	$slug = basename($source, '.md');
	foreach($projects as $index => $candidate)
	{
		if($candidate['slug'] === $slug) { $project = $candidate; $projectIndex = $index; }
	}
}
$facts = is_array($frontmatter['facts'] ?? null) ? $frontmatter['facts'] : [];
$gallery = is_array($frontmatter['gallery'] ?? null) ? $frontmatter['gallery'] : [];
$show = (int) ($frontmatter['show'] ?? 5);
$navigation = [['href' => '/work/', 'label' => 'Work', 'match' => 'work/'], ['href' => '/about.html', 'label' => 'About', 'match' => 'about.md'], ['href' => '/contact.html', 'label' => 'Contact', 'match' => 'contact.md']];

/** Render one tile of the work grid. */
function studioTile(array $item, bool $featured): string
{
	$sizes = $featured ? '(max-width: 44rem) 100vw, min(100vw - 3rem, 78rem)' : '(max-width: 44rem) 100vw, min(50vw - 2.5rem, 38rem)';
	$meta = trim($item['kind'] . ($item['year'] !== '' ? ' · ' . $item['year'] : ''), ' ·');
	return '<li class="work-item' . ($featured ? ' work-item--featured' : '') . '">'
		. '<a href="' . escapeHtml(resolveAssetUrl('/work/' . $item['slug'] . '.html')) . '">'
		. '<span class="work-photo">' . studioCover($item, $sizes, $featured) . '</span>'
		. '<span class="work-meta"><strong>' . escapeHtml($item['title']) . '</strong>'
		. ($meta !== '' ? '<span>' . escapeHtml($meta) . '</span>' : '')
		. ($featured && $item['description'] !== '' ? '<em>' . tpl($item['description']) . '</em>' : '')
		. '</span></a></li>';
}

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

	<?php if($layout === 'home'): ?>
	<main id="content" class="page">
		<section class="statement prose">
			$body$
		</section>
		<?php if(!empty($frontmatter['hero'])): ?>
		<figure class="hero">
			<?=studioImage((string) $frontmatter['hero'], (string) ($frontmatter['heroAlt'] ?? ''), '(max-width: 44rem) 100vw, min(100vw - 3rem, 78rem)', true)?>
			<?php if(!empty($frontmatter['heroCaption'])): ?><figcaption><?=tpl((string) $frontmatter['heroCaption'])?></figcaption><?php endif; ?>
		</figure>
		<?php endif; ?>
		<section class="work" aria-labelledby="work-title">
			<div class="section-head"><h2 id="work-title">Selected work</h2><?php if(count($projects) > $show): ?><a href="<?=escapeHtml(resolveAssetUrl('/work/'))?>">All work</a><?php endif; ?></div>
			<ul class="work-grid">
				<?php foreach(array_slice($projects, 0, $show) as $index => $item) echo studioTile($item, $index === 0); ?>
			</ul>
		</section>
	</main>
	<?php elseif($layout === 'work'): ?>
	<main id="content" class="page">
		<section class="statement prose">
			$body$
		</section>
		<ul class="work-grid work-grid--all">
			<?php foreach($projects as $item) echo studioTile($item, false); ?>
		</ul>
	</main>
	<?php elseif($layout === 'project'): ?>
	<main id="content" class="page">
		<article class="project">
			<header class="project-head">
				<p class="kicker"><a href="<?=escapeHtml(resolveAssetUrl('/work/'))?>">Work</a><?php if($project !== null && $project['kind'] !== ''): ?> · <?=escapeHtml($project['kind'])?><?php endif; ?><?php if($project !== null && $project['year'] !== ''): ?> · <?=escapeHtml($project['year'])?><?php endif; ?></p>
				<h1>$title$</h1>
				<?php if(!empty($frontmatter['description'])): ?><p class="standfirst"><?=tpl((string) $frontmatter['description'])?></p><?php endif; ?>
			</header>
			<?php if($project !== null): ?>
			<figure class="project-photo"><?=studioCover($project, '(max-width: 44rem) 100vw, min(100vw - 3rem, 78rem)', true)?></figure>
			<?php endif; ?>
			<div class="project-body">
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
			</div>
			<?php if($gallery !== []): ?>
			<ul class="gallery">
				<?php foreach($gallery as $entry): if(!is_array($entry) || empty($entry['image'])) continue; ?>
				<li><figure><?=studioImage((string) $entry['image'], (string) ($entry['alt'] ?? ''), '(max-width: 44rem) 100vw, min(50vw - 2.5rem, 38rem)')?><?php if(!empty($entry['caption'])): ?><figcaption><?=tpl((string) $entry['caption'])?></figcaption><?php endif; ?></figure></li>
				<?php endforeach; ?>
			</ul>
			<?php endif; ?>
			<?php if($projectIndex !== null && count($projects) > 1): $next = $projects[($projectIndex + 1) % count($projects)]; ?>
			<nav class="next" aria-label="Next project">
				<a href="<?=escapeHtml(resolveAssetUrl('/work/' . $next['slug'] . '.html'))?>"><span class="kicker">Next</span><strong><?=escapeHtml($next['title'])?></strong><span><?=escapeHtml(trim($next['kind'] . ($next['year'] !== '' ? ' · ' . $next['year'] : ''), ' ·'))?></span></a>
			</nav>
			<?php endif; ?>
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
		<p class="colophon-links"><a href="<?=escapeHtml(resolveAssetUrl('/contact.html'))?>">Contact</a><a href="https://seanmorris.github.io/smgen-extras/themes.html#theme-portfolio">Portfolio theme</a></p>
	</footer>
</body>
</html>
