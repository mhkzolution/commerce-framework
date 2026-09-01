<div id="admin-command-palette" class="admin-command-palette" aria-hidden="true" role="dialog" aria-label="{{ __('admin::shell.command_palette') }}">
    <div class="admin-command-panel">
        <div class="border-b border-border p-3">
            <input
                id="admin-command-input"
                type="search"
                placeholder="{{ __('admin::shell.command_search') }}"
                class="cf-input"
                autocomplete="off"
                data-search-url="{{ route('admin.search') }}"
            >
        </div>
        <div id="admin-command-results" class="max-h-80 overflow-y-auto p-2">
            <p id="admin-command-empty" hidden class="px-3 py-6 text-center text-sm text-muted">{{ __('admin::shell.command_no_matches') }}</p>
            <div id="admin-command-static">
            @foreach ($adminCommandItems ?? [] as $item)
                @if (!empty($item['url']))
                    <button
                        type="button"
                        data-command-item
                        data-command-href="{{ $item['url'] }}"
                        data-command-keywords="{{ $item['keywords'] ?? strtolower($item['label']) }}"
                        class="cf-command-item flex w-full items-center justify-between rounded-md px-3 py-2 text-left text-sm"
                    >
                        <span>{{ $item['label'] }}</span>
                        @if (!empty($item['group']))
                            <span class="text-xs text-muted">{{ $item['group'] }}</span>
                        @endif
                    </button>
                @endif
            @endforeach
            @if (Route::has('admin.design-system'))
                <button
                    type="button"
                    data-command-item
                    data-command-href="{{ route('admin.design-system') }}"
                    data-command-keywords="design system shell components"
                    class="cf-command-item flex w-full items-center justify-between rounded-md px-3 py-2 text-left text-sm"
                >
                    <span>{{ __('admin::shell.design_system') }}</span>
                    <span class="text-xs text-muted">{{ __('admin::shell.shell') }}</span>
                </button>
            @endif
            </div>
            <div id="admin-command-dynamic"></div>
        </div>
        <div class="border-t border-border px-3 py-2 text-xs text-muted">
            <span>{{ __('admin::shell.command_navigate') }}</span>
            <span class="mx-2">·</span>
            <span>{{ __('admin::shell.command_open') }}</span>
            <span class="mx-2">·</span>
            <span>{{ __('admin::shell.command_close') }}</span>
        </div>
    </div>
</div>
