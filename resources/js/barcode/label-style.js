const DEFAULTS = {
    label_padding_top: 1,
    label_padding_right: 2,
    label_padding_bottom: 1,
    label_padding_left: 2,
    label_content_gap: 0.2,
    label_owner_font_size: 6,
    label_sku_font_size: 6,
};

/**
 * @param {Record<string, unknown>} settings
 */
export function resolveLabelStyle(settings = {}) {
    return {
        paddingTop: Number(settings.label_padding_top ?? DEFAULTS.label_padding_top),
        paddingRight: Number(settings.label_padding_right ?? DEFAULTS.label_padding_right),
        paddingBottom: Number(settings.label_padding_bottom ?? DEFAULTS.label_padding_bottom),
        paddingLeft: Number(settings.label_padding_left ?? DEFAULTS.label_padding_left),
        contentGap: Number(settings.label_content_gap ?? DEFAULTS.label_content_gap),
        ownerFontSize: Number(settings.label_owner_font_size ?? DEFAULTS.label_owner_font_size),
        skuFontSize: Number(settings.label_sku_font_size ?? DEFAULTS.label_sku_font_size),
    };
}

/**
 * @param {Record<string, unknown>} settings
 * @param {number} scale
 */
export function previewLabelStyleAttribute(settings, scale = 2.5) {
    const style = resolveLabelStyle(settings);
    const mm = (value) => `${(value * scale).toFixed(2)}px`;
    const pt = (value) => `${(value * (96 / 72)).toFixed(2)}px`;

    return [
        `padding:${mm(style.paddingTop)} ${mm(style.paddingRight)} ${mm(style.paddingBottom)} ${mm(style.paddingLeft)}`,
        `gap:${mm(style.contentGap)}`,
        `--bc-owner-font:${pt(style.ownerFontSize)}`,
        `--bc-sku-font:${pt(style.skuFontSize)}`,
    ].join(';');
}

/**
 * @param {Record<string, unknown>} template
 */
export function labelStyleFromTemplate(template) {
    return {
        label_padding_top: template.label_padding_top,
        label_padding_right: template.label_padding_right,
        label_padding_bottom: template.label_padding_bottom,
        label_padding_left: template.label_padding_left,
        label_content_gap: template.label_content_gap,
        label_owner_font_size: template.label_owner_font_size,
        label_sku_font_size: template.label_sku_font_size,
    };
}
