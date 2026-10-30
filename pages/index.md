---
title: SMGen Extras
description: Five starter themes to begin an SMGen site from, and six plugins that add API references, spreadsheet-driven pages, category rollups, a dated archive, and static checkout controls.
layout: home
TOC: false
llms:
  section: start
  description: Choose a theme to start from and the plugins to install.
menus:
  documentation:
    title: Overview
    weight: 0
jsonld:
  "@context": https://schema.org
  "@graph":
    - "@type": WebSite
      "@id": https://seanmorris.github.io/smgen-extras/#website
      url: https://seanmorris.github.io/smgen-extras/
      name: smgen-extras
      description: Themes and plugins for static SMGen sites.
      inLanguage: en
    - "@type": CollectionPage
      "@id": https://seanmorris.github.io/smgen-extras/#page
      url: https://seanmorris.github.io/smgen-extras/
      name: SMGen Extras
      isPartOf:
        "@id": https://seanmorris.github.io/smgen-extras/#website
      about:
        "@type": SoftwareApplication
        name: SMGen
        url: https://seanmorris.github.io/smgen/
---

<section class="band" id="examples" aria-labelledby="examples-title">
<div class="band-head"><h2 id="examples-title">Four sites, built with the plugins</h2><p>Every example is rebuilt by CI from the sources in <code>examples/</code>. Open one and view the page source.</p></div>
<ul class="previews">
<li><a href="examples/store/" target="_blank" rel="noopener noreferrer"><img class="preview preview--dark" src="previews/store-dark.png" width="900" height="585" alt="Fernway Goods storefront home page" loading="lazy"><img class="preview preview--light" src="previews/store-light.png" width="900" height="585" alt="Fernway Goods storefront home page" loading="lazy"><span class="preview-body"><strong>Fernway Goods</strong><span>A storefront whose catalog is a CSV, with generated category pages and hosted checkout.</span><small class="package">smgen-data-pages + smgen-categories + smgen-store</small></span></a></li>
<li><a href="examples/blog/" target="_blank" rel="noopener noreferrer"><img class="preview preview--dark" src="previews/blog-dark.png" width="900" height="585" alt="Field Notes journal home page" loading="lazy"><img class="preview preview--light" src="previews/blog-light.png" width="900" height="585" alt="Field Notes journal home page" loading="lazy"><span class="preview-body"><strong>Field Notes</strong><span>A journal of Markdown files with generated topic pages and a dated archive.</span><small class="package">smgen-timeline + smgen-categories</small></span></a></li>
<li><a href="examples/dispatch/" target="_blank" rel="noopener noreferrer"><img class="preview preview--dark" src="previews/dispatch-dark.png" width="900" height="585" alt="Dispatch documentation site home page" loading="lazy"><img class="preview preview--light" src="previews/dispatch-light.png" width="900" height="585" alt="Dispatch documentation site home page" loading="lazy"><span class="preview-body"><strong>Dispatch</strong><span>Guides beside two API references, one generated from PHP source and one from JavaScript and JSDoc.</span><small class="package">smgen-phpdoc + smgen-jsdoc</small></span></a></li>
<li><a href="examples/data-pages/" target="_blank" rel="noopener noreferrer"><img class="preview preview--dark" src="previews/data-pages-dark.png" width="900" height="585" alt="Greywater Range trail guide home page" loading="lazy"><img class="preview preview--light" src="previews/data-pages-light.png" width="900" height="585" alt="Greywater Range trail guide home page" loading="lazy"><span class="preview-body"><strong>Greywater Range</strong><span>A trail guide with twenty-eight trail and campground pages rendered from two spreadsheets.</span><small class="package">smgen-data-pages</small></span></a></li>
</ul>
</section>

<section class="band" aria-labelledby="plugins-title">
<div class="band-head"><h2 id="plugins-title">The plugins</h2></div>
<ol class="plugins">
<li><div class="plugin-name"><span class="package">smgen-phpdoc</span><a href="phpdoc.html">PHP API references</a></div><div class="plugin-body"><p>Parses PHP source as text, never loading it, and renders one linked page per class, interface, trait, enum, and function. Pages join the site's menus, search index, sitemap, and <code>llms.txt</code>.</p><ul><li>Documentation policy with warn or error levels</li><li>Source links pinned to a commit</li><li>Namespace index pages and stable anchors</li></ul></div></li>
<li><div class="plugin-name"><span class="package">smgen-jsdoc</span><a href="jsdoc.html">JavaScript API references</a></div><div class="plugin-body"><p>Reads public exports and JSDoc through the TypeScript compiler without importing the modules, then renders linked pages for modules, functions, classes, values, and types.</p><ul><li>Explicit JSDoc with inferred JavaScript types</li><li>Static analysis that never runs project code</li><li>No browser assets added to the published site</li></ul></div></li>
<li><div class="plugin-name"><span class="package">smgen-data-pages</span><a href="data-pages.html">Pages from a spreadsheet</a></div><div class="plugin-body"><p>Validates CSV or TSV rows against the columns you declare, then renders one Markdown page per row through a PHP template, plus any collection pages you want from the same rows.</p><ul><li>Required columns, row filters, and reserved output paths</li><li>Rows join menus through their own frontmatter</li><li>A local <code>validate</code> command for CI</li></ul></div></li>
<li><div class="plugin-name"><span class="package">smgen-categories</span><a href="categories.html">Category pages</a></div><div class="plugin-body"><p>Groups every Markdown page, authored or generated, by a frontmatter field and writes one rollup page per value. Dates group by day, month, or year.</p><ul><li>Per-site PHP template for each category</li><li>Flat pages or directory indexes</li><li>Members, counts, and labels in frontmatter for other plugins</li></ul></div></li>
<li><div class="plugin-name"><span class="package">smgen-timeline</span><a href="timeline.html">Dated archive</a></div><div class="plugin-body"><p>A calendar and year and month groups for every page with a date, built on category pages so a busy day gets a page of its own.</p><ul><li>Nothing to add beyond the date</li><li>Single-post days link to the post</li><li>Calendar steps through every month in place</li></ul></div></li>
<li><div class="plugin-name"><span class="package">smgen-store</span><a href="store.html">Static storefronts</a></div><div class="plugin-body"><p>Checks the checkout metadata on product pages and inserts hosted checkout controls. The provider owns the price, the payment state, inventory, and fulfilment; the page only links to it.</p><ul><li>HTTPS-only links, credential-shaped values rejected</li><li>Stripe and PayPal embeds load only where used</li><li>Build fails before publish on bad data</li></ul></div></li>
</ol>
</section>

