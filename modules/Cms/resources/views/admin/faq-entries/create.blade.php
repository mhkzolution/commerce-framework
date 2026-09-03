@extends('layouts.admin')
@section('title', __('cms::admin.create_faq_entry'))
@section('page')
    <x-admin.page :title="__('cms::admin.create_faq_entry')" :description="__('cms::admin.faq_entries_description')">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => __('cms::admin.faq_entries'), 'url' => route('admin.cms.faq-entries.index')],
                ['label' => __('cms::admin.create_faq_entry'), 'active' => true],
            ]" />
        </x-slot:breadcrumb>
        <x-slot:secondaryActions>
            <x-admin.button variant="outline" :href="route('admin.cms.faq-entries.index')">{{ __('cms::admin.back_to_faq_entries') }}</x-admin.button>
        </x-slot:secondaryActions>
        <x-admin.form.shell action="{{ route('admin.cms.faq-entries.store') }}" method="POST" class="max-w-3xl">
            @csrf
            @include('cms::admin.faq-entries._form')
            <x-slot:actions>
                @include('cms::admin.partials.form-actions', ['indexRoute' => route('admin.cms.faq-entries.index')])
            </x-slot:actions>
        </x-admin.form.shell>
    </x-admin.page>
@endsection
