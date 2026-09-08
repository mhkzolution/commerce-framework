# Catalog V2 Octane Synonym Freeze Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Lock `SearchSynonymExpander` as a once-per-container in-memory map, then add Octane-only `WorkerStarting` warm and amend Discovery in-place.

**Architecture:** Task 1 locks freeze semantics with tests only (constructor loads `product_search_synonyms` once; `expand()` is memory-only; CUD does not invalidate). Task 2 registers a `class_exists`-gated `WorkerStarting` listener and rewrites Discovery wording. No `laravel/octane` Composer dependency. No reload command, version check, index ops, or Suggest V1.1.

**Tech Stack:** Laravel 13, PHP 8.4, PHPUnit, existing `SearchSynonymExpander` singleton

**Spec:** `docs/superpowers/specs/2026-09-08-catalog-v2-octane-synonym-freeze-design.md` (Locked)

**Start gate:** Locked freeze spec. Work on `feat/catalog-v2-octane-synonym-freeze`.

## Global Constraints

- Source of truth stays `product_search_synonyms` + existing admin CUD. Do not move synonyms to `config/search.php`.
- Keep `SearchSynonymExpander` as a **singleton**. Do not bind it transient.
- Constructor may query `product_search_synonyms` **only once** per container lifecycle. It must not query other tables, cache, or config on construct.
- `expand()` is memory only. No DB, no `Cache::remember`, no config rebuild.
- Admin CUD writes the table only. Production paths: no `forgetInstance`, no `make()` of a fresh expander, no version bump.
- `forgetInstance` is allowed **only in tests**, as the stand-in for container recreate.
- Do not add `laravel/octane`. Absence of that package must not affect application boot, migrations, tests, or production php-fpm execution.
- No `use Laravel\Octane\…` in Product production PHP. Gate with `class_exists('Laravel\\Octane\\Events\\WorkerStarting')` only.
- Do not add `search:synonyms:reload`, request-time version/checksum checks, index flush/rebuild, Suggest V1.1, merchandising, or admin reload copy.
- Do not add an Octane integration suite.
- Human gates: Task 1 review → Task 2 review → whole-branch review. Do not start Task 2 until Task 1 is reviewed.

---

## File map

| Area | Files |
|---|---|
| Expander (Task 1: touch only if tests fail) | `modules/Product/src/Services/SearchSynonymExpander.php` |
| Provider (Task 2 only) | `modules/Product/src/ProductServiceProvider.php` |
| Freeze tests | Modify `tests/Unit/Product/SearchSynonymExpanderTest.php`, `tests/Feature/Product/SearchSynonymAdminTest.php` |
| Source-scan tests | Create `tests/Unit/Product/SearchSynonymFreezeIsolationTest.php` |
| Discovery amendment (Task 2) | `docs/superpowers/specs/2026-09-08-catalog-v2-search-discovery-design.md` |

Do not modify `SearchSynonymController` unless a freeze test proves it invalidates the expander. Today it is write-DB-only.

---

### Task 1: Freeze semantics + regression tests

**Files:**
- Modify: `tests/Unit/Product/SearchSynonymExpanderTest.php`
- Modify: `tests/Feature/Product/SearchSynonymAdminTest.php`
- Create: `tests/Unit/Product/SearchSynonymFreezeIsolationTest.php`
- Modify only if tests fail: `modules/Product/src/Services/SearchSynonymExpander.php`

**Interfaces:**
- Consumes: `SearchSynonymExpander` singleton already bound in `ProductServiceProvider::register`; constructor `SearchSynonym::query()->pluck('to_term', 'from_term')`; `expand(array $tokens): array`.
- Produces: Acceptance 1–3 locked in tests. Production `SearchSynonymExpander` behavior unchanged unless a new test fails. No `WorkerStarting` registration in this task.

Do not edit `ProductServiceProvider` in this task. Do not amend Discovery in this task.

Existing directed-replace, unique `from_term`, and “CUD does not touch `search_documents`” tests stay. They may still call `forgetInstance` **after** inserts when they intend to observe the new row.

If the new tests pass on the first run with no production diff, that is expected: today’s freeze is accidental. Commit the tests as the lock. Change production PHP only when a test fails.

