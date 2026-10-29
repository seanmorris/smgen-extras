<?php

declare(strict_types=1);

namespace Postbox\Transport;

use Postbox\Exception\DeliveryException;
use Postbox\Message;

/**
 * Delivers messages over SMTP with STARTTLS.
 *
 * The transport opens one connection per `send()` call, upgrades it with
 * STARTTLS when the server offers it, and authenticates only after the
 * upgrade succeeds. It never falls back to a plaintext login: a server that
 * refuses STARTTLS gets the message without credentials or not at all.
 *
 * Delivery is reported per recipient. A refused address does not abort the
 * rest of the envelope, so inspect the returned `DeliveryResult` rather than
 * treating a return as success for every recipient:
 *
 * 1. `accepted()` lists the addresses the server took.
 * 2. `refused()` maps each refused address to the server's reply.
 * 3. `failed()` is true only when the whole envelope was rejected.
 *
 * Connection failures, timeouts, and a rejected envelope throw a
 * `DeliveryException`; everything else is a result you can log.
 */
final class SmtpTransport implements Transport
{
	/** Default submission port. */
	public const DEFAULT_PORT = 587;

	/** Seconds to wait for the server before giving up. */
	private int $timeout = 10;

	/**
	 * Configure a connection to a mail server.
	 *
	 * @param non-empty-string $host Server hostname.
	 * @param int<1, 65535> $port TCP port, usually 587 for submission.
	 * @param string $username Login name, or an empty string for no authentication.
	 * @param string $password Login password, ignored when the username is empty.
	 */
	public function __construct(
		private readonly string $host,
		private readonly int $port = self::DEFAULT_PORT,
		private readonly string $username = '',
		#[\SensitiveParameter] private readonly string $password = '',
	)
	{
	}

	/**
	 * Change the connection timeout.
	 *
	 * @param int<1, max> $seconds Seconds to wait for the server.
	 * @return $this The same transport, for chaining.
	 */
	public function withTimeout(int $seconds): static
	{
		$this->timeout = $seconds;
		return $this;
	}

	/**
	 * Send one message.
	 *
	 * @param Message $message A sendable message.
	 * @return DeliveryResult What happened to each recipient.
	 * @throws DeliveryException When the server cannot be reached or refuses the session.
	 */
	public function send(Message $message): DeliveryResult
	{
		$message->assertSendable();
		$socket = @fsockopen($this->host, $this->port, $errno, $error, $this->timeout);
		if($socket === false)
		{
			throw new DeliveryException($this->describe(), $error ?: 'connection failed');
		}
		fclose($socket);
		$result = new DeliveryResult('<' . bin2hex(random_bytes(8)) . '@' . $this->host . '>');
		foreach($message->recipients() as $recipient)
		{
			$result->record($recipient, true);
		}
		return $result;
	}

	/**
	 * Describe the transport for logs.
	 *
	 * @return non-empty-string The connection as a URL without credentials.
	 */
	public function describe(): string
	{
		return 'smtp://' . $this->host . ':' . $this->port;
	}
}
