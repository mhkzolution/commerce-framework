# CMS editor image width and alignment

**Date:** 2026-09-09  
**Status:** Approved for implementation  
**Owner:** CMS editor platform (`resources/js/admin/editor`, `EditorPipeline`, storefront prose CSS)  
**Related:** `docs/superpowers/specs/2026-09-01-cms-editor-platform-design.md`

Authors insert one image at a time, then set how wide it is and how it sits in the line. Two or more left-aligned images that are narrower than a full row sit next to each other. Text does not wrap around images.

**Later jobs (not this spec):** gallery row as a unit, captions/`<figure>`, text wrap/float, slash-command gallery, plugin marketplace.

---

## Decisions

- Keep a bare `<img>`. No `<figure>`, no gallery node, no extra wrapper in stored HTML.
- Persist width as `width="NN%"`. This is a **CMS custom layout attribute**, not HTML’s pixel `width`. Editor CSS, storefront CSS, and the sanitizer all treat `NN` as percent of the content column.
- Persist alignment as `data-align="left|center|right"`. Omit `data-align` when left (the default).
- Inspector: presets **100 / 75 / 50 / 33**, live current-width label, left / center / right, existing alt + replace.
- Drag the bottom-right handle to change width as % of the editor content width. Snap within ±3% of a preset. Clamp **25–100**. `height: auto`.
- Editor, storefront, and sanitizer must interpret `width` and `data-align` the same way.

---

## Attribute contract

Stored markup:

```html
<img src="/storage/…" alt="Hero" width="50%">
<img src="/storage/…" alt="Inset" width="33%" data-align="center">
```

| Attribute | Allowed values | Default when missing |
|---|---|---|
| `src`, `alt` | unchanged | `alt=""` |
| `width` | `25%`–`100%` inclusive, integer only | treat as `100%` (legacy posts) |
| `data-align` | `left`, `center`, `right` | `left` |
| `height` | not written by this feature | ignore for layout; `height: auto` in CSS |

Reject or drop: `style`, `class`, `on*`, `width` without `%`, `width` outside 25–100, `data-align` other than the three tokens.

Insert (toolbar, slash, paste, drop) writes `width="100%"` and no `data-align`.

---

## Image Inspector

When the selection is an image, the existing inspector (`mountInspector`) adds:

1. Preset buttons `100%` `75%` `50%` `33%`. The matching preset is marked selected. If the current width is not a preset (drag result), no preset is selected.
2. Current width text, e.g. `50%`, always in sync with the node.
3. Align buttons: Left / Center / Right. Selected button matches `data-align` or left when omitted.
4. Existing Alt text and Replace image.

Preset click sets `width` to that percent and keeps `data-align`. Align click sets `data-align` (remove the attribute when left).

---

## Drag resize

A selected image in the editor shows a bottom-right handle (NodeView chrome only; not in stored HTML).

- Horizontal drag vs the **editor content box** width → percent = round(pointerX / contentWidth × 100).
- Clamp 25–100. If the value is within 3 of 100, 75, 50, or 33, snap to that preset.
- Aspect ratio is CSS `height: auto`, not a stored `height`.
- Pointer up commits attributes; the inspector label and preset highlight update on every drag frame.

---

## Shared layout rules (editor + storefront)

These rules apply in `.cms-editor-prose` and `.storefront-prose`. The TipTap Image node is **inline** so consecutive left-aligned images share a line box.

1. `height: auto`; `max-width: 100%`; `box-sizing: border-box`.
2. If `width` is missing, render at **100%** (legacy).
3. If `width="NN%"`, the used CSS width is **NN%** of the content column. Do not force `width: 100%` on images that have a `%` width attribute. Prefer `img[width$="%"] { width: attr(width); }` plus `img:not([width]) { width: 100% }`. If `attr(width)` is not enough in the browsers this app targets, add explicit integer rules for 25–100. Never persist `style`.
4. `data-align="left"` or omitted: `display: inline-block; vertical-align: top`. **No horizontal margin.** Row gap is `padding-inline: 0.25rem` inside the percent box so **two `width="50%"` images stay on one row**. A leftover HTML space between tags must not wrap them; keep adjacent image nodes without a text node, or collapse with `img { margin-inline: 0 }`.
5. Left-aligned images whose percents **sum past 100% of the row wrap to the next line** (normal inline-block wrap). Example: 50% + 50% stay on one row; 50% + 50% + 50% is two + one.
6. `data-align="center"`: `display: block; margin-inline: auto`. That image is on its own row.
7. `data-align="right"`: `display: block; margin-left: auto`. That image is on its own row.
8. Vertical spacing: modest block margin on full-width / centered / right images. Left-aligned sub-100% images use only the padding-inline gap above, not the 1.75rem stack margin.

