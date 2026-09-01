import { mountEditor } from './editor/platform';

document.querySelectorAll('[data-cms-editor]').forEach((root) => {
    mountEditor(root);
});
