# Storefront Money Color, PDP Chrome, and Share Menu Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let admins set a storefront money color, apply it to payable amounts, make PDP CTAs follow Appearance primary, flex the PDP breadcrumb, and give desktop PDP share a Facebook/LINE/X/copy dropdown.

**Architecture:** `theme.money` joins the existing theme color group and injects `--color-money` through `ThemeDesignTokens`. Storefront CSS points payable selectors at that token. PDP CTAs and mobile buy-bar buttons use `--color-primary` instead of `--pdp-accent`. Share markup always includes four actions; JS opens the menu only at `min-width: 1024px`.

**Tech Stack:** Laravel 13, PHP 8.4, PHPUnit, Blade, Vite, storefront CSS custom properties.

**Spec:** `docs/superpowers/specs/2026-09-09-storefront-money-share-design.md`

## Global Constraints

- Dedicated `theme.money` → `--color-money`. Not Accent, not Primary.
- Default `#dc2626`. Empty HEX uses that default via CSS + skipped override.
- Payable amounts use `--color-money`. Compare-at stays `--color-muted` + line-through.
- Discount `%` badge stays `--color-danger`. `--color-danger` is not for payable prices.
- PDP add-to-cart / buy (and mobile buy-bar buttons) use `--color-primary` / `--color-primary-hover`, not `--pdp-accent`.
- PDP breadcrumb is flex + `/` like Shop. Do not add a Shop crumb or change trail order.
- Share breakpoint is viewport `min-width: 1024px`, not `navigator.share` presence.
- Desktop: dropdown always. Mobile: native share; abort or missing API → copy. No dropdown below 1024px.
- Do not change POS, admin money color, or CMS article share.

## File map

| File | Responsibility |
|---|---|
| `modules/Settings/src/Database/Seeders/SettingsSeeder.php` | Register `theme.money` |
| `modules/Settings/src/Http/Controllers/Admin/AppearanceController.php` | Color field metadata |
| `modules/Settings/src/Http/Requests/UpdateAppearanceRequest.php` | HEX validation |
| `modules/Settings/src/Support/ThemeDesignTokens.php` | Map `money` → `money` |
| `modules/Settings/resources/views/admin/appearance/index.blade.php` | Preview chip |
| `modules/Settings/resources/lang/{en,th}/admin.php` | Field labels |
| `resources/css/tokens/semantic-light.css` | `--color-money` fallback |
| `tests/Feature/Settings/AppearanceSettingsTest.php` | Save / reject / HTML |
| `resources/css/storefront/product-card.css` | Card payable color |
| `resources/css/storefront/pdp.css` | PDP price, CTAs, breadcrumb, share menu |
| `resources/css/storefront/shopper.css` | Cart / checkout / pay / confirmation / table totals |
| `resources/css/storefront/header.css` | Wishlist price |
| `tests/Unit/Storefront/StorefrontMoneyLayoutCssTest.php` | CSS contract |
| `resources/views/components/storefront/buttons/share-button.blade.php` | Menu markup |
| `resources/js/storefront/product.js` | Desktop menu vs mobile native share |
| `modules/Cart/resources/lang/{en,th}/storefront.php` | Share item labels |
| `tests/Feature/Storefront/StorefrontProductTest.php` | Share URLs on PDP HTML |

---

### Task 1: Appearance money token

**Files:**
- Modify: `tests/Feature/Settings/AppearanceSettingsTest.php`
- Modify: `modules/Settings/src/Database/Seeders/SettingsSeeder.php`
- Modify: `modules/Settings/src/Http/Controllers/Admin/AppearanceController.php`
- Modify: `modules/Settings/src/Http/Requests/UpdateAppearanceRequest.php`
- Modify: `modules/Settings/src/Support/ThemeDesignTokens.php`
- Modify: `modules/Settings/resources/views/admin/appearance/index.blade.php`
- Modify: `modules/Settings/resources/lang/en/admin.php`
- Modify: `modules/Settings/resources/lang/th/admin.php`
- Modify: `resources/css/tokens/semantic-light.css`

**Interfaces:**
- Consumes: existing theme color PUT + `ThemeDesignTokens::resolve(): array<string, string>`
- Produces: `theme.money` setting; resolve key `money` → CSS `--color-money`

