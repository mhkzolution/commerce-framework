# Task 4 Report: Attribute Recovery Commands

## Status

Implemented the recovery service and both Artisan commands. Recovery audits and canonicalizes size/age values, backs up affected state, links the eight covered attributes without replacing unrelated attributes, flips them to select, updates the WooCommerce Default set, set-syncs products with `used_for_variations=false`, and reindexes touched products. Restore reinstates PAV rows, attribute types, set pivots, and pre-recovery product-attribute rows, removes catalog values created after the backup watermark, and reindexes all products.

No recovery command was run against the live `commerce_framework` database.

## RED

`php artisan test tests/Feature/Product/RecoverProductAttributeValuesTest.php`

Observed: 7 tests failed with 0 assertions because `Commerce\Product\Services\RecoverProductAttributeValues` and `product:recover-attribute-values` did not exist.

## GREEN

`php artisan test tests/Feature/Product/RecoverProductAttributeValuesTest.php tests/Feature/Product/ProductCsvImportTest.php tests/Feature/Product/ProductAttributeValueLinkerTest.php`

Observed: 40 tests passed with 228 assertions.

`vendor/bin/pint --test modules/Product/src/Services/RecoverProductAttributeValues.php modules/Product/src/Console/RecoverProductAttributeValuesCommand.php modules/Product/src/Console/RestoreProductAttributeValuesCommand.php tests/Feature/Product/RecoverProductAttributeValuesTest.php modules/Product/src/ProductServiceProvider.php`

Observed: passed. IDE diagnostics reported no errors in task files.

## Operational Notes

- Apply refuses to overwrite the dated PAV backup; `--force` allocates a numbered suffix.
- A completed second apply is a no-op and returns the original suffix.
- Audit and dry-run paths create no backups or catalog values.
- Restore intentionally replaces staff changes made after recovery, as warned by the restore command.

## Important Review Fixes

- Mixed linked and raw product values now submit the union of existing catalog labels and normalized raw tokens, preserving already-linked values. Attributes with no resulting tokens are omitted from linker input.
- Every apply backup now includes `_bak_attr_recovery_meta_{suffix}`, and apply/idempotency reporting tracks the numbered suffix actually created by forced runs.
- Apply validates all eight covered attribute name/code pairs and the `woocommerce_default` attribute set before creating backups or mutating recovery data.
- Added regression coverage for mixed `Blue` + raw `Red`, numbered backup suffix reporting, and missing-set failure before PAV backup creation.

`php artisan test tests/Feature/Product/RecoverProductAttributeValuesTest.php tests/Feature/Product/ProductAttributeValueLinkerTest.php`

Observed: 16 tests passed with 53 assertions.
