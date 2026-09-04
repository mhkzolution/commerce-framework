# WS-002 Blog Refresh v1 — Implementation Spec (v1.13.0)

**Date:** 2026-09-04  
**Status:** Locked  
**Tag:** v1.13.0  
**Branch:** `feat/ws002-blog-v1`

Feature: `docs/superpowers/specs/2026-09-04-ws002-blog-refresh-feature.md`

Inventory is in that feature spec (section 1). Design from current `main`, not archive.

```text
git merge feat/commerce-framework-v1
git merge feat/homepage-cms-preservation
git merge feat/barcode-stock-recovery
git checkout 84e905c -- .
```

Do not extract archive blog markup. Rewrite onto current tokens if a single archive file is still the best *reference*.

---

## 1. Layout

Mirror Shop:

```blade
@section('main_class', 'storefront-blog-main')
```

```blade
<x-storefront.layout.page-container class="storefront-blog">
```

Article page uses `variant="narrow"` on the reading column (header band, body, preview banner). Hero / related / end-CTA may stay full page-container width if the current article CSS already treats them as wider than the reading column — do not invent a 87.5rem track.

Do not change `layouts/storefront.blade.php` default `<main>` (`max-w-5xl` stays for PDP / cart / checkout / account). Do not render `site-header` from Cms views.

`.storefront-blog-shell` on `<main>` goes away. If a shell class remains, it is inner content only and must not set `max-width: 87.5rem`.

Delete unused `.storefront-page-container--wide` / `--reading` from `blog.css` so they cannot drift from `x-storefront.layout.page-container`.

---

## 2. Toolbar search

Keep the GET form to `storefront.cms.posts.index` and `name="search"`.

Replace:

```blade
<x-admin.search-input ... />
```

with storefront markup + classes in `blog.css` (same pattern as Shop filters: native input, no `x-admin.*`).

Keep `data-blog-search-form` / `data-blog-search-input` so `blog.js` debounce still works. Do not add a new search engine. Do not Ajax the listing.

Sort stays `x-storefront.forms.sort-dropdown`. Category pills stay GET links.

---

## 3. Pagination

Archive:

```blade
$posts->withQueryString()->links('pagination::storefront')
```

Reuse `resources/views/vendor/pagination/storefront.blade.php`. Style the blog pagination hook with the same `.storefront-pagination` language Shop already has, or share it — do not add a second paginator view.

Do not change Laravel paginator backend. Do not change admin `links()`.

---

## 4. Vite (UI-TECH-001)

Remove `@push('scripts') @vite('resources/js/storefront/blog.js')` from both post views.

Load `resources/js/storefront/blog.js` without a second `@vite` directive. Preferred: `import './storefront/blog.js'` from `resources/js/app.js` (the script already no-ops without `[data-blog]` / `[data-article]`).

Keep `blog.js` in `vite.config.js` only if it remains a separate entry; after the import it should not be a second page entry.

---

## 5. Tokens in `blog.css`

```text
gutters     var(--store-gutter) / var(--space-*)
archive max var(--store-max-width) via page-container, not 87.5rem
reading max var(--store-max-width-narrow), not a raw 56.25rem duplicate
radius     existing --radius-store-lg where the file already uses it
```

Do not restyle article-card / featured-article / TOC / share as a new visual system. Token adoption + width alignment only.

---

## 6. Isolation / contract tests

Add:

```text
tests/Unit/Storefront/Ws002BlogIsolationTest.php
tests/Feature/Storefront/Ws002BlogContractTest.php
```

Isolation must fail (red) until production changes land:

```text
posts/index and posts/show use x-storefront.layout.page-container
neither view contains x-admin.search-input
archive pagination is pagination::storefront
neither view contains @vite('resources/js/storefront/blog.js')
neither view contains site-header / Setting:: / @auth
blog.css does not set max-width: 87.5rem
```

Historical tests that require `storefront-blog-shell` on the page (`CmsBlogV1Test` and similar) update to the new main/page-container class names — do not delete the blog feature coverage.

Existing WS-002 isolation tests that forbid Blog from using page-container must be updated the same way Header isolation was after v1.12: Blog v1.13 owns that surface. Shop / Homepage / Header views still must not embed blog chrome.

---

## 7. Out of this branch

```text
HeaderViewData / site-header markup
Shop listing / product card
PDP
CMS editor
page.blade.php
newsletter / sidebar wiring
new DTO layer for blog cards (keep current $post + $blogService)
```

---

## 8. Sequence

```text
isolation tests red
↓
main_class + page-container on index + show
↓
pagination::storefront
↓
replace x-admin.search-input
↓
blog.css drop 87.5rem / unused page-container clones
↓
import blog.js from app.js; remove second @vite
↓
update CmsBlogV1Test selectors
↓
contract tests at 375 / 768 / 1024 (no overflowX; header still site-header)
↓
PR squash → tag v1.13.0
```
