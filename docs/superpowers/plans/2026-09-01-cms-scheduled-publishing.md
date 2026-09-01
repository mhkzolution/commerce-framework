# CMS-104 Scheduled Publishing Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Give CMS Posts and Pages a shared scheduled publish / scheduled unpublish workflow so every consumer treats `status = published` as the only visibility rule.

**Architecture:** `PublishStateResolver` maps form intent to persisted status. `CmsPublishScheduler` plus `cms:publish-scheduled` (every minute) flips due rows. Posts and Pages stay separate tables with one status list in `config('cms.statuses')`.

**Tech Stack:** Laravel 13, PHP 8.4, PHPUnit, existing CMS module patterns.

## Global Constraints

- Branch from current `main` only: `feat/cms-scheduled-publishing`.
- Do not mix Footer / Product Workspace / Mazsashop WIP. Do not `git add .`.
- Do not change Editor / TipTap / `EditorPipeline`, Admin Navigation IA, or SEO/redirect platforms.
- Do not copy Mazsashop files or request-time `content_publish_scheduled()`.
- Visibility is `status === 'published'` only. `published_at` / `unpublish_at` are workflow fields.
- No queue, no `cms_publish_runs` table, no timezone picker, no page signed preview.
- One command for posts and pages. Process due rows with `chunkById`. Archiving must not null `published_at`.
- Spec: `docs/superpowers/specs/2026-09-01-cms-scheduled-publishing-design.md`.

## File map

- Create: `modules/Cms/src/DTO/PublishState.php`
- Create: `modules/Cms/src/Services/PublishStateResolver.php`
- Create: `modules/Cms/src/Services/CmsPublishScheduler.php`
- Create: `modules/Cms/src/Console/PublishScheduledContentCommand.php`
- Create: `modules/Cms/database/migrations/2026_09_01_200000_add_cms_scheduled_publishing_columns.php`
- Create: `tests/Unit/Cms/PublishStateResolverTest.php`
- Create: `tests/Unit/Cms/CmsPublishSchedulerTest.php`
- Create: `tests/Feature/Cms/CmsScheduledPublishingTest.php`
- Modify: `modules/Cms/config/cms.php`
- Modify: `modules/Cms/src/CmsServiceProvider.php`
- Modify: `modules/Cms/src/Models/Post.php`, `modules/Cms/src/Models/Page.php`
- Modify: `modules/Cms/src/Services/PostService.php`, `PageService.php`, `StorefrontBlogService.php`, `CmsSitemapProvider.php`
- Modify: Post/Page DTOs, FormRequests, Admin controllers
- Modify: `modules/Cms/resources/views/admin/posts/_form.blade.php`, `pages/_form.blade.php`
- Modify: `modules/Cms/resources/lang/en/admin.php`, `th/admin.php`
- Modify: `bootstrap/app.php` (committed copy on `main`, not local Footer WIP)
- Modify existing CMS tests that assumed `published + future published_at` hid content

---

### Task 1: Branch, statuses, columns, models

**Files:**
- Create: `modules/Cms/database/migrations/2026_09_01_200000_add_cms_scheduled_publishing_columns.php`
- Modify: `modules/Cms/config/cms.php`
- Modify: `modules/Cms/src/Models/Post.php`
- Modify: `modules/Cms/src/Models/Page.php`

**Interfaces:**
- Consumes: existing `cms_posts.published_at`, `config('cms.statuses')`
- Produces: `scheduled` status key; `unpublish_at` on posts; `published_at` + `unpublish_at` on pages; data migration of `published` + future `published_at` → `scheduled`

- [ ] **Step 1: Isolate WIP and open the branch from `main`**

Working tree has unrelated Footer/Product WIP. Stash **tracked** changes only (do not `stash -u`):

```bash
git stash push -m "wip: before feat/cms-scheduled-publishing"
git checkout main
git checkout -b feat/cms-scheduled-publishing
```

Confirm `git status` has no staged CMS-104 files and HEAD includes spec commits `7e591e8` / `f2c7b25`.

- [ ] **Step 2: Add `scheduled` to config**

In `modules/Cms/config/cms.php`:

```php
'statuses' => [
    'draft' => 'Draft',
    'scheduled' => 'Scheduled',
    'published' => 'Published',
    'archived' => 'Archived',
],
```

