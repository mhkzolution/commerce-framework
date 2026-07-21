@component('mail::message')
# Payment received

Payment for order **{{ $order_number }}** was successful.

Amount: {{ number_format(($amount ?? 0) / 100, 2) }} {{ $currency ?? 'USD' }}

Thanks,<br>
{{ config('app.name') }}
@endcomponent
