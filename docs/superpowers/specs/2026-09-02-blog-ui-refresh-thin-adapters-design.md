# Blog UI Refresh — Local Thin Adapters (v1.3.0)

**Branch:** `feat/blog-ui-refresh` (from `feat/cms-publishing-integration-v2` @ `79d5338`)  
**Date:** 2026-09-02  
**Status:** Approved

## Goal

Unblock the cherry-picked blog redesign (`9f1bff1` / `4c4cd96`) so storefront archive and single post can render, without importing the `feat/commerce-framework-v1` design system.

```text
v1.2.0  CMS Scheduled Publishing   hold (do not merge)
v1.3.0  Blog UX/UI Refresh         this branch
```

## Problem

These views call components that do not exist on this branch:

| Caller | Missing component |
|---|---|
| `modules/Cms/resources/views/storefront/posts/show.blade.php` | `x-storefront.breadcrumb` |
| `modules/Cms/resources/views/storefront/posts/show.blade.php` | `x-storefront.blog.share` |
| `modules/Cms/resources/views/storefront/posts/index.blade.php` | `x-storefront.empty-state` |
| `resources/views/components/storefront/blog/article-grid.blade.php` | `x-storefront.layout.grid` |
| `resources/views/components/storefront/blog/toolbar.blade.php` | `x-storefront.forms.sort-dropdown` |

The first four were identified in the architecture review. `sort-dropdown` appeared when archive tests ran: the cherry-picked toolbar already called it. Same adapter rules apply — do not import the v1 forms library.

Do **not** copy the v1 aliases as-is. On `feat/commerce-framework-v1`, `breadcrumb` and `empty-state` are wrappers around `navigation.breadcrumb` and `layout.empty-state`, which pull the rest of the storefront navigation system.

## Approach (A)

Add **local thin adapters** on this branch only. Markup must match classes already in `resources/css/storefront/blog.css` from the redesign:

```text
.storefront-breadcrumb
.storefront-breadcrumb__list
.storefront-breadcrumb__item
.storefront-breadcrumb__link
.storefront-breadcrumb__current

.storefront-article-grid

.storefront-article-share
.storefront-article-share__title
.storefront-article-share__actions
.storefront-article-share__btn

.storefront-blog__empty   (passed by the archive view)
```

### Files

```text
resources/views/components/storefront/breadcrumb.blade.php
resources/views/components/storefront/empty-state.blade.php
resources/views/components/storefront/layout/grid.blade.php
resources/views/components/storefront/blog/share.blade.php
resources/views/components/storefront/forms/sort-dropdown.blade.php
```

These are compatibility adapters, not shared primitives. When the real design system is merged later, replace them (or retarget the aliases). Call sites stay:

```blade
<x-storefront.breadcrumb />
<x-storefront.empty-state />
<x-storefront.layout.grid />
<x-storefront.blog.share />
```

## Rule 1 — No new translation namespace

Do not use `__('storefront::...')`.

Use existing `cms::blog.*` keys, or add a key **only** under `modules/Cms/resources/lang/{en,th}/blog.php`.

Allowed for this work: `cms::blog.breadcrumb` for the breadcrumb `aria-label`. Share copy already exists (`share_heading`, `share_facebook`, `share_x`, `share_linkedin`, `share_copy`, `share_copied`). Empty state title is passed by the caller (`cms::blog.no_posts`). Sort dropdown uses existing `cms::blog.sort`.

## Rule 2 — Adapter comment on every file

Each adapter Blade file must open with:

```blade
{{--
Temporary adapter for Blog UI Refresh (v1.3.0)

This component intentionally does not depend on
commerce-framework-v1 storefront primitives.

Replace with shared storefront primitives
when the design system is merged.
--}}
```

## Out of scope

- Merge `feat/cms-publishing-integration-v2` into `main`
- `stash@{0}`, homepage, hero/FAQ/banners
- `feat/commerce-framework-v1` backport (navigation, drawer, cart, layout shell)
- Rewriting archive/single markup inline (approach B)
- Changing `@if (feature('scheduled-publishing'))` on admin post/page forms

## Success

`tests/Feature/Cms/CmsBlogV1Test.php` returns HTTP 200 for archive, single, and preview. Scheduled-publishing feature gates remain intact.
