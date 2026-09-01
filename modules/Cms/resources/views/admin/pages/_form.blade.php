@php
    $item = $item ?? null;
@endphp

<div class="cms-workspace">
    <div class="cms-workspace__main">
        <div class="cms-writing-canvas">
            <label class="sr-only" for="cms-page-title">Title</label>
            <input
                id="cms-page-title"
                name="title"
                value="{{ old('title', $item?->title) }}"
                class="cms-writing-title"
                placeholder="Title"
                required
            >

            <label class="sr-only" for="cms-page-slug">Slug</label>
            <input
                id="cms-page-slug"
                name="slug"
                value="{{ old('slug', $item?->slug) }}"
                class="cms-writing-slug"
                placeholder="slug (auto-generated from title)"
            >

            @include('cms::components.editor', ['name' => 'content', 'value' => old('content', $item?->content)])
        </div>
    </div>

    <aside class="cms-workspace__aside">
        <details class="cms-sidebar-card" open>
            <summary>Publish</summary>
            <div class="cms-sidebar-card__body">
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
            </div>
        </details>

        <details class="cms-sidebar-card">
            <summary>SEO</summary>
            <div class="cms-sidebar-card__body">
                @include('product::admin.products._seo', ['seo' => $seo ?? null])
            </div>
        </details>
    </aside>
</div>
