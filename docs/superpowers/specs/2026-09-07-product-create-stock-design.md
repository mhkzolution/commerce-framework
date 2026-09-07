# Product create: type, SKU, and stock policy

**Date:** 2026-09-07  
**Status:** Draft for review  
**Owner:** Product workspace (`modules/Product`) + Inventory ledger (`modules/Inventory`) + purchasability in Cart / POS / checkout  
**Related:** `docs/superpowers/specs/2026-09-04-order-inventory-reservation.md`

This spec is one implementation unit. It does **not** move inventory into the Product module. The product form owns **policy + initial on-hand**. The Inventory module owns **ledger, movements, reserved, available**.

---

## 1. Problem

Admin **New Product** is a tabbed workspace. Staff cannot set the things they need at create time:

- Product type is not chosen. Save infers `simple` vs `variable` from `count(variants) > 1` (`ProductWorkspaceSaveService`).
- Stock is not on the form. Quantity lives at `/admin/inventory/purchasable/{uuid}` and only works after a variant UUID exists.
- There is no “track stock” flag. Storefront already treats `available === null` as in stock (`ProductDetailBuilder::inStock`).
- There is no backorder policy. `InventoryService::reserve()` always throws when `on_hand - reserved < qty`.
- Variant image, SKU, and status already exist on the variant grid. Stock on that grid is a link out to Inventory.

---

## 2. Goals

1. Staff pick **Simple** or **Variable** on create. The value is stored on `products.type`. It is not inferred from variant count.
2. Simple create is one scrolling page: identity, price, SKU, stock policy, media, description, categories. No variant builder.
3. Variable create has no parent stock block. Variants are rows with image, SKU, enabled state, and stock fields.
4. Saving a tracked quantity goes through `InventoryService::setOnHand()` so adjustment movements keep the audit trail.
5. Checkout, POS, and shop “in stock” filters honor `track_inventory` and `backorder_policy`.

Non-goals: multi-warehouse stock, editing `reserved` from the product form, per-variant backorder in this version, rewriting the Inventory admin screens.

---

## 3. Product type

### 3.1 Stored type

`products.type` remains `'simple' | 'variable'`.

The workspace save payload includes an explicit `type`. Persist that value. **Do not** assign type from `count($data->variants)`.

Simple products still have exactly one default `product_variants` row (purchasable identity, price, SKU). Staff never see the variant builder.

### 3.2 Create UI

At the top of create and edit:

- Control: Simple | Variable.
- Switching Simple → Variable on an unsaved form reveals the variant builder and hides the simple stock block.
- Switching Variable → Simple on an unsaved form hides the builder and keeps the first variant as the default purchasable.

### 3.3 Type change after save

| Transition | Rule |
|---|---|
| Simple → Variable | Always allowed. Existing default variant becomes the first row. Staff add options and more variants. |
| Variable → Simple | Allowed only when every extra variant can be discarded: no `inventory_items.reserved > 0`, and no order line items referencing those variant UUIDs. The remaining variant becomes the simple default. Otherwise reject with a concrete error (which variants block the change). |

Deleting extra variants still uses the existing workspace sync (soft-delete variants not in the payload) **after** the guard passes.

---

## 4. SKU

`product_variants.sku` already has `unique(tenant_id, sku)`. Copying the parent SKU onto every variant would violate that constraint.

### 4.1 Effective SKU

Every persisted variant has a non-empty SKU that is unique per tenant.

- Simple: the SKU field is the default variant’s SKU.
- Variable: the product-level SKU field is a **prefix only**. It is not a sellable SKU by itself.

### 4.2 Empty SKU on save

If the staff leave a variant SKU blank:

1. Generate `{PREFIX}-{OPTION}-{OPTION}-…` uppercased, non-alphanumerics collapsed to `-`.
   Example: prefix `TSHIRT`, options Red + S → `TSHIRT-RED-S`.
2. If that value collides in the tenant, append `-2`, `-3`, …
3. Persist `sku_is_auto = true`.

If the staff type a SKU, persist it and set `sku_is_auto = false`. Later edits of an auto SKU also clear `sku_is_auto`.

Reject save on duplicate SKU with a field error. Do not silently reuse another variant’s SKU.

---

## 5. Stock policy vs ledger

