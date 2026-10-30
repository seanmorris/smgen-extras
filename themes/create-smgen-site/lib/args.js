// Command-line parsing and usage text.

import {parseArgs} from 'node:util';

export const USAGE = `Usage: create-smgen-site <directory> [options]

Scaffold an SMGen site from one of the official base themes.

Options:
  --theme <name>     Theme to copy (required when not running in a terminal)
  --name <text>      Product name written to .smgen.yaml (default: from the directory)
  --tagline <text>   Tagline written to .smgen.yaml (default: the theme's sample tagline)
  --list             List the available themes and exit
  --help             Show this message

Examples:
  npm create smgen-site@latest my-site -- --theme journal --name "Harbour Notes"
  npx create-smgen-site@latest my-site --list
`;

/**
 * Parse the command line into options, treating any bad input as a usage error.
 *
 * @param {string[]} argv Arguments after the executable and script.
 * @returns {{target: string|null, theme: string|null, name: string|null, tagline: string|null, list: boolean, help: boolean}} Parsed options.
 */
export function parseCommandLine(argv)
{
	const {values, positionals} = parseArgs({
		args: argv,
		allowPositionals: true,
		strict: true,
		options: {
			theme: {type: 'string'},
			name: {type: 'string'},
			tagline: {type: 'string'},
			list: {type: 'boolean', default: false},
			help: {type: 'boolean', default: false},
		},
	});
	if(positionals.length > 1)
	{
		throw new UsageError('Expected one directory argument, received ' + positionals.length + '.');
	}
	return {
		target: positionals[0] ?? null,
		theme: values.theme ?? null,
		name: values.name ?? null,
		tagline: values.tagline ?? null,
		list: values.list === true,
		help: values.help === true,
	};
}

/** A problem with how the command was invoked, reported with exit code 1. */
export class UsageError extends Error
{
}
