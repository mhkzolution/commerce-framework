@extends('layouts.admin')

@section('title', __('product::workspace.variant_options_edit'))

@section('page')
    <x-admin.page :title="__('product::workspace.variant_options_edit')">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => __('catalog::admin.catalog'), 'url' => route('admin.catalog.index')],
                ['label' => __('product::workspace.variant_options_title'), 'url' => route('admin.catalog.variant-options.index')],
                ['label' => $option->name, 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-admin.form.shell :action="route('admin.catalog.variant-options.update', $option)" method="POST" class="max-w-2xl">
            @csrf
            @method('PUT')
            @include('product::admin.variant-options._form', ['option' => $option])

            <div class="flex gap-2">
                <x-admin.button variant="primary" type="submit">{{ __('product::workspace.save') }}</x-admin.button>
                <x-admin.button variant="secondary" :href="route('admin.catalog.variant-options.index')">{{ __('product::workspace.cancel') }}</x-admin.button>
            </div>
        </x-admin.form.shell>
    </x-admin.page>
@endsection
