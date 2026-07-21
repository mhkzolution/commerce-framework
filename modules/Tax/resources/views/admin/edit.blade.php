@extends('layouts.admin')

@section('title', $rate->name)

@section('page')
    <x-admin.page :title="$rate->name" :description="$rate->code">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => 'Marketing'],
                ['label' => 'Tax rates', 'url' => route('admin.tax.index')],
                ['label' => $rate->name, 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-slot:secondaryActions>
            <form method="POST" action="{{ route('admin.tax.destroy', $rate) }}" onsubmit="return confirm('Delete this tax rate?')">
                @csrf
                @method('DELETE')
                <x-admin.button variant="danger" type="submit">Delete</x-admin.button>
            </form>
        </x-slot:secondaryActions>

        <x-admin.form.shell action="{{ route('admin.tax.update', $rate) }}" method="POST" class="max-w-3xl">
            @csrf
            @method('PUT')
            <x-admin.form.section title="Rate details">
                @include('tax::admin._form', ['rate' => $rate])
            </x-admin.form.section>
            <x-slot:actions>
                <x-admin.button variant="secondary" :href="route('admin.tax.index')">Cancel</x-admin.button>
                <x-admin.button variant="primary" type="submit">Save changes</x-admin.button>
            </x-slot:actions>
        </x-admin.form.shell>
    </x-admin.page>
@endsection
