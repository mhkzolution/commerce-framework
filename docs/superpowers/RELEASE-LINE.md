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
```

v1.2.0 / v1.3.0 feature branches were deleted after merge. v1.4.0, v1.5.0, and v1.6.0 were squash-merged as PR #1, PR #2, and PR #3. The tags on `main` are the source of history.

```text
main = stable (active development line)   8d36013
v1.4.0  Barcode Management     (9cf699f)
v1.5.0  Homepage CMS           (6b63a8d)
v1.6.0  Footer Management      (52a1a20)
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

v1.2.0 and v1.3.0 are separate so a scheduler defect rolls back v1.2.0 and a blog layout defect rolls back v1.3.0. v1.4.0 / v1.5.0 / v1.6.0 are separate so a barcode, homepage, or footer defect rolls back only that surface.

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

## Next candidate

**Navigation Management (v1.7.x)** — feature on `main`, not recovery.

Footer already exposes `FooterNavigationQuery::links($source)` as `[]`. Homepage arrival tabs stay on `HomepageNavigationQuery` (Catalog categories). v1.7 owns named menus and fills the Footer query in place.

Feature lock: `docs/superpowers/specs/2026-09-03-navigation-management-feature.md`  
Implementation spec: `docs/superpowers/specs/2026-09-03-navigation-management-v1-implementation.md`

Do not start Website Settings or WS-002 until Navigation is tagged.

```text
v1.7.x  Navigation Management     ← next
v1.8.x  Website Settings          (brand, logo, contact, social, SEO defaults)
v1.9.x  WS-002 Design System      (header/footer/nav/cards once data owners are stable)
later   Scanner, POS Barcode, Inventory Expansion, Marketplace Payouts
```

## Not included

These remain outside the tagged history on `main`:

- Website Settings / Appearance / Site Identity / Customer Experience
- Navigation Management (v1.7.x — next feature on `main`)
- Warehouse Scanner, POS barcode expansion, Inventory expansion, Marketplace payouts
- Storefront primitives / `feat/commerce-framework-v1` design system / `stash@{0}`
- UI-TECH-001 (`@vite/client` injected twice on blog pages)

Safety net (do not delete, do not merge):

```text
recovery/barcode-v1.4
recovery/homepage-cms
feat/homepage-cms-preservation
feat/barcode-stock-recovery      (archive, read-only)
feat/commerce-framework-v1       (archive, read-only)
```
