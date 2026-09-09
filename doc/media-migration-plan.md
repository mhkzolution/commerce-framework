# Product image migration plan (PPK → commerce-framework)

**Status:** Discovery only. No code, database, or media changes in this pass.  
**Date:** 2026-09-09  
**Inputs:** `doc/products-woocommerce-enriched.csv`, `doc/products-image-mapping-report.md`, `doc/uploads/`  
**Facts:** 1370 template products, 1369 SKU matches, **1011** SKUs with local files, **2516** mapped files, **76911** files under `uploads/` (mostly WordPress derivatives — do not ingest).

---

## 1. Current media architecture

Media lives in module `modules/Media`. Products do not store image blobs; they store **media UUIDs**.

### Models

| Table / model | Role |
|---|---|
| `media` / `Commerce\Media\Models\Media` | Canonical file. UUID, disk, path, mime, dimensions, `meta` JSON, soft deletes. |
| `media_variants` / `MediaVariant` | Named derivatives (`thumbnail`, `card`, `detail`). Unique `(media_id, name)`. |
| `media_folders` / `MediaFolder` | Optional library folders. |
| `media_tags` / `MediaTag` | Library tags (seed includes `Products`). Pivot `media_tag_media`. |

### Services

| Class | Role |
|---|---|
| `MediaUploadService` | `upload(UploadedFile\|url)` → persist original + fire `MediaUploaded`. `importFromUrl()` HTTP GET (30s, size cap). `replace()`. |
| `MediaQueryService` | `getUrl($uuid, $variant)`, `getSrcset()`, aliases `medium→card`, `large→detail`. |
| `MediaService` | Update alt/caption/crop/tags; `delete` / `deleteMany` (blocks if in use unless `$force`). |
| `MediaUsageService` | Usage from `config('media.usage_sources')` (includes `product_media.media_uuid`). |
| `ImageVariantGenerator` | GD WebP variants. Skip SVG. Skip if GD/`imagewebp` missing. |

### Upload and processing pipeline

1. Bytes written to `Storage::disk(config('media.disk'))` (default **`public`**).
2. Path convention: `{config media.path}/` + `{uuid}.{ext}` → typically `media/{uuid}.jpg`.
3. `MediaUploaded` is dispatched on the Laravel event bus (**synchronous**). `MediaUploaded` does **not** implement `ShouldQueue`.
4. Listener `GenerateMediaVariants` runs **inline** in the same request/command and writes:

| Variant | Rule | Output |
|---|---|---|
| `thumbnail` | width 300 | `media/variants/{uuid}-thumbnail.webp` |
| `card` | width 800 | `media/variants/{uuid}-card.webp` |
| `detail` | max 1600 | `media/variants/{uuid}-detail.webp` |

`media:generate-variants {--force}` can backfill variants in chunks of 50.

`MEDIA_KEEP_ORIGINAL` (default true) keeps the original file. If false, the original is replaced by the `detail` WebP.

### Queue

Default queue is `database`, but **this pipeline does not enqueue variant generation**. Upload + three WebPs happen in-process. There is no media-specific job class.

---

## 2. Product media relationships

### Featured vs gallery

There is **no separate featured-image column** on `products`.

`product_media`:

- `product_id` → `products.id` (cascade delete)
- `media_uuid` (no FK to `media`; unique `(product_id, media_uuid)`)
- `position` (unsigned int, default 0)
- `is_primary` (bool)

`Product::media()` is `hasMany(ProductMedia::class)->orderBy('position')`.

Workspace save and WooCommerce `linkProductImages` both:

1. Delete existing `product_media` for the product.
2. Recreate rows in array order: `position = 0..n-1`, **`is_primary` = first row only**.

Storefront (`ProductDetailBuilder`) sorts gallery as **primary first**, then `position`. Variant-level images can also appear from `variants.meta.image_media_uuid` (not used by the PPK CSV mapping; mapping is product-level).

Admin listing uses the first `product_media` row’s `thumbnail` URL. Cards/PDP use `card` / `detail` via `MediaQueryService`.

### Fallback

`product.fallback_image_media_uuid` (product settings) is for products **with no media**, not a per-SKU gallery.

---

## 3. Existing reusable workflows

