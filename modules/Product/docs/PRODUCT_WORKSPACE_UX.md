# Product Workspace — UX Architecture

> Modern product management for a headless commerce framework.  
> UI-only scaffold. Backend integration is a separate phase.

---

## 1. UX Philosophy

The Product Workspace treats **product management as editing a living catalog object**, not filling out database forms.

| Principle | Application |
|-----------|-------------|
| **User mental model first** | Users think: *"This product has options."* Not: *"This product has child records."* |
| **One workspace, one save** | Product, media, variants, SEO, and organization persist together. No nested submit buttons. |
| **Variants are the commerce unit** | SKU, barcode, price, cost, weight, and stock visibility live on variants — never duplicated on the product. |
| **Progressive disclosure** | Tabs isolate concerns. The Variants tab is deep; General is shallow. |
| **Spreadsheet-grade editing** | Variant grid supports inline edit, multi-select, and bulk actions — like Linear or Shopify admin. |
| **Inventory is read-only here** | Stock is displayed for context; adjustments happen in the Inventory module. |
| **Content-first, quiet chrome** | Large whitespace, 20–28px radii, semantic tokens, minimal borders — per `DESIGN.md`. |
| **Framework components, not pages** | Every surface is a reusable primitive composable across admin, API docs, and future mobile shells. |

We optimize for **operators** (merchants, catalog managers, marketplace sellers) who manage hundreds of SKUs — not for developers reading schema diagrams.

---

## 2. Mental Model

### Old model (wrong)

```
Product (type: simple | variable)
├── SKU, Price, Stock        ← on product for simple
├── Variants (separate form) ← only for variable
└── Inventory (separate table)
```

Users must understand ORM concepts before they can sell a T-shirt.

### New model (correct)

```
Product (catalog shell)
├── Name, description, brand, categories, visibility
├── Media (product-level gallery)
└── Variants[] (always ≥ 1)
      ├── Options → Color=Red, Size=M
      ├── SKU, Barcode, Price, Cost, Weight
      └── Stock summary (read-only link → Inventory)
```

- **Single-variant product** = one default variant. No "type" selector.
- **Multi-variant product** = same product, more variants generated from options.
- **No product type** — complexity emerges from variant count, not a dropdown.

---

## 3. Page Layout

```
┌─────────────────────────────────────────────────────────────────────────┐
│  Breadcrumb: Catalog › Products › {Name}                                │
├─────────────────────────────────────────────────────────────────────────┤
│  PRODUCT HEADER                                                         │
│  ┌──────────────────────────────────────┐  [Draft]  Publish · Archive │
│  │  Product name (editable inline)       │                               │
│  │  slug · last saved 2m ago             │                               │
│  └──────────────────────────────────────┘                               │
├─────────────────────────────────────────────────────────────────────────┤
│  TABS: General | Media | Variants | SEO | Organization | Advanced       │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │  TAB PANEL (single responsibility)                               │   │
│  │                                                                  │   │
│  │  [Tab-specific content — see §4]                                 │   │
│  │                                                                  │   │
│  └─────────────────────────────────────────────────────────────────┘   │
│                                                                         │
├─────────────────────────────────────────────────────────────────────────┤
│  STICKY SAVE BAR                                                        │
│  Unsaved changes · Discard          [ Save product ]                    │
└─────────────────────────────────────────────────────────────────────────┘
```

### Why this layout

| Zone | Purpose |
|------|---------|
| **Header** | Identity + lifecycle actions (publish, archive). Always visible context. |
| **Tabs** | Cognitive load reduction — each tab answers one question. |
| **Panel** | Generous padding (`--radius-store-lg`), no sidebar clutter. |
| **Save bar** | Single authoritative save affordance; dirty-state feedback. |

---

## 4. Tab Responsibilities

### General

**Question:** *What is this product?*

| Field | Why here |
|-------|----------|
| Name | Product identity |
| Slug | URL / API handle |
| Brand | Merchandising |
| Categories | Navigation & filters |
| Collections | Curated groups (manual merchandising) |
| Description | Rich text story |
| Visibility | Who can see it |
| Status | Draft / published / archived |
| Publish date | Scheduled go-live |

**Excluded:** SKU, price, stock — those are variant concerns.

### Media

**Question:** *How does this product look?*

- Drag-and-drop upload (images, video, documents)
- Reorder via drag
- Assign media to specific variants
- Product-level default gallery

### Variants

**Question:** *What can customers buy, and at what price?*

