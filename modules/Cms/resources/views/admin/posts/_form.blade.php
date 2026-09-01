@php
    $item = $item ?? null;
    $selectedTagIds = old('tag_ids', $item?->tags?->pluck('id')->all() ?? []);
@endphp

<div class="cms-workspace">
    <div class="cms-workspace__main">
        <div class="cms-writing-canvas">
            <label class="sr-only" for="cms-post-title">Title</label>
            <input
                id="cms-post-title"
                name="title"
                value="{{ old('title', $item?->title) }}"
                class="cms-writing-title"
                placeholder="Title"
                required
            >

            <label class="sr-only" for="cms-post-slug">Slug</label>
            <input
                id="cms-post-slug"
                name="slug"
                value="{{ old('slug', $item?->slug) }}"
                class="cms-writing-slug"
                placeholder="slug (auto-generated if empty)"
            >

            <label class="sr-only" for="cms-post-excerpt">Excerpt</label>
            <textarea
                id="cms-post-excerpt"
                name="excerpt"
                class="cms-writing-excerpt"
                rows="2"
                placeholder="Excerpt"
            >{{ old('excerpt', $item?->excerpt) }}</textarea>

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

                <label class="mt-4 inline-flex items-center gap-2 text-sm text-text-secondary">
                    <input type="hidden" name="is_featured" value="0">
                    <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $item?->is_featured))>
                    Featured post
                </label>
            </div>
        </details>

        <details class="cms-sidebar-card" open>
            <summary>Category</summary>
            <div class="cms-sidebar-card__body">
                <select name="category_id" class="cf-input">
                    <option value="">— None —</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) old('category_id', $item?->category_id) === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
        </details>

        <details class="cms-sidebar-card" open>
            <summary>Tags</summary>
            <div class="cms-sidebar-card__body">
                <select name="tag_ids[]" class="cf-input" multiple size="6">
                    @foreach ($tags as $tag)
                        <option value="{{ $tag->id }}" @selected(in_array($tag->id, $selectedTagIds, false))>{{ $tag->name }}</option>
                    @endforeach
                </select>
            </div>
        </details>

        <details class="cms-sidebar-card" open>
            <summary>Author</summary>
            <div class="cms-sidebar-card__body">
                <select name="author_uuid" class="cf-input">
                    <option value="">— None —</option>
                    @foreach ($authors as $author)
                        <option value="{{ $author->uuid }}" @selected((string) old('author_uuid', $item?->author_uuid ?? auth()->user()?->uuid) === (string) $author->uuid)>{{ $author->name }}</option>
                    @endforeach
                </select>
            </div>
        </details>

        <details class="cms-sidebar-card" open>
            <summary>Featured Image</summary>
            <div class="cms-sidebar-card__body">
                @include('media::components.media-picker', [
                    'name' => 'featured_image_media_uuid',
                    'value' => old('featured_image_media_uuid', $item?->featured_image_media_uuid),
                    'label' => 'Featured image',
                ])
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
