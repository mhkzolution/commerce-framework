<div class="grid gap-4 md:grid-cols-2">
    <div>
        <label class="block text-sm font-medium text-text" for="name">Name</label>
        <input id="name" name="name" value="{{ old('name', $role?->name) }}" required class="cf-input mt-1">
    </div>
    @if (! isset($role))
        <div>
            <label class="block text-sm font-medium text-text" for="code">Code</label>
            <input id="code" name="code" value="{{ old('code') }}" required pattern="[a-z0-9]+(?:-[a-z0-9]+)*" class="cf-input mt-1">
            <p class="mt-1 text-xs text-muted">Lowercase letters, numbers, and hyphens only.</p>
        </div>
    @else
        <div>
            <label class="block text-sm font-medium text-text">Code</label>
            <p class="mt-1 text-sm text-text-secondary">{{ $role->code }}</p>
        </div>
    @endif
</div>

<div>
    <label class="block text-sm font-medium text-text" for="description">Description</label>
    <textarea id="description" name="description" rows="3" class="cf-input mt-1">{{ old('description', $role?->description) }}</textarea>
</div>

@if ($permissionsByModule->isNotEmpty())
    <div>
        <p class="text-sm font-medium text-text">Permissions</p>
        @php
            $selectedPermissions = old('permissions', isset($role) ? $role->permissions->pluck('name')->all() : []);
        @endphp
        <div class="mt-3 space-y-4">
            @foreach ($permissionsByModule as $module => $permissions)
                <div class="rounded-md border border-border p-4">
                    <p class="text-sm font-medium capitalize text-text">{{ $module }}</p>
                    <div class="mt-3 grid gap-2 sm:grid-cols-2">
                        @foreach ($permissions as $permission)
                            <label class="flex items-start gap-2 text-sm text-text-secondary">
                                <input
                                    type="checkbox"
                                    name="permissions[]"
                                    value="{{ $permission->name }}"
                                    @checked(in_array($permission->name, $selectedPermissions, true))
                                    class="mt-0.5 rounded border-border"
                                >
                                <span>
                                    <span class="block text-text">{{ $permission->label }}</span>
                                    <span class="text-xs text-muted">{{ $permission->name }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif
