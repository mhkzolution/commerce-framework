@extends('layouts.admin')

@section('title', __('commerce::admin.features_title'))

@section('page')
    <x-admin.page
        :title="__('commerce::admin.features_title')"
        :description="__('commerce::admin.features_description')"
    >
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => __('commerce::admin.breadcrumb_system')],
                ['label' => __('commerce::admin.breadcrumb_features'), 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-slot:search>
            <form method="GET" action="{{ route('admin.system.features.index') }}">
                <x-admin.table.toolbar>
                    <x-slot:search>
                        <x-admin.search-input
                            name="search"
                            :value="$search"
                            :placeholder="__('commerce::admin.feature_search')"
                        />
                    </x-slot:search>
                    <x-slot:actions>
                        <x-admin.button type="submit" variant="secondary">
                            {{ __('commerce::admin.feature_search') }}
                        </x-admin.button>
                    </x-slot:actions>
                </x-admin.table.toolbar>
            </form>
        </x-slot:search>

        <x-admin.table.shell>
            <x-slot:head>
                <tr class="text-left text-xs uppercase tracking-wide text-muted">
                    <th class="px-4 py-3">{{ __('commerce::admin.feature_name') }}</th>
                    <th class="px-4 py-3">{{ __('commerce::admin.feature_module') }}</th>
                    <th class="px-4 py-3">{{ __('commerce::admin.feature_status') }}</th>
                    <th class="px-4 py-3">{{ __('commerce::admin.feature_updated_at') }}</th>
                    <th class="px-4 py-3 text-right">{{ __('commerce::admin.feature_actions') }}</th>
                </tr>
            </x-slot:head>

            @forelse ($features as $feature)
                <tr>
                    <td class="px-4 py-3">
                        <div class="font-medium text-text">{{ $feature->name }}</div>
                        <div class="font-mono text-sm text-muted">{{ $feature->code }}</div>
                        @if ($feature->description)
                            <div class="text-sm text-muted">{{ $feature->description }}</div>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="font-medium text-text">{{ $moduleNames[$feature->module_code] }}</div>
                        <div class="font-mono text-sm text-muted">{{ $feature->module_code }}</div>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex flex-wrap items-center gap-2">
                            <x-admin.badge :variant="$feature->status->badgeVariant()">
                                {{ $feature->status->value }}
                            </x-admin.badge>
                            @if (($disabledModules[$feature->module_code] ?? false) && $feature->status === \Commerce\Core\Enums\FeatureStatus::Enabled)
                                <x-admin.badge variant="warning">INACTIVE (MODULE DISABLED)</x-admin.badge>
                            @endif
                        </div>
                        @if (($disabledModules[$feature->module_code] ?? false) && $feature->status === \Commerce\Core\Enums\FeatureStatus::Enabled)
                            <div class="mt-1 text-sm text-muted">{{ __('commerce::admin.feature_parent_disabled_hint') }}</div>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-muted">{{ $feature->updated_at?->timezone(config('app.timezone'))->format('Y-m-d H:i') }}</td>
                    <td class="px-4 py-3 text-right">
                        <form
                            method="POST"
                            action="{{ route('admin.system.features.update', $feature) }}"
                            class="flex flex-wrap items-center justify-end gap-2"
                            data-feature-status-form
                            data-feature-name="{{ $feature->name }}"
                            data-confirm-template="{{ __('commerce::admin.feature_confirm', ['name' => ':name', 'status' => ':status']) }}"
                        >
                            @csrf
                            @method('PUT')
                            <label class="sr-only" for="feature-status-{{ $feature->id }}">{{ __('commerce::admin.feature_status') }}</label>
                            <select
                                id="feature-status-{{ $feature->id }}"
                                name="status"
                                class="cf-input py-2"
                            >
                                @foreach (\Commerce\Core\Enums\FeatureStatus::cases() as $status)
                                    <option value="{{ $status->value }}" @selected($feature->status === $status)>
                                        {{ $status->value }}
                                    </option>
                                @endforeach
                            </select>
                            <x-admin.button type="submit" variant="secondary">
                                {{ __('commerce::admin.feature_save') }}
                            </x-admin.button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-12 text-center text-muted">
                        {{ __('commerce::admin.feature_empty') }}
                    </td>
                </tr>
            @endforelse
        </x-admin.table.shell>
    </x-admin.page>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('[data-feature-status-form]').forEach((form) => {
            form.addEventListener('submit', (event) => {
                const status = form.querySelector('[name="status"]')?.value ?? '';
                const name = form.dataset.featureName ?? '';
                const template = form.dataset.confirmTemplate ?? 'Change :name status to :status?';
                const message = template.replaceAll(':name', name).replaceAll(':status', status);

                if (! window.confirm(message)) {
                    event.preventDefault();
                }
            });
        });
    </script>
@endpush
