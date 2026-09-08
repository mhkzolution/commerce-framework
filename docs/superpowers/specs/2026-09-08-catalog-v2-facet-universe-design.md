# Catalog V2 Follow-up: Shop filter facet universe

**Date:** 2026-09-08  
**Status:** Locked  
**Owner:** Storefront shop (`modules/Cart`)  
**Related:** `docs/superpowers/specs/2026-09-08-catalog-v2-search-discovery-design.md`, `docs/superpowers/specs/2026-09-07-catalog-v2-attribute-variant-design.md`

This spec is one implementation unit: **listing SQL and facet SQL use the same attribute-code universe**. It does not change discovery ranking, synonyms, suggest, `constrainInStock()`, PDP combination rules, or multi-select.

---

## 1. Problem

Phase 2 locked filter URLs as one query param per `attributes.code`, value = `attribute_values.code`. The shop filter chrome already emits that: each facet chip group uses `name="{code}"`.

Listing still applies reserved `size` / `color` against a **config group** of attribute ids. `ShopFilterCatalogService::groupAttributes()` buckets any filterable attribute whose `code` or `name` contains a configured needle (`size`, `shoe_size`, `ขนาด`, …). `ShopProductQuery::paginate()` then runs:

```text
?size=s  →  applyAttributeGroupFilter(all size-group ids, "s")
?color=  →  applyAttributeGroupFilter(all color-group ids, …)
then remaining attributes[] except size/color, one id per code
```

So `?size=s` matches apparel `size=s` **and** `shoe_size=s`. Facet counts for `size` only join `attribute_id` of the `size` row. The listing set and the Size facet are different universes.

`cart.storefront.filters.groups` is the leftover of pre–Phase 2 chrome (one Size chip, one Color chip). That chrome is gone.

---

## 2. Goals

1. `GET /shop?{code}={value}` filters only the attribute whose `code` equals `{code}`.
2. Reserved `size` / `color` remain aliases for those **exact** codes. `?color=red` keeps working when the attribute code is `color`. It does not match an attribute whose **name** is Color but whose code is something else.
3. Facet self-exclusion and listing share that same mapping. A Size chip and a Shoe size chip do not steal each other’s products.
4. Phase 2 discovery (`q`), brand, category, price, availability, and suggest stay unchanged.

Non-goals: merging size attributes into one merchandising facet, multi-select, changing reserved-param collision rules, deleting `cart.storefront.filters.groups` from config (may remain unread), Octane synonym map, attribute-code slug rewrite on update, index rebuild/cap.

---

## 3. Locked decisions

| Topic | Decision |
|---|---|
| Universe | One filterable attribute = one URL param = one listing predicate = one facet. Param name is `attributes.code`. |
| `?size=` | Matches only the attribute with code `size` (exact). Not `shoe_size`, not name contains “size”. |
| `?color=` | Matches only the attribute with code `color` (exact). Not `colour` unless that is the stored code. |
| Other codes | Unchanged: `?shoe_size=`, `?material=`, etc. already work via `ShopListingFilters::$attributes`. |
| Alias | `ShopListingFilters` may keep copying reserved `size`/`color` query params into `$attributes['size']` / `$attributes['color']`. Listing must not apply those params a second time via group ids. |
| Group config | Listing and facet SQL **must not** read `cart.storefront.filters.groups` or `sizeAttributeIds` / `colorAttributeIds`. `exclude_codes` is not expanded in this spec (it never hid facets). |
| Match | Still Phase 1: `attribute_values.code` only. Non-axis = product-level PAV; axis = any variant. |
| Chrome | Keep per-code facet chip groups. Do not restore a combined Size/Color group UI. |
| DTO | After this change, listing and facet logic must not depend on `sizeAttributeIds` / `colorAttributeIds`. The fields may be removed if no remaining storefront consumer exists. Same for unused `$sizes` / `$colors` (Blade already reads `$facets`, not those maps). |

---

## 4. Query

```text
attributes = request codes (filterable) ∪ { size, color } if those reserved params are present
for each (code, value) in attributes:
  resolve Attribute where is_filterable and code = that code (exact, one row)
  applyAttributeGroupFilter(query, [that id], value)
```

Unknown or non-filterable codes stay ignored (today’s `filterableAttributes()` allowlist). They do **not** abort the request. Resolve with an existence check (`whereIn` / `first`), never `firstOrFail()`.

These requests must not create an attribute predicate:

```text
GET /shop?foo=bar      unknown param, not a filterable attribute code
GET /shop?brand=red    reserved brand filter (slug), not attributes.code
GET /shop?page=2       reserved pagination, not an attribute
```

`200` with the usual listing. `brand=red` may empty the grid when no brand slug is `red`; that is still a brand predicate, not an attribute miss.

Do not `unset($attributes['size'], $attributes['color'])` before the loop. Do not also apply `$filters->size` / `$filters->color` through a second id list.

---

## 5. Tests (acceptance)

1. Two filterable attributes `size` and `shoe_size`, both with value code `s`. Product A has apparel size `s` only. Product B has shoe size `s` only. `GET /shop?size=s` sees A, not B. `GET /shop?shoe_size=s` sees B, not A.
2. Size facet counts for `s` do not include Product B. Shoe size facet counts for `s` do not include Product A.
3. Phase 1 semantics: a variable product with a Red variant is included when the request param is **that attribute’s code** and the value is `red`. Production URL examples continue to use `?color=` only when the actual attribute code is `color`. Tests that created `code = color-p1-{uniqid}` with name Color and called `?color=red` relied on group substring matching; they must call `?{actualCode}=red` instead. (`ShopAttributeFilterTest` already uses code `color`.)
4. Direct `ShopProductQuery` tests that stuffed a non-color attribute into `colorAttributeIds` (material cotton) must filter by that attribute’s **code** in `$filters->attributes`, not via the color group slot.
5. `CatalogV2SearchDiscoveryRegressionTest` stays green (self-excluding facets, empty `q`, discovery once).
6. `GET /shop?foo=bar` and `GET /shop?page=2` return `200` and do not throw. They do not apply an attribute filter. `GET /shop?brand=red` is the reserved brand filter, not `firstOrFail` on an attribute named brand.
6. `GET /shop?foo=bar`, `GET /shop?page=2` return `200` and do not throw. They do not apply an attribute filter. `GET /shop?brand=red` is the reserved brand filter, not `firstOrFail` on an attribute named brand.

---

## 6. Out of scope

- Keyboard/ARIA overlay, suggest throttle, localized suggest headings
- SearchSynonymExpander / Octane
- `UpdateAttributeRequest` rewriting `code`
- Index rebuild flush / discovery candidate cap / label reindex N+1
- Multi-select facets, merchandising, Meilisearch

---

## 7. Delivery

```text
Wave 1  Listing + facet SQL share exact attribute.code
        Regression: Phase 1 shop color filter + Phase 2 discovery tests
```

Human gate after the wave. One small PR.
