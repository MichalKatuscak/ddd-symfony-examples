# Examples Extension Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Extend chapters 01, 03, 04, 05 of `ddd-symfony-examples` with new sections that better match their corresponding articles on ddd-symfony.cz.

**Architecture:** Each chapter is a self-contained PHP namespace under `src/ChapterXX_Name/`. Extensions are additive — new PHP classes + new form actions in existing controllers + new sections appended to existing Twig templates. No new routes needed except Ch03 which uses a `action` hidden field dispatch pattern already in place.

**Tech Stack:** PHP 8.4, Symfony 8, Doctrine ORM + DBAL, Twig, SQLite (dev).

---

## File Map

**New files to create:**
- `src/Chapter01_WhatIsDDD/Domain/BoundedContext/CatalogProduct.php`
- `src/Chapter01_WhatIsDDD/Domain/BoundedContext/OrderProduct.php`
- `src/Chapter03_BasicConcepts/Domain/Email.php`
- `src/Chapter03_BasicConcepts/Domain/Order/Events/OrderItemAdded.php`
- `src/Chapter03_BasicConcepts/Domain/Order/Events/OrderConfirmed.php`

**Files to modify:**
- `src/Chapter01_WhatIsDDD/UI/Chapter01Controller.php` — pass context objects to template
- `src/Chapter03_BasicConcepts/Domain/Order/Order.php` — record domain events
- `src/Chapter03_BasicConcepts/UI/Chapter03Controller.php` — add vo_email, vo_money, events actions
- `src/Chapter04_Implementation/UI/Chapter04Controller.php` — pass full event payload to template
- `src/Chapter05_CQRS/Application/GetOrders/GetOrdersHandler.php` — switch to DBAL read
- `templates/examples/chapter01/index.html.twig` — add BC + UL sections
- `templates/examples/chapter03/index.html.twig` — add VO + events sections
- `templates/examples/chapter04/index.html.twig` — add events pipeline + VO panels
- `templates/examples/chapter05/index.html.twig` — add write vs. read model section

---

## Task 1: Ch01 — Bounded Context demo

**Files:**
- Create: `src/Chapter01_WhatIsDDD/Domain/BoundedContext/CatalogProduct.php`
- Create: `src/Chapter01_WhatIsDDD/Domain/BoundedContext/OrderProduct.php`
- Modify: `src/Chapter01_WhatIsDDD/UI/Chapter01Controller.php`
- Modify: `templates/examples/chapter01/index.html.twig`

No tests for static demo classes.

- [ ] **Step 1: Create CatalogProduct**

```php
<?php
// src/Chapter01_WhatIsDDD/Domain/BoundedContext/CatalogProduct.php
namespace App\Chapter01_WhatIsDDD\Domain\BoundedContext;

final class CatalogProduct
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $description,
        public readonly int $stockQty,
        public readonly float $weightKg,
    ) {}
}
```

- [ ] **Step 2: Create OrderProduct**

```php
<?php
// src/Chapter01_WhatIsDDD/Domain/BoundedContext/OrderProduct.php
namespace App\Chapter01_WhatIsDDD\Domain\BoundedContext;

final class OrderProduct
{
    public function __construct(
        public readonly string $productId,
        public readonly int $unitPriceCents,
        public readonly string $currency,
        public readonly float $taxRate,
    ) {}
}
```

- [ ] **Step 3: Update Chapter01Controller — pass context objects**

Replace the entire `src/Chapter01_WhatIsDDD/UI/Chapter01Controller.php` with:

```php
<?php
namespace App\Chapter01_WhatIsDDD\UI;

use App\Chapter01_WhatIsDDD\Domain\BoundedContext\CatalogProduct;
use App\Chapter01_WhatIsDDD\Domain\BoundedContext\OrderProduct;
use App\Chapter01_WhatIsDDD\Domain\Cart\Cart;
use App\Chapter01_WhatIsDDD\Domain\Product\Price;
use App\Chapter01_WhatIsDDD\Domain\Product\Product;
use App\Chapter01_WhatIsDDD\Domain\Product\ProductId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class Chapter01Controller extends AbstractController
{
    private static array $catalog = [
        ['name' => 'Symfony v praxi', 'price' => 59900],
        ['name' => 'Domain-Driven Design', 'price' => 89900],
        ['name' => 'Clean Architecture', 'price' => 74900],
    ];

    #[Route('/examples/co-je-ddd', name: 'chapter01')]
    public function index(Request $request): Response
    {
        $products = array_map(
            fn($p) => new Product(ProductId::generate(), $p['name'], new Price($p['price'], 'CZK')),
            self::$catalog,
        );

        $cart = Cart::empty();
        if ($request->isMethod('POST')) {
            foreach ($request->request->all('items') as $idx => $qty) {
                $qty = (int) $qty;
                if ($qty > 0 && isset($products[$idx])) {
                    $cart->add($products[$idx], $qty);
                }
            }
        }

        $catalogProduct = new CatalogProduct(
            id: 'prod-42',
            name: 'Symfony v praxi',
            description: 'Kompletní průvodce frameworkem',
            stockQty: 14,
            weightKg: 0.45,
        );

        $orderProduct = new OrderProduct(
            productId: 'prod-42',
            unitPriceCents: 59900,
            currency: 'CZK',
            taxRate: 0.21,
        );

        return $this->render('examples/chapter01/index.html.twig', [
            'products' => $products,
            'cart' => $cart,
            'catalogProduct' => $catalogProduct,
            'orderProduct' => $orderProduct,
        ]);
    }
}
```

