<?php

declare(strict_types=1);

namespace App\Chapter12_LesserPatterns\Domain\Order;

use App\Chapter12_LesserPatterns\Domain\Customer\CustomerId;

/**
 * Košík — pomocná entita pro demonstraci Factory class (OrderFromCartFactory).
 * Objednávka z košíku vyžaduje kolaboraci s pricing servicem, proto se vznik
 * agregátu Order neobejde s pouhou statickou metodou na Order.
 */
final class Cart
{
    /**
     * @param list<array{name: string, quantity: int, unitPriceMinorUnits: int}> $rawItems
     */
    public function __construct(
        private readonly string $id,
        private readonly CustomerId $customerId,
        private readonly array $rawItems,
        private readonly string $shippingCountry,
    ) {}

    public function id(): string
    {
        return $this->id;
    }

    public function customerId(): CustomerId
    {
        return $this->customerId;
    }

    public function shippingCountry(): string
    {
        return $this->shippingCountry;
    }

    public function isEmpty(): bool
    {
        return count($this->rawItems) === 0;
    }

    /** @return list<array{name: string, quantity: int, unitPriceMinorUnits: int}> */
    public function rawItems(): array
    {
        return $this->rawItems;
    }
}
