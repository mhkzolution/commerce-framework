@props([
    'paginator' => null,
])

@if ($paginator && $paginator->hasPages())
    <nav {{ $attributes->merge(['class' => 'storefront-pagination']) }} aria-label="Pagination">
        {{ $paginator->withQueryString()->links() }}
    </nav>
@endif
