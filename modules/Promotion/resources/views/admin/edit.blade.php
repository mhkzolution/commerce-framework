@extends('layouts.admin')

@section('title', $promotion->name)

@section('page')
    <x-admin.page :title="$promotion->name" :description="$promotion->code">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => 'Marketing'],
                ['label' => 'Promotions', 'url' => route('admin.promotions.index')],
                ['label' => $promotion->name, 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-slot:secondaryActions>
            <form method="POST" action="{{ route('admin.promotions.destroy', $promotion) }}" onsubmit="return confirm('Delete this promotion?')">
                @csrf
                @method('DELETE')
                <x-admin.button variant="danger" type="submit">Delete</x-admin.button>
            </form>
        </x-slot:secondaryActions>

        <x-admin.form.shell action="{{ route('admin.promotions.update', $promotion) }}" method="POST" class="max-w-3xl">
            @csrf
            @method('PUT')
            <x-admin.form.section title="Promotion details">
                @include('promotion::admin._form', ['promotion' => $promotion])
            </x-admin.form.section>
            <x-slot:actions>
                <x-admin.button variant="secondary" :href="route('admin.promotions.index')">Cancel</x-admin.button>
                <x-admin.button variant="primary" type="submit">Save changes</x-admin.button>
            </x-slot:actions>
        </x-admin.form.shell>
    </x-admin.page>
@endsection
