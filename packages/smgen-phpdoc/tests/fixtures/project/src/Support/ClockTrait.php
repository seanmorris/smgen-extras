<?php

declare(strict_types=1);

namespace Acme\Support;

use DateTimeImmutable;

/**
 * Supplies an immutable creation time.
 */
trait ClockTrait
{
	/**
	 * Return the recorded creation time.
	 *
	 * @return DateTimeImmutable Recorded creation time.
	 */
	public function createdAt(): DateTimeImmutable
	{
		return new DateTimeImmutable();
	}
}
