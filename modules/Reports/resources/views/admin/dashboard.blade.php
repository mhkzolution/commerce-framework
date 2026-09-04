@extends('layouts.admin')

@section('title', __('reports::admin.title'))

@section('page')
    <x-admin.page :title="__('reports::admin.title')" :description="__('reports::admin.description')">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[['label' => __('reports::admin.title'), 'active' => true]]" />
        </x-slot:breadcrumb>

        <x-slot:secondaryActions>
            <x-admin.button variant="secondary" :href="route('admin.dashboard.export', request()->query())">
                <x-admin.icon name="arrow-down-tray" class="h-4 w-4" />
                {{ __('reports::admin.export_csv') }}
            </x-admin.button>
            @if (Route::has('admin.reports.index'))
                <x-admin.button variant="secondary" :href="route('admin.reports.index')">
                    {{ __('reports::admin.all_reports') }}
                </x-admin.button>
            @endif
        </x-slot:secondaryActions>

        <x-slot:filters>
            <div class="flex flex-wrap items-end gap-3">
                <div class="flex flex-wrap gap-2">
                    @foreach (['7d' => __('reports::admin.range_7d'), '30d' => __('reports::admin.range_30d'), '90d' => __('reports::admin.range_90d')] as $key => $label)
                        <x-admin.button
                            :href="route('admin.dashboard', ['range' => $key])"
                            :variant="$summary['preset'] === $key ? 'primary' : 'secondary'"
                        >{{ $label }}</x-admin.button>
                    @endforeach
                </div>
                <form method="GET" class="flex flex-wrap items-end gap-3">
                    <input type="hidden" name="range" value="custom">
                    <label class="text-sm">
                        <span class="mb-1 block text-muted">{{ __('reports::admin.from') }}</span>
                        <input type="date" name="from" value="{{ $summary['from'] }}" class="cf-input py-2">
                    </label>
                    <label class="text-sm">
                        <span class="mb-1 block text-muted">{{ __('reports::admin.to') }}</span>
                        <input type="date" name="to" value="{{ $summary['to'] }}" class="cf-input py-2">
                    </label>
                    <x-admin.button type="submit" variant="secondary">{{ __('reports::admin.apply') }}</x-admin.button>
                </form>
            </div>
        </x-slot:filters>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-admin.stat-card
                :label="__('reports::admin.revenue_period')"
                :value="number_format($summary['revenue_period'] / 100, 2) . ' ' . $summary['currency']"
                :hint="__('reports::admin.revenue_all_time', [
                    'amount' => number_format($summary['revenue_total'] / 100, 2),
                    'currency' => $summary['currency'],
                ])"
            />
            <x-admin.stat-card
                :label="__('reports::admin.orders_period')"
                :value="(string) $summary['orders_period']"
                :hint="__('reports::admin.orders_total', ['count' => $summary['orders_total']])"
            />
            <x-admin.stat-card
                :label="__('reports::admin.orders_pending')"
                :value="(string) $summary['orders_pending']"
                :hint="__('reports::admin.orders_pending_hint')"
            />
            <x-admin.stat-card
                :label="__('reports::admin.average_order_value')"
                :value="number_format($summary['average_order_value'] / 100, 2) . ' ' . $summary['currency']"
                :hint="__('reports::admin.average_order_value_hint')"
            />
            @if (module_active('blog') && isset($blogStats))
                <x-admin.stat-card
                    :label="__('reports::admin.blog_posts')"
                    :value="(string) $blogStats['published']"
                    :hint="__('reports::admin.blog_posts_hint', ['count' => $blogStats['posts']])"
                />
            @endif
        </div>

        <div class="mt-6 grid gap-6 xl:grid-cols-2">
            <x-admin.bar-chart
                :series="$revenueSeries"
                currency="{{ $summary['currency'] }}"
                :title="__('reports::admin.daily_revenue')"
            />
            <x-admin.bar-chart
                :series="$revenueSeries"
                value-key="orders"
                format="number"
                :title="__('reports::admin.daily_orders')"
            />
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            <x-admin.card :title="__('reports::admin.orders_by_status')">
                <ul class="space-y-2 text-sm">
                    @forelse ($ordersByStatus as $status => $count)
                        <li class="flex items-center justify-between rounded-md bg-primary-subtle px-3 py-2">
                            <span class="text-text">{{ $orderStatuses[$status] ?? $status }}</span>
                            <x-admin.badge>{{ $count }}</x-admin.badge>
                        </li>
                    @empty
                        <li class="text-muted">{{ __('reports::admin.no_orders') }}</li>
                    @endforelse
                </ul>
            </x-admin.card>

            <x-admin.card :title="__('reports::admin.sales_by_channel')">
                <x-admin.table.shell>
                    <x-slot:head>
                        <tr class="text-left text-xs uppercase tracking-wide text-muted">
                            <th class="px-4 py-3">{{ __('reports::admin.channel') }}</th>
                            <th class="px-4 py-3">{{ __('reports::admin.orders') }}</th>
                            <th class="px-4 py-3">{{ __('reports::admin.revenue') }}</th>
                        </tr>
                    </x-slot:head>
                    @forelse ($salesByChannel as $row)
                        <tr>
                            <td class="px-4 py-3">{{ $row['label'] }}</td>
                            <td class="px-4 py-3">{{ $row['orders'] }}</td>
                            <td class="px-4 py-3">{{ number_format($row['revenue'] / 100, 2) }} {{ $summary['currency'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-4 py-8 text-center text-muted">{{ __('reports::admin.no_sales') }}</td></tr>
                    @endforelse
                </x-admin.table.shell>
            </x-admin.card>
        </div>

        <x-admin.card :title="__('reports::admin.recent_orders')" class="mt-6">
            <x-admin.table.shell>
                <x-slot:head>
                    <tr class="text-left text-xs uppercase tracking-wide text-muted">
                        <th class="px-4 py-3">{{ __('reports::admin.order') }}</th>
                        <th class="px-4 py-3">{{ __('reports::admin.customer') }}</th>
                        <th class="px-4 py-3">{{ __('reports::admin.total') }}</th>
                        <th class="px-4 py-3">{{ __('reports::admin.status') }}</th>
                    </tr>
                </x-slot:head>

                @forelse ($recentOrders as $order)
                    @php
                        $orderBadge = match ($order->status) {
                            'completed' => 'published',
                            'pending' => 'pending',
                            'cancelled' => 'archived',
                            default => 'info',
                        };
                    @endphp
                    <tr>
                        <td class="px-4 py-3">
                            @if (Route::has('admin.orders.show'))
                                <x-admin.button variant="link" :href="route('admin.orders.show', $order)" class="!px-0 font-medium">
                                    {{ $order->order_number }}
                                </x-admin.button>
                            @else
                                <span class="font-medium text-text">{{ $order->order_number }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-muted">{{ $order->customer_name ?? $order->customer_email ?? __('reports::admin.guest') }}</td>
                        <td class="px-4 py-3">{{ number_format($order->grand_total / 100, 2) }} {{ $order->currency }}</td>
                        <td class="px-4 py-3">
                            <x-admin.badge :variant="$orderBadge">
                                {{ $orderStatuses[$order->status] ?? $order->status }}
                            </x-admin.badge>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-8 text-center text-muted">{{ __('reports::admin.no_orders') }}</td></tr>
                @endforelse
            </x-admin.table.shell>
        </x-admin.card>
    </x-admin.page>
@endsection
