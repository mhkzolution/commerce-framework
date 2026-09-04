@extends('layouts.admin')

@section('title', __('orders::admin.title'))

@section('page')
    <x-admin.page
        :title="__('orders::admin.title')"
        :description="__('orders::admin.description')"
        :wide="true"
    >
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => __('admin::nav.groups.sales')],
                ['label' => __('admin::nav.labels.orders'), 'url' => route('admin.orders.index')],
                ['label' => __('orders::admin.title'), 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <form
            method="POST"
            action="{{ route('admin.orders.store') }}"
            class="order-create"
            data-order-create
            data-customers-url="{{ route('admin.orders.lookup.customers') }}"
            data-products-url="{{ route('admin.orders.lookup.products') }}"
            data-i18n="{{ json_encode([
                'searching' => __('orders::admin.searching'),
                'noResults' => __('orders::admin.no_results'),
                'customerEmpty' => __('orders::admin.customer_empty'),
                'productEmpty' => __('orders::admin.product_empty'),
                'inStock' => __('orders::admin.in_stock'),
                'lowStock' => __('orders::admin.low_stock'),
                'outOfStock' => __('orders::admin.out_of_stock'),
                'warnNoProducts' => __('orders::admin.warn_no_products'),
                'warnNoPhone' => __('orders::admin.warn_no_phone'),
                'warnOutOfStock' => __('orders::admin.warn_out_of_stock'),
                'remove' => __('orders::admin.remove'),
                'sku' => __('orders::admin.sku'),
                'unitPrice' => __('orders::admin.unit_price'),
                'creating' => __('orders::admin.creating'),
                'saving' => __('orders::admin.saving'),
            ], JSON_THROW_ON_ERROR) }}"
            data-initial-lines="{{ json_encode($initialLines, JSON_THROW_ON_ERROR) }}"
        >
            @csrf
            <input type="hidden" name="intent" value="create" data-intent>
            <input type="hidden" name="customer_uuid" value="{{ old('customer_uuid') }}" data-customer-uuid>
            <input type="hidden" name="currency" value="{{ $currency }}">
            <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', (string) \Illuminate\Support\Str::uuid()) }}">
            <template hidden>
                <input type="number" data-unit-price>
            </template>

            <div class="order-create-layout">
                <div class="space-y-6">
                    <x-admin.card :title="__('orders::admin.customer')">
                        <div class="space-y-4">
                            <div class="relative">
                                <label class="block text-sm font-medium text-text" for="customer-search">{{ __('orders::admin.customer_search') }}</label>
                                <input id="customer-search" type="search" class="cf-input mt-1" autocomplete="off" data-customer-search placeholder="{{ __('orders::admin.customer_search') }}" role="combobox" aria-autocomplete="list" aria-controls="customer-results" aria-expanded="false">
                                <div id="customer-results" class="order-create-results" data-customer-results hidden role="listbox"></div>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <button type="button" class="cf-btn cf-btn-secondary" data-guest>{{ __('orders::admin.continue_guest') }}</button>
                            </div>

                            <p class="text-sm text-muted" data-customer-mode data-guest-label="{{ __('orders::admin.guest') }}">{{ __('orders::admin.guest') }}</p>

                            <div class="grid gap-4 sm:grid-cols-3">
                                <div>
                                    <label class="block text-sm font-medium text-text" for="customer_name">{{ __('orders::admin.customer_name') }}</label>
                                    <input id="customer_name" name="customer_name" value="{{ old('customer_name') }}" class="cf-input mt-1" data-customer-name>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-text" for="customer_email">{{ __('orders::admin.customer_email') }}</label>
                                    <input id="customer_email" type="email" name="customer_email" value="{{ old('customer_email') }}" class="cf-input mt-1" data-customer-email>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-text" for="customer_phone">{{ __('orders::admin.customer_phone') }}</label>
                                    <input id="customer_phone" name="customer_phone" value="{{ old('customer_phone') }}" required class="cf-input mt-1" data-customer-phone>
                                </div>
                            </div>
                        </div>
                    </x-admin.card>

                    <x-admin.card :title="__('orders::admin.shipping')">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-text" for="ship-name">{{ __('orders::admin.recipient_name') }}</label>
                                <input id="ship-name" name="shipping_address[recipient_name]" value="{{ old('shipping_address.recipient_name') }}" class="cf-input mt-1" data-ship-name>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-text" for="ship-phone">{{ __('orders::admin.customer_phone') }}</label>
                                <input id="ship-phone" name="shipping_address[phone]" value="{{ old('shipping_address.phone') }}" class="cf-input mt-1" data-ship-phone>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-text" for="ship-line1">{{ __('orders::admin.address_line1') }}</label>
                                <input id="ship-line1" name="shipping_address[line1]" value="{{ old('shipping_address.line1') }}" class="cf-input mt-1" data-ship-line1>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-text" for="ship-line2">{{ __('orders::admin.address_line2') }}</label>
                                <input id="ship-line2" name="shipping_address[line2]" value="{{ old('shipping_address.line2') }}" class="cf-input mt-1" data-ship-line2>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-text" for="ship-district">{{ __('orders::admin.district') }}</label>
                                <input id="ship-district" name="shipping_address[district]" value="{{ old('shipping_address.district') }}" class="cf-input mt-1" data-ship-district>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-text" for="ship-subdistrict">{{ __('orders::admin.subdistrict') }}</label>
                                <input id="ship-subdistrict" name="shipping_address[subdistrict]" value="{{ old('shipping_address.subdistrict') }}" class="cf-input mt-1" data-ship-subdistrict>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-text" for="ship-province">{{ __('orders::admin.province') }}</label>
                                <input id="ship-province" name="shipping_address[province]" value="{{ old('shipping_address.province') }}" class="cf-input mt-1" data-ship-province>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-text" for="ship-postal">{{ __('orders::admin.postal_code') }}</label>
                                <input id="ship-postal" name="shipping_address[postal_code]" value="{{ old('shipping_address.postal_code') }}" class="cf-input mt-1" data-ship-postal>
                            </div>
                        </div>
                    </x-admin.card>

                    <x-admin.card :title="__('orders::admin.billing')">
                        <div class="space-y-4">
                            <label class="flex items-center gap-2 text-sm text-text">
                                <input
                                    type="hidden"
                                    name="billing_same_as_shipping"
                                    value="0"
                                >
                                <input
                                    type="checkbox"
                                    name="billing_same_as_shipping"
                                    value="1"
                                    @checked((string) old('billing_same_as_shipping', '1') === '1')
                                    data-billing-same
                                >
                                <span>{{ __('orders::admin.billing_same_as_shipping') }}</span>
                            </label>

                            <div class="grid gap-4 sm:grid-cols-2" data-billing-fields @if ((string) old('billing_same_as_shipping', '1') === '1') hidden @endif>
                                <div>
                                    <label class="block text-sm font-medium text-text" for="bill-name">{{ __('orders::admin.recipient_name') }}</label>
                                    <input id="bill-name" name="billing_address[recipient_name]" value="{{ old('billing_address.recipient_name') }}" class="cf-input mt-1" data-bill-name>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-text" for="bill-phone">{{ __('orders::admin.customer_phone') }}</label>
                                    <input id="bill-phone" name="billing_address[phone]" value="{{ old('billing_address.phone') }}" class="cf-input mt-1" data-bill-phone>
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-medium text-text" for="bill-line1">{{ __('orders::admin.address_line1') }}</label>
                                    <input id="bill-line1" name="billing_address[line1]" value="{{ old('billing_address.line1') }}" class="cf-input mt-1" data-bill-line1>
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-medium text-text" for="bill-line2">{{ __('orders::admin.address_line2') }}</label>
                                    <input id="bill-line2" name="billing_address[line2]" value="{{ old('billing_address.line2') }}" class="cf-input mt-1" data-bill-line2>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-text" for="bill-district">{{ __('orders::admin.district') }}</label>
                                    <input id="bill-district" name="billing_address[district]" value="{{ old('billing_address.district') }}" class="cf-input mt-1" data-bill-district>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-text" for="bill-subdistrict">{{ __('orders::admin.subdistrict') }}</label>
                                    <input id="bill-subdistrict" name="billing_address[subdistrict]" value="{{ old('billing_address.subdistrict') }}" class="cf-input mt-1" data-bill-subdistrict>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-text" for="bill-province">{{ __('orders::admin.province') }}</label>
                                    <input id="bill-province" name="billing_address[province]" value="{{ old('billing_address.province') }}" class="cf-input mt-1" data-bill-province>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-text" for="bill-postal">{{ __('orders::admin.postal_code') }}</label>
                                    <input id="bill-postal" name="billing_address[postal_code]" value="{{ old('billing_address.postal_code') }}" class="cf-input mt-1" data-bill-postal>
                                </div>
                            </div>
                        </div>
                    </x-admin.card>

                    <x-admin.card :title="__('orders::admin.products')">
                        <div class="space-y-4">
                            <div class="relative">
                                <label class="block text-sm font-medium text-text" for="product-search">{{ __('orders::admin.product_search') }}</label>
                                <div class="mt-1 flex gap-2">
                                    <input id="product-search" type="search" class="cf-input flex-1" autocomplete="off" data-product-search placeholder="{{ __('orders::admin.product_search') }}" role="combobox" aria-autocomplete="list" aria-controls="product-results" aria-expanded="false">
                                    <button type="button" class="cf-btn cf-btn-secondary whitespace-nowrap" data-add-product>{{ __('orders::admin.add_product') }}</button>
                                </div>
                                <div id="product-results" class="order-create-results" data-product-results hidden role="listbox"></div>
                            </div>

                            <div class="space-y-3" data-lines>
                                <p class="text-sm text-muted" data-lines-empty>{{ __('orders::admin.product_empty') }}</p>
                            </div>
                        </div>
                    </x-admin.card>

                    <x-admin.card :title="__('orders::admin.settings')">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-text" for="admin_status">{{ __('orders::admin.status') }}</label>
                                <select id="admin_status" name="admin_status" class="cf-input mt-1">
                                    @foreach ($adminStatuses as $code => $label)
                                        <option value="{{ $code }}" @selected(old('admin_status', 'pending') === $code)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-text" for="channel">{{ __('orders::admin.channel') }}</label>
                                <select id="channel" name="channel" class="cf-input mt-1">
                                    @foreach ($channels as $code => $label)
                                        <option value="{{ $code }}" @selected(old('channel', $defaultChannel) === $code)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-text" for="notes">{{ __('orders::admin.notes') }}</label>
                                <textarea id="notes" name="notes" rows="3" class="cf-input mt-1" placeholder="{{ __('orders::admin.notes_hint') }}">{{ old('notes') }}</textarea>
                                <p class="mt-1 text-sm text-muted">{{ __('orders::admin.notes_hint') }}</p>
                            </div>
                        </div>
                    </x-admin.card>

                    <x-admin.card :title="__('orders::admin.pricing')">
                        <div class="grid gap-4 sm:grid-cols-3">
                            <div>
                                <label class="block text-sm font-medium text-text" for="discount_type">{{ __('orders::admin.discount') }}</label>
                                <select id="discount_type" name="discount_type" class="cf-input mt-1" data-discount-type>
                                    <option value="fixed" @selected(old('discount_type', 'fixed') === 'fixed')>{{ __('orders::admin.discount_fixed') }}</option>
                                    <option value="percent" @selected(old('discount_type') === 'percent')>{{ __('orders::admin.discount_percent') }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-text" for="discount_value">{{ __('orders::admin.discount') }}</label>
                                <input id="discount_value" name="discount_value" type="number" min="0" step="0.01" value="{{ old('discount_value', '0.00') }}" class="cf-input mt-1" data-discount-value>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-text" for="shipping_fee">{{ __('orders::admin.shipping_fee') }}</label>
                                <input id="shipping_fee" name="shipping_fee" type="number" min="0" step="0.01" value="{{ old('shipping_fee', '0.00') }}" class="cf-input mt-1" data-shipping-fee>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-text" for="tax_total">{{ __('orders::admin.tax') }}</label>
                                <input id="tax_total" name="tax_total" type="number" min="0" step="0.01" value="{{ old('tax_total', '0.00') }}" class="cf-input mt-1" data-tax-total>
                            </div>
                        </div>
                    </x-admin.card>
                </div>

                <aside class="order-create-summary" data-order-summary>
                    <x-admin.card :title="__('orders::admin.summary')">
                        <div class="space-y-4">
                            <div class="space-y-2 text-sm" data-item-summary></div>
                            <dl class="space-y-2 text-sm">
                                <div class="flex justify-between gap-4">
                                    <dt class="text-muted">{{ __('orders::admin.subtotal') }}</dt>
                                    <dd data-summary-subtotal>0.00</dd>
                                </div>
                                <div class="flex justify-between gap-4">
                                    <dt class="text-muted">{{ __('orders::admin.discount') }}</dt>
                                    <dd data-summary-discount>0.00</dd>
                                </div>
                                <div class="flex justify-between gap-4">
                                    <dt class="text-muted">{{ __('orders::admin.shipping_fee') }}</dt>
                                    <dd data-summary-shipping>0.00</dd>
                                </div>
                                <div class="flex justify-between gap-4">
                                    <dt class="text-muted">{{ __('orders::admin.tax') }}</dt>
                                    <dd data-summary-tax>0.00</dd>
                                </div>
                                <div class="flex justify-between gap-4 border-t border-border pt-2 text-base font-semibold">
                                    <dt>{{ __('orders::admin.grand_total') }}</dt>
                                    <dd data-summary-total>0.00</dd>
                                </div>
                            </dl>
                            <div class="rounded-lg border border-warning/40 bg-warning-subtle p-3 text-sm" data-warnings hidden aria-live="polite">
                                <p class="mb-1 font-medium">{{ __('orders::admin.warnings') }}</p>
                                <ul class="list-disc space-y-1 pl-4" data-warning-list></ul>
                            </div>
                            <div class="flex flex-col gap-2">
                                <button type="submit" class="cf-btn cf-btn-primary w-full" data-submit-create>{{ __('orders::admin.create_order') }}</button>
                                <button type="submit" class="cf-btn cf-btn-secondary w-full" data-submit-draft>{{ __('orders::admin.save_draft') }}</button>
                            </div>
                        </div>
                    </x-admin.card>
                </aside>
            </div>
        </form>
    </x-admin.page>
@endsection
