# Homepage popup (port from Young Elephant)

**Date:** 2026-09-17  
**Status:** Approved for implementation  
**Source:** `/Users/kritsadanambunraung/Projects/young_elephant` homepage popup  
**Target:** this repo (`commerce-framework`) Cms + Cart storefront

Port the Young Elephant homepage overlay 1:1: admin CRUD, scheduled visibility, homepage-only modal/carousel, session dismiss, and 7-day opt-out. Keep Young Elephant’s data shape and behavior. Change only what the framework already names differently (nav prefix, storage key).

**Later jobs (not this spec):** popups on other pages, free-form HTML / TipTap body, homepage-section toggle in the homepage editor, renaming columns to match Hero banners, syncing the change back into Young Elephant.

---

## Decisions

- Direct port of Young Elephant files into the same Cms/Cart/Vite slots. Do not reshape fields to Hero banner names (`starts_at`, `sort_order`).
- Storage key is `commerce:home-popup`, not `ye:home-popup`.
- Reuse `cms.page.view` / `cms.page.manage`. No popup-specific permission.
- Overlay is **not** a `HomepageSection`. It does not appear in the homepage section editor.
- Do not port `PopupSeeder`. It depends on Young Elephant sample catalog images and store copy. Feature tests create their own rows.
- Dismiss is one key for the whole carousel, not per popup.

---

## Data model

Table `cms_popups` (tenant-aware, soft deletes):

| Column | Role |
|---|---|
| `title`, `slug`, `status` (`draft` / `published`), `is_active`, `priority` | Admin identity and ordering (lower priority first) |
| `popup_type` | `image` / `content` / `promotion` |
| `headline`, `subheadline` | Plain text only. No HTML. |
| `image_media_uuid` | Media library UUID |
| `button_text`, `button_url`, `button_target` | CTA (`self` / `blank`) |
| `show_delay` | Seconds before open |
| `auto_close` | Seconds until auto-dismiss; `null` / `0` = stay open |
| `closable` | Close control + scrim + 7-day checkbox |
| `start_at`, `end_at`, `timezone` | Schedule; default timezone `Asia/Bangkok` |
| `meta` | Unused JSON column, kept for parity |

`currentlyVisible`: `status = published` AND `is_active` AND (`start_at` null or due) AND (`end_at` null or not expired), ordered by `priority`, then `id`.

Image-type rows with no resolvable media URL are skipped on the storefront. Content/promotion rows may render without an image.

---

## Admin

Custom Blade CRUD under **Online Store → Popups**, same pattern as Hero / Promo banners.

Routes (`admin.cms.popups.*`):

- View: `GET /admin/cms/popups`, `create`, `{popup}/edit`
- Manage: `POST /admin/cms/popups`, `PUT` / `DELETE /admin/cms/popups/{popup}`

Form sections:

1. Identity: title, slug (auto from title if empty), status, type, priority, active, closable
2. Content: media attach, headline, subheadline, button text/url/target. Type `image` requires `image_media_uuid`.
3. Timing: show delay (0–120s), auto close (empty/0–300s), start/end in the chosen timezone (persisted UTC), live preview (`data-popup-preview`)

Schedule inputs convert from the form timezone to UTC in `UpsertPopupRequest`, matching Young Elephant.

Save/delete flushes homepage cache, including a new `popups` segment.

---

## Storefront

Homepage only. `StorefrontHomePageService` passes `homePopups` from `HomeContentQueryService::popups()`. If the CMS module is disabled, pass `[]` and render nothing.

Markup: `cart::storefront.partials.home-popup` included from `home.blade.php`. Client: `resources/js/storefront/home-popup.js` via `initHomePopups()` from `home.js`. Styles live in `resources/css/storefront/home.css`.

Behavior:

- Several visible popups share one modal carousel (prev/next, dots, auto-advance every 5.5s unless `prefers-reduced-motion`).
- `show_delay` and `auto_close` come from the **first** popup in the ordered list.
- Image-only slide (type `image`, or no copy): the image wraps the CTA URL when present.
- Content/promotion slides show headline, subheadline, and CTA button. Promotion uses the darker promo treatment from Young Elephant CSS.
- Close button and scrim exist if any slide is `closable`. Scrim click closes only when a close button exists.
- Close without the checkbox → `sessionStorage` `commerce:home-popup:session` = `1`.
- Close with checkbox → `localStorage` `commerce:home-popup:hide-until` = now + 7 days.
- Storage failures (private mode) still close the overlay.

