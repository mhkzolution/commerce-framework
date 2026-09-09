# Suggest enhancement (category infix + English aliases)

**Date:** 2026-09-09  
**Status:** Approved for implementation  
**Owner:** `ProductSuggestQuery` (`modules/Product/src/Services/ProductSuggestQuery.php`)  
**Related:** `docs/superpowers/specs/2026-09-09-category-navigation-v1-design.md`

Category Navigation V1 already feeds suggest the **visible** tree (inclusive count ≥ 1). This job only changes how a query matches those nodes.

**Later job (not this spec):** 87 stub JPEG recovery.

---

## Decisions

- Category matching only. Product and brand suggest stay **token-prefix** on whitespace-split tokens (`SearchNormalizer::tokenize` + `str_starts_with`). Do not turn product matching into infix (`eam` must still miss Team Jersey).
- Still flatten `HomepageNavigationQuery::shopFilterOptions()` (visible tree). Empty categories stay absent. Aliases never resurrect a hidden slug.
- `SearchSynonym` / `SearchSynonymExpander` stay unused in suggest.
- No taxonomy changes, no product moves, no PDP/shop breadcrumb changes, no image work.

---

## Category match

A visible node is a hit when **every** query token matches that node by **either**:

1. **Infix** — after NFC + lowercase, the token is a substring of the category **name** (`mb_stripos`), or
2. **Alias** — the token equals an alias, or is a prefix of that alias (same 2-character minimum as `hasShortToken`), and the node’s slug is in that alias’s slug list.

The node must also be in the flattened visible tree. Aliases never resurrect a hidden slug.

Existing Thai prefix hits remain (`เสื้อ` → เสื้อเด็ก, `รองเท้า` → รองเท้า) because a prefix is also an infix.

Keep `LIMIT` 5 and current sort (shorter label first, then natural case-insensitive).

---

## Alias table

Hard-code on `ProductSuggestQuery`. Leaves only. Parents are not in this table; they appear only via infix on their Thai name.

| Query token (English) | Visible slugs |
|---|---|
| `swim` | `kids-swimwear`, `kids-swim-shirts` |
| `dress` | `kids-dresses` |
| `toy` | `toys` |
| `book` | `kids-books` |
| `shoes` | `kids-shoes` |

`sw` / `sho` may match via alias prefix. A slug missing from the visible tree is skipped.

---

## Surfaces

| File | Change |
|---|---|
| `modules/Product/src/Services/ProductSuggestQuery.php` | Category infix + alias map; `matchesName` for products/brands unchanged |
| `tests/Feature/Product/ProductSuggestQueryTest.php` | New cases below |

`SuggestController` stays on `shopFilterOptions()`. No new HTTP surface.

---

## Tests (TDD)

Failing tests first. Seed the target leaf with a published product so it is in the visible tree (same as the Accessories / Graphic Tees tests).

1. `swim` → category labels include ชุดว่ายน้ำเด็ก and เสื้อว่ายน้ำเด็ก; URLs use `kids-swimwear` and `kids-swim-shirts`.
2. `dress` → ชุดเดรส / `kids-dresses`.
3. `toy` → ของเล่น / `toys`. Must **not** require the parent ของเล่นและเครื่องนอนการตกแต่ง.
4. `book` → หนังสือเด็ก / `kids-books`.
5. `shoes` → รองเท้า / `kids-shoes`.
6. `ว่ายน้ำ` → ชุดว่ายน้ำเด็ก (infix; no alias).
7. `เดรส` → ชุดเดรส.
8. Empty-hide regression: inactive or zero-count leaf with slug `kids-dresses` does not appear for `dress`.
9. Product infix still rejected: `eam` does not suggest Team Jersey; `suggest_does_not_resolve_synonym_expander` stays green.

---

## Non-goals

- Admin alias UI or `SearchSynonym` wiring
- English aliases for other leaves (jeans, hats, …)
- Changing product/brand token rules
- Shop listing or PDP
- Stub JPEG recovery
- Recategorizing the 436 leftovers
