# Catalog V2 Attribute–Variant Unification Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make Catalog attributes the only source of truth for specification, variant generation, PDP, and shop filters, with relation-only read/write after a one-way JSON cutover.

**Architecture:** `attribute_values` hold canonical `code`s. `product_attributes` records which attributes a product uses (`used_for_variations` per product). `product_attribute_values` stores spec values, axis membership, and per-variant values. Variant identity is `implode('-', sort(attribute_value_ids))`. Inventory, type, and SKU uniqueness stay on the stock spec.

**Tech Stack:** Laravel 13, PHP 8.4, PHPUnit, Blade product workspace, `resources/js/admin/product-workspace/`

**Spec:** `docs/superpowers/specs/2026-09-07-catalog-v2-attribute-variant-design.md` (Locked)

**Start gate:** Product Stock V1 is Complete (manual smoke 1–6 + Task 8). Implement this plan only after that close; do not mix Catalog V2 into leftover stock work. Both features edit the product workspace; mixing them hides the source of regressions.

## Global Constraints

- Do not change inventory tables, `setOnHand()`, backorder policy, or SKU uniqueness.
- Do not infer `products.type` from variant count.
- Simple products keep exactly one default variant; staff never see a variant builder.
- `attribute_values.code` is unique per attribute and **immutable after creation**. Updates may change `label` only. Filter URLs depend on `code`.
- `is_filterable` and `is_visible` live on `attributes`. Used for Variations lives on `product_attributes`.
- Variation axes are `select` only. No multiselect axes in Phase 1.
- After cutover: read path = relation only; write path = relation only. Do not keep a JSON compatibility layer.
- Migration must not auto-create catalog attributes. Unmatched options: skip and log `product_id`, `attribute_name`, `option_name`.
- Preserve variant UUID, SKU, inventory rows, and `products.type` through migrate and idempotent generate.
- Do not start this plan’s storefront cutover until Wave 1 schema+migration tests pass. Human gate after each Wave 3 **task** (PDP, then filter, then cutover).
- **Cutover last:** do not delete `meta.variant_options`, `meta.options`, or `VariantOptionAttributeProvisioner` from runtime until PDP, shop filter, and workspace hydrate all read relations. Wave 2 already stopped writing JSON; that is not permission to remove the read-path/runtime class.
- **Migration Safety Gate (implementation lock, not a spec change):** Wave 1 migrations must be re-runnable. Fresh migrate passes; upgrade of existing `attributes.options` passes; `migrate:rollback` then migrate again passes; they must not create duplicate `attribute_values`, `product_attributes`, or `product_attribute_values` relations.

---

## File map

| Area | Files |
|---|---|
| Values | Create `modules/Catalog/src/Models/AttributeValue.php`, `modules/Catalog/src/Services/AttributeValueService.php` (`allocateCode`) |
| Schema | Create `modules/Catalog/database/migrations/2026_09_07_200000_create_attribute_values_table.php`, `modules/Product/database/migrations/2026_09_07_200001_add_product_attributes_and_value_fk.php` |
| Identity / generate | Create `modules/Product/src/Services/VariantIdentity.php`, `modules/Product/src/Services/VariantMatrixGenerator.php` |
| JSON migrate | Create `modules/Product/src/Services/CatalogVariantRelationMigrator.php` |
| Publish guard | Create `modules/Product/src/Services/VariableProductPublishGuard.php` |
| Workspace | Modify `ProductWorkspaceSaveService`, `WorkspacePayload`, `ProductWorkspaceStateBuilder`, Blade attributes panel, `resources/js/admin/product-workspace/` |
| Storefront | Modify `ShopProductQuery`, `ShopFilterCatalogService`, `ProductDetailBuilder` |
| Cutover | Remove save-path use of `VariantOptionAttributeProvisioner`; stop writing `meta.variant_options` / `meta.options` |

---

## Wave 1 — Schema + migration

### Task 1: `attribute_values` table and options backfill