The Variant Builder (see §5). Heart of the workspace.

### SEO

**Question:** *How do search engines see this?*

Meta title, description, keywords, canonical, OG image. Isolated so operators don't mix SEO with pricing.

### Organization

**Question:** *How do we classify this internally?*

Tags, seller (marketplace), attribute set, internal notes. Operational metadata — not customer-facing options.

### Advanced

**Question:** *Power-user settings.*

UUID, external IDs, custom meta JSON, webhooks hints, API preview link. Hidden from daily workflow.

---

## 5. Variant Workflow

```
Step 1: Choose options          Step 2: Enter values
┌─────────────────────┐         ┌─────────────────────┐
│ + Color             │         │ Color               │
│ + Size              │    →    │ [Red] [Blue] [Black]│
│ + Custom attribute  │         │ Size                │
└─────────────────────┘         │ [S] [M] [L]         │
                                └─────────────────────┘
              ↓
Step 3: Generate matrix         Step 4: Variant grid
┌─────────────────────┐         ┌──────────────────────────────────┐
│ 3 × 3 = 9 variants  │    →    │ □ Image │ Name │ SKU │ Price │…│
│ [ Generate ]        │         │ □  🖼   │ Red/S│ ... │  590  │  │
└─────────────────────┘         └──────────────────────────────────┘
```

### Attribute linkage (automatic)

When matrix generates `Red / M`:

```json
{
  "name": "Red / M",
  "options": { "color": "Red", "size": "M" }
}
```

No manual attribute assignment. Options → values → variants → attributes.

### SKU generation

Configurable pattern (tenant-level setting, future):

| Pattern | Example output |
|---------|----------------|
| `{PRODUCT}-{COLOR}-{SIZE}` | `TEE-RED-M` |
| `{PRODUCT}-{INDEX}` | `TEE-001` |
| Random | `SKU-A7X9K2` |

Users can override any cell inline. Auto-generation runs on matrix create and bulk regenerate.

### Barcode

Auto-generated (EAN-13 placeholder in UI). Editable inline. Unique per variant.

### Stock display

| Column | Source | Editable here? |
|--------|--------|----------------|
| On hand | Inventory service | No — click → Inventory module |
| Reserved | Inventory service | No |
| Available | Computed | No |
| Incoming | Purchase orders (future) | No |

---

## 6. Save Workflow

```
User clicks "Save product"
        │
        ▼
Client serializes workspace state
  ├── product fields (General, SEO, Organization, Advanced)
  ├── media_uuids[] + variant_media map
  └── variants[] (full grid snapshot)
        │
        ▼
Single PUT/POST to product endpoint
        │
        ▼
Backend transaction (future):
  1. Upsert product
  2. Sync media attachments
  3. Upsert variants (create / update / soft-delete removed)
  4. Link variant option attributes
  5. Sync SEO
  6. Emit ProductUpdated event
        │
        ▼
UI: clear dirty state, toast success
```

**Rules:**

- No `POST /variants` mid-edit.
- No separate inventory writes from this page.
- Discard resets client state to last server snapshot.

---

## 7. Responsive Behavior

| Breakpoint | Layout |
|------------|--------|
| **Desktop (≥1024px)** | Full tab bar. Variant grid = horizontal spreadsheet with sticky header row. Bulk toolbar above grid. |
| **Tablet (768–1023px)** | Tabs scroll horizontally. Grid hides low-priority columns (cost, weight); expandable row detail. |
| **Mobile (<768px)** | Tabs become segmented control or bottom sheet picker. Variants render as **accordion cards** (`VariantCard`). Bulk actions in bottom action sheet. |

Touch targets ≥ 44px. Inline cells become full-width inputs inside expanded cards on mobile.

---

## 8. Reusable Components

```
components/workspace/
├── workspace.blade.php           ProductWorkspace — root shell + form
├── header.blade.php            ProductHeader — title, status, actions
├── tabs.blade.php              ProductTabs — tab navigation
├── save-bar.blade.php          Sticky save / discard
├── general-form.blade.php      ProductGeneralForm
├── media-manager.blade.php     ProductMediaManager
├── seo-form.blade.php          ProductSeoForm
├── organization-form.blade.php ProductOrganizationForm
├── advanced-form.blade.php     ProductAdvancedForm
└── variants/
    ├── builder.blade.php           VariantBuilder — orchestrates steps 1–4
    ├── option-selector.blade.php   VariantOptionSelector
    ├── matrix-generator.blade.php  VariantMatrixGenerator
    ├── grid.blade.php              VariantGrid (desktop)
    ├── card.blade.php              VariantCard (mobile)
    ├── inline-cell.blade.php       VariantInlineCell
    ├── bulk-toolbar.blade.php      VariantBulkToolbar
    └── stock-summary.blade.php     VariantStockSummary (read-only)
```

