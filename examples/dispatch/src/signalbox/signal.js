// @ts-check

/**
 * Options applied when a listener is registered.
 *
 * @typedef {object} ListenerOptions
 * @property {boolean} [once=false] Remove the listener after its first call.
 * @property {AbortSignal} [signal] Remove the listener when this signal aborts.
 */

/**
 * Receive a value emitted by a signal.
 *
 * @template T
 * @callback SignalListener
 * @param {T} value Emitted value.
 * @returns {void} Nothing is returned.
 */

/**
 * Remove one listener from its signal.
 *
 * Every call to `subscribe()` returns one of these. Holding on to it is the
 * only way to remove a listener deliberately; there is no `off()` that takes
 * the function back, because the same function may be registered more than
 * once with different options.
 *
 * Calling `unsubscribe()` more than once is safe. After the first call:
 *
 * - `closed` reports `true`;
 * - the listener is no longer counted by the signal's `size()`;
 * - the `cleanup` function has already run and is not run again.
 *
 * ```js
 * const subscription = selected.subscribe(render, {once: true});
 * subscription.unsubscribe(); // idempotent
 * ```
 */
export class Subscription
{
	/**
	 * Create a subscription around a cleanup function.
	 *
	 * @param {()=>void} cleanup Cleanup invoked once.
	 */
	constructor(cleanup)
	{
		this.cleanup = cleanup;
	}

	/** Whether this subscription has been removed. */
	closed = false;

	/**
	 * Cleanup retained until the subscription closes.
	 *
	 * @protected
	 */
	cleanup;

	/**
	 * Remove the listener.
	 *
	 * @returns {void} Nothing is returned.
	 */
	unsubscribe()
	{
		if(this.closed) return;
		this.closed = true;
		this.cleanup();
	}
}

/**
 * Create a typed signal with subscribe and emit operations.
 *
 * @template T
 * @param {string} [name='signal'] Name shown by development tools.
 * @returns {{name:string,subscribe:(listener:SignalListener<T>,options?:ListenerOptions)=>Subscription,emit:(value:T)=>void,size:()=>number}} Signal controls.
 * @example
 * const selected = createSignal('selected');
 * const subscription = selected.subscribe((id) => console.log(id));
 * selected.emit('row-7');
 * subscription.unsubscribe();
 */
export function createSignal(name = 'signal')
{
	/** @type {Set<SignalListener<unknown>>} */
	const listeners = new Set();
	return {
		name,
		subscribe(listener, options = {})
		{
			const wrapped = options.once ? (value) => {
				listeners.delete(wrapped);
				listener(value);
			} : listener;
			listeners.add(wrapped);
			const subscription = new Subscription(() => listeners.delete(wrapped));
			options.signal?.addEventListener('abort', () => subscription.unsubscribe(), {once: true});
			return subscription;
		},
		emit(value)
		{
			for(const listener of [...listeners]) listener(value);
		},
		size()
		{
			return listeners.size;
		},
	};
}

throw new Error('Static documentation must not execute this module.');
