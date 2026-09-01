@extends('layouts.admin')

@section('title', 'รายงานคำสั่งซื้อ')

@section('page')
    <x-admin.page title="รายงานคำสั่งซื้อ" description="รายละเอียดคำสั่งซื้อทั้งหมดในช่วงเวลาที่เลือก">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => 'รายงาน', 'url' => route('admin.reports.index')],
                ['label' => 'คำสั่งซื้อ', 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-slot:secondaryActions>
            <x-reports::export-actions
                :filter="$filter"
                export-route="admin.reports.orders.export"
                pdf-route="admin.reports.orders.pdf"
                print-route="admin.reports.orders.print"
            />
        </x-slot:secondaryActions>

        <x-slot:filters>
            <x-reports::filters
                :filter="$filter"
                :action="route('admin.reports.orders.index')"
                :channels="$channels"
            />
        </x-slot:filters>

        <div class="mb-4 flex flex-wrap gap-2">
            @foreach ($byStatus as $status => $count)
                <x-admin.badge>{{ $orderStatuses[$status] ?? $status }}: {{ $count }}</x-admin.badge>
            @endforeach
        </div>

        <x-admin.table.shell>
            <x-slot:head>
                <tr class="text-left text-xs uppercase tracking-wide text-muted">
                    <th class="px-4 py-3">เลขออเดอร์</th>
                    <th class="px-4 py-3">วันที่</th>
                    <th class="px-4 py-3">ลูกค้า</th>
                    <th class="px-4 py-3">ช่องทาง</th>
                    <th class="px-4 py-3">รายการ</th>
                    <th class="px-4 py-3">ยอดรวม</th>
                    <th class="px-4 py-3">สถานะ</th>
                </tr>
            </x-slot:head>

            @forelse ($orders as $order)
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
                    <td class="px-4 py-3 text-muted">{{ $order->created_at?->format('d/m/Y H:i') }}</td>
                    <td class="px-4 py-3">{{ $order->customer_name ?: 'ลูกค้าทั่วไป' }}</td>
                    <td class="px-4 py-3">{{ $channels[$order->channel] ?? $order->channel }}</td>
                    <td class="px-4 py-3">{{ $order->line_items_count }}</td>
                    <td class="px-4 py-3">{{ number_format($order->grand_total / 100, 2) }} {{ $order->currency }}</td>
                    <td class="px-4 py-3">
                        <x-admin.badge :variant="$orderBadge">
                            {{ $orderStatuses[$order->status] ?? $order->status }}
                        </x-admin.badge>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-4 py-8 text-center text-muted">ไม่มีคำสั่งซื้อในช่วงเวลานี้</td></tr>
            @endforelse
        </x-admin.table.shell>
    </x-admin.page>
@endsection
