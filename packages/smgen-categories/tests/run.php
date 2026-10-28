<?php

declare(strict_types=1);

use SmgenCategories\Categories;

require_once(dirname(__DIR__) . '/src/autoload.php');

/**
 * Exercises grouping, date rollups, thresholds, templates, and output isolation.
 */
final class CategoriesRegression
{
	private string $tempRoot;

	/** Create a private test root, execute regressions, and remove fixtures. */
	public function run(): void
	{
		$this->tempRoot = sys_get_temp_dir() . '/smgen-categories-' . bin2hex(random_bytes(6));
		mkdir($this->tempRoot, 0700, true);
		try
		{
			$this->testValueGroupsIncludeListsAndGeneratedPages();
			$this->testDayGroupsHonourMinimumAndSort();
			$this->testDirectoryIndexOutputsPublishDirectoryUrls();
			$this->testDefinitionsFromPluginsAcceptBundledTemplates();
			$this->testInvalidConfigurationFails();
			$this->testUnsafeOutputsFail();
		}
		finally
		{
			$this->removeTree($this->tempRoot);
		}
		fwrite(STDOUT, "smgen-categories tests passed.\n");
	}

	/** Group by a scalar or list field across authored and plugin-generated pages. */
	private function testValueGroupsIncludeListsAndGeneratedPages(): void
	{
		$project = $this->project('values');
		mkdir($project . '/pages/posts', 0700, true);
		mkdir($project . '/pages/.smgen-generated/other/posts', 0700, true);
		$this->write($project . '/pages/posts/a.md', "---\ntitle: Alpha\ntopic: Walks\ndate: 2026-10-24T09:00:00Z\n---\n\n# A\n");
		$this->write($project . '/pages/posts/b.md', "---\ntitle: Beta\ntopic: [Walks, Tools]\ndate: 2026-10-25\n---\n\n# B\n");
		$this->write($project . '/pages/.smgen-generated/other/posts/c.md', "---\ntitle: Gamma\ntopic: Tools\n---\n\n# C\n");
		$this->write($project . '/pages/about.md', "---\ntitle: About\ntopic: Walks\n---\n\n# About\n");
		$this->write($project . '/pages/posts/d.md', "---\ntitle: Delta\nproduct:\n  category: Tools\n---\n\n# D\n");
		$this->write($project . '/pages/.smgen-generated/smgen-categories/topics/stale.md', "---\ntitle: Stale\ntopic: Walks\n---\n");
		$this->write($project . '/.smgen-categories.yaml', "version: 1\ncategories:\n  topic:\n    field: topic\n    pages: ['posts/**/*.md']\n    output: topics/{slug}.md\n");
		$outputs = (new Categories($project))->render($project . '/pages');
		$this->assertSame(['topics/tools.md', 'topics/walks.md'], array_keys($outputs), 'rendered paths');
		$nested = Categories::fromDefinitions($project, ['line' => ['field' => 'product.category', 'pages' => ['posts/**/*.md'], 'output' => 'lines/{slug}.md']])->render($project . '/pages');
		$this->assertSame(['lines/tools.md'], array_keys($nested), 'dotted field path');
		$this->assertContains('Delta', $nested['lines/tools.md'], 'nested member');
		$walks = $outputs['topics/walks.md'];
		$this->assertContains("title: Walks", $walks, 'default title');
		$this->assertContains("name: topic", $walks, 'category name');
		$this->assertContains("count: 2", $walks, 'walks count');
		$this->assertContains("- /posts/b.html", $walks, 'newest first by date');
		$this->assertTrue(strpos($walks, '/posts/b.html') < strpos($walks, '/posts/a.html'), 'date-desc order');
		$this->assertContains('<a href="/posts/c.html">Gamma</a>', $outputs['topics/tools.md'], 'generated page is a member');
		$prefixed = (new Categories($project, '.smgen-categories.yaml', 'https://example.test/site/'))->render($project . '/pages');
		$this->assertContains('<a href="https://example.test/site/posts/c.html">Gamma</a>', $prefixed['topics/tools.md'], 'base URL prefixes member links');
		$this->assertTrue(!str_contains($walks, 'About'), 'pages outside the globs are skipped');
		$this->assertTrue(!str_contains($walks, 'Stale'), 'own generated pages are skipped');
	}

	/** Group dated pages by day, drop days under the minimum, and sort ascending. */
	private function testDayGroupsHonourMinimumAndSort(): void
	{
		$project = $this->project('days');
		mkdir($project . '/pages', 0700, true);
		$this->write($project . '/pages/one.md', "---\ntitle: One\ndate: 2026-10-24T15:10:00Z\n---\n\n# One\n");
		$this->write($project . '/pages/two.md', "---\ntitle: Two\ndate: 2026-10-24T09:05:00Z\n---\n\n# Two\n");
		$this->write($project . '/pages/three.md', "---\ntitle: Three\ndate: 2026-10-11\n---\n\n# Three\n");
		$this->write($project . '/day.md.php', "<?=\$context->frontmatter(['layout' => 'day'])?># <?=\$category['label']?> (<?=\$category['value']?>)\n<?php foreach(\$members as \$member): ?>\n- <?=\$member['title']?>\n<?php endforeach; ?>\n");
		$this->write($project . '/.smgen-categories.yaml', "version: 1\ncategories:\n  day:\n    field: date\n    group: day\n    minimum: 2\n    sort: date-asc\n    template: day.md.php\n    output: archive/{value}.md\n");
		$outputs = (new Categories($project))->render($project . '/pages');
		$this->assertSame(['archive/2026-10-24.md'], array_keys($outputs), 'single multi-post day');
		$page = $outputs['archive/2026-10-24.md'];
		$this->assertContains('# October 24, 2026 (2026-10-24)', $page, 'day label');
		$this->assertContains("layout: day", $page, 'template frontmatter kept');
		$this->assertContains("title: October 24, 2026", $page, 'default title merged');
		$this->assertTrue(strpos($page, '- Two') < strpos($page, '- One'), 'date-asc order');
		mkdir($project . '/generated');
		$this->assertSame(1, (new Categories($project))->generate($project . '/pages', 'generated'), 'generated count');
		$this->assertTrue(is_file($project . '/generated/archive/2026-10-24.md'), 'generated rollup page');
	}

