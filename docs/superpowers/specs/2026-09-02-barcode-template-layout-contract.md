# Barcode v1.4 — Template / Label Layout Contract

**Date:** 2026-09-02  
**Status:** Locked (ADR-006)  
**Decision:** Preset Catalog + Archive Template Schema  
**Applies to:** `feat/barcode-management-v1` when Phase 1 starts  
**Does not start:** extraction

```text
Barcode templates define label layout only.

Templates must not contain business logic,
database access,
or runtime dependencies on Product, Media,
Marketplace, Inventory, or Scanner.
```

v1.4 is a **label management** center (pick a preset, toggle fields, print). It is not a sticker CAD tool and not a printer driver.

```text
Barcode = Product Consumer          (ADR-003)
Barcode = Print Queue Owner         (ADR-004)
Barcode ≠ Layout Designer           (this ADR)
Barcode ≠ Product/POS/Inventory Expansion
```

---

## Decision (locked)

```text
Template = preset-based configuration

Preset catalog (code)
    ↓
barcode_templates row (archive columns + preset_code + show_*)
    ↓
BarcodeLayoutCalculator
    ↓
ResolvedBarcodeLayout DTO
    ↓
Renderer  →  HTML | PDF
```

- `preset_code` is the source of truth for paper size and grid millimetres. `paper_size` is **derived** from it and is never a second source of truth.
- `BarcodeLayoutCalculator` builds the resolved layout for the renderer from the **job.settings snapshot**, not from the live template row and not from a later catalog edit.
- Users cannot set paper dimensions freely in v1.4.
- Do **not** rewrite the table to `paper_width_mm` / `paper_height_mm`.
- Do **not** add a `barcode_presets` table.
- Do **not** start a CAD / label-designer workstream.

Rejected alternatives:

| Approach | Why rejected for v1.4 |
|----------|------------------------|
| B — millimetres on the row | Turns Center into a designer: mm validation, collision checks, printable-area math, preset→custom migration, a large extra test surface. None of that is in ADR-001–005. |
| C — two tables (presets + templates) | Purest split, extra extraction work, not needed if the catalog lives in config. |

---

## Layout vs payload (ADR-004)

```text
LabelPayload (frozen on the print job)
  +
ResolvedBarcodeLayout (from job.settings snapshot only)
        ↓
BarcodePrintService → HTML | PDF
```

Not:

```text
Template → query Product → render
Template row → render
Preset catalog → render a historical job
```

Template never queries Product. Reprint uses the stored payload plus the **job.settings snapshot** written at job create. Do not re-read `barcode_templates`. Do not re-read `config('barcode.presets')` to replace snapshotted millimetres. An edited template or a later catalog change must not rewrite print history.

Renderer input is never an Eloquent `BarcodeTemplate`, `Product`, or `Media` model.

---

## Persistence: keep archive columns

Copy the five archive template migrations from `84e905c`. Do **not** rename columns on day one.

Conceptual names from the ADR discussion map onto archive names. The archive names are what land in MySQL.

| Concept | Column / source (archive) |
|---------|---------------------------|
| Identity | `id`, `uuid`, `name` |
| Preset (new) | `preset_code` |
| Paper identity | `paper_size` (`a4` \| `thermal` only) |
| Paper mm | `config('barcode.paper_sizes.{paper_size}')` — **not** a table column |
| Grid | `rows`, `columns` |
| Label mm | `label_width`, `label_height` |
| Margins | `margin_top`, `margin_right`, `margin_bottom`, `margin_left` |
| Gaps | `spacing_horizontal`, `spacing_vertical` |
| Type | `label_orientation` |
| Padding | `label_padding_top/right/bottom/left`, `label_content_gap` |
| Font SKU / name | `label_sku_font_size` (name uses this size in v1.4; no extra name-font column) |
| Font owner | `label_owner_font_size` |
| Visibility (new) | `show_name`, `show_sku`, `show_owner`, `show_barcode` |
| Flags | `is_favorite`, `is_default` |

Do **not** add `font_size_name` / `font_size_sku` / `font_size_owner` as new column names. Do **not** collapse padding into one column.

Forbidden on the template row: SQL, Product/Seller rules, barcode generation strategy, Inventory/POS/Scanner keys, HTML, CSS, JS, Blade, canvas JSON.

