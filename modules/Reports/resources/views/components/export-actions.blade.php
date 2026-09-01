@props([
    'filter',
    'exportRoute',
    'pdfRoute',
    'printRoute',
])

<div class="flex flex-wrap gap-2">
    <x-admin.button variant="secondary" :href="route($exportRoute, $filter->toQuery())">
        <x-admin.icon name="arrow-down-tray" class="h-4 w-4" />
        Excel (CSV)
    </x-admin.button>
    <x-admin.button variant="secondary" :href="route($pdfRoute, $filter->toQuery())">
        PDF
    </x-admin.button>
    <x-admin.button variant="secondary" :href="route($printRoute, $filter->toQuery())" target="_blank" rel="noopener">
        พิมพ์
    </x-admin.button>
</div>
