# Catalog V2 Importer Reserved-Code Skip (F2b) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** WooCommerce CSV import skips `Attribute 1–4 name` columns whose slugged code is in `SearchReservedParams::KEYS`, continues the product, and reports a warning without incrementing `skipped` or `errors`.

**Architecture:** Pre-check `attributeCode($name)` against `KEYS` **before** `attributeCache` lookup. Record a warning; do not create or attach. CLI and admin emit the same copy. `AttributeService` is unchanged. Do not catch `DomainException` to skip.

**Tech Stack:** Laravel 13, PHP 8.4, PHPUnit, existing `WooCommerceProductImporter` + `ProductCsvImportResult`

**Spec:** `docs/superpowers/specs/2026-09-08-catalog-v2-importer-reserved-code-design.md` (Locked)

**Start gate:** Locked spec on `main`. Work on `feat/catalog-v2-importer-reserved-code`.

## Global Constraints

- Skip lives in `WooCommerceProductImporter::resolveAttributeValues` only.
- Order is mandatory: `attributeCode(name)` → if in `KEYS`, warn and `continue` **before** cache lookup → then cache / create / attach.
- Always skip reserved codes even when a legacy `attributes` row exists and is preloaded into `attributeCache` via the WooCommerce attribute set.
- `AttributeService` is not modified. Do not catch `DomainException` as the skip path.
- Non-reserved create failures continue to fail the row exactly as today.
- Warning copy is exactly `Row {rowId}: skipped reserved attribute column "{name}" (code "{code}").`
- Admin uses `ProductCsvImportResult::withMessage()` (no counter increment). Do not use `withSkipped()` for this.
- CLI writes the same copy via `$output->writeln('<comment>'.$message.'</comment>')`.
- Messages may repeat per row. Deduplication is not required.
- `KEYS` stays `['q', 'category', 'brand', 'sort', 'availability', 'price_min', 'price_max', 'page']`.
- Do not change `buildVariantOptions`, CSV `Brands` / `resolveBrandUuid`, dry-run, Octane, index ops, or Suggest.
- Human gate after the wave.

---

## File map

| Area | Files |
|---|---|
| Result | Modify `modules/Product/src/Import/ProductCsvImportResult.php` |
| Importer | Modify `modules/Product/src/Import/WooCommerceProductImporter.php` |
| Unit test | Create `tests/Unit/Product/ProductCsvImportResultTest.php` |
| Feature tests | Modify `tests/Feature/Product/ProductCsvImportTest.php` |
| Regression | Run existing `tests/Feature/Catalog/AttributeReservedCodeTest.php` and `tests/Feature/Catalog/VariantOptionReservedCodeTest.php` (do not change them unless they fail for an unrelated reason — then stop) |

Do not modify `AttributeService`, FormRequests, or `SearchReservedParams`.

**CSV companion column:** default WooCommerce attributes include `code = color` with **name `สี`**. A CSV column named `Color` cache-misses and would `create(code: color)` against that unique row and fail the product. Use `สี` wherever the spec says Color / สี.

---

### Task 1: `withMessage()` does not change counters

**Files:**
- Create: `tests/Unit/Product/ProductCsvImportResultTest.php`
- Modify: `modules/Product/src/Import/ProductCsvImportResult.php`

**Interfaces:**
- Consumes: existing `ProductCsvImportResult` constructor fields.
- Produces: `withMessage(string $message): self` — appends `$message` to `messages`; leaves `created`, `updated`, `skipped`, `duplicates`, `linkedImages`, `duplicateSkus`, and `errors` unchanged.

- [ ] **Step 1: Write the failing unit test**

Create `tests/Unit/Product/ProductCsvImportResultTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Product;

use Commerce\Product\Import\ProductCsvImportResult;
use Tests\TestCase;

final class ProductCsvImportResultTest extends TestCase
{
    public function test_with_message_appends_message_and_does_not_change_counters(): void
    {
        $original = new ProductCsvImportResult(
            created: 2,
            updated: 3,
            skipped: 4,
            duplicates: 5,
            linkedImages: 6,
            messages: ['existing'],
            duplicateSkus: ['SKU-1'],
            errors: ['err'],
        );

        $next = $original->withMessage('Row 1: skipped reserved attribute column "Brand" (code "brand").');

        $this->assertSame(2, $next->created);
        $this->assertSame(3, $next->updated);
        $this->assertSame(4, $next->skipped);
        $this->assertSame(5, $next->duplicates);
        $this->assertSame(6, $next->linkedImages);
        $this->assertSame(['SKU-1'], $next->duplicateSkus);
        $this->assertSame(['err'], $next->errors);
        $this->assertSame(
            [
                'existing',
                'Row 1: skipped reserved attribute column "Brand" (code "brand").',
            ],
            $next->messages,
        );

        $this->assertSame(2, $original->created);
        $this->assertSame(['existing'], $original->messages);
        $this->assertSame(2 + 3 + 4 + 5, $next->totalProcessed());
    }
}
```

