# Footer Management Design

Date: 2026-08-18
Status: Design Frozen
Scope: Customer Experience-style Footer Management for Commerce Framework

## Summary

Footer Management adds a dedicated admin module at `/admin/settings/footer` for configuring the storefront footer as a composition-driven, config-backed renderer. The footer does not own content. Content remains owned by source modules such as Website Settings, Navigation, CMS, and Marketplace. Footer Management owns only composition, visibility, ordering, layout, and presentation intent.

The admin experience follows the existing Customer Experience pattern:

- configuration workspace on the left
- live preview on the right
- desktop, tablet, and mobile preview modes
- real-time preview updates with debounce
- explicit save and discard actions

The storefront footer and the admin preview must render the exact same component tree, using the same view-model pipeline and the same CSS.

## Goals

- Make the storefront footer fully config-driven.
- Reuse existing source data instead of duplicating content.
- Avoid hardcoded footer links and content.
- Support multiple business types without custom code paths per business.
- Support future footer blocks without requiring a large refactor.
- Preserve a single source of truth across the platform.

## Non-Goals

- Footer Management will not become the owner of store identity, social links, menu content, or CMS page content.
- Footer Management will not auto-save drafts.
- Footer Management will not introduce a full plugin system in v1.
- Footer Management will not store resolved content snapshots in settings.

## Architecture Principles

- Footer Management is a configuration-driven renderer, not a content owner.
- Source of truth remains in source modules.
- Footer configuration stores intent.
- Footer drivers resolve data.
- Footer view model stores presentation.
- Blade only renders.
- Preview is not a mockup. Preview must render the exact same component tree used by the storefront.
- Footer must fail gracefully. A broken section must never break the storefront footer or page render.

## Primary Use Cases

- Merchant enables or disables footer sections.
- Merchant reorders sections through drag and drop.
- Merchant configures presentation options such as layout, spacing, divider style, and theme tokens.
- Merchant chooses the source menu for navigation sections.
- Merchant selects and orders CMS pages for customer service links.
- Merchant previews changes across desktop, tablet, and mobile before saving.

## Supported Footer Sections In V1

- Brand
- Navigation
- CMS
- Social
- Marketplace
- Copyright
- Powered By

The architecture must allow future sections such as Newsletter Signup, Trust Badges, Payment Methods, Mobile App Links, Contact Information, Store Locations, Recently Viewed Products, and Category Shortcuts.

## Domain Model

Footer Management is a dedicated settings feature backed by a single JSON configuration object stored in a dedicated `footer` settings group.

### Config Ownership

Footer config stores only:

- enabled or disabled state
- ordering
- section instances
- per-section rendering options
- layout and presentation tokens
- visibility intent

Footer config does not store:

- store name, logo, social URLs, or store description
- menu link snapshots
- CMS page title snapshots
- resolved localized labels
- computed storefront HTML

### Config Shape

The saved configuration is schema-versioned. Schema versioning exists at both the config level and the view-model level.

```json
{
  "schema_version": 1,
  "enabled": true,
  "layout": {
    "columns": 4,
    "color_scheme": "default",
    "surface": "footer",
    "variant": "default",
    "divider_style": "solid",
    "padding": "lg",
    "spacing": "md"
  },
  "sections": [
    {
      "id": "brand-primary",
      "type": "brand",
      "enabled": true,
      "visibility": {
        "guest": true,
        "authenticated": true
      },
      "settings": {
        "show_logo": true,
        "show_store_name": true,
        "show_description": true
      }
    },
    {
      "id": "quick-links",
      "type": "navigation",
      "enabled": true,
      "visibility": {
        "guest": true,
        "authenticated": true
      },
      "settings": {
        "source": "main",
        "max_links": 6,
        "visibility_mode": "footer_enabled_only"
      }
    },
    {
      "id": "help-pages",
      "type": "cms",
      "enabled": true,
      "visibility": {
        "guest": true,
        "authenticated": true
      },
      "settings": {
        "page_ids": [
          1,
          2,
          3
        ]
      }
    },
    {
      "id": "social-links",
      "type": "social",
      "enabled": true,
      "visibility": {
        "guest": true,
        "authenticated": true
      },
      "settings": {}
    }
  ]
}
```

### Config Rules

- `schema_version` is required and starts at `1`.
- `enabled` controls the entire footer.
- `sections` array order is the render order.
- each section must have a stable unique `id`.
- `type` determines which driver builds the section.
- multiple sections may share the same `type` only when that driver supports multiple instances; section `id` must remain unique.
- `settings` is driver-specific and validated by section type.
- `visibility` stores audience or feature intent, not resolved runtime output.
- theme-related values are stored as supported tokens, not raw colors.
- section `id` format requirements:
  - lowercase
  - kebab-case (letters, numbers, and `-`)
  - unique within the config

