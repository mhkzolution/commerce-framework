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

## Phase E (Production & Domain Maturity)

| Capability | Highlights |
|---|---|
| **Tenant scoping** | `BelongsToTenant` on Product, Order, Customer, User, CMS |
| **Outbox** | `dispatchReliable()` + `php artisan commerce:outbox:publish` |
| **IAM admin** | `/admin/iam/security`, `/admin/iam/audit-logs` |
| **Tenant admin** | `/admin/platform/tenants` |
| **CMS services** | `PageService` / `PostService` with slug + URL redirects |

## Phase F (Commerce Core & Storefront)

| Capability | Highlights |
|---|---|
| **Tax** | `DatabaseTaxCalculator` + `TaxContext` / `TaxLine` DTOs |
| **Channel** | `ChannelContext` + `ResolveChannel` middleware (`X-Channel`, path, query) |
| **Tier pricing** | `product_variant_price_tiers` + quantity-aware `CompositePriceResolver` |
| **Catalog SEO** | Category/Brand slugs, redirects, storefront pages (`/categories/{slug}`, `/brands/{slug}`) |
| **CRM services** | `LeadService` / `DealService`, qualify lead, `LeadCreated` / `DealWon` events |
| **Impersonation** | Admin impersonate user + amber banner; `POST /admin/impersonation/stop` |
| **Outbox auto-publish** | `commerce.outbox.auto_publish=true` publishes after DB commit |

```env
COMMERCE_CHANNEL_DEFAULT=web
COMMERCE_OUTBOX_AUTO_PUBLISH=true
```

```bash
# Resolve channel via header
curl -H 'X-Channel: wholesale' http://localhost:1234/api/v1/products
```

## Phase G (CRM Pipeline, POS Sessions, Marketplace Portal)

| Capability | Highlights |
|---|---|
| **CRM pipeline** | Board view `/admin/crm/deals/board`, stage transitions, lead→deal convert |
| **POS sessions** | Explicit open/close with opening float, cash sales tracking, variance on close |
| **POS cash** | `pos_cash_movements` ledger; orders store `pos_session_uuid` in meta |
| **Seller portal** | `/seller` dashboard for linked users (`user_uuid` on seller) |
| **Payouts** | Request payout from portal; admin mark paid at `/admin/marketplace/payouts` |

```bash
# Open POS session with opening float (cents)
POST /admin/pos/terminal/{register}/open

# Seller portal (user must be linked to active seller)
GET /seller
POST /seller/payouts/request
```

## Phase H (Media Phase 2)

| Capability | Highlights |
|---|---|
| **URL import** | `POST /admin/media/import` and `POST /api/v1/media/import` |
| **S3-ready storage** | `MEDIA_DISK=s3`; variant generator streams via temp files |
| **Tenant scoping** | `BelongsToTenant` on `Media` + `MediaFolder` |
| **File attach component** | `<x-media::file-attach>` — drag-drop upload, URL import, library picker |

```env
MEDIA_DISK=public   # or s3
AWS_ACCESS_KEY_ID=...
AWS_SECRET_ACCESS_KEY=...
AWS_DEFAULT_REGION=...
AWS_BUCKET=...
```

```blade
@include('media::components.file-attach', [
    'name' => 'logo_media_uuid',
    'value' => $brand->logo_media_uuid,
    'label' => 'Brand logo',
    'imagesOnly' => true,
    'folderUuid' => null,
])
```

## Phase I (Headless API, Refunds, Gateways)

| Capability | Highlights |
|---|---|
| **Headless checkout** | `POST /api/v1/cart/checkout` returns `order`, `payment`, `payment_initiation` |
| **Payment API** | `GET /payments/config`, `POST /payments/{uuid}/initiate`, `POST /payments/{uuid}/pay` |
| **Refunds** | `PaymentService::refund()`, admin + `POST /api/v1/payments/{uuid}/refund` |
| **Gateways** | Simulated + Stripe with `refund()`; webhook handles `charge.refunded` |
| **Orders API** | `GET /orders`, `POST /orders/{uuid}/cancel` |
| **Customers API** | `PATCH /customers/{uuid}`, address delete/default |
| **Products API** | `GET /products?search=` |

```env
PAYMENT_GATEWAY=simulated   # or stripe
PAYMENT_SIMULATE_GATEWAY=true
```

```bash
# Headless flow
curl -X POST /api/v1/cart/items -d '{"purchasable_uuid":"...","quantity":1}'
curl -X POST /api/v1/cart/checkout -d '{...}'
curl -X POST /api/v1/payments/{uuid}/pay          # simulated
curl -X POST /api/v1/payments/{uuid}/initiate     # stripe client_secret
```

## Phase J (Teams, Elasticsearch, Plugin CLI, Outbox Worker)

| Capability | Highlights |
|---|---|
| **IAM Teams** | `iam_teams` + members; admin `/admin/iam/teams`; `X-Team` header + `TeamContext` |
| **Elasticsearch** | `SEARCH_DRIVER=elasticsearch`; HTTP driver with DB fallback on query |
| **Plugin CLI** | `commerce:plugin:list`, `enable`, `disable`, `make` |
| **Outbox worker** | `PublishOutboxJob` queue + `commerce:outbox:publish` scheduled every minute |

```env
IAM_TEAMS_ENABLED=true
SEARCH_DRIVER=database          # or elasticsearch
ELASTICSEARCH_HOST=http://localhost:9200
COMMERCE_OUTBOX_USE_QUEUE=true
COMMERCE_OUTBOX_AUTO_PUBLISH=true
```

```bash
php artisan commerce:plugin:list
php artisan commerce:plugin:make MyPlugin
php artisan commerce:plugin:enable hello-world
php artisan commerce:outbox:publish --queue
```

## Optional (Token cart, API includes, tenant scoping)

| Capability | Highlights |
|---|---|
| **Token cart** | `X-Cart-Token` header; issued on first cart mutation; stored in `cart_tokens` |
| **API includes** | `?include=variants,categories` on products; `line_items` on orders; `addresses` on customers |
| **Tenant scoping** | `BelongsToTenant` on Payment, CRM, POS, Marketplace, Catalog children, etc. |

```env
CART_TOKEN_ENABLED=true
CART_TOKEN_HEADER=X-Cart-Token
CART_TOKEN_TTL_DAYS=30
```

```bash
# Headless cart without session
curl -X POST /api/v1/cart/items -d '{"purchasable_uuid":"...","quantity":1}' -D -
# reuse X-Cart-Token from response header

curl '/api/v1/products/{slug}?include=variants,media'
curl '/api/v1/orders/{uuid}?include=line_items'
curl '/api/v1/customers/{uuid}?include=addresses'
```
