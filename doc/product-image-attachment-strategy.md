# Product image attachment strategy

**Status:** Discovery only. No code, database, or media changes in this pass.  
**Date:** 2026-09-09  
**Inputs:** current `product_media` write/read paths; `doc/products-image-mapping-report.md`; PPK `Images` column (source of that report).  
**Prerequisite:** products already exist, keyed by SKU. This document is only about *which* files attach, *in what order*, and *how* `product_media` rows should look.

Related: `doc/media-migration-plan.md` (storage and ingest). This document does not repeat the upload pipeline.

---

## 1. Current product media rules

There is **no featured-image column** on `products`. Featured vs gallery is entirely `product_media`.

### Schema

`product_media` (`modules/Product/database/migrations/2026_07_21_500002_create_product_media_table.php`):

| Column | Meaning |
|---|---|
| `product_id` | FK to `products.id`, cascade delete |
| `media_uuid` | UUID of a `media` row. **No FK** to `media` |
| `position` | Unsigned int, default 0 |
| `is_primary` | Boolean, default false |
| Unique | `(product_id, media_uuid)` — same file cannot be attached twice to the **same** product |

The same `media_uuid` **can** appear on many products.

`Product::media()` is `hasMany(ProductMedia)->orderBy('position')`.

### How primary is chosen (writes)

Every current writer uses the **same rule**:

1. Delete all existing `product_media` for the product.
2. Recreate from an ordered list of media UUIDs.
3. `position = 0, 1, 2, …`
4. **`is_primary = true` only when `position === 0`**. Every later row is `false`.

Writers:

| Writer | Ordered input |
|---|---|
| Admin workspace (`ProductWorkspaceSaveService::syncRelations`) | `media_uuids[]` form order. UI copy: “First image is the product cover.” Drag-to-reorder. |
| `ProductService::syncRelations` | Same array contract. |
| WooCommerce CSV (`WooCommerceProductImporter::linkProductImages` / `resolveMediaUuids`) | `Images` column, **comma-split, left to right**, then **first-occurrence unique**. First remaining UUID → primary. |

Primary is **not** chosen from filename, upload timestamp, or WordPress `_thumbnail_id`. It is **list order**.

If `is_primary` and `position` ever disagree (manual DB edit), **reads** prefer `is_primary`. Writers never leave them inconsistent.

### How gallery order is chosen (reads)

| Surface | Order |
|---|---|
| Eloquent `Product::media` | `position` ascending |
| Admin list thumbnail | `is_primary` row, else first loaded row (relation is already position-sorted) |
| Storefront PDP gallery (`ProductDetailBuilder`) | Sort key: primary first, then `position`. Dedupe by resolved URL. Then append variant `meta.image_media_uuid` not already in the gallery. |
| Storefront cards (`ProductCardMapper`) | Same primary-then-position; first two URLs |
| Cart, wishlist, POS, barcode | `is_primary` else first row |
| CSV export (`WooCommerceProductExporter::resolveImages`) | Iterate `media` (position order); comma-join URLs. First exported URL is therefore the cover if writes stayed consistent. |

**Not used for order:** filename, filesystem mtime, WordPress size suffix, UUID lexicographic sort.

### Variant images

PPK mapping is **product-level** only. Variant cover (`variants.meta.image_media_uuid`) is out of scope for this migration. Do not invent per-variant attaches from the report.

### Zero images today

No `product_media` rows. Storefront/admin then use `product.fallback_image_media_uuid` (global setting), not a per-SKU placeholder.

---

## 2. Mapping report structure

File: `doc/products-image-mapping-report.md`.

Built by `doc/enrich-woocommerce-csv.py`: for each **template** SKU, parse PPK `Images` with `https?://[^,\s]+` **in URL order**, map each URL’s path after `/uploads/` to `doc/uploads/{rel}`, keep the file only if it exists.

### Counts (verified against PPK + `doc/uploads`, 2026-09-09)

| Fact | Value |
|---|---|
| Template SKUs with ≥1 local file | **1011** |
| URL hits that exist on disk (including repeats) | **2516** |
| Distinct files in that set | **2497** |
| Matched SKUs with empty PPK `Images` | **358** |
| URLs in PPK that did not exist locally | **0** |
| Gallery size | min **1**, max **9**, mean **2.49** |
| SKUs with exactly 1 file | **411** |
| SKUs with 2–9 files | **600** |
| SKUs with ≥10 files | **0** |

Distribution of listed files per SKU: 1→411, 2→76, 3→310, 4→119, 5→47, 6→33, 7→8, 8→5, 9→2.

