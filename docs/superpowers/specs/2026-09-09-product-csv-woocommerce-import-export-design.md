# Product CSV Import / Export (WooCommerce format)

**Date:** 2026-09-09  
**Status:** Locked  
**Owner:** Product import/export (`modules/Product/src/Import`, `modules/Product/src/Export`)  
**Related:** `docs/superpowers/specs/2026-09-08-catalog-v2-importer-reserved-code-design.md`

WooCommerce-style CSV is the **only** product file format. Admin export, Admin import, the import template, and `php artisan product:import-woocommerce` share the same column names. There is no Excel format.

---

## 1. Surfaces

| Surface | Route / command |
|---|---|
| Admin export | `admin.products.export` |
| Admin import | `admin.products.import.show` / `admin.products.import.store` |
| CSV template (headers only) | `admin.products.import.template` |
| CLI import | `php artisan product:import-woocommerce {file}` |

Encoding: UTF-8. UTF-8 BOM on the header cell is stripped.

A row is processed only when `ID`, `SKU`, or `Name` is non-empty.

---

## 2. Matching

SKU is the primary identifier.

- New SKU → create
- Existing SKU (already in the catalog) → update (Admin always; CLI unless `--force` is off, in which case existing SKUs are skipped)
- Duplicate SKU **in the same file**: first row wins; later rows are skipped with a warning; import continues

CLI `--force` does not override in-file first-wins.

---

## 3. Types

| Type | Parent | Meaning |
|---|---|---|
| `simple` or empty | empty | One product, one SKU |
| `variable` | empty | Parent; option universe from Attribute 1–4 |
| `variation` (or `variant`) | parent SKU, numeric ID, or `id:123` | Child of that parent |

`Parent=id:123` matches the parent row’s `ID` even when the parent also has a SKU.

---

## 4. Columns that mutate catalog data

Identity: `SKU`, `Name`, `Type`, `Parent`  
Visibility: `Published` (`1` published / `0` draft), `Visibility in catalog` (`visible` / `hidden`)  
Pricing: `Sale price`, `Regular price` (commas stripped; sale > 0 is the selling price; higher regular becomes compare-at)  
Inventory: `Stock` (integer, minimum 0)  
Taxonomy: `Categories` (`Apparel > Tees` and/or commas; missing names are created), `Tags`, `Collections` (comma list; missing names are created)  
Media: `Images` (comma-separated URLs; downloaded and attached)  
Brand: `Brands` (Brand model — never an attribute named Brand)  
Seller: name, slug, or UUID  
Attributes: `Attribute 1–4 name` + `value(s)` (max 4)  
Condition: `Meta: condition` → attribute `สภาพ` when that attribute exists on the WooCommerce set

Exporter `WooCommerceProductExporter::headers()` is the canonical header list. Extra WooCommerce columns may be present and are ignored on import.

---

## 5. Catalog V2

Reserved `Attribute N name` codes (after `Str::slug($name, '_')`): `q`, `category`, `brand`, `sort`, `availability`, `price_min`, `price_max`, `page`.

Skip that attribute only; the product still imports; warning is recorded. Do not increment product `skipped` / `errors` for this.

Search ranking, synonyms, and suggest terms are **not** CSV columns. After import, indexing continues to use name, description, SKU, brand, categories, and attributes.

WooCommerce Default color is often named `สี`, not `Color`.

---

## 6. Summary

Admin and CLI collect: created, updated, skipped, warnings, errors (plus images linked). Duplicate later rows increment skipped and warnings. Reserved attribute skips increment warnings only.

Import continues on per-row failures.
