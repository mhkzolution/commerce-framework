# CMS Image Width and Align Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let CMS authors set each `<img>` width as a percent of the content column and left/center/right alignment, with inspector presets plus drag-resize, without changing the stored HTML shape.

**Architecture:** TipTap Image stays an `<img>`. Persist `width="NN%"` (CMS layout attribute, not pixel width) and `data-align`. Editor NodeView adds a resize handle that is not stored. `EditorPipeline` allowlists those attributes. Editor prose CSS and storefront prose CSS share the same layout rules, including 50%+50% gap and mobile stacking.

**Tech Stack:** TipTap 2.x Image extension, Vite, Laravel 13, PHP 8.4, PHPUnit.

**Spec:** `docs/superpowers/specs/2026-09-09-cms-image-width-align-design.md`

## Global Constraints

- Bare `<img>` only. No `<figure>`, gallery node, or persisted wrapper.
- `width="NN%"` is a CMS custom layout attribute (percent of content column).
- `data-align` is `left|center|right`; omit when left.
- Clamp width 25–100; snap drag to 100/75/50/33 within ±3%.
- No text wrap around images. Left-aligned images that overflow a row wrap to the next line.
- Gap must not push two `width="50%"` images onto two rows (`box-sizing: border-box`, no horizontal margin, `padding-inline: 0.25rem`).
- At `max-width: 1023px`, every image is `width: 100%` / `display: block`.
- Do not persist `style` or event handlers.
- Do not change CMS routes, permissions, media picker APIs, or featured-image sidebar.

## File map

| File | Responsibility |
|---|---|
| `modules/Cms/src/Services/EditorPipeline.php` | Keep/drop `width` and `data-align` |
| `tests/Unit/Cms/EditorPipelineTest.php` | Sanitizer contract |
| `resources/css/storefront/blog.css` | Storefront layout contract |
| `resources/css/admin/cms-editor.css` | Editor layout + resize handle |
| `tests/Unit/Cms/CmsImageLayoutCssTest.php` | CSS contract assertions |
| `resources/js/admin/editor/cms-image.js` | Inline Image + attrs + NodeView |
| `resources/js/admin/editor/platform.js` | Use CmsImage; default width on insert |
| `resources/js/admin/editor/inspector.js` | Presets, current %, align |
| `tests/Feature/Cms/CmsAdminTest.php` | Persist `width="50%"` on save |

---

### Task 1: Sanitizer contract

**Files:**
- Modify: `tests/Unit/Cms/EditorPipelineTest.php`
- Modify: `modules/Cms/src/Services/EditorPipeline.php`

**Interfaces:**
- Consumes: `EditorPipeline::sanitize(?string $html): ?string`
- Produces: same method; `width` only as `25%`–`100%`; `data-align` only `left|center|right`

- [ ] **Step 1: Write the failing tests**

Add to `tests/Unit/Cms/EditorPipelineTest.php`:

```php
public function test_it_keeps_percent_width_and_data_align(): void
{
    $html = '<img src="/x.jpg" alt="A" width="50%" data-align="center">';

    $this->assertSame($html, $this->pipeline->sanitize($html));
}

public function test_it_drops_invalid_image_layout_attributes(): void
{
    $html = '<img src="/x.jpg" alt="A" width="12%" data-align="justify" style="width:50%" width-px="50">';

    $sanitized = $this->pipeline->sanitize($html);

    $this->assertStringContainsString('src="/x.jpg"', $sanitized);
    $this->assertStringContainsString('alt="A"', $sanitized);
    $this->assertStringNotContainsString('width="12%"', $sanitized);
    $this->assertStringNotContainsString('data-align="justify"', $sanitized);
    $this->assertStringNotContainsString('style=', $sanitized);
}

public function test_it_drops_pixel_and_out_of_range_width(): void
{
    $pixel = $this->pipeline->sanitize('<img src="/x.jpg" alt="" width="50">');
    $over = $this->pipeline->sanitize('<img src="/x.jpg" alt="" width="150%">');

    $this->assertStringNotContainsString('width="50"', $pixel);
    $this->assertStringNotContainsString('width="150%"', $over);
}

public function test_legacy_image_without_width_is_kept(): void
{
    $html = '<img src="/x.jpg" alt="">';

    $this->assertSame($html, $this->pipeline->sanitize($html));
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=EditorPipelineTest`

Expected: new tests FAIL because `data-align` is stripped and invalid `width` is kept as-is.

- [ ] **Step 3: Implement sanitizer rules**

In `sanitizeImages`, allow `data-align` in the attribute list. After collecting attrs:

```php
if (isset($attrs['width']) && ! $this->isAllowedPercentWidth($attrs['width'])) {
    unset($attrs['width']);
}

$align = $attrs['data-align'] ?? '';
if (! in_array($align, ['left', 'center', 'right'], true)) {
    unset($attrs['data-align']);
} elseif ($align === 'left') {
    unset($attrs['data-align']);
}
```

Add:

```php
private function isAllowedPercentWidth(string $value): bool
{
    if (preg_match('/^(\d{1,3})%$/', $value, $match) !== 1) {
        return false;
    }

    $percent = (int) $match[1];

    return $percent >= 25 && $percent <= 100;
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --filter=EditorPipelineTest`

Expected: PASS (existing XSS tests still green).

- [ ] **Step 5: Commit**

```bash
git add tests/Unit/Cms/EditorPipelineTest.php modules/Cms/src/Services/EditorPipeline.php
git commit -m "$(cat <<'EOF'
feat: sanitize CMS image percent width and data-align

EOF
)"
```

---

### Task 2: Shared CSS contract (editor + storefront)

**Files:**
- Create: `tests/Unit/Cms/CmsImageLayoutCssTest.php`
- Modify: `resources/css/storefront/blog.css` (replace `.storefront-prose img` block)
- Modify: `resources/css/admin/cms-editor.css` (prose images + handle)

**Interfaces:**
- Consumes: attribute contract from Task 1
- Produces: identical desktop/mobile layout rules on `.storefront-prose img` and `.cms-editor-prose img`

- [ ] **Step 1: Write the failing CSS contract test**

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Cms;

use PHPUnit\Framework\TestCase;

final class CmsImageLayoutCssTest extends TestCase
{
    public function test_prose_css_honors_percent_width_gap_and_mobile_stack(): void
    {
        foreach ([
            dirname(__DIR__, 3).'/resources/css/storefront/blog.css',
            dirname(__DIR__, 3).'/resources/css/admin/cms-editor.css',
        ] as $path) {
            $css = file_get_contents($path);
            $this->assertNotFalse($css, $path);

            $this->assertStringContainsString('box-sizing: border-box', $css);
            $this->assertStringContainsString('padding-inline: 0.25rem', $css);
            $this->assertStringContainsString('img:not([width])', $css);
            $this->assertStringContainsString('max-width: 1023px', $css);
            $this->assertStringNotContainsString(
                ".storefront-prose img {\n    display: block;\n    width: 100%;",
                $css,
            );
        }
    }
}
```

Adjust the last assertion so it only applies to `blog.css` (editor never had that exact block). For editor, assert `.cms-editor-prose img` exists with `inline-block` and `attr(width)` or `[width$="%"]`.

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=CmsImageLayoutCssTest`

Expected: FAIL (blog CSS still forces `width: 100%`).

- [ ] **Step 3: Replace storefront image CSS**

Replace `.storefront-prose img { display: block; width: 100%; ...}` with:

```css
.storefront-prose img {
    box-sizing: border-box;
    height: auto;
    max-width: 100%;
    border-radius: 0.75rem;
}

.storefront-prose img:not([width]) {
    width: 100%;
}

.storefront-prose img[width$="%"] {
    width: attr(width);
}

.storefront-prose img:not([data-align]),
.storefront-prose img[data-align="left"] {
    display: inline-block;
    vertical-align: top;
    margin: 0.5rem 0;
    margin-inline: 0;
    padding-inline: 0.25rem;
}

.storefront-prose img[data-align="center"] {
    display: block;
    margin: 1.75rem auto;
}

.storefront-prose img[data-align="right"] {
    display: block;
    margin: 1.75rem 0 1.75rem auto;
}

@media (max-width: 1023px) {
    .storefront-prose img,
    .storefront-prose img[width$="%"] {
        display: block;
        width: 100%;
        padding-inline: 0;
        margin: 1.75rem 0;
    }

    .storefront-prose img[data-align="center"] {
        margin-inline: auto;
    }

    .storefront-prose img[data-align="right"] {
        margin-left: auto;
        margin-right: 0;
    }
}
```

