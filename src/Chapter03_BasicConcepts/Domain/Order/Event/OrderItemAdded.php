<?php

declare(strict_types=1);

namespace App\Chapter03_BasicConcepts\Domain\Order\Event;

use App\Chapter03_BasicConcepts\Domain\Order\OrderId;
use App\Chapter03_BasicConcepts\Domain\Order\ProductId;

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