### Composition example

```blade
<x-product::workspace.workspace :product="$product" :mode="$mode">
    <x-slot:header>...</x-slot:header>
    <x-product::workspace.tabs :tabs="$tabs">
        <x-slot:general><x-product::workspace.general-form /></x-slot:general>
        <x-slot:variants><x-product::workspace.variants.builder /></x-slot:variants>
    </x-product::workspace.tabs>
</x-product::workspace.workspace>
```

Components accept **data props only** — no page-specific `@if` logic inside primitives.

---

## 9. State Management

Client-side store: `ProductWorkspaceState` (`resources/js/admin/product-workspace/state.js`).

```javascript
{
  mode: 'create' | 'edit',
  dirty: false,
  activeTab: 'general',
  product: { name, slug, description, brandUuid, categoryIds, ... },
  media: { product: [], variantMap: {} },
  options: [{ id, name, values: [] }],
  variants: [{ id, name, sku, barcode, price, cost, comparePrice, weight, status, options, stock, selected }],
  skuPattern: '{PRODUCT}-{COLOR}-{SIZE}',
  selection: { variantIds: [] },
}
```

| Module | Responsibility |
|--------|----------------|
| `state.js` | Observable store, dirty tracking, serialize/deserialize |
| `variant-builder.js` | Option CRUD, matrix cartesian product, SKU/barcode generation |
| `variant-grid.js` | Inline edit, selection, bulk actions |
| `tabs.js` | Tab switching, URL hash sync (`#variants`) |
| `product-workspace.js` | Bootstrap, form intercept, save payload assembly |

Initial state hydrated from `<script type="application/json" data-product-workspace-state>`.

On save, state serializes to hidden `workspace_payload` + conventional field names for progressive backend adoption.

---

## 10. Bulk Actions

Available when ≥1 variant selected:

| Action | Behavior |
|--------|----------|
| Set price | Apply value to all selected |
| Set cost | Apply value to all selected |
| Regenerate SKU | Apply pattern to selected |
| Regenerate barcode | New codes for selected |
| Set weight | Apply value to selected |
| Set status | Active / draft / archived |
| Assign image | Pick from media library |
| Delete | Remove selected variants (keep ≥1) |

Toolbar: `VariantBulkToolbar` — hidden when selection empty.

---

## Implementation Status

