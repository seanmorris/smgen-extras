<?php

declare(strict_types=1);

namespace Postbox;

/**
 * A file attached to a message, held in memory.
 */
final readonly class Attachment
{
	/**
	 * Create an attachment from raw bytes.
	 *
	 * @param non-empty-string $filename Name presented to the recipient.
	 * @param string $contents Raw file contents.
	 * @param non-empty-string $mediaType MIME type such as `application/pdf`.
	 */
	public function __construct(
		public string $filename,
		public string $contents,
		public string $mediaType = 'application/octet-stream',
	)
	{
	}

	/**
	 * Read an attachment from disk.
	 *
	 * @param non-empty-string $path Readable file path.
	 * @param non-empty-string|null $filename Name presented to the recipient, or the path's basename.
	 * @return self Attachment holding the file contents.
	 * @throws \RuntimeException When the file cannot be read.
	 */
	public static function fromFile(string $path, ?string $filename = null): self
	{
		$contents = @file_get_contents($path);
		if($contents === false)
		{
			throw new \RuntimeException('Unable to read attachment: ' . $path);
		}
		$type = function_exists('mime_content_type') ? (mime_content_type($path) ?: 'application/octet-stream') : 'application/octet-stream';
		return new self($filename ?? basename($path), $contents, $type);
	}

	/**
	 * Return the attachment size in bytes.
	 *
	 * @return int<0, max> Number of bytes.
	 */
	public function size(): int
	{
		return strlen($this->contents);
	}
}
