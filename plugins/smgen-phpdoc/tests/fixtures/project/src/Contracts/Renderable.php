<?php

declare(strict_types=1);

namespace Acme\Contracts;

/**
 * Renders a value for display.
 */
interface Renderable
{
	/**
	 * Render the current value.
	 *
	 * @param string $prefix Text placed before the value.
	 * @return non-empty-string Rendered display text.
	 */
	public function render(string $prefix = ''): string;
}
