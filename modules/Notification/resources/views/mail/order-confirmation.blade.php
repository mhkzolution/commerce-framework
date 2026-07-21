<!DOCTYPE html>
<html>
<body style="font-family: sans-serif; color: #111;">
    <h1>Thank you for your order!</h1>
    <p>Hi {{ $customer_name }},</p>
    <p>Your order <strong>{{ $order_number }}</strong> has been confirmed.</p>
    <p>Total: <strong>{{ $grand_total }} {{ $currency }}</strong></p>
    @if (! empty($order->lineItems))
        <table cellpadding="8" cellspacing="0" border="1" style="border-collapse: collapse; width: 100%; max-width: 480px;">
            <thead><tr><th align="left">Item</th><th align="right">Qty</th><th align="right">Total</th></tr></thead>
            <tbody>
                @foreach ($order->lineItems as $line)
                    <tr>
                        <td>{{ $line->name }}</td>
                        <td align="right">{{ $line->quantity }}</td>
                        <td align="right">{{ number_format($line->line_total / 100, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
    <p style="margin-top: 24px; color: #666;">Commerce Framework</p>
</body>
</html>
