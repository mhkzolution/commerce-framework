@extends('layouts.admin')

@section('title', $template->name)

@section('page')
    <x-admin.page :title="$template->name" :description="$template->code">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => __('admin::nav.groups.configuration')],
                ['label' => __('notification::admin.templates_title'), 'url' => route('admin.notification.templates.index')],
                ['label' => $template->name, 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        @session('status')
            <div class="cf-flash cf-flash--success mb-6" role="status">{{ $value }}</div>
        @endsession

        <form method="POST" action="{{ route('admin.notification.templates.update', $template) }}" class="max-w-3xl space-y-6">
            @csrf
            @method('PUT')

            <x-admin.card :title="__('notification::admin.template')">
                <div class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-text" for="name">{{ __('notification::admin.name') }}</label>
                        <input id="name" type="text" name="name" value="{{ old('name', $template->name) }}" required class="cf-input mt-1">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-text" for="subject">{{ __('notification::admin.subject') }}</label>
                        <input id="subject" type="text" name="subject" value="{{ old('subject', $template->subject) }}" required class="cf-input mt-1">
                        <p class="mt-1 text-sm text-muted">{{ __('notification::admin.subject_hint') }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-text" for="view">{{ __('notification::admin.view') }}</label>
                        <input id="view" type="text" name="view" value="{{ old('view', $template->view) }}" required class="cf-input mt-1">
                    </div>

                    <label class="flex items-center gap-2 text-sm text-text-secondary">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $template->is_active))>
                        {{ __('notification::admin.active') }}
                    </label>
                </div>
            </x-admin.card>

            <div class="flex items-center gap-3">
                <x-admin.button variant="primary" type="submit">{{ __('notification::admin.save') }}</x-admin.button>
                <x-admin.button variant="secondary" :href="route('admin.notification.templates.index')">{{ __('notification::admin.back') }}</x-admin.button>
            </div>
        </form>
    </x-admin.page>
@endsection
