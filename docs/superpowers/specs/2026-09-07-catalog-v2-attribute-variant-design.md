# Catalog V2: Attribute and variant unification (Phase 1)

**Date:** 2026-09-07  
**Status:** Locked  
**Owner:** Catalog (`modules/Catalog`) + Product workspace (`modules/Product`) + storefront PDP/shop (`modules/Cart`)  
**Related:** `docs/superpowers/specs/2026-09-07-product-create-stock-design.md`

This spec is one implementation unit: **one Source of Truth for attributes**, used by specification, variant generation, PDP, and shop filters.

It does **not** change inventory, stock ledger, backorder policy, product type, or SKU uniqueness. Simple products still have exactly one default `product_variants` row as the purchasable. Staff still do not see a variant builder on Simple.

---

## 1. Problem

Today the catalog is four separate worlds:

```text
Organization tab     → product_attribute_values (product_variant_id null)
Variants tab         → products.meta.variant_options + product_variants.meta.options
Shop filters         → product-level rows only
PDP spec / selectors → meta.specifications overlay + JSON options
```

`VariantOptionAttributeProvisioner` copies variant JSON into attribute rows after the fact. Filters still ignore variant-scoped rows. Simple products often have no filterable attributes. Variable products are entered twice (Color on Organization and Color on Variants) and the two copies can disagree.

---

## 2. Goals

1. Catalog attributes are the only named attributes. Products do not invent attribute names on the product form.
2. Simple products can hold specification attributes without a matrix or extra SKUs.
3. Variable products generate variants from the same attributes (those marked **Used for Variations** on that product). Staff do not re-type option names.
4. After save (and after migration), **read and write paths are relation-only**. No JSON option SoT. No half compatibility layer.
5. Shop filters and PDP read the same relations. A variable product matches a variation filter if any variant has that value, even when the default variant does not.
6. Existing variant UUIDs, SKUs, inventory rows, and `products.type` are preserved through migration and through idempotent generate.

Non-goals for Phase 1: search index, CSV format changes, public API redesign, SEO/JSON-LD/feeds, product-specific attribute names, listing cards per variant.

---

## 3. Locked decisions

| Topic | Decision |
|---|---|
| Simple purchasable | One hidden default variant (stock spec). Attributes live on the product, not on that row as options. |
| Attribute names | Global catalog only (`attributes` + attribute sets). |
| New values | Product form may create `attribute_values` on the global attribute immediately. |
| Flags | `is_filterable` and `is_visible` are on the catalog attribute. **Used for Variations** is per product. |
| Variant identity | Canonical sorted list of `attribute_value_id` for the variation axes. `Red+M` equals `M+Red`. |
| Value identity | `attribute_values.code` is canonical (URLs, facets later, CSV later). Labels are display. **`code` is immutable after create**; merchandisers rename via `label` only. |
| `product_attributes` | “This product uses these attributes”, even when no value is set yet. Not the value store. |
| Variation vs spec | If `used_for_variations` is true, there is **no product-level specification value**. Selected values are axis membership for generate only. PDP/filter for that attribute use variant rows. |
| Generate | Explicit button. Idempotent: match by canonical identity; keep UUID, SKU, stock, image. |
| Change of axes | Warn before existing variants are affected. |
| PDP missing combo | Existing combinations only. Impossible pairs are **disabled**, not selectable-then-OOS, not auto-switched. |
| Post-migration I/O | Read path = relation only. Write path = relation only. |
| Variable publish | A variable product may be saved without generating a matrix, but cannot be **published** until at least one variant has a complete variation identity. |

---

## 4. Source of Truth

```text
attributes
  └─ attribute_values (id, code, label, position)
        │
products
  ├─ product_attributes (which attributes this product uses, used_for_variations)
  └─ product_attribute_values
        ├─ variant_id null, not a variation axis → specification value(s)
        ├─ variant_id null, variation axis → axis membership (generate input only)
        └─ variant_id set → that variant’s value on an axis
              │
              └─ variant identity = sort(those attribute_value_ids)
```

JSON `products.meta.variant_options`, `product_variants.meta.options`, and `products.meta.specifications` are **not** sources of truth after cutover. `VariantOptionAttributeProvisioner` is removed from the write path.

---

## 5. Schema

### 5.1 `attribute_values` (new)

Per tenant, per attribute:

- `id`, `uuid`, `tenant_id` (same tenancy as `attributes`)
- `attribute_id` (FK)
- `code` — required, unique with `attribute_id` (and tenant). Lower-snake from the label on create (`burgundy`). Collisions append `-2`, `-3`, … like SKU uniqueness. **Never** derive code from label at read time. **Immutable after insert:** updates may change `label` and `position` only. Reject any write that changes `code` (admin, product-form “add value”, API). Existing filter URLs such as `?color=burgundy` stay valid when the label becomes “Dark Burgundy”.
- `label` — display string; may be renamed without changing `code`
- `position`

Migrate `attributes.options` JSON into this table. After migrate, stop using `attributes.options` as the value list (column may remain unused until a later cleanup).

### 5.2 `product_attributes` (new)

- `product_id`, `attribute_id`
- `used_for_variations` boolean, default false
- `position`
- Unique `(product_id, attribute_id)`

Assigning an attribute set **upserts** a row for every set member. Existing rows with values are not deleted silently when the set changes.

This row may exist with **zero** `product_attribute_values`. That means “the product uses Material” not “Material = Cotton”.

Simple products: every `used_for_variations` is false (UI does not offer the checkbox; save ignores true).

Variable products: at least one attribute with `used_for_variations` true is required before generate.

Variable products may exist without generated variants, but cannot be published (`products.status = published` or `scheduled`) until at least one variant exists **with a complete variation identity** (every Used-for-Variations axis has an `attribute_value_id`). The stock-spec default row with no axis values does not count. Draft save is allowed.

`used_for_variations` is allowed only on discrete types (`select`). Not `text`, `textarea`, `number`, or `boolean`. Phase 1 does not use `multiselect` as a variation axis (each variant has one value per axis). `is_filterable` applies to discrete `attribute_values` only.

### 5.3 `product_attribute_values` (existing, meaning change)

Add `attribute_value_id` nullable FK to `attribute_values`.

Discrete (select) rows: unique `(product_id, attribute_id, product_variant_id, attribute_value_id)` so a product can hold **Red and Blue** as axis membership.

Free-text / number / boolean: `attribute_value_id` is null; unique `(product_id, attribute_id)` at product level (`product_variant_id` null). They cannot be axes or shop filters in Phase 1.

| Kind | `product_variant_id` | `attribute_value_id` | Role |
|---|---|---|---|
| Spec (not an axis) | `null` | set (or `value` text for free-text types) | PDP spec + non-variation filter |
| Axis membership | `null` | one row per selected value | Generate input only. Not PDP spec. Not “the product is Red”. |
| Variant value | variant id | exactly one per axis | Identity + PDP selected variant + variation filter |

Free-text types keep `value` text, `attribute_value_id` null, `product_variant_id` null. They cannot be axes or shop filters in Phase 1.

---

## 6. Variant identity and generate

Identity of a variable variant is the canonical key:

```text
implode('-', sort(attribute_value_id of each variation axis))
```

Do not compare unsorted arrays. Do not include spec-only attributes.

**Generate** is an explicit workspace action. It does not run on every value keystroke.

Cartesian product of axis membership values:

- Identity already has a variant → keep that row (UUID, SKU, `sku_is_auto`, on-hand, image, status).
- Identity is new → create a row. Blank SKU is filled on **save** by the existing `VariantSkuGenerator`, using **`attribute_values.code`** (uppercased) in `product_attributes.position` order, plus the product SKU prefix from the stock spec.
- Identity disappeared because staff removed a value or an axis (after confirm) → existing workspace variant sync: soft-delete extra variants only after the stock spec’s type/order guards (`reserved > 0` or open order lines block).

Changing **Used for Variations** (add/remove an axis) after a matrix exists: the UI must warn that existing variants will be rebuilt/removed. No silent axis change.

A variation attribute has no specification value at product level. The only product-level rows for that attribute are axis membership.

---

## 7. Workspace

One **Attributes** panel. Not Organization Color plus Variants Color.

1. Choose attribute set → upsert `product_attributes`.
2. Each row: catalog name, values, optional “add value” (creates global `attribute_values`).
3. Variable only: **Used for Variations** on discrete attributes.
4. Spec attributes: one value (select) or text.
5. Axis attributes: many values (the membership set).
6. **Generate variants** when each axis has at least one value.
7. Variants tab (variable only): grid of generated rows — image, SKU, price, stock, enabled. No free-form option name/value editor.