```text
Product form                          Inventory module
─────────────────────────────         ─────────────────────────────
product.type                          inventory_items.on_hand
variant.track_inventory               inventory_items.reserved
product.backorder_policy              stock_movements
on-hand input → setOnHand()           available = on_hand - reserved
```

The form never `UPDATE inventory_items.on_hand` directly.

### 5.1 Columns

**`products`**

- `backorder_policy` string, not null, default `'deny'`
  Values: `deny` | `notify` | `allow`
  Labels (th): ไม่อนุญาต | ยินยอม แต่ต้องระบุลูกค้า | อนุญาต

**`product_variants`**

- `track_inventory` boolean, not null, default `true` for **new** rows
- `sku_is_auto` boolean, not null, default `false`

**`inventory_items`**

No new quantity columns. Ledger stays `on_hand`, `reserved`, movements.

### 5.2 Track stock

`track_inventory = false`

- Do not create or update an inventory item from the product form.
- Storefront / POS treat availability as unknown (`null`) → purchasable, same as today’s `inStock($available === null)`.
- Inventory index does not list these purchasables as tracked rows.

`track_inventory = true`

- An `inventory_items` row **must** exist for that variant UUID.
- Never persist `track_inventory = true` without an inventory item.

### 5.3 First time enabling track

When a variant (or simple default) goes from `track_inventory = false` to `true` (including a product that never had an item):

1. Create the inventory item.
2. Initial `on_hand = 0`, `reserved = 0`.
3. The form **requires** an explicit quantity (`>= 0`). Do not infer from sales, siblings, or parent.
4. Apply that quantity with `InventoryService::setOnHand($uuid, $qty, 'Product workspace')`, which writes an adjustment movement for the delta from 0.

Empty quantity while enabling track is a validation error, not an implicit zero save that looks like a real count. An entered `0` is an explicit count and is allowed.

### 5.4 New variable variants while parent tracks stock

When a new variant row is persisted and the product’s variants are tracking stock (`track_inventory = true` on the new row; default true when the product is in tracked mode):

- Immediately `firstOrCreate` inventory item `on_hand = 0`, `reserved = 0` for that UUID.
- If the form submitted a quantity for that row, then `setOnHand`.
- This forbids the state `track_inventory = true` with no inventory item (including options generated after the product already exists, e.g. adding `RED-L`).

### 5.5 Quantity field

The editable number is **on hand**, not available.

Beside it, show read-only **Reserved** and **Available** from the ledger when the variant already exists.

`setOnHand` already rejects on-hand below reserved. Surface that as a form error.

Unchecking track on save: leave historical inventory rows and movements in place; stop using them for purchasability. Do not delete the ledger.

### 5.6 Backorder

Policy lives on the **product** (one value for simple and for all variants). Not a stock quantity.

| Policy | `available > 0` | `available <= 0` and tracking |
|---|---|---|
| `deny` | Purchasable | Not purchasable. `reserve()` keeps today’s insufficient-stock error. |
| `notify` | Purchasable | Purchasable. Order/UI flagged as backorder (customer must be told). |
| `allow` | Purchasable | Purchasable. No extra customer-facing backorder emphasis required. |

`notify` vs `allow` do not differ in ledger math. They differ in messaging and an order/line flag.

`InventoryService::reserve()` (and checkout / POS quantity checks that currently throw `Insufficient stock`) must read the product’s `backorder_policy` plus `track_inventory`:

- Not tracking → do not reserve; do not block.
- Tracking + `deny` → current reserve rule.
- Tracking + `notify` or `allow` → allow `reserved` to exceed `on_hand`.

Confirm / `sale()` (timing still follows `2026-09-04-order-inventory-reservation.md`):

- Decrease `reserved` by the line quantity when a reservation exists.
- Decrease `on_hand` by the line quantity but **floor at 0**. This version does not store negative on-hand.
- Example: `on_hand = 0`, `reserved = 5`, confirm qty 5 → `on_hand = 0`, `reserved = 0`, movement records the sale without a negative balance.

Receive / `setOnHand` after a backorder restock the ledger as usual; they do not auto-allocate to older orders in this version.

Shop in-stock filter (`ShopProductQuery::constrainInStock`) must include:

- variants with `track_inventory = false`, and
- tracked variants with `available > 0`, and
- tracked variants with `available <= 0` whose product policy is `notify` or `allow`.

---

## 6. Workspace UI

