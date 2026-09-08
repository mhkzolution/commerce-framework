# Catalog V2 Follow-up: Importer reserved-code skip (F2b)

**Date:** 2026-09-08  
**Status:** Draft for review  
**Owner:** Product importer (`modules/Product/src/Import`)  
**Related:** `docs/superpowers/specs/2026-09-08-catalog-v2-reserved-code-hardening-design.md`, `docs/superpowers/specs/2026-09-08-catalog-v2-search-reserved-params-support-design.md`

This spec is one implementation unit: **WooCommerce CSV import skips `Attribute 1–4 name` columns whose slugged code is in `SearchReservedParams::KEYS`, continues the product, and reports a warning.** It does not change `AttributeService`, does not catch `DomainException` as control flow, and does not increment `skipped` or `errors`.

---

## 1. Problem

F2 rejects reserved `attributes.code` at `AttributeService::create` (`DomainException`: `Attribute code is reserved.`).

The importer maps CSV `Attribute N name` through `attributeCode($name)` (`Str::slug($name, '_')`, or `attr_<md5>` if the slug is empty) and calls `create`. A WooCommerce column named `Brand` becomes `brand`, which is in `KEYS`. The importer’s per-row `catch (\Throwable)` then marks the **entire product** as an error. Other attributes on the same row never apply.

The CSV `Brands` column (product brand / `brand_uuid`) is a different field. This spec only concerns `Attribute N name`.

---

## 2. Goals

1. Skip only the reserved attribute column. The product still imports (`created` / `updated`).
2. Do not create a reserved `attributes.code` and do not attach values for that column, including when a legacy reserved row already exists in the database or `attributeCache`.
3. Warn without treating the product as skipped or failed.
4. Leave other create failures exactly as today.

Non-goals: suffixing reserved codes, expanding `KEYS`, catching `DomainException` to skip, changing `AttributeService`, deleting legacy reserved rows, Octane, index ops, Suggest V1.1, `attribute_sets.code`.

---

## 3. Locked decisions

| Topic | Decision |
|---|---|
| Where | `WooCommerceProductImporter::resolveAttributeValues` only. Same helper is used by simple `upsertRow` and variable `upsertVariableRow` (parent row). CLI `import()` and admin `importForAdmin` both go through it. |
| Check | After `attributeCode($name)`, before cache lookup. `in_array($code, SearchReservedParams::KEYS, true)`. |
| Skip | Do not look up `attributeCache`. Do not call `AttributeService::create`. Do not attach values for that column. Continue the remaining Attribute 1–4 columns and the rest of the row. |
| Legacy | Always skip when the slugged code is in `KEYS`, even if that attribute already exists in DB or cache. Legacy `brand` (and other reserved codes) must not be attached by import. |
| Service | `AttributeService` unchanged. F2b does not catch `DomainException` as the skip mechanism. |
| Other create failures | Non-reserved create failures (database exception, unexpected create failure) continue to fail the row exactly as today. |
| Counters | Do not increment `skipped`. Do not increment `errors`. Product still counts as created or updated. |
| Warning copy | `Row {rowId}: skipped reserved attribute column "{name}" (code "{code}").` `{rowId}` is the existing `rowId($row)`. `{name}` is the raw CSV `Attribute N name`. `{code}` is `attributeCode($name)`. |
| CLI | `$output->writeln('<comment>'.$message.'</comment>')` with that copy. |
| Admin | Same copy in `ProductCsvImportResult::$messages` via a new `withMessage()` that appends to `messages` and does **not** increment `created`, `updated`, `skipped`, `duplicates`, or `errors`. Do not use `withSkipped()` for this. |
| Warning plumbing | `resolveAttributeValues` (or a helper it calls) records each skip. CLI and admin callers emit those warnings. Do not writeln inside the helper in a way that admin cannot capture. |
| Dedup | Messages may repeat per row. Deduplication is not required in F2b. |
| Keys | Current `Commerce\Support\SearchReservedParams::KEYS` only. Do not add `size`, `color`, or `search`. |
| Dry-run | Unchanged. CLI dry-run does not call `resolveAttributeValues` today and does not need reserved warnings. |
| Variant options | `buildVariantOptions` / `buildVariantOptionsMap` are unchanged. They do not call `AttributeService::create`. |
| Product `Brands` | Unchanged. |

---

## 4. Flow

The reserved check **must** run before cache lookup. That is what keeps a legacy reserved `attributes` row from being attached.

```text
column name
  -> attributeCode(name)

if code in SearchReservedParams::KEYS:
    report warning
    continue

lookup attribute cache
create attribute if missing
attach values
```

```text
Attribute N name empty → continue (no warning)

code not in KEYS:
  cache hit → attach value if present
  cache miss → AttributeService::create (F2 still rejects reserved codes if this path is ever reached)
  attach value if present

Non-reserved create throws:
  per-row catch (\Throwable) → error, same as today
```

Do not wrap `create` in a reserved-code catch. The skip path is the `KEYS` pre-check only.

---

## 5. Tests (acceptance)

Extend `tests/Feature/Product/ProductCsvImportTest.php` (admin `import.store`) unless a CLI-only case needs the importer command. Existing import tests stay green.

1. `Attribute 1 name` = `Brand`, `Attribute 1 value(s)` = `Nike`, plus a non-reserved column (`Color` / `สี`) with a value → product is created. No `attributes` row with `code = brand` is created by this import. The product has the Color/สี value and does **not** have a brand attribute value. `import_result` has `created` (or `updated`) incremented, `errors` empty, `skipped` not incremented for this product, and `messages` contains `Row {id}: skipped reserved attribute column "Brand" (code "brand").`
2. `Attribute 1 name` = `price-min` or `Price Min` (both slug to `price_min`) → same skip. No `attributes.code = price_min`. Warning uses the raw name and slugged code.
3. `Attribute 1 name` = `Color` with a value still creates/attaches `color` (or uses existing `color`). No reserved warning.
4. Reserved-only row (`Brand` only, no other Attribute N columns) still creates the product. Warning present. `errors` empty.
5. Legacy: an `attributes` row with `code = brand` already exists and is present in the importer cache/set. Importing `Attribute 1 name` = `Brand` still skips. The imported product does not receive that attribute’s value.
6. Non-reserved create failures (e.g. database exception, unexpected create failure) continue to fail the row exactly as today (`errors` incremented / CLI `<error>`, product not counted created/updated from that catch).
7. Existing HTTP reserved-create and service-guard tests stay green (`AttributeReservedCodeTest`, `VariantOptionReservedCodeTest`, `AttributeService::create` reserved cases).

---

## 6. Out of scope

- Suffix / rename reserved CSV columns into a non-reserved code
- Catching `DomainException` to skip
- Changing `AttributeService` or F2 HTTP `422`
- Expanding `SearchReservedParams::KEYS`
- Deleting or renaming legacy reserved rows in the database
- Deduplicating identical warnings across rows
- `buildVariantOptions` reserved names
- CSV `Brands` / `resolveBrandUuid`
- CLI dry-run reserved warnings
- SearchSynonymExpander / Octane
- Index rebuild flush / discovery cap / label N+1
- Suggest overlay a11y / throttle / i18n
- `attribute_sets.code`

---

## 7. Delivery

```text
Wave 1  withMessage() + reserved skip in resolveAttributeValues (CLI comment + admin messages)
        Tests in ProductCsvImportTest + reserved-create regression
```

Human gate after the wave. One small PR.
