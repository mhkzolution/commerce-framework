@extends('layouts.admin')

@section('title', 'แดชบอร์ด')

@section('page')
    <x-admin.page title="แดชบอร์ด" description="ภาพรวมยอดขายและคำสั่งซื้อในช่วงเวลาที่เลือก">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[['label' => 'แดชบอร์ด', 'active' => true]]" />
        </x-slot:breadcrumb>

        <x-slot:secondaryActions>
            <x-admin.button variant="secondary" :href="route('admin.dashboard.export', request()->query())">
                <x-admin.icon name="arrow-down-tray" class="h-4 w-4" />
                ส่งออก CSV
            </x-admin.button>
            <x-admin.button variant="secondary" :href="route('admin.reports.index')">
                รายงานทั้งหมด
            </x-admin.button>
        </x-slot:secondaryActions>

        <x-slot:filters>
            <div class="flex flex-wrap items-end gap-3">
                <div class="flex flex-wrap gap-2">
                    @foreach (['7d' => '7 วัน', '30d' => '30 วัน', '90d' => '90 วัน'] as $key => $label)
                        <x-admin.button
                            :href="route('admin.dashboard', ['range' => $key])"
                            :variant="$summary['preset'] === $key ? 'primary' : 'secondary'"
                        >{{ $label }}</x-admin.button>
                    @endforeach
                </div>
                <form method="GET" class="flex flex-wrap items-end gap-3">
                    <input type="hidden" name="range" value="custom">
                    <label class="text-sm">
                        <span class="mb-1 block text-muted">ตั้งแต่</span>
                        <input type="date" name="from" value="{{ $summary['from'] }}" class="cf-input py-2">
                    </label>
                    <label class="text-sm">
                        <span class="mb-1 block text-muted">ถึง</span>
                        <input type="date" name="to" value="{{ $summary['to'] }}" class="cf-input py-2">
                    </label>
                    <x-admin.button type="submit" variant="secondary">ใช้ตัวกรอง</x-admin.button>
                </form>
            </div>
        </x-slot:filters>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-admin.stat-card
                label="ยอดขาย (ช่วงที่เลือก)"
                :value="number_format($summary['revenue_period'] / 100, 2) . ' ' . $summary['currency']"
                :hint="'ทั้งหมด ' . number_format($summary['revenue_total'] / 100, 2) . ' ' . $summary['currency']"
            />
            <x-admin.stat-card
                label="ออเดอร์ (ช่วงที่เลือก)"
                :value="(string) $summary['orders_period']"
                :hint="$summary['orders_total'] . ' ออเดอร์ทั้งหมด'"
            />
            <x-admin.stat-card
                label="รอดำเนินการ"
                :value="(string) $summary['orders_pending']"
                hint="รอชำระเงินหรือดำเนินการ"
            />
            <x-admin.stat-card
                label="ยอดเฉลี่ยต่อออเดอร์"
                :value="number_format($summary['average_order_value'] / 100, 2) . ' ' . $summary['currency']"
                hint="ออเดอร์ที่ชำระแล้วในช่วงที่เลือก"
            />
        </div>

        <div class="mt-6 grid gap-6 xl:grid-cols-2">
            <x-admin.bar-chart
                :series="$revenueSeries"
                currency="{{ $summary['currency'] }}"
                title="ยอดขายรายวัน"
            />
            <x-admin.bar-chart
                :series="$revenueSeries"
                value-key="orders"
                format="number"
                title="จำนวนออเดอร์รายวัน"
            />
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            <x-admin.card title="ออเดอร์ตามสถานะ">
                <ul class="space-y-2 text-sm">
                    @forelse ($ordersByStatus as $status => $count)
                        <li class="flex items-center justify-between rounded-md bg-primary-subtle px-3 py-2">
                            <span class="text-text">{{ $orderStatuses[$status] ?? $status }}</span>
                            <x-admin.badge>{{ $count }}</x-admin.badge>
                        </li>
                    @empty
                        <li class="text-muted">ไม่มีออเดอร์ในช่วงเวลานี้</li>
                    @endforelse
                </ul>
            </x-admin.card>

            <x-admin.card title="ยอดขายตามช่องทาง">
                <x-admin.table.shell>
                    <x-slot:head>
                        <tr class="text-left text-xs uppercase tracking-wide text-muted">
                            <th class="px-4 py-3">ช่องทาง</th>
                            <th class="px-4 py-3">ออเดอร์</th>
                            <th class="px-4 py-3">ยอดขาย</th>
                        </tr>
                    </x-slot:head>
                    @forelse ($salesByChannel as $row)
                        <tr>
                            <td class="px-4 py-3">{{ $row['label'] }}</td>
                            <td class="px-4 py-3">{{ $row['orders'] }}</td>
                            <td class="px-4 py-3">{{ number_format($row['revenue'] / 100, 2) }} {{ $summary['currency'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-4 py-8 text-center text-muted">ไม่มียอดขายในช่วงเวลานี้</td></tr>
                    @endforelse
                </x-admin.table.shell>
            </x-admin.card>
        </div>

        <x-admin.card title="ออเดอร์ล่าสุด" class="mt-6">
            <x-admin.table.shell>
                <x-slot:head>
                    <tr class="text-left text-xs uppercase tracking-wide text-muted">
                        <th class="px-4 py-3">เลขออเดอร์</th>
                        <th class="px-4 py-3">ลูกค้า</th>
                        <th class="px-4 py-3">ยอดรวม</th>
                        <th class="px-4 py-3">สถานะ</th>
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
                        <td class="px-4 py-3 text-muted">{{ $order->customer_name ?? $order->customer_email ?? 'ลูกค้าทั่วไป' }}</td>
                        <td class="px-4 py-3">{{ number_format($order->grand_total / 100, 2) }} {{ $order->currency }}</td>
                        <td class="px-4 py-3">
                            <x-admin.badge :variant="$orderBadge">
                                {{ $orderStatuses[$order->status] ?? $order->status }}
                            </x-admin.badge>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-8 text-center text-muted">ไม่มีออเดอร์ในช่วงเวลานี้</td></tr>
                @endforelse
            </x-admin.table.shell>
        </x-admin.card>
    </x-admin.page>
@endsection
