# Thailand Address Select Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make province, district, and subdistrict type-to-search on every address form, and replace remaining admin free-text location fields with the shared Thailand widget.

**Architecture:** One Blade partial (`_location_fields`) plus `address.js` remains the only location UI. Admin add-address and admin order-create include that partial. Search is a vanilla combobox wrapped around the existing `<select>`s after `fillSelect`. Order create keeps posting `province`; customer addresses keep posting `state`.

**Tech Stack:** Laravel 13, PHP 8.4, Blade, vanilla JS, Vite, PHPUnit.

**Spec:** `docs/superpowers/specs/2026-09-17-thailand-address-select-design.md`

## Global Constraints

- Reuse `_location_fields` + `address.js` + `/api/v1/storefront/locations/thailand`. No second dataset.
- Vanilla combobox only. No Tom Select / Choices / Select2.
- Postal stays a text input; subdistrict fills it; still editable.
- Do not put literal `cf-input` in `_location_fields`. Pass classes from admin includes.
- Admin add-address default country is `TH`.
- Admin order create field name for province stays `shipping_address[province]` / `billing_address[province]` via `$stateKey`.
- No migration. No Documents/POS changes. No JS unit test / browser test.
- Branch from `main` as `feat/thailand-address-select`.

## File map

| File | Change |
|---|---|
| `tests/Feature/Customers/CustomerAdminFormTest.php` | Edit add-address widget + persist district/subdistrict |
| `tests/Feature/Orders/AdminOrderCreateTest.php` | Create page uses widget + `shipping_address[province]` |
| `tests/Unit/Storefront/ThailandAddressComboboxTest.php` | `address.js` / `shopper.css` contain combobox contract |
| `modules/Customers/resources/views/storefront/_location_fields.blade.php` | `$stateKey`, `$fieldAttrs` |
| `modules/Customers/resources/views/admin/_address_form.blade.php` | Include location widget |
| `modules/Customers/resources/views/admin/edit.blade.php` | Vite `shopper.css` + `address.js` |
| `modules/Customers/src/Http/Requests/StoreAddressRequest.php` | city ← district |
| `modules/Customers/src/Http/Controllers/Admin/CustomerAddressController.php` | Persist district / subdistrict |
| `modules/Orders/resources/views/admin/create.blade.php` | Widget on shipping/billing; Vite assets |
| `resources/js/admin/order-create.js` | Prefill `data-selected` + `storefront:address-sync` |
| `resources/js/storefront/address.js` | Combobox after `fillSelect` |
| `resources/css/storefront/shopper.css` | Combobox chrome |

---

### Task 1: Admin add-address widget and persist

**Files:**
- Modify: `tests/Feature/Customers/CustomerAdminFormTest.php`
- Modify: `modules/Customers/src/Http/Requests/StoreAddressRequest.php`
- Modify: `modules/Customers/src/Http/Controllers/Admin/CustomerAddressController.php`
- Modify: `modules/Customers/resources/views/storefront/_location_fields.blade.php`
- Modify: `modules/Customers/resources/views/admin/_address_form.blade.php`
- Modify: `modules/Customers/resources/views/admin/edit.blade.php`

**Interfaces:**
- Consumes: existing `CreateAddressData` (`district`, `subdistrict` already on the DTO)
- Produces: `_location_fields` accepts `$stateKey` (default `'state'`) and `$fieldAttrs` (`array<string, array<string, string>>`); admin add-address posts top-level `district` / `subdistrict` / `state` / `city`

- [ ] **Step 1: Create the branch**

```bash
git checkout main && git pull && git checkout -b feat/thailand-address-select
```

- [ ] **Step 2: Write the failing tests**

Append to `tests/Feature/Customers/CustomerAdminFormTest.php` before `customerPayload()`:

