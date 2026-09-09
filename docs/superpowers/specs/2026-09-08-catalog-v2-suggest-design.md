# Catalog V2 Phase 3: Storefront Suggest

**Date:** 2026-09-08  
**Status:** Locked  
**Owner:** Storefront shop (`modules/Cart`) + product search read model (`modules/Product`)  
**Related:** `docs/superpowers/specs/2026-09-08-catalog-v2-search-discovery-design.md`

This spec is one implementation unit: **as-you-type suggest** on the existing storefront search overlay. It is a prefix shortcut into products, brands, categories, and query completions.

It does **not** change Phase 2 listing, ranking, facets, synonyms, or empty-`q` browse. It does **not** introduce Meilisearch, Typesense, a `suggest_index` table, query logs, popular-search merchandising, or redirect keywords.

---

## 1. Problem

Phase 2 listing is exact-token discovery. `cot` does not match `cotton`. Shoppers who type a prefix in the header overlay today only see config `popular_terms` and local recent searches — no live catalog matches.

The overlay already exists (`resources/views/components/storefront/navigation/search-overlay.blade.php` + `resources/js/storefront/header.js`). Enter already submits `GET /shop?q=`. Suggest must add a typed-prefix panel without replacing that submit path.

---

## 2. Goals

1. After two or more characters, show a mixed overlay: query completions, products, brands, categories.
2. Word-prefix match on **name tokens** already in the catalog (product title, brand name, category name). Each query token prefixes a name token; this is not substring-inside-a-token matching, listing-token matching, or SKU-row matching.
3. Clicks are entity-correct: completion → `/shop?q=`, product → PDP, brand → `/shop?brand={slug}`, category → `/shop?category={slug}`.
4. Enter in the search field still runs Phase 2 discovery listing.
5. Empty or short `q` does not read `search_documents`.

Non-goals: synonym expansion in suggest, query-log autocomplete, SKU completion rows, typo tolerance, hyphen-insensitive SKU, multi-select facets, merchandising, search-platform migration, Phase 2 follow-up tickets (synonym expander lifecycle, size/color group vs code, attribute code slug rewrite, rebuild/cap).

---

## 3. Locked decisions

| Topic | Decision |
|---|---|
| Engine | SQL on existing tables. No new suggest table. No Meilisearch/Typesense. |
| Listing isolation | `ProductDiscoveryQuery` is never called from suggest. Shop listing ranking/facets unchanged. |
| Min length | Run `SearchNormalizer::tokenize(q)`. If there are no tokens or any token has `mb_strlen` **< 2**, return empty groups and do not read `search_documents`. Whitespace-only is empty. |
| Match | Case-insensitive **word prefix**: each query token must prefix some token of the name. Not contains inside a token. Product SQL `LIKE` is recall only; the PHP matcher is the source of truth. |
| Synonyms | **Off** for suggest V1. Directed replace stays listing-only. |
| Completions source | Union of word-prefix + AND matching product, brand, and category names. Distinct after `SearchNormalizer::textNormalize()` **across groups** (so `TEE` / `Tee` / `tee` is one row). Keep the first label after the group sort below. No query log. No SKU strings. |
| Products | Product SQL recalls `search_documents.title` rows, then PHP applies word-prefix + AND with `visibleOnStorefront()`. **Title only:** description, attributes, categories, and brand matches do **not** create product rows. Link = PDP (`storefront.products.show` / `/products/{slug}`). |
| Brands | `brands` relation, `is_active`, word-prefix + AND on `name`. URL = `/shop?brand={slug}`. |
| Categories | Start with `HomepageNavigationQuery::shopFilterOptions()`, then apply word-prefix + AND in PHP on `name`. URL = `/shop?category={slug}`. |
| Cap | **5** items per group. Deterministic order: **shorter matching names first, then alphabetical** (`ORDER BY CHAR_LENGTH(name), name` / `mb_strlen` then name). Example: `te` → `Tee`, `Tee Shirt`, `Team Jersey`. |
| Overlay empty state | `q` empty or `< 2` chars: keep today’s popular config pills + recent (localStorage). Hide those when suggest groups are shown. |
| Submit | Overlay form `GET /shop` with `name="q"` unchanged. |
| Duplicate rows | A name may appear as both a completion and an entity (e.g. product “Tee” in Completions and Products). That is allowed. |

---

## 4. Source of Truth

```text
Suggest reads
─────────────
products via search_documents.title + visibleOnStorefront
brands table (active)
categories via HomepageNavigationQuery::shopFilterOptions()
```

