# Media migration — open questions

Unresolved before implementing attach. Not blockers for the plan doc; they are decisions for the implementation ticket.

## Product catalog state

1. Are the 1370 enriched SKUs already in the shop database, or must `products-woocommerce-enriched.csv` be imported first?
2. If some SKUs already have `product_media` from a trial HTTP import, is `--skip-existing` or `--force` the default?

## Source layout

3. Should `wordpress_uploads` root be `doc/uploads` (this machine) or `public/wp-content/uploads` (CLI help text)? They are not the same path today.
4. After migration, is `doc/uploads` kept as an archive only, or copied into the deploy artifact?

## Media identity

5. If two SKUs share the same WordPress file, one `media` row (reuse UUID) or one row per product?
6. Stamp key: `meta.wordpress_path`, `meta.source_url`, or both?

## Pipeline gaps (code not written)

7. Implement `MediaUploadService::registerExistingFile` (importer already calls it) vs wrap local files as `UploadedFile` into existing `upload()`?
8. Add `wordpress_uploads` to `config/filesystems.php` or always copy from an explicit `--source=` path and ignore that disk name?
9. Change `linkProductImages` so resume does not `delete()` the whole gallery, or never use that method for this run?

## Variants and runtime

10. Keep **synchronous** WebP generation (current architecture) for 2516 files, or queue `GenerateMediaVariants` (new behavior)?
11. Alt text: product name, empty, or filename?

## Coverage

12. The **358** SKUs with no PPK image URL: leave empty (fallback setting) or a later manual pass?
13. SKU `110092` (no PPK match): skip forever or supply a file by hand?

## Operations

14. Who runs the command (local shop vs staging vs production), and is `MEDIA_DISK=public` vs `s3` already decided for production?
15. Rollback window: keep `meta.batch` per run so a bad second run can roll back without deleting the first?