```php
public function test_edit_add_address_form_uses_thailand_location_fields(): void
{
    $this->actingAs(User::query()->first())
        ->post(route('admin.customers.store'), $this->customerPayload())
        ->assertRedirect();

    $customer = Customer::query()->where('email', 'harbor@example.com')->firstOrFail();

    $html = $this->actingAs(User::query()->first())
        ->get(route('admin.customers.edit', $customer))
        ->assertOk()
        ->getContent();

    $this->assertStringContainsString('data-thailand-address', $html);
    $this->assertStringContainsString('name="district"', $html);
    $this->assertStringContainsString('name="subdistrict"', $html);
    $this->assertStringContainsString('name="state"', $html);
    $this->assertStringContainsString('name="country_code"', $html);
}

public function test_admin_can_add_thailand_address_with_district_and_subdistrict(): void
{
    $this->actingAs(User::query()->first())
        ->post(route('admin.customers.store'), $this->customerPayload())
        ->assertRedirect();

    $customer = Customer::query()->where('email', 'harbor@example.com')->firstOrFail();

    $this->actingAs(User::query()->first())
        ->post(route('admin.customers.addresses.store', $customer), [
            'label' => 'Office',
            'type' => 'both',
            'line1' => '1 Silom',
            'district' => 'Bang Rak',
            'subdistrict' => 'Si Lom',
            'state' => 'Bangkok',
            'postal_code' => '10500',
            'country_code' => 'TH',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('customer_addresses', [
        'customer_id' => $customer->id,
        'line1' => '1 Silom',
        'city' => 'Bang Rak',
        'district' => 'Bang Rak',
        'subdistrict' => 'Si Lom',
        'state' => 'Bangkok',
        'postal_code' => '10500',
        'country_code' => 'TH',
    ]);
}
```

- [ ] **Step 3: Run tests to verify they fail**

```bash
php artisan test --filter='test_edit_add_address_form_uses_thailand_location_fields|test_admin_can_add_thailand_address_with_district_and_subdistrict'
```

Expected: FAIL — edit HTML has `name="city"` free text, not `data-thailand-address` on the add-address form; POST does not persist `district` / `subdistrict`; city required without district fallback.

- [ ] **Step 4: Extend `_location_fields` with `$stateKey` and `$fieldAttrs`**

In the `@php` block of `modules/Customers/resources/views/storefront/_location_fields.blade.php`, after `$inputClass`, add:

```php
    $stateKey = $stateKey ?? 'state';
    $fieldAttrs = $fieldAttrs ?? [];
    $extra = static function (string $field) use ($fieldAttrs): string {
        $html = '';
        foreach ($fieldAttrs[$field] ?? [] as $attribute => $attributeValue) {
            $html .= ' '.$attribute.'="'.e((string) $attributeValue).'"';
        }

        return $html;
    };
    $stateValue = $value($stateKey) !== '' ? $value($stateKey) : $value('state');
```

Province select `data-selected` uses `$stateValue`. Hidden province/state input:

```blade
<input type="hidden" name="{{ $name($stateKey) }}" value="{{ $stateValue }}" data-address-field="state" data-address-prefix="{{ $prefix }}" data-thailand-state {!! $extra('state') !!} @disabled(! $isThailand)>
```

International free state input also uses `name="{{ $name($stateKey) }}"` so only one enabled state/province field submits.

Add `{!! $extra('district') !!}` on the district select, `{!! $extra('subdistrict') !!}` on the subdistrict select, `{!! $extra('postal_code') !!}` on the postal input.

Do **not** add the string `cf-input` to this file.

- [ ] **Step 5: Replace admin add-address free-text location fields**

Replace city / state / postal / country blocks in `modules/Customers/resources/views/admin/_address_form.blade.php` with:

```blade
    <div class="sm:col-span-2">
        @include('customers::storefront._location_fields', [
            'prefix' => '',
            'required' => true,
            'wrapperClass' => 'grid gap-4 sm:grid-cols-2',
            'gridClass' => 'contents',
            'fieldClass' => '',
            'labelClass' => 'block text-sm font-medium text-text',
            'selectClass' => 'cf-input mt-1',
            'inputClass' => 'cf-input mt-1',
        ])
    </div>
```

Keep label, type, line1, line2, and the default checkbox.

In `modules/Customers/resources/views/admin/edit.blade.php`, after `@section('title')`:

```blade
@push('head')
    @vite(['resources/css/storefront/shopper.css', 'resources/js/storefront/address.js'])
@endpush
```

- [ ] **Step 6: Persist district/subdistrict and city ← district**

`StoreAddressRequest`:

```php
protected function prepareForValidation(): void
{
    $city = trim((string) $this->input('city', ''));
    $district = trim((string) $this->input('district', ''));

    if ($city === '' && $district !== '') {
        $this->merge(['city' => $district]);
    }
}
```

`CustomerAddressController::store` — pass through to the DTO (other args unchanged):

```php
state: $request->validated('state'),
district: $request->validated('district'),
subdistrict: $request->validated('subdistrict'),
isDefault: (bool) $request->boolean('is_default'),
```

- [ ] **Step 7: Run Task 1 tests plus isolation**

```bash
php artisan test --filter='CustomerAdminFormTest|Ws002ShopperChromeIsolationTest'
```

Expected: PASS. Isolation still forbids `cf-input` inside `_location_fields`.

- [ ] **Step 8: Commit**

