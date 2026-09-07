# Product Create Type, SKU, and Stock Policy Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let staff choose Simple vs Variable and set track-stock / quantity / backorder on create, while keeping on-hand in the Inventory ledger via `setOnHand()`.

**Architecture:** Product owns `type`, `backorder_policy`, and per-variant `track_inventory` / SKU. Inventory still owns `on_hand`, `reserved`, and movements. Workspace save calls `InventoryServiceInterface`. Cart, POS, PDP, and shop filters read variant track flag + product backorder policy instead of raw `(on_hand - reserved) > 0`.

**Tech Stack:** Laravel 13, PHP 8.4, PHPUnit, Blade product workspace, `resources/js/admin/product-workspace/`

**Spec:** `docs/superpowers/specs/2026-09-07-product-create-stock-design.md`

## Global Constraints

- Do not move inventory tables or movements into the Product module.
- Never `UPDATE inventory_items.on_hand` from Product; only `InventoryService::setOnHand()`.
- Do not infer `products.type` from variant count.
- Every persisted variant SKU is unique per tenant; blank SKUs are auto-generated.
- v1 track toggle is product-level in the UI; save copies the boolean onto every `product_variants.track_inventory`.
- No negative `on_hand`. Backorder demand lives on orders, not as ledger backlog.
- Variable → Simple is blocked by extra-variant `reserved > 0` or **open** order lines (`orders.status` in `pending`, `confirmed`). `completed` / `cancelled` do not block.
- Unchecking track leaves inventory rows and movements in place.

---

### Task 1: Schema and models

**Files:**
- Create: `modules/Product/database/migrations/2026_09_07_100000_add_stock_policy_to_products_and_variants.php`
- Modify: `modules/Product/src/Models/Product.php` (fillable + casts)
- Modify: `modules/Product/src/Models/ProductVariant.php` (fillable + casts)
- Test: `tests/Feature/Product/ProductStockPolicyMigrationTest.php`

**Interfaces:**
- Consumes: existing `products` / `product_variants` / `inventory_items` tables
- Produces: `products.backorder_policy` (`deny`|`notify`|`allow`, default `deny`); `product_variants.track_inventory` (bool); `product_variants.sku_is_auto` (bool)

- [ ] **Step 1: Write the failing test**

```php
public function test_migration_backfills_track_from_inventory_item_presence(): void
{
    $tracked = $this->createPurchasableProduct(sku: 'MIG-TRACK');
    app(\Commerce\Inventory\Contracts\InventoryServiceInterface::class)
        ->setOnHand($tracked->uuid, 0);

    $untracked = $this->createPurchasableProduct(sku: 'MIG-FREE');
    \Commerce\Inventory\Models\InventoryItem::query()
        ->where('purchasable_uuid', $untracked->uuid)
        ->delete();

    $this->artisan('migrate');

    $this->assertTrue($tracked->fresh()->track_inventory);
    $this->assertFalse($untracked->fresh()->track_inventory);
    $this->assertSame('deny', $tracked->product->fresh()->backorder_policy);
    $this->assertFalse($tracked->fresh()->sku_is_auto);
}
```

If migrate-already-ran makes this awkward, assert schema + model fill after running the new migration in the test via `Schema::hasColumn`.

- [ ] **Step 2: Run test, confirm fail** (columns missing)

Run: `php vendor/bin/phpunit tests/Feature/Product/ProductStockPolicyMigrationTest.php`

- [ ] **Step 3: Migration**

```php
Schema::table('products', function (Blueprint $table): void {
    $table->string('backorder_policy', 20)->default('deny')->after('type');
});
Schema::table('product_variants', function (Blueprint $table): void {
    $table->boolean('track_inventory')->default(true)->after('sku');
    $table->boolean('sku_is_auto')->default(false)->after('track_inventory');
});
// UPDATE product_variants SET track_inventory = exists inventory_items for uuid
// (zero on_hand still counts as historical evidence)
```

Add `backorder_policy`, `track_inventory`, `sku_is_auto` to model `$fillable`. Cast booleans.

- [ ] **Step 4: Tests pass**

- [ ] **Step 5: Commit**

```bash
git add modules/Product/database/migrations/2026_09_07_100000_add_stock_policy_to_products_and_variants.php \
  modules/Product/src/Models/Product.php modules/Product/src/Models/ProductVariant.php \
  tests/Feature/Product/ProductStockPolicyMigrationTest.php
git commit -m "feat: add product stock policy columns"
```

