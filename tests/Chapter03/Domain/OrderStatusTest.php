<?php

declare(strict_types=1);

namespace App\Tests\Chapter03\Domain;

use App\Chapter03_BasicConcepts\Domain\Order\OrderStatus;
use PHPUnit\Framework\TestCase;

final class OrderStatusTest extends TestCase
{
    public function test_has_pending_case(): void
    {
        $this->assertSame('pending', OrderStatus::Pending->value);
    }

    public function test_has_confirmed_case(): void
    {
        $this->assertSame('confirmed', OrderStatus::Confirmed->value);
    }

    public function test_has_cancelled_case(): void
    {
        $this->assertSame('cancelled', OrderStatus::Cancelled->value);
    }

    public function test_from_pending_string_works(): void
    {
        $status = OrderStatus::from('pending');
        $this->assertSame(OrderStatus::Pending, $status);
    }

    public function test_from_confirmed_string_works(): void
    {
        $status = OrderStatus::from('confirmed');
        $this->assertSame(OrderStatus::Confirmed, $status);
    }

    public function test_from_cancelled_string_works(): void
    {
        $status = OrderStatus::from('cancelled');
        $this->assertSame(OrderStatus::Cancelled, $status);
    }
}
