# Attribute Recovery Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Normalize imported free-text product attributes into Catalog V2 `attribute_values` + `attribute_value_id` so shop filters, admin select lists, PDP spec collections, and search all read the same relations.

**Architecture:** One `AttributeTokenNormalizer` (comma split + size/age canonicalize) and one `ProductAttributeValueLinker` (resolve-or-create catalog values, write PAV FKs). Wire those into import/`ProductService` first. Then an artisan recovery command audits, canonicalizes size/age, links existing rows, flips `type=select`, and can restore from backup tables. Workspace checkboxes and PDP `values[]` follow so multi-value specs survive.

**Tech Stack:** Laravel 13, PHP 8.4, PHPUnit, existing Catalog V2 relations, `php artisan product:reindex`

**Spec:** `docs/superpowers/specs/attribute-recovery-design.md` (Approved)

## Global Constraints

- Do not touch taxonomy (30 categories), `product_categories`, Category Navigation V1, PDP breadcrumb/badge, Suggest Enhancement, or stub JPEG recovery.
- Do not change `attributes.code` (ภาษา stays `attr_0ebb640bb9a7`).
- Do not mark recovered attributes `used_for_variations` or generate variant matrices.
- Do not implode PDP spec values into a comma string.
- Size (`size_top`, `size_bottom`, `size`) and `age` must be canonicalized before `type=select`.
- Durable writers ship before the recovery command.
- Recovery reads `product_attribute_values.value`, not `doc/products-woocommerce-template.csv`.
- Do not `--force` WooCommerce catalog import as a recovery mechanism.
- Do not commit `doc/` unless the user asks.

---

## File map

| Area | Files |
|---|---|
| Normalizer | Create `modules/Product/src/Support/AttributeTokenNormalizer.php` |
| Linker | Create `modules/Product/src/Services/ProductAttributeValueLinker.php` |
| Writers | `modules/Product/src/Services/ProductService.php`, `modules/Product/src/Services/ProductWorkspaceSaveService.php`, `modules/Product/src/Import/WooCommerceProductImporter.php` (ensureDefaultAttributes `select`) |
| Recovery | Create `modules/Product/src/Services/RecoverProductAttributeValues.php`, `modules/Product/src/Console/RecoverProductAttributeValuesCommand.php`, `modules/Product/src/Console/RestoreProductAttributeValuesCommand.php` |
| Register | `modules/Product/src/ProductServiceProvider.php` |
| Workspace | `resources/js/admin/product-workspace/attributes-panel.js` |
| PDP | `packages/commerce/contracts/src/Storefront/ProductDetailData.php`, `modules/Cart/src/Services/ProductDetailBuilder.php`, `modules/Cart/resources/views/storefront/product.blade.php`, `resources/css/storefront/pdp.css` |
| Facets | `resources/views/components/storefront/shop/filters-form.blade.php` |
| Tests | `tests/Unit/Product/AttributeTokenNormalizerTest.php`, `tests/Feature/Product/ProductAttributeValueLinkerTest.php`, `tests/Feature/Product/RecoverProductAttributeValuesTest.php`, `tests/Feature/Product/ProductCsvImportTest.php`, `tests/Unit/Cart/ProductDetailBuilderTest.php`, `tests/Feature/Storefront/ShopFacetUniverseTest.php` |

Do not edit `ProductSuggestQuery`, category nav, or PDP breadcrumb builders except `visibleAttributes`.

---

### Task 1: AttributeTokenNormalizer

**Files:**
- Create: `modules/Product/src/Support/AttributeTokenNormalizer.php`
- Test: `tests/Unit/Product/AttributeTokenNormalizerTest.php`

**Interfaces:**
- Consumes: raw PAV `value` strings and `attributes.code`
- Produces:

```php
final class AttributeTokenNormalizer
{
    /** @return list<string> */
    public function tokens(string $raw, string $attributeCode): array {}

    public function canonicalize(string $token, string $attributeCode): string {}
}
```