**Files:**
- Create: `modules/Catalog/database/migrations/2026_09_07_200000_create_attribute_values_table.php`
- Create: `modules/Catalog/src/Models/AttributeValue.php`
- Modify: `modules/Catalog/src/Models/Attribute.php` — `hasMany` values
- Create: `modules/Catalog/src/Services/AttributeValueService.php`
- Test: `tests/Feature/Catalog/AttributeValueMigrationTest.php`

**Interfaces:**
- Consumes: `attributes.options` JSON list of strings
- Produces: `AttributeValue` rows (`tenant_id`, `attribute_id`, `code`, `label`, `position`); `AttributeValueService::allocateCode(int $attributeId, string $label, ?int $exceptId = null): string`; updates must not change `code` (label-only)

- [ ] **Step 1: Write the failing test**

```php
public function test_migration_backfills_option_strings_into_attribute_values(): void
{
    $attribute = \Commerce\Catalog\Models\Attribute::query()->create([
        'uuid' => (string) \Illuminate\Support\Str::uuid(),
        'code' => 'color',
        'name' => 'Color',
        'type' => 'select',
        'is_filterable' => true,
        'is_visible' => true,
        'options' => ['Red', 'Blue', 'Red'],
    ]);

    $this->artisan('migrate');

    $codes = \Commerce\Catalog\Models\AttributeValue::query()
        ->where('attribute_id', $attribute->id)
        ->orderBy('position')
        ->pluck('code')
        ->all();

    $this->assertSame(['red', 'blue', 'red-2'], $codes);
}
```

If `migrate` in tests already ran the new file, split: create attribute with options **before** running only this migration via `$this->artisan('migrate', ['--path' => '...'])` following `ProductStockPolicyMigrationTest` patterns.

- [ ] **Step 2: Run, fail** (table missing)

- [ ] **Step 3: Implement table + backfill in the same migration after `Schema::create`.** Unique `(tenant_id, attribute_id, code)`. `code` from `Str::slug($label, '_')` with `-2` suffix on collision (hyphen form `red-2` per spec). Never read-time derive code from label.

- [ ] **Step 4: Pass the test.** Also cover `test_updating_label_does_not_change_code` (create `burgundy` / “Burgundy”, update label to “Dark Burgundy”, assert `code` still `burgundy`). `AttributeValueService::allocateCode` is used by later tasks for product-form creates.

- [ ] **Step 5: Commit** `feat: add attribute_values and backfill from options json`

---

### Task 2: `product_attributes` and value FK

**Files:**
- Create: `modules/Product/database/migrations/2026_09_07_200001_add_product_attributes_and_value_fk.php`
- Create: `modules/Product/src/Models/ProductAttribute.php`
- Modify: `modules/Product/src/Models/ProductAttributeValue.php` — `attribute_value_id`, relations
- Modify: `modules/Product/src/Models/Product.php` — `productAttributes()`
- Test: `tests/Feature/Product/ProductAttributeSchemaTest.php`

**Interfaces:**
- Consumes: Task 1 `attribute_values.id`
- Produces: `product_attributes` (`used_for_variations` bool default false); `product_attribute_values.attribute_value_id` nullable FK; discrete unique `(product_id, attribute_id, product_variant_id, attribute_value_id)`

- [ ] **Step 1: Failing test** — assign set members: after helper `syncProductAttributesFromSet(Product $product)`, rows exist with no `product_attribute_values`. Two axis membership rows for Color Red+Blue (`product_variant_id` null) both persist.

- [ ] **Step 2: Run, fail**

- [ ] **Step 3: Migration:** create `product_attributes`; add `attribute_value_id`; drop old unique `product_attribute_values_unique`; add new unique. Backfill nothing for `used_for_variations` (false until Task 3).

- [ ] **Step 4: Pass tests.**

- [ ] **Step 5: Commit** `feat: add product_attributes and attribute_value foreign keys`

---

### Task 3: Relation migrator (JSON → rows)

