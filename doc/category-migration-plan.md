# Category migration plan

**Planning only.** No database writes, no product updates, no commits in this step.

Sources:

- [`doc/category-audit.csv`](category-audit.csv) — confidence + suggested path
- [`doc/category-reclassification-plan.csv`](category-reclassification-plan.csv) — `New Category` (matches suggested on all 1,370 SKUs)

Live catalog (`commerce_framework`): five top-level categories, **no children**, every product has **at most one** category (`product_categories` has 0 multi-assign rows).

---

## Decision

| Phase | Scope | Products |
|---|---|---|
| **1 — this plan** | Create all 25 proposed child categories | **Do not modify** |
| 2 — later, not this run | Reassign SKUs | Auto Move only after checklist sign-off |
| 3 — later | Manual Review queue | Human decision |
| Never (until names exist) | Keep Existing | Leave `product_categories` as-is |

Storefront shop filters by **exact category slug**, not descendants (`ShopProductQuery::applyCategory`). Creating empty children does not change listings. A later leaf reassignment **will** remove SKUs from the parent listing unless that query is changed.

---

## Product split (from audit confidence)

| Bucket | Rule | SKUs | Product writes |
|---|---|---|---|
| **Auto Move** | confidence **≥ 90** | **783** | Phase 2 only |
| **Manual Review** | confidence **70–89** | **130** | Do not auto-apply |
| **Keep Existing** | confidence **< 70** | **457** | Do not change |

`110092` is Keep Existing and has no category.

Every Auto Move row has `Current Category` ≠ suggested path (0 already on a leaf). When Phase 2 runs, **783** pivot rows would be replaced.

---

## Exact counts per proposed category

Band is from `category-audit.csv`. Destination is `Suggested Category` / `New Category`.

| Proposed category | Auto ≥90 | Review 70–89 | Keep &lt;70 | Total in plan |
|---|---:|---:|---:|---:|
| สินค้าและอุปกรณ์สำหรับเด็ก | 0 | 0 | 432 | 432 |
| เสื้อผ้าและเครื่องประดับ *(parent, no new leaf)* | 0 | 0 | 22 | 22 |
| เสื้อผ้าและเครื่องประดับ > เสื้อเด็ก | 185 | 44 | 0 | 229 |
| เสื้อผ้าและเครื่องประดับ > กางเกงเด็ก | 177 | 1 | 0 | 178 |
| เสื้อผ้าและเครื่องประดับ > รองเท้า | 64 | 0 | 0 | 64 |
| เสื้อผ้าและเครื่องประดับ > เสื้อผ้าเด็กอ่อน | 63 | 0 | 0 | 63 |
| เสื้อผ้าและเครื่องประดับ > เสื้อแจ็คเก็ตและฮู้ด | 55 | 0 | 0 | 55 |
| เสื้อผ้าและเครื่องประดับ > ชุดนอน | 44 | 0 | 0 | 44 |
| เสื้อผ้าและเครื่องประดับ > ถุงเท้า | 30 | 0 | 0 | 30 |
| เสื้อผ้าและเครื่องประดับ > ชุดเดรส | 26 | 0 | 0 | 26 |
| เสื้อผ้าและเครื่องประดับ > ชุดแฟนซี | 24 | 0 | 0 | 24 |
| เสื้อผ้าและเครื่องประดับ > ชุดเซ็ต | 0 | 22 | 0 | 22 |
| เสื้อผ้าและเครื่องประดับ > ชุดว่ายน้ำเด็ก | 18 | 0 | 0 | 18 |
| เสื้อผ้าและเครื่องประดับ > หมวก | 15 | 0 | 0 | 15 |
| เสื้อผ้าและเครื่องประดับ > กางเกงยีนส์เด็ก | 13 | 0 | 0 | 13 |
| เสื้อผ้าและเครื่องประดับ > เสื้อว่ายน้ำเด็ก | 6 | 0 | 0 | 6 |
| เสื้อผ้าและเครื่องประดับ > เครื่องประดับ | 0 | 4 | 0 | 4 |
| เสื้อผ้าและเครื่องประดับ > กระเป๋า | 0 | 2 | 0 | 2 |
| เครื่องมือการเลี้ยงดูเด็ก > อุปกรณ์ให้นมและอาหาร | 39 | 1 | 0 | 40 |
| เครื่องมือการเลี้ยงดูเด็ก > รถเข็น คาร์ซีท และเป้อุ้ม | 5 | 0 | 0 | 5 |
| เครื่องมือการเลี้ยงดูเด็ก > อุปกรณ์ความปลอดภัย | 0 | 3 | 0 | 3 |
| หนังสือและดนตรี > หนังสือเด็ก | 12 | 0 | 0 | 12 |
| หนังสือและดนตรี > วิดีโอสำหรับเด็ก | 6 | 0 | 0 | 6 |
| หนังสือและดนตรี > ดนตรี | 0 | 1 | 0 | 1 |
| หนังสือและดนตรี *(parent)* | 0 | 0 | 1 | 1 |
| ของเล่นและเครื่องนอนการตกแต่ง > ของเล่น | 0 | 34 | 0 | 34 |
| ของเล่นและเครื่องนอนการตกแต่ง > เครื่องนอนและผ้าปู | 0 | 18 | 0 | 18 |
| ของเล่นและเครื่องนอนการตกแต่ง > เฟอร์นิเจอร์ห้องเด็ก | 1 | 0 | 0 | 1 |
| ของเล่นและเครื่องนอนการตกแต่ง *(parent)* | 0 | 0 | 1 | 1 |
| *(none)* `110092` | 0 | 0 | 1 | 1 |
| **Total** | **783** | **130** | **457** | **1,370** |

