<?php

declare(strict_types=1);

namespace Demo;

/**
 * Selects the voice used for a generated greeting.
 */
enum Tone: string
{
	/** Uses a brief, informal greeting. */
	case Casual = 'casual';

	/** Uses a complete, formal greeting. */
	case Formal = 'formal';
}

/**
 * Formats a greeting for one named person.
 */
interface MessageFormatter
{
	/**
	 * Format one greeting.
	 *
	 * @param non-empty-string $name Person receiving the greeting.
	 * @param Tone $tone Requested voice.
	 * @return non-empty-string Completed greeting.
	 */
	public function format(string $name, Tone $tone = Tone::Casual): string;
}

/**
 * Produces greetings with a configurable salutation.
 */
final readonly class Greeter implements MessageFormatter
{
	/**
	 * Create a greeting formatter.
	 *
	 * @param non-empty-string $salutation Formal salutation placed before a name.
	 */
	public function __construct(public string $salutation = 'Hello')
	{
	}

	/**
	 * Format one greeting.
	 *
	 * @param non-empty-string $name Person receiving the greeting.
	 * @param Tone $tone Requested voice.
	 * @return non-empty-string Completed greeting.
	 */
	public function format(string $name, Tone $tone = Tone::Casual): string
	{
		return $tone === Tone::Formal
			? $this->salutation . ', ' . $name . '.'
			: 'Hi ' . $name . '!';
	}
}
