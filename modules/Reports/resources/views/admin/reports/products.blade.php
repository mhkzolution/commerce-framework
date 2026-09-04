@extends('layouts.admin')

@section('title', 'รายงานสินค้าที่ขายได้')

@section('page')
    <x-admin.page title="รายงานสินค้าที่ขายได้" description="สรุปสินค้า จำนวน และยอดขายตาม SKU">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => 'รายงาน', 'url' => route('admin.reports.index')],
                ['label' => 'สินค้าที่ขายได้', 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-slot:secondaryActions>
            <x-reports::export-actions
                :filter="$filter"
                export-route="admin.reports.products.export"
                pdf-route="admin.reports.products.pdf"
                print-route="admin.reports.products.print"
            />
        </x-slot:secondaryActions>

        <x-slot:filters>
            <x-reports::filters
                :filter="$filter"
                :action="route('admin.reports.products.index')"
                :channels="$channels"
            />
        </x-slot:filters>

        <div class="grid gap-4 sm:grid-cols-3">
            <x-admin.stat-card label="ออเดอร์" :value="(string) $summary['orders_total']" />
            <x-admin.stat-card
                label="ยอดขายรวม"
                :value="number_format($summary['revenue_total'] / 100, 2) . ' ' . $summary['currency']"
            />
            <x-admin.stat-card label="SKU ที่ขายได้" :value="(string) $products->count()" />
        </div>

        <x-admin.table.shell class="mt-6">
            <x-slot:head>
                <tr class="text-left text-xs uppercase tracking-wide text-muted">
                    <th class="px-4 py-3">SKU</th>
                    <th class="px-4 py-3">สินค้า</th>
                    <th class="px-4 py-3">จำนวนขาย</th>
                    <th class="px-4 py-3">ออเดอร์</th>
                    <th class="px-4 py-3">ยอดขาย</th>
                </tr>
            </x-slot:head>

            @forelse ($products as $product)
                <tr>
                    <td class="px-4 py-3 font-mono text-sm">{{ $product->sku }}</td>
                    <td class="px-4 py-3">{{ $product->name }}</td>
                    <td class="px-4 py-3">{{ $product->quantity }}</td>
                    <td class="px-4 py-3">{{ $product->orders }}</td>
                    <td class="px-4 py-3">{{ number_format($product->revenue / 100, 2) }} {{ $summary['currency'] }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-8 text-center text-muted">ไม่มีสินค้าที่ขายได้ในช่วงเวลานี้</td></tr>
            @endforelse
        </x-admin.table.shell>
    </x-admin.page>
@endsection
