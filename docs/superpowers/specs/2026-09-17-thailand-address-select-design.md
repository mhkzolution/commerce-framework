# Thailand address select (searchable)

**Date:** 2026-09-17  
**Status:** Approved for implementation

Make province / district / subdistrict easy to pick on every address form: type-to-search cascading selects, auto-filled postal code, and the same widget on storefront plus the remaining admin free-text forms.

**Later jobs (not this spec):** Documents tax/invoice billing, POS invoice, in-place admin address edit, unifying `province` vs `state` across the whole order payload.

---

## Decisions

- Reuse `customers::storefront._location_fields` + `resources/js/storefront/address.js` + `/api/v1/storefront/locations/thailand`. No second location dataset.
- Search is a **vanilla combobox** wrapped around the existing `<select>`s. No Tom Select / Choices / Select2.
- Postal code stays a text input. Choosing a subdistrict fills it; the shopper can still edit it.
- Country other than `TH` still uses free-text city and state.
- Admin chrome stays out of the storefront partial: pass `selectClass` / `inputClass` (`cf-input` on admin). Combobox copies those classes onto the search input. `Ws002ShopperChromeIsolationTest` keeps forbidding literal `cf-input` in `_location_fields`.
- Admin customer “Add address” default country is `TH` (today it is `US`).
- Admin order create keeps posting `shipping_address[province]` / `billing_address[province]`. The widget’s hidden province field is renamed via a parameter (`$stateKey`, default `state`).

---

## Surfaces

| Surface | Today | This job |
|---|---|---|
| Checkout shipping / billing | Cascading native selects | Same widget + searchable |
| Storefront address add / edit | Cascading native selects | Same widget + searchable |
| Admin customer create | Cascading native selects | Same widget + searchable |
| Admin customer edit → Add address | Free-text city / state / postal, country `US` | Shared widget; persist district / subdistrict |
| Admin order create shipping / billing | Free-text district / subdistrict / province / postal | Shared widget; keep `province` field names and customer prefill |

Out of scope: Documents tax profile billing, POS invoice, international typeahead.

---

## Widget

Keep native `<select data-thailand-province|district|subdistrict>` as the source of truth (options, `data-id`, `data-postal`, cascade `change` events). After `fillSelect`, attach a combobox:

- Closed: looks like the current select.
- Open / focused: shopper types; list filters by `name_th` and `name_en` (case-insensitive).
- Pick an option: set the select value, dispatch `change`, close the list.
- Changing province clears district and subdistrict (existing `dataset.selected` reset). Changing district clears subdistrict. Subdistrict still copies `dataset.postal` onto `[data-thailand-postal]`.
- Re-attach after options are replaced. Do not leave a stale list of the previous province’s districts.

Cascade and hidden fields stay as today:

- `state` (or `$stateKey`) = province English name (`name_en`)
- `district` / `subdistrict` = selected `name_en`
- hidden `city` = district
- `postal_code` = subdistrict postal, editable

API endpoints and JSON shape do not change.

Styles live in `resources/css/storefront/shopper.css` (storefront tokens). Admin pages that already load `shopper.css` (customer create) keep doing so. Customer edit and order create load `address.js` plus `shopper.css` so the combobox matches.

---

## Admin add address

Replace city / state / postal / country inputs in `modules/Customers/resources/views/admin/_address_form.blade.php` with `_location_fields` (no prefix). Keep label, type, line1, line2, default checkbox.

`CustomerAddressController::store` already receives a DTO that has `district` / `subdistrict`. Pass them through from `StoreAddressRequest` (validated keys already exist; they are dropped today).

`StoreAddressRequest::prepareForValidation`: if `city` is empty and `district` is set, `city` ← `district` (same as admin customer create).

---

## Admin order create

Replace the four free-text location inputs on shipping and billing with `_location_fields`:

- prefix `shipping_address` / `billing_address`
- `$stateKey` = `province` so the hidden field is still `shipping_address[province]`
- `$required` = false (order create location is optional today)
- pass extra data attributes so `resources/js/admin/order-create.js` `fillShipping` still finds `[data-ship-district]`, `[data-ship-subdistrict]`, `[data-ship-province]`, `[data-ship-postal]` (and the billing equivalents)

`fillShipping` writes `dataset.selected` (and postal value), then dispatches `storefront:address-sync` so the comboboxes reload options and show the chosen names. Do not only set `.value` on an empty select.

`AdminStoreOrderRequest::cleanAddress` stays as-is: `city` ← district, `state` ← province.

---

## Files to touch

- `resources/js/storefront/address.js` — combobox + re-init after `fillSelect`
- `resources/css/storefront/shopper.css` — combobox chrome
- `modules/Customers/resources/views/storefront/_location_fields.blade.php` — `$stateKey`; optional per-field attributes
- `modules/Customers/resources/views/admin/_address_form.blade.php` — include location widget
- `modules/Customers/resources/views/admin/edit.blade.php` — Vite `shopper.css` + `address.js`
- `modules/Customers/src/Http/Requests/StoreAddressRequest.php` — city ← district
- `modules/Customers/src/Http/Controllers/Admin/CustomerAddressController.php` — persist district / subdistrict
- `modules/Orders/resources/views/admin/create.blade.php` — widget on shipping / billing; Vite address assets
- `resources/js/admin/order-create.js` — prefill via `data-selected` + `storefront:address-sync`
- Tests listed below

No migration. `customer_addresses.district` / `subdistrict` already exist.

---

## Testing

Keep passing:

- `StorefrontShoppingExperienceTest` checkout + address-book `data-thailand-address`
- `CustomerAdminFormTest` create with Thailand address
- `Ws002ShopperChromeIsolationTest` (no `cf-input` in `_location_fields`)
- `AdminOrderCreateTest` POST with `shipping_address.province` / `district` / `subdistrict`

Add:

- Admin customer edit HTML includes `data-thailand-address` on the add-address form
- `POST admin.customers.addresses.store` saves `district`, `subdistrict`, `state`, `city` (city from district when omitted)
- Admin order create HTML includes `data-thailand-address` and `name="shipping_address[province]"` (not a free-text province input)

No JS unit test for the combobox. No browser test in this job.

---

## Error handling

- Location API failure: empty option lists; shopper can still type postal / international fields. No toast required.
- Thailand selected with empty province: existing required rules on city / postal still apply where the form already requires them (storefront address book, checkout). Admin order create stays optional.
- Non-TH country: comboboxes stay hidden/disabled; free-text city and state submit as today.
- Order create customer lookup with a partial address: fill what exists; missing province leaves district/subdistrict empty.
