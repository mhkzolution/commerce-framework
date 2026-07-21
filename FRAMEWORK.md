# Commerce Framework — Standards

## Framework Name

**Commerce Framework**

## Version

**1.0.0-alpha**

## Philosophy

A modular, domain-driven commerce platform kernel. Framework first, business second. Configuration over customization. Replaceability over inheritance.

## Architecture Style

Modular Monolith · Clean Architecture · Event-Driven Integration · CQRS-Lite

## Module Strategy

- One module per domain under `modules/`
- Identical internal structure (Product is the reference template)
- Enabled/disabled via `config/commerce.php`
- Manifest-driven discovery via `module.json`

## Package Strategy

- Kernel infrastructure in `packages/commerce/`
- `contracts` has zero dependencies
- Modules are Composer path repositories
- Plugins in `plugins/` with binding overrides

## Dependency Rules

1. Packages depend only on contracts/core
2. Modules communicate via contracts and events
3. No circular dependencies
4. Plugins depend on modules; never the reverse
5. Kernel modules have zero commerce dependencies

## Domain Rules

1. Explicit responsibilities and non-responsibilities per domain
2. Aggregates define consistency boundaries
3. Reference other aggregates by ID only
4. Business logic in Services only
5. `uuid` public, `id` internal, `tenant_id` for SaaS

## Event Rules

1. Past-tense naming (`ProductCreated`)
2. Dispatched from Services after persistence
3. Cross-module listeners in consuming module
4. `EventBusInterface` for all inter-module events
5. Critical events support outbox (future)

## Plugin Rules

1. Never modify module source
2. Override via container bindings only
3. UI extension via HookRegistry
4. Disabled = no routes, bindings, or listeners

## Coding Rules

PHP 8.4 · `strict_types=1` · PSR-12 · PHPStan L8 · Constructor injection · Thin controllers

## Folder Rules

```
packages/commerce/{contracts,core,support,module-manager,plugin-manager,api}
modules/{Iam,Settings,...}
plugins/
config/commerce.php
```

## API Rules

- Prefix: `/api/v1/`
- Envelope: `{ data, meta, links }`
- UUIDs in public API
- Opt-in includes: `?include=`

## Database Rules

- MySQL 8 · BIGINT PK · UUID public · Money as integer cents
- Soft deletes · `meta` JSON · Migrations owned by module

## Testing Rules

- Unit: Services (mocked repos)
- Feature: API + admin flows
- Tests live inside each module

## Security Rules

- RBAC via IAM · Encrypted secrets · Token hashing · 2FA support · Audit trail

## Performance Rules

- Query service eager loading · Cached permissions/settings · Async side effects via queues

## Platform Capabilities (Shared)

| Capability | Location | Contracts |
|---|---|---|
| Media | Platform module (future) | `MediaQueryServiceInterface` |
| SEO | `commerce/contracts` | `SeoServiceInterface`, `SlugServiceInterface` |
| Pricing | `commerce/contracts` | `PriceResolverInterface` |
| Search | `commerce/contracts` | `SearchIndexInterface`, `SearchQueryInterface` |
| Tax | `commerce/contracts` | `TaxCalculatorInterface` |
| Event Bus | `commerce/core` | `EventBusInterface` |

## Catalog vs Product (Future)

| Module | Owns |
|---|---|
| Catalog | Categories, Brands, Tags, Attributes, Attribute Sets |
| Product | Products, Variants, publish lifecycle, product-media associations |
