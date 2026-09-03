# Release Line

Baseline of tagged releases on `main` as of 2026-09-03.

```text
v1.0.0-module-management
v1.1.0-feature-flags
v1.2.0-cms-scheduled-publishing
v1.3.0-blog-ui-refresh
v1.4.0
v1.5.0
v1.6.0
v1.7.0
v1.8.0
```

v1.2.0 / v1.3.0 feature branches were deleted after merge. v1.4.0 through v1.8.0 were squash-merged as PR #1 through PR #6. The tags on `main` are the source of history.

```text
main = stable (active development line)   22babfe
v1.4.0  Barcode Management     (9cf699f)
v1.5.0  Homepage CMS           (6b63a8d)
v1.6.0  Footer Management      (52a1a20)
v1.7.0  Navigation Management  (a5b336e)
v1.8.0  Website Settings       (22babfe)
```

Recovery is **closed**. Phase 1 = Barcode. Phase 2 = Homepage + Footer. Later work is a feature roadmap on `main`. Do not merge archive branches.

## Tags

| Tag | Commit | Concern | What a rollback undoes |
|---|---|---|---|
| `v1.0.0-module-management` | `0a2c3ca` | Infrastructure | Module registry / admin System → Modules |
| `v1.1.0-feature-flags` | `0f55100` | Infrastructure | Feature registry / `feature()` |
| `v1.2.0-cms-scheduled-publishing` | `79d5338` | Behavior | Scheduler, resolver, publish timestamps |
| `v1.3.0-blog-ui-refresh` | `8ce3e65` | UI | Blog archive, single, editor, adapters |
| `v1.4.0` | `9cf699f` | Catalog | Barcode Center, templates, print queue |
| `v1.5.0` | `6b63a8d` | Storefront / CMS | Homepage sections, hero/promo/FAQ, `/` |
| `v1.6.0` | `52a1a20` | Storefront / Settings | Footer composition admin, preview, storefront render |
| `v1.7.0` | `a5b336e` | Storefront / Navigation | Named menus, admin CRUD, FooterNavigationQuery |
| `v1.8.0` | `22babfe` | Storefront / Settings | Website Settings admin, brand keys, FooterSocialQuery |

v1.2.0 and v1.3.0 are separate so a scheduler defect rolls back v1.2.0 and a blog layout defect rolls back v1.3.0. v1.4.0 through v1.8.0 are separate so a barcode, homepage, footer, navigation, or website-settings defect rolls back only that surface.

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

## v1.4.0 — Barcode Management

- Admin **Catalog → Barcode Center** at `/admin/barcode`
- Template presets, print queue, PDF preview
- Path-extracted from archive `84e905c`; squash-merged as PR #1
- Product is a narrow read. Media via `MediaQueryServiceInterface::getUrl()`. No `SiteIdentityServiceInterface`
- Recovery tag: `recovery/barcode-v1.4`

Design: `docs/superpowers/specs/2026-09-02-barcode-template-layout-contract.md`

## v1.5.0 — Homepage CMS

- Admin **Content → Homepage / Hero Banners / Promotion Banners / FAQ**
- Storefront `GET /` and `GET /home/arrivals`
- Query adapters on current main (`HomepageNavigationQuery`, `HomepageProductQuery`, `HomepageBrandingQuery`)
- Isolation lock: no archive `Storefront*Catalog`, `ProductImageResolver`, or `SiteIdentityServiceInterface`
- Vault (source snapshot, not integrated): `feat/homepage-cms-preservation` / `recovery/homepage-cms`
- Squash-merged as PR #2

## v1.6.0 — Footer Management

