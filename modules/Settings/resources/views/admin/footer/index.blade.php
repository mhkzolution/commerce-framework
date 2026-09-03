@extends('layouts.admin')

@section('title', 'Footer Settings')

@php
    $i18n = [
        'dirty' => 'Unsaved changes',
        'saved' => 'All changes saved',
        'save' => 'Save',
        'discard' => 'Discard',
        'footer_enabled' => 'Enable footer',
        'layout' => 'Layout',
        'sections' => 'Sections',
        'details' => 'Section details',
        'preview' => 'Live preview',
        'preview_hint' => 'Preview uses the shared footer renderer and updates after a short debounce.',
        'add_section' => 'Add Section',
        'add_section_hint' => 'Choose a section template to add a new block instance.',
        'device_desktop' => 'Desktop',
        'device_tablet' => 'Tablet',
        'device_mobile' => 'Mobile',
        'status_visible' => 'Visible',
        'status_empty' => 'Empty',
        'status_no_sources' => 'No Sources',
        'status_module_disabled' => 'Module Disabled',
        'status_hidden' => 'Hidden',
        'select_section' => 'Select a section to edit its settings.',
        'section_id' => 'Section ID',
        'section_type' => 'Section type',
        'guest_visibility' => 'Show for guests',
        'auth_visibility' => 'Show for signed-in customers',
        'brand_group' => 'Brand content',
        'nav_group' => 'Navigation source',
        'nav_source' => 'Source',
        'nav_max_links' => 'Max links',
        'nav_visibility_mode' => 'Visibility mode',
        'nav_count' => 'Preview links',
        'cms_group' => 'CMS pages',
        'cms_available' => 'Available pages',
        'cms_selected' => 'Selected pages',
        'cms_empty' => 'No CMS pages selected yet.',
        'social_group' => 'Social sources',
        'social_read_only' => 'Social links are managed in Website Settings.',
        'copyright_group' => 'Copyright text',
        'copyright_template' => 'Template',
        'driver_note_marketplace' => 'Marketplace visibility is resolved by the backend driver and module availability.',
        'driver_note_powered_by' => 'Powered By visibility is resolved by plan rules on the backend.',
        'preview_meta_total' => 'Total',
        'preview_meta_visible' => 'Visible',
        'preview_meta_hidden' => 'Hidden',
        'move_up' => 'Move up',
        'move_down' => 'Move down',
        'remove' => 'Remove',
        'edit' => 'Edit',
        'enabled' => 'Enabled',
        'disabled' => 'Disabled',
        'show_logo' => 'Show logo',
        'show_store_name' => 'Show store name',
        'show_description' => 'Show description',
        'open_navigation' => 'Open navigation settings',
        'open_site_identity' => 'Open Website Settings',
        'open_cms' => 'Open CMS pages',
        'visibility_footer_only' => 'Footer enabled only',
        'visibility_public_only' => 'Public only',
        'visibility_all' => 'All links',
    ];
@endphp

