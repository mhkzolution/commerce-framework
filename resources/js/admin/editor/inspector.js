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

function action(label, onClick) {
    const el = document.createElement('button');
    el.type = 'button';
    el.className = 'cms-editor-toolbar__btn';
    el.textContent = label;
    el.addEventListener('click', onClick);
    return el;
}

export function mountInspector(container, editor, media) {
    if (!container) {
        return;
    }

    const render = () => {
        container.innerHTML = '';
        container.classList.add('cms-editor-inspector');

        const heading = document.createElement('h3');
        heading.className = 'cms-editor-inspector__title';

        if (editor.isActive('image')) {
            heading.textContent = 'Image';
            container.append(heading);
            const attrs = editor.getAttributes('image');
            container.append(field('Alt text', attrs.alt, (value) => {
                editor.chain().focus().updateAttributes('image', { alt: value }).run();
            }));
            container.append(action('Replace image', async () => {
                const item = await media.pickImage();
                if (!item) {
                    return;
                }
                editor.chain().focus().updateAttributes('image', {
                    src: item.preview_url || item.url,
                    alt: item.filename || attrs.alt || '',
                }).run();
            }));
            return;
        }

        if (editor.isActive('link')) {
            heading.textContent = 'Link';
            container.append(heading);
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
            heading.textContent = 'Table';
            container.append(heading);
            container.append(action('Add row', () => editor.chain().focus().addRowAfter().run()));
            container.append(action('Add column', () => editor.chain().focus().addColumnAfter().run()));
            container.append(action('Delete table', () => editor.chain().focus().deleteTable().run()));
            return;
        }

        heading.textContent = 'Inspector';
        container.append(heading);
        const empty = document.createElement('p');
        empty.className = 'cms-editor-inspector__empty';
        empty.textContent = 'Select an image, link, or table to edit it.';
        container.append(empty);
    };

    editor.on('selectionUpdate', render);
    editor.on('transaction', render);
    render();
}
