@props(['empty' => 'No records found.'])

<div {{ $attributes->merge(['class' => 'admin-table-shell overflow-hidden rounded-xl border border-border bg-card shadow-sm']) }}>
    @isset($toolbar)
        <div class="border-b border-border p-4">{{ $toolbar }}</div>
    @endisset

    @isset($bulk)
        <div class="border-b border-border bg-primary-subtle px-4 py-3 text-sm">{{ $bulk }}</div>
    @endisset

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-border text-sm">
            @isset($head)
                <thead>{{ $head }}</thead>
            @endisset
            <tbody class="divide-y divide-border">
                {{ $slot }}
            </tbody>
        </table>
    </div>

    @if (trim($slot) === '' && !isset($head))
        <div class="px-4 py-12 text-center text-sm text-muted">{{ $empty }}</div>
    @endif

    @isset($pagination)
        <div class="border-t border-border px-4 py-3">{{ $pagination }}</div>
    @endisset
</div>