<section class="band" id="themes" aria-labelledby="themes-title">
<div class="band-head"><h2 id="themes-title">Five themes, one command</h2><p>Each theme is a complete, plugin-free site with real sample pages. Copy one with <code>npm create smgen-site</code>, rename it, and start writing.</p></div>
<ul class="previews previews--three">
<li><a href="examples/kestrel/" target="_blank" rel="noopener noreferrer"><img class="preview preview--dark" src="previews/theme-docs-dark.png" width="900" height="585" alt="Kestrel documentation home page" loading="lazy"><img class="preview preview--light" src="previews/theme-docs-light.png" width="900" height="585" alt="Kestrel documentation home page" loading="lazy"><span class="preview-body"><strong>Docs</strong><span>A reference manual: numbered sections, notes in the margin, code with a rule and a label. Shown as the Kestrel example.</span><small class="package">smgen-theme-docs</small></span></a></li>
<li><a href="examples/commonplace/" target="_blank" rel="noopener noreferrer"><img class="preview preview--dark" src="previews/theme-journal-dark.png" width="900" height="585" alt="Commonplace journal home page" loading="lazy"><img class="preview preview--light" src="previews/theme-journal-light.png" width="900" height="585" alt="Commonplace journal home page" loading="lazy"><span class="preview-body"><strong>Journal</strong><span>Dated entries listed from their frontmatter, topic pages and an RSS feed. Shown as the Commonplace example.</span><small class="package">smgen-theme-journal</small></span></a></li>
<li><a href="examples/halden-bindery/" target="_blank" rel="noopener noreferrer"><img class="preview preview--dark" src="previews/theme-portfolio-dark.png" width="900" height="585" alt="Halden Bindery portfolio home page" loading="lazy"><img class="preview preview--light" src="previews/theme-portfolio-light.png" width="900" height="585" alt="Halden Bindery portfolio home page" loading="lazy"><span class="preview-body"><strong>Portfolio</strong><span>A maker's catalogue set like a book: numbered entries, photographs as plates, a specification block and a drop cap. Shown as the Halden Bindery example.</span><small class="package">smgen-theme-portfolio</small></span></a></li>
<li><a href="examples/tidewatch/" target="_blank" rel="noopener noreferrer"><img class="preview preview--dark" src="previews/theme-landing-dark.png" width="900" height="585" alt="Tidewatch landing page" loading="lazy"><img class="preview preview--light" src="previews/theme-landing-light.png" width="900" height="585" alt="Tidewatch landing page" loading="lazy"><span class="preview-body"><strong>Landing</strong><span>A one-page product site: hero with a screen drawn from data, figures, features with readouts, a plan table, all YAML in the front page. Shown as the Tidewatch example.</span><small class="package">smgen-theme-landing</small></span></a></li>
<li><a href="themes/semantic/" target="_blank" rel="noopener noreferrer"><img class="preview preview--dark" src="previews/theme-semantic-dark.png" width="900" height="585" alt="Plain semantic site home page" loading="lazy"><img class="preview preview--light" src="previews/theme-semantic-light.png" width="900" height="585" alt="Plain semantic site home page" loading="lazy"><span class="preview-body"><strong>Semantic</strong><span>Classless three-column HTML and a stylesheet under twenty lines, with pages that show every Markdown construct. Shown as Plain.</span><small class="package">smgen-theme-semantic</small></span></a></li>
<li class="preview-cta"><a href="themes.html"><span class="preview-body"><strong>Pick one</strong><span>The Themes guide walks through the command, what each theme contains, and how to make your own.</span></span></a></li>
</ul>
</section>

<section class="band band--split" aria-labelledby="install-title">
<div>
<div class="band-head"><h2 id="install-title">Install one</h2></div>

Install the package beside SMGen, activate it in `.smgen.yaml`, and run the
normal build. SMGen reads the package manifest, runs the plugin's build hooks,
and keeps output replacement atomic.

```bash
npm install --save-dev smgen-phpdoc
```

```yaml
PLUGINS:
  - smgen-phpdoc
```

```bash
npx smgen build
```

</div>
<div>
<div class="band-head"><h2>Combine them</h2></div>

Data pages, categories, and the store form a small static commerce pipeline.
A CSV row supplies the product, a PHP template turns it into Markdown with
checkout frontmatter, categories group those pages into a page per product
category, and the store plugin validates the checkout frontmatter before SMGen
publishes. The [Fernway Goods example](examples/store/) is exactly that
pipeline.

The timeline builds on categories too: it asks for a day rollup of every dated
page, so a blog gets a calendar, an archive, and a page for each busy day from
nothing more than the dates it already has. The
[Field Notes example](examples/blog/) adds its own topic category beside it.

PHPDoc and JSDoc are independent. They can document the PHP and JavaScript used
by a site, a library, or another plugin in the same build.

</div>
</section>
