// The README written into a newly scaffolded site.

/**
 * Build the site README from the chosen name, the theme, and the theme's own README.
 *
 * @param {{name: string, theme: {name: string, manifest: Record<string, any>}, themeReadme: string}} input What the site was made from.
 * @returns {string} Markdown text.
 */
export function renderReadme(input)
{
	const {name, theme, themeReadme} = input;
	const samples = Array.isArray(theme.manifest.sampleContent) ? theme.manifest.sampleContent : [];
	const notes = Array.isArray(theme.manifest.notes) ? theme.manifest.notes : [];
	const lines = [
		'# ' + name,
		'',
		'An [SMGen](https://seanmorris.github.io/smgen/) site started from the **' + String(theme.manifest.title ?? theme.name) + '** theme.',
		'',
		'## Build it',
		'',
		'SMGen is a Bash and PHP tool. It needs PHP 8.1 or newer with the YAML extension, Pandoc, and Bash (on Windows, use WSL or Git Bash). Install it from GitHub, then build:',
		'',
		'```bash',
		'curl -fsSL https://seanmorris.github.io/smgen/install.sh | sudo bash',
		'smgen build        # writes the site to docs/',
		'smgen watch        # rebuilds and serves on http://localhost:8000',
		'```',
		'',
		'## Edit it',
		'',
		'- `.smgen.yaml` holds the site name, tagline, menus and feeds. Set `BASE_URL` when the site is served from a subdirectory, such as `https://you.github.io/' + slug(name) + '`.',
		'- `templates/page.php` is the page template. `static/` is copied to the output as it is.',
	];
	for(const sample of samples)
	{
		lines.push('- `' + String(sample.path) + '`: ' + String(sample.purpose));
	}
	if(notes.length > 0)
	{
		lines.push('', '## Notes', '');
		for(const note of notes) lines.push('- ' + String(note));
	}
	lines.push('', '## Deploy it', '', 'Commit `docs/` and point GitHub Pages at it, or publish the directory with any static host. The theme guide is at https://seanmorris.github.io/smgen-extras/themes.html#' + theme.name + '.', '');
	if(themeReadme.trim() !== '')
	{
		lines.push('---', '', '## About the ' + String(theme.manifest.title ?? theme.name) + ' theme', '', themeReadme.replace(/^# .*\n+/, '').trim(), '');
	}
	return lines.join('\n');
}

/**
 * Turn a product name into a directory-ish slug for the README example.
 *
 * @param {string} name Product name.
 * @returns {string} Lowercase hyphenated slug.
 */
export function slug(name)
{
	const value = name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
	return value === '' ? 'my-site' : value;
}

/**
 * Turn a directory name into a readable default product name.
 *
 * @param {string} directory Directory basename.
 * @returns {string} Title-cased words.
 */
export function titleCase(directory)
{
	const words = directory.split(/[-_\s.]+/).filter((word) => word !== '');
	if(words.length === 0) return 'My Site';
	return words.map((word) => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
}