- [ ] **Step 4: Update chapter01 template — add Bounded Context section**

Append before `{% endblock %}` in `templates/examples/chapter01/index.html.twig`:

```twig
    <hr class="my-5">
    <h2>Bounded Context — stejná věc, jiný model</h2>
    <p>Produkt <code>prod-42</code> existuje ve dvou kontextech. Každý kontext modeluje jen to, co potřebuje.</p>
    <div class="row g-4">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-primary text-white"><strong>CatalogContext\Product</strong></div>
                <div class="card-body">
                    <p class="text-muted small mb-2">Zajímá ho co produkt <em>je</em> a zda je skladem.</p>
                    <table class="table table-sm mb-0">
                        <tbody>
                            <tr><th>id</th><td><code>{{ catalogProduct.id }}</code></td></tr>
                            <tr><th>name</th><td>{{ catalogProduct.name }}</td></tr>
                            <tr><th>description</th><td>{{ catalogProduct.description }}</td></tr>
                            <tr><th>stockQty</th><td>{{ catalogProduct.stockQty }} ks</td></tr>
                            <tr><th>weightKg</th><td>{{ catalogProduct.weightKg }} kg</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-success text-white"><strong>OrderContext\Product</strong></div>
                <div class="card-body">
                    <p class="text-muted small mb-2">Zajímá ho za kolik a s jakou daní se prodal.</p>
                    <table class="table table-sm mb-0">
                        <tbody>
                            <tr><th>productId</th><td><code>{{ orderProduct.productId }}</code></td></tr>
                            <tr><th>unitPriceCents</th><td>{{ orderProduct.unitPriceCents }}</td></tr>
                            <tr><th>currency</th><td>{{ orderProduct.currency }}</td></tr>
                            <tr><th>taxRate</th><td>{{ (orderProduct.taxRate * 100)|number_format(0) }} %</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <p class="text-muted mt-3"><small>Obě třídy nesou stejné <code>productId</code>, ale mají různé atributy — protože slouží různým účelům.</small></p>

    <hr class="my-5">
    <h2>Ubiquitous Language — stejný výraz, jiný význam</h2>
    <p>Jazyk musí být shodný v rámci jednoho kontextu. Mezi kontexty se stejný výraz může lišit.</p>
    <table class="table table-bordered">
        <thead class="table-light">
            <tr><th>Výraz</th><th>CatalogContext</th><th>OrderContext</th></tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Product</strong></td>
                <td>Zboží s popisem, obrázky a skladem</td>
                <td>Cenový snapshot v okamžiku nákupu</td>
            </tr>
            <tr>
                <td><strong>Price</strong></td>
                <td>Aktuální prodejní cena (může se měnit)</td>
                <td>Zamčená cena z doby objednávky</td>
            </tr>
            <tr>
                <td><strong>Customer</strong></td>
                <td>Registrovaný uživatel s profilem</td>
                <td>Fakturační a doručovací adresa</td>
            </tr>
            <tr>
                <td><strong>Stock</strong></td>
                <td>Počet kusů na skladě</td>
                <td>Rezervace v okamžiku potvrzení objednávky</td>
            </tr>
        </tbody>
    </table>
```

- [ ] **Step 5: Verify in browser**

Navigate to `http://localhost:8001/examples/co-je-ddd` — page must show the cart section as before, plus two new sections: "Bounded Context" with two cards, and "Ubiquitous Language" table.

- [ ] **Step 6: Commit**

```bash
cd /home/michal/Work/ddd-symfony-examples
git add src/Chapter01_WhatIsDDD/Domain/BoundedContext/ \
        src/Chapter01_WhatIsDDD/UI/Chapter01Controller.php \
        templates/examples/chapter01/index.html.twig
git commit -m "feat(ch01): add Bounded Context and Ubiquitous Language sections"
```

---

## Task 2: Ch03 — Value Object demo (Email + Money)

**Files:**
- Create: `src/Chapter03_BasicConcepts/Domain/Email.php`
- Modify: `src/Chapter03_BasicConcepts/UI/Chapter03Controller.php`
- Modify: `templates/examples/chapter03/index.html.twig`

- [ ] **Step 1: Write failing test for Email VO**

```php
<?php
// tests/Chapter03/Domain/EmailTest.php
namespace App\Tests\Chapter03\Domain;

use App\Chapter03_BasicConcepts\Domain\Email;
use PHPUnit\Framework\TestCase;

class EmailTest extends TestCase
{
    public function testValidEmail(): void
    {
        $email = new Email('jan@example.com');
        $this->assertSame('jan@example.com', (string) $email);
    }

    public function testInvalidEmailThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Email('not-an-email');
    }
}
```

- [ ] **Step 2: Run test — expect FAIL**

```bash
cd /home/michal/Work/ddd-symfony-examples
./vendor/bin/phpunit tests/Chapter03/Domain/EmailTest.php --testdox
```

Expected: FAIL — `App\Chapter03_BasicConcepts\Domain\Email` not found.

- [ ] **Step 3: Create Email VO**

```php
<?php
// src/Chapter03_BasicConcepts/Domain/Email.php
namespace App\Chapter03_BasicConcepts\Domain;

final readonly class Email
{
    private string $value;

    public function __construct(string $value)
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email address: ' . $value);
        }
        $this->value = strtolower($value);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
```

- [ ] **Step 4: Run test — expect PASS**

```bash
./vendor/bin/phpunit tests/Chapter03/Domain/EmailTest.php --testdox
```

