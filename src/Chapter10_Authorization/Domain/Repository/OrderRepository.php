<?php

declare(strict_types=1);

namespace App\Chapter10_Authorization\Domain\Repository;

use App\Chapter02_AggregateDesign\Domain\Order\OrderId;
use App\Chapter10_Authorization\Domain\Order\Exception\OrderNotFoundException;
use App\Chapter10_Authorization\Domain\Order\Order;

interface OrderRepository
{
    public function save(Order $order): void;

    /** @throws OrderNotFoundException když objednávka neexistuje */
    public function get(OrderId $id): Order;
}
