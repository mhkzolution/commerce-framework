#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

mkdir -p packages/commerce/{contracts,core,support,module-manager,plugin-manager,api}/src
mkdir -p packages/commerce/core/{config,tests}
mkdir -p packages/commerce/module-manager/src/Commands
mkdir -p packages/commerce/api/src/{Middleware,Resources,Responses,Exceptions}
mkdir -p packages/commerce/core/src/{Base,Concerns,Support,Enums,Exceptions,Events,Hooks,Outbox}
mkdir -p packages/commerce/contracts/src/{Authorization,Channel,Event,Hook,Identifiable,Media,Module,Notification,Plugin,Pricing,Purchasable,Query,Repository,Search,Seo,Service,Settings,Tax,Tenant,ValueObject}
mkdir -p packages/commerce/support/src/{DTO,Enums,Collections}
mkdir -p modules/Iam/{config,database/{migrations,seeders,factories},routes,resources/{views/admin,lang/en},src/{Contracts/{Authentication,Authorization,Profile,Session,Token,TwoFactor,Security,Activity,Impersonation,Preferences},Services,Repositories,DTO,Events,Listeners,Policies,Http/{Controllers/{Admin,Api/V1},Requests,Resources},Exceptions},tests/{Feature,Unit}}
mkdir -p modules/Settings/{config,database/{migrations,seeders},routes,resources/{views/admin,lang/en},src/{Contracts,Services,Repositories,DTO,Events,Listeners,Policies,Http/{Controllers/{Admin,Api/V1},Requests,Resources},Exceptions},tests/{Feature,Unit}}
mkdir -p plugins stubs/module config/modules

touch plugins/.gitkeep

echo "Directories created."
