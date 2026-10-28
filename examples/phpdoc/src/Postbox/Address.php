<?php

declare(strict_types=1);

namespace Postbox;

use Postbox\Exception\InvalidAddressException;

/**
 * An immutable email address with an optional display name.
 */
final readonly class Address
{
	/**
	 * Create an address from its parts.
	 *
	 * @param non-empty-string $email Mailbox address such as `mara@example.test`.
	 * @param string $name Display name shown by mail clients, or an empty string.
	 * @throws InvalidAddressException When the email is not syntactically valid.
	 */
	public function __construct(public string $email, public string $name = '')
	{
		if(filter_var($email, FILTER_VALIDATE_EMAIL) === false)
		{
			throw new InvalidAddressException($email);
		}
	}

	/**
	 * Parse a header-style address such as `Mara Bell <mara@example.test>`.
	 *
	 * @param non-empty-string $header Address as it appears in a mail header.
	 * @return self Parsed address.
	 * @throws InvalidAddressException When no mailbox can be found in the header.
	 */
	public static function parse(string $header): self
	{
		if(preg_match('/^\s*(?:"?([^"<]*)"?\s*)?<([^>]+)>\s*$/', $header, $match) === 1)
		{
			return new self(trim($match[2]), trim($match[1]));
		}
		return new self(trim($header));
	}

	/**
	 * Format the address for a mail header.
	 *
	 * @return non-empty-string Header form, quoting the name when present.
	 */
	public function toHeader(): string
	{
		return $this->name === '' ? $this->email : sprintf('"%s" <%s>', addcslashes($this->name, '"\\'), $this->email);
	}

	/**
	 * Return the domain part of the address.
	 *
	 * @return non-empty-string Everything after the last `@`.
	 */
	public function domain(): string
	{
		return substr($this->email, (int) strrpos($this->email, '@') + 1);
	}
}
