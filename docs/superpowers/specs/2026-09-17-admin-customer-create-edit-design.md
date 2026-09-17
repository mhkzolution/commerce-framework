# Admin customer create and edit (port from Young Elephant)

**Date:** 2026-09-17  
**Status:** Approved for implementation  
**Source:** `/Users/kritsadanambunraung/Projects/young_elephant` admin customers create/edit  
**Target:** this repo (`commerce-framework`) Customers admin

Bring Young Elephant’s create/edit customer forms into commerce-framework: required phone, password on create, optional password on edit, optional Thailand address on create. Keep this repo’s tax profile, address list, and order history on edit.

**Later jobs (not this spec):** in-place address edit, Thailand location widget on the edit “Add address” form, tags/notes/groups, index page changes.

---

## Decisions

- Port Young Elephant create/edit **into existing CF files**. Do not replace the whole Customers module.
- Keep the Documents tax-profile card on edit (`documents::admin.customers.tax-profile`).
- Keep existing edit address add/remove and order history.
- `CreateCustomerData.password` is optional (`?string = null`) so API and existing tests keep working.
- Admin store requires password; `POST /api/v1/customers` does not (same `StoreCustomerRequest` as today, gated with `routeIs('admin.customers.store')`).
- Model already casts `password` as `hashed`. Service writes the plain string; do not double-hash.

---

## Create

Form matches Young Elephant `_form` with `passwordRequired=true`, `showAddress=true`.

| Field | Rule |
|---|---|
| name, email, status | required (email unique) |
| phone | **required** on admin create (API still nullable) |
| password + confirmation | **required** on admin create, min 8 |
| `address[line1]` | optional; empty → no address row |
| `address[line2]`, district, subdistrict, state | optional |
| city, postal_code, country_code | required if `line1` is present |
| city empty + district set | city ← district in `prepareForValidation` |

Create page loads Vite `shopper.css` + `address.js` and includes `customers::storefront._location_fields` with prefix `address`.

If `line1` is filled, `CustomerController::store` creates one address via `CustomerAddressService`: `type=both`, default shipping+billing, country default `TH`.

After store: redirect to `admin.customers.edit`.

Widen the create shell to `max-w-3xl` like Young Elephant.

---

## Edit

Same `_form` with `passwordRequired=false`, `showAddress=false`.

| Field | Rule |
|---|---|
| name, email, status | required (email unique ignoring self) |
| phone | **required** |
| password | nullable; empty string coerced to null; min 8 + confirmed if present |

`CustomerService::update` writes password only when a non-empty string is provided.

Layout order stays:

1. Customer details form (now with password fields)
2. Tax profile (if Documents view exists)
3. Addresses card (unchanged add/remove)
4. Order history (unchanged)

Widen the details form shell to `max-w-3xl`. Delete button, tax card, addresses, and orders stay.

---

## Files to touch

- `modules/Customers/resources/views/admin/_form.blade.php` — copy Young Elephant form
- `modules/Customers/resources/views/admin/create.blade.php` — Vite address assets; pass `passwordRequired` / `showAddress`
- `modules/Customers/resources/views/admin/edit.blade.php` — pass form flags; keep tax include
- `modules/Customers/src/Http/Requests/StoreCustomerRequest.php` — YE rules + city←district
- `modules/Customers/src/Http/Requests/UpdateCustomerRequest.php` — required phone + optional password
- `modules/Customers/src/DTO/CreateCustomerData.php` / `UpdateCustomerData.php` — optional `password`
- `modules/Customers/src/Services/CustomerService.php` — persist password when set
- `modules/Customers/src/Http/Controllers/Admin/CustomerController.php` — pass password; `createAddressIfPresent`
- `tests/Feature/Customers/CustomerAdminFormTest.php` — port YE tests + assert tax profile still on edit

Lang keys already exist: `customers::auth.password`, `confirm_password`, `storefront::storefront.password_hint`, `settings::admin.mail_password_hint`, location/address storefront strings.

---

## Testing

Port `tests/Feature/Customers/CustomerAdminFormTest.php` from Young Elephant:

- Create form shows name/phone/email/password/address fields and `cf-input` on location widgets
- Store without password errors
- Store saves hashed password, customer-guard login works, optional TH address with default shipping+billing
- Update with new password changes hash
- Update without password keeps hash

Add:

- Edit page still contains the tax profile heading (`documents::admin.tax_profile`)

Keep passing: `CustomerTaxProfileAdminTest` (creates customer via service without password).

No browser test for the Thailand dropdowns in this job.

---

## Error handling

- Admin create without password or phone stays on create with validation errors.
- Address with `line1` but missing city/postal/country stays on create with `address.*` errors.
- API `POST /api/v1/customers` still allows missing password and phone.
- Empty edit password does not wipe the stored hash.
