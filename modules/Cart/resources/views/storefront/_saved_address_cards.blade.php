@php
    $inputName = $role === 'billing' ? 'billing_address_uuid' : 'shipping_address_uuid';
    $addLabel = __('storefront::storefront.add_new_address');
@endphp

<div class="storefront-address-cards" data-address-cards="{{ $role }}">
    <div class="storefront-address-cards__list" role="radiogroup" aria-label="{{ __('storefront::storefront.saved_addresses') }}">
        @foreach ($addresses as $address)
            @php
                $isSelected = $selectedUuid === $address->uuid;
                $recipient = $address->toCheckoutCardArray($customer);
            @endphp
            <article
                class="storefront-address-card"
                role="radio"
                tabindex="0"
                aria-checked="{{ $isSelected ? 'true' : 'false' }}"
                data-address-uuid="{{ $address->uuid }}"
                data-address-role="{{ $role }}"
                data-selected="{{ $isSelected ? '1' : '0' }}"
                data-address='@json($recipient)'
            >
                <span class="storefront-choice-mark" aria-hidden="true"></span>
                <div class="storefront-address-card__body">
                    <p class="storefront-address-card__label">
                        {{ $address->label ?: __('storefront::storefront.address') }}
                        @if ($role === 'shipping' && $address->is_default_shipping)
                            <span class="storefront-badge">{{ __('storefront::storefront.default_shipping') }}</span>
                        @elseif ($role === 'billing' && $address->is_default_billing)
                            <span class="storefront-badge">{{ __('storefront::storefront.default_billing') }}</span>
                        @endif
                    </p>
                    @if ($recipient['recipient_name'] ?? null)
                        <p class="storefront-address-card__recipient">{{ $recipient['recipient_name'] }}</p>
                    @endif
                    @if ($recipient['phone'] ?? null)
                        <p>{{ $recipient['phone'] }}</p>
                    @endif
                    <p class="storefront-muted">
                        {{ $address->line1 }}@if ($address->line2), {{ $address->line2 }}@endif<br>
                        @if ($address->subdistrict || $address->district)
                            {{ $address->subdistrict }} {{ $address->district }}<br>
                        @endif
                        {{ $address->state ?: $address->city }} {{ $address->postal_code }}
                    </p>
                </div>
                <button
                    type="button"
                    class="storefront-address-card__edit"
                    data-edit-address
                    data-target="{{ $role }}"
                >
                    {{ __('storefront::storefront.edit_address') }}
                </button>
            </article>
        @endforeach

        <button
            type="button"
            class="storefront-address-add"
            data-add-address="{{ $role }}"
            aria-expanded="false"
        >
            <span class="storefront-address-add__icon" aria-hidden="true">+</span>
            <span>{{ $addLabel }}</span>
        </button>
    </div>
    <input type="hidden" name="{{ $inputName }}" id="{{ $inputName }}" value="{{ $selectedUuid }}" data-address-uuid-input="{{ $role }}">
    <input type="hidden" name="update_{{ $role }}_address_uuid" value="" data-update-address-uuid="{{ $role }}">
</div>
