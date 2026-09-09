# Attribute Recovery Final Review Fixes

Status: complete

## Changes

- Delete remaining product-level null-FK rows for the eight covered attributes after text linking and before changing attributes to `select`.
- Cover recovery of a color value containing only `,`, which now leaves no product-level color row.
- Cover array label payloads in `ProductAttributeValueLinker`, including two linked PAV rows with non-null foreign keys.
- Update the Catalog V2 cutover assertion to the PDP `values[]` shape.

## Verification

- `php artisan test tests/Feature/Product/RecoverProductAttributeValuesTest.php tests/Feature/Product/ProductAttributeValueLinkerTest.php tests/Unit/Cart/ProductDetailBuilderTest.php`
  - Passed: 35 tests, 165 assertions.
- `php artisan test tests/Feature/Product/CatalogV2CutoverTest.php`
  - Passed: 4 tests, 33 assertions.
- Scoped `git diff --check`
  - Passed.
- IDE diagnostics on all four modified PHP files
  - No errors.
