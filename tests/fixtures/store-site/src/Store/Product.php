<?php

declare(strict_types=1);

namespace Store;

/**
 * Describes one product published in the static catalog.
 */
final readonly class Product
{
	/**
	 * Create one immutable catalog product.
	 *
	 * @param string $sku Stable catalog identifier.
	 * @param string $name Public product name.
	 */
	public function __construct(
		public string $sku,
		public string $name,
	) {
	}

	/**
	 * Format the product for search results.
	 *
	 * @return non-empty-string Product identifier and name.
	 */
	public function label(): string
	{
		return $this->sku . ': ' . $this->name;
	}
}
