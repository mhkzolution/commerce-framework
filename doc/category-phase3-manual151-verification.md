# Manual 151 apply verification

**Applied:** 151 recommended moves from [`doc/category-manual-review.csv`](category-manual-review.csv).  
**Not done:** no new categories, no SKU-only edits, remaining 436 unchanged. Auto Move 783 left in place.

Executed against `commerce_framework` on 2026-09-09.

---

## Verdict

| Check | Expected | Actual |
|---|---|---|
| Moves applied | 151 | **151** |
| Frozen SKUs | 436 | **436** (pivots unchanged) |
| Auto Move still on children | 783 | **934 − 151 = 783** |
| Products on child categories | 934 | **934** |
| Products on 5 parents | 435 | **435** |
| Uncategorized | 1 (`110092`) | **1** |
| Categories | 30 | **30** |
| `product_categories` | 1,369 | **1,369** |
| Multi-assign | 0 | **0** |

Rollback snapshot: `_bak_product_categories_manual151_20260909` (1,369 rows, immediately before this apply). Auto Move snapshot `_bak_product_categories_automove_20260909` is still the pre-783-move pivot.

---

## Live tree

```
สินค้าและอุปกรณ์สำหรับเด็ก (1)                         430
หนังสือและดนตรี (2)                                    1
├── หนังสือเด็ก (25)                                   12
├── วิดีโอสำหรับเด็ก (26)                               6
└── ดนตรี (27)                                          1
เครื่องมือการเลี้ยงดูเด็ก (3)                            0
├── อุปกรณ์ให้นมและอาหาร (22)                            44
├── รถเข็น คาร์ซีท และเป้อุ้ม (23)                        5
└── อุปกรณ์ความปลอดภัย (24)                              4
เสื้อผ้าและเครื่องประดับ (4)                            3
├── เสื้อผ้าเด็กอ่อน (6)                                  63
├── เสื้อเด็ก (7)                                       229
├── กางเกงเด็ก (8)                                      178
├── กางเกงยีนส์เด็ก (9)                                  13
├── ชุดว่ายน้ำเด็ก (10)                                  18
├── เสื้อว่ายน้ำเด็ก (11)                                  6
├── ชุดเซ็ต (12)                                         24
├── ชุดเดรส (13)                                         26
├── หมวก (14)                                           15
├── รองเท้า (15)                                        65
├── ชุดนอน (16)                                         44
├── ถุงเท้า (17)                                        30
├── ชุดแฟนซี (18)                                       29
├── เสื้อแจ็คเก็ตและฮู้ด (19)                            56
├── เครื่องประดับ (20)                                    5
└── กระเป๋า (21)                                          2
ของเล่นและเครื่องนอนการตกแต่ง (5)                     1
├── ของเล่น (28)                                        36
├── เครื่องนอนและผ้าปู (29)                              21
└── เฟอร์นิเจอร์ห้องเด็ก (30)                               2
```

---

## Spot checks

| SKU | Now |
|---|---|
| 26300450 adidas Toy Story shoes | รองเท้า |
| 900112 Music For mothers | ดนตรี |
| 400005 Swim vest / เสื้อชูชีพ | อุปกรณ์ความปลอดภัย |
| 900023 CAPTAIN AMERICA | ชุดแฟนซี |
| 800051 Bag | กระเป๋า |
| 900054 Bed Sheet | เครื่องนอนและผ้าปู |
| 1000029 Shirt Set | ชุดเซ็ต |
| 210042 (SKU-only, clothing parent) | still เสื้อผ้าและเครื่องประดับ |
| 110092 | still none |

---

## Rollback (this 151 only)

```sql
START TRANSACTION;
DELETE FROM product_categories;
INSERT INTO product_categories (product_id, category_id)
SELECT product_id, category_id FROM _bak_product_categories_manual151_20260909;
SELECT COUNT(*) FROM product_categories; -- 1369
COMMIT;
```

That restores the catalog to **after Auto Move 783, before these 151**.
