<?php

declare(strict_types=1);

use SmgenTimeline\PluginHook;
use SmgenTimeline\Timeline;

require_once(dirname(__DIR__) . '/src/autoload.php');

if(!function_exists('navigationPutPageLink'))
{
	/** Minimal stand-in for SMGen's link registry helper. */
	function navigationPutPageLink(array &$links, string $menu, array $page, array $settings, string $context): void
	{
		$links[$menu][$page['id']] = ['id' => $page['id'], 'type' => 'page', 'source' => $page['source'], 'path' => $page['path'], 'title' => $page['title']];
	}
}

/**
 * Exercises compilation, rollup resolution, rendering, membership, and day page generation.
 */
final class TimelineRegression
{
	private string $tempRoot;

	/** Create a private test root, execute regressions, and remove fixtures. */
	public function run(): void
	{
		$this->tempRoot = sys_get_temp_dir() . '/smgen-timeline-' . bin2hex(random_bytes(6));
		mkdir($this->tempRoot, 0700, true);
		try
		{
			$this->testCompileGroupsAndResolvesRollups();
			$this->testRenderLinksCalendarCellsToRollupsOrPosts();
			$this->testAlterHookSelectsDatedPages();
			$this->testPrepareGeneratesDayRollupsThroughCategories();
			$this->testCompileStampsFallbackDates();
			$this->testFinalizeWritesPartialsIntoTheStage();
		}
		finally
		{
			$this->removeTree($this->tempRoot);
		}
		fwrite(STDOUT, "smgen-timeline tests passed.\n");
	}

	/** Group newest first and attach rollup paths only to multi-post days that have one. */
	private function testCompileGroupsAndResolvesRollups(): void
	{
		$catalog = $this->catalog();
		$menu = Timeline::compile(['name' => 'archive', 'renderer' => 'timeline', 'label' => 'Archive'], $this->links($catalog), $catalog, []);
		$years = $menu['timeline']['years'];
		$this->assertSame(['2026', '2025'], array_column($years, 'year'), 'years newest first');
		$october = $years[0]['months'][0];
		$this->assertSame('2026-10', $october['month'], 'newest month first');
		$this->assertSame(['/posts/late.html', '/posts/early.html', '/posts/solo.html'], array_column($october['pages'], 'path'), 'pages newest first');
		$this->assertSame('/archive/2026-10-24.html', $october['days']['2026-10-24']['path'], 'multi-post day uses its rollup');
		$this->assertTrue(!empty($october['days']['2026-10-24']['rollup']), 'rollup flag');
		$this->assertSame('/posts/solo.html', $october['days']['2026-10-11']['path'], 'single-post day links to the post');
		$this->assertSame(['/smgen-timeline/timeline.css'], Timeline::assets($menu), 'stylesheet when populated');
		$this->assertSame([], Timeline::assets(['timeline' => ['years' => []]]), 'no stylesheet when empty');
	}