---

### Task 2: Unique SKU generator

**Files:**
- Create: `modules/Product/src/Services/VariantSkuGenerator.php`
- Test: `tests/Unit/Product/VariantSkuGeneratorTest.php`

**Interfaces:**
- Consumes: existing SKUs in `product_variants` for the tenant
- Produces: `VariantSkuGenerator::allocate(string $prefix, array $optionValues, ?string $typedSku): GeneratedSku` where `GeneratedSku` is `readonly (string $sku, bool $isAuto)`

- [ ] **Step 1: Failing tests**

```php
public function test_blank_sku_uses_prefix_and_options(): void
{
    $gen = new VariantSkuGenerator(fn () => ['TSHIRT-RED-S']);
    $out = $gen->allocate('TSHIRT', ['Red', 'S'], null);
    $this->assertSame('TSHIRT-RED-S-2', $out->sku);
    $this->assertTrue($out->isAuto);
}

public function test_typed_sku_is_not_auto(): void
{
    $out = (new VariantSkuGenerator(fn () => []))->allocate('TSHIRT', ['Red'], 'CUSTOM-1');
    $this->assertSame('CUSTOM-1', $out->sku);
    $this->assertFalse($out->isAuto);
}
```

Slugify: uppercase, non-alphanumerics → `-`, collapse dashes.

- [ ] **Step 2: Run, fail**

Run: `php vendor/bin/phpunit tests/Unit/Product/VariantSkuGeneratorTest.php`

- [ ] **Step 3: Implement `VariantSkuGenerator`** — collision loop `-2`, `-3`. Empty prefix falls back to `SKU`.

- [ ] **Step 4: Pass**

- [ ] **Step 5: Commit** `feat: generate unique variant SKUs`

---

### Task 3: Variable → Simple guard

**Files:**
- Create: `modules/Product/src/Services/ProductTypeChangeGuard.php`
- Test: `tests/Feature/Product/ProductTypeChangeGuardTest.php`

**Interfaces:**
- Consumes: `Product` with `variants`, `inventory_items.reserved`, `order_line_items.purchasable_uuid` + `orders.status`
- Produces: `ProductTypeChangeGuard::assertCanBecomeSimple(Product $product, array $keptVariantUuids): void` throws `DomainException` naming blocking variant SKUs

Open order statuses: `pending`, `confirmed` only.

- [ ] **Step 1: Failing tests**

```php
public function test_blocks_when_extra_variant_has_reserved_stock(): void { /* ... */ }
public function test_blocks_when_extra_variant_is_on_pending_order(): void { /* ... */ }
public function test_allows_when_only_completed_order_references_extra_variant(): void { /* ... */ }
public function test_simple_to_variable_always_allowed(): void { /* no throw */ }
```

- [ ] **Step 2: Run, fail**

- [ ] **Step 3: Implement guard** — extra = variants not in `$keptVariantUuids`. Block if `reserved > 0` OR exists order line join orders where status in `pending`,`confirmed`.

- [ ] **Step 4: Pass**

- [ ] **Step 5: Commit** `feat: guard variable-to-simple product type changes`

---

### Task 4: Inventory reserve/sale honors track + backorder

**Files:**
- Create: `modules/Inventory/src/Services/StockPolicyEvaluator.php`
- Modify: `modules/Inventory/src/Services/InventoryService.php` (`reserve`, `sale`)
- Modify: `modules/Inventory/src/Services/InventoryQueryService.php` (`paginate` filter tracked; add `availabilityForPurchasable(): ?int`)
- Test: `tests/Feature/Inventory/StockPolicyEvaluatorTest.php`

**Interfaces:**
- Consumes: `ProductVariant.track_inventory`, `Product.backorder_policy`, `StockLevel`
- Produces:
  - `StockPolicyEvaluator::shouldTrack(ProductVariant $variant): bool`
  - `StockPolicyEvaluator::canFulfill(Product $product, ProductVariant $variant, int $qty, StockLevelInterface $level): bool`
  - `InventoryQueryService::availabilityForPurchasable(string $uuid): ?int` — `null` if untracked

- [ ] **Step 1: Failing tests**

