# Product card “ใหม่” badge

**Date:** 2026-09-09  
**Status:** Approved  
**Owner:** Sample plugin `product-badge` + `ProductCardData`

Replace the price-based Premium/Popular sample badges with a single **New** badge.

## Decisions

- Show **ใหม่** (EN: New) when `product.created_at` is within the last **14 days**, inclusive of the cutoff instant (`created_at >= now() - 14 days`).
- Older products: **no badge**.
- Surfaces: every `x-storefront.cards.product` via hook `storefront.product.card` (Shop, homepage, related cards).
- No admin menu and no per-product badge editor in this job.
- Pass `createdAt` on `ProductCardData`. Missing/null createdAt → no badge.
- Do not use price. Remove Premium and Popular.

## Out of scope

Admin plugin toggle, threshold settings, PDP-only badge, category badge.
