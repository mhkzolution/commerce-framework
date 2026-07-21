@props([
    'title',
    'description' => null,
])

<div class="mx-auto max-w-7xl">
    <x-admin.flash />

    <x-admin.page-header :title="$title" :description="$description">
        @isset($breadcrumb)
            <x-slot:breadcrumb>{{ $breadcrumb }}</x-slot:breadcrumb>
        @endisset
        @isset($primaryActions)
            <x-slot:primaryActions>{{ $primaryActions }}</x-slot:primaryActions>
        @endisset
        @isset($secondaryActions)
            <x-slot:secondaryActions>{{ $secondaryActions }}</x-slot:secondaryActions>
        @endisset
    </x-admin.page-header>

    @isset($filters)
        <div class="mb-4">{{ $filters }}</div>
    @endisset

    @isset($search)
        <div class="mb-4">{{ $search }}</div>
    @endisset

    <div {{ $attributes->merge(['class' => 'space-y-4']) }}>
        {{ $slot }}
    </div>
</div>
