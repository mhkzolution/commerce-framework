@extends('layouts.admin')

@section('title', __('barcode::admin.history.title'))

@section('page')
    <x-admin.page :title="__('barcode::admin.history.title')" :description="__('barcode::admin.history.description')">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => __('barcode::admin.title'), 'href' => route('admin.barcode.index')],
                ['label' => __('barcode::admin.history.title'), 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-slot:secondaryActions>
            <x-admin.button variant="ghost" :href="route('admin.barcode.index')">
                {{ __('barcode::admin.nav.print') }}
            </x-admin.button>
        </x-slot:secondaryActions>

        <x-barcode::print-history-table :jobs="$jobs" />
    </x-admin.page>
@endsection

@push('head')
    @vite(['resources/js/barcode/history.js'])
@endpush
