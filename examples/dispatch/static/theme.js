// Remember the visitor's colour theme and switch it from a header button.
//
// Load this file as a blocking head script (SCRIPTS). The site is dark by
// default; a stored choice of "light" applies before the first paint. Any
// element with data-theme-toggle switches the theme and stores the choice.

(function() {
	const root = document.documentElement;
	const storageKey = 'theme';

	/**
	 * Read the stored theme without making storage availability mandatory.
	 *
	 * @returns {string} "light" or "dark".
	 */
	function storedTheme()
	{
		try
		{
			return localStorage.getItem(storageKey) === 'light' ? 'light' : 'dark';
		}
		catch(error)
		{
			return 'dark';
		}
	}

	/**
	 * Apply a theme to the document and label every toggle for the other one.
	 *
	 * @param {string} theme "light" or "dark".
	 * @returns {void}
	 */
	function apply(theme)
	{
		root.setAttribute('data-theme', theme);
		root.style.colorScheme = theme;
		for(const toggle of document.querySelectorAll('[data-theme-toggle]'))
		{
			toggle.setAttribute('aria-label', theme === 'dark' ? 'Switch to light theme' : 'Switch to dark theme');
			toggle.setAttribute('aria-pressed', theme === 'light' ? 'true' : 'false');
		}
	}

	/**
	 * Switch to the other theme and remember it when storage allows.
	 *
	 * @returns {void}
	 */
	function toggle()
	{
		const next = root.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
		apply(next);
		try
		{
			localStorage.setItem(storageKey, next);
		}
		catch(error)
		{
			// Storage may be unavailable; the choice still applies to this page.
		}
	}

	apply(storedTheme());

	document.addEventListener('DOMContentLoaded', function() {
		apply(root.getAttribute('data-theme') || 'dark');
	});

	document.addEventListener('click', function(event) {
		const target = event.target instanceof Element ? event.target.closest('[data-theme-toggle]') : null;
		if(!target) return;
		event.preventDefault();
		toggle();
	});
})();