```bash
git add tests/Feature/Customers/CustomerAdminFormTest.php \
  modules/Customers/src/Http/Requests/StoreAddressRequest.php \
  modules/Customers/src/Http/Controllers/Admin/CustomerAddressController.php \
  modules/Customers/resources/views/storefront/_location_fields.blade.php \
  modules/Customers/resources/views/admin/_address_form.blade.php \
  modules/Customers/resources/views/admin/edit.blade.php
git commit -m "$(cat <<'EOF'
feat(customers): use Thailand location widget on admin add address

EOF
)"
```

---

### Task 2: Admin order-create location widget

**Files:**
- Modify: `tests/Feature/Orders/AdminOrderCreateTest.php`
- Modify: `modules/Orders/resources/views/admin/create.blade.php`
- Modify: `resources/js/admin/order-create.js`

**Interfaces:**
- Consumes: `_location_fields` `$stateKey` and `$fieldAttrs` from Task 1
- Produces: order create posts `shipping_address[province]` (hidden) plus district/subdistrict/postal; `fillShipping` sets `dataset.selected` then dispatches `storefront:address-sync`

- [ ] **Step 1: Write the failing assertion**

In `test_create_page_is_a_lookup_workflow_and_does_not_dump_the_catalog`, after the existing `assertStringContainsString` calls, add:

```php
        $this->assertStringContainsString('data-thailand-address', $html);
        $this->assertStringContainsString('name="shipping_address[province]"', $html);
        $this->assertStringContainsString('name="shipping_address[district]"', $html);
        $this->assertStringContainsString('name="shipping_address[subdistrict]"', $html);
        $this->assertStringContainsString('data-ship-province', $html);
        $this->assertStringNotContainsString('id="ship-province"', $html);
```

- [ ] **Step 2: Run the test to verify it fails**

```bash
php artisan test --filter=test_create_page_is_a_lookup_workflow_and_does_not_dump_the_catalog
```

Expected: FAIL — page still has `id="ship-province"` free-text input and no `data-thailand-address`.

- [ ] **Step 3: Swap shipping/billing location inputs for the widget**

After `@section('title')` in `modules/Orders/resources/views/admin/create.blade.php`:

```blade
@push('head')
    @vite(['resources/css/storefront/shopper.css', 'resources/js/storefront/address.js'])
@endpush
```

Delete the four shipping inputs (`ship-district`, `ship-subdistrict`, `ship-province`, `ship-postal`) and replace with:

```blade
                            @include('customers::storefront._location_fields', [
                                'prefix' => 'shipping_address',
                                'required' => false,
                                'stateKey' => 'province',
                                'wrapperClass' => 'sm:col-span-2 grid gap-4 sm:grid-cols-2',
                                'gridClass' => 'contents',
                                'fieldClass' => '',
                                'labelClass' => 'block text-sm font-medium text-text',
                                'selectClass' => 'cf-input mt-1',
                                'inputClass' => 'cf-input mt-1',
                                'fieldAttrs' => [
                                    'district' => ['data-ship-district' => ''],
                                    'subdistrict' => ['data-ship-subdistrict' => ''],
                                    'state' => ['data-ship-province' => ''],
                                    'postal_code' => ['data-ship-postal' => ''],
                                ],
                            ])
```

Keep recipient name, phone, line1, line2. Repeat for billing inside `[data-billing-fields]`, using prefix `billing_address` and `data-bill-district` / `data-bill-subdistrict` / `data-bill-province` / `data-bill-postal`.

Do not change `AdminStoreOrderRequest::cleanAddress`.

- [ ] **Step 4: Prefill from customer lookup**

Replace `fillShipping` in `resources/js/admin/order-create.js` with:

```javascript
    const fillAttr = (selector, value, asSelected = false) => {
        const el = root.querySelector(selector);

        if (!el) {
            return;
        }

        const next = value || '';
        el.value = next;

        if (asSelected) {
            el.dataset.selected = next;
        }
    };

    const fillShipping = (address = {}, customer = {}) => {
        root.querySelector('[data-ship-name]').value = address.recipient_name || customer.name || '';
        root.querySelector('[data-ship-phone]').value = address.phone || customer.phone || '';
        root.querySelector('[data-ship-line1]').value = address.line1 || '';
        root.querySelector('[data-ship-line2]').value = address.line2 || '';
        fillAttr('[data-ship-district]', address.district, true);
        fillAttr('[data-ship-subdistrict]', address.subdistrict, true);
        fillAttr('[data-ship-province]', address.province);
        fillAttr('[data-ship-postal]', address.postal_code);
        document.dispatchEvent(new Event('storefront:address-sync'));
    };
```

- [ ] **Step 5: Run order-create tests**