`tenant_id` stays if the archive migration has it. Do not invent a second tenant model in v1.4.

---

## Preset catalog (code, not a table)

Lives in `modules/Barcode/config/barcode.php` (adapt after copy). Do **not** put presets in `packages/commerce/core/config/barcode.php` — that file is the value-generator catalog (ADR kernel bind), not layout.

Codes use **underscores**:

```text
a4_40
a4_24
a4_65
thermal_50x30
```

`paper_size` on the row stays the archive keys `a4` and `thermal`. `thermal_50x30` is a **preset_code**, not a paper-size key.

```text
paper_size is derived from preset_code.

When preset_code is set or changed,
paper_size MUST be rewritten from the preset catalog.

Manual writes to paper_size are forbidden.
```

A row `preset_code = a4_40` with `paper_size = thermal` is invalid. The service layer never persists that pair: it always copies `paper_size` (and the other frozen millimetre fields) from `config('barcode.presets.{preset_code}')`. Form requests must not accept `paper_size` as user input.

`BarcodeLayoutCalculator` still looks up ISO paper millimetres with the snapshotted `paper_size` key only when `paper_width_mm` / `paper_height_mm` are missing from a snapshot. v1.4 always writes those two keys at job create so reprints do not depend on a later `paper_sizes` config edit.

Config shape (adapt; keep archive `width_mm` / `height_mm` keys):

```php
'paper_sizes' => [
    'a4' => [
        'label' => 'A4',
        'width_mm' => 210,
        'height_mm' => 297,
    ],
    'thermal' => [
        'label' => 'Thermal 50×30',
        'width_mm' => 50,
        'height_mm' => 30,
    ],
],

'presets' => [
    'a4_40' => [ /* paper_size, rows, columns, label mm, margins, gaps */ ],
    'a4_24' => [ /* ... */ ],
    'a4_65' => [ /* ... */ ],
    'thermal_50x30' => [ /* ... */ ],
],
```

Drop from the v1.4 catalog (do not show in the Center, do not seed):

```text
Thermal 40×30
paper_sizes.a5
paper_sizes.custom
```

Admin create = pick a `preset_code` (copies grid / paper / label mm / margins / gaps). Users do not type millimetres on a blank form.

---

## Edit: `preset_code` is mutable (option A)

`preset_code` is **not** immutable. The edit form has the same preset dropdown as create.

When `preset_code` changes on update, overwrite every frozen field from the **current** catalog for that code:

```text
paper_size
rows
columns
label_width
label_height
margin_*
spacing_*
```

Keep current overrides unless the client also sent new values:

```text
name
show_name, show_sku, show_owner, show_barcode
label_*_font_size, label_padding_*, label_content_gap, label_orientation
is_favorite, is_default
```

Do **not** require clone-to-change-preset. Archive already lets a template’s paper/grid change in place; v1.4 keeps that shape and routes it through `preset_code`.

Client-supplied `paper_size`, `rows`, `columns`, `label_width`, `label_height`, `margin_*`, or `spacing_*` on create/update are ignored. Those fields exist on the row only as copies of the catalog.

Changing a live template never mutates existing `barcode_print_jobs.settings`.

---

## Four v1.4 presets (locked millimetres)

Replace the archive seed (`A4 4×10`, `A4 3×8`, `Thermal 50×30`, `Thermal 40×30`).

Grid is `columns × rows`. A4 40 labels is **4 columns × 10 rows**, not the reverse.

```text
a4_40: columns = 4, rows = 10
labels_per_page = rows * columns   # 40, never 4*10-as-rows-first
```

Numbers are chosen so `columns * label_width + (columns-1) * gap_x + margin_left + margin_right` equals paper width (same for height). Pagination tests stay deterministic.

| `preset_code` | Name (default `name`) | `paper_size` | Grid | Label mm | Gap X/Y | Margin T / R / B / L |
|---------------|------------------------|--------------|------|----------|---------|----------------------|
| `a4_40` | A4 40 Labels | `a4` | 4 × 10 | 48.5 × 25.4 | 2 / 2 | 12.5 / 5 / 12.5 / 5 |
| `a4_24` | A4 24 Labels | `a4` | 3 × 8 | 63.5 × 33.9 | 2 / 2 | 5.9 / 7.75 / 5.9 / 7.75 |
| `a4_65` | A4 65 Labels | `a4` | 5 × 13 | 38.1 × 21.2 | 0 / 0 | 10.7 / 9.75 / 10.7 / 9.75 |
| `thermal_50x30` | Thermal 50×30 | `thermal` | 1 × 1 | 50 × 30 | 0 / 0 | 0 / 0 / 0 / 0 |

