@php
    $item = $item ?? null;
@endphp

<div class="cms-workspace">
    <div class="cms-workspace__main">
        <x-admin.form.section title="Page">
            <label class="block text-sm font-medium text-text">Title</label>
            <input name="title" value="{{ old('title', $item?->title) }}" class="cf-input mt-1" required>

            <label class="mt-4 block text-sm font-medium text-text">Slug</label>
            <input name="slug" value="{{ old('slug', $item?->slug) }}" class="cf-input mt-1" placeholder="auto-generated from title">

            <label class="mt-4 block text-sm font-medium text-text">Content</label>
            @include('cms::components.editor', ['name' => 'content', 'value' => old('content', $item?->content)])
        </x-admin.form.section>
    </div>

    <aside class="cms-workspace__aside">
        <x-admin.form.section title="Publish">
            <label class="block text-sm font-medium text-text">Status</label>
            <select name="status" class="cf-input mt-1">
                @foreach ($statuses as $k => $v)
                    <option value="{{ $k }}" @selected(old('status', $item?->status ?? 'draft') == $k)>{{ $v }}</option>
                @endforeach
            </select>

            @if (feature('scheduled-publishing'))
                <label class="mt-4 block text-sm font-medium text-text">{{ __('cms::admin.published_at') }}</label>
                <input name="published_at" type="datetime-local" value="{{ old('published_at', optional($item?->published_at)->format('Y-m-d\TH:i')) }}" class="cf-input mt-1">
                <p class="mt-1 text-xs text-text-secondary">{{ __('cms::admin.schedule_helper') }}</p>

                <label class="mt-4 block text-sm font-medium text-text">{{ __('cms::admin.unpublish_at') }}</label>
                <input name="unpublish_at" type="datetime-local" value="{{ old('unpublish_at', optional($item?->unpublish_at)->format('Y-m-d\TH:i')) }}" class="cf-input mt-1">
            @endif
        </x-admin.form.section>

        <details class="cms-seo-panel rounded-xl border border-border bg-card p-5">
            <summary>SEO</summary>
            <div class="mt-4">
                @include('product::admin.products._seo', ['seo' => $seo ?? null])
            </div>
        </details>
    </aside>
</div>
