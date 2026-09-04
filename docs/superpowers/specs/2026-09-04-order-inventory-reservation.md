# Order inventory reservation policy

This document describes when inventory is reserved, deducted, and released for storefront checkout and admin create.

## Config

`reserve_on_checkout` (`config('inventory.reserve_on_checkout')`, default `true`) controls reservation after an order is created. Reservation is **not** performed inside `OrderService::create`. Confirm and cancel still consult the same flag when releasing a reservation.

## Storefront checkout

When a shopper completes checkout and `reserve_on_checkout` is true, inventory is reserved after the order row exists. On **confirm**, stock is deducted with `sale()` and the reservation is released if reserved quantity is at least the line quantity.

## Admin create

Non-draft **admin create** follows the same reservation rule: after a newly created order is persisted, inventory is reserved when `reserve_on_checkout` is true. Idempotent replays (`wasRecentlyCreated` is false) must not reserve again.

An admin **draft** does not reserve inventory. Drafts stay `pending` with `meta.admin_status = draft` until staff confirm or recreate them as a live order.

## Confirm

Confirm is allowed only for pending orders. Each line is deducted (`sale`). If `reserve_on_checkout` is true and reserved quantity is at least the line quantity, the matching reservation is released.

## Cancel

- Cancel a pending order: if `reserve_on_checkout` is true and a reservation exists (`reserved >= qty`), release it. On-hand is not increased.
- Cancel a confirmed order: return stock (`returnStock`). Reservations were already released at confirm.

## Summary

| Path | Reserve | Deduct | Release / return |
| --- | --- | --- | --- |
| Storefront checkout | After create, if `reserve_on_checkout` | On confirm | Release reservation on confirm |
| Admin create (non-draft) | After create, if `reserve_on_checkout` | On confirm | Release reservation on confirm |
| Admin draft | No | On confirm (if later confirmed) | N/A until reserved |
| Cancel pending | — | — | Release reservation if present |
| Cancel confirmed | — | — | `returnStock` |
