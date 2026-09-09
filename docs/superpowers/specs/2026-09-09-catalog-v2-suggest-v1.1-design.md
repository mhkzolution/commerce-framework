# Catalog V2 Follow-up: Suggest V1.1

**Date:** 2026-09-09  
**Status:** Locked  
**Owner:** Storefront shop (`modules/Cart`) + product search read model (`modules/Product`)  
**Related:** `docs/superpowers/specs/2026-09-08-catalog-v2-suggest-design.md`

This spec is one implementation unit: **word-prefix as-you-type suggest** on the existing overlay. It keeps V1 HTTP, groups, caps, and listing isolation. It changes how a typed query matches names.

It amends the Suggest V1 spec in-place for match, min length, query flow, and acceptance. After the edit, no remaining wording implies whole-string name prefix as the match rule, “do not tokenize,” or a SQL `LIKE 'prefix%'` as the source of truth.

Discovery ranking, merchandising, `SearchSynonymExpander` lifecycle, search-index rebuild, and search-engine replacement stay out of this unit.

---

## 1. Problem

Suggest V1 matches a prefix of the **entire** name. `tee` finds `Tee` and misses `Classic Tee`. Shoppers type a later word in the title, brand, or category and the overlay looks empty even though the catalog has that name.

V1 SQL `title LIKE 'prefix%'` encodes that whole-string rule. V1.1 must recall names where a **word** prefixes, then apply the real matcher in PHP.

---

## 2. Goals

1. Typed tokens prefix **words** in product titles, brand names, and category names (same rule for completions).
2. Several tokens AND together. A token shorter than 2 characters empties the whole result and skips the product index.
3. Product rows stay title-only on `search_documents`. Brands and categories stay on V1 tables / `HomepageNavigationQuery::shopFilterOptions()`.
4. Overlay JSON, URLs, cap 5, shorter-then-alpha sort, no synonym expand, no `ProductDiscoveryQuery`.

Non-goals: SKU / description / attribute product rows, substring-inside-a-word, query-log completions, a `search_suggestions` table, synonym-aware suggest, overlay chrome (debounce, images), merchandising, ranking, rebuild, Octane.

---

## 3. Locked decisions

| Topic | Decision |
|---|---|
| Source | Existing `search_documents` for **product** titles. No query-history table. No `search_suggestions` table. |
| Product fields | `search_documents.title` + `visibleOnStorefront()`. Description, SKU, attributes, brand, and category text do not create product rows. |
| Match | After `SearchNormalizer::textNormalize`, split names and query with `SearchNormalizer::tokenize` (whitespace only). Each query token must be a **prefix of some name token**. Not whole-string-only prefix. Not substring inside a token (`eam` ≠ `Team`). |
| Groups | Products, brands, categories, and completions all use that word-prefix rule. |
| Multi-token | AND: every query token must match some name token. `classic te` matches `Classic Tee`. `tee park` does not. |
| Token length | Every token from `tokenize(q)` must have `mb_strlen` ≥ 2. If any token is shorter (including `'t e'` and `'classic t'`), return empty groups and **do not** query `search_documents`. Empty / whitespace / single-character `q` still empty (V1). `'  te  '` still runs. |
| Synonyms | Off. Do not call `SearchSynonymExpander`. Enter still uses Discovery expand. |
| Cap / sort | 5 per group. Shorter `label` first (`mb_strlen`), then `strnatcasecmp`. Not Discovery field rank. Not first-word boost. |
| Completions | Union of matching product, brand, and category labels. Distinct by `textNormalize` **across groups**. Keep the first label after that sort. URL = `/shop?q={full label}`. |
| SQL | Product SQL is **recall only**. PHP word-prefix is the source of truth. A recalled title that fails the matcher is omitted (`Streetwear` + `te` is not a product hit). |
| Brands | Active `brands.name`, PHP matcher, no `search_documents`. |
| Categories | Filter **after** `HomepageNavigationQuery::shopFilterOptions()`. No `search_documents` lookup for categories. |
| Listing | `ProductDiscoveryQuery` is never used. `GET /shop?q=` unchanged. |
| Suggest V1 amendment | In-place. Known targets and replacement text: §5.1. |

---

## 4. Flow

```text
tokens = SearchNormalizer::tokenize(q)
if tokens empty or any token mb_strlen < 2
  → empty SuggestResult
  → no search_documents query

products
  → SQL recall on search_documents.title (optimization; may over-fetch)
  → PHP: tokenize(title); AND word-prefix
  → visibleOnStorefront()
  → sort short then alpha, cap 5
  → PDP URL

brands
  → active brands
  → PHP same matcher on name
  → /shop?brand={slug}

categories
  → shopFilterOptions()
  → PHP same matcher on name
  → /shop?category={slug}

completions
  → union of those three label sets
  → unique by textNormalize (cross-group)
  → same sort, cap 5
  → /shop?q={full label}
```

`PRODUCT_CANDIDATE_LIMIT` remains a product recall ceiling, not the overlay cap. If recall is too tight and a legitimate PHP match never appears, that is a V1.1 defect of the prefilter, not a reason to scan every document.

Do not treat SQL `LIKE 'token%'` on the full title as the match rule. Escape `LIKE` wildcards as V1.

---

## 5. Suggest V1 replacement wording

Write these into `docs/superpowers/specs/2026-09-08-catalog-v2-suggest-design.md` in-place. Known amendment targets (do not only edit acceptance):

- **§2 Goals** — prefix on **names** / not substring (keep not-substring-inside-token; state word prefix)
- **§3** Min length, Match, Completions, Products, Brands, Categories
- **§5 Query** — replace whole-string prefix flow and **delete** “Do not tokenize” as a match rule
- **§7 Tests** — min length, word-prefix, AND, cross-group completion dedupe