### One image vs many

Most SKUs have a gallery (600 of 1011). 411 are single-image. After attach, a single-image product has one `product_media` row: `position=0`, `is_primary=true`. That row is both cover and the only gallery item.

### Can order be reconstructed?

**Yes.** The report’s “Proposed files” list is **left-to-right PPK `Images` order**, not a filename sort.

Evidence:

- Script appends locals in URL-find order (`proposed_image_mapping`).
- Regex order matches comma-split HTTP URLs on this PPK (0 mismatches).
- Filenames are **not** a usable sort. Example SKU `100015`: `S__3162129.jpg`, then `S__3162127.jpg` (numeric filename would reverse 2127/2129). UUID-style names are unordered.

The markdown table lists SKUs in **template CSV row order**, not SKU numeric order. That only affects reading the report, not per-SKU gallery order.

### Does the report preserve WooCommerce gallery order?

**Yes, for this dataset.** WooCommerce product CSV `Images` is a comma-separated URL list. The **first URL is the featured image**; later URLs are the gallery, in that sequence.

Commerce-framework already treats that the same way (`extractImageUrls` = `explode(',', $raw)`). The mapping report used a URL regex on the same string; order matched.

Caveats:

- The report **does not unique** paths. **7 SKUs** repeat the same relative path in the list (e.g. `300049` lists the same `-scaled.jpeg` twice). CF **cannot** store that twice (`unique(product_id, media_uuid)`). First occurrence wins; later duplicates are dropped. That matches `linkProductImages` / `extractImagePaths`.
- WordPress `-NNNxNNN` thumbs that were **not** in the CSV URL list are **not** in the report. Do not add them. Commerce-framework generates its own WebP variants.
- Many first images are `-scaled` files (**313** of 1011). That is the file WooCommerce referenced. Do not swap in a guessed “original” without `-scaled`.

---

## 3. Recommended migration attach logic

Reuse existing write semantics. Do not add a second notion of featured image.

### Primary image

**The first remaining file in the mapping list, after per-SKU unique-by-path, becomes primary.**

```text
is_primary = (position === 0)
```

That is:

- WooCommerce featured image (first `Images` URL)
- Admin “first image is the product cover”
- Importer `position === 0`

Do **not** pick primary by:

- smallest filename
- “hero” in the name
- largest pixel size
- first file that is not `-scaled`

### Gallery order

Walk the mapping list **in report/PPK order**. After skipping duplicates and missing/corrupt files, assign:

```text
position = 0, 1, 2, … (contiguous)
is_primary = true iff position == 0
```

Later rows are gallery. Storefront will show primary first, then these positions. Export will emit the same sequence.

If a later file fails, **do not leave a hole** (`position` 0, 2 with 1 missing). Re-number surviving files 0..n-1 so the first **successful** file stays cover.

### Duplicate files (same path on one SKU)

**7 SKUs** list the same relative path twice.

**Action:** unique **preserving first index**. One `product_media` row. Log a per-SKU warning (`duplicate_path_skipped`). Do not fail the SKU.

### Shared files (same path on multiple SKUs)

**12 distinct files** are referenced by two SKUs each (24 product attachments). Examples: `300023`/`300060`, `300005`/`300061`, `100080`/`100084`.

**Action:** ingest the bytes **once**. Reuse one `media` UUID (stamp `meta.wordpress_path`). Create a **separate** `product_media` row per product, each with that product’s own `position` / `is_primary`.

Do **not** copy the file per SKU. Do **not** make SKU B’s cover depend on SKU A’s `position`. Shared media, independent pivots.

If SKU A’s first URL is a shared file and SKU B’s third URL is the same file: A gets `is_primary=true` for that UUID; B gets `is_primary=false` unless it is also B’s first remaining file.

### Missing source files

The current mapping has **zero** missing locals. A future run must still:

1. Skip the path.
2. Continue the SKU with remaining files.
3. If **no** files survive, leave the product with **zero** `product_media` (fallback setting). Log `missing_file`.
4. Do not attach a broken UUID.

### Corrupt / unreadable files

Treat like missing after persist or variant generation fails (GD/`getimagesize` fails). Skip that file, continue, log `corrupt_file`. If it was going to be position 0, the next surviving file becomes primary.

---

## 4. How `product_media` rows should be created

For SKU `300058` (six distinct files in report order):