- [ ] **Step 2: Run the test and confirm it fails**

```bash
php artisan test --compact tests/Unit/Product/ProductCsvImportResultTest.php
```

Expected: FAIL (`withMessage` is not defined).

- [ ] **Step 3: Add `withMessage()`**

Add to `modules/Product/src/Import/ProductCsvImportResult.php` after `withSkipped()`:

```php
public function withMessage(string $message): self
{
    return new self(
        created: $this->created,
        updated: $this->updated,
        skipped: $this->skipped,
        duplicates: $this->duplicates,
        linkedImages: $this->linkedImages,
        messages: [...$this->messages, $message],
        duplicateSkus: $this->duplicateSkus,
        errors: $this->errors,
    );
}
```

Do not increment any int field. Do not copy `withSkipped` and leave `skipped + 1`.

- [ ] **Step 4: Run the test and confirm it passes**

```bash
php artisan test --compact tests/Unit/Product/ProductCsvImportResultTest.php
```

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add tests/Unit/Product/ProductCsvImportResultTest.php modules/Product/src/Import/ProductCsvImportResult.php
git commit -m "feat: add ProductCsvImportResult withMessage without counters"
```

---

### Task 2: Skip reserved Attribute N columns before cache lookup

**Files:**
- Modify: `modules/Product/src/Import/WooCommerceProductImporter.php`
- Modify: `tests/Feature/Product/ProductCsvImportTest.php`

**Interfaces:**
- Consumes: `attributeCode($name)`, `Commerce\Support\SearchReservedParams::KEYS`, `rowId($row)`, `withMessage()`.
- Produces: reserved Attribute N columns skipped before `attributeCache`; warnings drained to CLI `<comment>` and admin `withMessage()` after a successful product upsert. Failed rows drain pending warnings without adding them (row still fails as today).

- [ ] **Step 1: Write the failing feature tests**

Add these uses to `tests/Feature/Product/ProductCsvImportTest.php`:

```php
use Commerce\Catalog\Models\Attribute;
use Commerce\Catalog\Models\AttributeSet;
use Commerce\Product\Models\ProductAttributeValue;
```

Add tests (reuse `makeCsv` / `csvRow`). Add a private helper in the same class:

```php
private function importCsv(string $csv): array
{
    $response = $this->actingAs(User::query()->first())
        ->post(route('admin.products.import.store'), [
            'csv' => UploadedFile::fake()->createWithContent('products.csv', $csv),
        ])
        ->assertRedirect(route('admin.products.import.show'));

    $result = $response->getSession()->get('import_result');
    $this->assertIsArray($result);

    return $result;
}

private function assertHasAttributeValue(Product $product, string $attributeCode, string $value): void
{
    $product->load('attributeValues.attribute');

    $this->assertTrue(
        $product->attributeValues->contains(
            fn ($row): bool => $row->attribute?->code === $attributeCode && $row->value === $value,
        ),
        "Expected attribute [{$attributeCode}] = [{$value}].",
    );
}

private function assertMissingAttributeCode(Product $product, string $attributeCode): void
{
    $product->load('attributeValues.attribute');

    $this->assertFalse(
        $product->attributeValues->contains(
            fn ($row): bool => $row->attribute?->code === $attributeCode,
        ),
        "Did not expect attribute [{$attributeCode}] on the product.",
    );
}
```

Then add:

```php
public function test_import_skips_reserved_brand_column_and_keeps_other_attributes(): void
{
    $result = $this->importCsv($this->makeCsv([
        $this->csvRow([
            'ID' => '11',
            'SKU' => 'CSV-RSV-001',
            'Name' => 'Reserved Brand Tee',
            'Attribute 1 name' => 'Brand',
            'Attribute 1 value(s)' => 'Nike',
            'Attribute 2 name' => 'สี',
            'Attribute 2 value(s)' => 'Blue',
        ]),
    ]));

    $this->assertSame(1, $result['created']);
    $this->assertSame(0, $result['skipped']);
    $this->assertSame([], $result['errors']);
    $this->assertContains(
        'Row 11: skipped reserved attribute column "Brand" (code "brand").',
        $result['messages'],
    );

    $product = ProductVariant::query()->where('sku', 'CSV-RSV-001')->first()?->product;
    $this->assertNotNull($product);
    $this->assertDatabaseMissing('attributes', ['code' => 'brand']);
    $this->assertHasAttributeValue($product, 'color', 'Blue');
    $this->assertMissingAttributeCode($product, 'brand');
}

