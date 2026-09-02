# Barcode v1.4 — Test Contract

**Date:** 2026-09-02  
**Status:** Locked (ADR-005)  
**Applies to:** `feat/barcode-management-v1` when Phase 1 starts  
**Does not start:** extraction  
**Does not add:** Dusk / Playwright / browser E2E

Tests exist so extraction **fails when an ADR is violated**, not so the suite is green by accident.

```text
Unit (Barcode-owned, fast)
  → Feature (HTTP + DB on main schema)
  → Gate C (v1.1–v1.3 regression + Barcode contract)
```

---

## What archive already has

Copy these, then **adapt** cases that contradict ADR-002 / ADR-004:

```text
tests/Feature/Barcode/BarcodeCenterAdminTest.php   # 471 lines; super-admin only
tests/Unit/Barcode/BarcodeLabelStyleTest.php
tests/Unit/Barcode/BarcodeQueueArchitectureTest.php  # mocks SiteIdentity — rewrite
tests/Unit/Barcode/NumericSequenceGeneratorTest.php
```

Archive gaps (must add on the extraction branch):

```text
Operator vs Admin vs 403
status queued → printed | failed
reprint from frozen payload after Product rename
owner fallback without SiteIdentity
search with no media (thumbnail_url null)
Barcode without marketplace_sellers
```

Archive cases that **must change** after copy:

```text
test_reprint_endpoint_returns_queue_payload
test_manual_barcode_reprint_restores_queue_payload
```

Those assert JSON refill of the UI queue. ADR-004 reprint re-renders the same job. Rewrite them; do not keep the JSON-refill assertions.

---

## Unit (`tests/Unit/Barcode`)

No HTTP. Prefer mocked contracts over full Product graphs.

Keep:

```text
NumericSequenceGeneratorTest
BarcodeLabelStyleTest
BarcodeQueueArchitectureTest   # drop SiteIdentityServiceInterface mock
```

Add (or extend):

| Case | Locks |
|------|--------|
| `BarcodeImageResolver` with Media mock `getUrl` → URL / `null` | ADR-001 |
| `BarcodeOwnerResolver`: Seller missing → `store.name` → `app.name` → `'Store'`; never throws | ADR-002 |
| Queue expansion of stored lines does **not** call OwnerResolver | ADR-004 |
| Search mapper / service returns `BarcodeSearchResult`; serialized payload has no Eloquent `product` key | ADR-003 |

`BarcodeProductSearchService` talking to real tables belongs in Feature, not a pure unit, unless the test builds the DTO without querying.

---

## Feature

### `BarcodeCenterAdminTest` (keep, split scenarios)

All archive cases run as **super-admin**. Add role packs from ADR-003:

```text
Operator   barcode.print + barcode.history.view
           Center, search, print, history OK
           templates CRUD → 403

Admin      + barcode.template.manage + barcode.history.reprint
           template CRUD + reprint OK

None       guest or user without barcode.* → 403 / redirect
```

Search (main Product / Variant / ProductMedia):

```text
SKU hit returns DTO JSON keys
  product_uuid, variant_uuid, sku, product_name, owner_name, thumbnail_url
  no nested product model

Product with no ProductMedia → 200, thumbnail_url = null, no throw
```

Owner (HTTP or service in Feature):

```text
no Seller              → store.name
no store.name          → config('app.name')
all missing            → 'Store', POST print still creates a job
```

Manual print without a product stays (already in archive).

### New `tests/Feature/Barcode/BarcodePrintJobTest.php`

| Case | Expect |
|------|--------|
| POST print | row exists; status `queued` then `printed` after render (never `completed`) |
| renderer throws | status `failed` |
| reprint after `product.name` change | HTML/PDF still shows stored `product_name` |
| reprint after Product row deleted | still 200 from payload |
| reprint | same job UUID; payload JSON unchanged; no clone row |

Do **not** require a global `Product::query()` mock. Deleting or renaming the Product after insert is the contract. A query listener is optional extra, not the gate.

---

## Isolation (proves Barcode is a consumer)

These run on **main** Product/Media/Settings, not `84e905c` expansions.

```text
No Marketplace     Schema::hasTable('marketplace_sellers') false or empty
                   → print job still created

No Media URL       MediaQueryServiceInterface::getUrl() returns null
                   → search + print still work

No Product extras  tests must not reference ProductImageResolver,
                   SiteIdentityServiceInterface, Product events, import/export
```

“Product expansion off” is not a runtime flag. It means Feature tests boot current `main` Product. If a test cannot run without archive Product files, the boundary leaked.

---

## Gate C (v1.4)

**C1 — must stay green** (unchanged from Boundary):

```text
tests/Unit/Features/FeatureServiceTest.php
tests/Feature/Features/SystemFeatureAdminTest.php
tests/Feature/Features/EnsureFeatureEnabledTest.php
tests/Feature/Cms/CmsScheduledPublishingTest.php
tests/Feature/Cms/ScheduledPublishingFeatureFlagTest.php
tests/Feature/Cms/CmsBlogV1Test.php
tests/Feature/Cms/CmsAdminTest.php
```

**C2 — Barcode contract** (minimum before extraction review):

```text
tests/Feature/Barcode/BarcodeCenterAdminTest.php
tests/Feature/Barcode/BarcodePrintJobTest.php
tests/Unit/Barcode/BarcodeLabelStyleTest.php
tests/Unit/Barcode/BarcodeQueueArchitectureTest.php
tests/Unit/Barcode/NumericSequenceGeneratorTest.php
```

C2 must fail if reprint follows live Product, status is `completed`, or SiteIdentity is required.

No browser E2E in v1.4. Print HTML/PDF assertions stay in PHPUnit Feature tests.

---

## Manifest

New tests live under `tests/Feature/Barcode/**` and `tests/Unit/Barcode/**` (already allowlisted). Create `BarcodePrintJobTest.php` on the branch; it is not in `84e905c`.
