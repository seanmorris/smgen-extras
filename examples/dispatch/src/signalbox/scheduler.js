// @ts-check

/** Default delay before a queued task runs. */
export const DEFAULT_DELAY = 0;

/**
 * Schedule one task and return a cancellation function.
 *
 * @param {()=>void} task Task to run.
 * @param {number} [delay=DEFAULT_DELAY] Delay in milliseconds.
 * @returns {()=>void} Function that cancels the pending task.
 */
export function schedule(task, delay = DEFAULT_DELAY)
{
	const timer = setTimeout(task, delay);
	return () => clearTimeout(timer);
}

/**
 * Collect calls made during one delay window and run only the latest.
 *
 * @template {(...args:any[])=>void} T
 * @param {T} callback Function to defer.
 * @param {number} delay Delay in milliseconds.
 * @returns {T} Debounced function.
 */
export function debounce(callback, delay)
{
	let cancel = () => {};
	return /** @type {T} */ ((...args) => {
		cancel();
		cancel = schedule(() => callback(...args), delay);
	});
}