public function test_import_skips_price_min_slugged_attribute_column(): void
{
    $result = $this->importCsv($this->makeCsv([
        $this->csvRow([
            'ID' => '12',
            'SKU' => 'CSV-RSV-002',
            'Name' => 'Price Min Tee',
            'Attribute 1 name' => 'Price Min',
            'Attribute 1 value(s)' => '100',
        ]),
    ]));

    $this->assertSame(1, $result['created']);
    $this->assertSame([], $result['errors']);
    $this->assertContains(
        'Row 12: skipped reserved attribute column "Price Min" (code "price_min").',
        $result['messages'],
    );
    $this->assertDatabaseMissing('attributes', ['code' => 'price_min']);

    $product = ProductVariant::query()->where('sku', 'CSV-RSV-002')->first()?->product;
    $this->assertNotNull($product);
    $this->assertMissingAttributeCode($product, 'price_min');
}

public function test_import_still_attaches_non_reserved_color_attribute(): void
{
    $result = $this->importCsv($this->makeCsv([
        $this->csvRow([
            'ID' => '13',
            'SKU' => 'CSV-RSV-003',
            'Name' => 'Color Only Tee',
            'Attribute 1 name' => 'สี',
            'Attribute 1 value(s)' => 'Red',
        ]),
    ]));

    $this->assertSame(1, $result['created']);
    $this->assertSame([], $result['errors']);
    $this->assertFalse(
        collect($result['messages'])->contains(
            fn (string $message): bool => str_contains($message, 'skipped reserved attribute column'),
        ),
    );

    $product = ProductVariant::query()->where('sku', 'CSV-RSV-003')->first()?->product;
    $this->assertNotNull($product);
    $this->assertHasAttributeValue($product, 'color', 'Red');
}

public function test_import_creates_product_when_only_reserved_attribute_column_is_present(): void
{
    $result = $this->importCsv($this->makeCsv([
        $this->csvRow([
            'ID' => '14',
            'SKU' => 'CSV-RSV-004',
            'Name' => 'Brand Only Tee',
            'Attribute 1 name' => 'Brand',
            'Attribute 1 value(s)' => 'Nike',
        ]),
    ]));

    $this->assertSame(1, $result['created']);
    $this->assertSame([], $result['errors']);
    $this->assertContains(
        'Row 14: skipped reserved attribute column "Brand" (code "brand").',
        $result['messages'],
    );
    $this->assertNotNull(ProductVariant::query()->where('sku', 'CSV-RSV-004')->first());
}

public function test_import_skips_reserved_column_even_when_attribute_is_preloaded_in_cache(): void
{
    $brand = Attribute::query()->create([
        'code' => 'brand',
        'name' => 'Brand',
        'type' => 'text',
        'is_filterable' => true,
        'is_visible' => true,
    ]);

    $set = AttributeSet::query()->create([
        'code' => (string) config('product.import.woocommerce.attribute_set_code', 'woocommerce_default'),
        'name' => (string) config('product.import.woocommerce.attribute_set_name', 'WooCommerce Default'),
    ]);
    $set->attributes()->attach($brand->id, ['position' => 0, 'is_required' => false]);

    $this->assertTrue(
        $set->fresh()->load('attributes')->attributes->contains(
            fn (Attribute $attribute): bool => $attribute->id === $brand->id,
        ),
        'Precondition: Brand must be on the WooCommerce set so importer cache-preloads it by name.',
    );

    $result = $this->importCsv($this->makeCsv([
        $this->csvRow([
            'ID' => '15',
            'SKU' => 'CSV-RSV-005',
            'Name' => 'Legacy Brand Tee',
            'Attribute 1 name' => 'Brand',
            'Attribute 1 value(s)' => 'Nike',
            'Attribute 2 name' => 'สี',
            'Attribute 2 value(s)' => 'Green',
        ]),
    ]));

    $this->assertSame(1, $result['created']);
    $this->assertSame([], $result['errors']);
    $this->assertContains(
        'Row 15: skipped reserved attribute column "Brand" (code "brand").',
        $result['messages'],
    );

    $product = ProductVariant::query()->where('sku', 'CSV-RSV-005')->first()?->product;
    $this->assertNotNull($product);
    $this->assertDatabaseHas('attributes', ['id' => $brand->id, 'code' => 'brand']);
    $this->assertSame(
        0,
        ProductAttributeValue::query()
            ->where('product_id', $product->id)
            ->where('attribute_id', $brand->id)
            ->count(),
    );
    $this->assertHasAttributeValue($product, 'color', 'Green');
}