**§3 Min length:** `SearchNormalizer::tokenize(q)`. If any token has length < 2 (or there are no tokens), empty groups and no `search_documents` read. Whitespace-only is empty.

**§3 Match:** Case-insensitive word prefix. Each query token prefixes some token of the name. Not contains inside a token. SQL `LIKE` is recall for products, not the matcher.

**§3 Products / Brands / Categories:** same word-prefix + AND. Products still title-only. Categories still from `shopFilterOptions()` then PHP filter.

**§3 Completions:** Distinct after `textNormalize` across product, brand, and category labels. First label after short-then-alpha. URL `/shop?q=` + that label.

**§5:** tokenize; gate; no expand; PHP matcher; SQL recall for products only.

**§7:** keep listing isolation, title-only Parka, HTTP never resolves `ProductDiscoveryQuery`. Replace “whole name prefixes `te`” examples that contradict word-prefix. Add Classic Tee / AND / token gate / Streetwear SoT.

Scan the V1 document. After the edit, none of these remain as match rules: whole-string-only prefix, “Do not tokenize,” `normalized LIKE 'prefix%'` as SoT, “normalized length ≥ 2 is enough even if a token is 1 character.” Synonym off and listing isolation stay.

---

## 6. Tests (acceptance)

Keep V1 tests that still hold: title-only vs description, hidden products, PDP / brand / category URLs, HTTP throwing `ProductDiscoveryQuery`, `GET /shop?q=cot` exact-token listing, `LIKE` escape, NFC/NFD title recall, no Cart constructor dependency.

Replace tests that treat `LIKE 'te%'` on the full title as the match rule.

### Product matching

1. `q=te` returns products titled `Tee` and `Classic Tee`.
2. `q=te` does not return `Parka` whose description or attributes contain `tee`. Description, attributes, brand, and category matches do not create product rows.
3. A storefront-hidden product whose title would match is omitted. Product URL is the PDP.

### Brand matching

4. `q=per` returns an active brand `Peak Performance`. An inactive brand with a matching name is omitted. URL is `/shop?brand={slug}`. Brand matching does not read `search_documents`.

### Category matching

5. `q=te` returns a category named `Graphic Tees` that appears in `shopFilterOptions()`. A category omitted from those options (inactive / empty slug per V1) is omitted.
6. Category filtering runs on that options list. No `search_documents` lookup for categories.

### Completion behavior

7. Completions are the union of matching product, brand, and category labels. URL is `/shop?q=` plus the **full** label (`Classic Tee` → `/shop?q=Classic Tee`).
8. Dedupe by `textNormalize` **across groups**: product `Acme` and brand `Acme` yield one completion. `TEE` / `Tee` / `tee` remain one row. Keep the first label after short-then-alpha. A name may still appear as both a completion and an entity row.

### Token length gate

9. `q` empty, `"  "`, or `"t"`: empty groups, no `search_documents` query.
10. `"t e"` and `"classic t"`: empty groups, no `search_documents` query.
11. `"  te  "` still returns matches.

### AND semantics

12. `classic te` returns `Classic Tee`.
13. `tee park` does not return `Classic Tee`.

### Word-prefix semantics

14. `tee` returns `Tee` and `Classic Tee`.
15. `eam` does not return `Team Jersey`. `las` does not return `Classic Tee`.
16. PHP is SoT: title `Streetwear` with `q=te` is not a product hit even if SQL recall included the row.
17. `LIKE` `%` / `_` in the query are still escaped.

### No synonym expansion

18. Directed synonym `tee` → `cotton` plus product `Cotton Shirt`: `suggest('tee')` does not return that product. `ProductSuggestQuery` does not resolve `SearchSynonymExpander`.
19. `GET /shop?q=cot` still does not list a product named `Cotton Parka` via this work.

### Sorting

20. Per group: shorter label first, then case-insensitive natural order.
21. Completions for `q=te`: `Tee` before `Tee Shirt` before `Team Jersey` (`te` prefixes `Tee` / `Team`).
22. Products for `q=tee`: `Tee` before `Tee Shirt` before `Classic Tee`. **`Team Jersey` is not a `tee` hit** (`tee` is not a prefix of `Team` or `Jersey`).

### Cap 5

23. Six products that all match (e.g. `Classic Tea 1` … `Classic Tea 6` for `q=te`): products length is 5.
24. Cap is per group. Completions are capped at 5 **after** cross-group dedupe and sort.

### Suggest V1 in-place amendment

25. Suggest V1 is updated in-place at §2, §3, §5, and §7 as needed. After the edit: word-prefix + AND + token gate are documented; SQL is recall; completions dedupe across groups; no leftover whole-string-only prefix, “Do not tokenize,” or “full-title LIKE is the matcher.” Scan the whole V1 document. Synonym off and `ProductDiscoveryQuery` isolation stay.

---

## 7. Out of scope

Do not change in this unit, even slightly:

- Discovery ranking
- Merchandising
- Synonym expansion (`SearchSynonymExpander` / Octane lifecycle)
- Search index rebuild lifecycle
- Search engine replacement

Also out: SKU / attribute / description product rows, query log, substring inside a token, overlay JS redesign (debounce / thumbnails). Task 2 overlay regression means existing V1 HTTP + listing tests stay green, not a new overlay UI.

---

## 8. Delivery

```text
Task 1  Word-prefix matcher + token gate + Suggest V1 amendment
Task 2  Product recall query + brand/category matcher + overlay regression
Task 3  Cross-group completion dedupe + sort/cap + acceptance sweep
```

Human gate after each task, then whole-branch review. One spec, three tasks. Do not expand into ranking, merchandising, synonym expansion, search-index lifecycle, or search-engine replacement.
