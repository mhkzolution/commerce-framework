# Shop category strip

**Date:** 2026-09-09  
**Status:** Approved for implementation  
**Owner:** Storefront shop listing (`ShopController`, `cart::storefront.shop`, filter chrome)  
**Related:** `docs/superpowers/specs/2026-09-09-category-navigation-v1-design.md`

Category Navigation V1 already made `?category=` on a parent inclusive of descendants. This job only changes **where** shop categories are chosen.

**Out of scope:** taxonomy, `product_categories`, mega/mobile Shop tree, PDP breadcrumb/badge, suggest, images, recategorizing leftovers.

---

## Decisions

- Categories sit in a **strip at the top** of `/shop`, not in the filter sidebar or mobile filter sheet.
- Unfiltered shop: strip shows **visible parents**.
- Click a **parent**: listing uses that parent slug (inclusive descendants). Strip shows **that parent’s children** plus a **back to all categories** control.
- Click a **child**: listing is that leaf only. Strip still shows that parent’s children (active child highlighted) plus back to all.
- **Back to all categories** removes `category` and returns the parent chips. It keeps other query params (`q`, brand, attributes, sort, price).
- Category chip links also keep those other query params and replace `category`.
- Filter forms keep the current category via a **hidden `category` input** so Apply does not drop it.
- Empty nodes stay omitted (same visible tree as `HomepageNavigationQuery::shopFilterOptions()`).
- A parent with no visible children still shows the back control after it is selected.

## Surfaces

| Surface | Behavior |
|---|---|
| Shop category strip | Parents, or children + back |
| Shop filter sidebar | No category tree |
| Shop filter sheet | No category tree |
| Mega / mobile Shop | Unchanged nested tree |
| `?category=` listing query | Unchanged (parent inclusive, child exact) |

## Non-goals

- Changing category records or product assignments
- A “back to parent” step between child and all categories
- Recreating the sidebar nested tree elsewhere
