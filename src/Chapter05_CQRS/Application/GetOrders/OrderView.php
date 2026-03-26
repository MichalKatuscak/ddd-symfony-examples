<?php
namespace App\Chapter05_CQRS\Application\GetOrders;
final readonly class OrderView
{
    public function __construct(
        public readonly string $id,
        public readonly string $customerId,
        public readonly string $total,
        public readonly int $itemCount,
    ) {}
}
