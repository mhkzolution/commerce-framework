# Product import verification

**Status:** Read-only. No code changes, media import, or commits.  
**Date:** 2026-09-09  
**Database:** Laravel `mysql` → `commerce_framework` (`127.0.0.1:8890`)  
**CSV:** `doc/products-woocommerce-enriched.csv`  
**Mapping:** `doc/products-image-mapping-report.md`

---

## Verdict

**NO-GO**

The enriched CSV is valid and ready to import. It has **not** been imported into `commerce_framework`. Image migration must not start.

---

## Verdict in one table

| Check | CSV / mapping | Catalog | Match? |
|---|---|---|---|
| 1. Total products | — | **0** | No |
| 2. Total SKUs (distinct, live) | **1370** | **0** | No |
| 3. Imported SKUs (CSV SKUs found in DB) | 1370 | **0** | No |
| 4. Missing SKUs (in CSV, not in DB) | — | **1370** | Fail |
| 5. Duplicate SKUs | CSV: **0** | DB: **0** | CSV OK; DB empty |
| 6. Products with existing media | — | **0** | Empty catalog |
| 7. Products without media | — | **0** (no products) | Empty catalog |
| 8. Product count vs CSV row count | **1370** rows | **0** products | No |
| 9. Product variants | expect ~1370 (all simple except 1 empty Type) | **0** | No |
| 10. Import warnings | No import run recorded | — | No result to inspect |

---

## CSV file (source of truth for an import)

| Metric | Count |
|---|---|
| Rows | **1370** |
| Unique SKUs | **1370** |
| Empty SKU rows | **0** |
| Duplicate SKUs in file | **0** |
| `Type=simple` | **1369** |
| `Type` empty | **1** (`110092`) |
| Rows with `Images` filled | **0** (intentional) |

`110092` is the enrichment miss (no PPK row). Name/Type/Published are empty in the enriched file.

### Mapping overlap

| Metric | Count |
|---|---|
| Mapped SKUs (local files) | **1011** |
| Mapped SKUs present in CSV | **1011** (all of them) |
| CSV SKUs not in mapping | **359** = **358** no PPK image URL + **`110092`** |

---

## Catalog (this database)

| Metric | Count |
|---|---|
| Products (live / deleted) | **0 / 0** |
| Variants (live) | **0** |
| Distinct SKUs | **0** |
| `product_media` | **0** |
| Live `media` | **0** |
| CSV SKUs in catalog | **0** |
| Mapped SKUs in catalog | **0** |
| DB SKUs not in CSV | **0** |
| Duplicate SKUs in DB | **0** |

No import summary (created / updated / skipped / warnings / errors / images) exists in the database or in `doc/` for this file. There is nothing to treat as import warnings because **the import did not run here**.

---

## Blockers

1. **Products were not imported** into `commerce_framework`. All 1370 CSV SKUs are missing.
2. **Image attach has no products to join** (1011 mapped SKUs missing).
3. Re-run this verification after:

   ```text
   php artisan product:import-woocommerce doc/products-woocommerce-enriched.csv
   ```

   Do not fill `Images`. Do not `--link-images`. Then confirm products = 1370 (or 1369 if `110092` is skipped for empty identity — see open note below).

### Open note (does not change NO-GO)

SKU `110092` has empty Name and Type. Importer requires ID, SKU, or Name. SKU is present, so the row may still create. Confirm after import whether that one product exists.

---

## Expected counts after a successful import (not current)

These are the image-migration targets **once** this verification is GO:

| Phase | Count |
|---|---|
| Products / SKUs to have in catalog | **1370** (or 1369 if `110092` skipped) |
| Eligible for image attach | **1011** mapped SKUs |
| Distinct files to ingest | **2497** |
| `product_media` rows after attach | **~2509** |
| SKUs that stay imageless | **358** (+ `110092` if it exists) |
| `--skip-existing` after a clean CSV import (Images empty) | **0** |

Until the catalog matches the CSV, image migration stays **NO-GO**.