public function test_import_still_fails_the_row_on_non_reserved_create_failure(): void
{
    Attribute::query()->create([
        'code' => 'fabric',
        'name' => 'Other Fabric',
        'type' => 'text',
        'is_filterable' => true,
        'is_visible' => true,
    ]);

    $result = $this->importCsv($this->makeCsv([
        $this->csvRow([
            'ID' => '16',
            'SKU' => 'CSV-RSV-006',
            'Name' => 'Fabric Clash Tee',
            'Attribute 1 name' => 'Fabric',
            'Attribute 1 value(s)' => 'Cotton',
        ]),
    ]));

    $this->assertSame(0, $result['created']);
    $this->assertNotSame([], $result['errors']);
    $this->assertNull(ProductVariant::query()->where('sku', 'CSV-RSV-006')->first());
}

public function test_import_skips_reserved_column_on_variable_parent(): void
{
    $result = $this->importCsv($this->makeCsv([
        $this->csvRow([
            'ID' => '17',
            'Type' => 'variable',
            'SKU' => 'CSV-RSV-VAR',
            'Name' => 'Variable Reserved Tee',
            'Attribute 1 name' => 'Brand',
            'Attribute 1 value(s)' => 'Nike',
            'Attribute 2 name' => 'สี',
            'Attribute 2 value(s)' => 'Blue',
        ]),
    ]));

    $this->assertSame(1, $result['created']);
    $this->assertSame([], $result['errors']);
    $this->assertContains(
        'Row 17: skipped reserved attribute column "Brand" (code "brand").',
        $result['messages'],
    );
}

public function test_cli_import_writes_reserved_column_comment_and_still_imports(): void
{
    $csv = $this->makeCsv([
        $this->csvRow([
            'ID' => '18',
            'SKU' => 'CSV-RSV-CLI',
            'Name' => 'CLI Brand Tee',
            'Attribute 1 name' => 'Brand',
            'Attribute 1 value(s)' => 'Nike',
        ]),
    ]);

    $path = sys_get_temp_dir().'/wc-import-reserved-'.uniqid('', true).'.csv';
    file_put_contents($path, $csv);

    try {
        $this->artisan('product:import-woocommerce', ['file' => $path, '--force' => true])
            ->expectsOutputToContain('Row 18: skipped reserved attribute column "Brand" (code "brand").')
            ->assertSuccessful();
    } finally {
        @unlink($path);
    }

    $this->assertNotNull(ProductVariant::query()->where('sku', 'CSV-RSV-CLI')->first());
    $this->assertDatabaseMissing('attributes', ['code' => 'brand']);
}
```

The cache-preload test is not optional. A row in `attributes` without attaching it to the WooCommerce set is **not** enough.

- [ ] **Step 2: Run the new tests and confirm they fail**

```bash
php artisan test --compact --filter='test_import_skips_reserved_brand_column_and_keeps_other_attributes|test_import_skips_price_min_slugged_attribute_column|test_import_still_attaches_non_reserved_color_attribute|test_import_creates_product_when_only_reserved_attribute_column_is_present|test_import_skips_reserved_column_even_when_attribute_is_preloaded_in_cache|test_import_still_fails_the_row_on_non_reserved_create_failure|test_import_skips_reserved_column_on_variable_parent|test_cli_import_writes_reserved_column_comment_and_still_imports'
```

Expected: FAIL (Brand column currently errors the whole product via `Attribute code is reserved.`).

- [ ] **Step 3: Implement skip-before-cache and warning plumbing**

In `modules/Product/src/Import/WooCommerceProductImporter.php`:

1. Add `use Commerce\Support\SearchReservedParams;`
2. Add `/** @var list<string> */ private array $pendingAttributeWarnings = [];`
3. Add helpers (do not writeln inside `resolveAttributeValues`):

```php
private function recordReservedAttributeSkip(array $row, string $name, string $code): void
{
    $this->pendingAttributeWarnings[] = 'Row '.$this->rowId($row).': skipped reserved attribute column "'.$name.'" (code "'.$code.'").';
}

