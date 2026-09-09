# Category Auto Move verification (Phase 2)

**Scope:** reassign **783** SKUs with audit confidence ≥ 90.  
**Not done:** Manual Review (130), Keep Existing (457), category creates/deletes, parent edits.

Sources:

- [`doc/category-audit.csv`](category-audit.csv)
- [`doc/category-migration-plan.md`](category-migration-plan.md)
- [`doc/category-phase1-verification.md`](category-phase1-verification.md)

Executed against `commerce_framework` on 2026-09-09.

---

## Verdict

**Auto Move complete.** Manual Review remains paused.

| Check | Expected | Actual |
|---|---|---|
| SKUs moved | 783 | **783** |
| `product_categories` row count | 1,369 | **1,369** |
| Products | 1,370 | **1,370** |
| Uncategorized | 1 (`110092`) | **1** |
| Multi-category products | 0 | **0** |
| Products now on child categories | 783 | **783** |
| Review + Keep pivots unchanged | 586 | **586** (130 review + 456 keep with a category) |
| Categories created/deleted | none | **none** (still 5 parents + 25 children) |

Rollback snapshot: table `_bak_product_categories_automove_20260909` (1,369 rows, pre-move).

---

## Destination counts (Auto Move only)

Matches the Phase 1 plan Auto Move table.

| New category | ID | Slug | Moved |
|---|---:|---|---:|
| เสื้อผ้าและเครื่องประดับ > เสื้อเด็ก | 7 | `kids-tops` | 185 |
| เสื้อผ้าและเครื่องประดับ > กางเกงเด็ก | 8 | `kids-pants` | 177 |
| เสื้อผ้าและเครื่องประดับ > รองเท้า | 15 | `kids-shoes` | 64 |
| เสื้อผ้าและเครื่องประดับ > เสื้อผ้าเด็กอ่อน | 6 | `kids-baby` | 63 |
| เสื้อผ้าและเครื่องประดับ > เสื้อแจ็คเก็ตและฮู้ด | 19 | `kids-jackets` | 55 |
| เสื้อผ้าและเครื่องประดับ > ชุดนอน | 16 | `kids-sleepwear` | 44 |
| เครื่องมือการเลี้ยงดูเด็ก > อุปกรณ์ให้นมและอาหาร | 22 | `feeding-accessories` | 39 |
| เสื้อผ้าและเครื่องประดับ > ถุงเท้า | 17 | `kids-socks` | 30 |
| เสื้อผ้าและเครื่องประดับ > ชุดเดรส | 13 | `kids-dresses` | 26 |
| เสื้อผ้าและเครื่องประดับ > ชุดแฟนซี | 18 | `kids-costumes` | 24 |
| เสื้อผ้าและเครื่องประดับ > ชุดว่ายน้ำเด็ก | 10 | `kids-swimwear` | 18 |
| เสื้อผ้าและเครื่องประดับ > หมวก | 14 | `kids-hats` | 15 |
| เสื้อผ้าและเครื่องประดับ > กางเกงยีนส์เด็ก | 9 | `kids-jeans` | 13 |
| หนังสือและดนตรี > หนังสือเด็ก | 25 | `kids-books` | 12 |
| เสื้อผ้าและเครื่องประดับ > เสื้อว่ายน้ำเด็ก | 11 | `kids-swim-shirts` | 6 |
| หนังสือและดนตรี > วิดีโอสำหรับเด็ก | 26 | `kids-video` | 6 |
| เครื่องมือการเลี้ยงดูเด็ก > รถเข็น คาร์ซีท และเป้อุ้ม | 23 | `strollers-car-seats` | 5 |
| ของเล่นและเครื่องนอนการตกแต่ง > เฟอร์นิเจอร์ห้องเด็ก | 30 | `nursery-furniture` | 1 |
| **Total** | | | **783** |

Leaves still **empty** (Manual Review / no auto SKUs): ชุดเซ็ต, เครื่องประดับ, กระเป๋า, อุปกรณ์ความปลอดภัย, ดนตรี, ของเล่น, เครื่องนอนและผ้าปู.

---

## Live tree after Auto Move

Storefront filters by exact slug, so parent listings now exclude the 783 moved SKUs.

```
สินค้าและอุปกรณ์สำหรับเด็ก (1)                         580
หนังสือและดนตรี (2)                                    2
├── หนังสือเด็ก (25)                                   12
├── วิดีโอสำหรับเด็ก (26)                               6
└── ดนตรี (27)                                          0
เครื่องมือการเลี้ยงดูเด็ก (3)                            0
├── อุปกรณ์ให้นมและอาหาร (22)                            39
├── รถเข็น คาร์ซีท และเป้อุ้ม (23)                        5
└── อุปกรณ์ความปลอดภัย (24)                              0
เสื้อผ้าและเครื่องประดับ (4)                            3
├── เสื้อผ้าเด็กอ่อน (6)                                  63
├── เสื้อเด็ก (7)                                       185
├── กางเกงเด็ก (8)                                      177
├── กางเกงยีนส์เด็ก (9)                                  13
├── ชุดว่ายน้ำเด็ก (10)                                  18
├── เสื้อว่ายน้ำเด็ก (11)                                  6
├── ชุดเซ็ต (12)                                          0
├── ชุดเดรส (13)                                         26
├── หมวก (14)                                           15
├── รองเท้า (15)                                        64
├── ชุดนอน (16)                                         44
├── ถุงเท้า (17)                                        30
├── ชุดแฟนซี (18)                                       24
├── เสื้อแจ็คเก็ตและฮู้ด (19)                            55
├── เครื่องประดับ (20)                                    0
└── กระเป๋า (21)                                          0
ของเล่นและเครื่องนอนการตกแต่ง (5)                     1
├── ของเล่น (28)                                         0
├── เครื่องนอนและผ้าปู (29)                               0
└── เฟอร์นิเจอร์ห้องเด็ก (30)                               1
```

Parent remainder is Review + Keep that were already on that parent (e.g. 1,341 − 761 auto from the kids catch-all = **580**).

---

## Spot checks

| SKU | Name meaning | Now |
|---|---|---|
| 100287 | Shark Boys Swimwear | id 10 ชุดว่ายน้ำเด็ก |
| 100289 | Boys Swim Shirt | id 11 เสื้อว่ายน้ำเด็ก |
| 100271 | Baby Organic Cotton Rib Pants | id 8 กางเกงเด็ก |
| 100283 | Relaxed Fit Jeans | id 9 กางเกงยีนส์เด็ก |
| 1000001 | Rainbow Skirt One Piece Suit | id 10 ชุดว่ายน้ำเด็ก |
| 100198 | Trainer Cup | id 22 อุปกรณ์ให้นมและอาหาร |
| 400006 | Colouring book | id 25 หนังสือเด็ก |

---

## Not moved (paused)

| Bucket | SKUs | Action |
|---|---|---|
| Manual Review (70–89) | 130 | Still on original parent |
| Keep Existing (&lt;70) | 457 | Still on original parent / none |

---

## Rollback

Pre-move pivot is in `_bak_product_categories_automove_20260909`.

```sql
START TRANSACTION;
DELETE FROM product_categories;
INSERT INTO product_categories (product_id, category_id)
SELECT product_id, category_id FROM _bak_product_categories_automove_20260909;
SELECT COUNT(*) FROM product_categories; -- expect 1369
COMMIT;
```

Do **not** delete child categories as part of this rollback; the tree can stay.
