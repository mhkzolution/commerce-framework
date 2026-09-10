# Admin IA Phase 2A: Analytics consolidation

**Date:** 2026-09-10  
**Status:** Approved  
**Owner:** Reports module (`modules/Reports`)  
**Pattern:** compose + redirect + preserve routes  
**Related:** Phase 1 admin IA (frozen)

Phase 1 navigation is immutable. This spec consolidates Analytics **screens**, not the sidebar.

**Not this spec:** Homepage Builder, Navigation consolidation, Settings Hub, Home dashboard (`admin.dashboard`), new report metrics, new permissions, new routes.

---

## Decisions

- One Analytics canvas with three tabs: Sales, Orders, Products.
- Each tab loads **only that tab’s queries**. Do not stack all three reports on one request.
- Named routes stay registered. HTTP 200 on all four index URLs.
- `admin.reports.index` (Overview) is no longer a card launcher. It renders the same canvas with the Sales tab selected.
- `admin.reports.sales.index` / `orders.index` / `products.index` render the same canvas with the matching tab selected.
- Export / PDF / print URLs are unchanged and stay out of the tab chrome (print/PDF remain standalone documents).
- Sidebar Analytics children stay **Overview, Sales Reports, Order Reports, Product Reports**. 2A does not drop destinations.
- `reports.dashboard.view` remains the only permission.
- Query services, CSV, and PDF builders do not change.
- Tab identity lives in the **path**, not in a `tab=` query param, not in session, not in `localStorage`.

---

## Why Overview and Sales share a tab

The hub is a launcher with no data. The unified job is “read the business,” not “pick a report card.” Overview therefore opens the canvas immediately.

Sidebar still has both Overview and Sales Reports (IA frozen). Both land on the Sales tab. Destination reduction (4 → 1) is a later track, not 2A.

---

## Canonical routes

Tab identity is the route path. There is no `?tab=` parameter.

| Role | Route name | Path | Active tab |
|---|---|---|---|
| Overview alias (Sales) | `admin.reports.index` | `/admin/reports` | Sales |
| Sales canonical | `admin.reports.sales.index` | `/admin/reports/sales` | Sales |
| Orders canonical | `admin.reports.orders.index` | `/admin/reports/orders` | Orders |
| Products canonical | `admin.reports.products.index` | `/admin/reports/products` | Products |

Rules:

- Tab links in the canvas always point at the **canonical** child routes (`sales.index`, `orders.index`, `products.index`), never at `admin.reports.index`.
- Overview sidebar item still hits `admin.reports.index`. That URL is an alias of the Sales tab, not a fourth tab.
- Deep-link: `GET /admin/reports/orders` must render Orders as the active tab. Same for sales and products.
- `admin.reports.{sales|orders|products}.{export|pdf|print}` keep today’s paths and handlers. They are not tabs.
- No new route names. No `Route::redirect` required; all four indexes return 200 with the shared shell.

Sidebar highlight follows the request route (`AdminUi::navIsActive`). Overview highlights on `/admin/reports`. Sales Reports highlights on `/admin/reports/sales`. That is expected while IA is frozen.

---

## Shared filter contract

Keys that belong on every Analytics index, tab link, filter GET, and export/pdf/print link:

| Key | Source | Notes |
|---|---|---|
| `range` | `ReportFilter::toQuery()` | `7d` / `30d` / `90d` / `custom` |
| `from` | same | `Y-m-d` |
| `to` | same | `Y-m-d` |
| `channel` | same | Omitted when “all channels” |

Rules:

- Use existing `ReportFilter`. Do not add a second filter DTO in 2A.
- Tab hrefs append `ReportFilter::toQuery()` so switching tabs keeps range/channel.
- Filter form `action` is the **current canonical index** (or Overview when the request is `admin.reports.index`) so Apply does not jump tabs.
- Export / PDF / print already receive `toQuery()`. Keep that.

---

## Tab-specific filter contract

Today no tab has private query keys. Orders status chips are display-only.

If a tab later adds private keys (example: Orders `status`):

- Private keys appear only on that tab’s filter form and on that tab’s export/pdf/print URLs.
- Tab links to **other** tabs copy **only** the shared keys (`range`, `from`, `to`, `channel`). They must not forward private keys.
- A private key on another tab’s URL is ignored (not an error).

2A does not add private keys. The contract exists so tab markup copies `toQuery()` as a whole today, and a future Orders `status` does not leak into Sales.

Do not introduce `tab` as a query key.

---

## Refresh persistence

The URL is the only store for tab and filter state.

- Refresh of `/admin/reports/orders?range=7d&channel=web` must show Orders + those filters. No session flash, no `localStorage`, no JS router.
- Tabs are `<a href>` GETs, not `x-admin.form.tabs` (that component is client-side and would require all three bodies in one response).
- A second identical GET is the test for refresh: same path, same query, same active tab, same filter fields.

---

## UI

Shared shell (new Blade component, included by hub + three report pages):

