# Examples Extension Design — Chapters 01, 03, 04, 05

**Goal:** Extend 4 weaker example chapters so they demonstrate the key concepts taught in their corresponding articles on ddd-symfony.cz.

**Scope:** Additive only — existing functionality stays untouched. Each chapter gets new sections appended below the current content.

**Chapters 06–09 are not touched** — their examples already match the articles well.

---

## Chapter 01 — Co je DDD

**Article teaches:** Bounded Context, Ubiquitous Language, strategic + tactical patterns.
**Current example:** Simple in-memory shopping cart.

### New Section 2 — Bounded Context

Show the same concept (`Product`) represented differently in two Bounded Contexts:

- `CatalogContext\Product` — has `name`, `description`, `stockQty`, `weight`
- `OrderContext\Product` — has `productId`, `unitPrice`, `taxRate`

Implemented as two plain PHP classes in separate namespaces under `Chapter01_WhatIsDDD`. Displayed side-by-side in the template with a short explanation: same real-world thing, different model because different purpose.

No form interaction needed — static display is sufficient to illustrate the concept.

### New Section 3 — Ubiquitous Language

Static table showing 3–4 domain terms, each with their meaning in two different contexts:

| Term | CatalogContext | OrderContext |
|------|---------------|-------------|
| Product | Catalog item with stock | Line item with price snapshot |
| Customer | Registered user with profile | Billing/shipping address holder |
| Price | Current selling price | Locked price at time of order |

Rendered as an HTML table in the template. No PHP logic needed.

---

## Chapter 03 — Základní koncepty

**Article teaches:** Entity, Value Object, Aggregate, Repository, Domain Service, Domain Events.
**Current example:** Order aggregate with `addItem()` + `confirm()` invariant enforcement.

### New Section 2 — Value Objects

Two interactive demos:

**Email VO:**
- Form: user enters an email address
- On submit: try `new Email($input)` — if valid show `Email('jan@example.com') ✓`, if invalid show `InvalidArgumentException: Invalid email address`
- PHP class: `Chapter03_BasicConcepts\Domain\Email` — readonly, validates in constructor, `__toString()`

**Money VO:**
- Form: two amount inputs + currency
- On submit: `Money::of($a, 'CZK')->add(Money::of($b, 'CZK'))` → displays result
- PHP class: `Chapter03_BasicConcepts\Domain\Money` — readonly, `add()` returns new instance, `formatted()`, throws on currency mismatch

### New Section 3 — Domain Events

Extend the existing `Chapter03_BasicConcepts\Domain\Order` aggregate:

- After `addItem()`: aggregate records `OrderItemAdded { productName, qty, lineTotal }`
- After `confirm()`: aggregate records `OrderConfirmed { orderId, totalAmount, occurredAt }`
- Aggregate has `pullEvents(): array` method that returns and clears the internal event list
- Template shows "Raised Domain Events" panel below the existing form output — event class name, timestamp, payload as JSON

PHP change: add `private array $events = []` and `pullEvents()` to the existing `Order` class (or a separate `Chapter03Order` if changing existing breaks tests).

---

## Chapter 04 — Implementace v Symfony

**Article teaches:** Full Symfony integration — Value Objects with Doctrine types, Domain Events pipeline, Application Service/Command Handler, DI.
**Current example:** Repository + Domain Service (OrderPricingService) + SQLite persistence.

### New Section 2 — Value Objects in the Entity

Extend the existing `Order` entity to use a `Money` Value Object for the total:

- Add `Chapter04_Implementation\Domain\Order\Money` VO (same as Ch03 but in this namespace)
- Change `Order::total()` return type to `Money`
- Add a Doctrine custom type `MoneyType` that persists as integer cents
- Template shows "Za kulisami (Value Object)" panel: `Money(270000, 'CZK')`, `.formatted()` = `2,700.00 CZK`, `.amount()` = `270000`
- This panel is rendered automatically after each order creation

### New Section 3 — Domain Events Pipeline

Show the full flow from aggregate to listener:

- `Order` aggregate raises `OrderPlaced` event via `$this->events[] = new OrderPlaced(...)`
- `OrderService` (application service) calls `$order->pullEvents()` after saving, dispatches via Symfony `EventDispatcherInterface`
- `OrderPlacedListener` logs the event (writes to `var/log/domain_events.log`)
- Template shows "Dispatched Events" panel: event class, `orderId`, `customerId`, `total`, `occurredAt`
- Events are passed from the controller to the template via the `$result` array

No new routes. The existing POST handler in `Chapter04Controller` is extended.

---

## Chapter 05 — CQRS

**Article teaches:** Commands, Queries, handlers, ViewModels/Read Models, denormalization, optimization.
**Current example:** Basic command/query bus with `PlaceOrderCommand` + `GetOrdersQuery`.

### New Section 2 — Write Model vs. Read Model

Static side-by-side display (no new form interaction):

**Write side panel:**
```
Order aggregate
─────────────────────────
- private $id
- private $customerId
- private $items[]
- private $status
─────────────────────────
+ place(customerId, items)
+ confirm()
+ cancel()
+ pullEvents()
─────────────────────────
Chrání invarianty.
Žádné veřejné gettery.
```

**Read side panel — OrderView:**
```
OrderView (read model)
─────────────────────────
+ string $id
+ string $customerName
+ string $totalFormatted
+ string $statusLabel
+ int $itemCount
─────────────────────────
Flat DTO, optimalizovaný
pro zobrazení. Plněný
přímo z DB (DBAL query).
```

Below both panels: show the actual `GetOrdersQueryHandler` code snippet rendering the DBAL query that builds `OrderView` directly, bypassing the aggregate. Highlight: no aggregate loaded, no repository, just a fast SELECT.

PHP: Add a `GetOrdersQueryHandler` that uses `DBAL\Connection` for the read side (currently may already use Doctrine — switch the read path to raw DBAL query). Add `itemCount` to `OrderView`.

---

## Architecture Notes

- All new PHP classes follow existing namespace conventions: `App\Chapter0X_Name\Domain\...`
- No new routes needed for Sections 2–3 of Ch04 and Ch05 — extensions render within existing page responses
- Ch01 Sections 2–3 are purely static — no PHP domain classes needed beyond the namespace examples
- Ch03 Section 2 requires a new route action or POST handler on the existing Ch03 controller
- All new Value Object classes are self-contained, no Doctrine mapping needed except Ch04 `MoneyType`
- Existing tests must continue to pass; new domain classes get their own test files where applicable
