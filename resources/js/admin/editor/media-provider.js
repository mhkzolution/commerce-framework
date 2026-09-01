export function createMediaProvider(pickerUrl) {
    return {
        pickImage() {
            if (!pickerUrl) {
                return Promise.resolve(null);
            }

            return new Promise((resolve) => {
                const overlay = document.createElement('div');
                overlay.className = 'cms-editor-media-overlay';
                overlay.innerHTML = `
                    <div class="cms-editor-media-dialog" role="dialog" aria-label="Select media">
                        <div class="cms-editor-media-dialog__header">
                            <strong>Select image</strong>
                            <button type="button" data-cms-media-close>&times;</button>
                        </div>
                        <div class="cms-editor-media-dialog__search">
                            <input type="search" placeholder="Search images..." data-cms-media-search>
                        </div>
                        <div class="cms-editor-media-dialog__grid" data-cms-media-grid></div>
                    </div>
                `;

                const grid = overlay.querySelector('[data-cms-media-grid]');
                const search = overlay.querySelector('[data-cms-media-search]');
                const close = () => {
                    overlay.remove();
                    resolve(null);
                };

                overlay.querySelector('[data-cms-media-close]').addEventListener('click', close);
                overlay.addEventListener('click', (event) => {
                    if (event.target === overlay) {
                        close();
                    }
                });

                const load = async () => {
                    const params = new URLSearchParams({
                        images_only: '1',
                        page: '1',
                        search: search.value || '',
                    });
                    const response = await fetch(`${pickerUrl}?${params.toString()}`, {
                        headers: { Accept: 'application/json' },
                    });
                    const payload = await response.json();
                    grid.innerHTML = '';

                    (payload.data || []).forEach((item) => {
                        const button = document.createElement('button');
                        button.type = 'button';
                        button.className = 'cms-editor-media-tile';
                        const img = document.createElement('img');
                        img.src = item.url || item.preview_url;
                        img.alt = '';
                        const caption = document.createElement('span');
                        caption.textContent = item.filename || '';
                        button.append(img, caption);
                        button.addEventListener('click', () => {
                            overlay.remove();
                            resolve(item);
                        });
                        grid.appendChild(button);
                    });
                };

                search.addEventListener('input', () => {
                    load();
                });

                document.body.appendChild(overlay);
                load();
            });
        },
    };
}
