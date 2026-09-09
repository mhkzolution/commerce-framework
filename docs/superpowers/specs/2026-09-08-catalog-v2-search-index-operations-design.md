# Catalog V2 Follow-up: Search Index Operations

**Date:** 2026-09-08  
**Status:** Locked  
**Owner:** Product search (`modules/Product`) + Catalog label reindex (`AttributeValueService`)  
**Related:** `docs/superpowers/specs/2026-09-08-catalog-v2-search-discovery-design.md`

This spec is one implementation unit: **flush-first rebuild with fail-fast abort, a 500-candidate cap after rank, and eager-loaded label-change reindex.** Ranking formula, synonym freeze, Suggest, and the SQL search engine stay as Discovery V2.

It amends the Discovery spec in-place for rebuild abort and the candidate cap. After the edit, no remaining wording implies an unbounded candidate set or a rebuild that restores the old index on failure.

---

## 1. Problem

Three operational holes sit on the current SQL index:

1. **Rebuild** is already flush-then-`reindexAll()`. Discovery says “Flush + rebuild.” Abort after flush is undefined: empty or partial `search_documents` can look like success if a caller swallows the exception.
2. **Candidates** — `ProductDiscoveryQuery` loads every `products` document, matches in PHP, sorts, and returns every matching uuid. Listing and facets inherit that unbounded set.
3. **Label rename** reindexes every product that references the value, but loads those products without the relations `index()` needs. `reindexAll()` already eager-loads them.

This unit does not introduce a second index generation, a search platform, or merchandising.

---

## 2. Goals

1. Rebuild stays flush-first. A throw after flush leaves empty or partial documents. Operator re-runs. CLI/HTTP do not report success.
2. `ProductDiscoveryQuery` still ranks as Discovery V2, then returns at most 500 uuids.
3. Label-change reindex uses the same product relation set as `ProductSearchIndexer::reindexAll()`.

Non-goals: build-then-swap, skip-and-continue per product, DB transaction around flush+rebuild, rebuild lock, Redis label cache, Suggest V1.1, merchandising/ranking, Octane worker-boot handling, `octane.php` flush.

---

## 3. Locked decisions

| Topic | Decision |
|---|---|
| Rebuild | Keep `flush('products')` then `reindexAll()`. Do not add `products_next` / swap. |
| Abort | Fail-fast. First exception stops remaining products. No skip-and-continue. No rollback (no snapshot). Durable state = whatever documents exist after the throw. |
| Success reporting | CLI: non-zero exit when rebuild throws. Admin rebuild: failure response; do **not** emit the success flash/message. Do not catch-and-log then redirect as success. |
| Concurrent rebuild | No advisory lock. Overlap is ops (do not run two rebuilds). |
| Queue | None. Still synchronous HTTP + `product:reindex`. |
| Cap location | After the **final ranking order** is produced and **before** UUIDs are returned from `ProductDiscoveryQuery`. Never `array_slice` then `usort`. Never SQL `LIMIT` on the document scan. |
| Cap value | `500`. `public const` on `ProductDiscoveryQuery`. Not config / admin setting. |
| Cap callers | All `candidateUuids()` callers. Facet aggregation for q-based discovery uses the same post-rank capped candidate set returned by `ProductDiscoveryQuery`. Listing and facets must not compute a second, uncapped candidate list. Suggest does not use this method and is unchanged. Empty `q` still returns `[]` without reading the table. |
| Label N+1 | Eager-load on the label-change product query. No Redis. No `attribute_value_id → label` map inside `index()`. |
| Relation source of truth | The relation list is **one** constant used by `reindexAll()` and the label-change path. Do not copy the array in two files. See §4. |
| Discovery amendment | In-place in **Task 1**. Known targets: §10 Reindex (rebuild), §8 Facets and filters (discovery flow), §12 Tests (acceptance), plus §3 locked decisions if a pipeline/candidate row is needed. After amendment: rebuild is flush-first + fail-fast; cap = 500 is documented; no leftover wording implies unbounded candidates or rollback-on-failure. Replacement text is §5.1. |

---

## 4. Relation set (label-change)

`ProductSearchIndexer` owns a public list, e.g. `INDEX_RELATIONS`, equal to what `reindexAll()` uses today:

```php
['variants', 'categories', 'brand', 'attributeValues.attributeValue']
```

`reindexAll()` and `AttributeValueService::update` (label-changed branch) both pass that constant to `with(...)`. Label-change may `chunkById(100)` like `reindexAll()`. Do not `Product::query()->whereKey($ids)->get()` without `with(INDEX_RELATIONS)`.

`index()` may still `loadMissing(...)` for single-product save-path callers. That does not replace the eager `with()` on the multi-product label path.

---

## 5. Flow

```text
rebuild()
  → flush(products)
  → reindexAll()
  → on throw: leftover empty/partial documents
               CLI exit ≠ 0
               admin: no success flash

candidateUuids(q)
  → load documents, match tokens (unchanged)
  → usort fieldRank, then coverage DESC for text ranks
    (exact-SKU pair: title only; coverage not computed)
  → slice first 500 of that ordered list
  → return uuids
  → ShopController: that same array is listing searchUuids and facet searchUuids

attribute_values.label change
  → persist label
  → products referencing that value
  → with(INDEX_RELATIONS), chunk
  → index() each
```

