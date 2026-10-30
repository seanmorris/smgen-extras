<?php

declare(strict_types=1);

namespace Acme;

use Acme\Contracts\Renderable;
use Acme\Support\ClockTrait;
use DateTimeImmutable as Clock;
use Stringable;

/**
 * Stores and renders one named widget.
 *
 * The class keeps its input data immutable after construction.
 *
 * @template T of array-key
 * @property-read list<string> $labels Labels available to callers.
 * @method static self make(string $name) Create a widget by name.
 * @see WidgetStatus
 */
#[\AllowDynamicProperties]
final class Widget implements Renderable
{
	use ClockTrait;

	/**
	 * Create one widget.
	 *
	 * @param string $name Public widget name.
	 * @param array{owner: string, tags?: list<string>} $options Widget options.
	 */
	public function __construct(
		public readonly string $name,
		protected array $options = [],
	) {
	}

	/**
	 * Render the current widget.
	 *
	 * @param string $prefix Text placed before the name.
	 * @return non-empty-string Rendered widget name.
	 * @throws \RuntimeException When the name cannot be rendered.
	 */
	public function render(string $prefix = ''): string
	{
		return $prefix . $this->name;
	}

	/**
	 * Compare the widget with a renderable string value.
	 *
	 * @param (Renderable&Stringable)|null $other Optional comparison value.
	 * @return bool Whether the values match.
	 */
	protected function matches((Renderable&Stringable)|null $other): bool
	{
		return $other !== null;
	}

	/**
	 * Return a clock for serializer fixtures.
	 *
	 * @return Clock Immutable clock value.
	 */
	public function clock(): Clock
	{
		return new Clock();
	}
}
