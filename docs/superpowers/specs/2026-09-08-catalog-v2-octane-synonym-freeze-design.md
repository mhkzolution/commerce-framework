# Catalog V2 Follow-up: Octane synonym freeze

**Date:** 2026-09-08  
**Status:** Draft for review  
**Owner:** Product search (`modules/Product`) — `SearchSynonymExpander`  
**Related:** `docs/superpowers/specs/2026-09-08-catalog-v2-search-discovery-design.md`

This spec is one implementation unit: **the directed synonym map is loaded once per container/worker lifetime and is never refreshed on the request path or on admin CUD.** Source of truth stays `product_search_synonyms`. It does not add Octane as a Composer dependency, a reload command, request-time version checks, or index/suggest/ranking changes.

It amends Discovery V2 acceptance 10 for long-lived workers.

---

## 1. Problem

`SearchSynonymExpander` is a Laravel singleton. The constructor loads `product_search_synonyms` into an in-memory `from_term → to_term` map. `expand()` already reads only that array.

That is correct for php-fpm (new container per request). On Octane the same singleton lives for the worker. Today that freeze is accidental: a worker that has already resolved the expander ignores later admin CUD; a worker that has not yet resolved it loads the latest DB on first search. Tests paper over this with `app()->forgetInstance(SearchSynonymExpander::class)` after inserts.

Discovery V2 said CUD does not rewrite `search_documents` and “the next search uses the new expansion.” That is true for a new container. It is not a fleet-wide Octane guarantee.

---

## 2. Goals

1. Request-path search expansion is deterministic: memory only, no DB / cache / config rebuild.
2. Worker state is immutable for the worker’s lifetime. Refresh is an explicit process recreate (`octane:reload` or a new php-fpm container).
3. Admin CUD still writes the table only. No `forgetInstance`, no worker-local invalidation.
4. Directed replace, unique `from_term`, and “CUD does not reindex” stay as Discovery V2.

Non-goals: `search:synonyms:reload`, broadcasting to workers, moving synonyms to config, adding `laravel/octane` to Composer, synonym groups / reverse auto-map, index ops, Suggest V1.1, merchandising, admin copy telling staff to reload.

---

## 3. Locked decisions

| Topic | Decision |
|---|---|
| Source of truth | `product_search_synonyms` + existing admin CUD. Do not move to `config/search.php`. |
| Binding | Keep `SearchSynonymExpander` as a **singleton**. Do not bind it transient. |
| Load | Constructor is the only place that queries `product_search_synonyms`. It runs **exactly once** per container lifecycle. |
| `expand()` | Memory only. No DB, no `Cache::remember`, no config rebuild. |
| CUD | Write DB only. Controllers, model, requests: no `forgetInstance`, no `make()` of a fresh expander, no version bump for search. |
| php-fpm | New request = new container = constructor runs again = map from DB at that construct. |
| Octane | A worker does not refresh the map during its lifetime. It observes synonym changes only after that worker/container is recreated (`octane:reload` or a new worker process). |
| Fleet identity | Do **not** require “all workers always hold identical maps.” Octane may spawn a worker later; that new worker constructs against current DB. Consistency of the whole fleet after a coordinated restart is `octane:reload`, not a runtime invariant. |
| Eager warm | Octane-only. See §4. |
| Composer | Do not add `laravel/octane`. |
| Reload command | None. Manual `php artisan octane:reload` after deploy/ops. |
| Tests | `forgetInstance` is allowed **only in tests**, as the stand-in for container recreate. Production CUD paths must not call it. |
| Discovery §7 / acceptance 10 | CUD still does not rewrite `search_documents`. Replace “the next search uses the new expansion” with the php-fpm / Octane wording in §5. Update that sentence in the Discovery spec as part of this unit. |

---

## 4. Eager warm (Octane-only)

```text
No unconditional app()->make(SearchSynonymExpander::class)
inside provider boot methods.

Warm only when:
- class_exists(\Laravel\Octane\Events\WorkerStarting::class)
- running under Octane worker lifecycle
```

Listen to `Laravel\Octane\Events\WorkerStarting` only behind `class_exists`. The listener resolves `SearchSynonymExpander` so the map is loaded at worker start, not on the first search.

Do not warm from `ProductServiceProvider::boot()` / `register()` without that guard. Unconditional `make()` breaks `migrate` and `RefreshDatabase` (table missing at boot).

Until Octane is installed, `class_exists` is false: php-fpm stays “construct on first resolve in this request,” which is still once per container.

---

## 5. Flow

```text
container/worker start
  → (Octane) WorkerStarting → resolve expander → constructor queries table once
  → (php-fpm) first resolve in this request → constructor queries table once

expand()
  → in-memory map only

admin CUD
  → insert/update/delete product_search_synonyms
  → search_documents unchanged
  → expander instance unchanged

worker/container recreated
  → constructor runs again
  → new map
```

---

## 6. Tests (acceptance)

Keep existing directed-replace, unique `from_term`, admin CUD, and “CUD does not touch `search_documents`” tests green. Those tests may still `forgetInstance` **after** inserts when they intend to observe the new row (reload stand-in).

Add:

1. **Constructor once per container.** Resolve `SearchSynonymExpander` twice, then call `expand()`. Query log: first resolve queries `product_search_synonyms`; second resolve does not; `expand()` does not. Proves the singleton freeze and that the binding did not become transient.
2. **CUD does not refresh a live expander.** Resolve expander (empty or old map) → create/update a synonym → `expand()` on the **same** instance/container **without** `forgetInstance` → still the old mapping. Then `forgetInstance` + resolve → new mapping. This is the freeze acceptance without running Octane.
3. **Production CUD does not invalidate.** Grep `modules/Product` PHP excluding `tests/`: no `forgetInstance(SearchSynonymExpander` (and no `forgetInstance` of that class via FQCN). Admin store/update/destroy stay write-DB-only.
4. **No unconditional boot warm.** `ProductServiceProvider` (and other Product providers) do not call `make(SearchSynonymExpander::class)` / `app(SearchSynonymExpander::class)` in `register` or `boot` except inside an Octane `WorkerStarting` listener gated by `class_exists`.
5. Existing HTTP/admin synonym tests and Discovery synonym tests stay green.

Do not add an Octane integration suite in this spec (package is not required).

---

## 7. Out of scope

- `php artisan search:synonyms:reload` or any broadcast to workers
- Request-time version / checksum checks
- `forgetInstance` on CUD
- Config-file source of truth
- Adding `laravel/octane` to Composer
- Unconditional `make()` in provider `boot()`
- Index flush/rebuild, candidate cap, label N+1
- Suggest V1.1
- Merchandising / ranking
- Synonym groups, reverse auto-map, weights
- Admin UI notice to reload Octane

---

## 8. Delivery

```text
Wave 1  Singleton freeze tests + Octane-only WorkerStarting warm
        Amend Discovery acceptance 10
        Regression: synonym expander + admin CUD + no-reindex
```

Human gate after the wave. One small PR.
