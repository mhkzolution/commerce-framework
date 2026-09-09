# Product import execution checklist

**Status:** Documentation only. This file does not run the import.  
**Date:** 2026-09-09  
**Target DB:** `commerce_framework` (Laravel `.env` `DB_DATABASE`)  
**File:** `doc/products-woocommerce-enriched.csv`  
**Catalog today:** 0 products, 0 SKUs, 0 `product_media` (`doc/product-import-verification.md`)

This checklist is for **product CSV import only**. Image migration stays NO-GO until post-import verification passes.

---

## GO / NO-GO (this command)

**GO** — execute the product import against `commerce_framework`.

Preconditions already met:

- CSV exists, 1370 unique SKUs, 0 in-file duplicates, `Images` empty
- Target catalog is empty (nothing to overwrite)
- `--link-images` will not be used

Do **not** start media attach until the post-import checks in §9–10 pass.

---

## 1. Expected command

From the repo root. CLI `max_execution_time` is 0 on this PHP (no HTTP timeout).

**Dry-run first (no writes):**

```bash
php artisan product:import-woocommerce doc/products-woocommerce-enriched.csv --dry-run
```

**Real import:**

```bash
php artisan product:import-woocommerce doc/products-woocommerce-enriched.csv
```

| Flag | Use? | Why |
|---|---|---|
| *(none)* | **Yes** | `skipExisting` is on. Empty DB → create all rows. Safe resume: skipped SKUs stay, missing SKUs still create. |
| `--dry-run` | **Yes, once first** | No attribute set, no products. Confirms the file parses. |
| `--force` | **No** (first run and resume) | Would update every already-imported SKU. Not needed on an empty catalog. |
| `--limit=` | Optional smoke test only | e.g. `--limit=5` then rollback those rows, or leave them and resume without `--force`. |
| `--link-images` | **Never** for this file | Images column is empty; `registerExistingFile` is missing; disk `wordpress_uploads` is not configured. |

The command will still print a `public/wp-content/uploads/` hint. **Ignore it.** Do not copy the 76k dump there and do not re-run with `--link-images`.

Admin UI import of the same file is equivalent, but CLI is the path this checklist specifies.

---

## 2. Expected runtime

1370 standalone simple rows (empty `Type` is still standalone). Each create:

- workspace persist + default variant + stock
- categories/tags/attributes as needed
- **synchronous** search index (`SyncProductSearchIndex` on create; published rows also on publish)

No HTTP media download (`Images` is empty). No GD.

**Estimate:** about **5–20 minutes** on this machine. Budget **~20 minutes**. If it stops at a row, resume with the **same command without `--force`**.

---

## 3. Expected summary counts

CLI table after a clean first run (empty catalog, no `--force`):

| Metric | Expected |
|---|---|
| Created | **1370** |
| Updated | **0** |
| Skipped | **0** |
| Warnings | **0** |
| Images linked | **0** |
| Errors | **0** |

Dry-run uses the same created/imported increments **without writing**. Attribute-set creation is skipped on dry-run.

Reserved search attribute codes (`q`, `category`, `brand`, `sort`, `availability`, `price_min`, `price_max`, `page`): **0** events in this CSV → warnings stay 0.

In-file duplicate SKUs: **0** → no duplicate skips.

Exit code: **0** if `errors === 0`. Any `errors > 0` → command returns failure; already-created rows remain (no all-or-nothing transaction).

---

## 4. Expected created products

| Item | Expected |
|---|---|
| `products` (live) | **1370** |
| `product_variants` (live) | **1370** (one default variant per simple product) |
| Distinct SKUs | **1370** |
| `type = simple` | **1370** (importer hardcodes simple for standalone rows, including `110092`) |
| `status = published` | **1369** (`Published=1`) |
| `status = draft` | **1** (`110092`, empty Published) |
| `product_media` | **0** |
| `media` | **0** |

Attribute set `woocommerce_default` / “WooCommerce Default” is created on first real run if missing (this DB already has 1 `attribute_sets` row; importer reuses it if the code matches).

Categories: **5** distinct `Categories` cells in the CSV; missing names are created. Brands column is empty → no brands required.

---

## 5. Expected skipped rows

**0** on a first run against an empty catalog.

Rows would skip only if:

- SKU already exists and `--force` is off
- in-file duplicate SKU (none here)
- unsupported `Type` that is non-empty and not `simple`/`variable` (none here)

Empty `Type` is **not** skipped; it is treated as a standalone simple product.

---

## 6. Expected warnings

**0** counted warnings.

Console may still show:

