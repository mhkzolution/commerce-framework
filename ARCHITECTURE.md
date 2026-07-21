# Commerce Framework — Architecture

**Version:** 1.0.0-alpha  
**Philosophy:** Domain-driven modular monolith. Framework first, business second.

## Architectural Refinements (v1.0.0-alpha)

### 1. Media — Shared Platform Capability

Media is **not** part of Catalog. It lives as a **platform module** (Priority 10) alongside IAM and Settings.

- Owns file upload, storage drivers, media library
- Consumed by Product, CMS, IAM (avatar), Brand, etc.
- Contracts: `MediaQueryServiceInterface`, `MediaUploadServiceInterface`

### 2. SEO — Shared Platform Capability

SEO is **not** a business module. It is a **shared capability** in `commerce/contracts` + `commerce/core`.

- `SeoServiceInterface`, `SlugServiceInterface`, `UrlRedirectServiceInterface`
- Polymorphic `SeoEntry` pattern in core
- Product, Category, CMS modules **use** SEO contracts; they do not own SEO infrastructure

### 3. Catalog vs Product Separation

| Module | Owns | Priority |
|---|---|---|
| **Catalog** (future) | Categories, Brands, Tags, Attributes, Attribute Sets | 20 |
| **Product** (future) | Products, Variants, product-media associations, publish lifecycle | 20 |

Catalog provides taxonomy and attribute infrastructure. Product provides sellable entities.

### 4. Pricing — Platform Contract

Pricing is a **replaceable capability** via contracts (implementation deferred):

- `PriceResolverInterface` — resolve price for a purchasable in a context
- `PricingContextInterface` — channel, customer group, quantity, date
- `PriceQuoteInterface` — resolved amount, currency, breakdown

Product stores **base price** on variants. Promotions, tiers, and dynamic pricing override via `PriceResolverInterface`.

### 5. Search — Platform Contract

Search is **replaceable** (database today, Elasticsearch tomorrow):

- `SearchIndexInterface` — index, update, delete documents
- `SearchQueryInterface` — query with filters, facets, pagination
- `SearchResultInterface` — normalized result set

### 6. Event Bus — Platform Abstraction

Inter-module communication uses `EventBusInterface` (in `commerce/contracts`), implemented by `EventBus` in `commerce/core`.

- Wraps Laravel events with domain event conventions
- Supports sync/async dispatch
- Foundation for outbox pattern

### 7. Tax — Platform Contract

Tax calculation is **pluggable**:

- `TaxCalculatorInterface` — calculate tax lines for a basket/order context
- `TaxContextInterface` — addresses, customer type, line items
- `TaxLineInterface` — rate, amount, label

## Module Layers

```
Priority 10 — Platform Kernel
  IAM, Settings, Media

Priority 20 — Catalog & Product
  Catalog, Product

Priority 30 — Commerce
  Customers, Inventory, Orders

Priority 40 — Channels
  Payment, Notifications, CMS, Reports

Priority 50 — POS

Priority 100 — Plugins
```

## Package Dependency Graph

```
contracts → (none)
support → contracts
core → contracts, support
module-manager → core
plugin-manager → core
api → core
modules → packages
plugins → modules + packages
```