| Path | What it does | Reuse for PPK? |
|---|---|---|
| Admin `POST admin.media.store` / API `MediaController@store` | Multipart upload → `MediaUploadService::upload` | Too interactive for 2516 files. |
| Admin `POST admin.media.import` | One URL → `importFromUrl` | Do **not** pull 2516 live `punpunkun.com` URLs. Slow, flaky, ignores local `doc/uploads`. |
| CSV `WooCommerceProductImporter::resolveMediaUuids` | Same HTTP import per Images URL during product upsert | Enriched CSV **Images column is empty**. Would no-op unless Images are filled. HTTP still wrong. |
| CLI `product:import-woocommerce --link-images` | Intended disk link via `linkProductImages` | **Broken as written:** calls `MediaUploadService::registerExistingFile()` behind `method_exists`; **that method does not exist**. Always links 0. Also requires disk `wordpress_uploads`, which is **not** defined in `config/filesystems.php`. |
| `media:generate-variants` | Backfill WebP | Reuse **after** originals are in `media`, if variants were skipped. |
| Seeders | No product-image seeder found | Do not invent one as the main path. |

**Recommendation:** reuse **`MediaUploadService` persist + `ProductMedia` attach** (same records the admin picker already uses). Complete the **intended** `registerExistingFile` + `wordpress_uploads` disk **or** copy local files through `persistMedia` using `UploadedFile` from a real path. Do not add a second media store.

Do **not** ingest the 76,911 WordPress thumbs. Commerce-framework **already generates** thumbnail/card/detail. Source of truth for *which* files: `doc/products-image-mapping-report.md` (2516 URL-selected files).

---

## 4. Storage strategy

| Disk | Root | URL | Used by media today |
|---|---|---|---|
| `public` | `storage/app/public` | `{APP_URL}/storage` | **Yes** (`MEDIA_DISK`, default). Needs `php artisan storage:link`. |
| `local` | `storage/app/private` | `/storage/private` | Not used for product images. |
| `s3` | env | env | Optional; not required for this migration. |
| `wordpress_uploads` | *not configured* | — | Referenced only in `config/product.php` + importer. |

**Naming after ingest:** `{uuid}.{ext}` under `media/`, not WordPress `2021/03/foo.jpg`. Original filename is stored on `media.original_filename`. Variants: `media/variants/{uuid}-{name}.webp`.

**Source files stay in `doc/uploads/`.** Copy (or stream) into the public disk. Do not point `media.path` at the WordPress tree (CF expects uuid filenames and variant paths; serving 76k WP files as library originals would mix two conventions).

---

## 5. Recommended migration strategy

**Prerequisite:** products exist in DB, keyed by SKU (import `products-woocommerce-enriched.csv` *without* relying on Images). Image attach is a **second** command.

**Do not** fill Images with remote URLs and re-run admin CSV import.

### Command / service (future code)

```text
php artisan product:attach-local-images
  {--mapping=doc/products-image-mapping-report.md}  # or a JSON sidecar derived from it
  {--source=doc/uploads}
  {--dry-run}
  {--limit=}
  {--offset=} / {--resume-from-sku=}
  {--skip-existing}   # default: skip products that already have product_media
  {--force}           # replace product_media for those SKUs
```

Implementation sketch (fits existing types):

1. Parse mapping: SKU → ordered list of relative paths under `uploads/`.
2. Resolve `Product` by SKU (`product_variants.sku`).
3. For each path:
   - If `media.meta.wordpress_path` (or `source_url`) already exists → reuse UUID (shared image across SKUs).
   - Else `MediaUploadService` persist from local file (copy into `public`/`media/{uuid}.ext`) → `MediaUploaded` → WebP variants.
   - Stamp `meta`: `wordpress_path`, `migration: ppk-images`, `batch: {iso}`.
   - Optional: attach tag `Products`.
4. Attach `product_media` in mapping order. **Do not** call current `linkProductImages` until `registerExistingFile` exists and it stops deleting galleries on a partial rerun unless `--force`.
5. Continue on per-file errors; write a run log (SKU, path, error).

### Idempotent

- Media: unique key = WordPress relative path (and/or original source URL).
- Product attach: `--skip-existing` if `product_media` already has rows.
- Unique `(product_id, media_uuid)` prevents duplicate pivot rows.