- [ ] **Step 3: Write the migration**

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cms_posts') && ! Schema::hasColumn('cms_posts', 'unpublish_at')) {
            Schema::table('cms_posts', function (Blueprint $table): void {
                $table->timestamp('unpublish_at')->nullable()->after('published_at');
                $table->index(['status', 'published_at']);
                $table->index(['status', 'unpublish_at']);
            });
        }

        if (Schema::hasTable('cms_pages')) {
            Schema::table('cms_pages', function (Blueprint $table): void {
                if (! Schema::hasColumn('cms_pages', 'published_at')) {
                    $table->timestamp('published_at')->nullable()->after('status');
                }
                if (! Schema::hasColumn('cms_pages', 'unpublish_at')) {
                    $table->timestamp('unpublish_at')->nullable()->after('published_at');
                }
                $table->index(['status', 'published_at']);
                $table->index(['status', 'unpublish_at']);
            });
        }

        $now = now();

        if (Schema::hasTable('cms_posts')) {
            DB::table('cms_posts')
                ->where('status', 'published')
                ->whereNotNull('published_at')
                ->where('published_at', '>', $now)
                ->update(['status' => 'scheduled']);
        }

        if (Schema::hasTable('cms_pages') && Schema::hasColumn('cms_pages', 'published_at')) {
            DB::table('cms_pages')
                ->where('status', 'published')
                ->whereNotNull('published_at')
                ->where('published_at', '>', $now)
                ->update(['status' => 'scheduled']);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('cms_posts') && Schema::hasColumn('cms_posts', 'unpublish_at')) {
            Schema::table('cms_posts', function (Blueprint $table): void {
                $table->dropIndex(['status', 'published_at']);
                $table->dropIndex(['status', 'unpublish_at']);
                $table->dropColumn('unpublish_at');
            });
        }

        if (Schema::hasTable('cms_pages')) {
            Schema::table('cms_pages', function (Blueprint $table): void {
                $table->dropIndex(['status', 'published_at']);
                $table->dropIndex(['status', 'unpublish_at']);
                if (Schema::hasColumn('cms_pages', 'unpublish_at')) {
                    $table->dropColumn('unpublish_at');
                }
                if (Schema::hasColumn('cms_pages', 'published_at')) {
                    $table->dropColumn('published_at');
                }
            });
        }
    }
};
```

If an index name already exists on `cms_posts` (`published_at` was indexed in the taxonomy migration), drop/recreate only what is missing. Prefer `Schema::hasIndex` / try-add rather than failing migrate.

- [ ] **Step 4: Update models**

`Post` fillable + casts: add `unpublish_at` datetime. Change `isPublished()` to:

```php
public function isPublished(): bool
{
    return $this->status === 'published';
}
```

`Page` fillable + casts: add `published_at`, `unpublish_at`. Add the same `isPublished()`.

- [ ] **Step 5: Migrate and commit**

```bash
php artisan migrate
git add modules/Cms/config/cms.php modules/Cms/database/migrations/2026_09_01_200000_add_cms_scheduled_publishing_columns.php modules/Cms/src/Models/Post.php modules/Cms/src/Models/Page.php
git commit -m "feat: add CMS scheduled status and unpublish timestamps"
```

---

### Task 2: PublishStateResolver

**Files:**
- Create: `modules/Cms/src/DTO/PublishState.php`
- Create: `modules/Cms/src/Services/PublishStateResolver.php`
- Create: `tests/Unit/Cms/PublishStateResolverTest.php`

**Interfaces:**
- Consumes: `status: string`, `publishedAt: CarbonInterface|string|null`, `unpublishAt: CarbonInterface|string|null`
- Produces: `PublishStateResolver::resolve(...): PublishState` with `status`, `publishedAt`, `unpublishAt`
- Throws: `Illuminate\Validation\ValidationException` for invalid scheduled intents

- [ ] **Step 1: Write failing unit tests**

Use `Tests\TestCase` (Laravel) so `ValidationException` works. Freeze time with `Carbon\CarbonImmutable::setTestNow('2026-09-01 12:00:00')`.

Cover at least:

1. Published + null date → published + `published_at = now`
2. Published + future date → scheduled, **same** datetime (not now)
3. Published + past unpublish → archived, keep `published_at`
4. Published + future publish + past unpublish → archived (precedence)
5. Scheduled + future publish + expired unpublish (`2026-09-01` while publish is `2026-10-01`) → archived, keep `published_at`
6. Scheduled + null date → ValidationException on `published_at`
7. Scheduled + past publish → ValidationException
8. Scheduled + future unpublish ≤ publish → ValidationException on `unpublish_at`
9. Draft keeps submitted timestamps
10. Archived keeps `published_at` (does not null it)

- [ ] **Step 2: Run tests and confirm they fail**

```bash
php artisan test tests/Unit/Cms/PublishStateResolverTest.php
```

Expected: FAIL (class not found).

- [ ] **Step 3: Implement resolver**

`PublishState`:

```php
final readonly class PublishState
{
    public function __construct(
        public string $status,
        public ?\Carbon\CarbonInterface $publishedAt,
        public ?\Carbon\CarbonInterface $unpublishAt,
    ) {}
}
```

`PublishStateResolver::resolve` evaluation order:

1. Parse timestamps (empty string → null).
2. If intent `archived` → `{ archived, keep both timestamps }`.
3. If intent `draft` → `{ draft, keep both timestamps }`.
4. If `unpublishAt !== null && unpublishAt->lte(now())` and intent is `published` or `scheduled` → `{ archived, keep publishedAt, keep unpublishAt }`.
5. If intent `scheduled`: require future `publishedAt`; if `unpublishAt` is not null and `unpublishAt->lte($publishedAt)` → error (this is only reachable when unpublish is still in the future).
6. If intent `published`:
   - future `publishedAt` → `scheduled` (keep that datetime)
   - null `publishedAt` → `published_at = now()`
   - past/now `publishedAt` → keep it, status published
7. If `unpublishAt` is not null and not already archived, require `unpublishAt > publishedAt` when `publishedAt` is set.

Messages:

- `Published date is required for scheduled content.`
- `Scheduled publish date must be in the future.`
- Unpublish window: `Unpublish date must be after the publish date.`

- [ ] **Step 4: Run tests and confirm they pass**

```bash
php artisan test tests/Unit/Cms/PublishStateResolverTest.php
```

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add modules/Cms/src/DTO/PublishState.php modules/Cms/src/Services/PublishStateResolver.php tests/Unit/Cms/PublishStateResolverTest.php
git commit -m "feat: resolve CMS publish intent into persisted status"
```

