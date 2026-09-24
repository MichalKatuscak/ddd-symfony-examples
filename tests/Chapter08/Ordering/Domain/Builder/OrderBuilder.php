<?php

declare(strict_types=1);

namespace App\Tests\Chapter08\Ordering\Domain\Builder;

use App\Chapter02_AggregateDesign\Domain\Order\CustomerId;
use App\Chapter02_AggregateDesign\Domain\Order\Order;
use App\Chapter02_AggregateDesign\Domain\Order\OrderId;
use App\Chapter02_AggregateDesign\Domain\Order\ProductId;
use App\Shared\Domain\Currency;
use App\Shared\Domain\Money;

/**
 * Test Data Builder (Freeman, Pryce): bezpečné výchozí hodnoty,
 * řetězitelné přepisy a build(). Builder je mutabilní, takže si ho
 * každý test staví znovu přes anOrder().
 */
final class OrderBuilder
{
    private OrderId $orderId;
    private CustomerId $customerId;

    /** @var list<array{ProductId, int, Money}> */
    private array $items = [];

    private bool $confirmed = false;

    private function __construct()
    {
        // Bezpečné výchozí hodnoty - test nastavuje jen to, na čem mu skutečně záleží
        $this->orderId    = OrderId::generate();
        $this->customerId = CustomerId::generate();
    }

    public static function anOrder(): self
    {
        return new self();
    }

    public function forCustomer(CustomerId $customerId): self
    {
        $this->customerId = $customerId;

        return $this;
    }

    public function withItem(int $quantity = 1, ?Money $unitPrice = null): self
    {
        $this->items[] = [
            ProductId::generate(),
            $quantity,
            $unitPrice ?? new Money(49900, Currency::CZK),
        ];

        return $this;
    }

    public function confirmed(): self
    {
        $this->confirmed = true;

        return $this;
    }

    public function build(): Order
    {
        $order = Order::place($this->orderId, $this->customerId);

        // Objednávka bez položek nejde potvrdit, výchozí položka je proto bezpečná hodnota
        $items = $this->items !== []
            ? $this->items
            : [[ProductId::generate(), 1, new Money(49900, Currency::CZK)]];

        foreach ($items as [$productId, $quantity, $unitPrice]) {
            $order->addItem($productId, $quantity, $unitPrice);
        }

        if ($this->confirmed) {
            $order->confirm();
        }

        return $order;
    }
}