`SIZE_CODES = ['size_top', 'size_bottom', 'size']`. Age code is `age`.

- [ ] **Step 1: Write the failing test**

Create `tests/Unit/Product/AttributeTokenNormalizerTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Product;

use Commerce\Product\Support\AttributeTokenNormalizer;
use PHPUnit\Framework\TestCase;

final class AttributeTokenNormalizerTest extends TestCase
{
    private AttributeTokenNormalizer $normalizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->normalizer = new AttributeTokenNormalizer();
    }

    public function test_splits_ascii_and_fullwidth_commas(): void
    {
        $this->assertSame(
            ['สีฟ้า', 'สีเทา'],
            $this->normalizer->tokens('สีฟ้า, สีเทา', 'color'),
        );
        $this->assertSame(
            ['ชาย', 'หญิง'],
            $this->normalizer->tokens('ชาย，หญิง', 'gender'),
        );
    }

    public function test_size_collapses_whitespace_and_thai_units(): void
    {
        $this->assertSame(['4-5Y'], $this->normalizer->tokens('4-5 Y', 'size_top'));
        $this->assertSame(['12-18M'], $this->normalizer->tokens('12-18 เดือน', 'size_bottom'));
        $this->assertSame(['2-3Y'], $this->normalizer->tokens('2-3 ปี', 'size_bottom'));
        $this->assertSame(['5T'], $this->normalizer->tokens('5t', 'size_top'));
        $this->assertSame(['XS'], $this->normalizer->tokens('xs', 'size_top'));
        $this->assertSame(['90CM'], $this->normalizer->tokens('90cm', 'size_top'));
        $this->assertSame(['29-31'], $this->normalizer->tokens('29-31', 'size'));
    }

    public function test_age_keeps_thai_units(): void
    {
        $this->assertSame(['12เดือน'], $this->normalizer->tokens('12 เดือน', 'age'));
        $this->assertSame(['2ปี'], $this->normalizer->tokens('2 ปี', 'age'));
        $this->assertNotSame(['12M'], $this->normalizer->tokens('12เดือน', 'age'));
    }

    public function test_does_not_map_size_month_onto_age(): void
    {
        $this->assertSame(['12M'], $this->normalizer->tokens('12M', 'size_top'));
        $this->assertSame(['12เดือน'], $this->normalizer->tokens('12เดือน', 'age'));
    }

    public function test_keeps_mai_mee_and_drops_empty_tokens(): void
    {
        $this->assertSame(['ไม่มี'], $this->normalizer->tokens('ไม่มี', 'size_top'));
        $this->assertSame([], $this->normalizer->tokens(' , , ', 'color'));
    }

    public function test_dedupes_case_folded_tokens_within_one_value(): void
    {
        $this->assertSame(['Good'], $this->normalizer->tokens('Good, good', 'condition'));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Unit/Product/AttributeTokenNormalizerTest.php`

Expected: FAIL (class not found)

- [ ] **Step 3: Write minimal implementation**

Create `modules/Product/src/Support/AttributeTokenNormalizer.php`:

```php
<?php

declare(strict_types=1);

namespace Commerce\Product\Support;

use Normalizer;

final class AttributeTokenNormalizer
{
    private const SIZE_CODES = ['size_top', 'size_bottom', 'size'];

    /**
     * @return list<string>
     */
    public function tokens(string $raw, string $attributeCode): array
    {
        $nfc = Normalizer::normalize($raw, Normalizer::FORM_C) ?: $raw;
        $parts = preg_split('/\s*[,，]\s*/u', $nfc) ?: [];
        $seen = [];
        $out = [];

        foreach ($parts as $part) {
            $canonical = $this->canonicalize($part, $attributeCode);
            if ($canonical === '') {
                continue;
            }
            $fold = mb_strtolower($canonical);
            if (isset($seen[$fold])) {
                continue;
            }
            $seen[$fold] = true;
            $out[] = $canonical;
        }

        return $out;
    }

    public function canonicalize(string $token, string $attributeCode): string
    {
        $nfc = Normalizer::normalize($token, Normalizer::FORM_C) ?: $token;
        $trimmed = trim($nfc);
        if ($trimmed === '') {
            return '';
        }

        $compact = preg_replace('/\s+/u', '', $trimmed) ?? $trimmed;
        $compact = preg_replace('/\s*-\s*/u', '-', $compact) ?? $compact;

        if ($attributeCode === 'age') {
            return $this->uppercaseLatin($compact);
        }

        if (in_array($attributeCode, self::SIZE_CODES, true)) {
            $compact = preg_replace('/เดือน$/u', 'M', $compact) ?? $compact;
            $compact = preg_replace('/ปี$/u', 'Y', $compact) ?? $compact;
            $compact = preg_replace('/cm$/iu', 'CM', $compact) ?? $compact;

            return $this->uppercaseLatin($compact);
        }

        return $trimmed;
    }

    private function uppercaseLatin(string $token): string
    {
        return preg_replace_callback('/[A-Za-z]+/', static fn (array $m): string => strtoupper($m[0]), $token) ?? $token;
    }
}
```

First-seen casing for color: split uses canonicalize which for non-size returns `$trimmed` (not case-folded label). Dedupe uses case-fold so `Good, good` keeps `Good`.

- [ ] **Step 4: Run the tests and make sure they pass**

Run: `php artisan test tests/Unit/Product/AttributeTokenNormalizerTest.php`

Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add modules/Product/src/Support/AttributeTokenNormalizer.php tests/Unit/Product/AttributeTokenNormalizerTest.php
git commit -m "$(cat <<'EOF'
feat: add attribute token normalizer for recovery and import

EOF
)"
```

---

### Task 2: ProductAttributeValueLinker

**Files:**
- Create: `modules/Product/src/Services/ProductAttributeValueLinker.php`
- Modify: `modules/Product/src/Services/ProductWorkspaceSaveService.php` (delegate `syncProductLevelValues` / `resolveOrCreateAttributeValue` if that avoids duplication — prefer moving those two privates onto the linker and calling them from the workspace service)
- Test: `tests/Feature/Product/ProductAttributeValueLinkerTest.php`

**Interfaces:**
- Consumes: `AttributeTokenNormalizer`, `AttributeValueService::allocateCode`, `Attribute` / `AttributeValue` / `Product`
- Produces:

```php
final class ProductAttributeValueLinker
{
    /**
     * @param  array<int, mixed>  $valuesByAttributeId  attribute id => raw string|list
     */
    public function syncProductLevel(Product $product, array $valuesByAttributeId): void {}
}
```

Each raw value is normalized with that attribute’s `code`. One PAV per token with `attribute_value_id` set and `value` = catalog label. Deletes stale product-level rows for those attribute ids (including null-FK leftovers).

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Product;

use Commerce\Catalog\Models\Attribute;
use Commerce\Product\Models\ProductAttributeValue;
use Commerce\Product\Services\ProductAttributeValueLinker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class ProductAttributeValueLinkerTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    public function test_splits_color_and_sets_attribute_value_id(): void
    {
        $product = $this->createPurchasableProduct(sku: 'LNK-TEE')->product;
        $color = Attribute::query()->create([
            'code' => 'color',
            'name' => 'สี',
            'type' => 'text',
            'is_filterable' => true,
            'is_visible' => true,
        ]);

        app(ProductAttributeValueLinker::class)->syncProductLevel($product, [
            $color->id => 'สีฟ้า, สีเทา',
        ]);

        $rows = ProductAttributeValue::query()
            ->where('product_id', $product->id)
            ->where('attribute_id', $color->id)
            ->whereNull('product_variant_id')
            ->with('attributeValue')
            ->get();

        $this->assertCount(2, $rows);
        $this->assertTrue($rows->every(fn ($row) => $row->attribute_value_id !== null));
        $this->assertEqualsCanonicalizing(
            ['สีฟ้า', 'สีเทา'],
            $rows->pluck('attributeValue.label')->all(),
        );
    }

    public function test_size_top_canonicalizes_before_insert(): void
    {
        $product = $this->createPurchasableProduct(sku: 'LNK-SIZE')->product;
        $size = Attribute::query()->create([
            'code' => 'size_top',
            'name' => 'Size (เสื้อ)',
            'type' => 'text',
            'is_filterable' => true,
            'is_visible' => true,
        ]);

        app(ProductAttributeValueLinker::class)->syncProductLevel($product, [
            $size->id => '4-5 Y',
        ]);

        $row = ProductAttributeValue::query()
            ->where('product_id', $product->id)
            ->where('attribute_id', $size->id)
            ->first();

        $this->assertNotNull($row?->attribute_value_id);
        $this->assertSame('4-5Y', $row->attributeValue?->label);
    }

    public function test_second_sync_is_idempotent(): void
    {
        $product = $this->createPurchasableProduct(sku: 'LNK-IDEM')->product;
        $color = Attribute::query()->create([
            'code' => 'color',
            'name' => 'สี',
            'type' => 'text',
            'is_filterable' => true,
            'is_visible' => true,
        ]);
        $linker = app(ProductAttributeValueLinker::class);
        $linker->syncProductLevel($product, [$color->id => 'สีฟ้า']);
        $linker->syncProductLevel($product, [$color->id => 'สีฟ้า']);

        $this->assertSame(1, ProductAttributeValue::query()->where('product_id', $product->id)->count());
        $this->assertSame(1, \Commerce\Catalog\Models\AttributeValue::query()->where('attribute_id', $color->id)->count());
    }
}
```