@section('page')
    <x-admin.page
        title="Footer Settings"
        description="Configure the storefront footer composition and save changes when ready."
        wide
    >
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => __('admin::nav.groups.website')],
                ['label' => 'Footer Settings', 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <form
            method="POST"
            action="{{ route('admin.settings.footer.update') }}"
            class="footer-settings"
            data-footer-settings
            data-footer-config='@json($config)'
            data-footer-preview='@json($preview)'
            data-footer-editor='@json($editor)'
            data-footer-i18n='@json($i18n)'
        >
            @csrf
            @method('PUT')

            <input type="hidden" name="config" value="{{ e(json_encode($config, JSON_UNESCAPED_UNICODE)) }}" data-footer-config-input>

            <div class="footer-settings__toolbar">
                <div>
                    <p class="footer-settings__eyebrow">Footer Configuration</p>
                    <div class="footer-settings__status">
                        <span class="footer-settings__dirty-indicator" data-footer-dirty-indicator>All changes saved</span>
                    </div>
                </div>
                <div class="footer-settings__actions">
                    <x-admin.button variant="secondary" type="button" data-footer-discard disabled>Discard</x-admin.button>
                    <x-admin.button variant="primary" type="submit" data-footer-save disabled>Save</x-admin.button>
                </div>
            </div>

            <div class="footer-settings__split">
                <section class="footer-settings__config" aria-label="Footer configuration">
                    <div class="footer-settings__stack">
                        <div class="footer-settings__panel">
                            <div class="footer-settings__panel-header">
                                <div>
                                    <h2 class="footer-settings__panel-title">Layout</h2>
                                    <p class="footer-settings__panel-copy">Global footer composition and spacing tokens.</p>
                                </div>
                            </div>

                            <label class="footer-settings__toggle">
                                <input type="checkbox" data-footer-path="enabled" @checked($config['enabled'] ?? true)>
                                <span>Enable footer</span>
                            </label>

                            <div class="footer-settings__grid">
                                <div class="footer-settings__field">
                                    <label class="footer-settings__label" for="footer-columns">Columns</label>
                                    <select id="footer-columns" class="cf-input" data-footer-path="layout.columns" data-footer-type="integer">
                                        @foreach ([2, 3, 4, 5] as $columns)
                                            <option value="{{ $columns }}" @selected(($config['layout']['columns'] ?? 4) === $columns)>{{ $columns }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="footer-settings__field">
                                    <label class="footer-settings__label" for="footer-color-scheme">Color scheme</label>
                                    <input id="footer-color-scheme" class="cf-input" type="text" value="{{ $config['layout']['color_scheme'] ?? 'default' }}" data-footer-path="layout.color_scheme">
                                </div>

                                <div class="footer-settings__field">
                                    <label class="footer-settings__label" for="footer-surface">Surface</label>
                                    <input id="footer-surface" class="cf-input" type="text" value="{{ $config['layout']['surface'] ?? 'footer' }}" data-footer-path="layout.surface">
                                </div>

                                <div class="footer-settings__field">
                                    <label class="footer-settings__label" for="footer-variant">Variant</label>
                                    <input id="footer-variant" class="cf-input" type="text" value="{{ $config['layout']['variant'] ?? 'default' }}" data-footer-path="layout.variant">
                                </div>

                                <div class="footer-settings__field">
                                    <label class="footer-settings__label" for="footer-divider-style">Divider style</label>
                                    <input id="footer-divider-style" class="cf-input" type="text" value="{{ $config['layout']['divider_style'] ?? 'solid' }}" data-footer-path="layout.divider_style">
                                </div>

                                <div class="footer-settings__field">
                                    <label class="footer-settings__label" for="footer-padding">Padding</label>
                                    <select id="footer-padding" class="cf-input" data-footer-path="layout.padding">
                                        @foreach (['sm', 'md', 'lg', 'xl'] as $token)
                                            <option value="{{ $token }}" @selected(($config['layout']['padding'] ?? 'lg') === $token)>{{ strtoupper($token) }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="footer-settings__field">
                                    <label class="footer-settings__label" for="footer-spacing">Spacing</label>
                                    <select id="footer-spacing" class="cf-input" data-footer-path="layout.spacing">
                                        @foreach (['sm', 'md', 'lg', 'xl'] as $token)
                                            <option value="{{ $token }}" @selected(($config['layout']['spacing'] ?? 'md') === $token)>{{ strtoupper($token) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="footer-settings__panel">
                            <div class="footer-settings__panel-header">
                                <div>
                                    <h2 class="footer-settings__panel-title">Sections</h2>
                                    <p class="footer-settings__panel-copy">Reorder blocks, enable or disable them, and add new section instances.</p>
                                </div>
                                <x-admin.button variant="secondary" type="button" data-footer-add-toggle>Add Section</x-admin.button>
                            </div>

                            <div class="footer-settings__library" data-footer-library hidden>
                                <p class="footer-settings__hint">Choose a section template to add a new block instance.</p>
                                <div class="footer-settings__library-grid" data-footer-template-list></div>
                            </div>

                            <div class="footer-settings__list" data-footer-section-list></div>
                        </div>

                        <div class="footer-settings__panel">
                            <div class="footer-settings__panel-header">
                                <div>
                                    <h2 class="footer-settings__panel-title">Section Details</h2>
                                    <p class="footer-settings__panel-copy">Driver-owned controls render here for the selected section.</p>
                                </div>
                            </div>

                            <div data-footer-detail-panel>
                                <p class="footer-settings__empty">Select a section to edit its settings.</p>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="footer-settings__preview" aria-label="Footer live preview">
                    <div class="footer-settings__panel-header">
                        <div>
                            <h2 class="footer-settings__panel-title">Live Preview</h2>
                            <p class="footer-settings__panel-copy">Preview uses the shared footer renderer and updates after a short debounce.</p>
                        </div>
                    </div>

                    <div class="footer-settings__device-toggle">
                        <button type="button" class="footer-settings__device is-active" data-footer-device="desktop">Desktop</button>
                        <button type="button" class="footer-settings__device" data-footer-device="tablet">Tablet</button>
                        <button type="button" class="footer-settings__device" data-footer-device="mobile">Mobile</button>
                    </div>

                    <div class="footer-settings__preview-meta" data-footer-preview-meta>
                        <span data-footer-preview-total>Total: 0</span>
                        <span data-footer-preview-visible>Visible: 0</span>
                        <span data-footer-preview-hidden>Hidden: 0</span>
                    </div>

                    <div class="footer-device" data-footer-device-frame data-device="desktop">
                        <div class="footer-device__chrome">
                            <span></span><span></span><span></span>
                        </div>
                        <div class="footer-device__screen" data-footer-preview-root>
                            <div class="footer-settings__preview-loading">Loading preview…</div>
                        </div>
                    </div>
                </section>
            </div>
        </form>
    </x-admin.page>
@endsection