Default template: `a4_40` (`is_default = true`). `a4_65` uses zero gaps so 13 × 21.2 mm fits on 297 mm (2 mm vertical gaps would overflow).

Overrides allowed after create (do **not** re-copy millimetres from a later catalog edit unless the user changes `preset_code` on that row):

```text
name, is_favorite, is_default
show_name, show_sku, show_owner, show_barcode
label_*_font_size, label_padding_*, label_content_gap, label_orientation
```

Frozen from `preset_code` (form must not accept writes; service overwrites from catalog when `preset_code` is set or changed):

```text
paper_size, rows, columns
label_width, label_height
margin_*, spacing_*
```

---

## Catalog versioning

The preset catalog in `modules/Barcode/config/barcode.php` may change in a later release (for example `a4_40` margins). That change is **not** retroactive.

```text
Changes to the preset catalog affect
new template selections only
(create, and edit when preset_code changes).

Existing template rows keep their stored millimetres
until the user changes preset_code on that row.

Existing jobs render from
their stored settings snapshot.
```

```text
snapshot wins
```

The calculator/renderer must not merge live catalog millimetres over a job snapshot. New jobs copy from the template row as it exists at print time (that row was itself filled from the catalog at last preset selection).

---

## Job settings snapshot

Written once when the print job is created. After insert, **never update** `settings` (same immutability as `payload` in ADR-004).

Archive already stores JSON `barcode_print_jobs.settings` plus denormalized `paper_size` / `template_name` columns. v1.4 keeps those columns as copies of the snapshot; the renderer still reads **JSON settings**, not the live template.

Required keys in `job.settings` (archive `toSettingsArray()` names, plus the new fields):

```text
preset_code
paper_size                 # derived from preset_code at snapshot time
paper_width_mm             # copied from paper_sizes[paper_size] at snapshot time
paper_height_mm

rows
columns
label_width
label_height

margin_top
margin_right
margin_bottom
margin_left

spacing_horizontal
spacing_vertical

show_name
show_sku
show_owner
show_barcode

label_padding_top
label_padding_right
label_padding_bottom
label_padding_left
label_content_gap

label_sku_font_size
label_owner_font_size
label_orientation

name                       # template display name at print time
id                         # template id at print time (reference only; do not load the row to render)
```

```text
Renderer MUST read layout from job.settings snapshot.
Renderer MUST NOT read barcode_templates.
Renderer MUST NOT replace snapshotted millimetres from the preset catalog.
```

`BarcodeLayoutCalculator` maps those snapshot keys into `ResolvedBarcodeLayout` (`label_width` → `label_width_mm`, etc.). Missing `show_*` keys default true.

---

## New columns (Phase 1 migration, not in archive)

After copying the five archive migrations, add:

```text
modules/Barcode/database/migrations/2026_09_02_120000_add_preset_and_visibility_to_barcode_templates_table.php
```

```text
preset_code     string   not null, default a4_40
show_name       boolean  not null, default true
show_sku        boolean  not null, default true
show_owner      boolean  not null, default true
show_barcode    boolean  not null, default true
```

Allowed `preset_code` values are exactly the four keys above. Reject anything else at Form Request, not in the renderer.

Renderer honors `show_*` from **job.settings** (copied from the template at print). Missing keys default true (archive always drew owner + barcode text).

---

## Resolved layout DTO (renderer contract)

`paper_width_mm` exists **here**, not on `barcode_templates`.

New file:

```text
modules/Barcode/src/DTO/ResolvedBarcodeLayout.php
```

Built by `BarcodeLayoutCalculator` from **job.settings only**. Shape:

```text
paper_width_mm
paper_height_mm
rows                  # e.g. 10 for a4_40
columns               # e.g. 4 for a4_40
labels_per_page       # rows * columns  (40 for a4_40, not 4×10 swapped)
label_width_mm
label_height_mm
margin_top_mm
margin_right_mm
margin_bottom_mm
margin_left_mm
spacing_horizontal_mm
spacing_vertical_mm
cells                 # list of {left, top, width, height} in mm
show_name
show_sku
show_owner
show_barcode
```

