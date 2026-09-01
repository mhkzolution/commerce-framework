# CMS-104 Scheduled Publishing Design

**Branch:** `feat/cms-scheduled-publishing` (from `main` only)  
**Date:** 2026-09-01  
**Status:** Approved for implementation plan

## Goal

Give Posts and Pages the same scheduled publish and scheduled unpublish workflow, so content visibility is a single status on every consumer.

This is a workflow epic. It does not change the editor, admin navigation, or SEO/redirect platforms.

## Source of truth

Content visibility is determined solely by `status`.

```text
published   = visible
draft       = not visible
scheduled   = not visible
archived    = not visible
```

`published_at` and `unpublish_at` are workflow fields only. Consumers must not use them as visibility filters after this epic.

Scheduled means: the content is approved, but it is not live yet. It is not `draft + future published_at`.

## Current state

- Shared status list: `config('cms.statuses')` = draft, published, archived.
- Posts have `published_at`. Storefront, sitemap post URLs, related posts, and `Post::isPublished()` also require `published_at <= now()`.
- Pages have status only. `PageService::findPublishedBySlug` and the page sitemap already filter `status = published`.
- Admin post form has status + `published_at`. Admin page form has status only.
- Posts have signed preview. Pages do not.
- Laravel schedule already runs `commerce:outbox:publish` every minute in `bootstrap/app.php`.
- Product has a separate `product:publish-scheduled` command. CMS must not reuse Product code; it may follow the same command + every-minute pattern.

Today a post can be `status = published` with a future `published_at`. Storefront hides it via the time check. That hidden state is what this epic removes.

## Out of scope

- Editor / TipTap / media picker / `EditorPipeline`
- Admin Navigation IA
- SEO platform, redirect platform, sitemap generator internals
- CMS-201 editor UX (alt warning, word count, slash commands, FAQ/accordion, AI, table UX, link `rel` cleanup)
- Queue / delayed jobs at `published_at`
- `cms_publish_runs` table or other scheduler observability beyond `Log::info`
- Timezone picker, calendar UI
- Page signed preview
- Bulk scheduling, approval, versioning, notifications
- CMS-300 content unification (do not merge `cms_posts` and `cms_pages`)

Allowed consumer edits: CMS listing/show/sitemap/related/`isPublished()` queries that currently mix status with time. Footer `CmsSectionDriver` already uses `status = published` and must not be changed.

## Architecture

```text
Platform
  PublishStateResolver     form intent → persisted status/timestamps
  CmsPublishScheduler       due scheduled → published; expired published → archived
  cms:publish-scheduled    artisan command, both entity types
  bootstrap/app.php       everyMinute() next to outbox

Content types
  cms_posts                existing table
  cms_pages                existing table
```

One status vocabulary in `config('cms.statuses')`. No `PostStatus` / `PageStatus` classes.

```text
draft | scheduled | published | archived
```

### Columns

```text
cms_posts.published_at     existing
cms_posts.unpublish_at     new, nullable timestamp
cms_pages.published_at     new, nullable timestamp
cms_pages.unpublish_at     new, nullable timestamp
```

Indexes: add the due-query indexes `(status, published_at)` and `(status, unpublish_at)` where missing.

### Data migration

On both tables:

```text
status = published
AND published_at IS NOT NULL
AND published_at > now()
  → status = scheduled
```

Do not convert `published` rows with `published_at` null. Those stay published (already live).

Do not backfill page `published_at` as a schedule. Existing published pages remain published with a null `published_at` until the next intent save.

## Layer 1 — Intent-based save

`PublishStateResolver` maps form intent to persisted state. It does not query the storefront and does not run the scheduler.

Timezone is application `now()`. No per-author timezone.

### Precedence

Archived decisions win over published/scheduled decisions. Evaluate expired `unpublish_at` (`<= now()`) before publish/schedule coercion.

Example: status Published, `published_at` in 2030, `unpublish_at` yesterday → persist **archived**. Do not persist scheduled.

Example: status Scheduled, `published_at=2026-10-01`, `unpublish_at=2026-09-01` (already due) → persist **archived**. Do not validation-error and do not persist scheduled.

Archiving does not modify `published_at`. Never null it on archive.

### Coercion must keep the submitted publish time

Published + future `published_at` becomes scheduled **without** replacing `published_at` with `now()`.

```text
Input:  status=published, published_at=2026-10-01 09:00
Persist: status=scheduled, published_at=2026-10-01 09:00
```

### Matrix

