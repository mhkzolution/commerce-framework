# Catalog V2 Attribute Code Lock Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** After create, `attributes.code` (including variant option presets) is an immutable identity: updates reject a different identity and never write `code`.

**Architecture:** Create path stays slug + reserved `notIn`. Update FormRequests stop slugging into a new stored value; they 422 when `Str::slug(request.code, '_') !== stored.code`. `AttributeService::update` omits `code`. `Attribute::updating` throws `DomainException` if `code` is dirty. Edit forms mark code `readonly` as convenience only.

**Tech Stack:** Laravel 13, PHP 8.4, PHPUnit, existing Catalog admin + variant option preset admin

**Spec:** `docs/superpowers/specs/2026-09-08-catalog-v2-attribute-code-lock-design.md` (Locked)

**Start gate:** Facet Universe on `main` (`001b40e`). Work on `feat/catalog-v2-attribute-code-lock`.

## Global Constraints

- Create still `Str::slug($code, '_')` and rejects `SearchReservedParams::KEYS`. Do not change `StoreAttributeRequest` / `StoreVariantOptionPresetRequest` / `AttributeService::create`.
- Update identity check is `Str::slug(request.code, '_') === stored.code`, not raw string equality.
- Immutability is `Attribute::updating` only. Do not use `creating`, `saving`, or a mutator that also fires on create.
- Readonly UI is not the enforcement layer.
- Do not change shop listing/facet SQL, `attribute_sets.code`, discovery, suggest, or add a code-migration workflow.
- Human gate after the wave. Do not start other Phase 2 follow-ups in this plan.

---

## File map

| Area | Files |
|---|---|
| HTTP | Modify `modules/Catalog/src/Http/Requests/UpdateAttributeRequest.php`, `modules/Product/src/Http/Requests/UpdateVariantOptionPresetRequest.php` |
| Persist | Modify `modules/Catalog/src/DTO/UpdateAttributeData.php`, `modules/Catalog/src/Services/AttributeService.php`, `modules/Catalog/src/Http/Controllers/Admin/AttributeController.php`, `modules/Product/src/Services/VariantOptionPresetService.php`, `modules/Product/src/Http/Controllers/Admin/VariantOptionPresetController.php` |
| Model | Modify `modules/Catalog/src/Models/Attribute.php` |
| UI | Modify `modules/Catalog/resources/views/admin/attributes/_form.blade.php`, `modules/Product/resources/views/admin/variant-options/_form.blade.php` |
| Tests | Create `tests/Feature/Catalog/AttributeCodeLockTest.php`; modify `tests/Feature/Catalog/VariantOptionReservedCodeTest.php` |

`AttributeServiceInterface` signature stays `update(string $uuid, UpdateAttributeData $data): Attribute`.

---

### Task 1: Reject a different identity on update (HTTP)

**Files:**
- Modify: `modules/Catalog/src/Http/Requests/UpdateAttributeRequest.php`
- Modify: `modules/Product/src/Http/Requests/UpdateVariantOptionPresetRequest.php`
- Create: `tests/Feature/Catalog/AttributeCodeLockTest.php`
- Modify: `tests/Feature/Catalog/VariantOptionReservedCodeTest.php`

**Interfaces:**
- Consumes: route params `attribute` (uuid) and `variant_option` (uuid); stored `Attribute.code`.
- Produces: update `code` rule = `sometimes|string|max:100` plus closure `Str::slug($value, '_') !== $stored->code` → `$fail('The attribute code cannot be changed.')`. No `prepareForValidation` slug merge. No `Rule::unique` / `Rule::notIn` on update `code` (different identity already 422s; reserved create stays on store requests).

- [ ] **Step 1: Failing tests**

