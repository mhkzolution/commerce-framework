# Catalog V2 Follow-up: Immutable attribute codes

**Date:** 2026-09-08  
**Status:** Draft  
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
2. Update never changes the stored code. A posted different code is **rejected** (not silently overwritten, not re-slugged into a new identity).
3. Same guard in the model as values: writing a dirty `code` throws `DomainException`.
4. Admin edit forms show the code as read-only identity.

Non-goals: migrating existing codes, redirect maps, changing `attribute_sets.code`, discovery/suggest/stock/PDP, Octane synonym map, index rebuild/cap.

---

## 3. Locked decisions

| Topic | Decision |
|---|---|
| Identifiers | `attributes.code` and preset codes (same column) are immutable after create. |
| Create | `Str::slug($code, '_')` allowed. Unique per tenant. `notIn` reserved params. |
| Update | Do not slug. Do not write `code`. Existing row is preserved. |
| HTTP reject | If the request includes `code` and it is not **exactly** the stored code, `422` on `code`. Same stored string may be posted (readonly inputs still submit). Missing `code` on update is allowed; still preserve. |
| Service | `AttributeService::update` does not set `code`. `VariantOptionPresetService::update` does not pass a new code into that write. |
| Model | `Attribute::updating`: if `isDirty('code')`, throw `DomainException` (same pattern as `AttributeValue`). |
| Form | Edit: code control is `readonly` (not `disabled`, so the original value still posts). Create stays editable. |
| Migration | A future dedicated code-migration workflow is out of scope. |
| Sets | `attribute_sets.code` is not in this spec. |

---

## 4. Flow

```text
create:
  slug(code) → reserved check → insert

update:
  if request.code is present and request.code !== stored.code → 422
  persist name/type/flags/options/position only
  model aborts if code is dirty
```

Do not reindex because of a code change — there is no code change.

---

## 5. Tests (acceptance)

1. Create attribute (or preset) with `code: 'Color'` persists `color`. Reserved `brand` / slugged `price-min` still invalid on **create**.
2. Update with `code` not exactly equal to the stored value returns `422` on `code`. The row is unchanged (including name). Do not apply a partial update.
3. Update posting the **exact** stored code plus a new name succeeds; code unchanged.
4. `AttributeService::update` (or direct `$attribute->update(['code' => 'x'])`) cannot persist a new code (`DomainException`).
5. Preset update posting a reserved code (`q`, `page`) is invalid because it is a code change, not because we re-slug into reserved. Stored preset code unchanged. (`VariantOptionReservedCodeTest` update case stays green.)
6. Shop `GET /shop?color=red` still works when the attribute code is `color` (no listing change).

---

## 6. Out of scope

- Facet universe (done)
- SearchSynonymExpander / Octane
- Index rebuild flush / discovery cap / label N+1
- Renaming `attribute_sets.code`
- Suggest overlay a11y / throttle / i18n

---

## 7. Delivery

```text
Wave 1  Attribute + preset code immutable after create
        Regression: reserved-code create tests + Phase 1 shop color filter
```

Human gate after the wave. One small PR.
