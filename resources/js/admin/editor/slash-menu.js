const COMMANDS = [
    { keys: ['heading', 'h', 'h2'], label: 'Heading', hint: '/heading', run: (editor) => editor.chain().focus().toggleHeading({ level: 2 }).run() },
    { keys: ['image', 'img'], label: 'Image', hint: '/image', run: null },
    { keys: ['quote', 'blockquote'], label: 'Quote', hint: '/quote', run: (editor) => editor.chain().focus().toggleBlockquote().run() },
    { keys: ['list', 'ul', 'bullet'], label: 'List', hint: '/list', run: (editor) => editor.chain().focus().toggleBulletList().run() },
    { keys: ['table'], label: 'Table', hint: '/table', run: (editor) => editor.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run() },
];

function slashQuery(editor) {
    const { $from } = editor.state.selection;
    if ($from.parent.type.name !== 'paragraph') {
        return null;
    }

    const text = $from.parent.textContent;
    if (!text.startsWith('/')) {
        return null;
    }

    return {
        query: text.slice(1),
        from: $from.start(),
        to: $from.pos,
    };
}

export function mountSlashMenu(editor, { onImage } = {}) {
    const menu = document.createElement('div');
    menu.className = 'cms-slash-menu';
    menu.hidden = true;
    document.body.appendChild(menu);

    let active = 0;
    let matches = [];

    const hide = () => {
        menu.hidden = true;
        matches = [];
        active = 0;
    };

    const execute = async (command) => {
        const range = slashQuery(editor);
        if (range) {
            editor.chain().focus().deleteRange({ from: range.from, to: range.to }).run();
        }
        hide();
        if (command.keys.includes('image')) {
            await onImage?.();
            return;
        }
        command.run?.(editor);
    };

    const render = () => {
        const range = slashQuery(editor);
        if (!range) {
            hide();
            return;
        }

        const needle = range.query.toLowerCase();
        matches = COMMANDS.filter((command) => (
            needle === ''
            || command.keys.some((key) => key.startsWith(needle))
            || command.label.toLowerCase().includes(needle)
        ));

        if (matches.length === 0) {
            hide();
            return;
        }

        active = Math.min(active, matches.length - 1);
        menu.innerHTML = '';
        matches.forEach((command, index) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = `cms-slash-menu__item${index === active ? ' is-active' : ''}`;
            button.innerHTML = `${command.label}<span class="cms-slash-menu__hint">${command.hint}</span>`;
            button.addEventListener('mousedown', (event) => {
                event.preventDefault();
                execute(command);
            });
            menu.append(button);
        });

        const coords = editor.view.coordsAtPos(editor.state.selection.from);
        menu.style.left = `${Math.min(coords.left, window.innerWidth - 280)}px`;
        menu.style.top = `${coords.bottom + 8}px`;
        menu.hidden = false;
    };

    editor.on('update', render);
    editor.on('selectionUpdate', render);
    editor.on('blur', () => {
        window.setTimeout(hide, 120);
    });

    editor.view.dom.addEventListener('keydown', (event) => {
        if (menu.hidden) {
            return;
        }

        if (event.key === 'ArrowDown') {
            event.preventDefault();
            event.stopPropagation();
            active = (active + 1) % matches.length;
            render();
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            event.stopPropagation();
            active = (active - 1 + matches.length) % matches.length;
            render();
        } else if (event.key === 'Enter') {
            event.preventDefault();
            event.stopPropagation();
            if (matches[active]) {
                execute(matches[active]);
            }
        } else if (event.key === 'Escape') {
            event.preventDefault();
            event.stopPropagation();
            hide();
        }
    }, true);
}
