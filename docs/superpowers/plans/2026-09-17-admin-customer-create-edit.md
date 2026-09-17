# Admin Customer Create/Edit Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Port Young Elephant admin customer create/edit (password, required phone, optional TH address on create) while keeping tax profile, addresses, and orders on edit.

**Architecture:** Extend existing Customers admin files. Shared `_form` for create/edit. Admin store requires password; API store does not. Optional first address created through `CustomerAddressService`.

**Tech Stack:** Laravel 13, PHP 8.4, Blade, PHPUnit.

**Spec:** `docs/superpowers/specs/2026-09-17-admin-customer-create-edit-design.md`

## Global Constraints

- Port into existing CF files. Do not replace the Customers module.
- Keep Documents tax-profile on edit.
- Keep edit address add/remove and order history.
- `CreateCustomerData.password` optional default null.
- Admin password required; API `POST /api/v1/customers` is not.
- Do not double-hash; model casts `password` as `hashed`.
- Branch from `main` as `feat/admin-customer-create-edit`.

## File map

| File | Change |
|---|---|
| `tests/Feature/Customers/CustomerAdminFormTest.php` | Port YE tests + tax heading on edit |
| `modules/Customers/resources/views/admin/_form.blade.php` | YE form |
| `modules/Customers/resources/views/admin/create.blade.php` | Vite + flags |
| `modules/Customers/resources/views/admin/edit.blade.php` | flags + `max-w-3xl`; keep tax |
| `StoreCustomerRequest` / `UpdateCustomerRequest` | YE validation |
| `CreateCustomerData` / `UpdateCustomerData` | optional password |
| `CustomerService` | persist password when set |
| `CustomerController` | password + `createAddressIfPresent` |
| `modules/Cart/resources/lang/{en,th}/storefront.php` | `full_name`, `delivery_information` |

Source copies from `/Users/kritsadanambunraung/Projects/young_elephant`.

---

### Task 1: Failing tests then backend + forms

- [ ] **Step 1:** `git checkout main && git checkout -b feat/admin-customer-create-edit`
- [ ] **Step 2:** Copy YE `tests/Feature/Customers/CustomerAdminFormTest.php`. Add:

```php
public function test_edit_form_still_shows_tax_profile(): void
{
    $this->actingAs(User::query()->first())
        ->post(route('admin.customers.store'), $this->customerPayload())
        ->assertRedirect();

    $customer = Customer::query()->where('email', 'harbor@example.com')->firstOrFail();

    $this->actingAs(User::query()->first())
        ->get(route('admin.customers.edit', $customer))
        ->assertOk()
        ->assertSee('name="password"', false)
        ->assertSee(__('documents::admin.tax_profile'), false);
}
```

- [ ] **Step 3:** `php artisan test --filter=CustomerAdminFormTest` — expect FAIL
- [ ] **Step 4:** Copy YE `_form.blade.php`, `create.blade.php`. On edit, pass `passwordRequired => false`, `showAddress => false`, `class="max-w-3xl"`. Keep tax include.
- [ ] **Step 5:** Copy YE `StoreCustomerRequest`, `UpdateCustomerRequest`. Add `password` to both DTOs. Persist password in `CustomerService` when non-empty. Controller: pass password; copy `createAddressIfPresent`. Add lang keys `full_name` and `delivery_information` (EN/TH from YE).
- [ ] **Step 6:** `php artisan test --filter='CustomerAdminFormTest|CustomerTaxProfileAdminTest'` — expect PASS
- [ ] **Step 7:** Commit `feat(customers): add password and address to admin create/edit`
