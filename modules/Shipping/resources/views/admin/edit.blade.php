@extends('layouts.admin')

@section('title', $method->name)

@section('page')
    <x-admin.page :title="$method->name" :description="$method->code">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => 'Configuration'],
                ['label' => 'Shipping', 'url' => route('admin.shipping.index')],
                ['label' => $method->name, 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-slot:secondaryActions>
            <form method="POST" action="{{ route('admin.shipping.destroy', $method) }}" onsubmit="return confirm('Delete this shipping method?')">
                @csrf
                @method('DELETE')
                <x-admin.button variant="danger" type="submit">Delete</x-admin.button>
            </form>
        </x-slot:secondaryActions>

        <x-admin.form.shell action="{{ route('admin.shipping.update', $method) }}" method="POST" class="max-w-3xl">
            @csrf
            @method('PUT')
            <x-admin.form.section title="Method details">
                @include('shipping::admin._form', ['method' => $method])
            </x-admin.form.section>
            <x-slot:actions>
                <x-admin.button variant="secondary" :href="route('admin.shipping.index')">Cancel</x-admin.button>
                <x-admin.button variant="primary" type="submit">Save changes</x-admin.button>
            </x-slot:actions>
        </x-admin.form.shell>
    </x-admin.page>
@endsection
