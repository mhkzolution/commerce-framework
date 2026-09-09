# Media migration preflight audit

**Status:** Read-only audit. No code, database writes, imports, or commits.  
**Date:** 2026-09-09  
**Database audited:** Laravel default `mysql` → `commerce_framework` on `127.0.0.1:8890`  
**Locked inputs:** `doc/media-migration-plan.md`, `doc/product-image-attachment-strategy.md`, `doc/products-image-mapping-report.md`

This pass validates those documents against **this app’s current catalog**. Architecture and ordering rules are unchanged.

---

## Go / no-go

| Question | Result |
|---|---|
| Is the catalog ready for image attach? | **No.** 0 products, 0 SKUs. |
| Do existing media create conflicts? | **No collisions.** There is no product media to skip or rebuild. |
| Is rollback design still valid? | **Yes**, with the shared-file hazard already in the plan. |
| Should media migration run now? | **NO-GO.** |
| After products exist, is the approach still the one to use? | **Yes** (conditional GO). Import `products-woocommerce-enriched.csv` first (Images empty), then re-count. Expect `--skip-existing` = 0 if nobody attaches images in between. |

**Do not ingest media until mapped SKUs exist as products.** The attach command is also not implemented yet; this audit does not treat that as a catalog defect.

---

## 1. Product readiness

Live rows (`deleted_at IS NULL` where the column exists):

| Metric | Count |
|---|---|
| Products (all / not deleted / deleted) | **0 / 0 / 0** |
| Product variants | **0** |
| Distinct SKUs | **0** |
| Products with no `product_media` | **0** (no products) |
| Products with media | **0** |
| Template CSV SKUs | **1370** |
| Template SKUs present in catalog | **0** |
| Mapped SKUs (report / regen) | **1011** |
| Mapped SKUs present in catalog | **0** |
| Mapped SKUs missing from catalog | **1011** |

Related seed (not a product catalog):

| Table | Count |
|---|---|
| `attribute_sets` | 1 |
| `media_tags` | 5 |
| `categories` / `brands` / `tenants` | 0 |

SKU `110092` (template, no PPK row) is also absent; it was never in the 1011 mapped set.

**Readiness:** catalog is **not ready**. Product CSV import is a hard prerequisite.

---

## 2. Media readiness

| Metric | Count |
|---|---|
| `media` (all / live / soft-deleted) | **0 / 0 / 0** |
| `media_variants` | **0** |
| `product_media` | **0** |
| Distinct `media_uuid` on products | **0** |
| `product_media` with `is_primary=1` | **0** |
| `product_media` with `is_primary=0` | **0** |
| Live `media` not referenced by `product_media` | **0** |
| `product_media` whose `media_uuid` is missing or soft-deleted | **0** |
| `product_media` on a deleted product | **0** |
| `media.meta.migration = ppk-images` | **0** |

Schema (unchanged, confirmed on this database):

- `media.meta` is JSON; `caption` / `description` exist; soft deletes on.
- `product_media` unique `(product_id, media_uuid)`.
- FK `product_id` → `products.id` only. **No FK** from `product_media.media_uuid` to `media.uuid`.

**Anomalies:** none in media data (empty). Empty library is expected before first ingest.

Setting `product.fallback_image_media_uuid` exists and is **null**. The 358 SKUs with no PPK image URL will have no cover after product import until that setting is set or images are added later.

PHP on this machine: GD **yes**, `imagewebp` **yes**, `memory_limit=128M`, CLI `max_execution_time=0`.

---

## 3. Collision analysis

Among the 1011 mapped SKUs **in this catalog**:

| Check | Count |
|---|---|
| Mapped SKU already has any `product_media` | **0** |
| Mapped SKU already has a primary row | **0** |
| Mapped SKU already has gallery (`is_primary=0`) rows | **0** |
| Existing `product_media` rows on mapped products | **0** |

| Flag | Estimate **today** |
|---|---|
| `--skip-existing` would skip | **0** SKUs (none exist) |
| `--force` would rebuild | **0** SKUs |

After a clean enriched-CSV import **without** filling `Images` and without `--link-images`:

- Expected collisions remain **0**.
- `--skip-existing` would skip **0**; all **1011** mapped SKUs would be eligible.
- `--force` would be unnecessary on a first attach.

If someone later HTTP-imports Images or uses admin upload on those SKUs, skip-existing would skip that subset; `--force` would replace **that product’s** pivots only. Re-run this audit after product import before attaching.

---

## 4. Shared media impact

Documented: **12** files, each used by **two** SKUs; **2497** distinct files → **~2509** pivots after intra-SKU unique.

| Layer | Shared UUID supported? | Evidence |
|---|---|---|
| Schema | **Yes** | Unique is `(product_id, media_uuid)`, not global `media_uuid`. |
| Storefront | **Yes** | PDP/cards resolve URL by UUID per product. In-product gallery dedupes by **URL**, not by blocking reuse on another product. |
| Admin | **Yes** | Picker attaches UUIDs; first in `media_uuids[]` is cover **for that product**. Same UUID can appear in two products’ lists. |
| Export | **Yes** | Each product emits its own position-ordered URLs. Two CSV rows may share the same URL. |
| Usage / delete | **Yes, with a hazard** | `MediaUsageService` lists every `product_media` row for that UUID. Non-force delete is blocked if any product still references it. |

**Risks (design, not current data):**

