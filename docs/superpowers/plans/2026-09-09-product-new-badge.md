# Product “ใหม่” badge Implementation Plan

> **For agentic workers:** Implement task-by-task. TDD.

**Goal:** Storefront product cards show “ใหม่” only when the product was created in the last 14 days.

**Architecture:** `ProductCardMapper` copies `created_at` onto `ProductCardData`. The `product-badge` hook reads that timestamp and prepends a CSS-classed span. Related-product clones keep `createdAt`.

**Tech Stack:** Laravel hooks, `ProductCardData`, storefront CSS, Cart lang.

## Global Constraints

- Badge copy: TH `ใหม่`, EN `New` via `storefront::storefront.new_badge`.
- Inclusive window: `created_at >= now()->subDays(14)`.
- No admin UI.

## Tasks

- [ ] Failing plugin tests for new / 14-day / 15-day / no Premium
- [ ] `createdAt` on DTO + mapper + related-card clone
- [ ] Plugin + CSS + lang
- [ ] Run `php artisan test --filter=SamplePluginsTest` and `AppearanceSettingsTest` is unrelated; run SamplePlugins + HomepageDtoRendering