Create `tests/Feature/Catalog/AttributeCodeLockTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use Commerce\Catalog\Models\Attribute;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AttributeCodeLockTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(IamSeeder::class);
    }

    public function test_create_slugs_code(): void
    {
        $this->actingAs(User::query()->first())
            ->post(route('admin.catalog.attributes.store'), [
                'code' => 'Color',
                'name' => 'Color',
                'type' => 'text',
            ])
            ->assertRedirect(route('admin.catalog.attributes.index'));

        $this->assertDatabaseHas('attributes', [
            'code' => 'color',
            'name' => 'Color',
        ]);
    }

    public function test_update_rejects_a_different_identity_and_does_not_partial_update(): void
    {
        $attribute = Attribute::query()->create([
            'code' => 'shoe_size',
            'name' => 'Shoe size',
            'type' => 'text',
        ]);

        $this->actingAs(User::query()->first())
            ->put(route('admin.catalog.attributes.update', $attribute->uuid), [
                'code' => 'footwear_size',
                'name' => 'Renamed',
                'type' => 'text',
            ])
            ->assertInvalid('code');

        $fresh = $attribute->fresh();
        $this->assertSame('shoe_size', $fresh->code);
        $this->assertSame('Shoe size', $fresh->name);
    }

    public function test_update_accepts_normalized_same_identity(): void
    {
        $attribute = Attribute::query()->create([
            'code' => 'shoe_size',
            'name' => 'Shoe size',
            'type' => 'text',
        ]);

        foreach (['shoe_size', 'Shoe Size', 'shoe-size'] as $postedCode) {
            $this->actingAs(User::query()->first())
                ->put(route('admin.catalog.attributes.update', $attribute->uuid), [
                    'code' => $postedCode,
                    'name' => 'Footwear size',
                    'type' => 'text',
                ])
                ->assertRedirect(route('admin.catalog.attributes.index'));

            $fresh = $attribute->fresh();
            $this->assertSame('shoe_size', $fresh->code);
            $this->assertSame('Footwear size', $fresh->name);
        }
    }
}
```

Do not add model/service tests in this task — they belong in Task 2 after `UpdateAttributeData` drops `code`.

Add this method to `tests/Feature/Catalog/VariantOptionReservedCodeTest.php` (keep the existing reserved update test):

```php
public function test_update_accepts_normalized_same_identity(): void
{
    $option = app(VariantOptionPresetService::class)->create(
        name: 'Shoe size',
        code: 'shoe_size',
        options: ['40', '41'],
        position: 0,
    );

    $this->actingAs(User::query()->first())
        ->put(route('admin.catalog.variant-options.update', $option->uuid), [
            'code' => 'Shoe Size',
            'name' => 'EU shoe size',
            'options' => ['40', '41'],
            'position' => 0,
        ])
        ->assertRedirect(route('admin.catalog.variant-options.index'));

    $fresh = $option->fresh();
    $this->assertSame('shoe_size', $fresh->code);
    $this->assertSame('EU shoe size', $fresh->name);
}
```

- [ ] **Step 2: Run tests to verify the identity-change cases fail**

Run:

```bash
php artisan test --compact tests/Feature/Catalog/AttributeCodeLockTest.php tests/Feature/Catalog/VariantOptionReservedCodeTest.php
```

Expected: `test_update_rejects_a_different_identity_and_does_not_partial_update` FAIL (today the update succeeds and writes `footwear_size`). Same-identity HTTP tests may already pass because the current slug-on-update writes the same stored code — leave them in anyway.

- [ ] **Step 3: Update requests — compare slugged identity, do not merge slug**

Replace `modules/Catalog/src/Http/Requests/UpdateAttributeRequest.php` with:

```php
<?php

declare(strict_types=1);

namespace Commerce\Catalog\Http\Requests;

use Commerce\Catalog\Models\Attribute;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class UpdateAttributeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $attribute = Attribute::query()->where('uuid', $this->route('attribute'))->first();

        return [
            'code' => [
                'sometimes',
                'string',
                'max:100',
                function (string $attributeName, mixed $value, \Closure $fail) use ($attribute): void {
                    if ($attribute === null || ! is_string($value)) {
                        return;
                    }

                    if (Str::slug($value, '_') !== $attribute->code) {
                        $fail('The attribute code cannot be changed.');
                    }
                },
            ],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in(array_keys(config('catalog.attribute_types', [])))],
            'is_filterable' => ['nullable', 'boolean'],
            'is_required' => ['nullable', 'boolean'],
            'is_visible' => ['nullable', 'boolean'],
            'position' => ['nullable', 'integer', 'min:0'],
            'options' => ['nullable', 'string'],
        ];
    }
}
```

Replace `modules/Product/src/Http/Requests/UpdateVariantOptionPresetRequest.php` with:

```php
<?php

declare(strict_types=1);

namespace Commerce\Product\Http\Requests;

use Commerce\Catalog\Models\Attribute;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

final class UpdateVariantOptionPresetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $uuid = (string) $this->route('variant_option');
        $attribute = Attribute::query()->where('uuid', $uuid)->first();

        return [
            'code' => [
                'sometimes',
                'string',
                'max:100',
                function (string $attributeName, mixed $value, \Closure $fail) use ($attribute): void {
                    if ($attribute === null || ! is_string($value)) {
                        return;
                    }

                    if (Str::slug($value, '_') !== $attribute->code) {
                        $fail('The attribute code cannot be changed.');
                    }
                },
            ],
            'name' => ['required', 'string', 'max:255'],
            'position' => ['nullable', 'integer', 'min:0'],
            'options' => ['required', 'array', 'min:1'],
            'options.*' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'options.required' => 'กรุณาเพิ่มค่าตัวเลือกอย่างน้อย 1 รายการ',
            'options.min' => 'กรุณาเพิ่มค่าตัวเลือกอย่างน้อย 1 รายการ',
        ];
    }
}
```

Do not add `prepareForValidation` slug on these two classes. Leave store requests unchanged.

- [ ] **Step 4: Re-run HTTP tests**

Run:

```bash
php artisan test --compact tests/Feature/Catalog/AttributeCodeLockTest.php tests/Feature/Catalog/VariantOptionReservedCodeTest.php tests/Feature/Catalog/AttributeReservedCodeTest.php
```

Expected: identity HTTP tests PASS (`footwear_size` 422; `Shoe Size` / `shoe-size` 200; reserved update still `assertInvalid('code')` because `q` is a different identity).

- [ ] **Step 5: Commit**

```bash
git add tests/Feature/Catalog/AttributeCodeLockTest.php tests/Feature/Catalog/VariantOptionReservedCodeTest.php modules/Catalog/src/Http/Requests/UpdateAttributeRequest.php modules/Product/src/Http/Requests/UpdateVariantOptionPresetRequest.php
git commit -m "$(cat <<'EOF'
feat: reject attribute code identity changes on update

EOF
)"
```

---

### Task 2: Persist path never writes `code`; model guard after create; readonly UI

**Files:**
- Modify: `modules/Catalog/src/DTO/UpdateAttributeData.php`
- Modify: `modules/Catalog/src/Services/AttributeService.php`
- Modify: `modules/Catalog/src/Http/Controllers/Admin/AttributeController.php`
- Modify: `modules/Product/src/Services/VariantOptionPresetService.php`
- Modify: `modules/Product/src/Http/Controllers/Admin/VariantOptionPresetController.php`
- Modify: `modules/Catalog/src/Models/Attribute.php`
- Modify: `modules/Catalog/resources/views/admin/attributes/_form.blade.php`
- Modify: `modules/Product/resources/views/admin/variant-options/_form.blade.php`

**Interfaces:**
- Consumes: `UpdateAttributeData` without `code`; `VariantOptionPresetService::update(Attribute $attribute, string $name, array $options, int $position): Attribute` (drop `$code`).
- Produces: `Attribute::updating` throws `DomainException('Attribute code is immutable.')` when `isDirty('code')`. Create still inserts `code`.

