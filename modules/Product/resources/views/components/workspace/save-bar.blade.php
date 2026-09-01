<div class="cf-product-workspace__save-bar" data-product-workspace-save-bar>
    <div class="cf-product-workspace__save-bar-inner">
        <div class="cf-product-workspace__save-status">
            <span class="cf-product-workspace__save-indicator" data-workspace-dirty-indicator hidden aria-hidden="true"></span>
            <span data-workspace-dirty-label>{{ __('product::workspace.all_changes_saved') }}</span>
        </div>

        <div class="cf-product-workspace__save-actions">
            <button
                type="button"
                class="cf-btn cf-btn--ghost"
                data-workspace-discard
                hidden
            >
                {{ __('product::workspace.discard') }}
            </button>
            <x-admin.button variant="primary" type="submit" data-workspace-save>
                {{ __('product::workspace.save_product') }}
            </x-admin.button>
        </div>
    </div>
</div>
