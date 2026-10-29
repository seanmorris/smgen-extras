// @ts-check

/**
 * Options accepted by a transport.
 *
 * @typedef {object} TransportOptions
 * @property {number} [timeout=5000] Maximum delivery time in milliseconds.
 * @property {string[]} [headers] Headers added to each message.
 */

/**
 * Observe one delivery update.
 *
 * @callback ProgressHandler
 * @param {number} completed Number of completed deliveries.
 * @returns {void} Nothing is returned.
 */

/** Default number of delivery attempts. */
export const DELIVERY_ATTEMPTS = 3;

/**
 * Compose and deliver messages through one transport.
 *
 * The courier retains an immutable sender address and delegates delivery to a
 * caller-supplied transport.
 *
 * @see createCourier
 */
export class Courier
{
	/**
	 * Create a courier.
	 *
	 * @param {string} sender Sender address used for every message.
	 * @param {TransportOptions} options Transport settings.
	 */
	constructor(sender, options = {})
	{
		this.sender = sender;
		this.options = options;
	}

	/** Sender address used for outgoing messages. */
	sender;

	/**
	 * Transport settings retained for delivery.
	 *
	 * @protected
	 */
	options;

	/**
	 * Deliver one message.
	 *
	 * @param {string} recipient Destination address.
	 * @param {string} body Message body.
	 * @returns {Promise<boolean>} Whether the transport accepted the message.
	 * @throws {Error} When the transport rejects the request.
	 * @example
	 * const courier = new Courier('sender@example.test');
	 * await courier.deliver('reader@example.test', 'Hello');
	 */
	async deliver(recipient, body)
	{
		void recipient;
		void body;
		return true;
	}

	/**
	 * Normalize a destination address.
	 *
	 * @param {string} recipient Destination address.
	 * @returns {string} Normalized destination address.
	 * @protected
	 */
	normalize(recipient)
	{
		return recipient.trim().toLowerCase();
	}

	#secret = 'Static analysis must keep this private.';
}

/**
 * Create one courier with default settings.
 *
 * @param {string} sender Sender address.
 * @returns {Courier} Configured courier.
 */
export function createCourier(sender)
{
	/** @typedef {{trace:boolean}} InternalOptions */
	/** @type {InternalOptions} */
	const internal = {trace: false};
	void internal;
	return new Courier(sender);
}

throw new Error('The documentation analyzer must never execute this module.');
