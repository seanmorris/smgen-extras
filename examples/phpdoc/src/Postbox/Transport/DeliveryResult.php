<?php

declare(strict_types=1);

namespace Postbox\Transport;

use Postbox\Address;
use Postbox\HasHeaders;

/**
 * The outcome of one delivery attempt, per recipient.
 */
final class DeliveryResult
{
	use HasHeaders;

	/** @var array<string, bool> Accepted state keyed by recipient email. */
	private array $accepted = [];

	/**
	 * Record a transport-assigned message identifier.
	 *
	 * @param non-empty-string $messageId Identifier such as `<abc@mail.example.test>`.
	 */
	public function __construct(public readonly string $messageId)
	{
	}

	/**
	 * Record whether one recipient was accepted by the server.
	 *
	 * @param Address $recipient Recipient that was attempted.
	 * @param bool $accepted True when the server accepted the recipient.
	 * @return void
	 */
	public function record(Address $recipient, bool $accepted): void
	{
		$this->accepted[$recipient->email] = $accepted;
	}

	/**
	 * Return the recipients the server rejected.
	 *
	 * @return list<non-empty-string> Rejected email addresses.
	 */
	public function rejected(): array
	{
		return array_values(array_keys(array_filter($this->accepted, static fn(bool $ok): bool => !$ok)));
	}

	/**
	 * Report whether every recipient was accepted.
	 *
	 * @return bool True when nothing was rejected.
	 */
	public function isComplete(): bool
	{
		return $this->rejected() === [] && $this->accepted !== [];
	}
}