Slice **after** sort:

```text
sort(...)
$matches = array_slice($matches, 0, 500)
return uuids
```

Not:

```text
$matches = array_slice($matches, 0, 500)
sort(...)
```

### 5.1 Discovery replacement wording

Write these into the Discovery spec in-place. Known amendment targets (do not only edit acceptance):

- **Rebuild section (§10 Reindex)** — Admin rebuild row
- **Discovery flow section (§8 Facets and filters)** — “After the index returns candidate product ids”
- **Acceptance section (§12 Tests)** — add/adjust items for abort leftover and cap 500
- **§3 Locked decisions** — pipeline / candidate row if that table still implies an unbounded set

**§10 Admin rebuild row** — replace “Flush + rebuild all product documents” with: Flush + rebuild all product documents. After flush, an aborted rebuild leaves empty or partial documents; documents indexed before the exception remain present; there is no rollback. CLI exits non-zero; admin does not flash success. Operator re-runs the command or button.

**§3 ranking / candidate row:** After rank (highest matched field, then `title` ascending), `ProductDiscoveryQuery` returns at most 500 uuids. Facets for non-empty `q` use that same list. SQL document load is not `LIMIT`ed. Empty `q` still does not query the index.

**§8 discovery flow** — replace “After the index returns candidate product ids” so it means: when `q` is non-empty, listing and facet aggregation use the same post-rank capped set (≤ 500). Empty `q` is still the full published catalog via relations.

**§12** — add acceptance that leftover documents after a thrown rebuild are not rolled back, and that `candidateUuids` returns at most 500 in rank order.

Scan the whole Discovery document for wording that means “every matching document is a candidate” or “rebuild restores the previous index on failure.” After the edit, none of that remains. `Query-time only` for synonyms stays.

---

## 6. Tests (acceptance)

Keep existing rebuild-flush-stale, label-change reindex, and Discovery ranking tests green.

### Task 1 — Rebuild failure semantics + Discovery amendment

1. After `flush`, if `reindexAll` / `index` throws, leftover documents are empty or the prefix already written. No restore of the pre-flush set.
2. **Documents indexed before the exception remain present. No rollback occurs.** A catch path that deletes those rows (cleanup) fails this spec.
3. `php artisan product:reindex` exits **non-zero** when rebuild throws. It does not print the success “Indexed N products.” line as if the run completed.
4. Admin rebuild POST does **not** set the success flash (`Rebuilt search documents for …`) when rebuild throws. The response is a failure (uncaught exception / 5xx is acceptable). Catching the exception and redirecting with that success `status` is a spec fail.
5. **Discovery V2 is updated in-place** at §10, §8, §12, and §3 as needed. After the edit: rebuild section is flush-first + fail-fast; candidate cap = 500 is documented (including that facets use that set); no remaining wording implies unbounded candidate sets or rollback-on-failure. Scan the whole Discovery document.

### Task 2 — Candidate cap (500)

6. Rank order unchanged for the kept prefix: build a set of more than 500 matches; the first 500 uuids from `candidateUuids` equal the first 500 of the same query if the cap were not applied (same sort as Ranking V1 (`fieldRank`, coverage DESC on text ranks, title; exact-SKU pairs title-only)). Cap still after that sort.
7. Tail removed: a document that sorts as 501st is absent from the returned list.
8. No SQL `LIMIT` on the `search_documents` scan in `ProductDiscoveryQuery`. Cap is `array_slice` (or equivalent) **after** `usort`.
9. Empty / whitespace `q` still returns `[]` without reading `search_documents`. Suggest tests stay green.
10. **Same set for facets:** `ShopController` (or equivalent) passes the `candidateUuids()` return value to both listing and `ShopFilterCatalogService::buildFor`. No second uncapped discovery call for `q`.

### Task 3 — Label-change eager load

11. Label rename still reindexes affected products (existing `crimson` / filter-code test stays).
12. `reindexAll()` and the label-change product query use the **same** `INDEX_RELATIONS` (or equivalent named constant). Grep: `AttributeValueService` does not duplicate a literal relation array that can drift from `reindexAll()`.
13. Query log (or equivalent) on a label change that touches more than one product: not one `attribute_values` / `variants` load per product via lazy `loadMissing` as the only load path. Eager `with(INDEX_RELATIONS)` is present on that query.

---

## 7. Out of scope

- Build-then-swap / second `index_name`
- Skip failed products and continue
- Transaction wrapping flush + all inserts
- Rebuild mutex / cache lock
- Candidate cap in SQL or in `ShopController` only (listing capped, facets uncapped)
- Cap config / admin UI
- Suggest query changes
- Ranking formula, synonym expander, merchandising
- Label cache, listing/PDP N+1
- Search platform migration

---

## 8. Delivery

```text
Task 1  Rebuild failure semantics + Discovery amendment
Task 2  Candidate cap = 500
Task 3  Label-change reindex eager loading
```

Human gate after each task, then whole-branch review. One spec, three tasks.
