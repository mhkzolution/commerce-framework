# Storefront money color, PDP chrome, and share menu

**Date:** 2026-09-09  
**Status:** Approved for implementation  
**Owner:** Appearance (`theme` settings) + storefront PDP/shopper CSS + `share-button`  
**Related:** `docs/superpowers/specs/2026-09-09-pdp-breadcrumb-badge-design.md`

Authors set a dedicated money color in `/admin/settings/appearance`. Payable amounts on the customer storefront use that color. PDP add-to-cart / buy buttons follow Appearance primary. PDP breadcrumb is a flex row like Shop. Desktop share opens a dropdown (Facebook, LINE, X, copy link); mobile keeps the native share sheet.

**Later jobs (not this spec):** POS money color, CMS article share, Shop listing breadcrumb trail, category moves.

---

## Decisions

- Dedicated `theme.money` → `--color-money`. Not Accent, not Primary.
- Default `#dc2626` (current `--color-danger`) so existing shops do not jump until an admin picks a color.
- Payable amounts storefront-wide use `--color-money`. Strikethrough / compare-at stays `--color-muted`.
- Discount `%` badge on PDP stays as it is today. `--color-danger` remains for errors / out of stock, not prices.
- PDP CTAs stop using `--pdp-accent` (`--color-danger`). Cart = primary outline. Buy = primary fill.
- PDP breadcrumb CSS matches Shop flex + `/` separators. Trail content stays Home → Parent → Child → Product (existing breadcrumb spec).
- Share breakpoint is **viewport `min-width: 1024px`**, not `navigator.share` presence.
- Desktop: always the four-item dropdown. Mobile: native share; if missing or the shopper cancels, copy the URL (current fallback). Do not show the dropdown below 1024px.
- CMS blog share block is unchanged.

---

## Appearance token

| Setting key | CSS token | Default | Empty value |
|---|---|---|---|
| `theme.money` | `--color-money` | `#dc2626` | treat as default (same HEX regex as other theme colors) |

Register in `SettingsSeeder` (`group` theme, `is_public` true). Add to `AppearanceController::COLOR_FIELDS`, `UpdateAppearanceRequest`, `ThemeDesignTokens` map, appearance preview chip, and `resources/css/tokens/semantic-light.css` (`--color-money: #dc2626`).

Labels: TH `สีจำนวนเงิน`, EN `Money`. Show `--color-money` under the field like other colors.

`ThemeDesignTokens::resolve()` injects the override into `:root` via `components.admin.design-tokens` (already used on storefront + admin layouts). No new injection path.

---

## Money on the storefront

Payable amount = the figure the shopper pays or a line/order total. Not the crossed-out compare price.

Apply `color: var(--color-money)` to existing payable selectors, including:

- `.storefront-product-card__price` (today `--color-danger`)
- `.storefront-buy-box__amount` and `.storefront-mobile-buy-bar__price` (today `--pdp-accent`)
- Cart unit / line / subtotal / grand totals (`.storefront-cart-item__price`, `.storefront-cart-item__total`, `.storefront-cart__total`, and the same totals on checkout / confirmation)
- `.storefront-pay__amount`
- `.storefront-account-product__price` (wishlist)
- Order/account payable cells (`.storefront-table__num` on order totals, confirmation totals)

Keep `--color-muted` + `line-through` on compare-at (`.storefront-product-card__compare`, `.storefront-buy-box__compare`).

JS that only swaps formatted text inside those nodes does not set color; CSS on the node is enough.

Admin UI and POS stay on existing tokens.

---

## PDP buttons

Remove `--pdp-accent` / `--pdp-accent-hover` from CTA and price rules on `.storefront-pdp--market`.

| Control | Idle | Hover |
|---|---|---|
| Add to cart | border + text `--color-primary`, surface fill | primary mixed ~6% on surface |
| Buy now | background + border `--color-primary`, `--color-on-primary` text | `--color-primary-hover` |

Focus ring stays `--color-ring`. Mobile buy-bar CTAs that share these classes follow the same tokens.

---

## PDP breadcrumb

On `.storefront-pdp .storefront-breadcrumb__list` (same pattern as `.storefront-shop__breadcrumb`):

- `display: flex; flex-wrap: wrap; gap: var(--space-8); list-style: none; margin: 0; padding: 0;`
- `::after` `/` on items except last
- links/current: muted; hover to `--color-text`

Do not add a Shop crumb. Blade `x-storefront.breadcrumb` markup stays.

---

## Share menu

Keep `resources/views/components/storefront/buttons/share-button.blade.php`. Wrap the existing button with a menu in the same component.

Markup always includes the four actions (so HTTP tests can see them). CSS/JS hide and skip opening the menu below 1024px.

| Item | Action |
|---|---|
| Facebook | `https://www.facebook.com/sharer/sharer.php?u={url}` |
| LINE | `https://social-plugins.line.me/lineit/share?url={url}` |
| X | `https://twitter.com/intent/tweet?url={url}&text={title}` |
| Copy link | `navigator.clipboard.writeText(url)`; copied state ~1.5s; `window.prompt` if clipboard fails |

Social links: `target="_blank"` `rel="noopener noreferrer"`.

Desktop: click toggles the menu (`aria-expanded`). Click outside and Escape close it.

Mobile (`max-width: 1023px`): click still runs `navigator.share({ title, url })` when available; abort or missing API → copy. Do not open the dropdown.

Lang keys on Cart storefront (TH/EN): Facebook, LINE, X, คัดลอกลิงก์ / Copy link, plus copied.

---

## Surfaces

| File | Change |
|---|---|
| `modules/Settings/src/Database/Seeders/SettingsSeeder.php` | `theme.money` |
| `AppearanceController`, `UpdateAppearanceRequest`, appearance Blade + lang | Field + preview |
| `ThemeDesignTokens` | `money` → `money` |
| `resources/css/tokens/semantic-light.css` | `--color-money` |
| `resources/css/storefront/product-card.css` | Payable vs compare |
| `resources/css/storefront/pdp.css` | Price, CTAs, breadcrumb flex, share menu |
| `resources/css/storefront/shopper.css` | Cart / checkout / pay / wishlist / account payable amounts |
| `share-button.blade.php` + `resources/js/storefront/product.js` | Menu + breakpoint |
| Cart `storefront.php` lang | Share item labels |

---

## Tests (TDD)

Failing tests first.

1. **Save money color** — PUT appearance with `money` `#00aa00` (plus existing required colors). `theme.money` and `ThemeDesignTokens::resolve()['money']` are `#00aa00`.
2. **Reject bad HEX** — invalid `money` does not persist; same regex message as other colors.
3. **Appearance HTML** — show page includes the money field / `--color-money`.
4. **CSS contract** — product-card payable uses `--color-money` not `--color-danger`; compare does not use `--color-money`; PDP CTAs use `--color-primary` and do not use `--pdp-accent` / `--color-danger`; PDP breadcrumb list includes `display: flex`.
5. **PDP HTML** — product show contains Facebook, LINE, X share URLs and a copy-link control.

Existing Appearance and PDP tests stay green.

---

## Error handling

- Empty money HEX → stored null / default `#dc2626` at resolve time (same as other theme colors).
- Invalid HEX → validation error, no partial save of that field.
- Clipboard failure → prompt with the URL.
- Native share abort → copy fallback, not an error toast.

---

## Non-goals

- POS / admin money color
- CMS article share buttons
- Shop `/shop` breadcrumb trail content
- Changing category assignment or PDP crumb order
- Using Accent as money
- Native share on desktop even when the API exists
