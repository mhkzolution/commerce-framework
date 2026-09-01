@component('mail::message')
# Purchase Order {{ $order->reference }}

Hello {{ $supplierName }},

Please find the attached purchase order **{{ $order->reference }}**.

@if ($order->expected_at)
**Expected delivery:** {{ $order->expected_at->format('M j, Y') }}
@endif

@if ($order->notes)
**Notes:** {{ $order->notes }}
@endif

Thanks,<br>
{{ config('app.name') }}
@endcomponent
