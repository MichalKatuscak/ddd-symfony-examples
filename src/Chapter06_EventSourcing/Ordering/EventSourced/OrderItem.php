<?php

declare(strict_types=1);

namespace App\Chapter06_EventSourcing\Ordering\EventSourced;

/**
 * Neměnný záznam položky. Musí jít serializovat, protože cestuje
 * uvnitř události do Event Store a zpátky.
 */
final readonly class OrderItem
{
    public function __construct(
        public string $productId,
        public int $quantity,
        public int $unitPriceInCents,
    ) {}
}
