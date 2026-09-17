@extends('layouts.admin')

@section('title', __('documents::admin.company_title'))

@section('page')
    <x-admin.page
        :title="__('documents::admin.company_title')"
        :description="__('documents::admin.company_description')"
    >
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => __('settings::admin.configuration')],
                ['label' => __('documents::admin.company_title'), 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <form method="POST" action="{{ route('admin.settings.company.update') }}" class="max-w-3xl space-y-6">
            @csrf
            @method('PUT')

            <x-admin.card :title="__('documents::admin.company_title')">
                <div class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-text" for="company-name">{{ __('documents::admin.company_name') }}</label>
                        <input id="company-name" type="text" name="name" value="{{ old('name', $profile->name) }}" required class="cf-input mt-1">
                    </div>

                    <div class="grid gap-6 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-text" for="company-tax-id">{{ __('documents::admin.tax_id') }}</label>
                            <input id="company-tax-id" type="text" name="tax_id" value="{{ old('tax_id', $profile->taxId) }}" inputmode="numeric" maxlength="13" required class="cf-input mt-1">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-text" for="company-branch-no">{{ __('documents::admin.branch_no') }}</label>
                            <input id="company-branch-no" type="text" name="branch_no" value="{{ old('branch_no', $profile->branchNo) }}" inputmode="numeric" maxlength="5" class="cf-input mt-1">
                            <p class="mt-1 text-sm text-muted">{{ __('documents::admin.branch_no_hint') }}</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-text" for="company-address">{{ __('documents::admin.address') }}</label>
                        <textarea id="company-address" name="address" rows="3" class="cf-input mt-1">{{ old('address', $profile->address) }}</textarea>
                    </div>

                    <div class="grid gap-6 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-text" for="company-phone">{{ __('documents::admin.phone') }}</label>
                            <input id="company-phone" type="text" name="phone" value="{{ old('phone', $profile->phone) }}" class="cf-input mt-1">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-text" for="company-email">{{ __('documents::admin.email') }}</label>
                            <input id="company-email" type="email" name="email" value="{{ old('email', $profile->email) }}" class="cf-input mt-1">
                        </div>
                    </div>

                    @include('media::components.file-attach', [
                        'name' => 'logo',
                        'value' => old('logo', $profile->logo !== '' ? $profile->logo : null),
                        'label' => __('documents::admin.logo'),
                        'imagesOnly' => true,
                    ])
                </div>
            </x-admin.card>

            <div class="flex items-center gap-3">
                <x-admin.button variant="primary" type="submit">{{ __('documents::admin.save_company') }}</x-admin.button>
            </div>
        </form>
    </x-admin.page>
@endsection