	/** Render the calendar cell and list markup. */
	private function testRenderLinksCalendarCellsToRollupsOrPosts(): void
	{
		$catalog = $this->catalog();
		$menu = Timeline::compile(['name' => 'archive', 'renderer' => 'timeline', 'label' => 'Site archive'], $this->links($catalog), $catalog, []);
		$html = $this->capture(static fn() => Timeline::render($menu, 'archive', ['currentPage' => '/posts/early.html']));
		$this->assertContains('aria-label="Site archive"', $html, 'label');
		$this->assertContains('<td class="has-posts has-rollup"><a href="/archive/2026-10-24.html" aria-label="October 24, 2026: 2 posts" aria-current="date">24<span class="archive-day-count" aria-hidden="true">2</span></a></td>', $html, 'rollup cell');
		$this->assertContains('<td class="has-posts"><a href="/posts/solo.html" aria-label="October 11, 2026: 1 post" >11</a></td>', $html, 'single cell');
		$this->assertContains('<details open data-id-path="archive-archive-2026" data-contains-current="true">', $html, 'current year open');
		$this->assertContains('<details open data-id-path="archive-archive-2026-10" data-contains-current="true">', $html, 'current month open');
		$this->assertContains('<details  data-id-path="archive-archive-2025">', $html, 'other year closed');
		$this->assertContains('<a href="/posts/early.html" class="active-link" aria-current="page"><time datetime="2026-10-24">24</time><span>Early</span></a>', $html, 'active list entry');
		$this->assertSame(2, substr_count($html, '<time datetime="2026-10-24">24</time>'), 'one list line per post');
		$this->assertSame(1, substr_count($html, '<table>'), 'one month embedded per page');
		$this->assertContains('<div class="archive-calendar" data-calendar-menu="archive">', $html, 'calendar names its menu');
		$this->assertContains('<strong aria-live="polite">October 2026</strong>', $html, 'current month shown');
		$this->assertContains('<a rel="prev" class="archive-calendar-step" href="/posts/sept.html" data-calendar="/smgen-timeline/calendar/archive/2026-09.html" aria-label="Show September 2026">', $html, 'older month step links into the month and names its partial');
		$this->assertContains('<p class="archive-calendar-years" aria-label="Jump to a year"><a href="/archive/2026-10-24.html" data-calendar="/smgen-timeline/calendar/archive/2026-10.html" aria-label="Show October 2026" aria-current="true">2026</a><a href="/posts/old.html" data-calendar="/smgen-timeline/calendar/archive/2025-03.html" aria-label="Show March 2025">2025</a></p>', $html, 'year chips jump to the newest month of each year');

		$partials = Timeline::partials($menu);
		$this->assertSame(['/smgen-timeline/calendar/archive/2026-10.html', '/smgen-timeline/calendar/archive/2026-09.html', '/smgen-timeline/calendar/archive/2025-03.html'], array_keys($partials), 'one partial per month with posts');
		$september = $partials['/smgen-timeline/calendar/archive/2026-09.html'];
		$this->assertContains('<strong aria-live="polite">September 2026</strong>', $september, 'partial heading');
		$this->assertContains('rel="next" class="archive-calendar-step" href="/archive/2026-10-24.html" data-calendar="/smgen-timeline/calendar/archive/2026-10.html"', $september, 'partial steps forward');
		$this->assertContains('<td class="has-posts"><a href="/posts/sept.html" aria-label="September 18, 2026: 1 post" >18</a></td>', $september, 'partial day cell');
		$this->assertTrue(!str_contains($september, 'aria-current="date"'), 'partials carry no current page');
		$this->assertTrue(!str_contains($partials['/smgen-timeline/calendar/archive/2025-03.html'], 'rel="prev"'), 'oldest month has no previous step');

		$rollupView = $this->capture(static fn() => Timeline::render($menu, 'archive', ['currentPage' => '/archive/2026-10-24.html']));
		$this->assertContains('data-id-path="archive-archive-2026-10" data-contains-current="true"', $rollupView, 'rollup page counts as current for its month');
	}

	/** The finalize hook writes every month partial under the staged output with base-URL links. */
	private function testFinalizeWritesPartialsIntoTheStage(): void
	{
		$catalog = $this->catalog();
		$menu = Timeline::compile(['name' => 'archive', 'renderer' => 'timeline', 'label' => 'Archive'], $this->links($catalog), $catalog, []);
		$stage = $this->tempRoot . '/stage';
		mkdir($stage, 0700, true);
		$navigation = $this->tempRoot . '/navigation.json';
		file_put_contents($navigation, json_encode(['menus' => ['archive' => $menu, 'primary' => ['renderer' => 'flat']]], JSON_THROW_ON_ERROR));
		$count = PluginHook::writePartials(['paths' => ['stage' => $stage], 'site' => ['baseUrl' => 'https://example.test/blog/']], $navigation);
		Timeline::setBaseUrl('');
		$this->assertSame(3, $count, 'three partials written');
		$september = (string) file_get_contents($stage . '/smgen-timeline/calendar/archive/2026-09.html');
		$this->assertContains('href="https://example.test/blog/posts/sept.html"', $september, 'partial links carry the base URL');
		$this->assertContains('data-calendar="https://example.test/blog/smgen-timeline/calendar/archive/2026-10.html"', $september, 'partial references carry the base URL');
		$this->assertFails(static fn() => PluginHook::writePartials(['paths' => ['stage' => $stage]], $stage . '/missing.json'), 'SMGEN_NAV_DATA');
	}

