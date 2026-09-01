import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import Link from '@tiptap/extension-link';
import Image from '@tiptap/extension-image';
import Table from '@tiptap/extension-table';
import TableRow from '@tiptap/extension-table-row';
import TableHeader from '@tiptap/extension-table-header';
import TableCell from '@tiptap/extension-table-cell';
import { mountToolbar } from './toolbar';
import { mountInspector } from './inspector';
import { createMediaProvider } from './media-provider';

export function mountEditor(root) {
    const input = root.querySelector('[data-cms-editor-input]');
    const canvas = root.querySelector('[data-cms-editor-canvas]');
    const toolbarEl = root.querySelector('[data-cms-editor-toolbar]');
    const inspectorEl = root.querySelector('[data-cms-editor-inspector]');
    const pickerUrl = root.dataset.mediaPickerUrl;

    if (!input || !canvas) {
        return;
    }

    const media = createMediaProvider(pickerUrl);
    const editor = new Editor({
        element: canvas,
        content: input.value || '',
        extensions: [
            StarterKit.configure({
                heading: { levels: [1, 2, 3, 4, 5, 6] },
            }),
            Link.configure({
                openOnClick: false,
                autolink: true,
                defaultProtocol: 'https',
            }),
            Image.configure({ allowBase64: false }),
            Table.configure({ resizable: false }),
            TableRow,
            TableHeader,
            TableCell,
        ],
        editorProps: {
            attributes: {
                class: 'cms-editor-prose',
            },
        },
        onUpdate: ({ editor: instance }) => {
            input.value = instance.getHTML();
        },
    });

    const sync = () => {
        input.value = editor.getHTML();
    };

    input.closest('form')?.addEventListener('submit', sync);
    mountToolbar(toolbarEl, editor, media);
    mountInspector(inspectorEl, editor, media);
}
