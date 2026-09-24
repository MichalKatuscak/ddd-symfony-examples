<?php

declare(strict_types=1);

namespace App\Chapter12_LesserPatterns\Ordering\Domain\Repository;

use App\Chapter12_LesserPatterns\Ordering\Domain\Model\Order;
use App\Chapter12_LesserPatterns\Ordering\Domain\ValueObject\OrderId;

interface OrderRepository
{
    public function save(Order $order): void;

    public function get(OrderId $id): Order;
}
