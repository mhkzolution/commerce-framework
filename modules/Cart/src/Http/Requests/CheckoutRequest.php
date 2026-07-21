<?php

declare(strict_types=1);

namespace Commerce\Cart\Http\Requests;

use Commerce\Cart\DTO\CheckoutData;
use Commerce\Contracts\Shipping\ShippingQuoteServiceInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_uuid' => ['nullable', 'uuid'],
            'shipping_address_uuid' => ['nullable', 'uuid'],
            'billing_address_uuid' => ['nullable', 'uuid'],
            'billing_same_as_shipping' => ['nullable', 'boolean'],
            'shipping_address' => ['nullable', 'array'],
            'shipping_address.line1' => ['required_without:shipping_address_uuid', 'string', 'max:255'],
            'shipping_address.line2' => ['nullable', 'string', 'max:255'],
            'shipping_address.city' => ['required_without:shipping_address_uuid', 'string', 'max:100'],
            'shipping_address.state' => ['nullable', 'string', 'max:100'],
            'shipping_address.postal_code' => ['required_without:shipping_address_uuid', 'string', 'max:20'],
            'shipping_address.country_code' => ['required_without:shipping_address_uuid', 'string', 'size:2'],
            'billing_address' => ['nullable', 'array'],
            'billing_address.line1' => ['required_without_all:billing_address_uuid,billing_same_as_shipping', 'string', 'max:255'],
            'billing_address.line2' => ['nullable', 'string', 'max:255'],
            'billing_address.city' => ['required_without_all:billing_address_uuid,billing_same_as_shipping', 'string', 'max:100'],
            'billing_address.state' => ['nullable', 'string', 'max:100'],
            'billing_address.postal_code' => ['required_without_all:billing_address_uuid,billing_same_as_shipping', 'string', 'max:20'],
            'billing_address.country_code' => ['required_without_all:billing_address_uuid,billing_same_as_shipping', 'string', 'size:2'],
            'shipping_method_uuid' => [
                Rule::requiredIf(fn (): bool => app()->bound(ShippingQuoteServiceInterface::class)),
                'nullable',
                'uuid',
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->filled('shipping_address_uuid') || is_array($this->input('shipping_address'))) {
                return;
            }

            $validator->errors()->add('shipping_address.line1', 'A shipping address is required.');
        });
    }

    public function toCheckoutData(
        ?string $customerUuid = null,
        ?string $customerEmail = null,
        ?string $customerName = null,
    ): CheckoutData {
        $shippingAddress = $this->validated('shipping_address');
        $billingAddress = $this->boolean('billing_same_as_shipping')
            ? $shippingAddress
            : $this->validated('billing_address');

        return new CheckoutData(
            customerEmail: $customerEmail ?? $this->validated('customer_email'),
            customerName: $customerName ?? $this->validated('customer_name'),
            customerUuid: $customerUuid ?? $this->validated('customer_uuid'),
            shippingAddressUuid: $this->validated('shipping_address_uuid'),
            billingAddressUuid: $this->boolean('billing_same_as_shipping')
                ? $this->validated('shipping_address_uuid')
                : $this->validated('billing_address_uuid'),
            billingAddress: $billingAddress,
            shippingAddress: $shippingAddress,
            shippingMethodUuid: $this->validated('shipping_method_uuid'),
        );
    }
}