Print service contract:

```text
Input:   ResolvedBarcodeLayout + LabelPayload (job.payload.lines)
Output:  HTML | PDF
```

Not responsible for: Windows print, USB, Bluetooth, CUPS, printer drivers. Those are a separate workstream.

`BarcodeLayoutCalculator` / the DTO / print Blade must not import Product, Media, Marketplace, Inventory, or Scanner types.

---

## Forbidden in v1.4

```text
paper_width_mm / paper_height_mm on barcode_templates
custom_canvas_json
custom_css / custom HTML / custom JS
template PHP / Blade upload
Mustache / Liquid stored on the row
template_blocks / template_elements
drag/drop designer
a5 / custom paper as first-class Center choices
Thermal 40×30 seed
Printer drivers (Windows / USB / Bluetooth)
Calling Product, Media, Marketplace, Inventory, Scanner from template or renderer code
```

Those are the start of a CAD / Label Designer. If that product exists later, it is not v1.4.

---

## v1.5 Scanner (why this shape)

Scanner does not need a Product-aware template:

```text
Scan Product
  → Create LabelPayload
  → Create print job (snapshot template → job.settings)
  → ResolvedBarcodeLayout from that snapshot
  → Print
```

---

## Adapt after copy

```text
modules/Barcode/src/Services/BarcodeTemplateService.php      # four presets; preset_code derives frozen fields
modules/Barcode/config/barcode.php                           # paper_sizes a4 + thermal; presets catalog
modules/Barcode/src/Models/BarcodeTemplate.php               # preset_code + show_*; toSettingsArray includes snapshot keys
modules/Barcode/resources/views/admin/templates/_form.blade.php  # preset dropdown on create and edit; no free mm
modules/Barcode/src/Http/Requests/*Template*                 # reject / ignore paper_size and frozen mm writes
modules/Barcode/src/Services/BarcodeLayoutCalculator.php     # DTO from job.settings only
modules/Barcode/src/Services/BarcodePrintService.php         # honor show_* from job.settings; never load template row
modules/Barcode/src/Services/BarcodePrintJobService.php      # write full settings snapshot at create
```

Create (not in archive):

```text
modules/Barcode/src/DTO/ResolvedBarcodeLayout.php
modules/Barcode/database/migrations/2026_09_02_120000_add_preset_and_visibility_to_barcode_templates_table.php
tests/Unit/Barcode/BarcodeLayoutCalculatorTest.php
```

---

## Tests (ADR-005)

- Seeded templates are exactly `a4_40`, `a4_24`, `a4_65`, `thermal_50x30`; default is `a4_40`
- No row with archive name `Thermal 40×30`
- `a4_40` calculator: `labels_per_page === 40`; paper 210×297
- `a4_65` calculator: `labels_per_page === 65`; paper 210×297; cells stay inside paper
- Print with `show_owner = false` omits owner text
- Template service / calculator / renderer tests do not boot Product
- Create/update with client `paper_size = thermal` and `preset_code = a4_40` persists `paper_size = a4`
- Edit `preset_code` from `a4_40` to `a4_24` overwrites rows/columns/label mm/margins/gaps from the catalog
- After print, change live template millimetres (via another preset) and/or catalog config; reprint HTML still matches the original snapshot
- Form / service rejects writes to `label_width` (and other frozen millimetre fields) except via `preset_code` change
- No test and no renderer path may require `paper_width_mm` on the **table** (it belongs on the DTO and on `job.settings`)

---

## First-review checklist

Green:

```text
Four preset_code values only; default a4_40
paper_size always derived from preset_code
Edit dropdown can change preset_code and overwrites frozen mm
paper_sizes = a4 + thermal
Template row has no paper_width_mm
Renderer reads job.settings only; never barcode_templates
Reprint unchanged after catalog or live-template edit
show_* honored from job.settings
Print outputs HTML and PDF only
```

Red:

```text
User can type paper width on create
preset_code = a4_40 persisted with paper_size = thermal
Template or Blade queries Product
Reprint loads BarcodeTemplate for layout
Reprint re-reads config('barcode.presets') for millimetres
Thermal 40×30 still seeded
a5 or custom in the Center paper list
Uploaded HTML/CSS/Blade template
```
