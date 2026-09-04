# POS Terminal + Warehouse Scanner — Feature (v1.16.0)

**Date:** 2026-09-04  
**Status:** Locked  
**Owner:** `modules/Pos`, `modules/WarehouseScanner`, `resources/css/pos.css`, `resources/css/scanner.css`, `resources/js/pos/**`, `resources/js/scanner/**`  
**Mode:** Path-extract from local `commerce-framework-old` onto current `main`. Not archive merge. Not WS-002 storefront.

```text
v1.15.0 Shopper Chrome          ← already on this tree (unpushed)
v1.16.0 POS + Warehouse Scanner ← this file
later   CX, Appearance, shop header restore
```

```text
git merge feat/commerce-framework-v1
git checkout 84e905c -- .
copy commerce-framework-old wholesale onto main
```

Do not commit `commerce-framework-old/`. Ignore that directory.

Implementation spec: `docs/superpowers/specs/2026-09-04-pos-warehouse-scanner-v1-implementation.md`  
Branch: `feat/pos-warehouse-scanner-v1`

---

## 1. Surfaces (this tag)

```text
GET  /pos                         pos.index          module:pos
GET  /pos/orders                  pos.orders.index   module:pos
GET  /pos/returns                 pos.returns.index  module:pos + feature:pos-returns
POST /pos/api/hold                pos.api.hold       module:pos + feature:pos-hold
POST /pos/api/holds/{id}/resume   pos.api.holds.resume
GET  /warehouse                   warehouse.index    module:warehouse
GET  /warehouse/dashboard         warehouse.dashboard module:warehouse + feature:warehouse-reports
GET  /warehouse/history           warehouse.history  module:warehouse + feature:warehouse-reports
```

Admin POS registers / sessions / thin terminal stay at `/admin/pos/*` behind `module:pos`.

## 2. Definition of Done

```text
POS        full terminal at /pos (search, cart API, hold, checkout, orders, returns)
Scanner    WarehouseScanner module boots; sidebar warehouse.index works
Gates      module() + feature() middleware (404 when disabled, not 403)
Catalog    SystemModuleCatalog: pos, warehouse
Features   pos-hold, pos-returns, warehouse-reports (default ENABLED)
Identity   no SiteIdentityServiceInterface; titles use config('admin.name')
Images     MediaQueryServiceInterface (no ProductImageResolver)
Lookup     BarcodeProductSearchService DTO, not array access
Money      variant.price is already minor units (current Product model)
Nav        Sales → POS = pos.index (module pos); Catalog → Warehouse Scanner (module warehouse)
Vite       pos.css + pos/index.js + scanner.css + scanner/index.js
```

## 3. Out of scope

```text
Customer Experience / Appearance
shop header / mega-menu
Media bulk/import
product workspace
commerce-framework-old commit
feat/commerce-framework-v1 merge
```
