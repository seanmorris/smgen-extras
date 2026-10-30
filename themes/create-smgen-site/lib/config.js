// Rewrite the handful of top-level .smgen.yaml keys a new site needs changed.
//
// Themes keep PRODUCT_NAME, TITLE_PREFIX, TAGLINE, BASE_URL and OUTPUT_DIR
// as single top-level lines, so a line-based rewrite is exact and needs no
// YAML parser. Nested keys never start at column zero and are never touched.

/**
 * Write a value as a YAML scalar that round-trips exactly.
 *
 * @param {string} value Plain string value.
 * @returns {string} Quoted YAML scalar.
 */
export function yamlScalar(value)
{
	return value === '' ? "''" : JSON.stringify(value);
}

/**
 * Read a scalar written by yamlScalar back to its string value.
 *
 * @param {string} text The text after "KEY: ".
 * @returns {string|null} The value, or null when the text is not one of ours.
 */
export function readScalar(text)
{
	const trimmed = text.trim();
	if(trimmed === "''") return '';
	if(trimmed.startsWith('"'))
	{
		try
		{
			const value = JSON.parse(trimmed);
			return typeof value === 'string' ? value : null;
		}
		catch(error)
		{
			return null;
		}
	}
	return trimmed;
}

/**
 * Replace top-level keys in a .smgen.yaml document, line by line.
 *
 * @param {string} text Original document.
 * @param {Record<string, string>} values Keys to set, each a plain string.
 * @returns {string} The rewritten document with the original line endings.
 */
export function rewriteSmgenYaml(text, values)
{
	const newline = text.includes('\r\n') ? '\r\n' : '\n';
	const trailing = text.endsWith(newline) ? newline : '';
	const lines = (trailing === '' ? text : text.slice(0, -newline.length)).split(newline);
	for(const [key, value] of Object.entries(values))
	{
		const pattern = new RegExp('^' + key.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '[ \\t]*:');
		const matches = lines.map((line, index) => (pattern.test(line) ? index : -1)).filter((index) => index >= 0);
		if(matches.length !== 1)
		{
			throw new Error('Expected exactly one top-level ' + key + ' line in .smgen.yaml, found ' + matches.length + '.');
		}
		lines[matches[0]] = key + ': ' + yamlScalar(value);
	}
	return lines.join(newline) + trailing;
}

/**
 * Check that a rewrite changed only the requested lines and wrote the requested values.
 *
 * @param {string} before Original document.
 * @param {string} after Rewritten document.
 * @param {Record<string, string>} values Keys that were set.
 * @returns {void}
 */
export function validateRewrite(before, after, values)
{
	const oldLines = before.split(/\r?\n/);
	const newLines = after.split(/\r?\n/);
	if(oldLines.length !== newLines.length)
	{
		throw new Error('The rewritten .smgen.yaml has a different number of lines.');
	}
	const keys = Object.keys(values);
	for(let index = 0; index < newLines.length; index++)
	{
		const match = newLines[index].match(/^([A-Z_]+)[ \t]*:(.*)$/);
		const key = match && keys.includes(match[1]) ? match[1] : null;
		if(key === null)
		{
			if(newLines[index] !== oldLines[index])
			{
				throw new Error('Line ' + (index + 1) + ' of .smgen.yaml changed unexpectedly.');
			}
			continue;
		}
		if(readScalar(match[2]) !== values[key])
		{
			throw new Error('The ' + key + ' line did not take the requested value.');
		}
	}
}