`CreatesPurchasableProduct::createPurchasableProduct()` returns the default `ProductVariant`; use `->product` for the parent.

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Product/ProductAttributeValueLinkerTest.php`

Expected: FAIL

- [ ] **Step 3: Write the linker**

Implement `syncProductLevel`:

1. For each attribute id, load `Attribute` (skip missing).
2. If value is array, join is wrong — instead flatten each element through `tokens()`.
3. `resolveOrCreate` by `attribute_id` + `LOWER(label)` (same as workspace today) with `allocateCode`.
4. Reuse the body of `ProductWorkspaceSaveService::syncProductLevelValues` + `upsertProductAttributeValue`.

Keep `used_for_variations` untouched.

- [ ] **Step 4: Run the tests and make sure they pass**

Run: `php artisan test tests/Feature/Product/ProductAttributeValueLinkerTest.php tests/Unit/Product/AttributeTokenNormalizerTest.php`

Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add modules/Product/src/Services/ProductAttributeValueLinker.php tests/Feature/Product/ProductAttributeValueLinkerTest.php modules/Product/src/Services/ProductWorkspaceSaveService.php
git commit -m "$(cat <<'EOF'
feat: link product attribute text to catalog values

EOF
)"
```

---

### Task 3: Durable writers (before recovery)

**Files:**
- Modify: `modules/Product/src/Services/ProductService.php` (`syncAttributeValues` → linker)
- Modify: `modules/Product/src/Services/ProductWorkspaceSaveService.php` (`syncProductAttributeValues` → linker)
- Modify: `modules/Product/src/Import/WooCommerceProductImporter.php` (`ensureDefaultAttributes` / on-the-fly create: `type: 'select'` not `text`)
- Modify: `tests/Feature/Product/ProductCsvImportTest.php`

**Interfaces:**
- Consumes: `ProductAttributeValueLinker::syncProductLevel`
- Produces: CSV / create-product writes with non-null `attribute_value_id`

- [ ] **Step 1: Write the failing CSV assertion**

In `ProductCsvImportTest`, add:

```php
public function test_import_writes_attribute_value_id_for_color(): void
{
    $this->importCsv($this->makeCsv([
        $this->csvRow([
            'ID' => '80',
            'SKU' => 'CSV-ATTR-FK-1',
            'Name' => 'Blue Tee',
            'Attribute 1 name' => 'สี',
            'Attribute 1 value(s)' => 'สีฟ้า, สีเทา',
        ]),
    ]));

    $product = ProductVariant::query()->where('sku', 'CSV-ATTR-FK-1')->first()?->product;
    $this->assertNotNull($product);
    $rows = ProductAttributeValue::query()
        ->where('product_id', $product->id)
        ->whereHas('attribute', fn ($q) => $q->where('code', 'color'))
        ->get();
    $this->assertCount(2, $rows);
    $this->assertTrue($rows->every(fn ($row) => $row->attribute_value_id !== null));
}

public function test_import_canonicalizes_size_top(): void
{
    $this->importCsv($this->makeCsv([
        $this->csvRow([
            'ID' => '81',
            'SKU' => 'CSV-ATTR-SIZE-1',
            'Name' => 'Sized Tee',
            'Attribute 1 name' => 'Size (เสื้อ)',
            'Attribute 1 value(s)' => '4-5 Y',
        ]),
    ]));

    $product = ProductVariant::query()->where('sku', 'CSV-ATTR-SIZE-1')->first()?->product;
    $this->assertNotNull($product);
    $row = $product->attributeValues()->whereHas('attribute', fn ($q) => $q->where('code', 'size_top'))->first();
    $this->assertSame('4-5Y', $row?->attributeValue?->label);
}
```

Tighten `assertHasAttributeValue` to also require `attribute_value_id !== null` **or** add a sibling assertion so old tests fail if only `value` is set.

- [ ] **Step 2: Run the new tests to verify they fail**

Run: `php artisan test tests/Feature/Product/ProductCsvImportTest.php --filter=test_import_writes_attribute_value_id`

Expected: FAIL (`attribute_value_id` null)

- [ ] **Step 3: Wire writers**

`ProductService::syncAttributeValues` and `ProductWorkspaceSaveService::syncProductAttributeValues` call the linker (do not keep a text-only insert).

Importer `CreateAttributeData` type `'select'`.

When `productAttributes !== []`, workspace already uses FK path; leave that branch, still run linker for empty `productAttributes`.

- [ ] **Step 4: Run CSV + linker tests**

Run: `php artisan test tests/Feature/Product/ProductCsvImportTest.php tests/Feature/Product/ProductAttributeValueLinkerTest.php`

Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add modules/Product/src/Services/ProductService.php modules/Product/src/Services/ProductWorkspaceSaveService.php modules/Product/src/Import/WooCommerceProductImporter.php tests/Feature/Product/ProductCsvImportTest.php
git commit -m "$(cat <<'EOF'
feat: write catalog attribute FKs on import and product save

EOF
)"
```

---

### Task 4: Recovery command (audit, normalize, apply, rollback)

**Files:**
- Create: `modules/Product/src/Services/RecoverProductAttributeValues.php`
- Create: `modules/Product/src/Console/RecoverProductAttributeValuesCommand.php`
- Create: `modules/Product/src/Console/RestoreProductAttributeValuesCommand.php`
- Modify: `modules/Product/src/ProductServiceProvider.php` (register both commands)
- Test: `tests/Feature/Product/RecoverProductAttributeValuesTest.php`

**Interfaces:**
- Consumes: linker, normalizer, `ProductSearchIndexer`
- Produces:

```php
final class RecoverProductAttributeValues
{
    public function audit(): array {} // size/age raw vs canonical counts
    public function apply(bool $dryRun = false): array {}
    public function restore(string $suffix): void {}
}
```

Covered names (match `attributes.name`): สี, เพศ, Size (เสื้อ), Size (กางเกง), อายุ, สภาพ, ภาษา, Size (รองเท้า).

Apply order: backup tables → rewrite size/age `value` to a single canonical token (null FK) → `syncProductLevel` per product for remaining raw/comma rows → `type=select` on all eight → attach ภาษา + Size (รองเท้า) to `woocommerce_default` → `ProductAttributeSetSync` for products on that set → reindex touched products.

`type=select` for `size_top`/`size_bottom`/`size`/`age` only after the normalize rewrite in this same apply.

Backup names: `_bak_product_attribute_values_attr_recovery_{Ymd}`, `_bak_attributes_attr_recovery_{Ymd}`, `_bak_attribute_set_attributes_attr_recovery_{Ymd}`. If the PAV table exists, abort unless a new suffix is used.

- [ ] **Step 1: Write failing tests**

```php
public function test_audit_reports_size_collapse_without_writing(): void
{
    // product with size_top value "4-5 Y"
    $beforeTypes = Attribute::query()->where('code', 'size_top')->value('type');
    app(RecoverProductAttributeValues::class)->audit();
    $this->assertSame($beforeTypes, Attribute::query()->where('code', 'size_top')->value('type'));
    $this->assertSame(0, \Commerce\Catalog\Models\AttributeValue::query()->count());
}

