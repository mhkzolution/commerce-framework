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
    addNodeView() {
        return ({ node, editor, getPos }) => {
            const wrapper = document.createElement('span');
            const image = document.createElement('img');
            const handle = document.createElement('span');
            let currentNode = node;
            let pointerId = null;

            wrapper.className = 'cms-image-node';
            handle.className = 'cms-image-node__handle';
            handle.contentEditable = 'false';
            handle.setAttribute('aria-hidden', 'true');

            wrapper.append(image, handle);

            const render = () => {
                const width = percentAttr(currentNode.attrs.width || '100%');
                const align = currentNode.attrs['data-align'];

                wrapper.setAttribute('width', width);
                wrapper.style.width = width;

                if (align) {
                    wrapper.setAttribute('data-align', align);
                } else {
                    wrapper.removeAttribute('data-align');
                }

                image.src = currentNode.attrs.src;
                image.alt = currentNode.attrs.alt || '';

                if (currentNode.attrs.title) {
                    image.title = currentNode.attrs.title;
                } else {
                    image.removeAttribute('title');
                }
            };

            const stopDragging = () => {
                pointerId = null;
                window.removeEventListener('pointermove', onPointerMove);
                window.removeEventListener('pointerup', onPointerUp);
                window.removeEventListener('pointercancel', onPointerUp);
            };

            const onPointerMove = (event) => {
                if (pointerId !== event.pointerId) {
                    return;
                }

                const contentWidth = editor.view.dom.clientWidth;
                if (!contentWidth) {
                    return;
                }

                const next = snapPercent(
                    ((event.clientX - wrapper.getBoundingClientRect().left) / contentWidth) * 100,
                );
                wrapper.style.width = `${next}%`;
                editor.commands.updateAttributes('image', { width: `${next}%` });
            };

            const onPointerUp = (event) => {
                if (pointerId !== event.pointerId) {
                    return;
                }

                onPointerMove(event);
                stopDragging();
            };

            handle.addEventListener('pointerdown', (event) => {
                event.preventDefault();
                editor.commands.setNodeSelection(getPos());
                pointerId = event.pointerId;
                window.addEventListener('pointermove', onPointerMove);
                window.addEventListener('pointerup', onPointerUp);
                window.addEventListener('pointercancel', onPointerUp);
            });

            render();

            return {
                dom: wrapper,
                update(updatedNode) {
                    if (updatedNode.type !== currentNode.type) {
                        return false;
                    }

                    currentNode = updatedNode;
                    render();
                    return true;
                },
                ignoreMutation: () => true,
                destroy: stopDragging,
            };
        };
    },
});