**Files:**
- Create: `modules/Product/src/Services/CatalogVariantRelationMigrator.php`
- Modify: Task 2 migration or a follow-up `2026_09_07_200002_migrate_variant_json_to_relations.php` that calls the migrator
- Test: `tests/Feature/Product/CatalogVariantRelationMigratorTest.php`

**Interfaces:**
- Consumes: `products.meta.variant_options`, `product_variants.meta.options`, existing PAV `value` text, product attribute set
- Produces: `used_for_variations` true for mapped axes; axis membership; per-variant `attribute_value_id`; logs skipped options via `Log::warning` with context keys `product_id`, `attribute_name`, `option_name`. Does **not** call `AttributeService::create`.

- [ ] **Step 1: Failing tests**

```php
public function test_migrator_maps_json_options_without_changing_variant_uuid(): void
{
    // Product type variable, meta.variant_options Color/Size, variants Red-S and Blue-M.
    // Color/Size exist on the product's attribute set as select attributes with those labels.
    $migrator = app(\Commerce\Product\Services\CatalogVariantRelationMigrator::class);
    $migrator->migrate($product);

    $this->assertSame($redSUuid, $product->fresh()->variants->firstWhere('sku', 'RED-S')?->uuid);
    $this->assertTrue(
        $product->productAttributes()->where('used_for_variations', true)->count() >= 2
    );
}

public function test_unmatched_option_is_logged_and_skipped(): void
{
    \Illuminate\Support\Facades\Log::fake();
    // Option axis "Fit" not on the set.
    $migrator->migrate($product);
    \Illuminate\Support\Facades\Log::assertLogged('warning', fn ($log) =>
        isset($log['context']['product_id'], $log['context']['attribute_name'], $log['context']['option_name'])
    );
}
```

Use the project’s actual Log fake API if it differs; context array must contain those three keys.

- [ ] **Step 2: Run, fail**

- [ ] **Step 3: Implement migrator.** Match axis name to set attribute `name` or `code` (case-insensitive). Match option string to `attribute_values.label` or `code`; create **value** (not attribute) if missing. Reuse existing PAV rows. Canonical identity for later generate. Do not rewrite SKUs.

- [ ] **Step 4: Pass tests.**

- [ ] **Step 5: Commit** `feat: migrate variant json options onto attribute relations`

**Stop for human Wave 1 review.** Do not start Wave 2 until approved.

**Wave 1 human review (2026-09-07):** Approved. No blocking issues.

### Known debt (not blocking Wave 2)

Recorded at Wave 1 close. Do not treat these as Wave 2 start-gate failures.

| ID | Debt | When to close |
|---|---|---|
| A | **NULL unique hole.** Unique indexes that include nullable `product_variant_id` / `tenant_id` do not reject duplicate identical rows at SQL level. Wave 2 write path must upsert by lookup (existing `attribute_value_id`, else insert), not rely on the unique index. Follow-up: partial/functional unique or application uniqueness helper before merge if writes still race. | Wave 2 save (mitigate); later schema if still open |
| B | **Non-select axis.** Migrator does not reject `used_for_variations` on `text` / `textarea` / `number` / `boolean`. Wave 2 **save path** must validate `used_for_variations ⇒ attribute.type = select` (not UI-only). | Task 5 |
| C | **Provisioner still on save path** until Task 9. Wave 1 correctly left `VariantOptionAttributeProvisioner` in place. Task 5 stops calling it and stops writing `variant_options` / `options` JSON. Task 9 deletes leftover callers and ignores JSON on read. | Task 5 write; Task 9 cutover |

---

## Wave 2 — Workspace + generate

### Task 4: Identity + idempotent matrix generator

**Files:**
- Create: `modules/Product/src/Services/VariantIdentity.php`
- Create: `modules/Product/src/Services/VariantMatrixGenerator.php`
- Test: `tests/Unit/Product/VariantIdentityTest.php`, `tests/Feature/Product/VariantMatrixGeneratorTest.php`

