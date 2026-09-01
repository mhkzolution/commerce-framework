@props([
    'id' => 'pos-dialog',
    'title' => '',
    'fullscreen' => false,
])

<div
    id="{{ $id }}"
    class="pos-dialog-backdrop"
    data-pos-dialog
    hidden
    role="dialog"
    aria-modal="true"
    aria-labelledby="{{ $id }}-title"
>
    <div class="pos-dialog {{ $fullscreen ? 'pos-dialog--fullscreen' : '' }}">
        <header class="pos-dialog__header">
            <h2 class="pos-dialog__title" id="{{ $id }}-title">{{ $title }}</h2>
            <button type="button" class="pos-btn pos-btn--secondary pos-btn--icon" data-pos-dialog-close aria-label="Close">
                <kbd>Esc</kbd>
            </button>
        </header>

        <div class="pos-dialog__body">
            {{ $slot }}
        </div>

        @isset($footer)
            <footer class="pos-dialog__footer">
                {{ $footer }}
            </footer>
        @endisset
    </div>
</div>
