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
                data-analytics-tab="{{ $key }}"
                href="{{ route($tab['route'], $query) }}"
                role="tab"
                @if ($activeTab === $key) aria-current="page" @endif
                @class([
                    'cf-tab rounded-t-md px-3 py-2 text-sm font-medium',
                    'is-active' => $activeTab === $key,
                ])
            >{{ $tab['label'] }}</a>
        @endforeach
    </div>
</div>
