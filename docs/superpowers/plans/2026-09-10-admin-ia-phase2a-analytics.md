# Analytics Canvas (Phase 2A) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the Analytics card launcher with one server-rendered canvas (Sales / Orders / Products tabs) while keeping every existing report route and the frozen sidebar.

**Architecture:** Each index controller still loads only its own query services. A shared Blade tab row links to canonical child routes and copies `ReportFilter::toQuery()`. `ReportsHubController` returns `SalesReportController::canvas()` so Overview is a Sales alias, not a fourth query set.

**Tech Stack:** Laravel, PHPUnit, Blade anonymous components (`x-reports::*`), existing `ReportFilter`.

**Spec:** `docs/superpowers/specs/2026-09-10-admin-ia-phase2a-analytics-design.md`

## Global Constraints

- Phase 1 IA is frozen. Do not edit `config/admin.php` or `AdminNavigationIaTest`.
- Compose + preserve routes. All four indexes stay HTTP 200. No new route names.
- Tab identity is the path. No `?tab=`, no session, no `localStorage`.
- Shared filter keys only: `range`, `from`, `to`, `channel` via `ReportFilter::toQuery()`.
- Do not use `x-admin.form.tabs` (client-side; would require all three bodies in one response).
- Do not call unrelated report query methods when rendering a tab.
- Do not change CSV / PDF / print views or query services.
- Do not touch Homepage Builder, Navigation, Settings Hub, or `DashboardController`.
- Tab chrome labels: `admin::nav.labels.sales_reports`, `order_reports`, `product_reports`. Title/breadcrumb: `admin::nav.groups.analytics`.
- Leave hardcoded Thai report body copy as-is.

## File map

| File | Responsibility |
|---|---|
| `tests/Feature/Reports/AdminReportsTest.php` | Hub canvas, deep-links, filter copy, refresh, query isolation |
| `modules/Reports/resources/views/components/analytics-tabs.blade.php` | Canonical tab `<a>` row |
| `modules/Reports/resources/views/admin/reports/{index,sales,orders,products}.blade.php` | Shared shell + existing bodies |
| `modules/Reports/src/Http/Controllers/Admin/SalesReportController.php` | `canvas(): View` used by hub and sales index |
| `modules/Reports/src/Http/Controllers/Admin/ReportsHubController.php` | Delegate to sales canvas |
| `modules/Reports/src/Http/Controllers/Admin/OrdersReportController.php` | Pass `activeTab = orders` |
| `modules/Reports/src/Http/Controllers/Admin/ProductsReportController.php` | Pass `activeTab = products` |

---

### Task 1: Failing tests for canvas, isolation, deep-link, refresh

**Files:**
- Modify: `tests/Feature/Reports/AdminReportsTest.php`
- Test: `tests/Feature/Reports/AdminReportsTest.php`

**Interfaces:**
- Consumes: existing `AdminReportsTest` checkout helpers
- Produces: failing tests that the later tasks must turn green

- [ ] **Step 1: Replace `test_reports_hub_is_accessible` and add the new cases**

Keep the three existing sales/orders/products tests. Replace the hub test and append the new methods. Do not change export/pdf/print assertions.