- [ ] **Step 1: Add persist-path tests to `AttributeCodeLockTest`**

```php
use Commerce\Catalog\DTO\UpdateAttributeData;
use Commerce\Catalog\Services\AttributeService;
use Commerce\Core\Exceptions\DomainException;

public function test_model_rejects_dirty_code_after_persistence(): void
{
    $attribute = Attribute::query()->create([
        'code' => 'material',
        'name' => 'Material',
        'type' => 'text',
    ]);

    $this->expectException(DomainException::class);
    $this->expectExceptionMessage('Attribute code is immutable.');

    $attribute->update(['code' => 'fabric']);
}

public function test_create_is_not_blocked_by_immutability(): void
{
    $attribute = Attribute::query()->create([
        'code' => 'brand_fit',
        'name' => 'Brand fit',
        'type' => 'text',
    ]);

    $this->assertSame('brand_fit', $attribute->code);
}

public function test_service_update_preserves_code(): void
{
    $attribute = Attribute::query()->create([
        'code' => 'material',
        'name' => 'Material',
        'type' => 'text',
    ]);

    $updated = app(AttributeService::class)->update($attribute->uuid, new UpdateAttributeData(
        name: 'Fabric',
        type: 'text',
    ));

    $this->assertSame('material', $updated->code);
    $this->assertSame('Fabric', $updated->name);
}
```

Run:

```bash
php artisan test --compact --filter=test_model_rejects_dirty_code_after_persistence tests/Feature/Catalog/AttributeCodeLockTest.php
```

Expected: FAIL (no `updating` hook). `test_service_update_preserves_code` will error until `UpdateAttributeData` drops `code` — implement Step 2 next, do not commit a red suite.

- [ ] **Step 2: Remove `code` from update DTO and writers**

`modules/Catalog/src/DTO/UpdateAttributeData.php` — drop the `code` property:

```php
<?php

declare(strict_types=1);

namespace Commerce\Catalog\DTO;

use Commerce\Support\DTO\DataTransferObject;

final readonly class UpdateAttributeData extends DataTransferObject
{
    /**
     * @param  list<string>|null  $options
     */
    public function __construct(
        public string $name,
        public string $type = 'text',
        public bool $isFilterable = false,
        public bool $isRequired = false,
        public bool $isVisible = true,
        public int $position = 0,
        public ?array $options = null,
    ) {}
}
```

`AttributeService::update` — omit `code` (keep `Str` import only if `create` still uses it):

```php
public function update(string $uuid, UpdateAttributeData $data): Attribute
{
    $attribute = $this->findOrFail($uuid);

    $attribute->update([
        'name' => $data->name,
        'type' => $data->type,
        'is_filterable' => $data->isFilterable,
        'is_required' => $data->isRequired,
        'is_visible' => $data->isVisible,
        'position' => $data->position,
        'options' => $data->options,
    ]);

    return $attribute->fresh();
}
```

`AttributeController::update` — stop passing `code`:

```php
$this->attributeService->update($attribute, new UpdateAttributeData(
    name: $request->validated('name'),
    type: $request->validated('type'),
    isFilterable: (bool) $request->validated('is_filterable', false),
    isRequired: (bool) $request->validated('is_required', false),
    isVisible: (bool) $request->validated('is_visible', true),
    position: (int) $request->validated('position', 0),
    options: $this->parseOptions($request->validated('options')),
));
```

`VariantOptionPresetService::update` — drop `$code`:

```php
/**
 * @param  list<string>  $options
 */
public function update(Attribute $attribute, string $name, array $options, int $position): Attribute
{
    $this->attributeService->update($attribute->uuid, new UpdateAttributeData(
        name: $name,
        type: 'select',
        isFilterable: true,
        isRequired: false,
        isVisible: true,
        position: $position,
        options: $this->normalizeOptions($options),
    ));

    $set = $this->attributeSet();
    $set->attributes()->syncWithoutDetaching([
        $attribute->id => [
            'position' => $position,
            'is_required' => false,
        ],
    ]);

    return $attribute->fresh() ?? $attribute;
}
```