Simple: no Used for Variations, no Variants tab, default variant has no option identity.

---

## 8. PDP

After cutover, PDP reads relations only.

| Attribute | What the shopper sees |
|---|---|
| Visible, not an axis | Product spec from `product_variant_id` null |
| Visible, is an axis | Value of the **currently selected variant** |
| Axis membership set | Not shown as “this product is Red” |

Variant pickers list values that appear on at least one variant. A value is **disabled** when, given the other selected axes, **no variant exists** for that combination (example: Blue-S missing while Blue-M exists → with Color=Blue, Size S is disabled).

That is distinct from stock policy: a combination that exists but cannot be purchased (tracked + deny + qty 0) remains selectable; purchasability follows `StockPolicyEvaluator` from the stock spec.

Do not auto-switch to a nearby variant. Do not allow a selected state with no variant row.

`products.meta.specifications`: one-time migrate into attributes where the name maps to a catalog attribute; then PDP **does not** read that overlay.

---

## 9. Shop filters

Only `attributes.is_filterable`.

- Non-axis: product row `product_variant_id` null matches `attribute_value_id`.
- Axis: product is included if **any** variant has that `attribute_value_id` (default Blue still appears for Color=Red if a Red variant exists).
- Filter query/URL uses `attribute_values.code` (e.g. `color=red`), not the label.

In-stock listing constraints stay on the stock spec (`constrainInStock`). This spec only changes **which attribute values** attach to a product.

---

## 10. Migration

One-way cutover. Preserve variant UUID, SKU, inventory items, `products.type`.

1. `attributes.options` → `attribute_values` (`code` from slug of the option string).
2. Each product in a set → upsert `product_attributes` (including unused attributes).
3. Existing product-level `product_attribute_values.value` text → resolve `attribute_value_id` (match code or label on that attribute; create value if missing).
4. `meta.variant_options` + `meta.options` → set `used_for_variations`, write axis membership, write per-variant values, identity canonicalized. Same UUID.
5. Rows already mirrored by `VariantOptionAttributeProvisioner` are reused, not duplicated.
6. **Cut over:**
   - **Read path → relation only** (PDP, shop filters, workspace hydrate).
   - **Write path → relation only** (workspace save, generate). Stop writing `variant_options` / `options` JSON. Remove provisioner from save.
7. Option names that do not match any catalog attribute **in the product’s set** are skipped and logged. Each skipped row must include at least `product_id`, `attribute_name` (the unmatched option axis name), and `option_name` (the unmatched value) so staff can find the source product after migrate. Migration does **not** auto-create new catalog attributes (names stay catalog-only). Staff fix leftovers in admin.

Do not rewrite SKUs. Auto SKU rules apply only to **new** variants after cutover.

---

## 11. Tests (acceptance)

1. Simple product: attribute set assigned → `product_attributes` rows exist with no values; filling Material writes product-level value; no variant options on the default variant; shop filter on Material includes the product.
2. Product form adds Burgundy to Color → `attribute_values.code` is unique (`burgundy` or `burgundy-2`); other products can pick it.
3. Variable: Color+Size used for variations, values Red/Blue and S/M → generate four identities; second generate after SKU/stock/image edits on Blue-M keeps that UUID and fields; adding Size L adds Red-L and Blue-L only.
4. Canonical identity: assigning axes in Size-then-Color order still matches Red+M as one variant.
5. Used for Variations removed after generate → warning; confirming rebuilds/removes variants subject to stock/open-order guards.
6. PDP: Blue-S missing → S disabled when Blue is selected; Blue-M qty 0 + deny remains selectable and not purchasable.
7. Shop filter Color=Red includes a product whose default variant is Blue if a Red variant exists.
8. After migration, workspace save and PDP/filter do not read or write variant JSON; provisioner is not invoked.
9. Variable product with only a default variant (no complete identity) saved as draft succeeds; publishing it is rejected until generate has created at least one identified variant.

---

## 12. Out of scope (later specs)

- Search indexing of attributes
- CSV import/export column layout
- Storefront/Admin HTTP API resource redesign
- JSON-LD / shopping feeds
- Per-product attribute names (non-catalog)
- Shop listing one card per variant
- Deleting unused `attributes.options` JSON column (optional cleanup after Phase 1 is stable)
- Changing stock, backorder, or SKU uniqueness rules
