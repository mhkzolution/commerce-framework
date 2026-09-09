# Attribute Recovery

**Date:** 2026-09-09  
**Status:** Approved for implementation  
**Owner:** Catalog (`modules/Catalog`) + Product import/workspace (`modules/Product`) + storefront shop facets (`modules/Cart`)  
**Related:** `docs/superpowers/specs/2026-09-07-catalog-v2-attribute-variant-design.md`, `docs/superpowers/specs/2026-09-08-catalog-v2-search-discovery-design.md`, `docs/superpowers/specs/2026-09-09-product-csv-woocommerce-import-export-design.md`

Catalog V2 Phase 1 made shop filters and search **relation-only**: they read `attribute_values.code` through `product_attribute_values.attribute_value_id`. WooCommerce import wrote free-text into `product_attribute_values.value` and never created catalog values. Shop facet headers appear; chips do not. Admin workspace only renders selectable catalog values.

**Locked elsewhere (do not touch):** 30-category taxonomy, `product_categories` assignments, Category Navigation V1, PDP breadcrumb/badge, Suggest Enhancement, 87 stub JPEG recovery, shop category-strip chrome.

---

## Amendments (2026-09-09)

1. **Size + age are audited and canonicalized before `type=select`.** Do not freeze messy tokens (`4-5 Y`, `12-18 เดือน`, `5t`) as catalog values.
2. **PDP spec is a collection of values**, not a comma-joined string and not `first()` of many PAV rows.
3. **Durable writers ship before the recovery command.** Import / `ProductService` must write FKs first so recovery cannot be undone by the next CSV upsert.

---

## 1. Current-state analysis

### 1.1 Tables and roles

```text
attributes                          catalog definition (code, name, type, is_filterable)
  └─ attribute_values               discrete options (code immutable, label display)
        │
attribute_sets
  └─ attribute_set_attributes      which definitions belong to a set
        │
products.attribute_set_id           all 1,370 products → WooCommerce Default (id 2)
  ├─ product_attributes            “this product uses these attributes” (used_for_variations)
  └─ product_attribute_values
        ├─ attribute_value_id     Catalog V2 SoT for filters / search / workspace select
        └─ value                   free-text leftover; PDP currently falls back to this string
```

Shop listing filters (`ShopFilterCatalogService::facet`, `ShopProductQuery::matchAttributeFilterValue`) join:

`products` → `product_attribute_values` → `attribute_values.code`

They ignore `product_attribute_values.value`.

Search (`ProductSearchIndexer`) skips a PAV row when `attributeValue` is null. Suggest (`ProductSuggestQuery`) does not read attributes.

Admin edit is the product workspace (`/admin/products/{uuid}/edit`), Organization tab. `resources/js/admin/product-workspace/attributes-panel.js` draws option chips only from `attribute.values`. For `type !== 'select'` it prints the type name (`text`) and no editor. Non-axis select uses **radio** (one value).

Import (`WooCommerceProductImporter`) builds `SaveProductWorkspaceData` with `attributeValues` as `attributeId => string`. When `productAttributes` is empty, `ProductWorkspaceSaveService::syncProductAttributeValues` / `ProductService::syncAttributeValues` insert PAV with `value` only.

### 1.2 What exists in this catalog (read-only snapshot, 2026-09-09)

| id | code | name | type | filterable | PAV rows | unique raw strings | unique tokens after `,` split | multi-token rows |
|---|---|---|---|---|---|---|---|---|
| 1 | `color` | สี | text | yes | 939 | 137 | 38 | 164 |
| 2 | `gender` | เพศ | text | yes | 392 | 3 | 2 | 61 |
| 3 | `size_top` | Size (เสื้อ) | text | yes | 433 | 68 | 59 | 32 |
| 4 | `size_bottom` | Size (กางเกง) | text | yes | 201 | 59 | 51 | 16 |
| 5 | `age` | อายุ | text | yes | 427 | 36 | 21 | 51 |
| 6 | `condition` | สภาพ | text | yes | 605 | 5 | 5 | 0 |
| 7 | `attr_0ebb640bb9a7` | ภาษา | text | yes | 1 | 1 | 1 | 0 |
| 8 | `size` | Size (รองเท้า) | text | yes | 37 | 18 | 18 | 0 |

- `attribute_values`: **0 rows**
- PAV `attribute_value_id`: **all null**
- PAV `product_variant_id`: **all null** (not variation axes)
- `product_attributes.used_for_variations`: **all false**
- `product_attributes` membership: attributes 1–6 on all 1,370 products. **7 and 8 are not on the set and not on `product_attributes`**
- Products with zero PAV: **401**
- JSON / `meta` attribute blobs: **not used** for these eight
- `doc/products-woocommerce-template.csv` Attribute 1–4 columns are **empty**. Recovery reads **current `product_attribute_values.value`**, not that file