If `width: attr(width)` is not enough in a later check, add integer rules 25–100 in the same files (do not persist `style`).

- [ ] **Step 4: Add matching editor prose + handle CSS** in `resources/css/admin/cms-editor.css` using `.cms-editor-prose` instead of `.storefront-prose`. Add:

```css
.cms-image-node {
    position: relative;
    display: inline-block;
    max-width: 100%;
    vertical-align: top;
}

.cms-image-node img {
    display: block;
    width: 100%;
    height: auto;
}

.cms-image-node__handle {
    position: absolute;
    right: 0;
    bottom: 0;
    width: 0.75rem;
    height: 0.75rem;
    cursor: nwse-resize;
    background: var(--color-primary, #44403c);
}
```

The NodeView wrapper uses percent width; the inner img is 100% of the wrapper so gap padding still lives on the wrapper at desktop.

Editor NodeView wrapper should use the same desktop/mobile rules as prose `img` so inspector preview matches the blog.

- [ ] **Step 5: Run CSS test**

Run: `php artisan test --filter=CmsImageLayoutCssTest`

Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add tests/Unit/Cms/CmsImageLayoutCssTest.php resources/css/storefront/blog.css resources/css/admin/cms-editor.css
git commit -m "$(cat <<'EOF'
feat: honor CMS image percent width on editor and blog

EOF
)"
```

---

### Task 3: TipTap Image attrs, inline packing, inspector

**Files:**
- Create: `resources/js/admin/editor/cms-image.js`
- Modify: `resources/js/admin/editor/platform.js`
- Modify: `resources/js/admin/editor/inspector.js`

**Interfaces:**
- Consumes: sanitizer + CSS from Tasks 1–2
- Produces: Image node attrs `{ src, alt, width: string, 'data-align': string|null }`; inspector updates those attrs

- [ ] **Step 1: Add `CmsImage` extension**

`resources/js/admin/editor/cms-image.js`:

```js
import Image from '@tiptap/extension-image';

export const WIDTH_PRESETS = [100, 75, 50, 33];
export const WIDTH_MIN = 25;
export const WIDTH_MAX = 100;

export function clampPercent(value) {
    const n = Math.round(Number(value));
    return Math.min(WIDTH_MAX, Math.max(WIDTH_MIN, Number.isFinite(n) ? n : 100));
}

export function snapPercent(value) {
    const clamped = clampPercent(value);
    for (const preset of WIDTH_PRESETS) {
        if (Math.abs(clamped - preset) <= 3) {
            return preset;
        }
    }
    return clamped;
}

export function percentAttr(value) {
    return `${clampPercent(String(value).replace('%', ''))}%`;
}

