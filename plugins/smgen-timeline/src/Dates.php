<?php

declare(strict_types=1);

namespace SmgenTimeline;

use DateTimeImmutable;
use RuntimeException;
use Throwable;

/**
 * Resolves the publication date of a page for the timeline.
 */
final class Dates
{
	/**
	 * Normalize a frontmatter date value.
	 *
	 * @param mixed $value Date-like value from frontmatter.
	 * @param string $label Page label for diagnostics.
	 * @return array{date: string, timestamp: int} Calendar day and Unix timestamp.
	 */
	public static function normalize(mixed $value, string $label): array
	{
		if(!is_string($value) && !is_int($value) && !is_float($value))
		{
			throw new RuntimeException('Timeline date must be a string or number: ' . $label);
		}
		$text = trim((string) $value);
		if($text === '')
		{
			throw new RuntimeException('Timeline date must not be empty: ' . $label);
		}
		try
		{
			$date = new DateTimeImmutable($text);
		}
		catch(Throwable $error)
		{
			throw new RuntimeException('Invalid timeline date in ' . $label . ': ' . $text, 0, $error);
		}
		return ['date' => $date->format('Y-m-d'), 'timestamp' => $date->getTimestamp()];
	}

	/**
	 * Resolve a catalog page's date from frontmatter or, failing that, from the file's history.
	 *
	 * A fallback date is reported through $stamp so the build can write it back as `created`.
	 *
	 * @param array<string, mixed> $page Catalog page with metadata, source, and absolute path.
	 * @param callable|null $stamp Receives the absolute path and timestamp when a fallback was used.
	 * @return array{date: string, timestamp: int} Calendar day and Unix timestamp.
	 */
	public static function resolve(array $page, ?callable $stamp = null): array
	{
		$metadata = $page['metadata'] ?? [];
		if(array_key_exists('date', $metadata))
		{
			return self::normalize($metadata['date'], (string) $page['source']);
		}
		if(array_key_exists('created', $metadata))
		{
			return self::normalize($metadata['created'], (string) $page['source']);
		}
		$absolute = (string) ($page['absolute'] ?? '');
		$timestamp = self::birthTime($absolute) ?? self::gitCreatedTime($absolute) ?? (is_file($absolute) ? filemtime($absolute) : false);
		if($timestamp === false || $timestamp === null)
		{
			throw new RuntimeException('Unable to determine a creation time for timeline page: ' . (string) $page['source']);
		}
		if($stamp !== null)
		{
			$stamp($absolute, $timestamp);
		}
		return self::normalize(gmdate('Y-m-d\TH:i:sP', $timestamp), (string) $page['source']);
	}

	/** Read the filesystem birth time when the platform exposes one. */
	private static function birthTime(string $path): ?int
	{
		if($path === '' || !is_file($path))
		{
			return null;
		}
		$quoted = escapeshellarg($path);
		$value = PHP_OS_FAMILY === 'Darwin' || PHP_OS_FAMILY === 'BSD'
			? self::command('stat -f %B ' . $quoted)
			: self::command('stat -c %W -- ' . $quoted);
		return ctype_digit($value) && (int) $value > 0 ? (int) $value : null;
	}

	/** Read the first Git commit time that added the file. */
	private static function gitCreatedTime(string $path): ?int
	{
		if($path === '')
		{
			return null;
		}
		$root = self::command('git -C ' . escapeshellarg(dirname($path)) . ' rev-parse --show-toplevel');
		if($root === '' || strpos($path, rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR) !== 0)
		{
			return null;
		}
		$relative = str_replace(DIRECTORY_SEPARATOR, '/', substr($path, strlen(rtrim($root, DIRECTORY_SEPARATOR)) + 1));
		$dates = self::command('git -C ' . escapeshellarg($root) . ' log --follow --diff-filter=A --format=%aI -- ' . escapeshellarg($relative));
		if($dates === '')
		{
			return null;
		}
		$lines = preg_split('/\R/', $dates, -1, PREG_SPLIT_NO_EMPTY) ?: [];
		$timestamp = $lines === [] ? false : strtotime((string) end($lines));
		return $timestamp === false ? null : $timestamp;
	}

	/** Run a read-only shell command and return its trimmed output, or an empty string. */
	private static function command(string $command): string
	{
		if(!function_exists('exec'))
		{
			return '';
		}
		$output = [];
		$status = 0;
		exec($command . ' 2>/dev/null', $output, $status);
		return $status === 0 ? trim(implode("\n", $output)) : '';
	}
}