Size/age tokens are **not** a clean enum. Examples: `4-5 Y` vs `4-5Y`, `12-18 เดือน` vs `12-18M`, `5t` vs `5T`, ranges `29-31`, mixed letter sizes (`XS`…`XL`) and month codes (`12M`) on the same attribute. Forcing `select` on the raw set would lock that mess into `attribute_values`.

### 1.3 Why each surface fails

| Surface | Reads | Sees today |
|---|---|---|
| `/shop` facet chips | `attribute_values` + count > 0 | Eight legends, zero chips |
| Shop GET `?color=` | `attribute_values.code` | No matches |
| Admin Organization attributes | set members + `type=select` + `values[]` | Names with muted `text`, or missing ภาษา / Size (รองเท้า) |
| Search by attribute label | indexer `attributeValue` | Those tokens are not in `search_documents.payload.attributes` |
| Suggest | name/brand/category only | Unchanged, no bug |
| PDP spec list | one `{label, value}` per PAV via `first()` + string fallback | Comma blobs; after naïve split would show one token |

### 1.4 Write-path trap

If recovery ran first, the next WooCommerce upsert would delete product-level PAV and rewrite text-only rows. **Durable writers are therefore a prerequisite**, not a follow-up.

---

## 2. Approaches considered

**A — Shared normalizer + FK writer, then artisan recovery (recommended).** One tokenizer/canonicalizer used by import, `ProductService`, and the recovery command. Recovery copies PAV to backup tables, normalizes size/age, creates `attribute_values`, rewrites PAV, attaches missing set members, flips `type` to `select`. `--dry-run` / `--audit`. Rollback restores backups. No Laravel schema migration for this data.

**B — Laravel `migrate` data migration.** Weak dry-run, mixes catalog data with schema history. **Rejected.**

**C — Dual-read shop filters on `value` text.** Diverges from Catalog V2 SoT. **Rejected.**

**Decision:** A, with writers **before** recovery apply.

---

## 3. Proposed architecture

```text
Phase order (locked)
1. Durable writers     import + ProductService write attribute_value_id
2. Size/age audit      report raw vs canonical tokens (no type change)
3. Size/age normalize  rewrite PAV.value tokens in place (still text, still null FK)
4. Recovery apply      create attribute_values, link FKs, type=select, set attach
5. Workspace + PDP    checkbox specs; PDP values[] collection; hide empty facets
6. Reindex
```

These eight stay **specification** attributes (`used_for_variations = false`). Do not generate variant matrices from recovered sizes/colors.

### 3.1 Shared tokenizer (`AttributeTokenNormalizer`)

Used by writers **and** recovery. No second copy of split rules.

**Split (all eight attributes):**

1. NFC, trim the raw `value`.
2. Split on ASCII `,` and fullwidth `，`.
3. Trim tokens; drop empties.
4. Deduplicate within a product+attribute after canonicalize (Unicode case-fold).

Do **not** split on `/`, hyphen, or space (`26.5`, `3M`, `12เดือน` stay one token until size/age rules below). Do **not** merge condition synonyms (`Good` ≠ `สภาพดี`).

**Canonicalize — color, gender, condition, language:** trim + Unicode case-fold for **matching** only; stored **label** is the first-seen original casing after trim (first product id wins).

**Canonicalize — `size_top`, `size_bottom`, `size` (Size รองเท้า), `age` — before any `attribute_values` insert and before `type=select`:**

| Rule | Applies to | Example |
|---|---|---|
| NFC, trim, delete whitespace (including around `-`) | size_* + age | `4-5 Y` → `4-5Y`, `3-4 Y` → `3-4Y` |
| Latin letters in the token uppercased | size_* + age | `5t` → `5T`, `xs` → `XS` |
| Trailing `ปี` → `Y` | size_* only, not age | `2-3ปี` → `2-3Y` |
| Trailing `เดือน` → `M` | size_* only, not age | `12-18เดือน` → `12-18M` |
| Trailing `CM` stays `CM` | size_* | `90CM` → `90CM` |
| Trailing unit stays Thai | age only | `12เดือน`, `2ปี` (spaces already gone) |
| Ranges stay one token | all | `29-31`, `12-18M` |
| `ไม่มี` kept | all | not dropped |

Do **not** map `12M` on Size (เสื้อ) onto อายุ `12เดือน`. Different attributes.

`--audit` (or recover `--dry-run`) **must** print, per size/age attribute: distinct raw tokens, canonical tokens, count, and many-to-one collapses. Apply of `type=select` on those four is illegal if any remaining PAV for them still has `attribute_value_id` null **and** the stored `value` is not already a single canonical token (recovery’s normalize step rewrites first).

