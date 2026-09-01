@props([
    'seo' => null,
])

<section class="cf-product-workspace__section">
    <header class="cf-product-workspace__section-header">
        <h2 class="cf-product-workspace__section-title">SEO</h2>
        <p class="cf-product-workspace__section-desc">Search engine and social sharing metadata.</p>
    </header>

    @include('product::admin.products._seo', ['seo' => $seo])
</section>
