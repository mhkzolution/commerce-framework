import { WIDTH_PRESETS, percentAttr } from './cms-image';

function field(label, value, onInput) {
    const wrap = document.createElement('label');
    wrap.className = 'cms-editor-inspector__field';
    wrap.innerHTML = `<span>${label}</span>`;
    const input = document.createElement('input');
    input.className = 'cf-input';
    input.value = value || '';
    input.addEventListener('input', () => onInput(input.value));
    wrap.append(input);
    return wrap;
}

function action(label, onClick, selected) {
    const el = document.createElement('button');
    el.type = 'button';
    el.className = 'cms-editor-toolbar__btn';
    el.textContent = label;
    if (typeof selected === 'boolean') {
        el.classList.toggle('is-active', selected);
        el.setAttribute('aria-pressed', String(selected));
    }
    el.addEventListener('click', onClick);
    return el;
}

function controls(label, items) {
    const wrap = document.createElement('div');
    wrap.className = 'cms-editor-inspector__controls';
    const heading = document.createElement('span');
    heading.textContent = label;
    const row = document.createElement('div');
    row.className = 'cms-editor-inspector__controls-row';
    row.append(...items);
    wrap.append(heading, row);
    return wrap;
}

export function mountInspector(container, editor, media) {
    if (!container) {
        return;
    }

    const hide = () => {
        container.hidden = true;
        container.innerHTML = '';
        container.classList.remove('cms-editor-inspector');
    };

    const show = (title) => {
        container.hidden = false;
        container.innerHTML = '';
        container.classList.add('cms-editor-inspector');
        const heading = document.createElement('h3');
        heading.className = 'cms-editor-inspector__title';
        heading.textContent = title;
        container.append(heading);
    };

    const render = () => {
        if (editor.isActive('image')) {
            show('Image');
            const attrs = editor.getAttributes('image');
            container.append(field('Alt text', attrs.alt, (value) => {
                editor.chain().focus().updateAttributes('image', { alt: value }).run();
            }));
            const width = Number(percentAttr(attrs.width || '100%').replace('%', ''));
            container.append(controls('Width', WIDTH_PRESETS.map((preset) => action(
                `${preset}%`,
                () => editor.chain().focus().updateAttributes('image', { width: `${preset}%` }).run(),
                width === preset,
            ))));
            const currentWidth = document.createElement('span');
            currentWidth.textContent = `${width}%`;
            container.append(currentWidth);

            const align = ['center', 'right'].includes(attrs['data-align']) ? attrs['data-align'] : 'left';
            container.append(controls('Align', [
                action(
                    'Left',
                    () => editor.chain().focus().updateAttributes('image', { 'data-align': null }).run(),
                    align === 'left',
                ),
                action(
                    'Center',
                    () => editor.chain().focus().updateAttributes('image', { 'data-align': 'center' }).run(),
                    align === 'center',
                ),
                action(
                    'Right',
                    () => editor.chain().focus().updateAttributes('image', { 'data-align': 'right' }).run(),
                    align === 'right',
                ),
            ]));
            container.append(action('Replace image', async () => {
                const item = await media.pickImage();
                if (!item) {
                    return;
                }
                editor.chain().focus().updateAttributes('image', {
                    src: item.preview_url || item.url,
                    alt: item.original_filename || item.filename || attrs.alt || '',
                }).run();
            }));
            return;
        }

        if (editor.isActive('link')) {
            show('Link');
            const href = editor.getAttributes('link').href || '';
            container.append(field('URL', href, (value) => {
                if (value === '') {
                    editor.chain().focus().extendMarkRange('link').unsetLink().run();
                    return;
                }
                editor.chain().focus().extendMarkRange('link').setLink({ href: value }).run();
            }));
            container.append(action('Remove link', () => {
                editor.chain().focus().extendMarkRange('link').unsetLink().run();
            }));
            return;
        }

        if (editor.isActive('table')) {
            show('Table');
            container.append(action('Add row', () => editor.chain().focus().addRowAfter().run()));
            container.append(action('Add column', () => editor.chain().focus().addColumnAfter().run()));
            container.append(action('Delete table', () => editor.chain().focus().deleteTable().run()));
            return;
        }

        if (editor.isActive('iframe') || editor.isActive('youtube') || editor.isActive('embed')) {
            show('Embed');
            return;
        }

        hide();
    };

    editor.on('selectionUpdate', render);
    editor.on('transaction', render);
    render();
}
