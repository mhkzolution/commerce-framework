# Store Visibility & Access Control v1

**Date:** 2026-09-18  
**Status:** Approved for implementation  
**Stack:** Laravel PHP (this repo). Spec types below are PHP, not TypeScript.

Add a store-wide visibility mode so merchants can run public retail, wholesale catalog, membership, or dealer-portal storefronts without changing cart/checkout internals.

---

## Decisions

- Setting key: `store.visibility` (`public` | `catalog` | `members` | `private`). Default `public`.
- Two layers: **route middleware** (who may enter) + **capability context** (who may see prices / purchase).
- Login URL is existing `storefront.account.login` (`/account/login?redirect=`), not `/login`.
- Search is `/shop?q=` and `/shop/suggest`. There is no `/categories/*` storefront route yet; middleware still matches those prefixes for the future.
- Catalog guests: hide prices in **HTML, data attributes, JSON-LD, and storefront APIs**, not UI-only.
- Private guests: **Access Restricted page** (403) with Login + Register. Do not silent-redirect to login.
- After login, `StoreAccessPolicyInterface` decides private entitlement. v1: any active authenticated customer is entitled.
- Future dealer/VIP/corporate groups rebind the policy. Do not add a fifth visibility mode.
- Public mode must keep current cart and checkout behavior.

---

## Modes

| Mode | Guest catalog | Guest prices | Guest purchase | SEO |
|---|---|---|---|---|
| `public` | yes | yes | yes | page default (`index,follow` unless already noindex) |
| `catalog` | yes | no | no | index allowed |
| `members` | redirect to login with `redirect=` | no | no | `noindex,nofollow` |
| `private` | Access Restricted page | no | no | `noindex,nofollow` |

Logged-in entitled customers see prices and can purchase in every mode.

---

## Architecture

```
store.visibility (Settings)
        ↓
StoreVisibilityConfig::mode()
        ↓
StorefrontAccessResolver + StoreAccessPolicyInterface
        ↓
StorefrontAccessContext (scoped per request)
   ├── EnsureStorefrontAccess (web middleware)
   ├── EnsureStorefrontCanPurchase (cart/checkout mutations)
   └── Views / Quick View / Wishlist / SEO robots
```

`StorefrontAccessContext` flags: `mode`, `authenticated`, `entitled`, `canViewCatalog`, `canViewPrices`, `canPurchase`.

`robots(?string $pageRobots)`: members/private always `noindex,nofollow`; otherwise keep the page directive.

---

## Route rules

**Skip:** `/admin`, `/api`, `/pos`, `/warehouse`, `/up`, account login/register (and oauth if added).

**Members** (guest only), catalog paths:

- `/shop`, `/shop/*`
- `/products`, `/products/*`
- `/brands`, `/brands/*`
- `/categories`, `/categories/*`
- `/search`, `/search/*`

Redirect: `/account/login?redirect=<currentUrl>`. Existing `StorefrontAuthRedirect` returns to that URL after login.

**Private:**

- Guest on any other storefront page → `cart::storefront.access-restricted` (403)
- Authenticated but `!entitled` → `cart::storefront.access-forbidden` (403)
- Entitled customer → full storefront
- `/account` remains available so denied members can log out

---

## Catalog concealment

When `!canViewPrices`:

- Product card / PDP / mobile buy bar: no formatted money, no compare/sale, no Add to Cart / Buy Now
- CTA: “เข้าสู่ระบบเพื่อดูราคา” + login button (with redirect)
- Omit `data-product-price`
- Quick View JSON: null price fields
- Wishlist JSON: null price
- Hide shop price filters and price sort options
- Block `POST /cart/items` and checkout (HTML → login redirect, JSON → 403)

When `canViewPrices` (logged-in catalog shopper, or public): existing UI unchanged.

---

## Admin

Online Store → Shop display → **Store access** (`admin.settings.store-visibility.show`).

Radio: Public, Catalog, Members Only, Private — each with a short description.

Permission: `settings.setting.view` / `settings.setting.update` (same as other Settings pages).

---

## Copy

Thai storefront (source of truth from product spec):

- Catalog CTA: `เข้าสู่ระบบเพื่อดูราคา` / button `เข้าสู่ระบบ`
- Private guest: `ร้านค้านี้เปิดให้เฉพาะสมาชิกที่ได้รับสิทธิ์` + `กรุณาเข้าสู่ระบบ`
- Private denied member: `คุณยังไม่มีสิทธิ์เข้าถึงร้านค้านี้`

English equivalents live in `storefront.php` / `settings::admin`.

---

## Out of scope

Customer groups, B2B/dealer price lists, per-product visibility, changing cart/checkout math, a new `/login` route.