export const CmsImage = Image.extend({
    name: 'image',
    inline: true,
    group: 'inline',
    addAttributes() {
        return {
            ...this.parent?.(),
            width: {
                default: '100%',
                parseHTML: (el) => percentAttr(el.getAttribute('width') || '100%'),
                renderHTML: (attrs) => ({ width: percentAttr(attrs.width || '100%') }),
            },
            'data-align': {
                default: null,
                parseHTML: (el) => {
                    const align = el.getAttribute('data-align');
                    return ['center', 'right'].includes(align) ? align : null;
                },
                renderHTML: (attrs) => (attrs['data-align'] ? { 'data-align': attrs['data-align'] } : {}),
            },
        };
    },
});
```

Leave NodeView for Task 4; this task can render native `<img>`.

- [ ] **Step 2: Use CmsImage in `platform.js`**

Replace `import Image from '@tiptap/extension-image'` with `import { CmsImage } from './cms-image'`. Replace `Image.configure({ allowBase64: false })` with `CmsImage.configure({ allowBase64: false })`.

Every `setImage` / `insertContentAt` image insert must pass `width: '100%'`.

- [ ] **Step 3: Inspector presets + align**

When `editor.isActive('image')`, after alt:

- Read `attrs.width` (strip `%`) and `attrs['data-align']`.
- Four preset buttons. Click: `updateAttributes('image', { width: '50%' })`.
- Span showing current `{n}%`.
- Left / Center / Right. Left: `updateAttributes('image', { 'data-align': null })`. Center/right set the token.

- [ ] **Step 4: Manual check**

Open `/admin/cms/posts/create`, insert two 50% left images. They must sit on one row on a desktop viewport. Save a draft and confirm `cms_posts.content` contains `width="50%"`.

- [ ] **Step 5: Commit**

```bash
git add resources/js/admin/editor/cms-image.js resources/js/admin/editor/platform.js resources/js/admin/editor/inspector.js
git commit -m "$(cat <<'EOF'
feat: add CMS image width presets and alignment

EOF
)"
```

---

### Task 4: Drag resize NodeView + persist test

**Files:**
- Modify: `resources/js/admin/editor/cms-image.js` (`addNodeView`)
- Modify: `tests/Feature/Cms/CmsAdminTest.php`

**Interfaces:**
- Consumes: `snapPercent`, `percentAttr`, inspector attrs from Task 3
- Produces: NodeView handle; stored HTML still a single `<img>`

- [ ] **Step 1: Write the failing persist test**

In `CmsAdminTest`:

```php
public function test_post_save_keeps_image_percent_width(): void
{
    $admin = User::query()->first();

    $this->actingAs($admin)
        ->post(route('admin.cms.posts.store'), [
            'title' => 'Width',
            'slug' => 'width-attr',
            'content' => '<p><img src="/media/hero.jpg" alt="Hero" width="50%"></p>',
            'status' => 'draft',
        ])
        ->assertRedirect();

    $post = Post::query()->where('slug', 'width-attr')->first();
    $this->assertNotNull($post);
    $this->assertStringContainsString('width="50%"', (string) $post->content);
    $this->assertStringContainsString('src="/media/hero.jpg"', (string) $post->content);
}
```

- [ ] **Step 2: Run persist test**

Run: `php artisan test --filter=test_post_save_keeps_image_percent_width`

Expected: PASS after Task 1. If it already passes, keep it as the round-trip regression.

- [ ] **Step 3: Add NodeView drag handle**

In `CmsImage.addNodeView()`, render a `span.cms-image-node` with the img and a handle. On pointerdown/move/up:

```js
const contentWidth = editor.view.dom.clientWidth;
const next = snapPercent((event.clientX - wrapper.getBoundingClientRect().left) / contentWidth * 100);
editor.commands.updateAttributes('image', { width: `${next}%` });
```

Wrapper style width is the percent so drag preview matches stored %. `getHTML()` must still emit `<img src alt width data-align>` with no wrapper.

- [ ] **Step 4: Run CMS tests**

Run: `php artisan test --filter='EditorPipelineTest|CmsImageLayoutCssTest|CmsAdminTest'`

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add resources/js/admin/editor/cms-image.js tests/Feature/Cms/CmsAdminTest.php
git commit -m "$(cat <<'EOF'
feat: drag-resize CMS images as percent width

EOF
)"
```

---

## Spec coverage

| Spec requirement | Task |
|---|---|
| `width="NN%"` custom layout attr | 1, 2, 3 |
| `data-align` | 1, 2, 3 |
| Inspector presets + current % | 3 |
| Drag % of content width, snap ±3, 25–100 | 3–4 |
| `height: auto` | 2 |
| Left wrap to next line | 2, 3 (`inline: true`) |
| 50%+50% gap | 2 |
| Mobile 1023px stack | 2 |
| Sanitizer | 1 |
| Storefront + editor same CSS | 2 |
| Persist | 4 |