**Interfaces:**
- Consumes: axis `attribute_id`s in `product_attributes.position` order; membership `attribute_value_id`s; existing variants’ value rows
- Produces: `VariantIdentity::key(array $valueIds): string` (`implode('-', sort(array_map('intval', $valueIds)))`); generator returns keep/create/drop lists by that key. Keep list preserves UUID.

- [ ] **Step 1: Failing unit test** — `key([5, 1]) === key([1, 5])`. Feature: generate Red/Blue × S/M → 4 rows; edit Blue-M SKU; generate again after adding L → Blue-M UUID and SKU unchanged; two new L rows.

- [ ] **Step 2: Run, fail**

- [ ] **Step 3: Implement.** Do not delete variants here; return drop candidates. Task 5 applies stock-spec guards before soft-delete.

- [ ] **Step 4: Pass tests.**

- [ ] **Step 5: Commit** `feat: generate variant matrix from canonical value identity`

---

### Task 5: Workspace save + publish guard

**Files:**
- Create: `modules/Product/src/Services/VariableProductPublishGuard.php`
- Modify: `modules/Product/src/Support/WorkspacePayload.php`
- Modify: `modules/Product/src/DTO/SaveProductWorkspaceData.php`
- Modify: `modules/Product/src/Services/ProductWorkspaceSaveService.php`
- Modify: `modules/Product/src/Services/ProductWorkspaceStateBuilder.php`
- Test: `tests/Feature/Product/ProductWorkspaceAttributeSaveTest.php`

**Interfaces:**
- Consumes: payload `productAttributes: list<{attributeId, usedForVariations, valueIds: list<int>}>`; Task 4 generator; existing `VariantSkuGenerator` using **codes** in axis position order; `ProductTypeChangeGuard` for drops
- Produces: persisted `product_attributes` + PAV; simple forces `used_for_variations` false; `used_for_variations` allowed only when `attributes.type = select` (ValidationException otherwise — Known Debt B); `VariableProductPublishGuard::assertCanPublish(Product $product): void` throws `ValidationException` if type is variable and no variant has a complete identity
- PAV writes upsert by lookup (Known Debt A). Do not rely on nullable unique indexes.

- [ ] **Step 1: Failing tests** matching spec §11.1, §11.2, §11.9 (draft variable without generate OK; `status=published` rejected). Adding Burgundy via payload creates `attribute_values` with unique code.

- [ ] **Step 2: Run, fail**

- [ ] **Step 3: Persist relations. Call `VariantSkuGenerator` only for **new** blank SKUs using `attribute_values.code`. Do not call `VariantOptionAttributeProvisioner`. Do not write `variant_options` / `options` JSON.**

- [ ] **Step 4: Pass tests. Existing `ProductWorkspaceStockSaveTest` still green.**

- [ ] **Step 5: Commit** `feat: persist product attributes and block publishing empty variable products`

---

### Task 6: Workspace Attributes panel + generate UI

**Files:**
- Modify: `modules/Product/resources/views/components/workspace/organization-form.blade.php` (or a new `attributes-panel.blade.php` included from General/Organization — one panel, not a second Color editor on Variants)
- Modify: `modules/Product/resources/views/components/workspace/variants/` — remove option-name chips as the write path; keep grid as generate **output**
- Modify: `resources/js/admin/product-workspace.js`, `state.js`, `variant-builder.js`
- Modify: `modules/Product/resources/lang/en/workspace.php`, `th/workspace.php`
- Test: `tests/Feature/Product/ProductWorkspaceApiTest.php` — create page sees used-for-variations control when type=variable (JS-hidden OK); `data-generate-variants` present

**Interfaces:**
- Consumes: Task 5 payload shape; state `skuPrefix` from stock spec
- Produces: explicit Generate button; warn before unchecking Used for Variations when a matrix exists (`confirm` copy in lang files)

- [ ] **Step 1: Failing UI test** `test_variable_workspace_shows_generate_and_variation_toggle`

