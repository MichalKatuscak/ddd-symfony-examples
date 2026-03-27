<?php

declare(strict_types=1);
namespace App\Chapter05_CQRS\Application\GetOrders;
final readonly class OrderView
{
    public function __construct(
        public string $id,
        public string $customerId,
        public string $total,
        public int $itemCount,
    ) {}
}
