# Category navigation V1

**Date:** 2026-09-09  
**Status:** Approved for implementation  
**Out of scope:** PDP/Category QA, 87 stub images, recategorizing the 436 leftovers, new/renamed taxonomy nodes

## Decisions

- Header **Shop** mega: one Categories column showing the **5 parents**, each with children listed underneath. Featured/Brands columns unchanged.
- Mobile drawer uses the same tree under Shop.
- Shop `?category=` on a parent matches that category **and all descendants**. A child slug matches only that leaf.
- Homepage featured tiles and arrival tabs: **top-level parents only**, product counts **include descendants**.
- Shop sidebar: same parent → children tree (not a flat chip dump of 30 leaves).
- **Active:** a parent is active when the current slug is that parent or any descendant. A child is active on exact slug match.
- **Empty visibility:** omit a node from nav/sidebar/homepage if inclusive product count (self + descendants) is 0. Empty children are omitted; a parent with only empty children and 0 own products is omitted.

## Surfaces

| Surface | Behavior |
|---|---|
| Mega menu Categories | Groups: parent heading (link) + child links |
| Mobile Shop panel | Same groups |
| Shop listing query | Inclusive IDs for the selected slug |
| Homepage arrivals filter | Same inclusive match |
| Homepage category tiles | Parents with inclusive count |
| Shop sidebar | Nested category links |
| Suggest | Flattens the visible tree (parents and children still match by name) |

## Non-goals

- CMS `navigation_menus` `main` rebuild
- Changing category records or `product_categories` for the 436
- Descendant-inclusive **facets** beyond the product listing query
