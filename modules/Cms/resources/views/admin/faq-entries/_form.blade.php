@php
    $item = $item ?? null;
@endphp

<x-admin.form.section title="{{ __('cms::admin.faq_entry') }}">
    <div class="grid gap-4">
        <div>
            <label class="block text-sm font-medium text-text" for="question">{{ __('cms::admin.question') }}</label>
            <input id="question" name="question" value="{{ old('question', $item?->question) }}" required class="cf-input mt-1">
        </div>
        <div>
            <label class="block text-sm font-medium text-text" for="answer">{{ __('cms::admin.answer') }}</label>
            <textarea id="answer" name="answer" rows="5" required class="cf-input mt-1">{{ old('answer', $item?->answer) }}</textarea>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-text" for="sort_order">{{ __('cms::admin.sort_order') }}</label>
                <input id="sort_order" name="sort_order" type="number" min="0" value="{{ old('sort_order', $item?->sort_order ?? 0) }}" class="cf-input mt-1">
            </div>
            <div class="flex items-end">
                <label class="inline-flex items-center gap-2 text-sm text-text-secondary">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $item?->is_active ?? true)) class="rounded border-border">
                    {{ __('cms::admin.active') }}
                </label>
            </div>
        </div>
    </div>
</x-admin.form.section>
