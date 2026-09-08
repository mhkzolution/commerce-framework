# Catalog V2 Reserved-Code Hardening Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** After slug, `AttributeService::create` rejects `SearchReservedParams::KEYS` with `DomainException` and does not persist.

**Architecture:** Slug first, then `in_array($code, SearchReservedParams::KEYS, true)`, then insert. Store FormRequests keep `notIn` so HTTP stays `422`. Do not map `DomainException` to `ValidationException`. Preset create, importer, and tests inherit the guard because they already call `create()`.

**Tech Stack:** Laravel 13, PHP 8.4, PHPUnit, existing Catalog `AttributeService` + `SearchReservedParams`

**Spec:** `docs/superpowers/specs/2026-09-08-catalog-v2-reserved-code-hardening-design.md` (Locked)

**Start gate:** Attribute Code Lock on `main` (`6dede36`). Work on `feat/catalog-v2-reserved-code-hardening`.

## Global Constraints

- Create still `Str::slug($code, '_')`. The reserved check is performed against the slugged code that would otherwise be persisted.
- `AttributeService::create` throws `DomainException` (`Attribute code is reserved.`). F2 does not introduce validation-error mapping at the service layer.
- Leave `Rule::notIn(SearchReservedParams::KEYS)` on `StoreAttributeRequest` and `StoreVariantOptionPresetRequest`.
- Do not change `AttributeService::update`, model `creating`/`updating`, importer skip/log, `Attribute::query()->create()`, `SearchReservedParams::KEYS`, F1 identity, Octane, index ops, Suggest, or `attribute_sets.code`.
- Human gate after the wave. Do not start other follow-ups in this plan.

---

## File map

| Area | Files |
|---|---|
| Service | Modify `modules/Catalog/src/Services/AttributeService.php` |
| Tests | Modify `tests/Feature/Catalog/AttributeReservedCodeTest.php`, `tests/Feature/Catalog/VariantOptionReservedCodeTest.php` |

Do not modify store/update FormRequests, `VariantOptionPresetService`, the importer, or `Attribute` model.

`Catalog` already imports `SearchReservedParams` from `Commerce\Product\Support` in `StoreAttributeRequest`. `AttributeService` uses the same class.

---

### Task 1: Reject reserved codes in `AttributeService::create`

**Files:**
- Modify: `modules/Catalog/src/Services/AttributeService.php`
- Modify: `tests/Feature/Catalog/AttributeReservedCodeTest.php`
- Modify: `tests/Feature/Catalog/VariantOptionReservedCodeTest.php`

**Interfaces:**
- Consumes: `CreateAttributeData::$code`; `SearchReservedParams::KEYS`.
- Produces: `AttributeService::create` slugs, then throws `Commerce\Core\Exceptions\DomainException` with message `Attribute code is reserved.` when the slugged code is in `KEYS`; otherwise inserts `code` as the slugged value. Signature stays `create(CreateAttributeData $data): Attribute`.

- [ ] **Step 1: Failing tests**

Add to `tests/Feature/Catalog/AttributeReservedCodeTest.php`:

```php
use Commerce\Catalog\DTO\CreateAttributeData;
use Commerce\Catalog\Services\AttributeService;
use Commerce\Core\Exceptions\DomainException;

public function test_service_create_rejects_reserved_slugged_codes(): void
{
    $service = app(AttributeService::class);

    foreach (['brand', 'Brand', 'BRAND', 'price-min', 'price_min'] as $code) {
        try {
            $service->create(new CreateAttributeData(
                code: $code,
                name: 'Reserved',
                type: 'text',
            ));
            $this->fail("Expected DomainException for code [{$code}].");
        } catch (DomainException $exception) {
            $this->assertSame('Attribute code is reserved.', $exception->getMessage());
        }
    }

    $this->assertDatabaseMissing('attributes', ['code' => 'brand']);
    $this->assertDatabaseMissing('attributes', ['code' => 'price_min']);
}

public function test_service_create_slugs_and_persists_non_reserved_code(): void
{
    $attribute = app(AttributeService::class)->create(new CreateAttributeData(
        code: 'Color',
        name: 'Color',
        type: 'text',
    ));

    $this->assertSame('color', $attribute->code);
    $this->assertDatabaseHas('attributes', [
        'code' => 'color',
        'name' => 'Color',
    ]);
}
```