- Admin **Website → Footer** at `/admin/settings/footer`; `footer.config` composition, no Footer migrations
- Shared storefront + admin preview renderer (`site-footer`) from `FooterPageData` / `FooterSectionData` / `FooterBrandData` / `FooterLinkData`
- Brand from `store.name` via `FooterBrandingQuery`; navigation and social fail-soft empty until Navigation Management / Website Settings
- Isolation lock: no `SiteIdentityServiceInterface`, `StorefrontNavigationConfig`, `WebsiteSettingsService`, Appearance / CX, WS-002, or `modules/Footer/**`
- Path-extracted from archive `84e905c`; squash-merged as PR #3
- Allowlists: `docs/superpowers/specs/2026-09-03-footer-management-m1-allowlist.md`, `m2-allowlist.md`, `m3-allowlist.md`

## v1.7.0 — Navigation Management

- Admin **Website → Navigation** at `/admin/navigation`; own module, IAM `navigation.menu.view` / `navigation.menu.update`
- Named menus `main` and `footer`; `account` reserved in the query API only
- `NavigationQueryServiceInterface::links($source)` returns `NavigationLinkData[]` (never Eloquent / raw arrays / config bags)
- `FooterNavigationQuery` forwards `$source` and maps DTOs to the existing Footer driver arrays
- Homepage arrival tabs stay on `HomepageNavigationQuery` (Catalog categories)
- Isolation lock: no `StorefrontNavigationConfig`, `SiteIdentityServiceInterface`, `WebsiteSettingsService`, Appearance / CX, WS-002, mega menu, or header redesign
- Squash-merged as PR #4

Feature lock: `docs/superpowers/specs/2026-09-03-navigation-management-feature.md`  
Implementation spec: `docs/superpowers/specs/2026-09-03-navigation-management-v1-implementation.md`

## v1.8.0 — Website Settings

- Admin **Settings → Website Settings** at `/admin/settings/website`; IAM `settings.setting.view` / `settings.setting.update`
- Writes `store.name`, `store.logo_media_uuid`, `store.description`, and `social.facebook` / `instagram` / `tiktok` / `line`
- `WebsiteSettingsQueryServiceInterface::socialLinks()` returns `WebsiteSocialLinkData[]`
- `FooterSocialQuery` maps those DTOs onto the existing Footer social driver
- `FooterBrandingQuery` and `HomepageBrandingQuery` keep reading `store.*` keys (no consumer rewrite)
- Isolation lock: no `SiteIdentityServiceInterface`, `WebsiteSettingsService`, Appearance / CX, WS-002, or `modules/Website/**`
- Squash-merged as PR #6 (`22babfe`); PR #5 is the earlier squash of the same branch

Feature lock: `docs/superpowers/specs/2026-09-03-website-settings-feature.md`  
Implementation spec: `docs/superpowers/specs/2026-09-03-website-settings-v1-implementation.md`

## Next candidate

**WS-002 Design System (v1.9.x)** — feature on `main`, not recovery.

Homepage, Footer, Navigation, and Website Settings now have stable data owners. v1.9 owns storefront tokens and shared `x-storefront.*` primitives. First tag is tokens + Homepage container only.

Feature lock: `docs/superpowers/specs/2026-09-03-ws002-design-system-feature.md`  
Implementation spec: `docs/superpowers/specs/2026-09-03-ws002-design-system-v1-implementation.md`

Do not start Scanner / POS / Inventory / Marketplace until WS-002 Phase 1 is tagged unless the owner picks a different next.

```text
v1.9.x  WS-002 Design System      ← next
later   Scanner, POS Barcode, Inventory Expansion, Marketplace Payouts
```

## Not included

These remain outside the tagged history on `main`:

- Appearance / Customer Experience admin (not WS-002)
- Warehouse Scanner, POS barcode expansion, Inventory expansion, Marketplace payouts
- `feat/commerce-framework-v1` design system / `stash@{0}` (archive — do not merge)
- UI-TECH-001 (`@vite/client` injected twice on blog pages)

WS-002 on `main` is the next tag: storefront tokens + `x-storefront.*` primitives, not the archive library.

Safety net (do not delete, do not merge):

```text
recovery/barcode-v1.4
recovery/homepage-cms
feat/homepage-cms-preservation
feat/barcode-stock-recovery      (archive, read-only)
feat/commerce-framework-v1       (archive, read-only)
```
