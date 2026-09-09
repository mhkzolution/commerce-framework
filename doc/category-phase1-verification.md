# Category Phase 1 verification

**Scope:** create 25 child categories only.  
**Not done:** product moves, Auto Move, Manual Review, parent edits, deletes.

Source plan: [`doc/category-migration-plan.md`](category-migration-plan.md)

Executed against `commerce_framework` on 2026-09-09.

---

## Verdict

**Phase 1 complete.** 25 children exist, all empty. Product assignments are unchanged.

| Check | Expected | Actual |
|---|---|---|
| New children | 25 | **25** |
| Total categories | 30 | **30** |
| Duplicate slugs | none | **none** (30 unique / 30) |
| Products | 1,370 | **1,370** |
| `product_categories` | 1,369 | **1,369** |
| Uncategorized products | 1 (`110092`) | **1** |
| Pivots on new children | 0 | **0** |
| Children under `สินค้าและอุปกรณ์สำหรับเด็ก` | 0 | **0** |
| Orphan `parent_id` | 0 | **0** |
| `meta.batch` on new rows | `2026-09-09-category-children` | **25 / 25** |
| `meta.migration` | `category-tree-v1` | **25 / 25** |
| Parent `updated_at` / name / slug | unchanged | **unchanged** |
| `seo_entries` for `category` | 0 | **0** |

---

## Duplicate slug check

| Metric | Result |
|---|---|
| Slugs in `categories` (not deleted) | 30 |
| Distinct slugs | 30 |
| Duplicates | **none** |
| Planned child slugs already present before insert | none (preflight) |
| Collision with parent slugs `item`…`item-4` | none |

---

## Existing parents (not modified)

| ID | Name | Slug | Parent | Products | `updated_at` |
|---|---|---|---|---:|---|
| 1 | สินค้าและอุปกรณ์สำหรับเด็ก | `item` | — | 1,341 | 2026-09-09 06:22:40 (unchanged) |
| 2 | หนังสือและดนตรี | `item-1` | — | 20 | 2026-09-09 06:22:49 (unchanged) |
| 3 | เครื่องมือการเลี้ยงดูเด็ก | `item-2` | — | 1 | 2026-09-09 06:23:00 (unchanged) |
| 4 | เสื้อผ้าและเครื่องประดับ | `item-3` | — | 6 | 2026-09-09 06:23:04 (unchanged) |
| 5 | ของเล่นและเครื่องนอนการตกแต่ง | `item-4` | — | 1 | 2026-09-09 06:23:07 (unchanged) |

---

## Categories created

All 25 are `is_active = 1`, `product_categories` count **0**.

| ID | Parent ID | Parent name | Name | Slug | Position | UUID |
|---|---:|---|---|---|---:|---|
| 6 | 4 | เสื้อผ้าและเครื่องประดับ | เสื้อผ้าเด็กอ่อน | `kids-baby` | 10 | `71fa24d0-3f13-4d34-9487-e0f6d625e4aa` |
| 7 | 4 | เสื้อผ้าและเครื่องประดับ | เสื้อเด็ก | `kids-tops` | 20 | `d8af096e-1370-4726-9a88-363c065b0585` |
| 8 | 4 | เสื้อผ้าและเครื่องประดับ | กางเกงเด็ก | `kids-pants` | 30 | `bba4549b-1ee7-4df3-8faf-f1d6348a3c6b` |
| 9 | 4 | เสื้อผ้าและเครื่องประดับ | กางเกงยีนส์เด็ก | `kids-jeans` | 40 | `a7df3fce-82e6-4f13-a272-49437cc3285c` |
| 10 | 4 | เสื้อผ้าและเครื่องประดับ | ชุดว่ายน้ำเด็ก | `kids-swimwear` | 50 | `ae1fc99a-9325-4357-b419-3dc792134c02` |
| 11 | 4 | เสื้อผ้าและเครื่องประดับ | เสื้อว่ายน้ำเด็ก | `kids-swim-shirts` | 60 | `ea046264-2039-4baf-abf4-198a305a38b8` |
| 12 | 4 | เสื้อผ้าและเครื่องประดับ | ชุดเซ็ต | `kids-sets` | 70 | `599dc629-70d2-4dcd-b947-8b2a821e673b` |
| 13 | 4 | เสื้อผ้าและเครื่องประดับ | ชุดเดรส | `kids-dresses` | 80 | `0a9f1fc7-0272-4207-a837-00cd38624278` |
| 14 | 4 | เสื้อผ้าและเครื่องประดับ | หมวก | `kids-hats` | 90 | `b26be4f8-31fd-4374-b993-16b5672e6f24` |
| 15 | 4 | เสื้อผ้าและเครื่องประดับ | รองเท้า | `kids-shoes` | 100 | `2defc90e-4c21-4430-94b1-391392a9b6f8` |
| 16 | 4 | เสื้อผ้าและเครื่องประดับ | ชุดนอน | `kids-sleepwear` | 110 | `f923ae2c-872d-4762-ba85-a26a503502c3` |
| 17 | 4 | เสื้อผ้าและเครื่องประดับ | ถุงเท้า | `kids-socks` | 120 | `946731a4-9e30-4901-841e-77191b687111` |
| 18 | 4 | เสื้อผ้าและเครื่องประดับ | ชุดแฟนซี | `kids-costumes` | 130 | `fc3c3257-2101-4291-987a-da4388b128f7` |
| 19 | 4 | เสื้อผ้าและเครื่องประดับ | เสื้อแจ็คเก็ตและฮู้ด | `kids-jackets` | 140 | `a1f9a7be-8254-49bb-87ed-7ddfa8cb3945` |
| 20 | 4 | เสื้อผ้าและเครื่องประดับ | เครื่องประดับ | `kids-accessories` | 150 | `0a39f489-4452-495f-9cf0-b8b3580abd2b` |
| 21 | 4 | เสื้อผ้าและเครื่องประดับ | กระเป๋า | `kids-bags` | 160 | `d45ea4a2-eca8-4b39-84d2-f02cac0c37ff` |
| 22 | 3 | เครื่องมือการเลี้ยงดูเด็ก | อุปกรณ์ให้นมและอาหาร | `feeding-accessories` | 10 | `705fe671-941b-4ace-83b2-6930da42e24c` |
| 23 | 3 | เครื่องมือการเลี้ยงดูเด็ก | รถเข็น คาร์ซีท และเป้อุ้ม | `strollers-car-seats` | 20 | `874c161a-c224-4852-bd32-2a5fd70ad376` |
| 24 | 3 | เครื่องมือการเลี้ยงดูเด็ก | อุปกรณ์ความปลอดภัย | `safety-gear` | 30 | `3ab6fd1a-41b2-4fd1-a224-1d7632ddb299` |
| 25 | 2 | หนังสือและดนตรี | หนังสือเด็ก | `kids-books` | 10 | `2ea3a360-7736-4149-9bc3-1954361ed7c5` |
| 26 | 2 | หนังสือและดนตรี | วิดีโอสำหรับเด็ก | `kids-video` | 20 | `4b060f8b-dfbf-4fa8-9dd3-546bf601c657` |
| 27 | 2 | หนังสือและดนตรี | ดนตรี | `kids-music` | 30 | `7e2adfcf-ce95-4f60-89f4-87754b0c92d4` |
| 28 | 5 | ของเล่นและเครื่องนอนการตกแต่ง | ของเล่น | `toys` | 10 | `0c597644-e45a-4365-b679-c3be3c6e3765` |
| 29 | 5 | ของเล่นและเครื่องนอนการตกแต่ง | เครื่องนอนและผ้าปู | `bedding-textiles` | 20 | `932faa12-58d1-445c-98f8-3bd7c4e96355` |
| 30 | 5 | ของเล่นและเครื่องนอนการตกแต่ง | เฟอร์นิเจอร์ห้องเด็ก | `nursery-furniture` | 30 | `b529334e-6633-4b9d-b3f1-e3f6c5e3f0fc` |

