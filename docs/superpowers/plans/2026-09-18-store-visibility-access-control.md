# Store Visibility & Access Control Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans (inline in this session). User asked to implement the approved spec; do not wait for a second execution-choice prompt. Do not commit unless the user asks.

**Goal:** Add `store.visibility` with public/catalog/members/private behavior on the Laravel storefront.

**Architecture:** Settings owns the key and admin page. Cart owns resolver, middleware, and storefront UI. A `StoreAccessPolicyInterface` default-allows active customers so dealer groups can plug in later.

**Tech Stack:** PHP 8.4, Laravel, PHPUnit, Blade, existing Settings + customer guard.

## Global Constraints

- Default mode is `public` (current behavior).
- Login is `/account/login?redirect=`.
- Catalog must not leak prices in HTML, JSON-LD, data attributes, or storefront APIs.
- Private guests see Access Restricted (403), not a silent login redirect.
- Do not change unrelated features or cart math.

---

### Task 1: Capability core

**Files:**
- Create: `packages/commerce/contracts/src/Storefront/StoreVisibility.php`
- Create: `packages/commerce/contracts/src/Storefront/StorefrontAccessContext.php`
- Create: `packages/commerce/contracts/src/Storefront/StoreAccessPolicyInterface.php`
- Create: `modules/Settings/src/Services/StoreVisibilityConfig.php`
- Create: `modules/Cart/src/Services/StorefrontAccessResolver.php`
- Create: `modules/Cart/src/Services/AuthenticatedCustomerStoreAccessPolicy.php`
- Test: `tests/Unit/Storefront/StorefrontAccessResolverTest.php`

- [ ] Write failing resolver tests for public/catalog/members/private × guest/customer
- [ ] Implement enum, context, policy, config, resolver
- [ ] Bind scoped context + policy in Cart/Settings providers
- [ ] Register `store.visibility` via `ensureRegistered()` and SettingsSeeder

---

### Task 2: Admin setting

**Files:**
- Create: `modules/Settings/src/Http/Controllers/Admin/StoreVisibilityController.php`
- Create: `modules/Settings/src/Http/Requests/UpdateStoreVisibilityRequest.php`
- Create: `modules/Settings/resources/views/admin/store-visibility/index.blade.php`
- Modify: `modules/Settings/routes/web.php`, `config/admin.php`, nav + settings lang
- Test: `tests/Feature/Settings/StoreVisibilityAdminTest.php`, `tests/Feature/Admin/AdminNavigationIaTest.php`

- [ ] Failing tests: admin can view/save; invalid mode rejected; sidebar label
- [ ] Implement admin page (radio + descriptions)
- [ ] Add nav item under Shop display

---

### Task 3: Access middleware

**Files:**
- Create: `modules/Cart/src/Http/Middleware/EnsureStorefrontAccess.php`
- Create: `modules/Cart/src/Http/Middleware/EnsureStorefrontCanPurchase.php`
- Create: `modules/Cart/resources/views/storefront/access-restricted.blade.php`
- Create: `modules/Cart/resources/views/storefront/access-forbidden.blade.php`
- Test: `tests/Feature/Storefront/StoreVisibilityAccessTest.php`

- [ ] Failing tests: members guest redirect with redirect param; login returns to shop; private guest 403 page; denied policy 403; login/register still 200
- [ ] Append access middleware to `web`; alias purchase middleware on cart/checkout mutations

---

### Task 4: Catalog concealment

**Files:**
- Modify: product card, PDP, filters, toolbar, seo-meta, quick view, cart store, wishlist presenter
- Create: `resources/views/components/storefront/commerce/price-login-cta.blade.php`
- Test: `tests/Feature/Storefront/StoreVisibilityCatalogTest.php`

- [ ] Failing tests: guest catalog hides money + add/buy; no `data-product-price`; quick-view JSON nulls prices; cart POST denied; logged-in customer still sees prices
- [ ] Implement concealment via `$storeAccess->canViewPrices` / `canPurchase`

---

### Task 5: SEO + verification

- Members/private pages emit `noindex,nofollow`
- Public/catalog keep index unless the page already noindexed
- Run targeted PHPUnit, then broader storefront/settings/admin-nav suites
