@extends('layouts.admin')

@section('title', 'Edit currency')

@section('page')
    <x-admin.page :title="'Edit '.$currency->code" :description="$currency->name">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => 'Configuration'],
                ['label' => 'Currencies', 'url' => route('admin.currencies.index')],
                ['label' => $currency->code, 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        @unless ($currency->is_base)
            <x-slot:secondaryActions>
                <form method="POST" action="{{ route('admin.currencies.destroy', $currency) }}" onsubmit="return confirm('Delete this currency?')">
                    @csrf
                    @method('DELETE')
                    <x-admin.button variant="danger" type="submit">Delete</x-admin.button>
                </form>
            </x-slot:secondaryActions>
        @endunless

        <x-admin.form.shell action="{{ route('admin.currencies.update', $currency) }}" method="POST" class="max-w-3xl">
            @csrf
            @method('PUT')
            <x-admin.form.section title="Currency details">
                @include('currency::admin._form', ['currency' => $currency])
            </x-admin.form.section>
            <x-slot:actions>
                <x-admin.button variant="secondary" :href="route('admin.currencies.index')">Cancel</x-admin.button>
                <x-admin.button variant="primary" type="submit">Save changes</x-admin.button>
            </x-slot:actions>
        </x-admin.form.shell>
    </x-admin.page>
@endsection
