# Task 5 Report: Workspace save pipeline

## Status

DONE

## TDD RED

- Added `tests/Feature/Product/ProductWorkspaceStockSaveTest.php` with nine workspace API acceptance tests.
- First run: 9 tests, 0 passed, 8 failures and 1 error.
- Failures confirmed missing explicit type persistence, simple default-variant synthesis, SKU allocation/validation, inventory ledger writes, tracking transitions, and type-change guard integration.

## TDD GREEN

- Implemented payload/DTO mapping for type, backorder policy, track inventory, product-level on-hand, and per-variant on-hand in camelCase and snake_case.
- Persisted explicit product type and backorder policy without variant-count inference.
- Added simple default-variant synthesis, tenant-aware SKU allocation, same-save collision handling, and field-level duplicate SKU validation.
- Copied the product tracking flag to all variants and routed quantities exclusively through `InventoryServiceInterface::setOnHand()`.
- Enforced explicit quantity on first enable, zero-ledger creation for new variants on existing tracked products, retained historical ledger rows when tracking is disabled, and guarded variable-to-simple deletion.
- Updated all three workspace requests to validate type and backorder policy, including decoded workspace payload values.
- Kept WooCommerce imports compatible by supplying explicit type and initial per-variant quantities.

## Verification

- `ProductWorkspaceStockSaveTest.php`: 9 passed, 34 assertions.
- Combined Task 5 suite (`ProductWorkspaceStockSaveTest`, `ProductWorkspaceApiTest`, `ProductTypeChangeGuardTest`): 19 passed, 55 assertions.
- PHP syntax, Pint, IDE diagnostics, and `git diff --check`: clean.

## Self-review

- Confirmed Product never updates `inventory_items.on_hand` directly.
- Confirmed no Cart, Checkout, POS, storefront, PDP, workspace Blade, or workspace JavaScript changes.
- Confirmed untracked saves make no Inventory service calls and type is always sourced from the DTO.
