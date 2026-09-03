# WS-002 Design System v1 — Implementation Spec (locked)

**Date:** 2026-09-03  
**Status:** Locked — do not branch until this file is the contract  
**Milestone:** v1.9.x on `main`  
**Mode:** Feature on the release line (not recovery)

```text
WS-002 owns storefront tokens + shared Blade primitives
Homepage / Footer / Navigation / Settings own data and page sections
CMS owns content
Catalog owns category trees
```

Base: current `main`. Do not branch from `84e905c`. Do not merge archive branches.

```text
git merge feat/homepage-cms-preservation
git merge feat/barcode-stock-recovery
git merge feat/commerce-framework-v1
git checkout 84e905c -- .
```

Path-extract from archive only if a single file is still the best source, then rewrite it onto current main tokens in the same commit.

Feature overview: `docs/superpowers/specs/2026-09-03-ws002-design-system-feature.md`

---

## 1. Owner

**Locked:**

```text
resources/css/storefront/
resources/views/components/storefront/
```

Canonical Blade prefix: `x-storefront.*`

Do not add `packages/storefront-design-system` or `modules/Storefront`. Do not let Cart, CMS, Settings, or Navigation own shared primitives.

Vite / `app.css` continue to import storefront CSS from `resources/css/storefront/`. New token file:

```text
resources/css/storefront/tokens.css
```

Imported from `resources/css/app.css` **before** `shell.css` / `home.css` / `footer.css` / `blog.css`.

---

## 2. Design tokens (locked)

Define on `.storefront` (and `:root` aliases only if a sheet already expects `:root`). Do not put storefront layout tokens in `resources/css/tokens/semantic-light.css` (that file is the admin source of truth).

Storefront **reuses** existing semantic colors:

```text
--color-background
--color-surface
--color-border
--color-text
--color-text-secondary
--color-muted
--color-primary
--color-link
```

Storefront **owns**:

### Container

```text
--store-max-width: 80rem;     /* 1280px at 16px */
--store-max-width-narrow: 56.25rem;
--store-gutter: 1.5rem;       /* 24px */
```

Homepage today hardcodes `77.5rem`. Footer already uses `var(--store-max-width)` with **no definition**. v1.9 must define these before any visual refactor.

### Radius

```text
--radius-sm: 0.375rem;
--radius-md: 0.5rem;
--radius-lg: 0.75rem;
--radius-xl: 1rem;
--radius-store: 1.5rem;      /* cards / media */
--radius-store-lg: 1.75rem;   /* hero / large surfaces */
```

`--radius-sm/md/lg` already exist on `:root` for admin. Storefront may read them. `--radius-store*` are storefront-only (DESIGN.md). Do not change admin radii.

### Spacing scale

Use pixel steps the user locked; expose as rem:

```text
--space-4:  0.25rem;   /* 4 */
--space-8:  0.5rem;    /* 8 */
--space-12: 0.75rem;   /* 12 */
--space-16: 1rem;      /* 16 */
--space-24: 1.5rem;    /* 24 */
--space-32: 2rem;      /* 32 */
--space-48: 3rem;      /* 48 */
```

New CSS in v1.9 prefers these (or `--store-gutter` / `--space-24`) over magic numbers. Existing sheets may keep local values until their phase.

### Shadow

Reuse admin:

```text
--shadow-sm
--shadow-md
```

Do not invent a third shadow scale in v1.9.0.

### Typography

Keep `Instrument Sans` from `app.css` `@theme`. No new font files in v1.9.0.

```text
--font-store: inherit; /* Instrument Sans via --font-sans */
```

Do not switch the storefront to Prompt (DESIGN.md) in v1.9.0 — that is a later token revision, not a data-owner blocker.

### Breakpoints

Match Tailwind v4 defaults already used on `main`:

```text
sm  640px
md  768px
lg  1024px
xl  1280px
```

Do not introduce a parallel breakpoint table.

---

## 3. Component inventory

Classify everything that exists on `main` today.

### A. WS-002 owns (shared primitives)

| Surface | Today | v1.9 action |
|---|---|---|
| Tokens | missing `tokens.css`; vars used anyway | **Create** `resources/css/storefront/tokens.css` |
| Page container | Homepage `77.5rem`, Cart layout `max-w-5xl`, Footer `--store-max-width` | **Add** `x-storefront.layout.page-container` |
| Grid | `x-storefront.layout.grid` (v1.3.0 thin adapter) | Keep name; restyle onto tokens in Blog phase |
| Empty state | `x-storefront.empty-state` | Keep name; token pass in Blog phase |
| Breadcrumb | `x-storefront.breadcrumb` | Keep name; token pass in Blog phase |
| Site footer chrome | `x-storefront.layout.partials.site-footer` + `footer.css` | Keep Blade/DTO contract; consume tokens (already does) |
| Site header chrome | inline in `modules/Cart/resources/views/layouts/storefront.blade.php` + `shell.css` | Extract `x-storefront.layout.partials.site-header` in **Phase 3** |
| Product card | Homepage-only `home-product-card`; Shop uses ad-hoc Tailwind | Shared `x-storefront.cards.product` in **Phase 4** |
| Blog article card | `x-storefront.blog.article-card` + `cards/blog-card` | Stay under `x-storefront.blog.*` / `cards.*`; token pass in **Phase 5** |

