# Order Detail and Fulfillment — v1

`/admin/orders/{uuid}` is the merchant’s primary operational screen after an order exists.

## Status model

Do **not** expand `OrderStatus` (`pending | confirmed | completed | cancelled`).

| Layer | Source | Values |
| --- | --- | --- |
| Lifecycle | `orders.status` | pending, confirmed, completed, cancelled |
| Financial | derived from `payments` | unpaid, pending, partially_paid, paid, partially_refunded, refunded |
| Fulfillment | derived from `order_shipments` | unfulfilled, partial, fulfilled, cancelled |

Financial truth is payment rows, not `meta.admin_status`. Staff labels in meta remain display-only.

## Layout

Two columns, wide admin page:

- **Main:** snapshot line items (with fulfilled/remaining), shipments + tracking, timeline, inventory reservation history, payments
- **Sidebar:** action panel, customer summary, formatted addresses, internal + customer notes, audit (created/updated by, channel, timestamps)

## Shipments

New tables `order_shipments` and `order_shipment_items`.

- Create a shipment only when lifecycle is **confirmed** or **completed** (stock already deducted at confirm).
- Quantities cannot exceed remaining unfulfilled qty per line.
- Tracking number and carrier are optional (pickup / walk-in).
- Cancel a shipment to return qty to unfulfilled. Inventory is **not** reversed on ship/cancel-ship (sale already happened at confirm).
- Permission: `orders.order.update`

## Timeline and audit

`order_events` records staff/system actions: created, confirmed, completed, cancelled, notes updated, shipment created/updated/cancelled.

Inventory history is a **separate** panel from `stock_movements` where `reference_type = order` and `reference_id = order uuid`.

Notes:

- Internal: `meta.notes`
- Customer: `meta.customer_note`
- Saving notes writes an event and sets `updated_by_user_uuid`

## Actions

| Action | When |
| --- | --- |
| Confirm | pending |
| Create shipment | confirmed or completed, remaining qty > 0 |
| Update tracking | active shipment |
| Complete | confirmed |
| Cancel | pending or confirmed |
| Save notes | not cancelled (view still shows notes) |

Confirm / complete / cancel keep existing inventory policy (`docs/superpowers/specs/2026-09-04-order-inventory-reservation.md`).

## Out of v1

Refunds, returns/RMA, emails, pick/pack states, editing line items, capturing payment from this screen.
