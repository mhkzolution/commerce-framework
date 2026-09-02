# Barcode v1.4 — Print Queue + Template Contract

**Date:** 2026-09-02  
**Status:** Locked (ADR-004)  
**Applies to:** `feat/barcode-management-v1` when Phase 1 starts  
**Does not start:** extraction

```text
Every print operation creates a print job first.
Templates and reprints operate on stored payloads,
not on live Product, Media, Marketplace, or Settings data.
```

---

## Queue-first (already true on `84e905c` for Print Now)

Keep this shape. Do not add a code path that renders labels without a `barcode_print_jobs` row.

```text
UI queue (client)
  → POST print
  → create BarcodePrintJob (payload frozen)
  → barcode_print_jobs
  → BarcodePrintService / BarcodeLabelRenderer
  → HTML or PDF
```

Warehouse Scanner v1.5 can insert the same job type. v1.4 does not persist a server-side draft queue (`draft` is unused).

---

## Label payload (immutable)

Job `payload.lines[]` is the frozen label snapshot. After insert, **never update** those lines.

Minimum fields per line (map from existing queue item / archive keys):

```text
barcode_value     # archive: barcode (Code128 source; often SKU)
sku               # archive: sku or barcode
product_name      # archive: title
owner_name
image_url         # archive: thumbnail_url; queue UI only on v1.4 stickers
```

If the product is later renamed, reprint still shows the stored `product_name`.

This is **not** a Product catalog copy: no `barcode_products` table, no Product writes. `variant_id` / `product_id` on a line are optional references, never used to re-fetch live data at render.

---

## Template contract

`BarcodeTemplate` is **layout and label style** (paper, grid, margins, orientation, `show_*`). It is not a Product-aware engine and not a stored HTML/PHP template.

See ADR-006: `docs/superpowers/specs/2026-09-02-barcode-template-layout-contract.md`.

Renderer fields (user-facing placeholders → stored payload):

```text
{{ barcode }}  → barcode_value → SVG via BarcodeLabelRenderer
{{ sku }}      → sku
{{ name }}     → product_name
{{ owner }}    → owner_name
{{ image }}    → image_url (optional; archive print sheet does not print the photo)
```

Forbidden inside template service, renderer, print Blade, expansion, reprint:

```text
Product::find
Seller::find
MediaQueryServiceInterface
SettingQueryServiceInterface
BarcodeProductSearchService
BarcodeOwnerResolver
```

Owner resolution happens **before** the job is created (search / manual enqueue). `BarcodeQueueItemNormalizer` on `84e905c` can call `OwnerResolver` for empty manual `owner_name` — that must **not** run when expanding a stored job.

---

## Renderer boundary

```text
BarcodeTemplateService     # paper / grid / style
        ↓
stored LabelPayload        # job.payload.lines
        ↓
BarcodeLabelExpansionService
        ↓
BarcodePrintService
  → BarcodeLayoutCalculator
  → BarcodeLabelRenderer   # SVG from barcode_value only
        ↓
HTML / PDF
```

Controllers pass a `BarcodePrintJob`. Blade receives computed cells, not Eloquent Product/Media models.

`dompdf` is used by archive `BarcodePrintService` but is **absent on main** and absent from archive `modules/Barcode/composer.json`. Phase 1: add `dompdf/dompdf` on the Barcode module (and root require). Do not copy archive root `composer.json`.

---

## Job status vocabulary

Archive writes `status = completed` at insert. Reject that word for v1.4.

Allowed values only:

```text
queued
printed
failed
```

Sync Print Now in v1.4:

```text
insert queued
  → render
  → printed   or   failed
```

Migration default: `queued` (adapt after copy). History i18n: `status_printed` / `status_failed` — stop using `status_completed`.

Do not mix `completed`, `done`, `success`. `draft` is not a DB state in v1.4.

---

## Reprint rule

Archive `HistoryController::reprint` returns JSON payload so the UI can refill the queue. That can mutate lines and create a **new** job. Reject for v1.4.

```text
history.reprint
  → render the same job UUID from stored payload
  → print HTML / PDF
```

- Do not `INSERT` a clone row
- Do not `UPDATE` payload
- Do not re-query Product / Seller / Media / Settings
- Do not send lines back to the client for editing

A future reprint audit log (v1.5+) is out of scope.

---

## Adapt after copy (ADR-004)

Same paths, not byte-for-byte from `84e905c`:

```text
modules/Barcode/src/Services/BarcodePrintJobService.php      # queued → printed|failed
modules/Barcode/src/Http/Controllers/Admin/HistoryController.php
modules/Barcode/src/Services/BarcodeQueueItemNormalizer.php  # no live owner on stored lines
modules/Barcode/database/migrations/...print_jobs...         # default queued
modules/Barcode/resources/lang/{en,th}/admin.php
modules/Barcode/resources/views/components/print-history-table.blade.php
modules/Barcode/composer.json                                # dompdf
tests/Unit/Barcode/BarcodeQueueArchitectureTest.php          # drop SiteIdentity mock
```

---

## First-review checklist

Green:

```text
Print Now creates a job before render
payload JSON frozen at insert
Template / renderer read payload only
Reprint uses the same job payload
No Product query during reprint
status ∈ {queued, printed, failed}
```

Red:

```text
Reprint → Product::find()
Template / Blade → Product model
Queue expand → Seller query
Renderer → MediaQueryServiceInterface
status = completed
reprint clones a new row or mutates payload
```
