<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Receipt {{ $receipt['order_number'] }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: ui-monospace, monospace; font-size: 12px; line-height: 1.4; color: #000; background: #fff; width: 80mm; margin: 0 auto; padding: 8mm 6mm; }
        h1 { font-size: 16px; text-align: center; margin-bottom: 4px; }
        .meta { text-align: center; font-size: 11px; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; margin: 8px 0; }
        th, td { padding: 2px 0; vertical-align: top; }
        th { text-align: left; border-bottom: 1px dashed #000; padding-bottom: 4px; }
        .right { text-align: right; }
        .totals td { padding-top: 4px; }
        .grand { font-size: 16px; font-weight: bold; border-top: 2px solid #000; padding-top: 6px; }
        .payments { margin-top: 8px; }
        .footer { text-align: center; margin-top: 16px; font-size: 11px; }
        @media print {
            body { width: 80mm; margin: 0; padding: 4mm; }
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="no-print" style="margin-bottom:12px;text-align:center;">
        <button onclick="window.print()">Print</button>
        <button onclick="window.close()">Close</button>
    </div>

    <h1>RECEIPT</h1>
    <div class="meta">
        <div>#{{ $receipt['order_number'] }}</div>
        <div>{{ $receipt['created_at'] }}</div>
        @if ($receipt['register'])<div>{{ $receipt['register'] }}</div>@endif
        @if ($receipt['cashier'])<div>Cashier: {{ $receipt['cashier'] }}</div>@endif
        <div>{{ $receipt['customer_name'] }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th class="right">Qty</th>
                <th class="right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($receipt['lines'] as $line)
                <tr>
                    <td>
                        {{ $line['name'] }}
                        @if ($line['sku'])<br><small>{{ $line['sku'] }}</small>@endif
                    </td>
                    <td class="right">{{ $line['quantity'] }}</td>
                    <td class="right">{{ $line['line_total'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Subtotal</td><td class="right">{{ $receipt['subtotal'] }} {{ $receipt['currency'] }}</td></tr>
        @if ((float) str_replace(',', '', $receipt['discount']) > 0)
            <tr><td>Discount</td><td class="right">-{{ $receipt['discount'] }}</td></tr>
        @endif
        <tr><td>Tax</td><td class="right">{{ $receipt['tax'] }}</td></tr>
        <tr class="grand"><td>TOTAL</td><td class="right">{{ $receipt['grand_total'] }} {{ $receipt['currency'] }}</td></tr>
    </table>

    @if (count($receipt['payments']) > 0)
        <div class="payments">
            <strong>Payment</strong>
            @foreach ($receipt['payments'] as $payment)
                <div>{{ $payment['method'] }}: {{ $payment['amount'] }}</div>
            @endforeach
        </div>
    @endif

    @if ($receipt['cash_received'])
        <div style="margin-top:8px;">Received: {{ $receipt['cash_received'] }}</div>
    @endif
    @if ($receipt['change_amount'])
        <div><strong>Change: {{ $receipt['change_amount'] }}</strong></div>
    @endif

    @if ($receipt['coupon_code'])
        <div style="margin-top:8px;">Coupon: {{ $receipt['coupon_code'] }}</div>
    @endif

    @if ($receipt['notes'])
        <div style="margin-top:8px;">Note: {{ $receipt['notes'] }}</div>
    @endif

    <div class="footer">Thank you</div>
</body>
</html>
