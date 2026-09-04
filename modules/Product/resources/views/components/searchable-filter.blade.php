@once
    @push('scripts')
        <script>
            window.initSearchableLists = (scope = document) => {
                scope.querySelectorAll('[data-searchable-list]').forEach((root) => {
                    if (root.dataset.searchableBound === '1') {
                        return;
                    }

                    root.dataset.searchableBound = '1';
                    const input = root.querySelector('[data-searchable-filter]');
                    const items = () => root.querySelectorAll('[data-searchable-item]');

                    input?.addEventListener('input', () => {
                        const query = input.value.trim().toLowerCase();

                        items().forEach((item) => {
                            const text = (item.dataset.searchableText || item.textContent || '').toLowerCase();
                            item.classList.toggle('hidden', query !== '' && !text.includes(query));
                        });
                    });
                });
            };

            document.addEventListener('DOMContentLoaded', () => {
                window.initSearchableLists();
            });
        </script>
    @endpush
@endonce