## Composition Pipeline

The footer runtime uses a driver-based composition flow:

```text
FooterConfig
    ->
FooterSectionManager
    ->
FooterSectionDriver instances
    ->
FooterViewModelBuilder
    ->
FooterViewModel
    ->
Storefront Footer Component
```

### Runtime Responsibilities

- `FooterConfigService` loads, merges, validates, and persists configuration.
- `FooterSectionManager` resolves and executes registered section drivers.
- each `FooterSectionDriver` resolves source data for its section instance.
- `FooterViewModelBuilder` converts built sections and layout into one normalized versioned view model.
- the shared storefront footer Blade component renders the final view model.

### View Model Shape

The exact PHP structure can evolve, but it must remain schema-versioned and presentation-focused.

```php
[
    'schema_version' => 1,
    'layout' => [
        'columns' => 4,
        'color_scheme' => 'default',
        'surface' => 'footer',
        'variant' => 'default',
        'divider_style' => 'solid',
        'padding' => 'lg',
        'spacing' => 'md',
    ],
    'sections' => [
        [
            'id' => 'quick-links',
            'type' => 'navigation',
            'title_key' => 'footer.navigation',
            'items' => [],
            'meta' => [
                'icon' => 'links',
                'count' => 0,
            ],
        ],
    ],
]
```

### View Model Rules

- the view model must never contain unresolved source dependencies
- labels should be represented by translation keys or stable presentation fields
- `meta` is reserved for non-breaking future display metadata
- copyright placeholders such as `{year}` and `{store_name}` are resolved before render
- empty or null sections are removed before render

## Driver Architecture

Footer uses typed section drivers rather than a single large resolver.

### Driver Contract

```php
interface FooterSectionDriver
{
    public function build(FooterSectionConfig $config): ?FooterSection;

    /**
     * Whether this section type can appear multiple times in a single footer config.
     * - Drivers with `false` must be validated to appear at most once (per config).
     */
    public function supportsMultiple(): bool;
}
```

Drivers return `null` when:

- the section is disabled
- required source data is unavailable
- the referenced feature or module is not enabled
- the section would otherwise render empty

### supportsMultiple Validation

Validation uses `supportsMultiple()` on the driver:

- If `supportsMultiple() === false`, at most one section instance of that section type may exist in a given footer config.
- If `supportsMultiple() === true`, multiple section instances are allowed as long as their `id` values are unique.

### V1 Drivers

- `BrandSectionDriver`
- `NavigationSectionDriver`
- `CmsSectionDriver`
- `SocialSectionDriver`
- `MarketplaceSectionDriver`
- `CopyrightSectionDriver`
- `PoweredBySectionDriver`

### Driver Registration

Driver registration must not use hardcoded switch logic. Drivers are registered through configuration or explicit registration.

Preferred shape:

```php
return [
    'drivers' => [
        'brand' => BrandSectionDriver::class,
        'navigation' => NavigationSectionDriver::class,
        'cms' => CmsSectionDriver::class,
        'social' => SocialSectionDriver::class,
        'marketplace' => MarketplaceSectionDriver::class,
        'copyright' => CopyrightSectionDriver::class,
        'powered_by' => PoweredBySectionDriver::class,
    ],
];
```

Required behavior:

- unknown driver types are ignored safely
- missing driver classes do not break the footer
- drivers can be extended in the future without changing the manager contract

## Source Ownership Rules

### Brand

Owned by Website Settings or Store Settings. Footer only controls display toggles such as showing logo, store name, and description.

### Navigation

Owned by Navigation. Footer may select a source menu identifier and limit how links are filtered and counted.

### CMS

Owned by CMS. Footer may choose which pages to show and their order.

### Social

Owned by Website Settings. Footer does not edit URLs. Empty sources are hidden automatically.

### Marketplace

Owned by Marketplace features. The section is shown only when marketplace capability is available.

### Copyright

Owned by Footer config as presentation text only. Placeholder resolution happens in the view-model layer.

### Powered By

Visibility depends on commercial plan rules:

- free plan: required
- paid plan: optional
- enterprise: hidden

These rules must be enforced server-side, not only by UI state.

## Admin UX

The admin page lives at `/admin/settings/footer` and follows the Customer Experience workspace pattern.

### Layout

