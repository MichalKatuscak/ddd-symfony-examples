<?php

declare(strict_types=1);

namespace App\Chapter05_CQRS\Ordering\Domain\Event;

use App\Chapter05_CQRS\Ordering\Domain\ValueObject\OrderId;
use App\Chapter05_CQRS\Ordering\Domain\ValueObject\ProductId;

final readonly class OrderItemAdded
{
    public \DateTimeImmutable $occurredAt;

    public function __construct(
        public OrderId $orderId,
        public ProductId $productId,
        public int $quantity,
    ) {
        $this->occurredAt = new \DateTimeImmutable();
    }
}