	/** The alter hook adds dated pages and skips opt-outs, explicit menus, and rollups. */
	private function testAlterHookSelectsDatedPages(): void
	{
		$alter = require(dirname(__DIR__) . '/helpers/alter.php');
		$catalog = $this->catalog();
		$catalog['pages']['hidden.md'] = $this->page('hidden.md', 'Hidden', ['date' => '2026-10-24', 'archive' => false]);
		$catalog['pages']['explicit.md'] = $this->page('explicit.md', 'Explicit', ['date' => '2026-10-24', 'menus' => ['archive' => true]]);
		$catalog['pages']['undated.md'] = $this->page('undated.md', 'Undated', []);
		$registry = ['menus' => ['archive' => ['renderer' => 'timeline'], 'primary' => ['renderer' => 'flat']], 'links' => ['archive' => [], 'primary' => []], 'regions' => []];
		$alter($registry, $catalog, []);
		$ids = array_keys($registry['links']['archive']);
		sort($ids);
		$this->assertSame(['page:posts/early.md', 'page:posts/late.md', 'page:posts/old.md', 'page:posts/sept.md', 'page:posts/solo.md'], $ids, 'dated pages joined');
		$this->assertSame([], $registry['links']['primary'], 'flat menus untouched');
	}

	/** The prepare hook writes a rollup for every day with two or more dated pages. */
	private function testPrepareGeneratesDayRollupsThroughCategories(): void
	{
		$project = $this->tempRoot . '/site';
		mkdir($project . '/pages/posts', 0700, true);
		mkdir($project . '/templates', 0700, true);
		file_put_contents($project . '/pages/posts/late.md', "---\ntitle: Late\ndate: 2026-10-24T15:10:00Z\ndescription: Later that day.\n---\n");
		file_put_contents($project . '/pages/posts/early.md', "---\ntitle: Early\ndate: 2026-10-24T09:05:00Z\n---\n");
		file_put_contents($project . '/pages/posts/solo.md', "---\ntitle: Solo\ndate: 2026-10-11\n---\n");
		file_put_contents($project . '/pages/posts/hidden.md', "---\ntitle: Hidden\ndate: 2026-10-24\narchive: false\n---\n");
		$count = PluginHook::generate($project, $project . '/pages', $project . '/pages/.smgen-generated/smgen-timeline', [], 'https://example.test/journal');
		$this->assertSame(1, $count, 'one rollup');
		$rollup = (string) file_get_contents($project . '/pages/.smgen-generated/smgen-timeline/archive/2026-10-24.md');
		$this->assertContains('name: timeline-day', $rollup, 'category name');
		$this->assertContains('value: "2026-10-24"', $rollup, 'category value');
		$this->assertContains('# October 24, 2026', $rollup, 'default template heading');
		$this->assertContains('<a href="https://example.test/journal/posts/late.html">Late</a> — Later that day.', $rollup, 'member with description links through the base URL');
		$this->assertTrue(!str_contains($rollup, 'Hidden'), 'archive: false pages are left out');
		$this->assertTrue(strpos($rollup, 'Late') < strpos($rollup, 'Early'), 'newest first');

		file_put_contents($project . '/templates/day.md.php', "<?=\$context->frontmatter(['layout' => 'day'])?>custom <?=\$category['value']?>\n");
		$this->removeTree($project . '/pages/.smgen-generated');
		PluginHook::generate($project, $project . '/pages', $project . '/pages/.smgen-generated/smgen-timeline', ['template' => 'templates/day.md.php', 'output' => 'days/{value}/index.md']);
		$custom = (string) file_get_contents($project . '/pages/.smgen-generated/smgen-timeline/days/2026-10-24/index.md');
		$this->assertContains('custom 2026-10-24', $custom, 'site template used');
		$this->assertContains('layout: day', $custom, 'site frontmatter kept');
		$this->assertFails(static fn() => PluginHook::definition($project, ['colour' => 'red']), 'Unknown smgen-timeline option');
		$this->assertFails(static fn() => PluginHook::definition($project, ['output' => 'days.md']), 'must contain {value}');
	}

