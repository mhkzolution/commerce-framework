@component('mail::message')
# Order received

Hi {{ $customer_name ?? 'there' }},

We received your order **{{ $order_number }}**.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
