# smgen-timeline

Add a dated archive to an SMGen site: a calendar for the current or newest month, expandable year and month groups, and a page for every day that has more than one post. Posts need nothing beyond the `date` they already carry.

The timeline was once built into SMGen. It now lives here as a menu renderer plugin and builds its day pages with [smgen-categories](../smgen-categories), which this package depends on.

## Install and Activate

```bash
npm install --save-dev smgen-timeline
```

```yaml
PLUGINS:
  - smgen-timeline
MENU_REGIONS:
  sidebar: [archive]
```

The plugin defines an `archive` menu with the `timeline` renderer. Place it in a region and every page with a `date` joins it. A page opts out with `archive: false`. A page that declares its own `menus` field keeps explicit membership instead.

## What the Widget Shows

- A calendar for the month holding the current page, or the newest month. A day with one post links to that post. A day with several posts shows a count and links to that day's rollup page.
- Year and month groups as native `details` elements with counts. The group holding the current page always renders open, and SMGen's `nav-state.js` remembers the rest.
- One line per post under each month, newest first.

The widget's stylesheet is published at `/smgen-timeline/timeline.css` and linked only on pages where the menu renders with content. Class names (`archive-calendar`, `archive-timeline`, `archive-count`, `archive-day-count`) are unchanged from the built-in version, so existing site CSS keeps working.

## Day Rollup Pages

During the `prepare` phase the plugin asks smgen-categories for a `day` grouping of every dated page with a minimum of two members. Each rollup lands at `archive/<Y-m-d>.md` by default and carries `category` frontmatter with the day and its member paths.

```yaml
PLUGINS:
  - use: smgen-timeline
    options:
      output: journal/{value}/index.md   # must contain {value}; may be a directory index
      template: templates/day.md.php     # project-relative; omit for the built-in list
      pages: ['posts/**/*.md']           # which pages may form a day; default **/*.md
      exclude: []
```

A site template receives `$category` (`value` is `2026-10-24`, `label` is `October 24, 2026`), `$members`, and `$context`, exactly as smgen-categories documents. The built-in template sets `archive: false` so a rollup never joins the timeline itself.

## Dates

A page's day comes from `date`, then `created`. A page with neither gets the file's birth time, its first Git addition time, or its modification time, and SMGen writes that value back as `created` so later builds stay stable.