- [ ] **Step 1: Failing (or locking) tests for constructor-once and live CUD freeze**

Add to `tests/Unit/Product/SearchSynonymExpanderTest.php`:

```php
use Illuminate\Support\Facades\DB;

public function test_constructor_queries_product_search_synonyms_once_per_container(): void
{
    SearchSynonym::query()->create([
        'from_term' => 'ผ้าฝ้าย',
        'to_term' => 'cotton',
    ]);

    app()->forgetInstance(SearchSynonymExpander::class);

    DB::flushQueryLog();
    DB::enableQueryLog();

    $first = app(SearchSynonymExpander::class);
    $second = app(SearchSynonymExpander::class);

    $this->assertSame($first, $second);
    $this->assertSame(['cotton'], $first->expand(['ผ้าฝ้าย']));
    $this->assertSame(['cotton', 'tee'], $second->expand(['ผ้าฝ้าย', 'tee']));

    $log = DB::getQueryLog();
    $synonymQueries = collect($log)->filter(
        static fn (array $query): bool => str_contains($query['query'], 'product_search_synonyms'),
    );
    $other = collect($log)->reject(
        static fn (array $query): bool => str_contains($query['query'], 'product_search_synonyms'),
    );

    $this->assertCount(1, $synonymQueries);
    $this->assertCount(0, $other, json_encode($other->pluck('query')->all()));
}

public function test_creating_a_synonym_does_not_refresh_a_live_expander(): void
{
    app()->forgetInstance(SearchSynonymExpander::class);
    $expander = app(SearchSynonymExpander::class);

    $this->assertSame(['ผ้าฝ้าย'], $expander->expand(['ผ้าฝ้าย']));

    SearchSynonym::query()->create([
        'from_term' => 'ผ้าฝ้าย',
        'to_term' => 'cotton',
    ]);

    $this->assertSame(['ผ้าฝ้าย'], $expander->expand(['ผ้าฝ้าย']));
    $this->assertSame($expander, app(SearchSynonymExpander::class));

    app()->forgetInstance(SearchSynonymExpander::class);

    $this->assertSame(['cotton'], app(SearchSynonymExpander::class)->expand(['ผ้าฝ้าย']));
}
```

Add to `tests/Feature/Product/SearchSynonymAdminTest.php`:

```php
use Commerce\Product\Services\SearchSynonymExpander;

public function test_admin_store_does_not_refresh_a_live_expander(): void
{
    $user = User::query()->firstOrFail();

    app()->forgetInstance(SearchSynonymExpander::class);
    $expander = app(SearchSynonymExpander::class);

    $this->assertSame(['ผ้าฝ้าย'], $expander->expand(['ผ้าฝ้าย']));

    $this->actingAs($user)
        ->post(route('admin.catalog.search-synonyms.store'), [
            'from_term' => 'ผ้าฝ้าย',
            'to_term' => 'cotton',
        ])
        ->assertRedirect(route('admin.catalog.search-synonyms.index'));

    $this->assertDatabaseHas('product_search_synonyms', [
        'from_term' => 'ผ้าฝ้าย',
        'to_term' => 'cotton',
    ]);
    $this->assertSame(['ผ้าฝ้าย'], $expander->expand(['ผ้าฝ้าย']));
    $this->assertSame($expander, app(SearchSynonymExpander::class));
}
```