- [ ] **Step 1: Write the failing tests**

Add to `tests/Feature/Settings/AppearanceSettingsTest.php`:

```php
public function test_admin_can_save_money_color(): void
{
    $this->actingAs(User::query()->first())
        ->put(route('admin.settings.appearance.update'), [
            'primary' => '#111827',
            'primary_hover' => '#0f172a',
            'primary_active' => '#020617',
            'accent' => '#db2777',
            'accent_hover' => '#be185d',
            'background' => '#f8fafc',
            'surface' => '#ffffff',
            'money' => '#00aa00',
        ])
        ->assertRedirect(route('admin.settings.appearance.show'));

    $this->assertSame('#00aa00', app(SettingQueryServiceInterface::class)->get('theme.money'));
    $this->assertSame('#00aa00', ThemeDesignTokens::resolve()['money'] ?? null);
}

public function test_invalid_money_hex_is_rejected(): void
{
    $this->actingAs(User::query()->first())
        ->from(route('admin.settings.appearance.show'))
        ->put(route('admin.settings.appearance.update'), [
            'primary' => '#111827',
            'primary_hover' => '#0f172a',
            'primary_active' => '#020617',
            'accent' => '#db2777',
            'accent_hover' => '#be185d',
            'background' => '#f8fafc',
            'surface' => '#ffffff',
            'money' => 'red',
        ])
        ->assertRedirect(route('admin.settings.appearance.show'))
        ->assertSessionHasErrors('money');
}

public function test_appearance_page_includes_money_token(): void
{
    $this->actingAs(User::query()->first())
        ->get(route('admin.settings.appearance.show'))
        ->assertOk()
        ->assertSee('--color-money', false)
        ->assertSee(__('settings::admin.appearance_color_money'), false);
}
```

Also extend `test_admin_can_view_appearance_settings` is unnecessary if the new HTML test covers it.

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=AppearanceSettingsTest`

Expected: new tests FAIL (`money` not saved / not on page). Existing two tests still PASS.

- [ ] **Step 3: Implement the token**

`SettingsSeeder` after `theme.accent_hover`:

```php
'theme.money' => ['type' => 'string', 'label' => 'Money color', 'group' => 'theme', 'default' => '#dc2626', 'is_public' => true],
```

`AppearanceController::COLOR_FIELDS` add:

```php
'money' => ['token' => 'money', 'label_key' => 'appearance_color_money', 'default' => '#dc2626'],
```

`UpdateAppearanceRequest` rules add `'money' => $hex`.

`ThemeDesignTokens` map add `'money' => 'money'`.

Lang:

- EN: `'appearance_color_money' => 'Money',`
- TH: `'appearance_color_money' => 'สีจำนวนเงิน',`

`semantic-light.css` after `--color-danger-subtle-foreground`:

```css
--color-money: #dc2626;
```

Appearance preview: add a chip next to the others:

```blade
<div class="rounded-lg border px-4 py-3 text-sm font-medium shadow-sm" data-preview="money" style="background: var(--color-surface); color: var(--color-money);">
    {{ __('settings::admin.appearance_color_money') }}
</div>
```

In `applyPreview`, when `token === 'money'`, set `preview.style.color = value` (not background).

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --filter=AppearanceSettingsTest`

Expected: PASS (5 tests).

- [ ] **Step 5: Commit**

```bash
git add tests/Feature/Settings/AppearanceSettingsTest.php \
  modules/Settings/src/Database/Seeders/SettingsSeeder.php \
  modules/Settings/src/Http/Controllers/Admin/AppearanceController.php \
  modules/Settings/src/Http/Requests/UpdateAppearanceRequest.php \
  modules/Settings/src/Support/ThemeDesignTokens.php \
  modules/Settings/resources/views/admin/appearance/index.blade.php \
  modules/Settings/resources/lang/en/admin.php \
  modules/Settings/resources/lang/th/admin.php \
  resources/css/tokens/semantic-light.css
git commit -m "$(cat <<'EOF'
feat: add appearance money color token

EOF
)"
```

---

### Task 2: Storefront money CSS + PDP buttons + breadcrumb