### 6.1 Simple (create and edit)

Single column, no Variants tab/builder:

1. Type switch  
2. Name, status, visibility  
3. SKU, price, compare-at  
4. Stock card: track checkbox, quantity (if tracked), backorder radios, reserved/available if known  
5. Media  
6. Description, categories, collections  

SEO / organization / advanced stay reachable on edit (existing tabs or a collapsed block). They are not required to save a sellable simple product.

### 6.2 Variable

Same header fields except **no product-level quantity**. Backorder radios stay on the product (shared). Track stock can be a product-level control that sets `track_inventory` on all variants in this version (no per-row track toggle unless already cheap to add; default: one product-level track flag copied onto every variant).

Variant rows (existing builder, extended):

- Image (existing media picker)
- SKU (placeholder: auto from prefix + options)
- Enabled: maps to existing `meta.status` `active` vs `draft` (archived remains in the status select)
- On-hand when tracking, plus reserved/available when the UUID exists
- Price / compare / delete as today

Matrix generate still creates the cartesian rows. Each new persisted row follows §5.4.

### 6.3 Create vs edit

Create uses the type-aware layout above so staff can save without visiting Inventory. Edit uses the same type-aware layout so the two screens do not diverge.

---

## 7. Save pipeline

`ProductWorkspaceSaveService` (or a collaborator in Product that **calls** Inventory contracts):

1. Persist product including `type` and `backorder_policy`.
2. Sync variants including SKU generation, `sku_is_auto`, `track_inventory`.
3. For each tracked variant: ensure inventory item; `setOnHand` when a quantity was submitted or when enabling track (required qty).
4. For each newly created tracked variant with no qty: item exists at 0/0.
5. Do not call Inventory for untracked variants.

Product module depends on `InventoryServiceInterface` the same way CSV import already calls `setOnHand`. No new Product-owned stock table.

---

## 8. Data migration

- `products.backorder_policy`: backfill `'deny'` (matches current reserve behavior).
- `product_variants.sku_is_auto`: backfill `false`.
- `product_variants.track_inventory`: `true` if an `inventory_items` row exists for that UUID; otherwise `false`.
- Existing `products.type` values stay as stored. Stop future inference; do not rewrite types in the migration.
- Existing SKUs stay as-is. Unique constraint already applies to non-null SKUs.

---

## 9. Call sites that must honor policy

| Call site | Change |
|---|---|
| `InventoryService::reserve` | Honor track + backorder |
| `Cart\CheckoutService` insufficient-stock | Same |
| `Cart\CartService` / `PosCartService` insufficient-stock | Same |
| `Orders\OrderService` insufficient-stock | Same |
| `ProductDetailBuilder::inStock` / card / quick view | `null` if untracked; backorder policies still purchasable when qty is 0 |
| `ShopProductQuery::constrainInStock` | See §5.6 |

`2026-09-04-order-inventory-reservation.md` remains the reservation **timing** spec (when to reserve vs sale vs release). This spec only changes **whether** a reserve is allowed at qty 0.

---

## 10. Tests (acceptance)

1. Create simple with type simple, track on, qty 10 → one default variant, inventory on_hand 10, one adjustment movement, `products.type = simple`.
2. Create simple with track off → no inventory item; PDP in stock.
3. Create variable with two option values → `type = variable`, no parent stock field in the payload; each variant unique SKU; blank SKUs auto-generated and unique.
4. Duplicate typed SKU rejected.
5. Enable track on a previously untracked variant without qty → 422; with qty 7 → item created, on_hand 7 via `setOnHand`.
6. Add a third variant to a tracked variable product → inventory item 0/0 exists after save even if qty omitted.
7. `setOnHand` is used (movement exists); raw on_hand assignment is not the save path.
8. Simple → variable allowed; variable → simple blocked while a second variant has reserved qty or order lines.
9. Checkout: tracked + deny + available 0 → cannot buy; tracked + allow + available 0 → can buy; untracked → can buy.
10. In-stock shop filter includes untracked and backorder-allow zero-qty products.

---

## 11. Out of scope

- Multi-location / warehouse bins
- Negative `on_hand`
- Per-variant `backorder_policy`
- Deleting inventory rows when unchecking track
- Changing Inventory admin adjust/receive UX beyond using the same ledger
- Replacing the whole workspace with a WooCommerce clone of every advanced tab
