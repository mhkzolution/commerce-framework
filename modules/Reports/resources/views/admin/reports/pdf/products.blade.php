@extends('reports::admin.reports.pdf.layout')

@section('content')
    <div class="summary">
        <div>ออเดอร์<strong>{{ $summary['orders_total'] }}</strong></div>
        <div>ยอดขาย<strong>{{ number_format($summary['revenue_total'] / 100, 2) }} {{ $summary['currency'] }}</strong></div>
        <div>SKU<strong>{{ $products->count() }}</strong></div>
    </div>

    <table>
        <thead>
            <tr>
                <th>SKU</th>
                <th>สินค้า</th>
                <th>จำนวนขาย</th>
                <th>ออเดอร์</th>
                <th>ยอดขาย</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($products as $product)
                <tr>
                    <td>{{ $product->sku }}</td>
                    <td>{{ $product->name }}</td>
                    <td>{{ $product->quantity }}</td>
                    <td>{{ $product->orders }}</td>
                    <td>{{ number_format($product->revenue / 100, 2) }} {{ $summary['currency'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
