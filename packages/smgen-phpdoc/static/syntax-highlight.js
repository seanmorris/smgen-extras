/**
 * Re-highlight PHP code blocks in the browser with highlight.js.
 *
 * Pandoc's PHP grammar marks only the first letter of class and namespace
 * names. This script replaces the token spans inside each Pandoc code block
 * with highlight.js tokens, mapped onto Pandoc's own class names so the site's
 * configured highlight style keeps working. Line spans, their anchors, and any
 * line-number or line-highlight markup are preserved.
 */

(function() {
	'use strict';

	/** @type {Record<string, string>} Pandoc class for each highlight.js scope. */
	const scopeClasses = {
		keyword: 'kw',
		string: 'st',
		number: 'dv',
		comment: 'co',
		built_in: 'bu',
		literal: 'cn',
		meta: 'ot',
		attr: 'at',
		type: 'dt',
		operator: 'op',
		punctuation: 'op',
		symbol: 'ss',
		doctag: 'an',
		subst: 'ot',
		regexp: 'ss',
		property: 'va',
		variable: 'va',
	};

	/**
	 * Read the languages this page wants re-highlighted.
	 *
	 * @returns {string[]} Language names as they appear on Pandoc code elements.
	 */
	function configuredLanguages()
	{
		const settings = /** @type {{languages?: unknown}|undefined} */ (window.smgenPhpDocHighlight);
		return Array.isArray(settings?.languages) ? settings.languages.map(String) : ['php'];
	}

	/**
	 * Find the Pandoc language class on a code element.
	 *
	 * @param {Element} code Code element inside a Pandoc source block.
	 * @param {string[]} languages Languages this page re-highlights.
	 * @returns {string|null} Language name, or null when the block is left alone.
	 */
	function blockLanguage(code, languages)
	{
		for(const name of code.classList)
		{
			if(name !== 'sourceCode' && languages.includes(name)) return name;
		}
		return null;
	}

	/**
	 * Convert highlight.js scope spans into Pandoc token spans in place.
	 *
	 * @param {ParentNode} root Parsed highlight.js output.
	 * @returns {void}
	 */
	function convertScopes(root)
	{
		const spans = Array.from(root.querySelectorAll('span'));
		/** @type {Map<Element, string|null>} */
		const decisions = new Map();
		for(const span of spans)
		{
			decisions.set(span, pandocClass(span));
		}
		for(const span of spans)
		{
			const className = decisions.get(span) ?? null;
			if(className === null)
			{
				span.replaceWith(...span.childNodes);
				continue;
			}
			span.className = className;
		}
	}

	/**
	 * Choose the Pandoc class for one highlight.js span, or null to unwrap it.
	 *
	 * @param {Element} span Highlight.js output span.
	 * @returns {string|null} Pandoc class name or null.
	 */
	function pandocClass(span)
	{
		const classes = Array.from(span.classList);
		const scope = classes.find((name) => name.startsWith('hljs-'))?.slice(5);
		if(scope === undefined) return null;
		if(scope === 'title')
		{
			if(classes.includes('function_')) return 'fu';
			if(classes.includes('class_')) return 'dt';
			const previous = span.previousElementSibling;
			return previous !== null && previous.textContent?.trim() === 'function' ? 'fu' : 'dt';
		}
		if(scope === 'variable' && classes.includes('constant_')) return 'cn';
		return scopeClasses[scope] ?? null;
	}

	/**
	 * Split converted token markup into one fragment per source line.
	 *
	 * Spans that cross a line break are closed at the end of one line and
	 * reopened at the start of the next, so each line stays self-contained.
	 *
	 * @param {ParentNode} root Converted highlight.js output.
	 * @returns {DocumentFragment[]} One fragment per line.
	 */
	function splitLines(root)
	{
		/** @type {DocumentFragment[]} */
		const lines = [document.createDocumentFragment()];
		/** @type {Array<{source: Element, clone: Element}>} */
		let stack = [];
		/** @type {ParentNode} */
		let cursor = lines[0];

		/**
		 * Open a copy of a source span at the cursor.
		 *
		 * @param {Element} source Span being copied.
		 * @returns {void}
		 */
		function open(source)
		{
			const clone = /** @type {Element} */ (source.cloneNode(false));
			cursor.append(clone);
			stack.push({source, clone});
			cursor = clone;
		}

		/**
		 * Close the innermost open span.
		 *
		 * @returns {void}
		 */
		function close()
		{
			stack.pop();
			cursor = stack.length > 0 ? stack[stack.length - 1].clone : lines[lines.length - 1];
		}

		/**
		 * Start a new line and reopen every span that was open.
		 *
		 * @returns {void}
		 */
		function newline()
		{
			const fragment = document.createDocumentFragment();
			lines.push(fragment);
			cursor = fragment;
			const sources = stack.map((entry) => entry.source);
			stack = [];
			for(const source of sources) open(source);
		}

		/**
		 * Copy one node's children into the current line structure.
		 *
		 * @param {Node} node Parent node to copy.
		 * @returns {void}
		 */
		function walk(node)
		{
			for(const child of Array.from(node.childNodes))
			{
				if(child.nodeType === Node.TEXT_NODE)
				{
					const parts = (child.textContent ?? '').split('\n');
					parts.forEach((part, index) => {
						if(index > 0) newline();
						if(part !== '') cursor.append(document.createTextNode(part));
					});
				}
				else if(child.nodeType === Node.ELEMENT_NODE)
				{
					open(/** @type {Element} */ (child));
					walk(child);
					close();
				}
			}
		}

		walk(root);
		return lines;
	}

	/**
	 * Replace the tokens in one Pandoc code block with highlight.js tokens.
	 *
	 * @param {Element} code Code element inside a Pandoc source block.
	 * @param {string} language Highlight.js language name.
	 * @param {{highlight: (text: string, options: {language: string, ignoreIllegals: boolean}) => {value: string}}} hljs Highlight.js API.
	 * @returns {void}
	 */
	function rehighlight(code, language, hljs)
	{
		const lineSpans = Array.from(code.children).filter((child) => child.tagName === 'SPAN');
		const text = lineSpans.length > 0 ? lineSpans.map((line) => line.textContent ?? '').join('\n') : (code.textContent ?? '');
		const template = document.createElement('template');
		template.innerHTML = hljs.highlight(text, {language, ignoreIllegals: true}).value;
		convertScopes(template.content);

		if(lineSpans.length === 0)
		{
			code.replaceChildren(template.content);
			return;
		}

		const lines = splitLines(template.content);
		if(lines.length !== lineSpans.length) return;
		lineSpans.forEach((line, index) => {
			const anchor = line.querySelector(':scope > a[aria-hidden]');
			line.replaceChildren(...(anchor === null ? [] : [anchor]), lines[index]);
		});
	}

	/**
	 * Re-highlight every eligible code block on the page once.
	 *
	 * @returns {void}
	 */
	function run()
	{
		const hljs = /** @type {{getLanguage: (name: string) => unknown, highlight: Function}|undefined} */ (window.hljs);
		if(hljs === undefined) return;
		const languages = configuredLanguages();
		for(const code of document.querySelectorAll('pre > code.sourceCode'))
		{
			if(code.hasAttribute('data-highlighter')) continue;
			const language = blockLanguage(code, languages);
			if(language === null || !hljs.getLanguage(language)) continue;
			rehighlight(code, language, /** @type {any} */ (hljs));
			code.setAttribute('data-highlighter', 'highlight.js');
		}
	}

	if(document.readyState === 'loading')
	{
		document.addEventListener('DOMContentLoaded', run);
	}
	else
	{
		run();
	}
})();
