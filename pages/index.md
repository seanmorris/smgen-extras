---
title: SMGen Plugins
description: Add generated PHP API references, spreadsheet-driven pages, and static checkout controls to an SMGen site.
layout: home
TOC: false
llms:
  section: start
  description: Choose and install the official SMGen plugins.
menus:
  documentation:
    title: Overview
    weight: 0
jsonld:
  "@context": https://schema.org
  "@graph":
    - "@type": WebSite
      "@id": https://seanmorris.github.io/smgen-plugins/#website
      url: https://seanmorris.github.io/smgen-plugins/
      name: smgen-plugins
      description: Focused extensions for static SMGen sites.
      inLanguage: en
    - "@type": CollectionPage
      "@id": https://seanmorris.github.io/smgen-plugins/#page
      url: https://seanmorris.github.io/smgen-plugins/
      name: SMGen Plugins
      isPartOf:
        "@id": https://seanmorris.github.io/smgen-plugins/#website
      about:
        "@type": SoftwareApplication
        name: SMGen
        url: https://seanmorris.github.io/smgen/
---

<section class="band" id="examples" aria-labelledby="examples-title">
<div class="band-head"><h2 id="examples-title">Four sites, built from this repository</h2><p>Every example is rebuilt by CI from the sources in <code>examples/</code>. Open one and view the page source.</p></div>
<ul class="previews">
<li><a href="examples/phpdoc/" target="_blank" rel="noopener noreferrer"><img src="previews/phpdoc.png" width="900" height="585" alt="Postbox documentation site home page" loading="lazy"><span class="preview-body"><strong>Postbox</strong><span>Guides beside an API reference generated from PHP source.</span><small class="package">smgen-phpdoc</small></span></a></li>
<li><a href="examples/data-pages/" target="_blank" rel="noopener noreferrer"><img src="previews/data-pages.png" width="900" height="585" alt="Greywater Range trail guide home page" loading="lazy"><span class="preview-body"><strong>Greywater Range</strong><span>A trail guide with twenty-eight pages rendered from two spreadsheets.</span><small class="package">smgen-data-pages</small></span></a></li>
<li><a href="examples/store/" target="_blank" rel="noopener noreferrer"><img src="previews/store.png" width="900" height="585" alt="Fernway Goods storefront home page" loading="lazy"><span class="preview-body"><strong>Fernway Goods</strong><span>A storefront whose catalog is a CSV, with generated category pages and hosted checkout.</span><small class="package">smgen-data-pages + smgen-categories + smgen-store</small></span></a></li>
<li><a href="examples/blog/" target="_blank" rel="noopener noreferrer"><img src="previews/blog.png" width="900" height="585" alt="Field Notes journal home page" loading="lazy"><span class="preview-body"><strong>Field Notes</strong><span>A journal of Markdown files with generated topic pages and a dated archive.</span><small class="package">smgen-timeline + smgen-categories</small></span></a></li>
</ul>
</section>

<section class="band" aria-labelledby="plugins-title">
<div class="band-head"><h2 id="plugins-title">The plugins</h2></div>
<ol class="plugins">
<li><div class="plugin-name"><span class="package">smgen-phpdoc</span><a href="phpdoc.html">PHP API references</a></div><div class="plugin-body"><p>Parses PHP source as text, never loading it, and renders one linked page per class, interface, trait, enum, and function. Pages join the site's menus, search index, sitemap, and <code>llms.txt</code>.</p><ul><li>Documentation policy with warn or error levels</li><li>Source links pinned to a commit</li><li>Namespace index pages and stable anchors</li></ul></div></li>
<li><div class="plugin-name"><span class="package">smgen-data-pages</span><a href="data-pages.html">Pages from a spreadsheet</a></div><div class="plugin-body"><p>Validates CSV or TSV rows against the columns you declare, then renders one Markdown page per row through a PHP template, plus any collection pages you want from the same rows.</p><ul><li>Required columns, row filters, and reserved output paths</li><li>Rows join menus through their own frontmatter</li><li>A local <code>validate</code> command for CI</li></ul></div></li>
<li><div class="plugin-name"><span class="package">smgen-categories</span><a href="categories.html">Category pages</a></div><div class="plugin-body"><p>Groups every Markdown page, authored or generated, by a frontmatter field and writes one rollup page per value. Dates group by day, month, or year.</p><ul><li>Per-site PHP template for each category</li><li>Flat pages or directory indexes</li><li>Members, counts, and labels in frontmatter for other plugins</li></ul></div></li>
<li><div class="plugin-name"><span class="package">smgen-timeline</span><a href="timeline.html">Dated archive</a></div><div class="plugin-body"><p>A calendar and year and month groups for every page with a date, built on category pages so a busy day gets a page of its own.</p><ul><li>Nothing to add beyond the date</li><li>Single-post days link to the post</li><li>Stylesheet linked only where the widget renders</li></ul></div></li>
<li><div class="plugin-name"><span class="package">smgen-store</span><a href="store.html">Static storefronts</a></div><div class="plugin-body"><p>Checks the checkout metadata on product pages and inserts hosted checkout controls. The provider owns the price, the payment state, inventory, and fulfilment; the page only links to it.</p><ul><li>HTTPS-only links, credential-shaped values rejected</li><li>Stripe and PayPal embeds load only where used</li><li>Build fails before publish on bad data</li></ul></div></li>
</ol>
</section>

<section class="band band--split" aria-labelledby="install-title">
<div>
<div class="band-head"><h2 id="install-title">Install one</h2></div>

Install the package beside SMGen, activate it in `.smgen.yaml`, and run the
normal build. SMGen reads the package manifest, runs the plugin's prepare hook,
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

Data pages and the store form a small static commerce pipeline. A CSV row
supplies the product, a PHP template turns it into Markdown with checkout
frontmatter, and the store plugin validates that frontmatter before SMGen
publishes the page. The [Fernway Goods example](examples/store/) is exactly
that pipeline.

PHPDoc is independent. It can document the PHP used by a site, a library, or
another plugin in the same build.

</div>
</section>
