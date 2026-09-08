# Catalog V2 Follow-up: Immutable attribute codes

**Date:** 2026-09-08  
**Status:** Locked  
**Owner:** Catalog (`modules/Catalog`) + variant option presets (`modules/Product`)  
**Related:** `docs/superpowers/specs/2026-09-07-catalog-v2-attribute-variant-design.md`, `docs/superpowers/specs/2026-09-08-catalog-v2-search-discovery-design.md`, `docs/superpowers/specs/2026-09-08-catalog-v2-facet-universe-design.md`

This spec is one implementation unit: **`attributes.code` and `variant_option_presets.code` are immutable identifiers after create**. It does not add a code-migration workflow, reindex-on-rename, or change shop listing/facet SQL.

Presets persist as `attributes` rows (`VariantOptionPresetService`). One immutability rule covers both admin surfaces.

---

## 1. Problem

Shop filter URLs use `attributes.code`. Phase 1 already made `attribute_values.code` immutable. `attributes.code` is still rewritten on every update:

- `UpdateAttributeRequest` and `UpdateVariantOptionPresetRequest` run `Str::slug($code, '_')`
- `AttributeService::update` slugs again and writes `code`

Editing a name-adjacent code field (or a merchandiser typing a new slug) changes `?color=` / `?size=` and breaks bookmarks. Reserved-code checks on update also fight a field that should not move.

---

## 2. Goals

1. Create still normalizes: `Str::slug($code, '_')` (request + service). Reserved codes stay rejected on create (`SearchReservedParams`).
2. Update never changes the stored code. A posted **different identity** is **rejected** (not silently overwritten, not re-slugged into a new identity).
3. Same guard in the model as values: writing a dirty `code` on a **persisted** row throws `DomainException`.
4. Admin edit forms show the code as read-only identity (convenience only).

Non-goals: migrating existing codes, redirect maps, changing `attribute_sets.code`, discovery/suggest/stock/PDP, Octane synonym map, index rebuild/cap.

---

## 3. Locked decisions

| Topic | Decision |
|---|---|
| Identifiers | `attributes.code` and preset codes (same column) are immutable after create. |
| Create | `Str::slug($code, '_')` allowed. Unique per tenant. `notIn` reserved params. |
| Update | Do not slug into a new stored value. Do not write `code`. Existing row is preserved. |
| HTTP identity | If the request includes `code`, compare **normalized** identity: `Str::slug(request.code, '_') === stored.code`. Reject with `422` on `code` when they differ. Missing `code` on update is allowed; still preserve. |
| Same identity | `shoe_size`, `Shoe Size`, and `shoe-size` are the same identity as stored `shoe_size`. `footwear_size` is not. |
| Service | `AttributeService::update` does not set `code`. `VariantOptionPresetService::update` does not pass a new code into that write. |
| Model | `Attribute::updating` only (not `creating`, not a `saving`/mutator that also fires on create): if `isDirty('code')`, throw `DomainException`. Immutability applies only after persistence; create remains unrestricted. |
| Form | Edit: code control is `readonly` (not `disabled`, so the original value still posts). Create stays editable. Readonly UI is convenience only. Server-side validation and model immutability are the source of truth. |
| Migration | A future dedicated code-migration workflow is out of scope. |
| Sets | `attribute_sets.code` is not in this spec. |

---

## 4. Flow

```text
create:
  slug(code) → reserved check → insert
  (no immutability guard)

update:
  if request.code is present
  and Str::slug(request.code, '_') !== stored.code
  → 422 (whole request rejected; no partial update)
  persist name/type/flags/options/position only
  model abort if code is dirty on a persisted row
```

Do not reindex because of a code change — there is no code change.

---

## 5. Tests (acceptance)

1. Create attribute (or preset) with `code: 'Color'` persists `color`. Reserved `brand` / slugged `price-min` still invalid on **create**.
2. Update with a **different identity** (`footwear_size` when stored is `shoe_size`) returns `422` on `code`. The row is unchanged (including name). Do not apply a partial update.
3. Update posting a **same identity** as stored (`shoe_size`, `Shoe Size`, or `shoe-size` when stored is `shoe_size`) plus a new name succeeds (`200` / redirect). Code unchanged.
4. `AttributeService::update` (or direct `$attribute->update(['code' => 'x'])`) cannot persist a new code (`DomainException`). Create / `creating` must still persist a code.
5. Preset update posting a reserved code (`q`, `page`) is invalid because it is a **different identity**, not because we re-slug into reserved. Stored preset code unchanged. (`VariantOptionReservedCodeTest` update case stays green.)
6. Preset stored `shoe_size`, update `code="Shoe Size"` (same identity) succeeds (`200` / redirect). Code remains `shoe_size`.
7. Shop `GET /shop?color=red` still works when the attribute code is `color` (no listing change). Existing `ShopAttributeFilterTest` is sufficient.

---

## 6. Out of scope

- Facet universe (done)
- SearchSynonymExpander / Octane
- Index rebuild flush / discovery cap / label N+1
- Renaming `attribute_sets.code`
- Suggest overlay a11y / throttle / i18n
- Dedicated code-migration workflow

---

## 7. Delivery

```text
Wave 1  Attribute + preset code immutable after create
        Regression: reserved-code create tests + Phase 1 shop color filter
```

Human gate after the wave. One small PR.
