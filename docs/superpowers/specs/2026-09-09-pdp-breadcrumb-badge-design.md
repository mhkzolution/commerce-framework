# PDP polish: breadcrumb + category badge

**Date:** 2026-09-09  
**Status:** Approved for implementation  
**Owner:** Storefront PDP (`ProductDetailBuilder`, `ProductDetailData`, `cart::storefront.product`)  
**Related:** `docs/superpowers/specs/2026-09-09-category-navigation-v1-design.md`

Parent `?category=` listings already include descendants. This job only changes how a product page shows its assigned category.

**Later jobs (not this spec):** Suggest Enhancement, 87 stub JPEG recovery.

---

## Decisions

- PDP only. Shop listing breadcrumbs stay `Shop → current`.
- Trail: **Home → Parent → Child → Product** when the product is assigned to a child.
- Parent-only assignment (435 leftovers): `Home → Parent → Product`.
- Uncategorized (`110092`): `Home → Product`.
- One linked badge = the **assigned** category (leaf or parent). No parent+child pair of badges.
- Uncategorized: no badge.
- No taxonomy changes, no product moves, no suggest changes, no image work, no related-product changes.

---

## Trail rules

Built in `ProductDetailBuilder::breadcrumbItems()`. The Blade breadcrumb component is unchanged.

| Crumb | Label | URL |
|---|---|---|
| Home | `__('storefront::storefront.home')` | `route('storefront.home')`; if the route is missing, `/` |
| Parent | Parent category name | `/shop?category={parent.slug}` |
| Assigned | Assigned category name | `/shop?category={assigned.slug}` |
| Product | Product name | none (`aria-current`) |

Assigned category is `$product->categories->first()` (catalog is single-assign).

Omit a category crumb when that node is missing or its `slug` is empty. Do not invent a Shop crumb. Do not invent a child crumb.

If the assigned node is already a root, there is no parent crumb (same shape as parent-only).

---

## Badge

`ProductDetailData` adds:

- `?string $categoryName = null`
- `?string $categoryUrl = null`

Both set only when the assigned category has a non-empty slug. Both null when uncategorized or the assigned slug is empty.

Buy box: immediately under the H1, a single link:

- class `storefront-buy-box__category`
- text = `categoryName`
- href = `categoryUrl` (same as the assigned crumb)

No badge markup when both fields are null.

---

## Surfaces

| File | Change |
|---|---|
| `packages/commerce/contracts/src/Storefront/ProductDetailData.php` | `categoryName`, `categoryUrl` |
| `modules/Cart/src/Services/ProductDetailBuilder.php` | Load `categories.parent`; build trail + badge fields |
| `modules/Cart/resources/views/storefront/product.blade.php` | Badge under H1 |
| `resources/css/storefront/pdp.css` | `.storefront-buy-box__category` |

ShopController, shop Blade, HomepageNavigationQuery, suggest, and `product_categories` are not in this job.

---

## Tests (TDD)

Failing tests first.

1. **Child product** — assigned to a child of an active parent with slugs. Builder crumbs: Home, parent, child, product name. `categoryName` / `categoryUrl` match the child.
2. **Parent-only product** — assigned to a root. Crumbs: Home, parent, product. Badge matches the parent.
3. **Uncategorized** — crumbs: Home, product. Both category fields null. HTML has no `storefront-buy-box__category`.
4. **HTTP** — child PDP HTML contains Home + parent name + child name, badge href `?category={child-slug}`, and no extra Shop crumb between Home and the parent.

Existing `ProductDetailBuilderTest` and storefront PDP tests must stay green.

---

## Non-goals

- Shop `/shop?category=` breadcrumb trail
- English/infix suggest matching
- Related products
- Category records or assignments
- Stub JPEG recovery
- Quick View payload (already has a category name string)
