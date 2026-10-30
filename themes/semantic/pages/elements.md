---
title: Elements
description: Every Markdown construct SMGen renders, shown with the default browser styling.
menus:
  site:
    weight: 10
---

# Elements

This page exists so you can see the HTML before you style it. View the
source of this page to see what each block becomes.

## Text

A paragraph with *emphasis*, **strong emphasis**, `inline code`, a
[link](https://seanmorris.github.io/smgen/), and a footnote.[^1] Pandoc
turns straight quotes into "curly" ones and three dots into an ellipsis...

Line breaks inside a paragraph  
with two trailing spaces become `br` elements.

[^1]: Footnotes collect at the end of the page in a `section` with the
    class `footnotes`.

## Headings

Six levels exist. Only the first three appear in the outline.

### Third level

#### Fourth level

##### Fifth level

###### Sixth level

## Lists

- An unordered list
- With a nested list
  - Two levels deep
  - Still a plain `ul`
- And a final item

1. An ordered list
2. Numbered by the browser
3. From a plain `ol`

Term
: A definition list uses `dl`, `dt` and `dd`.

Another term
: With as many definitions as you like.

## Quotations

> A block quotation. Browsers indent it and nothing else.
>
> It can hold more than one paragraph.

## Code

```php
<?php
echo 'A fenced block with a language becomes pre > code with Pandoc token spans.';
```

    An indented block is a plain pre > code with no highlighting.

## Tables

| Element | Produced by | Default look |
| --- | --- | --- |
| `table` | A pipe table | Borderless until styled |
| `thead` | The header row | Bold cells |
| `tbody` | Every other row | Plain cells |

## Figures

![A square, a circle and a triangle drawn as SVG.](shapes.svg)

An image alone in a paragraph becomes a `figure` with a `figcaption`.

## Details

<details>
<summary>Raw HTML passes through</summary>

A `details` element written directly in Markdown, with Markdown inside it.

</details>

## Rules

Three dashes make a thematic break:

---

That is the last element.
