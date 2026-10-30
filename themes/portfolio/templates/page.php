<?php

/**
 * Renders Halden Bindery as a catalogue set like a fine-press book: running
 * heads, numbered entries, photographs as captioned plates, a specification
 * block on each project page, and a colophon. Projects are the files under
 * pages/work/; the front page lists the newest few and the work page all of
 * them.
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
		];
	}
	usort($projects, static fn(array $a, array $b): int => [$b['year'], $a['weight'], $a['title']] <=> [$a['year'], $b['weight'], $b['title']]);
	foreach($projects as $index => $project) $projects[$index]['number'] = $index + 1;
	return $projects;
}

/** Roman numerals for catalogue and plate numbers. */
function studioRoman(int $number): string
{
	$map = ['M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400, 'C' => 100, 'XC' => 90, 'L' => 50, 'XL' => 40, 'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1];
	$out = '';
	foreach($map as $roman => $value) while($number >= $value) { $out .= $roman; $number -= $value; }
	return $out === '' ? '0' : $out;
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

/** Escape text for the page body and double dollars so Pandoc leaves them alone. */
function tpl(string $value): string
{
	return str_replace('$', '$$', escapeHtml($value));
}

/** One numbered entry of the catalogue. */
function studioEntry(array $item): string
{
	$href = escapeHtml(resolveAssetUrl('/work/' . $item['slug'] . '.html'));
	$meta = trim($item['kind'] . ($item['year'] !== '' ? ' · ' . $item['year'] : ''), ' ·');
	$plate = $item['image'] !== ''
		? '<a class="entry-plate" href="' . $href . '" tabindex="-1" aria-hidden="true">' . studioImage($item['image'], '', '(max-width: 44rem) 100vw, 16rem') . '</a>'
		: '';
	return '<li class="entry">'
		. '<span class="entry-number">No. ' . studioRoman((int) $item['number']) . '</span>'
		. '<div class="entry-text">'
		. '<h3 class="entry-title"><a href="' . $href . '">' . escapeHtml($item['title']) . '</a></h3>'
		. ($meta !== '' ? '<p class="entry-meta">' . escapeHtml($meta) . '</p>' : '')
		. ($item['description'] !== '' ? '<p class="entry-description">' . tpl($item['description']) . '</p>' : '')
		. '</div>'
		. $plate
		. '</li>';
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
$show = (int) ($frontmatter['show'] ?? 4);
$navigation = [['href' => '/work/', 'label' => 'Work', 'match' => 'work/'], ['href' => '/about.html', 'label' => 'About', 'match' => 'about.md'], ['href' => '/contact.html', 'label' => 'Contact', 'match' => 'contact.md']];
$section = 'Home';
foreach($navigation as $link) if(str_starts_with($source, $link['match'])) $section = $link['label'];
if($layout === 'project') $section = 'Work';

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
	<header class="running-head">
		<a class="wordmark" href="<?=escapeHtml(resolveAssetUrl('/'))?>"><?=escapeHtml(getConf('PRODUCT_NAME'))?></a>
		<span class="running-section" aria-hidden="true"><?=escapeHtml($section)?></span>
		<nav class="nav" aria-label="Site">
			<?php foreach($navigation as $link): ?>
			<a href="<?=escapeHtml(resolveAssetUrl($link['href']))?>"<?=str_starts_with($source, $link['match']) ? ' aria-current="page"' : ''?>><?=escapeHtml($link['label'])?></a>
			<?php endforeach; ?>
			<button type="button" class="theme-toggle" data-theme-toggle aria-label="Switch to dark theme" aria-pressed="false">
				<svg class="theme-icon theme-icon--moon" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" focusable="false"><path d="M20.5 14.2A8.5 8.5 0 0 1 9.8 3.5a8.5 8.5 0 1 0 10.7 10.7z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
				<svg class="theme-icon theme-icon--sun" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M12 2.5v2.5M12 19v2.5M2.5 12H5M19 12h2.5M5.3 5.3l1.8 1.8M16.9 16.9l1.8 1.8M5.3 18.7l1.8-1.8M16.9 7.1l1.8-1.8" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
			</button>
		</nav>
	</header>

	<?php if($layout === 'home' || $layout === 'work'): ?>
	<main id="content" class="page">
		<section class="statement prose">
			$body$
		</section>
		<p class="fleuron" aria-hidden="true">❦</p>
		<section class="catalogue-section" aria-labelledby="catalogue-title">
			<h2 id="catalogue-title" class="section-title"><?=$layout === 'home' ? 'Selected work' : 'All entries, newest first'?></h2>
			<ol class="catalogue">
				<?php foreach($layout === 'home' ? array_slice($projects, 0, $show) : $projects as $item) echo studioEntry($item); ?>
			</ol>
			<?php if($layout === 'home' && count($projects) > $show): ?>
			<p class="catalogue-more"><a href="<?=escapeHtml(resolveAssetUrl('/work/'))?>">The full catalogue, <?=count($projects)?> entries</a></p>
			<?php endif; ?>
		</section>
	</main>
	<?php elseif($layout === 'project'): ?>
	<main id="content" class="page">
		<article class="project">
			<header class="title-page">
				<?php if($project !== null): ?><p class="entry-number">No. <?=studioRoman((int) $project['number'])?><?php if($project['kind'] !== ''): ?> · <?=escapeHtml($project['kind'])?><?php endif; ?><?php if($project['year'] !== ''): ?> · <?=escapeHtml($project['year'])?><?php endif; ?></p><?php endif; ?>
				<h1>$title$</h1>
				<?php if(!empty($frontmatter['description'])): ?><p class="standfirst"><?=tpl((string) $frontmatter['description'])?></p><?php endif; ?>
			</header>
			<?php $plateNumber = 0; if($project !== null && $project['image'] !== ''): $plateNumber++; ?>
			<figure class="plate">
				<?=studioImage($project['image'], $project['imageAlt'] !== '' ? $project['imageAlt'] : $project['title'], '(max-width: 44rem) 100vw, 44rem', true)?>
				<figcaption><span class="plate-number">Plate <?=studioRoman($plateNumber)?></span><?=tpl($project['imageAlt'] !== '' ? $project['imageAlt'] : $project['title'])?></figcaption>
			</figure>
			<?php endif; ?>
			<?php if($facts !== []): ?>
			<dl class="spec" aria-label="Specification">
				<?php foreach($facts as $label => $value): ?>
				<div><dt><?=tpl((string) $label)?></dt><dd><?=tpl((string) $value)?></dd></div>
				<?php endforeach; ?>
			</dl>
			<?php endif; ?>
			<div class="prose prose--dropcap">
				$body$
			</div>
			<?php foreach($gallery as $entry): if(!is_array($entry) || empty($entry['image'])) continue; $plateNumber++; ?>
			<figure class="plate">
				<?=studioImage((string) $entry['image'], (string) ($entry['alt'] ?? ''), '(max-width: 44rem) 100vw, 44rem')?>
				<figcaption><span class="plate-number">Plate <?=studioRoman($plateNumber)?></span><?=tpl((string) ($entry['caption'] ?? $entry['alt'] ?? ''))?></figcaption>
			</figure>
			<?php endforeach; ?>
			<?php if($projectIndex !== null && count($projects) > 1): $next = $projects[($projectIndex + 1) % count($projects)]; ?>
			<nav class="next" aria-label="Next entry">
				<p class="fleuron" aria-hidden="true">❦</p>
				<p class="next-label">Next in the catalogue</p>
				<a href="<?=escapeHtml(resolveAssetUrl('/work/' . $next['slug'] . '.html'))?>"><span class="entry-number">No. <?=studioRoman((int) $next['number'])?></span><?=escapeHtml($next['title'])?></a>
			</nav>
			<?php endif; ?>
		</article>
	</main>
	<?php else: ?>
	<main id="content" class="page page--prose">
		<article class="prose prose--dropcap">
			$body$
		</article>
	</main>
	<?php endif; ?>

	<footer class="colophon">
		<p class="fleuron" aria-hidden="true">❦</p>
		<p class="colophon-name"><?=escapeHtml(getConf('PRODUCT_NAME'))?><?php if(getConf('TAGLINE')): ?> · <?=escapeHtml(getConf('TAGLINE'))?><?php endif; ?></p>
		<p class="colophon-note">Set in the reader's own serif and built with <a href="https://seanmorris.github.io/smgen/">SMGen</a> and the <a href="https://seanmorris.github.io/smgen-extras/themes.html#theme-portfolio">portfolio theme</a>. <a href="<?=escapeHtml(resolveAssetUrl('/contact.html'))?>">Contact</a>.</p>
	</footer>
</body>
</html>
