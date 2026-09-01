@php
    $item = $item ?? null;
    $selectedTagIds = old('tag_ids', $item?->tags?->pluck('id')->all() ?? []);
@endphp

<div class="cms-workspace">
    <div class="cms-workspace__main">
        <x-admin.form.section title="Post">
            <label class="block text-sm font-medium text-text">Title</label>
            <input name="title" value="{{ old('title', $item?->title) }}" class="cf-input mt-1" required>

            <label class="mt-4 block text-sm font-medium text-text">Slug</label>
            <input name="slug" value="{{ old('slug', $item?->slug) }}" class="cf-input mt-1" placeholder="auto-generated if empty">

            <label class="mt-4 block text-sm font-medium text-text">Excerpt</label>
            <textarea name="excerpt" class="cf-input mt-1" rows="2">{{ old('excerpt', $item?->excerpt) }}</textarea>

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

            <label class="mt-4 block text-sm font-medium text-text">Published at</label>
            <input name="published_at" type="datetime-local" value="{{ old('published_at', optional($item?->published_at)->format('Y-m-d\TH:i')) }}" class="cf-input mt-1">

            <label class="mt-4 inline-flex items-center gap-2 text-sm text-text-secondary">
                <input type="hidden" name="is_featured" value="0">
                <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $item?->is_featured))>
                Featured post
            </label>
        </x-admin.form.section>

        <x-admin.form.section title="Organization">
            <label class="block text-sm font-medium text-text">Category</label>
            <select name="category_id" class="cf-input mt-1">
                <option value="">— None —</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) old('category_id', $item?->category_id) === (string) $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>

            <label class="mt-4 block text-sm font-medium text-text">Tags</label>
            <select name="tag_ids[]" class="cf-input mt-1" multiple size="6">
                @foreach ($tags as $tag)
                    <option value="{{ $tag->id }}" @selected(in_array($tag->id, $selectedTagIds, false))>{{ $tag->name }}</option>
                @endforeach
            </select>

            <label class="mt-4 block text-sm font-medium text-text">Author</label>
            <select name="author_uuid" class="cf-input mt-1">
                <option value="">— None —</option>
                @foreach ($authors as $author)
                    <option value="{{ $author->uuid }}" @selected((string) old('author_uuid', $item?->author_uuid ?? auth()->user()?->uuid) === (string) $author->uuid)>{{ $author->name }}</option>
                @endforeach
            </select>
        </x-admin.form.section>

        <x-admin.form.section title="Featured image">
            @include('media::components.media-picker', [
                'name' => 'featured_image_media_uuid',
                'value' => old('featured_image_media_uuid', $item?->featured_image_media_uuid),
                'label' => 'Featured image',
            ])
        </x-admin.form.section>

        <details class="cms-seo-panel rounded-xl border border-border bg-card p-5">
            <summary>SEO</summary>
            <div class="mt-4">
                @include('product::admin.products._seo', ['seo' => $seo ?? null])
            </div>
        </details>
    </aside>
</div>
