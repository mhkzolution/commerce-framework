import { openMediaPicker } from '../media-picker';

export function createMediaProvider(pickerUrl, uploadUrl) {
    const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    return {
        async pickImage() {
            if (!pickerUrl) {
                return null;
            }

            return openMediaPicker({
                url: pickerUrl,
                multiple: false,
                imagesOnly: true,
                title: 'Select image',
            });
        },

        async uploadImage(file) {
            if (!uploadUrl || !file) {
                return null;
            }

            const body = new FormData();
            body.append('file', file);

            const response = await fetch(uploadUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body,
            });

            if (!response.ok) {
                return null;
            }

            const payload = await response.json();
            return payload.data || payload;
        },
    };
}
