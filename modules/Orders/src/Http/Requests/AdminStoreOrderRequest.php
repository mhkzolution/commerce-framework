<?php

declare(strict_types=1);

namespace Commerce\Orders\Http\Requests;

use Commerce\Contracts\Inventory\InventoryQueryServiceInterface;
use Commerce\Contracts\Product\ProductQueryServiceInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class AdminStoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'intent' => ['nullable', Rule::in(['create', 'draft'])],
            'admin_status' => ['nullable', Rule::in(array_keys(config('orders.admin_statuses', ['pending' => 'Pending'])))],
            'channel' => ['nullable', 'string', Rule::in(array_keys(config('orders.channels', ['web' => 'Website'])))],
            'customer_uuid' => ['nullable', 'uuid'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'discount_type' => ['nullable', Rule::in(['fixed', 'percent'])],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'shipping_fee' => ['nullable', 'numeric', 'min:0'],
            'tax_total' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'idempotency_key' => ['nullable', 'uuid'],
            'billing_same_as_shipping' => ['nullable', 'boolean'],
            'shipping_address' => ['nullable', 'array'],
            'shipping_address.recipient_name' => ['nullable', 'string', 'max:255'],
            'shipping_address.phone' => ['nullable', 'string', 'max:50'],
            'shipping_address.line1' => ['nullable', 'string', 'max:255'],
            'shipping_address.line2' => ['nullable', 'string', 'max:255'],
            'shipping_address.district' => ['nullable', 'string', 'max:120'],
            'shipping_address.subdistrict' => ['nullable', 'string', 'max:120'],
            'shipping_address.province' => ['nullable', 'string', 'max:120'],
            'shipping_address.postal_code' => ['nullable', 'string', 'max:20'],
            'billing_address' => ['nullable', 'array'],
            'billing_address.recipient_name' => ['nullable', 'string', 'max:255'],
            'billing_address.phone' => ['nullable', 'string', 'max:50'],
            'billing_address.line1' => ['nullable', 'string', 'max:255'],
            'billing_address.line2' => ['nullable', 'string', 'max:255'],
            'billing_address.district' => ['nullable', 'string', 'max:120'],
            'billing_address.subdistrict' => ['nullable', 'string', 'max:120'],
            'billing_address.province' => ['nullable', 'string', 'max:120'],
            'billing_address.postal_code' => ['nullable', 'string', 'max:20'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.purchasable_uuid' => ['required', 'uuid'],
            'lines.*.quantity' => ['required', 'integer', 'min:1'],
            'lines.*.unit_price' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            foreach ($this->input('lines', []) as $index => $line) {
                $uuid = $line['purchasable_uuid'] ?? null;
                $quantity = (int) ($line['quantity'] ?? 0);

                if (! is_string($uuid) || $quantity < 1) {
                    continue;
                }

                if (app()->bound(ProductQueryServiceInterface::class)) {
                    $variant = app(ProductQueryServiceInterface::class)->findVariantByUuid($uuid);

                    if ($variant === null) {
                        $validator->errors()->add(
                            'lines',
                            __('orders::admin.variant_unavailable'),
                        );
                        $validator->errors()->add("lines.{$index}.purchasable_uuid", __('orders::admin.variant_unavailable'));
                        break;
                    }
                }

                if (! app()->bound(InventoryQueryServiceInterface::class)) {
                    continue;
                }

                try {
                    $available = app(InventoryQueryServiceInterface::class)->getAvailable($uuid);
                } catch (\Throwable) {
                    continue;
                }

                if ($quantity > $available) {
                    $validator->errors()->add(
                        'lines',
                        __('orders::admin.stock_exceeded', ['available' => $available]),
                    );
                    $validator->errors()->add("lines.{$index}.quantity", __('orders::admin.stock_exceeded', ['available' => $available]));
                    break;
                }
            }
        });
    }

    public function intent(): string
    {
        return $this->validated('intent') === 'draft' ? 'draft' : 'create';
    }

    public function shippingFeeMinor(): int
    {
        return $this->toMinor($this->validated('shipping_fee') ?? 0);
    }

    public function taxMinor(): int
    {
        return $this->toMinor($this->validated('tax_total') ?? 0);
    }

    public function discountMinor(int $subtotal): int
    {
        $type = $this->validated('discount_type') ?: 'fixed';
        $value = (float) ($this->validated('discount_value') ?? 0);

        if ($type === 'percent') {
            $percent = min(100, max(0, $value));

            return (int) floor($subtotal * $percent / 100);
        }

        return min($subtotal, $this->toMinor($value));
    }

    /**
     * @return array<string, mixed>|null
     */
    public function shippingAddress(): ?array
    {
        return $this->cleanAddress(
            $this->validated('shipping_address') ?? [],
            $this->validated('customer_phone'),
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function billingAddress(): ?array
    {
        $sameAsShipping = $this->has('billing_same_as_shipping')
            ? $this->boolean('billing_same_as_shipping')
            : true;

        if ($sameAsShipping) {
            return $this->shippingAddress();
        }

        return $this->cleanAddress(
            $this->validated('billing_address') ?? [],
            $this->validated('customer_phone'),
        );
    }

    public function lineUnitPriceMinor(array $line): ?int
    {
        if (! array_key_exists('unit_price', $line) || $line['unit_price'] === null || $line['unit_price'] === '') {
            return null;
        }

        return $this->toMinor($line['unit_price']);
    }

    /**
     * @param  array<string, mixed>  $address
     * @return array<string, mixed>|null
     */
    private function cleanAddress(array $address, mixed $fallbackPhone): ?array
    {
        $clean = array_filter(
            [
                'recipient_name' => $address['recipient_name'] ?? null,
                'phone' => $address['phone'] ?? $fallbackPhone,
                'line1' => $address['line1'] ?? null,
                'line2' => $address['line2'] ?? null,
                'district' => $address['district'] ?? null,
                'subdistrict' => $address['subdistrict'] ?? null,
                'province' => $address['province'] ?? null,
                'city' => $address['district'] ?? null,
                'state' => $address['province'] ?? null,
                'postal_code' => $address['postal_code'] ?? null,
                'country_code' => config('orders.default_country', 'TH'),
            ],
            static fn (mixed $value): bool => $value !== null && $value !== '',
        );

        return $clean === [] ? null : $clean;
    }

    private function toMinor(mixed $value): int
    {
        if (! is_numeric($value)) {
            return 0;
        }

        return (int) round(((float) $value) * 100);
    }
}
