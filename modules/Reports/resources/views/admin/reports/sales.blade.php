@extends('layouts.admin')

@section('title', 'รายงานยอดขายรายวัน')

@section('page')
    <x-admin.page title="รายงานยอดขายรายวัน" description="สรุปยอดขายและจำนวนออเดอร์แยกตามวัน">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => 'รายงาน', 'url' => route('admin.reports.index')],
                ['label' => 'ยอดขายรายวัน', 'active' => true],
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

        <x-slot:filters>
            <x-reports::filters
                :filter="$filter"
                :action="route('admin.reports.sales.index')"
                :channels="$channels"
            />
        </x-slot:filters>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-admin.stat-card
                label="ออเดอร์ทั้งหมด"
                :value="(string) $summary['orders_total']"
                :hint="$filter->channelLabel()"
            />
            <x-admin.stat-card
                label="ยอดขาย"
                :value="number_format($summary['revenue_total'] / 100, 2) . ' ' . $summary['currency']"
                hint="ออเดอร์ที่ชำระแล้ว"
            />
            <x-admin.stat-card
                label="ยอดเฉลี่ยต่อออเดอร์"
                :value="number_format($summary['average_order_value'] / 100, 2) . ' ' . $summary['currency']"
            />
            <x-admin.stat-card
                label="ยกเลิก"
                :value="(string) $summary['cancelled_total']"
            />
        </div>

        <div class="mt-6">
            <x-admin.bar-chart :series="$dailySeries" currency="{{ $summary['currency'] }}" title="ยอดขายรายวัน" />
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            <x-admin.card title="ยอดขายรายวัน">
                <x-admin.table.shell>
                    <x-slot:head>
                        <tr class="text-left text-xs uppercase tracking-wide text-muted">
                            <th class="px-4 py-3">วันที่</th>
                            <th class="px-4 py-3">ออเดอร์</th>
                            <th class="px-4 py-3">ยอดขาย</th>
                            <th class="px-4 py-3">ยกเลิก</th>
                        </tr>
                    </x-slot:head>
                    @forelse ($dailySeries as $row)
                        <tr>
                            <td class="px-4 py-3">{{ $row['label'] }}</td>
                            <td class="px-4 py-3">{{ $row['orders'] }}</td>
                            <td class="px-4 py-3">{{ number_format($row['revenue'] / 100, 2) }} {{ $summary['currency'] }}</td>
                            <td class="px-4 py-3">{{ $row['cancelled'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-8 text-center text-muted">ไม่มีข้อมูลในช่วงเวลานี้</td></tr>
                    @endforelse
                </x-admin.table.shell>
            </x-admin.card>

            <x-admin.card title="แยกตามช่องทาง">
                <x-admin.table.shell>
                    <x-slot:head>
                        <tr class="text-left text-xs uppercase tracking-wide text-muted">
                            <th class="px-4 py-3">ช่องทาง</th>
                            <th class="px-4 py-3">ออเดอร์</th>
                            <th class="px-4 py-3">ยอดขาย</th>
                        </tr>
                    </x-slot:head>
                    @forelse ($byChannel as $row)
                        <tr>
                            <td class="px-4 py-3">{{ $channels[$row->channel] ?? $row->channel }}</td>
                            <td class="px-4 py-3">{{ $row->orders }}</td>
                            <td class="px-4 py-3">{{ number_format($row->revenue / 100, 2) }} {{ $summary['currency'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-4 py-8 text-center text-muted">ไม่มีข้อมูลในช่วงเวลานี้</td></tr>
                    @endforelse
                </x-admin.table.shell>
            </x-admin.card>
        </div>
    </x-admin.page>
@endsection