```bash
php artisan test --filter='AdminOrderCreateTest|AdminOrderCreateReadinessTest'
```

Expected: PASS. Existing POST with `shipping_address.province` still creates the order.

- [ ] **Step 6: Commit**

```bash
git add tests/Feature/Orders/AdminOrderCreateTest.php \
  modules/Orders/resources/views/admin/create.blade.php \
  resources/js/admin/order-create.js
git commit -m "$(cat <<'EOF'
feat(orders): use Thailand location widget on admin order create

EOF
)"
```

---

### Task 3: Searchable combobox

**Files:**
- Create: `tests/Unit/Storefront/ThailandAddressComboboxTest.php`
- Modify: `resources/js/storefront/address.js`
- Modify: `resources/css/storefront/shopper.css`

**Interfaces:**
- Consumes: existing `fillSelect(select, items, selected)` and `initThailandAddresses()`
- Produces: `enhanceCombobox(select)` wraps each Thailand select; option search uses `name_th` + `name_en`; picking an option sets `select.value` and dispatches `change`

- [ ] **Step 1: Write the failing contract test**

Create `tests/Unit/Storefront/ThailandAddressComboboxTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Storefront;

use PHPUnit\Framework\TestCase;

final class ThailandAddressComboboxTest extends TestCase
{
    public function test_address_script_builds_a_searchable_combobox(): void
    {
        $root = dirname(__DIR__, 3);
        $js = file_get_contents($root.'/resources/js/storefront/address.js');
        $css = file_get_contents($root.'/resources/css/storefront/shopper.css');

        $this->assertNotFalse($js);
        $this->assertNotFalse($css);
        $this->assertStringContainsString("setAttribute('role', 'combobox')", $js);
        $this->assertStringContainsString('enhanceCombobox', $js);
        $this->assertStringContainsString('.storefront-combobox', $css);
        $this->assertStringNotContainsString('tom-select', $js);
        $this->assertStringNotContainsString('choices.js', $js);
    }
}
```

- [ ] **Step 2: Run it to verify it fails**

```bash
php artisan test --filter=ThailandAddressComboboxTest
```

Expected: FAIL — `address.js` has no `enhanceCombobox` / `role=combobox`.

- [ ] **Step 3: Add combobox CSS**

Append to `resources/css/storefront/shopper.css`:

```css
.storefront-combobox {
    position: relative;
}

.storefront-combobox__select {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}

.storefront-combobox__list {
    position: absolute;
    z-index: 30;
    top: calc(100% + var(--space-4));
    right: 0;
    left: 0;
    max-height: 16rem;
    margin: 0;
    padding: var(--space-4);
    overflow: auto;
    list-style: none;
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    background: var(--color-surface);
    box-shadow: var(--shadow-md, 0 8px 24px rgb(0 0 0 / 0.08));
}

.storefront-combobox__option {
    padding: var(--space-8) var(--space-12);
    border-radius: var(--radius-md);
    cursor: pointer;
}

.storefront-combobox__option:hover,
.storefront-combobox__option[aria-selected='true'] {
    background: var(--color-surface-muted, rgb(0 0 0 / 0.04));
}
```

If `--space-4` does not exist in this file, use `0.25rem` instead. Check neighboring token usage before writing.

- [ ] **Step 4: Enhance selects in `address.js`**

Keep `localeIsThai`, `locationLabel`, `fetchLocations`, `setDisabled`, cascade, and `initThailandAddresses` as they are.

In `fillSelect`, after creating each option, set `option.dataset.en = item.name_en || ''` (dataset.th already exists). At the end of `fillSelect`, call `enhanceCombobox(select)`.

After `[province, district, subdistrict, ...].forEach((el) => setDisabled(...))` in `syncThailandGroup`, call `enhanceCombobox` on province, district, and subdistrict so disabled state copies onto the search input.

Add these functions to `resources/js/storefront/address.js`:

```javascript
const optionSearchText = (option) =>
    [option.textContent, option.value, option.dataset.th, option.dataset.en]
        .filter(Boolean)
        .join(' ')
        .toLowerCase();

const selectedLabel = (select) => select.selectedOptions[0]?.textContent || '';

const enhanceCombobox = (select) => {
    if (!select) {
        return;
    }

    let wrap = select.closest('[data-thailand-combobox]');

    if (!wrap) {
        wrap = document.createElement('div');
        wrap.className = 'storefront-combobox';
        wrap.dataset.thailandCombobox = '';
        select.parentNode.insertBefore(wrap, select);
        wrap.append(select);
        select.classList.add('storefront-combobox__select');

        const input = document.createElement('input');
        input.type = 'text';
        input.autocomplete = 'off';
        input.spellcheck = false;
        input.className = select.className.replace('storefront-combobox__select', '').trim();
        input.classList.add('storefront-combobox__input');
        input.setAttribute('role', 'combobox');
        input.setAttribute('aria-autocomplete', 'list');
        input.setAttribute('aria-expanded', 'false');
        input.dataset.thailandComboboxInput = '';

        const list = document.createElement('ul');
        list.hidden = true;
        list.setAttribute('role', 'listbox');
        list.className = 'storefront-combobox__list';
        list.dataset.thailandComboboxList = '';

        wrap.append(input, list);

        input.addEventListener('focus', () => {
            input.value = '';
            renderComboboxList(select, '');
            openCombobox(select);
        });
        input.addEventListener('input', () => {
            renderComboboxList(select, input.value);
            openCombobox(select);
        });
        input.addEventListener('blur', () => {
            closeCombobox(select);
        });
        input.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeCombobox(select);
                input.blur();
            }
        });
        list.addEventListener('mousedown', (event) => event.preventDefault());
    }

    syncComboboxInput(select);
    renderComboboxList(select, '');
};

const syncComboboxInput = (select) => {
    const input = select.closest('[data-thailand-combobox]')?.querySelector('[data-thailand-combobox-input]');

    if (!input) {
        return;
    }

    input.disabled = select.disabled;

    if (document.activeElement !== input) {
        input.value = selectedLabel(select);
        input.placeholder = select.querySelector('option[value=""]')?.textContent || '';
    }
};

const renderComboboxList = (select, query) => {
    const list = select.closest('[data-thailand-combobox]')?.querySelector('[data-thailand-combobox-list]');

    if (!list) {
        return;
    }

    const needle = query.trim().toLowerCase();
    list.replaceChildren();

    [...select.options]
        .filter((option) => option.value !== '')
        .filter((option) => !needle || optionSearchText(option).includes(needle))
        .forEach((option) => {
            const item = document.createElement('li');
            item.setAttribute('role', 'option');
            item.className = 'storefront-combobox__option';
            item.textContent = option.textContent;

            if (option.selected) {
                item.setAttribute('aria-selected', 'true');
            }

            item.addEventListener('click', () => {
                select.value = option.value;
                select.dispatchEvent(new Event('change', { bubbles: true }));
                closeCombobox(select);
                syncComboboxInput(select);
            });
            list.append(item);
        });
};

const openCombobox = (select) => {
    const wrap = select.closest('[data-thailand-combobox]');
    const input = wrap?.querySelector('[data-thailand-combobox-input]');
    const list = wrap?.querySelector('[data-thailand-combobox-list]');

    if (!list || !input) {
        return;
    }

    list.hidden = false;
    input.setAttribute('aria-expanded', 'true');
};

const closeCombobox = (select) => {
    const wrap = select.closest('[data-thailand-combobox]');
    const input = wrap?.querySelector('[data-thailand-combobox-input]');
    const list = wrap?.querySelector('[data-thailand-combobox-list]');

    if (!list || !input) {
        return;
    }

    list.hidden = true;
    input.setAttribute('aria-expanded', 'false');
    syncComboboxInput(select);
};
```

`list` `mousedown preventDefault` keeps focus on the input so `blur` does not close before `click`. Picking an option still runs the existing province/district `change` listeners (clear child `dataset.selected`, sync next list, copy postal).

- [ ] **Step 5: Run combobox + related suites**

```bash
php artisan test --filter='ThailandAddressComboboxTest|CustomerAdminFormTest|AdminOrderCreateTest|StorefrontShoppingExperienceTest|Ws002ShopperChromeIsolationTest'
```

Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add tests/Unit/Storefront/ThailandAddressComboboxTest.php \
  resources/js/storefront/address.js \
  resources/css/storefront/shopper.css
git commit -m "$(cat <<'EOF'
feat(storefront): make Thailand address selects searchable

EOF
)"
```

---

## Spec coverage

| Spec requirement | Task |
|---|---|
| Searchable vanilla combobox on existing selects | 3 |
| Checkout / storefront address book / admin customer create | 3 (same `address.js`) |
| Admin customer edit Add address widget | 1 |
| Persist district/subdistrict; city ← district; default TH | 1 |
| Admin order create widget; `province` field name; customer prefill | 2 |
| Postal auto-fill, still an input | unchanged + Task 3 cascade |
| No `cf-input` in `_location_fields` | 1 include params + isolation test |
| Listed PHP tests | 1, 2, 3 |
| Documents / POS / in-place admin address edit | out of scope |