```php
use Commerce\Reports\Services\OrdersReportQueryService;
use Commerce\Reports\Services\ProductsReportQueryService;
use Commerce\Reports\Services\SalesReportQueryService;
use Mockery;

public function test_reports_hub_renders_sales_canvas_not_card_launcher(): void
{
    $this->actingAs(User::query()->first())
        ->get(route('admin.reports.index'))
        ->assertOk()
        ->assertSee(__('admin::nav.groups.analytics'), false)
        ->assertSee(__('admin::nav.labels.sales_reports'), false)
        ->assertSee(__('admin::nav.labels.order_reports'), false)
        ->assertSee(__('admin::nav.labels.product_reports'), false)
        ->assertDontSee('group block rounded-xl', false)
        ->assertSee('data-analytics-tab="sales"', false)
        ->assertSee('aria-current="page"', false);
}

public function test_deep_links_mark_the_matching_tab_current(): void
{
    $admin = User::query()->first();

    $orders = $this->actingAs($admin)
        ->get(route('admin.reports.orders.index'))
        ->assertOk();
    $this->assertActiveTab($orders->getContent(), 'orders');

    $products = $this->actingAs($admin)
        ->get(route('admin.reports.products.index'))
        ->assertOk();
    $this->assertActiveTab($products->getContent(), 'products');

    $sales = $this->actingAs($admin)
        ->get(route('admin.reports.sales.index'))
        ->assertOk();
    $this->assertActiveTab($sales->getContent(), 'sales');

    $hub = $this->actingAs($admin)
        ->get(route('admin.reports.index'))
        ->assertOk();
    $this->assertActiveTab($hub->getContent(), 'sales');
}

public function test_tab_links_copy_shared_filter_query(): void
{
    $html = $this->actingAs(User::query()->first())
        ->get(route('admin.reports.sales.index', ['range' => '7d', 'channel' => 'web']))
        ->assertOk()
        ->getContent();

    $this->assertActiveTab($html, 'sales');
    $this->assertStringContainsString('range=7d', $html);
    $this->assertStringContainsString('channel=web', $html);
    $this->assertMatchesRegularExpression(
        '/data-analytics-tab="orders"[^>]*href="[^"]*range=7d[^"]*channel=web/',
        $html,
    );
    $this->assertMatchesRegularExpression(
        '/data-analytics-tab="products"[^>]*href="[^"]*range=7d[^"]*channel=web/',
        $html,
    );
}

public function test_refresh_preserves_active_tab_and_filters(): void
{
    $url = route('admin.reports.orders.index', ['range' => '30d', 'channel' => 'web']);
    $admin = User::query()->first();

    $first = $this->actingAs($admin)->get($url)->assertOk()->getContent();
    $second = $this->actingAs($admin)->get($url)->assertOk()->getContent();

    $this->assertActiveTab($first, 'orders');
    $this->assertActiveTab($second, 'orders');
    $this->assertMatchesRegularExpression('/<option[^>]*value="web"[^>]*selected/', $second);
    $this->assertMatchesRegularExpression('/name="range"[^>]*value="custom"|name="range" value="custom"/', $second);
}

public function test_sales_and_hub_do_not_run_orders_or_products_queries(): void
{
    $this->actingAs(User::query()->first());

    $this->spySiblingQueries();
    $this->get(route('admin.reports.index'))->assertOk();
    $this->assertOrdersQueryIdle();
    $this->assertProductsQueryIdle();

    $this->spySiblingQueries();
    $this->get(route('admin.reports.sales.index'))->assertOk();
    $this->assertOrdersQueryIdle();
    $this->assertProductsQueryIdle();
}

public function test_orders_tab_does_not_run_sales_series_or_products_queries(): void
{
    $this->actingAs(User::query()->first());
    $sales = Mockery::spy(SalesReportQueryService::class);
    $products = Mockery::spy(ProductsReportQueryService::class);
    $this->app->instance(SalesReportQueryService::class, $sales);
    $this->app->instance(ProductsReportQueryService::class, $products);

    $response = $this->get(route('admin.reports.orders.index'))->assertOk();

    $sales->shouldNotHaveReceived('dailySeries');
    $sales->shouldNotHaveReceived('byChannel');
    $sales->shouldNotHaveReceived('summary');
    $products->shouldNotHaveReceived('products');
    $this->assertArrayNotHasKey('dailySeries', $response->original->getData());
    $this->assertArrayNotHasKey('products', $response->original->getData());
}

public function test_products_tab_does_not_run_orders_or_sales_series_queries(): void
{
    $this->actingAs(User::query()->first());
    $orders = Mockery::spy(OrdersReportQueryService::class);
    $this->app->instance(OrdersReportQueryService::class, $orders);

    $response = $this->get(route('admin.reports.products.index'))->assertOk();

    $orders->shouldNotHaveReceived('orders');
    $orders->shouldNotHaveReceived('byStatus');
    $data = $response->original->getData();
    $this->assertArrayHasKey('summary', $data);
    $this->assertArrayHasKey('products', $data);
    $this->assertArrayNotHasKey('dailySeries', $data);
    $this->assertArrayNotHasKey('byChannel', $data);
    $this->assertArrayNotHasKey('orders', $data);
}

/**
 * @return string the active tab id
 */
private function assertActiveTab(string $html, string $tab): string
{
    $this->assertMatchesRegularExpression(
        '/data-analytics-tab="'.$tab.'"[^>]*aria-current="page"/',
        $html,
    );
    foreach (array_diff(['sales', 'orders', 'products'], [$tab]) as $other) {
        $this->assertDoesNotMatchRegularExpression(
            '/data-analytics-tab="'.$other.'"[^>]*aria-current="page"/',
            $html,
        );
    }

    return $tab;
}

private function spySiblingQueries(): void
{
    $this->ordersSpy = Mockery::spy(OrdersReportQueryService::class);
    $this->productsSpy = Mockery::spy(ProductsReportQueryService::class);
    $this->app->instance(OrdersReportQueryService::class, $this->ordersSpy);
    $this->app->instance(ProductsReportQueryService::class, $this->productsSpy);
}

private function assertOrdersQueryIdle(): void
{
    $this->ordersSpy->shouldNotHaveReceived('orders');
    $this->ordersSpy->shouldNotHaveReceived('byStatus');
}

private function assertProductsQueryIdle(): void
{
    $this->productsSpy->shouldNotHaveReceived('products');
}
```