Payload keys from `resolvePopups()`: `uuid`, `slug`, `type`, `headline`, `subheadline`, `imageUrl`, `imageSrcset`, `buttonText`, `buttonUrl`, `buttonTarget`, `showDelay`, `autoClose`, `closable`.

---

## Architecture

```
Admin CRUD (PopupController → PopupService → Popup)
                 ↓ save/delete
        HomeContentCache::flushContent()  (+ popups segment)

GET /
  StorefrontHomePageService
    → HomeContentQueryService::popups()
    → home.blade.php includes home-popup partial
    → home.js → initHomePopups()
```

Cart isolation stays as today: homepage service reads popups only through `HomeContentQueryService`, never the `Popup` model.

---

## Files to add or touch

**Add (copy from Young Elephant, then change the storage key):**

- `modules/Cms/database/migrations/2026_09_17_200000_create_cms_popups_table.php` (same schema as Young Elephant; dated today so it runs after existing CMS migrations)
- `modules/Cms/src/Models/Popup.php`
- `modules/Cms/src/Services/PopupService.php`
- `modules/Cms/src/DTO/UpsertPopupData.php`
- `modules/Cms/src/Http/Requests/UpsertPopupRequest.php`
- `modules/Cms/src/Http/Controllers/Admin/PopupController.php`
- `modules/Cms/resources/views/admin/popups/{index,create,edit,_form,_preview}.blade.php`
- `modules/Cart/resources/views/storefront/partials/home-popup.blade.php`
- `resources/js/storefront/home-popup.js`
- `resources/js/admin/popup-preview.js`
- `resources/css/admin/popup-preview.css`
- `tests/Feature/Cms/PopupAdminTest.php`
- `tests/Feature/Storefront/StorefrontPopupTest.php`

**Touch:**

- `modules/Cms/src/CmsServiceProvider.php` — register `PopupService`
- `modules/Cms/routes/web.php` — popup routes next to hero/promo/faq
- `modules/Cms/src/Services/HomeContentQueryService.php` — `popups()` / `resolvePopups()`
- `modules/Cms/src/Support/HomeContentCache.php` — flush `popups`; invalidate on `Popup` save/delete
- `modules/Cart/src/Services/StorefrontHomePageService.php` — `homePopups`
- `modules/Cart/resources/views/storefront/home.blade.php` — include partial
- `modules/Cms/resources/lang/{en,th}/admin.php` — popup strings
- `modules/Cart/resources/lang/{en,th}/storefront.php` — hide/prev/next strings
- `resources/lang/{en,th}/nav.php` — sidebar labels
- `config/admin.php` — Online Store → Popups
- `resources/js/storefront/home.js` — import and call `initHomePopups()`
- `resources/css/storefront/home.css` — overlay styles
- `resources/js/admin.js` / `resources/css/admin.css` — preview assets

---

## Testing

Feature tests, same style as `HomeContentAdminTest` and Young Elephant’s popup tests.

**Admin (`PopupAdminTest`):**

- Authenticated admin can create a published image popup; empty slug becomes a slugified title.
- Update can switch type, copy, CTA target, active flag, priority, and auto-close.
- Create form includes media attach + live preview hooks.
- Edit form shows stored headline/subheadline/CTA in the preview.

**Storefront (`StorefrontPopupTest`):**

- Published active popups render `data-home-popup` slides and the 7-day copy.
- Several popups render carousel next control.
- Draft popups do not render.
- Published image-type popup with no `image_media_uuid` does not render.

**Also keep passing:** `HomepageIsolationTest` (no `Popup` model import in Cart homepage service).

No browser test for session/localStorage dismiss in this job.

---

## Error handling

- Validation failures stay on the admin form (required title; unique slug; image required for type `image`; `end_at` after or equal `start_at`; timezone must be valid).
- CMS module disabled: homepage still 200, no popup markup.
- Missing media for image type: skip that row. Missing media for content/promotion: still render copy.
- Client storage throws: overlay still closes.