---

## Tree verification

```
สินค้าและอุปกรณ์สำหรับเด็ก (id 1, item)                    products 1341
หนังสือและดนตรี (id 2, item-1)                             products 20
├── หนังสือเด็ก (id 25, kids-books)                        products 0
├── วิดีโอสำหรับเด็ก (id 26, kids-video)                    products 0
└── ดนตรี (id 27, kids-music)                                products 0
เครื่องมือการเลี้ยงดูเด็ก (id 3, item-2)                     products 1
├── อุปกรณ์ให้นมและอาหาร (id 22, feeding-accessories)         products 0
├── รถเข็น คาร์ซีท และเป้อุ้ม (id 23, strollers-car-seats)     products 0
└── อุปกรณ์ความปลอดภัย (id 24, safety-gear)                 products 0
เสื้อผ้าและเครื่องประดับ (id 4, item-3)                     products 6
├── เสื้อผ้าเด็กอ่อน (id 6, kids-baby)
├── เสื้อเด็ก (id 7, kids-tops)
├── กางเกงเด็ก (id 8, kids-pants)
├── กางเกงยีนส์เด็ก (id 9, kids-jeans)
├── ชุดว่ายน้ำเด็ก (id 10, kids-swimwear)
├── เสื้อว่ายน้ำเด็ก (id 11, kids-swim-shirts)
├── ชุดเซ็ต (id 12, kids-sets)
├── ชุดเดรส (id 13, kids-dresses)
├── หมวก (id 14, kids-hats)
├── รองเท้า (id 15, kids-shoes)
├── ชุดนอน (id 16, kids-sleepwear)
├── ถุงเท้า (id 17, kids-socks)
├── ชุดแฟนซี (id 18, kids-costumes)
├── เสื้อแจ็คเก็ตและฮู้ด (id 19, kids-jackets)
├── เครื่องประดับ (id 20, kids-accessories)
└── กระเป๋า (id 21, kids-bags)
ของเล่นและเครื่องนอนการตกแต่ง (id 5, item-4)              products 1
├── ของเล่น (id 28, toys)                                    products 0
├── เครื่องนอนและผ้าปู (id 29, bedding-textiles)              products 0
└── เฟอร์นิเจอร์ห้องเด็ก (id 30, nursery-furniture)            products 0
```

Counts by parent:

| Parent | Children created |
|---|---:|
| id 4 เสื้อผ้าและเครื่องประดับ | 16 |
| id 3 เครื่องมือการเลี้ยงดูเด็ก | 3 |
| id 2 หนังสือและดนตรี | 3 |
| id 5 ของเล่นและเครื่องนอนการตกแต่ง | 3 |
| id 1 สินค้าและอุปกรณ์สำหรับเด็ก | 0 |

Slugs and positions match the Phase 1 table in the migration plan.

---

## Product assignments

No `product_categories` rows were inserted, updated, or deleted.

All 1,369 existing assignments remain on the five parent IDs. New child IDs 6–30 have **zero** products.

Auto Move and Manual Review were not run.

---

## Stamp

```json
{"migration": "category-tree-v1", "batch": "2026-09-09-category-children"}
```

Rollback (if ever needed) can target `meta.batch = 2026-09-09-category-children` **only after** confirming those IDs still have zero `product_categories` rows. Do not delete parents.
