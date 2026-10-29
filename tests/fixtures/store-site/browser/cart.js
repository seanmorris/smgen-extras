// @ts-check

/**
 * Format the number of items shown beside the cart.
 *
 * @param {number} count Current item count.
 * @returns {string} Compact cart label.
 */
export function cartLabel(count)
{
	return count === 1 ? '1 item' : `${count} items`;
}