### B. Domain owns (do not move)

| Surface | Owner | Notes |
|---|---|---|
| `home-section-*`, hero, promo, FAQ, arrivals | Cart + CMS | Compose primitives; keep section markup in Cart |
| `FooterPageData` / drivers / `footer.config` | Settings Footer | Do not retouch preview JSON |
| Named menus / `NavigationLinkData` | Navigation | Header **reads** `links('main')` in Phase 3; Navigation does not own CSS |
| `store.*` / `social.*` | Website Settings | Header brand reads existing queries |
| Blog archive/single page templates | CMS | Call `x-storefront.*` |
| Shop listing/product page | Cart | Call `x-storefront.cards.product` in Phase 4 |
| Checkout / cart pages | Cart | Out of v1.9.0 |
| Admin components / `cf-btn` | Admin design system | Out |

### C. Not on main — do not import as a set

```text
x-storefront.layout.store-layout
x-storefront.cards.category
x-storefront.buttons.*
x-storefront.commerce.*
auth-footer / checkout footer
archive mega menu
```

Add these only in a later phase spec, one primitive at a time.

---

## 4. Migration order (locked)

Do not refactor every surface in one PR.

```text
Phase 1  Homepage     ← v1.9.0 (this tag)
Phase 2  Footer       consume the same container/tokens (mostly already)
Phase 3  Navigation   extract site-header; optional links('main')
Phase 4  Shop         shared product card
Phase 5  Blog         replace v1.3.0 adapters onto tokens
```

### v1.9.0 = Phase 1 only (plus the token file Footer already needs)

```text
WS-002 v1

✓ resources/css/storefront/tokens.css
✓ Import tokens in app.css before other storefront sheets
✓ x-storefront.layout.page-container
✓ Homepage inner / sections use page-container + --store-max-width (drop 77.5rem)
✓ Footer keeps site-footer Blade/DTOs; tokens.css makes --store-max-width/--store-gutter real
✓ Isolation: no feat/commerce-framework-v1 merge, no archive components.css dump, no admin token rewrite

✗ Extract site-header (Phase 3)
✗ Wire Navigation links('main') into the header (Phase 3)
✗ Shared Shop product card (Phase 4)
✗ Blog adapter replacement (Phase 5)
✗ Appearance admin / WS-002 theme editor
✗ Font change to Prompt
✗ Checkout / cart restyle
```

Value path for v1.9.0:

```text
Define storefront tokens
        ↓
Homepage + Footer share 1280px container / gutter / radius-store
        ↓
Header and Shop still look old until later phases
```

Phase 2 after v1.9.0: if Footer already looks correct once tokens exist, Phase 2 may be a no-op plus tests. Do not restyle Footer Blade.

---

## 5. `page-container` contract

```blade
<x-storefront.layout.page-container>
    {{ $slot }}
</x-storefront.layout.page-container>

<x-storefront.layout.page-container variant="narrow">
```

```text
default → max-width: var(--store-max-width); padding-inline: var(--store-gutter)
narrow  → max-width: var(--store-max-width-narrow)
```

Homepage FAQ inner (`56.25rem`) uses `narrow`. Cart layout `max-w-5xl` is **not** changed in v1.9.0 (header/shop still Phase 3–4).

Do not pass domain DTOs into `page-container`. It is width + gutter only.

---

## 6. Isolation

`tests/Unit/Storefront/Ws002IsolationTest.php` written **before** production token/container files (red: missing `tokens.css`).

Forbidden in new WS-002 files:

```text
SiteIdentityServiceInterface
WebsiteSettingsService
AppearanceController
CustomerExperienceController
StorefrontNavigationConfig
git merge of archive branches
modules/Footer/**
packages/storefront-design-system
```

Forbidden recoveries:

```text
Whole archive components.css
auth-footer / checkout footer
Prompt font swap
Admin semantic-light.css rewritten as storefront source
```

Allowed:

```text
resources/css/storefront/tokens.css
resources/views/components/storefront/layout/page-container.blade.php
Homepage blades using page-container
Existing Footer/Blog/Homepage CSS consuming --store-* tokens
```

`FooterViewIsolationTest` still forbids query classes in `site-footer`. WS-002 does not put queries in primitives.

---

## 7. Branch / test / release

```text
1. This spec locked
2. Branch feat/ws002-design-system-v1 from main (after docs commit if desired)
3. Isolation test red
4. tokens.css + page-container + Homepage consume
5. Visual/regression: GET / and GET /shop footer container still render; no 500
6. Full suite
7. PR → squash merge → tag v1.9.0
```

Phases 2–5 get their own allowlists after v1.9.0. Do not start Scanner, POS, Inventory, or Marketplace on this branch.
