@extends('reports::admin.reports.pdf.layout')

@section('content')
    <div class="summary">
        <div>ออเดอร์<strong>{{ $summary['orders_total'] }}</strong></div>
        <div>ยอดขาย<strong>{{ number_format($summary['revenue_total'] / 100, 2) }} {{ $summary['currency'] }}</strong></div>
        <div>ยอดเฉลี่ย<strong>{{ number_format($summary['average_order_value'] / 100, 2) }} {{ $summary['currency'] }}</strong></div>
        <div>ยกเลิก<strong>{{ $summary['cancelled_total'] }}</strong></div>
    </div>

    <table>
        <thead>
            <tr>
                <th>วันที่</th>
                <th>ออเดอร์</th>
                <th>ยอดขาย</th>
                <th>ยกเลิก</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($dailySeries as $row)
                <tr>
                    <td>{{ $row['label'] }}</td>
                    <td>{{ $row['orders'] }}</td>
                    <td>{{ number_format($row['revenue'] / 100, 2) }} {{ $summary['currency'] }}</td>
                    <td>{{ $row['cancelled'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if ($byChannel->isNotEmpty())
        <h2 style="margin-top:1.5rem;font-size:1rem;">แยกตามช่องทาง</h2>
        <table>
            <thead>
                <tr>
                    <th>ช่องทาง</th>
                    <th>ออเดอร์</th>
                    <th>ยอดขาย</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($byChannel as $row)
                    <tr>
                        <td>{{ $channels[$row->channel] ?? $row->channel }}</td>
                        <td>{{ $row->orders }}</td>
                        <td>{{ number_format($row->revenue / 100, 2) }} {{ $summary['currency'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
@endsection