- left panel: footer settings and section editor
- right panel: live preview
- preview device switcher: desktop, tablet, mobile
- explicit save and discard actions
- visible dirty-state indicator for unsaved changes

### Editor Structure

1. global settings
2. section list
3. section detail panel

### Global Settings

- footer enabled
- columns
- theme tokens
- divider style
- padding
- spacing
- powered-by summary and effective state

### Section List

The section list is instance-based, not type-based.

Requirements:

- drag-and-drop ordering
- enable and disable toggle per section
- status badges such as `Visible`, `Empty`, `No Sources`, or `Module Disabled`
- `Add Section` action available from day one
- section creation flow: add section -> choose template from `FooterSectionRegistry` -> create instance

### Section Detail Panel

Section detail UI is driver-owned. The main footer editor should not contain a large hardcoded form matrix for every section type.

Examples:

- `BrandSectionDriver` supplies its editor fields
- `NavigationSectionDriver` supplies source and max-links fields
- `CmsSectionDriver` supplies selected page ordering controls
- `SocialSectionDriver` supplies informational source status and link to Website Settings

### Navigation UX

- show selected source
- show public link count
- show warnings when zero public links are available

### CMS UX

- selected pages displayed as an ordered list
- drag-and-drop ordering within selected pages
- no plain unordered multiselect as the primary reordering experience

### Social UX

Social is read-only within Footer Management.

The panel should show:

- which social sources are available
- which are missing
- a clear indication that the data is managed in Website Settings
- a link or action to open the relevant Website Settings screen

## Preview Architecture

Preview must be stateless and must never write to persistent storage.

### Preview Endpoint

`POST /admin/settings/footer/preview`

Input:

```json
{
  "config": {}
}
```

Behavior:

- validate and normalize transient config
- build the view model through the same runtime pipeline used by storefront
- render the same storefront footer component
- return preview response with a stable contract:
  - `html`: rendered footer HTML
  - `meta`: preview metadata counts and hidden reasons (frontend must not guess)

Example response:

```json
{
  "html": "<rendered footer html>",
  "meta": {
    "total_sections": 6,
    "visible_sections": 4,
    "hidden_sections": 2,
    "hidden_reasons": [
      {
        "section_id": "help-pages",
        "reason": "empty_cms_selection"
      }
    ]
  }
}
```

Forbidden behavior:

- saving draft settings automatically
- writing temporary settings to the database
- mutating live storefront configuration during preview

### Preview Refresh Rules

- changes are debounced, typically 300 to 500 milliseconds
- drag refresh happens after drop completes
- toggle refresh happens after interaction completes
- preview should not request a rebuild on every keystroke without debounce

### Preview Metadata Bar

The preview surface should expose state summary such as:

- total section instances
- visible section count
- hidden section count
- reasons hidden, such as empty source data or missing modules

Preview metadata helps merchants understand why a configured block may not be visible.

## Storefront Rendering

The storefront must render through the same shared component tree used in admin preview.

Shared requirements:

- same view model pipeline
- same Blade component
- same CSS

Rendering requirements:

- responsive grid on desktop
- stacked layout on mobile
- accessibility-compliant landmarks and links
- localization support
- theme token application

Blade must not:

- query the database
- resolve settings
- fetch CMS data
- implement fallback source logic

Blade only renders the provided view model.

## Failure Handling

Footer is non-critical UI and must fail gracefully.

### Section Failure Rule

If a single driver throws or fails, the entire footer must not fail.

Required runtime behavior:

- isolate section build failures
- skip failed sections
- log structured failure events
- continue rendering the rest of the footer

Example log key:

```text
footer.section.cms.failed
```

This rule applies to both storefront render and admin preview generation.

## Config Integrity Rules

Footer config must be resilient to malformed entries.

Required runtime behavior:
- a malformed section object must never invalidate the entire footer configuration
- invalid or malformed sections are skipped and do not break rendering
- skip events should be logged with a structured reason

Example rule:
- `{"id":"bad","type":"unknown"}` => skip the section and continue rendering the rest

## Validation

Validation happens both on save and on preview requests.

Rules include:

- schema_version must be supported
- layout tokens must be allowed values
- section ids must be unique
- section types must be strings
- driver-owned settings must conform to their schema
- navigation source must refer to an allowed source
- CMS page identifiers must exist and be selectable
- visibility rules must be structurally valid
- powered-by restrictions must follow plan rules

Unknown section types do not make the whole config invalid for rendering. They are safely ignored at runtime, but the admin editor should surface unsupported types when practical.

## Technical Structure

