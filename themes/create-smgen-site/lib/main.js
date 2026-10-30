// The scaffolding flow: parse, discover, validate, copy, rewrite, report.

import {readFileSync, writeFileSync, rmSync, existsSync} from 'node:fs';
import {basename, join, resolve} from 'node:path';
import {parseCommandLine, UsageError, USAGE} from './args.js';
import {discoverThemes, formatThemeList} from './themes.js';
import {copyTheme, targetState} from './copy.js';
import {rewriteSmgenYaml, validateRewrite} from './config.js';
import {renderReadme, titleCase} from './readme.js';
import {promptForTheme} from './prompt.js';

export const EXIT = {ok: 0, usage: 1, occupied: 2, filesystem: 3, rewrite: 4, interrupted: 130};

/**
 * Run the scaffolder and return the process exit code.
 *
 * @param {string[]} argv Arguments after the executable and script.
 * @param {{stdout: NodeJS.WritableStream, stderr: NodeJS.WritableStream, stdin: NodeJS.ReadableStream & {isTTY?: boolean}, cwd: string}} io Streams and working directory.
 * @returns {Promise<number>} Exit code.
 */
export async function run(argv, io)
{
	/**
	 * Report one error line and hand back the exit code to return.
	 *
	 * @param {number} code Exit code.
	 * @param {string} message What went wrong.
	 * @returns {number} The same exit code.
	 */
	const fail = (code, message) => {
		io.stderr.write('create-smgen-site: ' + message + '\n');
		return code;
	};
	let options;
	try
	{
		options = parseCommandLine(argv);
	}
	catch(error)
	{
		io.stderr.write('create-smgen-site: ' + (error instanceof Error ? error.message : String(error)) + '\n\n' + USAGE);
		return EXIT.usage;
	}
	if(options.help)
	{
		io.stdout.write(USAGE);
		return EXIT.ok;
	}
	const themes = discoverThemes();
	if(options.list)
	{
		io.stdout.write(formatThemeList(themes));
		return EXIT.ok;
	}
	if(options.target === null)
	{
		io.stderr.write('create-smgen-site: a target directory is required.\n\n' + USAGE);
		return EXIT.usage;
	}
	const target = resolve(io.cwd, options.target);
	if(target === resolve('/') || themes.some((theme) => target.startsWith(theme.dir)))
	{
		return fail(EXIT.usage, 'Refusing to write into ' + target + '.');
	}
	const state = targetState(target);
	if(state === 'occupied' || state === 'file')
	{
		return fail(EXIT.occupied, target + ' exists and is not an empty directory.');
	}
	let themeName = options.theme;
	if(themeName === null)
	{
		if(!io.stdin.isTTY)
		{
			return fail(EXIT.usage, '--theme is required when not running in a terminal. Run with --list to see the themes.');
		}
		try
		{
			themeName = await promptForTheme(themes, io);
		}
		catch(error)
		{
			return EXIT.interrupted;
		}
	}
	const theme = themes.find((candidate) => candidate.name === themeName);
	if(!theme)
	{
		return fail(EXIT.usage, 'Unknown theme "' + themeName + '". Run with --list to see the themes.');
	}
	const name = options.name ?? titleCase(basename(target));
	const created = state === 'missing';
	try
	{
		copyTheme(theme.dir, target);
		const configPath = join(target, '.smgen.yaml');
		const before = readFileSync(configPath, 'utf8');
		const values = {PRODUCT_NAME: name, TITLE_PREFIX: name, BASE_URL: '', OUTPUT_DIR: './docs'};
		if(options.tagline !== null) values.TAGLINE = options.tagline;
		let after;
		try
		{
			after = rewriteSmgenYaml(before, values);
			validateRewrite(before, after, values);
		}
		catch(error)
		{
			if(created) rmSync(target, {recursive: true, force: true});
			return fail(EXIT.rewrite, 'The theme\'s .smgen.yaml could not be updated: ' + (error instanceof Error ? error.message : String(error)));
		}
		writeFileSync(configPath, after);
		const themeReadmePath = join(theme.dir, 'README.md');
		const themeReadme = existsSync(themeReadmePath) ? readFileSync(themeReadmePath, 'utf8') : '';
		writeFileSync(join(target, 'README.md'), renderReadme({name, theme, themeReadme}));
	}
	catch(error)
	{
		if(created) rmSync(target, {recursive: true, force: true});
		return fail(EXIT.filesystem, (error instanceof Error ? error.message : String(error)));
	}
	io.stdout.write([
		'',
		'Created ' + name + ' from the ' + theme.name + ' theme in ' + target,
		'',
		'Next steps:',
		'  cd ' + options.target,
		'  smgen build      # needs SMGen: https://seanmorris.github.io/smgen/',
		'  smgen watch      # rebuild and serve on http://localhost:8000',
		'',
		'Theme guide: https://seanmorris.github.io/smgen-extras/themes.html#' + theme.name,
		'',
	].join('\n'));
	return EXIT.ok;
}
