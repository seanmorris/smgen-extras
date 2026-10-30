// @ts-check

/** Legacy label used by CommonJS consumers. */
exports.LEGACY_LABEL = 'courier';

/**
 * Format a legacy delivery identifier.
 *
 * @param {number} value Numeric delivery identifier.
 * @returns {string} Prefixed legacy identifier.
 */
exports.legacyId = function(value)
{
	return `legacy-${value}`;
};
