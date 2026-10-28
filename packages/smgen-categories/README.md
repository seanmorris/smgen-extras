# smgen-categories

Generate one SMGen page for every value of a frontmatter field. A category can be a topic, an author, a product line, or a date, grouped by day, month, or year. The generated page lists its members and carries metadata other plugins can read, so `smgen-timeline` builds its day rollups on this package.

The plugin runs in the `prepare` phase, reads every Markdown page under `PAGES_DIR`, including pages that earlier plugins generated in the same build, and writes rollup pages into its own managed directory. Nothing runs in the browser.

## Install and Activate

```bash
npm install --save-dev smgen-categories
```

```yaml
PLUGINS:
  - smgen-data-pages
  - use: smgen-categories
    options:
      config: .smgen-categories.yaml
```

List it after any plugin whose generated pages should be members.

## Define Categories

```yaml
version: 1
categories:
  topic:
    field: topic                 # frontmatter key on member pages
    pages: ['posts/**/*.md']     # globs against the public-relative source path; default **/*.md
    exclude: ['posts/drafts/**']
    where: {published: true}     # exact frontmatter matches a member must have
    unless: {archive: false}     # exact frontmatter matches that drop a page
    group: value                 # value | day | month | year
    output: topics/{slug}.md     # {slug} or {value}
    template: templates/topic.md.php
    minimum: 1
    sort: date-desc              # date-desc | date-asc | title | path
```

- `field` names the frontmatter key, or a dotted path into a mapping such as `product.category`. A scalar value files the page under one category; a list files it under each entry.
- `group: day`, `month`, or `year` parses the field as a date and groups by that unit. The value becomes `2026-10-24`, `2026-10`, or `2026`; the label becomes `October 24, 2026`, `October 2026`, or `2026`.
- `output` may publish a flat page, `topics/{slug}.md`, or a directory index, `topics/{slug}/index.md`, which SMGen serves at `/topics/walks/`. A rollup is never required to be its directory's index.
- `where` and `unless` compare frontmatter values exactly, preserving YAML types, so `false` does not match the string `"false"`.
- `minimum` suppresses groups with fewer members. The timeline uses `2` so single-post days keep linking to the post.
- `template` is a PHP template inside the project. Omit it for the built-in list.

Generated rollups are never members of a category, so a second build does not nest them.

## Write a Template

A template receives:

- `$category`: `name`, `field`, `group`, `value`, `label`, `slug`, `count`.
- `$members`: one record per page with `source`, `path` (public URL path), `title`, `description`, `date`, and the full `metadata` mapping.
- `$context`: `frontmatter()`, `yaml()`, `escapeHtml()`, `pathSegment()`, and `url()`, which prefixes the site's `BASE_URL` onto a member path so links work when the site is published below a path prefix.

```php
<?php
echo $context->frontmatter(['title' => $category['label'], 'layout' => 'topic']);
?>
# <?=$context->escapeHtml($category['label'])?>

<?php foreach($members as $member): ?>
- [<?=$context->escapeHtml($member['title'])?>](<?=$context->escapeHtml($context->url($member['path']))?>)
<?php endforeach; ?>
```

`frontmatter()` adds a `title` and a `category` mapping (`name`, `value`, `label`, `count`, `members`) to whatever the template passes, unless the template sets those keys itself.

## Validate Locally

```bash
npx smgen-categories validate --root . --pages pages
npx smgen-categories generate --root . --pages pages --output /tmp/rollups --base-url https://example.test/site
```

`validate` renders every rollup in memory and reports bad dates, unsafe values, collisions, and template errors with the page and category that caused them.

## Use From Another Plugin

`SmgenCategories\Categories::fromDefinitions($projectRoot, $definitions, $baseUrl)` accepts the same definition shape without a configuration file and allows absolute template paths, so a plugin can ship its own default template. `render($pagesRoot, $skipGeneratedId)` returns the Markdown documents; `generate($pagesRoot, $outputRoot, $skipGeneratedId)` installs them.