Keep Existing **does not apply** the suggested clothing-parent path for 19 `ชุด - brand` titles. Those 19 stay on `สินค้าและอุปกรณ์สำหรับเด็ก`. The 22 Keep rows on clothing parent = those 19 (still on kids today) plus 3 SKU-only names already on `เสื้อผ้าและเครื่องประดับ`.

### Auto Move (783) by destination

These are the only SKUs Phase 2 may touch without review.

| New Category | Auto SKUs |
|---|---:|
| เสื้อผ้าและเครื่องประดับ > เสื้อเด็ก | 185 |
| เสื้อผ้าและเครื่องประดับ > กางเกงเด็ก | 177 |
| เสื้อผ้าและเครื่องประดับ > รองเท้า | 64 |
| เสื้อผ้าและเครื่องประดับ > เสื้อผ้าเด็กอ่อน | 63 |
| เสื้อผ้าและเครื่องประดับ > เสื้อแจ็คเก็ตและฮู้ด | 55 |
| เสื้อผ้าและเครื่องประดับ > ชุดนอน | 44 |
| เครื่องมือการเลี้ยงดูเด็ก > อุปกรณ์ให้นมและอาหาร | 39 |
| เสื้อผ้าและเครื่องประดับ > ถุงเท้า | 30 |
| เสื้อผ้าและเครื่องประดับ > ชุดเดรส | 26 |
| เสื้อผ้าและเครื่องประดับ > ชุดแฟนซี | 24 |
| เสื้อผ้าและเครื่องประดับ > ชุดว่ายน้ำเด็ก | 18 |
| เสื้อผ้าและเครื่องประดับ > หมวก | 15 |
| เสื้อผ้าและเครื่องประดับ > กางเกงยีนส์เด็ก | 13 |
| หนังสือและดนตรี > หนังสือเด็ก | 12 |
| เสื้อผ้าและเครื่องประดับ > เสื้อว่ายน้ำเด็ก | 6 |
| หนังสือและดนตรี > วิดีโอสำหรับเด็ก | 6 |
| เครื่องมือการเลี้ยงดูเด็ก > รถเข็น คาร์ซีท และเป้อุ้ม | 5 |
| ของเล่นและเครื่องนอนการตกแต่ง > เฟอร์นิเจอร์ห้องเด็ก | 1 |
| **Total** | **783** |

Current parent of Auto Move SKUs: 761 on `สินค้าและอุปกรณ์สำหรับเด็ก`, 18 on `หนังสือและดนตรี`, 3 on `เสื้อผ้าและเครื่องประดับ`, 1 on `เครื่องมือการเลี้ยงดูเด็ก`.

