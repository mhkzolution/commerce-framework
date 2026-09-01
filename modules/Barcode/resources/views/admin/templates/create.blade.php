@extends('layouts.admin')

@section('title', __('barcode::admin.templates.new'))

@section('page')
    <x-admin.page :title="__('barcode::admin.templates.new')" :description="__('barcode::admin.templates.description')">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => __('barcode::admin.title'), 'href' => route('admin.barcode.index')],
                ['label' => __('barcode::admin.templates.title'), 'href' => route('admin.barcode.templates.index')],
                ['label' => __('barcode::admin.templates.new'), 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-admin.form.shell action="{{ route('admin.barcode.templates.store') }}" method="POST" class="max-w-2xl">
            @include('barcode::admin.templates._form')
            <x-slot:actions>
                <x-admin.button variant="primary" type="submit">{{ __('barcode::admin.templates.new') }}</x-admin.button>
            </x-slot:actions>
        </x-admin.form.shell>
    </x-admin.page>
@endsection
