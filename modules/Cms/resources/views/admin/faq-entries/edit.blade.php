@extends('layouts.admin')
@section('title', __('cms::admin.edit_faq_entry'))
@section('page')
    <x-admin.page :title="__('cms::admin.edit_faq_entry')" :description="__('cms::admin.faq_entries_description')">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => __('cms::admin.faq_entries'), 'url' => route('admin.cms.faq-entries.index')],
                ['label' => __('cms::admin.edit_faq_entry'), 'active' => true],
            ]" />
        </x-slot:breadcrumb>
        <x-slot:secondaryActions>
            <x-admin.button variant="outline" :href="route('admin.cms.faq-entries.index')">{{ __('cms::admin.back_to_faq_entries') }}</x-admin.button>
            <form method="POST" action="{{ route('admin.cms.faq-entries.destroy', $item) }}" onsubmit="return confirm('{{ __('cms::admin.confirm_delete') }}')">
                @csrf
                @method('DELETE')
                <x-admin.button variant="danger" type="submit">{{ __('cms::admin.delete') }}</x-admin.button>
            </form>
        </x-slot:secondaryActions>
        <x-admin.form.shell action="{{ route('admin.cms.faq-entries.update', $item) }}" method="POST" class="max-w-3xl">
            @csrf
            @method('PUT')
            @include('cms::admin.faq-entries._form')
            <x-slot:actions>
                @include('cms::admin.partials.form-actions', ['indexRoute' => route('admin.cms.faq-entries.index')])
            </x-slot:actions>
        </x-admin.form.shell>
    </x-admin.page>
@endsection
