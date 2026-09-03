@extends('layouts.admin')

@section('title', __('barcode::admin.templates.edit'))

@section('page')
    <x-admin.page :title="__('barcode::admin.templates.edit')" :description="$template->name">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => __('barcode::admin.title'), 'href' => route('admin.barcode.index')],
                ['label' => __('barcode::admin.templates.title'), 'href' => route('admin.barcode.templates.index')],
                ['label' => $template->name, 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-admin.form.shell action="{{ route('admin.barcode.templates.update', $template) }}" method="POST" class="max-w-2xl">
            @method('PUT')
            @include('barcode::admin.templates._form', ['template' => $template])
            <x-slot:actions>
                <x-admin.button variant="primary" type="submit">{{ __('barcode::admin.templates.edit') }}</x-admin.button>
            </x-slot:actions>
        </x-admin.form.shell>
    </x-admin.page>
@endsection
