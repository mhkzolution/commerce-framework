# Commerce Framework

Modular commerce platform kernel for ecommerce, POS, inventory, and multi-channel retail.

**Version:** 1.0.0-alpha

## Quick Start (คำสั่งเดียว)

```bash
composer start
```

หรือ

```bash
npm start
```

คำสั่งนี้จะทำทุกอย่างให้อัตโนมัติ:

1. สร้าง `.env` (ถ้ายังไม่มี)
2. `composer install` + `npm install`
3. `migrate` + `seed`
4. build assets (ครั้งแรกเท่านั้น)
5. รัน **server** (port 1234) + **vite** + **queue** + **logs**

เปิดเบราว์เซอร์: **http://localhost:1234/admin/login**

| | |
|---|---|
| Email | `superadmin@example.com` |
| Password | `password` |

กด `Ctrl+C` เพื่อหยุดทุก process

## คำสั่งอื่น

| คำสั่ง | ใช้เมื่อ |
|---|---|
| `composer setup` | ติดตั้งครั้งแรก (ไม่รัน server) |
| `composer serve` | รัน PHP server อย่างเดียว |
| `php artisan commerce:modules` | ดู modules ที่เปิดใช้ |

## Requirements

- PHP 8.4+
- Composer
- Node.js 20+
- MySQL (local: port `8890`, user `root` / `root`)

## Architecture

See [ARCHITECTURE.md](ARCHITECTURE.md) and [FRAMEWORK.md](FRAMEWORK.md).

## Phase 3+ (IAM, Payment, Tenancy, Plugins)

| Capability | Highlights |
|---|---|
| **IAM** | API tokens (`/api/v1/auth/*`), TOTP 2FA, Google/GitHub OAuth, audit log |
| **Payment** | Stripe PaymentIntent + webhooks (`POST /webhooks/payment/stripe`) |
| **Multi-tenant** | `COMMERCE_TENANT_ENABLED=true`, header `X-Tenant` |
| **Plugins** | `plugins/hello-world`, `plugins/product-badge` (hook: `storefront.product.card`) |

```env
IAM_TWO_FACTOR_ENABLED=false
PAYMENT_GATEWAY=simulated          # or stripe
STRIPE_SECRET_KEY=sk_...
STRIPE_PUBLISHABLE_KEY=pk_...
STRIPE_WEBHOOK_SECRET=whsec_...
COMMERCE_TENANT_ENABLED=false
```

```bash
# API login
curl -X POST http://localhost:1234/api/v1/auth/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"superadmin@example.com","password":"password","device_name":"CLI"}'
```
