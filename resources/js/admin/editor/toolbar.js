function button(label, title) {
    const el = document.createElement('button');
    el.type = 'button';
    el.className = 'cms-editor-toolbar__btn';
    el.textContent = label;
    el.title = title;
    return el;
}

export function mountToolbar(container, editor, media) {
    if (!container) {
        return;
    }

    container.innerHTML = '';
    container.classList.add('cms-editor-toolbar');

    const heading = document.createElement('select');
    heading.className = 'cms-editor-toolbar__select';
    heading.innerHTML = `
        <option value="paragraph">Paragraph</option>
        <option value="1">Heading 1</option>
        <option value="2">Heading 2</option>
        <option value="3">Heading 3</option>
        <option value="4">Heading 4</option>
        <option value="5">Heading 5</option>
        <option value="6">Heading 6</option>
    `;
    heading.addEventListener('change', () => {
        const value = heading.value;
        if (value === 'paragraph') {
            editor.chain().focus().setParagraph().run();
            return;
        }
        editor.chain().focus().toggleHeading({ level: Number(value) }).run();
    });
    container.append(heading);

    const actions = [
        ['B', 'Bold', () => editor.chain().focus().toggleBold().run()],
        ['I', 'Italic', () => editor.chain().focus().toggleItalic().run()],
        ['Link', 'Link', () => {
            const previous = editor.getAttributes('link').href || '';
            const url = window.prompt('Link URL', previous);
            if (url === null) {
                return;
            }
            if (url === '') {
                editor.chain().focus().extendMarkRange('link').unsetLink().run();
                return;
            }
            editor.chain().focus().extendMarkRange('link').setLink({ href: url }).run();
        }],
        ['Image', 'Image', async () => {
            const item = await media.pickImage();
            if (!item) {
                return;
            }
            editor.chain().focus().setImage({
                src: item.preview_url || item.url,
                alt: item.filename || '',
            }).run();
        }],
        ['• List', 'Bullet list', () => editor.chain().focus().toggleBulletList().run()],
        ['1. List', 'Ordered list', () => editor.chain().focus().toggleOrderedList().run()],
        ['Quote', 'Quote', () => editor.chain().focus().toggleBlockquote().run()],
        ['Code', 'Code block', () => editor.chain().focus().toggleCodeBlock().run()],
        ['Table', 'Table', () => editor.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run()],
    ];

    actions.forEach(([label, title, onClick]) => {
        const el = button(label, title);
        el.addEventListener('click', onClick);
        container.append(el);
    });
}
