# Catalog V2 Follow-up: Ranking V1

**Date:** 2026-09-09  
**Status:** Locked  
**Owner:** Product search (`modules/Product`) + storefront listing sort (`ShopProductQuery`)  
**Related:** `docs/superpowers/specs/2026-09-08-catalog-v2-search-discovery-design.md`, `docs/superpowers/specs/2026-09-08-catalog-v2-search-index-operations-design.md`

This spec is one implementation unit: **candidate ordering** inside `ProductDiscoveryQuery`. Highest matched field stays the primary rank. Same-field ties add **coverage** (distinct expanded query tokens in the winning field), then `title`.

It amends Discovery in-place for ranking, §6.3, the candidate-cap row, and acceptance, and the Index Ops `candidateUuids` `usort` line so cap-after-sort still names this order. After the edits, no remaining wording implies that same-field ties are resolved by title only.

Who matches stays the Discovery AND set. Suggest, merchandising (pins, keyword rules, redirects), synonym lifecycle, rebuild, and the search engine stay out.

---

## 1. Problem

Discovery already ranks by highest matched field, then `title` ascending. Two name hits are ordered alphabetically even when one title contains more of the query. Coverage of the winning field is not used. The candidate cap of 500 therefore keeps a title-prefix of that order, not a coverage-aware prefix.

This unit does not change membership of the AND set.

---

## 2. Goals

1. Order `candidateUuids` by `fieldRank`, then coverage DESC, then `title` ASC.
2. Coverage counts **distinct** tokens from the same expanded list Discovery uses to match, and only in the **winning** field.
3. Exact SKU remains rank 1 and does not use coverage.
4. Cap 500 still runs **after** this order. Facets share that list. `price_asc` / `price_desc` still reorder within that capped set only.

Non-goals: cumulative scores across fields, popularity, pins, keyword merchandising, redirects, Suggest order, Octane synonym refresh, rebuild, search-platform migration.

---

## 3. Locked decisions

| Topic | Decision |
|---|---|
| Scope | Ranking only. No promoted products, keyword rules, or redirects. |
| Where | `ProductDiscoveryQuery` `usort` only. No new ranker class. No SQL `ORDER BY` for relevance. |
| fieldRank | Unchanged: 1 Exact SKU (whole query), 2 name, 3 brand, 4 category, 5 attribute, 6 description. Lower always beats any tie-break. |
| coverage | Only when `fieldRank >= 2`. Distinct tokens after `SearchSynonymExpander::expand` (same list as AND matching). A token counts if it is an **exact** token in the winning field. Do not count worse fields. Not cumulative across fields. |
| Distinct | Query `red red cotton` vs winning-field tokens `red cotton tee` → coverage **2** (`red`, `cotton`), not 3. |
| Expand | Directed **replace**, not OR-append. `tee → cotton` yields `[cotton]`, not `[tee, cotton]`. Coverage uses that replaced list. |
| Exact SKU | No coverage layer. Several exact-SKU hits: `title` ASC only. |
| Exact-SKU comparator | **Bypasses coverage entirely.** When both rows have `fieldRank === 1`, compare `title` only. Do not compute coverage for rank-1 rows. Do not use `[fieldRank, -coverage, title]` for a rank-1 pair (a name-token intersection on those rows must not reorder them). Mixed SKU vs text: rank 1 wins; the text hit’s coverage is never compared to the SKU row. |
| Final order | `fieldRank` ASC, coverage DESC (text ranks only), `title` ASC. Then `CANDIDATE_CAP` (500). |
| Listing | Default / relevance uses this uuid order. `sort=price_asc` / `price_desc` do not use relevance order; they sort price inside the already-capped set. |
| Facets | Same `candidateUuids` array as listing (Index ops). |
| Discovery amendment | In-place. Targets and replacement text: §5. |

---

## 4. Sort key

```text
match AND set (unchanged)
  → fieldRank
  → if fieldRank >= 2: coverage = |distinct expanded tokens ∩ winning-field tokens|
     if fieldRank == 1 (exact SKU): skip coverage
  → title ascending
  → array_slice(..., 0, 500)
```

`usort` comparator:

```text
if both fieldRank === 1:
    title <=> title          // coverage never consulted
else:
    [fieldRank, -coverage, title]
```

Exact-SKU rows: do not compute coverage. A rank-1 pair is never ordered by how many query tokens also sit in the title or other text fields.

Example — fieldRank beats coverage:

```text
Query: red cotton

A  name has cotton, description has red
   fieldRank = 2, coverage(name) = 1

B  description has red and cotton
   fieldRank = 6, coverage(description) = 2

A before B
```

Example — winning field only:

```text
Query: red cotton

A  name has red and cotton     coverage(name) = 2
B  name has cotton, description has red
   fieldRank = 2, coverage(name) = 1

A before B
```

Example — coverage uses expanded tokens (replace):

```text
query:              ผ้าฝ้าย tee
synonym:            ผ้าฝ้าย → cotton
expanded tokens:    cotton, tee

A  title = Cotton Tee
   winning field = name
   coverage(name) = 2

B  title = Tee Shirt
   cotton only in description
   winning field = name
   coverage(name) = 1

A before B
```

This does **not** assert synonym CUD, Octane worker freeze, or constructor reload.

---

