// Browse the archive calendar in place: step links and year chips fetch a
// month partial and swap it into the widget. Without this script they are
// ordinary links into the target month.
(() => {
	if(window.smgenTimelineCalendar) return;
	window.smgenTimelineCalendar = true;

	const partials = new Map();

	/** Fetch a month partial once and share the pending or finished text. */
	const load = url => {
		if(!partials.has(url))
		{
			partials.set(url, fetch(url, {credentials: 'same-origin'}).then(response => {
				if(!response.ok) throw new Error('Calendar partial ' + response.status);
				return response.text();
			}).catch(error => {
				partials.delete(url);
				throw error;
			}));
		}
		return partials.get(url);
	};

	/** Find the calendar step or year chip under an event target. */
	const stepFor = target => {
		const link = target.closest?.('.archive-calendar a[data-calendar]');
		return link && link.closest('.archive-calendar') ? link : null;
	};

	/** Mark the day cell whose link is the page being viewed. */
	const markCurrent = calendar => {
		for(const link of calendar.querySelectorAll('td.has-posts > a'))
		{
			if(link.pathname === location.pathname) link.setAttribute('aria-current', 'date');
		}
	};

	/** Warm the partials for the previous and next months of a calendar. */
	const prefetchNeighbours = calendar => {
		for(const link of calendar.querySelectorAll('.archive-calendar-heading a[data-calendar]'))
		{
			load(link.dataset.calendar).catch(() => {});
		}
	};

	/**
	 * Swap a month into the calendar while keeping the existing step and chip
	 * elements, so a mousedown-started navigation (SMGen's main.js) still targets
	 * the same anchor.
	 */
	const apply = (calendar, html) => {
		const next = document.createElement('div');
		next.innerHTML = html;
		const heading = calendar.querySelector('.archive-calendar-heading');
		const nextHeading = next.querySelector('.archive-calendar-heading');
		if(heading && nextHeading)
		{
			for(const rel of ['prev', 'next'])
			{
				const current = heading.querySelector('[rel="' + rel + '"]');
				const incoming = nextHeading.querySelector('[rel="' + rel + '"]');
				if(current && incoming)
				{
					current.href = incoming.getAttribute('href');
					current.dataset.calendar = incoming.dataset.calendar;
					current.setAttribute('aria-label', incoming.getAttribute('aria-label'));
				}
				else if(current)
				{
					current.replaceWith(incoming ?? Object.assign(document.createElement('span'), {ariaHidden: 'true'}));
				}
				else if(incoming)
				{
					heading.querySelector(rel === 'prev' ? ':scope > :first-child' : ':scope > :last-child').replaceWith(incoming);
				}
			}
			heading.querySelector('strong').textContent = nextHeading.querySelector('strong').textContent;
		}
		const table = calendar.querySelector('table');
		const nextTable = next.querySelector('table');
		if(table && nextTable) table.replaceWith(nextTable);
		const years = calendar.querySelector('.archive-calendar-years');
		const nextYears = next.querySelector('.archive-calendar-years');
		if(years && nextYears)
		{
			const incoming = [...nextYears.querySelectorAll("a")];
			for(const chip of years.querySelectorAll('a'))
			{
				const match = incoming.find(candidate => candidate.textContent === chip.textContent);
				if(match && match.hasAttribute('aria-current')) chip.setAttribute('aria-current', 'true');
				else chip.removeAttribute('aria-current');
			}
		}
		markCurrent(calendar);
		prefetchNeighbours(calendar);
	};

	document.addEventListener('click', event => {
		if(event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
		const link = stepFor(event.target);
		if(!link) return;
		event.preventDefault();
		const calendar = link.closest('.archive-calendar');
		const href = link.href;
		calendar.classList.add('is-loading');
		load(link.dataset.calendar).then(html => {
			if(!calendar.isConnected) return;
			apply(calendar, html);
		}).catch(() => {
			location.href = href;
		}).finally(() => calendar.classList.remove('is-loading'));
	});

	/** Prefetch the partial behind a hovered, focused, or touched step. */
	const prefetch = event => {
		const link = stepFor(event.target);
		if(link) load(link.dataset.calendar).catch(() => {});
	};
	document.addEventListener('mouseover', prefetch);
	document.addEventListener('focusin', prefetch);
	document.addEventListener('touchstart', prefetch, {passive: true});

	/** Prefetch the neighbours of every calendar on the page. */
	const start = () => {
		for(const calendar of document.querySelectorAll('.archive-calendar')) prefetchNeighbours(calendar);
	};
	document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', start) : start();
})();
