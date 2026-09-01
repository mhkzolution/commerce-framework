@extends('reports::admin.reports.print.layout')

@section('content')
    <div class="summary">
        <div><strong>{{ $summary['orders_total'] }}</strong> ออเดอร์</div>
        <div><strong>{{ number_format($summary['revenue_total'] / 100, 2) }} {{ $summary['currency'] }}</strong> ยอดขาย</div>
        <div><strong>{{ $summary['cancelled_total'] }}</strong> ยกเลิก</div>
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
@endsection
