/**
 * Decide whether a product-workspace state notification should rebuild the variant grid.
 * On-hand/SKU typing notifies without bumping uiEpoch; structural edits bump it.
 */
export function applyVariantBuilderNotification(currentEpoch, lastEpoch, renderers = {}) {
    const { renderGrid, renderOptions } = renderers;

    if (currentEpoch === lastEpoch) {
        return { lastEpoch, rebuilt: false };
    }

    renderOptions?.();
    renderGrid?.();

    return { lastEpoch: currentEpoch, rebuilt: true };
}
