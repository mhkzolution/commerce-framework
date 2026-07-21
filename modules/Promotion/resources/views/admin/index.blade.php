@extends('layouts.admin')

@section('title', 'Promotions')

@section('page')
    <x-admin.page title="Promotions" description="Coupons and cart discounts.">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => 'Marketing'],
                ['label' => 'Promotions', 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-slot:primaryActions>
            <x-admin.button variant="primary" :href="route('admin.promotions.create')">
                <x-admin.icon name="plus" class="h-4 w-4" />
                Add promotion
            </x-admin.button>
        </x-slot:primaryActions>

        <x-admin.table.shell>
            <x-slot:head>
                <tr class="text-left text-xs uppercase tracking-wide text-muted">
                    <th class="px-4 py-3">Code</th>
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Type</th>
                    <th class="px-4 py-3">Used</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </x-slot:head>

            @forelse ($promotions as $promotion)
                <tr>
                    <td class="px-4 py-3 font-medium text-text">{{ $promotion->code }}</td>
                    <td class="px-4 py-3">{{ $promotion->name }}</td>
                    <td class="px-4 py-3">
                        <x-admin.badge variant="info">{{ $promotion->type }}</x-admin.badge>
                    </td>
                    <td class="px-4 py-3 text-muted">
                        {{ $promotion->used_count }}{{ $promotion->max_uses ? '/'.$promotion->max_uses : '' }}
                    </td>
                    <td class="px-4 py-3 text-right">
                        <x-admin.button variant="link" :href="route('admin.promotions.edit', $promotion)">Edit</x-admin.button>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-8 text-center text-muted">No promotions yet.</td></tr>
            @endforelse
        </x-admin.table.shell>
    </x-admin.page>
@endsection
