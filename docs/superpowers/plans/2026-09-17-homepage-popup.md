# Homepage Popup Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Port Young Elephant’s homepage popup (admin CRUD + homepage overlay) into commerce-framework with storage key `commerce:home-popup`.

**Architecture:** New `cms_popups` entity in the Cms module, queried through `HomeContentQueryService::popups()`, rendered only on the Cart homepage. Admin Blade CRUD mirrors Hero/Promo banners. Client dismiss uses sessionStorage (this session) or localStorage (7 days).

**Tech Stack:** Laravel 13, PHP 8.4, Blade, Vite, vanilla JS, PHPUnit.

**Spec:** `docs/superpowers/specs/2026-09-17-homepage-popup-design.md`

**Source root:** `/Users/kritsadanambunraung/Projects/young_elephant`

## Global Constraints

- Direct port of Young Elephant data shape (`start_at` / `priority` / `status`). Do not rename to Hero banner fields.
- Storage key is `commerce:home-popup`, never `ye:home-popup`.
- Permissions stay `cms.page.view` / `cms.page.manage`.
- Overlay is not a `HomepageSection`.
- Do not port `PopupSeeder`.
- Cart homepage service must not import `Commerce\Cms\Models\Popup`.
- Branch from `main` as `feat/homepage-popup`. Do not commit onto `feat/pos-tax-invoice-integration`.
- Do not commit `Archive.zip`.

## File map

| File | Responsibility |
|---|---|
| `modules/Cms/database/migrations/2026_09_17_200000_create_cms_popups_table.php` | `cms_popups` schema |
| `modules/Cms/src/Models/Popup.php` | Eloquent + `currentlyVisible` |
| `modules/Cms/src/DTO/UpsertPopupData.php` | Admin write DTO |
| `modules/Cms/src/Http/Requests/UpsertPopupRequest.php` | Validation + timezone→UTC |
| `modules/Cms/src/Services/PopupService.php` | Create/update/delete + unique slug |
| `modules/Cms/src/Http/Controllers/Admin/PopupController.php` | Admin CRUD |
| `modules/Cms/resources/views/admin/popups/*` | List/create/edit/form/preview |
| `modules/Cms/src/Services/HomeContentQueryService.php` | Storefront popup payload |
| `modules/Cms/src/Support/HomeContentCache.php` | Cache segment `popups` |
| `modules/Cart/src/Services/StorefrontHomePageService.php` | Pass `homePopups` |
| `modules/Cart/resources/views/storefront/partials/home-popup.blade.php` | Overlay markup |
| `resources/js/storefront/home-popup.js` | Show/hide/carousel/dismiss |
| `tests/Feature/Cms/PopupAdminTest.php` | Admin create/update/forms |
| `tests/Feature/Storefront/StorefrontPopupTest.php` | Homepage visibility |

Copy verbatim from Young Elephant unless a step names a patch. After copy, replace every `ye:home-popup` with `commerce:home-popup`.

---

### Task 1: Branch, schema, model, admin tests

**Files:**
- Create: `modules/Cms/database/migrations/2026_09_17_200000_create_cms_popups_table.php`
- Create: `modules/Cms/src/Models/Popup.php`
- Create: `modules/Cms/src/DTO/UpsertPopupData.php`
- Create: `modules/Cms/src/Http/Requests/UpsertPopupRequest.php`
- Create: `modules/Cms/src/Services/PopupService.php`
- Create: `modules/Cms/src/Http/Controllers/Admin/PopupController.php`
- Create: admin views under `modules/Cms/resources/views/admin/popups/`
- Create: `tests/Feature/Cms/PopupAdminTest.php`
- Modify: `modules/Cms/src/CmsServiceProvider.php` — `singleton(PopupService::class)`
- Modify: `modules/Cms/routes/web.php` — popup routes beside FAQ
- Modify: `config/admin.php` — Online Store → Popups
- Modify: `modules/Cms/resources/lang/{en,th}/admin.php`
- Modify: `resources/lang/{en,th}/nav.php`

**Interfaces:**
- Consumes: `UniqueSlug::allocate`, `RedirectsAfterSave`, `CmsMediaThumbnails::urls`
- Produces: `Popup` model; routes `admin.cms.popups.{index,create,store,edit,update,destroy}`; `PopupService::create/update/delete`

- [ ] **Step 1: Create branch from main**

```bash
git checkout main
git checkout -b feat/homepage-popup
```

Bring `docs/superpowers/specs/2026-09-17-homepage-popup-design.md` and this plan along (untracked files survive checkout).

- [ ] **Step 2: Write the failing admin test**

