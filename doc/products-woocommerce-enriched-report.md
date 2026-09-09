# WooCommerce CSV enrichment report

Source: `ppk-product.csv` into `products-woocommerce-template.csv`.
Output: `products-woocommerce-enriched.csv`.
Match key: SKU. Existing template values were preserved; only empty copy-fields were filled.

## Counts

- Template rows: **1370**
- PPK rows: **2266** (2250 unique SKUs)
- Matched SKUs: **1369**
- Missing SKUs: **1**
- Rows updated: **1369**

## Missing SKUs

110092

## Fields filled (empty template cells only)

| Value | Rows |
| --- | ---: |
| Type | 1369 |
| Published | 1369 |
| Visibility in catalog | 1369 |
| Tax status | 1369 |
| Categories | 1369 |
| Attribute 1 name | 954 |
| Attribute 1 value(s) | 954 |
| Attribute 1 visible | 954 |
| Attribute 1 global | 954 |
| Attribute 2 name | 799 |
| Attribute 2 value(s) | 799 |
| Attribute 2 visible | 799 |
| Attribute 2 global | 799 |
| Weight (kg) | 654 |
| Meta: condition | 605 |
| Short description | 604 |
| Attribute 3 name | 392 |
| Attribute 3 value(s) | 392 |
| Attribute 3 visible | 392 |
| Attribute 3 global | 392 |
| Attribute 4 name | 285 |
| Attribute 4 value(s) | 285 |
| Attribute 4 visible | 285 |
| Attribute 4 global | 285 |

## Attribute mappings

Copied `Attribute N name` values onto template rows that had an empty name cell.

| Attribute name | Products filled |
| --- | ---: |
| สี | 939 |
| Size (เสื้อ) | 433 |
| อายุ | 427 |
| เพศ | 392 |
| Size (กางเกง) | 201 |
| Size (รองเท้า) | 37 |
| ภาษา | 1 |

## Category mappings

| Value | Rows |
| --- | ---: |
| สินค้าและอุปกรณ์สำหรับเด็ก | 1341 |
| หนังสือและดนตรี | 20 |
| เสื้อผ้าและเครื่องประดับ | 6 |
| เครื่องมือการเลี้ยงดูเด็ก | 1 |
| ของเล่นและเครื่องนอนการตกแต่ง | 1 |

## Other copied values

- Type: {'simple': 1369}
- Published: {'1': 1369}
- Visibility in catalog: {'visible': 1369}
- Tax status: {'taxable': 1369}

### Meta: condition

| Value | Rows |
| --- | ---: |
| Good | 416 |
| Fair | 83 |
| สภาพดี | 40 |
| Mint | 38 |
| New | 28 |

## Warnings

- PPK source has duplicate SKU(s) 210008; first row wins.
- PPK source has 15 row(s) with empty SKU; ignored.
- Template SKU(s) with no PPK match: 110092.
- Meta: condition uses mixed labels (Good=416, Fair=83, สภาพดี=40, Mint=38, New=28).
- Images, IDs, and URLs were not copied.
- Search ranking, synonyms, and suggest metadata are not CSV columns and were not copied.

