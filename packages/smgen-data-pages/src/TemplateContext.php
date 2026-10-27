<?php

declare(strict_types=1);

namespace SmgenDataPages;

use InvalidArgumentException;
use RuntimeException;

/**
 * Supplies safe formatting helpers to trusted PHP page templates.
 */
final class TemplateContext
{
	/**
	 * Encode metadata as a complete Pandoc YAML frontmatter block.
	 *
	 * @param array<string, mixed> $metadata Page metadata to encode.
	 */
	public function frontmatter(array $metadata): string
	{
		return "---\n" . $this->yaml($metadata) . "---\n";
	}

	/**
	 * Encode a value as deterministic YAML without document delimiters.
	 */
	public function yaml(mixed $value): string
	{
		$this->assertYamlValue($value, 'value');
		$yaml = yaml_emit($value, YAML_UTF8_ENCODING, YAML_LN_BREAK);
		if($yaml === false)
		{
			throw new RuntimeException('Unable to encode template data as YAML.');
		}
		$yaml = preg_replace('/\A---\s*\R/', '', $yaml, 1) ?? $yaml;
		$yaml = preg_replace('/\R\.\.\.\s*\R?\z/', "\n", $yaml, 1) ?? $yaml;
		return rtrim($yaml, "\r\n") . "\n";
	}

	/** Escape text for safe placement in raw HTML emitted by a template. */
	public function escapeHtml(string $value): string
	{
		return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
	}

	/** Convert human text into one portable, nonempty URL path segment. */
	public function pathSegment(string $value): string
	{
		$value = strtolower(trim($value));
		$value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
		$value = trim($value, '-');
		if($value === '')
		{
			throw new InvalidArgumentException('A path segment must contain at least one ASCII letter or number.');
		}
		return $value;
	}

	/** Reject values that the YAML extension could encode as PHP-specific types. */
	private function assertYamlValue(mixed $value, string $path): void
	{
		if($value === null || is_string($value) || is_int($value) || is_float($value) || is_bool($value))
		{
			return;
		}
		if(!is_array($value))
		{
			throw new InvalidArgumentException('Unsupported YAML value at ' . $path . '.');
		}
		foreach($value as $key => $child)
		{
			if(!is_int($key) && !is_string($key))
			{
				throw new InvalidArgumentException('Unsupported YAML key at ' . $path . '.');
			}
			$this->assertYamlValue($child, $path . '.' . (string) $key);
		}
	}
}
