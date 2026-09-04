@extends('pos::layouts.pos')

@section('title', 'ขายหน้าร้าน')

@section('content')
    <div
        class="pos-layout"
        id="pos-app"
        data-pos-app
        data-pos-config='@json(['routes' => $apiRoutes, 'initial' => $posState])'
    >
        <x-pos::nav-rail active="pos" />

        <div class="pos-main">
            <x-pos::top-bar
                :cashier="$posState['context']['cashier']"
                :branch="$posState['context']['branch']"
                :register="$posState['context']['register']"
                :shift="$posState['context']['shift']"
                :network-status="$posState['context']['network_status']"
                :sync-status="$posState['context']['sync_status']"
                :registers="$registers"
                :current-register-uuid="$register->uuid"
            />

            <div id="pos-toast" class="pos-toast hidden" role="alert"></div>

            <div class="pos-workspace">
                <div class="pos-panel-left">
                    <div class="pos-search-header">
                        <h2 class="pos-search-header__title">ค้นหาสินค้า</h2>
                        <div class="pos-search-header__inputs">
                            <x-pos::barcode-input />
                            <x-pos::search-bar />
                        </div>
                    </div>
                    <x-pos::product-grid :products="[]" />
                </div>

                <div class="pos-panel-right">
                    <div class="pos-panel-right__scroll">
                        <div id="pos-customer-root">
                            <x-pos::customer-panel
                                :customer="$posState['customer']['customer'] ?? null"
                                :is-guest="$posState['customer']['is_guest']"
                                :reward-points="$posState['customer']['reward_points'] ?? null"
                                :tier="$posState['customer']['tier'] ?? null"
                                :has-special-pricing="$posState['customer']['has_special_pricing'] ?? false"
                            />
                        </div>

                        <div id="pos-cart-root">
                            <x-pos::cart-panel
                                :lines="$posState['cart']['lines']"
                                :item-count="$posState['cart']['item_count']"
                            />
                        </div>

                        <div id="pos-discount-root">
                            <x-pos::discount-notes-panel
                                :notes="$posState['notes']"
                                :coupon-code="$posState['cart']['coupon_code'] ?? null"
                                :promotion-name="$posState['cart']['promotion_name'] ?? null"
                            />
                        </div>

                        <div id="pos-payment-root">
                            <x-pos::payment-panel :selected-method="$posState['payment']['method']" />
                        </div>
                    </div>

                    <div id="pos-summary-root">
                        <x-pos::summary-panel
                            :subtotal="$posState['totals']['subtotal']"
                            :discount="$posState['totals']['discount']"
                            :tax="$posState['totals']['tax']"
                            :shipping="$posState['totals']['shipping']"
                            :grand-total="$posState['totals']['grand_total']"
                        />
                    </div>

                    <x-pos::bottom-actions />
                </div>
            </div>

            <x-pos::shortcut-bar />
        </div>
    </div>

    <x-pos::payment-dialog />
    <x-pos::customer-search-dialog />
    <x-pos::product-search-dialog />
    <x-pos::receipt-dialog />
    <x-pos::hold-dialog />
@endsection