The exact file layout may adapt to existing project conventions, but the following boundaries should be preserved.

```text
modules/Settings/
  routes/web.php
  src/Http/Controllers/Admin/FooterController.php
  src/Http/Requests/UpdateFooterRequest.php
  src/Services/FooterConfigService.php
  src/Services/FooterSectionManager.php
  src/Services/FooterViewModelBuilder.php
  src/Footer/Contracts/FooterSectionDriver.php
  src/Footer/Registry/FooterSectionRegistry.php
  src/Footer/Drivers/BrandSectionDriver.php
  src/Footer/Drivers/NavigationSectionDriver.php
  src/Footer/Drivers/CmsSectionDriver.php
  src/Footer/Drivers/SocialSectionDriver.php
  src/Footer/Drivers/MarketplaceSectionDriver.php
  src/Footer/Drivers/CopyrightSectionDriver.php
  src/Footer/Drivers/PoweredBySectionDriver.php
  resources/views/admin/footer/index.blade.php
  resources/views/admin/footer/_preview-shell.blade.php
resources/js/admin/footer-settings.js
resources/views/components/storefront/layout/partials/site-footer.blade.php
```

The current storefront footer partial may be retained as the shared render entry point, but it must become view-model driven.

## FooterSectionRegistry

The editor template source and backend driver wiring should use the same registry.

Registry responsibilities:
- provide the available block templates for each `type` (for the UI “Add Section” flow)
- provide default settings per template (`default_settings`)
- define multiplicity via `supportsMultiple()` (or equivalent metadata)

Example registry shape:

```php
return [
  'brand' => [
    'label_key' => 'footer.section.brand',
    'template_id' => 'brand',
    'supports_multiple' => false,
    'default_settings' => [
      'show_logo' => true,
      'show_store_name' => true,
      'show_description' => true,
    ],
  ],
  'navigation' => [
    'label_key' => 'footer.section.navigation',
    'template_id' => 'navigation',
    'supports_multiple' => true,
    'default_settings' => [
      'source' => 'main',
      'max_links' => 6,
      'visibility_mode' => 'footer_enabled_only',
    ],
  ],
];
```

## Save Model

- save is explicit
- discard is explicit
- no auto-save
- unsaved changes are visible

This matches the layout-sensitive nature of footer composition and gives merchants control over when changes are published.

## Default Configuration

V1 should seed a sensible default footer configuration so merchants are not forced into an empty first-run experience.

Suggested default instances:

- one brand section
- one navigation section
- one CMS section
- one social section
- one copyright section
- one powered-by section

Marketplace section may be omitted from defaults when the feature is unavailable, or included and auto-hidden if product strategy prefers consistency.

## Testing Strategy

### Unit Tests

- footer config defaults and merge behavior
- schema_version handling
- driver registration and lookup
- each driver build contract
- view-model builder output normalization
- placeholder resolution
- graceful failure behavior

### Feature Tests

- admin page loads with defaults
- admin page persists valid configuration
- invalid configuration is rejected
- preview endpoint is stateless
- preview returns shared footer component output
- storefront render respects enabled and disabled sections
- storefront render drops empty sections
- marketplace sections hide when feature is unavailable
- duplicate section types work through distinct ids
- powered-by rules match plan tier

### Parity Tests

Add targeted tests that prove preview and storefront use the same render path. These tests should fail if preview starts diverging into a separate mockup implementation.

## Migration Strategy

- store `schema_version` in config from v1
- store `schema_version` in the view model from v1
- tolerate unknown fields in stored config where possible
- provide a future migration path from schema_version N to N+1 within the config service or a dedicated migrator

The presence of versioning from the first release is required because future footer blocks and layout capabilities will likely evolve the schema.

## Open Implementation Notes

- follow the existing Customer Experience route, controller, request, Blade, and JS patterns where they fit
- prefer sharing current storefront footer markup rather than introducing a second footer Blade tree for preview
- keep section-driver contracts small and stable
- keep editor extensibility real without building a full plugin runtime in v1

## Final Decisions

- Footer is renderer-only.
- Source modules remain the single source of truth.
- Config stores intent only.
- Config is schema-versioned (`schema_version`).
- View model is schema-versioned (`schema_version`).
- Sections use `id + type`.
- Unique section ids are required.
- Driver registration is configuration-driven, not switch-based.
- Unknown section types are ignored safely.
- Empty sections are auto-dropped.
- Preview is stateless.
- Preview and storefront share the same component tree.
- Blade is render-only.
- Footer failures must degrade gracefully at section level.
