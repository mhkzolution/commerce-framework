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
import { mountSlashMenu } from './slash-menu';
import { createMediaProvider } from './media-provider';

function imageFilesFrom(list) {
    return [...(list || [])].filter((file) => file.type.startsWith('image/'));
}

export function mountEditor(root) {
    const input = root.querySelector('[data-cms-editor-input]');
    const canvas = root.querySelector('[data-cms-editor-canvas]');
    const toolbarEl = root.querySelector('[data-cms-editor-toolbar]');
    const inspectorEl = root.querySelector('[data-cms-editor-inspector]');
    const pickerUrl = root.dataset.mediaPickerUrl;
    const uploadUrl = root.dataset.mediaUploadUrl;
    const placeholder = canvas?.dataset.placeholder || 'Write, or type / for commands';

    if (!input || !canvas) {
        return;
    }

    const media = createMediaProvider(pickerUrl, uploadUrl);
    let editor;

    const insertUploadedImages = async (files, position) => {
        for (const file of files) {
            const item = await media.uploadImage(file);
            const src = item?.preview_url || item?.url;
            if (!src) {
                continue;
            }
            const chain = editor.chain().focus();
            if (typeof position === 'number') {
                chain.insertContentAt(position, { type: 'image', attrs: { src, alt: file.name || '' } });
            } else {
                chain.setImage({ src, alt: file.name || '' });
            }
            chain.run();
        }
    };

    const insertPickedImage = async () => {
        const item = await media.pickImage();
        if (!item) {
            return;
        }
        editor.chain().focus().setImage({
            src: item.preview_url || item.url,
            alt: item.filename || '',
        }).run();
    };

    editor = new Editor({
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
                'data-placeholder': placeholder,
            },
            handlePaste: (_view, event) => {
                const files = imageFilesFrom(event.clipboardData?.files);
                if (files.length === 0) {
                    return false;
                }
                event.preventDefault();
                insertUploadedImages(files);
                return true;
            },
            handleDrop: (view, event, _slice, moved) => {
                if (moved) {
                    return false;
                }
                const files = imageFilesFrom(event.dataTransfer?.files);
                if (files.length === 0) {
                    return false;
                }
                event.preventDefault();
                const pos = view.posAtCoords({ left: event.clientX, top: event.clientY });
                insertUploadedImages(files, pos?.pos);
                return true;
            },
        },
        onUpdate: ({ editor: instance }) => {
            input.value = instance.getHTML();
            instance.view.dom.classList.toggle('is-empty', instance.isEmpty);
        },
        onCreate: ({ editor: instance }) => {
            instance.view.dom.classList.toggle('is-empty', instance.isEmpty);
        },
    });

    canvas.addEventListener('dragover', (event) => {
        if (imageFilesFrom(event.dataTransfer?.files).length > 0 || [...(event.dataTransfer?.types || [])].includes('Files')) {
            event.preventDefault();
            canvas.classList.add('is-dragover');
        }
    });
    canvas.addEventListener('dragleave', () => canvas.classList.remove('is-dragover'));
    canvas.addEventListener('drop', () => canvas.classList.remove('is-dragover'));

    const sync = () => {
        input.value = editor.getHTML();
    };

    input.closest('form')?.addEventListener('submit', sync);
    mountToolbar(toolbarEl, editor, media);
    mountInspector(inspectorEl, editor, media);
    mountSlashMenu(editor, { onImage: insertPickedImage });
}