### Resumable

- `--resume-from-sku=` or skip SKUs already attached.
- Failed files listed in a sidecar log; rerun the same command.

### Dry-run

Print: SKU, product found?, file exists?, would-create-media vs reuse, would-attach count. Zero writes.

### Failure recovery

- Per-file try/catch; do not abort the 1011-product run.
- Incomplete product: leave attached rows that succeeded; rerun with skip-existing **off** for that SKU only if using `--force`, or attach missing UUIDs only (prefer this over delete-all).

---

## 6. Rollback strategy

Identify migrated media:

- `media.meta->migration = 'ppk-images'` (and/or `batch`)
- optional `media_tags.slug = products` is too broad; **do not** use the seed tag alone for rollback

Rollback steps (future):

1. Select media where `meta->migration = ppk-images` (JSON query).
2. Delete `product_media` rows with those `media_uuid`s (or by `product_id` in the SKU set).
3. `MediaService::delete($uuid, force: true)` so in-use check does not block after pivots are gone. Deletes original + variant files, **soft-deletes** `media`.
4. Do **not** delete `doc/uploads` or `storage` files that were not stamped.

Batch tracking: one `batch` ISO string per command run in `meta`. Rollback one batch without touching later runs.

---

## 7. Performance considerations

| Item | Guidance |
|---|---|
| Scope | **2516 originals**, not 76911. |
| Batch | **25–50 products** or **50–100 files** per progress tick. `chunkById` is for DB; here iterate mapping. |
| Memory | `persistMedia` currently loads the **entire file into a PHP string**. Keep batch small; do not `file_get_contents` a huge directory at once. |
| CPU | 2516 × 3 GD WebP encodes, **synchronous**. Expect **tens of minutes** on a laptop; more if originals are large JPEGs. |
| Queue | Not required for correctness (current architecture is sync). Optional later: queue `GenerateMediaVariants` — that would be a **new** behavior, not present today. Until then, run the artisan command with a long timeout, not the HTTP admin import. |
| Disk | Original + 3 WebPs per image. Rough order: a few GB depending on source size. `storage/app/public/media` + `media/variants`. |
| HTTP | Avoid. Local copy only. |

---

## 8. Risks

- **`registerExistingFile` is a dead call.** Shipping `--link-images` as-is will report 0 linked.
- **`wordpress_uploads` disk missing** from `filesystems.php`.
- Enriched CSV has **empty Images**; CSV import will not attach files until a dedicated attach command (or Images filled with a scheme the importer understands).
- `linkProductImages` **wipes** existing galleries — unsafe for resume.
- Shared URLs across SKUs: must reuse media UUID or the library duplicates 2516+ files.
- 358 matched SKUs have **no image URL** — they stay on fallback image.
- Template SKU `110092` has no PPK row — no mapping.
- GD missing `imagewebp` → originals stored, **no variants**, storefront `getUrl(..., 'detail')` falls back to original (acceptable but uglier).
- `product_media.media_uuid` has **no FK**; orphan UUIDs possible if media is force-deleted first.

---

## 9. Future command/service architecture (no implementation in this task)

```text
ProductAttachLocalImagesCommand
  → ProductImageMappingReader (SKU → paths from mapping report / JSON)
  → MediaUploadService (persist local file; add registerExistingFile or UploadedFile::createFromBase)
  → ProductMedia attach (position / is_primary)
  → run log + counters
```

Do not introduce a parallel CDN, WP path-as-public-URL, or skipping `media` / `media_variants`.

---

## Success answers

1. **Stored today:** `storage/app/public/media/{uuid}.{ext}` + WebP variants; DB `media` + `media_variants`.
2. **Products reference:** `product_media.media_uuid`, order `position`, featured = `is_primary` on position 0.
3. **Reuse:** upload/persist + `ProductMedia` attach. HTTP import and current `--link-images` are **not** ready. Mapping report is the file list.
4. **Safest:** local copy of 2516 mapped files into the existing media disk; skip WP derivatives; skip HTTP; skip-existing; dry-run; per-file continue.
5. **Command:** artisan attach-from-mapping, not admin one-URL import.
6. **Rollback:** stamp `meta.migration`, delete pivots, `MediaService::delete(..., force: true)`.
