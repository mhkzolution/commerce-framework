@extends('layouts.admin')

@section('title', $view->number)

@section('page')
    <x-admin.page :title="$view->number" :description="__('documents::admin.tax_invoice')" :wide="true">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => 'Sales'],
                ['label' => __('documents::admin.documents_title'), 'url' => route('admin.documents.index')],
                ['label' => $view->number, 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-slot:primaryActions>
            <x-admin.button variant="secondary" :href="route('admin.documents.print', $document)">{{ __('documents::admin.print') }}</x-admin.button>
            <x-admin.button variant="primary" :href="route('admin.documents.download', $document)">{{ __('documents::admin.download') }}</x-admin.button>
        </x-slot:primaryActions>

        <x-slot:secondaryActions>
            <x-admin.button variant="secondary" :href="route('admin.documents.index')">{{ __('documents::admin.back') }}</x-admin.button>
        </x-slot:secondaryActions>

        <style>
            @include('documents::print.partials.styles')
            .document-preview { overflow: auto; border-radius: 0.5rem; border: 1px solid var(--color-border, #e5e7eb); background: #fff; padding: 24px; }
        </style>

        <div class="document-preview" data-document-view data-document-number="{{ $view->number }}">
            @include('documents::print.partials.sheet')
        </div>
    </x-admin.page>
@endsection
