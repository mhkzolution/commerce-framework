<div class="grid gap-4 md:grid-cols-2">
    <div>
        <label class="block text-sm font-medium text-text" for="name">Name</label>
        <input id="name" name="name" value="{{ old('name', $user?->name) }}" required class="cf-input mt-1">
    </div>
    <div>
        <label class="block text-sm font-medium text-text" for="email">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email', $user?->email) }}" required class="cf-input mt-1">
    </div>
</div>

<div class="grid gap-4 md:grid-cols-2">
    <div>
        <label class="block text-sm font-medium text-text" for="first_name">First name</label>
        <input id="first_name" name="first_name" value="{{ old('first_name', $user?->profile?->first_name) }}" class="cf-input mt-1">
    </div>
    <div>
        <label class="block text-sm font-medium text-text" for="last_name">Last name</label>
        <input id="last_name" name="last_name" value="{{ old('last_name', $user?->profile?->last_name) }}" class="cf-input mt-1">
    </div>
</div>

<div class="grid gap-4 md:grid-cols-2">
    <div>
        <label class="block text-sm font-medium text-text" for="status">Status</label>
        <select id="status" name="status" class="cf-input mt-1">
            @foreach ($statuses as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $user?->status ?? 'active') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
</div>

<div class="grid gap-4 md:grid-cols-2">
    <div>
        <label class="block text-sm font-medium text-text" for="password">Password</label>
        <input id="password" type="password" name="password" @if (! isset($user)) required @endif class="cf-input mt-1">
        @if (isset($user))
            <p class="mt-1 text-xs text-muted">Leave blank to keep the current password.</p>
        @endif
    </div>
    <div>
        <label class="block text-sm font-medium text-text" for="password_confirmation">Confirm password</label>
        <input id="password_confirmation" type="password" name="password_confirmation" @if (! isset($user)) required @endif class="cf-input mt-1">
    </div>
</div>

@if ($roles !== [])
    <div>
        <p class="text-sm font-medium text-text">Roles</p>
        @error('roles')
            <p class="mt-1 text-sm text-danger">{{ $message }}</p>
        @enderror
        <div class="mt-2 grid gap-2 sm:grid-cols-2">
            @php
                $selectedRoles = old('role_codes', isset($user) ? $user->roles->pluck('code')->all() : []);
            @endphp
            @foreach ($roles as $role)
                <label class="flex items-center gap-2 text-sm text-text-secondary">
                    <input
                        type="checkbox"
                        name="role_codes[]"
                        value="{{ $role->code }}"
                        @checked(in_array($role->code, $selectedRoles, true))
                        class="rounded border-border"
                    >
                    <span>{{ $role->name }}</span>
                    @if ($role->is_system)
                        <x-admin.badge variant="info">System</x-admin.badge>
                    @endif
                </label>
            @endforeach
        </div>
    </div>
@endif
