# CMS Editor Platform Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ship a TipTap 2.x EditorPlatform for CMS Posts and Pages that stores sanitized HTML in the existing content columns.

**Architecture:** Client TipTap editor mounts on `[data-cms-editor]`, syncs into the existing `content` textarea, and opens images through Commerce Framework `admin.media.picker`. PHP `EditorPipeline` sanitizes on save. No Mazsashop files, CSS, or APIs.

**Tech Stack:** TipTap 2.x, Vite, Laravel 13, PHP 8.4, PHPUnit.

## Global Constraints

- TipTap 2.x only (not 3.x).
- Do not copy Mazsashop files, CSS, or APIs.
- Store HTML in `cms_posts.content` and `cms_pages.content`.
- Do not change CMS routes, permissions, SEO, redirects, sitemap, or preview.
- Media via `route('admin.media.picker')`.
- v1 features only: headings, paragraph, bold, italic, link, image, lists, quote, table, code block.

---

### Task 1: Server sanitizer

**Files:**
- Create: `modules/Cms/src/Services/EditorPipeline.php`
- Create: `tests/Unit/Cms/EditorPipelineTest.php`
- Modify: `modules/Cms/src/CmsServiceProvider.php`
- Modify: `modules/Cms/src/Services/PostService.php`
- Modify: `modules/Cms/src/Services/PageService.php`

- [ ] **Step 1:** Add `EditorPipeline` with an HTML allowlist and XSS stripping.
- [ ] **Step 2:** Sanitize `content` inside PostService and PageService create/update.
- [ ] **Step 3:** Unit-test script stripping, safe tags kept, `javascript:` neutralized.
- [ ] **Step 4:** Commit.

### Task 2: TipTap editor client

**Files:**
- Create: `resources/js/admin/cms-editor.js`
- Create: `resources/js/admin/editor/platform.js`
- Create: `resources/js/admin/editor/toolbar.js`
- Create: `resources/js/admin/editor/inspector.js`
- Create: `resources/js/admin/editor/media-provider.js`
- Create: `resources/css/admin/cms-editor.css`
- Modify: `vite.config.js`
- Modify: `package.json` / `package-lock.json`

- [ ] **Step 1:** Add TipTap 2.x packages.
- [ ] **Step 2:** Implement platform, toolbar, inspector, CF media provider.
- [ ] **Step 3:** Register Vite entries.

### Task 3: CMS two-column forms

**Files:**
- Create: `modules/Cms/resources/views/components/editor.blade.php`
- Create: `modules/Cms/resources/views/admin/pages/_form.blade.php`
- Modify: post/page create + edit views and `_form` partials.

- [ ] **Step 1:** Replace textarea with editor component.
- [ ] **Step 2:** Two-column layout + collapsible SEO + featured image in sidebar.
- [ ] **Step 3:** Feature tests for mount point and sanitized HTML save.
- [ ] **Step 4:** Commit.
