# Catalog V2 Follow-up: Reserved-code hardening at create

**Date:** 2026-09-08  
**Status:** Draft  
**Owner:** Catalog (`modules/Catalog`) — `AttributeService::create`  
**Related:** `docs/superpowers/specs/2026-09-08-catalog-v2-search-discovery-design.md`, `docs/superpowers/specs/2026-09-08-catalog-v2-attribute-code-lock-design.md`

This spec is one implementation unit: **after slug, `AttributeService::create` rejects `SearchReservedParams::KEYS` with `DomainException` and does not persist.** HTTP store requests keep their existing `notIn` so admin still gets `422`. It does not change update/immutability, importer skip policy, or the reserved-key list.

Callers that already go through `create()` (preset service, WooCommerce importer, tests, seeders) inherit the guard. Direct `Attribute::query()->create()` does not.

---

## 1. Problem

Shop listing reserved query params (`q`, `category`, `brand`, `sort`, `availability`, `price_min`, `price_max`, `page`) must not become `attributes.code`, or they collide with Facet Universe URL identity.

Today only FormRequests enforce that:

- `StoreAttributeRequest` / `StoreVariantOptionPresetRequest` — `Str::slug` then `Rule::notIn(SearchReservedParams::KEYS)`
- `AttributeService::create` — slug then insert

Importer, presets, seeders, and tests call the service and skip HTTP. After Attribute Code Lock, a reserved code that lands this way cannot be renamed in admin.

---

## 2. Goals

1. One create choke point: slug, then reject reserved identity, then insert.
2. Admin create UX unchanged: `422` on `code` from FormRequest (not a 500 from an uncaught domain error).
3. Same reserved set as today. Do not add `size`, `color`, or `search`.

Non-goals: suffixing reserved codes, importer skip/log, model `creating` hook, F1 slug-both-sides identity, Octane, index ops, Suggest V1.1, `attribute_sets.code`.

---

## 3. Locked decisions

| Topic | Decision |
|---|---|
| Choke point | `AttributeService::create` only. |
| Order | `$code = Str::slug($data->code, '_');` then `in_array($code, SearchReservedParams::KEYS, true)`. |
| Reject | `DomainException` with message `Attribute code is reserved.` Do not persist. |
| Keys | Current `SearchReservedParams::KEYS` only. F2 does not add keys. |
| HTTP | Leave `notIn` on both store requests. Dual layer is intentional. |
| Presets | `VariantOptionPresetService::create` is unchanged; it already calls `AttributeService::create`. |
| Importer | Unchanged. Existing per-row `catch (\Throwable)` records an error if `create` throws. No skip/log policy in this spec. |
| Existing rows | Leave reserved codes already in the database. Update remains immutable (Code Lock). |
| Eloquent | `Attribute::query()->create()` and model `creating` are out of scope. |
| Unique | Still HTTP `unique:attributes,code`. F2 does not add a uniqueness check in the service. |

---

## 4. Flow

```text
AttributeService::create:
  code = Str::slug(input, '_')
  if code in SearchReservedParams::KEYS → DomainException (no insert)
  insert

HTTP store (unchanged):
  slug → notIn KEYS → 422
  then AttributeService::create (guard is redundant for valid HTTP)

VariantOptionPresetService::create / importer / tests / seeders:
  AttributeService::create → same DomainException
```

---

## 5. Tests (acceptance)

1. `AttributeService::create(code: 'brand')` throws `DomainException` (`Attribute code is reserved.`). No `attributes` row with `code = brand`.
2. `AttributeService::create(code: 'price-min')` throws (slugs to `price_min`). No row with `code = price_min`.
3. `AttributeService::create(code: 'Color')` persists `color`.
4. Existing HTTP reserved-create tests stay green (`AttributeReservedCodeTest`, `VariantOptionReservedCodeTest` store cases) — still `assertInvalid('code')`, not 500.
5. `VariantOptionPresetService::create(code: 'brand', ...)` throws the same `DomainException` and does not persist.

---

## 6. Out of scope

- Importer skip/log policy
- Model `creating` hook
- `Attribute::query()->create()`
- F1: `Str::slug(request.code) === Str::slug(stored.code)`
- SearchSynonymExpander / Octane
- Index rebuild flush / discovery cap / label N+1
- Suggest overlay a11y / throttle / i18n
- `attribute_sets.code`
- Expanding `SearchReservedParams::KEYS`

---

## 7. Delivery

```text
Wave 1  Reject reserved codes in AttributeService::create
        Regression: existing HTTP reserved-create tests
```

Human gate after the wave. One small PR. Do not start implementation until this spec is Locked and an implementation plan is written.