Expected: PASS — 2 tests.

- [ ] **Step 5: Update Chapter03Controller — add vo_email and vo_money actions**

Replace the entire `src/Chapter03_BasicConcepts/UI/Chapter03Controller.php`:

```php
<?php
namespace App\Chapter03_BasicConcepts\UI;

use App\Chapter03_BasicConcepts\Domain\Email;
use App\Chapter03_BasicConcepts\Domain\Order\Money;
use App\Chapter03_BasicConcepts\Domain\Order\Order;
use App\Chapter03_BasicConcepts\Domain\Order\OrderId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class Chapter03Controller extends AbstractController
{
    #[Route('/examples/zakladni-koncepty', name: 'chapter03')]
    public function index(Request $request): Response
    {
        $order = Order::create(OrderId::generate(), 'student-1');
        $result = null;
        $error = null;
        $voResult = null;
        $voError = null;
        $events = [];

        if ($request->isMethod('POST')) {
            $action = $request->request->get('action');
            try {
                match ($action) {
                    'add_item' => (function () use ($order, $request, &$result, &$events) {
                        $order->addItem(
                            $request->request->get('name', 'Produkt'),
                            max(1, (int) $request->request->get('qty', 1)),
                            new Money((int) ($request->request->get('price', 100) * 100), 'CZK'),
                        );
                        $result = 'Položka přidána. Celkem: ' . $order->total()->formatted();
                        $events = $order->pullEvents();
                    })(),
                    'confirm_with_item' => (function () use ($order, &$result, &$events) {
                        $order->addItem('Demo produkt', 1, new Money(10000, 'CZK'));
                        $order->confirm();
                        $result = 'Objednávka potvrzena. Stav: ' . $order->status()->value();
                        $events = $order->pullEvents();
                    })(),
                    'confirm_empty' => (function () use ($order) {
                        $order->confirm();
                    })(),
                    'vo_email' => (function () use ($request, &$voResult) {
                        $email = new Email($request->request->get('email', ''));
                        $voResult = ['type' => 'email', 'ok' => true, 'value' => (string) $email];
                    })(),
                    'vo_money' => (function () use ($request, &$voResult) {
                        $a = new Money((int) ($request->request->get('amount_a', 0) * 100), 'CZK');
                        $b = new Money((int) ($request->request->get('amount_b', 0) * 100), 'CZK');
                        $sum = $a->add($b);
                        $voResult = [
                            'type' => 'money',
                            'ok' => true,
                            'a' => $a->formatted(),
                            'b' => $b->formatted(),
                            'sum' => $sum->formatted(),
                            'immutable' => $a->formatted(),
                        ];
                    })(),
                    default => null,
                };
            } catch (\DomainException $e) {
                $error = 'DomainException: ' . $e->getMessage();
            } catch (\InvalidArgumentException $e) {
                if (in_array($action, ['vo_email', 'vo_money'])) {
                    $voError = 'InvalidArgumentException: ' . $e->getMessage();
                } else {
                    $error = 'InvalidArgumentException: ' . $e->getMessage();
                }
            }
        }

        return $this->render('examples/chapter03/index.html.twig', [
            'order' => $order,
            'result' => $result,
            'error' => $error,
            'voResult' => $voResult,
            'voError' => $voError,
            'events' => $events,
        ]);
    }
}
```

- [ ] **Step 6: Update chapter03 template — add VO section**

Append after the closing `</div>` of the existing `row g-4` div (before `</div>` of container), in `templates/examples/chapter03/index.html.twig`:

```twig
    <hr class="my-5">
    <h2>Value Objects</h2>
    <p>Value Object je immutabilní objekt definovaný svými atributy — neporovnáváme ho podle identity ale podle hodnoty. Validuje se v konstruktoru.</p>

    {% if voResult %}
        <div class="alert alert-success">
            {% if voResult.type == 'email' %}
                Email('{{ voResult.value }}') ✓ — validní, uložen jako lowercase
            {% else %}
                Money({{ voResult.a }}) + Money({{ voResult.b }}) = <strong>{{ voResult.sum }}</strong>
                &nbsp;|&nbsp; Původní hodnota <code>$a</code> zůstala: {{ voResult.immutable }} (immutabilita ✓)
            {% endif %}
        </div>
    {% endif %}
    {% if voError %}
        <div class="alert alert-danger">{{ voError }}</div>
    {% endif %}

    <div class="row g-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><strong>Email VO</strong> — validace v konstruktoru</div>
                <div class="card-body">
                    <form method="post" data-turbo="false">
                        <input type="hidden" name="action" value="vo_email">
                        <div class="mb-2">
                            <input type="text" name="email" value="jan@example.com" class="form-control" placeholder="E-mailová adresa">
                        </div>
                        <button class="btn btn-primary">new Email($value)</button>
                    </form>
                    <pre class="mt-3 bg-light p-2 rounded small">final readonly class Email {
    public function __construct(string $value) {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException(...);
        }
        $this->value = strtolower($value);
    }
}</pre>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><strong>Money VO</strong> — immutabilní aritmetika</div>
                <div class="card-body">
                    <form method="post" data-turbo="false">
                        <input type="hidden" name="action" value="vo_money">
                        <div class="row g-2 mb-2">
                            <div class="col"><input type="number" name="amount_a" value="599" class="form-control" placeholder="Částka A (CZK)"></div>
                            <div class="col"><input type="number" name="amount_b" value="299" class="form-control" placeholder="Částka B (CZK)"></div>
                        </div>
                        <button class="btn btn-primary">$a->add($b)</button>
                    </form>
                    <pre class="mt-3 bg-light p-2 rounded small">final readonly class Money {
    public function add(self $other): self {
        // vrací NOVÝ objekt — $this se nemění
        return new self(
            $this->amount + $other->amount,
            $this->currency
        );
    }
}</pre>
                </div>
            </div>
        </div>
    </div>
```

