<?php

declare(strict_types=1);

namespace App\Tests\Chapter12\Ordering;

use App\Chapter12_LesserPatterns\Ordering\Domain\Model\Order;
use App\Chapter12_LesserPatterns\Ordering\Domain\Model\OrderItem;
use App\Chapter12_LesserPatterns\Ordering\Domain\ValueObject\CustomerId;
use App\Chapter12_LesserPatterns\Ordering\Domain\ValueObject\ProductId;
use App\Chapter12_LesserPatterns\Ordering\Domain\ValueObject\ShippingAddress;
use App\Shared\Domain\Currency;
use App\Shared\Domain\Money;

/** Fyzická objednávka s jednou položkou v zadané hodnotě a zemi. */
final class OrderMother
{
    public static function physical(int $totalInCents, string $country = 'CZ', ?CustomerId $customer = null): Order
    {
        return Order::placePhysical(
            $customer ?? CustomerId::generate(),
            [new OrderItem(ProductId::generate(), 1, new Money($totalInCents, Currency::CZK))],
            new \DateTimeImmutable('2026-03-01 10:00:00'),
            new ShippingAddress('Ukázková 1', 'Město', '110 00', $country),
        );
    }
}