| position | is_primary | media_uuid |
|---|---|---|
| 0 | true | UUID of `2021/03/Image-from-iOS-187.jpg` |
| 1 | false | UUID of `…-189.jpg` |
| 2 | false | … |
| 3 | false | … |
| 4 | false | … |
| 5 | false | UUID of `…-193.jpg` |

Rules:

1. Resolve product by SKU (`product_variants.sku`).
2. Build ordered unique relative paths from the mapping (or regenerate from PPK `Images` the same way).
3. For each path: get-or-create `media` (shared-file reuse).
4. **Do not** call current `linkProductImages` (wipes gallery; `registerExistingFile` missing). Insert pivots the same way `syncRelations` does, but **do not delete** other SKUs’ rows; on `--skip-existing`, skip SKUs that already have any `product_media`.
5. On `--force`, delete **that product’s** `product_media` only, then recreate from the full surviving list (so primary/position stay consistent).
6. Unique `(product_id, media_uuid)` is the last guard; do not insert a second row for the same pair.

Expected volume if every mapped file attaches after intra-SKU unique:

- **~2509** `product_media` rows (2516 listed minus 7 intra-SKU duplicates)
- **~2497** `media` rows if shared files are reused
- **358** products with **no** rows

---

## 5. Edge-case handling

| Case | Count / note | Recommendation |
|---|---|---|
| 0 images | 358 SKUs + SKU `110092` (no PPK match) | Create **no** `product_media`. Rely on `product.fallback_image_media_uuid`. Do not invent a stock photo per SKU. |
| 1 image | 411 | One row, `position=0`, `is_primary=true`. Gallery is a single item. |
| Many images | max 9 | Keep PPK order. No cap in current schema. |
| Same path twice on one SKU | 7 SKUs | Unique first-wins. Warning only. |
| Same path on two SKUs | 12 files | One `media` row; two pivots. |
| Missing file at attach time | 0 in current mapping | Skip file; re-number; maybe 0-image product. |
| Corrupt file | unknown until ingest | Skip file; do not abort the whole run. |
| Partial gallery after failures | — | First success is primary. Log which positions dropped. |
| Product already has media | unknown until runtime | `--skip-existing` default; `--force` replace **that SKU only**. |
| Filename looks sequential (`S__3162…`) | some SKUs | Ignore filename. Trust URL order. |
| `-scaled` / `-rotated` in mapping | common | Ingest **that** file. Do not substitute a sibling original. |
| Extra files under `doc/uploads` | 76,911 total | Ignore. Not in the URL list. |

---

## 6. WooCommerce compatibility

| WooCommerce | Commerce-framework |
|---|---|
| `Images` first URL = featured | `position=0` + `is_primary=true` |
| Remaining URLs = gallery order | `position` 1..n-1, `is_primary=false` |
| Duplicate URL in the same cell | Unique first-wins (importer already) |
| Same attachment on two products | Allowed; CF unique is per product |
| Export `Images` | Position order, comma-separated |

Future CSV export after this attach will list CF/public URLs (or `meta.source_url` if stamped). Order will match the mapping if rows are written as above.

Do **not** fill the enriched CSV `Images` column with live PPK URLs and re-run HTTP import. Attachment order would match, but that path is the wrong ingest (see media migration plan).

---

## 7. Open questions

These do not block the ordering rules above.

1. **Parser source for the future command:** parse the markdown table, or regenerate SKU→paths from PPK `Images` + `doc/uploads` (same algorithm as the report)? Regenerating is less brittle than scraping HTML `<br>` lists.
2. **Resume vs wipe:** if a SKU has 3 of 6 images from a crashed run, is `--skip-existing` (leave partial) or `--force` (rebuild full order) the operator default? Ordering is only correct after a full rebuild of that SKU’s pivots.
3. **Alt text:** storefront uses product name, not filename. Confirm no per-image alt from PPK.
4. **358 empty SKUs:** leave empty forever, or a later manual pass? Not an ordering question.
5. **Shared-file rollback:** deleting `media` for a shared file removes it from both SKUs. Rollback must be path- or batch-scoped, not “delete all media used by SKU A”.

---

## 8. Success criteria (locked for a future implementation)

1. **Primary** = first unique, existing, ingestible file in PPK/`Images` (mapping) order.
2. **Gallery order** = that list, unique-preserving-first, re-numbered after skips. Not filename sort.
3. **Rows:** one `product_media` per (product, media UUID); `is_primary` only on `position` 0; shared files share `media` UUID.
4. **0-image products** stay empty (fallback setting).
5. **Behavior** matches admin save and the WooCommerce importer’s intended `Images` order — without using the broken `--link-images` path as-is.