- [ ] **Step 7: Verify in browser**

Navigate to `http://localhost:8001/examples/zakladni-koncepty`. Submit the Email form with a valid email — see success message. Submit with `invalid-email` — see `InvalidArgumentException`. Submit Money form — see sum with immutability note.

- [ ] **Step 8: Commit**

```bash
git add src/Chapter03_BasicConcepts/Domain/Email.php \
        src/Chapter03_BasicConcepts/UI/Chapter03Controller.php \
        templates/examples/chapter03/index.html.twig \
        tests/Chapter03/Domain/EmailTest.php
git commit -m "feat(ch03): add Value Object section (Email + Money)"
```

---

## Task 3: Ch03 — Domain Events

**Files:**
- Create: `src/Chapter03_BasicConcepts/Domain/Order/Events/OrderItemAdded.php`
- Create: `src/Chapter03_BasicConcepts/Domain/Order/Events/OrderConfirmed.php`
- Modify: `src/Chapter03_BasicConcepts/Domain/Order/Order.php`
- Modify: `templates/examples/chapter03/index.html.twig`

Note: `Chapter03Controller` was already updated in Task 2 to pass `$events` to the template.

- [ ] **Step 1: Write failing test for domain events**

```php
<?php
// tests/Chapter03/Domain/OrderEventsTest.php
namespace App\Tests\Chapter03\Domain;

use App\Chapter03_BasicConcepts\Domain\Order\Events\OrderConfirmed;
use App\Chapter03_BasicConcepts\Domain\Order\Events\OrderItemAdded;
use App\Chapter03_BasicConcepts\Domain\Order\Money;
use App\Chapter03_BasicConcepts\Domain\Order\Order;
use App\Chapter03_BasicConcepts\Domain\Order\OrderId;
use PHPUnit\Framework\TestCase;

class OrderEventsTest extends TestCase
{
    public function testAddItemRecordsEvent(): void
    {
        $order = Order::create(OrderId::generate(), 'student-1');
        $order->addItem('Kniha', 2, new Money(59900, 'CZK'));

        $events = $order->pullEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(OrderItemAdded::class, $events[0]);
        $this->assertSame('Kniha', $events[0]->productName);
        $this->assertSame(2, $events[0]->qty);
    }

    public function testConfirmRecordsEvent(): void
    {
        $order = Order::create(OrderId::generate(), 'student-1');
        $order->addItem('Kniha', 1, new Money(59900, 'CZK'));
        $order->pullEvents(); // clear addItem event

        $order->confirm();
        $events = $order->pullEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(OrderConfirmed::class, $events[0]);
        $this->assertSame(59900, $events[0]->totalAmount);
    }
}
```

- [ ] **Step 2: Run test — expect FAIL**

```bash
./vendor/bin/phpunit tests/Chapter03/Domain/OrderEventsTest.php --testdox
```

Expected: FAIL — event classes not found.

- [ ] **Step 3: Create OrderItemAdded event**

```php
<?php
// src/Chapter03_BasicConcepts/Domain/Order/Events/OrderItemAdded.php
namespace App\Chapter03_BasicConcepts\Domain\Order\Events;

use App\Shared\Domain\DomainEvent;

final readonly class OrderItemAdded implements DomainEvent
{
    public function __construct(
        public readonly string $orderId,
        public readonly string $productName,
        public readonly int $qty,
        public readonly int $lineTotalCents,
        private readonly \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {}

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
```

- [ ] **Step 4: Create OrderConfirmed event**

```php
<?php
// src/Chapter03_BasicConcepts/Domain/Order/Events/OrderConfirmed.php
namespace App\Chapter03_BasicConcepts\Domain\Order\Events;

use App\Shared\Domain\DomainEvent;

final readonly class OrderConfirmed implements DomainEvent
{
    public function __construct(
        public readonly string $orderId,
        public readonly int $totalAmount,
        private readonly \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {}

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
```

- [ ] **Step 5: Update Order to record events**

Replace `src/Chapter03_BasicConcepts/Domain/Order/Order.php`:

```php
<?php
namespace App\Chapter03_BasicConcepts\Domain\Order;

use App\Chapter03_BasicConcepts\Domain\Order\Events\OrderConfirmed;
use App\Chapter03_BasicConcepts\Domain\Order\Events\OrderItemAdded;
use App\Shared\Domain\AggregateRoot;

final class Order extends AggregateRoot
{
    /** @var OrderItem[] */
    private array $items = [];
    private OrderStatus $status;

    private function __construct(
        private readonly OrderId $id,
        private readonly string $customerId,
    ) {
        $this->status = OrderStatus::pending();
    }

    public static function create(OrderId $id, string $customerId): self
    {
        return new self($id, $customerId);
    }

    public function addItem(string $name, int $qty, Money $unitPrice): void
    {
        if (!$this->status->isPending()) {
            throw new \DomainException('Cannot add items to a non-pending order');
        }
        $item = new OrderItem($name, $qty, $unitPrice);
        $this->items[] = $item;
        $this->record(new OrderItemAdded(
            orderId: $this->id->value,
            productName: $name,
            qty: $qty,
            lineTotalCents: $item->lineTotal()->amount,
        ));
    }

    public function confirm(): void
    {
        if (empty($this->items)) {
            throw new \DomainException('Cannot confirm an empty order');
        }
        $this->status = OrderStatus::confirmed();
        $this->record(new OrderConfirmed(
            orderId: $this->id->value,
            totalAmount: $this->total()->amount,
        ));
    }

    public function id(): OrderId { return $this->id; }
    public function customerId(): string { return $this->customerId; }
    public function status(): OrderStatus { return $this->status; }

    public function total(): Money
    {
        return array_reduce(
            $this->items,
            fn(Money $carry, OrderItem $item) => $carry->add($item->lineTotal()),
            new Money(0, 'CZK'),
        );
    }

    /** @return OrderItem[] */
    public function items(): array { return $this->items; }
}
```

