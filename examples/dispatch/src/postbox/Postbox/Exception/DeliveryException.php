<?php

declare(strict_types=1);

namespace Postbox\Exception;

/**
 * Raised when a transport cannot deliver at all.
 */
final class DeliveryException extends \RuntimeException
{
	/**
	 * Describe a failed delivery attempt.
	 *
	 * @param non-empty-string $transport The transport description, such as `smtp://host:587`.
	 * @param string $reason Server or socket error text.
	 */
	public function __construct(public readonly string $transport, string $reason)
	{
		parent::__construct('Delivery failed via ' . $transport . ': ' . $reason);
	}
}