	/** A rollup may be the index of its own directory and members keep directory URLs. */
	private function testDirectoryIndexOutputsPublishDirectoryUrls(): void
	{
		$project = $this->project('index');
		mkdir($project . '/pages/guides/intro', 0700, true);
		$this->write($project . '/pages/guides/intro/index.md', "---\ntitle: Intro\nsection: Guides\n---\n");
		$this->write($project . '/pages/guides/next.md', "---\ntitle: Next\nsection: Guides\n---\n");
		$this->write($project . '/.smgen-categories.yaml', "version: 1\ncategories:\n  section:\n    field: section\n    sort: title\n    output: sections/{slug}/index.md\n");
		$outputs = (new Categories($project))->render($project . '/pages');
		$this->assertSame(['sections/guides/index.md'], array_keys($outputs), 'directory index output');
		$this->assertContains('<a href="/guides/intro/">Intro</a>', $outputs['sections/guides/index.md'], 'index member uses its directory URL');
		$this->assertContains('<a href="/guides/next.html">Next</a>', $outputs['sections/guides/index.md'], 'page member uses its html URL');
	}

	/** Plugins may pass definitions with templates outside the project. */
	private function testDefinitionsFromPluginsAcceptBundledTemplates(): void
	{
		$project = $this->project('library');
		mkdir($project . '/pages', 0700, true);
		$this->write($project . '/pages/post.md', "---\ntitle: Post\ndate: 2026-01-01\n---\n");
		$this->write($project . '/pages/other.md', "---\ntitle: Other\ndate: 2026-01-01\n---\n");
		$template = $this->tempRoot . '/bundled.md.php';
		$this->write($template, "<?=\$context->frontmatter([])?>bundled <?=\$category['value']?>\n");
		$categories = Categories::fromDefinitions($project, ['day' => ['field' => 'date', 'group' => 'day', 'minimum' => 2, 'output' => 'days/{value}.md', 'template' => $template]]);
		$outputs = $categories->render($project . '/pages', 'smgen-timeline');
		$this->assertSame(['days/2026-01-01.md'], array_keys($outputs), 'library definition output');
		$this->assertContains('bundled 2026-01-01', $outputs['days/2026-01-01.md'], 'bundled template ran');
		$this->assertFails(static fn() => new Categories($project, '.smgen-categories.yaml'), 'Configuration must be a regular file');
	}

	/** Reject unknown fields, bad groups, and missing placeholders. */
	private function testInvalidConfigurationFails(): void
	{
		$project = $this->project('invalid');
		mkdir($project . '/pages', 0700, true);
		$this->write($project . '/.smgen-categories.yaml', "version: 1\ncategories:\n  bad:\n    field: topic\n    group: week\n    output: x/{slug}.md\n");
		$this->assertFails(static fn() => new Categories($project), 'group must be one of');
		$this->write($project . '/.smgen-categories.yaml', "version: 1\ncategories:\n  bad:\n    field: topic\n    output: fixed.md\n");
		$this->assertFails(static fn() => new Categories($project), 'must contain {slug} or {value}');
		$this->write($project . '/.smgen-categories.yaml', "version: 1\ncategories:\n  bad:\n    field: topic\n    output: x/{slug}.md\n    colour: red\n");
		$this->assertFails(static fn() => new Categories($project), 'Unknown category bad field: colour');
		$this->write($project . '/pages/p.md', "---\ntitle: P\nwhen: not-a-date\n---\n");
		$this->write($project . '/.smgen-categories.yaml', "version: 1\ncategories:\n  bad:\n    field: when\n    group: month\n    output: x/{slug}.md\n");
		$this->assertFails(static fn() => (new Categories($project))->render($project . '/pages'), 'Invalid date in p.md');
	}

	/** Reject values that would escape the generated directory. */
	private function testUnsafeOutputsFail(): void
	{
		$project = $this->project('unsafe');
		mkdir($project . '/pages', 0700, true);
		$this->write($project . '/pages/p.md', "---\ntitle: P\ntopic: '../escape'\n---\n");
		$this->write($project . '/.smgen-categories.yaml', "version: 1\ncategories:\n  topic:\n    field: topic\n    output: t/{value}.md\n");
		$this->assertFails(static fn() => (new Categories($project))->render($project . '/pages'), 'not a safe output segment');
		$this->write($project . '/.smgen-categories.yaml', "version: 1\ncategories:\n  topic:\n    field: topic\n    output: t/{slug}.md\n");
		$outputs = (new Categories($project))->render($project . '/pages');
		$this->assertSame(['t/escape.md'], array_keys($outputs), 'slug sanitises the value');
	}

	/** Create one isolated project directory. */
	private function project(string $name): string
	{
		$path = $this->tempRoot . '/' . $name;
		mkdir($path, 0700, true);
		return $path;
	}

	/** Write one fixture file, creating parents, and fail when the write is incomplete. */
	private function write(string $path, string $contents): void
	{
		$directory = dirname($path);
		if(!is_dir($directory))
		{
			mkdir($directory, 0700, true);
		}
		if(file_put_contents($path, $contents) !== strlen($contents))
		{
			throw new RuntimeException('Unable to write test fixture: ' . $path);
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

(new CategoriesRegression())->run();
