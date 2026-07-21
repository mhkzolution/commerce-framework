@component('mail::message')
# Payment failed

We could not process payment for order **{{ $order_number }}**.

Please try again or contact support.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