Create `tests/Feature/Cms/PopupAdminTest.php` by copying Young Elephant’s `tests/Feature/Cms/PopupAdminTest.php` (same assertions: create slugifies title, update switches type/CTA/auto-close, create form has `data-popup-preview`, edit form shows stored copy).

- [ ] **Step 3: Run test to verify it fails**

```bash
php artisan test --filter=PopupAdminTest
```

Expected: FAIL (missing class/table/route).

- [ ] **Step 4: Copy admin backend + views from Young Elephant**

Copy these files (migration filename becomes `2026_09_17_200000_create_cms_popups_table.php`; schema unchanged):

- `modules/Cms/src/Models/Popup.php`
- `modules/Cms/src/DTO/UpsertPopupData.php`
- `modules/Cms/src/Http/Requests/UpsertPopupRequest.php`
- `modules/Cms/src/Services/PopupService.php`
- `modules/Cms/src/Http/Controllers/Admin/PopupController.php`
- `modules/Cms/resources/views/admin/popups/{index,create,edit,_form,_preview}.blade.php`

Register `PopupService` in `CmsServiceProvider::register()`.

Add routes in `modules/Cms/routes/web.php` next to FAQ, matching Young Elephant (`PopupController`, view routes under `cms.page.view`, write routes under `cms.page.manage`).

Add nav child after FAQ in `config/admin.php`:

```php
['type' => 'link', 'label' => 'Popups', 'route' => 'admin.cms.popups.index', 'permission' => 'cms.page.view', 'module' => 'cms'],
```

Insert popup lang keys from Young Elephant `modules/Cms/resources/lang/{en,th}/admin.php` (`popups` through `popup_preview_auto_close`, plus `slug`, `slug_placeholder`, `status_draft`, `status_published`, `headline`, `subheadline`, `button_text`, `button_url`, `button_target`, `button_target_self`, `button_target_blank`, `priority`, `priority_hint`, `show_delay`, `auto_close`, `auto_close_hint`, `closable`, `timezone`).

Add nav labels:

- EN: `'popups' => 'Popups'`, `'admin_cms_popups_index' => 'Popups'`
- TH: `'popups' => 'ป๊อปอัป'`, `'admin_cms_popups_index' => 'ป๊อปอัป'`

- [ ] **Step 5: Run admin tests**

```bash
php artisan test --filter=PopupAdminTest
```

Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add modules/Cms config/admin.php resources/lang tests/Feature/Cms/PopupAdminTest.php docs/superpowers
git commit -m "$(cat <<'EOF'
feat(cms): add homepage popup admin CRUD

Port Young Elephant popups so merchants can schedule homepage overlays from Online Store.
EOF
)"
```

---

### Task 2: Storefront query, cache, homepage tests

**Files:**
- Modify: `modules/Cms/src/Services/HomeContentQueryService.php`
- Modify: `modules/Cms/src/Support/HomeContentCache.php`
- Modify: `modules/Cart/src/Services/StorefrontHomePageService.php`
- Create: `modules/Cart/resources/views/storefront/partials/home-popup.blade.php`
- Modify: `modules/Cart/resources/views/storefront/home.blade.php`
- Modify: `modules/Cart/resources/lang/{en,th}/storefront.php`
- Create: `tests/Feature/Storefront/StorefrontPopupTest.php`

**Interfaces:**
- Consumes: `Popup::currentlyVisible()`, `HomeContentCache::remember('popups', …)`
- Produces: `HomeContentQueryService::popups(): array` with keys `uuid`, `slug`, `type`, `headline`, `subheadline`, `imageUrl`, `imageSrcset`, `buttonText`, `buttonUrl`, `buttonTarget`, `showDelay`, `autoClose`, `closable`. `StorefrontHomePageService::build()` includes `'homePopups'`.

- [ ] **Step 1: Write the failing storefront test**

Create `tests/Feature/Storefront/StorefrontPopupTest.php` from Young Elephant, plus this extra case:

```php
public function test_home_skips_image_popup_without_media(): void
{
    Popup::query()->create([
        'title' => 'Broken image overlay',
        'slug' => 'broken-image-overlay',
        'status' => Popup::STATUS_PUBLISHED,
        'priority' => 1,
        'is_active' => true,
        'popup_type' => Popup::TYPE_IMAGE,
        'image_media_uuid' => null,
        'headline' => 'Should not render',
        'closable' => true,
        'timezone' => 'Asia/Bangkok',
    ]);

    $this->get('/')
        ->assertOk()
        ->assertDontSee('data-home-popup', false)
        ->assertDontSee('Should not render');
}
```

Media stub is the same anonymous `MediaQueryServiceInterface` as Young Elephant (`https://cdn.test/{uuid}.jpg`).

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test --filter=StorefrontPopupTest
```

Expected: FAIL (no `homePopups` / no markup).

- [ ] **Step 3: Wire query + cache + homepage**

Add `use Commerce\Cms\Models\Popup;` and these methods to `HomeContentQueryService` (copy `popups()` / `resolvePopups()` from Young Elephant `HomeContentQueryService.php`).

In `HomeContentCache::flushContent()`, add `'popups'` to the segment list. In `registerContentInvalidation()`, add `Popup::saved($flush); Popup::deleted($flush);`.

In `StorefrontHomePageService::build()` return array, add:

```php
'homePopups' => module_disabled('cms') ? [] : $this->homeContent->popups(),
```

Copy `home-popup.blade.php` from Young Elephant. Change `data-storage-key="ye:home-popup"` to `data-storage-key="commerce:home-popup"`.

In `home.blade.php`, after the homepage sections wrapper (still homepage-only), include:

```blade
@include('cart::storefront.partials.home-popup')
```

Add storefront strings next to `'close'`:

EN: `popup_hide_seven_days`, `popup_previous`, `popup_next`  
TH: `ไม่ต้องแสดงอีกเป็นเวลา 7 วัน`, `ป๊อปอัปก่อนหน้า`, `ป๊อปอัปถัดไป`

- [ ] **Step 4: Run storefront + isolation tests**

```bash
php artisan test --filter=StorefrontPopupTest
php artisan test --filter=HomepageIsolationTest
```

Expected: PASS. Isolation still forbids importing `Popup` in `StorefrontHomePageService`.

- [ ] **Step 5: Commit**

```bash
git add modules/Cms/src/Services/HomeContentQueryService.php modules/Cms/src/Support/HomeContentCache.php modules/Cart tests/Feature/Storefront/StorefrontPopupTest.php
git commit -m "$(cat <<'EOF'
feat(storefront): render scheduled homepage popups

