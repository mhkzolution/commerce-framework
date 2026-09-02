# Barcode v1.4 — Product Consumer Contract

**Date:** 2026-09-02  
**Status:** Locked (ADR-003)  
**Applies to:** `feat/barcode-management-v1` when Phase 1 starts  
**Does not start:** extraction

Barcode is a **consumer of Product**, not a slice of Product Management.

```text
Barcode  ->  depends on Product (read, narrow)
Product  ->  never depends on Barcode
```

No code in `modules/Product` may import `Commerce\Barcode`.

---

## Permission model (keep four codes)

Already in archive `module.json` and already used on `main` nav (`barcode.print`). Do not add codes in v1.4.0.

| Domain | Codes | Role pack |
|--------|--------|-----------|
| Operator | `barcode.print`, `barcode.history.view` | Search, queue, print, view history |
| Admin | `barcode.template.manage`, `barcode.history.reprint` | Templates + reprint |

Route shape on `84e905c` (keep): the Center is gated by `barcode.print`. Template and history routes add the inner permission. Warehouse staff can hold Operator codes without `barcode.template.manage`.

Nav on `main` already uses `barcode.print` as the Center entry. Do not replace `config/admin.php` from the archive.

---

## Product / Variant read surface (main)

On current `main`, these columns exist and are enough:

```text
ProductVariant   uuid, sku, name
Product          uuid, name, seller_uuid
ProductMedia     media_uuid, is_primary, position
```

There is **no** `products.media_uuid`. Thumbnail comes from primary (or first) `ProductMedia.media_uuid`.

`BarcodeProductSearchService` is the **only** Barcode type allowed to query Product / Variant / ProductMedia. After that, downstream uses DTOs.

Forbidden from Barcode:

```text
ProductServiceInterface / ProductService
ProductQueryService
Product events / publishing / search indexer
Product import/export / workspace / analytics
POS / Inventory types
```

Search may match `Product.slug` internally. Do not put `slug` on the Barcode DTO.

---

## Search result DTO

Create in Barcode (not copied from Product):

```text
modules/Barcode/src/DTO/BarcodeSearchResult.php
```

```text
product_uuid
variant_uuid
sku
product_name
variant_name
owner_name
thumbnail_url
```

`variant_name` is `ProductVariant.name` on main (display only).

```text
Product / Variant / ProductMedia
        ↓
BarcodeProductSearchService
        ↓
BarcodeSearchResult
        ↓
ProductQueueItemAdapter → BarcodeQueueItemData
        ↓
Print job payload (JSON lines) → History / Reprint
```

Queue and Print must not query Product, Media, Marketplace, or Settings again.

---

## ADR-001 (tightened) — `BarcodeImageResolver`

```php
resolve(?string $mediaUuid): ?string
```

Returns a ready URL or `null`. Uses `MediaQueryServiceInterface::getUrl()` on **main** (no `preload()` — that method exists on `84e905c` only; do not extend the Media contract in v1.4.0).

Search extracts `media_uuid` from `ProductMedia`, then calls the resolver. The resolver does **not** take a `Product` model.

Missing media → `null` thumbnail. Do not block search or queue.

---

## ADR-002 addendum — owner is presentation

Owner name is label copy, not a business invariant.

```text
Seller.name  (optional; Schema::hasTable / class_exists)
  -> SettingQueryServiceInterface::get('store.name')
  -> config('app.name')
  -> 'Store'
```

Never throw. Never block queue or print. Empty or missing Marketplace is normal on a single-store `main`.

`BarcodeWorkspaceService` may list `{uuid, name}` of active sellers the same guarded way. It must not call Marketplace application services.

---

## Print payload vs Product snapshot

`barcode_print_jobs.payload` stores **label lines** at print time (sku, title, owner, barcode value, thumbnail URL, variant id). That is print history.

It is **not** a Product catalog snapshot: no `barcode_products` table, no copied product rows, no Product writes.

Reprint reads the job payload. It does not re-fetch Product.

Manual print (no product) must keep working.

---

## First-review checklist

Green:

```text
Barcode module boots
Search Product/Variant on main records
Generate barcode value
Create print job
History / reprint from payload
```

Red flag (boundary leak):

```text
use Commerce\Product\Services\*
use Commerce\Product\Events\*
ProductServiceInterface from Barcode
Marketplace *Service from Barcode
Settings module types other than SettingQueryServiceInterface
POS / Inventory / WarehouseScanner / storefront components
```

Allowed `Commerce\Product` imports (search service only):

```text
Commerce\Product\Models\Product
Commerce\Product\Models\ProductVariant
Commerce\Product\Models\ProductMedia
```
