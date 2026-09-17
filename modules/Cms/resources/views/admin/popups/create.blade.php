@extends('layouts.admin')
@section('title', __('cms::admin.create_popup'))
@section('page')
    <x-admin.page :title="__('cms::admin.create_popup')" :description="__('cms::admin.popups_description')" wide>
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => __('cms::admin.popups'), 'url' => route('admin.cms.popups.index')],
                ['label' => __('cms::admin.create_popup'), 'active' => true],
            ]" />
        </x-slot:breadcrumb>
        <x-slot:secondaryActions>
            <x-admin.button variant="outline" :href="route('admin.cms.popups.index')">{{ __('cms::admin.back_to_popups') }}</x-admin.button>
        </x-slot:secondaryActions>
        <x-admin.form.shell action="{{ route('admin.cms.popups.store') }}" method="POST" data-popup-form>
            @csrf
            @include('cms::admin.popups._form')
            <x-slot:actions>
                @include('cms::admin.partials.form-actions', ['indexRoute' => route('admin.cms.popups.index')])
            </x-slot:actions>
        </x-admin.form.shell>
    </x-admin.page>
@endsection