Create `tests/Unit/Product/SearchSynonymFreezeIsolationTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Product;

use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class SearchSynonymFreezeIsolationTest extends TestCase
{
    /**
     * @var list<string>
     */
    private const FORGET_NEEDLES = [
        'forgetInstance(SearchSynonymExpander',
        'forgetInstance(\\Commerce\\Product\\Services\\SearchSynonymExpander',
        "forgetInstance('Commerce\\Product\\Services\\SearchSynonymExpander",
        'forgetInstance("Commerce\\Product\\Services\\SearchSynonymExpander',
    ];

    public function test_production_product_php_does_not_forget_the_expander(): void
    {
        $hits = [];

        foreach ($this->productProductionPhp() as $path) {
            $contents = file_get_contents($path);
            $this->assertNotFalse($contents, $path);

            foreach (self::FORGET_NEEDLES as $needle) {
                if (str_contains($contents, $needle)) {
                    $hits[] = $path.' contains '.$needle;
                }
            }
        }

        $this->assertSame([], $hits, implode("\n", $hits));
    }

    public function test_expander_constructor_does_not_read_cache_or_config(): void
    {
        $path = $this->repoRoot().'/modules/Product/src/Services/SearchSynonymExpander.php';
        $contents = file_get_contents($path);
        $this->assertNotFalse($contents);

        foreach (['Cache::', 'config(', 'Redis', 'Cache::remember'] as $needle) {
            $this->assertStringNotContainsString($needle, $contents);
        }

        $this->assertStringContainsString("SearchSynonym::query()", $contents);
        $this->assertStringContainsString("pluck('to_term', 'from_term')", $contents);
    }

    /**
     * @return list<string>
     */
    private function productProductionPhp(): array
    {
        $files = [];
        $root = $this->repoRoot().'/modules/Product';
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $path = $file->getPathname();

            if (str_contains($path, '/tests/')) {
                continue;
            }

            $files[] = $path;
        }

        return $files;
    }

    private function repoRoot(): string
    {
        return dirname(__DIR__, 3);
    }
}
```

- [ ] **Step 2: Run Task 1 tests**

```bash
php artisan test --compact \
  tests/Unit/Product/SearchSynonymExpanderTest.php \
  tests/Unit/Product/SearchSynonymFreezeIsolationTest.php \
  tests/Feature/Product/SearchSynonymAdminTest.php \
  tests/Feature/Product/ProductDiscoveryQueryTest.php \
  tests/Feature/Product/CatalogV2SearchDiscoveryRegressionTest.php \
  tests/Feature/Product/SearchReindexTriggersTest.php
```

Expected: PASS. Constructor query log has exactly one `product_search_synonyms` query and no other tables. Live expander ignores CUD until `forgetInstance`. Admin store writes the row and leaves the live instance unchanged. Production Product PHP has no `forgetInstance(SearchSynonymExpander`. Existing synonym / no-reindex tests stay green.

If constructor-once fails because extra queries appear, fix `SearchSynonymExpander::__construct` so it only `pluck`s `product_search_synonyms`. Do not add cache or config. Do not register Octane listeners to make this pass.

- [ ] **Step 3: Commit**

```bash
git add \
  tests/Unit/Product/SearchSynonymExpanderTest.php \
  tests/Unit/Product/SearchSynonymFreezeIsolationTest.php \
  tests/Feature/Product/SearchSynonymAdminTest.php \
  modules/Product/src/Services/SearchSynonymExpander.php
git commit -m "$(cat <<'EOF'
test: lock SearchSynonymExpander singleton freeze

EOF
)"
```

If `SearchSynonymExpander.php` was not modified, omit it from `git add`.

- [ ] **Step 4: Human gate — stop**

```text
Task 1
  ↓ review   ← stop here
```

Do not start Task 2 until this task is reviewed. Reviewer checks: singleton still bound; constructor loads that table once; `expand()` adds no queries; CUD does not invalidate; production grep is clean; no `WorkerStarting` in the diff.

---

### Task 2: Octane-only eager warm + Discovery amendment

**Files:**
- Modify: `modules/Product/src/ProductServiceProvider.php`
- Modify: `tests/Unit/Product/SearchSynonymFreezeIsolationTest.php`
- Modify: `docs/superpowers/specs/2026-09-08-catalog-v2-search-discovery-design.md`

**Interfaces:**
- Consumes: Task 1 freeze tests; `ProductServiceProvider::boot` already listens for product index events.
- Produces: When `class_exists('Laravel\\Octane\\Events\\WorkerStarting')` is true, `Event::listen('Laravel\\Octane\\Events\\WorkerStarting', …)` resolves `SearchSynonymExpander` so the constructor runs at worker start. When the class is absent, boot/migrate/tests/php-fpm are unchanged. Discovery §2 goal 7, §10 Synonym CUD row, and §12 acceptance 10 match freeze spec §5.1.

Do not add `laravel/octane` to any `composer.json`. Do not `use Laravel\Octane\…`. Do not call `make(SearchSynonymExpander::class)` from `register()` or from `boot()` outside the gated listener. Do not add a reload command. Do not add an Octane integration suite.