public function test_apply_links_color_and_flips_select(): void
{
    // color PAV "สีฟ้า, สีเทา"
    app(RecoverProductAttributeValues::class)->apply();
    $this->assertSame('select', Attribute::query()->where('code', 'color')->value('type'));
    $this->assertSame(0, ProductAttributeValue::query()->where('attribute_id', $colorId)->whereNull('attribute_value_id')->count());
}

public function test_apply_does_not_select_size_until_canonical(): void
{
    // covered by apply() internal order: after apply, label is 4-5Y not "4-5 Y"
}

public function test_apply_attaches_language_and_shoe_size_to_set(): void
{
    // after apply, woocommerce_default has attribute ids for ภาษา and Size (รองเท้า)
}

public function test_second_apply_is_idempotent(): void
{
    app(RecoverProductAttributeValues::class)->apply();
    $count = ProductAttributeValue::query()->count();
    app(RecoverProductAttributeValues::class)->apply();
    $this->assertSame($count, ProductAttributeValue::query()->count());
}

public function test_restore_reverts_pav_and_types(): void
{
    $suffix = app(RecoverProductAttributeValues::class)->apply()['suffix'];
    app(RecoverProductAttributeValues::class)->restore($suffix);
    $this->assertSame('text', Attribute::query()->where('code', 'color')->value('type'));
    $this->assertSame(0, \Commerce\Catalog\Models\AttributeValue::query()->count());
}
```

Use `CreatesPurchasableProduct`, seed WooCommerce set like importer tests (`ProductCsvImportTest` attribute-set setup).

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test tests/Feature/Product/RecoverProductAttributeValuesTest.php`

Expected: FAIL

- [ ] **Step 3: Implement service + commands**

Command signatures:

```
product:recover-attribute-values {--audit : Report size/age tokens only} {--dry-run : Print plan without writing} {--force : Use a new backup suffix if dated tables exist}
product:restore-attribute-values {suffix : Backup suffix e.g. 20260909}
```

`--audit` and `--dry-run` must not write.

Chunk by `product_id`. On unique failure, fail the chunk; never delete the last text row until FK rows exist.

- [ ] **Step 4: Run recovery + CSV + linker tests**

Run: `php artisan test tests/Feature/Product/RecoverProductAttributeValuesTest.php tests/Feature/Product/ProductCsvImportTest.php tests/Feature/Product/ProductAttributeValueLinkerTest.php`

Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add modules/Product/src/Services/RecoverProductAttributeValues.php modules/Product/src/Console/RecoverProductAttributeValuesCommand.php modules/Product/src/Console/RestoreProductAttributeValuesCommand.php modules/Product/src/ProductServiceProvider.php tests/Feature/Product/RecoverProductAttributeValuesTest.php
git commit -m "$(cat <<'EOF'
feat: recover imported attribute text into catalog values

