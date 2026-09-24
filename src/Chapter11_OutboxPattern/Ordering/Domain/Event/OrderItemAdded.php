<?php

declare(strict_types=1);

namespace App\Chapter11_OutboxPattern\Ordering\Domain\Event;

use App\Chapter11_OutboxPattern\Ordering\Domain\ValueObject\OrderId;
use App\Chapter11_OutboxPattern\Ordering\Domain\ValueObject\ProductId;

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
