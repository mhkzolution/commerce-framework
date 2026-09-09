# Post-import verification

**Status:** Read-only. No code changes, media import, or commits.  
**Date:** 2026-09-09  
**Database:** `commerce_framework` (`127.0.0.1:8890`)  
**CSV:** `doc/products-woocommerce-enriched.csv`  
**Compared to:** `doc/product-import-verification.md` (pre-import: empty catalog), `doc/products-image-mapping-report.md`  
**CLI source:** `php artisan product:import-woocommerce doc/products-woocommerce-enriched.csv` (exit 0)

---

## Verdict

**GO**

The enriched CSV is fully present in `commerce_framework`. No product has media. The catalog is ready for the **image migration phase** (attach still must use the locked local-file plan, not `--link-images`).

---

## Verdict table

| Check | Expected | Catalog | Match? |
|---|---|---|---|
| 1. Total products (live) | 1370 | **1370** | Yes |
| 2. Total SKUs (distinct, live) | 1370 | **1370** | Yes |
| 3. SKU `110092` | present | **Yes** (draft, simple) | Yes |
| 4. Products with media | 0 | **0** | Yes |
| 5. `product_media` count | 0 | **0** | Yes |
| 6. `media` count (live) | 0 | **0** | Yes |
| 7. Mapped SKU coverage | 1011 | **1011 / 1011** | Yes |
| 8. Import summary | Created 1370, rest 0 | **Matches CLI** | Yes |

Pre-import (`doc/product-import-verification.md`) was 0 products / 0 SKUs. That gap is closed.

---

## Catalog counts

| Metric | Count |
|---|---|
| Products live / deleted | **1370 / 0** |
| Variants live | **1370** |
| Distinct SKUs | **1370** |
| Duplicate SKUs in DB | **0** |
| CSV SKUs missing from catalog | **0** |
| DB SKUs not in CSV | **0** |
| `type = simple` | **1370** |
| `status = published` | **1369** |
| `status = draft` | **1** (`110092`) |
| Products with `product_media` | **0** |
| Products without media | **1370** |
| `product_media` rows | **0** |
| `media` live / all | **0 / 0** |
| Created window | `2026-09-09 06:22:41` → `06:23:54` UTC (~73 s) |

---

## SKU `110092`

Present. Not in the 1011 mapped image SKUs.

| Field | Value |
|---|---|
| SKU | `110092` |
| Name | `110092` |
| Status | `draft` |
| Type | `simple` |
| Slug | `110092-110092` |
| Media | none |

It will stay imageless in the image phase. That is expected.

---

## Mapped SKU coverage

From `doc/products-image-mapping-report.md`: **1011** SKUs with local files.

| Metric | Count |
|---|---|
| Mapped SKUs in catalog | **1011** |
| Mapped SKUs missing | **0** |
| Mapped SKUs that already have media | **0** |
| CSV SKUs with no mapping (no image URL + `110092`) | **359** |

`--skip-existing` on a future attach would skip **0** products today.

---

## Import summary (CLI)

Captured from the completed artisan run (exit 0):

| Metric | Count |
|---|---|
| Created | **1370** |
| Updated | **0** |
| Skipped | **0** |
| Warnings | **0** |
| Images linked | **0** |
| Errors | **0** |

The command printed the `public/wp-content/uploads/` / `--link-images` hint. **Do not follow it.** Images column is empty; that path is still broken.

---

## Image migration targets (next phase)

| Item | Count |
|---|---|
| SKUs eligible to attach | **1011** |
| Distinct files to ingest | **2497** |
| `product_media` rows after attach | **~2509** (2516 listed minus 7 intra-SKU duplicate paths) |
| SKUs that stay without images | **359** (358 no URL + `110092`) |
| `--skip-existing` would skip now | **0** |
| `--force` would rebuild now | **0** |

Do not HTTP-import. Do not `--link-images`. Use `doc/media-migration-plan.md` + `doc/product-image-attachment-strategy.md`.
