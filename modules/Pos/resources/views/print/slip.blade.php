<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <title>Slip {{ $slip['slip_number'] ?? $slip['order_number'] }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { max-width: 100%; overflow-x: hidden; }
        body {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: {{ $paperWidth === '58mm' ? '11px' : '12px' }};
            line-height: 1.4;
            color: #000;
            background: #fff;
            width: {{ $paperWidth }};
            margin: 0 auto;
            padding: {{ $paperWidth === '58mm' ? '3mm 2mm' : '8mm 6mm' }};
        }
        h1 { font-size: {{ $paperWidth === '58mm' ? '14px' : '16px' }}; text-align: center; margin-bottom: 4px; }
        .store { text-align: center; font-weight: 700; margin-bottom: 2px; overflow-wrap: anywhere; word-break: break-word; }
        .meta { text-align: center; font-size: 11px; margin-bottom: 12px; overflow-wrap: anywhere; }
        table { width: 100%; table-layout: fixed; border-collapse: collapse; margin: 8px 0; }
        col.item { width: auto; }
        col.qty { width: {{ $paperWidth === '58mm' ? '14%' : '12%' }}; }
        col.total { width: {{ $paperWidth === '58mm' ? '28%' : '26%' }}; }
        th, td { padding: 2px 0; vertical-align: top; }
        th { text-align: left; border-bottom: 1px dashed #000; padding-bottom: 4px; }
        .item { overflow-wrap: anywhere; word-break: break-word; }
        .right { text-align: right; white-space: nowrap; }
        .totals td { padding-top: 4px; }
        .totals td:first-child { overflow-wrap: anywhere; }
        .grand { font-size: {{ $paperWidth === '58mm' ? '14px' : '16px' }}; font-weight: bold; border-top: 2px solid #000; padding-top: 6px; }
        .payments { margin-top: 8px; overflow-wrap: anywhere; }
        .footer { text-align: center; margin-top: 16px; font-size: 11px; }
        @page { size: {{ $paperWidth }} auto; margin: 0; }
        @media print {
            body { width: {{ $paperWidth }}; margin: 0; padding: 4mm 2mm; }
            .no-print { display: none; }
        }
    </style>
</head>
<body data-paper-width="{{ $paperWidth }}" data-print-renderer="browser-print" data-print-type="slip" onload="window.print()">
    <div class="no-print" style="margin-bottom:12px;text-align:center;">
        <button type="button" onclick="window.print()">Print</button>
        <button type="button" onclick="window.close()">Close</button>
    </div>

    @if (! empty($slip['store_name']))
        <div class="store">{{ $slip['store_name'] }}</div>
    @endif
    <h1>SLIP</h1>
    <div class="meta">
        <div>{{ $slip['slip_number'] ?? $slip['order_number'] }}</div>
        @if (! empty($slip['order_number']))
            <div>Order {{ $slip['order_number'] }}</div>
        @endif
        <div>{{ $slip['created_at'] }}</div>
        @if (! empty($slip['register']))<div>{{ $slip['register'] }}</div>@endif
        @if (! empty($slip['cashier']))<div>Cashier: {{ $slip['cashier'] }}</div>@endif
        @if (! empty($slip['customer_name']))<div>{{ $slip['customer_name'] }}</div>@endif
    </div>

    <table>
        <colgroup>
            <col class="item">
            <col class="qty">
            <col class="total">
        </colgroup>
        <thead>
            <tr>
                <th>Item</th>
                <th class="right">Qty</th>
                <th class="right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($slip['lines'] ?? [] as $line)
                <tr>
                    <td class="item">
                        {{ $line['name'] }}
                        @if (! empty($line['sku']))<br><small>{{ $line['sku'] }}</small>@endif
                    </td>
                    <td class="right">{{ $line['quantity'] }}</td>
                    <td class="right">{{ $line['line_total'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Subtotal</td><td class="right">{{ $slip['subtotal'] }} {{ $slip['currency'] }}</td></tr>
        @if ((int) ($slip['discount_minor'] ?? 0) > 0)
            <tr><td>Discount</td><td class="right">-{{ $slip['discount'] }}</td></tr>
        @endif
        <tr><td>Tax</td><td class="right">{{ $slip['tax'] }}</td></tr>
        <tr class="grand"><td>TOTAL</td><td class="right">{{ $slip['grand_total'] }} {{ $slip['currency'] }}</td></tr>
    </table>

    @if (count($slip['payments'] ?? []) > 0)
        <div class="payments">
            <strong>Payment</strong>
            @foreach ($slip['payments'] as $payment)
                <div>{{ $payment['method'] }}: {{ $payment['amount'] }}</div>
            @endforeach
        </div>
    @endif

    @if (! empty($slip['cash_received']))
        <div style="margin-top:8px;">Received: {{ $slip['cash_received'] }}</div>
    @endif
    @if (! empty($slip['change_amount']))
        <div><strong>Change: {{ $slip['change_amount'] }}</strong></div>
    @endif

    @if (! empty($slip['coupon_code']))
        <div style="margin-top:8px;">Coupon: {{ $slip['coupon_code'] }}</div>
    @endif

    @if (! empty($slip['notes']))
        <div style="margin-top:8px;">Note: {{ $slip['notes'] }}</div>
    @endif

    <div class="footer">Thank you</div>
</body>
</html>
