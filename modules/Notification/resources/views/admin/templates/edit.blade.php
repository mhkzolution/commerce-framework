@extends('layouts.admin')

@section('title', $template->name)

@section('page')
    <x-admin.page :title="$template->name" :description="$template->code">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => 'System'],
                ['label' => 'Templates', 'url' => route('admin.notification.templates.index')],
                ['label' => $template->name, 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-admin.form.shell action="{{ route('admin.notification.templates.update', $template) }}" method="POST" class="max-w-2xl">
            @csrf
            @method('PUT')
            <x-admin.form.section title="Template">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-text" for="name">Name</label>
                        <input id="name" name="name" value="{{ old('name', $template->name) }}" required class="cf-input mt-1">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-text" for="subject">Subject</label>
                        <input id="subject" name="subject" value="{{ old('subject', $template->subject) }}" required class="cf-input mt-1">
                        <p class="mt-1 text-xs text-muted">Use placeholders like {{ '{{order_number}}' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-text" for="view">Blade view</label>
                        <input id="view" name="view" value="{{ old('view', $template->view) }}" required class="cf-input mt-1">
                    </div>
                    <label class="flex items-center gap-2 text-sm text-text-secondary">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $template->is_active))>
                        Active
                    </label>
                </div>
            </x-admin.form.section>
            <x-slot:actions>
                <x-admin.button variant="secondary" :href="route('admin.notification.templates.index')">Back</x-admin.button>
                <x-admin.button variant="primary" type="submit">Save</x-admin.button>
            </x-slot:actions>
        </x-admin.form.shell>
    </x-admin.page>
@endsection