Facet/filter identity stays Phase 2 (`attribute_values.code`, brand slug, category slug). Suggest does not invent new URL params.

---

## 5. Query

New service, e.g. `ProductSuggestQuery` in Product or Cart, used only by a storefront suggest endpoint.

```text
tokens = SearchNormalizer::tokenize(q)
if tokens empty or any token length < 2 → empty payload, return without search_documents

products   ← SQL recall storefront-visible search_documents.title rows,
             then PHP word-prefix + AND on title, cap 5
             (title only; ignore description / attributes / brand / category text)
brands     ← active brands, then PHP word-prefix + AND on name, cap 5
categories ← HomepageNavigationQuery::shopFilterOptions(),
             then PHP word-prefix + AND on name, cap 5
completions ← union matching product, brand, and category labels,
              unique across groups by textNormalize, shorter then alphabetical, cap 5
```

- Do not expand synonyms. Do not score with Phase 2 field ranks.
- Do not scan `payload.skus`, description, or attribute labels for suggest V1.
- Do not call `ProductDiscoveryQuery`.
- Alias `search` is not required on the suggest endpoint; overlay already sends `q`.

---

## 6. HTTP + overlay

`GET` JSON, public storefront, e.g. `storefront.suggest` → `/shop/suggest`.

```json
{
  "completions": [{"label": "Tee", "url": "/shop?q=Tee"}],
  "products": [{"label": "Classic Tee", "url": "/products/classic-tee"}],
  "brands": [{"label": "Acme", "url": "/shop?brand=acme"}],
  "categories": [{"label": "T-shirts", "url": "/shop?category=shirts"}]
}
```

- `200` with empty arrays when `q` is missing, empty, or short. Not `400`.
- Product `url` is the existing PDP path (slug). No new card payload (image optional, not required in V1).
- Completion `url` uses canonical `q` (not `search`).

JS: debounce input (≈200ms). `< 2` chars → show hints, clear suggest. `>= 2` → fetch, hide hints, render four sections that have at least one row. Escape labels. Failure → empty groups, do not break overlay open/close.

---

## 7. Tests (acceptance)

1. `q` empty, `"  "`, or one character, and token-gate cases `"t e"` and `"classic t"`: suggest returns empty groups and does not query `search_documents`. A trimmed two-letter token such as `"  te  "` still runs.
2. `q=te` word-prefix matches products named `Tee` and `Classic Tee`, while `eam` does not match `Team Jersey` and `las` does not match `Classic Tee`.
3. Product matching remains title-only: `q=te` does **not** return `Parka` whose description or attributes contain `tee`. Description, attributes, categories, and brand matches do not create product rows.
4. Multiple query tokens use AND: `classic te` matches `Classic Tee`; `tee park` does not.
5. PHP is the source of truth: a recalled `Streetwear` title does not match `q=te`.
6. Word-prefix matching is case-insensitive (`TE` finds `Tee`). Completions for `TEE` / `Tee` / `tee` are one row after cross-group `textNormalize` dedupe.
7. Completion click target is `/shop?q=` + the full label. Brand/category use slug params. Product uses PDP URL.
8. `GET /shop?q=cot` listing still uses Phase 2 exact-token and does not list `Cotton Parka` through suggest behavior.
9. Cap: a sixth word-prefix-matching product is omitted. Completions for `te` order `Tee` before `Tee Shirt` before `Team Jersey`.
10. Suggest endpoint never invokes `ProductDiscoveryQuery` (bind a throwing fake in the HTTP test).

---

## 8. Out of scope (later specs)

- Popular searches as live merchandising (config pills may remain as empty-overlay chrome)
- Redirect keywords
- Query-log / “other shoppers searched”
- SKU prefix rows, attribute-value suggest, collections, tags
- Synonym-aware suggest
- Typo / n-gram / Meilisearch
- Changing `constrainInStock()`, PDP, or Phase 2 reserved-code rules
- Closing Phase 2 follow-ups (Octane synonym map, size vs `shoe_size` facet universe, `UpdateAttributeRequest` slug rewrite, flush-first rebuild, candidate cap)

---

## 9. Delivery

Implementation (not a spec change) uses a human gate after the wave:

```text
Wave 1  ProductSuggestQuery + JSON endpoint + overlay wiring
        Regression: Phase 2 CatalogV2SearchDiscoveryRegressionTest still green
```

Do not merge suggest matching into `ProductDiscoveryQuery`. Do not start a search-platform spike in this wave.