- [ ] **Step 2: Run, fail**

- [ ] **Step 3: One Attributes panel. Simple: no variation checkbox. Generate disabled until each axis has ≥1 value. Idempotent generate in JS should call the same identity key as PHP (duplicate the sort-join in `state.js` or document that generate is server-side on save only — prefer **server generate on save** if the matrix is posted as variants; client generate must use the same key). Spec: explicit button; if generate is client-side, identity function must match `VariantIdentity::key`.**

Recommended: client builds cartesian rows with `id` from existing UUID when identity matches; save persists. Shared key helper in JS:

```js
export function variantIdentityKey(valueIds) {
    return [...valueIds].map(Number).sort((a, b) => a - b).join('-');
}
```

- [ ] **Step 4: Pass UI + Task 5 tests.**

- [ ] **Step 5: Commit** `feat: unify workspace attributes panel and explicit generate`

**Stop for human Wave 2 review.** Do not start Wave 3 until the workspace flow is approved (same bar as product-stock Task 7).

**Wave 2 human review (2026-09-07):** Start gate for Wave 3 is open. Human gate after **each** Wave 3 task (PDP, then filter, then cutover). Do not batch the three.

---

## Wave 3 — PDP, then filter, then cutover

Locked order (implementation, not a spec change):

```text
Task 7  PDP + variant selector     → review → human gate
Task 8  Shop filter                → review → human gate
Task 9  Relation-only cutover      → review → human gate
```

Do not merge these. If PDP breaks, filter must still be the old path so the regression source is obvious.

**Cutover is last.** Until PDP, shop filter, **and** workspace hydrate all read relations in production code, do **not** delete from runtime:

- `products.meta.variant_options`
- `product_variants.meta.options`
- `VariantOptionAttributeProvisioner`

even if the workspace save path no longer writes JSON (Wave 2). JSON may remain in the DB. Task 7–8 must not remove the provisioner class or strip JSON columns.

### Task 7: PDP spec and combination disable

**Files:**
- Modify: `modules/Cart/src/Services/ProductDetailBuilder.php` (`variantAxes`, `visibleAttributes`, variant option mapping)
- Modify: storefront selector if disable-vs-OOS is computed in the view/JS — `resources/views/components/storefront/forms/variant-axis-selector.blade.php`, `resources/js/storefront/product.js`
- Test: `tests/Unit/Cart/ProductDetailBuilderTest.php` (and a focused storefront JS/PHP test if the disable rule lives in the selector)

**Interfaces:**
- Consumes: relations from Tasks 2–5 (`product_attributes.used_for_variations`, variant PAV `attribute_value_id`); `StockPolicyEvaluator` from stock spec
- Produces: axes from variation attributes + values that appear on at least one variant; a value is **disabled** when, given the other selected axes, **no variant row exists** for that combination; spec list = visible non-axis product values + selected variant’s axis values
- Do not use `meta.specifications` or `variant_options` as SoT for this builder. Do not delete those keys or the provisioner.

**Acceptance (locked UX):** Color=Blue, Size=M exists; Blue-S does **not**. With Color=Blue selected: Size S is **disabled**, Size M is **enabled**. That is not “selectable then OOS”. Blue-M with qty 0 + deny remains selectable and not purchasable (`canFulfill`).

- [ ] **Step 1: Failing tests** — Blue-S missing → S disabled when Blue selected; Blue-M qty 0 deny still listed as a selectable combination; Material on product appears in spec; Color does not appear as a static “product is Red” spec.

- [ ] **Step 2: Run, fail**

- [ ] **Step 3: Implement. Keep stock-spec purchasability for OOS vs missing combo. Do not auto-switch to a nearby variant.**

- [ ] **Step 4: Pass unit + existing PDP stock tests (`ProductDetailBuilderTest`). Do not change `ShopProductQuery` / shop filters in this task.**

- [ ] **Step 5: Commit** `feat: drive pdp axes and specs from attribute relations`