EOF
)"
```

---

### Task 5: Workspace checkboxes for spec selects

**Files:**
- Modify: `resources/js/admin/product-workspace/attributes-panel.js` (`renderFields`: non-axis select uses checkbox)
- Test: add a Feature test that the built JS source contains checkbox for spec, **or** a small Node/PHPUnit string assertion on the file (this repo already uses file-content isolation tests). Prefer `tests/Unit/Product/WorkspaceAttributesPanelTest.php` reading the JS file:

```php
public function test_non_axis_select_uses_checkbox(): void
{
    $js = file_get_contents(base_path('resources/js/admin/product-workspace/attributes-panel.js'));
    $this->assertNotFalse($js);
    $this->assertStringContainsString("const inputType = usedForVariations ? 'checkbox' : 'checkbox'", $js);
}
```

Better: change the code to `const inputType = 'checkbox';` for all select options (variation already checkbox). Radio must disappear.

- [ ] **Step 1: Failing test** that JS no longer contains `radio` for attribute values

```php
$this->assertStringNotContainsString("inputType = usedForVariations ? 'checkbox' : 'radio'", $js);
$this->assertStringContainsString("type=\"checkbox\"", $js);
```

- [ ] **Step 2: Run to verify fail or already fail after expecting no radio**

Run: `php artisan test tests/Unit/Product/WorkspaceAttributesPanelTest.php`

- [ ] **Step 3: Set `const inputType = 'checkbox'`** (variation and spec)

- [ ] **Step 4: Pass + existing ProductSuggestQueryTest still green (untouched)**

Run: `php artisan test tests/Unit/Product/WorkspaceAttributesPanelTest.php tests/Feature/Product/ProductSuggestQueryTest.php`

Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add resources/js/admin/product-workspace/attributes-panel.js tests/Unit/Product/WorkspaceAttributesPanelTest.php
git commit -m "$(cat <<'EOF'
fix: allow multiple spec attribute values in product workspace

EOF
)"
```

---

### Task 6: PDP spec collection + hide empty facets

**Files:**
- Modify: `packages/commerce/contracts/src/Storefront/ProductDetailData.php` phpdoc `$attributes`
- Modify: `modules/Cart/src/Services/ProductDetailBuilder.php` `visibleAttributes`
- Modify: `modules/Cart/resources/views/storefront/product.blade.php`
- Modify: `resources/css/storefront/pdp.css`
- Modify: `resources/views/components/storefront/shop/filters-form.blade.php`
- Modify: `tests/Unit/Cart/ProductDetailBuilderTest.php`
- Modify: `tests/Feature/Storefront/ShopFacetUniverseTest.php` (or new `tests/Feature/Storefront/ShopEmptyFacetChromeTest.php`)

**Interfaces:**
- Produces: `list<array{label: string, values: list<string>}>`
- Blade loops `$attribute['values']`. No `implode`.

- [ ] **Step 1: Failing PDP tests**

Replace `test_spec_list_uses_visible_non_axis_and_selected_variant_values` expectations:

```php
$this->assertContains(['label' => 'Material', 'values' => ['Cotton']], $data->attributes);
$this->assertContains(['label' => 'Color', 'values' => ['Blue']], $data->attributes);
$this->assertContains(['label' => 'Size', 'values' => ['M']], $data->attributes);
```

Add:

```php
public function test_spec_list_collects_multiple_non_axis_values(): void
{
    // simple product, color PAV two FKs สีฟ้า + สีเทา
    $data = app(ProductDetailBuilder::class)->fromSlug($product->slug);
    $color = collect($data->attributes)->firstWhere('label', 'สี');
    $this->assertSame(['สีฟ้า', 'สีเทา'], $color['values']);
    $this->assertArrayNotHasKey('value', $color);
}
```

Empty facet: create `is_filterable` attribute with zero `attribute_values` / zero counts, GET `/shop`, `assertDontSee` that attribute name in the filters sidebar (use a unique name `ZZEmptyFacet`).

- [ ] **Step 2: Run to verify fail**

Run: `php artisan test tests/Unit/Cart/ProductDetailBuilderTest.php --filter=test_spec_list`

