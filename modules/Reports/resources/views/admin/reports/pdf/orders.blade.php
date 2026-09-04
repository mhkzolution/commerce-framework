@extends('reports::admin.reports.pdf.layout')

@section('content')
    <table>
        <thead>
            <tr>
                <th>เลขออเดอร์</th>
                <th>วันที่</th>
                <th>ลูกค้า</th>
                <th>ช่องทาง</th>
                <th>รายการ</th>
                <th>ยอดรวม</th>
                <th>สถานะ</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($orders as $order)
                <tr>
                    <td>{{ $order->order_number }}</td>
                    <td>{{ $order->created_at?->format('d/m/Y H:i') }}</td>
                    <td>{{ $order->customer_name ?: 'ลูกค้าทั่วไป' }}</td>
                    <td>{{ $channels[$order->channel] ?? $order->channel }}</td>
                    <td>{{ $order->line_items_count }}</td>
                    <td>{{ number_format($order->grand_total / 100, 2) }} {{ $order->currency }}</td>
                    <td>{{ $orderStatuses[$order->status] ?? $order->status }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