`VariantOptionPresetController::update` — stop passing `code`:

```php
$this->presetService->update(
    attribute: $model,
    name: $request->validated('name'),
    options: array_values($request->validated('options')),
    position: (int) $request->validated('position', 0),
);
```

Grep `UpdateAttributeData(` and `presetService->update(` after this step. There must be no remaining `code:` argument on update.

- [ ] **Step 3: Model guard on `updating` only**

Add to `modules/Catalog/src/Models/Attribute.php` (same pattern as `AttributeValue`):

```php
use Commerce\Core\Exceptions\DomainException;

protected static function booted(): void
{
    static::updating(function (Attribute $attribute): void {
        if ($attribute->isDirty('code')) {
            throw new DomainException('Attribute code is immutable.');
        }
    });
}
```

Do not register `creating` or `saving`. Keep `code` in `$fillable` so create still works.

- [ ] **Step 4: Readonly forms (convenience)**

In `modules/Catalog/resources/views/admin/attributes/_form.blade.php`, change the code input to:

```blade
<input id="code" name="code" value="{{ old('code', $attribute?->code) }}" @readonly($attribute !== null) required class="cf-input mt-1">
```

In `modules/Product/resources/views/admin/variant-options/_form.blade.php`, change the code input to:

```blade
<input id="code" name="code" value="{{ old('code', $option?->code ?? ($suggestedCode ?? '')) }}" @readonly($option !== null) required class="cf-input mt-1 font-mono text-sm">
```

Use `readonly`, not `disabled`. Create views still pass `$attribute => null` / omit `$option` (`$option ??= null`).

- [ ] **Step 5: Run the wave tests**

```bash
php artisan test --compact \
  tests/Feature/Catalog/AttributeCodeLockTest.php \
  tests/Feature/Catalog/AttributeReservedCodeTest.php \
  tests/Feature/Catalog/VariantOptionReservedCodeTest.php \
  tests/Feature/Cart/ShopAttributeFilterTest.php
```

Expected: PASS. Create still slugs. Update different identity 422 with name unchanged. Same identity (`Shoe Size`) 200 and stored code unchanged. Direct `$attribute->update(['code' => 'x'])` throws `DomainException`. `ShopAttributeFilterTest` still matches `?color=red`.

- [ ] **Step 6: Commit**

```bash
git add modules/Catalog/src/DTO/UpdateAttributeData.php modules/Catalog/src/Services/AttributeService.php modules/Catalog/src/Http/Controllers/Admin/AttributeController.php modules/Product/src/Services/VariantOptionPresetService.php modules/Product/src/Http/Controllers/Admin/VariantOptionPresetController.php modules/Catalog/src/Models/Attribute.php modules/Catalog/resources/views/admin/attributes/_form.blade.php modules/Product/resources/views/admin/variant-options/_form.blade.php tests/Feature/Catalog/AttributeCodeLockTest.php
git commit -m "$(cat <<'EOF'
feat: keep attribute codes immutable after create

EOF
)"
```

---

## Spec coverage

| Spec | Task |
|---|---|
| Create slug + reserved | Unchanged store path; Task 1 create test + existing reserved-code tests |
| Update identity = `Str::slug(request.code, '_')` | Task 1 requests |
| Different identity 422, no partial update | Task 1 |
| Same identity `shoe_size` / `Shoe Size` / `shoe-size` | Task 1 + preset test |
| Service does not write `code` | Task 2 |
| `updating` only, create unrestricted | Task 2 model |
| Readonly convenience | Task 2 forms |
| Shop `?color=red` | Task 2 runs `ShopAttributeFilterTest` |
| No code-migration / sets / listing SQL | Global constraints |