---

### Task 3: Wire create/update

**Files:**
- Modify: `modules/Cms/src/CmsServiceProvider.php` (singleton `PublishStateResolver`)
- Modify: `modules/Cms/src/Services/PostService.php` — replace `applyPublishState` with resolver; persist `unpublish_at`
- Modify: `modules/Cms/src/Services/PageService.php` — persist resolved status + timestamps
- Modify: `CreatePostData`, `UpdatePostData` — add `?string $unpublishAt = null`
- Modify: `CreatePageData`, `UpdatePageData` — add `?string $publishedAt = null`, `?string $unpublishAt = null`
- Modify: `StorePostRequest`, `UpdatePostRequest`, `StorePageRequest`, `UpdatePageRequest` — `unpublish_at` nullable date; page requests also `published_at`
- Modify: `Admin/PostController.php`, `Admin/PageController.php` — pass the new fields

**Interfaces:**
- Consumes: `PublishStateResolver::resolve`
- Produces: DB rows whose status matches the matrix; 422 when resolver throws

- [ ] **Step 1: Inject resolver into PostService and PageService**

```php
$state = $this->publishState->resolve($data->status, $data->publishedAt, $data->unpublishAt);
// payload status / published_at / unpublish_at from $state
```

Remove `PostService::applyPublishState`.

- [ ] **Step 2: Wire requests and controllers**

Pass `unpublishAt: $request->validated('unpublish_at')` (and page `publishedAt`).

- [ ] **Step 3: Commit**

```bash
git commit -m "feat: persist CMS publish state through the resolver"
```

---

### Task 4: Scheduler, command, Laravel schedule

**Files:**
- Create: `modules/Cms/src/Services/CmsPublishScheduler.php`
- Create: `modules/Cms/src/Console/PublishScheduledContentCommand.php`
- Create: `tests/Unit/Cms/CmsPublishSchedulerTest.php`
- Modify: `modules/Cms/src/CmsServiceProvider.php` — `$this->commands([...])` when `runningInConsole()`
- Modify: `bootstrap/app.php` — `$schedule->command('cms:publish-scheduled')->everyMinute();`

**Interfaces:**
- Consumes: Post/Page rows
- Produces: `CmsPublishScheduler::run(): array{published: int, archived: int}`
- Command signature: `cms:publish-scheduled`

- [ ] **Step 1: Write failing scheduler tests** (`RefreshDatabase`)

1. Scheduled post with `published_at` in the past → published; `published_at` unchanged
2. Published post with `unpublish_at` in the past → archived; `published_at` still the original value
3. Same-run: scheduled with both timestamps due → archived after `run()`
4. Second `run()` does not change counts / statuses
5. Same four cases for pages
6. Future scheduled row is left scheduled

- [ ] **Step 2: Implement scheduler with chunking**

```php
public function run(): array
{
    $published = $this->publishDue();
    $archived = $this->archiveExpired();

    return ['published' => $published, 'archived' => $archived];
}
```

`publishDue`: for `Post` and `Page`, `where status=scheduled`, `published_at <= now()`, `chunkById(100)`, `update(['status' => 'published'])` only.

`archiveExpired`: `status=published`, `unpublish_at` not null and `<= now()`, `chunkById(100)`, `update(['status' => 'archived'])` only — never touch `published_at`.

- [ ] **Step 3: Command**