```php
public function test_untracked_variant_skips_reserve(): void { /* no DomainException, reserved stays 0 */ }
public function test_deny_zero_available_cannot_reserve(): void { /* DomainException */ }
public function test_allow_zero_available_can_reserve_beyond_on_hand(): void { /* reserved=5, on_hand=0 */ }
public function test_sale_floors_on_hand_at_zero(): void { /* after confirm-style sale */ }
```

- [ ] **Step 2: Run, fail**

- [ ] **Step 3: Implement evaluator; `reserve()`: if `!shouldTrack` return current level without mutating; if `canFulfill` false throw; else allow reserved > on_hand. `sale()`: `on_hand = max(0, on_hand - qty)`, reduce reserved if present, never negative on_hand. `paginate()`: `whereExists` variants `track_inventory = true`.

- [ ] **Step 4: Pass**

- [ ] **Step 5: Commit** `feat: honor track and backorder in inventory reserve`

---

### Task 5: Workspace save pipeline

**Files:**
- Modify: `modules/Product/src/DTO/SaveProductWorkspaceData.php` — add `string $type = 'simple'`, `string $backorderPolicy = 'deny'`, `bool $trackInventory = true`, `?int $onHand = null`
- Modify: `modules/Product/src/Support/WorkspacePayload.php` — map `type`, `backorder_policy`/`backorderPolicy`, `track_inventory`, variant `onHand`
- Modify: `modules/Product/src/Services/ProductWorkspaceSaveService.php` — persist explicit type; call guard; generate SKUs; copy track flag; call Inventory
- Modify: `modules/Product/src/Http/Requests/StoreProductRequest.php` and `UpdateProductRequest.php` — `type` in simple|variable; `backorder_policy` in deny|notify|allow
- Test: `tests/Feature/Product/ProductWorkspaceStockSaveTest.php`

**Interfaces:**
- Consumes: Tasks 1–4
- Produces: create/update persist spec §7 behavior

- [ ] **Step 1: Failing tests covering spec §10 cases 1–8 and 11** (workspace API `postJson` like `ProductWorkspaceApiTest`)

```php
public function test_create_simple_tracked_sets_on_hand_via_movement(): void
public function test_create_simple_untracked_has_no_inventory_item(): void
public function test_create_variable_does_not_infer_type_from_one_variant(): void
public function test_blank_variant_skus_are_unique_and_auto(): void
public function test_duplicate_typed_sku_is_rejected(): void
public function test_enable_track_without_qty_fails_validation(): void
public function test_new_tracked_variant_gets_zero_inventory_item(): void
public function test_uncheck_track_keeps_item_and_movements(): void
```

- [ ] **Step 2: Run, fail**

- [ ] **Step 3: Replace `$type = count($data->variants) > 1 ? 'variable' : 'simple'` with `$data->type`. Simple with empty variants array: synthesize one default variant from name/sku/price. After `syncVariants`, for each variant: set `track_inventory` from `$data->trackInventory`; if tracked, `ensureItem` via `setOnHand` when qty provided; new tracked without qty → `setOnHand(uuid, 0)` is no-op after firstOrCreate 0. Enabling track: qty required. Inject `InventoryServiceInterface` like CSV importer.

- [ ] **Step 4: Pass**

- [ ] **Step 5: Commit** `feat: save product type and stock policy from workspace`

---

### Task 6: Storefront, checkout, POS, shop filter

**Files:**
- Modify: `modules/Cart/src/Services/ProductDetailBuilder.php` — `available()` use `availabilityForPurchasable`; `inStock` already treats `null` as true; `notify`/`allow` + 0 available still in stock
- Modify: `modules/Cart/src/Services/ProductCardMapper.php`
- Modify: `modules/Cart/src/Services/StorefrontQuickViewService.php`
- Modify: `modules/Cart/src/Services/ShopProductQuery.php` `constrainInStock`
- Modify: `modules/Cart/src/Services/CartService.php` and `CheckoutService.php` insufficient-stock branches
- Modify: `modules/Pos/src/Services/PosCartService.php`
- Modify: `modules/Orders/src/Services/OrderService.php` if it duplicates the check
- Test: extend `tests/Unit/Cart/ProductDetailBuilderTest.php`, `tests/Feature/Checkout/CheckoutFlowTest.php`, shop filter test, POS test

**Interfaces:**
- Consumes: `InventoryQueryService::availabilityForPurchasable`, `StockPolicyEvaluator::canFulfill`
- Produces: spec §9 call-site behavior