- [ ] **Step 6: Run tests — expect PASS**

```bash
./vendor/bin/phpunit tests/Chapter03/ --testdox
```

Expected: PASS — all Chapter03 tests green.

- [ ] **Step 7: Update chapter03 template — add Domain Events section**

Append after the Value Objects section (after the last `</div>` of the VO row) in `templates/examples/chapter03/index.html.twig`:

```twig
    <hr class="my-5">
    <h2>Domain Events</h2>
    <p>Agregát si zaznamenává co se stalo. Volající si eventy vyzvedne přes <code>pullEvents()</code> — agregát neví, kdo je zpracuje.</p>

    {% if events %}
        <div class="card mb-3 border-warning">
            <div class="card-header bg-warning"><strong>Raised Domain Events</strong> (po poslední akci)</div>
            <ul class="list-group list-group-flush">
                {% for event in events %}
                <li class="list-group-item">
                    <span class="badge bg-warning text-dark me-2">{{ event|class_name }}</span>
                    <small class="text-muted">{{ event.occurredAt()|date('H:i:s') }}</small>
                    <pre class="mt-1 mb-0 small bg-light p-1 rounded">{{ event|json_encode(constant('JSON_PRETTY_PRINT')) }}</pre>
                </li>
                {% endfor %}
            </ul>
        </div>
    {% else %}
        <div class="alert alert-light border">Eventy se zobrazí po akci výše (Přidat položku nebo Potvrdit objednávku).</div>
    {% endif %}

    <p class="text-muted"><small><strong>Jak to funguje:</strong> <code>Order::addItem()</code> volá <code>$this->record(new OrderItemAdded(...))</code> interně. <code>pullEvents()</code> z <code>AggregateRoot</code> eventy vrátí a smaže — jsou určeny pro jednorázové zpracování.</small></p>
```

- [ ] **Step 8: Skip class_name filter — convert events to arrays in controller instead**

We will NOT use `event|class_name` in the template. Instead the controller converts raw events to plain arrays before passing them. This avoids any Twig filter complexity. The array format is: `['class' => string, 'occurredAt' => string, 'payload' => array]`. Proceed directly to Step 9.

- [ ] **Step 9: Fix json_encode for event objects**

Domain events are readonly classes — `json_encode` won't serialize private properties by default. We need to serialize only public properties. Add a Twig filter `event_payload` or use a simpler approach in the template.

Replace the `{{ event|json_encode(...) }}` line in the template with:

```twig
                    {% set payload = {} %}
                    {% for key, val in event|keys_and_values %}
                        {% set payload = payload|merge({(key): val}) %}
                    {% endfor %}
```

Actually, the simplest approach: in the controller, convert events to arrays before passing to template. Update `Chapter03Controller` — in the three actions that set `$events`, add this mapping after `$events = $order->pullEvents()`:

```php
$events = array_map(function ($e) {
    $ref = new \ReflectionClass($e);
    $payload = [];
    foreach ($ref->getProperties(\ReflectionProperty::IS_PUBLIC) as $prop) {
        $val = $prop->getValue($e);
        $payload[$prop->getName()] = $val instanceof \DateTimeImmutable
            ? $val->format('Y-m-d H:i:s')
            : $val;
    }
    return [
        'class' => $ref->getShortName(),
        'occurredAt' => $e->occurredAt()->format('H:i:s'),
        'payload' => $payload,
    ];
}, $events);
```

And update the template to use `event.class`, `event.occurredAt`, `event.payload`:

```twig
    {% if events %}
        <div class="card mb-3 border-warning">
            <div class="card-header bg-warning"><strong>Raised Domain Events</strong> (po poslední akci)</div>
            <ul class="list-group list-group-flush">
                {% for event in events %}
                <li class="list-group-item">
                    <span class="badge bg-warning text-dark me-2">{{ event.class }}</span>
                    <small class="text-muted">{{ event.occurredAt }}</small>
                    <pre class="mt-1 mb-0 small bg-light p-1 rounded">{{ event.payload|json_encode(constant('JSON_PRETTY_PRINT')) }}</pre>
                </li>
                {% endfor %}
            </ul>
        </div>
    {% else %}
        <div class="alert alert-light border">Eventy se zobrazí po akci výše (Přidat položku nebo Potvrdit objednávku).</div>
    {% endif %}

    <p class="text-muted"><small><strong>Jak to funguje:</strong> <code>Order::addItem()</code> volá <code>$this->record(new OrderItemAdded(...))</code> interně. <code>pullEvents()</code> z <code>AggregateRoot</code> eventy vrátí a smaže — jsou určeny pro jednorázové zpracování.</small></p>
```

- [ ] **Step 10: Verify in browser**