```php
protected $signature = 'cms:publish-scheduled';

public function handle(CmsPublishScheduler $scheduler): int
{
    $result = $scheduler->run();
    Log::info('cms.publish-scheduled', $result);
    $this->info("Published {$result['published']}, archived {$result['archived']}.");

    return self::SUCCESS;
}
```

- [ ] **Step 4: Register command and schedule**

Follow `ProductServiceProvider` console registration. In `bootstrap/app.php` add the command next to `commerce:outbox:publish`.

- [ ] **Step 5: Run tests**

```bash
php artisan test tests/Unit/Cms/CmsPublishSchedulerTest.php
php artisan cms:publish-scheduled
```

Expected: PASS; command prints counts.

- [ ] **Step 6: Commit**

```bash
git commit -m "feat: auto-publish and archive CMS content on a minute schedule"
```

---

### Task 5: Consumer queries

**Files:**
- Modify: `modules/Cms/src/Services/PostService.php` `findPublishedBySlug`
- Modify: `modules/Cms/src/Services/StorefrontBlogService.php` `publishedQuery()` and tag `withCount`
- Modify: `modules/Cms/src/Services/CmsSitemapProvider.php` post query

**Interfaces:**
- Produces: all live queries `where('status', 'published')` with no `published_at <= now()`

- [ ] **Step 1: Drop time filters**

`publishedQuery()`:

```php
return Post::query()->where('status', 'published');
```

Same for `findPublishedBySlug`, sitemap posts, and tag published counts. Do **not** edit `modules/Settings/src/Footer/Drivers/CmsSectionDriver.php`.

- [ ] **Step 2: Commit**

```bash
git commit -m "fix: treat CMS visibility as published status only"
```

---

### Task 6: Admin forms

**Files:**
- Modify: `modules/Cms/resources/views/admin/posts/_form.blade.php`
- Modify: `modules/Cms/resources/views/admin/pages/_form.blade.php`
- Modify: `modules/Cms/resources/lang/en/admin.php`
- Modify: `modules/Cms/resources/lang/th/admin.php`

Do not touch `cms::components.editor` or SEO includes.

- [ ] **Step 1: Fields**

Posts: after `published_at`, add `unpublish_at` `datetime-local`.

Pages: add `published_at` and `unpublish_at` in the Publish section.

Static helper under publish date (no calendar JS):

```blade
<p class="mt-1 text-xs text-text-secondary">{{ __('cms::admin.schedule_helper') }}</p>
```

EN: `If status is Published and the publish date is in the future, this will be saved as Scheduled.`

TH: equivalent one sentence.

Also add `unpublish_at` labels.

- [ ] **Step 2: Commit**

```bash
git commit -m "feat: add CMS unpublish fields and schedule helper"
```

---

### Task 7: Feature tests and regression lock

**Files:**
- Create: `tests/Feature/Cms/CmsScheduledPublishingTest.php`
- Modify: `tests/Feature/Cms/CmsBlogV1Test.php`, `CmsAdminTest.php` only if they still assume time-based hiding; do not change editor sanitizer tests in `CmsEditorQaTest`

**Interfaces:**
- HTTP admin save, storefront, sitemap, signed preview, artisan command

- [ ] **Step 1: Feature tests**

Acting as IAM seeder admin:

1. POST post `status=published`, no dates → DB `published` with `published_at` set
2. POST post `status=published` + future `published_at` → DB `scheduled`, same datetime
3. POST post `status=scheduled` without date → 422
4. Same 1–3 for pages
5. Factory/create scheduled post/page → storefront 404; sitemap excludes slug
6. `artisan cms:publish-scheduled` then storefront 200
7. Scheduled post signed preview still 200 + noindex (copy the draft preview pattern from `CmsBlogV1Test`)
8. Direct-created `status=published` with future `published_at` is **visible** on storefront (proves consumers no longer time-check; migration is what prevents this in production data)

- [ ] **Step 2: Run full CMS suite**

```bash
php artisan test --filter=Cms
```

Expected: all pass, including previous 28 plus new tests.

- [ ] **Step 3: Commit**

```bash
git commit -m "test: lock CMS scheduled publishing workflow"
```

---

## Self-review

| Spec requirement | Task |
| --- | --- |
| Shared statuses + columns + migration | 1 |
| Intent save matrix, keep future date, archived wins, scheduled+expired unpublish | 2–3 |
| Scheduler order, chunks, keep `published_at` on archive | 4 |
| `cms:publish-scheduled` everyMinute | 4 |
| Consumer status-only queries | 5 |
| Admin fields + helper, no editor | 6 |
| Tests (unit/feature/command/preview) | 2, 4, 7 |
| Out of scope (editor, nav, SEO platform, queue, page preview) | Global constraints |

No TBD placeholders. Names match spec: `PublishStateResolver`, `CmsPublishScheduler`, `cms:publish-scheduled`.