- `Created attribute set: WooCommerce Default` (info, not the Warnings counter)
- Per-row `Imported: …` lines (1370 lines)

No reserved-attribute skip messages for this file.

---

## 7. Handling of SKU `110092`

| Field in CSV | Value | Importer behavior |
|---|---|---|
| SKU | `110092` | Row is eligible (`ID`/`SKU`/`Name`: SKU is set) |
| Name | empty | Stored as **`Untitled product`** |
| Type | empty | Standalone simple (not skipped) |
| Published | empty | **`draft`** (only `'1'` is published) |
| Visibility | empty | **`public`** |
| Images | empty | No media |
| PPK match | none | Not in the 1011 mapped SKUs |

Slug: `Str::slug` of empty name + SKU → **`110092`**.

**Do not treat this SKU as an import failure.** It is a known enrichment miss. It will not receive images later. Optional follow-up: rename or delete after import; not a blocker for the other 1369.

---

## 8. Post-import verification queries

Read-only. Run against `commerce_framework`.

```sql
SELECT COUNT(*) AS products_live FROM products WHERE deleted_at IS NULL;
SELECT COUNT(*) AS variants_live FROM product_variants WHERE deleted_at IS NULL;
SELECT COUNT(DISTINCT sku) AS distinct_skus
  FROM product_variants
  WHERE deleted_at IS NULL AND sku IS NOT NULL AND sku <> '';
SELECT status, COUNT(*) AS n FROM products WHERE deleted_at IS NULL GROUP BY status;
SELECT COUNT(*) AS product_media FROM product_media;
SELECT COUNT(*) AS media_live FROM media WHERE deleted_at IS NULL;

SELECT COUNT(*) AS sku_110092
  FROM product_variants
  WHERE deleted_at IS NULL AND sku = '110092';

SELECT p.name, p.status, p.type, p.slug
  FROM products p
  JOIN product_variants pv ON pv.product_id = p.id
  WHERE pv.sku = '110092' AND pv.deleted_at IS NULL;
```

Pass when:

- products = **1370**, variants = **1370**, distinct SKUs = **1370**
- published = **1369**, draft = **1**
- `product_media` = **0**, `media` = **0**
- `110092` exists as Untitled / draft / simple
- CLI Errors = **0**

Then update `doc/product-import-verification.md` with live counts (separate pass).

---

## 9. Rollback options

The importer does **not** wrap the file in one DB transaction. A crash leaves a prefix of products.

| Situation | Action |
|---|---|
| Dry-run only | Nothing to roll back |
| Failed mid-file | Re-run **without `--force`**. Existing SKUs skip; remaining SKUs create. |
| Want a clean retry on this empty shop | Soft-delete or hard-delete **all** `products` (variants cascade). Categories, tags, attributes, attribute set **remain**. Then import again. |
| `--force` after a partial run | Updates every matching SKU (rewrites workspace). Avoid unless you intend a full upsert. |
| Media | None created; no media rollback |

Do **not** `migrate:fresh` unless you explicitly want to wipe the whole app schema/seed.

There is no import-batch id. Identification of this run is “all current products” while the catalog was empty beforehand, plus `products.meta.wordpress_id` from the CSV `ID` column.

---

## 10. Conditions before image migration becomes GO

Image attach is a **second** step. It becomes GO only when **all** of these hold:

1. Product import CLI: **errors = 0**, created **1370**, images linked **0**
2. Catalog: **1370** live products / SKUs matching the CSV
3. Mapped SKUs in catalog: **1011**
4. `product_media` still **0** (no HTTP/admin attach in between)
5. Attach command implemented per `doc/media-migration-plan.md` (not this import)
6. `--link-images` was **not** used

Then expected image-phase volume (locked): **2497** `media` rows, **~2509** `product_media`, **358** SKUs remain imageless (+ `110092`).

---

## Operator sequence

```text
1. php artisan product:import-woocommerce doc/products-woocommerce-enriched.csv --dry-run
   → Created 1370, Errors 0, no DB change
2. php artisan product:import-woocommerce doc/products-woocommerce-enriched.csv
   → Created 1370, Skipped 0, Warnings 0, Images 0, Errors 0
3. Run §8 SQL
4. If SQL matches → product import GO (done)
5. Image migration still NO-GO until §10
```

---

## GO / NO-GO

| Action | Verdict |
|---|---|
| Run product CSV import now | **GO** |
| Run `--link-images` | **NO-GO** |
| Run media attach now | **NO-GO** (no products until step 2 finishes; command not in this checklist) |