### Manual Review (130) by destination

| New Category | Review SKUs |
|---|---:|
| เสื้อผ้าและเครื่องประดับ > เสื้อเด็ก | 44 |
| ของเล่นและเครื่องนอนการตกแต่ง > ของเล่น | 34 |
| เสื้อผ้าและเครื่องประดับ > ชุดเซ็ต | 22 |
| ของเล่นและเครื่องนอนการตกแต่ง > เครื่องนอนและผ้าปู | 18 |
| เสื้อผ้าและเครื่องประดับ > เครื่องประดับ | 4 |
| เครื่องมือการเลี้ยงดูเด็ก > อุปกรณ์ความปลอดภัย | 3 |
| เสื้อผ้าและเครื่องประดับ > กระเป๋า | 2 |
| เครื่องมือการเลี้ยงดูเด็ก > อุปกรณ์ให้นมและอาหาร | 1 |
| เสื้อผ้าและเครื่องประดับ > กางเกงเด็ก | 1 |
| หนังสือและดนตรี > ดนตรี | 1 |
| **Total** | **130** |

Filter: `Confidence` 70–89 in `category-audit.csv`.

### Keep Existing (457) by **current** category

| Current Category | Keep SKUs |
|---|---:|
| สินค้าและอุปกรณ์สำหรับเด็ก | 451 |
| เสื้อผ้าและเครื่องประดับ | 3 |
| ของเล่นและเครื่องนอนการตกแต่ง | 1 |
| หนังสือและดนตรี | 1 |
| *(none)* | 1 |
| **Total** | **457** |

---

## Phase 1 — create child categories (no product writes)

Create **25** children. Do **not** create a child under `สินค้าและอุปกรณ์สำหรับเด็ก`. Do **not** rename or retag the five parents.

### Live parents (do not delete)

| id | Name | Current slug | Products now |
|---|---|---|---:|
| 1 | สินค้าและอุปกรณ์สำหรับเด็ก | `item` | 1,341 |
| 2 | หนังสือและดนตรี | `item-1` | 20 |
| 3 | เครื่องมือการเลี้ยงดูเด็ก | `item-2` | 1 |
| 4 | เสื้อผ้าและเครื่องประดับ | `item-3` | 6 |
| 5 | ของเล่นและเครื่องนอนการตกแต่ง | `item-4` | 1 |

Parent slugs are already `item`…`item-4` because `Str::slug()` on Thai names collapses to empty → `item`. **Every new child must set an explicit ASCII `slug`.** If slug is omitted, new rows become `item-5`, `item-6`, … and storefront URLs are unusable.

Stamp every new row so rollback can target them:

```json
{"migration": "category-tree-v1", "batch": "2026-09-09-category-children"}
```

Use `CategoryService` / admin create (writes `uuid`, timestamps). Do not insert without `uuid`. `seo_entries` stay empty unless SEO fields are passed (`CatalogSeoSync` no-ops on null/empty).

### Children to insert

`unique(tenant_id, slug)` is active. Soft-deleted rows still occupy the slug. Confirm none of these slugs exist before insert.

