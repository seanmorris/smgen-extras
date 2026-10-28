<?php

declare(strict_types=1);

namespace SmgenCategories;

use RuntimeException;

/**
 * Reads every Markdown page under a pages directory, including pages other plugins generated.
 */
final class PageScanner
{
	/**
	 * Scan a pages root and return one member record per Markdown page.
	 *
	 * @param string $pagesRoot Absolute pages directory.
	 * @param string|null $skipGenerated Plugin ID whose generated directory is left out, usually the caller's own.
	 * @return list<array<string, mixed>> Members with source, public path, title, description, date, and metadata.
	 */
	public function scan(string $pagesRoot, ?string $skipGenerated = null): array
	{
		$root = realpath($pagesRoot);
		if($root === false || !is_dir($root))
		{
			throw new RuntimeException('Pages directory does not exist: ' . $pagesRoot);
		}
		$members = [];
		$this->walk($root, $root, '', $skipGenerated, $members);
		usort($members, static fn(array $a, array $b): int => strcmp($a['source'], $b['source']));
		return $members;
	}

	/** Recurse one directory, skipping hidden entries other than generated pages and skipping links. */
	private function walk(string $directory, string $root, string $relativeDirectory, ?string $skipGenerated, array &$members): void
	{
		$entries = scandir($directory);
		if($entries === false)
		{
			throw new RuntimeException('Unable to read pages directory: ' . $directory);
		}
		foreach($entries as $entry)
		{
			if($entry === '.' || $entry === '..')
			{
				continue;
			}
			$pathname = $directory . DIRECTORY_SEPARATOR . $entry;
			if(is_link($pathname))
			{
				continue;
			}
			$relative = ltrim($relativeDirectory . '/' . $entry, '/');
			if(is_dir($pathname))
			{
				if($entry[0] === '.' && !($relativeDirectory === '' && $entry === '.smgen-generated'))
				{
					continue;
				}
				if($relativeDirectory === '.smgen-generated' && $entry === $skipGenerated)
				{
					continue;
				}
				$this->walk($pathname, $root, $relative, $skipGenerated, $members);
				continue;
			}
			if($entry[0] === '.' || !str_ends_with(strtolower($entry), '.md'))
			{
				continue;
			}
			$members[] = $this->member($pathname, $relative);
		}
	}

	/** Build one member record from a page file. */
	private function member(string $absolute, string $source): array
	{
		$metadata = $this->frontmatter($absolute);
		$public = preg_replace('~^\.smgen-generated/[^/]+/~', '', $source) ?? $source;
		$output = preg_replace('/\.[^.]+$/', '.html', $public) ?? $public;
		$path = '/' . ltrim($output, '/');
		if($path === '/index.html')
		{
			$path = '/';
		}
		elseif(str_ends_with($path, '/index.html'))
		{
			$path = substr($path, 0, -strlen('index.html'));
		}
		$title = $metadata['title'] ?? ucwords(preg_replace(['/\.[^.]+$/', '/-/'], ['', ' '], basename($source)) ?? basename($source));
		return [
			'source' => $source,
			'absolute' => $absolute,
			'relative' => $public,
			'path' => $path,
			'title' => is_scalar($title) ? (string) $title : basename($source),
			'description' => is_scalar($metadata['description'] ?? null) ? (string) $metadata['description'] : '',
			'date' => isset($metadata['date']) && is_scalar($metadata['date']) ? (string) $metadata['date'] : null,
			'metadata' => $metadata,
		];
	}

	/** Parse a page's YAML frontmatter the way SMGen does, returning an empty mapping when absent. */
	private function frontmatter(string $path): array
	{
		$contents = file_get_contents($path);
		if($contents === false)
		{
			throw new RuntimeException('Unable to read page: ' . $path);
		}
		$contents = str_replace(["\r\n", "\r"], "\n", $contents);
		if(strncmp($contents, "---\n", 4) !== 0)
		{
			return [];
		}
		$end = strpos($contents, "\n---\n", 4);
		if($end === false)
		{
			if(substr($contents, -4) !== "\n---")
			{
				throw new RuntimeException('Unterminated YAML frontmatter: ' . $path);
			}
			$end = strlen($contents) - 4;
		}
		$text = substr($contents, 4, $end - 4);
		set_error_handler(static function(int $severity, string $message) use ($path): never {
			throw new RuntimeException('Invalid YAML frontmatter in ' . $path . ': ' . $message);
		});
		try
		{
			$value = trim($text) === '' ? [] : yaml_parse($text);
		}
		finally
		{
			restore_error_handler();
		}
		if($value === false || $value === null)
		{
			return [];
		}
		if(!is_array($value) || ($value !== [] && array_keys($value) === range(0, count($value) - 1)))
		{
			throw new RuntimeException('Frontmatter must be a YAML mapping: ' . $path);
		}
		return $value;
	}
}
