@php
    $collection = $collection ?? null;
    $savedRules = $collection?->rules ?? [];
    $useGroups = old('rules.use_groups', ! empty($savedRules['groups']));
    $savedGroups = old('rules.groups', $savedRules['groups'] ?? []);
    if ($savedGroups === []) {
        $savedGroups = [[]];
    }
@endphp

<div>
    <label class="block text-sm font-medium text-text" for="name">Name</label>
    <input id="name" name="name" value="{{ old('name', $collection?->name) }}" required class="cf-input mt-1">
</div>

<div>
    <label class="block text-sm font-medium text-text" for="slug">Slug</label>
    <input id="slug" name="slug" value="{{ old('slug', $collection?->slug) }}" class="cf-input mt-1" placeholder="auto-generated if empty">
</div>

<div>
    <label class="block text-sm font-medium text-text" for="description">Description</label>
    <textarea id="description" name="description" rows="3" class="cf-input mt-1">{{ old('description', $collection?->description) }}</textarea>
</div>

@include('media::components.media-picker', [
    'name' => 'cover_media_uuid',
    'value' => old('cover_media_uuid', $collection?->cover_media_uuid),
    'label' => 'Cover image',
])

<div>
    <label class="block text-sm font-medium text-text" for="type">Collection type</label>
    <select id="type" name="type" class="cf-input mt-1">
        <option value="manual" @selected(old('type', $collection?->type ?? 'manual') === 'manual')>Manual</option>
        <option value="automated" @selected(old('type', $collection?->type ?? 'manual') === 'automated')>Automated</option>
    </select>
</div>

<div class="rounded-lg border border-border p-4">
    <p class="text-sm font-medium text-text">Automated rules</p>
    <p class="mt-1 text-sm text-muted">Drag conditions to reorder them. Products matching the selected logic are synced into this collection.</p>

    <div class="mt-4 grid gap-4">
        <label class="inline-flex items-center gap-2 text-sm text-text-secondary">
            <input type="hidden" name="rules[use_groups]" value="0">
            <input type="checkbox" id="rules_use_groups" name="rules[use_groups]" value="1" @checked($useGroups) class="rounded border-border">
            Use nested rule groups (e.g. Group A AND Group B, or Group A OR Group B)
        </label>

        <div id="flat-rules" class="{{ $useGroups ? 'hidden' : '' }}">
            @include('catalog::admin.collections._automated-rule-fields', [
                'namePrefix' => 'rules',
                'ruleValues' => $savedRules,
            ])
        </div>

        <div id="group-rules" class="{{ $useGroups ? '' : 'hidden' }} grid gap-4">
            <div>
                <label class="block text-sm font-medium text-text" for="rules_group_match">Combine groups</label>
                <select id="rules_group_match" name="rules[match]" class="cf-input mt-1">
                    <option value="any" @selected(old('rules.match', $savedRules['match'] ?? 'any') === 'any')>Match any group (OR)</option>
                    <option value="all" @selected(old('rules.match', $savedRules['match'] ?? 'any') === 'all')>Match all groups (AND)</option>
                </select>
            </div>

            <div id="rule-groups-list" class="grid gap-4">
                @foreach ($savedGroups as $groupIndex => $groupValues)
                    <div class="rounded-lg border border-border bg-primary-subtle p-4" data-rule-group>
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <button type="button" class="text-sm font-medium text-text" data-rule-group-handle>
                                <span class="inline-flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4 text-muted" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                                    </svg>
                                    Rule group <span data-rule-group-label>{{ $loop->iteration }}</span>
                                </span>
                            </button>
                            <button type="button" class="text-sm text-muted hover:text-danger" data-rule-group-remove>Remove</button>
                        </div>
                        @include('catalog::admin.collections._automated-rule-fields', [
                            'namePrefix' => 'rules[groups][' . $groupIndex . ']',
                            'ruleValues' => $groupValues,
                        ])
                    </div>
                @endforeach
            </div>

            <div>
                <button type="button" class="text-sm text-accent hover:underline" data-rule-group-add>Add rule group</button>
                <p class="mt-1 text-xs text-muted">Up to 5 rule groups. Drag groups to reorder them.</p>
            </div>
        </div>
    </div>
</div>

<template id="rule-group-template">
    <div class="rounded-lg border border-border bg-primary-subtle p-4" data-rule-group>
        <div class="mb-3 flex items-center justify-between gap-3">
            <button type="button" class="text-sm font-medium text-text" data-rule-group-handle>
                <span class="inline-flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4 text-muted" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                    Rule group <span data-rule-group-label></span>
                </span>
            </button>
            <button type="button" class="text-sm text-muted hover:text-danger" data-rule-group-remove>Remove</button>
        </div>
        <div data-rule-group-fields></div>
    </div>
</template>

<template id="rule-group-builder-template">
    @include('catalog::admin.collections._automated-rule-builder', [
        'namePrefix' => '__PREFIX__',
        'ruleValues' => [],
        'showGroupMatch' => true,
    ])
</template>

@include('catalog::admin.partials.seo-fields', ['seo' => $seo ?? null])