| parent_id | Name | slug | position |
|---|---|---|---:|
| 4 | เสื้อผ้าเด็กอ่อน | `kids-baby` | 10 |
| 4 | เสื้อเด็ก | `kids-tops` | 20 |
| 4 | กางเกงเด็ก | `kids-pants` | 30 |
| 4 | กางเกงยีนส์เด็ก | `kids-jeans` | 40 |
| 4 | ชุดว่ายน้ำเด็ก | `kids-swimwear` | 50 |
| 4 | เสื้อว่ายน้ำเด็ก | `kids-swim-shirts` | 60 |
| 4 | ชุดเซ็ต | `kids-sets` | 70 |
| 4 | ชุดเดรส | `kids-dresses` | 80 |
| 4 | หมวก | `kids-hats` | 90 |
| 4 | รองเท้า | `kids-shoes` | 100 |
| 4 | ชุดนอน | `kids-sleepwear` | 110 |
| 4 | ถุงเท้า | `kids-socks` | 120 |
| 4 | ชุดแฟนซี | `kids-costumes` | 130 |
| 4 | เสื้อแจ็คเก็ตและฮู้ด | `kids-jackets` | 140 |
| 4 | เครื่องประดับ | `kids-accessories` | 150 |
| 4 | กระเป๋า | `kids-bags` | 160 |
| 3 | อุปกรณ์ให้นมและอาหาร | `feeding-accessories` | 10 |
| 3 | รถเข็น คาร์ซีท และเป้อุ้ม | `strollers-car-seats` | 20 |
| 3 | อุปกรณ์ความปลอดภัย | `safety-gear` | 30 |
| 2 | หนังสือเด็ก | `kids-books` | 10 |
| 2 | วิดีโอสำหรับเด็ก | `kids-video` | 20 |
| 2 | ดนตรี | `kids-music` | 30 |
| 5 | ของเล่น | `toys` | 10 |
| 5 | เครื่องนอนและผ้าปู | `bedding-textiles` | 20 |
| 5 | เฟอร์นิเจอร์ห้องเด็ก | `nursery-furniture` | 30 |

After Phase 1: `categories` = 5 parents + 25 children = **30**. `product_categories` **unchanged** (1,369 rows; 1,370 products; 1 uncategorized).

---

## SQL-safe rollback

### Why this is the unsafe path

`product_categories.category_id` is `ON DELETE CASCADE`. Deleting a **parent** that still has products **drops those pivot rows** and uncategorises the catalog. Never `DELETE FROM categories WHERE parent_id IS NULL`.

`categories` uses **soft deletes**. `UNIQUE(tenant_id, slug)` does **not** ignore `deleted_at`, so a soft-deleted child still blocks the same slug. Phase 1 rollback of **empty** children should **hard-delete**, not soft-delete.

### 1. Snapshot before any write

Run in a maintenance window. Tables are small.

```sql
START TRANSACTION;

CREATE TABLE _bak_categories_20260909 AS
SELECT * FROM categories;

CREATE TABLE _bak_product_categories_20260909 AS
SELECT * FROM product_categories;

-- verify snapshots, then:
COMMIT;
```

Optional: `SELECT COUNT(*) FROM _bak_categories_20260909;` expect **5**. `product_categories` expect **1369**.

These `_bak_*` tables are not attached to FKs. Drop them only after the tree is accepted.

### 2. Rollback Phase 1 (children only, products untouched)

Safe **only if** no product was assigned to the new children (Phase 1 guarantee).

```sql
START TRANSACTION;

-- refuse rollback if any child already has products
SELECT c.id, c.name, COUNT(pc.product_id) AS attached
FROM categories c
LEFT JOIN product_categories pc ON pc.category_id = c.id
WHERE JSON_UNQUOTE(JSON_EXTRACT(c.meta, '$.batch')) = '2026-09-09-category-children'
  AND c.deleted_at IS NULL
GROUP BY c.id, c.name
HAVING attached > 0;
-- If this returns rows: STOP. Restore from _bak_* or unassign first.

DELETE FROM seo_entries
WHERE entity_type = 'category'
  AND entity_uuid IN (
    SELECT uuid FROM categories
    WHERE JSON_UNQUOTE(JSON_EXTRACT(meta, '$.batch')) = '2026-09-09-category-children'
  );

DELETE FROM categories
WHERE JSON_UNQUOTE(JSON_EXTRACT(meta, '$.batch')) = '2026-09-09-category-children'
  AND id NOT IN (SELECT category_id FROM product_categories);

-- expect 25 rows deleted, 5 parents remain
SELECT COUNT(*) FROM categories WHERE deleted_at IS NULL;
-- expect 5

COMMIT;
```

If `meta` was not stamped, roll back by slug list (`kids-baby`, `kids-tops`, …) **and** `parent_id IS NOT NULL`, still with the “no `product_categories`” guard.

### 3. Rollback Phase 2 (only after products were moved — future)

Do **not** run this in Phase 1.

Strategy: restore the pivot from snapshot, not from guessed parents.