Expected: FAIL on `value` key

- [ ] **Step 3: Implement**

`visibleAttributes`: group by attribute id; skip axes for the extra loop; for non-axis collect all product-level labels ordered by `attributeValue.position`; for axis one-element list from selected variant.

Blade:

```blade
<dt class="storefront-pdp-spec-list__label">{{ $attribute['label'] }}</dt>
<dd class="storefront-pdp-spec-list__value">
    <ul class="storefront-pdp-spec-list__values">
        @foreach ($attribute['values'] as $value)
            <li>{{ $value }}</li>
        @endforeach
    </ul>
</dd>
```

CSS: list with no bullets, wrap gap — not a comma.

`filters-form.blade.php`: `@continue` when `$facetOptions === []`.

- [ ] **Step 4: Run PDP + shop facet + suggest**

Run: `php artisan test tests/Unit/Cart/ProductDetailBuilderTest.php tests/Feature/Storefront/ShopFacetUniverseTest.php tests/Feature/Storefront/StorefrontProductTest.php tests/Feature/Product/ProductSuggestQueryTest.php`

Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add packages/commerce/contracts/src/Storefront/ProductDetailData.php modules/Cart/src/Services/ProductDetailBuilder.php modules/Cart/resources/views/storefront/product.blade.php resources/css/storefront/pdp.css resources/views/components/storefront/shop/filters-form.blade.php tests/Unit/Cart/ProductDetailBuilderTest.php tests/Feature/Storefront/ShopFacetUniverseTest.php
git commit -m "$(cat <<'EOF'
feat: render PDP spec values as a list and hide empty shop facets

EOF
)"
```

---

### Task 7: Live catalog apply + verification (human gate)

**Files:** none in git except optional local notes (do not commit `doc/` unless asked)

- [ ] **Step 1: Dry-run and audit against `commerce_framework`**

```bash
php artisan product:recover-attribute-values --audit
php artisan product:recover-attribute-values --dry-run
```

Expected: size/age collapses printed; no table writes; `attribute_values` still 0 if apply has not run.

- [ ] **Step 2: Apply once**

```bash
php artisan product:recover-attribute-values
```

Expected: PAV null FK for the eight = 0; eight types `select`; set includes ภาษา + Size (รองเท้า).

- [ ] **Step 3: Reindex if the command did not**

```bash
php artisan product:reindex
```

- [ ] **Step 4: Manual checks**

- `/shop` — chips for สี, เพศ, sizes, อายุ, สภาพ; no empty legends
- `?color=` a recovered code lists a known product
- `/admin/products/{uuid}/edit` Organization — eight selects; multi-color product has several chips
- PDP — two colors as two list items, not `สีฟ้า, สีเทา`
- Search — a color label finds a product
- Suggest — unchanged
- Category tree / counts — unchanged

- [ ] **Step 5: Do not commit the database. Commit only remaining code from Tasks 1–6 if any.**

---

## Spec coverage

| Spec | Task |
|---|---|
| Durable writers first | Task 3 (after 1–2) |
| Size/age canonicalize before select | Task 1 rules + Task 4 apply order |
| PDP `values[]` collection | Task 6 |
| Recovery backup/rollback | Task 4 |
| Set attach ภาษา / shoe size | Task 4 |
| Workspace multi spec | Task 5 |
| Hide empty facets | Task 6 |
| Reindex | Task 4 apply + Task 7 |
| Suggest / taxonomy / nav / breadcrumb | Global constraints — no task |

## Placeholder scan

No TBD. Linker helper names in Task 2 must match `CreatesPurchasableProduct` when implementing.

## Type consistency

- `AttributeTokenNormalizer::tokens(string $raw, string $attributeCode): list<string>`
- `ProductAttributeValueLinker::syncProductLevel(Product $product, array $valuesByAttributeId): void`
- `ProductDetailData` attributes: `list<array{label: string, values: list<string>}>`
- Recovery `apply(): array{suffix: string, ...}`