Add two typed properties on the test class:

```php
private mixed $ordersSpy = null;
private mixed $productsSpy = null;
```

- [ ] **Step 2: Run the new tests and confirm they fail**

Run: `php artisan test --filter=AdminReportsTest`

Expected: FAIL — missing `data-analytics-tab`, hub still shows card grid, spies unused because markup/controller not updated.

Do not implement production code in this task.

- [ ] **Step 3: Commit tests only**

```bash
git add tests/Feature/Reports/AdminReportsTest.php
git commit -m "$(cat <<'EOF'
test: fail Analytics canvas tab and query-isolation contracts

EOF
)"
```

---

### Task 2: Tab component

**Files:**
- Create: `modules/Reports/resources/views/components/analytics-tabs.blade.php`

**Interfaces:**
- Consumes: `$filter` (`ReportFilter`), `$activeTab` (`sales`|`orders`|`products`)
- Produces: `x-reports::analytics-tabs` with canonical hrefs

- [ ] **Step 1: Add the component**

Do not use `x-admin.form.tabs`. Links only.

```blade
@props([
    'filter',
    'activeTab',
])

@php
    $query = $filter->toQuery();
    $tabs = [
        'sales' => [
            'label' => __('admin::nav.labels.sales_reports'),
            'route' => 'admin.reports.sales.index',
        ],
        'orders' => [
            'label' => __('admin::nav.labels.order_reports'),
            'route' => 'admin.reports.orders.index',
        ],
        'products' => [
            'label' => __('admin::nav.labels.product_reports'),
            'route' => 'admin.reports.products.index',
        ],
    ];
@endphp

<div class="mb-4 border-b border-border">
    <div class="flex flex-wrap gap-1" role="tablist">
        @foreach ($tabs as $key => $tab)
            <a
                href="{{ route($tab['route'], $query) }}"
                role="tab"
                data-analytics-tab="{{ $key }}"
                @if ($activeTab === $key) aria-current="page" @endif
                @class([
                    'cf-tab rounded-t-md px-3 py-2 text-sm font-medium',
                    'is-active' => $activeTab === $key,
                ])
            >{{ $tab['label'] }}</a>
        @endforeach
    </div>
</div>
```

