<?php

declare(strict_types=1);

namespace Postbox;

/**
 * The delivery priority recorded in a message's headers.
 */
enum Priority: int
{
	/** Delivered ahead of queued normal and low messages. */
	case High = 1;

	/** The default priority for most mail. */
	case Normal = 3;

	/** Delivered when the queue is otherwise idle. */
	case Low = 5;

	/**
	 * Return the `X-Priority` header value for this priority.
	 *
	 * @return non-empty-string Header value such as `1 (High)`.
	 */
	public function header(): string
	{
		return $this->value . ' (' . $this->name . ')';
	}
}