**Stop for human Task 7 review.** Do not start Task 8 (shop filter) until approved.

---

### Task 8: Shop filters from relations

**Files:**
- Modify: `modules/Cart/src/Services/ShopProductQuery.php` (`applyAttributeGroupFilter`)
- Modify: `modules/Cart/src/Services/ShopFilterCatalogService.php` (`distinctValues`)
- Modify: `modules/Cart/src/Support/StorefrontAttributeFilterValue.php` only if still needed for legacy text; new path matches `attribute_values.code` (and label as fallback during migrate if any leftover text rows)
- Test: `tests/Feature/Storefront/StorefrontShopFilterChromeTest.php` or new `tests/Feature/Cart/ShopAttributeFilterTest.php`

**Interfaces:**
- Consumes: `product_attribute_values.attribute_value_id`, `product_attributes.used_for_variations`
- Produces: non-axis filter = product-level row; axis filter = `whereHas` variant PAV; facet values from `attribute_values.code`

- [ ] **Step 1: Failing test** — variable default Blue, extra variant Red; filter `color=red` includes the product. Simple Material=Cotton product-level row matches.

- [ ] **Step 2: Run, fail** (current filter ignores variant-scoped rows)

- [ ] **Step 3: Implement exists-on-variant for axes. Query URL uses code. Do not change PDP disable logic except if a shared helper is required.**

- [ ] **Step 4: Pass tests. `constrainInStock` unchanged.**

- [ ] **Step 5: Commit** `feat: filter shop products by attribute value relations`

**Stop for human Task 8 review.** Do not start Task 9 (cutover) until approved.

---

### Task 9: Cutover — relation-only I/O

**Files:**
- Modify: `ProductWorkspaceSaveService` — grep must be zero on save path for the provisioner
- Modify: `ProductWorkspaceStateBuilder` — hydrate from relations only
- Modify: PDP + shop filter if any JSON fallback remains
- Delete or leave unused: `VariantOptionAttributeProvisioner.php` (delete if no remaining callers)
- Test: grep-backed test or `tests/Feature/Product/CatalogV2CutoverTest.php`

**Interfaces:**
- Consumes: Tasks 7–8 plus Wave 2 hydrate already on relations
- Produces: save/hydrate/PDP/filter never read or write `variant_options` / `options` JSON. Provisioner has no runtime callers.

- [ ] **Step 1: Failing test** — after save, `products.meta` has no `variant_options` key (or it is absent/empty and ignored); `product_variants.meta.options` not written; `VariantOptionAttributeProvisioner` has no production callers.

- [ ] **Step 2: Run, fail** if provisioner or JSON read still hooked

- [ ] **Step 3: Remove write/read. Keep JSON in DB until ops delete it; code must ignore it.**

- [ ] **Step 4: Run `php vendor/bin/phpunit tests/Feature/Product tests/Feature/Catalog tests/Feature/Cart tests/Unit/Cart/ProductDetailBuilderTest.php tests/Unit/Product`**

- [ ] **Step 5: Commit** `feat: cut over catalog variants to relation-only read and write`

**Stop for human Wave 3 / Phase 1 review.** Search, CSV, API, SEO stay out of this plan.

---

## Spec coverage (plan self-review)

| Spec | Task |
|---|---|
| `attribute_values` + `code` | 1 |
| `code` immutable after create | 1 |
| `product_attributes` even without values | 2, 5 |
| Canonical identity | 4 |
| Idempotent generate | 4, 6 |
| No product-level spec on axes | 5, 7 |
| Publish needs complete identity | 5 |
| Explicit generate + axis-change warning | 6 |
| PDP disable missing combo | 7 |
| Filter any-matching-variant | 8 |
| Relation-only I/O | 9 |
| Migration skip log keys | 3 |
| No auto-create attributes | 3 |
| Stock/SKU/type untouched | all |

---

## Out of this plan

Search index, CSV, public API redesign, JSON-LD, deleting the unused `attributes.options` column, product-specific attribute names.
