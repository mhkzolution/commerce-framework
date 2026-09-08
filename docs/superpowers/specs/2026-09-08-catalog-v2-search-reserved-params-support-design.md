# Catalog V2 Follow-up: Move SearchReservedParams to Support

**Date:** 2026-09-08  
**Status:** Draft  
**Owner:** Shared (`packages/commerce/support`) + Catalog / Product / Cart callers  
**Related:** `docs/superpowers/specs/2026-09-08-catalog-v2-search-discovery-design.md`, `docs/superpowers/specs/2026-09-08-catalog-v2-reserved-code-hardening-design.md`

This spec is one implementation unit: **move `SearchReservedParams` out of Product into `commerce/support` so Catalog (and Cart) no longer import Product for a shop-URL constant list.** It is a dependency refactor. Behavior of reserved-code create, shop listing, discovery, suggest, and importer does not change.

---

## 1. Problem

`SearchReservedParams::KEYS` is the shop listing reserved query set. Catalog's `AttributeService::create` and `StoreAttributeRequest` import `Commerce\Product\Support\SearchReservedParams`. Cart's `ShopListingFilters` does the same.

Catalog's `composer.json` / `module.json` do not declare a Product dependency. After reserved-code hardening, the domain create path cannot run without Product's autoload. That is an accidental cycle: Product already depends on Catalog.

---

## 2. Goals

1. One home: `Commerce\Support\SearchReservedParams` in `packages/commerce/support`.
2. Hard cut: no `class_alias`, no leftover Product class.
3. `KEYS` stays byte-for-byte the current list.
4. Callers keep the same `in_array` / `Rule::notIn` usage.

Non-goals: F2b importer policy, expanding `KEYS`, stored-code slug-both-sides identity, Octane, index ops, Suggest, `attribute_sets.code`, discovery ranking.

---

## 3. Locked decisions

| Topic | Decision |
|---|---|
| Home | `packages/commerce/support/src/SearchReservedParams.php` — namespace `Commerce\Support`. |
| Keys | `['q', 'category', 'brand', 'sort', 'availability', 'price_min', 'price_max', 'page']` unchanged. |
| Cut | Delete `modules/Product/src/Support/SearchReservedParams.php`. No alias. |
| Callers | Update `use` in `AttributeService`, `StoreAttributeRequest`, `StoreVariantOptionPresetRequest`, `ShopListingFilters`. |
| Catalog composer | Add `"commerce/support": "*"` to `modules/Catalog/composer.json`. |
| Product composer | Add `"commerce/support": "*"` if Product still imports the class (preset store request). Cart already requires `commerce/support`. |
| Autoload | `composer dump-autoload` plus Catalog / Product / Cart tests that cover reserved create and shop filters must pass **without** a Product `class_alias`. |

---

## 4. Flow

```text
packages/commerce/support
  SearchReservedParams::KEYS

Catalog AttributeService / StoreAttributeRequest
Product StoreVariantOptionPresetRequest
Cart ShopListingFilters
  → Commerce\Support\SearchReservedParams

Product Support\SearchReservedParams
  → deleted
```

---

## 5. Tests (acceptance)

1. Grep the repository: no remaining `Commerce\Product\Support\SearchReservedParams` (PHP `use` / FQCN). Docs that describe the old path may mention the move once in this spec; production PHP must not import it.
2. Catalog PHP has no import from `Commerce\Product\Support\SearchReservedParams`.
3. Cart PHP has no import from `Commerce\Product\Support\SearchReservedParams`.
4. `KEYS` on the new class equals the list in section 3.
5. Existing reserved-create HTTP + service tests stay green (`AttributeReservedCodeTest`, `VariantOptionReservedCodeTest`) — still `422` on HTTP, `DomainException` on service, no Product alias.
6. Shop listing still treats `q` / `search` as search and does not treat them as attribute filters (`ShopListingFilters` / existing shop filter tests).
7. After `composer dump-autoload`, those suites pass. Autoload of `Commerce\Support\SearchReservedParams` does not require the deleted Product class.

---

## 6. Out of scope

- Importer reserved-code row policy (F2b)
- Changing `KEYS` (no `size` / `color` / `search` in this spec)
- Stored-code slug-both-sides identity
- Attribute code immutability
- Discovery / suggest / facets / ranking
- `attribute_sets.code`

---

## 7. Delivery

```text
Wave 1  Move class + update callers + composer requires + delete Product class
        Regression: reserved-code tests + shop listing filters
```

Human gate after the wave. One small PR. Do not start implementation until this spec is Locked and an implementation plan is written.