- [ ] **Step 1: Isolation tests for gated warm and no hard dependency**

Add to `tests/Unit/Product/SearchSynonymFreezeIsolationTest.php`:

```php
public function test_product_production_php_does_not_import_octane(): void
{
    $hits = [];

    foreach ($this->productProductionPhp() as $path) {
        $contents = file_get_contents($path);
        $this->assertNotFalse($contents, $path);

        if (preg_match('/^use Laravel\\\\Octane\\\\/m', $contents) === 1
            || str_contains($contents, 'use Laravel\\Octane\\')) {
            $hits[] = $path;
        }
    }

    $this->assertSame([], $hits, implode("\n", $hits));
}

public function test_worker_starting_warm_is_gated_by_class_exists_string(): void
{
    $path = $this->repoRoot().'/modules/Product/src/ProductServiceProvider.php';
    $contents = file_get_contents($path);
    $this->assertNotFalse($contents);

    $this->assertStringContainsString(
        "class_exists('Laravel\\\\Octane\\\\Events\\\\WorkerStarting')",
        $contents,
    );
    $this->assertStringContainsString(
        "Event::listen('Laravel\\\\Octane\\\\Events\\\\WorkerStarting'",
        $contents,
    );
    $this->assertStringContainsString(
        'make(SearchSynonymExpander::class)',
        $contents,
    );
    $this->assertStringNotContainsString(
        'use Laravel\\Octane\\',
        $contents,
    );
}

public function test_composer_does_not_require_octane(): void
{
    foreach ([
        $this->repoRoot().'/composer.json',
        $this->repoRoot().'/composer.lock',
        $this->repoRoot().'/modules/Product/composer.json',
    ] as $path) {
        $contents = file_get_contents($path);
        $this->assertNotFalse($contents, $path);
        $this->assertStringNotContainsString('laravel/octane', $contents, $path);
    }
}
```

These fail until the provider has the gated listener. The Composer assertion should already pass.

- [ ] **Step 2: Run isolation tests and confirm they fail on the listener**

```bash
php artisan test --compact tests/Unit/Product/SearchSynonymFreezeIsolationTest.php
```

Expected: FAIL on `test_worker_starting_warm_is_gated_by_class_exists_string` (string missing). `test_product_production_php_does_not_import_octane` and `test_composer_does_not_require_octane` may already pass. Forget-instance test from Task 1 stays green.

- [ ] **Step 3: Register the gated listener**

In `modules/Product/src/ProductServiceProvider.php`, after the existing `Event::listen` calls in `boot()`, add **only**:

```php
if (class_exists('Laravel\\Octane\\Events\\WorkerStarting')) {
    Event::listen('Laravel\\Octane\\Events\\WorkerStarting', function (): void {
        $this->app->make(SearchSynonymExpander::class);
    });
}
```

Do not add a `use` import for Octane. Do not put `make(SearchSynonymExpander::class)` in `register()`. Do not put it in `boot()` outside this `if`. Leave `singleton(SearchSynonymExpander::class)` in `register()` as it is.

- [ ] **Step 4: Amend Discovery in-place**

Edit `docs/superpowers/specs/2026-09-08-catalog-v2-search-discovery-design.md` only at the sites below. Do not rewrite unrelated sections.

**§2 goal 7** — replace:

```text
7. Reindex is event-driven. Synonym edits take effect at query time without a full rebuild.
```

with:

```text
7. Reindex is event-driven. Synonym CUD does not reindex. Expansion uses the in-memory map of the current container.
```

**§10 Synonym CUD row** — replace:

```text
| Synonym CUD | No reindex; next query expands with the new map |
```

with:

```text
| Synonym CUD | No reindex; expansion uses the in-memory map of the current container; a live Octane worker does not pick up CUD until process recreate |
```

**§12 acceptance 10** — replace:

```text
10. Changing a synonym does not rewrite `search_documents`. The next search uses the new expansion.
```

with:

```text
10. Changing a synonym does not rewrite `search_documents`. php-fpm: a new request is a new container, so the constructor loads the current table. Octane: a worker already holding the expander does not see CUD until that worker/container is recreated (`octane:reload`).
```

