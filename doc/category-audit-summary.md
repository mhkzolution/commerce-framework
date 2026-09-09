# Product category audit (read-only)

**Scope:** titles from `doc/products-woocommerce-enriched.csv` vs live `commerce_framework` categories.  
**No database writes. No product updates. No import/export.**

Suggested labels are a **proposed merchandising tree** (business meaning of the title). They are **not** existing WooCommerce/category records. The live catalog only has five top-level categories and no children.

Row-level output: [`doc/category-audit.csv`](category-audit.csv) (1,370 SKUs).

---

## Totals

| Metric | Count |
|---|---|
| Total products | **1,370** |
| With a current category | 1,369 |
| Uncategorized (SKU `110092`, name is the SKU) | 1 |

**Match (current vs title-inferred meaning)**

| Match | Count | Share |
|---|---|---|
| YES | 13 | 0.9% |
| MAYBE | 454 | 33.1% |
| NO | 903 | 65.9% |

Interpretation: **YES** is rare because almost the entire catalog sits in one catch-all. A title that clearly means *Jeans* or *Swimwear* is still stored as *สินค้าและอุปกรณ์สำหรับเด็ก*, so the row is **NO** even when the SKU is “kids” in a broad sense.

---

## Confidence breakdown

| Confidence | Count | Typical case |
|---|---|---|
| high | 837 | Garment type is explicit in the title (jeans, swimsuit, bodysuit, pajamas, socks, costume, etc.) |
| medium | 88 | Type is plausible but mixed (set vs single piece, bag, feeding accessory) |
| low | 445 | Name is only a SKU, or Thai `ชุด - brand` with no garment type |

**815** of the **NO** rows are **high** confidence: the title names a type the current category does not represent.

---

## Current category distribution (live)

| Current category | Products | Role today |
|---|---|---|
| สินค้าและอุปกรณ์สำหรับเด็ก | **1,341** | Catch-all for apparel, toys, feeding, nursery, and SKU-only names |
| หนังสือและดนตรี | 20 | Books + children’s DVDs + one music CD |
| เสื้อผ้าและเครื่องประดับ | 6 | Directionally “clothing”; still too coarse |
| เครื่องมือการเลี้ยงดูเด็ก | 1 | Feeding cup — title matches this bucket |
| ของเล่นและเครื่องนอนการตกแต่ง | 1 | One stuffed toy / bedding-adjacent SKU |
| *(none)* | 1 | `110092` |

The WooCommerce `Categories` column on the source CSV matches this distribution (same five names; no hierarchy).

---

## Suggested category distribution (from titles)

These are **recommendations**, not live categories.

| Suggested category | Count |
|---|---|
| Uncategorized (title is SKU only) | 407 |
| Pants | 129 |
| T-Shirts | 101 |
| Shirts | 98 |
| Bodysuits | 62 |
| Shoes | 60 |
| Jackets / Hoodies | 46 |
| Pajamas / Sleepwear | 45 |
| Kids apparel (unspecified) | 38 |
| Polo Shirts | 36 |
| Toys & Accessories | 34 |
| Socks | 30 |
| Costumes | 29 |
| Dresses | 27 |
| Shorts | 27 |
| Feeding & Nursing | 24 |
| Swimwear | 22 |
| Bedding & Nursery textiles | 21 |
| Matching Sets | 20 |
| Leggings | 19 |
| Hats & Accessories | 18 |
| Feeding accessories | 17 |
| Jeans | 12 |
| Books | 12 |
| Swimwear > Rash Guard / Swim Shirt | 6 |
| Bags | 6 |
| Media > Children's Video | 6 |
| Strollers, Car Seats & Carriers | 5 |
| Rompers | 4 |
| Baby Pants | 3 |
| Dungarees / Overalls | 2 |
| Sports uniforms | 2 |
| Media > Music | 1 |
| Nursery furniture | 1 |

---

## Products likely miscategorized

Treat **Match = NO** + **Confidence = high** as the merchandising queue (815 SKUs).

They are not “wrong SKU assigned to the wrong leaf.” They are **specific product types dumped into a kids catch-all**.

Examples (business meaning vs current):

