@extends('reports::admin.reports.print.layout')

@section('content')
    <div class="summary">
        <div><strong>{{ $products->count() }}</strong> SKU</div>
        <div><strong>{{ number_format($summary['revenue_total'] / 100, 2) }} {{ $summary['currency'] }}</strong> ยอดขาย</div>
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