1. Rollback by `meta.migration` deletes **one** `media` row used by two SKUs — both lose that image. Correct for a full PPK rollback; wrong if only one SKU should be reverted.
2. Admin later attaching the same UUID to a **non-PPK** product keeps `meta.migration`. A later PPK rollback would still delete that file. Do not recycle stamped media for unrelated products, or clear the stamp after a successful keep.
3. Storefront dedupe is per product by URL. Shared files do not collapse two products into one gallery.

No current shared-UUID rows exist (library empty).

---

## 5. Rollback validation

Proposed stamp: `media.meta.migration = ppk-images` plus optional `batch`.

| Check | Verdict |
|---|---|
| Can `meta` store migration tags? | **Yes.** JSON column, Laravel array cast. `JSON_EXTRACT(meta, '$.migration')` already runs on this MySQL. Crop and other keys can coexist if updates **merge** meta instead of replacing the whole object blindly. |
| Can rollback target only migration-created media? | **Yes**, if every ingest path stamps `migration` (and `batch`). Do **not** use the seed `media_tags` “Products” tag as the selector (5 tags exist; that tag is library taxonomy, not a batch id). |
| Target after attach | `WHERE JSON_UNQUOTE(JSON_EXTRACT(meta,'$.migration')) = 'ppk-images'` (optional `AND batch = …`). Expected ~2497 rows if reuse is implemented. |
| Order of rollback | Delete matching `product_media` first, then `MediaService::delete($uuid, force: true)` so usage does not block. Soft-delete `media`; files on the public disk removed. Leave `doc/uploads` alone. |
| Shared-file hazard | **Real.** One media UUID → two products. Rollback-all-PPK is safe. Per-SKU rollback must **not** delete shared `media` while the other SKU still needs it: drop that product’s pivot only; delete `media` only when no `product_media` (and other usage_sources) remain. |
| Orphan risk | No FK on `media_uuid`. Deleting `media` before pivots leaves orphan UUIDs on products. Keep the documented order. |
| `--force` re-attach | Must re-stamp or reuse the same UUID via `wordpress_path` so rollback still finds rows. Avoid creating untagged duplicates. |

**Rollback is safe enough for a tagged batch**, provided operators treat shared files as **media-scoped**, not “undo SKU A only by deleting the file.”

---

## 6. Runtime estimate

Assumptions from locked docs, confirmed on disk for this mapping:

| Item | Figure |
|---|---|
| Distinct files to ingest (reuse shared) | **2497** `media` rows |
| Pivots after intra-SKU unique | **~2509** `product_media` rows |
| Mapped SKUs | **1011** |
| Source bytes (`doc/uploads` mapped files) | **~1.75 GB** (1,835,802,248 bytes) |
| Largest file | **~5.1 MB** (none > 10 MB) |
| Median file | **~0.46 MB** |

### Time

Pipeline is still **synchronous**: copy original + 3 GD WebP variants in-process. No media queue today.

Order-of-magnitude on this laptop class (GD + WebP yes):

- ~0.5–2 s per file → **~20–80 minutes** for 2497 files
- Plan’s “tens of minutes” still holds; budget **~1 hour**, plus logging

CLI `max_execution_time=0` is appropriate. Do **not** run this as an HTTP admin upload.

### Memory

`persistMedia` loads the whole file into a PHP string. Max ~5 MB source; JPEG decode + three encodes can spike tens of MB per file. **`memory_limit=128M` is enough if one file at a time.** Do not slurp the mapping or the 76k dump into one giant blob.

### Disk after ingest

Originals ~1.75 GB on `storage/app/public/media` plus three WebPs per image (typically smaller than source JPEG). Rough **~2.5–3.5 GB** under `storage/app/public` depending on WebP ratios. Confirm free disk on the host before a real run.

### Recommended batch

Unchanged from the plan:

- Progress tick: **25–50 products** or **50–100 files**
- Process files **sequentially** (or a small worker pool later; not required)
- `--limit` / `--resume-from-sku` for resumability
- Dry-run first (zero writes)

---

## 7. Readiness summary

| Area | Status |
|---|---|
| Schema | Ready (unique pivot, JSON meta, no global media uniqueness) |
| Empty media library | Ready for first ingest (no orphans, no collisions) |
| Products / SKUs | **Not ready** (0 / 1011 mapped missing) |
| Source files | Ready (2497 files present, 0 missing, 1.75 GB) |
| GD / WebP | Ready on this PHP |
| Fallback image | Unset (affects 358 SKUs only) |
| Attach command | Not built (implementation, not catalog) |

---

## 8. Remaining blockers

**Hard (catalog):**

1. Import products (enriched WooCommerce CSV) so the 1011 mapped SKUs exist.
2. Re-run counts: mapped-in-catalog should be 1011 (or 1011 minus any SKU import failures). Then attach.

**Hard (implementation, already documented):**

3. No attach command yet. Do not use CSV HTTP Images or broken `--link-images`.
4. `registerExistingFile` / `wordpress_uploads` still missing; persist via existing upload path instead.

**Non-blocking:**

5. 358 SKUs stay imageless; fallback UUID is null.
6. Shared-file rollback discipline (section 5).
7. After product import, if any SKU already has media, decide `--skip-existing` vs `--force` with a new collision count.

---

## Success answers

1. **Catalog ready?** No. 0 products, 0 SKUs, 1011 mapped SKUs missing.
2. **Existing media conflicts?** None. `--skip-existing` skips 0; `--force` rebuilds 0.
3. **Rollback safe?** Yes if tagged in `meta` and shared UUIDs are deleted only when unused.
4. **Proceed with media ingest?** **No** until products exist (and the command exists).
5. **Blockers:** empty catalog; then implement attach; then dry-run.