1. Page title: Analytics / การวิเคราะห์ (`admin::nav.groups.analytics`).
2. Breadcrumb: Analytics (active). No “Reports → child” trail.
3. Tab row: Sales Reports, Order Reports, Product Reports. Labels from `admin::nav.labels.sales_reports` / `order_reports` / `product_reports`.
4. Active tab from the current route (hub and sales both mark Sales). Mark with `aria-current="page"` on the active tab link.
5. Shared filter bar (`x-reports::filters`). Form `action` is the current index route.
6. Tab-specific body: today’s sales / orders / products markup, unchanged.
7. Export actions remain in `secondaryActions` and still target that tab’s export/pdf/print routes.

Do not restyle charts, tables, or print/PDF layouts. Do not translate the hardcoded Thai report body copy in this spec (pre-existing). Tab chrome is the only new copy, and it uses existing nav keys so EN/TH follow admin locale.

---

## Controllers

Keep `SalesReportController`, `OrdersReportController`, `ProductsReportController`. They keep building the same view data.

`ReportsHubController::index` must not duplicate sales queries inline. It calls a shared method on `SalesReportController` (or a one-line action that sales index already uses) and returns that view.

Pass `activeTab` (`sales` | `orders` | `products`) into the view. Hub uses `sales`.

Query isolation:

- Hub and Sales may call `SalesReportQueryService` only (`summary`, `dailySeries`, `byChannel`).
- Orders may call `OrdersReportQueryService` only (`orders`, `byStatus`).
- Products may call `ProductsReportQueryService::products` and the existing `SalesReportQueryService::summary` (already on the products page). It must not call `dailySeries`, `byChannel`, or `OrdersReportQueryService`.

Do not merge the three query services. Do not add a mega-controller that loads every report.

---

## Navigation (frozen)

`config/admin.php` Analytics group is unchanged:

- Overview → `admin.reports.index`
- Sales Reports → `admin.reports.sales.index`
- Order Reports → `admin.reports.orders.index`
- Product Reports → `admin.reports.products.index`

`AdminNavigationIaTest` route and label assertions stay as they are. Command palette still lists four destinations; they now open the same canvas at different tabs.

Home (`admin.dashboard`) is not an Analytics tab.

---

## Tests (TDD)

Failing tests first. Extend `tests/Feature/Reports/AdminReportsTest.php`.

- Hub is 200, shows tab labels, shows sales canvas (filters + sales stats), does **not** render the three-card launcher (`group block rounded-xl` cards with `route('admin.reports.sales.index')` as the primary UI).
- Deep-link: `GET admin.reports.orders.index` has `aria-current="page"` on the Orders tab, not Sales. Same for products and sales.
- `GET admin.reports.sales.index?range=7d&channel=web` is 200; Orders and Products tab hrefs contain `range=7d` and `channel=web`.
- Refresh: a second GET of that same orders/sales URL with the same query still has the same active tab and the same `from`/`to`/`channel` field values.
- Query isolation: spies on the other two query services must receive no calls on hub/sales; Orders must not call products or sales query methods; Products must not call `OrdersReportQueryService` or `SalesReportQueryService::dailySeries`.
- Existing sales filter/export/pdf/print tests still pass.
- Existing orders list + CSV test still pass.
- Existing products SKU test still pass.

Do not change `AdminNavigationIaTest` in 2A.

---

## Files

| File | Change |
|---|---|
| `modules/Reports/resources/views/components/analytics-tabs.blade.php` | Server-side tab links |
| `modules/Reports/resources/views/admin/reports/index.blade.php` | Canvas with Sales body (no card grid) |
| `modules/Reports/resources/views/admin/reports/sales.blade.php` | Include tabs; breadcrumb/title via shell |
| `modules/Reports/resources/views/admin/reports/orders.blade.php` | Same |
| `modules/Reports/resources/views/admin/reports/products.blade.php` | Same |
| `modules/Reports/src/Http/Controllers/Admin/ReportsHubController.php` | Render sales canvas, not cards |
| `modules/Reports/src/Http/Controllers/Admin/SalesReportController.php` | Shared canvas method for hub; pass `activeTab` |
| `modules/Reports/src/Http/Controllers/Admin/{Orders,Products}ReportController.php` | Pass `activeTab` |
| `tests/Feature/Reports/AdminReportsTest.php` | Hub, tabs, isolation, deep-link, refresh |

Not in this job: `DashboardController`, dashboard Blade, CSV/PDF/print views, `ReportFilter`, query services, `config/admin.php`.

---

## Acceptance criteria

- Merchant can move Sales → Orders → Products without leaving Analytics and without losing the selected date/channel filter.
- `/admin/reports`, `/admin/reports/sales`, `/admin/reports/orders`, `/admin/reports/products` all 200.
- Deep-link URLs open the correct active tab.
- Refreshing the page preserves the active tab and filter state (URL is the store).
- Tab switch does not trigger unrelated report queries.
- CSV / PDF / print still download from the same route names.
- Sidebar Analytics group still has four children with the same labels and routes.
- No new routes, permissions, modules, or tables.
- `php artisan test --filter=AdminReportsTest` and `AdminNavigationIaTest` pass.

---

## Spec self-review

- No Homepage / Navigation / Settings work.
- No IA regroup or label rename.
- Destination count does **not** drop in 2A (explicit).
- Shared vs tab-private filter keys, canonical paths, and refresh-via-URL are explicit.
- Overview ≡ Sales tab is a conscious trade-off while the sidebar stays frozen.
