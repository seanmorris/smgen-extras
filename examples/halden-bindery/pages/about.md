---
title: "About"
description: "Who Halden Bindery is, and what this site demonstrates."
---

# About

Halden Bindery is a fictional studio: one binder, one bench, in an old sail
loft on the quay of a harbour town that does not exist. The bindery, its
clients and its photographs were made to demonstrate the SMGen **portfolio**
theme, one of the base themes in the
[smgen-extras](https://seanmorris.github.io/smgen-extras/themes.html#theme-portfolio)
collection.

![The bindery's shopfront on the quay, door open to the morning.](/images/shopfront.webp)

## How the work is made

Everything here is sewn by hand on linen tape or cord. Cases are covered in
bookcloth or leather pared on a litho stone; paper is chosen with the grain
running head to tail so books open flat and stay flat. Repairs keep as much
of the original as the book can carry.

![Paring leather on the litho stone.](/images/paring.webp)

## How the site works

Each project is one Markdown file under `pages/work/` with a photograph named
in its frontmatter. The front page and the work page read every project's
frontmatter at build time and lay out the grid, newest first. A project without
a photograph yet is listed without a plate, so it can be published before it
is shot.

Start a portfolio of your own with:

```bash
npm create smgen-site@latest my-studio -- --theme portfolio
```