Add to `tests/Feature/Catalog/VariantOptionReservedCodeTest.php`:

```php
use Commerce\Core\Exceptions\DomainException;

public function test_preset_service_create_rejects_reserved_code(): void
{
    $this->expectException(DomainException::class);
    $this->expectExceptionMessage('Attribute code is reserved.');

    app(VariantOptionPresetService::class)->create(
        name: 'Brand',
        code: 'brand',
        options: ['Nike'],
        position: 0,
    );
}

public function test_preset_service_create_does_not_persist_reserved_code(): void
{
    try {
        app(VariantOptionPresetService::class)->create(
            name: 'Brand',
            code: 'brand',
            options: ['Nike'],
            position: 0,
        );
    } catch (DomainException) {
        $this->assertDatabaseMissing('attributes', ['code' => 'brand']);

        return;
    }

    $this->fail('Expected DomainException.');
}
```

Keep every existing HTTP store/update test in both files.

- [ ] **Step 2: Run tests to verify they fail**

```bash
php artisan test --compact tests/Feature/Catalog/AttributeReservedCodeTest.php tests/Feature/Catalog/VariantOptionReservedCodeTest.php
```

Expected: `test_service_create_rejects_reserved_slugged_codes` FAIL (today `create` inserts `brand`). `test_preset_service_create_rejects_reserved_code` FAIL for the same reason. HTTP store tests still PASS (`notIn` is unchanged).

- [ ] **Step 3: Guard `AttributeService::create`**

Replace `create()` in `modules/Catalog/src/Services/AttributeService.php` with:

```php
use Commerce\Core\Exceptions\DomainException;
use Commerce\Product\Support\SearchReservedParams;

public function create(CreateAttributeData $data): Attribute
{
    $code = Str::slug($data->code, '_');

    if (in_array($code, SearchReservedParams::KEYS, true)) {
        throw new DomainException('Attribute code is reserved.');
    }

    return Attribute::query()->create([
        'code' => $code,
        'name' => $data->name,
        'type' => $data->type,
        'is_filterable' => $data->isFilterable,
        'is_required' => $data->isRequired,
        'is_visible' => $data->isVisible,
        'position' => $data->position,
        'options' => $data->options,
    ]);
}
```

Do not catch this in the service. Do not throw `ValidationException`. Do not change `update()`.

- [ ] **Step 4: Re-run tests**

```bash
php artisan test --compact \
  tests/Feature/Catalog/AttributeReservedCodeTest.php \
  tests/Feature/Catalog/VariantOptionReservedCodeTest.php \
  tests/Feature/Catalog/AttributeCodeLockTest.php
```

Expected: PASS. HTTP reserved-create still `assertInvalid('code')`. Service reserved create throws `DomainException` and persists nothing. `Color` still becomes `color`. Code-lock tests still pass.

- [ ] **Step 5: Commit**

```bash
git add modules/Catalog/src/Services/AttributeService.php tests/Feature/Catalog/AttributeReservedCodeTest.php tests/Feature/Catalog/VariantOptionReservedCodeTest.php
git commit -m "$(cat <<'EOF'
feat: reject reserved attribute codes in AttributeService create

EOF
)"
```

---

## Spec coverage

| Spec | Task |
|---|---|
| Check slugged code vs `KEYS` | Task 1 `create()` |
| `Brand` / `brand` / `BRAND` / `price-min` / `price_min` | Task 1 service tests |
| `DomainException`, no ValidationException mapping | Task 1 |
| HTTP `422` via FormRequest | Existing store tests; do not change requests |
| Preset create via service | Task 1 preset tests |
| `Color` → `color` | Task 1 |
| No importer / model / KEYS / F1 / Octane | Global constraints |
