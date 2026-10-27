<?php

declare(strict_types=1);

use SmgenDataPages\DataPages;

require_once(dirname(__DIR__) . '/src/autoload.php');

/**
 * Exercises parsing, validation, rendering, and output isolation regressions.
 */
final class DataPagesRegression
{
	private string $tempRoot;

	/** Create a private test root, execute regressions, and remove fixtures. */
	public function run(): void
	{
		$this->tempRoot = sys_get_temp_dir() . '/smgen-data-pages-' . bin2hex(random_bytes(6));
		mkdir($this->tempRoot, 0700, true);
		try
		{
			$this->testValidCsvPreservesStringsAndRawMarkup();
			$this->testTsvAndMultilineRowsUsePhysicalLineNumbers();
			$this->testMalformedRowsFail();
			$this->testDuplicateOutputsFail();
			$this->testUnsafeOutputSegmentsFail();
			$this->testSymlinkInputsFail();
			$this->testTemplateErrorsNameTheirRows();
		}
		finally
		{
			$this->removeTree($this->tempRoot);
		}
		fwrite(STDOUT, "smgen-data-pages tests passed.\n");
	}

	/** Parse inferred tab delimiters and report the line after a multiline record. */
	private function testTsvAndMultilineRowsUsePhysicalLineNumbers(): void
	{
		$project = $this->project('tsv');
		$this->basicTemplate($project);
		$this->write($project . '/data.tsv', "Code\tSlug\tDescription\tPublished\n1\tone\t\"Two\nlines\"\tyes\n2\ttwo\tonly-three\n");
		$this->writeConfig($project, 'products/{slug}.md', 'data.tsv');
		$this->assertFails(static fn() => (new DataPages($project))->render(), 'row 4 has 3 fields; expected 4');
	}

	/** Verify quoted fields, multiline values, filters, aliases, and collections. */
	private function testValidCsvPreservesStringsAndRawMarkup(): void
	{
		$project = $this->project('valid');
		$this->write($project . '/data.csv', "Code,Slug,Description,Published\n001,first,\"Hello, <em>world</em>\",yes\n002,second,\"Two\nlines\",no\n");
		$this->write($project . '/row.md.php', <<<'PHP'
<?=$context->frontmatter(['title' => $code, 'sourceLine' => $rowNumber])?>
# <?=$context->escapeHtml($code)?>

<?=$description?>
PHP
);
		$this->write($project . '/collection.md.php', <<<'PHP'
<?=$context->frontmatter(['title' => 'Catalog'])?>
# Catalog
<?php foreach($records as $record): ?>
- <?=$context->escapeHtml($record['row']['code'])?>
<?php endforeach; ?>
PHP
);
		$this->writeConfig($project, 'products/{slug}.md');
		$pages = new DataPages($project);
		$outputs = $pages->render();
		$this->assertSame(['products/first.md', 'products/index.md'], array_keys($outputs), 'rendered paths');
		$this->assertContains("title: \"001\"", $outputs['products/first.md'], 'leading-zero string');
		$this->assertContains('<em>world</em>', $outputs['products/first.md'], 'raw HTML');
		mkdir($project . '/generated');
		$this->assertSame(2, $pages->generate('generated'), 'generated count');
		$this->assertTrue(is_file($project . '/generated/products/first.md'), 'generated row page');
	}

	/** Reject a CSV record whose width differs from its header. */
	private function testMalformedRowsFail(): void
	{
		$project = $this->project('width');
		$this->basicTemplate($project);
		$this->write($project . '/data.csv', "Code,Slug,Description,Published\n1,one,missing\n");
		$this->writeConfig($project, 'products/{slug}.md');
		$this->assertFails(static fn() => (new DataPages($project))->render(), 'has 3 fields; expected 4');
	}

	/** Reject separate rows that resolve to the same generated Markdown path. */
	private function testDuplicateOutputsFail(): void
	{
		$project = $this->project('collision');
		$this->basicTemplate($project);
		$this->write($project . '/data.csv', "Code,Slug,Description,Published\n1,same,One,yes\n2,same,Two,yes\n");
		$this->writeConfig($project, 'products/{slug}.md');
		$this->assertFails(static fn() => (new DataPages($project))->render(), 'Generated output collision');
	}

	/** Reject spreadsheet values that could create nested or parent output paths. */
	private function testUnsafeOutputSegmentsFail(): void
	{
		$project = $this->project('unsafe-output');
		$this->basicTemplate($project);
		$this->write($project . '/data.csv', "Code,Slug,Description,Published\n1,../outside,One,yes\n");
		$this->writeConfig($project, 'products/{slug}.md');
		$this->assertFails(static fn() => (new DataPages($project))->render(), 'unsafe output segment');
	}

	/** Reject a configured source that reaches outside the project through a link. */
	private function testSymlinkInputsFail(): void
	{
		$project = $this->project('symlink');
		$this->basicTemplate($project);
		$outside = $this->tempRoot . '/outside.csv';
		$this->write($outside, "Code,Slug,Description,Published\n1,one,One,yes\n");
		symlink($outside, $project . '/data.csv');
		$this->writeConfig($project, 'products/{slug}.md');
		$this->assertFails(static fn() => (new DataPages($project))->render(), 'symbolic link');
	}

	/** Surface template warnings with the dataset and physical row number. */
	private function testTemplateErrorsNameTheirRows(): void
	{
		$project = $this->project('template-error');
		$this->write($project . '/data.csv', "Code,Slug,Description,Published\n1,one,One,yes\n");
		$this->write($project . '/row.md.php', '<?php trigger_error("broken template", E_USER_WARNING);');
		$this->write($project . '/collection.md.php', '# Catalog');
		$this->writeConfig($project, 'products/{slug}.md');
		$this->assertFails(static fn() => (new DataPages($project))->render(), 'products row 2: broken template');
	}

	/** Create an isolated project directory for one regression. */
	private function project(string $name): string
	{
		$path = $this->tempRoot . '/' . $name;
		mkdir($path, 0700, true);
		return $path;
	}

	/** Write the reusable row and collection templates for rejection tests. */
	private function basicTemplate(string $project): void
	{
		$this->write($project . '/row.md.php', '<?=$context->frontmatter(["title" => $code])?># <?=$code?>');
		$this->write($project . '/collection.md.php', '<?=$context->frontmatter(["title" => "Catalog"])?># Catalog');
	}

	/** Write the common mapped dataset configuration with a chosen output. */
	private function writeConfig(string $project, string $output, string $source = 'data.csv'): void
	{
		$config = <<<YAML
version: 1
datasets:
  products:
    source: {$source}
    template: row.md.php
    output: {$output}
    key: code
    columns:
      code: Code
      slug: Slug
      description: Description
      published: Published
    required: [code, slug]
    where:
      published: "yes"
    collections:
      - template: collection.md.php
        output: products/index.md
YAML;
		$this->write($project . '/.smgen-data-pages.yaml', $config . "\n");
	}

	/** Write one fixture file and fail immediately when the write is incomplete. */
	private function write(string $path, string $contents): void
	{
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

	/** Assert that a string contains one exact diagnostic or output fragment. */
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

(new DataPagesRegression())->run();