Then scan the whole Discovery document for leftover live-map wording. These must not remain:

```text
the next search uses the new expansion
next query expands with the new map
take effect at query time
```

`Query-time only` in Discovery §7 stays (expansion is still query-time vs reindex). Do not change indexer, ranking, facets, or Suggest.

- [ ] **Step 5: Re-run freeze + synonym regression + migrate**

```bash
php artisan migrate --no-interaction --force
php artisan test --compact \
  tests/Unit/Product/SearchSynonymExpanderTest.php \
  tests/Unit/Product/SearchSynonymFreezeIsolationTest.php \
  tests/Feature/Product/SearchSynonymAdminTest.php \
  tests/Feature/Product/ProductDiscoveryQueryTest.php \
  tests/Feature/Product/CatalogV2SearchDiscoveryRegressionTest.php \
  tests/Feature/Product/SearchReindexTriggersTest.php
```

Expected: migrate succeeds without Octane. All listed tests PASS. Isolation: no `use Laravel\Octane\`, gated `class_exists` + `Event::listen` strings present, no `laravel/octane` in composer files, no production `forgetInstance`. Task 1 freeze tests still pass (listener does not fire without Octane, so first resolve is still the load).

Confirm leftover Discovery wording is gone:

```bash
rg -n "next search uses the new expansion|next query expands with the new map|take effect at query time" \
  docs/superpowers/specs/2026-09-08-catalog-v2-search-discovery-design.md
```

Expected: no matches.

- [ ] **Step 6: Commit**

```bash
git add \
  modules/Product/src/ProductServiceProvider.php \
  tests/Unit/Product/SearchSynonymFreezeIsolationTest.php \
  docs/superpowers/specs/2026-09-08-catalog-v2-search-discovery-design.md
git commit -m "$(cat <<'EOF'
feat: warm synonym expander on Octane worker start

EOF
)"
```

- [ ] **Step 7: Human gate — stop**

```text
Task 2
  ↓ review   ← stop here
```

Reviewer checks: listener is string-gated; no Octane import or Composer dependency; Discovery §2 / §10 / §12 match freeze spec §5.1; no conflicting Discovery wording; migrate and PHPUnit still work without Octane.

---

## Whole-branch review

After Task 2 review, one PR. Do not merge until this pass.

```bash
php artisan migrate --no-interaction --force
php artisan test --compact \
  tests/Unit/Product/SearchSynonymExpanderTest.php \
  tests/Unit/Product/SearchSynonymFreezeIsolationTest.php \
  tests/Feature/Product/SearchSynonymAdminTest.php \
  tests/Feature/Product/ProductDiscoveryQueryTest.php \
  tests/Feature/Product/CatalogV2SearchDiscoveryRegressionTest.php \
  tests/Feature/Product/SearchReindexTriggersTest.php
```

Diff must not include: `laravel/octane` in Composer, `use Laravel\Octane\`, `search:synonyms:reload`, version/checksum checks, index ops, Suggest, or `forgetInstance` on CUD.

```text
Task 1  → Task 2  → whole-branch review  → merge
```

---

## Spec coverage

| Spec | Task |
|---|---|
| Constructor queries `product_search_synonyms` once; no other table/cache/config | Task 1 expander tests + isolation source check |
| `expand()` memory only | Task 1 query log after expand |
| Singleton binding stays | Task 1 `assertSame` on two resolves |
| CUD does not refresh live expander | Task 1 unit + admin store test |
| Production CUD does not `forgetInstance` | Task 1 isolation grep |
| Existing directed-replace / unique / no-reindex | Task 1 regression command |
| No `WorkerStarting` in Task 1 | Task 1 file list + human gate |
| Gated `WorkerStarting` warm | Task 2 provider + isolation |
| No `use Laravel\Octane\`; no Composer dep | Task 2 isolation |
| Absence of Octane does not break boot/migrate/tests/php-fpm | Task 2 migrate + PHPUnit |
| Discovery acceptance 10 + goal 7 + §10 in-place; no leftover next-search wording | Task 2 Discovery edit + `rg` |
| No Octane integration suite / reload command / version check / index / Suggest | Global constraints |
