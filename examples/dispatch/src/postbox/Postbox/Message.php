<?php

declare(strict_types=1);

namespace Postbox;

use Postbox\Exception\MessageException;

/**
 * A mutable email message assembled with a fluent interface.
 */
class Message
{
	use HasHeaders;

	/** @var list<Address> Primary recipients. */
	private array $to = [];

	/** @var list<Address> Carbon-copy recipients. */
	private array $cc = [];

	/** @var list<Attachment> Attached files. */
	private array $attachments = [];

	private ?Address $from = null;

	private string $subject = '';

	private string $text = '';

	private ?string $html = null;

	private Priority $priority = Priority::Normal;

	/**
	 * Start a new message from a sender.
	 *
	 * @param Address $from Sender shown in the `From` header.
	 * @return self A message with no recipients or body yet.
	 */
	public static function from(Address $from): self
	{
		$message = new self();
		$message->from = $from;
		return $message;
	}

	/**
	 * Add a primary recipient.
	 *
	 * @param Address $recipient Recipient to add to the `To` header.
	 * @return $this The same message, for chaining.
	 */
	public function to(Address $recipient): static
	{
		$this->to[] = $recipient;
		return $this;
	}

	/**
	 * Add a carbon-copy recipient.
	 *
	 * @param Address $recipient Recipient to add to the `Cc` header.
	 * @return $this The same message, for chaining.
	 */
	public function cc(Address $recipient): static
	{
		$this->cc[] = $recipient;
		return $this;
	}

	/**
	 * Set the subject line.
	 *
	 * @param string $subject Subject without line breaks.
	 * @return $this The same message, for chaining.
	 */
	public function subject(string $subject): static
	{
		$this->subject = str_replace(["\r", "\n"], ' ', $subject);
		return $this;
	}

	/**
	 * Set the plain-text body.
	 *
	 * @param string $text Body as UTF-8 text.
	 * @return $this The same message, for chaining.
	 */
	public function text(string $text): static
	{
		$this->text = $text;
		return $this;
	}

	/**
	 * Set an optional HTML alternative to the text body.
	 *
	 * @param string|null $html Body as an HTML document, or null to remove it.
	 * @return $this The same message, for chaining.
	 */
	public function html(?string $html): static
	{
		$this->html = $html;
		return $this;
	}

	/**
	 * Attach a file.
	 *
	 * @param Attachment $attachment File to attach.
	 * @return $this The same message, for chaining.
	 */
	public function attach(Attachment $attachment): static
	{
		$this->attachments[] = $attachment;
		return $this;
	}

	/**
	 * Set the delivery priority.
	 *
	 * @param Priority $priority Priority recorded in the headers.
	 * @return $this The same message, for chaining.
	 */
	public function priority(Priority $priority): static
	{
		$this->priority = $priority;
		return $this;
	}

	/**
	 * Return every recipient, primary and carbon-copy, in order.
	 *
	 * @return list<Address> Recipients with duplicates removed.
	 */
	public function recipients(): array
	{
		$seen = [];
		$recipients = [];
		foreach([...$this->to, ...$this->cc] as $recipient)
		{
			if(isset($seen[$recipient->email])) continue;
			$seen[$recipient->email] = true;
			$recipients[] = $recipient;
		}
		return $recipients;
	}

	/**
	 * Return the attached files.
	 *
	 * @return list<Attachment> Attachments in the order they were added.
	 */
	public function attachments(): array
	{
		return $this->attachments;
	}

	/**
	 * Return the sender.
	 *
	 * @return Address|null The sender, or null before `from()` is called.
	 */
	public function sender(): ?Address
	{
		return $this->from;
	}

	/**
	 * Return the subject line.
	 *
	 * @return string Subject, possibly empty.
	 */
	public function subjectLine(): string
	{
		return $this->subject;
	}

	/**
	 * Return the plain-text body.
	 *
	 * @return string Text body, possibly empty.
	 */
	public function textBody(): string
	{
		return $this->text;
	}

	/**
	 * Return the HTML body.
	 *
	 * @return string|null HTML body, or null when only text was set.
	 */
	public function htmlBody(): ?string
	{
		return $this->html;
	}

	/**
	 * Check that the message can be sent.
	 *
	 * @return void
	 * @throws MessageException When the sender, a recipient, or the subject is missing.
	 */
	public function assertSendable(): void
	{
		if($this->from === null)
		{
			throw new MessageException('A message needs a sender.');
		}
		if($this->to === [])
		{
			throw new MessageException('A message needs at least one recipient.');
		}
		if($this->subject === '')
		{
			throw new MessageException('A message needs a subject.');
		}
	}

	/**
	 * Render the message headers as they will be transmitted.
	 *
	 * @return array<string, string> Header values keyed by canonical name.
	 */
	public function renderHeaders(): array
	{
		$headers = $this->headers();
		$headers['From'] = $this->from?->toHeader() ?? '';
		$headers['To'] = implode(', ', array_map(static fn(Address $a): string => $a->toHeader(), $this->to));
		if($this->cc !== [])
		{
			$headers['Cc'] = implode(', ', array_map(static fn(Address $a): string => $a->toHeader(), $this->cc));
		}
		$headers['Subject'] = $this->subject;
		$headers['X-Priority'] = $this->priority->header();
		return $headers;
	}
}
