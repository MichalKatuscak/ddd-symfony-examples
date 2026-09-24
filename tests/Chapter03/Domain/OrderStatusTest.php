<?php

declare(strict_types=1);

namespace App\Tests\Chapter03\Domain;

use App\Chapter03_BasicConcepts\Domain\Order\OrderStatus;
use PHPUnit\Framework\TestCase;

final class OrderStatusTest extends TestCase
{
    public function test_has_the_canonical_states(): void
    {
        self::assertSame(
            ['draft', 'confirmed', 'paid', 'shipped', 'delivered', 'cancelled'],
            array_map(static fn (OrderStatus $s): string => $s->value, OrderStatus::cases()),
        );
    }

    public function test_from_string_works(): void
    {
        self::assertSame(OrderStatus::Confirmed, OrderStatus::from('confirmed'));
    }
}