| Layer | Status |
|-------|--------|
| UX documentation | ✅ This file |
| Blade components | ✅ Scaffolded |
| CSS (`product-workspace.css`) | ✅ Scaffolded |
| JS state + variant builder | ✅ Client-only |
| Backend save transaction | ✅ `ProductWorkspaceSaveService` |
| Variant columns (barcode, cost, weight, status) | ✅ Migration added |
| Collections module | ✅ Catalog module + product pivot |
| API endpoint for workspace save | ✅ `api/v1/admin/products/workspace` |
| SKU pattern tenant setting | ✅ `product.sku_pattern` in settings |
| Product attributes in Organization tab | ✅ `_attributes.blade.php` wired |
| Variant image picker | ✅ Media library dialog in variant grid |
| Auto-create variant option attributes | ✅ `VariantOptionAttributeProvisioner` |
| Deprecate legacy variant routes | ✅ Removed from web routes |
| Storefront collection pages | ✅ `/collections/{slug}` dedicated landing page |
| WooCommerce import/export update | ✅ Uses `ProductWorkspaceSaveService` + variant fields |
| Bulk "Assign image" in toolbar | ✅ Wired in variant builder |
| Media drag-reorder in workspace | ✅ Native drag on image list |
| Remove `product.type` from schema | ✅ Column dropped; derived from variant count |
| Incoming stock on variant grid | ✅ PO module populates incoming from open purchase orders |
| Media filter tabs (image/video/doc) | ✅ Filter + upload all media types in workspace |
| Multi-variant WooCommerce CSV rows | ✅ Parent + variation rows on import/export |
| CLI WooCommerce variable import | ✅ `product:import-woocommerce` groups parent/child rows |
| Storefront brand filter + redirect | ✅ `/brands/{slug}` → shop `?brand=` |
| Collection slider in header | ✅ `collection-slider` component |
| Brand slider in header | ✅ `brand-slider` with logo pills |
| Dedicated collection landing layout | ✅ Hero + filtered grid at `/collections/{slug}` |
| Brand/Category landing pages | ✅ `/brands/{slug}` and `/categories/{slug}` |
| Collection cover image admin | ✅ Create/edit with media picker |
| Catalog SEO (brand/category/collection) | ✅ Admin SEO fields + storefront meta tags |
| Purchase order module | ✅ Admin PO create/receive/cancel + incoming stock query |
| PO permissions | ✅ `inventory.purchase_order.view` / `.manage` |
| Brand filter slug URLs | ✅ Shop filter uses brand slug consistently |
| Brand logos in filter sidebar | ✅ Logo chips with search in filter panel |
| Catalog SEO API | ✅ SEO payload on brand/category/collection API resources |
| Automated collections | ✅ Rule-based sync (`on_sale`, `category_id`, `brand_uuid`, `tag_id`, `price_min`/`price_max`) |
| Automated collections CLI | ✅ `catalog:sync-automated-collections` |
| Category filter sidebar | ✅ Logo chips with search in shop filter panel |
| Public storefront catalog API | ✅ `/api/v1/storefront/catalog/{brands,categories,collections}` |
| Supplier master data | ✅ Admin CRUD + PO supplier dropdown |
| PO line cancel | ✅ Cancel remaining incoming quantity per line |
| Supplier edit/delete | ✅ Full supplier CRUD |
| PO supplier filter | ✅ Filter PO list by supplier and status |
| Advanced tab | ✅ Custom meta JSON, API preview, webhook hints |
| Automated collections scheduler | ✅ Hourly `catalog:sync-automated-collections` |
| Automated rule logic | ✅ AND (`all`) / OR (`any`) match modes |
| Product webhook events | ✅ `product.created` / `product.updated` / `product.published` / `product.unpublished` / `product.archived` / `product.deleted` |
| `commerce:production-bootstrap` | ✅ Sync permissions + production scheduler checklist |
| Supplier date-range analytics | ✅ Filter supplier report and export by date range |
| Category match mode | ✅ `category_match` all/any for multi-category rules |
| `iam:sync-permissions` command | ✅ Register module permissions + optional super-admin assign |
| PO CSV export | ✅ Filtered export from purchase orders index |
| PO print / PDF | ✅ Print page + server-side PDF download (`dompdf`) |
| Supplier report page | ✅ PO summary and recent orders per supplier |
| Supplier PO export | ✅ CSV export of supplier purchase order lines |
| Nested automated rule groups | ✅ Combine rule groups with AND/OR (`groups` array) |
| Supplier units chart | ✅ Ordered vs received dual bar chart by month |
| PO analytics units chart | ✅ Ordered vs received dual bar chart on analytics dashboard |
| PO line unit cost | ✅ `unit_cost` on PO lines with value analytics |
| Email PO PDF to supplier | ✅ Send PDF attachment from PO show page |
| PO email notification template | ✅ `purchase_order.supplier` in Notification admin + config fallback |
| Auto-email PO on create | ✅ Optional checkbox + `INVENTORY_PO_AUTO_EMAIL` default |
| Queued PO email | ✅ `Mail::queue()` by default + `INVENTORY_PO_QUEUE_EMAIL` toggle |
| Dedicated PO email queue | ✅ `notifications` queue via `INVENTORY_PO_QUEUE_NAME` |
| Failed PO email admin | ✅ Retry/dismiss failed supplier email jobs |
| Multi-currency PO | ✅ PO `currency` field + base-currency analytics conversion |
| Dynamic rule groups UI | ✅ Add/remove up to 5 rule groups in collection form |
| Visual rule builder | ✅ Drag-and-drop condition cards with add/remove palette |
| Multi-brand/tag/category rules | ✅ Multi-select `brand_uuids` / `tag_ids` / `category_ids` |
| Brand/tag match mode | ✅ `brand_match` / `tag_match` all/any for multi-brand/tag rules |
| PO analytics dashboard | ✅ Global PO summary, charts, top suppliers, CSV export |
| Supplier PO chart | ✅ Monthly PO bar chart on supplier report page |
| Legacy product form cleanup | ✅ Removed unused `_form*.blade.php` files |

---

## References

- `DESIGN.md` — visual language
- `resources/views/components/admin/*` — shared admin primitives
- `modules/Inventory/routes/web.php` — stock management destination
