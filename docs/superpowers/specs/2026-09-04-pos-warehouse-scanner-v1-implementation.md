# POS Terminal + Warehouse Scanner v1 — Implementation Spec (v1.16.0)

**Date:** 2026-09-04  
**Status:** Locked  
**Tag:** v1.16.0  
**Branch:** `feat/pos-warehouse-scanner-v1`

Feature: `docs/superpowers/specs/2026-09-04-pos-warehouse-scanner-feature.md`

Source snapshot (read-only, not merged): `commerce-framework-old/` (gitignored).

```text
git merge feat/commerce-framework-v1
git checkout 84e905c -- .
```

Path-extract file-by-file from `commerce-framework-old/modules/Pos` and `commerce-framework-old/modules/WarehouseScanner`. Adapt to current contracts.

---

## 1. Isolation (must fail red before copy)

```text
modules/Pos/** and modules/WarehouseScanner/**
  no SiteIdentityServiceInterface
  no ProductImageResolver
  no AppearanceController / CustomerExperienceController
  no commerce-framework-old paths

routes
  /pos*            includes module:pos
  hold/resume      includes feature:pos-hold
  /pos/returns*    includes feature:pos-returns
  /warehouse*      includes module:warehouse
  dashboard/history includes feature:warehouse-reports

host
  config('commerce.modules.pos') === true
  config('commerce.modules.warehouse') === true
  SystemModuleCatalog contains pos and warehouse
```

## 2. Host wiring

- `composer.json` require `commerce/warehouse-scanner: @dev` and PSR-4 `Commerce\\WarehouseScanner\\`
- `packages/commerce/core/config/commerce.php` `'warehouse' => true`
- `SystemModuleCatalog` entries `pos` (sort 85) and `warehouse` (sort 90), not core
- `SystemFeatureCatalog`: `pos-hold` / `pos-returns` (module `pos`), `warehouse-reports` (module `warehouse`)
- `config/admin.php`: POS → `pos.index` + `module` => `pos`; Warehouse Scanner + `module` => `warehouse`
- Vite input: `resources/css/pos.css`, `resources/js/pos/index.js`, `resources/css/scanner.css`, `resources/js/scanner/index.js`
- Update `BarcodeHostWiringTest` so it no longer forbids pos/scanner Vite entries

## 3. POS adaptations

- Layout title: `config('admin.name', config('commerce.name'))`. Drop `x-site.favicon` / `x-site.fonts`.
- `PosProductImageService` uses `MediaQueryServiceInterface::getUrl()` from primary `product.media` uuid.
- Treat `ProductVariant.price` as minor units. Do not multiply by 100.
- Keep `/pos` open-session form in baht (`* 100` on submit). Admin terminal opening float stays cents (existing old admin form).
- `PosSaleService::cart()` returns `PosCartService` (hold, price override, mixed payments).
- Keep existing `PosTerminalTest` working: open session before add-item on admin terminal.

## 4. Scanner adaptations

- `ScannerProductLookupService` maps `BarcodeSearchResult` via `toArray()`, then adds stock fields from `InventoryQueryServiceInterface`.
- Layout title same as POS (no SiteIdentity). Drop site favicon/font components.

## 5. Tests

```text
tests/Unit/Pos/PosWarehouseIsolationTest.php
tests/Feature/Pos/PosHostWiringTest.php
tests/Feature/Pos/PosModuleGateTest.php
tests/Feature/WarehouseScanner/WarehouseScannerTest.php
tests/Feature/WarehouseScanner/WarehouseScannerModuleGateTest.php
+ ported PosInterfaceTest, PosAdvancedFeaturesTest, PosOrdersReturnsTest, PosSessionLifecycleTest
```

Money in ported tests uses current cents (e.g. price `5000` not `50` when asserting `grand_total` `10000` for qty 2).

`FeatureServiceTest` catalog lists include the three new flags.

## 6. Out of scope

CX, Appearance, shop header, Media bulk, product workspace, committing `commerce-framework-old`.
