@extends('layouts.admin')

@section('title', __('product::workspace.variant_options_create'))

@section('page')
    <x-admin.page :title="__('product::workspace.variant_options_create')">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => __('catalog::admin.catalog'), 'url' => route('admin.catalog.index')],
                ['label' => __('product::workspace.variant_options_title'), 'url' => route('admin.catalog.variant-options.index')],
                ['label' => __('product::workspace.variant_options_create'), 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-admin.form.shell :action="route('admin.catalog.variant-options.store')" method="POST" class="max-w-2xl">
            @include('product::admin.variant-options._form', ['suggestedCode' => $suggestedCode])

            <div class="flex gap-2">
                <x-admin.button variant="primary" type="submit">{{ __('product::workspace.save') }}</x-admin.button>
                <x-admin.button variant="secondary" :href="route('admin.catalog.variant-options.index')">{{ __('product::workspace.cancel') }}</x-admin.button>
            </div>
        </x-admin.form.shell>
    </x-admin.page>
@endsection
