// Interactive theme choice when no --theme was given and a terminal is attached.

import {createInterface} from 'node:readline/promises';

/**
 * Ask the user to pick a theme by number or name.
 *
 * @param {Array<{name: string, manifest: Record<string, any>}>} themes Installed themes.
 * @param {{stdin: NodeJS.ReadableStream, stdout: NodeJS.WritableStream}} io Streams to talk through.
 * @returns {Promise<string>} The chosen theme name.
 */
export async function promptForTheme(themes, io)
{
	const rl = createInterface({input: io.stdin, output: io.stdout});
	try
	{
		io.stdout.write('Choose a theme:\n');
		themes.forEach((theme, index) => {
			io.stdout.write('  ' + (index + 1) + ') ' + theme.name.padEnd(12) + String(theme.manifest.description ?? '') + '\n');
		});
		for(;;)
		{
			const answer = (await rl.question('Theme [1-' + themes.length + ']: ')).trim();
			const byNumber = /^\d+$/.test(answer) ? themes[Number(answer) - 1] : undefined;
			const byName = themes.find((theme) => theme.name === answer);
			const chosen = byNumber ?? byName;
			if(chosen) return chosen.name;
			io.stdout.write('Please enter a number from the list or a theme name.\n');
		}
	}
	finally
	{
		rl.close();
	}
}