Go to `http://localhost:8001/examples/zakladni-koncepty`. Click "Přidat položku" — see `OrderItemAdded` event with payload. Click "confirm() úspěch" — see `OrderItemAdded` + `OrderConfirmed` events.

- [ ] **Step 11: Commit**

```bash
git add src/Chapter03_BasicConcepts/Domain/Order/Events/ \
        src/Chapter03_BasicConcepts/Domain/Order/Order.php \
        src/Chapter03_BasicConcepts/UI/Chapter03Controller.php \
        templates/examples/chapter03/index.html.twig \
        tests/Chapter03/Domain/OrderEventsTest.php
git commit -m "feat(ch03): add Domain Events section (OrderItemAdded + OrderConfirmed)"
```

---

## Task 4: Ch04 — Domain Events pipeline panel

**Files:**
- Modify: `src/Chapter04_Implementation/UI/Chapter04Controller.php`
- Modify: `templates/examples/chapter04/index.html.twig`

The aggregate already raises `OrderPlaced`. We just need to surface its full payload in the template.

- [ ] **Step 1: Update Chapter04Controller to pass event data**

Replace `src/Chapter04_Implementation/UI/Chapter04Controller.php`:

```php
<?php
namespace App\Chapter04_Implementation\UI;

use App\Chapter04_Implementation\Domain\Order\Money;
use App\Chapter04_Implementation\Domain\Order\Order;
use App\Chapter04_Implementation\Domain\Order\OrderId;
use App\Chapter04_Implementation\Domain\Repository\OrderRepositoryInterface;
use App\Chapter04_Implementation\Domain\Service\OrderPricingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class Chapter04Controller extends AbstractController
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
        private readonly OrderPricingService $pricing,
    ) {}

    #[Route('/examples/implementace', name: 'chapter04')]
    public function index(Request $request): Response
    {
        $result = null;
        $dispatchedEvents = [];

        if ($request->isMethod('POST')) {
            $qty = max(1, (int) $request->request->get('qty', 1));
            $price = (int) ($request->request->get('price', 100) * 100);
            $discountedPrice = $this->pricing->applyVolumeDiscount(
                new Money($price, 'CZK'),
                $qty,
            );

            $order = Order::place(
                OrderId::generate(),
                'student-' . rand(1, 99),
                [['name' => $request->request->get('name', 'Produkt'), 'qty' => $qty, 'price' => $discountedPrice->amount]],
            );
            $this->orders->save($order);

            $rawEvents = $order->pullEvents();
            $dispatchedEvents = array_map(function ($e) {
                $ref = new \ReflectionClass($e);
                $payload = [];
                foreach ($ref->getProperties(\ReflectionProperty::IS_PUBLIC) as $prop) {
                    $val = $prop->getValue($e);
                    $payload[$prop->getName()] = $val instanceof \DateTimeImmutable
                        ? $val->format('Y-m-d H:i:s')
                        : $val;
                }
                return [
                    'class' => $ref->getShortName(),
                    'occurredAt' => $e->occurredAt()->format('H:i:s'),
                    'payload' => $payload,
                ];
            }, $rawEvents);

            $result = sprintf(
                'Objednávka uložena. Celkem: %s.',
                $order->total()->formatted(),
            );
        }

        return $this->render('examples/chapter04/index.html.twig', [
            'orders' => $this->orders->findAll(),
            'result' => $result,
            'dispatchedEvents' => $dispatchedEvents,
        ]);
    }
}
```

- [ ] **Step 2: Update chapter04 template — add events pipeline + VO panels**

Replace `templates/examples/chapter04/index.html.twig`:

```twig
{% extends 'base.html.twig' %}
{% block title %}Ukázka: Implementace DDD v Symfony{% endblock %}
{% block body %}
<div class="container mt-4">
    <div class="alert alert-info">
        Tato ukázka patří ke kapitole
        <a href="https://ddd-v-symfony.katuscak.cz/implementace-v-symfony"><strong>Implementace DDD v Symfony</strong></a>
    </div>
    <h1>Ukázka: Repository, Domain Service, Domain Event</h1>

    {% if result %}<div class="alert alert-success">{{ result }}</div>{% endif %}

    <div class="row g-4">
        <div class="col-md-5">
            <h3>Vytvořit objednávku</h3>
            <form method="post" data-turbo="false">
                <div class="mb-2"><input type="text" name="name" value="Symfony kurz" class="form-control" placeholder="Produkt"></div>
                <div class="mb-2"><input type="number" name="qty" value="3" min="1" class="form-control" placeholder="Množství (≥3 = 10% sleva)"></div>
                <div class="mb-2"><input type="number" name="price" value="1000" class="form-control" placeholder="Cena za kus (CZK)"></div>
                <button class="btn btn-primary">Zadat objednávku</button>
            </form>
            <p class="text-muted mt-2"><small>OrderPricingService: 2+ ks = −5 %, 3+ ks = −10 %</small></p>
        </div>
        <div class="col-md-7">
            <h3>Uložené objednávky (Doctrine + SQLite)</h3>
            {% if orders is empty %}
                <p class="text-muted">Zatím žádné objednávky.</p>
            {% else %}
                <table class="table table-sm">
                    <thead><tr><th>ID</th><th>Zákazník</th><th>Celkem</th></tr></thead>
                    <tbody>
                    {% for order in orders %}
                        <tr>
                            <td><code>{{ order.id().value|slice(0,8) }}…</code></td>
                            <td>{{ order.customerId() }}</td>
                            <td>{{ order.total().formatted() }}</td>
                        </tr>
                    {% endfor %}
                    </tbody>
                </table>
            {% endif %}
        </div>
    </div>

    <hr class="my-5">
    <h2>Value Object — Money za kulisami</h2>
    <p>Cena v agregátu není <code>int</code> — je to <code>Money</code> Value Object. Doctrine ho persistuje jako integer haléřů.</p>
    <div class="row g-4">
        <div class="col-md-6">
            <pre class="bg-light p-3 rounded small">// V Order agregátu:
private int $totalAmount; // haléře v DB

public function total(): Money {
    return new Money($this->totalAmount, 'CZK');
}

// Money Value Object:
final readonly class Money {
    public int $amount;    // haléře (int)
    public string $currency;

    public function formatted(): string {
        return number_format($amount / 100, 2)
            . ' ' . $currency;
    }
}</pre>
        </div>
        <div class="col-md-6">
            <table class="table table-sm">
                <thead><tr><th>Vrstva</th><th>Typ</th><th>Hodnota</th></tr></thead>
                <tbody>
                    <tr><td>Databáze</td><td><code>INTEGER</code></td><td><code>270000</code></td></tr>
                    <tr><td>PHP (Money)</td><td><code>Money</code></td><td><code>Money(270000, 'CZK')</code></td></tr>
                    <tr><td>Zobrazení</td><td><code>string</code></td><td><code>2 700,00 CZK</code></td></tr>
                </tbody>
            </table>
            <p class="text-muted small">Money se nevyskytuje nikde jako <code>int</code> v doméně — type safety je garantována.</p>
        </div>
    </div>

    <hr class="my-5">
    <h2>Domain Events pipeline</h2>
    <p>Po zadání objednávky agregát zvedne <code>OrderPlaced</code> event. Application layer ho vyzvedne přes <code>pullEvents()</code>.</p>

    {% if dispatchedEvents %}
        <div class="card border-warning mb-3">
            <div class="card-header bg-warning"><strong>Dispatched Domain Events</strong></div>
            <ul class="list-group list-group-flush">
                {% for event in dispatchedEvents %}
                <li class="list-group-item">
                    <span class="badge bg-warning text-dark me-2">{{ event.class }}</span>
                    <small class="text-muted">{{ event.occurredAt }}</small>
                    <pre class="mt-1 mb-0 small bg-light p-1 rounded">{{ event.payload|json_encode(constant('JSON_PRETTY_PRINT')) }}</pre>
                </li>
                {% endfor %}
            </ul>
        </div>
    {% else %}
        <div class="alert alert-light border">Zadej objednávku výše — event se zobrazí zde.</div>
    {% endif %}

    <pre class="bg-light p-3 rounded small">// Application layer (controller):
$order = Order::place(...);
$this->orders->save($order);           // persist

$events = $order->pullEvents();        // ← vyzvedni eventy
foreach ($events as $event) {
    $this->eventDispatcher->dispatch($event); // ← zpracuj
}

// Event listener (např. posílání e-mailu, invalidace cache...)
class OrderPlacedListener {
    public function __invoke(OrderPlaced $event): void {
        // $event->orderId, $event->customerId, $event->totalAmount
    }
}</pre>
</div>
{% endblock %}
```

- [ ] **Step 3: Verify in browser**

Go to `http://localhost:8001/examples/implementace`. Submit order — see "Domain Events pipeline" panel with `OrderPlaced` payload. Verify "Value Object — Money za kulisami" section renders correctly.

- [ ] **Step 4: Commit**

```bash
git add src/Chapter04_Implementation/UI/Chapter04Controller.php \
        templates/examples/chapter04/index.html.twig
git commit -m "feat(ch04): add Domain Events pipeline and Money VO panels"
```

---

## Task 5: Ch05 — Write vs. Read Model + DBAL handler

**Files:**
- Modify: `src/Chapter05_CQRS/Application/GetOrders/GetOrdersHandler.php`
- Modify: `templates/examples/chapter05/index.html.twig`

- [ ] **Step 1: Determine actual DB column names for ch05_orders**

Run this to see the real schema:

```bash
cd /home/michal/Work/ddd-symfony-examples
php bin/console doctrine:query:sql "PRAGMA table_info(ch05_orders)"
```

Note the actual column names from the output (will be either `customerId`/`totalAmount` or `customer_id`/`total_amount` depending on Doctrine NamingStrategy). Use the real names in the SQL query below.

- [ ] **Step 2: Update GetOrdersHandler to use DBAL**

Replace `src/Chapter05_CQRS/Application/GetOrders/GetOrdersHandler.php` using the correct column names from Step 1:

```php
<?php
namespace App\Chapter05_CQRS\Application\GetOrders;

use Doctrine\DBAL\Connection;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class GetOrdersHandler
{
    public function __construct(private readonly Connection $connection) {}

    /** @return OrderView[] */
    public function __invoke(GetOrdersQuery $query): array
    {
        // Column names depend on Doctrine NamingStrategy — verify with PRAGMA table_info first
        $rows = $this->connection->fetchAllAssociative(
            'SELECT id, customer_id, total_amount, items FROM ch05_orders ORDER BY rowid DESC'
        );

        return array_map(fn(array $row) => new OrderView(
            id: substr($row['id'], 0, 8) . '…',
            customerId: $row['customer_id'],
            total: number_format($row['total_amount'] / 100, 2) . ' CZK',
            itemCount: count(json_decode($row['items'], true)),
        ), $rows);
    }
}
```

- [ ] **Step 2: Verify query runs**

