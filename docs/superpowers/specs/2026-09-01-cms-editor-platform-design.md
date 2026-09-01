# CMS-200 Editor Platform Design

**Branch:** `feat/cms-editor-platform`  
**Date:** 2026-09-01  
**Status:** Approved by product (CMS v1 + Admin IA on `main`)

## Goal

Replace the CMS textarea with a reusable **EditorPlatform** so authors write structured HTML in Posts and Pages, without changing SEO, redirects, sitemap, preview, permissions, or routes.

This is a content platform, not a drop-in textarea swap.

## Current state

- Posts (`modules/Cms/resources/views/admin/posts/_form.blade.php`) and Pages (inline create/edit) use `<textarea name="content">`.
- Storage is already `cms_posts.content` and `cms_pages.content` (HTML or plain text).
- Storefront `BlogContentFormatter` already prefers HTML when `<h2>`/`<h3>` are present.
- Featured image uses `media::components.media-picker` (`GET admin.media.picker`).
- SEO fields are included via `catalog::admin.partials.seo-fields` and must stay.

## Mazsashop concepts to reuse (not files)

Reuse the *shape* of Mazsashop’s EditorPlatform:

| Concept | Commerce Framework v1 |
|---|---|
| TipTap 2.x authoring surface | Yes |
| Hidden field sync for form POST | Yes (`textarea[name=content]`) |
| Toolbar + inspector | Yes (core formatting only) |
| Media provider | CF `admin.media.picker` JSON API |
| Server sanitizer on save | `EditorPipeline::sanitize()` |
| HTML storage | Unchanged columns |

Do **not** copy Mazsashop files, CSS, `window.MazsaEditor`, esm.sh imports, slash commands, FAQ/accordion plugins, or Mazsa media APIs.

## Out of scope (CMS-201+)

Slash commands, FAQ/accordion/callout blocks, AI writing, live SEO analyzer, collaborative editing, plugin marketplace.

## Architecture

```
resources/js/admin/cms-editor.js          boot
resources/js/admin/editor/platform.js       TipTap Editor per [data-cms-editor]
resources/js/admin/editor/toolbar.js       H1–H6, marks, lists, quote, code, table, link, image
resources/js/admin/editor/inspector.js    image alt / link href / table actions
resources/js/admin/editor/media-provider.js   GET /admin/media/picker
resources/css/admin/cms-editor.css        CF tokens only

modules/Cms/src/Services/EditorPipeline.php
  sanitize(html) → allowlisted HTML (no script/handlers/javascript:)

PostService / PageService
  persist pipeline.sanitize($content)
```

v1 extensions: StarterKit (heading 1–6, paragraph, bold, italic, lists, blockquote, code block) + Link, Image, Table.

## UI

- Two-column CMS layout: main column (title, slug, excerpt, editor) + sidebar (status, organization, featured image, collapsible SEO).
- Preview and save actions stay on the form shell.
- Routes, permissions, preview URLs, sitemap, JSON-LD, and redirects are untouched.

## Testing

- Unit: sanitizer strips XSS, keeps headings/lists/tables/images/links.
- Feature: create/edit forms mount `[data-cms-editor]`; saving HTML persists sanitized content; existing CMS tests still pass.
