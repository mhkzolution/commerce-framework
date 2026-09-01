@props([
    'name' => 'content',
    'value' => null,
])

<div
    class="cms-editor"
    data-cms-editor
    data-media-picker-url="{{ route('admin.media.picker') }}"
>
    <div data-cms-editor-toolbar></div>
    <div class="cms-editor-chrome">
        <div class="cms-editor-canvas" data-cms-editor-canvas></div>
        <aside data-cms-editor-inspector></aside>
    </div>
    <textarea name="{{ $name }}" hidden data-cms-editor-input>{{ old($name, $value) }}</textarea>
</div>

@vite(['resources/css/admin/cms-editor.css', 'resources/js/admin/cms-editor.js'])
