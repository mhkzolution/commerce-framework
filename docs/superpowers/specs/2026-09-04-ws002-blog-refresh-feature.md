# WS-002 Blog Refresh — Feature (v1.13.0)

**Date:** 2026-09-04  
**Status:** Locked  
**Owner:** `modules/Cms/resources/views/storefront/posts` + `resources/css/storefront/blog.css` + `resources/views/components/storefront/blog`  
**Mode:** Feature development on the current release line (not recovery)

```text
v1.11.0 Shop Listing
v1.12.0 Header Foundation       ← done
v1.13.0 Blog Refresh            ← this file
later   PDP Refresh, Appearance/Theming
```

Dependencies Blog needs are already on `main`:

```text
✓ Design tokens (--store-max-width 80rem, --store-gutter, --store-max-width-narrow)
✓ page-container
✓ site-header / site-footer
✓ Shop listing layout (full-bleed main + page-container)
✓ pagination::storefront
✓ x-storefront.empty-state
✓ v1.3.0 blog archive / article / adapters
```

v1.13 is not "recover the archive blog" and not a second v1.3.0 redesign. It is Blog joining the current storefront width and chrome.

```text
git merge feat/commerce-framework-v1
git merge feat/homepage-cms-preservation
git merge feat/barcode-stock-recovery
git checkout 84e905c -- .
```

Do not path-extract wholesale blog markup from archive. Current storefront on `main` is ahead of that snapshot (Header, Shop listing, tokens).

Implementation spec: `docs/superpowers/specs/2026-09-04-ws002-blog-v1-implementation.md`  
Branch after both specs are committed on `main`: `feat/ws002-blog-v1`

---

## 1. Layout inventory (from `main`, not archive)

Sources:

```text
modules/Cms/resources/views/storefront/posts/index.blade.php
modules/Cms/resources/views/storefront/posts/show.blade.php
resources/css/storefront/blog.css
modules/Cart/resources/views/layouts/storefront.blade.php
```

Header and Footer are already the shared primitives. Blog views must not render `site-header` / `site-footer`.

### Width today

```text
Homepage / Shop / Header inner    --store-max-width 80rem + --store-gutter 1.5rem
Blog <main>                       .storefront-blog-shell
                                    max-width 87.5rem (1400px)
                                    padding 1.25rem / 2rem / 2.5rem (not --store-gutter)
Article reading column            hardcoded max-width 56.25rem
                                    (same number as --store-max-width-narrow, not the token)
```

`blog.css` also defines unused `.storefront-page-container--wide` (87.5rem) and `.storefront-page-container--reading` (56.25rem). Those are **not** `x-storefront.layout.page-container`. Do not keep a parallel 87.5rem container.

Layout default `<main>` is still `max-w-5xl`. Shop escapes it with `storefront-shop-main` (full bleed) and wraps content in page-container. Blog escapes it with `storefront-blog-shell` on `<main>` itself — width lives on the layout chrome, not on a page-container.

### Archive (`posts/index`)

```text
main.storefront-blog-shell
  .storefront-blog[data-blog]
    x-storefront.blog.toolbar          title, description, GET search, category pills, sort
    .storefront-blog__main
      x-storefront.blog.featured-article   when $featured
      x-storefront.blog.article-grid
        x-storefront.blog.article-card  or  x-storefront.empty-state
      .storefront-blog__pagination      $posts->links()   ← Laravel default, not pagination::storefront
```

Search is `x-admin.search-input` (Shop already left admin search chrome).  
Sort is existing `x-storefront.forms.sort-dropdown` (v1.3.0 adapter).  
Empty state already uses `x-storefront.empty-state`.

### Article (`posts/show`)

```text
main.storefront-blog-shell
  .storefront-article-page
    article.storefront-article
      header band (breadcrumb, title, dek, meta)     max-width 56.25rem
      hero image
      body (TOC + prose + share)
    x-storefront.blog.related-articles
    .storefront-article-end CTA
```

Breadcrumb "Home" currently points at `storefront.shop.index`, not `storefront.home`. Leave that unless the implementation spec calls it out as a one-line fix.

### Vite (UI-TECH-001)

Layout already `@vite`s `app.css` / `app.js` / `footer.css`. Both blog views `@push('scripts') @vite('resources/js/storefront/blog.js')`. In dev that injects a second `@vite/client`. `blog.js` is a no-op unless `[data-blog]` or `[data-article]` exists.

### Unused on these pages

```text
x-storefront.blog.newsletter     only referenced from sidebar
x-storefront.blog.sidebar        not included by index/show
CMS page.blade.php               static pages, not blog — out of v1.13
```

---

## 2. What this is

```text
Homepage  page-container
Shop      page-container     (v1.11)
Header    page-container     (v1.12)
Blog      page-container     ← this tag
```

Same move Shop already made: full-bleed `<main>`, content in `x-storefront.layout.page-container`. Keep the editorial components from v1.3.0. Do not restyle the article as a product card. Do not change Header, Shop listing, or the product card.

---

## 3. Definition of Done (v1.13)

```text
Layout        Archive + article: @section('main_class') full-bleed (not max-w-5xl, not 87.5rem on <main>)
              Content wrapped in x-storefront.layout.page-container
              Archive uses default (80rem); article reading column uses variant=narrow (56.25rem token)
Toolbar       Keep GET search / categories / sort. Replace x-admin.search-input with storefront markup
Empty         Keep x-storefront.empty-state
Pagination    pagination::storefront (same view Shop uses)
Cards         featured-article / article-card / article-grid unchanged in contract
Header        unchanged (layout site-header)
Vite          no second @vite/client on blog pages (blog.js loaded without a second @vite directive)
Tokens        drop 87.5rem blog-shell width; gutters from --store-gutter; reading width from --store-max-width-narrow
```

---

## 4. Owner

```text
WS-002     page-container (already), blog.css token adoption, storefront pagination reuse
Cms        posts/index.blade.php, posts/show.blade.php, toolbar search markup
Cart       layout <main> default unchanged (Blog overrides main_class like Shop)
```

---

## 5. Out of scope

```text
Header / Footer / Shop listing / Product card / PDP
Admin post editor, slash menu, inspector
Scheduled publishing
CMS static page.blade.php
Wiring newsletter / sidebar
New search engine / Ajax listing (keep GET; existing debounce in blog.js is allowed)
Mega menu / sticky header
Appearance / theme engine
Archive merge / git checkout 84e905c -- .
```

---

## 6. Sequence

```text
Blog feature spec            ← this file
↓
Blog implementation spec
↓
commit docs on main
↓
feat/ws002-blog-v1
↓
Blog isolation tests (red)
↓
layout + page-container
↓
pagination + search chrome
↓
vite single client
↓
PR
↓
v1.13.0
```

Do not implement on `main`. Isolation tests before production classes.
