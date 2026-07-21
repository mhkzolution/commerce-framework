@extends('layouts.admin')

@section('title', $user->name)

@section('page')
    <x-admin.page :title="$user->name" :description="$user->email">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => 'Identity'],
                ['label' => 'Users', 'url' => route('admin.iam.users.index')],
                ['label' => $user->name, 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-slot:secondaryActions>
            <form method="POST" action="{{ route('admin.iam.users.destroy', $user) }}" onsubmit="return confirm('Delete this user?')">
                @csrf
                @method('DELETE')
                <x-admin.button variant="danger" type="submit">Delete</x-admin.button>
            </form>
        </x-slot:secondaryActions>

        @error('delete')
            <div class="cf-flash cf-flash--danger mb-4 max-w-2xl" role="alert">{{ $message }}</div>
        @enderror

        <x-admin.form.shell action="{{ route('admin.iam.users.update', $user) }}" method="POST" class="max-w-2xl">
            @csrf
            @method('PUT')
            <x-admin.form.section title="Account details">
                @include('iam::admin.users._form', ['user' => $user, 'statuses' => $statuses, 'roles' => $roles])
            </x-admin.form.section>
            <x-slot:actions>
                <x-admin.button variant="secondary" :href="route('admin.iam.users.index')">Cancel</x-admin.button>
                <x-admin.button variant="primary" type="submit">Save changes</x-admin.button>
            </x-slot:actions>
        </x-admin.form.shell>

        <x-admin.card title="Activity" class="mt-6 max-w-2xl">
            <dl class="grid gap-4 text-sm sm:grid-cols-2">
                <div>
                    <dt class="text-muted">Last login</dt>
                    <dd class="mt-1 text-text">{{ $user->last_login_at?->format('Y-m-d H:i') ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-muted">Email verified</dt>
                    <dd class="mt-1 text-text">{{ $user->email_verified_at?->format('Y-m-d H:i') ?? 'Not verified' }}</dd>
                </div>
                <div>
                    <dt class="text-muted">Created</dt>
                    <dd class="mt-1 text-text">{{ $user->created_at?->format('Y-m-d H:i') }}</dd>
                </div>
                <div>
                    <dt class="text-muted">Updated</dt>
                    <dd class="mt-1 text-text">{{ $user->updated_at?->format('Y-m-d H:i') }}</dd>
                </div>
            </dl>
        </x-admin.card>
    </x-admin.page>
@endsection
