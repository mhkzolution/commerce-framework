@extends('layouts.admin')
@section('title', __('cms::admin.create_promotion_banner'))
@section('page')
    <x-admin.page :title="__('cms::admin.create_promotion_banner')" :description="__('cms::admin.promotion_banners_description')">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => __('cms::admin.promotion_banners'), 'url' => route('admin.cms.promotion-banners.index')],
                ['label' => __('cms::admin.create_promotion_banner'), 'active' => true],
            ]" />
        </x-slot:breadcrumb>
        <x-slot:secondaryActions>
            <x-admin.button variant="outline" :href="route('admin.cms.promotion-banners.index')">{{ __('cms::admin.back_to_promotion_banners') }}</x-admin.button>
        </x-slot:secondaryActions>
        <x-admin.form.shell action="{{ route('admin.cms.promotion-banners.store') }}" method="POST" class="max-w-3xl">
            @csrf
            @include('cms::admin.promotion-banners._form')
            <x-slot:actions>
                @include('cms::admin.partials.form-actions', ['indexRoute' => route('admin.cms.promotion-banners.index')])
            </x-slot:actions>
        </x-admin.form.shell>
    </x-admin.page>
@endsection