- [ ] **Step 2: Commit**

```bash
git add modules/Reports/resources/views/components/analytics-tabs.blade.php
git commit -m "$(cat <<'EOF'
feat: add server-side Analytics tab links

EOF
)"
```

---

### Task 3: Sales canvas + Overview alias

**Files:**
- Modify: `modules/Reports/src/Http/Controllers/Admin/SalesReportController.php`
- Modify: `modules/Reports/src/Http/Controllers/Admin/ReportsHubController.php`
- Modify: `modules/Reports/resources/views/admin/reports/sales.blade.php`
- Modify: `modules/Reports/resources/views/admin/reports/index.blade.php`

**Interfaces:**
- Consumes: `SalesReportController::canvas(): View`
- Produces: Overview and Sales URLs render the Sales tab

- [ ] **Step 1: Share canvas from SalesReportController**

Replace `index()` with a public `canvas()` that `index()` returns. Hub must call `canvas()`, not copy query calls.

```php
public function index(): View
{
    return $this->canvas();
}

public function canvas(): View
{
    $filter = ReportFilter::fromRequest();

    return view('reports::admin.reports.sales', array_merge($this->sharedViewData($filter), [
        'activeTab' => 'sales',
        'summary' => $this->sales->summary($filter),
        'dailySeries' => $this->sales->dailySeries($filter),
        'byChannel' => $this->sales->byChannel($filter),
    ]));
}
```

- [ ] **Step 2: Hub delegates to sales canvas**

```php
public function __construct(
    private readonly SalesReportController $sales,
) {}

public function index(): View
{
    return $this->sales->canvas();
}
```

Delete the `$reports` card array. Hub returns `$this->sales->canvas()` (the sales Blade), not `reports::admin.reports.index`.

Replace `index.blade.php` with a stub so a mistaken `view('reports::admin.reports.index')` cannot resurrect the launcher:

```blade
{{-- Unused. Overview is SalesReportController::canvas() → reports::admin.reports.sales --}}
```

- [ ] **Step 3: Wrap sales Blade with Analytics chrome**

Change title, breadcrumb, insert tabs **above** the existing stats (inside `x-admin.page`, after filters slot is fine — tabs should sit above filters).

```blade
@section('title', __('admin::nav.groups.analytics'))

@section('page')
    <x-admin.page
        :title="__('admin::nav.groups.analytics')"
        description="สรุปยอดขายและจำนวนออเดอร์แยกตามวัน"
    >
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => __('admin::nav.groups.analytics'), 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-slot:secondaryActions>
            <x-reports::export-actions
                :filter="$filter"
                export-route="admin.reports.sales.export"
                pdf-route="admin.reports.sales.pdf"
                print-route="admin.reports.sales.print"
            />
        </x-slot:secondaryActions>

        <x-reports::analytics-tabs :filter="$filter" :active-tab="$activeTab" />

        <x-slot:filters>
            <x-reports::filters
                :filter="$filter"
                :action="route('admin.reports.sales.index')"
                :channels="$channels"
            />
        </x-slot:filters>

        {{-- existing stat cards, chart, tables unchanged --}}
```

When the request is Overview (`admin.reports.index`), filter form action must stay on Overview so Apply does not jump to `/admin/reports/sales`. Pass `filterAction` from the controller:

```php
'filterAction' => request()->routeIs('admin.reports.index')
    ? route('admin.reports.index')
    : route('admin.reports.sales.index'),
```

Use `:action="$filterAction"` in the sales filters slot.

- [ ] **Step 4: Run isolation + hub tests**

Run: `php artisan test --filter='test_reports_hub|test_sales_and_hub|test_tab_links|test_deep_links'`

Expected: those pass except orders/products deep-link and orders refresh until Task 4.

- [ ] **Step 5: Commit**

