@extends('layouts.admin')
@section('title', __('cms::admin.edit_promotion_banner'))
@section('page')
    <x-admin.page :title="__('cms::admin.edit_promotion_banner')" :description="__('cms::admin.promotion_banners_description')">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => __('cms::admin.promotion_banners'), 'url' => route('admin.cms.promotion-banners.index')],
                ['label' => __('cms::admin.edit_promotion_banner'), 'active' => true],
            ]" />
        </x-slot:breadcrumb>
        <x-slot:secondaryActions>
            <x-admin.button variant="outline" :href="route('admin.cms.promotion-banners.index')">{{ __('cms::admin.back_to_promotion_banners') }}</x-admin.button>
            <form method="POST" action="{{ route('admin.cms.promotion-banners.destroy', $item) }}" onsubmit="return confirm('{{ __('cms::admin.confirm_delete') }}')">
                @csrf
                @method('DELETE')
                <x-admin.button variant="danger" type="submit">{{ __('cms::admin.delete') }}</x-admin.button>
            </form>
        </x-slot:secondaryActions>
        <x-admin.form.shell action="{{ route('admin.cms.promotion-banners.update', $item) }}" method="POST" class="max-w-3xl">
            @csrf
            @method('PUT')
            @include('cms::admin.promotion-banners._form')
            <x-slot:actions>
                @include('cms::admin.partials.form-actions', ['indexRoute' => route('admin.cms.promotion-banners.index')])
            </x-slot:actions>
        </x-admin.form.shell>
    </x-admin.page>
@endsection