/**
 * @return list<string>
 */
private function pullPendingAttributeWarnings(): array
{
    $warnings = $this->pendingAttributeWarnings;
    $this->pendingAttributeWarnings = [];

    return $warnings;
}

private function applyPendingAttributeWarningsToCli(OutputStyle $output): void
{
    foreach ($this->pullPendingAttributeWarnings() as $message) {
        $output->writeln('<comment>'.$message.'</comment>');
    }
}

private function applyPendingAttributeWarningsToResult(ProductCsvImportResult $result): ProductCsvImportResult
{
    foreach ($this->pullPendingAttributeWarnings() as $message) {
        $result = $result->withMessage($message);
    }

    return $result;
}
```

4. In `resolveAttributeValues`, **before** `$this->attributeCache[$name]`:

```php
$code = $this->attributeCode($name);

if (in_array($code, SearchReservedParams::KEYS, true)) {
    $this->recordReservedAttributeSkip($row, $name, $code);
    continue;
}

$attributeId = $this->attributeCache[$name] ?? null;
```

Do **not** wrap `attributeService->create` in `catch (DomainException)`. Empty Attribute N name still continues with no warning.

5. After a successful upsert, emit warnings. On catch, drain without emitting:

`importStandaloneRow` success path: `$result = $this->applyPendingAttributeWarningsToResult($result);` before `withLinkedImages`. Catch: `$this->pullPendingAttributeWarnings();` then `appendError`.

Same for `importVariableParentRow`.

`importCliStandaloneRow` success: `$this->applyPendingAttributeWarningsToCli($output);` after `importRow`. Catch: `$this->pullPendingAttributeWarnings();` then existing error writeln.

Same for `importCliVariableParentRow` after `upsertVariableRow`.

- [ ] **Step 4: Run the new tests and confirm they pass**

```bash
php artisan test --compact tests/Feature/Product/ProductCsvImportTest.php
```

Expected: PASS, including the older import tests in that file.

- [ ] **Step 5: Run F2 regression**

```bash
php artisan test --compact tests/Feature/Catalog/AttributeReservedCodeTest.php tests/Feature/Catalog/VariantOptionReservedCodeTest.php tests/Unit/Product/ProductCsvImportResultTest.php
```

Expected: PASS. HTTP reserved-create still `assertInvalid('code')`. Service create still `DomainException` (`Attribute code is reserved.`).

Confirm `AttributeService.php` has no F2b diff.

- [ ] **Step 6: Commit**

```bash
git add modules/Product/src/Import/WooCommerceProductImporter.php tests/Feature/Product/ProductCsvImportTest.php
git commit -m "fix: skip reserved WooCommerce attribute columns during import"
```

---

## Spec coverage

| Spec | Task |
|---|---|
| Skip before cache lookup | Task 2 `resolveAttributeValues` |
| Legacy DB + **cache preload** via WooCommerce set | Task 2 `test_import_skips_reserved_column_even_when_attribute_is_preloaded_in_cache` |
| `withMessage()` counters unchanged | Task 1 unit test |
| CLI `<comment>` + admin messages, same copy | Task 2 admin tests + CLI test |
| No `skipped` / `errors` increment on reserved skip | Task 2 Brand / reserved-only tests |
| Reserved-only row still creates | Task 2 |
| `สี` / non-reserved still attaches | Task 2 |
| Non-reserved create still fails the row | Task 2 unique `fabric` clash |
| Variable parent uses the same helper | Task 2 variable parent test |
| F2 HTTP + service guard stay green | Task 2 Step 5 |
| No `DomainException` catch as skip | Task 2 Step 3 (explicit) |
| `AttributeService` unchanged | Task 2 Step 5 |

---

## Out of scope (do not do)

Suffix/rename reserved columns, expand `KEYS`, catch `DomainException` to skip, change `AttributeService`, delete legacy reserved rows, dedupe warnings, `buildVariantOptions`, CSV `Brands`, dry-run warnings, Octane, index ops, Suggest, `attribute_sets.code`.
