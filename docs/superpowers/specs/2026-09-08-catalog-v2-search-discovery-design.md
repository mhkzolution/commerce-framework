# Catalog V2 Phase 2: Search and Discovery

**Date:** 2026-09-08  
**Status:** Locked  
**Owner:** Catalog (`modules/Catalog`) + Product search index (`modules/Product`) + storefront shop (`modules/Cart`)  
**Related:** `docs/superpowers/specs/2026-09-07-catalog-v2-attribute-variant-design.md`, `docs/superpowers/specs/2026-09-07-product-create-stock-design.md`

This spec is one implementation unit: **Discovery Search** on the Phase 1 relation SoT. The search index returns candidate product ids. Facets, attribute filters, category, and brand stay on relations.

It does **not** introduce Meilisearch, Typesense, or a search-platform migration. It does **not** change inventory, stock policy, `constrainInStock()`, variant identity, PDP combination rules, or workspace generate/save.

---

## 1. Problem

Shop search today is not discovery.

`ProductSearchIndexer` writes `search_documents` with product name, stripped description, **default variant SKU only**, and the first category name. `DatabaseSearchQuery` matches `LIKE` on `title`/`body` and sorts by `title`. There is no attribute indexing, no brand field, no ranking by field, no synonyms, and no facet counts on the result set.

Phase 1 already made shop **filters** relation-only (`attribute_values.code`, any-variant match for axes). Search still cannot find a product by a non-default SKU, an attribute label, or a synonym. Facet chrome is still hard-wired to size/color config groups instead of every `is_filterable` attribute.

---

## 2. Goals

1. One search document per product. Listing remains one card per product (Phase 1).
2. Search matches name, all variant SKUs, brand, categories, attribute **code and label**, and description.
3. Query pipeline: Exact SKU (whole string) → tokenize → directed synonym **replace** → AND across tokens, OR across fields, **exact token** only.
4. Ranking uses fixed field weights. No merchandising, popularity, or manual boosts.
5. Facet counts are SQL on relations, self-excluding, scoped to the current search result set.
6. Filter URLs use `attributes.code` = `attribute_values.code`, one value per attribute. Reserved system params do not collide with attribute codes.
7. Reindex is event-driven. Synonym edits take effect at query time without a full rebuild.

Non-goals: suggest/autocomplete, popular searches, merchandising rules, redirect keywords, typo tolerance, prefix/substring tokens, multi-select facets, category path, collections, tags, SEO keywords, CSV/API/feeds, listing cards per variant, Stock V1 refactor.

---

## 3. Locked decisions

| Topic | Decision |
|---|---|
| Scope | Discovery Search (not Catalog-only, not Search Platform). |
| Document grain | One document per product. `document_id` = `product.uuid`. |
| Engine | Keep `SearchIndexInterface` + `search_documents`. No Meilisearch/Typesense. |
| Split | Index: text, ranking, synonyms, candidate ids. Relations: facets, attribute/category/brand filters. |
| Attribute search | Index `attribute_values.code` **and** `label`. Filter/URL/identity remain `code` only. |
| Ranking | Fixed weights: Exact SKU → name → brand → category → attribute → description. |
| Exact SKU | Whole-query match, trim + uppercase both sides, full string equality. Not contains/prefix/hyphen-stripped. Wins over all text hits. Other hits may follow. |
| Multi-term | Whitespace tokens. AND across tokens. OR across fields. |
| Token match | Exact token only after lowercase + trim. `cot` ≠ `cotton`. `tee` ≠ `t-shirt` unless a synonym maps them. |
| Synonyms | Directed `from_term` → `to_term`. Replace the token. Not groups, bidirectional, weighted, or OR-expand. |
| Facet counts | Self-excluding: search results + other filters − this facet’s dimension. Still scoped to the search set. |
| Facet attributes | `attributes.is_filterable` only. Match rules stay Phase 1 (non-axis = product-level row; axis = any variant). |
| URL | One query param per filterable attribute. Name = `attributes.code`. Value = `attribute_values.code`. Single value. No multi-select. |
| Reserved params | `q`, `category`, `brand`, `sort`, `availability`, `price_min`, `price_max`, `page`. Creating an attribute whose `code` collides is rejected. `brand` is the Brand model slug. |
| Query param | Canonical search param is `q`. Existing `search` is accepted as an alias for `q` so current shop bookmarks keep working. |
| Reindex | Product save / product attribute-brand-category change → that product. `attribute_values.label` change → products that reference that value. Synonym CUD → no reindex. Admin full rebuild command. |
| Stock | `constrainInStock()` unchanged. `availability=in_stock` stays on the stock spec. |
| JSON | Indexer does not read `variant_options`, `options`, or `specifications`. |

