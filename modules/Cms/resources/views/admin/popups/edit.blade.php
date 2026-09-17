@extends('layouts.admin')
@section('title', __('cms::admin.edit_popup'))
@section('page')
    <x-admin.page :title="__('cms::admin.edit_popup')" :description="__('cms::admin.popups_description')" wide>
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => __('cms::admin.popups'), 'url' => route('admin.cms.popups.index')],
                ['label' => $item->title, 'active' => true],
            ]" />
        </x-slot:breadcrumb>
        <x-slot:secondaryActions>
            <x-admin.button variant="outline" :href="route('admin.cms.popups.index')">{{ __('cms::admin.back_to_popups') }}</x-admin.button>
            <form method="POST" action="{{ route('admin.cms.popups.destroy', $item) }}" onsubmit="return confirm('{{ __('cms::admin.confirm_delete') }}')">
                @csrf
                @method('DELETE')
                <x-admin.button variant="danger" type="submit">{{ __('cms::admin.delete') }}</x-admin.button>
            </form>
        </x-slot:secondaryActions>
        <x-admin.form.shell action="{{ route('admin.cms.popups.update', $item) }}" method="POST" data-popup-form>
            @csrf
            @method('PUT')
            @include('cms::admin.popups._form')
            <x-slot:actions>
                @include('cms::admin.partials.form-actions', ['indexRoute' => route('admin.cms.popups.index')])
            </x-slot:actions>
        </x-admin.form.shell>
    </x-admin.page>
@endsection