| Status    | Published at | Unpublish at                         | Persist                                      |
| --------- | ------------ | ------------------------------------ | -------------------------------------------- |
| Draft     | any          | any                                  | `draft` (timestamps stored as submitted)     |
| Published | null         | null or future                        | `published`, `published_at = now()`         |
| Published | past         | null or future                        | `published`, keep submitted `published_at` |
| Published | future       | null or after that `published_at`     | `scheduled`, keep submitted `published_at` |
| Published | any          | past or now                          | `archived`                                   |
| Scheduled | future       | past or now                          | `archived` (precedence; keep `published_at`) |
| Scheduled | future       | null or after `published_at`          | `scheduled`                                  |
| Scheduled | null         | any                                  | validation error                             |
| Scheduled | past         | any                                  | validation error                             |
| Scheduled | future       | future and ≤ `published_at`         | validation error                             |
| Archived  | any          | any                                  | `archived`                                   |

Scheduled errors:

- missing `published_at` → “Published date is required for scheduled content.”
- past `published_at` → “Scheduled publish date must be in the future.”
- `unpublish_at` is still in the future and ≤ `published_at` → validation error

`PostService` and `PageService` call the resolver on create/update. They do not duplicate the matrix.

## Layer 2 — Scheduler

`CmsPublishScheduler`:

```text
1. publishDue()
     status = scheduled AND published_at <= now()
     → status = published

2. archiveExpired()
     status = published
     AND unpublish_at IS NOT NULL
     AND unpublish_at <= now()
     → status = archived
```

Order is required. A row whose `published_at` and `unpublish_at` are both due in the same run must end **archived**.

The command is idempotent: a second run does not change rows that are no longer in the due sets. `archiveExpired()` updates `status` only. It does not modify `published_at`.

Process due rows in chunks (`chunkById`). Do not `get()` the full due set.

No queue. One-minute drift is acceptable. Log counts with `Log::info`, not a runs table.

## Layer 3 — Command and schedule

```text
php artisan cms:publish-scheduled
```

One command for posts and pages. Do not split into post/page commands.

Register in `bootstrap/app.php`:

```php
$schedule->command('cms:publish-scheduled')->everyMinute();
```

alongside `commerce:outbox:publish`.

## Consumers

After migration, every live-content query uses `status = published` only.

| Consumer | Change |
| --- | --- |
| `PostService::findPublishedBySlug` | drop `published_at` time checks |
| `StorefrontBlogService::publishedQuery` and related/tag counts | drop time checks |
| `CmsSitemapProvider` post URLs | drop time checks |
| `Post::isPublished()` | `status === 'published'` only |
| `Page` | add the same `isPublished()` contract; `findPublishedBySlug` already status-only |
| Footer `CmsSectionDriver` | no change |

JSON-LD and canonical on the public post show action follow whatever the storefront loaded. Unpublished statuses never reach that action. Signed preview keeps `noindex` and omits BlogPosting; it uses `isPublished()` only to decide canonical (unchanged preview route).

## Admin UI

Do not change the TipTap editor, SEO include, or navigation.

Posts sidebar: keep status and `published_at`; add `unpublish_at` (`datetime-local`); show a helper when status is Published and `published_at` is in the future, e.g. “This content will be scheduled automatically because the publish date is in the future.”

Pages sidebar: add `published_at` and `unpublish_at` with the same helper.

No calendar widget.

## Preview

Post signed preview routes, middleware, and permissions stay as they are. Scheduled and draft posts remain previewable.

Do not add page preview in this epic.

## Testing

Unit:

- `PublishStateResolver` covers the matrix, including kept future `published_at`, archived precedence, and scheduled + expired `unpublish_at` → archived.
- `CmsPublishScheduler` covers posts and pages, idempotency, and same-run publish-then-archive → archived.

Feature:

- Admin save for posts and pages: publish now; publish later → scheduled; scheduled without date → 422.
- Storefront: scheduled slug 404; published slug 200 without relying on time filters.
- After the command, a due scheduled post/page becomes visible.
- Signed post preview for scheduled still works with noindex.
- `php artisan cms:publish-scheduled` succeeds.

Update existing `CmsBlogV1Test`, `CmsAdminTest`, and `CmsEditorQaTest` so they no longer treat `published + future published_at` as a hide mechanism. Do not change editor sanitizer assertions.

## Definition of Done

```text
✓ scheduled in config('cms.statuses')
✓ Posts.unpublish_at and Pages.published_at + unpublish_at
✓ Data migration published + future published_at → scheduled
✓ PublishStateResolver intent save
✓ CmsPublishScheduler + cms:publish-scheduled everyMinute()
✓ Storefront, sitemap posts, related, isPublished() = status only
✓ Admin date fields + schedule helper
✓ Tests above
✓ No editor, navigation, or SEO/redirect platform changes
```

## Implementation constraints

- Branch from current `main` only. Do not mix Footer / Product Workspace / Mazsashop WIP.
- Do not `git add .`.
- Do not copy Mazsashop files or `content_publish_scheduled()` request-time publishing.