---

## 4. Source of Truth

```text
Relations (Phase 1)                 Search index (derived)
─────────────────                   ─────────────────────
products + variants + PAV    →     one search_documents row
attribute_values.code/label   →     payload.attributes[]
brands / categories           →     payload brand_* / category_names[]
facet / filter / URL          →     still relations, never the index
```

The index is a **read model**. If JSON leftover in `meta` disagrees with relations, the document follows relations.

---

## 5. Search document

Keep table `search_documents` (`title`, `body`, `payload`). Do not add a second product index table in V1.

```text
document_id    product.uuid
title          product.name
body           strip_html(product.description)

payload
  uuid
  status
  skus[]              every variant SKU
  brand_name
  brand_slug
  category_names[]   every assigned category name (not path)
  attributes[]        { code, label } from relation PAV with attribute_value_id
                      (product-level specs + variant-scoped values; unique by code)
```

Not in V1: category path, collections, tags, SEO, popularity, sales rank, click metrics, merchandising boosts, JSON options, `meta.specifications`.

Unpublished/draft products stay in the index with `payload.status`. Storefront search and empty-`q` browse only return `visibleOnStorefront()` products. Deleting a product deletes its search document.

---

## 6. Query pipeline

```text
1. Exact SKU
   normalize(query) = uppercase(trim(query))
   normalize(sku)    = uppercase(sku)
   if normalize(query) == any payload.skus[]
     → that product ranks first
     → then continue with remaining ranked text matches (do not hide the rest of the shop)

2. Tokenize
   lowercase, trim, split on whitespace
   do not split on hyphens
   "TSHIRT-RED" stays one token (and is usually consumed by step 1)

3. Synonym replace (per token)
   if from_term (normalized) maps to to_term → replace that token
   ผ้าฝ้าย tee  →  [cotton, tee]

4. AND across tokens / OR across fields
   every token must exact-match at least one field token in:
     name, brand_name, category_names, attribute code, attribute label, description
   (SKUs in this step are exact tokens too if the whole-query SKU check missed)

5. Rank remaining hits by the first matching field in the weight list
```

### 6.1 Exact SKU examples

```text
abc123      = ABC123      ✓
 ABC123     = ABC123      ✓
abc123      = ABC1234     ✗
abc123      = XABC123     ✗
TSHIRT-RED  = tshirt-red  ✓
TSHIRTRED   = TSHIRT-RED  ✗
```

### 6.2 Token examples

```text
Query cotton / Doc "cotton shirt"     → match
Query cot    / Doc "cotton shirt"    → no match
Query tee    / Doc "t-shirt"         → no match (unless synonym tee → t-shirt or reverse mapping)
Query red cotton tee
  Name = Classic Tee
  Attr = color:red, material:cotton   → match
Query red cotton tee
  Name = Red Jacket
  Attr = material:polyester           → no match
```

### 6.3 Ranking weights (among matches)

1. Exact SKU (step 1)  
2. Product name  
3. Brand  
4. Category  
5. Attribute code / label  
6. Description  

No merchandising boosts, manual ranking rules, or popularity signals. Ranking does not change who matches; it only orders the AND set.

---

## 7. Synonyms

Storage: directed rows `from_term` → `to_term` (normalized the same way as query tokens: lowercase + trim).

Query-time only. Create/update/delete does **not** reindex.

Not in V1: synonym groups, automatic reverse mapping, weights, OR expansion, multiple expansion candidates.

`cotton → ผ้าฝ้าย` is a **separate** row if staff want the reverse.

---

## 8. Facets and filters

After the index returns candidate product ids (or the full published catalog when `q` is empty):