Navigate to `http://localhost:8001/examples/cqrs` — orders table must still render correctly. Dispatch a new command and verify the new order appears.

- [ ] **Step 3: Update chapter05 template — add Write vs. Read Model section**

Replace `templates/examples/chapter05/index.html.twig`:

```twig
{% extends 'base.html.twig' %}
{% block title %}Ukázka: CQRS v Symfony{% endblock %}
{% block body %}
<div class="container mt-4">
    <div class="alert alert-info">
        Tato ukázka patří ke kapitole
        <a href="https://ddd-v-symfony.katuscak.cz/cqrs"><strong>CQRS v Symfony 8</strong></a>
    </div>
    <h1>Ukázka: CQRS — Commands a Queries</h1>
    <p>Zápis jde přes <code>PlaceOrderCommand</code>, čtení přes <code>GetOrdersQuery</code> — dva oddělené toky dat.</p>

    {% if result %}<div class="alert alert-success">{{ result }}</div>{% endif %}

    <div class="row g-4">
        <div class="col-md-5">
            <h3>Command (Write side)</h3>
            <form method="post" data-turbo="false">
                <div class="mb-2"><input type="text" name="customer" value="zákazník-1" class="form-control" placeholder="ID zákazníka"></div>
                <div class="mb-2"><input type="text" name="product" value="Symfony kurz" class="form-control" placeholder="Produkt"></div>
                <div class="mb-2"><input type="number" name="qty" value="1" min="1" class="form-control" placeholder="Množství"></div>
                <div class="mb-2"><input type="number" name="price" value="999" class="form-control" placeholder="Cena (CZK)"></div>
                <button class="btn btn-primary">Dispatch PlaceOrderCommand</button>
            </form>
        </div>
        <div class="col-md-7">
            <h3>Query (Read side) — OrderView</h3>
            {% if orders is empty %}
                <p class="text-muted">Zatím žádné objednávky. Zadej první příkaz.</p>
            {% else %}
                <table class="table table-sm">
                    <thead><tr><th>ID</th><th>Zákazník</th><th>Položky</th><th>Celkem</th></tr></thead>
                    <tbody>
                    {% for order in orders %}
                        <tr>
                            <td><code>{{ order.id }}</code></td>
                            <td>{{ order.customerId }}</td>
                            <td>{{ order.itemCount }}</td>
                            <td>{{ order.total }}</td>
                        </tr>
                    {% endfor %}
                    </tbody>
                </table>
            {% endif %}
        </div>
    </div>

    <hr class="my-5">
    <h2>Write Model vs. Read Model</h2>
    <p>Command side a Query side používají různé reprezentace dat — každá optimalizovaná pro svůj účel.</p>

    <div class="row g-4">
        <div class="col-md-6">
            <div class="card h-100 border-danger">
                <div class="card-header bg-danger text-white"><strong>Write Model — Order agregát</strong></div>
                <div class="card-body">
                    <p class="text-muted small">Chrání invarianty. Žádné veřejné gettery na data.</p>
                    <pre class="bg-light p-2 rounded small">class Order extends AggregateRoot {
    private string $id;          // skryté
    private string $customerId;  // skryté
    private int $totalAmount;    // skryté
    private array $items;        // skryté

    // Sémantické metody (ne settery):
    public static function place(...): self
    public function confirm(): void
    public function cancel(): void
    public function pullEvents(): array
}</pre>
                    <p class="text-muted small mt-2">Načítá se přes <strong>ORM Repository</strong> — celý agregát do paměti.</p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100 border-success">
                <div class="card-header bg-success text-white"><strong>Read Model — OrderView DTO</strong></div>
                <div class="card-body">
                    <p class="text-muted small">Flat objekt optimalizovaný pro zobrazení.</p>
                    <pre class="bg-light p-2 rounded small">final readonly class OrderView {
    public string $id;           // veřejné
    public string $customerId;   // veřejné
    public string $total;        // formátováno
    public int $itemCount;       // vypočteno
}</pre>
                    <p class="text-muted small mt-2">Plněn přes <strong>DBAL Connection</strong> — raw SQL, žádný ORM overhead:</p>
                    <pre class="bg-light p-2 rounded small">SELECT id, customer_id,
       total_amount, items
FROM ch05_orders
ORDER BY rowid DESC</pre>
                </div>
            </div>
        </div>
    </div>
    <p class="text-muted mt-3 small">Write model zajišťuje konzistenci (invarianty). Read model zajišťuje výkon (žádné lazy loading, žádný overhead ORM). Každá strana se vyvíjí nezávisle.</p>
</div>
{% endblock %}
```

- [ ] **Step 4: Verify in browser**

Go to `http://localhost:8001/examples/cqrs`. Existing orders render. Dispatch a new command — appears in table. Scroll down to "Write Model vs. Read Model" — both cards render with code snippets.

- [ ] **Step 5: Commit**

```bash
git add src/Chapter05_CQRS/Application/GetOrders/GetOrdersHandler.php \
        templates/examples/chapter05/index.html.twig
git commit -m "feat(ch05): add Write vs. Read Model section, switch query handler to DBAL"
```

---

## Final verification

- [ ] **Run all tests**

```bash
cd /home/michal/Work/ddd-symfony-examples
./vendor/bin/phpunit --testdox
```

Expected: all green.

- [ ] **Smoke-check all 4 extended pages**

```bash
for url in co-je-ddd zakladni-koncepty implementace cqrs; do
  code=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:8001/examples/$url)
  echo "$url: $code"
done
```

Expected: all `200`.
