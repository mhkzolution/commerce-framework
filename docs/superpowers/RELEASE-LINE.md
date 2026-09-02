# CMS Release Line

Baseline of tagged CMS releases on `main` as of 2026-09-02 (`8ce3e65`).

```text
v1.0.0-module-management
v1.1.0-feature-flags
v1.2.0-cms-scheduled-publishing
v1.3.0-blog-ui-refresh
```

v1.2.0 / v1.3.0 feature branches were deleted after merge. The tags on `main` are the source of history.

## Tags

| Tag | Commit | Concern | What a rollback undoes |
|---|---|---|---|
| `v1.0.0-module-management` | `0a2c3ca` | Infrastructure | Module registry / admin System → Modules |
| `v1.1.0-feature-flags` | `0f55100` | Infrastructure | Feature registry / `feature()` |
| `v1.2.0-cms-scheduled-publishing` | `79d5338` | Behavior | Scheduler, resolver, publish timestamps |
| `v1.3.0-blog-ui-refresh` | `8ce3e65` | UI | Blog archive, single, editor, adapters |

v1.2.0 and v1.3.0 are separate so a scheduler defect rolls back v1.2.0 and a blog layout defect rolls back v1.3.0.

## v1.0.0 — Module Management

- `system_modules` registry and `ModuleService`
- `module()` / `module_enabled()` helpers and `module:{code}` middleware
- Admin **System → Modules**
- IAM audit for module status changes

## v1.1.0 — Feature Flags

- `system_features` registry and `FeatureService`
- `feature()` / `feature_enabled()` and `feature:{code}` middleware
- Admin **System → Features**
- Flag defaults ENABLED; parent module DISABLED turns child flags off
- This tag does not wire product behavior; scheduled publishing lands in v1.2.0

Design: `docs/superpowers/specs/2026-09-02-feature-flags-framework-design.md`

## v1.2.0 — CMS Scheduled Publishing

- `scheduled` status, `published_at` / `unpublish_at`
- `PublishStateResolver` and `CmsPublishScheduler` (minute command)
- `ConstrainsScheduledPublishing` on post/page requests
- Storefront visibility = published only
- Editor, API, scheduler, and resolver gated with `feature('scheduled-publishing')`
- Existing scheduled rows and timestamps are preserved when the flag is off

Design: `docs/superpowers/specs/2026-09-01-cms-scheduled-publishing-design.md`

## v1.3.0 — Blog UI Refresh

- Storefront archive and single-post redesign (`blog.css` / `blog.js`)
- Admin post/page editor workspace, slash menu, inspector, media JSON upload
- Related posts and latest/popular sort
- Local thin adapters: breadcrumb, empty-state, layout.grid, blog.share, forms.sort-dropdown
- No `commerce-framework-v1` backport, no homepage, no new migrations
- Admin post/page forms keep `@if (feature('scheduled-publishing'))`

Design: `docs/superpowers/specs/2026-09-02-blog-ui-refresh-thin-adapters-design.md`

## Not included

These are outside v1.0.0–v1.3.0 tagged history:

- Homepage CMS (untracked work: hero/FAQ/banners, widgets, `public/wp-content`, `HomeContentAdminTest.php`)
- Storefront primitives / `feat/commerce-framework-v1` design system / `stash@{0}`
- UI-TECH-001 (`@vite/client` injected twice on blog pages)
- Any other files or branches not reachable from the tags above

A directory-wide `phpunit tests/Feature/Cms` on a dirty tree can load untracked homepage tests. Committed CMS / Features / Admin tests at this baseline: 69 passed, 0 failed.