## 5. Discovery replacement wording

Write into `docs/superpowers/specs/2026-09-08-catalog-v2-search-discovery-design.md` in-place. Known targets (do not only edit acceptance):

- **§3 Ranking** — keep highest field not cumulative; add coverage then title
- **§3 Candidate cap** — after **this** order (not “highest field then title only”)
- **§6.3** — replace “Tie-break among the same highest field: title ascending”
- **§12** — coverage, cap-after-coverage, price sort, expand-token coverage; keep SKU-first and non-cumulative field examples
- **Search Index Operations §5** — `candidateUuids` `usort` line names this order, then slice 500. Cap location (sort then slice, never slice then sort) and the shared listing/facet uuid list stay.

**§3 Ranking:** Highest matched field, not cumulative across fields. Same-field tie-break: distinct expanded query tokens present as exact tokens in the **winning** field (DESC), then `title` ASC. Exact SKU (rank 1) **bypasses coverage entirely** (compute nothing; two SKU hits are title-only). No merchandising.

**§3 Candidate cap:** After that full order, return at most 500 uuids. Facets for non-empty `q` use that same list. Not SQL LIMIT.

**§6.3:** Keep the field list and the red-cotton name-vs-description example. Replace the title-only tie-break sentence with coverage-in-winning-field then title. Exact-SKU pairs bypass coverage. State cap runs after this sort.

Scan the Discovery document. After the edit, none of these remain as the same-field rule: “ties resolved by title only,” “after rank (highest field, then title)” without coverage. Non-cumulative field rank, synonym replace, and listing isolation stay.

---

## 6. Tests (acceptance)

Keep existing tests that still hold: highest field is not cumulative; exact SKU ranks first; cap 500 + no SQL LIMIT; listing `cot`; ShopController one `candidateUuids` for listing and facets.

1. **fieldRank beats coverage.** Query `red cotton`. Name+description split (coverage 1 on name) ranks above a description-only hit with coverage 2. Existing `test_highest_field_rank_is_not_cumulative` stays.
2. **coverage uses the winning field only.** Query `red cotton`. Name contains both tokens ranks above a product whose name contains only `cotton` and whose description contains `red`.
3. **coverage counts DISTINCT query tokens.** Query `red red cotton`. Winning-field tokens `red cotton tee` → coverage **2**, not 3, versus a name-only `cotton` hit (coverage 1) that matches `red` only in a worse field.
4. **Exact SKU remains the highest rank; comparator bypasses coverage.** Whole-query SKU still ranks before a high-coverage name hit. Two `search_documents` whose `payload.skus` both exact-match the query: order `title` ASC (`Alpha…` before `Zed Red Cotton…`) even when the later title contains more query tokens. Coverage is not computed for those rows. Existing non-default SKU test stays.
5. **Same field, higher coverage first.** Query `red cotton`. `Red Cotton Tee` (name coverage 2) before `Cotton Parka` whose name has only `cotton` (and `red` only in description), even if title sort alone would reverse them.
6. **Same field, equal coverage → title ASC.** Query `cotton`. `Alpha Cotton` before `Zebra Cotton` (both name, coverage 1).
7. **Cap after ranking.** Existing prefix/tail cap tests stay when coverage is equal. More than 500 same-`fieldRank` hits: a lexicographically late title with **higher** coverage is inside the 500; a lexicographically early title with **lower** coverage can be the omitted 501st. Cap is not title-only then coverage.
8. **Facets and listing use the same ranked set.** Isolation: `ShopController` calls `candidateUuids` once and passes that array to listing and `buildFor`. No second title-only discovery call.
9. **`price_asc` / `price_desc` do not use relevance order.** `GET /shop?q=…&sort=price_asc`: lower price first even when relevance is worse. Default sort follows `candidateUuids`. Same capped uuid set as item 8.
10. **Discovery in-place amendment.** Discovery updated at §3 Ranking, §3 Candidate cap, §6.3, and §12. Index Ops §5 `candidateUuids` `usort` names this order (sort then slice unchanged). After the edit: coverage is documented; cap is after this order; no leftover “same-field ties are title only.” Scan both documents. Synonym **replace** (not OR-expand) stays.
11. **Coverage uses expanded Discovery tokens.** Directed replace, not union. Canonical fixture:

```text
query:              ผ้าฝ้าย tee
synonym:            ผ้าฝ้าย → cotton
expanded tokens:    cotton, tee

Product A  title = Cotton Tee
  winning field = name
  coverage(name) = 2

Product B  title = Tee Shirt
  cotton only in description
  winning field = name
  coverage(name) = 1

A before B
```

Item 11 does **not** test synonym CUD, Octane worker freeze, or expander reconstruct. Do not use a `tee → cotton` fixture that implies tokens `[tee, cotton]`.

---

## 7. Out of scope

- Merchandising: pins, keyword rules, redirects, popularity, sales rank
- Cumulative field scores
- Suggest ranking
- Synonym lifecycle / Octane freeze
- Rebuild / cap value / search engine replacement
- Changing AND matching or exact-SKU equality

---

## 8. Delivery

```text
Task 1  coverage comparator + Discovery spec amendment
Task 2  cap-after-coverage + listing / facet / price regression
```

Human gate after each task. Do not open merchandising, pinning, or keyword rules.
