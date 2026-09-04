@props(['config' => []])

<button
    type="button"
    class="cx-back-to-top cx-back-to-top--{{ $config['style'] ?? 'circle' }} cx-back-to-top--{{ $config['position'] ?? 'bottom-right' }}"
    data-back-to-top
    data-show-after="{{ $config['showAfter'] ?? 500 }}"
    data-smooth="{{ ($config['smoothScroll'] ?? true) ? '1' : '0' }}"
    data-fade="{{ ($config['fadeIn'] ?? true) ? '1' : '0' }}"
    data-target="{{ $config['target'] ?? 'top' }}"
    hidden
    aria-label="{{ __('storefront::storefront.back_to_top') }}"
>
    ↑
</button>
