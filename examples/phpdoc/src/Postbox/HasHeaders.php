<?php

declare(strict_types=1);

namespace Postbox;

/**
 * Shared header storage for messages and delivery results.
 */
trait HasHeaders
{
	/** @var array<string, string> Header values keyed by canonical name. */
	private array $headers = [];

	/**
	 * Set one header, replacing any previous value.
	 *
	 * @param non-empty-string $name Header name in any case.
	 * @param string $value Header value without line breaks.
	 * @return static The same object, for chaining.
	 */
	public function withHeader(string $name, string $value): static
	{
		$this->headers[self::canonicalHeaderName($name)] = str_replace(["\r", "\n"], ' ', $value);
		return $this;
	}

	/**
	 * Read one header.
	 *
	 * @param non-empty-string $name Header name in any case.
	 * @return string|null The value, or null when the header is not set.
	 */
	public function header(string $name): ?string
	{
		return $this->headers[self::canonicalHeaderName($name)] ?? null;
	}

	/**
	 * Return every header.
	 *
	 * @return array<string, string> Values keyed by canonical header name.
	 */
	public function headers(): array
	{
		return $this->headers;
	}

	/**
	 * Normalise a header name to `Title-Case` words.
	 *
	 * @param non-empty-string $name Header name in any case.
	 * @return non-empty-string Canonical name such as `Content-Type`.
	 */
	private static function canonicalHeaderName(string $name): string
	{
		return implode('-', array_map('ucfirst', explode('-', strtolower(trim($name)))));
	}
}