### 3.2 Attribute definitions

| Action | Rule |
|---|---|
| Existing eight | Reuse. Do **not** change `attributes.code`. ภาษา keeps `attr_0ebb640bb9a7`. |
| Missing definition | Create only if a covered name has PAV and no `attributes` row. |
| Type | `select` only **after** size/age canonicalize for codes `size_top`, `size_bottom`, `size`, `age`. Color / gender / condition / language may flip to `select` in the same apply once tokens are split (no extra alias table). `is_filterable` / `is_visible` stay true. |
| New attributes from import | `select` + `is_filterable`, never `text`. |
| Set membership | Attach ภาษา and Size (รองเท้า) to `woocommerce_default` at the end of the set. Do not remove 1–6. |
| `product_attributes` | Upsert membership for every product on that set (`ProductAttributeSetSync`), `used_for_variations = false`. |

### 3.3 Value codes

Reuse `AttributeValueService::allocateCode`. Match existing rows by attribute_id + NFC + case-fold **canonical label**. Codes assigned once. Thai slug → `value`, `value-2` is acceptable.

Position: first insert order, `products.id` ascending, tokens left-to-right.

### 3.4 Durable writers (before recovery)

`ProductService::syncAttributeValues` and the workspace path that currently writes text-only PAV (`syncProductAttributeValues` when `productAttributes === []`) must:

1. Run `AttributeTokenNormalizer` (split + canonicalize by attribute code).
2. Resolve-or-create `attribute_values` (label match + `allocateCode`).
3. Write one PAV per token with `attribute_value_id` (reuse `upsertProductAttributeValue` / `syncProductLevelValues`).
4. Attach newly seen attribute names to `woocommerce_default`.
5. Create new catalog attributes as `select` + `is_filterable`.

Importer keeps calling the same write path. CSV tests that only assert `$row->value === 'Blue'` must also assert `attribute_value_id` is non-null.

### 3.5 Linking existing rows (recovery apply)

After writers exist, the command for **already imported** rows:

1. Backup (see §4).
2. Normalize size/age `value` in place (still null FK).
3. Split remaining comma strings on all eight; ensure catalog values; insert FK rows; delete text-only rows for that product+attribute.
4. Flip `type` to `select` on all eight.
5. Attach ภาษา / Size (รองเท้า); set-sync `product_attributes`.
6. Reindex products touched.

Rows that already have `attribute_value_id` are left untouched (idempotent). Unique key: `(product_id, attribute_id, product_variant_id, attribute_value_id)`.

Preserve product ids, variants, SKUs, categories, media, prices.

### 3.6 Admin workspace

1. After recovery, set payload includes all eight as `select` with `values`.
2. Non-axis select uses **checkbox** (multi), not radio.
3. Do not mark recovered attributes Used for Variations.
4. `valueIds` already hydrate from `attribute_value_id`.

### 3.7 PDP spec collection

`ProductDetailData::$attributes` becomes:

```text
list<array{label: string, values: list<string>}>
```

One row **per attribute**, not per PAV.

- Non-axis: `values` = every product-level label for that attribute, `attribute_values.position` then label, **no implode**.
- Axis: `values` = the **selected variant’s** single label as a one-element list (picker still owns the rest).
- Blade: one `<dt>`, then `@foreach ($attribute['values'] as $value)` into list items (or multiple `<dd>`). **Forbidden:** `implode(', ', $values)` and a single concatenated string.

`visibleAttributes()` must not `first()` among spec PAV rows.

Existing unit tests that expect `['label' => 'Material', 'value' => 'Cotton']` change to `['label' => 'Material', 'values' => ['Cotton']]`.

### 3.8 Storefront filters and search

- Hide facet groups whose `values` is empty.
- Filter URL unchanged: `?color=` = `attribute_values.code`.
- Indexer already keys payload by **value** code (multi-color survives). Reindex after recovery apply/rollback.
- Suggest: **no change**.

---

## 4. Migration plan (operational)

Vehicle: `php artisan product:recover-attribute-values`

| Flag | Effect |
|---|---|
| `--audit` | Print size/age raw vs canonical; no writes |
| `--dry-run` | Print full plan (including color/gender/…) ; no writes |
| (apply) | Backup → normalize size/age → link all eight → `type=select` → set attach → reindex |
| `--force` | New backup suffix if dated table exists; never overwrite |

Covered names: สี, เพศ, Size (เสื้อ), Size (กางเกง), อายุ, สภาพ, ภาษา, Size (รองเท้า).

### 4.1 Backup (apply only)