**Files:**
- Create: `tests/Unit/Storefront/StorefrontMoneyLayoutCssTest.php`
- Modify: `resources/css/storefront/product-card.css`
- Modify: `resources/css/storefront/pdp.css`
- Modify: `resources/css/storefront/shopper.css`
- Modify: `resources/css/storefront/header.css`

**Interfaces:**
- Consumes: `--color-money` from Task 1
- Produces: payable amounts, PDP CTAs, and PDP breadcrumb CSS matching the spec

- [ ] **Step 1: Write the failing CSS contract test**

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Storefront;

use PHPUnit\Framework\TestCase;

final class StorefrontMoneyLayoutCssTest extends TestCase
{
    public function test_payable_amounts_and_pdp_chrome_use_appearance_tokens(): void
    {
        $card = file_get_contents(dirname(__DIR__, 3).'/resources/css/storefront/product-card.css');
        $pdp = file_get_contents(dirname(__DIR__, 3).'/resources/css/storefront/pdp.css');
        $shopper = file_get_contents(dirname(__DIR__, 3).'/resources/css/storefront/shopper.css');
        $header = file_get_contents(dirname(__DIR__, 3).'/resources/css/storefront/header.css');

        $this->assertNotFalse($card);
        $this->assertNotFalse($pdp);
        $this->assertNotFalse($shopper);
        $this->assertNotFalse($header);

        $this->assertStringContainsString('.storefront-product-card__price', $card);
        $this->assertStringContainsString('color: var(--color-money)', $card);
        $this->assertStringNotContainsString(
            ".storefront-product-card__price {\n    font-size: 1rem;\n    font-weight: 600;\n    color: var(--color-danger);",
            $card,
        );
        $this->assertMatchesRegularExpression(
            '/\.storefront-product-card__compare\s*\{[^}]*color:\s*var\(--color-muted\)/s',
            $card,
        );

        $this->assertStringContainsString('color: var(--color-money)', $pdp);
        $this->assertStringContainsString('.storefront-buy-box__cta--cart', $pdp);
        $this->assertStringContainsString('var(--color-primary)', $pdp);
        $this->assertStringNotContainsString(
            '.storefront-buy-box__cta--cart {
    border: 1px solid var(--pdp-accent, var(--color-primary));',
            $pdp,
        );
        $this->assertStringContainsString(
            ".storefront-pdp .storefront-breadcrumb__list {\n    display: flex;",
            $pdp,
        );

        $this->assertStringContainsString('color: var(--color-money)', $shopper);
        $this->assertStringContainsString('color: var(--color-money)', $header);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=StorefrontMoneyLayoutCssTest`

Expected: FAIL (card still `--color-danger`, no `--color-money`, no PDP breadcrumb flex).

- [ ] **Step 3: Apply CSS**

`product-card.css` `.storefront-product-card__price` `color:` change to `var(--color-money)`. Leave `.storefront-product-card__compare` on `--color-muted`. Leave `.storefront-wishlist-btn--active` on `--color-danger`.

`pdp.css`:

1. Keep `--pdp-accent` on `.storefront-pdp--market` only if still used by the discount badge; otherwise set `.storefront-buy-box__discount` to `background: var(--color-danger)` and remove `--pdp-accent` from price/CTA/mobile-bar rules.

2. Replace:

```css
.storefront-buy-box__amount {
    font-size: 1.5rem;
    font-weight: 600;
    font-variant-numeric: tabular-nums;
    color: var(--color-money);
}

.storefront-buy-box__cta--cart {
    border: 1px solid var(--color-primary);
    background: var(--color-surface);
    color: var(--color-primary);
}

.storefront-buy-box__cta--cart:hover {
    background: color-mix(in srgb, var(--color-primary) 6%, var(--color-surface));
}

.storefront-buy-box__cta--buy {
    border: 1px solid var(--color-primary);
    background: var(--color-primary);
    color: var(--color-on-primary);
}

.storefront-buy-box__cta--buy:hover {
    background: var(--color-primary-hover);
    border-color: var(--color-primary-hover);
}
```

3. Mobile bar:

```css
.storefront-mobile-buy-bar__price {
    flex: 0 0 auto;
    min-width: 4.5rem;
    font-size: 0.9375rem;
    font-weight: 600;
    color: var(--color-money);
}

.storefront-mobile-buy-bar__button {
    flex: 1;
    min-height: 2.5rem;
    border-radius: 9999px;
    border: 1px solid var(--color-primary);
    font: inherit;
    font-size: 0.8125rem;
    font-weight: 500;
    cursor: pointer;
}

.storefront-mobile-buy-bar__button--cart {
    background: var(--color-surface);
    color: var(--color-primary);
}

.storefront-mobile-buy-bar__button--buy {
    background: var(--color-primary);
    color: var(--color-on-primary);
}
```

4. After `.storefront-pdp .storefront-breadcrumb { padding: ... }` add Shop-matching rules:

```css
.storefront-pdp .storefront-breadcrumb__list {
    display: flex;
    flex-wrap: wrap;
    gap: var(--space-8);
    margin: 0;
    padding: 0;
    list-style: none;
    font-size: 0.875rem;
}

.storefront-pdp .storefront-breadcrumb__item:not(:last-child)::after {
    content: "/";
    margin-inline-start: var(--space-8);
    color: var(--color-muted);
}

.storefront-pdp .storefront-breadcrumb__link,
.storefront-pdp .storefront-breadcrumb__current {
    color: var(--color-muted);
    text-decoration: none;
}

.storefront-pdp .storefront-breadcrumb__link:hover {
    color: var(--color-text);
}
```

`shopper.css` add `color: var(--color-money);` to:

```css
.storefront-cart-item__price,
.storefront-cart-item__total {
    margin: var(--space-8) 0 0;
    font-variant-numeric: tabular-nums;
    color: var(--color-money);
}

.storefront-cart__total {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 600;
    font-variant-numeric: tabular-nums;
    color: var(--color-money);
}

.storefront-pay__amount {
    margin: var(--space-8) 0 0;
    font-size: 1.75rem;
    font-weight: 600;
    font-variant-numeric: tabular-nums;
    color: var(--color-money);
}

.storefront-table__num {
    text-align: right;
    font-variant-numeric: tabular-nums;
    color: var(--color-money);
}

.storefront-confirmation__total {
    font-size: 1.125rem;
    font-weight: 600;
    font-variant-numeric: tabular-nums;
    color: var(--color-money);
}

.storefront-checkout__summary-toggle-total {
    font-variant-numeric: tabular-nums;
    color: var(--color-money);
}
```

`header.css`:

```css
.storefront-account-product__price {
    margin: 0;
    font-size: 0.875rem;
    font-variant-numeric: tabular-nums;
    color: var(--color-money);
}
```

Checkout subtotal / grand-total `<span>` / `#checkout-total-mobile` inherit from parent or add:

```css
.storefront-checkout__summary-body strong,
#checkout-total-mobile,
#checkout-subtotal {
    color: var(--color-money);
}
```

only if those nodes are not already inside a selector listed above. Do not color muted coupon discount lines if they use `.storefront-muted`.

- [ ] **Step 4: Run CSS test**

Run: `php artisan test --filter=StorefrontMoneyLayoutCssTest`

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add tests/Unit/Storefront/StorefrontMoneyLayoutCssTest.php \
  resources/css/storefront/product-card.css \
  resources/css/storefront/pdp.css \
  resources/css/storefront/shopper.css \
  resources/css/storefront/header.css
git commit -m "$(cat <<'EOF'
feat: apply money color and primary CTAs on storefront

EOF
)"
```

---

### Task 3: PDP share dropdown

**Files:**
- Modify: `tests/Feature/Storefront/StorefrontProductTest.php`
- Modify: `resources/views/components/storefront/buttons/share-button.blade.php`
- Modify: `resources/js/storefront/product.js`
- Modify: `resources/css/storefront/pdp.css`
- Modify: `modules/Cart/resources/lang/en/storefront.php`
- Modify: `modules/Cart/resources/lang/th/storefront.php`

**Interfaces:**
- Consumes: existing `share-button` props `url`, `title`; Task 2 PDP CSS file
- Produces: four share actions in markup; desktop menu at `min-width: 1024px`

- [ ] **Step 1: Write the failing HTTP test**

Add to `StorefrontProductTest`:

```php
public function test_pdp_share_menu_includes_facebook_line_x_and_copy(): void
{
    $variant = $this->createPurchasableProduct(price: 3200, stock: 3, sku: 'SHARE-001');
    $product = $variant->product;
    app(ProductSearchIndexer::class)->index($product->fresh(['variants', 'categories']));

    $html = $this->get(route('storefront.products.show', $product->slug))
        ->assertOk()
        ->getContent();

    $this->assertStringContainsString('facebook.com/sharer/sharer.php?u=', $html);
    $this->assertStringContainsString('social-plugins.line.me/lineit/share?url=', $html);
    $this->assertStringContainsString('twitter.com/intent/tweet?url=', $html);
    $this->assertStringContainsString('data-share-copy', $html);
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=test_pdp_share_menu_includes_facebook_line_x_and_copy`

Expected: FAIL (share URLs absent).

- [ ] **Step 3: Lang keys**

In both `modules/Cart/resources/lang/en/storefront.php` and `th/storefront.php` after `'share' => ...`:

EN:

```php
'share_facebook' => 'Facebook',
'share_line' => 'LINE',
'share_x' => 'X',
'share_copy' => 'Copy link',
'share_copied' => 'Copied',
```

TH:

```php
'share_facebook' => 'Facebook',
'share_line' => 'LINE',
'share_x' => 'X',
'share_copy' => 'คัดลอกลิงก์',
'share_copied' => 'คัดลอกแล้ว',
```

- [ ] **Step 4: Replace share-button markup**

```blade
@props([
    'url',
    'title' => null,
])

@php
    $shareUrl = rawurlencode($url);
    $shareTitle = rawurlencode((string) $title);
@endphp

<div {{ $attributes->class('storefront-share') }} data-share-root>
    <button
        type="button"
        class="storefront-share-btn"
        data-share-button
        data-share-url="{{ $url }}"
        data-share-title="{{ $title }}"
        aria-label="{{ __('storefront::storefront.share') }}"
        aria-expanded="false"
        aria-haspopup="menu"
        aria-controls="storefront-share-menu"
    >
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
            <path d="M4 12v7a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-7" />
            <path d="M16 6l-4-4-4 4" />
            <path d="M12 2v14" />
        </svg>
        <span class="storefront-share-btn__label">{{ __('storefront::storefront.share') }}</span>
    </button>
    <div class="storefront-share__menu" id="storefront-share-menu" data-share-menu hidden role="menu">
        <a class="storefront-share__item" role="menuitem" href="https://www.facebook.com/sharer/sharer.php?u={{ $shareUrl }}" target="_blank" rel="noopener noreferrer">
            {{ __('storefront::storefront.share_facebook') }}
        </a>
        <a class="storefront-share__item" role="menuitem" href="https://social-plugins.line.me/lineit/share?url={{ $shareUrl }}" target="_blank" rel="noopener noreferrer">
            {{ __('storefront::storefront.share_line') }}
        </a>
        <a class="storefront-share__item" role="menuitem" href="https://twitter.com/intent/tweet?url={{ $shareUrl }}&text={{ $shareTitle }}" target="_blank" rel="noopener noreferrer">
            {{ __('storefront::storefront.share_x') }}
        </a>
        <button type="button" class="storefront-share__item" role="menuitem" data-share-copy>
            {{ __('storefront::storefront.share_copy') }}
        </button>
    </div>
</div>
```

If multiple share buttons appear on one page, drop the hard-coded `id` and use a unique id per instance (e.g. `share-menu-{{ substr(md5($url), 0, 8) }}`) so `aria-controls` stays unique.

- [ ] **Step 5: Share CSS in `pdp.css`**

```css
.storefront-share {
    position: relative;
    display: inline-flex;
}

.storefront-share__menu {
    position: absolute;
    top: calc(100% + 0.25rem);
    left: 0;
    z-index: 6;
    min-width: 11rem;
    padding: 0.35rem;
    border: 1px solid var(--color-border);
    border-radius: 0.75rem;
    background: var(--color-surface);
    box-shadow: var(--shadow-sm, 0 1px 2px rgb(15 23 42 / 0.08));
}

.storefront-share__menu[hidden] {
    display: none;
}

.storefront-share__item {
    display: block;
    width: 100%;
    padding: 0.5rem 0.75rem;
    border: 0;
    border-radius: 0.5rem;
    background: transparent;
    color: var(--color-text);
    font: inherit;
    font-size: 0.8125rem;
    text-align: start;
    text-decoration: none;
    cursor: pointer;
}

.storefront-share__item:hover {
    background: var(--color-surface-muted);
}

@media (max-width: 1023px) {
    .storefront-share__menu {
        display: none !important;
    }
}
```

Keep existing `.storefront-share-btn` rules.

- [ ] **Step 6: Replace `initShare` in `product.js`**

```js
function isDesktopShare() {
    return window.matchMedia('(min-width: 1024px)').matches;
}

function copyShareUrl(url, copiedEl) {
    const markCopied = () => {
        copiedEl?.classList.add('storefront-share-btn--copied');
        window.setTimeout(() => copiedEl?.classList.remove('storefront-share-btn--copied'), 1500);
    };

    return navigator.clipboard.writeText(url).then(markCopied).catch(() => {
        window.prompt('Copy link:', url);
    });
}

function initShare(root) {
    root.querySelectorAll('[data-share-root]').forEach((wrap) => {
        const button = wrap.querySelector('[data-share-button]');
        const menu = wrap.querySelector('[data-share-menu]');
        const copyBtn = wrap.querySelector('[data-share-copy]');
        if (!button) {
            return;
        }

        const close = () => {
            if (!menu) {
                return;
            }
            menu.hidden = true;
            button.setAttribute('aria-expanded', 'false');
        };

        const open = () => {
            if (!menu) {
                return;
            }
            menu.hidden = false;
            button.setAttribute('aria-expanded', 'true');
        };

        button.addEventListener('click', async (event) => {
            event.preventDefault();
            const url = button.dataset.shareUrl;
            const title = button.dataset.shareTitle;

            if (isDesktopShare()) {
                if (menu?.hidden) {
                    open();
                } else {
                    close();
                }
                return;
            }

            if (navigator.share) {
                try {
                    await navigator.share({ title, url });
                    return;
                } catch {
                    // fall through to copy
                }
            }

            await copyShareUrl(url, button);
        });

        copyBtn?.addEventListener('click', async () => {
            await copyShareUrl(button.dataset.shareUrl, button);
            close();
        });

        document.addEventListener('click', (event) => {
            if (!wrap.contains(event.target)) {
                close();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                close();
            }
        });
    });
}
```

- [ ] **Step 7: Run PDP tests**

Run: `php artisan test --filter='StorefrontProductTest|StorefrontMoneyLayoutCssTest|AppearanceSettingsTest'`

Expected: PASS.

- [ ] **Step 8: Commit**

```bash
git add tests/Feature/Storefront/StorefrontProductTest.php \
  resources/views/components/storefront/buttons/share-button.blade.php \
  resources/js/storefront/product.js \
  resources/css/storefront/pdp.css \
  modules/Cart/resources/lang/en/storefront.php \
  modules/Cart/resources/lang/th/storefront.php
git commit -m "$(cat <<'EOF'
feat: add desktop PDP share dropdown

EOF
)"
```

---

## Spec coverage

| Spec requirement | Task |
|---|---|
| `theme.money` / `--color-money` / Appearance field | 1 |
| Payable amounts storefront-wide | 2 |
| Compare-at muted | 2 |
| PDP CTAs + mobile bar primary | 2 |
| Discount badge stays danger | 2 |
| PDP breadcrumb flex | 2 |
| Share Facebook / LINE / X / copy | 3 |
| Desktop 1024px menu; mobile native share | 3 |
| Save / reject / HTML tests | 1 |
| CSS contract | 2 |
| PDP HTML share test | 3 |

## Self-review

- Wishlist price lives in `header.css`, not `shopper.css` — Task 2 uses the real file.
- Mobile buy-bar buttons have their own classes (not `storefront-buy-box__cta`); Task 2 restyles them too.
- Discount badge is switched to `--color-danger` so removing `--pdp-accent` from CTAs does not restyle the sale chip as primary.