| SKU | Name (truncated) | Current | Suggested |
|---|---|---|---|
| 1000001 | Rainbow Skirt One Piece Suit / ชุดว่ายน้ำ… | สินค้าและอุปกรณ์สำหรับเด็ก | Swimwear |
| 1000040 | Toddler Elasticized Pull-On Slim Taper Jeans | สินค้าและอุปกรณ์สำหรับเด็ก | Jeans |
| 1000042 | Juniors' One Piece Swimsuit | สินค้าและอุปกรณ์สำหรับเด็ก | Swimwear |
| 100014 | Dinosaurs Bodysuit | สินค้าและอุปกรณ์สำหรับเด็ก | Bodysuits |
| 100011 | Stars Pajamas | สินค้าและอุปกรณ์สำหรับเด็ก | Pajamas / Sleepwear |
| 100025 | Sharks Set Body suit and Pants | สินค้าและอุปกรณ์สำหรับเด็ก | Bodysuits |
| (typical) | Shark Boys Swimwear | สินค้าและอุปกรณ์สำหรับเด็ก | Swimwear |
| (typical) | Baby Organic Cotton Rib Pants | สินค้าและอุปกรณ์สำหรับเด็ก | Baby Pants |
| (typical) | Relaxed Fit Jeans | สินค้าและอุปกรณ์สำหรับเด็ก | Jeans |
| (typical) | Boys Swim Shirt | สินค้าและอุปกรณ์สำหรับเด็ก | Swimwear > Rash Guard / Swim Shirt |

**YES (13)** — titles already sit in a bucket that matches meaning:

- Parenting / children’s **books** in `หนังสือและดนตรี`
- One feeding product in `เครื่องมือการเลี้ยงดูเด็ก`

**MAYBE (454)** — still in the kids catch-all, but the title does **not** name a safer leaf (SKU-only names, `ชุด - brand`, or generic “kids apparel”). Also: 6 SKUs already in `เสื้อผ้าและเครื่องประดับ` (right family, no garment type).

---

## Top category corrections

If a real tree is introduced, the largest **high-confidence** moves **out of** `สินค้าและอุปกรณ์สำหรับเด็ก` would be:

1. **Pants** (129) — trousers, joggers, cargo; not jeans unless the title says jeans  
2. **T-Shirts** (101) and **Shirts** (98) — split tees vs collared/button/Henley  
3. **Bodysuits** (62) — onesies / บอดี้สูท, not “sets” unless the title is a set  
4. **Shoes** (60)  
5. **Jackets / Hoodies** (46)  
6. **Pajamas / Sleepwear** (45)  
7. **Polo Shirts** (36) — keep separate from T-shirts  
8. **Socks** (30), **Costumes** (29), **Dresses** (27), **Shorts** (27)  
9. **Swimwear** (22) + **Rash Guard / Swim Shirt** (6) — do not leave these in generic kids  
10. **Feeding & Nursing** (24) + **Feeding accessories** (17) — bottles, cups, teethers; not apparel  
11. **Jeans** (12) — denim named as jeans, not “pants”  
12. **Toys** (34) and **Bedding** (21) — not clothing  

**Do not** “correct” the 407 SKU-only titles into a garment leaf; there is no title to infer from.

---

## Categories that should possibly be merged

**Live tree (too few nodes, not too many):**

- Do **not** merge `สินค้าและอุปกรณ์สำหรับเด็ก` with `เสื้อผ้าและเครื่องประดับ`. The problem is the opposite: apparel is missing leaves. The 6 SKUs in `เสื้อผ้าและเครื่องประดับ` should eventually join a clothing tree, not be absorbed into the catch-all.
- **`หนังสือและดนตรี` mixes three businesses:** printed books, children’s DVDs (`Media > Children's Video`), and music. Split media from books; do not merge further.
- **`ของเล่นและเครื่องนอนการตกแต่ง`** combines toys and bedding. Those should not stay one node if volume grows.

**Proposed leaves that could merge later (after a tree exists):**

- **Pants + Baby Pants** if age is a filter, not a category  
- **T-Shirts + Polo** only if the store never merchandises polos separately (today titles distinguish them)  
- **Swimwear + Rash Guard** as parent/child, not siblings at the same level  
- **Feeding & Nursing + Feeding accessories** as one “feeding” parent  
- **Matching Sets** vs first garment in the set — merchandising choice, not a data error  

---

## Categories that appear unused

All **five live categories have at least one product.** None is unused.

What is unused is a **usable apparel taxonomy**. There are no live categories for Swimwear, Jeans, Bodysuits, Pajamas, Shoes, etc., so those types cannot be assigned today.

`110092` is the only SKU with **no** category.

---

## Method (limits)

- Inference is from **product name only** (English + Thai), not attributes, not images.
- **407** names are numeric SKUs — cannot classify.
- Thai `ชุด` is overloaded (outfit, swimsuit, pajamas, costume). Those stay **Kids apparel (unspecified)** / **low** unless a more specific word is present (ว่ายน้ำ, ชุดนอน, คอสเพลย์, …).
- Age words (Baby / Boys / Girls) were used to pick **Baby Pants** vs **Pants** where the rest of the title is pants; they were not used to invent a full age tree.

---

## Verdict

The live category model is a **single kids dump plus four sparse buckets**. Titles already describe jeans, swimwear, bodysuits, pajamas, shoes, feeding, books, and costumes. **~66% of SKUs are merchandising-mismatched** relative to a type-based tree; **~31% cannot be typed from the title**. Fixing this is a **taxonomy + recategorize** project, not a few SKU patches.

No products were changed.
