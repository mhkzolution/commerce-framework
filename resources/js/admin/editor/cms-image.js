import Image from '@tiptap/extension-image';

export const WIDTH_PRESETS = [100, 75, 50, 33];
export const WIDTH_MIN = 25;
export const WIDTH_MAX = 100;

export function clampPercent(value) {
    const n = Math.round(Number(value));
    return Math.min(WIDTH_MAX, Math.max(WIDTH_MIN, Number.isFinite(n) ? n : 100));
}

export function snapPercent(value) {
    const clamped = clampPercent(value);
    for (const preset of WIDTH_PRESETS) {
        if (Math.abs(clamped - preset) <= 3) {
            return preset;
        }
    }
    return clamped;
}

export function percentAttr(value) {
    return `${clampPercent(String(value).replace('%', ''))}%`;
}

export const CmsImage = Image.extend({
    name: 'image',
    inline: true,
    group: 'inline',
    addAttributes() {
        return {
            ...this.parent?.(),
            width: {
                default: '100%',
                parseHTML: (el) => percentAttr(el.getAttribute('width') || '100%'),
                renderHTML: (attrs) => ({ width: percentAttr(attrs.width || '100%') }),
            },
            'data-align': {
                default: null,
                parseHTML: (el) => {
                    const align = el.getAttribute('data-align');
                    return ['center', 'right'].includes(align) ? align : null;
                },
                renderHTML: (attrs) => (attrs['data-align'] ? { 'data-align': attrs['data-align'] } : {}),
            },
        };
    },
});