```bash
git add modules/Reports/src/Http/Controllers/Admin/SalesReportController.php \
  modules/Reports/src/Http/Controllers/Admin/ReportsHubController.php \
  modules/Reports/resources/views/admin/reports/sales.blade.php \
  modules/Reports/resources/views/admin/reports/index.blade.php
git commit -m "$(cat <<'EOF'
feat: render Analytics Overview as the Sales canvas

EOF
)"
```

---

### Task 4: Orders and Products tabs

**Files:**
- Modify: `modules/Reports/src/Http/Controllers/Admin/OrdersReportController.php`
- Modify: `modules/Reports/src/Http/Controllers/Admin/ProductsReportController.php`
- Modify: `modules/Reports/resources/views/admin/reports/orders.blade.php`
- Modify: `modules/Reports/resources/views/admin/reports/products.blade.php`

**Interfaces:**
- Consumes: `x-reports::analytics-tabs`, `activeTab`
- Produces: canonical Orders/Products URLs with the same chrome

- [ ] **Step 1: Pass `activeTab` from orders and products controllers**

In `OrdersReportController::index` add `'activeTab' => 'orders'` to the view data.

In `ProductsReportController::index` add `'activeTab' => 'products'`.

Do not inject `SalesReportQueryService` into the orders controller. Do not call `dailySeries` from products (summary stays).

- [ ] **Step 2: Same chrome on orders and products Blades**

Title + breadcrumb like sales. Insert `<x-reports::analytics-tabs :filter="$filter" :active-tab="$activeTab" />` above the filters slot.

Orders filter `:action="route('admin.reports.orders.index')"`.

Products filter `:action="route('admin.reports.products.index')"`.

Keep existing tables/stats/export slots.

- [ ] **Step 3: Run the full reports test file plus IA**

Run: `php artisan test --filter='AdminReportsTest|AdminNavigationIaTest'`

Expected: PASS. Hub is not a card grid. Four sidebar Analytics children unchanged. Export/pdf/print still 200.

- [ ] **Step 4: Commit**

```bash
git add modules/Reports/src/Http/Controllers/Admin/OrdersReportController.php \
  modules/Reports/src/Http/Controllers/Admin/ProductsReportController.php \
  modules/Reports/resources/views/admin/reports/orders.blade.php \
  modules/Reports/resources/views/admin/reports/products.blade.php
git commit -m "$(cat <<'EOF'
feat: put Orders and Products on the Analytics canvas

EOF
)"
```

---

## Spec coverage

| Spec rule | Task |
|---|---|
| Shared filter contract | 1 (`test_tab_links_copy_shared_filter_query`), 2 (tab hrefs), 3 (`filterAction`) |
| Tab-specific filter contract | 2 (tabs copy `toQuery()` only; no private keys in 2A) |
| Canonical routes | 2 (tab hrefs never `admin.reports.index`), 3 (hub alias) |
| Refresh via URL | 1 (`test_refresh_preserves_active_tab_and_filters`) |
| Query isolation | 1 (spies), 3–4 (controllers) |
| Deep-link active tab | 1 (`test_deep_links_mark_the_matching_tab_current`) |
| Frozen IA | Do not touch `config/admin.php`; run `AdminNavigationIaTest` |
| Export/pdf/print unchanged | Existing tests in Task 1 file, still green in Task 4 |
| No `x-admin.form.tabs` | Task 2 |

## Placeholder scan

No TBD. `filterAction` is specified for Overview vs Sales. Spies use `Mockery::spy` + `instance()` because the query services are singletons.

---

Plan complete and saved to `docs/superpowers/plans/2026-09-10-admin-ia-phase2a-analytics.md`. Two execution options:

**1. Subagent-Driven (recommended)** — I dispatch a fresh subagent per task, review between tasks, fast iteration

**2. Inline Execution** — Execute tasks in this session using executing-plans, batch execution with checkpoints

Which approach?
