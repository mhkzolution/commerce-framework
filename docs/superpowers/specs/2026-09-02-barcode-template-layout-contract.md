# Barcode v1.4 — Template / Label Layout Contract

**Date:** 2026-09-02  
**Status:** Locked (ADR-006)  
**Applies to:** `feat/barcode-management-v1` when Phase 1 starts  
**Does not start:** extraction

```text
Barcode templates define label layout only.

Templates must not contain business logic,
database access,
or runtime dependencies on Product, Media,
Marketplace, Inventory, or Scanner.
```

v1.4 is a **label management** center (presets + print), not a sticker CAD tool and not a printer driver.

---

## Layout vs payload

```text
LabelPayload (frozen on the print job)
  +
BarcodeTemplate (paper / grid / type / visibility)
        ↓
BarcodePrintService → HTML | PDF
```

Template never queries Product. Reprint uses the stored payload plus the **job settings snapshot** (archive already copies settings onto the job at create). Do not re-read the live template row to change frozen names; layout may come from job.settings so an edited template does not rewrite history.

---

## Keep archive schema; do not rename columns

`84e905c` already stores layout. Copy those migrations. Do **not** rewrite to `paper_width_mm` on day one — paper millimetres live in `config('barcode.paper_sizes.{key}')`.

| Concept | Archive column / source |
|---------|-------------------------|
| Paper identity | `paper_size` (`a4` \| `thermal`) |
| Paper mm | config, not a table column |
| Grid | `rows`, `columns` |
| Label mm | `label_width`, `label_height` |
| Margins | `margin_top/right/bottom/left` |
| Gaps | `spacing_horizontal`, `spacing_vertical` |
| Type | `label_orientation`, padding, `label_*_font_size` |
| Flags | `is_favorite`, `is_default` |

Forbidden on the template row: SQL, Product/Seller rules, barcode strategy, Inventory/POS/Scanner keys.

---

## Four v1.4 presets

Replace archive seed of A4 4×10, A4 3×8, Thermal 50×30, Thermal 40×30.

| `preset_code` | Meaning | Grid (archive equivalent) |
|---------------|---------|---------------------------|
| `a4-24` | A4 24 labels | 3×8 (63.5 × 33.9 mm) |
| `a4-40` | A4 40 labels | 4×10 (48.5 × 25.4 mm) — default |
| `a4-65` | A4 65 labels | **new:** 5×13, 38.1 × 21.2 mm |
| `thermal-50x30` | Thermal 50×30 | existing thermal 1×1 |

Drop from v1.4 catalog: Thermal 40×30 seed, `a5` / `custom` as first-class paper choices (do not show in the Center). Numbers for `a4-65` are locked so pagination tests are deterministic.

Admin create = pick a preset (copies grid/paper/label mm). Overrides allowed:

```text
name, is_favorite, is_default
show_name, show_sku, show_owner, show_barcode
font sizes, padding, orientation
```

Grid, paper, label mm, margins, gaps stay those of `preset_code`. Users do not type every millimetre on a blank form.

---

## New columns (Phase 1 migration, not in archive)

After copying the five archive migrations, add one migration on the branch:

```text
preset_code     string nullable  # a4-24 | a4-40 | a4-65 | thermal-50x30
show_name       boolean default true
show_sku        boolean default true
show_owner      boolean default true
show_barcode    boolean default true
```

Renderer honors `show_*` from **job.settings** (copied from template at print). Missing keys default true (archive always drew owner + barcode text).

---

## Forbidden in v1.4

```text
Uploaded PHP / Blade / HTML / CSS / JS templates
Mustache/Liquid stored on the row
Printer drivers (Windows / USB / Bluetooth)
Calling Product, Media, Marketplace, Inventory, Scanner from template code
```

`BarcodePrintService` input = Template settings + Payload. Output = HTML and PDF only.

---

## Adapt after copy

```text
modules/Barcode/src/Services/BarcodeTemplateService.php   # four presets; drop 40×30
modules/Barcode/config/barcode.php                        # paper catalog a4 + thermal only
modules/Barcode/resources/views/admin/templates/_form.blade.php
modules/Barcode/src/Services/BarcodePrintService.php      # honor show_*
```

New file:

```text
modules/Barcode/database/migrations/2026_09_02_*_add_preset_and_visibility_to_barcode_templates.php
```

---

## Tests (ADR-005)

- Seeded templates are exactly the four `preset_code`s; default is `a4-40`
- Print with `show_owner = false` omits owner text
- Template service / renderer tests do not boot Product
- LayoutCalculator for `a4-65` yields 65 cells per page