Load published overlays on the home page only and skip image popups that have no media.
EOF
)"
```

---

### Task 3: Storefront JS/CSS and admin live preview

**Files:**
- Create: `resources/js/storefront/home-popup.js`
- Modify: `resources/js/storefront/home.js`
- Modify: `resources/css/storefront/home.css`
- Create: `resources/js/admin/popup-preview.js`
- Create: `resources/css/admin/popup-preview.css`
- Modify: `resources/js/admin.js`
- Modify: `resources/css/admin.css`

**Interfaces:**
- Consumes: `[data-home-popup]` root with `data-storage-key`, `data-show-delay`, `data-auto-close`
- Produces: `export const initHomePopups = () => void`; admin `initPopupPreview` on `[data-popup-form]`

- [ ] **Step 1: Copy client assets and retarget storage key**

Copy `home-popup.js` from Young Elephant. Replace default `'ye:home-popup'` with `'commerce:home-popup'` in both `persistDismiss` and `initHomePopups`.

At the top of `resources/js/storefront/home.js`:

```js
import { initHomePopups } from './home-popup.js';
```

Inside `initHome()`, call `initHomePopups();`.

Append Young Elephant’s `body.is-home-popup-open` / `.storefront-home-popup*` block (from `resources/css/storefront/home.css` ~1678–1884) to this repo’s `home.css`.

Copy `popup-preview.js` and `popup-preview.css`. Import in `admin.js` (`import './admin/popup-preview.js';`) and `admin.css` (`@import './admin/popup-preview.css';`).

- [ ] **Step 2: Re-run tests**

```bash
php artisan test --filter=PopupAdminTest
php artisan test --filter=StorefrontPopupTest
php artisan test --filter=HomepageIsolationTest
```

Expected: PASS.

- [ ] **Step 3: Commit**

```bash
git add resources/js resources/css
git commit -m "$(cat <<'EOF'
feat(storefront): add homepage popup overlay behavior

Show the overlay after delay, carousel multiple popups, and persist dismiss for the session or seven days.
EOF
)"
```

---

### Task 4: Spec status and full verification

- [ ] **Step 1: Mark spec approved**

Set `docs/superpowers/specs/2026-09-17-homepage-popup-design.md` status to `Approved for implementation`.

- [ ] **Step 2: Full related test run**

```bash
php artisan test --filter='PopupAdminTest|StorefrontPopupTest|HomepageIsolationTest|HomeContentAdminTest|StorefrontHomePageTest'
```

Expected: PASS.

- [ ] **Step 3: Commit spec/plan if still uncommitted**

```bash
git add docs/superpowers
git commit -m "$(cat <<'EOF'
docs: add homepage popup port spec and plan
EOF
)"
```

(Skip empty commit if already included in Task 1.)