- [ ] **Step 1: Failing tests** — spec §10 cases 9–10: deny+0 cannot checkout; allow+0 can; untracked can; in-stock filter includes untracked and allow+0

- [ ] **Step 2: Run, fail**

- [ ] **Step 3: Replace raw available>0 checks with policy evaluator. Shop filter: untracked OR available>0 OR product.backorder_policy in (notify, allow).

- [ ] **Step 4: Pass** (also run existing PDP/cart/POS stock tests)

- [ ] **Step 5: Commit** `feat: apply stock policy to checkout pos and shop`

---

### Task 7: Workspace UI (type switch + stock card)

**Files:**
- Modify: `modules/Product/resources/views/components/workspace/header.blade.php` — Simple | Variable control bound to `data-workspace-type`
- Modify: `modules/Product/resources/views/components/workspace/general-form.blade.php` — SKU/price/stock card for simple; hide quantity for variable
- Modify: `modules/Product/resources/views/components/workspace/tabs.blade.php` — hide Variants tab when type=simple (`data-workspace-panel="variants"`)
- Modify: `modules/Product/resources/views/components/workspace/variants/grid-row.blade.php` — on-hand input when tracking; reserved/available read-only
- Modify: `modules/Product/src/Services/ProductWorkspaceStateBuilder.php` — `product.type`, `backorderPolicy`, `trackInventory`, variant `trackInventory`, `skuIsAuto`
- Modify: `resources/js/admin/product-workspace/state.js` — persist type/track/backorder/onHand in payload
- Modify: `resources/js/admin/product-workspace/variant-builder.js` — on-hand cells; copy product track to rows before submit
- Modify: `modules/Product/resources/lang/en/workspace.php` and `th/workspace.php` — track / backorder labels
- Test: `tests/Feature/Product/ProductWorkspaceApiTest.php` — create page sees type control and stock checkbox; simple payload hides variants builder (`assertDontSee` variant generate, or assertSee type radios)

**Interfaces:**
- Consumes: Task 5 payload keys: `type`, `backorderPolicy`, `trackInventory`, variant `onHand`
- Produces: create/edit same type-aware layout

- [ ] **Step 1: Failing UI test** `test_create_page_shows_type_and_track_controls`

- [ ] **Step 2: Run, fail**

- [ ] **Step 3: Add stock card Blade; JS toggles `[data-simple-stock]` vs `[data-variant-builder]` from type radios; submit copies `trackInventory` onto every variant in `workspace_payload`.

- [ ] **Step 4: Pass `ProductWorkspaceApiTest` + `ProductWorkspaceStockSaveTest`

- [ ] **Step 5: Commit** `feat: add simple variable stock controls to product workspace`

---

### Task 8: Spec coverage self-check (no new product code)

Walk spec §2–§10. Confirm:

| Spec | Task |
|---|---|
| Explicit type | 5, 7 |
| Simple no variant UI | 7 |
| Variable no parent qty | 7 |
| `setOnHand` only | 5 |
| Unique auto SKU | 2, 5 |
| First-enable requires qty | 5 |
| New variant 0/0 item | 5 |
| Open-order type guard | 3 |
| Uncheck keeps ledger | 5, 4 listing |
| Checkout/POS/shop | 6 |
| Inventory index tracked only | 4 |
| Migration historical item | 1 |

If a row is missing, add a test to that task before calling the work done.

- [ ] **Step 1: Run** `php vendor/bin/phpunit tests/Feature/Product tests/Feature/Inventory tests/Feature/Checkout tests/Unit/Product tests/Unit/Cart/ProductDetailBuilderTest.php`
- [ ] **Step 2: Commit** only if leftover docs/comments `docs: note product stock policy implementation complete`

---

## Spec coverage (plan self-review)

- Type stored, not inferred → Task 5
- Type change rules → Task 3
- SKU unique + auto + `sku_is_auto` → Task 2, 5
- Form vs ledger boundary → Tasks 4–5
- Track UI copy-to-all-variants → Task 7
- First enable qty required → Task 5
- New variant inventory 0/0 → Task 5
- Backorder policy + no negative on_hand + demand on orders → Task 4, 6
- Migration historical item → Task 1
- Call sites → Task 6
- Acceptance 1–11 → Tasks 5–7
- Out of scope respected (no negative stock, no per-variant backorder UI, no delete ledger)
