<?php

declare(strict_types=1);

namespace App\Chapter03_BasicConcepts\Domain\Repository;

use App\Chapter03_BasicConcepts\Domain\Order\Exception\OrderNotFoundException;
use App\Chapter03_BasicConcepts\Domain\Order\Order;
use App\Chapter03_BasicConcepts\Domain\Order\OrderId;

// Záměrně úzký: uložit agregát a načíst ho podle identity. Dotazy typu
// „všechny objednávky zákazníka“ obsluhuje read model (kapitola CQRS).
interface OrderRepository
{
    public function save(Order $order): void;

    /** @throws OrderNotFoundException když objednávka neexistuje */
    public function get(OrderId $id): Order;
}