- `_bak_product_attribute_values_attr_recovery_{Ymd}` 
- `_bak_attributes_attr_recovery_{Ymd}` (`id`, `type`)
- `_bak_attribute_set_attributes_attr_recovery_{Ymd}` (set 2)

Refuse clobber; `--force` writes a new suffix.

### 4.2 Apply order inside the command

1. Size/age canonicalize on PAV.value (text, null FK).
2. Tokenize + link all eight (§3.5).
3. `type=select` on all eight.
4. Set attach + `product_attributes` sync.
5. Reindex touched products.

Chunk by `product_id`. Do not leave a product with neither text row nor FK rows.

### 4.3 Idempotency

Second apply: no duplicate labels/codes; no duplicate PAV keys; type already `select`; set attach `updateOrCreate`. `--audit` / `--dry-run` never write.

### 4.4 Verification

| Check | Expect |
|---|---|
| Size/age audit | Collapses like `4-5 Y` and `4-5Y` share one canonical token |
| `attribute_values` | Distinct **canonical** tokens only |
| PAV null `attribute_value_id` for the eight | 0 |
| `/shop` | Chips; empty legends hidden |
| Admin | Eight select lists; multi สี checked |
| PDP | `values` array; two colors = two items, no comma string |
| Search | Label token finds the product |
| Suggest | Existing tests green |
| Taxonomy / nav | Unchanged |

---

## 5. Rollback plan

`php artisan product:restore-attribute-values {backup_suffix}`

1. Replace `product_attribute_values` from PAV backup.
2. Restore `attributes.type`.
3. Restore set-2 pivot.
4. Delete `attribute_values` created at/after the watermark (this catalog starts at 0).
5. Reindex all products.

Not `migrate:rollback`. Staff edits after recovery are lost on restore — document on the command.

---

## 6. Risk analysis

| Risk | Severity | Mitigation |
|---|---|---|
| Re-import wipes FKs | High | Writers **before** recovery |
| Size/age select freezes junk labels | High | Canonicalize + audit before `type=select` |
| Workspace radio collapses multi-color | High | Checkbox before staff edit recovered products |
| PDP comma string / `first()` | High | `values: list<string>` DTO + blade loop |
| Ugly ภาษา URL `attr_0ebb640bb9a7` | Low | Code immutable |
| `allocateCode` Thai collisions | Low | Label match first |
| Empty facet legends | Low | Hide `values === []` |
| Backup overwrite | Medium | Dated names; refuse clobber |

---

## 7. Impact matrix

| Area | Change | Notes |
|---|---|---|
| Import / ProductService | Yes, first | FK writes + shared normalizer |
| Recovery command | Yes | After writers |
| Storefront filters | Yes | After recovery (new imports may facet earlier) |
| Admin product edit | Yes | After `type=select` + checkbox |
| Product search | Yes, reindex | |
| Suggest | No | |
| PDP spec list | Yes | Collection; breadcrumb/badge untouched |
| Taxonomy / nav / images | No | |

---

## 8. Implementation phases

TDD. No catalog `--force` import. Shop category chrome is a later spec.

**Phase 1 — Durable writers.** Normalizer + FK linker. Importer and `ProductService` / text workspace path. Tests: CSV `สี` writes `attribute_value_id`; comma `สีฟ้า, สีเทา` → two PAV; `4-5 Y` on size_top stores canonical `4-5Y` with FK.

**Phase 2 — Size/age audit.** `--audit` output + unit tests for §3.1 table. No `type=select` yet.

**Phase 3 — Recovery command.** Backup, normalize, link, `type=select`, set attach, rollback, reindex. Idempotent second run.

**Phase 4 — Workspace + PDP + empty facets.** Checkbox; `ProductDetailData` `values` list; hide empty facet groups.

**Phase 5 — Verify** §4.4 on the live catalog copy.

---

## 9. Non-goals

- Taxonomy create/rename/move; recategorizing 436 leftovers
- Category Navigation V1, mega menu, shop category strip
- PDP breadcrumb or category badge
- Suggest Enhancement
- Stub JPEG recovery
- Merging `Good` / `Mint` / `สภาพดี`
- Changing `attributes.code`
- Used for Variations / generate matrix
- Dropping `ไม่มี` or inventing a size chart beyond §3.1
- Committing `doc/` CSV or using WooCommerce import as the recovery mechanism

---

## 10. Spec self-review

- Size/age cannot become `select` until canonicalize has run (same command, ordered steps).
- PDP is a collection (`values[]`), not `implode` and not `first()`.
- Writers are Phase 1; recovery is Phase 3.
- SoT remains Catalog V2 relations. Approach C rejected.
- Rollback is table restore.