	/** A page without a date gets a fallback timestamp reported for stamping. */
	private function testCompileStampsFallbackDates(): void
	{
		$file = $this->tempRoot . '/undated.md';
		file_put_contents($file, "---\ntitle: Undated\n---\n");
		$catalog = ['pages' => ['undated.md' => $this->page('undated.md', 'Undated', [], $file)]];
		$stamped = [];
		$menu = Timeline::compile(['name' => 'archive', 'renderer' => 'timeline'], $this->links($catalog), $catalog, ['stampCreated' => static function(string $path, int $timestamp) use (&$stamped): void {
			$stamped[$path] = $timestamp;
		}]);
		$this->assertTrue(isset($stamped[$file]), 'fallback reported for stamping');
		$this->assertSame(1, $menu['timeline']['years'][0]['count'], 'undated page still listed');
	}

	/** Build a catalog with two posts on one day, one solo post, one older post, and a rollup. */
	private function catalog(): array
	{
		return ['pages' => [
			'posts/late.md' => $this->page('posts/late.md', 'Late', ['date' => '2026-10-24T15:10:00Z']),
			'posts/early.md' => $this->page('posts/early.md', 'Early', ['date' => '2026-10-24T09:05:00Z']),
			'posts/solo.md' => $this->page('posts/solo.md', 'Solo', ['date' => '2026-10-11']),
			'posts/old.md' => $this->page('posts/old.md', 'Old', ['date' => '2025-03-02']),
			'posts/sept.md' => $this->page('posts/sept.md', 'September', ['date' => '2026-09-18']),
			'.smgen-generated/smgen-timeline/archive/2026-10-24.md' => $this->page('.smgen-generated/smgen-timeline/archive/2026-10-24.md', 'October 24, 2026', ['category' => ['name' => 'timeline-day', 'value' => '2026-10-24']], null, '/archive/2026-10-24.html'),
		]];
	}

	/** Build one catalog page record. */
	private function page(string $source, string $title, array $metadata, ?string $absolute = null, ?string $path = null): array
	{
		return [
			'id' => 'page:' . $source,
			'source' => $source,
			'absolute' => $absolute ?? $this->tempRoot . '/' . $source,
			'path' => $path ?? '/' . preg_replace('/\.md$/', '.html', $source),
			'title' => $title,
			'metadata' => $metadata,
		];
	}

	/** Build menu links for every dated, non-rollup catalog page. */
	private function links(array $catalog): array
	{
		$links = [];
		foreach($catalog['pages'] as $page)
		{
			if(is_array($page['metadata']['category'] ?? null))
			{
				continue;
			}
			$links[] = ['id' => $page['id'], 'type' => 'page', 'source' => $page['source'], 'path' => $page['path'], 'title' => $page['title']];
		}
		return $links;
	}

	/** Capture rendered output. */
	private function capture(callable $callback): string
	{
		ob_start();
		try
		{
			$callback();
			return (string) ob_get_contents();
		}
		finally
		{
			ob_end_clean();
		}
	}

	/** Assert that a callback fails with a diagnostic containing expected text. */
	private function assertFails(callable $callback, string $expected): void
	{
		try
		{
			$callback();
		}
		catch(Throwable $error)
		{
			$this->assertContains($expected, $error->getMessage(), 'failure message');
			return;
		}
		throw new RuntimeException('Expected callback to fail with: ' . $expected);
	}

	/** Assert strict equality and include both values in a useful failure. */
	private function assertSame(mixed $expected, mixed $actual, string $label): void
	{
		if($expected !== $actual)
		{
			throw new RuntimeException($label . ' mismatch: ' . var_export($actual, true));
		}
	}

	/** Assert that a string contains one exact fragment. */
	private function assertContains(string $needle, string $haystack, string $label): void
	{
		if(!str_contains($haystack, $needle))
		{
			throw new RuntimeException($label . ' does not contain ' . $needle . ': ' . $haystack);
		}
	}

	/** Assert a boolean condition while retaining a focused failure label. */
	private function assertTrue(bool $condition, string $label): void
	{
		if(!$condition)
		{
			throw new RuntimeException('Assertion failed: ' . $label);
		}
	}

	/** Remove generated test directories without following symbolic links. */
	private function removeTree(string $path): void
	{
		if(!file_exists($path) && !is_link($path))
		{
			return;
		}
		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
			RecursiveIteratorIterator::CHILD_FIRST,
		);
		foreach($iterator as $entry)
		{
			if($entry->isLink() || $entry->isFile())
			{
				unlink($entry->getPathname());
			}
			else
			{
				rmdir($entry->getPathname());
			}
		}
		rmdir($path);
	}
}

(new TimelineRegression())->run();
