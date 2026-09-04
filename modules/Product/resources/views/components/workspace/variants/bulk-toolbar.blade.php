<div class="cf-variant-bulk-toolbar hidden" data-variant-bulk-toolbar>
    <div class="cf-variant-bulk-toolbar__inner">
        <span class="cf-variant-bulk-toolbar__count">
            <strong data-variant-bulk-count>0</strong> {{ __('product::workspace.bulk_selected') }}
        </span>

        <div class="cf-variant-bulk-toolbar__actions">
            <button type="button" class="cf-btn cf-btn--ghost cf-btn--sm" data-bulk-action="price">{{ __('product::workspace.bulk_set_price') }}</button>
            <button type="button" class="cf-btn cf-btn--ghost cf-btn--sm" data-bulk-action="cost">{{ __('product::workspace.bulk_set_cost') }}</button>
            <button type="button" class="cf-btn cf-btn--ghost cf-btn--sm" data-bulk-action="sku">{{ __('product::workspace.bulk_regenerate_sku') }}</button>
            <button type="button" class="cf-btn cf-btn--ghost cf-btn--sm" data-bulk-action="weight">{{ __('product::workspace.bulk_set_weight') }}</button>
            <button type="button" class="cf-btn cf-btn--ghost cf-btn--sm" data-bulk-action="status">{{ __('product::workspace.bulk_set_status') }}</button>
            <button type="button" class="cf-btn cf-btn--ghost cf-btn--sm" data-bulk-action="image">{{ __('product::workspace.bulk_assign_image') }}</button>
            <button type="button" class="cf-btn cf-btn--danger cf-btn--sm" data-bulk-action="delete">{{ __('product::workspace.bulk_delete') }}</button>
        </div>
    </div>
</div>

<dialog class="cf-variant-bulk-dialog" data-variant-bulk-dialog>
    <div class="cf-variant-bulk-dialog__form">
        <h3 class="cf-variant-bulk-dialog__title" data-bulk-dialog-title>{{ __('product::workspace.bulk_set_price') }}</h3>
        <div class="cf-variant-bulk-dialog__body" data-bulk-dialog-body></div>
        <div class="cf-variant-bulk-dialog__actions">
            <button type="button" class="cf-btn cf-btn--ghost" data-bulk-dialog-cancel>{{ __('product::workspace.cancel') }}</button>
            <button type="button" class="cf-btn cf-btn--primary" data-bulk-dialog-apply>{{ __('product::workspace.apply') }}</button>
        </div>
    </div>
</dialog>