```sql
START TRANSACTION;

DELETE FROM product_categories;

INSERT INTO product_categories (product_id, category_id)
SELECT product_id, category_id
FROM _bak_product_categories_20260909;

-- expect 1369
SELECT COUNT(*) FROM product_categories;

COMMIT;
```

Then optionally run Phase 1 child `DELETE` if the tree should also go.

Do **not** `TRUNCATE categories`. Do **not** restore `_bak_categories_*` with `INSERT` while current IDs still exist (PK clash). Prefer: delete batch children, leave original five IDs 1–5 in place.

### 4. Emergency full restore of category rows

Only if Phase 1 corrupted the five parents (should not happen if inserts only add children):

```sql
-- inspect, do not run blindly
SELECT id, name, slug, parent_id FROM categories ORDER BY id;
SELECT id, name, slug, parent_id FROM _bak_categories_20260909;
```

Restore parent rows by `UPDATE` of those five IDs, not by deleting the table.

---

## Later product move (documented, not executed)

When Auto Move is approved:

1. Resolve SKU → `product_id` via `product_variants.sku` (SKU is not on `products`).
2. Resolve path → child `categories.id`.
3. **Replace** the single pivot row (today cardinality is 1). Do not `INSERT` a second category unless product policy changes.
4. Limit the write set to audit `Confidence >= 90`.
5. Transaction + row-count check: **783** updates.
6. Keep Existing (457) and Manual Review (130) stay on current `category_id`.

Until listing includes descendants, parent pages (`item`, `item-3`, …) will show fewer products after Phase 2. That is expected.

---

## Execution checklist

### A. Before any SQL / admin create

- [ ] Confirm this document is the runbook; **no product updates** in Phase 1
- [ ] Confirm `doc/category-audit.csv` still has 1,370 rows and bands 783 / 130 / 457
- [ ] Confirm live: 5 categories, 1,370 products, 1,369 `product_categories`, 0 children (`parent_id IS NULL` for all)
- [ ] Confirm no existing slug in the child slug list
- [ ] Create `_bak_categories_20260909` and `_bak_product_categories_20260909`
- [ ] Record `SELECT MAX(id) FROM categories` (expect 5)

### B. Phase 1 — create 25 children

- [ ] Create each child with **explicit slug**, `parent_id`, `position`, `is_active = 1`, `meta.batch = 2026-09-09-category-children`
- [ ] Do not pass SEO (keeps `seo_entries` empty)
- [ ] Do not attach products
- [ ] Verify: `SELECT COUNT(*) FROM categories WHERE parent_id IS NOT NULL` = **25**
- [ ] Verify: `SELECT COUNT(*) FROM product_categories` still **1369**
- [ ] Verify: product counts on the five parents unchanged (1,341 / 20 / 1 / 6 / 1)
- [ ] Spot-check admin category tree: 16 clothing + 3 feeding + 3 books + 3 toys children
- [ ] Storefront: parent listings unchanged (empty children by design)

### C. Stop here until a later go-ahead

- [ ] Do **not** run Auto Move
- [ ] Do **not** bulk-edit Manual Review
- [ ] Do **not** invent leaves for Keep Existing SKU-only names

### D. Phase 2 (future) — Auto Move 783

- [ ] Re-read audit; freeze CSV so SKU set cannot drift
- [ ] Dry-run: list 783 SKU → `product_id` → child id; 0 missing SKUs; 0 missing children
- [ ] Backup `product_categories` again (new `_bak_*` name)
- [ ] Apply 783 replacements in one transaction
- [ ] Counts match Auto Move destination table above
- [ ] Keep 457 and Review 130 pivot rows identical to pre-move backup
- [ ] Smoke-test shop filters on `kids-tops`, `kids-pants`, `kids-swimwear`

### E. Rollback drills (empty catalog children)

- [ ] On staging: insert 25 → run Phase 1 rollback SQL → back to 5 categories, 1,369 pivots
- [ ] Confirm cascade did not fire (`product_categories` count unchanged)

---

## Out of scope for this plan

- Import/export
- Git commit
- Recategorising Manual Review or Keep Existing
- Renaming parent slugs `item`…`item-4` (separate SEO/URL change)
- Storefront descendant-inclusive category listing

No products were changed. No categories were created by this document.
