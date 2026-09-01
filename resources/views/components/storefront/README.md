# Storefront Component Library

Canonical UI primitives for Commerce Framework storefront pages.

## Principles

1. **Compose pages from primitives** — `/shop`, `/product`, `/blog`, `/cart`, `/account` share the same building blocks.
2. **Change once, update everywhere** — tokens and components live in `resources/css/storefront/` and this directory.
3. **Canonical paths** — use namespaced tags (`x-storefront.cards.product-card`). Legacy flat names remain as aliases.

## Registry

### Layout — `x-storefront.layout.*`

- `store-layout` — full page shell
- `page-container` — content width wrapper
- `section` — titled block
- `section-header` — title + description + actions
- `grid` — responsive grid (`variant`: product | blog)
- `empty-state` — empty/zero state
- `partials.site-header` — global header

### Cards — `x-storefront.cards.*`

- `product-card`
- `blog-card`
- `category-card`
- `brand-card`
- `review-card`

### Buttons — `x-storefront.buttons.*`

- `primary-button`
- `ghost-button`
- `add-to-cart-button`
- `wishlist-button`
- `share-button`

### Forms — `x-storefront.forms.*`

- `search-bar`
- `sort-dropdown`
- `filter-panel`
- `filters-form`
- `hidden-filters`
- `variant-selector`
- `attribute-list`

### Navigation — `x-storefront.navigation.*`

- `breadcrumb`
- `pagination`
- `tabs`
- `drawer`
- `mobile-bottom-sheet`

### Commerce — `x-storefront.commerce.*`

- `price`
- `stock-badge`
- `rating`
- `product-gallery`
- `delivery-info`
- `mini-cart`
- `cart-summary`
- `order-summary`
- `checkout-button`

### Domain composites

- `blog/*` — blog index/show sections
- `cart/*` — cart line items, coupon, shipping
- `auth/*` — login / forgot-password

## Example

```blade
<x-storefront.layout.page-container>
    <x-storefront.layout.section-header :title="__('storefront::storefront.shop')" />

    <x-storefront.layout.grid variant="product">
        @foreach ($products as $product)
            <x-storefront.cards.product-card ... />
        @endforeach
    </x-storefront.layout.grid>

    <x-storefront.navigation.pagination :paginator="$products" />
</x-storefront.layout.page-container>
```

## References

- Design language: `/DESIGN.md`
- Styles: `resources/css/storefront/`
- Legacy aliases: flat files at `components/storefront/*.blade.php`
