@extends('layouts.admin')

@section('title', __('commerce::admin.modules_title'))

@section('page')
    <x-admin.page
        :title="__('commerce::admin.modules_title')"
        :description="__('commerce::admin.modules_description')"
    >
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => __('commerce::admin.breadcrumb_system')],
                ['label' => __('commerce::admin.breadcrumb_modules'), 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-slot:search>
            <form method="GET" action="{{ route('admin.system.modules.index') }}">
                <x-admin.table.toolbar>
                    <x-slot:search>
                        <x-admin.search-input
                            name="search"
                            :value="$search"
                            :placeholder="__('commerce::admin.module_search')"
                        />
                    </x-slot:search>
                    <x-slot:actions>
                        <x-admin.button type="submit" variant="secondary">
                            {{ __('commerce::admin.module_search') }}
                        </x-admin.button>
                    </x-slot:actions>
                </x-admin.table.toolbar>
            </form>
        </x-slot:search>

        <x-admin.table.shell>
            <x-slot:head>
                <tr class="text-left text-xs uppercase tracking-wide text-muted">
                    <th class="px-4 py-3">{{ __('commerce::admin.module_name') }}</th>
                    <th class="px-4 py-3">{{ __('commerce::admin.module_code') }}</th>
                    <th class="px-4 py-3">{{ __('commerce::admin.module_status') }}</th>
                    <th class="px-4 py-3">{{ __('commerce::admin.module_updated_at') }}</th>
                    <th class="px-4 py-3 text-right">{{ __('commerce::admin.module_actions') }}</th>
                </tr>
            </x-slot:head>

            @forelse ($modules as $module)
                <tr>
                    <td class="px-4 py-3">
                        <div class="font-medium text-text">{{ $module->name }}</div>
                        @if ($module->description)
                            <div class="text-sm text-muted">{{ $module->description }}</div>
                        @endif
                    </td>
                    <td class="px-4 py-3 font-mono text-sm text-muted">{{ $module->code }}</td>
                    <td class="px-4 py-3">
                        <div class="flex flex-wrap items-center gap-2">
                            <x-admin.badge :variant="$module->status->badgeVariant()">
                                {{ $module->status->value }}
                            </x-admin.badge>
                            @if ($module->is_core)
                                <x-admin.badge variant="info">{{ __('commerce::admin.module_core_badge') }}</x-admin.badge>
                            @endif
                        </div>
                    </td>
                    <td class="px-4 py-3 text-muted">{{ $module->updated_at?->timezone(config('app.timezone'))->format('Y-m-d H:i') }}</td>
                    <td class="px-4 py-3 text-right">
                        @if ($module->is_core)
                            <span class="text-sm text-muted">{{ __('commerce::admin.module_core_locked_hint') }}</span>
                        @else
                        <form
                            method="POST"
                            action="{{ route('admin.system.modules.update', $module) }}"
                            class="flex flex-wrap items-center justify-end gap-2"
                            data-module-status-form
                            data-module-name="{{ $module->name }}"
                            data-confirm-template="{{ __('commerce::admin.module_confirm', ['name' => ':name', 'status' => ':status']) }}"
                        >
                            @csrf
                            @method('PUT')
                            <label class="sr-only" for="module-status-{{ $module->id }}">{{ __('commerce::admin.module_status') }}</label>
                            <select
                                id="module-status-{{ $module->id }}"
                                name="status"
                                class="cf-input py-2"
                            >
                                @foreach (\Commerce\Core\Enums\ModuleStatus::cases() as $status)
                                    <option value="{{ $status->value }}" @selected($module->status === $status)>
                                        {{ $status->value }}
                                    </option>
                                @endforeach
                            </select>
                            <x-admin.button type="submit" variant="secondary">
                                {{ __('commerce::admin.module_save') }}
                            </x-admin.button>
                        </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-12 text-center text-muted">
                        {{ __('commerce::admin.module_empty') }}
                    </td>
                </tr>
            @endforelse
        </x-admin.table.shell>
    </x-admin.page>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('[data-module-status-form]').forEach((form) => {
            form.addEventListener('submit', (event) => {
                const status = form.querySelector('[name="status"]')?.value ?? '';
                const name = form.dataset.moduleName ?? '';
                const template = form.dataset.confirmTemplate ?? 'Change :name status to :status?';
                const message = template.replaceAll(':name', name).replaceAll(':status', status);

                if (! window.confirm(message)) {
                    event.preventDefault();
                }
            });
        });
    </script>
@endpush
