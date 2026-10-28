<?php

declare(strict_types=1);

namespace Postbox\Transport;

use Postbox\Exception\DeliveryException;
use Postbox\Message;

/**
 * Delivers a message to its recipients.
 */
interface Transport
{
	/**
	 * Send one message.
	 *
	 * @param Message $message A sendable message.
	 * @return DeliveryResult What happened to each recipient.
	 * @throws DeliveryException When the transport cannot reach the mail server at all.
	 */
	public function send(Message $message): DeliveryResult;

	/**
	 * Describe the transport for logs.
	 *
	 * @return non-empty-string Short name such as `smtp://mail.example.test:587`.
	 */
	public function describe(): string;
}