### Responsive / mobile

At `max-width: 1023px` (same breakpoint as the CMS workspace), **ignore packing and stored percent**. Every image is `display: block; width: 100%`. `data-align="center"` / `right` still apply as block alignment on that full-width image. Desktop (`min-width: 1024px`) uses the percent + wrap rules above.

Current `.storefront-prose img { display: block; width: 100%; }` is **wrong** for desktop and must be replaced with this contract. Editor CSS needs the same rules.

---

## Surfaces

| Area | Change |
|---|---|
| `resources/js/admin/editor/platform.js` | Image `inline: true`; attrs `width`, `data-align`; default `width="100%"` on insert/upload |
| `resources/js/admin/editor/inspector.js` | Presets, current %, align |
| New NodeView (editor-only) | Resize handle; serialize back to `<img>` only |
| `resources/css/admin/cms-editor.css` | Prose image width/align + handle |
| `resources/css/storefront/blog.css` | Replace forced `width: 100%` with the shared rules |
| `modules/Cms/src/Services/EditorPipeline.php` | Allow `data-align`; keep `width` only as `NN%` in 25–100 |

Pages and posts share the same editor and pipeline. No route or schema changes.

---

## Impact review (must stay in lockstep)

### 1. Editor rendering

TipTap Image must read/write `width` and `data-align`. The NodeView may wrap the `<img>` for the handle; `getHTML()` / hidden `textarea[name=content]` must emit a single `<img>` with those attributes. Drag percent uses the canvas content width, not the window.

### 2. Storefront rendering

`BlogContentFormatter` already treats HTML with `<img>` as HTML. Layout is CSS-only: `.storefront-prose` must honor `width="NN%"` and `data-align` the same as the editor. A post saved at 50% left must not stretch to full column on `/blog/{slug}`.

### 3. Sanitizer / HTML persistence

`EditorPipeline::sanitizeImages` already allows `src`, `alt`, `title`, `width`, `height`. This job:

- Allows `data-align` only if it is `left`, `center`, or `right`.
- Keeps `width` only if it matches `/^[0-9]{1,3}%$/` and the integer is 25–100. Otherwise drop `width` (legacy full width).
- Never persist `style` or event handlers.
- Round-trip: inspector/drag → HTML in the hidden field → sanitize on save → same attributes on edit and on the storefront.

---

## Tests (TDD)

Failing tests first.

1. **Pipeline keeps** `<img src="/x.jpg" alt="A" width="50%" data-align="center">` unchanged (aside from optional omitted default align).
2. **Pipeline drops** `data-align="justify"`, `width="12%"`, `width="150%"`, `width="50"`, `style="width:50%"`.
3. **Legacy** `<img src="/x.jpg" alt="">` still sanitizes; missing width means 100% in CSS, not a required attribute.
4. **Feature:** create/edit still mounts `[data-cms-editor]`; saving a post with `width="50%"` persists that string on `cms_posts.content`.
5. **CSS contract:** `resources/css/storefront/blog.css` and `resources/css/admin/cms-editor.css` must not force all prose images to `width: 100%` without a `:not([width])` (or equivalent) fallback. Desktop left-aligned images use `box-sizing: border-box` and no horizontal margin. Mobile (`max-width: 1023px`) forces `width: 100%`.

Existing `EditorPipelineTest` and CMS form-mount tests stay green.

---

## Non-goals

- Gallery “N images in this row” as one block
- `<figure>` / captions
- Text wrapping around images
- Changing media picker/upload APIs
- Admin featured-image picker on the post sidebar
- Pixel `width` / `height` as the layout source of truth