1. Apply relation filters: category, brand, price, `availability`, each filterable attribute code.  
2. Attribute match rules **stay Phase 1**:  
   - non-axis → product-level PAV (`product_variant_id` null)  
   - axis → any variant PAV  
   - token = `attribute_values.code`  
3. Facet counts: for dimension D, count values on (search set + all filters except D). Color counts ignore `color=`. Size counts ignore `size=`. Brand counts ignore `brand=`.  
4. Facet chrome lists **every** `is_filterable` attribute, not only the old size/color config groups.  
5. `constrainInStock()` is unchanged.

Empty `q`: skip the index; browse + facets run on the published catalog with the same relation filters.

---

## 9. URLs

```text
?q=tee
&category=shirts
&brand=nike
&color=red
&material=cotton
&availability=in_stock
&sort=latest
&page=1
```

- `q` = search string (alias: `search`)  
- `brand` = Brand slug (not an attribute)  
- `category` = category slug/code as today  
- Other keys that equal a filterable `attributes.code` = that attribute’s `attribute_values.code`  
- One value per attribute. Unknown or colliding attribute codes are not created.

Reserved: `q`, `category`, `brand`, `sort`, `availability`, `price_min`, `price_max`, `page`.

---

## 10. Reindex

| Event | Action |
|---|---|
| Product workspace save (create/update) | Reindex that product document |
| Product brand / categories / attribute values change | Reindex that product document |
| `attribute_values.label` change | Reindex products that reference that `attribute_value_id` |
| `attribute_values.code` | Immutable; no rename path |
| Synonym CUD | No reindex; next query expands with the new map |
| Product delete | Delete that search document |
| Product unpublish / visibility change | Reindex that product (`payload.status`); storefront still uses `visibleOnStorefront()` |
| Admin “Rebuild Search Index” | Flush + rebuild all product documents |

Save-path indexing stays **synchronous** in V1 (same as today’s indexer). No new queue topology.

---

## 11. Delivery waves

Implementation (not a spec change) uses human gates:

```text
Wave 1  Search schema / indexer / query pipeline
Wave 2  Admin synonyms + rebuild
Wave 3  Storefront search, generalized facets, URL contract
        Regression
```

Do not merge Wave 3 storefront chrome with Wave 1 indexer. Do not change PDP or Stock V1 in any wave.

---

## 12. Tests (acceptance)

1. Index includes every variant SKU. Searching the non-default SKU (exact, case-insensitive) ranks that product first.  
2. Searching `red` matches a variable product whose **default** variant is Blue if a Red variant exists (search uses indexed attribute tokens; listing still one card).  
3. `?color=red` still uses `attribute_values.code` and Phase 1 any-variant filter. Renaming label Red → Crimson Red does not break the URL; search finds “Crimson Red” after reindex of affected products.  
4. `ผ้าฝ้าย tee` with synonym `ผ้าฝ้าย → cotton` matches a product named Classic Tee with material cotton. Reverse mapping is not implied.  
5. `cot` does not match `cotton`. `tee` does not match `t-shirt` without a synonym.  
6. `red cotton tee` requires all three tokens; a Red Jacket with polyester does not match.  
7. Facet: with `q=tee&color=red`, Color facet still shows a non-zero count for `blue` if a tee with a Blue variant exists in the search set; Size counts are restricted to red tees.  
8. `?material=cotton` filters via relations (not leftover `product_attribute_values.value` text).  
9. Creating an attribute with `code=brand` (or another reserved key) is rejected.  
10. Changing a synonym does not rewrite `search_documents`. The next search uses the new expansion.  
11. `availability=in_stock` still uses `constrainInStock()` (untracked / allow / notify at 0 remain visible per stock spec).  
12. Indexer never reads `meta.variant_options` / `meta.options` / `meta.specifications`.

---

## 13. Out of scope (later specs)

- Suggest, autocomplete, popular searches, redirect keywords  
- Typo tolerance, prefix tokens, substring contains, hyphen-insensitive SKU  
- Multi-select facets  
- Meilisearch / Typesense / other search engines  
- Merchandising rules, manual ranking, popularity  
- CSV import/export search columns  
- Public API search redesign  
- JSON-LD / shopping feeds  
- Shop listing one card per variant  
- Changing stock, backorder, or SKU uniqueness rules  
- Deleting leftover JSON columns from the schema  
