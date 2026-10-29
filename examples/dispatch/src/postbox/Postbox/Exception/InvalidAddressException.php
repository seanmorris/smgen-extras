<?php

declare(strict_types=1);

namespace Postbox\Exception;

/**
 * Raised when an email address cannot be parsed.
 */
final class InvalidAddressException extends MessageException
{
	/**
	 * Describe the rejected address.
	 *
	 * @param string $address The text that failed to parse.
	 */
	public function __construct(public readonly string $address)
	{
		parent::__construct('Invalid email address: ' . $address);
	}
}
